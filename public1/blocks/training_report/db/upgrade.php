<?php

function xmldb_block_training_report_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2021012000) {

        // Define table report_completion to be created.
        $table = new xmldb_table('report_completion');

        // Adding fields to table report_completion.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('course_category_id', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('course_category_name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('course_id', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('course_name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('enrolment_types', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('enrolment_startdate', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('enrolment_is_active', XMLDB_TYPE_CHAR, '1', null, null, null, '0');
        $table->add_field('course_module_id', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('module_name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('course_module_instance_id', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('course_module_instance_name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('completion_id', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('completion_state', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('completion_date', XMLDB_TYPE_INTEGER, '20', null, null, null, null);

        // Adding keys to table report_completion.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table report_completion.
        $table->add_index('user_id_ix', XMLDB_INDEX_NOTUNIQUE, array('user_id'));
        $table->add_index('course_category_id_ix', XMLDB_INDEX_NOTUNIQUE, array('course_category_id'));
        $table->add_index('course_id_ix', XMLDB_INDEX_NOTUNIQUE, array('course_id'));
        $table->add_index('enrolment_startdate_ix', XMLDB_INDEX_NOTUNIQUE, array('enrolment_startdate'));
        $table->add_index('course_module_id_ix', XMLDB_INDEX_NOTUNIQUE, array('course_module_id'));
        $table->add_index('course_module_instance_id_ix', XMLDB_INDEX_NOTUNIQUE, array('course_module_instance_id'));
        $table->add_index('completion_id_ix', XMLDB_INDEX_NOTUNIQUE, array('completion_id'));
        $table->add_index('completion_date_ix', XMLDB_INDEX_NOTUNIQUE, array('completion_date'));

        // Conditionally launch create table for report_completion.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Training_report savepoint reached.
        upgrade_block_savepoint(true, 2021012000, 'training_report');
    }

    return true;
}
