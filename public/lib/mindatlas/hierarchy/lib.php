<?php
/**
 * Functions for hierarchy
 *  
 */

// This function only apply when user select multiple nodes. Otherwise, it will return null. 
// The report will give more graphs
//@ return array of node_id refer to list of users under that node
// arr[2] = userid1,userid2,userid3
function getAllUsersSelectedNodes($selectednodes) {
  global $DB;
  $results = array();
  $arr_nodes = explode(",", $selectednodes);
  if (count($arr_nodes) > 1) {
    foreach ($arr_nodes as $nodeid) {
      $rs = $DB->get_records('hierarchy_user', array('node_id' => $nodeid));
      $results[$nodeid] = getAllUsersFromNode($nodeid);
    }
  }
  return $results;
}

// Check that if the $parent is the real parent of $child in the hierarchy. Otherwise, access is denied

function isParentNode($parent, $child) {
  global $DB;
  $return = false;
  $root = findRootNode();
  $root_node_id = $root->id;
  $node_id = $child; // Start from child
  if ($parent == $child)
    return true;
  $i = 0;
  while ($node_id != $root_node_id) {
    $node_id = $DB->get_field('hierarchy_node', 'parent_node_id', array('id' => $node_id));
    if ($node_id == $parent) {
      $return = true;
      break;
    }
    $i++;
    if ($i == 5)
      break;
  }
  return $return;
}

// Find all users 
// Case 1: if current user in the same node with selected node, Only this user and all children under this node will be included in the Queue.
// Case 2: if current user is not the same node with selected node, All user from this node and children node will be included
// @Array of selected nodes
//@ Return a list of users.
function getAllUsersFromNodes($selectednodes) {
  global $DB, $USER;
  if ($selectednodes == null)
    return false;
  $arrnodes = explode(',', $selectednodes);
  $arr_users = array();
  $is_admin = is_siteadmin($USER->id);
  // Check if the user belong to selected node list, then the current user has to be added into the list
  $currentUserNodeId = $DB->get_field('hierarchy_user', 'node_id', array('user_id' => $USER->id));
  if (!$is_admin) {
    foreach ($arrnodes as $child) {
      $test = isParentNode($currentUserNodeId, $child);
      if ($test == false) {
        echo get_string('notallowtoaccess', 'block_reporting');
        exit();
      }
    }
  }
  if (!empty($currentUserNodeId) && (in_array($currentUserNodeId, $arrnodes)) && (!$is_admin))
  //if user selected their postion node in the hierarchy. List of users will include their user_id
    $arr_users [] = $USER->id;
  else {
    // Add all users in selected nodes into the list
    $sql_user = "SELECT user_id from mdl_hierarchy_user where node_id in($selectednodes) group by user_id";
    $rs_user = $DB->get_records_sql($sql_user);
    if ($rs_user) {
      foreach ($rs_user as $row_user) {
        $arr_users [] = $row_user->user_id;
      }
    }
  }
  // Get all users under the selected nodes
  $nodes_queue = array();
  foreach ($arrnodes as $node_id) {
    // Find all children nodes of current node
    //if(!in_array($node_id, $nodes_queue)) $nodes_queue [] = $node_id;
    $all_children_nodes = findChildrenNodes($node_id);

    if (!empty($all_children_nodes)) {
      foreach ($all_children_nodes as $child_node_id) {
        if (!in_array($child_node_id, $nodes_queue))
          $nodes_queue [] = $child_node_id;
      }
    }
    // Include the current nodes as well => This will other user can see each others in the same node
    // $nodes_queue [] = $node_id; // => Should be disabled
  }
  // Get all users in these nodes: nodes_queue.
  $all_users = array();
  if (!empty($nodes_queue)) {
    $list = implode(',', $nodes_queue);
    $sql = "select user_id from mdl_hierarchy_user where node_id in($list)";
    $all_users = $DB->get_records_sql($sql, array());
  }
  foreach ($all_users as $r) {
    $arr_users [] = $r->user_id;
  }

  $list = implode(',', $arr_users);
  return $list;
}

// Find all child users under this node only. Not include users in the current node
// @Array of selected nodes
//@ Return a list of users.
function getAllChildUsersFromNodes($selectednodes) {
  global $DB;
  if ($selectednodes == null)
    return false;
  $arrnodes = explode(',', $selectednodes);
  $nodes_queue = array();
  $list = "";
  foreach ($arrnodes as $node_id) {
    // Find all children nodes of current node
    // if(!in_array($node_id, $nodes_queue)) $nodes_queue [] = $node_id;
    $all_children_nodes = findChildrenNodes($node_id);
    foreach ($all_children_nodes as $child_node_id) {
      if (!in_array($child_node_id, $nodes_queue))
        $nodes_queue [] = $child_node_id;
    }
  }
  // Get all users in these nodes: nodes_queue.
  if (!empty($nodes_queue)) {
    $list = implode(',', $nodes_queue);
    $sql = "select user_id from mdl_hierarchy_user where node_id in($list)";
    $all_users = $DB->get_records_sql($sql, array());
    $arr_users = array();
    foreach ($all_users as $r) {
      $arr_users [] = $r->user_id;
    }
    $list = implode(',', $arr_users);
  }
  return $list;
}

