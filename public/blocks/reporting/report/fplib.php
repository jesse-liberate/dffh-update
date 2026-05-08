<?php

ini_set('display_errors', true);
error_reporting(E_ALL + E_NOTICE);

function get_get($value) {
  $ret = '';
  if(isset($_GET[$value])) {
	$ret = $_GET[$value];
  }
  return $ret;
}

function get_field_id($name) {
  global $DBH;
  $STH = $DBH->prepare("SELECT id FROM mdl_user_info_field WHERE shortname = :name");
  $STH->execute(array(':name' => $name));
  $data = $STH->fetchall(PDO::FETCH_COLUMN, 0);
  
  if (count($data) != 1) {
	return -1;
  }

  return $data[0];
}

$USERSTATUS_FIELD_ID = get_field_id('Active');

// TODO: Add, employment type, hire date for filtering

function get_subordinate_ids($pos_ids, $max_level = -1, $level = 0) {
  global $DBH, $POS_FIELD_ID, $REPORTS_FIELD_ID;

  $level += 1;
  
  $str_pos_ids = implode(', ', $pos_ids);

  $STH = $DBH->prepare("SELECT DISTINCT d.data FROM mdl_user_info_data as d LEFT JOIN (SELECT data, userid FROM mdl_user_info_data WHERE fieldid = :reportsid_id) as d2 ON d.userid = d2.userid WHERE d.fieldid = :posid_id AND d2.data IN ( $str_pos_ids );");
  $STH->execute(array(':reportsid_id' => $REPORTS_FIELD_ID,
					  ':posid_id' => $POS_FIELD_ID));

  $data = $STH->fetchall(PDO::FETCH_COLUMN, 0);

  if (count($data) > 0 && ($level < $max_level || $max_level == -1)) {
	$data = array_merge($data, get_subordinate_ids($data, $max_level, $level));
  }

  return $data;
}

function get_user_ids($pos_ids) {
  global $DBH, $POS_FIELD_ID;

  $str_pos_ids = implode(', ', $pos_ids);

  $STH = $DBH->prepare("SELECT userid FROM mdl_user_info_data WHERE fieldid = :posid_id AND data IN ( $str_pos_ids );");
  
  $STH->execute(array(':posid_id' => $POS_FIELD_ID));

  $data = $STH->fetchall(PDO::FETCH_COLUMN, 0);

  return $data;
}

function get_user_field_list($field_id) {
  global $DBH;
  $STH = $DBH->prepare('SELECT param1 FROM mdl_user_info_field WHERE id = :field_id;',
					   array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));

  $STH->execute(array(':field_id' => $field_id));
  $data = $STH->fetchall(PDO::FETCH_COLUMN, 0);
  $data = explode("\n", $data[0]);
  array_unshift($data, "");
  return $data;
}

function get_distinct_user_field_list($field_id) {
  global $DBH;
  $STH = $DBH->prepare('SELECT DISTINCT data FROM mdl_user_info_data WHERE fieldid = :field_id AND userid IN (SELECT id FROM mdl_user where deleted=\'0\') ORDER BY data;',
					   array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));

  $STH->execute(array(':field_id' => $field_id));
  $data = $STH->fetchall(PDO::FETCH_COLUMN, 0);
  return $data;
}

function get_user_field_value($user_id, $field_id) {
  global $DBH;
  $STH = $DBH->prepare('SELECT data FROM mdl_user_info_data WHERE fieldid = :field_id AND userid= :user_id;',
					   array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));

  $STH->execute(array(':field_id' => $field_id,
					  ':user_id' => $user_id));
  $data = $STH->fetchall(PDO::FETCH_COLUMN, 0);

  if(count($data) == 0) {
    $ret = '';
  } else {
	$ret = $data[0];
  }

  return $ret;
}

function getCourses() {
  global $DBH;
  $STH = $DBH->prepare("SELECT fullname FROM mdl_course where format='scorm' ORDER BY category, sortorder");
  $STH->execute();
	
  $data = $STH->fetchall(PDO::FETCH_COLUMN, 0);
  return $data;
}

$user_joins = '';
$wheres = '';
$params = array();

function add_info_data_filter($field_id, $data) {
  global $user_joins, $wheres, $params;
  
  if($data && !empty($data)) {
	$join = "LEFT JOIN (
      SELECT userid, data
      FROM mdl_user_info_data
      WHERE mdl_user_info_data.fieldid = %d) as data%d
      ON u.id = data%d.userid\n";
	$user_joins .= sprintf($join, $field_id, $field_id, $field_id);

	$where = "AND data%d.data = :data%d\n";
	$wheres .= sprintf($where, $field_id, $field_id);
	
	$params[':data'.$field_id] = $data;
  }
}

function add_user_id_list($user_ids) {
  global $wheres;
  $where = " AND u.id IN (%s)\n";
  $wheres .= sprintf($where, implode(', ', $user_ids));
}

$reportingquery = "
SELECT u.id as userid, s.id as scormid, s.course as courseid, c.fullname as name, c.sortorder
FROM mdl_user as u
LEFT JOIN

(SELECT userid, enrolid, timestart FROM mdl_user_enrolments) ue
on u.id = ue.userid

LEFT JOIN

(SELECT id, courseid FROM mdl_enrol) e
ON ue.enrolid = e.id


LEFT JOIN

(SELECT id, course, name FROM mdl_scorm) s
ON e.courseid = s.course

LEFT JOIN

(SELECT id, category, fullname, sortorder FROM mdl_course) c
ON s.course = c.id

%s
WHERE u.id = u.id and u.deleted='0' and course!=''
%s
GROUP BY u.id, c.id
ORDER BY c.category, c.sortorder, c.fullname;
";



?>
