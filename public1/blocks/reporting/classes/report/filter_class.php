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
 * Description of filter_class
 *
 * @author david
 */
class filter_class extends base_class {
  private $filters;
  private $concat_filters;
  private $translation;
  private $_reporting_filters;
  private $_general_filters;
  private $yes_no;
  public function __construct($request_values, $reporting_filters, $general_filters) {
    parent::__construct($request_values);  
    $this->_reporting_filters = $reporting_filters;
    $this->_general_filters = $general_filters;
    $this->init();
  }
  protected function init() {
    $this->translation['suspendedusers'] = array(
      'none' => get_string('exclude_suspended_users', 'block_reporting'),
      'only' => get_string('show_suspended_users_only', 'block_reporting'),
      'all'  => get_string('include_suspended_users', 'block_reporting')
    );
    $this->translation['completionstatus'] = array(
      0 => null,
      1 => 'Not Completed',
      2 => 'Completed'
    );
    $this->translation['yes_no'] = array(
      0 => 'No',
      1 => 'Yes'
    );    
    $this->buildFiltersArray();
    $this->concatFiltersArray(true);
    //error_log($this->concat_filters);
  }
  
  private function hasValue($val) {
    return isset($val) && !empty($val);
  }
  
  public function getConcatFilters() {
    return $this->concat_filters;
  }
  
  private function concatFiltersArray($bold = false) {
    $arr = array();
    foreach ($this->filters as $name => $val) {
      if ($bold) {
        $name = '<b>' . $name . '</b>';
      }
      if (isset($val) && !empty($val)) {
        if (is_array($val)) {
          $connector = ', ';
          if (preg_grep('/\d{2}\/\d{2}\/\d{2,4}$/', $val)) {
            $connector = ' AND ';
          }
          $val = array_map(function($v) {
            return "'" . $v . "'";
          }, $val);
          $arr[] = $name . ': (' . implode($connector, $val) . ')';
        }
        else {
          $arr[] = $name . ": '" . $val . "'";
        }
      }
    }
    $this->concat_filters = implode('<br/>AND ', $arr);
  }
  
