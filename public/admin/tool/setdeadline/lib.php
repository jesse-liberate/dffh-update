<?php
/**
*   get all the cousre that does not exist in course_reminder
*/

function getCourses_reminder() {
    global $DB;

    $courses = $DB->get_records_sql('SELECT c.id, c.fullname FROM {course} c
        LEFT JOIN {course_reminder} r
        ON c.id = r.courseid
        WHERE  r.courseid IS NULL AND c.category<>0 
        ORDER BY c.fullname ASC', array());

    $data = array();

    foreach ($courses as $course) {
        $data[$course->id] = $course->fullname;
    }

    return $data;
}

function get_get_get($value) {
    $ret = '';
    if(isset($_GET[$value])) {
        $ret = $_GET[$value];
    }
    return $ret;
}

function get_deadlines() {
    global $DB;
    return $DB->get_records_sql('SELECT * FROM {course_reminder} AS r');
}
function get_full_datelines(){
    global $DB;
    return $DB->get_records_sql('SELECT r.id, r.firstreminder, r.secondreminder, r.manager, r.siteadmin, r.emailadminlist, r.timecreated,r.repeated, r.type as deadlinetype,r.courseid, r.modifiedby, c.fullname as coursename  
        FROM mdl_course_reminder AS r left join mdl_course c on c.id=r.courseid WHERE r.id is not null GROUP BY r.id ORDER BY c.fullname ASC,r.type ASC');
}

function get_manager_coursedeadlines(){
    global $DB;
    return $DB->get_records_sql('SELECT r.id, r.firstreminder, r.secondreminder, r.timecreated,r.repeated,r.courseid, c.fullname as coursename  
        FROM mdl_course_reminder_mgr AS r 
        LEFT JOIN mdl_course c on c.id=r.courseid 
        WHERE r.id is not null GROUP BY r.id ORDER BY c.fullname ASC');    
}

/**
 * Return existing user deadlines of a course.
 * @param   int     $courseid
 * @param   string  $fields 
 * @throws  DBException
 * @return  found records or false if none found
 */
function getCourseUserDeadlines($courseid = null, 
                                $fields = '*') {
  global $DB;
  
  $sql = <<< EOT
  select $fields
    from {reminder_override} ro
    left join {user} u on ro.userid = u.id
    left join {course_reminder} cr on cr.id = ro.reminder_id
    where ro.courseid = ?
    order by u.firstname, u.lastname
EOT;
  return $DB->get_records_sql($sql, array($courseid));
}

/**
 * Return reminder_override record
 * @param int $courseid
 * @param int $userid
 * @return bool
 */
function getReminderOverride($courseid, $userid) {
  global $DB;
  return $DB->get_record('reminder_override', array(
    'userid' => $userid,
    'courseid' => $courseid
  ));
}

/**
 * Return course reminder record
 * @param int $id reminder id
 * @return found record or false if not found
 */
function getCourseReminder($id) {
  global $DB;
  return $DB->get_record('course_reminder', array(
    'id' => $id
  ));
}

function getCourseId($name) {
    global $DB;
    return $DB->get_field("course", "id", array("fullname"=>$name));
}

function getCourseName($id) {
    global $DB;
    return $DB->get_field("course", "fullname", array("id"=>$id));
}

function getSingleCourse($id) {
    global $DBH;
    $STH = $DBH->prepare("SELECT * FROM mdl_course_reminder WHERE courseid =" . $id);
    $STH->execute();
    return $STH->fetchall(PDO::FETCH_ASSOC);
}

function is_hierarchy_installed_SD() {
    global $DB, $IS_HIERARCHY_INSTALLED;

    if (!isset($IS_HIERARCHY_INSTALLED)) {
        $IS_HIERARCHY_INSTALLED = $DB->record_exists('config_plugins',array('plugin' => 'tool_hierarchy'));
    }

    return $IS_HIERARCHY_INSTALLED;
}

function find_managers($user_id) {
    global $DB,$CFG;

    $admin_tracking = $CFG->dataroot."/setdateline_email_to_admins.log";
    // If hierarchy is not installed then site admins will be the manager
    if (!is_hierarchy_installed_SD()) {
        // return array();
      $line = "<br>\n".date('Y-m-d H:m').": site admin from find_managers funcs ";
      error_log($line, 3, $admin_tracking);
        return find_site_admins();
    }

    // Find the manager of the users
    $manager_query = 'SELECT u.id, u.username, u.email, u.firstname, u.lastname, u.maildisplay, u.mailformat,
            u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename,
            parent_node.id as parent_node_id, parent_node.parent_node_id
        FROM {hierarchy_node} hn
        JOIN {hierarchy_user} hu
        ON hu.node_id = hn.id
        JOIN {hierarchy_node} parent_node
        ON parent_node.id = hn.parent_node_id
        LEFT JOIN {hierarchy_user} parent_hu
        ON parent_hu.node_id = parent_node.id
        LEFT JOIN {user} u
        ON u.id = parent_hu.user_id
        WHERE hu.user_id = ?';

    $managers = $DB->get_records_sql($manager_query, array(
        $user_id
    ));

    // If there are records but all of 
    // We need to go up one more level
    while (count($managers) === 1 && !isset(reset($managers)->id)) {
        $manager_record = current($managers);
        $managers = find_managers_for_node($manager_record->parent_node_id);
    }

    if (count($managers) === 0) {
        // No higher level than user this is because this is the top level so managers are site admins
        $line = "<br>\n".date('Y-m-d H:m').": site admin from find_managers funcs : No manager found";
        error_log($line, 3, $admin_tracking);
        $managers = find_site_admins();
    }

    return $managers;
}

function find_site_admins() {
    global $DB;

    $admin_ids_query = 'SELECT value 
        FROM {config} 
        WHERE name = ?';
    $admin_ids_record = $DB->get_record_sql($admin_ids_query, array('siteadmins'));
    $admin_ids = explode(',', $admin_ids_record->value);
    list($admin_ids_query, $admin_ids_params) = $DB->get_in_or_equal($admin_ids);
    $admin_users_query = "SELECT u.id, u.username, u.email, u.firstname, u.lastname, u.maildisplay,
            u.mailformat, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
        FROM {user} u
        WHERE u.id $admin_ids_query";

    return $DB->get_records_sql($admin_users_query, $admin_ids_params);
}

function find_managers_for_node($node_id) {
    global $DB;

    $manager_query = 'SELECT u.id, u.username, u.email, u.firstname, u.lastname, u.maildisplay, u.mailformat,
            parent_node.id, parent_node.parent_node_id
        FROM {hierarchy_node} hn
        JOIN {hierarchy_node} parent_node
        ON parent_node.id = hn.parent_node_id
        LEFT JOIN {hierarchy_user} parent_hu
        ON parent_hu.node_id = parent_node.id
        LEFT JOIN {user} u
        ON u.id = parent_hu.user_id
        WHERE hn.id = ?';

    return $DB->get_records_sql($manager_query, array(
        $node_id,
    ));
}

function siteadmin_selector($reminder = null) {
    $allAdmins = find_site_admins();

    $html = '';
    $html .= '<select name="emailtoadmin[]" id="select_emailtoadmin" multiple="multiple">';

    if ($reminder) {
        $emailadminlist = explode(',',$reminder->emailadminlist);
        foreach ($allAdmins as $admin) {
            $selected = '';
            if (in_array($admin->id, $emailadminlist)) {
                $selected = ' selected ';
            }
            $html .= '<option value="'.$admin->id.'" '.$selected.'>';
            $html .= $admin->firstname.' '.$admin->lastname;
            $html .= '</option>';
        }

    }else{
        $selected = ' selected '; // choose first admin by default
        foreach ($allAdmins as $admin) {
            $html .= '<option value="'.$admin->id.'" '.$selected.'>'.$admin->firstname.' '.$admin->lastname.'</option>';
            $selected = ''; 
        }
    }

    $html .= '</select>';

    return $html;
}
function get_reminder_condition($courseid,$userid){
    global $DB;
    $reminder= array();
    $reminderid = $DB->get_field_sql("SELECT reminder_id from mdl_reminder_assign where courseid=? and type='user' and instanceid=?",array($courseid,$userid));
    //Check user - first priority
    if(empty($reminderid)||$reminderid==""){
        //Check cohort - second priority
        $cohorts = $DB->get_fieldset_select('reminder_assign','instanceid',"courseid=? and type=?",array($courseid,'cohort'));
        if(!empty($cohorts)){
            if($DB->record_exists_sql("SELECT 'x' from mdl_cohort_members where userid=? and cohortid in(".implode(",",$cohorts).")",array($userid))){
                $reminderid = $DB->get_field_sql("SELECT reminder_id from mdl_reminder_assign where courseid=? and type='cohort' group by reminder_id",array($courseid));
            }
        }
        //Check course - thrid priority - must be this option
        if(empty($reminderid)||$reminderid=="") $reminderid = $DB->get_field('reminder_assign','reminder_id',array('courseid'=>$courseid,'type'=>'course'));
    }
    $reminder [] = $DB->get_record('course_reminder',array('id'=>$reminderid));
    return $reminder;
}


function toUnixtime($dateStr = null, $hhmmss = '00:00:00') {
    if (isset($dateStr)) {
        $date = DateTime::createFromFormat('d/m/Y', $dateStr);
        $time = strtotime($date->format('Y-m-d ' . $hhmmss));
        return $time;
    }
    return null;
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
        $enrolleddate = get_user_enroled_date($userid,$courseid);
        if($enrolleddate=='') return false; //User has not enrolled to the course.
        if(empty($course_record)&&empty($cohort_record)) return false; // No deadline for the user course
        $course_enrolleddate = intval($enrolleddate);
        //Check if users are part of any cohort enrolment for this course.

        if(!empty($cohort_record) && is_user_enrol_by_cohort_course($userid,$courseid,$cohort_record->instanceid)){//Cohort case
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
function is_user_enrol_by_cohort_course($userid,$courseid,$cohorts_list){
    global $DB;

    return $DB->record_exists_sql("SELECT ue.userid FROM mdl_user_enrolments as ue inner join
mdl_enrol e ON e.id = ue.enrolid where e.courseid=? and ue.userid=? and e.enrol='cohort' and e.status=0 and e.customint1 in(".$cohorts_list.")",array($courseid,$userid));
}

function get_user_enroled_date($userid,$courseid){
    global $DB;
    $record = $DB->get_record_sql('SELECT MAX(ue.timecreated) as enrolleddate FROM mdl_user_enrolments as ue inner join
mdl_enrol e ON e.id = ue.enrolid where e.courseid=? and ue.userid=? group by ue.userid',array($courseid,$userid));
    if(empty($record)) return '';
    else return $record->enrolleddate;
}
function get_coursereminder_user_duedate($userid,$courseid){
    $reminder = get_user_course_reminder($userid,$courseid);
    if($reminder){
        return $reminder->firstreminder + $reminder->secondreminder;
    }else return "";
}
function get_coursereminder_user_specific($userid,$courseid){
    $reminder = get_user_course_reminder($userid,$courseid);
    if($reminder){
        return array('duedate'=>($reminder->firstreminder + $reminder->secondreminder),
            'type'=>$reminder->type);
    }else return false;
}
//This function is only worked when we know user had completed the course.
//This will avoid re-calculate the completion from Theme.
function get_course_user_completed($userid,$courseid){
    global $DB;
    $sql='SELECT max(cmc.timemodified) as completeddate from mdl_course_modules_completion as cmc inner join mdl_course_modules cm on cm.id=cmc.coursemoduleid and cmc.userid=? and cm.course=? and cm.visible=1';
    $duedate = $DB->get_field_sql($sql,array($userid,$courseid));
    return $duedate;
}

function get_all_users_in_site(){
    global $DB,$USER;
    $teammembers = array();
    if(isset($USER->profile['SiteCode'])){
        $sitecode_fieldid = $DB->get_field('user_info_field','id',array('shortname'=>'SiteCode'));
        $teammembers = $DB->get_fieldset_sql("SELECT userid from mdl_user_info_data where fieldid=? and data=?",[$sitecode_fieldid, $USER->profile['SiteCode']]);
    }
    return $teammembers;
}

function get_bluecross_siteadmin($userid){
    global $DB;
    $sitecode_fieldid = $DB->get_field('user_info_field','id',array('shortname'=>'SiteCode'));
    $siteadmin_fieldid = $DB->get_field('user_info_field','id',array('shortname'=>'SiteAdmin'));
    $sitecode = $DB->get_field('user_info_data','data',array('userid'=>$userid,'fieldid'=>$sitecode_fieldid));
    //Get site admin of the site code
  $sql="SELECT    {user}.*      
                          FROM      {user} 
                          LEFT JOIN {user_info_data} profile_sitecode
                          ON        {user}.id = profile_sitecode.userid AND profile_sitecode.fieldid = ?
                          LEFT JOIN {user_info_data} profile_siteadmin
                          ON        {user}.id = profile_siteadmin.userid AND profile_siteadmin.fieldid = ?
                          WHERE     {user}.deleted = 0
                          AND       {user}.suspended = 0 AND  profile_siteadmin.data=1 and profile_sitecode.data=?";

  $users = $DB->get_records_sql($sql,array($sitecode_fieldid, $siteadmin_fieldid, $sitecode));
  return $users;
}