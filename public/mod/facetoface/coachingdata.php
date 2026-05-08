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
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib_mindatlas.php');  // MA-MODIFIED

// Face-to-face session ID.
$s = required_param('s', PARAM_INT);

$takeattendance = optional_param('takeattendance', false, PARAM_BOOL); // Take attendance.
$cancelform = optional_param('cancelform', false, PARAM_BOOL); // Cancel request.
$backtoallsessions = optional_param('backtoallsessions', 0, PARAM_INT); // Face-to-face activity to return to.
$formbuilderuserid = optional_param('attendeeid', -1, PARAM_INT); // userid for taking the coaching data survey
$edit = optional_param('edit', 0, PARAM_INT); // userid for taking the coaching data survey
$formid = optional_param('formid', -1, PARAM_INT);
// Load data.
if (!$session = facetoface_get_session($s)) {
    print_error('error:incorrectcoursemodulesession', 'facetoface');
}
if (!$facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface))) {
    print_error('error:incorrectfacetofaceid', 'facetoface');
}
if (!$course = $DB->get_record('course', array('id' => $facetoface->course))) {
    print_error('error:coursemisconfigured', 'facetoface');
}
if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id)) {
    print_error('error:incorrectcoursemodule', 'facetoface');
}



// Load cancellations.
$cancellations = facetoface_get_cancellations($session->id);


/*
 * Capability checks to see if the current user can view this page
 *
 * This page is a bit of a special case in this respect as there are four uses for this page.
 *
 * 1) Viewing attendee list
 *   - Requires mod/facetoface:viewattendees capability in the course
 *
 * 2) Viewing cancellation list
 *   - Requires mod/facetoface:viewcancellations capability in the course
 *
 * 3) Taking attendance
 *   - Requires mod/facetoface:takeattendance capabilities in the course
 */
$context = context_course::instance($course->id);
$contextmodule = context_module::instance($cm->id);
// MA-MODIFIED ===>
// require_course_login($course); 
//Check if you are manager or not
require_login();
$is_hierarchy_installed = face_to_face_check_hierarchy();
$is_admin = is_siteadmin($USER->id);
$team_members = array();
if ($is_hierarchy_installed && !$is_admin) {
    //Use new hiearchy tool function to get all staffs
    // $team_members = get_hierarchy_teammembers($USER->id);
    $team_members = get_hierarchy_teammembers($USER->id);
    //add here if the attendees need to remove from the list.
}
// <=== MA-MODIFIED 

// Actions the user can perform.
$canviewattendees = has_capability('mod/facetoface:viewattendees', $context) || !empty($team_members);
$cantakeattendance = has_capability('mod/facetoface:takeattendance', $context) || !empty($team_members);
$canviewcancellations = has_capability('mod/facetoface:viewcancellations', $context) || !empty($team_members);
$canviewsession = $canviewattendees || $cantakeattendance || $canviewcancellations;
$canapproverequests = false;

$requests = array();
$declines = array();

$signup = $DB->get_record('facetoface_signups', array('sessionid' => $s,'userid' => $formbuilderuserid ));

// If a user can take attendance, they can approve staff's booking requests.
if ($cantakeattendance) {
    $requests = facetoface_get_requests($session->id);

    foreach ($requests as $key => $request) {
        if (!$is_admin) {
            if (!in_array($request->id, $team_members)) {
                unset($requests[$key]);
            }
        }
    }
}

// If requests found (but not in the middle of taking attendance), show requests table.
if ($requests && !$takeattendance) {

    $canapproverequests = true;
}

// Check the user is allowed to view this page.
if (!$canviewattendees && !$cantakeattendance && !$canapproverequests && !$canviewcancellations) {
    print_error('nopermissions', '', "{$CFG->wwwroot}/mod/facetoface/view.php?id={$cm->id}", get_string('view'));
}

// Check user has permissions to take attendance.
if ($takeattendance && !$cantakeattendance) {
    print_error('nopermissions', '', '', get_capability_string('mod/facetoface:takeattendance'));
}

if(!empty($_POST)){
    $data = $_POST;
   
    dffh_handle_formfield_data_submit($data);
}

/*
 * Handle submitted data
 */
