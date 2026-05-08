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

use core\analytics\indicator\read_actions;

require_once(__DIR__ . '../../../../config.php');
require_once(__DIR__ . '../../lib.php');
require_once($CFG->dirroot . '/blocks/theme_support/classes/mindatlas_theme_library.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib_mindatlas.php');
global $THEME, $USER;
use core_course\external\course_summary_exporter;

$THEME->requires('training_session.js');
$sessionid = optional_param('id', 0, PARAM_INT);

if ($sessionid) {
    if (!$session = facetoface_get_session($sessionid)) {
        print_error('error:incorrectcoursemodulesession', 'facetoface');
    }
    if (!$facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface))) {
        print_error('error:incorrectfacetofaceid', 'facetoface');
    }
    if (!$course = $DB->get_record('course', array('id' => $facetoface->course))) {
        print_error('error:coursemisconfigured', 'facetoface');
    }
    if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id)) {
        print_error('error:incorrectcoursemoduleid', 'facetoface');
    }
    if (!$signup = $DB->get_record('facetoface_signups', array('sessionid' => $sessionid,'userid' => $USER->id))) {
    }
    if (!$locationfield = $DB->get_record('facetoface_session_field', array('shortname' => 'location'))) {
    }
    if (!$location = $DB->get_record('facetoface_session_data', array('sessionid' => $sessionid , 'fieldid' => $locationfield->id))) {
    }
    
    $nbdays = count($session->sessiondates);
}

require_login();

$theme_lib = new mindatlas_theme_library();

$url = new moodle_url('/theme/mindatlas/pages/sessions_details.php');
$PAGE->set_url($url);

$context = context_course::instance($course->id);

$isbookedsession = false;
$bookedsession = $session->bookedsession;
$sessionstarted = false;
$sessionfull = false;
 // Capacity.
 $signupcount = facetoface_get_num_attendees($session->id, MDL_F2F_STATUS_APPROVED);
 $stats = $session->capacity - $signupcount;
 if ($viewattendees) {
     $stats = $signupcount . ' / ' . $session->capacity;
 } else {
     $stats = max(0, $stats);
 }
 $status  = get_string('bookingopen', 'facetoface');
 $bookedsession = null;
    if ($submissions = facetoface_get_user_submissions($facetoface->id, $USER->id)) {
        $submission = array_shift($submissions);
        $bookedsession = $submission;
    }

    if(!$bookedsession){
    unset($signup);
    }
 if ($session->datetimeknown && facetoface_has_session_started($session, $timenow) && facetoface_is_session_in_progress($session, $timenow)) {
     $status = get_string('sessioninprogress', 'facetoface');
     $sessionstarted = true;
 } else if ($session->datetimeknown && facetoface_has_session_started($session, $timenow)) {
     $status = get_string('sessionover', 'facetoface');
     $sessionstarted = true;
 } else if ($signup && $bookedsession) {    // MA-MODIFIED
     $signupstatus = facetoface_get_status($bookedsession->statuscode);
     $status = get_string('status_' . $signupstatus, 'facetoface');
     $isbookedsession = true;
 } else if ($signupcount >= $session->capacity) {
     $status = get_string('bookingfull', 'facetoface');
     $sessionfull = true;
 }
 $button = '';
 $link = '';

 if ($isbookedsession) {
    // Hide More Info link as requested by client
    //$options .= html_writer::link('signup.php?s='.$session->id.'&backtoallsessions='.$session->facetoface, get_string('moreinfo', 'facetoface'), array('title' => get_string('moreinfo', 'facetoface'))) . html_writer::empty_tag('br');
if ($session->allowcancellations) {
    if($signupcount >= $session->capacity){
        $button = 'Cancel Waitlist';
    }else{
        $button = get_string('cancelbooking', 'facetoface');
    }

$link = 'cancelsignup.php?s=' . $session->id . '&backtoallsessions=' . $session->facetoface ;
}
} else if (!$sessionstarted and !$bookedsession) {  // MA-MODIFIED
if ($signupcount >= $session->capacity) { // fully booked
$button = get_string('joinwaitlist', 'facetoface');
$link = 'signup.php?s='.$session->id.'&backtoallsessions='.$session->facetoface;
}else{
$button = 'Register';
$link = 'signup.php?s='.$session->id.'&backtoallsessions='.$session->facetoface;
}

}
$PAGE->set_context($context);

$title = 'Session details';
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('standard');
// $PAGE->add_body_classes(['full-width']);
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Session details', $url);

$output = $PAGE->get_renderer('core', 'course');
$imageurl= $THEME->brand["mycourses_banner"];
$content = '
    <section id="section-banner" class=" page-banner" style="background-image: url('.$imageurl.')">
        <div class="banner-box">
            <h1 class="page-title">FP&R Response Facilitated Training Booking</h1>
        </div>
    </section>';
