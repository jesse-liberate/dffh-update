<?php 

namespace tool_enrolmentemail;

require_once(__DIR__ . '/../locallib.php');
require_once(__DIR__ . '/../constants.php');

class queue {

  public function __construct() {

  }

  /**
   * fetch data for email
   * Step 1: increment attempts by one for all QUEUED notifications
   * Step 2: get a list of notifications with following conditions:
   *   - notification is in QUEUED status
   *   - course module is visible and is tracking completion is enabled
   * Step 3: apply business rules to filter out selected
   * Step 4: create data structure 1 user to many courses 
   * @return array
   */
  public function fetch_data_for_email() {
    global $DB;

    $all_queued_notifications = $this->fetch_queued_notifications();

    /**
     * Step 1: get a list of notifications with following conditions:
     * 1. notification is in QUEUED status
     * 2. course module is visible and is tracking completion is enabled
     */ 
    $sql = <<< EOT
SELECT
  q.id, q.userid, q.courseid, 
  ec.enabled as notification_enabled, 
  c.visible as course_visible, 
  u.deleted as user_deleted, 
  u.suspended as user_suspended,
  COUNT(cm.instance) as total_activities,
  COUNT(IF(cmc.completionstate IN (1, 2), 1, NULL)) as total_completed
FROM
  mdl_enrolmentemail_queue q
  JOIN mdl_enrolmentemail_courses ec ON q.courseid = ec.courseid
  JOIN mdl_user u ON q.userid = u.id
  JOIN mdl_course c ON q.courseid = c.id
  LEFT JOIN mdl_course_modules cm ON cm.course = c.id
  LEFT JOIN mdl_course_modules_completion cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id
WHERE
  q.status = ?
GROUP BY
  q.id, q.userid, q.courseid, ec.enabled, c.visible, u.deleted, u.suspended
ORDER BY 
  q.userid, 
  q.courseid
EOT;
    $params = array(ENROLMENTEMAIL_STATUS_QUEUED);
    /** @var array */
    $rows = $DB->get_records_sql($sql, $params);

    /** @var array */
    $data = [];

    $matrix = get_role_context_matrix('student');

    // error_log(var_export($rows, true));

    /**
     * Step 2: apply business rules to filter out selected
     */
    foreach ($rows as $id => $row) {
      // error_log(var_export($row, true));
      $context = \context_course::instance($row->courseid);
      $userid = $row->userid;
      $notification = $all_queued_notifications[$id];

      // Business rule 1: skip if user is not a student
      if (!isset($matrix[$context->id][$userid])) {
        $this->log_failed_message($notification, 'Not student');
        continue;
      }
      // error_log('passed rule 1');

      // Business rule 2: skip if course notification is not enabled
      if (!$row->notification_enabled) {
        $this->log_failed_message($notification, 'Notification disabled');
        continue;
      }
      // error_log('passed rule 2');

      // Business rule 3: skip if course is not visible
      if (!$row->course_visible) {
        $this->log_failed_message($notification, 'Course not visible');
        continue;
      }
      // error_log('passed rule 3');

      // Business rule 4: skip if user is deleted
      // User's notifications will be removed from queue when user_deleted event is triggered
      // Just check for precautionary purpose here
      if ($row->user_deleted) {
        $this->log_failed_message($notification, 'User deleted');
        continue;
      }
      // error_log('passed rule 4');
      
      // Business rule 5: skip if user is suspended
      if ($row->user_suspended) {
        $this->log_failed_message($notification, 'User suspended');
        continue;
      }
      // error_log('passed rule 5');

      // Business rule 6: skip if user is not enrolled
      if (!is_enrolled($context, $row->userid, '', true)) {
        $this->log_failed_message($notification, 'User not enrolled');
        continue;
      }
      // error_log('passed rule 6');
      // Business rule 9: is user enrolment deleted?
      // User's notification will be removed from queue when user_enrolment_deleted event is triggered

      // create following structure 1 user to many courses relationship
      // [[user1 => [courseid1, courseid2, ...]], [user2 => [courseid1, courseid2, ...]], ...]
      $data[$row->userid][] = $row->courseid;
    }
    return $data;
  }

