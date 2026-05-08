<?php

define('PRACTITIONER', 'Family Services Practitioner/ Case worker');
define('PROGRAM_MANAGER', 'Family Services Program Manager');
define('TEAM_LEADER', 'Family Services Team Leader');
define('CP_NAVIGATOR', 'Child Protection Navigator');
define('PRACTICE_LEAD', 'Family Services Practice Lead or Principal Practitioner');
define('IMPLEMENTATION_LEAD', 'Implementation Coordinator/ Lead');

//This function is used to generate Position Code and Report To fields
//If clients does not have these fields, then these fields will be worked by their business rules.
//Please update the fields by using function get_hiearchy_fields();
function update_PositionCode_ReportTo_field()
{
  // $hierarchy_fields = get_hierarchy_fields(); //Uncomment this if the function is used.

}
/**
 * The funcion return hierachy field configuration
 * @return - array of three values: postioncode, reportto, position title
 */
function get_hierarchy_fields()
{
  $array = array();
  $array[] = get_config('tool_hierarchy', 'hierarchy_positioncode');
  $array[] = get_config('tool_hierarchy', 'hierarchy_reportto');
  $array[] = get_config('tool_hierarchy', 'hierarchy_positiontitle');
  return $array;
}
/**
 * The function return site admins 
 * @return string - contains admin id seperated by comma. ex "1,2,3"
 * @author Khang Cao
 * @version 20220424
 */
function get_site_admins()
{
  global $DB;
  // get admin ids from config
  $admin_users_sql = "SELECT value 
              FROM   mdl_config 
              WHERE  name = ?";
  $admin_users_record = $DB->get_record_sql($admin_users_sql, array('siteadmins'));

  $admin_user_ids = $admin_users_record->value;
  return $admin_user_ids;
}
/**
 * The function generate hieracy_user_parent_data table
 * @param array - users
 */
function generate_hierarchy_user_parent_table($valid_users)
{
  global $DB;
  foreach ($valid_users as $user) {
    if (!$DB->record_exists('hierarchy_user_parent_data', array('user_id' => $user->user_id))) {
      $data = new stdClass();
      $data->user_id = $user->user_id;
      $data->position_code = $user->position_code;
      $data->position_level = $user->position_level;
      $data->position_title = $user->position_title;
      $id = $DB->insert_record('hierarchy_user_parent_data', $data);
    }
  }
  return;
}
function dffh_get_unassign_users()
{
  global $DB;
  $admin_users_string = get_site_admins();
  $admin_user_ids = $admin_users_string;
  $IsPractitioner_fieldid = 5;
  $RoleOrPosition_fieldid = 2;
  $OrganisationOrAgency_fieldid = 1;
  $sql_str = "SELECT    {user}.id,{user}.username,
                        user_info_practitioner.data AS isPractitioner,
                        user_info_role.data AS role,
                        user_info_angency.data as agency
                FROM      {user} 
                LEFT JOIN {user_info_data} user_info_practitioner
                ON        {user}.id = user_info_practitioner.userid AND user_info_practitioner.fieldid = $IsPractitioner_fieldid
                LEFT JOIN {user_info_data} user_info_role
                ON        {user}.id = user_info_role.userid AND user_info_role.fieldid = $RoleOrPosition_fieldid
                LEFT JOIN {user_info_data} user_info_angency
                ON        {user}.id = user_info_angency.userid AND user_info_angency.fieldid = $OrganisationOrAgency_fieldid
                WHERE     {user}.id not in (0) 
                AND     (user_info_angency.data = '' OR user_info_angency.data IS NULL OR user_info_role.data = '' OR user_info_role.data IS NULL)
                AND     (user_info_practitioner.data = 'No' OR (user_info_practitioner.data = 'Yes' AND (user_info_role.data = '".TEAM_LEADER."' OR user_info_role.data = '".PRACTITIONER."')))
                AND     {user}.deleted = 0
                AND     {user}.suspended = 0";

  $hierarchy_user_info_records = $DB->get_records_sql($sql_str);
  return $hierarchy_user_info_records;
}
/**
 * The function return all valid users for building the hierarch. 
 * @author Khang Cao
 * @version 20220424
 */
