<?php
//DEFINE ALL ROLES MUST BE CORRECT AS THESE
define('ROLE_STUDENT_SUPERVISOR','studentsupervisor');
define('ROLE_STUDENT','student');
define('ROLE_STAFF','staff');
define('ROLE_COACH','coach');
define('ROLE_COACHEE','coachee');
define('ROLE_INSTRUCTOR','instructor');
define('SYS_CONTEXT_LEVEL',10);
define('STUDENT_OR_SUPERVISOR',1);
define('NOT_STUDENT_OR_SUPERVISOR',0);
define('GRADUTE_STUDENT',2);
define('COACH_COACHEE',0);

//FOR POSITION PROFILE FILE
define('POSITION_COACH','Coach');
define('POSITION_COACHEE','Coachee');
define('POSITION_STUDENT','Student');
define('POSITION_STUDENTSUPERVISOR','Student preceptor');

define('NO_UPLOAD_FILE','upload:error:nofile');
define('UPLOAD_EXCEED_MAX','upload:error:exceedmax');
define('UPLOAD_ERROR_FILETYPE','upload:error:filetype');

define('MDL_COACH_FIELD','PositionCoach');
define('MDL_STUDENT_FIELD','PositionStudent');
//PROFILE CHECKBOX
define('MDL_CHECKBOX_CHECK',1);
define('MDL_CHECKBOX_UNCHECK',0);
//Get position based on the profile fields
function get_position_profile_id($typevalue){
	global $DB;
	if(!$DB->record_exists('user_info_field',array('shortname'=>MDL_COACH_FIELD))){
		$field = new stdClass();
	    $field->shortname = MDL_COACH_FIELD;
	    $field->name = "Position Coach";
	    $field->datatype = "menu";
	    $field->categoryid=1;
	    $field->visible=0;
	    $field->param1 = "\nCoach\nCoachee";
	    $fieldid = $DB->insert_record('user_info_field',$field);
	}
	if(!$DB->record_exists('user_info_field',array('shortname'=>MDL_STUDENT_FIELD))){
		$field = new stdClass();
	    $field->shortname = MDL_STUDENT_FIELD;
	    $field->name = "Position Student";
	    $field->datatype = "menu";
	    $field->categoryid=1;
	    $field->visible=0;
	    $field->param1 = "\nStudent\nStudent preceptor";
	    $fieldid = $DB->insert_record('user_info_field',$field);
	}
	if($typevalue==POSITION_COACH || $typevalue==POSITION_COACHEE)
		return $DB->get_field('user_info_field','id',array('shortname'=>'PositionCoach'));
	else return $DB->get_field('user_info_field','id',array('shortname'=>'PositionStudent'));
}
function remove_user_position_profile($userid,$typevalue){
	global $DB;
	$profile_field_id = get_position_profile_id($typevalue);
	if($profile_field_id){
		$record = $DB->get_record('user_info_data',array('userid'=>$userid,'fieldid'=>$profile_field_id));
		if(!empty($record)){
			$record->data = "";
			$DB->update_record('user_info_data',$record);
		}
	}
}
function update_user_position_profile($userid,$typevalue){
	global $DB;
	$profile_field_id = get_position_profile_id($typevalue);
	//assume that position is always exist
	//EDIT ME IF THE POSITION IS DELETED
	if($profile_field_id){
		$record = $DB->get_record('user_info_data',array('userid'=>$userid,'fieldid'=>$profile_field_id));
		if(!empty($record)){
			//Update the existing one
			$record->data = $typevalue;
			$DB->update_record('user_info_data',$record);
		}else{
			$new = new stdClass();
			$new->userid = $userid;
			$new->fieldid = $profile_field_id;
			$new->data = $typevalue;
			$new->dataformat = 0;
			$DB->insert_record('user_info_data',$new);
		}
	}
}

function get_context_systemid(){
	global $DB;
	if(!isset($_SESSION['CONTEXT_SYSTEM_ID'])){
		$_SESSION['CONTEXT_SYSTEM_ID'] = $DB->get_field('context','id',array('contextlevel'=>SYS_CONTEXT_LEVEL));
	}else return $_SESSION['CONTEXT_SYSTEM_ID'];
}

function get_coach_roleid(){
	global $DB;
	$roleid = $DB->get_field('role','id',array('shortname'=>ROLE_COACH));
	return $roleid;
}
function get_coachee_roleid(){
	global $DB;
	$roleid = $DB->get_field('role','id',array('shortname'=>ROLE_COACHEE));
	return $roleid;
}
function get_supervisor_roleid(){
	global $DB;
	$roleid = $DB->get_field('role','id',array('shortname'=>ROLE_STUDENT_SUPERVISOR));
	return $roleid;
}
function get_student_roleid(){
	global $DB;
	$roleid = $DB->get_field('role','id',array('shortname'=>ROLE_STUDENT));
	return $roleid;
}

