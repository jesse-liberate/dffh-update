<?php

// Include required libary files
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once('lib.php');

// Include what we need
// require_once($CFG->dirroot.'/vendor/autoload.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin'); 
$PAGE->set_title($SITE->fullname.': User role rules');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/admin/tool/managerolerules/role_setting_rule_edit.php');

//Setup Breadcrumbs
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Site administration');
$PAGE->navbar->add('Users');
$PAGE->navbar->add('Accounts');
$PAGE->navbar->add('User role rules', new moodle_url('/admin/tool/managerolerules/index.php'));
$PAGE->navbar->add('Edit Rule');

$contextid = optional_param('contextid', 0, PARAM_INT);

if ($CFG->forcelogin) {
    require_login();
}

if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
} else {
    $context = context_system::instance();
}
/* --------------------------------------------------------------------------------------- */

// After submitting the changes
if (isset($_POST['save'])) {
	//Generate the empty field to ensure the condition is working fine.
	generate_empty_user_info_data_for_role();
	// echo '<pre>'.print_r($_POST, true).'</pre>';
	$data = $_POST;

	$ruleid = $data['ruleid'];
	$rule_name = $data['rule_name'];
	$rule_description = $data['rule_description'];

	// Keep only conditions in data 
	unset($data['rule_name']); unset($data['rule_description']); unset($data['ruleid']); unset($data['save']); 

	// Remove AND if there is 'AND' at the first and the last element of an array. Happen removing conditions of a rule or adding conditions to an empty rule
	if (current($data) == 'AND') { array_shift($data); }
	if (end($data) == 'AND') { array_pop($data); }
	
	// echo '<pre>'.print_r($data, true).'</pre>';  die();

	$sql = implode(' ', $data);

	// echo '<pre>'.print_r($sql, true).'</pre>'; 
	// 2 = VIC AND 3 LIKE Manager AND 6 LIKE Toorak

	$conditions = explode(' AND ', $sql);
	// echo '<pre>'.print_r($conditions, true).'</pre>';
	// Array
	// (
	//     [0] => 2 = VIC
	//     [1] => 3 LIKE Manager
	//     [2] => 6 LIKE Toorak
	// )

	$role_setting_conditions = array();
	$conditions_array = array();
	$joins_array = array();
	$key = 0;

	foreach ($conditions as $condition) {
		$input_key = null;
		$input_value = null;
		$internal_input_value = null;
		$operator = null;
		$default_condition = 'OR';

		$operator_description = array(
			'NOT LIKE' => 'does not contain',
			'LIKE' => 'contains',
			'=' => 'is equal to',
			'<>' => 'is not equal to'
		);

		if (strpos($condition, 'NOT LIKE') !== false) {
			$operator = 'NOT LIKE';
			$default_condition = 'AND';
		} else if (strpos($condition, 'LIKE') !== false) {
			$operator = 'LIKE';
		} else if (strpos($condition, '=') !== false) {
			$operator = '=';
		} else if (strpos($condition, '<>') !== false) {
			$operator = '<>';
			$default_condition = 'AND';
		}

		if (!empty($operator)) {
			list($input_key, $input_value) = array_map('trim', explode($operator, $condition));

			switch ($operator) {
				case 'NOT LIKE':
				case 'LIKE':
					$internal_input_value = "%$input_value%";
					break;
			}

			if (empty($internal_input_value)) {
				$internal_input_value = $input_value;
			}

			$condition_code = "$input_key,$operator,$input_value";
			$fieldname = $DB->get_field('user_info_field', 'shortname', array('id' => $input_key));
			$description = "$fieldname {$operator_description[$operator]} $input_value";

			$conditions_array[$counter] = "(uid$key.data $operator '$internal_input_value'
				$default_condition uif$key.defaultdata $operator '$internal_input_value')";

			$joins_array[$key] = "LEFT JOIN {user_info_field} uif$key ON uif$key.id = $input_key
				LEFT JOIN {user_info_data} uid$key ON uid$key.fieldid = $input_key AND uid$key.userid = u.id";

			$role_setting_conditions[$description] = array($conditions_array[$key], $condition_code);
			$key++;
		}
	}

	// echo '<pre>'.print_r($role_setting_conditions, true).'</pre>';

	// echo '<pre>'.print_r($conditions_array, true).'</pre>';

	$conditions_sql = implode(' AND ', $conditions_array);

	// echo '<pre>'.print_r($conditions_sql, true).'</pre>';

	$conditions_sql_detail = 'SELECT u.id as userid FROM {user} as u';
	if (!empty($joins_array)) {
		$conditions_sql_detail .= " " . implode(' ', $joins_array);
	}
	$conditions_array[] = 'u.id > 1';
	$conditions_array[] = 'u.deleted = 0';
	$conditions_array[] = 'u.confirmed = 1';
	$conditions_sql_detail .= ' WHERE ' . implode(' AND ', $conditions_array);
	$conditions_sql_detail .= ' GROUP BY u.id';

	// echo '<pre>'.print_r($conditions_array, true).'</pre>';

	// Remove all current conditions of the selected rule
	if ($DB->delete_records('role_setting_conditions', array('rule_id'=>$ruleid))) {
		// Update rule with new conditions
		$role_setting_rule_record = new stdClass();
	    $role_setting_rule_record->id = $ruleid;
	    $role_setting_rule_record->rule_name = $rule_name;
	    $role_setting_rule_record->conditions_sql = $conditions_sql;
	    $role_setting_rule_record->conditions_sql_detail = $conditions_sql_detail;
	    $role_setting_rule_record->description = $rule_description;
	    $DB->update_record('role_setting_rules', $role_setting_rule_record);

	    // Remove all current members of the selected rule
	    if ($DB->delete_records('role_rule_members', array('ruleid'=>$ruleid))) {
		    // Add user ids to rule member table
		    $date = new DateTime();
		    $rulemembers = $DB->get_records_sql($conditions_sql_detail);
		    // echo '<pre>'.print_r($rulemembers, true).'</pre>';
		    foreach ($rulemembers as $userid => $userobj) {
			    $role_setting_rule_members_record = new stdClass();
			    $role_setting_rule_members_record->ruleid = $ruleid;
			    $role_setting_rule_members_record->userid = $userid;
			    $role_setting_rule_members_record->timeadded = $date->getTimestamp();
			    $DB->insert_record('role_rule_members', $role_setting_rule_members_record);
		    }
		}

	    // Insert a new set of conditions for the selected rule
		foreach ($role_setting_conditions as $description => $condition_array) {
			$role_setting_conditions_record = new stdClass();
		    $role_setting_conditions_record->rule_id = $ruleid;
		    $role_setting_conditions_record->condition_sql = $condition_array[0];
		    $role_setting_conditions_record->condition_code = $condition_array[1];
		    $role_setting_conditions_record->description = $description;
		    $DB->insert_record('role_setting_conditions', $role_setting_conditions_record);
		}
	}

	$all_settings = $DB->get_records('role_setting');
	$updated_settings = array();

	if (!empty($all_settings)) {
		foreach ($all_settings as $id => $setting) {
			$rules_combinations = explode(' OR ', $setting->rules_combination);
			foreach ($rules_combinations as $key => $rule_id) {
				if ($rule_id === $ruleid) {
					array_push($updated_settings, $id);					
				}
			}
		}

		// Change the setting with updated rule		
		$userids = array();

		$system_contextid = $DB->get_field('context','id',array('contextlevel'=>SYSTEM_CONTEXT_LEVEL));

		foreach ($updated_settings as $key => $setting_id) {
			$roleid = $DB->get_field('role_setting', 'role_id', array('id'=>$setting_id));

			$rules_combinations = explode(' OR ', $DB->get_field('role_setting', 'rules_combination', array('id'=>$setting_id)));

			foreach ($rules_combinations as $key => $rule_id) {
				$rulesql[$setting_id] = $DB->get_field('role_setting_rules', 'conditions_sql_detail', array('id'=>$rule_id));

				// User ID results for each rule
				$members[$setting_id] = $DB->get_records_sql($rulesql[$setting_id]);

				// Add all userids from all rules to an array userids
				foreach ($members[$setting_id] as $userid => $value) {
					array_push($userids, $userid);
				}
			}

			// Remove redundant userids. This is the list of userids to add to a role
			$useridlist = array_unique($userids);		

			// Clear member of a role first then add new members
			
			$DB->delete_records('role_assignments',array('roleid'=>$roleid,'contextid'=>$system_contextid));
			foreach ($useridlist as $userid) {
				role_assign($roleid, $userid, $system_contextid);
			}
		}

	}

	header('Location:' . $CFG->wwwroot .'/admin/tool/managerolerules/index.php'); 

} elseif (isset($_POST['cancel'])) {
	header('Location:' . $CFG->wwwroot .'/admin/tool/managerolerules/index.php'); 
}


