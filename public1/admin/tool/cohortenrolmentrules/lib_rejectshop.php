<?php
//Generate all rules for all stores - RejectShop rules
function generate_update_cohort_rule_all_stores(){
global $DB,$CFG;
	require_once($CFG->dirroot.'/cohort/lib.php');
	require_once($CFG->dirroot.'/user/profile/lib.php');

	//Get cohort names which will be generated.
	$user_field_sql = "SELECT id 
	                   FROM   {user_info_field}
	                   WHERE  {user_info_field}.name = ?";

	$field_departmentname_id  = $DB->get_field_sql($user_field_sql, array('Store Location'));
	$field_regioncode_id  = $DB->get_field_sql($user_field_sql, array('Region Code'));

	$sql="SELECT    mdl_user.id , profile_departmentname.data AS departmentname, profile_regioncode.data AS regioncode 
	                                FROM      mdl_user 
	                                LEFT JOIN mdl_user_info_data profile_departmentname
	                                ON        mdl_user.id = profile_departmentname.userid AND profile_departmentname.fieldid = ?
	                                LEFT JOIN mdl_user_info_data profile_regioncode
	                                ON        mdl_user.id = profile_regioncode.userid AND profile_regioncode.fieldid = ?
	                                WHERE     mdl_user.id not in (1) 
	                                AND       mdl_user.deleted = 0 AND profile_departmentname.data not in('STORE SUPPORT CENTRE','DCIP','DCHA') and profile_departmentname.data !='' group by profile_departmentname.data, profile_regioncode.data";

	$rs = $DB->get_records_sql($sql,array($field_departmentname_id, $field_regioncode_id));

	if(!empty($rs)){
		foreach ($rs as $row) {
			$cohortname = trim($row->regioncode)."-".trim($row->departmentname)." Store";
			//check if the rule has been existing or not
			$row->departmentname = str_replace("'","\'", $row->departmentname);
			if(!$DB->record_exists('cohort_setting_rules',array('rule_name'=>$cohortname))){
				$new_rule = (object)array(
					'rule_name'=>$cohortname,
					'conditions_sql'=>'(fieldid = '.$field_departmentname_id.' and data = "'.$row->departmentname.'") AND (fieldid = '.$field_regioncode_id.' and data = "'.$row->regioncode.'") and ',
					'conditions_sql_detail'=>'SELECT u.id as userid FROM mdl_user as u INNER JOIN (select userid from mdl_user_info_data where (fieldid = '.$field_departmentname_id.' and data = "'.$row->departmentname.'")) as uid0 ON uid0.userid = u.id INNER JOIN (select userid from mdl_user_info_data where (fieldid = '.$field_regioncode_id.' and data = "'.$row->regioncode.'")) as uid1 ON uid1.userid = u.id 
			WHERE id>1 and confirmed=1 and deleted=0  GROUP BY userid'
				);
				$ruleid = $DB->insert_record('cohort_setting_rules',$new_rule);
				//There are two conditions for each rule
				$condition1 = (object)array(
					'rule_id'=>$ruleid,
					'condition_sql'=>'(fieldid = '.$field_departmentname_id.' and data = "'.$row->departmentname.'")',
					'condition_code'=>$field_departmentname_id.',=,'.$row->departmentname,
					'description'=>'departmentname is equal to '.$row->departmentname
				);
				$condition2 = (object)array(
					'rule_id'=>$ruleid,
					'condition_sql'=>'(fieldid = '.$field_regioncode_id.' and data = "'.$row->regioncode.'")',
					'condition_code'=>$field_regioncode_id.',=,'.$row->regioncode,
					'description'=>'regioncode is equal to '.$row->regioncode
				);
				$conditionid1 = $DB->insert_record('cohort_setting_conditions',$condition1);
				$conditionid2 = $DB->insert_record('cohort_setting_conditions',$condition2);

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
?>