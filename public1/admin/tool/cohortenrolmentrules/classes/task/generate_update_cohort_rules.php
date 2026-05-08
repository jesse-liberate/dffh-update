<?php
namespace tool_cohortenrolmentrules\task;

class generate_update_cohort_rules extends \core\task\scheduled_task {      
    //extends \core\task\scheduled_task
    public function get_name() {
        //Shown in admin screens
       return "Generate cohort rules based profile field (Require to run before create_allusers_cohort schedule)";
    }
                                                                     
    public function execute() {       
        global $CFG;
        require_once($CFG->dirroot."/admin/tool/cohortenrolmentrules/lib.php");
        generate_cohort_rule_based_profile_fields();
    }
}