function dffh_get_valid_users()
{
  global $DB;
  $admin_users_string = get_site_admins();
  $admin_user_ids = $admin_users_string;
  $RoleOrPosition_fieldid = 2;
  $OrganisationOrAgency_fieldid = 1;
  $sql_str = "SELECT    {user}.id,{user}.username,
                        user_info_role.data AS role,
                        user_info_angency.data as agency
                FROM      {user} 
                LEFT JOIN {user_info_data} user_info_role
                ON        {user}.id = user_info_role.userid AND user_info_role.fieldid = $RoleOrPosition_fieldid
                LEFT JOIN {user_info_data} user_info_angency
                ON        {user}.id = user_info_angency.userid AND user_info_angency.fieldid = $OrganisationOrAgency_fieldid
                WHERE     {user}.id not in (0) 
                AND     (user_info_angency.data <> '' AND user_info_angency.data IS NOT NULL AND user_info_role.data <> '' AND user_info_role.data IS NOT NULL)
                AND     {user}.deleted = 0
                AND     {user}.suspended = 0";
  $valid_users = $DB->get_records_sql($sql_str );
  return $valid_users;
}
/**
 * The function creates hierarchy level if it does not exist
 * @param level_num - the deapth of the hierachy tree
 * @return integer - level id in the database
 */
function create_hierarchy_level($level_num)
{
  global $DB;
  if (!$DB->record_exists('hierarchy_level', array('level' => $level_num))) {
    $level = new stdClass();
    $level->level = $level_num;
    $level->description = $level->level . " level";
    $level_id = $DB->insert_record('hierarchy_level', $level);
    return $level_id;
  }
  $level = $DB->get_record('hierarchy_level', array('level' => $level_num));
  return $level->id;
}


/**
 * The function creates hierarchy node in the hierarchy tree if the node name does not exist
 * @return boolean|integer - false if the node exist or node id in the database
 * @author Khang Cao
 * @version 20220424
 */
function create_hierachy_node($node_name, $decription = null, $parent_node_id = 1, $level_id = -1)
{
  // parent node is integer number and default is root node id 
  // level is not used in the hierarchy
  global $DB;
  if ($node_name == null)
    return null;
  if (!$DB->record_exists('hierarchy_node', array('name' => $node_name))) {
    $node = new stdClass();
    $node->name = $node_name;
    if ($decription == null)
      $node->description = $node_name;
    else
      $node->description = $decription;
    $node->level_id = $level_id;
    if ($parent_node_id != -1)
      $node->parent_node_id = $parent_node_id;
    if ($parent_node_id == null)
      $node->parent_node_id = 1;
    $node_id = $DB->insert_record('hierarchy_node', $node);
    return $node_id;
  }
  $node = $DB->get_record('hierarchy_node', array('name' => $node_name));
  return $node->id;
}
/**
 * function assigns selected user to given node
 * @return boolean - true if the allocation is successful and false if the user is in the node or already assigned to another node
 * @author Khang Cao
 * @version 20220424
 */
function add_user_to_hierarchy_node($node_id, $user_id)
{
  global $DB;
  if ($node_id == null || $user_id == null)
    return false;
  if (!$DB->record_exists('hierarchy_user', array('user_id' => $user_id))) {
    $hierarchy_user = new stdClass();
    $hierarchy_user->node_id = $node_id;
    $hierarchy_user->user_id = $user_id;
    $DB->insert_record('hierarchy_user', $hierarchy_user);
    return true;
  }
  return false;
}

function truncate_hierarchy_tables()
{
  global $DB;
  try {
    /* clear all related tables */
    $transaction = $DB->start_delegated_transaction();
    $trunc_user = "TRUNCATE table {hierarchy_user}";
    $trunc_node = "TRUNCATE table {hierarchy_node}";
    $trunc_level = "TRUNCATE table {hierarchy_level}";
    $trunc_user_parent_data = "TRUNCATE table {hierarchy_user_parent_data}";
    $DB->execute($trunc_user);
    $DB->execute($trunc_node);
    $DB->execute($trunc_level);
    $DB->execute($trunc_user_parent_data);
    echo "Successful: clear all existing data" . "</br>";
    $transaction->allow_commit();
  } catch (Exception $e) {
    echo "Error : process cannot clear all existing data" . "</br>";
    $transaction->rollback($e);
  }
}
/**
 * the function crate root node in the hierarchy
 * @author Khang cao
 * @version 20220729
 */
