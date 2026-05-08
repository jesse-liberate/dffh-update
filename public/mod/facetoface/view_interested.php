<?php
// MA-MODIFIED 

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');
require_once('lib_mindatlas.php');
require_once('renderer.php');

require_login();

global $DB, $OUTPUT;

$is_admin = is_siteadmin($USER->id);

if (!$is_admin) {
    redirect($CFG->wwwroot, "You don't have access to this page.", null, \core\output\notification::NOTIFY_ERROR);
}


$PAGE->requires->js_init_code(js_writer::set_variable('window.user', $USER));


$context = context_system::instance();
$PAGE->set_url('/mod/facetoface/view_interested.php');
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');


$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Home', new moodle_url('/'));
$PAGE->navbar->add('Expressions of interest');

$title = 'Expressions of interest';

$PAGE->set_title($title);
$PAGE->set_heading($title);

$f2frenderer = $PAGE->get_renderer('mod_facetoface');

echo $OUTPUT->header();

echo $OUTPUT->heading($PAGE->heading);


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


?>


<?php if(count($f2f_nosession) > 0): ?>
<table class="generaltable sessions-at-capacity" summary="Sessions at capacity">
    <!-- <caption>Sessions at capacity</caption> -->
    <thead>
        <tr>
        <th class="header c0"  scope="col"><?php echo get_string('course','core'); ?></th>
        <th class="header c1"  scope="col">Activity</th>
        <th class="header c2"  scope="col">Interested</th>
        <th class="header c4"  scope="col"></th>
        </tr>
    </thead>
    <tbody>
        <?php echo html_tbody_interested($f2f_nosession); ?>
    </tbody>
</table>
<?php else : ?>
<div>No record found.</div>

<?php endif; ?>


<script>

</script>

<style type="text/css">


</style>

<?php
echo $OUTPUT->footer();




