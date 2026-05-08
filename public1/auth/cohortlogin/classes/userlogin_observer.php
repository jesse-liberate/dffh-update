<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/auth/cohortlogin/lib.php');
require_once($CFG->dirroot.'/auth/manual/auth.php');

class auth_cohortlogin_userlogin_observer {
    public static function observe_userlogin() {
        //Update the current users into right cohorts When they login to LMS
        run_update_user_cohorts();
    }    
}