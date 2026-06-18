<?php
// MA-MODIFIED 
require_once($CFG->dirroot.'/blocks/reporting/report/lib.php');
require_once($CFG->dirroot.'/admin/tool/hierarchy/lib.php');

//Get list of team member
function get_hierarchy_teammembers($userid){
	global $DB,$CFG;
	$sql = "SELECT hu.user_id from mdl_hierarchy_user hu inner join mdl_hierarchy_node hn on hu.node_id=hn.id where hn.parent_node_id in(select hu2.node_id from mdl_hierarchy_user as hu2 where hu2.user_id=?) group by hu.user_id";
	$rs = $DB->get_records_sql($sql,array($userid));
	if(!empty($rs)){
		$array = array();
		foreach ($rs as $row) {
			$array [] = $row->user_id;
		}
		return $array;
  }else 
    return [];
}


function face_to_face_get_all_child_users_from_userid($userid) {
  global $DB;

  $node_id = $DB->get_field('hierarchy_user', 'node_id', array('user_id'=>$userid));

  $nodes_queue = array();

  // Find all children nodes of current node
  $all_children_nodes = find_children_nodes($node_id);
  foreach ($all_children_nodes as $child_node_id) {
	if(!in_array($child_node_id, $nodes_queue)) $nodes_queue [] = $child_node_id;
  }

  // Get all users in these nodes: nodes_queue.
  $arr_users = array();
  if (!empty($nodes_queue)) {
    $list = implode(',',$nodes_queue);
    $sql = "select user_id from mdl_hierarchy_user where node_id in($list)";
    $all_users = $DB->get_records_sql($sql,array());
    foreach ($all_users as $r) {
      $arr_users [] = $r->user_id;
    }
  }
  return $arr_users;
}


function face_to_face_check_hierarchy(){
	global $DB;
	$is_install = false;
	if($DB->record_exists('config_plugins',array('plugin'=>'tool_hierarchy'))) $is_install = true;
	return $is_install;
}


/**
 *  Get list of users in the session waitlist
 *  MA-MODIFIED based on facetoface_get_attendees($sessionid)
 * @access public
 * @param integer Session ID
 * @param integer user ID, refine the attendees under this user in hierarchy
 * @return array
 */
