<?php

require_once('PrimaryConnection.php');
require_once('LogConnection.php');
require_once('FeedConnection.php');
require_once('ReportConnection.php');

require_once('ProcessorModel.php');
require_once('AutoReportModel.php');
require_once('ProcessorLogModel.php');
require_once('ApiLogModel.php');
require_once('UserLogModel.php');
require_once('StreamModel.php');
require_once('GroupModel.php');
require_once('UserModel.php');
require_once('GroupMemberModel.php');

require_once('FeedEntryModel.php');
require_once('ReportEntryModel.php');
require_once('ApiThrottleModel.php');
require_once('ApiThrottleProvider.php');
require_once('PHPMailer/class.phpmailer.php');

class BaseProcessor
{
    const PROC_ERROR    = -2;
    const API_ERROR     = -1;
    const API_SUCCESS   = 0;
    const API_LIMIT     = 2;

    public    $primaryConnection;
    protected $logConnection;
    protected $feedConnection;

    public    $processorData;
    public    $processorModel;
    protected $processorLogModel;
    protected $apiLogModel;
    public    $streamModel;
    public    $feedEntryModel;
    protected $parentEntryModel;
    protected $reportEntryModel;
    protected $logModel;
    protected $invoke_time;
    protected $lastApiError;
    protected $runType;
    private   $apiThrottleProvider;
    protected $notificationModel;
    protected $now;

    // Constants for DEBUG logging
    const DEBUG_MSG = true;
    const LOG_MSG = false;

    public function __construct($primary_conn, $processor_model, $stream_model, $run_type = '')
    {
        $this->runType = strtoupper($run_type);
        try {
            $this->primaryConnection = $primary_conn;
            $this->logConnection = new LogConnection($primary_conn);
            $this->processorModel = $processor_model;
            $this->streamModel = $stream_model;
            $this->feedConnection = new FeedConnection($primary_conn, $this->processorModel->getStreamId());
            $this->init();
        } catch (InvalidTokenException $ex) {
//          init any way so we can log in DB
            $this->init();
            $this->setPermErrorAndAlert($ex->getMessage());
            throw $ex;
        } catch (NoFeedTableException $ex) {
//          init any way so we can log in DB
            $this->init();
            $this->setErrorCount($ex->getCode());
            $this->processorModel->setLastError($ex->getMessage());
            $this->processorModel->updateStatusAndErrCount();
            throw $ex;
        }
    }

    public function __destruct()
    {
        unset($this->feedConnection);
        unset($this->logConnection);
    }

    /**
     * Initialize the various dataModels used by the processor
     */
    protected function init()
    {
        // create new instance of ProcessorLogModel
        $this->processorLogModel = new ProcessorLogModel($this->logConnection);
        $this->processorLogModel->setProcessorId($this->processorModel->getId());
        $this->processorLogModel->setSourceCode($this->streamModel->getSourceCode());
        $this->processorLogModel->setStreamName($this->streamModel->getName());
        $this->processorLogModel->setStreamUrl($this->streamModel->getUrl());
        $this->processorLogModel->setProcessorType($this->processorModel->getProcessorType());

        // create new instance of ApiLogModel
        $this->apiLogModel = new ApiLogModel($this->logConnection);
        $this->apiLogModel->setApiId($this->processorModel->getOAApi());
        $this->apiLogModel->setSourceCode($this->streamModel->getSourceCode());
        $this->apiLogModel->setStreamId($this->streamModel->getId());
        $this->apiLogModel->setProcessorId($this->processorModel->getId());
        $this->apiLogModel->setStreamName($this->streamModel->getName());


        if (is_object($this->feedConnection)) {
            $this->feedEntryModel   = new FeedEntryModel($this->feedConnection);
            $this->parentEntryModel = new FeedEntryModel($this->feedConnection);

            $this->processorModel->setStatusCode('ACTIVE');
            $this->invoke_time = $this->processorModel->readNow();
            $this->logAction("INITIALIZE", "INTERNAL", "Invoke time is: " . $this->invoke_time, self::LOG_MSG, 1);
        }

        // check if the stream has access token
        if ($this->processorModel->isAccessTokenRequired() && $this->processorModel->getOAAccessToken() == '') {
            throw new InvalidTokenException("There is no access token", 401);
        }

        $this->processorData = $this->processorModel->getProcessorData();
        if (!is_array($this->processorData)) {
            $this->processorData = [];
        }

        // Read current timestamp from database
        $this->now = $this->streamModel->readNow();
        // Get stream's notification configuration
        $this->notificationModel = new NotificationModel($this->primaryConnection);
        $this->notificationModel->setStreamId($this->streamModel->getId());
        $this->notificationModel->Read();
    }

