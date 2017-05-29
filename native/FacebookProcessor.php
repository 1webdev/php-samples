<?php

namespace Facebook;

require_once 'facebook-php-sdk-v5/src/Facebook/autoload.php';
require_once 'processors/BasePullProcessor.php';
require_once 'processors/traits/Moderation.php';
require_once 'processors/traits/ExtensionData.php';
require_once 'socialProviderConn/FacebookConn.php';

use BasePullProcessor;
use User2Model;

class FacebookProcessor extends \BasePullProcessor
{
    use \Moderation;
    use \ExtensionData;

    const BATCH_SIZE_POSTS = 20;
    const BATCH_SIZE_FEED_COMMENTS = 50;

    const POST_FIELDS = 'id,type,message_tags,to,created_time,updated_time,from,message,story,link,picture,is_hidden,shares,scheduled_publish_time';
    const CONVERSATION_FIELDS = 'id,can_reply,is_subscribed,link,message_count,snippet,subject,updated_time,unread_count';
    const COMMENT_FIELDS = 'id,message,created_time,from,attachment,is_hidden,can_hide,can_remove,can_comment,parent.fields(id)';
    const PHOTOS_FIELDS = 'id,created_time,from,height,link,name,picture,page_story_id,source,updated_time,album.fields(id,from,name,link,cover_photo,count,type,created_time,updated_time,can_upload)';
    const MESSAGE_FIELDS = 'id,from,message,subject,to,created_time,attachments,shares';
    const RATING_FIELDS = 'id,created_time,has_rating,has_review,open_graph_story,rating,review_text,reviewer';
    const VIDEO_FIELDS = 'created_time,description,from,id,permalink_url,picture,published,scheduled_publish_time,source,status,title,updated_time';

    protected $facebookConn;
    private $baseFbUrl;
    private $alertEmailSent = false;

    protected function init()
    {
        parent::init();
        $user_model = null;
        if (isset($_REQUEST['userId']) && $_REQUEST['userId'] != '') {
            $this->logAction('INITIALIZE', 'USER_TOKEN', 'Use facebook user token', self::DEBUG_MSG);
            $user_model = new User2Model($this->primaryConnection);
            $user_model->setId($_REQUEST['userId']);
            $user_model->read();
        }
        $this->facebookConn = new FacebookConn($this->processorModel, $this->streamModel, $this, $user_model);

        $this->baseFbUrl = ($this->streamModel->getOwned()) ? 'https://business.facebook.com/' : 'https://www.facebook.com/';
    }

    protected function initProcessorData($validData = false)
    {
        $validData = false;
        if (is_array($this->processorData)) {
            if (array_key_exists('VERSION', $this->processorData)) {
                if ($this->processorData['VERSION'] == 11) {
                    $validData = true;
                }
            }
        }

        if (!$validData) {
            $this->logAction('INIT_PROC_DATA', 'INTERNAL', 'Valid proc_data not found - Initializing.', self::DEBUG_MSG);
            $newProcData = array(
                'VERSION' => 11,
                'INVOKE_TIME' => $this->invoke_time,
                'NEW_POST_SET' => array(),
                'MED_POST_SET' => array(),
                'REQUESTS' => array(
                        'FEED' => array('URL' => 'feed',           'SUB_URL' => 'comments'),
                        'CONVERSATIONS' => array('URL' => 'conversations',  'SUB_URL' => 'messages'),
                        'PHOTOS' => array('URL' => 'photos',         'SUB_URL' => 'comments'),
                        'PROMOTABLE' => array('URL' => 'promotable_posts', 'SUB_URL' => 'comments'),
                        'RATINGS' => array('URL' => 'ratings',        'SUB_URL' => 'comments'),
                        // call to action
                    ),
                'NEXT_RUN' => array(
                        'NEW' => ($this->getNextRunTime('NEW')),
                        'OLD' => ($this->getNextRunTime('OLD')),
                    ),
                'CURSORS' => array(
                    'NEW' => array(
                        'FEED' => array('SINCE' => 0, 'UNTIL' => 0),
                        'CONVERSATIONS' => array('SINCE' => 0, 'UNTIL' => 0),
                        'PHOTOS' => array('SINCE' => 0, 'UNTIL' => 0),
                        'PROMOTABLE' => array('SINCE' => 0, 'UNTIL' => 0),
                        'RATINGS' => array('SINCE' => 0, 'UNTIL' => 0),
                    ),

                    'OLD' => array(
                        'FEED' => array('SINCE' => 0, 'UNTIL' => 0),
                        'CONVERSATIONS' => array('SINCE' => 0, 'UNTIL' => 0),
                        'PHOTOS' => array('SINCE' => 0, 'UNTIL' => 0),
                        'PROMOTABLE' => array('SINCE' => 0, 'UNTIL' => 0),
                        'RATINGS' => array('SINCE' => 0, 'UNTIL' => 0),
                    ),
                ),
                'ACCESS_TOKEN' => $this->processorModel->getOAAccessToken(),
                'REMAINING_CALLS' => 600,
            );

            $this->processorData = $newProcData;
            $validData = true;
        }
        parent::initProcessorData(true);

        return $validData;
    }

    public function acceptAll($pending)
    {
        $batches = [];
        $requests = [];

        while ($pending) {
            $action = $this->feedEntryModel->getStatusCode();
            if (strpos($this->feedEntryModel->getEntryType(), 'COMMENT') !== false) {
                $entry_type = 'COMMENT';
            } elseif (strpos($this->feedEntryModel->getEntryType(), 'PRIVATE MESSAGE') !== false) {
                $entry_type = 'DM';
            } else {
                $entry_type = 'POST';
            }
            $request = [];
            $on_success = 'UNKNOWN';
            $actionType = $action.'_'.$entry_type;
            if ($this->facebookConn->hasPermission($actionType) == true) {
                switch ($actionType) {
                    case 'DELETE_COMMENT':
                    case 'DELETE_POST':
                        $entry_data = $this->feedEntryModel->getEntryData();
                        if (!isset($entry_data['can_remove']) || $entry_data['can_remove']) {
                            $request['method'] = 'DELETE';
                            $request['relative_url'] = '/'.$this->feedEntryModel->getIdCode();
                            $on_success = 'DELETED';
                        } else {
                            $this->logAction('DELETE_'.$entry_type, 'ACCEPT_ALL', 'Cannot Remove '.$entry_type.'. can_remove field is FALSE', self::LOG_MSG, 1);
                            $this->feedEntryModel->setStatusCode('ERROR');
                            $this->feedEntryModel->appendExtData('error', 'Cannot Remove '.$entry_type.'. can_remove field is FALSE');
                            $this->feedEntryModel->updateStatus();
                        }
                        break;
                    case 'HIDE_COMMENT':
                    case 'HIDE_POST':
                        $entry_data = $this->feedEntryModel->getEntryData();
                        if (!isset($entry_data['can_hide']) || $entry_data['can_hide']) {
                            $request['method'] = 'POST';
                            $request['relative_url'] = '/'.$this->feedEntryModel->getIdCode();
                            $request['body'] = 'is_hidden=true';
                            $on_success = 'HIDDEN';
                        } else {
                            $this->logAction('HIDE_'.$entry_type, 'ACCEPT_ALL', "Facebook doesn't allow you to hide this ".$entry_type, self::LOG_MSG, 1);
                            $this->feedEntryModel->setStatusCode('ERROR');
                            $this->feedEntryModel->appendExtData('error', "Facebook doesn't allow you to hide this $entry_type");
                            $this->feedEntryModel->updateStatus();
                        }
                        break;
                    default:
                        $this->logAction($action.'_ERR', 'API_CALL', 'Unsupported operation putStreamUpdate: '.$action.'_'.$entry_type, self::LOG_MSG, 1);
                        $this->feedEntryModel->setStatusCode('UNSUPP');
                        $this->feedEntryModel->appendExtData('error', "Unsupported action: $action");
                        $this->feedEntryModel->updateStatus();
                        break;
                }
            }
            //if the request is not empty
            if (!empty($request)) {
                //add it to list of requests
                $requests[] = array(
                    'id_code' => $this->feedEntryModel->getIdCode(),
                    'action' => $action,
                    'entry_type' => $entry_type,
                    'on_success' => $on_success,
                );
                //add into batch list
                $batches[] = json_encode($request);
            }
            $pending = $this->feedEntryModel->nextRow();
        }
        //split into max 50 items per chunk
        $batches = array_chunk($batches, 50, true);
        $requests = array_chunk($requests, 50);

        foreach ($batches as $key => $batch) {
            $params = array(
                'batch' => '['.implode(',', $batch).']',
            );
            $responses = $this->facebookConn->apiCall(
                '/',
                0,
                'POST',
                \ApiLogModel::BATCH_REQUEST,
                'Batch Request to GraphAPI with params:'.json_encode($params),
                $params
            );
            if ($responses && !isset($responses['error_msg'])) {
                foreach ($responses as $k => $response) {
                    $action = $requests[$key][$k]['action'].'_'.$requests[$key][$k]['entry_type'];
                    if ($response) {
                        //check response code
                        switch ($response['code']) {
                            case '200':
                            case '203':
                                $this->feedEntryModel->setStatusCode($requests[$key][$k]['on_success']);
                                $this->logAction($action, 'ACCEPT_ALL', 'Item successfully '.$requests[$key][$k]['on_success'], self::DEBUG_MSG);
                                break;
                            default:
                                $body = json_decode($response['body'], true);
                                $error = (isset($body['error']['message'])) ? $body['error']['message'] : 'Unknown error';
                                $this->feedEntryModel->setStatusCode('ERROR');
                                $this->feedEntryModel->appendExtData('error', $error);
                                $this->logAction($action, 'ACCEPT_ALL', $error, self::LOG_MSG);
                                break;
                        }
                    } else {
                        $this->feedEntryModel->setStatusCode('ERROR');
                        $this->feedEntryModel->appendExtData('error', 'Operation not completed');
                        $this->logAction($action, 'ACCEPT_ALL', 'Timeout. Facebook API returned NULL', self::LOG_MSG);
                    }
                    //update feedEntry
                    $this->feedEntryModel->updateStatus();
                }
            }
        }

        return true;
    }