//@ Get all user FROM SELECTED NODE AND CHILDREN NODES
function getAllUsersFromNode($selectednode) {
  global $DB;
  if ($selectednode == null)
    return false;
  $nodes_queue = array($selectednode);

  $all_children_nodes = findChildrenNodes($selectednode);
  if (!empty($all_children_nodes)) {
    foreach ($all_children_nodes as $child_node_id) {
      if (!in_array($child_node_id, $nodes_queue))
        $nodes_queue [] = $child_node_id;
    }
  }
  // Get all users in these nodes: nodes_queue.
  $list = implode(',', $nodes_queue);
  $sql = "select user_id from mdl_hierarchy_user where node_id in($list)";
  $all_users = $DB->get_records_sql($sql, array());
  $arr_users = array();
  foreach ($all_users as $r) {
    $arr_users [] = $r->user_id;
  }
  $list = implode(',', $arr_users);
  return $list;
}

// Get the label for the selected nodes
function getSelectedNodeNames($arr_selectednodes) {
  global $DB;
  $arr = array();
  if (!empty($arr_selectednodes)) {
    foreach ($arr_selectednodes as $nodeid => $value) {
      // Display the description in the label
      $arr[$nodeid]['label'] = $DB->get_field('hierarchy_node', 'name', array('id' => $nodeid));
      $arr[$nodeid]['completed'] = 0;
      $arr[$nodeid]['num_users'] = 0;
    }
  }
  return $arr;
}
if(!function_exists('findrootnode')){
  function findRootNode(){
    global $DB;
    // root node's parent value can be either 0 or NULL
    $root = is_object($DB->get_record('hierarchy_node',array('parent_node_id'=>0))) ? $DB->get_record('hierarchy_node',array('parent_node_id'=>0)) : $DB->get_record('hierarchy_node',array('parent_node_id'=>NULL));
    return $root;
  }
}

/**
*   Find all users under the given node, including the users who are assigned to the nodes that are children nodes of this node
*   @param $node_id The node that needs to be searched
*   @return all users under this node, and child nodes of this node ... ...(loop)
*
* 
*/
function findNodesUserIdsUnderNode($node_id) {
    global $DB;

    $all_users_ids = findAllUserIdsUnderNode($node_id); //get all users for current node

    $children_node_ids = findChildrenNodes($node_id); 

    foreach($children_node_ids as $child_node_id) {
        $all_users_ids = array_merge($all_users_ids, findAllUserIdsUnderNode($child_node_id));
    }

    return array_unique($all_users_ids);
}

// Find all children nodes under this node_id only. Not include current node
// @ Return array of children node
if(!function_exists('findchildrennodes')){
  function findChildrenNodes($node_id) {
      global $DB;
      $all_children_node_ids = array();
      $children_node_ids_queue = array();
      $lower_level_children_nodes_records = $DB->get_records('hierarchy_node', array('parent_node_id'=>$node_id));
      if ($lower_level_children_nodes_records != false) {
          foreach ($lower_level_children_nodes_records as $lower_level_children_nodes_record) {
              $all_children_node_ids[] = $lower_level_children_nodes_record->id;
              $children_node_ids_queue[] = $lower_level_children_nodes_record->id;
          }
      }
      
      while (count($children_node_ids_queue) != 0 ) {
          foreach ($children_node_ids_queue as $index=>$child_node_id) {
              $lower_level_children_nodes_records = $DB->get_records('hierarchy_node', array('parent_node_id'=>$child_node_id));
              if ($lower_level_children_nodes_records != false) {
                  foreach ($lower_level_children_nodes_records as $lower_level_children_nodes_record) {
                      $all_children_node_ids[] = $lower_level_children_nodes_record->id;
                      $children_node_ids_queue[] = $lower_level_children_nodes_record->id;
                  }
              } 
  
                  /* remove node from queue */
              unset($children_node_ids_queue[$index]);
          }
  
      }
      return $all_children_node_ids;    
  }
}
// Find all users in this node
// @ Return array of user_id
function findAllUserIdsUnderNode ($node_id) {
    global $DB;
    $all_users_ids = array();
    $node_user_records = $DB->get_records('hierarchy_user', array('node_id'=>$node_id));
    if ($node_user_records != false) {
        foreach ($node_user_records as $node_user_record) {
            $all_users_ids[] = $node_user_record->user_id;
        }
    }

    return $all_users_ids;
}

function getNodeByUserId($user_id) {
  global $DB;
  $node_id = $DB->get_field(
          'hierarchy_user', 'node_id', array('user_id' => $user_id)
  );
  $node = $DB->get_record('hierarchy_node', array('id' => $node_id));
  return $node;
}

