<?php

use core\event\user_loggedin;

require_once(__DIR__ . '/../lib.php');

class tool_managerolerules_observer {
    /**
     * @param user_loggedin $event
     * @return void
     */
    public static function user_loggedin($event) {
        global $DB, $CFG;

        $user = $event->get_record_snapshot('user', $event->userid);
        $uid = $event->userid;

        // Get advanced user data.
        require_once("$CFG->dirroot/user/profile/lib.php");
        profile_load_custom_fields($user);

        $userprofile = $user->profile;
        $userprofileid = array();

        if (empty($userprofile)) {
            return;
        }

        $system_contextid = context_system::instance()->id;

        foreach ($userprofile as $shortname => $value) {
            $userprofileid[$DB->get_field('user_info_field', 'id', array('shortname' => $shortname))] = $value;
        }

        $roleconditions = $DB->get_records('role_setting_conditions');

        $rulelist = $DB->get_fieldset_select('role_setting_rules', 'id', null);

        foreach ($rulelist as $key => $ruleid) {
            foreach ($userprofileid as $fieldid => $value) {
                foreach ($roleconditions as $conditionid => $condition) {
                    $condition_code = explode(',', $condition->condition_code);
                    // If user field id is in condition list
                    if ($condition->rule_id == $ruleid && $fieldid == $condition_code[0]) {
                        switch ($condition_code[1]) {
                            case '=': //Equal to
                                if (strtolower($value) <> strtolower($condition_code[2])) {
                                    unset($rulelist[$key]);
                                } break;
                            case '<>': //Not equal to
                                if (strtolower($value) == strtolower($condition_code[2])) {
                                    unset($rulelist[$key]);
                                } break;
                            case 'LIKE':
                                if (strpos(strtolower($value),strtolower($condition_code[2]))===FALSE) {
                                    unset($rulelist[$key]);
                                } break;
                            case 'NOT LIKE':
                                if (strpos(strtolower($value),strtolower($condition_code[2]))!==FALSE) {
                                    unset($rulelist[$key]);
                                } break;
                        }
                    }
                }
            }
        }
        // echo '<pre>Rules related - '.print_r($rulelist, true).'</pre>';

        // Add user id to role_setting_rule_members table
        if (!empty($rulelist)) {
            foreach ($rulelist as $key => $ruleid) {
                role_login_rule_add_member($ruleid, $uid);
            }
        }

        // List all role setting that apply these rules
        $rolesettings = $DB->get_records('role_setting', null, null, 'role_id, rules_combination');
        $roleids = array();

        // echo '<pre>All role settings - '.print_r($rolesettings, true).'</pre>';

        foreach ($rolesettings as $setting) {
            $ruleids = explode(' OR ', $setting->rules_combination);
            foreach ($rulelist as $ruleid) {
                if (in_array($ruleid, $ruleids)) {
                    array_push($roleids, $setting->role_id);
                }
            }
        }

        // echo '<pre>'.print_r($roleids, true).'</pre>';

        // Enroll user to roles
        if (!empty($roleids)) {
            foreach ($roleids as $key => $roleid) {
                role_assign($roleid, $uid, $system_contextid);
            }
        }
    }
}
