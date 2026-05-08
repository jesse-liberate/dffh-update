<?php
// List of observers.
$observers = array(
  array(
    'eventname' => '\core\event\user_enrolment_created',
    'callback' => '\tool_enrolmentemail\eventobservers::user_enrolment_created',
  ),
  array(
    'eventname' => '\core\event\user_enrolment_deleted',
    'callback' => '\tool_enrolmentemail\eventobservers::user_enrolment_deleted',
  ),
  array(
    'eventname' => '\core\event\user_enrolment_updated',
    'callback' => '\tool_enrolmentemail\eventobservers::user_enrolment_updated',
  ),
  array(
    'eventname' => '\core\event\course_created',
    'callback' => '\tool_enrolmentemail\eventobservers::course_created',
  ),
  array(
    'eventname' => '\core\event\course_deleted',
    'callback' => '\tool_enrolmentemail\eventobservers::course_deleted'
  ),
  array(
    'eventname' => '\core\event\user_deleted',
    'callback' => '\tool_enrolmentemail\eventobservers::user_deleted'
  ),
  // Commented out because this will listen to all email_failed event regardless.
//  array(
//    'eventname' => '\core\event\email_failed',
//    'callback' => '\tool_enrolmentemail\eventobservers::email_failed'
//  )
);
