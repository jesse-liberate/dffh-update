<?php
if (!defined('CLI_SCRIPT')) {
  define('CLI_SCRIPT', true);
}
define('TIME_WAIT_BEFORE_EMAIL', 5); // in seconds
define('FIRST_REMINDER', 'first_time'); // in seconds
define('SECOND_REMINDER', 'second_time'); // in seconds

require_once(__DIR__ . '/../../../config.php');
require_once("$CFG->libdir/completionlib.php");
require_once(__DIR__ . '/lib.php');
require_once($CFG->dirroot. '/admin/tool/trackemails/lib.php');

$is_debug = defined('DEADLINE_INFO') && DEADLINE_INFO === true;

$support_user = core_user::get_support_user();

$course_reminders = array();
if(!isset($courseid) || $courseid==0) $course_ids = $DB->get_fieldset_sql('SELECT courseid from mdl_course_reminder group by courseid');
else $course_ids[] = $courseid;

if(empty($course_ids)) return; // There is no course has been setup yet. The process is stoped

list($course_ids_query, $course_ids_params) = $DB->get_in_or_equal($course_ids);

$not_completed_users_query = "SELECT ue.id as codeid,u.id as userid, u.username, u.email, u.firstname, u.lastname,
  u.maildisplay, u.mailformat, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename,
  MAX(ue.timecreated) as enrolleddate, c.fullname as course_name, c.id as course_id
FROM mdl_user as u
JOIN mdl_user_enrolments ue ON u.id = ue.userid
JOIN mdl_enrol e ON e.id = ue.enrolid
JOIN mdl_course c ON c.id = e.courseid
JOIN mdl_course_modules cm ON c.id = cm.course
JOIN mdl_modules as m ON m.id = cm.module
LEFT JOIN mdl_course_modules_completion cmc ON cmc.userid = u.id AND cmc.coursemoduleid = cm.id
WHERE u.confirmed=1 and u.deleted=0 and u.suspended=0 and cmc.completionstate IS NULL
AND c.visible = 1
AND cm.completion <> " . COMPLETION_TRACKING_NONE . "
AND ue.timecreated IS NOT NULL 
AND c.id $course_ids_query 
GROUP BY u.id, e.courseid 
ORDER BY u.id";

// print_object($not_completed_users_query);
// print_object($course_ids_params);die();

$not_completed_users = $DB->get_records_sql($not_completed_users_query, $course_ids_params);
$now = time();

$tm_reminder_users = array();
$manager_reminder_users = array();
$completion_map = [];

// print_object($not_completed_users);die();

