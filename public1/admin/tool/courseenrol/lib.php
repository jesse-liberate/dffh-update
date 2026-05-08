<?php 
defined('MOODLE_INTERNAL') || die();

function shortern_course_name($coursename){
  $num_limit = 30; // default Limited number of charactor showing in course name
  $str = $coursename;
  if(strlen($coursename)>$num_limit){
    $str = substr($coursename,0,$num_limit)."..";
  }
  return $str;  
}

function is_cohort_course($cohortid,$courseid){
    global $DB;

    $enrolled = 0; // Default cohort is not enrolled into the course
    $enrol_row = $DB->get_record('enrol',array('enrol'=>'cohort','courseid'=>$courseid,'customint1'=>$cohortid));
    if(!empty($enrol_row)){
        if($enrol_row->status=='0'){
            $enrolled = 1; // Cohort is enrolled for the course
        }else {
            $enrolled = 2; // Cohort is enrolled for the course( but is deactived ) not in used
        }
        return $enrolled;
    }
    return $enrolled;
}
function delete_cohort_course($cohortid,$courseid){
    global $DB;
    // find the instance id first
    $instanceid = $DB->get_field('enrol','id',array('enrol'=>'cohort','courseid'=>$courseid,'customint1'=>$cohortid));
    $instances = enrol_get_instances($courseid, false);
    $plugins   = enrol_get_plugins(false);

    $instance = $instances[$instanceid];
    $plugin = $plugins[$instance->enrol];

    if ($plugin->can_delete_instance($instance)) {
        // if ($confirm) {
            // if (enrol_accessing_via_instance($instance)) {
            //     if (!$confirm2) {
            //         $yesurl = new moodle_url('/enrol/instances.php',
            //                                  array('id' => $courseid,
            //                                        'action' => 'delete',
            //                                        'instance' => $instance->id,
            //                                        'confirm' => 1,
            //                                        'confirm2' => 1,
            //                                        'sesskey' => sesskey()));
            //         $displayname = $plugin->get_instance_name($instance);
            //         $message = markdown_to_html(get_string('deleteinstanceconfirmself',
            //                                                'enrol',
            //                                                array('name' => $displayname)));
            //         // echo $OUTPUT->header();
            //         // echo $OUTPUT->confirm($message, $yesurl, $PAGE->url);
            //         // echo $OUTPUT->footer();
            //         // die();
            //     }
            // }
            $plugin->delete_instance($instance);
            //redirect($PAGE->url);
        }

      // //  echo $OUTPUT->header();
      //   $yesurl = new moodle_url('/enrol/instances.php',
      //                            array('id' => $courseid,
      //                                  'action' => 'delete',
      //                                  'instance' => $instance->id,
      //                                  'confirm' => 1,
      //                                  'sesskey' => sesskey()));
      //   $displayname = $plugin->get_instance_name($instance);
      //   $users = $DB->count_records('user_enrolments', array('enrolid' => $instance->id));
      //   if ($users) {
      //       $message = markdown_to_html(get_string('deleteinstanceconfirm', 'enrol',
      //                                              array('name' => $displayname,
      //                                                    'users' => $users)));
      //   } else {
      //       $message = markdown_to_html(get_string('deleteinstancenousersconfirm', 'enrol',
      //                                              array('name' => $displayname)));
      //   }
        //echo $OUTPUT->confirm($message, $yesurl, $PAGE->url);
       // echo $OUTPUT->footer();
       // die();
   // }
}

function enrol_cohort_course($cohortid,$courseid){
    global $DB;
if (!enrol_is_enabled('cohort')) {
        // Not enabled.
        return false;
    }

    if ($DB->record_exists('enrol', array('enrol'=>'cohort','courseid'=>$courseid,'customint1'=>$cohortid))) {
        // The course already has a cohort enrol method.
        return false;
    } else{

        // Get the cohort enrol plugin
        $enrol = enrol_get_plugin('cohort');

        // Get the course record.
        $course = $DB->get_record('course', array('id' => $courseid));
        $cohortname = $DB->get_field('cohort','name',array('id'=>$cohortid));
        // Add a cohort instance to the course.
        $instance = array();
        $instance['name'] = $cohortname;
        $instance['status'] = ENROL_INSTANCE_ENABLED; // Enable it.
        $instance['customint1'] = $cohortid; // Used to store the cohort id.
        $instance['roleid'] = $enrol->get_config('roleid'); // Default role for cohort enrol which is usually student.
        $instance['customint2'] = 0; // Optional group id.
        $enrol->add_instance($course, $instance);

        // Sync the existing cohort members.
        $trace = new null_progress_trace();
        enrol_cohort_sync($trace, $course->id);
        $trace->finished();
    }
}

function courseid_column($column){
    $courseid = str_replace("course", "", $column);
    return $courseid;
}