    public function putStreamUpdate()
    {
        $action = $this->feedEntryModel->getStatusCode();
        if (strpos($this->feedEntryModel->getEntryType(), 'COMMENT') !== false) {
            $entry_type = 'COMMENT';
        } elseif (strpos($this->feedEntryModel->getEntryType(), 'PRIVATE MESSAGE') !== false) {
            $entry_type = 'DM';
        } elseif(strpos($this->feedEntryModel->getEntryType(), 'PRIVATE REPLY') !== false){
            $entry_type = 'PRIVATE_REPLY';
        }else {
            $entry_type = 'POST';
        }

        $result = true;
        $status = false;
        $actionType = $action.'_'.$entry_type;
        if ($this->facebookConn->hasPermission($actionType) == true) {
            switch ($actionType) {
                case 'PENDING_DM':
                    $request = '/'.$this->feedEntryModel->getParentIdCode().'/messages';
                    $response = $this->facebookConn->apiCall(
                        $request,
                        1,
                        'POST',
                        \ApiLogModel::DIRECT_MESSAGE,
                        'Sending POST to GraphAPI: '.$request.' Message: '.$this->feedEntryModel->getEntryText(),
                        array('message' => urldecode($this->feedEntryModel->getEntryText()))
                    );

                    if (isset($response['error_msg'])) {
                        $this->feedEntryModel->setStatusCode('ERROR');
                        $this->feedEntryModel->appendExtData('error', $response['error_msg']);
                    } else {
                        $this->feedEntryModel->setOldIdCode($this->feedEntryModel->getIdCode());
                        $request = '/'.$response['id'];
                        $entry = $this->facebookConn->apiCall(
                            $request,
                            1,
                            'GET',
                            \ApiLogModel::DIRECT_MESSAGE,
                            'Get direct message from GraphAPI: '.$request
                        );

                        $entry['parent_id'] = $this->feedEntryModel->getParentIdCode();
                        $entry['type'] = 'private message';
                        $entry['link'] = '';
                    }
                    $result = true;
                    break;
                case 'PENDING_POST':
                    $request = '/'.$this->streamModel->getStreamCode().'/feed';
                    $response = $this->facebookConn->apiCall(
                        $request,
                        1,
                        'POST',
                        \ApiLogModel::POST_NEW,
                        'Sending POST to GraphAPI: '.$request.' Message: '.$this->feedEntryModel->getEntryText(),
                        ['message' => urldecode($this->feedEntryModel->getEntryText())]
                    );

                    if (isset($response['error_msg'])) {
                        $this->feedEntryModel->setStatusCode('ERROR');
                        $this->feedEntryModel->appendExtData('error', $response['error_msg']);
                    } else {
                        $this->feedEntryModel->setOldIdCode($this->feedEntryModel->getIdCode());
                        $request = '/'.$response['id'].'?fields='.self::POST_FIELDS;
                        $entry = $this->facebookConn->apiCall(
                            $request,
                            1,
                            'GET',
                            \ApiLogModel::GET_POST,
                            'Get POST from GraphAPI: '.$request
                        );
                        $entry['type'] = $this->feedEntryModel->getEntryType();
                    }
                    $result = true;
                    break;
                case 'PENDING_COMMENT':
                    $request = '/'.$this->feedEntryModel->getParentIdCode().'/comments';
                    $response = $this->facebookConn->apiCall(
                        $request,
                        1,
                        'POST',
                        \ApiLogModel::COMMENT_NEW,
                        'Sending COMMENT to GraphAPI: '.$request.' Message: '.$this->feedEntryModel->getEntryText(),
                        ['message' => urldecode($this->feedEntryModel->getEntryText())]
                    );

                    if (isset($response['error_msg'])) {
                        if ($response['error_msg'] == '(#200) Permissions error') {
                            //for some reason this is the error we get when we try to reply to an non-existing entry
                            //try to get parent entry
                            $request = '/'.$this->feedEntryModel->getParentIdCode();
                            $entry = $this->facebookConn->apiCall(
                                $request,
                                1,
                                'GET',
                                \ApiLogModel::GET_COMMENT,
                                'Get COMMENT from GraphAPI: '.$request
                            );
                            if (is_object($this->lastApiError) && in_array($this->lastApiError->getCode(), [100, 1705])) {
                                // if the parent entry doesn't exist
                                $this->processorModel->setLastError('The entry you are trying to reply to doesn\'t exist any more.');
                            }
                        } elseif (is_object($this->lastApiError) && in_array($this->lastApiError->getCode(), [100, 1705])) {
                            // if the parent entry doesn't exist
                            $this->processorModel->setLastError('The entry you are trying to reply to doesn\'t exist any more.');
                        }

                        $this->feedEntryModel->setStatusCode('ERROR');
                        $this->feedEntryModel->appendExtData('error', $response['error_msg']);
                    } else {
                        $this->feedEntryModel->setOldIdCode($this->feedEntryModel->getIdCode());

                        // weird thing: cannot request can_hide field with an user token
                        $comment_fields = ($this->facebookConn->postAsModerator)
                            ? str_replace('can_hide,', '', self::COMMENT_FIELDS)
                            : self::COMMENT_FIELDS;

                        $request = '/'.$response['id'].'?fields='.$comment_fields;
                        $entry = $this->facebookConn->apiCall(
                            $request,
                            1,
                            'GET',
                            \ApiLogModel::GET_COMMENT,
                            'Get COMMENT from GraphAPI: '.$request
                        );
                        $entry['type'] = $this->feedEntryModel->getEntryType();
                        $entry['parent_id'] = $this->feedEntryModel->getParentIdCode();
                        $entry['is_comment_reply'] = false;
                    }
                    $result = true;
                    break;
                case 'PENDING_PRIVATE_REPLY':
                    $request = '/'.$this->feedEntryModel->getParentIdCode().'/private_replies';
                    $response = $this->facebookConn->apiCall(
                        $request,
                        1,
                        'POST',
                        \ApiLogModel::PRIVATE_REPLY,
                        'Sending PRIVATE REPLY to GraphAPI: '.$request.' Message: '.$this->feedEntryModel->getEntryText(),
                        ['message' => urldecode($this->feedEntryModel->getEntryText())]
                    );
                    
                    if (isset($response['error_msg'])) {
                        if ($response['error_msg'] == '(#200) Permissions error') {
                            //for some reason this is the error we get when we try to reply to an non-existing entry
                            //try to get parent entry
                            $request = '/'.$this->feedEntryModel->getParentIdCode();
                            $entry = $this->facebookConn->apiCall(
                                $request,
                                1,
                                'GET',
                                \ApiLogModel::GET_COMMENT,
                                'Get COMMENT from GraphAPI: '.$request
                            );
                            if (is_object($this->lastApiError) && in_array($this->lastApiError->getCode(), [100, 1705])) {
                                // if the parent entry doesn't exist
                                $this->processorModel->setLastError('The entry you are trying to reply to doesn\'t exist any more.');
                            }
                        } elseif (is_object($this->lastApiError) && in_array($this->lastApiError->getCode(), [100, 1705])) {
                            // if the parent entry doesn't exist
                            $this->processorModel->setLastError('The entry you are trying to reply to doesn\'t exist any more.');
                        }

                        $this->feedEntryModel->setStatusCode('ERROR');
                        $this->feedEntryModel->appendExtData('error', $response['error_msg']);
                    } else {
                        $this->feedEntryModel->setOldIdCode($this->feedEntryModel->getIdCode());
                        $request = '/'.$response['id'];
                        $entry = $this->facebookConn->apiCall(
                            $request,
                            1,
                            'GET',
                            \ApiLogModel::DIRECT_MESSAGE,
                            'Get direct message from GraphAPI: '.$request
                        );
                        $conversationsId = $this->getConversationsId($response['id']);
                        $entry['parent_id'] = $conversationsId ? $conversationsId : $this->feedEntryModel->getParentIdCode();
                        $entry['type'] = 'private message';
                        $entry['link'] = '';
                    }
                    $result = true;
                    break;
                case 'DELETE_COMMENT':
                case 'DELETE_POST':
                    $entry_data = $this->feedEntryModel->getEntryData();
                    if (!isset($entry_data['can_remove']) || $entry_data['can_remove']) {
                        $i = 0;
                        $request = '/'.$this->feedEntryModel->getIdCode();
                        while ($i <= 1) {
                            if ($i == 1) {
                                //wait for 2 microseconds
                                usleep(200000);
                            }
                            $response = $this->facebookConn->apiCall(
                                $request,
                                1,
                                'DELETE',
                                \ApiLogModel::POST_DELETE,
                                'Delete POST from GraphAPI: '.$request
                            );

                            if (!$response) {
                                if (is_object($this->lastApiError) && in_array($this->lastApiError->getCode(), [100, 1705])) {
                                    //(#1705) Selected wall post for deletion does not exist.
                                    //(#100) Error finding the requested story
                                    $this->feedEntryModel->setStatusCode('DELETED');
                                    break;
                                } else {
                                    $this->feedEntryModel->setStatusCode('ERROR');
                                    if (isset($response['error_msg'])) {
                                        $this->feedEntryModel->appendExtData('error', $response['error_msg']);
                                    }
                                }
                            } elseif (isset($response['success']) && $response['success'] == 'true') {
                                $this->feedEntryModel->setStatusCode('DELETED');
                                $request = '/'.$this->feedEntryModel->getIdCode();
                                $entry = $this->facebookConn->apiCall(
                                    $request,
                                    1,
                                    'GET',
                                    \ApiLogModel::GET_POST,
                                    'Get POST from GraphAPI: '.$request
                                );

                                if (is_object($this->lastApiError) && in_array($this->lastApiError->getCode(), [100, 1705])) {
                                    //(#1705) Selected wall post for deletion does not exist.
                                    //(#100) Error finding the requested story
                                    $this->feedEntryModel->setStatusCode('DELETED');
                                    break;
                                } else {
                                    $this->feedEntryModel->setStatusCode('ERROR');
                                    if (isset($response['error_msg'])) {
                                        $this->feedEntryModel->appendExtData('error', $response['error_msg']);
                                    }
                                }
                            } else {
                                $this->feedEntryModel->setStatusCode('ERROR');
                                if (isset($response['error_msg'])) {
                                    $this->feedEntryModel->appendExtData('error', $response['error_msg']);
                                }
                            }
                            ++$i;
                        }
                    } else {
                        $this->logAction('DELETE_'.$entry_type, 'PUT_STREAM_UPDATE', 'Cannot Remove '.$entry_type.'. can_remove field is FALSE', self::LOG_MSG, 1);
                        $this->feedEntryModel->setStatusCode('ERROR');
                        $this->feedEntryModel->appendExtData('error', "Cannot Remove $entry_type can_remove field is FALSE");
                    }
                    $result = true;
                    break;
                case 'HIDE_COMMENT':
                case 'HIDE_POST':
                    $entry_data = $this->feedEntryModel->getEntryData();
                    if (!isset($entry_data['can_hide']) || $entry_data['can_hide']) {
                        $i = 0;
                        $request = '/'.$this->feedEntryModel->getIdCode();
                        while ($i <= 1) {
                            if ($i == 1) {
                                //wait for 2 microseconds
                                usleep(200000);
                            }
                            $response = $this->facebookConn->apiCall(
                                $request,
                                1,
                                'POST',
                                \ApiLogModel::POST_HIDE,
                                'Hide '.$entry_type.' from GraphAPI:'.$request,
                                array('is_hidden' => 'true')
                            );

                            if (isset($response['success']) && $response['success']) {
                                $this->feedEntryModel->setOldIdCode($this->feedEntryModel->getIdCode());
                                $fields = ($entry_type == 'POST') ? self::POST_FIELDS : self::COMMENT_FIELDS;
                                $request = '/'.$this->feedEntryModel->getIdCode().'?fields='.$fields;
                                $entry = $this->facebookConn->apiCall(
                                    $request,
                                    1,
                                    'GET',
                                    \ApiLogModel::GET_POST,
                                    'Get POST from GraphAPI: '.$request
                                );

                                $entry['type'] = $this->feedEntryModel->getEntryType();
                                $entry['parent_id'] = $this->feedEntryModel->getParentIdCode();
                                $entry['is_comment_reply'] = false;
                                $status = 'HIDDEN';
                                if (isset($entry['is_hidden']) && $entry['is_hidden'] == 1) {
                                    break;
                                }
                            } else {
                                $this->feedEntryModel->setStatusCode('ERROR');
                                if (isset($response['error_msg'])) {
                                    $this->feedEntryModel->appendExtData('error', $response['error_msg']);
                                }
                                if (is_object($this->lastApiError) && in_array($this->lastApiError->getCode(), [100, 1705])) {
                                    //(#1705) Selected wall post for deletion does not exist.
                                    //(#100) Error finding the requested story
                                    $this->processorModel->setLastError('The entry you are trying to hide doesn\'t exist anymore.');
                                    $this->feedEntryModel->setStatusCode('ERROR');
                                    break;
                                }
                            }
                            ++$i;
                        }
                    } else {
                        $this->logAction('HIDE_'.$entry_type, 'PUT_STREAM_UPDATE', "Facebook doesn't allow you to hide this $entry_type", self::LOG_MSG, 1);
                        $this->processorModel->setLastError("Facebook doesn't allow you to hide this $entry_type");
                        $this->feedEntryModel->setStatusCode('ERROR');
                        $this->feedEntryModel->appendExtData('error', "Facebook doesn't allow you to hide this $entry_type");
                    }
                    $result = true;
                    break;
                case 'UNBLOCKING_DM':
                case 'UNBLOCKING_POST':
                case 'UNBLOCKING_COMMENT':
                    //Block user
                    if ($this->processorData['PAGE_TOKEN'] == 1) {
                        $request = '/'.$this->streamModel->getStreamCode().'/blocked';
                        $response = $this->facebookConn->apiCall(
                            $request,
                            0,
                            'DELETE',
                            \ApiLogModel::UNBLOCK_USER,
                            'Unblocking user via GraphAPI: '.$request.' UserId: '.$this->feedEntryModel->getAuthorCode(),
                            array('user' => $this->feedEntryModel->getAuthorCode())
                        );

                        if (isset($response['error_msg'])) {
                            $this->feedEntryModel->setStatusCode('ERROR');
                            $this->feedEntryModel->appendExtData('error', $response['error_msg']);
                        } else {
                            $this->feedEntryModel->setStatusCode('UNBLOCKED');
                            $this->feedEntryModel->appendExtData('unblocked_user', $this->feedEntryModel->getAuthorCode());
                        }
                    } else {
                        $this->feedEntryModel->setStatusCode('ERROR');
                        $this->feedEntryModel->appendExtData('error', 'Page access token is needed in order to block/unblock users.');
                        $this->processorModel->setLastError('Page access token is needed in order to block/unblock users.');
                        $this->logAction($actionType, 'PUT_STREAM_UPDATE', 'Page access token is needed in order to block/unblock users.', self::LOG_MSG, 1);
                    }
                    $result = true;
                    break;
                default:
                    $this->logAction($action.'_ERR', 'API_CALL', 'Unsupported operation putStreamUpdate: '.$action.'_'.$entry_type, self::LOG_MSG, 1);
                    $this->feedEntryModel->setStatusCode('UNSUPP');
                    $this->feedEntryModel->appendExtData('error', "Unsupported action: $action");
                    $this->processorModel->setLastError('Unsupported action');
                    $result = true;
                    break;
            }
        }

        if ($this->feedEntryModel->getOldIdCode() != '' && isset($entry['id'])) {
            $this->populateFeedModel($entry, $this->feedEntryModel, $action);
            //Save to extension data
            $this->saveReplyExtensionData();
            //Responses from the page/user should also be auto-moderated
            $this->autoModerateResponse();
            if ($status) {
                $this->feedEntryModel->setStatusCode($status);
            }
            $this->feedEntryModel->updateAllFields();
        } else {
            $this->feedEntryModel->updateStatus();
        }

        return $result;
    }

