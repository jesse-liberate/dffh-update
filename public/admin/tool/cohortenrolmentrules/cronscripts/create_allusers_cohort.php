<?php
define('CLI_SCRIPT', true);
require(realpath(dirname(__FILE__)) . '/../../../../config.php');
core_php_time_limit::raise(HOURSECS);
raise_memory_limit(MEMORY_EXTRA);
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once('../lib.php');

global $DB,$DESTINATION;

// Need to add $CFG->clientroot in config.php
$DESTINATION = $CFG->dataroot."/cohort_rules.log";
$logs_path = $CFG->dataroot.'/logs';
if (!file_exists($logs_path)) mkdir($logs_path, 0777, true);

// Delete files that older than 7 days
$logsfiles = glob($logs_path."/*");  // return the array of all files in the folder
$now = time();

foreach ($logsfiles as $file) {
    if (is_file($file))
        // If file modification time is more than 30 days, delete a file
        if ($now - filemtime($file) >= 60*60*24*30) unlink($file);
}

$logs_output = "//============================Start============================//\n";

$dbman = $DB->get_manager();
$table = new xmldb_table('tmp_cohort_members');
$table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
$table->add_field('cohortid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
$table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
$table->add_index('use_coh', XMLDB_INDEX_NOTUNIQUE, ['userid', 'cohortid']);
if($dbman->table_exists($table)){
    $dbman->drop_table($table);
}
$dbman->create_temp_table($table);

$table2 = new xmldb_table('tmp_cohort_rule_members');
$table2->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
$table2->add_field('ruleid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
$table2->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);

$table2->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
$table->add_index('use_rul', XMLDB_INDEX_NOTUNIQUE, ['userid', 'ruleid']);
if($dbman->table_exists($table2)){
    $dbman->drop_table($table2);
}
$dbman->create_temp_table($table2);


//===================================
//APPLY FOR ALL USERS COHORT AND REMOVE DELETED USERS FROM THE COHORT
try {
    $transaction = $DB->start_delegated_transaction();
    $all_user_cohort='all users';
    $sql_where = 'SELECT * FROM mdl_cohort WHERE lower(name)=?';
    // Create allusers system cohort if it is not existed
    $allusersExisted = $DB->record_exists_sql($sql_where,array($all_user_cohort));
    
    if (!$allusersExisted) {
        $alluser_cohort = new stdClass();
        $alluser_cohort->contextid = 1;
        $alluser_cohort->name = ALL_USER_COHORT_NAME;
        $alluser_cohort->idnumber = ALL_USER_COHORT_NAME;
        $alluser_cohort->description = '<p>All users in the LMS</p>';
        $alluser_cohort->descriptionformat = 1;
        $alluser_cohort->visible = 1;
        $alluser_cohort->component = '';
        $alluser_cohort->timecreated = time();
        $alluser_cohort->timemodified = time();
        $alluser_cohortid = $DB->insert_record('cohort', $alluser_cohort);

        $logs_output .= "Create the All user cohort, cohortid is: ".$alluser_cohortid."\n";

    } else {
        $all_user_cohort_record = $DB->get_record_sql($sql_where,array($all_user_cohort));
        // var_dump($all_user_cohort_record);
        $alluser_cohortid = $all_user_cohort_record->id;
    }
    // var_dump($alluser_cohortid);


    // Remove ALL DELETED USERS FROM deleted user from ALL COHORTS
    $logs_output .= "\n"."Remove deleted users from all cohort and rule:"."\n";
    $sql_delete = 'DELETE FROM {cohort_members} where userid in(select id from {user} where deleted=1)';
    $DB->execute($sql_delete);

    $sql_delete = 'DELETE FROM {cohort_setting_rule_members} where userid in(select id from {user} where deleted=1)';
    $DB->execute($sql_delete);

    // echo '<pre>'.print_r($allusersid, true).'</pre>'; die();

    //Add new user into the All user cohort
    $sql = "SELECT id,firstname,lastname from mdl_user where id>1 and confirmed=1 and deleted=0 and id not in(select userid from mdl_cohort_members where cohortid=? group by userid)";
    $all_newusersids = $DB->get_records_sql($sql,array($alluser_cohortid));
    if(!empty($all_newusersids)){
        // Only consider recently created or modified users in 24 hours
        $logs_output .= "\n"."Identified ".sizeof($all_newusersids)." new 'active' users. They will be added to All Users cohort id ".$alluser_cohortid.":\n";
        foreach ($all_newusersids as $key => $user) {
            $new = new stdClass();
            $new->userid = $user->id;
            $new->cohortid = $alluser_cohortid;
            $DB->insert_record('tmp_cohort_members',$new);

            if ( cohort_add_member($alluser_cohortid, $user->id) )
            $logs_output .= "  . Added user: ".$user->firstname." ".$user->lastname."\n";
            else $logs_output .= "  . Existing user (ignored): ".$user->firstname." ".$user->lastname."\n";
        }
    }
    $transaction->allow_commit();

} catch(Exception $e) {
    var_dump($e);
    $transaction->rollback($e);
    echo "Error: process cannot creat All users cohort and add users." . "</br>";
}

$defaultfields = array('country','lang'); // If we want to apply more default fields, add more value to this array
$sql_default_fields = implode(",", $defaultfields);
//===================================
//APPLY FOR ALL OTHER COHORT

// Get all confirmed users in the LMS, except guest user and deleted users
$sql="SELECT id,firstname,lastname,timecreated,timemodified,".$sql_default_fields." from mdl_user where id>1 and confirmed=1 and deleted=0";
$allusersid = $DB->get_records_sql($sql);
foreach ($allusersid as $key => $user) {
    $uid = $user->id;
    // Add all users to cohorts before removing userid from the list by recent changes only

      
    $timecreated = $user->timecreated;
    $timemodified = $user->timemodified;
    $currenttime = time();

//We might need to disable below commands if we run the first time: Get all users to apply for the cohort rules
    // if ($timemodified == 0) {
    //     if (round(abs($currenttime - $timecreated)/60/60) > 24) {
    //         unset($allusersid[$key]);
    //     }
    // } else {
    //     if (round(abs($currenttime - $timemodified)/60/60) > 24) {
    //         unset($allusersid[$key]);
    //     }
    // }
}

$logs_output .= "\n"."List of Cohort rules at time of execution:"."\n";

$cohort_setting_conditions = $DB->get_records('cohort_setting_conditions');

if (!empty($cohort_setting_conditions)) {
    foreach ($cohort_setting_conditions as $key => $value) {
        $logs_output .= "  . ".$DB->get_field('cohort_setting_rules','rule_name',array('id'=>$value->rule_id)).": ".$value->description."\n";
    }
} else $logs_output .= "There are no rules in the system."."\n";


// echo '<pre>'.print_r($allusersid, true).'</pre>'; die();
$logs_output .= "\n"."Identified ".sizeof($allusersid)." users that have been created/updated within 24 hours. Compare new details against cohort rules:"."\n";
$processed_user = array();


if (!empty($allusersid)) {
    $cohortconditions = $DB->get_records('cohort_setting_conditions');
    $profile_fields = $DB->get_records_menu('user_info_field', null, '' , 'shortname, id');
    $all_rules = $DB->get_fieldset_select('cohort_setting_rules', 'id', null);
    $cohortsettings = $DB->get_records_sql('SELECT cs.cohort_id, cs.rules_combination
        FROM {cohort_setting} cs
        JOIN {cohort} c
        ON c.id = cs.cohort_id');
    foreach ($allusersid as $key => $user) {
        $transaction = $DB->start_delegated_transaction();
        $uid=$user->id;
        array_push($processed_user, $uid);
        // error_log("User: $user->firstname $user->lastname ($user->id) \n", 3, $DESTINATION);
        $logs_output .= "  . User: ".$user->firstname." ".$user->lastname."\n";

        // Get advanced user data.
        // profile_load_data($user);
        profile_load_custom_fields($user);

        $userprofile = $user->profile;
        $userprofileid = array();
        // echo '<pre> userid: '.$uid.' - userprofileid: '.print_r($userprofile, true).'</pre>';
        /* --------------------- Get necessary default --------------------- */
        foreach ($defaultfields as $default_field) {
            $userprofileid[$default_field] = $user->$default_field;
        }
        // echo '<pre> userid: '.$uid.' - userprofileid: '.print_r($userprofileid, true).'</pre>';
        /* --------------------------------------------------------------------------------------- */

        // Apply enrolment rule settings
        foreach ($userprofile as $shortname => $value) {
            $userprofileid[$profile_fields[$shortname]] = $value;
        }
        // echo '<pre>'.'User profile - '.print_r($userprofileid, true).'</pre>'; 

        // echo '<pre>All Cohort Conditions - '.print_r($cohortconditions, true).'</pre>'; 
        // echo '<pre>'.'User profile IDs - '.print_r($userprofileid, true).'</pre>'; 

        $rulelist = $all_rules;

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
        // die();
        if (!empty($rulelist)) {
            foreach ($rulelist as $key => $ruleid) {
                if(!$DB->record_exists('tmp_cohort_rule_members',array('ruleid'=>$ruleid,'userid'=>$uid))){
                    $new = new stdClass();
                    $new->ruleid = $ruleid;
                    $new->userid = $uid;
                    $new->timeadded = time();
                    $DB->insert_record('tmp_cohort_rule_members',$new);
                    // error_log("Ruleid: $ruleid \n", 3, $DESTINATION);
                }
            }
        }
        
        // List all cohort setting that apply these rules       
        $cohortids = array();

        // echo '<pre>All cohort settings - '.print_r($cohortsettings, true).'</pre>';
        //GET ALL COHORT WHICH WILL BE AFFECTED FOR COHORT RULES

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
                    // error_log("Cohortid: $cohortid \n", 3, $DESTINATION);
                }
            }
        }
        $logs_output.= cohort_team_member($uid,$alluser_cohortid);
        $transaction->allow_commit();
    }
}
echo "Successful: create All users cohort and add all users to it." . "</br>";
$dbman->drop_table($table);
$dbman->drop_table($table2);
$logs_path = $logs_path."/".date("Y-m-d_h:i")."_cohort_cron.txt";
file_put_contents($logs_path,$logs_output);


