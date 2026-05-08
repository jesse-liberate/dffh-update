<?php
define('CLI_SCRIPT', true);
require(realpath(dirname(__FILE__)) . '/../../../config.php');

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/accesslib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once('lib.php');

global $DB,$DESTINATION;

// generate_empty_user_info_data_for_role();

// Need to add $CFG->clientroot in config.php
$DESTINATION = $CFG->dataroot."/role_rules.log";


$dbman = $DB->get_manager();
$table = new xmldb_table('tmp_role_assignments');
$table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
$table->add_field('roleid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
$table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
$dbman->create_temp_table($table);

$table2 = new xmldb_table('tmp_role_rule_members');
$table2->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
$table2->add_field('ruleid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
$table2->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);

$table2->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

$dbman->create_temp_table($table2);
$system_contextid = $DB->get_field('context','id',array('contextlevel'=>SYSTEM_CONTEXT_LEVEL));

$rs = $DB->get_records('role_setting');
if(!empty($rs)){
    foreach ($rs as $setting) {
        $roleid = $setting->role_id;
        $rule_ids = explode(' OR ',$setting->rules_combination);
        $arr_users = array();
        foreach ($rule_ids as $ruleid) {
            $rule = $DB->get_record('role_setting_rules',array('id'=>$ruleid));
            $rs_users = $DB->get_records_sql($rule->conditions_sql_detail);
            if(!empty($rs_users)){
                foreach ($rs_users as $user) {
                    //Avoid two rules add the same user
                    if(!in_array($user->userid,$arr_users)){
                        $arr_users [] = $user->userid;
                        $new = new stdClass();
                        $new->userid = $user->userid;
                        $new->roleid = $roleid;
                        $DB->insert_record('tmp_role_assignments',$new);
                    }
                    //Add user which has fired by the rule
                    $new2 = new stdClass();
                    $new2->ruleid = $ruleid;
                    $new2->userid = $user->userid;
                    $DB->insert_record('tmp_role_rule_members',$new2);
                    
                }
            }
        }
    }
    //=================================================
    //Start compare and add, delete users into the role
    // $rs1 = $DB->get_records('tmp_role_assignments');
    // $rs2 = $DB->get_records('tmp_role_rule_members');
    // echo "<pre>".print_r($rs1,true)."</pre>";
    // echo "<pre>".print_r($rs2,true)."</pre>";
    // die();
    try{
        $transaction = $DB->start_delegated_transaction();
        //role SETTING RULE MEMBERS
        //==================================
        $sql="SELECT * FROM {tmp_role_rule_members} where  (ruleid,userid) not in(select ruleid,userid from {role_rule_members})";
        $rs = $DB->get_records_sql($sql);
        if(!empty($rs)){
            foreach ($rs as $row) {
                $new = new stdClass();
                $new->ruleid = $row->ruleid;
                $new->userid = $row->userid;
                $new->timeadded = time();
                $member_rule_id = $DB->insert_record('role_rule_members',$new);
                // error_log("role setting rule: userid- $row->userid  (rule- $row->ruleid , role_rule_members-id: $member_rule_id \n", 3, $DESTINATION);
            }
        }

        //==================================
        //ADD NEW RECORDS INTO role_MEMBERS
        $sql="SELECT * FROM {tmp_role_assignments} where (roleid,userid) not in(select roleid,userid from {role_assignments} where contextid=".$system_contextid.")";
        $rs = $DB->get_records_sql($sql);
        if(!empty($rs)){
            foreach ($rs as $row) {
               $assign_id = role_assign($row->roleid, $row->userid, $system_contextid);
               error_log(date('d/m/Y - H:i')." Assign to rule: userid- $row->userid  (rule- $row->roleid , role_assignments-id: $assign_id \n", 3, $DESTINATION);
            }
        }
        $transaction->allow_commit();

    } catch(Exception $e) {
        //$transaction->rollback($e);
        // var_dump($e);
        echo "Error: process cannot create All users role and add users." . "</br>";
        // continue;
    }finally{
        // Remove rule-member which has been changed
        $sql="DELETE FROM {role_rule_members} where (ruleid,userid) not in(select ruleid,userid from {tmp_role_rule_members})";
        $DB->execute($sql);

        // Some cases can be happen: profile fields had been changed, therefore, we need to clear all other roles before applying
        //REMOVE THE RECORDS WHICH HAS BEEN CHANGED
        $sql="DELETE FROM {role_assignments} where roleid in(select role_id from {role_setting} group by role_id) AND contextid=".$system_contextid." AND (roleid,userid) not in(select roleid,userid from {tmp_role_assignments})";
        $DB->execute($sql);
    }

}

echo "All rules have been fired successfully." . "</br>";
$dbman->drop_table($table);
$dbman->drop_table($table2);

?>