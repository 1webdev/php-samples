<?php
require_once('PrimaryConnection.php');
require_once('SocialConnection.php');
require_once('FeedConnection.php');
require_once('StreamModel.php');
require_once('ProcessorModel.php');
require_once('ProcessorLogModel.php');
require_once('FeedEntryModel.php');
require_once('PostSet.php');
require_once('BaseProcessor.php');
require_once('SystemVariableModel.php');
require_once('PreModStageModel.php');
require_once('CoverageScheduleModel.php');
require_once('NotificationModel.php');
require_once('UserModel.php');
require_once('ClientModel.php');
require_once('SocialIdentityRelationModel.php');
require_once 'processors/traits/Moderation.php';

class BasePullProcessor extends \BaseProcessor
{
    use \Moderation;

    const SINCE_NEW = 604800; // 7  days
    const SINCE_MED = 2592000;// 30 days
    const MAX_POSTS = 60;
    const POST_SET_SIZE = 40;
    const BATCH_SIZE_POSTS = 30;
    const BATCH_SIZE_COMMENTS = 500;
    const BATCH_SIZE_FEED_COMMENTS = 100;
    const MAX_ENTRIES = 1000;
    const NEXT_RUN_CONTINUE = 20; //20 seconds

    protected $cursors;
    protected $newPostSet;
    protected $medPostSet;
    protected $oldPostSet;
    protected $sla;
    protected $postSetMaxTime;
    protected $hasPreMod;
    protected $clientModel = null;
    protected $coverageSchedule = null;

    protected function putStreamUpdate()
    {
        //virtual
    }

    protected function acceptAll($pending)
    {
        //virtual
    }
    protected function blockUser($action)
    {
        throw new Exception('Cannot '.$action.' user. Unsupported feature for this social network.', 400);
    }

    protected function vote($action)
    {
        throw new Exception('Cannot '.$action.'. Unsupported feature for this social network.', 400);
    }

    protected function followUser($action)
    {
        throw new Exception('Cannot '.$action.' user. Unsupported feature for this social network.', 400);
    }

    protected function processPost($entry)
    {
        //virtual
    }

    protected function getSourceEntries($id, $type)
    {
        //virtual
        throw new Exception('This is a virtual method. Please Implement getSourceEntries($id, $type)', 403);
    }

    protected function getSourceComments($post)
    {
        //virtual
    }

    protected function hasPermission($permission)
    {
        //virtual
    }

    protected function postPullProcessing($run_type)
    {
        // virtual
    }

    protected function processChildren($entry){
        // virtual
    }

