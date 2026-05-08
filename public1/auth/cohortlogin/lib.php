<?php

require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
/**
 * This function check whether cohort setting admin tool plugin is installed
 *
 * @param
 * @return true or false
 */

function is_cohortenrolmentrules_installed() {
  global $DB;
  return $DB->record_exists('config_plugins',array('plugin'=>'tool_cohortenrolmentrules'));
}


function cohort_login_rule_add_member($ruleid, $userid) {
    global $DB;

    $isExisted = $DB->record_exists('cohort_setting_rule_members', array('ruleid'=>$ruleid, 'userid'=>$userid));

    if (!$isExisted) {            
        $date = new DateTime();
        $cohort_setting_rule_members_record = new stdClass();
        $cohort_setting_rule_members_record->ruleid = $ruleid;
        $cohort_setting_rule_members_record->userid = $userid;
        $cohort_setting_rule_members_record->timeadded = $date->getTimestamp();
        $DB->insert_record('cohort_setting_rule_members', $cohort_setting_rule_members_record);
    }

}

//COMPARE TWO COHORT_MEMBERS AND TMP_COHORT_MEMBERS TO FIND THE DIFFERENT
//ONLY APPLY FOR RELATIVE COHORT WITH THE RULES
function cohort_team_member($userid,$alluser_cohortid){
    global $DB;
    try{
        $transaction = $DB->start_delegated_transaction();
        //COHORT SETTING RULE MEMBERS
        //==================================
        $sql="SELECT * FROM {tmp_cohort_rule_members} where userid=".$userid." and ruleid not in(select ruleid from {cohort_setting_rule_members} where userid=".$userid.")";
        $rs = $DB->get_records_sql($sql);
        if(!empty($rs)){
            foreach ($rs as $row) {
                $new = new stdClass();
                $new->ruleid = $row->ruleid;
                $new->userid = $row->userid;
                $new->timeadded = time();
                $DB->insert_record('cohort_setting_rule_members',$new);
            }
        }

        //==================================
        //ADD NEW RECORDS INTO COHORT_MEMBERS
        $sql="SELECT * FROM {tmp_cohort_members} where userid=".$userid." and cohortid not in(select cohortid from {cohort_members} where userid=".$userid.")";
        $rs = $DB->get_records_sql($sql);
        if(!empty($rs)){
            foreach ($rs as $row) {
                cohort_add_member($row->cohortid, $row->userid);
            }
        }
        $transaction->allow_commit();

    } catch(Exception $e) {
        //$transaction->rollback($e);
        echo "Error: process cannot create All users cohort and add users." . "</br>";
    }finally{
        // Remove rule-member which has been changed
        $sql="DELETE FROM {cohort_setting_rule_members} where userid=".$userid." and ruleid not in(select ruleid from {tmp_cohort_rule_members} where userid=".$userid.")";
        $DB->execute($sql);

        // Some cases can be happen: profile fields had been changed, therefore, we need to clear all other cohorts before applying
        //REMOVE THE RECORDS WHICH HAS BEEN CHANGED
        //DO NOT REMOVE COHORT MEMBERS THAT ARE MANUALLY ADDED
        $sql="SELECT cohortid
            FROM {cohort_members} cm
            JOIN {cohort} c
            ON c.id = cm.cohortid
            WHERE cohortid <> ?
            AND userid = ?
            AND cohortid IN (
                SELECT cohort_id
                FROM {cohort_setting}
            ) AND cohortid NOT IN (
                SELECT cohortid
                FROM {tmp_cohort_members}
                WHERE userid = ?
            )";
        $cohort_ids = $DB->get_records_sql($sql, [
            $alluser_cohortid, $userid, $userid
        ]);

        foreach ($cohort_ids as $cohort_id => $ignored) {
            cohort_remove_member($cohort_id, $userid);
        }
    }
}
function run_update_user_cohorts(){
    global $DB,$USER;
    $user = $USER;
    // var_dump($user);
    $uid = $user->id;

    // Get advanced user data.
    profile_load_data($user);
    profile_load_custom_fields($user);

    $userprofile = $user->profile;
    $userprofileid = array();

    $defaultfields = array('country','lang'); // If we want to apply more default fields, add more value to this array
    $user_record = $DB->get_record('user',array('id'=>$uid));
    /* --------------------- Get necessary default --------------------- */
    foreach ($defaultfields as $default_field) {
        $userprofileid[$default_field] = $user_record->$default_field;
    }

    /*---------------------------- Create temporary tables  --------------------------------*/
    $dbman = $DB->get_manager();
    $table = new xmldb_table('tmp_cohort_members');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('cohortid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    $dbman->create_temp_table($table);

    $table2 = new xmldb_table('tmp_cohort_rule_members');
    $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table2->add_field('ruleid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
    $table2->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);

    $table2->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    $dbman->create_temp_table($table2);

    /*---------------------------- Add all users to System cohort once logged in  --------------------------------*/

    // Create allusers system cohort if it is not existed
    $allusersExisted = $DB->record_exists('cohort', array('name'=>'All Users'));

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
        $alluser_cohortid = $DB->get_field('cohort', 'id', array('name'=>'All Users'));
    }

    // Add the logged user to the allusers cohort if they are not in yet and temp table
    $new = new stdClass();
    $new->userid = $uid;
    $new->cohortid = $alluser_cohortid;
    $DB->insert_record('tmp_cohort_members',$new);

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

        // Add user id to cohort_setting_rule_members table
        if (!empty($rulelist)) {
            foreach ($rulelist as $key => $ruleid) {
                if(!$DB->record_exists('tmp_cohort_rule_members',array('ruleid'=>$ruleid,'userid'=>$uid))){
                    $new = new stdClass();
                    $new->ruleid = $ruleid;
                    $new->userid = $uid;
                    $new->timeadded = time();
                    $DB->insert_record('tmp_cohort_rule_members',$new);
                }
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

        if (!empty($cohortids)) {

            //ADD USERS INTO THE COHORT
            foreach ($cohortids as $cohortid) {
                if(!$DB->record_exists('tmp_cohort_members',array('userid'=>$uid,'cohortid'=>$cohortid))){
                    $new = new stdClass();
                    $new->userid = $uid;
                    $new->cohortid = $cohortid;
                    $DB->insert_record('tmp_cohort_members',$new);
                }
            }
        }

        cohort_team_member($uid,$alluser_cohortid);

    }

    $dbman->drop_table($table);
    $dbman->drop_table($table2);
}