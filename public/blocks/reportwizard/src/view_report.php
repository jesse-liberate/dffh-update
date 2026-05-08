<?php

require(__DIR__ . '/../../../config.php');
require_once('../renderer.php');
require_once('classes/report.php');
require_once('lib.php');

if (!is_manager_user($USER->id) && !is_siteadmin()) {
	redirect($CFG->wwwroot, 'Sorry, you don\'t have access to this page', null, \core\output\notification::NOTIFY_INFO);
}

require_login();

$report_id = required_param('id', PARAM_INT);
$report = null;

if ($DB->record_exists('report_wzd_report', array('id' => $report_id))) {
	$report = new block_reportwizard_report($DB->get_record('report_wzd_report', array('id' => $report_id)));
}

$context_system = context_system::instance();
$PAGE->set_context($context_system);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading(get_string('pluginname', 'block_reportwizard'));
$PAGE->set_url('/blocks/reportwizard/src/view_report.php');

$PAGE->navbar->ignore_active();
// $PAGE->navbar->add('Home', new moodle_url('/'));
$PAGE->navbar->add(get_string("pluginname", 'block_reportwizard'), new moodle_url('/blocks/reportwizard/src/myreports.php'));
$PAGE->navbar->add(get_string("viewreport", 'block_reportwizard'));

$PAGE->requires->js('/lib/mindatlas/jquery/jquery.min.js', true);
$PAGE->requires->js('/lib/mindatlas/jquery/ui/jquery-ui.min.js', true);
$PAGE->requires->css('/lib/mindatlas/jquery/ui/jquery-ui.min.css');
$PAGE->requires->css('/blocks/reportwizard/src/css/plugin.css');


$output = $PAGE->get_renderer('block_reportwizard');

echo $output->header();
echo $output->heading(get_string('viewreport', 'block_reportwizard'));

echo $output->view_report($report);


?>


<section class="section-page-buttons clearfix">
	<div class="page-buttons">
		<div class="float-left">
			<a href="run_report.php?id=<?= $report_id ?>" class="btn btn-primary"><?php echo 'Run'; ?></a>
			<a href="edit_report.php?id=<?= $report_id ?>" class="btn btn-primary"><?php echo 'Edit Settings'; ?></a>
			<a class="btn btn-primary" href="manage_report_columns.php?id=<?= $report_id ?>"><?php echo get_string('managereportcolumns', 'block_reportwizard'); ?></a>
			<a href="myreports.php" class="btn btn-primary"><?php echo 'Back to ' . get_string('myreport', 'block_reportwizard'); ?></a>
		</div>

	</div>
</section>

<script>
	$(function() {


	});
</script>

<?php

echo $OUTPUT->footer();

?>