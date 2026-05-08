<?php
//$fromuserid = -1 if there is no sender
function email_to_user_delay($touser, $fromuser,$subject,$text,$html,$attachpaths="",$attachnames=""){
	global $DB;
	$new = new stdClass();
	$new->touserid =$touser->id;
	$new->toemail = $touser->email;
	if(!isset($fromuser->id) || $fromuser->id=="") $fromuser->id = -1;
	$new->fromuserid = $fromuser->id;
	$new->fromemail = $fromuser->email;
	$new->subject = $subject;
	$new->textcontent = $text;
	$new->htmlcontent = $html;
	if($attachpaths!="" && $attachnames!=""){
		$attachpaths = json_encode($attachpaths);
		$attachnames = json_encode($attachnames);
	}
	$new->attachmentpath = $attachpaths;
	$new->attachmentname = $attachnames;
	$new->issent = 0;
	$new->timecreated = time();
	if($DB->insert_record('delayemail_queue',$new)) return true;
	else return false;
}
function email_to_userid_delay($touserid, $fromuserid,$subject,$text,$html,$attachpaths="",$attachnames=""){
	global $DB;
	$new = new stdClass();
	$new->touserid =$touserid;
	$new->fromuserid = $fromuserid;
	$new->subject = $subject;
	$new->textcontent = $text;
	$new->htmlcontent = $html;
	if(is_array($attachpaths) && is_array($attachnames)){
		$attachpaths = json_encode($attachpaths);
		$attachnames = json_encode($attachnames);
	}
	$new->attachmentpath = $attachpaths;
	$new->attachmentname = $attachnames;
	$new->issent = 0;
	$new->timecreated = time();
	if($DB->insert_record('delayemail_queue',$new)) return true;
	else return false;
}
