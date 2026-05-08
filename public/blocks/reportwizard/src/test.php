<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/lib.php');

// run_reportwizard_schedule();
$recipients = array(9862,4213);
$more_users = $DB->get_fieldset_select('cohort_members','userid','cohortid=63',[]);
foreach ($more_users as $uid) {
	if(!in_array($uid, $recipients)) $recipients [] = $uid; 
}

$report = $DB->get_record('report_wzd_report',array('id'=>181));
$attachment = $CFG->dataroot."/report_2021_05_21.csv";
$filename = 'report_2021_05_21.csv';
// foreach ($recipients as $value) {
// 	$user = $DB->get_record('user',array('id'=>$value),'firstname,lastname');
// 	echo "";
// }
// send_email_reportwizard($report, $recipients, $attachment, $filename);