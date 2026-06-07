<?php

global $CFG, $DB, $USER;

class mindatlas_plugin_library {

    public function __construct()
    {

    }
    public function verify_session($payload){
        global $USER;
        if(isset($payload['sesskey']) && $payload['sesskey'] == $USER->sesskey)
            return true;
        else return false;        
    }
    public function get_course_info($payload){
        global $DB, $PAGE,$CFG;
        $data = [];

        if($this->verify_session($payload)){
            $courseId = $payload["courseId"];
            $course = $DB->get_record('course', array('id'=>$courseId));
            if(!empty($course)){
                $data["fullname"] = $course->fullname;
                $data["summary"] = $course->summary;
            }
            $data["image"] = $CFG->wwwroot . '/theme/generic/pix/default-course.jpg';
            $course_image =  $this->get_course_url($courseId);
            if($course_image){
                $data["image"] = $course_image;
            }
        }
        
        // Please get course image url 
        //if image not existed then use default-course.jpg under theme/generic/pix;
        // $data->image = '/generic/theme/generic/pix/default-course.jpg'
        return $data;
    }
    
    public function get_user_profile($payload){
        global $DB, $PAGE;
        $data = [];
        $userid = $payload['userid'];
        $user = $DB->get_record('user', ['id'=>$userid,'deleted'=>0]);
        if(!empty($user)){
            $userpicture = new user_picture($user);
            $userpicture = (string)($userpicture->get_url($PAGE));
            $data =  [
                'id'=>$userid,
                'avatar' => $userpicture
            ];
        }
        return $data;
    }
    public function get_user_badges($payload) {
        global $CFG;
        require_once($CFG->dirroot.'/lib/badgeslib.php');
        $data = array(
            'total'=>0,
            'badges' => []
        );
        if(!isset($payload['userid'])) 
            $userid = $USER->id;
        else 
            $userid = $payload['userid'];

        if($this->verify_session($payload)){

            $badges = badges_get_user_badges($userid);
            foreach($badges as $badge) {
                // skip invisible badge
                if(!$badge->visible) {
                    continue;
                }

                // add badge image url
                $context = ($badge->type == BADGE_TYPE_SITE) ? context_system::instance() : context_course::instance($badge->courseid);
                $image_url = moodle_url::make_pluginfile_url($context->id, 'badges', 'badgeimage', $badge->id, '/', 'f1', false);
                $info_url = new moodle_url('/badges/badge.php', array('hash' => $badge->uniquehash));
                $data['badges'][] = (object)[
                    'name'=>$badge->name,
                    'image'=>$image_url->out(),
                    'link' => $info_url->out()
                ];
             }
             $data['total'] = count($badges);
        }
        return $data;
    }

    public function get_user_course_summary($payload){
        global $USER,$DB;
        $data_user = $this->get_user_learning_progress($payload);
        //default data
        $data =[ 
            "overall_progress" => 100,
            "enrolled_course_num" => 0,
            "enrolled_percent" => 100,
            "inprogress_course_num" => 0,
            "inprogress_percent" => 0,
            "overdue_course_num" => 0,
            "overdue_percent" => 0,
            "completed_course_num" => 0,
            "completed_percent" => 100
        ];
        if(!empty($data_user)){
            $data['overall_progress'] = $data_user['progress'];
            $data['enrolled_percent'] = $data_user['progress'];
            $data['enrolled_course_num'] = count($data_user['completed']) + count($data_user['not_completed']);
            $data['completed_course_num'] = count($data_user['completed']);
            $data['completed_percent'] = $data_user['progress'];
            
            //Get other data
            $summary = [
                'total_no_overdue'=>0,
                'total_no_overdue_incompleted'=>0,
                'total_overdue'=>0,
                'total_incompleted_overdue'=>0
            ];
            foreach ($data_user['completed'] as $course) {
                if($course->isoverdue==true){
                    $summary['total_overdue']++;
                }else{
                    $summary['total_no_overdue']++;
                }
            }
            foreach ($data_user['not_completed'] as $course) {
                if($course->isoverdue==true){
                    $summary['total_overdue']++;
                    $summary['total_incompleted_overdue']++;
                }else{
                    $summary['total_no_overdue']++;
                    $summary['total_no_overdue_incompleted']++;
                }
            }

            $data['inprogress_course_num'] = $summary['total_no_overdue_incompleted'];
            if($summary['total_no_overdue']==0) $data['inprogress_percent'] = 0;
            else
                $data['inprogress_percent'] = round($summary['total_no_overdue_incompleted'] / $summary['total_no_overdue'] * 100);

            $data['overdue_course_num'] = $summary['total_incompleted_overdue'];
            if ($summary['total_overdue'] !== 0) {
                $data['overdue_percent'] = round($summary['total_incompleted_overdue'] / $summary['total_overdue'] * 100);
            } else {
                $data['overdue_percent'] = 0;
            }
        }

        // $data =[ 
        //     "overall_progress" => 80,
        //     "enrolled_course_num" => 12,
        //     "enrolled_percent" => 50,
        //     "inprogress_course_num" => 5,
        //     "inprogress_percent" => 80,
        //     "overdue_course_num" => 6,
        //     "overdue_percent" => 40,
        //     "completed_course_num" => 2,
        //     "completed_percent" => 30
        // ];
        // 
        return $data;
    }



