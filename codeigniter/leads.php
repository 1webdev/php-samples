<?php
/**
 * @property Model_Property $model_property
 * @property Model_Statuses $model_statuses
 */
class Leads extends Controller
{
	public static $MAX_OWNERDATA_NR_OF_CHARS = 20;
	public static $PROPERTY_INFO_FIELDS_BY_TYPE = array
	(
		'contacts'	=>	array
						(
							'FIELDS'		=>	array('MLSNr','ParcelNumber','ListPrice','OriginalPrice','Restrictions','Status','PreviousStatus','OffMarketDate','ListDate','ActiveMarketTime','NrOfBedsPerBaths','BuildingSize','LotSize','Style','YearBuilt','Neighborhood','AgentName','AgentBroker','AgentPhone','AgentOfficePhone'),
							'SEPARATORS'	=>	array(5=>'small',8=>'medium',14=>'small<strong></strong>')
						),
		'fsbo'		=>	array
						(
							'FIELDS'		=>	array(),
							'SEPARATORS'	=>	array()
						),
		'nod'		=>	array
						(
							'FIELDS'		=>	array('SmartSearch', 'NODTaxID', 'NODRecordingDate','FilingDate','NODDefaultAmount','NODOriginalMortgageAmount','NrOfBedsPerBaths','BuildingSize','LotSize','YearBuilt'),
							'SEPARATORS'	=>	array(0=>'small',2=>'small',4=>'small')
						),

		'jljs'		=>	array
						(
							'FIELDS'		=>	array('SmartSearch', 'MLSNr','Restrictions','NrOfBedsPerBaths','BuildingSize','LotSize','Style','YearBuilt','Neighborhood'),
							'SEPARATORS'	=>	array(1=>'big')
						),
		'followups'	=>	array
						(
							'FIELDS'		=>	array('SmartSearch', 'MLSNr','ParcelNumber','ListPrice','OriginalPrice','Restrictions','Status','PreviousStatus','OffMarketDate','ListDate','ActiveMarketTime','NrOfBedsPerBaths','BuildingSize','LotSize','Style','YearBuilt','Neighborhood','AgentName','AgentBroker','AgentPhone','AgentOfficePhone'),
							'SEPARATORS'	=>	array(5=>'small',8=>'medium',14=>'small')
						)
	);
    
