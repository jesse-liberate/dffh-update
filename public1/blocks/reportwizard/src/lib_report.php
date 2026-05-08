<?php

// define('MANUAL_ENROLLMENT', 'manual');
// define('SELF_ENROLLMENT', 'self');
//

require_once($CFG->dirroot . '/blocks/reporting/report/lib.php');

function reportwizard_get_all_sub_categories($categoryid) {
   global $DB;
   return $DB->get_fieldset_sql('SELECT id from {course_categories} where '.$DB->sql_like('path',':path'),['path'=>'/'.$DB->sql_like_escape($categoryid).'/%']);
}