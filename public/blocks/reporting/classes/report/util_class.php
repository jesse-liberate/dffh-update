<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace block_reporting\report;

require_once($CFG->dirroot . '/lib/completionlib.php');
/**
 * Description of util_class
 *
 * @author david
 */
class util_class {
  
  private static $completionstatus_values = array(
    0 => array(),
    1 => array(COMPLETION_INCOMPLETE, COMPLETION_COMPLETE_FAIL),
    2 => array(COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS)
  );

  public static function getDateConditions($field, $date_from, $date_to) {
    $conditions = array();
    $params = array();
    if (isset($date_from) && !empty($date_from)) {
      $conditions[] = $field . ' >= ?';
      $params[] = self::getTimestamp($date_from);
    }
    if (isset($date_to) && !empty($date_from)) {      
      $conditions[] = $field . ' <= ?';
      $params[] = self::getTimestamp($date_to, '23:59:59');
      
    }
    if (!empty($conditions)) {
      return array(implode(' AND ', $conditions), $params);
    }
    return array('', array());

  }  
  
  public static function getTimestamp($str, $time_format = '00:00:00') {
    if (!isset($str) || empty($str)) {
      return 0;
    }
    return strtotime(str_replace('/', '-', $str) . ' ' . $time_format);
  }
  
  public static function getformattedDate($time, $format = 'd/m/Y') {
    if (!is_numeric($time)) {
      return '';
    }
    if (isset($time) && !empty($time) && (int)$time > 0) {
      return date($format, $time);
    }
    return '';
  }  

  
  public static function getGeneralFilterRecords() {
    global $DB;
    $records = $DB->get_records('general_filter', array('status' => 'Y'));
    foreach (array_keys($records) as $id) {
      $records[$id]->name = get_string($records[$id]->filtername);
      $datatype = 'text';
      if ($records[$id]->filtername === 'lastaccess') {
        $datatype = 'datetime';
      }
      $records[$id]->datatype = $datatype;
    }
    return $records;
  }

  public static function getReportingFilterRecords() {
    global $DB;
    $sql = <<< EOT
SELECT 
  f.id, 
  f.shortname as filtername, 
  f.name,
  f.datatype
FROM 
  mdl_reporting_filter rf JOIN mdl_user_info_field f ON rf.user_info_field_id = f.id 
WHERE
  rf.status = ?
EOT;
    $records = $DB->get_records_sql($sql, array('Y'));
    return $records;
  }
  
  public static function getUserFullname($userid, $fullname, $with_url = true) {
    if ($with_url) {
      return \html_writer::link(
        new \moodle_url('/user/profile.php', array('id' => $userid)), 
        $fullname
      );
    }
    return $fullname;
  }
  
  public static function isHTML($type) {
    return $type === 'HTML';
  }
  
  public static function exportCSV($headers, $rows, $filename = null) {
    if (!isset($filename) && empty($filename)) {
      $filename = 'report_' . date('Ymd_His') . '.csv';
    } 
    self::downloadSendHeaders($filename);
    echo self::array2csv($headers);
    echo self::array2csv($rows);
    exit;
  }
  
  private static function downloadSendHeaders($filename) {
    header("Content-type: application/csv");
    header("Content-Disposition: attachment; filename=$filename");
    header("Pragma: no-cache");
    header("Expires: 0");
  }  

  private static function array2csv(array &$rows)
  {
    if (count($rows) == 0) {
      return null;
    }

    ob_start();
    $df = fopen("php://output", 'w');
    if (is_array($rows[0])) {
      foreach ($rows as $row) {
        fputcsv($df, $row);
      }
    }
    else {
      fputcsv($df, $rows);
    }

    fclose($df);
    return ob_get_clean();
  }

  private static function array2csv2(array &$rows, $df)
  {
    if (count($rows) == 0) {
      return null;
    }

    ob_start();
    if (is_array($rows[0])) {
      foreach ($rows as $row) {
        fputcsv($df, $row);
      }
    }
    else {
      fputcsv($df, $rows);
    }

    return ob_get_clean();
  }
  