$text1 = $THEME->brand["training_text"];
$text2 = $THEME->brand["training_textt"];

echo $OUTPUT->header();
echo $content;
$fs = get_file_storage();
// $files = $fs->get_area_files($context->id, 'mod_facetoface', 'sessionimage',$session->id);
$timestart  = ma_facetoface_get_date_format(null, $session->sessiondates[0]->timestart , 'd-m-Y');
$timefinish = ma_facetoface_get_date_format(null, $session->sessiondates[0]->timefinish, 'd-m-Y');
$timestartt  = ma_facetoface_get_date_format(null, $session->sessiondates[0]->timestart , 'g:i a ');
$timefinishh = ma_facetoface_get_date_format(null, $session->sessiondates[0]->timefinish, 'g:i a ');
if($session->sessiondates[1]){
    $timestart2  = ma_facetoface_get_date_format(null, $session->sessiondates[1]->timestart , 'd-m-Y');
    $timefinish2 = ma_facetoface_get_date_format(null, $session->sessiondates[1]->timefinish, 'd-m-Y');
    $timestartt2  = ma_facetoface_get_date_format(null, $session->sessiondates[1]->timestart , 'g:i a ');
    $timefinishh2 = ma_facetoface_get_date_format(null, $session->sessiondates[1]->timefinish, 'g:i a ');
}

$course_image = course_summary_exporter::get_course_image($course);
// foreach ($files as $file) {
//     $filename = $file->get_filename();            
//     if ($filename <> '.') {
//         $fileuserid = $file->get_userid();          
//         $fileurl['file'] = $file;
//         $fileurl['url'] = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(),$file->get_itemid(), $file->get_filepath(), $filename);
//             }
//         }
        $target_user = $THEME->get_user($USER->id);
    $session->details = strip_tags( $session->details, '<p><a><ul><li><div><h1><h2><h3><h4><h5><span>');
$content = '<div class="training-wrapper">';
    $content .= '<div class="row mb-5">';
        $content .= '<div class="col-lg-7 p-70"><h1 class="session-title bold mb-2">'.$facetoface->name.'</h1>
        <p class="session-details">'.$session->additonal_details.'</p></div>';
        $content .= '<div class="col-lg-5 image-container"><img class="session-image" src="'. $course_image.'"></img></div>';
    $content .= '</div>';
    $content .= '<div class="time-details-container p-5">';
        $content .= '<div class="row">';
            $content .= '<div class="col-lg-4"><h3 class="time-details">Start date</h3>
            <p class="session-details">'.$timestart.'</p>  <p class="session-details"> '.$timestart2.'</p></div>';
            $content .= '<div class="col-lg-4"><h3 class="time-details">End date</h3>
            <p class="session-details">'.$timefinish.'</p>  <p class="session-details">'.$timefinish2.'</p></div>';
            $content .= '<div class="col-lg-4"><h3 class="time-details">Time</h3>
            <p class="session-details">'.$timestartt.' - '.$timefinishh.'</p>  <p class="session-details">'.$timestartt2.' - '.$timefinishh2.' </p></div>';
        $content .= '</div>';
    $content .= '</div>';
    $content .= '<div class="time-details-container mt-3 p-5">';
        $content .= '<div class="row">';
            $content .= '<div class="col-lg-12"><h3 class="time-details">Location</h3>
            <p class="session-details">'.$location->data.'</p></div>';
        $content .= '</div>';
    $content .= '</div>';
    $content .= '<div class="time-details-container mt-3 p-5">';
    $content .= '<div class="row">';
        $content .= '<div class="col-lg-12"><h3 class="time-details">About this event</h3>
        <p class="session-details">'.$session->details.'</p></div>';
    $content .= '</div>';
$content .= '</div>';
$content .= '<div class="user-details-container mt-3 p-5">';
    $content .= '<div class="row">';
        $content .= '<div class="col-lg-6"><h3 class="time-details">Personal details</h3>';
        $content .= '<div class="row">';
        $content .= '<div class="col-lg-4"><p class="user-details">Full name</p>
        <p class="session-details">'.$target_user->firstname.' '.$target_user->lastname.'</p></div>';
        $content .= '<div class="col-lg-4"><p class="user-details">Profession</p>
        <p class="session-details">'.$target_user->profile['RoleOrPosition'].'</p></div>';
        $content .= '<div class="col-lg-4"><p class="user-details">Company</p>
        <p class="session-details">'.$target_user->profile['OrganisationOrAgency'].'</p></div>';
    $content .= '</div>';
        $content .= '</div>';
    $content .= '</div>';
$content .= '</div>';

