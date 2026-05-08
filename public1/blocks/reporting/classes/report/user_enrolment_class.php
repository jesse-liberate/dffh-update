<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace block_reporting\report;

/**
 * Description of UserEnrolmentClass
 *
 * @author david
 */
class user_enrolment_class extends base_class {

  private $user_class;
  private $course_class;
  private $user_enrolments;
  private $enrolleddate_from = 0;
  private $enrolleddate_to = 0;
  private $max_limit;
  private $count;
  private $sort_matrix;
  private $sql_params;

  private static $csv_headers;
  

  /**
   * @param array $request_values
   * @param \block_reporting\user_class $user_class
   * @param \block_reporting\course_class $course_class
   * @param int max_limit
   */
  public function __construct($request_values, $user_class, $course_class, $max_limit = 5000) {
    parent::__construct($request_values);
    $this->user_class = $user_class;
    $this->course_class = $course_class;
    $this->max_limit = $max_limit;
    $this->init();
  }

  public static function get_courseoverview_headers_csv()
  {
    self::$csv_headers = array(
      get_string('fullname'),
      get_string('course_name', 'block_reporting'),
      get_string('completedpercentage', 'block_reporting'),
      get_string('enrolled_date', 'block_reporting'),
      get_string('completion_date', 'block_reporting'),
    );

    foreach (util_class::getReportingFilterRecords() as $filter) {
      self::$csv_headers[] = $filter->name;
    }
    foreach (util_class::getGeneralFilterRecords() as $filter) {
      self::$csv_headers[] = $filter->name;
    }

    return self::$csv_headers;
  }


  protected function init() {

    if (isset($this->_request_values['enrolleddate_from'])) {
      $this->enrolleddate_from = $this->_request_values['enrolleddate_from'];
    }
    if (isset($this->_request_values['enrolleddate_to'])) {
      $this->enrolleddate_to  = $this->_request_values['enrolleddate_to'];
    }
    
    if (isset($this->_request_values['completiondate_from'])) {
      $this->completiondate_from = $this->_request_values['completiondate_from'];
    }
    if (isset($this->_request_values['completiondate_to'])) {
      $this->completiondate_to  = $this->_request_values['completiondate_to'];
    }
    
    list($enrolleddate_cond, $enrolleddate_pars) = 
      util_class::getDateConditions('ce.enrolled_date', $this->enrolleddate_from, $this->enrolleddate_to);
    
    $user_cond = $this->user_class->getSqlConditions('u.id');
    $user_pars = $this->user_class->getSqlParams();
    $course_cond = $this->course_class->getSqlConditions('e.courseid');
    $course_pars = $this->course_class->getSqlParams();    
    
    list($completiondate_cond, $completiondate_pars) = 
      util_class::getDateConditions('cmc.timemodified', $this->completiondate_from, $this->completiondate_to);
    
    $this->sql_enrolment_cond = implode(' AND ', array_filter(array(
      $user_cond,
      $course_cond
    )));
    $this->sql_other_cond = implode(' AND ', array_filter(array(
      $enrolleddate_cond,
      $completiondate_cond,
    )));
    
    $this->sql_params = array_merge(
      $user_pars,
      $course_pars,
      $enrolleddate_pars,
      $completiondate_pars
    );

    /**
     * Course review sort matrix for sorting the table
     * Need to work on this to make it dynamic for sorting
     * Currently the table sorting is based on this sort matrix
     */
    $this->sort_matrix = array(
      0 => 'u.firstname asc, u.lastname',
      1 => 'c.fullname asc',
      2 => 'ce.enrolled_date asc',
      3 => 'tmc.completion_date asc',
      4 => 'u.institution asc',
      5 => 'u.department asc',
      6 => 'CAST(uid2.data as UNSIGNED) asc',
      7 => 'uid1.data asc',
      8 => 'u.lastaccess asc'
    );
  }
  
  public function getNextId() {
    return $this->ueid;
  }
  
  public function getCount() {
    return $this->count;
  }

  public function getUserEnrolments($offset = 0, $limit = 5000, $isHtml = true)
  {
    $this->getUserEnrolmentsData($offset, $limit, $isHtml);
    return $this->user_enrolments;
  }

