<?php

require(__DIR__.'/../../../config.php');
require_once('../renderer.php');
require_once('classes/report.php');
require_once('lib.php');

if (!is_manager_user($USER->id) && !is_siteadmin()) {
	redirect($CFG->wwwroot, 'Sorry, you don\'t have access to this page', null, \core\output\notification::NOTIFY_INFO);
}

$hierarchy_nodes = \reporting\lib\get_hierarchy_tree($USER->id);
$root_node_id = \reporting\lib\get_root_hierarchy($USER->id);
$reports_helper = new block_reportwizard_reports_helper();


if (isset($_POST['submit']) && $_POST['submit'] == 'Save') {
	
	// echo '<pre>'.print_r($_POST, true).'</pre>'; die();

	$new_record = new stdClass();
	$new_record->name = $_POST['reportname'];
	$new_record->type = $_POST['reporttype'];

	// @30/07/2018 enhancement
	if ($_POST['reporttype'] == block_reportwizard_report::REPORT_TYPE_ACTIVITY) {
		$new_record->object_id = $_POST['activity_course'];
	} else {
		if (!empty($_POST['category_course'])){
			$new_record->object_id = implode(',', $_POST['category_course']);
		}
	}
	// if ($_POST['reporttype'] == block_reportwizard_report::REPORT_TYPE_ACTIVITY) {
	// 	$new_record->object_type = block_reportwizard_report::OBJECT_COURSE;
	// 	$new_record->object_id = $_POST['activity_course'];
	// }else{
	// 	$new_record->object_type = get_coursecat_from_post($_POST['category_course'])['type'];
	// 	$new_record->object_id = get_coursecat_from_post($_POST['category_course'])['id'];;		
	// }

	/*
	 * Read the selected node ids and get the real node names before saving the report in database
	 */
	$selected_nodes = explode(",", $_POST['selectednodes']);
	$selected_node_names = array();
	foreach($selected_nodes as $nodeid){
		$selected_node_names[] = $DB->get_field('hierarchy_node', 'name', array('id'=> $nodeid));
	}
	// $new_record->hierarchy_nodes = $_POST['selectednodenames'];
	$new_record->hierarchy_nodes = implode(", ", $selected_node_names);

	// @30/07/2018 enhancement
	if (!empty($_POST['enrol_date_from'])){
		$new_record->enrol_date_from = strtotime(str_replace('/', '-', $_POST['enrol_date_from']) . ' 00:00');
	}
	if (!empty($_POST['enrol_date_to'])){
		$new_record->enrol_date_to = strtotime(str_replace('/', '-', $_POST['enrol_date_to']) . ' 23:59');
	}
	if (!empty($_POST['complete_date_from'])){
		$new_record->complete_date_from = strtotime(str_replace('/', '-', $_POST['complete_date_from']) . ' 00:00');
	}
	if (!empty($_POST['complete_date_to'])){
		$new_record->complete_date_to = strtotime(str_replace('/', '-', $_POST['complete_date_to']) . ' 23:59');
	}
	// if (!empty($_POST['enrol_period_number'])) {
	// 	$new_record->enrol_period_condition = $_POST['enrol_period_condition'];
	// 	$new_record->enrol_period_number = $_POST['enrol_period_number'];
	// 	$new_record->enrol_period_unit = $_POST['enrol_period_unit'];
	// }
	// if (!empty($_POST['complete_period_number'])) {
	// 	$new_record->complete_period_condition = $_POST['complete_period_condition'];
	// 	$new_record->complete_period_number = $_POST['complete_period_number'];
	// 	$new_record->complete_period_unit = $_POST['complete_period_unit'];
	// }
	
	$new_record->completion_status = $_POST['completion'];
	$new_record->creator_type = creator_type($USER->id);
	$new_record->access_type = $_POST['access_type'];
	$new_record->share_to = $_POST['selectednodenames_shareto'];
	$new_record->creator_id = $USER->id;
	$new_record->timecreated = time();

	$new_report_id = block_reportwizard_report::db_insert($new_record);

	if ($new_report_id) {

		// process infofield filters
		foreach ($_POST as $key => $value) {
			if (strpos($key, 'infofield_') === 0) {
				$infofield_id = substr($key, strlen('infofield_'));
				$reports_helper->save_filter_value($new_report_id,$infofield_id,$value);
			}
		}

		$reports_helper->share_to($new_report_id, $_POST['selectednodenames_shareto']);

		redirect('view_report.php?id='.$new_report_id, 'New report created successfully.', null, \core\output\notification::NOTIFY_SUCCESS);
	}

// echo '<pre>';
// var_dump($new_record);
// echo '</pre>';

}


require_login();


$context_system = context_system::instance();
$PAGE->set_context($context_system);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading(get_string('pluginname','block_reportwizard'));
$PAGE->set_url('/blocks/reportwizard/src/create_report.php');

$PAGE->navbar->ignore_active();
// $PAGE->navbar->add('Home', new moodle_url('/'));
$PAGE->navbar->add(get_string("pluginname",'block_reportwizard'), new moodle_url('/blocks/reportwizard/src/myreports.php'));
$PAGE->navbar->add(get_string("createnewreport",'block_reportwizard'));

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
echo $output->heading(get_string('createnewreport', 'block_reportwizard'));

echo $output->form_report(0);

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

echo $output->footer();

?>




