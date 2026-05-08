<?php
require_once('../../config.php');
require_once('lib.php');

// $delete    = optional_param('delete', 0, PARAM_BOOL);
$delete    = optional_param('delete', 0, PARAM_BOOL);
$confirm   = optional_param('confirm', 0, PARAM_BOOL);

// Include what we need
// require_once($CFG->dirroot.'/vendor/autoload.php');
$context_system = context_system::instance();
$PAGE->set_context($context_system);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading(get_string('coachees','block_coach'));
$PAGE->set_url($CFG->wwwroot. '/blocks/coach/coach_delete.php');
if ($CFG->forcelogin) {  require_login();}

require_capability('block/coach:manage_coaches', $context_system);

$contextid = optional_param('contextid', 0, PARAM_INT);


if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
} else {
    $context = context_system::instance();
}

$returnurl = new moodle_url('/blocks/coach/coachees.php');

/* --------------------------------------------------------------------------------------- */

if (isset($_POST) && !empty($_POST)) {

	$userid = $_POST['id'];
	$delete = $_POST['delete'];
	$confirm = $_POST['confirm'];
	// echo '<pre>'.print_r($_POST, true).'</pre>';

	if ($confirm and confirm_sesskey()) {
	    // delete_coach_supervisor($userid,NOT_STUDENT_OR_SUPERVISOR);
	    delete_coach($userid);
	    redirect($returnurl);
	}
}

/* --------------------------------------------------------------------------------------- */
if (isset($_GET['id']) && !empty($_GET['id'])) {
	$userid = $_GET['id'];
	$user = $DB->get_record('user',array('id'=>$userid));
	$current_user = ucfirst($user->firstname)." ".ucfirst($user->lastname);
	$num_affected = $DB->count_records('coachees',array('coachid'=>$userid,'is_student'=>NOT_STUDENT_OR_SUPERVISOR));

	echo $OUTPUT->header();

	$strheading = get_string('coach:delete', 'block_coach');
	$PAGE->navbar->add($strheading);
	echo $OUTPUT->heading($strheading);

	$yesurl = new moodle_url('/blocks/coach/coach_delete.php', array('id' => $userid, 'delete' => 1,
	    'confirm' => 1, 'sesskey' => sesskey(), 'returnurl' => $returnurl->out_as_local_url()));
	$message_warning = get_string('delete:coachwarning','block_coach',$num_affected);
	$message_confirm = get_string('delete:coachconfirm', 'block_coach', format_string($current_user));
	$message = $message_warning.$message_confirm;

	echo $OUTPUT->confirm($message, $yesurl, $returnurl);
	echo $OUTPUT->footer();
}

?>