// print_object(date('d M Y H:i', $now));
foreach ($not_completed_users as $not_completed_user) {
  // Use completion_info for checking if the user is enrolled with a role that is
  // in completion report, because is_enrolled() function is not reliable because of caching
  if (isset($completion_map[$not_completed_user->course_id])) {
    $completion = $completion_map[$not_completed_user->course_id];
  } else {
    // Since we only want to use get_tracked_users function therefore we only need course id
    $completion = new completion_info((object) [
      'id' => $not_completed_user->course_id,
    ]);
  }
  $enrolled_users = $completion->get_tracked_users('u.id = :userid', ['userid' => $not_completed_user->userid]);
  if (count($enrolled_users) == 0) {
    continue;
  }
  //Get the first and second date reminder of the user
   $reminder = get_user_course_reminder($not_completed_user->userid,$not_completed_user->course_id);
   // No reminder found, meaning no need to send email continue pls
   if (empty($reminder)) {
     continue;
   }
   $first_reminder = $reminder->firstreminder;
   $second_reminder = $reminder->secondreminder;
   $stop_second_reminder = ($second_reminder==$first_reminder);//When second reminder is disable
   $due_date = $first_reminder + $reminder->secondreminder;

    $reminder_email = $DB->get_record('reminder_email', array(
      'userid' => $not_completed_user->userid,
      'courseid' => $not_completed_user->course_id,
    ));

    if ($reminder_email !== false) {
      $has_reminded_first_time = $reminder_email->firstemail === '1';
      $has_reminded_second_time = $reminder_email->secondemail === '1';
      //Avoid the case that users will receive first and second reminder closely.(same day or one day after)
      if($has_reminded_first_time) $second_reminder = intval($reminder_email->timefirstemail) + intval($reminder->secondreminder);
    } else {
      $has_reminded_first_time = false;
      $has_reminded_second_time = false;
      //Avoid first and second emails are sending at the same time. Users who have not been sent
      $stop_second_reminder=true;
    }
    $has_repeated_reminder = $reminder->repeated==1;

    $is_first_time = $now > $first_reminder && (!$has_reminded_first_time);
    $is_second_time = ($now > $second_reminder) && !$has_reminded_second_time;
    $is_repeated_time = $has_reminded_second_time && $has_repeated_reminder && ($now > (intval($reminder->secondreminder) + intval($reminder_email->timesecondemail)));
    // print_object(date('d M Y H:i', $first_reminder));
    // print_object($now > $first_reminder ? 'true' : 'false');
    // print_object($is_first_time ? 'true': 'false');
    // If deadline is not met or has reminded before then ignore the user
    if(!$is_debug){
      if (!$is_first_time && !$is_second_time && !$is_repeated_time) {
        continue;
      }
    }
    // Extract user info from not_completed_user
    $user = (object) array_intersect_key((array) $not_completed_user, array_flip(array(
      'userid', 'username', 'email', 'firstname', 'lastname', 'maildisplay',
      'mailformat', 'firstnamephonetic', 'lastnamephonetic', 'middlename', 'alternatename',
    )));

    $course = new StdClass();
    $course->id = $not_completed_user->course_id;
    $course->name = $not_completed_user->course_name;
    $course->enrolleddate = $not_completed_user->enrolleddate;
    $course->due_date = $due_date;

    if (!isset($tm_reminder_users[$user->userid])) {
      $user->not_completed_courses = array();
      $tm_reminder_users[$user->userid] = array();

      $tm_reminder_users[$user->userid]['first_time'] = clone $user;
      $tm_reminder_users[$user->userid]['second_time'] = clone $user;
      $tm_reminder_users[$user->userid]['repeated_time'] = clone $user;
    }

    if (($is_debug && !$has_reminded_first_time) || $is_first_time) {
      $tm_reminder_users[$user->userid]['first_time']
        ->not_completed_courses[$course->id] = $course;
      $tm_reminder_users[$user->userid]['first_time']
        ->date_of_sent_out[$course->id] = $first_reminder;
    }
    if(!$stop_second_reminder){
      if (($is_debug && !$has_reminded_second_time) || $is_second_time) {
        $tm_reminder_users[$user->userid]['second_time']
          ->not_completed_courses[$course->id] = $course;
        $tm_reminder_users[$user->userid]['second_time']
          ->date_of_sent_out[$course->id] = $second_reminder;

        if (!isset($manager_reminder_users[$user->userid])) {
          $user->not_completed_courses = array();
          $user->reminders = array();
          $user->date_of_sent_out = $second_reminder;
          $user->is_manager[$course->id] = $reminder->manager;
          $manager_reminder_users[$not_completed_user->userid] = $user;
        }

        $manager_reminder_users[$user->userid]->not_completed_courses[$course->id] = $course;
        $manager_reminder_users[$user->userid]->reminders[] = $reminder;
      }
      if(($is_debug && $has_repeated_reminder && $has_reminded_second_time) ||  $is_repeated_time){
        $tm_reminder_users[$user->userid]['repeated_time']
          ->not_completed_courses[$course->id] = $course;
        $tm_reminder_users[$user->userid]['repeated_time']
          ->date_of_sent_out[$course->id] = intval($reminder->secondreminder) + intval($reminder_email->timesecondemail);
      }
    }
}
// die();
//FIRST REMINDER WILL BE SENT TO USER
if ($is_debug) {
  $DEBUG_RESULTS[] = '<h2>Reminder information:</h2>';
}

// print_object($tm_reminder_users);
// print_object($manager_reminder_users);
// die();
// print_object($manager_reminder_users);die();

