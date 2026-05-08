<?php

/**
 * @package   tool_cohortenrolmentrules
 * @copyright  2016  Charlie Tran
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Include required libary files
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');

require_once($CFG->dirroot . '/cohort/lib.php');       // for adding users to a cohort
require_once($CFG->dirroot . '/cohort/locallib.php');  // for adding users to a cohort

// Include what we need
// require_once($CFG->dirroot.'/vendor/autoload.php');

$PAGE->requires->js('/admin/tool/cohortenrolmentrules/js/jquery-3.1.0.min.js', true);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin'); 
$PAGE->set_title($SITE->fullname.': Cohort Enrolment Rules');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/admin/tool/cohortenrolmentrules/cohort_setting_rule_edit.php');

//Setup Breadcrumbs
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Site administration');
$PAGE->navbar->add('Users');
$PAGE->navbar->add('Accounts');
$PAGE->navbar->add('Cohort Enrolment Rules', new moodle_url('/admin/tool/cohortenrolmentrules/index.php'));
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

$defaultfields = array('country','lang'); // If we want to apply more default fields, add more value to this array

// After submitting the changes
if (isset($_POST['save'])) {
	// echo '<pre>'.print_r($_POST, true).'</pre>';
	$data = $_POST;

	$ruleid = $data['ruleid'];
	$rule_name = $data['rule_name'];

	// Keep only conditions in data
	unset($data['rule_name']); unset($data['ruleid']); unset($data['save']);

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

	// Separate between the default and custom fields
	$conditions_defaultfield = array();

	foreach ($defaultfields as $defaultfield) {
		foreach ($conditions as $key => $condition) {
	        if (stripos($condition, $defaultfield) !== FALSE) {
	            array_push($conditions_defaultfield, $condition);
	            unset($conditions[$key]);
	        }
	    }
	}

	// echo '<pre>conditions: '.print_r($conditions, true).'</pre>';
	// echo '<pre>conditions_defaultfield: '.print_r($conditions_defaultfield, true).'</pre>'; die();

	$cohort_setting_conditions = array();
	$conditions_array = array();
	$conditions_defaultfield_array = array();

	foreach ($conditions as $key=>$condition) {

		if (strpos($condition, 'NOT LIKE') !== false) {
			$input = explode('NOT LIKE', $condition);

			$condition_code = trim($input[0]).',NOT LIKE,'.trim($input[1]);
			$fieldname = $DB->get_field('user_info_field', 'shortname', array('id'=>trim($input[0])));
			$description = $fieldname.' does not contain '.trim($input[1]);

			$condition_notcontain = '(fieldid = '.trim(current($input)).' and data'.' NOT LIKE '.'"%'.trim($input[1]).'%")';
			array_push($conditions_array, $condition_notcontain);
			$cohort_setting_conditions[$description] = array($condition_notcontain,$condition_code);

		} else if (strpos($condition, 'LIKE') !== false) {
			$input = explode('LIKE', $condition);

			$condition_code = trim($input[0]).',LIKE,'.trim($input[1]);
			$fieldname = $DB->get_field('user_info_field', 'shortname', array('id'=>trim($input[0])));
			$description = $fieldname.' contains '.trim($input[1]);

			$condition_contain = '(fieldid = '.trim($input[0]).' and data'.' LIKE '.'"%'.trim($input[1]).'%")';
			array_push($conditions_array, $condition_contain);
			$cohort_setting_conditions[$description] = array($condition_contain,$condition_code);
		} 

		if (strpos($condition, '=') !== false) {
			$input = explode('=', $condition);

			$condition_code = trim($input[0]).',=,'.trim($input[1]);
			$fieldname = $DB->get_field('user_info_field', 'shortname', array('id'=>trim($input[0])));
			$description = $fieldname.' is equal to '.trim($input[1]);

			$condition_equal = '(fieldid = '.trim($input[0]).' and data'.' = '.'"'.trim($input[1]).'")';
			array_push($conditions_array, $condition_equal);
			$cohort_setting_conditions[$description] = array($condition_equal,$condition_code);
		}

		if (strpos($condition, '<>') !== false) {
			$input = explode('<>', $condition);

			$condition_code = trim($input[0]).',<>,'.trim($input[1]);
			$fieldname = $DB->get_field('user_info_field', 'shortname', array('id'=>trim($input[0])));
			$description = $fieldname.' is not equal to '.trim($input[1]);

			$condition_notequal = '(fieldid = '.trim($input[0]).' and data'.' <> '.'"'.trim($input[1]).'")';
			array_push($conditions_array, $condition_notequal);
			$cohort_setting_conditions[$description] = array($condition_notequal,$condition_code);			
		}

	}

	foreach ($conditions_defaultfield as $key=>$condition) {

		if (strpos($condition, 'NOT LIKE') !== false) {
			$input = explode('NOT LIKE', $condition);

			$condition_code = trim($input[0]).',NOT LIKE,'.trim($input[1]);
			$description = ucfirst(trim($input[0])).' does not contain '.trim($input[1]);

			$condition_notcontain = trim(current($input)).' NOT LIKE '.'"%'.trim($input[1]).'%"';
			array_push($conditions_defaultfield_array, $condition_notcontain);
			$cohort_setting_conditions[$description] = array($condition_notcontain,$condition_code);

		} else if (strpos($condition, 'LIKE') !== false) {
			$input = explode('LIKE', $condition);

			$condition_code = trim($input[0]).',LIKE,'.trim($input[1]);
			$description = ucfirst(trim($input[0])).' contains '.trim($input[1]);

			$condition_contain = trim($input[0]).' LIKE '.'"%'.trim($input[1]).'%"';
			array_push($conditions_defaultfield_array, $condition_contain);
			$cohort_setting_conditions[$description] = array($condition_contain,$condition_code);
		} 

		if (strpos($condition, '=') !== false) {
			$input = explode('=', $condition);

			$condition_code = trim($input[0]).',=,'.trim($input[1]);
			$description = ucfirst(trim($input[0])).' is equal to '.trim($input[1]);

			$condition_equal = trim($input[0]).' = '.'"'.trim($input[1]).'"';
			array_push($conditions_defaultfield_array, $condition_equal);
			$cohort_setting_conditions[$description] = array($condition_equal,$condition_code);
		}

		if (strpos($condition, '<>') !== false) {
			$input = explode('<>', $condition);

			$condition_code = trim($input[0]).',<>,'.trim($input[1]);
			$description = ucfirst(trim($input[0])).' is not equal to '.trim($input[1]);

			$condition_notequal = trim($input[0]).' <> '.'"'.trim($input[1]).'"';
			array_push($conditions_defaultfield_array, $condition_notequal);
			$cohort_setting_conditions[$description] = array($condition_notequal,$condition_code);			
		}

	}

	// echo '<pre>'.print_r($cohort_setting_conditions, true).'</pre>';
	// echo '<pre>'.print_r($conditions_array, true).'</pre>';

	$conditions_sql = implode(' AND ', $conditions_array);
	$conditions_defaultfield_sql = implode(' AND ', $conditions_defaultfield_array);

	// echo '<pre>'.print_r($conditions_sql, true).'</pre>';

	$conditions_sql_detail = 'SELECT u.id as userid FROM mdl_user as u ';
	foreach ($conditions_array as $key => $condition) {
		if((strpos($condition,"<>")===false)&&(strpos($condition,"NOT LIKE ")===false)){
			$conditions_sql_detail .= 'INNER JOIN (select userid from mdl_user_info_data where '.$condition.') as uid'.$key.' ';
		}else{
			$condition = str_replace("<>","=",$condition);
			$condition = str_replace("NOT LIKE","LIKE",$condition);
			$conditions_sql_detail .= 'INNER JOIN (select id as userid from mdl_user where id not in(select userid from mdl_user_info_data where '.$condition.')) as uid'.$key.' ';
		}
		$conditions_sql_detail .= 'ON uid'.$key.'.userid = u.id ';			
	}

	$conditions_sql_detail .= '
		WHERE id>1 and confirmed=1 and deleted=0 ';

	foreach ($conditions_defaultfield_array as $conditions_defaultfield) {
		$conditions_sql_detail .= ' and '.$conditions_defaultfield;
	}

	$conditions_sql_detail .= ' GROUP BY userid';

	// echo '<pre>'.print_r($conditions_array, true).'</pre>';

	// Remove all current conditions of the selected rule
	if ($DB->delete_records('cohort_setting_conditions', array('rule_id'=>$ruleid))) {
		// Update rule with new conditions
		$cohort_setting_rule_record = new stdClass();
	    $cohort_setting_rule_record->id = $ruleid;
	    $cohort_setting_rule_record->rule_name = $rule_name;
	    $cohort_setting_rule_record->conditions_sql = $conditions_sql.' and '.$conditions_defaultfield_sql;
	    $cohort_setting_rule_record->conditions_sql_detail = $conditions_sql_detail;
	    $DB->update_record('cohort_setting_rules', $cohort_setting_rule_record);
	    // echo '<pre>'.print_r($cohort_setting_rule_record, true).'</pre>';

	    // Remove all current members of the selected rule
	    if ($DB->delete_records('cohort_setting_rule_members', array('ruleid'=>$ruleid))) {
		    // Add user ids to rule member table
		    $date = new DateTime();
		    $rulemembers = $DB->get_records_sql($conditions_sql_detail);
		    // echo '<pre>'.print_r($rulemembers, true).'</pre>';
		    foreach ($rulemembers as $userid => $userobj) {
			    $cohort_setting_rule_members_record = new stdClass();
			    $cohort_setting_rule_members_record->ruleid = $ruleid;
			    $cohort_setting_rule_members_record->userid = $userid;
			    $cohort_setting_rule_members_record->timeadded = $date->getTimestamp();
			    $DB->insert_record('cohort_setting_rule_members', $cohort_setting_rule_members_record);
		    }
		}

	    // Insert a new set of conditions for the selected rule
		foreach ($cohort_setting_conditions as $description => $condition_array) {
			$cohort_setting_conditions_record = new stdClass();
		    $cohort_setting_conditions_record->rule_id = $ruleid;
		    $cohort_setting_conditions_record->condition_sql = $condition_array[0];
		    $cohort_setting_conditions_record->condition_code = $condition_array[1];
		    $cohort_setting_conditions_record->description = $description;
		    $DB->insert_record('cohort_setting_conditions', $cohort_setting_conditions_record);
		}
	}

	$all_settings = $DB->get_records('cohort_setting');
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

		foreach ($updated_settings as $key => $setting_id) {
			$userids = array();
			$cohortid = $DB->get_field('cohort_setting', 'cohort_id', array('id'=>$setting_id));

			$rules_combinations = explode(' OR ', $DB->get_field('cohort_setting', 'rules_combination', array('id'=>$setting_id)));

			foreach ($rules_combinations as $key => $rule_id) {
				$rulesql[$setting_id] = $DB->get_field('cohort_setting_rules', 'conditions_sql_detail', array('id'=>$rule_id));

				// User ID results for each rule
				$members[$setting_id] = $DB->get_records_sql($rulesql[$setting_id]);

				// Add all userids from all rules to an array userids
				foreach ($members[$setting_id] as $userid => $value) {
					array_push($userids, $userid);
				}
			}

			// Remove redundant userids. This is the list of userids to add to a cohort
			$useridlist = array_unique($userids);		

			// Clear member of a cohort first then add new members
			$existing_userids = array_keys($DB->get_records('cohort_members', ['cohortid' => $cohortid], '', 'userid'));
			// New users are ones that are in the list but not in the existing list
			$new_userids = array_diff($useridlist, $existing_userids);
			$remove_userids = array_diff($existing_userids, $useridlist);
			
			$transaction = $DB->start_delegated_transaction();
			foreach ($remove_userids as $userid) {
				cohort_remove_member($cohortid, $userid);
			}

			foreach ($new_userids as $userid) {
				cohort_add_member($cohortid, $userid);
			}
			$transaction->allow_commit();
		}

	}
	header('Location:' . $CFG->wwwroot .'/admin/tool/cohortenrolmentrules/index.php'); 

} elseif (isset($_POST['cancel'])) {
	header('Location:' . $CFG->wwwroot .'/admin/tool/cohortenrolmentrules/index.php'); 
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
	} else if (in_array($field, $defaultfields)) {
		foreach ($defaultfields as $value) {
			$profilefields[$value] = ucfirst($value);
		}
	}
}

/* --------------------------------------------------------------------------------------- */

