<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once($CFG->dirroot . '/config.php');
require_once($CFG->dirroot . '/blocks/reporting/report/smarty/Smarty.class.php');
//require_once($CFG->dirroot . '/lib.php');

/**
 * Description of report_class
 *
 * @author Mindatlas
 */
class report_class_v1 {
  
  private $userFields = array(
    'id'                => 'UID',
    'lastname'          => 'Last Name',
    'firstname'         => 'First Name',
    'firstnamephonetic' => 'Preferred First Name',
    'username'          => 'User Name',
    'email'             => 'Email',
  );

  private $name;
  private $actionPath;
  private $minDate;
  private $template;
  private $smarty;
  private $hideDisplayFields = array();
  private $hideFields;
  private $baseFields;
  private $baseRecords;
  private $userRecords;
  private $profRecords;
  private $baseRecordHeaders;
  private $userRecordHeaders;
  private $profRecordHeaders;
  private $headers;
  private $records;
  private $scripts;
  
  //put your code here
  public function __construct($name, $actionPath) {
    $this->name = $name;
    $this->actionPath = $actionPath;
    $this->init();
  }

  private function init() {
    // initialize smarty
    $this->smarty = new Smarty;
    $this->smarty->compile_dir = get_reporting_compile_folder();
    $this->smarty->template_dir = '../report/template';
    
    $this->setMinDate();
  }
  
  public function getMinDate() {
    return $this->minDate;
  }
  
  public function setMinDate($minDate = null) {
    $this->minDate = $minDate;
    if (is_null($minDate)) {
      global $DB;
      $sql = <<< EOT
select 
  date_format(from_unixtime(min(enrolledate)), '%Y-%m-%d') as min_date 
from {compliance_users}
EOT;
      $rs = $DB->get_record_sql($sql);
      $this->minDate = $rs->min_date;
    }
  }

  public function getHideFields() {
    return $this->hideFields;
  }

  public function setHideFields($hideFields = array()) {
    $this->hideFields = $hideFields;
  }

  public function getUserFields() {
    return $this->userFields;
  }

  public function setUserFields($userFields = null) {
    $this->userFields = $userFields;
  }
  
  public function getBaseFields() {
    return $this->baseFields;
  }

  public function setBaseFields($baseFields = null) {
    $this->baseFields = $baseFields;
  }
  
  public function getTemplate() {
    return $this->template;
  }

  public function setTemplate($template = null) {
    $this->template = $template;
  }
  
  public function showTemplate($params = array()) {
    
    $params = array_merge(array(
      'dateFrom' => $this->minDate
    ), $params);

    foreach ($params as $key => $data) {        
      $this->smarty->assign($key, $data);
    }

    $this->smarty->display($this->template);
  }
  
  public function getReportHeadings() {
    return "<p>Date of report: " .date('d/m/Y') . "</p>";
  }
  
  public function getExportButton($params = array()) {
    $q = '';
    if (!empty($params)) {
      $q = '&' . http_build_query($params);
    }
    error_log($q);
    return 
      '<div class="pull-right">'
      . '<a href="' . $this->actionPath . '?type=CSV' . $q . '" class="export btn">Export CSV</a>'
      . '</div>';
  }

  private function hideFieldsExists() {
    return isset($this->hideFields) 
      && is_array($this->hideFields) 
      && count($this->hideFields) > 0;
  }  
  
  public function generateReportContent($rs) {
//    error_log(var_export($rs, true));
    $this->setBaseRecords($rs);
    
    // get a unique set of user ids
    $userIds = array();
    foreach ($this->baseRecords as $id => $data) {
      if (!in_array($data['UID'], $userIds)) {
        $userIds[] = $data['UID'];
      }
    }

    $this->setUserRecords($userIds);
    $this->setProfRecords($userIds);
    $this->combineRecords();
  }
  
  private function setBaseRecords($records) {
    $baseRecords = array();
    $baseRecordHeaders = array();
    foreach ($records as $id => $record) {
      foreach ($this->baseFields as $fieldName => $fieldDisplayName) {
        $baseRecords[$id][$fieldDisplayName] = $record->$fieldName;
        if (!in_array($fieldDisplayName, $baseRecordHeaders)) {
          $baseRecordHeaders[] = $fieldDisplayName;
        }
      }
    }
    $this->baseRecords = $baseRecords;
    $this->baseRecordHeaders = $baseRecordHeaders;
//    error_log(var_export($this->baseRecords, true));
//    error_log(var_export($this->baseRecordHeaders, true));
  }
  
  public function hideDisplayField($type, $fieldName) {
    $this->hideDisplayFields[$type][$fieldName] = 1;
  }

  private function isVisible($type, $fieldName) {
    if (!empty($this->hideDisplayFields)) {
      if (isset($this->hideDisplayFields[$type][$fieldName])) {
        return false;
      }
    }
    return true;
  }

