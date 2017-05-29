<?php

class User_Model_Schedule extends System_Model_Abstract
{
    protected $_sessionNameSpace = 'User_Model_Schedule';
    
    protected $_createForm = null;
    
    /**
     *
     * @param type $start   Timestamp
     * @param type $end     Timestamp
     * @param type $aid
     * @param type $oid
     * @param type $userId
     * @return type 
     */
    public function getPeriodsListForCalendar($start, $end, $aid, $oid, $userId)
    {
        if ($oid) {
            $blackDates = $this->getOfferBlackedDatesList($start, $end, $oid, false);
        } else {
            $blackDates = array();
        }
        if ($oid && $aid) {
            $reservedExclusively = $this->getOfferExclusivelyReservedPeriods($oid, $aid);
        } else {
            $reservedExclusively = array();
        }
        
        $start = date("Y-m-d", $start) . ' 00:00:00';
        $end   = date("Y-m-d", $end) . ' 23:59:59';
        
        if ($oid) {
            $offset = $this->getTable()->getCitiesUsTable()->getTimezoneOffsetByOfferId($oid);
        } else {
            $offset = 0;
        }
        $start  = $this->_convertToServer($start, $offset);
        $end    = $this->_convertToServer($end, $offset);
        
        // Fix for offers which are scheduled for several months
        if ($aid && $oid) {
            $_periods = $this->getTable()->getAppOfferPromotions()->getByAppOfferIds($aid, $oid);
        } else {
            $_periods = $this->getTable()->getAppOfferPromotions()->fetchAll();
        }
        foreach ($_periods as $_period) {
            if (strtotime($_period['promotion_start']) < strtotime($start)) {
                $start = date("Y-m-d H:i:s", strtotime($_period['promotion_start']));
            }
            
            if (strtotime($_period['promotion_end']) > strtotime($end)) {
                $end = date("Y-m-d H:i:s", strtotime($_period['promotion_end']));
            }
        }
        
        $periods = $this->getTable()->getAppOfferPromotions()->getByDatesAndParams($start, $end, $userId, $aid, $oid);
        
        $events = array();
        foreach ($periods as $i => $period) {
            // Prepare dates
            $offset = $this->getTable()->getCitiesUsTable()->getTimezoneOffsetByOfferId($period['offer_id']);
            $start  = $this->_convertToClient($period['promotion_start'], $offset);
            $end    = $this->_convertToClient($period['promotion_end'], $offset);
            
            $events[$i]['id']    = $period['id'];
            $events[$i]['aid']   = $period['app_id'];
            $events[$i]['oid']   = $period['offer_id'];
            $events[$i]['title'] = $period['app_name'] .' - '. $period['offer_name'];
            $events[$i]['start'] = $start;
            $events[$i]['end']   = $end;
            $events[$i]['qtip']  = "<div class='periodDeleteBtn'><input type='submit' value='' onclick=\"removePromotionPeriod('{$start}', '{$end}', {$aid}, {$oid})\"></div>";
        }
        
        $events = array_merge($blackDates, $reservedExclusively, $events);
        
        return $events;
    }
    
