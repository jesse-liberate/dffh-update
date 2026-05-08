<?php 
require('../../../config.php');
require('lib.php');
require($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/authlib.php');
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->dirroot.'/user/filters/lib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/blocks/reporting/report/lib.php');
global $DB;


$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('courseenrol', 'tool_courseenrol'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/admin/tool/courseenrol/index.php');
$PAGE->requires->css('/admin/tool/courseenrol/css/style.css');
$contextid = optional_param('contextid', 0, PARAM_INT);

$PAGE->requires->js('/lib/mindatlas/jquery/jquery.min.js', true);
$PAGE->requires->js('/lib/mindatlas/chosen/chosen.jquery.js', true);
$PAGE->requires->css('/lib/mindatlas/chosen/chosen.css', true);


if ($CFG->forcelogin) {
    require_login(); 
}

admin_externalpage_setup('courseenrol_management');

$contextid = optional_param('contextid', 0, PARAM_INT);
if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
} else {
    $context = context_system::instance();
}

$category = null;
if ($context->contextlevel == CONTEXT_COURSECAT) {
    $category = $DB->get_record('course_categories', array('id'=>$context->instanceid), '*', MUST_EXIST);
}


if (isset($_GET["page"])) { 
    $page = $_GET["page"]; 
} else { 
    $page=1; 
} 

$params = array('page' => $page);
if ($contextid) {
    $params['contextid'] = $contextid;
}
 
$params['tabindividual'] = true;