function getHierarchyManagerId($user_id){
	global $DB;

	$query = 'SELECT mgr.user_id
		FROM {hierarchy_user} hu
		JOIN {hierarchy_node} hn
		ON hn.id = hu.node_id
		JOIN {hierarchy_node} mgr_node
		ON mgr_node.id = hn.parent_node_id
		JOIN {hierarchy_user} mgr
		ON mgr.node_id = mgr_node.id
		WHERE hu.user_id = ?';
	
	$managers = $DB->get_records_sql($query, [
		$user_id
	]);

	if (!empty($managers)) {
		$first_manager = reset($managers);

		return $first_manager->user_id;
	}

	return false;
}

function getHierarchyManagers($user_id = null) {
  global $DB, $USER;
  if (!isset($user_id)) {
    $user_id = $USER->id;
  }
  $sql = <<< EOT
  SELECT * FROM {user} 
  WHERE id IN (
    SELECT mgr.user_id
    FROM {hierarchy_user} hu 
    JOIN {hierarchy_node} hn ON hn.id = hu.node_id
    JOIN {hierarchy_node} mgr_node ON mgr_node.id = hn.parent_node_id
    JOIN {hierarchy_user} mgr ON mgr.node_id = mgr_node.id
    WHERE hu.user_id = ?
  );
EOT;
  $managers = $DB->get_records_sql($sql, array($user_id));
  return $managers;
}

function getHierarchyUsersInSameNode($user_id = null) {
  global $DB, $USER;
  
  $params = array();
  
  if (!isset($user_id)) {
    $user_id = $USER->id;
  }
  $where    = 'where user_id = ?';
  $params[] = $user_id;
  
  $sql = <<< EOT
  select * from mdl_hierarchy_user 
  where node_id in (select node_id from mdl_hierarchy_user $where);
EOT;
  $rs = $DB->get_records_sql($sql, $params);
  return $rs;
}

function isManager($user_id = null) {
  global $USER;
  $result = false;
  if (!isset($user_Id)) {
    $user_id = $USER->id;
  }
  $node = getNodeByUserId($user_id);
  if ($node) {
    $nodes = findChildrenNodes($node->id);
    if ($nodes && count($nodes) > 0) {
      $result = true;
    }
  }
  return $result;
}

function isTeamMember($user_id = null) {
  global $USER;
  $result = false;
  if (!isset($user_Id)) {
    $user_id = $USER->id;
  }
  $node = getNodeByUserId($user_id);
  if ($node) {
    $nodes = findChildrenNodes($node->id);
    if (!$nodes || count($nodes) == 0) {
      $result = true;
    }
  }
  return $result;
}

function findNextAvailableManagers($userid) { 
  // 1. Get parent node id by userid
  // 2. Get userid(s) by node id
  // 3. if no userid, get parent node id by node id 
  // 4. repeat 2 and 3 until it returns userid(s)
  // 5. repeat step 
  $managerids = null;
  $parent_node_id = getParentNodeIdByUserId($userid);
  while (true) {
    $managerids = getUserIdByNodeId($parent_node_id);
    if ($parent_node_id == 1 || $managerids) {
      // reached the top, no more to search
      break;
    }
    
    // find none, continue to search
    $parent_node_id = getParentNodeIdByNodeId($parent_node_id);
  }
  
  global $DB;
  list($query, $params) = $DB->get_in_or_equal($managerids);
  if ($query) {
    $query = 'WHERE id ' . $query;
  }
  return $DB->get_records_sql('SELECT * FROM mdl_user ' . $query, $params);
}

function getParentNodeIdByNodeId($nodeid) {
  global $DB;
  return $DB->get_field('hierarchy_node', 'parent_node_id', array('id' => $nodeid));  
}

function getUserIdByNodeId($nodeid) {
  global $DB;
  $records = $DB->get_records('hierarchy_user', array('node_id' => $nodeid), '', 'user_id');
  if ($records) {
    return array_keys($records);
  }
  return false;
}

function getParentNodeIdByUserId($userid) {
  global $DB;
  $sql = <<< EOT
	select parent_node_id from mdl_hierarchy_node n where n.id = (
		select node_id from mdl_hierarchy_user u where u.user_id = ?
	)
EOT;
  return $DB->get_field_sql($sql, array($userid));
}

/**
 * Given a userid, return all the manager ids in parent and ancestor nodes in the hierarchy
 * @param int $userid
 * @return array of manager ids
 */
function findAllManagerIdsRecursively($userid = null) {
  
  if (!isset($userid)) {
    return false;
  }
  
  $managerids = array();

  $parent_node_id = getParentNodeIdByUserId($userid);
  while (true) {
    $ids = getUserIdByNodeId($parent_node_id);
    if ($ids) {
      if (is_array($ids)) {
        $managerids = array_merge($managerids, $ids);
      }
      else {
        $managerids[] = $ids;
      }
    }
    if ($parent_node_id == 1) {
      // reached the top, no more to search
      break;
    }

    $parent_node_id = getParentNodeIdByNodeId($parent_node_id);
  }
  $managerids = array_unique($managerids);
  sort($managerids);
  
  return $managerids;
}
