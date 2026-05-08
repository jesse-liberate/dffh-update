<?php
require_once('../../../config.php');
require_once('lib.php');

 $term = $_REQUEST["term"];
if (!$term) return;

$users_ids = $USER->id;
$result = array();
$hierarchyquery = "";
// $hierarchy = is_hierarchy_installed();
// if(($hierarchy)&&(!is_siteadmin($USER->id))){
// 	// Hierarchy plugin exist. Therefore, user can only search other user under this node
// 	// FIND all users under this node in the hierarchy
// 	$currentNode = $DB->get_field('hierarchy_user','node_id',array('user_id'=>$USER->id));
// 	$all_childs_nodes = find_children_nodes($currentNode);

// 	$selectedNodes = implode(',', $all_childs_nodes);
// 	if(($selectedNodes=="")||is_null($selectedNodes)){
// 		// This user is the leave of the hierarchy tree
// 		$hierarchyquery = $USER->id;
// 	} else {
// 		// Get list of users under current user
// 		$hierarchyquery = get_all_users_from_nodes($selectedNodes);
// 		if(($hierarchyquery=="")||is_null($hierarchyquery)){
// 			// User has no childs user even he/she is not the leave of the hierarchy tree
// 			$hierarchyquery = $USER->id;
// 		} else {
// 			$hierarchyquery.=",".$USER->id;
// 		}
// 	}
// 	// Add query into the hierarchy query.
// 	$hierarchyquery = " and id in(".$hierarchyquery.")";
// }
//$hierarchyquery="";
$find_all_users_sql = "SELECT DISTINCT concat(firstname, ' ', lastname) user_name
		FROM mdl_user 
		WHERE concat(firstname, ' ', lastname) LIKE '%" . addslashes($term) ."%' AND deleted <> 1
		AND suspended <> 1
		ORDER BY firstname";

$user_records = $DB->get_records_sql($find_all_users_sql);

if ($user_records != false) {
	foreach ($user_records as $user_record) {
		$result[]  = $user_record->user_name;
	}
	
}

echo json_encode($result);

?>