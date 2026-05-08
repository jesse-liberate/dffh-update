<?php
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');

global $USER, $PAGE;
require_login();

$file = '/blocks/mindatlas_formbuilder/dist/index.bundle.js';
$PAGE->requires->js($file.'?'.filemtime($CFG->dirroot.$file));

$url = new moodle_url('/blocks/mindatlas_formbuilder/pages/formbuiler.php');
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
$title = get_string('formbuilder', 'block_mindatlas_formbuilder');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('standard');

$output = $PAGE->get_renderer('core', 'course');


echo $OUTPUT->header();

?>

<div class="">
    <div id="mount-react-formbbuilder-page"></div>
</div>

<?php
echo $OUTPUT->footer();