function dffh_create_root_node()
{
  global $DB;
  $admin_users_string = get_site_admins();
  $admin_user_ids = $admin_users_string;
  $admin_user_ids_array = explode(",", $admin_user_ids);
  try {
    $transaction = $DB->start_delegated_transaction();
    /* add root node 
     * all admin users will be added to the root node
     */
    /* create the first level */
    $root_node_id;
    $admin_user_ids;
    $first_level_id =  create_hierarchy_level(1);
    /* create the root node */
    $root_node_id  = create_hierachy_node(get_config('tool_hierarchy', 'hiearchy_rootname'), null, -1, $first_level_id);
    if (count($admin_user_ids_array) != 0) {
    } else {
      echo "Error: there is no admin users" . "</br>";
    }
    $transaction->allow_commit();
    echo "Successful: process build up the first level hierarchy." . "</br>";
  } catch (Exception $e) {
    //$transaction->rollback($e);
    echo "Error: process cannot build up the first level hierarchy." . "</br>";
    exit();
  }
}
/**
 * the function crate unassigned node in the hierarchy
 * @author Khang cao
 * @version 20220729
 */
function dffh_create_unassigned_node()
{
  global $DB;
  $unassign_name = "Unassigned users";
  $tmp_level_id = create_hierarchy_level(2);
  $unassign_node_id = create_hierachy_node($unassign_name, null, 1, $tmp_level_id);
  $unassign_users = dffh_get_unassign_users();
  echo "There are ". sizeof($unassign_users). " unassinged users<br>";
  /* create the second level */
  try {
    /*Create unassigned node containing all users which does not have position code and report to*/
    $unassigned_count = 0;
    if (!empty($unassign_users)) {
      foreach ($unassign_users as $unassign_user) {
        $user_id = $unassign_user->id;
        if (add_user_to_hierarchy_node($unassign_node_id, $user_id)) {
          $unassigned_count++;
        }
      }
    }
  } catch (Exception $e) {
    echo $e;
    echo "Error: process cannot build up the second level hierarchy." . "</br>";
    exit();
  }
}
/**
 * function create agency nodes
 * agency nodes are created from user_info_field - OrganisationOrAgency
 * position values: 
 * Practitioner
 * Team Leader
 * Program Manager
 */
function dffh_create_agency_nodes(){
  global $DB;
  $OrganisationOrAgency_data = $DB->get_record('user_info_field', array('shortname'=>'OrganisationOrAgency'));
  if($OrganisationOrAgency_data == false) return;
  $agency_data = preg_split("/\r\n|\n|\r/", $OrganisationOrAgency_data->param1);
  foreach ($agency_data as $agency){
    $agency_node_id = create_hierachy_node($agency,null,1,2);
    // var_dump($agency_node_id);
    if($agency_node_id == false) continue;
    $teamlead_node_id = create_hierachy_node($agency.'-'.TEAM_LEADER,null,$agency_node_id,3);
    create_hierachy_node($agency.'-'.PRACTITIONER,null,$teamlead_node_id,4);

  }
}

/**
 * The function create practice lead node
 */