foreach ($tm_reminder_users as $user_reminders) {
  foreach ($user_reminders as $reminder_type => $user) {
    if (count($user->not_completed_courses) === 0) {
      continue;
    }
    $subject = get_string('user_reminder_email:subject','tool_setdeadline',$SITE->fullname);
    $a = new stdClass();
    $a->firstname = $user->firstname;
    $a->sitename = $SITE->fullname;
    $courselist="";
    if ($reminder_type == FIRST_REMINDER) {
      if (count($user->not_completed_courses) === 1) {
        $course = reset($user->not_completed_courses);
        $number_of_enrolled_days = intval((time() - intval($course->enrolleddate)) / 86400);
        $courselist = " ".$course->name.", due by " . date('d/m/Y', $course->due_date) . ". ";
      } else {
        $courselist.="<br/><ul>";
        foreach ($user->not_completed_courses as $course) {
          $number_of_enrolled_days = intval((time() - intval($course->enrolleddate)) / 86400);
          $courselist.="<li>".$course->name.", due by " . date('d/m/Y', $course->due_date) . "</li>";
        }
        $courselist.="</ul>";
      }
    } else {
      if (count($user->not_completed_courses) === 1) {
        $course = reset($user->not_completed_courses);
        $number_of_enrolled_days = intval((time() - intval($course->enrolleddate)) / 86400);
        $courselist = $course->name.", due by " . date('d/m/Y', $course->due_date);
      } else {
        $courselist.="<br/><ul>";
        foreach ($user->not_completed_courses as $course) {
          $number_of_enrolled_days = intval((time() - intval($course->enrolleddate)) / 86400);
          $courselist.="<li>".$course->name.", due by " . date('d/m/Y', $course->due_date) . "</li>";
        }
        $courselist.="</ul>";
      }
    }
    $a->courselist = $courselist;
    $a->support_fullname = trim($support_user->firstname." ".$support_user->lastname);
    $a->support_email = $support_user->email;
    $a->siteurl = $CFG->wwwroot;
    $a->learning_path_url = (string) (new moodle_url('/course/'));
    if ($reminder_type == FIRST_REMINDER) {
      $messagehtml = get_string('user_reminder_email:body','tool_setdeadline',$a);
    } else {
      if (count($user->not_completed_courses) === 1) {
        $messagehtml = get_string('user_overdue_single_course_email:body','tool_setdeadline',$a);
      } else {
        $messagehtml = get_string('user_overdue_several_courses_email:body','tool_setdeadline',$a);
      }
    }
    $messagetext = html_to_text($messagehtml);

    if (!$is_debug) {
      // $instanceid = ($reminder_type == FIRST_REMINDER) ? 1 : 2;
      $instanceid = rand(1000,100000);
      trackemail_to_userid('reminder_email',$instanceid, $user->userid, $subject,$messagehtml);

      $transaction = $DB->start_delegated_transaction();
      foreach ($user->not_completed_courses as $course) {
        $reminder_email = $DB->get_record('reminder_email', array(
          'userid' => $user->userid,
          'courseid' => $course->id,
        ));
        if($reminder_type == FIRST_REMINDER){
          if ($reminder_email === false) {
            $reminder_email = new StdClass();
            $reminder_email->userid = $user->userid;
            $reminder_email->courseid = $course->id;
            $reminder_email->firstemail = 1;
            $reminder_email->secondemail = null;
            $reminder_email->timefirstemail = time();
            $reminder_email->id = $DB->insert_record('reminder_email', $reminder_email);
          } else {
            $reminder_email->firstemail = 1;
            $reminder_email->secondemail = null;
            // $reminder_email->timefirstemail = time();
            $DB->update_record('reminder_email', $reminder_email);
          }
        }else{//Second reminder and repeated reminder
          $reminder_email->secondemail = 1;
          if($reminder_type==SECOND_REMINDER){ 
            if($reminder_email->timesecondemail==0 || $reminder_email->timesecondemail==NULL) $reminder_email->timesecondemail = time();
          }else{
            $reminder_email->timesecondemail = time();
          }
          $DB->update_record('reminder_email', $reminder_email);
        }
      }
      $transaction->allow_commit();
    } else {
      // $DEBUG_RESULTS[] = "From: {$support_user->firstname} {$support_user->lastname} ({$support_user->email})";
      switch ($reminder_type) {
        case FIRST_REMINDER: $reminder_type_txt = 'First Reminder'; 
          break;
        case SECOND_REMINDER: $reminder_type_txt = 'Second Reminder';
          break;
        default: $reminder_type_txt = 'Repeated Reminder';
          break;
      }
      $reminder_type = ($reminder_type==FIRST_REMINDER) ? 'First Reminder' : 'Second Reminder';
      $DEBUG_RESULTS[] = "Type: ".$reminder_type_txt;
      $date_of_sent_out ="";
      foreach ($user->date_of_sent_out as $c_id => $date_sent) {
        $date_of_sent_out = date('d/m/Y',$date_sent);
      }
      $DEBUG_RESULTS[] = "Date of sending out notification: ".$date_of_sent_out;
      $DEBUG_RESULTS[] = "To: <a target='_blank' href='".$CFG->wwwroot."/user/profile.php?id=".$user->userid."'>{$user->firstname} {$user->lastname} ({$user->email})</a>";
      $DEBUG_RESULTS[] = "Subject: $subject";
      if ($user->mailformat === '1') {
        $DEBUG_RESULTS[] = "<div class=\"email-message\">$messagehtml</div>";
      } else {
        $DEBUG_RESULTS[] = "<pre>$messagetext</pre>";
      }
      $DEBUG_RESULTS[] = '';
    }
  }
}

