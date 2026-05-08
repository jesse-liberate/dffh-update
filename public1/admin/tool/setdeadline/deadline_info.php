<?php
require_once(__DIR__ . '/../../../config.php');
require_once("$CFG->libdir/adminlib.php");

define('DEADLINE_INFO', true);
global $DEBUG_RESULTS;
$courseid = required_param('id',PARAM_INT);

$return_page = new moodle_url('/admin/tool/setdeadline/index.php');
$DEBUG_RESULTS = array();
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin'); 
$PAGE->set_title($SITE->fullname.': Debug Course Deadline');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/admin/tool/setdeadline/deadline_info.php?id=' . $_GET['id']);
$PAGE->navbar->add("Set course deadlines", $return_page);
$PAGE->navbar->add("Course Deadline Information",
	new moodle_url('/admin/tool/setdeadline/deadline_info.php', array(
		'id' => $courseid,
	)
));
admin_externalpage_setup('toolsetdeadline');

echo($OUTPUT->header());
?>
<style type="text/css">
.email-message {
    display: block;
    padding: 9.5px;
    margin: 0 0 10px;
    font-size: 13px;
    line-height: 20px;
    border-radius: 4px;
    word-wrap: break-word;
    background-color: #f5f5f5;	
}
</style>
<?php
$coursename = $DB->get_field('course','fullname',array('id'=>$courseid));
$back_page = html_writer::link($return_page,'Back',array('class'=>'btn btn-custom'));

echo($OUTPUT->heading('COURSE - '.$coursename));
echo $back_page;
ob_start();
require_once('send_emails.php');
$send_emails_output = ob_get_clean();

foreach ($DEBUG_RESULTS as $result) {
	echo("$result<br/>");
}

echo('<div class="text-center">----------Output from send_emails----------</div>');
if (empty(trim($send_emails_output))) {
	echo('No message<br/>');
} else {
	echo($send_emails_output);
}
echo $back_page;
echo($OUTPUT->footer());
?>