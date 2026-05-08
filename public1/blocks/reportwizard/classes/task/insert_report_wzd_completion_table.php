<?php
namespace block_reportwizard\task;

class insert_report_wzd_completion_table extends \core\task\scheduled_task {      
    //extends \core\task\scheduled_task
    public function get_name() {
        //Shown in admin screens
       return "Update report_wzd_completion table - recently completion users";
    }
    public function execute() {       
        global $CFG;
        $path = $CFG->dirroot.'/blocks/reportwizard/src/cronscripts/insert_report_wzd_completion_table.php';
        exec ('/usr/bin/php -f '.$path);
    } 
}

?> 