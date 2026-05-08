<?php 
require('../../../config.php');
require($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->libdir.'/adminlib.php');
global $DB;



$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('courseenrol', 'tool_courseenrol'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/admin/tool/courseenrol/index.php');

$contextid = optional_param('contextid', 0, PARAM_INT);
if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
} else {
    $context = context_system::instance();
}

if ($context->contextlevel != CONTEXT_COURSECAT and $context->contextlevel != CONTEXT_SYSTEM) {
    print_error('invalidcontext');
}

$category = null;
if ($context->contextlevel == CONTEXT_COURSECAT) {
    $category = $DB->get_record('course_categories', array('id'=>$context->instanceid), '*', MUST_EXIST);
}

if ($CFG->forcelogin) {
    require_login(); 
}
$individualquery = "";
$cohortquery = "";

// echo $filterstr;w

if (isset($_GET["page"])) { 
    $page = $_GET["page"]; 
} else { 
    $page=1; 
} 

echo $OUTPUT->header();

$params = array('page' => $page);
if ($contextid) {
    $params['contextid'] = $contextid;
}
 
if ($cohortquery) {
    $params['tabcohort'] = true;
} else $params['tabindividual'] = true;


$baseurl = new moodle_url('/courseenrol/individual.php', $params);
if ($editcontrols = courseenrol_edit_controls($context, $baseurl)) {
    echo $OUTPUT->render($editcontrols);
}
// print_r($editcontrols);
?>

<?php echo $OUTPUT->footer();?>   

</body>
    
    

    
    