<?php
namespace tool_delayemail\task;

class corn_delayemail extends \core\task\scheduled_task {      
    //extends \core\task\scheduled_task
    public function get_name() {
        //Shown in admin screens
       return "Sending emails out from the queue - Avoid moodle email issues";
    }

    public function execute() {     
        global $CFG;
        exec ('/usr/bin/php -f '.$CFG->dirroot.'/admin/tool/delayemail/cron_delayemail.php');
    } 
}
?> 