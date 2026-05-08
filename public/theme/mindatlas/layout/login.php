<?php
defined('MOODLE_INTERNAL') || die();

global $THEME, $PAGE;

$bodyattributes = $OUTPUT->body_attributes();

// Choose template
switch($PAGE->bodyid) {
    case 'page-login-signup': 
        $template = 'theme_mindatlas/signup';
        $THEME->requires('signup.bundle.js');
        break;
    default: 
        $template = 'theme_mindatlas/login';
        $THEME->requires();
}

$collection_statement = get_config('theme_mindatlas', 'collection_statement');

$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'theme' => $THEME,
    'theme_brand' => $THEME->brand,
    'theme_url' => $THEME->url,
    'collection_statement'=>$collection_statement,
    'year' => date('Y'),
];

echo $OUTPUT->render_from_template($template, $templatecontext);

