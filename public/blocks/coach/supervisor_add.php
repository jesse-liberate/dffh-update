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
$PAGE->set_url($CFG->wwwroot. '/blocks/coach/supervisor_add.php');
if(!is_siteadmin($userid)) {
  echo get_string('notallowtoaccess','block_coach');
  exit();
}
$id="";
$html="";
if(isset($_POST['sub'])){
	$coachid = $_POST['id'];
	if($coachid=="") $coachid= $_POST['coach'];
	// echo "<pre>".print_r($_POST,TRUE)."</pre>";
	// die();
	$update = isset($_POST['coachees']) ? $_POST['coachees'] : array();
	$old = $DB->get_records('coachees',array('coachid'=>$coachid));
	$existing = array();
	$msg="";
	if(!empty($old)){
		foreach ($old as $row) {
			if(!in_array($row->coacheeid, $update)){
				//Remove students from the Student preceptor list
				$DB->delete_records('coachees',array('coachid'=>$coachid,'coacheeid'=>$row->coacheeid,'is_student'=>STUDENT_OR_SUPERVISOR));
				//NEED TO UPDATE AND REMOVE PROFILE FIELD
				$msg.= update_profile_field_value('IsStudent',$row->coacheeid,MDL_CHECKBOX_UNCHECK);
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
			$new->is_student = STUDENT_OR_SUPERVISOR;
			$new->timemodified = time();
			$DB->insert_record('coachees',$new);
			$msg.= update_profile_field_value('IsStudent',$val,MDL_CHECKBOX_CHECK);
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
	    new moodle_url('/blocks/coach/supervisor.php'),
	    $message,
	    null,
	    $message_type
	);
	// echo "<script type='text/javascript'>window.location='supervisor.php?u=1'</script>";
}

if(isset($_GET['id'])){
	$coachid= $_GET['id'];

	$coachs_options=get_coachs_options($coachid);
	$coachees_options=get_available_students_options($coachid);

	$html_lib = "<script src='js/jquery-1.12.2.min.js'></script>
	  <script src='js/jquery.tablesorter.js'></script>
	  <script src='resource/chosen.jquery.js' type='text/javascript'></script>
	  <link rel='stylesheet' href='resource/chosen.css'>
	  <link rel='stylesheet' href='js/style.css'>";

	$html.="<form action='supervisor_add.php' method='POST'>";
	$html.="<table class='tablestyle10'>";
	$html.="<tr>";
	$html.="<td>".get_string('supervisor:name','block_coach')."</td>";
	$html.="<td><select name='coach' class='chosen-select' readonly>".$coachs_options."</select></td>";
	$html.="</tr>";
	$html.="<tr>";
	$html.="<td>".get_string('studentname','block_coach')."</td>";
	$html.="<td><select name='coachees[]' class='chosen-select' multiple data-placeholder='Enter name'>".$coachees_options."</select></td>";
	$html.="</tr>";
	$html.="<tr>";
	$html.="<tr><td colspan='2'>";
	$html.="<input type='submit' name='sub' value='Save changes' style='margin-bottom: 0px;'>";
	$html.=" <a href='supervisor.php' class='btn'> Cancel </a>";

	$html.="<input type='hidden' name='id' value='".$coachid."'>";

	$html.="</td></tr>";
	$html.="</table>";
	$html.="</form>";
	$html.="<br><br>";

	echo $OUTPUT->header();
	echo $OUTPUT->heading(get_string('student:title','block_coach'));
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