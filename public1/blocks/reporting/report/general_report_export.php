<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/blocks/reporting/report/smarty/Smarty.class.php');
require_once($CFG->dirroot . '/lib/completionlib.php');
require_once($CFG->dirroot . '/blocks/reporting/report/lib.php');

global $USER, $DB;

$general_filters   = getGeneralFilterRecords();
$reporting_filters = getReportingFilterRecords();
$users    = getUsers();
$profiles = getProfiles($reporting_filters);
$courses  = getCourses();
$modules  = getModuleDetails();
$scorm    = getScormTrackRecords();
$output_folder = $CFG->dataroot . '/reports/general';
if (!file_exists($output_folder)) {
  mkdir($output_folder, 0777, true);
}

//error_log(var_export($general_filters, true));
//error_log(var_export($reporting_filters, true));
//error_log(var_export($users, true));
//error_log(var_export($profiles, true));
//error_log(var_export($courses, true));
//error_log(var_export($modules, true));
//error_log(var_export($scorm_tracks, true));

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
  
  // prepare header titles
  $headers = array(
    get_string('fullname'),
    get_string('course_name', 'block_reporting'),
    get_string('module', 'block_reporting'),
    get_string('completion', 'block_reporting'),
    get_string('enrolled_date', 'block_reporting'),
    get_string('completion_date', 'block_reporting')
  );
  
  $additional_fields = getAdditionalFields();
  
  if (!empty($additional_fields)) {
    $headers = array_merge($headers, array_values($additional_fields));
  }
  
//  /error_log(var_export($additional_fields, true));
  
  // open file handler
  $tmp_path = tempnam($output_folder, '');
    
  //error_log('tmp_path ' . $tmp_path);
    
  $fp = fopen($tmp_path, 'w');
  
  // write header
  fputcsv($fp, $headers);  

  $sql = <<< EOT
SELECT
  CONCAT(ue.userid, '_', e.courseid, '_', m.id) as id
, ue.userid
, MIN(ue.timecreated) as enrolled_date
, e.courseid
, cm.module as moduleid
, m.name as module_type
, cm.completion as completion_tracking
, cmc.completionstate as completion_status
, cmc.timemodified as completion_date
, cm.instance as module_instance
FROM
mdl_user_enrolments ue
JOIN mdl_enrol e ON e.id = ue.enrolid
LEFT JOIN mdl_course_modules cm ON cm.course = e.courseid AND cm.completion != 0
LEFT JOIN mdl_course_modules_completion cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = ue.userid
LEFT JOIN mdl_modules m ON cm.module = m.id
WHERE
    ue.status = 0
    AND e.status = 0
    AND ue.userid IN (
      SELECT id 
      FROM mdl_user u 
      WHERE u.deleted = 0
    )
GROUP BY
  CONCAT(ue.userid, '_', e.courseid, '_', m.id),
  ue.userid, 
  e.courseid, 
  cm.module, 
  m.name, 
  cm.completion, 
  cmc.completionstate, 
  cmc.timemodified, 
  cm.instance 
