<?php

/**
 * @package   tool_cohortenrolmentrules
 * @copyright  2016  Charlie Tran
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Include required libary files
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_once($CFG->dirroot . '/cohort/lib.php');       // for adding users to a cohort
require_once($CFG->dirroot . '/cohort/locallib.php');  // for adding users to a cohort

// Include what we need
// require_once($CFG->dirroot.'/vendor/autoload.php');

$PAGE->requires->js('/admin/tool/cohortenrolmentrules/js/jquery-3.1.0.min.js', true);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin'); 
$PAGE->set_title($SITE->fullname.': Cohort Enrolment Rules');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/admin/tool/cohortenrolmentrules/cohort_setting_edit.php');

//Setup Breadcrumbs
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Site administration');
$PAGE->navbar->add('Users');
$PAGE->navbar->add('Accounts');
$PAGE->navbar->add('Cohort Enrolment Rules', new moodle_url('/admin/tool/cohortenrolmentrules/index.php'));
$PAGE->navbar->add('Edit', new moodle_url('/admin/tool/cohortenrolmentrules/cohort_setting_edit.php'));

$contextid = optional_param('contextid', 0, PARAM_INT);

if ($CFG->forcelogin) {
    require_login();
}

if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
} else {
    $context = context_system::instance();
}

/* --------------------------------------------------------------------------------------- */

// Edit Cohort

if (isset($_GET['id'])) {

	$cohortid = $_GET['id'];
	$cohortname = $DB->get_field('cohort', 'name', array('id'=>$cohortid));

	$isSettingExisted = $DB->record_exists('cohort_setting', array('cohort_id'=>$cohortid));

	if ($isSettingExisted) {
		$current_cohort_setting = $DB->get_record('cohort_setting', array('cohort_id'=>$cohortid));
		// echo '<pre>'.print_r($current_cohort_setting, true).'</pre>';
		$current_setting_id = $current_cohort_setting->id;
		$current_setting_name = $current_cohort_setting->setting_name;
		$current_description = $current_cohort_setting->description;
		$current_rules_combination = $current_cohort_setting->rules_combination;

	} else {
		$current_setting_name = '';
		$current_description = '';
	}

	if (isset($_POST['save'])) {
		// echo '<pre>'.print_r($_POST, true).'</pre>';
		
		$setting_name = $_POST['setting_name'];
		$setting_description = $_POST['setting_description'];

		// keep only rules and operand OR
		$setting = $_POST; unset($setting['setting_name']); unset($setting['setting_description']); unset($setting['save']);

		// echo '<pre>'.print_r($setting, true).'</pre>';

		$rules_combination = implode(' ', $setting); // will be saved in DB for references later

		// echo '<pre>'.print_r($rules_combination, true).'</pre>';

		foreach ($setting as $key => $value) {
			if ($value !== 'OR') {
				$setting[$key] = $DB->get_field('cohort_setting_rules', 'conditions_sql_detail', array('id'=>$value));
			} else unset($setting[$key]);
		}

		// echo '<pre>'.print_r($setting, true).'</pre>';

		$userids = array();

		foreach ($setting as $rulesql) {
			$members = $DB->get_records_sql($rulesql);		
				
			// Add all members from all rules to an array userids
			foreach ($members as $userid => $value) {
				array_push($userids, $userid);
			}
		}	

		// Remove redundant userids. This is the list of userids to add to a cohort
		$useridlist = array_unique($userids);
		// echo '<pre>'.print_r($useridlist, true).'</pre>';

		// Clear member of a cohort first then add new members
		$DB->delete_records('cohort_members', array('cohortid'=>$cohortid));
		foreach ($useridlist as $userid) {
			cohort_add_member($cohortid, $userid);
		}

		// Add a new setting
		if (!$isSettingExisted) {								
			$cohort_setting_record = new stdClass();
		    $cohort_setting_record->cohort_id = $cohortid;
		    $cohort_setting_record->setting_name = $setting_name;
		    $cohort_setting_record->description = $setting_description;
		    $cohort_setting_record->rules_combination = $rules_combination;
		    $DB->insert_record('cohort_setting', $cohort_setting_record);

		// Edit a current setting
		} else {			
			$cohort_setting_record = new stdClass();
			$cohort_setting_record->id = $current_setting_id;
		    $cohort_setting_record->setting_name = $setting_name;
		    $cohort_setting_record->description = $setting_description;
		    $cohort_setting_record->rules_combination = $rules_combination;
		    $DB->update_record('cohort_setting', $cohort_setting_record);
		}

		header('Location:' . $CFG->wwwroot .'/admin/tool/cohortenrolmentrules/index.php'); 
	}

	if (isset($_POST['cancel'])) {
		header('Location:' . $CFG->wwwroot .'/admin/tool/cohortenrolmentrules/index.php'); 
	}

}
/* --------------------------------------------------------------------------------------- */

