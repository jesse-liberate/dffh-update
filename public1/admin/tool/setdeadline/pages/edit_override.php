<?php

use tool_setdeadline\model\reminder_assign;
use tool_setdeadline\utils;
use tool_setdeadline\form\reminder_override_form;
use tool_setdeadline\model\reminder;
use tool_setdeadline\model\reminder_override;

require_once('../../../../config.php');
require_once('../lib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/authlib.php');

$reminderid = optional_param('reminderid', null, PARAM_INT);
$overrideid = optional_param('overrideid', null, PARAM_INT);
$courseid   = optional_param('courseid'  , null, PARAM_INT);




//$overrideid = optional_param('overrideid', null, PARAM_INT);

//$userids  = optional_param('user'    , null, PARAM_RAW);
//$pone     = optional_param('pone'    , null, PARAM_RAW);
//$isPost   = optional_param('isPost'  , false, PARAM_INT);

require_login(0, false);
$context_system = context_system::instance();

$PAGE->requires->js('/lib/mindatlas/jquery/jquery.min.js', true);

$PAGE->set_context($context_system);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot . '/admin/tool/setdeadline/pages/edit_override.php', [
  'reminderid' => $reminderid,
  'overrideid' => $overrideid,
  'courseid'   => $courseid
]);

// set navigation breadcrumb
$course_page_url = new moodle_url('/admin/tool/setdeadline/index.php');
$overide_page_url = new moodle_url(
  '/admin/tool/setdeadline/pages/override_deadlines.php',
  array('id' => $reminderid, 'courseid' => $courseid)
);

$PAGE->navbar->add("Course deadlines", $course_page_url);
$PAGE->navbar->add('User deadlines'   , $overide_page_url);
admin_externalpage_setup('toolsetdeadline');

$selected_course = get_course($courseid);
$course_context  = context_course::instance($courseid);

$all_admins = get_admins();
foreach ($all_admins as $adminid => $admin) {
  $all_admins[$adminid] = fullname($admin);
}
asort($all_admins);



$form = new reminder_override_form(null, 
  array(
    'edit'       => 1,
    'courseid'   => $courseid,
    'reminderid' => $reminderid,
    'overrideid' => $overrideid,
    'all_admins' => $all_admins
  )
);

if ($form->is_cancelled()) {
//  error_log('form cancelled');
  redirect($overide_page_url);
}

$message = "";

$data = $form->get_data();
$override = reminder_override::get_by_id($overrideid);
$user = $override->get_user();
$user_fullname = fullname($user, true);
if (!isset($data)) {
//  error_log('populate form');  
  $reminder = reminder::get_by_id($reminderid);
  $data = array_merge($reminder->get_attributes(), $override->get_attributes());  
  $data['user'] = $user_fullname . ' (' . $user->email . ')';
  $form->setData($data);
}
else {
  $override->firstreminder = $data->firstreminder;
  $override->save();
  $override->clear_previous_user_emails($user->id,$courseid);
  $msg = get_string('override_deadline:update:success', 'tool_setdeadline', "'$user_fullname'");  
  redirect($overide_page_url, $msg, null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $message;
//echo $OUTPUT->heading(get_string('courses_with_deadlines', 'tool_setdeadline'));
echo $OUTPUT->heading(get_string('course') . ': ' . $selected_course->fullname);
$form->display();     
echo $OUTPUT->footer();