    public function get_user_learning_progress($payload) {
        global $DB, $USER, $CFG;
        require_once __DIR__ . '/mindatlas_setdeadline_library.php';
        //extra 'overdue' needed
        $data = array(
            'progress'=> 0,
            'nodeadline' => 0,
            'completed' => [],
            'completed_deadline' => [],
            'not_completed' => [],
            'not_completed_deadline' => []
        );
        if(!isset($payload['userid'])){
            $userid = $USER->id;
        }else{
            $userid = $payload['userid'];
        }


        if($this->verify_session($payload)){
            /*$rs_enrolled_courses = $DB->get_records_sql("SELECT DISTINCT e.courseid as course_id,c.fullname as coursename, c.category as category_id,c.shortname,c.idnumber,c.summary,c.visible,c.sortorder,min(ue.timecreated) as enrol_time FROM mdl_enrol e INNER JOIN mdl_user_enrolments ue on ue.enrolid=e.id INNER JOIN mdl_course c on c.id=e.courseid WHERE ue.status=0 and e.status=0 and c.visible=1 and ue.userid=? GROUP BY e.courseid ORDER BY c.sortorder ASC",[638]);*/



        $sql = "SELECT DISTINCT e.courseid AS course_id, c.fullname AS coursename,
               c.category AS category_id, c.shortname, c.idnumber, 
               c.summary, c.visible, c.sortorder, MIN(ue.timecreated) AS enrol_time
        FROM {enrol} e
        INNER JOIN {user_enrolments} ue ON ue.enrolid = e.id
        INNER JOIN {course} c ON c.id = e.courseid
        WHERE ue.status = :ue_status 
          AND e.status = :e_status 
          AND c.visible = :visible 
          AND ue.userid = :userid
        GROUP BY e.courseid, c.fullname, c.category, c.shortname, 
                 c.idnumber, c.summary, c.visible, c.sortorder
        ORDER BY c.sortorder ASC";

        $params = [
            'ue_status' => 0,
            'e_status'  => 0,
            'visible'   => 1,
            'userid'    => (int)$userid
        ];

        $rs_enrolled_courses = $DB->get_records_sql($sql, $params);

            if(!empty($rs_enrolled_courses)){
                //The user are enrolled to at least one course
                $overdue = new mindatlas_setdeadline_library();
                $user_tick_box_total = 0; //total tick boxes for the user
                $user_tick_box_completion_total = 0; // total completed tick boxes for the user

                foreach ($rs_enrolled_courses as $course) {
                    if(is_null($course->course_id)) continue;
                    //overdue course
                    $course_duedate = $overdue->get_user_course_duedate($course->course_id);
                    if (empty($course_duedate)) { 
                        $course->isoverdue = false;
                    } else {
                        $course->isoverdue = $course_duedate <= time();
                    }
                    $tick_box_total = 0;
                    $tick_box_completed_total = 0;

                    $course_modules_records = $DB->get_records('course_modules', array('course'=>$course->course_id, 'visible'=>'1', 'deletioninprogress'=>'0'));                          
                    if ($course_modules_records != false)  {

                        //Start CourseModule ForEach Loop
                        foreach ($course_modules_records as $course_modules_record) {
                            $tick_box_type = $course_modules_record->completion;
                            
                            // Activity Completion Tracking
                            // 0 - Do not indicate activity completion (no tick box)
                            // 1 - Students can manually mark the activity as completed (solid line tick box)
                            // 2 - Show activity as complete when conditions are met (dotted line tick box)

                            if ($tick_box_type == 1 || $tick_box_type == 2) {
                                $tick_box_total++;

                                $course_modules_completion_records = $DB->get_records('course_modules_completion', array('coursemoduleid'=>$course_modules_record->id, 'userid'=>$userid));
                                if ($course_modules_completion_records != false) {
                                    foreach ($course_modules_completion_records as $course_modules_completion_record) {
                                        if ($course_modules_completion_record->completionstate == 1 || $course_modules_completion_record->completionstate == 2) {
                                            $tick_box_completed_total++;
                                        }
                                    }
                                }
                            }

                        //End CourseModule ForEach Loop
                        }
                        $user_tick_box_total += $tick_box_total;
                        $user_tick_box_completion_total += $tick_box_completed_total;
                    }
                    //Calculate progress
                    $progress = 0;
                    if($tick_box_completed_total!=0){
                        $progress = round($tick_box_completed_total / $tick_box_total * 100);
                    }
                    $course->progress = $progress;

                    //Get course image
                    // $course->course_image = $CFG->wwwroot.'/theme/generic/pix/default-course.jpg';
                    // var_dump($this->get_course_url($course->course_id));die();
                    $course->course_image = $this->get_course_url($course->course_id);
                    if($course->course_image == ''){
                        $course->course_image = $CFG->wwwroot.'/theme/generic/pix/default-course.jpg';
                    }


                    //Get course rate - to be implement

                    if($tick_box_total == $tick_box_completed_total){
                        $data['completed'] [] = $course; // DO NOT remove this line, related to dashboard course display
                        if (!empty($course_duedate)){
                            $data['completed_deadline'] [] = $course;
                            $completed_deadline_course_count++;
                            $completed_deadline_module_count += $tick_box_completed_total;
                            $deadline_module_count += $tick_box_total;
                        }
                    }
                    else {
                        $data['not_completed'] [] = $course; // DO NOT remove this line, related to dashboard course display
                        if (!empty($course_duedate)){
                            $data['not_completed_deadline'] [] = $course;
                            $not_completed_deadline_course_count++;
                            $completed_deadline_module_count += $tick_box_completed_total;
                            $deadline_module_count += $tick_box_total;
                        }
                    }
                }

                // User progress by completed module vs. all module
                //If No Tickboxes then show as 0% - Overcome Divide By Zero
                // if ($user_tick_box_total == 0) {
                //     $percentage = 0;
                //     $data['nodeadline'] = 1;
                // }else{
                //     $percentage = round($user_tick_box_completion_total / $user_tick_box_total * 100);
                // }

                // User progress by completed deadline module vs. all deadline module
                if (empty($data['not_completed_deadline']) && empty($data['completed_deadline'])) {
                    $percentage = 0;
                    $data['nodeadline'] = 1;
                }else{
                    // $percentage = round($completed_deadline_course_count / ($not_completed_deadline_course_count + $completed_deadline_course_count) * 100);
                    $percentage = round($completed_deadline_module_count / $deadline_module_count * 100);
                }
                $data['progress'] = $percentage;
            }
        }

        return $data;
    }

