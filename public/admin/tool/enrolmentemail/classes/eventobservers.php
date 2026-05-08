<?php

namespace tool_enrolmentemail;
require_once($CFG->dirroot . '/admin/tool/enrolmentemail/locallib.php');
require_once($CFG->dirroot . '/admin/tool/enrolmentemail/constants.php');

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for enrolmentemail.
 */
class eventobservers {

  /**
   * Triggered via user_enrolment_created event.
   *
   * @param \core\event\user_enrolment_created $event
   * @return bool true on success.
   */
  public static function user_enrolment_created(\core\event\user_enrolment_created $event) {
// item passed to event object
//  'objectid' => $ue->id,
//  'courseid' => $courseid,
//  'context' => $context,
//  'relateduserid' => $ue->userid,
//  'other' => array('enrol' => $name)

    if (self::is_manual_or_cohort($event->other['enrol'])) {
      $queue = new queue();
      $queue->create_notification($event->relateduserid, $event->courseid);
    }

    return true;
  }

  /**
   * Triggered via user_enrolment_deleted event.
   *
   * @param \core\event\user_enrolment_deleted $event
   * @return bool true on success.
   */
  public static function user_enrolment_deleted(\core\event\user_enrolment_deleted $event) {
    // item passed to event object
    //    'courseid' => $courseid,
    //    'context' => $context,
    //    'relateduserid' => $ue->userid,
    //    'objectid' => $ue->id,
    //    'other' => array(
    //        'userenrolment' => (array)$ue,
    //        'enrol' => $name
    //        )
    //    )
    if (self::is_manual_or_cohort($event->other['enrol'])
      && !is_enrolled($event->get_context(), $event->relateduserid)) {
      $queue = new queue();
      $queue->delete_notification($event->relateduserid, $event->courseid);
    }
    return true;
  }

  /**
   * Triggered via user_enrolment_updated event.
   *
   * @param \core\event\user_enrolment_updated $event
   * @return bool true on success
   */
  public static function user_enrolment_updated(\core\event\user_enrolment_updated $event) {
// item passed to event object    
//    'objectid' => $ue->id,
//    'courseid' => $instance->courseid,
//    'context' => context_course::instance($instance->courseid),
//    'relateduserid' => $ue->userid,
//    'other' => array('enrol' => $name)
    // error_log(var_export($event, true));
    return true;
  }

  /**
   * Triggered via course_created event.
   *
   * @param \core\event\course_created $event
   * @return bool true on success
   */
  public static function course_created(\core\event\course_created $event) {
    $defaultsetting = get_local_config(ENROLMENTEMAIL_INITIALCOURSENOTIFICATION);
    global $DB;
    $id = $event->objectid;
    $record = new \stdClass();
    $record->courseid = $id;
    $record->enabled = $defaultsetting;
    $record->timecreated = time();
    $DB->insert_record('enrolmentemail_courses', $record);
    return true;
  }

  /**
   * Triggered via course_deleted event.
   *
   * @param \core\event\course_deleted $event
   * @return bool true on success
   */
  public static function course_deleted(\core\event\course_deleted $event) {
    global $DB;
    $id = $event->objectid;

    // Course is gone, so do all related course notification setting and notification records
    $DB->delete_records('enrolmentemail_courses', array('courseid' => $id));
    $DB->delete_records('enrolmentemail_queue', array('courseid' => $id, 'status' => ENROLMENTEMAIL_STATUS_QUEUED));
    return true;
  }

  /**
   * Triggered via user_deleted event.
   *
   * @param \core\event\user_deleted $event
   * @return bool true on success
   */
  public static function user_deleted(\core\event\user_deleted $event) {
    global $DB;
    $id = $event->objectid;
    $DB->delete_records('enrolmentemail_queue', array('userid' => $id, 'status' => ENROLMENTEMAIL_STATUS_QUEUED));
    return true;
  }

