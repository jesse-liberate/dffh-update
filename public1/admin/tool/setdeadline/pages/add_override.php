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

$id       = optional_param('id'      , null, PARAM_INT);
$courseid = optional_param('courseid', null, PARAM_INT);
//$userids  = optional_param('user'    , null, PARAM_RAW);
//$pone     = optional_param('pone'    , null, PARAM_RAW);
//$isPost   = optional_param('isPost'  , false, PARAM_INT);

require_login(0, false);
$context_system = context_system::instance();

$PAGE->requires->js('/lib/mindatlas/jquery/jquery.min.js', true);
$PAGE->requires->js('/lib/mindatlas/jquery/ui/jquery-ui.min.js', true); 
$PAGE->requires->js('/lib/mindatlas/chosen/chosen.jquery.js', true);
$PAGE->requires->css('/lib/mindatlas/jquery/ui/jquery-ui.min.css');
$PAGE->requires->css('/lib/mindatlas/chosen/chosen.css');

$PAGE->set_context($context_system);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot . '/admin/tool/setdeadline/pages/add_override.php', [
  'id'       => $id, 
  'courseid' => $courseid
]);
//$PAGE->requires->js('/admin/tool/setdeadline/javascript/setting.js?' . time());

$index_url = new moodle_url('/admin/tool/setdeadline/index.php');
$override_deadlines = new moodle_url(
  '/admin/tool/setdeadline/pages/override_deadlines.php?id=' . $id . '&courseid=' . $courseid);

$PAGE->navbar->add("Course deadlines", $index_url);
$PAGE->navbar->add('User deadlines'   , $override_deadlines);
admin_externalpage_setup('toolsetdeadline');

$selected_course = get_course($courseid);
$course_context  = context_course::instance($courseid);

$users_deadlines = getCourseUserDeadlines($courseid, 'userid');
$userids = array();
if ($users_deadlines) {
  $userids = array_keys($users_deadlines);
}
$enrolled_users = get_enrolled_users(
  $course_context, '', 0, 'u.*', 'u.firstname,u.lastname ASC'
);
//For BlueCross site admin, they only can see their team members. Not others
if(!is_siteadmin()){
  $team_members = get_all_users_in_site();
}
// echo "<pre>".print_r($team_members,true)."</pre>"; die();

$all_users = array();
foreach ($enrolled_users as $userid => $user) {
  if (!in_array($userid, $userids)) {
    if(isset($team_members)){
      if(in_array($userid, $team_members)){
         $all_users[$userid] = fullname($user) . " ($user->email)"; 
      }
    }else{
      $all_users[$userid] = fullname($user) . " ($user->email)";
    }
  }
}

$all_admins = get_admins();
foreach ($all_admins as $adminid => $admin) {
  $all_admins[$adminid] = fullname($admin);
}
asort($all_admins);

$form = new reminder_override_form(null, 
  array(
    'reminderid' => $id,
    'courseid'   => $courseid,
    'all_users'  => $all_users,
    'all_admins' => $all_admins,
  )
);

if ($form->is_cancelled()) {
//  error_log('form cancelled');
  redirect($override_deadlines);
}

$message = "";

$data = $form->get_data();


if (!isset($data)) {
//  error_log('populate form');
  $reminder = reminder::get_by_id($id);
  $data = (array)$reminder->to_object();
  unset($data['firstreminder']);
  $form->setData($data);
  //$form->set_data($reminder->to_object());
}
else {
//  error_log('submit data');
//  error_log(var_export($data,true));
  foreach (array_values($data->user) as $userid) {
//    error_log('user id ' . $userid);
    $reminder_override = new reminder_override();
    $reminder_override->reminder_id   = $data->id;
    $reminder_override->userid        = $userid;
    $reminder_override->courseid      = $data->courseid;
    $reminder_override->firstreminder = $data->firstreminder;
    $reminder_override->save();
    //Any overwrite user profile will clear the previous email.
    $reminder_override->clear_previous_user_emails($userid,$courseid);
  }
  $msg = get_string('override_deadline:save:success', 'tool_setdeadline') . $message;  
  redirect($override_deadlines, $msg, null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $message;
//echo $OUTPUT->heading(get_string('courses_with_deadlines', 'tool_setdeadline'));
echo $OUTPUT->heading(get_string('course') . ': ' . $selected_course->fullname);
$form->display();
$script = <<< EOT
<script type='text/javascript'>
$(function() {
  $('#id_user').chosen({search_contains: true});
});
</script>
EOT;
echo $script;       
echo $OUTPUT->footer();


