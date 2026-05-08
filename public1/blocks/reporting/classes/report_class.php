<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once($CFG->dirroot . '/config.php');
require_once($CFG->dirroot . '/blocks/reporting/report/smarty/Smarty.class.php');
require_once($CFG->dirroot . '/blocks/reporting/report/lib.php');

/**
 * Description of report_class
 *
 * @author Mindatlas
 */
class report_class {
  
  const _ID          = 'id';
  const _PERCENT     = 'percent';
  const _PERCENT_1   = 'percent_1';
  const _PERCENT_2   = 'percent_2';
  const _PERCENT_3   = 'percent_3';
  const _TEXT        = 'text';
  const _DATETIME    = 'datetime'; 
  const _NUM         = 'num';
  const _CHECKBOX    = 'checkbox';
  const _YES_NO      = 'yesno';
  const _MODULE_ACTUAL_NAME = 'module_actual_name';
  const _COURSE_NAME = 'course_name';
  const _BEGINNING_OF_TIME = 100000000;
  
  private $name;
  private $template;
  private $smarty;
  private $scripts;
  private $params;
  private $appliedFormattingToRows;
  private $courses;
  private $modules;
  private $cohorts;
  private $moduleActualNames;
  private $dataSet;  
  private $requestParams;

//  private $appliedFilters;
  
  private $userCols = array(
    'id'                => array('name'=> 'Id'                 ,'type' => self::_NUM, 'hide'=>true),
    'lastname'          => array('name'=> 'Surname'            ,'type' => self::_TEXT),
    'firstname'         => array('name'=> 'First name'         ,'type' => self::_TEXT),
    // 'firstnamephonetic' => array('name'=> 'First name phonetic','type' => self::_TEXT),
    // 'lastnamephonetic'  => array('name'=> 'Last name phonetic' ,'type' => self::_TEXT),
    // 'middlename'        => array('name'=> 'Middle name'        ,'type' => self::_TEXT),
    'alternatename'     => array('name'=> 'Preferred name'     ,'type' => self::_TEXT),
    'username'          => array('name'=> 'Username'           ,'type' => self::_TEXT),
    // 'email'             => array('name'=> 'Email'              ,'type' => self::_TEXT),
    'deleted'           => array('name'=> 'Deleted'            ,'type' => self::_NUM, 'hide'=> true),
    'suspended'         => array('name'=> 'Suspended'          ,'type' => self::_NUM, 'hide' => true)
  );
  private $userRows;
  private $profCols;
  private $profRows;
  private $baseCols;
  private $baseRows;
  private $hideCols;
  private $cols;
  private $rows;

  //put your code here
  public function __construct(
      $name = 'defaultName', 
      $appliedFormattingToRows = false,
      $params = array()) {
    $this->name = $name;
    $this->appliedFormattingToRows = $appliedFormattingToRows;
    $this->params = $params;
    $this->init();
  }

  private function init() {
    // initialize smarty
    $this->smarty = new Smarty;
    $this->smarty->compile_dir = get_reporting_compile_folder();
    $this->smarty->template_dir = '../report/template';
  }
  
  public function getTemplateDir() {
    return $this->smarty->template_dir;
  }
  
  public function setTemplateDir($templateDir) {
    $this->smarty->template_dir = $templateDir;
  }
  
  public function getCourses($assoc = false) {
    if (!isset($this->courses)) {
      $this->setCourses($assoc);
    }
    return $this->courses;
  }
  
  public function setCourses($assoc = false, $fields = 'id, fullname', $sort = 'fullname asc') {
    global $DB;
    $method = 'get_records';
    if ($assoc) {
      $method = 'get_records_menu';
    }
    $this->courses = $DB->$method('course', array('visible' => 1), $sort, $fields);
  }
  
  public function getModules($assoc = false) {
    if (!isset($this->modules)) {
      $this->setModules($assoc);
    }
    return $this->modules;
  }
  
  public function setModules($assoc = false, $fields = 'id, name', $sort = '') {
    global $DB;
    $method = 'get_records';
    if ($assoc) {
      $method = 'get_records_menu';
    }
    $this->modules = $DB->$method('modules', array('visible' => 1), $sort, $fields);
  }
  
  public function getCohorts() {
    if (!isset($this->cohorts)) {
      $this->setCohorts();
    }
    return $this->cohorts;
  }
  