function dffh_create_practicelead_node(){
  global $DB;
  // Practitioner
  // Team Leader
  // CP Navigator
  // Program Manager
  // Practice Lead
  // Implementation Coordinator/Lead
  $IsPractitioner_fieldid = 5;
  $sql_str= "SELECT * FROM mdl_user_info_data WHERE fieldid = $IsPractitioner_fieldid AND data ='Yes' ";
  // $practicelead_users = $DB->get_records('user_info_data', array('fieldid' => $IsPractitioner_fieldid, 'data'=>'Yes'));
  $practicelead_users = $DB->get_records_sql($sql_str);

  $practicelead_node_id = create_hierachy_node(PRACTICE_LEAD,null,1,2);
  foreach($practicelead_users as $user){
      $lead_user = $DB->get_records_sql("SELECT * FROM mdl_user_info_data WHERE userid = $user->userid AND fieldid = 2 AND (data = '" .TEAM_LEADER . "' OR data = '" .PRACTITIONER . "') ");

      if (empty($lead_user)) {
          add_user_to_hierarchy_node($practicelead_node_id,$user->userid );
      }
  }
}

/**
 * This function will allocate the users to the nodes based on their role. 
 * If the user's "Organisational Admin" checkbox is checked, the user will be assigned to the organisational level node.
 */
function dffh_allocate_users_to_nodes(){
  global $DB;
  dffh_get_valid_users();
  $valid_users = dffh_get_valid_users();
  echo "There are ". sizeof($valid_users). " valid users<br>";
  if($valid_users  == false) return;
  
 


  foreach($valid_users as $user){
  
    // var_dump($user); 
     //Implementation Coordinator/Lead, Program Manager, Team Leader are in the same nod

    if($user->role == IMPLEMENTATION_LEAD || strpos($user->role, PROGRAM_MANAGER) || strpos($user->role, TEAM_LEADER) !== false){
      $user->role = TEAM_LEADER;
     } else {
      $user->role = PRACTITIONER;
     }

    $fieldid = $DB->get_field('user_info_field', 'id', ['shortname' => 'Organisationaladmin']);
    if ($fieldid) {
      $isorgadmin = $DB->get_field('user_info_data', 'data', [
          'userid' => $user->id,
          'fieldid' => $fieldid
      ]);
      
      if ($isorgadmin == '1') {
          $user->role = 'Organisational Admin';
      }
    }

    $sql_str = "SELECT * 
              FROM  {hierarchy_node}
              WHERE name like '$user->agency-$user->role'";
    $user_node = $DB->get_record_sql($sql_str);
    //if can not find the node, add user to unassigned node
    if(in_array($user->role, [
      'Family Services Practice Lead or Principal Practitioner',
      'Family Services executive/ leadership role',
      'Stakeholder',
      'Organisational Admin',
    ])){
      $sql_str = "SELECT * 
              FROM  {hierarchy_node}
              WHERE name like '$user->agency'";
    $user_node = $DB->get_record_sql($sql_str);
    add_user_to_hierarchy_node($user_node->id, $user->id);
    } else if($user_node == false){
      echo "$user->id-$user->agency-$user->role <br>";
      add_user_to_hierarchy_node(2, $user->id);
    }else{
      add_user_to_hierarchy_node($user_node->id, $user->id);
    }

  }

}
/**
 * the function rebuild the hierarchy tree with the following steps
 * 1. create root node which contains all site administrators
 * 2. create unassigned users node which contains all unassigned users
 * 3. create agency nodes (organisation nodes) 
 * 3. create position nodes under each agency nodes: 
 * 3.1 team leader, practioner, program manager, implemetation coordinator are in the same node
 * 3.2 CP navigator node
 * 3.3 Practitioner node
 * 4. create DFFH imp Sci lead node
 * 5. create CP Navigator node
 * 6. create practice lead
 * @author Khang Cao
 * @version 20220729
 */
function rebuild_hierarchy()
{
  global $DB;
  truncate_hierarchy_tables();
  create_hierarchy_level(1);
  create_hierarchy_level(2);
  create_hierarchy_level(3);
  create_hierarchy_level(4);
  
  dffh_create_root_node();
  dffh_create_unassigned_node();
  dffh_create_agency_nodes();
  dffh_create_practicelead_node();
  dffh_allocate_users_to_nodes();


}

