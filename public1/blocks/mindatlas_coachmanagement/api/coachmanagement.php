<?php

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');
// require_login(); 
define('MDL_F2F_ICAL',   1);
function dffh_error_message($message)
{
    return array('error' => true, 'message' => $message);
}

function dffh_get_coach_users($payload)
{
    //fieldId for practioner - 5
    $field_is_practitioner = 5;
    global $DB, $PAGE;
    $sql_str = "SELECT u.* 
                FROM mdl_user u
                JOIN mdl_user_info_data ud
                ON u.id = ud.userid AND ud.fieldid = $field_is_practitioner
                WHERE  ud.data = 'Yes' OR ud.data = 'yes'";
    $practice_lead_node = $DB->get_record_sql('SELECT * FROM {hierarchy_node} WHERE description = ?',array('Practice Lead'));
    $practice_lead_users = $DB->get_records_sql('SELECT * FROM {hierarchy_user} WHERE node_id = ?',array($practice_lead_node->id));
    $practice_lead_users_array = [];
    foreach($practice_lead_users as $user){
        $practice_lead_users_array[]= $user->user_id;
    }
    $practice_lead_users_array = implode(', ', $practice_lead_users_array);
    $available_users = $DB->get_records_sql('SELECT * FROM {user} WHERE id IN ('.$practice_lead_users_array.')');
    foreach($available_users as $row){
        $userpicture = new user_picture($user);
        $userpicture = (string)($userpicture->get_url($PAGE));
        $row->img = $userpicture;
    }
    return array_values($available_users);
}

define('COACHMANGEMENT_REQUESTED', 0);
define('COACHMANGEMENT_ACCEPT', 1);
define('COACHMANGEMENT_REJECT', 2);

function get_coach_node( $node_id) {
    global $DB;
    $node = $DB->get_record('hierarchy_node', array('id' => $node_id));
   
    if ($node->parent_node_id == 1) {
      return $node->id;
    } else {
      $parent_node = $DB->get_record('hierarchy_node', array('id' => $node->parent_node_id));
      if ($parent_node->parent_node_id == 1) {
        return $parent_node->id;
      } else {
        return get_coach_node($parent_node->id);
      }
    }
}