  public static function inDateRange($timefrom, $timeto, $timestamp) {
    if ($timefrom == 0 && $timeto == 0) {
      return true;
    }
    else if ($timefrom > 0 && $timeto > 0) {
      if ($timestamp >= $timefrom && $timestamp <= $timeto) {
        return true;
      } 
    }
    else if ($timefrom > 0 && $timestamp >= $timefrom) {
      return true;
    }
    else if ($timeto > 0 && $timestamp <= $timeto) {
      return true;
    }
    return false;
  }
  
  public static function getCourseCompletionSqlCondition($val) {
    global $DB;
    $null_condition = 'cmc.completionstate IS NULL';
    $values = self::$completionstatus_values[$val];
    if (!empty($values)) {
      list($conditions, $params) = $DB->get_in_or_equal($values);
      if ($val == 1) {
        $conditions .= ' OR ' . $null_condition;
      }
      return array('(cmc.completionstate ' . $conditions . ')', $params);
    }
    return array('', array());    
  }

  public static function get_headers()
  {
    $headers = array();

    foreach (self::getReportingFilterRecords() as $fieldid => $filter) {
      $headers[] = $filter->name;
    //  $column_types[] = $filter->datatype;
    }
    
    foreach (self::getGeneralFilterRecords() as $id => $filter) {
      $headers[] = $filter->name;
    //  $column_types[] = $filter->datatype;
    }

    return $headers;
  }

  public static function get_data($is_html, $request_values, $offset=0, $limit=0)
  {
    set_time_limit(1200);
    $new_memory = MEMORY_HUGE;
    if($request_values['suspendedusers'] == 'all'){
      $new_memory = '4G';
    }
    // raise_memory_limit(MEMORY_HUGE);
    raise_memory_limit($new_memory);

    $hierarchy_installed = is_hierarchy_installed();

    $general_filters      = util_class::getGeneralFilterRecords();
    $reporting_filters    = util_class::getReportingFilterRecords();
    $fieldids             = array_keys($reporting_filters);
    $filter_class         = new filter_class($request_values, $reporting_filters, $general_filters);
    $user_class           = new user_class($request_values, $fieldids, $reporting_filters, $hierarchy_installed);
    $course_class         = new course_class($request_values);
    // $course_enrolment_class = new course_enrolment_class($request_values, $user_class, $course_class);
    $course_module_class  = new course_module_class($request_values, $user_class, $course_class);

    $return = '';

    switch($request_values['reportname']){
      case 'general':
        $return = self::get_general_report_data($request_values, 
                                                $general_filters, 
                                                $reporting_filters, 
                                                $user_class, 
                                                $course_class, 
                                                $course_module_class, 
                                                $is_html, 
                                                $offset, 
                                                $limit);
      break;

      case 'courseoverview':
        $return = self::get_courseoverview_report_data($request_values, 
                                                       $general_filters, 
                                                       $reporting_filters,
                                                       $user_class, 
                                                       $course_class, 
                                                       $course_module_class, 
                                                       $is_html, 
                                                       $offset, 
                                                       $limit);
      break;
    }

    return $return;
  }