  /**
   * A convenient function to log failed message to the notification
   *
   * @param   object  $notification the enrolmentemail_queue erecord
   * @param   string  $message failed message
   */
  public function log_failed_message($notification, $message) {
    global $DB;
    $notification->last_log_msg = $message;
    $DB->update_record('enrolmentemail_queue', $notification);
  }

  /**
   * Just a convenient function to fetch QUEUED notifications
   */
  public function fetch_queued_notifications() {
    global $DB;
    return $this->get_notifications(array('status' => ENROLMENTEMAIL_STATUS_QUEUED));
  }

//   public function DEPRECATED_fetch_queued_records() {
//     global $DB;
//     /** 
//      * STEP 1 - fetch queued records (if it fullfils the following criteria)
//      * 1. user account must be ctive
//      * 2. course must be visible
//      * 3. activity tracking is enabled
//      * 4. activity is visible
//      * 5. total activity > total completed (meaning: user has not completed all required activity)
//      * 6. enrolment notification is currently in "QUEUED" status
//      * 7. course notification is enabled
//      */
//     $sql = <<< EOT
// SELECT
//   q.*,
//   COUNT(cm.instance) as total_activities,
//   COUNT(IF(cmc.completionstate IN (1, 2), 1, NULL)) as total_completed
// FROM
//   mdl_enrolmentemail_queue q
//   JOIN mdl_enrolmentemail_courses ec ON q.courseid = ec.courseid
//   JOIN mdl_user u ON q.userid = u.id
//   JOIN mdl_course c ON q.courseid = c.id AND c.visible = 1
//   LEFT JOIN mdl_course_modules cm ON cm.course = c.id AND cm.visible = 1 AND cm.completion != 0
//   LEFT JOIN mdl_course_modules_completion cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id
// WHERE
//   u.deleted = 0
//   AND ec.enabled = ?
//   AND u.suspended = 0
//   AND q.status = ?
// GROUP BY
//   q.id
// ORDER BY 
//   q.userid, 
//   q.courseid
// EOT;
//     $params = array(
//       ENROLMENTEMAIL_COURSENOTIFICATION_ENABLED,
//       ENROLMENTEMAIL_STATUS_QUEUED
//     );

//     $results = $DB->get_records_sql($sql, $params);

//     $matrix = get_role_context_matrix('student');

//     $records = array();
//     if ($results) {
//       /**
//        * STEP 2 - filter results where
//        * 1. user is still enrolled into the course
//        * 2. user enrolment status is active
//        * 3. user enrolment method (either manual or cohort) is active
//        * 4. user is a student
//        */
//       foreach ($results as $id => $result) {
//         // user has completed all activities, skip
//         // if ($result->total_activities == $result->total_completed && $result->total_activities != 0) {
//         //   $course_completed[] = $result;
//         //   continue;
//         // }
//         $context = \context_course::instance($result->courseid);
//         $userid = $result->userid;
//         // check if user is enrolled into course
//         if (is_enrolled($context, $userid, '', true)) {  
//           // check if user is a student  
//           if (isset($matrix[$context->id][$userid])) {
//              $records[] = $result;
//           }
//         }
//       }
//     }

//     //update_queue_coursecompleted($course_completed);

//     return $records;
//   }

  /**
   * Create notification and put in queue
   * If notification already exists, it will not be re-created
   *
   * @param   int  $userid    userid
   * @param   int  $courseid  courseid
   */
  public function create_notification($userid, $courseid) {
    global $DB;
    // only create notification when it does not exist, we don't care if it's from manual or cohort
    $exists = $DB->record_exists('enrolmentemail_queue', array('userid' => $userid, 'courseid' => $courseid));
    if (!$exists) {
      $notification = new \stdClass();
      $notification->userid = $userid;
      $notification->courseid = $courseid;
      $notification->status = ENROLMENTEMAIL_STATUS_QUEUED;
      $notification->timecreated = time();
      $notification->id = $DB->insert_record('enrolmentemail_queue', $notification);
    }
  }

