jQuery.fn.extend({
    disableSelection : function() {
            this.each(function() {
                    this.onselectstart = function() { return false; };
                    this.unselectable = "on";
                    jQuery(this).css('-moz-user-select', 'none');
            });
    },
    enableSelection : function() {
            this.each(function() {
                    this.onselectstart = function() {};
                    this.unselectable = "off";
                    jQuery(this).css('-moz-user-select', 'auto');
            });
    }
});

/// <reference path="jquery-1.3.2.js" />
var refreshByDateSearch = false;
var refreshBySearch = false;
var actionDatesCacheCollection = {};
var resetSelectedDateToCurrentDate = true;
var adjacentRowDirection = 0;

var adminMode;

//Document ready
$j(document).ready
(
    function()
    {

        $j('#flexigrid').disableSelection();    
        drag_start = 0;
        curent_backgr = '';
        curent_text = '';
        
        adminMode = $j('#fldAdminMode').val()=="1"?true:false;

        $j(document).click(
            function () {
                jQuery('.list_items').addClass('removed');
                jQuery('#move_to_folder_list').hide();
            }
        );
        
        build_folder_list(400);
   
        //Adding AJAX links
        addAJAXLinksForFullRefresh();
        
        if (!adminMode)
        {
            //Add owner info next and previous links
            initOwnerInfoBinding();
            
            //Property info binding
            initPropertyInfoTypeBinding();
            
            //Photos binding
            initPhotosBinding();
            
            //GoogleMap binding
            initGoogleMapBinding();
            
            //Property menu links
            $j('div[id*=menulink_property]').click(function () {refreshPropertyInfo(this);});
        }
        //Drag and drop
        addDragAndDropBehavior();
        
        initLeadListBinding();
        
        if (!adminMode)
        {
            var propertyInfoType = $j('#fldPropertyInfoType').val();
            var selectedElement = $j('div[id='+propertyInfoType+'_menulink_property]');
            if (selectedElement.length==0)
                $j('div[id=info_menulink_property]').addClass('selected_propertyinfo_menu');
            else
                selectedElement.addClass('selected_propertyinfo_menu');
        }

        $j('#pid').live(
            'click',
            function(e)
            {
                var pid = $j(e.target).closest('div').find('input[name="pid"]').val();
                $j('#row' + pid + ' .button_delete_lead').click();
            }
        );
        
        //check_fsbo_cities();
        
   }
);


