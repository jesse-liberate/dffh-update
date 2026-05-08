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

// MA-MODIFIED 

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot.'/mod/facetoface/classes/signup_team_form.php');
require_once('lib.php');


$s = required_param('s', PARAM_INT); // Facetoface session ID.
$backtoallsessions = optional_param('backtoallsessions', 0, PARAM_INT);

if (!$session = facetoface_get_session($s)) {
    print_error('error:incorrectcoursemodulesession', 'facetoface');
}
if (!$facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface))) {
    print_error('error:incorrectfacetofaceid', 'facetoface');
}
if (!$course = $DB->get_record('course', array('id' => $facetoface->course))) {
    print_error('error:coursemisconfigured', 'facetoface');
}
if (!$cm = get_coursemodule_from_instance("facetoface", $facetoface->id, $course->id)) {
    print_error('error:incorrectcoursemoduleid', 'facetoface');
}

require_course_login($course, true, $cm);
$context = context_course::instance($course->id);
$contextmodule = context_module::instance($cm->id);
require_capability('mod/facetoface:view', $context);

$returnurl = "$CFG->wwwroot/course/view.php?id=$course->id";
$currenturl = "$CFG->wwwroot/mod/facetoface/signup_team.php?s=$s";

if ($backtoallsessions) {
    $returnurl = "$CFG->wwwroot/mod/facetoface/view.php?f=$backtoallsessions";
}

$pagetitle = format_string($facetoface->name);

$PAGE->set_cm($cm);
$PAGE->set_url('/mod/facetoface/signup_team.php', array('s' => $s, 'backtoallsessions' => $backtoallsessions));

$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

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
    echo $OUTPUT->confirm($out, $loginurl, get_referer(false));
    echo $OUTPUT->footer();
    exit();
}

$showdiscountcode = ($session->discountcost > 0);

$mform = new mod_facetoface_signup_team_form(
    null,
    compact('s', 'backtoallsessions', 'showdiscountcode')
);

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($fromform = $mform->get_data()) { // Form submitted.
    // Disable to select notification type, no iCal included
    $fromform->notificationtype = MDL_F2F_TEXT;

    if (isset($fromform->teammembers)) $teammembers = $fromform->teammembers;

    // echo '<pre>'.print_r($fromform, true).'</pre>'; die();

    if (empty($fromform->submitbutton)) {
        print_error('error:unknownbuttonclicked', 'facetoface', $returnurl);
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

    if (!facetoface_session_has_capacity($session, $context) && (!$session->allowoverbook)) {
        // print_error('sessionisfull', 'facetoface', $returnurl);
        redirect($currenturl,get_string('sessionisfull','mod_facetoface'));
    } else {
        if (isset($teammembers)) {
            foreach ($teammembers as $userid => $value) {    
                if ($value) {
                    if ($submissionid = facetoface_user_team_signup($session, $facetoface, $course, $fromform->discountcode, $fromform->notificationtype, $statuscode, $userid, true)) {
                        // Logging and events trigger.
                        $params = array(
                            'context'  => $contextmodule,
                            'objectid' => $session->id
                        );
                        $event = \mod_facetoface\event\signup_success::create($params);
                        $event->add_record_snapshot('facetoface_sessions', $session);
                        $event->add_record_snapshot('facetoface', $facetoface);
                        $event->trigger();

                    } else {
                        // Logging and events trigger.
                        $params = array(
                            'context'  => $contextmodule,
                            'objectid' => $session->id
                        );
                        $event = \mod_facetoface\event\signup_failed::create($params);
                        $event->add_record_snapshot('facetoface_sessions', $session);
                        $event->add_record_snapshot('facetoface', $facetoface);
                        $event->trigger();

                        print_error('error:problemsigningup', 'facetoface', $returnurl);
                    }
                }
            }
        }
    }
    redirect($currenturl);
} 

echo $OUTPUT->header();

$heading = get_string('signupfor', 'facetoface', $facetoface->name);

$viewattendees = has_capability('mod/facetoface:viewattendees', $context);

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

// Commented to allow Manager sees the team members enrolment status
// if (!facetoface_session_has_capacity($session, $context) && (!$session->allowoverbook)) {
//     print_error('sessionisfull', 'facetoface', $returnurl);
//     echo $OUTPUT->box_end();
//     echo $OUTPUT->footer($course);
//     exit;
// }

echo facetoface_print_session($session, $viewattendees);

// Signup form.
$mform->display();

echo $OUTPUT->box_end();
echo $OUTPUT->footer($course);