    public function getViewPromotionScheduleEvents($start, $end, $resellerId = null, $offerType = null, $offerValue = null, $isSupplier = false, $supplierId = null, $isAdmin = null)
    {
        $start = date("Y-m-d", $start) . ' 00:00:00';
        $end   = date("Y-m-d", $end) . ' 23:59:59';
        
        $sql = $this->_getAdapter()
                    ->select()
                    ->from(array('o'=>'offers'), array())
                    ->join(array('c'=>'cities_us'), 'c.id = o.city_id', array())
                    ->join(array('t'=>'timezones'), 't.id = c.timezone_id', array('offset'))
                    ->group('t.offset')
                    ->order('t.offset DESC');
        
        $gmtOffsets = $this->_getAdapter()->fetchAll($sql, array(), zend_db::FETCH_COLUMN);
        
        $lb = $gmtOffsets[0];
        $hb = $gmtOffsets[count($gmtOffsets) - 1];
        
        $_start  = $this->_convertToServer($start, $lb);
        $_end    = $this->_convertToServer($end, $hb);
        
        $offerId = ($offerType == 'offerId') ? $offerValue : null;
        $offerTitle = ($offerType == 'offerTitle') ? $offerValue : null;
        $offerBusinessName = ($offerType == 'offerBusinessName') ? $offerValue : null;
        
        if ($isSupplier) {
            $sql = $this->_getAdapter()
                ->select()
                ->from(array('o'=>'offers'), array('offer_name'=>'title','offer_id'=>'id'))
                ->joinInner(array('aop'=>'app_offer_promotions'), 'o.id = aop.offer_id')
                ->join(array('a'=>'apps'), 'a.id = aop.app_id', array('app_name'=>'name'))
                ->join(array('u'=>'users'), 'u.id = a.reseller_id', array('reseller_org'=>'organization', 'reseller_name'=>'CONCAT_WS(" ", first_name, last_name)'))
                ->join(array('cu'=>'cities_us'), 'cu.id = o.city_id', array())
                ->join(array('tz'=>'timezones'), 'tz.id = cu.timezone_id', array('offset'))
                ->where('o.supplier_id = ?', $supplierId)
                ->where('promotion_start >= ?', $_start)
                ->where('promotion_end <= ?', $_end);
        } else {
            $sql = $this->_getAdapter()
                ->select()
                ->from(array('aop'=>'app_offer_promotions'), array('id', 'promotion_start', 'promotion_end', 'is_exclusive'))
                ->join(array('a'=>'apps'), 'a.id = aop.app_id', array('app_name'=>'name'))
                ->join(array('u'=>'users'), 'u.id = a.reseller_id', array('reseller_org'=>'organization', 'reseller_name'=>'CONCAT_WS(" ", first_name, last_name)'))
                ->join(array('o'=>'offers'), 'o.id = aop.offer_id', array('offer_name'=>'title','offer_id'=>'id'))
                ->join(array('cu'=>'cities_us'), 'cu.id = o.city_id', array())
                ->join(array('tz'=>'timezones'), 'tz.id = cu.timezone_id', array('offset'))
                ->where('promotion_start >= ?', $_start)
                ->where('promotion_end <= ?', $_end);
        }
        
        if ($resellerId) {
            $sql->where('a.reseller_id = ?', $resellerId);
        }
        
        if ($offerId) {
            $sql->where('o.id = ?', $offerId);
        }

        if ($offerTitle) {
            $sql->where("o.title LIKE '%{$offerTitle}%'");
        }
        
        if ($offerBusinessName) {
            $sql->where("o.business_name LIKE '%{$offerBusinessName}%'");
        }
     
        $periods = $this->_getAdapter()->fetchAll($sql);
        
        $events = array();
        foreach ($periods as $i => $period) {
            $pmStart  = $this->_convertToClient($period['promotion_start'], $period['offset']);
            $pmEnd    = $this->_convertToClient($period['promotion_end'], $period['offset']);
            
            // Render qtip content
            $qtip = '';
            $view = new Zend_View();
            $view->setScriptPath(APPLICATION_PATH . '/modules/user/views/scripts/schedule/partials/');
            $view->assign(array(
                'resellerOrg'   => $period['reseller_org'],
                'resellerName'  => $period['reseller_name'],
                'appName'       => $period['app_name'],
                'offerName'     => $period['offer_name'],
                'offerId'       => $period['offer_id'],
                'start'         => date("M j Y", strtotime($pmStart)),
                'end'           => date("M j Y", strtotime($pmEnd)),
                'exclusive'     => $period['is_exclusive'] ? 'Yes' : 'No',
                'isSupplier'    => $isSupplier,
                'isAdmin'    => $isAdmin
            ));
            $qtip = $view->render('view-promotion-schedule-qtip.phtml');
        
            $events[$i]['id']    = $period['id'];
            $events[$i]['title'] = $period['app_name'] .' - '. $period['offer_name'];
            $events[$i]['start'] = $pmStart;
            $events[$i]['end']   = $pmEnd;
            $events[$i]['offer_id'] = $period['offer_id'];
            $events[$i]['qtip']  = $qtip;
            if ($period['is_exclusive']) {
                $events[$i]['color']  = '#CC3387';
            }
        }
        
        return $events;
    }
    
