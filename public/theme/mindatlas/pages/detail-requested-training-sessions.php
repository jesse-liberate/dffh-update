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
$id = required_param('request', PARAM_INT);
$url = new moodle_url('/theme/mindatlas/pages/detail-requested-training-sessions.php', ['request' => $id]);
$PAGE->set_url($url);


$context = context_system::instance();

$PAGE->set_context($context);

$title = 'Requested coaching session';
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('mytrainingsession');
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('My coaching sessions');
$PAGE->navbar->add('Requested coaching sessions', $CFG->wwwroot . '/theme/mindatlas/pages/requested-training-sessions.php');
$PAGE->navbar->add('Edit coaching sessions');


$output = $PAGE->get_renderer('core', 'course');

echo $OUTPUT->header();
?>

<?php
$back_url = $CFG->wwwroot;
?>
<div id="mount-react-detail-requested-training-sessions" data-id="<?= $id ?>">

</div>
<?php
echo $OUTPUT->footer();