$baseurl = new moodle_url('/admin/tool/courseenrol/individual.php', $params);



	$delete       = optional_param('delete', 0, PARAM_INT);
    $confirm      = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash
    $confirmuser  = optional_param('confirmuser', 0, PARAM_INT);
    $sort         = optional_param('sort', 'name', PARAM_ALPHANUM);
    $dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
    $page         = optional_param('page', 0, PARAM_INT);
    $perpage      = optional_param('perpage', 15, PARAM_INT);        // how many per page
    $ru           = optional_param('ru', '2', PARAM_INT);            // show remote users
    $lu           = optional_param('lu', '2', PARAM_INT);            // show local users
    $acl          = optional_param('acl', '0', PARAM_INT);           // id of user to tweak mnet ACL (requires $access)
    $suspend      = optional_param('suspend', 0, PARAM_INT);
    $unsuspend    = optional_param('unsuspend', 0, PARAM_INT);
    $unlock       = optional_param('unlock', 0, PARAM_INT);

    // admin_externalpage_setup('editusers');

    $sitecontext = context_system::instance();
    $site = get_site();

    if (!has_capability('moodle/user:update', $sitecontext) and !has_capability('moodle/user:delete', $sitecontext)) {
        print_error('nopermissions', 'error', '', 'edit/delete users');
    }

    $selected_category = "";
    if(!isset($_POST['category'])){
        if(isset($_SESSION['category'])) $selected_category = $_SESSION['category'];
    } else {
        $selected_category = $_POST['category'];
        $_SESSION['category'] = $_POST['category'];
    }

    $method_error = false;
    // Save all course enrolment if it is submitted
    if(isset($_POST['save_course_enrol'])){
        $post = $_POST;

        if (!$enrol_manual = enrol_get_plugin('manual')) {
            throw new coding_exception('Can not instantiate enrol_manual');
        }
        //echo "<pre>".print_r($post,true)."</pre>";
        if(isset($post['courseuser'])) $users = $post['courseuser'];
        $userenrolled = $post['userenrolled'];
        //echo "<pre>".print_r($users,true)."</pre>";
        if(!empty($users))
        foreach ($users as $key => $courses) {
            $currentuserid = $key;
            //echo "<pre>".print_r($value,true)."</pre>";
            $userenrolled[$currentuserid] = ",".$userenrolled[$currentuserid];
           // echo "BEFORE: ".$userenrolled[$currentuserid]."<br>";
            //
            $enrol_list = "";
            foreach ($courses as $k => $value) {
                if(strpos(','.$k.',', $userenrolled[$currentuserid])==false){
                    // The user has been enrolled into new course
                    // check if the user has been enrolled or not.
                    $context = context_course::instance($k);
                    if(!is_enrolled($context,$currentuserid)){
                        $enrol_list .=$k.',';  // For testing only
                        // enrol user in here: currentuserid into courseid: $k.
                        $studentroleid = $DB->get_field('role','id',array('shortname'=>'student','archetype'=>'student'));
                        // $enrol_date = $DB->get_field('course','startdate',array('id'=>$k));
                        $enrol_date = time();
                        $enrolid = $DB->get_field('enrol','id',array('enrol'=>'manual','courseid'=>$k));
                        $instance = $DB->get_record('enrol', array('id'=>$enrolid, 'enrol'=>'manual'), '*', MUST_EXIST);

                        // $defaultExtended = strtotime('+48 months',$enrol_date); // DEFAULT OF EXPIRE MONTHS ARE 48 MONTHS AFTER ENROL DATE
                        $enrol_manual->enrol_user($instance, $currentuserid, $studentroleid, $enrol_date, 0);
                       //  if (!enrol_try_internal_enrol($k, $currentuserid, $studentroleid, $enrol_date)) {
                       //      echo "ERRor";
                       //      $method_error = true;
                       // }
                    }
                    $userenrolled[$currentuserid] = str_replace(','.$k.',',',', $userenrolled[$currentuserid]);
                }
            }
            // For testing only
             //echo "<br>New enrol list: ".$enrol_list;
             //echo "<br>Unenrol list: ".$userenrolled[$currentuserid];
        }
        // Unenroll all users in this $userenrolled
       // echo "<br> Unenroll: ";
        foreach ($userenrolled as $key=>$row) {
            if(trim($row)!=','){
                $arr_courses = explode(",", $row);
                // echo $key." :: ".$row."<br>";
                foreach ($arr_courses as $course_id) {
                    // Unenrol user: $key, $courseid: $course_id
                        // Have to ensure that 'Manual' enrolment method is active.
                        $myinstance = null;
                        $einst = enrol_get_instances($course_id, false);
                        foreach ($einst as $instance) {
                            if ($instance->enrol == 'manual') {
                                $myinstance = $instance;
                                break;
                            }
                        }
                        if ($myinstance !== null) {
                            $pl = enrol_get_plugin('manual');
                            $pl->unenrol_user($instance, $key);
                        }
                    
                }
            }
        }
        // echo "<pre>".print_r($userenrolled,true)."</pre>";
    }
     


    $stredit   = get_string('edit');
    $strdelete = get_string('delete');
    $strdeletecheck = get_string('deletecheck');
    $strshowallusers = get_string('showallusers');
    $strsuspend = get_string('suspenduser', 'admin');
    $strunsuspend = get_string('unsuspenduser', 'admin');
    $strunlock = get_string('unlockaccount', 'admin');
    $strconfirm = get_string('confirm');
    if (empty($CFG->loginhttps)) {
        $securewwwroot = $CFG->wwwroot;
    } else {
        $securewwwroot = str_replace('http:','https:',$CFG->wwwroot);
    }

    // $returnurl = new moodle_url('/admin/user.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage, 'page'=>$page));
    $returnurl = $baseurl;

    // The $user variable is also used outside of these if statements.
    $user = null;
    if ($confirmuser and confirm_sesskey()) {
        require_capability('moodle/user:update', $sitecontext);
        if (!$user = $DB->get_record('user', array('id'=>$confirmuser, 'mnethostid'=>$CFG->mnet_localhost_id))) {
            print_error('nousers');
        }

        $auth = get_auth_plugin($user->auth);

        $result = $auth->user_confirm($user->username, $user->secret);

        if ($result == AUTH_CONFIRM_OK or $result == AUTH_CONFIRM_ALREADY) {
            redirect($returnurl);
        } else {
            echo $OUTPUT->header();
            redirect($returnurl, get_string('usernotconfirmed', '', fullname($user, true)));
        }

    } else if ($delete and confirm_sesskey()) {              // Delete a selected user, after confirmation
        require_capability('moodle/user:delete', $sitecontext);

        $user = $DB->get_record('user', array('id'=>$delete, 'mnethostid'=>$CFG->mnet_localhost_id), '*', MUST_EXIST);

        if (is_siteadmin($user->id)) {
            print_error('useradminodelete', 'error');
        }

        if ($confirm != md5($delete)) {
            echo $OUTPUT->header();
            $fullname = fullname($user, true);
            echo $OUTPUT->heading(get_string('deleteuser', 'admin'));
            $optionsyes = array('delete'=>$delete, 'confirm'=>md5($delete), 'sesskey'=>sesskey());
            echo $OUTPUT->confirm(get_string('deletecheckfull', '', "'$fullname'"), new moodle_url($returnurl, $optionsyes), $returnurl);
            echo $OUTPUT->footer();
            die;
        } else if (data_submitted() and !$user->deleted) {
            if (delete_user($user)) {
                \core\session\manager::gc(); // Remove stale sessions.
                redirect($returnurl);
            } else {
                \core\session\manager::gc(); // Remove stale sessions.
                echo $OUTPUT->header();
                echo $OUTPUT->notification($returnurl, get_string('deletednot', '', fullname($user, true)));
            }
        }
    } else if ($acl and confirm_sesskey()) {
        if (!has_capability('moodle/user:update', $sitecontext)) {
            print_error('nopermissions', 'error', '', 'modify the NMET access control list');
        }
        if (!$user = $DB->get_record('user', array('id'=>$acl))) {
            print_error('nousers', 'error');
        }
        if (!is_mnet_remote_user($user)) {
            print_error('usermustbemnet', 'error');
        }
        $accessctrl = strtolower(required_param('accessctrl', PARAM_ALPHA));
        if ($accessctrl != 'allow' and $accessctrl != 'deny') {
            print_error('invalidaccessparameter', 'error');
        }
        $aclrecord = $DB->get_record('mnet_sso_access_control', array('username'=>$user->username, 'mnet_host_id'=>$user->mnethostid));
        if (empty($aclrecord)) {
            $aclrecord = new stdClass();
            $aclrecord->mnet_host_id = $user->mnethostid;
            $aclrecord->username = $user->username;
            $aclrecord->accessctrl = $accessctrl;
            $DB->insert_record('mnet_sso_access_control', $aclrecord);
        } else {
            $aclrecord->accessctrl = $accessctrl;
            $DB->update_record('mnet_sso_access_control', $aclrecord);
        }
        $mnethosts = $DB->get_records('mnet_host', null, 'id', 'id,wwwroot,name');
        redirect($returnurl);

    } else if ($suspend and confirm_sesskey()) {
        require_capability('moodle/user:update', $sitecontext);

        if ($user = $DB->get_record('user', array('id'=>$suspend, 'mnethostid'=>$CFG->mnet_localhost_id, 'deleted'=>0))) {
            if (!is_siteadmin($user) and $USER->id != $user->id and $user->suspended != 1) {
                $user->suspended = 1;
                // Force logout.
                \core\session\manager::kill_user_sessions($user->id);
                user_update_user($user, false);
            }
        }
        redirect($returnurl);

    } else if ($unsuspend and confirm_sesskey()) {
        require_capability('moodle/user:update', $sitecontext);

        if ($user = $DB->get_record('user', array('id'=>$unsuspend, 'mnethostid'=>$CFG->mnet_localhost_id, 'deleted'=>0))) {
            if ($user->suspended != 0) {
                $user->suspended = 0;
                user_update_user($user, false);
            }
        }
        redirect($returnurl);

    } else if ($unlock and confirm_sesskey()) {
        require_capability('moodle/user:update', $sitecontext);

        if ($user = $DB->get_record('user', array('id'=>$unlock, 'mnethostid'=>$CFG->mnet_localhost_id, 'deleted'=>0))) {
            login_unlock_account($user);
        }
        redirect($returnurl);
    }
    // create the user filter form
    $ufiltering = new user_filtering();
    echo $OUTPUT->header();
    $PAGE->requires->js('/admin/tool/courseenrol/textrotate.js');
    $PAGE->requires->js_function_call('textrotate_init', null, true);

    if(isset($_POST['save_course_enrol'])){
        echo get_string('savemessage','tool_courseenrol');
    }


