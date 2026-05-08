<?php

require_once(__DIR__ . '/constants.php');

/**
 * A convenient method to get plugin config data
 *
 * @param   string  $name  $key
 *
 * @return  mixed
 */
function get_local_config($name) {
  return get_config(ENROLMENTEMAIL_PLUGINNAME, $name);
}

/**
 * A convenient method to set plugin config data
 *
 * @param   string  $name   key
 * @param   string  $value  value
 */
function set_local_config($name, $value) {
  set_config($name, $value, ENROLMENTEMAIL_PLUGINNAME);
}

/**
 * A role context matrix
 *
 * @param   string  $name  role shortname
 *
 * @return  array   role context matrix
 */
function get_role_context_matrix($name) {
  global $DB;
  $sql = <<< EOT
SELECT 
  DISTINCT ra.id, ra.contextid, ra.userid 
FROM 
  mdl_role_assignments ra 
  JOIN mdl_role r ON ra.roleid = r.id AND r.shortname = ?
EOT;
  $records = $DB->get_records_sql($sql, array($name));
  if (!$records) {
    return false;
  }
  
  $matrix = array();
  foreach ($records as $id => $record) {
    $matrix[$record->contextid][$record->userid] = $id;
  }
  return $matrix;
}

/**
 * Get courses in html unordered list format
 * @param array $courseids
 * @return string course list in html unordered list, or empty string if no course records
 */
function get_courselist($courseids) {
  global $CFG, $DB;
  $courselist = '';
  if (isset($courseids) && !empty($courseids)) {
    $coursenames = $DB->get_records_menu('course', array('visible' => 1), '', 'id, fullname');
    $items = array();
    foreach ($courseids as $courseid) {
      $items[] = html_writer::link(
        new moodle_url($CFG->wwwroot . '/course/view.php', array('id' => $courseid)),
        $coursenames[$courseid]
      );
    }
    $courselist = html_writer::alist($items, null, 'ul');
  }

  return $courselist;
}

/**
 * Get html link of site
 * @return string html link e.g. <a href="http://example">Example</a>
 */
function get_sitelink() {
  global $CFG, $SITE;
  return html_writer::link(
    new moodle_url($CFG->wwwroot), $SITE->fullname
  );
}

/**
 * Get user profile link
 * @param object $user
 * @return string html link e.g. <a href="http://example/user/profile?id={$id}">{$firstname}</a>
 */
function get_userprofilelink($user) {
  global $CFG;
  return html_writer::link(
    new moodle_url($CFG->wwwroot . '/user/profile.php', array('id' => $user->id)), $user->firstname
  );
}

/**
 * get course module completion figures
 *
 * @param   int  $courseid  [$courseid description]
 * @param   int  $userid    [$userid description]
 *
 * @return  array  [total_activities, total_completed]
 */
function get_course_modules_completion_figures($courseid, $userid) {
  global $DB;
  $sql = <<< EOT
  SELECT 
    COUNT(cm.instance) as total_activities,
    COUNT(IF(cmc.completionstate IN (?, ?), 1, NULL)) as total_completed
  FROM
    {course_modules} cm 
    LEFT JOIN {course_modules_completion} cmc ON 
      cmc.coursemoduleid = cm.id 
      AND cm.completion != ?
      AND cmc.userid = ?
  WHERE 
    cm.course = ?
EOT;
  $params = array(
    COMPLETION_COMPLETE,
    COMPLETION_COMPLETE_PASS,
    COMPLETION_TRACKING_NONE,
    $userid,
    $courseid
  );
  $result = $DB->get_records_sql($sql, $params);
  if ($result) {
    // error_log(var_export((array)array_values($result)[0], true));
    $values = (array)array_values($result)[0];
    return [$values['total_activities'], $values['total_completed']];
  }
}