function dffh_request_coaching_session($payload)
{
    global $DB, $CFG;
    if ($DB->get_record('user', array('id' => $payload['userid'])) == false) {
        return dffh_error_message('The user does not exist');
    }

    // if ($DB->get_record('coachmanagement_request', array('userid' => $payload['userid'], 'coachid' => $payload['coachid'], 'startdate' => $payload['startdate'], 'enddate' => $payload['enddate'])) != false) {
    //     return dffh_error_message('the request exists');
    // }
   
    $requestobj = new stdClass();
    $node_user_record = $DB->get_record('hierarchy_user', array('user_id' => $payload['userid']));
    $node = $DB->get_record('hierarchy_node', array('id' => $node_user_record->node_id));
    $nodeparent = $DB->get_record('hierarchy_node', array('id' => $node->parent_node_id));
    $coachuser= '';

    $hascoach = $DB->get_record('hierarchy_coach', array('user_id' => $payload['userid']));
    if($hascoach){
      $coachuser = $hascoach;
      $link = $CFG->wwwroot.'/theme/mindatlas/pages/my_coaching_sessions_manager.php';
    }else if($nodeparent->id != 1){
        $coachnodeid = get_coach_node( $nodeparent->id);
        $coachuser =  $DB->get_record('hierarchy_coach', array('node_id' => $coachnodeid));
        $link = $CFG->wwwroot.'/theme/mindatlas/pages/my_coaching_sessions.php';
    }else{
        $coachuser = new stdClass();
        $coachuser->user_id = $payload['userid'];
        $link = $CFG->wwwroot.'/theme/mindatlas/pages/my_coaching_sessions_manager.php';
    }
    if($node_user_record==null){
        throw new Exception('The user must be in the hierarchy');
    };
    $coach = $DB->get_record_sql('SELECT * FROM {user} WHERE id = ?',array($coachuser->user_id));
    $requestee = $DB->get_record_sql('SELECT * FROM {user} WHERE id = ?',array($payload['userid']));
    $requestobj->userid = $payload['userid'];
    $requestobj->coachid = $coachuser->user_id;
    $requestobj->courseid = $payload['courseid'];
    $requestobj->startdate = toUnixtime($payload['startdate'], $payload['startTime']);
    $requestobj->enddate = $payload['enddate'];
    $requestobj->status = COACHMANGEMENT_REQUESTED;
    
    $requestobj->id = $DB->insert_record('coachmanagement_request', $requestobj, true);

    $emailuser = new stdClass();
    $emailuser->email = $coach->email;
    $emailuser->id = $coach->id;

    $subject = 'DFFH: New Coaching Request';
    $messagetext = get_string('testoutgoingmailconf_message', 'admin');

    // Manage Moodle debugging options.
 
    $messagehtml = '<p>User <b>'.fullname($requestee).' </b> has requested a new coaching session. Check here: </p>
    <a href="'.$CFG->wwwroot.'/theme/mindatlas/pages/my_coaching_sessions_manager.php"> Link </a>';
    $messagetext = 'User '.fullname($requestee).' has requested a new coaching session. Check here:'.$CFG->wwwroot.'/theme/mindatlas/pages/my_coaching_sessions_manager.php';
    // Send test email.
    $noreplyuser = \core_user::get_support_user();
    $requestobj->link = $link;
    email_to_user($emailuser, $noreplyuser, $subject, $messagetext, $messagehtml);

  
   
    return $requestobj;
}

function toUnixtime($dateStr = null, $hhmmss = '00:00:00')
{
    $date = date_create($dateStr);
    $date_real = date_format($date, 'Y-m-d') . ' ' . $hhmmss;
    return date_create_from_format('Y-m-d H:i A', $date_real)->getTimestamp();
}


function dffh_list_coaching_session_manager($payload)
{
    $userid = $payload['userid'];
    global $DB;
    if(is_siteadmin()){
        $sql_str = "SELECT u.id, u.firstname, u.lastname, count(ud.id) as total
        FROM mdl_user u
        JOIN mdl_coachmanagement_request ud
        ON u.id = ud.coachid
        group by ud.coachid";
    }else{
        $sql_str = "SELECT u.id, u.firstname, u.lastname, count(ud.id) as total
        FROM mdl_user u
        JOIN mdl_coachmanagement_request ud
        ON u.id = ud.coachid
        WHERE ud.coachid = ?
        group by ud.coachid";
    }
   
    $records = $DB->get_records_sql($sql_str,array($userid));

    foreach ($records as $record) {
        $node_user_record = $DB->get_record('hierarchy_user', array('user_id' => $record->id));
        $node = $DB->get_record('hierarchy_node', array('id' => $node_user_record->node_id));
        if ($node_user_record) {
            $record->agency = $node->name;
        } else {
            $record->agency = 'No Agency';
        }
    }
    return array_values($records);
}

function dffh_request_list_course($payload)
{
    global $DB;
    $coaching_category = $DB->get_record_sql('SELECT * FROM {course_categories} WHERE idnumber = "coaching"');
    $sql_str = "SELECT id, fullname 
    FROM mdl_course WHERE category = ?";
   
    $records = $DB->get_records_sql($sql_str,array($coaching_category->id));
 
    return array_values($records);
}

function dffh_update_request_coaching_session($payload)
{
    global $DB;
    $requestobj = new stdClass();
    $requestobj->id = $payload['id'];
    $requestobj->coachid = $payload['coachid'];
    $requestobj->courseid = $payload['courseid'];
    $requestobj->startdate = toUnixtime($payload['startdate'], $payload['startTime']['value']);
    $requestobj->enddate = $payload['enddate'];
    $DB->update_record('coachmanagement_request', $requestobj);
    return $requestobj;
}



