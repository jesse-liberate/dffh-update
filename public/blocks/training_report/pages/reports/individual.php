<?php
require_once(__DIR__ . '../../../../../config.php');
require_once(__DIR__ . '../../../lib.php');

global $USER;

require_login();

// include js and css
block_training_report_js_css();

$file = '/blocks/training_report/dist/individualReports.bundle.js';
$PAGE->requires->js($file.'?'.filemtime($CFG->dirroot.$file));

$url = new moodle_url('/blocks/training_report/pages/reports/individual.php');
$PAGE->set_url($url);

if (isguestuser()) {
    $PAGE->set_context(context_system::instance());
    echo $OUTPUT->header();
    echo $OUTPUT->box('Guest user has no access.', 'notifyproblem');
    echo $OUTPUT->footer();
    die();
}


$context = context_system::instance();

$PAGE->set_context($context);

$PAGE->add_body_classes(['full-width']);
$title = get_string('individual_report', 'block_training_report');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('standard');

$output = $PAGE->get_renderer('core', 'course');


echo $OUTPUT->header();

?>

<div>
    <div id="mount-react-individual-report"></div>
</div>

<?php
echo $OUTPUT->footer();
