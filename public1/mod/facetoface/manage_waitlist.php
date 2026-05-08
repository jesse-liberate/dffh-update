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
require_once('renderer.php');

global $DB, $OUTPUT;

$sessionid = required_param('s',PARAM_INT);

$is_admin = is_siteadmin($USER->id);

$session = facetoface_get_session($sessionid);
$facetoface = $DB->get_record('facetoface', array('id'=>$session->facetoface));
$course = $DB->get_record('course', array('id'=>$facetoface->course));

$waitlist_attendees = ma_facetoface_get_waitlist_attendees($sessionid, $USER->id);


$PAGE->requires->js_init_code(js_writer::set_variable('window.user', $USER));


$context = context_system::instance();
$PAGE->set_url('/mod/facetoface/manage_waitlist.php.php');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');

require_login();

$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Home', new moodle_url('/'));
$PAGE->navbar->add('Face-to-face requests', new moodle_url('/mod/facetoface/facetoface_requests.php'));
$PAGE->navbar->add(get_string('managewaitlist', 'facetoface'));

$title = get_string('managewaitlist', 'facetoface');

$PAGE->set_title($title);
$PAGE->set_heading($course->fullname.' - '.$facetoface->name);

$f2frenderer = $PAGE->get_renderer('mod_facetoface');

echo $OUTPUT->header();

echo $OUTPUT->heading($PAGE->heading);

echo facetoface_print_session($session, false, false, true, true);


// echo '<pre>';
// var_dump($course);
// echo '</pre>';




?>

<table class="generaltable table-waitlist" summary="People planning on this session.">
    <caption><?php echo get_string('managewaitlist', 'facetoface'); ?></caption>
    <thead>
        <tr>
        <th class="header c1"  scope="col">Name</th>
        <th class="header c2"  scope="col">Request time</th>
        <th class="header c3"  scope="col">Priority</th>
        <!-- <th class="header c4"  scope="col">pri</th> -->
        </tr>
    </thead>
    <tbody>
        <?php echo html_tbody_waitlist($waitlist_attendees); ?>
    </tbody>
</table>

<script>

$('.table-waitlist .attendee').each(function(){
    var sessionid = $(this).attr('data-sessionid');
    var signupstatusid = $(this).attr('data-ssid');

    $(this).find('.up').click(function(){
        move_attendee(sessionid, signupstatusid, 'UP');
    })
    $(this).find('.down').click(function(){
        move_attendee(sessionid, signupstatusid, 'DOWN');
    })
    $(this).find('.top').click(function(){
        move_attendee(sessionid, signupstatusid, 'TOP');
    })
})



    function move_attendee(sessionid, signupstatusid, direction){
        console.log('move_attendee ');
        $.ajax({
            type: "post",
            url: M.cfg.wwwroot+"/mod/facetoface/api/move_attendee.php",
            data: {
                userid: user.id,
                sessionid: sessionid,
                signupstatusid: signupstatusid,
                direction: direction

            },
            success: function(data,status){
                console.log(data);
            }

        }).done(function(data) {
            result = JSON.parse(data);
            console.log(result);
            location.reload();
        });    
    }
</script>

<style type="text/css">
    .table-waitlist .c3 {
        text-align: right;
    }

    .table-waitlist .c3 img {
        margin: 0 4px;
        height: 12px;
        padding: 0;
        cursor: pointer;
    }

    .table-waitlist .attendee:first-child img.up,
    .table-waitlist .attendee:first-child img.top,
    .table-waitlist .attendee:last-child img.down {
        opacity: 0.5;
        pointer-events: none;
    }

</style>

<?php
echo $OUTPUT->footer();



function html_tbody_waitlist($candidates){
    global $DB, $OUTPUT;

    $html = '';

    foreach ($candidates as $key => $candidate) {
        $html .= '<tr class="attendee" data-ssid="'.$candidate->signupstatusid.'" data-sessionid="'.$candidate->sessionid.'">';
        $html .=    '<td class="c1">'.$candidate->firstname.' '.$candidate->lastname.'</td>';
        $html .=    '<td class="c2">'.date('d/m/Y H:i:s', $candidate->timecreated).'</td>';
        $html .=    '<td class="c3 move-attendee-btns" >';
        $html .=        '<img class=" up "   src="'.$OUTPUT->image_url('t/up').'"   title="Move up">';
        $html .=        '<img class=" down " src="'.$OUTPUT->image_url('t/down').'" title="Move down">';  
        // $html .=        '<img class=" top "  src="pix/top.svg"  title="Move to top">';
        $html .=    '</td>';
        // $html .=    '<td>'.$candidate->waitlist_priority.'</td>';
        $html .= '</tr>';
    }

    
    return $html;

}
