<?php
namespace block_reportwizard\task;

class insert_report_wzd_completion_table_full extends \core\task\scheduled_task {
    //extends \core\task\scheduled_task
    public function get_name() {
        //Shown in admin screens
       return "Update report_wzd_completion table - Full list";
    }
    public function execute() {       
        global $CFG;
        $path = $CFG->dirroot.'/blocks/reportwizard/src/cronscripts/insert_report_wzd_completion_table_full.php';
        exec ('/usr/bin/php -f '.$path);
    } 
}

?> 