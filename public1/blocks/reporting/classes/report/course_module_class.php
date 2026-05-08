<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace block_reporting\report;


// require_once($CFG->dirroot . '/blocks/reporting/classes/report/util_class.php');
// require_once($CFG->dirroot . '/blocks/reporting/classes/report/db_class.php');
require_once($CFG->dirroot . '/lib/completionlib.php');
// use block_reporting\db_class;
// use block_reporting\util_class;

define('MODULE_COMPLETED', 'Completed');
define('MODULE_NOT_COMPLETED', 'Not Completed');

/**
 * Description of course_module_class
 *
 * @author david
 */
class course_module_class extends base_class {

  private $module_names;
  private $modules;
  private $course_modules;
  private $course_module_ids;
  private $course_modules_completion;
  private $course_activities = array();
  private $user_activities = array();
  private $user_activities_completed;
  private $user_class;
  private $course_class;
  private $scorms;
  private $completiondate_from = 0;
  private $completiondate_to = 0;

  /**
   * @param array $request_values
   * @param \block_reporting\user_class $user_class
   * @param \block_reporting\course_class $course_class
   */  
  public function __construct($request_values, $user_class, $course_class) {
    parent::__construct($request_values);
    $this->user_class = $user_class;
    $this->course_class = $course_class;
    $this->init();
  }
  
  protected function init() { 
    if (isset($this->_request_values['completiondate_from'])) {
      $this->completiondate_from = util_class::getTimestamp($this->_request_values['completiondate_from']);
    }
    if (isset($this->_request_values['completiondate_to'])) {
      $this->completiondate_to  =  util_class::getTimestamp($this->_request_values['completiondate_to']);
    }
    
    $this->setModuleNames();
    $this->setModules();
    $this->setCourseModules();
    $this->setCourseActivities();
    $this->setCourseModulesCompletion();
    $this->setScorms();
    $this->setUserActivities();
    $this->setUserActivitiesCompleted();
  }
  
  private function setModules() {
    global $DB;

    $modules = array();
    foreach ($this->module_names as $id => $name) {
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

      $records = $DB->get_records_sql($sql);
      $modules[$name] = $records;
    }
    $this->modules = $modules;
  }

  public function get_modules_join_query()
  {
    $query = 'LEFT JOIN mdl_modules m ON m.id = cm.module';
    $sql = array();
    foreach($this->module_names as $id=>$name){
      $sql[] = " (SELECT '$name' as act_type, m$id.id as act_id, m$id.name as act_name FROM mdl_$name m$id) ";
    }
    $query .= " LEFT JOIN (". implode(" UNION ", $sql) . ") activity ON m.name = activity.act_type AND activity.act_id = cm.instance "; 
    return $query;
  }
  
  private function setCourseModules() {
    global $DB;
    $conditions = $this->course_class->getSqlConditions('cm.course');
    $params = $this->course_class->getSqlParams();
    if (!empty($conditions)) {
      $conditions = ' AND ' . $conditions;
    }

    $sql = <<< EOT
SELECT
  cm.id,
  m.name as module_type,
  cm.course as courseid,
  cm.instance as module_instance
FROM
  mdl_modules m
  JOIN mdl_course_modules cm ON m.id = cm.module
WHERE
  cm.completion != 0
  $conditions
EOT;
//    error_log($sql);
//    error_log(var_export($params, true));
    
    $this->course_modules = $DB->get_records_sql($sql, $params);
  }
  
  private function setCourseActivities() {
    if ($this->course_modules) {
      $this->course_module_ids = array_keys($this->course_modules);
      foreach ($this->course_modules as $id => $record) {
        $this->course_activities[$record->courseid][] = $id;
      }
    }    
  }

