<?php
require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/admin/tool/setdeadline/lib.php');
require_once $CFG->libdir.'/adminlib.php';

global $USER;
$reminderid = required_param('id',PARAM_INT);
$confirm = optional_param('confirm',0,PARAM_INT);

if(!isset($userid)) $userid = $USER->id;
$current_url = new moodle_url('/admin/tool/setdeadline/deadline_delete.php',array('id'=>$reminderid));
$return_url = new moodle_url('/admin/tool/setdeadline/index.php');
$title = "Confirm to delete set deadline";

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($current_url);
if ($CFG->forcelogin) {
    require_login(); 
}
$reminder = $DB->get_record('course_reminder',array('id'=>$reminderid));
$course = $DB->get_record('course',array('id'=>$reminder->courseid));

if(!is_siteadmin() && $USER->id!= $reminder->modifiedby){
	redirect($return_url,get_string('accessdenied','tool_setdeadline'), '', core\output\notification::NOTIFY_ERROR);
}


if($confirm!=0){// Confirm for delete the resources
    if(confirm_sesskey()){

		$DB->delete_records('reminder_email', array('courseid' => $reminder->courseid));
		$DB->delete_records("course_reminder", array('id' => $reminderid));
		$DB->delete_records('reminder_override', array('reminder_id' => $reminderid));
        redirect($return_url, get_string('message:success','tool_setdeadline',get_string('deadline:remove','tool_setdeadline',$course->fullname))
        	, '', core\output\notification::NOTIFY_SUCCESS);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$yesurl = new moodle_url('/admin/tool/setdeadline/deadline_delete.php', array('id'=>$reminderid, 'confirm' => 1, 'sesskey' => sesskey(), 'returnurl' => $return_url->out_as_local_url()));
$message = get_string('deadline:confirm:delete','tool_setdeadline',$course->fullname);

echo $OUTPUT->confirm($message, $yesurl, $return_url);

echo $OUTPUT->footer();
