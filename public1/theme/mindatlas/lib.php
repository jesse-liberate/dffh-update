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
 * Mindatlas backgrounds callbacks.
 *
 * @package    theme_mindatlas
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This line protects the file from being accessed by a URL directly.
defined('MOODLE_INTERNAL') || die();

// JZ: This $THEME is not the one in the config.php
include_once(__DIR__ . '/classes/theme_helper.php');

use block_most_viewed_liked_course\model\UserCourseRating;

global $THEME;
$THEME = new theme_mindatlas_theme_helper();

/**
 * Returns the main SCSS content.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_mindatlas_get_main_scss_content($theme)
{
    global $CFG;

    $scss = '';
    $filename = !empty($theme->settings->preset) ? $theme->settings->preset : null;
    $fs = get_file_storage();

    $context = context_system::instance();
    if ($filename == 'default.scss') {
        // We still load the default preset files directly from the boost theme. No sense in duplicating them.
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
    } else if ($filename == 'plain.scss') {
        // We still load the default preset files directly from the boost theme. No sense in duplicating them.
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/plain.scss');
    } else if ($filename && ($presetfile = $fs->get_file($context->id, 'theme_mindatlas', 'preset', 0, '/', $filename))) {
        // This preset file was fetched from the file area for theme_mindatlas and not theme_boost (see the line above).
        $scss .= $presetfile->get_content();
    } else {
        // Safety fallback - maybe new installs etc.
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
    }

    // Pre CSS - this is loaded AFTER any prescss from the setting but before the main scss.
    $pre = file_get_contents($CFG->dirroot . '/theme/mindatlas/scss/pre.scss');
    // Post CSS - this is loaded AFTER the main scss but before the extra scss from the setting.
    $post = file_get_contents($CFG->dirroot . '/theme/mindatlas/scss/post.scss');

    // Combine them together.
    return $pre . "\n" . $scss . "\n" . $post;
}


function theme_mindatlas_get_modules()
{
    global $DB;

    $content = '';
    $category = $DB->get_record_sql('SELECT * FROM {course_categories} WHERE idnumber = ?',array('module'));
    $courses = $DB->get_records_sql('SELECT * FROM {course} WHERE category = ?',array($category->id));

    foreach($courses as $course){
        $content .='<tr class="">
            <td ><b>'.$course->fullname.'</b> </td>
            <td >'.$course->summary.'</td>
        </tr>';
    }
   

    // Combine them together.
    return $content;
}


// $url = $CFG->wwwroot/pluginfile.php/$contextid/$component/$filearea/arbitrary/extra/infomation.ext
// http://localhost/rejectshop/pluginfile.php/1/theme_mindatlastheme/loginbackgroundimage/1547/welcome-photo.png
function theme_mindatlas_file_from_setting($setting_name)
{
    global $CFG, $DB;

    $plugin = 'theme_mindatlas';
    $url = "";
    $setting_record = $DB->get_record('config_plugins', array('plugin' => $plugin, 'name' => $setting_name));

    if ($setting_record) {
        // Admin settings are stored in system context.                                                               
        $syscontext = context_system::instance();
        $url .= $CFG->wwwroot . "/pluginfile.php/" . $syscontext->id . "/" . $plugin . "/" . $setting_name . "/0" . $setting_record->value;
    }

    return $url;
}


function theme_mindatlas_update_settings_images($settingname)
{
    global $CFG;

    // The setting name that was updated comes as a string like 's_theme_mindatlas_loginbackgroundimage'.
    // We split it on '_' characters.
    $parts = explode('_', $settingname);
    // And get the last one to get the setting name..
    $settingname = end($parts);

    // Admin settings are stored in system context.
    $syscontext = context_system::instance();
    // This is the component name the setting is stored in.
    $component = 'theme_mindatlas';


    // This is the value of the admin setting which is the filename of the uploaded file.
    $filename = get_config($component, $settingname);
    // We extract the file extension because we want to preserve it.
    $extension = substr($filename, strrpos($filename, '.') + 1);

    // This is the path in the moodle internal file system.       
    $fullpath = "/{$syscontext->id}/{$component}/{$settingname}/0{$filename}";
    // Get an instance of the moodle file storage.                                                                    
    $fs = get_file_storage();
    // This is an efficient way to get a file if we know the exact path.                                              
    if ($file = $fs->get_file_by_hash(sha1($fullpath))) {
        // We got the stored file - copy it to dataroot.                                                              
        // This location matches the searched for location in theme_config::resolve_image_location.      
        $pathname = $CFG->dataroot . '/pix_plugins/theme/mindatlas/' . $settingname . '.' . $extension;

        // This pattern matches any previous files with maybe different file extensions.                              
        $pathpattern = $CFG->dataroot . '/pix_plugins/theme/mindatlas/' . $settingname . '.*';

        // Make sure this dir exists.                                                                                 
        @mkdir($CFG->dataroot . '/pix_plugins/theme/mindatlas/', $CFG->directorypermissions, true);

        // Delete any existing files for this setting.
        foreach (glob($pathpattern) as $filename) {
            @unlink($filename);
        }

        // Copy the current file to this location.
        $file->copy_content_to($pathname);
    }

    // Reset theme caches.                                                                                                          
    theme_reset_all_caches();
}

/**
 * Serves any files associated with the theme settings.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function theme_mindatlas_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array())
{       
       
        $theme = theme_config::load('mindatlas');
        // By default, theme files must be cache-able by both browsers and proxies.
        if (!array_key_exists('cacheability', $options)) {
            $options['cacheability'] = 'public';
        }
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    
}

/**
 * Get SCSS to prepend.
 *
 * @param theme_config $theme The theme config object.
 * @return array
 */