    public function getApiThrottleProvider(){
        if(!is_object($this->apiThrottleProvider)){
            $this->setApiThrottleProvider();
        }
        return $this->apiThrottleProvider;
    }
    private function setApiThrottleProvider(){
        $remaining_calls = (isset($this->processorData['REMAINING_CALLS'])) ? $this->processorData['REMAINING_CALLS'] : 0;
        $previous_invoke_time = (isset($this->processorData['INVOKE_TIME'])) ? $this->processorData['INVOKE_TIME'] : 0;

        $this->apiThrottleProvider  = new ApiThrottleProvider();
        $this->apiThrottleProvider->initWithPrimaryConnection(
                                        $this->runType,
                                        $this->streamModel->getSourceCode(),
                                        $remaining_calls,
                                        $this->invoke_time,
                                        $previous_invoke_time,
                                        $this->primaryConnection
                                    );
    }

    protected function getProcSchedule($last_run_type)
    {
        $now = $this->processorModel->readNow();

        $resArray = [];
        $resArray['RUN_TIME'] = $now + (10 * 60);
        $resArray['RUN_TYPE'] = $last_run_type;
        return $resArray;
    }

    public function reschedule($success_code, $run_type)
    {
        if ($this->processorModel->getStatusCode() != 'ACTIVE') {
            $success_code = 400;
        }
        $this->setErrorCount($success_code);

        $schedule = $this->getProcSchedule($run_type);
        $this->processorModel->setRunTime(date('Y-m-d H:i:s', $schedule['RUN_TIME']));
        $this->processorModel->setRunType($schedule['RUN_TYPE']);

        $this->processorModel->setProcessorData($this->processorData);
        if (in_array($this->streamModel->getStatusCode(), array('DELETED', 'DISABLED'))) {
            $this->processorModel->setStatusCode($this->streamModel->getStatusCode());
        }
        $this->processorModel->setLastRunType($run_type);
        $this->processorModel->setLastRunTime(date('Y-m-d H:i:s', $this->invoke_time));

        $this->logAction('RESCHED', 'BASE_PROC',
            'Next run_time=' . $this->processorModel->getRunTime() . ', run_type=' . $this->processorModel->getRunType(),
            self::DEBUG_MSG);
        $this->processorModel->Update();

        return $success_code;
    }

    protected function setProcessorState($run_type)
    {
        $this->processorData['NEXT_RUN'][$run_type] = $this->getNextRunTime($run_type);
        switch ($run_type) {
            case 'NEW':
                // $this->processorData['NEXT_RUN']['NEW'] = $this->getNextRunTime('NEW');
                $this->processorData['CURSORS']['NEW']['SINCE'] = $this->cursors['SINCE'];
                $this->processorData['CURSORS']['NEW']['UNTIL'] = null;
                break;
            case 'OLD':
                $this->processorData['CURSORS']['OLD']['SINCE'] = null;
                $this->processorData['CURSORS']['OLD']['UNTIL'] = $this->cursors['UNTIL'];
                break;
            default:
                break;
        }
    }

    public function logAction(
        $action_code,
        $action_type,
        $action_text,
        $debug = false,
        $action_count = 0,
        $id_code = null
    )
    {
        // echo 'log = ' . $action_text . ' count:' . $action_count . PHP_EOL . '<br/><br/>' . PHP_EOL;
        if (!$debug || ($debug && $this->processorModel->getDebugFlag())) {
            $this->processorLogModel->reset();
            $this->processorLogModel->setActionCode($action_code);
            $this->processorLogModel->setActionType($action_type);
            $this->processorLogModel->setRunId($this->invoke_time);
            $this->processorLogModel->setActionCount($action_count);
            $this->processorLogModel->setIdCode($id_code);
            $this->processorLogModel->setActionText($action_text);
            $this->processorLogModel->Insert();
        }
    }

    public function saveApiCall($actionCode, $actionResult, $apiCallUrl, $actionText = '', $params = [])
    {
        $this->apiLogModel->reset();
        $this->apiLogModel->setActionCode($actionCode);
        $this->apiLogModel->setActionResult($actionResult);
        $this->apiLogModel->setApiCallUrl($apiCallUrl);
        $this->apiLogModel->setActionText($actionText);
        isset($params['characters']) ? $this->apiLogModel->setCharacters($params['characters']) : $this->apiLogModel->setCharacters(0);
        isset($params['bytes']) ? $this->apiLogModel->setBytes($params['bytes']) : $this->apiLogModel->setBytes(0);
        $this->apiLogModel->Insert();
    }

