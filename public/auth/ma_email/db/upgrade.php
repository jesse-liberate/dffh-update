<?php
function xmldb_auth_ma_email_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2021060801) {
        upgrade_to_2021060801($dbman);
        $oldversion = 2021060801;
    }
    
    return true;
}

function upgrade_to_2021060801($dbman) {
     // Define table ma_email_records to be created.
    $table = new xmldb_table('ma_email_records');

    // Adding fields to table ma_email_records.
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('adminid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('action', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
    $table->add_field('message', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

    // Adding keys to table ma_email_records.
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    // Adding indexes to table ma_email_records.
    $table->add_index('mdl_userid_ix', XMLDB_INDEX_NOTUNIQUE, array('userid'));
    $table->add_index('mdl_adminid_ix', XMLDB_INDEX_NOTUNIQUE, array('adminid'));

    // Conditionally launch create table for ma_email_records.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    // ma_email savepoint reached.
    upgrade_plugin_savepoint(true, 2021060801, 'auth', 'ma_email');
}