  /**
   * Delete notification from queue
   *
   * @param   int   $userid       userid
   * @param   int   $courseid     courseid
   * @param   bool  $onlyqueued   only affect notification that are in QUEUED status
   */
  public function delete_notification($userid, $courseid, $onlyqueued = true) {
    global $DB;
    $params = array(
      'userid' => $userid,
      'courseid' => $courseid
    );
    if ($onlyqueued) {
      $params['status'] = ENROLMENTEMAIL_STATUS_QUEUED;
    }
    $exists = $DB->record_exists('enrolmentemail_queue', $params);
    if ($exists) {
      $DB->delete_records('enrolmentemail_queue', $params);
    }
  }

  /**
   * Increment attempts of the notification by one
   *
   * @param   object  $notification 
   */
  public function increment_attempts_by_one($notification) {
    global $DB;
    $notification->attempts++;
    $DB->update_record('enrolmentemail_queue', $notification);
  }

  /**
   * Just a convenient method to get records from queue
   *
   * @param   array  $conditions
   *
   * @return  array  notification records
   */
  public function get_notifications(array $conditions = null) {
    global $DB;
    return $DB->get_records('enrolmentemail_queue', $conditions);
  }

//   public function update_queued_records_status() {
//     $this->mark_notificationdisabled();
//     $this->mark_coursehidden();
//     $this->mark_coursenotfound();
//     $this->mark_usersuspended();
//     $this->mark_usernotfound();
//     $this->mark_methodsuspended();
//     $this->mark_methodnotfound();
//     $this->mark_userenrolmentsuspended();
//     //$this->mark_unknown();
//   }

  /**
   * Check if there are any notifications in QUEUED status
   */
  public function has_queued_notifications() {
    global $DB;
    $count = $DB->count_records('enrolmentemail_queue', array('status' => ENROLMENTEMAIL_STATUS_QUEUED));
    return $count > 0;
  }

  public function mark_notificationsent($userid, $courseids) {
    global $DB;
    if (empty($courseids)) {
      return;
    }
    if (!$this->has_queued_notifications()) {
      return;
    }
    list($c, $p) = $DB->get_in_or_equal($courseids);
    $now = time();
    $sql = <<< EOT
UPDATE mdl_enrolmentemail_queue q SET
  q.status = ?,
  q.last_log_msg = ?,
  q.batchnum = ((SELECT selected_value FROM (SELECT IFNULL(MAX(batchnum), 0) AS selected_value FROM mdl_enrolmentemail_queue) AS sub_selected_value) + 1),
  q.timemodified = ?
WHERE
  q.status = ?
  AND q.userid = ?
  AND q.courseid $c
EOT;
    $params = array(
      ENROLMENTEMAIL_STATUS_SENT,
      'Notification sent',
      time(),
      ENROLMENTEMAIL_STATUS_QUEUED,
      $userid
    );
    $params = array_merge($params, $p);
    $DB->execute($sql, $params);
  }

