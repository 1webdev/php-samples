<?php

class Admin_Model_Reports extends App_Model_Abstract
{
    
    protected $_statsApi;
    
    /**
     * @param array $sites List of sites which can accesses by the model
     * @param System_Search_Filters_Select $filter List of sites which can accesses by the model
     */
    public function __construct(array $sites, System_Search_Filters_Select $filter)
    {
        $this->_siteIds = $sites;
        $this->_sitesFilter = $filter;
    }

    /**
     * @return System_Grid_DataGrid
     */
    public function getPublisherSubAppStatsGrid()
    {
        $grid = new System_Grid_DataGrid();

        $grid->setFilters(array('Dates', $this->_sitesFilter, 'Publisher', 'AppId'));

        $grid->setExportable(true)
             ->setDefaultSort('app_id')
             ->setDefaultDir('desc')
             ->setTemplatePart('body', 'reports/grid/pub-subapp-report-body.phtml')
             ->setLimit(-1);

        $subColumn     = 'sub_app';
        $exportColumns = array(
            'pub_name',
            'app_id',
            'app_name',
            'impressions',
            'clicks',
            'ecpm_net',
            'ecpm_gross',
            'earned_publisher',
            'earned_adblade'
        );
        $grid->setExportColumns($exportColumns)
             ->setExportSubColumns(array($subColumn => $exportColumns));

        $grid->addColumn('pub_name',            array('header' => 'Publisher'));
        $grid->addColumn('app_id',              array('header' => 'App ID'));
        $grid->addColumn('app_name',            array('header' => 'App Name'));
        $grid->addColumn('impressions',         array('header' => 'Impressions'));
        $grid->addColumn('clicks',              array('header' => 'Clicks'));
        $grid->addColumn('ecpm_net',            array('header' => 'NET eCPM'));
        $grid->addColumn('ecpm_gross',          array('header' => 'Gross eCPM'));
        $grid->addColumn('earned_publisher',    array('header' => 'Publisher Earned'));
        $grid->addColumn('earned_adblade',      array('header' => 'AdBlade Earned'));

        $pubId     = $grid->getFilter('Publisher')->getValue();
        $appId     = $grid->getFilter('AppId')->getValue();
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate   = $grid->getFilter('Dates')->getValue('endCalendarDate');
        $siteId    = $this->_sitesFilter->getValue();

        $appsTable = App_Model_Tables::getAppsTable();

        // Find any app that has sub-app associated
        $apps = $appsTable->getStatsForAppWithSubApp(
            $pubId, $appId, $startDate, $endDate, $siteId
        );

        // Filter app IDs
        $appIds = array();
        foreach ($apps as $app) {
            $appIds[] = $app['app_id'];
        }

        // Fetch sub-app stats for every app ID filtered
        $subAppStats = $appsTable->getSubAppStats($appIds, $startDate, $endDate);

        // Assemble all stat arrays together
        foreach ($apps as $i => $app) {

            $subApps = array();

            foreach ($subAppStats as $subApp) {
                if ($subApp['app_id'] == $app['app_id']) {

                    // Process sub-app data
                    $subApp['app_id'] = '';
                    $subApps[] = $subApp;
                }
            }

            if ($subApps) {
                // Push sub-app stats if master app has any
                $apps[$i][$subColumn] = $subApps;
            } else {
                // If no stats found - remove such app
                unset($apps[$i]);
            }
        }

        return $grid->setDataSource(new System_Grid_DataGrid_DataSource_Array(array_values($apps)));
    }

    public function appsAdStatsGrid($adId)
    {
        $blockTypeFilter = new System_Search_Filters_Select();
        $profitMargin    = new System_Search_Filters_Checkbox();
        
        $datesFilter = new System_Search_Filters_DatesJQ();
        $datesFilter->setDefault(System_Search_Date::TODAY);
        
        $grid = new System_Grid_DataGrid();
        $grid->setSortable(true)
             ->setLimit(100);
        $grid->setDefaultSort('impressions')
             ->setDefaultDir('desc'); 
        $grid->setFilters(array($datesFilter, $profitMargin, $blockTypeFilter));
        
        $startDate = $grid->getFilter('DatesJQ')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('DatesJQ')->getValue('endCalendarDate');
        
        $showProfitMargin = $profitMargin->setLabel('Show Profit %')
                                         ->setWidth(200)
                                         ->setDefaultValue(true)
                                         ->getValue();

        $blockTypeOptions = array();
        foreach (App_Model_Tables::getAppAdStatusesTable()->getStatusesList() as $option) {
            $blockTypeOptions[$option['id']] = $option['name'];
        }
        $blockType = $blockTypeFilter->setLabel('Block Type')
                                     ->setOptions($blockTypeOptions + array('pubBlock' => 'Publisher Block'))
                                     ->getValue();
        
        $select = $this->_getAppsAdStatsSelect($adId, $startDate, $endDate);

        if (!is_null($blockType)) {
            if ($blockType == 'pubBlock') {
                $select->where('appAdStatus.id IS NULL');
            } else {
                $select->where('appAdStatus.id = ?', $blockType);
            }
        }

        $finalSelect = $this->getDb(true)->select()->from(array('results'=>$select));

        //the query should use master DB, because it can be delay in replication
        //and changes on page aren't sticked.
        $data = $this->getDb()->fetchAll($finalSelect);
        $this->_addRankData($adId, $data);
        
        $dataSource = new System_Grid_DataGrid_DataSource_Array($data);
        $grid->setDataSource($dataSource);
        
        $grid->addColumn('app_id', array(
                'header'=>'App ID','type'=>'link',
                'links'=>'/admin/apps/app-statistics/appId/$app_id',
                'sortable'=>true, 'firstDirDesc'=>true
             ))
             ->addColumn('app_extended_id', array(
                'header'=>'App Extended ID',
                'sortable'=>true, 'firstDirDesc'=>true
             ))
             ->addColumn('appName', array(
                 'header'=>'App Name','type'=>'link',
                 'links'=>'/admin/reports/app-ad-stats/adid/$ad_id/appid/$app_id',
                 'sortable'=>false,
             ))
             ->addColumn('media_cost', array(
                 'header'=>'Media Cost','sortable'=>false))
             ->addColumn('impressions', array(
                 'header'=>'Impressions','type'=>'number','sortable'=>true, 'firstDirDesc'=>true
             ));
        $ad = $this->getTable()->getAdsTable()->getById($adId);
        $isMobile = $ad->{'target_devices'}==App_Model_DbTable_DeviceTypes::DEVICE_TYPE_MOBILE?true:false;
        if ($isMobile) {
            $grid->addColumn('mobileCancelClicks', array(
                'header'=>'Clicked Cancel','type'=>'number','sortable'=>false,
            ));            
        }
        $grid->addColumn('clicks', array('header'=>'Clicks','type'=>'number','sortable'=>true, 'firstDirDesc'=>true))
             ->addColumn('orders', array(
                    'header'=>'Conversions',
                    'type'=>'script',
                    'sortable'=>true,
                    'firstDirDesc'=>true,
                    'align'=>'right',
                    'scriptPath'=>'apps/grid/ad-stats-orders-column.phtml'
                 )
             )
             ->addColumn('convRate', array('header'=>'Conversion Rate','align'=>'right','sortable'=>false))
             ->addColumn('ctr', array('header'=>'CTR','align'=>'right','sortable'=>false))
             ->addColumn('cpa', array('header'=>'CPA','align'=>'right','sortable'=>false,'type'=>'money'))
             ->addColumn('max_cpa', array('header'=>'CPA Goal','sortable'=>false,'type'=>'money')) 
             ->addColumn('expended', array('header'=>'Expended','sortable'=>false,'type'=>'money'))
             ->addColumn('blockTypeName', array('header'=>'Block Type','type'=>'script','sortable'=>false,
                 'scriptPath'=>'apps/grid/ad-stats-block-column.phtml'))             
             ->addColumn('flexbid', array(
             		'header'=>'Flex Bid', 
             		'type'=>'script', 
             		'sortable'=>false,
             		'align'=>'right',
             		'scriptPath'=>'apps/grid/app-ad-flexbid-column.phtml'))
             ->addColumn('eCPM', array('header'=>'eCPM','align'=>'right','sortable'=>false,'type'=>'money'))
             ->addColumn('h_banner_ecpm', array(
             		'header'=>'(h) App eCPM',
             		'align'=>'right',
             		'sortable'=>true
                    , 'type'=>'money', 'firstDirDesc'=>true
             		))
             ->addColumn('banner_ecpm', array(
             		'header'=>'App eCPM',
             		'align'=>'right',
             		'sortable'=>true
                    , 'type'=>'money', 'firstDirDesc'=>true
             		))
             ->addColumn('rank', array(
                        'header'=>'Relative Rank',
                        'align'=>'right',
                        'sortable'=>true
                        , 'type'=>'number', 'firstDirDesc'=>true
                        ))
             		;

         if ($showProfitMargin) {
             $grid->addColumn('profitMargin', array(
                     'index'=>'',
                     'header'=>'Profit %',
                     'align'=>'right',
                     'sortable'=>false
             ));
             $grid->getColumn('profitMargin')->setDefault('0.00');
         }

        return $grid;
	}
    
    /**
     *
     * @param type $appId
     * @return System_Grid_DataGrid 
     */
    public function getAppMonitorCoverage($appId)
    {
        $appId = (int)$appId;
        $_appsTable = $this->getTable()->getAppsTable();
        $appData = $_appsTable->getApp($appId);
        if (!$appData) {
            return false;
        }        
        $maxDisplayAds = $_appsTable->getMaxDisplayAds($appId);

        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(false)
             ->setLimit(-1);
        $grid->setDefaultSort('hour')
             ->setDefaultDir('asc');
        $grid->setFilters(array('dates'));        
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate = $grid->getFilter('Dates')->getValue('endCalendarDate');
        
        $db = $this->getMongoDb();
        $map = new MongoCode("function() {            
            emit({pos:this._id.ads,hour:parseInt(this._id.date%100)},{imps:this.imps});
        }");
        $reduce = new MongoCode("function(k, vals) { ".
            "var imps = 0;
            vals.forEach(function(v){imps+=v.imps});
            return {imps:imps}; }");
        //@TODO:
        //maybe make sense to create a new class for mongo which will handle queries like this one
        $_query = array(                
            '_id.appId' => $appId,         
        );
        if (!empty($startDate) && !empty($endDate)) {
            $_mStartId = (int)(date('Ymd', strtotime($startDate.' 00:00:00') ).'00');
            $_mEndId = (int)(date('Ymd', strtotime($endDate.' 23:59:59')).'23');
            $_query['_id.date'] = array('$gte'=>$_mStartId,'$lte'=>$_mEndId);
        }
        $result = $db->command(array(
            "mapreduce" => 'appPos', 
            "map" => $map,
            "reduce" => $reduce,
            "query" => $_query,
            "out"=>array("inline" => 1),
        ), array('timeout'=>2*3600*1000, 'socketTimeoutMS'=>2*3600*1000));//old new timeout support
        if ($result['ok']!=1) {
            throw new Exception('error: '.var_export($result, true));
        }
        $_items = $result['results'];
        $grid->addColumn('hour', array('header'=>'Hour','align'=>'right'));
        $_data = array();
        for ($i=1;$i<=$maxDisplayAds;$i++) {
            $grid->addColumn('position'.$i, array(
                'header'=>"Position $i, # (%)",
                'align'=>'right','type'=>'text'
            ));            
        }
        
        $grid->addColumn('total', array('header'=>'Total','align'=>'right','type'=>'number'));
        for ($j=0;$j<24;$j++) {
            $_data[$j]['hour'] = $j;
            for ($i=1;$i<=$maxDisplayAds;$i++) {        
                $_data[$j]["position$i"] = '0.00';
            }
            $_data[$j]['total'] = 0;
        }        
        foreach ($_items as $_item) {
            $_hour = (int)$_item['_id']['hour'];
            $_data[$_hour]['position'.$_item['_id']['pos']] = $_item['value']['imps'];
            $_data[$_hour]['total']+=$_item['value']['imps'];            
        }   
        //Postion column means how many times the "POSTION" # of ads was shown.
        //Example: if 3 ads was show, the postion #3 get +1 imp
        foreach ($_data as &$_item) {
            if ($_item['total']) {       
                $total = $_item['total'];
                for ($i=1;$i<=$maxDisplayAds;$i++) {
                    $_imps = $_item["position$i"];
                    $_pos = number_format($_imps) . ' ('.number_format($_imps/$total*100, 2).'%)';
                    $_item["position$i"] = $_pos;                    
                }
            }
        }
        
        $filename = "app_monitor_coverage_{$startDate}-{$endDate}";
        $grid->setExportable(true)->setExportFilename($filename);
        
        $_fields = array();
        foreach ($grid->getColumns() as $column ) {
            $_fields[]=$column->getId();
        }
        $grid->setExportIncludeFields($_fields);
        
        $grid->setDataSource(new System_Grid_DataGrid_DataSource_Array($_data));
        return $grid;
    }
    
    public function appAdStatsGrid($appId, $adId)
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(false)
             ->setLimit(-1);//don't use limit
        $grid->setDefaultSort('dateTime')
             ->setDefaultDir('asc');
        $grid->addColumn('dateTime', array('header'=>'Date/Time') )
             ->addColumn('ipAddr', array('header'=>'IP Address'))
             ->addColumn('url', array('header'=>'URL'))             
            ;

        $grid->setFilters(array('dates'));
        
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('Dates')->getValue('endCalendarDate');
        
        $_data = $this->_getAppAdStats($appId, $adId, $startDate, $endDate);
        $grid->setDataSource(new System_Grid_DataGrid_DataSource_Array($_data));
        
