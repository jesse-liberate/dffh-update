<?php

// MA-MODIFIED 

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');
require_once(__DIR__ . '/../lib_mindatlas.php');

$userid = required_param('userid', PARAM_INT);
$sessionid =  required_param('sessionid', PARAM_INT);
$signupstatusid =  required_param('signupstatusid', PARAM_INT);
$direction =  required_param('direction', PARAM_TEXT);

// debug
// $userid = 2;
// $sessionid = 1;
// $signupstatusid = 7;
// $direction = 1;



$is_admin = is_siteadmin($userid);

$result = new StdClass;
$result->signupstatusid = $signupstatusid;
$result->message = move_attendee($userid, $sessionid, $signupstatusid, $direction);



// JavaScript arrays are always consecutively numerically indexed.
// Use  array_values() to discard the original array keys and replace them with zero-based consecutive numbering
echo json_encode($result);



/**
 * move attendee in waitlist to change their priority
 * @param integer user ID, the user who action this
 * @param integer Session ID
 * @param integer sign up status ID
 * @param string  direction
 * @return array
 */
function move_attendee($userid, $sessionid, $signupstatusid, $direction){
	global $DB;
	
	$waitlist_attendees = ma_facetoface_get_waitlist_attendees($sessionid, $userid);


	$key_current = null;
	$current = null;
	$new = null;
	foreach ($waitlist_attendees as $key => $attendee) {
		if ($attendee->signupstatusid == $signupstatusid) {
			$key_current = $key;
			$current = $attendee;
		}
	}

	if (!$current) {
		return 'can not find attendee';
	}

	$priority_1 = null;
	$priority_2 = null;

	if ($direction == 'UP') { // move up
		if ($key_current == 0) { // first
			return 'Already at top, can not move up';
		}elseif ($key_current == 1) { // second
			$priority_1 = $waitlist_attendees[$key_current-1]->waitlist_priority;
			$priority_2 = $waitlist_attendees[$key_current-1]->waitlist_priority-1;
		}else{
			$priority_1 = $waitlist_attendees[$key_current-1]->waitlist_priority;
			$priority_2 = $waitlist_attendees[$key_current-2]->waitlist_priority;
		}
	}else if($direction == 'DOWN'){ // move down
		if ($key_current == (count($waitlist_attendees)-1) ) { // last
			return 'Already at button, can not move down';
		}elseif ($key_current == (count($waitlist_attendees)-2) ){ // last second
			$priority_1 = $waitlist_attendees[$key_current+1]->waitlist_priority;
			$priority_2 = time();
		}else{
			$priority_1 = $waitlist_attendees[$key_current+1]->waitlist_priority;
			$priority_2 = $waitlist_attendees[$key_current+2]->waitlist_priority;
		}
	}else if($direction == 'TOP'){
		if ($key_current == 0){
			return 'Already top, can not move up';			
		}else{
			$priority_1 = $waitlist_attendees[0]->waitlist_priority;
			$priority_2 = $waitlist_attendees[0]->waitlist_priority-1;
		}
	}


	$new_priority = ($priority_1 + $priority_2)/2;

	$record = $DB->get_record('facetoface_signups_status', array('id'=>$signupstatusid));
	$record->waitlist_priority = $new_priority;

	$DB->update_record('facetoface_signups_status', $record);

	return $record;


}