    protected function blockUser($action)
    {
        //Block user
        switch ($action) {
            case 'BLOCK':
                $req_method = 'POST';
                $api_action = \ApiLogModel::BLOCK_USER;
                break;
            case 'UNBLOCK':
                $req_method = 'DELETE';
                $api_action = \ApiLogModel::UNBLOCK_USER;
                break;
            default:
                # code...
                break;
        }

        if ($this->processorData['PAGE_TOKEN'] == 1) {
            $request = '/'.$this->streamModel->getStreamCode().'/blocked';
            $response = $this->facebookConn->apiCall(
                $request,
                0,
                $req_method,
                $api_action,
                $action.' user via GraphAPI: '.$request.' UserId: '.$this->feedEntryModel->getAuthorCode(),
                array('user' => $this->feedEntryModel->getAuthorCode())
            );

            if (isset($response['error_msg'])) {
                throw new \Exception($response['error_msg']);
            }
        } else {
            throw new \Exception('Page access token is needed in order to block/unblock users.', 1);
        }
    }

    /**
     * Like/Unlike.
     *
     * @param $action
     *
     * @throws \Exception
     */
    protected function vote($action)
    {
        //like/unlike item
        switch ($action) {
            case 'UPVOTE':
                $request_method = 'POST';
                $api_action = \ApiLogModel::UPVOTE;
                break;
            case 'REMOVE_VOTE':
                $request_method = 'DELETE';
                $api_action = \ApiLogModel::REMOVE_VOTE;
                break;
            default:
                throw new \Exception('Unsupported action: '.$action);
                break;
        }
        $request = '/'.$this->feedEntryModel->getIdCode().'/likes';
        $response = $this->facebookConn->apiCall(
            $request,
            0,
            $request_method,
            $api_action,
            $action.' via Facebook REST API: '.$request
        );
        if (isset($response['error_msg'])) {
            throw new \Exception(($response['error_msg'] === '(#1705) There was an error during posting.') ? 'Can\'t like deleted entry.' : $response['error_msg']);
        }
    }