if ($editcontrols = courseenrol_edit_controls($context, $baseurl)) {
    echo $OUTPUT->render($editcontrols);
}
    
    // Carry on with the user listing
    $context = context_system::instance();
   //  $extracolumns = get_extra_user_fields($context);

    // Get all user name fields as an array.
    $allusernamefields = get_all_user_name_fields(false, null, null, null, true);
    // echo "<pre>".print_r($allusernamefields,true)."</pre>";
    // echo "<pre>".print_r($extracolumns,true)."</pre>";
    
    //$all_courses = get_all_course(); // get all course with id: courseid
    // $all_column_course = get_all_column_course();
    $all_column_course = get_column_course($selected_category);

    $arr_users = array('firstname','lastname');
    $columns = array_merge($arr_users,$all_column_course);

        $a_manual = $DB->get_records_sql("select c.id,e.status from mdl_course c left join mdl_enrol e 
on (e.courseid=c.id and e.enrol='manual') where c.category<>0 and (e.status<>0||(e.status is null)) group by id,status",array());
        $course_without_manual = array();
        $course_manual_disabled = array();
        if(!empty($a_manual)){
            foreach ($a_manual as $row) {
                if($row->status==1) $course_manual_disabled[] = $row->id;
                $course_without_manual[] = $row->id;
            }
        }
    //$columns = array_merge($allusernamefields, $extracolumns, array('city', 'country', 'lastaccess'));
   // echo "<pre>".print_r($extracolumns,true)."</pre>";
    foreach ($columns as $column) {
         //$string[$column] = get_user_field_name($column);
    	$string[$column] = shortern_course_name(get_user_course_string($column));

        if(in_array($column, $arr_users)){
            if ($sort != $column) {
                $columnicon = "";
                if ($column == "lastaccess") {
                    $columndir = "DESC";
                } else {
                    $columndir = "ASC";
                }
            } else {
                $columndir = $dir == "ASC" ? "DESC":"ASC";
                if ($column == "lastaccess") {
                    $columnicon = ($dir == "ASC") ? "sort_desc" : "sort_asc";
                } else {
                    $columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
                }
                $columnicon = "<img class='iconsort' src=\"css/" . $columnicon . ".png\" alt=\"\" />";

            }
            $$column = "<a href=\"individual.php?sort=$column&amp;dir=$columndir\">".$string[$column]."</a>$columnicon";
        }
        else{
            $courseid = courseid_column($column);
            $method_active = "";
            if(in_array($courseid, $course_without_manual)) 
                if(in_array($courseid,$course_manual_disabled)) 
                    $method_active = html_writer::link(new moodle_url($securewwwroot.'/enrol/instances.php', array('id'=>$courseid)), html_writer::empty_tag('img', array('src'=>'css/show.png', 'alt'=>get_string('manualdisabled','tool_courseenrol'), 'title'=>get_string('manualdisabled','tool_courseenrol'), 'class'=>'iconsmall')), array('title'=>$stredit,'target'=>'_blank'));
            $$column = " <a href=\"".$securewwwroot."/course/view.php?id=".$courseid."\" title='Go to the course'><div class='rotatetext'><span>".$string[$column]."</span></div></a>".$method_active; 
            // $$column = " <a href=\"".$securewwwroot."/course/view.php?id=".$courseid."\" title='Go to the course'><div class='aaaa'><span class='completion-activityname'>".$string[$column]."</span></div>"."</a>".$method_active; 
            // $$column = "<a href=\"individual.php?sort=$column&amp;dir=$columndir\">".$string[$column]."</a>$columnicon";
        }
    }

    // We need to check that alternativefullnameformat is not set to '' or language.
    // We don't need to check the fullnamedisplay setting here as the fullname function call further down has
    // the override parameter set to true.
    $fullnamesetting = $CFG->alternativefullnameformat;
    // If we are using language or it is empty, then retrieve the default user names of just 'firstname' and 'lastname'.
    if ($fullnamesetting == 'language' || empty($fullnamesetting)) {
        // Set $a variables to return 'firstname' and 'lastname'.
        $a = new stdClass();
        $a->firstname = 'firstname';
        $a->lastname = 'lastname';
        // Getting the fullname display will ensure that the order in the language file is maintained.
        $fullnamesetting = get_string('fullnamedisplay', null, $a);
    }

    // Order in string will ensure that the name columns are in the correct order.
    $usernames = order_in_string($allusernamefields, $fullnamesetting);
    $fullnamedisplay = array();
    foreach ($usernames as $name) {
        // Use the link from $$column for sorting on the user's name.
        $fullnamedisplay[] = ${$name};
    }
    // All of the names are in one column. Put them into a string and separate them with a /.
    $fullnamedisplay = implode(' / ', $fullnamedisplay);
    // If $sort = name then it is the default for the setting and we should use the first name to sort by.
    if ($sort == "name") {
        // Use the first item in the array.
        $sort = reset($usernames);
    }

    list($extrasql, $params) = $ufiltering->get_sql_filter();
    $users = get_users_listing($sort, $dir, $page*$perpage, $perpage, '', '', '',
            $extrasql, $params, $context);
    $usercount = get_users(false);
    $usersearchcount = get_users(false, '', false, null, "", '', '', '', '', '*', $extrasql, $params);

    if ($extrasql !== '') {
        echo $OUTPUT->heading("$usersearchcount / $usercount ".get_string('users'));
        $usercount = $usersearchcount;
    } else {
        echo $OUTPUT->heading("$usercount ".get_string('users'));
    }

    $strall = get_string('all');

    $baseurl = new moodle_url('/admin/tool/courseenrol/individual.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage));
    echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);

    flush();


    if (!$users) {
        $match = array();
        echo $OUTPUT->heading(get_string('nousersfound'));

        $table = NULL;

    } else {

        $countries = get_string_manager()->get_list_of_countries(false);
        if (empty($mnethosts)) {
            $mnethosts = $DB->get_records('mnet_host', null, 'id', 'id,wwwroot,name');
        }


        $table = new html_table();
        $table->head = array ();
        $table->colclasses = array();
        $table->head[] = $fullnamedisplay;
        $table->attributes['class'] = 'generaltable rotate';
        foreach ($all_column_course as $field) {
             $table->head[] = ${$field};
            // $table->head->attributes['class'] = 'verticaltext';
            // echo "<pre>".print_r($table->head,true)."</pre>";
        }


        $table->id = "users";
        $course_enrolled="";

        // Array of users has been assigned to course by cohort method
        $arr_users_cohorts = array();
        $user_cohorts = $DB->get_records_sql("select u.id,u.userid,e.courseid from mdl_user_enrolments u, mdl_enrol e where u.enrolid = e.id and e.enrol='cohort' group by id,userid,courseid",array());
       if(!empty($user_cohorts)){
            foreach($user_cohorts as $row){
                if(!isset($arr_users_cohorts[$row->userid])) $arr_users_cohorts[$row->userid]=','.$row->courseid.",";
                else $arr_users_cohorts[$row->userid] .= $row->courseid.",";
            }
        }
        //echo "<pre>".print_r($arr_users_cohorts,true)."</pre>";
       foreach ($users as $user) {
            $buttons = array();
            $lastcolumn = '';
            
 
            $fullname = fullname($user, true);





            $row = array ();
            $row[] = "<a href=\"".$securewwwroot."/user/view.php?id=$user->id&amp;course=$site->id\">$fullname</a>";
            $enrolled_list =""; // Courses has been enrolled
            foreach ($all_column_course as $field) {
            	$courseid = str_replace('course', '', $field);
            	// Edit icon to go to Edit enrolment
                $context = context_course::instance($courseid);
                $response = 'courseuser['.$user->id.']['.$courseid.']'; // courseuser[2][4];
                
                if(is_user_enrolled($user->id,$courseid)){
                    // This user has been enrolled by COHORT OR MANUAL

                    $enrolled_list .=$courseid.",";
                    // Get the right link to edit enrolment course: enrol/editenrolments.php?id=$courseid&ue=$user_enrol_id
                    $enrol_id = $DB->get_field('enrol','id',array('enrol'=>'manual','courseid'=>$courseid));
                    if((array_key_exists($user->id, $arr_users_cohorts))&&(!(strpos($arr_users_cohorts[$user->id],','.$courseid.',')===false))){
                        // echo "<pre>".$cohort."</pre>".$courseid."<br>";
                            // user has been enrolled by cohort
                            $list_enrolled_cohort = "";
                            $cohort_list = $DB->get_records_sql("select u.id,c.name from mdl_user_enrolments u, mdl_enrol e, mdl_cohort c 
                                where c.id=e.customint1 and u.enrolid = e.id and e.enrol='cohort' and u.userid=$user->id and e.courseid=$courseid group by id,name",array());
                            if(!empty($cohort_list)){
                                foreach($cohort_list as $key_cohort=>$cohort_enrolled){
                                    if($list_enrolled_cohort=="")
                                        $list_enrolled_cohort = $cohort_enrolled->name;
                                    else $list_enrolled_cohort .= ",".$cohort_enrolled->name;
                                }
                            }
                            // Finish list of cohorts assigned to this user.
                            $cohortrollover = str_replace("{cohortlist}",$list_enrolled_cohort, get_string('enrolledcohorts','tool_courseenrol'));
                            $str_course = html_writer::empty_tag('input',array('type'=>'checkbox','name'=>$response,'checked'=>'true','disabled'=>'true','title'=>$cohortrollover));
                            $str_course .= html_writer::link(new moodle_url('#', array()), html_writer::empty_tag('img', array('src'=>'css/edit.png', 'alt'=>$stredit, 'class'=>'iconsmall')), array('title'=>$cohortrollover));
                            // if(in_array($courseid,$course_without_manual)) $str_course.="(*)";
                    }else{
                      //  echo $user->id." - ".$courseid."<br>";
                        // enrolled Manually or by other as Self enrolment
                        $str_course = html_writer::empty_tag('input',array('type'=>'checkbox','name'=>$response,'checked'=>'true'));
                        $user_enrol = $DB->get_record('user_enrolments',array('userid'=>$user->id,'enrolid'=>$enrol_id));
                        if(isset($user_enrol->timeend)&&($user_enrol->timeend!=0)&&($user_enrol->timeend<time())){
                            $expire = "Course expired: ".date('d/m/Y',$user_enrol->timeend);
                            
                            $str_course .= html_writer::link(new moodle_url($securewwwroot.'/enrol/editenrolment.php', array('id'=>$courseid,'ue'=>$user_enrol->id)), html_writer::empty_tag('img', array('src'=>'css/warning.png', 'alt'=>$expire, 'title'=>$expire, 'class'=>'iconsmall')), array('title'=>$stredit,'target'=>'_blank'));
                        }
                        else{
                            // echo " userid: ".$user->id." enrolid: ".$enrol_id."<br>\n";
                            if(!empty($user_enrol)){
                                $str_course .= html_writer::link(new moodle_url($securewwwroot.'/enrol/editenrolment.php', array('id'=>$courseid,'ue'=>$user_enrol->id)), html_writer::empty_tag('img', array('src'=>'css/edit.png', 'alt'=>$stredit, 'class'=>'iconsmall')), array('title'=>$stredit,'target'=>'_blank'));
                            }else{
                                //User enrolled to the course by self-enrolment.
                                $str_course = html_writer::empty_tag('input',array('type'=>'checkbox','name'=>$response,'checked'=>'true','disabled'=>'true','title'=>'Self-enrolled or Manager-enrolled'));
                                // $str_course .= html_writer::link(new moodle_url('#', array()), html_writer::empty_tag('img', array('src'=>'css/edit.png', 'alt'=>$stredit, 'class'=>'iconsmall')), array('title'=>'Self-enrolled'));
                            }
                        }
                        //if(in_array($courseid,$course_without_manual)) $str_course.="(*)";
                    }
                }
                else {
                    if(in_array($courseid,$course_without_manual)){
                        if(in_array($courseid, $course_manual_disabled)){
                            $str_course = html_writer::empty_tag('input',array('type'=>'checkbox','name'=>$response));
                        } else $str_course = html_writer::empty_tag('img', array('src'=>'css/less.png', 'alt'=>get_string('nomanualenrolment','tool_courseenrol'), 'title'=>get_string('nomanualenrolment','tool_courseenrol'), 'class'=>'iconsmall')); // No Manual method
                    } else $str_course = html_writer::empty_tag('input',array('type'=>'checkbox','name'=>$response));
                }
                $row[] = $str_course;
            }

            $course_enrolled .=html_writer::empty_tag('input',array('type'=>'hidden','name'=>'userenrolled['.$user->id.']','value'=>$enrolled_list));


            if ($user->suspended) {
                foreach ($row as $k=>$v) {
                    $row[$k] = html_writer::tag('span', $v, array('class'=>'usersuspended'));
                }
            }
            $row[] = implode(' ', $buttons);
            $row[] = $lastcolumn;
            $table->data[] = $row;
        }
    }

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

    echo html_writer::start_tag('form',array('action'=> new moodle_url('individual.php'),'id'=>'frmcategory','method'=>'POST'));
    echo get_string('selectcategory','tool_courseenrol');
    echo html_writer::select($list_category,'category', $selected_category, false, array('class'=>'chzn-select'));
    echo html_writer::empty_tag('input',array('type'=>'submit','name'=>'select_category','class'=>'cls_coursecategory','value'=>get_string('go', 'tool_courseenrol')));
    echo html_writer::end_tag('form');
// ========== End of the course category

    // add filters
    $ufiltering->display_add();
    $ufiltering->display_active();


 // === ===============
    echo html_writer::start_tag('div',array('id'=>'main_area_courses','class'=>'mainareacouses'));
    echo html_writer::start_tag('form',array('action'=> new moodle_url('individual.php'),'id'=>'frm','method'=>'POST'));
    if (!empty($table)) {
        echo html_writer::start_tag('div', array('class'=>'no-overflow'));
        echo html_writer::table($table);
        echo html_writer::end_tag('div');
        echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);
        echo $course_enrolled;
    }

    echo html_writer::empty_tag('input',array('type'=>'submit','name'=>'save_course_enrol','value'=>get_string('saveenrol', 'tool_courseenrol')));
    echo html_writer::end_tag('form');
    echo html_writer::end_tag('div');

    echo $OUTPUT->footer();
?>

<script>
    $(document).ready(function(){
        $('.chzn-select').chosen({search_contains: true});
    })
</script>