//MINDATLAS WRITE LOG TO TRACK EMAILS SEND TO SITE ADMINISTRATORS
$admin_tracking = $CFG->dataroot."/setdateline_email_to_admins.log";

// Get managers and the users that report to them so we can send only 1 email to the manager including
// all the users that have not completed the course
$managers_users = array();
$site_admins = find_site_admins();
// print_object($site_admins);die();
// echo "<pre>".print_r($manager_reminder_users,true)."</pre>";
foreach ($manager_reminder_users as $user_id => $user) {
  $managers = find_managers($user_id);
  // echo "<pre>".print_r($managers_users,true)."</pre>";
  foreach ($user->reminders as $reminder) {
    if($reminder->manager == '1'){
      foreach ($managers as $manager) {
        if (!isset($managers_users[$manager->id])) {
          $managers_users[$manager->id] = (object) array_intersect_key((array) $manager,
            array_flip(array(
              'id', 'username', 'email', 'firstname', 'lastname', 'maildisplay', 'mailformat',
              'firstnamephonetic', 'lastnamephonetic', 'middlename', 'alternatename',
          )));

          $managers_users[$manager->id]->not_completed_users = array();
        }
        $managers_users[$manager->id]->not_completed_users[$user->userid] = $user;
        if(!isset($managers_users[$manager->id]->date_of_sent_out)) $managers_users[$manager->id]->date_of_sent_out = $user->date_of_sent_out;
        elseif($managers_users[$manager->id]->date_of_sent_out > intval($user->date_of_sent_out)) $managers_users[$manager->id]->date_of_sent_out = $user->date_of_sent_out;
        // echo "<pre>".print_r($user,true)."</pre>";
      }
    }
    // Check if the reminder has site admin setting on
    if ($reminder->siteadmin == '1') {

      $line = "<br>\n".date('Y-m-d H:m').": Reminder admin list: ".$reminder->emailadminlist;
      error_log($line, 3, $admin_tracking);

      $emailadminlist = explode(',',$reminder->emailadminlist);

      if(!empty($site_admins)){
        foreach ($site_admins as $site_admin) {
          //skip if the site admin is not in reminder eamil list
          if ( !in_array($site_admin->id, $emailadminlist)) {
            continue;
          }

          // Get the course information for student that is set up with site admin reminder
          // Because in the not_completed_users array can contain courses that do not need
          // to remind site admin
          if (!isset($managers_users[$site_admin->id])) {
            $managers_users[$site_admin->id] = $site_admin;
            $managers_users[$site_admin->id]->not_completed_users = array();
          }

          if (!isset($managers_users[$site_admin->id]->not_completed_users[$user_id])) {
            $managers_users[$site_admin->id]->not_completed_users[$user->userid] = clone $user;
            $managers_users[$site_admin->id]->not_completed_users[$user->userid]
              ->not_completed_courses = array();
          }

          if (isset($user->not_completed_courses[$reminder->courseid])) {
            $managers_users[$site_admin->id]->not_completed_users[$user->userid]
              ->not_completed_courses[$reminder->courseid] =
                $user->not_completed_courses[$reminder->courseid];
          }
        }
      }//End of site admin loop
    }
  }
}
//SECOND REMINDER WILL BE SENT TO MANAGERS AND/OR SITE ADMINISTRATORS
if ($is_debug) {
  $DEBUG_RESULTS[] = '<h2>Managers and Admins reminder information:</h2>';
}

