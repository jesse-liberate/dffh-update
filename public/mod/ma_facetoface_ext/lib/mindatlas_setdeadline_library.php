<?php

global $CFG, $DB, $USER;

class mindatlas_setdeadline_library {

    public function __construct()
    {

    }
    
    
    public function verify_session($payload){
        global $USER;
        if(isset($payload['sesskey']) && $payload['sesskey'] == $USER->sesskey)
            return true;
        else return false;        
    }    
    
    function get_user_enroled_date($userid,$courseid){
        global $DB;
        $record = $DB->get_record_sql('SELECT MAX(ue.timecreated) as enrolleddate FROM mdl_user_enrolments as ue inner join
    mdl_enrol e ON e.id = ue.enrolid where e.courseid=? and ue.userid=? group by ue.userid',array($courseid,$userid));
        if(empty($record)) return '';
        else return $record->enrolleddate;
    }
    
    function is_user_enrol_by_cohort_course($userid,$courseid,$cohorts_list){
        global $DB;
    
        return $DB->record_exists_sql("SELECT ue.userid FROM mdl_user_enrolments as ue inner join
    mdl_enrol e ON e.id = ue.enrolid where e.courseid=? and ue.userid=? and e.enrol='cohort' and e.status=0 and e.customint1 in(".$cohorts_list.")",array($courseid,$userid));
    }
    
    function get_user_course_reminder($userid,$courseid){
        global $DB;
        //Based on the priority
        //1. User
        //2. Cohort
        //3. Course
        //Check user case
        $user = new stdClass();
        $user->userid = $userid;
        $user->courseid = $courseid;
    
        $user_record = $DB->get_record('reminder_override',array('userid'=>$userid,'courseid'=>$courseid));
        $course_record = $DB->get_record('course_reminder',array('courseid'=>$courseid,'type'=>'course'));
        if(!empty($user_record)){//Will be user case
            $user->firstreminder = intval($user_record->firstreminder); //Specific date
            $user->secondreminder = intval($course_record->secondreminder);
            $user->secondgap = intval($course_record->secondreminder);
            $user->repeated = $course_record->repeated;
            $user->manager = $course_record->manager;
            $user->siteadmin = $course_record->siteadmin;
            $user->emailadminlist = $course_record->emailadminlist;
            $user->type = 1;
        }else{//Check cohort case
            $cohort_record = $DB->get_record('course_reminder',array('courseid'=>$courseid,'type'=>'cohort'));
            $enrolleddate = $this->get_user_enroled_date($userid,$courseid);
            if($enrolleddate=='') return false; //User has not enrolled to the course.
            if(empty($course_record)&&empty($cohort_record)) return false; // No deadline for the user course
            $course_enrolleddate = intval($enrolleddate);
            //Check if users are part of any cohort enrolment for this course.
    
            if(!empty($cohort_record) && $this->is_user_enrol_by_cohort_course($userid,$courseid,$cohort_record->instanceid)){//Cohort case
                $reminder_timecreated = intval($cohort_record->timecreated);
                if($reminder_timecreated >= $course_enrolleddate) $course_enrolleddate = $reminder_timecreated;
    
                $user->firstreminder = intval($cohort_record->firstreminder) + intval($course_enrolleddate); // Enrolled date + first reminder gap
                $user->secondreminder = intval($cohort_record->secondreminder);
                $user->secondgap = intval($cohort_record->secondreminder);
                $user->repeated = $cohort_record->repeated;
                $user->manager = $cohort_record->manager;
                $user->siteadmin = $cohort_record->siteadmin;
                $user->emailadminlist = $cohort_record->emailadminlist;
                $user->type = 2;
            }else{//Course case
                if(empty($course_record)) return false;
                $reminder_timecreated = intval($course_record->timecreated);
                if($reminder_timecreated >= $course_enrolleddate) $course_enrolleddate = $reminder_timecreated;
    
                $user->firstreminder = intval($course_record->firstreminder) + $course_enrolleddate; // Enrolled date + first reminder gap
                $user->secondreminder = intval($course_record->secondreminder);
                $user->secondgap = intval($course_record->secondreminder);
                $user->repeated = $course_record->repeated;
                $user->manager = $course_record->manager;
                $user->siteadmin = $course_record->siteadmin;
                $user->emailadminlist = $course_record->emailadminlist;
                $user->type = 3;
            }
        }
        return $user;
    }    
    
    function get_coursereminder_user_specific($userid,$courseid){
        $reminder = $this->get_user_course_reminder($userid,$courseid);
        if($reminder){
            return array('duedate'=>($reminder->firstreminder + $reminder->secondreminder),
                'type'=>$reminder->type);
        }else return false;
    }    
    
    public function get_user_course_duedate($courseid,$userid=""){
        global $DB,$USER,$CFG;
        require_once($CFG->dirroot. '/admin/tool/setdeadline/lib.php');

        if($userid=="") $userid = $USER->id;
        $data = array();

        $duedate_reminder = $this->get_coursereminder_user_specific($USER->id,$courseid);
        if($duedate_reminder!=false && isset($duedate_reminder['duedate'])){ 
            $data = $duedate_reminder['duedate'];
        }
        return $data;
    }
    
}