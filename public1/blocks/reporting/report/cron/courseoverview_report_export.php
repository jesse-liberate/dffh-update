<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/blocks/reporting/report/smarty/Smarty.class.php');
require_once($CFG->dirroot . '/lib/completionlib.php');
require_once($CFG->dirroot . '/blocks/reporting/report/lib.php');

use block_reporting\report\user_enrolment_class;

global $USER, $DB;

$general_filters   = getGeneralFilterRecords();
$reporting_filters = getReportingFilterRecords();
$users    = getUsers();
$profiles = getProfiles($reporting_filters);
$courses  = getCourses();

$output_folder = $CFG->dataroot . '/reports/courseoverview';
if (!file_exists($output_folder)) {
  mkdir($output_folder, 0777, true);
}

set_time_limit(1200);
raise_memory_limit(MEMORY_HUGE);
process($output_folder);

function getPDO() {
  global $CFG;
  $pdo = new PDO("mysql:host=$CFG->dbhost;dbname=$CFG->dbname", $CFG->dbuser, $CFG->dbpass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  return $pdo;
}

function process($output_folder = '.') {

  $headers = user_enrolment_class::get_courseoverview_headers_csv();
  
  // open file handler
  $tmp_path = tempnam($output_folder, '');
    
  //error_log('tmp_path ' . $tmp_path);
    
  $fp = fopen($tmp_path, 'w');
  
  // write header
  fputcsv($fp, $headers);  

  $sql = <<< EOT
  SELECT 
  ce.courseid,
  ce.fullname, 
  ce.userid,  
  ce.enrolled_date,
  round( (COUNT(tmc.userid)/cmt.total)*100, 2) as percent,
  tmc.completion_date as completion_date 
    
FROM 
(
    SELECT
      ue.userid,
      e.courseid,
      c.fullname,
      MIN(ue.timecreated) as enrolled_date
    FROM
      mdl_user u 
      JOIN mdl_user_enrolments ue ON ue.userid = u.id
      JOIN mdl_enrol e ON ue.enrolid = e.id
      JOIN mdl_course c ON c.id = e.courseid
      WHERE 
        u.deleted <> 1
        AND ue.status = 0
        AND e.status = 0
    GROUP BY
      ue.userid, e.courseid
  ) ce

LEFT JOIN (
  SELECT cm.course, cmc.userid, MAX(cmc.timemodified) as completion_date 
    FROM mdl_course_modules_completion cmc 
    INNER JOIN mdl_course_modules cm ON cm.id = cmc.coursemoduleid AND cm.completion <> 0
    GROUP BY 
      cm.course, cmc.userid 

) tmc ON tmc.course = ce.courseid AND ce.userid = tmc.userid

LEFT JOIN (
 SELECT c.id as courseid, COUNT(cm.id) as total 
 FROM mdl_course_modules cm
 INNER JOIN mdl_course c ON cm.course = c.id
 WHERE cm.visible = 1 AND cm.completion <> 0

 GROUP BY c.id

) as cmt ON cmt.courseid = ce.courseid
    GROUP BY 
      ce.courseid, ce.userid
    LIMIT :start, :offset
EOT;

  $pdo = getPDO();
  $stmt = $pdo->prepare($sql);
  if ($stmt) {
    $stmt->setFetchMode(PDO::FETCH_NUM);
  }

  $start = 0;
  $offset = 300000;
  $fetched = 0;
  $total = 0;
 
  error_log('start');
  do {
    
    $stmt->bindValue(':start' , $start  , PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    error_log('fetching ...');
    $stmt->execute();
    $records = $stmt->fetchAll();

    $fetched = count($records);
        
    error_log('fetched ' . $fetched);
    
    $total += $fetched;
    $rows = getRows($records);
    
    foreach ($rows as $row) {
      fputcsv($fp, $row);
    }
    
    error_log('total written ' . $total);
    
    $start += $offset;

    //error_log(var_export($data, true));
  } while ($fetched > 0 && $fetched == $offset);
  error_log('end');
  //error_log(((time() - $start) / 60) . ' mins');

  fclose($fp);
  
  $filename = 'courseoverview_report_' . date('Ymd_his') . '.csv';
  $file_path = $output_folder . '/' . $filename;
  if (!copy($tmp_path, $file_path)) {
    error_log('Failed to copy ' . $tmp_path . ' to ' . $file_path);
  }
  else {
    unlink($tmp_path);
  }
  
}

function getGeneralFilterRecords() {
  global $DB;
  $records = $DB->get_records('general_filter', array('status' => 'Y'));
  return $records;
}

function getReportingFilterRecords() {
  global $DB;
  $sql = <<< EOT
SELECT 
  f.id, 
  f.shortname, 
  f.name 
FROM 
  mdl_reporting_filter rf JOIN mdl_user_info_field f ON rf.user_info_field_id = f.id 
WHERE
  rf.status = ?
EOT;
  $records = $DB->get_records_sql($sql, array('Y'));
  return $records;
}

function getAdditionalFields() {
  global $reporting_filters, $general_filters;
  $fields = array();
  foreach ($reporting_filters as $id => $record) {
    $fields[$record->shortname] = $record->name;
  }
  foreach ($general_filters as $id => $record) {
    $fields[$record->filtername] = get_string($record->filtername); 
  }
  return $fields;
}

function getRows($records = array()) {
  global $users, $profiles, $courses, $reporting_filters, $general_filters;
   
  $rows = array();
  foreach ($records as $record) {
    $row = array();
    $courseid = $record[0];
    $course = $record[1];
    $userid = $record[2];
    $enrolled_date = $record[3];
    $percent = $record[4];
    $completion_date = $record[5];
    
    // user
    $data = '';
    if (isset($users[$userid])) {
      $data = $users[$userid]->fullname;
    }
    $row[] = $data;
    
    // course
    $row[] = $course;
    
    //completion percent
    $row[] = $percent;
     
    // enrolled date
    $row[] = getFormattedDate($enrolled_date);
    
    // completion date
    $row[] = getFormattedDate($completion_date);
    
    // reporting filters fields
    if (isset($reporting_filters)) {
      foreach ($reporting_filters as $id => $filter) {
        $field = $filter->shortname;
        $key   = $userid . '-' . $id;
        $data = '';
        if (isset($users[$userid]) && isset($profiles[$key])) {
          $data = $profiles[$key]->data;
          if ($profiles[$key]->datatype === 'datetime') {
            $data = getFormattedDate($data);
          }
        }
        $row[] = $data;
      }
    }
    // general filters fields
    if (isset($general_filters)) {
      foreach ($general_filters as $id => $filter) {
        $field = $filter->filtername;
        $data = '';
        if (isset($users[$userid]) && isset($users[$userid]->$field)) {
          $data = $users[$userid]->$field;
          if ($field === 'lastaccess') {
            $data = getFormattedDate($data);
          }
        }
        $row[] = $data;
      }
    }

    //$row[] = getAdditionalFieldsInfo
    
//    /error_log(var_export($row, true));
    $rows[] = $row;
  }
  return $rows;
}

function getFormattedDate($data) {
  if (isset($data) && !empty($data) && (int)$data > 0) {
    return date('d/m/Y', $data);
  }
  return '';
}


function getUsers($conditions = array('deleted' => 0), 
                     $fields = "id, concat(firstname, ' ', lastname) as fullname, username, email, city, country, lastaccess") {
  global $DB;
  //$conditions = mergeConditions($conditions, array('deleted' => 0));
  return $DB->get_records('user', $conditions, '', $fields);
}

function getProfiles($reporting_filters) {
  global $DB;
  if (empty($reporting_filters)) {
    return array();
  }
  list($query, $params) = $DB->get_in_or_equal(array_keys($reporting_filters));
  
  $sql = <<< EOT
SELECT 
  concat(u.id, '-', d.fieldid) as id,
  u.id as userid, 
  d.fieldid,
  d.data,
  f.datatype
FROM 
  mdl_user_info_data d 
  JOIN mdl_user u on u.id = d.userid
  LEFT JOIN mdl_user_info_field f on f.id = d.fieldid 
WHERE 
  d.fieldid $query
  and u.deleted = 0
EOT;
  $records = $DB->get_records_sql($sql, $params);
  return $records;
}

function mergeConditions($conditions, $extra) {
  if (empty($conditions)) {
    $conditions = $extra;
  } else {
    $conditions = array_merge($conditions, $extra);
  }
  return $conditions;
}