ORDER BY
  ue.userid, e.courseid, m.id
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
  //$start = time();
  error_log('start');
  do {
    //error_log('search id >= ' . $id);
    
    $stmt->bindValue(':start' , $start  , PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    error_log('fetching ...');
    $stmt->execute();
    $records = $stmt->fetchAll();
    //$limit += $offset;

    $fetched = count($records);
    
//    if ($fetched > $offset) {
//      $last_record = array_values(array_pop($records));
//      $id = $last_record[0];
//      $fetched--;
//    }
        
    error_log('fetched ' . $fetched);
    
    $total += $fetched;
    $rows = getRows($records, $additional_fields);
    
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
  
  $filename = 'general_' . date('Ymd_his') . '.csv';
  $file_path = $output_folder . '/' . $filename;
  if (!copy($tmp_path, $file_path)) {
    error_log('Failed to copy ' . $tmp_path . ' to ' . $file_path);
  }
  else {
    unlink($tmp_path);
  }
  
//  while ($row = $sth->fetch) {
//    error_log(var_export($row, true));
//  }
  
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
  global $users, $profiles, $modules, $courses, $scorms, $general_filters, $reporting_filters;
   
  $header_indexes = array(
    'id'                  => 0,
    'userid'              => 1,
    'enrolled_date'       => 2,
    'courseid'            => 3,
    'moduleid'            => 4,
    'module_type'         => 5,
    'completion_tracking' => 6,
    'completion_status'   => 7,
    'completion_date'     => 8,
    'module_instance'     => 9
  );
  $rows = array();
  foreach ($records as $record) {
    $row = array();
    
    // user
    $userid = $record[$header_indexes['userid']];
    $data = '';
    if (isset($users[$userid])) {
      $data = $users[$userid]->fullname;
    }
    $row[] = $data;
    
    // course
    $courseid = $record[$header_indexes['courseid']];
    $data = '';
    if (isset($courses[$courseid])) {
      $data = $courses[$courseid];
    }
    $row[] = $data;
    
    // module
    $module_type     = $record[$header_indexes['module_type']];
    $module_instance = $record[$header_indexes['module_instance']];
    $data = '';
    if (isset($modules[$module_type][$module_instance])) {
      $data = $modules[$module_type][$module_instance]->name;
    }
    $row[] = $data;
    
    // completion status
    $data = '';
    if (isset($record[$header_indexes['completion_status']])) {
      $data = $record[$header_indexes['completion_status']];
    }
        
    $status = '';
    //error_log('completion ' . $data);
    //error_log('module_type ' . $module_type);
    if ($data == COMPLETION_COMPLETE || $data == COMPLETION_COMPLETE_PASS) {
      $status = 'module.completed';
    }
    else if ($module_type === 'scorm') {
      $key = $userid . '-' . $module_instance;
      //error_log('$key = ' . $key);
      if (isset($scorms[$key])) {
        $status = 'scorm.' . $scorms[$key]->value;
      }
    }
    //error_log($status);
    $row[] = getCompletionStatusStr($status);
    
    // enrolled date
    $row[] = getFormattedDate($record[$header_indexes['enrolled_date']]);
    
    // completion date
    $row[] = getFormattedDate($record[$header_indexes['completion_date']]);
    
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

function getCompletionStatusStr($data) {
  $str = 'Not Completed';
  switch ($data) {
    case 'module.completed':
    case 'scorm.passed':
    case 'scorm.completed':
      $str = 'Completed';
      break;
    case 'scorm.incomplete':
      $str = 'In Progress';
      break;
  }
  return $str;
}

function getScormDetails() {
  global $DB;
  
}

function getScormTrackRecords() {
  global $DB;
  $sql = <<< EOT
SELECT 
  CONCAT(s.userid, '-', s.scormid) as id,
  s.value,
  s.attempt
FROM 
  {scorm_scoes_track} s
WHERE 
  s.element = 'cmi.core.lesson_status' 
GROUP BY 
  s.userid, 
  s.scormid
ORDER BY 
  s.attempt
EOT;
  return $DB->get_records_sql($sql);
}

function getModuleDetails() {
  global $DB;
  $modules = getModules();
  $modules_details = array();
  foreach ($modules as $id => $module) {
    $name = $module->name;
    $sql = <<< EOT
SELECT 
  DISTINCT 
    m.id as instance, 
    m.name, 
    m.course as courseid
FROM 
  mdl_$name m 
  JOIN {course} c ON m.course = c.id
EOT;
//    error_log($sql);
    $records = $DB->get_records_sql($sql);
    $modules_details[$name] = $records;
  }
  return $modules_details;
}

function getModules($conditions = array('visible' => 1), 
                    $fields = 'id, name') {
  global $DB;
  return $DB->get_records('modules', $conditions, '', $fields);
}

//function getCourses($conditions = array('visible' => 1), $fields = 'id, fullname') {
//  global $DB;
//  //$conditions = mergeConditions($conditions, array('visible' => 1));
//  return $DB->get_records('course', $conditions, '', $fields);
//}

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