function view_coach_resource_html($resources){
	$count=1;
	$html="";
	$attachment="";
	$html.="<table class='tablestyle1'>";
	foreach ($resources as $resource) {
		if($count==1){
			$url=(($resource->link=="")||($resource->link==NULL))? "" : "<a href='".$resource->link."' target='_blank'>".$resource->link."</a>";
			$html.="<tr>";
			$html.="<td>".get_string('resource:name','block_coach')."</td>";
			$html.="<td>".$resource->name."</td>";
			$html.="</tr>";
			$html.="<tr>";
			$html.="<td>".get_string('resource:description','block_coach')."</td>";
			$html.="<td>".$resource->description."</td>";
			$html.="</tr>";
			$html.="<tr>";
			$html.="<td>".get_string('resource:link','block_coach')."</td>";
			$html.="<td>".$url."</td>";
			$html.="</tr>";
		}
		if($resource->contenthash!=NULL){
			$attachment.="<li>";
			$attachment.=get_file_content_attachment($resource);
			$attachment.="</li>";
		}
		$count++;
	}
	if($attachment!=""){
		$html.="<tr>";
		$html.="<td>".get_string('resource:attachments','block_coach')."</td>";
		$html.="<td>".$attachment."</td>";
		$html.="</tr>";
	}
	$html.="</table>";
	$html.="<div style='text-align:center'><input type='button' id='close_btn_resource' value='Close'></div>";
	$html.='<script>
			  $("#close_btn_resource").click(function() {
			      $("#postto_background").hide();
			      $("#postto_popup").hide();
			  });
	</script>';
	return $html;
}
function get_file_content_attachment($resource){
	global $CFG;
	$html="";
	$width = 450;
	$height=350;
	$type = $resource->extension;
	$time = time();
	$url = $CFG->wwwroot."/blocks/coach/gettype.php?id=".$resource->id."&t=".time()."&file=download";
	// $url2 = $CFG->wwwroot."/blocks/resources/gettype.php?id%3D".$row->id."%26t%3D".$time;
	$ext = $resource->extension;
	switch ($type) {
		// case 'pdf':
		// 	$height = intval($width*1.3);
		// 	$html.='<iframe frameBorder="0" src="pdf/web/viewer.html?file='.$url2.'" width="'.$width.'px" height="'.($height).'" ></iframe>';
		// 	break;
		case "flv":
		case "mov":
		case "mp4":
			$height = intval((9*$width)/16);
			$html.="<video id='id_video' src='".$url.".".$ext."' width='".($width)."px' height='".($height)."px' type='video/mp4'></video>";
			break;
		case "wma":
		case "mp3":
			$html.="<audio width='".$width."px' height='".($height-50)."px' preload='auto' controls='controls' codecs='mp3'>
						<source width='".$width."px' height='".($height-50)."px' src='".$url.".mp3' type='audio/mp3'>
					</audio>";
			break;
		case 'png':
		case 'bmp':
		case 'gif':
		case 'jpg':
		case 'jpeg':
			$html.="<img src='".$url.".".$resource->extension."' class='resource_picture'>";
			break;
		default: // Download link
		$html.="<a href='".$url.".".$type."' target='_blank'> ".$resource->filename." </a>";
			break;
	}
	return $html;
}
function get_resources_html(){
	global $DB,$USER,$CFG;
	$html="";
	$html.=get_string('resource:title','block_coach');
	$html.="<div class='resource'>";
	$html.="<div class='resource_manage'><table width='100%'>";
		$html.="<tr><td><select size='15' id='resource_select'>";
		$html.=get_resource_options_list();
		$html.="</select></td></tr>";
		$html.="<tr><td><div class='btn' id='resource_remove'>Remove</div><div id='resource_remove_msg'></div></td></tr>";
	$html.="</table></div>";
	$html.="<div class='resource_upload'>";
	$html.="<form action='index.php' method='POST' enctype='multipart/form-data'>";
		$html.="<table class='tablestyle1'>";
		$html.="<tr>";
		$html.="<td>".get_string('resource:name','block_coach').get_string('required','block_coach')."</td>";
		$html.="<td><input type='text' name='resourcename' id='resourcename' required></td>";
		$html.="</tr>";
		$html.="<tr>";
		$html.="<td>".get_string('resource:description','block_coach')."</td>";
		$html.="<td><textarea name='resourcedesc' id='resourcedesc'></textarea></td>";
		$html.="</tr>";
		$html.="<tr>";
		$html.="<td>".get_string('resource:link','block_coach')."</td>";
		$html.="<td><input type='text' name='resourcelink' id='resourcelink'></td>";
		$html.="</tr>";
		$html.="<tr>";
		$html.="<td>".get_string('resource:attachment1','block_coach')."</td>";
		$html.="<td><input type='file' name='attachment1' id='attachment1' title='".get_string('attachment:tip','block_coach')."'>
		<div class='attachment_edit' id='attachment_edit1'></div>
		<input type='hidden' name='attachmentedit1' id='attachmentedit1' value=''>
		</td>";
		$html.="</tr>";
		$html.="<tr>";
		$html.="<td>".get_string('resource:attachment2','block_coach')."</td>";
		$html.="<td><input type='file' name='attachment2' id='attachment2' title='".get_string('attachment:tip','block_coach')."'>
		<div class='attachment_edit' id='attachment_edit2'></div>
		<input type='hidden' name='attachmentedit2' id='attachmentedit2' value=''>
		</td>";
		$html.="</tr>";
		$html.="<tr>";
		$html.="<td>".get_string('resource:attachment3','block_coach')."</td>";
		$html.="<td><input type='file' name='attachment3' id='attachment3' title='".get_string('attachment:tip','block_coach')."'>
		<div class='attachment_edit' id='attachment_edit3'></div>
		<input type='hidden' name='attachmentedit3' id='attachmentedit3' value=''>
		</td>";
		$html.="</tr>";
		$html.="<tr>";
		$html.="<td colspan='2'><input type='submit' name='sub' value='Save changes' style='margin-bottom: 0px;'>";
		$html.=" <a href='index.php' class='btn'> Cancel </a></td>";
		$html.="</tr>";
		$html.="</table>";
		$html.="<input type='hidden' name='eid' id='eid' value=''>";
		$html.="<input type='hidden' name='type' value='resource'>";
	$html.="</form>";
	$html.="<div id='resource_msg'>".get_string('resource:edit:note','block_coach')."</div>";
	$html.="</div>";
	$html.="</div>";
	return $html;
}
//Including the coach and coachee resources
function get_resource_options_list(){
	global $DB,$USER;
	$html="";
	$coacheeid="";
	$coachid="";
	if(isset($_SESSION['coacheeid'])){ 
		$coacheeid = $_SESSION['coacheeid'];
		$coachid = $USER->id;
	}else{
		$coachid = $DB->get_field('coachees','coachid',array('coacheeid'=>$USER->id));
		$coacheeid = $USER->id;
	}
	$sql="SELECT entry,creater,name,description,timecreated from mdl_coach_resource where coachid=? and coacheeid=? GROUP BY entry ORDER BY creater ASC,name ASC";
	$rs = $DB->get_records_sql($sql,array($coachid,$coacheeid));
	if(!empty($rs)){
		$coach_group = get_string('resource:coachoption','block_coach');
		$coachee_group = get_string('resource:coacheeoption','block_coach');
		if(isset($_SESSION['is_student_role'])&&($_SESSION['is_student_role'])){
			$coach_group = get_string('resource:supervisoroption','block_coach');
			$coachee_group = get_string('resource:studentoption','block_coach');
		}
		$coach_html="<optgroup label='".$coach_group."'>";
		$coachee_html="<optgroup label='".$coachee_group."'>";
		foreach ($rs as $row) {
			if($coachid==$row->creater){
				$coach_html.="<option value='".$row->entry."' title='Last date: ".date('d/m/Y',$row->timecreated)."'>".$row->name."</option>";
			}else{
				$coachee_html.="<option value='".$row->entry."' title='Last date: ".date('d/m/Y',$row->timecreated)."'>".$row->name."</option>";
			}
		}
		$coach_html.="</optgroup>";
		$coachee_html.="</optgroup>";
		$html.=$coach_html;
		$html.=$coachee_html;
	}
	return $html;
}
function update_coach_resource($post,$file,$is_student=false){
	global $DB,$USER;
	$coachee_name='coachee';
	if($is_student) $coachee_name = 'student';
	$error="";
	$coacheeid="";
	$coachid="";
	$html="";
	if(isset($_SESSION['coacheeid'])){ 
		$coacheeid = $_SESSION['coacheeid'];
	}else{
		if(!$DB->record_exists('coachees',array('coacheeid'=>$USER->id))){ 
			$html.=get_string('coachee:no_coach_data','block_coach',$coachee_name);
			return $html;
		}
		else $coachid = $DB->get_field('coachees','coachid',array('coacheeid'=>$USER->id));
	}
	$file_uploadeds = upload_resource_file($file,$error);
	if($error==""){
		//Check Action is INSERT OR UPDATE
		$entry_edit = $post['eid'];
		if($entry_edit==""){
			$new = new stdClass();
			$new->name = $post['resourcename'];
			$new->description = $post['resourcedesc'];
			$new->description = $post['resourcedesc'];
			$new->link = $post['resourcelink'];
			$new->creater =$USER->id;
			$new->coachid = ($coachid=="") ? $USER->id : $coachid;
			$new->coacheeid = ($coacheeid=="") ? $USER->id : $coacheeid;
			$new->timecreated = time();
			$new->entry = time().rand(10,99);
			if(!empty($file_uploadeds)){
				foreach ($file_uploadeds as $file) {
					$new->contenthash = $file->contenthash;
					$new->filename = $file->filename;
					$new->filetype = $file->filetype;
					$new->extension = $file->extension;
					$new->filesize = $file->filesize;
					$DB->insert_record('coach_resource',$new);	
				}
			}else $DB->insert_record('coach_resource',$new);
		}else{//UPDATE THE EXISTING RESOURCE
			$olds = $DB->get_records('coach_resource',array('entry'=>$entry_edit,'creater'=>$USER->id));
			if(!empty($olds)){
				foreach ($olds as $row) {
					$row->name = $post['resourcename'];
					$row->description = $post['resourcedesc'];
					$row->description = $post['resourcedesc'];
					$row->link = $post['resourcelink'];
					if(!empty($file_uploadeds)){
						for($i=1;$i<4;$i++){
							if(($row->id==$post['attachmentedit'.$i])&& isset($file_uploadeds['attachment'.$i])){
								delete_resource_path($row->contenthash);
								$row->contenthash = $file_uploadeds['attachment'.$i]->contenthash;
								$row->filename = $file_uploadeds['attachment'.$i]->filename;
								$row->filetype = $file_uploadeds['attachment'.$i]->filetype;
								$row->extension = $file_uploadeds['attachment'.$i]->extension;
								$row->filesize = $file_uploadeds['attachment'.$i]->filesize;
								unset($file_uploadeds['attachment'.$i]);
							}
						}
					}
					$row->timecreated = time();
					$DB->update_record('coach_resource',$row);
				}
				//IF WE HAVE NEW UPLOADED FILE
				if(!empty($file_uploadeds)){
					$new = new stdClass();
					$new->name = $post['resourcename'];
					$new->description = $post['resourcedesc'];
					$new->description = $post['resourcedesc'];
					$new->link = $post['resourcelink'];
					$new->creater =$USER->id;
					$new->coachid = ($coachid=="") ? $USER->id : $coachid;
					$new->coacheeid = ($coacheeid=="") ? $USER->id : $coacheeid;
					$new->timecreated = time();
					$new->entry = $entry_edit;
					foreach ($file_uploadeds as $file) {
						$new->contenthash = $file->contenthash;
						$new->filename = $file->filename;
						$new->filetype = $file->filetype;
						$new->extension = $file->extension;
						$new->filesize = $file->filesize;
						$DB->insert_record('coach_resource',$new);	
					}
				}
			}else $html.=get_string('resource:update:nopermission','block_coach');
		}
	}else $html.=get_string($error,'block_coach');
	return $html;
}
function delete_resource_path($filename){
	global $CFG;
	$folder = substr($filename,0,3);
	$file = $CFG->dataroot."/resources/".$folder."/".$filename;
	if(file_exists($file)) unlink($file);
}
function upload_resource_file($file,&$error=""){
	global $CFG,$USER;
	$max_file_upload = 120000000; // 50MB
	$allowedExts = array('pdf','doc','ppt','pptx','docx','xls','xlsx','csv','txt','rtf','html','zip','mp3','mp4','wma','mpg','flv','avi','jpg','jpeg','png','gif','bmp','mov');
	$file_ids = array('attachment1','attachment2','attachment3');
	$arr_result = array();
	foreach ($file_ids as $file_id) {
		if(isset($file[$file_id])){
			if(($file[$file_id]["name"]==NULL)||($file[$file_id]["name"]=="")) continue;
			$temp = explode(".", $file[$file_id]["name"]); 
			// echo "upload file\n<br>";
			$new = new stdClass();
			$new->attachment = $file_id;
			$new->filename = $file[$file_id]["name"];
			$new->filetype = $file[$file_id]["type"];
			$new->filesize = $file[$file_id]["size"];
			$new->contenthash = md5(uniqid($new->filename));
			$folder = substr($new->contenthash,0,3);
			$extension = strtolower(end($temp));
			$new->extension = $extension;
			if (in_array($extension, $allowedExts)) {
				if($file[$file_id]["size"] >$max_file_upload) $error = UPLOAD_EXCEED_MAX; // Over 50M will be rejected
				else{
					$mode = 0777;
					$path = $CFG->dataroot."/resources";
					if(!file_exists($path)) mkdir($path,$mode,true);

					$path.="/".$folder;
					if(!file_exists($path)) mkdir($path,$mode,true);
					$path.="/".$new->contenthash;
					$arr_result [$file_id] = $new;
					move_uploaded_file($file[$file_id]["tmp_name"], $path);
				}
			}else $error=UPLOAD_ERROR_FILETYPE;
		}
	}
	return $arr_result;
}
function get_discussion_html($is_student=false){
	global $DB,$USER,$CFG;
	$coachee_name='coachee';
	if($is_student) $coachee_name = 'student';	
	$coacheeid="";
	$coachid="";
	$coachee_no_data=false;
	$content="";
	$otherid ="";
	$is_coachee = false;
	if(isset($_SESSION['coacheeid'])){ 
		$coacheeid=$_SESSION['coacheeid'];
		$otherid = $coacheeid; 
		$coachid = $USER->id;
	}
	else{
		//status: user is coachee. Check if user has been assigned to any coach or not
		if(!$DB->record_exists('coachees',array('coacheeid'=>$USER->id))) $coachee_no_data=true;
		else{ 
			$coachid = $DB->get_field('coachees','coachid',array('coacheeid'=>$USER->id));
			$otherid = $coachid;
			$coacheeid = $USER->id;
			$is_coachee = true;
		}
	}

	$html="";
	$html.="<div class='discussion'>";
	$html.=get_string('discussion:title','block_coach');
	$html.="<div id='discussion_content'>";
	//content will be here
	$rs = $DB->get_records('coach_discuss',array('coachid'=>$coachid,'coacheeid'=>$coacheeid,'deleted'=>0),'timecreated ASC');
	if(!empty($rs)){
		$fullname="<a href='".$CFG->wwwroot."/user/profile.php?id=".$USER->id."'>".ucfirst($USER->firstname)." ".ucfirst($USER->lastname)."</a>";
		$other = $DB->get_record('user',array('id'=>$otherid));
		$other_fullname = "<a href='".$CFG->wwwroot."/user/profile.php?id=".$otherid."'>".ucfirst($other->firstname)." ".ucfirst($other->lastname)."</a>";
		foreach ($rs as $row) {

			$html.="<div class='discuss_line'>";
			$html.="<div class='discuss_header'>";
			$html.= ($row->creater==$USER->id) ? $fullname : $other_fullname;
			$html.="<div class='discuss_date'>(".date('d/m/Y H:i',$row->timecreated).") :</div>";
			$html.="</div>";
			$html.="<div class='content'>".nl2br($row->content)."</div>";
			$html.="</div>";
		}
	}
	$html.="</div>";
	if($coachee_no_data) $html.=get_string('coachee:no_coach_data','block_coach',$coachee_name);
	else{
		$html.="<form action='index.php' method='POST' id='dicussionPOST'>";
		$html.="<div class='message'><textarea name='message' id='message' required></textarea></div>";
		$html.="<div class='action'><input type='submit' name='sub' value='submit'></div>";
		if(!$is_coachee){
			$html.="<input type='hidden' name='c' value='".$coacheeid."'>";
		}
		$html.="<input type='hidden' name='type' value='discuss'>";
		$html.="</form>";
	}
	$html.="</div>";
	return $html;
}
function update_discussion_message($post){
	global $DB,$USER;
    if(!isset($post['c'])){
        //Current user is a coachee, we need to find their coach
        $c_id = $DB->get_field('coachees','coachid',array('coacheeid'=>$USER->id));
        $discuss = new stdClass();
        $discuss->content = $post['message'];
        $discuss->coachid = $c_id;
        $discuss->coacheeid = $USER->id;
        $discuss->timecreated = time();
        $discuss->creater = $USER->id;
        $DB->insert_record('coach_discuss',$discuss);
    }else{
        //Current user is a coach
        $discuss = new stdClass();
        $discuss->content = $post['message'];
        $discuss->coachid = $USER->id;
        $discuss->coacheeid = $post['c'];
        $discuss->creater = $USER->id;
        $discuss->timecreated = time();
        $DB->insert_record('coach_discuss',$discuss);
    }
}
function get_coachee_learningplan(){
	global $DB,$USER,$CFG;
	$html="";
	$userid = isset($_SESSION['coacheeid'])?$_SESSION['coacheeid']:$USER->id;
	require_once($CFG->dirroot.'/mod/learningplan/lib.php');
	$sql = 'SELECT * FROM mdl_learningplan_submission where userid=? ORDER BY timecreated DESC LIMIT 1';
	$submission = $DB->get_record_sql($sql,array($userid));
	if(!empty($submission)){
		$html.=view_html_table_submission($submission,true);
	}else $html.=get_string('not_submit_yet','block_coach');
	return $html;
	// $userid="";
	// $context_system = context_system::instance();
	// $can_addlearningplan = has_capability('block/coach:addlearningplan',$context_system);
	// if(isset($_SESSION['coacheeid'])) $userid = $_SESSION['coacheeid'];
	// else $userid = $USER->id;
	// $record = $DB->get_record('coach_learning_plan',array('userid'=>$userid));
	// $completiondate=""; $reviewdate=""; $agreementdate="";
	// $goal=""; $activity=""; $training="";
	// $competency="";
	// if(!empty($record)){
	// 	$goal = $record->goals;
	// 	$activity = $record->activities;
	// 	$training = $record->training;
	// 	$competency = get_user_competency($userid);
	// 	$agreementdate = ($record->agreementdate==NULL||$record->agreementdate==0)?"":date('d/m/Y',$record->agreementdate);
	// 	$reviewdate = ($record->reviewdate==NULL||$record->reviewdate==0)?"":date('d/m/Y',$record->reviewdate);
	// 	$completiondate = ($record->completiondate==NULL||$record->completiondate==0)?"":date('d/m/Y',$record->completiondate);
	// }
	// $user = $DB->get_record('user',array('id'=>$userid));
	// $fullname = ucfirst($user->firstname)." ".ucfirst($user->lastname);
	// $html.="<div class='learningplan'>";
	// // if(isset($_SESSION['is_student_role'])&&($_SESSION['is_student_role']))
	// // 	$html.=get_string('studentlearning:title','block_coach',$fullname);
	// // else $html.=get_string('learning:title','block_coach',$fullname);
	// $html.="<table class='tablestyle1'>";
	// $html.="<tr>";
	// $html.="<th>".get_string('learning:goal','block_coach')."</th>";
	// $html.="<td>".$goal."</td>";
	// $html.="</tr>";
	// $html.="<tr>";
	// $html.="<th>".get_string('learning:competency','block_coach')."</th>";
	// $html.="<td>".$competency."</td>";
	// $html.="</tr>";
	// $html.="<tr>";
	// $html.="<th>".get_string('learning:activity','block_coach')."</th>";
	// $html.="<td>".$activity."</td>";
	// $html.="</tr>";
	// $html.="<tr>";
	// $html.="<th>".get_string('learning:training','block_coach')."</th>";
	// $html.="<td>".$training."</td>";
	// $html.="</tr>";
	// $html.="<tr>";
	// $html.="<th>".get_string('learning:agreementdate','block_coach')."</th>";
	// $html.="<td>".$agreementdate."</td>";
	// $html.="</tr>";
	// $html.="<tr>";
	// $html.="<th>".get_string('learning:reviewdate','block_coach')."</th>";
	// $html.="<td>".$reviewdate."</td>";
	// $html.="</tr>";
	// $html.="<tr>";
	// $html.="<th>".get_string('learning:completiondate','block_coach')."</th>";
	// $html.="<td>".$completiondate."</td>";
	// $html.="</tr>";
	// $html.="</table>";
	// $html.="</div>";
	// if(isset($_SESSION['coacheeid'])&&$can_addlearningplan){
	// 	$html.="<a class='btn' href='".$CFG->wwwroot."/blocks/coach/learningplan_update.php?id=".$_SESSION['coacheeid']."'>".get_string('learning:update','block_coach')."</a>";
	// }
}
function get_user_competency($userid){
	global $DB;
	$html="";
	//PLEASE EDIT ME WHEN CONNECT WITH COMPETENCIES IN OTHER FEATURES
	$sql="SELECT cac.* from {career_competency} as cac INNER JOIN {coach_competencies} coc ON cac.id=coc.competencyid and userid=?";
	$rs = $DB->get_records_sql($sql,array($userid));
	if(!empty($rs)){
		foreach ($rs as $row) {
			$html.="<p>".$row->name."</p>";
		}
	}
	return $html;
}
//Apply for coach or supervisor permission
function get_team_member_user_options($is_student){
	global $DB,$USER;
	$coacheeid="";
	if(isset($_SESSION['coacheeid'])) $coacheeid = $_SESSION['coacheeid'];
	$html="";
	$sql="SELECT u.id,u.firstname,u.lastname,u.email from mdl_user as u 
	 INNER JOIN mdl_coachees co on co.coacheeid=u.id WHERE co.coachid=? and u.deleted=0 and u.suspended=0";
	$rs = $DB->get_records_sql($sql,array($USER->id));
	if(!empty($rs)){
		$select = "<option value=''></option>";
		if(count($rs)>1){
			foreach ($rs as $row) {
				$fullname = ucfirst($row->firstname)." ".ucfirst($row->lastname)."(".$row->email.")";
				if($row->id==$coacheeid){
					$select.="<option value='".$row->id."' selected>".$fullname."</option>";
				}else{
					$select.="<option value='".$row->id."'>".$fullname."</option>";
				}
			}
			$html="<select name='coachee' class='chosen-select' onchange='this.form.submit();'>".$select."</select>";	
		}else{
			foreach ($rs as $row) {
				$_SESSION['coacheeid'] = $row->id;
				$html = ucfirst($row->firstname)." ".ucfirst($row->lastname)."(".$row->email.")";
			}
		}
		
	}
	$title_name = 'Coachee';
	if($is_student) $title_name = 'Student';
	$html="<form action='index.php' method='POST'><table class='coachee_table'>
	<tr>
	<th>".$title_name.": </th>
	<th>".$html."</th>
	</tr>
	</table></form>";
	return $html;
}
//coach option only
function get_coachs_options($coachid=""){
	global $DB;
	$html="";
	if($coachid!=""){
		$row = $DB->get_record('user',array('id'=>$coachid));
		if(!empty($row)){
			$fullname = ucfirst($row->firstname)." ".ucfirst($row->lastname)."(".$row->email.")";
			$html.="<option value='".$row->id."' selected>".$fullname."</option>";
		}
	}else{
		$sql = "SELECT u.id,u.firstname,u.lastname,u.email from {user} as u where id not in(select userid from {coach} where is_student=".NOT_STUDENT_OR_SUPERVISOR.") and u.deleted=0 and u.suspended=0";
		$rs = $DB->get_records_sql($sql);
		if(!empty($rs)){
			foreach ($rs as $row) {
				$fullname = ucfirst($row->firstname)." ".ucfirst($row->lastname)."(".$row->email.")";
				$html.="<option value='".$row->id."'>".$fullname."</option>";
			}
		}
	}
	return $html;
}
function get_available_coachees_options($coachid){
	global $DB;
	//We mind need to limit coachees users only in here. Please edit it
	$html="";
	$is_graduate_profile_id = $DB->get_field('user_info_field','id',array('shortname'=>'IsGraduateStudent'));
	$is_student_profile_id = $DB->get_field('user_info_field','id',array('shortname'=>'IsStudent'));

	$sql="SELECT u.id,u.firstname,u.lastname,u.email  
	FROM mdl_user as u WHERE 
	 u.id not in(SELECT userid FROM mdl_user_info_data WHERE fieldid=? AND data=?) AND 
	 u.id not in(SELECT userid FROM mdl_user_info_data WHERE fieldid=? AND data=?)";
	$rs = $DB->get_records_sql($sql,array($is_graduate_profile_id,MDL_CHECKBOX_CHECK,$is_student_profile_id,MDL_CHECKBOX_CHECK));
	if(!empty($rs)){
		// echo "<pre>".print_r($rs,true)."</pre>";
		$sql_exception = 'SELECT * from mdl_coachees where coacheeid=? AND (coachid<>? OR is_student<>?)';
		foreach ($rs as $row) {
			//If coach will not be a coachee, please edit me here

			//CHECK IF USER ARE NOT BELONG TO COACHEE, DO NOT ADD THEM 
			if($DB->record_exists_sql($sql_exception,array($row->id,$coachid,COACH_COACHEE)))
				continue;
  			
			$fullname = ucfirst($row->firstname)." ".ucfirst($row->lastname)."(".$row->email.")";
			if($DB->record_exists('coachees',array('coachid'=>$coachid,'coacheeid'=>$row->id)))
			 	$html.="<option value='".$row->id."' selected>".$fullname."</option>";
			else
				$html.="<option value='".$row->id."'>".$fullname."</option>";
		}
	}
	return $html;
}
//GET student and graduate students
function get_available_students_options($supervisorid,$is_graduate=false){
	global $DB;
	$context_system_id = get_context_systemid();
 	$student_role_id = get_student_roleid();

 	$is_staff_profile_id = $DB->get_field('user_info_field','id',array('shortname'=>'IsStaff'));
	$is_graduate_profile_id = $DB->get_field('user_info_field','id',array('shortname'=>'IsGraduateStudent'));
	$is_student_profile_id = $DB->get_field('user_info_field','id',array('shortname'=>'IsStudent'));
	// echo $is_staff_profile_id.":".$is_graduate_profile_id;
	//We mind need to limit coachees users only in here. Please edit it
	$html="";
	$sql="SELECT u.id,u.firstname,u.lastname,u.email  
	FROM mdl_user as u WHERE 
	 u.id not in(SELECT userid FROM mdl_user_info_data WHERE fieldid=? AND data=?) AND 
	 u.id not in(SELECT userid FROM mdl_user_info_data WHERE fieldid=? AND data=?)";
	//Graduate => Get all users except staff and student
	if($is_graduate){//GRADUATE
		$rs = $DB->get_records_sql($sql,array($is_staff_profile_id,MDL_CHECKBOX_CHECK,$is_student_profile_id,MDL_CHECKBOX_CHECK));
	}else{
		//STUDENT => get all users except staff and graduate
		$rs = $DB->get_records_sql($sql,array($is_staff_profile_id,MDL_CHECKBOX_CHECK,$is_graduate_profile_id,MDL_CHECKBOX_CHECK));
	}
	// echo $sql;
	// echo "<pre>".print_r($rs,true)."</pre>";
	// die();
	if(!empty($rs)){
		$sql_exception = 'SELECT * from mdl_coachees where coacheeid=? AND (coachid<>? OR is_student<>?)';
		foreach ($rs as $row) {
			//If the user has been assigned to someone, they will not be listed in here
			//Avoid if the user is the coach or supervisor
			if(is_coach_or_supervisor($row->id)) continue;
			if($is_graduate){
				//Avoid if users are student
				//Avoid if user are assigned to other supervisor
				// echo "<br>\n".$row->id.":".$supervisorid.":";
				if($DB->record_exists_sql($sql_exception,array($row->id,$supervisorid,GRADUTE_STUDENT))){ 
					// echo ": rejected"; 
					continue;
				}
			}else{
				if($DB->record_exists_sql($sql_exception,array($row->id,$supervisorid,STUDENT_OR_SUPERVISOR))) continue;
			}
			// echo "<br>\n".$row->coachid."::".$supervisorid;
			$fullname = ucfirst($row->firstname)." ".ucfirst($row->lastname)."(".$row->email.")";
			if($DB->record_exists('coachees',array('coachid'=>$supervisorid,'coacheeid'=>$row->id))) 
				$html.="<option value='".$row->id."' selected>".$fullname."</option>";
			else{
				$html.="<option value='".$row->id."'>".$fullname."</option>";
			}
		}
	}
	return $html;
}
function delete_coach_supervisor($userid,$is_student){
	global $DB;
	//Delete all coachee
	if($is_student == STUDENT_OR_SUPERVISOR) remove_user_position_profile($userid,POSITION_STUDENTSUPERVISOR);
	else remove_user_position_profile($userid,POSITION_COACH);
	$DB->delete_records('coachees',array('coachid'=>$userid,'is_student'=>$is_student));
	$DB->delete_records('coach',array('userid'=>$userid,'is_student'=>$is_student));
}
function delete_coach($userid){
	global $DB;
	//Delete all coachee
	update_profile_field_value('IsCoach',$userid,MDL_CHECKBOX_UNCHECK);
	$DB->delete_records('coachees',array('coachid'=>$userid,'is_student'=>NOT_STUDENT_OR_SUPERVISOR));
	$DB->delete_records('coach',array('userid'=>$userid,'is_student'=>NOT_STUDENT_OR_SUPERVISOR));
}
function delete_student_supervisor($userid){
	global $DB;
	//Delete all coachee and coach
	update_profile_field_value('IsSupervisor',$userid,MDL_CHECKBOX_UNCHECK);
	$DB->delete_records('coachees',array('coachid'=>$userid,'is_student'=>STUDENT_OR_SUPERVISOR));
	$DB->delete_records('coach',array('userid'=>$userid,'is_student'=>STUDENT_OR_SUPERVISOR));
}
function get_coach_supervisor_userid(){
	global $DB,$USER;
	$type=0;
	if(isset($_SESSION['is_student_role'])&&($_SESSION['is_student_role'])) $type=1;
	return $DB->get_field('coachees','coachid',array('coacheeid'=>$USER->id,'is_student'=>$type));
}

