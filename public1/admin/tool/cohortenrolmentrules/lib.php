<?php

require_once($CFG->dirroot . '/cohort/lib.php');       // for adding users to a cohort: no need  so far
require_once($CFG->dirroot . '/cohort/locallib.php');  // for adding users to a cohort

define('ALL_USER_COHORT_NAME', 'All users');

//==================== Cohort Setting Functions //====================

function cohortsetting_get_all_rules() {
    global $DB;

    $fields = "SELECT * ";
    $sql = " FROM {cohort_setting_rules}";
    $params = array();
    $wheresql = ' order by rule_name ASC';
    $rules = $DB->get_records_sql($fields . $sql . $wheresql);

    return $rules;
}


function cohortsetting_delete_rule($ruleid) {
    global $DB;

    // List all the cohort settings available
    $cohortsettings = $DB->get_records('cohort_setting');
    // echo '<pre>'.print_r($cohortsettings, true).'</pre>'; 
    
    $cohortsettings_updated = $cohortsettings;

    foreach ($cohortsettings as $id => $setting) {
        if(!$DB->record_exists('cohort',array('id'=>$setting->cohort_id))) continue;
        $rules_combination[$id] = explode(' OR ', $setting->rules_combination);

        // Remove the rule id and rule sql that have been deleted from the setting and keep the remaining rules
        foreach ($rules_combination[$id] as $key => $rule_id) {
            if ($ruleid === $rule_id) {
                unset($rules_combination[$id][$key]);
            }
        }       
        $cohortsettings_updated[$id]->rules_combination = implode(' OR ', $rules_combination[$id]);
    }
    // echo '<pre>'.print_r($cohortsettings_updated, true).'</pre>';
    
    // Update the setting back to the DB
    foreach ($cohortsettings_updated as $id => $setting) {
        if($setting->rules_combination!=$cohortsettings[$id]->rules_combination){
            $cohort_setting_updated = new stdClass();
            $cohort_setting_updated->id = $id;
            $cohort_setting_updated->rules_combination = $setting->rules_combination;
            $DB->update_record('cohort_setting', $cohort_setting_updated);

            // Remove all users from the cohort. We need to add users back again based on a updated SQL
            $DB->delete_records('cohort_members', array('cohortid'=>$setting->cohort_id));
        }else unset($cohortsettings_updated[$id]);
    }

    // Add users back to a cohort
    $useridlist = array();

    foreach ($cohortsettings_updated as $id => $setting) {
        if(!$DB->record_exists('cohort',array('id'=>$setting->cohort_id))) continue;
        if (trim($setting->rules_combination) != '') {
            $ruleids = explode(' OR ', $setting->rules_combination);
            // echo '<pre>'.print_r($ruleids, true).'</pre>';
            
            foreach ($ruleids as $key => $id) {
                $rulesql = $DB->get_field('cohort_setting_rules', 'conditions_sql_detail', array('id'=>$id));
                $userids = $DB->get_records_sql($rulesql);              
                foreach ($userids as $userid => $value) {
                    array_push($useridlist, $userid);
                }
            }

            // Remove redundant user id. This is the list of user id to add to a cohort
            $userids = array_unique($useridlist);

            // Add updated users to a cohort
            foreach ($userids as $userid) {
                cohort_add_member($setting->cohort_id, $userid);
            }
        }
    }   

    // echo '<pre>'.print_r($userids, true).'</pre>';
    // echo '<pre>'.print_r($ruleid, true).'</pre>';
    // die();

    // Delete all details of selected rules and conditions
    $DB->delete_records('cohort_setting_rules', array('id'=>$ruleid));
    $DB->delete_records('cohort_setting_rule_members', array('ruleid'=>$ruleid));
    $DB->delete_records('cohort_setting_conditions', array('rule_id'=>$ruleid));
}


function cohortsetting_rule_add_member($ruleid, $userid) {
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

function is_builtin_cohort($cohort) {
    return strtolower($cohort->name) == strtolower(ALL_USER_COHORT_NAME);
}

function get_cohort_rules_all_profile_fields(){
    global $DB;
    $array = array(''=>'None');
    $rs = $DB->get_records('user_info_field',array(),'','id,name');
    if(!empty($rs)){
        foreach ($rs as $row) {
            $array[$row->id] = $row->name;
        }
    }
    return $array;
}
function get_cohort_rule_fieldid(){
    return get_config('tool_cohortenrolmentrules','profilefield');
}
//
function generate_cohort_rule_based_profile_fields(){
global $DB,$CFG;
    require_once($CFG->dirroot.'/cohort/lib.php');
    require_once($CFG->dirroot.'/user/profile/lib.php');

    $rule_field_id = get_cohort_rule_fieldid();
    if((!$rule_field_id)|| $rule_field_id=="") return;

    $fieldname = $DB->get_field('user_info_field','name',array('id'=>$rule_field_id));
    //Get cohort names which will be generated.
    $user_field_sql = "SELECT id 
                       FROM   {user_info_field}
                       WHERE  {user_info_field}.name = ?";


    $sql="SELECT    mdl_user.id , profile_fieldname.data AS fieldname 
                                    FROM      mdl_user 
                                    LEFT JOIN mdl_user_info_data profile_fieldname
                                    ON        mdl_user.id = profile_fieldname.userid AND profile_fieldname.fieldid = ?
                                    WHERE     mdl_user.id not in (1) 
                                    AND       mdl_user.deleted = 0 AND profile_fieldname.data !='' group by profile_fieldname.data";

    $rs = $DB->get_records_sql($sql,array($rule_field_id));

    if(!empty($rs)){
        foreach ($rs as $row) {
            $cohortname = $row->fieldname;
            //check if the rule has been existing or not
            $row->departmentname = str_replace("'","\'", $row->fieldname);
            if(!$DB->record_exists('cohort_setting_rules',array('rule_name'=>$cohortname))){
                $new_rule = (object)array(
                    'rule_name'=>$cohortname,
                    'conditions_sql'=>'(fieldid = '.$rule_field_id.' and data = "'.$row->fieldname.'") AND ',
                    'conditions_sql_detail'=>'SELECT u.id as userid FROM mdl_user as u INNER JOIN (select userid from mdl_user_info_data where (fieldid = '.$rule_field_id.' and data = "'.$row->fieldname.'")) as uid0 ON uid0.userid = u.id  
            WHERE id>1 and confirmed=1 and deleted=0  GROUP BY userid'
                );
                $ruleid = $DB->insert_record('cohort_setting_rules',$new_rule);
                //There are two conditions for each rule
                $condition1 = (object)array(
                    'rule_id'=>$ruleid,
                    'condition_sql'=>'(fieldid = '.$rule_field_id.' and data = "'.$row->fieldname.'")',
                    'condition_code'=>$rule_field_id.',=,'.$row->fieldname,
                    'description'=>$fieldname.' is equal to '.$row->fieldname
                );
                $conditionid1 = $DB->insert_record('cohort_setting_conditions',$condition1);

                //check if the cohort has been existing or not. Then assign this rule to the cohort
                $cohort_record = $DB->get_record('cohort', array('name'=>$cohortname));
                if(empty($cohort_record)){
                    $new_cohort = (object)array(
                        'name'=>$cohortname,
                        'description'=>$cohortname,
                        'contextid'=>'1'
                    );
                    $cohortid = cohort_add_cohort($new_cohort);
                    $setting = (object) array(
                        'cohort_id'=>$cohortid,
                        'rules_combination'=>$ruleid
                    );
                    $DB->insert_record('cohort_setting',$setting);
                }
            }
        }
    }
}