    /**
     * Get all exclusive promotions for given offer
     *
     * @param   int $oid    Offer ID
     * @param   int $aid    App ID
     * @return  array
     */
    public function getOfferExclusivelyReservedPeriods($oid, $aid)
    {
        $exPromotions = $this->getTable()->getAppOfferPromotions()->getByOfferId($oid, $exclusiveOnly = true);
        
        $events = array();
        
        if ($exPromotions) {
            
            // Prep dates
            $offset = $this->getTable()->getCitiesUsTable()->getTimezoneOffsetByOfferId($oid);
            
            $evtBgColor     = '#696969';
            $evtBrdrColor   = '#666666';
            $evtTitle       = 'NOT AVAILABLE FOR SCHEDULE';
            
            foreach ($exPromotions as $promo) {
                // Skip exclusive promotions the user created himself,
                // e.g. show exc. promotions created by other resellers only
                if (($oid == $promo['offer_id']) && ($aid == $promo['app_id'])) {
                    continue;;
                }
                
                $events[] = array(
                    'start' => $this->_convertToClient($promo['promotion_start'], $offset),
                    'end' => $this->_convertToClient($promo['promotion_end'], $offset),
                    'backgroundColor' => $evtBgColor,
                    'borderColor' => $evtBrdrColor,
                    'title' => $evtTitle,
                    'editable' => false
                );
            }
        }
        
        return $events;
    }
    
    /**
     * Get blacked out dates for given offer
     *
     * @param   string  $start      Timestamp
     * @param   string  $end        Timestamp
     * @param   int     $oid        Offer ID
     * @param   bool    $forEdit    (Optioanl) Default is true
     * @return  array
     */
    public function getOfferBlackedDatesList($start, $end, $oid, $forEdit = true)
    {
        $start = date("Y-m-d", $start) . ' 00:00:00';
        $end   = date("Y-m-d", $end) . ' 23:59:59';
        
        $offset = $this->getTable()->getCitiesUsTable()->getTimezoneOffsetByOfferId($oid);
        $start  = $this->_convertToServer($start, $offset);
        $end    = $this->_convertToServer($end, $offset);
        
        $tbl = $this->getTable()->getOfferBlackoutDatesTable();
        $blockPeriods = $tbl->getByOfferId($oid);
        
        // Offer doesn't have any blocks
        if (count($blockPeriods) < 1) {
            return array();
        }
        
        $evtBgColor     = '#FF6347';
        $evtBrdrColor   = '#FF0000';
        $evtTitle       = 'NOT AVAILABLE FOR SCHEDULE';
        $qtipContent    = $forEdit ? "<div class='periodDeleteBtn'><input type='submit' value='' onclick=\"javascript:removeBlackDates('{$oid}', '%BLOCKID%');return false;\"></div>" : '';
        
        $events = array();
        foreach ($blockPeriods as $period) {
            switch ($period->block_type) {
                case User_Model_DbTable_OfferBlackoutDates::BLOCKTYPE_DOW:
                    // Index of the day of the week the offer is blacked out on
                    $dowBlockedOn = $period->dow;

                    $_start = strtotime($start);
                    $_end   = strtotime($end);
                    
                    for ($_start; $_start < $_end; $_start = strtotime("+1 DAY", $_start)) {
                        $currentDow = date("N", $_start);
                        if ($dowBlockedOn == $currentDow) {
                            // Show to user the dates converted to the offer's timezone
                            $calStart   = $this->_convertToClient(date("Y-m-d H:i:s", $_start), $offset);
                            $calEnd     = $calStart;
                            
                            $events[] = array(
                                'start' => $calStart,
                                'end' => $calEnd,
                                'backgroundColor' => $evtBgColor,
                                'borderColor' => $evtBrdrColor,
                                'title' => $evtTitle,
                                'editable' => false,
                                'qtip' => str_replace('%BLOCKID%', $period->id, $qtipContent)
                            );
                        }
                    }

                    break;
                case User_Model_DbTable_OfferBlackoutDates::BLOCKTYPE_PERIOD:
                    $events[] = array(
                        'start' => $this->_convertToClient($period->period_start, $offset),
                        'end' => $this->_convertToClient($period->period_end, $offset),
                        'backgroundColor' => $evtBgColor,
                        'borderColor' => $evtBrdrColor,
                        'title' => $evtTitle,
                        'editable' => false,
                        'qtip' => str_replace('%BLOCKID%', $period->id, $qtipContent)
                    );

                    break;
                default:
                    break;
            }
        }
        
        return $events;
    }
    
