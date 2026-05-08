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

// Include what we need
// require_once($CFG->dirroot.'/vendor/autoload.php');

$PAGE->requires->js('/admin/tool/cohortenrolmentrules/js/jquery-3.1.0.min.js', true);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin'); 
$PAGE->set_title($SITE->fullname.': Cohort Enrolment Rules');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/admin/tool/cohortenrolmentrules/cohort_setting_rule_add.php');

//Setup Breadcrumbs
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Site administration');
$PAGE->navbar->add('Users');
$PAGE->navbar->add('Accounts');
$PAGE->navbar->add('Cohort Enrolment Rules', new moodle_url('/admin/tool/cohortenrolmentrules/index.php'));
$PAGE->navbar->add('Add Rule', new moodle_url('/admin/tool/cohortenrolmentrules/cohort_setting_rule_add.php'));

$contextid = optional_param('contextid', 0, PARAM_INT);

if ($CFG->forcelogin) {
    require_login();
}

if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
} else {
    $context = context_system::instance();
}

$defaultfields = array('country','lang'); // If we want to apply more default fields, add more value to this array

/* --------------------------------------------------------------------------------------- */

if (isset($_POST['save'])) {
	
	$rule_name = $_POST['rule_name'];
	$data = $_POST; unset($data['rule_name']); unset($data['save']);
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

	// echo '<pre>'.print_r($sql, true).'</pre>';   // country LIKE AU AND 2 = VIC AND 3 LIKE Staff AND 6 NOT LIKE Toorak 

	$conditions = explode('AND', $sql);

	// echo '<pre>'.print_r($conditions, true).'</pre>';
	// Array
	// (
	// 	   [0] => country LIKE AU 	
	//     [1] => 2 = VIC
	//     [2] => 3 LIKE Staff
	//     [3] => 6 NOT LIKE Toorak
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
	// echo '<pre>conditions_defaultfield: '.print_r($conditions_defaultfield, true).'</pre>';

	// Array to save all conditions to save to mdl_cohort_setting_conditions later

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

	// echo '<pre>'.print_r($conditions, true).'</pre>';
	// Array
	// (
	//     [0] => 2 = VIC 
	//     [1] =>  2 <> NSW 
	//     [2] =>  3 LIKE Employee 
	//     [3] =>  6 NOT LIKE CBD 
	// )
	// echo '<pre>cohort_setting_conditions: '.print_r($cohort_setting_conditions, true).'</pre>';
	// Array
	// (
	//     [Location is equal to VIC] => (fieldid = VIC and data = "VIC")
	//     [Position is not equal to Staff] => (fieldid = Staff and data <> "Staff")
	//     [ is equal to Toorak and data LIKE "%Toorak%")] => (fieldid = Toorak and data LIKE "%Toorak%") and data = "Toorak and data LIKE "%Toorak%")")
	//     [ is equal to CBD and data NOT LIKE "%CBD%")] => (fieldid = CBD and data NOT LIKE "%CBD%") and data = "CBD and data NOT LIKE "%CBD%")")
	// )

	$conditions_sql = implode(' AND ', $conditions_array);
	$conditions_defaultfield_sql = implode(' AND ', $conditions_defaultfield_array);

	// echo '<pre>conditions_array: '.print_r($conditions_array, true).'</pre>';
	// echo '<pre>conditions_defaultfield_array: '.print_r($conditions_defaultfield_array, true).'</pre>';

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

	// echo '<pre>'.print_r($conditions_sql_detail, true).'</pre>';

	// Add details to Rule
	$cohort_setting_rule_record = new stdClass();
    $cohort_setting_rule_record->rule_name = $rule_name;
    $cohort_setting_rule_record->conditions_sql = $conditions_sql.' and '.$conditions_defaultfield_sql;
    $cohort_setting_rule_record->conditions_sql_detail = $conditions_sql_detail;
    $ruleid = $DB->insert_record('cohort_setting_rules', $cohort_setting_rule_record);

    // Add user ids to rule member table
    $date = new DateTime();
    $rulemembers = $DB->get_records_sql($conditions_sql_detail);
    foreach ($rulemembers as $userid => $userobj) {
	    $cohort_setting_rule_members_record = new stdClass();
	    $cohort_setting_rule_members_record->ruleid = $ruleid;
	    $cohort_setting_rule_members_record->userid = $userid;
	    $cohort_setting_rule_members_record->timeadded = $date->getTimestamp();
	    $DB->insert_record('cohort_setting_rule_members', $cohort_setting_rule_members_record);
    }

	// Add relevant conditions of rule to condition table
	foreach ($cohort_setting_conditions as $description => $condition_array) {
		$cohort_setting_conditions_record = new stdClass();
	    $cohort_setting_conditions_record->rule_id = $ruleid;
	    $cohort_setting_conditions_record->condition_sql = $condition_array[0];
	    $cohort_setting_conditions_record->condition_code = $condition_array[1];
	    $cohort_setting_conditions_record->description = $description;
	    $DB->insert_record('cohort_setting_conditions', $cohort_setting_conditions_record);
	}

	header('Location:' . $CFG->wwwroot .'/admin/tool/cohortenrolmentrules/index.php');

} elseif (isset($_POST['cancel'])) {
	header('Location:' . $CFG->wwwroot .'/admin/tool/cohortenrolmentrules/index.php');
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
	} else if (in_array($field, $defaultfields)) {
		foreach ($defaultfields as $value) {
			$profilefields[$value] = ucfirst($value);
		}
	}
}