  public function setCohorts($assoc = false, $fields = 'id, name', $sort = 'name asc') {
    global $DB;
    $method = 'get_records';
    if ($assoc) {
      $method = 'get_records_menu';
    }
    $this->cohorts = $DB->$method('cohort', null, $sort, $fields);
  }
  
  public function getModuleNames($assoc = false, $module = null, $fields = 'id, name', $sort = 'name asc') {
    global $DB;
    if (!isset($module)) {
      return null;
    }
    $method = 'get_records';
    if ($assoc) {
      $method = 'get_records_menu';
    }
    return $DB->$method($module , null, $sort, $fields);
  }
  
  public function getModuleActualNames() {
    if (!isset($this->moduleActualNames)) {
      $this->setModuleNamesMap();
    }
    return $this->moduleActualNames;
  }
  
  public function setModuleActualNames($modules = null) {
    
    if (!isset($modules)) {
      $modules = $this->modules;
    }
    $moduleActualNames = array();
    foreach ($modules as $mid => $module) {
      $names = $this->getModuleNames(true, $module);
      if (isset($names) && !empty($names)) {
        foreach ($names as $nameId => $name) {
          $moduleActualNames[$module . '_' . $nameId] = $name;
        }
      }
    }
    $this->moduleActualNames = $moduleActualNames; 
  }
  
//  public function setAppliedFilters($appliedFilters) {
//    $this->appliedFilters = $appliedFilters;
//  }
//  
//  public function getAppliedFilters($toString = false) {
//    $termStr = '';
//    if ($toString) {
//      $termStr = 'Not Specified';
//      $terms = array();
//      foreach ($this->appliedFilters as $name => $val) {
//        if (is_array($val)) {
//          $terms[] = $name . ' = "' . implode(' OR ', $val) . '"';
//        }
//        else {
//          $terms[] = $name . ' = "' . $val . '"';
//        }
//      }
//      $termStr = implode(' AND ', $terms);
//    }
//    return 'Applied Filters: ' . $termStr;
//  }
  
  public function getHideCols() {
    return $this->hideCols;
  }

  public function setHideCols($hideCols = array()) {
    $this->hideCols = $hideCols;
  }

  public function getUserCols() {
    return $this->userCols;
  }

  public function setUserCols($userCols = null) {
    $this->userCols = $userCols;
  }
  
  public function getBaseCols() {
    return $this->baseCols;
  }

  public function setBaseCols($baseCols = null) {
    $this->baseCols = $baseCols;
  }
  
  public function getTemplate() {
    return $this->template;
  }

  public function setTemplate($template = null) {
    $this->template = $template;
  }
  
  public function showTemplate($params = array()) {
    foreach ($params as $key => $data) {        
      $this->smarty->assign($key, $data);
    }

    $this->smarty->display($this->template);
  }
  
  public function getReportHeadings() {
    return "<p>Date of report: " .date('d/m/Y') . "</p>";
  }

  private function hideColsExists() {
    return isset($this->hideCols) 
      && is_array($this->hideCols) 
      && count($this->hideCols) > 0;
  }  
  
  public function generateReportContent($rs, $postFilters = null) {
//    error_log('$rs ' . var_export($rs, true));
    $this->setBaseRows($rs);
    
    if (!isset($this->baseRows)) {
      return;
    }
    
    if (isset($this->dataSet)) {
      // get a unique set of user ids
      $userIds = array();
      foreach ($this->baseRows as $id => $data) {
        if (!in_array($data['userid'], $userIds)) {
          $userIds[] = $data['userid'];
        }
      }
      foreach ($this->dataSet as $name) {
        $funcName = 'set' . ucfirst($name) . 'Rows';
        $this->{$funcName}($userIds);
      }
    }

    $this->combineData($postFilters);
  }
  
  private function convertObjectToArray($object) {
    return json_decode(json_encode($object), true);
  }
  
  private function setBaseRows($records) {
//    error_log(var_export($records, true));
//    $rows = array();
//
//    if (isset($records) && count($records) > 0) {
//      foreach ($records as $id => $record) {
//        //$uid = 'uid_' . $record->userid;
//        if (!isset($rows[$id])) {
//          $rows[$id] = array();
//        }
//        foreach ($this->baseCols as $fieldName => $item) {
//          $rows[$id][$item['name']] = $record->$fieldName;
//        }
//      }
//    }
//    $this->baseRows = $rows;
//    error_log('get_object_vars ' . var_export(json_decode(json_encode($records), true), true));
    if (isset($records) && count($records) > 0) {
      $this->baseRows = $this->convertObjectToArray($records);
    }

//    error_log('baseRows ' . var_export($this->baseRows, true));

  }

