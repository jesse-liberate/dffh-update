<?php

namespace block_training_report;

use cm_info;
use completion_info;
use context_course;
use core\event\course_category_updated;
use core\event\course_deleted;
use core\event\course_module_completion_updated;
use core\event\course_module_created;
use core\event\course_module_deleted;
use core\event\course_module_updated;
use core\event\course_updated;
use core\event\role_assigned;
use core\event\role_unassigned;
use core\event\user_enrolment_created;
use core\event\user_enrolment_deleted;
use core\event\user_enrolment_updated;

class observer {
    public static function course_module_completion_updated(course_module_completion_updated $event) {
        /**
         * @todo Implement create or update records in {report_completion} table
         * note:
         *   Create will be handled via role assign, however it is possible that the record is missing therefore it
         *     would be good to also create the record if it is missing before updating
         *   Delete will be handled via different events, because completion data is only deleted in bulk
         *     and triggered by course delete, role un-assign, course module is deleted
         * useful methods:
         *   $event->get_record_snapshot('course_modules_completion', $event->objectid) - This is to get the record
         *     that is triggered from the event, this will avoid doing DB query if available
         */
        global $DB;
        $module_completion = $event->get_record_snapshot('course_modules_completion', $event->objectid);
        $report_completion = $DB->get_record('report_completion', [
            'user_id'=>$event->relateduserid,
            'course_module_id'=>$module_completion->coursemoduleid,
        ]);
        if(!$report_completion){
            // insert if not exist

            $enrolment_types = self::get_enrolment_types($event->courseid, $event->relateduserid);

            $enrolment_timestart = self::get_enrolment_timestart($event->courseid, $event->relateduserid);

            $course = get_course($event->courseid);
            $course_category = $DB->get_record('course_categories',['id'=>$course->category]);
            $cm = $DB->get_record('course_modules', ['id'=>$module_completion->coursemoduleid]);
            $report_completion = self::insert_report_completion($course, $event->relateduserid, $course_category, $cm, $enrolment_types, $enrolment_timestart);
        }

        if($module_completion){
            $report_completion->completion_id = $event->objectid;
            $report_completion->completion_state = $module_completion->completionstate;
            $report_completion->completion_date = $module_completion->timemodified;
            $DB->update_record('report_completion', $report_completion);
        }else{
            $report_completion->completion_id = null;
            $report_completion->completion_state = null;
            $report_completion->completion_date = null;
            $DB->update_record('report_completion', $report_completion);
        }
    }

    public static function course_module_created(course_module_created $event) {
        /**
         * @todo Implement insert records to {report_completion} table
         * steps:
         *   Check if the created module is not COMPLETION_TRACKING_NONE
         *   Get all tracked users
         *   Foreach tracked users
         *   Apply similar behaviour to static::role_assigned but on a given user and course_module_id
         */
        global $CFG, $DB;
        $cm = $event->get_record_snapshot('course_modules', $event->objectid);
        if ($cm->completion == COMPLETION_TRACKING_NONE) {
            return;
        }
        $course = get_course($event->courseid);
        require_once("$CFG->libdir/completionlib.php");
        $completion_info = new completion_info($course);
        $users = $completion_info->get_tracked_users();
        foreach ($users as $user){
            $sql = "SELECT DISTINCT e.enrol
                FROM {enrol} e
                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE e.courseid = ?
                AND ue.userid = ?";
            $records = $DB->get_records_sql($sql, [$event->courseid, $user->id]);
            $enrolment_types = [];
            foreach ($records as $record){
                $enrolment_types[] = $record->enrol;
            }
            array_unique($enrolment_types);

            $sql =  "SELECT DISTINCT MIN(ue.`timecreated`) AS timecreated
                FROM {enrol} e
                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE e.courseid = ?
                AND ue.userid = ?";
            $record = $DB->get_record_sql($sql, [$event->courseid, $user->id]);
            $enrolment_timestart = $record->timecreated;

            $course_category = $DB->get_record('course_categories',['id'=>$course->category]);
            $course_context = context_course::instance($course->id);
            $enrolment_is_active = is_enrolled($course_context, $user->id, '', true);

            $record = new \stdClass();
            $record->user_id = $user->id;
            $record->course_category_id = $course->category;
            $record->course_category_name = $course_category->name;
            $record->course_id = $course->id;
            $record->course_name = $course->fullname;
            $enrolment_types = $enrolment_types;
            $record->enrolment_types = json_encode($enrolment_types);
            $record->enrolment_startdate = $enrolment_timestart;
            $record->enrolment_is_active = $enrolment_is_active?1:0;
            $record->course_module_id = $cm->id;

            $module = $DB->get_record('modules', ['id'=>$cm->module]);
            $record->module_name = $module->name;

            $record->course_module_instance_id = $cm->instance;

            $module_instance = $DB->get_record($module->name, ['id'=>$cm->instance]);
            $record->course_module_instance_name = $module_instance->name;

            $course_module_completion = $DB->get_record('course_modules_completion', [
                'coursemoduleid'=>$cm->id,
                'userid'=>$user->id
            ]);
            if($course_module_completion){
                $record->completion_id = $course_module_completion->id;
                $record->completion_state = $course_module_completion->completionstate;
                $record->completion_date = $course_module_completion->timemodified;
            }
            $DB->insert_record('report_completion', $record);
        }
    }

