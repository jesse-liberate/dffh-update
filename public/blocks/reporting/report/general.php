<?php

require_once(__DIR__ . '/../../../config.php');
//require_once($CFG->dirroot . '/blocks/reporting/report/smarty/Smarty.class.php');
require_once($CFG->dirroot . '/blocks/reporting/report/lib.php');
require_once($CFG->dirroot . '/blocks/reporting/classes/report_class.php');
require_once($CFG->dirroot . '/blocks/reporting/classes/report/ma_constants.php');
require_once($CFG->dirroot . '/blocks/reporting/classes/report/util_class.php');
//require_once($CFG->dirroot . '/blocks/reporting/classes/report/user_class.php');
require_once($CFG->dirroot . '/blocks/reporting/classes/report/filter_class.php');

use block_reporting\report\filter_class;
use block_reporting\report\util_class;

$blocks_dir = '/blocks/reporting';
$mindatlas_lib = '/lib/mindatlas';
$report_dir = $blocks_dir . '/report';

$PAGE->requires->js($mindatlas_lib . '/jquery/jquery.min.js', true);
$PAGE->requires->js($mindatlas_lib . '/jquery/ui/jquery-ui.min.js', true);
$PAGE->requires->css($mindatlas_lib . '/jquery/ui/jquery-ui.min.css');
$PAGE->requires->js($mindatlas_lib . '/datatables/datatables.min.js', true);
$PAGE->requires->css($mindatlas_lib . '/datatables/datatables.min.css');
$PAGE->requires->css($report_dir . '/css/reporting.css?v=' . $PAGE->requires->get_jsrev());
//$PAGE->requires->js($report_dir . '/js/jquery.tablesorter.js', true);

// Check login status
require_login(0, false);

$userid      = $USER->id;
$base_url    = $report_dir . '/' . basename(__FILE__);
$is_admin    = is_siteadmin($userid);
$export_link = 'export_all.php?type=general';

$hierarchy_installed = is_hierarchy_installed();

$DATE_FIELDS = array(
  'HireDate' => null,
  'lastaccess' => null
);


$rpt = new report_class();

parse_str($_SERVER['QUERY_STRING'], $request_values);
if (!isset($request_values['page'])) {
  $request_values['page'] = 0;
}
if (!isset($request_values['sort'])) {
  $request_values['sort'] = 'ASC';
}
if (!isset($request_values['type'])) {
  $request_values['type'] = 'HTML';
}

// Special treatment for FromPayroll, delete it if val is empty
if (empty($request_values['FromPayroll'][0])) {
  unset($request_values['FromPayroll']);
}

//REMOVE ALL DELETED FIELDS TO AVOID ISSUE
remove_deleted_fields();
$general_filters   = util_class::getGeneralFilterRecords();
$reporting_filters = util_class::getReportingFilterRecords();

$is_html = util_class::isHTML($request_values['type']);

// get report headers
$headers = array(
  get_string('fullname'),
  get_string('course_name', 'block_reporting'),
  get_string('module', 'block_reporting'),
  get_string('completion', 'block_reporting'),
  get_string('enrolled_date', 'block_reporting'),
  get_string('completion_date', 'block_reporting')
);
$filterHeaders = util_class::get_headers();
$headers = array_merge($headers, $filterHeaders);

if (!$is_html) {
  util_class::process_export_csv($headers, $request_values);
  // $rows = util_class::get_data($is_html, $request_values);
  // print_object($rows); die();
  // $rows = array();
  // util_class::exportCSV($headers, $rows, 'generalreport_' . date('Ymd_His') . '.csv');
  exit;  
}

//error_log(var_export($request_values, true));

$context_system = context_system::instance();
$PAGE->set_context($context_system);
$PAGE->set_pagelayout('reports');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading(get_string('general:reportname', 'block_reporting'));
$PAGE->set_url($base_url);
$PAGE->navbar->add(get_string('general:reportname', 'block_reporting'), new moodle_url($base_url));


// Show Filter Page
if (!isset($request_values['is_post'])) {
  $PAGE->requires->js($mindatlas_lib . '/moment/moment.js', true);
  $PAGE->requires->js(new moodle_url($report_dir . '/js/reporting.js'), true);
  $PAGE->requires->js($report_dir . '/js/jstree/dist/jstree.min.js', true);
  $PAGE->requires->js($report_dir . '/resource/chosen.jquery.js', true);
  $PAGE->requires->css($report_dir . '/css/chosen.css');
  $PAGE->requires->css($report_dir . '/js/jstree/dist/themes/default/style.min.css');  
  echo $OUTPUT->header();
  $courses = $hierarchy_installed ? getCourses_Category_User() : getCourses_Category();
  $user_profile_filters_array = get_reporting_filter_array($hierarchy_installed);
  $general_filters_array      = get_general_filter_array();
  $datepicker_fields          = get_reporting_date_picker_script();
  $template = 'interface_general.html.tpl';
  $smarty_params = array(
    'courses'            => $courses,
    'datepicker_fields'  => $datepicker_fields,  
    //'max_display_limit_reminder' => $max_display_limit_reminder,
    //'max_export_limit_reminder' => $max_export_limit_reminder,
    'export_link'        => $export_link,
    'is_admin'           => $is_admin,
    'reportname'         => 'general'
  );
  if (!empty($general_filters_array)) {
    $smarty_params['general_filters_array'] = $general_filters_array;
  }
  if (!empty($user_profile_filters_array)) {
    $smarty_params['user_profile_filters_array'] = $user_profile_filters_array;
  }
  if ($hierarchy_installed) {
    $template = 'interface_general_hierarchy.html.tpl';
    $smarty_params['hierarchy_nodes'] = get_hierarchy_tree($userid);
    $smarty_params['root_node_id'] = get_root_hierarchy($userid);
  }
//  error_log('show ' . $template);
  $rpt->setTemplate($template);
  $rpt->showTemplate($smarty_params);
  echo $OUTPUT->footer();
  exit(0);
}

//set_time_limit(300);
//raise_memory_limit(MEMORY_HUGE);

$filter_class         = new filter_class($request_values, $reporting_filters, $general_filters);

// foreach (util_class::getReportingFilterRecords() as $fieldid => $filter) {
//   $headers[] = $filter->name;
// //  $column_types[] = $filter->datatype;
// }
// foreach (util_class::getGeneralFilterRecords() as $id => $filter) {
//   $headers[] = $filter->name;
// //  $column_types[] = $filter->datatype;
// }


echo $OUTPUT->header();
$rpt->setTemplate('report_general.html.tpl');
$params = array(
  'headers' => $headers, 
  'ajax_url' => $CFG->wwwroot."/blocks/reporting/report/ajax/report.php",
  'filters' => $filter_class->getConcatFilters(),
  'pre_loading_img' => "<img src='".$OUTPUT->image_url('i/loading_small')."' class='report_pre_loading' alt='Loading...'/>",
);

$rpt->showTemplate($params);
echo $OUTPUT->footer();