  private function setUserRows($userIds = array()) {
    global $DB;
    
    $fieldNames = '';
    if (isset($this->userCols)) {
      $fieldNames = implode(',', array_keys($this->userCols));
    }
    
    $records = $DB->get_records_list('user', 'id', $userIds, null, $fieldNames);
    
//    error_log(var_export($records, true));
//    
//    $rows = array();
//    
//    if (isset($records) && count($records) > 0) {    
//      foreach ($records as $id => $record) {        
//        foreach ($this->userCols as $fieldName => $item) {
//          if (isset($record->$fieldName)) {
//            $rows[$id][$fieldName] = $record->$fieldName;
//          }
//        }
//      }
//    }
    if (isset($records) && count($records) > 0) {
      $this->userRows = $this->convertObjectToArray($records);
    }
//    error_log('userRows ' . var_export($this->userRows, true));
  }
  
  public function getCols() {
    return $this->cols;
  }
  
  public function getRows() {
    return $this->rows;
  }
 

  private function setProfRows($userIds = array()) {
    $sql = <<< EOT
select uid.id, uid.userid, uif.shortname, uif.name, uif.datatype, uid.data 
from {user_info_data} uid join {user_info_field} uif on uid.fieldid = uif.id

EOT;
    
    $nameCondition = '';
    if ($this->hideColsExists()) {
      $nameCondition = 'uif.shortname not in (' 
        . $this->getPlaceholdersInString($this->hideCols)
        . ')';
    }

    $userCondition = '';
    if (count($userIds) > 0) {
      $userCondition = 'uid.userid in (' 
        . $this->getPlaceholdersInString($userIds)
        . ')';
    }
    
    $params = $this->mergeArray($this->hideCols, $userIds);
    $conditions = implode(' AND ', array_filter(array($nameCondition, $userCondition)));
    
    if ($conditions) {
      $sql .= ' where ' . $conditions;
    }

    $records = $this->getResultSetSql($sql, $params);
    
    $rows = array();
    $cols = array();
    if (isset($records) && count($records) > 0) {
      foreach ($records as $id => $record) {
        $uid = $record->userid;
        if (!isset($rows[$uid])) {
          $rows[$uid] = array();
        }
        $rows[$uid][$record->shortname] = $record->data;
        if (!in_array($record->name, $cols)) {
          $cols[$record->shortname]['name'] = $record->name;
          $cols[$record->shortname]['type'] = $record->datatype;
        }
      }
    }

    $this->profRows = $rows;
    $this->profCols = $cols;
    
//    error_log('profRows: ' . var_export($this->profRows, true));
//    error_log('profCols: ' . var_export($this->profCols, true));
  }
  
  
  public function addDataSet($dataSet = null) {
    $this->dataSet = $dataSet;
  }
  
  /**
   * combine all cols and rows
   * @param array $postFilters for filtering unmatched rows
   */
  protected function combineData($postFilters = null) {
    
    $dataSet = array('base');
    
    $cols = array();
    if (isset($this->dataSet)) {
      $dataSet = $this->mergeArray($dataSet, $this->dataSet);
      foreach ($dataSet as $name) {
        $col = $name . 'Cols';
        $this->standardizeCol($this->$col);
        $cols = $this->mergeArray($cols, $this->$col);
      }
    }
    else {
      $cols = $this->baseCols;
    }
    $this->cols = $cols;

    $rows = array();
    if (isset($this->dataSet)) {
      foreach ($this->baseRows as $key => $baseRow) {
        $uid = isset($baseRow['userid']) ? $baseRow['userid'] : 'undefined';

        // merge rows
        $mergedRow = array();
        foreach ($this->dataSet as $name) {
          $row = $name . 'Rows';
          if (isset($this->$row[$uid])) {
            $mergedRow = $this->mergeArray($mergedRow, $this->$row[$uid]);
          }
        }
        
        // apply filters
        $selectRow = true;
        if (isset($postFilters)) {
          $selectRow = false;
          if ($this->matchFilters($mergedRow, $postFilters)) {
            $selectRow = true;
          }
        }

        // merge with base row
        if ($selectRow) {
          $rows[] = $this->mergeArray($baseRow, $mergedRow);
        }
      }
    }
    else {
      $rows = $this->baseRows;
    }

    if ($this->appliedFormattingToRows == true) {
      $formattedRows = array();
      foreach ($rows as $row) {
        $formattedRow = array();
        foreach ($this->cols as $shortname => $col) {
          $val = '';
          if (isset($row[$shortname])) {
            $type = !isset($col['type']) ? $this->getType($row[$shortname]) : $col['type'];
            $val  = $this->getFormat($type, $row[$shortname]);
          }
          $formattedRow[$shortname] = $val;
        }
        $formattedRows[] = $formattedRow;
      }
      $this->rows = $formattedRows;
    }
    else {
      $this->rows = $rows;
    }
    
    

//error_log('combine cols ' . var_export($this->cols, true));
//error_log('combine rows ' . var_export($this->rows, true));
  }
  