function theme_mindatlas_get_pre_scss($theme)
{
    global $CFG;

    $scss = '';
    $configurable = [
        // Config key => [variableName, ...].
        'primarycolor'  => ['primary'],
        'brandcolor1' => ['brandcolor1'],
        'brandcolor2' => ['brandcolor2'],
        'brandcolor3' => ['brandcolor3'],
        // 'brandcolor4' => ['brandcolor4'],
    ];

    // Prepend variables first.
    foreach ($configurable as $configkey => $targets) {
        $value = isset($theme->settings->{$configkey}) ? $theme->settings->{$configkey} : null;
        if (empty($value)) {
            continue;
        }
        array_map(function ($target) use (&$scss, $value) {
            $scss .= '$' . $target . ': ' . $value . ";\n";
        }, (array) $targets);
    }

    // Prepend pre-scss.
    if (!empty($theme->settings->scsspre)) {
        $scss .= $theme->settings->scsspre;
    }

    return $scss;
}

/**
 * PHP7.0, Moodle 3.7
 * require js file on page
 *
 * @param string $file, relative path to dirroot and filename, example 'theme/mindatlas/javascript/_theme.js' 
 * @return bool $inhead, require before <header>
 */
function theme_mindatlas_require_js(string $file, $inhead = false)
{
    global $PAGE, $CFG;
    if (file_exists($CFG->dirroot . $file)) {
        $PAGE->requires->js($file . '?' . filemtime($CFG->dirroot . $file), $inhead);
    } else {
        print_error('filenotfound', 'error', '', null, $CFG->dirroot . $file);
    }
}

/**
 * PHP7.0, Moodle 3.7
 * require css file on page
 *
 * @param string $file, 
 */
function theme_mindatlas_require_css(string $file)
{
    global $PAGE, $CFG;
    if (file_exists($CFG->dirroot . $file)) {
        $PAGE->requires->css($file . '?' . filemtime($CFG->dirroot . $file));
    } else {
        print_error('filenotfound', 'error', '', null, $CFG->dirroot . $file);
    }
}


function theme_mindatlas_get_course_image($course)
{
    global $CFG;

    //default image
    $moodle_url = new moodle_url('/theme/mindatlas/pix/image.png');
    $img_url = $moodle_url->out();

    foreach ($course->get_course_overviewfiles() as $file) {
        $isimage = $file->is_valid_image();
        if ($isimage) {
            return file_encode_url(
                "$CFG->wwwroot/pluginfile.php",
                '/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
                    $file->get_filearea() . $file->get_filepath() . $file->get_filename(),
                !$isimage
            );
        }
    }

    return $img_url;
}


function theme_mindatlas_get_course_progress($courseid)
{
    global $DB, $USER;
    $userid = $USER->id;

    $progress = 0;
    $tick_box_total = 0;
    $tick_box_completed_total = 0;
    $course_modules_records = $DB->get_records('course_modules', array('course' => $courseid, 'visible' => '1', 'deletioninprogress' => '0'));
    if ($course_modules_records != false) {

        //Start CourseModule ForEach Loop
        foreach ($course_modules_records as $course_modules_record) {
            $tick_box_type = $course_modules_record->completion;

            if ($tick_box_type == 1 || $tick_box_type == 2) {
                $tick_box_total++;

                $course_modules_completion_records = $DB->get_records('course_modules_completion', array('coursemoduleid' => $course_modules_record->id, 'userid' => $userid));
                if ($course_modules_completion_records != false) {
                    foreach ($course_modules_completion_records as $course_modules_completion_record) {
                        if ($course_modules_completion_record->completionstate == 1 || $course_modules_completion_record->completionstate == 2) {
                            $tick_box_completed_total++;
                        }
                    }
                }
            }

            //End CourseModule ForEach Loop
        }
        if ($tick_box_completed_total != 0) {
            $progress = round($tick_box_completed_total / $tick_box_total * 100);
        }
        // $user_tick_box_total += $tick_box_total;
        // $user_tick_box_completion_total += $tick_box_completed_total;
    }

    return $progress;
}

function theme_mindatlas_draw_course_rate($courseid)
{
    $model_user_course_rating = new UserCourseRating();
    $rating = 0;
    $course_rate = round($model_user_course_rating->getCourseAverageRate($courseid), 1);
    if ($course_rate != null) {
        $rating = $course_rate;
    }
    return $rating;
}

function str_startsWith($haystack, $needle)
{
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}

function str_endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}
