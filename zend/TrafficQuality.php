<?php

use App_Model_Links as Links;

class App_Model_TrafficQuality extends App_Model_Abstract
{
    protected $_ipRangeForm;
    protected $_userAgentForm;

    /**
     * Expire IPs without activate after this days count
     */
    const IPS_DAYS_EXPIRATION               = 4;

    public function getUserAgentGrid($path)
    {
        $select = $this->getDb()->select()
            ->from(App_Model_Tables::USER_AGENT_BLACK_LIST);

        return $this->_getGrid($select, $path);
    }

    public function getIpsGrid($path)
    {
        $select = $this->getDb()->select()
            ->from(App_Model_Tables::IP_BLACK_LIST, array(
                'id',
                'start_ip_addr'  =>'INET_NTOA(start_ip)',
                'end_ip_addr'    =>'INET_NTOA(end_ip)',
                'start_ip',
                'end_ip',
                'description',
                'is_permanent'=>'IF(is_permanent=1,"YES","NO")',
                'last_activity_datetime',
                'creation_datetime'
            ));
        return $this->_getGrid($select, $path);
    }

    protected function _getGrid($select, $path)
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setSelect($select)
            ->setLimit(1000);
        $grid->addDefaultColumn();
        $grid->addColumn('edit',
            array('header'=>'Actions',
                'index'=>'id',
                'type'=>'action',
                'width'=>18,
                'actions'=>array(
                    'url'=>$path.'/id/$id',
                    'image'=>'/images/design/icons/edit.png',
                    'caption'=>'edit'
                ),
            ))
            ->addColumn('-',
                array('header'=>'Del',
                    'type'=>'action',
                    'width'=>18,
                    'index'=>'id',
                    'actions'=>array(
                        'url'=>$path.'/delete-id/$id',
                        'image'=>'/images/design/icons/delete.png',
                        'caption'=>'delete',
                        'confirm'=>'Are you sure want to delete [$id]?')
                ))
        ;
        return $grid;
    }

    public function deleteIp($id)
    {
        return $this->getTable()->getIpBlackListTable()->delete('id='.(int)$id);
    }

    public function getIpRangeForm($id=null, $freshForm=false)
    {
        if (null===$this->_userAgentForm || true==$freshForm) {
            $form = new Admin_Form_IpRange();
            if (null!==$id) {
                $data = $this->getTable()->getIpBlackListTable()
                    ->getById($id);
                if (empty($data)) {
                    $msg = "The #{$id} does not exist";
                    throw new System_Model_UserException($msg);
                }
                $data = $data->toArray();
                $data['start_ip'] = long2ip($data['start_ip']);
                $data['end_ip'] = long2ip($data['end_ip']);
                $form->populate($data);
                $form->submit->setLabel('Save');
            }
            if (true===$freshForm) {
                return $form;
            }
            $this->_userAgentForm = $form;
        }
        return $this->_userAgentForm;
    }

    public function saveIp($data, $id, $freshForm=false)
    {
        if (!empty($data) && !empty($data['hiddenField'])) {
            $form = $this->getIpRangeForm($id, $freshForm);
            if ($form->isValid($data)) {
                $values = $form->getValues();
                $values['start_ip'] = sprintf("%u", ip2long($data['start_ip']));
                $values['end_ip'] = sprintf("%u", ip2long($data['end_ip']));
                $table = $this->getTable()->getIpBlackListTable();

                if (null===$id) {
                    $id = $table->insert($values);
                    return $id;
                } else {
                    return $table->update($values, 'id='.(int)$id);
                }
            }
        }
        return false;
    }

    public function deleteUserAgent($id)
    {
        return $this->getTable()->getUserAgentBlackListTable()->delete('id='.(int)$id);
    }

    public function getUserAgentForm($id=null)
    {
        if (null===$this->_ipRangeForm) {
            $form = new Zend_Form();
            $form->addElements(array(
                $form->createElement('text','agent')
                    ->setLabel('User Agent:')
                    ->setRequired(true),
                $form->createElement('text','description')
                    ->setLabel('Description:')
                    ->setRequired(true),
                $form->createElement('hidden', 'hiddenField')
                    ->setValue(1)
                    ->setIgnore(true)
                    ->setDecorators(array('ViewHelper')),
                $form->createElement('submit', 'submit', array(
                    'type'=>'submit',
                    'ignore'=>true,
                    'label'=>'Save'
                )),
            ));
            if (null!==$id) {
                $data = $this->getTable()->getUserAgentBlackListTable()
                    ->fetchRow('id='.(int)$id);
                if (empty($data)) {
                    $msg = "The #{$id} does not exist";
                    throw new System_Model_UserException($msg);
                }
                $data = $data->toArray();
                $form->populate($data);
                $form->submit->setLabel('Save');
            }
            $this->_ipRangeForm = $form;
        }
        return $this->_ipRangeForm;
    }

    public function saveUserAgent($data, $id)
    {
        if (!empty($data) && !empty($data['hiddenField'])) {
            $form = $this->getUserAgentForm($id);
            if ($form->isValid($data)) {
                $values = $form->getValues();
                $table = $this->getTable()->getUserAgentBlackListTable();
                if (null===$id) {
                    $id = $table->insert($values);
                    return $id;
                } else {
                    return $table->update($values, 'id='.(int)$id);
                }
            }
        }
        return false;
    }

    /**
     * Import data, purge old
     * @param $filePath
     * @return bool
     */
    public function importBlackListedIps($filePath)
    {
        $this->loadIps($filePath);
        $this->deleteOldIps();
        return false;
    }

    public function loadIps($filePath)
    {
        if (!file_exists($filePath)) {
            throw new App_Model_Exception("File doesn't exist: $filePath");
        }
        $table = $this->getTable()->getIpBlackListTable();
        if (($handle = fopen($filePath, 'r')) !== false) {
            $line = 1;
            while (($data = fgetcsv($handle)) !== false) {
                $errException = new App_Model_Exception("File is not valid on line #{$line}: {$filePath}");
                if (count($data)!=3) {
                    throw $errException;
                }

                list($ip, $imps, $clicks) = $data;
                if ($ip=='0') {
                    throw new Exception('Line #'.$line);
                }
                $ipAddr = long2ip($ip);
                if ($ipAddr=='0') {
                    throw new Exception('Line #'.$line);
                }
                if (false===ip2long($ipAddr)) {
                    throw $errException;
                }

                $now = date('Y-m-d H:i:s');
                $record = $table->getByIp($ip);
                if (!$record) {
                    //Create new record in database - ip doesn't present
                    $_newIpData = array(
                        'start_ip'=>$ipAddr,
                        'end_ip'=>$ipAddr,
                        'is_permanent'=>0,
                        'description'=>'Imported from VS',
                        'hiddenField'=>true
                    );
                    $newId = $this->saveIp($_newIpData, null, true);
                    if (empty($newId)) {
                        throw new App_Model_Exception('Can not save imported IP: '.var_export($_newIpData, true));
                    }
                    $record = $table->getById($newId);
                    $record->status_id = 1;
                    $record->save();
                }
                //Update datetime for the IP
                $record->last_activity_datetime = $now;
                $record->save();

                //$this->saveIp(array(), $record?$id)
                $line++;
            }
            fclose($handle);
        }
        return false;
    }

    /**
     * //Delete not permanent IPs without activity in last 4 days
     * @return int number of removed IPs
     */
    public function deleteOldIps()
    {
        //Delete not permanent IPs without activity in last 4 days
        $date= date('Y-m-d H:i:s', time()-86400*self::IPS_DAYS_EXPIRATION);
        $removed = $this->getTable()->getIpBlackListTable()
            ->delete(array('is_permanent=0','last_activity_datetime<?'=>$date));
        return $removed;
    }

    public function getStatsGrid()
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $statuses = new System_Search_Filters_Select();
        $actionStatuses = App_Model_RtbEx::$statuses;
        $statuses->setOptions($actionStatuses)
            ->setLabel('Block Rule Type');
        $grid->setDefaultDir('desc');
        $grid->setDefaultSort('expended');
        $grid->setLimit(1000);
        $grid->setFilters(array('Dates','AppId',$statuses));
        $grid->getFilter('Dates')->setDefault(System_Search_Date::TODAY);

        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('Dates')->getValue('endCalendarDate');
        $appId = $grid->getFilter('AppId')->getValue();
        $actionStatusId = $statuses->getValue();

        $data = App_Di::instance()->getRtbEx()
            ->getFilteredTraffic($startDate, $endDate, $appId, $actionStatusId);
        foreach ($data as &$_item) {
            $_item['actionStatusName'] = $actionStatuses[$_item['actionStatusId']];
            $appData = $this->getTable()->getAppsTable()->getById($_item['appId']);
            $_item['appName'] = $appData?$appData->full_name:'N/A';
        }
        $grid
            ->addColumn('appId',            array('header'=>'App Id',
                    'type'=>'link','links'=>Links::trafficQualityAppStatsGrid()))
            ->addColumn('appName',          array('header'=>'App Name'))
            ->addColumn('actionStatusName', array('header'=>'Block Type Rule'))
            ->addColumn('imps',             array('header'=>'Imps', 'type'=>'number', 'showTotals'=>true))
            ->addColumn('clicks',           array('header'=>'Clicks', 'type'=>'number', 'showTotals'=>true))
            ->addColumn('expended',         array('header'=>'Missed Revenue', 'type'=>'money', 'showTotals'=>true));
        $grid->setShowTotals(true);
        $grid->setDataSource(new System_Grid_DataGrid_DataSource_Array($data));
        return $grid;
    }

    public function getStatsAppGrid($appId)
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $statuses = new System_Search_Filters_Select();
        $types = new System_Search_Filters_Select();
        $actionStatuses = App_Model_RtbEx::$statuses;

        $actionTypes = App_Model_RtbEx::$actionTypes;
        $statuses->setOptions($actionStatuses)
            ->setLabel('Block Rule Type');

        $types->setOptions($actionTypes)
            ->setLabel('Traffic Type')
            ->setDefaultValue(App_Model_RtbEx::ACTION_TYPE_CLICK);;

        $grid->setDefaultDir('desc');
        $grid->setDefaultSort('expended');

        $grid->setLimit(2000);
        $grid->setFilters(array('Dates','AdId',$statuses,$types));
        $grid->getFilter('Dates')->setDefault(System_Search_Date::TODAY);

        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('Dates')->getValue('endCalendarDate');
        $adId = $grid->getFilter('AdId')->getValue();
        $actionStatusId = $statuses->getValue();
        $actionTypeId = $types->getValue();

        $data = App_Di::instance()->getRtbEx()
            ->getFilteredTrafficApp($startDate, $endDate, $appId, $adId, $actionStatusId, $actionTypeId);
        foreach ($data as &$_item) {
            $_item['actionStatusName'] = $actionStatuses[$_item['actionStatusId']];
            $ad = $this->getTable()->getAdsTable()->getById($_item['adId']);
            $_item['adName'] = $ad?$ad->title:'N/A';
            $_item['actionName'] = $actionTypes[$_item['actionId']];
        }
        $grid->setDataSource(new System_Grid_DataGrid_DataSource_Array($data));
        $grid
            ->addColumn('adId',             array('header'=>'Ad Id'))
            ->addColumn('adName',           array('header'=>'Ad Name'))
            ->addColumn('actionName',       array('header'=>'Block Type Rule'))
            ->addColumn('actionStatusName', array('header'=>'Traffic Type'))
            ->addColumn('ip',               array('header'=>'IP'))
            ->addColumn('country',          array('header'=>'Country'))
            ->addColumn('url',              array('header'=>'Url'))
            ->addColumn('expended',         array('header'=>'Missed Revenue', 'type'=>'money'))
            ->addColumn('creationDateTime', array('header'=>'DateTime'));

        $filename = "app_blocked_traffic_{$startDate}-{$endDate}";
        $grid->setExportable(true)->setExportFilename($filename);

        $_fields = array();
        foreach ($grid->getColumns() as $column ) {
            $_fields[]=$column->getId();
        }
        $grid->setExportColumns($_fields);

        return $grid;
    }

    public function getAppPendingReviewStatsGrid()
    {
        $grid = new System_Grid_DataGrid(new System_Grid_DataGrid_DataSource_DbSelect());
        $grid->setDefaultDir('desc');
        $grid->setDefaultSort('expended');
        $grid->setLimit(1000);
        $grid->setFilters(array('Dates','AppId'));
        $grid->getFilter('Dates')->setDefault(System_Search_Date::TODAY);

        $startDate = $grid->getFilter('Dates')->getValue('startCalendarDate');
        $endDate= $grid->getFilter('Dates')->getValue('endCalendarDate');
        $appId = $grid->getFilter('AppId')->getValue();

        $data = App_Di::instance()->getRtbEx()
            ->getTrafficPendingReview($startDate, $endDate, $appId);
        foreach ($data as &$_item) {
            $appData = $this->getTable()->getAppsTable()->getById($_item['appId']);

            $_item['appName'] = $appData?$appData->full_name:'N/A';
            $_item['traffFilterPct'] = $appData?(int)$appData->traff_filter_pct:'N/A';
        }
        $grid
            ->addColumn('appId',            array('header'=>'App Id',
                'type'=>'link','links'=>Links::trafficQualityAppStatsGrid()))
            ->addColumn('appName',          array('header'=>'App Name'))
            ->addColumn('traffFilterPct',   array('header'=>'% to Filter','type'=>'number'))
            ->addColumn('appImps',          array('header'=>'Imps', 'type'=>'number', 'showTotals'=>true))
            ->addColumn('clicks',           array('header'=>'Clicks', 'type'=>'number', 'showTotals'=>true))
            ->addColumn('earned',           array('header'=>'Missed Pub Earned', 'type'=>'money', 'showTotals'=>true))
            ->addColumn('expended',         array('header'=>'Missed Revenue', 'type'=>'money', 'showTotals'=>true));
        $grid->setShowTotals(true);
        $grid->setDataSource(new System_Grid_DataGrid_DataSource_Array($data));
        return $grid;
    }
}
