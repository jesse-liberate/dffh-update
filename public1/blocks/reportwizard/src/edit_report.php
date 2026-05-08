<?php

require(__DIR__.'/../../../config.php');
require_once('../renderer.php');
require_once('classes/report.php');
require_once('classes/reports_helper.php');
require_once('lib.php');

// echo '<pre>';
// var_dump($_POST);
// echo '</pre>';

if (!is_manager_user($USER->id) && !is_siteadmin()) {
	redirect($CFG->wwwroot, 'Sorry, you don\'t have access to this page', null, \core\output\notification::NOTIFY_INFO);
}

$hierarchy_nodes = \reporting\lib\get_hierarchy_tree($USER->id);
$root_node_id = \reporting\lib\get_root_hierarchy($USER->id);

$report_id = required_param('id', PARAM_INT);
$report = null;
$reports_helper = new block_reportwizard_reports_helper();

if ( $DB->record_exists('report_wzd_report', array('id'=>$report_id)) ) {
	$report = new block_reportwizard_report( $DB->get_record('report_wzd_report', array('id' => $report_id)) );
}else{
	redirect('myreports.php', 'Invalid URL. No report found.', null, \core\output\notification::NOTIFY_ERROR);
}

// only admin and creator can edit
if (!is_siteadmin($USER) && $report->creator_id != $USER->id) {
	redirect($CFG->wwwroot, 'Sorry, you don\'t have access to this page', null, \core\output\notification::NOTIFY_INFO);
}

if (isset($_POST['submit']) && $_POST['submit'] == 'Save') {
	$report->name = $_POST['reportname'];
	$report->type = $_POST['reporttype'];

	// @30/07/2018 enhancement
	if ($_POST['reporttype'] == block_reportwizard_report::REPORT_TYPE_ACTIVITY) {
		$report->object_id = $_POST['activity_course'];
	} else {
		if (!empty($_POST['category_course'])){
			$report->object_id = implode(',', $_POST['category_course']);
		}
	}
	// if ($_POST['reporttype'] == block_reportwizard_report::REPORT_TYPE_ACTIVITY) {
	// 	$report->object_type = block_reportwizard_report::OBJECT_COURSE;
	// 	$report->object_id = $_POST['activity_course'];
	// }else{
	// 	$report->object_type = get_coursecat_from_post($_POST['category_course'])['type'];
	// 	$report->object_id = get_coursecat_from_post($_POST['category_course'])['id'];;		
	// }
	$selectednodes = $_POST['selectednodes'];
	// var_dump($selectednodes);
	$report->hierarchy_nodes = get_db_nodenames_from_nodeids($selectednodes); //$_POST['selectednodenames'];
	// var_dump($report->hierarchy_nodes);
	// die();

	// @30/07/2018 enhancement
	if (!empty($_POST['enrol_date_from'])) {
		$report->enrol_date_from = strtotime(str_replace('/', '-', $_POST['enrol_date_from']) . ' 00:00');
	} else {
		$report->enrol_date_from = null;
	}
	if (!empty($_POST['enrol_date_to'])) {
		$report->enrol_date_to = strtotime(str_replace('/', '-', $_POST['enrol_date_to']) . ' 23:59');
	} else {
		$report->enrol_date_to = null;
	}
	if (!empty($_POST['complete_date_from'])) {
		$report->complete_date_from = strtotime(str_replace('/', '-', $_POST['complete_date_from']) . ' 00:00');
	} else {
		$report->complete_date_from = null;
	}
	if (!empty($_POST['complete_date_to'])) {
		$report->complete_date_to = strtotime(str_replace('/', '-', $_POST['complete_date_to']) . ' 23:59');
	} else {
		$report->complete_date_to = null;
	}
	// if (!empty($_POST['enrol_period_number'])) {
	// 	$report->enrol_period_condition = $_POST['enrol_period_condition'];
	// 	$report->enrol_period_number = $_POST['enrol_period_number'];
	// 	$report->enrol_period_unit = $_POST['enrol_period_unit'];
	// }
	// if (!empty($_POST['complete_period_number'])) {
	// 	$report->complete_period_condition = $_POST['complete_period_condition'];
	// 	$report->complete_period_number = $_POST['complete_period_number'];
	// 	$report->complete_period_unit = $_POST['complete_period_unit'];
	// }


	$report->completion_status = $_POST['completion'];
	$report->access_type = $_POST['access_type'];
	$selectednodes_shareto = $_POST['selectednodes_shareto'];
	$report->share_to = get_db_nodenames_from_nodeids($selectednodes_shareto);//$_POST['selectednodenames_shareto'];

	if ($report->db_update()) {

		// $reports_helper->share_to($report_id, $_POST['selectednodenames_shareto']);
		$reports_helper->share_to($report_id, $report->share_to);

		redirect('view_report.php?id='.$report_id, 'Report updated successfully.', null, \core\output\notification::NOTIFY_SUCCESS);
	}
}

