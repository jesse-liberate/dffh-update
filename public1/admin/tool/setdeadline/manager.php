<?php

use tool_setdeadline\model\reminder_assign;
use tool_setdeadline\utils;
use tool_setdeadline\form\reminder_form;
use tool_setdeadline\form\reminder_override_form;
use tool_setdeadline\model\reminder;
require('../../../config.php');
require_once $CFG->libdir.'/adminlib.php';
require_once('lib.php');
global $DB;

require_login(0,false);
$context_system = context_system::instance();
$PAGE->set_context($context_system);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot.'/admin/tool/setdeadline/manager.php');
$PAGE->navbar->add(get_string('setting_coursedeadline','tool_setdeadline'), new moodle_url('/admin/tool/setdeadline/index.php'));
// admin_externalpage_setup('toolsetdeadline');



$repeated = optional_param('repeated', '0', PARAM_INT);

$delete = isset($_GET['delete']);


if ($delete) {
	//Delete all emails has been sent out:
	$reminder_id = $_GET['id'];
	$reminder = $DB->get_record('course_reminder_manager',array('id'=>$reminder_id));
	$course = $DB->get_record('course',array('id'=>$reminder->courseid));
	$DB->delete_records('reminder_email', array('courseid' => $reminder->courseid));
	$DB->delete_records("course_reminder_manager", array('id' => $reminder_id));
	$DB->delete_records('reminder_override', array('reminder_id' => $reminder_id));
	echo get_string('message:success','tool_setdeadline',get_string('deadline:remove','tool_setdeadline',$course->fullname));
}
$headers = array(
	get_string('coursename', 'tool_setdeadline'),
	'Period 1 (in days)',
	'Period 2 (in days after period 1)',
	'Repeated',
	'Send to Manager',
	'Created date',
	'Action'
);

$deadlinelist = get_full_datelines();
$table1 = new html_table();
$table1->head = $headers;
$table1->attributes = array(
    'class' => 'tablesorter generaltable'
);
if(!empty($deadlinelist)){
	foreach ($deadlinelist as $row) {
		$cells = array();
		$cells [] = $row->coursename;
		$cells [] = ($row->firstreminder==0) ? "None" : ($row->firstreminder/86400);
		$cells [] = ($row->secondreminder==0) ? "None" : ($row->secondreminder/86400);
		$cells [] = ($row->repeated==1) ? "Yes" : "No";
		$cells [] = ($row->manager==1) ? "Yes" : "No";
		$cells [] = date('d/m/Y',$row->timecreated);
		$cells [] = "-";
		$table1->data[] = new html_table_row($cells);
	}
}
//For manager
$deadlinelist_mgr = get_manager_coursedeadlines();
$table2 = new html_table();
$table2->head = $headers;
$table2->attributes = array(
    'class' => 'tablesorter generaltable'
);
if(!empty($deadlinelist_mgr)){
	foreach ($deadlinelist_mgr as $row) {
		$delete = $OUTPUT->action_icon(
				new moodle_url('manager.php', array('id' => $row->id, 'delete' => 1)),
				new pix_icon('t/delete', 'delete', 'moodle', array(
				'class' => 'iconsmall',
			)), null,array('title' => 'delete'));
		$edit = $OUTPUT->action_icon(
				new moodle_url('manager_edit.php', array('id' => $row->id)),
				new pix_icon('t/edit', 'edit', 'moodle', array(
			'class' => 'iconsmall',
			)),null,array('title' => 'edit'));

		$cells = array();
		$cells [] = $row->coursename;
		$cells [] = ($row->firstreminder==0) ? "None" : ($row->firstreminder/86400);
		$cells [] = ($row->secondreminder==0) ? "None" : ($row->secondreminder/86400);
		$cells [] = ($row->repeated==1) ? "Yes" : "No";
		$cells [] = ($row->manager==1) ? "Yes" : "No";
		$cells [] = date('d/m/Y',$row->timecreated);
		$cells [] = $delete." ".$edit;
		$table2->data[] = new html_table_row($cells);
	}
}


echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('courses_with_deadlines', 'tool_setdeadline')." - Global");
echo html_writer::table($table1)."<br>\n";
echo $OUTPUT->heading(get_string('courses_with_deadlines', 'tool_setdeadline')." - Site");
echo html_writer::table($table2);
echo html_writer::link(new moodle_url('/admin/tool/setdeadline/manager_add.php'),get_string('set_deadline', 'tool_setdeadline'), array('title' =>get_string('set_deadline', 'tool_setdeadline'),'class'=>'btn btn-primary'));
echo $OUTPUT->footer();
