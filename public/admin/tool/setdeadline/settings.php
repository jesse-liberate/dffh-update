<?php


defined('MOODLE_INTERNAL') || die;
$context_system = context_system::instance();
if (has_capability('tool/setdeadline:set_deadline', $context_system)) {
    $ADMIN->add('courses', new admin_externalpage('toolsetdeadline',  get_string('setting_coursedeadline', 'tool_setdeadline'), "$CFG->wwwroot/$CFG->admin/tool/setdeadline/index.php",array('tool/setdeadline:set_deadline')));
}

/**
*	admin_externalpage
*
* 	public function __construct($name, $visiblename, $url, $req_capability='moodle/site:config', $hidden=false, $context=NULL)
* 	Constructor for adding an external page into the admin tree.
*
* 	@param string $name The internal name for this external page. Must be unique amongst ALL part_of_admin_tree objects.
* 	@param string $visiblename The displayed name for this external page. Usually obtained through get_string().
* 	@param string $url The external URL that we should link to when someone requests this external page.
* 	@param mixed $req_capability The role capability/permission a user must have to access this external page. Defaults to 'moodle/site:config'.
* 	@param boolean $hidden Is this external page hidden in admin tree block? Default false.
*	@param stdClass $context The context the page relates to. Not sure what happens
*      if you specify something other than system or front page. Defaults to system.
*/