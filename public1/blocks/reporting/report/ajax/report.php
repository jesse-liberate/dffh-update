<?php

require_once(__DIR__ . '/../../../../config.php');
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

// print_object($request_values); die();

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

