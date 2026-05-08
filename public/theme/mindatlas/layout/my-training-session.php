<?php
defined('MOODLE_INTERNAL') || die();

global $THEME, $PAGE;
$THEME->require_user();
$THEME->require('tailwind.bundle.css');
user_preference_allow_ajax_update('drawer-open-nav', PARAM_ALPHA);
require_once($CFG->libdir . '/behat/lib.php');


$navdraweropen = false;
$extraclasses = [];

$bodyattributes = $OUTPUT->body_attributes($extraclasses);

// switch to fullwidth mode,
// use $PAGE->add_body_classes(['full-width']); in target page
$pageContainerClass = "container";
if (strpos($bodyattributes, 'full-width') !== false) {
    $pageContainerClass = "container-fluid";
}

// Choose template
switch ($PAGE->bodyid) {
    case 'page-login-forgot_password':
        $template = 'theme_mindatlas/login';
        break;
    default:
        $template = 'theme_mindatlas/mytrainingsession';
}

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = strpos($blockshtml, 'data-block=') !== false;
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions();
// If the settings menu will be included in the header then don't add it here.
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;
$idReact = 'mount-react-mycoachingsessionbanner';
if (in_array($PAGE->title, ['My training sessions', 'Requested Training Session'])) {
    $idReact = 'mount-react-mytrainingsessionbanner';
}
if (in_array($PAGE->title, ['Form builder'])) {
    $idReact = 'mount-react-form-builder';
}
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
    'pageContainerClass' => $pageContainerClass,
    'idReact' => $idReact,
];

$nav = $PAGE->flatnav;
$templatecontext['flatnavigation'] = $nav;
$templatecontext['firstcollectionlabel'] = $nav->get_collectionlabel();

$THEME->requires();

echo $OUTPUT->render_from_template($template, $templatecontext);
