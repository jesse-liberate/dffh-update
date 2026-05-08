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

global $THEME, $USER, $DB;

require_login();

$theme_lib = new mindatlas_theme_library();

$url = new moodle_url('/theme/mindatlas/pages/form-builder-edit.php');
$PAGE->set_url($url);
$id = required_param('id', PARAM_INT);
$form_detail = $DB->get_record('formbuilder_form', array('id' => $id));

$context = context_system::instance();

$PAGE->set_context($context);

$title = 'Form builder';
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('mytrainingsession');
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Form builder management', $CFG->wwwroot . '/theme/mindatlas/pages/form-builder.php');
$PAGE->navbar->add($form_detail->name);

$output = $PAGE->get_renderer('core', 'course');

echo $OUTPUT->header();

?>

<?php
$back_url = $CFG->wwwroot;
?>
<div id="mount-react-coach-form-setup-edit"></div>
<?php
echo $OUTPUT->footer();