    public static $alphabet = array ("A","B","C","D","E","F","G","H","I","J","K","L","M",
"N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
    
	public static $LEAD_LIST_FIELDS_BY_TYPE = array
	(
		              'fsbo'	=>	array
						(
                            'smart_search'					=> array('WIDTH'=>30,'VISIBILITY'=>1),
                            'check_box_all'                 => array('WIDTH'=>10,'VISIBILITY'=>0),
                            'is_favorite'                   => array('WIDTH'=>10,'VISIBILITY'=>0),
							'address'						=> array('WIDTH'=>175,'VISIBILITY'=>0),
                            'city'							=> array('WIDTH'=>126,'VISIBILITY'=>0),
                            'state'						=> array('WIDTH'=>41,'VISIBILITY'=>0),
                            'zip'							=> array('WIDTH'=>52,'VISIBILITY'=>0),
                            'name'                          => array('WIDTH'=>103,'VISIBILITY'=>1),
                            //'address'                       => array('WIDTH'=>151,'VISIBILITY'=>0),
                            'home_price'                    => array('WIDTH'=>103,'VISIBILITY'=>0),
                            'email'                         => array('WIDTH'=>152,'VISIBILITY'=>0),
                            'phone'                         => array('WIDTH'=>95,'VISIBILITY'=>0),
                            'delete'                        => array('WIDTH'=>30,'VISIBILITY'=>0),	
						),
                        
                       'expireds'	=>	array
						(
                            'smart_search'					=> array('WIDTH'=>30,'VISIBILITY'=>1),
                            'check_box_all'                 => array('WIDTH'=>10,'VISIBILITY'=>0),
                            'is_favorite'                   => array('WIDTH'=>10,'VISIBILITY'=>0),
							'MLSNr'							=> array('WIDTH'=>80,'VISIBILITY'=>0),
                            'Address'						=> array('WIDTH'=>206,'VISIBILITY'=>0),
                            'City'							=> array('WIDTH'=>126,'VISIBILITY'=>0),
                            'StateID'						=> array('WIDTH'=>41,'VISIBILITY'=>0),
                            'ZIP'							=> array('WIDTH'=>52,'VISIBILITY'=>0),
                            'ListPrice'					    => array('WIDTH'=>63,'VISIBILITY'=>0),
                            'phone'							=> array('WIDTH'=>99,'VISIBILITY'=>0),
                            //'Restrictions'					=> array('WIDTH'=>63,'VISIBILITY'=>0),
                            'Status'						=> array('WIDTH'=>65,'VISIBILITY'=>0),
							'Occupancy'						=> array('WIDTH'=>56,'VISIBILITY'=>1),
							/*'Restrictions'					=> array('WIDTH'=>63,'VISIBILITY'=>0),
							'TaxRecordsName'				=> array('WIDTH'=>182,'VISIBILITY'=>0),
							'DNC'							=> array('WIDTH'=>20,'VISIBILITY'=>0),
							'phone'							=> array('WIDTH'=>68,'VISIBILITY'=>0),
							'email'							=> array('WIDTH'=>65,'VISIBILITY'=>1),
							
							'City'							=> array('WIDTH'=>96,'VISIBILITY'=>0),
							'ZIP'							=> array('WIDTH'=>32,'VISIBILITY'=>0),
							'Occupancy'						=> array('WIDTH'=>56,'VISIBILITY'=>1),
							//'OffMarketDate'				=> array('WIDTH'=>30,'VISIBILITY'=>1),
							'ListPrice'					=> array('WIDTH'=>54,'VISIBILITY'=>0),
							'Source'						=> array('WIDTH'=>30,'VISIBILITY'=>1),
							'YearBuilt'					=> array('WIDTH'=>30,'VISIBILITY'=>1),
							'Style'							=> array('WIDTH'=>30,'VISIBILITY'=>1),*/
							'delete'						=> array('WIDTH'=>30,'VISIBILITY'=>0)
						), 
                        
                        'nod'		=>	array
						(
                            'smart_search' => array('WIDTH'=>30,'VISIBILITY'=>1),
                            'check_box_all' => array('WIDTH'=>10,'VISIBILITY'=>0),
                            'is_favorite'                   => array('WIDTH'=>10,'VISIBILITY'=>0),
							'TaxRecordsName'				=> array('WIDTH'=>222,'VISIBILITY'=>0),
							'Address'						=> array('WIDTH'=>176,'VISIBILITY'=>0),
							'UnitDesignator'				=> array('WIDTH'=>30,'VISIBILITY'=>1),
							'UnitNumber'					=> array('WIDTH'=>30,'VISIBILITY'=>1),
							'City'							=> array('WIDTH'=>80,'VISIBILITY'=>0),
							'StateID'						=> array('WIDTH'=>30,'VISIBILITY'=>1),
							'phone'							=> array('WIDTH'=>71,'VISIBILITY'=>0),
							'zip'							=> array('WIDTH'=>40,'VISIBILITY'=>0),
							'TaxRecordsMailAddress'			=> array('WIDTH'=>30,'VISIBILITY'=>1),
							'NODRecordingDate'				=> array('WIDTH'=>84,'VISIBILITY'=>0),
							'NODFilingDate'					=> array('WIDTH'=>30,'VISIBILITY'=>1),
							'NODDefaultAmount'				=> array('WIDTH'=>30,'VISIBILITY'=>1),
							'NODOriginalMortgageAmount'		=> array('WIDTH'=>89,'VISIBILITY'=>0),
							'NrOfBeds'						=> array('WIDTH'=>30,'VISIBILITY'=>1),
							'NrOfBaths'						=> array('WIDTH'=>30,'VISIBILITY'=>1),
							'BuildingSize'					=> array('WIDTH'=>30,'VISIBILITY'=>1),
							'LotSize'						=> array('WIDTH'=>30,'VISIBILITY'=>1),
							'YearBuilt'						=> array('WIDTH'=>35,'VISIBILITY'=>1),
							'delete'						=> array('WIDTH'=>30,'VISIBILITY'=>0)
						),

                        'jljs'		=>	array
                        (
                            'smart_search'					=> array('WIDTH'=>30,'VISIBILITY'=>1),
                            'check_box_all'                 => array('WIDTH'=>10,'VISIBILITY'=>0),
                            'is_favorite'                   => array('WIDTH'=>10,'VISIBILITY'=>0),
							'MLSNr'							=> array('WIDTH'=>80,'VISIBILITY'=>0),
                            'Address'						=> array('WIDTH'=>206,'VISIBILITY'=>0),
                            'City'							=> array('WIDTH'=>126,'VISIBILITY'=>0),
                            'StateID'						=> array('WIDTH'=>41,'VISIBILITY'=>0),
                            'ZIP'							=> array('WIDTH'=>52,'VISIBILITY'=>0),
                            'BuildingSize'					=> array('WIDTH'=>56,'VISIBILITY'=>0),
                            'ParcelNumber'					=> array('WIDTH'=>97,'VISIBILITY'=>0),
                            'ListPrice'					    => array('WIDTH'=>74,'VISIBILITY'=>0),
                            'SoldPrice'						=> array('WIDTH'=>74,'VISIBILITY'=>0),
                            'delete'						=> array('WIDTH'=>30,'VISIBILITY'=>0)
                        ),
                        
                      'followups'	=>	array
						(
                            'smart_search' => array('WIDTH'=>30,'VISIBILITY'=>1),
                            'check_box_all' => array('WIDTH'=>10,'VISIBILITY'=>0),
                            'is_favorite' => array('WIDTH'=>10,'VISIBILITY'=>0),
                            'f_date' => array('WIDTH'=>40,'VISIBILITY'=>0),
                            'name' => array('WIDTH'=>116,'VISIBILITY'=>0),
							'phone' => array('WIDTH'=>76,'VISIBILITY'=>0),
							'email' => array('WIDTH'=>88,'VISIBILITY'=>0),
							'im' => array('WIDTH'=>40,'VISIBILITY'=>1),
							'address' => array('WIDTH'=>150,'VISIBILITY'=>0),
							'city' => array('WIDTH'=>40,'VISIBILITY'=>1),
							'state' => array('WIDTH'=>40,'VISIBILITY'=>1),
							'zip' => array('WIDTH'=>31,'VISIBILITY'=>0),
							'relationship' => array('WIDTH'=>64,'VISIBILITY'=>0),
                            'category' => array('WIDTH'=>40,'VISIBILITY'=>1),
                            'folder' => array('WIDTH'=>40,'VISIBILITY'=>1),
							'status' => array('WIDTH'=>50,'VISIBILITY'=>0),
							'motivation' => array('WIDTH'=>32,'VISIBILITY'=>0),
							'home_price' => array('WIDTH'=>60,'VISIBILITY'=>0),
							'post_date' => array('WIDTH'=>40,'VISIBILITY'=>1),
							'delete' => array('WIDTH'=>31,'VISIBILITY'=>0),	
						),
                        'contacts'	=>	array
						(
                            'smart_search' => array('WIDTH'=>30,'VISIBILITY'=>1),
                            'check_box_all' => array('WIDTH'=>10,'VISIBILITY'=>0),
                            'is_favorite' => array('WIDTH'=>10,'VISIBILITY'=>0),
                            'name' => array('WIDTH'=>109,'VISIBILITY'=>0),
							'phone' => array('WIDTH'=>76,'VISIBILITY'=>0),
							'email' => array('WIDTH'=>110,'VISIBILITY'=>0),
							'im' => array('WIDTH'=>75,'VISIBILITY'=>1),
							'address' => array('WIDTH'=>139,'VISIBILITY'=>0),
							'city' => array('WIDTH'=>68,'VISIBILITY'=>1),
							'state' => array('WIDTH'=>40,'VISIBILITY'=>1),
							'zip' => array('WIDTH'=>31,'VISIBILITY'=>0),
							'relationship' => array('WIDTH'=>63,'VISIBILITY'=>0),
                            'category' => array('WIDTH'=>47,'VISIBILITY'=>0),
                            'folder' => array('WIDTH'=>40,'VISIBILITY'=>1),
							'status' => array('WIDTH'=>54,'VISIBILITY'=>0),
							'motivation' => array('WIDTH'=>31,'VISIBILITY'=>0),
							'home_price' => array('WIDTH'=>54,'VISIBILITY'=>1),
							'post_date' => array('WIDTH'=>47,'VISIBILITY'=>0),
							'delete' => array('WIDTH'=>31,'VISIBILITY'=>0),	
						)
            /*'followups'	=>	array
						(
							'SmartSearch'					=> array('WIDTH'=>30,'VISIBILITY'=>1),
                            'FollowUpDate'					=> array('WIDTH'=>30,'VISIBILITY'=>1),
							'Type'							=> array('WIDTH'=>38,'VISIBILITY'=>0),
							'Status'						=> array('WIDTH'=>55,'VISIBILITY'=>1),
							'MLSNr'							=> array('WIDTH'=>50,'VISIBILITY'=>0),
							'TaxRecordsName'				=> array('WIDTH'=>122,'VISIBILITY'=>0),
							'DNC'							=> array('WIDTH'=>20,'VISIBILITY'=>1),
							'Phone'							=> array('WIDTH'=>74,'VISIBILITY'=>0),
							'Email'							=> array('WIDTH'=>146,'VISIBILITY'=>0),
							'Address'						=> array('WIDTH'=>162,'VISIBILITY'=>0),
							'City'							=> array('WIDTH'=>74,'VISIBILITY'=>0),
							'ZIP'							=> array('WIDTH'=>31,'VISIBILITY'=>0),
							'Occupancy'						=> array('WIDTH'=>50,'VISIBILITY'=>1),
							'OffMarketDate'					=> array('WIDTH'=>62,'VISIBILITY'=>1),
							'ListPrice'						=> array('WIDTH'=>46,'VISIBILITY'=>0),
							'Restrictions'					=> array('WIDTH'=>65,'VISIBILITY'=>1),
							'YearBuilt'						=> array('WIDTH'=>35,'VISIBILITY'=>1),
							'Style'							=> array('WIDTH'=>35,'VISIBILITY'=>1),
							'Delete'						=> array('WIDTH'=>30,'VISIBILITY'=>0)
						)       */     
                        
	);

	var $propertyPages = array("expireds","fsbo","nod","jljs","followups","contacts");
    //var $propertyPages = array("fsbo","followups","contacts");
	var $propertyInfoPages = array("info","photos","map","history","neighbors","adinfo", "ad");
	var $leadGridFields = array('Status','DNC','TaxRecordsName','Email','Phone','Address','City','ZIP','Occupancy','MLSNr','OffMarketDate','ListPrice','Restrictions','Source','YearBuilt','Style');
	//var $followUpGridFields = array('FollowUpDate','Type');
	var $additionalLeadGridFields = array('Delete');
	var $additionalAdminLeadGridFields = array('Neighbors','Edit');
	var $additionalGridStatusFields = array('grid_height','grid_sort','grid_sort_order');
	var $propertyID = 0;
	var $followUpID = 0;
	var $neighborID = -1;
	var $propertyType = "contacts";
	var $pageNr = 1;
	var $pageNrNeighbors = 1;
	var $propertyInfoType = "info";
	var $mainView = "leads/view_leads";
	var $mainContentBoxClass = "maincontentbox_contact";
	var $selectedDate = null;
	var $selected_date = null;
	var $userID;
	var $searchFilter;
	var $searchLimit;
	var $adminMode;
	var $followUpMode;
	var $flexigridOptions = array();
	var $leadGridStateColumnMetaSuffix = "";
    var $new_sort_fields = array();

	/**
	 * Flexigrid options
	 */
	var $rp;
	var	$sortname;
	var	$sortorder;
	var	$limitstart;
	var	$page;
	var $qtype;
	var $query;

	/**
	 * Array which contains the data that is sent to the view
	 *
	 * @var array
	 *
	 */
	var $data = array();

	function Leads()
	{
		parent::Controller();

		if (IS_AJAX_REQUEST)
		{
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
			$this->output->cache(0);
		}

		$this->load->helper('url');
		$this->load->helper('form');
		$this->load->helper('flexigrid');

		$this->load->library('session');
		$this->load->library('flexigrid');
		$this->load->library('form_validation');
		//Redirect if not logged in
		
        $method = $this->uri->segment(2);
        
        if (!$this->redux_auth->logged_in() && $method != 'ajax_show_change_membership_popup')
			$this->model_utils->redirectToLoginPage();
   	
        $this->_setDefaultFieldValues();
		$this->_setFieldValues();
        
		$this->_checkRequest();
		$this->_validateGridPostData();
		$this->_setLeadGridStateColumnMetaSuffix();
        
        if($method != 'ajax_add_touch_for_contact' && $method != 'buildGrid_new')
        {
            //echo $this->propertyID;
            //echo $this->propertyID;
        }
        
        

	}

	/* REGION MAIN_CONTROLLER_FUNCTIONS */
	function _remap($propertyType,$propertyID=-1)
	{     
        $this->data['r_propertyType'] = $propertyType;
    	//if main page
		if (in_array($propertyType,$this->propertyPages))
			$this->property($propertyType,$propertyID);
		// if other page (e.g. AJAX requests)
		else
			$this->$propertyType();
	}
	/* END_REGION MAIN_CONTROLLER_FUNCTIONS */

	function property($propertyType=null,$propertyID=-1,$loadView=true)
	{   
	    if($propertyType == 'expireds' || $propertyType == 'jljs' || $propertyType == 'fsbo')
        {
            if($propertyType == 'expireds' && !$this->redux_auth->access_to_expired())
            {
                if($this->redux_auth->access_to_fsbo())
                    redirect('/leads/fsbo');
                elseif($this->redux_auth->access_to_jljs())
                    redirect('/leads/jljs');
                else
                    redirect('/leads/contacts');
            }
            elseif($propertyType == 'fsbo' && !$this->redux_auth->access_to_fsbo())
            {
                if($this->redux_auth->access_to_expired())
                    redirect('/leads/expireds');
                elseif($this->redux_auth->access_to_jljs())
                    redirect('/leads/jljs');
                else
                    redirect('/leads/contacts');
            }
            elseif($propertyType == 'jljs' && !$this->redux_auth->access_to_jljs())
            {
                if($this->redux_auth->access_to_expired())
                    redirect('/leads/expireds');
                elseif($this->redux_auth->access_to_fsbo())
                    redirect('/leads/fsbo');
                else
                    redirect('/leads/contacts');
            }                
        }
       
        $this->_showWelcomeOrStatsPopup();
        /*if($this->session->userdata('s_login'))
            $this->set_exp_date();
        $this->session->unset_userdata('s_login')*/
		//$this->_setDefaultValues();
		if($propertyType == 'fsbo' && !$this->redux_auth->access_to_fsbo())
            $this->model_utils->redirectToLoginPage();
        elseif($propertyType == 'expireds' && !$this->redux_auth->access_to_expired())
            $this->model_utils->redirectToLoginPage();
        elseif($propertyType == 'jljs' && !$this->redux_auth->access_to_jljs())
            $this->model_utils->redirectToLoginPage();
        elseif($propertyType == 'nod' && !$this->redux_auth->access_to_nod())
            $this->model_utils->redirectToLoginPage();    
            
        if ($propertyType==null)
			$this->model_utils->redirectToLoginPage();

		if ($propertyType=="followups")
			$this->propertyInfoType = "history";
        elseif ($propertyType=="contacts")
			$this->propertyInfoType = "history";
        elseif ($propertyType=="fsbo")
			$this->propertyInfoType = "adinfo";
        elseif ($propertyType=="expireds")
			$this->propertyInfoType = "expiredinfo";
        elseif ($propertyType=="nod")
			$this->propertyInfoType = "nodinfo";
		elseif ($propertyType=="jljs")
			$this->propertyInfoType = "jljsinfo";


		$this->propertyType = $propertyType;
        $this->_setLeadGridStateColumnMetaSuffix();
                
		$this->_validateGridPostData();

        $sessionValue = $this->session->userdata('s_propertyID');

        $check_property = $this->model_property->check_property_into_group($sessionValue, $this->userID, $propertyType);
       
        if($sessionValue && $sessionValue > 0 && $check_property && $propertyType != 'followups' && $propertyType != 'fsbo' && $propertyType != 'expireds' && $propertyType != 'nod' && $propertyType != 'jljs')
        {
            $this->propertyID = $sessionValue;
        }  
        else
        {
            
            $params = $this->get_sort_params();
            
            $this->propertyID = intval($this->model_property->GetFirstPropertyID($propertyType,$this->userID,$this->selectedDate,$this->searchFilter,$this->adminMode,$this->followUpMode,$this->flexigridOptions,$params));

            if($this->propertyType == 'fsbo')
            {
                $dublicate = $this->model_property->check_dublicate_row($this->propertyID, $this->userID);
                if($dublicate)
                    $this->propertyID = $dublicate->PropertyID;
            }
            
            if($this->propertyType == 'contacts')
                $this->session->set_userdata('s_propertyID', $this->propertyID);
        }
        
		$this->_setOwnerData();
      
        $this->data['update_status'] = 0;
        
		$this->_setPropertyInfoData();
		$this->_setLeadsListData();
  
		$this->_setPropertyData();
		$this->_setDataToSession();
        $this->_setUserContacts();
        
		$this->data['SEARCH_NAME'] = $this->model_user->GetUserMetaItem($this->userID,META_SELECTED_SEARCH_NAME,META_GROUP_LEAD_GRID_SEARCH);
		$this->data['SELECT_FIRST_ROW'] = true;

		$welcomeMessageShown = $this->session->userdata('s_welcomeMessageShown');
		if (empty($welcomeMessageShown))
		{
			$this->data['WELCOME_DATA'] = $this->model_user->GetSpentTimeData($this->userID);
			$this->session->set_userdata('s_welcomeMessageShown','1');
		}

		$this->_setMainViewData($this->mainView,$this->mainContentBoxClass,$loadView);
	}

    function _setUserContacts()
    {   
        $category = $this->model_user->GetUserInfoFieldByUserID($this->userID, 'category_search_option');

        if($category > 0)
        {
            $params = array('define' => 'category', 'category' => $category);
            $user_contacts = $this->model_property->getUserContacts($this->userID, 'order_column', $params);
        }  
        else
            $user_contacts = $this->model_property->getUserContacts($this->userID);
        
        $user_categories = $this->model_property->get_user_fields_for_type($this->userID, 'category');
        
        $this->_setOwnerData();
        
        $this->data['user_categories'] = $user_categories;
        $this->data['user_contacts'] = $user_contacts;
        $this->data['alphabet'] = self::$alphabet;
        $this->data['category_main'] = $category;
    }

	function _showWelcomeOrStatsPopup()
	{
		$firstVisit = $this->session->userdata('s_firstVisit');

		if ($firstVisit==false)
		{
			$firstVisit = $this->model_user->GetUserInfoFieldsByUserID($this->userID,array('first_visit'));
			$firstVisit = $firstVisit['first_visit'];

			$this->model_user->updateUserInfoByUserID($this->userID,array("first_visit"=>0));
			$this->session->set_userdata('s_firstVisit','0');

		}
        
		if ($firstVisit=='1')
		{
			$this->data['SHOW_WELCOME_POPUP'] = true;
			$this->session->set_userdata('s_showStatsPopup','0');
		}
		else
		{
			$showStatsPopup = $this->session->userdata('s_showStatsPopup');
            $this->data['SHOW_EXP_APPT'] = true;

			if (($showStatsPopup===false || $showStatsPopup==="1") && $this->_statsPopupDataExists())
				$this->data['SHOW_STATS_POPUP'] = true;

			$this->session->set_userdata('s_showStatsPopup','0');
		}
	}

	public function followups()
	{
		$this->property("followups",-1,false);
		$this->data['PAGE'] = "followups";
		$this->data['SHOW_ADD_NEW_LEAD'] = true;
		$this->data['SHOW_SELECTED_DATE'] = false;
		$this->data['SHOW_SELECT_DATE_ARROW'] = false;
		$this->data['FOLLOWUP_MODE'] = true;
		$this->load->view('leads/view_leads_properties',$this->data);
	}

	/* REGION AJAX_REQUESTS */
	/**
	 * AJAX Request for Owner Info
	 *
	 * @return void
	 *
	 */
	function getOwnerInfo()
	{
		$this->_setPropertyData();
		$this->_setOwnerData();
		$this->_setDataToSession();

        if($this->propertyType != 'fsbo' && $this->propertyType != 'expireds' && $this->propertyType != 'nod' && $this->propertyType != 'jljs')
            $this->load->view('leads/view_leads_ownerinfo_content_info', $this->data);
        else
            $this->load->view('leads/view_leads_ownerinfo_content_info_' . $this->propertyType, $this->data);
	}
    
    function get_owner_icons()
    {        
        if($this->propertyType == 'fsbo' && $this->propertyID > 0)
        {
            $dublicate = $this->model_property->check_dublicate_row($this->propertyID, $this->userID);
            if($dublicate)
                $this->propertyID = $dublicate->PropertyID;
            
            $output['result'] = '<a onclick="createNewLeadPopup();" href="javascript:void(0);"><img src="/images/contact_info/btn+.png"></a>

<a onclick="editContact(\'' . $this->propertyID . '\');" href="javascript:void(0);"><img src="/images/contact_info/edit-icon.png"></a>
<a href="javascript:void(0);"><img id="pid" src="/images/contact_info/delete-icon.png"></a><input type="hidden" name="pid" value="' . $this->propertyID . '"/>';

        }
        else
            $output['result'] = '<a onclick="createNewLeadPopup();" href="javascript:void(0);"><img src="/images/contact_info/btn+.png"></a>';
            
        return $this->output->set_output($this->json->encode($output));
    }
    
	function getLeadsList()
	{
		$this->_setPropertyData();
		$this->_setLeadsListData();
		$this->_setDataToSession();
        
        if($this->propertyType != 'fsbo' && $this->propertyType != 'expireds' && $this->propertyType != 'nod' && $this->propertyType != 'jljs')  
            $this->load->view('leads/view_leads_list',$this->data);
        else
            $this->load->view('leads/view_leads_list_fsbo',$this->data);
    
	}

	function getPropertyInfo()
	{ 
        if($this->input->post('propertyID') && $this->input->post('propertyID') == 0)
            $this->propertyID = 0;
        
		$this->_setPropertyData();
		$this->_setPropertyInfoData();
		$this->_setDataToSession();
                
		$this->load->view('leads/view_leads_propertyinfo_'.$this->propertyInfoType,$this->data);
	}
    
    function ajax_set_tab_on_session()
    {
        $define = $this->input->post('define');
        
        if($define != 'jl')
            $define = 'js';
            
        $this->session->set_userdata('s_current_jljs_tab', $define);
    }
    
    function ajax_check_subsribtion_lead()
    {
        $define_sub = $this->input->post('define_sub');
        
        if($define_sub != 'expired' && $define_sub != 'nod' && $define_sub != 'jljs')
            $define_sub = 'fsbo';
        
        $check = $this->model_user->check_subsribtion_lead($this->userID, $define_sub);
        
        if($check)
        {
            $output['success'] = 'success';
            if($define_sub == 'expired')
                $output['redirect_to'] = '/leads/expireds';
            elseif($define_sub == 'nod')
                $output['redirect_to'] = '/leads/nod';
            elseif($define_sub == 'jljs')
                $output['redirect_to'] = '/leads/jljs';
            else
                $output['redirect_to'] = '/leads/fsbo';
        }
        else
            $output = array('success'=>'error');
            
        return $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_add_expired_to_contact()
    {
        if(!$this->redux_auth->is_fsbo_user() && !$this->redux_auth->is_broker_user())
        {
            $output = array('success'=>'error');
            return $this->output->set_output($this->json->encode($output));
        }
        $output = array('success'=>'error');
        $property_id = (int) $this->input->post('contact_id');
        
        $property_info = $this->model_property->get_property_by_id($property_id, $this->userID, $this->propertyType);
        
        if($property_info)
        {
            $data['is_contact'] = 1;
            $this->model_property->update_property($property_id, $data);
            $output = array('success'=>'success');
        }
        
        return $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_fsbo_add_to_contact()
    {
        $property_id = (int) $this->input->post('propertyID');
        
        if($property_id > 0)
            $this->propertyID = $property_id;
        
        $property_info = $this->model_property->get_property_by_id($this->propertyID, $this->userID, $this->propertyType, false);
        
        if($property_info && $property_info->is_expired == 1)
        {
            if(!$this->redux_auth->access_to_expired() || $property_info->UserID != $this->userID)
            {
                $output = array('success'=>'error');
                return $this->output->set_output($this->json->encode($output));
            }
        }
        
        if($property_info && $property_info->is_fsbo == 1)
        {
            if(!$this->redux_auth->access_to_fsbo())
            {
                $output = array('success'=>'error');
                return $this->output->set_output($this->json->encode($output));
            }
        }

        $this->model_property->update_property($this->propertyID, array('status'=>'new', 'category'=>13, 'fsbo_move_to_contact' => 1));
        
        $expired = ($property_info->is_expired == 1) ? 1 : 0;
        
        $output = array('success'=>'success', 'expired' => $expired);
        return $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_save_phone_number()
    {
        $property_id = (int) $this->input->post('contact_id');
        $value = $this->input->post('value');
        
        $property_info = $this->model_property->get_property_by_id($property_id, $this->userID, $this->propertyType);
        
        $output = array('success'=>'error');
        
        if($property_info)
        {
            if(trim($value) != '')
            {
                $data['value'] = trim($value);
                $data['property_id'] = $property_id;
                $data['define'] = 'phone';
                
                $this->model_property->insert_extra_data($data);
                $output = array('success'=>'success');
            }
        }
        
        return $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_check_fsbo_cities()
    {
        if(!$this->redux_auth->is_fsbo_user() && !$this->redux_auth->is_broker_user())
        {
            $output = array('success'=>'error');
            return $this->output->set_output($this->json->encode($output));
        }
        
        $check = $this->model_user->get_fsbo_user_cities($this->userID);
        if($check)
        {
            $output = array('success'=>'error');
            return $this->output->set_output($this->json->encode($output));
        }
        else
        {
            $output = array('success'=>'success');
            return $this->output->set_output($this->json->encode($output));
        }
    }
    
    
    function ajax_show_fsbo_in_popup()
    {
        if(!$this->redux_auth->access_to_fsbo() && !$this->redux_auth->access_to_expired())
        {
            $output = array('success'=>'error');
            return $this->output->set_output($this->json->encode($output));
        } 
        
        $property_id = (int) $this->input->post('property_id');
        
        if($property_id > 0)
            $this->propertyID = $property_id;
           
        $property_info = $this->model_property->get_property_by_id($this->propertyID, $this->userID, $this->propertyType);
        $data['property_info'] = $property_info;
        
        $photos = $this->model_property->get_images_for_contact($this->propertyID);
        if(count($photos) == 0)
            $data['PHOTOS'] = $photos;
        else
            $data['PHOTOS'] = $this->check_image_exist($photos);

        if($property_info)
        {
            $output['success'] = 'success'; 
            $output['result'] = $this->load->view('leads/popup_show_fsbo',$data, true); 
        }
        else
            $output['success'] = 'error'; 
        
        $this->output->set_output($this->json->encode($output));     
    }
    
    function ajax_edit_user_username()
    {
		$valid = false;
        
        $this->form_validation->set_rules('username', 'Username', 'trim|required|callback__username_already_exists|min_length[6]|max_length[20]|xss_clean');
		$this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[6]|max_length[20]|xss_clean');
		$this->form_validation->set_rules('repeat_password', 'Password', 'trim|required|matches[password]|min_length[6]|max_length[20]|xss_clean');
		
		$valid = $this->form_validation->run();
		
		if (!$valid)
		{
		    $output = array('success'=>'error', 'error'=>$this->form_validation->_error_array);
		}
		else
		{
        	$new_password = $this->input->post('password');
        
            $this->config->load('redux_auth');
    		$auth = $this->config->item('auth');
    		$salt = $auth['salt'];
            
            $hash = sha1(microtime()); 
    		$password_enc = sha1($salt.$hash.$new_password);
    
            $data = array(
                'hash' => $hash,
                'password' => $password_enc,
                'username' => $this->input->post('username'),
            );
            
            $this->model_user->updateUserInfoByUserID($this->userID, $data);           
            $output = array('success'=>'success');
		}
        
        $this->output->set_output($this->json->encode($output));
    }
    
    function _username_already_exists($username)
	{
	   
       $check_user_by_username = $this->model_user->check_other_user_username($username, 'username', $this->userID);
        
        if(!is_null($check_user_by_username))
        {
			$this->form_validation->set_message('_username_already_exists','Username already exists!');
			return false;
		}
		
		return true;
	
	}
    
    function ajax_update_category_relation_filter()
    {
        $types = $this->_get_field_types();
        $this->data['types'] = $types;
        $this->data['PROPERTY_TYPE'] = $this->input->post('type');
        $output['result_relation'] = $this->load->view('leads/view_leads_filter_relation_block',$this->data, true);
        $output['result_category'] = $this->load->view('leads/view_leads_filter_category_block',$this->data, true);
        
        return $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_check_equel_custom_status()
    {
        $statusName = $this->input->xss_clean($this->input->post('statusName'));
		$statusType = $this->input->xss_clean($this->input->post('statusType'));
        
        $property_info = $this->model_property->get_property_by_id($this->propertyID, $this->userID, $this->propertyType);
       
        $category = $property_info ? $property_info->category : 0;        
		
        $check = $this->model_statuses->StatusExists($statusType,$statusName,$this->userID,$category);
        
        if($check)
            $output = array('success' => 'error');
        else
            $output = array('success' => 'success');
            
        return $this->output->set_output($this->json->encode($output));    

    }
    
    function ajax_check_broker_user()
    {
        if($this->redux_auth->is_broker_user())
            $output['success'] = 'success';
        else
        {
            $user_info = $this->model_user->getUserInfoByUserID($this->userID, array('*'));
            if($user_info->parent_id > 0)
                $output['success'] = 'is_agent';
            else
                $output['success'] = 'error';
        }

        return $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_show_change_username_popup()
    {
        if($this->redux_auth->is_fsbo_user())
        {
            $this->data['user_info'] = $this->model_user->getUserInfoByUserID($this->userID, array('*'));
            $output['success'] = 'success';
            $output['result'] = $this->load->view('leads/view_change_username_popup',$this->data, true);
        }
        else
            $output['success'] = 'error';
        
        return $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_show_msg_for_broker()
    {
        $output['result'] = $this->load->view('leads/view_add_users_for_broker',$this->data, true);
        return $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_show_change_membership_popup()
    {
        $define = $this->input->post('define');
        
        switch ($define)
        {
            case 'limit_change_membership':
                
                if (!$this->redux_auth->is_rebill_limit_user())
                    return $this->model_utils->redirectToLoginPage();
                $this->data['text'] = '<span style="font-size:12px;">You have reached the contact limit for your free account. Please<br>update your account to add more contacts.</span>'; 
                $output['result'] = $this->load->view('leads/view_change_membership_limit',$this->data, true);
                break;
            case 'limit_change_membership_import':
                if (!$this->redux_auth->is_rebill_limit_user())
                    return $this->model_utils->redirectToLoginPage();
                
                $membership_info = $this->model_registration->get_membership_info_by_id(1);
                $mem_contact_limit = (int)$membership_info->mem_contact_limit;                
                
                $this->data['text'] = '<span style="font-size:12px;">The LeadBuddy Limited membership has a ' . $mem_contact_limit . ' contact limit. Your file<br /> contains more than the allowed limit. You can upgrade your member-<br />shipto import unlimited contacts or import up to ' . $mem_contact_limit . ' contacts now.</span>'; 
                $output['result'] = $this->load->view('leads/view_change_membership_limit_import',$this->data, true);
                break;
            case 'broker_user_limit':
                if (!$this->redux_auth->is_rebill_broker_user())
                    return $this->model_utils->redirectToLoginPage();
                      
                $user_count = $this->model_user->GetUserInfoFieldByUserID($this->userID, 'user_count');
                
                $broker_info = $this->model_user->getUserInfoByUserID($this->userID, array('*'));
        
                if($broker_info->access_to_fsbo == 1 || $broker_info->access_to_expired == 1 || $broker_info->access_to_jljs == 1 || $broker_info->access_to_nod == 1)
                    $broker_plan = 'plus';
                else
                    $broker_plan = 'basic';
                    
                if($broker_plan == 'plus')
                {
                    $coef1 = $broker_info_coef[1]->coef1;
                    $coef2 = $broker_info_coef[1]->coef2;
                }
                else
                {
                    $coef1 = $broker_info_coef[0]->coef1;
                    $coef2 = $broker_info_coef[0]->coef2;
                }
                                                                                            
                $price = get_price_by_user_count($user_count, $coef1, $coef2);
                
                $this->data['user_count'] = $user_count;
                $this->data['price'] = '$' . $price;
        
                $output['result'] = $this->load->view('leads/view_change_membership_broker',$this->data, true);
                break;
            case 'is_manualy_created':
                $this->data['text'] = 'Your free membership has expired. Please upgrade your<br />account to continue access.';    
                $output['result'] = $this->load->view('leads/view_change_membership_limit',$this->data, true);
                break;
            case 'pro_change_membership':
                $output['result'] = $this->load->view('leads/view_change_membership_pro',$this->data, true);
                break;
            case 'fsbo_change_membership':
                $output['result'] = $this->load->view('leads/view_change_membership_fsbo', $this->data, true);
                break;
            case 'lead_subscribe':
                $this->data['text'] = '<span style="font-size:12px;">Your membership does not include these leads. Would you like<br /> to upgrade your account to add these?</span>';
                $output['result'] = $this->load->view('leads/view_change_membership_limit',$this->data, true);
                break;
            case 'login_error_msg':
                $msg = $this->input->post('error_msg');  
                $this->data['text'] = '<span style="font-size:12px;">' . $msg . '</span>';
                $output['result'] = $this->load->view('leads/view_change_membership_limit_msg',$this->data, true);
                break;
            default:
                $this->data['text'] = '<span style="font-size:12px;">You have reached the contact limit for your free account. Please<br>update your account to add more contacts.</span>';    
                $output['result'] = $this->load->view('leads/view_change_membership_limit',$this->data, true);
                break;
        }
        
        return $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_set_page_for_current_property()
    {
        $page_limit = (int) $this->input->post('page_limit');
        $current_page = (int) $this->input->post('current_page');
        $property_id = (int) $this->input->post('propertyID');
        
        if($property_id > 0)
            $this->propertyID = $property_id;
        
        
        $_POST['rp'] = $page_limit;
        $_POST['page'] = $current_page;
        
        $current_page = $this->get_page_by_property($page_limit);
        $output['property_page'] = $current_page;
        
        return $this->output->set_output($this->json->encode($output));
        
    }
    
    function get_page_by_property($page_limit )
    {
        $params = array();
        if($this->propertyType == 'fsbo' || $this->propertyType == 'expireds' || $this->propertyType == 'jljs' || $this->propertyType == 'nod')
            $params['selected_date'] = $this->selectedDate;
        
        
        $params_add = $this->get_sort_params();

        $params['sortname'] = $params_add['sortname'];
        $params['sortorder'] = $params_add['sortorder'];
        
        $properties = $this->model_property->get_properties($this->propertyType, $this->userID, $params, false);
        $position = 0;
        $total_count = count($properties['records']);

        foreach($properties['records'] as $k=>$property)
        {
            if($property->PropertyID == $this->propertyID)
            {
                $position = $k + 1;
                break;
            }
        }
      
        $current_page = (int) ($position / $page_limit);
        
        if($position % $page_limit != 0) $current_page++;
        
        if($current_page == 0) $current_page = 1;
        
        return $current_page;
        
    }
    
    
    function ajax_get_count_items()
    {
        $type_id = (int) $this->input->post('typeID');
        $define = $this->input->post('define');

        if($define != 'category')
            $define = 'relation';
        
        $items = $this->model_property->get_count_items($this->userID, $type_id, $define);
        $output['count_items'] = count($items);
        
        return $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_set_next_action_property()
    {
        $statusID = $this->input->xss_clean('status_id');
        $appt_id = $this->input->xss_clean('appt_id');
        
        $status_info = $this->model_statuses->getStatusInfo($statusID);
        $update_status = 0;
        
        if(!is_null($status_info))
        {
            if($status_info['StatusType'] == 'attempt')
            {
                $this->model_property->update_property($this->propertyID, array('status_property'=>'follow'));
                $update_status = 1;
            }
                
        }

        if($appt = $this->model_property->check_user_appt($appt_id, $this->userID))
			$propertyID = $appt->property_id;
        else
            $propertyID = 0;

        $note = '';

		$statusDate = date(PHP_ISO_DATE_HOUR_MINUTE_SECOND_FORMAT);
        
        if($propertyID > 0)
		  $this->model_statuses->AddLeadStatus($propertyID,$this->userID,$statusID,$note,$statusDate);

		if ($statusID==TALKED_TO_OWNER_ATTEMPT_STATUS_ID) #Talked To Owner status counts as attempt and contact also
			$this->model_statuses->AddLeadStatus($propertyID,$this->userID,TALKED_TO_OWNER_CONTACT_STATUS_ID,$note,$statusDate);
            
        $output = array('success' => 'success', 'update_status' => $update_status);
        return $this->output->set_output($this->json->encode($output));
        
    }
    
	function setLeadStatusHistory()
	{	
	    $update_status = 0;
        
		if (isset($_POST['statusID']))
		{
			$statusID = $this->input->xss_clean($this->input->post('statusID'));
			$postNote = $this->input->xss_clean($this->input->post('note'));
			//$statusDate = $this->input->xss_clean($this->input->post('statusDate'));
            
            $propertyID = (int) $this->input->post('propertyID');
            $check_property = $this->model_property->check_property_into_group($propertyID, $this->userID, $this->propertyType);
            
            if($check_property)
                $this->propertyID = $propertyID;

            
            $status_info = $this->model_statuses->getStatusInfo($statusID);
            
            if(!is_null($status_info))
            {
                if($status_info['StatusType'] == 'attempt')
                {
                    $this->model_property->update_property($this->propertyID, array('status_property'=>'follow'));
                }
            }
            
			if ($postNote!="")
				$note = $postNote;
			else
				$note = "";

			if (trim($note) == trim(DEFAULT_ACTION_NOTE_TEXT))
				$note = "";

			if ($this->propertyInfoType=="history")
				$propertyID = $this->propertyID;
			else
				$propertyID = $this->neighborID;

			//if (intval($propertyID)!=-1)

			$statusDate = date(PHP_ISO_DATE_HOUR_MINUTE_SECOND_FORMAT);

			$this->model_statuses->AddLeadStatus($propertyID,$this->userID,$statusID,$note,$statusDate);

			if ($statusID==TALKED_TO_OWNER_ATTEMPT_STATUS_ID) #Talked To Owner status counts as attempt and contact also
				$this->model_statuses->AddLeadStatus($propertyID,$this->userID,TALKED_TO_OWNER_CONTACT_STATUS_ID,$note,$statusDate);
    
            $update_status = 1;

		}
		elseif (isset($_POST['leadStatusID']))
		{
			$leadStatusID = $this->input->xss_clean($this->input->post('leadStatusID'));

			#Talked To Owner status counts as attempt and contact also
			$leadStatusData = $this->model_statuses->GetLeadStatusData($leadStatusID);
			if ($leadStatusData["StatusID"]==TALKED_TO_OWNER_ATTEMPT_STATUS_ID)
				$this->model_statuses->DeleteTalkedToOwnerContactStatus($leadStatusData["Added"]);

			$this->model_statuses->DeleteLeadStatus($leadStatusID,$this->userID);
		}

		$this->getLeadStatusHistoryContent($update_status);

	}

    function setApptLeadStatusHistory()
	{	
		$statusID = 16;
        $propertyID = $this->propertyID;
        $set_appt_id = (int)$this->input->post('set_appt_id');

        
        if($set_appt_id > 0)
            $this->model_statuses->AddApptLeadStatus($propertyID,$this->userID,$statusID,$set_appt_id);
	
		$this->getLeadStatusHistoryContent();
	}

	function deleteLeadNote()
	{
		if (isset($_POST['noteID']))
		{
			$noteID = $this->input->post('noteID',true);
			$this->model_statuses->DeleteLeadNote($noteID,$this->userID);
		}

		$this->getLeadStatusHistoryContent();
	}

	function getLeadStatusHistoryContent($update_status = 0)
	{
		if ($this->propertyInfoType=="history")
			$this->_setLeadsStatusHistoryData(null);
		else
			$this->_setLeadsStatusHistoryData($this->neighborID);
            
        $this->data['update_status'] = $update_status;
        
        
		$this->load->view($this->data['VIEW_LEAD_STATUS_HISTORY'],$this->data);
	}

	function addLeadNote()
	{
		$note = $this->input->post('note',true);

		if ($note===false)
		{
			$this->output->set_output('0');
			return;
		}

		if ($this->propertyInfoType=="history")
			$propertyID = $this->propertyID;
		else
			$propertyID = $this->neighborID;

		$success = $this->model_statuses->AddLeadNote($propertyID,$this->userID,$note);

		$this->output->set_output($success==true?"1":"0");
	}

	function getLeadStatusHistory()
	{
		if ($this->propertyInfoType=="history")
			$this->_setLeadsStatusHistoryData(null);
		else
			$this->_setLeadsStatusHistoryData($this->neighborID);

		$this->load->view($this->data['VIEW_LEAD_STATUS_HISTORY'],$this->data);
	}

	function addCustomStatus()
	{
		$statusName = $this->input->xss_clean($this->input->post('statusName'));
		$statusType = $this->input->xss_clean($this->input->post('statusType'));
        
        $property_info = $this->model_property->get_property_by_id($this->propertyID, $this->userID, $this->propertyType);
       
        $category = $property_info ? $property_info->category : 0;        
		$this->model_statuses->AddUserStatus($this->userID,$statusType,$statusName,$category);

		$this->_setPropertyData();
		$this->_setLeadStatusTypesData();
		$this->_setDataToSession();

		$this->load->view($this->data['VIEW_LEADS_STATUSES'],$this->data);
	}
    
    function set_exp_date()
    {
        $exp_appts = $this->model_property->get_exp_appts($this->userID);
        
        foreach($exp_appts as $appt)
        {
            if($appt->answer_exp_id > 3 || $appt->answer_exp_id == 1)
                $this->model_property->update_appointment($appt->id, array('is_show' => 0));
            elseif($appt->answer_exp_id == 0)
            {
                $this->model_property->update_appointment($appt->id, array('answer_exp_id' => 2));
                $this->model_property->update_appointment_add_one_week($appt->id);
            }
            elseif($appt->answer_exp_id == 2)
                $this->model_property->update_appointment_add_one_week($appt->id);

        }
    }
    
    function ajax_add_touch_for_contact()
    {
        $current_id = (int) $this->input->post('current_id');
        
        if($current_id > 0)
            $this->propertyID = $current_id;

        $property_info = $this->model_property->get_property_by_id($this->propertyID, $this->userID, $this->propertyType);
        //$is_appointment = $this->model_property->is_property_has_appt($this->propertyID);
        $output['reload'] = 0;
        
        if($this->propertyType == 'fsbo')
        {
            if($property_info && $property_info->fsbo_parent_id == 0)
            {
                $new_duplicate_id = $this->model_property->create_duplicate_contact($property_info->PropertyID, $this->userID);
                $this->propertyID = $new_duplicate_id;
                                    
                $property_info = $this->model_property->get_property_by_id($this->propertyID, $this->userID);
                
                $output['reload'] = 1;
                $output['new_id'] = $new_duplicate_id;
            }
        }
        
        if($property_info)
        {
            if(is_null($property_info->date_touch) || $property_info->date_touch != date('Y-m-d'))
            {
                if(is_null($property_info->first_touch))
                    $data = array('first_touch'=>date('Y-m-d'), 'count_touches'=>($property_info->count_touches + 1), 'date_touch'=>date('Y-m-d'));
                else
                    $data = array('count_touches'=>($property_info->count_touches + 1), 'date_touch'=>date('Y-m-d')); 
                    
                $this->model_property->update_property($property_info->PropertyID, $data);
            }    
            
        }
        
        $output['success'] = 'success';
        return $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_set_follow_up()
    {
        $output['result'] = $this->load->view('leads/view_followup_exp_appt',$this->data, true);
        return $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_remove_selected_rows()
    {
        $del_items_arr = $this->input->post('del_items_arr');
        
        foreach($del_items_arr as $property_id)
        {
            $property_id = (int) $property_id;
            if($this->model_property->check_property($property_id, $this->userID, $this->propertyType))
                $this->model_property->DeletePropertyByID($property_id);
        }
        
        $output['success'] = 'success';
        return $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_remove_property()
    {
        $property_id = (int) $this->input->post('property_id');
        if($this->model_property->check_property($property_id, $this->userID, $this->propertyType))
            $this->model_property->DeletePropertyByID($property_id);
        
        $output['success'] = 'success';
        return $this->output->set_output($this->json->encode($output));
        
    }
    
    function ajax_move_to_folder()
    {
        $selected_ids = $this->input->post('selected_ids');
        $folder_id = (int) $this->input->post('folder_id');
        
        $folder_info = $this->model_property->check_folder($folder_id,$this->userID);
        
        if(count($selected_ids) > 0 && $folder_info && is_array($selected_ids))
        {
            foreach($selected_ids as $id)
            {
                $poroperty_info = $this->model_property->check_property($id, $this->userID, $this->propertyType);
                if($poroperty_info)
                {
                    $this->model_property->remove_property_from_folders($id);
                    $this->model_property->insert_property_into_folder(array('folder_id'=>$folder_id, 'property_id'=>$id));
                }
            }
            $output['success'] = 'success';
        }
        else
            $output['error'] = 'error';
            
        return $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_get_folder_properties()
    {
        $folder_id = (int) $this->input->post('folder_id');
        $folder_info = $this->model_property->check_folder($folder_id,$this->userID);
        
        if(!$folder_info)
        {
            $output = array('success' => 'error', 'error' => 'You have not permissions');
            return $this->output->set_output($this->json->encode($output));
        }
        
        $properties = $this->model_property->get_folder_properties($folder_info->id);
        
        $output['success'] = 'success';
        $output['count_properties'] = count($properties);
        return $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_delete_folder()
    {
        $folder_id = (int) $this->input->post('folder_id');
        $folder_info = $this->model_property->check_folder($folder_id,$this->userID);
        
        if(!$folder_info)
        {
            $output = array('success' => 'error', 'error' => 'You have not permissions');
            return $this->output->set_output($this->json->encode($output));
        }
        
        $this->model_property->remove_properties_from_folder($folder_id, $this->userID);
        $this->model_property->remove_folder($folder_id, $this->userID);
        
        $output = array('success' => 'success');
        return $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_get_user_folders_list()
    {
        $current_folder = (int) $this->input->post('current_folder');
        
        $folder_info = $this->model_property->check_folder($current_folder, $this->userID);
        
        $this->data['current_folder'] = $folder_info;
        $output['current_folder_id'] = $folder_info ? $folder_info->id : 0;
        
        $output['success'] = 'success';
        $this->data['folders'] = $this->model_property->get_user_folders($this->userID);
        $output['result'] = $this->load->view('leads/view_leads_contact_list_folders', $this->data, true);
        $output['result_move'] = $this->load->view('leads/view_leads_contact_move_list', $this->data, true);
        
        return $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_save_user_folders()
    {
        $folders = $this->input->post('folders');
        $current_folder = (int) $this->input->post('current_folder');
        
        $error = '';
        
        if(count($folders) > 0)
        {
            foreach($folders as $folder)
            {
                $name = trim($folder['name']);
                if($name != "")
                {
                    $folder_id = (int) $folder['id'];
                    if($folder_id > 0)
                    {
                        $folder_info = $this->model_property->check_folder($folder_id,$this->userID);
                        
                        if($folder_info && $folder_info->name != $name)
                        {
                            if(!$this->model_property->check_equal_folder($name,$this->userID,$folder_info->id))
                                $this->model_property->update_folder($folder_id,array('name'=>$name));
                            else
                            {
                                if($error == '')
                                    $error .= 'Folder ' . $name . ' already exist.';
                                else
                                    $error .= '<br />Folder ' . $name . ' already exist.';
                            }
                        }
                            
                    }
                    else
                    {
                        if(!$this->model_property->check_equal_folder($name,$this->userID))
                            $this->model_property->insert_folder(array('name'=>$name,'user_id'=>$this->userID));
                        else
                        {
                            if($error == '')
                                $error .= 'Folder ' . $name . ' already exist.';
                            else
                                $error .= '<br />Folder ' . $name . ' already exist.';
                        }
                    }
                }
            }
        }
        if($error == '')
        {
            $folder_info_cur = $this->model_property->check_folder($current_folder, $this->userID);
            $this->data['current_folder'] = $folder_info_cur;
            
            $output['current_folder_id'] = $folder_info_cur ? $folder_info_cur->id : 0;
            $output['success'] = 'success';
            $folders = $this->model_property->get_user_folders($this->userID);
            $this->data['folders'] = $folders;
            
            $output['result'] = $this->load->view('leads/view_leads_contact_list_folders', $this->data, true);
            $output['result_move'] = $this->load->view('leads/view_leads_contact_move_list', $this->data, true);
        }
        else
            $output = array('success' => 'error', 'error' => $error);
            
        return $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_show_edit_folder_popup()
    {
        $folders = $this->model_property->get_user_folders($this->userID);
        $this->data['folders'] = $folders;
        
        $output['result'] = $this->load->view('leads/view_leads_folders', $this->data, true);
        return $this->output->set_output($this->json->encode($output));
    }
    
    
    function ajax_set_exp_answer()
    {
        $appt_id = (int) $this->input->post('appt_id');
        $answer_exp_id = (int) $this->input->post('answer_exp_id');
        
        $appt_info = $this->model_property->check_user_appt($appt_id, $this->userID);
        $answer_exp_info = $this->model_property->check_answer_exp_appt($answer_exp_id, $this->userID);
        
        if(!$appt_info || !$answer_exp_info)
        {
            $output = array('success' => 'error', 'error' => 'You have not permissions');
            return $this->output->set_output($this->json->encode($output));
        }
        
        $this->model_property->update_appointment($appt_info->id, array('answer_exp_id' => $answer_exp_info->id));
        $output = array('success' => 'success');
        
        if($answer_exp_id == 5 || $answer_exp_id == 7 || $answer_exp_id == 9 || $answer_exp_id == 11)
            $output['show_second'] = 1;
        else
            $output['show_second'] = 0;
            
        return $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_set_default_exp_answer()
    {       
        $this->model_property->update_user_exp_appointments($this->userID, array('answer_exp_id' => 2));
        $output = array('success' => 'success');
        $this->set_exp_date();
        return $this->output->set_output($this->json->encode($output)); 
    }
    
    function ajax_show_expired_appointments()
    {
        $expired_appt = $this->model_property->get_user_expired_appointments($this->userID);
        
        if(count($expired_appt) == 0)
        {
            $output = array('success' => 'error', 'error' => 'You have not any expired appointments');
            return $this->output->set_output($this->json->encode($output));            
        }

        $appt_current_id = (int) $this->input->post('appt');
        
        $appt_info = $this->model_property->check_user_appt($appt_current_id, $this->userID);

        if(!$appt_info)
            $appt_current_id = 0;
        
        if($appt_current_id == 0)
            $define = 'new';
        else
            $define = 'next';
            
        if(!$appt_current_id)
            $appt_current_id = $expired_appt[0]->id;
         
        $appt_info = $this->model_property->check_user_appt($appt_current_id, $this->userID); 
          
        $current_property_id = $appt_info->property_id;
        
        $ownerData = $this->model_property->get_property_by_id($current_property_id, $this->userID, $this->propertyType);
        $extraData = $this->model_property->get_property_extra_data($current_property_id);
        $main_data = $this->get_main_data($extraData);
        
        $name = $ownerData->fname . ' ' . $ownerData->lname;        
        $address_str = '';
        $address_str .= $main_data['address'];
        if(trim($address_str) == '')
            $address_str .= $main_data['city'];
        else
            $address_str .= '<br />' . $main_data['city'];
            
        if(trim($address_str) == '')
            $address_str .= $main_data['state'];
        else
            $address_str .= ', ' . $main_data['state'];
            
        if(trim($address_str) == '')
            $address_str .= $main_data['zip'];
        else
            $address_str .= ' ' . $main_data['zip'];  
        
        $phone = $main_data['phone'];
        
        $this->data['define'] = $define;
        $this->data['name'] = $name;
        $this->data['address_str'] = $address_str;
        $this->data['phone'] = $phone;
        $this->data['appt_current_id'] = $appt_current_id;
        $this->data['current_category'] = $ownerData->category;
        $this->data['appt_info'] = $appt_info;
        
        $answers = $this->model_property->get_questions_expired($ownerData->category, $this->userID);      
        $this->data['answers'] = $answers;
                
        $next_id = 0;
        $previous_id = 0;
        $text_count = 'Appointment 1/' . count($expired_appt);
        
        foreach($expired_appt as $key => $appt)
        {
            if($appt->id == $appt_current_id)
            {
                $text_count = 'Appointment ' . ($key + 1) . '/' . count($expired_appt);
                if(isset($expired_appt[$key-1]))
                    $previous_id = $expired_appt[$key-1]->id;
                if(isset($expired_appt[$key+1]))
                    $next_id = $expired_appt[$key+1]->id;
            }
        }
        
        $this->data['next_id'] = $next_id;
        $this->data['previous_id'] = $previous_id;
        $this->data['text_count'] = $text_count;
        
        $output['success'] = 'success';
        $output['define'] = $define;
        $output['result'] = $this->load->view('leads/view_leads_appt_exp_question', $this->data, true);
        return $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_save_custom_field()
    {
        $field_value = $this->input->xss_clean($this->input->post('field_value'));
        $define = $this->input->xss_clean($this->input->post('define'));
        $appt_id = (int) $this->input->post('appt_id');
        
        if($define != 'action' && $define != 'answer_exp')
            $define = 'answer';
          
        if($define == 'action')
        {
            $property_info = $this->model_property->get_property_by_id($this->propertyID, $this->userID, $this->propertyType);
            $status_id = $this->model_statuses->AddUserStatus($this->userID,'action',$field_value,$property_info->category);
            
            if($status_id > 0)
            {
                $output['success'] = 'success';
                $output['define'] = $define;
                $html = '<li id="action_' . $status_id . '"><input type="radio" value="' . $status_id . '" name="answer2"/>' . $field_value . '&nbsp;&nbsp;<img title="Delete action" onclick="delete_custom_field(\'' . $define . '\', this)" class="pointer" src="/images/delete_custom_action.gif"></li>';
                $output['new_element'] = $html;
                
                $this->_setPropertyData();
        		$this->_setLeadStatusTypesData();
        		$this->_setDataToSession();
                
                $output['result'] = $this->load->view($this->data['VIEW_LEADS_STATUSES'], $this->data, true);
            }
            else
                $output = array('success' => 'error', 'error' => 'This action already exist.');
        }
        elseif($define == 'answer_exp')
        {
            $appt_info = $this->model_property->check_user_appt($appt_id, $this->userID);
            
            if(!$appt_info)
            {
                $output = array('success' => 'error', 'error' => 'You havn\'t permission');
                return $this->output->set_output($this->json->encode($output));
            }
            
            $answer_id = $this->model_property->AddUserAnswerExp($this->userID, $field_value);
            
            if($answer_id > 0)
            {
                $output['success'] = 'success';
                $output['define'] = $define;
                $html = '<li id="answer_' . $answer_id . '"><input  onchange="set_exp_answer(true);" type="radio" value="' . $answer_id . '" name="answer"/>' . $field_value . '&nbsp;&nbsp;<img title="Delete action" onclick="delete_custom_field(\'' . $define . '\', this)" class="pointer" src="/images/delete_custom_action.gif"></li>';
                $output['new_element'] = $html;
            }
            else
                $output = array('success' => 'error', 'error' => 'This answer already exist.');
        }
        else
        {
            $appt_info = $this->model_property->get_appt($appt_id, $this->propertyID);
            
            if(!$appt_info)
            {
                $output = array('success' => 'error', 'error' => 'You havn\'t permission');
                return $this->output->set_output($this->json->encode($output));
            }
            
            $category = $appt_info->category;
            $question = $this->model_property->get_question_by_category($category);
            
            if(!$question)
            {
                $output = array('success' => 'error', 'error' => 'You havn\'t permission');
                return $this->output->set_output($this->json->encode($output));
            }  
                    
            $answer_id = $this->model_property->AddUserAnswer($this->userID,$field_value,$question->id);
            
            if($answer_id > 0)
            {
                $output['success'] = 'success';
                $output['define'] = $define;
                $html = '<li id="answer_' . $answer_id . '"><input type="radio" value="' . $answer_id . '" name="answer1"/>' . $field_value . '&nbsp;&nbsp;<img title="Delete action" onclick="delete_custom_field(\'' . $define . '\', this)" class="pointer" src="/images/delete_custom_action.gif"></li>';
                $output['new_element'] = $html;
            }
            else
                $output = array('success' => 'error', 'error' => 'This answer already exist.');
        }
        
        return $this->output->set_output($this->json->encode($output));
        
    }
    
    function ajax_delete_custom_field()
    {
        $define = $this->input->xss_clean($this->input->post('define'));
        $id = (int) $this->input->post('id');
        $property_info = $this->model_property->get_property_by_id($this->propertyID, $this->userID, $this->propertyType);
        
        if(!$id || !$property_info)
        {
            $output = array('success' => 'error', 'error' => 'You havn\'t permission');
            return $this->output->set_output($this->json->encode($output));
        }
        
        if($define == 'action')
        {
            $status_info = $this->model_statuses->checkUserStatus($this->userID,$id);
            
            if($status_info)
            {
                $this->model_statuses->DeleteCustomStatus($this->userID,$id);
                $output['success'] = 'success';
                $output['define'] = $define;

                $this->_setPropertyData();
        		$this->_setLeadStatusTypesData();
        		$this->_setDataToSession();
                
                $output['result'] = $this->load->view($this->data['VIEW_LEADS_STATUSES'], $this->data, true);
            }
            else
                $output = array('success' => 'error', 'error' => 'You havn\'t permissions');
        }
        elseif($define == 'answer_exp')
        {
            $answer_info = $this->model_property->check_custom_user_exp_answer($this->userID,$id);
            if($answer_info)
            {

                $this->model_property->update_appointment_by_exp_answer($answer_info->id, array('answer_exp_id'=>0));
                    
                $this->model_property->remove_custom_exp_answer($answer_info->id);
                $output['success'] = 'success';
                $output['define'] = $define;
            }
            else
                 $output = array('success' => 'error', 'error' => 'You havn\'t permissions');
        }
        else
        {
            $answer_info = $this->model_property->check_custom_user_answer($this->userID,$id);
            if($answer_info)
            {
                $this->model_property->update_appointment_by_answer($answer_info->id, array('answer_id'=>0));
                    
                $this->model_property->remove_custom_answer($answer_info->id);
                $output['success'] = 'success';
                $output['define'] = $define;
            }
            else
                 $output = array('success' => 'error', 'error' => 'You havn\'t permissions');
        }
        
        return $this->output->set_output($this->json->encode($output));   
    }
    
    function ajax_save_exp_question_answer()
    {
        $answer_id = (int) $this->input->post('answer');
        $appt_id = (int) $this->input->post('appt_id');
        
        $answer_info = $this->model_property->check_answer_exp_appt($answer_id, $this->userID); 
        $appt_info = $this->model_property->check_user_appt($appt_id, $this->userID);
        
        if($answer_info && $appt_id && $appt_info)
        { 
            $this->model_property->update_appointment($appt_info->id, array('answer_exp_id'=>$answer_info->id));
            $output['success'] = 'success';
            if($answer_id == 5 || $answer_id == 7 || $answer_id == 9 || $answer_id == 11)
                $output['show_second'] = 1;
            else
                $output['show_second'] = 0;
        }
        else
            $output = array('success' => 'error', 'error' => 'You havn\'t permissions');
            
        return $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_save_question_answer()
    {
        $answer_id = (int) $this->input->post('answer1');
        $appt_id = (int) $this->input->post('appt_id');
        
        $answer_info = $this->model_property->check_answer($this->userID,$answer_id); 
        $appt_info = $this->model_property->check_user_appt($appt_id, $this->userID);
        
        if($answer_info && $appt_id && $appt_info)
        { 
            $this->model_property->update_appointment($appt_info->id, array('answer_id'=>$answer_info->id));
            $output['success'] = 'success';
        }
        else
            $output = array('success' => 'error', 'error' => 'You havn\'t permissions');
            
        return $this->output->set_output($this->json->encode($output));
    }
    
	function getNeighbor()
	{
		$this->_setPropertyData();
		$this->_setPropertyInfoData();
		$this->_setDataToSession();

		$this->load->view('leads/view_leads_neighbor',$this->data['NEIGHBOR_DATA']);
	}
    
    function deleteFollowUp()
    {
        $this->model_property->remove_follow_up($this->propertyID, $this->userID);
        $this->output->set_output('1');
    }
    
	function deleteLead()
	{
		/*if ($this->adminMode)
		{
			$result = $this->model_property->DeleteProperty($this->propertyType,$this->propertyID);
			$output = $result==true?'1':'0';

			$this->output->set_output($output);
		}
		elseif ($this->followUpMode)
		{
			$this->model_property->DeleteFollowUpsOfProperty($this->userID,$this->propertyID);
			$this->output->set_output('1');
		}
		else
		{
			$this->model_property->RemoveUserProperty($this->userID,$this->propertyType,$this->propertyID,$this->followUpMode);
			$this->output->set_output('1');
		}*/
        
        
        if($property_info = $this->model_property->check_property($this->propertyID, $this->userID, $this->propertyType))
        {
            if($this->propertyType != 'fsbo' && $property_info->is_fsbo == 0)
                $this->model_property->DeletePropertyByID($this->propertyID);
            else
            {
                if( (int) $property_info->fsbo_parent_id > 0)
                {
                    $this->model_property->remove_fsbo_dublicate($property_info->fsbo_parent_id, $this->userID);
                    $deleted_orig_id = $property_info->fsbo_parent_id;
                }
                else
                    $deleted_orig_id = $property_info->PropertyID;

                $data = array('user_id'=>$this->userID, 'property_id'=>$deleted_orig_id);
                $this->model_property->remove_from_fsbo($data);
            }
            
                
            $this->output->set_output('1');
            $this->session->set_userdata('s_propertyID', 0);
        }
        else
            $this->output->set_output('0');
        
        
	}

	function getAddNewLeadContent()
	{
		$this->data = array();
		$this->data['BASE_URL'] = base_url_http();
		$this->data['PROPERTY_TYPE'] = $this->propertyType;

		$this->data['FOLLOWUP_FROM_ADDNEW'] = true;
		$this->data['FOLLOWUP_MODE'] = $this->followUpMode;
		$this->data['FOLLOWUP_TYPES'] = $this->config->item('FOLLOWUP_TYPES');
        $states = $this->model_user->GetUserStatesForSelect($this->userID);
        unset($states["-1"]);
		$this->data['STATES'] = $states;
		$this->data['MLS'] = $this->model_user->GetUserMLSForSelect($this->userID);
        $days = array('0'=>'Day');
        for($i=1;$i<=31;$i++)
            $days[$i] = $i;
        
        $month = array (
            '0' => 'Month',
            '1' => 'January',
            '2' => 'February',
            '3' => 'March',
            '4' => 'April',
            '5' => 'May',
            '6' => 'June',
            '7' => 'July',
            '8' => 'August',
            '9' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December'
        );

            
        $this->data['days'] = $days;
        $this->data['months'] = $month;
        
        $types = $this->_get_field_types();
        $this->data['types'] = $types;
        
		$this->load->view('leads/view_leads_addnew.php',$this->data);
	}

    function _get_field_types()
    {
        $allTypes = $this->model_property->get_field_types($this->userID);
        $types = array(
            'phones'=>array(),
            'emails'=>array(),
            'addresses'=>array(),
            'relations'=>array(),
            'categories'=>array(),
            'ims'=>array(),
            'websites'=>array(),
            'dates'=>array(),
            'customs'=>array()
        );
        
        foreach($allTypes as $type)
        {
            if($type->type == 'phone')
                $types['phones'][] = $type;
            elseif($type->type == 'email')
                $types['emails'][] = $type;
            elseif($type->type == 'address')
                $types['addresses'][] = $type;
            elseif($type->type == 'relation')
                $types['relations'][] = $type;
            elseif($type->type == 'category')
                $types['categories'][] = $type;
            elseif($type->type == 'im')
                $types['ims'][] = $type;
            elseif($type->type == 'website')
                $types['websites'][] = $type;
            elseif($type->type == 'date')
                $types['dates'][] = $type;
            elseif($type->type == 'custom')
                $types['customs'][] = $type;
        }
        return $types;
    }
    
	function getSearchLeadsContent()
	{
		$this->data['BASE_URL'] = base_url_http();

		$searchName = $this->input->post('searchName',true);

		if ($searchName===false)
			$this->data['SAVED_SEARCH_FIELDS'] = array();
		else
		{
			$this->data['SAVED_SEARCH_NAME'] = $searchName;
			$this->data['SAVED_SEARCH_FIELDS'] = $this->model_user->GetSavedSearchFields($this->userID,$searchName);
			if ($this->data['SAVED_SEARCH_FIELDS'] == null)
				$this->data['SAVED_SEARCH_FIELDS'] = array();
		}

		$this->_setSearchLeadsData();

		$this->load->view('leads/view_leads_search.php',$this->data);
	}

	function getSearchZIPContent()
	{
		$this->data['BASE_URL'] = base_url_http();
		$this->data['ZIP_INFO'] = $this->model_property->GetZIPInfo($this->userID);

		$this->load->view('leads/view_leads_searchzip.php',$this->data);
	}
    
    function ajax_set_contact_category()
    {
        $category = (int) $this->input->post('category');
        $appt_id = (int) $this->input->post('appt_id');
        $appt_info = $this->model_property->get_appt($appt_id, $this->propertyID);
        
        if($category > 0)
        {
            $type_info = $this->model_property->check_field_type($this->userID, $category);
            if($type_info)
            {
                $this->model_property->update_property($this->propertyID, array('category'=>$category));
                $output['category'] = $category;
            }
            else
                $output['category'] = 0;
            
        }
        else
        {
            $this->model_property->update_property($this->propertyID, array('category'=>0));
            $output['category'] = 0;
        }
        if($appt_info)
            $output['appt_id'] = $appt_info->id;
        else
            $output['appt_id'] = 0;
        
        $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_set_question_list()
    {
        $appt_id = (int) $this->input->post('appt');
        
        $appt_info = $this->model_property->check_user_appt($appt_id, $this->userID);
        
        $property_id = $appt_info->property_id;
        
        $property_info = $this->model_property->get_property_by_id($appt_info->property_id, $this->userID, $this->propertyType);
        $block_next_action = $this->input->post('block_next_action');
        $show_second = $this->input->post('show_second');

        if(!$property_info || !$appt_info || !$appt_id)
        {
            $output = array('success' => 'error', 'error' => 'You havn\'t permission');
            return $this->output->set_output($this->json->encode($output));
        }
        
        if($appt_info)
        {
            $category = $appt_info->category;
            if($category > 0)
            {
                if(!$show_second)
                {
                    $ownerData = $this->model_property->get_property_by_id($property_id, $this->userID, $this->propertyType);
                    $extraData = $this->model_property->get_property_extra_data($property_id);
                    $main_data = $this->get_main_data($extraData);
                    
                    $name = $ownerData->fname . ' ' . $ownerData->lname;        
                    $address_str = '';
                    $address_str .= $main_data['address'];
                    if(trim($address_str) == '')
                        $address_str .= $main_data['city'];
                    else
                        $address_str .= '<br />' . $main_data['city'];
                        
                    if(trim($address_str) == '')
                        $address_str .= $main_data['state'];
                    else
                        $address_str .= ', ' . $main_data['state'];
                        
                    if(trim($address_str) == '')
                        $address_str .= $main_data['zip'];
                    else
                        $address_str .= ' ' . $main_data['zip'];  
                    
                    $phone = $main_data['phone'];
            
                    $this->data['name'] = $name;
                    $this->data['address_str'] = $address_str;
                    $this->data['phone'] = $phone;
                    $this->data['current_category'] = $ownerData->category;
                    $this->data['appt_info'] = $appt_info;
                    
                    $answers = $this->model_property->get_questions_expired($ownerData->category, $this->userID);
                          
                    $this->data['answers'] = $answers;
            
                    $output['success'] = 'success';
                    $output['result'] = $this->load->view('leads/view_leads_appt_single_exp_question', $this->data, true);
                }
                else
                {
                    $this->data['appt_id_next'] = (int) $this->input->post('appt_id_next');
                    $this->data['set_current_history'] = $this->input->post('set_current_history');
                    
                    
                    $question = $this->model_property->get_question_by_category($category);
                    $this->data['question'] = $question;
                    $this->data['appt_info'] = $appt_info;
                    $this->data['property_info'] = $property_info;
                    
                    if($question)
                        $this->data['answers'] = $this->model_property->get_answers_by_question($question->id, $this->userID);
                    
                    $ids = array(15, 17, 19);
                    $this->data['status_actions'] = $this->model_statuses->get_statuses($ids, $this->userID, $property_info->category); 
                    
                    $output['result'] = $this->load->view('leads/view_leads_appt_questions',$this->data, true);
                    $output['success'] = 'success';
                }
                  
            }
            elseif($category == 0)
            {
                $types = $this->_get_field_types();
                $this->data['types'] = $types;
                $this->data['block_next_action'] = $block_next_action;
                $this->data['appt_info'] = $appt_info;
                $output['result'] = $this->load->view('leads/view_leads_appt_select_category',$this->data, true);
                $output['success'] = 'success';
            }
            else
            {
                $output = array('success' => 'notice', 'notice' => 'We have not question for this category.');
                return $this->output->set_output($this->json->encode($output));
            }
            
        }
        else
        {
            $output['success'] = 'error';
            $output['error'] = 'You havn\'t permission';
        }
        $this->output->set_output($this->json->encode($output));        
    }
    
    function ajax_save_user_status()
    {
        $statusID = (int) $this->input->post('current_id');
        $text = $this->input->post('text');
        
        if($this->model_statuses->check_user_status($statusID, $this->userID))
        {
            $this->model_statuses->update_status_by_id($statusID, array('Name' => $text));
            $output['success'] = 'success';
        }
        else
            $output['success'] = 'error';
            
        $this->output->set_output($this->json->encode($output)); 
    }
    
    
    function ajax_check_equal_contact()
    {
        $propertyID = (int) $this->input->post('propertyID');
        $output['result'] = 0;
        if($propertyID > 0)
        {
            if($property = $this->model_property->check_property($propertyID, $this->userID, $this->propertyType)) 
            {
                $extraData = $this->model_property->get_property_extra_data($propertyID);
                
                $chech_equal_arr = array();
                                
                foreach($extraData as $data)
                {
                    if($data->define == 'phone' && $data->value != "")
                        $chech_equal_arr['phone'][] = $data->value;
                    elseif($data->define == 'email' && $data->value != "")
                        $chech_equal_arr['email'][] = $data->value;
                }
                
                $where = '';
                $where_sub = '';
                if(count($chech_equal_arr) > 0)
                {
                    $where .= '(fname = \'' . $property->fname . '\' AND lname = \'' . $property->lname . '\' AND (';
                    foreach($chech_equal_arr as $key=>$define)
                    {
                        if(count($define) > 0)
                        {
                            if($where_sub != '')
                                $where_sub .= " OR ";
                                
                            $where_sub .= '(define = \'' . $key . '\' ';
                            foreach($define as $k=>$value)
                            {
                                if($k == 0)
                                    $where_sub .= ' && (';
                                
                                if($k +1 != count($define))
                                    $separator = ' OR ';
                                else
                                    $separator = '';
                                    
                                $where_sub .= ' value = \'' . $value . '\' ' . $separator;
                                
                                if($k +1 == count($define))
                                    $where_sub .= '))';
                                
                            }
                        }
                    }
                    $where .= $where_sub . '))';
                    
                }
                
                if($where != "")
                {
                    $check = $this->model_property->check_equal_contact($propertyID, $where, $this->userID);
                    if($check)
                        $output['result'] = 1;
                    else
                        $output['result'] = 0; 

                }
                
            }  
        }
        
        if($output['result'] == 0 && $property)
        {   
            $extraData = $this->model_property->get_property_extra_data($property->PropertyID);
            $main_data = $this->get_main_data($extraData);
            
            $update_data = array(
                'grid_phone' => $main_data['phone'],
                'grid_email' => $main_data['email'],
                'grid_im' => $main_data['im'],
                'grid_address' => $main_data['address'],
                'grid_zip' => $main_data['zip'],
                'grid_city' => $main_data['city'],
                'grid_state' => $main_data['state']
            );
            $update_data['is_tmp'] = 0;
            
            $this->model_property->update_property($property->PropertyID, $update_data);
            
            $current_page = $this->get_page_by_property($this->input->post('rp'));
            $output['current_page'] = $current_page;
        }
            
            
        $this->output->set_output($this->json->encode($output)); 
    }
    
    function ajax_remove_type_dd()
    {
        $typeID = (int) $this->input->post('typeID');
        $define = $this->input->post('define');
        
        if($define != 'category')
            $define = 'relation';
        
        $typeInfo = $this->model_property->get_type_info($typeID, $this->userID);
        {
            $this->model_property->update_properties_by_param($define, $typeID, $this->userID, array($define=>0));
            $this->model_property->remove_type_info($typeID);
        }
        
        $output['success'] = 'success';
        $this->output->set_output($this->json->encode($output)); 
    }
    
    function ajax_delete_type()
    {
        $typeID = (int) $this->input->post('typeID');
        $typeInfo = $this->model_property->get_type_info($typeID, $this->userID);
        
        if($typeInfo)
        {
            $this->model_property->update_extra_data_type($typeID, array('type_id' => 0));
            $this->model_property->remove_type_info($typeID);
        }
            
        
        $output['success'] = 'success';
        
        $this->output->set_output($this->json->encode($output)); 
    }
    
    function ajax_save_note()
    {
        $noteID = (int) $this->input->post('noteID');
        $note = trim($this->input->post('note', true));
        
        $note_info = $this->model_statuses->getNote($noteID,$this->userID);
        if($note_info)
        {
            if($note != "")
                $this->model_statuses->update_note($noteID, array('NoteText' => $note));
            else
                $this->model_statuses->delete_note($noteID);
            $output['success'] = 'success';
        }
        else
           $output['success'] = 'error';
           
        $this->output->set_output($this->json->encode($output)); 
    }
    
    function ajax_get_note()
    {
        $noteID = (int) $this->input->post('noteID');
        $note_info = $this->model_statuses->getNote($noteID,$this->userID);
        $this->data['note_info'] = $note_info;
        if($note_info)
        {
            $output['result'] = $this->load->view('leads/view_leads_history_edit',$this->data, true);
            $output['success'] = 'success';
        }
        else
            $output['success'] = 'error';
        
        $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_set_motivation()
    {
        $value = (int) $this->input->post('value');
        if($value > 0 && $value <= 5)
        {
            $data = array('motivation' => $value);
            $this->model_property->update_property($this->propertyID, $data);
            $output['success'] = 'success'; 
        }
        else
            $output['success'] = 'error'; 

        $this->output->set_output($this->json->encode($output)); 
    }
    
    function ajax_update_property_block()
    {
        $propertyID = (int) $this->input->post('propertyID');
        if($property = $this->model_property->check_property($propertyID, $this->userID, $this->propertyType))
        {
            $this->_setOwnerData($propertyID);
            $output['result'] = $this->load->view('leads/view_leads_ownerinfo_content_info',$this->data, true);
            $output['success'] = 'success';
        }
        else
            $output['success'] = 'error';
        
        $this->output->set_output($this->json->encode($output)); 
    }
    
    function ajax_edit_property()
    {
        
        $propertyID = (int) $this->input->post('propertyID');
        if($property = $this->model_property->check_property($propertyID, $this->userID, $this->propertyType))
        {
            $states = $this->model_user->GetUserStatesForSelect($this->userID);
            unset($states["-1"]);
    		$this->data['STATES'] = $states;
    		$this->data['MLS'] = $this->model_user->GetUserMLSForSelect($this->userID);
            $days = array('0'=>'Day');
            for($i=1;$i<=31;$i++)
                $days[$i] = $i;
            
            $month = array (
                '0' => 'Month',
                '1' => 'January',
                '2' => 'February',
                '3' => 'March',
                '4' => 'April',
                '5' => 'May',
                '6' => 'June',
                '7' => 'July',
                '8' => 'August',
                '9' => 'September',
                '10' => 'October',
                '11' => 'November',
                '12' => 'December'
            );
            
            $types = $this->_get_field_types();
            $this->data['types'] = $types;
                
            $this->data['days'] = $days;
            $this->data['months'] = $month;
            
            $this->_setOwnerData($propertyID);
            $output['result'] = $this->load->view('leads/view_leads_editpr.php',$this->data, true);
            $output['success'] = 'success';
        }
        else
            $output['success'] = 'error';
        
        $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_check_property()
    {
        $propertyID = (int) $this->input->post('propertyID');
        if($property = $this->model_property->check_property($propertyID, $this->userID, $this->propertyType))
            $output['success'] = 'success';
        else
            $output['success'] = 'error';
            
        $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_set_search_filter()
    {
        $define = $this->input->post('define');
        $value = trim($this->input->post('value'));
        $search_text = trim($this->input->post('search_text'));
        $search_category = trim($this->input->post('search_category'));
        
        $this->data['PROPERTY_INFO_TYPE'] = $this->propertyInfoType;
		$this->data['PROPERTY_TYPE'] = $this->propertyType;
        
        if($define == 'field')
        {
            $params = array('define' => 'field', 'value' => mysql_escape_string($value));
            
            if($search_category > 0 && $this->model_property->check_field_type($this->userID, (int) $search_category))    
                $params['category'] = $search_category;
                
            $contacts = $this->model_property->getUserContacts($this->userID, 'order_column', $params);
        }
        elseif($define == 'category')
        {
            if($value > 0 && $this->model_property->check_field_type($this->userID, (int) $value))
            {   
                $params = array('define' => 'category', 'category' => $value);
                
                if($search_text != "")
                    $params['search_text'] = mysql_escape_string($search_text);
                    
                $data = array('category_search_option' => $value);
                $this->model_user->updateUser($this->userID, $data);
                
                $contacts = $this->model_property->getUserContacts($this->userID, 'order_column', $params);
            }
            else
            {
                $data = array('category_search_option' => 0);
                $this->model_user->updateUser($this->userID, $data);
                $contacts = $this->model_property->getUserContacts($this->userID);
            }
        }
        elseif($define == 'fav')
        {   
            if($value == 'on')
                $contacts = $this->model_property->getUserContacts($this->userID, 'order_column', array('define' => 'fav'));
            else
                $contacts = $this->model_property->getUserContacts($this->userID); 
        }
        else
        {
            $contacts = $this->model_property->getUserContacts($this->userID);
        }
        $this->data['user_contacts'] = $contacts;
        $this->data['alphabet'] = self::$alphabet;
        $this->data['PROPERTY_ID'] = $this->propertyID;
        
        $output['content'] = $this->load->view('leads/view_leads_ownerinfo_content.php',$this->data, true);
        $this->output->set_output($this->json->encode($output));
    }
    
    function getOwnerSearchInfo()
    {
        $this->_setUserContacts();
        $this->data['PROPERTY_INFO_TYPE'] = $this->propertyInfoType;
		$this->data['PROPERTY_TYPE'] = $this->propertyType;
        $this->data['PROPERTY_ID'] = $this->propertyID;
        
        if($this->propertyType == 'jljs')
            $output['result'] = $this->load->view('leads/view_leads_ownerinfo_content_jljs.php',$this->data, true);
        else
            $output['result'] = $this->load->view('leads/view_leads_ownerinfo_content.php',$this->data, true);
        
        $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_set_favourite()
    {
        $propertyID = (int) $this->input->post('propertyID');
        if($property = $this->model_property->check_property($propertyID, $this->userID, $this->propertyType))
        {
            if($property->is_favourite == 0)
            {
                $favourite = 1;
                $output['is_favourite'] = 1;
            }    
            else
            {
                $favourite = 0;
                $output['is_favourite'] = 0;
            }
                        
            $data = array('is_favourite' => $favourite);
            $this->model_property->update_property($propertyID, $data);
            $output['success'] = 'success';
        }
        else
           $output['success'] = 'error'; 
           
        $this->output->set_output($this->json->encode($output));
    }
    
    function remove_custom_field()
    {
        $type_edit_id = (int) $this->input->post('typeID');
        $define = $this->input->post('define');
        if($define != 'folder')
        {
            $typeInfo = $this->model_property->get_type_info($type_edit_id, $this->userID);

            if($typeInfo && $typeInfo->is_general == 0)
            {
                $this->model_property->remove_type_info($type_edit_id);
                $this->model_property->update_property_relation($type_edit_id);
                $output = array('success' => 'success');
            } 
            else
                $output = array('success' => 'error', 'error' => 'You have not permissions');
        }
        else
        {
            $folder_info = $this->model_property->check_folder($type_edit_id,$this->userID);
        
            if(!$folder_info)
                $output = array('success' => 'error', 'error' => 'You have not permissions');
            else
            {
                $this->model_property->remove_properties_from_folder($type_edit_id, $this->userID);
                $this->model_property->remove_folder($type_edit_id, $this->userID);
                
                $output = array('success' => 'success');
            }
        }
        
        return $this->output->set_output($this->json->encode($output));    
    }
    
    
    function ajax_saveProperty()
    {
        $propertyID = (int) $this->input->post('propertyID');
        $field = $this->input->post('field');
        $define = $this->input->post('define');
        $element_val = $this->input->post('value', true);
        $extra_id = (int) $this->input->post('extra_id', true);
        $ins_up = $this->input->post('ins_up', true);
        $type_edit_id = $this->input->post('typeID');
                
        $output['status'] = '';
        
        if($this->redux_auth->is_limit_user() || $this->redux_auth->is_pro_user() || $this->redux_auth->is_fsbo_user())
        {
            if($this->redux_auth->is_limit_user())
                $mem_id = 1;
            elseif($this->redux_auth->is_pro_user())
                $mem_id = 2;
            else
                $mem_id = 3;
                
            $count_contacts = $this->model_property->get_count_user_properties($this->userID);
            $membership_info = $this->model_registration->get_membership_info_by_id($mem_id);
            
            $mem_contact_limit = (int)$membership_info->mem_contact_limit;
            
            if($count_contacts >= $mem_contact_limit && $mem_contact_limit > 0)
            {
                $output['success'] = 'error';
                $output['error'] = 'contact_limit';
                return $this->output->set_output($this->json->encode($output));
            }   
        }
        
        if($field == 'fname' || $field == 'lname' || $field == 'note' || $field == 'phone' || $field == 'phone_type' || $define == 'address' || $define == 'address_type' || $define == 'phone_custom_type' || $define == 'address_custom_type' || $field == 'email' || $field == 'email_type' || $define == 'email_custom_type' || $field == 'nickname' || $define == 'company_name' || $define == 'company_title' || $field == 'im' || $field == 'im_type' || $define == 'im_custom_type' || $field == 'website' || $field == 'website_type' || $define == 'website_custom_type' || $field == 'date' || $field == 'date_type' || $define == 'date_custom_type' || $field == 'custom_row_name' || $field == 'custom_row_value' || $field == 'relation' || $field == 'relation_custom' || $field == 'category' || $field == 'category_custom' || $field == 'sel_time' || $field == 'sel_price' || $field == 'sel_address' || $field == 'sel_home_traits' || $field == 'sel_home_traits' || $field == 'buy_fin' || $field == 'buy_time' || $field == 'buy_price' || $field == 'buy_area' || $field == 'buy_home_traits' || $field == 'land_address' || $field == 'land_available' || $field == 'land_lease' || $field == 'land_rent' || $field == 'land_deposit' || $field == 'land_home_traits' || $field == 'ten_move' || $field == 'ten_rent' || $field == 'ten_lease' || $field == 'ten_area' || $field == 'ten_home_traits' || $field == 'set_phone_main' || $field == 'set_email_main' || $field == 'set_im_main' || $field == 'del_relation_custom' || $field == 'del_category_custom' || $field == 'home_price_max' || $field == 'home_price_define' || $field == 'home_price_min' || $field == 'home_price_equal')
        {
            $leadPropertyTypeName = $this->propertyType;
            if($propertyID == 0)
            {
                $leadPropertyTypeName = $this->propertyType;
                $LeadTypeID = $this->model_property->GetPropertyTypeIDFromName($leadPropertyTypeName);
                if($LeadTypeID == -1)
                    $LeadTypeID = 6;
                    
                $propertyLeadID = $this->model_property->insert_property(array('LeadTypeID' => $LeadTypeID, 'UserID' => $this->userID, 'status_property'=>'new', 'post_date'=>date('Y-m-d h:i:s'), 'is_tmp' => 1, 'is_contact' => 1));
                $success = 1;
                $new_row = $propertyLeadID;
                
                $this->model_user->updateUser($this->userID, array('has_contacts' => 1));
            }
            else
            {
                if($contact_info = $this->model_property->check_property($propertyID, $this->userID, $this->propertyType))
                    $success = 1;
                else
                    $success = 0;
                    
                if($this->propertyType == 'fsbo')
                {
                    if($contact_info && (int) $contact_info->UserID > 0)
                        $propertyLeadID = $contact_info->propertyID;
                    else if($contact_info)
                    {
                        $new_duplicate_id = $this->model_property->create_duplicate_contact($contact_info->PropertyID, $this->userID);
                        $new_row = $new_duplicate_id;
                        $propertyLeadID = $new_duplicate_id;
                    }
                    else
                        $success = 0;
                }
                else
                {
                    $propertyLeadID = $propertyID;
                    $new_row = 0;   
                }
 
            } 
            
            if($success)
            {
                $data = array();
                                
                if($field == 'fname' || $field == 'lname' || $field == 'nickname' || $define == 'company_name' || $define == 'company_title' || $field == 'note' || $field == 'relation' || $field == 'category' || $field == 'sel_time' || $field == 'sel_price' || $field == 'sel_address' || $field == 'sel_home_traits' || $field == 'sel_home_traits' || $field == 'buy_fin' || $field == 'buy_time' || $field == 'buy_price' || $field == 'buy_area' || $field == 'buy_home_traits' || $field == 'land_address' || $field == 'land_available' || $field == 'land_lease' || $field == 'land_rent' || $field == 'land_deposit' || $field == 'land_home_traits' || $field == 'ten_move' || $field == 'ten_rent' || $field == 'ten_lease' || $field == 'ten_area' || $field == 'ten_home_traits' || $field == 'home_price_max' || $field == 'home_price_define' || $field == 'home_price_min' || $field == 'home_price_equal')
                {
                    $data = array($field => $element_val);
                    
                    
                    $this->model_property->update_property($propertyLeadID, $data);
                }
                elseif ($field == 'del_relation_custom')
                {
                    $typeInfo = $this->model_property->get_type_info($type_edit_id, $this->userID);
                    if($typeInfo && $typeInfo->is_general == 0)
                    {
                        $this->model_property->remove_type_info($type_edit_id);
                        $this->model_property->update_property_relation($type_edit_id);
                    }
                }
                elseif ($field == 'del_category_custom')
                {
                    $typeInfo = $this->model_property->get_type_info($type_edit_id, $this->userID);
                    if($typeInfo && $typeInfo->is_general == 0)
                    {
                        $this->model_property->remove_type_info($type_edit_id);
                        $this->model_property->update_property_category($type_edit_id);
                    }
                }
                elseif($field == 'relation_custom')
                {
                    $data_custom = array(
                            'type' => 'relation',
                            'value' => $element_val,
                            'user_id' => $this->userID,
                            'is_general' => 0
                        );
                        
                    if($define == 'new')

                        $typeID = $this->model_property->insert_custom_type($data_custom);                        
                    else
                    {
                        $this->model_property->update_custom_type($type_edit_id, $data_custom); 
                        $typeID = $type_edit_id;
                    }
                    
                    $this->model_property->update_property($propertyLeadID, array('relation' => $typeID));
                    
                    $output['typeID'] = $typeID;
                }
                elseif($field == 'category_custom')
                {
                    $data_custom = array(
                            'type' => 'category',
                            'value' => $element_val,
                            'user_id' => $this->userID,
                            'is_general' => 0
                        );
                        
                    if($define == 'new')

                        $typeID = $this->model_property->insert_custom_type($data_custom);                        
                    else
                    {
                        $this->model_property->update_custom_type($type_edit_id, $data_custom); 
                        $typeID = $type_edit_id;
                    }
                    
                    $this->model_property->update_property($propertyLeadID, array('category' => $typeID));
                    
                    $output['typeID'] = $typeID;
                }
                elseif($field == 'set_phone_main' || $field == 'set_email_main' || $field == 'set_im_main')
                {
                    if($field == 'set_phone_main')
                        $define_type = 'phone';
                    elseif($field == 'set_email_main')
                        $define_type = 'email';
                    elseif($field == 'set_im_main')
                        $define_type = 'im';
                        
                    $this->model_property->set_not_main_extra($propertyLeadID, $define_type);
                    if($extra_id > 0)
                    {
                        $data = array(
                                    'is_main' => 1,
                                );
                        $this->model_property->update_extra_data($extra_id, $data);
                        $output['extra_id'] = 0;
                    }
                    else
                    {
                        
                        $data = array(
                                'property_id' => $propertyLeadID,
                                'define' => $define_type,
                                'is_main' => 1
                            );
                             
                        $output['extra_id'] = $this->model_property->insert_extra_data($data);
                    }
                }
                elseif($field == 'phone' || $field == 'phone_type' || $define == 'phone_custom_type')
                {
                    $params = array(
                        'field' => $define == 'phone_custom_type' ? $define : $field,
                        'define' => 'phone',
                        'element_val' => $element_val,
                        'ins_up' => $ins_up,
                        'propertyLeadID' => $propertyLeadID,
                        'extra_id' => $extra_id,
                        'type_edit_id' => $type_edit_id
                    );

                    $output = $this->_set_extra_data($params);

                }
                elseif($field == 'email' || $field == 'email_type' || $define == 'email_custom_type')
                {
                    $params = array(
                        'field' => $define == 'email_custom_type' ? $define : $field,
                        'define' => 'email',
                        'element_val' => $element_val,
                        'ins_up' => $ins_up,
                        'propertyLeadID' => $propertyLeadID,
                        'extra_id' => $extra_id,
                        'type_edit_id' => $type_edit_id
                    );
        
                    $output = $this->_set_extra_data($params);
                }
                elseif($field == 'im' || $field == 'im_type' || $define == 'im_custom_type')
                {
                    $params = array(
                        'field' => $define == 'im_custom_type' ? $define : $field,
                        'define' => 'im',
                        'element_val' => $element_val,
                        'ins_up' => $ins_up,
                        'propertyLeadID' => $propertyLeadID,
                        'extra_id' => $extra_id,
                        'type_edit_id' => $type_edit_id
                    );
        
                    $output = $this->_set_extra_data($params);
                }
                elseif($field == 'website' || $field == 'website_type' || $define == 'website_custom_type')
                {
                    $params = array(
                        'field' => $define == 'website_custom_type' ? $define : $field,
                        'define' => 'website',
                        'element_val' => $element_val,
                        'ins_up' => $ins_up,
                        'propertyLeadID' => $propertyLeadID,
                        'extra_id' => $extra_id,
                        'type_edit_id' => $type_edit_id
                    );
        
                    $output = $this->_set_extra_data($params);
                }
                elseif($field == 'date' || $field == 'date_type' || $define == 'date_custom_type')
                {
                    $params = array(
                        'field' => $define == 'date_custom_type' ? $define : $field,
                        'define' => 'date',
                        'element_val' => $element_val,
                        'ins_up' => $ins_up,
                        'propertyLeadID' => $propertyLeadID,
                        'extra_id' => $extra_id,
                        'type_edit_id' => $type_edit_id
                    );
        
                    $output = $this->_set_extra_data($params); 
                }
                elseif($field == 'custom_row_name' || $field == 'custom_row_value')
                {
                    if($extra_id > 0)
                    {
                        if($this->model_property->check_extra_data($extra_id, $this->userID))
                        {
                            if($field == 'custom_row_name')
                            {
                                $custom = $this->model_property->check_custom_field($element_val, $this->userID, 'custom');
                                $user_custom_fields = $this->model_property->user_custom_fields($this->userID, 'custom');
                                $typeID = 0;
                                   
                                if($custom ||($custom && $ins_up == 'new'))
                                    $output['custom_error'] = 'already_exist';
                                else
                                {
                                    $data_custom = array(
                                        'type' => 'custom',
                                        'value' => $element_val,
                                        'user_id' => $this->userID,
                                        'is_general' => 0
                                    );
                                   
                                    if($ins_up == 'old')
                                    {
                                        $this->model_property->update_custom_type($type_edit_id, $data_custom); 
                                        $typeID = $type_edit_id;
                                    }
                                    elseif($user_custom_fields < 5)
                                        $typeID = $this->model_property->insert_custom_type($data_custom);
                                    else
                                       $output['custom_error'] = 'a_lot_of_items'; 
                                }
                                if($typeID > 0)
                                    $data = array('type_id' => $typeID, 'define' => 'custom');
                                    
                                $output['typeID'] = $typeID;
                            }
                            elseif($field == 'custom_row_value')
                                $data = array(
                                    'define' => 'custom',
                                    'value' => $element_val
                                );
                                
                            if(isset($data) && count($data) > 0)
                                $this->model_property->update_extra_data($extra_id, $data);
                            
                            $output['extra_id'] = 0;
                        }
                    }
                    else
                    {
                        if($field == 'custom_row_name')
                        {
                            $custom = $this->model_property->check_custom_field($element_val, $this->userID, 'custom');
                            $user_custom_fields = $this->model_property->user_custom_fields($this->userID, 'custom');
                            $typeID = 0;
                            if(!$custom)
                            {
                                
                                    
                                    $data_custom = array(
                                        'type' => 'custom',
                                        'value' => $element_val,
                                        'user_id' => $this->userID,
                                        'is_general' => 0
                                    );
                                    if($type_edit_id > 0)
                                    {
                                        $this->model_property->update_custom_type($type_edit_id, $data_custom); 
                                        $typeID = $type_edit_id;
                                    }
                                    elseif($user_custom_fields < 5)
                                    {
                                        $typeID = $this->model_property->insert_custom_type($data_custom);
                                    }
                                    else
                                        $output['custom_error'] = 'a_lot_of_items'; 
                            }
                            else
                                $output['custom_error'] = 'already_exist';
                                
                            if($typeID > 0)
                                $data = array('type_id' => $typeID, 'property_id' => $propertyLeadID, 'define' => 'custom'); 
                            $output['typeID'] = $typeID;
                        }
                        elseif($field == 'custom_row_value')
                            $data = array(
                                    'property_id' => $propertyLeadID,
                                    'define' => 'custom',
                                    'value' => $element_val
                             );
                        
                        if(isset($data) && count($data) > 0)
                            $output['extra_id'] = $this->model_property->insert_extra_data($data);
                    }
                }
                elseif($define == 'address' || $define == 'address_type' || $define == 'address_custom_type')
                {
                    if($field == 'state' || $field == 'city' || $field == 'street' || $field == 'zip' || $define == 'address_type' || $define == 'address_custom_type')
                    {
                        if($extra_id > 0)
                        {
                            if($this->model_property->check_extra_data($extra_id, $this->userID))
                            {
                                if($define == 'address')
                                    $data = array(
                                        'define' => 'address',
                                        $field => $element_val
                                    );
                                elseif($define == 'address_type')
                                {
                                    $element_val = (int) $element_val;
                   
                                    if($element_val > 0)
                                    {
                                        if($this->model_property->check_field_type($this->userID, $element_val))
                                        {
                                            $data = array(
                                                'type_id' => $element_val,
                                                'define' => 'address',
                                            );
                                        }
                                    }
                                }
                                elseif($define == 'address_custom_type')
                                {
                                    $custom = $this->model_property->check_custom_field($element_val, $this->userID, 'address');
                                    $user_custom_fields = $this->model_property->user_custom_fields($this->userID, 'address');
                                    $typeID = 0;
                                   
                                    if($custom ||($custom && $ins_up == 'new'))
                                        $output['custom_error'] = 'already_exist';
                                    else
                                    {
                                        $data_custom = array(
                                            'type' => 'address',
                                            'value' => $element_val,
                                            'user_id' => $this->userID,
                                            'is_general' => 0
                                        );
                                       
                                        if($ins_up == 'old')
                                        {
                                            $this->model_property->update_custom_type($type_edit_id, $data_custom); 
                                            $typeID = $type_edit_id;
                                        }
                                        elseif($user_custom_fields < 5)
                                            $typeID = $this->model_property->insert_custom_type($data_custom);
                                        else
                                           $output['custom_error'] = 'a_lot_of_items'; 
                                    }
                                        
                                     
                                    if($typeID > 0)
                                        $data = array('type_id' => $typeID, 'define' => 'address');
                                        
                                    $output['typeID'] = $typeID;
                                }    
                                                                        
                                $this->model_property->update_extra_data($extra_id, $data);
                                $output['extra_id'] = 0;
                            }
                        }
                        else
                        {
                            if($define == 'address')
                                $data = array(
                                    'property_id' => $propertyLeadID,
                                    'define' => $define,
                                    $field => $element_val
                                );
                            elseif($define == 'address_type')
                            {
                                $element_val = (int) $element_val;
                                
                                if($element_val > 0)
                                {
                                    if($this->model_property->check_field_type($this->userID, $element_val))
                                    {
                                        $data = array(
                                            'property_id' => $propertyLeadID,
                                            'type_id' => $element_val,
                                        );
                                    }
                                }
                            }
                            elseif($define == 'address_custom_type')
                            {
                                $custom = $this->model_property->check_custom_field($element_val, $this->userID, 'address');
                                $user_custom_fields = $this->model_property->user_custom_fields($this->userID, 'address');
                                
                                $typeID = 0;
                                if(!$custom)
                                {
                                    if($user_custom_fields < 5)
                                    {
                                        
                                        $data_custom = array(
                                            'type' => 'address',
                                            'value' => $element_val,
                                            'user_id' => $this->userID,
                                            'is_general' => 0
                                        );
                                        $typeID = $this->model_property->insert_custom_type($data_custom);
                                    }
                                    else
                                       $output['custom_error'] = 'a_lot_of_items'; 
                                }
                                else
                                    $output['custom_error'] = 'already_exist';
                                    
                                if($typeID > 0)
                                    $data = array('type_id' => $typeID, 'property_id' => $propertyLeadID, 'define' => 'address'); 
                                $output['typeID'] = $typeID;
                            }
                            
                            $output['extra_id'] = count($data) > 0 ? $this->model_property->insert_extra_data($data) : 0;
                        }
                    }
                }
                $output['status'] = 'saved';
            }
            $output['new_row'] = $new_row;
            
            $this->output->set_output($this->json->encode($output));
        }
    }
    
    
    function _set_extra_data($params)
    {
        $data = array();
        $field = $params['field'];
        $define = $params['define'];
        $element_val = $params['element_val'];
        $ins_up = $params['ins_up'];
        $propertyLeadID = $params['propertyLeadID'];
        $extra_id = $params['extra_id'];
        $type_edit_id = $params['type_edit_id'];
        
        
        if($extra_id > 0)
        { 
            if($this->model_property->check_extra_data($extra_id, $this->userID))
            {
                if($field == $define)
                {
                    $data = array(
                        'define' => $define,
                        'value' => $element_val
                    );
                    if($define == 'date')
                        $data['value_date'] = $element_val;
                }
                elseif($field == $define . '_type')
                {
                    $element_val = (int) $element_val;
                   
                    if($element_val > 0)
                    {
                        if($this->model_property->check_field_type($this->userID, $element_val))
                        {
                            $data = array(
                                'type_id' => $element_val,
                                'define' => $define,
                            );
                        }
                    }
                }
                elseif($field == $define . '_custom_type')
                {
                    $custom = $this->model_property->check_custom_field($element_val, $this->userID, $define);
                    $user_custom_fields = $this->model_property->user_custom_fields($this->userID, $define);
                    $typeID = 0;
                   
                    if($custom ||($custom && $ins_up == 'new'))
                        $output['custom_error'] = 'already_exist';
                    else
                    {
                        $data_custom = array(
                            'type' => $define,
                            'value' => $element_val,
                            'user_id' => $this->userID,
                            'is_general' => 0
                        );
                       
                        if($ins_up == 'old')
                        {
                            $this->model_property->update_custom_type($type_edit_id, $data_custom); 
                            $typeID = $type_edit_id;
                        }
                        elseif($user_custom_fields < 5)
                            $typeID = $this->model_property->insert_custom_type($data_custom);
                        else
                           $output['custom_error'] = 'a_lot_of_items'; 
                    }
                        
                     
                    if($typeID > 0)
                        $data = array('type_id' => $typeID, 'define' => $define);
                        
                    $output['typeID'] = $typeID;
                }
                           
                if(count($data) > 0)
                    $this->model_property->update_extra_data($extra_id, $data);
                    
                $output['extra_id'] = 0;
            }
        }
        else
        {
            if($field == $define)
                $data = array(
                    'property_id' => $propertyLeadID,
                    'define' => $define,
                    'value' => $element_val
                );
            elseif($field == $define . '_type')
            {                
                if($element_val > 0 || $element_val == 'custom')
                {
                    if($this->model_property->check_field_type($this->userID, $element_val))
                    {
                        $data = array(
                            'property_id' => $propertyLeadID,
                            'type_id' => $element_val,
                            'define' => $define
                        );
                    }
                }
            }
            elseif($field == $define . '_custom_type')
            {
                $custom = $this->model_property->check_custom_field($element_val, $this->userID, $define);
                $user_custom_fields = $this->model_property->user_custom_fields($this->userID, $define);
                
                $typeID = 0;
                if(!$custom)
                {
                    if($user_custom_fields < 5)
                    {   
                        $data_custom = array(
                            'type' => $define,
                            'value' => $element_val,
                            'user_id' => $this->userID,
                            'is_general' => 0
                        );
                        $typeID = $this->model_property->insert_custom_type($data_custom);  
                    }
                    else
                       $output['custom_error'] = 'a_lot_of_items'; 
                    
                }
                else
                    $output['custom_error'] = 'already_exist';
                    
                if($typeID > 0)
                    $data = array('type_id' => $typeID, 'property_id' => $propertyLeadID, 'define' => $define);
                    
                $output['typeID'] = $typeID;
            }
            
            $output['extra_id'] = count($data) > 0 ? $this->model_property->insert_extra_data($data) : 0;
        }
        
        return $output;
    }
    
    function ajax_save_form_data()
    {
        $form_data = $this->input->post('form_data');
        $propertyID = (int) $this->input->post('propertyID');

        if($this->model_property->check_property($propertyID, $this->userID, $this->propertyType))
        {
            
            $extraData = $this->model_property->get_property_extra_data($propertyID);
            $main_data = $this->get_main_data($extraData);
            
            $update_data = array(
                'grid_phone' => $main_data['phone'],
                'grid_email' => $main_data['email'],
                'grid_im' => $main_data['im'],
                'grid_address' => $main_data['address'],
                'grid_zip' => $main_data['zip'],
                'grid_city' => $main_data['city'],
                'grid_state' => $main_data['state'],
                'is_tmp' => 0
            );            
            
            $main_data = array(
                'fname' => mysql_escape_string($form_data['single_data']['fname']),
                'lname' => mysql_escape_string($form_data['single_data']['lname']),
            );
            
            $main_data = array_merge($main_data, $update_data);
            
            $this->model_property->update_property($propertyID, $main_data);
            $this->model_property->remove_extara_data_for_property($propertyID);
            
            foreach($form_data as $key=>$data)
            {
                $typeID = 0;
                $checkType = false;

                if(($key == 'phone' || $key == 'im' || $key == 'email' || $key == 'website') && count($data) > 0)
                {   
                    foreach($data as $k=>$value)
                    {   
                        if(isset($value['type']))
                            $typeID = (int) $value['type'];
                        if($typeID > 0)
                            $checkType = $this->model_property->check_field_type($this->userID, $typeID);
                
                        if($checkType || !empty($value['value']))
                        {
                            $data = array(
                                    'property_id' => $propertyID,
                                    'define' => $key,
                                    'type_id' => $checkType ? $typeID : null,
                                    'value' => mysql_escape_string($value['value']),
                                    'is_main' => (int) $value['is_main']
                            );  
                            $this->model_property->insert_extra_data($data);
                        }
                    }
                    
                }
                elseif($key == 'address' && count($data) > 0)
                {                        
                    foreach($data as $k=>$value)
                    {   
                        if(isset($value['type']))
                            $typeID = (int) $value['type'];
                        if($typeID > 0)
                            $checkType = $this->model_property->check_field_type($this->userID, $typeID);
                            
                        if($checkType || !empty($value['value']) || !empty($value['value_street']) || !empty($value['value_city']) || !empty($value['value_state']) || !empty($value['value_zip']))
                        {
                            $data = array(
                                    'property_id' => $propertyID,
                                    'define' => $key,
                                    'type_id' => $checkType ? $typeID : null,
                                    'street' => mysql_escape_string($value['value_street']),
                                    'city' => mysql_escape_string($value['value_city']),
                                    'state' => mysql_escape_string($value['value_state']),
                                    'zip' => mysql_escape_string($value['value_zip']),
                            );                                
                            $this->model_property->insert_extra_data($data);
                        }
                    }
                }
                elseif($key == 'date' && count($data) > 0)
                {
                    foreach($data as $k=>$value)
                    {   
                        if(isset($value['type']))
                            $typeID = (int) $value['type'];
                        if($typeID > 0)
                            $checkType = $this->model_property->check_field_type($this->userID, $typeID);
                            
                        if($checkType || !empty($value['value']))
                        {
                            $data = array(
                                    'property_id' => $propertyID,
                                    'define' => $key,
                                    'type_id' => $checkType ? $typeID : null,
                                    'value_date' => mysql_escape_string($value['value']) 
                            );
                            $this->model_property->insert_extra_data($data);
                        }
                     }
                    
                }
                elseif($key == 'custom_row' && count($data) > 0)
                {       
                    foreach($data as $k=>$value)
                    {
                        if(isset($value['type']))
                            $typeID = (int) $value['type'];
                        if($typeID > 0)
                            $checkType = $this->model_property->check_field_type($this->userID, $typeID);
                            
                        if($checkType || !empty($value['custom_value']))
                        {    
                            $data_val = array(
                                    'property_id' => $propertyID,
                                    'define' => 'custom',
                                    'type_id' => $checkType ? $typeID : null,
                                    'value' => mysql_escape_string($value['custom_value'])
                            ); 
                            
                            $this->model_property->insert_extra_data($data_val);

                        }
                    }
                }
            }
        }
        
        $this->propertyID = $propertyID;
        if($this->propertyType == 'contacts' || $this->propertyType == 'followups')
            $this->session->set_userdata('s_propertyID', $propertyID);
        
        $output['success'] = 'success';
        $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_remove_extra_data()
    {
        $extra_id = (int) $this->input->post('extra_id', true);
        $define = $this->input->post('define', true);
        $propertyLeadID = (int) $this->input->post('propertyLeadID', true);
        
        if($define != 'nickname' && $define != 'comp_title')
        {
            if($extra_id > 0 && $this->model_property->check_extra_data($extra_id, $this->userID))
            {
                $this->model_property->delete_extra_data($extra_id);
            }
        }
        else
        {
            if($propertyLeadID > 0 && $this->model_property->check_property($propertyLeadID, $this->userID, $this->propertyType))
            {
                if($define == 'nickname')
                {
                    $data = array('nickname' => '');
                    $this->model_property->update_property($propertyLeadID, $data);
                }
                elseif($define == 'comp_title')
                {
                    $data = array('company_name' => '', 'company_title' => '');
                    $this->model_property->update_property($propertyLeadID, $data);
                }
            }
        }
        $output['success'] = 'success';
        $this->output->set_output($this->json->encode($output));
    }
    
    function ajax_add_more_fields()
    {
         $define = $this->input->post('define');
         $output['success'] = 'error';
         
         if($define == 'phone')
         {
            $extraID = $this->model_property->insert_extra_data(array('field_define'=>'phone'));
            $this->data['extraID'] = $extraID;
            $output['success'] = 'success';
         }
  
        if($output['success'] == 'success')
            $output['result'] = $this->load->view('leads/view_leads_property_extra_field', $this->data, true); 
            
        $this->output->set_output($this->json->encode($output));           
    }
    
	function addNewLead()
	{
		switch ($this->propertyType)
		{
			case "fsbo":
				$this->form_validation->set_rules('FSBOName', 'Ad Name', 'trim|required|xss_clean');
				break;
			default:
				$this->form_validation->set_rules('OwnerName', 'Owner Name', 'trim|required|xss_clean');
				break;
		}

		if (!$this->form_validation->run())
		{
			$this->model_utils->redirectToLoginPage();
			return;
		}

		$fieldsArray = $this->model_utils->GetFieldsForJS("properties",array());
		$fields = $fieldsArray[2];

		$dataArray = array();
		foreach ($_POST as $key=>$value)
		{
			$key = $this->input->xss_clean($key);
			$value = $this->model_utils->TrimText($this->input->xss_clean($value));

			if (array_key_exists($key,$fields))
				$dataArray[$key] = $value;
		}

		if ($this->adminMode)
			$dataArray['UserID'] = null;
		else
			$dataArray['UserID'] = $this->userID;

		$dataArray['Added'] = date("Y/m/d H:i:s");

		#Determining and validating PropertyType and MLSID
		$mls = $this->model_user->getUserMLSByUserID($this->userID,true);
		if (empty($mls) || count($mls)==0)
		{
			$this->output->set_output('-1');
			return;
		}

		if ($this->adminMode)
			$leadPropertyTypeName = $this->propertyType;
		else
		{
			$leadPropertyTypeName = "other";
			$dataArray["FollowUpLeadTypeID"] = $this->model_property->GetPropertyTypeIDFromName($this->propertyType);
			$dataArray["FollowUpLeadTypeName"] = $this->propertyType;
		}

		if (!isset($dataArray['MLSID']))
			$dataArray['MLSID'] = $mls[0]['mls_id'];
		else
		{
			$mlsFound = false;

			for ($i=0;$i<count($mls) && !$mlsFound;$i++)
				if ($mls[$i]['mls_id']==$dataArray['MLSID'])
					$mlsFound = true;

			if (!$mlsFound)
			{
				$this->output->set_output('-1');
				return;
			}
		}

		$dataArray['LeadTypeID'] = $this->model_property->GetPropertyTypeIDFromName($leadPropertyTypeName);

		if ($this->propertyType=="fsbo")
			$dataArray["Status"] = "FSBO";

		$propertyID = $this->model_property->AddProperty($dataArray,true);

		# add note
		$note = trim($this->input->post('note',true));
		if ($note!="")
			$this->model_statuses->AddLeadNote($propertyID,$this->userID,$note);

		if ($propertyID==null)
			$propertyID = -1;

		$output = $propertyID=="-1"?"":$propertyID;

		$this->output->set_output($output);
	}

	function searchLeads()
	{
		$searchName = $this->input->post('search_name',true);

		if ($this->model_user->CheckLeadSearchExists($this->userID,$searchName))
		{
			$this->session->set_userdata('s_selectedSearchName',$searchName);
			if ($searchName!='[DEFAULT]' && $searchName!='[EMPTY]')
				$this->model_user->AddUserMeta($this->userID,META_GROUP_LEAD_GRID_SEARCH,META_SELECTED_SEARCH_NAME,$searchName);
		}

		$this->_setSearchLeadsData();
		$this->_buildSearchQuery();
	}

	function saveLeadSearch()
	{
		$searchFields = $this->_buildSearchQuery();

		$editMode = $this->input->post('editMode',true);
		$searchName = trim($this->input->post('searchName',true));
		$searchFilter = $this->_refreshSearchFilter($this->session->userdata('s_searchFilter'));
		$searchLimit = $this->session->userdata('s_searchLimit');

		if ($searchName!='')
		{
			if (trim($searchFilter)=="")
				print "empty";
			elseif ($editMode==="0" && $this->model_user->CheckLeadSearchExists($this->userID,$searchName))
				print "exists";
			else
				$this->model_user->SaveLeadSearch($this->userID,$searchName,$searchFilter,$searchLimit,$searchFields);
		}
	}

	function changeSelectedSearch()
	{
		$searchName = $this->input->post('searchName',true);

		if ($searchName===false)
			return;

		if ($searchName==="")
		{
			$this->model_user->DeleteUserMeta($this->userID,META_SELECTED_SEARCH_NAME,META_GROUP_LEAD_GRID_SEARCH);
			$this->searchFilter = '[EMPTY]';
		}
		elseif ($searchName!='[DEFAULT]' && $searchName!='[EMPTY]')
			$this->model_user->AddUserMeta($this->userID,META_GROUP_LEAD_GRID_SEARCH,META_SELECTED_SEARCH_NAME,$searchName);
	}

	function getSetGoalsContent()
	{
		$this->_setGoalsData();
		$this->data['BASE_URL'] = base_url_http();
		$this->data['IS_AJAX_REQUEST'] = IS_AJAX_REQUEST?1:0;
		$this->load->view('user/features/view_set_goals',$this->data);
	}

	function getGoalsContent()
	{
		$this->data['BASE_URL'] = base_url_http();

		$this->data['GOALS'] = $this->model_user->GetGoalsForDisplay($this->userID);
		$addedGoals = $this->model_user->GetUserInfoFieldByUserID($this->userID,"added_goals");
		$this->data['ADDED_GOALS'] = $addedGoals == 1 ? true : false;
		$this->data['VIEW_GOALS'] = 'leads/view_goals';
		$this->data['IS_AJAX_REQUEST'] = IS_AJAX_REQUEST?1:0;

		$this->load->view('leads/view_goals',$this->data);
	}

  function getAllcontent($stats)
  {
    if($stats == 'all')
    {
      echo "sss";
    }
  }

	function getGoalsContentAndSetTime()
	{
		if ($this->input->post('seconds'))
			$secondsToAdd = $this->input->xss_clean($this->input->post('seconds'));
		else
			$secondsToAdd = 0;

		$this->model_user->SetGoalTime($this->userID,$secondsToAdd);
		$this->getGoalsContent();
	}

	function setGoalsSeconds()
	{
		$seconds = $this->input->post('seconds',true);

		if ($seconds!=false)
			$this->model_user->SetGoalSeconds($this->userID,intval($seconds));
	}

	function deleteCustomStatus()
	{
		$statusID = $this->input->xss_clean($this->input->post('statusID'));

		$this->model_statuses->DeleteCustomStatus($this->userID,$statusID);

		$this->_setPropertyData();
		$this->_setLeadStatusTypesData();
		$this->_setDataToSession();

		$this->load->view($this->data['VIEW_LEADS_STATUSES'],$this->data);
	}

	function getFollowUpContent()
	{
		$this->data['BASE_URL'] = base_url_http();
		$this->data['FOLLOWUP_TYPES'] = $this->config->item('FOLLOWUP_TYPES');

		$this->load->view('leads/view_followup',$this->data);
	}
    
    
    function getSetAppointmentContent()
	{
        $define = $this->input->post('define');    
        $status_id = (int) $this->input->post('status_id'); 
        
        $status_info = $this->model_property->get_status_info($status_id, $this->userID);
        if($status_info)
            $property_id = $status_info->PropertyID;
        else
            $property_id = $this->propertyID;
        
        if($define != "edit")
            $define = 'new';
        
        $this->data['define'] = $define;
        $this->data['status_id'] = $status_id;
           
		$this->data['BASE_URL'] = base_url_http();
        
        if($status_id > 0)
            $appointment = $this->model_property->get_property_appointment($status_id, $property_id);
        else
            $appointment = false;
            
        $this->data['appointment'] = $appointment;
        
		$this->load->view('leads/view_setappointment',$this->data);
	}
    
    function getHelpBoxContent()
	{
                $pagename = $this->uri->rsegment(3);
                switch($pagename)
                {
                    case 'import_contacts':
                        $this->data['VIEW_HELPTEXT'] = 'help/view_help_importcontacts';
                        break;
                }
		$this->load->view('leads/view_helpbox',$this->data);
	}
    
    function addSetAppointment()
    {
        $customDateString = $this->input->post('appt_date',true);
        $hour = $this->input->post('appt_hour', true);
        $min = $this->input->post('appt_min',true);
        $format = $this->input->post('appt_format',true);
        
        $define = $this->input->post('define');    
        $status_id = (int) $this->input->post('status_id'); 
        
        $status_info = $this->model_property->get_status_info($status_id, $this->userID);
        if($status_info)
            $propertyID = $status_info->PropertyID;
        else
            $propertyID = $this->propertyID;
        
        if($define != "edit")
            $define = 'new';
        
        $cancel = $this->input->post('cancel',true);
        
		$customDate = date(PHP_ISO_DATE_FORMAT,strtotime($customDateString));
		$currentDate = date(PHP_ISO_DATE_FORMAT);
        
        $property_info = $this->model_property->check_property($propertyID, $this->userID, $this->propertyType);

        if(!$property_info)
        {
            $output = array('success' => '0', 'error' => 'You are havn\'t permissions', 'define' => $define, 'status_id'=>$status_id);
            $this->output->set_output($this->json->encode($output));
			return;
        }
        
        if($hour == '' || $min == '' )
        {
            $output = array('success' => '0', 'error' => 'You must set a time.');
            $this->output->set_output($this->json->encode($output));
			return;
        }
        
        $appointment = $this->model_property->get_property_appointment($status_id, $propertyID);
        
        $appt_id = $appointment?$appointment->id:0;

        
        if($cancel == 'on' && $define == 'edit' && $status_id > 0)
        {
            if($appointment)
            {
                $this->model_property->update_set_appt($appointment->id, array('cancel' => 1));
                $output = array('success' => 1, 'define' => $define, 'status_id'=>$status_id, 'cancel'=>1);
            }
            else
                $output = array('success' => '0', 'error' => 'You are havn\'t permissions', 'define' => $define, 'status_id'=>$status_id, 'cancel'=>1);
    		
            $output['category'] = $property_info->category;
            $this->output->set_output($this->json->encode($output));
        }
        else
        {   
    		if ($customDateString=="" || ($customDate<$currentDate &&  $define != 'edit'))
    		{
                $output = array('success' => 0);
    			$this->output->set_output($this->json->encode($output));
    			return;
    		}
                       
            $params = array(
                'userID'=>$this->userID,
                'propertyID'=>$propertyID
            );
            
            
            $date_str = $customDate;
            $date_str .= ' ' . $hour. ':' . $min . ' ' . $format;
            $date_time = strtotime($date_str);

            $expired_time = date(PHP_ISO_DATE_HOUR_MINUTE_SECOND_FORMAT, $date_time);

            $data = array(
                'property_id' => $propertyID,
                'date'=>$customDate,
                'hour'=>$hour,
                'min'=>$min,
                'format'=>$format,
                'cancel'=>0,
                'expired_time' => $expired_time,
                'is_show' => 1
            );
     
            $check_same_appt = $this->model_property->check_same_appt($data, $appt_id);
            
            if(!$check_same_appt)
            {

                if($define == 'new')
                {
                    $data['first_touch'] = $property_info->first_touch;
                    $data['count_touches'] = $property_info->count_touches;
                    $set_appt_id = $this->model_property->AddSetAppointment($params, $data);
                    
                    $data = array('first_touch'=>date('Y-m-d'));
                    $this->model_property->update_property($property_info->PropertyID, $data);
                }   
                else
                {
                    $this->model_property->update_set_appt($appointment->id, $data);
                    $set_appt_id = $appointment->id;
                }
                
                $this->model_property->update_property($propertyID, array('status_property' => 'appt'));
                
                list($year, $month, $day ) = explode('-', $customDate);
                
                $new_str = '';
                $new_str .= 'Appt: ' . $month . '/' . $day . '/' . $year;
                if($hour != "" && $min != "")
                    $new_str .= " @ " . $hour . ":" . $min . " " . $format;
                $new_str .= '&nbsp;&nbsp;&nbsp;&nbsp;Outcome:';

                $output = array('success' => $set_appt_id > 0?"1":"0", 'set_appt_id' => $set_appt_id, 'define' => $define, 'status_id'=>$status_id, 'cancel'=>0, 'str'=>$new_str);
            }   
            else
                $output = array('success' => '0', 'error' => 'You already have an appointment scheduled for this time.  Please select a different time.', 'define' => $define, 'status_id'=>$status_id);
            
            $output['category'] = $property_info->category;
            $this->output->set_output($this->json->encode($output));
        }
		
    }

	function addFollowUp()
	{        

		if (isset($_POST['fldStatusID']) && isset($_POST['followup_type']) && trim($_POST['followup_type'])!="")
		{
            $statusID = intval($this->input->post('fldStatusID',true));
			if ($statusID==0)
				$statusID = null;
			$daysToAdd = $this->input->post('followup_type',true);
			$customDateString = "";

			if ($daysToAdd=="custom")
			{
				$customDateString = $this->input->post('followup_type_custom',true);

				$customDate = date(PHP_DATE_FORMAT,strtotime($customDateString));
				$currentDate = date(PHP_DATE_FORMAT);

				if ($customDateString=="" || $customDate<$currentDate)
				{
					$this->output->set_output("0");
					return;
				}
			}
			else
				$daysToAdd = intval($daysToAdd);



			if (isset($_POST['fldFollowUpPropertyID']))
				$propertyID = $this->input->post('fldFollowUpPropertyID',true);
            elseif(isset($_POST['fldFollowUpCurrApptID']) && (int) $_POST['fldFollowUpCurrApptID'] > 0 )
            {
                if($appt = $this->model_property->check_user_appt((int) $_POST['fldFollowUpCurrApptID'], $this->userID))
                    $propertyID = $appt->property_id;
                else
                    $propertyID = 0;
            }

            
            /*$check_session_follow_up = $this->model_property->check_session_follow_up($this->session->userdata('s_propertyID'));
            
            if($this->session->userdata('s_propertyID') > 0 && $this->propertyType == 'fsbo' && !$check_session_follow_up)
            {
                $propertyID = $this->session->userdata('s_propertyID');
            }*/
			if (intval($propertyID)==0)
				$propertyID = $this->propertyID;

			$success = $this->model_property->AddFollowUp($this->userID,$statusID,$propertyID,$daysToAdd,$customDateString);

			$this->output->set_output($success==true?"1":"0");
		}
	}

	function getActionDaysForCalendar()
	{
		if (!isset($_POST['year']) || !isset($_POST['month']))
			$this->output->set_output('');

		$year = $this->input->post('year',true);
		$month = $this->input->post('month',true);

		$result = $this->model_statuses->GetActionDaysForCalendar($this->userID,$this->propertyID,$year,$month);


		if ($result==null)
			$this->output->set_output('');
		else
		{
			$dates = "";
			foreach ($result as $row)
				$dates .= $row['day'].',';

			$dates = substr($dates,0,strlen($dates)-1);

			$this->output->set_output($dates);
		}
	}
    
    function getNotEmptyDays()
    {
        if (!isset($_POST['year']) || !isset($_POST['month']) || !isset($_POST['propertyType']))
			$this->output->set_output($this->json->encode(array('success' => 'error')));

		$year = $this->input->post('year',true);
		$month = $this->input->post('month',true);

        $result = $this->model_statuses->NotEmptyFSBODays($this->userID,$year,$month,$this->propertyType);

        if ($result==null)
			$this->output->set_output($this->json->encode(array('success' => 'error')));
		else
		{
			$dates = "";
			foreach ($result as $row)
				$dates .= $row['day'].',';

			$dates = substr($dates,0,strlen($dates)-1);
            
            $output['success'] = 'success';
            $output['result'] = $dates;
			$this->output->set_output($this->json->encode($output));
		}
    }
    
	function getPropertyActionDays()
	{
		if (!isset($_POST['year']) || !isset($_POST['month']) || !isset($_POST['propertyType']))
			$this->output->set_output('');

		$year = $this->input->post('year',true);
		$month = $this->input->post('month',true);
		$propertyType = $this->input->post('propertyType',true);

		$result = $this->model_statuses->GetPropertyActionDays($this->userID,$year,$month,$propertyType);

		if ($result==null)
			$this->output->set_output('');
		else
		{
			$dates = "";
			foreach ($result as $row)
				$dates .= $row['day'].',';

			$dates = substr($dates,0,strlen($dates)-1);

			$this->output->set_output($dates);
		}
	}

	function getStatsPopupContent()
	{
		$newPhoneNumberValues = $this->model_property->GetNrOfNewPhoneNumbers($this->userID);

		$newPhoneNumbers = array
		(
			array('Display'=>'Phone numbers from internet searches','Nr'=>$newPhoneNumberValues[0]),
			array('Display'=>'Phone numbers from Tax Records','Nr'=>$newPhoneNumberValues[1]),
			array('Display'=>'Phone numbers from Do Not Call registry','Nr'=>$newPhoneNumberValues[2])
		);

		$this->data['NEW_PROPERTIES_COUNT'] = $this->model_property->GetNrOfNewPropertiesForUser($this->userID);
		$nr_jljs = $this->model_property->check_jljs($this->userID);
		$this->data['PIE_CHART_NEW_PROPERTIES_URL'] = $this->model_chart->GetPieChartURL($this->data['NEW_PROPERTIES_COUNT'],-1,'Display','Nr',370,100);
		$this->data['BAR_CHART_NEW_PROPERTIES_URL'] = $this->model_chart->GetVerticalBarChartURL($this->data['NEW_PROPERTIES_COUNT'],-1,'Display','Nr',370,100,false,true);
		$this->data['BAR_CHART_NEW_PHONE_NUMBERS_URL'] = $this->model_chart->GetVerticalBarChartURL($newPhoneNumbers,-1,'Display','Nr',350,100,false,true);
		$this->data['NEWS'] = $this->model_news->GetNewsForUser($this->userID);
		$this->data['BASE_URL'] = base_url_http();
		$this->data['CURRENT_DATE'] = date(PHP_VERBOSE_DATE_FORMAT);
		$this->data['NR_OF_NEW_LEADS'] = $this->_getNrOfNewLeads($this->data['NEW_PROPERTIES_COUNT']) - $nr_jljs;

		$this->data['IS_ADMIN'] = $this->redux_auth->is_admin();
		$this->data['UPLOAD_ENABLED'] = $this->model_utils->UploadEnabled();

		$this->load->view('stats/view_stats_popup.php',$this->data);
	}

	function getWelcomePopupContent()
	{
        if($this->redux_auth->is_broker_user())
            $this->data['VIDEO_URL'] = base_url_http().'upload/videos/tour_broker.flv';
        elseif($this->redux_auth->is_fsbo_user())
            $this->data['VIDEO_URL'] = base_url_http().'upload/videos/tour_fsbo.flv';
        else
            $this->data['VIDEO_URL'] = base_url_http().'upload/videos/tour_single.flv';
            
        $this->data['BASE_URL'] = base_url_http();
        $this->load->view('main/view_welcome_popup.php',$this->data);
	}

	function updateContainerOrder()
	{
		$firstContainer = $this->input->post('FirstContainer',true);
		$secondContainer = $this->input->post('SecondContainer',true);
		$thirdContainer = $this->input->post('ThirdContainer',true);

		if ($firstContainer===false || $secondContainer===false || $thirdContainer===false)
			return;

		$this->model_user->UpdateLeadContainerOrder($this->userID,$firstContainer,$secondContainer,$thirdContainer);
	}

	function getSavedSearchList()
	{
		$this->data['SAVED_SEARCH_LIST'] = $this->model_user->GetSavedSearchList($this->userID);
		$this->data['SEARCH_NAME'] = $this->session->userdata('s_selectedSearchName');
		$this->data['BASE_URL'] = base_url_http();
		$this->load->view('leads/view_leads_savedsearchlist',$this->data);
	}

	function deleteSavedSearch()
	{
		$searchName = $this->input->post('searchNameDelete',true);

		$success = $this->model_user->DeleteLeadSearch($this->userID,$searchName);
		if ($success)
			$this->session->unset_userdata('s_selectedSearchName');

		$this->output->set_output($success===true?"1":"0");
	}

	function _setLeadGridStateColumnMetaSuffix()
	{
        if($this->propertyType == 'jljs')
        {
            if($this->session->userdata('s_current_jljs_tab') && $this->session->userdata('s_current_jljs_tab') == 'js')
                $propertyType = 'js';
            else
                $propertyType = 'jl';
        }
        else
        {
            $requestURI = str_replace('/leads/','',$_SERVER['REQUEST_URI']);
    		
            if (in_array($requestURI,$this->propertyPages))
    			$propertyType = $requestURI;
    		else
                $propertyType = $this->propertyType;
        }
            
		$this->leadGridStateColumnMetaSuffix = '_'.$propertyType;
		if ($this->adminMode)
			$this->leadGridStateColumnMetaSuffix .= '_admin';

		$this->_setDataToSession();
	}

	function saveLeadGridState()
	{
		$this->_setLeadGridStateColumnMetaSuffix();

		$stateFields = array();

		$gridFields = array_keys(self::$LEAD_LIST_FIELDS_BY_TYPE[$this->propertyType]);
		if ($this->adminMode)
		{
			$gridFields['Edit'] = array();
			$gridFields['Neighbors'] = array();
		}
		for ($i=0;$i<count($this->additionalLeadGridFields);$i++)
			$gridFields[] = $this->additionalLeadGridFields[$i];
        //print_r($_POST);
        
		foreach ($_POST as $key=>$value)
		{
			if (strpos($key,'grid_column_')===0)
			{
				$field = str_replace('grid_column_','',$key);
				if (in_array($field,$gridFields))
					$stateFields[$key.$this->leadGridStateColumnMetaSuffix] = $this->input->post($key,true);
			}
		}

		foreach ($this->additionalGridStatusFields as $field)
			if (isset($_POST[$field]))
				$stateFields[$field.$this->leadGridStateColumnMetaSuffix] = $this->input->post($field,true);
        

		$this->model_user->DeleteUserMeta($this->userID,META_LEAD_GRID_HEIGHT.$this->leadGridStateColumnMetaSuffix,META_GROUP_LEAD_GRID_STATE);
		$this->model_user->DeleteGridStateMeta($this->userID,$this->leadGridStateColumnMetaSuffix,META_GROUP_LEAD_GRID_STATE);
		$this->model_user->AddUserMetaGroup($this->userID,META_GROUP_LEAD_GRID_STATE,$stateFields,false);
	}
	/* END_REGION AJAX_REQUESTS */

	/* REGION SET_VIEW_DATA */
	function _setPropertyData()
	{
	   if($this->input->post('propertyID'))
       {
            if((int)$this->input->post('propertyID') > 0)
                $this->propertyID = $this->input->post('propertyID');
            else
                $this->propertyID = 0;
       }
        
		$this->data['PAGE'] = $this->propertyType;
       // $this->data['OWNER_DATA'] = $this->model_property->get_property_by_id($this->propertyID, $this->userID);
		$this->_setOwnerData($this->propertyID);
        $this->data['PROPERTY_INFO_TYPE'] = $this->propertyInfoType;
		$this->data['PROPERTY_TYPE'] = $this->propertyType;
		$this->data['PROPERTY_ID'] = $this->propertyID;
		$this->data['BASE_URL'] = base_url_http();
		$this->data['UPLOAD_FOLDER_PHOTOS'] = UPLOAD_FOLDER_PHOTOS;
		$this->data['LEAD_CONTAINER_ORDER'] = $this->model_user->GetLeadContainerOrder($this->userID);
	}

	function _setOwnerData_old()
	{
        //$ownerData = $this->model_property->get_property_by_id($this->propertyID, $this->userID);
       
		$ownerData = $this->model_property->GetOwnerData($this->propertyType,$this->propertyID,$this->followUpMode);
		if (!$ownerData) {
			$ownerData = $this->model_property->GetOwnerData("other",$this->propertyID,$this->followUpMode);
		}

		$this->data['OWNER_DATA'] = $ownerData;

		if (isset($this->data['OWNER_DATA']['Address']) && isset($this->data['OWNER_DATA']['TaxRecordsMailAddress']) && trim($this->data['OWNER_DATA']['Address'])==trim($this->data['OWNER_DATA']['TaxRecordsMailAddress']))
			$this->data['MAIL_ADDRESS_SAME'] = true;
		else
			$this->data['MAIL_ADDRESS_SAME'] = false;

		list($this->data['PREVIOUS_PROPERTY_ID'], $this->data['NEXT_PROPERTY_ID']) = $this->getPrevAndNextPropertyIDs(false);

		$this->data['PROPERTY_ID'] = $this->propertyID;
		$this->data['HAS_NEIGHBORS'] = $this->model_property->GetNrOfNeighbors($this->propertyType,$this->propertyID,$this->userID)>0;
		$this->data['HAS_PHOTOS'] = $this->model_property->GetNrOfImagesForProperty($this->propertyID)>0;
		$this->data['WHITE_PAGES_DATA'] = $this->model_property->GetWhitePagesData($this->propertyID);

		if (count($this->data['OWNER_DATA'])>0)
		{
			$this->data['OWNER_DATA']['Address'] = $this->model_utils->AddWhiteSpace($this->data['OWNER_DATA']['Address'],self::$MAX_OWNERDATA_NR_OF_CHARS);
			$this->data['OWNER_DATA']['TaxRecordsName'] = $this->model_utils->AddWhiteSpace($this->data['OWNER_DATA']['TaxRecordsName'],self::$MAX_OWNERDATA_NR_OF_CHARS);
		}

		for ($whitePagesCounter=0;$whitePagesCounter<count($this->data['WHITE_PAGES_DATA']);$whitePagesCounter++)
			$this->data['WHITE_PAGES_DATA'][$whitePagesCounter]['Name'] = $this->model_utils->AddWhiteSpace($this->data['WHITE_PAGES_DATA'][$whitePagesCounter]['Name'],self::$MAX_OWNERDATA_NR_OF_CHARS);

		if ($this->followUpMode)
			$this->data['PROPERTY_TYPE'] = $this->model_property->GetPropertyTypeNameFromID($ownerData['LeadTypeID']);


		$fieldsToAddWhiteSpace = array
								(
									'MLSName'=>20,
									'OccupantName'=>17,
									'FSBOName'=>20,
									'AgentName'=>17,
									'AgentBroker'=>17
								);
		foreach ($fieldsToAddWhiteSpace as $field=>$maxChars)
			if (isset($this->data['OWNER_DATA'][$field]))
				$this->data['OWNER_DATA'][$field] = $this->model_utils->AddWhiteSpace($this->data['OWNER_DATA'][$field],$maxChars);
	}
    
    function _setOwnerData($propertyID = 0)
	{
       // echo $this->propertyID; die;
        
        if($propertyID == 0)
            $propertyID = $this->propertyID;
        
        $has_contacts = $this->model_user->GetUserInfoFieldByUserID($this->userID, 'has_contacts');
        $this->data['has_contacts'] = $has_contacts;
        
           
        $ownerData = $this->model_property->get_property_by_id($propertyID, $this->userID, $this->propertyType);
        
        $extraData = $this->model_property->get_property_extra_data($propertyID);
        
        //print_r($extraData); 
        
        $extra_data_sort = array(
                'phones' => array(),
                'addresses' => array(),
                'dates' => array(),
                'websites' => array(),
                'ims' => array(),
                'emails' => array(),
                'customs' => array()
            );
        
        if($ownerData)
        {
            if($ownerData->relation > 0)
            {
                $typeInfo = $this->model_property->get_type_info_by_id($ownerData->relation);
                
                if($typeInfo)
                    $ownerData->relation_value = $typeInfo->value;
                else
                    $ownerData->relation_value = '';
            }
            else
                $ownerData->relation_value = '';
             
            if($ownerData->category > 0)
            {
                $typeInfo = $this->model_property->get_type_info_by_id($ownerData->category);
                if($typeInfo)
                    $ownerData->category_value = $typeInfo->value;
                else
                    $ownerData->category_value = '';
            }
            else
                $ownerData->category_value = '';
            
            
            $main_data = $this->get_main_data($extraData);
            $mainID = isset($main_data['address_id']) ? $main_data['address_id'] : 0;

            foreach($extraData as $key=>$data)
            {
                if($data->define == 'phone')
                    $extra_data_sort['phones'][$key] = $data;
                elseif($data->define == 'address')
                {
                    $extra_data_sort['addresses'][$key] = $data;
                    $coordenates = $this->_get_coordinates($data);
                    $extra_data_sort['addresses'][$key]->latitude = $coordenates['latitude'];
                    $extra_data_sort['addresses'][$key]->longitude = $coordenates['longitude'];
                    
                    if($data->id == $mainID)
                        $extra_data_sort['addresses'][$key]->is_main = 1;
                    else
                        $extra_data_sort['addresses'][$key]->is_main = 0;
                    
                }
                elseif($data->define == 'email')
                    $extra_data_sort['emails'][$key] = $data;
                elseif($data->define == 'website')
                    $extra_data_sort['websites'][$key] = $data;
                elseif($data->define == 'im')
                    $extra_data_sort['ims'][$key] = $data;
                elseif($data->define == 'date')
                    $extra_data_sort['dates'][$key] = $data;
                elseif($data->define == 'custom')
                    $extra_data_sort['customs'][$key] = $data;
            }
        }   
        $this->data['OWNER_DATA'] = $ownerData;
        $this->data['extra_data'] = $extra_data_sort;
        $this->data['PROPERTY_ID'] = $propertyID;
        $this->data['HAS_NEIGHBORS'] = 0;
        $this->data['HAS_PHOTOS'] = 0;
        //print_r($extra_data_sort);
        list($this->data['PREVIOUS_PROPERTY_ID'], $this->data['NEXT_PROPERTY_ID']) = $this->getPrevAndNextPropertyIDs(false);
        
    }
    
	function getPrevAndNextPropertyIDs($ajaxMode = true)
	{
	   $params = array();
	   $folder = isset($_POST['folder']) && $this->model_property->check_folder((int)$_POST['folder'],$this->userID)?(int)$_POST['folder']:0;
       $category = isset($_POST['category']) && $this->model_property->check_field_type($this->userID, (int)$_POST['category'])?(int)$_POST['category']:0;
       $relation = isset($_POST['relation']) && $this->model_property->check_field_type($this->userID, (int)$_POST['relation'])?(int)$_POST['relation']:0;
        $selected_date = $this->selectedDate != "" ? $this->selectedDate : 0;
        
    
        if($folder > 0)
            $params['folder'] = $folder;
        if($category > 0)
            $params['category'] = $category;
        if($relation > 0)
            $params['relation'] = $relation;
        if($selected_date != 0)
            $params['selected_date'] = $selected_date;
       
		$params_add = $this->get_sort_params();
        
        
        $params['sortname'] = $params_add['sortname'];
        $params['sortorder'] = $params_add['sortorder'];
        
        $nextPropertyID = $this->getNextPropertyID(false, $params);
		$previousPropertyID = $this->getPreviousPropertyID(false, $params);

		$prevAndNext = array($previousPropertyID,$nextPropertyID);
        
		if ($ajaxMode)
			$this->output->set_output(json_encode($prevAndNext));
		else
			return $prevAndNext;
	}
    
	function getNextPropertyID($ajaxMode = true, $params = array())
	{
		$nextPropertyID = $this->input->post('nextPropertyID',true);

		if ($nextPropertyID===false || $nextPropertyID=='-1')
        {
            $nextPropertyID = 0;

           //$params['gird_debug'] = 1;
            //print_r($params); die;
            $properties = $this->model_property->get_properties($this->propertyType, $this->userID, $params, false);
            
            if(count($properties['records']) > 0)
            {
                if($this->propertyType == 'fsbo')
                {
                    $ids = array();
                    $new_records = array();
                    
                    foreach($properties["records"] as $record)
                    {
                        $new_records[$record->PropertyID] = $record;
                        $ids[] = $record->PropertyID;
                    }
                    if( count($ids) > 0 )
                        $aditional_data = $this->model_property->get_user_edit_data($ids, $this->userID);
                    else
                        $aditional_data = array();
        
                    foreach($aditional_data as $ad_data)
                    {
                        $new_records[$ad_data->fsbo_parent_id] = $ad_data;
                    }
                    
                    $properties['records'] = $new_records;
                }
                
                $properties['records'] = array_values($properties['records']);
                
                foreach($properties['records'] as $k=>$property)
                {
                    if($property->PropertyID == $this->propertyID)
                    {
                        if(isset($properties['records'][$k+1]))
                            $nextPropertyID = $properties['records'][$k+1]->PropertyID;
                            
                        break;         
                    }
                }
            }
        }  

		if ($ajaxMode)
			$this->output->set_output($nextPropertyID);
		else
			return $nextPropertyID;
	}

	function getPreviousPropertyID($ajaxMode = true, $params = array())
	{
		$previousPropertyID = $this->input->post('previousPropertyID',true);

		if ($previousPropertyID===false || $previousPropertyID=='-1')
		{
            $previousPropertyID = 0;
            $properties = $this->model_property->get_properties($this->propertyType, $this->userID, $params, false);

            if(count($properties['records']) > 0)
            {
                if($this->propertyType == 'fsbo')
                {
                    $ids = array();
                    $new_records = array();
                    
                    foreach($properties["records"] as $record)
                    {
                        $new_records[$record->PropertyID] = $record;
                        $ids[] = $record->PropertyID;
                    }
                    
                   // print_r($new_records);die;
                    
                    if( count($ids) > 0 )
                        $aditional_data = $this->model_property->get_user_edit_data($ids, $this->userID);
                    else
                        $aditional_data = array();
                    
                    
                    foreach($aditional_data as $ad_data)
                    {
                       // echo $ad_data->fsbo_parent_id;
                        $new_records[$ad_data->fsbo_parent_id] = $ad_data;
                    }

                    $properties['records'] = $new_records;
                }
                
                $properties['records'] = array_values($properties['records']);
                
                foreach($properties['records'] as $k=>$property)
                {
                    if($property->PropertyID == $this->propertyID)
                    {
                        if(isset($properties['records'][$k-1]))
                            $previousPropertyID = $properties['records'][$k-1]->PropertyID;
                            
                        break;         
                    }
                }
            }
		}

		if ($ajaxMode)
			$this->output->set_output($previousPropertyID);
		else
			return $previousPropertyID;
	}

	function _setLeadsListData()
	{
       $followPerPage = $this->model_user->GetFollowUpPerPageItems($this->userID);
       if($followPerPage == 0)
            $followPerPage = 10;
            
       $new_current_page = $this->get_page_by_property($followPerPage);
       
       
		// set if the page has to be the one on which the current propertyID is located
		if ($this->input->xss_clean($this->input->post('pageByID'))=="1")
			$this->pageNr = $this->model_property->GetPageNrOfProperty($this->propertyType,$this->propertyID,NR_OF_LEAD_ROWS,$this->userID,$this->adminMode,$this->followUpMode,$this->flexigridOptions);

		//$this->data['PROPERTY_LIST'] = $this->model_property->GetPaginatedProperties($this->propertyType,NR_OF_LEAD_ROWS,$this->pageNr,$this->userID);
        $this->data['new_current_page'] = $new_current_page;
		$this->data['VIEW_ADD_NEW_LEAD'] = 'leads/view_leads_addnew';
		$this->data['CURRENT_PAGE'] = $this->pageNr;
		$this->data['CURRENT_DISPLAY_DATE'] = date(PHP_VERBOSE_DATE_FORMAT,strtotime($this->selectedDate));
		//$this->data['CURRENT_DISPLAY_DATE'] = date(PHP_VERBOSE_DATE_FORMAT,time());
		$this->data['SELECTED_DATE'] = $this->selectedDate;
		$this->data['CURRENT_DATE'] = date('Y/m/d',strtotime(PREVIOUS_DAY));
		$this->data['CURRENT_VERBOSE_DATE'] = date(PHP_VERBOSE_DATE_FORMAT);
		$this->data['PROPERTY_ID'] = $this->propertyID;
		$this->data['SHOW_SELECTED_DATE'] = true; //($this->searchFilter=='');
		$this->data['SAVED_SEARCH_LIST'] = $this->model_user->GetSavedSearchList($this->userID);
		$this->data['SHOW_ADD_NEW_LEAD'] = true;
		$this->data['ADMIN_MODE'] = $this->adminMode;
		$this->data['FOLLOWUP_MODE'] = $this->followUpMode;
		$this->data['VIEW_SAVED_SEARCH_LIST'] = 'leads/view_leads_savedsearchlist';
		$this->data['SEARCH_NAME'] = $this->model_user->GetUserMetaItem($this->userID,META_SELECTED_SEARCH_NAME,META_GROUP_LEAD_GRID_SEARCH);
        
        $folders = $this->model_property->get_user_folders($this->userID, $this->propertyType);
        $this->data['folders'] = $folders;
        
        $this->data['types'] = $this->_get_field_types();
        
		$this->_setGridData_new();
	}

	function _setPropertyInfoData()
	{   
	   if($this->input->post('propertyID') && $this->input->post('propertyID') == 0)
            $this->propertyID = 0;
            
        if($this->propertyType == 'fsbo' || $this->propertyType == 'expireds' || $this->propertyType == 'nod' || $this->propertyType == 'jljs')
        {
            $photos = $this->model_property->get_images_for_contact($this->propertyID);
            
            if(count($photos) == 0)
                $this->data['PHOTOS'] = $photos;
            else
                $this->data['PHOTOS'] = $this->check_image_exist($photos);
        }
            
    
        switch ($this->propertyInfoType)
		{
			case "adinfo":
				$this->_setPropertyAdInfoMainData();break;
            case "expiredinfo":
				$this->_setPropertyAdInfoMainData();break;
            case "nodinfo":
				$this->_setPropertyAdInfoMainData();break;
            case "jljsinfo":
				$this->_setPropertyAdInfoMainData();break;
            case "info":
				$this->_setPropertyInfoMainData();break;
			case "photos":
				$this->_setPropertyInfoPhotosData();break;
			case "map":
				$this->_setPropertyInfoMapData();break;
			case "history":
				$this->_setPropertyInfoHistoryData();break;
			case "neighbors":
				$this->_setPropertyInfoNeighborsData();break;
		}
        $this->data['update_status'] = 0;
		$this->data['PROPERTY_ID'] = $this->propertyID;
		$this->data['VIEW_PROPERTY_INFO'] = 'leads/view_leads_propertyinfo_'.$this->propertyInfoType;
		$this->data['HAS_NEIGHBORS'] = $this->model_property->GetNrOfNeighbors($this->propertyType,$this->propertyID,$this->userID)>0;
		$this->data['HAS_PHOTOS'] = $this->model_property->GetNrOfImagesForProperty($this->propertyID)>0;
        
        
	}

	function _setPropertyInfoMainData()
	{
		$this->data['PROPERTY_INFO_LABELS'] = $this->config->item('PROPERTY_INFO_FIELDS');
		$propertyInfoFieldsByType = self::$PROPERTY_INFO_FIELDS_BY_TYPE[$this->propertyType];

		$this->data['PROPERTY_INFO_FIELDS'] = $propertyInfoFieldsByType['FIELDS'];
		$this->data['FIELD_SEPARATOR_INDEXES'] = $propertyInfoFieldsByType['SEPARATORS'];

		$this->data['PROPERTY_ID'] = $this->propertyID;
		$this->data['PROPERTY_INFO'] = $this->model_property->GetPropertyInfo($this->propertyType,$this->propertyID,false,$this->followUpMode);
        
        $this->_setOwnerData($this->propertyID);

	}

    function _setPropertyAdInfoMainData()
    {
        $this->data['PROPERTY_ID'] = $this->propertyID;
        $this->_setOwnerData($this->propertyID);
    }

	function _setPropertyInfoPhotosData()
	{
		$this->_setPropertyData();
		$this->_setDataToSession();

		$photos = $this->model_property->get_images_for_contact($this->propertyID);
        if(count($photos) == 0)
            $this->data['PHOTOS'] = $photos;
        else
            $this->data['PHOTOS'] = $this->check_image_exist($photos);

		if (count($this->data['PHOTOS'])>0)
			$this->data['FIRST_IMAGE_EXISTS'] = file_exists(base_url_http().UPLOAD_FOLDER_FSBO.$this->data['PHOTOS'][0]->name);
		else
			$this->data['FIRST_IMAGE_EXISTS'] = false;
            
		$this->data['PROPERTY_ID'] = $this->propertyID;
	}

	function _setPropertyInfoMapData()
	{
        $this->_setOwnerData($this->propertyID);
		$propertyInfo = $this->model_property->GetPropertyInfo($this->propertyType,$this->propertyID,false,$this->followUpMode);

		if ($propertyInfo != null)
		{
            $extraData = $this->model_property->get_property_extra_data($this->propertyID);
          
            $main_data = $this->get_main_data($extraData);
            $mainID = isset($main_data['address_id']) ? $main_data['address_id'] : 0;
            
            if($mainID > 0)
            {
    			$country = "United States";
    			$state = $main_data["stateID"];
    			$city = $main_data["city"];
    			$street = $main_data["address"];
    			$zip = $main_data["zip"];
    
    			$coordinates = $this->model_property->GetCoordinatesOfProperty($country,$state,$city,$street,$zip);
    
    			$latitude = $coordinates['latitude'];
    			$longitude = $coordinates['longitude'];
    
    			$this->data['LATITUDE'] = $latitude;
    			$this->data['LONGITUDE'] = $longitude;
    			$this->data['MAP_URL']='http://maps.google.com/staticmap?format=gif&maptype=roadmap&center='.$latitude.','.$longitude.'&zoom=14&size=640x440&markers='.$latitude.','.$longitude.',red&key='.MAP_KEY.'&r='.rand();
            }
            else
            {
                $this->data['LATITUDE'] = 39.0138486983;
                $this->data['LONGITUDE'] = -98.4889984131;
            }
		}
		else
		{
			$this->data['LATITUDE'] = 39.0138486983;
            $this->data['LONGITUDE'] = -98.4889984131;
		}
		$this->data['PROPERTY_ID'] = $this->propertyID;
	}
    
    function _get_coordinates($address)
    {
            $country = "United States";
			$state = $address->state;
            $city = $address->city;
			$street = $address->street;
			$zip = $address->zip;

			$coordinates = $this->model_property->GetCoordinatesOfProperty($country,$state,$city,$street,$zip);

			$latitude = $coordinates['latitude'];
			$longitude = $coordinates['longitude'];
            
            return array('latitude'=>$latitude, 'longitude'=>$longitude);
    }

	function _setPropertyInfoHistoryData()
	{
		$this->_setLeadStatusTypesData();
		$this->_setLeadsStatusHistoryData(null);
	}

	function _setLeadStatusTypesData()
	{
		$this->data['VIEW_LEADS_STATUSES'] = 'leads/view_leads_statuses';
		$this->data['DEFAULT_NOTE_TEXT'] = DEFAULT_ACTION_NOTE_TEXT;
		$this->data['PROPERTY_ID'] = $this->propertyID;
        
        $propertyInfo = $this->model_property->get_property_by_id($this->propertyID, $this->userID, $this->propertyType);
        
        $LEAD_STATUS_TYPES = array
		(
            #!!! do not modify the 'name' because images are loaded by this
			array('name'=>'attempt','values' => $this->model_statuses->GetStatusesForUser($this->userID, "attempt", isset($propertyInfo->category)?(int)$propertyInfo->category:0)),
			array('name'=>'outcome','values' => $this->model_statuses->GetStatusesForUser($this->userID,"outcome", isset($propertyInfo->category)?(int)$propertyInfo->category:0)),
			array('name'=>'action','values' => $this->model_statuses->GetStatusesForUser($this->userID, "action", isset($propertyInfo->category)?(int)$propertyInfo->category:0))
		);
        
        $this->data['LEAD_STATUS_TYPES'] = $LEAD_STATUS_TYPES;
	}

	function _setLeadsStatusHistoryData($propertyID = null)
	{
		$this->data['BASE_URL'] = base_url_http();
		$this->data['CURRENT_DATE'] = date('Y/m/d');
		$this->data['VIEW_LEAD_STATUS_HISTORY'] = 'leads/view_leads_status_history';
		$this->data['PROPERTY_ID'] = $this->propertyID;

		if ($propertyID==null)
			$propertyID = $this->propertyID;

		$leadStatuses = $this->model_statuses->GetLeadStatusHistory($propertyID,$this->userID);
		$leadNotes = $this->model_statuses->GetLeadNotes($propertyID,$this->userID);

		$this->data['LEAD_STATUS_HISTORY'] = array();

		$i = 0;
		$j = 0;
		$k = 0;
		$statusTimestamp = -1;
		$noteTimestamp = -1;

		while ($i<count($leadStatuses) || $j<count($leadNotes))
		{
			if ($i<count($leadStatuses))
				$statusTimestamp = $leadStatuses[$i]['Timestamp'];
			else
				$statusTimestamp = -1;

			if ($j<count($leadNotes))
				$noteTimestamp = $leadNotes[$j]['Timestamp'];
			else
				$noteTimestamp = -1;

			if ($statusTimestamp>$noteTimestamp)
			{
				$this->data['LEAD_STATUS_HISTORY'][$k] = $leadStatuses[$i];
				$this->data['LEAD_STATUS_HISTORY'][$k]['Type'] = 'status';
				$i++;
			}
			else
			{
				$this->data['LEAD_STATUS_HISTORY'][$k] = $leadNotes[$j];
				$this->data['LEAD_STATUS_HISTORY'][$k]['Type'] = 'note';
				$j++;
			}

			$k++;
		}
       
	}

	function _setPropertyInfoNeighborsData()
	{
		if ($this->propertyID!=0)
		{

			$this->data['NEIGHBOR_DATA']['NEIGHBOR'] = $this->model_property->GetPaginatedNeighbors($this->propertyType,$this->propertyID,1,$this->pageNrNeighbors,$this->userID);

			if (count($this->data['NEIGHBOR_DATA']['NEIGHBOR']))
			{
				$this->neighborID = $this->data['NEIGHBOR_DATA']['NEIGHBOR']['NeighborID'];
				$this->data['NEIGHBOR_DATA']['NEIGHBOR_ID'] = $this->neighborID;
				$this->data['NEIGHBOR_DATA']['NR_OF_NEIGHBORS'] = $this->model_property->GetNrOfNeighbors($this->propertyType,$this->propertyID,$this->userID);
				$this->data['NEIGHBOR_DATA']['PREVIOUS_NEIGHBOR_ID'] = $this->model_property->GetPreviousNeighborID($this->propertyType,$this->propertyID,$this->neighborID,$this->userID);
				$this->data['NEIGHBOR_DATA']['NEXT_NEIGHBOR_ID'] = $this->model_property->GetNextNeighborID($this->propertyType,$this->propertyID,$this->neighborID,$this->userID);
				$this->data['NEIGHBOR_DATA']['PAGE_NR_NEIGHBOR'] = $this->pageNrNeighbors;
			}
			else
			{
				$this->data['NEIGHBOR_DATA']['NR_OF_NEIGHBORS'] = 0;
				$this->data['NEIGHBOR_DATA']['NEIGHBOR_ID'] = -1;
				$this->neighborID = -1;
				$this->data['NEIGHBOR_DATA']['PREVIOUS_NEIGHBOR_ID']='';
				$this->data['NEIGHBOR_DATA']['NEXT_NEIGHBOR_ID']='';
				$this->data['NEIGHBOR_DATA']['PAGE_NR_NEIGHBOR'] = 0;
			}

			$this->data['NEIGHBOR_DATA']['BASE_URL'] = base_url_http();
		}
		$this->_setLeadStatusTypesData();
		$this->_setLeadsStatusHistoryData($this->neighborID);

		$this->data['VIEW_LEAD_NEIGHBOR'] = 'leads/view_leads_neighbor';
		$this->data['PROPERTY_ID'] = $this->propertyID;
	}

    function _setGridData_new()
    {
        $colModel = array();
        
        $gridStatuses = $this->model_user->GetUserMetaGroup($this->userID,META_GROUP_LEAD_GRID_STATE);

		$fieldSettings = self::$LEAD_LIST_FIELDS_BY_TYPE[$this->propertyType];
        
        if($this->propertyType == 'jljs')
        {
            if($this->session->userdata('s_current_jljs_tab') && $this->session->userdata('s_current_jljs_tab') == 'js')
                unset($fieldSettings['ListPrice']);
            else
                unset($fieldSettings['SoldPrice']);
        }
        
       // print_r($fieldSettings); die;
       // $colModelAll['PropertyID'] = array('PropertyID',40,TRUE,'center',0,TRUE);
		$colModelAll['smart_search'] = array('Smart Search',40,TRUE,'center',2,TRUE);
		$colModelAll['check_box_all'] = array('<input type="checkbox" id="check_main" onclick="select_all();">',10, FALSE, 'center',0);
		$colModelAll['is_favorite'] = array('<img id="main_header_star" src="/images/grid/star-dark.png">',10, FALSE, 'center',0);
        $colModelAll['f_date'] = array('F-Date',40, TRUE, 'center',1);
		$colModelAll['name'] = array('Name',40,TRUE,'center',1);
		$colModelAll['company'] = array('Company',40,TRUE,'center',1);
		$colModelAll['phone'] = array('Phone',74,FALSE,'center',1);
		$colModelAll['email'] = array('Email',146,FALSE,'center',1);
		$colModelAll['im'] = array('IM',40, FALSE,'center',1);
		if($this->propertyType == 'fsbo')
            $colModelAll['address'] = array('Location',150, FALSE, 'center',1);        
        else        
            $colModelAll['address'] = array('Address',150, FALSE, 'center',1);
                
		$colModelAll['city'] = array('City',40, FALSE, 'center',1);
		$colModelAll['state'] = array('State',40, FALSE, 'center',1);
		$colModelAll['zip'] = array('ZIP',40, FALSE, 'center',1);
		$colModelAll['relationship'] = array('Relationship',40, FALSE, 'center',1);
		$colModelAll['category'] = array('Category',40, FALSE, 'center',1);
		$colModelAll['folder'] = array('Folder',40, FALSE, 'center',1);
		$colModelAll['status'] = array('Status',40, FALSE, 'center',1);
		$colModelAll['motivation'] = array('Mot',40, TRUE, 'center',1);
		$colModelAll['home_price'] = array('Home Price',40, TRUE, 'center',1);
		$colModelAll['post_date'] = array('Post Date',40, TRUE, 'center',1);
		$colModelAll['delete'] = array('Delete',40, FALSE, 'center',0);
        
        $colModelAll['Address'] = array('Address',150, FALSE, 'center',1);
		$colModelAll['City'] = array('City',40, FALSE, 'center',1);
		$colModelAll['StateID'] = array('State',40, FALSE, 'center',1);
		$colModelAll['ZIP'] = array('ZIP',40, FALSE, 'center',1);
        
		$colModelAll['MLSNr'] = array('MLS #',56, FALSE, 'center',0);
		$colModelAll['Restrictions'] = array('Restrictions',63, FALSE, 'center',0);
		$colModelAll['TaxRecordsName'] = array('Name',182, FALSE, 'center',0);
		$colModelAll['DNC'] = array('DNC',20, FALSE, 'center',0);
		$colModelAll['Occupancy'] = array('Occupancy',56, FALSE, 'center',0);
		$colModelAll['OffMarketDate'] = array('Off Market Date',30, FALSE, 'center',0);
		$colModelAll['CloseEscrow'] = array('Close Date',30, FALSE, 'center',0);
		$colModelAll['ListPrice'] = array('List Price',54, FALSE, 'center',0);
		$colModelAll['SoldPrice'] = array('Sold Price',54, FALSE, 'center',0);
		$colModelAll['ParcelNumber'] = array('Parcel #',54, FALSE, 'center',0);

		$colModelAll['Source'] = array('Source',30, FALSE, 'center',0);
		$colModelAll['YearBuilt'] = array('Year',30, FALSE, 'center',0);
		$colModelAll['Style'] = array('Style',30, FALSE, 'center',0);
		$colModelAll['Status'] = array('Status',30, FALSE, 'center',0);
		$colModelAll['UnitDesignator'] = array('Unit Designator',30, FALSE, 'center',0);
		$colModelAll['UnitNumber'] = array('Unit Number',30, FALSE, 'center',0);
		$colModelAll['TaxRecordsMailAddress'] = array('Tax Records Mail Address',30, FALSE, 'center',0);
		$colModelAll['NODRecordingDate'] = array('Recording Date',30, FALSE, 'center',0);
		$colModelAll['NODFilingDate'] = array('Filing Date',30, FALSE, 'center',0);
		$colModelAll['NODDefaultAmount'] = array('Default Amount',30, FALSE, 'center',0);
		$colModelAll['NrOfBeds'] = array('Bedrooms',30, FALSE, 'center',0);
		$colModelAll['NrOfBaths'] = array('Bathrooms',30, FALSE, 'center',0);
		$colModelAll['BuildingSize'] = array('Est. SqFt',30, FALSE, 'center',0);
		$colModelAll['LotSize'] = array('Lot Size',30, FALSE, 'center',0);

        foreach($fieldSettings as $k=>$val)
        {
            $colData['defaultwidth'][$k] = $val["WIDTH"];
            if(isset($colModelAll[$k]))
            {
                $colModelAll[$k][1] = $val["WIDTH"];
                $colModelAll[$k][5] = $val["VISIBILITY"];
            }
                
        }
                  
        $followPerPage = $this->model_user->GetFollowUpPerPageItems($this->userID);
        
        if($followPerPage > 0)
            $nrOfLeadRows = $followPerPage;
		elseif ($this->session->userdata('s_searchLimit')!=false && $this->searchFilter!="")
			$nrOfLeadRows = intval($this->session->userdata('s_searchLimit',true));
		else
			$nrOfLeadRows = NR_OF_LEAD_ROWS;
        
        if($this->propertyType == 'followups')
            $sortname = 'f_date';
        else
            $sortname = 'post_date';
        
            
        $sortorder = 'asc';
        $height = 390;
        
        $new_sort_fields = array();
        
        if ($gridStatuses!=null)
		{
		  
            if($this->propertyType == 'jljs')
            {
                if($this->session->userdata('s_current_jljs_tab') && $this->session->userdata('s_current_jljs_tab') == 'js')
                    $propertyType = 'js';
                else
                    $propertyType = 'jl';
            }
            else
                $propertyType = $this->propertyType;
            
            //echo $propertyType; die;
			foreach ($gridStatuses as $status)
			{
				$key = $status['MetaKey'];
                
				if (strpos($key,'_'.$propertyType)!==false)
				{

                    $key = str_replace($this->leadGridStateColumnMetaSuffix,'',$key);

					if (strpos($key,'grid_column_')===0)
					{
						$field = str_replace('grid_column_','',$key);

						if (array_key_exists($field, $colModelAll))
						{
							$values = explode('|',$status['MetaValue']);
							$width = intval($values[0]);
							$visibility = intval($values[1]);

							if ($width!=0)
								$colModelAll[$field][1] = $width;
							else
								$colModelAll[$field][1] = $colData['defaultwidth'][$field];

							$colModelAll[$field][5] = $visibility;
                            
                            if(isset($fieldSettings[$field]))
                                $new_sort_fields[$field] = $fieldSettings[$field];
                            
						}
					}
                    
				}

                if($key == 'grid_sort')
                    $sortname = $status['MetaValue'];
                if($key == 'grid_sort_order')
                    $sortorder = $status['MetaValue'];
                if($key == 'grid_height')
                    $height = $status['MetaValue'];
			}
        }

        if(count($new_sort_fields) > 0)
            $fieldSettings = $new_sort_fields;
        
            
        foreach($fieldSettings as $key=>$field)
        {
            if(isset($colModelAll[$key]))
                $colModel[$key] = $colModelAll[$key];
        }
		/*
		 * Aditional Parameters
		 */
         
        if($this->propertyType == 'fsbo')
        {
            $dublicate = $this->model_property->check_dublicate_row($this->propertyID, $this->userID);
            if($dublicate)
                $this->propertyID = $dublicate->PropertyID;
                
           // echo $this->propertyID;
            
        } 
         
		$gridParams = array
		(
			'width' => 'auto',
			'height' => $height,
			'rp' => $nrOfLeadRows,
			'rpOptions' => '[10,'.NR_OF_LEAD_ROWS.',20,25,40,100]',
			'pagestat' => 'Displaying: {from} to {to} of {total} contacts.',
			'blockOpacity' => 0.5,
			'showTableToggleBtn' => true,
            'current_property_id' => $this->propertyID
            
		);

        $grid_js = build_grid_js('lead_grid', site_url("/leads/buildGrid_new"),$colModel,$sortname,$sortorder,$gridParams,null);                
		$this->data['LEAD_GRID_JS'] = $grid_js;
        
        $gridHeight = $this->model_user->GetUserMetaItem($this->userID,META_LEAD_GRID_HEIGHT.'_'.$this->propertyType,META_GROUP_LEAD_GRID_STATE);
		
        if ($gridHeight!=null)
			$this->data['LEAD_GRID_HEIGHT'] = $gridHeight;
    }

	function _setGridData()
	{
		//ver lib
		/*
		 * 0 - display name
		 * 1 - width
		 * 2 - sortable
		 * 3 - align
		 * 4 - searchable (2 -> yes and default, 1 -> yes, 0 -> no)
		 */

		$gridStatuses = $this->model_user->GetUserMetaGroup($this->userID,META_GROUP_LEAD_GRID_STATE);
        
		$colVisibility = array();
		$colData = array();
		$defaultColData = array();
		$width = 0;
		$visibility = 1;

		$fieldSettings = self::$LEAD_LIST_FIELDS_BY_TYPE[$this->propertyType];

		if ($this->adminMode)
		{
			$fieldSettings['Edit'] = array('WIDTH'=>18,'VISIBILITY'=>0);
			$fieldSettings['Neighbors'] = array('WIDTH'=>16,'VISIBILITY'=>0);
		}

		if (isset(self::$LEAD_LIST_FIELDS_BY_TYPE[$this->propertyType]))
			$fields = array_keys($fieldSettings);
		else
			$fields = array();

		foreach ($fields as $field)
			$defaultColData['defaultwidth'][$field] = $fieldSettings[$field]['WIDTH'];

		/*$defaultColData['defaultwidth']['FollowUpDate'] = 30;
		$defaultColData['defaultwidth']['Type'] = 30;
		$defaultColData['defaultwidth']['Status'] = 55;
		$defaultColData['defaultwidth']['DNC'] = 20;
		$defaultColData['defaultwidth']['TaxRecordsName'] = 94;
		$defaultColData['defaultwidth']['Phone'] = 65;
		$defaultColData['defaultwidth']['Email'] = 65;
		$defaultColData['defaultwidth']['Address'] = 129;
		$defaultColData['defaultwidth']['City'] = 80;
		$defaultColData['defaultwidth']['ZIP'] = 40;
		$defaultColData['defaultwidth']['Occupancy'] = 50;
		$defaultColData['defaultwidth']['MLSNr'] = 50;
		$defaultColData['defaultwidth']['OffMarketDate'] = 62;
		$defaultColData['defaultwidth']['ListPrice'] = 75;
		$defaultColData['defaultwidth']['Restrictions'] = 65;
		$defaultColData['defaultwidth']['Source'] = 35;
		$defaultColData['defaultwidth']['YearBuilt'] = 35;
		$defaultColData['defaultwidth']['Style'] = 35;
		$defaultColData['defaultwidth']['UnitDesignator'] = 30;
		$defaultColData['defaultwidth']['UnitNumber'] = 30;
		$defaultColData['defaultwidth']['StateID'] = 30;
		$defaultColData['defaultwidth']['TaxRecordsMailAddress'] = 30;
		$defaultColData['defaultwidth']['NODRecordingDate'] = 30;
		$defaultColData['defaultwidth']['NODFilingDate'] = 30;
		$defaultColData['defaultwidth']['NODDefaultAmount'] = 30;
		$defaultColData['defaultwidth']['NODOriginalMortgageAmount'] = 30;
		$defaultColData['defaultwidth']['NrOfBeds'] = 30;
		$defaultColData['defaultwidth']['NrOfBaths'] = 30;
		$defaultColData['defaultwidth']['BuildingSize'] = 30;
		$defaultColData['defaultwidth']['LotSize'] = 30;
		$defaultColData['defaultwidth']['Delete'] = 30;*/

		if (!empty($fields))
			foreach ($fields as $field)
				$colData['defaultwidth'][$field] = $defaultColData['defaultwidth'][$field];
		else
			$colData['defaultwidth'] = $defaultColData['defaultwidth'];

		$colData['align']['SmartSearch'] = 'center';
		$colData['align']['FollowUpDate'] = 'center';
		$colData['align']['Type'] = 'center';
		$colData['align']['Status'] = 'center';
		$colData['align']['DNC'] = 'center';
		$colData['align']['TaxRecordsName'] = 'left';
		$colData['align']['Phone'] = 'left';
		$colData['align']['Email'] = 'left';
		$colData['align']['Address'] = 'left';
		$colData['align']['City'] = 'left';
		$colData['align']['ZIP'] = 'left';
		$colData['align']['Occupancy'] = 'center';
		$colData['align']['MLSNr'] = 'right';
		$colData['align']['OffMarketDate'] = 'center';
		$colData['align']['ListPrice'] = 'right';
		$colData['align']['Restrictions'] = 'center';
		$colData['align']['Source'] = 'center';
		$colData['align']['YearBuilt'] = 'center';
		$colData['align']['Style'] = 'center';
		$colData['align']['UnitDesignator'] = 'center';
		$colData['align']['UnitNumber'] = 'center';
		$colData['align']['StateID'] = 'center';
		$colData['align']['TaxRecordsMailAddress'] = 'center';
		$colData['align']['NODRecordingDate'] = 'center';
		$colData['align']['NODFilingDate'] = 'center';
		$colData['align']['NODDefaultAmount'] = 'center';
		$colData['align']['NODOriginalMortgageAmount'] = 'center';
		$colData['align']['NrOfBeds'] = 'center';
		$colData['align']['NrOfBaths'] = 'center';
		$colData['align']['BuildingSize'] = 'center';
		$colData['align']['LotSize'] = 'center';
		$colData['align']['FSBOName'] = 'left';
		$colData['align']['Delete'] = 'center';
		if ($this->adminMode)
		{
			$colData['align']['Edit'] = 'center';
			$colData['align']['Neighbors'] = 'center';
		}


		$colData['searchable']['SmartSearch'] = 2;
		$colData['searchable']['FollowUpDate'] = 1;
		$colData['searchable']['Type'] = 0;
		$colData['searchable']['Status'] = 1;
		$colData['searchable']['DNC'] = 0;
		$colData['searchable']['TaxRecordsName'] = 1;
		$colData['searchable']['Phone'] = 1;
		$colData['searchable']['Email'] = 1;
		$colData['searchable']['Address'] = 1;
		$colData['searchable']['City'] = 1;
		$colData['searchable']['ZIP'] = 1;
		$colData['searchable']['Occupancy'] = 0;
		$colData['searchable']['MLSNr'] = 0;
		$colData['searchable']['OffMarketDate'] = 0;
		$colData['searchable']['ListPrice'] = 1;
		$colData['searchable']['Restrictions'] = 0;
		$colData['searchable']['Source'] = 0;
		$colData['searchable']['YearBuilt'] = 0;
		$colData['searchable']['Style'] = 0;
		$colData['searchable']['UnitDesignator'] = 0;
		$colData['searchable']['UnitNumber'] = 0;
		$colData['searchable']['StateID'] = 0;
		$colData['searchable']['TaxRecordsMailAddress'] = 0;
		$colData['searchable']['NODRecordingDate'] = 1;
		$colData['searchable']['NODFilingDate'] = 1;
		$colData['searchable']['NODDefaultAmount'] = 1;
		$colData['searchable']['NODOriginalMortgageAmount'] = 1;
		$colData['searchable']['NrOfBeds'] = 0;
		$colData['searchable']['NrOfBaths'] = 0;
		$colData['searchable']['BuildingSize'] = 0;
		$colData['searchable']['LotSize'] = 0;
		$colData['searchable']['FSBOName'] = 1;
		$colData['searchable']['Delete'] = 0;
		if ($this->adminMode)
		{
			$colData['searchable']['Edit'] = 0;
			$colData['searchable']['Neighbors'] = 0;
		}

		$colData['sortable']['SmartSearch'] = FALSE;
		$colData['sortable']['FollowUpDate'] = TRUE;
		$colData['sortable']['Type'] = TRUE;
		$colData['sortable']['Status'] = TRUE;
		$colData['sortable']['DNC'] = TRUE;
		$colData['sortable']['TaxRecordsName'] = TRUE;
		$colData['sortable']['Phone'] = TRUE;
		$colData['sortable']['Email'] = TRUE;
		$colData['sortable']['Address'] = TRUE;
		$colData['sortable']['City'] = TRUE;
		$colData['sortable']['ZIP'] = TRUE;
		$colData['sortable']['Occupancy'] = TRUE;
		$colData['sortable']['MLSNr'] = TRUE;
		$colData['sortable']['OffMarketDate'] = TRUE;
		$colData['sortable']['ListPrice'] = TRUE;
		$colData['sortable']['Restrictions'] = TRUE;
		$colData['sortable']['Source'] = TRUE;
		$colData['sortable']['YearBuilt'] = TRUE;
		$colData['sortable']['Style'] = TRUE;
		$colData['sortable']['UnitDesignator'] = TRUE;
		$colData['sortable']['UnitNumber'] = TRUE;
		$colData['sortable']['StateID'] = TRUE;
		$colData['sortable']['TaxRecordsMailAddress'] = TRUE;
		$colData['sortable']['NODRecordingDate'] = TRUE;
		$colData['sortable']['NODFilingDate'] = TRUE;
		$colData['sortable']['NODDefaultAmount'] = TRUE;
		$colData['sortable']['NODOriginalMortgageAmount'] = TRUE;
		$colData['sortable']['NrOfBeds'] = TRUE;
		$colData['sortable']['NrOfBaths'] = TRUE;
		$colData['sortable']['BuildingSize'] = TRUE;
		$colData['sortable']['LotSize'] = TRUE;
		$colData['sortable']['FSBOName'] = TRUE;
		$colData['sortable']['Delete'] = TRUE;
		if ($this->adminMode)
		{
			$colData['sortable']['Edit'] = true;
			$colData['sortable']['Neighbors'] = true;
		}


		$colData['name']['SmartSearch'] = 'Smart Search';
		$colData['name']['FollowUpDate'] = 'F-Date';
		$colData['name']['Type'] = 'Type';
		$colData['name']['Status'] = 'Status';
		$colData['name']['DNC'] = 'DNC';
		$colData['name']['TaxRecordsName'] = 'Name';
		$colData['name']['Phone'] = 'Phone';
		$colData['name']['Email'] = 'Email';
		$colData['name']['Address'] = 'Address';
		$colData['name']['City'] = 'City';
		$colData['name']['ZIP'] = 'ZIP';
		$colData['name']['Occupancy'] = 'Occupancy';
		$colData['name']['MLSNr'] = 'MLS #';
		$colData['name']['OffMarketDate'] = 'Off Market Date';
		$colData['name']['ListPrice'] = 'List Price';
		$colData['name']['Restrictions'] = 'Restrictions';
		$colData['name']['Source'] = 'Source';
		$colData['name']['YearBuilt'] = 'Year';
		$colData['name']['Style'] = 'Style';
		$colData['name']['UnitDesignator'] = 'Unit Designator';
		$colData['name']['UnitNumber'] = 'Unit Number';
		$colData['name']['StateID'] = 'State';
		$colData['name']['TaxRecordsMailAddress'] = 'Mailing Address';
		$colData['name']['NODRecordingDate'] = 'Recording Date';
		$colData['name']['NODFilingDate'] = 'Filing Date';
		$colData['name']['NODDefaultAmount'] = 'Default Amount';
		$colData['name']['NODOriginalMortgageAmount'] = 'Original Amount';
		$colData['name']['NrOfBeds'] = 'Bdrm';
		$colData['name']['NrOfBaths'] = 'Bath';
		$colData['name']['BuildingSize'] = 'Est. SqFt';
		$colData['name']['LotSize'] = 'Lot Size';
		$colData['name']['FSBOName'] = 'Name';
		$colData['name']['Delete'] = 'Delete';
		if ($this->adminMode)
		{
			$colData['name']['Edit'] = 'Edit';
			$colData['name']['Neighbors'] = 'N';
		}

		for ($i=0;$i<count($fields);$i++)
			$colData['visibility'][$fields[$i]] = $fieldSettings[$fields[$i]]['VISIBILITY'];
		//$colData['visibility']['Delete'] = 0;



		if ($gridStatuses==null)
			$colData['width'] = $colData['defaultwidth'];
		else
		{
			foreach ($gridStatuses as $status)
			{
				$key = $status['MetaKey'];
				if (strpos($key,'_'.$this->propertyType)!==false)
				{
					$key = str_replace($this->leadGridStateColumnMetaSuffix,'',$key);

					if (strpos($key,'grid_column_')===0)
					{
						$field = str_replace('grid_column_','',$key);

						if (array_key_exists($field,$colData['defaultwidth']))
						{
							$values = explode('|',$status['MetaValue']);
							$width = intval($values[0]);
							$visibility = intval($values[1]);

							if ($width!=0)
								$colData['width'][$field] = $width;
							else
								$colData['width'][$field] = $colData['defaultwidth'][$field];

							$colData['visibility'][$field] = $visibility;
						}
					}
				}
			}
            
			foreach ($colData['defaultwidth'] as $key=>$value)
				if (!isset($colData['width']) || !array_key_exists($key,$colData['width']))
					$colData['width'][$key]=$value;
		}
/*
        if(isset($colData['width']['SmartSearch']))
            unset($colData['width']['SmartSearch']);
        $tmp_arr = array('SmartSearch' => 30);
        foreach ($colData['width'] as $key=>$val)
        {
            if($key != 'SmartSearch')
                $tmp_arr[$key] = $val;
        }
        $colData['width'] = $tmp_arr;
        */
		$colModel = array();
		$columnOrder = array();

	/*	if ($this->followUpMode)
		{
			$colModel['FollowUpDate'] = array('F-Date',50,false,'center',0);
			$colModel['Type'] = array('Type',40,false,'center',0);
		}*/

		foreach ($colData['width'] as $key=>$value)
		{
			$colModel[$key] = array($colData['name'][$key],$value,$colData['sortable'][$key],$colData['align'][$key],$colData['searchable'][$key],$colData['visibility'][$key]);
			$columnOrder[] = $key;
		}
		$this->session->set_userdata('s_leadGridColumnOrder',$columnOrder);
        
        $followPerPage = $this->model_user->GetFollowUpPerPageItems($this->userID);
        if($followPerPage > 0)
            $nrOfLeadRows = $followPerPage;
		elseif ($this->session->userdata('s_searchLimit')!=false && $this->searchFilter!="")
			$nrOfLeadRows = intval($this->session->userdata('s_searchLimit',true));
		else
			$nrOfLeadRows = NR_OF_LEAD_ROWS;

		/*
		 * Aditional Parameters
		 */
		$gridParams = array
		(
			'width' => 'auto',
			'height' => 405,
			'rp' => $nrOfLeadRows,
			'rpOptions' => '[10,'.NR_OF_LEAD_ROWS.',20,25,40,100]',
			'pagestat' => 'Displaying: {from} to {to} of {total} items.',
			'blockOpacity' => 0.5,
			'showTableToggleBtn' => true
		);

		//Build js
		$grid_js = build_grid_js('lead_grid', site_url("/leads/buildGrid"),$colModel,'PropertyID','asc',$gridParams,null);

		$this->data['LEAD_GRID_JS'] = $grid_js;

		$gridHeight = $this->model_user->GetUserMetaItem($this->userID,META_LEAD_GRID_HEIGHT.'_'.$this->propertyType,META_GROUP_LEAD_GRID_STATE);
		if ($gridHeight!=null)
			$this->data['LEAD_GRID_HEIGHT'] = $gridHeight;

	}

	function _validateGridPostData()
	{
		// List of all fields that can be sortable. This is Optional.
		// This prevents that a user sorts by a column that we dont want him to access, or that doesnt exist, preventing errors.

		$this->flexigrid->validate_post('PropertyID','asc',array_keys(self::$LEAD_LIST_FIELDS_BY_TYPE[$this->propertyType]));
	}

	function buildGrid()
	{  
	   
        if(isset($_POST['rp']))
        {
            $countPerPage = (int) $_POST['rp'];
            $this->model_user->SetFollowUpPerPageItems($this->userID, $countPerPage);
        }
        
        $followPerPage = $this->model_user->GetFollowUpPerPageItems($this->userID);
        
        if(!$followPerPage)
            $followPerPage = $this->flexigrid->post_info['rp'];
        	   
		$this->_validateGridPostData();

		$columnOrder = $this->session->userdata('s_leadGridColumnOrder');

		if (isset($_POST['searchName']))
		{
			$searchName = $this->input->post('searchName',true);
			$this->session->set_userdata('s_selectedSearchName',$searchName);
			if ($searchName!='[DEFAULT]' && $searchName!='[EMPTY]')
				$this->model_user->AddUserMeta($this->userID,META_GROUP_LEAD_GRID_SEARCH,META_SELECTED_SEARCH_NAME,$searchName);
		}
		if (isset($_POST['qtype']) && $_POST['qtype'] == 'DNC')
		{
			$swhere = $this->flexigrid->post_info['swhere'];
			$swhere = str_replace('%n%','%0%',$swhere);
			$swhere = str_replace('%y%','%1%',$swhere);
			$swhere = str_replace('%N%','%0%',$swhere);
			$swhere = str_replace('%Y%','%1%',$swhere);
			$this->flexigrid->post_info['swhere'] = $swhere;
		}
		elseif (isset($_POST['qtype']) && $_POST['qtype'] == 'NODRecordingDate') {
			$this->searchFilter = preg_replace('!(?:(?:AND|OR)\\s+)?ListPrice[^ ]+!', '', $this->searchFilter);
			$this->searchFilter = preg_replace('!(?:(?:AND|OR)\\s+)?ListPrice BETWEEN [^ ]+ AND [^ ]+!', '', $this->searchFilter);
		}

		if (isset($_POST['rp']) && $this->input->post('rp') != FALSE && is_numeric($this->input->post('rp')))
			$numRows = intval($this->input->xss_clean($this->input->post('rp')));
		else
			$numRows = NR_OF_LEAD_ROWS;
//print_r($this->searchFilter); die;
		// Setting page number

		if ($this->input->xss_clean($this->input->post('pageByID'))=='1')	// Get the page on which the current property is located
			$this->pageNr = $this->model_property->GetPageNrOfProperty($this->propertyType,$this->propertyID,$numRows,$this->userID,$this->selectedDate,$this->searchFilter,$this->adminMode,$this->followUpMode,$this->flexigridOptions);
		elseif ($this->flexigrid->post_info['page'])
			$this->pageNr = $this->input->xss_clean($this->flexigrid->post_info['page']);
		else
			$this->pageNr = $this->flexigrid->post_info['page'];

		// Page number and limit
		$this->flexigrid->post_info['page'] = $this->pageNr;
		$this->flexigrid->post_info['limitstart'] = (($this->flexigrid->post_info['page']-1) * $this->flexigrid->post_info['rp']);

		$records['records'] = $this->model_property->GetPaginatedPropertiesForGrid($this->propertyType,$numRows,$this->pageNr,$this->userID,$this->selectedDate,$this->searchFilter,$this->adminMode,$this->followUpMode,$this->flexigridOptions,$columnOrder,$followPerPage);
        
       // print_r($records['records']); die;
        
		$records['record_count'] = $this->model_property->GetNrOfPropertiesForGrid($this->propertyType,$this->userID,$this->selectedDate,$this->searchFilter,$this->adminMode,$this->followUpMode,$this->flexigridOptions);

		$this->output->set_header($this->config->item('json_header'));

		/*
		 * Json build WITH json_encode. If you do not have this function please read
		 * http://flexigrid.eyeviewdesign.com/index.php/flexigrid/example#s3 to know how to use the alternative
		 */
		$record_items = array();
		$parameters = array();
		$columnOrder = $this->session->userdata('s_leadGridColumnOrder');
		$lastHeaderIndex = -1;
		$headerName = "";
		$headerIndex = -1;

		if ($this->followUpMode)
		{
			$headerNames = $this->config->item('FOLLOWUP_HEADERS');
			$secondsInDay = 60 * 60 * 24;
		}



		for ($recordCounter=0;$recordCounter<count($records['records']);$recordCounter++)
		{
		  
			$row = $records['records'][$recordCounter];
			$timeDifference = intval($row['FollowUpAddedTime'])-intval($row['CurrentDayTime']);

			#Follow Up period headers in grid
			if ($this->followUpMode)
			{
				if ($recordCounter==0 || $timeDifference == 0 || $timeDifference>=$secondsInDay)
				{
					list ($headerName,$headerIndex) = $this->_getFollowUpHeader($timeDifference,$secondsInDay);

					if ($headerIndex>$lastHeaderIndex)
					{
						$headerRow = array();

						$headerRow[] = "-1".rand()%100;
						$headerRow[] = $headerNames[$headerName];

						for ($i=0;$i<count($columnOrder)-1;$i++)
							$headerRow[] = "";
						$record_items[] = $headerRow;

						$lastHeaderIndex=$headerIndex;
					}
				}
			}

			$itemRow = array();

			$itemRow[] = $row['PropertyID'];

			/*if ($this->followUpMode)
			{
				foreach ($this->followUpGridFields as $field)
					$itemRow[] = $this->getFormattedGridField($row,$field);
			}*/

			$fieldSettings = self::$LEAD_LIST_FIELDS_BY_TYPE[$this->propertyType];

			if ($this->adminMode)
			{
				$fieldSettings['Edit'] = '';
				$fieldSettings['Neighbors'] = '';
			}
            
			for ($i=0;$i<count($columnOrder);$i++)
				if (in_array($columnOrder[$i],array_keys($fieldSettings)))
				{
					switch ($columnOrder[$i])
					{
						case 'Delete':$itemRow[] = '<div style="text-align:center"><span class="button_delete_lead"><img class="pointer" border="0" src="'.base_url_http().'images/delete.gif" /></span></div>';break;
						case 'Edit':$itemRow[] = '<span class="button_edit_lead"><img class="pointer" border="0" src="'.base_url_http().'images/edit.gif" /></span>';break;
						case 'Neighbors':$itemRow[] = '<span class="button_neighbor_management"><img class="pointer" border="0" src="'.base_url_http().'images/house.png" title="Manage neighbors" /></span>';break;
						default:$itemRow[] = $this->getFormattedGridField($row,$columnOrder[$i]);break;
					}

				}

			$record_items[] = $itemRow;

			$parameters['ActionTaken'][] = $row['ActionTaken'];
		}

		$this->output->set_output($this->flexigrid->json_build($records['record_count'],$record_items));
		$this->_setDataToSession();
//		get_instance()->load->view('sqlmon/sqlmon');
	}
    
    
    function get_main_data($extra_data)
    {
        $result = array(
                'phone' => '',
                'email' => '',
                'im' => '',
                'address' => '',
                'zip' => '',
                'city' => '',
                'state' => '',
                'relationship' => '',
            );
            
        if(count($extra_data) > 0)
        {
            $phone_main = 0;
            $phone_cell = 0;
            $phone_work = 0;
            $phone_count = 0;
            
            $email_main = 0;
            $email_work = 0;
            $email_home = 0;
            $email_count = 0;
            
            $im_count = 0;
            
            $address_main = 0;
            $address_work = 0;
            $address_home = 0;
            $address_count = 0;
            
            foreach($extra_data as $data)
            {
                if($data->define == 'phone')
                {
                    if($data->is_main == 1)
                    {
                        $result['phone'] = $data->value;
                        $phone_main = 1;
                    }
                    elseif($phone_main == 0 && $data->typeID == 1)
                    {
                        $result['phone'] = $data->value;
                        $phone_cell = 1;
                    }
                    elseif($phone_cell == 0 && $phone_main == 0 && $data->typeID == 3)
                    {
                        $result['phone'] = $data->value;
                        $phone_work = 1;
                    }
                    elseif($phone_cell == 0 && $phone_main == 0 && $phone_work == 0 && $data->typeID == 2)
                    {
                        $result['phone'] = $data->value;
                        $phone_home = 1;
                    }
                    elseif($phone_count == 0)
                        $result['phone'] = $data->value;
        
                    $phone_count++;
                }
                elseif($data->define == 'email')
                {
                    if($data->is_main == 1)
                    {
                        $result['email'] = $data->value;
                        $email_main = 1;
                    }
                    elseif($email_main == 0 && $data->typeID == 5)
                    {
                        $result['email'] = $data->value;
                        $email_home = 1;
                    }
                    elseif($email_home == 0 && $email_main == 0 && $data->typeID == 6)
                    {
                        $result['email'] = $data->value;
                        $email_work = 1;
                    }
                    elseif($email_count == 0)
                        $result['email'] = $data->value;
        
                    $email_count++;
                }
                elseif($data->define == 'address')
                {
                    if($data->is_main == 1)
                    {
                        $result['address_id'] = $data->id;
                        $result['address'] = $data->street;
                        $result['city'] = $data->city;
                        $result['zip'] = $data->zip;
                        $result['state'] = $data->state;
                        $result['stateID'] = $data->state;
                        $address_main = 1;
                    }
                    elseif($address_main == 0 && $data->typeID == 7)
                    {   
                        $result['address_id'] = $data->id;
                        $result['address'] = $data->street;
                        $result['city'] = $data->city;
                        $result['zip'] = $data->zip;
                        $result['state'] = $data->state;
                        $result['stateID'] = $data->state;
                        $address_home = 1;
                    }
                    elseif($address_home == 0 && $address_main == 0 && $data->typeID == 8)
                    {
                        $result['address_id'] = $data->id;
                        $result['address'] = $data->street;
                        $result['city'] = $data->city;
                        $result['zip'] = $data->zip;
                        $result['state'] = $data->state;
                        $result['stateID'] = $data->state;
                        $address_work = 1;
                    }
                    elseif($address_count == 0)
                    {
                        $result['address_id'] = $data->id;
                        $result['address'] = $data->street;
                        $result['city'] = $data->city;
                        $result['zip'] = $data->zip;
                        $result['state'] = $data->state;
                        $result['stateID'] = $data->state;
                    }
        
                    $address_count++;
                }
                elseif($data->define == 'im')
                {
                    if($data->is_main == 1)
                    {
                        $result['im'] = $data->value;
                        $im_main = 1;
                    }
                    elseif($im_count == 0)
                        $result['im'] = $data->value;
                    
                    $im_count++;
                }
            }
        }
        
        return $result;
    }
    
    function buildGrid_new()
	{   
        $record_items = array();
        if(isset($_POST['rp']))
        {
            $countPerPage = (int) $_POST['rp'];
            $this->model_user->SetFollowUpPerPageItems($this->userID, $countPerPage);
        }
        
        $has_contacts = $this->model_user->GetUserInfoFieldByUserID($this->userID, 'has_contacts');
        $this->data['has_contacts'] = $has_contacts;
        
        $followPerPage = $this->model_user->GetFollowUpPerPageItems($this->userID);
        
        if(!$followPerPage)
            $followPerPage = $this->flexigrid->post_info['rp'];
        
        $property_fields = self::$LEAD_LIST_FIELDS_BY_TYPE[$this->propertyType];
        
        if($this->propertyType == 'jljs')
        {
            if($this->session->userdata('s_current_jljs_tab') && $this->session->userdata('s_current_jljs_tab') == 'js')
                unset($property_fields['ListPrice']);
            else
                unset($property_fields['SoldPrice']);
        }
        
        $gridStatuses = $this->model_user->GetUserMetaGroup($this->userID,META_GROUP_LEAD_GRID_STATE);

        if($this->propertyType == 'followups')
            $sortname = 'f_date';
        else
            $sortname = 'post_date';
            
        $sortorder = 'asc';
        
        $new_sort_fields = array();
        if ($gridStatuses!=null)
		{
			foreach ($gridStatuses as $status)
			{
				$key = $status['MetaKey'];
				if (strpos($key,'_'.$this->propertyType)!==false)
				{
					$key = str_replace($this->leadGridStateColumnMetaSuffix,'',$key);

					if (strpos($key,'grid_column_')===0)
					{
						$field = str_replace('grid_column_','',$key);

						if (array_key_exists($field, $property_fields))
						{                            
                            if(isset($property_fields[$field]))
                                $new_sort_fields[$field] = $property_fields[$field];
                            
						}
					}
				}
                
                if($key == 'grid_sort')
                    $sortname = $status['MetaValue'];
                if($key == 'grid_sort_order')
                    $sortorder = $status['MetaValue'];
                    
			}
        }
        
        if($this->input->post('sortname'))
        {
            if($sortname != $this->input->post('sortname'))
                $sortname = $this->input->post('sortname');
        }
        
        if($this->input->post('sortorder'))
        {
            if($sortorder != $this->input->post('sortorder'))
                $sortorder = $this->input->post('sortorder');
        }

        
        if(count($new_sort_fields) > 0)
            $property_fields = $new_sort_fields;
        
        $this->flexigrid->validate_post('PropertyID','asc', $property_fields);
        
        
        $lastHeaderIndex = -1;
		$headerName = "";
		$headerIndex = -1;
        
        $params = array();
        
        $folder = isset($_POST['folder']) && $this->model_property->check_folder((int)$_POST['folder'],$this->userID)?(int)$_POST['folder']:0;
        $category = isset($_POST['category']) && $this->model_property->check_field_type($this->userID, (int)$_POST['category'])?(int)$_POST['category']:0;
        $relation = isset($_POST['relation']) && $this->model_property->check_field_type($this->userID, (int)$_POST['relation'])?(int)$_POST['relation']:0;
        $selected_date = $this->selectedDate != "" ? $this->selectedDate : 0;
        
        if($folder > 0)
            $params['folder'] = $folder;
        if($category > 0)
            $params['category'] = $category;
        if($relation > 0)
            $params['relation'] = $relation;
        if($selected_date != 0)
            $params['selected_date'] = $selected_date;
        
        if($_POST['category'] == -1)
            $params['category'] = 0;
        if($_POST['relation'] == -1)
            $params['relation'] = 0;
        
        $params['sortname'] = $sortname;
        $params['sortorder'] = $sortorder;
        //$params['gird_debug'] = 1;
  
 // echo $params['selected_date'];
  
        $records = $this->model_property->get_properties($this->propertyType, $this->userID, $params);

        if($this->propertyType == 'fsbo')
        {
            $ids = array();
            $new_records = array();
            
            foreach($records["records"] as $record)
            {
                $new_records[$record->PropertyID] = $record;
                $ids[] = $record->PropertyID;
            }
            if( count($ids) > 0 )
                $aditional_data = $this->model_property->get_user_edit_data($ids, $this->userID);
            else
                $aditional_data = array();

            foreach($aditional_data as $ad_data)
            {
                $new_records[$ad_data->fsbo_parent_id] = $ad_data;
            }
            
            $records["records"] = $new_records;
        }
        
		//print_r($records);
		$this->output->set_header($this->config->item('json_header'));
		
        if ($this->propertyType == 'followups')
		{
			$headerNames = $this->config->item('FOLLOWUP_HEADERS');
			$secondsInDay = 60 * 60 * 24;
		}
        
		foreach ($records['records'] as $k=>$row)
		{
		  //echo $row->fname . ' - ' . $row->SetTo;
			#Follow Up period headers in grid
            $propertyID = $row->PropertyID;
            //$extra_data = $this->model_property->get_property_extra_data($propertyID);
            //$grid_data = $this->get_main_data($extra_data);
            $grid_data = array();
            
            if($row->relation > 0)
            {
                $typeInfo = $this->model_property->get_type_info_by_id($row->relation);
                if($typeInfo)
                {
                    $grid_data['relationship'] = $typeInfo->value;
                }
                else
                    $grid_data['relationship'] = '';
            }
            else
            {
                $grid_data['relationship'] = '';
            }               
            
            if($row->category > 0)
            {
                $typeInfo = $this->model_property->get_type_info_by_id($row->category);
                if($typeInfo)
                {
                    $grid_data['category'] = $typeInfo->value;
                }
                else
                    $grid_data['category'] = '';
            }
            else
            {
                $grid_data['category'] = '';
            }              
              
            if(!is_null($row->folder_id) &&  $row->folder_id > 0)
            {
                $folder_info = $this->model_property->check_folder($row->folder_id, $this->userID);
                if($folder_info)
                {
                    $grid_data['folder'] = $folder_info->name;
                }
                else
                    $grid_data['folder'] = '';
            }
            else
                $grid_data['folder'] = '';  
              
			if ($this->propertyType == 'followups')
			{
				list ($headerName,$headerIndex) = $this->_getFollowUpHeader($row->SetTo);

				if ($headerIndex>$lastHeaderIndex)
				{
					$headerRow = array();

					$headerRow[] = "-1".rand()%100;
					$headerRow[] = $headerNames[$headerName];

					for ($i=0;$i<count($property_fields)-1;$i++)
						$headerRow[] = "";
					$record_items[] = $headerRow;

					$lastHeaderIndex=$headerIndex;
				}
			
            }
            
            
            $itemRow = array($row->PropertyID);

			$fieldSettings = $property_fields;

			if ($this->adminMode)
			{
				$fieldSettings['Edit'] = array();
				$fieldSettings['Neighbors'] = array();
			}

			foreach ($fieldSettings as $field=>$val)
					switch ($field)
					{
						case 'check_box_all':$itemRow[] = '<input type="checkbox" class="am" onclick="add_to_select(\'' . $propertyID . '\', this);cancelBubble(event);" value="' . $propertyID . '">';break;
                        case 'is_favorite':
                            if($row->is_favourite == 1)
                                $itemRow[] = '<img src="/images/contact_info/star-r.jpg" id="grid_star" class="pointer" onclick="set_favourite(\'' . $propertyID . '\', this, \'grid\');cancelBubble(event);">';
                            else
                                $itemRow[] = '<img src="/images/contact_info/star-g.jpg" id="grid_star" class="pointer" onclick="set_favourite(\'' . $propertyID . '\', this, \'grid\');cancelBubble(event);">';
                        break;
                        case 'delete':$itemRow[] = '<div style="text-align:center;padding:0px;"><span class="button_delete_lead"><img class="pointer" border="0" src="/images/delete.gif" /></span></div>';break;
						case 'Edit':$itemRow[] = '<span class="button_edit_lead"><img class="pointer" border="0" src="'.base_url_http().'images/edit.gif" /></span>';break;
						case 'Neighbors':$itemRow[] = '<span class="button_neighbor_management"><img class="pointer" border="0" src="'.base_url_http().'images/house.png" title="Manage neighbors" /></span>';break;
						default:$itemRow[] = $this->getFormattedGridField($row,$field,$grid_data);break;
					}

			$record_items[] = $itemRow;    
		}
		//Print please
        
		$this->output->set_output($this->flexigrid->json_build($records['record_count'],$record_items));
	}
    
	function getFormattedGridField($row,$field,$grid_data)
	{       

        if($field == 'smart_search')
            $formattedField = '1';
        elseif($field == 'PropertyID')
            $formattedField = $row->PropertyID;      
        elseif($field == 'name')
            $formattedField = $row->fname . ' ' . $row->lname;  
        elseif($field == 'phone')
            $formattedField = $row->grid_phone;
        elseif($field == 'email')
            $formattedField = '<span class="email_red">' . $row->grid_email . '</span>';
        elseif($field == 'im')
            $formattedField = $row->grid_im;
        elseif($field == 'address' && ($this->propertyType == 'contacts' || $this->propertyType == 'fsbo' || $this->propertyType == 'followups'))
        {
            if($row->is_expired == 1 || $row->is_nod == 1 || $row->is_jl == 1 || $row->is_js == 1)
            {
                $formattedField = $row->Address; 
            } 
            else
            {
                if($row->is_fsbo == 1)
                    $formattedField = $row->city_name . ', ' . $row->state_abv;
                else
                    $formattedField = $row->grid_address;
            }
                
        }    
        elseif($field == 'city' && ($this->propertyType == 'contacts' || $this->propertyType == 'fsbo' || $this->propertyType == 'followups'))
        {
            if($row->is_expired == 1 || $row->is_nod == 1 || $row->is_jl == 1 || $row->is_js == 1)
                $formattedField = $row->City;
            else
            {
                if($row->is_fsbo == 1)
                    $formattedField = $row->city_name;
                else
                    $formattedField = $row->grid_city;
            }
        }   
        elseif($field == 'state' && ($this->propertyType == 'contacts' || $this->propertyType == 'fsbo' || $this->propertyType == 'followups'))
        {
            if($row->is_expired == 1 || $row->is_nod == 1 || $row->is_jl == 1 || $row->is_js == 1)
                $formattedField = $row->State;
            else
            {
                if($row->is_fsbo == 1)
                    $formattedField = $row->state_abv;
                else
                    $formattedField = $row->grid_state;
            }  
        }
        elseif($field == 'zip' && ($this->propertyType == 'contacts' || $this->propertyType == 'fsbo' || $this->propertyType == 'followups'))
        {
            if($row->is_expired == 1 || $row->is_nod == 1 || $row->is_jl == 1 || $row->is_js == 1)
                $formattedField = $row->ZIP;
            else
                $formattedField = $row->grid_zip;
        }
        elseif($field == 'relationship')
            $formattedField = $grid_data['relationship'];
        elseif($field == 'category')
            $formattedField = $grid_data['category'];
        elseif($field == 'folder')
            $formattedField = $grid_data['folder'];
        elseif($field == 'status' && ($this->propertyType == 'contacts' || $this->propertyType == 'fsbo' || $this->propertyType == 'followups'))
        {
            $formattedField = '<span id="grid_status_' . $row->PropertyID . '">';
            if($row->status_property == 'new')
                $status = 'New';
            else if($row->status_property == 'appt')
                $status = 'Appt';
            else if($row->status_property == 'follow')
                $status = 'Follow Up';
            else
                $status = $row->status_property;
                
            $formattedField .= $status . '</span>'  ;
            
        }
        elseif($field == 'motivation')
            $formattedField = !is_null($row->motivation) ? '<div style="text-align: center;padding:0;">' . $row->motivation . '</div>' : '';
        elseif($field == 'home_price')
        {
            if($row->is_expired == 1)
                $formattedField = '$' . preg_replace('/(\d{1,3})(?=(?:\d{3})+$)/si', '$1,', preg_replace('![^0-9]!si', '', $row->ListPrice));
            elseif($row->is_js == 1)
                $formattedField = '$' . preg_replace('/(\d{1,3})(?=(?:\d{3})+$)/si', '$1,', preg_replace('![^0-9]!si', '', $row->SoldPrice)); 
            elseif($row->is_jl == 1)
                $formattedField = '$' . preg_replace('/(\d{1,3})(?=(?:\d{3})+$)/si', '$1,', preg_replace('![^0-9]!si', '', $row->ListPrice));
            else
            {
                if($row->category == 12 || $row->category == 13 || $row->category == 14 || $row->category == 15)
                {
                    if($row->home_price_define == 0)
                        $formattedField = $row->home_price_max;
                    else
                        $formattedField = $row->home_price_equal;
                    
                    $formattedField = preg_replace('![^0-9]!si', '', $formattedField);
                        
                    if((int) $formattedField > 0)
                        $formattedField = '$' . preg_replace('/(\d{1,3})(?=(?:\d{3})+$)/si', '$1,', $formattedField);
                    
                }
                else
                    $formattedField = '';
            }
        }
        elseif($field == 'post_date')
            $formattedField = date('m/d/y', strtotime($row->post_date));
        elseif($field == 'f_date')
        {
            $formattedField = date('m/d/y', strtotime($row->SetTo));
        } 
        elseif($field == 'ListPrice')
        {
            $price = preg_replace('![^0-9]!si', '', $row->ListPrice);
            
            if($price != '')
                $formattedField = '$' . preg_replace('/(\d{1,3})(?=(?:\d{3})+$)/si', '$1,', $price);
            else
                $formattedField = '';
        }
        elseif($field == 'SoldPrice')
        {
            $price = preg_replace('![^0-9]!si', '', $row->SoldPrice);
            
            if($price != '')
                $formattedField = '$' . preg_replace('/(\d{1,3})(?=(?:\d{3})+$)/si', '$1,', $price);
            else
                $formattedField = '';
        }
        elseif(isset($row->{$field}))
                $formattedField = $row->{$field};  
        else
            $formattedField = '';
       
		/*if ($row['ActionTaken']=='1' || ($field=='DNC' && $row['DNC']=='y'))
			$formattedField = '<span class="lead_action_taken">[VALUE]</span>';
		elseif($field != 'SmartSearch')
			$formattedField = $row[$field];
        else
            $formattedField = '';*/

		/*if ($row['ValidAddress']=='0' && in_array($field,array("Address","ZIP","City")))
			$formattedField = str_replace('[VALUE]','<span class="center_text"><img src="'.base_url_http().'images/warning.png" class="center_text" alt="" title="Address does not match property address!"/></span>',$formattedField);
		else*/
        
        /*if($field != 'SmartSearch')       
		  $formattedField = str_replace('[VALUE]',$row[$field],$formattedField);*/

		return $formattedField;
	}

	function _getFollowUpHeader($set_to)
	{
        $current_date = date('Y-m-d');
        $tomorrow_date =  date('Y-m-d', strtotime('+1 DAY'));
        $set_date = date('Y-m-d', strtotime($set_to));
		
        $headerName = '';
		$headerIndex = -1;

		if ($set_date < $current_date)
		{
			$headerName = 'PAST';
			$headerIndex = 0;
		}
		elseif ($set_date == $current_date)
		{
			$headerName = 'TODAY';
			$headerIndex = 1;
		}
		elseif ($set_date == $tomorrow_date)
		{
			$headerName = 'TOMORROW';
			$headerIndex = 2;
		}
		elseif ($set_date > $tomorrow_date)
		{
			$headerName = 'FUTURE';
			$headerIndex = 3;
		}

		return array($headerName,$headerIndex);
	}

	function _setMainViewData($contentView,$boxHeightClass,$loadView=true)
	{
		//MAIN DATA
		$this->data['CSS_LINKS'] = array
		(
			'general.css',
			'profile.css',
			'page_leads.css',
			'panel.css',
			'include/jQueryUI/css/smoothness/jquery-ui-1.7.2.custom.css',
			'include/jqtransformplugin/jqtransform.css',
			'include/datetimepicker/jquery_ui_datepicker/timepicker_plug/css/style.css',
			'include/datetimepicker/jquery_ui_datepicker/smothness/jquery_ui_datepicker.css',
			'include/jQueryAlerts/jquery.alerts.css',
			'include/flexigrid/css/style.css',
			'include/flexigrid/css/flexigrid.css',
			'include/jhelpertip/jHelperTip.css',
			'goals.css',
			'stats.css'
		);
		$this->data['JS_LINKS'] = array
		(
			'jquery-1.3.2.js',
			'jquery.scrollTo-min.js',
			'jquery.form.js',
			'jquery.blockUI.js',
			'include/jqtransformplugin/jquery.jqtransform.js',
			'jquery.selectboxes.js',
			'jquery.validate.js',
			'include/datetimepicker/jquery_ui_datepicker/jquery_ui_datepicker.js',
			'include/datetimepicker/jquery_ui_datepicker/timepicker_plug/timepicker.js',
			'include/jQueryAlerts/jquery.alerts.js',
			'jquery-ui-1.7.2.custom.min.js',
			'include/flexigrid/js/flexigrid.js',
			'include/jhelpertip/jquery.jHelperTip.1.0.min.js',
			'include.js',
			'functions.js',
			'elements_user.js',
			'json2.js',
			'http://maps.google.com/maps?file=api&amp;v=2&amp;sensor=false&amp;key='.MAP_KEY,
			'google_map.js',
			'main.js',
			'popups.js?h=20100708053700',
			'goals.js',
			'elements.js',
			'elements_action_calendars.js',
			'elements_leads.js',
			'form_validation.js'
		);

		$this->data['BOX_HEIGHT_CLASS'] =$boxHeightClass;
		$this->data['TITLE'] = '';
		$this->data['BASE_URL'] = base_url_http();
		$this->data['IS_ADMIN'] = $this->redux_auth->is_admin();

		//VIEWS
		$this->data['VIEW_HEADER'] = 'view_leadsheader.php';
		$this->data['VIEW_MENU'] = 'view_leadsmenu.php';
  
		if($this->propertyType == 'fsbo' || $this->propertyType == 'expireds' || $this->propertyType == 'nod' || $this->propertyType == 'jljs')
        {
            $this->data['VIEW_OWNERINFO'] = 'view_leads_ownerinfo_' . $this->propertyType . '.php';
            $this->data['VIEW_PROPERTYINFO'] = 'view_leads_propertyinfo_' . $this->propertyType . '.php';
        }
        else
        {
            $this->data['VIEW_OWNERINFO'] = 'view_leads_ownerinfo.php';
            $this->data['VIEW_PROPERTYINFO'] = 'view_leads_propertyinfo.php';
        }

        if($this->propertyType != 'fsbo' && $this->propertyType != 'expireds' && $this->propertyType != 'nod' && $this->propertyType != 'jljs')  
            $this->data['VIEW_LEADS_LIST'] ='view_leads_list.php';
        else
            $this->data['VIEW_LEADS_LIST'] ='view_leads_list_fsbo.php';

		$this->data['VIEW_MAIN'] = $contentView;
		$this->data['VIEW_FOOTER'] = 'view_leadsfooter.php';


        $user_info = $this->model_user->getUserInfoByUserID($this->userID, array('*'));
        $this->data['user_info'] = $user_info;

		$this->data['GOALS'] = $this->model_user->GetGoalsForDisplay($this->userID);
		$addedGoals = $this->model_user->GetUserInfoFieldByUserID($this->userID,"added_goals");
		$this->data['ADDED_GOALS'] = $addedGoals == 1 ? true : false;
		$this->data['ACCOUNT_VALID'] = !$this->redux_auth->account_cancelled() && !$this->redux_auth->account_expired();
		$this->data['VIEW_GOALS'] = 'leads/view_goals';
		$this->data['IS_AJAX_REQUEST'] = IS_AJAX_REQUEST?1:0;
		$this->data['USER_AUTHENTICATED'] = $this->redux_auth->logged_in();
		$this->data['SITE_VERSION'] = SITE_VERSION;


		$this->data['UPLOAD_ENABLED'] = $this->model_utils->UploadEnabled();

		if ($loadView)
			$this->load->view('leads/view_leads_properties',$this->data);
	}
	/* END_REGION SET_VIEW_DATA */


	function _setDataToSession()
	{
		$this->session->unset_userdata(array('s_propertyID','s_propertyType','s_pageNr','s_propertyInfoType','s_selectedDate'));
        
        if($this->input->post('propertyID') && $this->input->post('propertyID') == 0)
            $this->propertyID = 0;
        if($this->propertyType == 'contacts')
            $this->session->set_userdata('s_propertyID', $this->propertyID);
            
		$this->session->set_userdata('s_propertyType', $this->propertyType);
		$this->session->set_userdata('s_pageNr', $this->pageNr);
		$this->session->set_userdata('s_propertyInfoType',$this->propertyInfoType);
		//$this->session->set_userdata('s_pageNrNeighbors',$this->pageNrNeighbors);
		$this->session->set_userdata('s_neighborID',$this->neighborID);
		$this->session->set_userdata('s_selectedDate',$this->selectedDate);
		$this->session->set_userdata('s_searchFilter',$this->searchFilter);
		$this->session->set_userdata('s_adminMode',$this->adminMode);
		$this->session->set_userdata('s_followUpMode',$this->followUpMode);
		/*$this->_setFlexigridOptionsToSession();*/
	}

	function _setFlexigridOptionsToSession()
	{
		$this->session->set_userdata('s_page',$this->flexigrid->post_info['page']);
		$this->session->set_userdata('s_qtype',$this->flexigrid->post_info['qtype']);
		$this->session->set_userdata('s_sortorder',$this->flexigrid->post_info['sortorder']);
		$this->session->set_userdata('s_sortname',$this->flexigrid->post_info['sortname']);
		$this->session->set_userdata('s_query',$this->flexigrid->post_info['query']);
		$this->session->set_userdata('s_rp',$this->flexigrid->post_info['rp']);
	}

	/* REGION OTHER */
	function _checkRequest()
	{
		$redirect = false;

		/*if (($this->propertyID!=null && $this->propertyID<-1) || !in_array(strtolower($this->propertyType),$this->propertyPages))
            $redirect = true;
		elseif (!in_array($this->propertyInfoType,$this->propertyInfoPages))
			$redirect = true;*/

		if ($redirect)
			$this->model_utils->redirectToLoginPage();
	}


	function _setFieldValues()
	{
		//Admin mode
		$this->adminMode = $this->_getFieldValue("adminMode");
		$sessionFollowUpMode = $this->session->userdata('s_followUpMode');
		$this->followUpMode = $sessionFollowUpMode=="1" || $sessionFollowUpMode==true;

		$this->pageNr = intval($this->_getFieldValue("pageNr"));
		
        if($this->input->post('propertyID') && $this->input->post('propertyID') == 0)
            $this->propertyID = 0;
        else
            $this->propertyID = intval($this->_getFieldValue("propertyID"));
        $method = $this->uri->segment(2);
        if($method != 'ajax_add_touch_for_contact' && $method != 'buildGrid_new')
        {
            //echo $this->propertyID;
            //echo $this->propertyID;
        }
        


		$this->propertyType = $this->_getFieldValue("propertyType");

		$this->propertyInfoType = $this->_getFieldValue("propertyInfoType");
		$this->pageNrNeighbors = intval($this->_getFieldValue("pageNrNeighbors"));
		$this->neighborID = intval($this->_getFieldValue("neighborID"));

		$this->selectedDate = $this->_getFieldValue("selectedDate");
		$this->userID = intval($this->session->userdata('s_userID'));


		// Search lead filter
		$searchFilterSet = $this->input->post('searchName');
		$this->searchFilter = $this->input->post('searchName',true);

	/*	if (isset($_POST['sortname']) && $_POST['sortname']!='PropertyID' && ( !isset($_POST['searchName']) || (isset($_POST['searchName']) && $_POST['searchName']=="") ) )
			$this->searchFilter = '[EMPTY]';*/

		if ($this->searchFilter===false)
			$this->searchFilter = '';

		if ($this->searchFilter=='[EMPTY]')
			$this->searchFilter = '';
		elseif ($searchFilterSet==true)
		{
			if ($this->searchFilter=='[DEFAULT]')
				$this->searchFilter = $this->session->userdata('s_searchFilter');
			else
			{
				$savedSearchData = $this->model_user->GetSavedSearchByName($this->userID,$this->searchFilter);
				$this->searchFilter = $savedSearchData['Filter'];
				$this->searchLimit = $savedSearchData['RowLimit'];
				$this->session->set_userdata('s_searchFilter',$this->searchFilter);
				$this->session->set_userdata('s_searchLimit',$this->searchLimit);
			}
		}
		else
		{
			$this->searchFilter = $this->session->userdata('s_searchFilter');
			if ($this->searchFilter===false || $this->searchFilter==='')
			{
				$searchName = $this->model_user->GetUserMetaItem($this->userID,META_SELECTED_SEARCH_NAME,META_GROUP_LEAD_GRID_SEARCH);
				if ($searchName!=null && $searchName!='[DEFAULT]' && $searchName!='[EMPTY]')
				{
					$savedSearchData = $this->model_user->GetSavedSearchByName($this->userID,$this->searchFilter);
					if ($savedSearchData!==null && isset($savedSearchData['Filter']) && isset($savedSearchData['RowLimit']))
					{
						$this->searchFilter = $savedSearchData['Filter'];
						$rowLimit = $savedSearchData['RowLimit'];
						$this->session->set_userdata('s_searchFilter',$this->searchFilter);
						$this->session->set_userdata('s_searchLimit',$rowLimit);
					}
				}
			}
		}

		$this->searchFilter = $this->_refreshSearchFilter($this->searchFilter);

		if ($this->propertyID==0)
		{
			$this->_validateGridPostData();
            $sessionValue = $this->session->userdata('s_propertyID');
            if($sessionValue && $sessionValue > 0 && $this->propertyType != 'fsbo' && $this->propertyType != 'expireds' && $this->propertyType != 'nod'  && $this->propertyType != 'jljs')
            {
                $this->propertyID = $sessionValue;
            }
            else
            {
                $params = $this->get_sort_params();
                $this->propertyID = intval($this->model_property->GetFirstPropertyID($this->propertyType,$this->userID,$this->selectedDate,$this->searchFilter,$this->adminMode,$this->followUpMode,$this->flexigridOptions,$params));
                
                if($this->propertyType == 'fsbo')
                {
                    $dublicate = $this->model_property->check_dublicate_row($this->propertyID, $this->userID);
                    if($dublicate)
                        $this->propertyID = $dublicate->PropertyID;
                }
                if($this->propertyType == 'contacts')
                    $this->session->set_userdata('s_propertyID', $this->propertyID);
            }
			
		}

		if ($this->input->post('searchNameDelete'))
		{
			$this->session->set_userdata('s_searchFilter','');
			$this->session->set_userdata('s_searchLimit','');
		}

	}

	function _refreshSearchFilter($searchFilter)
	{
		$refreshedSearchFilter = $searchFilter;

		if ($searchFilter && preg_match('/(.*)(STR_TO_DATE\(")(.*)(","%Y\/%c\/%d"\))(.*)/',$this->searchFilter,$matches))
			$refreshedSearchFilter = $matches[1].$matches[2].$this->selectedDate.$matches[4].$matches[5];

		return $refreshedSearchFilter;
	}

	function _setFlexigridValues()
	{
		$this->flexigridOptions['page'] = $this->_getFieldValue('page');
		$this->flexigridOptions['qtype'] = $this->_getFieldValue('qtype');
		$this->flexigridOptions['sortname'] = $this->_getFieldValue('sortname');
		$this->flexigridOptions['sortorder'] = $this->_getFieldValue('sortorder');
		$this->flexigridOptions['query'] = $this->_getFieldValue('query');
		$this->flexigridOptions['rp'] = $this->_getFieldValue('rp');
		$this->flexigridOptions['swhere'] = $this->_getFieldValue('swhere');

		$this->flexigrid->post_info['page'] = $this->flexigridOptions['page'];
		$this->flexigrid->post_info['qtype'] = $this->flexigridOptions['qtype'];
		$this->flexigrid->post_info['sortname'] = $this->flexigridOptions['sortname'];
		$this->flexigrid->post_info['sortorder'] = $this->flexigridOptions['sortorder'];
		$this->flexigrid->post_info['query'] = $this->flexigridOptions['query'];
		$this->flexigrid->post_info['rp'] = $this->flexigridOptions['rp'];
		$this->flexigrid->post_info['swhere'] = $this->flexigridOptions['swhere'];
	}

	function _setDefaultFieldValues()
	{
		$this->userID = intval($this->session->userdata('s_userID'));

		$this->propertyType = "contacts";
		$this->pageNr = 1;
		$this->propertyInfoType = "info";
		$this->pageNrNeighbors = 1;
		$this->selectedDate = date('Y/m/d',strtotime(PREVIOUS_DAY)); # When a user logs in, MLB should default to the previous day's date
		$this->propertyID = 0;

		$this->rp = NR_OF_LEAD_ROWS;
		$this->sortname = 'PropertyID';
		$this->sortorder = 'asc';
		$this->limitstart = 0;
		$this->swhere = '';

		$this->adminMode = $this->session->userdata('s_adminMode') && $this->redux_auth->is_admin();
	}

	function _getFieldValue($fieldName)
	{
		$fieldValue = null;

		if (isset($_POST[$fieldName]))
			$fieldValue = $this->input->xss_clean($_POST[$fieldName]);
		else
		{
			$sessionValue = $this->session->userdata('s_'.$fieldName);
                        
			if ($sessionValue)
				$fieldValue = $sessionValue;
			else
				$fieldValue = $this->$fieldName;
                
            if($this->propertyType == 'fsbo' && $fieldName == 'propertyID')
            {
                $dublicate = $this->model_property->check_dublicate_row($this->propertyID, $this->userID);
                if($dublicate)
                    $this->propertyID = $dublicate->PropertyID;
                
                return $this->propertyID;
            }
            
		}
		return $fieldValue;
	}


	function _buildSearchQuery()
	{
		$fields = array
		(
			"Added"			=>	"currentdate",
			"ZIP"			=>	"string_array",
			"ListPrice"		=>	"decimal",
			"Style"			=>	"array",
			"Status"		=>	"array",
			"ListStatus"	=>	"array",
			"Phone"			=>	"ynd",
			"DNC"			=>	"ynd",
			"Restrictions"	=>	"array"
			//"LeadsPerPage"	=>	"int"
		);

		$optionalFields = array("Style","Status","Restrictions");

		$fieldPrefixes = array("field_","rules_","field_end_","field_start_");

		$searchFields = array();
		$operator = " AND ";
		$sql = "";

		$rulesToSQL = array
		(
			'eq'	=>	'=',
			'ne'	=>	'<>',
			'gt'	=>	'>',
			'lt'	=>	'<',
			'leq'	=>	'<=',
			'geq'	=>	'>=',
			'btw'	=>	''
		);

		#Build fields for search
		foreach ($fields as $field=>$fieldType)
		{
			foreach ($fieldPrefixes as $prefix)
			{
				$value = $this->_getSearchFieldValueForDB($prefix,$field);
				if ($value!=null)
					$searchFields[$prefix.$field] = $value;
			}
		}

		$field = 'LeadsPerPage';
		foreach ($fieldPrefixes as $prefix)
		{
			$value = $this->_getSearchFieldValueForDB($prefix,$field);
			if ($value!=null)
				$searchFields[$prefix.$field] = $value;
		}

		#Build search query
		foreach ($fields as $field=>$fieldType)
		{
			if ($this->input->post("rules_".$field))
			{
				if ($fieldType=='array' || $fieldType=='string_array')
					$rule = $this->input->post("rules_".$field).'_in';
				else
				{
					$rule = $this->input->xss_clean($this->input->post("rules_".$field));
					if (!array_key_exists($rule,$rulesToSQL))
						continue;
				}
			}
			elseif ($fieldType=='array' || $fieldType=='string_array')
				$rule = "in";
			else
				$rule = "eq";

			if ($rule!="btw")
			{
				$fieldValue = $this->input->xss_clean($this->input->post("field_".$field));

				if ($fieldValue=="")
					continue;

				if ($fieldValue==" ") {
					$fieldValue = '';
				}

				if ($fieldType == 'ynd') {
					if ('d' == $fieldValue) {
						continue;
					}

					$fieldValue = $this->_getSearchFieldValues($fieldValue,$fieldType);
				}
				elseif ($rule!="like")
					$fieldValue = $this->_getSearchFieldValues($fieldValue,$fieldType);
			}
			else
			{
				$firstFieldValue = $this->input->xss_clean($this->input->post("field_start_".$field));
				$secondFieldValue = $this->input->xss_clean($this->input->post("field_end_".$field));

				if ($firstFieldValue=="" || $secondFieldValue=="")
					continue;

				$firstFieldValue = $this->_getSearchFieldValues($firstFieldValue,$fieldType);
				$secondFieldValue = $this->_getSearchFieldValues($secondFieldValue,$fieldType);
			}


			$optionalField = in_array($field,$optionalFields);
			$sql.= $operator;

			if ($optionalField)
				$sql.= "(";

			switch ($rule)
			{
				case 'btw':
				{
					$sql.= $this->_getFieldExpression($field,$fieldType,'')." BETWEEN ".$firstFieldValue." AND ".$secondFieldValue;
					break;
				}
				case 'like':
				{
					$sqlRule = $rulesToSQL[$rule];
					$sql.= $this->_getFieldExpression($field,$fieldType,$fieldValue)." LIKE '%".$fieldValue."%'";
					break;
				}
				case 'in':
				{
					$sql.= $this->_getFieldExpression($field,$fieldType,$fieldValue)." IN (".$fieldValue.")";
					break;
				}
				case 'eq_in':
				{
					$sql.= $this->_getFieldExpression($field,$fieldType,$fieldValue)." IN (".$fieldValue.")";
					break;
				}
				case 'ne_in':
				{
					$sql.= $this->_getFieldExpression($field,$fieldType,$fieldValue)." NOT IN (".$fieldValue.")";
					break;
				}
				default:
				{
					$sqlRule = $rulesToSQL[$rule];
					$sql.= $this->_getFieldExpression($field,$fieldType,$fieldValue).$sqlRule.$fieldValue;
					break;
				}
			}

			if ($optionalField)
				$sql.= " OR lt.Name IN ('fsbo','jljs','nod') ) ";
		}


		$limit = "";
		$sql = substr($sql,4,strlen($sql));

		if ($this->input->post('field_LeadsPerPage')!=false)
			$limit = $this->input->post('field_LeadsPerPage',true);
//
		$this->searchFilter = $sql;

		$this->session->set_userdata('s_searchFilter',$sql);
		$this->session->set_userdata('s_searchLimit',$limit);

		return $searchFields;
	}

	function _getFieldExpression($field,$fieldType,$fieldValue)
	{
		switch ($fieldType)
		{
			case "date":
				return " DATE(".$field.") ";
			case "currentdate":
				return " DATE(".$field.") ";
			default:
				if ($field == 'Phone') {
					return 'COALESCE(' .
							'IF(TRIM(IFNULL(TaxRecordsPhone,""))="" OR TRIM(IFNULL(TaxRecordsPhone,""))=TRIM(IFNULL(a1.BrokerOfficePhone,"")) OR TRIM(IFNULL(TaxRecordsPhone,""))=TRIM(IFNULL(a2.BrokerOfficePhone,"")) OR TRIM(IFNULL(TaxRecordsPhone,""))=TRIM(IFNULL(a1.Phone,"")) OR TRIM(IFNULL(TaxRecordsPhone,""))=TRIM(IFNULL(a2.Phone,"")),NULL,TaxRecordsPhone), IF(TRIM(IFNULL(WhitePagesPhone,""))="" OR TRIM(IFNULL(WhitePagesPhone,""))=TRIM(IFNULL(a1.BrokerOfficePhone,"")) OR TRIM(IFNULL(WhitePagesPhone,""))=TRIM(IFNULL(a2.BrokerOfficePhone,"")) OR TRIM(IFNULL(WhitePagesPhone,""))=TRIM(IFNULL(a1.Phone,"")) OR TRIM(IFNULL(WhitePagesPhone,""))=TRIM(IFNULL(a2.Phone,"")),NULL,"y"),' .
							'IF(TRIM(IFNULL(MLSPhone,""))="" OR TRIM(IFNULL(MLSPhone,""))=TRIM(IFNULL(a1.BrokerOfficePhone,"")) OR TRIM(IFNULL(MLSPhone,""))=TRIM(IFNULL(a2.BrokerOfficePhone,"")) OR TRIM(IFNULL(MLSPhone,""))=TRIM(IFNULL(a1.Phone,"")) OR TRIM(IFNULL(MLSPhone,""))=TRIM(IFNULL(a2.Phone,"")),NULL,"y"),' .
							'IF(TRIM(IFNULL(FSBOPhone,""))="" OR TRIM(IFNULL(FSBOPhone,""))=TRIM(IFNULL(a1.BrokerOfficePhone,"")) OR TRIM(IFNULL(FSBOPhone,""))=TRIM(IFNULL(a2.BrokerOfficePhone,"")) OR TRIM(IFNULL(FSBOPhone,""))=TRIM(IFNULL(a1.Phone,"")) OR TRIM(IFNULL(FSBOPhone,""))=TRIM(IFNULL(a2.Phone,"")),NULL,"y"),' .
							'IF(TRIM(IFNULL(p.Phone,""))="" OR TRIM(IFNULL(p.Phone,""))=TRIM(IFNULL(a1.BrokerOfficePhone,"")) OR TRIM(IFNULL(p.Phone,""))=TRIM(IFNULL(a2.BrokerOfficePhone,"")) OR TRIM(IFNULL(p.Phone,""))=TRIM(IFNULL(a1.Phone,"")) OR TRIM(IFNULL(p.Phone,""))=TRIM(IFNULL(a2.Phone,"")),NULL,"y"),' .
							'"n")';
				}
				elseif ($field == 'DNC') {
					return 'COALESCE(' .
							'IF(TRIM(IFNULL(TaxRecordsPhone,""))="" OR TRIM(IFNULL(TaxRecordsPhone,""))=TRIM(IFNULL(a1.BrokerOfficePhone,"")) OR TRIM(IFNULL(TaxRecordsPhone,""))=TRIM(IFNULL(a2.BrokerOfficePhone,"")) OR TRIM(IFNULL(TaxRecordsPhone,""))=TRIM(IFNULL(a1.Phone,"")) OR TRIM(IFNULL(TaxRecordsPhone,""))=TRIM(IFNULL(a2.Phone,"")),NULL,IF(IFNULL(p.TaxRecordsDNC,0)=0,"n","y")),' .
							'IF(TRIM(IFNULL(WhitePagesPhone,""))="" OR TRIM(IFNULL(WhitePagesPhone,""))=TRIM(IFNULL(a1.BrokerOfficePhone,"")) OR TRIM(IFNULL(WhitePagesPhone,""))=TRIM(IFNULL(a2.BrokerOfficePhone,"")) OR TRIM(IFNULL(WhitePagesPhone,""))=TRIM(IFNULL(a1.Phone,"")) OR TRIM(IFNULL(WhitePagesPhone,""))=TRIM(IFNULL(a2.Phone,"")),NULL,IF(IFNULL(p.WhitePagesDNC,0)=0,"n","y")),' .
							'IF(TRIM(IFNULL(MLSPhone,""))="" OR TRIM(IFNULL(MLSPhone,""))=TRIM(IFNULL(a1.BrokerOfficePhone,"")) OR TRIM(IFNULL(MLSPhone,""))=TRIM(IFNULL(a2.BrokerOfficePhone,"")) OR TRIM(IFNULL(MLSPhone,""))=TRIM(IFNULL(a1.Phone,"")) OR TRIM(IFNULL(MLSPhone,""))=TRIM(IFNULL(a2.Phone,"")),NULL,IF(IFNULL(p.MLSDNC,0)=0,"n","y")),' .
							'IF(TRIM(IFNULL(FSBOPhone,""))="" OR TRIM(IFNULL(FSBOPhone,""))=TRIM(IFNULL(a1.BrokerOfficePhone,"")) OR TRIM(IFNULL(FSBOPhone,""))=TRIM(IFNULL(a2.BrokerOfficePhone,"")) OR TRIM(IFNULL(FSBOPhone,""))=TRIM(IFNULL(a1.Phone,"")) OR TRIM(IFNULL(FSBOPhone,""))=TRIM(IFNULL(a2.Phone,"")),NULL,IF(IFNULL(p.FSBODNC,0)=0,"n","y")),' .
							'"n")';
				}

				return $field;
		}
	}

	function _getSearchFieldValueForDB($prefix,$field)
	{
		if (isset($_POST[$prefix.$field]))
		{
			$value = $this->input->post($prefix.$field,true);
			if (is_array($value))
				return implode(",",$value);
			else
				return $value;
		}

		return null;
	}

	function _getSearchFieldValues($fieldValue,$fieldType)
	{
		switch ($fieldType)
		{
			case "ynd":
			case "string":
				return "'".$fieldValue."'";
			case "date":
			{
				if (strpos($fieldValue,"00:00")>=0)
					return "DATE('".date("Y/m/d H:i:s", strtotime($fieldValue))."')";
				//else
				//	return " DATE('".$fieldValue."') ";
			}
			case "currentdate":
				return 'STR_TO_DATE("'.$this->selectedDate.'","%Y/%c/%d")';
			case "decimal":
				return (float)$fieldValue;
			case "int":
				return (int)$fieldValue;
			case "array":
			{
				$returnValue = "";

				foreach ($fieldValue as $value)
					$returnValue .= "'$value',";

				return substr($returnValue,0,strlen($returnValue)-1);
			}
			case "string_array":
			{
				$returnValue = "";
				$valueArray = explode(",",$fieldValue);

				foreach ($valueArray as $value)
					$returnValue .= "'$value',";

				return substr($returnValue,0,strlen($returnValue)-1);
			}
		}
	}

	function _setSearchLeadsData()
	{
		// Property types,statuses and restrictions
		$propertyStyleFieldNames = $this->model_utils->GetFields("properties","Style");
		$propertyStyleFieldNames = explode(",",strtolower($propertyStyleFieldNames[0]["FieldValues"]));
		sort($propertyStyleFieldNames);
		$propertyStyleDisplayNames = $this->model_utils->GetDisplayTexts($propertyStyleFieldNames);


		$propertyStatusFieldNames = $this->model_utils->GetFields("properties","Status");
		$propertyStatusFieldNames = explode(",",strtolower($propertyStatusFieldNames[0]["FieldValues"]));
		sort($propertyStatusFieldNames);
		$propertyStatusDisplayNames = $this->model_utils->GetDisplayTexts($propertyStatusFieldNames);

		$restrictionsFieldNames = $this->model_utils->GetFields("properties","Restrictions");
		$restrictionsFieldNames = explode(",",strtolower($restrictionsFieldNames[0]["FieldValues"]));
		sort($restrictionsFieldNames);
		$restrictionsDisplayNames = $this->model_utils->GetDisplayTexts($restrictionsFieldNames);

		$propertyStyles = array();
		$propertyStatuses = array();
		$restrictions = array();

		if (count($propertyStyleDisplayNames)==count($propertyStyleFieldNames))
		{
			for ($i=0;$i<count($propertyStyleFieldNames);$i++)
				$propertyStyles[$propertyStyleFieldNames[$i]] = $propertyStyleDisplayNames[$i]['Display'];
		}

		if (count($propertyStatusDisplayNames)==count($propertyStatusFieldNames))
		{
			for ($i=0;$i<count($propertyStatusFieldNames);$i++)
				$propertyStatuses[$propertyStatusFieldNames[$i]] = $propertyStatusDisplayNames[$i]['Display'];
		}

		if (count($restrictionsDisplayNames)==count($restrictionsFieldNames))
		{
			for ($i=0;$i<count($restrictionsFieldNames);$i++)
				$restrictions[$restrictionsFieldNames[$i]] = $restrictionsDisplayNames[$i]['Display'];
		}


		foreach ($propertyStatuses as $key=>$value)
			if (!in_array($key,array('exp','canc','with')))
				unset($propertyStatuses[$key]);

		$this->data['PROPERTY_STYLES'] = $propertyStyles;
		$this->data['PROPERTY_STATUSES'] = $propertyStatuses;
		$this->data['PROPERTY_RESTRICTIONS'] = $restrictions;
		$this->data['MULTIPLE_SELECT_FIELDS'] = array('Style','Status','Restrictions','ListStatus');

		//Fields
		$this->data['FIELDS_TO_DISPLAY'] = array('ZIP','ListPrice','Style','Status','Restrictions','ListStatus','Phone','DNC','LeadsPerPage');
		//$this->data['FIELDS_TO_DISPLAY'] = array('Added','ZIP','ListPrice','Style','Status','Restrictions','LeadsPerPage');

		$this->data['ALL_SEARCH_RULES'] = array
		(
			'eq'	=>	array('name'=>'Equal','value'=>'eq'),
			'ne'	=>	array('name'=>'Not equal','value'=>'ne'),
			'gt'	=>	array('name'=>'Greater than','value'=>'gt'),
			'lt'	=>	array('name'=>'Less than','value'=>'lt'),
			'btw'	=>	array('name'=>'Between','value'=>'btw')
			//'leq'	=>	array('name'=>'Less or equal','value'=>'leq'),
			//'geq'	=>	array('name'=>'Greater or equal','value'=>'geq'),
			//'like'	=>	array('name'=>'Like','value'=>'like')
		);

		$searchRulesNoLike = $this->data['ALL_SEARCH_RULES'];
		unset($searchRulesNoLike['like']);

		$searchRulesListPrice = $this->data['ALL_SEARCH_RULES'];
		unset($searchRulesListPrice['eq']);
		unset($searchRulesListPrice['ne']);

		$searchRulesAdded = $this->data['ALL_SEARCH_RULES'];
		unset($searchRulesAdded['gt']);
		unset($searchRulesAdded['lt']);
		unset($searchRulesAdded['ne']);

		$searchRulesZIP = $this->data['ALL_SEARCH_RULES'];
		unset($searchRulesZIP['btw']);
		unset($searchRulesZIP['gt']);
		unset($searchRulesZIP['lt']);

		$searchRulesEqNe = $this->data['ALL_SEARCH_RULES'];
		unset($searchRulesEqNe['gt'], $searchRulesEqNe['lt'], $searchRulesEqNe['btw']);

		$fv_phone = array(
			'd' => 'Display leads both with and without phone numbers',
			'y' => 'Display leads with phone numbers only',
			'n' => 'Display leads with no phone numbers only',
		);

		$fv_dnc = array(
			'd' => 'Display all leads',
			'y' => 'Display DNC leads only',
			'n' => 'Display non-DNC leads only',
		);

		$this->data['FIELDS'] = array
		(
			'Added'			=>	array('FieldName'=>'Added',        'Display'=>'Date',               'Type'=>'date',  'SearchRules'=>$searchRulesAdded),
			'ZIP'			=>	array('FieldName'=>'ZIP',          'Display'=>'ZIP Code',           'Type'=>'custom','SearchRules'=>$searchRulesZIP),
			'ListPrice'		=>	array('ListPrice'=>'Added',        'Display'=>'List Price',         'Type'=>'text',  'SearchRules'=>$searchRulesListPrice),
			'Style'			=>	array('FieldName'=>'Style',        'Display'=>'Residential Style',  'Type'=>'select','FieldValues'=>$this->data['PROPERTY_STYLES'],'SearchRules'=>$searchRulesEqNe),
			'Status'		=>	array('FieldName'=>'Status',       'Display'=>'Status',             'Type'=>'select','FieldValues'=>$this->data['PROPERTY_STATUSES'],'SearchRules'=>$searchRulesEqNe),
			'Restrictions'	=>	array('FieldName'=>'Restrictions', 'Display'=>'Sales Restrictions', 'Type'=>'select','FieldValues'=>$this->data['PROPERTY_RESTRICTIONS'],'SearchRules'=>$searchRulesEqNe),
			'ListStatus'	=>	array('FiledName'=>'ListStatus',   'Display'=>'Relisted',           'Type'=>'select','FieldValues'=>array('RELISTED' => 'Yes', ' ' => 'No'), 'SearchRules'=>$searchRulesEqNe),
			'Phone'			=>	array('FieldName'=>'Phone',        'Display'=>'Phone Numbers',      'Type'=>'select','FieldValues'=>$fv_phone),
			'DNC'			=>	array('FieldName'=>'DNC',          'Display'=>'DNC Registry',       'Type'=>'select','FieldValues'=>$fv_dnc),
			'LeadsPerPage'	=>	array('FieldName'=>'LeadsPerPage', 'Display'=>'Leads Per Page',     'Type'=>'select','SearchRules'=>array(),'FieldValues'=>$this->config->item('LEAD_LIST_ROWS')),
		);
	}

	function _setGoalsData()
	{
		$this->data['GOALS'] = $this->config->item('GOALS');
		$addedGoals = $this->model_user->GetUserInfoFieldByUserID($this->userID,"added_goals");
		$this->data['ADDED_GOALS'] = $addedGoals == 1 ? true : false;
		$this->data['GOAL_PERIODS'] = $this->config->item('GOAL_PERIODS');
		$this->data['IS_AJAX_REQUEST'] = IS_AJAX_REQUEST?1:0;
		$goalValuesTemp = $this->model_user->GetAllGoals($this->userID);


		$goalValues =array();
		foreach ($goalValuesTemp as $goalValueTemp)
			if ($goalValueTemp['name']=='attemptperappointment' || $goalValueTemp['name']=='contactperappointment')
				$goalValues[$goalValueTemp['name']] = $goalValueTemp;
			else
				$goalValues[$goalValueTemp['name'].'_'.strtolower($goalValueTemp['goal_period'])] = $goalValueTemp;

		$this->data['GOAL_VALUES'] = $goalValues;
	}

	/* END_REGION OTHER */

	/* REGION VALIDATION */
	function _dropdown_valid_selection($selectedItem)
	{
		if ($selectedItem=='-1')
		{
			$this->form_validation->set_message('_dropdown_valid_selection','Please select an option.');
			return false;
		}

		return true;
	}

	function _getNrOfNewLeads($NEW_PROPERTIES_COUNT=null)
	{
		if ($NEW_PROPERTIES_COUNT==null)
			$NEW_PROPERTIES_COUNT = $this->model_property->GetNrOfNewPropertiesForUser($this->userID);

		if ($NEW_PROPERTIES_COUNT === null)
			return 0;

		$nrOfNewLeads =0;
		foreach ($NEW_PROPERTIES_COUNT as $propertyCount)
			if (intval($propertyCount['Nr'])>0)
				$nrOfNewLeads += intval($propertyCount['Nr']);

		return $nrOfNewLeads;
	}

	function _statsPopupDataExists()
	{
		$showStatsPopup = false;

		$news = $this->model_news->GetNewsForUser($this->userID);
		$nrOfNewLeads = $this->_getNrOfNewLeads();
		$newPhoneNumberValues = $this->model_property->GetNrOfNewPhoneNumbers($this->userID);

		return count($news)>0 || intval($nrOfNewLeads)>0 || intval($newPhoneNumberValues[0])>0 || intval($newPhoneNumberValues[1])>0 || intval($newPhoneNumberValues[2])>0;
	}
    
    function check_image_exist($photos)
    {
        $new_photo = array();
        foreach ($photos as $photo)
        {
            if(file_exists($_SERVER['DOCUMENT_ROOT'] . "/upload/fsbo_images/" . $photo->name))
            {
                $new_photo[]->name = $photo->name;
            }
            else
            {
                //$this->model_property->remove_fsbo_image($photo->id);
            }
                
    
        }
        return $new_photo;
    }
    
    function get_sort_params()
    {
        $gridStatuses = $this->model_user->get_user_meta_sort_data($this->userID, $this->propertyType);

        if($this->propertyType == 'followups')
            $sortname = 'f_date';
        else
            $sortname = 'post_date';
            
        $sortorder = 'asc';
        
        
        foreach($gridStatuses as $status)
        {
            if($status['MetaKey'] == 'grid_sort_' . $this->propertyType)
                $sortname = $status['MetaValue'];
            if($status['MetaKey'] == 'grid_sort_order_' . $this->propertyType)
                $sortorder = $status['MetaValue'];
        }
        
        if($this->input->post('sortname'))
        {
            if($sortname != $this->input->post('sortname'))
                $sortname = $this->input->post('sortname');
        }
        
        if($this->input->post('sortorder'))
        {
            if($sortorder != $this->input->post('sortorder'))
                $sortorder = $this->input->post('sortorder');
        }
        
        return array('sortname'=>$sortname, 'sortorder'=>$sortorder);
    }
    
	/* END_REGION VALIDATION */
}
