<?php

// Include required libary files
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/locallib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/classes/forms/settings_form.php');

use tool_enrolmentemail\forms\settings_form;

require_login();

if (!is_siteadmin()) {
    redirect('/');
}

// Include what we need
// require_once($CFG->dirroot.'/vendor/autoload.php');
$filepath = '/admin/tool/enrolmentemail/index.php';
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin'); 
$PAGE->set_title($SITE->fullname.': Course Enrolment Notification');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot . $filepath);

$css_path = '/lib/mindatlas/datatables/datatables.min.css';
$js_path = '/lib/mindatlas/datatables/datatables.min.js';
if (file_exists($CFG->dirroot . $css_path)) {
  $PAGE->requires->css($css_path);
}
if (file_exists($CFG->dirroot . $js_path)) {
  $PAGE->requires->js(new moodle_url($js_path), true);
}

$form = new settings_form();
// Cancel
if ($form->is_cancelled()) {
    redirect('/');
}
// Save Data
else if (($data = $form->get_data()) || isset($_POST['coursenotification'])) {
    if (isset($_POST['coursenotification'])) {
        $data->coursenotification = array_keys($_POST['coursenotification']);
    }
    $form->save($data);
    $message = get_string('savechangessuccess', ENROLMENTEMAIL_PLUGINNAME);
    $messagetype = \core\output\notification::NOTIFY_SUCCESS;
    redirect($filepath, $message, '', $messagetype);
}
// Load Data
else {
    $support_user = \core_user::get_support_user();
    $support_name = fullname($support_user);
    $data = new stdClass();
    if ($initialnotificationconfig = get_local_config(ENROLMENTEMAIL_INITIALCOURSENOTIFICATION)) {
        $data->initialcoursenotification = $initialnotificationconfig;
    }
    if ($maxattemptsallowed = get_local_config(ENROLMENTEMAIL_MAXATTEMPTSALLOWED)) {
        $data->maxattemptsallowed = $maxattemptsallowed;
    }
    if ($subjectline = get_local_config(ENROLMENTEMAIL_EMAILSUBJECTLINE)) {
        $data->subjectline = $subjectline;
    }
    if ($signaturename = get_local_config(ENROLMENTEMAIL_EMAILSIGNATURENAME)) {
        $data->signaturename = $signaturename;
    }
    else {
        $data->signaturename = $support_name;
    }
    if ($contact = get_local_config(ENROLMENTEMAIL_EMAILCONTACT)) {
        $data->contact = $contact;
    }
    else {
        $data->contact = $support_user->email;
    }
    if ($content = get_local_config(ENROLMENTEMAIL_EMAILCONTENT)) {
        $data->content = $content;
    }
    $form->set_data($data);
}


// get parameters
// error_log(var_export($_POST, true));

// Display page
echo $OUTPUT->header();
echo $form->render();
echo <<< EOT
<script type="text/javascript">
$(document).ready( function () {
    $('#courselist').DataTable({
        'pageLength': 50,
        'language': {
            'emptyTable': 'No records found'
        }
    });
} );
</script>
EOT;
echo $OUTPUT->footer();

