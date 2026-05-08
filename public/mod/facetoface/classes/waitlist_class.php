<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of waitlistClass
 *
 * @author Mindatlas
 */
namespace facetoface;
require_once($CFG->dirroot . '/config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib_mindatlas.php');

class mod_facetoface_waitlist_class {

    private $userFields = array(
        'id'                => 'UID',
        'lastname'          => 'Last Name', 
        'firstname'         => 'First Name', 
        'firstnamephonetic' => 'Preferred First Name',
        'username'          => 'User Name',
        'email'             => 'Email', 
    );
    private $ffSessionFields = array(
        'date_added' => 'Date Added to Waitlist',
        'ff_name'    => 'Face to Face Session',
        'ff_date'    => 'Session Date',
        'ff_time'    => 'Start/Finish Time',
        'userid'     => 'UID'
    );
    private $excludeFields;
    private $DBH;
    private $userRecords;
    private $additionalRecords;
    private $userRecordHeaders;
    private $additionalRecordHeaders;
    private $ffSessionRecords;
    private $ffSessionRecordHeaders;
    private $headers;
    private $records;
    
    //put your code here
    public function __construct() {
        $this->init();
    }
    
    private function init() {
        global $CFG;
//        error_log($CFG->dbname);
        $this->DBH = new \PDO("mysql:host=$CFG->dbhost;dbname=$CFG->dbname", $CFG->dbuser, $CFG->dbpass);
    }
    
    private function getDBH() {
        global $CFG;
        if (!isset($this->DBH)) {
            $this->DBH = new \PDO("mysql:host=$CFG->dbhost;dbname=$CFG->dbname", $CFG->dbuser, $CFG->dbpass);
        }
        return $this->DBH;
    }
    
//    private function resetUserFields() {
//        $this->userFields = $this->prefixElements($this->defaultUserFields, 'u.');
//        if (isset($this->excludeFields) && is_array($this->excludeFields)) {
//            $this->userFields = array_diff($this->userFields, $this->excludeFields);
//        }
//        error_log('userFields');
//        error_log(var_export($this->userFields, true));
//    }
    
//    private function resetAdditionalFields() {
//        $this->additionalFields = $this->prefixElements($this->getUserInfoFields(), 'a.');
//        if (isset($this->excludeFields) && is_array($this->excludeFields)) {
//            $this->additionalFields = array_diff($this->additionalFields, $this->excludeFields);
//        }        
//        error_log('additionalFields');
//        error_log(var_export($this->additionalFields, true));
//    }
    
//    private function prefixElements($arr, $prefix) {
//        $newArray = array();
//        foreach ($arr as $element) {
//            $newArray[] = $prefix . $element;
//        }
//        return $newArray;
//    }
    
    public function getExcludeFields() {
        return $this->excludeFields;
    }
    
    public function setExcludeFields($excludeFields = array()) {
        $this->excludeFields = $excludeFields;
    }
    
    public function getUserFields() {
        return $this->userFields;
    }
    
    public function setUserFields($userFields = null) {
        $this->userFields = $userFields;
    }
    
    private function executeSQL($sql, $params) {
        $DBH = $this->getDBH();
        $STH = $DBH->prepare($sql);
	$STH->execute($params);
        $rows = null;
	if ($STH) {
            $rows = $STH->fetchAll(\PDO::FETCH_ASSOC);
        }
        return $rows;      
    }
    
    /**
     * get courses with face to face activities
     */
//    public function getCourses($returnType) {
//        global $DB;
//        $sql = 'select distinct c.id, c.fullname from mdl_course c join mdl_facetoface ff on ff.course = c.id';
//        $result = $this->getResultSet($sql, array(), $returnType);
//        return $result;
//    }
    
    
    public function getActivities() {
      $sql = <<< EOT
SELECT 
distinct ff.id,
concat(c.fullname, ' / ', ff.name) as session_name
from {facetoface} as ff 
join {course} c on c.id = ff.course 
order by session_name

EOT;
      return $this->getResultSet($sql, null, 'menu');
      
//        $fields = <<< EOT
//            distinct ff.id, 
//            concat(c.fullname, ' / ', ff.name) as 'session_name'
//EOT;
//        return $this->getWaitListedData(array(), $fields, 'menu');
    }
    
