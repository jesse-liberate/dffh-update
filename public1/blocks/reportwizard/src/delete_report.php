<?php

require(__DIR__.'/../../../config.php');
require_once('../renderer.php');
require_once('classes/report.php');
require_once('classes/reports_helper.php');
require_once('lib.php');

if (!is_manager_user($USER->id) && !is_siteadmin()) {
	redirect($CFG->wwwroot, 'Sorry, you don\'t have access to this page', null, \core\output\notification::NOTIFY_INFO);
}


$report_id = required_param('id', PARAM_INT);
$report = null;
$reports_helper = new block_reportwizard_reports_helper();

if ( $DB->record_exists('report_wzd_report', array('id'=>$report_id)) ) {
	$report = new block_reportwizard_report( $DB->get_record('report_wzd_report', array('id' => $report_id)) );
}else{
	redirect('myreports.php', 'Invalid URL. No report found.', null, \core\output\notification::NOTIFY_ERROR);
}

// only admin and creator can delete
if (!is_siteadmin($USER) && $report->creator_id != $USER->id) {
	redirect($CFG->wwwroot, 'Sorry, you don\'t have access to this page', null, \core\output\notification::NOTIFY_INFO);
}

if (isset($_POST['submit']) && $_POST['submit'] == 'Delete') {
	if (is_siteadmin($USER) || $report->creator_id == $USER->id) {
		if ($report->db_delete()) {
			redirect('myreports.php', 'Report deleted successfully.', null, \core\output\notification::NOTIFY_SUCCESS);
		}
	}


}

require_login();


$context_system = context_system::instance();
$PAGE->set_context($context_system);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading(get_string('pluginname','block_reportwizard'));
$PAGE->set_url('/blocks/reportwizard/src/delete_report.php');

$PAGE->navbar->ignore_active();
// $PAGE->navbar->add('Home', new moodle_url('/'));
$PAGE->navbar->add(get_string("pluginname",'block_reportwizard'), new moodle_url('/blocks/reportwizard/src/myreports.php'));
$PAGE->navbar->add(get_string("deletereport",'block_reportwizard'));

$PAGE->requires->js('/lib/mindatlas/jquery/jquery.min.js', true);
$PAGE->requires->js('/lib/mindatlas/jquery/ui/jquery-ui.min.js', true);
$PAGE->requires->js('/blocks/reportwizard/src/javascript/jstree/dist/jstree.min.js',true);
$PAGE->requires->js('/blocks/reportwizard/src/javascript/lib.js',true);

$PAGE->requires->css('/lib/mindatlas/jquery/ui/jquery-ui.min.css');
$PAGE->requires->css('/blocks/reportwizard/src/css/plugin.css');
$PAGE->requires->css('/blocks/reportwizard/src/javascript/jstree/dist/themes/default/style.min.css');


$output = $PAGE->get_renderer('block_reportwizard');

echo $output->header();
echo $output->heading(get_string('deletereport', 'block_reportwizard'));

// echo $output->form_report($report);


?>


<div id="notice" class="box generalbox modal modal-dialog modal-in-page show">
	<div id="modal-content" class="box modal-content">
		<div id="modal-header" class="box modal-header"><h4>Confirm</h4></div>
		<div id="modal-body" class="box modal-body">
			<p>Are you sure you want to delete this report?<br><br>
				<b><?= $report->name?></b></p>
		</div>
		<div id="modal-footer" class="box modal-footer">
			<div class="buttons">
				<div class="singlebutton">
					<form method="post" action="">
						<div>
							<input type="submit" name="submit" value="Delete">
							<input type="hidden" name="id" value="<?= $report->id ?>">
						</div>
					</form>
				</div>
				<div class="singlebutton">
					<form method="get" action="myreports.php">
						<div>
							<input type="submit" value="Cancel">
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">


$(function() {


});

</script>


<script src="javascript/reportwizard.js?v=2"></script>


<style type="text/css">
/*	header.navbar {
		display: none;
	}*/
</style>



<?php

echo $OUTPUT->footer();

?>




