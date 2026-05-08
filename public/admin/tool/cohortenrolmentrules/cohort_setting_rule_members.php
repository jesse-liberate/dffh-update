<?php

/**
 * @package   tool_cohortenrolmentrules
 * @copyright  2016  Charlie Tran
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Include required libary files
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require($CFG->dirroot.'/cohort/lib.php');
require_once('lib.php');

// Include what we need
// require_once($CFG->dirroot.'/vendor/autoload.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin'); 
$PAGE->set_title($SITE->fullname.': Cohort Enrolment Rules');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/admin/tool/cohortenrolmentrules/cohort_setting_rule_members.php');

//Setup Breadcrumbs
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Site administration');
$PAGE->navbar->add('Users');
$PAGE->navbar->add('Accounts');
$PAGE->navbar->add('Cohort Enrolment Rules', new moodle_url('/admin/tool/cohortenrolmentrules/index.php'));
$PAGE->navbar->add('Members');

$contextid = optional_param('contextid', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$searchquery  = optional_param('search', '', PARAM_RAW);


if ($CFG->forcelogin) {
    require_login();
}


if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
} else {
    $context = context_system::instance();
}

echo $OUTPUT->header();

echo get_string('cohortsettingrulememberheader','tool_cohortenrolmentrules');

$ruleid = $_GET['id'];

$members = $DB->get_fieldset_select('cohort_setting_rule_members','userid', 'ruleid =?',array($ruleid));

foreach ($members as $key=>$userid) {
    $user = $DB->get_record('user', array('id'=>$userid, 'deleted'=>0));
    if (!empty($user)) {        
        $line = array();

        $line[] = html_writer::link(new moodle_url('/user/editadvanced.php', array('id' => $userid)),
                    html_writer::tag('span', $user->username));
        $line[] = html_writer::link(new moodle_url('/user/editadvanced.php', array('id' => $userid)),
                    html_writer::tag('span', $user->firstname));
        $line[] = html_writer::link(new moodle_url('/user/editadvanced.php', array('id' => $userid)),
                    html_writer::tag('span', $user->lastname));
        $line[] = html_writer::link(new moodle_url('/user/editadvanced.php', array('id' => $userid)),
                    html_writer::tag('span', $user->email));
        $img = "<img src='".$CFG->wwwroot."/admin/tool/cohortenrolmentrules/js/show.png' width='15'/>";
        if($user->suspended==1){
            $img = "<img src='".$CFG->wwwroot."/admin/tool/cohortenrolmentrules/js/hide.png' width='15' title='Suspended'/>";
        }
        $line[] = html_writer::link(new moodle_url('/user/editadvanced.php', array('id' => $userid)),
                    $img);

        $data_rules[] = $row = new html_table_row($line);
    }
}

if (isset($data_rules)) {
    $table_rule_members = new html_table();

    $table_rule_members->head = array('Username', 'First name', 'Last name', 'Email','Status');
    $table_rule_members->colclasses = array('leftalign name', 'leftalign description', 'leftalign description','centeralign source');
    $table_rule_members->id = 'cohort_setting_rule_members';
    $table_rule_members->attributes['class'] = 'admintable generaltable';
    $table_rule_members->data  = $data_rules;

    echo html_writer::table($table_rule_members);

} else echo '<p>There is no members belong to this rule.</p>';

echo "<a class='btn' href='index.php'>Back</a>";

echo $OUTPUT->footer();   

?>