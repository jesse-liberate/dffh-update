<?php

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/blocks/reporting/report/smarty/Smarty.class.php');
require_once($CFG->dirroot . '/blocks/reporting/report/lib.php');
require_once($CFG->dirroot . '/blocks/reporting/classes/report_class.php');
require_once($CFG->dirroot . '/blocks/reporting/classes/report/ma_constants.php');

use block_reporting\report\filter_class;
use block_reporting\report\user_class;
use block_reporting\report\course_class;
use block_reporting\report\user_enrolment_class;
use block_reporting\report\util_class;

$blocks_dir = '/blocks/reporting';
$mindatlas_lib = '/lib/mindatlas';
$report_dir = $blocks_dir . '/report';

$PAGE->requires->js($mindatlas_lib . '/jquery/jquery.min.js', true);
$PAGE->requires->js($mindatlas_lib . '/jquery/ui/jquery-ui.min.js', true);
$PAGE->requires->css($mindatlas_lib . '/jquery/ui/jquery-ui.min.css');
$PAGE->requires->css($report_dir . '/css/reporting.css?v=' . $PAGE->requires->get_jsrev());

// Check login status
require_login(0, false);

$userid   = $USER->id;
$base_url = '/blocks/reporting/report/' . basename(__FILE__);
$is_admin    = is_siteadmin($userid);
$export_link = 'export_all.php?type=courseoverview';

$hierarchy_installed = is_hierarchy_installed();

$DATE_FIELDS = array(
  'HireDate' => null,
  'lastaccess' => null
);

//REMOVE ALL DELETED FIELDS TO AVOID ISSUE
remove_deleted_fields();
parse_str($_SERVER['QUERY_STRING'], $request_values);

//initialise the report class
$rpt = new report_class();

$context_system = context_system::instance();
$PAGE->set_context($context_system);
$PAGE->set_pagelayout('reports');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading(get_string('courseoverview:reportname', 'block_reporting'));
$PAGE->set_url($base_url);
$PAGE->navbar->add(get_string('courseoverview:reportname', 'block_reporting'), new moodle_url($base_url));

// Show Filter Page
if (!isset($request_values['is_post'])) {
  $PAGE->requires->js($mindatlas_lib . '/moment/moment.js', true);  
  $PAGE->requires->js(new moodle_url($report_dir . '/js/reporting.js'), true);
  $PAGE->requires->js($report_dir . '/js/chosen.js', true);
  $PAGE->requires->js($report_dir . '/js/jstree/dist/jstree.min.js', true);
  $PAGE->requires->js($report_dir . '/resource/chosen.jquery.js', true);
  $PAGE->requires->css($report_dir . '/css/chosen.css');
  $PAGE->requires->css($report_dir . '/js/jstree/dist/themes/default/style.min.css');
  
  
  echo $OUTPUT->header();

  $courses                    = $hierarchy_installed ? getCourses_Category_User() : getCourses_Category();
  $user_profile_filters_array = get_reporting_filter_array($hierarchy_installed);
  $general_filters_array      = get_general_filter_array();
  $datepicker_fields          = get_reporting_date_picker_script();
  $template = 'interface_courseoverview.html.tpl';
  $smarty_params = array(
    'courses'            => $courses,
    'datepicker_fields'  => $datepicker_fields,
    'reportname'        => 'courseoverview',
    'export_link'        => $export_link,
    'is_admin'           => $is_admin
   );
  if (!empty($general_filters_array)) {
    $smarty_params['general_filters_array'] = $general_filters_array;
  }
  if (!empty($user_profile_filters_array)) {
    $smarty_params['user_profile_filters_array'] = $user_profile_filters_array;
  }
  if ($hierarchy_installed) {
    $template = 'interface_courseoverview_hierarchy.html.tpl';
    $smarty_params['hierarchy_nodes'] = get_hierarchy_tree($userid);
    $smarty_params['root_node_id'] = get_root_hierarchy($userid);
  }
//  error_log('show ' . $template);
  $rpt->setTemplate($template);
  $rpt->showTemplate($smarty_params);
  echo $OUTPUT->footer();
  exit(0);
}
// Show filter page ends

/**
 * process from here once the form is submitted
 */
if (!isset($request_values['page'])) {
  $request_values['page'] = 0;
}
if (!isset($request_values['sort'])) {
  $request_values['sort'] = 'ASC';
}
if (!isset($request_values['type'])) {
  $request_values['type'] = 'HTML';
}

$general_filters   = util_class::getGeneralFilterRecords();
$reporting_filters = util_class::getReportingFilterRecords();
$fieldids = array_keys($reporting_filters);

$filter_class         = new filter_class($request_values, $reporting_filters, $general_filters);
$user_class           = new user_class($request_values, $fieldids, $reporting_filters, $hierarchy_installed);
$course_class         = new course_class($request_values);

// for course overview report
$user_enrolment_class = new user_enrolment_class($request_values, $user_class, $course_class);

// get report headers
$headers = array(
  get_string('fullname'),
  get_string('course_name', 'block_reporting'),
);
$column_types = array(
  'text',
  'text',
);

// check if report type is HTML or CSV
$is_html = util_class::isHTML($request_values['type']);

if (!$is_html) {
  $headers[] = get_string('completedpercentage', 'block_reporting');
  $column_types[] = 'text';
}

$headers[] = get_string('enrolled_date', 'block_reporting');
$headers[] = get_string('completion_date', 'block_reporting');
$column_types[] = 'datetime';
$column_types[] = 'datetime';

