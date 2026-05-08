<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace block_reporting\report;

// require_once($CFG->dirroot . '/blocks/reporting/classes/report/util_class.php');
// require_once($CFG->dirroot . '/blocks/reporting/classes/report/base_class.php');
// use block_reporting\base_class;
// use block_reporting\util_class;

/**
 * Description of user_class
 *
 * @author david
 */
class user_class extends base_class {
  private $reporting_filters;
  private $hierarchy_installed;
  private $sql_conditions;
  private $sql_params = array();
  private $users;
  private $profiles;
  private $fieldids;
  private $suspended_values = array(
    'none' => 0,
    'only' => 1
  );
  
  /**
   * 
   * @param array $request_values
   * @param array $fieldids
   * @param array $reporting_filters
   * @param bool $hierarchy_installed
   */
  public function __construct($request_values, 
                              $fieldids, 
                              $reporting_filters, 
                              $hierarchy_installed = false) {
    parent::__construct($request_values);
    $this->reporting_filters = $reporting_filters;
    $this->hierarchy_installed = $hierarchy_installed;
    $this->fieldids = $fieldids;
    $this->init();
  }
  
  public function getSqlConditions($field = 'u.id') {
    return str_replace('{x}', $field, $this->sql_conditions);
  }
  
  public function getSqlParams() {
    return $this->sql_params;
  }
  
  public function getUsersData($userid, $filtername) {
    return $this->users[$userid]->$filtername;
  }
  
  public function getProfilesData($userid, $fieldid) {
    return $this->profiles[$userid][$fieldid];
  }
  
  public function getUserFullname($userid) {
    return $this->users[$userid]->fullname;
  }
  
  public function getUsers() {
    return $this->users;
  }
  
  public function getUserIds() {
    return (!empty($this->users)) ? array_keys($this->users) : array();
  }
  
  protected function init() {
    global $DB;  
    
    $hierarchy_cond = ($this->hierarchy_installed) ? $this->getUsersFromHierarchy() : '';
    // var_dump($hierarchy_cond);

    list($suspended_cond, $suspended_params) = $this->getSuspendedConditions();
    
    $access_cond = '';
    $access_params = array();
    if (array_key_exists('lastaccess_from', $this->_request_values) ||
        array_key_exists('lastaccess_to', $this->_request_values)) {
      list($access_cond, $access_params) = util_class::getDateConditions('u.lastaccess',
                                                                    $this->_request_values['lastaccess_from'],
                                                                    $this->_request_values['lastaccess_to']);
    }
    $conditions = implode(' AND ', array_filter(array($hierarchy_cond, 
                                                      $suspended_cond, 
                                                      $access_cond)));
    
    // var_dump($conditions);

    if (!empty($conditions)) {
      $conditions = ' AND ' . $conditions;
    }

    // var_dump($conditions);
    
    $params = array_merge($suspended_params, $access_params);
    
    $users = $this->getUserRecords($conditions, $params, 'id');
    
    $this->profiles = $this->getProfiles(array_keys($users));
    
    if (empty($this->profiles)) {
      // $this->users = null;
      // $this->sql_conditions = "false";
      //return;
      $this->sql_conditions = '';
      $this->sql_params = array();
      $this->users = $this->getUserRecords($this->getSqlConditions(), $this->sql_params);
    } else {
      list($this->sql_conditions, $this->sql_params) = $DB->get_in_or_equal(array_keys($this->profiles));
      $this->sql_conditions = '{x} ' . $this->sql_conditions;
      $this->users = $this->getUserRecords('AND ' . $this->getSqlConditions(), $this->sql_params);
    }
    
  }
  
  private function getSuspendedConditions() {  
    if (isset($this->_request_values['suspendedusers'])) {
      $val = $this->_request_values['suspendedusers'];
      if (isset($this->suspended_values[$val])) {
        return array(
          'u.suspended = ?',
          array($this->suspended_values[$val])
        );
      }
    }
    return array('', array());
  }
  
//  private function getDateConditions($field, $date_from, $date_to) {
//    $conditions = array();
//    $params = array();
//    if (isset($date_from) && !empty($date_from)) {
//      $conditions[] = $field . ' >= ?';
//      $params[] = $this->getTimestamp($date_from);
//    }
//    if (isset($date_to) && !empty($date_from)) {      
//      $conditions[] = $field . ' <= ?';
//      $params[] = $this->getTimestamp($date_to, '23:59:59');
//      
//    }
//    if (!empty($conditions)) {
//      return array(implode(' AND ', $conditions), $params);
//    }
//    return array('', array());
//
//  }  
//  
//  private function getTimestamp($str, $time_format = '00:00:00') {
//    return strtotime(str_replace('/', '-', $str) . ' ' . $time_format);
//  }
  
  private function getUserRecords($conditions, $params, $fields = null) {
    global $DB;
    if (!isset($fields)) {
      $fields = "id, concat(firstname, ' ', lastname) as fullname, username, email, city, country, lastaccess";
    }

    $sql = <<< EOT
  SELECT
    $fields
  FROM
    mdl_user u
  WHERE
    u.deleted = 0
    AND u.username != 'guest'
    $conditions
EOT;

// echo $sql;
// var_dump($params); die();
    return $DB->get_records_sql($sql, $params);
  }

