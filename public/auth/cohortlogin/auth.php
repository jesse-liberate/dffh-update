<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    auth
 * @subpackage cohortsetting
 * @copyright  2016 Charlie Tran (charlie@mindatlas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/auth/cohortlogin/lib.php');
require_once($CFG->dirroot.'/auth/manual/auth.php');

class auth_plugin_cohortlogin extends auth_plugin_manual {

    const COMPONENT_NAME = 'auth_cohortlogin';
    
    public $mustache;

    /**
     * Constructor.
     */
    public function __construct() {
        global $CFG;
        require_once($CFG->dirroot . '/lib/mustache/src/Mustache/Autoloader.php');

        $this->authtype = 'cohortlogin';
        $this->config = get_config(self::COMPONENT_NAME);
        Mustache_Autoloader::register();

        $this->mustache = new Mustache_Engine;
    }

    /**
     * Post authentication hook.
     * This method is called from authenticate_user_login() for all enabled auth plugins.
     *
     * @param object $user user object, later used for $USER
     * @param string $username (with system magic quotes)
     * @param string $password plain text password (with system magic quotes)
     */
    public function user_authenticated_hook(&$user, $username, $password) {
        
        
        mail('jesse.shrock@liberateglobal.com','cohort adjustment method','user_authenticated_hook');

        $uid = $user->id;

        // Get advanced user data.
        profile_load_data($user);
        profile_load_custom_fields($user);

        $userprofile = $user->profile;
        $userprofileid = array();

        /*---------------------------- Add all users to System cohort once logged in  --------------------------------*/

        // Create allusers system cohort if it is not existed
        $allusersExisted = $DB->record_exists('cohort', array('name'=>get_string('allusers','auth_cohortlogin')));
        
        if (!$allusersExisted) {
            $alluser_cohort = new stdClass();
            $alluser_cohort->contextid = 1;
            $alluser_cohort->name = get_string('allusers','auth_cohortlogin');
            $alluser_cohort->idnumber = get_string('allusers','auth_cohortlogin');
            $alluser_cohort->description = get_string('allusers:desc','auth_cohortlogin');
            $alluser_cohort->descriptionformat = 1;
            $alluser_cohort->visible = 1;
            $alluser_cohort->component = '';
            $alluser_cohort->timecreated = time();
            $alluser_cohort->timemodified = time();

            $alluser_cohortid = $DB->insert_record('cohort', $alluser_cohort);
        } else {
            $alluser_cohortid = $DB->get_field('cohort', 'id', array('name'=>get_string('allusers','auth_cohortlogin')));
        }

        // Add the logged user to the allusers cohort if they are not in yet
        cohort_add_member($alluser_cohortid, $uid);

        /*-------------------------------------------------------------------------------------------------------------*/
        
        if (is_cohortenrolmentrules_installed() && !empty($userprofile)) {
            foreach ($userprofile as $shortname => $value) {
                $userprofileid[$DB->get_field('user_info_field', 'id', array('shortname'=>$shortname))] = $value;
            }
            // echo '<pre>'.'User profile - '.print_r($userprofile, true).'</pre>'; 

            $cohortconditions = $DB->get_records('cohort_setting_conditions');        

            // echo '<pre>All Cohort Conditions - '.print_r($cohortconditions, true).'</pre>'; 

            // echo '<pre>'.'User profile IDs - '.print_r($userprofileid, true).'</pre>'; 

            $rulelist = $DB->get_fieldset_select('cohort_setting_rules', 'id', null);

            // echo '<pre>'.'All Rule IDs - '.print_r($rulelist, true).'</pre>'; 

            /*  Charlie - Algorithm 
                First get all the Rules
                Then go through user profile fields one by one, compare to the all condittions
                If not matching, remove the rule from the rule list
            */

            foreach ($rulelist as $key => $ruleid) {
                foreach ($userprofileid as $fieldid => $value) {
                    foreach ($cohortconditions as $conditionid => $condition) {
                        $condition_code = explode(',', $condition->condition_code);
                        // If user field id is in condition list
                        if ($condition->rule_id == $ruleid && $fieldid == $condition_code[0]) {
                            if ($condition_code[1] === '=' || $condition_code[1] === 'LIKE') {
                                if ($value <> $condition_code[2]) {
                                    unset($rulelist[$key]);
                                } 
                            }
                            if ($condition_code[1] === '<>' || $condition_code[1] === 'NOT LIKE') {
                                if ($value == $condition_code[2]) {
                                    unset($rulelist[$key]);
                                }
                            }
                        }
                    }
                }
            }
            // echo '<pre>Rules related - '.print_r($rulelist, true).'</pre>';

            // Add user id to cohort_setting_rule_members table
            if (!empty($rulelist)) {
                foreach ($rulelist as $key => $ruleid) {
                    cohort_login_rule_add_member($ruleid, $uid);
                }
            }
          
            // List all cohort setting that apply these rules
            $cohortsettings = $DB->get_records('cohort_setting', null, null, 'cohort_id, rules_combination');         
            $cohortids = array();

            // echo '<pre>All cohort settings - '.print_r($cohortsettings, true).'</pre>';

            foreach ($cohortsettings as $setting) {
                $ruleids = explode(' OR ', $setting->rules_combination);
                foreach ($rulelist as $ruleid) {
                    if (in_array($ruleid, $ruleids)) {
                        array_push($cohortids, $setting->cohort_id);
                    }
                }
            }

            // echo '<pre>'.print_r($cohortids, true).'</pre>';

            // Enroll user to cohorts
            if (!empty($cohortids)) {
                foreach ($cohortids as $key => $id) {
                    cohort_add_member($id, $uid);
                }
            }

        }
    }

}
