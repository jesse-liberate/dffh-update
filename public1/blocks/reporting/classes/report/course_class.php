<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace block_reporting\report;

// require_once($CFG->dirroot . '/blocks/reporting/classes/report/base_class.php');
// use block_reporting\base_class;

/**
 * Description of course_class
 *
 * @author david
 */
class course_class extends base_class {
  private $courses;
  private $sql_conditions = '';
  private $sql_params = array();

  /**
   * 
   * @param array $request_values
   */
  public function __construct($request_values) {
//    $keys = array('course');
//    foreach ($keys as $key) {
//      if (isset($request_values[$key])) {
//        $this->$key = $request_values[$key];
//      }
//    }
    parent::__construct($request_values);
    $this->init();
  }
  
  protected function init() {
    list($conditions, $params) = $this->getCourseConditions();
      
    if (!empty($conditions)) {
      $conditions = 'AND ' . $conditions;
    }
    $this->courses = $this->getCourses($conditions, $params);
    
    if (empty($this->courses)) {
      $this->sql_conditions = "false";
      return;
    }
    $this->setSqlConditions();
//    /error_log(var_export($this->courses, true));
  }

  public function getSqlConditions($field = 'c.id') {
    return str_replace('{x}', $field, $this->sql_conditions);
  }
  
  public function getSqlParams() {
    return $this->sql_params;
  }
  
  public function getCoursesData() {
    return $this->courses;
  }
  
  public function getCourseName($courseid) {
    return $this->courses[$courseid];
  }
  
  private function setSqlConditions() {
    global $DB;
    list($this->sql_conditions, $this->sql_params) = $DB->get_in_or_equal(array_keys($this->courses));
    $this->sql_conditions = '{x} ' . $this->sql_conditions;
  }
  
  private function getCourseConditions() {
    global $DB;
    $course_ids = array();
    $category_ids = array();
    $conditions = array();
    $sql_params = array(); 
    if (isset($this->_request_values['course']) && !empty($this->_request_values['course'])) {
      foreach ($this->_request_values['course'] as $val) {
        if (strpos($val, 'category') === false) {
          $course_ids[] = $val;
        }
        else {
          $category_ids[] = trim(str_replace('{category}', '', $val));
        }
      }
      if (!empty($course_ids)) {
        list($query, $params) = $DB->get_in_or_equal($course_ids);
        $conditions[] = 'c.id ' . $query;
        $sql_params = array_merge($sql_params, $params);
      }
      if (!empty($category_ids)) {
        list($query, $params) = $DB->get_in_or_equal($category_ids);
        $conditions[] = 'c.category ' . $query;
        $sql_params = array_merge($sql_params, $params);
      }
      
      return array('(' . implode(' OR ', $conditions) . ')', $sql_params);
    }
  }

  private function getCourses($conditions = '', $params = null) {
    global $DB;
    $sql = <<< EOT
  SELECT DISTINCT
    c.id,
    c.fullname
  FROM
    mdl_course c
  WHERE
    c.visible = 1 
    AND c.fullname != ''
    $conditions
EOT;
    return $DB->get_records_sql_menu($sql, $params);
  }
  
 
  
}
