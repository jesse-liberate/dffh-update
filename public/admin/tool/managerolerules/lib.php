<?php
DEFINE('SYSTEM_CONTEXT',1);
DEFINE('SYSTEM_CONTEXT_LEVEL',10);
require_once($CFG->dirroot . '/admin/roles/lib.php');
require_once($CFG->dirroot . '/lib/accesslib.php');

function get_all_assign_roles(){
	global $DB,$CFG;
	$html="";
	$html.="<table class='tablesorter' id='report'>";
	$html.="<thead>";
	$html.="<tr>";
	$html.="<th>".get_string('rolename','tool_managerolerules')."</th>";
	$html.="<th>".get_string('applyrules','tool_managerolerules')."</th>";
	$html.="<th>".get_string('usersize','tool_managerolerules')."</th>";
	$html.="<th>Action</th>";
	$html.="</tr>";
	$html.="</thead><tbody>";
	$sql='SELECT r.id,r.name,r.shortname,rs.rules_combination from {role_setting} as rs INNER JOIN {role} r on r.id=rs.role_id group by r.id ORDER BY r.name ASC';
	$rs = $DB->get_records_sql($sql);
	// echo $sql;
	// die();
	$system_contextid = $DB->get_field('context','id',array('contextlevel'=>SYSTEM_CONTEXT_LEVEL));
	if(!empty($rs)){
		foreach ($rs as $row) {
			$num_users = $DB->count_records('role_assignments',array('roleid'=>$row->id));
			$rule_names = get_rules_combine_names($row->rules_combination);
			$url = '<a href="role_setting_edit.php?id='.$row->id.'"><img src="js/edit.png"></a>';
			$url .= ' <a href="'.$CFG->wwwroot.'/admin/roles/assign.php?contextid='.$system_contextid.'&roleid='.$row->id.'"><img src="js/users.png"></a>';
			$url .= ' <a href="role_setting_delete.php?id='.$row->id.'"><img src="js/delete.png"></a>';
			$html.="<tr>";
			$html.="<td>".$row->name." - ".$row->shortname."</td>";
			$html.="<td>".$rule_names."</td>";
			$html.="<td>".$num_users."</td>";
			$html.="<td>".$url."</td>";
			$html.="</tr>";
		}
	}
	$html.="</tbody></table>";
	return $html;
}
function get_rules_combine_names($rule_combine_ids){
	global $DB;
	$array = explode(' OR ',$rule_combine_ids);
	$html="";
	if(!empty($array)&&$rule_combine_ids!=NULL){
		$rule_names = array();
		$sql='SELECT rsr.id, rsr.rule_name from {role_setting_rules} as rsr where rsr.id in('.implode(",", $array).')';
		// echo $sql."<br>";
		$rs = $DB->get_records_sql($sql);
		if(!empty($rs)){
			foreach ($rs as $row) {
				$rule_names [] = $row->rule_name;
			}
			$html.= implode(' OR ',$rule_names);
		}
	}
	return $html;
}
//==================== role Setting Functions //====================

