<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Copyright (C) 2007-2011 Catalyst IT (http://www.catalyst.net.nz)
 * Copyright (C) 2011-2013 Totara LMS (http://www.totaralms.com)
 * Copyright (C) 2014 onwards Catalyst IT (http://www.catalyst-eu.net)
 *
 * @package    mod
 * @subpackage facetoface
 * @copyright  2014 onwards Catalyst IT <http://www.catalyst-eu.net>
 * @author     Stacey Walker <stacey@catalyst-eu.net>
 * @author     Alastair Munro <alastair.munro@totaralms.com>
 * @author     Aaron Barnes <aaron.barnes@totaralms.com>
 * @author     Francois Marier <francois@catalyst.net.nz>
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');

$s = required_param('s', PARAM_INT); // Facetoface session ID.
$backtoallsessions = optional_param('backtoallsessions', 0, PARAM_INT);
$dietoptions = optional_param('dietOptions','', PARAM_TEXT); // Session id
$diettext = optional_param('diettext','', PARAM_TEXT); // Session id
$accessOptions = optional_param('accessOptions','', PARAM_TEXT); // Session id
$accesstext = optional_param('accesstext','', PARAM_TEXT); // Session id
$update = optional_param('update','', PARAM_TEXT); // Session id
if (!$session = facetoface_get_session($s)) {
    throw new moodle_exception('error:incorrectcoursemodulesession', 'facetoface');
}
if (!$facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface))) {
    throw new moodle_exception('error:incorrectfacetofaceid', 'facetoface');
}
if (!$course = $DB->get_record('course', array('id' => $facetoface->course))) {
    throw new moodle_exception('error:coursemisconfigured', 'facetoface');
}
if (!$cm = get_coursemodule_from_instance("facetoface", $facetoface->id, $course->id)) {
    throw new moodle_exception('error:incorrectcoursemoduleid', 'facetoface');
}

require_course_login($course, true, $cm);
$context = context_course::instance($course->id);
$contextmodule = context_module::instance($cm->id);
require_capability('mod/facetoface:view', $context);

    $returnurl = "$CFG->wwwroot/mod/ma_facetoface_ext/my_training_sessions.php";

// if ($backtoallsessions) {
//     $returnurl = "$CFG->wwwroot/mod/facetoface/view.php?f=$backtoallsessions";
// }
$currentpage = $_SERVER['HTTP_REFERER'];    // MA-MODIFIED 

$pagetitle = format_string($facetoface->name);

$PAGE->set_cm($cm);
$PAGE->set_url('/mod/facetoface/signup.php', array('s' => $s, 'backtoallsessions' => $backtoallsessions));

$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);
// $is_admin = is_siteadmin($USER->id);

// Guests can't signup for a session, so offer them a choice of logging in or going back.
if (isguestuser()) {
    $loginurl = $CFG->wwwroot.'/login/index.php';
    if (!empty($CFG->loginhttps)) {
        $loginurl = str_replace('http:', 'https:', $loginurl);
    }

    echo $OUTPUT->header();
    $out = html_writer::tag('p', get_string('guestsno', 'facetoface')) .
        html_writer::empty_tag('br') .
        html_writer::tag('p', get_string('continuetologin', 'facetoface'));
    echo $OUTPUT->confirm($out, $loginurl, get_local_referer(false));
    echo $OUTPUT->footer();
    exit();
}

// MA-MODIFIED ===>
/* --------------- Get hierarchy managers' email then add to user_info_data ---------------- */

$transaction = $DB->start_delegated_transaction();
// if (face_to_face_check_hierarchy()) {