if($signup->dietary_requirements == '1'){
    $diet_requirements = '  <label class="form-check-label session-details mr-2" for="diet1">Yes</label>
    <input onclick="javascript:yesnoCheck1();" class="form-check-input" type="radio" checked name="dietOptions" id="diet1" value="yes">
  </div>
  <div class="form-check form-check-inline">
    <label class="form-check-label session-details mr-2" for="diet2">No</label>
    <input onclick="javascript:yesnoCheck1();" class="form-check-input" type="radio" name="dietOptions" id="diet2" value="no">
  </div>';
}else{
    $diet_requirements = ' <label class="form-check-label session-details mr-2" for="diet1">Yes</label>
    <input onclick="javascript:yesnoCheck1();" class="form-check-input" type="radio" name="dietOptions" id="diet1" value="yes">
  </div>
  <div class="form-check form-check-inline">
    <label class="form-check-label session-details mr-2" for="diet2">No</label>
    <input onclick="javascript:yesnoCheck1();" class="form-check-input" checked type="radio" name="dietOptions" id="diet2" value="no">
  </div>';
}
if($signup->dietary_details){
    $diet_details = '<textarea class="form-control" name="diettext" form="confirmationForm" id="dietarea" rows="6">'.$signup->dietary_details.'</textarea> ';
}else{
    $diet_details = '<textarea style="display:none" class="form-control" name="diettext" form="confirmationForm" id="dietarea" rows="6"></textarea> ';
}

if($signup->access_details){
    $access_details = '   <textarea class="form-control" name="accesstext" form="confirmationForm" id="accessarea" rows="6">'.$signup->access_details.'</textarea>';
}else{
    $access_details = '<textarea style="display:none" class="form-control" name="accesstext" form="confirmationForm"  id="accessarea" rows="6"></textarea>';
}

if($signup->access_requirements == '1'){
    
    $access_requirements = '  <div class="form-check form-check-inline">
    <label class="form-check-label session-details mr-2" for="access1">Yes</label>
    <input onclick="javascript:yesnoCheck2();" class="form-check-input" checked type="radio" name="accessOptions" id="access1" value="yes">
  </div>
  <div class="form-check form-check-inline">
    <label class="form-check-label session-details  mr-2" for="access2">No</label>
    <input onclick="javascript:yesnoCheck2();" class="form-check-input" type="radio" name="accessOptions" id="access2" value="no">
  </div>';
}else {
    $access_requirements = ' <div class="form-check form-check-inline">
    <label class="form-check-label session-details mr-2" for="access1">Yes</label>
    <input onclick="javascript:yesnoCheck2();" class="form-check-input" type="radio" name="accessOptions" id="access1" value="yes">
  </div>
  <div class="form-check form-check-inline">
    <label class="form-check-label session-details  mr-2" for="access2">No</label>
    <input onclick="javascript:yesnoCheck2();" class="form-check-input" checked type="radio" name="accessOptions" id="access2" value="no">
  </div>';
}


//  ["dietary_details"]=> string(3) "ddd" ["access_requirements"]=> string(1) "1" ["access_details"]=> string(3) "ddd" }
$content .= '<form id="confirmationForm" name="confirmationForm" action="'.$CFG->wwwroot.'/mod/facetoface/'.$link.'" method="get">';
$content .= '<div class="time-details-container p-5">';
        $content .= '<div class="row">';
            $content .= '<div class="col-lg-6"><h3 class="time-details">Dietary requirements</h3>
            <p class="session-details diet">Do you have dietary requirements?</p>
            <div class="form-check form-check-inline">
            <input type="hidden" id="sessionid" name="s" value="'.$sessionid.'" />
            '.$diet_requirements.'
            <p style="display:none" id="diet_question"class="session-details margin-top-diet">What are your dietary requirements?</p>
            <div class="form-group">
                '.$diet_details.'
            </div></div>';
            $content .= '<div class="col-lg-6"><h3 class="time-details">Accessibility requirements</h3>
            <p class="session-details access">Do you have accessibility needs that we can support to participate in the facilitated training?</p>
            '.$access_requirements.'
            <p style="display:none" id="access_question" class="session-details">How can we support you to access the training?</p>
            <div class="form-group">
           '.$access_details.'
        </div></div>';
        $content .= '</div>';
    $content .= '</div>';

$content .= '</div>';
$content .= '<div class="button-container text-center w-100 mt-5">';
if($button !== ''){
$content .='<button type="submit" name="register" value="register" class="btn-custom bg-color-brand-2 mr-2 hover-bg-color-brand-2 text-white font-weight-bold">'.$button.'</button>';
}
if($bookedsession){
    $content .='<button type="submit" formaction="'.$CFG->wwwroot.'/mod/facetoface/signup.php?s='.$session->id.'&backtoallsessions='.$session->facetoface.'" name="update" value="update" class="btn-custom bg-color-brand-2 hover-bg-color-brand-2 text-white font-weight-bold">Update booking</button>';
}

$content .= '</div>';
$content .= '</form>';

echo $content;
echo $OUTPUT->footer();