function rolesetting_get_all_rules() {
	global $DB;

	$fields = "SELECT * ";
    $sql = " FROM {role_setting_rules}";
    $params = array();
    $wheresql = '';
    $rules = $DB->get_records_sql($fields . $sql . $wheresql);

    return $rules;
}
//DELETE SETTING
function rolesetting_delete($settingid){
	global $DB;
	//remove all users assign by this role setting. Assumption that there is only this setting applied for the role
	$record = $DB->get_record('role_setting',array('id'=>$settingid));
	if(!empty($record)){
		$roleid =$record->role_id;
		$system_contextid = $DB->get_field('context','id',array('contextlevel'=>SYSTEM_CONTEXT_LEVEL));

		$DB->delete_records('role_assignments',array('roleid'=>$roleid,'contextid'=>$system_contextid));
		$DB->delete_records('role_setting',array('id'=>$settingid));
	}
}
//DELETE THE RULE
function rolesetting_delete_rule($ruleid) {
	global $DB;

	// List all the role settings available
	$rolesettings = $DB->get_records('role_setting');
	// echo '<pre>'.print_r($rolesettings, true).'</pre>'; 
	
	$rolesettings_updated = $rolesettings;

	foreach ($rolesettings as $id => $setting) {
		$rules_combination[$id] = explode(' OR ', $setting->rules_combination);

		// Remove the rule id and rule sql that have been deleted from the setting and keep the remaining rules
		foreach ($rules_combination[$id] as $key => $rule_id) {
			if ($ruleid === $rule_id) {
				unset($rules_combination[$id][$key]);
			}
		}		
		$rolesettings_updated[$id]->rules_combination = implode(' OR ', $rules_combination[$id]);
	}
	// echo '<pre>'.print_r($rolesettings_updated, true).'</pre>';
	$system_contextid = $DB->get_field('context','id',array('contextlevel'=>SYSTEM_CONTEXT_LEVEL));

	// Update the setting back to the DB
	foreach ($rolesettings_updated as $id => $setting) {
		$role_setting_updated = new stdClass();
		$role_setting_updated->id = $id;
	    $role_setting_updated->rules_combination = $setting->rules_combination;
	    $DB->update_record('role_setting', $role_setting_updated);

	    // Remove all users from the role. We need to add users back again based on a updated SQL
		$DB->delete_records('role_assignments',array('roleid'=>$setting->role_id,'contextid'=>$system_contextid));
	}


	foreach ($rolesettings_updated as $id => $setting) {
		if (trim($setting->rules_combination) != '') {
			// Add users back to a role
			$useridlist = array();
			$ruleids = explode(' OR ', $setting->rules_combination);
			// echo '<pre>'.print_r($ruleids, true).'</pre>';
			
			foreach ($ruleids as $key => $id) {
				$rulesql = $DB->get_field('role_setting_rules', 'conditions_sql_detail', array('id'=>$id));
				$userids = $DB->get_records_sql($rulesql);				
				foreach ($userids as $userid => $value) {
					array_push($useridlist, $userid);
				}
			}

			// Remove redundant user id. This is the list of user id to add to a role
			$userids = array_unique($useridlist);

			// Add updated users to a role
			//NEED TO CHANGE THIS
			foreach ($userids as $userid) {
				role_assign($setting->role_id, $userid, $system_contextid);
			}
		}
	}	

	// echo '<pre>'.print_r($userids, true).'</pre>';
	// echo '<pre>'.print_r($ruleid, true).'</pre>';
	// die();

	// Delete all details of selected rules and conditions
	$DB->delete_records('role_setting_rules', array('id'=>$ruleid));
	$DB->delete_records('role_rule_members', array('ruleid'=>$ruleid));
	$DB->delete_records('role_setting_conditions', array('rule_id'=>$ruleid));
}


function rolesetting_rule_add_member($ruleid, $userid) {
    global $DB;

    $isExisted = $DB->record_exists('role_rule_members', array('ruleid'=>$ruleid, 'userid'=>$userid));

    if (!$isExisted) {            
        $date = new DateTime();
        $role_setting_rule_members_record = new stdClass();
        $role_setting_rule_members_record->ruleid = $ruleid;
        $role_setting_rule_members_record->userid = $userid;
        $role_setting_rule_members_record->timeadded = $date->getTimestamp();
        $DB->insert_record('role_rule_members', $role_setting_rule_members_record);
    }

}
function generate_empty_user_info_data_for_role(){
	global $DB;
	//GET ALL FIELDS RELATING TO ROLE SETTING;
	$rs = $DB->get_records('role_setting_conditions');
	$profile_fields = array();
	if(!empty($rs)){
		foreach ($rs as $row) {
			$condition = explode(",",$row->condition_code);
			if(!array_key_exists($condition[0],$profile_fields)){
				$default_value = $DB->get_field('user_info_field','defaultdata',array('id'=>$condition[0]));
				$profile_fields[$condition[0]] = $default_value;
			}
		}
	}
	//START GENERATING EMPTY RECORD
	$sql='SELECT id from mdl_user where suspended=0 and deleted=0 and id not in(select userid from mdl_user_info_data where fieldid=?)';
	if(!empty($profile_fields)){
		foreach ($profile_fields as $key => $default_value) {
			$rs = $DB->get_records_sql($sql,array($key));
			if(!empty($rs)){
				foreach ($rs as $row) {
					if(!$DB->record_exists('user_info_data',array('fieldid'=>$key,'userid'=>$row->id))){
						$new = new stdClass();
						$new->userid = $row->id;
						$new->fieldid = $key;
						$new->data = $default_value;
						$DB->insert_record('user_info_data',$new);
					}
				}
			}
		}
	}
}

function role_login_rule_add_member($ruleid, $userid) {
    global $DB;

    $exists = $DB->record_exists('role_rule_members', array('ruleid'=>$ruleid, 'userid'=>$userid));

    if (!$exists) {
        $date = new DateTime();
        $role_setting_rule_members_record = new stdClass();
        $role_setting_rule_members_record->ruleid = $ruleid;
        $role_setting_rule_members_record->userid = $userid;
        $role_setting_rule_members_record->timeadded = $date->getTimestamp();
        $DB->insert_record('role_rule_members', $role_setting_rule_members_record);
    }

}