if (false) {
    $getNodeId = "SELECT node_id
                  FROM {hierarchy_user}
                  WHERE user_id = ?";
    $current_nodeid = current($DB->get_records_sql($getNodeId, array($USER->id)))->node_id;     

    if (!is_null($current_nodeid) && !is_siteadmin()) {

        $getParentNodeId = "SELECT parent_node_id
                            FROM {hierarchy_node}
                            WHERE id = ?";
        //use hierarchy lib function to directly get one level up manager until find it                    
        $parents = tool_hierarchy_get_user_managers($USER->id);
        $managerids = [];
        if($parents){
            foreach($parents as $parent){
                $managerids[] = $parent->id;
            }
        }
        
        // $parent_id = array_values($parent);
        $parent_nodeid = current($DB->get_records_sql($getParentNodeId, array($current_nodeid)))->parent_node_id;     
        
        //  var_dump($managerids);var_dump($parent_nodeid);die();
        if (!is_null($parent_nodeid)) {
            
            $getManagerIds = "SELECT user_id
                              FROM {hierarchy_user}
                              WHERE node_id = ?";
            $manager_ids = $DB->get_records_sql($getManagerIds, array($parent_nodeid));     

            $getEmail = "SELECT email
                         FROM {user}
                         WHERE id = ?";
            $emaillist = array(); // Avoid the case of no email manager
            $errors = array_filter($managerids);

            if (!empty($errors)) {
                $manager_ids = $managerids;
            }
            foreach ($manager_ids as $id) {
                // $email = current($DB->get_records_sql($getEmail, array($id->user_id)))->email;
                $email = current($DB->get_records_sql($getEmail, array($id)))->email;
                if (!is_null($email) && $email != '') {
                    $emaillist[] = $email;                    
                }
            }
            if(!empty($emaillist)){
                $managersemail = implode(';', $emaillist);}
            else{
                // If manager's email is empty, it should send to admin's email.
                $adminids = $DB->get_field('config','value',array('name'=>'siteadmins'));
                $arr_admins = explode(",",$adminids);
                $main_admin_id = reset($arr_admins);
                if(!empty($main_admin_id)){
                    $managersemail = $DB->get_field('user','email',array('id'=>$main_admin_id));
                }
            }
        }
    }
}

// $fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => MDL_MANAGERSEMAIL_FIELD));
//=============================
// Add manager's email automatically. So, the feature can be run without required configuration {eric@mindatlas.com}
// if(!$fieldid) {
//     // Add the new field into database
//     $field = new stdClass();
//     $field->shortname = MDL_MANAGERSEMAIL_FIELD;
//     $field->name = "Manager's email";
//     $field->datatype = "text";
//     $field->categoryid=1;
//     $fieldid = $DB->insert_record('user_info_field',$field);
// }
// $isEmailAdded = $DB->record_exists('user_info_data', array('userid'=>$USER->id, 'fieldid'=>$fieldid));

// if (!$isEmailAdded) {
//     $record = new stdClass();
//     $record->userid = $USER->id;
//     $record->fieldid = $fieldid;   // field id for Manager's email. Should set up Manager's email field from front end first.
//     if(!isset($managersemail)) $record->data="";
//     else $record->data = $managersemail;
//     $lastinsertid = $DB->insert_record('user_info_data', $record, false);
// }else{ // Update the email in case of moving user to another node, then email is not up to date {eric@mindatlas.com}
//     if(!empty($managersemail)){
//         $record = $DB->get_record('user_info_data',array('userid'=>$USER->id,'fieldid'=>$fieldid));
//         $record->data = $managersemail;
//         $lastinsertid = $DB->update_record('user_info_data', $record, false);
//     }
// }
//=============================
$transaction->allow_commit();

/* ------------------------------------------------------------------------------------------ */
// <=== MA-MODIFIED 

$manageremail = false;


$showdiscountcode = ($session->discountcost > 0);

// MA-MODIFIED ===>

    $mform = new mod_facetoface_signup_noform(
        null,
        compact('s', 'backtoallsessions', 'manageremail', 'showdiscountcode','facetoface')
    );

// <=== MA-MODIFIED 

if ($mform->is_cancelled()) {
    redirect($returnurl);
}