  protected function get_courseoverview_query_fields()
  {
    $fields = <<< EOT
  ce.userid, 
  ce.courseid,  
  ce.enrolled_date, 
  tmc.completion_date as completed_date      
EOT;

    return $fields;
  }

  protected function get_courseoverview_base_query()
  {
    $enrolment_cond = '';
    $other_cond = '';
    if (!empty($this->sql_enrolment_cond)) {
      $enrolment_cond .= 'WHERE '.$this->sql_enrolment_cond;
    }
    if (!empty($this->sql_other_cond)) {
      $other_cond = 'WHERE ' . $this->sql_other_cond;
    }

    $sql = <<< EOT
    SELECT 
      __MA_FIELDS__
    FROM
    (
      SELECT
        ue.userid,
        e.courseid,
        MIN(ue.timecreated) as enrolled_date
      FROM
        mdl_user u 
        JOIN mdl_user_enrolments ue ON ue.userid = u.id
        JOIN mdl_enrol e ON ue.enrolid = e.id
      $enrolment_cond
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

    __MA_JOIN__
    $other_cond
    __MA_ORDERBY__
EOT;

     return $sql; 

  }
  

  private function getUserEnrolmentsData($offset = 0, $limit = 5000, $isHtml = true) {
    $order_by = $this->getOrderByStatement($this->sort_matrix);
    
    $additional_join = <<< EOT
JOIN mdl_user u ON ce.userid = u.id
LEFT JOIN mdl_user_info_data uid1 ON ce.userid = uid1.userid and uid1.fieldid = 14
LEFT JOIN mdl_user_info_data uid2 ON ce.userid = uid2.userid and uid2.fieldid = 8  
LEFT JOIN mdl_course c ON ce.courseid = c.id  
EOT;

    $fields = $this->get_courseoverview_query_fields();
    $sql = $this->get_courseoverview_base_query();

   $sql .= ($isHtml) ? " LIMIT __MA_OFFSET__, ".$limit : '';

    // print_object($query);
    if($isHtml){
      $this->count = $this->get_total_records_count($sql, $this->sql_params);
      // $this->count = $this->fetchRecords($query, $this->sql_params)[0][0];
    }
    
    // 2. fetch the data
    $query = str_replace('__MA_FIELDS__', $fields, $sql);
    $query = str_replace('__MA_OFFSET__', $offset, $query);
    $query = str_replace('__MA_JOIN__', $additional_join, $query);
    $query = str_replace('__MA_ORDERBY__', $order_by, $query);

    // print_object($query); die();
    $this->user_enrolments = $this->fetchRecords($query, $this->sql_params);
    
  }

  public function get_courseoverview_graph_data()
  {
    set_time_limit(600);
    raise_memory_limit(MEMORY_HUGE);
    $course_module_class  = new course_module_class($this->_request_values, $this->user_class, $this->course_class);
    $is_html = util_class::isHTML($this->_request_values['type']);
    $fields = $this->get_courseoverview_query_fields();
    $sql = $this->get_courseoverview_base_query();

    $query = str_replace('__MA_FIELDS__', $fields, $sql);
    $query = str_replace('__MA_JOIN__', '' , $query);
    $query = str_replace('__MA_ORDERBY__', '' , $query);

    $records = $this->fetchRecords($query, $this->sql_params);
    $tracker = array();

    if (count($records) > 0) {
      foreach ($records as $record) {
        $userid = $record[0];
        $courseid = $record[1];
        
        // Column: course name
        $course_name = $this->course_class->getCourseName($courseid);
        if ($is_html) {
          $course_name = shortern_course_name2($course_name);
        }
      
        
        // tracker
        if (!isset($tracker[$course_name])) {
          $tracker[$course_name] = array(
            'activities' => 0,
            'completed' => 0 
          );
        }
        $tracker[$course_name]['activities'] += $course_module_class->getCourseActivitiesCount($courseid);
        $tracker[$course_name]['completed'] += $course_module_class->getUserActivitiesCompletedCount($userid, $courseid);    
        
      }
      //error_log(var_export($rows, true));
    }

    return $tracker;

  }
  
}
