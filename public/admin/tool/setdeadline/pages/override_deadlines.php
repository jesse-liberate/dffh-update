<?php

use tool_setdeadline\model\reminder_assign;
use tool_setdeadline\utils;
use tool_setdeadline\form\reminder_form;
use tool_setdeadline\form\reminder_override_form;
use tool_setdeadline\model\reminder;
require_once('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('../lib.php');
require_once('../smarty/Smarty.class.php');

/**
 * get GET values
 */
// course_reminder id
$id         = optional_param('id'         , 0 , PARAM_INT);
$overrideid = optional_param('overrideid' , 0 , PARAM_INT);
$courseid   = optional_param('courseid'   , 0 , PARAM_INT);
$delete     = optional_param('delete'     , 0 , PARAM_INT);
$confirm    = optional_param('confirm'    , '', PARAM_ALPHANUM);
// require_once($CFG->libdir . '/accesslib.php');
global $DB;

require_login(0,false);
$context_system = context_system::instance();
$PAGE->set_context($context_system);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot.'/admin/tool/setdeadline/index.php');
// $PAGE->navbar->ignore_active();
$index_url = new moodle_url('/admin/tool/setdeadline/index.php');
$PAGE->navbar->add("Set Course Deadlines", $index_url);

admin_externalpage_setup('toolsetdeadline');

if ($delete) {
  // start delete process
  $record = $DB->get_record('reminder_override', array('id' => $overrideid));
  $returnurl = new moodle_url('/admin/tool/setdeadline/pages/override_deadlines.php', array('id' => $id, 'courseid' => $courseid));
  $fullname = '';
  if ($record) {
    $user = $DB->get_record('user', array('id' => $record->userid));
    $fullname = fullname($user, true);
  }
  
  // show confirm dialog
  if ($confirm != md5($delete)) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('override_deadline:confirmdeleteremindertitle', 'tool_setdeadline'));

    $optionsyes = array('delete'=>$delete, 'confirm'=>md5($delete), 'sesskey'=>sesskey(), 'overrideid'=>$overrideid);
    $deleteurl = new moodle_url($returnurl, $optionsyes);
    $deletebutton = new single_button($deleteurl, get_string('delete'), 'post');

    $msg = get_string('override_deadline:confirmdeleteremindermessage', 'tool_setdeadline', "'$fullname'");

    echo $OUTPUT->confirm($msg, $deletebutton, $returnurl);
    echo $OUTPUT->footer();
    die;
  }
  // proceed to delete and back to course deadline table
  else {
    $DB->delete_records('reminder_override', array('id' => $overrideid));
    $msg = get_string('override_deadline:deletedreminder', 'tool_setdeadline', "'$fullname'");
    redirect($returnurl, $msg, null, \core\output\notification::NOTIFY_SUCCESS);
  }
}

$selected_course = get_course($courseid);
$course_context  = context_course::instance($courseid);
$enrolled_users_obj = get_enrolled_users($course_context, '', 0, 'u.id');
if(!is_siteadmin()){
  $all_users_site = get_all_users_in_site();
  $site_enrolled_users = array();
  if(!empty($enrolled_users_obj)){
    foreach ($enrolled_users_obj as $userid => $value) {
       if(in_array($userid, $all_users_site)){
         $site_enrolled_users [] = $userid;
       }
    }
  }
  $total_enrolled = count($site_enrolled_users);
}else $total_enrolled = count($enrolled_users_obj);
// var_dump($enrolled_users);
// var_dump($all_users_site); die();
// $site_enrolled_users = 
// $total_enrolled = count();

/**
 * set up Smarty
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

$deadlines = getCourseUserDeadlines(
  $courseid,
  'ro.id, ro.userid, u.firstname, u.lastname, u.email, ro.firstreminder, cr.secondreminder'
);
$counter = 0;
foreach ($deadlines as $key => $deadline) {
  if(!array_key_exists($deadline->userid, $enrolled_users_obj)){
    $DB->delete_records('reminder_override',array('id'=>$deadline->id)); 
    unset($deadlines[$key]);
    continue;
  }
  if(!is_siteadmin()){
    if(!in_array($deadline->userid,$site_enrolled_users)){ 
      unset($deadlines[$key]);
      continue;
    }
  }
  $counter++;

  $deadline->fullname = "<a href='".$CFG->wwwroot."/user/profile.php?id=".$deadline->userid."'>".$deadline->firstname." ".$deadline->lastname."</a>";
  $deadline->deletelink = $OUTPUT->action_icon(
		new moodle_url('override_deadlines.php', array(
      'id'         => $id,
      'overrideid' => $deadline->id, 
      'courseid'   => $courseid, 
      'delete'     => 1)
    ),
		new pix_icon('t/delete', 'delete', 'moodle', array(
			'class' => 'iconsmall',
		)),
		null,
		array(
			'title' => 'delete'
		)
	);
  $deadline->editlink = $OUTPUT->action_icon(
		new moodle_url('edit_override.php', array(
      'reminderid' => $id,
      'overrideid' => $deadline->id, 
      'courseid'   => $courseid
    )),
		new pix_icon('t/edit', 'edit', 'moodle', array(
			'class' => 'iconsmall',
		)),
		null,
		array(
			'title' => 'edit'
		)
	);
  $deadlines[$key] = $deadline;
}

$is_hierarchy_installed = is_hierarchy_installed_SD();

echo $OUTPUT->header();
echo $OUTPUT->heading('Override course deadline: ' . $selected_course->fullname);
///$smarty->assign('is_hierarchy_installed', $is_hierarchy_installed);
$smarty->assign('deadlines', $deadlines);
$smarty->assign('total_enrolled', $total_enrolled);
$smarty->assign('index_url', $index_url);
$smarty->display("../report_override_deadlines.html.tpl");
if($total_enrolled > 0){
  if($counter < $total_enrolled){
    echo html_writer::link(new moodle_url('/admin/tool/setdeadline/pages/add_override.php',array('id'=>$id,'courseid'=>$courseid)),get_string('override_deadline:add_button', 'tool_setdeadline'), array('title' =>get_string('override_deadline:add_button', 'tool_setdeadline'),'class'=>'btn btn-primary'))." ";
  }
}else{
  echo "<strong>No user has enrolled to the course yet. You can go to ".
  html_writer::link(new moodle_url('/admin/tool/courseenrol/cohort.php'),'Manage Course enrolments', array('class'=>'btn btn-primary'))
  ." to enrol your users <strong> <br>\n"; 
}
echo html_writer::link($index_url,get_string('override_deadline:back_button', 'tool_setdeadline'), array('class'=>'btn btn-primary'))
;
echo $OUTPUT->footer();
