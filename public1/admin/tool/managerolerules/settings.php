<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) { // needs this condition or there is error on login page
    
    $ADMIN->add('roles', new admin_externalpage('tool_managerolerules',get_string('rolerules','tool_managerolerules'), "$CFG->wwwroot/$CFG->admin/tool/managerolerules/index.php"));
   
}