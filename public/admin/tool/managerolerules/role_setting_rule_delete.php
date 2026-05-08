<?php

// Include required libary files
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once('lib.php');

// $delete    = optional_param('delete', 0, PARAM_BOOL);
$delete    = optional_param('delete', 0, PARAM_BOOL);
$confirm   = optional_param('confirm', 0, PARAM_BOOL);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

// Include what we need
// require_once($CFG->dirroot.'/vendor/autoload.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin'); 
$PAGE->set_title($SITE->fullname.': User role rules');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/admin/tool/managerolerules/role_setting_rule_delete.php');

//Setup Breadcrumbs
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Site administration');
$PAGE->navbar->add('Users');
$PAGE->navbar->add('Accounts');
$PAGE->navbar->add('User role rules', new moodle_url('/admin/tool/managerolerules/index.php'));
$PAGE->navbar->add('Delete Rule', new moodle_url('/admin/tool/managerolerules/role_setting_rule_delete.php'));

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

if ($returnurl) {
    $returnurl = new moodle_url($returnurl);
} else {
    $returnurl = new moodle_url('/role/index.php', array('contextid'=>$context->id));
}

/* ---------- Get the warning meaasge and number of users affected when deleting rules ---- */

if (isset($_GET['id']) && !empty($_GET['id'])) {
	$ruleid = $_GET['id'];
	$current_rule = $DB->get_record('role_setting_rules', array('id'=>$ruleid));	

	// List all the role settings available
	$rolesettings = $DB->get_records('role_setting');
	// echo '<pre>'.print_r($rolesettings, true).'</pre>';

	$order = array();

	foreach ($rolesettings as $id => $setting) {
		// $rules_sql = explode('||', $setting->rules_sql);
		$rules_combination = explode(' OR ', $setting->rules_combination);

		foreach ($rules_combination as $key => $rule_id) {
			if ($ruleid === $rule_id) {
				// Save the order of the rule in rule combination that will be remove later
				$order[$setting->role_id] = $rules_combination[$key];
			}
		}		
	}

	$role_names = ''; 

	// echo '<pre>'.print_r($order, true).'</pre>';
	$count=0;
	
	foreach ($order as $role_id => $rule_id) {
		$role_names .= $DB->get_field('role', 'name', array('id'=>$role_id)).'; ';
		$rule_sql = $DB->get_field('role_setting_rules', 'conditions_sql_detail', array('id'=>$rule_id));
		$count = count($DB->get_records_sql($rule_sql));
	}

	// echo '<pre>'.print_r($order, true).'</pre>';
	$warning = 'role <strong>'.$role_names.'</strong> and <strong>'.$count.'</strong> users will be removed from those roles';
}

/* --------------------------------------------------------------------------------------- */

if (isset($_POST) && !empty($_POST)) {

	$ruleid = $_POST['id'];
	$delete = $_POST['delete'];
	$confirm = $_POST['confirm'];
	// echo '<pre>'.print_r($_POST, true).'</pre>';

	if ($confirm and confirm_sesskey()) {
	    rolesetting_delete_rule($ruleid);
	    redirect($returnurl);
	}
}

/* --------------------------------------------------------------------------------------- */

echo $OUTPUT->header();

$strheading = get_string('deleterule', 'tool_managerolerules');
$PAGE->navbar->add($strheading);
echo $OUTPUT->heading($strheading);

$yesurl = new moodle_url('/admin/tool/managerolerules/role_setting_rule_delete.php', array('id' => $ruleid, 'delete' => 1,
    'confirm' => 1, 'sesskey' => sesskey(), 'returnurl' => $returnurl->out_as_local_url()));

$message_warning = get_string('deletewarning', 'tool_managerolerules', $warning);
$message_confirm = get_string('deleteconfirm', 'tool_managerolerules', format_string($current_rule->rule_name));
$message = $message_warning.$message_confirm;

echo $OUTPUT->confirm($message, $yesurl, $returnurl);
echo $OUTPUT->footer();
die;

?>
