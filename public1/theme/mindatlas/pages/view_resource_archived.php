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

global $THEME, $USER;

require_login();

$id = required_param('id', PARAM_INT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$lmsonly = optional_param('lmsonly', true, PARAM_BOOL);
$keyword = optional_param('keyword', '', PARAM_TEXT);
$page = optional_param('page', 1, PARAM_INT);

$THEME->requires('view_resource.js');


$url = new moodle_url('/theme/mindatlas/pages/view_resource.php');
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

$title = 'File library';
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('plain');

$output = $PAGE->get_renderer('core', 'course');


echo $OUTPUT->header();

?>

<?php
$back_url = $CFG->wwwroot . '/theme/mindatlas/pages/file_library.php?categoryid=' . $categoryid;
if ($lmsonly) {
    $back_url .= '&lmsonly=true';
} else {
    $back_url .= '&lmsonly=false';
}

if ($keyword) {
    $back_url .= '&keyword=' . $keyword;
}
?>


<section class="section-back-link">
    <div class="container">
        <div class="back-link-wrapper py-3">
            <a class="back-link" href="<?= $back_url ?>">
                <span class="back-arrow"></span>
                <span><?= get_string('backto', '', get_string('resources', 'block_resources')) ?></span>
            </a>
        </div>
    </div>
</section>


<?= $THEME->render_view_resource($id, $categoryid, $lmsonly, $keyword, $page); ?>




<?php
echo $OUTPUT->footer();
