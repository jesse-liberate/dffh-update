<?php
namespace tool_trackemails\task;

class cron_trackemails extends \core\task\scheduled_task {      
    //extends \core\task\scheduled_task
    public function get_name() {
        //Shown in admin screens
       return "Tracking all emails from the features";
    }
                                                                     
    public function execute() {     
        global $CFG;
        exec ('/usr/bin/php -f '.$CFG->dirroot.'/admin/tool/trackemails/cron_trackemails.php');
    } 
}
?> 