foreach (util_class::getReportingFilterRecords() as $fieldid => $filter) {
  $headers[] = $filter->name;
  $column_types[] = $filter->datatype;
}
foreach (util_class::getGeneralFilterRecords() as $id => $filter) {
  $headers[] = $filter->name;
  $column_types[] = $filter->datatype;
}

/**
 * If export to csv is selected
 */
if (!$is_html) {
  util_class::process_export_csv($headers, $request_values);
  // $rows = util_class::get_data($is_html, $request_values);
  // // print_object($rows); die();
  // util_class::exportCSV($headers, $rows, 'courseoverviewreport_' . date('Ymd_His') . '.csv');
  exit;
} else {
  /**
   * Add the required scripts for HTML view
   */
  $PAGE->requires->js($mindatlas_lib . '/datatables/datatables.min.js', true);
  $PAGE->requires->css($mindatlas_lib . '/datatables/datatables.min.css');
  $PAGE->requires->js($report_dir . '/js/Chartjs/Chart.js', true);
  
  // ---------------------------- setup color for the graphs
  $color_pie = array();
  $color_bar = array();
  $color_courseoverview = array();
  
  $defaultcolor_pie = get_default_colors();
  $defaultcolor_bar = array('0' => '#cbdde6', '1' => '#eeeeee');
  $defaultcolor_courseoverview = array('0' => '#cbdde6', '1' => '#eeeeee');
  
  $pie = $DB->get_record('report_setting', array('name' => 'pie_chart'));
  if (!empty($pie))
    $color_pie = explode(',', $pie->setting);
  else
    $color_pie = $defaultcolor_pie;
  
  $bar = $DB->get_record('report_setting', array('name' => 'bar_chart'));
  if (!empty($bar))
    $color_bar = explode(',', $bar->setting);
  else
    $color_bar = $defaultcolor_bar;
  
  $cour = $DB->get_record('report_setting', array('name' => 'courseoverview_chart'));
  if (!empty($cour))
    $color_courseoverview = explode(',', $cour->setting);
  else
    $color_courseoverview = $defaultcolor_courseoverview;
  
  
  $bgcolor_courseoverview = $color_courseoverview[0];
  $percentage_bgcolor_courseoverview = $color_courseoverview[1];
  
  //$course_completion_diagram_value_totalenrolled = 1000000;
  $bar_chart_figures = array();
  $total_enrolled = 0;
  $total_completed = 0;
  $num_course_chart = 0;
  
  /**
   * get the graph data only for all records based on filters being used
   */
  $tracker = $user_enrolment_class->get_courseoverview_graph_data();
  
  foreach ($tracker as $course_name => $data) {
    $num_course_chart = count(array_keys($tracker));
    if($data['activities'] > 0){
      $bar_chart_figures[] = round($data['completed'] / $data['activities'] * 100, 2);
    } else {
      $bar_chart_figures[] = 0;
    }
    $total_enrolled += $data['activities'];
    $total_completed += $data['completed'];
  }
  $course_completion_diagram_value_string = (implode(',', $bar_chart_figures)) . ',100';
  $course_completion_diagram_value_coursename_string = '"' . implode('","', array_keys($tracker)) . '"';
  
  if($num_course_chart <= 5){
    $bar_graph_width = "400px";
  } else if($num_course_chart > 5 && $num_course_chart <= 10){
    $bar_graph_width = "800px";
  } else {
    $bar_graph_width = "calc(100% - 300px)";
  }

  
  $date_field_script = get_reporting_sort_date_script(array_filter(array_values($DATE_FIELDS)));
  
  $percentage_true = 0;
  $percentage_false = 0;
  if ($total_enrolled != 0) {
    $percentage_true = ($total_completed / $total_enrolled) * 100;
    $percentage_false = (100 - $percentage_true);
  }
  $total_overall_diagram_value['true'] = number_format($percentage_true, 2, '.', '');
  $total_overall_diagram_value['false'] = number_format($percentage_false, 2, '.', '');
  
  
  /**
   * Output report results in HTML
   */
  echo $OUTPUT->header();
  
  //set template for html view
  $rpt->setTemplate('report_courseoverview_hierarchy.html.tpl');
  
  //set smarty params for html view
  $params = array(
    'headers' => $headers, 
    'ajax_url' => $CFG->wwwroot."/blocks/reporting/report/ajax/report.php",
    'filters' => $filter_class->getConcatFilters(),
    'pre_loading_img' => "<img src='".$OUTPUT->image_url('i/loading_small')."' class='report_pre_loading' alt='Loading...'/>",
  
    // For assigning color of graph
    'bgcolor_courseoverview' => $bgcolor_courseoverview,
    'percentage_bgcolor_courseoverview' => $percentage_bgcolor_courseoverview,
  
    'pie_color_completed' =>  $color_pie[0],
    'pie_color_not_completed' => $color_pie[1],
    'pie_highlightcolor_completed' => $color_pie[2],
    'pie_highlightcolor_not_completed' => $color_pie[3],
    'bar_color_completed' => $color_bar[0],
    'bar_color_not_completed' => $color_bar[1],
     // End of assigning color
  
    'course_completion_diagram_value_string' => rtrim($course_completion_diagram_value_string, ","),
    'course_completion_diagram_value_coursename' => rtrim($course_completion_diagram_value_coursename_string, ","),
    'bar_graph_width' => $bar_graph_width,
  
    'total_overall_diagram_value_true' => $total_overall_diagram_value['true'],
    'total_overall_diagram_value_false' => $total_overall_diagram_value['false'],
  );
  
  $rpt->showTemplate($params);
  echo $OUTPUT->footer();
}