  private function buildFiltersArray() {

    // courses
    $key = 'course';
    if (array_key_exists($key, $this->_request_values)
        && $this->hasValue($this->_request_values[$key])) {
      $string = get_string('course_name', 'block_reporting');
      $course_string = get_string('course', 'block_reporting');
      $category_string = get_string('category', 'block_reporting');
      $courseids = preg_grep('/^(\d+)$/', $this->_request_values['course']);
      $categoryids = preg_replace('/\{category\}/', '', preg_grep('/^\{category\}(\d+)$/', $this->_request_values[$key]));
      $courses = $this->getCourseNames($courseids);
      $categories = $this->getCategoryNames($categoryids);
      
      $course_filter = array();
      if (isset($courses) && !empty($courses)) {
        $course_filter[] = $course_string . " = ['" . implode(', ', $courses) . "']";
      }
      if (isset($categories) && !empty($categories)) {
        $course_filter[] = $category_string . " = ['" . implode(', ', $categories) . "']";
      }
      $this->filters[$string] = implode(' OR ', $course_filter);
        
    }
    
    // completion status
    $key = 'completionstatus';
    if (array_key_exists($key, $this->_request_values)
        && $this->hasValue($this->_request_values[$key])) {
      $string = get_string('completionmenuitem', 'core_completion');
      $this->filters[$string] = $this->translation[$key][$this->_request_values[$key]];
    }
    
    // enrolled date
    $string = get_string('enrolled_date', 'block_reporting');
    $val = $this->getDateFiltersValue('enrolleddate', '_from');
    if (isset($val)) {
      $this->filters[$string][] = '>= ' . $val;
    }
    $val = $this->getDateFiltersValue('enrolleddate', '_to');
    if (isset($val)) {
      $this->filters[$string][] = '<= ' . $val;
    }

    // completion date
    $string = get_string('completion_date', 'block_reporting');
    $val = $this->getDateFiltersValue('completiondate', '_from');
    if (isset($val)) {
      $this->filters[$string][] = '>= ' . $val;
    }
    $val = $this->getDateFiltersValue('completiondate', '_to');
    if (isset($val)) {
      $this->filters[$string][] = '<= ' . $val;
    }

    // selectednodes
    $key = 'selectednodenames';
    if (array_key_exists($key, $this->_request_values)
        && $this->hasValue($this->_request_values[$key])) {
      $string = get_string('hierarchy', 'block_reporting');
      $this->filters[$string] = $this->_request_values[$key];
    }    

    // all reporting filters
    foreach ($this->_reporting_filters as $id => $filter) {
      
      if ($filter->datatype === 'datetime') {
        $val = $this->getDateFiltersValue($filter->filtername, '_from');
        if (isset($val)) {
          $this->filters[$filter->name][] = '>= ' . $val;
        }
        $val = $this->getDateFiltersValue($filter->filtername, '_to');
        if (isset($val)) {
          $this->filters[$filter->name][] = '<= ' . $val;
        }
      }
      else if ($filter->datatype === 'checkbox'
               && array_key_exists($filter->filtername, $this->_request_values)) {
        $val = $this->getYesNo($this->_request_values[$filter->filtername][0]);
        if (isset($val)) {
          $this->filters[$filter->name] = $val;
        }
      }
      else if (array_key_exists($filter->filtername, $this->_request_values)) {
        if (isset($this->_request_values[$filter->filtername])
            && !empty($this->_request_values[$filter->filtername])) {
          $this->filters[$filter->name] = $this->_request_values[$filter->filtername];
        }
      }
    }

    // all general filters
    $skip_list = array('username', 'email');
    foreach ($this->_general_filters as $id => $filter) {
      // skip username and email by default
      if (in_array($filter->filtername, $skip_list)) {
        continue;
      }
      
      // lastaccess
      if ($filter->filtername === 'lastaccess') {
        $val = $this->getDateFiltersValue($filter->filtername, '_from');
        if (isset($val)) {
          $this->filters[$filter->name][] = '>= ' . $val;
        }
        $val = $this->getDateFiltersValue($filter->filtername, '_to');
        if (isset($val)) {
          $this->filters[$filter->name][] = '<= ' . $val;
        }    
      }
      else if (array_key_exists($filter->filtername, $this->_request_values)) {
        $this->filters[$filter->name] = $this->_request_values[$filter->filtername];
      }
    }

    // suspendedusers
    $key = 'suspendedusers';
    if (array_key_exists($key, $this->_request_values)) {
      $string = get_string('suspended_users', 'block_reporting');
      $this->filters[$string] = $this->translation[$key][$this->_request_values[$key]];
    }
  }
  
  private function getYesNo($val = null) {
    if (!isset($val) || empty($val)) {
      return null;
    }
    return $this->translation['yes_no'][$val];
  }
  
  private function getDateFiltersValue($filtername, $suffix) {
    $key = $filtername . $suffix;
    if (array_key_exists($key, $this->_request_values)
        && !empty($this->_request_values[$key])) {
      return $this->_request_values[$key];
    }
    return null;
  }

  private function getCategoryNames($categoryids) {
    global $DB;
    if (empty($categoryids)) {
      return null;
    }
    list($cond, $params) = $DB->get_in_or_equal($categoryids);
    $sql = <<< EOT
SELECT
  id, name
FROM
  {course_categories}
WHERE
  id $cond
EOT;
    return $DB->get_records_sql_menu($sql, $params);
  }
  
  private function getCourseNames($courseids) {
    global $DB;
    if (empty($courseids)) {
      return null;
    }
    list($cond, $params) = $DB->get_in_or_equal($courseids);
    $sql = <<< EOT
SELECT
  id, fullname
FROM
  {course}
WHERE
  id $cond
EOT;
    return $DB->get_records_sql_menu($sql, $params);    
  }
}