  /**
   * Turn date string to unixtime
   * @param type $dateStr
   * @param type $hhmmss default value '00:00:00'
   * @return unixtime
   */
  public function toUnixtime($dateStr = null, $hhmmss = '00:00:00') {
    if (isset($dateStr)) {
      $date = DateTime::createFromFormat('d/m/Y', $dateStr);
      $time = strtotime($date->format('Y-m-d ' . $hhmmss));
      return $time;
      //return strtotime(DateTime::createFromFormat('d/m/Y ' . $hhmmss, $dateStr));
    }
    return null;
  }
  
  /**
   * Check whether date is within range
   * @param int $date unixtime value
   * @param array $arr should be $arr['from'] and/or $arr['to'], both unixtime values
   * @return bool
   */
  private function inDateRange($date, $arr) {
    $result = false;
    if (isset($arr['from']) && isset($arr['to'])) {
      $result = $date >= $arr['from'] && $date <= $arr['to'];
    }
    else if (isset($arr['from'])) {
      $result = $date >= $arr['from'];
    }
    else if (isset($arr['to'])) {
      $result = $date <= $arr['to'];
    }
    return $result;
  }
  
  /**
   * Check if time value exists
   * @param int $time unixtime
   * @return bool
   */
  private function isTimeExist($time) {
    return isset($time) 
      && $time > self::_BEGINNING_OF_TIME
      && !empty($time);
  }
  
  /**
   * check if row matches filters
   * @param array $row
   * @param array $postFilters
   * @return bool
   */
  private function matchFilters($row, $postFilters = null) {
    $matched = false;
    if (isset($postFilters)) {
      $bools = array();
      foreach ($postFilters as $shortName => $val) {
        $longName = $this->cols[$shortName]['name'];
        $type     = $this->cols[$shortName]['type'];
        if (isset($row[$longName])) {
          if ($type === self::_DATETIME) {
            $bools[] = $this->isTimeExist($row[$longName]) 
              && $this->inDateRange($row[$longName], $val);
          }
          else {
            $bools[] = $row[$longName] === $val;
          }
        }
        else {
          $bools[] = false;
        }
      }
      $matched = !in_array(false, $bools);
    }
    return $matched;
  }
  
  private function standardizeCol(&$cols) {
//    error_log(var_export($cols, true));
    foreach ($cols as $name => $item) {
      if (!isset($item)) {
        $item = array();
      }
      if (!isset($item['type']) || empty($item)) {
        $item['type'] = self::_TEXT;
        $cols[$name] = $item;
      }
    }
  }

  private function mergeArray($arr1, $arr2) {
    if (is_array($arr1) && !empty($arr1) && is_array($arr2) && !empty($arr2)) {
      return array_merge($arr1, $arr2);
    } else if (is_array($arr1) && !empty($arr1)) {
      return $arr1;
    } else if (is_array($arr2) && !empty($arr2)) {
      return $arr2;
    } else {
      return array();
    }
  }

  private function getPlaceholdersInString($params) {
    $placeholders = array();
    if (count($params) > 0) {
      for ($i = 0; $i < count($params); $i++) {
        $placeholders[] = '?';
      }
    }
    return implode(',', $placeholders);
  }