  /**
   * Public static function to process the general report data
   * @param array $request_values, the get parameters list
   * @param array $general_filters
   * @param array $reporting_filters
   * @param object $user_class
   * @param object $course_class
   * @param object $course_module_class
   * @param boolean $is_html
   * @param int $offset
   * @param int $limit
   */
  public static function get_general_report_data($request_values, 
                                                  $general_filters, 
                                                  $reporting_filters, 
                                                  $user_class, 
                                                  $course_class, 
                                                  $course_module_class,
                                                  $is_html,
                                                  $offset,
                                                  $limit)
  {
    $course_enrolment_class = new course_enrolment_class($request_values, $user_class, $course_class);
    $course_enrolments = $course_enrolment_class->getCourseEnrolments($offset, $limit, $is_html, $course_module_class);

    // print_object($course_enrolments); die('here');

    $record_count = $course_enrolment_class->getCount();
    //error_log(var_export($course_enrolments, true));
    //die;

    $rows = array();

    if (count($course_enrolments) > 0) {

      foreach ($course_enrolments as $course_enrolment) {
        $userid          = $course_enrolment[0];
        $courseid        = $course_enrolment[1];
        $cmid            = $course_enrolment[2];
        $enrolled_date   = $course_enrolment[3];
        $completionstate = $course_enrolment[4];
        $completed_date  = $course_enrolment[5];

        $row = array();

        // Column: user id
        //$default_columns[] = $userid;

        // Column: user fullname
        $row[] = $user_class->getUserFullname($userid);

        // Column: course name
        $row[] = $course_class->getCourseName($courseid);
        
        // Column: module
        $row[] = $course_module_class->getCourseModuleName($cmid);
        
        // Column: completion
        $completion = $course_module_class->getUserActivityCompletionStatus($userid, $cmid, $completionstate);
        $row[] = $completion;
        
        // Column: enrolled date
        $row[] = util_class::getformattedDate($enrolled_date);
        
        // Column: completion date
        $data = '';
        if ($completion === MODULE_COMPLETED) {
          $data = util_class::getformattedDate($completed_date);
        }
        $row[] = $data;

        $custom_columns = array();
        // Columns from reporting filter
        foreach ($reporting_filters as $fieldid => $filter) {
          $data = $user_class->getProfilesData($userid, $fieldid);
          if ($filter->datatype === 'datetime') {
            $data = util_class::getformattedDate($data);
            //$DATE_FIELDS[$filter->filtername] = array_search($filter->name, $headers);
          }
          $row[] = $data;
        }

        // Columns from general filter
        foreach ($general_filters as $id => $filter) {
          $data = $user_class->getUsersData($userid, $filter->filtername);
          if ($filter->datatype === 'datetime') {
            $data = util_class::getformattedDate($data);
            //$DATE_FIELDS[$filter->filtername] = array_search($filter->name, $headers);
          }
          $row[] = $data;
        }
        $rows[] = $row;
      }
    }

    //usort($rows, sorter($sort_col, $sort_dir));
    if($is_html){
      if($rows){
        $return_arr = array(
          'draw'            => $_GET['draw'],
          'recordsTotal'    => $course_enrolment_class->getCount(),
          'recordsFiltered' => $course_enrolment_class->getCount(),
          'data'            => $rows
        );
      } else {
        $return_arr = [
          'draw'            => $_GET['draw'],
          'recordsTotal'    => 0,
          'recordsFiltered' => 0,
          'data'            => $rows
        ];
      }

      // print_object($return_arr); die();
  
      return json_encode($return_arr); 

    } else {
      return $rows;
    }

  }

  public static function courseoverview_progressbar($course_name, $percentage)
  {
    $progress_bar = <<< EOT
    <div class="report_progress_bar">
      <div class="report_progress_text" name="{$percentage}">{$course_name} <b>({$percentage}%)</b></div>
      <div class="report_progress_percentage" role="report_progress_bar" style="width:{$percentage}%"></div>
    </div>
EOT;

    return $progress_bar;
  }

