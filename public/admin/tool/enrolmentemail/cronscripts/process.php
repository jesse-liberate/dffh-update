<?php

/**
 * Overview
 * 1. Fetch configuration data. e.g. subjectline, signaturename, content
 * 2. Initialise email manager
 * 3. Fetch data from queue
 * 4. Send out notifications
 * 5. Mark notification where course has been completed by user
 * 6. Mark remaining notification with corresponding status. e.g. user suspended, course deleted
 */
define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../constants.php');
require_once(__DIR__ . '/../locallib.php');
require_once(__DIR__ . '/../classes/queue.php');
require_once(__DIR__ . '/../classes/emailmanager.php');

use tool_enrolmentemail\emailmanager;
use tool_enrolmentemail\queue;

// 1. Fetch configuration data
$subjectline = get_local_config(ENROLMENTEMAIL_EMAILSUBJECTLINE);
$support_user = \core_user::get_support_user();
$support_name = fullname($support_user);
if (!($signaturename = get_local_config(ENROLMENTEMAIL_EMAILSIGNATURENAME))) {
  $signaturename = $support_name;
}
if (!($contact = get_local_config(ENROLMENTEMAIL_EMAILCONTACT))) {
  $contact = $support_user->email;
}
if (!($content = get_local_config(ENROLMENTEMAIL_EMAILCONTENT))) {
  $content = get_string('defaulttemplatecontent', ENROLMENTEMAIL_PLUGINNAME);
}
$maxattempts = get_local_config(ENROLMENTEMAIL_MAXATTEMPTSALLOWED);

// 2. Initialize email manager
$placeholders = array(
  'sitelink' => get_sitelink(),
  'subjectline' => $subjectline,
  'signaturename' => $signaturename,
  'contact' => $contact,
  'content' => $content,
  'sitename' => $SITE->fullname
);
$emailmanager = new emailmanager($subjectline, $signaturename, $contact, $content, $placeholders);
$emailmanager->set_sender($support_user);

// 3. Fetch notification data for email
$usercourses = array();
$queue = new queue();

// 4. Increment attempts by one for each QUEUED notification
$all_queued_notifications = $queue->fetch_queued_notifications();
foreach ($all_queued_notifications as $id => $queued_notification) {
  $queue->increment_attempts_by_one($queued_notification);
}

$transaction = $DB->start_delegated_transaction();

$data = $queue->fetch_data_for_email();

// error_log(var_export($data, true));

// 5. Send out notifications
if (!empty($data)) {
  // fetch all users data
  $users = $DB->get_records('user', array('deleted' => 0, 'suspended' => 0));
  foreach ($data as $userid => $courseids) {
    if (isset($users[$userid])) {
      $user = $users[$userid];
      $courselist = get_courselist($courseids);
      $userprofilelink = get_userprofilelink($user);
      $emailmanager->set_placeholder('courselist', $courselist);
      $emailmanager->set_placeholder('firstname', $user->firstname);
      $emailmanager->set_placeholder('lastname', $user->lastname);
      $emailmanager->set_placeholder('userprofilelink', $userprofilelink);
      $emailmanager->set_receiver($user);
      $emailmanager->fill_in();
      $emailmanager->send();
      $queue->mark_notificationsent($userid, $courseids);
    }
  }
}

// 6. Mark any notification that has made more than 10 attempts with ARCHIVED status
$all_queued_notifications = $queue->fetch_queued_notifications();
foreach ($all_queued_notifications as $id => $queued_notification) {
  $queue->mark_notificationarchived($queued_notification, $maxattempts);
}
$transaction->allow_commit();

// 5. Mark notification where course has been completed by user
// if (!empty($completed)) {
//   $queue->mark_coursecompleted($completed);
// }

// 6. Mark remaining notification with corresponding status. e.g. user suspended, course deleted
// $queue->update_queued_records_status();

