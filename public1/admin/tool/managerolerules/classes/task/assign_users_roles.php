<?php
namespace tool_managerolerules\task;

class assign_users_roles extends \core\task\scheduled_task {      
    //extends \core\task\scheduled_task
    public function get_name() {
        //Shown in admin screens
       return "Auto assign users into roles";
    }
                                                                     
    public function execute() {       
        global $CFG;
        date_default_timezone_set('Australia/Melbourne');
        $date = date("Y-m-d");
        
        $path = $CFG->dirroot.'/admin/tool/managerolerules/cron_assign_user_role_rules.php';
        exec ('/usr/bin/php -f '.$path);    
    }                                                                                                                               
}

?> 