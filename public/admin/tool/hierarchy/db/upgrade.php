<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * DB authentication plugin upgrade code
 *
 * @package    tool_hierarchy
 * @copyright  2022 Khang Cao
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Function to upgrade tool_hierarchy.
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_tool_hierarchy_upgrade($oldversion)
{
    global $CFG, $DB;

    if ($oldversion < 2022072702) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('hierarchy_user_parent_data');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('position_code', XMLDB_TYPE_CHAR, '128', null, null, null, null);
        $table->add_field('position_level', XMLDB_TYPE_CHAR, '128', null, null, null, null);
        $table->add_field('position_title', XMLDB_TYPE_CHAR, '128', null, null, null, null);
    
        // Adding keys to table coursepoints_levelup_popup.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        // Conditionally launch add field timetopped.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2022072702, 'tool', 'hierarchy');
        $oldversion = 2022072702;
    }

     if ($oldversion < 2023012701) {
        $dbman = $DB->get_manager();
        // Define table hierarchy_coach to be created.
        $table = new xmldb_table('hierarchy_coach');

        // Adding fields to table hierarchy_coach.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('node_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table hierarchy_coach.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for hierarchy_coach.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Hierarchy savepoint reached.
        upgrade_plugin_savepoint(true, 2023012701, 'tool', 'hierarchy');
    }

    return true;
}