    protected function getSourceEntries($stream_code, $type = null)
    {
        $posts = [];
        foreach ($this->processorData['REQUESTS'] as $request_type => $value) {
            if ($this->getApiThrottleProvider()->getMaxCalls() > 0) {
                if (count($this->newPostSet) == 0) {
                    $this->cursors[$request_type]['SINCE'] = null;
                }
                // Skip excluded requests
                if (is_array($this->streamModel->getExcludeRequest()) && count($this->streamModel->getExcludeRequest()) > 0) {
                    if (in_array($request_type, $this->streamModel->getExcludeRequest())) {
                        continue;
                    }
                }

                switch ($request_type) {
                    case 'FEED':
                        $posts = array_merge($posts, $this->getPosts($request_type, $stream_code, $this->cursors[$request_type]['SINCE'], $this->cursors[$request_type]['UNTIL']));
                        break;
                    case 'CONVERSATIONS':
                        if ($this->streamModel->getOwned() && $this->facebookConn->hasPermission('GET_CONVERSATIONS')) {
                            $posts = array_merge($posts, $this->getConversations($stream_code, $this->cursors[$request_type]['SINCE'], $this->cursors[$request_type]['UNTIL']));
                        }
                        break;
                    case 'PHOTOS':
                        if ($this->streamModel->getOwned()) {
                            //get photos
                            $posts = array_merge($posts, $this->getPhotos($stream_code, $this->cursors[$request_type]['SINCE'], $this->cursors[$request_type]['UNTIL']));
                        }
                        break;
                    case 'PROMOTABLE':
                        //get promotable_posts
                        if ($this->streamModel->getOwned() && $this->facebookConn->hasPermission('GET_PROMOTABLE')) {
                            $posts = array_merge($posts, $this->getPosts($request_type, $stream_code, $this->cursors[$request_type]['SINCE'], $this->cursors[$request_type]['UNTIL']));
                        }
                        break;
                    case 'RATINGS':
                        //get RATINGS
                        if ($this->streamModel->getOwned() && $this->facebookConn->hasPermission('GET_RATINGS')) {
                            $posts = array_merge($posts, $this->getPosts($request_type, $stream_code, $this->cursors[$request_type]['SINCE'], $this->cursors[$request_type]['UNTIL']));
                        }
                        break;
                }
            }
        }

        return $posts;
    }

    /**
     * Get a certain post in case it isn't in the processor data.
     *
     * @param $post_id
     *
     * @return array|bool|mixed
     */
    protected function getPost($post_id)
    {
        $post = $this->getEntryFromSource($post_id);
        if ($post['id']) {
            $new_post = $this->populatePost($post);
            if ($new_post) {
                if ($this->runType != 'OLD') {
                    $this->addPostToPostSets($new_post);
                } else {
                    $this->oldPostSet->add($post);
                }
            }
        }

        return $post;
    }

    public function getEntryFromSource($id, $child_type = false)
    {
        // if the $id starts with 't_mid.'
        if (strpos($id, 't_mid.') === 0) {
            // get conversation details
            $request = '/'.$id.'?fields='.self::CONVERSATION_FIELDS.',messages.limit('.self::BATCH_SIZE_FEED_COMMENTS.'){'.self::MESSAGE_FIELDS.'},participants';
        } else {
            // try to get a normal post
            $request = '/'.$id.'?fields='.self::POST_FIELDS;
        }
        $tries = 0;
        while ($tries < 3) {
            $post = $this->facebookConn->apiCall(
                $request,
                1,
                'GET',
                \ApiLogModel::GET_POST,
                'Get POST from GraphAPI: '.$request
            );
            // if the request fails
            if (isset($post['error_msg'], $post['error_code']) && $post['error_code'] == 100) {
                // get the correct entry type from error_msg
                preg_match('#on node type \((\w+)\)#', $post['error_msg'], $match);
                if (isset($match[1])) {
                    $type = strtoupper($match[1]);
                    switch ($type) {
                        case 'COMMENT':
                            $request = '/'.$id.'?fields='.self::COMMENT_FIELDS;
                            break;
                        case 'PHOTO':
                            $request = '/'.$id.'?fields='.self::PHOTOS_FIELDS;
                            break;
                        case 'VIDEO':
                            $request = '/'.$id.'?fields='.self::VIDEO_FIELDS;
                            break;
                        default:
                            $this->logAction('GET_POSTS', 'API_CALL', 'Unknown post type: '.$type, self::LOG_MSG);
                            //get out
                            $tries = 3;
                            break;
                    }
                }
            } else {
                // get out
                break;
            }
            ++$tries;
        }

        if (isset($post['id'])) {
            if (isset($post['messages'])) {
                $post['type'] = 'conversation';
                $post['created_time'] = $post['updated_time'];
            } elseif (isset($post['type']) && strtolower($post['type']) == 'status' && $post['from']['id'] != $this->streamModel->getStreamCode()) {
                $post['type'] = 'wall';
            } elseif ($child_type && isset($type) && $type == 'COMMENT') {
                $post['type'] = $child_type;
                if (isset($post['parent']['id'])) {
                    $post['parent_id'] = $post['parent']['id'];
                    $post['is_comment_reply'] = true;
                } else {
                    // get the parent_id from id
                    $arr = explode('_', $post['id']);
                    $post['parent_id'] = isset($arr[0]) ? $arr[0] : null;
                    $post['is_comment_reply'] = false;
                }
            } elseif (isset($type)) {
                $post['type'] = $type;
            }
        } else {
            $post = false;
        }

        return $post;
    }