    /**
     *
     * @param type $oid
     * @param type $type
     * @param type $dow
     * @param type $start
     * @param type $end 
     * @return  bool
     */
    public function setOfferBlackedDates($oid, $type, $dow = null, $start = null, $end = null)
    {
//        $this->validateOfferBlackedDatesData($type, $dow, $start, $end);

        $tbl = $this->getTable()->getOfferBlackoutDatesTable();
        
        switch ($type) {
            case User_Model_DbTable_OfferBlackoutDates::BLOCKTYPE_DOW:
                $data['block_type'] = User_Model_DbTable_OfferBlackoutDates::BLOCKTYPE_DOW;
                $data['dow']  = (int)$dow;
                $data['offer_id'] = $oid;
                $tbl->insert($data);
                
                break;
            case User_Model_DbTable_OfferBlackoutDates::BLOCKTYPE_PERIOD:
                if (strpos($start, 'T') === false) {
                    $start = str_replace('/', '-', $start) . ' 00:00:00';
                }
                if (strpos($end, 'T') === false) {
                    $end = str_replace('/', '-', $end) . ' 23:59:59';
                }
                
                $offset = $this->getTable()->getCitiesUsTable()->getTimezoneOffsetByOfferId($oid);
                $start  = $this->_convertToServer($start, $offset);
                $end    = $this->_convertToServer($end, $offset);
                
                $data['block_type'] = User_Model_DbTable_OfferBlackoutDates::BLOCKTYPE_PERIOD;
                $data['period_start'] = $start;
                $data['period_end'] = $end;
                $data['offer_id'] = $oid;
                $tbl->insert($data);
                
                break;
            default:
                return false;
        }
        
        return true;
    }
    
    /**
     * Delete offer's black out dates by offer ID and optional blackout period ID
     *
     * @param   int $oid    Offer ID
     * @param   int $bid    (Optional) Block period ID
     * @return  bool
     */
    public function deleteOfferBlackedDates($oid, $bid = null)
    {
        $tbl = $this->getTable()->getOfferBlackoutDatesTable();
        
        $blockPeriods = $tbl->getByOfferId($oid);
        if ($bid) {
            $blockPeriods = $tbl->getById($bid);
        }
        
        if (! empty($blockPeriods)) {
            $where = $bid ? array('id = ?'=>$bid) : array('offer_id = ?'=>$oid);
            $tbl->delete($where);
        } else {
            throw new User_Model_UserException("Such offer's black out period doesn't exists.");
        }
        
        return true;
    }
    
    /**
     * Validates offer's blacked dates data
     *
     * @param   string  $type
     * @param   mixed   $dow
     * @param   string  $start
     * @param   string  $end 
     * @return  void
     * @throws  User_Model_UserException if given block type is unsupported
     * @throws  User_Model_UserException if day of week has incorrect format
     * @throws  User_Model_UserException if day of week number is invalid
     * @throws  User_Model_UserException if day of week name is invalid
     */
    public function validateOfferBlackedDatesData($type, $dow = null, $start = null, $end = null)
    {
        // Check offer has valid block type
        $validTypes = System_Db_Tables::getOfferBlackoutDatesTable()->getBlockTypes();
        if (! in_array($type, $validTypes)) {
            throw new User_Model_UserException("Given block type is unsupported.");
        }
        
        // Check day of week is valid
        if ($type == User_Model_DbTable_OfferBlackoutDates::BLOCKTYPE_DOW) {
            if (is_null($dow) || empty($dow)) {
                throw new User_Model_UserException("Day of week format is incorrect.");
            }
            
            $validDows = System_Db_Tables::getOfferBlackoutDatesTable()->getDowNames();
            
            if (is_numeric($dow) && ! array_key_exists($dow, $validDows)) {
                throw new User_Model_UserException("Day of week number is invalid.");
            }

            if (! is_numeric($dow)) {
                $dow = ucfirst($dow);
                if (! in_array($dow, $validDows)) {
                    throw new User_Model_UserException("Day of week name is invalid.");
                }
            }
        }
    }

