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
require_once('lib.php');
require_once('renderer.php');

global $DB, $OUTPUT;

$location = optional_param('location', '', PARAM_TEXT); // Location.

$is_admin = is_siteadmin($USER->id);

$sessions = ma_get_user_sessions($USER->id);

$context = context_system::instance();
$PAGE->set_url('/mod/facetoface/view_all.php');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');

require_login();

$title = get_string('view_all_sessions', 'mod_facetoface');

$PAGE->set_title($title);
$PAGE->set_heading($title);

$f2frenderer = $PAGE->get_renderer('mod_facetoface');

echo $OUTPUT->header();

echo $OUTPUT->box_start();
echo $OUTPUT->heading($PAGE->heading);

// if($is_admin){
//     if (count($locations) > 2) {
//         echo html_writer::start_tag('form', array('action' => 'view.php', 'method' => 'get'));
//         echo html_writer::start_tag('div');
//         echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'f', 'value' => $facetoface->id));
//         echo html_writer::select($locations, 'location', $location, '');
//         echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('showbylocation', 'facetoface')));
//         echo html_writer::end_tag('div'). html_writer::end_tag('form');
//     }
// }
print_session_list_bystate($sessions);

$btns = html_writer::link(new moodle_url('/calendar/view.php', array('view' => 'month')),
                'BACK TO CALENDAR',
                array('class' => 'btn'));


echo html_writer::tag('div', $btns, array('class'=>'btns'));

echo $OUTPUT->box_end();
echo $OUTPUT->footer();



function print_session_list_bystate($sessions) {
    global $CFG, $USER, $DB, $OUTPUT, $PAGE;

    $f2frenderer = $PAGE->get_renderer('mod_facetoface');

    $timenow = time();

    $context = context_system::instance();
    $viewattendees = has_capability('mod/facetoface:viewattendees', $context);
    $editsessions = has_capability('mod/facetoface:editsessions', $context);

    $customfields = facetoface_get_session_customfields();

    $upcomingarray = array();
    $previousarray = array();
    $upcomingtbdarray = array();

    if (!empty($sessions)) {
        foreach ($sessions as $session) {

            $sessionstarted = false;
            $sessionfull = false;
            $sessionwaitlisted = false;

            $session->bookedsession = (object) array(
                'sessionid' => $session->id,
                // 'statuscode' => $session->status
            );

            $sessiondata = $session;

            // Add custom fields to sessiondata.
            $customdata = $DB->get_records('facetoface_session_data', array('sessionid' => $session->id), '', 'fieldid, data');
            $sessiondata->customfielddata = $customdata;

            // Is session waitlisted.
            if (!$session->datetimeknown) {
                $sessionwaitlisted = true;
            }

            // Check if session is started.
            $sessionstarted = facetoface_has_session_started($session, $timenow);
            if ($session->datetimeknown && $sessionstarted && facetoface_is_session_in_progress($session, $timenow)) {
                $sessionstarted = true;
            } else if ($session->datetimeknown && $sessionstarted) {
                $sessionstarted = true;
            }

            // Put the row in the right table.
            if ($sessionstarted) {
                $previousarray[] = $sessiondata;
            } else if ($sessionwaitlisted) {
                $upcomingtbdarray[] = $sessiondata;
            } else { // Normal scheduled session.
                $upcomingarray[] = $sessiondata;
            }
        }
    }

    // Upcoming sessions.
    echo $OUTPUT->heading(get_string('upcomingsessions', 'facetoface'));
    if (empty($upcomingarray) && empty($upcomingtbdarray)) {
        print_string('noupcoming', 'facetoface');
    } else {
        $upcomingarray = array_merge($upcomingarray, $upcomingtbdarray);
        echo $f2frenderer->print_session_list_table($customfields, $upcomingarray, $viewattendees, $editsessions);
    }

    // Previous sessions.
    if (!empty($previousarray)) {
        echo $OUTPUT->heading(get_string('previoussessions', 'facetoface'));
        echo $f2frenderer->print_session_list_table($customfields, $previousarray, $viewattendees, $editsessions);
    }
}