function ma_facetoface_get_waitlist_attendees($sessionid, $userid=NULL) {
    global $CFG, $DB;

    $usernamefields = get_all_user_name_fields(true, 'u');
    // MA-MODIFIED: in sql add 'ss.note,' between ss.statuscode, and sign.timecreated
    $records = $DB->get_records_sql("
        SELECT u.id, {$usernamefields},
            u.email,
            su.id AS submissionid,
            s.id AS sessionid,
            s.discountcost,
            su.discountcode,
            su.notificationtype,
            f.id AS facetofaceid,
            f.course,
            ss.id AS signupstatusid,
            ss.statuscode,
            ss.waitlist_priority,    
            sign.timecreated
        FROM
            {facetoface} f
        JOIN
            {facetoface_sessions} s
         ON s.facetoface = f.id
        JOIN
            {facetoface_signups} su
         ON s.id = su.sessionid
        JOIN
            {facetoface_signups_status} ss
         ON su.id = ss.signupid
        LEFT JOIN
            (
            SELECT
                ss.signupid,
                MAX(ss.timecreated) AS timecreated
            FROM
                {facetoface_signups_status} ss
            INNER JOIN
                {facetoface_signups} s
             ON s.id = ss.signupid
            AND s.sessionid = ?
            WHERE
                ss.statuscode IN (?,?)
            GROUP BY
                ss.signupid
            ) sign
         ON su.id = sign.signupid
        JOIN
            {user} u
         ON u.id = su.userid
        WHERE
            s.id = ?
        AND ss.superceded != 1
        AND ss.statuscode = ?
        ORDER BY
            ss.waitlist_priority ASC,
            ss.timecreated ASC,
            sign.timecreated ASC

    ", array ($sessionid, MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_WAITLISTED, $sessionid, MDL_F2F_STATUS_WAITLISTED));

    if ($userid == NULL) {

      return array_values($records);
      
    }else{
      // if user id provided, refine the results
      $node = hierarchy_get_user_node($userid);

      $descendants = hierarchy_get_node_descendant_userids($node->name);

      // remvoe records not below this user in hierarchy
      foreach ($records as $key => $record) {
        if (!in_array($record->id, $descendants)) {
          unset($records[$key]);
        }
      }

      return array_values($records);      
    }
    
}


function ma_update_interest($facetofaceid, $userid, $interested){
  global $DB;

  $record = ma_get_interest($facetofaceid, $userid);
  $result = false;

  $timemodified = time();
  $emailsent = ma_send_interest_expression_email($facetofaceid, $userid);

  if ($record) {
    // interest record exist
    $record->interested = $interested;
    $record->emailsent = $emailsent;
    $record->timemodified = $timemodified;

    $result = $DB->update_record('facetoface_interest', $record);

  }else{
    // new interest record
    $record = new stdClass();
    $record->facetoface = $facetofaceid;
    $record->userid = $userid;
    $record->interested = $interested;
    $record->emailsent = $emailsent;
    $record->timemodified = $timemodified;

    $result = $DB->insert_record('facetoface_interest', $record);
  }

  return $result;

}

function ma_send_interest_expression_email($facetofaceid, $userid) {
  global $DB, $CFG;

  $user = $DB->get_record('user', array('id'=>$userid));

  $facetoface = $DB->get_record('facetoface', array('id' =>$facetofaceid ));
  $course_name = $DB->get_field('course', 'fullname', array('id'=>$facetoface->course));
  $facetoface_url = $CFG->wwwroot.'/mod/facetoface/view.php?f='.$facetofaceid;

  $adminids = explode(',', $CFG->siteadmins);
  $adminuser = get_complete_user_data('id', reset($adminids));

  $support_user = core_user::get_support_user();

  $emailbody = '';
  $emailbody .= '<p>Dear ' . fullname($support_user) . ',</p>';
  $emailbody .= '<br />';
  
  $emailbody .= '<p>'.$user->firstname.' '.$user->lastname.' has expressed interest in the following face-to-face activity.</p>';
  $emailbody .= '<p><a href="'.$facetoface_url.'">';
  $emailbody .= $facetoface->name;
  $emailbody .= '</a></p>';
  $emailbody .= '<br />';

  $emailbody .= '<p>Kind regards,</p>';
  $emailbody .= '<p>' . fullname($support_user) . '</p>';
  $emailbody .= '<p>' . $support_user->email . '</p>';

  $from_user = \core_user::get_support_user();
  $email_text = html_to_text($emailbody);

  $emailsent = email_to_user($adminuser, $from_user, 'FSV face-to-face: Expressions of interest', $email_text, $emailbody);

  return $emailsent;

}

function ma_html_interest_btn($facetofaceid, $userid){

  $interest = ma_get_interest($facetofaceid, $userid);

  $html = '';
  $html .= '<form id="form_interested" action="#" method="post">';

  
  if ($interest && $interest->interested == 1) {
    // is interested
    $html .= '<p>You have expressed interest in this activity.</p>';
  }else{
    // not record or not interested
    $html .= '<input id="form_interested_facetoface" name="facetoface" type="hidden" value="'.$facetofaceid.'">';
    $html .= '<input id="form_interested_userid" name="userid" type="hidden" value="'.$userid.'">';
    $html .= '<input id="form_interested_interested" name="interested" type="hidden" value="1">';
    $html .= '<input type="submit" value="Send expression of interest">';
  }


  $html .= '</form>';

  return $html;
}


function ma_get_interest($facetofaceid, $userid){
  global $DB;
  return $DB->get_record('facetoface_interest', array('facetoface' => $facetofaceid, 'userid'=>$userid));
}

// all interested records belong to this facetoface
function ma_get_facetoface_interested($facetofaceid){
  global $DB;
  return $DB->get_records('facetoface_interest', array('facetoface' => $facetofaceid));
}


// BR.08 facetoface do not have a current session
function ma_facetoface_has_upcoming_session($facetofaceid) {
  global $DB;
  $has_upcoming = false;

  $sessions = facetoface_get_sessions($facetofaceid);

  foreach ($sessions as $key => $session) {
    if (!facetoface_has_session_started($session, time())) {
      $has_upcoming = true;
      break;
    }
  }

  return $has_upcoming;

}

function ma_facetoface_clear_interest($facetofaceid) {
  global $DB;

  if (ma_facetoface_has_upcoming_session($facetofaceid)) {
    return $DB->delete_records('facetoface_interest', array('facetoface' => $facetofaceid));
  }else{
    return false;
  }

}

function html_tbody_interested($rows){
    global $DB;

    $html = '';

    foreach ($rows as $key => $row) {

        $courseurl = new moodle_url('/course/view.php', array('id'=>$row->course));
        $activityurl = new moodle_url('/mod/facetoface/view.php', array('f'=>$row->id));

        $html .= '<tr class="" data-courseid="'.$row->course.'" data-sessionid="'.$row->id.'">';
        $html .=    '<td class="c0"><a href="'.$courseurl.'">'.$row->coursename.'</a></td>';
        $html .=    '<td class="c1"><a href="'.$activityurl.'">'.$row->name.'</a></td>';
        $html .=    '<td class="c2">'.$row->interested.'</td>';
        $html .= '</tr>';
    }

    return $html;

}

/**
 * check if timezone is same as server's timezone
 * @param string $tz_str
 * @return bool
 */
function ma_facetoface_same_timezone_as_server($tz_str) {
  $server_tz = core_date::get_server_timezone();
  $tz_str = str_replace(' ', '_', trim($tz_str));
  return $server_tz === $tz_str;
}

/**
 * calculate difference (in seconds) between two timezones
 * if check flag is true, and tz_str1 is the same as server's timezone, return 0
 * @param string $tz_str1
 * @param string $tz_str2
 * @return int offset
 */
function ma_facetoface_get_timezone_offset_diff($tz_str1, $tz_str2, $check = true) {
  
  $tz_str1 = str_replace(' ', '_', trim($tz_str1));
  $tz_str2 = str_replace(' ', '_', trim($tz_str2));
  
  if ($check && ma_facetoface_same_timezone_as_server($tz_str1)) {
    return 0;
  }
  
  $tz1 = new DateTimeZone($tz_str1);
  $tz2 = new DateTimeZone($tz_str2);
  $date1 = new DateTime("now", $tz1);
  $date2 = new DateTime("now", $tz2);
  $tz1_offset = $tz1->getOffset($date1);
  $tz2_offset = $tz2->getOffset($date2);
  return $tz1_offset - $tz2_offset;
}

/**
 * Return human readable date based on timezone
 * @param str $tz timezone
 * @param int $timestamp unix timestamp
 * @param str $format see date function predefined format
 * @return type
 */
function ma_facetoface_get_date_format($tz, $timestamp, $format = 'Y-m-d H:i:s A') {
  if (!isset($tz)) {
    $tz = core_date::get_server_timezone();
  }
  $dt = new DateTime("now", new DateTimeZone($tz)); 
  //adjust the object to correct timestamp
  $dt->setTimestamp($timestamp); 
  return $dt->format($format);
}

/**
 * Gives an adjusted timestamp of a session
 * For example, given a timestamp (this is usually a server timestamp),
 * to work out the timestamp for a perth user for a brisbane session, the formula:
 * timestamp - diff in seconds between server and user + diff in seconds between server and session
 * @param int $timestamp
 * @param string $session_tz Session timezone
 * @param string $user_tz User timezone
 * @param string $server_tz Server timezone
 * @return int adjusted timestamp
 */
function ma_facetoface_get_adjusted_timestamp($timestamp, $session_tz, $user_tz = null, $server_tz = null) {
  if (!isset($user_tz)) {
    $user_tz = core_date::get_user_timezone();
  }
  if (!isset($server_tz)) {
    $server_tz = core_date::get_server_timezone();
  }
  if (!isset($timestamp) || !isset($session_tz)) {
    return 0;
  }

  $timezone_user    = new DateTimeZone($user_tz);
  $timezone_session = new DateTimeZone($session_tz);
  $timezone_server  = new DateTimeZone($server_tz);
  $user_offset    = $timezone_user->getOffset(new DateTime("now", $timezone_user));
  $session_offset = $timezone_session->getOffset(new DateTime("now", $timezone_session));
  $server_offset  = $timezone_server->getOffset(new DateTime("now", $timezone_server));
//  error_log('$user_offset ' . $user_offset);
//  error_log('$session_offset ' . $session_offset);
//  error_log('$server_offset ' . $server_offset);
  return $timestamp - ($server_offset - $user_offset) + ($server_offset - $session_offset);
}

function ma_facetoface_offset_timestamp($timestamp, $from_tz = '', $to_tz = ''){

  $server_tz = core_date::get_server_timezone();
  $server_tz = new DateTimeZone($server_tz);
  
  if ($from_tz) {
    $from_tz = str_replace(' ', '_', $from_tz);
    $from_tz = new DateTimeZone($from_tz);
  }else{
    $from_tz = $server_tz;
  } 
 
  if ($to_tz) {
    $to_tz = str_replace(' ', '_', $to_tz);
    $to_tz = new DateTimeZone($to_tz);
  }else{
    $to_tz = $server_tz;
  }

  $datetime_from = new DateTime("now", $from_tz);
  $datetime_to = new DateTime("now", $to_tz);

  $timezone_offset_from = timezone_offset_get($from_tz,$datetime_from);
  $timezone_offset_to = timezone_offset_get($to_tz,$datetime_to);
  
  return $timestamp + ($timezone_offset_to - $timezone_offset_from);

}

function ma_f2f_timestamp_conversion_timezone($timestamp, $user_tz='', $session_tz=''){

    $server_tz = core_date::get_user_timezone();
    // $server_tz = new DateTimeZone($server_tz);

    if ($user_tz) {
      $user_tz = str_replace(' ', '_', $user_tz);
      // $user_tz = new DateTimeZone($user_tz);
    }else{
      $user_tz = $server_tz;
    } 

    if ($session_tz) {
      $session_tz = str_replace(' ', '_', $session_tz);
      // $session_tz = new DateTimeZone($session_tz);
    }else{
      $session_tz = $server_tz;
    }

    $original_time_object = new DateTime('now', new DateTimeZone($user_tz));
    $original_time_object->setTimestamp($timestamp);

    $timestart_object = new DateTime('now', new DateTimeZone($session_tz));
    $timestart_object->setDate($original_time_object->format('Y'), $original_time_object->format('m'), $original_time_object->format('d'));
    $timestart_object->setTime($original_time_object->format('G'), $original_time_object->format('i'));

    return $timestart_object->format('U');
}

function ma_facetoface_get_session_timezone($sessionid){
  global $DB;

  return ma_facetoface_get_session_customfield_data($sessionid, 'timezone');

}

function ma_facetoface_get_session_timezone_v1($sessionid){
  global $DB;
  $tz = '';

  $session = $DB->get_record('facetoface_sessions', array('id'=>$sessionid));

  if($session){
    if($session->datetimeknown){
      $tz = $session->timezone;
    }
  }

  return $tz;

}

// TODO, make it work for field id/name/shortname
function ma_facetoface_get_session_customfield_data($sessionid, $fieldshortname){
  global $DB;

  $field = $DB->get_record('facetoface_session_field', array('shortname' => $fieldshortname));
  $data = $DB->get_field('facetoface_session_data', 'data', array('fieldid'=>$field->id, 'sessionid'=>$sessionid));

  return $data;
}

function ma_check_hierarchy_manager($userid){
   global $DB;
   if($DB->record_exists_sql('SELECT * from mdl_hierarchy_node where parent_node_id in(select node_id from mdl_hierarchy_user where user_id=?)',array($userid))) return true;
   else return false;
}

function ma_facetoface_coursef2f_options($selected = "", 
                                         $has_capability = false, 
                                         $is_manager = false){
  global $DB,$USER;
  if(!is_siteadmin($USER->id) && !$is_manager && !$has_capability) 
    $sql = 'SELECT f.id,f.name as f2fname, c.fullname as coursename,c.id as courseid from mdl_facetoface as f inner join mdl_course c on c.id=f.course where c.id in(SELECT e.courseid from mdl_enrol as e inner join mdl_user_enrolments ue on ue.enrolid=e.id where ue.userid='.$USER->id.' group by e.courseid) order by coursename ASC, f2fname ASC';
  else 
    $sql = 'SELECT f.id,f.name as f2fname, c.fullname as coursename,c.id as courseid from mdl_facetoface as f inner join mdl_course c on c.id=f.course order by coursename ASC, f2fname ASC';
  $rs = $DB->get_records_sql($sql);
  $html="<option value=''></option>";
  if(!empty($rs)){
    foreach ($rs as $row) {
      if($row->id == $selected) $html.="<option value='".$row->id."' selected>".$row->coursename." \ ".$row->f2fname."</option>";
      else $html.="<option value='".$row->id."'>".$row->coursename." \ ".$row->f2fname."</option>";
    }
  }
  return $html;
}

function dffh_get_user_sessions($userid) {
        global $DB, $USER;
        
        //$userid = $payload['userid'];
        //$limit = $payload['limit'];
        //$extrafields = $payload['extrafields']; 
        $include_past = false;
        $fields = array(
            'fsess.id', 'f.course','fsess.additonal_details','f.name','fsess_dates.timestart', 'fsess_dates.timefinish');
       
        /*foreach ($extrafields as $field) {
            if (!in_array($field, $fields)) {
                $fields[] = $field;
            }
        }*/
        $condition1="";
        $condition2="";
        
        $timenow = time();
        
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
        //$bookedsession = $session->bookedsession;
        $sessionstarted = false;
        $sessionfull = false;
        // Capacity.
        $signupcount = facetoface_get_num_attendees($session->id, MDL_F2F_STATUS_APPROVED);
        $stats = $session->capacity - $signupcount;
        /*if ($viewattendees) {
            $stats = $signupcount . ' / ' . $session->capacity;
        } else {*/
            $stats = max(0, $stats);
        //}
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
        $s->venue = !empty($venue) ? $venue->data:null;
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
  
  return $varsessions;
     
    //$data[0] = $varsessions;
    //$this->result = $data;
}


