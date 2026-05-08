<?php
// Include required libary files
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');

// Include what we need
// require_once($CFG->dirroot.'/vendor/autoload.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin'); 
$PAGE->set_title($SITE->fullname.': User role rules');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/admin/tool/managerolerules/role_setting_rule_add.php');

//Setup Breadcrumbs
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Site administration');
$PAGE->navbar->add('Users');
$PAGE->navbar->add('Accounts');
$PAGE->navbar->add('User role rules', new moodle_url('/admin/tool/managerolerules/index.php'));
$PAGE->navbar->add('Add Rule', new moodle_url('/admin/tool/managerolerules/role_setting_rule_add.php'));


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

if (isset($_POST['save'])) {
	
	$rule_name = $_POST['rule_name'];
	$rule_description = $_POST['rule_description'];

	$data = $_POST; unset($data['rule_name']); unset($data['rule_description']); unset($data['save']); 
	$sql = '';
	// echo '<pre>'.print_r($data, true).'</pre>';
	// Array
	// (
	//     [profile_field] => 2
	//     [rule_compare] => =
	//     [rule_condition] => VIC
	//     [rule_operand1] => AND
	//     [profile_field1] => 3
	//     [rule_compare1] => LIKE
	//     [rule_condition1] => Staff
	//     [rule_operand2] => AND
	//     [profile_field2] => 6
	//     [rule_compare2] => NOT LIKE
	//     [rule_condition2] => Toorak
	// )

	foreach ($data as $elementname => $value) {		
		$sql .= trim($value).' ';
	}

	// echo '<pre>'.print_r($sql, true).'</pre>';   // 2 = VIC AND 3 LIKE Staff AND 6 NOT LIKE Toorak 

	$conditions = explode('AND', $sql);
	// echo '<pre>'.print_r($conditions, true).'</pre>';
	// Array
	// (
	//     [0] => 2 = VIC 
	//     [1] =>  3 LIKE Staff 
	//     [2] =>  6 NOT LIKE Toorak 
	// )

	// Array to save all conditions to save to mdl_role_setting_conditions later


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

	// echo '<pre>'.print_r($conditions, true).'</pre>';
	// Array
	// (
	//     [0] => 2 = VIC 
	//     [1] =>  2 <> NSW 
	//     [2] =>  3 LIKE Employee 
	//     [3] =>  6 NOT LIKE CBD 
	// )
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

	// echo '<pre>'.print_r($conditions_sql_detail, true).'</pre>';

	// Add details to Rule
	$role_setting_rule_record = new stdClass();
    $role_setting_rule_record->rule_name = $rule_name;
    $role_setting_rule_record->conditions_sql = $conditions_sql;
    $role_setting_rule_record->conditions_sql_detail = $conditions_sql_detail;
    $role_setting_rule_record->description = $rule_description;
    $ruleid = $DB->insert_record('role_setting_rules', $role_setting_rule_record);

    // Add user ids to rule member table
    $date = new DateTime();
    $rulemembers = $DB->get_records_sql($conditions_sql_detail);
    foreach ($rulemembers as $userid => $userobj) {
	    $role_setting_rule_members_record = new stdClass();
	    $role_setting_rule_members_record->ruleid = $ruleid;
	    $role_setting_rule_members_record->userid = $userid;
	    $role_setting_rule_members_record->timeadded = $date->getTimestamp();
	    $DB->insert_record('role_rule_members', $role_setting_rule_members_record);
    }

	// Add relevant conditions of rule to condition table
	foreach ($role_setting_conditions as $description => $condition_array) {
		$role_setting_conditions_record = new stdClass();
	    $role_setting_conditions_record->rule_id = $ruleid;
	    $role_setting_conditions_record->condition_sql = $condition_array[0];
	    $role_setting_conditions_record->condition_code = $condition_array[1];
	    $role_setting_conditions_record->description = $description;
	    $DB->insert_record('role_setting_conditions', $role_setting_conditions_record);
	}

	header('Location:' . $CFG->wwwroot .'/admin/tool/managerolerules/index.php');


}
/* --------------------------------------------------------------------------------------- */

echo $OUTPUT->header();

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

// echo '<pre>'.print_r($usrhelper, true).'</pre>';
// echo '<pre>'.print_r($profilefields, true).'</pre>';

/* --------------------------------------------------------------------------------------- */

echo get_string('rolesettingruleaddheader','tool_managerolerules');

$table = '<table id="tbl_rule">';
$table .= '<tbody>';
$table .= '<tr>';
$table .= '<td>Rule name: </td>'.'<td colspan="4"><input type="text" name="rule_name" size="60"/></td>';
$table .= '</tr>';
// $table .= '<tr>';
// $table .= '<td>Description: </td>'.'<td colspan="4"><textarea name="rule_description" cols="62" rows="3"></textarea></td>';
// $table .= '</tr>';
$table .= '<tr>';
$table .= '<td>Condition(s): </td>'.'<td><select name="profile_field">';
foreach($profilefields as $id=>$field) {
	$table .= '<option value='.$id.'>'.$field.'</option>';
}
$table .= '</select></td>';

$table .= '<td><select name="rule_compare"><option value="=">is equal to</option>';
$table .= '<option value="<>">is not equal to</option>';
$table .= '<option value="LIKE">contains</option>';
$table .= '<option value="NOT LIKE">does not contain</option>';
$table .= '</select></td>';

$table .= '<td><input type="text" name="rule_condition" id="" size="30"/></td>';
$table .= '</tr>';
$table .= '<tr><td></td><td><div id="add" class="btn">+</div></td><td></td><td></td><td></td></tr>';
$table .= '</tboby>';
$table .= '</table>';

echo "<form id='addruleform' action='' method='POST'>";
echo $table.'</br>';
echo '<input class="btn" id="save" type="submit" value="Save" name="save" style="vertical-align: top;"/>';
echo ' <a href="'.$CFG->wwwroot.'/admin/tool/managerolerules/index.php" class="btn">Cancel</a>';
echo "</form>";

echo $OUTPUT->footer();   


?>
<script src="js/jquery-1.6.2.min.js"></script>
<script type="text/javascript">
	var i = 0;
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

  	$('#add').click(function() {    
  		i++;
	    $('#tbl_rule tr:last').prev().after('<tr><td><input type="text" name="rule_operand'+i+'" value="AND" size=1 readonly/></td><td><select name="profile_field'+i+'">'+profilefields_list+'</select></td><td><select name="rule_compare'+i+'" id ="rule_compare">'+rule_compare+'</select></td><td><input type="text" name="rule_condition'+i+'"size="30"/></td><td><button id="remove">-</button></td></tr>');
	}); 


	$('#remove').live ('click', function() {    
		$(this).closest ('tr').remove();
	}); 


	$("#addruleform").submit(function(){
	    var isFormValid = true;

	    $("#addruleform input:text").each(function(){
	        if ($.trim($(this).val()).length == 0){
	            isFormValid = false;
	        }
	      
	    });

	    if (!isFormValid) alert("Please fill in all the required fields");

	    return isFormValid;
	});


</script>