$ruleid = $_GET['id'];

$current_rule = $DB->get_record('cohort_setting_rules', array('id'=>$ruleid));
// echo '<pre>current_rule: '.print_r($current_rule, true).'</pre>';

$current_rule_conditions = $DB->get_records('cohort_setting_conditions', array('rule_id'=>$ruleid));
// echo '<pre>current_rule_conditions: '.print_r($current_rule_conditions, true).'</pre>';

$current_conditions = array();
foreach ($current_rule_conditions as $id => $condition) {
	$current_conditions[$id] = explode(',',$condition->condition_code);
}
// echo '<pre>current_conditions: '.print_r($current_conditions, true).'</pre>';
// Array
// (
//     [13] => Array
//         (
//             [0] => 4
//             [1] => =
//             [2] => Michelin
//         )
//     [14] => Array
//         (
//             [0] => country
//             [1] => LIKE
//             [2] => AU
//         )
// )

/* --------------------------------------------------------------------------------------- */

$table = '<table id="tbl_rule">';
$table .= '<tbody>';
$table .= '<tr>';
$table .= '<td>Rule name: </td>'.'<td colspan="4"><input class="form-control" type="text" name="rule_name" value="'.$current_rule->rule_name.'" size="60"/></td>';
$table .= '</tr>';