  // /**
  //  * Triggered via enrol_instance_updated event.
  //  *
  //  * @param \core\event\enrol_instance_updated $event
  //  * @return bool true on success
  //  */
  // public static function enrol_instance_updated(\core\event\enrol_instance_updated $event) {
  //   global $DB;
  //   $table = 'enrolmentemail_queue';
  //   $snapshot = $event->get_record_snapshot('enrol', $event->objectid);
  //   if (self::is_manual_or_cohort($snapshot->enrol)) {
  //     $enrols = self::get_enrol_records($snapshot->courseid);
  //     if (count($enrols) == 1) {
  //       $params[] = array();
  //       $params['courseid'] = $snapshot->courseid;
  //       switch ($snapshot->status) {
  //         case ENROL_INSTANCE_ENABLED:
  //           $params['status'] = ENROLMENTEMAIL_METHODSUSPENDED;
  //           $records = $DB->get_records($table, $params);
  //           foreach ($records as $id => $record) {
  //             $record->status = ENROLMENTEMAIL_QUEUED;
  //             $DB->update_record($table, $record);
  //           } 
  //           break;
  //         case ENROL_INSTANCE_DISABLED:
  //           $params['status'] = ENROLMENTEMAIL_QUEUED;
  //           $records = $DB->get_records($table, $params);
  //           foreach ($records as $id => $record) {
  //             $record->status = ENROLMENTEMAIL_METHODSUSPENDED;
  //             $DB->update_record($table, $record);
  //           } 
  //           break;
  //       }
  //     }
  //   }
  //   return true;

  // }

  // /**
  //  * Triggered via enrol_instance_deleted event.
  //  *
  //  * @param \core\event\enrol_instance_deleted $event
  //  * @return bool true on success
  //  */
  // public static function enrol_instance_deleted(\core\event\enrol_instance_deleted $event) {
  //   global $CFG, $DB;
  //   $table = 'enrolmentemail_queue';
  //   $snapshot = $event->get_record_snapshot('enrol', $event->objectid);
  //   if (self::is_manual_or_cohort($snapshot->enrol)) {
  //     $enrols = self::get_enrol_records($snapshot->courseid);
  //     if (!$enrols) {
  //       $records = $DB->get_records('enrolmentemail_queue', array('courseid' => $snapshot->courseid, 'status' => ENROLMENTEMAIL_QUEUED));
  //       foreach ($records as $id => $record) {
  //         $record->status = ENROLMENTEMAIL_METHODNOTFOUND;
  //         $DB->update_record($table, $record);
  //       }
  //     }
  //   }
  //   return true;
  // }

  /**
   * get enrol records
   *
   * @param   int    $courseid
   * @param   bool   $includeselfenrol
   *
   * @return  array  enrol records
   */
  public static function get_enrol_records($courseid, $includeselfenrol = false) {
    global $DB;
    $params = [$courseid, 'manual', 'cohort'];
    $condition = '(?, ?)';
    if ($includeselfenrol === true) {
      $params[] = 'self';
      $condition = '(?, ?, ?)';
    }
    $sql = <<< EOT
  SELECT * FROM {enrol} WHERE courseid = ? AND enrol IN $condition
EOT;
    return $DB->get_records_sql($sql, $params);
  }

  /**
   * Commented out because this will listen to all email_failed event regardless.
   * Triggered via email_failed event.
   *
   * @param \core\event\email_failed $event
   * @return bool true on success
   */  
  /*
  public static function email_failed(\core\event\email_failed $event) {
// item passed to event object
//    'context' => context_system::instance(),
//    'userid' => $from->id,
//    'relateduserid' => $user->id,
//    'other' => array(
//        'subject' => $subject,
//        'message' => $messagetext,
//        'errorinfo' => $mail->ErrorInfo
//    )
    global $DB;
    $userid = $event->relateduserid;
    $records = $DB->get_records('enrolmentemail_queue', array('userid' => $userid, 'status' => ENROLMENTEMAIL_QUEUED));
    $timemodified = time();
    $log_msg = self::get_log_msg($event->other['errorinfo']);
    
    foreach ($records as $id => $record) {
      $record->status = ENROLMENTEMAIL_FAILED;
      $record->log_msg = $log_msg;
      $record->timemodified = $timemodified;
      $DB->update_record('enrolmentemail_queue', $record);
    }
    return true;
  }
   * 
   */
  
  /**
   * Check if enrol type is either manaul or cohort
   *
   * @param   string  $type  enrol type
   *
   * @return  bool
   */
  private static function is_manual_or_cohort($type) {
    $type = strtolower($type);
    return $type === 'manual' || $type === 'cohort';
  }
  
  /**
   * Specify custom log_msg
   * @param string $text
   * @return string custom log_msg
   */
  private static function get_log_msg($text) {
    $log_msg = $text;
    if (strpos($text, 'SMTP connect() failed') !== false) {
      $log_msg = 'SMTP Connection failure';
    }
    return $log_msg;
  }

}