  private function setCourseModulesCompletion() {
    global $DB;

//    $completiondate_from = 0;
//    $completiondate_to = 0;
//    if (isset($this->_request_values['completiondate_from'])) {
//      $completiondate_from = $this->_request_values['completiondate_from'];
//    }
//    if (isset($this->_request_values['completiondate_to'])) {
//      $completiondate_to = $this->_request_values['completiondate_to'];
//    }    
//    list($date_condition, $date_params) = util_class::getDateConditions(
//      'cmc.timemodified', 
//      $completiondate_from,
//      $completiondate_to);
    
    $cm_cond = '';
    $cm_pars = array();
    if (isset($this->course_module_ids)) {
      list($cm_cond, $cm_pars) = $DB->get_in_or_equal($this->course_module_ids);
      $cm_cond = 'cmc.coursemoduleid ' . $cm_cond;
    }

    $conditions = implode(' AND ', array_filter(array($this->user_class->getSqlConditions('cmc.userid'),
                                                      $cm_cond)));
    if (!empty($conditions)) {
      $conditions = ' WHERE ' . $conditions;
    }
    
    $params = array_merge($this->user_class->getSqlParams(), $cm_pars);
 
    $sql = <<< EOT
SELECT
  cmc.userid,
  cmc.coursemoduleid,
  cmc.completionstate,
  cmc.timemodified as completion_date
FROM
  mdl_course_modules_completion cmc
  $conditions
EOT;
    $instance = db_class::getInstance();
    $conn = $instance->getConnection();
    $stmt = $conn->prepare($sql);
    if ($stmt) {
      $stmt->setFetchMode(\PDO::FETCH_NUM);
    }
    $stmt->execute($params);
    $records = $stmt->fetchAll();
    $rows = array();
    foreach ($records as $record) {
      $rows[$record[0]][$record[1]][] = $record[2];
      $rows[$record[0]][$record[1]][] = $record[3];
    }
    $this->course_modules_completion = $rows;
  }
  
  private function setModuleNames($conditions = array('visible' => 1)) {
    global $DB;
    $this->module_names = $DB->get_records_menu('modules', $conditions, '', 'id, name');
  }
  
  public function getModulesData() {
    return $this->modules;
  }
  
  public function getCourseModulesData($userid, $courseid) {
    return $this->course_modules;
  }
  
  public function getCourseModulesCompletionData() {
    return $this->course_modules_completion;
  }

  public function getCourseActivitiesCount($courseid) {
    if (array_key_exists($courseid, $this->course_activities)) {
      return count($this->course_activities[$courseid]);
    }
    return 0;
  }
  
  public function getUserActivitiesCompletedCount($userid, $courseid) {
    if (isset($this->user_activities_completed[$userid][$courseid])) {
      return $this->user_activities_completed[$userid][$courseid];
    }
    return 0;
  }

  /**
   * Function to check if course is completed based on previous class properties settings
   */
  public function isCourseCompleted($userid, $courseid)
  {
	  if (isset($this->course_activities[$courseid])) {
		  $course_activities = $this->course_activities[$courseid];
		  
		  $user_completed_activites = isset($this->course_modules_completion[$userid]) ? array_keys($this->course_modules_completion[$userid]) : array();
		  
		  if(empty(array_diff($course_activities, $user_completed_activites))){
			  return true;
		  } else {
			  return false;
		  }
	  }
	  
  }
  
  public function getCourseCompletionDate($userid, $courseid)
  {  
    //check if course is completed
    if(!$this->isCourseCompleted($userid, $courseid)){
      return '';
    }

    $latest = null;
    if (isset($this->course_activities[$courseid])) {
      foreach ($this->course_activities[$courseid] as $cmid) {
        if (isset($this->course_modules_completion[$userid][$cmid])) {
          $time = $this->course_modules_completion[$userid][$cmid][1];
          if (!isset($latest) || $time > $latest) {
            $latest = $time;
          }
        }
      }
      return $latest;
    }
    return '';
  }
  
  public function getUserActivitiesData($userid, $courseid) {
    if (isset($this->user_activities[$userid][$courseid])) {
      return $this->user_activities[$userid][$courseid];
    }
    return array();
  }
  
