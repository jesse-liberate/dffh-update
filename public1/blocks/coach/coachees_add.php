<?php
require_once('../../config.php');
require_once('lib.php');
if(!isset($userid)) $userid = $USER->id;
require_login(0, false);
$context_system = context_system::instance();
$PAGE->set_context($context_system);

$PAGE->set_pagelayout('admin');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading(get_string('coachees','block_coach'));
$PAGE->set_url($CFG->wwwroot. '/blocks/coach/coachees_add.php');
require_capability('block/coach:assign_coaches_to_users', $context_system);
$id="";
$html="";
if(isset($_POST['sub'])){
	$coachid = $_POST['id'];
	if($coachid=="") $coachid= $_POST['coach'];
	// echo "<pre>".print_r($_POST,TRUE)."</pre>";
	// die();
	$update = $_POST['coachees'];
	$old = $DB->get_records('coachees',array('coachid'=>$coachid));
	$existing = array();
	$msg="";
	if(!empty($old)){
		foreach ($old as $row) {
			if(!in_array($row->coacheeid, $update)){
				$DB->delete_records('coachees',array('coachid'=>$coachid,'coacheeid'=>$row->coacheeid));
				// remove_user_position_profile($row->coacheeid,POSITION_COACHEE);
				$msg.= update_profile_field_value('IsCoachee',$row->coacheeid,MDL_CHECKBOX_UNCHECK);
			}else{
				$existing [] = $row->coacheeid;
			}
		}
	}
	if(!empty($update)){
		foreach ($update as $key=>$val) {
			if(in_array($val,$existing)) continue;
			$new = new stdClass();
			$new->coacheeid = $val;
			$new->coachid = $coachid;
			$new->timemodified = time();
			$DB->insert_record('coachees',$new);
			//UPDATE COACHEE PROFILE
			$msg.= update_profile_field_value('IsCoachee',$val,MDL_CHECKBOX_CHECK);
			// update_user_position_profile($val,POSITION_COACHEE);
		}
	}
	if($msg==""){ 
		$message = get_string('save:success:notag','block_coach');
		$message_type = core\output\notification::NOTIFY_SUCCESS;
	}else{
		$message = $msg;
		$message_type = core\output\notification::NOTIFY_ERROR;
	}
	redirect(
	    new moodle_url('/blocks/coach/coachees.php'),
	    $message,
	    null,
	    $message_type
	);
}

if(isset($_GET['id'])){
	$coachid= $_GET['id'];

	$coachs_options=get_coachs_options($coachid);
	$coachees_options=get_available_coachees_options($coachid);

	$html_lib = "<script src='js/jquery-1.12.2.min.js'></script>
	  <script src='js/jquery.tablesorter.js'></script>
	  <script src='resource/chosen.jquery.js' type='text/javascript'></script>
	  <link rel='stylesheet' href='resource/chosen.css'>
	  <link rel='stylesheet' href='js/style.css'>";

	$html.=get_string('coachs:managecoachees:title','block_coach');

	$html.="<form action='coachees_add.php' method='POST'>";
	$html.="<table width='100%' cellspacing='10'>";
	$html.="<tr>";
	$html.="<th>".get_string('coachs:name','block_coach')."</th>";
	$html.="<td><select name='coach' class='chosen-select' readonly>".$coachs_options."</select></td>";
	$html.="</tr>";
	$html.="<tr>";
	$html.="<th>".get_string('coachs:coachees','block_coach')."</th>";
	$html.="<td><select name='coachees[]' class='chosen-select' multiple>".$coachees_options."</select></td>";
	$html.="</tr>";
	$html.="<tr>";
	$html.="<tr><td colspan='2'>";
	$html.="<input type='submit' name='sub' value='Save changes' style='margin-bottom: 0px;'>";
	$html.=" <a href='coachees.php' class='btn'> Cancel </a>";

	$html.="<input type='hidden' name='id' value='".$coachid."'>";

	$html.="</td></tr>";
	$html.="</table>";
	$html.="</form>";
	$html.="<br><br>";

	echo $OUTPUT->header();
	echo $html;
	echo $OUTPUT->footer();
	echo $html_lib;
}
?>
<script type="text/javascript">
var config = {
    '.chosen-select'           : {},
    '.chosen-select-deselect'  : {allow_single_deselect:true},
    '.chosen-select-no-single' : {disable_search_threshold:10},
    '.chosen-select-no-results': {no_results_text:'Could not find any!'},
    '.chosen-select-width'     : {width:"95%"}
}
for (var selector in config) {
    $(selector).chosen(config[selector]);
}
</script>