    private function getPosts($request_type, $stream_code, $since, $until)
    {
        $posts = [];
        $limit = self::BATCH_SIZE_POSTS;
        $total_posts_limit = self::MAX_POSTS;

        //pull less content for OLD processors
        if ($this->runType == 'OLD') {
            $limit = 10;
            $total_posts_limit = 5;
            $this->logAction('GET_POST', 'INTERNAL', 'Total OLD posts (soft) limit: '.$total_posts_limit, self::DEBUG_MSG);
        }
        switch ($request_type) {
            case 'FEED':
                $requestBase = '/'.$stream_code.'/feed?include_hidden=true&fields='.self::POST_FIELDS.
                    ',comments.filter(stream).fields('.self::COMMENT_FIELDS.').limit('.self::BATCH_SIZE_FEED_COMMENTS.').summary(true),likes.limit(1).summary(true)'.
                    '&limit='.$limit;
                break;
            case 'PROMOTABLE':
                $promotable_limit = floor($limit/2);
                $promotable_comment_limit = floor(self::BATCH_SIZE_FEED_COMMENTS/2);
                $requestBase = '/'.$stream_code.'/promotable_posts?include_hidden=true&fields='.self::POST_FIELDS.
                    ',comments.filter(stream).fields('.self::COMMENT_FIELDS.').limit('.$promotable_comment_limit.').summary(true),likes.limit(1).summary(true)'.
                    '&is_published=false&limit='.$promotable_limit;
                break;
            case 'RATINGS':
                $requestBase = '/'.$stream_code.'/ratings?fields='.self::RATING_FIELDS.
                    ',comments.filter(stream).fields('.self::COMMENT_FIELDS.').limit('.self::BATCH_SIZE_FEED_COMMENTS.').summary(true),likes.limit(1).summary(true)'.
                    '&limit='.$limit;
                break;
        }

        $request = ($until == null) ? $requestBase : $requestBase.'&until='.$until;
        $request .= ($since == null) ? '' : '&since='.$since;

        $first_set = true;

        while ($this->getApiThrottleProvider()->getMaxCalls() > 0 && count($posts) < $total_posts_limit) {
            $result = $this->facebookConn->apiCall(
                $request,
                1,
                'GET',
                \ApiLogModel::GET_POSTS,
                'Get POST(s) from GraphAPI: '.$request
            );

            //Application/user request limit reached
            if (isset($this->lastApiError) && in_array($this->lastApiError->getCode(), array(4, 17))) {
                break;
            }

            // If data was retrieved, append it to the $stream_entries
            $entry_count = 0;
            if (is_array($result) && array_key_exists('data', $result) && is_array($result['data'])) {
                $entry_count = count($result['data']);
            }

            if ($entry_count > 0) {
                //add post_set list
                $posts = array_merge($posts, $result['data']);
                //at the first iteration get SINCE for next instance of new
                if ($this->runType == 'NEW' && $first_set) {
                    if (isset($result['paging']['previous']) && ($result['paging']['previous'] != '')) {
                        parse_str(parse_url($result['paging']['previous'], PHP_URL_QUERY), $query);
                        $this->cursors['FEED']['SINCE'] = $query['since'];
                    } else {
                        //get the creation time of the latest post
                        $this->cursors['FEED']['SINCE'] = strtotime($result['data'][0]['created_time']);
                    }
                    $first_set = false;
                }

                if (isset($result['paging']['next']) && ($result['paging']['next'] != '')) {
                    //generate next $request
                    parse_str(parse_url($result['paging']['next'], PHP_URL_QUERY), $query);
                    if (isset($query['until']) && $until != $query['until']) {
                        $until = $query['until'];
                        $this->cursors['FEED']['UNTIL'] = $until;
                        $request = $requestBase.'&until='.$until;
                    } else {
                        //if the 'NEXT' page is the same as this one
                        $this->cursors['FEED']['UNTIL'] = null;
                        $this->logAction('GET_NEXT_PAGE', 'API_CALL', 'Fb GraphAPI returned next the same as current one. break;', self::DEBUG_MSG);
                        break;
                    }
                } else {
                    //no next
                    $this->cursors['FEED']['UNTIL'] = null;
                    $this->logAction('GET_ENTRIES_OK', 'API_CALL', 'Fb GraphAPI returned no next page. break;', self::DEBUG_MSG);
                    break;
                }
                if ($entry_count < ($limit - 2)) {
                    //pulled less items than the limit
                    $this->cursors['FEED']['UNTIL'] = null;
                    $this->logAction('GET_ENTRIES_OK', 'API_CALL', 'Pulled less items than the limit. break;', self::DEBUG_MSG);
                    break;
                }
            } else {
                //no results
                $this->cursors['FEED']['UNTIL'] = null;
                $this->logAction('GET_ENTRIES_OK', 'API_CALL', 'Fb GraphAPI returned no results. break;', self::DEBUG_MSG);
                break;
            }
        }

        foreach ($posts as $key => $post) {
            if (isset($post['from'])) {
                if ($this->isRemoteMention($post, $stream_code)) {
                    $posts[$key]['type'] = 'remote mention';
                } elseif (strtolower($post['type']) == 'status' && $post['from']['id'] != $stream_code) {
                    $posts[$key]['type'] = 'wall';
                }
            } elseif (isset($post['reviewer'])) {
                $posts[$key]['type'] = 'rating';
            }

            if (isset($post['open_graph_story']['id'])) {
                $posts[$key]['id'] = $post['open_graph_story']['id'];
            }
            if (!isset($post['updated_time'])) {
                $posts[$key]['updated_time'] = $post['created_time'];
            }
        }

        return $posts;
    }

    // Check if the post is a mention from remote page
    private function isRemoteMention($post, $stream_code)
    {
        $to = false;
        $tag = false;
        $pages_mentioned_to = [];

        if (isset($post['message_tags'], $post['to']['data'])) {
            // Loop through the tags and check if there one for this page
            foreach ($post['message_tags'] as $tag) {
                if (isset($tag['id']) && $tag['id'] == $stream_code) {
                    $tag = true;
                }
            }
            // Loop through the 'to' array and check if there is only one element related to this page
            // If there are more than one - it means someone posted on this page(not remote page)
            foreach ($post['to']['data'] as $val) {
                if (isset($val['id']) && $val['id'] == $stream_code) {
                    $pages_mentioned_to[] = $val['id'];
                }
            }
            if (count($pages_mentioned_to) == 1) {
                $to = true;
            }
        }
        return $to && $tag;
    }

    private function getConversations($stream_code, $since, $until)
    {
        $posts = [];
        //pull less content for OLD processors
        $limit = self::BATCH_SIZE_POSTS;
        $total_posts_limit = self::MAX_POSTS;
        if ($this->runType == 'OLD') {
            $limit = 10;
            $total_posts_limit = 5;
            $this->logAction('GET_CONVERSATIONS', 'INTERNAL', 'Total OLD conversations (soft) limit: '.$total_posts_limit, self::DEBUG_MSG);
        }
        $requestBase = '/'.$stream_code.'/conversations?fields='.self::CONVERSATION_FIELDS.',messages.limit('.self::BATCH_SIZE_FEED_COMMENTS.'){'.self::MESSAGE_FIELDS.'},participants&limit='.$limit;

        $request = ($until == null) ? $requestBase : $requestBase.'&until='.$until;
        $request .= ($since == null) ? '' : '&since='.$since;

        $first_set = true;

        while ($this->getApiThrottleProvider()->getMaxCalls() > 0 && count($posts) <= $total_posts_limit) {
            $result = $this->facebookConn->apiCall(
                $request,
                0,
                'GET',
                \ApiLogModel::GET_CONVERSATIONS,
                'Get CONVERSATIONS from GraphAPI: '.$request
            );
            //Application/user request limit reached
            if (isset($this->lastApiError) && in_array($this->lastApiError->getCode(), array(4, 17))) {
                break;
            }
            // If data was retrieved, append it to the $stream_entries
            $entry_count = 0;
            if (is_array($result) && array_key_exists('data', $result) && is_array($result['data'])) {
                $entry_count = count($result['data']);
            }

            if ($entry_count > 0) {
                //add post_set list
                $posts = array_merge($posts, $result['data']);

                // for NEW instance, remember SINCE
                if ($this->runType == 'NEW' && $first_set) {
                    if (isset($result['paging']['previous']) && ($result['paging']['previous'] != '')) {
                        parse_str(parse_url($result['paging']['previous'], PHP_URL_QUERY), $query);
                        $this->cursors['CONVERSATIONS']['SINCE'] = $query['since'];
                    } else {
                        //get current time
                        $this->cursors['CONVERSATIONS']['SINCE'] = strtotime($this->processorModel->readNow());
                    }
                    $first_set = false;
                }

                if (isset($result['paging']['next']) && ($result['paging']['next'] != '')) {
                    //generate next $request
                    parse_str(parse_url($result['paging']['next'], PHP_URL_QUERY), $query);
                    if ($until != $query['until']) {
                        $until = $query['until'];
                        $this->cursors['CONVERSATIONS']['UNTIL'] = $until;
                        $request = $requestBase.'&until='.$until;
                    } else {
                        //if the 'NEXT' page is the same as this one
                        $this->cursors['CONVERSATIONS']['UNTIL'] = null;
                        $this->logAction('GET_NEXT_PAGE', 'API_CALL', 'Fb GraphAPI returned next the same as current one. break;', self::DEBUG_MSG);
                        break;
                    }
                } else {
                    //no next
                    $this->cursors['CONVERSATIONS']['UNTIL'] = null;
                    $this->logAction('GET_ENTRIES_OK', 'API_CALL', 'Fb GraphAPI returned no next page. break;', self::DEBUG_MSG);
                    break;
                }

                if ($entry_count < (self::BATCH_SIZE_POSTS - 2)) {
                    //pulled less items than the limit
                    $this->cursors['CONVERSATIONS']['UNTIL'] = null;
                    $this->logAction('GET_ENTRIES_OK', 'API_CALL', 'Pulled less items than the limit. break;', self::DEBUG_MSG);
                    break;
                }
            } else {
                //no results
                break;
            }
        }

        foreach ($posts as $key => $post) {
            $posts[$key]['type'] = 'conversation';
            $posts[$key]['created_time'] = $post['updated_time'];
        }

        return $posts;
    }

