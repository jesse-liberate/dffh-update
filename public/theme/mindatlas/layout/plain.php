<?php
defined('MOODLE_INTERNAL') || die();

global $THEME, $PAGE;
$THEME->require_user();

user_preference_allow_ajax_update('drawer-open-nav', PARAM_ALPHA);
require_once($CFG->libdir . '/behat/lib.php');

$navdraweropen = false;
$extraclasses = [];

$bodyattributes = $OUTPUT->body_attributes('');

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = strpos($blockshtml, 'data-block=') !== false;
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions();
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;
$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'isAdmin' => is_siteadmin($USER),
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $bodyattributes,
    'navdraweropen' => $navdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'user' => $USER,
    'theme' => $THEME,
    'theme_brand' => $THEME->brand,
    'theme_url' => $THEME->url,

];

$THEME->requires();

echo $OUTPUT->render_from_template('theme_mindatlas/plain', $templatecontext);