// echo '<pre>usrhelper: '.print_r($usrhelper, true).'</pre>';
// echo '<pre>profilefields: '.print_r($profilefields, true).'</pre>';

/* --------------------------------------------------------------------------------------- */

echo get_string('cohortsettingruleaddheader','tool_cohortenrolmentrules');

$table = '<table id="tbl_rule">';
$table .= '<tbody>';
$table .= '<tr>';
$table .= '<td>Rule name: </td>'.'<td colspan="4"><input class="form-control" type="text" name="rule_name" size="60"/></td>';
$table .= '</tr>';

$table .= '<tr>';
$table .= '<td>Condition(s): </td>'.'<td><select class="form-control" name="profile_field">';
foreach($profilefields as $id=>$field) {
	$table .= '<option value='.$id.'>'.$field.'</option>';
}
$table .= '</select></td>';

$table .= '<td><select class="form-control" name="rule_compare"><option value="=">is equal to</option>';
$table .= '<option value="<>">is not equal to</option>';
$table .= '<option value="LIKE">contains</option>';
$table .= '<option value="NOT LIKE">does not contain</option>';
$table .= '</select></td>';

$table .= '<td><input type="text" class="form-control" name="rule_condition" id="" size="30"/></td>';
$table .= '</tr>';
$table .= '<tr><td></td><td><div id="add" class="btn btn-secondary">+</div></td><td></td><td></td><td></td></tr>';
$table .= '</tboby>';
$table .= '</table>';

echo "<form id='addruleform' action='' method='POST'>";
echo $table.'</br>';
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


?>

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
	    $('#tbl_rule tr:last').prev().after('<tr><td><input class="form-control" type="text" name="rule_operand'+i+'" value="AND" size=1 readonly/></td><td><select class="form-control" name="profile_field'+i+'">'+profilefields_list+'</select></td><td><select class="form-control" name="rule_compare'+i+'" id ="rule_compare">'+rule_compare+'</select></td><td><input type="text" class="form-control" name="rule_condition'+i+'"size="30"/></td><td><button class="btn btn-secondary remove">-</button></td></tr>');
	}); 


	$('#tbl_rule').on('click', '.remove', function(e) {
		e.preventDefault();
		$(this).closest ('tr').remove();
	}); 

	var is_cancel = false;

	$('#cancel').on('click', function() {
		is_cancel = true;
	});

	$("#addruleform").submit(function(e){
		if (is_cancel) {
			return true;
		}

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