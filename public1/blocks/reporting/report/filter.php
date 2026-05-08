<?php
require_once('../../../config.php');
require_once('lib.php');

	$PAGE->requires->js('/lib/mindatlas/jquery/jquery.min.js', true);
	$PAGE->requires->js('/lib/mindatlas/jquery/ui/jquery-ui.min.js', true); 
	$PAGE->requires->css('/lib/mindatlas/jquery/ui/jquery-ui.min.css');
	$PAGE->requires->js('/blocks/reporting/report/js/chosen.js', true);
	$PAGE->requires->js('/blocks/reporting/report/js/jquery.tablesorter.js', true);
	$PAGE->requires->js('/blocks/reporting/report/js/jstree/dist/jstree.min.js', true);
	$PAGE->requires->js('/blocks/reporting/report/resource/chosen.jquery.js', true);
	$PAGE->requires->css('/blocks/reporting/report/css/chosen.css');
	$PAGE->requires->css('/blocks/reporting/report/js/jstree/dist/themes/default/style.min.css');

if(!isset($userid)) $userid = $USER->id;
require_login(0, false);
$context_system = context_system::instance();
$PAGE->set_context($context_system);

$PAGE->set_pagelayout('reports');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading(get_string('filter_reports', 'block_reporting'));
$PAGE->set_url('/blocks/reporting/report/filter.php');
$PAGE->navbar->add("Reporting filter", new moodle_url('/blocks/reporting/report/filter.php'));
//REMOVE ALL DELETED FIELDS TO AVOID ISSUE
remove_deleted_fields();

echo $OUTPUT->header();

///////////////////////////////////////////////////////////////////get data from filter tables
$filter_records = $DB->get_records("reporting_filter");
$general_fitler_records = $DB->get_records("general_filter");
$filter_array = array();
//var_dump($filter_records);
if ($filter_records != false) {
	foreach ($filter_records as $filter_record) {
		$filter_array[$filter_record->user_info_field_id]= $filter_record;		
	}	
}


$general_filter_array = array();
if ($general_fitler_records != false) {
	foreach ($general_fitler_records as $general_fitler_record) {
		$general_filter_array[$general_fitler_record->id] = $general_fitler_record;
	}		
}

$content = '';
$content .= '<p>' . get_string('fill_out_fields_you_wish', 'block_reporting') . '</p>';
$content .= "<input type='hidden' name='report' value='1' />";
$content .= "<table id='report' class='tablesorter'>";

$content .= '<thead>';
$content .= '<tr class="filtertableheader">';
$content .= '<th>' . get_string('filter_name', 'block_reporting') . '</th>';
$content .= '<th>' . get_string('filter_applied', 'block_reporting') . '</th>';
$content .= '</tr>';
$content .= '</thead>';

$content .= '<tbody>';
$content.="<tr><td colspan='2'>".get_string('userdefinedfields','block_reporting')."</td></tr>";
$content.="<tr class='userprofilefilterinfo'>";

//get the user profile details
$user_info_fields_sql = "SELECT id,
								name
							FROM   {user_info_field} 
							WHERE  {user_info_field}.datatype = 'text' 
							OR     {user_info_field}.datatype = 'menu' 
							OR     {user_info_field}.datatype = 'datetime' 
							OR 	{user_info_field}.datatype = 'checkbox' ORDER BY sortorder ASC";
$user_info_field_records = $DB->get_records_sql($user_info_fields_sql);
//var_dump($user_info_field_records);
foreach($user_info_field_records as $user_info_field_record){
	$content.="<tr><td>".$user_info_field_record->name."</td>";
	$exist_id = $user_info_field_record->id;

	if(array_key_exists($exist_id, $filter_array) ){
		$content.="<td><input type='checkbox' name='result_applied[]' value=".$user_info_field_record->id." checked='checked' disabled='disabled'/></td>";
	}else{
		$content.="<td><input type='checkbox' name='result_applied[]'value=".$user_info_field_record->id." disabled='disabled'/></td>";
	}
	$content.="</tr>";
}
////////////////////////////////////////////////////////////////////////////////general_filter
// Auto add some default fields into the general_filter
$default_fields_sql = "SELECT `COLUMN_NAME` 
					FROM `INFORMATION_SCHEMA`.`COLUMNS` 
					WHERE `TABLE_NAME`='mdl_user'
					AND `TABLE_SCHEMA`='$CFG->dbname';";
$default_fields = $DB->get_records_sql($default_fields_sql);
// echo '<pre>'.print_r($default_fields,true).'</pre>'; die();
$options = array('username','email','country','city','lastaccess');
foreach ($default_fields as $fieldobj) {            	
	if ( in_array($fieldobj->column_name, $options) && !$DB->record_exists('general_filter',array('filtername'=>$fieldobj->column_name)) ) {
		$general_filter_record = new stdClass();
		$general_filter_record->filtername = $fieldobj->column_name;
		$general_filter_record->status = 'N';
		$DB->insert_record('general_filter',$general_filter_record);
	}
}

$content.="<tr class='general_filterinfor'>";
$content.="<tr><td colspan='2'>".get_string('generalfields','block_reporting')." (only site administrators will see these details)</td></tr>";
$general_filter_sql = "SELECT id,filtername,status
							FROM   {general_filter}";
$general_filter_records = $DB->get_records_sql($general_filter_sql);
//var_dump($general_filter_records);
$arr_labels = get_list_user_profile_labels();
foreach ($general_filter_records as $general_filter_record){
	$content.="<tr><td>".$arr_labels[$general_filter_record->filtername]."</td>";
	$filter_db_status = $general_filter_record->status;
	if($filter_db_status == 'Y'){
		$content.="<td><input type='checkbox' checked='checked'  name='general_status[]' value='Y' disabled='disabled' /></td>";
	}else{
		$content.="<td><input type='checkbox' name='general_status[]' value='N' disabled='disabled'/></td>";
	}
	$content.="</tr>";
}

$content.="</tbody>";
$content.="</table>";
$content.='<a href="editfilter.php" class="btn btn-primary">' . get_string('edit') . '</a>';

echo $content;

echo $OUTPUT->footer();
