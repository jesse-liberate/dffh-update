<?php

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/blocks/reporting/report/smarty/Smarty.class.php');
require_once($CFG->dirroot . '/blocks/reporting/report/lib.php');
require_once($CFG->dirroot . '/blocks/reporting/classes/report_class.php');
require_once($CFG->dirroot . '/blocks/reporting/classes/report/ma_constants.php');
// require_once($CFG->dirroot . '/blocks/reporting/classes/report/util_class.php');
// require_once($CFG->dirroot . '/blocks/reporting/classes/report/user_class.php');
// require_once($CFG->dirroot . '/blocks/reporting/classes/report/filter_class.php');
// require_once($CFG->dirroot . '/blocks/reporting/classes/report/course_class.php');
// require_once($CFG->dirroot . '/blocks/reporting/classes/report/course_enrolment_class.php');
// require_once($CFG->dirroot . '/blocks/reporting/classes/report/course_module_class.php');

use block_reporting\report\filter_class;
use block_reporting\report\user_class;
use block_reporting\report\course_class;
use block_reporting\report\course_enrolment_class;
use block_reporting\report\course_module_class;
use block_reporting\report\util_class;

if (!isset($_GET['draw'])) {
  return;
}

// Check login status
require_login(0, false);

$hierarchy_installed = is_hierarchy_installed();

$DATE_FIELDS = array(
  'HireDate' => null,
  'lastaccess' => null
);

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
if (!isset($request_values['ueid'])) {
  $request_values['ueid'] = 1;
}
$is_html = util_class::isHTML($request_values['type']);

error_log('calling from general_backend.php request_values ' . var_export($request_values, true));

// print_object($request_values); 
// die();


$offset = 0;
if (isset($_GET['start'])) {
  $offset = $_GET['start'];
}
$limit = 10;
if (isset($_GET['length'])) {
  $limit = $_GET['length'];
}

// set_time_limit(300);
// raise_memory_limit(MEMORY_HUGE);

//REMOVE ALL DELETED FIELDS TO AVOID ISSUE
remove_deleted_fields();
echo util_class::get_data($is_html, $request_values, $offset, $limit);
// $general_filters      = util_class::getGeneralFilterRecords();
// $reporting_filters    = util_class::getReportingFilterRecords();
// $fieldids             = array_keys($reporting_filters);
// $filter_class         = new filter_class($request_values, $reporting_filters, $general_filters);
// $user_class           = new user_class($request_values, $fieldids, $reporting_filters, $hierarchy_installed);
// $course_class         = new course_class($request_values);
// $course_enrolment_class = new course_enrolment_class($request_values, $user_class, $course_class);
// $course_module_class  = new course_module_class($request_values, $user_class, $course_class);


// $course_enrolments = $course_enrolment_class->getCourseEnrolments($offset, $limit);
// $record_count = $course_enrolment_class->getCount();
// //error_log(var_export($course_enrolments, true));
// //die;

// $rows = array();

// if (count($course_enrolments) > 0) {

//   foreach ($course_enrolments as $course_enrolment) {
//     $userid          = $course_enrolment[0];
//     $courseid        = $course_enrolment[1];
//     $cmid            = $course_enrolment[2];
//     $enrolled_date   = $course_enrolment[3];
//     $completionstate = $course_enrolment[4];
//     $completed_date  = $course_enrolment[5];

//     $row = array();

//     // Column: user id
//     //$default_columns[] = $userid;

//     // Column: user fullname
//     $row[] = $user_class->getUserFullname($userid);

//     // Column: course name
//     $row[] = $course_class->getCourseName($courseid);
    
//     // Column: module
//     $row[] = $course_module_class->getCourseModuleName($cmid);
    
//     // Column: completion
//     $completion = $course_module_class->getUserActivityCompletionStatus($userid, $cmid, $completionstate);
//     $row[] = $completion;
    
//     // Column: enrolled date
//     $row[] = util_class::getformattedDate($enrolled_date);
    
//     // Column: completion date
//     $data = '';
//     if ($completion === MODULE_COMPLETED) {
//       $data = util_class::getformattedDate($completed_date);
//     }
//     $row[] = $data;

//     $custom_columns = array();
//     // Columns from reporting filter
//     foreach ($reporting_filters as $fieldid => $filter) {
//       $data = $user_class->getProfilesData($userid, $fieldid);
//       if ($filter->datatype === 'datetime') {
//         $data = util_class::getformattedDate($data);
//         //$DATE_FIELDS[$filter->filtername] = array_search($filter->name, $headers);
//       }
//       $row[] = $data;
//     }

//     // Columns from general filter
//     foreach ($general_filters as $id => $filter) {
//       $data = $user_class->getUsersData($userid, $filter->filtername);
//       if ($filter->datatype === 'datetime') {
//         $data = util_class::getformattedDate($data);
//         //$DATE_FIELDS[$filter->filtername] = array_search($filter->name, $headers);
//       }
//       $row[] = $data;
//     }
//     $rows[] = $row;
//   }
// }

// //usort($rows, sorter($sort_col, $sort_dir));


// $return_arr = array(
//   'draw'            => $_GET['draw'],
//   'recordsTotal'    => $course_enrolment_class->getCount(),
//   'recordsFiltered' => $course_enrolment_class->getCount(),
//   'data'            => $rows
// );

// echo json_encode($return_arr); 