    /**
     * Process the feed data according to the runType
     * @param $id_code
     * @param $res
     * @return bool
     * @throws Exception
     */
    public function run($id_code, $res, $req)
    {
        $this->logAction("RUN", "BASE_PROC", "Run: run_type=" . $this->runType . ". id_code=" . $id_code);

        if ($this->processorModel->getStatusCode() != 'ERROR') {
            $this->initProcessorData();
            // $this->setMaxCalls($this->runType, $this->calls_per_sec, $this->calls_time_range);
            // $this->logAction("SET_MAX_CALLS", "BASE_PROC", "Initialized maxCalls: " . json_encode($this->getApiThrottleProvider()->getMaxCalls()), self::DEBUG_MSG);
            $this->setCursors($this->runType, $id_code);
            $successCode = 203;
            // Get new or updated entries
            switch ($this->runType) {
                case "NEW":
                    $entries = $this->getNewEntries();
                    $successCode = $this->persistEntries($entries, $this->runType);
                    $successCode = $this->reschedule($successCode, $this->runType);
                    break;
                case "MED":
                    $entries = $this->getMedEntries();
                    $successCode = $this->persistEntries($entries, $this->runType);
                    $successCode = $this->reschedule($successCode, $this->runType);
                    break;
                case "OLD":
                    $entries = $this->getOldEntries();
                    $successCode = $this->persistEntries($entries, $this->runType);
                    $successCode = $this->reschedule($successCode, $this->runType);
                    break;
                case "COMMENTS":
                    $id_code_type = $req->get('id_type');
                    $category = $req->get('categ');
                    $category_class = $req->get('class');

                    $entries = $this->getCommentEntries($id_code, $id_code_type);
                    $successCode = $this->persistEntries($entries, $this->runType,
                                            function ($feedModel) use ($id_code, $category, $category_class) {
                                                if ( $feedModel->getIdCode() == $id_code ) {
                                                    $feedModel->appendCategoryData($category, $category_class);
                                                }
                                            }
                                    );
                    $successCode = $this->reschedule($successCode, $this->runType);
                    break;
                case "APPROVED":
                case "SPAM":
                case "DISABLED":
                case "DELETE":
                case "HIDE":
                case "PENDING":
                    $this->feedEntryModel->reset();
                    $this->feedEntryModel->setIdCode($id_code);
                    if ($this->feedEntryModel->read()) {
                        $this->putStreamUpdate();
                        if (in_array($this->feedEntryModel->getStatusCode(), ['ERROR', 'UNSUPP'])) {
                            $successCode = 404;
                        }
                    } else {
                        $this->processorModel->setLastError('Cannot find this item in the database.');
                        $successCode = 404;
                    }
                    break;
                case "BLOCK":
                case "UNBLOCK":
                    $social_conn = new SocialConnection($this->primaryConnection);
                    $social_relation_model = new SocialIdentityRelationModel($social_conn, $this->streamModel->getSourceCode());
                    $social_relation_model->reset();

                    $this->feedEntryModel->reset();
                    $this->feedEntryModel->setIdCode($id_code);
                    if ($this->feedEntryModel->read()) {
                        //block user
                        try {
                            $this->blockUser($this->runType);
                            $social_relation_model->setFrom($this->streamModel->getOAuthAccCode());
                            $social_relation_model->setTo($this->feedEntryModel->getAuthorCode());
                            $social_relation_model->setRelation('BLOCK');
                            if($this->runType == 'BLOCK'){
                                $social_relation_model->Insert();
                            }else{
                                $social_relation_model->Delete();
                            }
                            $this->feedEntryModel->appendExtData('block_user', $this->runType.'ED');
                        } catch(Exception $e) {
                            //save _ext blocked user
                            $this->feedEntryModel->appendExtData('block_user_error', $e->getMessage());
                            $this->processorModel->setLastError($e->getMessage());
                            $successCode = 400;
                        }
                        $entry_data = $this->feedEntryModel->getEntryData();
                        $entry_data['block_user'] = ($this->runType == 'BLOCK') ? 'TRUE' : 'FALSE';
                        $this->feedEntryModel->setEntryData($entry_data);
                        $split_dt = date('Y-m-d H:i:s', strtotime('100 days ago'));
                        $this->feedEntryModel->insertData($split_dt); // this in fact will update
                    } else {
                        $this->processorModel->setLastError('Cannot find this item in the database.');
                        $successCode = 404;
                    }
                    break;
                case "UPVOTE":
                case "DOWNVOTE":
                case "REMOVE_VOTE":
                    $this->feedEntryModel->reset();
                    $this->feedEntryModel->setIdCode($id_code);
                    if ($this->feedEntryModel->read()) {
                        //upvote feed
                        try {
                            $this->vote($this->runType);
                            $this->feedEntryModel->appendExtData('upvote', $this->runType.'D');

                            if($this->runType == "UPVOTE") {
                                $this->feedEntryModel->appendCategoryData("INTERNAL", "UPVOTE");
                            }

                            $streamId = $this->streamModel->getId();

                        } catch(Exception $e) {
                            //save _ext upvote feed
                            $this->feedEntryModel->appendExtData('upvote_error', $e->getMessage());
                            $this->processorModel->setLastError($e->getMessage());
                            $successCode = 400;
                        }
                        $entry_data = $this->feedEntryModel->getEntryData();
                        if(!is_array($entry_data)){
                            $entry_data = [];
                        }
                        $entry_data['upvote'] = ($this->runType == 'UPVOTE') ? 'TRUE' : 'FALSE';
                        $this->feedEntryModel->setEntryData($entry_data);
                        $split_dt = date('Y-m-d H:i:s', strtotime('100 days ago'));
                        $this->feedEntryModel->insertData($split_dt); // this in fact will update
                    } else {
                        $this->processorModel->setLastError('Cannot find this item in the database.');
                        $successCode = 404;
                    }
                    break;
                case "FOLLOW":
                case "UNFOLLOW":
                    $social_conn = new SocialConnection($this->primaryConnection);
                    $social_relation_model = new SocialIdentityRelationModel($social_conn, $this->streamModel->getSourceCode());
                    $social_relation_model->reset();

                    $this->feedEntryModel->reset();
                    $this->feedEntryModel->setIdCode($id_code);
                    if ($this->feedEntryModel->read()) {
                        //Follow/unfollow user
                        try {
                            $this->followUser($this->runType);
                            $social_relation_model->setFrom($this->streamModel->getOAuthAccCode());
                            $social_relation_model->setTo($this->feedEntryModel->getAuthorCode());
                            $social_relation_model->setRelation('FOLLOW');
                            if($this->runType == 'FOLLOW'){
                                $social_relation_model->Insert();
                            }else{
                                $social_relation_model->Delete();
                            }
                            $this->feedEntryModel->appendExtData('follow_user', $this->runType.'ED');
                        } catch(Exception $e) {
                            //save _ext blocked user
                            $this->feedEntryModel->appendExtData('follow_user_error', $e->getMessage());
                            $this->processorModel->setLastError($e->getMessage());
                            $successCode = 400;
                        }
                        $entry_data = $this->feedEntryModel->getEntryData();
                        $entry_data['follow_user'] = ($this->runType == 'FOLLOW') ? 'TRUE' : 'FALSE';
                        $this->feedEntryModel->setEntryData($entry_data);
                        $split_dt = date('Y-m-d H:i:s', strtotime('100 days ago'));
                        $this->feedEntryModel->insertData($split_dt); // this in fact will update
                    } else {
                        $this->processorModel->setLastError('Cannot find this item in the database.');
                        $successCode = 404;
                    }
                    break;
                case 'ACCEPT_ALL':
                    $pending = $this->feedEntryModel->readPending();
                    $this->feedEntryModel->lockPending();
                    $this->acceptAll($pending);
                    break;
                default:
                    // Unknown Run Type
                    $this->processorModel->setLastError('Unsupported processor run_type (' . $this->runType . ')');
                    $successCode = 501;
            }
        } else {
            $successCode = 404;
        }

        switch ($successCode) {
            case 400:
            case 404:
                $this->setResult($res, $successCode, 'error', $this->handleErrorMessages($this->processorModel->getLastError()));
                break;
            case 500:
                $this->setResult($res, $successCode, 'error', 'Error code:500 Details:' . $this->handleErrorMessages($this->processorModel->getLastError()));
                break;
            case 200:
            case 203:
            case 204:
                // this will update statuses in destination dbs
                $this->postPullProcessing($this->runType);
                $this->setResult($res, $successCode, 'success', 'done');
                break;
            default:
                break;
        }
        $this->cleanup();
        return true;
    }

