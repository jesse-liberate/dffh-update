<?php

require(__DIR__.'/../../../config.php');
require_once('../renderer.php');
require_once('classes/report.php');
require_once('classes/reports_helper.php');
require_once('lib.php');

//////////////////
// Manage filters page is included into edit and create report page
redirect('myreports.php', 'Invalid URL. Manage filters page is deprecated.', null, \core\output\notification::NOTIFY_ERROR);
//
//////////////////

if (!is_manager_user($USER->id) && !is_siteadmin()) {
	redirect($CFG->wwwroot, 'Sorry, you don\'t have access to this page', null, \core\output\notification::NOTIFY_INFO);
}

$report_id = required_param('id', PARAM_INT);

$report = null;

if ( $DB->record_exists('report_wzd_report', array('id'=>$report_id)) ) {
	$report = new block_reportwizard_report( $DB->get_record('report_wzd_report', array('id' => $report_id)) );
}else{
	redirect('myreports.php', 'Invalid URL. No report found.', null, \core\output\notification::NOTIFY_ERROR);
}

$reports_helper = new block_reportwizard_reports_helper();

if (isset($_POST['submit']) && $_POST['submit'] == 'Save') {
	foreach ($_POST as $key => $value) {
		if ($key == 'submit') {
			continue;
		}

		//function enable_report_filter($report_id,$infofield_id,$enabled)
		if ($value == '1') {
			$reports_helper->enable_report_filter($report_id,$key,true);
		}else{
			$reports_helper->enable_report_filter($report_id,$key,false);
		}
	}

	redirect('edit_report.php?id='.$report_id, 'Report filters updated successfully. Please input filter values.', null, \core\output\notification::NOTIFY_SUCCESS);
}

require_login();


$context_system = context_system::instance();
$PAGE->set_context($context_system);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading(get_string('pluginname','block_reportwizard'));
$PAGE->set_url('/blocks/reportwizard/src/manage_report_filters.php');

$PAGE->navbar->ignore_active();
// $PAGE->navbar->add('Home', new moodle_url('/'));
$PAGE->navbar->add(get_string("pluginname",'block_reportwizard'), new moodle_url('/blocks/reportwizard/src/myreports.php'));
$PAGE->navbar->add($report->name, new moodle_url('/blocks/reportwizard/src/view_report.php',array('id'=>$report_id)));
$PAGE->navbar->add(get_string("managefilters",'block_reportwizard'));

$PAGE->requires->js('/lib/mindatlas/jquery/jquery.min.js', true);
$PAGE->requires->js('/lib/mindatlas/jquery/ui/jquery-ui.min.js', true);
$PAGE->requires->css('/lib/mindatlas/jquery/ui/jquery-ui.min.css');
$PAGE->requires->css('/blocks/reportwizard/src/css/plugin.css');


$output = $PAGE->get_renderer('block_reportwizard');

echo $output->header();
echo $output->heading(get_string('managefilters', 'block_reportwizard'));

echo $output->form_manage_filters($report);


?>



<script>


</script>

<style type="text/css">





</style>


<?php

echo $OUTPUT->footer();

?>




