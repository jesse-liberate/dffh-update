<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/templates/schedule_form.php');
require_once($CFG->dirroot.'/blocks/reportwizard/src/classes/reports_helper.php');

require_login();
$reportid = required_param('reportid', PARAM_INT);

$context = context_system::instance();
$base_url = new moodle_url('/blocks/reportwizard/src/myreports.php');
$url = new moodle_url('/blocks/reportwizard/src/schedule.php',array('reportid'=>$reportid));
$heading = get_string('reportwizard:schedule','block_reportwizard');


$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title("$SITE->fullname: $heading");
$PAGE->set_pagelayout('standard');

// $PAGE->navbar->add('home');
$PAGE->navbar->add($heading, $PAGE->url);

$html_lib = "
<script src='js/jquery-1.12.2.min.js'></script>
<script src='js/jquery-ui.js'></script>";

if ( $DB->record_exists('report_wzd_report', array('id'=>$reportid)) ) {
	$report = new block_reportwizard_report( $DB->get_record('report_wzd_report', array('id' => $reportid)) );
	// echo "<pre>".print_r($report,true)."</pre>"; die();
	if($report->type==block_reportwizard_report::REPORT_TYPE_MANDATORY_ONLINE && ($report->complete_date_from!="" || $report->complete_date_to!="")){
		redirect($base_url, get_string('mandatory_online:error:schedule:completiondate','block_reportwizard', $report->name), '', core\output\notification::NOTIFY_ERROR);
	}	
}

$schedule_form = new schedule_form(null, array('reportid'=>$reportid));
$schedule = $DB->get_record('report_wzd_schedule',array('report_id'=>$reportid));
if(!empty($schedule)){
    $schedule_form->set_data($schedule);
}
if ($schedule_form->is_cancelled()) {
    redirect($base_url);
}elseif ($data = $schedule_form->get_data()) {
    // var_dump($data);die();
    if(isset($data->startdate) && ($data->startdate=="")){
       redirect($url, 'Start date can not be emptied', '', core\output\notification::NOTIFY_ERROR); 
    }
    block_reportwizard_reports_helper::save_reportwizard_schedule($data);
    redirect($base_url, get_string('savesuccess','block_reportwizard'), '', core\output\notification::NOTIFY_SUCCESS);
}
echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
echo $html_lib;
echo $schedule_form->render();
echo $OUTPUT->footer();

?>
<script type="text/javascript">
$(function() {
    $('head').append('<link rel="stylesheet" href="<?php echo $CFG->wwwroot;?>/blocks/reportwizard/src/css/jquery-ui.css" type="text/css" media="all">');
    $("#id_startdate").datepicker({
        dateFormat: 'dd/mm/yy',
        minDate: 0
    });
});
</script>