    public function setJobError($action_code, $action_type, $message)
    {
        $this->jobModel->setStatusCode('ERROR');
        $this->logAction($action_code, $action_type, $message, self::LOG_MSG, 1);
        $this->processorModel->setStatusCode('ERROR');
        $this->processorModel->setLastError($message);
    }

    public function setErrorCount($successCode)
    {
        $error_count = $this->processorModel->getCountError();
        if ($successCode >= 400) {
            $error_count += 1;
            $this->processorModel->setCountError($error_count);
        } else {
            $this->processorModel->setCountError(0);
        }
        if ($error_count >= 5) {
            $this->sendAlertEmail();
            $this->logAction("SET_ERR_COUNT", "INTERNAL", 'Set status_code: PERM_ERROR and send alert email',
                self::LOG_MSG, 1);
            $this->processorModel->setStatusCode('PERM_ERROR');
            $this->processorModel->setCountError(0);
        }
    }

    protected function setPermErrorAndAlert($message = '')
    {
        $this->sendAlertEmail();
        $this->logAction("SET_ERR_COUNT", "INTERNAL", 'Set status_code: PERM_ERROR and send alert email', self::LOG_MSG, 1);
        $this->processorModel->setStatusCode('PERM_ERROR');
        $this->processorModel->setCountError(0);
        $this->processorModel->setLastError($message);
        $this->processorModel->updateStatusAndErrCount();
    }

    protected function sendAlertEmail($subject = null, $message = null, $recipient = ['address' => 'technology@icuc.social', 'name' => 'Technology'])
    {
        if ($_SERVER['SOCIAL_PATROL_ENV'] == 'PRODUCTION' || $_SERVER['SOCIAL_PATROL_ENV'] == 'TESTING') {
            if (!$subject) {
                $subject = 'SP Error';
            }
            $subject .= ' ' . $this->streamModel->getName() . ' (' . HOST_CLEAN . ')';
            if (!$message) {
                $message = 'The ' . $this->processorModel->getProcessorType() . ' processor for the "' . $this->streamModel->getName() .
                    '" stream for the client "' . $this->streamModel->getClientName() . '",<br/> ' .
                    'is encountering a problem and is currently in the ERROR state. <br/> ';

                $message .= '<br/>The last error message received is: "' . $this->processorModel->getLastError() . '"<br/><br/>';
            }
            $message .= '<br/>Processor OAuth name: ' . $this->processorModel->getOAAcctName() . ', id: ' . $this->processorModel->getId() . ', stream id: ' . $this->streamModel->getId();

            Utilities::sendEmail($this->primaryConnection, $subject, $message, $recipient);
        }
    }

    protected function cleanup()
    {
        $this->getApiThrottleProvider()->updateLimits();

        // recording the total runtime
        $now = $this->processorModel->readNow();
        $elapsed = $now - $this->invoke_time;
        $this->logAction("CLEANUP", "INTERNAL", "Completed processing in " . $elapsed . "s", self::LOG_MSG, $elapsed );
        unset($this->parentEntryModel);
        unset($this->feedEntryModel);
        unset($this->apiLogModel);
        unset($this->processorLogModel);
    }

    protected function getNextRunTime($type)
    {
        $now = $this->processorModel->readNow();
        return $now + (10 * 60);
    }

    public function handleErrors(Exception $ex, $sendProcessorToError = true)
    {
        $this->lastApiError = $ex;
        $this->processorModel->setLastError($ex->getMessage());
        if ($sendProcessorToError) {
            $this->processorModel->setStatusCode('ERROR');
        }
    }

