<?php
if ($hassiteconfig) {
    $temp = new admin_settingpage('selfregistration_settingpage', new lang_string('settingpage', 'tool_selfregistration'));
    $ADMIN->add('authsettings', $temp);

    $all_admins = array_map(function ($admin) {
        return fullname($admin) . ' (' . $admin->email . ')';
    }, get_admins());
    $temp->add(new admin_setting_configmulticheckbox(
        'tool_selfregistration/admins_to_get_notif',
        get_string('admins_to_get_notif', 'tool_selfregistration'),
        get_string('admins_to_get_notif:desc', 'tool_selfregistration'),
        $all_admins,
        $all_admins
    ));
}
