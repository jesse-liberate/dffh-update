<?php
require_once('../../config.php');
include("lib.php");

global $USER, $DB,$TEAMMEMBER;

if(!isset($userid)) $userid = $USER->id;
require_login(0, false);
$context_system = context_system::instance();
$PAGE->set_context($context_system);

$PAGE->set_pagelayout('standard');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading(get_string('mylearning','block_coach'));
$PAGE->set_url('/blocks/coach/course.php');
if(isset($_GET['uid'])){
	$TEAMMEMBER = $_GET['uid'];
	if(!is_in_your_team_member($TEAMMEMBER)){
		//Check if current user are allowed to access  this user
		echo $OUTPUT->header();
		echo get_string('notallowtoaccessteammember','block_coach');
		echo $OUTPUT->footer();
		die();
	}
}else{
	echo $OUTPUT->header();
	echo get_string('couldnotfindteammember','block_coach');
	echo $OUTPUT->footer();
	die();
}

echo $OUTPUT->header();
// echo "<pre>".print_r($courses,true)."</pre>";
$courserenderer = $PAGE->get_renderer('core', 'course');
// echo $courserenderer->course_info_box($course);
$tm = $DB->get_record('user',array('id'=>$TEAMMEMBER));
$tm_fullname = ucfirst($tm->firstname)." ".ucfirst($tm->lastname);
// echo "<section id='region-main'>";
echo "<div class='course_cat_title'>Team Member: $tm_fullname</div>";
echo "<div id='teammember_courses'><div class='container'>";
echo $courserenderer->frontpage_combo_list($TEAMMEMBER);
echo "</div></div>";
// echo "</section>";
echo $OUTPUT->footer();
?>
<script type="text/javascript">
	$('#teammember_courses .course_category_tree .subcategories a').each(function(){
		$(this).attr('href','#');
	});
	$('.course_category_tree .category.loaded.with_children').each(function(){
		if ($(this).find('.courses .course[data-courseid]').length == 0) {
			$(this).addClass('hidden');
		}
	});
</script>
<style type="text/css">
#id_expired_category .course{
    background-color: #ddd;
    cursor: auto;
}

#id_expired_category .category_progress_state {
    display: none;
}

.course_category_tree #id_expired_category>.info>.categoryname:after {
    display: none;
}
</style>