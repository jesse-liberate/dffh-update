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
$PAGE->set_url($CFG->wwwroot. '/blocks/coach/learningplan_update.php');
$can_addlearningplan = has_capability('block/coach:addlearningplan',$context_system);
$is_student = has_capability('block/coach:isstudent',$context_system);
$coachee_name = 'coachee';
if($is_student) $coachee_name = 'student';
$id="";

if(!$can_addlearningplan){
	print_error('notallowtoaccess','block_coach');
}

$html="";
if(isset($_POST['sub'])){
	$coacheeid = $_POST['id'];
	// echo "<pre>".print_r($_POST,TRUE)."</pre>";
	// die();
	$agreementdate = $_POST['agreementdate'];
	$reviewdate = $_POST['reviewdate'];
	$completiondate = $_POST['completiondate'];
	$agreementdate = ($agreementdate=="")? strtotime(str_replace("/","-",$agreementdate)):"";
	$reviewdate = ($reviewdate=="")? strtotime(str_replace("/","-",$reviewdate)):"";
	$completiondate = ($completiondate=="")? strtotime(str_replace("/","-",$completiondate)):"";

	$record = $DB->get_record('coach_learning_plan',array('userid'=>$coacheeid));
	if(!empty($record)){
		//update
		$record->goals = $_POST['goal'];
		$record->training = $_POST['training'];
		$record->activities = $_POST['activity'];
		$record->agreementdate = $agreementdate;
		$record->reviewdate = $reviewdate;
		$record->completiondate = $completiondate;
		$record->timemodified = time();
		$DB->update_record('coach_learning_plan',$record);
		//EDIT WITH COMPENTENCY HERE
	}else{
		$new = new stdClass();
		$new->userid = $coacheeid;
		$new->goals = $_POST['goal'];
		$new->training = $_POST['training'];
		$new->activities = $_POST['activity'];
		$new->agreementdate = $agreementdate;
		$new->reviewdate = $reviewdate;
		$new->completiondate = $completiondate;
		$new->timecreated = time();
		$DB->insert_record('coach_learning_plan',$new);
		//EDIT WITH COMPENTENCY HERE
	}
	//UPDATE COMPETENCY
	if($_POST['competency']){
		$update = $_POST['competency'];
		$old_competencies = $DB->get_fieldset_select('coach_competencies','competencyid','userid=?',array($coacheeid));
		foreach ($_POST['competency'] as $cid) {
			if(($key = array_search($cid,$old_competencies))!==false){
				unset($old_competencies[$key]);
			}
			if($DB->record_exists('coach_competencies',array('userid'=>$coacheeid,'competencyid'=>$cid))) continue;
			$new = new stdClass();
			$new->userid = $coacheeid;
			$new->competencyid = $cid;
			$new->timecreated = time();
			$DB->insert_record('coach_competencies',$new);
		}
		//Remove all old competencies
		if(!empty($old_competencies)){
			foreach ($old_competencies as $del_cid) {
				$DB->delete_records('coach_competencies',array('userid'=>$coacheeid,'competencyid'=>$del_cid));
			}
		}
	}
	echo "<script type='text/javascript'>window.location='index.php?l_p=1'</script>";
}