  private function filterElement($key, $arr) {
    return array_filter($arr, function($item) use($key) {
      return $item !== $key;
    });
  }

  private function removeElement($key, $arr) {
    $pos = array_search($key, array_keys($arr));
    array_splice($arr, $pos, 1);
    return $arr;
  }
  
  private function isHiddenColumn($col) {
    return isset($col['hide']) && $col['hide'] === true;
  }
  
  private function getFormat($type = null, $data = '') {

    switch ($type) {
      case self::_NUM:
        $data = number_format($data);
        break;
      case self::_DATETIME:
        $data = $data > self::_BEGINNING_OF_TIME ? date('d/m/Y', $data) : '';
        break;
      case self::_CHECKBOX:
        $data = ($data == true) ? 'Yes' : 'No';
        break;
      case self::_YES_NO:
        $data = ($data == true || $data == 1) ? 'Yes' : 'No';
        break;
      case self::_MODULE_ACTUAL_NAME:
        $data = $this->moduleActualNames[$data];
        break;
      case self::_COURSE_NAME:
        $data = $this->courses[$data];
        break;
      case self::_PERCENT:
      case self::_PERCENT_1:
      case self::_PERCENT_2:
      case self::_PERCENT_3:
        $precision = substr($type, -1);
        $precision = is_numeric($precision) ? intval($precision) : 0;
        $data = number_format(round((float)$data * 100), $precision) . '%';
        break;
    }

    return $data;
  }
  
  private function getStyle($type = null) {
    $class = '';
    switch ($type) {
      case self::_NUM:
      case self::_PERCENT:
      case self::_PERCENT_1:
      case self::_PERCENT_2:
      case self::_PERCENT_3:
        $class = 'numeric';
        break;
    }
    return $class;
  }
  
  private function getType($data = null) {
    $type = self::_TEXT;
    $varType = gettype($data);
    switch($varType) {
      case 'integer':
      case 'double':
      case 'float':
        $type = self::_NUM; 
        break;
    }
    return $type;
  }

  public function getHTMLTable() {
    
    $html = '<table id="report" class="report tablesorter">';
    $html .= '<thead>';
    $html .= '<tr>';
    foreach ($this->cols as $key => $col) {
      if (!$this->isHiddenColumn($col)) {
        $html .= '<th>' . $col['name'] . '</th>';
      }
    }
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    foreach ($this->rows as $ukey => $row) {
      $html .= '<tr>';
      foreach ($this->cols as $key => $col) {
        if (!$this->isHiddenColumn($col)) {
//          error_log(var_export($col, true));
//          error_log(var_export($row, true));
          $val   = '';
          $class = '';
          if (isset($row[$col['name']])) {
            $type  = !isset($col['type']) ? $this->getType($row[$col['name']]) : $col['type'];
            $val   = $this->getFormat($type, $row[$col['name']]);
            $class = $this->getStyle($type);
          }
          if ($class != '') {
            $class = 'class="' . $class . '"';
          }
          $html .= '<td ' . $class . '>' . $val . '</td>';
        }
      }
      $html .= '</tr>';
    }
    $html .= '</tbody>';
    $html .= '</table>';

    return $html;
  }

  public function exportCSV($filename = null) {
    if (!$filename) {
      $filename = 'report' . '_' . date('Y_m_d') . ".csv";
    }
    $file = fopen('php://output', 'w');
    header("Content-type: application/csv");
    header("Content-Disposition: attachment; filename=" . $filename);
    header("Pragma: no-cache");
    header("Expires: 0");
    
    $header = array();
    //echo '<pre>'.print_r($this->cols,1).'</pre>';
    foreach ($this->cols as $key => $col) {

      if (!$this->isHiddenColumn($col)) {
        $header[] = $col['name'];
      }
    }
    fputcsv($file, $header);
    foreach ($this->rows as $ukey => $row) {
      //echo '<pre>'.print_r($row,1).'</pre>';
      // echo $row['deleted'];
    if(!$row['deleted']){
      $newRow = array();
      
      foreach ($this->cols as $key => $col) {
        
        if (!$this->isHiddenColumn($col)) {
          $val = '';
          if (isset($row[$key])) {
            // $type  = !isset($col['type']) ? $this->getType($data) : $col['type'];
            $val   = $row[$key];
          }
          $newRow[] = $val;
        }
      }
      fputcsv($file, $newRow);
    }
      //echo '<pre>'.print_r($newRow,1).'</pre>';
      
    }
      //die();
  }