    /**
     * get activities with face to face activities
     */
    /*
    public function getActivities($returnType, $courseId = 'all') {
        $sql = "select ff.id, concat(c.fullname, ' / ', ff.name) from mdl_course c join mdl_facetoface ff on ff.course = c.id";
        $where = '';
        $params = array();
        $orderBy = 'order by ff.name ASC';
        if ($courseId !== 'all') {
            $where = 'where c.id = ?';
            $params[] = $courseId;
        }
        $sql = implode(' ', array($sql, $where, $orderBy));
        $result = $this->getResultSet($sql, $params, $returnType);
        return $result;
    }
    */
    
    
    private function getFaceToFaceSessionData($params) {
        $records = $this->getWaitListedData($params);
        $ffSessionRecords = array();
        $ffSessionRecordHeaders = array();
        
        foreach ($records as $ffId => $record) {
            foreach ($this->ffSessionFields as $key => $val) {
                $ffSessionRecords[$ffId][$val] = $record->$key;
                if (!in_array($val, $ffSessionRecordHeaders)) {
                    $ffSessionRecordHeaders[] = $val;
                }
            }
        }
        $this->ffSessionRecords = $ffSessionRecords;
        $this->ffSessionRecordHeaders = $ffSessionRecordHeaders;
//        error_log(var_export($this->ffSessionRecords, true)); 
    }
    
    public function filterRecords($params) {
        
        $this->getFaceToFaceSessionData($params);

        // get a unique set of user ids
        $userIds = array();
        foreach ($this->ffSessionRecords as $id => $data) {
            if (!in_array($data['UID'], $userIds)) {
                $userIds[] = $data['UID'];
            }
        }
        
        $this->getUserRecords($userIds);
        $this->getUserInfoRecords($userIds);
        $this->combineRecords();
    }
    
    public function getWaitListedData(
            $arr = array(), 
            $fields = null, 
            $returnType = null, 
            $userIds = null) {
        $defaultFields = <<< EOT
            distinct concat(ff.id, '_', sup.userid) as ff_uid, 
            sups.waitlist_priority as date_added,
            concat(c.fullname, ' / ', ff.name) as ff_name, 
            sup.userid,
            sessdate.timestart as ff_date, 
            concat(from_unixtime(sessdate.timestart, '%h:%i %p'), ' - ',
            from_unixtime(sessdate.timefinish, '%h:%i %p')) as ff_time
EOT;
        if (is_null($fields)) {
            $fields = $defaultFields;
        }
        
        if (!empty($arr)) {
          if ($arr['activities'] != 0) {
            $arr['ff.id'] = $arr['activities'];
          }
          unset($arr['activities']);
        }
        
        $sql = <<< EOT
select 
$fields
from {facetoface_sessions} sess
join {facetoface_sessions_dates} sessdate on sess.id = sessdate.sessionid 
join {facetoface_session_data} sessdata on sessdate.sessionid = sessdata.sessionid
join {facetoface} ff on ff.id = sess.facetoface
join {course} c on ff.course = c.id
left join {facetoface_signups} sup on sess.id = sup.sessionid
left join {facetoface_signups_status} sups on sup.id = sups.signupid

EOT;

        $where = 'sups.superceded = 0 AND sups.statuscode = 60';
        $params = array();
        if (!empty($arr)) {
            $where = $this->addWhere($where, $arr);
            $params = array_values($arr);
        }
        
        
        if (isset($userIds) && !empty($userIds)) {
          $where .= ' and ' . $this->getSqlInCondition('sup.userid', count($userIds));
          $params = array_merge($params, $userIds);
        }
        
        //$orderBy = '';
        //$groupBy = 'ff.name, sess.id, sessdate.timestart, sessdate.timefinish, u.id, u.username, sups.statuscode';

        if ($where) {
            $sql .= 'where ' . $where;
        }

        error_log($sql);
        error_log(var_export($params, true));
        
        $rs = $this->getResultSet($sql, $params, $returnType);
//        error_log(var_export($rs, true));

        return $rs;
    }
    
    private function excludeFieldsExists() {
        return isset($this->excludeFields) && is_array($this->excludeFields);
    }

    private function getUserRecords($userIds = array()) {
//        error_log(__METHOD__);
        global $DB;
        $fieldStr = implode(',', array_keys($this->userFields));
        $records = $DB->get_records_list('user', 'id', $userIds, null, $fieldStr);
        $userRecords = array();
        $userRecordHeaders = array();
        foreach ($records as $id => $record) {
            $ukey = $id;
            if (!isset($userRecords[$ukey])) {
                $userRecords[$ukey] = array();
            }
            foreach ($this->userFields as $key => $val) {
                if (isset($record->$key)) {
                    $userRecords[$ukey][$val] = $record->$key;
                    if (!in_array($val, $userRecordHeaders)) {
                        $userRecordHeaders[] = $val;
                    }
                }
            }
        }
        $this->userRecords = $userRecords;
        $this->userRecordHeaders = $userRecordHeaders;
//        error_log(var_export($this->userRecords, true));  
    }
    
