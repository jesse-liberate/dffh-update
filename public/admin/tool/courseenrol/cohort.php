<?php 
require('../../../config.php');
require('lib.php');
require($CFG->dirroot.'/cohort/lib.php');
require($CFG->dirroot.'/enrol/cohort/locallib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/blocks/training_report/api/helper.php');
global $DB;

$cohort_page_size = 15;

$contextid = optional_param('contextid', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$showall = optional_param('showall', false, PARAM_BOOL);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('courseenrol', 'tool_courseenrol'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/admin/tool/courseenrol/index.php');
$PAGE->requires->css('/admin/tool/courseenrol/css/style.css');

$PAGE->requires->js('/lib/mindatlas/jquery/jquery.min.js', true);
$PAGE->requires->js('/lib/mindatlas/chosen/chosen.jquery.js', true);
$PAGE->requires->css('/lib/mindatlas/chosen/chosen.css', true);

$contextid = optional_param('contextid', 0, PARAM_INT);
if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
} else {
    $context = context_system::instance();
}

if ($CFG->forcelogin) { 
    require_login(); 
}
admin_externalpage_setup('courseenrol_management');



if (empty($CFG->loginhttps)) {
    $securewwwroot = $CFG->wwwroot;
} else {
    $securewwwroot = str_replace('http:','https:',$CFG->wwwroot);
}


$params = array('page' => $page);
if ($contextid) {
    $params['contextid'] = $contextid;
}
 
$selected_category = "";
if(!isset($_POST['category'])){
    if(isset($_SESSION['categorycohort'])) $selected_category = $_SESSION['categorycohort'];
} else {
    $selected_category = $_POST['category'];
    $_SESSION['categorycohort'] = $_POST['category'];
}


if(isset($_POST['save_course_enrol'])){
    // Process all value
    $post = $_POST;
    if(isset($post['coursecohort'])){
        $coursecohort = $post['coursecohort'];

        $cohortenrolled = $post['cohortenrolled'];
        // echo "<pre>".print_r($coursecohort,true)."</pre>";
        // echo "<pre>".print_r($cohortenrolled,true)."</pre>";
        foreach($coursecohort as $cohortid=>$value){
            $cohortenrolled[$cohortid] = ",".$cohortenrolled[$cohortid];
            // $value is array of enrolled courses
            foreach($value as $courseid=>$v){
                if(strpos($cohortenrolled[$cohortid],','.$courseid.',')===false){
                    // The cohort is not in the enrolled course list. So, we need to enrol it here
                    //echo "<br>Enrolled cohortid: $cohortid - courseid: $courseid";
                     //echo "<br>Enrolled cohortid: $cohortid - courseid: $courseid";
                     enrol_cohort_course($cohortid,$courseid);
                } else {
                    // The cohort is already in the list. Therefore, remove course in the enrolled course list.
                    $cohortenrolled[$cohortid] = str_replace(','.$courseid.',',',', $cohortenrolled[$cohortid]);
                }
            }
            
        }

        // Unenrol other courses
       // echo "<pre>".print_r($cohortenrolled,true)."</pre>";
        foreach ($cohortenrolled as $cohortid => $courses) {
            if(trim($courses)!=','){ // check if there no course to unenrol
                $arr_courses = explode(",", $courses);
                foreach($arr_courses as $courseid){
                    // unenrol $cohortid $courseid
                    if($courseid!=null)
                        delete_cohort_course($cohortid,$courseid);
                        // echo "<br>== UnEnrolled cohortid: $cohortid - courseid: $courseid";
                        
                }
            }
        }
    } else { // in case delete all cohort 

        $cohortenrolled = $post['cohortenrolled'];
        foreach ($cohortenrolled as $cohortid => $courses) {
            if(trim($courses)!=','){ // check if there no course to unenrol
                $arr_courses = explode(",", $courses);
                foreach($arr_courses as $courseid){
                    // unenrol $cohortid $courseid
                    if($courseid!=null)
                        delete_cohort_course($cohortid,$courseid);
                        // echo "<br>== UnEnrolled cohortid: $cohortid - courseid: $courseid";
                }
            }
        }
    }

}

// Hide or un hide the cohort to course
if(isset($_GET['ins'])){
    $get = $_GET;
    $ins = $get['ins'];

    // update the instance to hide or show.
    if(isset($get['hide'])){
        $record = $DB->get_record('enrol',array('id'=>$ins));
        $record->status = 1;
        $DB->update_record('enrol',$record);
        //echo "Hide the instance: $ins";
    }
    if(isset($get['show'])){
        $record = $DB->get_record('enrol',array('id'=>$ins));
        $record->status = 0;
        $DB->update_record('enrol',$record);
        //echo "Show the instance: $ins";
    }

}


$params['tabcohort'] = true;
$cohorts = cohort_get_all_cohorts($page, $cohort_page_size, "");
// echo "<pre>".print_r($cohorts,true)."</pre>";
echo $OUTPUT->header();

$PAGE->requires->js('/admin/tool/courseenrol/textrotate.js');
$PAGE->requires->js_function_call('textrotate_init', null, true);

if(isset($_POST['save_course_enrol'])){
    echo get_string('savemessage','tool_courseenrol');
}

$baseurl = new moodle_url('/admin/tool/courseenrol/cohort.php', $params);
if ($editcontrols = courseenrol_edit_controls($context, $baseurl)) {
    echo $OUTPUT->render($editcontrols);
}

// $all_column_course = get_all_column_course();
$all_column_course = get_column_course($selected_category);

$data = array();
$editcolumnisempty = true;


    // ================ For choosing category of courses
    // Getting list of category in the databases
    // List of all categories from database
    $list_category_original = flatten_course_categories(getCourses_Category());
    $list_category [""] = "";
    foreach ($list_category_original as $key => $value) {
        if ($value->type !== 'category') {
            continue;
        }
        $list_category[$value->value] = $value->label;
    }


echo html_writer::start_tag('div',array('id'=>'couse_category','class'=>'coursecategory'));
echo html_writer::start_tag('form',array('action'=> new moodle_url('cohort.php'),'id'=>'frmcategory','method'=>'POST'));
echo get_string('selectcategory','tool_courseenrol');
echo html_writer::select($list_category,'category',$selected_category,false, array('class'=>'chzn-select'));
echo html_writer::empty_tag('input',array('type'=>'submit','name'=>'select_category','class'=>'cls_coursecategory','value'=>get_string('go', 'tool_courseenrol')));
echo html_writer::end_tag('form');
echo html_writer::end_tag('div');

// ==== End of Category area =============


$course_enrolled = "";
echo html_writer::start_tag('div',array('id'=>'main_area_courses','class'=>'mainareacouses'));
echo html_writer::start_tag('form',array('action'=> new moodle_url('cohort.php'),'id'=>'frm','method'=>'POST'));
echo html_writer::start_tag('div',array('class'=>'no-overflow'));
foreach($cohorts['cohorts'] as $cohort) {
    $line = array();
    $cohortcontext = context::instance_by_id($cohort->contextid);
    if ($showall) {
        if ($cohortcontext->contextlevel == CONTEXT_COURSECAT) {
            $line[] = html_writer::link(new moodle_url('/cohort/index.php' ,
                    array('contextid' => $cohort->contextid)), $cohortcontext->get_context_name(false));
        } else {
            $line[] = $cohortcontext->get_context_name(false);
        }
    }
    $cohortname_column = "<a href='$securewwwroot/cohort/index.php'>".format_string($cohort->name)."</a> ";
    $cohortname_column .=html_writer::link(new moodle_url($securewwwroot.'/cohort/assign.php',array('id'=>$cohort->id)),
                html_writer::empty_tag('img', array('src' =>'css/users.png', 'alt' => get_string('assign', 'core_cohort'), 'class' => 'iconsmall')),
                array('title' => get_string('assign', 'core_cohort'),'target'=>'_blank'));


    $line[] = $cohortname_column;
    //$line[] = s($cohort->idnumber); // All idnumbers are plain text.
    // Get all courses in the database
    //$urlparams = array('id' => $cohort->id, 'returnurl' => $baseurl->out_as_local_url());


    $enrolled_list =""; // Courses has been enrolled
    foreach ($all_column_course as $field) {
        $courseid = str_replace('course', '', $field);
        // Check cohort has been assigned to the course or not
        $enrol_row = $DB->get_record('enrol',array('enrol'=>'cohort','courseid'=>$courseid,'customint1'=>$cohort->id));
        $response = 'coursecohort['.$cohort->id.']['.$courseid.']'; // courseuser[2][4];

        if(!empty($enrol_row)){
            $enrolled_list .= $courseid.',';
            $str_course = html_writer::empty_tag('input',array('type'=>'checkbox','name'=>$response,'checked'=>'true'));

            $urlparams = array('id' => $cohort->id,'ins'=>$enrol_row->id);
            $showhideurl = new moodle_url('cohort.php', $urlparams + array('sesskey' => sesskey()));

            if($enrol_row->status=='0'){
                // Course has been available
                $showhideurl->param('hide', 1);
                $visibleimg = html_writer::empty_tag('img', array('src' => 'css/hide.png', 'alt' => get_string('hide'), 'class' => 'iconsmall'));
                $str_course .= html_writer::link($showhideurl, $visibleimg, array('title' => get_string('hide')));
            } else{
                // Course has been disable
                $showhideurl->param('show', 1);
                $visibleimg = html_writer::empty_tag('img', array('src' => 'css/show.png', 'alt' => get_string('show'), 'class' => 'iconsmall'));
                $str_course .= html_writer::link($showhideurl, $visibleimg, array('title' => get_string('show')));

            }
            //$str_course = "TEST 1";
        } else {
            $str_course = html_writer::empty_tag('input',array('type'=>'checkbox','name'=>$response));
        }
        $line [] = $str_course;
    }
    $course_enrolled .= html_writer::empty_tag('input',array('type'=>'hidden','name'=>'cohortenrolled['.$cohort->id.']','value'=>$enrolled_list));

    $data[] = $row = new html_table_row($line);
    if (!$cohort->visible) {
        $row->attributes['class'] = 'dimmed_text';
    }
}
$table = new html_table();

$table->head  = array(get_string('cohortname', 'tool_courseenrol'));
// $table->colclasses = array('leftalign name', 'leftalign id', 'leftalign description', 'leftalign size','centeralign source');
// Get all Course title:
foreach($all_column_course as $row){
    $courseid = courseid_column($row);
    $coursename = get_user_course_string($row);
    $course_column = "<a href='$securewwwroot/enrol/instances.php?id=$courseid' title='Go to ".$coursename." enrolment methods'><div class='rotatetext'><span>".shortern_course_name($coursename)."</span></div></a> ";
    // $course_column = "<a href='$securewwwroot/enrol/instances.php?id=$courseid' title='Go to ".$coursename." enrolment methods'><span class='completion-activityname'>".shortern_course_name($coursename)."</span></a> ";
    $table->head [] = $course_column;
}



$table->id = 'coursecohorts';
$table->attributes['class'] = 'generaltable rotate';
$table->data  = $data;
echo html_writer::table($table);
echo $OUTPUT->paging_bar($cohorts['totalcohorts'], $page, $cohort_page_size, $baseurl);
echo $course_enrolled;
echo html_writer::end_tag('div');
echo html_writer::empty_tag('input',array('type'=>'submit','name'=>'save_course_enrol','value'=>get_string('save', 'tool_courseenrol')));
echo html_writer::end_tag('form');
echo html_writer::end_tag('div');


echo $OUTPUT->footer();
?>

<script>
    $(document).ready(function(){
        $('.chzn-select').chosen({search_contains: true});
    })
</script>
