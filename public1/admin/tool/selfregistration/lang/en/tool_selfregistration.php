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
 * Strings for component 'tool_selfregistration', language 'en'
 *
 * @package   tool_selfregistration
 * @copyright Daniel Neis <danielneis@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'self-registration';

$string['self_registration_users_notification_email'] = 'Dear {$a->user_firstname},

This is to notify you the self-registered users from {$a->from_date} to {$a->to_date}.

******************DETAILS********************

{$a->main_content}

Kind regards,
{$a->from}';

$string['settingpage'] = 'Self-registration settings';
$string['admins_to_get_notif'] = 'Administrators to get email notifications';
$string['admins_to_get_notif:desc'] = 'Administrators to get email notifications for new users who self-registered';