    /**
     * Add new promotion schedule period
     * 
     * @param 	int		$uid	User ID
     * @param 	int		$start	Promotion period start date
     * @param	int		$end	Promotion period end date
     * @param	int		$aid	Application ID
     * @param 	int		$oid	Offer ID
     * @return	bool			true if success
     * @throws	User_Model_UserException
     */
    public function addPromotionPeriod($uid, $start, $end, $aid, $oid, $suppress = null)
    {
        // Prep dates
        $start = str_replace('/', '-', $start);
        $end   = str_replace('/', '-', $end);
        
        if (! $suppress) {
            if ($this->getTable()->getResellersTable()->getDataById($uid)->use_exclusive_schedule) {
                if (System_Models::getResellersModel()->isScheduledByOther($uid, $oid, $start, $end)) {
                    throw new User_Model_UserException('Notification: A publisher in our system has scheduled this offer to run during the time frame you selected. To continue with your reservation, click on the "Schedule" button below.');
                }
            }
        }
        
        try {
            System_Models::getResellersModel()->setOfferPromotionSchedule($uid, $aid, $oid, $start, $end);
        } catch (User_Model_UserException $e) {
            throw new User_Model_UserException($e->getMessage());
        }
        
        return true;
    }
    
    /**
     * Update an existing offer promotion period
     *
     * @param	int     $uid	Reseller ID
     * @param	int	$pid	Promotion period ID
     * @param	int	$start	Start date
     * @param	int	$end	End date
     * @return  bool    
     */
    public function updatePromotionPeriod($uid, $pid, $start, $end)
    {
        if ($period = $this->getTable()->getAppOfferPromotions()->getById($uid, $pid)) {
            // Prep dates
            $offset    = $this->getTable()->getCitiesUsTable()->getTimezoneOffsetByOfferId($period['offer_id']);
            $origStart = $this->_convertToClient($period['promotion_start'], $offset);
            $origEnd   = $this->_convertToClient($period['promotion_end'], $offset);
            
            try {
                System_Models::getResellersModel()->updateOfferPromotionSchedule($uid, $period['app_id'], $period['offer_id'], $origStart, $origEnd, $start, $end);
            } catch (User_Model_UserException $e) {
                throw new User_Model_UserException($e->getMessage());
            }
        } else {
            throw new User_Model_UserException("Offer doesn't scheduled for this period.");
        }
        
        return true;
    }
    
    /**
     * Removes for given appOffer assn given period
     * 
     * @param	int		$uid	Reseller ID
     * @param	string          $start	Date in ISO8601 calendar format
     * @param	string          $end	Date in ISO8601 calendar format
     * @param	int		$aid	Application ID
     * @param	int		$oid	Offer ID
     * @return	bool                    true if period(s) removed
     */
    public function removePromotionPeriod($uid, $start, $end, $aid, $oid)
    {
        // Prep dates
        list($start, $time) = explode(' ', $start);
        list($end, $time)   = explode(' ', $end);
        
        try {
            System_Models::getResellersModel()->removeAllOfferPromotionSchedules($uid, $aid, $oid, $start, $end);
        } catch (User_Model_UserException $e) {
            throw new User_Model_UserException($e->getMessage());
        }
        
        return true;
    }
    
    /**
     * Just a shortcut for helper's convert method
     * 
     * @param 	string $datetime	Datetime string
     * @param 	string $offset		Offer TZ offset
     * @return	string
     */
    private function _convertToClient($datetime, $offset)
    {
        $helper = $this->getHelper('Date');
        $converted = $helper->convertDateTimeServerToClient($datetime, $offset);
        return date('Y-m-d H:i:s', strtotime($converted));
    }
    
    /**
     * Just a shortcut for helper's convert method
     * 
     * @param 	string $datetime	Datetime string
     * @param 	string $offset		Offer TZ offset
     * @return	string
     */
    private function _convertToServer($datetime, $offset)
    {
        $helper = $this->getHelper('Date');
        return $helper->convertDateTimeClientToServer($datetime, $offset);
//        return date('Y-m-d H:i:s', strtotime($converted));
    }
}