    private function getPhotos($stream_code, $before, $after)
    {
        $posts = [];
        //temporary, based on some errors from FB asking me to: "Please reduce the amount of data you're asking for"
        $limit = 10;
        $total_posts_limit = $limit * 3;
        //pull less content for OLD processors
        if ($this->runType == 'OLD') {
            $total_posts_limit = 5;
            $this->logAction('GET_PHOTOS', 'INTERNAL', 'Total OLD photos (soft) limit: '.$total_posts_limit, self::DEBUG_MSG);
        }
        $requestBase = '/'.$stream_code.'/photos/uploaded'.
            '?fields='.self::PHOTOS_FIELDS.
            ',comments.filter(stream).fields('.self::COMMENT_FIELDS.').limit('.self::BATCH_SIZE_FEED_COMMENTS.').summary(true),likes.limit(1).summary(true)'.
            '&limit='.$limit;
        $request = ($before == null) ? $requestBase : $requestBase.'&before='.$before;
        $request .= ($after == null) ? '' : '&after='.$after;

        while ($this->getApiThrottleProvider()->getMaxCalls() > 0 && count($posts) < $total_posts_limit) {
            $result = $this->facebookConn->apiCall(
                $request,
                1,
                'GET',
                \ApiLogModel::GET_PHOTOS,
                'Get POST(s) from GraphAPI: '.$request
            );

            //Application/user request limit reached
            if (isset($this->lastApiError) && in_array($this->lastApiError->getCode(), array(4, 17))) {
                break;
            }
            // If data was retrieved, append it to the $stream_entries
            $entry_count = 0;
            if (is_array($result) && array_key_exists('data', $result) && is_array($result['data'])) {
                $entry_count = count($result['data']);
            }

            if ($entry_count > 0) {
                //add post_set list
                $posts = array_merge($posts, $result['data']);

                //generate next $request
                // for NEW instance, remember SINCE
                $after = isset($result['paging']['cursors']['after']) ? $result['paging']['cursors']['after'] : null;
                $before = isset($result['paging']['cursors']['before']) ? $result['paging']['cursors']['before'] : null;

                $this->cursors['PHOTOS']['SINCE'] = $before;
                $this->cursors['PHOTOS']['UNTIL'] = $after;

                if ($this->runType == 'OLD') {
                    $request = $requestBase.'&after='.$after;
                    $no_next_page = !isset($result['paging']['next']) ? true : false;
                } else {
                    $request = $requestBase.'&before='.$before;
                    $no_next_page = !isset($result['paging']['previous']) ? true : false;
                }

                if ($no_next_page) {
                    $this->cursors['PHOTOS']['UNTIL'] = null;
                    $this->logAction('GET_ENTRIES_OK', 'API_CALL', 'Fb GraphAPI returned no next page. break;', self::DEBUG_MSG);
                    break;
                }
            } else {
                //no results
                $this->logAction('GET_ENTRIES_OK', 'API_CALL', 'Fb GraphAPI returned no results. break;', self::DEBUG_MSG);
                break;
            }
        }

        foreach ($posts as $key => $post) {
            if (!isset($post['page_story_id'])) {
                $posts[$key]['type'] = 'photo';
                $posts[$posts[$key]['album']['id']] = $posts[$key]['album'];
                $posts[$posts[$key]['album']['id']]['type'] = 'album';
                unset($posts[$key]['album']);
            } else {
                unset($posts[$key]);
            }
        }

        return $posts;
    }

    protected function processChildren($entry)
    {
        switch ($entry['type']) {
            case 'conversation':
                $children = $this->processPrivateMessages($entry['messages'], $entry['id'], $entry['updated_time'], $entry['link']);
                break;
            default:
                if (!isset($entry['comments'])) {
                    $entry['comments'] = [];
                }
                $children = $this->processComments($entry['comments'], $entry['id'], $entry['type'], $entry['created_time'], $entry['updated_time']);
                break;
        }

        return $children;
    }

    protected function getSourceComments($post, $limit = self::BATCH_SIZE_COMMENTS, $total_comments_limit = self::BATCH_SIZE_COMMENTS)
    {
        $comments = [];
        //pull less content for OLD processors
        if ($this->runType == 'OLD') {
            $total_comments_limit = (int) ($total_comments_limit / 2);
            $this->logAction('GET_COMMENTS', 'INTERNAL', 'Total OLD comments (soft) limit: '.$total_comments_limit, self::DEBUG_MSG);
        }
        switch ($post['type']) {
            case 'conversation':
                // Skip getting private messages if CONVERSATIONS is in exclude requests
                // Required if conversation is already in posts set, so we don't want to see new messages
                if (is_array($this->streamModel->getExcludeRequest()) && count($this->streamModel->getExcludeRequest()) > 0) {
                    if (in_array('CONVERSATIONS', $this->streamModel->getExcludeRequest())) {
                        break;
                    }
                }
                if ($this->facebookConn->hasPermission('GET_MESSAGES')) {
                    $comments = $this->getPrivateMessages($post);
                }
                break;
            default:
                $comments = $this->getComments($post, $limit, $total_comments_limit);
                break;
        }

        return $comments;
    }

    private function getComments($post, $limit, $total_comments_limit)
    {
        //disable next runs
        unset($this->processorData['NEXT_RUN'][$post['id']]);
        unset($this->processorData['CURSORS'][$post['id']]);

        if (isset($post['commentsCursors']['after'])) {
            $after = '&after='.$post['commentsCursors']['after'];
        } else {
            $after = '';
        }

        $requestBase = '/'.$post['id'].'/comments?summary=true&filter=stream&fields='.self::COMMENT_FIELDS.
            '&limit='.$limit.',likes.limit(1).summary(true),shares';

        $entry_count = 0;
        $comments = [];

        while ($this->getApiThrottleProvider()->getMaxCalls() > 0 && $entry_count < $total_comments_limit) {
            $request = $requestBase.$after;
            $result = $this->facebookConn->apiCall(
                $request,
                1,
                'GET',
                \ApiLogModel::GET_COMMENTS,
                'Get COMMENTS from GraphAPI: '.$request
            );

            //Application/user request limit reached
            if (isset($this->lastApiError) && in_array($this->lastApiError->getCode(), array(4, 17))) {
                break;
            }

            if (is_array($result) && array_key_exists('data', $result) && is_array($result['data'])) {
                $entry_count += count($result['data']);
                $comments = array_merge($comments, $result['data']);
            }

            // If no posts were returned, then maybe the cursor is expired.
            // Try getting all posts since the last update
            /*
            if ($first_call && ($entry_count == 0)) {
                $since = $post['updatedTime'];
                $request = $requestBase . '&since=' . $since;
                $result = $this->$this->facebookConn->apiCall($request);

                if (is_array($result) && array_key_exists('data', $result) && is_array($result['data'])) {
                    $entry_count += count($result['data']);
                    $comments = array_merge($comments, $result['data']);
                }
            }
             */

            if (isset($result['paging']['next'])) {
                //next page
                $after = '&after='.$result['paging']['cursors']['after'];
            } else {
                break;
            }
        }

        if (!isset($result['paging'])) {
            $result['paging'] = null;
        }

        if (!isset($result['summary'])) {
            $result['summary'] = null;
        }

        $comments = array('data' => $comments, 'paging' => $result['paging'], 'summary' => $result['summary']);
        $comments = $this->processComments($comments, $post['id'], $post['type'], $post['createdTime'], $post['updatedTime']);
        $this->setPostSchedule($comments['childrenCount'], $comments['updatedTime'], $comments['cursors'], $comments['gotAllComments'], $post);

        return $comments['data'];
    }

    protected function getPrivateMessages($post)
    {
        //disable next runs
        unset($this->processorData['NEXT_RUN'][$post['id']]);
        unset($this->processorData['CURSORS'][$post['id']]);

        $requestBase = '/'.$post['id'].'/messages?fields=id,from,message,subject,to,created_time,attachments,shares&limit='.self::BATCH_SIZE_COMMENTS;
        if (isset($post['cursors']['since'])) {
            $request = $requestBase.'&since='.$post['cursors']['since'];
        }

        $entry_count = 0;
        $messages = [];

        while ($this->getApiThrottleProvider()->getMaxCalls() > 0 && $entry_count < self::BATCH_SIZE_COMMENTS) {
            $result = $this->facebookConn->apiCall(
                $request,
                0,
                'GET',
                \ApiLogModel::GET_PRIVATE_MESSAGES,
                'Get PRIVATE MESSAGES from GraphAPI: '.$request
            );

            //Application/user request limit reached
            if (isset($this->lastApiError) && in_array($this->lastApiError->getCode(), array(4, 17))) {
                break;
            }

            if (is_array($result) && array_key_exists('data', $result) && is_array($result['data'])) {
                $entry_count += count($result['data']);
                $messages = array_merge($messages, $result);
            }

            if (isset($result['paging']['next']) && isset($result['paging']['cursors']['after'])) {
                //next page
                $after = '&after='.$result['paging']['cursors']['after'];
                $request = $requestBase.$after;
            } else {
                //if there is no 'next', then exit
                break;
            }
        }

        $messages = $this->processPrivateMessages($messages, $post['id'], $post['updatedTime'], $post['link']);
        $this->setPostSchedule($messages['childrenCount'], $messages['updatedTime'], $messages['cursors'], $messages['gotAllComments'], $post);
        if ($entry_count > 0) {
            return $messages['data'];
        } else {
            return [];
        }
    }

