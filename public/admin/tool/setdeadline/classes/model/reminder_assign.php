<?php
namespace tool_setdeadline\model;

class reminder_assign extends base {
    protected $fields = ['id', 'reminder_id', 'type', 'instanceid', 'courseid'];

    public static function get_types() {
        return [
            'course' => get_string('course'),
            'cohort' => get_string('cohort', 'core_cohort')
        ];
    }

    public static function delete_for_reminder($reminder_id) {
        global $DB;

        return $DB->delete_records(static::get_table(), [
            'reminder_id' => $reminder_id
        ]);
    }
    public static function course_reminder_check_conflict($courseid,$type,$reminderid=0){
        global $DB;
        $message="";
        $coursename = $DB->get_field('course','fullname',array('id'=>$courseid));
        if($reminderid==0){//New reminder is created
            if($DB->record_exists('course_reminder',array('courseid'=>$courseid,'type'=>$type))){
                $new = (object)array('coursename'=>$coursename,'type'=>$type);
                $message = get_string('conflict:newexistingreminder','tool_setdeadline',$new);
            }
        }else{//Existing reminder
            if(!$DB->record_exists('course_reminder',array('courseid'=>$courseid,'type'=>$type,'id'=>$reminderid))){//Check if the reminder has changed setting: course, deadlinetype, reminderid
                if($DB->record_exists('course_reminder',array('courseid'=>$courseid,'type'=>$type))){
                    $new = (object)array('coursename'=>$coursename,'type'=>$type);
                    $message = get_string('conflict:updateexistingreminder','tool_setdeadline',$new);
                }
            }
        }
        return $message;
    }
    //Verify if users are enrolled to course, or the cohort is enrolled to course.
    public static function course_reminder_verify_data(&$data,&$error){
        global $DB,$CFG;
        $message="";
        $unenroll_users = array();
        $unenroll_cohorts = array();
        $courseid = $data->course;
        $coursename = $DB->get_field('course','fullname',array('id'=>$courseid));
        $coursename = "<a href='".$CFG->wwwroot.'/course/view.php?id='.$courseid."' target='_blank'>".$coursename."</a>";
        switch ($data->deadline_type) {
            case 'user':
                foreach ($data->user as $key=>$userid) {
                    if(!$DB->record_exists_sql("SELECT 'x' from mdl_enrol e inner join mdl_user_enrolments ue on e.id=ue.enrolid where e.status=0 and e.courseid=? and ue.userid=?",array($courseid,$userid))){
                        $unenroll_users [] = $userid;
                        unset($data->user[$key]);
                    }
                }
                if(empty($data->user)) $error = true;
                break;
            case 'cohort':
                foreach ($data->cohort as $key=>$cohortid) {
                    if(!$DB->record_exists('enrol',array('enrol'=>'cohort','courseid'=>$courseid,'status'=>0))){
                        $unenroll_cohorts [] = $cohortid;
                        unset($data->cohort[$key]);
                    }
                }
                if(empty($data->cohort)) $error = true;
                break;
        }
        if(!empty($unenroll_cohorts)){
            $message.=get_string('cohort:removed','tool_setdeadline',$coursename);
            foreach ($unenroll_cohorts as $cohortid) {
                $cohortname = $DB->get_field('cohort','name',array('id'=>$cohortid));
                $message.="<li>".$cohortname."</li>";
            }
        }
        if(!empty($unenroll_users)){
            $message.=get_string('user:removed','tool_setdeadline',$coursename);
            foreach ($unenroll_users as $userid) {
                $user = $DB->get_record('user',array('id'=>$userid));
                $message.="<li>".fullname($user)."</li>";
            }
        }
        return $message;
    }
}