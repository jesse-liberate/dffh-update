<?php
/**
 * settings.php
 * Created by Fan Li on 28/01/2021.
 */


use block_training_report\admin_setting_configUserFields;

defined('MOODLE_INTERNAL') || die();
global $CFG;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcolourpicker ('block_training_report/pie_completed_color', 'Pie chart completed color', '', \block_training_report_helper::DEFAULT_PIE_COMPLETED_COLOR));
    $settings->add(new admin_setting_configcolourpicker ('block_training_report/pie_not_completed_color', 'Pie chart not completed color', '', \block_training_report_helper::DEFAULT_PIE_NOT_COMPLETED_COLOR));
    $settings->add(new admin_setting_configcolourpicker ('block_training_report/pie_completed_highlight_color', 'Pie chart completed highlight color', '', \block_training_report_helper::DEFAULT_PIE_COMPLETED_HIGHLIGHT_COLOR));
    $settings->add(new admin_setting_configcolourpicker ('block_training_report/pie_not_completed_highlight_color', 'Pie chart not completed highlight color', '', \block_training_report_helper::DEFAULT_PIE_NOT_COMPLETED_HIGHLIGHT_COLOR));

    $settings->add(new admin_setting_configcolourpicker ('block_training_report/bar_completed_color', 'Bar chart completed color', '', \block_training_report_helper::DEFAULT_BAR_COMPLETED_COLOR));
    $settings->add(new admin_setting_configcolourpicker ('block_training_report/bar_not_completed_color', 'Bar chart not completed color', '', \block_training_report_helper::DEFAULT_BAR_NOT_COMPLETED_COLOR));

    $settings->add(new admin_setting_configcolourpicker ('block_training_report/course_overview_percentage_text_color', 'Course overview percentage text color', '', \block_training_report_helper::DEFAULT_COURSE_OVERVIEW_PERCENTAGE_TEXT_COLOR));
    $settings->add(new admin_setting_configcolourpicker ('block_training_report/course_overview_percentage_background_color', 'Course overview percentage background color', '', \block_training_report_helper::DEFAULT_COURSE_OVERVIEW_PERCENTAGE_BACKGROUND_COLOR));

    $settings->add(new admin_setting_configstoredfile('block_training_report/client_logo',
        'Client logo', '', 'client_logo'));

    require_once("$CFG->dirroot/user/profile/lib.php");
    $custom_fields = profile_get_custom_fields();
    $custom_fields_options = [];
    $custom_fields_info = [];
    foreach ($custom_fields as $field){
        $custom_fields_options[$field->shortname] = $field->name;
        $custom_fields_info[$field->shortname] = $field;
    }

    $default_fields_options = [
        'username' => 'User name',
        'email' => 'Email',
        'country' => 'Country',
        'city'=>'City',
        'lastaccess' => 'Last access'
    ];
    $default_fields_info = [
      'username'=>(object)['datatype'=>'text', 'name'=>'User name'],
      'email'=>(object)['datatype'=>'text', 'name'=>'Email'],
      'country'=>(object)['datatype'=>'menu', 'name'=>'Country'],
      'city'=>(object)['datatype'=>'text', 'name'=>'City'],
      'lastaccess'=>(object)['datatype'=>'datetime', 'name'=>'Last access'],
    ];

    // add "suspended" filed to defualt display fields list
    $default_fields_options_display = $default_fields_options;
    $default_fields_options_display['suspended'] = 'Suspended';
    $default_fields_info_display = $default_fields_info;
    $default_fields_info_display['suspended'] = (object)['datatype'=>'text', 'name'=>'Suspended'];

    $settings->add(new admin_setting_configUserFields('block_training_report/filter_user_default_fields',
        'User default fields for filter', 'You can filter report result by selected fields', [], $default_fields_options, $default_fields_info));
    $settings->add(new admin_setting_configUserFields('block_training_report/display_user_default_fields',
        'User default fields for display', 'Fields selected will show in report', [], $default_fields_options_display, $default_fields_info_display));

    $settings->add(new admin_setting_configUserFields('block_training_report/filter_user_profile_fields',
        'User profile fields for filter', 'You can filter report result by selected fields', [], $custom_fields_options, $custom_fields_info));
    $settings->add(new admin_setting_configUserFields('block_training_report/display_user_profile_fields',
        'User profile fields for display', 'Fields selected will show in report', [], $custom_fields_options, $custom_fields_info));
}