    protected function populatePost($entry)
    {
        $post = array(
            'id' => $entry['id'],
            'createdTime' => strtotime($entry['created_time']),
            'updatedTime' => strtotime($entry['updated_time']),
            'type' => $entry['type'],
            'link' => isset($entry['link']) ? $entry['link'] : '',
        );
        $post['childrenCount'] = isset($entry['comments']['summary']['total_count']) ? $entry['comments']['summary']['total_count'] : 0;

        switch ($entry['type']) {
            case 'conversation':
                $post['cursors'] = array();
                if (isset($entry['messages']['paging']['previous'])) {
                    parse_str(parse_url($entry['messages']['paging']['previous'], PHP_URL_QUERY), $query);
                    $post['cursors']['since'] = $query['since'];
                }
                break;

            default:
                $post['commentsCursors'] = isset($entry['comments']['paging']['cursors']) ? $entry['comments']['paging']['cursors'] : array();
                break;
        }

        return $post;
    }

    protected function addPostsToEntries($posts, $entries)
    {
        foreach ($posts as $key => $post) {
            if (isset($post['comments']['data']) && count($post['comments']['data']) > 0) {
                //if there are comments then save them
                $comments = $this->processComments($post['comments'], $post['id'], $post['type'], strtotime($post['created_time']), strtotime($post['updated_time']));
                $entries = array_merge($comments['data'], $entries);
                unset($posts[$key]['comments']['data']);
            }

            if (isset($post['messages']['data']) && count($post['messages']['data']) > 0) {
                //save private messages
                $messages = $this->processPrivateMessages($post['messages'], $post['id'], strtotime($post['updated_time']), $post['link']);
                $entries = array_merge($messages['data'], $entries);
                unset($posts[$key]['messages']['data']);
            }
        }
        $entries = array_merge($posts, $entries);

        return $entries;
    }

    protected function processComments($comments, $parent_id, $parent_type, $parent_created_time, $parent_updated_time)
    {
        if (isset($comments['data'])) {
            foreach ($comments['data'] as $key => $value) {
                $comments['data'][$key]['is_comment_reply'] = false;
                if (isset($value['parent']['id'])) {
                    $comments['data'][$key]['main_parent_id'] = $parent_id;
                    $comments['data'][$key]['parent_id'] = $value['parent']['id'];
                    $comments['data'][$key]['is_comment_reply'] = true;
                } else {
                    $comments['data'][$key]['parent_id'] = $parent_id;
                }
                $comments['data'][$key]['type'] = $parent_type.' COMMENT';
                $comments['data'][$key]['parent_type'] = $parent_type;
                $comments['data'][$key]['last_updated'] = null;
                if (strtotime($value['created_time']) > $parent_updated_time) {
                    $parent_updated_time = strtotime($value['created_time']);
                    $this->logAction('NEW_COMMENT', 'INTERNAL', 'Parent updated_time updated to latest comment: '.date('Y-m-d h:i:s', $parent_updated_time), self::DEBUG_MSG, 0);
                }
            }
        } else {
            $comments['data'] = [];
        }

        $comments_cursors = isset($comments['paging']['cursors']) ? $comments['paging']['cursors'] : null;
        //schedule proc NEXT_RUN
        //if there is a next page, then keep the run type
        $got_all_comments = true;
        if (isset($comments['paging']['next'])) {
            $got_all_comments = false;
        }
        $children_count = isset($comments['summary']['total_count']) ? $comments['summary']['total_count'] : null;
        $comments_count = (isset($comments['data'])) ? count($comments['data']) : 0;
        // $this->logAction('PROCESS_COMMENTS', 'INTERNAL', 'Processed comments for post: '.$parent_id.' ('.$children_count.' total)', self::DEBUG_MSG, $comments_count, $parent_id);

        // convert to unix time
        $parent_updated_time = (!is_int($parent_updated_time)) ? strtotime($parent_updated_time) : $parent_updated_time;

        $comments = [
            'data' => $comments['data'],
            'cursors' => $comments_cursors,
            'childrenCount' => $children_count,
            'gotAllComments' => $got_all_comments,
            'updatedTime' => $parent_updated_time,
        ];

        return $comments;
    }

    private function processPrivateMessages($messages, $parent_id, $parent_updated_time, $parent_link)
    {
        if (isset($messages['data']) && count($messages['data']) > 0) {
            foreach ($messages['data'] as $key => $value) {
                $messages['data'][$key]['parent_id'] = $parent_id;
                $messages['data'][$key]['type'] = 'private message';
                $messages['data'][$key]['link'] = $parent_link;
            }
        } else {
            $messages['data'] = [];
        }

        //schedule proc NEXT_RUN
        //if there is a next page, then keep the run type
        $got_all_comments = true;
        $cursors = null;

        if (isset($messages['paging']['previous'])) {
            parse_str(parse_url($messages['paging']['previous'], PHP_URL_QUERY), $query);
            $cursors['since'] = $query['since'];
        }
        $this->logAction('PROCESS_ENTRIES', 'INTERNAL', 'Process private messages for: '.$parent_id.'', self::DEBUG_MSG, count($messages['data']));

        //convert to unix time
        $parent_updated_time = (!is_int($parent_updated_time)) ? strtotime($parent_updated_time) : $parent_updated_time;

        return $messages = array(
            'data' => $messages['data'],
            'cursors' => $cursors,
            'updatedTime' => $parent_updated_time,
            'gotAllComments' => $got_all_comments,
            'childrenCount' => 0,
        );
    }

    /**
     * Populate the FeedEntryData model form the Entries.
     *
     * @param $entry
     * @param $feed_model
     * @param $run_type
     */
    public function populateFeedModel($entry, $feed_model, $run_type)
    {
        //Set Entry Type
        $feed_model->setEntryType($entry['type']);
        //Set various ID codes
        $feed_model->setIdCode($entry['id']);
        $feed_model->appendExtData('page_id', $this->streamModel->getStreamCode());
        $feed_model->setParentIdCode(null);
        $feed_model->setEntryText('');
        $feed_model->setAuthorName('');
        $feed_model->setAuthorCode('');
        $feed_model->setAuthorUrl('');
        $feed_model->setAuthorImageUrl('');
        $entry_data = [];

        $arr = explode('_', $entry['id']);
        $is_comment = (strpos($feed_model->getEntryType(), 'COMMENT') === false) ? false : true;
        if ($is_comment) {
            $feed_model->setParentIdCode($entry['parent_id']);
            if (isset($entry['attachment']['media']['image']['src'])) {
                $entry_data['pictures'][] = $entry['attachment']['media']['image']['src'];
            }

            $arr_parent = explode('_', $entry['parent_id']);
            $comment_id = (isset($arr[1])) ? $arr[1] : $arr[0];
            if ($entry['is_comment_reply']) {
                $arr_main_parent = explode('_', $entry['main_parent_id']);
                $post_id = (isset($arr_main_parent[1])) ? $arr_main_parent[1] : $arr_main_parent[0];
                $main_comment_id = (isset($arr_parent[1])) ? $arr_parent[1] : $arr_parent[0];
                $str = '?comment_id='.$main_comment_id.'&reply_comment_id='.$comment_id;
            } else {
                $post_id = (isset($arr_parent[1])) ? $arr_parent[1] : $arr_parent[0];
                $str = '?comment_id='.$comment_id;
            }

            $feed_model->appendExtData('post_id', $post_id);
            $feed_model->appendExtData('comment_id', $comment_id);
            $feed_model->appendExtDataRepliedTo();
            $entry_url = $this->baseFbUrl.$this->streamModel->getStreamCode().'/posts/'.$post_id;
            $entry_url = $entry_url.$str;
        } elseif ($entry['type'] == 'conversation') {
            $entry_url = $this->baseFbUrl.trim($entry['link'], '/');
            $text = 'Conversation with ';
            foreach ($entry['participants']['data'] as $participant) {
                if ($participant['id'] != $this->streamModel->getStreamCode()) {
                    $text .= ' '.$participant['name'].',';
                }
            }
            $feed_model->setEntryText(trim($text, ','));
            $feed_model->setAuthorName('No Name');
            $feed_model->setAuthorCode('NoID');
        } elseif ($entry['type'] == 'private message') {
            $entry_url = $this->baseFbUrl.trim($entry['link'], '/'); //this is the link to the conversation
            $feed_model->setParentIdCode($entry['parent_id']);
            if (isset($entry['attachments']['data'][0]['image_data']['url'])) {
                $entry_data['pictures'][] = $entry['attachments']['data'][0]['image_data']['url'];
            }
        } elseif ($entry['type'] == 'rating') {
            $entry_url = $this->baseFbUrl.$entry['reviewer']['id'].'/posts/'.$entry['id'];
            if (isset($entry['rating'])) {
                $feed_model->appendExtData('rating', $entry['rating']);
            }
        } else {
            $post_id = (isset($arr[1])) ? $arr[1] : $arr[0];
            $entry_url = $this->baseFbUrl.$this->streamModel->getStreamCode().'/posts/'.$post_id;
            $feed_model->appendExtData('post_id', $post_id);
            if (isset($entry['picture'])) {
                $entry_data['pictures'][] = $entry['picture'];
            }
        }
        $feed_model->setEntryUrl($entry_url);
        $feed_model->setEntryText(' ');
        $feed_model->setAuthorName(' ');
        $feed_model->setAuthorCode(' ');
        $feed_model->setAuthorUrl(' ');
        $feed_model->setAuthorImageUrl(' ');
        $feed_model->setLastUpdate('');
        foreach ($entry as $key => $val) {
            switch ($key) {
                case 'created_time':
                    $feed_model->setEntryTime($val);
                    break;
                case 'reviewer':
                case 'from':
                    $name = '';
                    if (isset($val['name'])) {
                        $name = $val['name'];
                    } elseif (isset($val['category'])) {
                        $name = $val['category'];
                    }
                    $feed_model->setAuthorName($name);
                    $feed_model->setAuthorCode($val['id']);
                    $feed_model->setAuthorUrl('https://www.facebook.com/'.$val['id']);
                    $feed_model->setAuthorImageUrl('http://graph.facebook.com/'.$val['id'].'/picture');
                    break;
                case 'message':
                case 'review_text':
                    $feed_model->setEntryText($val);
                    break;
                case 'updated_time':
                    $feed_model->setLastUpdate($val);
                    break;
                case 'link':
                    if ($feed_model->getEntryText() == '') {
                        $feed_model->setEntryText($val);
                    }
                    break;
                case 'shares':
                    if (isset($val['count'])) {
                        $feed_model->appendExtData('shares_count', $val['count']);
                    }
                    break;
                case 'likes':
                    if (isset($val['summary']['total_count'])) {
                        $feed_model->appendExtData('likes_count', $val['summary']['total_count']);
                    }
                    break;
                case 'is_hidden':
                    $entry_data['is_hidden'] = $val;
                    break;
                case 'can_hide':
                    $entry_data['can_hide'] = $val;
                    break;
                case 'can_remove':
                    $entry_data['can_remove'] = $val;
                    break;
                case 'can_comment':
                    $entry_data['can_comment'] = $val;
                    break;
                case 'scheduled_publish_time':
                        $feed_model->appendExtData('scheduled_publish_time', $val);
                        $feed_model->appendExtData('initial_created_time', strtotime($entry['created_time']));
                        break;
                default:
                    break;
            }
        }
        $feed_model->setEntryData($entry_data);

        parent::populateFeedModel($entry, $feed_model, $run_type);
    }

