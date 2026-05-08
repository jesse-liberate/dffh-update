<?php
/**
 * reportImport.php
 * Created by Fan Li on 27/01/2021.
 */

require(__DIR__.'/../../../config.php');

set_time_limit(0);

global $CFG, $DB;
$courses = $DB->get_records('course');
foreach ($courses as $course){
    require_once("$CFG->libdir/completionlib.php");
    $completion_info = new completion_info($course);
    $users = $completion_info->get_tracked_users();
    foreach ($users as $user){
        $sql = "SELECT DISTINCT e.enrol
            FROM {enrol} e
            JOIN {user_enrolments} ue ON ue.enrolid = e.id
            WHERE e.courseid = ?
            AND ue.userid = ?";
        $records = $DB->get_records_sql($sql, [$course->id, $user->id]);
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
        $record = $DB->get_record_sql($sql, [$course->id, $user->id]);
        $enrolment_timestart = $record->timecreated;

        $cm_info = $completion_info->get_activities();
        $course_category = $DB->get_record('course_categories',['id'=>$course->category]);
        $course_context = context_course::instance($course->id);
        $enrolment_is_active = is_enrolled($course_context, $user->id, '', true);
        foreach ($cm_info as $activity){
            if(!$DB->record_exists('report_completion', [
                'user_id'=>$user->id,
                'course_module_id'=>$activity->id,
            ])){
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
                $record->course_module_id = $activity->id;

                $module = $DB->get_record('modules', ['id'=>$activity->module]);
                $record->module_name = $module->name;

                $record->course_module_instance_id = $activity->instance;

                $module_instance = $DB->get_record($module->name, ['id'=>$activity->instance]);
                $record->course_module_instance_name = $module_instance->name;

                $course_module_completion = $DB->get_record('course_modules_completion', [
                    'coursemoduleid'=>$activity->id,
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
    }
}