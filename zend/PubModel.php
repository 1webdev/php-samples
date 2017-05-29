<?php

include_once 'PapsTable.php';
include_once 'tables/PublishizersTable.php';
include_once 'ZoT.php';

include_once 'tables/BrandingPublishserTable.php';

class PubModel extends System_Model_Abstract 
{

    const FAKE_STATUS_ID_ACTIVE_AND_AWAITING = 11;
    const PAP_DETAILED_STATS_TOO_LARGE_PERIOD_DAYS  = 22;
    const PAP_DETAILED_STATS_TOO_LARGE_PERIOD_ERROR = 'Date range is incorrect';

    const PAP_DETAILED_STATS_REFERRER_URL    = 'refUrlStats';
    const PAP_DETAILED_STATS_SESSION_VALUE   = 'sessionValueStats';
    const PAP_DETAILED_STATS_GEO_TRAFFIC     = 'trafficByGeo';
    const PAP_DETAILED_STATS_REGION_PLATFORM = 'regionAndPlatform';
    const PAP_DETAILED_STATS_HIGH_LEVEL      = 'highLevel';
    const PAP_DETAILED_STATS_DEVICE_TRAFFIC  = 'trafficByDevice';

    public $papsTable;
    public $publishizersTable;
    public $zoT;
    
    private $_logoUploadPath = '/images/user/publishizer/logos/';
    private $_reportImagesUploadPath = '/images/user/publishizer/reports/';
    private $_chartUploadPath = '/images/user/publishizer/charts/';
    private $_logoUploadFormActionUrl = "/admin/users/publishizer-monthly-summary-tab-upload-logo";
    private $_maxLogoWidth = 200;
    private $_maxLogoHeight = 100;
    
    public function __construct()
    {
        parent::__construct();

        $this->papsTable=new PapsTable();
        $this->publishizersTable=new PublishizersTable();
        $this->zoT=new ZoT();
    }

    /**
     * Return biz-dev' email address for given publishizer
     * @param  int $pubId
     * @return string   Email address if found, empty string otherwise
     */
    public function getPublishizerBizDevEmail($pubId)
    {
        $users = Pap_Model_Models::getUsersModel();

        $pub = $users->getUserDataById($pubId);
        if ($pub) {

            $bizDev = $users->getUserDataById($pub['biz_dev_user_id']);
            if ($bizDev) {

                return isset($bizDev['notification_email'])
                       ? $bizDev['notification_email']
                       : $bizDev['email'];
            }
        }

        return '';
    }

    /**
     * @param  int $pubId
     * @return array An array with email and subj keys and corresponding values
     */
    public function getApDetailedStatsInquireNowOptions($pubId)
    {
        $user = Pap_Model_Models::getUsersModel()->getUserDataById($pubId);
        return array(
            'email' => $this->getPublishizerBizDevEmail($pubId),
            'subj'  => 'Paid Discovery Inquiry from ' . $user['name']
        );
    }

    /**
     * @param  string      $statsType
     * @param  int         $pubId
     * @param  int         $papId
     * @param  int|null    $papStatus
     * @param  string|null $startDate
     * @param  string|null $endDate
     * @param  int|null    $offset      Data offset for some stat grids
     * @return mixed
     * @throws User_Model_UserException if period selected too large
     */
    public function getPapDetailedStatsAndAnalytics($statsType, $pubId, $papId,
        $papStatus = null, $startDate = null, $endDate = null, $offset = null
    ) {
        /*
         * Check selected period short enough
         */
        if ($this->isPapDetailedStatsPeriodTooLarge($startDate, $endDate)) {
            throw new User_Model_UserException(
                self::PAP_DETAILED_STATS_TOO_LARGE_PERIOD_ERROR
            );
        }

        switch ($statsType) {
            case self::PAP_DETAILED_STATS_HIGH_LEVEL:
                $stats = $this->getPapDetailedHighLevelStats($papId, $pubId, $papStatus, $startDate, $endDate);
                break;

            case self::PAP_DETAILED_STATS_REGION_PLATFORM:
                $stats = $this->getPapStatisticsGroupedByPlatformAndRegionGrid($papId, $pubId, $startDate, $endDate);
                break;

            case self::PAP_DETAILED_STATS_GEO_TRAFFIC:
                $stats = $this->_getPapDetailedGeoStats($papId, $startDate, $endDate);
                break;

            case self::PAP_DETAILED_STATS_DEVICE_TRAFFIC:
                $deviceStats = array();
                $deviceTypes = Pap_Model_DbTable_DeviceTypes::getTypes(true);
                foreach ($this->getPapDetailedDeviceStats($pubId, $startDate, $endDate) as $devStats) {
                    if (array_key_exists($devStats['device_id'], $deviceTypes)) {
                        $deviceStats[$devStats['device_id']] = $devStats;
                    }
                }
                $stats = $deviceStats;
                break;

            case self::PAP_DETAILED_STATS_SESSION_VALUE:
                $stats = $this->_getPapDetailedSessionValueStats($papId, $startDate, $endDate, $offset);
                break;

            case self::PAP_DETAILED_STATS_REFERRER_URL:
                $stats = $this->_getPapDetailedTrafficByUrlGrid($papId, $startDate, $endDate, $offset);
                break;

            default:
                throw new User_Model_UserException('Wrong stats type given: ' . $statsType);
                break;
        }

        return $stats;
    }

    /**
     * @param  int         $papId       Pap ID
     * @param  null|int    $papStatus   Pap status ID
     * @param  int         $pubId       Publishizer ID
     * @param  null|string $startDate
     * @param  null|string $endDate
     * @return array
     */
    public function getPapDetailedHighLevelStats($papId, $pubId, $papStatus = null,
        $startDate = null, $endDate = null
    ) {
        $cols = array(
            'imps'    => new Zend_Db_Expr('IFNULL(SUM(at.impressions), 0)'),
            'clicks'  => new Zend_Db_Expr('IFNULL(SUM(at.clicks), 0)'),
            'revenue' => new Zend_Db_Expr('IFNULL(SUM(at.expended - at.earned), 0)')
        );

        $db  = $this->getTable()->getPapsTable()->getDefaultAdapter();
        $sql = $db->select()
                  ->from(array('a' => Content_Db_Tables::ADREV_PAP), $cols)
                  ->join(array('as' => Content_Db_Tables::ADREV_PAP_STATUSES), 'as.id = a.status', array())
                  ->joinLeft(array('at' => Content_Db_Tables::ADREV_PAP_ZONE), 'at.pap_id = a.id', array())
                  ->where('a.id = ?', $papId)
                  ->where('a.user_id = ?', $pubId);

        if ($papStatus) {
            $sql->where('a.status = ?', $papStatus);
        }

        if ($startDate) {
            $sql->where('at.creation_date >= ?', $startDate);
        }

        if ($endDate) {
            $sql->where('at.creation_date <= ?', $endDate);
        }

        return $db->fetchRow($sql);
    }

    /**
     * Get device stats for a publishizer
     *
     * @param  int         $publishizerId
     * @param  string|null $startDate  (optional) defaults to today
     * @param  string|null $endDate    (optional) defaults to today
     * @return array       An array with stats grouped by device type
     */
    public function getPapDetailedDeviceStats($publishizerId, $startDate = null,
        $endDate = null
    ) {
        $db = $this->getDb();

        if (is_null($startDate)) {
            $startDate = date('Ymd');
        }

        if (is_null($endDate)) {
            $endDate = date('Ymd');
        }

        $cols = array(
            'device_id' => 'dt.id',
            'imps'      => new Zend_Db_Expr('SUM(atdd.impressions)'),
            'ecpm'      => new Zend_Db_Expr('IFNULL(IF(atdd.impressions > 0, SUM(atdd.expended - atdd.earned) * 1000 / SUM(atdd.impressions), 0), 0)')
        );

        $select = $db->select()
                     ->from(array('atdd' => Content_Db_Tables::PAP_TRAFFIC_DETAILS_DAY), $cols)
                     ->join(array('aa' => 'adrev_pap'), 'atdd.pap_id = aa.id', array())
                     ->join(array('dt' => 'device_types'), 'atdd.device_id = dt.id', array())
                     ->where('aa.user_id = ?', $publishizerId)
                     ->where('atdd.creation_date >= ?', $startDate)
                     ->where('atdd.creation_date <= ?', $endDate)
                     ->group(array('aa.user_id', 'dt.name'))
                     ->order('dt.name');

        return $db->fetchAll($select);
    }

