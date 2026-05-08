<?php
namespace tool_setdeadline\task;
class setdeadline_send_emails_out extends \core\task\scheduled_task {      
    //extends \core\task\scheduled_task
    public function get_name() {
        //Shown in admin screens
       return "Set Course Deadline - Send emails out to Admin and Managers";
    }
                                                                     
    public function execute() {     
        global $CFG;
        exec ('/usr/bin/php -f '.$CFG->dirroot.'/admin/tool/setdeadline/send_emails.php');
    } 
}

?> 