/* --------------------- Get necessary profile fields of Admin users --------------------- */
$usrhelper = $DB->get_record('user', array('id' => 2));
profile_load_data($usrhelper);
profile_load_custom_fields($usrhelper);

$profilefields = array();
foreach ($usrhelper as $field => $value) {
	if ($field === 'profile' && !empty($value)) {
		foreach ($value as $definedfield => $v) {
			$fieldid = $DB->get_field('user_info_field', 'id', array('shortname'=>$definedfield));
			$profilefields[$fieldid] = $definedfield;
		}
	} 
}


/* --------------------------------------------------------------------------------------- */

$ruleid = $_GET['id'];

$current_rule = $DB->get_record('role_setting_rules', array('id'=>$ruleid));

$current_rule_conditions = $DB->get_records('role_setting_conditions', array('rule_id'=>$ruleid));

$current_conditions = array();
foreach ($current_rule_conditions as $id => $condition) {
	$current_conditions[$id] = explode(',',$condition->condition_code);
}
// echo '<pre>'.print_r($current_conditions, true).'</pre>';
// Array
// (
//     [48] => Array
//         (
//             [0] => 2
//             [1] => =
//             [2] => VIC
//         )
//     [49] => Array
//         (
//             [0] => 3
//             [1] => LIKE
//             [2] => Manager
//         )
//     [50] => Array
//         (
//             [0] => 6
//             [1] => LIKE
//             [2] => Toorak
//         )
// )

