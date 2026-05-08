<?php

function xmldb_block_reportwizard_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2019012200) {
        // Changing nullability of field object_type on table report_wzd_report to null.
        $table = new xmldb_table('report_wzd_report');
        $field = new xmldb_field('object_type', XMLDB_TYPE_INTEGER, '2', null, null, null, null, 'type');

        // Launch change of nullability for field object_type.
        $dbman->change_field_notnull($table, $field);

        // Reportwizard savepoint reached.
        upgrade_block_savepoint(true, 2019012200, 'reportwizard');
    }
    if($oldversion < 2020111300){
        $table = new xmldb_table('report_wzd_schedule');

        // Adding fields to table conference_history.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('report_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('report_format', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cohortid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('frequency', XMLDB_TYPE_CHAR, '128', null, null, null, null);
        $table->add_field('startdate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('lastrun', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('nextrun', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '20', null, null, null, null);

        // Adding keys to table conference_history.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for conference_history.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
    }

    return true;
}
