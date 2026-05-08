<?php
namespace tool_hierarchy\task;
// Once hierarchy is installed, all users who are not in the hierarchy, will be move to un-assign node of the hierarchy
class import_unassign_hierarchy extends \core\task\scheduled_task {      
    //extends \core\task\scheduled_task
    public function get_name() {
        //Shown in admin screens
       return "Import un-assign users into hierarchy";
    }
                                                                     
    public function execute() {     
        global $CFG;
        // exec ('/usr/bin/php -f '.$CFG->dirroot.'/admin/tool/hierarchy/user_import/unassign_user_hierarchy.php');
    } 
}

?> 