    /**
     * User-friendly error messages
     * @param $initialMessage
     * @return string
     */
    private function handleErrorMessages($initialMessage)
    {
        if (strpos($initialMessage, 'Unsupported get request. Please read the Graph API documentation at https://developers.facebook') !== false) {
            return 'The initial post had been removed from Facebook';
        } elseif (strpos($initialMessage, '(#200) Cannot access object_id') !== false) {
            return 'The initial post had been hidden or removed from Facebook';
        }
        return $initialMessage;
    }

    protected function initProcessorData($validData = false)
    {
        $this->postSetMaxTime = $this->invoke_time - self::SINCE_MED;

        if (array_key_exists('VERSION', $this->processorData)) {
            if ($this->processorData['VERSION'] == 10) {
                $validData = true;
            }
        }

        if (!$validData) {
            $this->logAction("INIT_PROC_DATA", "INTERNAL", "Valid proc_data not found - Initializing.", self::DEBUG_MSG);
            $newProcData = array(
                'VERSION' => 10,
                'INVOKE_TIME' => $this->invoke_time,
                'NEW_POST_SET' => [],
                'MED_POST_SET' => [],
                'OLD_POST_SET' => [],
                'NEXT_RUN' =>
                    array(
                        'NEW' => $this->invoke_time,
                        'OLD' => $this->getNextRunTime('OLD')
                    ),
                'CURSORS' => array(
                    'NEW' => array('SINCE' => null, 'UNTIL' => time()),
                    'OLD' => array('SINCE' => null, 'UNTIL' => time()),
                ),
                'REMAINING_CALLS' => 600
            );
            $this->processorData = $newProcData;
            $this->logAction("INIT_PROC_DATA", "INTERNAL", "Initialized processorData: " . json_encode($this->processorData), self::DEBUG_MSG);
            $validData = true;
        }
        if(!isset($this->processorData['OLD_POST_SET'])){
            $this->processorData['OLD_POST_SET'] = [];
        }
        $this->newPostSet = new PostSet($this->processorData['NEW_POST_SET'], self::POST_SET_SIZE, $this->postSetMaxTime);
        $removed_posts = $this->newPostSet->checkList();
        $this->medPostSet = new PostSet($this->processorData['MED_POST_SET'], self::POST_SET_SIZE, $this->postSetMaxTime);
        $this->medPostSet->checkList();
        $this->medPostSet->addPosts($removed_posts);
        // old has no time limit
        $this->oldPostSet = new PostSet($this->processorData['OLD_POST_SET'], 1000, 0);
        $this->sla = $this->streamModel->getSla();
        $this->hasPreMod = $this->getHasPreMod();

        // Get client and coverage schedule info
        $this->clientModel = new ClientModel($this->primaryConnection);
        $this->clientModel->setId($this->streamModel->getClientId());
        $this->clientModel->Read();

        return $validData;
    }