  /**
   * Public static function to process the general report data
   * @param array $request_values, the get parameters list
   * @param array $general_filters
   * @param array $reporting_filters
   * @param object $user_class
   * @param object $course_class
   * @param object $course_module_class
   * @param boolean $is_html
   * @param int $offset
   * @param int $limit
   */
  public static function get_courseoverview_report_data(
                                                  $request_values, 
                                                  $general_filters, 
                                                  $reporting_filters, 
                                                  $user_class, 
                                                  $course_class, 
                                                  $course_module_class,
                                                  $is_html,
                                                  $offset,
                                                  $limit
  )
  {

    $user_enrolment_class = new user_enrolment_class($request_values, $user_class, $course_class, $limit);
    $user_enrolments = $user_enrolment_class->getUserEnrolments($offset, $limit, $is_html);

    // $course_enrolment_class = new course_enrolment_class($request_values, $user_class, $course_class);
    // $user_enrolments = $course_enrolment_class->getUserEnrolments($offset, $limit, $is_html);

    $tracker = array();
    $rows = array();
    if (count($user_enrolments) > 0) {
      foreach ($user_enrolments as $user_enrolment) {
        $row = array();
        $userid = $user_enrolment[0];
        $courseid = $user_enrolment[1];
        $enrolled_date = $user_enrolment[2];
        // $completionstate = $user_enrolment[3];
        // $completion_date = $user_enrolment[4];
        
        $percentage = $course_module_class->getUserCourseCompletionPercentage($userid, $courseid);
        
        // Column: user fullname
        $fullname = $user_class->getUserFullname($userid);
        $row[] = util_class::getUserFullname($userid, $fullname, $is_html);

        // Column: course name
        $course_name = $course_class->getCourseName($courseid);
        if ($is_html) {
          $course_name = shortern_course_name2($course_name);
        }
        // $row[] = $course_name;
        
        // course completion percentage
        if (!$is_html) {
          $row[] = $course_name;
          $percentage .= '%';
          $row[] = $percentage;
        } else {
          $row[] = self::courseoverview_progressbar($course_name, $percentage);
        } 
        
        // Column: enrolled date
        $row[] = util_class::getformattedDate($enrolled_date);
        
        // Column: completion date
        $completion_date = $course_module_class->getCourseCompletionDate($userid, $courseid);
        $row[] = util_class::getformattedDate($completion_date);
        
        // Columns from reporting filter
        foreach ($reporting_filters as $fieldid => $filter) {
          $data = $user_class->getProfilesData($userid, $fieldid);
          if ($filter->datatype === 'datetime') {
            $data = util_class::getformattedDate($data);
            // $DATE_FIELDS[$filter->filtername] = array_search($filter->name, $headers);
          }
          $row[] = $data;
        }
        
        // Columns from general filter
        foreach ($general_filters as $id => $filter) {
          $data = $user_class->getUsersData($userid, $filter->filtername);
          if ($filter->filtername === 'lastaccess') {
            $data = util_class::getformattedDate($data);
            // $DATE_FIELDS[$filter->filtername] = array_search($filter->name, $headers);
          }
          $row[] = $data;
        }  

        $rows[] = $row;

        // print_object($row); die();
      }
      //error_log(var_export($rows, true));
    }

    if($is_html){
      $return_arr = array(
        'draw'            => $_GET['draw'],
        'recordsTotal'    => $user_enrolment_class->getCount(),
        'recordsFiltered' => $user_enrolment_class->getCount(),
        'data'            => $rows
      );
  
      return json_encode($return_arr); 

    } else {
      return $rows;
    }

  }

  public static function process_export_csv($headers, $request_values)
  {
    global $CFG;

    $is_html = self::isHTML($request_values['type']);
    if(!$is_html){
      $output_folder = $CFG->dataroot . '/reports';
      if (!file_exists($output_folder)) {
        mkdir($output_folder, 0777);
      }

      $reportType = '';
      switch($request_values['reportname']){
        case 'general':
        case 'courseoverview':
         $reportType = $request_values['reportname'];
         break;
        default:
          $reportType = 'report';
      }

      // die($reportType);

      $filename = $reportType.'_' . date('Ymd_his') . '.csv';
      $file = $output_folder.'/'.$filename;
      // $tmp_path = tempnam($output_folder, '');
      // $fp = fopen($tmp_path, 'w');

      $fp = fopen($file, 'w');

      echo self::array2csv2($headers, $fp);

      // write header
      // fputcsv($fp, $headers); 

      error_log ('start');

      $offset = 0;
      $limit = 200000;
      $fetched = 0;
      $total = 0;
      do{

        $data = util_class::get_data($is_html, $request_values, $offset, $limit);
        $fetched = count($data);
        $total += $fetched;

        echo self::array2csv2($data, $fp);

        error_log('total written ' . $total);
        $offset += $limit;

      } while($fetched > 0 && $fetched == $limit);
      fclose($fp);
      error_log('end');

      // $filename = 'courseoverview_' . date('Ymd_his') . '.csv';
      // $file_path = $output_folder . '/' . $filename;
      // if (!copy($tmp_path, $file_path)) {
      //   error_log('Failed to copy ' . $tmp_path . ' to ' . $file_path);
      // }
      // else {
      //   unlink($tmp_path);
      // }

      self::download_csv($filename);
      exit();
    }

  }

  protected static function download_csv($file)
  {
    global $CFG;

    $file_path = $CFG->dataroot . '/reports/'.$file;
    
    if(file_exists($file_path)){
      header('Content-Description: File Transfer');
      header("Content-type: application/csv");
      header("Content-Disposition: attachment; filename=$file");
      header("Expires: 0");
      header("Pragma: public");
      header('Content-Length: ' . filesize($file_path));
      readfile($file_path);
      unlink($file_path);
    } else{
      redirect(
        '/blocks/reporting/report/courseoverview.php',
        'Cannot find any CSV File. Nothing to download at this moment.',
        null,
        \core\output\notification::NOTIFY_WARNING
      );
    }

  }

}
