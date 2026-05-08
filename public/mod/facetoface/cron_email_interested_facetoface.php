<?php
// MA-MODIFIED 

if (!defined('CLI_SCRIPT')) {
    define('CLI_SCRIPT', true);
}

require_once(__DIR__.'/../../config.php');
require_once('lib.php');
require_once('lib_mindatlas.php');

$context = context_system::instance();
$PAGE->set_context($context);

global $DB,$CFG;

$facetoface_ids = $DB->get_records_sql_menu('SELECT DISTINCT id,facetoface FROM {facetoface_sessions}', array());

$all_facetoface = $DB->get_records('facetoface', array());

$f2f_nosession = array();

foreach ($all_facetoface as $key => $facetoface) {

    if (ma_facetoface_has_upcoming_session($facetoface->id)) {
        continue;
    }

    $interests = ma_get_facetoface_interested($facetoface->id);
    if (count($interests) < 1) {
        continue;
    }

    $new_facetoface = new stdClass();
    $new_facetoface->id = $facetoface->id;
    $new_facetoface->name = $facetoface->name;
    $new_facetoface->course = $facetoface->course;
    $new_facetoface->interested = count($interests);
    $new_facetoface->coursename = $DB->get_field('course', 'fullname', array('id'=>$facetoface->course));

    $f2f_nosession[] = $new_facetoface;
}

if (count($f2f_nosession) == 0) {
	die();
}

$adminids = explode(',', $CFG->siteadmins);
$adminuser = get_complete_user_data('id', reset($adminids));

$emailbody = '';
$emailbody .= '<p>Hi '.$adminuser->firstname.', </p>';
$emailbody .= '<p>Learners have expressed interest in the following face-to-face activities.</p>';

$emailbody .= '<table class="generaltable sessions-at-capacity" summary="Sessions at capacity">';
$emailbody .= '
			    <thead>
			        <tr>
			        <th class="header c0"  scope="col">'.get_string('course','core').'</th>
			        <th class="header c1"  scope="col">Activity</th>
			        <th class="header c2"  scope="col">Interested</th>
			        </tr>
			    </thead>';

$emailbody .= '<tbody>';		

$emailbody .= '</tbody>';		    
    
$emailbody .= html_tbody_interested($f2f_nosession);

$emailbody .= '</table>';

$emailbody .= '<p></p>';
$emailbody .= '<p>Kind regards,</p>';
$emailbody .= '<p>FSV Learning management system</p>';


$emailbody .= '
<style type="text/css">
    .generaltable  {
    	width: 100%;
    	max-width: 600px;
        text-align: left;
    }
    .generaltable .c2 {
    	text-align: right;
    }

</style>
';


$from_user = \core_user::get_support_user();
$email_text = html_to_text($emailbody);

$emailsent = email_to_user($adminuser, $from_user, 'FSV face-to-face: Expressions of interest', $email_text, $emailbody);







