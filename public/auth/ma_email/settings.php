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
 * Admin settings and defaults.
 *
 * @package auth_ma_email
 * @copyright  2017 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    // Introductory explanation.
    $settings->add(new admin_setting_heading('auth_ma_email/pluginname', '',
        new lang_string('auth_ma_emaildescription', 'auth_ma_email')));

    $options = array(
        new lang_string('no'),
        new lang_string('yes'),
    );

    $settings->add(new admin_setting_configselect('auth_ma_email/recaptcha',
        new lang_string('auth_ma_emailrecaptcha_key', 'auth_ma_email'),
        new lang_string('auth_ma_emailrecaptcha', 'auth_ma_email'), 0, $options));

    $setting = new admin_setting_configtextarea(
        'auth_ma_email/allowed_domains',
        'Domains not requiring approval (one domain per line)',
        'Users signing up with these email domains will not require administrator approval. These users will simply need to confirm that they own the email address before their account is activated.',
        ''
    );
    $setting->set_updatedcallback(function () {
        $allowed_domains = get_config('auth_ma_email', 'allowed_domains');
        $allowed_domains = explode("\n", str_replace(["\r\n", "\n\r", "\r"], "\n", $allowed_domains));
        sort($allowed_domains);
        set_config('allowed_domains', implode("\n", $allowed_domains), 'auth_ma_email');
    });
    $settings->add($setting);

    $options = array(
        -1 => 'First admin user',
        -2 => 'All admin users',
    );
    $admins = get_admins();
    foreach ($admins as $admin) {
        $options[$admin->id] = $admin->username;
    }
    $settings->add(new admin_setting_configselect(
        'auth_ma_email/notif_strategy',
        'Notification strategy',
        'Defines the strategy to send the registration notifications. Available options are "first" admin user, "all" admin users or one specific admin user.',
        '-1',
        $options
    ));

    // Display locking / mapping of profile fields.
    $authplugin = get_auth_plugin('ma_email');
    display_auth_lock_options($settings, $authplugin->authtype, $authplugin->userfields,
            get_string('auth_fieldlocks_help', 'auth'), false, false);
}
