<?php

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/ajax_controller.php');
require_once('../lib.php');
require_once($CFG->dirroot.'/blocks/theme_support/classes/mindatlas_theme_library.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->dirroot.'/mod/facetoface/lib_mindatlas.php');
// use sesskey() to create sesskey, it will be saved in $_SESSION['USER'], also can be get from $USER->sesskey
// require_sesskey(); // Gotta have the sesskey.
require_login(); 
$PAGE->set_context(context_system::instance());

new class() extends ajax_controller{
    function __construct()
    {
        parent::__construct();
        echo json_encode($this->process($this->action, $this->payload));
    }


    // add your extra action process functions, add your return to $this->result.

    //get user profile
    public function  get_user_profile($payload){
        $get_user_profile = new mindatlas_theme_library();
        $data = $get_user_profile->get_user_profile($payload);

        $this->result = $data;
    }

    public function get_user_badges($payload){
        // $userid = $payload['userid'];
        // $sesskey = $payload['sesskey'];
        //please use userid to get current user bagdes, if no data, then return total = 0, badges = []

        $get_user_badge = new mindatlas_theme_library();
        $data = $get_user_badge->get_user_badges($payload);

        $this->result = $data;
    }

    public function get_user_course_summary($payload){
        global $PAGE, $DB;
        $get_user_course_summary = new mindatlas_theme_library();
        $data = $get_user_course_summary->get_user_course_summary($payload);
        // $data =[ 
        //     "overall_progress" => 80,
        //     "enrolled_course_num" => 12,
        //     "enrolled_percent" => 50,
        //     "inprogress_course_num" => 5,
        //     "inprogress_percent" => 80,
        //     "overdue_course_num" => 6,
        //     "overdue_percent" => 40,
        //     "completed_course_num" => 2,
        //     "completed_percent" => 30
        // ];
        $this->result = $data;
    }

    function dffh_get_user_sessions($payload) {
        global $DB, $USER;
        
        $userid = $payload['userid'];
        $limit = $payload['limit'];
        $extrafields = $payload['extrafields']; 
        $include_past = false;
        $fields = array(
            'fsess.id', 'f.course','fsess.additonal_details','f.name','fsess_dates.timestart', 'fsess_dates.timefinish');
       
        foreach ($extrafields as $field) {
            if (!in_array($field, $fields)) {
                $fields[] = $field;
            }
        }
        $condition1="";
        $condition2="";
         $params = array();
        if(!is_siteadmin($userid)){
            $condition1="INNER JOIN (SELECT e.courseid from mdl_enrol e INNER JOIN mdl_user_enrolments ue on e.id=ue.enrolid group by e.courseid) u_enrolled on u_enrolled.courseid=f.course";
            $condition2="and fsign.userid = ?";
           
            // We need two userid variables as the parameters for condititions in SQL query
        }
        $fields = implode(', ', $fields);
    
        $query = "SELECT $fields
            FROM mdl_facetoface f
             ".$condition1."
            JOIN mdl_facetoface_sessions fsess
            ON fsess.facetoface = f.id
             JOIN mdl_facetoface_sessions_dates fsess_dates
            ON fsess_dates.sessionid = fsess.id
            WHERE fsess.id IS NOT NULL ";
        if (!$include_past) {
            $query .= 'AND fsess_dates.timestart > ?';
            $params[] = time();
        }
        $query .= ' ORDER BY fsess_dates.timestart ASC ';
    
        
        // print_object($params);
        // print_object($query);die();
      
        $session = $DB->get_records_sql($query, $params);
       
        foreach($session as $s){
            $paramss [] = $userid;
            $paramss [] = $s->id;
            $sessionstatus = $DB->get_record_sql('SELECT sign_status.statuscode as status, sign_status.id as id FROM  mdl_facetoface_signups fsign
            LEFT JOIN mdl_facetoface_signups_status sign_status
            ON sign_status.signupid = fsign.id WHERE fsign.userid = ? AND fsign.sessionid = ? ORDER BY sign_status.timecreated DESC LIMIT 1   ', $paramss);
            if($sessionstatus){
            $s->status = $sessionstatus->status;
            }
        }
      
        $locationfieldid = $DB->get_field_sql('SELECT id from {facetoface_session_field} where shortname=?',array('location'));
      
        $venuefieldid = $DB->get_field_sql('SELECT id from {facetoface_session_field} where shortname=?',array('venue'));
      $varsessions = [];
      foreach($session as $s){
        if ($s) {
            if (!$session = facetoface_get_session($s->id)) {
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
            if (!$signup = $DB->get_record('facetoface_signups', array('sessionid' => $s->id,'userid' => $USER->id))) {
            }
            if (!$locationfield = $DB->get_record('facetoface_session_field', array('shortname' => 'location'))) {
            }
            if (!$location = $DB->get_record('facetoface_session_data', array('sessionid' => $s->id , 'fieldid' => $locationfield->id))) {
            }
            
            $nbdays = count($session->sessiondates);
        }
        $timestart = null;
        $timefinish = null;
        $timestartt = null;
        $timefinishh = null;
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
        $statuscancel = null;
        $bookedsession = null;
            if ($submissions = facetoface_get_user_submissions($facetoface->id, $USER->id)) {
                $submissions = array_filter($submissions, function($submission) use ($s) {
                    return $submission->sessionid == $s->id;
                });
                $submission = array_shift($submissions);
                $bookedsession = $submission;
            }
        if ($session->datetimeknown && facetoface_has_session_started($session, $timenow) && facetoface_is_session_in_progress($session, $timenow)) {
            $status = get_string('sessioninprogress', 'facetoface');
            $sessionstarted = true;
        } else if ($session->datetimeknown && facetoface_has_session_started($session, $timenow)) {
            $status = get_string('sessionover', 'facetoface');
            $sessionstarted = true;
        } else if ($signup && $bookedsession) {    // MA-MODIFIED
            $signupstatus = facetoface_get_status($bookedsession->statuscode);
            if($signupstatus == 'waitlisted' || $signupstatus == 'booked' ){
            $statuscancel = get_string('status_' . $signupstatus .'_cancel', 'facetoface');
            }
            $status = get_string('status_' . $signupstatus, 'facetoface');
            $isbookedsession = true;
        } else if ($signupcount >= $session->capacity) {
            $status = 'Waitlist';
            $sessionfull = true;
        }
        $button = '';
        $link = '';

        if ($isbookedsession) {
            // Hide More Info link as requested by client
            //$options .= html_writer::link('signup.php?s='.$session->id.'&backtoallsessions='.$session->facetoface, get_string('moreinfo', 'facetoface'), array('title' => get_string('moreinfo', 'facetoface'))) . html_writer::empty_tag('br');
        if ($session->allowcancellations) {
        $button = get_string('cancelbooking', 'facetoface');
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
        $venue = $DB->get_record_sql('SELECT * FROM {facetoface_session_data} WHERE sessionid = ? AND fieldid = ?',array($s->id,$venuefieldid));
        $location = $DB->get_record_sql('SELECT * FROM {facetoface_session_data} WHERE sessionid = ? AND fieldid = ?',array($s->id,$locationfieldid));
        $s->timestart = $timestart;
        $s->timefinish = $timefinish;
        $s->venue = $venue->data;
        $s->location = $location->data;
        $course = get_course($s->course);
        $multipledates = $DB->get_records_sql('SELECT * FROM  {facetoface_sessions_dates} WHERE sessionid = ? ',array($s->id));
        $timestart2 = null;
        $timefinish2 = null;
        $timestartt2 = null;
        $timefinishh2 = null;
        $timestart3 = null;
        $timefinish3 = null;
        $timestartt3 = null;
        $timefinishh3 = null;
        
    
            $counter = 1;
            foreach ($multipledates as $index => $date) {
                if ($counter == 1) {
                  
                    $timestart  = ma_facetoface_get_date_format(null, $date->timestart , 'd-m-Y');
                    $timefinish = ma_facetoface_get_date_format(null, $date->timefinish, 'd-m-Y');
                    $timestartt  = ma_facetoface_get_date_format(null, $date->timestart , 'g:i a ');
                    $timefinishh = ma_facetoface_get_date_format(null, $date->timefinish, 'g:i a ');
                }
                if ($counter == 2) {
                  
                    $timestart2 = ma_facetoface_get_date_format(null, $date->timestart , 'd-m-Y');
                    $timefinish2 = ma_facetoface_get_date_format(null, $date->timefinish, 'd-m-Y');
                    $timestartt2 = ma_facetoface_get_date_format(null, $date->timestart , 'g:i a ');
                    $timefinishh2 = ma_facetoface_get_date_format(null, $date->timefinish, 'g:i a ');
                }
                if ($counter == 3) {
                    $timestart3 = ma_facetoface_get_date_format(null, $date->timestart , 'd-m-Y');
                    $timefinish3 = ma_facetoface_get_date_format(null, $date->timefinish, 'd-m-Y');
                    $timestartt3 = ma_facetoface_get_date_format(null, $date->timestart , 'g:i a ');
                    $timefinishh3 = ma_facetoface_get_date_format(null, $date->timefinish, 'g:i a ');
                }
                $counter++;
            }
        
       
        $category = $DB->get_record_sql('SELECT * FROM  {course_categories} WHERE id = ?   ',array($course->category));
        if($category->idnumber != 'coaching'){
            if($course->visible == 1){
                $object = (object)[
                    'id'=> $s->id,
                    'name'=>$s->name,
                    'details'=>strip_tags($s->additonal_details),
                    'timestart'=> $timestart,
                    'timefinish'=> $timefinish,
                    'time'=>$timestartt.''.$timefinishh,
                    'timestart2'=> $timestart2,
                    'timefinish2'=> $timefinish2,
                    'time2'=>$timestartt2.''.$timefinishh2,
                    'timestart3'=> $timestart3,
                    'timefinish3'=> $timefinish3,
                    'time3'=>$timestartt3.''.$timefinishh3,
                    'status'=>$status,
                    'statuscancel' => $statuscancel,
                    'venue'=>$s->venue,
                    'location'=> $s->location];
                $varsessions[] = $object;
            }
        }
       
      }
         
        $data[0] = $varsessions;
        $this->result = $data;
    }
    function dffh_get_user_sessions_coaching($payload) {
        global $DB, $USER, $CFG;
        $userid = $payload['userid'];
        $limit = $payload['limit'];
        $extrafields = $payload['extrafields']; 
        $include_past = false;
        $coaching_category = $DB->get_record_sql('SELECT * FROM {course_categories} WHERE idnumber = "coaching"');
        $sql_str = "SELECT id, fullname 
        FROM mdl_course WHERE category = ?";
        $courserecord = $DB->get_record_sql($sql_str,array($coaching_category->id));

        $fields = array(
            'fsess.id', 'fsess.duration','fsess.additonal_details','f.name','fsess_dates.timestart', 'fsess_dates.timefinish');
       
        foreach ($extrafields as $field) {
            if (!in_array($field, $fields)) {
                $fields[] = $field;
            }
        }
        $condition1="";
        $condition2="";
         $params = array();
        if(!is_siteadmin($userid)){
            $condition1="INNER JOIN (SELECT e.courseid from mdl_enrol e INNER JOIN mdl_user_enrolments ue on e.id=ue.enrolid group by e.courseid) u_enrolled on u_enrolled.courseid=f.course";
            $condition2="and fsign.userid = ?";
           
            // We need two userid variables as the parameters for condititions in SQL query
        }
        $fields = implode(', ', $fields);
    
        $query = "SELECT $fields
            FROM mdl_facetoface f
             ".$condition1."
            JOIN mdl_facetoface_sessions fsess
            ON fsess.facetoface = f.id
            LEFT JOIN mdl_facetoface_sessions_dates fsess_dates
            ON fsess_dates.sessionid = fsess.id
            WHERE fsess.id IS NOT NULL ";
        if (!$include_past) {
            $query .= 'AND fsess_dates.timestart > ?';
            $params[] = time();
        }
         $query .= ' AND f.course = ?';
        $params[] = $courserecord->id;
        $query .= ' ORDER BY fsess_dates.timestart ASC';
    
        if (!empty($limit)) {
            $query .= ' LIMIT 0, ' . $limit;
        }
       
        // print_object($params);
        // print_object($query);die();
      
        $session = $DB->get_records_sql($query, $params);
        // var_dump($query);
        // var_dump($params);
        // var_dump($session);
        // die();
        foreach($session as $s){
            $paramss [] = $userid;
            $paramss [] = $s->id;
            $sessionstatus = $DB->get_record_sql('SELECT sign_status.statuscode as status, sign_status.id as id FROM  mdl_facetoface_signups fsign
            LEFT JOIN mdl_facetoface_signups_status sign_status
            ON sign_status.signupid = fsign.id WHERE fsign.userid = ? AND fsign.sessionid = ? ORDER BY sign_status.timecreated DESC LIMIT 1   ', $paramss);
            if($sessionstatus){
            $s->status = $sessionstatus->status;
            }
        }
      
        $locationfieldid = $DB->get_field_sql('SELECT id from {facetoface_session_field} where shortname=?',array('location'));
      
        $venuefieldid = $DB->get_field_sql('SELECT id from {facetoface_session_field} where shortname=?',array('venue'));
      $varsessions = [];
      foreach($session as $s){
        if ($s) {
            if (!$session = facetoface_get_session($s->id)) {
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
            if (!$signup = $DB->get_record('facetoface_signups', array('sessionid' => $s->id,'userid' => $USER->id))) {
            }
            if (!$locationfield = $DB->get_record('facetoface_session_field', array('shortname' => 'location'))) {
            }
            if (!$location = $DB->get_record('facetoface_session_data', array('sessionid' => $s->id , 'fieldid' => $locationfield->id))) {
            }
            
            $nbdays = count($session->sessiondates);
        }
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
        $statuscancel = null;
        $bookedsession = null;
            if ($submissions = facetoface_get_user_submissions($facetoface->id, $USER->id)) {
                $submission = array_shift($submissions);
                $bookedsession = $submission;
            }
        if ($session->datetimeknown && facetoface_has_session_started($session, $timenow) && facetoface_is_session_in_progress($session, $timenow)) {
            $status = get_string('sessioninprogress', 'facetoface');
            $sessionstarted = true;
        } else if ($session->datetimeknown && facetoface_has_session_started($session, $timenow)) {
            $status = get_string('sessionover', 'facetoface');
            $sessionstarted = true;
        } else if ($signup && $bookedsession) {    // MA-MODIFIED
            $signupstatus = facetoface_get_status($bookedsession->statuscode);
            if($signupstatus == 'waitlisted' || $signupstatus == 'booked' ){
            $statuscancel = get_string('status_' . $signupstatus .'_cancel', 'facetoface');
            }
            $status = get_string('status_' . $signupstatus, 'facetoface');
            $isbookedsession = true;
        } else if ($signupcount >= $session->capacity) {
            $status = 'Waitlist';
            $sessionfull = true;
        }
        $button = '';
        $link = '';

        if ($isbookedsession) {
            // Hide More Info link as requested by client
            //$options .= html_writer::link('signup.php?s='.$session->id.'&backtoallsessions='.$session->facetoface, get_string('moreinfo', 'facetoface'), array('title' => get_string('moreinfo', 'facetoface'))) . html_writer::empty_tag('br');
        if ($session->allowcancellations) {
        $button = get_string('cancelbooking', 'facetoface');
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
        $venue = $DB->get_record_sql('SELECT * FROM {facetoface_session_data} WHERE sessionid = ? AND fieldid = ?',array($s->id,$venuefieldid));
        $location = $DB->get_record_sql('SELECT * FROM {facetoface_session_data} WHERE sessionid = ? AND fieldid = ?',array($s->id,$locationfieldid));
        $timestart  = ma_facetoface_get_date_format(null, $s->timestart , 'd-m-Y');
        $timefinish = ma_facetoface_get_date_format(null, $s->timefinish, 'd-m-Y');
        $timestartt  = ma_facetoface_get_date_format(null, $s->timestart , 'g:i a ');
        $timefinishh = ma_facetoface_get_date_format(null, $s->timefinish, 'g:i a ');
        $s->timestart = $timestart;
        $s->timefinish = $timefinish;
        $s->venue = $venue->data;
        $s->location = $location->data;
        $coachid = $DB->get_record_sql('SELECT * FROM {facetoface_session_roles} WHERE sessionid = ?',array($s->id));
        $coach = $DB->get_record_sql('SELECT * FROM {user} WHERE id = ?',array($coachid->userid));
        if($coachid->userid == $USER->id){
            $sessionlink = $CFG->wwwroot.'/mod/facetoface/attendees.php?s='.$s->id;
        }else{
            $sessionlink = false;
        }
        $usersession = $DB->get_record_sql('SELECT * FROM {facetoface_signups} WHERE sessionid = ? AND userid = ? ',array($s->id,$userid));
        if($coachid->userid == $USER->id){
            $object = (object)[
                'id'=> $s->id,
                'coach'=> fullname($coach,true),
                'name'=>$s->name,
                'duration'=>facetoface_minutes_to_hours($s->duration).' hours',
                'details'=>strip_tags($s->additonal_details),
                'timestart'=> $timestart,
                'timefinish'=> $timefinish,
                'time'=>$timestartt.''.$timefinishh,
                'status'=>$status,
                'statuscancel' => $statuscancel,
                'venue'=>$s->venue,
                'link'=> $sessionlink,
                'location'=> $s->location];
            $varsessions[] = $object;
        }else if(!empty($usersession)){
            $object = (object)[
                'id'=> $s->id,
                'coach'=> fullname($coach,true),
                'name'=>$s->name,
                'duration'=>facetoface_minutes_to_hours($s->duration).' hours',
                'details'=>strip_tags($s->additonal_details),
                'timestart'=> $timestart,
                'timefinish'=> $timefinish,
                'time'=>$timestartt.''.$timefinishh,
                'status'=>$status,
                'statuscancel' => $statuscancel,
                'venue'=>$s->venue,
                'link'=> $sessionlink,
                'location'=> $s->location];
            $varsessions[] = $object;
        }
       
      
      }
            
           
        $data[0] = $varsessions;
        $this->result = $data;
    }

    function dffh_get_user_requests($payload)
{
  global $DB;
  $user_id = $payload['userid'];

  $sql_str = "SELECT request.id as request_id, request.userid as userid_request,request.coachid, user.firstname, user.lastname, request.startdate, coach.firstname as coach_firstname, coach.lastname as coach_lastname,
  F.id AS facetofaceid,
               F.name,
               request.courseid AS courseid,
               FSD1.data AS LOCATION,
               FSD2.data AS room,
               FSD3.data AS venue,
               FSDE.sessionid as sessionidold,
               FSDE.timestart,
               FSDE.timefinish,
               S.duration,
               S.capacity
        FROM mdl_coachmanagement_request request
        LEFT JOIN mdl_facetoface_sessions S
        ON S.facetoface = request.sessionid
        JOIN mdl_user user
        ON request.userid = user.id
        JOIN mdl_user coach
        ON request.coachid = coach.id
        LEFT JOIN mdl_facetoface F
        ON F.id = S.facetoface
        LEFT JOIN mdl_facetoface_sessions_dates FSDE ON S.id = FSDE.sessionid
        LEFT JOIN mdl_facetoface_session_data FSD1 ON S.id = FSD1.sessionid
        AND FSD1.fieldid = 1
        LEFT JOIN mdl_facetoface_session_data FSD2 ON S.id = FSD2.sessionid
        AND FSD2.fieldid = 2
        LEFT JOIN mdl_facetoface_session_data FSD3 ON S.id = FSD3.sessionid
        AND FSD3.fieldid = 3
        WHERE request.userid = $user_id";

  $data = array_values($DB->get_records_sql($sql_str));

  foreach ($data as $item) {
    $date = date('d-m-Y', $item->startdate);
    $time = date('h:i A', $item->startdate);
    $item->date = $date;
    $item->time = $time;
    $item->isAdmin = is_siteadmin();
    $records[] = $item;
  }
  $data[0] = $records;
  $this->result = $data;
  return $this->result;
}

public function dffh_cancel_request($payload){
    global $DB, $USER, $CFG;

    $request_id = $payload['request_id'];
    $request = $DB->get_records_sql('SELECT * FROM {coachmanagement_request} WHERE id = ? ',array($payload['request_id']));
    $coach = $DB->get_record_sql('SELECT * FROM {user} WHERE id = ?',array($request->coachid));
    $requestee = $DB->get_record_sql('SELECT * FROM {user} WHERE id = ?',array($request->userid));
    $emailuser = new stdClass();
    $emailuser->email = $coach->email;
    $emailuser->id = $coach->id;

    $subject = 'DFFH: User canceled request';
    $messagetext = get_string('testoutgoingmailconf_message', 'admin');

    // Manage Moodle debugging options.
 
    $messagehtml = '<p>User <b>'.fullname($requestee).' </b> has cancelled the session request.';
    $messagetext = 'User '.fullname($requestee).' has cancelled the session request.';
    // Send test email.
    $noreplyuser = \core_user::get_support_user();
   
    $DB->delete_records('coachmanagement_request', array('id' => $request_id));

    email_to_user($emailuser, $noreplyuser, $subject, $messagetext, $messagehtml);

    return true;
}
    public function get_user_learning_progress($payload){
        global $PAGE, $DB;
        // $userid = $payload['userid'];
        // $sesskey = $payload['sesskey'];
        //please use userid to get current user overall progress, and user's all completed/incompled courses info
        $get_user_learning_progress = new mindatlas_theme_library();
        $data = $get_user_learning_progress->get_user_learning_progress($payload);

        // $data = [
        //     //completed courses, include: course_id, category_id, fullname, idnumber(if no then ''),
        //     //shortname, idnumber, summary, course_image(url), progress(this user's),
        //     //rate(this course's aeverage, if no rate, then return 0, enrol_time(timestamp))
        //     'completed' => [
        //         (object)[
        //             "course_id" =>"2",
        //             "category_id"=> "2",
        //             "fullname"=> "Completed course 1",
        //             "shottname" => "Completed course 1",
        //             "idnumber"=> "",
        //             // plain text
        //             "summary" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit.Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
        //             "course_image" => "/generic/theme/generic/pix/photo/1.jpg",
        //             //0 - 100, int,
        //             "progress" => 100,
        //             // 0-5, float, 1 decimal place,
        //             "enrol_time" =>1601254210,
        //             "isOverdue" => true,
        //             ],
        //         (object)[
        //             "course_id" =>"3",
        //             "category_id"=> "4",
        //             "fullname"=> "Completed course 2",
        //             "shottname" => "Completed course 2",
        //             "idnumber"=> "",
                    
        //             "summary" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit.Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
        //             "course_image" => "/generic/theme/generic/pix/photo/2.jpg",
        //             "progress" => 100,
        //             "enrol_time" =>1601254210,
        //             "isOverdue" => true,
        //         ],

        //     ],
        //     'not_completed' => [
        //         (object)[
        //             "course_id" =>"4",
        //             "category_id"=> "4",
        //             "fullname"=> "Completed course 4",
        //             "shottname" => "Completed course 4",
        //             "idnumber"=> "",
        //             "summary" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit.Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
        //             "course_image" => "/generic/theme/generic/pix/photo/4.jpg",
        //             "progress" => 80,
        //             "enrol_time" =>1601254210,
        //             "isOverdue" => false,
        //             ],
        //         (object)[
        //             "course_id" =>"5",
        //             "category_id"=> "5",
        //             "fullname"=> "Completed course 5",
        //             "shottname" => "Completed course 5",
        //             "idnumber"=> "",
        //             "summary" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit.Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
        //             "course_image" => "/generic/theme/generic/pix/photo/5.jpg",
        //             "progress" => 60,
        //             "enrol_time" =>1601254210,
        //             "isOverdue" => false,
        //         ],
        //         (object)[
        //             "course_id" =>"8",
        //             "category_id"=> "8",
        //             "fullname"=> "Completed course 8",
        //             "shottname" => "Completed course 8",
        //             "idnumber"=> "",
        //             "summary" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit.Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
        //             "course_image" => "/generic/theme/generic/pix/photo/5.jpg",
        //             "progress" => 90,
        //             "enrol_time" =>1601254210,
        //             "isOverdue" => true,
        //         ],

        //     ]
        // ];
       

        $this->result = $data;

    }
};
exit;


// react & axios example:

// componentDidMount() {
//     axios.post(`http://vba.local/blocks/marking_tool/api/modules.php`, {
//         action: 'get_modules',
//         payload: {
//             userid: M.user.id,
//             sesskey: M.user.sesskey
//         },
//     }).then(res => {
//         console.log(res)
//     })
// }