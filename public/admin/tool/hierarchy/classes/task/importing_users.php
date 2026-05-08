<?php
namespace tool_hierarchy\task;

class importing_users extends \core\task\scheduled_task {      
    //extends \core\task\scheduled_task
    public function get_name() {
        //Shown in admin screens
       return "Importing users to hierarchy";
    }
                                                                     
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot.'/admin/tool/hierarchy/lib.php');
        // date_default_timezone_set('Australia/Melbourne');
        // $date = date("Y-m-d");
        rebuild_hierarchy();
    }        
}

?> 