function assign_new_users_to_unassignnode($unassign_nodeid)
{
  global $DB, $CFG;
  $sql = "select id,firstname,lastname from mdl_user WHERE id not in(select user_id from mdl_hierarchy_user group by user_id) AND username != 'guest' AND suspended = 0 AND deleted=0 group by id";
  $rs = $DB->get_records_sql($sql);
  if (!empty($rs)) {
    foreach ($rs as $user) {
      if (($user->id == "") || ($user->id == NULL)) continue;
      if (!$DB->record_exists('hierarchy_user', array('user_id' => $user->id))) {
        $new_record = new stdClass();
        $new_record->node_id = $unassign_nodeid;
        $new_record->user_id = $user->id;
        // echo "<pre>".print_r($new_record)."</pre>";
        $DB->insert_record('hierarchy_user', $new_record);
        echo "<br> User " . $user->firstname . " " . $user->lastname . " (ID: " . $user->id . ") has been added into unassign node: " . $unassign_nodeid;
      }
    }
  }
}

function get_hiearchy_profile_fields()
{
  global $DB;
  $rs = $DB->get_records('user_info_field', array());
  $fields = array('' => '');
  if (!empty($rs)) {
    foreach ($rs as $row) {
      $fields[$row->id] = $row->name;
    }
  }
  return $fields;
}

//============================ Hierarchy Functions ==============
function get_all_hierarchy_tree($res)
{
  global $DB;
  global $pos;
  /* get hierarchy information */
  // if the user belong to admin. It will show everyone of the tree.
  // Otherwise, it will show their brand from their position node in the tree

  // Check if position title exist, it will show in the hierarchy title:
  // $position = array('','','','');
  // $pos = false; // default, there are no PositionTitle
  // $pos_field_id = $DB->get_field('user_info_field', 'id', array('shortname'=>'PositionTitle'));
  if (!empty($pos_field_id)) $pos = true;

  $node_user_record = findrootnode();
  $all_children_node_ids = array();
  $hierarchy_nodes = array();
  if ($node_user_record != false) {
    $all_children_node_ids = findchildrennodes($node_user_record->id);

    $all_children_node_sql = "
          select n.id node_id,n.name node_name,n.description node_description,n.parent_node_id parent_id, l.level level,count(hu.user_id) num_user  
          from mdl_hierarchy_node n
          inner join mdl_hierarchy_level l on 
          n.level_id = l.id 
          left join mdl_hierarchy_user hu on hu.node_id=n.id 
          group by node_id,node_name,level,parent_id
          order by level,node_name desc";
    $all_children_node_records = $DB->get_records_sql($all_children_node_sql);

    if ($all_children_node_records != false) {
      foreach ($all_children_node_records as $child_node_record) {
        $child_node = new stdclass;
        $child_node->id = $child_node_record->node_id;
        $child_node->name = $child_node_record->node_name;
        if (trim($child_node_record->node_description) != "" && ctype_digit($child_node->name)) $child_node->name .= " - " . $child_node_record->node_description;
        $child_node->level = $child_node_record->level;
        $child_node->parent_id = $child_node_record->parent_id;
        $child_node->num_user = $child_node_record->num_user;
        $hierarchy_nodes[] = $child_node;
      }
    }
  }
  if ($res) {
    $hierarchy_nodes = recursive_num_users($hierarchy_nodes);
  }
  $hierarchy_nodes = sorthierarchynodes($hierarchy_nodes);
  return $hierarchy_nodes;
}

// recursive. Users in parent nodes should include all children nodes users including grand-children and lower level
function recursive_num_users($nodes)
{
  $arr_nodes = array();
  $i = $j = count($nodes) - 1;

  while ($i >= 0) {
    $total = $nodes[$i]->num_user;
    $j = count($nodes) - 1;
    while ($j >= 0) {
      //echo "IJ: ".$i." - ".$j."<br>";
      //echo $nodes[$i]->id." - ".$nodes[$j]->parent_id."<br>";
      if ($nodes[$i]->id == $nodes[$j]->parent_id) {
        $total = $total + $nodes[$j]->num_user;
      }
      $j = $j - 1;
    }
    $nodes[$i]->num_user = $total;
    $i = $i - 1;
  }

  foreach ($nodes as $row) {
    $newnode = new stdclass();
    $newnode->id = $row->id;
    $newnode->name = $row->name;
    $newnode->level = $row->level;
    $newnode->parent_id = $row->parent_id;
    $newnode->num_user = $row->num_user;
    $arr_nodes[] = $newnode;
  }
  return $arr_nodes;
}