    public static function course_module_deleted(course_module_deleted $event) {
        /**
         * @todo Implement delete records in {report_completion} table based on course_module_id given in the event
         *       $event->objectid is course_module_id
         */
        global $DB;
        $DB->delete_records('report_completion', ['course_module_id'=>$event->objectid]);
    }

    public static function course_module_updated(course_module_updated $event) {
        /**
         * @todo Implement delete records in {report_completion} table if the completion is set to
         *       COMPLETION_TRACKING_NONE or update completion_state by checking the
         *       course_modules_completion table.
         *       And update course_module_instance_name in {report_completion} table
         */
        global $CFG, $DB;
        $cm = $event->get_record_snapshot('course_modules', $event->objectid);
        if($cm->completion == COMPLETION_TRACKING_NONE){
            $DB->delete_records('report_completion', ['course_module_id'=>$event->objectid]);
        }else{
            $course = get_course($cm->course);
            require_once("$CFG->libdir/completionlib.php");
            $completion_info = new completion_info($course);
            $users = $completion_info->get_tracked_users();
            foreach ($users as $user){
                $exist = $DB->record_exists('report_completion', [
                    'user_id'=>$user->id,
                    'course_module_id'=>$event->objectid,
                ]);
                if(!$exist && !$cm->deletioninprogress){
                    $enrolment_types = self::get_enrolment_types($event->courseid, $user->id);

                    $enrolment_timestart = self::get_enrolment_timestart($event->courseid, $user->id);

                    $course = get_course($event->courseid);
                    $course_category = $DB->get_record('course_categories',['id'=>$course->category]);
                    $report_completion = self::insert_report_completion($course, $user->id, $course_category, $cm, $enrolment_types, $enrolment_timestart);
                }

                $course_module_completion = $DB->get_record('course_modules_completion', [
                    'coursemoduleid'=>$event->objectid,
                    'userid'=>$user->id
                ]);
                if($course_module_completion){
                    $sql = "UPDATE `{report_completion}` 
                    SET `completion_state`= ? 
                    WHERE `user_id` = ? AND `course_module_id` = ?";
                    $DB->execute($sql, [$course_module_completion->completionstate, $user->id, $event->objectid]);
                }else{
                    $sql = "UPDATE `{report_completion}` 
                    SET `completion_id` = ?, `completion_state`= ?, `completion_date` = ? 
                    WHERE `user_id` = ? AND `course_module_id` = ?";
                    $DB->execute($sql, [null, null, null, $user->id, $event->objectid]);
                }
            }

            $sql = "UPDATE `{report_completion}` 
                    SET `course_module_instance_name` = ? 
                    WHERE `course_module_id` = ?";
            $DB->execute($sql, [$event->other['name'], $event->objectid]);
        }
    }