$table .= '<tr>';
$table .= (empty($current_conditions)) ? '<td>Current condition(s): </td><td colspan="4">No conditions are applied. This rule includes all users.</td>' : '<td colspan="5">Current condition(s): </td>';
$table .= '</tr>';

$i = 0;
foreach ($current_conditions as $conditionid=>$fieldid_operand_value) {
	$i++;
	$fieldid = $fieldid_operand_value[0];

	if (in_array(trim($fieldid), $defaultfields)) {
		$fieldname = trim($fieldid);
	} else $fieldname = $DB->get_field('user_info_field', 'shortname', array('id'=>$fieldid));

	$operand = $fieldid_operand_value[1];
	$value = $fieldid_operand_value[2];

	$table .= '<tr><td></td>';

	$table .= '<td><select class="form-control" name="profile_field'.$i.'">';
	foreach($profilefields as $id=>$field) {
		$selected = ($id == $fieldid) ? ' selected="selected"' : '';
		$table .= '<option value='.$id.$selected.'>'.$field.'</option>';
	}
	$table .= '</select></td>';

	$table .= '<td><select class="form-control" name="rule_compare'.$i.'">';
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

	$table .= '<td><input class="form-control" type="text" name="rule_condition'.$i.'" value="'.$value.'" size="30"/></td>';

	$table .= '<td><button class="btn btn-secondary remove">-</button></td>';
	if ($i < count($current_conditions)) {
		$table .= '<td><input type="hidden" name="rule_operand'.$i.'" value="AND"/>';		
	}

	$table .= '</tr>';				
}

