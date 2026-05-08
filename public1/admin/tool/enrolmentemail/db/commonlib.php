<?php

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../constants.php');
require_once(__DIR__ . '/../locallib.php');

function set_default_config() {
  // Set default config
  set_local_config(ENROLMENTEMAIL_INITIALCOURSENOTIFICATION, ENROLMENTEMAIL_COURSENOTIFICATION_ENABLED);
  set_local_config(ENROLMENTEMAIL_MAXATTEMPTSALLOWED, ENROLMENTEMAIL_MAXATTEMPTS);
  $subjectline = '{$sitename}: ' . get_string('course') . ' enrolment notification';
  set_local_config(ENROLMENTEMAIL_EMAILSUBJECTLINE, $subjectline);
  set_local_config(ENROLMENTEMAIL_EMAILSIGNATURENAME, '');
  set_local_config(ENROLMENTEMAIL_EMAILCONTACT, '');
  set_local_config(ENROLMENTEMAIL_EMAILCONTENT, '');
}

function populate_courselist() {
  global $DB;

  // Set course notification for each course to enabled
  global $DB;
  $courses = $DB->get_records('course', null, '', 'id');
  $transaction = $DB->start_delegated_transaction();
  if ($courses) {
    foreach ($courses as $id => $course) {
      $record = new stdClass();
      $record->courseid = $id;
      $record->enabled = ENROLMENTEMAIL_COURSENOTIFICATION_ENABLED;
      $record->timecreated = time();
      $DB->insert_record('enrolmentemail_courses', $record);
    }
  }

  $transaction->allow_commit();
}