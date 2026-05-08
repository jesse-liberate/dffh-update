<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_tool_setdeadline_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();
    if ($oldversion < 2017121900) {
        //add timecreated in course_reminder
        $table1 = new xmldb_table('course_reminder');
        $field1 = new xmldb_field('timecreated');
        if(!$dbman->field_exists($table1, $field1)){
            $field1->set_attributes(XMLDB_TYPE_INTEGER,'10','XMLDB_UNSIGNED',null,null,null, null);
            $dbman->add_field($table1, $field1);
        }
        //Add timefirstemail and timesecondemail 
        $table2 = new xmldb_table('reminder_email');
        $field2 = new xmldb_field('timefirstemail');
        if(!$dbman->field_exists($table2, $field2)){
            $field2->set_attributes(XMLDB_TYPE_INTEGER,'10','XMLDB_UNSIGNED',null,null,null, null);
            $dbman->add_field($table2, $field2);
        }
        $field3 = new xmldb_field('timesecondemail');
        if(!$dbman->field_exists($table2, $field3)){
            $field3->set_attributes(XMLDB_TYPE_INTEGER,'10','XMLDB_UNSIGNED',null,null,null, null);
            $dbman->add_field($table2, $field3);
        }
        upgrade_plugin_savepoint(true, 2017121900,'tool', 'setdeadline');
    }
    if ($oldversion < 2017121905) {
        $table1 = new xmldb_table('course_reminder');
        $field1 = new xmldb_field('repeated');
        if(!$dbman->field_exists($table1, $field1)){
            $field1->set_attributes(XMLDB_TYPE_INTEGER,'1','XMLDB_UNSIGNED',null,null,null, null);
            $dbman->add_field($table1, $field1);
        }
        upgrade_plugin_savepoint(true, 2017121905,'tool', 'setdeadline');
    }

    if ($oldversion < 2018012901) {

        // Define field emailadminlist to be added to course_reminder.
        $table = new xmldb_table('course_reminder');
        $field = new xmldb_field('emailadminlist', XMLDB_TYPE_CHAR, '1024', null, null, null, null, 'siteadmin');

        // Conditionally launch add field emailadminlist.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Setdeadline savepoint reached.
        upgrade_plugin_savepoint(true, 2018012901, 'tool', 'setdeadline');
    }

    if ($oldversion < 2018062600) {
        $table = new xmldb_table('course_reminder');
        $courseid_field = new xmldb_field('courseid');

        if ($dbman->field_exists($table, $courseid_field)) {
            $dbman->drop_field($table, $courseid_field);
        }

        // Define table reminder_assign to be created.
        $table = new xmldb_table('reminder_assign');

        // Adding fields to table reminder_assign.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('reminder_id', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('instanceid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);

        // Adding keys to table reminder_assign.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for reminder_assign.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2018062600, 'tool', 'setdeadline');
    }

    if ($oldversion < 2019031301) {

      // Define table reminder_overrides to be dropped.
      $table = new xmldb_table('reminder_overrides');

      // Conditionally launch drop table for reminder_overrides.
      if ($dbman->table_exists($table)) {
          $dbman->drop_table($table);
      }
      
      // Define table reminder_overrides to be created.
      $table = new xmldb_table('reminder_override');

      // Adding fields to table reminder_override.
      $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
      $table->add_field('reminder_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
      $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
      $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
      $table->add_field('firstreminder', XMLDB_TYPE_INTEGER, '20', null, null, null, null);

      // Adding keys to table reminder_override.
      $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

      // Conditionally launch create table for reminder_override.
      if (!$dbman->table_exists($table)) {
          $dbman->create_table($table);
      }

      // Setdeadline savepoint reached.
      upgrade_plugin_savepoint(true, 2019031301, 'tool', 'setdeadline');
    }
    
    if ($oldversion < 2019031302) {

      // Define field sentstatus to be added to reminder_override.
      $table = new xmldb_table('reminder_override');
      $field = new xmldb_field('sentstatus', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'firstreminder');

      // Conditionally launch add field sentstatus.
      if (!$dbman->field_exists($table, $field)) {
          $dbman->add_field($table, $field);
      }

      $field = new xmldb_field('secondreminder');

      // Conditionally launch drop field secondreminder.
      if ($dbman->field_exists($table, $field)) {
          $dbman->drop_field($table, $field);
      }
      
      // Setdeadline savepoint reached.
      upgrade_plugin_savepoint(true, 2019031302, 'tool', 'setdeadline');
    }
    //Eric - fix issue with the user,cohort enhancement
    if ($oldversion < 2019032100) {
      // Define field sentstatus to be added to reminder_override.
      $table = new xmldb_table('course_reminder');
      $field1 = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
      // Conditionally launch add field sentstatus.
      if (!$dbman->field_exists($table, $field1)) {
          $dbman->add_field($table, $field1);
      }
      $field2 = new xmldb_field('type', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
      // Conditionally launch add field sentstatus.
      if (!$dbman->field_exists($table, $field2)) {
          $dbman->add_field($table, $field2);
      }
      $field3 = new xmldb_field('instanceid', XMLDB_TYPE_CHAR, '128', null, null, null, null);
      // Conditionally launch add field sentstatus.
      if (!$dbman->field_exists($table, $field3)) {
          $dbman->add_field($table, $field3);
      }

      //Remove the uncessary table
      $tableremove = new xmldb_table('reminder_assign');
      if ($dbman->table_exists($tableremove)) {
          $dbman->drop_table($tableremove);
      }
      // Setdeadline savepoint reached.
      upgrade_plugin_savepoint(true, 2019032100, 'tool', 'setdeadline');
    }

    if ($oldversion < 2019100700) {
        tool_setdeadline_upgrade_to_2019100700($dbman);
    }

    //Allow managers to setup deadline for team
    if ($oldversion < 2020100700) {
      $table = new xmldb_table('course_reminder_mgr');

      // Adding fields to table reminder_override.
      $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
      $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
      $table->add_field('firstreminder', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
      $table->add_field('secondreminder', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
      $table->add_field('managerid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
      $table->add_field('sitecode', XMLDB_TYPE_CHAR, '128', null, XMLDB_NOTNULL, null, null);
      $table->add_field('repeated', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
      $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
      $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
      
      // Adding keys to table course_reminder_mgr.
      $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

      // Conditionally launch create table for course_reminder_mgr.
      if (!$dbman->table_exists($table)) {
          $dbman->create_table($table);
      }
      upgrade_plugin_savepoint(true, 2020100700, 'tool', 'setdeadline');
    }    


    return true;
}

function tool_setdeadline_upgrade_to_2019100700($dbman) {
    $table = new xmldb_table('course_reminder');
    // Define field timemodified to be added to course_reminder.
    $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'timecreated');

    // Conditionally launch add field timemodified.
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    $field = new xmldb_field('modifiedby', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'timemodified');

    // Conditionally launch add field modifiedby.
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }
    
    // Setdeadline savepoint reached.
    upgrade_plugin_savepoint(true, 2019100700, 'tool', 'setdeadline');
}