$table .= '<td colspan="5">Add condition(s): </td>';
$table .= '<tr><td></td><td><div id="add" class="btn btn-secondary">+</div></td><td></td><td></td><td></td></tr>';
$table .= '</tboby>';
$table .= '</table>';

/* --------------------------------------------------------------------------------------- */

echo $OUTPUT->header();
echo get_string('cohortsettingruleeditheader','tool_cohortenrolmentrules');
echo "<form id='editruleform' action='' method='POST'>";
echo $table.'</br>';
echo '<input type="hidden" value="'.$ruleid.'" name="ruleid"/>';
echo '<div class="form-row">';
echo '<div class="col-auto">';
echo '<input class="btn btn-primary" id="save" type="submit" value="Save" name="save"/>';
echo '</div>';
echo '<div class="col-auto">';
echo '<input class="btn btn-secondary" id="cancel" type="submit" value="Cancel" name="cancel"/>';
echo '</div>';
echo '</div>';
echo "</form>";
echo $OUTPUT->footer();   

/* --------------------------------------------------------------------------------------- */

?>

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
	    $('#tbl_rule tr:last').prev().after('<tr><td><input class="form-control" type="text" name="rule_operand'+i+'" value="AND" size=1 readonly/></td><td><select class="form-control" name="profile_field'+i+'">'+profilefields_list+'</select></td><td><select class="form-control" name="rule_compare'+i+'" id ="rule_compare">'+rule_compare+'</select></td><td><input class="form-control" type="text" name="rule_condition'+i+'"size="30"/></td><td><button class="btn btn-secondary remove">-</button></td></tr>');
	}); 


	$('#tbl_rule').on('click', '.remove',function(e) {
		e.preventDefault();
		$(this).closest ('tr').remove();
	});

	var is_cancel = false;

	$('#cancel').on('click', function() {
		is_cancel = true;
	});

	// Input text should not be empty when submit
	$("#editruleform").submit(function(){
		if (is_cancel) {
			return true;
		}

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