/* --------------------------------------------------------------------------------------- */

$table = '<table id="tbl_rule">';
$table .= '<tbody>';
$table .= '<tr>';
$table .= '<td>Rule name: </td>'.'<td colspan="4"><input type="text" name="rule_name" value="'.$current_rule->rule_name.'" size="60"/></td>';
$table .= '</tr>';
// $table .= '<tr>';
// $table .= '<td>Description: </td>'.'<td colspan="4"><textarea name="rule_description" cols="62" rows="3">'.$current_rule->description.'</textarea></td>';
// $table .= '</tr>';
$table .= '<tr>';

$table .= '<tr>';
$table .= (empty($current_conditions)) ? '<td>Current condition(s): </td><td colspan="4">No conditions are applied. This rule includes all users.</td>' : '<td colspan="5">Current condition(s): </td>';
$table .= '</tr>';

$i = 0;
foreach ($current_conditions as $conditionid=>$fieldid_operand_value) {
	$i++;
	$fieldid = $fieldid_operand_value[0];
	$fieldname = $DB->get_field('user_info_field', 'shortname', array('id'=>$fieldid));
	$operand = $fieldid_operand_value[1];
	$value = $fieldid_operand_value[2];

	$table .= '<tr><td></td>';

	$table .= '<td><select name="profile_field'.$i.'">';
	foreach($profilefields as $id=>$field) {
		$selected = ($id == $fieldid) ? ' selected="selected"' : '';
		$table .= '<option value='.$id.$selected.'>'.$field.'</option>';
	}
	$table .= '</select></td>';

	$table .= '<td><select name="rule_compare'.$i.'">';
	switch ($operand) {				
		case '=': 
			$table .= '<option value="=" selected="selected">is equal to</option>';
			$table .= '<option value="<>">is not equal to</option>';
			$table .= '<option value="LIKE">contains</option>';
			$table .= '<option value="NOT LIKE">does not contain</option>';
			break;
		case '<>':
			$table .= '<option value="=">is equal to</option>';
			$table .= '<option value="<>" selected="selected">is not equal to</option>';
			$table .= '<option value="LIKE">contains</option>';
			$table .= '<option value="NOT LIKE">does not contain</option>';
			break;
		case 'LIKE':
			$table .= '<option value="=">is equal to</option>';
			$table .= '<option value="<>">is not equal to</option>';
			$table .= '<option value="LIKE" selected="selected">contains</option>';
			$table .= '<option value="NOT LIKE">does not contain</option>';
			break;
		case 'NOT LIKE':
			$table .= '<option value="=">is equal to</option>';
			$table .= '<option value="<>">is not equal to</option>';
			$table .= '<option value="LIKE" selected="selected">contains</option>';
			$table .= '<option value="NOT LIKE" selected="selected">does not contain</option>';
			break;		
		default:
			break;
	}	
	$table .= '</select></td>';

	$table .= '<td><input type="text" name="rule_condition'.$i.'" value="'.$value.'" size="30"/></td>';

	$table .= '<td><button id="remove">-</button></td>';
	if ($i < count($current_conditions)) {
		$table .= '<td><input type="hidden" name="rule_operand'.$i.'" value="AND"/>';		
	}

	$table .= '</tr>';				
}

