<?php

require_once(__DIR__ . '/commonlib.php');

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function xmldb_tool_enrolmentemail_upgrade($oldversion) {
  global $DB;

  $dbman = $DB->get_manager();
  
  if ($oldversion < 2019091200) {
    upgrade_plugin_savepoint(true, 2019091200, 'tool', 'enrolmentemail');
    $oldversion = 2019091200;
  }

  if ($oldversion < 2019091600) {
    tool_enrolmentemail_upgrade_to_2019091600($dbman);
    $oldversion = 2019091600;
  }

  if ($oldversion < 2020052400) {
    tool_enrolmentemail_upgrade_to_2020052400($dbman);
    $oldversion = 2020052400;
  }

  if ($oldversion < 2020060500) {
    tool_enrolmentemail_upgrade_to_2020060500($dbman);
    $oldversion = 2020060500;
  }

  if ($oldversion < 2020060501) {
    tool_enrolmentemail_upgrade_to_2020060501($dbman);
    $oldversion = 2020060501;
  } 
  return true;
}

function tool_enrolmentemail_upgrade_to_2019091600($dbman) {

  $table = new xmldb_table('enrolmentemail_queue');

  // Define field timecreated to be added to enrolmentemail_queue.  
  $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'status');

  // Conditionally launch add field timecreated.
  if (!$dbman->field_exists($table, $field)) {
    $dbman->add_field($table, $field);
  }

  // Define field timemodified to be added to enrolmentemail_queue.
  $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'timecreated');

  // Conditionally launch add field timemodified.
  if (!$dbman->field_exists($table, $field)) {
    $dbman->add_field($table, $field);
  }

  // Define field log_msg to be added to enrolmentemail_queue.
  $field = new xmldb_field('log_msg', XMLDB_TYPE_TEXT, null, null, null, null, null, 'timemodified');

  // Conditionally launch add field log_msg.
  if (!$dbman->field_exists($table, $field)) {
    $dbman->add_field($table, $field);
  }

  // Enrolmentemail savepoint reached.
  upgrade_plugin_savepoint(true, 2019091600, 'tool', 'enrolmentemail');
}

function tool_enrolmentemail_upgrade_to_2020052400($dbman) {

  // Define table enrolmentemail_courses to be created.
  $table = new xmldb_table('enrolmentemail_courses');

  // Adding fields to table enrolmentemail_courses.
  $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
  $table->add_field('courseid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
  $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
  $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
  $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '20', null, null, null, null);

  // Adding keys to table enrolmentemail_courses.
  $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

  // Conditionally launch create table for enrolmentemail_courses.
  if (!$dbman->table_exists($table)) {
    $dbman->create_table($table);
  }

  // Enrolmentemail savepoint reached.
  upgrade_plugin_savepoint(true, 2020052400, 'tool', 'enrolmentemail');
}

function tool_enrolmentemail_upgrade_to_2020060500($dbman) {
  // Define field attempts to be added to enrolmentemail_queue.
  $table = new xmldb_table('enrolmentemail_queue');
  $field = new xmldb_field('attempts', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'status');

  // Conditionally launch add field attempts.
  if (!$dbman->field_exists($table, $field)) {
    $dbman->add_field($table, $field);
  }

  // Define field batchnum to be added to enrolmentemail_queue.
  $table = new xmldb_table('enrolmentemail_queue');
  $field = new xmldb_field('batchnum', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'attempts');

  // Conditionally launch add field batchnum.
  if (!$dbman->field_exists($table, $field)) {
    $dbman->add_field($table, $field);
  }

  // Enrolmentemail savepoint reached.
  upgrade_plugin_savepoint(true, 2020060500, 'tool', 'enrolmentemail');
}

function tool_enrolmentemail_upgrade_to_2020060501($dbman) {
  // Rename field last_log_msg on table enrolmentemail_queue to NEWNAMEGOESHERE.
  $table = new xmldb_table('enrolmentemail_queue');
  $field = new xmldb_field('log_msg', XMLDB_TYPE_TEXT, null, null, null, null, null, 'timemodified');

  // Launch rename field last_log_msg.
  $dbman->rename_field($table, $field, 'last_log_msg');

  // Enrolmentemail savepoint reached.
  upgrade_plugin_savepoint(true, 2020060501, 'tool', 'enrolmentemail');
}
