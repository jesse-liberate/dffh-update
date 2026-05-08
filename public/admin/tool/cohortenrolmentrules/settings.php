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
 * Add the admin menu link under Site Admin > Users > Accounts
 *
 * @package tool_cohortenrolmentrules
  * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/admin/tool/cohortenrolmentrules/lib.php');

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) { // needs this condition or there is error on login page
    
    $ADMIN->add('accounts',
                new admin_externalpage('toolcohortenrolmentrules',
                    get_string('pluginname', 'tool_cohortenrolmentrules'),
                    "$CFG->wwwroot/$CFG->admin/tool/cohortenrolmentrules/index.php"
                )
    );

    $temp = new admin_settingpage('tool_cohortenrolmentrule_settingpage', new lang_string('automate_rules','tool_cohortenrolmentrules'));
    $ADMIN->add('tools', $temp);

    $fields = get_cohort_rules_all_profile_fields();
    $temp->add(new admin_setting_configselect('tool_cohortenrolmentrules/profilefield',new lang_string('profilefield','tool_cohortenrolmentrules') , new lang_string('setting:profilefield:desc','tool_cohortenrolmentrules'), '', $fields));
}