// MA-MODIFIED ===>
$PAGE->set_url('/mod/facetoface/coachingdata.php', array('s' => $s));
$PAGE->set_context($context);
$PAGE->set_cm($cm);

$pagetitle = format_string($facetoface->name);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);
// <=== MA-MODIFIED 
$PAGE->requires->css('/mod/facetoface/css/table-styles.css');   // MA-MODIFIED
$pagetitle = format_string($facetoface->name);
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->js('/mod/facetoface/js/coachingdata.js');



echo $OUTPUT->header();

/*
 * Print page content
 */

// If taking attendance, make sure the session has already started.
if ($takeattendance && $session->datetimeknown && !facetoface_has_session_started($session, time())) {
    $link = "{$CFG->wwwroot}/mod/facetoface/coachingdata.php?s={$session->id}";
    print_error('error:canttakeattendanceforunstartedsession', 'facetoface', $link);
}
echo $OUTPUT->box_start();
echo $OUTPUT->heading(format_string($facetoface->name));
if ($canviewsession) {
    echo facetoface_print_session($session, true);
}
// MA-MODIFIED: Formbuilder ===>

// <=== MA-MODIFIED 
/*
 * Print attendees (if user able to view)
 */
$url = new moodle_url('/mod/facetoface/coachingdata.php', array('s' => $s, 'attendeeid' => $formbuilderuserid, 'takeattendance' => $takeattendance, 'backtoallsessions' => $backtoallsessions));
if ($formbuilderuserid > 0 && ($canviewattendees || $cantakeattendance)) {
    // get form id
    $formid =  $facetoface->formbuilder;
    // check current session with user_id has data
    $formfield_data = $DB->get_record('formbuilder_form_info_data', array('f2fsessionid' => $s, 'userid' => $formbuilderuserid));
    // if (!$formfield_data) {
    //     $formfield = $DB->get_record('formbuilder_form_info_field', array('formid' => $formid));

    //     if ($formfield != false)
    //         $formid = $formfield->formid;
    // }
    $formbuilderuser = $DB->get_record('user', array('id' => $formbuilderuserid));
    // $heading = "Coaching data - $formbuilderuser->username";
    $heading = "Dietary and accessibility details - $formbuilderuser->username";
    echo $OUTPUT->heading($heading);
    dffh_generate_formbuider_userdata($s, $formbuilderuserid);
    //Get current form data
    $formfielddata = dffh_view_formbuilder_user($s, $formbuilderuserid, $formid);
    //handle form submitted data if have any
   // $formfielddata = dffh_handle_formfield_data_submit($formfielddata);
    //reload form data
    $formfielddata = dffh_view_formbuilder_user($s, $formbuilderuserid, $formid);
    //render form 
    echo "<form action=\"$url\" method=\"POST\">";
    echo dffh_render_formfield_hidden('attendeeid', $formbuilderuserid);
    echo dffh_render_formfield_hidden('formid', $formid);
    echo dffh_render_formfield_hidden('s', $s);
        //var_dump($formfielddata);die();
    foreach ($formfielddata as $formfield) {
        echo dffh_render_formfield($formfield);
        echo "</br>";
    }
    //echo "<input class='btn btn-request-session' name='submit' type=\"submit\" value=\"Save\">";
    echo "</form>";

    if ($signup->dietary_details == '' || $signup->dietary_requirements == 0){
        echo "Dietary details: None";
    }else{
        echo "Dietary details: " . $signup->dietary_details;
    }
    
    echo "</br>";

    if ($signup->access_details == '' || $signup->access_requirements == 0){
        echo "Accessibility details: None";
    }else{
        echo "Accessibility details: " . $signup->access_details;
    }
    echo "</br>";
    echo "</br>";

    $backUrl = "{$CFG->wwwroot}/mod/facetoface/attendees.php?s=$s&backtoallsessions=1";
    echo "<button class='btn btn-request-session' onclick=\"window.location.href='$backUrl'\">Back to attendees list</button>";
}



echo $OUTPUT->box_end();
echo $OUTPUT->footer($course);
?>

<!-- MA-MODIFIED ===> -->
<script type="text/javascript">
    (function($) {

    })(jQuery)
</script>
<!-- <=== MA-MODIFIED  -->