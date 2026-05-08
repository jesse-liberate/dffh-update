<?php

namespace tool_selfregistration\task;

class self_registration_users_notification_email extends \core\task\scheduled_task {
    public function get_name() {
        //Shown in admin screens
        return "Self-registration user’s summary email notification";

        $last_run = get_config('tool_selfregistration', 'self_registration_users_notification_email_last_run');

        $today = strtotime('today midnight');
        if (!(!empty($last_run) && $last_run <= strtotime("-14 day", $today))) {
            return;
        }
        $from_time = empty($last_run) ? 0 : $last_run;
        $email_params['from_date'] = empty($last_run) ? "Project created" : date("F j, Y, g:i a", $from_time);
    }

    public function execute() {
        global $CFG, $DB, $PAGE;

        $last_run = get_config('tool_selfregistration', 'self_registration_users_notification_email_last_run');

        $today = strtotime('today midnight');
        if (!(empty($last_run) || $last_run <= strtotime("-14 day", $today))) {
            return;
        }
        $from_time = empty($last_run) ? 0 : $last_run;
        $email_params['from_date'] = empty($last_run) ? "Project created" : date("F j, Y, g:i a", $from_time);
        $email_params['to_date'] = date("F j, Y, g:i a", $today);

        $signup_methods = $this->get_plugins_allowing_signup();
        list($signup_query, $sql_params) = $DB->get_in_or_equal($signup_methods);

        $org_record = $DB->get_record('user_info_field', array('shortname' => 'OrganisationOrAgency'), 'id');
        $org_id = $org_record->id;
        $role_record = $DB->get_record('user_info_field', array('shortname' => 'RoleOrPosition'), 'id');
        $role_id = $role_record->id;

        $sql =
        "   SELECT u.*, organisation_info.data as organisation, role_info.data as `role`    
            FROM {user} u
            LEFT JOIN {user_info_data} organisation_info
                  ON u.id = organisation_info.userid AND organisation_info.fieldid = " . $org_id . "
            LEFT JOIN {user_info_data} role_info
                  ON u.id = role_info.userid AND role_info.fieldid = " . $role_id . "
            where u.auth " . $signup_query . " and u.timecreated >= ? and u.timecreated < ?
            AND u.deleted = 0
            order by u.timecreated
            ";
        $sql_params[] = $from_time;
        $sql_params[] = $today;
        $new_users = $DB->get_records_sql($sql, $sql_params);

        if (empty($new_users)) {
            set_config('self_registration_users_notification_email_last_run', $today, 'tool_selfregistration');
            return;
        }

        $PAGE->set_context(null);
        $from  = \core_user::get_support_user();
        $from->mailformat = 1;
        $email_params['from'] = $from->firstname . ' ' . $from->lastname;
        $email_params['main_content'] = '';
        $email_params['main_content'] .= "<table>" .
            "<thead>" .
            "<tr>" .
            // "<th>User Name</th>" .
            "<th>First Name</th>" .
            "<th>Last Name</th>" .
            "<th>Email</th>" .
            "<th>Organisation or Agency</th>" .
            "<th>Role/Position titles</th>" .
            "<th>Confirmed</th>" .
            "<th>Registered Date</th>" .
            "</tr>" .
            "</thead>";


        foreach ($new_users as $user) {
            $confirmed = $user->confirmed ? 'Yes' : 'No';
            $email_params['main_content'] .= "<tr>" .
                // "<td>$user->username</td>" .
                "<td>$user->firstname</td>" .
                "<td>$user->lastname</td>" .
                "<td>$user->email</td>" .
                "<td>$user->organisation</td>" .
                "<td>$user->role</td>" .
                "<td>$confirmed</td>" .
                "<td>" . date("F j, Y, g:i a", $user->timecreated) . "</td>" .
                "</tr>";
        }
        $email_params['main_content'] .= "</table>";

        $admins = get_admins();
        $admins_to_get_notif = get_config('tool_selfregistration', 'admins_to_get_notif');
        if ($admins_to_get_notif === false) {
            $admins_to_get_notif = get_admins();
        } else {
            $admins_to_get_notif = array_flip(explode(',', $admins_to_get_notif));
        }
        foreach ($admins as $admin) {
            if (!array_key_exists($admin->id, $admins_to_get_notif)) {
                continue;
            }
            $admin->mailformat = FORMAT_HTML;
            $email_params['user_firstname'] = $admin->firstname;

            $content = get_string('self_registration_users_notification_email', 'tool_selfregistration', $email_params);
            $subject = 'Self-registration user’s summary';

            ob_start();
            include dirname(__FILE__) . '/email_template/self_registration_users_notification_email.html';
            $message = ob_get_clean();
            $txt_body = html_to_text($message);

            $try_times = 3;
            while ($try_times-- > 0 && !email_to_user($admin, $from, $subject, $txt_body, $message));
        }
        set_config('self_registration_users_notification_email_last_run', $today, 'tool_selfregistration');
    }

    function get_plugins_allowing_signup() {
        $auth_plugins = get_enabled_auth_plugins();
        $plugins_allowing_signup = [];
        foreach ($auth_plugins as $auth_plugin) {
            $plugin = get_auth_plugin($auth_plugin);
            if ($plugin->can_signup()) {
                $plugins_allowing_signup[] = $auth_plugin;
            }
        }
        return $plugins_allowing_signup;
    }
}
