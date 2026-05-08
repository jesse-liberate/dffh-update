<?php
namespace tool_cohortenrolmentrules\task;

class create_allusers_cohort extends \core\task\scheduled_task {      
    //extends \core\task\scheduled_task
    public function get_name() {
        //Shown in admin screens
       return "Create All users cohort";
    }

    public function execute() {       
        global $CFG;
        date_default_timezone_set('Australia/Melbourne');
        $date = date("Y-m-d");
        
        $path = $CFG->dirroot.'/admin/tool/cohortenrolmentrules/cronscripts/create_allusers_cohort.php';
        exec ('/usr/bin/php -f '.$path);    
    } 
}