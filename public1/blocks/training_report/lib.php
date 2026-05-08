<?php
//defined('MOODLE_INTERNAL') || die();

function block_training_report_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload = false, array $options = []) {
    list($itemid, $filename) = $args;

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'block_training_report', $filearea, $itemid, '/', $filename);

    if (!empty($file)) {
        send_stored_file($file);
    }
}



function block_training_report_js_css() {
    global $PAGE, $CFG;

    $file = '/blocks/training_report/dist/style.bundle.css';
    $PAGE->requires->css($file.'?'.filemtime($CFG->dirroot.$file));

}