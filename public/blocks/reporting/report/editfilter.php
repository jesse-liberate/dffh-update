<?php
require_once('../../../config.php');
require_once('lib.php');

if(!isset($userid))
	$userid = $USER->id;
	
require_login(0, false);
$PAGE->set_context(context_system::instance());

if (isset($_POST['cancel'])) {
	redirect(new moodle_url('/blocks/reporting/report/filter.php'));
} else if (!empty($_POST['save'])){
	$result_applied = isset($_POST['result_applied']) ? $_POST['result_applied'] : array(); 		
	//var_dump($result_applied);
		//var_dump( $result_applied ); 
	/* delete all records */
	// Add new records into database
	$num_records = $DB->count_records('reporting_filter');
	if ($num_records > 0 ) {
		$DB->delete_records('reporting_filter');
	}
	if (count($result_applied) > 0) {
		foreach ($result_applied as $filter_user_info_field_id) {
			/* insert new record */
			$new_record = new stdClass();
			$new_record->user_info_field_id= $filter_user_info_field_id;
			$new_record->status = 'Y';

			if (in_array($filter_user_info_field_id, $result_applied)) {
				$new_record->status = 'Y';
			} else {
				$new_record->status = 'N';
			}
			//var_dump($new_record);
			$lastinsertid = $DB->insert_record('reporting_filter', $new_record, false);
		}
	} 
	$general_status_s = "";
	if(isset($_POST['general_status'])) $general_status_s =$_POST['general_status'];
	$num_general_records = $DB->count_records('general_filter');
	if ($num_general_records > 0 ) {
		$sql = 'UPDATE mdl_general_filter SET status = "N"';
		$DB->execute($sql);
	}
	//new record for general filter
	if(($general_status_s!="") && ($general_status_s>0)) {
		foreach ($general_status_s as $general_field_id) {
			/* insert new record */
			$general_record = new stdClass();
			$general_record->id = $general_field_id;
			$general_record->status = 'Y';
			if (in_array($general_field_id, $general_status_s)) {
				$general_record->status = 'Y';
			} else {
				$general_record->status = 'N';
			}
			//var_dump($general_record);
			$lastinsertedid = $DB->update_record('general_filter', $general_record, false);
			//var_dump($lastinsertedid);
		}
	}
	
	redirect(new moodle_url('/blocks/reporting/report/filter.php'));
}

$PAGE->set_pagelayout('reports');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading(get_string('filter_reports', 'block_reporting'));
$PAGE->set_url('/blocks/reporting/report/filter.php');
$PAGE->navbar->add("Reporting filter", new moodle_url('/blocks/reporting/report/filter.php'));
echo $OUTPUT->header();

///////////////////////////////////////////////////||////////||////////get data from filter tables
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
$content .= '<form action="editfilter.php" method="post" id="filterform" name="filter">';
$content .= "<input type='hidden' name='report' value='1' />";
$content .= "<table id='report' class='tablesorter'>";

$content .= '<thead>';
$content .= '<tr class="filtertableheader">';
$content .= '<th>' . get_string('filter_name', 'block_reporting') . '</th>';
$content .= '<th>' . get_string('filter_applied', 'block_reporting') . '</th>';
$content .= '</tr>';
$content .= '</thead>';

$content.="<tr><td colspan='2'>".get_string('userdefinedfields','block_reporting')."</td></tr>";
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

	if(array_key_exists($exist_id, $filter_array)){
		$content.="<td><input type='checkbox' name='result_applied[]'  value='".$user_info_field_record->id."' checked='checked'/></td>";
	}else{
		$content.="<td><input type='checkbox' name='result_applied[]'  value='".$user_info_field_record->id."' /></td>";
	}
	$content.="</tr>";
	
}
///////////////////////////////////////////////////||////////||/////////////////////general_filter

$content.="<tr class='general_filterinfor'>";
$content.="<tr><td colspan='2'>".get_string('generalfields','block_reporting')."</td></tr>";
$general_filter_sql = "SELECT id,filtername,status
							FROM   {general_filter}";
$general_filter_records = $DB->get_records_sql($general_filter_sql);
//var_dump($general_filter_records);
$arr_labels = get_list_user_profile_labels();
foreach ($general_filter_records as $general_filter_record){
	$content.="<tr><td>".$arr_labels[$general_filter_record->filtername]."</td>";
	$filter_db_status = $general_filter_record->status;
	//var_dump($general_filter_record);
	if($filter_db_status == 'Y'){
		$content.="<td><input type='checkbox' name='general_status[]' value='".$general_filter_record->id."'  checked='checked' /></td>";
	}else{
		$content.="<td><input type='checkbox' name='general_status[]' value='".$general_filter_record->id."' /></td>";
	}
	//var_dump($general_filter_record->id);
	$content.="</tr>";
}

$content.="</tbody>";
$content.="</table>";
$content.='<input id="save" class="btn btn-primary" type="submit" value="' . get_string('savechanges') . '" name="save"/>';
$content.='&nbsp;<button class="btn btn-secondary" name="cancel"/>' . get_string('cancel') . '</button>';
$content.="</form>";
echo $content;

echo $OUTPUT->footer();
