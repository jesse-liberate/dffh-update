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
$PAGE->set_url($CFG->wwwroot. '/blocks/coach/coach_add.php');
require_capability('block/coach:manage_coaches', $context_system);
$id="";
$html="";
if(isset($_POST['sub'])){
	// echo "<pre>".print_r($_POST,TRUE)."</pre>";
	// die();
	if(isset($_POST['coaches'])){
		$update = $_POST['coaches'];
		$msg="";
		if(!empty($update)){
			foreach ($update as $key=>$u_id) {
				$new = new stdClass();
				$new->userid = $u_id;
				$new->timecreated = time();
				$new->is_student=0;
				if(!$DB->record_exists('coach',array('userid'=>$new->userid,'is_student'=>0))){
					$DB->insert_record('coach',$new);
				}
				//UPDATE COACH PROFILE
				$msg.= update_profile_field_value('IsCoach',$u_id,MDL_CHECKBOX_CHECK);
				// update_user_position_profile($u_id,POSITION_COACH);
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
}

$coachs_options=get_coachs_options();

$html_lib = "<script src='js/jquery-1.12.2.min.js'></script>
  <script src='js/jquery.tablesorter.js'></script>
  <script src='resource/chosen.jquery.js' type='text/javascript'></script>
  <link rel='stylesheet' href='resource/chosen.css'>
  <link rel='stylesheet' href='js/style.css'>";

$html.=get_string('coachs:addnew:title','block_coach');

$html.="<form action='coach_add.php' method='POST'>";
$html.="<table width='100%' cellspacing='10'>";
$html.="<tr>";
$html.="<th>".get_string('coachs:name','block_coach')."</th>";
$html.="<td><select name='coaches[]' class='chosen-select' multiple data-placeholder='Enter name'>".$coachs_options."</select></td>";
$html.="</tr>";
$html.="<tr>";
$html.="<tr>";
$html.="<tr><td colspan='2'>";
$html.="<input type='submit' name='sub' value='Save changes' style='margin-bottom: 0px;'>";
$html.=" <a href='coachees.php' class='btn'> Cancel </a>";

$html.="</td></tr>";
$html.="</table>";
$html.="</form>";
$html.="<br><br>";

echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();
echo $html_lib;
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