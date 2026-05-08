<?php
define('CLI_SCRIPT', true);
require(realpath(dirname(__FILE__)) . '/../../../config.php');
include("lib.php");
global $DB;
$rs = $DB->get_records('trackemails',array('issent'=>0),'timecreated ASC');
if(!empty($rs)){
	$support_user = core_user::get_support_user();
	echo "Start sending email out <br>\n";
	foreach($rs as $row){
		echo "Checking row ".$row->id."<br>\n";
		$row->note="";
		$user = $DB->get_record('user',array('id'=>$row->touserid));

		if($user->email==NULL || (strpos($user->email,'@')==false)){
			$row->note .= "Receiver's email is error. ";
			$row->issent = EMAIL_ERROR;
			$row->timemodified = time();
			$DB->update_record('trackemails',$row);
		}else{
			$content = html_to_text($row->htmlcontent);
			if(email_to_user($user,$support_user,$row->subject,$content,$row->htmlcontent)){
				echo "Send email to user ".$touser->firstname." ".$touser->lastname."(".$touser->email.") <br>\n";
				$row->note.="Email has sent.";
				$row->issent = ALREADY_SENT;
				$row->timemodified = time();
				$DB->update_record('trackemails',$row);
			}
		}
	}
}else echo "No email";
?>