    /**
     * Returns traffic stats by country for given pap
     *
     * @param  int         $papId
     * @param  string|null $startDate
     * @param  string|null $endDate
     * @return array
     */
    protected function _getPapDetailedGeoStats($papId, $startDate = null, $endDate = null)
    {
        $traffic = Pap_Di::instance()->getStatsApi()->getPapTrafficStatsByCountry(
            $papId, $startDate, $endDate
        );

        $stats = array();

        if ($traffic) {
            $nonUsImpsTotal = $nonUsEarnedTotal = 0;

            foreach ($traffic as $data) {
                if ($data['country'] == 'US') {
                    $stats[Content_Db_Table_TargetCountries::US] = array(
                        'imps' => $data['imps'],
                        'ecpm' => $data['imps'] > 0 ? ($data['earned'] / $data['imps']) * 1000 : 0
                    );
                } else {
                    $nonUsImpsTotal   += $data['imps'];
                    $nonUsEarnedTotal += $data['earned'];
                }
            }

            $stats[Content_Db_Table_TargetCountries::OTHERS] = array(
                'imps' => $nonUsImpsTotal,
                'ecpm' => $nonUsImpsTotal > 0 ? ($nonUsEarnedTotal / $nonUsImpsTotal) * 1000 : 0
            );
        }

        return $stats;
    }

    /**
     * @param  int         $papId       Pap ID
     * @param  null|string $startDate   Start date to fetch stats from
     * @param  null|string $endDate     End date to fetch stats to
     * @param  null|int    $offset      How much data should we skip
     * @param  null|int    $limit       Defaults to 6 rows per page
     * @return System_Grid_DataGrid
     */
    protected function _getPapDetailedSessionValueStats($papId, $startDate = null,
        $endDate = null, $offset = null, $limit = 10
    ) {
        $sessionStats = Pap_Di::instance()->getStatsApi()->getPapSessionValueStats(
            $papId, $startDate, $endDate
        );

        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_Array($sessionStats), $limit);

        $grid->setShowTotals(false)
             ->setPagerAdapter('StandardUpdatedPlus');

        $grid->addColumn('entry_path',              array('header' => 'Entry Path',                     'sortable' => false))
             ->addColumn('impressions',             array('header' => 'Banner Impressions',             'sortable' => false, 'type'  => 'number'))
             ->addColumn('sessions',                array('header' => 'Sessions',                       'sortable' => false, 'type'  => 'number'))
             ->addColumn('pub_earnings',            array('header' => 'Net Publishizer Session Earnings', 'sortable' => false, 'type'  => 'money'))
             ->addColumn('pub_session_value',       array('header' => 'Net Publishizer Session Value',    'sortable' => false, 'align' => 'right'))
             ->addColumn('avg_ecpm_by_entry_page',  array('header' => 'Avg Net eCPM by Entry Page',     'sortable' => false, 'align' => 'right'));

        if ($offset) {
            $grid->setPage($offset);
        }