        return $grid;
    }
    
    public function getAdvertiserSignUpsGrid()
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(true)->setLimit(30);
        $grid->setDefaultSort('app_id')
             ->setDefaultDir("desc")
             ->setShowTotals(false);
        $grid->setTemplatePart('body', 'reports/grid/advertiser-signups-body.phtml');
        $grid->addColumn('app_id', array('header'=>'APP ID','sortable'=>true))
             ->addColumn('full_name', array('header'=>'App Name','sortable'=>true))
             ->addColumn('ad_id', array('header'=>'AD ID','sortable'=>true))
             ->addColumn('display_name', array('header'=>'Display Name','sortable'=>true))
             ->addColumn('user_id', array('header'=>'User ID','sortable'=>true))
             ->addColumn('creation_date', array('header'=>'Sign up Date','sortable'=>true));
        $grid->setFilters(array('dates'));
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate = $grid->getFilter('Dates')->getValue('endCalendarDate');
        $select = $this->getTable()->getAdvertiserSignupsTable()->getAdvertiserSignupsSelect($startDate, $endDate);
        $grid->setSelect($select);
        return $grid;
    }
    
    public function sendCreditAdvancesReport($startDate=null, $endDate=null)
    {
        $grid = $this->getCreditAdvancesReportGrid($startDate, $endDate);
        $exportObject = $grid->getExportFile();

        $at              = new Zend_Mime_Part( $exportObject->getData() );
        $at->type        = $exportObject->getMimeType() ;//Zend_Mime::TYPE_OCTETSTREAM;
        $at->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
        $at->encoding    = Zend_Mime::ENCODING_BASE64;
        $at->filename    = $exportObject->getFilename();
        
        if (! empty($startDate) && ! empty($endDate)) {
            $subject = "Credit Advances Report {$startDate} - {$endDate}";
        } else {
            $subject = 'Monthly Credit Advances Report';
        }
        
        $mailer = App_Di::instance()->getMailer();
        $mail   = $mailer->createMail(App_Model_Mailer::CREDIT_ADVANCES_REPORT);
        $mail->clearSubject()
             ->setSubject($subject)
             ->addAttachment($at)
             ->send();
        
        return true;
    }

    /**
     * Send report to users which are subscirbed to provided interval($type)
     * Use list of emails to send to from reports table - subscriber's emails
     * @param string $intervalStr daily|weekly|monthly
     * @return boolean
     */
    public function sendReports($intervalStr)
    {
        $interval = Admin_Model_DbTable_ReportIntervals::DAILY;
        $dateRange = System_Search_Date::YESTERDAY;
        switch ($intervalStr) {
            case 'daily':
                    $interval = Admin_Model_DbTable_ReportIntervals::DAILY;
                    $dateRange = System_Search_Date::YESTERDAY;
                break;
            case 'weekly':
                    $interval = Admin_Model_DbTable_ReportIntervals::WEEKLY;
                    $dateRange = System_Search_Date::LAST_WEEK;
                break;
            case 'monthly':
                    $interval = Admin_Model_DbTable_ReportIntervals::MONTHLY;
                    $dateRange = System_Search_Date::LAST_MONTH;
                break;
            default:
                throw new System_Model_Exception('Wrong interval');
        }

        //Get list of reports which need to send,
        //user "email" from reports table
        $reports = $this->getTable()->getReportsTable()
                ->getByInterval($interval);

        if (!empty($reports)) {
            $dates = new System_Search_Filters_Dates();
            $dates->setDefault($dateRange);
            $startDate = $dates->getValue('startCalendarDate');
            $endDate = $dates->getValue('endCalendarDate');


            $sleep = false;
            $sleepSeconds = 5;
            if (count($reports)>10) {
                $sleep = true;
            }
            foreach ($reports as $_item) {
                switch ($_item['type_id']) {
                    case Admin_Model_DbTable_ReportTypes::ADS_REPORT:
                        $result = $this->_sendAdsReport(
                          $_item['user_id'], $_item['email'], $_item['interval_name'],
                          $startDate, $endDate, $_item['site_id']
                        );
                        break;
                    case Admin_Model_DbTable_ReportTypes::PACING_REPORT:
                        $result = $this->_sendPacingReport(
                          $_item['user_id'], $_item['email'], $_item['interval_name'],
                          $startDate, $endDate, $_item['site_id']
                        );
                        break;                    
                    case Admin_Model_DbTable_ReportTypes::SECTION_STATISTICS_REPORT:
                        $result = $this->_sendSectionStatisticsReport(
                          $_item['user_id'], $_item['email'], $_item['interval_name'],
                          $startDate, $endDate, $_item['site_id']
                        );
                        break;                    
                    default:
                        throw Sytem_Model_Exception('Invalid report type');
                }
                if ($result && $sleep) {
                    sleep($sleepSeconds);
                }
            }
        }

        return true;
    }

    public function sendPerformanceReports()
    {
        $today = date('Ymd');
        $_db = $this->getDb();
        //Order by overallEcpm for further "foreach"
        $adsTable = $this->getTable()->getAdsTable();
        $adsSelect = $adsTable->getAdsOverallEcpmSelect($today, $today);
                
        $adsSelect
                ->order('ad_type_unit_id')
                ->order('overallECPM DESC');
        $_ads = $_db->fetchAll($adsSelect);        
        
        $_pnAds = array();
        $_notPnAds = array();
        $_pnMinImps = 10000;
        $_pnAdsLimit = 20;
        $_notPnMinImps = 5000;
        $_pnReachModeCount = 0;
        $_pnMobileCount = 0;
        $_pnWebCount = 0;
        foreach ($_ads as $_ad) {
            $_target = $adsTable->getAdTargetType($_ad['id']);
            $_ad['ad_target'] = $_target;
            $_imps = $_ad['impressions'];
            $_ad['eCPM'] = $_imps?$_ad['expended']*1000/$_imps:0;
            if (strtolower( substr($_target, 0, 2) )=='pn') {                
                $_unitId = $_ad['ad_type_unit_id'];
                switch ($_unitId) {
                    case User_Model_DbTable_AdTypeUnits::WEB:
                        if ($_imps>=$_pnMinImps && $_pnWebCount<$_pnAdsLimit) {
                            $_pnAds[] = $_ad;
                            $_pnWebCount++;
                        }
                        break;
                }
            } else if ($_imps>=$_notPnMinImps) {
                $_notPnAds[] = $_ad;
            }
        }
        
        $appsSelect =  $this->getTable()->getAppsTable()
                ->getRevenueStatisticsSelect($today, $today);
        $appsSelect
            ->order('unit_type_id')
            ->having('grossProfit<0')
            ->having('impressions>=10000');
        $_apps = $_db->fetchAll($appsSelect);
        
        if (!empty($_apps) || !empty($_pnAds) || !empty($_notPnAds)) {
            
            $params = array(
                'pnAds'     => $_pnAds,
                'notPnAds'  => $_notPnAds,
                'apps'      => $_apps,
            );
            
            $mailer = App_Di::instance()->getMailer();
            $mailer->createMail(App_Model_Mailer::PERFORMANCE_REPORT, $params)
                   ->send();
            
            return true;
        }
        return false;
        
    }
    
    public function sendWebsitesReport()
    {        
        $id = $this->getHelper('adblade')->getSettings()
                ->other->websitesReportMasterId;
        $db = $this->getDb();
        $websitesReportSelect = $db->select()
            ->from(array('apps'=>'adrev_app'), array(
                'DATE_FORMAT(traffic.creation_date,"%m/%d/%Y")',
                'apps.id',
                'apps.full_name',
                'SUM(traffic.impressions) AS impressions', 
                'cpm'=>'IF(SUM(traffic.impressions)>0, 
                   SUM(traffic.expended-traffic.earned-traffic.cobrand_earned)*1000/SUM(traffic.impressions),0)',
                'earned'=>'SUM(traffic.expended-traffic.earned-cobrand_earned)'
            ))
            ->joinLeft(array('traffic'=>'adrev_app_zone'), '`traffic`.`app_id` = `apps`.`id`', array())
            ->join(array('pub'=>'adrev_users'), '`pub`.`id` = `apps`.`user_id`', array())
            ->join(array('master'=>'user_master'), '`pub`.`id` = `master`.`user_id`', array())
            ->group('apps.id')
            ->group('traffic.creation_date')
            ->order('traffic.creation_date DESC')
            ->order('apps.id ASC')
            ->where('`master`.`master_id`=?', $id);
        $_startDate = date("Y-m-d", mktime(0, 0, 0, date("m") - 1, 1, date("Y")));
        $_endDate = date("Y-m-d", mktime(0, 0, 0, date("m"), 0, date("Y")));
        $websitesReportSelect->where('traffic.creation_date>=?', $_startDate);
        $websitesReportSelect->where('traffic.creation_date<=?', $_endDate);
        $data = $db->fetchAll($websitesReportSelect);
        array_unshift($data, array('Date', 'App ID', 'Sitename', 'Impressions', 'Ecpm', 'Revenue'));
        require_once 'Outcome/Export/CSV.php';
        $csv = new Outcome_Export_CSV();
        
        $attachment              = new Zend_Mime_Part($csv->process($data));
        $attachment->type        = Zend_Mime::TYPE_OCTETSTREAM;
        $attachment->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
        $attachment->encoding    = Zend_Mime::ENCODING_BASE64;
        $attachment->filename    = 'websites.csv';
        
        $mailer = App_Di::instance()->getMailer();
        $mail   = $mailer->createMail(App_Model_Mailer::MONTHLY_WEBSITES_REPORT);
        $mail->addAttachment($attachment)
             ->send();
    }
    
    public function sendStatsComparison()
    {
        $usersTable = $this->getTable()->getUsersTable();
    	$usersList = $usersTable->comparisonUsersBalances();
    	//@TODO maybe it's better to fetch users with bad balances instead of all
    	//when we will have too many users
        
    	//unset users with good balances
    	if (is_array($usersList) && !empty($usersList)) {
            foreach ($usersList as $key=>$user) {
            	$difference=abs($user['balance'] - $user['required_balance']);
            	if ( $difference <= 5 ) {
            		unset($usersList[$key]);
            	}
            }
        }
        if (!empty($usersList)) {            
            $subject = 'Stats comparison - Users balances aren\'t correct';
            $this->getMail()
                ->assign('usersList', $usersList)
                ->setIsHtml(true)
                ->send(System_Mail::ERROR, null, null, $subject, 'stats-comparison-email.phtml');
            $this->log(System_Log::EMAIL, 'Stats comparison email was sent', Zend_Log::INFO);
        }
    }
    
    public function sendDeadTrans()
    {
        $paymentsTable = $this->getTable()->getPaymentsTable();
        //Check DEAD payment transactions
        $deadTrans = $paymentsTable->getDeadAuthorizeTrans();
        
        if (!empty($deadTrans)) {      
             $subject = 'Stats comparison - Dead transactions';
            $this->getMail()
                ->assign('deadTrans', $deadTrans)
                ->setIsHtml(true)
                ->send(System_Mail::ERROR, null, null, $subject, 'dead-transactions-email.phtml');
            $this->log(System_Log::EMAIL, 'Dead transactions email was sent', Zend_Log::INFO);   
        }                

    }

    /**
     * @param $providerId
     * @param $startDate - yyyymmdd
     * @param $endDate - yyyymmdd
     * @return System_Grid_DataGrid
     */
    public function getDmpProviderMonthlyReportGrid($providerId)
    {
        if (is_null($providerId) || !is_numeric($providerId)) {
            throw new Exception('Missing provider id');
        }

        $providersTable = $this->getTable()->getDmpProvidersTable();
        $provider = $providersTable->getById($providerId);
        if (is_null($provider)) {
            throw new Exception('invalid provider id');
        }

        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(true)->setLimit(-1);
        $grid->setDefaultSort('ad_id')
            ->setDefaultDir("asc")
            ->setShowTotals(true);
        $grid->addColumn('advertiser_id', array('header'=>'ADVERTISER'))
            ->addColumn('ad_id',          array('header'=>'CAMPAIGN_NAME'))
            ->addColumn('segment_code',   array('header'=>'SEGMENT_ID'))
            ->addColumn('segment_name',   array('header'=>'SEGMENT_NAME'))
            ->addColumn('impressions',    array('header'=>'IMPRESSIONS'))
            ->addColumn('segment_cost',   array('header'=>'CPM'))
            ->addColumn('data_fee',       array('header'=>'DATA_FEE','showTotals'=>true));

        $grid->setFilters(array('dates'));
        $grid->getFilter('Dates')->setDefault(System_Search_Date::LAST_MONTH);

        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate = $grid->getFilter('Dates')->getValue('endCalendarDate');

        $db = $this->getDb(true);
        $select = $db->select()
            ->from(array('ast'=>Adblade_Db_Tables::AD_SEGMENT_TRAFFIC_DAY), array(
                'advertiser_id'    => 'ads.user_id',
                'ad_id'            => 'ast.ad_id',
                'segment_code'     => 'dps.segment_code',
                'segment_name'     => 'dps.name',
                'impressions'      => 'SUM(ast.impressions)',
                'segment_cost'     => 'dps.cost',
                'data_fee'         => 'SUM(ast.expended)',
            ))
            ->join(array('dps'=>Adblade_Db_Tables::DMP_PROVIDER_SEGMENTS),
                'dps.id=ast.segment_id AND dps.provider_id='.(int)$providerId,
                array())
            ->join(array('ads'=>Adblade_Db_Tables::ADREV_ADS), 'ads.id=ast.ad_id', array())
            ->group(array('ast.ad_id','ast.segment_id'));
        if (null!==$startDate) {
            $select->where('ast.creation_date>=?', $startDate);
        }
        if (null!==$endDate) {
            $select->where('ast.creation_date<=?', $endDate);
        }
        $grid->setExportColumns(array(
            'advertiser_id','ad_id','segment_code','segment_name','impressions','segment_cost','data_fee'
        ));
        $filename = "dmp-monthly-report-{$provider->name}-{$startDate}-{$endDate}";
        $grid->setExportable(true)->setExportFilename($filename);
        $grid->setSelect($select);
        return $grid;
    }

    public function sendDmpProviderReport($providerId)
    {
        $grid = $this->getDmpProviderMonthlyReportGrid($providerId);
        $providersTable = $this->getTable()->getDmpProvidersTable();
        $provider = $providersTable->getById($providerId);
        $grid->setExportAdapter(array('xls'));
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate = $grid->getFilter('Dates')->getValue('endCalendarDate');

        $exportObject    = $grid->getExportFile();
        $at              = new Zend_Mime_Part( $exportObject->getData() );
        $at->type        = $exportObject->getMimeType() ;//Zend_Mime::TYPE_OCTETSTREAM;
        $at->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
        $at->encoding    = Zend_Mime::ENCODING_BASE64;
        $at->filename    = $exportObject->getFilename();

        $subject = sprintf('DMP monthly report for %s %s_%s', $provider->name, $startDate, $endDate);

        $mailer = App_Di::instance()->getMailer();
        $mail   = $mailer->createMail(App_Model_Mailer::DMP_REPORT);
        $mail->clearSubject()
            ->setSubject($subject)
            ->addAttachment($at)
            ->send();
        $this->log(System_Log::EMAIL, 'DMP monthly report email was sent for $providerId - ' . $providerId, Zend_Log::INFO);
        return true;
    }

    /**
     * If params provided, it will be generated report for this interval
     * otherwise - for last_month or data in request
     * @param string|null $startDate
     * @param string|null $endDate
     * @return System_Grid_DataGrid
     */
    public function getCreditAdvancesReportGrid($startDate=null, $endDate=null)
    {
        $grid = new System_Grid_DataGrid();
        
        $grid->setFilters(array('dates'));
        $grid->getFilter('Dates')->setDefault(System_Search_Date::LAST_MONTH);
        if (null===$startDate) {
            $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        }
        if (null===$endDate) {
            $endDate = $grid->getFilter('Dates')->getValue('endCalendarDate');
        }
        
        $filename = "credit_advances_report_{$startDate}-{$endDate}";
        $grid->setExportable(true)->setExportFilename($filename);
        
        $advertisers = $this->_getCreditAdvancesReport($startDate, $endDate);
        $data = array();
        $i=1;
        foreach ($advertisers as $key=>$row) {
            //Show only users with bill>0
            if ( $row['expendedCreditAdvanceAfterPeriod']>0 ) {
               $data[] = $row + array('rowNumber'=>$i++);
            } 
        }
        
        $grid->setDataSource(new System_Grid_DataGrid_DataSource_Array($data));


        $grid->setSortable(false)
             ->setLimit(999999999);
        $grid->setDefaultSort('rowNumber')
             ->setDefaultDir("asc")
             
             ->setShowTotals(false);
        $grid
             ->addColumn('rowNumber', array('header'=>'#'))
             ->addColumn('name',
                        array('header'=>'Advertiser',
                              'type'=>'link',
                              'links'=>"/admin/users/view/userId/\$id"))
            ->addColumn('site_name', array('header'=>'Site'))
            ->addColumn('organization', array('header'=>'Company'))
             ->addColumn('email',
                        array('header'=>'Email',
                              'type'=>'link',
                              'links'=>'mailto:$email'))
             ->addColumn('salesRep', array('header'=>'Sales Rep'))
             ->addColumn('accManager', array('header'=>'Account Manager'))
             ->addColumn('payedMoneyBeforePeriod',
                     array('header'=>
                         'Paid (Before Period)',
                         //'Paid<br /><span class="small_txt">Before Period',
                           'type'=>'money'))
             ->addColumn('credidAdvanceRestBeforePeriod',
                     array('header'=>
                         'Credit Advance (Rest Before Period)',
                         //'Credit Advance<br /><span class="small_txt">Rest Before Period',
                           'type'=>'money'))
             ->addColumn('expendedBeforePeriod',
                     array('header'=>
                         'Expended (Before Period)',
                         //'Expended<br /><span class="small_txt">Before Period',
                           'type'=>'money'))
             ->addColumn('ownMoneyRestBeforePeriod',
                     array('header'=>
                         'Own (Rest Before Period)',
                         //'Own<br /><span class="small_txt">Rest Before Period',
                           'type'=>'money'))
             ->addColumn('payedMoneyAfterPeriod',
                     array('header'=>
                         //'Paid<br /><span class="small_txt">In Period',
                         'Paid (In Period)',
                           'type'=>'money'))
             ->addColumn('creditAdvanceAfterPeriod',
                     array('header'=>
                         'Credit Advance (In Period)',
                         //'Credit Advance<br /><span class="small_txt">In Period',
                           'type'=>'money'))
             ->addColumn('credidAdvanceRestAfterPeriod',
                     array('header'=>
                         'Credit Advance Rest',
                         //'Credit Advance<br /><span class="small_txt">In Period',
                           'type'=>'money'))
             ->addColumn('expendedAfterPeriod',
                     array('header'=>
                         //'Expended<br /><span class="small_txt">In Period',
                         'Expended (In Period)',
                           'type'=>'money'))
             ->addColumn('expendedCreditAdvanceAfterPeriod',
                     array('header'=>'Bill',
                           'type'=>'money'))
             ->addColumn('ownMoneyRestAfterPeriod',
                     array('header'=>'Own',
                           'type'=>'money'))
                ;
        $grid->setExportIncludeFields(array(
            'rowNumber',
            'name',
            'organization',
            'site_name',
            'email',
            'salesRep',
            'accManager',
            'payedMoneyBeforePeriod',
            'credidAdvanceRestBeforePeriod',
            'expendedBeforePeriod',
            'ownMoneyRestBeforePeriod',
            'payedMoneyAfterPeriod',
            'creditAdvanceAfterPeriod',
            'credidAdvanceRestAfterPeriod',
            'expendedAfterPeriod',
            'expendedCreditAdvanceAfterPeriod',
            'ownMoneyRestAfterPeriod',
        ));
        return $grid;
    }

    public function getClassifiedsStatsGrid()
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(false)
             ->setLimit(-1);//don't use limit
        $grid->setDefaultSort('user_id')
             ->setDefaultDir('asc')
             ->setShowTotals(false);
        $grid->addColumn('user_id', array('header'=>'User Id'))
             ->addColumn('user_name', array(
                  'header'=>'User Name',
                  'type'=>'link',
                  'format'=>'$organization - $user_name',
                  'links'=>"/admin/users/view/userId/\$user_id/"
             )) 
             ->addColumn('imported', array('header'=>'Imported Ads','align'=>'right') );

        $grid->setFilters(array('dates'));
        
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('Dates')->getValue('endCalendarDate');
        $select = $this->getDb(true)->select()
            ->from(array('user'=>'adrev_users'), array(
                'user_id'=>'user.id',
                'user_name'=>'user.name',
                'organization',
            ))
            ->join(array('adv'=>'user_advertiser'),
                    'adv.user_id=user.id AND adv.type_id='.App_Model_DbTable_AdvertiserTypes::CLASSIFIEDS, array())
            ->joinLeft(array('ads'=>'adrev_ads'),
                'ads.user_id=user.id '
                .((null!==$startDate)?(' AND ads.creation_datetime>='.$this->getDb()->quote($startDate.' 00:00:00')):' ')
                .((null!==$endDate)?(' AND ads.creation_datetime<='.$this->getDb()->quote($endDate.' 23:59:59')):' '),
                array('imported'=>'SUM(IF(ads.id IS NULL,0,1))'))
            ->group('user.id');
        $grid->setSelect($select);
        
        $filename = "classifieds_{$startDate}-{$endDate}";
        $grid->setExportable(true)
             ->setExportFilename($filename)
             ->setExportIncludeFields(array('user_id','user_name','imported'));
        
        return $grid;
    }
    
    public function getContainersStatsGrid()
    {
        
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(false)
             ->setLimit(-1);//don't use limit
        $grid->setDefaultSort('container_id')
             ->setDefaultDir('asc')
             ->setShowTotals(false);
        $grid->setTemplatePart('body', 'reports/grid/containers-stats-body.phtml');
        $grid->addColumn('container_name', array('header'=>'Container') )
             ->addColumn('app_id', array('header'=>'App Id'))
             ->addColumn('full_name', array('header'=>'App Name'))
             ->addColumn('site_name',array('header'=>'Site'))
             ->addColumn('container_priority', array('header'=>'Priority') )
             ->addColumn('impressions', array('header'=>'Impressions') )
             ->addColumn('cpm', array('header'=>'eCPM') )
             ->addColumn('publisher_earned', array('header'=>'Publisher Earnings') )
             ->addColumn('cobrand_earned', array('header'=>'Cobrand Earnings') )
             ->addColumn('expended', array('header'=>'Total Earnings') )
             ->addColumn('grossProfit', array('header'=>'Gross Profit') )
             ->addColumn('grossProfitPct', array('header'=>'Gross Profit %') )
            ;

        $grid->setFilters(array('dates', $this->_sitesFilter));
        
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('Dates')->getValue('endCalendarDate');
        $select = $this->getTable()->getAppsTable()
                ->getRevenueStatisticsSelect($startDate, $endDate);
        $select->where('site_id IN (?)', $this->_siteIds);
        $site = $this->_sitesFilter->getValue();
        if (null!==$site) {
            $select->where('site_id IN (?)', $site);
        }
        $select->where('container_id IS NOT NULL')
               ->reset( Zend_Db_Select::ORDER );
               //->order('container_id');
        $grid->setSelect(
            $this->getDb(true)->select()->from(array('wrapper'=>$select))
        );

        return $grid;
    }

    public function getContainerStatsGrid($containerId)
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(false)
             ->setLimit(-1);//don't use limit
        $grid->setDefaultSort('app_id')
             ->setDefaultDir('asc')
             ->setShowTotals(false);
        $grid->addColumn('app_id', array(
                'header'=>'App Id',
                'type'=>'link',
                'links'=>'/admin/apps/app-statistics/appId/$app_id'
            ))
             ->addColumn('full_name', array('header'=>'App Name'))
             ->addColumn('impressions', array('header'=>'Impressions') )
             ->addColumn('cpm', array('header'=>'eCPM') )
             ->addColumn('publisher_earned', array('header'=>'Publisher Earnings') )
             ->addColumn('cobrand_earned', array('header'=>'Cobrand Earnings') )
             ->addColumn('expended', array('header'=>'Total Earnings') )
             ->addColumn('grossProfit', array('header'=>'Gross Profit') )
             ->addColumn('grossProfitPct', array('header'=>'Gross Profit %') )
            ;

        $grid->setFilters(array('dates'));
        
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('Dates')->getValue('endCalendarDate');
        $select = $this->getTable()->getAppsTable()
                ->getRevenueStatisticsSelect($startDate, $endDate);
        $select->where('container_id=?', $containerId)
               ->reset( Zend_Db_Select::ORDER );
               //->order('container_id');
        $grid->setSelect($select);

        return $grid;
    }
    
    public function slAdsStatsGrid()
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(false)
             ->setLimit(-1);
        $grid->setDefaultSort('fc_ad_id')
             ->setDefaultDir('ASC');
        $grid->setTemplatePart('body', 'reports/grid/sl-stats-body.phtml');
        $grid->addColumn('fc_ad_id', array('header'=>'NB AD ID') )
             ->addColumn('sl_ad_id', array('header'=>'SL AD ID'))
             ->addColumn('bid', array('header'=>'Calculated Bid'))
             ->addColumn('fc_ad_imps', array('header'=>'NB Imps'))
             ->addColumn('fc_ad_clicks', array('header'=>'NB Clicks'))
             ->addColumn('sl_ad_clicks', array('header'=>'SL Ad Clicks'))
             ->addColumn('fc_ad_ctr', array('header'=>'FC CTR(not charged)'))
             ->addColumn('sc_ad_ctr', array('header'=>'FC CTR(charged)'))
             ->addColumn('sl_expended', array('header'=>'Exp by advertiser'))
             ->addColumn('sl_orders', array('header'=>'Conversions'))
             ->addColumn('cpa', array('header'=>'CPA'))
             ->addColumn('ecpm', array('header'=>'eCPM'))                
            ;
        $grid->setFilters(array('dates'));
        
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate = $grid->getFilter('Dates')->getValue('endCalendarDate');
        
        //@TODO convert to select object 
        $sladsStatsSql = 
         "SELECT 
            `sl_traffic`.`sl_ad_id`, 
            `sl_traffic`.`fc_ad_id`, 
             SUM(sl_traffic.expended) AS `sl_expended`, 
            `fc_traffic`.`fc_ad_imps`, 
            `fc_traffic`.`fc_ad_clicks`, 
            SUM(sl_traffic.clicks) AS `sl_ad_clicks`, 
            IF(fc_traffic.fc_ad_imps,fc_traffic.fc_ad_clicks/fc_traffic.fc_ad_imps,0) AS `fc_ad_ctr`, 
            IF(fc_traffic.fc_ad_imps,SUM(sl_traffic.clicks)/fc_traffic.fc_ad_imps,0) AS `sc_ad_ctr`, 
            IF(SUM(sl_traffic.orders),SUM(sl_traffic.expended)/SUM(sl_traffic.orders),0) AS `cpa`, 
            IF(fc_traffic.fc_ad_imps,SUM(sl_traffic.expended)/fc_traffic.fc_ad_imps*1000,0) AS `ecpm`, 
            SUM(`sl_traffic`.`orders`) AS `sl_orders`,
            IF(SUM(sl_traffic.clicks), SUM(sl_traffic.expended)/SUM(sl_traffic.clicks),0) AS bid
            
        FROM `slads_traffic` AS `sl_traffic`
        LEFT JOIN (SELECT `adrev_app_traffic`.`ad_id`, `adrev_app_traffic`.`creation_date`, 
                           SUM(`adrev_app_traffic`.`impressions`) 
            AS fc_ad_imps, SUM(`adrev_app_traffic`.`clicks`) AS fc_ad_clicks FROM adrev_app_traffic 
            GROUP BY adrev_app_traffic.ad_id, adrev_app_traffic.creation_date) as fc_traffic ON 
            `fc_traffic`.`ad_id`=`sl_traffic`.`fc_ad_id` AND fc_traffic.creation_date=sl_traffic.creation_date" .
            (($startDate !== null || $endDate !== null) ? " WHERE " : "") .
            (($startDate !== null) ? " sl_traffic.creation_date>='" . $startDate . "' " : "") .  
            (($startDate !== null && $endDate !== null) ? " AND " : "") . 
            ($endDate !== null ? " sl_traffic.creation_date<= '" . $endDate  . "' " : "") . 
            " GROUP BY `sl_traffic`.`fc_ad_id`, `sl_traffic`.`sl_ad_id` ORDER BY `sl_traffic`.`fc_ad_id` ASC";
        $select = $this->getDb()->select()->from(array('result'=>new Zend_Db_Expr("($sladsStatsSql)")) );
                
        $grid->setSelect($select);                        

        return $grid;
    }
    
    public function getUsersGrid($onlyAdv=false)
    {        
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(true)
             ->setLimit(250);
        $grid->setDefaultSort('id')
             ->setDefaultDir('desc');
        $grid->setTemplatePart('body', 'reports/grid/users-body.phtml');
        $grid->addColumn('id', array('header'=>'ID') )
             ->addColumn('name', array('header'=>'Name'));
        $grid->addColumn('admin', array('header'=>'Type'));
        $grid->addColumn('site_name',array('header'=>'Site','sortable'=>false));

        $grid->addColumn('organization', array('header'=>'Organization') );
        if (!$onlyAdv) {
             $grid->addColumn('cobrand_name', array('header'=>'Cobrand') );
        };
        $grid->addColumn('email', array('header'=>'Email') )
             ->addColumn('status', array('header'=>'Status') )
             ->addColumn('emails_sent', array('header'=>'Email Sent') )
             ->addColumn('date', array('header'=>'Registered') )
             ->addColumn('today_expended', array('header'=>'Today Exp') );
        
        if (!$onlyAdv) {
            $grid->addColumn('today_earned', array('header'=>'Today Earn') );
        }
        $grid->addColumn('balance', array('header'=>'Balance') );

        $grid->setFilters(array('userName'));
        
        $userName = $grid->getFilter('UserName')->getValue();
        $grid->addFilter($this->_sitesFilter);
        $select = $this->getTable()->getUsersTable()
                ->getUsersSelect($userName);

        if ($onlyAdv) {
            $select->where('admin=?', UsersTable::ADVERTISER_ROLE);
        }
        $select->where('site_id IN(?)', $this->_siteIds);
        $site = $this->_sitesFilter->getValue();
        if (null!==$site) {
            $select->where('site_id=?', $site);
        }
        $grid->setSelect(
            $this->getDb(true)->select()->from(array('results'=>$select))
        );

        return $grid;
    }

    /**
     * @return System_Grid_DataGrid
     */
    public function getPublishersGrid()
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());        
        $grid->setSortable(true)
             ->setLimit(250);
        $grid->setDefaultSort('id')
             ->setDefaultDir('desc');
        
        $grid->setTemplatePart('body', 'reports/grid/publishers-body.phtml');        
        $grid->addColumn('id', array('header'=>'ID') )
             ->addColumn('name', array('header'=>'Name'));        
                     
        $grid->addColumn('organization', array('header'=>'Organization') )  
             ->addColumn('email', array('header'=>'Email') )
             ->addColumn('site_name',array('header'=>'Site','sortable'=>false))
             ->addColumn('deniedApps', array('header'=>'Status') )
             ->addColumn('notApprovedApps', array('header'=>'Approval Needed') )
             ->addColumn('creation_date', array('header'=>'Registered') )
             ->addColumn('balance', array('header'=>'Balance') )
             ->addColumn('actions', array('header'=>'','index'=>'id','width'=>'165',));

        $showWithoutApps = new System_Search_Filters_Checkbox();
        $showWithoutApps->setLabel('Include publishers without apps')
                        ->setDefaultValue(0);

        $grid->setFilters(array('appStatus', 'userName', $this->_sitesFilter, $showWithoutApps));

        $userName        = $grid->getFilter('UserName')->getValue();
        $appStatus       = $grid->getFilter('AppStatus')->getValue();
        $site            = $this->_sitesFilter->getValue();
        $showWithoutApps = $showWithoutApps->getValue();
        $denied          = User_Model_DbTable_AppStatuses::DENIED;
        $awaiting        = User_Model_DbTable_AppStatuses::AWAITING_APPROVAL;

        $select = $this->getDb(true)->select()
                  ->from(array('users'=>'adrev_users'),
                        array(
                            'users.*',
                            'deniedApps'      => "sum(case when apps.status={$denied} then 1 else 0 end )",
                            'notApprovedApps' => "COUNT(IF(apps.status={$awaiting},1,NULL))",
                            'date'            => 'DATE_FORMAT(`creation_date`,"%b %d %Y")',
                            'hasApps'         => 'IF(COUNT(apps.id) > 0, 1, 0)'
                        ))
                  ->join(array('sites'), 'sites.id=users.site_id', array('site_name'=>'sites.name'))
                  ->where('site_id IN (?)', $this->_siteIds)
                  ->where('users.admin = ?', UsersTable::PUBLISHER_ROLE)
                  ->group('users.id');

        if (null!==$site) {
            $select->where('site_id=?', $site);
        }

        $appsJoin = $showWithoutApps ? 'joinLeft' : 'join';
        $select->$appsJoin(array('apps'=>'adrev_app'), 'apps.user_id=users.id', array());

        $grid->setSelect($select);
        if (!empty($userName)) {
            $_searchUserName = "%{$userName}%";
            $select->where('users.id LIKE ?', $_searchUserName);
            $select->orWhere('users.name LIKE ?', $_searchUserName);
            $select->orWhere('email LIKE ?', $_searchUserName);
            $select->orWhere('organization LIKE ?', $_searchUserName);
        }        
        if (!empty($appStatus)) {            
            $select->where('apps.status=?', $appStatus);
        }
        
        return $grid;
    }

    public function getAutoBillingGrid()
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(true)
            ->setLimit(250);
        $grid->setDefaultSort('id')
            ->setDefaultDir('desc');
        $grid->addColumn('id', array(
                'header'=>'ID',
                'type'=>'link',
                'links'=>"/admin/users/view/userId/\$id/"
            ))
            ->addColumn('name', array('header'=>'Name'))
            ->addColumn('organization', array('header'=>'Organization') )
            ->addColumn('site_name',array('header'=>'Site','sortable'=>false))
            ->addColumn('email', array('header'=>'Email') )
            ->addColumn('date', array('header'=>'Registered') )
            ->addColumn('today_expended', array('header'=>'Today Exp', 'type'=>'money') )
            ->addColumn('today_earned', array('header'=>'Today Earn', 'type'=>'money') )
            ->addColumn('balance', array('header'=>'Balance', 'type'=>'money') )
            ->addColumn('edit',
                array('header'=>'Auto-Billing',
                    'type'=>'script',
                    'scriptPath'=>'reports/grid/auto-billing-column.phtml',
                    'index'=>'profile_id'
                ))
        ;
        $abActive = new System_Search_Filters_Select();
        $abActive->setOptions(array(
            '1'=>'Active',
            '2'=>'Not Active'
        ))->setLabel('AutoBilling')
          ->setDefaultValue(1);
        $abValue = $abActive->getValue();
        $grid->setFilters(array('userName', $abActive));
        $userName = $grid->getFilter('UserName')->getValue();
        $select = $this->getTable()->getUsersTable()
            ->getUsersSelect($userName);
        $select->joinLeft(array('ab'=>'user_auto_billing'),'ab.user_id=user.id')
            ->where('admin IN(1)');
        $select->where('site_id IN(?)', $this->_siteIds);
        switch($abValue) {
            case 1:
                $select->where('ab.is_active=1');
                break;
            case 2:
                $select->where('ab.is_active=0');
                break;
        }
        $grid->setSelect($select);

        return $grid;
    }


    public function getRevenueTrend($daterange, $orderName, $orderDir)
    {
        $cols = array();
        $arrDates = array();
        $db = $this->getDb(true);
        switch($daterange) {
        case "1":
            for ($i=0; $i<7; $i++) {
                $arrDates[] = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d")-$i, date("Y")));
            }
            sort($arrDates);
            foreach ($arrDates as $dt) {
                $cols[] = new Zend_Db_Expr("SUM(IF(`app_traffic`.`creation_date`='$dt', 
                        app_traffic.expended - app_traffic.earned, 0) ) AS `[pub_earned]$dt`");
                $cols[] = new Zend_Db_Expr("SUM(IF(`app_traffic`.`creation_date`='$dt', 
                        app_traffic.expended - (app_traffic.expended - app_traffic.earned), 0) ) 
                        AS `[gross_profit]$dt`");
                $cols[] = new Zend_Db_Expr("SUM(IF(`app_traffic`.`creation_date`='$dt', 
                        app_traffic.expended, 0) ) AS `[total_earned]$dt`");
            }
            break;
        case "2":
            $theDate = mktime(0, 0, 0, date('m'), date('d') - date('w'), date('Y'));
            $arrDates[] = date('Y-m-d', $theDate);
            for ($i=1; $i<4; $i++) {
                $arrDates[] = date('Y-m-d', strtotime(' - ' . ($i * 7) . ' days', $theDate));
            }
            sort($arrDates);

            for ($i=0; $i < count($arrDates); $i++) {
                switch ($i) {
                    case (count($arrDates)-1):
                        $cols[] = new Zend_Db_Expr("SUM(IF(`app_traffic`.`creation_date`>='$arrDates[$i]', 
                                app_traffic.expended - app_traffic.earned, 0) ) 
                                AS `[pub_earned]Week of $arrDates[$i]`");
                        $cols[] = new Zend_Db_Expr("SUM(IF(`app_traffic`.`creation_date`>='$arrDates[$i]', 
                                app_traffic.expended - (app_traffic.expended - app_traffic.earned), 0) ) 
                                AS `[gross_profit]Week of $arrDates[$i]`");
                        $cols[] = new Zend_Db_Expr("SUM(IF(`app_traffic`.`creation_date`>='$arrDates[$i]',
                                app_traffic.expended, 0) ) AS `[total_earned]Week of $arrDates[$i]`");
                        break;
                    default:
                        $cols[] = new Zend_Db_Expr("SUM(IF(`app_traffic`.`creation_date`>='$arrDates[$i]' 
                                and `app_traffic`.`creation_date` < '" . $arrDates[$i+1] 
                                . "', app_traffic.expended - app_traffic.earned, 0) ) 
                                AS `[pub_earned]Week of $arrDates[$i]`");
                        $cols[] = new Zend_Db_Expr("SUM(IF(`app_traffic`.`creation_date`>='$arrDates[$i]' 
                                and `app_traffic`.`creation_date` < '" . $arrDates[$i+1] 
                                . "', app_traffic.expended - (app_traffic.expended - app_traffic.earned), 0) ) 
                                AS `[gross_profit]Week of $arrDates[$i]`");
                        $cols[] = new Zend_Db_Expr("SUM(IF(`app_traffic`.`creation_date`>='$arrDates[$i]' 
                                and `app_traffic`.`creation_date` < '" . $arrDates[$i+1] 
                                . "', app_traffic.expended, 0) ) AS `[total_earned]Week of $arrDates[$i]`");
                }
            }
            
            break;
        case "3":
            for ($i=0; $i<3; $i++) {
                $arrDates[] = date('Y-m-d', mktime(0, 0, 0, date("m")-$i, 1, date("Y")) );
            }
            sort($arrDates);
            for ($i=0; $i < count($arrDates); $i++) {
                switch ($i) {
                    case (count($arrDates)-1):
                        $cols[] = new Zend_Db_Expr("SUM(IF(`app_traffic`.`creation_date`>='$arrDates[$i]', 
                                app_traffic.expended - app_traffic.earned, 0) ) AS `[pub_earned]Month of " . 
                                date('Y-m', strtotime($arrDates[$i])) . "`");
                        $cols[] = new Zend_Db_Expr("SUM(IF(`app_traffic`.`creation_date`>='$arrDates[$i]', 
                                app_traffic.expended - (app_traffic.expended - app_traffic.earned), 0) ) 
                                AS `[gross_profit]Month of  " . date('Y-m', strtotime($arrDates[$i])) . "`");
                        $cols[] = new Zend_Db_Expr("SUM(IF(`app_traffic`.`creation_date`>='$arrDates[$i]', 
                                app_traffic.expended, 0) ) AS `[total_earned]Month of  " . 
                                date('Y-m', strtotime($arrDates[$i])) . "`");
                        break;
                    default:
                        $cols[] = new Zend_Db_Expr("SUM(IF(`app_traffic`.`creation_date`>='$arrDates[$i]' 
                                and `app_traffic`.`creation_date` < '" . 
                                date('Y-m-d', strtotime($arrDates[$i+1] . '-1 days')) . "', 
                                    app_traffic.expended - app_traffic.earned, 0) ) 
                                    AS `[pub_earned]Month of " . date('Y-m', strtotime($arrDates[$i])) . "`");
                        $cols[] = new Zend_Db_Expr("SUM(IF(`app_traffic`.`creation_date`>='$arrDates[$i]' 
                                and `app_traffic`.`creation_date` < '" . 
                                date('Y-m-d', strtotime($arrDates[$i+1] . '-1 days')) . "', 
                                    app_traffic.expended - (app_traffic.expended - app_traffic.earned), 0) ) 
                                    AS `[gross_profit]Month of " . date('Y-m', strtotime($arrDates[$i]))  . "`");
                        $cols[] = new Zend_Db_Expr("SUM(IF(`app_traffic`.`creation_date`>='$arrDates[$i]' 
                                and `app_traffic`.`creation_date` < '" . 
                                date('Y-m-d', strtotime($arrDates[$i+1] . '-1 days')) . "', 
                                    app_traffic.expended, 0) ) AS `[total_earned]Month of " . 
                                date('Y-m', strtotime($arrDates[$i]))  . "`");
                }
            }
            break;
        }
        
        $sql = $db->select()
            ->from(array('apps'=>'adrev_app'), array('App Id' => 'id',
                'App Name'=>'full_name'))
            ->joinInner(array('users'=>'adrev_users'), 'users.id=apps.user_id', array('User Name'=>'name'))
            ->joinLeft(array('app_traffic'=>'adrev_app_zone'), 'app_traffic.app_id=apps.id', $cols)
            ->where(new Zend_Db_Expr("`app_traffic`.`creation_date` >= '$arrDates[0]'"))
            ->group('apps.full_name')
            ->order("{$orderName} {$orderDir}");            
        return $db->fetchAssoc($sql);
    }

    /**
     * @param  App_Model_Rtb_Bidder $rtbBidder
     * @return System_Grid_DataGrid
     */
    public function getRtbBidderReportGrid(App_Model_Rtb_Bidder $rtbBidder)
    {
        $grid = new System_Grid_DataGrid();

        $grid->setSortable(true)
             ->setLimit(-1); // do not paginate
        $grid->setDefaultSort('revenue')
             ->setDefaultDir('desc')
             ->setShowTotals(false);

        $grid->addColumn('exchange_name',           array('header' => 'Exchange'));
        $grid->addCOlumn('campaign_id',             array(
            'header' => 'Id<br /><span class="small-text">Campaign</span>',
            'type' => 'link',
            'links' => App_Model_Links::rtbCampaignEditGrid()
        ));
        $grid->addColumn('campaign_name',           array('header' => 'Name<br /><span class="small-text">Campaign</span>'));
        $grid->addColumn('container_id',            array('header' => 'Id<br /><span class="small-text">Container</span>'));
        $grid->addColumn('container_name',          array('header' => 'Name<br /><span class="small-text">Container</span>'));
        $grid->addColumn('container_size',          array('header' => 'Size'));
        $grid->addColumn('container_ecpm',          array(
            'header' => App_Model_Helper_Adblade::hoverGridHeader('eCPM<br /><span class="small-text">Container</span>', 'publisher earnings'),
            'align' => 'right',
            'type' => 'money'
        ));
        $grid->addColumn('exchange_spent',          array(
            'header'=> App_Model_Helper_Adblade::hoverGridHeader('Exchange Spent', 'Publisher Earnings'),
            'align' => 'right',
            'type'  => 'money'
        ));
        $grid->addColumn('gross_revenue',           array(
            'header' => App_Model_Helper_Adblade::hoverGridHeader('Gross Revenue','Total Earnings'),
            'align' => 'right',
            'type' => 'money'
        ));
        $grid->addColumn('net_revenue',             array(
            'header' => App_Model_Helper_Adblade::hoverGridHeader('Net Revenue','Gross Profit'),
            'align' => 'right',
            'type' => 'money'
        ));
        $grid->addColumn('bids_requested',          array(
            'header' => App_Model_Helper_Adblade::hoverGridHeader('Bid Requests', 'time the campaign was send in response to bid request')
        ));
        $grid->addColumn('bids_won',                array(
            'header' => App_Model_Helper_Adblade::hoverGridHeader('Bids Won', 'reported by Exchange'),
        ));
        $grid->addColumn('container_impressions',   array(
            'header' => App_Model_Helper_Adblade::hoverGridHeader('Imps', 'Tag impressions, should match Bids Won'),
        ));
        $grid->addColumn('bids_no',                 array('header' => 'No Bids'));
        $grid->addColumn('bids_winning',            array(
            'header' => App_Model_Helper_Adblade::hoverGridHeader('Avg. Winning Bids','reported by Exchange'),
            'align' => 'right',
            'type' => 'money'
        ));
        $grid->addColumn('campaign_status_id',      array('header' => 'Status<br /><span class="small-text">Campaign</span>',
                                                          'sortable' => false,
                                                          'type'   =>'script',
                                                          'index'  =>'campaign_status_id',
                                                          'scriptPath'=>'reports/grid/rtb-bidder-report-campaign-status.phtml'));

        $showDeletedFilter = new System_Search_Filters_Checkbox();
        $showDeletedFilter->setLabel('Show deleted');

        $grid->setFilters(array('dates', $showDeletedFilter));

        $startDate   = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate     = $grid->getFilter('Dates')->getValue('endCalendarDate');
        $showDeleted = $showDeletedFilter->getValue();

        $stats            = array();
        $containers       = $rtbBidder->getContainersStats($startDate, $endDate);
        $rtbCampaignTable = App_Model_Tables::getRtbCampaignTable();
        $appsTable        = App_Model_Tables::getAppsTable();

        /**
         * As long as we use containers with RTB campaigns exclusively, i.e.
         * one container can be assigned into only one RTB campaign, it's safe
         * to iterate through containers as through campaigns.
         */
        foreach ($containers as $i => $container) {

            // Grab required stuff for RTB campaign
            $campaign = $rtbCampaignTable->getCampaignByContainerId($container['cid'], $showDeleted);

            // Pile them up together
            if ($campaign) {

                // Calculate additional stats per container
                $contStats = $this->getContainerStatsData($container['cid'], $startDate, $endDate);

                $stats[$i]['exchange_name']        = $campaign['exchange_name'];
                $stats[$i]['campaign_id']          = $campaign['id'];
                $stats[$i]['campaign_name']        = $campaign['name'];
                $stats[$i]['campaign_status_id']   = $campaign['status_id'];
                $stats[$i]['campaign_status_name'] = $rtbCampaignTable->getStatusName($campaign['status_id']);
                $stats[$i]['container_name']       = $campaign['container_name'];
                $stats[$i]['container_size']       = $campaign['container_size'];
                $stats[$i]['container_id']         = $container['cid'];
                $stats[$i]['container_ecpm']       = $contStats['cpm'];
                $stats[$i]['exchange_spent']       = (float)$contStats['publisher_earned'];
                $stats[$i]['gross_revenue']        = $contStats['expended'];
                $stats[$i]['container_impressions']= $contStats['impressions'];
                $stats[$i]['net_revenue']          = $contStats['earned'];
                $stats[$i]['bids_requested']       = $container['bidsTotal'];
                $stats[$i]['bids_won']             = $container['bidsWon'];
                $stats[$i]['bids_no']              = $container['bidsNo'];
                $stats[$i]['bids_winning']         = $container['winPriceAvg'];
            }
        }

        return $grid->setDataSource(
            new System_Grid_DataGrid_DataSource_Array(array_values($stats))
        );
    }

    /**
     * @param  App_Model_RtbEx $ex
     * @return System_Grid_DataGrid
     */
    public function getRtbExchangeStatsGrid(App_Model_RtbEx $ex)
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(true)
             ->setLimit(-1);//don't use limit
        $grid->setDefaultSort('revenue')
             ->setDefaultDir('desc')
             ->setShowTotals(false);
        $grid
             ->addColumn('user_name', array(
                'header'=>'Publisher',
                'type'=>'link','links'=>"/admin/users/view/userId/\$user_id"
             ))
             ->addColumn('container_name', array(
                 'header'=>'Container Name',
                 'type'=>'link','links'=>"/admin/reports/rtb-exchange-container/id/\$id"
             ))
             ->addColumn('id', array(
                 'header'=>'Container Id','format'=>'$id',
                 'type'=>'link',
                 'links'=>"/admin/reports/rtb-exchange-container/id/\$id"))
             ->addColumn('size', array('header'=>'Size') )
             ->addColumn('imps', array('header'=>'Impressions','type'=>'number') )
             ->addColumn('ecpm', array('header'=>'eCPM','type'=>'money') )
             ->addColumn('total_earn', array('header'=>'Total Revenue','type'=>'money') )
             ->addColumn('pub_earn', array('header'=>'Publisher Earnings','type'=>'money') )
             ->addColumn('revenue', array('header'=>'Net Revenue','type'=>'money') )
             ->addColumn('winRate', array('header'=>'Win Rate','type'=>'percent') )
             ->addColumn('bidsTotal', array('header'=>'Bid requests','type'=>'number') )
             ->addColumn('bidsValid', array('header'=>'Valid Bids','type'=>'number') )
             ->addColumn('bidsNone', array('header'=>'No Bid','type'=>'number') )
             ->addColumn('bidsBlocked', array('header'=>'Blocked','type'=>'number') )
             ->addColumn('bidsTimeout', array('header'=>'Timeout','type'=>'number') )
            ;
        $grid->setFilters(array('dates'));
        
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate = $grid->getFilter('Dates')->getValue('endCalendarDate');
        $data = $ex->getContainersStats($startDate, $endDate);

        foreach ($data as $_key=>$item) {
            $container = $this->getTable()->getAppContainersTable()->getContainer($item['id']);
            if ($container) {
                $publisher = $this->getTable()->getUsersTable()->getById($container->publisher_id);
                $data[$_key]['user_name'] = $publisher->name;
                $data[$_key]['container_name'] = $container->name;
                $data[$_key]['user_id'] = $publisher->id;
            } else {
                $data[$_key]['user_name'] = '';
                $data[$_key]['container_name'] = 'N/A';
                $data[$_key]['user_id'] = '';
            }
            $data[$_key]['winRate'] = $data[$_key]['winRate']*100;
        }
        $grid->setDataSource(
            new System_Grid_DataGrid_DataSource_Array($data)
        );
        
        return $grid;
    }
    
    public function getRtxExtStatsContainerGrid(App_Model_RtbEx $ex, $id)
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(true)
             ->setLimit(-1);//don't use limit
        $grid->setDefaultSort('revenue')
             ->setDefaultDir('desc')
             ->setShowTotals(false);
        $grid
             ->addColumn('bidderId', array('header'=>'Buyer Id'))
             //->addColumn('size', array('header'=>'Size') )
             ->addColumn('imps', array('header'=>'Impressions','type'=>'number') )
             ->addColumn('ecpm', array('header'=>'eCPM','type'=>'money') )
             ->addColumn('total_earn', array('header'=>'Total Revenue','type'=>'money') )
             ->addColumn('pub_earn', array('header'=>'Publisher Earnings','type'=>'money') )
             ->addColumn('revenue', array('header'=>'Net Revenue','type'=>'money') )
             ->addColumn('winRate', array('header'=>'Win Rate','type'=>'percent') )
             ->addColumn('bidsTotal', array('header'=>'Bid requests','type'=>'number') )
             ->addColumn('bidsValid', array('header'=>'Valid Bids','type'=>'number') )
             ->addColumn('bidsNone', array('header'=>'No Bid','type'=>'number') )
             ->addColumn('bidsBlocked', array('header'=>'Blocked','type'=>'number') )
             ->addColumn('bidsTimeout', array('header'=>'Timeout','type'=>'number') )
            ;
        $grid->setFilters(array('dates'));
        
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate = $grid->getFilter('Dates')->getValue('endCalendarDate');
        $data = $ex->getContainerStats($id, $startDate, $endDate);
        foreach ($data as $_key=>$item) {
                $data[$_key]['winRate'] = $data[$_key]['winRate']*100;
        }        
        $grid->setDataSource(
            new System_Grid_DataGrid_DataSource_Array($data)
        );
        
        return $grid;
    }
    
    /**
     * Returns grid object
     * 
     * @param  int $publisherType Which publisher type to fetch data for
     * @return System_Grid_DataGrid
     */
    public function getRevStatsByPublisherType($publisherType = User_Model_DbTable_PublisherTypes::FIXED)
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setFilters(array('Dates', 'Select', 'AccountManager', 'bizDev'));
        
        // Set the date picker drop down to the last 30 days
        $grid->getFilter('Dates')->setDefaultDates(
            date('Y-m-d', time() - 86400 * 30), date('Y-m-d', time() - 86400)
        );
        
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate   = $grid->getFilter('Dates')->getValue('endCalendarDate');
        $userId    = $grid->getFilter('Select')->getValue();
        $bizDev    = $grid->getFilter('BizDev')->getValue();
        $accMgr    = $grid->getFilter('AccountManager')->getValue();
        
        // Set charts
        $width  = 800;
        $height = 200;
        
        if ($publisherType == User_Model_DbTable_PublisherTypes::FIXED) {
            $colHeader = 'Impression volume and eCPM';
            $link = '/admin/ofcharts/biz-dev-imps-ecpm?'
                  . "startDate={$startDate}&endDate={$endDate}&userId=\$user_id&"
                  . "width={$width}&height={$height}&isGrossEcpm=1";
        } else {
            $colHeader = 'Impression volume and Gross Profit';
            $link = '/admin/ofcharts/biz-dev-imps-gross-profit?'
                  . "startDate={$startDate}&endDate={$endDate}&userId=\$user_id&"
                  . "width={$width}&height={$height}";
        }
        
        $iframe = '<iframe scrolling="no"'
                . 'style="width:' . $width
                . 'px;height:' . ($height * 2)
                . 'px;border:0" src="' . $link . '">'
                . '</iframe>';
        
        // Fetch data
        $userFields = array(
            'user_id'        => 'id',
            'user_name'      => 'name',
            'expended'       => 'IFNULL(SUM(traff.expended), 0)',
            'adblade_earned' => 'IFNULL(SUM(traff.earned), 0)',
            'organization'
        );
        
        $traffCond = 'traff.app_id = app.id '
                   . 'AND traff.creation_date '
                   . "BETWEEN '{$startDate}' AND '{$endDate}'";
        
        $db = $this->getDb(true);
        $select = $db->select()
                     ->from(array('u' => 'adrev_users'), $userFields)
                     // user must be a publisher
                     ->join(array('pub' => 'user_publisher'), 'pub.user_id = u.id', array())
                     // there might be no stats at all so use joinLeft
                     ->joinLeft(array('app' => 'adrev_app'), 'app.user_id = u.id', array())
                     ->joinLeft(array('traff' => 'adrev_app_zone'), $traffCond, array())
                     ->where('pub.partner_type_id = ?', $publisherType)
                     ->group('u.id');

        // Apply filters
        if (! is_null($userId)) {
            $select->where('u.id = ?', $userId);
        } else if (! is_null($bizDev)) {
            $select->where('u.biz_dev_user_id = ?', $bizDev);
        } else if (! is_null($accMgr)) {
            $select->where('u.account_manager_user_id = ?', $accMgr);
        }
        
        $selectFinal = $db->select()->from(array('results' => $select));
        
        // Set grid options and columns
        $grid->setSelect($selectFinal)
             ->setSortable(true)
             ->setDefaultSort('expended')
             ->setDefaultDir('desc')
             ->setLimit(10);
        
        $grid->addColumn('organization', array(
                  'header'=>'Partner Name - ID',
                  'type'=>'link',
                  'width'=>200,
                  'format'=>'$organization - $user_id',
                  'links'=>"/admin/users/view/userId/\$user_id/"))
             ->addColumn('imps', array(
                 'header'=>$colHeader,
                 'index'=>'user_id',
                 'format'=>$iframe,
                 'sortable'=>false,
             ))
             ->addColumn('expended', array(
                 'header'=>'Gross Revenue',
                 'type'=>'money',
                 'width'=>80
             ))
             ->addColumn('adblade_earned', array(
                 'header'=>'Gross Profit',
                 'type'=>'money',
                 'width'=>80
             ));
        
        // Set custom data for Select grid filter
        $users   = array();
        $columns = $grid->getIterator();
        foreach ($columns as $item) {
            $users[$item['user_id']] = "{$item['organization']} - {$item['user_id']}";
        }
        asort($users);
        $grid->getFilter('Select')->setOptions($users)->setLabel('User');
        
        return $grid;
    }
    
    public function getAdvertiserReferrers()
    {
    	$advertiserReferrersSelect = $this->getDb()->select()
            ->from(array('app' => 'adrev_app'), array('app.app_name as name', 'app.id as id'))
            ->join(array('advOpt' => 'user_advertiser'), 'advOpt.ref_app_id = app.id', array())
            ->group('advOpt.ref_app_id')
            ->where('advOpt.ref_app_id <> 0');
        $data = $this->getDb()->fetchAll($advertiserReferrersSelect);
        return $data;
    }

    /**
     * @param  null|int $paymentStatus
     * @param  bool     $returnArchivedOnly
     * @return System_Grid_DataGrid
     */
    public function getPaymentWiresGrid($paymentStatus = null, $returnArchivedOnly = false)
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());

        $grid->setSortable(false)
             ->setLimit(-1);//don't use limit

        $grid->setDefaultSort('date_received')
             ->setDefaultDir('desc')
             ->setShowTotals(false);

        $grid->addColumn('id', array('header'=>'Id'))
             ->addColumn('site_name',array('header'=>'Site','sortable'=>false))
             ->addColumn('status', array('header'=>'Status'))
             ->addColumn('advertiser_id', array(
                 'header'=>'Advertiser','format'=>'$advertiser_id - $advertiser_name',
                 'type'=>'link',
                 'links'=>"/admin/users/view/userId/\$advertiser_id"))
             ->addColumn('amount_sent', array('header'=>'Amount Sent','align'=>'right','type'=>'money') )
             ->addColumn('date_sent', array('header'=>'Date Sent','align'=>'center') )
             ->addColumn('amount_received', array('header'=>'Amount Received','type'=>'money') )
             ->addColumn('date_received', array('header'=>'Date Received','align'=>'center') )
             ->addColumn('date_funds_applied', array('header'=>'Date Funds Applied','align'=>'center') )
             ->addColumn('notes', array('header'=>'Notes') )
             ->addColumn('edit',
                        array('header'=>'Actions',
                              'type'=>'script',
                              'scriptPath'=>'reports/grid/wire-transfers-grid-actions.phtml',
                              'index'=>'id'
                        ));

        $select = $this->getDb()->select()
                ->from(array('wires'=>'payment_wires'), array(
                    'wires.*',
                    'status'=>'IF(status_id=1,"Received",IF(status_id=2,"Pending","Undefined"))',
                ))
                ->join(array('user'=>'adrev_users'), 'user.id=wires.advertiser_id', array(
                   'advertiser_name'=>'name'
                ))
                ->join(array('sites'), 'sites.id=user.site_id', array('site_name'=>'sites.name'))
                ->joinLeft(array('payment'=>'adrev_payments'), 'payment.id=wires.payment_id', array(
                    'date_funds_applied'=>'creation_date'
                ))
                ->where('user.site_id IN (?)', $this->_siteIds);

        if (!is_null($paymentStatus) && is_numeric($paymentStatus)) {
            $select->where('status_id = ?', $paymentStatus);
            // Also do not show wire if it's archived no matter which status it has
            if (!$returnArchivedOnly) {
                $select->where('is_archived = ?', 0);
            }
        }

        if ($returnArchivedOnly) {
            $select->where('is_archived = ?', 1);
        }

        $grid->setSelect($select);

        return $grid;
    }

    public function getCreditAdvancesBillingGrid()
    {
        $grid = new System_Grid_DataGrid();

        $grid->setFilters(array('dates'));
        $grid->getFilter('Dates')->setDefault(System_Search_Date::LAST_MONTH);

        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('Dates')->getValue('endCalendarDate');

        $advertisers = $this->_getCreditAdvancesReport($startDate, $endDate);
        $data = array();
        $i=1;
        foreach ($advertisers as $key=>$row) {
            //Show only users with bill>0
            if ( $row['expendedCreditAdvanceAfterPeriod']>0 ) {
               $newRow = $row + array('rowNumber'=>$i++);;
               $newRow['rate'] = !empty($row['sumClicks'])?
                    number_format($row['sumExpended']/$row['sumClicks'], 2):'0.00';
               $data[] = $newRow;
            }
        }

        $grid->setDataSource(new System_Grid_DataGrid_DataSource_Array($data));


        $grid->setSortable(false)
             ->setLimit(999999999);
        $grid->setDefaultSort('rowNumber')
             ->setDefaultDir("asc")
             ->setShowTotals(false);
        $grid
             ->addColumn('rowNumber', array('header'=>'#'))
             ->addColumn('organization', array('header'=>'Company Name'))
             ->addColumn('site_name',array('header'=>'Site','sortable'=>false))
             ->addColumn('name',
                        array('header'=>'Advertiser Name',
                              'type'=>'link',
                              'links'=>"/admin/users/view/userId/\$id"))
             
             ->addColumn('login',
                        array('header'=>'Login',
                              'index'=>'email'))
             ->addColumn('campaign',
                     array('header'=>'',
                           'type'=>'money'))
              ->addColumn('sumExpended',
                     array('header'=>'Spend',
                           'type'=>'money'))
             ->addColumn('sumClicks',
                     array('header'=>'Clicks',
                           'type'=>'number'))
             ->addColumn('rate', array('header'=>'Rate'))
             ->addColumn('campaign', array('header'=>'Campaign'))                
             ->addColumn('email',
                    array('header'=>'Email Contact',
                          'type'=>'link',
                          'links'=>'mailto:$email'))

             ->addColumn('expendedCreditAdvanceAfterPeriod',
                     array('header'=>'Bill',
                           'type'=>'money'))
                ;

        return $grid;
    }

    public function getCreditCardPendingChargesGrid()
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(false)
             ->setLimit(100);
        $grid->setDefaultSort('creation_datetime')
             ->setDefaultDir('desc')
             ->setShowTotals(false);
        
        $grid->addColumn('userId', array('header'=>'User ID'))
             ->addColumn('userName',
                        array('header'=>'User Name',
                              'type'=>'link',
                              'links'=>"/admin/users/view/userId/\$userId"))
             ->addColumn('typeName', array('header'=>'Type','format'=>'$typeName - $typeDescription'))
             ->addColumn('creation_datetime', array('header'=>'Date/Time'))
             ->addColumn('amount', array('header'=>'Amount','type'=>'money'))
             ->addColumn('txid', array(
                 'header'=>'Trans ID',
                 'align'=>'right',
                 'type'=>'link',
                 'class'=>'transaction-id',
                 'links'=>"/admin/payments/trans-details/id/\$txid"
             ))
             ->addColumn('--',
                        array('header'=>'Commit',
                              'type'=>'action',
                              'width'=>18,
                              'index'=>'txid',
                              'actions'=>array(
                                  'url'=>'/admin/payments/commit-trans/id/$id',
                                  'image'=>'/images/design/icons/save.png',
                                  'caption'=>'commit',
                                  'confirm'=>'Are you sure want to commit the transaction [$txid]?')
                        ))
             ->addColumn('-',
                        array('header'=>'Del',
                              'type'=>'action',
                              'width'=>18,
                              'index'=>'txid',
                              'actions'=>array(
                                  'url'=>'/admin/payments/cancel-trans/id/$id',
                                  'image'=>'/images/design/icons/delete.png',
                                  'caption'=>'delete',
                                  'confirm'=>'Are you sure want to delete the transaction [$txid]?')
                        ))
             
            ;

        $grid->setFilters(array('dates', $this->_sitesFilter));
        $grid->getFilter('Dates')->setDefault(System_Search_Date::ALL_TIME);
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('Dates')->getValue('endCalendarDate');
        $select = $this->getTable()->getTokensTable()
                ->getPedingCCChargesSelect($startDate, $endDate);
        $select->where('site_id IN (?)', $this->_siteIds);
        $site = $this->_sitesFilter->getValue();
        if (!empty($site)) {
            $select->where('site_id IN (?)', $site);
        }
        $grid->setSelect($select);

        return $grid;
    }
    
    public function getCreditCardChargesGrid()
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(false)
             ->setLimit(100);
        $grid->setDefaultSort('creation_datetime')
             ->setDefaultDir('desc')
             ->setShowTotals(false);
        $grid->addColumn('userName',
                        array('header'=>'User Name',
                              'type'=>'link',
                              'links'=>"/admin/users/view/userId/\$userId"))
             ->addColumn('site_name',array('header'=>'Site'))
             ->addColumn('typeName', array('header'=>'Type','format'=>'$typeName - $typeDescription'))
             ->addColumn('x_last_name', array('header'=>'Last Name'))
             ->addColumn('x_first_name', array('header'=>'First Name'))
             ->addColumn('x_phone', array('header'=>'Phone'))
             ->addColumn('x_company', array('header'=>'Organization'))
             ->addColumn('creation_datetime', array('header'=>'Date/Time'))
             ->addColumn('x_amount', array('header'=>'Amount','type'=>'money'))
             ->addColumn('x_city', array('header'=>'City'))
             ->addColumn('x_address', array('header'=>'Address'))
             ->addColumn('x_state', array('header'=>'State'))
             ->addColumn('x_zip', array('header'=>'Zip Code'))
             ->addColumn('x_country', array('header'=>'Country'))             
            ;

        $grid->setFilters(array('dates', $this->_sitesFilter));
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('Dates')->getValue('endCalendarDate');
        $select = $this->getTable()->getAuthorizeLogTable()
                ->getSelect($startDate, $endDate);
        $select->where('site_id IN (?)', $this->_siteIds);
        $site = $this->_sitesFilter->getValue();
        if ($site) {
            $select->where('site_id IN (?)', $site);
        }
        $grid->setSelect($select);

        return $grid;
    }

    public function getPaymentsGrid()
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(false)
             ->setLimit(250);
        $grid->setDefaultSort('creation_datetime')
             ->setDefaultDir('desc')
             ->setShowTotals(false);
        $grid->addColumn('id', array('header'=>'ID'))
             ->addColumn('userName',
                        array('header'=>'User Name',
                              'type'=>'link',
                              'links'=>"/admin/users/view/userId/\$userid"))
             ->addColumn('site_name',array('header'=>'Site','sortable'=>false))
             ->addColumn('organization', array('header'=>'Organization<br /><span class="small_txt">user\'s profile'))
             ->addColumn('company', array(
                 'header'=>'Company<br /><span class="small_txt">cc payment</span>'))
             ->addColumn('typeName', array('header'=>'Payment Type',
                 'format'=>'$typeName - $typeDescription'))
                
             ->addColumn('description', array('header'=>'Description'))
             ->addColumn('amount', array('header'=>'Amount','type'=>'money'))
             ->addColumn('creation_date', array('header'=>'Date'))             
            ;

        $grid->setFilters(array('paymentTypes','dates', $this->_sitesFilter));
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('Dates')->getValue('endCalendarDate');
        $paymentTypes = $grid->getFilter('PaymentTypes')->getValue();
        $site = $this->_sitesFilter->getValue();
        $select = $this->getTable()->getPaymentsTable()->getPaymentsSelect(
                            $startDate, $endDate, $paymentTypes, $site
                  );
        $select->where('site_id IN (?)', $this->_siteIds);
        $grid->setSelect($select);

        return $grid;
    }
    
    /**
     * Get grid with all managers or for particular manager.
     * If for particular, manager's filter is not necessary
     * 
     * @param int|null $managerId
     * @return System_Grid_DataGrid 
     */
    public function getPublisherAccountManagerGrid($managerId=null)
    {
        
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(false)
             ->setLimit(-1);
        $grid->setDefaultSort('id')
             ->setDefaultDir('asc')
             ->setShowTotals(true);
        $grid
            ->addColumn('publisher_id', array('header'=>'ID','type'=>'link',
                              'links'=>"/admin/users/view/userId/\$publisher_id"))
            ->addColumn('organization', array('header'=>'Organization Name'))
            ->addColumn('publisher_name', array('header'=>'Contact Name'))
            ->addColumn('gross_adblade_earnings', array(
                'header'=>'Gross Adblade Earnings',
                'type'=>'money',
                'showTotals'=>true
            ))
            ->addColumn('adblade_earned', array(
                'header'=>'Adblade Profit',
                'type'=>'money',
                'showTotals'=>true
            ))
            ->addColumn('earned_from_adblade', array(
                'header'=>'Cobrand Network Earnings',
                'type'=>'money',
                'showTotals'=>true
            ))
            ->addColumn('publisher_earned', array(
                'header'=>'Publisher Earned',
                'type'=>'money',
                'showTotals'=>true
            ))                            
            ;
        
        $filters = array('dates');
        if (null===$managerId) {
            $filters[] = 'publisherManager';
        }
        
        $grid->setFilters($filters);
        if (null===$managerId) {
            $managerId = $grid->getFilter('PublisherManager')->getValue();
        }
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('Dates')->getValue('endCalendarDate');
        $select = $this->_getPublisherManagerStatsSelect($startDate, $endDate, $managerId);                
        $grid->setSelect($select);
        
        $filename = "publishers_stats_{$startDate}_{$endDate}";
        $grid->setExportable(true)
             ->setExportFilename($filename)
             ->setExportIncludeFields(array(
                 'publisher_name','earned_from_adblade','publisher_earned',
                 'gross_adblade_earnings'
             ));
        
        
        return $grid;
    }
    
    protected function _getCreditAdvancesReport($startDate=null,$endDate=null,$site=null)
    {

        $this->getTable()->getPaymentsTable();
        //10 minutes
        set_time_limit(600);
        $db = $this->getDb();
        $model = new App_Model_Reports_UserStats($db, true);
        return $model->getCreditAdvancesData($startDate, $endDate, $site);
    }

    public function getCreditAdvancesGrid()
    {
        $db = $this->getDb(true);
        //include table to load constants
        $this->getTable()->getPaymentsTable();

        $paymentsIdList=implode(',', array(PaymentsTable::PAYPAL_PT,
                                          PaymentsTable::AUTHORIZE_CC_PT,
                                          PaymentsTable::AUTHORIZE_AUTO_BILLING_PT,
                                          PaymentsTable::BANK_WIRE_PT,
                                          PaymentsTable::CASH_PT));
        $select = $db->select()
            ->from(array('p'=>'adrev_payments'), array(
                'add_date'=>'DATE_FORMAT(p.creation_date, "%M %Y")',
                'user_id'=>'u.id',
                'p.id','u.name','u.email',
                'sum_advance'=>new Zend_Db_Expr('IFNULL((SELECT SUM(amount) FROM adrev_payments p1
                        WHERE userid = p.userid
                              AND DATE_FORMAT(creation_date, "%M %Y") = add_date
                              AND type_id='.PaymentsTable::ADMIN_CREDIT_PT.'),0)'),
                'sum_payment'=>new Zend_Db_Expr('IFNULL((SELECT SUM(amount) FROM adrev_payments p2
                        WHERE userid = p.userid
                              AND DATE_FORMAT(creation_date, "%M %Y") = add_date
                              AND type_id IN ('.$paymentsIdList.') AND amount >0),0)'),
            ))
            ->join(array('u'=>'adrev_users'), 'u.id=p.userid', array())
            ->join(array('sites'), 'sites.id=u.site_id', array('site_name'=>'sites.name'))
            ->where(new Zend_Db_Expr('EXISTS (SELECT id FROM adrev_payments p1
                            WHERE userid = p.userid AND type_id='.PaymentsTable::ADMIN_CREDIT_PT.' and p1.amount > 0)'))
            ->group('u.id')
            ->group('add_date')
            ->order('p.creation_date desc')
            ;

        $data = $db->fetchAll($select);
    	/*$sql='SELECT DATE_FORMAT(p.creation_date, "%M %Y") AS add_date, u.id as user_id,p.id, u.name, u.email,

    	           IFNULL((SELECT SUM(amount) FROM adrev_payments p1
                        WHERE userid = p.userid
                              AND DATE_FORMAT(creation_date, "%M %Y") = add_date
                              AND type_id='.PaymentsTable::ADMIN_CREDIT_PT.'),0) AS sum_advance,

                   IFNULL((SELECT SUM(amount) FROM adrev_payments p2
                        WHERE userid = p.userid
                              AND DATE_FORMAT(creation_date, "%M %Y") = add_date
                              AND type_id IN ('.$paymentsIdList.') AND amount >0),0) AS sum_payment

                    FROM adrev_payments AS p
                    INNER JOIN adrev_users AS u on u.id = p.userid
                    WHERE EXISTS (SELECT id FROM adrev_payments p1
                            WHERE userid = p.userid AND type_id='.PaymentsTable::ADMIN_CREDIT_PT.' and p1.amount > 0)
                    GROUP BY u.id, add_date
                    ORDER BY p.creation_date DESC';
        $data = $db->fetchAll($sql);*/
        
        $i=0;
        foreach ($data as &$row) {
            $row['rowNumber']=++$i;
        }

        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_Array($data));

        $grid->setSortable(false)
             ->setLimit(999999999);
        $grid->setDefaultSort('rowNumber')
             ->setDefaultDir("asc")
             ->setShowTotals(false);
        $grid
             ->addColumn('rowNumber', array('header'=>'#'))
             ->addColumn('name',
                        array('header'=>'Advertiser',
                              'type'=>'link',
                              'links'=>"/admin/users/view/userId/\$id"))
            ->addColumn('site_name',array('header'=>'Site'))
             ->addColumn('sum_advance', array('header'=>'Credit Advance','type'=>'money'))
             ->addColumn('sum_payment', array('header'=>'Paid','type'=>'money'))
             ->addColumn('add_date', array('header'=>'Month','type'=>'text'))
                ;
        return $grid;
    }    

    /**
     * data source for Impression Referrals report
     * 
     * @author RT
     * @return System_Grid_DataGrid
     */
    public function getImpressionRefGrid($appId=null)
    {
        $grid = new System_Grid_DataGrid();

        $grid->setFilters(array('Dates','AppId'));
        $grid->getFilter('Dates')->setDefault(System_Search_Date::TODAY);

        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('Dates')->getValue('endCalendarDate');

        if ($appId === null || (int)$appId == 0) {
            $appId = $grid->getFilter('AppId')->getValue();
        }
        
        $data = array();
        
        if ((int)$appId !== 0) {
            $data = $this->getImpressionRefData($appId, $startDate, $endDate);
            $appTable = $this->getTable()->getAppsTable();
            $app = array();
            if ($appId!==null) {
                $app[$appId] = $appTable->getApp($appId);
            }
        } 
        
        //$_totalBannerImps = 0;
        //$_totalAdImps = 0;
        foreach ($data as &$row) {
            $rowAppId = (int)$row['app_id'];
            if (!isset($app[$rowAppId])) {
                $app[$rowAppId] = $appTable->getApp($rowAppId);
            }
            $row['app_name'] = !empty($app[$rowAppId]['app_name'])?
                $app[$rowAppId]['app_name']:"";                        
            //$_totalAdImps+=$row['ad_impressions'];
            //$_totalBannerImps+=$row['banner_impressions'];
        }

        $grid->setDataSource(new System_Grid_DataGrid_DataSource_Array($data));
        $grid->setSortable(true)
             ->setLimit(100);
        $grid->setDefaultSort('banner_impressions')
             ->setDefaultDir("desc")
             ->setShowTotals(true);
        $grid->addColumn('creation_date', array('header'=>'Creation Date'))
             ->addColumn('app_id',
                        array('header'=>'App ID',
                              'type'=>'link',
                              'links'=>"/admin/apps/app-statistics/appId/\$app_id"))
             ->addColumn('app_name', array('header'=>'App Name'))
             ->addColumn('referer', array('header'=>'Referrer URL'))
             ->addColumn('banner_impressions', array(
                 'header'=>
                 'Banner Impressions',
                 'type'=>'number',
                 'showTotals'=>true
             ))
             ->addColumn('url_ecpm', array(
             	'header'=>'URL eCPM',
             	'type'=>'money',
             	'showTotals'=>false
               ))
            ->addColumn('clicks', array(
                'header'=>'Clicks',
                'type'=>'number',
                'showTotals'=>true
            ));
        $fileName = "impression_ref_report_{$startDate}-{$endDate}_app{$appId}";
        $grid->setExportable(true)->setExportAdapter(
            array(
                'name'=>'csv',
                'options'=>array('fileName'=>$fileName),
                'exportUrlParams' => array('pages' => 'all')
            )
        );
        $grid->setExportIncludeFields(array(
            'creation_date','app_id','app_name','referer',
            'ad_impressions','banner_impressions','url_ecpm','clicks'
        ));

        return $grid;
        
    }
    
    
    /**
     * get data source for Impression Referrals report
     * 
     * @author RT
     * @param $startDate
     * @param $endDate
     * @return array
     */
    public function getImpressionRefData($appId, $startDate, $endDate)
    {
        $data = $this->getStatsApi()->getUrlImpressionRef($appId, $startDate, $endDate);
        return $data;

        /*$db = $this->getMongoDb();
        $map = new MongoCode('function() {
            emit({appId:this._id.appId,creationDate:this._id.date,url:this.url},
                {bannerImps:this.bimps,adImps:this.aimps,adExp:this.aexp,clicks:this.clicks});
        }');
        $reduce = new MongoCode("function(k, vals) { 
            var bimps = aimps = clicks = 0 ;
			var aexp = 0.000;
            vals.forEach(function(v){bimps+=v.bannerImps;aimps+=v.adImps;aexp+=v.aexp;clicks+=v.clicks});
            return {bannerImps:bimps,adImps:aimps,adExp:aexp,clicks:clicks}; }
        ");
        $_query = array();
        if (!empty($startDate) && !empty($endDate)) {
            $_mStartId = (int)date('Ymd', strtotime(($startDate.' 00:00:00')));
            $_mEndId = (int)date('Ymd',strtotime($endDate.' 23:59:59'));        
            $_query['_id.date'] = array('$gte'=>$_mStartId,'$lte'=>$_mEndId);
        }

        $_query['_id.appId'] = (int)$appId;
        
        $_tmpCollection = 'tmp_bannerImpsRef';
        $result = $db->command(array(
            "mapreduce" => "impsRef", 
            "map" => $map,
            "reduce" => $reduce,
            "query" => $_query,
            "out"=>$_tmpCollection,
        ), array('timeout'=>2*3600*1000, 'socketTimeoutMS'=>2*3600*1000));//old new timeout support
        if ($result['ok']!=1) {
            throw new Exception('error: '.var_export($result, true));
        }

        $coll = $db->selectCollection($_tmpCollection);
        
        $_appsCursor = $coll->find()
                ->sort(array('value.bannerImps'=>-1,'creationDate'=>1))->limit(1000);
        $_apps = array();
        foreach($_appsCursor as $_app) {
            $_apps[] = array(
                'app_id'=>$_app['_id']['appId'],
                'creation_date'=>$_app['_id']['creationDate'],
                'referer'=>$_app['_id']['url'],
                'banner_impressions'=>$_app['value']['bannerImps'],
                'ad_impressions'=>$_app['value']['adImps'],
                'clicks'=>$_app['value']['clicks'],
				'url_ecpm'=> ($_app['value']['bannerImps']==0 ? 0 : $_app['value']['adExp']/$_app['value']['bannerImps']*1000)
            );
        }        
        return $_apps;       */         
    }

    public function getAdvertiserMonthlyReportGrid()
    {
        $grid = new System_Grid_DataGrid();
        $grid->setLimit(-1);
        $grid->setDefaultSort('name');
        $grid->setDefaultDir('asc');
        $grid->setFilters(array('dates', $this->_sitesFilter));
        $grid->getFilter('Dates')->setDefault(System_Search_Date::THIS_MONTH);
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('Dates')->getValue('endCalendarDate');

        $filename = "advetisers_monthly_report_{$startDate}-{$endDate}";
        $grid->setExportable(true)->setExportFilename($filename);
         
        $data = $this->getAdvertiserMonthlyReportData($startDate, $endDate, $this->_sitesFilter->getValue());

        $headers = array_shift($data);
        
        $grid->setExportIncludeFields(array_keys($headers));
        
        foreach ($headers as $key=>$headerName) {  
            $format = array();
            switch($key) {
                case 'name':
                    break;
                case 'site':
                    break;
                case 'clicks':
                case 'impressions':
                    $format = array('type'=>'number','align'=>'right');
                    break;
                
                case 'cpm':                    
                case 'cpc':
                    $format = array('align'=>'right');
                    break;
                
                case 'total':                
                default:
                    $format = array('type'=>'money','align'=>'right');
                    break;
            }
            $grid->addColumn($key, array('header'=>$headerName,'sortable'=>true)+$format);            
        }

        $grid->setDataSource(
            new System_Grid_DataGrid_DataSource_Array($data)
        );
        return $grid;
    }
    
    public function getPubMonthlyReportFixedGrid()
    {
        $grid = new System_Grid_DataGrid();
        $grid->setLimit(-1);
        $grid->setDefaultSort('user_name');
        $grid->setDefaultDir('asc');
        $grid->setFilters(array('dates'));
        $grid->getFilter('Dates')->setDefault(System_Search_Date::LAST_MONTH);
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('Dates')->getValue('endCalendarDate');

        $filename = "publishers_fixed_monthly_report_{$startDate}-{$endDate}";
        $grid->setExportable(true)->setExportFilename($filename);
         
        $data = $this->getPubMonthlyReportFixedData($startDate, $endDate);

        $headers = array_shift($data);
        
        $grid->setExportIncludeFields(array_keys($headers));
        
        foreach ($headers as $key=>$headerName) {  
            $format = array();
            switch($key) {
                case 'user_name':
                    $format = array('header'=>'Publisher',
                              'type'=>'link',
                              'links'=>'/admin/users/view/userId/$user_id');
                    break;
                case 'organization':
                    break;
                case 'address':
                    break;
                case 'phone':
                    break;
                case 'user_id':
                    break;
                case 'total':                
                default:
                    $format = array('type'=>'money','align'=>'right');
                    break;
            }
            $grid->addColumn($key, array('header'=>$headerName,'sortable'=>true)+$format);            
        }

        $grid->setDataSource(
            new System_Grid_DataGrid_DataSource_Array($data)
        );
        return $grid;
    }
    
    /**
     * Get publishers monthy report
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function publisherMonthlyReportData($publisherId=null,$startDate=null,$endDate=null)
    {
        $db = $this->getDb(true);
        $select = $db->select()
            ->from(array('users'=>'adrev_users'),
                    array('users.id'
                          ,'users.name'
                          //earned by publisher
                          ,'earned'=>'SUM(traffic.earned)'
                          //earned by adblade
                          //,'earned'=>'SUM(traffic.expended-traffic.earned-traffic.cobrand_earned)
                          ,'month'=>'DATE_FORMAT(traffic.creation_date,"%Y-%m")'))
            ->join(array('apps'=>'adrev_app'), 'apps.user_id=users.id', array())
            ->join(array('traffic'=>'adrev_app_traffic'),
                        '`traffic`.`app_id` = `apps`.`id` '.
                        ((null !== $startDate) ? (' AND traffic.creation_date>='
                                . $db->quote($startDate)) : ' ') .
                        ((null !== $endDate) ? (' AND traffic.creation_date<='
                                . $db->quote($endDate)):' '), array())
             ->where('traffic.creation_date > 20080101')
             ->where('users.site_id IN (?)', $this->_siteIds)
             ->group('users.id')
             ->group('month')
             ->order('name')
             ->order('month');
        if ( null !== $publisherId ) {
            $select->where('users.id=?', $publisherId);                     
        }
        return $db->fetchAll($select);
    }
    
    public function getAdvertisersExpensGrid()
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(false)
             ->setLimit(-1);
        $grid->setDefaultSort('id')
             ->setDefaultDir('asc')
             ->setShowTotals(false);
        $grid
             ->addColumn('name',
                        array('header'=>'Advertiser Name',
                              'type'=>'link',
                              'links'=>"/admin/users/view/userId/\$id"))
             ->addColumn('site_name',array('header'=>'Site','sortable'=>false))
             ->addColumn('email', array('header'=>'Email','links'=>'mailto:$email'))
             ->addColumn('dailySpendingLimit', array('header'=>'Spending Limit','align'=>'right'))
             ->addColumn('adsCount', array('header'=>'Ads count','align'=>'right'))
             ->addColumn('expended', array('header'=>'Expenses','type'=>'money','align'=>'right'))
             ->addColumn('balance', array('header'=>'Current Balance','type'=>'money','align'=>'right'))
             ->addColumn('cpa', array('header'=>'CPA','align'=>'right'))
            ;

        $grid->setFilters(array('dates', $this->_sitesFilter));
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('Dates')->getValue('endCalendarDate');        
        
        $traffSubq = $this->getDb(true)->select()
                        ->from(array('aat'=>'adrev_app_traffic'), array(
                        	'ad_id'     =>'ad_id',
                            'expended'  => 'sum(expended)',
                            'orders'    => 'sum(orders)'    
                        ))
                        ->group('ad_id');
        if ( null !== $startDate ) {
            $traffSubq->where('aat.creation_date>=?', $startDate);
        }
        if ( null !== $endDate ) {
            $traffSubq->where('aat.creation_date<=?', $endDate);
        }
        
    	$select = $this->getDb(true)->select()
                  ->from(array('users'=>'adrev_users'), array(
                    'users.name',
                    'users.email',
                    'users.id',
                    'users.balance',
                    'dailySpendingLimit'=>'IF(daily_spending_limit IS NULL,"No Limit",FORMAT(daily_spending_limit,2))',
                    'expended'=>'IFNULL(SUM(appTraf.expended),0)',
                    'orders'=>'IFNULL(SUM(appTraf.orders),0)',
                    'cpa'=>'IF( SUM(appTraf.orders) ,ROUND(SUM(appTraf.expended)/SUM(appTraf.orders),2),"n/a")',
                    'adsCount'=> new Zend_Db_Expr('(SELECT COUNT(*) FROM adrev_ads WHERE user_id=users.id)'),
                    
                  ))
                  ->join(array('sites'), 'sites.id=users.site_id', array('site_name'=>'sites.name'))
                  ->join(array('ads'=>'adrev_ads'), 'ads.user_id=users.id', array() )
                  ->join(array('appTraf'=>$traffSubq), 'appTraf.ad_id=ads.id', array() )
                  ->join(array('advr'=>'user_advertiser'), '`advr`.`user_id`=`ads`.`user_id`')
                  ->where('users.site_id IN (?)', $this->_siteIds)
                  ->group('users.id');
        $site = $this->_sitesFilter->getValue();
        if (null!==$site) {
            $select->where('users.site_id IN (?)', $site);
        }
        $grid->setSelect($select);
        return $grid;
        
    }

    public function getPublishersEarningGrid()
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(true)
             ->setLimit(-1);
        $grid->setDefaultSort('id')
             ->setDefaultDir('asc')
             ->setShowTotals(false);
        $grid
             ->addColumn('admin', array('header'=>'Permission Type',
                 'type'=>'script','scriptPath'=>'reports/grid/user-type-column.phtml', 'sortable'=>true))
             ->addColumn('site_name',array('header'=>'Site','sortable'=>true))
             ->addColumn('id', array('header'=>'User ID', 'sortable'=>true, 'align'=>'right'))
             ->addColumn('name',
                        array('header'=>'Name',
                              'type'=>'link',
                              'links'=>"/admin/users/view/userId/\$id",'sortable'=>true))
             ->addColumn('publisher_type_name',array('header'=>'Publisher Type','sortable'=>true))   
             ->addColumn('organization', array('header'=>'Organization','sortable'=>true))
             ->addColumn('email', array('header'=>'Email','type'=>'link','links'=>'mailto:$email','sortable'=>true))
                       
             ->addColumn('appsCount', array('header'=>'Apps Count','type'=>'number','sortable'=>true))
             ->addColumn('publisher_earned', array('header'=>'Earned','type'=>'money','align'=>'right','sortable'=>true))
             ->addColumn('balance', array('header'=>'Current Balance','type'=>'money','align'=>'right','sortable'=>true))             
            ;
        
        $grid->setFilters(array('dates','checkbox', $this->_sitesFilter));
        $checkbox = $grid->getFilter('Checkbox');
        $checkbox->setLabel('Single and Master Accounts Only ')
                 ->setWidth(250);
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate = $grid->getFilter('Dates')->getValue('endCalendarDate');        
        $masterAccounts = $checkbox->getValue();
        
        $filename = "publisher-earnings_{$startDate}-{$endDate}";
        $grid->setExportable(true)
             ->setExportFilename($filename)
             ->setExportIncludeFields(array(
                 'id', 'name', 'publisher_type_name', 'organization','email', 'appsCount','publisher_earned','balance'
             ));
        
    	$select = $this->_getPublishersEarningsSelect($startDate, $endDate, $masterAccounts, $this->_sitesFilter->getValue());
        $grid->setSelect(
            $this->getDb(true)->select()->from(array('wrapper'=>$select))
        ); 
        return $grid;
        
    }
    
    public function getPublishersClicksGrid($userId)
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(true)
             ->setLimit(100);
        $grid->setDefaultSort('creation_datetime')
             ->setDefaultDir('asc');
        $grid
             ->addColumn('creation_datetime', array('header'=>'Date','sortable'=>true))
             ->addColumn('ad_type', array('header'=>'Type','sortable'=>false))
             ->addColumn('ip', array('header'=>'IP','sortable'=>true))
             ->addColumn('earned', array('header'=>'Ear. by Pub','align'=>'right','sortable'=>false))
             ->addColumn('expended', array('header'=>'Exp. by Adv','align'=>'right','sortable'=>false))   
             ->addColumn('referer', array('header'=>'Page','sortable'=>false))             
            ;

        $grid->setFilters(array('dates','ip'));
        $ip = $grid->getFilter('Ip')->getValue();
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate = $grid->getFilter('Dates')->getValue('endCalendarDate');        
        
        $apps = $this->getTable()->getAppsTable()->getAppsList($userId);
        $_appIds = array();
        foreach ($apps as $_app) {
            $_appIds[] = (int)$_app['id'];
        }            
        
        $db = $this->getMongoDb();
        $coll = $db->selectCollection(Adblade_Db_Collections::RAW_LOGS);
        $_query = array(                
            'type' => 2,
            'appId'=>array('$in'=>$_appIds)
        );
        if (!empty($startDate) && !empty($endDate)) {
            $_mStartId = System_Functions::dateStartToMongoId($startDate.' 00:00:00');
            $_mEndId = System_Functions::dateEndToMongoId($endDate.' 23:59:59');        
            $_query['_id'] = array('$gte'=>$_mStartId,'$lte'=>$_mEndId);
        }        
        if (!empty($ip)) {
            $_query['user.ip'] = $ip;
        }                
        
        $_cursor = $coll->find($_query)
                ->timeout(600*1000)
                ->sort(array('value.bannerImps'=>-1,'creationDate'=>1))->limit(1000);
        $_data = array();
        //@TODO maybe make ads as a single object instead of ADS list
        foreach($_cursor as $_app) {
            $_exp = 0;
            $_earn = 0;
            $_type = '';
            foreach ($_app['ads'] as $_ad) {
                $_exp+=$_ad['exp'];
                $_earn+=$_ad['earn'];
                $_type = $_ad['brate'];
            }
            $_id = $_app['_id'];
            $_data[] = array(
                'creation_datetime'=>date('Y-m-d H:i:s', $_id->getTimestamp()),
                'ad_type'=>$_type,
                'ip'=>$_app['user']['ip'],
                'earned'=>$_earn,
                'expended'=>$_exp,
                'referer'=>$_app['url'],
            );
        }
        
    	$grid->setDataSource(new System_Grid_DataGrid_DataSource_Array($_data));
        
        $grid->setExportIncludeFields(array(
            'creation_datetime','ad_type','ip',
            'earned','expended','referer'
        ));
        $fileName = "clicks_details-{$userId}_{$startDate}-{$endDate}";
        $grid->setExportable(true)->setExportAdapter(
                array(
                    'name'=>'csv',
                    'options'=>array('fileName'=>$fileName),
                    'exportUrlParams' => array('pages'=>'all')
                )
        );
        
        return $grid;
    }
    
    public function getAppsCpmVsRevshareReportGrid($publisherId)
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());

        $grid->setFilters(array('dates'));
        //$grid->getFilter('Dates')->setDefault(System_Search_Date::LAST_MONTH);

        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('Dates')->getValue('endCalendarDate');
        $db = $this->getDb(true);
        $select = $db->select()
                ->from(array('app'=>'adrev_app'), array(
                    'app.*',
                    'impressions'=>'IFNULL(SUM(traff.impressions),0)',
                    'clicks'=>'IFNULL(SUM(traff.clicks),0)',
                    //earned by publisher
                    'earned'=>'ROUND(IFNULL(SUM(traff.expended-traff.earned-traff.cobrand_earned),0),2)',
                    'expended'=>'ROUND(IFNULL(SUM(traff.expended),0),2)',
                    'gross_eCPM'=>'ROUND(IF(SUM(traff.impressions)>0,
                        SUM(traff.expended)
                        *1000/SUM(traff.impressions), 0),2)',
                    'pub_eCPM'=>'ROUND(IF(SUM(traff.impressions)>0,
                        SUM(traff.expended-traff.earned-traff.cobrand_earned)
                        *1000/SUM(traff.impressions), 0),2)',
                    
                ))
                ->join(array('user'=>'adrev_users'), 'user.id=app.user_id',
                        array('publisher_id'=>'id', 'publisher_name'=>'name'))
                ->join(array('options'=>'app_options'),
                       'options.app_id=app.id AND floor_guaranteed_cpm IS NOT NULL',
                       array('floor_guaranteed_cpm'))
                ->join(array('revshare'=>Adblade_Db_Tables::APP_REVENUE_SHARE),
                        'app.id=revshare.app_id and share_cpm IS NOT NULL',
                        array('share_cpm'=>'ROUND(share_cpm,2)'))
                ->joinLeft(array('traff'=>'adrev_app_zone'),
                        'traff.app_id=app.id '
                        .((null!==$startDate)?(' AND traff.creation_date>='.$db->quote($startDate)):' ')
                        .((null!==$endDate)?(' AND traff.creation_date<='.$db->quote($endDate)):' '),
                        array())
                ->where('app.user_id=?', $publisherId)
                ->group('app.id');
        $results = $db->select()->from(array('results'=>$select), array(
            'results.*',
            'needToPayECPM'=>'ROUND(IF(expended*(floor_guaranteed_cpm/100)>earned,
                expended*(floor_guaranteed_cpm/100)*1000/impressions ,pub_eCPM ),2)',
            'earned_by_rev_share'=>'ROUND(expended*(floor_guaranteed_cpm/100),2)',
            'rev_share_ecpm'=>'ROUND(expended*(floor_guaranteed_cpm/100)/impressions*1000,2)',
            'payBy'=>'IF(expended*(floor_guaranteed_cpm/100)>earned,"RevShare","CPM")',
            'needToPay'=>'IF(expended*(floor_guaranteed_cpm/100)>earned,expended*(floor_guaranteed_cpm/100),earned)',
            'pubNetEcpm'=>'ROUND(IF(expended*(floor_guaranteed_cpm/100)>earned,
                                expended*(floor_guaranteed_cpm/100),earned)/impressions*1000,2)'
        ));
        $finalSelect = $db->select()->from(array('final'=>$results), array(
            'final.*',
            'adbladeNetProfit'=>'ROUND(expended-needToPay,2)',
            'adbladeProfitMargin'=>'ROUND(IFNULL((expended-needToPay)/expended,0)*100,2)',
            'adbladeNetEcpm'=>'ROUND(IFNULL((expended-needToPay)/impressions*1000,0),2)',
            
        ));
        $grid->setSelect($finalSelect);

        $grid->setSortable(false)
             ->setLimit(-1);
        $grid->setDefaultDir("asc");
        $grid->setTemplatePart('body', 'reports/grid/apps-cpm-vs-revshare-body.phtml');
        $grid
             ->addColumn('id', array('header'=>'App ID'))
             ->addColumn('full_name',
                        array('header'=>'App Name',
                              'type'=>'link',
                              'links'=>"/admin/apps/app-statistics/appId/\$id"
                              ))

             ->addColumn('share_cpm', array('header'=>'Guaranteed CPM Rate','align'=>'right'))
             ->addColumn('floor_guaranteed_cpm', array('header'=>'Publisher Rev Share %','align'=>'right'))
             ->addColumn('impressions', array('header'=>'Impressions','align'=>'right'))
             ->addColumn('clicks', array('header'=>'Clicks','align'=>'right'))
             ->addColumn('expended', array('header'=>'Expended (Gross)','align'=>'right','type'=>'money'))
             ->addColumn('gross_eCPM', array('header'=>'Adblade Gross eCPM','align'=>'right'))
             ->addColumn('earned', array('header'=>'Pub Earned (based on CPM rate)','align'=>'right','type'=>'money'))
             ->addColumn('earned_by_rev_share', 
                     array('header'=>'Pub Earned (based on Rev Share %)', 'align'=>'right', 'type'=>'money'))
             ->addColumn('rev_share_ecpm', array('header'=>'Publisher Rev Share eCPM','align'=>'right','type'=>'money'))
             ->addColumn('payBy', array('header'=>'Pay Method','align'=>'right'))
             ->addColumn('needToPay', array('header'=>'Owed to Publisher','align'=>'right','type'=>'money'))
             ->addColumn('pubNetEcpm', array('header'=>'Publisher Net eCPM','align'=>'right'))
             ->addColumn('adbladeNetProfit', array('header'=>'Adblade Net Profit','align'=>'right','type'=>'money'))
             ->addColumn('adbladeProfitMargin', array('header'=>'Adblade Profit Margin','align'=>'right'))
             ->addColumn('adbladeNetEcpm', array('header'=>'Adblade Net eCPM','align'=>'right'));
        
        $_fields = array();
        foreach ( $grid->getColumns() as $column ) {
            $_fields[]=$column->getId();
        }
        
        $filename = "cpm-vs-revshare-{$publisherId}_{$startDate}-{$endDate}";
        $grid->setExportable(true)
             ->setExportFilename($filename)
             ->setExportIncludeFields($_fields);
        
        return $grid;
    }

    public function getDistributedImpressionsReportGrid()
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());

        $grid->setFilters(array('dates','adStatus','adId','userEmail'));
        
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('Dates')->getValue('endCalendarDate');
        $adStatus = $grid->getFilter('AdStatus')->getValue();
        $userEmail = $grid->getFilter('UserEmail')->getValue();
        $adId = $grid->getFilter('AdId')->getValue();
        
        $db = $this->getDb(true);
        $select = $db->select()
                ->from(array('ads'=>'adrev_ads'), array(
                    'id',
                    'user_id',
                    'title',
                    'impressions'=>'IFNULL(SUM(traff.impressions),0)',
                    'pacing'=>'ROUND(IF( traff.impressions, SUM(traff.impressions)/opt.imps*100  ,0),2)',
                    //'clicks'=>'IFNULL(SUM(traff.clicks),0)',
                    //'expended'=>'ROUND(IFNULL(SUM(traff.expended),0),2)',                    
                ))
                ->join(array('opt'=>'ad_options'), 'opt.ad_id=ads.id', array(
                    'start_datetime', 'stop_datetime', 'dist_imps'=>'imps',
                    'start_date'=>'DATE_FORMAT(start_datetime,"%m/%d/%Y")',
                    'stop_date'=>'DATE_FORMAT(stop_datetime,"%m/%d/%Y")'
                ))
                ->join(array('status'=>'adrev_ads_statuses'), 'status.id=ads.status', array(
                    'status_name'=>'name'
                ))
                ->join(array('user'=>'adrev_users'), 'user.id=ads.user_id',
                        array('advertiser_id'=>'id', 'advertiser_name'=>'name', 'email'))
                ->joinLeft(array('traff'=>'adrev_app_traffic'),
                        'traff.ad_id=ads.id '
                        .((null!==$startDate)?(' AND traff.creation_date>='.$db->quote($startDate)):' ')
                        .((null!==$endDate)?(' AND traff.creation_date<='.$db->quote($endDate)):' '),
                        array())   
                ->where('opt.imps IS NOT NULL')
                ->where('opt.start_datetime IS NOT NULL')
                ->where('opt.stop_datetime IS NOT NULL')
                ->group('ads.id'); 
        if ($adStatus) {
            $select->where('ads.status=?', $adStatus);
        }
        if ($adId) {
            $select->where('ads.id=?', $adId);
        }
        if ($userEmail) {
            $select->where('user.email LIKE ?', "%{$userEmail}%");
        }
        $grid->setSelect($select);

        $grid->setSortable(false)
             ->setLimit(-1);
        $grid->setDefaultDir("asc")
             ->setShowTotals(false);
        $grid
             ->addColumn('id', array('header'=>'ID'))
             ->addColumn('title', array('header'=>'Title'))
             ->addColumn('advertiser_name',
                        array('header'=>'Advertiser',
                              'type'=>'link',
                              'format'=>'$advertiser_name - $email',
                              'links'=>"/admin/users/view/userId/\$user_id"))
             ->addColumn('start_date', array('header'=>'Start'))
             ->addColumn('stop_date', array('header'=>'Stop'))
             ->addColumn('dist_imps', array('header'=>'Impression Goal','type'=>'number'))
             ->addColumn('impressions', array('header'=>'Imps','type'=>'number'))
             ->addColumn('pacing', array('header'=>'Pacing %'))
             ->addColumn('status_name', array('header'=>'Status'))
                ;

        return $grid;
    }
    
    public function getGeoStatsReportGrid()
    {
        $grid = new System_Grid_DataGrid();

        $grid->setFilters(array('dates','states','dma','targetCountries'));
        $grid->getFilter('Dates')->setDefault(System_Search_Date::THIS_MONTH);
                
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate = $grid->getFilter('Dates')->getValue('endCalendarDate');
        $dmaId = (int)$grid->getFilter('Dma')->getValue();
        $stateCode = $grid->getFilter('States')->getValue();
        $targetCountryCode = $grid->getFilter('TargetCountries')->getValue();
        
        $db = $this->getMongoDb();
        $map = new MongoCode("function() { emit(this._id.appId,{imps:this.imps,clicks:this.clicks,orders:this.orders}); }");
        $reduce = new MongoCode("function(k, vals) { 
            var imps = clicks = orders = 0;
            vals.forEach(function(v){imps+=v.imps;clicks+=v.clicks;orders+=v.orders});
            return {imps:imps,clicks:clicks,orders:orders}; }
        ");
        $_query = array();
        if ($dmaId) {
            $_query['_id.dma'] = $dmaId;
        }
        if ($stateCode) {
            $_query['_id.region'] = $stateCode;
        }
        if ($targetCountryCode) {
            $_query['_id.country'] = $targetCountryCode;
        }
        if (!empty($startDate) && !empty($endDate)) {
            $_mStartId = strtotime($startDate.' 00:00:00');
            $_mEndId = strtotime($endDate.' 23:59:59');        
            $_query['_id.time'] = array('$gte'=>$_mStartId,'$lte'=>$_mEndId);
        }
        $_tmpCollection = 'tmp_geoStats';
        $_cmd = array(
            "mapreduce" => "geoStats", 
            "map" => $map,
            "reduce" => $reduce,
            "out"=>$_tmpCollection,
        );
        if (!empty($_query)) {
            $_cmd['query'] = $_query;
        }
        $result = $db->command($_cmd, array('timeout'=>2*3600*1000, 'socketTimeoutMS'=>2*3600*1000));//old new timeout support
        
        if ($result['ok']!=1) {
            throw new Exception('error: '.var_export($result, true));
        }
        
        $coll = $db->selectCollection($_tmpCollection);
        
        $_appsCursor = $coll->find()->sort(array('value.imps'=>-1))->limit(15);
        $_apps = array();
        foreach($_appsCursor as $_app) {
            $_apps[$_app['_id']] = array(
                'app_id'=>$_app['_id'],
                'imps'=>$_app['value']['imps'],
                'clicks'=>$_app['value']['clicks'],
                'orders'=>$_app['value']['orders'],
            );
        }
        
        $_appsCursor = $coll->find( array('_id'=>array('$nin'=>array_keys($_apps))) )
                ->sort(array('value.imps'=>-1));
        $_apps['Others'] = array('app_id'=>'Others', 'imps'=>0,'clicks'=>0,'orders'=>0);
        
        foreach($_appsCursor as $_app) {
            $_apps['Others']['imps']   += $_app['value']['imps'];
            $_apps['Others']['clicks'] += $_app['value']['clicks'];
            $_apps['Others']['orders'] += $_app['value']['orders'];
        }        
        
        $grid->setDataSource(new System_Grid_DataGrid_DataSource_Array(array_values($_apps)));

        
        $grid->setSortable(true)
             ->setLimit(-1)
             ->setShowTotals(true);
        $grid->setDefaultSort('imps');
        $grid->addColumn('app_id', array('header'=>'App ID','type'=>'link',
            'links'=>'/admin/apps/app-statistics/appId/$app_id'));            
        $grid->addColumn('imps', array('header'=>'Imps','type'=>'number','showTotals'=>true));
        $grid->addColumn('clicks', array('header'=>'Clicks','type'=>'number','showTotals'=>true));
        $grid->addColumn('orders', array('header'=>'Orders','type'=>'number','showTotals'=>true));
        
        return $grid;
    }
    
    public function getActivityGrid($transactionId=null)
    {        
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());

        $grid->setFilters(array('activityTypes','dates','userId','select','actorId'));
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate = $grid->getFilter('Dates')->getValue('endCalendarDate');
        $userId = $grid->getFilter('UserId')->getValue();
        $actorId = $grid->getFilter('ActorId')->getValue();
        $types = $grid->getFilter('ActivityTypes')->getValue();
        $adminId = $grid->getFilter('Select')->getValue();
        
        $select = $this->getTable()->getActivityLogTable()->getActivitySelect(
                $userId, $actorId, $types, $startDate, $endDate, $adminId, $transactionId
        );

        if (null!==$transactionId) {
            $select->reset(Zend_Db_Select::GROUP)
                ->group('id');
        }

        $_admins = $this->getTable()->getAdminsTable()->getAdminsList();        
        foreach ($_admins as $_item) {
            $_names[$_item['id']] = $_item['name'];
        }
        asort($_names);
        $userId = $grid->getFilter('Select')
                    ->setOptions($_names)
                    ->setLabel('Admin'); 
        
        $grid->setSelect($select);

        $grid->setSortable(true)
             ->setLimit(50);
        $grid->setDefaultSort('creation_datetime')
             ->setDefaultDir('desc')
             ->setShowTotals(false);        
        
        //@TODO: refactor log types, should be a table in db
        $grid->addColumn('id', array('header'=>'ID','sortable'=>false))
             ->addColumn('userName',
                        array('header'=>'User Name',
                              'type'=>'link',
                              'sortable'=>true,
                              'links'=>'/admin/users/view/userId/$userId'
                              ))
             ->addColumn('type', array('header'=>'Log Type','type'=>'script',
                 'scriptPath'=>'reports/grid/activity-type-column.phtml','sortable'=>true))
             ->addColumn('changes', array('header'=>'Changes','type'=>'text','sortable'=>false))
             ->addColumn('creation_datetime', array('header'=>'Creation Date','format'=>'$dateTime','sortable'=>true))
             ->addColumn('login_history', array('header'=>'Logins History','sortable'=>false));

        return $grid;
    }
    
    public function getAppAdHistoryGrid($shouldRunReport=true) {       
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());

        $grid->setFilters(array('dates','appId','select','adId'));
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate = $grid->getFilter('Dates')->getValue('endCalendarDate');
        $adId = $grid->getFilter('AdId')->getValue();
        $appId = $grid->getFilter('AppId')->getValue();
        $_creators = $this->getTable()->getAppAdCreatorsTable()->getAllIdPairs();
        $grid->getFilter('Select')
                    ->setOptions($_creators)
                    ->setLabel('Creators'); 
        
        $creatorId = $grid->getFilter('Select')->getValue();
        
        $select = $this->getDb(true)->select()
                ->from(array('h'=>'app_ad_assn_history'), array(
                    'h.id',
                    'h.app_id',
                    'h.ad_id',
                    'creatorName'=>'c.name',
                    'h.creation_datetime',
                    'data',
                    'site_name'=>'site.name',
                ))
                ->join(array('app'=>'adrev_app'), 'app.id=h.app_id', array())
                ->join(array('user'=>'adrev_users'), 'user.id=app.user_id', array())
                ->join(array('site'=>'sites'), 'site.id=user.site_id', array())
                ->join(array('c'=>'app_ad_creators'), 'c.id=h.creator_id', array())
                ;
        $select->where('user.site_id IN (?)', $this->_siteIds);
        if (null!==$appId) {
            $select->where('h.app_id=?', $appId);
        }
        if (null!==$adId) {
            $select->where('h.ad_id=?', $adId);
        }
        if (null!==$creatorId) {
            $select->where('h.creator_id=?', $creatorId);
        }
        if (null!==$startDate) {
            $select->where('h.creation_datetime>=?', date('Y-m-d', strtotime($startDate)).' 00:00:00');
        }
        if (null!==$endDate) {
            $select->where('h.creation_datetime<=?', date('Y-m-d', strtotime($endDate)).' 23:59:59');
        }

        if ($shouldRunReport) {
            $grid->setSelect($select);
        }
        else {
            $grid->setEmptyText('Please use the form to search for results.');
            $grid->setDataSource(new System_Grid_DataGrid_DataSource_Array(array()));
        }

        $grid->setSortable(true)
             ->setLimit(50);
        $grid->setDefaultSort('creation_datetime')
             ->setDefaultDir('desc')
             ->setShowTotals(false);        
        
        $grid->addColumn('id', array('header'=>'ID','sortable'=>false))
             ->addColumn('site_name',array('header'=>'Site','sortable'=>false))
             ->addColumn('creatorName',
                        array('header'=>'Feature',
                              /*'type'=>'link',
                              'sortable'=>true,
                              'links'=>'/admin/users/view/userId/$userId'*/
                              ))
             ->addColumn('app_id', array('header'=>'AppId','type'=>'text','sortable'=>true))
             ->addColumn('ad_id', array('header'=>'AdId','type'=>'text','sortable'=>true))
             ->addColumn('data', array('header'=>'Details','type'=>'script',
                 'scriptPath'=>'reports/grid/app-ad-history-data.phtml','sortable'=>false))
             ->addColumn('creation_datetime', array('header'=>'Creation Date','sortable'=>true))
             ;

        return $grid;
    }
    
    /**
     * Get advertisers monthy report
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getAdvertiserMonthlyReportData($startDate=null,$endDate=null, $site=null)
    {
        $db = $this->getDb(true);
        $select = $db->select()
                ->from(array('users'=>'adrev_users'),
                        array('users.id'
                              ,'users.name'
                              ,'site'=>'site.name'
                              ,'expended'=>'SUM(traffic.expended)'
                              ,'clicks'=>'SUM(traffic.clicks)'
                              ,'impressions'=>'SUM(traffic.impressions)'
                              ,'orders'=>'SUM(traffic.orders)'
                              ,'month'=>'DATE_FORMAT(traffic.creation_date,"%Y-%m")'))
                ->join(array('ads'=>'adrev_ads'), 'ads.user_id=users.id', array())
                ->join(array('site'=>'sites'), 'site.id=users.site_id', array())
                ->join(array('traffic'=>'adrev_app_traffic'),
                            '`traffic`.`ad_id` = `ads`.`id` '.
                            ((null !== $startDate) ? (' AND traffic.creation_date>=' 
                                    . $db->quote($startDate)) : ' ') .
                            ((null !== $endDate) ? (' AND traffic.creation_date<=' 
                                    . $db->quote($endDate)):' '), array())
                 ->where('traffic.creation_date > 20080101')
                 ->where('users.site_id IN (?)', $this->_siteIds)
                 ->group('users.id')
                 ->group('month')
                 ->order('name')
                 ->order('month');
        if (null!==$site) {
            $select->where('users.site_id=?', $site);
        }
        $advertisersList = $db->fetchAll($select);
        $advFilteredList=array();
        $startMonth = $endMonth = date('Y-m', time());        
        foreach ($advertisersList as $item) {
            if ( $item['month'] < $startMonth) {
                $startMonth=$item['month'];
            }
            if (!isset($advFilteredList[$item['id']])) {
                $advFilteredList[$item['id']]=array('name'=>$item['name'],'site'=>$item['site']);
                $advFilteredList[$item['id']][$item['month']]=$item['expended'];
                $advFilteredList[$item['id']]['clicks']      = $item['clicks'];
                $advFilteredList[$item['id']]['impressions'] = $item['impressions'];
                $advFilteredList[$item['id']]['orders']      = $item['orders'];
            } else {
                $advFilteredList[$item['id']][$item['month']]=$item['expended'];
                $advFilteredList[$item['id']]['clicks']      += $item['clicks'];
                $advFilteredList[$item['id']]['impressions'] += $item['impressions'];
                $advFilteredList[$item['id']]['orders']      += $item['orders'];
            }


            $advFilteredList[$item['id']]["cpm".$item['month']] = 
                !empty($item['impressions']) ? $item['expended']/$item['impressions']*100 : 0;

            $advFilteredList[$item['id']]["cpc".$item['month']] =
                !empty($item['clicks']) ? $item['expended']/$item['clicks'] : 0;

        }

        $report=$months=array();
        $currentMonth=$startMonth;
        while ($currentMonth<=$endMonth) {
           $months[$currentMonth]=$currentMonth;
           $time=strtotime($currentMonth);
           $currentMonth= date('Y-m', mktime(0, 0, 0, date('m', $time)+1, 
                   date('d', $time), date('y', $time)));
        }

        foreach ($advFilteredList as $key=>$item) {
            $report[$key]=array('name'=>$item['name'], 'site'=>$item['site']);
            $total = 0;
            
            foreach ($months as $month) {
                $amount=!empty($item[$month])?$item[$month]:0;                
                $report[$key][$month]=round($amount, 2);
                $total+=$amount;                
            }

            $cpm = !empty($item['impressions']) ? $total/$item['impressions']*1000 : 0;
            $cpc = !empty($item['clicks']) ? $total/$item['clicks'] : 0;
            $report[$key]['total']=$total;
            $report[$key]['clicks']       = $item['clicks'];
            $report[$key]['impressions']  = $item['impressions'];
            $report[$key]['cpm']          = round($cpm, 5);
            $report[$key]['cpc']          = round($cpc, 5);

        }

        $months = array('name'=>'','site'=>'Site')
                + $months
                + array(
                    'total'=>'Total',
                    'clicks'=>'Clicks',
                    'impressions'=>'Impressions',
                    'cpm'=>'Avg CPM',
                    'cpc'=>'Avg CPC'
                );

        array_unshift($report, $months);
        /*$bottomTotals=array();

        for($j=0;$j<count($months)-2;$j++) {
            $total=0;
            for($i=1;$i<count($report);$i++) {
                if(isset($report[$i][$j]) && !empty($report[$i][$j])) //YY
                    $total+=$report[$i][$j];
            }
            $bottomTotals[]=$total;
        }
        
        array_unshift($bottomTotals,'Total');
        array_push($bottomTotals,'');
        array_push($report,$bottomTotals);
        */

        return $report;
    }

    /**
     * Get advertisers monthy report
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getPubMonthlyReportFixedData($startDate=null, $endDate=null)
    {
        $db = $this->getDb();
        //@TODO: works like include
        $this->getTable()->getPaymentsTable();

        $select = $this->getTable()->getAppsTable()->getAppsStatisticsSelect($startDate, $endDate);
        $select->reset(Zend_Db_Select::GROUP);
        $select->columns(array('month'=>'DATE_FORMAT(traff.creation_date,"%Y-%m")'), 'traff');
        $select->columns(array(
            'address'=>'CONCAT(street, ", ", city, " ", state, " ", postalcode)',
            'phone'=>'telephone',
            'debit'=>"(SELECT sum(amount) from adrev_payments WHERE userid=publisher.user_id "
                    ."AND type_id=".PaymentsTable::PUBLISHER_DEBIT
                    .($startDate?" AND creation_date >='{$startDate} 00:00:00' ":'')
                    .($endDate?" AND creation_date <='{$endDate} 23:59:59'":'')
                    .")"
        ), 'users');
        
        $select->where('publisher.partner_type_id=?', User_Model_DbTable_PublisherTypes::FIXED);        
        $select->where('traff.creation_date > 20080101');
        $select->group('user_id')->group('month')->order('user_name')->order('month');
        
        
        $publishersList = $db->fetchAll($select);

        $pubFilteredList = array();
        $startMonth = $endMonth = date('Y-m', time());        
        foreach ($publishersList as $item) {
            if ( $item['month'] < $startMonth) {
                $startMonth=$item['month'];
            }
            if (!isset($pubFilteredList[$item['user_id']])) {
                $pubFilteredList[$item['user_id']] = array(
                    'user_id'=>$item['user_id'],
                    'user_name'=>$item['user_name'],
                    'organization'=>$item['organization'],
                    'address'=>$item['address'],
                    'phone'=>$item['phone'],
                    'debit'=>$item['debit'],
                    
                );
                $pubFilteredList[$item['user_id']][$item['month']]=$item['publisher_earned'];
            } else {
                $pubFilteredList[$item['user_id']][$item['month']]=$item['publisher_earned'];
            }            

        }

        $report=$months=array();
        $currentMonth=$startMonth;
        while ($currentMonth<=$endMonth) {
           $months[$currentMonth]=$currentMonth;
           $time=strtotime($currentMonth);
           $currentMonth= date('Y-m', mktime(0, 0, 0, date('m', $time)+1, 
                   date('d', $time), date('y', $time)));
        }

        foreach ($pubFilteredList as $key=>$item) {
            $report[$key]=array(
                'user_id'=>$item['user_id'],
                'user_name'=>$item['user_name'],
                'organization'=>$item['organization'],
                'address'=>$item['address'],
                'phone'=>$item['phone'],
                'debit'=>$item['debit'],
            );
            $total = 0;
            
            foreach ($months as $month) {
                $amount=!empty($item[$month])?$item[$month]:0;                
                $report[$key][$month]=round($amount, 2);
                $total+=$amount;                
            }
            
            $report[$key]['total']=$total;

        }

        $months = array( 'user_id'=>'ID', 
                         'user_name'=>'Publisher', 
                         'organization'=>'Billing Name',
                         'address'=>'Address',
                         'phone'=>'Phone',
                         'debit'=>'Debit',
                       )
                + $months
                + array('total'=>'Total');
        
        array_unshift($report, $months);

        return $report;
    }
    
    public function getAdsInteractionStats($appId=null) 
    {        
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(true)
             ->setLimit(50);
        $grid->setDefaultSort('ad_id')
             ->setDefaultDir('asc')
             ->setShowTotals(false);
        $grid
             ->addColumn('ad_id', array('header'=>'Ad ID','width'=>25))
             ->addColumn('ad_title', array('header'=>'title'))
             ->addColumn('ad_status', array('header'=>'Status'))
             ->addColumn('impressions', array('header'=>'Imps','align'=>'right','type'=>'number') )
             ->addColumn('clicks', array('header'=>'Clicks','align'=>'right','type'=>'number') )
             ->addColumn('scroll_up_count', array('header'=>'Scroll Ups','align'=>'right','type'=>'number'))
             ->addColumn('scroll_dn_count', array('header'=>'Scroll Downs','align'=>'right','type'=>'number'))
             ->addColumn('hover_count', array('header'=>'Hover','align'=>'right','type'=>'number'))
             ->addColumn('hover_duration', array('header'=>'Total Hover Duration','align'=>'right'))
             ->addColumn('average_hover_time', array('header'=>'Average Time Per Hover','align'=>'right'))
             ->addColumn('convRate', array('header'=>'Conversions','align'=>'right','type'=>'number'))
             ->addColumn('expended', array('header'=>'Spend','align'=>'right','type'=>'money'))
             ->addColumn('cpa', array('header'=>'CPA','align'=>'right'))                     
            ;
        $grid->setFilters(array('dates','adId','appId'));
        $adId = $grid->getFilter('AdId')->getValue();
        if ($appId === null) {
            $appId = $grid->getFilter('AppId')->getValue();
        }
        
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('Dates')->getValue('endCalendarDate');
        
        $_sql = 
            "select
               il.ad_id,
               ROUND(hover_duration/1000,2) as hover_duration,
               hover_count,
               scroll_up_count,
               scroll_dn_count,
               ROUND(average_hover_time/1000,2) as average_hover_time,
               ad.title as ad_title,
               ad_status.name as ad_status,
               traff.impressions,
               traff.clicks,
               traff.convRate,
               traff.expended,
               ROUND(traff.cpa,2) as cpa
            from
            (
               select
                   ad_id,
                   SUM(IF(action_type=1, duration, 0)) as hover_duration,
                   SUM(IF(action_type=1, 1, 0)) as hover_count,
                   SUM(IF(action_type=2, 1, 0)) as scroll_up_count,
                   SUM(IF(action_type=3, 1, 0)) as scroll_dn_count,
                   IF(SUM(IF(action_type=1, 1, 0)),SUM(duration)/SUM(IF(action_type=1, 1, 0)),0) as average_hover_time,
                   sum(duration),
                   count(*)
               from
                   interaction_log" . 
               (($appId !== null || null !== $startDate || null !== $endDate) ? " where " : "") .
                    ($appId !== null ? "app_id=$appId" : "") .
                    ($appId !== null && (null !== $startDate || null !== $endDate) ? " AND " : "") . 
                    ((null !== $startDate) ? " creation_datetime >= '$startDate 00:00:01'" : "") . 
                    ((null !== $startDate && null !== $endDate) ? " AND " : "") .  
                    ((null !== $endDate) ? " creation_datetime <= '$endDate 23:59:59'" : "") . 
               " group by
                   ad_id
            ) as il
            INNER JOIN
            (
               select
                   ad_id,
                   sum(impressions) as impressions,
                   sum(clicks) as clicks,
                   IF(SUM(orders)>0 AND SUM(clicks)>0, SUM(orders)/SUM(clicks), 0) as convRate,
                   SUM(expended) as expended,
                   IF( orders,expended/orders,0 ) as cpa
               from
                   adrev_app_traffic" . 
               (($appId !== null || null !== $startDate || null !== $endDate) ? " where " : "") .
                    ($appId !== null ? "app_id=$appId" : "") .
                    ($appId !== null && (null !== $startDate || null !== $endDate) ? " AND " : "") . 
                    ((null !== $startDate) ? " creation_date >= '$startDate'" : "") . 
                    ((null !== $startDate && null !== $endDate) ? " AND " : "") .  
                    ((null !== $endDate) ? " creation_date <= '$endDate'" : "") . 
               " group by
                   ad_id
            ) as traff
            ON il.ad_id = traff.ad_id
            INNER JOIN adrev_ads as ad ON il.ad_id=ad.id
               INNER JOIN adrev_ads_statuses as ad_status ON ad.status=ad_status.id" . 
                    ($adId ? " WHERE ad.id=" . (int)$adId . 
                        " OR ad.title like '%$adId%'" : "");
        
        $select = $this->getDb()->select()->from(array('result'=>new Zend_Db_Expr("($_sql)")));    
        
        $grid->setSelect($select);
        return $grid;
        
    }
    
    public function getAppsCatsReport()
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(true)
             ->setLimit(-1);//don't use limit
        $grid->setDefaultSort('app_id')
             ->setDefaultDir('asc')
             ->setShowTotals(true);
        $grid
             ->addColumn('app_id', array('header'=>'ID','width'=>25))
             ->addColumn('appName', array('header'=>'Name'))
             ->addColumn('site_name',array('header'=>'Site','sortable'=>false))
             ->addColumn('cat1', array('header'=>'Cat 1', 'type'=>'link',
                 'format'=>'<a href="/admin/ads/index/cid/$cat1/">$catName1</a>'
             ))
             ->addColumn('cat2', array('header'=>'Cat 2', 'type'=>'link',
                 'format'=>'<a href="/admin/ads/index/cid/$cat2/">$catName2</a>'
             ))
             ->addColumn('cat3', array('header'=>'Cat 3', 'type'=>'link',
                 'format'=>'<a href="/admin/ads/index/cid/$cat3/">$catName3</a>'
             ))
             ->addColumn('cat4', array('header'=>'Cat 4', 'type'=>'link',
                 'format'=>'<a href="/admin/ads/index/cid/$cat4/">$catName4</a>'
             ))
             ->addColumn('cat5', array('header'=>'Cat 5', 'type'=>'link',
                 'format'=>'<a href="/admin/ads/index/cid/$cat5/">$catName5</a>'
             ))
             ;
        
        $grid->setFilters(array('dates','appId','AdCategory', $this->_sitesFilter));
        $site = $this->_sitesFilter->getValue();
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('Dates')->getValue('endCalendarDate');
        $appId = $grid->getFilter('AppId')->getValue();
        $catId = $grid->getFilter('AdCategory')->getValue();
        $_db = $this->getDb(true);
        $select = $_db->select()
            ->from(array('app'=>'adrev_app'), array(
                't.app_id',
                'appName'=>'app.full_name',
                'ac.cid',
                'catName'=>'c.name',
                'ecpm'=>'IFNULL(SUM(expended)*1000/SUM(impressions),0)'
            ))
            ->join(array('t'=>'adrev_app_traffic'), 't.app_id=app.id', array())
            ->join(array('user'=>'adrev_users'), 'user.id=app.user_id', array())
            ->join(array('sites'), 'sites.id=user.site_id', array('site_name'=>'sites.name'))
            ->join(array('ac'=>'ad_block_categories'), 'ac.ad_id=t.ad_id', array())
            ->join(array('c'=>'block_categories'), 'c.id=ac.cid', array())
            ->where('user.site_id IN (?)', $this->_siteIds)
            ->group('t.app_id')
            ->group('ac.cid')
            ->order('app_id ASC')
            ->order('ecpm DESC')
            ->where('user.site_id IN (?)', $this->_siteIds);

        if (null!==$site) {
            $select->where('user.site_id=?', $site);
        }

        if (null!==$startDate) {
            $select->where('t.creation_date>=?', $startDate);
        }
        if (null!==$endDate) {
            $select->where('t.creation_date<=?', $endDate);
        }
        if (null!==$appId) {
            $select->where('app.id=?', $appId);
        }

        $statement = $_db->query($select);
        $data = array();
        while ($_item = $statement->fetch()) {

            $_appId = $_item['app_id'];
            if (!isset($data[$_appId])) {
                $data[$_appId] = array(
                    'app_id'=>$_appId,
                    'appName'=>$_item['appName'],
                    'site_name'=>$_item['site_name'],
                    'cats'=>array(),
                );
            }
            $data[$_appId]['cats'][] = array('key'=>$_item['cid'], 'val'=>$_item['catName']);
            $data[$_appId]['catsIds'][] = $_item['cid'];
        }
        $_finalResult = array();
        foreach ($data as $_item) {
             $row = array(
                'app_id'=>$_item['app_id'],
                'appName'=>$_item['appName'],
                 'site_name'=>$_item['site_name'],
            );
            for ($i=1;$i<=5;$i++) {
                if (isset($_item['cats'][$i-1])) {
                    $row['cat'.$i] = $_item['cats'][$i-1]['key'];
                    $row['catName'.$i] = $_item['cats'][$i-1]['val'];
                    $row['catId'.$_item['cats'][$i-1]['key']] = true;
                } else {
                    $row['cat'.$i] = '';
                    $row['catName'.$i] = '';
                }
            }
            //Show only records if app has the cat in TOP 5
            if (null!==$catId) {
                if (isset($row['catId'.$catId])) {
                    $_finalResult[] = $row;
                }
            } else {        
                $_finalResult[] = $row;
            }
        }
        
        $grid->setDataSource(new System_Grid_DataGrid_DataSource_Array($_finalResult));
        return $grid;
    }        
    
    public function getAppsInteractionStats($appId=null) 
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(true)
             ->setLimit(50);
        $grid->setDefaultSort('app_id')
             ->setDefaultDir('asc')
             ->setShowTotals(false);
        $grid
             ->addColumn('app_id', array('header'=>'App ID','width'=>25,'type'=>'link',
                 'links'=>'/admin/reports/ads-interaction/appId/$app_id'))
             ->addColumn('user_name', array('header'=>'Publisher','type'=>'link',
                 'links'=>'/admin/users/view/userId/$uid'))
             ->addColumn('app_name', array('header'=>'App Name'))
             ->addColumn('app_status', array('header'=>'Status'))
             ->addColumn('impressions', array('header'=>'Imps','align'=>'right','type'=>'number') )
             ->addColumn('clicks', array('header'=>'Clicks','align'=>'right','type'=>'number') )
             ->addColumn('scroll_up_count', array('header'=>'Scroll Ups','align'=>'right','type'=>'number'))
             ->addColumn('scroll_dn_count', array('header'=>'Scroll Downs','align'=>'right','type'=>'number'))
             ->addColumn('hover_count', array('header'=>'Hover','align'=>'right','type'=>'number'))
             ->addColumn('hover_duration', array('header'=>'Total Hover Duration','align'=>'right'))
             ->addColumn('average_hover_time', array('header'=>'Average Time Per Hover','align'=>'right'))
                   
            ;
        $grid->setFilters(array('dates','appId'));
        
        if ($appId === null) {
            $appId = $grid->getFilter('AppId')->getValue();
        }
        
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate = $grid->getFilter('Dates')->getValue('endCalendarDate');
        
        $_sql =            
        'select
           il.app_id as app_id,
           app.full_name as app_name,
           app_status.name as app_status,
           traff.impressions,
           traff.clicks,
           scroll_up_count,
           scroll_dn_count,
           hover_count,
           user.name as user_name,
           user.id as uid,
           ROUND(hover_duration/1000,2) as hover_duration,
           ROUND(average_hover_time/1000,2) as average_hover_time
        from
        (
           select
               app_id,
               SUM(IF(action_type=1, duration, 0)) as hover_duration,
               SUM(IF(action_type=1, 1, 0)) as hover_count,
               SUM(IF(action_type=2, 1, 0)) as scroll_up_count,
               SUM(IF(action_type=3, 1, 0)) as scroll_dn_count,
               IF(SUM(IF(action_type=1, 1, 0)),SUM(duration)/SUM(IF(action_type=1, 1, 0)),0) as average_hover_time,
               sum(duration),
               count(*),
               DATE_FORMAT(creation_datetime,"%b %d %Y") as creation_datetime
           from
               interaction_log' .
            ((null !== $startDate || null !== $endDate) ? " where " : "") .
            ((null !== $startDate) ? "creation_datetime >= '$startDate 00:00:01'" : "") .
            ((null !== $startDate && null !== $endDate) ? " AND " : "") .  
            ((null !== $endDate) ? "creation_datetime <= '$endDate 23:59:59'" : "") .
           ' group by
               app_id
        ) as il
        INNER JOIN
        (
           select
               app_id,
               sum(impressions) as impressions,
               sum(clicks) as clicks,
               DATE_FORMAT(creation_date,"%b %d %Y") as creation_date
           from
               adrev_app_traffic ' . 
            ((null !== $startDate || null !== $endDate) ? " where " : "") . 
            ((null !== $startDate) ? "creation_date >= '$startDate'" : "") . 
            ((null !== $startDate && null !== $endDate) ? " AND " : "") .  
            ((null !== $endDate) ? "creation_date <= '$endDate'" : "") . 
           " group by
               app_id
        ) as traff
        ON il.app_id = traff.app_id
        
        INNER JOIN adrev_app as app ON il.app_id=app.id
           INNER JOIN adrev_apps_statuses as app_status ON app.status=app_status.id
                LEFT JOIN adrev_users as user ON user.id=app.user_id" . 
            ($appId ? 
                " WHERE app.id=" . (int)$appId . " OR app_name like '%$appId%' OR user.name like '%$appId%'" : "");
            
        
        $select = $this->getDb()->select()->from(array('result'=>new Zend_Db_Expr("($_sql)")));    
        
        $grid->setSelect($select);
        return $grid;
    }
    
    public function getZonesStatsGrid()
    {

        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(true)
             ->setLimit(-1);//don't use limit
        $grid->setDefaultSort('name')
             ->setDefaultDir('asc')
             ->setShowTotals(true);
        $grid
             ->addColumn('id', array('header'=>'ID','width'=>25))
             ->addColumn('name', array('header'=>'Zone Name'))
             ->addColumn('zone_type', array('header'=>'Zone Type'))
             ->addColumn('target_type', array('header'=>'Target Type'))
             ->addColumn('avg_cpc_bid', array('header'=>'Avg CPC Bid', 'align'=>'right', 'type'=>'money'))
             ->addColumn('clicks', array('header'=>'Clicks','align'=>'right','type'=>'number','showTotals'=>true) )
             ->addColumn('impressions', array('header'=>'Imps','align'=>'right','type'=>'number','showTotals'=>true) )
             ->addColumn('earned', array('header'=>'Earned','align'=>'right','type'=>'money','showTotals'=>true) )
             ->addColumn('status_name', array('header'=>'Status') )             
            ;
        $zoneActive = new System_Search_Filters_Select();
        $zoneActive->setOptions(array(
            App_Model_DbTable_ZoneStatuses::ACTIVE=>'Active',
            App_Model_DbTable_ZoneStatuses::ARCHIVED=>'Not Active'
        ))->setLabel('Zone Status');

        $grid->setFilters(array('dates',$zoneActive));

        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('Dates')->getValue('endCalendarDate');
        $select = $this->getTable()
                ->getZonesTable()->getZonesSelect($startDate, $endDate, $zoneActive->getValue());
        $select->reset('order');
        $grid->setSelect(
            $this->getDb(true)->select()->from(array('wrapper'=>$select))
        );

        $grid->setExportIncludeFields(array(
            'id',
            'name',
            'clicks',
            'impressions',
            'earned',
        ));
        $filename = "zones_stats_{$startDate}-{$endDate}";
        $grid->setExportable(true)->setExportFilename($filename);

        return $grid;
    }

    protected function _sendAdsReport($userId, $email, $interval, $startDate, $endDate, $siteId)
    {
        $fields = array('id','title','bid','targetType','ad_rate_type',
                'impressions','clicks','orders','ctr','cpa','expended');
        
        //need to send only if stats exists for selected date range
        $fileName = "ads_stats_{$startDate}-{$endDate}";
        $model = App_Model_Models::getAdsModel();
        $grid = $model->getStatisticGrid(
                $userId,
                null, null,
                $startDate, $endDate,
                true, null);

        // daily reports require the date column
        if ($interval === 'Daily') {
            array_unshift($fields, 'traffDate');
            $grid->addColumn('traffDate',array('header'=>'Date'));
        }

        $grid->setExportIncludeFields($fields);
        $grid->setExportable(true)->setExportAdapter(
            array('name'=>'csv','options'=>array('fileName'=>$fileName))
        );
        $exportObject = $grid->getExportFile();
        if (!$exportObject->isEmpty()) {

            $at              = new Zend_Mime_Part( $exportObject->getData() );
            $at->type        = $exportObject->getMimeType() ;//Zend_Mime::TYPE_OCTETSTREAM;
            $at->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
            $at->encoding    = Zend_Mime::ENCODING_BASE64;
            $at->filename    = $exportObject->getFilename();
            
            $params = array(
                'name'       => 'Subscriber',
                'rep_period' => ucfirst($interval),
                'start_date' => $startDate,
                'end_date'   => $endDate
            );
            
            $mailer = App_Di::instance()->getMailer($siteId);
            $mail   = $mailer->createMail(App_Model_Mailer::ADVERTISER_ADS_REPORT, $params);
            $mail->addTo($email)
                 ->addAttachment($at)
                 ->send();
            
            return true;
        }
        
        return false;
    }

    protected function _sendSectionStatisticsReport($userId, $email, $interval, $startDate, $endDate, $siteId) {
        //need to send only if stats exists for selected date range
        $model = App_Model_Models::getAdminAdsModel();
        $grid = $model->getSectionStatisticsGrid($userId, $startDate, $endDate);
        $grid->setExportAdapter(array('name'=>'csv'))
             ->setLimit(-1);

        $exportObject = $grid->getExportFile();
        if (!$exportObject->isEmpty()) {
            $at              = new Zend_Mime_Part( $exportObject->getData() );
            $at->type        = $exportObject->getMimeType() ;//Zend_Mime::TYPE_OCTETSTREAM;
            $at->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
            $at->encoding    = Zend_Mime::ENCODING_BASE64;
            $at->filename    = $exportObject->getFilename();
            
            $params = array(
                'name'       => 'Subscriber',
                'rep_period' => ucfirst($interval),
                'start_date' => $startDate,
                'end_date'   => $endDate
            );
            
            $mailer = App_Di::instance()->getMailer($siteId);
            $mail   = $mailer->createMail(App_Model_Mailer::ADVERTISER_SECTION_STATISTICS_REPORT, $params);
            $mail->addTo($email)
                 ->addAttachment($at)
                 ->send();
            
            return true;
        }
        
        return false;
    }
    
    protected function _getPublisherManagerStatsSelect($startDate, $endDate, $managerId)
    {
        //@TODO think how to reuse query from publisher "index" page
        $db = $this->getDb();
        
        $subselect = $db->select()
            ->from(array('adv'=>'user_advertiser'), array('IFNULL(SUM(appTraf.cobrand_earned),0)'))
            ->join(array('ads'=>'adrev_ads'), 'ads.user_id=adv.user_id', array())
            ->join(array('appTraf'=>'adrev_app_traffic'), 'appTraf.ad_id=ads.id', array())
            //check that app does not belong to same account, money earned through another user's app
            ->join(array('adblade_app'=>'adrev_app'), 'adblade_app.id=appTraf.app_id', array())
            ->where('brand_publisher_id=app.user_id')
            ->where('adblade_app.user_id!=app.user_id');
        
        if (!empty($startDate)) {
            $subselect->where('appTraf.creation_date>=?', $startDate);
        }
        if (!empty($endDate)) {
            $subselect->where('appTraf.creation_date<=?', $startDate);
        }        
        
        $select = $db->select()->from(array('app'=>'adrev_app'), array(
            'publisher_id'=>'user.id',
            'publisher_name'=>'user.name',
            'organization'=>'user.organization',
            'earned_from_adblade'=>"({$subselect->__toString()})",
            'publisher_earned'=>'SUM(traff.expended-traff.earned-traff.cobrand_earned)',
            'adblade_earned'=>'SUM(traff.earned)',//gross profit
            'gross_adblade_earnings'=>'SUM(traff.expended)',
        ))
            ->join(array('traff'=>'adrev_app_zone'), 'traff.app_id=app.id', array())
            ->join(array('user'=>'adrev_users'), 'user.id=app.user_id')
            ->join(array('pub'=>'user_publisher'), 'pub.user_id=user.id', array())
            ->group('user.id');
        if (null!==$managerId) {
            $select->where('manager_id=?', $managerId);
        }
        if (!empty($startDate)) {
            $select->where('traff.creation_date>=?', $startDate);
        }
        if (!empty($endDate)) {
            $select->where('traff.creation_date<=?', $startDate);
        }
        return $select;
    }
    
    public function _getAppAdStats($appId, $adId, $startDate, $endDate)
    {        
        $db = $this->getMongoDb();
        $coll = $db->selectCollection(Adblade_Db_Collections::RAW_LOGS);
        
        $_query = array(                
            'type' => 2,
            'appId'=>$appId,
            'ads'=>array('$elemMatch'=>array('id'=>$adId))
        );
        
        if (!empty($startDate) && !empty($endDate)) {
            $_mStartId = System_Functions::dateStartToMongoId($startDate.' 00:00:00');
            $_mEndId = System_Functions::dateEndToMongoId($endDate.' 23:59:59');        
            $_query['_id'] = array('$gte'=>$_mStartId,'$lte'=>$_mEndId);
        }        
        
        $_cursor = $coll->find($_query);
        $_cursor->timeout(600*1000);
        $_data = array();
        foreach ($_cursor as $_app) {
            $_id = $_app['_id'];
            $_data[] = array(
                'dateTime'=>date('Y-m-d H:i:s', $_id->getTimestamp()),                
                'ipAddr'=>$_app['user']['ip'],
                'url'=>$_app['url'],
            );
        }        
        return $_data;
    }
    
    public function _getAppsAdStatsSelect($adId, $startDate, $endDate)
    {
        $db = $this->getDb();
        
        /* historical ecpm subquery */
        $subq_hEcpm = $db->select()
            ->from(array('aaz'=>'adrev_app_zone'), 
                            'ROUND ( IF(SUM(aaz.impressions) <> 0, SUM(aaz.expended)/SUM(aaz.impressions)*1000, 0), 2)'
                            )
            ->where('traff.app_id=aaz.app_id');
        if (null !== $startDate) {
            $subq_hEcpm->where('aaz.creation_date>=?', $startDate);
        }

        if (null !== $endDate) {
            $subq_hEcpm->where('aaz.creation_date<=?', $endDate);
        }
        
        /* today ecpm subquery */
        $subq_tEcpm = $db->select()
                    ->from(array('aaz'=>'adrev_app_zone'), 
                                                    'ROUND( IF(SUM(aaz.impressions) <> 0, SUM(aaz.expended)/SUM(aaz.impressions)*1000, 0), 2)'
                                    )
                    ->where('aaz.creation_date=?', date('Ymd'))
                    ->where('traff.app_id=aaz.app_id');

        /* adrev_app_traff sub query -- see #7458*/
        $subq_traff = $db->select()
                       ->from(array('subq_traff'=>'adrev_app_traffic'))
                       ->where('subq_traff.ad_id=?', $adId);
        if (null !== $startDate) {
            $subq_traff->where('subq_traff.creation_date>=?', $startDate);
        }
        if (null !== $endDate) {
            $subq_traff->where('subq_traff.creation_date<=?', $endDate);
        }

        /* main query */
        $select = $db->select()->from(
            array('traff' => $subq_traff)
            ,array(
                'traff.ad_id',
                'traff.app_id',
                'isFlexBid'=>'adv.allow_flex_bid',
                'bid'=>'IFNULL(assign.bid,ad.bid)',
                'SUM(traff.impressions) AS impressions', 
                'SUM(traff.clicks) AS clicks',
                'SUM(traff.expended) AS expended',
                'profitMargin'=>'ROUND(IF(SUM(traff.expended), 100 - ((SUM(traff.earned + traff.cobrand_earned) / SUM(traff.expended))*100), 0), 2)',
                'SUM(traff.orders) AS orders',
                'mobileCancelClicks'=>new Zend_Db_Expr(
                    'IF(ad_type_unit_id=2,(SELECT COUNT(*) FROM interaction_log as int_log WHERE int_log.ad_id='
                    .$adId.' AND int_log.app_id=traff.app_id'.
                    ((null!==$startDate)?(' AND int_log.creation_datetime>='.$db->quote($startDate.' 00:00:00')):' ').
                    ((null!==$endDate)?(' AND int_log.creation_datetime<='.$db->quote($endDate.' 23:59:59')):' ').
                    '),null)'
                ),
                'max_cpa'=>'IFNULL(ad.max_cpa,0)',
                'convRate'=>'ROUND(IF( traff.clicks, SUM(traff.orders)/SUM(traff.clicks), 0),2)',
                'eCPM'=>'ROUND(IF(SUM(traff.impressions), SUM(traff.expended)*1000/SUM(traff.impressions)*((zone.max_display_ads-IFNULL(zaa.ad_weight_max,0))/za.ad_weight) ,0),2 )',
                'ctr'=>'ROUND(IF(SUM(traff.impressions), SUM(traff.clicks)/SUM(traff.impressions)*100,0), 3)',
                'cpa'=>'ROUND(IF( SUM(traff.orders), SUM(traff.expended)/SUM(traff.orders),0),2)',
                'h_banner_ecpm' => new Zend_Db_Expr("({$subq_hEcpm})"),
                'banner_ecpm' => new Zend_Db_Expr("({$subq_tEcpm})"),
                'media_cost' => new Zend_Db_Expr("({$this->_getAppMediaCostSqlExpr()})"),
            )
        )
        ->join(array('app'=>'adrev_app'), 'app.id=traff.app_id', array(
                'appName'=>'app_name','app_extended_id'=>'extended_id'
        ))
        ->join(array('appOpt'=>App_Model_Tables::APP_OPTIONS), 'appOpt.app_id=app.id', array(
                'master_app_id','master_app_use_flexbid'
        ))
        ->join(array('ad'=>'adrev_ads'), 'ad.id=traff.ad_id', array())
        ->join(array('ad_types','adrev_ad_types'), 'ad_types.id=ad.ad_type_id', array('ad_type_unit_id'))
        ->joinLeft(array('nl'=>'newsletter_app'), 'app.id=nl.app_id', 
                new Zend_Db_Expr('IFNULL(is_published,0) AS app_is_published'))            
        ->join(array('zone'=>'adrev_zones'), 'zone.id=app.zone_id', array())
        ->joinLeft(array('za'=>'zone_ad_type'), 'za.zone_id=zone.id and za.ad_type_id=ad.ad_type_id', array())
        ->joinLeft(array('zaa'=>'zone_ad_type'), 
                'zaa.zone_id=zone.id and zaa.ad_type_id='.App_Model_DbTable_AdTypes::FEED_ARTICLE, array())
        ->join(array('user'=>'adrev_users'), 'user.id=app.user_id', array())
        ->joinLeft(array('assign'=>'app_ad_assn'), 'assign.app_id=app.id AND assign.ad_id=ad.id',
                   array('assignAdId'=>'assign.ad_id', 'assignAppId'=>'assign.app_id'))
        ->joinLeft(array('appAdStatus'=>'app_ad_statuses'), 'appAdStatus.id=assign.status_id',
                   array('appAdStatusName'=>'name','appAdStatusId'=>'id'))
        ->joinLeft(array('appAdCreator'=>'app_ad_creators'), 'assign.creator_id=appAdCreator.id',
                   array('creator_short_name'=>'short_name'))
        ->joinLeft(array('adv'=>'user_advertiser'), 'ad.user_id=adv.user_id', array())
        ->joinLeft(array('ao' => 'app_options'), 'ao.app_id = app.id', array())
        ->joinLeft(array('ars' => Adblade_Db_Tables::APP_REVENUE_SHARE), 'ars.app_id = app.id', array())
        ->joinLeft(array('up' => 'user_publisher'), 'up.user_id = user.id', array())
        ->group('traff.app_id');
        
        return $select;
    }
    
    /**
     * @return string
     */
    protected function _getAppMediaCostSqlExpr()
    {
        $expr = 'CASE'
              . ' WHEN ao.floor_guaranteed_cpm IS NOT NULL AND ars.share_cpc IS NOT NULL THEN "Rev Share - CPC"'
              . ' WHEN ao.floor_guaranteed_cpm IS NOT NULL AND ars.share_cpm IS NOT NULL THEN "Rev Share - eCPM"'
              . ' WHEN up.partner_type_id = 1 AND ars.share_cpc IS NOT NULL THEN "Media Buy - CPC"'
              . ' WHEN up.partner_type_id = 1 AND ars.share_cpm IS NOT NULL THEN "Media Buy - CPM"'
              . 'ELSE "-"'
              . 'END';
        
        return $expr;
    }
    
    /**
     * Get publishers earnings
     *
     * If last param provided, only signle accounts(not assigned to masters) 
     * and masters accounts(grouped assinged users) will be selected
     * 
     * @param string $startDate
     * @param string $endDate
     * @param bool $masterAccounts
     * @return array
     */
    public function _getPublishersEarningsSelect($startDate = null, $endDate = null,$masterAccounts=false, $site=null)
    {
        $db = $this->getDb();
        //Select earnings for cobrand, cobrand can't earn this money on own tags,
        //it has to be tags, which belongs to another users. Thanks whay "EXISTS" is applied
        
        /* Introduced in Task #7377: subquery on adrev_app_traffic. 
         * When using adrev_app_traffic, "Last Month" takes over 2  hours to run.
         * If adrev_app_traffic is subqueried to limit the time period, "Last Month" takes less than 60 seconds.
         */
        $appTrafficSubqByAd = $db->select()
                                ->from('adrev_app_traffic',array(
                                            'ad_id'=>'ad_id',
                                            'cobrand_earned'=> 'SUM(cobrand_earned)')
                                )
                                ->group('ad_id');
                                
        $appTrafficSubqByApp = $db->select()
                                ->from('adrev_app_traffic',array(
                                        'app_id'=>'app_id',
                                        'earned'=> 'SUM(earned)')
                                )
                                ->group('app_id');
                                
        if ($startDate !== null) {
            $appTrafficSubqByAd->where('creation_date>=?',$startDate); 
            $appTrafficSubqByApp->where('creation_date>=?',$startDate);
        }
        if ($endDate !== null) {
            $appTrafficSubqByAd->where('creation_date<=?',$endDate);
            $appTrafficSubqByApp->where('creation_date<=?',$endDate);
        }
        /*end subquery definitions*/
        
        $selectCobrandEarnings = $db->select()
          ->from(array('advertiser'=>'user_advertiser'),
                 array('cobrand_earned'=>'IFNULL(SUM(appTraf.cobrand_earned),0)'))

          ->join(array('ads'=>'adrev_ads'), 'ads.user_id=advertiser.user_id', array())
          ->join(array('appTraf'=>$appTrafficSubqByAd),
              'appTraf.ad_id=ads.id AND NOT EXISTS 
                  (SELECT * FROM adrev_app as app where app.id=app_id and app.user_id=advertiser.brand_publisher_id)',
                    array())
          ->where('`advertiser`.`brand_publisher_id`=users.id');        
        
        
        $select = $db->select()
          ->from(array('users'=>'adrev_users'),
                 array('earned'=>'IFNULL(SUM(appTraf.earned),0)',
                       'cobrand_earned'=>'IFNULL( ('. $selectCobrandEarnings->__toString() .'),0.00 )',
                       'appsCount'=>'(SELECT COUNT(*) FROM adrev_app WHERE user_id=users.id)',
                      'users.id','users.name','users.organization','users.admin','users.email',
                      'site_name'=>'sites.name',
                      'balance'=>'users.balance'))
          ->join(array('sites'), 'sites.id=users.site_id', array())
          ->join(array('apps'=>'adrev_app'), 'apps.user_id=users.id', array())
          ->joinLeft(array('appTraf'=>$appTrafficSubqByApp), 'appTraf.app_id=apps.id', array())
          ->joinLeft(array('up'=>'user_publisher'),'users.id=up.user_id',array())
          ->joinLeft(array('upt'=>'publisher_types'),'up.partner_type_id=upt.id',array('publisher_type_name'=>'name'))
          ->group('users.id')
          ->where('users.site_id IN (?)', $this->_siteIds)
          ->where('users.admin in (2, 3)')
          ->where('users.status=1');
        if (null!==$site) {
            $select->where('users.site_id IN (?)', $site);
        }
        //Group publishers by master account and excluding grouped publishers from report
        //So, all publishers are grouped by master account in one record
        if ( true === $masterAccounts ) {
            $select->where(new Zend_Db_Expr('NOT EXISTS 
                (SELECT * FROM user_master WHERE user_master.user_id=users.id)'));
            
            $selectCobrandEarnings
                ->reset('where')
                ->where( new Zend_Db_Expr('EXISTS (SELECT * FROM user_master um 
                        WHERE `um`.`master_id`=`users`.`id` AND `advertiser`.`brand_publisher_id`=um.user_id)'));

            
            //UNION masters
            $masters = $db->select()
              ->from(array('users'=>'adrev_users'),
                     array('earned'=>'IFNULL(SUM(appTraf.earned),0)',
                           'cobrand_earned'=>'IFNULL( ('.$selectCobrandEarnings->__toString().'),0.00)',
                           'appsCount'=>'(SELECT COUNT(*) FROM adrev_app 
                                JOIN user_master AS um 
                                    WHERE um.master_id=users.id AND um.user_id=adrev_app.user_id)',
                           'users.id','users.name','users.organization','users.admin','users.email',
                           'site_name'=>'sites.name',
                           'balance'=>'(SELECT SUM(balance) FROM adrev_users
                                JOIN user_master AS um 
                                    WHERE um.master_id=users.id AND um.user_id=adrev_users.id)'))
              ->join(array('sites'), 'sites.id=users.site_id', array())
              ->joinLeft('user_master', 'user_master.master_id=users.id', array())
              ->joinLeft(array('apps'=>'adrev_app'), 'apps.user_id=user_master.user_id', array())
              ->joinLeft(array('appTraf'=>$appTrafficSubqByApp), 'appTraf.app_id=apps.id', array())
              ->joinLeft(array('up'=>'user_publisher'),'users.id=up.user_id',array())
              ->joinLeft(array('upt'=>'publisher_types'),'up.partner_type_id=upt.id',array('publisher_type_name'=>'name'))
              ->group('users.id')
              ->where('users.admin=?', UsersTable::MASTER_ROLE)
              ->where('users.site_id IN (?)', $this->_siteIds)
              ->where('users.status=1');
            if (null!==$site) {
                $select->where('users.site_id IN (?)', $site);
            }
            $select = $db->select()->union(array($select,$masters))
                        ->order('id');
                      
        }
        
        $selectFinal = $db->select()->from(array('results'=>$select), array(
            'results.*','publisher_earned'=>new Zend_Db_Expr('earned+cobrand_earned')
        ));
        return $selectFinal;
    }
    
    protected function _sendPacingReport($userId, $email, $interval, $startDate, $endDate, $siteId)
    {           
        $fields = array('groupName','budget','expended','start_date','end_date','pacing');
        
        //need to send only if stats exists for selected date range
        $fileName = "group_pacing_report_account_{$userId}";//_{$startDate}-{$endDate}";
        $model = App_Model_Models::getAdminAdsModel();
        $grid = $model->getAdGroupReportsGrid($userId, false);                
        $grid->setExportIncludeFields($fields);        
        $grid->setExportable(true)->setExportAdapter(
            array('name'=>'csv','options'=>array('fileName'=>$fileName))
        );
        $exportObject = $grid->getExportFile();
        if (!$exportObject->isEmpty()) {
            $at              = new Zend_Mime_Part( $exportObject->getData() );
            $at->type        = $exportObject->getMimeType() ;//Zend_Mime::TYPE_OCTETSTREAM;
            $at->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
            $at->encoding    = Zend_Mime::ENCODING_BASE64;
            $at->filename    = $exportObject->getFilename();
            
            $params = array(
                'name'       => 'Subscriber',
                'user_id'    => $userId,
                'rep_period' => ucfirst($interval)
            );
            
            $mailer = App_Di::instance()->getMailer($siteId);
            $mail   = $mailer->createMail(App_Model_Mailer::ADVERTISER_PACING_REPORT, $params);
            $mail->addTo($email)
                 ->addAttachment($at)
                 ->send();
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get stats for new advertisers
     * 
     * @return System_Grid_DataGrid
     */
    public function getNewAdvertiserStatsGrid()
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        
        /*
         * Init Dates filter
         */
        $grid->setFilters(array('datesUserRegistered','datesExpended','accountManager','salesRep', $this->_sitesFilter));
        $creationDate = $grid->getFilter('DatesUserRegistered');
        $creationDate->setDefault(System_Search_Date::THIS_MONTH);
        $creationStartDate = $creationDate->getValue('startCalendarDate');
        $creationEndDate   = $creationDate->getValue('endCalendarDate');
        $creationDate->setLabel('User Registered Date Range');
        
        $expendedDate = $grid->getFilter('DatesExpended');
        $expendedDate->setDefault(System_Search_Date::THIS_MONTH);
        $expendedStartDate = $expendedDate->getValue('startCalendarDate');
        $expendedEndDate   = $expendedDate->getValue('endCalendarDate');
        $expendedDate->setLabel('Expended Date Range');
                
        /*
         * Add custom options to the AccountManager filter
         */
        $options  = array('unassigned' => '- Unassigned -');
        $managers = App_Model_Tables::getUsersTable()->getAllAccountManagers();
        foreach ($managers as $mgr) {
            $options[$mgr['id']] = $mgr['name'];
        }
        $grid->getFilter('AccountManager')->setOptions($options);
        
        /*
         * Add custom options to the SalesRep filter
         */
        $options  = array('unassigned' => '- Unassigned -');
        $sales    = App_Model_Tables::getUsersTable()->getSalesList();
        foreach ($sales as $rep) {
            $options[$rep['id']] = $rep['name'];
        }
        $grid->getFilter('SalesRep')->setOptions($options);
        
        $accMgrId   = $grid->getFilter('AccountManager')->getValue();
        $salesRepId = $grid->getFilter('SalesRep')->getValue();
        
        /*
         * Set export options
         */
        $filename = "new_advertisers_stats_{$creationStartDate}-{$creationEndDate}";
        $grid->setExportable(true)
             ->setExportFilename($filename)
             ->setExportIncludeFields(array(
                 'id','name','organization', 'ads_type_note', 'email',
                 'period_expended','total_expended','balance',
                 'account_manager_name','sales_rep_name','status', 'creation_date'
             ));
        
        /* set up sorting and columns */
        $grid->setSortable(true)
             ->setLimit(150)
             ->setDefaultSort('creation_date')
             ->setTemplatePart('body', 'reports/grid/new-adv-stats-body.phtml');
                     
        $grid->addColumn('id', array('header' => 'UID'))
             ->addColumn('name', array('header' => 'Name'))
             ->addColumn('organization', array('header' => 'Organization'))
             ->addColumn('ads_type_note', array('header' => 'Note'))
             ->addColumn('email', array('header' => 'Email'))
             ->addColumn('period_expended', array('header' => 'Period Expended', 'align'=>'right', 'type'=>'money'))
             ->addColumn('total_expended', array('header' => 'Total Expended', 'align'=>'right', 'type'=>'money'))
             ->addColumn('balance', array('header' => 'Balance', 'align'=>'right', 'type'=>'money'))
             ->addColumn('account_manager_name', array('header' => 'Account Manager'))
             ->addColumn('sales_rep_name', array('header' => 'Sales Rep'))
             ->addColumn('status', array('header' => 'Status'))
             ->addColumn('creation_date', array('header' => 'Registered'));
                     
        $db = $this->getDb(true);

        /*
         * subqueries
         */
        $totalExpended = $db->select()
                            ->from(array('traff'=>'adrev_app_traffic'), 'IFNULL(SUM(traff.expended),0)')
                            ->join(array('ad'=>'adrev_ads'), 'traff.ad_id=ad.id', array())
                            ->where('ad.user_id=user.id');
        
        $subqAppTraff = $db->select()
                            ->from(array('aat'=>'adrev_app_traffic'), array('ad_id'=>'ad_id', 'expended'=>'SUM(expended)'))
                            ->group('ad_id');

        if (!is_null($expendedStartDate) && !is_null($expendedEndDate)) {
            $subqAppTraff->where('aat.creation_date>=?', $expendedStartDate)
                    ->where('aat.creation_date<=?', $expendedEndDate);
        }
        
        $periodExpended = $db->select()
                            ->from(array('traff'=>$subqAppTraff), 'IFNULL(SUM(traff.expended),0)')
                            ->join(array('ad'=>'adrev_ads'), 'traff.ad_id=ad.id', array())
                            ->where('ad.user_id=user.id');
        
        
        /* columns to return */
        $cols = array(
            'id',
            'name',
            'organization',
            'email',
            'balance',
            'status'=> new Zend_Db_Expr("IF(user.status = 1, 'Active', 'Inactive')"),
            'creation_date',
            'total_expended'=> new Zend_Db_Expr("({$totalExpended})"),
            'period_expended'=> new Zend_Db_Expr("({$periodExpended})"),
            'sales_rep_name'=> "IFNULL(salesrep.name, '- Unassigned - ')",
            'account_manager_name'=> "IFNULL(accmgr.name,'- Unassigned - ')"
            );
            
        $select = $db->select()
                    ->from(array('user'=>'adrev_users'), $cols)
                    ->joinLeft(array('salesrep'=>'adrev_users'), 'user.salesRep=salesrep.id', array())
                    ->joinLeft(array('accmgr'=>'adrev_users'), 'user.account_manager_user_id=accmgr.id', array())
                    ->joinLeft(array('adv'=>'user_advertiser'), 'user.id=adv.user_id', array(
                            'ads_type_note' => new Zend_Db_Expr("IFNULL(adv.ads_type_note, '')")
                        ))
                    ->where('user.admin=?', UsersTable::ADVERTISER_ROLE);

        if ($accMgrId) {
            $accMgrId = $accMgrId == 'house' ? 0 : $accMgrId;
            $select->where('user.account_manager_user_id = ?', $accMgrId);
        }
        
        if ($salesRepId) {
            $salesRepId = $salesRepId == 'house' ? 0 : $salesRepId;
            $select->where('user.SalesRep = ?', $salesRepId);
        }
                    
        if (!is_null($creationStartDate) && !is_null($creationEndDate)) {
            $select->where('user.creation_date >=?', $creationStartDate)
                   ->where('user.creation_date <=?', $creationEndDate);
        }

        $site = $this->_sitesFilter->getValue();
        if (!is_null($site)) {
            $select->where('user.site_id = ?', $site);
        }

        $finalSelect = $db->select()->from(array('results'=>$select));   // workaround for Grid bug
        $grid->setSelect($finalSelect);

        return $grid;
	}

        /**
         * Returns a dataset that includes the relative ranking of an
         * ad in all the apps it appears in. Ranking is based on same
         * category. For example, if ad is PN, return the ranking
         * relative to other PN ads.
         * First Introduced in Task #5010
         * 
         * @param type $adId, $startDate, $endDate
         */
        protected function _addRankData($adId, &$data)
        {
            $db = $this->getDb(true);
            
            $todaysDate = date('Ymd');
            
            // retrieve the apps
            $sql = $db->select()
                   ->distinct()
                   ->from(array('traffic'=>'adrev_app_traffic'), array(
                       'app_id',
                    ))
                   ->where('ad_id=?', $adId)
                   ->where('creation_date=?', $todaysDate);
            
            $appList = $db->fetchCol($sql);
            
            if (empty($appList)) {
                foreach ($data as $key=>$row) {
                    $data[$key]['rank'] = 'N/A';
                }
                return;
            }

            // retrieve the stats filtered by apps retrieved in previous step
            $adTargetExpr = $this->getHelper('adblade')->getAdTargetExpr();
            $adTargetType = App_Model_Tables::getAdsTable()->getAdTargetType($adId);
            $statsSql = $db->select()
                ->from(array('traffic'=>'adrev_app_traffic'), array(
                    'traffic.app_id',
                    'traffic.ad_id',
                    'ad_target'=> $adTargetExpr,
                    //@TODO not sure about this ecpm - exclude articles weight?
                    'banner_ecpm'=>'SUM(traffic.expended)/SUM(traffic.impressions/zone.max_display_ads)*1000'
                 ))
                ->join(array('app'=>'adrev_app'), 'traffic.app_id=app.id', array())
                ->join(array('zone'=>'adrev_zones'), 'app.zone_id=zone.id', array())
                ->join(array('ads'=>'adrev_ads'),'traffic.ad_id=ads.id', array())
                ->where('app_id in (?)', $appList)
                ->where('creation_date=?', $todaysDate)
                ->group('traffic.app_id')
                ->group('traffic.ad_id')
                ->order('traffic.app_id')
                ->order('banner_ecpm desc')
                ->having('ad_target=?',$adTargetType);
            
            // rank the data
            // Note: Zend_Db_Select does not support multiple FROMs which forced
            // me to write query explicitly
            $rankSql = 'SELECT ' .
                    'app_id, ad_id, ad_target, banner_ecpm,' .
                    '@ad:=CASE WHEN @app <> app_id THEN 1 ELSE @ad+1 END as rank,' .
                    '@app:=app_id as app ' .
                    'from ' .
                    '(SELECT @app:=0) a,' .
                    '(SELECT @ad:=0) b,' . 
                    '(' . $statsSql . ') qstats';
            $ranked = $db->fetchAll($rankSql);
            
            // index the data for quicker retrieval
            $rankedData = array();
            foreach($ranked as $row) {
                $rankedData[$row['app_id']][$row['ad_id']] = $row['rank'];
            }

            // modify passed array with rank data
            foreach ($data as $key=>$row) {
                $appId = $row['app_id'];
                $adId = $row['ad_id'];
                if (isset($rankedData[$appId][$adId])) {
                    $data[$key]['rank'] = $rankedData[$appId][$adId];
                } else {
                    $data[$key]['rank'] = 'N/A';
                }
            }
        }
        
    public function getAdsRankingStats()
    {
        $onlyAdjustedEcmpOver1 = new System_Search_Filters_Checkbox();
        $onlyAdjustedEcmpOver1
            ->setLabel('Only Adjusted eCPM')
            ->setDefaultValue(false);
        
        $datesStats = new System_Search_Filters_DatesJQ();
        $datesStats->setDefault(System_Search_Date::TODAY);
        $datesStats->setLabel('Stats Date');
        
        $datesLive = new System_Search_Filters_DatesJQ();
        $datesLive->setDefault(System_Search_Date::THIS_MONTH);
        $datesLive->setLabel('Live Date');
        
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(true)
            //show all ads, it works faster, because grid doesn't do "count(*)" of the select
            ->setLimit(-1)
            ->setFilters(array($datesStats, $datesLive, 'accountManager','salesRep', 'adId', $onlyAdjustedEcmpOver1))
            ->setDefaultSort('ad_id')
            ->setDefaultDir('asc')
            ->setShowTotals(false)
        ;
        
        $options  = array('unassigned' => '- Unassigned -');
        $managers = App_Model_Tables::getUsersTable()->getAllAccountManagers();
        foreach ($managers as $mgr) {
            $options[$mgr['id']] = $mgr['name'];
        }
        $grid->getFilter('AccountManager')->setOptions($options);
        
        $options  = array('unassigned' => '- Unassigned -');
        $sales    = App_Model_Tables::getUsersTable()->getSalesList();
        foreach ($sales as $rep) {
            $options[$rep['id']] = $rep['name'];
        }
        $grid->getFilter('SalesRep')->setOptions($options);
        
        $grid->addColumn('ad_id', array('header'=>'Ad ID','width'=>25,'sortable'=>true, 'type'=>'link', 'links'=>'/admin/ads/edit-ad/adId/$ad_id', 'title'=>'Edit Ad $ad_id'))
             ->addColumn('body', array('header' => 'Body','width'=>302,'type'=>'script', 'sortable'=>false, 'scriptPath'=>'reports/grid/ads-ranking-iframe-body.phtml'))
             ->addColumn('ad_live_date',array('header'=>'Live Date','sortable'=>true))
             ->addColumn('traff_impressions',array('header'=>'Impressions','sortable'=>false,'align'=>'right'))
             ->addColumn('traff_clicks',array('header'=>'Clicks','sortable'=>false,'align'=>'right'))
             ->addColumn('traff_ctr',array('header'=>'CTR','sortable'=>false,'align'=>'right'))
             ->addColumn('opt_ad_quality_score',array('header'=>'Quality Score','sortable'=>false,'align'=>'right'))
             ->addColumn('traff_expended',array('header'=>'Expended','sortable'=>false,'align'=>'right'))
             ->addColumn('traff_ecpm',array('header'=>'eCPM','sortable'=>false,'align'=>'right'))
             ->addColumn('traff_profit',array('header'=>'Profit %','sortable'=>false,'align'=>'right'))
             ->addColumn('overallECPM',array('header'=>'Adjusted eCPM','sortable'=>true,'align'=>'right'))
             ->addColumn('account_manager_name', array('header' => 'Account Manager'))
             ->addColumn('sales_rep_name', array('header' => 'Sales Rep'))
        ;
        
        $accountManagerId = $grid->getFilter('AccountManager')->getValue();
        $salesRepId = $grid->getFilter('SalesRep')->getValue();
        
        $datesStatsStart = $datesStats->getValue('startCalendarDate');
        $datesStatsEnd   = $datesStats->getValue('endCalendarDate');
        
        $datesLiveStart = $datesLive->getValue('startCalendarDate');
        $datesLiveEnd   = $datesLive->getValue('endCalendarDate');

        $adId = $grid->getFilter('AdId')->getValue();
        
        $select = $this->getDb(true)->select();
        $select->from(
                    array('ads'=>'adrev_ads')
                    ,array(
                        'ad_id'=>'id'
                        ,'ad_rate_type'
                        ,'ad_live_date'=>'DATE_FORMAT(ads.initial_approve_datetime,"%b %d %Y")'
                    )
                )
                ->joinLeft(
                    array('users'=>'adrev_users')
                    ,'users.id = ads.user_id'
                    ,array('Salesrep', 'account_manager_user_id')
                )
                ->joinLeft(
                    array('salesRepUsers'=>'adrev_users')
                    ,'salesRepUsers.id = users.Salesrep'
                    ,array(
                        'sales_rep_name'=> "IFNULL(salesRepUsers.name, '- Unassigned - ')"
                    )
                )
                ->joinLeft(
                    array('accountManagerUsers'=>'adrev_users')
                    ,'accountManagerUsers.id = users.account_manager_user_id'
                    ,array(
                        'account_manager_name'=> "IFNULL(accountManagerUsers.name,'- Unassigned - ')"
                    )
                )
                ->joinLeft(
                    array('traff'=>'adrev_app_traffic')
                    ,"traff.ad_id = ads.id"
                    ,array(
                        'traff_expended'=>'ROUND(SUM(traff.expended),2)'
                        ,'traff.app_id'
                        ,'traff_impressions'=>'SUM(traff.impressions)'
                        ,'traff_clicks'=>'SUM(traff.clicks)'
                        ,'traff_ctr'=>'ROUND(IF(SUM(traff.impressions), SUM(traff.clicks)/SUM(traff.impressions)*100,0),2)'
                        ,'traff_profit' => 'IF(SUM(traff.expended) > 0, ROUND(((SUM(traff.expended)-SUM(traff.earned)-SUM(traff.cobrand_earned))/SUM(traff.expended))*100, 2), 0)'
                        ,'traff_ecpm'=>'ROUND(IF(ads.ad_rate_type=\'CPC\', IF(SUM(traff.impressions)>0, SUM(traff.expended)*1000/SUM(traff.impressions), 0), ads.bid),2)'
                    )
                )
                ->joinLeft(
                    array('options'=>'ad_options')
                    ,'options.ad_id=ads.id'
                    ,array('opt_ad_quality_score'=>'IFNULL(options.ad_quality_score, "N/A")')
                )
                ->joinInner(
                    array('app'=>'adrev_app')
                    ,'app.id = traff.app_id'
                )
                ->where('ads.initial_approve_datetime IS NOT NULL')
                ->group('traff.ad_id');
        
        if ($adId) {
            $select->where('ads.id=?', $adId);
        }
        
        
        if (!is_null($datesLiveStart)) {
            $select->where('initial_approve_datetime >= ?', $datesLiveStart . ' 00:00:00');
        }
        if (!is_null($datesLiveEnd)) {
            $select->where('initial_approve_datetime <= ?', $datesLiveEnd . ' 23:59:59');
        }
        
        if (!is_null($datesStatsStart)) {
            $select->where('traff.creation_date>=?', $datesStatsStart);
        }
        if (!is_null($datesStatsEnd)) {
            $select->where('traff.creation_date<=?', $datesStatsEnd);
        }
        
        if ($accountManagerId) {
            $select->where('users.account_manager_user_id = ?', $accountManagerId);
        }
        if ($salesRepId) {
            $select->where('users.SalesRep = ?', $salesRepId);
        }
        
        $oecpm = $this->getTable()->getAdsTable()->getAdsOverallEcpmSelect($datesStatsStart, $datesStatsEnd);
        $finalSelect = $this->getDb(true)->select()
            ->from(
                array('result' => new Zend_Db_Expr('('.$select->__toString().')'))
            )
            ->joinLeft(
                array('oecpm' => new Zend_Db_Expr('('.$oecpm->__toString().')'))
                ,'result.ad_id = oecpm.id'
                ,array('overallECPM'=>'ROUND(IF(oecpm.overallECPM,oecpm.overallECPM,0),2)')
            );
        
        if (true ===  $onlyAdjustedEcmpOver1->getValue()) {
            $finalSelect->where('oecpm.overallECPM > 1');
        } 

        $grid->setSelect($finalSelect);
        return $grid;
    }

    public function getAdvertiserByPublisherGrid($shouldRunReport=true)
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(true)
            ->setLimit(-1)
            ->setFilters(array('Dates', 'publisher', 'checkbox', $this->_sitesFilter))
            ->setDefaultSort('clicks')
            ->setDefaultDir('desc')
            ->setShowTotals(true)
        ;
        $checkbox = $grid->getFilter('Checkbox');
        $checkbox->setLabel('Include Advertiser Contact Info');

        $publisher = $grid->getFilter('Publisher')->getValue();

        $site = $this->_sitesFilter->getValue();

        $grid->addColumn('pub_name', array('header'=>'Publisher', 'sortable'=>true, 'type'=>'text'))
             ->addColumn('adv_id', array('header'=>'Advertiser ID', 'sortable'=>true))
             ->addColumn('adv_name', array('header'=>'Advertiser Name', 'sortable'=>true, 'type' => 'text'))
             ->addColumn('clicks', array('header'=>'Clicks', 'sortable'=>true, 'type' => 'number', 'showTotals' => true))
             ->addColumn('cost', array('header'=>'Cost', 'sortable'=>true, 'type' => 'money', 'showTotals' => true))
             ->addColumn('conversions', array('header'=>'Conversions', 'sortable'=>true, 'type' => 'number', 'showTotals' => true))
             ->addColumn('created', array('header'=>'Created', 'sortable'=>true, 'type' => 'text'))
             ->addColumn('last_billed_click', array('header'=>'Last Billed Click', 'sortable'=>true, 'type' => 'text'))
        ;

        $includeContactInfo = $checkbox->getValue();
        
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate   = $grid->getFilter('Dates')->getValue('endCalendarDate');

        $filename = sprintf('advertiser-by-publisher-%s-%s', $startDate, $endDate);
        if ($publisher) {
            $filename = sprintf('advertiser-by-publisher-%d-%s-%s', $publisher, $startDate, $endDate);
        }

        $subSelect = $this->getDb(true)->select()
            ->from(array('t'=>Adblade_Db_Tables::ADREV_APP_TRAFFIC), array(
                'pub_id'=>'app.user_id', 'adv_id'=>'ad.user_id',
                'clicks'            => 'SUM(t.clicks)',
                'earned'            => 'SUM(t.earned)',
                'orders'            => 'SUM(t.orders)',
                'creation_date'     => 'MAX(t.creation_date)'
            ))
            ->join(array('app'=>Adblade_Db_Tables::ADREV_APP), 'app.id=t.app_id', array())
            ->join(array('ad'=>'adrev_ads'),   'ad.id = t.ad_id', array())
            ->group(array('app.user_id','ad.user_id'));

        if (null!==$startDate) {
            $subSelect->where('t.creation_date >= ?', $startDate);
        }
        if (null!==$endDate) {
            $subSelect->where('t.creation_date <= ?', $endDate);
        }
        if ($publisher) {
            $subSelect->where('app.user_id = ?', $publisher);
        }

        $select = $this->getDb(true)->select()
            ->from(array('traffic'=>$subSelect), array(
                'pub_name'          => 'pu.organization',
                'adv_id'            => 'au.id',
                'adv_name'          => 'au.organization',
                'clicks'            => 'traffic.clicks',
                'cost'              => 'traffic.earned',
                'conversions'       => 'traffic.orders',
                'created'           => 'au.creation_date',
                'last_billed_click' => 'DATE_FORMAT(traffic.creation_date, "%b %d %Y")',
            ))
            ->join(array('pu'=>'adrev_users'), 'pu.id = traffic.pub_id', array())
            ->join(array('au'=>'adrev_users'), 'au.id = traffic.adv_id', array(
                'name'        => 'IF(uao.brand_publisher_id=pu.id, au.name,"")',
                'email'       => 'IF(uao.brand_publisher_id=pu.id, au.email,"")',
                'phone'       => 'IF(uao.brand_publisher_id=pu.id, au.telephone,"")',
                'city'        => 'IF(uao.brand_publisher_id=pu.id, au.city,"")',
                'state'       => 'IF(uao.brand_publisher_id=pu.id, au.us_states,"")',
                'postal_code' => 'IF(uao.brand_publisher_id=pu.id, au.postalcode,"")',
            ))
            ->join(array('uao'=>Adblade_Db_Tables::USER_ADVERTISER), 'uao.user_id=au.id', array());

        if (!is_null($site)) {
            $select->where('pu.site_id = ?', $site);
        }

        if ($includeContactInfo) {
            $grid->addColumn('name', array('header'=>'Contact Name', 'sortable'=>false, 'type' => 'text'))
                 ->addColumn('email', array('header'=>'Contact Email', 'sortable'=>false, 'type' => 'text'))
                 ->addColumn('phone', array('header'=>'Contact Phone', 'sortable'=>false, 'type' => 'text'))
                 ->addColumn('city', array('header'=>'Contact City', 'sortable'=>false, 'type' => 'text'))
                 ->addColumn('state', array('header'=>'Contact State', 'sortable'=>false, 'type' => 'text'))
                 ->addColumn('postal_code', array('header'=>'Contact Zip', 'sortable'=>false, 'type' => 'text'))
            ;
        }

        $grid->setExportable(true)->setExportFilename($filename);
        $_fields = array();
        foreach ($grid->getColumns() as $column ) {
            $_fields[]=$column->getId();
        }
        $grid->setExportColumns($_fields);

        if ($shouldRunReport) {
            $grid->setSelect($select);
        } else {
            $grid->setEmptyText('Please use the form to search for results.');
            $grid->setDataSource(new System_Grid_DataGrid_DataSource_Array(array()));
        }

        return $grid;
    }

    public function getSectionRevenueStatsGrid() {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());

        $includeContainerAppCheckbox = new System_Search_Filters_Checkbox();
        $publisherDropDown           = new System_Search_Filters_Publisher();

        $publisherDropDown->usePublishersWithSections();

        $includeContainerAppCheckbox->setLabel('Include container/app');

        $grid->setSortable(true)
             ->setLimit(-1)
             ->setFilters(array('Dates', $publisherDropDown, $includeContainerAppCheckbox))
             ->setDefaultSort('clicks')
             ->setDefaultDir('desc')
             ->setShowTotals(true)
        ;

        $shouldShowContainerAndApp = $includeContainerAppCheckbox->getValue();
        $publisher                 = $publisherDropDown->getValue();
        $startDate                 = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate                   = $grid->getFilter('Dates')->getValue('endCalendarDate');

        $groups  = array('zone.creation_date', 'pub.organization', 'pub.id', 'sec.name', 'sec.id');
        $columns = array(
            'date'         => 'zone.creation_date',
            'publisher'    => 'pub.organization',
            'publisher_id' => 'pub.id',
            'section'      => 'sec.name',
            'section_id'   => 'sec.id',
        );

        if ($shouldShowContainerAndApp) {
            $columns = array_merge($columns, array( 'container' => 'con.name', 'cid' => 'CONCAT_WS("-", con.id, con.uid)', 'app' => 'app.app_name', 'app_id' => 'app.id',));
            array_push($groups, 'con.name', 'cid', 'app.app_name', 'app.id');
        }

        $moreColumns = array(
            'clicks'                => 'SUM(zone.clicks)',
            'impressions'           => 'SUM(zone.impressions)',
            'expended'              => 'SUM(zone.expended)',
            'earned'                => 'SUM(zone.earned)',
            'expended_minus_earned' => 'SUM(zone.expended) - SUM(zone.earned)',
            'ecpm'                  => '((SUM(zone.expended) - SUM(zone.earned))/SUM(zone.impressions))*1000',
        );

        $columns = array_merge($columns, $moreColumns);

        $grid->addColumn('date', array('header'=>'Date', 'sortable'=>true, 'type'=>'date'))
             ->addColumn('publisher', array('header'=>'Publisher', 'sortable'=>true))
             ->addColumn('publisher_id', array('header'=>'Publisher ID', 'sortable'=>true))
             ->addColumn('section', array('header'=>'Section', 'sortable'=>true))
             ->addColumn('section_id', array('header'=>'Section ID', 'sortable'=>true));

        if ($shouldShowContainerAndApp) {
            $grid->addColumn('container', array('header'=>'Container', 'sortable'=>true))
                 ->addColumn('cid', array('header'=>'CID', 'sortable'=>true))
                 ->addColumn('app', array('header'=>'App', 'sortable'=>true))
                 ->addColumn('app_id', array('header'=>'App ID', 'sortable'=>true));
        }


        $grid->addColumn('clicks', array('header'=>'Clicks', 'sortable'=>true, 'showTotals'=>true, 'type'=>'number'))
             ->addColumn('impressions', array('header'=>'Impressions', 'sortable'=>true, 'showTotals'=>true, 'type'=>'number'))
             ->addColumn('expended', array('header'=>'Gross Revenue', 'sortable'=>true, 'showTotals'=>true, 'type'=>'money'))
             ->addColumn('earned', array('header'=>'Net to Adblade', 'sortable'=>true, 'showTotals'=>true, 'type'=>'money'))
             ->addColumn('expended_minus_earned', array('header'=>'Publisher Revenue', 'sortable'=>true, 'showTotals'=>true, 'type'=>'money'))
             ->addColumn('ecpm', array('header'=>'ECPM', 'sortable'=>true, 'showTotals'=>false, 'type'=>'money'))
        ;

        $select = $this->getDb(true)->select();
        $select->from(
                   array('zone'=>'adrev_app_zone'),
                   $columns
               )
               ->join(
                   array('app'=>'adrev_app'),
                   'app.id = zone.app_id',
                   array()
               )
               ->join(
                   array('con'=>'app_containers'),
                   'app.container_id = con.id',
                   array()
               )
               ->join(
                   array('sec'=>'publisher_sections'),
                   'con.section_id = sec.id',
                   array()
               )
               ->join(
                   array('pub'=>'adrev_users'),
                   'app.user_id = pub.id',
                   array()
               )
               ->group($groups);

        if (!is_null($startDate)) {
            $select->where('zone.creation_date >= ?', $startDate);
        }

        if (!is_null($endDate)) {
            $select->where('zone.creation_date <= ?', $endDate);
        }

        $filename = sprintf('section-revenue-stats-%s-%s', $startDate, $endDate);
        if ($publisher) {
            $filename = sprintf('section-revenue-stats-%d-%s-%s', $publisher, $startDate, $endDate);
            $select->where('pub.id = ?', $publisher);
        }

        $grid->setExportable(true)->setExportFilename($filename);
        $_fields = array();
        foreach ($grid->getColumns() as $column ) {
            $_fields[]=$column->getId();
        }
        $grid->setExportIncludeFields($_fields);

        $grid->setSelect($select);

        return $grid;
    }

    public function getAdDetailByPublisherDetailGrid($shouldRunReport=true) {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(true)
             ->setLimit(100)
             ->setFilters(array('Dates', 'userName', 'publisher', $this->_sitesFilter))
             ->setDefaultSort('clicks')
             ->setDefaultDir('desc')
             ->setShowTotals(true)
        ;

        $publisher = $grid->getFilter('Publisher')->getValue();
        $userName = $grid->getFilter('UserName')->setOptions(array('labelLeft'=>true))->setLabel('Advertiser ID: ');
        $userName = $grid->getFilter('UserName')->getValue();
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate   = $grid->getFilter('Dates')->getValue('endCalendarDate');
        $site = $this->_sitesFilter->getValue();

        $grid->addColumn('date', array('header'=>'Date', 'sortable'=>true, 'type'=>'text'))
             ->addColumn('publisher', array(
                 'header'=>'Publisher',
                 'type'=>'link',
                 'links'=>'/admin/users/view/userId/$publisher_id',
                 'sortable' => true,
             ))
             ->addColumn('section_id', array('header'=>'Section ID', 'sortable'=>true))
             ->addColumn('section', array('header'=>'Section', 'sortable'=>true))
             ->addColumn('app_id', array(
                 'header'=>'App ID','type'=>'link',
                 'links'=>'/admin/apps/app-statistics/appId/$app_id',
                 'sortable'=>true, 'firstDirDesc'=>true
             ))
             ->addColumn('app', array(
                 'header'=>'App Name','type'=>'link',
                 'links'=>'/admin/reports/app-ad-stats/adid/$ad_id/appid/$app_id',
                 'sortable'=>true,
             ))
             ->addColumn('sales_rep', array('header'=>'Sales Rep', 'sortable'=>true))
             ->addColumn('account_manager', array('header'=>'Account Manager', 'sortable'=>true))
             ->addColumn('advertiser', array(
                 'header'=>'Advertiser',
                 'type'=>'link',
                 'links'=>'/admin/users/view/userId/$advertiser_id',
                 'sortable' => true,
             ))
             ->addColumn('campaign', array('header'=>'Campaign', 'sortable'=>true))
             ->addColumn('ad_id', array('header'=>'Ad ID','width'=>25,'sortable'=>true, 'type'=>'link', 'links'=>'/admin/ads/edit-ad/adId/$ad_id', 'title'=>'Edit Ad $ad_id'))
             ->addColumn('ad_title', array('header'=>'Ad Title', 'sortable'=>true))
             ->addColumn('clicks', array('header'=>'Clicks', 'sortable'=>true, 'type' => 'number', 'showTotals' => true))
             ->addColumn('impressions', array('header'=>'Impressions', 'sortable'=>true, 'type' => 'number', 'showTotals' => true))
             ->addColumn('conversions', array('header'=>'Conversions', 'sortable'=>true, 'type' => 'number', 'showTotals' => true))
             ->addColumn('expended', array('header'=>'Expended', 'sortable'=>true, 'type' => 'money', 'showTotals' => true))
        ;

        $advSelect = $this->getDb(true)->select();
        $advSelect->from( 
                        array('adv'=>'adrev_users'),
                        array(
                            'adv.id',
                            'adv.organization',
                            'account_manager' => 'am.name',
                            'sales_rep' => 'rep.name'
                        )
                    )
                    ->joinLeft(
                        array('rep'=>'adrev_users'),
                        'adv.salesrep = rep.id',
                        array()
                    )
                    ->joinLeft(
                        array('am'=>'adrev_users'),
                        'adv.account_manager_user_id = am.id',
                        array()
                    );

        $select = $this->getDb(true)->select();

        $select->from(
                    array('traff'=>'adrev_app_traffic'),
                    array(
                        'date'            => 'traff.creation_date',
                        'publisher_id'    => 'pub.id',
                        'publisher'       => 'pub.organization',
                        'section_id'      => 'sec.id',
                        'section'         => 'sec.name',
                        'app_id'          => 'app.id',
                        'app'             => 'app.app_name',
                        'sales_rep'       => 'IFNULL(adv.sales_rep, "N/A")',
                        'account_manager' => 'IFNULL(adv.account_manager, "N/A")',
                        'advertiser_id'   => 'adv.id',
                        'advertiser'      => 'IFNULL(adv.organization, "")',
                        'campaign'        => 'opt.campaign',
                        'ad_id'           => 'ads.id',
                        'ad_title'        => 'ads.title',
                        'clicks'          => 'SUM(traff.clicks)',
                        'impressions'     => 'SUM(traff.impressions)',
                        'conversions'     => 'SUM(traff.orders)',
                        'expended'        => 'SUM(traff.expended)',
                    )
                )
                ->join(
                    array('app'=>'adrev_app'),
                    'app.id = traff.app_id',
                    array()
                )
                ->join(
                    array('con'=>'app_containers'),
                    'app.container_id = con.id',
                    array()
                )
                ->join(
                    array('sec'=>'publisher_sections'),
                    'con.section_id = sec.id',
                    array()
                )
                ->joinLeft(
                    array('pub'=>'adrev_users'),
                    'pub.id = app.user_id',
                    array()
                )
                ->join(
                    array('ads'=>'adrev_ads'),
                    'ads.id = traff.ad_id',
                    array()
                )
                ->join(
                    array('opt'=>'ad_options'),
                    'ads.id = opt.ad_id',
                    array()
                )
                ->joinLeft(
                    array('adv' => $advSelect),
                    'adv.id = ads.user_id',
                    array()
                )
                ->group(array('traff.creation_date', 'pub.id', 'pub.organization', 'sec.id', 'sec.name', 'app.id', 'app.app_name', 'adv.sales_rep', 'adv.account_manager', 'adv.id', 'adv.organization', 'opt.campaign', 'ads.id', 'ads.title'));

        if (!is_null($site)) {
            $select->where('pub.site_id = ?', $site);
        }

        if (!is_null($startDate)) {
            $select->where('traff.creation_date >= ?', $startDate);
        }

        if (!is_null($endDate)) {
            $select->where('traff.creation_date <= ?', $endDate);
        }

        $filename = sprintf('ad-detail-by-pub-%s-%s', $startDate, $endDate);
        if ($publisher) {
            $select->where('pub.id = ?', $publisher);
        }

        if ($userName) {
            $select->where('adv.id = ?', $userName);
        }

        if ($shouldRunReport) {
            $grid->setSelect($select);
        }
        else {
            $grid->setEmptyText('Please use the form to search for results.');
            $grid->setDataSource(new System_Grid_DataGrid_DataSource_Array(array()));
        }

        $grid->setExportable(true)->setExportAdapter(
            array(
                'name'=>'csv',
                'options'=>array(
                    'fileName'=>$filename,
                    'exportUrlParams' => array('pages' => 'all')
                )
            )
        );

        $grid->addExportAdapter(
            array(
                'name'=>'xls',
                'options'=>array(
                    'fileName'=>$filename,
                    'exportUrlParams' => array('pages' => 'all')
                )
            )
        );

        $_fields = array();
        foreach ($grid->getColumns() as $column ) {
            $_fields[]=$column->getId();
        }
        $grid->setExportIncludeFields($_fields);

        return $grid;
    }

    public function getIndustryBrainsCommissionGrid() {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSortable(true)
             ->setFilters(array('Dates'))
             ->setLimit(-1);

        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate = $grid->getFilter('Dates')->getValue('endCalendarDate');
        $firstDayOfLastMonth = date('Y-m-01', strtotime("-1 month"));

        $grid->addColumn('id', array('header'=>'Account ID', 'sortable'=>true, 'type'=>'text'))
             ->addColumn('organization', array('header'=>'Organization', 'sortable'=>true))
             ->addColumn('name', array('header'=>'User Name', 'sortable'=>true, 'type' => 'text'))
             ->addColumn('rep', array('header'=>'Sales Rep', 'sortable'=>true, 'type' => 'text'))
             ->addColumn('manager', array('header'=>'Account Manager', 'sortable'=>true, 'type' => 'text'))
             ->addColumn('fc', array('header'=>'First Click Date', 'sortable'=>true, 'type' => 'date'))
             ->addColumn('month1', array('header'=>'Month 1', 'sortable'=>true, 'type' => 'money'))
             ->addColumn('month2', array('header'=>'Month 2', 'sortable'=>true, 'type' => 'money'))
             ->addColumn('thirtyday', array('header'=>'30 Day Spend Total', 'sortable'=>true, 'type' => 'money'))
             ->addColumn('expended', array('header'=>"Revenue", 'sortable'=>true, 'type' => 'money'))
        ;

        $creationSelect = $this->getDb(true)->select();
        $creationSelect->from(array('au2' => 'adrev_users'),
                              array(
                                  'au2.id',
                                  'fc' => 'MIN(aat2.creation_date)'
                              ))
                        ->join(array('aa2' => 'adrev_ads'), 'au2.id = aa2.user_id', array())
                        ->join(array('aat2' => 'adrev_app_traffic'), 'aa2.id = aat2.ad_id', array())
                        ->where('aat2.clicks > 0')
                        ->group('au2.id');

        $innerSelect = $this->getDb(true)->select();
        $innerSelect->from(array('au' => 'adrev_users'),
                           array(
                               'au.id',
                               'au.organization',
                               'au.name',
                               'rep' => "IFNULL(sru.name, 'N/A')",
                               'manager' => "IFNULL(amu.name, 'N/A')",
                               'cd.fc',
                               'expended' => 'SUM(aat.expended)'
                           )
                       )
                      ->join(array('aa' => 'adrev_ads'), 'au.id = aa.user_id', array())
                      ->join(array('aat' => 'adrev_app_traffic'), 'aa.id = aat.ad_id', array())
                      ->join(array('cd' => $creationSelect), 'cd.id = au.id', array())
                      ->joinLeft(array('sru' => 'adrev_users'), 'sru.id = au.salesrep', array())
                      ->joinLeft(array('amu' => 'adrev_users'), 'amu.id = au.account_manager_user_id', array())
                      ->where('aat.creation_date >= ?', $startDate)
                      ->where('aat.creation_date <= ?', $endDate)
                      ->where(sprintf('(sru.site_id = %s OR amu.site_id = %s)',
                            App_Model_DbTable_Sites::INDUSTRYBRAINS, App_Model_DbTable_Sites::INDUSTRYBRAINS))
                      ->group(array('au.id', 'au.organization', 'au.name', 'sru.name', 'amu.name', 'cd.fc'));

        $month1Select = $this->getDb(true)->select();
        $month1Select->from(array('aat4' => 'adrev_app_traffic'),
                            array('expended' => 'SUM(IFNULL(aat4.expended, 0))'))
                     ->where('aat4.ad_id = aa3.id')
                     ->where('aat4.expended > 0')
                     ->where('aat4.creation_date >= ?', $firstDayOfLastMonth)
                     ->where('aat4.creation_date <= LAST_DAY(?)', $firstDayOfLastMonth)
                     ->where('q.fc >= ?', $firstDayOfLastMonth);

        $month2Select = $this->getDb(true)->select();
        $month2Select->from(array('aat5' => 'adrev_app_traffic'),
                            array('expended' => 'SUM(IFNULL(aat5.expended, 0))'))
                     ->where('aat5.ad_id = aa3.id')
                     ->where('aat5.expended > 0')
                     ->where('aat5.creation_date >= DATE_ADD(LAST_DAY(?), INTERVAL 1 DAY)', $firstDayOfLastMonth)
                     ->where('aat5.creation_date <= DATE_ADD(q.fc, INTERVAL 30 DAY)')
                     ->where('q.fc >= ?', $firstDayOfLastMonth);

        $outerSelect = $this->getDb(true)->select();
        $outerSelect->from(array('q' => new Zend_Db_Expr("($innerSelect)")),
                           array(
                               'q.id', 
                               'q.organization',
                               'q.name',
                               'rep' => "IFNULL(q.rep, 'N/A')",
                               'manager' => "IFNULL(q.manager, 'N/A')",
                               'q.fc',
                               'Month1' => new Zend_Db_Expr("SUM(IFNULL(($month1Select), 0))"),
                               'Month2' => new Zend_Db_Expr("SUM(IFNULL(($month2Select), 0))"),
                               'q.expended'
                           )
                       )
                    ->join(array('aa3' => 'adrev_ads'), 'aa3.user_id = q.id', array())
                    ->group(array('q.id', 'q.organization', 'q.name', 'q.rep', 'q.manager', 'q.fc', 'q.expended'));

        $select = $this->getDb()->select();
        $select->from(array('q2'=> new Zend_Db_Expr("($outerSelect)")),
                      array(
                          'q2.id',
                          'q2.organization',
                          'q2.name',
                          'q2.rep',
                          'q2.manager',
                          'q2.fc',
                          'q2.month1',
                          'q2.month2',
                          'thirtyday' => new Zend_Db_Expr('q2.Month1 + q2.Month2'),
                          'q2.expended'
                      )
                  )
               ->group(array('q2.id', 'q2.organization', 'q2.name', 'q2.rep', 'q2.manager', 'q2.fc', 'q2.expended'));

        $filename = sprintf('industry-brains-commission-%s-%s', $startDate, $endDate);

        $grid->setExportable(true)->setExportFilename($filename);
        $_fields = array();
        foreach ($grid->getColumns() as $column ) {
            $_fields[]=$column->getId();
        }

        $grid->setExportIncludeFields($_fields);

        $grid->setSelect($select);

        return $grid;
    }

    public function getContainerStatsData($contId, $startDate, $endDate)
    {
        $appsTable = $this->getTable()->getAppsTable();
        $contStatsSql = $appsTable->getRevenueStatisticsSelect($startDate, $endDate, null, true, null, null, 'container_id');
        $contStatsSql->where('container_id = ?', $contId)
            ->reset( Zend_Db_Select::ORDER );
        $contStats    = $appsTable->getDefaultAdapter()->fetchAll($contStatsSql);
        $contStats    = current($contStats);
        return $contStats;
    }
    
    /**
     * reporting grid for Session Value (introduced in #8006)
     * @param App_Model_StatsApi $stats
     * @return System_Grid_DataGrid
     */
    public function getSessionValueGrid(App_Model_StatsApi $stats, $pubId = null, $containerId = null)
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        
        $grid->setFilters(array('Dates','ContainerId'));
        $grid->getFilter('Dates')->setDefault(System_Search_Date::TODAY);
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('Dates')->getValue('endCalendarDate');
        
        if ($startDate != $endDate) {
            $msg = 'Due to the massive size of the data for multiple dates, the date range is limited to 1 day at a time. ' .
                    'Please try the report again using a single day for the date range.';
            throw new System_Model_ReportException($msg); 
        }
        
        if ($pubId !== null && App_Model_Models::getPubModel()->isAllowedSessionValue($pubId) === false) {
            $msg = 'You do not have sufficient access privileges to this report. Please reach out to our Publisher Relations team for access.';
            throw new System_Model_ReportException($msg);
        }
        
        if ($containerId === null) {
            $containerId = $grid->getFilter('ContainerId')->getValue();
        }
        
        if ($containerId === null) {
            $containerId = 0;
        }
       
        if (($pubId !== null 
                && $containerId !== 0 
                && App_Model_Tables::getAppContainersTable()->isContainerOwner($containerId, $pubId) === false) || $containerId === 0) {
            // check if containerId belongs to pub
            // empty out the stats because container does not belong to pub
                $data = array();
        } else {
            $data = $stats->getSessionValue($containerId, $startDate, $endDate);
        }

        $grid->setDataSource(
                new System_Grid_DataGrid_DataSource_Array($data)
        );
        
        $grid->setSortable(true)
             ->setLimit(250);
        
        $grid->setDefaultSort('expended')
             ->setDefaultDir('desc')
             ->setShowTotals(false);
        
        $grid
            ->addColumn('creation_date', array('header'=>'Creation Date'))
            ->addColumn('container_id', array('header'=>'Container Id'))
            ->addColumn('entry_path', array('header'=>'Entry Path') )
            ->addColumn('impressions', array('header'=>'Banner Impressions','type'=>'number') )
            ->addColumn('sessions', array('header'=>'Sessions','type'=>'number') )
            ->addColumn('pub_earnings', array('header'=>'Publisher Session Earnings','type'=>'money') )
            ->addColumn('pub_session_value', array('header'=>'Publisher Session Value','align'=>'right') )
            ->addColumn('avg_ecpm_by_entry_page', array('header'=>'Avg eCPM by Entry Page', 'align'=>'right') );
        
        if ($pubId === null) {
            $grid->addColumn('gross_session_value', array('header'=>'Gross Session Value', 'align'=>'right'));
        }
        
        // export options
        $fileName = "session_value_report_{$startDate}-{$endDate}_session_value_{$containerId}";
        $grid->setExportable(true)->setExportAdapter(
                array(
                        'name'=>'csv',
                        'options'=>array('fileName'=>$fileName),
                        'exportUrlParams' => array('pages' => 'all')
                )
        );
        
        $exportFields = array(
                'creation_date',
                'container_id',
                'entry_path',
                'impressions',
                'sessions',
                'pub_earnings',
                'pub_session_value',
                'avg_ecpm_by_entry_page'
        );
        
        if ($pubId === null) {
            $exportFields[] = 'gross_session_value';
        }
        
        $grid->setExportIncludeFields($exportFields);        

        return $grid;
    }
    
    public function getAdViewabilityByRefGrid(App_Model_StatsApi $stats)
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        
        $grid->setSortable(true)
             ->setLimit(250);
        
        $grid->setDefaultSort('banner_impressions')
             ->setDefaultDir('desc')
              ->setShowTotals(false);
        
        $grid->addColumn('start_date', array('header'=>'Creation Date'))
             ->addColumn('app_id', array('header'=>'App Id'))
             ->addColumn('url', array('header'=>'URL') )
             ->addColumn('banner_impressions', array('header'=>'Measurable Banner Impressions','type'=>'number') )
             ->addColumn('median_50', array('header'=>'Median Duration of Imps at >50% Viewable in secs','type'=>'number') )
             ->addColumn('median_80', array('header'=>'Median Duration of Imps at >80% Viewable in secs','type'=>'number') )
             ->addColumn('adview_pct', array('header'=>'Ad Viewability % (> 1 sec at 50% or more)', 'type'=>'percentmultiplied', 'decimalplaces'=>0) )
        ;
        
        $grid->setFilters(array('Dates','AppId'));
        $grid->getFilter('Dates')->setDefault(System_Search_Date::TODAY);
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('Dates')->getValue('endCalendarDate');
        $appId = $grid->getFilter('AppId')->getValue();
        
        if ($startDate != $endDate) {
            $msg = 'Due to the massive size of the data for multiple dates, the date range is limited to 1 day at a time. ' .
                    'Please try the report again using a single day for the date range.';
            throw new System_Model_ReportException($msg);
        }
        
        $data = $stats->getAdViewabilityByRef($appId, $startDate, $endDate);
        
        $grid->setDataSource(
                new System_Grid_DataGrid_DataSource_Array($data)
        );
        
        // export options
        $fileName = "adviewability_by_ref_report_{$startDate}-{$endDate}_app{$appId}";
        $grid->setExportable(true)->setExportAdapter(
                array(
                        'name'=>'csv',
                        'options'=>array('fileName'=>$fileName),
                        'exportUrlParams' => array('pages' => 'all')
                )
        );
        $grid->setExportIncludeFields(array(
                'start_date',
                'app_id',
                'url',
                'banner_impressions',
                'median_50',
                'median_80',
                'adview_pct'
        ));
        
        return $grid;
        
    }
    
    /**
     * 
     * @param App_Model_StatsApi $api
     * @return App_Model_StatsApi
     */
    public function setStatsApi(App_Model_StatsApi $api)
    {
        $this->_statsApi = $api;
        return $this->_statsApi;
    }
    
    /**
     * @return App_Model_StatsApi
     */
    public function getStatsApi()
    {
        if ($this->_statsApi === null) {
            $_di = App_Di::instance();
            $this->_statsApi = $_di->getStatsApi();
        }
        return $this->_statsApi;
    }
}