    public function reschedule($success_code, $run_type)
    {
        $this->processorData['INVOKE_TIME'] = $this->invoke_time;
        $this->processorData['REMAINING_CALLS'] = $this->getApiThrottleProvider()->getReservedCalls() + $this->getApiThrottleProvider()->getMaxCalls();
        $this->processorData['NEW_POST_SET'] = is_object($this->newPostSet) ? $this->newPostSet->getPostSet() : [];
        $this->processorData['MED_POST_SET'] = is_object($this->medPostSet) ? $this->medPostSet->getPostSet() : [];
        $this->processorData['OLD_POST_SET'] = is_object($this->oldPostSet) ? $this->oldPostSet->getPostSet() : [];
        $this->setProcessorState($run_type);

        return parent::reschedule($success_code, $run_type);
    }

    protected function getNewEntries()
    {
        $entries = [];
        $this->logAction("GET_ENTRIES", "BASE_PROC", "Getting NEW entries.", self::DEBUG_MSG);

        $stream_code = $this->streamModel->getStreamCode();
        $post_entries = $this->getSourceEntries($stream_code, null);

        //add posts to posts sets
        foreach ($post_entries as $new_post) {
            $post = $this->populatePost($new_post);

            if ($post) {
                $this->addPostToPostSets($post);
            }
        }
        $entries = $this->addPostsToEntries($post_entries, $entries);

        $this->logAction("GET_ENTRIES", "BASE_PROC", "Retrieved " . count($entries) . " NEW Posts.", self::DEBUG_MSG);
        $postSet = $this->newPostSet->getPostSet();

        $entries = $this->getCommentsForPostSet($postSet, $entries);

        // if there are posts in medPostSet and there is no instance scheduled for MED
        if (!isset($this->processorData['NEXT_RUN']['MED']) && count($this->medPostSet) > 0) {
            $this->processorData['NEXT_RUN']['MED'] = $this->getNextRunTime('MED');
        }
        return $entries;
    }

    protected function getMedEntries()
    {
        $entries = [];
        $this->logAction("GET_ENTRIES", "BASE_PROC", "Getting MED entries.", self::DEBUG_MSG);
        $postSet = $this->medPostSet->getPostSet();

        $entries = $this->getCommentsForPostSet($postSet, $entries);

        return $entries;
    }

    protected function getOldEntries()
    {
        $entries = [];

//        temporarily disabled
        $this->logAction( 'GET_ENTRIES', 'BASE_PROC', 'Pulling OLD entries is disabled', self::LOG_MSG, 0);
        return [];


//      if there are entries in oldPostsSet
        $postSet = $this->oldPostSet->getPostSet();
        if(count($postSet) > 0){
            $entries = $this->getCommentsForPostSet($postSet, $entries);
        }else{
//          get more entries
            $stream_code = $this->streamModel->getStreamCode();
            $posts = $this->getSourceEntries($stream_code, null);

//          save them in oldPostsSet
            foreach($posts as $new_post){
                $comments = $this->processChildren($new_post);
                $entries = $this->addCommentsToEntries($comments['data'], $entries);
                $post = $this->populatePost($new_post);
                if ($post) {
                    $this->setPostSchedule($comments['childrenCount'], $comments['updatedTime'], $comments['cursors'], $comments['gotAllComments'], $post);
                }
            }
            $entries = $this->addPostsToEntries($posts, $entries);

        }
        return $entries;
    }

