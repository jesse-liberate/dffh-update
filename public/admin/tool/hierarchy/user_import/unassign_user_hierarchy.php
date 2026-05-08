<?php
define('CLI_SCRIPT', true);
require(realpath(dirname(__FILE__)) . '/../../../../config.php');
require(realpath(dirname(__FILE__)) . '/../lib.php');
global $DB;
$hierarchy = is_hierarchyfeature_installed();
$error="";
if($hierarchy){
	$root = findrootnode();
	$root_id = $root->id;
    // Add all site admins into the root node
    $siteadmin = $DB->get_field('config','value',array('name'=>'siteadmins'));
    $arr_admin_users = explode(",", $siteadmin);
    foreach ($arr_admin_users as $admin_user_id) {
    	assign_admin_user_root_node($admin_user_id,$root_id);
    }
	$unassign_node_id = get_unassign_node($root_id);
	if(($unassign_node_id!="")&&($unassign_node_id!=NULL)){
	    // Add all un-assign users into un-assign node of the hierarchy
	    // Get all users who are not part of the hierarchy
	    assign_new_users_to_unassignnode($unassign_node_id);
	}
}