    public static function course_deleted(course_deleted $event) {
        /**
         * @todo Implement delete records in {report_completion} table based on course_id given in the event
         */
        global $DB;
        $DB->delete_records('report_completion', ['course_id'=>$event->courseid]);
    }

    public static function role_assigned(role_assigned $event) {
        /**
         * @todo Finish implementing creating records in {report_completion} table
         * note:
         *   This function needs to get the user's current enrolment_types in this course and populate
         *     the data in the enrolment_types as well, for more details {@see static::user_enrolment_created}
         * useful resources:
         *   $completion_info->get_activities() - @return cm_info[] - Obtains a list of activities for which completion is
         *     enabled on the course. The list is ordered by the section order of those activities.
         *   $cm_info->modname - module_name in {report_completion}
         *   $cm_info->name - course_module_instance_name in {report_completion}
         */

        global $CFG, $DB;
        // Roles can be assigned outside of a course, therefore we need to ensure
        // that we only care about role assigned to course level
        if ($event->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        $course = get_course($event->courseid);
        require_once("$CFG->libdir/completionlib.php");
        $completion_info = new completion_info($course);
        // A user can be assigned as a teacher and we do not want to insert data
        // or report on the completion for these users, students in Moodle are referred
        // as 'tracked users' and therefore we use Moodle's function to count how many
        // tracked users there are given the user id, the result should always be 1
        // so if the result is 0, it means the user is not tracked
        if ($completion_info->get_num_tracked_users('u.id = :uid', ['uid'=>$event->relateduserid]) == 0) {
            return;
        }

        $sql = "SELECT DISTINCT e.enrol
                FROM {enrol} e
                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE e.courseid = ?
                AND ue.userid = ?";
        $records = $DB->get_records_sql($sql, [$event->courseid, $event->relateduserid]);
        $enrolment_types = [];
        foreach ($records as $record){
            $enrolment_types[] = $record->enrol;
        }
        array_unique($enrolment_types);

        $sql =  "SELECT DISTINCT MIN(ue.`timecreated`) AS timecreated
                FROM {enrol} e
                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE e.courseid = ?
                AND ue.userid = ?";
        $record = $DB->get_record_sql($sql, [$event->courseid, $event->relateduserid]);
        $enrolment_timestart = $record->timecreated;

        $cm_info = $completion_info->get_activities();
        $course_category = $DB->get_record('course_categories',['id'=>$course->category]);
        $course_context = context_course::instance($course->id);
        $enrolment_is_active = is_enrolled($course_context, $event->relateduserid, '', true);
        foreach ($cm_info as $activity){
            if(!$DB->record_exists('report_completion', [
                'user_id'=>$event->relateduserid,
                'course_module_id'=>$activity->id,
            ])){
                $record = new \stdClass();
                $record->user_id = $event->relateduserid;
                $record->course_category_id = $course->category;
                $record->course_category_name = $course_category->name;
                $record->course_id = $course->id;
                $record->course_name = $course->fullname;
                $enrolment_types = $enrolment_types;
                $record->enrolment_types = json_encode($enrolment_types);
                $record->enrolment_startdate = $enrolment_timestart;
                $record->enrolment_is_active = $enrolment_is_active?1:0;
                $record->course_module_id = $activity->id;

                $module = $DB->get_record('modules', ['id'=>$activity->module]);
                $record->module_name = $module->name;

                $record->course_module_instance_id = $activity->instance;

                $module_instance = $DB->get_record($module->name, ['id'=>$activity->instance]);
                $record->course_module_instance_name = $module_instance->name;

                $course_module_completion = $DB->get_record('course_modules_completion', [
                    'coursemoduleid'=>$activity->id,
                    'userid'=>$event->relateduserid
                ]);
                if($course_module_completion){
                    $record->completion_id = $course_module_completion->id;
                    $record->completion_state = $course_module_completion->completionstate;
                    $record->completion_date = $course_module_completion->timemodified;
                }
            $DB->insert_record('report_completion', $record);
            }
        }
    }

    public static function role_unassigned(role_unassigned $event) {
        /**
         * @todo Implement deleting records in {report_completion} based on course_id, user_id given in the event
         * note:
         *   Make sure you check the role is unassigned to a course level {@see static::role_assigned} if in doubt
         *   $event->userid is NOT the user who is unassigned, $event->userid is the user who triggered this action
         * useful properties:
         *   $event->courseid
         *   $event->relateduserid - This is the user who is unassigned with role, NOT $event->userid
         */
        global $CFG, $DB;
        // Roles can be assigned outside of a course, therefore we need to ensure
        // that we only care about role assigned to course level
        if ($event->contextlevel !== CONTEXT_COURSE) {
            return;
        }
        $course = get_course($event->courseid);
        require_once("$CFG->libdir/completionlib.php");
        $completion_info = new completion_info($course);
        // if the result is 0, it means the user is not tracked
        if ($completion_info->get_num_tracked_users('u.id = :uid', ['uid'=>$event->relateduserid]) == 0) {
            $DB->delete_records('report_completion', [
                'user_id'=>$event->relateduserid,
                'course_id'=>$event->courseid
            ]);
        }
    }

    public static function user_enrolment_created(user_enrolment_created $event) {
        /**
         * @todo Implement update enrolment_types column in {report_completion} table if the record exists
         * note:
         *   This event is triggered before role_assigned, therefore if the record does not exist we do not
         *     need to do anything in here because of various reasons below
         *   If a user is enrolled as a teacher, this is triggered but we do not want to report on these users
         *   If a user is enrolled as a student twice using 2 unique enrolment types (e.g. manual, cohort), this
         *     will be triggered twice, but the role_assigned is only triggered once, therefore we need this
         *     to update the enrolment_types column
         *   If a user is enrolled without a role, Moodle generally never do this, this has only been seen in
         *     MindAtlas' code, and Moodle will not track completion for these users so neither should we
         */
        global $DB;
        $records = $DB->get_records('report_completion', [
            'user_id' => $event->relateduserid,
            'course_id' => $event->courseid,
            ]);

        if(count($records) > 0){
            foreach ($records as $record){
                $enrolment_types = json_decode($record->enrolment_types);
                $enrolment_types[] = $event->other['enrol'];
                $record->enrolment_types = json_encode(array_unique($enrolment_types));
                $DB->update_record('report_completion', $record);
            }
        }
        return true;
    }

    public static function user_enrolment_deleted(user_enrolment_deleted $event) {
        /**
         * @todo Implement update enrolment_types column in {report_completion} table if the record exists
         */
        global $CFG, $DB;
        // Roles can be assigned outside of a course, therefore we need to ensure
        // that we only care about role assigned to course level
        if ($event->contextlevel !== CONTEXT_COURSE) {
            return;
        }
        $course = get_course($event->courseid);
        require_once("$CFG->libdir/completionlib.php");
        $completion_info = new completion_info($course);
        // if the result is 0, it means the user is not tracked
        if ($completion_info->get_num_tracked_users('u.id = :uid', ['uid'=>$event->relateduserid]) == 0) {
            $DB->delete_records('report_completion', [
                'user_id'=>$event->relateduserid,
                'course_id'=>$event->courseid
            ]);
        }
    }

    public static function user_enrolment_updated(user_enrolment_updated $event) {
        /**
         * @todo Implement update enrolment_is_active column in {report_completion} table
         * note:
         *   A user can be tracked but its enrolment can be suspended/inactive at the same time
         *   Suspended/inactive enrolment prevents users from accessing the course, but its completion
         *     is still reported
         * useful resources:
         *   ENROL_USER_SUSPENDED - the status if a user's enrolment is suspended
         *   Moodle have a convenient way to check this, see below closure as an example given user_id and course_id,
         *     this is NOT how you should do it, because it is not reusable!!!
         */
//        $is_active_enrolment = function ($user_id, $course_id) {
//            $context_course = context_course::instance($course_id);
//            return is_enrolled($context_course, $user_id, '', true);
//        }; // Example only please remove/comment out after you read
        global $DB;
        $context_course = context_course::instance($event->courseid);
        $enrolment_is_active  = is_enrolled($context_course, $event->relateduserid, '', true);
        $sql = "UPDATE `{report_completion}` 
                SET `enrolment_is_active` = ? 
                WHERE `course_id` = ? AND `user_id` = ?";
        $DB->execute($sql, [$enrolment_is_active, $event->courseid, $event->relateduserid]);
    }

    public static function course_category_updated(course_category_updated $event) {
        /**
         * @todo Implement update course_category_name column in {report_completion} table
         */
        global $DB;
        $course_category = $event->get_record_snapshot('course_categories', $event->objectid);
        $sql = "UPDATE `{report_completion}`
                SET `course_category_name` = ?
                WHERE `course_category_id` = ?";
        $DB->execute($sql, [$course_category->name, $event->objectid]);
    }

    public static function course_updated(course_updated $event) {
        /**
         * @todo Implement update course_name column in {report_completion} table
         */
        global $DB;
        $sql = "UPDATE `{report_completion}` 
                SET `course_name` = ? 
                WHERE `course_id` = ?";
        $DB->execute($sql, [$event->other['fullname'], $event->courseid]);
    }

    private static function get_enrolment_types($courseid, $userid){
        global $DB;
        $sql = "SELECT DISTINCT e.enrol
                FROM {enrol} e
                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE e.courseid = ?
                AND ue.userid = ?";
        $records = $DB->get_records_sql($sql, [$courseid, $userid]);
        $enrolment_types = [];
        foreach ($records as $record){
            $enrolment_types[] = $record->enrol;
        }
        return array_unique($enrolment_types);
    }

    private static function get_enrolment_timestart($courseid, $userid){
        global $DB;
        $sql =  "SELECT DISTINCT MIN(ue.`timecreated`) AS timecreated
                FROM {enrol} e
                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE e.courseid = ?
                AND ue.userid = ?";
        $record = $DB->get_record_sql($sql, [$courseid, $userid]);
        return $record->timecreated;
    }

    private static function insert_report_completion($course, $userid, $course_category, $cm, $enrolment_types, $enrolment_timestart)
    {
        global $DB;
        $course_context = context_course::instance($course->id);
        $enrolment_is_active = is_enrolled($course_context, $userid, '', true);

        $record = new \stdClass();
        $record->user_id = $userid;
        $record->course_category_id = $course->category;
        $record->course_category_name = $course_category->name;
        $record->course_id = $course->id;
        $record->course_name = $course->fullname;
        $enrolment_types = $enrolment_types;
        $record->enrolment_types = json_encode($enrolment_types);
        $record->enrolment_startdate = $enrolment_timestart;
        $record->enrolment_is_active = $enrolment_is_active?1:0;
        $record->course_module_id = $cm->id;

        $module = $DB->get_record('modules', ['id'=>$cm->module]);
        $record->module_name = $module->name;

        $record->course_module_instance_id = $cm->instance;

        $module_instance = $DB->get_record($module->name, ['id'=>$cm->instance]);
        $record->course_module_instance_name = $module_instance->name;

        $course_module_completion = $DB->get_record('course_modules_completion', [
            'coursemoduleid'=>$cm->id,
            'userid'=>$userid
        ]);
        if($course_module_completion){
            $record->completion_id = $course_module_completion->id;
            $record->completion_state = $course_module_completion->completionstate;
            $record->completion_date = $course_module_completion->timemodified;
        }
        $id = $DB->insert_record('report_completion', $record, true);
        $record->id = $id;
        return $record;
    }
}
