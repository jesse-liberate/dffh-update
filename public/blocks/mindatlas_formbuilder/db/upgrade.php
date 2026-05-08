<?php
function xmldb_block_mindatlas_formbuilder_upgrade($oldversion)
{
    if ($oldversion < 2022092600) {
        global $DB;

        $dbman = $DB->get_manager();
        // Define field timeupdated to be added to formbuilder_form.
        $table = new xmldb_table('formbuilder_form');
        $field = new xmldb_field('timeupdated', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timecreated');

        // Conditionally launch add field timeupdated.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Mindatlas_formbuilder savepoint reached.
        upgrade_block_savepoint(true, 2022092600, 'mindatlas_formbuilder');
    }

    if ($oldversion < 2022100200) {
        global $DB;

        $dbman = $DB->get_manager();
        // Define field timeupdated to be added to formbuilder_form.
        $table = new xmldb_table('formbuilder_form_info_data');
        $field = new xmldb_field('formid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, null);

        // Conditionally launch add field timeupdated.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Mindatlas_formbuilder savepoint reached.
        upgrade_block_savepoint(true, 2022100200, 'mindatlas_formbuilder');
    }
    return true;
}