function check_drag_action(define, obj, folder_id)
{
    if(define == 'over')
    {
        if(this.curent_backgr == '' || this.curent_backgr == undefined)
        {
            this.curent_backgr = jQuery(obj).find('.li').css('background-image');
            this.curent_text = jQuery(obj).find('.li').text();           
        }

        if(drag_start == 1)
        {
            jQuery(obj).find('.li').css('background-image','url("/images/grid/f-li-move.gif")');
            jQuery(obj).find('.li').html('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
            
            jQuery('#move_to_folder').val(folder_id);
        }
    }
    else if(define == 'out')
    {
        jQuery(obj).find('.li').css('background-image', this.curent_backgr);
        jQuery(obj).find('.li').text(this.curent_text);   
        jQuery('#move_to_folder').val(0);
        
        this.curent_backgr = '';
        this.curent_text = '';
    }
        
        
}

function build_folder_list(folders_width)
{
    if(!folders_width || folders_width == undefined)
        folders_width = 450;
        
    current_width = 0;
    addmore = 0;
    max_addition_folder_size = 0;
    
    tr_html = '';
    
    $j('li[id^=grid_folder_]').each
    (
        function()
        {
            current_folder = jQuery(this).outerWidth(true);
            current_width = current_width + current_folder;
           
            if(current_width > folders_width)
            {
                if(current_folder > max_addition_folder_size)
                    max_addition_folder_size = current_folder;
                     
                addmore = 1;
            }
        }
    );
    
    if(addmore == 1)
    {
        jQuery('#more_arrow').removeClass('removed');
        
        if(max_addition_folder_size > 100)
            folders_width = folders_width - (max_addition_folder_size - 100);
  
    }
    current_width = 0;
    
    $j('li[id^=grid_folder_]').each
    (
        function()
        {
            current_folder = jQuery(this).outerWidth(true);
            current_width = current_width + current_folder;
           
            if(current_width > folders_width)
            {
                if(current_folder > max_addition_folder_size)
                    max_addition_folder_size = current_folder;
                     
                addmore = 1;
                obj = jQuery(this).clone();
                jQuery(this).remove();
                
                more_folder_id = jQuery(obj).attr('id').split('_')[2];
                more_folder_name = jQuery(obj).find('#grid_folder').html();
                more_folder_count = parseInt(jQuery(obj).find('.li').html().replace(/[^0-9]/g, ''));

                tr_html = '<tr class="add_folder_list" onclick="set_grid_filter(\'folder\', \'' + more_folder_id + '\', this);cancelBubble(event);"><td align="right" valign="middle"><a id="count_items" href="javascript:void(0);">' + more_folder_count + ' - </a></td><td align="left"><a id="more_folder_name" href="javascript:void(0);">' + more_folder_name + '</a></td></tr>';
                
                jQuery('#add_more_folders').append(tr_html);
            }
        }
    );
        
}


function initLeadListBinding()
{
    //New lead creation
    addLeadPopupCreationBehavior();
    
    //Select date
    addSelectedDateBehavior();
    
    addSavedSearchBindings();
}

function addSavedSearchBindings()
{
    $j('#saved_lead_search').change
    (
        function()
        {
           var value = $j(this).val();
           var limit = $j(this).selectedOptions()[0].id.replace(value+"_","");
           
           if (value!="")
           {
               $j('#saved_search_operations').removeClass('hidden');
               if (limit>0)
               {
                   $j('select[name=rp]').val(limit);
                   $j("#lead_grid").flexOptions({ rp:limit });
               }
               //$j('#lead_date_text').text($j('#fldCurrentVerboseDate').val());
               //$j('#lead_date_text').addClass('hidden');
               //getSavedSearchList();
               
               $j("#fldSelectedDate").val(value);
               
               refreshBySearch = true;
               refreshLeadGrid(true,value, true);
           }
           else
               clearSelectedSavedLeadSearch();
        }
    );

    $j('#saved_lead_search_form').jqTransform({imgPath:BASE_URL+'img/leads'}); 
    
    $j('#btnDeleteSavedSearch').click
    (
        function()
        {
            var parameters = {};
            parameters['searchNameDelete'] = $j.trim($j('#saved_lead_search').val());
            parameters['searchName'] = '[EMPTY]';
            
            if (parameters['searchNameDelete'] == '')
                return false;
            
            blockElement('#saved_search_list_container');
            $j.post
            (
                BASE_URL+'leads/deleteSavedSearch',
                parameters,
                function(data)
                {
                    if (data=="1")
                        jAlert("Search deleted");
                    else
                        jAlert("An error occured during delete!");
                    getSavedSearchList();
                    refreshLeadGrid(false,null,true);
                    unblockElement('#saved_search_list_container');
                }
            );
        }
    );
    
    $j('#btnEditSavedSearch').click
    (
        function()
        {
            blockElement('#leads_list');
            createSearchLeadsPopup($j.trim($j('#saved_lead_search').val()),true);
        }
    );
}

function clearSelectedSavedLeadSearch()
{
    changeSelectedSearch("",refreshGridAfterEmptySelectedSavedLeadSearch);

    if (resetSelectedDateToCurrentDate)
    {
        var currentDate = $j('#fldCurrentDate').val();
        var date = new Date(currentDate);
        
        //$j('#lead_date').datepicker('setDate',date);
        //$j('#fldSelectedDate').val(currentDate);
    }
    resetSelectedDateToCurrentDate = true;
    
    $j('#saved_search_operations').addClass('hidden');
}

function refreshGridAfterEmptySelectedSavedLeadSearch() // :)
{
    //getSavedSearchList();
    refreshByDateSearch = true;
    refreshLeadGrid(true,null,true);
}

function changeSelectedSearch(searchName,callback)
{
    var parameters = {};
    parameters['searchName'] = searchName;
    if (searchName=='')
        parameters['searchFilter'] = '[EMPTY]';
    $j.post
    (
        BASE_URL+'leads/changeSelectedSearch',
        parameters,
        function()
        {
            if (callback) callback();
        }
    );
}

function addLeadPopupCreationBehavior()
{
    addAJAXLinks('#add_new_lead');
    addAJAXLinks('#search_lead');
    
    $j('#add_new_lead').click
    (
        function(e)
        {
            e.preventDefault();
            createNewLeadPopup();
		}
	);
	
    $j('#search_lead').click
    (
        function(e)
        {
            e.preventDefault();
            createSearchLeadsPopup();
		}
	);
}

function initLeadGrid()
{
     setSelectedLeadGridRow(false,refreshBySearch);
     setGridOptions();

     if (refreshByDateSearch || refreshBySearch)
     {
         refreshOwnerInfo(null,true);
         refreshByDateSearch = false;
         refreshBySearch = false;
     }
 
}

function setGridOptions()
{

	if ($j('#fldPropertyType').val()=='followups')
    {
        $j('#flexigrid .hDiv th').each
        (
            function()
            {
                var abbr = $j(this).attr('abbr');
                $j(this).attr('rel',abbr);
                $j(this).removeAttr('abbr');
            }
        );
        $j('#flexigrid .hDiv th').unbind('click');
    }

	addLeadGridRowEvents();
	addDeleteGridLeadBehavior();
    addEditGridLeadBehavior();
    addNeighborManagementBehavior();

	$j("#lead_grid").flexOptions({ params:[{name:'pageByID',value:0}] });

	addFlexigridBindings();

	 var leadGridHeight = $j('#fldLeadGridHeight').val();
    // if (leadGridHeight!="")
        //$j('#flexigrid div.bDiv').height(parseInt(leadGridHeight));

	 $j('#flexigrid tr[id^=row-1]').each
     (
        function()
        {
            var $element = $j(this);
            var text = $element.children(':first').children(':first').text();
            
            var nrOfChildren = $element.children().length;
            
            $element.children().remove();
            $element.unbind('click');
            $element.append('<td colspan="'+nrOfChildren+'"><div class="center_text followup_header" style="width:902px;">'+text+'</div></td>').click
        }
     );
	
     if ($j.browser.msie && $j.browser.version.substring(0, 1) === '6')
        $j('#flexigrid .cDrag').remove();
     
     unblockElement('#leads_leadslist_innercontent');
}

function afterFlexigridSearch(emptyGrid, current_property_id)
{
    if (emptyGrid)
    {
        $j('#fldPropertyID').val('0');
       // $j('#propertyinfo_innercontent').html('<div class="center_text">No data</div>');
       // $j('#leads_panel_ownerinfo').html('<div class="center_text">No data</div>');
        
        setGridOptions();
        refreshOwnerInfo(null,false);
    }
    else
    {        
        if(current_property_id == 0)
            setSelectedLeadGridRow(false,true);
        else if($j('#row' + current_property_id).length > 0)
            $j('#row' + current_property_id).addClass('trSelected');
        
        refreshOwnerInfo(null,false);
        refreshBySearch = false;
        initLeadGrid();
        
        if(current_property_id == 0)
            setSelectedLeadGridRow(false,true);
        else if($j('#row' + current_property_id).length > 0)
            $j('#row' + current_property_id).addClass('trSelected')

        if($j('#row' + current_property_id).length > 0) 
            init_selected_row();
        
        $j(".trSelected").draggable({ 
            start: function(event, ui) {drag_start = 1;},
            stop: function(event, ui) { stop_draggable(); },
            cursor: 'move' 
        });

    }
}

function stop_draggable()
{
    drag_start = 0;
    folder_id = parseInt(jQuery('#move_to_folder').val());
    
    if(folder_id > 0)
    {
        selected_ids = {};
        $j('.am').each
        (
            function(key)
            {
                if(jQuery(this).attr('checked'))
                    selected_ids[key] = jQuery(this).val(); 
            }
        );
        
        jQuery.ajax({
			type: "POST",
			url: "/leads/ajax_move_to_folder",
			dataType: 'json',
			data:     {selected_ids:selected_ids,folder_id:folder_id},
			cache:    false,
			success: function(data){
               if(data.success == 'success')
               {
                    jQuery('#check_main').attr('checked', false);
                    jQuery('#move_to_folder_list').hide();
                    get_user_folders_list();
                    
                    jQuery('#grid_folder_value').val(0);
                    jQuery('#grid_category_value').val(0);
                    jQuery('#grid_relation_value').val(0);
                    
                    jQuery('#all_contacts_link').addClass('red_link');

                    refreshLeadGrid(false,false,false,true);
               }
			}
		});
    }
}

function add_to_select(property_id, obj)
{
    current_property = $j('#fldPropertyID').val();
    
    if(current_property != property_id)
    {
        if($j(obj).attr('checked'))
        {
            $j('#row' + property_id).addClass('trSelected');
            set_star_in_grid(this, 'set');
        }
        else
        {
            $j('#row' + property_id).removeClass('trSelected');
            set_star_in_grid(this, 'set');
        }
    }
}

function init_selected_row(without_selected)
{
    $j('.trSelected').each
    (
        function()
        {
            if(without_selected)
            {
                current_property = $j('#fldPropertyID').val();
                if('row' + current_property != jQuery(this).attr('id'))
                    set_star_in_grid(this, 'set');
            }
            else
                set_star_in_grid(this, 'set');
        }
    );
}


function addFlexigridBindings()
{
     $j('#flexigrid input[type=submit][value=Search]').add('#flexigrid input[type=button][value=Clear]').click(setFlexigridRefreshOptions);
     $j('#flexigrid .sDiv input[type=text][name=q]').add('#flexigrid .sDiv select[name=qtype]').keypress
     (
        function(e)
        {
            if (e.which==13)
                setFlexigridRefreshOptions();
        }
     );
}

function setFlexigridRefreshOptions()
{
     refreshBySearch = true;
}

function addSelectedDateBehavior()
{
    var elementSelector = '#ui-datepicker-div';
    var blockElementSelector = 'body';

    $j("#lead_date").datepicker
    ({ 
        setDate: new Date(jQuery('#fldSelectedDate').val()),
        showOn: 'button', 
        dateFormat: 'yy/m/d',
        buttonImageOnly: true, 
        buttonImage: BASE_URL+'images/leads/calendar.gif',
        css: {'z-index':'2'},
        beforeShow: function(input)
                    {
                        blockElement(blockElementSelector);
                        setSelectedActionDays(new Array());
                        var selectedDate = new Date($j('#fldSelectedDate').val());
                        getNotEmptyDays(selectedDate.getFullYear(),selectedDate.getMonth()+1);
                        getActionDaysForCalendarFSBO(selectedDate.getFullYear(),selectedDate.getMonth()+1,elementSelector,true,function(){unblockElement(blockElementSelector);},actionDatesCacheCollection);

                    },
        onChangeMonthYear:  function(year,month,obj)
                            {
                                blockElement(blockElementSelector);
                                getNotEmptyDays(year,month);
                                getActionDaysForCalendarFSBO(year,month,elementSelector,true,function() {unblockElement(blockElementSelector);},actionDatesCacheCollection);
                                
                            },
        onSelect: function(date,element) 
                  {
                    var selectedDate = new Date(date);

                    var dayName = DAY_NAMES[selectedDate.getDay()];
                    var monthName = MONTH_NAMES[selectedDate.getMonth()];
                    var day = selectedDate.getDate();
                    var year = selectedDate.getFullYear();

                    $j('#lead_date_text').html(dayName+', '+monthName+' '+day+', '+year);
                    $j('#fldSelectedDate').val(date);
                    refreshByDateSearch = true;
                    resetSelectedDateToCurrentDate = false;

                    //$j('#saved_lead_search_form .jqTransformSelectWrapper ul a:first').click();
                    //$j('#saved_lead_search').selectOptions('');

                    $j('#lead_date_text').removeClass('hidden');
                    blockElement('#leads_leadslist_innercontent');

                    refreshLeadGrid(false,null,null,true);
                  }
    }); 

    $j("#lead_date").datepicker( "setDate" , new Date(jQuery('#fldSelectedDate').val())); 
     
}

function addAJAXLinksForFullRefresh()
{
    if (!adminMode)
    {
        addAJAXLinks('#menu_property_info div[class*=ajax_link]');
        addAJAXLinks('#leads_goals_verticaltext');
        addAJAXLinks('#ownerinfo_links div[class*=ajax_link]');
    }
    addAJAXLinks('#leads_leadslist_innercontent div[class*=ajax_link]');
}

function initOwnerInfoBinding()
{
    if (!adminMode)
        $j('div.property_ownerinfo_link').click(function () {refreshOwnerInfo(this,false);});
}

//function onEditLeadPage()
//{
//    var $element = $j('#edit_lead_container');

//    return $element.length == 1 && $element.css('display') != 'none';
//}

function refreshOwnerInfo(element,getFirstProperty)
{
    ///	<summary>
    ///		Refreshes the owner info container
    ///	</summary>
    ///	<param name="element" type="jQuery">
    ///		If the element is null it means that the function is called from another event.
    ///     Otherwise, the function is called from the initOwnerInfoBinding function
    ///	</param>
    ///	<returns type="void" />
    
    if (!adminMode)
    {
        var parameters = {};
        parameters['propertyType'] = $j('#fldPropertyType').val();
        adjacentRowDirection = 0;
        if (getFirstProperty==true)
        {
            parameters['propertyID'] = 0;
            parameters['selectedDate'] = $j('#fldSelectedDate').val();
            $j('#fldPropertyID').val(parameters['propertyID']);
        }
        else
        {   
            if (element!=null)
            {
                parameters['propertyID'] = $j('#fld_'+ element.id).val();
                $j('#fldPropertyID').val(parameters['propertyID']);
                
                if (element.id.indexOf("previous")>=0)
                    adjacentRowDirection = -1;
                else if (element.id.indexOf("next")>=0)
                    adjacentRowDirection = 1;
                
            }
            else
                parameters['propertyID'] = $j('#fldPropertyID').val();
        } 
          
        if(jQuery('#row' + parameters['propertyID']).length == 0 && adjacentRowDirection != 0)
        {
            set_page_for_current_property(parameters['propertyID']);
        }
            
        
        var selectedRow = $j('#lead_grid tbody tr[id=row'+parameters['propertyID']+']');
        if (getFirstProperty==true)
            selectedRow = $j('#lead_grid tbody tr:first');
            
        var previousID = -1;
        var nextID = -1;
        
        if (selectedRow!=undefined && selectedRow.length>0)
        {
            var previousRow = selectedRow.prevAll(':not([id^=row-1]):first');
            if (previousRow.length>0)
                previousID = previousRow.attr('id').replace("row","");
                
            var nextRow = selectedRow.nextAll(':not([id^=row-1]):first');
            if (nextRow.length>0)
                nextID = nextRow.attr('id').replace("row","");
            
        }
        parameters["previousPropertyID"] = previousID;
        parameters["nextPropertyID"] = nextID;
        
        if (refreshByDateSearch)
            parameters['searchName'] = '[DEFAULT]';
        
        parameters["fldPropertyType"] = $j('#fldPropertyType').val();
        
        blockElement('#leads_ownerinfo_innercontent');
        blockElement('#owner_info_item');
        
        if (!getFirstProperty)
            refreshPropertyInfo(null);
            
        var myRand=parseInt(Math.random()*99999999);
        $j('#content_info_block').load
        (
            BASE_URL + 'leads/getOwnerInfo/' + myRand,
            parameters,
            function() {
                if (getFirstProperty) {
                    setSelectedLeadGridRow(false, true);
                    refreshPropertyInfo(null);
                }
                else if (element != null)
                    setSelectedLeadGridRow(true);

                addAJAXLinks('#ownerinfo_links div[class*=ajax_link]');


                if ($j('#fldHasNeighbors').val() == '0')
                    $j('#neighbors_menulink_property').addClass('disabled').removeClass('pointer');
                else
                    $j('#neighbors_menulink_property').removeClass('disabled').addClass('pointer');

                if($j('#fldPropertyType').val() == 'fsbo')
                {
                    if ($j('#count_photo').val() > 0)
                    {
                        html = '<div onclick="active_tab(\'more\');" id="photos_menulink_property"><a href="javascript:void(0);">Pictures</a></div>';
                        
                    }
                    else
                    {
                        html = '<div style="color:#b6b6b6;">Pictures</div>';
                    }
                    $j('#more_tab').html(html);
                    $j('#photos_menulink_property').click(function () {refreshPropertyInfo(this);});
                    
                }
                /*jQuery('.row_selected').removeClass('row_selected');
                jQuery('#search_list_' + parameters['propertyID']).parents('#item').addClass('row_selected');*/
                
                unblockElement('#leads_ownerinfo_innercontent');
                unblockElement('#owner_info_item');

                initOwnerInfoBinding();
                
                refresh_search_container();
                
                property_val = $j('#fldPropertyID').val();
                
                
                if(jQuery('#row' + property_val).length > 0)
                    jQuery('.bDiv').scrollTo(jQuery('#row' + property_val), 800);

            }
         );
         
         if(parameters["fldPropertyType"] == 'fsbo' || $j('#owner_edit_icons').length > 0)
         {
            jQuery.ajax({
                type: "POST",
                url: "/leads/get_owner_icons",
                dataType: 'json',
                async: false,
                cache:    false,
                success: function(data){
                    if(data != null)
                    {
                        $j('#owner_edit_icons').html(data.result)
                    }
                        
                }
            }); 
         }
         
         addTooltip('#lead_help');
     }
}


function refresh_search_container()
{
    jQuery.ajax({
        type: "POST",
        url: "/leads/getOwnerSearchInfo",
        dataType: 'json',
        async: false,
        cache:    false,
        success: function(data){
            if(data != null)
            {
                jQuery('#content_info_full').html(data.result);
                property_val = $j('#fldPropertyID').val();
                
                if(jQuery('.scroll_item_' + property_val).length > 0)
                    jQuery('#content_info').scrollTo(jQuery('.scroll_item_' + property_val), 800);
            }
                
        }
    });
}

function refreshLeadGrid(refreshBySearch,searchName,emptySearch,setFirst)
{
    
   var parameters = new Array();
    
   if (refreshBySearch)
   {
       if (searchName != null)
           parameters = [{ name: 'searchName', value: searchName}];
       else if (emptySearch)
           parameters = [{ name: 'searchName', value: '[EMPTY]'}];
       else
           parameters = [{ name: 'searchName', value: '[DEFAULT]'}];
   }
   else
   { 
       parameters = [
                    { name: 'searchName', value: '[DEFAULT]' }
                    ,{ name: 'pageByID', value: 1 }
                    ,{ name: 'selectedDate', value: $j('#fldSelectedDate').val()}
                    ,{ name : 'folder', value : jQuery('#grid_folder_value').val()}
                    ,{ name : 'category', value : jQuery('#grid_category_value').val()}
                    ,{ name : 'relation', value : jQuery('#grid_relation_value').val()}];

   }
      
   if (refreshByDateSearch)
       parameters[parameters.length] = { name: 'propertyID', value: 0 };

   $j("#lead_grid").flexOptions
   ({
       params: parameters,
       onSuccess: function() {

            refreshBySearch = false; 
            initLeadGrid(); 
            if(setFirst)
                setSelectedLeadGridRow(false,true);
            else
                setSelectedLeadGridRow(false,false,true);
        
            init_selected_row();
            refreshOwnerInfo(null,false);
            
            $j(".trSelected").draggable({ 
                start: function(event, ui) {drag_start = 1;},
                stop: function(event, ui) { stop_draggable(); },
                cursor: 'move' 
            });
        },
       url: BASE_URL+'leads/buildGrid_new'
   }).flexReload(this); 

}

function addDeleteGridLeadBehavior()
{
    ///	<summary>
    ///		Removes the lead from the list
    ///	</summary>
    ///	<returns type="void" />
    $j('.button_delete_lead').click
    (
        function()
        {
            row = this;
            
            if($j('#fldPropertyType').val() == 'followups')
            {
                message = 'Are you sure you want to delete this follow up?';
                jConfirm
                (
                    message,
                    'Delete Follow Up',
                    function(result)
                    {
                        if(result)
                        {
                            var currentRow = $j(row).parents("tr");
                            var propertyID = $j(currentRow).attr('id').replace("row","");
                            var isLastRow = ($j('#lead_grid tbody').children('tr').length==0);
                          
                            blockElement('#lead_grid');
                            
                            if (propertyID==$j('#fldPropertyID').val())
                            {
                                var nextPropertyID;
                                var previousRow = $j(currentRow).nextAll(':not([id^=row-1]):first');
                                
                                if (previousRow.length==1)
                                    nextPropertyID = previousRow[0].id.replace("row","");
                                else
                                {
                                    var nextRow = $j(currentRow).prevAll(':not([id^=row-1]):first');
                                    if (nextRow.length==1)
                                        nextPropertyID = nextRow[0].id.replace("row","");
                                    else
                                        nextPropertyID = -1;
                                }
                            
                                $j('#fldPropertyID').val(nextPropertyID);
                            }
                            
                            if (isLastRow)
                            {
                                var currentPage = parseInt($j('#fldPageNr').val());
                                if (currentPage>1)
                                    $j('#fldPageNr').val(currentPage-1);
                                else
                                    $j('#fldPageNr').val(1);
                            }
                        
                            $j(currentRow).remove();
                            
                            var parameters = {};
                            parameters['propertyID'] = propertyID;
                            
                            $j.post
                            (
                                BASE_URL+'leads/deleteFollowUp',
                                parameters,
                                function(data)
                                {
                                    if (data!="1")
                                        jAlert("An error occured!");
                                        
                                    var $followUpMode = $j('#fldFollowUpMode');
                                    if ($followUpMode.length==1 && $followUpMode.val()=='1')
                                        refreshLeadGrid(false,null);
                                }
                            );
                            
                            setSelectedLeadGridRow(false,false);
                            refreshOwnerInfo(null,false);
                            init_selected_row();
                            unblockElement('#lead_grid');
                           
                            return false;
                        }
                    }
                );
            }
            else
            {
                message = 'Are you sure you want to delete the selected contact?';
                jConfirm
                (
                    message,
                    'Delete Contact',
                    function(result)
                    {
                        if(result)
                        {
                            var currentRow = $j(row).parents("tr");
                            var propertyID = $j(currentRow).attr('id').replace("row","");
                            var isLastRow = ($j('#lead_grid tbody').children('tr').length==0);
                          
                            blockElement('#lead_grid');
                            
                            if (propertyID==$j('#fldPropertyID').val())
                            {
                                var nextPropertyID;
                                var previousRow = $j(currentRow).nextAll(':not([id^=row-1]):first');
                                
                                if (previousRow.length==1)
                                    nextPropertyID = previousRow[0].id.replace("row","");
                                else
                                {
                                    var nextRow = $j(currentRow).prevAll(':not([id^=row-1]):first');
                                    if (nextRow.length==1)
                                        nextPropertyID = nextRow[0].id.replace("row","");
                                    else
                                        nextPropertyID = -1;
                                }
                            
                                $j('#fldPropertyID').val(nextPropertyID);
                            }
                            
                            if (isLastRow)
                            {
                                var currentPage = parseInt($j('#fldPageNr').val());
                                if (currentPage>1)
                                    $j('#fldPageNr').val(currentPage-1);
                                else
                                    $j('#fldPageNr').val(1);
                            }
                        
                            $j(currentRow).remove();
                            
                            var parameters = {};
                            parameters['propertyID'] = propertyID;
                            
                            $j.post
                            (
                                BASE_URL+'leads/deleteLead',
                                parameters,
                                function(data)
                                {
                                    if (data!="1")
                                        jAlert("An error occured!");
                                        
                                    var $followUpMode = $j('#fldFollowUpMode');
                                    if ($followUpMode.length==1 && $followUpMode.val()=='1')
                                        refreshLeadGrid(false,null);
                                }
                            );
                            
                            setSelectedLeadGridRow(false,false);
                            refreshOwnerInfo(null,false);
                            init_selected_row();
                            unblockElement('#lead_grid');
                           
                            return false;
                        }
                    }
                );
            }  
        }
    );
}

function addEditGridLeadBehavior()
{
    $j('.button_edit_lead').click
    (
        function()
        {
            var currentRow = $j(this).parent("div").parent("td").parent("tr")[0];
            var propertyID = currentRow.id.replace("row", "");

            var propertyIDs = getAdjacentPropertyIDs(propertyID, "getPrevAndNextPropertyIDs");

            getEditLeadContent(propertyID, propertyIDs[0], propertyIDs[1]);

            $j('#admin_content').hide();
            $j('#leads_list').toggleClass('removed');
            $j('#property_utils').toggleClass('hidden');
            $j('#edit_lead_container').show();
            $j('#neighbors_container').hide();
        }
    );
}

function getEditLeadContent(propertyID,previousPropertyID,nextPropertyID)
{
    var parameters = {};
    parameters['propertyID'] = propertyID;
    parameters['nextPropertyID'] = nextPropertyID;
    parameters['previousPropertyID'] = previousPropertyID;
    blockElement('#leads_content');

    $j('#edit_lead_container').load
    (
        BASE_URL + 'admin/getEditLeadContent',
        parameters,
        function()
        {
            addLeadFormValidation('#frmEditLead');

            // Submit button
            $j('#btnUpdate').click
            (
                function(e)
                {
                    e.preventDefault();
                    submitForm('frmEditLead', '', 'Error updating lead', 'frmEditLead', refreshGridNormal);
                }
            );

            $j('#button_cancel').click(hideEditLeadContainer);
            addAJAXLinks('#btn_back_edit_lead');
            $j('#btn_back_edit_lead').click(hideEditLeadContainer);

            addDateField("#frmEditLead .date_input");
            restrictKeyboardAction("#frmEditLead .date_input");
            unblockElement('#leads_content');

            $j('.property_link').click
            (
                function()
                {
                    var parameters = {};
                    var propertyID = $j('#fldPropertyID').val();
                    var elementID = this.id;
                    var newPropertyID, nextPropertyID, prevPropertyID, newNextPropertyID, newPrevPropertyID;

                    prevPropertyID = $j('#fldPreviousPropertyID').val();
                    nextPropertyID = $j('#fldNextPropertyID').val();
                    
                    if (elementID == 'next_property')
                    {
                        newNextPropertyID = getAdjacentPropertyIDs(nextPropertyID, "getNextPropertyID");
                        getEditLeadContent(nextPropertyID, propertyID, newNextPropertyID);
                    }
                    else if (elementID == 'previous_property' && prevPropertyID != null)
                    {
                        newPrevPropertyID = getAdjacentPropertyIDs(prevPropertyID, "getPreviousPropertyID");
                        getEditLeadContent(prevPropertyID, newPrevPropertyID, propertyID);
                    }
                }
            )
        }
    );
}

function getAdjacentPropertyIDs(propertyID,method)
{
    var parameters = {};
    var response;
    
    parameters['adminMode'] = true;
    parameters['propertyID'] = propertyID;
    
    $j.ajax
    ({
        type: "POST",
        url: BASE_URL + "leads/" + method,
        data: parameters,
        success: function(data)
        {
            response = data;
        },
        dataType: "json",
        async: false
    });

    return response;
}

function refreshGridNormal()
{
    refreshLeadGrid(false);
}

function hideEditLeadContainer()
{
     $j('#leads_list').toggleClass('removed');
     $j('#property_utils').toggleClass('hidden');
     $j('#edit_lead_container').hide();
     $j('#admin_content').hide();
     $j('#neighbors_container').hide();
}

function addNeighborManagementBehavior()
{
    $j('.button_neighbor_management').click
    (
        function()
        {
            var currentRow = $j(this).parent("div").parent("td").parent("tr")[0];
            var propertyID = currentRow.id.replace("row","");
            var parameters = {};
            
            parameters['propertyID'] = propertyID;
            
            blockElement('#top_panel');
           
            $j('#neighbors_container').load
            (
                BASE_URL+'admin/getNeighborManagementContent',
                parameters,
                function()
                {
                    unblockElement('#top_panel');
                    
                    addAJAXLinks('#btn_back_neighbor');
                    $j('#btn_back_neighbor').click(hideNeighborsContainer);
                    
                    $j('#admin_content').hide();
                    $j('#leads_list').toggleClass('removed');
                    $j('#property_utils').toggleClass('hidden');
                    $j('#edit_lead_container').hide();
                    $j('#neighbors_container').show();
                }
            );
        }
    );
}


function hideNeighborsContainer()
{
    $j('#admin_content').hide();
    $j('#leads_list').toggleClass('removed');
    $j('#property_utils').toggleClass('hidden');
    $j('#edit_lead_container').hide();
    $j('#neighbors_container').hide();
}

function refreshPropertyInfo(element)
{
    ///	<summary>
    ///		Refreshes the data in the Property info container
    ///	</summary>
    ///	<param name="element" type="jQuery">
    ///		If the element is null it means that the function is called from another event.
    ///     Otherwise, the function is called from the document ready event binder
    ///	</param>
    ///	<returns type="void" />

    if (element != null && $j(element).hasClass('disabled'))
        return;
    if (!adminMode)
    {
        var parameters = {};
        parameters['propertyType'] = $j('#fldPropertyType').val();
        parameters['propertyID'] = $j('#fldPropertyID').val();

        if (element!=null)
        {
            parameters['propertyInfoType'] = element.id.substring(0,element.id.indexOf('_'));
            $j('#fldPropertyInfoType').val(parameters['propertyInfoType']);
        }
        loadPropertyInfo('getPropertyInfo',parameters);
    }
 }
 
 
 function refreshPropertyInfo_popup(element)
{
    
    ///	<summary>
    ///		Refreshes the data in the Property info container
    ///	</summary>
    ///	<param name="element" type="jQuery">
    ///		If the element is null it means that the function is called from another event.
    ///     Otherwise, the function is called from the document ready event binder
    ///	</param>
    ///	<returns type="void" />

    if (element != null && $j(element).hasClass('disabled'))
        return;
    if (!adminMode)
    {
        var parameters = {};
        parameters['propertyType'] = $j('#fldPropertyType').val();
        parameters['propertyID'] = $j('#fldPropertyID').val();

        if (element!=null)
        {
            parameters['propertyInfoType'] = element.id.substring(0,element.id.indexOf('_'));
            $j('#fldPropertyInfoType').val(parameters['propertyInfoType']);
        }
        getPropertyInfo_popup('getPropertyInfo',parameters);
    }
 }
 
 function loadPropertyInfo(methodName,parameters)
 {
    if (!adminMode)
    {
        blockElement('#propertyinfo_block');
        var myRand=parseInt(Math.random()*99999999);
        
        $j('#propertyinfo_innercontent').load
        (
            BASE_URL+'leads/'+methodName+'/'+myRand,
            parameters,
            function()
            {
                initHistoryAndNeighborsBinding(parameters);
                initPhotosBinding();
                initGoogleMapBinding();
                initInfoBinding();
                
                var propertyInfoType;
                
                if (parameters['propertyInfoType']!=undefined)
                    propertyInfoType = parameters['propertyInfoType'];
                else
                    propertyInfoType = $j('#fldPropertyInfoType').val();
                 
                $j('div[id*=_menulink_property]').removeClass('selected_propertyinfo_menu');
                $j('div[id='+propertyInfoType+'_menulink_property]').addClass('selected_propertyinfo_menu');
                
                $j('#propertyinfo_block').unblock();
            }
         );
     }
 }
 
 function getPropertyInfo_popup(methodName,parameters)
 {

    if (!adminMode)
    {
        blockElement('#propertyinfo_block_popup');
        var myRand=parseInt(Math.random()*99999999);
        
        $j('#propertyinfo_innercontent_popup').load
        (
            BASE_URL+'leads/'+methodName+'/'+myRand,
            parameters,
            function()
            {
                initHistoryAndNeighborsBinding(parameters);
                initPhotosBinding();
                initGoogleMapBinding();
                initInfoBinding();
                
                var propertyInfoType;
                
                if (parameters['propertyInfoType']!=undefined)
                    propertyInfoType = parameters['propertyInfoType'];
                else
                    propertyInfoType = $j('#fldPropertyInfoType').val();
                 
                $j('div[id*=_menulink_property_popup]').removeClass('selected_propertyinfo_menu');
                $j('div[id='+propertyInfoType+'_menulink_property_popup]').addClass('selected_propertyinfo_menu');
                
                $j('#propertyinfo_block_popup').unblock();
            }
         );
     }
 }
 
 function initInfoBinding()
 {
     addTooltip('#tooltip_nod');
 }
 
 function initHistoryAndNeighborsBinding(parameters)
 {
    if (!adminMode)
    {
        var onHistoryPage = false;
        var onNeighborsPage = false;
        
        if ((parameters!=null && parameters['propertyInfoType']=="history") || $j('#fldPropertyInfoType').val()=="history")
            onHistoryPage = true;
        
        if ((parameters!=null && parameters['propertyInfoType']=="neighbors") || $j('#fldPropertyInfoType').val()=="neighbors")
            onNeighborsPage = true;
        
        if (onHistoryPage || onNeighborsPage)
        {
            var areNeighbors = true;
            bindEmptyNoteTextArea();
            addLeadStatusBehavior();
            addDeleteLeadStatusBehavior();
            addDateTimeField('#status_date');
            restrictKeyboardAction('#status_date');
            
            if (onNeighborsPage)
            {
                if($j('#areNeighbors').val() == 'false')
                    areNeighbors = false;
                addAJAXLinks('#lead_neighbor div.ajax_link');
                addTooltip("#tooltip_neighbors");
                addNeighborPaginationBinding();
            }
            
//         if (onHistoryPage)
//             addActionDateBindings();

            //remove the padding of the content panel
            //replacing bottom pictures
            $j('#leads_panel_content').removeClass('leads_panel_container');

            if(areNeighbors)
                addLeadContentBottomGreyImages();
            else
                addLeadContentBottomWhiteImages();

            adjustLeadStatusContainers();
        }
        else
        {
            addLeadContentBottomWhiteImages();
        }
    }
}

function adjustLeadStatusContainers()
{
    var maxHeight = 0;
    $j('#lead_statuses .leads_actions').each
    (
        function()
        {
            if ($j(this).height() > maxHeight)
                maxHeight = $j(this).height();
        }
    );
    $j('#lead_statuses .leads_actions').height(maxHeight);
    
    if($j('#block_3').length > 0)
    {
        $j('#block_2').height(maxHeight - 32);
        $j('#block_3').height('auto');
    }
    
}

function initPhotosBinding()
{
    if (!adminMode)
    {
        var thumbnailContainer = $j('#lead_photo_thumbnail_container');
        
        if (thumbnailContainer.children().length>0)
            $j('.lead_photo').attr('src',thumbnailContainer.children('img:first').attr('src').replace('_thumb',''));    
    
        $j('img[class*=lead_photo_thumb]').click
        (
            function()
            {
                var element = $j(this);
                blockElement('#lead_photo_big');
                $j.ajax
                (
                    {
                      url: element.attr('src').replace('_thumb',''),
                      data: null,
                      dataType: 'html',
                      success: function (html) 
                      {
                          $j('.lead_photo').attr('src',element.attr('src').replace('_thumb',''));
                          $j('#lead_photo_big').unblock();
                      },
                      error: function(e)
                      {
                          if (e.status==200)
                          {
                             $j('.lead_photo').attr('src',element.attr('src').replace('_thumb',''));
                             $j('#lead_photo_big').unblock();
                          }
                          else
                          {
                              $j('.lead_photo').attr('src',BASE_URL+PROPERTY_PHOTOS_FOLDER+'house.jpg');
                              $j('#lead_photo_big').unblock();
                          }
                      }
                    }
                );
            }
        )
    }
}
 
 function addNeighborPaginationBinding()
 {
    if (!adminMode)
    {
        $j('div.property_neighbor_link').click
        (
            function () 
            {
                var currentNeighborPage = parseInt($j('#fldPageNrNeighbors').val());
                var element = $j('#lead_neighbor');
                var parameters = {};
                
                if (this.id.indexOf("next")>=0)
                    currentNeighborPage++;
                else
                    currentNeighborPage--;
                    
                parameters['pageNrNeighbors'] = currentNeighborPage; 
                $j('#fldPageNrNeighbors').val(currentNeighborPage);
                
                blockElement('#lead_neighbor');
                
                element.load
                (
                    BASE_URL+"leads/getNeighbor",
                    parameters,
                    function()
                    {
                        addAJAXLinks('#lead_neighbor div.ajax_link');
                        loadLeadStatusHistory();
                        addTooltip('#tooltip_neighbors');
                        addNeighborPaginationBinding();
                        element.unblock();
                    }
                )
            }
         );
     }
 }
 
 function loadLeadStatusHistory()
 {
    if (!adminMode)
    {
        var parameters = {};
        parameters['propertyID'] = $j('#fldPropertyID').val(); 
        
        blockElement('#lead_status_history');
        $j('#lead_status_history').load
        (
            BASE_URL+'leads/getLeadStatusHistory',
            parameters,
            function()
            {
                addDeleteLeadStatusBehavior();
                $j('#lead_status_history').unblock();
            }
        );
    }
 }
 
 function addCustomStatusBehavior()
 {
    if (!adminMode)
    {
        $j('[id*=_button_custom_toggle_]').click
        (
            function()
            {
                var statusType = this.id.substring(0,this.id.indexOf('_'));
                $j('#'+statusType+'_custom_container').toggleClass('hidden');
                
                $j('#'+statusType+'_button_custom_toggle_show').toggleClass('hidden');
            }
        );
        
        $j('[id*=_button_custom_save]').click
        (
            function()
            {
                var statusType = this.id.substring(0,this.id.indexOf('_'));
               
                var parameters = {};
                
                parameters['statusType'] = $j.trim(statusType);
                parameters['statusName'] = $j.trim($j('#'+statusType+'_custom_name').val());
                
                parameters['propertyID'] = $j('#fldPropertyID').val();
                
                if (parameters['statusName']!="")
                { 
                    jQuery.ajax({
                    	type: "POST",
                    	url: "/leads/ajax_check_equel_custom_status",
                    	dataType: 'json',
                        data:{statusType:$j.trim(statusType),statusName:$j.trim($j('#'+statusType+'_custom_name').val())},
                    	cache:    false,
                    	success: function(data){	
                            if(data.success == 'success')
                            {   
                                blockElement('#lead_statuses');
                                
                                $j('#lead_statuses').load
                                (
                                    BASE_URL+'leads/addCustomStatus',
                                    parameters,
                                    function()
                                    {
                                        addLeadStatusBehavior();
                                        $j('#lead_statuses').unblock();
                                    }
                                )
                            
                                $j('#'+statusType+'_custom_container').toggleClass('hidden');
                                $j('#'+statusType+'_button_custom_toggle_show').toggleClass('hidden');
                            }
                            else
                                jAlert('That name already exists. Try a new one!', 'Error');
                        }
                    });
                
                }
                
                
            }
        );
    }
 }
 
 function addLeadStatusBehavior()
 {
    if (!adminMode)
    {
         var blockElementSelector = '#lead_status_history';
         addDeleteCustomStatusBehavior();
         addCustomStatusBehavior();
         addAJAXLinks('#lead_statuses .ajax_link');
         adjustLeadStatusContainers();
         $j('#lead_statuses [class*=ajax_link]').each
         (
            function()
            {
                var statusElement = $j(this);
                statusElement.click
                (
                    function()
                    {
                        var parameters = {};
                        var element = $j(this).next();
                        var statusID;
                        if (!element.hasClass('removed'))
                            element = $j(this).next().next();
                    
                        statusID = parseInt(element.text());
                        
                        if (!statusID)
                        {
                          statusID = 15;
                        }
                        
                        add_touch_for_contact();
                        
                        parameters['statusID'] = statusID;
                        parameters['note'] = $j('#leads_note_text').val();
                        parameters['statusDate'] = $j('#status_date').val();
                        parameters['propertyID'] = $j('#fldPropertyID').val();
                        
                        
                        if ($j('#fldPropertyInfoType').val()=="neighbors")
                            parameters['neighborID'] = $j('#fldNeighborID').val();
                        
                        if(statusID == 16)
                        {
                            createSetAppointmentPopup('new', 0, false);
                        }
                        else
                        {
                            blockElement(blockElementSelector);

                            $j('#lead_status_history').load
                            (
                                BASE_URL+'leads/setLeadStatusHistory',
                                parameters,
                                function()
                                {
                                    update_status = $j('#refresh_status').val();
                                    if(update_status == 1)
                                    {
                                        $j('.property_status').html('Follow Up');
                                        $j('#grid_status_' + $j('#fldPropertyID').val()).html('Follow Up');
                                    }
                                    
                                    addLeadHistoryBindings(blockElementSelector);                                    
                                    
                                    loadGoalsContent(false);
                                    setActionTakenStatusForCurrentGridRow(true);
                                    actionDatesCacheCollection = {};
                                    if (statusElement.hasClass('followup_action') && statusID != 16)
                                        createFollowUpPopup(statusID);
                                }
                            );
                        }
                    }
                );
            }
         );
         
         var btnSaveLeadNote = $j('#btnSaveLeadNote');
//         $j('#btnSaveLeadNote').click
//         (
//            function()
//            {
//                saveLeadNote();
//            }
//         );
         btnSaveLeadNote.hover
         (
            function()
            {
                btnSaveLeadNote.css('background-image',btnSaveLeadNote.css('background-image').replace('red','gray'));
            },
            function()
            {
                btnSaveLeadNote.css('background-image',btnSaveLeadNote.css('background-image').replace('gray','red'));
            }
         );
         $j('#leads_note_text').blur(saveLeadNote);
    }
 }

function add_touch_for_contact()
{
    current_id = jQuery('#fldPropertyID').val();

    jQuery.ajax({
            type: "POST",
            url: "/leads/ajax_add_touch_for_contact",
            dataType: 'json',
            async: false,
            data: {current_id:current_id},
            cache:    false,
            success: function(data){
                if(data.reload == 1)
                {
                    $j('#fldPropertyID').val(data.new_id); 
                    refreshLeadGrid(false,null,null,false);
                }
            }
        });
}

function set_next_action(statusID)
{
    var parameters = {};
    statusID = parseInt(statusID);
    
    if (statusID)
    {     
        parameters['statusID'] = statusID;
        parameters['propertyID'] = $j('#fldPropertyID').val();
        
        $j('#lead_status_history').load
        (
            BASE_URL+'leads/setLeadStatusHistory',
            parameters,
            function()
            {
                var blockElementSelector = '#lead_status_history';
                addLeadHistoryBindings(blockElementSelector);
                loadGoalsContent(false);
                setActionTakenStatusForCurrentGridRow(true);
                actionDatesCacheCollection = {};
                if (statusID == 15)
                    createFollowUpPopup(statusID);
            }
        );
    }
                    
}

function set_next_action_property(statusID, appt_id)
{
    var parameters = {};
    statusID = parseInt(statusID);
    appt_id = parseInt(appt_id);
    
    jQuery.ajax({
		type: "POST",
		url: "/leads/ajax_set_next_action_property",
		dataType: 'json',
		data:     {status_id:statusID,appt_id:appt_id},
		cache:    false,
		success: function(data){
           if(data.update_status == 1)
            $j('.property_status').html('Follow Up');
            
            if(statusID == 15)
            {
                createFollowUpPopup(statusID);
                
            }
		}
	});
                    
}


function set_follow_up(appt_id, appt_id_next)
{
    createFollowUpPopup(15, 'exp', appt_id, appt_id_next);
}

function load_set_appt_history_block(set_appt_id)
{
    var parameters = {};
    var blockElementSelector = '#lead_status_history';
    blockElement(blockElementSelector);
    parameters['set_appt_id'] = set_appt_id;
    
    $j('#lead_status_history').load
    (
        BASE_URL+'leads/setApptLeadStatusHistory',
        parameters,
        function()
        {
            addLeadHistoryBindings(blockElementSelector);
            loadGoalsContent(false);
            setActionTakenStatusForCurrentGridRow(true);
            actionDatesCacheCollection = {};
            
        }
    );
}
 
 
 function saveLeadNote()
 {
    var noteText = $j.trim($j('#leads_note_text').val());
    if (noteText=="" || noteText==$j.trim(DEFAULT_NOTE_TEXT))
    {
        jAlert('The note is not set!');
        return false;
    }
    else
    {
        var parameters = {};
        parameters['note'] = noteText;
        if ($j('#fldPropertyInfoType').val()=="neighbors")
            parameters['neighborID'] = $j('#fldNeighborID').val();
        
        $j.post
        (
            BASE_URL+'leads/addLeadNote',
            parameters,
            function(data)
            {
                if (data=="0")
                    jAlert('An error occured');
                else
                {
                    var blockElementSelector = '#lead_status_history';
                    blockElement(blockElementSelector);
                    $j('#lead_status_history').load
                    (
                        BASE_URL+'leads/getLeadStatusHistoryContent',
                        null,
                        function()
                        {
                            addLeadHistoryBindings(blockElementSelector);
                        }
                    );
                }
            }
        );
    }
 }
 
 function addLeadHistoryBindings(blockElementSelector)
 {
    addDeleteLeadStatusBehavior();
    $j('#leads_note_text').val(DEFAULT_NOTE_TEXT).removeClass('field-focus').css("color","grey");
    bindEmptyNoteTextArea();
    //setActionDatesForCurrentMonths();
    $j(blockElementSelector).unblock();
 }
 
 
 function addDeleteCustomStatusBehavior()
 {
    if (!adminMode)
    {
        var blockElementSelector = '#lead_statuses';
        $j('#lead_statuses img[class*=delete_custom_button]').click
        (
            function()
            {
                var statusID = $j(this).next().text();
                jConfirm
                (
                    'Are you sure you want to delete this custom option? Doing so will delete this custom option associated with all contacts.', 
                    'Delete Custom Option', 
                    function(result)
                    {
                        if (result)
                        {
                            var parameters = {};
                            parameters['statusID'] = statusID;
                            parameters['propertyID'] = $j('#fldPropertyID').val();
                            
                            blockElement(blockElementSelector);
                            $j('#lead_statuses').load
                            (
                                BASE_URL+'leads/deleteCustomStatus',
                                parameters,
                                function()
                                {
                                    addLeadStatusBehavior();
                                    loadLeadStatusHistory();
                                    unblockElement(blockElementSelector);
                                }
                            );
                        }
                    }
                );
            }
        );
    }
 }
 
 function addDeleteLeadStatusBehavior()
 {
    if (!adminMode)
    {
        var blockElementSelector = '#lead_status_history';
        $j('#lead_status_history img[class*=delete_button]').click
        (
            function()
            {
                var parameters = {};
                var type = $j(this).next().next().text();
                var method;
                
                if (type=='status')
                {
                    parameters['leadStatusID'] = $j(this).next().text();
                    parameters['propertyID'] = $j('#fldPropertyID').val();                    
                    method = 'setLeadStatusHistory';   
                } 
                else
                {
                    parameters['noteID'] = $j(this).next().text();
                    method = 'deleteLeadNote';
                }
                
                blockElement(blockElementSelector);
                
                $j('#lead_status_history').load
                (
                    BASE_URL+'leads/'+method,
                    parameters,
                    function()
                    {
                        addDeleteLeadStatusBehavior();
                        setActionTakenStatusForCurrentGridRow(false);
                        actionDatesCacheCollection = {};
                        $j(blockElementSelector).unblock();
                        //setActionDatesForCurrentMonths();
                        loadGoalsContent(false);
                    }
                 );
            }
        );
    }
 }

 
function addLeadGridRowEvents()
 {
    ///	<summary>
    ///		Adds hover state for leads list rows and binds click event which will refres
    ///     the "Owner info" panel and set the current row as selected
    ///	</summary>
    ///	<returns type="void" />
 
    $j('#lead_grid tbody tr').each
    (
        function()
        {
            $j(this).hover
            (
                function() 
                {
                    $j(this).toggleClass('leads_row_hover');
                    set_star_in_grid(this);
                },
                function()
                {
                    if (!$j(this).hasClass('leads_row_selected'))
                        $j(this).toggleClass('leads_row_hover');
                        
                    set_star_in_grid(this);
                }
            )
            .click
            (
                function(event_obj)
                {
                    /**/
                    
                    if(!event_obj.ctrlKey && !event_obj.shiftKey)
                    {
                        cur_id = this.id.replace("row","");
                        $j('#fldPropertyID').val(cur_id); 
                        jQuery('.am').attr('checked', false);   
                        jQuery('#row' + cur_id).find('.am').attr('checked', true);
                       
                        if (!adminMode)
                        {
                            blockElement('#lead_grid');
                            refreshOwnerInfo(null,false);
                            unblockElement('#lead_grid');
                        }
                        
                    }
                }
            );
        }
    );
}

function set_star_in_grid(obj, define)
{
    img = jQuery(obj).find('#grid_star');
    src = img.attr('src');

    img_del = jQuery(obj).find('.button_delete_lead img');
    src_del = img_del.attr('src');

    if(!jQuery(obj).hasClass('trSelected') || define == 'set')
    {
        if(src == '/images/contact_info/star-g.jpg')
            img.attr('src', '/images/contact_info/star-r-sel.jpg');
        else if(src == '/images/contact_info/star-w.jpg')
            img.attr('src', '/images/contact_info/star-r.jpg');
        else if(src == '/images/contact_info/star-r.jpg')
            img.attr('src', '/images/contact_info/star-w.jpg');
        else if(src == '/images/contact_info/star-r-sel.jpg')
            img.attr('src', '/images/contact_info/star-g.jpg');
       
        if(src_del == '/images/delete.gif')
            img_del.attr('src', '/images/red-x.png');
        else
            img_del.attr('src', '/images/delete.gif');
    }
}

function setSelectedLeadGridRow(searchPage,selectFirstRow,setCurrentRow)
{
    jQuery('.hDivBox').find('#check_main').parents('th').unbind();
    //jQuery('.hDivBox').find('#main_header_star').parents('th').unbind();
    
    ///	<summary>
    ///		Sets the current poperty row to selected. 
    ///	</summary>
    ///	<param name="searchPage" type="Boolean">
    ///		If searchPage is true and the current property is not on the current page, the page containing the
    ///     property will be retrieved
    ///	</param>
    ///	<returns type="void" />
    if (selectFirstRow)
    {
        $j('#lead_grid tbody tr:not([id^=row-1])').removeClass("trSelected").removeClass("leads_row_hover"); 
        
        var firstRow = $j('#lead_grid tbody tr:not([id^=row-1]):first');

        if (firstRow.length>0)
        {
            
            firstRow.removeClass("leads_row_hover").addClass("trSelected");
            $j('#fldPropertyID').val(firstRow[0].id.replace("row",""));
            
            jQuery('.am').attr('checked', false);
            firstRow.find('.am').attr('checked', true);
        }
    }
    else
    {
        var propertyID = parseInt($j('#fldPropertyID').val());
        var found = false;
        var $currentRow = $j('#lead_grid tbody tr.trSelected:first');
        var $rowToSelect = null;

        if(setCurrentRow)
            setCurrentRow = true;
        else
            setCurrentRow = false;
        
        $j('#lead_grid tbody tr:not([id^=row-1])').removeClass("trSelected").removeClass("leads_row_hover");
        
        if ($currentRow.length>0 && !setCurrentRow)
        {

            if (adjacentRowDirection==-1)
                $rowToSelect = $currentRow.prevAll(':not([id^=row-1]):first');
            else if (adjacentRowDirection == 1)
                $rowToSelect = $currentRow.nextAll(':not([id^=row-1]):first');
                
            set_star_in_grid(jQuery($rowToSelect), 'set');
            
            if (adjacentRowDirection==-1 && $rowToSelect != null)
                prev_selected = $rowToSelect.nextAll(':not([id^=row-1]):first');
            else if($rowToSelect != null)
                prev_selected = $rowToSelect.prevAll(':not([id^=row-1]):first');
            
            if($rowToSelect != null)
            {
                set_star_in_grid(prev_selected, 'set');
                jQuery(prev_selected).find('.am').attr('checked', false);
                jQuery($rowToSelect).find('.am').attr('checked', true);
            }
                
            
        }
        else
            $rowToSelect = $j('#row'+propertyID+':first');
        
        if ($rowToSelect!=null && $rowToSelect.length==1)
        {
            $rowToSelect.addClass("trSelected");
            found = true;
        }
        else if(setCurrentRow && $rowToSelect.length==1)
        {            
            $rowToSelect.addClass("trSelected");
            found = true;
        }
        
//        $j('#lead_grid tbody tr:not([id^=row-1])').each
//        (
//            function()
//            {
//                var currentPropertyID = parseInt(this.id.replace("row",""));
//                
//                if (currentPropertyID==propertyID)
//                {
//                    $j(this).removeClass("leads_row_hover").addClass("trSelected");
//                    found = true;
//                }
//                else
//                    $j(this).removeClass("trSelected").removeClass("leads_row_hover");
//            }
//        );
        
        if (searchPage && !found)
        {
            refreshLeadGrid(false,null);
            setSelectedLeadGridRow(false,false);
        }
    }
}

function bindEmptyNoteTextArea()
{
    if (!adminMode)
    {
        $j('#leads_note_text')
        .click
        (
            function()
            {
                var element = $j(this);
                
                if(jQuery.trim(element.val()) == "Click here to add a note")
                    element.val('').addClass('field-focus').val(' ');
               
            }
        )
        .keypress
        (
            function()
            {
                $element = $j(this);

                if ($element.val().length > MAX_NOTE_LENGTH)
                    $element.val($element.val().substring(0, MAX_NOTE_LENGTH));
            }
        );
    }
}

function setActionTakenStatusForCurrentGridRow(statusAdded)
{
    ///<param name="statusAdded" type="Boolean">
    ///True if add lead status was clicked, false if delete was clicked
    ///</param>
    if (!adminMode)
    {
        var propertyID = parseInt($j('#fldPropertyID').val());
        
        $j('#lead_grid tbody tr').each
        (
            function()
            {
                var element = $j(this);
                var rowPropertyID = parseInt(this.id.replace("row",""));
                
                if (rowPropertyID==propertyID)
                {
                    if (statusAdded)
                    {
                        if (!element.hasClass('lead_action_taken'))
                            element.addClass('lead_action_taken');
                    }
                    else
                    {
                        // if lead_status_history contains only the <div class="clear_float"></div> meaning that
                        // there is no status
                        var children = $j('#lead_status_history').children();
                        if (children.length==0 || (children.length==1 && $j(children[0]).attr('class')=='clear_float') )
                        {
                            element.removeClass('lead_action_taken');
                            element.children().children().children().removeClass('lead_action_taken');
                        }
                    }
                    return false;  
                }
                
            }
        );
    }
}

function initPropertyInfoTypeBinding()
{
    if (!adminMode)
    {
        var propertyInfoType = $j('#fldPropertyInfoType').val();
        
        switch (propertyInfoType)
        {
            case "history":
            {
                initHistoryAndNeighborsBinding(null);
                break;
            }
            case "info":
            {
                initInfoBinding();
                break;
            }
            case "photos":
            {
                break;
            }
            case "neighbors":
            {
                initHistoryAndNeighborsBinding(null);
                break;
            }
            case "map":
            {
                break;
            }
        }
    }
}

function addDragAndDropBehavior()
{
//    $j('#sortable_list_top').sortable({handle:$j('.sortable_handle'),items:'span'});
//    $j('#sortable_list_top').disableSelection();
    
    $j('#sortable_list').sortable({handle:$j('.sortable_handle'),stop:callbackStopSorting});
//    $j('#sortable_list').disableSelection();
}

function callbackStopSorting()
{
    var containerNames = ['FirstContainer','SecondContainer','ThirdContainer'];
    var containers = {};
    var i = 0;
    
    $j('#sortable_list').children('li').each
    (
        function()
        {
            containers[containerNames[i++]] = this.id;
        }
    );
    
    $j.post
    (
        BASE_URL+'leads/updateContainerOrder',
        containers,
        null
    );
}

function initGoogleMapBinding()
{
    if (!adminMode)
    {
        var latitude = $j('#fldLatitude').val();
        var longitude = $j('#fldLongitude').val();
        initializeMap('#property_map',latitude,longitude);
    
//        var image = new Image();
//        $j(image).attr('src',$j('#map_image').attr('src'));
//       
//        if (!$j.browser.msie)
//        {
//            blockElement('#map_image');
//            setTimeout('unblockElement("#map_image")',30*1000);
//            $j('#map_image').load
//            ( 
//                function()
//                {
//                    unblockElement('#map_image');
//                }
//            );
//        }
    }
}

function getSavedSearchList()
{
    $j('#saved_search_list_container').load
    (
        BASE_URL+'leads/getSavedSearchList',
        null,
        function()
        {
            addSavedSearchBindings();
            unblockElement('#saved_search_list_container');
        }
    );
}

function saveLeadGridState()
{
    var columnOrder = '',element;
    var gridState = {};
    var columnName, columnWidth, columnStatus;
    var gridHeight;

    $j('#flexigrid table th').each
    (
        function()
        {
            element = $j(this);
            if (element.attr('abbr')!="")
                columnName = element.attr('abbr');
            else if (element.attr('rel')!="")
                columnName = element.attr('rel');
            else
                columnName = $j.trim(element.children(':first').text());
                
            columnWidth = element.children(':first').width();
            columnStatus = element.css('display')=='none'?"1":"0";
            
            gridState['grid_column_'+columnName] = columnWidth+'|'+columnStatus;
            
            
            if (element.hasClass('sorted'))
            {
                gridState['grid_sort'] = columnName;

                if(element.find('.sasc').length > 0)
                    gridState['grid_sort_order'] = 'asc';
                else
                    gridState['grid_sort_order'] = 'desc';
            }
                
        }
    );
    
    gridState['grid_height'] = $j('#flexigrid div.bDiv').height();
    
    $j.post
    (
        BASE_URL+'leads/saveLeadGridState',
        gridState,
        null
    );

    $j('.followup_header').removeAttr('style');
}
function addLeadContentBottomWhiteImages()
{
    //adding padding to the leads_panel_content
    if (!$j('#leads_panel_content').hasClass('leads_panel_container'))
        $j('#leads_panel_content').addClass('leads_panel_container');
    $j('#leads_panel_bottom_left').removeClass('leads_panel_bottom_left_gray_corner');
    $j('#leads_panel_bottom_line').removeClass('leads_panel_bottom_line_grey');
    $j('#leads_panel_bottom_right').removeClass('leads_panel_bottom_right_gray_corner');

    $j('#leads_panel_bottom_left').addClass('leads_panel_bottom_left_white_corner');
    $j('#leads_panel_bottom_line').addClass('leads_panel_bottom_line');
    $j('#leads_panel_bottom_right').addClass('leads_panel_bottom_right_white_corner');
}
function addLeadContentBottomGreyImages()
{
    $j('#leads_panel_content').removeClass('leads_panel_container');
    
    $j('#leads_panel_bottom_left').removeClass('leads_panel_bottom_left_white_corner');
    $j('#leads_panel_bottom_line').removeClass('leads_panel_bottom_line');
    $j('#leads_panel_bottom_right').removeClass('leads_panel_bottom_right_white_corner');

    $j('#leads_panel_bottom_left').addClass('leads_panel_bottom_left_gray_corner');
    $j('#leads_panel_bottom_line').addClass('leads_panel_bottom_line_grey');
    $j('#leads_panel_bottom_right').addClass('leads_panel_bottom_right_gray_corner');
}

function selectFirstLeadGridRow()
{
    setSelectedLeadGridRow(false,true);
    refreshOwnerInfo(null,true);
}

function select_all()
{
    check = jQuery('#check_main:checked').length;
    if(check)
		jQuery('.am').attr('checked', true);
	else 
		jQuery('.am').attr('checked', false);
}

function edit_user_username()
{
    jQuery.ajax({
    	type: "POST",
    	url: "/leads/ajax_edit_user_username",
    	dataType: 'json',
        data: jQuery('#frm_change_username').serialize(),
    	cache:    false,
    	success: function(data){	
            if(data.success == 'error')
            {
                jQuery(".broker-table tr").each(
                    
                function()
                    {
                        jQuery(this).find('.error').html('');
                    }
                );
                
                jQuery.each(data.error, function(index, value) { 
                    parent_obj = jQuery('input[name=' + index + ']').parents('tr');
                    parent_obj.find('.error').html(value);
                });
            }
            else
            {
                jAlert('New username and password were saved.')
                popup.disablePopup();
            }
        }
    });
}

function check_fsbo_cities(first_login)
{
    if(first_login == undefined)
        first_login = 0;
        
    jQuery.ajax({
    	type: "POST",
    	url: "/leads/ajax_check_fsbo_cities",
    	dataType: 'json',
    	cache:    false,
    	success: function(data){	
            if(data.success == 'success')
                show_change_fsbo_popup(first_login);
            else
                check_broker_user();
        }
    });
}

function fsbo_add_to_contact(propertyID)
{
    message = 'Are you sure you want to move this lead to your contacts?';
    
    jConfirm(message, 'FSBO', 
        function(result)
        {
            if(result)
            {
                jQuery.ajax({
                	type: "POST",
                	url: "/leads/ajax_fsbo_add_to_contact",
                	dataType: 'json',
                	cache:    false,
                    data: {propertyID:propertyID},
                	success: function(data){	
                        if(data.success == 'success')
                        { 
                            jQuery('.add_to_contact').html('');
                            refresh_search_container();
                            
                            if(data.expired == 1)
                                refreshOwnerInfo(null,false);
                        }
                    }
                });
            }
        }
        
    );

}