    protected function getCommentEntries($id_code, $id_code_type = null)
    {
        $entries = [];
        $limit = self::BATCH_SIZE_COMMENTS;
        $total_comments_limit = self::BATCH_SIZE_COMMENTS;
        //search for this post_id in new/med post sets and get only new comments
        if (isset($this->processorData['CURSORS'][$id_code])) {
            $post = $this->processorData['CURSORS'][$id_code];
        } else {
            $post = $this->getPost($id_code);
            if ($post) {
                $entries = $this->addPostsToEntries([$post], $entries);
                $post = $this->populatePost($post);
            }
        }

        if ($post) {
            $comments = $this->getSourceComments($post, $limit, $total_comments_limit);
            $entries = $this->addCommentsToEntries($comments, $entries);
            $this->logAction( 'GET_ENTRIES', 'BASE_PROC', 'Getting comments', self::LOG_MSG, count($comments), $post['id']);
        }
        return $entries;
    }

    private function getCommentsForPostSet($post_set, $entries){
        shuffle($post_set);
        foreach ($post_set as $post) {
            $comments = $this->getSourceComments($post);
            $entries = $this->addCommentsToEntries($comments, $entries);
            $this->logAction("GET_ENTRIES", "BASE_PROC", "Retrieved " . count($comments) . " comments for NEW post ID ." . $post['id'], self::DEBUG_MSG);

            if(count($entries) >= self::MAX_ENTRIES){ break; }

            if ($this->getApiThrottleProvider()->getMaxCalls() <= 0) {
                $this->newPostSet->delete($post['id']);
                $this->medPostSet->add($post);
                break;
            }
        }

        return $entries;
    }

    protected function setPostSchedule($children_count, $updated_time, $comments_cursors, $got_all_comments, $post)
    {
        if (!is_numeric($updated_time)) {
            $updated_time = strtotime($updated_time);
        }
        $this->logAction('SET_SCHED', 'BASE_PROC', 'Set schedule post id: '.$post['id'].', childrenCount: '.$children_count.', updatedTime: '. date('Y-m-d h:i:s', $updated_time) .', got_all_comments: '. (int)$got_all_comments, self::DEBUG_MSG, 0, $post['id']);
        $post['updatedTime'] = ($updated_time > $post['updatedTime']) ? $updated_time : $post['updatedTime'];
        $post['childrenCount'] = ($children_count !== null) ? $children_count : $post['childrenCount'];
        if ($comments_cursors !== null) {
            $post['commentsCursors'] = $comments_cursors;
        }

        if($this->runType != 'OLD'){
            if (!$got_all_comments) {
                $this->removeFromPostSets($post['id']);
                $this->processorData['CURSORS'][$post['id']] = $post;
                $this->processorData['NEXT_RUN'][$post['id']] = $this->getNextRunTime('COMMENTS_NEXT_PAGE');
            } else if ($updated_time > $this->postSetMaxTime) {
                $this->addPostToPostSets($post);
            }
        }else{
            if (!$got_all_comments) {
                $this->oldPostSet->add($post);
            }else{
                $this->oldPostSet->delete($post['id']);
            }
        }
    }

    protected function addPostToPostSets($post)
    {
        $this->removeFromPostSets($post['id']);
        $this->newPostSet->add($post);
        $removed_posts = $this->newPostSet->checkDepth();
        $this->medPostSet->addPosts($removed_posts);
    }

    private function removeFromPostSets($id)
    {
        $this->newPostSet->delete($id);
        $this->medPostSet->delete($id);
    }

    /**
     * Get an array containing the "SINCE" and "UNTIL" times based on the runType
     * Returns 'before'/'after' cursors
     * @param $run_type
     * @param null $parent_id_code
     * @return array
     */
    protected function setCursors($run_type, $parent_id_code = null)
    {
        if ($parent_id_code != null) {
            //returns cursors for a specific post
            $cursors = (isset($this->processorData['CURSORS'][$parent_id_code])) ? $this->processorData['CURSORS'][$parent_id_code] : null;
        } else {
            //returns cursors for the feed
            $cursors = (isset($this->processorData['CURSORS'][$run_type])) ? $this->processorData['CURSORS'][$run_type] : null;
        }

        if ($cursors == null) {
            $cursors = array('SINCE' => null, 'UNTIL' => null);
        }
        // foreach ($cursors as $key => $value) {
        //     $this->logAction("SET_CURSORS", "BASE_PROC", "Init cursors. Since: ".date("Y-m-d H:i:s", $value['SINCE']).'. Until: '.date("Y-m-d H:i:s", $value['UNTIL']), self::DEBUG_MSG);
        // }
        $this->cursors = $cursors;
    }