function get_competency_options($coacheeid){
	global $DB;
	$html="";
	$rs = $DB->get_records('career_competency');
	if(!empty($rs)){
		foreach ($rs as $row) {
			if($DB->record_exists('coach_competencies',array('userid'=>$coacheeid,'competencyid'=>$row->id))) $html.="<option value='".$row->id."' selected>".$row->name."</option>";
			else $html.="<option value='".$row->id."'>".$row->name."</option>";
		}
	}
	return $html;
}

//We need to update the coach, coachee, student, Student preceptor with the profile fields. Just in case  they forget to update in the profile.
function update_profile_field_value($fieldname,$userid,$value){
	global $DB;
	if($fieldid = $DB->get_field('user_info_field','id',array('shortname'=>$fieldname))){
		$record = $DB->get_record('user_info_data',array('userid'=>$userid,'fieldid'=>$fieldid));
		if(!empty($record)){
			$record->data = $value;
			if($DB->update_record('user_info_data',$record)) return "";
		}else{
			$new = new stdClass();
			$new->userid = $userid;
			$new->fieldid = $fieldid;
			$new->data = $value;
			if($DB->insert_record('user_info_data',$new)) return "";

		}
	}else return "Could not find profile field ".$fieldname.". ";
}


// Directly supervising a student means that unless a user is set up as a student supervisor and has student placement users associated with them, the student supervisor widget shouldn't be displayed
function is_supervising($userid){
	global $DB;

	return $DB->count_records_select('coachees', 'coachid = ? OR coacheeid = ?', array($userid, $userid)) > 0;
}

function is_coach_or_supervisor($userid){
	global $DB;
	return $DB->record_exists('coach',array('userid'=>$userid));
}
?>