// function dffh_get_session_id($payload)
// {
//   global $DB;
//   $request_id = $payload['request_id'];
//   $course_id = $payload['course_id'];

//   $sql_str = "select f.id as facetoface_id, f.name as facetoface_name, fs.id as session_id, GROUP_CONCAT(fsf.name, ' : ', fsd.data) as data_field
//   from mdl_facetoface f
//   JOIN mdl_facetoface_sessions fs
//   on f.id = fs.facetoface
//   JOIN mdl_facetoface_session_data fsd
//   ON fs.id = fsd.sessionid
//   JOIN mdl_facetoface_session_field fsf
//   ON fsd.fieldid = fsf.id
//   where f.course = $course_id
//   GROUP BY fs.id";x§
//   $data = array_values($DB->get_records_sql($sql_str));

//   return $data;
// }

function dffh_get_available_users($payload){
 
    global $DB,$USER, $CFG;

    require_once($CFG->dirroot.'/blocks/reporting/report/lib.php');
    $request = optional_param('request', 0, PARAM_INT);
    $useragency = $DB->get_record('coachmanagement_request',array('id'=>$request));
   
   $node_user_record = $DB->get_record('hierarchy_user', array('user_id' => $USER->id));
   $coach_node = $DB->get_record('hierarchy_user', array('user_id' => $useragency->userid));
   $coachnode = get_coach_node($coach_node->node_id);
   
    if($node_user_record==null){
        throw new Exception('The user must be in the hierarchy');
    };
    $node_user_record =  $node_user_record->node_id;
    $arrnodes = explode(',', $node_user_record);
    $arr_userss = array();
    $is_admin =is_siteadmin($USER->id);
  
    // Check if the user belong to selected node list, then the current user has to be added into the list
    $currentUserNodeId = $DB->get_field('hierarchy_user','node_id',array('user_id'=>$USER->id));
  
        // Add all users in selected nodes into the list
        $sql_user = "SELECT user_id from mdl_hierarchy_user where node_id in($node_user_record) group by user_id";
        $rs_user = $DB->get_records_sql($sql_user);
        if($rs_user){
            foreach ($rs_user as $row_user) {
                $arr_users [] = $row_user->user_id;
            }
        }
    
    // Get all users under the selected nodes
    $nodes_queue = array();
    foreach ($arrnodes as $node_id) {
        // Find all children nodes of current node
        //if(!in_array($node_id, $nodes_queue)) $nodes_queue [] = $node_id;
        $all_children_node_ids = array();
        $children_node_ids_queue = array();
        $lower_level_children_nodes_records = $DB->get_records('hierarchy_node', array('parent_node_id' => $node_id));
        if ($lower_level_children_nodes_records != false) {
          foreach ($lower_level_children_nodes_records as $lower_level_children_nodes_record) {
            $all_children_node_ids[] = $lower_level_children_nodes_record->id;
            $children_node_ids_queue[] = $lower_level_children_nodes_record->id;
          }
        }
      
        while (count($children_node_ids_queue) != 0) {
          foreach ($children_node_ids_queue as $index => $child_node_id) {
            $lower_level_children_nodes_records = $DB->get_records('hierarchy_node', array('parent_node_id' => $child_node_id));
            if ($lower_level_children_nodes_records != false) {
              foreach ($lower_level_children_nodes_records as $lower_level_children_nodes_record) {
                $all_children_node_ids[] = $lower_level_children_nodes_record->id;
                $children_node_ids_queue[] = $lower_level_children_nodes_record->id;
              }
            }
      
            /* remove node from queue */
            unset($children_node_ids_queue[$index]);
          }
        }
        
        if (!empty($all_children_node_ids)) {
            foreach($all_children_node_ids as $child_node_id){
                if(!in_array($child_node_id, $nodes_queue)) $nodes_queue [] = $child_node_id;
            }
        }
        // Include the current nodes as well => This will other user can see each others in the same node
        // $nodes_queue [] = $node_id; // => Should be disabled
    }
    // Get all users in these nodes: nodes_queue.
    $all_users = array();
    if(!empty($nodes_queue)){
        $list = implode(',',$nodes_queue);
        $sql = "select user_id from mdl_hierarchy_user where node_id in($list)";
        $all_users = $DB->get_records_sql($sql,array());
    }
    foreach ($all_users as $r) {
      $userdata = $DB->get_record_sql('SELECT * FROM {user} WHERE id = ? ',array($r->user_id));
      $user = new stdClass;
      $user->value = $userdata->id;
      $user->label = $userdata->firstname.' '.$userdata->lastname;
        $arr_userss [] = $user;
    }

   return $arr_userss;
}