function get_all_course(){
    global $DB;
    $arr_course_name = array();
    $courses = $DB->get_records('course',array());
    if(!empty($courses)){
        foreach ($courses as $row) {
            if($row->category!=0){
            // Except the inital course for the Moodle
                $arr_course_name[(string)$row->id] = $row->fullname;
                //echo $row->id;
                // $arr_course_name [$row->id] = $row->shortname;
            }
        }
    }
    return $arr_course_name;
}
function get_all_column_course(){
    global $DB;
    $arr_course_name = array();
    $courses = $DB->get_records('course',array());
    if(!empty($courses)){
        foreach ($courses as $row) {
            if($row->category!=0){
            // Except the inital course for the Moodle
                $arr_course_name[] = 'course'.$row->id;
                //echo $row->id;
                // $arr_course_name [$row->id] = $row->shortname;
            }
        }
    }
    return $arr_course_name;
}
function get_all_course_without_manual(){
    global $DB;
    $method = $DB->get_records('enrol',array('enrol'=>'manual','status'=>'0'));
    return $method;
}
//Get columns courses in the selected course or category
// course: id
//Category: {}categoryid
function get_column_course($selected_category){
    global $DB;
    $arr_course_name = array();
    if($selected_category=="" || $selected_category=="0"){ 
        $courses = $DB->get_records('course',array());
    }else {//Get all courses in this category
        if(strpos($selected_category,'{category}')===false) {
            $courses = $DB->get_records('course',array('id'=>$selected_category));
        }else{
            $categoryid = trim(str_replace('{category}','',$selected_category));
            $path = $DB->get_field('course_categories','path',array('id'=>$categoryid));
            $childs = $DB->get_records_sql("select * from mdl_course_categories where path like '$path%'",array());
            $arr_category = array();
            foreach($childs as $row){
                $arr_category[] = $row->id;
            }
            $courses = $DB->get_records_list('course','category',$arr_category);
        }
    }

    if(!empty($courses)){
        foreach ($courses as $row) {
            if($row->category!=0){
            // Except the inital course for the Moodle
                $arr_course_name[] = 'course'.$row->id;
                //echo $row->id;
                // $arr_course_name [$row->id] = $row->shortname;
            }
        }
    }
    return $arr_course_name;
}
function get_user_course_string($key){
global $DB;
$exception_arr = array('firstname','lastname'); // to avoid the error if using function get_string
    $string = "";
    if(empty($key)) return;
    else
    {
        if(in_array($key, $exception_arr))
                $string = get_string($key,'tool_courseenrol');
        else{
            $courseid = str_replace('course', '', $key);
            $string = $DB->get_field('course','shortname',array('id'=>$courseid));
            //$string = $DB->get_field('course','fullname',array('id'=>$courseid));
        }

    }
    return $string;
}

function courseenrol_edit_controls(context $context, moodle_url $currenturl) {
    $tabs = array();

    $individualurl = new moodle_url('individual.php',array('contextid'=>$context->id));
	$tabs[] = new tabobject('tabindividual',$individualurl,get_string('tabindividual','tool_courseenrol'));

    $cohorturl = new moodle_url('cohort.php',array('contextid'=>$context->id));
    $tabs[] = new tabobject('tabcohort',$cohorturl,get_string('tabcohort','tool_courseenrol'));

    if($currenturl->get_param('tabindividual'))
	   $currenttab = 'tabindividual';
    else $currenttab = 'tabcohort';
    // $currenttab = 'view';
    // $viewurl = new moodle_url('/courseenrol/index.php', array('contextid' => $context->id));
    // if (($searchquery = $currenturl->get_param('search'))) {
    //     $viewurl->param('search', $searchquery);
    // }
    // if ($context->contextlevel == CONTEXT_SYSTEM) {
    //     $tabs[] = new tabobject('view', new moodle_url($viewurl, array('showall' => 0)), get_string('systemcohorts', 'cohort'));
    //     $tabs[] = new tabobject('viewall', new moodle_url($viewurl, array('showall' => 1)), get_string('allcohorts', 'cohort'));
    //     if ($currenturl->get_param('showall')) {
    //         $currenttab = 'viewall';
    //     }
    // } else {
    //     $tabs[] = new tabobject('view', $viewurl, get_string('cohorts', 'cohort'));
    // }
    // if (has_capability('moodle/cohort:manage', $context)) {
    //     $addurl = new moodle_url('/cohort/edit.php', array('contextid' => $context->id));
    //     $tabs[] = new tabobject('addcohort', $addurl, get_string('addcohort', 'cohort'));
    //     if ($currenturl->get_path() === $addurl->get_path() && !$currenturl->param('id')) {
    //         $currenttab = 'addcohort';
    //     }

    //     $uploadurl = new moodle_url('/cohort/upload.php', array('contextid' => $context->id));
    //     $tabs[] = new tabobject('uploadcohorts', $uploadurl, get_string('uploadcohorts', 'cohort'));
    //     if ($currenturl->get_path() === $uploadurl->get_path()) {
    //         $currenttab = 'uploadcohorts';
    //     }
    // }
    if (count($tabs) > 1) {
        return new tabtree($tabs, $currenttab);
    }
    return null;
}

function is_user_enrolled($userid,$courseid){
  global $DB;
  $sql = "select e.id from mdl_user_enrolments ue,mdl_enrol e where ue.enrolid=e.id and ue.userid=$userid and e.courseid=$courseid";
  if($DB->record_exists_sql($sql,array())){
    
    return true;
    }
  else false;
  return false;
}

function flatten_course_categories($categories) {
    $queue = $categories;
    $results = [];
    while (!empty($queue)) {
        $cat = array_pop($queue);
        $results[] = $cat;
        if (!empty($cat->children)) {
            foreach ($cat->children as $child) {
                if ($child->type === 'category') {
                    $queue[] = $child;
                }
            }
        }
    }
    return $results;
}

