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

use core\analytics\indicator\read_actions;

require_once(__DIR__ . '../../../../config.php');
require_once(__DIR__ . '../../lib.php');
require_once($CFG->dirroot . '/blocks/theme_support/classes/mindatlas_theme_library.php');

global $THEME, $USER;

require_login();

$theme_lib = new mindatlas_theme_library();

$url = new moodle_url('/theme/mindatlas/pages/my_coaching_sessions_manager.php');
$PAGE->set_url($url);

if(!is_siteadmin()){
    $node_user_record = $DB->get_record('hierarchy_coach', array('user_id' => $USER->id));
    if($node_user_record){
        $node = $DB->get_record('hierarchy_node', array('id' => $node_user_record->node_id));
        $nodeparent = $DB->get_record('hierarchy_node', array('id' => $node->parent_node_id));
        $coachuser= '';
        if($nodeparent->id != 1){
            $PAGE->set_context(context_system::instance());
            echo $OUTPUT->header();
            echo $OUTPUT->box('You do not have access here.', '');
            echo $OUTPUT->footer();
            die();
        }
    }else{
        $PAGE->set_context(context_system::instance());
            echo $OUTPUT->header();
            echo $OUTPUT->box('You are not assigned as a coach.', '');
            echo $OUTPUT->footer();
            die();
    }
}

$context = context_system::instance();

$PAGE->set_context($context);

$title = 'My coaching sessions';
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('mytrainingsession');
$coachingurl = new moodle_url('/theme/mindatlas/pages/my_coaching_sessions.php');
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('My coaching sessions',$coachingurl);
$PAGE->navbar->add('My coaching sessions manager', $url);
$output = $PAGE->get_renderer('core', 'course');

echo $OUTPUT->header();

?>

<?php
$back_url = $CFG->wwwroot;

?>

<div id="mount-react-coachingSessionManager"></div>


<?php
echo $OUTPUT->footer();