  private function setUserRecords($userIds = array()) {
    
//    error_log(var_export($this->hideDisplayFields, true));
    
    global $DB;
//    $fieldStr = implode(',', array_keys($this->userFields));
//    $records = $DB->get_records_list('user', 'id', $userIds, null, $fieldStr);
    $records = $DB->get_records_list('user', 'id', $userIds);
    $userRecords = array();
    $userRecordHeaders = array();
    foreach ($records as $uid => $record) {
      if (!isset($userRecords[$uid])) {
        $userRecords[$uid] = array();
      }
      foreach ($this->userFields as $fieldName => $fieldDisplayName) {
        if (isset($record->$fieldName)) {
          if (!$this->isVisible('user', $fieldDisplayName)) {
            continue;
          }
          $userRecords[$uid][$fieldDisplayName] = $record->$fieldName;
          if (!in_array($fieldDisplayName, $userRecordHeaders)) {
            $userRecordHeaders[] = $fieldDisplayName;
          }
        }
      }
    }
    $this->userRecords = $userRecords;
    $this->userRecordHeaders = $userRecordHeaders;
//    error_log(var_export($this->userRecords, true));
//    error_log(var_export($this->userRecordHeaders, true));
  }

  private function setProfRecords($userIds = array()) {
    $sql = <<< EOT
select uid.id, uid.userid, uif.shortname, uif.name, uid.data 
from {user_info_data} uid join {user_info_field} uif on uid.fieldid = uif.id

EOT;
    
    $nameCondition = '';
    if ($this->hideFieldsExists()) {
      $nameCondition = 'uif.shortname not in (' 
        . $this->getPlaceholdersInString($this->hideFields)
        . ')';
    }

    $userCondition = '';
    if (count($userIds) > 0) {
      $userCondition = 'uid.userid in (' 
        . $this->getPlaceholdersInString($userIds)
        . ')';
    }
    
    $params = $this->mergeArray($this->hideFields, $userIds);
    $conditions = implode(' AND ', array_filter(array($nameCondition, $userCondition)));
    
    if ($conditions) {
      $sql .= ' where ' . $conditions;
    }

    $rows = $this->getResultSetSql($sql, $params);

    $profRecords = array();
    $profRecordHeaders = array();
    if (isset($rows)) {
      foreach ($rows as $key => $val) {
        $uid = $val->userid;
        if (!isset($profRecords[$uid])) {
          $profRecords[$uid] = array();
        }
        $profRecords[$uid][$val->name] = $val->data;
        if (!in_array($val->name, $profRecordHeaders)) {
          $profRecordHeaders[] = $val->name;
        }
      }
    }
    $this->profRecords = $profRecords;
    $this->profRecordHeaders = $profRecordHeaders;
//    error_log(var_export($this->profRecords, true));
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
  
  private function filterHeader($type, $header) {
    if (!empty($this->hideDisplayFields)) {
      if (isset($this->hideDisplayFields[$type])) {
        foreach (array_keys($this->hideDisplayFields[$type]) as $fieldName) {
          $header = $this->filterElement($fieldName, $header);
        }
      }
    }
    return $header;
  }
  
  private function filterRecord($type, $record) {
    if (!empty($this->hideDisplayFields)) {
      if (isset($this->hideDisplayFields[$type])) {
        foreach (array_keys($this->hideDisplayFields[$type]) as $fieldName) {
          $record = $this->removeElement($fieldName, $record);
        }
      }
    }    
    return $record;
  }

  private function combineRecords() {
    $this->headers = array_merge(
      $this->filterHeader('base', $this->baseRecordHeaders),
      $this->filterHeader('user', $this->userRecordHeaders),
      $this->filterHeader('prof', $this->profRecordHeaders)
    );

    $records = array();
    foreach ($this->baseRecords as $key => $baseRecord) {
      $uid = $baseRecord['UID'];

      $baseRecord = $this->filterRecord('base', $baseRecord);
      $userRecord = array();
      if (isset($this->userRecords[$uid])) {
        $userRecord = $this->filterRecord('user', $this->userRecords[$uid]);
      }
      $profRecord = array();
      if (isset($this->profRecords[$uid])) {
        $profRecord = $this->filterRecord('prof', $this->profRecords[$uid]);
      }

      $records[] = 
        $this->mergeArray($baseRecord, 
          $this->mergeArray($userRecord, $profRecord)
        );
    }
    $this->records = $records;
  }

  public function getHTMLTable() {
    $html = '<table id="report" class="tablesorter">';
    $html .= '<thead>';
    $html .= '<tr>';
    foreach ($this->headers as $header) {
      $html .= '<th>' . $header . '</th>';
    }
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    foreach ($this->records as $ukey => $record) {
      $html .= '<tr>';
      foreach ($this->headers as $header) {
        $val = '';
        if (isset($record[$header])) {
          $val = $record[$header];
        }
        $html .= '<td>' . $val . '</td>';
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
    fputcsv($file, $this->headers);
    foreach ($this->records as $record) {
      fputcsv($file, $record);
    }
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
  
  public function addScript($name) {
    $script = '';
    switch ($name) {
      case 'tablesorter':
        $script = '
  $(document).ready(function(){
    $("#report").tablesorter({
      headers: {
        //6: { sorter: "customDate" },
        //5: { sorter: "customDate" },
      },
      widgets: ["zebra"]
    });
  });
  $.tablesorter.addParser({
    id: "customDate",
    is: function(s) {
    // return s.match(new RegExp(/^[A-Za-z]{3,10}\.? [0-9]{1,2}, [0-9]{4}|\'?[0-9]{2}$/));
      return false;
    },
    format: function(s) {
      var date = s.split("/");
      return $.tablesorter.formatFloat(new Date(date[2], date[1], date[0]).getTime());
    },
    type: "numeric"
  });';
        break;
      case 'datepicker':
        $script = " 
  $(function () {
    $('.datepicker').datepicker({
      dateFormat: 'dd/mm/yy',
      minDate: new Date('$this->minDate')
    });
  }); ";
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

}
