<?php
namespace block_reporting;

require_once(__DIR__. '/../../../config.php');

class reporting_util
{
    public static function get_courses_in_category($category_name = null)
    {
        if ($category_name == null){
            return null;
        }

        global $DB;
        $category_name = strtolower($category_name);
        $category_id = $DB->get_field('course_categories', 'id', ['name' => $category_name]);
        $category_path = '/' . $category_id;
        $query = "SELECT c.id as course_id, c.fullname as course_name FROM {course} c 
        JOIN {course_categories} cc ON cc.id = c.category
        WHERE cc.path LIKE '%$category_path%'";
        $courses = $DB->get_records_sql($query, [$category_name]);
        
        return $courses;
    }

    public static function group_course_completed_users($users = [], $group_by)
    {
        if ($group_by == 'gender'){
            $return = [];
            foreach ($users as $key => $value) {
                if (!isset($return[$value->user_gender])){
                    $return[$value->user_gender] = [];
                }
                $return[$value->user_gender][] = $value;
            }
            return $return;
        }

        if ($group_by == 'group'){
            $return = [];
            foreach ($users as $key => $value) {
                if (!isset($return[$value->user_group])) {
                    $return[$value->user_group] = [];
                }
                $return[$value->user_group][] = $value;
            }
            return $return;
        }
    }

    /**
     * There are 4 conditions that will return empty:
     * 1. course_id == null;
     * 2. No users enrolled in the course
     * 3. No users completed the course
     * 4. Users don't have gender or group
     * @param array $args
     * @return array
     */
    public static function get_course_completed_users($args = [])
    {
        global $DB;
        if (empty($args['course_id'])){
            return [];
        }

        $fields = [
            "DISTINCT(u.id) as user_id",
            "u.firstname",
            "u.lastname",
            "uidgender.data as user_gender",
            "uidgroup.data as user_group",
            "c.fullname as course_name",
            "c.id as course_id",
        ];
        $conditions = [
            "(u.username <> 'guest')",
            "(u.deleted <> 1)",
            "(u.suspended <> 1)",
            "(uifgender.shortname LIKE '%gender%' AND uidgender.data <> '')",
            "(uifgroup.shortname LIKE '%group%' AND uidgroup.data <> '')",
        ];
        if (!empty($args['course_id'])){
            $conditions[] = "(c.id = " . $args['course_id'] . ")";
        }

        $str_conditions = implode(' AND ', $conditions);
        $str_fields = implode(',', $fields);
        $q1 = "SELECT $str_fields 
        FROM {user} u
        LEFT JOIN {user_info_data} uidgender ON uidgender.userid = u.id
        LEFT JOIN {user_info_field} uifgender ON uifgender.id = uidgender.fieldid
        LEFT JOIN {user_info_data} uidgroup ON uidgroup.userid = u.id 
        LEFT JOIN {user_info_field} uifgroup ON uifgroup.id = uidgroup.fieldid               
        LEFT JOIN {user_enrolments} ue ON ue.userid = u.id
        LEFT JOIN {enrol} e ON e.id = ue.enrolid
        LEFT JOIN {course} c ON c.id = e.courseid
        WHERE " . $str_conditions;

        $users_in_course = $DB->get_records_sql($q1);
        // print_object($users_in_course);die();
        foreach ($users_in_course as $key => $value) {
            $user_completion_record = static::check_user_course_completion($args['course_id'], $key);
            if ($user_completion_record['is_completed'] == 1) {
                if (!empty($args['completed_from']) 
                && !empty($args['completed_to']) 
                && ($user_completion_record['completiondate'] > $args['completed_to'] || $user_completion_record['completiondate'] < $args['completed_from'])){
                    unset($users_in_course[$key]);
                }else{
                    $value->completionstatus = 1;
                    $value->completiondate = $user_completion_record['completiondate'];
                }
            }else{
                unset($users_in_course[$key]);
            }
        }
        // print_object($users_in_course);
        // die();
        return $users_in_course;
    }

    static function check_user_course_completion($course_id, $user_id)
    {
        global $DB;
        $q = "SELECT cm.id, m.name as type, cm.completion as needs_completed, cmc.completionstate as completionstatus, cmc.timemodified as completiondate
        FROM {course} c
        JOIN {course_modules} cm ON c.id = cm.course
        JOIN {modules} m ON m.id = cm.module
        LEFT JOIN {course_modules_completion} cmc ON (cmc.userid = $user_id AND cmc.coursemoduleid = cm.id)
        WHERE c.id = $course_id AND cm.completion <> 0 AND cm.deletioninprogress <> 1";
        $results = $DB->get_records_sql($q);
        
        $return = [];
        $return['is_completed'] = true;
        foreach ($results as $key => $value) {
            if ($value->completionstatus != 1) {
                $return['is_completed'] = false;
                break;
            }else{
                $arr_completiondate[] = $value->completiondate;
            }
        }

        if ($return['is_completed'] == true){
            $return['completiondate'] = max($arr_completiondate);
        }
        return $return;
    }
}
