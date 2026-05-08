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
 * Copyright (C) 2007-2011 Catalyst IT (http://www.catalyst.net.nz)
 * Copyright (C) 2011-2013 Totara LMS (http://www.totaralms.com)
 * Copyright (C) 2014 onwards Catalyst IT (http://www.catalyst-eu.net)
 *
 * @package    mod
 * @subpackage facetoface
 * @copyright  2014 onwards Catalyst IT <http://www.catalyst-eu.net>
 * @author     Stacey Walker <stacey@catalyst-eu.net>
 * @author     Alastair Munro <alastair.munro@totaralms.com>
 * @author     Aaron Barnes <aaron.barnes@totaralms.com>
 * @author     Francois Marier <francois@catalyst.net.nz>
 */

// MA-MODIFIED 

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');
require_once('lib_mindatlas.php');
require_once(__DIR__ . '/../../admin/tool/hierarchy/lib.php');
require_once('renderer.php');

global $DB, $OUTPUT;

$is_admin = is_siteadmin($USER->id);
$is_manager = true;



// check if user is a manager in hierarcy
if (!hierarchy_is_parentnode_user($USER->id)) {
    redirect($CFG->wwwroot, "You don't have access to this page.", null, \core\output\notification::NOTIFY_INFO);
}


$waitlist_sessions = array();
$all_sessions = $DB->get_records('facetoface_sessions', array());

$usernode = hierarchy_get_user_node($USER->id);
$descendant_userids = hierarchy_get_node_descendant_userids($usernode->name);

foreach ($all_sessions as $key => $session) {
    // add some extra attributes
    $session->facetofacename = $DB->get_field('facetoface', 'name', array('id'=>$session->facetoface));
    $session->courseid = $DB->get_field('facetoface', 'course', array('id'=>$session->facetoface));
    $session->coursename = $DB->get_field('course', 'fullname', array('id'=>$session->courseid));

    //skip sessions not full yet
    if (ma_facetoface_seats_available($session)) {
        continue;
    }

    $session->sessiondates = facetoface_get_session_dates($session->id);
    // skip sessions finished(started and not in progress)
    if (ma_facetoface_has_session_finished($session)) {
        continue;
    }

    // check if session has member in waitlist
    $waitlist_attendees = ma_facetoface_get_waitlist_attendees($session->id, $USER->id);
    $session->waitlistcount = count($waitlist_attendees);

    foreach ($waitlist_attendees as $key => $attendee) {

        if ( array_search($attendee->id, $descendant_userids) !== false ) {
            $waitlist_sessions[] = $session;
            break;
        }
    }
    
}


$PAGE->requires->js_init_code(js_writer::set_variable('window.user', $USER));


$context = context_system::instance();
$PAGE->set_url('/mod/facetoface/facetoface_requests.php.php');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');

require_login();

$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Home', new moodle_url('/'));
$PAGE->navbar->add('Face-to-face requests', new moodle_url('/mod/facetoface/facetoface_requests.php'));


$title = 'Face-to-face requests';

$PAGE->set_title($title);
$PAGE->set_heading($title);

$f2frenderer = $PAGE->get_renderer('mod_facetoface');

echo $OUTPUT->header();

echo $OUTPUT->heading($PAGE->heading);

// echo '<pre>';
// var_dump($waitlist_sessions);
// echo '</pre>'; 



?>

<table class="generaltable sessions-at-capacity" summary="Sessions at capacity">
    <!-- <caption>Sessions at capacity</caption> -->
    <thead>
        <tr>
        <th class="header c0"  scope="col"><?php echo get_string('course','core'); ?></th>
        <th class="header c1"  scope="col">Activity</th>
        <th class="header c2"  scope="col">Session time</th>
        <th class="header c3"  scope="col">Wait-list</th>
        <th class="header c4"  scope="col"></th>
        </tr>
    </thead>
    <tbody>
        <?php echo html_tbody_waitlist_session($waitlist_sessions); ?>
    </tbody>
</table>


<style type="text/css">
    .session td {
        vertical-align: middle;
    }
</style>


<script>


</script>

<style type="text/css">


</style>

<?php
echo $OUTPUT->footer();


function html_tbody_waitlist_session($waitlist_sessions){
    global $DB;

    $html = '';

    foreach ($waitlist_sessions as $key => $session) {
        $courseurl = new moodle_url('/course/view.php', array('id'=>$session->courseid));
        $activityurl = new moodle_url('/mod/facetoface/view.php', array('f'=>$session->facetoface));
        $sessionurl = new moodle_url('/mod/facetoface/manage_waitlist.php', array('s'=>$session->id));
        $sessiontime = '';
        foreach ($session->sessiondates as $key => $date) {
            $sessiontime .= date('d M Y - H:i', $date->timestart).'<br>';
        }

        $html .= '<tr class="session"  data-sessionid="'.$session->id.'">';
        $html .=    '<td class="c0"><a href="'.$courseurl.'">'.$session->coursename.'</a></td>';
        $html .=    '<td class="c1"><a href="'.$activityurl.'">'.$session->facetofacename.'</a></td>';
        $html .=    '<td class="c2">'.$sessiontime.'</td>';
        $html .=    '<td class="c3">'.$session->waitlistcount.'</td>';
        $html .=    '<td class="c4"><a class="btn " href="'.$sessionurl.'">VIEW</a></td>';
        $html .= '</tr>';
    }

    
    return $html;

}

