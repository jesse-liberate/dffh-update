<?php

require(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../renderer.php');
require_once('classes/reports_helper.php');
require_once('lib.php');


require_login();

if (!is_manager_user($USER->id) && !is_siteadmin()) {
	redirect($CFG->wwwroot, 'Sorry, you don\'t have access to this page', null, \core\output\notification::NOTIFY_INFO);
}

$context_system = context_system::instance();
$PAGE->set_context($context_system);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading(get_string('pluginname','block_reportwizard'));
$PAGE->set_url('/blocks/reportwizard/src/myreports.php');

$PAGE->navbar->ignore_active();
// $PAGE->navbar->add('Home', new moodle_url('/'));
$PAGE->navbar->add(get_string("pluginname",'block_reportwizard'), new moodle_url('/blocks/reportwizard/src/myreports.php'));

$PAGE->requires->js('/lib/mindatlas/jquery/jquery.min.js', true);
$PAGE->requires->js('/lib/mindatlas/jquery/ui/jquery-ui.min.js', true);
$PAGE->requires->css('/lib/mindatlas/jquery/ui/jquery-ui.min.css');
$PAGE->requires->css('/blocks/reportwizard/src/css/plugin.css');

// block_reportwizard_renderer_base from renderer.php
$output = $PAGE->get_renderer('block_reportwizard');

echo $output->header();
echo $output->heading(get_string('myreport', 'block_reportwizard'));


$reports_helper = new block_reportwizard_reports_helper();
$public_reports = $reports_helper->user_public_reports($USER->id);
$private_reports = $reports_helper->user_private_reports($USER->id);

?>

<section class="section-public-reports">
	<table class="table-public-reports">
		<?php echo $output->my_public_reports($public_reports);?>
	</table>
</section>

<section class="section-public-reports">
	<table class="table-public-reports">
		<?php echo $output->my_private_reports($private_reports);?>
	</table>
</section>


<section class="section-page-buttons">
	<div class="page-buttons">
		<a href="create_report.php" class="btn btn-primary"><?php echo get_string('createnewreport', 'block_reportwizard');?></a>
	</div>
</section>

<?php

echo $output->footer();