// Find all children noodes under this node_id
// @ Return array of children node
if (!function_exists('findchildrennodes') || !function_exists('findChildrenNodes')) {
  function findchildrennodes($node_id)
  {
    global $DB;
    $all_children_node_ids = array();
    $children_node_ids_queue = array();
    $lower_level_children_nodes_records = $DB->get_records('hierarchy_node', array('parent_node_id' => $node_id));
    if ($lower_level_children_nodes_records != false) {
      foreach ($lower_level_children_nodes_records as $lower_level_children_nodes_record) {
        $all_children_node_ids[] = $lower_level_children_nodes_record->id;
        $children_node_ids_queue[] = $lower_level_children_nodes_record->id;
      }
    }

    while (count($children_node_ids_queue) != 0) {
      foreach ($children_node_ids_queue as $index => $child_node_id) {
        $lower_level_children_nodes_records = $DB->get_records('hierarchy_node', array('parent_node_id' => $child_node_id));
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

/**
 *  sort the hierarchy nodes in an tree order 
 *  for displaying the hierarchy dropdown list in GR filter page
 *  
 *  @author C.C. - MA
 *  @param &$node the actually hierarchy nodes
 *  @return the sorted hierarchy nodes
 */
function sorthierarchynodes($nodes)
{
  global $pos;
  $stack = array();
  $list = array();

  // add the highest level nodes to stack
  // var_dump($nodes);
  $highest_level = $nodes[0]->level;

  // if($highest_level == 2) { // this is admin
  //     $stack[] = $nodes[0];
  //     $highest_level = 3;
  // }
  foreach ($nodes as $node) {
    if ($node->level == $highest_level)
      // if($node->level == 3)
      $stack[] = $node;
  }
  // echo "<pre>", print_r($stack, true), "</pre>";

  // $count = 0;
  // echo "<br>";

  while (!empty($stack)) {
    $cur = current($stack); //find child for the first in stack
    array_shift($stack); // shift the first off
    $list[] = $cur; // add to list
    // echo $count."|";
    // echo "<pre>", print_r($cur, true), "</pre>";

    //find children of the current node, and add to the beginning
    foreach ($nodes as $key => $val) {
      // echo "IN FOR EACH<pre>", $key, "|", print_r($val, true), "</pre>";

      if ($nodes[$key]->parent_id == $cur->id) {
        array_unshift($stack, $nodes[$key]);
        // echo "[added]";
      }
      // unset checked one
      // unset($nodes[$key]);
    }

    // reset the pointer
    reset($stack);
  }

  // change to JS TREE format
  $count = 0;
  $jstree = array();
  foreach ($list as $l) {
    $new = new stdClass();
    $new->id = $l->id;
    $new->parent = $l->level == $highest_level ? '#' : $l->parent_id;
    $new->state = $l->level == $highest_level ? array('selected' => true) : array('selected' => false); // to select the highest by default
    if (strcmp($l->name, "root") === 0) {
      $new->text = "root";
    } else {
      $new->text = $l->name . " (" . $l->num_user . " users)";
      // if($pos) $new->text.=' - '.$l->position_title;
    }
    $jstree[] = $new;
  }
  // return $list;
  return json_encode($jstree);
}

if (!function_exists('findrootnode') || !function_exists('findRootNode')) {
  function findrootnode()
  {
    global $DB;
    // root node is parent value is 0 or NULL
    $root = is_object($DB->get_record('hierarchy_node', array('parent_node_id' => 0))) ? $DB->get_record('hierarchy_node', array('parent_node_id' => 0)) : $DB->get_record('hierarchy_node', array('parent_node_id' => NULL));
    return $root;
  }
}
function get_node($node_id)
{
  global $DB;
  $node = $DB->get_record('hierarchy_node', array('id' => $node_id));
  return $node;
}

//function check is hierarchy install
function is_hierarchyfeature_installed()
{
  global $DB;
  if ($DB->record_exists('config_plugins', array('plugin' => 'tool_hierarchy'))) return true;
  return false;
}
function assign_admin_user_root_node($admin_id, $root_id)
{
  global $DB;
  if (!$DB->record_exists('hierarchy_user', array('node_id' => $root_id, 'user_id' => $admin_id))) {
    $DB->delete_record('hierarchy_user', array('user_id' => $admin_id));
    $new = new stdClass();
    $new->node_id = $root_id;
    $new->user_id = $admin_id;
    $DB->insert_record('hierarchy_user', $new);
    echo "<br> User ID: " . $admin_id . " has been added into root node of hierarchy - root_id: " . $root_id;
  }
}
// By default, this unassign node will be point to ROOT node. So, they will report to ROOT node. 
// If any hierarchy has been changed in payroll, these unassign users might be moved to other node(not unassign node).
function get_unassign_node($root_id)
{
  global $DB;
  $unassign_name = "Unassigned users";
  $level_2_id = $DB->get_field('hierarchy_level', 'id', array('level' => '2'));
  $unassign_node_id = $DB->get_field('hierarchy_node', 'id', array('name' => $unassign_name, 'level_id' => $level_2_id), IGNORE_MISSING);
  if ($unassign_node_id == false) { // No record return
    $new_node = new stdClass();
    $new_node->name = $unassign_name;
    $new_node->description = $unassign_name;
    $new_node->level_id = $level_2_id;
    $new_node->parent_node_id = $root_id;
    $unassign_node_id = $DB->insert_record('hierarchy_node', $new_node);
    echo "<br> New node has been created with name: " . $unassign_name . " - ID: " . $unassign_node_id;
  }
  return $unassign_node_id;
}


/** 
 * @return bool
 * @param  int $user_id  
 *
 * @author Jacky 2018062000, 
 * @version 2018062000
 */
function hierarchy_get_user_node($user_id)
{
  global $DB;

  $user_node = $DB->get_record('hierarchy_user', array('user_id' => $user_id));
  if ($user_node) {
    return $DB->get_record('hierarchy_node', array('id' => $user_node->node_id));
  } else {
    return false;
  }
}

/** 
 * @return bool
 * @param  int $node_id  
 *
 * @author Jacky 2018062000, 
 * @version 2018062000
 */
function hierarchy_is_parent_node($node_id)
{
  global $DB;

  $result = false;

  $records = $DB->get_records_sql('select parent_node_id from mdl_hierarchy_node group by parent_node_id', array(''));

  foreach ($records as $key => $record) {
    if ($node_id == $record->parent_node_id) {
      $result = true;
    }
  }

  return $result;
}


/** 
 * @return bool
 * @param  int $user_id  
 *
 * @author Jacky 2018062000, 
 * @version 2018062000
 */
function hierarchy_is_parentnode_user($user_id)
{
  global $DB;

  $result = false;

  $user_node = hierarchy_get_user_node($user_id);
  if ($user_node) {
    return hierarchy_is_parent_node($user_node->id);
  }

  return $result;
}


/** 
 * get all nodes below
 * REFER TO recursive_num_users($nodes), findchildrennodes($node_id)
 * @return array [nodes]
 * @param  string $nodename
 *
 * @author Jacky 2018062000, 
 * @version 2018062000
 */
function hierarchy_get_descendant_nodes($nodename)
{
  global $DB;

  $all_children_nodes = array();

  $node = $DB->get_record('hierarchy_node', array('name' => $nodename));

  if (!$node) {
    return $all_children_nodes;
  }

  $node_id = $node->id;

  $all_children_node_ids = array();
  $children_node_ids_queue = array();

  $lower_level_children_nodes_records = $DB->get_records('hierarchy_node', array('parent_node_id' => $node_id));
  if ($lower_level_children_nodes_records != false) {
    foreach ($lower_level_children_nodes_records as $lower_level_children_nodes_record) {
      $all_children_node_ids[] = $lower_level_children_nodes_record->id;
      $all_children_nodes[] = $lower_level_children_nodes_record;
      $children_node_ids_queue[] = $lower_level_children_nodes_record->id;
    }
  }

  while (count($children_node_ids_queue) != 0) {
    foreach ($children_node_ids_queue as $index => $child_node_id) {
      $lower_level_children_nodes_records = $DB->get_records('hierarchy_node', array('parent_node_id' => $child_node_id));
      if ($lower_level_children_nodes_records != false) {
        foreach ($lower_level_children_nodes_records as $lower_level_children_nodes_record) {
          $all_children_node_ids[] = $lower_level_children_nodes_record->id;
          $all_children_nodes[] = $lower_level_children_nodes_record;
          $children_node_ids_queue[] = $lower_level_children_nodes_record->id;
        }
      }

      /* remove node from queue */
      unset($children_node_ids_queue[$index]);
    }
  }

  return $all_children_nodes;
}

/** 
 * get all userid in this node
 * @return array [id]
 * @param  int $nodeid
 *
 * @author Jacky 2018062000, 
 * @version 2018062000
 */
function hierarchy_get_node_userids($nodeid)
{
  global $DB;
  $ids = $DB->get_records_sql_menu('SELECT DISTINCT id,user_id FROM {hierarchy_user} WHERE node_id = ?', array($nodeid));
  if ($ids) {
    return array_values($ids);
  } else {
    return array();
  }
}

/** 
 * get all userid in/below this node
 * @return array [id]
 * @param  int $nodeid
 *
 * @author Jacky 2018062000, 
 * @version 2018062000
 */
function hierarchy_get_node_descendant_userids($nodename)
{
  global $DB;

  $descendant_nodes = hierarchy_get_descendant_nodes($nodename);

  $descendant_userids = array();

  foreach ($descendant_nodes as $key => $node) {
    $descendant_userids = array_merge($descendant_userids, hierarchy_get_node_userids($node->id));
  }

  $descendant_userids = array_unique($descendant_userids);

  return $descendant_userids;
}

/**
 * Get all nodeids below this node
 * @param int $nodeid
 * @return array A list of node objects
 */
function hierarchy_get_descendant_nodes_by_id($nodeid)
{
  global $DB;

  $all_children_nodes = array();

  $node = $DB->get_record('hierarchy_node', array('id' => $nodeid));

  if (!$node) {
    return $all_children_nodes;
  }

  $node_id = $node->id;

  $all_children_node_ids = array();
  $children_node_ids_queue = array();

  $lower_level_children_nodes_records = $DB->get_records('hierarchy_node', array('parent_node_id' => $node_id));
  if ($lower_level_children_nodes_records != false) {
    foreach ($lower_level_children_nodes_records as $lower_level_children_nodes_record) {
      $all_children_node_ids[] = $lower_level_children_nodes_record->id;
      $all_children_nodes[] = $lower_level_children_nodes_record;
      $children_node_ids_queue[] = $lower_level_children_nodes_record->id;
    }
  }

  while (count($children_node_ids_queue) != 0) {
    foreach ($children_node_ids_queue as $index => $child_node_id) {
      $lower_level_children_nodes_records = $DB->get_records('hierarchy_node', array('parent_node_id' => $child_node_id));
      if ($lower_level_children_nodes_records != false) {
        foreach ($lower_level_children_nodes_records as $lower_level_children_nodes_record) {
          $all_children_node_ids[] = $lower_level_children_nodes_record->id;
          $all_children_nodes[] = $lower_level_children_nodes_record;
          $children_node_ids_queue[] = $lower_level_children_nodes_record->id;
        }
      }

      /* remove node from queue */
      unset($children_node_ids_queue[$index]);
    }
  }

  return $all_children_nodes;
}

function hierarchy_get_node_descendant_userids_by_id($nodeid)
{
  global $DB;

  $descendant_nodes = hierarchy_get_descendant_nodes_by_id($nodeid);
  $descendant_userids = array();

  foreach ($descendant_nodes as $key => $node) {
    $descendant_userids = array_merge($descendant_userids, hierarchy_get_node_userids($node->id));
  }

  $descendant_userids = array_unique($descendant_userids);

  return $descendant_userids;
}