    /**
     * Sends notifications to recipients when new content arrives
     * @param string $queue_code
     * @param string $result_code
     * @return bool
     */
    protected function sendNotifications($queue_code = '', $result_code = '')
    {
        //Check if notification option is enabled
        if (!$this->notificationModel->getActive()) {
            return false;
        }
        // Check the result_code that should be NEW
        if ($result_code !== 'NEW') {
            return false;
        }
        // Set default notification email template values
        $subject = 'New content notification in';
        $message = 'There is new content available for moderation in the '.$this->streamModel->getName().' ('.$this->streamModel->getSourceCode().') stream. <br/> http://' .HOST_CLEAN.'/moderate/'.$this->streamModel->getClientId().'/'.$this->streamModel->getId().'';
        // Set default previous datetime notification sent
        $prev_time_sent = $this->notificationModel->getLastTimeSent();

        // Check if the queues are configured
        if (isset($this->notificationModel->getData()['queues']) && is_array($this->notificationModel->getData()['queues'])) {
            // If yes and if $queue_code is not in that list(don't send notification)
            $queue_list = [];
            foreach($this->notificationModel->getData()['queues'] as $queue) {
                if (isset($queue['name'])) {
                    array_push($queue_list, $queue['name']);
                }
            }
            // $queue_list = ['Olympics', 'Another queue', 'etc.']
            if (!in_array($queue_code, $queue_list)) {
                return false;
            }

            // Queues are configured, queue is in the list
            // Get last time sent for this specific queue
            $data = $this->notificationModel->getData();
            $updated_queues = [];
            foreach($data['queues'] as $queue) {
                if ($queue['name'] == $queue_code) {
                    if (isset($queue['last_time_sent'])) {
                        // Set previous datetime sent from this queue
                        $prev_time_sent = $queue['last_time_sent'];
                        // Update last_time_sent for this specific queue
                        $queue['last_time_sent'] = date('Y-m-d H:i:s', $this->now);
                    }
                }
                $updated_queues[] = $queue;
            }
            $data['queues'] = $updated_queues;
            // Save updated data in notifications table
            $this->notificationModel->setData($data);

            // Update default notification template values
            $subject = 'New content notification for queue '.$queue_code.' in';
            $message = 'There is new content available for moderation in the '.$queue_code.' queue in the '.$this->streamModel->getName().' ('.$this->streamModel->getSourceCode().') stream. <br/> http://' .HOST_CLEAN.'/moderate/'.$this->streamModel->getClientId().'/'.$this->streamModel->getId().'';
        }

        // Check if the difference between prev time sent and current time more than frequency
        $currentTime = strtotime(date('Y-m-d H:i:s', $this->now));
        $lastTimeSent = strtotime($prev_time_sent);
        $difference_in_minutes = round(abs($currentTime - $lastTimeSent) / 60);

        if ($this->notificationModel->getFrequency() > $difference_in_minutes) {
            return false;
        }

        //We are good and ready for sending notifications
        //Get recipients
        $recipientsList = explode(',', $this->notificationModel->getRecipients());
        $recipientsIdsList = $recipientsEmailList = [];
        foreach ($recipientsList as $recipient) {
            if (strpos($recipient, '@') === false) {
                array_push($recipientsIdsList, trim($recipient));
            } else {
                array_push($recipientsEmailList, trim($recipient));
            }
        }
        //Get email addresses from recipients ids
        if (count($recipientsIdsList)) {
            $userModel = new User2Model($this->primaryConnection);
            $emails = $userModel->readEmailAddressesFromUserIds(implode(',', $recipientsIdsList));
            if ($emails && count($emails)) {
                foreach ($emails as $row) {
                    foreach ($row as $k => $v) {
                        array_push($recipientsEmailList, trim($v));
                    }
                }
            }
        }
        //Send notification alerts to recipients
        if (count($recipientsEmailList)) {
            try {
                //Save last time sent to current time
                $this->notificationModel->setLastTimeSent(date('Y-m-d H:i:s', $this->now));
                $this->notificationModel->Update();
                //Do not send any notifications if this is AUTOMATED_TESTS
                if (!defined('AUTOMATED_TESTS')) {
                    $this->sendAlertEmail($subject, $message, ['address' => $recipientsEmailList, 'name' => '']);
                    //TODO: Send via Google hangouts
                }
            } catch(Exception $e) {
                $this->logAction("NOTIFICATIONS", "ERROR", "Can not send notifications. Error message:" . $e->getMessage(), self::LOG_MSG, 0);
            }
        }
        return true;
    }

    /**
     * Helper method for making response
     * @param $status_code
     * @param $message_type
     * @param $message_text
     */
    protected function setResult($res, $status_code, $message_type, $message_text)
    {
        $res['Content-Type'] = 'application/json';
        $res->status($status_code);
        $json_response = json_encode([
            'messageType' => $message_type,
            'message' => $message_text
        ]);
        $this->logAction('RESPONSE', "BASE_PROC", "Response: " . $json_response, self::LOG_MSG);
        $res->write($json_response);
        return;
    }
}