        return $grid;
    }

    /**
     * @param  int         $papId       Pap ID
     * @param  null|string $startDate   Start date to fetch stats from
     * @param  null|string $endDate     End date to fetch stats to
     * @param  null|int    $offset      How much data should we skip
     * @param  null|int    $limit       Defaults to 6 rows per page
     * @return System_Grid_DataGrid
     */
    protected function _getPapDetailedTrafficByUrlGrid($papId, $startDate = null,
        $endDate = null, $offset = null, $limit = 10
    ) {
        $trafficByUrl = Pap_Di::instance()->getStatsApi()->getUrlImpressionRef(
            $papId, $startDate, $endDate
        );

        $refStats = array();
        foreach ($trafficByUrl as $traff) {
            $refStats[] = array(
                'url'  => $traff['referer'],
                'ecpm' => $traff['url_ecpm'],
                'imps' => $traff['banner_impressions']
            );
        }

        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_Array($refStats), $limit);

        $grid->setShowTotals(false)
             ->setPagerAdapter('StandardUpdatedPlus');

        $grid->setExportable(true)
             ->setExportAdapter(array(
                 'xls',
                 'options' => array(
                     'exportUrlParams' => array(
                         'pap'    => $papId,
                         'start'  => $startDate,
                         'end'    => $endDate,
                         'offset' => $offset,
                         'grid'   => self::PAP_DETAILED_STATS_REFERRER_URL
                     )
                 )
             ))
             ->setExportColumns(array(
                 'url', 'imps', 'ecpm'
             ))
             ->setExportFilename('referrer-urls-stats.xls');

        $grid->addColumn('url',  array('header' => 'URL',  'sortable' => false))
             ->addColumn('imps', array('header' => 'Imps', 'sortable' => false, 'type' => 'number'))
             ->addColumn('ecpm', array('header' => 'eCPM', 'sortable' => false, 'type' => 'money'));

        if ($offset) {
            $grid->setPage($offset);
        }

        return $grid;
    }

    /**
     * @param  string $startDate
     * @param  string $endDate
     * @return bool True if period too large, False if it's short enough
     */
    public function isPapDetailedStatsPeriodTooLarge($startDate, $endDate)
    {
        /*
         * Empty start and end dates means "All Time" date range selected.
         * It's too large period for VS API.
         */
        if ($startDate && $endDate) {

            $zfStartDate = new Zend_Date($startDate, 'YYYY-MM-dd');
            $zfEndDate   = new Zend_Date($endDate, 'YYYY-MM-dd');
            $periodInMs  = $zfEndDate->sub($zfStartDate)->getTimestamp();

            if (($periodInMs / (60 * 60 * 24)) <= self::PAP_DETAILED_STATS_TOO_LARGE_PERIOD_DAYS) {
                return false;
            }
        }

        return true;
    }

    /**
     * Find existing allowed zones, merge them with required and set as allowed
     * @param int $pubId
     */
    public function allowSelfServeBuilderRequiredZones($pubId)
    {
        $existing = Pap_Model_Tables::getUserZoneTable()->getZoneIdsByUserId($pubId);
        $allowed  = Pap_Model_Models::getPapsModel()->getBuildAdZoneForm()
                                    ->getAllowedZoneIds();

        /*
         * Merge already allowed zones and the new ones required for Self-Serve
         * Builder and set all of them as allowed zones
         */
        Pap_Model_Tables::getUsersTable()->setAllowedZones(
            $pubId, array_merge($existing, $allowed)
        );
    }

    /**
     * Allow required zones and enable self-serve ad zone builder
     * @param int $pubId
     */
    public function enableSelfServeBuilder($pubId)
    {
        /*
         * Allow required zones
         */
        $this->allowSelfServeBuilderRequiredZones($pubId);

        /*
         * After that enable self-serve ad zone builder
         */
        Pap_Model_Models::getUsersModel()->updatePublishizerData(
            $pubId, array('allow_selfserve_builder' => 1)
        );
    }

    /**
     * Disable Self-Serve Ad Zone Builder
     * @param int $pubId
     */
    public function disableSelfServeBuilder($pubId)
    {
        /*
         * Disable self-serve ad zone builder
         */
        Pap_Model_Models::getUsersModel()->updatePublishizerData(
            $pubId,
            array('allow_selfserve_builder' => 0)
        );
    }
    
    /**
     * 
     * @param  int      $pubId
     * @param  null|int $papId
     * @param  bool     $isContentDelivery
     * @param  array    $papsData
     * @throws System_Model_UserException if given publishizer not found
     * @throws System_Model_UserException not all columns present in given pap data array
     * @return array
     */
    public function getAllowedColumns($pubId, $papId = null, $isContentDelivery = false,
        array $papsData = array()
    ) {
        $publishizer = $this->getTable()->getPublishizersTable()->getPublishizer($pubId);
        
        if (empty($publishizer)) {
            throw new System_Model_UserException('Publishizer does not exist');
        }
        
        $columns = array(
            'date'          => 'Date',
            'startDate'     => 'Start Date',
            'endDate'       => 'End Date',
            'full_name'     => 'Paplication',
            'clicks'        => 'Clicks',
            'allClicks'     => 'All Clicks',
            'impressions'   => 'Impressions',
            'eCPM'          => 'eCPM',
            'earned'        => 'Earned'
        );
        
        if ($publishizer['hide_clicks'] == 1) {
            unset($columns['clicks']);
        }
        
        if ($publishizer['hide_first_clicks'] == 1) {
            unset($columns['allClicks']);
        }
        
        if (is_null($papId)) {
            unset($columns['date']);
        } else {
            unset($columns['startDate']);
            unset($columns['endDate']);
        }
        
        if ($isContentDelivery) {
            // Unset
            unset(
                $columns['clicks'],
                $columns['allClicks'],
                $columns['impressions'],
                $columns['eCPM'],
                $columns['earned']
            );
            
            // Insert
            $columns['full_name']           = 'Content Recommendation Paplication';
            $columns['pageviews']           = 'Additional Pageviews';
            $columns['user_retention_pct']  = 'User Retention %';
            $columns['content_clicks']      = 'Content Clicks';
        }

        // Finally check all required columns also present in each pap data array
        foreach ($papsData as $papData) {
            $papId = isset($papData['pap_id']) ? $papData['pap_id'] : 'n/a';
            foreach ($columns as $columnName => $val) {
                if (!in_array($columnName, array_keys($papData))) {
                    $message = "Column '{$columnName}' not present in data "
                             . "array for pap #{$papId} for user #{$pubId}";
                    throw new System_Model_UserException($message);
                }
            }
        }

        return $columns;
    }
    
    public function getBrandingStatistic($userId, $startDate = null, $endDate = null, $groupByDate=false)
    {
        $db = $this->getDb(true);
        $columns=array(
           'pap_id'=>new Zend_Db_Expr('-1'),
           'date'=>'papTraf.creation_date',
           'pap_name'=>new Zend_Db_Expr('"Content network"'),
           'full_name'=>new Zend_Db_Expr('"Content network"'),
           'impressions'=>'IFNULL( SUM(papTraf.impressions), 0)',
           'imps_passback'=>new Zend_Db_Expr('"N/A"'),
           'clicks'=>'IFNULL( SUM(papTraf.clicks) ,0)',
           'fcClicks'=>new Zend_Db_Expr('"N/A"'),
           'nonSlClicks'=>new Zend_Db_Expr('"N/A"'),
           'container_id'=>new Zend_Db_Expr('NULL'),
           'unitType'=>new Zend_Db_Expr('"N/A"'),
           'earned'=>'FORMAT( IFNULL(SUM(papTraf.Branding_earned),0), 2)',
           'earnedRevShare'=>new Zend_Db_Expr('"N/A"'),
           'eCPM'=>new Zend_Db_Expr('FORMAT(IFNULL(  (SUM(papTraf.Branding_earned))*1000/SUM(papTraf.impressions)  ,0),2)'),
           'payMethod'=>new Zend_Db_Expr('"N/A"'),
           'status_name'=>new Zend_Db_Expr('"N/A"'),
           'status'=>new Zend_Db_Expr('"N/A"'),
           'status_description'=>new Zend_Db_Expr('"N/A"'),
           'allClicks'=>'IFNULL( SUM(papTraf.clicks) ,0)',
           'startDate'=> new Zend_Db_Expr($db->quote($startDate)),
           'endDate'=> new Zend_Db_Expr($db->quote($endDate)),
        );
                                //       
        $select = $db->select()
              ->from(array('advertiser'=>'user_advertiser'),$columns)                                     
              ->joinLeft(array('ads'=>'adrev_ads'),'ads.user_id=advertiser.user_id',array())
              ->joinLeft(array('papTraf'=>'adrev_pap_traffic',array()),
              'papTraf.ad_id=ads.id AND NOT EXISTS (SELECT * FROM adrev_pap as pap where pap.id=pap_id and pap.user_id=advertiser.brand_publishizer_id)'.
               ((null!==$startDate)?(' AND papTraf.creation_date>='.$db->quote($startDate)):' ').
               ((null!==$endDate)?(' AND papTraf.creation_date<='.$db->quote($endDate)):' '),
                        array())
              ->group('advertiser.brand_publishizer_id');
        $select->where('`advertiser`.`brand_publishizer_id`=?', $userId);
        
        if($groupByDate === true) {
            $select->group('papTraf.creation_date');                       
        }
        
        return $this->getDb(true)->fetchAll($select);

    }
    
    
    public function getPapsListSubPap($papId=null, $startDate=null, $endDate=null)
    {

        $select = $this->getDb()->select()
              ->from(array('papTraf'=>'adrev_pap_zone_subpapid'), array(
                  'papTraf.sub_pap_id',
                  'SUM(papTraf.impressions) as impressions',
                  'SUM(papTraf.clicks) as clicks',
                  '(SUM(papTraf.expended)-SUM(papTraf.earned)) as earned',
                  'SUM(papTraf.earned) as earned_content',
              ))
              ->where (' papTraf.pap_id = '.$papId)
              ->group('papTraf.sub_pap_id')
              ->order('papTraf.sub_pap_id DESC');
      if (null!==$startDate) {
          $select->where('papTraf.creation_date>=?', $startDate);
      }
      if (null!==$endDate) {
          $select->where('papTraf.creation_date<=?', $endDate);
      }
      
      return $this->getDb()->fetchAll($select);
    }
    
    public function getUserPaps($userId,$papId=null)
    {
        $select=$this->getDb()->select()
           ->from(array('paps'=>'adrev_pap'),array('*','pap_name as name'))
           ->where('paps.user_id=?',$userId);
        if ( null !== $papId ) {
            $select->where('id=?',$papId);
            return $this->getDb()->fetchRow($select); 
        } else {
               return $this->getDb()->fetchAll($select);
        }
        
    }

    /**
     * @param  int         $papId
     * @param  int|null    $userId if null - won't be checked owner of pap
     * @param  null|string $startDate
     * @param  null|string $endDate
     * @return System_Grid_DataGrid
     * @throws User_Model_UserException if given parameters were not validated
     * @throws User_Model_UserException if required target country not found
     */
    public function getPapStatisticsGroupedByPlatformAndRegionGrid($papId, $userId,
        $startDate = null, $endDate = null
    ) {
        /*
         * Validate input
         */
        if ($papId && !Pap_Model_Models::getPapsModel()->isValidPap($papId, $userId)) {
            throw new User_Model_UserException("Provided Pap #{$papId} not valid");
        }

        if ($startDate && !Zend_Date::isDate($startDate, 'Y-M-d')) {
            throw new User_Model_UserException("Given start date is invalid");
        }

        if ($endDate && !Zend_Date::isDate($endDate, 'Y-M-d')) {
            throw new User_Model_UserException("Given end date is invalid");
        }

        $us = Pap_Model_Tables::getTargetCountriesTable()->getCountryById(
            Content_Db_Table_TargetCountries::US
        );

        if (!$us || !isset($us['name'])) {
            $msg = 'Required target country with ID '
                 . Content_Db_Table_TargetCountries::US
                 . ' not found';
            throw new User_Model_UserException($msg);
        }

        $statsForOtherCountries = $this->_getPapStatisticsGroupedByPlatformAndRegionSelect(
            $papId, $startDate, $endDate
        );
        $statsForUnitedStates = $this->_getPapStatisticsGroupedByPlatformAndRegionSelect(
            $papId, $startDate, $endDate, $us['name'], false
        );

        $db   = $this->getDb();
        $data = $db->fetchAll(
            $db->select()->union(array($statsForUnitedStates, $statsForOtherCountries))
        );

        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_Array($data));
        $grid->addColumn('region',      array('header' => 'Region',     'sortable' => false))
             ->addColumn('platform',    array('header' => 'Platform',   'sortable' => false))
             ->addColumn('clicks',      array('header' => 'Clicks',     'sortable' => false))
             ->addColumn('imps',        array('header' => 'Imps',       'sortable' => false))
             ->addColumn('ecpm',        array('header' => 'eCPM',       'sortable' => false))
             ->addColumn('earned',      array('header' => 'Earned',     'sortable' => false));
        if (null===$userId) {
            $grid->addColumn('grossEcpm',  array('header' => 'Gross eCPM',  'sortable' => false));
        }
        $grid->setTemplatePart('body','grid/paps-stats-region-platform-breakdown-body.phtml');

        return $grid;
    }

    /**
     * @param  int          $papId
     * @param  null|string  $startDate
     * @param  null|string  $endDate
     * @param  string       $regionName         Region name stub to return if no stats found
     * @param  bool         $otherCountriesOnly Return stats for unknown countries (doesn't exists in target_countries table)
     * @return Zend_Db_Select
     */
    private function _getPapStatisticsGroupedByPlatformAndRegionSelect($papId,
        $startDate = null, $endDate = null, $regionName = Content_Db_Table_TargetCountries::OTHERS_NAME,
        $otherCountriesOnly = true
    ) {
        $traffTableName = Pap_Model_Tables::getPapTrafficDetailsDayTable()->getDefaultTable()
                                                                          ->getName();

        $db   = $this->getDb();
        $cols = array(
            'region'   => new Zend_Db_Expr("IFNULL(country.name, '{$regionName}')"),
            'platform' => 'device.name',
            'clicks'   => 'IFNULL(SUM(traff.clicks), 0)',
            'imps'     => 'IFNULL(SUM(traff.impressions), 0)',
            'earned'   => 'IFNULL(SUM(traff.expended - traff.earned - traff.Branding_earned), 0)', // Publishizer Earned = Advertiser Expended - Advizer Earned
            'expended' => 'IFNULL(SUM(traff.expended), 0)',
            'ecpm'     => 'IFNULL(IF(traff.impressions > 0, SUM(traff.expended - traff.earned) * 1000 / SUM(traff.impressions), 0), 0)',
            'grossEcpm'=> 'IFNULL(IF(traff.impressions > 0, SUM(traff.expended) * 1000 / SUM(traff.impressions), 0), 0)'
        );

        $countryJoinCondCmp = $otherCountriesOnly ? '=' : '!=';

        $traffJoinConditions = array(
            'traff.device_id = device.id',
            $db->quoteInto('traff.pap_id = ?', $papId),
            "traff.country_id {$countryJoinCondCmp} " . (int) Content_Db_Table_TargetCountries::OTHERS
        );

        if (!is_null($startDate) && !empty($startDate)) {
            array_push(
                $traffJoinConditions,
                $db->quoteInto('traff.creation_date >= ?', $startDate)
            );
        }

        if (!is_null($endDate) && !empty($endDate)) {
            array_push(
                $traffJoinConditions,
                $db->quoteInto('traff.creation_date <= ?', $endDate)
            );
        }

        $sql = $db->select()
                  ->from(array('device' => 'device_types'), $cols)
                  ->joinLeft(
                      array('traff' => $traffTableName),
                      implode(' AND ', $traffJoinConditions),
                      array()
                  )
                  ->joinLeft(array('country' => 'target_countries'), 'country.id = traff.country_id', array())
                  ->group('device.id');

        return $sql;
    }

    /**
     * Returns grid object for pap stats
     * 
     * @param  int      $userId           User ID
     * @param  int|null $papId            Pap ID
     * @param  bool     $isContentDeliveryPage Wether to return data grid for ContentDelivery page or not
     * @return System_Grid_DataGrid
     */
    public function getPapsStatisticGrid($userId, $papId = null, $isContentDeliveryPage = false)
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        
        $papStatus = new System_Search_Filters_PapStatus();
        $statuses  = $papStatus->getStatuses();
        $defaultStatus = ($this->isSelfServe($userId)) ? self::FAKE_STATUS_ID_ACTIVE_AND_AWAITING
                                                       : User_Model_DbTable_PapStatuses::ACTIVE;
        $papStatus->setStatuses(
            array(
                self::FAKE_STATUS_ID_ACTIVE_AND_AWAITING => 'Active + Awaiting Paproval',
                99                                       => 'All + Archived',
            ) + $statuses
        )
        ->setDefaultValue($defaultStatus);

        $grid->setFilters(array('Dates', $papStatus));
        
        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate   = $grid->getFilter('Dates')->getValue('endCalendarDate');
        $statusId  = $grid->getFilter('PapStatus')->getValue();

        if ($isContentDeliveryPage) {
            $data = $this->getCrPapsStatistic($userId, $papId, $startDate, $endDate, $statusId);
        } else {
            $data = $this->getPapStatistic($userId, $papId, $startDate, $endDate, null, $statusId);
        }
        
        //Sort data: show at top containers, sorted by volume (sum of paps)
        $_dataSorted = array();
        $_containers = array();
        $_contPaps   = array();
        $_tmp        = array();
        foreach ($data as $_pap) {
            $_cid = $_pap['container_id'];
            $_id = $_pap['pap_id'];
            if (!empty($_cid)) {
                if (!isset($_containers[$_cid])) {
                    $_containers[$_cid] = 0;
                    $_contPaps[$_cid] = array();
                }
                $_containers[$_cid] += $_pap['impressions'];
                $_contPaps[$_cid][] = $_id;
            }
            $_tmp[$_id] = $_pap;
        }
   
        arsort($_containers);

        $i = 0;
        foreach ($_containers as $_cid=>$_imps) {
            foreach ($_contPaps[$_cid] as $_pap) {                
                $_dataSorted[] = $_tmp[$_pap]+array('position'=>$i++);
                unset($_tmp[$_pap]);
            }
        }
        foreach ($_tmp as $_pap) {
            $_dataSorted[] = $_pap+array('position'=>$i++);
        }
        $data = $_dataSorted;
        
        // List of sub id for Paplication
        foreach ($data as &$pap) {
            $x = $this->getPapsListSubPap($pap['pap_id'], $startDate, $endDate);
            if (count($x) > 0 ) {
                $pap['subPaps'] = $x;
            }
        }
        
        $grid->setDataSource(new System_Grid_DataGrid_DataSource_Array($data));
        
        $publishizerData = Pap_Model_Models::getUsersModel()->getPublishizerData($userId);
        $showPayMethod = $publishizerData['allow_cpm_vs_revshare']?true:false;
        $showPassback  = $publishizerData['allow_passback_tags'] ? true : false;
        $hideClicks    = isset($data[0]['clicks'])?false:true;
        $hideAllClicks = isset($data[0]['allClicks'])?false:true;
           
        $grid->setLimit(-1);
        $grid->setDefaultSort('position')
             ->setDefaultDir('asc')
             ->setSortable(false);

        $editLink = $publishizerData['allow_selfserve_builder']
                  ? '/pub/build-ad-zone/id/$pap_id'
                  : '/pub/edit?papId=$pap_id';

        $grid->addColumn(
            'pap_id',
            array(
                'header' => 'ID',
                'title'  => 'Edit tag',
                'type'   => 'link',
                'links'  => $editLink
            )
        );
        $grid->addColumn(
            'full_name',
            array(
                'header' => $isContentDeliveryPage ? 'Content Recommendation Paplication' : 'Paplication'
            )
        );
        
        if ($isContentDeliveryPage) {
            $grid->addColumn('pageviews', array('header' => 'Additional Pageviews'))
                 ->addColumn('user_retention_pct', array('header' => 'User Retention %'))
                 ->addColumn('content_clicks', array('header' => 'Content Clicks'));
        }
        
        
        if (!$isContentDeliveryPage) {
            
            if (Pap_Model_Functions::isOptiServe()) {
                $grid->addColumn('adPriority', array('header'=>'Permitted Ads'));
            }
            
            $grid->addColumn('unitType', array('header'=>'Type'));
            
            if (!$hideClicks) {
                $grid->addColumn('clicks', array('header'=>'Clicks'));
            }
            
            if (!$hideAllClicks) {
                $grid->addColumn('allClicks', array('header'=>'All Clicks'));            
            }
            
            $grid->addColumn('impressions', array('header'=>'Imps'));

            if ($showPassback) {
                $grid->addColumn('imps_passback', array('header'=>'Passbacks'));
            }
            
            if ($showPayMethod) {
                $grid->addColumn('floor_guaranteed_cpm', array('header'=>'RevShare %'));
                $grid->addColumn('eCPM', array('header'=>'CPM (Guarantee)'));
                $grid->addColumn('eCPM_revShare', array('header'=>'eCPM (RevShare)'));
                $grid->addColumn('earned', array('header'=>'Earnings (Guarantee)'));
                $grid->addColumn('earnedRevShare', array('header'=>'Earnings (RevShare)'));            
            } else {
                $grid->addColumn('eCPM', array('header'=>'eCPM'));
                $grid->addColumn('earned', array('header'=>'Earned'));
            }
        }
        
        $grid->addColumn('status', array('header'=>'Ad Code'));
        $grid->addColumn('report', array('header'=>'Reports','index'=>'pap_id'));
        $grid->setTemplatePart('body', 'pub/grid/paps-body.phtml');

        if ($isContentDeliveryPage) {
            $grid->setFormUrl('/pub/content-net');
        }
        
        return $grid;
    }
    
    /**
     * Get pap(s) stats
     *
     * @param  int         $userId    User ID
     * @param  int|null    $papId     Pap ID
     * @param  string|null $startDate Start date
     * @param  string|null $endDate   End date
     * @param  int|null    $unitType  Unit type ID
     * @param  int|null    $statusId  Pap status ID
     * @return array
     */
    
    public function getPapStatistic($userId, $papId = null, $startDate = null,
        $endDate = null, $unitType = null, $statusId = null
    ) {
        if (empty($startDate)) {
            $startDate = null;
        }
        if (empty($endDate)) {
            $endDate = null;
        }
        
        $papsStats    = array();
        $BrandingStats = array();
        $db           = $this->getDb(true);
        
        if ($papId === -1) {
            //take data grouped NOT by date, only by id
            $BrandingStats = $this->getBrandingStatistic($userId, $startDate, $endDate, true);
        } else {
             $_pubEarned = 'IFNULL(SUM(papTraf.expended-papTraf.earned), 0)';
             $_columns=array(
                'pap_id'=>'pap.id',
                'full_name'=>'pap.full_name',
                'date'=>'papTraf.creation_date',
                'startDate'=> new Zend_Db_Expr($db->quote($startDate)),
                'endDate'=> new Zend_Db_Expr($db->quote($endDate)),
                'container_id',
                'pap_name'=>'pap.pap_name',
                'impressions'=>'IFNULL( SUM(papTraf.impressions), 0)',
                'imps_passback'=>'IFNULL( SUM(papTraf.imps_passback), 0)',
                'clicks'=>'IFNULL( SUM(papTraf.clicks) ,0)',

                 //@TODO Hidden bug:
                 //fcClick can exist for day, when pap does not have stats in traffic table
                 //record is missed in case
                'fcClicks'=>new Zend_Db_Expr(
                   'IFNULL((SELECT SUM(clicks) FROM pap_ad_fc_log  as traff2 WHERE traff2.pap_id=pap.id '.
                    ((null!==$startDate)?(' AND traff2.creation_date>='.$db->quote($startDate)):' ').
                    ((null!==$endDate)?(' AND traff2.creation_date<='.$db->quote($endDate)):' ')
                  //need to group by date if it's for pap, otherwise it will be wrong report
                  .(null !== $papId?' AND creation_date=date group by creation_date ':' ').  '),0)'),
                'nonSlClicks'=>new Zend_Db_Expr(
                   'IFNULL((SELECT SUM(IF(ad.is_twoclick=0,traff3.clicks,0)) FROM adrev_pap_traffic  as traff3'
                  .' JOIN adrev_ads as ad ON ad.id=traff3.ad_id '
                  .' WHERE traff3.pap_id=pap.id '.
                    ((null!==$startDate)?(' AND traff3.creation_date>='.$db->quote($startDate)):' ').
                    ((null!==$endDate)?(' AND traff3.creation_date<='.$db->quote($endDate)):' ')
                  //need to group by date if it's for pap, otherwise it will be wrong report
                  .(null !== $papId?' AND creation_date=date group by creation_date ':' ').  '),0)'),
                //earned by publishizer
                /*'earned_adjusted'=>"IF(floor_guaranteed_cpm IS NOT NULL,
                               IF(SUM(expended)*(floor_guaranteed_cpm/100)>{$_pubEarned},
                                  SUM(expended)*(floor_guaranteed_cpm/100),{$_pubEarned}),
                             {$_pubEarned})",*/
                'earned'=>"{$_pubEarned}",
                'earnedRevShare'=>'ROUND( IFNULL(SUM(papTraf.expended)*floor_guaranteed_cpm/100,0),2)',
                'expended'=>'IFNULL(SUM(papTraf.expended),0)',
                'status_name'=>'pap_status.name',
                'status'=>'pap_status.id',
                'status_description'=>'pap_status.description'
             );
             
             $select = $db->select()
                  ->from(array('pap'=>'adrev_pap'),$_columns)
                  ->join(array('pap_status'=>'adrev_paps_statuses'),'pap.status=pap_status.id',array())
                  ->join(array('options'=>'pap_options'),
                          'options.pap_id=pap.id',
                    array('floor_guaranteed_cpm'))
                  ->join(array('zone'=>'adrev_zones'),'zone.id=pap.zone_id',array())
                  ->join(array('unitType'=>'ad_type_units'),'unitType.id=zone.unit_id',array('unitType'=>'name'))

                  ->joinLeft(array('papTraf'=>'adrev_pap_zone'),
                  'papTraf.pap_id=pap.id'.
                   ((null!==$startDate)?(' AND papTraf.creation_date>='.$db->quote($startDate)):' ').
                   ((null!==$endDate)?(' AND papTraf.creation_date<='.$db->quote($endDate)):' '),
                            array())
                     ->joinLeft(array('cont'=>'pap_containers'), 'cont.id=pap.container_id', array(
                         'container_name'=>'name',
                     ))
                   ->order('pap.container_id ASC')
                   ->order('earned DESC')
                   ->order('pap.id DESC')
                   ->group('pap.id');
                       
             if (!empty($unitType)) {
                $select->where('unitType.id=?', $unitType);
             }
        
             if(null !== $papId) {
                //Group by date if data for 1 pap
                $select->group('papTraf.creation_date')
                       ->order('papTraf.creation_date DESC');
                $select->where('pap.id=?',$papId);
             } else {
                $BrandingStats = $this->getBrandingStatistic($userId, $startDate, $endDate, false);
             }
             $select->where('pap.user_id=?',$userId);
             
             if (is_numeric($statusId)) {
                 if ($statusId != 99 && $statusId != self::FAKE_STATUS_ID_ACTIVE_AND_AWAITING) {
                     $select->where('pap.status = ?', $statusId);
                     if ($statusId != 4) {
                         $select->where('pap.status != ?', 4);
                     }
                 } elseif ($statusId == self::FAKE_STATUS_ID_ACTIVE_AND_AWAITING) {
                     $select->where(
                         'pap.status IN (?)',
                         array(
                             User_Model_DbTable_PapStatuses::ACTIVE,
                             User_Model_DbTable_PapStatuses::AWAITING_PAPROVAL
                         )
                     );
                 }
             } else {
                 // Hide archived paps if requested status is `All`
                 $select->where('pap.status != ?', 4);
             }
             
             //need to recalculate eCPM for publishizers with floor_guaranteed_cpm
             $finalSelect = $db->select()
                     ->from(array('results'=>$select),array(
                         'results.*',
                         'eCPM'=>'ROUND(IFNULL( earned*1000/impressions ,0),2)',
                         'eCPM_revShare'=>'ROUND(IFNULL( (expended*floor_guaranteed_cpm/100)*1000/impressions,0),2)',
                         'allClicks'=>'(fcClicks+nonSlClicks)',                         
                     ));
                     
             $papsStats = $db->fetchAll($finalSelect);                
        }

        $result = array();
        $columns = $this->getAllowedColumns($userId);
        $filterColumns = array();
        
        if (!isset($columns['clicks'])) {
            $filterColumns[]='clicks';
        }
        if (!isset($columns['allClicks'])) {
            $filterColumns[]='allClicks';
        }
        
        //go through PAP result and filter columns
        foreach( array($papsStats,$BrandingStats) as $papsData ) {
            if (!empty($papsData)) {
                foreach ($papsData as $pap) {
                    foreach ($filterColumns as $column) {
                        unset($pap[$column]);
                    }
                    $result[] = $pap;
                }
            }
        }
        

        return $result;
    }
    
    /**
     * Return statistics for Conteiner pap(s) only
     * 
     * @param  int         $publishizerId
     * @param  null|int    $papId
     * @param  null|string $startDate
     * @param  null|string $endDate
     * @param  null|int    $statusId
     * @return array
     */
    public function getCrPapsStatistic($publishizerId, $papId = null,
        $startDate = null, $endDate = null, $statusId = null
    ) {
        if (empty($startDate)) {
            $startDate = null;
        }
        if (empty($endDate)) {
            $endDate = null;
        }
        
        $db             = $this->getDb();
        $statsTable     = 'adrev_pap_traffic';
        $adsTable       = 'adrev_ads';
        $papFeedTable   = 'pap_feed_assn';
        
        /*
         * Feed Article specific impressions (Impressions)
         * Used to order containers within a grid only - not present as a column
        */
        $contentImpsSelect = $db->select()
                                ->from(array('traff' => $statsTable), array('IFNULL(SUM(impressions), 0)'))
                                ->join(array('ad'    => $adsTable),     'ad.id = traff.ad_id AND ad.ad_type_id = 69', array())
                                ->join(array('af'    => $papFeedTable), 'af.feed_id = ad.user_id', array())
                                ->where('traff.pap_id = pap.id')
                                ->where('af.pap_id = pap.id');
        if (!is_null($startDate)) {
            $contentImpsSelect->where('traff.creation_date >= ?', $startDate);
        }
        if (!is_null($endDate)) {
            $contentImpsSelect->where('traff.creation_date <= ?', $endDate);
        }

        /*
         * Conteiner specific clicks (Content Clicks)
         */
        $crClicksSelect = $db->select()
                             ->from(array('traff' => $statsTable), array('IFNULL(SUM(clicks), 0)'))
                             ->join(array('ad'    => $adsTable),     'ad.id = traff.ad_id AND ad.ad_type_id = 69', array())
                             ->join(array('af'    => $papFeedTable), 'af.feed_id = ad.user_id', array())
                             ->where('traff.pap_id = pap.id')
                             ->where('af.pap_id = pap.id');
        if (!is_null($startDate)) {
            $crClicksSelect->where('traff.creation_date >= ?', $startDate);
        }
        if (!is_null($endDate)) {
            $crClicksSelect->where('traff.creation_date <= ?', $endDate);
        }
        
        /*
         * Cols
         * 
         * - Post Click Pageviews or Additional Pageviews are just conversions
         *   after a user has clicked an article (ad)
         */
        $outterCols = array(
            'res.*',
            'user_retention_pct' => 'ROUND(IFNULL(user_retention_pct, 0), 2)',
        );
        
        $innerCols = array(
            'pap_id'             => 'pap.id',
            'pap.pap_name',
            'pap.full_name',
            'date'               => 'traff.creation_date',
            'startDate'          => new Zend_Db_Expr($db->quote($startDate)),
            'endDate'            => new Zend_Db_Expr($db->quote($endDate)),
            'container_id',
            'container_name'     => 'cont.name',
            'status_description' => 'pap_status.description',
            'status_name'        => 'pap_status.name',
            'status'             => 'pap_status.id',
            'content_clicks'     => new Zend_Db_Expr("({$crClicksSelect})"),
            'impressions'        => new Zend_Db_Expr("({$contentImpsSelect})"),
            'pageviews'          => 'IFNULL(SUM(traff.orders), 0)',
            'user_retention_pct' => '((SUM(traff.clicks) / SUM(seg_traff.unique_users)) * 100)' // add check for division by zero
        );
        
        $innerStatsJoinCondTrf = 'traff.pap_id = pap.id';
        $innerStatsJoinCondSeg = 'seg_traff.segment_id = seg.segment_id';
        if (!is_null($startDate)) {
            $innerStatsJoinCondTrf .= $db->quoteInto(' AND traff.creation_date >= ?', $startDate);
            $innerStatsJoinCondSeg .= $db->quoteInto(' AND seg_traff.creation_date >= ?', $startDate);
        }
        if (!is_null($endDate)) {
            $innerStatsJoinCondTrf .= $db->quoteInto(' AND traff.creation_date <= ?', $endDate);
            $innerStatsJoinCondSeg .= $db->quoteInto(' AND seg_traff.creation_date <= ?', $endDate);
        }
        
        $innerSelect = $db->select()
                          ->from(array('pap' => 'adrev_pap'), $innerCols)
                          ->join(array('pap_status' => 'adrev_paps_statuses'),  'pap.status = pap_status.id', array())
                          ->join(array('pap_opt'    => 'pap_options'),          'pap_opt.pap_id = pap.id', array())
                          ->join(array('traff'      => $statsTable), $innerStatsJoinCondTrf, array())
                          ->join(array('ad'         => $adsTable),              'ad.id = traff.ad_id AND ad.ad_type_id = 69', array())
                          ->join(array('af'         => $papFeedTable),          'af.feed_id = ad.user_id AND af.pap_id = pap.id', array())
                          ->joinLeft(array('seg'       => 'pap_segment'), 'seg.pap_id = pap.id', array())
                          ->joinLeft(array('seg_traff' => 'segment_logs'), $innerStatsJoinCondSeg, array())
                          ->joinLeft(array('cont'      => 'pap_containers'), 'cont.id = pap.container_id', array())
                          ->where('pap.is_content_rec = ?', 1)
                          ->where('pap.user_id = ?', $publishizerId)
                          ->group('pap.id')
                          ->order('earned DESC')
                          ->order('pap.id DESC');
        
        if (is_numeric($statusId)) {
            if ($statusId != 99) {
                $innerSelect->where('pap.status = ?', $statusId);
                if ($statusId != 4) {
                    $innerSelect->where('pap.status != ?', 4);
                }
            }
        } else {
            // Hide archived paps if requested status is `All`
            $innerSelect->where('pap.status != ?', 4);
        }
        
        $outterSelect = $db->select()
                           ->from(array('res' => $innerSelect), $outterCols);
        
        return $db->fetchAll($outterSelect);
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $papId
     * @param unknown_type $papSubId
     * @param unknown_type $startDate
     * @param unknown_type $endDate
     * @return unknown
     */
    public function getSubPapStatistic($papId, $papSubId, $startDate = null, $endDate = null, $getClicks = 1)
    {
          $columns=array ('papTraf.creation_date',
                          'papTraf.sub_pap_id',
                          'impressionsSum'=>'SUM(papTraf.impressions)',
                          'clicks'=>'SUM(papTraf.clicks)',
                          'earned'=>'ROUND( ( SUM(papTraf.expended) - SUM(papTraf.earned) ), 2) ',
                          'eCPM'=>'ROUND( ( SUM(papTraf.expended) - SUM(papTraf.earned) )*1000/SUM(papTraf.impressions), 2)'
                     );
         //Remove clicks column
         if( $getClicks !==1 ) {
            unset($columns[3]);
         }    
          $select=$this->getDb()->select()
          ->from ( 
                array ('papTraf'=>'adrev_pap_zone_subpapid'),
                $columns
            )
            //additional field
/*                              ->joinLeft(array('papTraf'=>'adrev_pap_zone_subpapid'), ' papTraf.sub_pap_id="'.$papSubId.'" '. array()) */
           ->order ('papTraf.creation_date DESC')
           ->group ('papTraf.creation_date')
           ->group ('papTraf.sub_pap_id')
           ->where ('papTraf.sub_pap_id=?', $papSubId)
           ->where ('papTraf.pap_id=?', $papId);
           if (null!==$startDate) {
                $select -> where (' papTraf.creation_date>='.$this->getDb()->quote($startDate));
           }
           if (null!==$endDate) {
               $select -> where (' papTraf.creation_date<='.$this->getDb()->quote($endDate));
           }
         
         
         return $this->getDb()->fetchAll($select);
    }
    
    /**
     * Return content statistic for all paps for given publishizer
     * 
     * Will return the next fields:
     * - pap_id
     * - pap_name
     * - start_date
     * - end_date
     * - impressions (summed by pap)
     * - clicks (summed by pap)
     * - conversions (summed by pap) a.k.a. orders
     * 
     * @param  int    $publishizerId
     * @param  string $startDate   Date in format ISO8601 (YYYY-MM-DD)
     * @param  string $endDate     Date in format ISO8601 (YYYY-MM-DD)
     * @return array
     */
    public function getContentMetrics($publishizerId, $startDate, $endDate, $where = null)
    {
        $zfDate = new Zend_Date();
        $format = 'Y-M-d';
        
        if (! $zfDate->isDate($startDate, $format) || ! $zfDate->isDate($endDate, $format)) {
            $this->_throwException(User_Model_Messages::DATE_INVALID);
        }
        
        $db = $this->getDb();
        
        $cols = array(
            'at.pap_id',
            'aa.pap_name',
            'start_date'   => new Zend_Db_Expr("DATE_FORMAT('$startDate', '%m-%d-%Y')"),
            'end_date'     => new Zend_Db_Expr("DATE_FORMAT('$endDate', '%m-%d-%Y')"),
            'impressions'  => 'SUM(at.impressions)',
            'clicks'       => 'SUM(at.clicks)',
            'conversions'  => 'SUM(at.orders)'
        );
        
        $sql = $db->select()
                  ->from(array('at' => 'adrev_pap_traffic'), $cols)
                  ->join(array('aa' => 'adrev_pap'), 'aa.id = at.pap_id', null)
                  ->where('aa.user_id = ?', $publishizerId)
                  ->where('at.creation_date >= ?', $startDate)
                  ->where('at.creation_date <= ?', $endDate)
                  ->group('at.pap_id')
                  ->order('at.creation_date');
        
        if (is_array($where)) {
            foreach ($where as $w) {
                $sql->where($w[0],$w[1]);
            }
        }

        return $db->fetchAll($sql);
    }
    
    /**
     * Throws an exception
     * 
     * @param  int    $code    Error code
     * @param  string $message (Optional) Error message
     * @throws User_Model_UserException
     */
    protected function _throwException($code, $message = null)
    {
        if (is_null($message)) {
            $message = User_Model_Messages::getByCode($code);
            
            if (is_null($message)) {
                $message = 'Unknown error';
            }
        }
        
        throw new User_Model_UserException($message, $code);
    }
    
    public function getPublishizerLogoUploadPath()
    {
        return $this->_logoUploadPath;
    }

    public function getPublishizerMonthlyPageScreenUrl($userId,$year,$month)
    {
        $grabzConfig = $this->getHelper('content')->getSettings()->grabz;
        return $grabzConfig->screensFolder.$userId.'_'.$year.'_'.$month.'.'.$grabzConfig->format;
    }
    
    public function getPublishizerLogoUploadFullPath()
    {
        return PAPLICATION_PATH . '/../htdocs' . $this->_logoUploadPath;
    }
    
    public function getPublishizerReportImageFullPath($returnWebPath = false)
    {
        if ($returnWebPath) {
            return $this->_reportImagesUploadPath;
        }
        
        return PAPLICATION_PATH . '/../htdocs' . $this->_reportImagesUploadPath;
    }
    
    public function getPublishizerChartUploadFullPath()
    {
        return PAPLICATION_PATH . '/../htdocs' . $this->_logoUploadPath;
    }
    
    public function getPublishizerLogoUploadFormActionUrl($userId = null)
    {
        return $this->_logoUploadFormActionUrl . '/userId/' . $userId;
    }
    
    public function getMonthlyStatistic($userId, $year, $month, $daily = false)
    {
        $firstDate = date('Y-m-d', mktime(0, 0, 0, (int)$month, 1, (int)$year));
        $lastDate = date('Y-m-d', mktime(0, 0, 0, (int)$month+1, 0, (int)$year));
        return $this->_getMonthlyStatistic($userId, $firstDate, $lastDate, $daily);
    }
    
    public function getEcpmChartImageUrl($userId,$year,$month)
    {
        return $this->_getChartImageUrl($userId,$year,$month,'ecpm');
    }
    
    public function getImpressionsChartImageUrl($userId,$year,$month)
    {
        return $this->_getChartImageUrl($userId,$year,$month,'impressions');
    }
    
    private function _getChartImageUrl($userId,$year,$month,$suffix)
    {
        $name = (int)$userId."_".(int)$year."_".(int)$month."_".$suffix;
        $imgPath = $this->_chartUploadPath . (string)$name . '.png';
        return $imgPath;
    }
    
    /**
     * 
     * @param  int    $userId
     * @param  mixed  $year
     * @param  mixed  $month
     * @param  string $suffix
     * @param  string $yAxisName
     * @param  array  $dataArray
     * @return mixed  Null if $dataArray empty, string otherwise
     */
    public function getChartImage($userId, $year, $month, $suffix, $yAxisName,
        $dataArray=array()
    ) {
        $width   = 360;
        $height  = 125;
        $imgPath = $this->_getChartImageUrl($userId, $year, $month, $suffix);
    
        $filePath = PAPLICATION_PATH.'/../htdocs' . $imgPath;
        
        if (empty($dataArray)) {
            return null;
        }
        
        if (!is_array($dataArray)) {
            $dataArray = (array) $dataArray;
        }
        
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    
        require_once "pchart/class/pData.class.php";
        require_once "pchart/class/pDraw.class.php";
        require_once "pchart/class/pImage.class.php";
        
        $data = new pData();
        
        
        $data->addPoints($dataArray, "data");
        $data->addPoints(array_keys($dataArray), "days");
        $data->setAbscissa('days');
        $data->setAbscissaName('Days');
        $data->setAxisName(0, $yAxisName);
        $data->setAxisDisplay(0, AXIS_FORMAT_METRIC);
        $data->setPalette('data', array('R'=>28, 'G'=>133, 'B'=>208, 'Alpha'=>60));
        
        $myPicture = new pImage($width, $height, $data);
        $myPicture->drawRectangle(0, 0, $width-2, $height-2, array("R"=>204,"G"=>204,"B"=>204,'NoAngle'=>TRUE));
        $myPicture->setFontProperties(array("FontName"=>stream_resolve_include_path('pchart/fonts/calibri.ttf'),"FontSize"=>8));
        $myPicture->setGraphArea(50, 7, $width-10, $height-30);
        $scaleSettings = array("XMargin"=>0,"YMargin"=>0,"AxisAlpha"=>15,"TickR"=>0,"TickG"=>0,"TickB"=>0,"TickAlpha"=>20,"DrawXLines"=>1,'RemoveXAxis'=>false,"Mode"=>SCALE_MODE_START0,"Floating"=>TRUE,"DrawSubTicks"=>FALSE,"GridR"=>100,"GridG"=>100,"GridB"=>100,"GridAlpha"=>15);
        $myPicture->drawScale($scaleSettings);
        $myPicture->Antialias = TRUE;
        $myPicture->drawAreaChart();
        $myPicture->Antialias = FALSE;
    
        $myPicture->render($filePath);
    
        return $imgPath;
    }
    
    public function renderPdf($content, $fileName = 'report')
    {

        $siteUrl = $this->getHelper('content')->getSettings()->site->url;
        $content = str_replace('src="/', 'src="'.$siteUrl.'/', $content);
        $content = str_replace("src='/", "src='".$siteUrl."/", $content);
    
        require_once "dompdf/dompdf_config.inc.php";
        $dompdf = new DOMPDF();
        // $dompdf->set_paper("A3", "portrait");
        $dompdf->load_html($content);
        $dompdf->render();
        // echo $dompdf->output();
        $dompdf->stream($fileName.".pdf");
    }
    
    private function _getMonthlyStatistic($userId, $firstDate, $lastDate, $daily = false)
    {
        $_data = $this->getPapStatistic($userId,null,$firstDate, $lastDate);
        $totals = array(
            'earned' => 0,
            'eCPM' => 0,
            'impressions' => 0,
            'clicks' => 0
        );
        $_d = explode('-', $lastDate);
        $paps = array();
    
        $impressions = array_fill(1, $_d[2], 0);
        $earned      = array_fill(1, $_d[2], 0);
        $eCPM        = array_fill(1, $_d[2], 0);
        $dates       = array_fill(1, $_d[2], 0);
        
        $bestEarned = null;
        $bestPerfPapId = null;
        
        foreach ($_data as $_pap) {

            $papStatsPerMonth = $this->getPapStatistic($userId, $_pap['pap_id'], $firstDate, $lastDate);
            foreach ($papStatsPerMonth as $papStatsPerDay) {
                
                $empty = true;
                $_imp = 0;
                if (isset($papStatsPerDay['impressions'])) {
                    $_imp = $papStatsPerDay['impressions'];
                }
                if ($_imp != 0) {
                    $empty = false;
                }
                $totals['impressions'] += $_imp;

                $_ear = 0;
                if (isset($papStatsPerDay['earned'])) {
                    $_ear = $papStatsPerDay['earned'];
                }
                if ($_ear != 0) {
                    $empty = false;
                }
                $totals['earned'] += $_ear;

                $_cli = 0;
                if (isset($papStatsPerDay['clicks'])) {
                    $_cli = $papStatsPerDay['clicks'];
                }
                $totals['clicks'] += $_cli;

                if (!$empty) {
                    $paps[] = $papStatsPerDay['pap_id'];
                }

                if ($daily) {
                    $_d = explode('-', $papStatsPerDay['date']);
                    if (isset($_d[2])) {
                        $impressions[(int)$_d[2]] += $_imp;
                        $earned[(int)$_d[2]] += $_ear;
                        $dates[(int)$_d[2]] = $papStatsPerDay['date'];

                        if ($bestEarned < $earned[(int)$_d[2]]) {
                            $bestPerfPapId = $papStatsPerDay['pap_id'];
                        }
                    }
                }
            }
        }
        
        $totals['eCPM'] = 0;
        if ($totals['impressions'] != 0) {
            $totals['eCPM'] = $totals['impressions']?($totals['earned']*1000/$totals['impressions']):0;
        }
        
        $_lImp = 0;
        $_hImp = 0;
        $_lEcpm = 0;
        $_hEcpm = 0;
        $lowestImpression = '';
        $highestImpression = '';
        
        foreach ($impressions as $i=>$v) {
            if ($_lImp == 0) {
                $_lImp = $v*1;
            }
            
            if ($v*1 > 0 && $_lImp >= $v*1) {
                $_lImp = $v*1;
                $lowestImpression = $dates[$i];
            }
            
            if ($_hImp < $v*1) {
                $_hImp = $v*1;
                $highestImpression = $dates[$i];
            }
            
            $eCPM[$i] = $impressions[$i]*1>0?($earned[$i]*1*1000/$impressions[$i]*1):0;
            if ($_lEcpm == 0) {
                $_lEcpm = $eCPM[$i];
            }
            
            if ($eCPM[$i] > 0 && $_lEcpm >= $eCPM[$i]) {
                $_lEcpm = $eCPM[$i];
            }
            
            if ($_hEcpm < $eCPM[$i]) {
                $_hEcpm = $eCPM[$i];
            }
        }
        
        $bestPerfAdCatName = '';
        $bestUrlEcpm       = 0;
        $referer           = '';
        $bestAdEarned      = null;
        $catId             = null;
        $adId              = null;
        
        if (is_null($bestPerfPapId) === false) {
            
            $select = Pap_Model_Models::getPapsModel()->_getPapStatsSelect($bestPerfPapId, false,false, $firstDate, $lastDate);
            $_tmpData = $this->getDb(true)->fetchAll($select);
            foreach ($_tmpData as $_ad) {
                if ($_ad['earned'] > $bestAdEarned) {
                    $bestAdEarned = $_ad['earned'];
                    $adId         = $_ad['ad_id'];
                }
            }
            
            $categories = Pap_Model_Models::getAdsModel()->getNotBlockedCats($adId);
            
            if ($categories) {
                $delim = ", ";
                foreach ($categories as $category) {
                    $bestPerfAdCatName .= $category['title'] . $delim;
                }
                
                $bestPerfAdCatName = rtrim($bestPerfAdCatName, $delim);
            } else {
                $bestPerfAdCatName = 'Uncategorized';
            }
            
            $_impData = Pap_Model_Models::getAdminReportsModel()->getImpressionRefData($bestPerfPapId, $firstDate, $lastDate);
            foreach ($_impData as $_imp) {
                if ($_imp['url_ecpm'] > $bestUrlEcpm) {
                    $bestUrlEcpm = $_imp['url_ecpm'];
                    $referer     = $_imp['referer'];
                }
            }
        }
        
        return array(
            'total'             => $totals,
            'impressions'       => $impressions,
            'eCPM'              => $eCPM,
            'highestImpression' => $highestImpression,
            'lowestImpression'  => $lowestImpression,
            'highestECPM'       => $_hEcpm,
            'lowestECPM'        => $_lEcpm,
            'paps'              => count(array_unique($paps)),
            'category'          => $bestPerfAdCatName,
            'url'               => $referer ? $referer : 'Not available'
        );
    }
 
    public function createNewReport($userId,$year,$month)
    {
        $reportData = $this->getMonthlyStatistic($userId,$year,$month,true);
        $lastMonthData = $this->getMonthlyStatistic($userId,$year,$month-1);
    
        $impressionsCompare = $reportData['total']['impressions'] - $lastMonthData['total']['impressions'];
        $eCPMCompare = $reportData['total']['eCPM'] - $lastMonthData['total']['eCPM'];
    
        if ((int)$reportData['total']['impressions'] == 0) {
            return array('status'=>false,'error'=>'Publishizer has no impressions in this period');
        }

        $userData = $this->getTable()->getUsersTable()->getById($userId);
        
        $data = array(
                'publishizer_id'=>$userId,
                'year'=>$year,
                'month'=>$month,
                'impressions_data'=>json_encode($reportData['impressions']),
                'ecpm_data'=>json_encode($reportData['eCPM']),
                'increased_impressions'=>$impressionsCompare,
                'increased_ecpm'=>$eCPMCompare,
                'total_earnings'=>$reportData['total']['earned'],
                'total_impressions'=>$reportData['total']['impressions'],
                'total_clicks'=>$reportData['total']['clicks'],
                'total_ecpm'=>$reportData['total']['eCPM'],
                'highest_daily_impressions'=>$reportData['highestImpression'],
                'lowest_daily_impressions'=>$reportData['lowestImpression'],
                'highest_daily_ecpm'=>$reportData['highestECPM'],
                'lowest_daily_ecpm'=>$reportData['lowestECPM'],
                'paps_count'=>$reportData['paps'],
                'best_performing_ad_category'=>$reportData['category'],
                'best_performing_page_url'=>$reportData['url'],
                'screen_url'=>'',
                'organization'=>$userData['organization']
        );

      // $data['best_performing_page_url'] = 'google.com';

        if (!empty($data['best_performing_page_url'])) {
            $screenUrl = $this->_grabPageScreen($data['best_performing_page_url'], $userId,$year,$month);
            if ($screenUrl) {
                $data['screen_url'] = $screenUrl;
            }
        }

        $result = $this->getTable()->getPublishizerMonthlySummaryReportsTable()->createNewReport($data);
        return $result;
    }

    private function _grabPageScreen($url, $userId,$year,$month)
    {

        if (empty($url) || !$userId || !$year || !$month) {
            return null;
        }

        require_once 'grabz/lib/GrabzItClient.class.php';
        
        $grabzConfig = $this->getHelper('content')->getSettings()->grabz;

        $grabzItPaplicationKey = $grabzConfig->key;
        $grabzItPaplicationSecret = $grabzConfig->secret;

        $customId = null;
        $browserWidth = null;
        $browserHeight = null;//-1;
        $width = $grabzConfig->width;//null;//-1;
        $height = $grabzConfig->height;//null;//-1;
        $format = $grabzConfig->format;//'png';
        $delay = null;
        $targetElement = null;
        $requestMobileVersion = false;
        $customWaterMarkId = null;

        $fileRelPath = $this->getPublishizerMonthlyPageScreenUrl($userId,$year,$month);
        $fileFullPath = PAPLICATION_PATH.'/../htdocs'.$fileRelPath;

        $targetUrl = $url;

        try {
            $grabzIt = new GrabzItClient($grabzItPaplicationKey, $grabzItPaplicationSecret);
            $grabzIt->SetImageOptions($targetUrl,$customId,$browserWidth,$browserHeight,$width,$height,$format,$delay,$targetElement,$requestMobileVersion,$customWaterMarkId);
            $result = $grabzIt->SaveTo($fileFullPath);
            if ($result) {
                return $fileRelPath;
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function getReports($userId,$year=null,$month=null)
    {
          $table = $this->getTable()->getPublishizerMonthlySummaryReportsTable();
          return $table->getReports($userId,$year,$month);
    }
    
    public function getReportById($reportId)
    {
          $table = $this->getTable()->getPublishizerMonthlySummaryReportsTable();
          return $table->getById($reportId);
    }
    
    public function updateReportHtml($reportId,$html)
    {
        $table = $this->getTable()->getPublishizerMonthlySummaryReportsTable();
        return $table->updateHtml($reportId,$html);
    }

    public function updateReportStatus($reportId, $isPublished)
    {
        $table = $this->getTable()->getPublishizerMonthlySummaryReportsTable();
        return $table->updatePublishedStatus($reportId, $isPublished);
    }

    public function sendMonthlyMails($repsData, $month, $year)
    {        
        $sitesConfig = $this->getHelper('content')->getSettings()->site;
        $mailsConfig = Pap_Model_Functions::getMailSettings();
        require_once PAPLICATION_PATH . '/modules/user/controllers/_Notificator.php';
        $instance = _Notificator::getInstance();
        foreach ($repsData as $rep=>$data) {
            $repData = Pap_Model_Models::getUsersModel()->getUserDataById($rep);
            $email = $repData['notification_email'];
            $date = ' - [' . $month . ' - ' . $year . ']';
            $report = '';            
            foreach ($data as $d) {
                $report .= $d['publishizer_id'] . ' - ' . $sitesConfig->url . $d['report_url'] . '<br />';
            }
            $userData = array('date'=>$date, 'report'=>$report);            
            $subject = $instance->getSubj(_Notificator::PUBLISHIZER_MONTHLY_SUMMARY_REPORT, $userData);
            $body = $instance->getMessText(_Notificator::PUBLISHIZER_MONTHLY_SUMMARY_REPORT, $userData);
            $mail = new Zend_Mail();
            $mail->setBodyHtml($body);            
            $mail->setFrom($mailsConfig->fromEmail, $mailsConfig->fromName);
            $mail->addTo($email, $repData['name']);
            $mail->setSubject($subject);
            $mail->send();
        }
    }

    /**
     * @param  int $pubId
     * @return bool
     */
    public function isAllowedDetailedStats($pubId)
    {
        $data = $this->getTable()->getPublishizersTable()->getPublishizer($pubId);
        return $data['allow_detailed_stats']?true:false;
    }

    /**
     * @param  int $pubId
     * @return bool
     */
    public function isAllowedSessionValue($pubId)
    {
        $data = $this->getTable()->getPublishizersTable()->getPublishizer($pubId);
        return $data['allow_session_value'] ? true : false;
    }

    /**
     * @param  int $containerId
     * @param  int $pubId
     * @return bool
     */
    public function isContainerOwner($containerId, $pubId)
    {
        $data = $this->getTable()->getPapContainersTable()->getContainer($containerId, $pubId);
        return count($data) > 0;
    }

    /**
     * @param $pubId
     * @return true if is self-serve, false if not
     */
    public function isSelfServe($pubId)
    {
        $data = $this->getTable()->getPublishizersTable()->getPublishizer($pubId);
        return isset($data['allow_selfserve_builder']) && $data['allow_selfserve_builder'] == 1;
    }

    /**
     * @param $pubId
     * @return true if the publishizer has paps awaiting paproval
     */
    public function hasPapsAwaitingPaproval($pubId)
    {
        $db     = $this->getDb();
        $select = $db->select()
                     ->from(
                         array('paps' => 'adrev_pap'),
                         array('count' => new Zend_Db_Expr('COUNT(*)'))
                     )
                     ->where('paps.user_id = ?', $pubId)
                     ->where('paps.status = ?', User_Model_DbTable_PapStatuses::AWAITING_PAPROVAL);

        $stmt = $db->query($select);

        return (boolean)$stmt->fetchColumn(0);
    }
}
