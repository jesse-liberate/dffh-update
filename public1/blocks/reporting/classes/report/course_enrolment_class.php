<?php

namespace block_reporting\report;

/**
 * Description of course_enrolment_class
 *
 * @author david
 */
class course_enrolment_class extends base_class {

  private $user_class;
  private $course_class;
  private $course_enrolments;
  private $user_enrolments;
  private $enrolleddate_from = 0;
  private $enrolleddate_to = 0;
  private $completiondate_from = 0;
  private $completiondate_to = 0;  
  private $count;
  private $sort_matrix;
  
  private $sql_enrolment_cond;
  private $sql_other_cond;
  private $sql_params;

  /**
   * @param array $request_values
   * @param \block_reporting\user_class $user_class
   * @param \block_reporting\course_class $course_class
   * @param int max_limit
   */
  public function __construct($request_values, $user_class, $course_class) {
    parent::__construct($request_values);
    $this->user_class = $user_class;
    $this->course_class = $course_class;
    $this->init();
  }

  protected function init() {

//    error_log($this->_request_values['enrolleddate_from']);
//    error_log($this->_request_values['enrolleddate_to']);
    
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
    
    //for general report only  
    if(isset($this->_request_values['completionstatus'])){
      list($completionstate_cond, $completionstate_pars) = 
        util_class::getCourseCompletionSqlCondition($this->_request_values['completionstatus']);    
    }  
    
    $this->sql_enrolment_cond = implode(' AND ', array_filter(array(
      $user_cond,
      $course_cond
    )));
    $this->sql_other_cond = implode(' AND ', array_filter(array(
      $enrolleddate_cond,
      $completiondate_cond,
      $completionstate_cond
    )));
    
    $this->sql_params = array_merge(
      $user_pars,
      $course_pars,
      $enrolleddate_pars,
      $completiondate_pars,
      $completionstate_pars
    );
    
    /**
     * General report sort matrix for sorting the table
     * Need to work on this to make it dynamic for sorting
     * Currently the table sorting is based on this sort matrix
     */
    $this->sort_matrix = array(
      0 => 'u.firstname asc, u.lastname',
      1 => 'c.fullname asc',
      2 => 'activity.act_name asc',
      3 => 'cmc.completionstate asc',
      4 => 'ce.enrolled_date asc',
      5 => 'cmc.timemodified asc',
      6 => 'u.institution asc',
      7 => 'u.department asc',
      8 => 'CAST(uid2.data as UNSIGNED) asc',
      9 => 'uid1.data asc',
      10 => 'u.lastaccess asc'
    );
  }
  
  public function getCount() {
    return $this->count;
  }
  
  public function getCourseEnrolments($offset = 0, $limit = 5000, $isHtml, $course_module_obj) {
    error_log(var_export($offset, true));
    error_log(var_export($limit, true));    
    $this->setCourseEnrolments($offset, $limit, $isHtml, $course_module_obj);
    return $this->course_enrolments;
  }
  
  private function setCourseEnrolments($offset = 0, $limit = 5000, $isHtml = true, $course_module_obj = null) {
    
    $order_by = $this->getOrderByStatement($this->sort_matrix);
    
    $enrolment_cond = '';
    $other_cond = '';
    if (!empty($this->sql_enrolment_cond)) {
      $enrolment_cond = 'WHERE ' . $this->sql_enrolment_cond;
    }
    if (!empty($this->sql_other_cond)) {
      $other_cond = 'WHERE ' . $this->sql_other_cond;
    }
    
    $additional_join = <<< EOT
JOIN mdl_user u ON ce.userid = u.id
LEFT JOIN mdl_user_info_data uid1 ON ce.userid = uid1.userid and uid1.fieldid = 14
LEFT JOIN mdl_user_info_data uid2 ON ce.userid = uid2.userid and uid2.fieldid = 8  
LEFT JOIN mdl_course c ON ce.courseid = c.id  
EOT;
if($course_module_obj){
  $additional_join .= $course_module_obj->get_modules_join_query() ;
}
    
    $fields = <<< EOT
  ce.userid, 
  ce.courseid, 
  cm.id, 
  ce.enrolled_date, 
  cmc.completionstate, 
  cmc.timemodified as completed_date      
EOT;

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
LEFT JOIN mdl_course_modules cm ON cm.course = ce.courseid AND cm.completion != 0
LEFT JOIN mdl_course_modules_completion cmc ON cmc.coursemoduleid = cm.id AND ce.userid = cmc.userid
__MA_JOIN__
$other_cond
__MA_ORDERBY__
EOT;

   $sql .= ($isHtml) ? " LIMIT __MA_OFFSET__, ".$limit : '';

  //  echo $sql; 
  //  error_log(var_export($this->sql_params, true));

    // 1. get total record count
    $query = str_replace('__MA_FIELDS__', 'COUNT(*) as total', $sql);
    $query = str_replace('__MA_OFFSET__', 0, $query);
    $query = str_replace('__MA_JOIN__', '', $query);
    $query = str_replace('__MA_ORDERBY__', '', $query);

    // print_object($query);
//    error_log(var_export($query, true));
    $this->count = $this->fetchRecords($query, $this->sql_params)[0][0];
//    error_log('count: ' . $this->count);
    
    // 2. fetch the data
    $query = str_replace('__MA_FIELDS__', $fields, $sql);
    $query = str_replace('__MA_OFFSET__', $offset, $query);
    $query = str_replace('__MA_JOIN__', $additional_join, $query);
    $query = str_replace('__MA_ORDERBY__', $order_by, $query);

    // print_object($query); die();
//    error_log(var_export($query, true));
//    error_log(var_export($this->sql_params, true));
    $this->course_enrolments = $this->fetchRecords($query, $this->sql_params);
    //error_log(var_export($this->course_enrolments, true));
    
  }  
  
}
