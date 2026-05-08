<?php
namespace tool_enrolmentemail\task;

class process extends \core\task\scheduled_task {      
    //extends \core\task\scheduled_task
    public function get_name() {
        //Shown in admin screens
       return "Process enrolment email notification queue";
    }
                                                                     
    public function execute() {       
        global $CFG;
        $path = $CFG->dirroot.'/admin/tool/enrolmentemail/cronscripts/process.php';
        exec ('/usr/bin/php -f '. $path);    
    }                                                                                                                               
}