  private function getResultSetSql($sql, $params = array(), $returnType = null) {
    global $DB;
    $result = null;
    if (isset($returnType) && $returnType === 'menu') {
      $result = $DB->get_records_sql_menu($sql, $params);
    } else {
      $result = $DB->get_records_sql($sql, $params);
    }
    return $result;
  }
  
  public function addScript($name, $param = '') {
    $script = '';
    switch ($name) {
      case 'tablesorter':
        $script = "
          $(function(){
            $('#report').tablesorter({
              widgets: ['zebra'],
              $param
            });
          });
          $.tablesorter.addParser({
            id: 'customDate',
            is: function(s) {
            // return s.match(new RegExp(/^[A-Za-z]{3,10}\.? [0-9]{1,2}, [0-9]{4}|\'?[0-9]{2}$/));
              return false;
            },
            format: function(s) {
              var date = s.split('/');
              return $.tablesorter.formatFloat(new Date(date[2], date[1], date[0]).getTime());
            },
            type: 'numeric'
          });";
        break;
      case 'datepicker':
        $script = " 
          $(function () {
            $('.datepicker').datepicker({
              dateFormat: 'dd/mm/yy',
              $param
            });
          });";
        break;
    }
    $this->scripts[] = $script;
  }
  
  public function getScripts() {
    return 
      '<script>' 
      . implode("\n", $this->scripts)
      . '</script>';
  }

  public function getRequestParamValues($requestParams) {
    $arr = array();
    foreach ($requestParams as $name) {
      $val = optional_param($name, null, PARAM_RAW);
      //if (isset($val) && !is_null($val) && $val != '') {
        $arr[$name] = $val;
      //}
    }
    return $arr;
  }
  
  /**
   * check if user is a site admin
   * @param int $userId user id to search. 
   *                    If not specified or null, the default is current login user id
   * @return bool true if user is site admin, false otherwise
   */
  public function isUserSiteAdmin($userId = null) {    
    if (!isset($userId)) {
      global $USER;
      $userId = $USER->id;
    }
    return is_siteadmin($userId);
  }
  
  /**
   * Get a list of users under the manager by manager id
   * @param int $managerId manager id to search. 
   *                       If not specified or null, the default is current login user id
   * @param bool $includeManager should manager id be included in the list too? default is no 
   * @return array Array of user ids under the manager or empty array if none found
   */
  public function getHierachyUserIdsByManagerId($managerId = null, $includeManager = false) {
    if (!isset($managerId)) {
      global $USER;
      $managerId = $USER->id;
    }
    global $DB;
    $current_node_id = $DB->get_field(
      'hierarchy_user',
      'node_id',
      array('user_id' => $managerId)
    );
    $userListInString = get_all_users_from_nodes($current_node_id);
    $users = array();
    if ($userListInString !== '') {
      $users = explode(',', $userListInString);
    }
    if (!$includeManager) {
      $users = $this->removeElement($managerId, $users);
    }
//    error_log('$managerId ' . $managerId);
//    error_log('$users ' . var_export($users, true));
    return $users;
  }
  
  /**
   * Check if user is a manager
   * @param int $userId user id to search. 
   *                    If not specified or null, the default is current login user id
   * @return bool true if user is a manager, false otherwise
   */
  public function isUserManager($userId = null) {
    if (!isset($userId)) {
      global $USER;
      $userId = $USER->id;
    }
    return !empty($this->getHierachyUserIdsByManagerId($userId));
  }
  
  /**
   * Wrapper function. Get a list of users under the manager by manager name
   * @param string $name name of the manager
   * @return array Array of user ids or empty array if none found
   */
  public function getUserIdsByManagerName($name = null) {
     return get_users_for_manager($name);
  }

  public function get_allusers_data(){
    global $DB;
    $users_data = array();
    $users_data = $DB->get_records_select('user', 'id <> 1 AND deleted <> 1 AND suspended <> 1');
    if(!empty($users_data)){
      foreach($users_data as $uid=>$ud){
        $ud->fullname = $ud->firstname.' '.$ud->lastname;
        $users_data[$uid] = $ud;
      }
    }
    return $users_data;
  }
}