    public function get_course_url($courseid){
        global $CFG;
        foreach ($this->get_course_overviewfiles($courseid) as $file) {
            $isimage = $file->is_valid_image();
            if($isimage) {
                return file_encode_url("$CFG->wwwroot/pluginfile.php",
                    '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                    $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
            }
        }

        return "";
    }
    
    public function get_course_overviewfiles($courseid) {
        global $CFG;
        if (empty($CFG->courseoverviewfileslimit)) {
            return array();
        }
        require_once($CFG->libdir. '/filestorage/file_storage.php');
        require_once($CFG->dirroot. '/course/lib.php');
        $fs = get_file_storage();
        $context = context_course::instance($courseid);
        $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', false, 'filename', false);
        if (count($files)) {
            $overviewfilesoptions = course_overviewfiles_options($courseid);
            $acceptedtypes = $overviewfilesoptions['accepted_types'];
            if ($acceptedtypes !== '*') {
                // Filter only files with allowed extensions.
                require_once($CFG->libdir. '/filelib.php');
                foreach ($files as $key => $file) {
                    if (!file_extension_in_typegroup($file->get_filename(), $acceptedtypes)) {
                        unset($files[$key]);
                    }
                }
            }
            if (count($files) > $CFG->courseoverviewfileslimit) {
                // Return no more than $CFG->courseoverviewfileslimit files.
                $files = array_slice($files, 0, $CFG->courseoverviewfileslimit, true);
            }
        }
        return $files;
    }

}