//COMPARE TWO COHORT_MEMBERS AND tmp_cohort_members TO FIND THE DIFFERENT
//ONLY APPLY FOR RELATIVE COHORT WITH THE RULES
function cohort_team_member($userid,$alluser_cohortid){
    global $DB,$DESTINATION;
    $output="";
    try{
        // $transaction = $DB->start_delegated_transaction();
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
                // error_log("Cohort setting rule: userid- $row->userid  rule- $row->ruleid \n", 3, $DESTINATION);
                $output.="Cohort setting rule: userid- $row->userid  rule- $row->ruleid \n";
            }
        }

        //==================================
        //ADD NEW RECORDS INTO COHORT_MEMBERS
        $sql="SELECT * FROM {tmp_cohort_members} where userid=".$userid." and cohortid not in(select cohortid from {cohort_members} where userid=".$userid.")";
        $rs = $DB->get_records_sql($sql);
        if(!empty($rs)){
            foreach ($rs as $row) {
                cohort_add_member($row->cohortid, $row->userid);
                error_log("Cohort member: userid- $row->userid  cohortid- $row->cohortid \n", 3, $DESTINATION);
                $output.="Cohort member: userid- $row->userid  cohortid- $row->cohortid \n";
            }
        }
        // $transaction->allow_commit();

    } catch(Exception $e) {
        //$transaction->rollback($e);
        echo "Error: process cannot create All users cohort and add users." . "</br>";
    }finally{
        // Remove rule-member which has been changed
        $output.="Remove user from the previous cohort";
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
?>