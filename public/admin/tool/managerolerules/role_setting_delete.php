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
$PAGE->set_url($CFG->wwwroot. '/admin/tool/managerolerules/role_setting_delete.php');

//Setup Breadcrumbs
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Site administration');
$PAGE->navbar->add('Users');
$PAGE->navbar->add('Accounts');
$PAGE->navbar->add('User role rules', new moodle_url('/admin/tool/managerolerules/index.php'));
$PAGE->navbar->add('Delete Rule', new moodle_url('/admin/tool/managerolerules/role_setting_delete.php'));

$contextid = optional_param('contextid', 0, PARAM_INT);

if ($CFG->forcelogin) {
    require_login();
}

if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
} else {
    $context = context_system::instance();
}

$returnurl = new moodle_url('/admin/tool/managerolerules/index.php');

/* ---------- Get the warning meaasge and number of users affected when deleting rules ---- */
$current_role = "";
if (isset($_GET['id']) && !empty($_GET['id'])) {
	$roleid = $_GET['id'];
	$setting = $DB->get_record('role_setting', array('role_id'=>$roleid));	
	$current_role = $setting->setting_name;
	$system_contextid = $DB->get_field('context','id',array('contextlevel'=>SYSTEM_CONTEXT_LEVEL));
	$warning = $DB->count_records('role_assignments',array('roleid'=>$roleid,'contextid'=>$system_contextid));
}

/* --------------------------------------------------------------------------------------- */

if (isset($_POST) && !empty($_POST)) {

	$rolesetting = $_POST['id'];
	$delete = $_POST['delete'];
	$confirm = $_POST['confirm'];
	// echo '<pre>'.print_r($_POST, true).'</pre>';

	if ($confirm and confirm_sesskey()) {
	    rolesetting_delete($rolesetting);
	    redirect($returnurl);
	}
}

/* --------------------------------------------------------------------------------------- */

echo $OUTPUT->header();

$strheading = get_string('role_deleterule', 'tool_managerolerules');
$PAGE->navbar->add($strheading);
echo $OUTPUT->heading($strheading);

$yesurl = new moodle_url('/admin/tool/managerolerules/role_setting_delete.php', array('id' => $setting->id, 'delete' => 1,
    'confirm' => 1, 'sesskey' => sesskey(), 'returnurl' => $returnurl->out_as_local_url()));

$message_warning = get_string('role_deletewarning', 'tool_managerolerules', $warning);
$message_confirm = get_string('role_deleteconfirm', 'tool_managerolerules', format_string($current_role));
$message = $message_warning.$message_confirm;

echo $OUTPUT->confirm($message, $yesurl, $returnurl);
echo $OUTPUT->footer();
die;

?>