  private function getProfiles($userids = array()) {
    global $DB;
    // $DB->set_debug(true);

    $field_condition = '';
    $user_condition = '';
    $params = array();

    if (!empty($this->fieldids)) {
      list($f_query, $f_params) = $DB->get_in_or_equal($this->fieldids);
      $field_condition = 'AND d.fieldid ' . $f_query;
      $params = array_merge($params, $f_params);
    }
    if (!empty($userids)) {
      list($u_query, $u_params) = $DB->get_in_or_equal($userids);   
      $user_condition = 'AND u.id ' . $u_query;
      $params = array_merge($params, $u_params);
    }

    $sql = <<< EOT
  SELECT 
    CONCAT(u.id, '_', d.fieldid) as id,
    u.id as userid, 
    d.fieldid,
    d.data
  FROM 
    mdl_user u
    LEFT JOIN mdl_user_info_data d ON u.id = d.userid 
    left JOIN mdl_user_info_field f ON f.id = d.fieldid 
  WHERE 
    u.deleted = 0
    $field_condition
    $user_condition
EOT;

// echo $sql;
// var_dump($params); 
    
    $records = $DB->get_records_sql($sql, $params);

    // flatten the records
    $new_records = array();
    foreach ($userids as $userid) {
      foreach ($this->fieldids as $fieldid) {
        $key = $userid . '_' . $fieldid;
        $data = '';
        if (isset($records[$key])) {
          $data = $records[$key]->data;
        }
        if (!isset($new_records[$userid])) {
          $new_records[$userid] = array();
        }
        $new_records[$userid][$fieldid] = $data;
      }
    }    
    return $this->filterProfilesData($new_records);
  }
  
  private function filterProfilesData($profiles) {
    $new_profiles = array();

    //error_log(var_export($reporting_filters, true));

    $filters_used = array();

    foreach ($this->reporting_filters as $fieldid => $filter) {
      $filtername = $filter->filtername;
      if (isset($this->_request_values[$filtername])) {
        $filters_used[$fieldid] = $this->_request_values[$filtername];
      }
      else {
        $key = $filtername . '_from';
        if (!empty($this->_request_values[$key])) {
          $filters_used[$fieldid]['_from'] = util_class::getTimestamp($this->_request_values[$key]);
        }
        $key = $filtername . '_to';
        if (!empty($this->_request_values[$key])) {
          $filters_used[$fieldid]['_to'] = util_class::getTimestamp($this->_request_values[$key], '23:59:59');
        }
      }
    }

//    error_log('$filters_used ' . var_export($filters_used, true));

    if (empty($filters_used)) {
      return $profiles;
    }

    $total_used = count($filters_used);

    foreach ($profiles as $userid => $profile) {
      $count = 0;
      foreach ($filters_used as $fieldid => $req_val) {
        if (!empty($req_val['_from']) || !empty($req_val['_to'])) {
          if ($this->isInDateRange($profile[$fieldid], $req_val['_from'], $req_val['_to'])) {
            $count++;
          }
        }
        else if (array_search($profile[$fieldid], $req_val, true) !== false) {
          $count++;
        }
      }
      if ($total_used == $count) {
        $new_profiles[$userid] = $profile;
      }
    }
    return $new_profiles;
  }
  
  private function isInDateRange($date, $from, $to) {
    if (!isset($date) || empty($date)) {
      return false;
    }
    else {
      $date = (int)$date;
    }
    $in_range = false;
    if (isset($from) && !empty($from)) {
      $in_range = $date >= $from;
    }
    if (isset($to) && !empty($to)) {
      $in_range = $in_range && $date <= $to;
    }
//    error_log(var_export(array($date, $from, $to), true));
//    error_log(var_export($in_range, true));
    
    return $in_range;
  }
  
  private function getUsersFromHierarchy() {
    global $USER;
    $hierarchy_query = null;
    $user_hierarchy_cond = 'false';
    
    if ($this->hierarchy_installed) {
      $selectednodes = get_root_hierarchy($USER->id);
      if (array_key_exists('selectednodes', $this->_request_values)
          && isset($this->_request_values['selectednodes'])
          && !empty($this->_request_values['selectednodes'])) {
        $selectednodes = $this->_request_values['selectednodes'];
      }
      
      $list_hierarchy_users = get_all_users_from_nodes($selectednodes); // List of users has been added to hierarchy condition
      // if there is no user records, the result should be none
      if (empty($list_hierarchy_users)) {
//        echo "There is no results";
//        exit(0);
        $list_hierarchy_users = 'false';
      }
      // echo "<pre>".print_r($list_hierarchy_users,true)."</pre>";
      
      $hierarchy_query = get_hierarchy_query($list_hierarchy_users);
      $user_hierarchy_cond = str_replace(' and ', '', $hierarchy_query['where']);
    }
    return $user_hierarchy_cond;
  }
}
