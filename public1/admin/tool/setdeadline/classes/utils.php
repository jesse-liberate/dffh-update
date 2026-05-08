<?php
namespace tool_setdeadline;

class utils {
    public static function get_all_courses($fields = '*') {
        global $DB;

        return $DB->get_records_select('course', 'id <> ?', [
            SITEID
        ], 'fullname ASC', $fields);
    }

    public static function get_all_users($fields = '*') {
        global $CFG, $DB;

        return $DB->get_records_select(
            'user',
            'id <> ? AND confirmed = 1 AND deleted = 0 AND suspended = 0',
            [$CFG->siteguest],
            'firstname ASC, lastname ASC',
            $fields
        );
    }

    public static function get_all_cohorts($fields = '*') {
        global $DB;

        return $DB->get_records('cohort', null, 'name ASC', $fields);
    }

    public static function get_courses($courseids, $fields = '*') {
        global $DB;

        list($courseids_query, $courseids_params) = $DB->get_in_or_equal($courseids);

        return $DB->get_records_select('course', "id $courseids_query", $courseids_params, '', $fields);
    }

    public static function get_cohorts($cohortids, $fields = '*') {
        global $DB;

        list($cohortids_query, $cohortids_params) = $DB->get_in_or_equal($cohortids);

        return $DB->get_records_select('cohort', "id $cohortids_query", $cohortids_params, '', $fields);
    }

    public static function get_users($userids, $fields = '*') {
        global $DB;

        list($userids_query, $userids_params) = $DB->get_in_or_equal($userids);

        return $DB->get_records_select('user', "id $userids_query", $userids_params, '', $fields);
    }
}