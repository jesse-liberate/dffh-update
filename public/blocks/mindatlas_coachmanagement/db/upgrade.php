<?php

function xmldb_block_mindatlas_coachmanagement_upgrade($oldversion)
{
    global $DB;

    $dbman = $DB->get_manager();


    if ($oldversion < 2022092000) {

        // Define field courseid to be added to coachmanagement_request.
        $table = new xmldb_table('coachmanagement_request');
        $field = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'userid');

        // Conditionally launch add field courseid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Mindatlas_coachmanagement savepoint reached.
        upgrade_block_savepoint(true, 2022092000, 'mindatlas_coachmanagement');
    }

    return true;
}
