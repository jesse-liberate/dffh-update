<?php

// MA-MODIFIED 

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');

$userid = required_param('userid', PARAM_INT);

$user_sessions = ma_get_user_sessions($userid);

$user_sessions_list = array();

foreach ($user_sessions as $key => $session) {

    // exclude cancled signups
    if ($session->signup_statuscode >= 30) {
        $session_dates = $DB->get_records('facetoface_sessions_dates', array('sessionid'=>$session->id));
        foreach ($session_dates as $key => $session_date) {
            $session_listitem = clone $session;
            $session_listitem->timestart = $session_date->timestart;
            $session_listitem->start_date  = userdate($session_date->timestart,  '%d %b %y - %H:%M');
            $session_listitem->finish_date = userdate($session_date->timefinish, '%d %b %y - %H:%M');

            // limit return size
            if (count($user_sessions_list) <= 10) {
                $user_sessions_list[] = $session_listitem;
            }
        }        
    }

}

// sort by start date
usort($user_sessions_list, "cmp");

// JavaScript arrays are always consecutively numerically indexed.
// Use  array_values() to discard the original array keys and replace them with zero-based consecutive numbering
echo json_encode(array_values($user_sessions_list));


function cmp($a, $b)
{
    return ($a->timestart < $b->timestart);
}

