<?php
namespace block_reporting\task;

class general_report_export extends \core\task\scheduled_task {      
    //extends \core\task\scheduled_task
    public function get_name() {
        //Shown in admin screens
       return "Export all general report data to CSV";
    }
                                                                     
    public function execute() {
        global $CFG;
        $path = $CFG->dirroot . "/blocks/reporting/report/general_report_export.php";
        
        exec ('/usr/bin/php -f ' . $path);
    }        
}