function dffh_check_coach($payload){
    global $DB;

    
    $node_user_record = $DB->get_record('hierarchy_user', array('user_id' => $payload['userid']));
    $node = $DB->get_record('hierarchy_node', array('id' => $node_user_record->node_id));
    $nodeparent = $DB->get_record('hierarchy_node', array('id' => $node->parent_node_id));
    $coachuser= '';
    if($nodeparent->id == 1){
    return true;
    }else{
    return false;
    }
}

function dffh_create_session($payload){
  global $DB, $CFG;

  require_once($CFG->dirroot.'/mod/facetoface/lib.php');
  $course_id = $payload['course_id'];
    
  $sql_str = "select * from mdl_facetoface where course = ? LIMIT 1 ";
  $facetofaceid = $DB->get_record_sql($sql_str,array($course_id));
 
    $session = new stdClass;
    $session->facetoface = $facetofaceid->id;
    $session->capacity = count($payload['staff']) + 1;
    $session->allowoverbook = 0;
    $getrequest = $DB->get_record_sql('SELECT * FROM {coachmanagement_request} WHERE id = ?',array($payload['id']));
    $getcoach = $DB->get_record('user',array('id' => $getrequest->coachid));
    $session->details = fullname($getcoach,true).'\'s coaching session';
    $session->datetimeknown = 1;
    $session->duration = intdiv($payload['duration'], 60).':'. ( $payload['duration'] % 60);
    $session->normalcost = 0;
    $session->discountcost = 0;
    $session->timezone = 'Australia/Sydney';
    $session->additonal_details = '';
    $session->allowcancellations = 1;
    $customfields = facetoface_get_session_customfields();
    $sessiondates = array();
    $date = new stdClass();
    $payload['startdate'] = substr($payload['startdate'], 0, strpos($payload['startdate'], "T"));
  
    $timestart = $payload['startdate'].' '.$payload['startTime']['value'];
    $timestamp1 = strtotime($timestart);
    $timestartfield = $timestamp1;
    
    $timefinishfield = $timestamp1 + ($payload['duration'] * 60);

    
    $date->id = 0;
    $date->timestart = $timestartfield;
    $date->timefinish = $timefinishfield;
    $date->is_deleted = 0;
    $sessiondates[] = $date;
   
  $session->id = facetoface_add_session($session, $sessiondates);
  $coach = new stdClass;
  $coach->userid = $payload['coachid'];
  $coach->roleid = 3;
  $coach->sessionid = $session->id;
  $DB->insert_record('facetoface_session_roles',$coach);
  $fromform = new stdClass;
  $fromform->location = $payload['location'];
 
  foreach ($customfields as $field) {
    if($field->shortname == 'location'){
        $fieldname = $field->shortname;
        if (!isset($fromform->$fieldname)) {
            $fromform->$fieldname = ''; // Need to be able to clear fields.
        }
       
        if (!facetoface_save_customfield_value($field->id, $fromform->$fieldname, $session->id, 'session')) {
            print_error('error:couldnotsavecustomfield', 'facetoface', $returnurl);
        }
    }

    $DB->delete_records('coachmanagement_request',array('id' => $payload['id']));
}

facetoface_update_calendar_entries($session, $facetofaceid);
$course = $DB->get_record('course', array('id' => $course_id));
 // Make sure that the user is enroled in the course.
 $context = context_course::instance($course_id);
 
 foreach($payload['staff'] as $users){
 $user = $DB->get_record('user', array('id' => $users['value']));

    // Make sure that the user is enroled in the course
    if (!is_enrolled($context, $user)) {
        $studentroleid = $DB->get_field('role','id',array('shortname'=>'student','archetype'=>'student'));  // MA-MODIFIED 
        if (!enrol_try_internal_enrol($course_id, $user->id, $studentroleid)) {    // MA-MODIFIED 
            $errors[] = get_string('error:enrolmentfailed', 'facetoface', fullname($user));
            $errors[] = get_string('error:addattendee', 'facetoface', fullname($user));
            continue; // Don't sign the user up.
        }
    }

$usernamefields = get_all_user_name_fields(true);

    // Check if we are waitlisting or booking.
    $subject = 'DFFH: You have been registered to a coaching session.';
    $messagetext = get_string('testoutgoingmailconf_message', 'admin');
    if(!empty($payload['email'])){
        $messagehtml = $payload['email'];
        $messagetext = $payload['email'];
    }else{
        $messagehtml = '<p> You have been succesfully booked to a coaching session. For more details please access this page: <a> href="'.$CFG->wwwroot.'/theme/mindatlas/pages/my_coaching_sessions.php">Link</a>';
        $messagetext ='<p> You have been succesfully booked to a coaching session. For more details please access this page: <a> href="'.$CFG->wwwroot.'/theme/mindatlas/pages/my_coaching_sessions.php">Link</a>';
    }
    $attachmentfilename = 'invite.ics';
    $notificationtype = MDL_F2F_INVITE;
    foreach ($sessiondates as $sessiondate) {
        $session->sessiondates = array($sessiondate); // One day at a time.

        $filename = facetoface_get_ical_attachment($notificationtype, $facetofaceid, $session, $user);
        $subject = 'DFFH: You have been registered to a coaching session';
        $body = facetoface_email_substitutions($messagehtml, format_string($facetofaceid->name), $facetofaceid->reminderperiod,
                                               $user, $session, $session->id,$coursename);
        $htmlmessage = facetoface_email_substitutions($messagehtml, $facetofaceid->name, $facetofaceid->reminderperiod, $user, $session, $session->id,$coursename);
        $htmlbody = $htmlmessage;
        $icalattachments[] = array('filename' => $filename, 'subject' => $subject,
                                   'body' => $body, 'htmlbody' => $htmlbody);
    }
    // Send test email.
    $noreplyuser = \core_user::get_support_user();
    $requestobj->link = $link;
     // Send email with iCal attachment.
        foreach ($icalattachments as $attachment) {
            if (!email_to_user($user, $noreplyuser, $attachment['subject'], $attachment['body'],
                    $attachment['htmlbody'],$attachment['filename'][0], $attachmentfilename)) {

                return 'error:cannotsendconfirmationuser';
            }
            unlink($CFG->dataroot . '/' . $attachment['filename']);
        }
    
    if ($session->datetimeknown) {
        $status = MDL_F2F_STATUS_BOOKED;
    } else {
        $status = MDL_F2F_STATUS_WAITLISTED;
    }
    if (!facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_BOTH,
    $status, $user->id, false)) {
        $erruser = $DB->get_record('user', array('id' => $user->id), "id, {$usernamefields}");
       
        $errors[] = get_string('error:addattendee', 'facetoface', fullname($erruser));
    }
}
 

 $DB->delete_records('filter_active', array('id' => $payload['id']));

return true;
}