$table .= '<td colspan="5">Add condition(s): </td>';
$table .= '<tr><td></td><td><div id="add" class="btn">+</div></td><td></td><td></td><td></td></tr>';
$table .= '</tboby>';
$table .= '</table>';

/* --------------------------------------------------------------------------------------- */

echo $OUTPUT->header();
echo get_string('rolesettingruleeditheader','tool_managerolerules');
echo "<form id='editruleform' action='' method='POST'>";
echo $table.'</br>';
echo '<input type="hidden" value="'.$ruleid.'" name="ruleid"/>';
echo '<input class="btn" id="save" type="submit" value="Save" name="save" style="vertical-align: top;"/>';
echo ' <a href="'.$CFG->wwwroot.'/admin/tool/managerolerules/index.php" class="btn"> Cancel</a>';
echo "</form>";
echo $OUTPUT->footer();   
?>
<style type="text/css">
	.btn{
		margin-right: 5px;
	}
</style>

<script src="js/jquery-1.6.2.min.js"></script>
<script type="text/javascript">
	var i = <?=json_encode($i) ?>;
	
  	var profilefields_js = <?=json_encode($profilefields) ?>;
  	var profilefields_list = '';
  	$.each(profilefields_js,function(index, value) {
		profilefields_list += '<option value="' + index + '">' + value + '</option>';
	});

	var rule_compare = '';
	rule_compare += '<option value="=">is equal to</option>';
	rule_compare += '<option value="<>">is not equal to</option>';
	rule_compare += '<option value="LIKE">contains</option>';
	rule_compare += '<option value="NOT LIKE">does not contain</option>';

	// Add new condition dynamically
  	$('#add').click(function() {    
  		i++;
	    $('#tbl_rule tr:last').prev().after('<tr><td><input type="text" name="rule_operand'+i+'" value="AND" size=1 readonly/></td><td><select name="profile_field'+i+'">'+profilefields_list+'</select></td><td><select name="rule_compare'+i+'" id ="rule_compare">'+rule_compare+'</select></td><td><input type="text" name="rule_condition'+i+'"size="30"/></td><td><button id="remove">-</button></td></tr>');
	}); 


	$('#remove').live ('click', function() {    
		$(this).closest ('tr').remove();
	});


	// Input text should not be empty when submit
	$("#editruleform").submit(function(){
	    var isFormValid = true;
	    $("#editruleform input:text").each(function(){
	        if ($.trim($(this).val()).length == 0){
	            isFormValid = false;
	        }
	    });
	    if (!isFormValid) alert("Please fill in all the required fields");
	    return isFormValid;
	});


</script>