    protected function addPostsToEntries($posts, $entries)
    {
        $entries = array_merge($posts, $entries);
        return $entries;
    }

    protected function addCommentsToEntries($comments, $entries)
    {
        $entries = array_merge($comments, $entries);
        return $entries;
    }

    public function persistEntries($entries, $run_type, $callbackForPreModerate = false)
    {
        // if there was a session open, close it now before saving stuff into db
        if (session_id() != '') {
            session_write_close();
        }
        $split_dt = date('Y-m-d H:i:s', strtotime('100 days ago'));

        $storedCount = $entryCount = 0;
        $ret_val = 200;

        $this->logAction("SAVE_ENTRIES", "DB_CALL", "Preparing " . count($entries) . " entries for persistence to the  database.", self::LOG_MSG);
        foreach ($entries as $entry) {
            $entryCount++;
            $this->feedEntryModel->reset();
            $this->populateFeedModel($entry, $this->feedEntryModel, $run_type);
            $this->preModerate($this->feedEntryModel, $callbackForPreModerate);
            $result = $this->feedEntryModel->insertData($split_dt);

            // If item was inserted
            if ($result == 1) {
                $storedCount++;
            }elseif($result == 2){
                // entry is EDITED
                // check if it's Brand Entry
                $this->autoModerateResponse();
                if($this->feedEntryModel->getStatusCode() == 'MOD'){
                    $this->feedEntryModel->updateStatus();
                }
            }
            if (($entryCount % 100) == 0) {
                $this->logAction("SAVE_ENTRIES", "DB_CALL", "Saved/updated/ignored " . $entryCount . " entries.", self::DEBUG_MSG);
            }
        }
        $this->logAction("SAVE_ENTRIES", "DB_CALL", "Persisted " . $storedCount . " entries to the 'feed' database.", self::LOG_MSG, $storedCount );

        return $ret_val;
    }

    protected function populateFeedModel($entry, $feed_model, $run_type)
    {
        $now = $this->processorModel->readNow();
        if (strtotime($feed_model->getEntryTime()) < $now - self::SINCE_MED) {
            $feed_model->setStatusCode("OLD");
        } else {
            if($this->hasPreMod){
                $feed_model->setStatusCode("PRE_MOD_00");
            }else{
                $feed_model->setStatusCode("NEW");
            }
        }

        // check if created time is in the future, log everything
        if(strtotime($feed_model->getEntryTime()) >= $now){
            $feed_model->appendExtData('INITIAL_ENTRY_TIME', $feed_model->getEntryTime());
            $feed_model->setEntryTime(date('Y-m-d H:i:s', $now));

            // $this->sendAlertEmail('Entry with created time in the future', 'entry: ' . json_encode($entry), [['address' => 'petre.tudor@icuc.social', 'name' => 'Petre Tudor']]);

            $this->logAction('ENTRY_TIME_ERR', 'PROCESS_ENTRIES', 'raw entry: ' . json_encode($entry) .
                ', feed entry_time: ' . $feed_model->getEntryTime() .
                ', now: ' . date('Y-m-d H:i:s', $now), self::LOG_MSG, 1, $feed_model->getIdCode());
        }

        $feed_model->appendExtData('pull_run_id', $this->invoke_time);
        // Save id_code in ext data
        $feed_model->appendExtData('Entry_Id', $feed_model->getIdCode());
    }

    protected function preModerate($feedModel, $callback)
    {
        if ( $callback ) {
            $callback($feedModel);
        }
    }

