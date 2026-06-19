<?php

use tool_setdeadline\model\reminder_assign;
use tool_setdeadline\utils;
use tool_setdeadline\form\reminder_form;
use tool_setdeadline\model\reminder;

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/../lib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/authlib.php');

$id = optional_param('id', 0, PARAM_INT);

require_login(0, false);
$context_system = context_system::instance();
$PAGE->set_context($context_system);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot . '/admin/tool/setdeadline/pages/edit_setting.php', [
    'id' => $id
]);
$PAGE->requires->js('/lib/mindatlas/jquery/jquery.min.js', true);
$PAGE->requires->js('/lib/mindatlas/chosen/chosen.jquery.min.js', true);
$PAGE->requires->css('/lib/mindatlas/chosen/chosen.css');

$PAGE->requires->js('/admin/tool/setdeadline/javascript/setting.js');
$PAGE->navbar->add("Set Course Deadlines", new moodle_url('/admin/tool/setdeadline/index.php'));
admin_externalpage_setup('toolsetdeadline');

$all_courses = utils::get_all_courses('id, fullname');
foreach ($all_courses as $courseid => $course) {
    $all_courses[$courseid] = $course->fullname;
}


$userfieldsapi = \core_user\fields::for_name();
$namefields = $userfieldsapi->get_sql('', false, '', '', false)->selects;

$all_users = utils::get_all_users('id,email,' . $namefields);
foreach ($all_users as $userid => $user) {
    $all_users[$userid] = fullname($user) . " ($user->email)";
}
$all_cohorts = utils::get_all_cohorts('id, name');
foreach ($all_cohorts as $cohortid => $cohort) {
    $all_cohorts[$cohortid] = $cohort->name;
}
$all_admins = get_admins();
foreach ($all_admins as $adminid => $admin) {
    $all_admins[$adminid] = fullname($admin);
}
if(is_siteadmin()){
    $deadline_types = reminder_assign::get_types();
}else $deadline_types = array('course' => get_string('course'));
$reminder_form = new reminder_form(null, [
    'deadline_types' => $deadline_types,
    'all_courses' => $all_courses,
    'all_users' => $all_users,
    'all_cohorts' => $all_cohorts,
    'all_admins' => $all_admins,
    'reminderid' => $id
]);

$base_url = new moodle_url('/admin/tool/setdeadline/index.php');

if (!empty($id)) {
    $reminder = reminder::get_by_id($id);
    $reminder_form->set_data($reminder->to_object());
    //Only site admin and owner can edit it
    if(!is_siteadmin() && $USER->id!= $reminder->modifiedby){
        redirect($base_url);
    }
}
$message = "";
if ($reminder_form->is_cancelled()) {
    redirect($base_url);
} else if ($data = $reminder_form->get_data()) {
    $message = reminder_assign::course_reminder_check_conflict($data->course, $data->deadline_type, $id);
    if ($message == "") {
        $error = false;
        $message = reminder_assign::course_reminder_verify_data($data, $error);
        if (!$error) {
            if (!isset($reminder)) {
                $reminder = new reminder();
                $reminder->timecreated = time();
            }
            $reminder->firstreminder = $data->pone * 86400;
            $reminder->secondreminder = $data->ptwo * 86400;
            $reminder->manager = isset($data->manager) ? $data->manager : 0;
            $reminder->siteadmin = isset($data->siteadmin) ? $data->siteadmin : 0;
            if (isset($data->emailtoadmin)) {
                $reminder->emailadminlist = implode(",", $data->emailtoadmin);
            }
            $reminder->repeated = isset($data->repeated) ? $data->repeated : 0;

            $reminder->type = $data->deadline_type;
            $reminder->courseid = $data->course;
            $reminder->timemodified = time();
            $reminder->modifiedby = $USER->id;

            switch ($data->deadline_type) {
                case 'course':
                    $reminder->instanceid = $data->course;
                    break;
                case 'cohort':
                    if(!empty($data->cohort)){
                        $reminder->instanceid = implode(',', $data->cohort);
                    }
                    break;
            }
            $reminder->save();
            
            $course = get_course($data->course);
            $a = (object)array('coursename' => $course->fullname, 'deadlinetype' => ucfirst($data->deadline_type));
            $message = ($message == "") ? "" : get_string('message:success', 'tool_setdeadline', $message);
            $msg = get_string('set_deadline:save:success', 'tool_setdeadline', $a) . $message;

            redirect($base_url, $msg, null, \core\output\notification::NOTIFY_SUCCESS);
        } else $message = get_string('message:error', 'tool_setdeadline', $message);
    }
}
echo $OUTPUT->header();
echo $message;
echo $OUTPUT->heading(get_string('courses_with_deadlines', 'tool_setdeadline'));
$reminder_form->display();
echo $OUTPUT->footer();
?>
<script>
    var config = {
        'select.chosen-select': {},
        '.chosen-select-deselect': {
            allow_single_deselect: true
        },
        '.chosen-select-no-single': {
            disable_search_threshold: 10
        },
        '.chosen-select-no-results': {
            no_results_text: 'Could not find any!'
        },
        '.chosen-select-width': {
            width: '95%'
        }
    }
    for (var selector in config) {
        $(selector).chosen(config[selector]);
    }
</script> 