if(isset($_GET['id'])){
	$coacheeid= $_GET['id'];
	if(!is_siteadmin($USER->id)){
		if(!$DB->record_exists('coachees',array('coachid'=>$USER->id,'coacheeid'=>$coacheeid))){
			echo get_string('coachees:couldnotfind','block_coach','$coachee_name');
			die();
		}
	}
	$record = $DB->get_record('coach_learning_plan',array('userid'=>$coacheeid));
	$competency_options="";
	$goal="";
	$activity="";
	$training="";
	$agreementdate="";
	$reviewdate="";
	$completiondate="";
	if(!empty($record)){
		$goal = $record->goals;
		$activity = $record->activities;
		$training = $record->training;
		$agreementdate = date('d/m/Y',$record->agreementdate);
		$reviewdate = date('d/m/Y',$record->reviewdate);
		$completiondate = date('d/m/Y',$record->completiondate);
	}
	if($DB->record_exists('config_plugins',array('plugin'=>'block_career_mapping'))){
		$competency_options = get_competency_options($coacheeid);
	}
	$html_lib = "<script src='js/jquery-1.12.2.min.js'></script>
	 <script src='js/jquery-ui.js'></script>
	  <script src='js/jquery.tablesorter.js'></script>
	  <script src='resource/chosen.jquery.js' type='text/javascript'></script>
	  <link rel='stylesheet' href='resource/chosen.css'>
	  <link rel='stylesheet' href='js/jquery-ui.css'>
	  <link rel='stylesheet' href='js/style.css'>";
	  

	$user = $DB->get_record('user',array('id'=>$coacheeid));
	$fullname = ucfirst($user->firstname)." ".ucfirst($user->lastname);
	$html.=get_string('learning:update:title','block_coach',$fullname);

	$html.="<form action='learningplan_update.php' method='POST'>";
	$html.="<table class='tablestyle1'>";
	$html.="<tr>";
	$html.="<th>".get_string('learning:goal','block_coach').get_string('required','block_coach')."</th>";
	$html.="<td><textarea name='goal' required>".$goal."</textarea></td>";
	$html.="</tr>";
	$html.="<tr>";
	$html.="<th>".get_string('learning:competency','block_coach')."</th>";
	$html.="<td><select name='competency[]' class='chosen-select' multiple>".$competency_options."</select></td>";
	$html.="</tr>";
	$html.="<tr>";
	$html.="<th>".get_string('learning:activity','block_coach').get_string('required','block_coach')."</th>";
	$html.="<td><textarea name='activity' required>".$activity."</textarea></td>";
	$html.="</tr>";
	$html.="<tr>";
	$html.="<th>".get_string('learning:training','block_coach').get_string('required','block_coach')."</th>";
	$html.="<td><textarea name='training' required>".$training."</textarea></td>";
	$html.="</tr>";
	$html.="<tr>";
	$html.="<th>".get_string('learning:agreementdate','block_coach')."</th>";
	$html.="<td><input type='text' value='".$agreementdate."' name='agreementdate' id='agreementdate' class='datepicker'></td>";
	$html.="</tr>";
	$html.="<tr>";
	$html.="<th>".get_string('learning:reviewdate','block_coach')."</th>";
	$html.="<td><input type='text' value='".$reviewdate."' name='reviewdate' id='reviewdate' class='datepicker'></td>";
	$html.="</tr>";
	$html.="<tr>";
	$html.="<th>".get_string('learning:completiondate','block_coach')."</th>";
	$html.="<td><input type='text' value='".$completiondate."' name='completiondate' id='completiondate' class='datepicker'></td>";
	$html.="</tr>";
	$html.="<tr><td colspan='2'>";
	$html.="<input type='submit' name='sub' value='Save changes' style='margin-bottom: 0px;'>";
	$html.=" <a href='index.php' class='btn'> Cancel </a>";
	$html.="</td></tr>";
	$html.="</table>";
	$html.="<input type='hidden' name='id' value='".$coacheeid."'>";
	$html.="</form>";
	$html.="<br><br>";

	echo $OUTPUT->header();
	echo $html;
	echo $html_lib;
	echo $OUTPUT->footer();
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
$(function() {
    $("#completiondate").datepicker({dateFormat: 'dd/mm/yy'});
    $("#reviewdate").datepicker({
        dateFormat: 'dd/mm/yy',
        onSelect: function(date){
            $("#completiondate").datepicker( "option", "minDate", date );
        }
    });
    $("#agreementdate").datepicker({
        dateFormat: 'dd/mm/yy',
        onSelect: function(date){
            $("#reviewdate").datepicker( "option", "minDate", date );
        }
    });
});

</script>
<style type="text/css">
.tablestyle1 textarea{
	width: 39%;
	height: 60px;
}
</style>