    protected function getNextRunTime($type)
    {
        $now = $this->processorModel->readNow();

        switch ($type) {
            case 'NEW':
                $time = $this->sla / 3;
                if ($time > 30 * 60) {
                    $time = 30 * 60;
                }
                break;
            case 'MED':
                $next_new = $this->getNextRunTime('NEW') - $now;
                $time = $this->sla / 1.5;
                if ($time < 3 * $next_new) {
                    $time = 3 * $next_new;
                }
                break;
            case 'OLD':
                //once an hour
                $time = 60 * 60;
                break;
            case 'COMMENTS_NEXT_PAGE':
                $time = $this->sla / 1.5;
                if ($time < 2 * 60) {
                    $time = 2 * 60;
                }

                if ($time > 5 * 60) {
                    $time = 5 * 60;
                }
                break;

            default:
                //once an hour
                $time = 60 * 60;
                break;
        }
        return $now + $time;
    }

    /**
     * Returns an array specifying the processor next run time and next run type
     * @param $last_run_type
     * @return array
     */
    protected function getProcSchedule($last_run_type)
    {
        $now = $this->processorModel->readNow();

        if($this->getApiThrottleProvider()->hasReachedLimit()){
            // we are out of api calls, so next time get the latest
            $run_type = 'NEW';
            $run_time = $this->getNextRunTime('NEW');
        } else {
            $run_time = $this->processorData['NEXT_RUN']['NEW'];
            $run_type = 'NEW';
            $priority_arr = [];
            arsort($this->processorData['NEXT_RUN']);
            foreach ($this->processorData['NEXT_RUN'] as $key => $value) {
                //disable rescheduling OLD
                if ($key == 'OLD') {
                    continue;
                }

                $run_type = $key;
                if (!in_array($run_type, array('NEW', 'MED', 'OLD'))) {
                    $run_type = 'COMMENTS';
                    $run_type .= '&id=' . $key;
                }
                if ($value < $this->invoke_time + self::NEXT_RUN_CONTINUE) {
                    $arr = explode('&', $run_type);
                    switch ($arr[0]) {
                        case 'NEW':
                            $priority = 0;
                            break;
                        case 'COMMENTS':
                            $priority = 1;
                            break;
                        case 'MED':
                            $priority = 2;
                            break;
                        case 'OLD':
                            $priority = 3;
                            break;
                    }
                    $priority_arr[$priority] = array('run_type' => $run_type, 'run_time' => $value, 'run_time_s' => date('Y-m-d H:i:s', $value));
                } else {
                    if ($value < $run_time) {
                        $run_time = $value;
                    }
                }
            }
            //if there are elements that have expired run time (< time())
            if (count($priority_arr) > 0) {
                ksort($priority_arr);

                $next = array_shift($priority_arr);
                $run_time = $next['run_time'];
                $run_type = $next['run_type'];
            }
            // Ensure runTime is a little bit in the future
            // $initial_run_time = $run_time;
            $run_time = ($run_time < ($now + self::NEXT_RUN_CONTINUE)) ? ($now + self::NEXT_RUN_CONTINUE) : $run_time;
        }

        $resArray = ['RUN_TIME' => $run_time, 'RUN_TYPE' => $run_type];
        return $resArray;
    }

    /**
     * get a certain post in case it isn't in the processor data
     * @param $post_id
     * @return bool
     */
    protected function getPost($post_id)
    {
        return FALSE;
    }

    protected function setProcessorState($run_type)
    {
        switch($run_type) {
            case 'NEW':
                $this->processorData['NEXT_RUN']['NEW'] = $this->getNextRunTime('NEW');
                $this->processorData['CURSORS']['NEW']['SINCE'] = $this->cursors['SINCE'];
                $this->processorData['CURSORS']['NEW']['UNTIL'] = NULL;
                break;
            case 'MED':
                $this->processorData['NEXT_RUN']['MED'] = $this->getNextRunTime('MED');
                break;
            case 'OLD':
                $this->processorData['NEXT_RUN']['OLD'] = $this->getNextRunTime('OLD');
                $this->processorData['CURSORS']['OLD']['SINCE'] = NULL;
                $this->processorData['CURSORS']['OLD']['UNTIL'] = $this->cursors['UNTIL'];
                break;
            default:
                break;
        }
    }

    protected function getHasPreMod()
    {
        $this->stageModel = new PreModStageModel($this->primaryConnection);
        $this->stageModel->setClientId($this->streamModel->getClientId());
        if ($this->stageModel->getStageCount() > 0 && $this->streamModel->isPreModProcessorActive()) {
            return true;
        } else {
            return false;
        }
    }



//    private function setM
}
