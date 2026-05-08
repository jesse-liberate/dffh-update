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
    public function get_user_course_duedate($courseid,$userid=""){
        global $DB,$USER,$CFG;
        require_once($CFG->dirroot. '/admin/tool/setdeadline/lib.php');

        if($userid=="") $userid = $USER->id;
        $data = array();

        $duedate_reminder = get_coursereminder_user_specific($USER->id,$courseid);
        if($duedate_reminder!=false && isset($duedate_reminder['duedate'])){ 
            $data = $duedate_reminder['duedate'];
        }
        return $data;
    }
}