if ($fromform = $mform->get_data()) { // Form submitted.
    // MA-MODIFIED ===>
    if(isset($fromform->manageremail) && $fromform->manageremail){
        $chosen_manageremail = $fromform->manageremail;
    }
    // var_dump($chosen_manageremail); die();

    // Disable to select notification type, no iCal included
    $fromform->notificationtype = MDL_F2F_ICAL;
    // <=== MA-MODIFIED 

    if (empty($fromform->submitbutton)) {
        throw new moodle_exception('error:unknownbuttonclicked', 'facetoface', $returnurl);
    }

    // User can not update Manager's email (depreciated functionality).
    if (!empty($fromform->manageremail)) {

        // Logging and events trigger.
        $params = array(
            'context'  => $contextmodule,
            'objectid' => $session->id
        );
        $event = \mod_facetoface\event\update_manageremail_failed::create($params);
        $event->add_record_snapshot('facetoface_sessions', $session);
        $event->add_record_snapshot('facetoface', $facetoface);
        $event->trigger();
    }

    // Get signup type.
    if (!$session->datetimeknown) {
        $statuscode = MDL_F2F_STATUS_WAITLISTED;
    } else if (facetoface_get_num_attendees($session->id) < $session->capacity) {

        // Save available.
        $statuscode = MDL_F2F_STATUS_BOOKED;
    } else {
        $statuscode = MDL_F2F_STATUS_WAITLISTED;
    }
    if($dietoptions){

        $session->dietoptions = $dietoptions;
        $session->diettext = $diettext;
        $session->accessOptions = $accessOptions;
        $session->accesstext = $accesstext;
        }
        if(!empty($update)){
            facetoface_user_signup($session, $facetoface, $course, $fromform->discountcode, $fromform->notificationtype, $statuscode);  
            redirect($returnurl);
        }
        
    if (!facetoface_session_has_capacity($session, $context) && (!$session->allowoverbook)) {
        throw new moodle_exception('sessionisfull', 'facetoface', $returnurl);
    // MA-modification    
    // } else if (facetoface_get_user_submissions($facetoface->id, $USER->id,true)) {
    //     print_error('alreadysignedup', 'facetoface', $returnurl);
    } else if (!is_siteadmin() && facetoface_manager_needed($facetoface)) {  // MA-MODIFIED
        throw new moodle_exception('error:manageremailaddressmissing', 'facetoface', $returnurl);
    } else if ($submissionid = facetoface_user_signup($session, $facetoface, $course, $fromform->discountcode, $fromform->notificationtype, $statuscode)) {
        //MA==> send email to manager
        unset($session->diettext);
        unset($session->dietOptions);
        unset($session->accessOptions);
        unset($session->accesstext);
        
        if(isset($chosen_manageremail)) {
        
            $toEmail = $DB->get_record('user',array('username'=> $chosen_manageremail,'suspended'=> 0, 'deleted'=> 0 ) ) ;
            
            
            $support_user = core_user::get_support_user();
            $url = $CFG->wwwroot."/mod/facetoface/attendees.php?notify=1&s=".$session->id;
            $customfields = facetoface_get_session_customfields();
            $customdata = $DB->get_records('facetoface_session_data', array('sessionid' => $session->id), '', 'fieldid, data');
            $location="";
            $venue="";
            $room="";
            foreach ($customfields as $field) {
                $customdata[$field->id];
                switch (strtolower($field->shortname)) {
                    case 'location': 
                        $location = $customdata[$field->id]->data; break;
                    case 'venue': 
                        $venue = $customdata[$field->id]->data; break;
                    case 'room': 
                        $room = $customdata[$field->id]->data; break;
                }
            }
            $a= (object)[
                'managerfirstname'=>$toEmail->firstname,
                'stafffirstname'=>$USER->firstname,
                'stafflastname'=>$USER->lastname,
                'staffname'=>$USER->firstname . " " .$USER->lastname ,
                'supportcontactname'=>$support_user->firstname,
                'supportcontactemail'=>$support_user->email,
                'date'=>date('m/d/Y', intval(current($session->sessiondates)->timestart)),
                'url'=>$url,
                'duration'=>$session->duration,
                'venue'=>$venue,
                'location'=>$location,
                'room'=>$room,
                'activityname'=>$facetoface->name
            ];
           
            $notification_confirmmanager = nl2br(get_string('session:approvalmanagercontent','facetoface', $a));
            $notification_confirmmanager_text = html_to_text($notification_confirmmanager);
            $notification_confirmmanager_subject = get_string('session:approvalmanagersubject','facetoface',  $a);
            email_to_user($toEmail,$support_user,$notification_confirmmanager_subject,$notification_confirmmanager_text, $notification_confirmmanager);
        }
        //MA==>end
        // Logging and events trigger.
        $params = array(
            'context'  => $contextmodule,
            'objectid' => $session->id
        );
        $event = \mod_facetoface\event\signup_success::create($params);
        $event->add_record_snapshot('facetoface_sessions', $session);
        $event->add_record_snapshot('facetoface', $facetoface);
        $event->trigger();

        
        $message = ($facetoface->approvalreqd) ? get_string('bookingplaced_approvalrequired', 'facetoface') 
                                               : get_string('bookingcompleted_approvalnotreqd', 'facetoface');   // MA-MODIFIED
        if ($session->datetimeknown && $facetoface->confirmationinstrmngr) {
            $message .= html_writer::empty_tag('br') . html_writer::empty_tag('br') . get_string('confirmationsentmgr', 'facetoface');
        } else {
            // $message .= html_writer::empty_tag('br') . html_writer::empty_tag('br') . get_string('confirmationsent', 'facetoface'); // MA-MODIFIED
        }

        $timemessage = 4;
        redirect($returnurl, $message, $timemessage); // MA-MODIFIED
    } else {
        unset($session->diettext);
        unset($session->dietOptions);
        unset($session->accessOptions);
        unset($session->accesstext);
        // Logging and events trigger.
        $params = array(
            'context'  => $contextmodule,
            'objectid' => $session->id
        );
        $event = \mod_facetoface\event\signup_failed::create($params);
        $event->add_record_snapshot('facetoface_sessions', $session);
        $event->add_record_snapshot('facetoface', $facetoface);
        $event->trigger();

        throw new moodle_exception('error:problemsigningup', 'facetoface', $returnurl);
    }

    redirect($returnurl);
} else if ($manageremail !== false) {
    // Set values for the form.
    // MA-MODIFIED ===>
    // $toform = new stdClass();
    // $toform->manageremail = $manageremail;
    // $mform->set_data($toform);
    // <=== MA-MODIFIED 
}


