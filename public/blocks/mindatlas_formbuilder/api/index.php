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
 * Performs ajax actions.
 *
 * Please note functions may throw exceptions, please ensure your JS handles them as well as the outcome objects.
 *
 * @package    theme_mindle
 * @copyright  2019 Jacky Zhu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');
require_once(__DIR__ . '/../classes/theme_ajax_controller.php');

if(empty($_POST)){
    $post_data = json_decode(file_get_contents("php://input"), true);
    $action = $post_data['action'];
    $payload = $post_data['payload'];
}else{
    $action = required_param('action', PARAM_RAW);
    $payload = optional_param('payload', null, PARAM_RAW);
}

$no_require_login_actions = [
    'dffh_ajax_view_formbuilder'
];

// use sesskey() to create sesskey, it will be saved in $_SESSION['USER'], also can be get from $USER->sesskey
// require_sesskey(); // Gotta have the sesskey.
if (!in_array($action, $no_require_login_actions)) {
    //require_login(); // Gotta be logged in (of course).
}
$PAGE->set_context(context_system::instance());

$ajax = new theme_mindle_ajax_controller();



// echo $OUTPUT->header();

echo json_encode($ajax->process($action, $payload));

// echo $OUTPUT->footer();

exit;
