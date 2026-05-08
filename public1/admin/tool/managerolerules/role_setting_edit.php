<?php

// Include required libary files
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('lib.php');

// Include what we need
// require_once($CFG->dirroot.'/vendor/autoload.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin'); 
$PAGE->set_title($SITE->fullname.': User role rules');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/admin/tool/managerolerules/role_setting_edit.php');

//Setup Breadcrumbs
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Site administration');
$PAGE->navbar->add('Users');
$PAGE->navbar->add('Accounts');
$PAGE->navbar->add('role Enrolment Rules', new moodle_url('/admin/tool/managerolerules/index.php'));
$PAGE->navbar->add('Edit', new moodle_url('/admin/tool/managerolerules/role_setting_edit.php'));

$contextid = optional_param('contextid', 0, PARAM_INT);

if ($CFG->forcelogin) {
    require_login();
}

if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
} else {
    $context = context_system::instance();
}

$html_lib="<script src='js/jquery-1.12.2.min.js'></script>
  <script src='js/jquery.tablesorter.js'></script>

  <link rel='stylesheet' type='text/css' href='js/style.css' />";
$html="";
/* ----------------------------------------------------------------- */
$roleid="";
// Edit role
$roleoption="";

if (isset($_GET['id'])) {

	$roleid = $_GET['id'];
	$role_record = $DB->get_record('role', array('id'=>$roleid));
	$rolename = $role_record->name." - ".$role_record->shortname;
	$roleoption = "<option value='".$roleid."'>".$rolename."</option>";

	$current_role_setting = $DB->get_record('role_setting', array('role_id'=>$roleid));
	// echo '<pre>'.print_r($current_role_setting, true).'</pre>';
	$current_setting_id = $current_role_setting->id;
	$current_rules_combination = $current_role_setting->rules_combination;
}else{
	//Get all new role options
	$rs = $DB->get_records('role',array(),'name ASC,shortname ASC');
	foreach ($rs as $row) {
		//only system context will be applied for rule. Other roles will be applied by Moodle core.
		if($DB->record_exists('role_context_levels',array('roleid'=>$row->id,'contextlevel'=>SYSTEM_CONTEXT_LEVEL))){
			if(!$DB->record_exists('role_setting',array('role_id'=>$row->id))){
				$roleoption.="<option value='".$row->id."'>".$row->name." - ".$row->shortname."</option>";
			}
		}
	}
}
//FOR SAVING
if (isset($_POST['save'])) {
	// echo '<pre>'.print_r($_POST, true).'</pre>';
	$roleid = $_POST['role'];
	$role_record = $DB->get_record('role', array('id'=>$roleid));
	$rolename = $role_record->name." - ".$role_record->shortname;

	$setting_name = $rolename;
	$setting_description = $rolename;

	// keep only rules and operand OR
	$setting = $_POST; unset($setting['setting_name']); unset($setting['setting_description']); unset($setting['save']); unset($setting['role']);

	// echo '<pre>'.print_r($setting, true).'</pre>';

	$rules_combination = implode(' ', $setting); // will be saved in DB for references later

	// echo '<pre>'.print_r($rules_combination, true).'</pre>';

	foreach ($setting as $key => $value) {
		if ($value !== 'OR') {
			$setting[$key] = $DB->get_field('role_setting_rules', 'conditions_sql_detail', array('id'=>$value));
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

	// Remove redundant userids. This is the list of userids to add to a role
	$useridlist = array_unique($userids);
	// echo '<pre>'.print_r($useridlist, true).'</pre>';

	// Clear member of a role first then add new members
	$system_contextid = $DB->get_field('context','id',array('contextlevel'=>SYSTEM_CONTEXT_LEVEL));
	$DB->delete_records('role_assignments',array('roleid'=>$roleid,'contextid'=>$system_contextid));
	foreach ($useridlist as $userid) {
		role_assign($roleid, $userid, $system_contextid);
	}

	// Add a new setting
	if (!$DB->record_exists('role_setting',array('role_id'=>$roleid))) {	
		$role_setting_record = new stdClass();
	    $role_setting_record->role_id = $roleid;
	    $role_setting_record->setting_name = $setting_name;
	    $role_setting_record->description = $setting_description;
	    $role_setting_record->rules_combination = $rules_combination;
	    $DB->insert_record('role_setting', $role_setting_record);

	// Edit a current setting
	} else {			
		$role_setting_record = new stdClass();
		$role_setting_record->id = $current_setting_id;
	    $role_setting_record->setting_name = $setting_name;
	    $role_setting_record->description = $setting_description;
	    $role_setting_record->rules_combination = $rules_combination;
	    $DB->update_record('role_setting', $role_setting_record);
	}

	header('Location:' . $CFG->wwwroot .'/admin/tool/managerolerules/index.php'); 
}

/* --------------------------------------------------------------------------------------- */


$rules = $DB->get_records('role_setting_rules');
$rulelist = array();

foreach ($rules as $ruleid=>$ruleobject) {
	$rulelist[$ruleid] = (trim($ruleobject->description)!='') ? $ruleobject->rule_name.' - '.$ruleobject->description : $ruleobject->rule_name;
}
if($roleid=="") $html.= get_string('rolesettingaddheader','tool_managerolerules');
$html.= get_string('rolesettingeditheader','tool_managerolerules');

$table = '<table id="tbl_setting">';
$table .= '<tbody>';
$table .= '<tr>';
$table .= '<td>Role: </td><td><select name="role" required>'.$roleoption.'</select></td>';
$table .= '</tr>';
// Display current Rules for references
if ($roleid!="") {
	$table .= '<tr>';
	$table .= '<td>Current rule(s): </td>';
	$table .= '<td>';
	$current_rules = explode(' OR ', $current_rules_combination);
	$i = 0;
	foreach ($current_rules as $ruleid) {
		$i++;
	 	$rule_name = $DB->get_field('role_setting_rules', 'rule_name', array('id'=>$ruleid));
		$rule_description = $DB->get_field('role_setting_rules', 'description', array('id'=>$ruleid));
		$or = ($i<count($current_rules)) ? '; OR ' : '';
		$rule_description_concat = ($i<count($current_rules) && trim($rule_description) != '') ? ' - '.$rule_description : '';
		$table .= $rule_name.$rule_description_concat.$or.'<br>';
	}
	$table .= '</td></tr>';

	$table .= '<tr>';
	$table .= '<td>Change to rule(s): </td><td><select name="rule">';
} else {
	$table .= '<tr>';
	$table .= '<td>Choose rule(s): </td><td><select name="rule">';
}

foreach ($rulelist as $ruleid=>$rule) {
	$table .= '<option value="'.$ruleid.'">'.$rule.'</option>';
}
$table .= '</select></td>';
$table .= '</tr>';
$table .= '<tr><td></td><td><div id="add" class="btn">+</div></td></tr>';
$table .= '</tboby>';
$table .= '</table>';


$html.="<form id='editsettingform' action='' method='POST'>";
$html.= $table.'</br>';
$html.= '<input class="btn" id="save" type="submit" value="Save" name="save" style="vertical-align: top;"/> ';
$html.=' <a href="'.$CFG->wwwroot.'/admin/tool/managerolerules/index.php" class="btn"> Cancel</a>';
$html.= "</form>";

$html.=get_string('systemrole:note','tool_managerolerules');

echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();   
echo $html_lib;
?>



<script src="js/jquery-1.6.2.min.js"></script>
<script type="text/javascript">	
	var i = 0;
  	var rulelist_js = <?=json_encode($rulelist) ?>;
  	var rulelist = '';
  	$.each(rulelist_js,function(id, rule) {
		rulelist += '<option value="' + id + '">' + rule + '</option>';
	});

  	$('#add').click(function() {    
  		i++;
	    $('#tbl_setting tr:last').prev().after('<tr><td><input type="text" name="rule_operand'+i+'" value="OR" size=1 readonly/></td><td><select name="rule'+i+'">'+rulelist+'</select></td><td><button id="remove">-</button></td></tr>');
	}); 

	$('#remove').live ('click', function() {    
		$(this).closest ('tr').remove();
	}); 
</script>