echo $OUTPUT->header();

/* --------------------------------------------------------------------------------------- */

$rules = $DB->get_records('cohort_setting_rules');
$rulelist = array();

foreach ($rules as $ruleid=>$ruleobject) {
	$rulelist[$ruleid] = (trim($ruleobject->description)!='') ? $ruleobject->rule_name.' - '.$ruleobject->description : $ruleobject->rule_name;
}

echo get_string('cohortsettingeditheader','tool_cohortenrolmentrules');

$table = '<table id="tbl_setting">';
$table .= '<tbody>';
$table .= '<tr>';
$table .= '<td>Cohort: </td><td>'.$cohortname.'</td>';
$table .= '</tr>';
// $table .= '<tr>';
// $table .= '<td>Setting name: </td><td><input name="setting_name" type="text" value="'.$current_setting_name.'" size=40/></td>';
// $table .= '</tr>';
// $table .= '<tr>';
// $table .= '<td>Description: </td><td><textarea name="setting_description" cols="62" rows="3">'.$current_description.'</textarea></td>';
// $table .= '</tr>';
// Display current Rules for references
$selected_ruleid = "";
if ($isSettingExisted) {
	$table .= '<tr>';
	$table .= '<td>Current rule(s): </td>';
	$table .= '<td>';
	$current_rules = explode(' OR ', $current_rules_combination);
	$i = 0;
	foreach ($current_rules as $ruleid) {
		$i++;
		if($selected_ruleid=="") $selected_ruleid = $ruleid;
	 	$rule_name = $DB->get_field('cohort_setting_rules', 'rule_name', array('id'=>$ruleid));
		$rule_description = $DB->get_field('cohort_setting_rules', 'description', array('id'=>$ruleid));
		$or = ($i<count($current_rules)) ? '; OR ' : '';
		$rule_description_concat = ($i<count($current_rules) && trim($rule_description) != '') ? ' - '.$rule_description : '';
		$table .= $rule_name.$rule_description_concat.$or.'<br>';
	}
	$table .= '</td></tr>';

	$table .= '<tr>';
	$table .= '<td>Change to rule(s): </td><td><select class="form-control" name="rule">';
} else {
	$table .= '<tr>';
	$table .= '<td>Choose rule(s): </td><td><select class="form-control" name="rule">';
}

foreach ($rulelist as $ruleid=>$rule) {
	if($selected_ruleid==$ruleid) $table .= '<option value="'.$ruleid.'" selected="selected">'.$rule.'</option>';
	else $table .= '<option value="'.$ruleid.'">'.$rule.'</option>';
}
$table .= '</select></td>';
$table .= '</tr>';
$table .= '<tr><td></td><td><div id="add" class="btn btn-secondary">+</div></td></tr>';
$table .= '</tboby>';
$table .= '</table>';


echo "<form id='editsettingform' action='' method='POST'>";
echo $table.'</br>';
echo '<div class="form-row">';
echo '<div class="col-auto">';
echo '<input class="btn btn-primary" id="save" type="submit" value="Save" name="save"/>';
echo '</div>';
echo '<div class="col-auto">';
echo '<input class="btn btn-secondary" id="cancel" type="submit" value="Cancel" name="cancel"/>';
echo '</div>';
echo '</div>';
echo "</form>";

echo $OUTPUT->footer();   

?>

<script type="text/javascript">	
	var i = 0;
  	var rulelist_js = <?=json_encode($rulelist) ?>;
  	var rulelist = '';
  	$.each(rulelist_js,function(id, rule) {
		rulelist += '<option value="' + id + '">' + rule + '</option>';
	});

  	$('#add').click(function() {    
  		i++;
	    $('#tbl_setting tr:last').prev().after('<tr><td><input class="form-control" type="text" name="rule_operand'+i+'" value="OR" size=1 readonly/></td><td><select class="form-control" name="rule'+i+'">'+rulelist+'</select></td><td><button class="btn btn-secondary" id="remove">-</button></td></tr>');
	}); 

	$('body').on('click', '#remove', function() {    
		$(this).closest ('tr').remove();
	}); 
</script>