  public function inCompletionDateRange($timestamp) {
    return util_class::inDateRange($this->completiondate_from, $this->completiondate_to, $timestamp);
  }
  
  public function isCompletionDateRangeExists() {
    return $this->completiondate_from > 0 || $this->completiondate_to > 0;
  }
  
  private function setUserActivitiesCompleted() {
    foreach ($this->user_activities as $userid => $courses) {
      foreach ($courses as $courseid => $course_modules) {
        foreach ($course_modules as $cmid => $data) {
          if (!isset($this->user_activities_completed[$userid][$courseid])) {
            $this->user_activities_completed[$userid][$courseid] = 0;
          }
          $this->user_activities_completed[$userid][$courseid] += $this->isCompleted($data[1]);
        }
      }
    }
  }
  
  private function setUserActivities() {
    $userids = $this->user_class->getUserIds();
    foreach ($userids as $userid) {
      foreach ($this->course_activities as $courseid => $cmids) {
        foreach ($cmids as $cmid) {
          if (!isset($this->user_activities[$userid][$courseid][$cmid])) {
            $this->user_activities[$userid][$courseid][$cmid] = array();
          }
          
          // course module name
          $course_module = $this->course_modules[$cmid];
          $module = $this->modules[$course_module->module_type][$course_module->module_instance];
          
          // course module status and timestamp
          $status    = MODULE_NOT_COMPLETED;
          $timestamp = 0;
          if (isset($this->course_modules_completion[$userid][$cmid])) {
            $state = $this->course_modules_completion[$userid][$cmid][0];
            $status = $this->getUserActivityCompletionStatus($userid, $cmid, $state);
            $timestamp = $this->course_modules_completion[$userid][$cmid][1];
          }

          $this->user_activities[$userid][$courseid][$cmid][] = $module->name;
          $this->user_activities[$userid][$courseid][$cmid][] = $status;
          $this->user_activities[$userid][$courseid][$cmid][] = $timestamp;
        }
      }
    }
  }
  
  public function getCourseModuleName($cmid) {
    // course module name
    if(isset($this->course_modules[$cmid])){
      $course_module = $this->course_modules[$cmid];
      return $this->modules[$course_module->module_type][$course_module->module_instance]->name;
    } else {
      return '';
    }
  }
  
  public function getUserActivityCompletionStatus($userid, $cmid, $completionstate = null) {
    if (isset($completionstate)) {
      if ($completionstate == COMPLETION_COMPLETE || $completionstate == COMPLETION_COMPLETE_PASS) {
        return MODULE_COMPLETED;
      }
      $module_instance = $this->course_modules[$cmid]->module_instance;
      $key = $userid . '_' . $module_instance;
      if ($this->isScorm($cmid)) {
        if (isset($this->scorms[$key]) && $this->scorms[$key]->value === 'incomplete') {
          return MODULE_NOT_COMPLETED;
        }
      }
    }
    return MODULE_NOT_COMPLETED;
  }
  
  private function isScorm($cmid) {
    if (isset($this->course_modules[$cmid])) {
      return $this->course_modules[$cmid]->module_type === 'scorm';
    }
    return false;
  }

  public function getUserCourseCompletionPercentage($userid, $courseid) {
    
    $total = $this->getCourseActivitiesCount($courseid);
    $completed = 0;
    if (isset($this->user_activities_completed[$userid][$courseid])) {
      $completed = $this->user_activities_completed[$userid][$courseid];
    }
    if ($completed == $total && $completed != 0) {
      return 100;
    }
    else if ($total > 0) {
      return $completed / $total * 100;
    }
    return 0;
  }
  
  private function isCompleted($status) {
    $completed = 0;
    if ($status === MODULE_COMPLETED) {
      $completed = 1;
    }
    return $completed;
  }

  private function setScorms() {
    global $DB;
    $sql = <<< EOT
  SELECT 
    CONCAT(s.userid, '_', s.scormid) as id,
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
    $this->scorms = $DB->get_records_sql($sql);
  }

}
