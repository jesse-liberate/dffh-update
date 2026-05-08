<?php
namespace block_reportwizard\task;

class report_wzd_schedule extends \core\task\scheduled_task {      
    //extends \core\task\scheduled_task
    public function get_name() {
        //Shown in admin screens
       return "Run report wizard schedule to send out emails including CSV reports.";
    }
    public function execute() {       
        global $CFG;
        require_once($CFG->dirroot.'/blocks/reportwizard/src/lib.php');
        run_reportwizard_schedule();
    } 
}