echo $OUTPUT->header();

$heading = get_string('signupfor', 'facetoface', $facetoface->name);

$viewattendees = has_capability('mod/facetoface:viewattendees', $context);
$signedup = facetoface_check_signup($facetoface->id);

if ($signedup and $signedup != $session->id) {
    throw new moodle_exception('error:signedupinothersession', 'facetoface', $returnurl);
}

echo $OUTPUT->box_start();
echo $OUTPUT->heading($heading);

$timenow = time();

if ($session->datetimeknown && facetoface_has_session_started($session, $timenow)) {
    $inprogressstr = get_string('cannotsignupsessioninprogress', 'facetoface');
    $overstr = get_string('cannotsignupsessionover', 'facetoface');

    $errorstring = facetoface_is_session_in_progress($session, $timenow) ? $inprogressstr : $overstr;

    echo html_writer::empty_tag('br') . $errorstring;
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer($course);
    exit;
}

if (!$signedup && !facetoface_session_has_capacity($session, $context) && (!$session->allowoverbook)) {
    print_error('sessionisfull', 'facetoface', $returnurl);
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer($course);
    exit;
}

echo facetoface_print_session($session, $viewattendees);

if ($signedup) {
    if (!($session->datetimeknown && facetoface_has_session_started($session, $timenow)) && $session->allowcancellations) {

        // Cancellation link.
        $cancellationurl = new moodle_url('cancelsignup.php', array('s' => $session->id, 'backtoallsessions' => $backtoallsessions));
        echo html_writer::link($cancellationurl, get_string('cancelbooking', 'facetoface'), array('title' => get_string('cancelbooking', 'facetoface'),'class'=>'btn'));
        echo ' &ndash; ';
    }

    // See attendees link.
    if ($viewattendees) {
        $attendeesurl = new moodle_url('attendees.php', array('s' => $session->id, 'backtoallsessions' => $backtoallsessions));
        echo html_writer::link($attendeesurl, get_string('seeattendees', 'facetoface'), array('title' => get_string('seeattendees', 'facetoface')));
    }

    echo html_writer::empty_tag('br') . html_writer::link($returnurl, get_string('goback', 'facetoface'), array('title' => get_string('goback', 'facetoface')));
} else if (facetoface_manager_needed($facetoface)) {

    // Don't allow signup to proceed if a manager is required.
    // Check to see if the user has a managers email set.
    echo html_writer::tag('p', html_writer::tag('strong', get_string('error:manageremailaddressmissing', 'facetoface')));
    echo html_writer::empty_tag('br') . html_writer::link($returnurl, get_string('goback', 'facetoface'), array('title' => get_string('goback', 'facetoface')));

} else if (!has_capability('mod/facetoface:signup', $context)) {
    echo html_writer::tag('p', html_writer::tag('strong', get_string('error:nopermissiontosignup', 'facetoface')));
    echo html_writer::empty_tag('br') . html_writer::link($returnurl, get_string('goback', 'facetoface'), array('title' => get_string('goback', 'facetoface')));
} else {

    // Signup form.
    $mform->display();
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer($course);