  /**
   * Mark any notification that are in QUEUED status 
   * and has reached the limit with ARCHIVED status 
   * 
   * @param object  $notification the enrolmentemail_queue record
   * @param int     $limit        max limit before notification can be marked as ARCHIVED
   */
  public function mark_notificationarchived($notification, $limit = 0) {
    global $DB;
    if (!$limit || $limit == 0) {
      return;
    }
    if ($notification->attempts >= $limit) {
      $notification->status = ENROLMENTEMAIL_STATUS_ARCHIVED;
    }
    $DB->update_record('enrolmentemail_queue', $notification);
  }

//   /**
//    * Mark notification with coursecompleted status
//    *
//    * @param   array  $ids  array of enrolmentemail_queue id
//    */
//   public function mark_coursecompleted($ids) {
//     global $DB;
//     if (empty($ids)) {
//       return;
//     }
//     if (!$this->has_queued_notifications()) {
//       return;
//     }
//     list($c, $p) = $DB->get_in_or_equal($ids);
//     $sql = <<< EOT
// UPDATE mdl_enrolmentemail_queue q SET
//   q.status = ?,
//   q.log_msg = ?,
//   q.timemodified = ?
// WHERE
//   q.status = ?
//   AND q.id $c
// EOT;
//     $params = array(
//       ENROLMENTEMAIL_COURSECOMPLETED,
//       'User has completed the course',
//       time(),
//       ENROLMENTEMAIL_STATUS_QUEUED
//     );
//     $params = array_merge($params, $p);
//     $DB->execute($sql, $params);
//   }

//   /**
//    * Mark notification with notification disabled status 
//    * for all courses with notification disabled
//    */
//   public function mark_notificationdisabled() {
//     global $DB;
//     if (!$this->has_queued_notifications()) {
//       return;
//     }
//     $sql = <<< EOT
// UPDATE
//   mdl_enrolmentemail_queue q
//   JOIN mdl_enrolmentemail_courses ec ON q.courseid = ec.courseid 
// SET
//   q.status = ?,
//   q.log_msg = ?,
//   q.timemodified = ?
// WHERE
//   ec.enabled = ?
//   AND q.status = ?
// EOT;
//     $params = array(
//       ENROLMENTEMAIL_NOTIFICATIONDISABLED, 
//       'Course notification is disabled', 
//       time(), 
//       ENROLMENTEMAIL_COURSENOTIFICATION_DISABLED, 
//       ENROLMENTEMAIL_STATUS_QUEUED
//     );
//     $DB->execute($sql, $params);
//   }

//   /**
//    * Mark notification with coursehidden status 
//    * for all courses with hidden status
//    */
//   public function mark_coursehidden() {
//     global $DB;
//     if (!$this->has_queued_notifications()) {
//       return;
//     }
//     $sql = <<< EOT
// UPDATE
//   mdl_enrolmentemail_queue q
//   JOIN mdl_course c ON q.courseid = c.id 
// SET
//   q.status = ?,
//   q.log_msg = ?,
//   q.timemodified = ?
// WHERE
//   c.visible = 0
//   AND q.status = ?
// EOT;
//     $params = array(
//       ENROLMENTEMAIL_COURSEHIDDEN, 
//       'Course is hidden',
//       time(), 
//       ENROLMENTEMAIL_STATUS_QUEUED
//     );
//     $DB->execute($sql, $params);
//   }

//   public function mark_coursenotfound() {
//     global $DB;
//     $sql = <<< EOT
// UPDATE
//   mdl_enrolmentemail_queue q
//   LEFT JOIN mdl_course c ON q.courseid = c.id 
// SET
//   q.status = ?,
//   q.log_msg = ?,
//   q.timemodified = ?
// WHERE
//   c.id IS NULL
//   AND q.status = ?
// EOT;
//     $params = array(
//       ENROLMENTEMAIL_COURSENOTFOUND, 
//       'Course not exist',
//       time(),
//       ENROLMENTEMAIL_STATUS_QUEUED
//     );
//     $DB->execute($sql, $params);
//   }

//   public function mark_usersuspended() {
//     global $DB;
//     if (!$this->has_queued_notifications()) {
//       return;
//     }
//     $log_msg = 'User account is suspended';
//     $timemodified = time();
//     $sql = <<< EOT
// UPDATE
//   mdl_enrolmentemail_queue q
//   JOIN mdl_user u ON q.userid = u.id
// SET
//   q.status = ?,
//   q.log_msg = ?,
//   q.timemodified = ?
// WHERE
//   u.suspended = 1
//   AND q.status = ?
// EOT;
//     $params = array(
//       ENROLMENTEMAIL_USERSUSPENDED,
//       'User account is suspended', 
//       time(), 
//       ENROLMENTEMAIL_STATUS_QUEUED
//     );
//     $DB->execute($sql, $params);
//   }

//   public function mark_usernotfound() {
//     global $DB;
//     $sql = <<< EOT
// UPDATE
//   mdl_enrolmentemail_queue q
//   LEFT JOIN mdl_user u ON q.userid = u.id
// SET
//   q.status = ?,
//   q.log_msg = ?,
//   q.timemodified = ?
// WHERE
//   (u.deleted = 1 OR u.id IS NULL)
//   AND q.status = ?
// EOT;
//     $params = array(
//       ENROLMENTEMAIL_USERNOTFOUND,
//       'User account not exist',
//       time(), 
//       ENROLMENTEMAIL_STATUS_QUEUED
//     );
//     $DB->execute($sql, $params);
//   }

//   public function mark_methodsuspended() {
//     global $DB;
//     if (!$this->has_queued_notifications()) {
//       return;
//     }
//     $sql = <<< EOT
// UPDATE 
//   mdl_enrolmentemail_queue q JOIN (
//     SELECT 
//       e.courseid, 
//       MIN(e.status) as min_val 
//     FROM 
//       mdl_enrol e
//     WHERE 
//       e.enrol IN ('manual', 'cohort')
//     GROUP BY 
//       e.courseid
// ) a ON q.courseid = a.courseid
// SET
//   q.status = IF(a.min_val >= 1, ?, q.status),
//   q.log_msg = ?,
//   q.timemodified = ?  
// WHERE 
//   q.status = ?
// EOT;
//     $params = array(
//       ENROLMENTEMAIL_METHODSUSPENDED,
//       'Enrolment method suspended', 
//       time(), 
//       ENROLMENTEMAIL_STATUS_QUEUED
//     );
//     $DB->execute($sql, $params);
//   }

//   function mark_methodnotfound() {
//     global $DB;
//     if (!$this->has_queued_notifications()) {
//       return;
//     }
//     $sql = <<< EOT
// UPDATE 
//   mdl_enrolmentemail_queue q JOIN (
//     SELECT 
//       e.courseid, 
//       MIN(e.status) as min_val 
//     FROM 
//       mdl_enrol e
//     WHERE 
//       e.enrol IN ('manual', 'cohort')
//     GROUP BY 
//       e.courseid
// ) a ON q.courseid = a.courseid
// SET
//   q.status = IF(a.min_val IS NULL, ?, q.status),
//   q.log_msg = ?,
//   q.timemodified = ?  
// WHERE 
//   q.status = ?
// EOT;
//     $params = array(
//       ENROLMENTEMAIL_METHODNOTFOUND,
//       'Enrolment method not found', 
//       time(), 
//       ENROLMENTEMAIL_STATUS_QUEUED
//     );
//     $DB->execute($sql, $params);
//   }

//   public function mark_userenrolmentsuspended() {
//     global $DB;
//     if (!$this->has_queued_notifications()) {
//       return;
//     }
//     $sql = <<< EOT
// UPDATE 
//   mdl_enrolmentemail_queue q JOIN (
//     SELECT 
//       e.courseid, 
//       ue.userid,
//       MIN(ue.status) as min_val 
//     FROM 
//       mdl_enrol e 
//       JOIN mdl_user_enrolments ue ON e.id = ue.enrolid
//     WHERE 
//       e.enrol IN ('manual', 'cohort') 
//     GROUP BY 
//       e.courseid
// ) a ON q.courseid = a.courseid AND q.userid = a.userid
// SET
//   q.status = IF(a.min_val >= 1, ?, q.status),
//   q.log_msg = ?,
//   q.timemodified = ?  
// WHERE 
//   q.status = ?
// EOT;
//     $params = array(
//       ENROLMENTEMAIL_USERENROLMENTSUSPENDED,
//       'User enrolment is suspended', 
//       time(), 
//       ENROLMENTEMAIL_STATUS_QUEUED
//     );
//     $DB->execute($sql, $params);
//   }
}