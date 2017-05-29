<?php
/**
 * @property CI_Loader $load
 * @property CI_Input $input
 * @property CI_DB_active_record $db
 */
class Model_Property extends Model
{
	public static $PROPERTIES_FIELD_QUERY_STATEMENTS;
	public static $FOLLOWUP_FIELD_QUERY_STATEMENTS;

	private $userRegistrationDate;
    
    var $lead_type = 'fsbo';
    
	function Model_Property()
	{
		self::$PROPERTIES_FIELD_QUERY_STATEMENTS = array
		(
			'GENERAL'	=>	array
		(
				'Status'					=>	'IFNULL(p.Status,"")',
				'DNC'						=>	'COALESCE(
													IF(TRIM(IFNULL(TaxRecordsPhone,""))="" OR TRIM(IFNULL(TaxRecordsPhone,""))=TRIM(IFNULL(a1.BrokerOfficePhone,"")) OR TRIM(IFNULL(TaxRecordsPhone,""))=TRIM(IFNULL(a2.BrokerOfficePhone,"")) OR TRIM(IFNULL(TaxRecordsPhone,""))=TRIM(IFNULL(a1.Phone,"")) OR TRIM(IFNULL(TaxRecordsPhone,""))=TRIM(IFNULL(a2.Phone,"")),NULL,IF(IFNULL(p.TaxRecordsDNC,0)=0,"n","y")),
													IF(TRIM(IFNULL(WhitePagesPhone,""))="" OR TRIM(IFNULL(WhitePagesPhone,""))=TRIM(IFNULL(a1.BrokerOfficePhone,"")) OR TRIM(IFNULL(WhitePagesPhone,""))=TRIM(IFNULL(a2.BrokerOfficePhone,"")) OR TRIM(IFNULL(WhitePagesPhone,""))=TRIM(IFNULL(a1.Phone,"")) OR TRIM(IFNULL(WhitePagesPhone,""))=TRIM(IFNULL(a2.Phone,"")),NULL,IF(IFNULL(p.WhitePagesDNC,0)=0,"n","y")),
													IF(TRIM(IFNULL(MLSPhone,""))="" OR TRIM(IFNULL(MLSPhone,""))=TRIM(IFNULL(a1.BrokerOfficePhone,"")) OR TRIM(IFNULL(MLSPhone,""))=TRIM(IFNULL(a2.BrokerOfficePhone,"")) OR TRIM(IFNULL(MLSPhone,""))=TRIM(IFNULL(a1.Phone,"")) OR TRIM(IFNULL(MLSPhone,""))=TRIM(IFNULL(a2.Phone,"")),NULL,IF(IFNULL(p.MLSDNC,0)=0,"n","y")),
													IF(TRIM(IFNULL(FSBOPhone,""))="" OR TRIM(IFNULL(FSBOPhone,""))=TRIM(IFNULL(a1.BrokerOfficePhone,"")) OR TRIM(IFNULL(FSBOPhone,""))=TRIM(IFNULL(a2.BrokerOfficePhone,"")) OR TRIM(IFNULL(FSBOPhone,""))=TRIM(IFNULL(a1.Phone,"")) OR TRIM(IFNULL(FSBOPhone,""))=TRIM(IFNULL(a2.Phone,"")),NULL,IF(IFNULL(p.FSBODNC,0)=0,"n","y")),
													"n"
												)',
				'TaxRecordsName'			=>	'COALESCE(
													IF(TRIM(IFNULL(TaxRecordsName,""))="",NULL,TaxRecordsName),
													IF(TRIM(IFNULL(WhitePagesPhone,""))="",NULL,WhitePagesName),
													IF(TRIM(IFNULL(MLSPhone,""))="",NULL,MLSName),
													IF(TRIM(IFNULL(FSBOName,""))="",NULL,FSBOName),
													IF(TRIM(IFNULL(OwnerName,""))="",NULL,OwnerName),
													""
												)',
				'Phone'						=>	'COALESCE(
													IF(TRIM(IFNULL(TaxRecordsPhone,""))="" OR TRIM(IFNULL(TaxRecordsPhone,""))=TRIM(IFNULL(a1.BrokerOfficePhone,"")) OR TRIM(IFNULL(TaxRecordsPhone,""))=TRIM(IFNULL(a2.BrokerOfficePhone,"")) OR TRIM(IFNULL(TaxRecordsPhone,""))=TRIM(IFNULL(a1.Phone,"")) OR TRIM(IFNULL(TaxRecordsPhone,""))=TRIM(IFNULL(a2.Phone,"")),NULL,TaxRecordsPhone),
													IF(TRIM(IFNULL(WhitePagesPhone,""))="" OR TRIM(IFNULL(WhitePagesPhone,""))=TRIM(IFNULL(a1.BrokerOfficePhone,"")) OR TRIM(IFNULL(WhitePagesPhone,""))=TRIM(IFNULL(a2.BrokerOfficePhone,"")) OR TRIM(IFNULL(WhitePagesPhone,""))=TRIM(IFNULL(a1.Phone,"")) OR TRIM(IFNULL(WhitePagesPhone,""))=TRIM(IFNULL(a2.Phone,"")),NULL,WhitePagesPhone),
													IF(TRIM(IFNULL(MLSPhone,""))="" OR TRIM(IFNULL(MLSPhone,""))=TRIM(IFNULL(a1.BrokerOfficePhone,"")) OR TRIM(IFNULL(MLSPhone,""))=TRIM(IFNULL(a2.BrokerOfficePhone,"")) OR TRIM(IFNULL(MLSPhone,""))=TRIM(IFNULL(a1.Phone,"")) OR TRIM(IFNULL(MLSPhone,""))=TRIM(IFNULL(a2.Phone,"")),NULL,MLSPhone),
													IF(TRIM(IFNULL(FSBOPhone,""))="" OR TRIM(IFNULL(FSBOPhone,""))=TRIM(IFNULL(a1.BrokerOfficePhone,"")) OR TRIM(IFNULL(FSBOPhone,""))=TRIM(IFNULL(a2.BrokerOfficePhone,"")) OR TRIM(IFNULL(FSBOPhone,""))=TRIM(IFNULL(a1.Phone,"")) OR TRIM(IFNULL(FSBOPhone,""))=TRIM(IFNULL(a2.Phone,"")),NULL,FSBOPhone),
													IF(TRIM(IFNULL(p.Phone,""))="" OR TRIM(IFNULL(p.Phone,""))=TRIM(IFNULL(a1.BrokerOfficePhone,"")) OR TRIM(IFNULL(p.Phone,""))=TRIM(IFNULL(a2.BrokerOfficePhone,"")) OR TRIM(IFNULL(p.Phone,""))=TRIM(IFNULL(a1.Phone,"")) OR TRIM(IFNULL(p.Phone,""))=TRIM(IFNULL(a2.Phone,"")),NULL,p.Phone),
													""
												)',
				'Email'						=>	'IFNULL(p.OwnerEmail,IFNULL(p.FSBOEmail,""))',
				'Address'					=>	'IFNULL(p.Address,"")',
				'City'						=>	'IFNULL(p.City,"")',
				'ZIP'						=>	'IFNULL(p.ZIP,"")',
				'Occupancy'					=>	'IFNULL(p.Occupancy,"")',
				'MLSNr'						=>	'IFNULL(p.MLSNr,"")',
				'OffMarketDate'				=>	'IFNULL(DATE_FORMAT(p.OffMarketDate,"'.MYSQL_DATE_FORMAT.'"),"")',
				'ListPrice'					=>	'CONCAT("$",FORMAT(p.ListPrice,0))',
				'Restrictions'				=>	'IFNULL((SELECT Display FROM display_text WHERE Name=p.Restrictions LIMIT 1),"")',
				'Source'					=>	'IFNULL(p.Source,"")',
				'YearBuilt'					=>	'IFNULL(p.YearBuilt,"")',
				'Style'						=>	'IFNULL((SELECT Display FROM display_text WHERE Name=p.Style LIMIT 1),"")',
				'NODTaxID'					=>	'p.NODTaxID',
				'UnitDesignator'			=>	'p.UnitDesignator',
				'UnitNumber'				=>	'p.UnitNumber',
				'NODRecordingDate'			=>	'DATE_FORMAT(p.NODRecordingDate,"'.MYSQL_DATE_FORMAT.'")',
				'NODFilingDate'				=>	'DATE_FORMAT(p.NODFilingDate,"'.MYSQL_DATE_FORMAT.'")',
				'NODDefaultAmount'			=>	'CONCAT("$",TRIM(BOTH "." FROM TRIM(BOTH "0" FROM FORMAT(NODDefaultAmount,3))))',
				'NODOriginalMortgageAmount'	=>	'CONCAT("$",TRIM(BOTH "." FROM TRIM(BOTH "0" FROM FORMAT(NODOriginalMortgageAmount,3))))',
				'StateID'					=>	'p.StateID',
				'TaxRecordsMailAddress'		=>	'p.TaxRecordsMailAddress',
				'NrOfBeds'					=>	'p.NrOfBeds',
				'NrOfBaths'					=>	'p.NrOfBaths',
				'BuildingSize'				=>	'p.BuildingSize',
				'LotSize'					=>	'p.LotSize',
				'FSBOName'					=>	'p.FSBOName'
				)
				);

				self::$FOLLOWUP_FIELD_QUERY_STATEMENTS = array
				(
			'FollowUpDate'		=>	'DATE_FORMAT(f.SetTo,"'.MYSQL_DATE_FORMAT.'")',
			'Type'				=>	'(SELECT Display FROM display_text WHERE CONCAT("calendar_",IF(p.LeadTypeID=6,p.FollowupLeadTypeName,lt.Name))=Name LIMIT 1)'
			);

			parent::Model();

			$this->userRegistrationDate = $this->session->userdata('s_registrationDate');
			$this->load->model('model_dnc');
	}
    
    function check_property_into_group($property_id, $userID, $propertyType)
    {
        $this->lead_type = $propertyType;
        
	    if($propertyType == 'followups')
            $lead_type_id = 6;
        elseif ($propertyType == 'fsbo')
            $lead_type_id = 2;
        elseif ($propertyType == 'expireds')
            $lead_type_id = 1;
        elseif ($propertyType == 'nod')
            $lead_type_id = 3;
        elseif ($propertyType == 'jljs')
            $lead_type_id = 4;
        else
            $lead_type_id = 6;
            
        $this->db->select('*');
        $this->db->from('properties p');

        if($propertyType == 'followups')
        {
            $this->db->join('follow_ups f_u', 'f_u.PropertyID = p.PropertyID');
        }
        
        $this->db->where('p.PropertyID', $property_id);
        $this->db->where('p.LeadTypeID', $lead_type_id);
        $this->db->where('p.is_tmp', 0);
        $this->db->where('p.UserID', $userID);
        
        $query = $this->db->get();
                
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE;
    }
    
    function check_dublicate_row($property_id, $user_id)
    {
        $this->db->select('*');
        $this->db->from('properties');
        $this->db->where('fsbo_parent_id', $property_id);
        $this->db->where('UserID', $user_id);
        $this->db->where('is_fsbo', 1);
        
        $query = $this->db->get();
                
        if  ($query->num_rows()>0)
			return $query->row();
        else
            return false;
    }
    
	function GetFirstPropertyID($propertyType,$userID,$selectedDate,$searchFilter,$adminMode,$followUpMode=false,$flexigridOptions,$params=array())
	{
        $this->lead_type = $propertyType;
        
	    if($propertyType == 'followups')
            $lead_type_id = 6;
        elseif ($propertyType == 'fsbo')
            $lead_type_id = 2;
        elseif ($propertyType == 'expireds')
            $lead_type_id = 1;
        elseif ($propertyType == 'nod')
            $lead_type_id = 3;
        elseif ($propertyType == 'jljs')
            $lead_type_id = 4;
        else
            $lead_type_id = 6;
            
        $this->db->select('DISTINCT(p.PropertyID), p.*, CONCAT(IF(`p`.`fname` is null, "", `p`.`fname`), " ", IF(`p`.`lname` is null, "", `p`.`lname`)) as Name', FALSE);
        
        if($propertyType == 'followups')
        {
            $this->db->select('f_u.SetTo');
        }
        $this->db->from('properties p');
        
        if($propertyType == 'followups')
        {
            $this->db->join('follow_ups f_u', 'f_u.PropertyID = p.PropertyID');
        }
        
        if(isset($params['folder']))
            $this->db->join('folder_properties f_p', 'p.PropertyID = f_p.property_id and f_p.folder_id = ' . $params['folder']);
        else 
            $this->db->join('folder_properties f_p', 'p.PropertyID = f_p.property_id', 'LEFT');

        if(isset($params['category']))
            $this->db->where('p.category', $params['category']);
            
        if(isset($params['relation']))
            $this->db->where('p.relation', $params['relation']);
        
       // $this->db->where('p.LeadTypeID', $lead_type_id);
 
        
        if($this->lead_type == 'fsbo' || $this->lead_type == 'expireds' || $this->lead_type == 'nod' || $this->lead_type == 'jljs')
        {
            if($selectedDate && $selectedDate != '')
            {
                $day_before = date(PHP_ISO_DATE_HOUR_MINUTE_SECOND_FORMAT, strtotime($selectedDate));
                $day_after = date(PHP_ISO_DATE_HOUR_MINUTE_SECOND_FORMAT, strtotime('+1 DAY', strtotime($selectedDate)));
                
                $this->db->where('p.post_date >=', $day_before);
                $this->db->where('p.post_date <', $day_after);
            }
            else
            {
                $day_before = date(PHP_ISO_DATE_HOUR_MINUTE_SECOND_FORMAT, strtotime(date(PHP_ISO_DATE_FORMAT, time())));
                $day_after = date(PHP_ISO_DATE_HOUR_MINUTE_SECOND_FORMAT, strtotime('+1 DAY', strtotime(date(PHP_ISO_DATE_FORMAT, time()))));
                $this->db->where('p.post_date >= ', $day_before);
                $this->db->where('p.post_date < ', $day_after);
            }
        }        
        
        
        if ($propertyType != 'fsbo')
            $this->db->where('p.UserID', $userID);

        $this->db->select('fc.name as city_name, fs.state_id as state_abv');
        
        $this->db->join('fsbo_cities fc', 'fc.id = p.fsbo_city_id', 'LEFT');
        $this->db->join('fsbo_states fs', 'fs.id = fc.state_id', 'LEFT');
        
        $this->db->where('fsbo_parent_id', 0);
        //$city_arr = $this->get_fsbo_city_for_user($userID);
        if ($propertyType == 'fsbo')
        {                        
            $this->db->where('(p.fsbo_city_id IN (SELECT city_id FROM fsbo_user2city WHERE user_id = ' . $userID . '))');
            $this->db->where('(p.PropertyID NOT IN (SELECT property_id FROM fsbo_deleted_property WHERE user_id = ' . $userID . '))');
        }            
            
        $this->db->where('p.is_tmp', 0);
        $this->db->group_by('p.PropertyID');
         
        if ($propertyType == 'fsbo')
            $this->db->where('p.is_fsbo', 1);
        elseif ($propertyType == 'expireds')
            $this->db->where('p.is_expired', 1);
        elseif ($propertyType == 'nod')
            $this->db->where('p.is_nod', 1);
        elseif ($propertyType == 'jljs')
        {
            if($this->session->userdata('s_current_jljs_tab'))
            {
                $this->db->where('p.is_' . $this->session->userdata('s_current_jljs_tab'), 1); 
            }
            else
                $this->db->where('p.is_jl', 1); 
        }
         elseif ($propertyType == 'followups')
            $this->db->where('(p.is_fsbo = 1 OR p.is_expired = 1 OR p.is_nod = 1 OR p.is_jl = 1 OR p.is_js = 1 OR (p.is_contact = 1 OR p.fsbo_move_to_contact = 1))');
        else
            $this->db->where('((p.is_contact = 1 OR p.fsbo_move_to_contact = 1))');
        
        $this->my_build_query($params['sortname'], $params['sortorder']);
        
        $query = $this->db->get();
                
        if  ($query->num_rows()>0)
			return $query->first_row()->PropertyID;
		else
			return null;
       
       
       /*
		$sql = 'SELECT	p.PropertyID
				FROM	properties p
						INNER JOIN
						lead_types lt ON lt.LeadTypeID=p.LeadTypeID
						LEFT JOIN agents a1 ON a1.AgentID=p.FirstAgentID
						LEFT JOIN agents a2 ON a2.AgentID=p.SecondAgentID
						'.($followUpMode?' INNER JOIN follow_ups f ON f.PropertyID=p.PropertyID AND f.UserID=? ':' ').'
				WHERE	'.($this->GetFollowUpFilter($followUpMode)).
		($this->GetAdminModeFilter($adminMode)).'
						AND DATE(p.Added)>=DATE_SUB( STR_TO_DATE("'.$this->userRegistrationDate.'","'.MYSQL_ISO_DATE_FORMAT.'"),INTERVAL '.NR_OF_PREVIOUS_ACCESS_DAYS.' DAY) AND [SEARCH_FILTER]
						[FLEXIGRID_OPTIONS]	LIMIT 1';

		$parameters = array();

		if ($adminMode)
		$parameters = array($propertyType);
		elseif ($followUpMode)
		$parameters = array($userID,$userID,$userID,$userID);
		else
		$parameters = array($propertyType,$userID,$userID,$userID);


		$query = $this->buildSearchCriteria($sql,$parameters,$selectedDate,$searchFilter,$followUpMode,$flexigridOptions,$propertyType);

		if  ($query->num_rows()>0)
			return $query->first_row()->PropertyID;
		else
			return null;*/
	}
    
    function GetPreviousPropertyID($propertyID,$userID,$params=array())
    {
        $this->db->select('p.PropertyID');
        $this->db->from('properties p');
        $this->db->join('follow_ups f_u', 'f_u.PropertyID = p.PropertyID');
        if(isset($params['folder']))
            $this->db->join('folder_properties f_p', 'p.PropertyID = f_p.property_id and f_p.folder_id = ' . $params['folder']);

        if(isset($params['category']))
            $this->db->where('p.category', $params['category']);
            
        if(isset($params['relation']))
            $this->db->where('p.relation', $params['relation']);
        
        $this->db->where('p.UserID', $userID);
        $this->db->where('p.PropertyID <', $propertyID);
        $this->db->where('p.is_tmp', 0);
        
        if($this->lead_type == 'fsbo' || $this->lead_type == 'expireds' || $this->lead_type == 'nod' || $this->lead_type == 'jljs')
        {
            if(isset($params['selected_date']))
            {
                $day_before = date(PHP_ISO_DATE_HOUR_MINUTE_SECOND_FORMAT, strtotime($params['selected_date']));
                $day_after = date(PHP_ISO_DATE_HOUR_MINUTE_SECOND_FORMAT, strtotime('+1 DAY', strtotime($params['selected_date'])));
                
                $this->db->where('p.post_date >=', $day_before);
                $this->db->where('p.post_date <', $day_after);
            }
            else
            {
                $day_before = date(PHP_ISO_DATE_HOUR_MINUTE_SECOND_FORMAT, strtotime(date(PHP_ISO_DATE_FORMAT, time())));
                $day_after = date(PHP_ISO_DATE_HOUR_MINUTE_SECOND_FORMAT, strtotime('+1 DAY', strtotime(date(PHP_ISO_DATE_FORMAT, time()))));
                $this->db->where('p.post_date >= ', $day_before);
                $this->db->where('p.post_date < ', $day_after);
            }
        }    
        
        $this->my_build_query($params['sortname'], $params['sortorder'], false);
        $query = $this->db->get();
                
        if ($query->num_rows() > 0) 
            return $query->last_row();
        else
            return FALSE;
    }
    
    function GetNextPropertyID($propertyID,$userID,$params=array())
    {
        $this->db->select('p.PropertyID');
        $this->db->from('properties p');
        $this->db->join('follow_ups f_u', 'f_u.PropertyID = p.PropertyID');
        
        if(isset($params['folder']))
            $this->db->join('folder_properties f_p', 'p.PropertyID = f_p.property_id and f_p.folder_id = ' . $params['folder']);

        if(isset($params['category']))
            $this->db->where('p.category', $params['category']);
            
        if(isset($params['relation']))
            $this->db->where('p.relation', $params['relation']);
        
        $this->db->where('p.UserID', $userID);
        $this->db->where('p.PropertyID >', $propertyID);
        $this->db->where('p.is_tmp', 0);
        
        if($this->lead_type == 'fsbo' || $this->lead_type == 'expireds' || $this->lead_type == 'nod' || $this->lead_type == 'jljs')
        {
            if(isset($params['selected_date']))
            {
                $day_before = date(PHP_ISO_DATE_HOUR_MINUTE_SECOND_FORMAT, strtotime($params['selected_date']));
                $day_after = date(PHP_ISO_DATE_HOUR_MINUTE_SECOND_FORMAT, strtotime('+1 DAY', strtotime($params['selected_date'])));
                
                $this->db->where('p.post_date >=', $day_before);
                $this->db->where('p.post_date <', $day_after);
            }
            else
            {
                $day_before = date(PHP_ISO_DATE_HOUR_MINUTE_SECOND_FORMAT, strtotime(date(PHP_ISO_DATE_FORMAT, time())));
                $day_after = date(PHP_ISO_DATE_HOUR_MINUTE_SECOND_FORMAT, strtotime('+1 DAY', strtotime(date(PHP_ISO_DATE_FORMAT, time()))));
                $this->db->where('p.post_date >= ', $day_before);
                $this->db->where('p.post_date < ', $day_after);
            }
        }    
        
        $this->my_build_query($params['sortname'], $params['sortorder'], false);
        $query = $this->db->get();
                
        if ($query->num_rows() > 0) 
            return $query->first_row();
        else
            return FALSE;
    }
	/*function GetPreviousPropertyID($propertyType,$propertyID,$userID,$selectedDate,$searchFilter,$adminMode,$followUpMode=false,$flexigridOptions)
	{
		$sql = 'SELECT	MAX(p.PropertyID) as PropertyID
				FROM	properties p
						INNER JOIN lead_types lt ON p.LeadTypeID=lt.LeadTypeID
						LEFT JOIN agents a1 ON a1.AgentID=p.FirstAgentID
						LEFT JOIN agents a2 ON a2.AgentID=p.SecondAgentID
						'.($followUpMode?' INNER JOIN follow_ups f ON f.PropertyID=p.PropertyID AND f.UserID=? ':' ').'
				WHERE	'.($followUpMode==true?' ':' lt.Name=? AND ').' p.LeadTypeID=lt.LeadTypeID AND p.PropertyID<? '.
		($this->GetFollowUpFilter($followUpMode,false,true)).
		($this->GetAdminModeFilterForPropertyOrder($adminMode)).'
						AND DATE(p.Added)>=DATE_SUB( STR_TO_DATE("'.$this->userRegistrationDate.'","'.MYSQL_ISO_DATE_FORMAT.'"),INTERVAL '.NR_OF_PREVIOUS_ACCESS_DAYS.' DAY) AND [SEARCH_FILTER]
						[FLEXIGRID_OPTIONS]';

		$parameters = array();

		if ($adminMode)
		$parameters = array($propertyType,$propertyID);
		elseif ($followUpMode)
		$parameters = array($userID,$propertyID,$userID,$userID,$userID,$userID);
		else
		$parameters = array($propertyType,$propertyID,$userID,$userID,$userID);

		$query = $this->buildSearchCriteria($sql,$parameters,$selectedDate,$searchFilter,$followUpMode,$flexigridOptions,$propertyType);

		if  ($query->num_rows()>0)
		return $query->first_row()->PropertyID;
		else
		return null;
	}*/

	/*function GetNextPropertyID($propertyType,$propertyID,$userID,$selectedDate,$searchFilter,$adminMode,$followUpMode=false,$flexigridOptions)
	{
		$sql = 'SELECT	MIN(p.PropertyID1) as PropertyID
				FROM	properties p
						INNER JOIN lead_types lt ON p.LeadTypeID=lt.LeadTypeID
						LEFT JOIN agents a1 ON a1.AgentID=p.FirstAgentID
						LEFT JOIN agents a2 ON a2.AgentID=p.SecondAgentID
						'.($followUpMode?' INNER JOIN follow_ups f ON f.PropertyID=p.PropertyID AND f.UserID=? ':' ').'
				WHERE	'.($followUpMode==true?' ':' lt.Name=? AND ').' p.LeadTypeID=lt.LeadTypeID AND p.PropertyID>? '.
		($this->GetFollowUpFilter($followUpMode,false,true)).
		($this->GetAdminModeFilterForPropertyOrder($adminMode)).'
						AND DATE(p.Added)>=DATE_SUB( STR_TO_DATE("'.$this->userRegistrationDate.'","'.MYSQL_ISO_DATE_FORMAT.'"),INTERVAL '.NR_OF_PREVIOUS_ACCESS_DAYS.' DAY) AND [SEARCH_FILTER]
						[FLEXIGRID_OPTIONS]';

		$parameters = array();

		if ($adminMode)
		$parameters = array($propertyType,$propertyID);
		elseif ($followUpMode)
		$parameters = array($userID,$propertyID,$userID,$userID,$userID,$userID);
		else
		$parameters = array($propertyType,$propertyID,$userID,$userID,$userID);

		$query = $this->buildSearchCriteria($sql,$parameters,$selectedDate,$searchFilter,$followUpMode,$flexigridOptions,$propertyType);

		if  ($query->num_rows()>0)
		return $query->first_row()->PropertyID;
		else
		return null;
	}*/

	function GetOwnerData($propertyType,$propertyID,$followUpMode)
	{
		$this->db->flush_cache();

		$this->db->select
		('
			PropertyID, Address, City, StateID, ZIP, DNC, (SELECT Display FROM display_text WHERE Name=Occupancy) as Occupancy,
			MLSName, MLSPhone,MLSDNC, OccupantName,
			TaxRecordsName, TaxRecordsName2, TaxRecordsDNC, TaxRecordsPhone, TaxRecordsMailAddress, TaxRecordsMailCity, TaxRecordsMailZIP, TaxRecordsMailStateID,
			WhitePagesName, WhitePagesPhone,WhitePagesDNC,
			FSBOPhone, FSBODNC, FSBOName, FSBOEmail,
			(SELECT Display FROM display_text WHERE Name=Status) as Status,
			DATE_FORMAT(OffMarketDate,"'.MYSQL_DATE_FORMAT.'") as OffMarketDate,
			CONCAT("$",FORMAT(ListPrice,0)) as ListPrice,
			CONCAT("$",FORMAT(SoldPrice,0)) as SoldPrice,

			a.Name as AgentName,
			a.Broker as AgentBroker,
			a.Phone as AgentPhone,
			a.BrokerOfficePhone as AgentOfficePhone,
			a.AdditionalPhone as AgentAdditionalPhone,
			a.Fax as AgentFax,

			a2.Name as SecondAgentName,
			a2.Broker as SecondAgentBroker,
			a2.Phone as SecondAgentPhone,
			a2.BrokerOfficePhone as SecondAgentOfficePhone,
			a2.AdditionalPhone as SecondAgentAdditionalPhone,
			a2.Fax as SecondAgentFax,

			NODTaxID,
			p.Phone,
			DATE_FORMAT(NODRecordingDate,"'.MYSQL_DATE_FORMAT.'") as NODRecordingDate,
			DATE_FORMAT(NODFilingDate,"'.MYSQL_DATE_FORMAT.'") as NODFilingDate,
			CONCAT("$",TRIM(BOTH "." FROM TRIM(BOTH "0" FROM FORMAT(NODDefaultAmount,3)))) as NODDefaultAmount,
			CONCAT("$",TRIM(BOTH "." FROM TRIM(BOTH "0" FROM FORMAT(NODOriginalMortgageAmount,3)))) as NODOriginalMortgageAmount,
			p.LeadTypeID, p.OwnerName, p.OwnerEmail
		',false);
		$this->db->from('properties p');
		$this->db->join('agents a','a.AgentID=p.FirstAgentID','left');
		$this->db->join('agents a2','a2.AgentID=p.SecondAgentID','left');
		$this->db->where('PropertyID',$propertyID);
		if (!$followUpMode)
		{
			$this->db->join('lead_types lt','lt.LeadTypeID=p.LeadTypeID');
			$this->db->where('lt.Name',$propertyType);
		}

		$query = $this->db->get();

		if  ($query->num_rows()>0)
		return $query->first_row('array');
		else
		return null;
	}

	function GetPropertyInfo($propertyType,$propertyID,$indexBased=true,$followUpMode=false)
	{
		$this->db->flush_cache();

		if ($propertyType=="fsbo")
		{
			$this->db->select('FSBODescription, FSBOName, p.City,p.Address,p.ZIP,p.SubDivision,p.StateID');
            $this->db->where('is_fsbo', 1);
		}
		else
		{
			$this->db->select
			(
				'IFNULL(Address,"") as Address,
                CONCAT(IFNULL(City,""),", ",IFNULL(StateID,"")," ",IFNULL(ZIP,"")) as Location,
				IFNULL(TaxRecordsMailAddress,Address) as TaxRecordsMailAddress,
				IFNULL(CONCAT(TaxRecordsMailCity,", ",TaxRecordsMailStateID," ",TaxRecordsMailZIP),CONCAT(IFNULL(City,""),", ",IFNULL(StateID,"")," ",IFNULL(ZIP,""))) as TaxRecordsMailLocation,
				Neighborhood,
                MLSNr,
                (SELECT Display FROM display_text WHERE Name=Style LIMIT 1) AS PropertyType,
                (CASE Style WHEN "ATT" THEN "N/A" WHEN "TOWNHM" THEN "N/A" WHEN "TWNHM" THEN "N/A" ELSE CONCAT(LotSize," ft") END) as LotSize,
                CONCAT(BuildingSize," ft") as BuildingSize,
                CONCAT(NrOfBeds,"/",NrOfBaths) as NrOfBedsPerBaths,
                Community,
                CONCAT("$",FORMAT(ListPrice,0)) as ListPrice,
                CONCAT("$",FORMAT(OriginalPrice,0)) as OriginalPrice,
				IFNULL(YearBuilt,"") as YearBuilt,
                DATE_FORMAT(ListDate,"'.MYSQL_DATE_FORMAT.'") as ListDate,
                DATE_FORMAT(OffMarketDate,"'.MYSQL_DATE_FORMAT.'") as OffMarketDate,
				CONCAT(ActiveMarketTime," day(s)") as ActiveMarketTime,
				DATE_FORMAT(NODFilingDate,"'.MYSQL_DATE_FORMAT.'") as FilingDate,
				DATE_FORMAT(NODRecordingDate,"'.MYSQL_DATE_FORMAT.'") as NODRecordingDate,
				CONCAT("$",TRIM(BOTH "." FROM TRIM(BOTH "0" FROM FORMAT(NODDefaultAmount,3)))) as NODDefaultAmount,
				CONCAT("$",TRIM(BOTH "." FROM TRIM(BOTH "0" FROM FORMAT(NODOriginalMortgageAmount,3)))) as NODOriginalMortgageAmount,

				DATE_FORMAT(SoldDate,"'.MYSQL_DATE_FORMAT.'") as SoldDate,
				(SELECT IFNULL(Display,"") FROM display_text WHERE Name=IF(ListStatus="RELISTED",ListStatus,Status) LIMIT 1) AS Status,
				(SELECT IFNULL(Display,"") FROM display_text WHERE Name=PreviousStatus LIMIT 1) AS PreviousStatus,
				(SELECT IFNULL(Display,"") FROM display_text WHERE Name=Restrictions LIMIT 1) as Restrictions,

				a.Name as AgentName,
				a.Broker as AgentBroker,
				a.Phone as AgentPhone,
				a.BrokerOfficePhone as AgentOfficePhone,
				a.AdditionalPhone as AgentAdditionalPhone,
				a.Fax as AgentFax,

				a2.Name as SecondAgentName,
				a2.Broker as SecondAgentBroker,
				a2.Phone as SecondAgentPhone,
				a2.BrokerOfficePhone as SecondAgentOfficePhone,
				a2.AdditionalPhone as SecondAgentAdditionalPhone,
				a2.Fax as SecondAgentFax,
        		NODTaxID,
				StateID,
				ZIP,
				City,
				PropertyType as PropertyTypeID,

				IFNULL(MLSName,"") as MLSName,
				MLSPhone,
				ParcelNumber,
				(SELECT IFNULL(Display,"") FROM display_text WHERE Name=Style LIMIT 1) as Style

			',false);
		}
		$this->db->from('properties p');

		if (!$followUpMode)
		$this->db->join('lead_types lt','lt.LeadTypeID=p.LeadTypeID');
		$this->db->join('agents a','a.AgentID=p.FirstAgentID','left');
		$this->db->join('agents a2','a2.AgentID=p.SecondAgentID','left');
		$this->db->where('PropertyID',$propertyID);

		$query = $this->db->get();
		$result = $query->first_row('array');

		if ($propertyType=="fsbo" || $indexBased==false)
		return $result;
		else
		return array_values($result);
	}

	function GetAllPropertyInfo($propertyType,$propertyID,$indexBased=true)
	{
		$this->db->select
		('
			Status,
			PreviousStatus,
			OwnerName,
			Phone,
			Address,
			City,
			ZIP,
			Neighborhood,
			Occupancy,
			OccupantName,
			OccupantPhone,
			OwnerPhone,
			MLSNr,
			DATE_FORMAT(OffMarketDate,"'.MYSQL_DATE_FORMAT.'") as OffMarketDate,
			ActiveMarketTime,
			IFNULL(FORMAT(ListPrice,0),"") as ListPrice,
			PropertyType,
			LotSize,
			BuildingSize,
			NrOfBeds,
			NrOfBaths,
			SubDivision,
			OriginalPrice,
			DATE_FORMAT(ListDate,"'.MYSQL_DATE_FORMAT.'") as ListDate,
			ListStatus,
			Added,
			DNC,
			StateID,
			MLSName,
			MLSPhone,
			MLSDNC,
			TaxRecordsName,
      		TaxRecordsName2,
			TaxRecordsPhone,
			TaxRecordsDNC,
			TaxRecordsMailAddress,
			TaxRecordsMailCity,
			TaxRecordsMailStateID,
			TaxRecordsMailZIP,
			TaxRecordsMailUnitDesignator,
			TaxRecordsMailUnitNumber,
			WhitePagesName,
			WhitePagesPhone,
			p.LeadTypeID,
			TaxIDNr,
			ParcelNumber,
			Community,
			DATE_FORMAT(ExpirationDate,"'.MYSQL_DATE_FORMAT.'") as ExpirationDate,
			Instructions,
			SubjectToCourt,
			PreviousListPrice,
			Style,
			OwnerEmail,
			County,
			DATE_FORMAT(NODRecordingDate,"'.MYSQL_DATE_FORMAT.'") as NODRecordingDate,
			DATE_FORMAT(NODFilingDate,"'.MYSQL_DATE_FORMAT.'") as NODFilingDate,
			NODTaxID,
			CONCAT("$",TRIM(BOTH "." FROM TRIM(BOTH "0" FROM FORMAT(NODDefaultAmount,3)))) as NODDefaultAmount,
			CONCAT("$",TRIM(BOTH "." FROM TRIM(BOTH "0" FROM FORMAT(NODOriginalMortgageAmount,3)))) as NODOriginalMortgageAmount,
			FSBOName,
			FSBOPhone,
			FSBOEmail,
			FSBODNC,
			FSBODescription,
			Restrictions,
			YearBuilt,
			Source,
			MLSID,
			UnitDesignator,
			UnitNumber
		',
		false);
		$this->db->from('properties p');
		$this->db->join('lead_types lt','p.LeadTypeID=lt.LeadTypeID');
		$this->db->where('PropertyID',$propertyID);
		$this->db->where('lt.Name',$propertyType);

		$query = $this->db->get();
		$result = $query->first_row('array');

		if ($indexBased)
		return array_values($result);
		else
		return $result;

	}

	function GetPaginatedPropertiesForGrid($propertyType,$nrOfRows=10,$pageNr=1,$userID,$selectedDate,$searchFilter,$adminMode,$followUpMode=false,$flexigridOptions,$columnOrder,$saveParam = 0)
	{
		$sql = 'SELECT ';
		if (isset(self::$PROPERTIES_FIELD_QUERY_STATEMENTS[$propertyType]))
		$fields = self::$PROPERTIES_FIELD_QUERY_STATEMENTS[$propertyType];
		else
		$fields = self::$PROPERTIES_FIELD_QUERY_STATEMENTS['GENERAL'];

		if ($followUpMode)
		{
			foreach (self::$FOLLOWUP_FIELD_QUERY_STATEMENTS as $key=>$value)
			$sql.= $value.' as '.$key.',';
		}

		for ($i=0;$i<count($columnOrder);$i++)
		if (array_key_exists($columnOrder[$i],$fields))
		$sql.= $fields[$columnOrder[$i]].' as '.$columnOrder[$i].',';

		$sql.= '			p.PropertyID,
							IF (EXISTS (SELECT	LeadStatusID
										FROM	lead_statuses ls
										WHERE	ls.UserID=? AND ls.PropertyID=p.PropertyID
										LIMIT	1),"1","0") as ActionTaken,
							1 as ValidAddress,
							UNIX_TIMESTAMP(DATE('.($followUpMode?'f.SetTo':'p.Added').')) as FollowUpAddedTime,
							UNIX_TIMESTAMP(DATE("'.date(PHP_ISO_DATE_FORMAT).'")) as CurrentDayTime,
							'.($followUpMode?'f.SetTo':'p.Added').' as FollowUpAdded,
							'.($followUpMode?'f.FollowUpID':'p.PropertyID').' as FollowUpID
				FROM		properties p
							LEFT JOIN agents a1 ON a1.AgentID=p.FirstAgentID
							LEFT JOIN agents a2 ON a2.AgentID=p.SecondAgentID
							INNER JOIN lead_types lt ON lt.LeadTypeID=p.LeadTypeID
							'.($followUpMode?' INNER JOIN follow_ups f ON f.PropertyID=p.PropertyID AND f.UserID=? ':' ').'
				WHERE		'.($this->GetFollowUpFilter($followUpMode)).
		($this->GetAdminModeFilter($adminMode)).'
							AND DATE(p.Added)>=DATE_SUB( STR_TO_DATE("'.$this->userRegistrationDate.'","'.MYSQL_ISO_DATE_FORMAT.'"),INTERVAL '.NR_OF_PREVIOUS_ACCESS_DAYS.' DAY) AND [SEARCH_FILTER]
							[FLEXIGRID_OPTIONS]'.
		$this->getFlexiGridLimit($searchFilter, $saveParam = 0);


		$parameters = array();
		if ($adminMode)
		$parameters = array($userID,$propertyType);
		elseif ($followUpMode)
		$parameters = array($userID,$userID,$userID,$userID,$userID,$userID);
		else
		$parameters = array($userID,$propertyType,$userID,$userID,$userID);

		$query = $this->buildSearchCriteria($sql,$parameters,$selectedDate,$searchFilter,$followUpMode,$flexigridOptions,$propertyType);

		return $query->result_array();
	}
    
    function get_count_user_properties($user_id)
    {
        $this->db->select('COUNT(*) as NrOfProperties');
		$this->db->from('properties p');
        $this->db->where('p.UserID', $user_id);
        $this->db->where('p.is_tmp', 0);
        $this->db->where('((p.is_fsbo = 0 OR p.fsbo_move_to_contact = 1))');
        
        $query = $this->db->get();

		if ($query->num_rows()>0)
            return $query->first_row()->NrOfProperties;
		else
            return 0;
        
    }
    
	function GetNrOfProperties($propertyType,$userID)
	{
		$this->db->select('COUNT(*) as NrOfProperties');
		$this->db->from('properties p');
		$this->db->join('lead_types lt','lt.LeadTypeID=p.LeadTypeID');
		$this->db->where('lt.Name',$propertyType);
		$this->db->where('(UserID='.$userID.' OR UserID IS NULL OR UserID=0)');
		$this->db->where('AND DATE(p.Added)>=DATE_SUB( STR_TO_DATE("'.$this->userRegistrationDate.'","'.MYSQL_ISO_DATE_FORMAT.'"),INTERVAL '.NR_OF_PREVIOUS_ACCESS_DAYS.' DAY)');

		$query = $this->db->get();

		if  ($query->num_rows()>0)
		return $query->first_row()->NrOfProperties;
		else
		return 0;
	}

	function GetPaginatedPropertiesForNeighbors($propertyID,$limit,$start,$onlyCount=false,$searchParameters,$orderBy=null,$orderDirection=null)
	{
		if ($onlyCount)
		$sql = 'SELECT COUNT(*) as count';
		else
		$sql = 'SELECT	p.PropertyID as PropertyID,
							IFNULL(TaxRecordsName,"") as Name,
							IFNULL(IF(MLSPhone=WhitePagesPhone AND MLSPhone=TaxRecordsPhone,MLSPhone,MLSPhone),"") as RealPhone,
							IFNULL(Address,"") as Address,
							IFNULL(ZIP,"") as ZIP ';

		$sql.='	FROM	properties p
				WHERE	DATE(p.Added)>=DATE_SUB( STR_TO_DATE("'.$this->userRegistrationDate.'","'.MYSQL_ISO_DATE_FORMAT.'"),INTERVAL '.NR_OF_PREVIOUS_ACCESS_DAYS.' DAY) AND
						NOT EXISTS (SELECT	FirstNeighborID
									FROM	neighbors n
									WHERE	(n.FirstNeighborID=? AND n.SecondNeighborID=p.PropertyID) OR (n.SecondNeighborID=? AND n.FirstNeighborID=p.PropertyID)) ';

		foreach ($searchParameters as $key=>$value)
		$sql.= ' AND IFNULL('.$key.',"") LIKE "%'.$value.'%" ';

		if (!$onlyCount && $orderBy!=null)
		{
			$sql.= ' ORDER BY '.$orderBy.' '.$orderDirection;
		}

		if (!$onlyCount)
		$sql.='	LIMIT ?,? ';

		if ($onlyCount)
		$parameters = array($propertyID,$propertyID);
		else
		$parameters = array($propertyID,$propertyID,$start,$limit);

		$query = $this->db->query($sql,$parameters);

		return $query->result_array();
	}

	function GetPaginatedNeighbors($propertyType,$propertyID,$nrOfRows=1,$pageNr=1,$userID,$adminMode=false)
	{
		$sql =
		'
			SELECT	p.PropertyID as NeighborID,
					IFNULL(WhitePagesName,"") as Name,
					IFNULL(WhitePagesPhone,"") as RealPhone,
					IFNULL(Address,"") as Address,
					IFNULL(ZIP,"") as ZIP
			FROM	properties p
					INNER JOIN
					lead_types lt ON lt.LeadTypeID=p.LeadTypeID
					INNER JOIN
					neighbors n ON (n.FirstNeighborID=p.PropertyID AND n.SecondNeighborID=?) OR (n.SecondNeighborID=p.PropertyID AND n.FirstNeighborID=?)'.
		($adminMode==true
		?' '
		:'
						WHERE	DATE(p.Added)>=DATE_SUB( STR_TO_DATE("'.$this->userRegistrationDate.'","'.MYSQL_ISO_DATE_FORMAT.'"),INTERVAL '.NR_OF_PREVIOUS_ACCESS_DAYS.' DAY) AND
								(p.UserID=? OR p.UserID IS NULL OR p.UserID=0)
								AND EXISTS (SELECT	mls_id
											FROM	user_mls um
											WHERE	um.user_id=? AND um.mls_id=p.MLSID)
						ORDER BY NeighborID
						LIMIT	?,?'
						);


						$parameters = array();
						if ($adminMode)
						$parameters = array($propertyID,$propertyID);
						else
						{
							$skip = ($nrOfRows*($pageNr-1));
							if ($skip<0)
							$skip = 0;
							$parameters = array($propertyID,$propertyID,$userID,$userID,$skip,$nrOfRows);
						}

						$query = $this->db->query($sql,$parameters);

						if ($adminMode)
						return $query->result_array();
						else
						return $query->first_row('array');
	}

	function GetPreviousNeighborID($propertyType,$propertyID,$neighborID,$userID)
	{
		$sql = 'SELECT	PropertyID
				FROM	properties p
						INNER JOIN
						lead_types lt ON lt.LeadTypeID=p.LeadTypeID
				WHERE	EXISTS (SELECT	mls_id
								FROM	user_mls um
								WHERE	um.user_id='.$userID.' AND um.mls_id=p.MLSID)
						AND DATE(p.Added)>=DATE_SUB( STR_TO_DATE("'.$this->userRegistrationDate.'","'.MYSQL_ISO_DATE_FORMAT.'"),INTERVAL '.NR_OF_PREVIOUS_ACCESS_DAYS.' DAY)
						AND (UserID='.$userID.' OR UserID IS NULL OR UserID=0)
						AND p.PropertyID=GREATEST(IFNULL((SELECT	MAX(n.FirstNeighborID)
														  FROM		neighbors n
														  WHERE		n.FirstNeighborID<'.$neighborID.' AND n.SecondNeighborID='.$propertyID.'),0),
												  IFNULL((SELECT	MAX(n.SecondNeighborID)
														  FROM		neighbors n
														  WHERE		n.SecondNeighborID<'.$neighborID.' AND n.FirstNeighborID='.$propertyID.'),0))';


		$query = $this->db->query($sql);

		if  ($query->num_rows()>0)
		{
			$neighborID = (int)$query->first_row()->PropertyID;
			if ($neighborID!=0)
			return $neighborID;
		}
	}

	function GetNextNeighborID($propertyType,$propertyID,$neighborID,$userID)
	{
		$sql = 'SELECT	PropertyID
				FROM	properties p
						INNER JOIN
						lead_types lt ON lt.LeadTypeID=p.LeadTypeID
				WHERE	DATE(p.Added)>=DATE_SUB( STR_TO_DATE("'.$this->userRegistrationDate.'","'.MYSQL_ISO_DATE_FORMAT.'"),INTERVAL '.NR_OF_PREVIOUS_ACCESS_DAYS.' DAY) AND
						(UserID='.$userID.' OR UserID IS NULL OR UserID=0)
						AND EXISTS (SELECT	mls_id
									FROM	user_mls um
									WHERE	um.user_id='.$userID.' AND um.mls_id=p.MLSID)
						AND p.PropertyID=GREATEST(IFNULL((SELECT	MIN(n.FirstNeighborID)
														  FROM		neighbors n
														  WHERE		n.FirstNeighborID>'.$neighborID.' AND n.SecondNeighborID='.$propertyID.'),0),
												  IFNULL((SELECT	MIN(n.SecondNeighborID)
														  FROM		neighbors n
														  WHERE		n.SecondNeighborID>'.$neighborID.' AND n.FirstNeighborID='.$propertyID.'),0))';


		$query = $this->db->query($sql);

		if  ($query->num_rows()>0)
		{
			$neighborID = (int)$query->first_row()->PropertyID;
			if ($neighborID!=0)
			return $neighborID;
		}
	}

	function GetNrOfNeighbors($propertyType,$propertyID,$userID)
	{
		$sql = 'SELECT	COUNT(*) as NrOfNeighbors
				FROM	properties p
						INNER JOIN
						lead_types lt ON lt.LeadTypeID=p.LeadTypeID
						INNER JOIN
						neighbors n ON n.FirstNeighborID=p.PropertyID AND n.SecondNeighborID='.$propertyID.' OR n.SecondNeighborID=p.PropertyID AND n.FirstNeighborID='.$propertyID.'
				WHERE	(p.UserID='.$userID.' OR p.UserID IS NULL OR p.UserID=0)
						AND DATE(p.Added)>=DATE_SUB( STR_TO_DATE("'.$this->userRegistrationDate.'","'.MYSQL_ISO_DATE_FORMAT.'"),INTERVAL '.NR_OF_PREVIOUS_ACCESS_DAYS.' DAY)
						AND EXISTS (SELECT	mls_id
									FROM	user_mls um
									WHERE	um.user_id='.$userID.' AND um.mls_id=p.MLSID)';

		$query = $this->db->query($sql);

		if  ($query->num_rows()>0)
		return $query->first_row()->NrOfNeighbors;
		else
		return 0;
	}

	function GetNrOfPropertiesForGrid($propertyType,$userID,$selectedDate,$searchFilter,$adminMode,$followUpMode=false,$flexigridOptions)
	{
		$sql = 'SELECT	COUNT(*) as NrOfProperties
				FROM	properties p
						INNER JOIN
						lead_types lt ON lt.LeadTypeID=p.LeadTypeID
						LEFT JOIN agents a1 ON a1.AgentID=p.FirstAgentID
						LEFT JOIN agents a2 ON a2.AgentID=p.SecondAgentID
						'.($followUpMode?' INNER JOIN follow_ups f ON f.PropertyID=p.PropertyID AND f.UserID=? ':' ').'
				WHERE	'.($this->GetFollowUpFilter($followUpMode)).
		($this->GetAdminModeFilter($adminMode)).'
						AND DATE(p.Added)>=DATE_SUB( STR_TO_DATE("'.$this->userRegistrationDate.'","'.MYSQL_ISO_DATE_FORMAT.'"),INTERVAL '.NR_OF_PREVIOUS_ACCESS_DAYS.' DAY) AND [SEARCH_FILTER]
						[FLEXIGRID_OPTIONS]';

		$parameters = array();

		if ($adminMode)
		$parameters = array($propertyType);
		elseif ($followUpMode)
		$parameters =array($userID,$userID,$userID,$userID);
		else
		$parameters = array($propertyType,$userID,$userID,$userID);

		$query = $this->buildSearchCriteria($sql,$parameters,$selectedDate,$searchFilter,$followUpMode,$flexigridOptions,$propertyType);

		if  ($query->num_rows()>0)
		return $query->first_row()->NrOfProperties;
		else
		return 0;
	}

	function GetPositionOfProperty($propertyType,$propertyID,$userID,$selectedDate,$searchFilter,$adminMode,$followUpMode=false,$flexigridOptions)
	{
		$sql = 'SELECT	COUNT(*) as NrOfProperties
				FROM	properties p
						INNER JOIN
						lead_types lt ON lt.LeadTypeID=p.LeadTypeID
						LEFT JOIN agents a1 ON a1.AgentID=p.FirstAgentID
						LEFT JOIN agents a2 ON a2.AgentID=p.SecondAgentID
						'.($followUpMode?' INNER JOIN follow_ups f ON f.PropertyID=p.PropertyID AND f.UserID=? ':' ').'
				WHERE	'.($this->GetFollowUpFilter($followUpMode)).'
						AND p.PropertyID < ?'.
		($this->GetAdminModeFilter($adminMode)).'
						AND DATE(p.Added)>=DATE_SUB( STR_TO_DATE("'.$this->userRegistrationDate.'","'.MYSQL_ISO_DATE_FORMAT.'"),INTERVAL '.NR_OF_PREVIOUS_ACCESS_DAYS.' DAY) AND [SEARCH_FILTER]
						[FLEXIGRID_OPTIONS]';

		$parameters = array();

		if ($adminMode)
		$parameters = array($propertyType,$propertyID);
		elseif ($followUpMode)
		$parameters = array($userID,$propertyID,$userID,$userID,$userID);
		else
		$parameters = array($propertyType,$propertyID,$userID,$userID,$userID);


		$query = $this->buildSearchCriteria($sql,$parameters,$selectedDate,$searchFilter,$followUpMode,$flexigridOptions,$propertyType);

		if  ($query->num_rows()>0)
		return ((int)$query->first_row()->NrOfProperties)+1;
		else
		return 1;
	}


	/**
	 * Returns the page number on which the Property is located
	 *
	 * @param string $propertyType Property type
	 * @param int $propertyID PropertyID
	 * @param int $nrOfRows Number of rows to display on page
	 * @return int Page number
	 *
	 */
	function GetPageNrOfProperty($propertyType,$propertyID,$nrOfRows=10,$userID,$selectedDate,$searchFilter,$adminMode,$followUpMode,$flexigridOptions)
	{
		$propertyPosition = (int)$this->GetPositionOfProperty($propertyType,$propertyID,$userID,$selectedDate,$searchFilter,$adminMode,$followUpMode,$flexigridOptions);

		$pageNr = (int)($propertyPosition/$nrOfRows)+1;

		if ($propertyPosition>=$nrOfRows && $propertyPosition%$nrOfRows==0)
		$pageNr--;

		return $pageNr;
	}

	/**
	 * Returns the latitude and longitude of given address
	 *
	 * @param string $address Search address
	 * @return array Contains the longitude and latitude if search was successful, otherwise NULL
	 */
	private function GetCoordinatesOfAddress($address)
	{
		// Initialize delay in geocode speed
		$delay = 0;
		$base_url = "http://" . MAPS_HOST . "/maps/geo?output=xml" . "&key=" .MAP_KEY."&oe=utf8";

		// Iterate through the rows, geocoding each address
		$isInsert=FALSE;
		$geocode_pending = true;

		while ($geocode_pending)
		{
			$request_url = $base_url . "&q=" . urlencode($address);
			$xml = simplexml_load_file($request_url); //or die("url not loading");
			$status = $xml->Response->Status->code;

			if (strcmp($status, "200") == 0)
			{
				// Successful geocode
				$geocode_pending = false;
				$coordinates = $xml->Response->Placemark->Point->coordinates;
				$coordinatesSplit = explode(",", $coordinates);

				// Format: Longitude, Latitude, Altitude
				$geocode['latitude']=$coordinatesSplit[1];
				$geocode['longitude']=$coordinatesSplit[0];
			}
			elseif (strcmp($status, "620") == 0)
			{
				$delay += 100000;
			}
			else
			{
				$geocode=NULL;
				$geocode_pending = false;
			}
			usleep($delay);
		}

		return $geocode;
	}

	/**
	 * Returns the latitude and longitude of the property's address
	 *
	 * @return array Contains the longitude and latitude if search was successful, otherwise 0,0
	 */
	function GetCoordinatesOfProperty($country,$state,$city,$street,$zip)
	{
		$addressArray = array($zip,$street,$city,$state,$country);
		$i = 0;
		$coordinates = null;
		$address = "";

		while ($i<4 && $coordinates==null)
		{
			$address = "";

			for ($j=$i;$j<count($addressArray);$j++)
			$address .= $addressArray[$j]." ";

			$address = substr($address,0,strlen($address)-1);

			$coordinates = $this->GetCoordinatesOfAddress($address);

			$i++;
		}

		if($coordinates==null)
		{
			$coordinates = array();
			$coordinates['latitude']='0';
			$coordinates['longitude']='0';
		}

		return $coordinates;
	}

	function RemoveUserProperty($userID,$propertyType,$propertyID,$followUpMode)
	{
		$this->db->select('PropertyID');
		$this->db->from('properties p');
		if (!$followUpMode)
		{
			$this->db->join('lead_types lt','lt.LeadTypeID=p.LeadTypeID');
			$this->db->where('lt.Name',$propertyType);
		}
		$this->db->where('PropertyID',$propertyID);

		$query = $this->db->get();

		if ($query->num_rows()==1)
		{
			$this->db->select('PropertyID');
			$this->db->from('deleted_user_properties');
			$this->db->where('UserID',$userID);
			$this->db->where('PropertyID',$propertyID);

			$query = $this->db->get();

			if ($query->num_rows()==0)
			{
				$data = array('UserID' => $userID  ,'PropertyID' => $propertyID);
				$this->db->insert('deleted_user_properties', $data);

				$this->DeleteFollowUpsOfProperty($userID,$propertyID);
			}
		}
	}

	function DeleteFollowUpsOfProperty($userID,$propertyID)
	{
		$this->db->where('PropertyID',$propertyID);
		if ($userID!=null)
		$this->db->where('UserID',$userID);
		$this->db->delete('follow_ups');

		return $this->db->_error_number()==0;
	}
    
    function remove_fsbo_dublicate($property_id, $user_id)
    {
   	    $this->db->select('*');
		$this->db->from('properties p');
		$this->db->where('fsbo_parent_id',$property_id);
		$this->db->where('UserID', $user_id);
        
        $query = $this->db->get();
        
        $result = $query->result();
        
        foreach($result as $property)
        {
            $this->DeletePropertyByID($property->PropertyID);
        }
        return true;
    }
    
    function remove_from_fsbo($data)
    {
        $this->db->select('*');
		$this->db->from('fsbo_deleted_property');
		$this->db->where('user_id', $data['user_id']);
		$this->db->where('property_id', $data['property_id']);
		
		$query = $this->db->get();
		
		if ($query->num_rows() == 0)
            $this->db->insert('fsbo_deleted_property', $data);

        return;
    }
    
    function DeletePropertyByID($propertyID)
    {
        $this->db->where('property_id', $propertyID);
		$this->db->delete('property_data');

		$error = $this->db->_error_message();
		if (!empty($error))
		return false;
        
        $this->db->where('PropertyID', $propertyID);
		$this->db->delete('follow_ups');

		$error = $this->db->_error_message();
		if (!empty($error))
		return false;
        
        
        $this->db->where('PropertyID', $propertyID);
		$this->db->delete('fsbo_urls');

		$error = $this->db->_error_message();
		if (!empty($error))
		return false;

		$this->db->where('PropertyID', $propertyID);
		$this->db->delete('deleted_user_properties');

		$error = $this->db->_error_message();
		if (!empty($error))
		return false;

		$this->db->where('PropertyID', $propertyID);
		$this->db->delete('lead_notes');

		$error = $this->db->_error_message();
		if (!empty($error))
		return false;

		$this->db->where('PropertyID', $propertyID);
		$this->db->delete('lead_statuses');

		$error = $this->db->_error_message();
		if (!empty($error))
		return false;

		$this->db->where('FirstNeighborID', $propertyID);
		$this->db->delete('neighbors');

		$error = $this->db->_error_message();
		if (!empty($error))
		return false;

		$this->db->where('SecondNeighborID', $propertyID);
		$this->db->delete('neighbors');

		$error = $this->db->_error_message();
		if (!empty($error))
		return false;

		$this->db->where('PropertyID', $propertyID);
		$this->db->delete('owner_infos');

		$error = $this->db->_error_message();
		if (!empty($error))
		return false;
        
        $this->db->where('property_id', $propertyID);
		$this->db->delete('folder_properties');

		$error = $this->db->_error_message();
		if (!empty($error))
		return false;

		$this->db->where('PropertyID', $propertyID);
		$this->db->delete('property_images');

		$error = $this->db->_error_message();
		if (!empty($error))
		return false;

		$success = $this->DeleteFollowUpsOfProperty(null,$propertyID);
		if (!$success)
		return false;

		$this->db->where('PropertyID', $propertyID);
		$this->db->delete('properties');

		$error = $this->db->_error_message();
		if (!empty($error))
		return false;
}


	function DeleteProperty($propertyType,$propertyID)
	{
		$this->db->select('PropertyID');
		$this->db->from('properties p');
		$this->db->join('lead_types lt','p.LeadTypeID=lt.LeadTypeID');
		$this->db->where('PropertyID',$propertyID);
		$this->db->where('lt.Name',$propertyType);

		$query = $this->db->get();

		if ($query->num_rows()>0)
		{
			$this->db->where('PropertyID', $propertyID);
			$this->db->delete('fsbo_urls');

			$error = $this->db->_error_message();
			if (!empty($error))
			return false;

			$this->db->where('PropertyID', $propertyID);
			$this->db->delete('deleted_user_properties');

			$error = $this->db->_error_message();
			if (!empty($error))
			return false;

			$this->db->where('PropertyID', $propertyID);
			$this->db->delete('lead_notes');

			$error = $this->db->_error_message();
			if (!empty($error))
			return false;

			$this->db->where('PropertyID', $propertyID);
			$this->db->delete('lead_statuses');

			$error = $this->db->_error_message();
			if (!empty($error))
			return false;

			$this->db->where('FirstNeighborID', $propertyID);
			$this->db->delete('neighbors');

			$error = $this->db->_error_message();
			if (!empty($error))
			return false;

			$this->db->where('SecondNeighborID', $propertyID);
			$this->db->delete('neighbors');

			$error = $this->db->_error_message();
			if (!empty($error))
			return false;

			$this->db->where('PropertyID', $propertyID);
			$this->db->delete('owner_infos');

			$error = $this->db->_error_message();
			if (!empty($error))
			return false;

			$this->db->where('PropertyID', $propertyID);
			$this->db->delete('property_images');

			$error = $this->db->_error_message();
			if (!empty($error))
			return false;

			$success = $this->DeleteFollowUpsOfProperty(null,$propertyID);
			if (!$success)
			return false;

			$this->db->where('PropertyID', $propertyID);
			$this->db->delete('properties');

			$error = $this->db->_error_message();
			if (!empty($error))
			return false;
		}
		else
		return false;

		return true;
	}

	function GetPropertyImages($propertyID)
	{
		$this->db->select('name,IFNULL(extension,".jpg") as extension',false);
		$this->db->from('property_images');
		$this->db->where('PropertyID',$propertyID);

		$query = $this->db->get();

		return $query->result_array();
	}
    
    function get_images_for_contact($propertyID)
    {
        $this->db->select('Name as name, PropertyImageID as id',false);
		$this->db->from('property_images');
		$this->db->where('PropertyID',$propertyID);

		$query = $this->db->get();

		return $query->result();
    }
    
    function remove_fsbo_image($id)
    {
        $this->db->where('PropertyImageID',$id);
        $this->db->delete('property_images');
    }
    
	function GetNrOfImagesForProperty($propertyID)
	{
		$this->db->select('COUNT(*) as count');
		$this->db->from('property_images');
		$this->db->where('PropertyID',$propertyID);

		$query = $this->db->get();

		if ($query->num_rows()>0)
		return $query->first_row()->count;
		else
		return 0;
	}

	function AddProperty($data,$checkDNC = false)
	{
		$this->_setPhoneData($data);

		if ($data['LeadTypeID']==$this->GetPropertyTypeIDFromName('fsbo'))
		{
			$gibberish = "{{[-]}[-]{[-]}[-]([-])}"; #stupid

			$sql = "SELECT	PropertyID
					FROM	properties
					WHERE	LeadTypeID={$data['LeadTypeID']}
							AND ((ListPrice=? AND FSBOPhone=?) OR (Address=? AND City=? AND StateID=? AND ZIP=?) OR FSBODescription=?)";

			if (isset($data["ListPrice"]) && isset($data["FSBOPhone"]) && !empty($data["ListPrice"]) && !empty($data["FSBOPhone"]))
			{
				$listPrice = $data["ListPrice"];
				$phone = $data["FSBOPhone"];
			}
			else
			{
				$listPrice = $gibberish;
				$phone = $gibberish;
			}

			$address = isset($data["Address"]) && !empty($data["Address"])?$data["Address"]:$gibberish;
			$city = isset($data["City"]) && !empty($data["City"])?$data["City"]:$gibberish;
			$stateID = isset($data["StateID"]) && !empty($data["StateID"])?$data["StateID"]:$gibberish;
			$zip = isset($data["ZIP"]) && !empty($data["ZIP"])?$data["ZIP"]:$gibberish;
			$FSBODescription = isset($data["FSBODescription"]) && !empty($data["FSBODescription"]) ?$data["FSBODescription"]:$gibberish;

			$query = $this->db->query($sql,array($listPrice,$phone,$address,$city,$stateID,$zip,$FSBODescription));

			$propertyExists = $query->num_rows()>0;
		}
		else
		$propertyExists = false;


		if (!$propertyExists)
		{
			$this->_setPropertyData($data);
			$this->db->insert('properties');
			$propertyID = $this->db->insert_id();

			$this->UpdateWhitePagesData($propertyID,$data);

			if ($checkDNC)
			$this->model_dnc->checkDNCForProperty($propertyID,$data);

			return $propertyID;
		}
		else
		return -1;
	}

	function _setPhoneData(&$data)
	{
		if (isset($data["Phone"]))
		$data["Phone"] = $this->model_utils->getPhone($data["Phone"]);
		if (isset($data["OccupantPhone"]))
		$data["OccupantPhone"] = $this->model_utils->getPhone($data["OccupantPhone"]);
		if (isset($data["OwnerPhone"]))
		$data["OwnerPhone"] = $this->model_utils->getPhone($data["OwnerPhone"]);
		if (isset($data["TaxRecordsPhone"]))
		$data["TaxRecordsPhone"] = $this->model_utils->getPhone($data["TaxRecordsPhone"]);
		if (isset($data["WhitePagesPhone"]))
		$data["WhitePagesPhone"] = $this->model_utils->getPhone($data["WhitePagesPhone"]);
		if (isset($data["FSBOPhone"]))
		$data["FSBOPhone"] = $this->model_utils->getPhone($data["FSBOPhone"]);
	}

	function UpdateProperty($propertyID,$data,$checkDNC = false)
	{
		$this->_setPhoneData($data);

		$this->_setPropertyData($data);
		$this->db->where('PropertyID',$propertyID);
		$this->db->update('properties');

		$this->UpdateWhitePagesData($propertyID,$data);

		if ($checkDNC)
		$this->model_dnc->checkDNCForProperty($propertyID,$data);

		$error = intval($this->db->_error_number());

		return  $error==0;
	}

	function _setPropertyData($data)
	{
		if (!isset($data['MLSID']))
		{
			$mlsID = null;
			if (isset($data['ZIP']) && trim($data['ZIP']!=""))
			$mlsID = $this->model_property->GetMLSIDOfZIP($data['ZIP']);

			if ($mlsID==null)
			{
				$mlsData = $this->model_mls->GetAllMLS(true);
				$mlsID = $mlsData[0]['mls_id'];
			}

			$data['MLSID'] = $mlsID;
		}

		foreach($data as $key=>$value)
		{
			if (strpos(strtolower($key),"date")!=false)
			$this->db->set($key,'STR_TO_DATE("'.$value.'","'.MYSQL_DATE_FORMAT.'")',false);
			else
			$this->db->set($key,$value);
		}

	}

	function UpdateWhitePagesData($propertyID,$data)
	{
		$name = isset($data["WhitePagesName"])?$data["WhitePagesName"]:"";
		$phone = isset($data["WhitePagesPhone"])?$this->model_utils->getPhone($data["WhitePagesPhone"]):"";
		$dnc = isset($data["WhitePagesDNC"])?$data["WhitePagesDNC"]:0;

		if ($name!="" || $phone!="")
		{
			$this->db->where('PropertyID',$propertyID);
			$this->db->delete('white_pages_data');

			$this->AddWhitePagesData
			(
			$propertyID,
			array
			(
					'PropertyID'	=>	$propertyID,
					'Name'			=>	$name,
					'Phone'			=>	$phone,
					'DNC'			=>	$dnc
			)
			);

		}
	}

	function DeleteWhitePagesData($propertyID)
	{
		$this->db->where('PropertyID',$propertyID);
		$this->db->delete('white_pages_data');
	}

	function GetPropertyTypeIDFromName($propertyType)
	{
		$this->db->select("LeadTypeID");
		$this->db->from("lead_types");
		$this->db->where("Name",$propertyType);

		$query = $this->db->get();

		if ($query->num_rows()>0)
		return $query->first_row()->LeadTypeID;
		else
		return -1;
	}

	function GetPropertyTypeNameFromID($propertyTypeID)
	{
		$this->db->select("Name");
		$this->db->from("lead_types");
		$this->db->where("LeadTypeID",$propertyTypeID);

		$query = $this->db->get();

		if ($query->num_rows()>0)
		return $query->first_row()->Name;
		else
		return -1;
	}

	function GetZIPInfo($userID)
	{
		$sql = 'SELECT		DISTINCT fz.ZIP,fc.City
				FROM		properties p
							INNER JOIN (SELECT	m.mls_id
										FROM	mls m
												INNER JOIN user_mls um ON um.mls_id=m.mls_id AND um.user_id=?) m
							INNER JOIN fsbo_zip fz ON fz.mls_id=m.mls_id
							INNER JOIN fsbo_city fc ON fc.id=fz.city_id
				ORDER BY	fz.ZIP ASC';

		$query = $this->db->query($sql,array($userID));

		return $query->result_array();
	}

	public function DeleteNeighbor($firstNeighborID,$secondNeighborID)
	{
		$this->db->where('FirstNeighborID', $firstNeighborID);
		$this->db->where('SecondNeighborID', $secondNeighborID);
		$this->db->delete('neighbors');

		return $this->db->_error_number()==0;
	}

	public function AddNeighbor($firstNeighborID,$secondNeighborID)
	{
		$this->db->select('FirstNeighborID');
		$this->db->from('neighbors');
		$this->db->where('FirstNeighborID',$firstNeighborID);
		$this->db->where('SecondNeighborID',$secondNeighborID);
		$this->db->limit(1);

		$query = $this->db->get();

		if ($query->num_rows()==0)
		$this->db->insert('neighbors',array('FirstNeighborID'=>$firstNeighborID,'SecondNeighborID'=>$secondNeighborID));

		return $this->db->_error_number()==0;
	}

	public function CheckIfRelistedProperty($propertyData)
	{
		$this->db->select('PropertyID');
		$this->db->from('properties');
		$this->db->where('is_expired',1);
		$this->db->where('Address',$propertyData['Address']);
		$this->db->where('ZIP',$propertyData['ZIP']);
		$this->db->where("DATEDIFF('".date(PHP_ISO_DATE_HOUR_MINUTE_SECOND_FORMAT)."',post_date)<=31");

		$query = $this->db->get();

		if ($query->num_rows() > 0)
		{
			$result = $query->result();
            
            foreach($result as $pr)
            {
    			$this->db->set('Status','Re-Listed');
    			$this->db->where('PropertyID',$pr->PropertyID);
    			$this->db->update('properties');
            }
		}
	}

	/*
	 * used for updating the database with data from the MLS system
	 */
	public function insertOrUpdateProperty($property,$agent,$agentInfoExists=true)
	{
		#If JL/JS check if there is an Expired lead with the same address
		if ($property['LeadTypeID']==4)
		$this->CheckIfRelistedProperty($property);

		$this->db->select('PropertyID,MLSNr,FirstAgentID,SecondAgentID,Status,LeadTypeID');
		$this->db->from('properties');
		$this->db->where('MLSNr', $property['MLSNr']);
		$this->db->where('LeadTypeID',$property['LeadTypeID']);

		$query = $this->db->get()->result();

		if (count($query) > 0)
		{
			if ($agentInfoExists)
			{
				$this->model_agent->insertOrUpdateAgent($query[0]->FirstAgentID,$agent["Name"],$agent["Phone"],$agent["AdditionalPhone"],$agent["Fax"],$agent["Broker"],$agent["BrokerOfficePhone"],$agent["ManagerPhone"],$agent["Pager"]);
				$this->model_agent->insertOrUpdateAgent($query[0]->SecondAgentID,$agent["SecondAgentName"],$agent["SecondAgentPhone"]);
			}

			//$property['PreviousStatus'] = $query[0]->Status;

			$this->db->where('MLSNr', $property['MLSNr']);
			$this->db->where('LeadTypeID',$property['LeadTypeID']);
			$this->db->update('properties', $property);

			return $query[0]->PropertyID;
		}
		else //insert
		{
			if($agentInfoExists)
			{
				//insert agents first to get the ids
				$firstAgentID = $this->model_agent->insertOrUpdateAgent("",$agent["Name"],$agent["Phone"],$agent["AdditionalPhone"],$agent["Fax"],$agent["Broker"],$agent["BrokerOfficePhone"],$agent["ManagerPhone"],$agent["Pager"]);
				$secondAgentID = $this->model_agent->insertOrUpdateAgent("",$agent["SecondAgentName"],$agent["SecondAgentPhone"]);
				$property['FirstAgentID'] = $firstAgentID;
				$property['SecondAgentID'] = $secondAgentID;
			}

			//$property['Added'] = date('Y-m-d H:i:s');
			//$property['UserID'] = $userID;
			$this->db->insert('properties', $property);

			return $this->db->insert_id();
		}
	}

	function insertProperty($property)
	{
		$this->db->insert('properties',$property);
		return $this->db->insert_id();
	}

	/*
	 * searches for the property if doesn't exists in db inserts it
	 * @return the property id
	 */
	function insertNODProperty($property)
	{
		$this->db->select('PropertyID');
		$this->db->where('ParcelNumber', $property["ParcelNumber"]);
		$this->db->where('ZIP',$property["ZIP"]);
		$this->db->where('StateID',$property["StateID"]);
		$this->db->where('Address',$property["Address"]);
		$this->db->where('OwnerName',$property["OwnerName"]);
		$query = $this->db->get('properties',1)->result();
		if(count($query) > 0)
		{
			return $query[0]->PropertyID;
		}
		else//insert
		{
			//$property['Added'] = date('Y-m-d H:i:s');
			$this->db->insert('properties', $property);
			return $this->db->insert_id();
		}
	}
    
    function get_count_items($userID, $type_id, $define, $propertyType)
    {
        if($propertyType == 'followups')
            $lead_type_id = 6;
        elseif ($propertyType == 'fsbo')
            $lead_type_id = 2;
        elseif ($propertyType == 'expireds')
            $lead_type_id = 1;
        elseif ($propertyType == 'nod')
            $lead_type_id = 3;
        elseif ($propertyType == 'jljs')
            $lead_type_id = 4;
        else
            $lead_type_id = 6;
            
        
        $this->db->select('*');
        $this->db->from('properties p');
        
        if($propertyType == 'followups')
        {
            $this->db->join('follow_ups f_u', 'f_u.PropertyID = p.PropertyID');
            $this->db->group_by('p.PropertyID');
        }
        
		$this->db->where('p.userID', $userID);
        $this->db->where('p.is_tmp', 0);
        $this->db->where('((p.is_contact = 1 OR p.fsbo_move_to_contact = 1))');
		$this->db->where($define, $type_id);

        $query = $this->db->get();
		return $query->result();
    }
    
    
	function getProperty($propertyID,$fields)
	{
		$this->db->select($fields);
		$this->db->where('PropertyID',$propertyID);
		$query = $this->db->get('properties');
		if($query->num_rows()>0)
		return $query->first_row();
		else
		return null;
	}

	function getPropertyDNCForDatabaseUpdate($from=0,$howMany=100)
	{
		$this->db->select('PropertyID, OccupantPhone, OccupantDNC, FSBOPhone, FSBODNC, WhitePagesPhone, WhitePagesDNC, TaxRecordsPhone, TaxRecordsDNC, MLSPhone, MLSDNC, Phone, DNC');
		$this->db->limit($howMany, $from);
		return $this->db->get('properties')->result();
	}

	function getLeadTypeID($leadtypeName)
	{
		$this->db->select('LeadTypeID');
		$this->db->from('lead_types');
		$this->db->where('name',$leadtypeName);

		$query = $this->db->get();

		if ($query->num_rows()>0)
		return $query->first_row()->LeadTypeID;
		else
		return null;
	}

	public function AddPropertyAndNeighbor($propertyID,$neighborProperty)
	{
		$this->db->select('PropertyID');
		$this->db->from('properties');
		$this->db->where('Address', $neighborProperty['Address']);
		$this->db->where('City', $neighborProperty['City']);
		$this->db->where('ZIP', $neighborProperty['ZIP']);
		$this->db->where('StateID', $neighborProperty['StateID']);
		$this->db->where('WhitePagesName', $neighborProperty['WhitePagesName']);
		$this->db->where('WhitePagesPhone', $neighborProperty['WhitePagesPhone']);

		$query = $this->db->get()->result();

		if(isset($query[0]->PropertyID))
		{
			$neighborPropertyID = $query[0]->PropertyID;
		}
		else
		{
			//$neighborProperty['Added'] = date('Y-m-d H:i:s');
			$this->db->insert('properties', $neighborProperty);
			$neighborPropertyID = $this->db->insert_id();
		}

		if($propertyID > $neighborPropertyID)
		$this->AddNeighbor($neighborPropertyID , $propertyID);
		else
		$this->AddNeighbor($propertyID, $neighborPropertyID);

		return $neighborPropertyID;
	}

    public function AddSetAppointment($params, $data)
    {
        $this->db->select('PropertyID');
		$this->db->from('properties');
		$this->db->where('PropertyID',$params['propertyID']);
		$this->db->where('UserID', $params['userID']);
		$this->db->limit(1);

		$query = $this->db->get();

		if ($query->num_rows()>0)
		{
            /*$this->db->select('id');
			$this->db->from('set_appointments');
			$this->db->where('property_id',$params['propertyID']);
			$this->db->limit(1);

			$query = $this->db->get();

			if ($query->num_rows()>0)
			{
				$this->db->where('id', $query->first_row()->id);
				$this->db->update('set_appointments', $data);
			}
			else
			{
				
			}*/
            $this->db->insert('set_appointments', $data);
            return $this->db->insert_id();
        }
        
        return $this->db->_error_number() == 0;
    }
    
    public function update_set_appt($appt_id, $data)
    {
        $this->db->where('id', $appt_id); 
        $this->db->update('set_appointments', $data);
        return ;
    }
    
    public function update_properties_by_param($define, $value, $user_id, $data)
    {
        $this->db->where($define, $value); 
        $this->db->where('userID', $user_id); 
        $this->db->update('properties', $data);
        return ;
    }

	public function AddFollowUp($userID,$statusID,$propertyID,$daysToAdd,$customDate)
	{
		$this->db->select('PropertyID');
		$this->db->from('properties');
		$this->db->where('PropertyID',$propertyID);
		$this->db->where('UserID IS NULL OR UserID=',$userID,false);
		$this->db->limit(1);

		$query = $this->db->get();

		if ($query->num_rows()>0)
		{
			$this->db->select('FollowUpID');
			$this->db->from('follow_ups');
			$this->db->where('UserID',$userID);
			$this->db->where('PropertyID',$propertyID);
			$this->db->limit(1);

			$query = $this->db->get();

			if ($daysToAdd==="custom")
			$setTo = 'STR_TO_DATE("'.$customDate.'","'.MYSQL_DATE_FORMAT.'")';
			else
			$setTo = 'DATE_ADD("'.date(PHP_ISO_DATE_HOUR_MINUTE_SECOND_FORMAT).'",INTERVAL '.$daysToAdd.' DAY)';

			if ($query->num_rows()>0)
			{
				$this->db->set('SetTo',$setTo,false);
				$this->db->where('FollowUpID',$query->first_row()->FollowUpID);
				$this->db->update('follow_ups');
			}
			else
			{
				$this->db->set('UserID',$userID);
				$this->db->set('PropertyID',$propertyID);
				if ($statusID>0)
				$this->db->set('StatusID',$statusID);
				$this->db->set('SetTo',$setTo,false);
				$this->db->insert('follow_ups');
			}
		}

		return $this->db->_error_number() == 0;
	}

	function check_jljs($userID)
	{
		$this->db->select('Added');
		$this->db->where('UserID', $userID);
		$this->db->where('LeadTypeID', 4);
		$this->db->from('properties');
		$query = $this->db->get();

		$now_date = date('Y-m-d');
		$i = 0;
		foreach ($query->result_array() as $row)
		{
			$date = explode(' ',$row['Added']);
			if ($now_date == $date[0])
			{
				$i++;
			}
		}
		return $i;
	}

	function GetNrOfNewPropertiesForUser($userID)
	{
		$this->db->select('COUNT(p.LeadTypeID) as Nr,dt.Display as Display',false);
		$this->db->from('users u');
		$this->db->from('lead_types lt');
		$this->db->join('display_text dt','CONCAT("stats_popup_",lt.Name)=dt.Name');
		$this->db->join('properties p','lt.LeadTypeID=p.LeadTypeID AND TO_DAYS(DATE(p.Added))-TO_DAYS(IF(p.LeadTypeID=3,DATE("'.TODAY.'"),DATE("'.PREVIOUS_DAY.'")))=0 AND (p.UserID IS NULL OR p.UserID='.$userID.')','left');
		$this->db->where('u.id',$userID);
		$this->db->where('lt.LeadTypeID<>',5,false);
		$this->db->group_by('lt.LeadTypeID');
		$this->db->order_by('Nr','desc');


		$query = $this->db->get();

		if ($query->num_rows()>0)
			return $query->result_array();
		else
			return null;
	}

	function GetNrOfNewPhoneNumbers($userID)
	{
		$data = array();

		$dncWhitePages = intval($this->GetNrOfNewDNCPhoneNumbersByType('WhitePages',$userID));
		$dncTaxRecords = intval($this->GetNrOfNewDNCPhoneNumbersByType('TaxRecords',$userID));
		$dncMLS = intval($this->GetNrOfNewDNCPhoneNumbersByType('MLS',$userID));
		$dnc = intval($this->GetNrOfNewDNCPhoneNumbersByType('',$userID));
		$dncFSBO = intval($this->GetNrOfNewDNCPhoneNumbersByType('FSBO',$userID));

		$data[] = $this->GetNrOfNewPhoneNumbersByType('WhitePagesPhone',$userID);
		$data[] = $this->GetNrOfNewPhoneNumbersByType('TaxRecordsPhone',$userID);
		$data[] = $dnc + $dncFSBO + $dncMLS + $dncTaxRecords + $dncWhitePages;

		return $data;
	}

	function GetPropertiesForExport($userID,$propertyTypeID,$fromDate,$toDate)
	{
		$this->load->dbutil();

		$fields =
			'IFNULL(Status,"") as Status,
			IFNULL(PreviousStatus,"") as PreviousStatus,
			IF(IFNULL(DNC,0)=0,"n","y") as DNC,
			IFNULL(OwnerName,"") as OwnerName,
			IFNULL(p.Phone,"") as Phone,
			IFNULL(Address,"") as Address,
			IFNULL(City,"") as City,
			IFNULL(ZIP,"") as ZIP,
			IFNULL(s.name,"") as State,
			IFNULL(Occupancy,"") as Occupancy,
			IFNULL(OccupantName,"") as OccupantName,
			IFNULL(OccupantPhone,"") as OccupantPhone,
			IFNULL(OccupantDNC,"") as OccupantDNC,
			IFNULL(MLSNr,"") as MLSNr,
			DATE_FORMAT(IFNULL(OffMarketDate,""),"'.MYSQL_DATE_FORMAT.'") as OffMarketDate,
			IFNULL(FORMAT(ListPrice,0),"") as ListPrice,
			IFNULL(SoldPrice,"") as SoldPrice,
			IFNULL(PropertyType,"") as PropertyType,
			IFNULL(LotSize,"") as LotSize,
			IFNULL(BuildingSize,"") as BuildingSize,
			IFNULL(NrOfBeds,"") as NrOfBeds,
			IFNULL(NrOfBaths,"") as NrOfBaths,
			IFNULL(SubDivision,"") as SubDivision,
			IFNULL(OriginalPrice,"") as OriginalPrice,
			IFNULL(ListDate,"") as ListDate,
			IFNULL(ListStatus,"") as ListStatus,
			IFNULL(Added,"") as Added,
			IFNULL(a1.Name,"") as FirstAgentName,
			IFNULL(a2.Name,"") as SecondAgentName,
			IFNULL(OwnerPhone,"") as OwnerPhone,
			IFNULL(MLSName,"") as MLSName,
			IFNULL(MLSPhone,"") as MLSPhone,
			IFNULL(MLSDNC,"") as MLSDNC,
			IFNULL(TaxRecordsName,"") as TaxRecordsName,
			IFNULL(TaxRecordsPhone,"") as TaxRecordsPhone,
			IFNULL(TaxRecordsDNC,"") as TaxRecordsDNC,
			IFNULL(WhitePagesName,"") as WhitePagesName,
			IFNULL(WhitePagesPhone,"") as WhitePagesPhone,
			IFNULL(WhitePagesDNC,"") as WhitePagesDNC,
			IFNULL(lt.Name,"") as LeadType,
			IFNULL(TaxIDNr,"") as TaxIDNr,
			IFNULL(ParcelNumber,"") as ParcelNumber,
			IFNULL(Community,"") as Community,
			IFNULL(ExpirationDate,"") as ExpirationDate,
			IFNULL(Instructions,"") as Instructions,
			IFNULL(SubjectToCourt,"") as SubjectToCourt,
			IFNULL(PreviousListPrice,"") as PreviousListPrice,
			IFNULL(Style,"") as Style,
			IFNULL(OwnerEmail,"") as OwnerEmail,
			IFNULL(County,"") as County,
			IFNULL(NODFilingDate,"") as NODFilingDate,
			IFNULL(SoldDate,"") as SoldDate,
			IFNULL(FSBODescription,"") as FSBODescription,
			IFNULL(FSBOPhone,"") as FSBOPhone,
			IFNULL(FSBODNC,"") as FSBODNC';


		$data = array();

		if ($propertyTypeID<=-1)
		{
			list($headers,$data) = $this->GetFollowupsForExport($fields,$userID,$propertyTypeID,$fromDate,$toDate);
		}

		if ($propertyTypeID>=-1)
		{
			list($headers,$tempData) = $this->GetLeadsForExport($fields,$userID,$propertyTypeID,$fromDate,$toDate,$propertyTypeID);
			$data = array_merge($data,$tempData);
		}

		$result =  $this->dbutil->csv_from_array($headers,$data);
		return $result;
	}

	private function GetLeadsForExport($fields,$userID,$propertyTypeID,$fromDate,$toDate,$propertyTypeID)
	{
		$mySQLDateFormat = MYSQL_DATE_FORMAT;

		$sql = "SELECT	$fields
				FROM	properties p
						LEFT JOIN agents a1 ON p.FirstAgentID=a1.AgentID
						LEFT JOIN agents a2 ON p.SecondAgentID=a2.AgentID
						LEFT JOIN states s ON p.StateID=s.state_id
						INNER JOIN lead_types lt ON p.LeadTypeId=lt.LeadTypeID
				WHERE	DATE(p.Added)>=DATE_SUB( STR_TO_DATE('{$this->userRegistrationDate}','".MYSQL_ISO_DATE_FORMAT."'),INTERVAL '".NR_OF_PREVIOUS_ACCESS_DAYS."' DAY) AND
						DATE(p.Added)>=STR_TO_DATE('{$fromDate}','{$mySQLDateFormat}')
						AND DATE(p.Added)<=STR_TO_DATE('{$toDate}','{$mySQLDateFormat}')
						AND NOT EXISTS (SELECT 	PropertyID
										FROM	deleted_user_properties dup
										WHERE	dup.PropertyID=p.PropertyID)";

		if ($propertyTypeID<0)
			$sql .= " AND NOT EXISTS (
						SELECT	PropertyID
						FROM	follow_ups fu
						WHERE	fu.PropertyID=p.PropertyID
					  )";
		else
			$sql .= " AND p.LeadTypeID = $propertyTypeID";

		#Removed because all leads have to be exported
		//$sql .= " AND p.UserID=$userID";
		$query = $this->db->query($sql);
		$result = array($query->list_fields(),$query->result_array());

		return $result;
	}

	private function GetFollowupsForExport($fields,$userID,$propertyTypeID,$fromDate,$toDate)
	{
		$this->db->select($fields,false);

		$this->db->from('follow_ups fu');
		$this->db->join('properties p','fu.propertyID=p.propertyID');
		$this->db->join('agents a1','p.FirstAgentID=a1.AgentID','left');
		$this->db->join('agents a2','p.SecondAgentID=a2.AgentID','left');
		$this->db->join('states s','p.StateID=s.state_id','left');
		$this->db->join('lead_types lt','p.LeadTypeId=lt.LeadTypeID');

		$this->db->where('DATE(fu.SetTo)>=','STR_TO_DATE("'.$fromDate.'","'.MYSQL_DATE_FORMAT.'")',false);
		$this->db->where('DATE(fu.SetTo)<=','STR_TO_DATE("'.$toDate.'","'.MYSQL_DATE_FORMAT.'")',false);
		$this->db->where('fu.UserID',$userID);

		$query = $this->db->get();

		return array($query->list_fields(),$query->result_array());
	}

	public function GetCountyIDFromName($countyName)
	{
		$this->db->select('CountyID');
		$this->db->from('counties');
		$this->db->where('LOWER(REPLACE(Name," ","")) = "'.strtolower(str_replace(' ','',$countyName)).'"');
		$this->db->limit(1);

		$query = $this->db->get();

		if ($query->num_rows()==0)
		return null;
		else
		return $query->first_row()->CountyID;
	}

	public function GetMLSIDOfZIP($ZIP)
	{
		$this->db->select('mls_id');
		$this->db->from('fsbo_zip');
		$this->db->where('zip',$ZIP);

		$query = $this->db->get();

		if ($query->num_rows()>0)
		return $query->first_row()->mls_id;
		else
		return null;
	}

	public function CityExists($mlsID,$city)
	{
		$this->db->select('COUNT(*) as count');
		$this->db->from('fsbo_city');
		$this->db->where('city',$city);
		$this->db->where('mls_id',$mlsID);

		$query = $this->db->get();

		if ($query->first_row()->count>0)
		return true;
		else
		return false;
	}

	public function AddWhitePagesData($propertyID,$parameters)
	{
		if (!isset($parameters['Name']) || !isset($parameters['Phone']))
		return;

		$this->db->select('PropertyID');
		$this->db->from('white_pages_data');
		$this->db->where('PropertyID',$propertyID);
		$this->db->where('Name',$parameters['Name']);
		$this->db->where('Phone',$parameters['Phone']);

		$query = $this->db->get();

		if ($query->num_rows()>0)
		{
			$this->db->where('PropertyID',$propertyID);
			$this->db->where('Name',$parameters['Name']);
			$this->db->where('Phone',$parameters['Phone']);
			$this->db->update('white_pages_data',$parameters);
		}
		else
		{
			if (!isset($parameters['PropertyID']))
			$parameters['PropertyID'] = $propertyID;
			$this->db->insert('white_pages_data',$parameters);
		}

		$this->model_dnc->checkWhitePagesPhoneNumbersArray(array(array($propertyID,$parameters['Phone'],$parameters['Name'])));
	}

	public function GetWhitePagesData($propertyID)
	{
		$this->db->select('Name,Phone,DNC,Type,Address,City,ZIP,StateID');
		$this->db->from('white_pages_data');
		$this->db->where('PropertyID',$propertyID);
		$this->db->order_by('DataID','asc');

		return $this->db->get()->result_array();
	}

	public function CheckAndUpdateWhitePagesData($propertyID)
	{
		$this->db->select('Name,Phone,DNC');
		$this->db->from('white_pages_data');
		$this->db->where('PropertyID',$propertyID);

		$query = $this->db->get();

		if ($query->num_rows()==1)
		{
			$row = $query->first_row();

			$this->db->set('WhitePagesName',$row->Name);
			$this->db->set('WhitePagesPhone',$row->Phone);
			$this->db->set('WhitePagesDNC',$row->DNC);
			$this->db->where('PropertyID',$propertyID);
			$this->db->update('properties');
		}
	}

	private function GetNrOfNewPhoneNumbersByType($fieldName,$userID)
	{
		$this->db->select('COUNT(*) as Nr');
		$this->db->from('properties p');

		if ($fieldName=='WhitePages')
		{
			$this->db->join('white_pages_data wpd','p.PropertyID=wpd.PropertyID');
			$this->db->where('IFNULL(wpd.Phone,"")<>""');
		}
		else
		$this->db->where('IFNULL(p.'.$fieldName.',"")<>""');

		$this->db->where('DATE(p.Added)','IF(p.LeadTypeID=3,DATE("'.TODAY.'"),DATE("'.PREVIOUS_DAY.'"))',false);
		$this->db->where('(p.UserID IS NULL OR p.UserID='.$userID.')');

		$query = $this->db->get();

		if ($query->num_rows()>0)
		return $query->first_row()->Nr;
		else
		return 0;
	}

	private function GetNrOfNewDNCPhoneNumbersByType($fieldName,$userID)
	{
		$this->db->select('COUNT(*) as Nr');
		$this->db->from('properties p');

		if ($fieldName=='WhitePages')
		{
			$this->db->join('white_pages_data wpd','p.PropertyID=wpd.PropertyID');
			$this->db->where('IFNULL(wpd.Phone,"")<>""');
			$this->db->where('wpd.DNC',1);
		}
		else
		{
			$this->db->where('IFNULL(p.'.$fieldName.'Phone,"")<>""');
			$this->db->where('p.'.$fieldName.'DNC',1);
		}
		$this->db->where('DATE(p.Added)','IF(p.LeadTypeID=3,DATE("'.TODAY.'"),DATE("'.PREVIOUS_DAY.'"))',false);
		$this->db->where('(p.UserID IS NULL OR p.UserID='.$userID.')');

		$query = $this->db->get();

		if ($query->num_rows()>0)
			return $query->first_row()->Nr;
		else
			return 0;
	}

	private function GetFollowUpFilter($followUpMode,$addName = true,$addANDOperator = false)
	{
		if ($addANDOperator)
		$operator = " AND ";
		else
		$operator = " ";

		return	$followUpMode==false
		?($addName==true?' lt.Name=? ':' ')
		:$operator.' 1=1 ';

		/*			EXISTS (	SELECT	PropertyID
		 FROM	follow_ups fu
		 WHERE	fu.PropertyID=p.PropertyID '. //AND TO_DAYS(DATE_FORMAT(fu.SetTo,"%Y-%m-%d"))-TO_DAYS(DATE_FORMAT(CURDATE(),"%Y-%m-%d"))=0
		 ' AND fu.UserID=?   ) ';*/
	}

	private function GetAdminModeFilter($adminMode)
	{
		return 	$adminMode==true
		?' '
		:' 	AND NOT EXISTS (SELECT	PropertyID
									FROM	deleted_user_properties dup
									WHERE	dup.PropertyID=p.PropertyID AND dup.UserID=?)
					AND (p.UserID=? OR p.UserID IS NULL OR p.UserID=0)
					AND EXISTS (SELECT	mls_id
								FROM	user_mls um
								WHERE	um.user_id=? AND um.mls_id=p.MLSID) ';
	}

	private function GetAdminModeFilterForPropertyOrder($adminMode)
	{
		return 	$adminMode==true
		?' '
		:'  AND (p.UserID=? OR p.UserID IS NULL OR p.UserID=0)
					AND EXISTS (SELECT	mls_id
								FROM	user_mls um
								WHERE	um.user_id=? AND um.mls_id=p.MLSID)
					AND NOT EXISTS (SELECT	PropertyID
									FROM	deleted_user_properties dup
									WHERE	dup.PropertyID=p.PropertyID AND dup.UserID=?) ';
	}

	private function buildSearchCriteria($sql,$parameters,$selectedDate,$searchFilter,$followUpMode,$flexigridOptions,$propertyType)
	{       
		$isPopupSearch = isset($_POST['fldPopupSearch']);
		$isFlexigridSearch = !$isPopupSearch && (!isset($_POST['clearSearch']) || $_POST['clearSearch']=='false') && ((!empty($_POST['query']) && isset($_POST['qtype'])) || (isset($_POST["sortname"]) && $_POST["sortname"]!="PropertyID") || ($searchFilter=='' && $this->session->userdata('s_flexigridFilter')!==false));
        
		if ($isPopupSearch)
		$this->session->unset_userdata('s_flexigridFilter');

		if ($isFlexigridSearch===true || $followUpMode===true)
		$sql = str_replace('[FLEXIGRID_OPTIONS]',$this->getFlexigridQueryOptions(false,$flexigridOptions,$searchFilter,$followUpMode,$propertyType),$sql);
		else
		$sql = str_replace('[FLEXIGRID_OPTIONS]',' ',$sql);

//print_r($_POST);

		if ($followUpMode)
		{
			$sql = str_replace('AND [SEARCH_FILTER]',' ',$sql);
			$sql = str_replace('[SEARCH_FILTER]',' ',$sql);
		}
		elseif ($searchFilter=='')
		{
		  
			$sql = str_replace('[SEARCH_FILTER]',' TO_DAYS(STR_TO_DATE(?,"%Y/%m/%d"))-TO_DAYS(DATE_FORMAT(Added,"%Y/%m/%d"))=0 ',$sql);
			$parameters[] = $selectedDate;
		}
		else
		$sql = str_replace('[SEARCH_FILTER]',' ('.$searchFilter.') ',$sql);
        
		$query = $this->flexigrid->CI->db->query($sql,$parameters);

		return $query;
	}

	private function getFlexigridQueryOptions($limit = true,$flexigridOptions,$searchFilter,$followUpMode,$propertyType)
	{
		$query = "";
        if(isset($_POST['qtype']) && $_POST['qtype'] == 'SmartSearch' && trim($_POST['query']) != '')
        {
            $query .= 'AND (
(  IFNULL(p.Status,"") LIKE "%' . mysql_escape_string($_POST['query']) . '%"  ) 
OR (  COALESCE(
	IF(TRIM(IFNULL(TaxRecordsPhone,""))="" OR TRIM(IFNULL(TaxRecordsPhone,""))=TRIM(IFNULL(a1.BrokerOfficePhone,"")) OR TRIM(IFNULL(TaxRecordsPhone,""))=TRIM(IFNULL(a2.BrokerOfficePhone,"")) OR TRIM(IFNULL(TaxRecordsPhone,""))=TRIM(IFNULL(a1.Phone,"")) OR TRIM(IFNULL(TaxRecordsPhone,""))=TRIM(IFNULL(a2.Phone,"")),NULL,TaxRecordsPhone),
	IF(TRIM(IFNULL(WhitePagesPhone,""))="" OR TRIM(IFNULL(WhitePagesPhone,""))=TRIM(IFNULL(a1.BrokerOfficePhone,"")) OR TRIM(IFNULL(WhitePagesPhone,""))=TRIM(IFNULL(a2.BrokerOfficePhone,"")) OR TRIM(IFNULL(WhitePagesPhone,""))=TRIM(IFNULL(a1.Phone,"")) OR TRIM(IFNULL(WhitePagesPhone,""))=TRIM(IFNULL(a2.Phone,"")),NULL,WhitePagesPhone),
	IF(TRIM(IFNULL(MLSPhone,""))="" OR TRIM(IFNULL(MLSPhone,""))=TRIM(IFNULL(a1.BrokerOfficePhone,"")) OR TRIM(IFNULL(MLSPhone,""))=TRIM(IFNULL(a2.BrokerOfficePhone,"")) OR TRIM(IFNULL(MLSPhone,""))=TRIM(IFNULL(a1.Phone,"")) OR TRIM(IFNULL(MLSPhone,""))=TRIM(IFNULL(a2.Phone,"")),NULL,MLSPhone),
	IF(TRIM(IFNULL(FSBOPhone,""))="" OR TRIM(IFNULL(FSBOPhone,""))=TRIM(IFNULL(a1.BrokerOfficePhone,"")) OR TRIM(IFNULL(FSBOPhone,""))=TRIM(IFNULL(a2.BrokerOfficePhone,"")) OR TRIM(IFNULL(FSBOPhone,""))=TRIM(IFNULL(a1.Phone,"")) OR TRIM(IFNULL(FSBOPhone,""))=TRIM(IFNULL(a2.Phone,"")),NULL,FSBOPhone),
	IF(TRIM(IFNULL(p.Phone,""))="" OR TRIM(IFNULL(p.Phone,""))=TRIM(IFNULL(a1.BrokerOfficePhone,"")) OR TRIM(IFNULL(p.Phone,""))=TRIM(IFNULL(a2.BrokerOfficePhone,"")) OR TRIM(IFNULL(p.Phone,""))=TRIM(IFNULL(a1.Phone,"")) OR TRIM(IFNULL(p.Phone,""))=TRIM(IFNULL(a2.Phone,"")),NULL,p.Phone),
	""
) LIKE "%' . mysql_escape_string($_POST['query']) . '%"  )

OR (  IFNULL(p.OwnerEmail,IFNULL(p.FSBOEmail,"")) LIKE "%' . mysql_escape_string($_POST['query']) . '%"  )
OR (  IFNULL(p.Address,"") LIKE "%' . mysql_escape_string($_POST['query']) . '%"  )
OR (  IFNULL(p.City,"") LIKE "%' . mysql_escape_string($_POST['query']) . '%"  )
OR (  IFNULL(p.ZIP,"") LIKE "%' . mysql_escape_string($_POST['query']) . '%"  )
' . ($followUpMode===true?'OR (  DATE_FORMAT(f.SetTo,"%c/%d/%Y") LIKE "%' . mysql_escape_string($_POST['query']) . '%"  )':'') . '
OR (  COALESCE(
	IF(TRIM(IFNULL(TaxRecordsName,""))="",NULL,TaxRecordsName),
	IF(TRIM(IFNULL(WhitePagesPhone,""))="",NULL,WhitePagesName),
	IF(TRIM(IFNULL(MLSPhone,""))="",NULL,MLSName),
	IF(TRIM(IFNULL(FSBOName,""))="",NULL,FSBOName),
	IF(TRIM(IFNULL(OwnerName,""))="",NULL,OwnerName),"") 
LIKE "%' . mysql_escape_string($_POST['query']) . '%"  )
OR (  CONCAT("$",FORMAT(p.ListPrice,0)) LIKE "%' . mysql_escape_string($_POST['query']) . '%"  )
) ORDER BY  DATE(' . ($followUpMode===true?"f.SetTo":"p.Added") . ') ASC';
        }
        else
        {
    		$fields = self::$PROPERTIES_FIELD_QUERY_STATEMENTS['GENERAL'];
    		$followUpFields = self::$FOLLOWUP_FIELD_QUERY_STATEMENTS;
    
    		$fields = array_merge($fields,$followUpFields);
    
    		if (isset($this->flexigrid->post_info['sortname']) && isset($fields[$this->flexigrid->post_info['sortname']]))
    		$sortname = $fields[$this->flexigrid->post_info['sortname']];
    		else
    		$sortname = isset($this->flexigrid->post_info['sortname'])?"p.".$this->flexigrid->post_info['sortname']:false;
    
    		$tempQuery = $this->session->userdata('s_flexigridFilter');
    		$resetFilter = ($followUpMode && strpos($tempQuery,"p.Added")>=0) || (!$followUpMode && strpos($tempQuery,"f.SetTo")>=0);
    
    		if ($resetFilter || (isset($_POST['query']) && isset($_POST['qtype'])) || $this->session->userdata('s_flexigridFilter')===false)
    		{
    			if ($this->flexigrid->post_info['swhere'])
    			$query.=" AND ".$this->getFlexigridWhere($propertyType);
    
    			$query.= " ORDER BY DATE(".($followUpMode===true?"f.SetTo":"p.Added").") ASC, ".$sortname.' '.$this->flexigrid->post_info['sortorder'];
    
    			if ($limit)
    			$query.=" LIMIT ".$this->flexigrid->post_info['limitstart'].','.$this->flexigrid->post_info['rp'];
    
    			$this->session->set_userdata('s_flexigridFilter',$query);
    		}
    		else
    		$query = $this->session->userdata('s_flexigridFilter');
    
    		if ($this->flexigrid->post_info['swhere'])
    		{
    			$query = str_replace("AND"," AND ( ",$query);
    			$query = str_replace("ORDER BY"," ) ORDER BY ",$query);
    		}
        }
		return $query;
	}

	private function getFlexigridWhere($propertyType)
	{
		$valid_fields = array_keys(Leads::$LEAD_LIST_FIELDS_BY_TYPE[$propertyType]);
		$fields = self::$PROPERTIES_FIELD_QUERY_STATEMENTS['GENERAL'];
		$followUpFields = self::$FOLLOWUP_FIELD_QUERY_STATEMENTS;

		$fields = array_merge($fields,$followUpFields);

		$query = $this->input->post('query');
		$qtype = $this->input->post('qtype');

		if ($query != FALSE && $query != "" && $qtype!= FALSE && $qtype != "")
		{
			if (is_array($valid_fields))
			if (in_array($qtype,$valid_fields))
			return $this->flexigrid->searchstr_validator($query,$fields[$qtype]);
			else
			return FALSE;
			else
			return $this->flexigrid->searchstr_validator($query,$fields[$qtype]);
		}
		else
		return FALSE;
	}

	private function getFlexiGridLimit($searchFilter = '', $saveParam = 0)
	{  
        if($saveParam == 0)
        {
            if ($this->session->userdata('s_searchLimit')!=false && isset($_POST['searchName']))
                return " LIMIT ".$this->flexigrid->post_info['limitstart'].",".$this->session->userdata('s_searchLimit');
            else
                return " LIMIT ".$this->flexigrid->post_info['limitstart'].','.$this->flexigrid->post_info['rp'];
        }
        else
        {
            if ($this->session->userdata('s_searchLimit')!=false && isset($_POST['searchName']))
                return " LIMIT ".$this->flexigrid->post_info['limitstart'].",".$this->session->userdata('s_searchLimit');
            else
                return " LIMIT ".$this->flexigrid->post_info['limitstart'].','.$saveParam;
        }
	}
    
    function insert_property($data)
    {
        $this->db->insert('properties', $data);
        return $this->db->insert_id();
    }
    
    function update_property($propertyLeadID, $data)
    {
        $this->db->where('PropertyID', $propertyLeadID);
        $this->db->update('properties', $data);
        return;
    }
    
     function set_not_main_extra($propertyLeadID, $field)
    {
        $this->db->where('property_id', $propertyLeadID);
        $this->db->where('define', $field);
        $this->db->update('property_data', array('is_main' => 0));
        return;
    }
    
    function insert_extra_data($data)
    {
        $this->db->insert('property_data', $data);
        return $this->db->insert_id();
    }
    
    function check_extra_data($extra_id, $userID)
    {
        $this->db->select('*');
        $this->db->from('property_data');
        $this->db->join('properties', 'properties.PropertyID = property_data.property_id and UserID=' . $userID);
        $this->db->where('property_data.id', $extra_id);
        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            return $query->row();
        } else {
            return FALSE;
        }
    }
    
    function create_duplicate_contact($current_property, $user_id)
    {
        $query = 'INSERT INTO properties (`category`, `home_price_define`, `LeadTypeID`, `post_date`, `fsbo_type`, `is_fsbo`, `fsbo_city_id`, `FSBODescription`, `FSBOName`, `home_price_equal`, `grid_phone`, `grid_email`)
  SELECT `category`, `home_price_define`, `LeadTypeID`, `post_date`, `fsbo_type`, `is_fsbo`, `fsbo_city_id`, `FSBODescription`, `FSBOName`, `home_price_equal`, `grid_phone`, `grid_email`
  FROM properties WHERE properties.PropertyID = ' . $current_property;
        $this->db->query($query);
        $new_id = $this->db->insert_id(); 
        
        $query = "UPDATE properties SET fsbo_parent_id = " . $current_property . ", UserID = " . $user_id . " WHERE PropertyID = " . $new_id;
        $this->db->query($query);
        
        $this->db->select('*');
        $this->db->from('property_data');
        $this->db->where('property_id', $current_property);  
        $query = $this->db->get();
        $data = $query->result();
        
        foreach($data as $info)
        {
            $query = 'INSERT INTO property_data (`define`, `type_id`, `value`, `value_date`, `street`, `city`, `state`, `zip`, `is_main`, `is_show` )
                      SELECT `define`, `type_id`, `value`, `value_date`, `street`, `city`, `state`, `zip`, `is_main`, `is_show`
                      FROM property_data WHERE property_data.id = ' . $info->id;
            $this->db->query($query);
            $new_data_id = $this->db->insert_id();
            
            $query = "UPDATE property_data SET property_id = " . $new_id . " WHERE id = " . $new_data_id;
            $this->db->query($query);
        }
        
        $this->db->select('*');
        $this->db->from('property_images');
        $this->db->where('PropertyID', $current_property);  
        $query = $this->db->get();
        $data = $query->result();
        
        foreach($data as $info)
        {
            $query = 'INSERT INTO property_images (`Name`)
                      SELECT `Name`
                      FROM property_images WHERE property_images.PropertyImageID = ' . $info->PropertyImageID;
            $this->db->query($query);
            $new_photo_id = $this->db->insert_id();
            
            $query = "UPDATE property_images SET PropertyID = " . $new_id . " WHERE PropertyImageID = " . $new_photo_id;
            $this->db->query($query);
        }
        
        return $new_id;
    }
    
    function check_property($propertyID, $userID, $type = 'contact')
    {
        $this->db->select('*');
        $this->db->from('properties');
        $this->db->where('PropertyID', $propertyID);
        
        if($type != 'fsbo')
            $this->db->where('UserID', $userID);
        
        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            return $query->row();
        } else {
            return FALSE;
        }
    }
    
    function update_extra_data($extra_id, $data)
    {
        $this->db->where('id', $extra_id);
        $this->db->update('property_data', $data);
        return;
    }
    
    function delete_extra_data($extra_id)
    {
        $this->db->where('id', $extra_id);
        $this->db->delete('property_data');
        return;
    }
    
    function remove_extara_data_for_property($propertyID)
    {
        $this->db->where('property_id', $propertyID);
        $this->db->delete('property_data');
    }
    
    function get_properties_to_delete()
    {
        $date = date(PHP_ISO_DATE_HOUR_MINUTE_SECOND_FORMAT,strtotime('-3 MONTH', strtotime(date(PHP_ISO_DATE_HOUR_MINUTE_SECOND_FORMAT, time()))));
        $this->db->select('*');
        $this->db->from('properties');
        $this->db->where('Added <= ', $date);
        $this->db->where('(is_expired = 1 OR is_jl = 1 OR is_js = 1)');
        
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) 
            return $query->result();
        else
            return FALSE;
    }
    
    function check_session_follow_up($property_id)
    {
        $this->db->select('*');
        $this->db->from('properties p');
        $this->db->join('follow_ups f_u', 'f_u.PropertyID = p.PropertyID');
        $this->db->where('p.PropertyID', $property_id);
        
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE;
        
    }
    
    public function get_properties($lead_type = 'followups', $userID, $params=array(), $for_grid = true) 
	{	
        $this->lead_type = $lead_type;
                
        if($lead_type == 'followups')
            $lead_type_id = 6;
        elseif ($lead_type == 'fsbo')
            $lead_type_id = 2;
        elseif ($lead_type == 'expireds')
            $lead_type_id = 1;
        elseif ($lead_type == 'nod')
            $lead_type_id = 3;
        elseif ($lead_type == 'jljs')
            $lead_type_id = 4;
        else
            $lead_type_id = 6;
            
            
        $this->db->select('SQL_CALC_FOUND_ROWS DISTINCT(p.PropertyID), p.*, CONCAT(IF(`p`.`fname` is null, "", `p`.`fname`), " ", IF(`p`.`lname` is null, "", `p`.`lname`)) as Name', FALSE);
        $this->db->select('f_p.folder_id as folder_id');
        
        
        if($lead_type == 'followups')
        {
            $this->db->select('f_u.SetTo');
        }
        
        $this->db->from('properties p');
        
        if($lead_type == 'followups')
        {
            $this->db->join('follow_ups f_u', 'f_u.PropertyID = p.PropertyID');
        }
        
        if(isset($params['folder']))
            $this->db->join('folder_properties f_p', 'p.PropertyID = f_p.property_id and f_p.folder_id = ' . $params['folder']);
        else
            $this->db->join('folder_properties f_p', 'p.PropertyID = f_p.property_id', 'LEFT');

        if(isset($params['category']))
            $this->db->where('p.category', $params['category']);
            
        if(isset($params['relation']))
            $this->db->where('p.relation', $params['relation']);
            
        if($this->lead_type == 'fsbo' || $this->lead_type == 'expireds' || $this->lead_type == 'nod' || $this->lead_type == 'jljs')
        {        
            if(isset($params['selected_date']))
            {
                $day_before = date(PHP_ISO_DATE_HOUR_MINUTE_SECOND_FORMAT, strtotime($params['selected_date']));
                $day_after = date(PHP_ISO_DATE_HOUR_MINUTE_SECOND_FORMAT, strtotime('+1 DAY', strtotime($params['selected_date'])));
                
                $this->db->where('p.post_date >=', $day_before);
                $this->db->where('p.post_date <', $day_after);
            }
            else
            {
                $day_before = date(PHP_ISO_DATE_HOUR_MINUTE_SECOND_FORMAT, strtotime(date(PHP_ISO_DATE_FORMAT, time())));
                $day_after = date(PHP_ISO_DATE_HOUR_MINUTE_SECOND_FORMAT, strtotime('+1 DAY', strtotime(date(PHP_ISO_DATE_FORMAT, time()))));
                $this->db->where('p.post_date >= ', $day_before);
                $this->db->where('p.post_date < ', $day_after);
            }
        }     
        
        //$this->db->where('p.LeadTypeID', $lead_type_id);
        
        if ($lead_type != 'fsbo')
            $this->db->where('p.UserID', $userID);
        else
            $this->db->where('p.fsbo_parent_id', 0);

        $this->db->select('fc.name as city_name, fs.state_id as state_abv');
        
        $this->db->join('fsbo_cities fc', 'fc.id = p.fsbo_city_id', 'LEFT');
        $this->db->join('fsbo_states fs', 'fs.id = fc.state_id', 'LEFT');
        
        //$this->db->where('fsbo_parent_id >', 0);
        //$city_arr = $this->get_fsbo_city_for_user($userID);
        if ($lead_type == 'fsbo')
        {                        
            $this->db->where('(p.fsbo_city_id IN (SELECT city_id FROM fsbo_user2city WHERE user_id = ' . $userID . '))');
            $this->db->where('(p.PropertyID NOT IN (SELECT property_id FROM fsbo_deleted_property WHERE user_id = ' . $userID . '))');
        }            
        
            
        $this->db->where('p.is_tmp', 0);
        
        if(isset($params['gird_debug']))
            $this->db->select('p1.sd');

            
        if ($lead_type == 'fsbo')
            $this->db->where('p.is_fsbo', 1);
        elseif ($lead_type == 'expireds')
            $this->db->where('p.is_expired', 1);
        elseif ($lead_type == 'nod')
            $this->db->where('p.is_nod', 1);
        elseif ($lead_type == 'jljs')
        {
            if($this->session->userdata('s_current_jljs_tab'))
            {
                $this->db->where('p.is_' . $this->session->userdata('s_current_jljs_tab'), 1); 
            }
            else
                $this->db->where('p.is_jl', 1); 
        }  
        elseif ($lead_type == 'followups')
            $this->db->where('(p.is_fsbo = 1 OR p.is_expired = 1 OR p.is_nod = 1 OR p.is_jl = 1 OR p.is_js = 1 OR (p.is_contact = 1 OR p.fsbo_move_to_contact = 1))');   
        elseif($lead_type != 'followups')
            $this->db->where('((p.is_contact = 1 OR p.fsbo_move_to_contact = 1))');
        
            
        
        if((!isset($params['sortname']) || !isset($params['sortorder'])) && $lead_type != 'followups')
            $this->db->order_by('p.post_date');
            
        $this->db->group_by('p.PropertyID');
    
    
            //$this->flexigrid->build_query();
            //$this->my_build_query($params['sortname'], $params['sortorder']);
     
        if($lead_type == 'followups')
            $this->db->order_by('f_u.SetTo');
        else
        {
            if(isset($params['sortname']) && isset($params['sortorder']))
                $this->my_build_query($params['sortname'], $params['sortorder'], $for_grid);
        }
     
            
            
        $query = $this->db->get();
                
        $result = $query->result();
        $cnt = $this->db->query('SELECT FOUND_ROWS() as rowCount')->row_array();
        //Get contents
        $return['records'] = $result;        
        $return['record_count'] = $cnt['rowCount'];
        
        return $return;
	}
    
    function get_fsbo_city_for_user($user_id)
    {
        $this->db->select('*');  
        $this->db->from('fsbo_user2city');  
        $this->db->where('user_id', $user_id);  
        
        $query = $this->db->get();     
        
        $result = $query->result();
        
        $arr = array();
        foreach($result as $city)
        {
            $arr[] = $city->city_id;
        }
        
        return $arr;
    }
    
    function get_user_edit_data($ids, $user_id)
    {
        $this->db->select('p.*, CONCAT(IF(`p`.`fname` is null, "", `p`.`fname`), " ", IF(`p`.`lname` is null, "", `p`.`lname`)) as Name', FALSE);
        $this->db->select('f_p.folder_id as folder_id');  
        $this->db->select('fc.name as city_name, fs.state_id as state_abv');
            
            
        $this->db->from('properties p');
        
        $this->db->join('folder_properties f_p', 'p.PropertyID = f_p.property_id', 'LEFT');
        $this->db->join('fsbo_cities fc', 'fc.id = p.fsbo_city_id', 'LEFT');
        $this->db->join('fsbo_states fs', 'fs.id = fc.state_id', 'LEFT');
        
        if(count($ids) > 0)
            $this->db->where_in('fsbo_parent_id', $ids);
            
        $this->db->where('UserID', $user_id);
        
        $query = $this->db->get();     
        
        $result = $query->result();
        return $result;
    }
    
    function my_build_query($sortname='post_date', $sortorder = 'asc', $with_limit=true)
    {
        //var_dump($this->input->post('sortname'));die;
        $rp = $this->input->post('rp');
        $page = $this->input->post('page');
        $query = $this->input->post('query');
        $qtype = $this->input->post('qtype');
        /*$sortname = $this->input->post('sortname');
        $sortorder = $this->input->post('sortorder');*/
        
        $limitstart = (($page-1) * $rp);
        
        if($sortorder != 'desc')
            $sortorder = 'asc';
        
        if($this->lead_type == 'followups')
            $default_column = 'f_u.SetTo';
        else
            $default_column = 'p.post_date';
        
        switch ($sortname)
        {
            case 'name':
                $this->db->order_by('Name', $sortorder);
                break;
            case 'phone':
                $this->db->order_by('p.grid_phone', $sortorder);
                break;
            case 'email':
                $this->db->order_by('p.grid_email', $sortorder);
                break;
            case 'im':
                $this->db->order_by('p.grid_im', $sortorder);
                break;
            case 'address':
                $this->db->order_by('p.grid_address', $sortorder);
                break;
            case 'city':
                $this->db->order_by('p.grid_city', $sortorder);
                break;
            case 'state':
                $this->db->order_by('p.grid_state', $sortorder);
                break;
            case 'zip':
                $this->db->order_by('p.grid_zip', $sortorder);
                break;
            case 'relationship':
                $this->db->join('field_types f_t', 'p.relation = f_t.id', 'LEFT');
                $this->db->order_by('f_t.value', $sortorder);
                break;
            case 'category':
                $this->db->join('field_types f_t', 'p.category = f_t.id', 'LEFT');
                $this->db->order_by('f_t.value', $sortorder);
                break;
            case 'folder':
                $this->db->join('folder_properties folder_p', 'p.PropertyID = folder_p.property_id', 'LEFT');
                $this->db->join('user_folders folder', 'folder_p.folder_id = folder.id', 'LEFT');
                $this->db->order_by('folder.name', $sortorder);
                break;
            case 'motivation':
                $this->db->order_by('p.motivation', $sortorder);
                break;
            case 'Address':
                $this->db->order_by('p.Address', $sortorder);
                break;
            case 'ZIP':
                $this->db->order_by('p.ZIP', $sortorder);
                break;
            case 'Phone':
                $this->db->order_by('p.Phone', $sortorder);
                break;
            case 'City':
                $this->db->order_by('p.City', $sortorder);
                break;
            case 'ListPrice':
                $this->db->order_by('p.ListPrice', $sortorder);
                break;
            case 'ListDate':
                $this->db->order_by('p.ListDate', $sortorder);
                break;
            case 'SoldPrice':
                $this->db->order_by('p.SoldPrice', $sortorder);
                break;
            case 'MLSNr':
                $this->db->order_by('p.MLSNr', $sortorder);
                break;
           
            case 'Status':
                $this->db->order_by('p.Status', $sortorder);
                break;
            case 'Restrictions':
                $this->db->order_by('p.Restrictions', $sortorder);
                break;
            case 'TaxRecordsName':
                $this->db->order_by('p.TaxRecordsName', $sortorder);
                break;
            case 'DNC':
                $this->db->order_by('p.DNC', $sortorder);
                break;
            case 'Source':
                $this->db->order_by('p.Source', $sortorder);
                break;
            case 'YearBuilt':
                $this->db->order_by('p.YearBuilt', $sortorder);
                break;
            case 'BuildingSize':
                $this->db->order_by('p.BuildingSize', $sortorder);
                break;
            case 'ParcelNumber':
                $this->db->order_by('p.ParcelNumber', $sortorder);
                break;    
            case 'CloseEscrow':
                $this->db->order_by('p.CloseEscrow', $sortorder);
                break;         
            case 'status':
                $this->db->order_by('p.status_property', $sortorder);
                break;  
            case 'home_price':
                $this->db->order_by('p.buy_price', $sortorder);
                break;
            case 'post_date':
                $this->db->order_by('p.post_date', $sortorder);
                break;
            case 'f_date':
                $this->db->order_by('f_u.SetTo', $sortorder);
                break;
            case 'is_favorite':
                
                if($sortorder == 'asc')
                    $sortorder = 'desc';
                else
                    $sortorder = 'asc';
                    
                $this->db->order_by('p.is_favourite', $sortorder);
                break;  
            default :
                 $this->db->order_by($default_column, $sortorder);
                break;
        }
        
        if($this->lead_type == 'followups')
            $this->db->order_by('f_u.SetTo');
        
        $this->check_search_params($query, $qtype); 
        
        if($with_limit)
            $this->db->limit($this->input->post('rp'), $limitstart);         
            
    }    
            
    function check_search_params($query, $qtype)
    {
        if ($query == FALSE || $qtype == FALSE)
			return FALSE;
		else
		{
		    $query = trim($query);
            
			if ($query != "" && $qtype != "")
			{
				switch ($qtype)
                {
                    case 'name':
                        $this->db->where('(CONCAT(IF(`p`.`fname` is null, "", `p`.`fname`), " ", IF(`p`.`lname` is null, "", `p`.`lname`)) LIKE \'%' . $query . '%\' OR CONCAT(IF(`p`.`lname` is null, "", `p`.`fname`), " ", IF(`p`.`fname` is null, "", `p`.`fname`)) LIKE \'%' . $query . '%\')'); 
                        break;
                    case 'phone':
                        $this->db->join('property_data pr_data', 'p.PropertyID = pr_data.property_id AND pr_data.define = \'phone\' AND pr_data.value LIKE \'%' . $query . '%\'');
                        break;
                    case 'email':
                        $this->db->join('property_data pr_data', 'p.PropertyID = pr_data.property_id AND pr_data.define = \'email\' AND pr_data.value LIKE \'%' . $query . '%\'');
                        break;
                    case 'im':
                        $this->db->join('property_data pr_data', 'p.PropertyID = pr_data.property_id AND pr_data.define = \'im\' AND pr_data.value LIKE \'%' . $query . '%\'');
                        break;
                    case 'address':
                        $this->db->join('property_data pr_data', 'p.PropertyID = pr_data.property_id AND pr_data.define = \'address\' AND pr_data.value LIKE \'%' . $query . '%\'');
                    case 'Address':
                        $this->db->like('p.Address', $query);
                        break;
                    case 'ZIP':
                        $this->db->like('p.ZIP', $query);
                        break;
                    case 'Phone':
                        $this->db->like('p.Phone', $query);
                        break;
                    case 'city':
                        $this->db->join('property_data pr_data', 'p.PropertyID = pr_data.property_id AND pr_data.define = \'address\' AND pr_data.city LIKE \'%' . $query . '%\'');
                        break;
                    case 'state':
                        $this->db->join('property_data pr_data', 'p.PropertyID = pr_data.property_id AND pr_data.define = \'address\' AND pr_data.state LIKE \'%' . $query . '%\'');
                        break;
                    case 'zip':
                        $this->db->join('property_data pr_data', 'p.PropertyID = pr_data.property_id AND pr_data.define = \'address\' AND pr_data.zip LIKE \'%' . $query . '%\'');
                        break;
                    case 'relationship':
                        $this->db->join('field_types f_t', 'p.relation = f_t.id AND f_t.value LIKE \'%' . $query . '%\'');
                        
                        break;
                    case 'category':
                        $this->db->join('field_types f_t', 'p.category = f_t.id AND f_t.value LIKE \'%' . $query . '%\'');
                        
                        break;
                    case 'folder':
                        $this->db->join('folder_properties folder_p', 'p.PropertyID = folder_p.property_id');
                        $this->db->join('user_folders folder', 'folder_p.folder_id = folder.id AND folder.name LIKE \'%' . $query . '%\'');
                       
                        break;
                    case 'motivation':
                        $this->db->like('p.motivation', $query);
                        break;
                    case 'status':
                        $this->db->like('p.status_property', $query);
                        break;  
                    case 'home_price':
                        $this->db->like('p.buy_price', $query);
                        break;
                    case 'post_date':
                        $date = date('Y-m-d', strtotime($query));
                        $this->db->where('p.post_date', $date);
                        break;
                    case 'f_date':
                        $date = date('Y-m-d', strtotime($query));
                        $this->db->where('f_u.SetTo', $date);
                        break;
                    default :
                        $this->db->join('property_data pr_data', 'p.PropertyID = pr_data.property_id', 'LEFT');
                        $this->db->where('((CONCAT(IF(`p`.`fname` is null, "", `p`.`fname`), " ", IF(`p`.`lname` is null, "", `p`.`lname`)) LIKE \'%' . $query . '%\' OR CONCAT(IF(`p`.`lname` is null, "", `p`.`fname`), " ", IF(`p`.`fname` is null, "", `p`.`fname`)) LIKE \'%' . $query . '%\') OR (pr_data.value LIKE \'%' . $query . '%\' OR pr_data.value_date LIKE \'%' . $query . '%\' OR pr_data.street LIKE \'%' . $query . '%\' OR pr_data.state LIKE \'%' . $query . '%\' OR pr_data.zip LIKE \'%' . $query . '%\' OR (p.Address LIKE \'%' . $query . '%\') OR (p.Phone LIKE \'%' . $query . '%\') OR (p.ZIP LIKE \'%' . $query . '%\') OR (p.City LIKE \'%' . $query . '%\') OR (p.Neighborhood LIKE \'%' . $query . '%\') OR (p.MLSNr LIKE \'%' . $query . '%\') ))');
                        break; 
 			    }
        	}
            return FALSE;
        }      
    }        
    
    function set_simple_search_conditions($searchstr, $searchby)
    {
        if ($searchstr == FALSE || $searchby == FALSE)
		{
			return FALSE;
		}
		else
		{
			if (trim($searchstr) != "" && $searchby != "")
			{
				$searchstr_split = explode(" ",$searchstr);
				$searchstr_final = "";
				
				foreach ($searchstr_split as $key => $value) 
				{
					if (trim($value) != "")
						if ($key == 0)
							$searchstr_final .= $searchby.' LIKE "%'.$value.'%"';
						else
							$searchstr_final .= ' OR '.$searchby.' LIKE "%'.$value.'%"';
				}
				
				return $searchstr_final;
			}
		}
		return FALSE;
    }
    
    
    
    function get_property_extra_data($propertyID)
    {
        $this->db->select('property_data.*,states.name as state_name, field_types.value as type_value, field_types.is_general, field_types.id as typeID');
        $this->db->from('property_data');
        $this->db->join('states', 'states.state_id = property_data.state', 'LEFT');
        $this->db->join('field_types', 'field_types.id = property_data.type_id', 'LEFT');
        $this->db->where('property_id', $propertyID);
        $this->db->where('is_show', 1);

        $query = $this->db->get(); 
        
        $result = $query->result();
        return $result;
    }
    

    function getUserContacts($userID, $order='order_column', $params=array())
    {
        $this->db->select('IF( p.lname is null OR p.lname = "", p.fname, CONCAT(IF(`p`.`fname` is null, "", `p`.`fname`), " ", IF(`p`.`lname` is null, "", `p`.`lname`))) as Name, IF(p.lname is null OR p.lname = "", p.fname, p.lname) as order_column, IF(p.lname is null OR p.lname = "", p.fname, p.lname) as sort_name,
`p`.`lname`, p.PropertyID, p.is_favourite', FALSE);
        $this->db->from('properties p');
        $this->db->where('UserID', $userID);

        if(isset($params['define']) && $params['define'] == 'field')
        {
            if($params['value'] != '')
            {
                preg_match('!([^\s]+)\s*(.*)!', $params['value'], $matches);
                if(count($matches) == 3 && $matches[2] != '')
                {
                    $this->db->where('(`p`.`lname` like "%' . $matches[1] . '%" or `p`.`fname` like "%' . $matches[2] . '%" OR `p`.`lname` like "%' . $matches[2] . '%" or `p`.`fname` like "%' . $matches[1] . '%")');
                }
                else
                    $this->db->where('(`p`.`lname` like "%' . $params['value'] . '%" or `p`.`fname` like "%' . $params['value'] . '%")');
            }     
            if(isset($params['category']))
                $this->db->where('p.category', $params['category']);
        }
            
        
        if(isset($params['define']) && $params['define'] == 'category')
        {
            if($params['category'] > 0)
                $this->db->where('p.category', $params['category']);
                
            if(isset($params['search_text']))
            {
                preg_match('!([^\s]+)\s*(.*)!', $params['search_text'], $matches);
                if(count($matches) == 3)
                {
                    $this->db->where('(`p`.`lname` like "%' . $matches[1] . '%" or `p`.`fname` like "%' . $matches[2] . '%" OR `p`.`lname` like "%' . $matches[2] . '%" or `p`.`fname` like "%' . $matches[1] . '%")');
                }
                else
                    $this->db->where('(`p`.`lname` like "%' . $params['value'] . '%" or `p`.`fname` like "%' . $params['value'] . '%")');
                    
            }
                
        }
        
        if(isset($params['define']) && $params['define'] == 'fav')
            $this->db->where('p.is_favourite', 1);
        
        $this->db->where('(p.is_fsbo = 0 OR p.fsbo_move_to_contact = 1)');
        $this->db->where('p.is_tmp', 0);
        $this->db->order_by($order);
        
        $query = $this->db->get(); 
        
        $result = $query->result();
        return $result;
    }
    
    function get_property_by_id($propertyID, $userID, $type = 'contact', $with_user = true)
    {            
        $this->db->select('p.*, CONCAT(IF(`p`.`fname` is null, "", `p`.`fname`), " ", IF(`p`.`lname` is null, "", `p`.`lname`)) as Name', FALSE);   
        
        if($type == 'expireds' || $type == 'followups' || $type == 'contacts')
        {
            $this->db->select('
                a.Name as AgentName,
    			a.Broker as AgentBroker,
    			a.Phone as AgentPhone,
    			a.BrokerOfficePhone as AgentOfficePhone,
    			a.AdditionalPhone as AgentAdditionalPhone,
    			a.Fax as AgentFax,
    
    			a2.Name as SecondAgentName,
    			a2.Broker as SecondAgentBroker,
    			a2.Phone as SecondAgentPhone,
    			a2.BrokerOfficePhone as SecondAgentOfficePhone,
    			a2.AdditionalPhone as SecondAgentAdditionalPhone,
    			a2.Fax as SecondAgentFax,');   
        
        }
        
        $this->db->from('properties p');
        
        if($type == 'expireds' || $type == 'followups' || $type == 'contacts')
        {
            $this->db->join('agents a','a.AgentID=p.FirstAgentID','left');
            $this->db->join('agents a2','a2.AgentID=p.SecondAgentID','left');
        }
        
        $this->db->where('p.PropertyID', $propertyID);
        
        if($type != 'fsbo' && $with_user)
            $this->db->where('p.UserID', $userID);

        $this->db->select('fc.name as city_name, fs.state_id as state_abv');
        
        $this->db->join('fsbo_cities fc', 'fc.id = p.fsbo_city_id', 'LEFT');
        $this->db->join('fsbo_states fs', 'fs.id = fc.state_id', 'LEFT');
        
        //$this->db->where('fsbo_parent_id', 0);
        //$city_arr = $this->get_fsbo_city_for_user($userID);
        if ($type == 'fsbo')
        {                        
            $this->db->where('(p.fsbo_city_id IN (SELECT city_id FROM fsbo_user2city WHERE user_id = ' . $userID . '))');
            $this->db->where('(p.PropertyID NOT IN (SELECT property_id FROM fsbo_deleted_property WHERE user_id = ' . $userID . '))');
        }            
        
        if ($type == 'fsbo')
            $this->db->where('p.is_fsbo', 1);
        elseif ($type == 'expireds')
            $this->db->where('p.is_expired', 1);
        elseif ($type == 'nod')
            $this->db->where('p.is_nod', 1);
        elseif ($type == 'jljs')
        {
            if($this->session->userdata('s_current_jljs_tab'))
            {
                $this->db->where('p.is_' . $this->session->userdata('s_current_jljs_tab'), 1); 
            }
            else
                $this->db->where('p.is_jl', 1); 
        }
        elseif ($type == 'followups')
            $this->db->where('(p.is_fsbo = 1 OR p.is_expired = 1 OR p.is_nod = 1 OR p.is_jl = 1 OR p.is_js = 1 OR (p.is_contact = 1 OR p.fsbo_move_to_contact = 1))');
        else
            $this->db->where('((p.is_contact = 1 OR p.fsbo_move_to_contact = 1))');
            
        $query = $this->db->get();     
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE;
    }
    
    function is_property_has_appt($property_id)
    {
        $this->db->select('*');             
        $this->db->from('properties p');
        $this->db->join('set_appointments appt', 'p.PropertyID = appt.property_id');
        
        $this->db->where('p.PropertyID', $property_id);
        $query = $this->db->get();     
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE;
    }
    
    function get_field_types($userID)
    {
        $this->db->select('*');        
        $this->db->from('field_types');
        
        $this->db->where('is_general', 1);
        $this->db->or_where('user_id', $userID);
        $this->db->order_by('is_general', 'DESC');
        $this->db->order_by('id');
        
        $query = $this->db->get();     
        
        $result = $query->result();
        return $result;
   
    }
    
    function get_user_fields_for_type($user_id, $type)
    {
        $this->db->select('*');        
        $this->db->from('field_types');
        
        $this->db->where('type', $type);
        $this->db->where('(user_id = ' . $user_id . ' OR is_general=1)');
        $this->db->order_by('is_general', 'DESC');
        $this->db->order_by('id');
        
        $query = $this->db->get();     
        
        $result = $query->result();
        return $result;
    }
    
    function check_field_type($userID, $typeID)
    {
        $this->db->select('*');        
        $this->db->from('field_types');
        
        $this->db->where('id', $typeID);
        $this->db->where('(is_general = 1 OR user_id = ' . $userID . ')');
        
        $query = $this->db->get();     
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE;
    }
    
    function check_custom_field($value, $userID, $type)
    {
        $this->db->select('*');        
        $this->db->from('field_types');
        
        $this->db->where('value', $value);
        $this->db->where('type', $type);
        $this->db->where('(is_general = 1 OR user_id = ' . $userID . ')');
        
        $query = $this->db->get();     
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE;
    }
    
    function user_custom_fields($userID, $type)
    {
        $this->db->select('*');        
        $this->db->from('field_types');
        $this->db->where('user_id', $userID);
        $this->db->where('type', $type);
        
        $query = $this->db->get(); 
        return $query->num_rows(); 
    }
    
    function get_type_info($typeID, $userID)
    {
        $this->db->select('*');        
        $this->db->from('field_types');
        $this->db->where('user_id', $userID);
        $this->db->where('id', $typeID);
        
        $query = $this->db->get();     
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE; 
 
    }
    
    function get_type_info_by_id($typeID)
    {
        $this->db->select('*');        
        $this->db->from('field_types');

        $this->db->where('id', $typeID);
        
        $query = $this->db->get();     
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE; 
 
    }
    
    function check_save_possibility($extraID, $value)
    {
        $this->db->select('*');
        $this->db->from('property_data p');
        $this->db->join('field_types f', 'p.type_id = f.id');
        $this->db->where('p.id', $extraID);  
        
        $query = $this->db->get();     
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE; 
    }
    
    function insert_custom_type($data)
    {
        $this->db->insert('field_types', $data);
        return $this->db->insert_id();
    }
    
    function update_custom_type($typeID, $data)
    {
        $this->db->where('id', $typeID); 
        $this->db->update('field_types', $data);
        return ;
    }
    
    function remove_type_info($typeID)
    {
        $this->db->where('id', $typeID); 
        $this->db->delete('field_types');
        return ;
    }
    
    function update_extra_data_type($typeID, $data)
    {
        $this->db->where('type_id', $typeID); 
        $this->db->update('property_data', $data);
        return ;
    }
    
    function update_property_relation($typeID)
    {
        $this->db->where('relation', $typeID); 
        $this->db->update('properties', array('relation' => 0));
        return ;
    }
    
    function update_property_category($typeID)
    {
        $this->db->where('category', $typeID); 
        $this->db->update('properties', array('category' => 0));
        return ;
    }
    
    function check_equal_contact($propertyID, $where, $userID)
    {
        $this->db->select('*');
        $this->db->from('property_data p');
        $this->db->join('properties pr', 'p.property_id = pr.PropertyID');
        $this->db->where('p.property_id <>', $propertyID);  
        $this->db->where('(' . $where . ')');  
        $this->db->where('pr.userID', $userID);  
        $this->db->where('pr.is_tmp', 0);  
        $this->db->where('((pr.is_fsbo = 0 OR pr.fsbo_move_to_contact = 1))');  
        
        $query = $this->db->get();     
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE;
    }
    
    function get_property_appointment($status_id, $property_id)
    {
        $this->db->select('set_appointments.*');
        $this->db->from('lead_statuses');
        $this->db->join('set_appointments', 'lead_statuses.set_appointment_id = set_appointments.id');
        $this->db->where('lead_statuses.PropertyID', $property_id);  
        $this->db->where('lead_statuses.LeadStatusID', $status_id);  

        $query = $this->db->get();     
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE;
    }
    
    function get_status_info($status_id, $user_id)
    {
        $this->db->select('*');
        $this->db->from('lead_statuses');
        $this->db->where('lead_statuses.LeadStatusID', $status_id);  
        $this->db->where('lead_statuses.UserID', $user_id); 
        
        $query = $this->db->get();     
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE;
    }
    
    function remove_appointment($propertyID)
    {
        $this->db->where('property_id', $propertyID); 
        $this->db->delete('set_appointments');
        return ;
    }
    
    function check_same_appt($data, $appt_id)
    {
        $this->db->select('*');
        $this->db->from('set_appointments');
        $this->db->where('property_id', $data['property_id']);  
        $this->db->where('date', $data['date']);  
        $this->db->where('hour', $data['hour']);  
        $this->db->where('min', $data['min']);  
        $this->db->where('format', $data['format']);  
        $this->db->where('cancel', 0);  
        $this->db->where('id <>', $appt_id);  

        $query = $this->db->get();     
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE;
    }
    
    function get_last_appt($property_id)
    {
        $this->db->select('set_appointments.*, lead_statuses.LeadStatusID');
        $this->db->from('set_appointments');
        $this->db->join('lead_statuses', 'lead_statuses.set_appointment_id = set_appointments.id and lead_statuses.PropertyID=' . $property_id);
        $this->db->where('set_appointments.property_id', $property_id);
        
        $query = $this->db->get();  
        
        return $query->last_row();
    }
    
    function get_appt($appt_id, $property_id)
    {
        $this->db->select('set_appointments.*, properties.category');
        $this->db->from('set_appointments');
        $this->db->join('properties', 'properties.PropertyID = set_appointments.property_id');
        $this->db->where('id', $appt_id);
        $this->db->where('property_id', $property_id);
        
        $query = $this->db->get();     
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE;    
    }
    
    function check_user_appt($appt_id, $user_id)
    {
        $this->db->select('set_appointments.*, properties.category');
        $this->db->from('set_appointments');
        $this->db->join('properties', 'properties.PropertyID = set_appointments.property_id AND properties.UserID = ' . $user_id );
        $this->db->where('id', $appt_id);

        $query = $this->db->get();     
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE;    
    }
    
    
    function get_answers_by_question($question_id, $user_id)
    {
        $this->db->select('a.*');
        $this->db->from('set_appt_answer a');
        $this->db->where('question_id', $question_id);   
        $this->db->where('(user_id = ' . $user_id . ' OR user_id IS NULL)');   
        $this->db->order_by('a.user_id');
        
        $query = $this->db->get();
            
        return $query->result();
    }
    
    function get_question_by_category($category)
    {
        $this->db->select('*');
        $this->db->from('set_appt_question');
        $this->db->where('category_id', $category);

        $query = $this->db->get();     
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE; 
    }
    
    function AddUserAnswer($userID,$answerName,$questionID)
	{
		if (!$this->answerExists($userID,$answerName,$questionID))
		{
			$this->db->insert('set_appt_answer', array('user_id'=>$userID, 'answer'=>$answerName, 'question_id'=>$questionID));
            $id = $this->db->insert_id();
		}
        else
            $id = 0;
            
        return $id;
	}
    
    function answerExists($userID,$answerName,$questionID)
    {
        $this->db->select('*');
        $this->db->from('set_appt_answer');
        $this->db->where('user_id', $userID);
        $this->db->where('answer', $answerName);
        $this->db->where('question_id', $questionID);
        
        $query = $this->db->get();     
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE; 
    }
    
    function AddUserAnswerExp($userID,$answerName)
	{
		if (!$this->expAnswerExists($userID,$answerName))
		{
			$this->db->insert('set_appt_exp_answer', array('user_id'=>$userID, 'answer'=>$answerName, 'category_id'=>16));
            $id = $this->db->insert_id();
		}
        else
            $id = 0;
            
        return $id;
	}
    
    function expAnswerExists($userID,$answerName)
    {
        $this->db->select('*');
        $this->db->from('set_appt_exp_answer');
        $this->db->where('user_id', $userID);
        $this->db->where('answer', $answerName);
        
        $query = $this->db->get();     
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE; 
    }
    
    function check_custom_user_answer($userID, $id)
    {
        $this->db->select('*');
        $this->db->from('set_appt_answer');
        $this->db->where('user_id', $userID);
        $this->db->where('id', $id);

        $query = $this->db->get();     
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE; 
    }
    
    function check_custom_user_exp_answer($userID, $id)
    {
        $this->db->select('*');
        $this->db->from('set_appt_exp_answer');
        $this->db->where('user_id', $userID);
        $this->db->where('id', $id);

        $query = $this->db->get();     
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE; 
    }
    
    function check_answer($userID, $id)
    {
        $this->db->select('*');
        $this->db->from('set_appt_answer');
        $this->db->where('id', $id);
        $this->db->where('(user_id = ' . $userID . ' OR user_id IS NULL)');
        
        $query = $this->db->get();     
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE; 
    }
    
    function remove_custom_answer($id)
    {
        $this->db->where('id', $id);
        $this->db->delete('set_appt_answer');
        return ;
    }
    
    function remove_custom_exp_answer($id)
    {
        $this->db->where('id', $id);
        $this->db->delete('set_appt_exp_answer');
        return ;
    }
    
    function get_user_expired_appointments($user_id)
    {
        $this->db->select('appt.*, p.PropertyID');
        $this->db->from('set_appointments appt');
        $this->db->join('properties p', 'p.PropertyID = appt.property_id and p.category IN (12,13,14,15,16)');
        $this->db->where('p.UserID', $user_id);
        $this->db->where('appt.expired_time < ', 'NOW()', false);
        $this->db->where('is_show', 1);
        
        $query = $this->db->get();     

        return $query->result();
    }
    
    function get_exp_appts($user_id)
    {
        $this->db->select('appt.*, p.PropertyID');
        $this->db->from('set_appointments appt');
        $this->db->join('properties p', 'p.PropertyID = appt.property_id and p.category IN (12,13,14,15,16)');
        $this->db->where('p.UserID', $user_id);
        $this->db->where('appt.expired_time < ', 'NOW()', false);
        $this->db->where('is_show', 1);
        
        $query = $this->db->get();     

        return $query->result();
    }
    
    function update_user_exp_appointments($user_id)
    {        
        $query = 'UPDATE set_appointments as appt 
        JOIN properties as p ON p.PropertyID = appt.property_id and p.category IN (12,13,14,15,16) AND p.userID = ' . $user_id . ' 
        SET appt.answer_exp_id = 2
        WHERE appt.expired_time < NOW() AND appt.is_show = 1 AND appt.answer_exp_id = 0';
        
        $this->db->query($query);
        return;  
    }
    
    function get_questions_expired($category_id, $user_id)
    {
        $this->db->select('*');
        $this->db->from('set_appt_exp_answer');
        
        if($category_id == 12 || $category_id == 13 || $category_id == 14 || $category_id == 15)
            $this->db->where('category_id', $category_id);
        else    
            $this->db->where('category_id', -1);
        
        $this->db->or_where('general_answer', 1);
        $this->db->order_by('general_answer, id');
        $query = $this->db->get();     

        return $query->result();
    }
    
    function update_appointment_by_answer($answer_id, $data)
    {
        $this->db->where('answer_id', $answer_id); 
        $this->db->update('set_appointments', $data);
        return ;
    }
    
    function update_appointment_by_exp_answer($answer_id, $data)
    {
        $this->db->where('answer_exp_id', $answer_id); 
        $this->db->update('set_appointments', $data);
        return ;
    }
    
    function update_appointment($appt_id, $data)
    {
        $this->db->where('id', $appt_id); 
        $this->db->update('set_appointments', $data);
        return ;
    }
    
    function update_appointment_add_one_week($appt_id)
    {
        $this->db->set('expired_time', 'expired_time + INTERVAL 1 WEEK',false);
        $this->db->where('id', $appt_id);
        $this->db->update('set_appointments');
    }
    
    function check_answer_exp_appt($answer_exp_id, $user_id)
    {
        $this->db->select('*');
        $this->db->from('set_appt_exp_answer');
        $this->db->where('id', $answer_exp_id);
        $this->db->where('(user_id = ' . $user_id . ' OR user_id IS NULL)');
        
        $query = $this->db->get();     
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE; 
    }
    
    function get_user_folders($user_id, $type = 'contact')
    {
        $this->db->select('user_folders.*');
        
        if($type == 'followups')        
            $this->db->select('count(f_u.PropertyID) as count_properties');
        else
            $this->db->select('count(folder_properties.id) as count_properties');
                                                            
        $this->db->from('user_folders');
    
        $this->db->join('folder_properties', 'user_folders.id = folder_properties.folder_id', 'LEFT');
        
        if($type == 'followups')
             $this->db->join('follow_ups f_u', 'f_u.PropertyID = folder_properties.property_id', 'LEFT');
             
        $this->db->where('user_folders.user_id', $user_id);
        $this->db->group_by('user_folders.id');
        
        $query = $this->db->get();     

        return $query->result();
    }
    
    function check_folder($folder_id,$user_id)
    {
        $this->db->select('*');
        $this->db->from('user_folders');
        $this->db->where('user_id', $user_id);
        $this->db->where('id', $folder_id);
        
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE; 
    }
    
    function check_equal_folder($name,$user_id,$folder_id = 0)
    {
        $this->db->select('*');
        $this->db->from('user_folders');
        $this->db->where('user_id', $user_id);
        $this->db->where('name', $name);
        if($folder_id > 0)
             $this->db->where('id <>', $folder_id);
        
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE; 
    }
    
    function get_folder_properties($folder_id)
    {
        $this->db->select('*');
        $this->db->from('folder_properties');
        $this->db->where('folder_id', $folder_id);
        
        $query = $this->db->get();     

        return $query->result();
    }
    
    function update_folder($folder_id,$data)
    {
        $this->db->where('id', $folder_id);
        $this->db->update('user_folders', $data);
        return;
    }
    
    function insert_folder($data)
    {
        $this->db->insert('user_folders', $data);
        return $this->db->insert_id();
    }
    
    function remove_properties_from_folder($folder_id, $user_id)
    {
        $query = 'DELETE pr from properties as pr
                JOIN folder_properties as folder ON folder.property_id = pr.PropertyID AND pr.userID = ' . $user_id . '
                WHERE folder.folder_id = ' . $folder_id . '';
        $this->db->query($query);
        
        $this->db->where('folder_id', $folder_id);
        $this->db->delete('folder_properties');
        
        return;
    }
    
    function remove_folder($folder_id)
    {
        $this->db->where('id', $folder_id);
        $this->db->delete('user_folders');
        return;
    }
    
    function insert_property_into_folder($data)
    {
        $this->db->insert('folder_properties', $data);
        return $this->db->insert_id();
    }
    
    function remove_property_from_folders($property_id)
    {
        $this->db->where('property_id', $property_id);
        $this->db->delete('folder_properties');
        return;
    }
    
    function check_document_by_field_count($user_id, $doc_field_count, $define = 'contact')
    {
        $this->db->select('*');
        $this->db->from('import_meta_data');
        $this->db->where('user_id', $user_id);
        $this->db->where('doc_field_count', $doc_field_count);
        $this->db->where('doc_define', $define);
        
        $query = $this->db->get();     

        return $query->result();
    }
    
    function get_import_metadata($uploaded_doc_id, $user_id, $define = 'contact')
    {
        $this->db->select('*');
        $this->db->from('import_meta_data');
        $this->db->where('user_id', $user_id);
        $this->db->where('id', $uploaded_doc_id);
        $this->db->where('doc_define', $define);
        
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE;
    }
    
    function insert_import_metadata($data)
    {
        $this->db->insert('import_meta_data', $data);
        return $this->db->insert_id();
    }
    
    function update_import_meta_data($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update('import_meta_data', $data);
        return;
    }
    
    function get_all_properties()
    {
        $this->db->select('*');
        $this->db->from('properties');
        
        $query = $this->db->get();
        return $query->result();
    }
    
    function remove_follow_up($property_id, $user_id)
    {
        $this->db->where('PropertyID', $property_id);
        $this->db->where('UserID', $user_id);
        $this->db->delete('follow_ups');
        return;
    }
    
    function get_leads_types()
    {
        $this->db->select('p.*, l.Name as lead_name');
        $this->db->from('lead_type_price p');
        $this->db->join('lead_types l', 'p.lead_type_id = l.LeadTypeID');
        
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) 
            return $query->result();
        else
            return FALSE;
    }
    
    function get_allowed_price()
    {
        $this->db->select('*');
        $this->db->from('config');
        $this->db->where('id', 1);
        
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE;
    }
    
    function update_config_value($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update('config', $data);
        return;
    }
    
    function get_sum_by_leads_types($leads = array(), $duration = 'month')
    {
        if($duration == 'month')
            $sum_column = 'month_price';
        else
            $sum_column = 'year_price';
            
        $this->db->select('SUM(p.' . $sum_column . ') as sum');
        $this->db->from('lead_type_price p');
        $this->db->where_in('p.lead_type_id', $leads);
        
        $query = $this->db->get();

        return $query->row()->sum;
    }
    
    function get_combine_price()
    {
        $this->db->select('*');
        $this->db->from('combine_price');
        
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) 
            return $query->result();
        else
            return FALSE;
    }
    
    function get_combine_price_by_count($count)
    {
        $this->db->select('*');
        $this->db->from('combine_price');
        $this->db->where('count_leads', $count);
        
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) 
            return $query->row();
        else
            return FALSE;
    }
    
    function insert_combine_price($data)
    {
        $this->db->insert('combine_price', $data);
        return $this->db->insert_id();
    }
    
    function delete_combine_price($id)
    {
        $this->db->where('id', $id);
        $this->db->delete('combine_price');
        return;
    }
    
    function update_lead_type_price($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update('lead_type_price', $data);
        return;
    }
    
    function truncate_combine_price()
    {
        $this->db->truncate('combine_price');
        return;
    }
    
    function get_broker_coefficients()
    {
        $this->db->select('*');
        $this->db->from('broker_coefficients');
        
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) 
            return $query->result();
        else
            return FALSE;
    }
    
    function update_coefficients($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update('broker_coefficients', $data);
        return;
    } 
    
}
?>