require_login();


$context_system = context_system::instance();
$PAGE->set_context($context_system);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading(get_string('pluginname','block_reportwizard'));
$PAGE->set_url('/blocks/reportwizard/src/edit_report.php');

$PAGE->navbar->ignore_active();
// $PAGE->navbar->add('Home', new moodle_url('/'));
$PAGE->navbar->add(get_string("pluginname",'block_reportwizard'), new moodle_url('/blocks/reportwizard/src/myreports.php'));
$PAGE->navbar->add(get_string("editreport",'block_reportwizard'));

$PAGE->requires->js('/lib/mindatlas/jquery/jquery.min.js', true);
$PAGE->requires->js('/lib/mindatlas/jquery/ui/jquery-ui.min.js', true);
$PAGE->requires->js('/blocks/reportwizard/src/javascript/jstree/dist/jstree.min.js',true);
// @30/07/2018 enhancement
$PAGE->requires->js('/lib/mindatlas/chosen/chosen.jquery.min.js', true);
$PAGE->requires->js('/blocks/reportwizard/src/javascript/lib.js',true);

$PAGE->requires->css('/lib/mindatlas/jquery/ui/jquery-ui.min.css');
// @30/07/2018 enhancement
$PAGE->requires->css('/lib/mindatlas/chosen/chosen.min.css');
$PAGE->requires->css('/blocks/reportwizard/src/css/plugin.css');
$PAGE->requires->css('/blocks/reportwizard/src/javascript/jstree/dist/themes/default/style.min.css');


$output = $PAGE->get_renderer('block_reportwizard');

echo $output->header();
echo $output->heading(get_string('editreport', 'block_reportwizard'));

echo $output->form_report($report);


?>

<script type="text/javascript">

var REPORT_TYPE_GENERAL = '<?php echo block_reportwizard_report::REPORT_TYPE_GENERAL; ?>';
var REPORT_TYPE_COURSEOVERVIEW = '<?php echo block_reportwizard_report::REPORT_TYPE_COURSEOVERVIEW; ?>';
var REPORT_TYPE_ACTIVITY = '<?php echo block_reportwizard_report::REPORT_TYPE_ACTIVITY; ?>';
var REPORT_TYPE_MANDATORY_ONLINE = '<?php echo block_reportwizard_report::REPORT_TYPE_MANDATORY_ONLINE; ?>';

var hierarchy_nodes = <?php echo $hierarchy_nodes; ?>;
var root_node_id = <?php echo $root_node_id; ?>;

$(function() {


});

</script>


<script src="javascript/reportwizard.js?v=<?php echo time(); ?>"></script>


<style type="text/css">
/*	header.navbar {
		display: none;
	}*/
.chosen-container
{
    max-width: 400px !important;
    width: 100% !important;
}
</style>



<?php

echo $OUTPUT->footer();

?>




