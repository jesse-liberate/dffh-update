<?php
defined('MOODLE_INTERNAL') || die();

global $THEME, $USER;

user_preference_allow_ajax_update('drawer-open-nav', PARAM_ALPHA);
require_once($CFG->libdir . '/behat/lib.php');

$navdraweropen = false;
$extraclasses = [];

$bodyattributes = $OUTPUT->body_attributes($extraclasses);

// Choose template
switch($PAGE->bodyid) {
    default: 
        $template = 'theme_mindatlas/shoppingcart';
}

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = strpos($blockshtml, 'data-block=') !== false;
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions();
// If the settings menu will be included in the header then don't add it here.
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
    'theme' => $THEME,
    'theme_brand' => $THEME->brand,
    'theme_url' => $THEME->url,
    'isAdmin'=>is_siteadmin($USER)
];

$nav = $PAGE->flatnav;
$templatecontext['flatnavigation'] = $nav;
$templatecontext['firstcollectionlabel'] = $nav->get_collectionlabel();

$THEME->requires();
$THEME->require_user();

echo $OUTPUT->render_from_template($template, $templatecontext);



