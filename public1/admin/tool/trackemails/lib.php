<?php
//$fromuserid = -1 if there is no sender
define('NOT_SENT_YET',0);
define('ALREADY_SENT',1);
define('EMAIL_ERROR',2);
function trackemail_to_userid($tablename,$instanceid, $touserid, $subject,$html){
	global $DB;
	if($touserid!="" && $touserid!=NULL){
		if(!$DB->record_exists('trackemails',array('tablename'=>$tablename,'instanceid'=>$instanceid,'touserid'=>$touserid))){
			$new = new stdClass();
			$new->tablename =$tablename;
			$new->instanceid =$instanceid;
			$new->touserid =$touserid;
			$new->subject = $subject;
			$new->htmlcontent = nl2br($html);
			$new->issent = NOT_SENT_YET;
			$new->timecreated = time();
			if($DB->insert_record('trackemails',$new)) return true;
			else return false;
		}else return false;
	}else return false;
}

?>