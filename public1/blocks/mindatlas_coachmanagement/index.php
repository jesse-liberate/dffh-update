<?php

require(__DIR__.'/../../config.php');
require_once('lib.php');
require_once(__DIR__.'/renderer.php');

// access check
require_login();
if (false) {
	redirect($CFG->wwwroot, 'Sorry, you don\'t have access to this page', null, \core\output\notification::NOTIFY_INFO);
}

// page settings
$context_system = context_system::instance();
$PAGE->set_context($context_system);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading(get_string('pluginname','block_mindatlas_coachmanagement'));
$PAGE->set_url('/blocks/mindatlas_coachmanagement/index.php');

// navbar setting
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Home', new moodle_url('/'));
$PAGE->navbar->add(get_string("pluginname",'block_mindatlas_coachmanagement'), new moodle_url('/blocks/mindatlas_coachmanagement/index.php'));

// require JS CSS
// sometimes need to purge cache to view changes
$PAGE->requires->js('/lib/mindatlas/jquery/jquery.min.js', true);
$PAGE->requires->js('/lib/mindatlas/jquery/ui/jquery-ui.min.js', true); 
$PAGE->requires->css('/lib/mindatlas/jquery/ui/jquery-ui.min.css');
$PAGE->requires->css('/blocks/mindatlas_coachmanagement/style/plugin.css');

// setup renderer
$output = $PAGE->get_renderer('block_mindatlas_coachmanagement');


// start outputting html
echo $output->header();
echo $output->heading(get_string('pluginname', 'block_mindatlas_coachmanagement'));

?>


<!-- //////////////////////////////////// -->
<!-- ************** HTML **************** -->
<!-- basic page structure -->
<section>
	<div> Page content </div>
</section>
<!-- ************************************ -->



<!-- ************** JS **************** -->
<!-- javascript only for this page -->
<script type="text/javascript">
// short cut for $(document).ready(function() { ... });
$(function() {

});
</script>

<!-- if inlucde js in this way, no need to purge catch to view the change,  -->
<!-- better to change to $PAGE->requires->js when finish development -->
<script src="javascript/plugin.js?v=<?php echo time(); ?>"></script>
<!-- ************************************ -->


<!-- ************** CSS **************** -->
<style type="text/css">
/*	header.navbar {
		display: none;
	}*/
</style>
<!-- ************************************ -->
<!-- //////////////////////////////////// -->



<?php

echo $output->footer();