    protected function setProcessorState($run_type)
    {
        $since = $until = '';
        switch ($run_type) {
            case 'NEW':
                $this->processorData['NEXT_RUN']['NEW'] = $this->getNextRunTime('NEW');
                $this->processorData['CURSORS']['NEW'] = $this->cursors;
                foreach ($this->processorData['REQUESTS'] as $key => $value) {
                    if (is_numeric($this->cursors[$key]['SINCE'])) {
                        $since .= ' SINCE '.$key.': '.date('Y-m-d h:i:s', $this->cursors[$key]['SINCE']);
                    }
                    $this->processorData['CURSORS']['NEW'][$key]['UNTIL'] = null;
                }
                break;
            case 'MED':
                $this->processorData['NEXT_RUN']['MED'] = $this->getNextRunTime('MED');
                break;
            case 'OLD':
                $this->processorData['NEXT_RUN']['OLD'] = $this->getNextRunTime('OLD');
                $this->processorData['CURSORS']['OLD'] = $this->cursors;
                foreach ($this->processorData['REQUESTS'] as $key => $value) {
                    if ((int) $this->cursors[$key]['UNTIL'] > 0) {
                        $until .= ' UNTIL '.$key.': '.date('Y-m-d h:i:s', $this->cursors[$key]['UNTIL']);
                    }
                    $this->processorData['CURSORS']['OLD'][$key]['SINCE'] = null;
                }
                break;
            default:
                break;
        }
        $this->logAction('SET_PROC_STATE', 'BASE_PROC', $since.' '.$until, self::DEBUG_MSG);
    }

    protected function getNextRunTime($type)
    {
        //Application request limit reached
        if (isset($this->lastApiError) && in_array($this->lastApiError->getCode(), [4, 17])) {
            $primaryModel = new \PrimaryModel($this->primaryConnection);
            $now = $primaryModel->readNow();
            $run_time = $now + 60 * 30; //30 minutes

            if (!$this->alertEmailSent) {
                $this->logAction('GET_NEXT_RUN_TIME', 'INTERNAL', 'Facebook pulling rate limit exceeded. Pulling will resume after about 30 minutes.', self::LOG_MSG, 1);
                $this->sendAlertEmail('SP Warning', 'Facebook pulling rate limit exceeded. Pulling will resume after about 30 minutes.');
                $this->alertEmailSent = true;
            }
        } else {
            $run_time = parent::getNextRunTime($type);
        }

        return $run_time;
    }

    public function handleErrors(\Exception $exception, $sendProcessorToError = true)
    {

        // parent::handleErrors($exception, $sendProcessorToError && $this->lastApiError->getCode() != 100);

        // Handling by error code
        switch ($exception->getCode()) {
            case 4:
                //app request limit reached
            case 17:
                //user request limit reached
                // todo handle errors
                $this->logAction('HANDLE_ERRORS', 'FB_PROC', 'Api Limit error. Setting maxCalls = 0.');
                $this->getApiThrottleProvider()->setMaxCalls(0);
                $this->getApiThrottleProvider()->reachedLimit();
                break;
            case 100:
                //Error finding the requested story
                $this->logAction('HANDLE_ERRORS', 'FB_PROC', 'Error finding the requested story');
                // $this->getApiThrottleProvider()->setMaxCalls(0);
                // $this->getApiThrottleProvider()->reachedLimit();
                $sendProcessorToError = false;
                break;
            case 102:
                //invalid access token
                $this->logAction('HANDLE_ERRORS', 'FB_PROC', 'Access token has expired, been revoked, or is otherwise invalid');

                // If moderator has invalid token - clear it to give the ability for refreshing it
                if (isset($this->facebookConn->postAsModerator) && $this->facebookConn->postAsModerator) {
                    $user_data = $this->facebookConn->userModel->getData();
                    $user_data['facebook'][$this->facebookConn->appId]['access_token'] = '';
                    $user_data['facebook'][$this->facebookConn->appId]['scopes'] = '';
                    $this->facebookConn->userModel->setData($user_data);
                    $this->facebookConn->userModel->Update();
                    throw new \InvalidUserTokenException('Your facebook token is invalid. Please login to facebook and respond again.', 409);
                }
                $this->getApiThrottleProvider()->setMaxCalls(0);
            case 1705: //Selected comment for deletion does not exist..
                //nothing to do here
                break;
            case 4096:
                //problems initializing FacebookRequest
                $this->getApiThrottleProvider()->setMaxCalls(0);
                break;
            default:
                $this->logAction('HANDLE_ERRORS', 'FB_PROC', 'Uncaught error. '.
                    'Message: '.$exception->getMessage().'. '.
                    'Code: '.$exception->getCode());
                break;
        }

        parent::handleErrors($exception, $sendProcessorToError);
    }
    
    private function getConversationsId($messageId){
        $requestConversations = '/'.$this->streamModel->getStreamCode().'/conversations';
        $responseConversations = $this->facebookConn->apiCall(
            $requestConversations,
            1,
            'GET',
            \ApiLogModel::GET_CONVERSATIONS,
            'Get CONVERSATIONS from GraphAPI: '.$requestConversations
        );
        $time = strtotime('-1 hour');
        foreach(array_splice($responseConversations['data'], 0, 3) as $conversations){
            if(strtotime($conversations['updated_time']) > $time){
                $requestConversationsMessages = '/'.$conversations['id'].'/messages';
                $responseConversationsMessages = $this->facebookConn->apiCall(
                    $requestConversationsMessages,
                    1,
                    'GET',
                    \ApiLogModel::GET_CONVERSATION_MESSAGES,
                    'Get MESSAGES by CONVERSATION ID from GraphAPI: '.$requestConversationsMessages
                );
                foreach($responseConversationsMessages['data'] as $messages){
                   if($messages['id'] == $messageId){
                        return $conversations['id'];
                    }
                }
            }
        }
        return null;
    }
}
