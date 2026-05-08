<?php
define('CLI_SCRIPT', true);
require(realpath(dirname(__FILE__)) . '/../../../config.php');
//require_once($CFG->dirroot."/lib/mindatlas/malib.php");
require_once("lib.php");
global $DB;
$rs = $DB->get_records('delayemail_queue',array('issent'=>0));
if(!empty($rs)){
	$support_user = core_user::get_support_user();
	echo "Start sending email out <br>\n";
	foreach($rs as $row){
		echo "Checking row ".$row->id."<br>\n";
		$row->note="";
		$touser = $DB->get_record('user',array('id'=>$row->touserid));
		if($row->toemail!=NULL && $row->toemail!="") $touser->email = $row->toemail;

		if($row->fromuserid== -1) $fromuser = $support_user;
		else $fromuser = $DB->get_record('user',array('id'=>$row->fromuserid));
		if($row->fromemail!=NULL && $row->fromemail!="") $fromuser->email = $row->fromemail;

		if(($touser->email==NULL)||(strpos($touser->email,'@')==false)){ 
			$touser->email = $support_user->email;
			$row->note .= "Receiver's email is error. ";
		}
		//Check if the sending email with the attachment or not
		if($row->attachmentpath!=NULL && $row->attachmentpath!=""){

			$attachment_paths = array();
			$attachment_names = array();
			$results = json_decode($row->attachmentpath,true);
 			if(json_last_error() == JSON_ERROR_NONE){
 				$attachment_paths = json_decode($row->attachmentpath,true);
 				$attachment_names = json_decode($row->attachmentname,true);
			}else{
				$attachment_paths [] = $row->attachmentpath;
				$attachment_names [] = $row->attachmentname;
			}
			if(ma_email_to_user($touser,$fromuser,$row->subject,$row->textcontent,$row->htmlcontent,$attachment_paths,$attachment_names)){
				foreach ($attachment_paths as $attachment_path_detail) {
					unlink($CFG->dataroot . '/' . $attachment_path_detail);
				}
				echo "Send email to user ".$touser->firstname." ".$touser->lastname."(".$touser->email.") <br>\n";
				$row->note.="Email has sent.";
				$row->issent = 1;
				$row->timemodified = time();
				$DB->update_record('delayemail_queue',$row);
			}
		}else if(ma_email_to_user($touser,$fromuser,$row->subject,$row->textcontent,$row->htmlcontent)){
			echo "Send email to user ".$touser->firstname." ".$touser->lastname."(".$touser->email.") <br>\n";
			$row->note.="Email has sent.";
			$row->issent = 1;
			$row->timemodified = time();
			$DB->update_record('delayemail_queue',$row);
		}
	}
}else echo "No email";
?>