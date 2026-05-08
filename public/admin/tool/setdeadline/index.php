<?php

use tool_setdeadline\model\reminder_assign;
use tool_setdeadline\utils;
use tool_setdeadline\form\reminder_form;
use tool_setdeadline\form\reminder_override_form;
use tool_setdeadline\model\reminder;
require('../../../config.php');
require_once $CFG->libdir.'/adminlib.php';
require_once('lib.php');
// require_once($CFG->libdir . '/accesslib.php');
require_once('smarty/Smarty.class.php');
// require_once("lib.php");
global $DB;

$DBH = new PDO("mysql:host=$CFG->dbhost;dbname=$CFG->dbname", $CFG->dbuser, $CFG->dbpass);

require_login(0,false);
$context_system = context_system::instance();
$PAGE->set_context($context_system);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot.'/admin/tool/setdeadline/index.php');
// $PAGE->navbar->ignore_active();
$PAGE->navbar->add("Set Course Deadlines", new moodle_url('/admin/tool/setdeadline/index.php'));

if(!has_capability('tool/setdeadline:set_deadline', $context_system)){
	redirect('/');
}

admin_externalpage_setup('toolsetdeadline');


/**
set up Smarty
*/
if (!file_exists($CFG->dataroot . '/tool_setdeadline/smarty/')) {
	mkdir($CFG->dataroot . '/tool_setdeadline/smarty/', 0777, true);
}
$smarty = new Smarty;
$path = realpath(".");
$smarty->compile_dir = $CFG->dataroot . '/tool_setdeadline/smarty/';
$smarty->template_dir = $path;
$smarty->config_dir = $path;
$smarty->cache_dir = $CFG->dataroot . '/tool_setdeadline/smarty/';

/**
get GET values
*/
$course = get_get_get("course");
$repeated = optional_param('repeated', '0', PARAM_INT);

$delete = isset($_GET['delete']);

$is_hierarchy_installed = is_hierarchy_installed_SD();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('courses_with_deadlines', 'tool_setdeadline'));


$deadlinelist = get_full_datelines();

foreach ($deadlinelist as $key => $val) {
	$deadlinelist[$key]->coursename = html_writer::link(new moodle_url('/course/view.php', array('id'=>$val->courseid)), $val->coursename);
	$priority="";
	switch ($val->deadlinetype) {
		case 'cohort': $priority = get_string('priority2','tool_setdeadline'); break;
		case 'course': $priority = get_string('priority3','tool_setdeadline'); break;
	}
	$deadlinelist[$key]->priority = $priority;
	$deadlinelist[$key]->deadlinetype = ucfirst($val->deadlinetype);
	$deadlinelist[$key]->sourcepath = $CFG->wwwroot;
	$deadlinelist[$key]->createddate = date('d/m/Y',$val->timecreated);

	if(is_siteadmin() || $USER->id== $val->modifiedby){

		$deadlinelist[$key]->deletelink = $OUTPUT->action_icon(
			new moodle_url('deadline_delete.php', array('id' => $val->id)),
			new pix_icon('t/delete', 'delete', 'moodle', array(
				'class' => 'iconsmall',
			)),
			null,
			array(
				'title' => 'delete'
			)
		);
		$deadlinelist[$key]->editlink = $OUTPUT->action_icon(
			new moodle_url('pages/edit_setting.php', array('id' => $val->id)),
			new pix_icon('t/edit', 'edit', 'moodle', array(
				'class' => 'iconsmall',
			)),
			null,
			array(
				'title' => 'edit'
			)
		);
	}
	//Admin can see this all the time
	// if ($is_developer_and_debug) {
	// } else {
	// 	$deadlinelist[$key]->test_link = '';
	// }
  if ($val->deadlinetype == 'Course' && is_siteadmin()) {
		$deadlinelist[$key]->test_link = $OUTPUT->action_icon(
			new moodle_url('deadline_info.php', array('id' => $val->courseid)),
			new pix_icon('i/report', 'Preview course deadline emails', 'moodle', array(
				'class' => 'iconsmall',
			))
		);
	}
	if($val->deadlinetype == 'Cohort') continue;
    $deadlinelist[$key]->override_link = $OUTPUT->action_icon(
      new moodle_url('pages/override_deadlines.php', array('id' => $val->id, 'courseid' => $val->courseid)),
      new pix_icon('i/users', 'Override course deadline', 'moodle', array(
        'class' => 'iconsmall',
      ))
    );
}
// var_dump($deadlinelist);
$smarty->assign('is_hierarchy_installed', $is_hierarchy_installed);
$smarty->assign("deadlinelist", $deadlinelist);
$smarty->display("report.html.tpl");
echo html_writer::link(new moodle_url('/admin/tool/setdeadline/pages/edit_setting.php'),get_string('set_deadline', 'tool_setdeadline'), array('title' =>get_string('set_deadline', 'tool_setdeadline'),'class'=>'btn btn-primary'));
echo $OUTPUT->footer();
