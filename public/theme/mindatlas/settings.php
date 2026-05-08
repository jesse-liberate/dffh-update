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
 * @package   theme_mindatlas
 * @copyright 2016 Damyon Wiese
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This line protects the file from being accessed by a URL directly.
defined('MOODLE_INTERNAL') || die();

// This is used for performance, we don't need to know about these settings on every page in Moodle, only when
// we are looking at the admin settings pages.
if ($ADMIN->fulltree) {

    // Boost provides a nice setting page which splits settings onto separate tabs. We want to use it here.
    $settings = new theme_boost_admin_settingspage_tabs('themesettingmindatlas', get_string('configtitle', 'theme_mindatlas'));

    // ============================================
    // Tab: General settings
    // Each page is a tab - the first is the "General" tab.
    $page = new admin_settingpage('theme_mindatlas_general', get_string('generalsettings', 'theme_mindatlas'));

    // site logo
    $name = 'theme_mindatlas/logo';
    $title = get_string('logo', 'theme_mindatlas');
    $description = get_string('logodesc', 'theme_mindatlas');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logo');
    $setting->set_updatedcallback('theme_mindatlas_update_settings_images');
    $page->add($setting);

    // Variable $color-brand.
    // We use an empty default value because the default colour should come from the preset.
    $name = 'theme_mindatlas/primarycolor';
    $title = get_string('primarycolor', 'theme_mindatlas');
    $description = get_string('primarycolor_desc', 'theme_mindatlas');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_mindatlas/brandcolor1';
    $title = get_string('brandcolor1', 'theme_mindatlas');
    $description = get_string('brandcolor_desc1', 'theme_mindatlas');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#1177d1');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_mindatlas/brandcolor2';
    $title = get_string('brandcolor2', 'theme_mindatlas');
    $description = get_string('brandcolor_desc2', 'theme_mindatlas');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#2EA9DA');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_mindatlas/brandcolor3';
    $title = get_string('brandcolor3', 'theme_mindatlas');
    $description = get_string('brandcolor_desc3', 'theme_mindatlas');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#4CD4E2');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // $name = 'theme_mindatlas/brandcolor4';
    // $title = get_string('brandcolor4', 'theme_mindatlas');
    // $description = get_string('brandcolor_desc4', 'theme_mindatlas');
    // $setting = new admin_setting_configcolourpicker($name, $title, $description, '#6AE9DE');
    // $setting->set_updatedcallback('theme_reset_all_caches');
    // $page->add($setting);

    // Collection statement
    $name = 'theme_mindatlas/collection_statement';
    $title = get_string('collection_statement', 'theme_mindatlas');
    $description = get_string('collection_statement-desc', 'theme_mindatlas');
    $setting = new admin_setting_confightmleditor($name, $title, $description, '');
    $page->add($setting);

    // Must add the page after defining all the settings!
    $settings->add($page);

    // ============================================
    // Tab: Login settings
    $page = new admin_settingpage('theme_mindatlas_loginsettings', get_string('loginsettings', 'theme_mindatlas'));

    // Login page background setting.
    // We use variables for readability.
    $name = 'theme_mindatlas/loginbackgroundimage';
    $title = get_string('loginbackgroundimage', 'theme_mindatlas');
    $description = get_string('loginbackgroundimage_desc', 'theme_mindatlas');
    // This creates the new setting.
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'loginbackgroundimage');
    // This function will copy the image into the data_root location it can be served from.
    $setting->set_updatedcallback('theme_mindatlas_update_settings_images');
    // We always have to add the setting to a page for it to have any effect.



    $page->add($setting);

    $name = 'theme_mindatlas/logincarouselimage1';
    $title = get_string('logincarouselimage1', 'theme_mindatlas');
    $description = get_string('logincarouselimage1_desc', 'theme_mindatlas');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logincarouselimage1');
    $setting->set_updatedcallback('theme_mindatlas_update_settings_images');
    $page->add($setting);

    $name = 'theme_mindatlas/logincarouselimage2';
    $title = get_string('logincarouselimage2', 'theme_mindatlas');
    $description = get_string('logincarouselimage2_desc', 'theme_mindatlas');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logincarouselimage2');
    $setting->set_updatedcallback('theme_mindatlas_update_settings_images');
    $page->add($setting);

    $name = 'theme_mindatlas/logincarouselimage3';
    $title = get_string('logincarouselimage3', 'theme_mindatlas');
    $description = get_string('logincarouselimage3_desc', 'theme_mindatlas');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logincarouselimage3');
    $setting->set_updatedcallback('theme_mindatlas_update_settings_images');
    $page->add($setting);

    $name = 'theme_mindatlas/logincarouselimage4';
    $title = get_string('logincarouselimage4', 'theme_mindatlas');
    $description = get_string('logincarouselimage4_desc', 'theme_mindatlas');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logincarouselimage4');
    $setting->set_updatedcallback('theme_mindatlas_update_settings_images');
    $page->add($setting);

    // Must add the page after defining all the settings!
    $settings->add($page);

    // ============================================
    // Tab: Home page
    $page = new admin_settingpage('theme_mindatlas_homepage', get_string('homepagesettings', 'theme_mindatlas'));

    $name = 'theme_mindatlas/introbg';
    $title = get_string('introbg', 'theme_mindatlas');
    $description = get_string('introbg_desc', 'theme_mindatlas');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'introbg');
    $setting->set_updatedcallback('theme_mindatlas_update_settings_images');
    $page->add($setting);

    // banner image
    $name = 'theme_mindatlas/banner1_img';
    $title = get_string('banner1_img', 'theme_mindatlas');
    $description = get_string('banner1_img_desc', 'theme_mindatlas');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'banner1_img');
    $setting->set_updatedcallback('theme_mindatlas_update_settings_images');
    $page->add($setting);
    // banner title
    $setting = new admin_setting_configtext('theme_mindatlas/banner1_title', get_string('banner1_title', 'theme_mindatlas'), get_string('banner1_img_desc', 'theme_mindatlas'), '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // banner text
    $setting = new admin_setting_configtext('theme_mindatlas/banner1_text', get_string('banner1_text', 'theme_mindatlas'), get_string('banner1_text_desc', 'theme_mindatlas'), '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // banner btn
    $setting = new admin_setting_configtext('theme_mindatlas/banner1_btn', get_string('banner1_btn', 'theme_mindatlas'), get_string('banner1_btn_desc', 'theme_mindatlas'), '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // banner link
    $setting = new admin_setting_configtext('theme_mindatlas/banner1_link', get_string('banner1_link', 'theme_mindatlas'), get_string('banner1_link_desc', 'theme_mindatlas'), '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // welcome title
    $setting = new admin_setting_configtext('theme_mindatlas/welcome_title', get_string('welcome_title', 'theme_mindatlas'), get_string('welcome_title_desc', 'theme_mindatlas'), '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // welcome text
    $setting = new admin_setting_confightmleditor('theme_mindatlas/welcome_text', get_string('welcome_text', 'theme_mindatlas'), get_string('welcome_text_desc', 'theme_mindatlas'), '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // welcome text 2
    $setting = new admin_setting_confightmleditor('theme_mindatlas/welcome_text_2', get_string('welcome_text_2', 'theme_mindatlas'), get_string('welcome_text_2_desc', 'theme_mindatlas'), '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);


    $settings->add($page);
    // -- end of page --

    // ============================================
    // Tab: Backgrounds
    // $page = new admin_settingpage('theme_mindatlas_backgrounds', get_string('backgrounds', 'theme_mindatlas'));

    // // Default background setting.
    // // We use variables for readability.
    // $name = 'theme_mindatlas/defaultbackgroundimage';
    // $title = get_string('defaultbackgroundimage', 'theme_mindatlas');
    // $description = get_string('defaultbackgroundimage_desc', 'theme_mindatlas');
    // // This creates the new setting.
    // $setting = new admin_setting_configstoredfile($name, $title, $description, 'defaultbackgroundimage');
    // // This function will copy the image into the data_root location it can be served from.
    // $setting->set_updatedcallback('theme_mindatlas_update_settings_images');
    // // We always have to add the setting to a page for it to have any effect.
    // $page->add($setting);

    // // Frontpage page background setting.
    // // We use variables for readability.
    // $name = 'theme_mindatlas/frontpagebackgroundimage';
    // $title = get_string('frontpagebackgroundimage', 'theme_mindatlas');
    // $description = get_string('frontpagebackgroundimage_desc', 'theme_mindatlas');
    // // This creates the new setting.
    // $setting = new admin_setting_configstoredfile($name, $title, $description, 'frontpagebackgroundimage');
    // // This function will copy the image into the data_root location it can be served from.
    // $setting->set_updatedcallback('theme_mindatlas_update_settings_images');
    // // We always have to add the setting to a page for it to have any effect.
    // $page->add($setting);

    // // Dashboard page background setting.
    // // We use variables for readability.
    // $name = 'theme_mindatlas/dashboardbackgroundimage';
    // $title = get_string('dashboardbackgroundimage', 'theme_mindatlas');
    // $description = get_string('dashboardbackgroundimage_desc', 'theme_mindatlas');
    // // This creates the new setting.
    // $setting = new admin_setting_configstoredfile($name, $title, $description, 'dashboardbackgroundimage');
    // // This function will copy the image into the data_root location it can be served from.
    // $setting->set_updatedcallback('theme_mindatlas_update_settings_images');
    // // We always have to add the setting to a page for it to have any effect.
    // $page->add($setting);

    // // In course page background setting.
    // // We use variables for readability.
    // $name = 'theme_mindatlas/incoursebackgroundimage';
    // $title = get_string('incoursebackgroundimage', 'theme_mindatlas');
    // $description = get_string('incoursebackgroundimage_desc', 'theme_mindatlas');
    // // This creates the new setting.
    // $setting = new admin_setting_configstoredfile($name, $title, $description, 'incoursebackgroundimage');
    // // This function will copy the image into the data_root location it can be served from.
    // $setting->set_updatedcallback('theme_mindatlas_update_settings_images');
    // // We always have to add the setting to a page for it to have any effect.
    // $page->add($setting);

    // // Must add the page after defining all the settings!
    // $settings->add($page);


    // ============================================
    // Tab: Advanced settings
    $page = new admin_settingpage('theme_mindatlas_social', get_string('socialmediasettings', 'theme_mindatlas'));

    // youtube
    $setting = new admin_setting_configtext('theme_mindatlas/youtube', 'Youtube', 'Youtube page address', '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // Facebook
    $setting = new admin_setting_configtext('theme_mindatlas/facebook', 'Facebook', 'Facebook page address', '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // Twitter
    $setting = new admin_setting_configtext('theme_mindatlas/twitter', 'Twitter', 'Twitter page address', '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // Linkedin
    $setting = new admin_setting_configtext('theme_mindatlas/linkedin', 'Linkedin', 'Linkedin page address', '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // Instagram
    $setting = new admin_setting_configtext('theme_mindatlas/instagram', 'Instagram', 'Instagram page address', '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);


    $settings->add($page);

    // ============================================
    // Tab: Banners
    $page = new admin_settingpage('theme_mindatlas_banners', get_string('banners', 'theme_mindatlas'));

    $name = 'theme_mindatlas/mycourses_banner';
    $title = get_string('mycourses_banner', 'theme_mindatlas');
    $description = '';
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'mycourses_banner');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);
    // ============================================
    // Tab: Banners My training
    $page = new admin_settingpage('theme_mindatlas_banners_my_training', get_string('bannerTraining', 'theme_mindatlas'));

    $name = 'theme_mindatlas/my_training_banner';
    $title = get_string('my_training_banner', 'theme_mindatlas');
    $description = '';
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'my_training_banner');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);

    $page = new admin_settingpage('theme_mindatlas_training', get_string('mytraining', 'theme_mindatlas'));
    $setting = new admin_setting_confightmleditor('theme_mindatlas/training_text', get_string('training_text', 'theme_mindatlas'), get_string('welcome_text_desc', 'theme_mindatlas'), '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $setting = new admin_setting_confightmleditor('theme_mindatlas/training_textt', get_string('training_text_second', 'theme_mindatlas'), get_string('welcome_text_desc', 'theme_mindatlas'), '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);

    // ============================================
    // Tab: Advanced settings
    // $page = new admin_settingpage('theme_mindatlas_advanced', get_string('advancedsettings', 'theme_mindatlas'));

    // // Replicate the preset setting from boost.
    // $name = 'theme_mindatlas/preset';
    // $title = get_string('preset', 'theme_mindatlas');
    // $description = get_string('preset_desc', 'theme_mindatlas');
    // $default = 'default.scss';

    // // We list files in our own file area to add to the drop down. We will provide our own function to
    // // load all the presets from the correct paths.
    // $context = context_system::instance();
    // $fs = get_file_storage();
    // $files = $fs->get_area_files($context->id, 'theme_mindatlas', 'preset', 0, 'itemid, filepath, filename', false);

    // $choices = [];
    // foreach ($files as $file) {
    //     $choices[$file->get_filename()] = $file->get_filename();
    // }
    // // These are the built in presets from Boost.
    // $choices['default.scss'] = 'default.scss';
    // $choices['plain.scss'] = 'plain.scss';

    // $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    // $setting->set_updatedcallback('theme_reset_all_caches');
    // $page->add($setting);

    // // Preset files setting.
    // $name = 'theme_mindatlas/presetfiles';
    // $title = get_string('presetfiles','theme_mindatlas');
    // $description = get_string('presetfiles_desc', 'theme_mindatlas');

    // $setting = new admin_setting_configstoredfile($name, $title, $description, 'preset', 0,
    //     array('maxfiles' => 20, 'accepted_types' => array('.scss')));
    // $page->add($setting);


    // // Raw SCSS to include before the content.
    // $setting = new admin_setting_configtextarea('theme_mindatlas/scsspre',
    //     get_string('rawscsspre', 'theme_mindatlas'), get_string('rawscsspre_desc', 'theme_mindatlas'), '', PARAM_RAW);
    // $setting->set_updatedcallback('theme_reset_all_caches');
    // $page->add($setting);

    // // Raw SCSS to include after the content.
    // $setting = new admin_setting_configtextarea('theme_mindatlas/scss', get_string('rawscss', 'theme_mindatlas'),
    //     get_string('rawscss_desc', 'theme_mindatlas'), '', PARAM_RAW);
    // $setting->set_updatedcallback('theme_reset_all_caches');
    // $page->add($setting);

    // $settings->add($page);
}