// echo "<pre>".print_r($managers_users,true)."</pre>";
foreach ($managers_users as $manager_users) {
  $reminder_emails = array();
  $subject = get_string('manager_reminder_email:subject','tool_setdeadline',$SITE->fullname);

  $a = new stdClass();
  $a->firstname = $manager_users->firstname;

  if(is_siteadmin($manager_users->id)){
      $line = "<br>\n".date('Y-m-d H:m').": Site admin in the manager lists: userid: ".$manager_users->id."  - Firstname: ".$manager_users->firstname."  -  Last name: ".$manager_users->lastname;
      error_log($line, 3, $admin_tracking);    
  }

  // print_object($manager_users->not_completed_users);die();
  $userlist= "";
  foreach ($manager_users->not_completed_users as $user) {
    $userlist.="<li>{$user->firstname} {$user->lastname}<ul>";
    foreach ($user->not_completed_courses as $course) {
      $number_of_enrolled_days = intval((time() - intval($course->enrolleddate)) / 86400);
      $userlist .= "<li>{$course->name}, due by " . date('d/m/Y', $course->due_date) . "</li>";
      if (!isset($reminder_emails[$user->userid][$course->id])) {
        if (!isset($reminder_emails[$user->userid])) {
          $reminder_emails[$user->userid] = array();
        }

        $reminder_emails[$user->userid][$course->id] = true;
      }
    }
    $userlist.= "</ul></li>";
  }
  $a->userlist = $userlist;
  $a->support_fullname = trim($support_user->firstname." ".$support_user->lastname);
  $a->support_email = $support_user->email;

  $messagehtml = get_string('manager_reminder_email:body','tool_setdeadline',$a);
  $messagetext = html_to_text($messagehtml);

  if (!$is_debug) {
    // sleep(TIME_WAIT_BEFORE_EMAIL);
    // $mgr_user = $manager_users;
    // email_to_user($mgr_user, $support_user, $subject, $messagetext, $messagehtml);
    $instanceid = rand(1000,100000);
    trackemail_to_userid('reminder_email',$instanceid, $manager_users->id, $subject,$messagehtml);
  } else {
    // $DEBUG_RESULTS[] = "From: {$support_user->firstname} {$support_user->lastname} ({$support_user->email})";
    // $DEBUG_RESULTS[] = "Date of sending out notification: ".date('d/m/Y',$manager_users->date_of_sent_out);  -- This line cause error -- date_of_sent_out is not a defined attribute of manager_users. Manager notification doesn't have a scheduled send time, it's send out immidiatly with second notifications when this script run.
    $DEBUG_RESULTS[] = "To: {$manager_users->firstname} {$manager_users->lastname} ({$manager_users->email})";
    $DEBUG_RESULTS[] = "Subject: $subject";
    if ($manager_users->mailformat === '1') {
      $DEBUG_RESULTS[] = "<div class=\"email-message\">$messagehtml</div>";
    } else {
      $DEBUG_RESULTS[] = "<pre>$messagetext</pre>";
    }
  }
}
?>