    private function getUserInfoRecords($userIds = array()) {
        $sql = <<< EOT
select uid.userid, uif.shortname, uif.name, uid.data 
from mdl_user_info_data uid join mdl_user_info_field uif on uid.fieldid = uif.id

EOT;
//        global $DB;
//        $rs = $DB->get_records_sql($sql, array());
//        error_log(var_export($rs, true));
        
        $nameConditions = '';
        if ($this->excludeFieldsExists()) {
            $nameConditions = 'uif.shortname not in (' 
                . $this->getPlaceholdersInString($this->excludeFields)
                . ')';
        }
        $userConditions = '';
        if (count($userIds) > 0) {
            $userConditions = 'uid.userid in ('
                . $this->getPlaceholdersInString($userIds)
                . ')';
        }
        $params = $this->mergeArray($this->excludeFields, $userIds);        
        $conditions = implode(' AND ', array($nameConditions, $userConditions));
//        error_log($conditions);
        if ($conditions) {
            $sql .= 'where ' . $conditions;
        }
        
//        error_log($sql);
//        error_log(var_export($params, true));
//        $rs = $this->getResultSet($sql, $params);
//        error_log(var_export($rs, true));
        
        $rows = $this->executeSQL($sql, $params);
        
//        error_log(var_export($rows, true));
        $additionalRecords = array();
        $additionalRecordHeaders = array();
        if (isset($rows)) {
            foreach ($rows as $row) {
                $ukey = $row['userid'];
                if (!isset($additionalRecords[$ukey])) {
                    $additionalRecords[$ukey] = array();
                }
                $additionalRecords[$ukey][$row['name']] = $row['data'];
                if (!in_array($row['name'], $additionalRecordHeaders)) {
                    $additionalRecordHeaders[] = $row['name'];
                }
            }
        }
        $this->additionalRecords = $additionalRecords;
        $this->additionalRecordHeaders = $additionalRecordHeaders;
//        error_log(var_export($this->additionalRecords, true));
    }
    
    private function mergeArray($arr1, $arr2) {
        if (is_array($arr1) && !empty($arr1) && is_array($arr2) && !empty($arr2)) {
            return array_merge($arr1, $arr2);
        }
        else if (is_array($arr1) && !empty($arr1)) {
            return $arr1;
        }
        else if (is_array($arr2) && !empty($arr2)) {
            return $arr2;
        }
        else {
            return array();
        }
    }
    
    private function getPlaceholdersInString($params) {
        $placeholders = array();
        if (count($params) > 0) {
            for($i=0; $i<count($params); $i++) {
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

    private function combineRecords() {

        $headers = array_merge(
            $this->ffSessionRecordHeaders,
            $this->userRecordHeaders,
            $this->additionalRecordHeaders
        );

        $this->headers = $this->filterElement('UID', $headers);
        
        $records = array();
        foreach ($this->ffSessionRecords as $key => $ffSessionRecord) {
            $uid = $ffSessionRecord['UID'];
            $session = $this->removeElement('UID', $ffSessionRecord);
            $user    = $this->removeElement('UID', $this->userRecords[$uid]);
            $records[] = array_merge(
                $session,
                $user, 
                $this->additionalRecords[$uid]
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
                $html .= '<td>' . $record[$header] . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';
        
        return $html;
    }
    
    public function exportCSV($filename = null) {
        if (!$filename) {
             $filename = "waitlisted_report_".date('Y_m_d').".csv";
        }
        $file = fopen('php://output', 'w');
        header("Content-type: application/csv");
        header("Content-Disposition: attachment; filename=".$filename);
        header("Pragma: no-cache");
        header("Expires: 0");
        fputcsv($file, $this->headers);
        foreach ($this->records as $record) {
            fputcsv($file, $record);
        }
    }
    
    private function getResultSet($sql, $params, $returnType) {
        global $DB;
        $result = null;
        if (isset($returnType) && $returnType === 'menu') {
            $result = $DB->get_records_sql_menu($sql, $params);
        }
        else {
            $result = $DB->get_records_sql($sql, $params);
        }
//        error_log($sql);
//        error_log('getRecords');
//        error_log(var_export($result, true));
        return $result;        
    }

    private function addWhere($where = '', $params = array(), $op = ' AND ') {
        
        $arr = array();
        if ($where) {
            $arr[] = $where;
        }
        if (!empty($params)) {
            foreach ($params as $key => $val) {
                $arr[] = $key . ' = ?';
            }
        }
        return implode($op, $arr);
    }

    private function getSqlInCondition($fieldName, $num = 0) {
  
      if (isset($fieldName) && $num > 0) {
        $arr = array();
        for($i=0; $i<$num; $i++) {
          $arr[] = '?';
        }
        return $fieldName . ' in (' . implode(',', $arr) . ')';
      }
      return '';

    }
}
