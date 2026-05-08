<?php
//==================Vendor========================
// As an assumption that vendor can only see courses which they have been enrolled into.
// Therefore, they need to be enrolled into the courses first
function is_vendor($userid){
    global $DB,$USER;
    $is_vendor = false;
    if(is_siteadmin($USER->id)) return $is_vendor;
    else if($DB->record_exists('report_vendor_user',array('userid'=>$userid))) $is_vendor = true;
    return $is_vendor;
}

function is_report_administrator($context = null) {
    if (!isset($context)) {
        $context = context_system::instance();
    }
    return has_capability('block/training_report:viewallreports_as_administrators', $context);
}

function is_organisational_admin($userid = null, $context = null) {
    global $USER, $DB;
    
    if ($userid === null) {
        $userid = $USER->id;
    }
    
    $fieldid = $DB->get_field('user_info_field', 'id', ['shortname' => 'Organisationaladmin']);
    
    if (!$fieldid) {
        return false;
    }
    
    $isadmin = $DB->get_field('user_info_data', 'data', [
        'userid' => $userid,
        'fieldid' => $fieldid
    ]);
    
    return $isadmin == '1';
}

// Get vendor course_ids
// Return Array
function get_vendor_course_ids($userid){
    global $DB;
    $sql = "SELECT e.courseid from mdl_enrol as e 
   INNER JOIN mdl_user_enrolments ue on e.id=ue.enrolid 
    WHERE e.status=0 and ue.userid=$userid GROUP BY e.courseid";
    $rs = $DB->get_records_sql($sql);
    $arr_course_ids = array();
    // echo $sql;
    // echo "<pre>".print_r($rs,true)."</pre>";
    if(!empty($rs)){

        foreach ($rs as $row) {
            $arr_course_ids [] = $row->courseid;
        }
    }
    return $arr_course_ids;
}

// ======================== PLUGINS ==========================
// Delare all additional plugin will be showed in the report:
function get_plugin_installed(){
    $list_plugin = array('certificate','facetoface','performancereview','pcc','feedback');

    if(empty($list_plugin)) return false;

    $array_plugin = array('fields'=>'','tables'=>'','groupbys'=>'');
    foreach ($list_plugin as $row) {
        $plug = get_sql_module_installed($row);
        if($plug!=false){
            $array_plugin['fields'].=$plug['field'];
            $array_plugin['tables'].=$plug['table'];
            $array_plugin['groupbys'].=$plug['groupby'];
        }

    }
    return $array_plugin;
}
function get_list_user_profile_labels(){
    return array('username'=>'Username','email'=>'Email','city'=>'City','country'=>'Country','lastlogin'=>'Last login','lastaccess'=>'Last access');
}
// get general filter array
function get_general_filter_array(){
    global $DB;
    $arr_labels = get_list_user_profile_labels();
    $general_filters_array = array();
    $general_filter_records = $DB->get_records('general_filter',array('status'=>'Y'));
    if (!empty($general_filter_records)) {
        foreach ($general_filter_records as $row) {
            $general_filters = new stdClass();
            $general_filters->id = $row->id;
            $general_filters->filtername = $row->filtername;
            $general_filters->filterdesc = $arr_labels[$row->filtername];
            if ( $row->filtername!='username' && $row->filtername!='email' ) {
                $values = $DB->get_fieldset_select('user',$row->filtername,'confirmed = ? and deleted = ? and suspended = ? and username <> ?',array(1,0,0,'guest'));
                $general_filters->value = array_unique(array_filter($values)); // remove empty and duplicated elements
            } else $general_filters->value = array();
            $general_filters_array[] = $general_filters;
        }
    }
    return $general_filters_array;
}

// Function get user profile fields query
//@Return array of query list
function get_reporting_filter_query(){
    global $DB;
    $array_query = array('fields'=>'','table'=>'','where'=>'');
    $array_query['p_dynamic'] = array();
    $filter_records = $DB->get_records('reporting_filter',array('status'=>'Y'));
    if(!empty($filter_records)){
        foreach ($filter_records as $row) {
            $fieldid = $row->user_info_field_id;
            $field_record = $DB->get_record('user_info_field',array('id'=>$fieldid));

            $alias_table_name = $field_record->shortname."info";

            //Table
            $array_query['table'] .= "LEFT JOIN mdl_user_info_data " . $alias_table_name . "\n 
      ON (u.id=" . $alias_table_name . ".userid AND " . $alias_table_name . ".fieldid=" . $fieldid . ")" . "\n";
            //Field
            $array_query['fields'] .= ", " . $alias_table_name . ".data as " . $field_record->shortname . "\n";
            //Disable: we don't need
            // $array_query['fields'] .= ", " . $alias_table_name . ".data as " . $field_record->datatype . "\n";

            //where
            // $filter_value = get_request_param($field_record->shortname);
            if ($field_record->datatype == 'datetime') {
                // $date_value = strtotime(str_replace('/', '-', $filter_value));
                // @23/07/2018 enhancement ======
                $date_from = get_request_param($field_record->shortname . "_from");
                if (!empty($date_from)) {
                    $date_from = strtotime(str_replace('/', '-', $date_from) . ' 00:00');
                    $array_query['where'] .= " AND (" . $alias_table_name . ".data >= '" . $date_from . "')\n ";
                }
                $date_to = get_request_param($field_record->shortname . "_to");
                if (!empty($date_to)) {
                    $date_to = strtotime(str_replace('/', '-', $date_to) . ' 23:59'); ;
                    $array_query['where'] .= " AND (" . $alias_table_name . ".data <= '" . $date_to . "')\n ";
                }
                // =============
                // $date_condition = get_request_param($field_record->shortname . "_condition");
                // $condition = ($date_condition == 1) ? "<=" : ">=";
                // $array_query['where'] .= " AND (" . $alias_table_name . '.data ' . $condition . $date_value . ")\n ";
            } else {
                if (!empty(get_request_param($field_record->shortname))){
                    // @25/07/2018 enhancement
                    list($q_dynamic, $p_dynamic) = $DB->get_in_or_equal(get_request_param($field_record->shortname));
                    $empty_condition='';
                    if(!empty($p_dynamic[0]) || count($p_dynamic)>1){
                        $array_query['where'] .= " AND (" . $DB->sql_compare_text($alias_table_name . '.data') . " " .$q_dynamic .$empty_condition. ")";
                        $array_query['p_dynamic'] = array_merge($array_query['p_dynamic'],$p_dynamic);
                    }
                }
            }
        }
    }
    // var_dump($array_query['where']);
    return $array_query;
}

// All names with space will be replaced by character: _  (underscore)
function get_name_standard($name){
    $str = strtolower(str_replace(" ","_",$name));
    return $str;
}
function is_user_in_hierarchy($userid){
    global $DB;
    $result = false;
    $hierarchy = is_hierarchy_installed();
    if($hierarchy){
        if($DB->record_exists('hierarchy_user',array('user_id'=>$userid))) $result=true;
    }
    return $result;
}

function can_view_all_user_reports() {
    return has_capability('block/reporting:view_all_user_reports', context_system::instance());
}

// ======================== PLUGIN ==========================
//function check is hierarchy install
function is_hierarchy_installed(){
    global $DB;
    if($DB->record_exists('config_plugins',array('plugin'=>'tool_hierarchy'))) return true;
    return false;
}
function add_childNode($current_node){
    global $DB;
    $sql_get_childnodes = "SELECT n2.`id`, n2.`name`
            FROM `mdl_hierarchy_node` AS n1 
            JOIN `mdl_hierarchy_node` AS n2
            ON n1.`id`=n2.`parent_node_id`
            WHERE n1.`id` = ?
            ORDER BY n2.`name`
            ";
    if($current_node){
        $children = $DB->get_records_sql($sql_get_childnodes, [$current_node->id]);
        foreach ($children as $child){
            $child->children = [];
            $child->value = $child->id;
            $child->label = $child->name;
            $current_node->children[] = $child;
            add_childNode($child);
        }
    }
}
//============================ Hierarchy Functions ==============
function get_hierarchy_tree($userid){
    global $DB;
    // global $pos;
    /* get hierarchy information */
    // if the user belong to site admin or report admin. It will show everyone of the tree.
    // Otherwise, it will show their brand from their position node in the tree

    $root_node_id = null;
    if (is_siteadmin($userid) || is_vendor($userid) || is_report_administrator()) {
        // Admin should see every thing start from the root
        $root_node = find_root_node();
    } else{
        $user_node = $DB->get_record('hierarchy_user', array('user_id'=>$userid));
        if($user_node){
            $root_node = $DB->get_record('hierarchy_node', array('id'=>$user_node->node_id));
        }
    }
    if(!$root_node){
        return [];
    }
    unset($root_node->level_id);
    unset($root_node->parent_node_id);
    unset($root_node->description);
    $root_node->children = [];
    $root_node->value = $root_node->id;
    $root_node->label = $root_node->name;
    add_childNode($root_node);
    return $root_node;
}
// Find the root node of the tree
function get_root_hierarchy($userid){
    global $DB;
    if(is_vendor($userid)){
        $root = find_root_node();
        // echo "<pre>".print_r($root,true)."</pre>";
        return $root->id;
    }
    $node_user_record = $DB->get_record('hierarchy_user', array('user_id'=>$userid));
    if(empty($node_user_record)) return false;
    return $node_user_record->node_id;
}

// Find the root node of the tree
function get_root_node_hierarchy($userid){
    global $DB;
    if(is_vendor($userid)){
        $root = find_root_node();
        // echo "<pre>".print_r($root,true)."</pre>";
        return $root;
    }
    $sql = "SELECT hn.* From {hierarchy_node} hn INNER JOIN {hierarchy_user} hu ON hn.id = hu.node_id WHERE hu.user_id = ?";
    $node_user = $DB->get_record_sql($sql, array($userid));
    if(empty($node_user)) return false;
    return $node_user;
}

/**
 *  sort the hierarchy nodes in an tree order
 *  for displaying the hierarchy dropdown list in GR filter page
 *
 *  @author C.C. - MA
 *  @param &$node the actually hierarchy nodes
 *  @return the sorted hierarchy nodes
 */
function sort_hierarchy_nodes($nodes) {
    global $pos;
    $stack = array();
    $list = array();

    // add the highest level nodes to stack
    $highest_level = $nodes[0]->level;

    // if($highest_level == 2) { // this is admin
    //     $stack[] = $nodes[0];
    //     $highest_level = 3;
    // }

    foreach($nodes as $node) {
        if($node->level == $highest_level)
            // if($node->level == 3)
            $stack[] = $node;
    }
    // echo "<pre>", print_r($stack, true), "</pre>";

    // $count = 0;
    // echo "<br>";

    while(!empty($stack)) {
        $cur = current($stack); //find child for the first in stack
        array_shift($stack); // shift the first off
        $list[] = $cur; // add to list
        // echo $count."|";
        // echo "<pre>", print_r($cur, true), "</pre>";

        //find children of the current node, and add to the beginning
        foreach($nodes as $key=>$val) {
            // echo "IN FOR EACH<pre>", $key, "|", print_r($val, true), "</pre>";

            if($nodes[$key]->parent_id == $cur->id) {
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
    foreach($list as $l) {
        $new = new stdClass();
        $new->id = $l->id;
        $new->parent = $l->level == $highest_level ? '#' : $l->parent_id;
        $new->state = $l->level == $highest_level ? array('selected'=>true) : array('selected'=>false); // to select the highest by default
        if(strcmp($l->name, "root") === 0){
            $new->text ="root";
        } else {
            $new->text = $l->name;
            //========================================================================
            // Disable POSITION TITLE TO DISPLAY. ONLY ENABLE WHEN ANY SPECIAL REQUIREMENT
            // if($pos) $new->text.=' - '.$l->position_title;
        }
        $jstree[] = $new;
    }


    // return $list;
    return json_encode($jstree);
}

// ============ End of Hierarchy Functions ===================
// ======================== Change color ===========
function get_default_colors(){
    $defaultcolor_pie = array('0'=>'#cbdde6','1'=>'#eeeeee','2'=>'#9dc2d5','3'=>'#bcb8b8'); // 0: completed, 1: not completed
    return $defaultcolor_pie;
}


// Get values if the module is existed in the courses
function get_module_value(&$userinfo_row,&$modules,$user_row){
    global $DB;
    $rs = $DB->get_records('modules',array());
    if(!empty($rs)){
        foreach ($rs as $row) {
            $modulename = $row->name."name";
            if(!empty($user_row[$modulename])){
                $userinfo_row[$user_row["userid"]]["module"][$user_row[$modulename]] = $user_row["completionstatus"];
                $modules[$user_row[$modulename]] = $user_row[$modulename];
            }
        }
    }
}


function get_sql_module_installed($modulename){
    global $DB;
    $arr_module = array('field'=>'','table'=>'','groupby'=>'');

    // check if the plugin is installed:
    $is_installed = $DB->get_records('modules',array('name'=>$modulename));
    if (empty($is_installed)) {
        return false;
    }else{
        $arr_module['field']=str_replace("{modulename}", $modulename,', {modulename}name.name as {modulename}name');
        $arr_module['table']=str_replace("{modulename}", $modulename," LEFT OUTER JOIN (
                SELECT Distinct mdl_{modulename}.name name, mdl_{modulename}.course cid, mdl_{modulename}.id id 
                FROM mdl_{modulename}, mdl_course 
                Where mdl_{modulename}.course = mdl_course.id
                ) as {modulename}name 
                ON ( {modulename}name.cid = c.id and cm.instance = {modulename}name.id and m.name = '{modulename}') ");
        $arr_module['groupby'] = ", ".$modulename;
    }
    return $arr_module;
}

function find_root_node(){
    global $DB;
    // root node's parent value can be either 0 or NULL
    $root = is_object($DB->get_record('hierarchy_node',array('parent_node_id'=>0))) ? $DB->get_record('hierarchy_node',array('parent_node_id'=>0)) : $DB->get_record('hierarchy_node',array('parent_node_id'=>NULL));
    return $root;
}

/**
 *   Find all users under the given node, including the users who are assigned to the nodes that are children nodes of this node
 *   @param $node_id The node that needs to be searched
 *   @return all users under this node, and child nodes of this node ... ...(loop)
 *
 *
 */
function find_nodes_userids_under_node($node_id) {
    global $DB;

    $all_users_ids = find_all_user_ids_under_node($node_id); //get all users for current node

    $children_node_ids = find_children_nodes($node_id);

    foreach($children_node_ids as $child_node_id) {
        $all_users_ids = array_merge($all_users_ids, find_all_user_ids_under_node($child_node_id));
    }

    return array_unique($all_users_ids);
}

// Find all children nodes under this node_id only. Not include current node
// @ Return array of children node
function find_children_nodes($node_id) {
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
// Find all users in this node
// @ Return array of user_id
function find_all_user_ids_under_node ($node_id) {
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

function get_request_param($value) {
    $ret = '';
    if(isset($_REQUEST[$value])) {
        $ret = $_REQUEST[$value];
    }
    return $ret;
}

// Get id from user field $name
function get_field_id($name) {
    global $DB;
    $id = "";
    $id = $DB->get_field('user_info_field','id',array('shortname'=>$name));
    if(empty($id)){
        return -1;
    }
    return $id;
}

// Get all field id in the database
function get_all_field_ids() {
    global $DB;
    $arr_fields = array();
    $rows = $DB->get_records('user_info_field',array());

    foreach ($rows as $key => $value) {
        $arr_fields [] = $value->id;
    }
    return $arr_fields;
}

function get_user_ids($pos_ids) {
    global $DBH, $POS_FIELD_ID;

    $str_pos_ids = implode(', ', $pos_ids);

    $STH = $DBH->prepare("SELECT userid FROM mdl_user_info_data WHERE fieldid = :posid_id AND data IN ( $str_pos_ids );");

    $STH->execute(array(':posid_id' => $POS_FIELD_ID));

    $data = $STH->fetchall(PDO::FETCH_COLUMN, 0);

    return $data;
}

function get_user_field_list($field_id) {
    global $DBH;
    $STH = $DBH->prepare('SELECT param1 FROM mdl_user_info_field WHERE id = :field_id;',
        array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));

    $STH->execute(array(':field_id' => $field_id));
    $data = $STH->fetchall(PDO::FETCH_COLUMN, 0);
    $data = explode("\n", $data[0]);
    //array_unshift($data, "");
    return $data;
}

function array_uppercase_values(array $input) {
    $data = array();
    foreach($input as $inputkey=>$inputvalue) {
        $data[] = trim(strtoupper($inputvalue));
    }
    return $data;
}

function get_user_field_list_match($field_id, $match_strings) {
    global $DBH;
    $STH = $DBH->prepare('SELECT param1 FROM mdl_user_info_field WHERE id = :field_id;',
        array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));

    $STH->execute(array(':field_id' => $field_id));
    $data = $STH->fetchall(PDO::FETCH_COLUMN, 0);
    $data = explode("\n", $data[0]);

    $exp_match_strings = explode(",", $match_strings);
    $exp_match_strings = array_uppercase_values($exp_match_strings);

    //print_r($exp_match_strings);

    // compare $match_strings to $data Array
    // only keep matches
    $data = array_intersect($data, $exp_match_strings);
    // add a blank element at the beginning of the list
    //array_unshift($data, "");
    return $data;
}

function get_user_data_list_match($field_id, $match_strings) {
    global $DBH;
    $STH = $DBH->prepare('SELECT DISTINCT data FROM mdl_user_info_data WHERE fieldid = :field_id AND data!=""  AND userid IN (SELECT id FROM mdl_user where deleted=\'0\') ORDER BY data;', array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));

    $STH->execute(array(':field_id' => $field_id));
    $data = $STH->fetchall(PDO::FETCH_COLUMN, 0);
    //$data = explode("\n", $data[0]);

    $exp_match_strings = explode(",", $match_strings);
    $exp_match_strings = array_uppercase_values($exp_match_strings);

    // compare $match_strings to $data Array
    // only keep matches
    $data = array_intersect($data, $exp_match_strings);
    // add a blank element at the beginning of the list
    //array_unshift($data, "");
    return $data;
}

function get_distinct_user_field_list($field_id) {
    global $DBH;
    $STH = $DBH->prepare('SELECT DISTINCT data FROM mdl_user_info_data WHERE fieldid = :field_id AND data!=""  AND userid IN (SELECT id FROM mdl_user where deleted=\'0\') ORDER BY data;',
        array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));

    $STH->execute(array(':field_id' => $field_id));
    $data = $STH->fetchall(PDO::FETCH_COLUMN, 0);
    return $data;
}

function get_user_field_value($user_id, $field_id) {
    global $DBH;
    $STH = $DBH->prepare('SELECT data FROM mdl_user_info_data WHERE fieldid = :field_id AND userid= :user_id;',
        array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));

    $STH->execute(array(':field_id' => $field_id,
        ':user_id' => $user_id));
    $data = $STH->fetchall(PDO::FETCH_COLUMN, 0);

    if(count($data) == 0) {
        $ret = '';
    } else {
        $ret = $data[0];
    }

    return $ret;
}
// @ List of users in selected nodes
// Return array of hierarchy query: field, table, where
function get_hierarchy_query($list_hierarchy_users){
    global $USER;
    $arr_query = array('fields'=>'','table'=>'','where'=>'');
    $arr_query['fields'] = ", hierarchy_info.node_name, hierarchy_info.level, hierarchy_info.node_id,hierarchy_info.nodedescription,
  hierarchy_info.leveldescription ";

    // Admin should be allowed to see everything but if they select conditions
    // if(is_siteadmin($USER->id)) $arr_query['where']="";  // should not apply anymore
    // Admin must be in the root node in order to see hierarchy report
    $arr_query['where'] = " and rc.user_id in (".$list_hierarchy_users.") ";

    $arr_query['table'] = "
  LEFT OUTER JOIN (
  SELECT mdl_hierarchy_node.id node_id,
       mdl_hierarchy_node.name node_name, mdl_hierarchy_node.description nodedescription,
       mdl_hierarchy_level.level level,mdl_hierarchy_level.description leveldescription,
       mdl_hierarchy_user.user_id user_id
  FROM   mdl_hierarchy_user,
       mdl_hierarchy_node,
       mdl_hierarchy_level
  WHERE  mdl_hierarchy_user.node_id = mdl_hierarchy_node.id
  AND    mdl_hierarchy_node.level_id = mdl_hierarchy_level.id ) as hierarchy_info
ON (u.id = hierarchy_info.user_id) 
  ";
    return $arr_query;
}

function get_stores_from_users(array $states, $statefieldid, $storefieldid) {
    global $DBH;
    $ret = array('');
    $states = array_filter($states);
    $str_states = implode(', ', $states);
    $STH = $DBH->prepare("SELECT userid FROM mdl_user_info_data WHERE fieldid = :statefieldid AND data IN ( '$str_states' );");
    $STH->execute(array(':statefieldid' => $statefieldid));
    $data = $STH->fetchall(PDO::FETCH_COLUMN, 0);
    foreach($data as $datakey=>$datavalue) {
        $STH = $DBH->prepare("SELECT data FROM mdl_user_info_data WHERE userid='" . $datavalue . "' and fieldid = :storefieldid");
        $STH->execute(array(':storefieldid' => $storefieldid));
        $ret[] = $STH->fetch(PDO::FETCH_COLUMN, 0);
    }
    return array_unique($ret);
}

function add_info_data_filter($field_id, $data) {
    global $user_joins, $wheres, $params;

    if($data && !empty($data)) {
        $join = "LEFT JOIN (
      SELECT userid, data
      FROM mdl_user_info_data
      WHERE mdl_user_info_data.fieldid = %d) as data%d
      ON u.id = data%d.userid\n";
        $user_joins .= sprintf($join, $field_id, $field_id, $field_id);

        $where = "AND data%d.data = :data%d\n";
        $wheres .= sprintf($where, $field_id, $field_id);

        $params[':data'.$field_id] = $data;
    }
}

function add_info_data_filter_match($field_id, $data, $match_strings) {
    global $user_joins, $wheres, $params;

    if($match_strings && !empty($match_strings)) {

        $new_strings = array();

        $match_strings = explode(",", $match_strings);

        foreach($match_strings as $match_string) {
            $new_strings[] = "'" . $match_string . "'";
        }

        $final_strings = implode(",", $new_strings);

        $join = "LEFT JOIN (
      SELECT userid, data
      FROM mdl_user_info_data
      WHERE mdl_user_info_data.fieldid = %d) as matchdata%d
      ON u.id = matchdata%d.userid\n";
        $user_joins .= sprintf($join, $field_id, $field_id, $field_id);

        $where = "AND matchdata%d.data IN ( %s )\n";
        $wheres .= sprintf($where, $field_id, $final_strings);

        //$params[':matchdata'.$field_id] = $data;
    }
}

function add_user_id_list($user_ids) {
    global $wheres;
    $where = " AND u.id IN (%s)\n";
    $wheres .= sprintf($where, implode(', ', $user_ids));
}

// Except admin
// all other users will see their courses and their staff's courses
function getCourse_HierarchyUser(){
    global $DB,$USER;

    if (is_siteadmin($USER->id) || is_vendor($USER->id) || is_report_administrator()) {
        $data = getCourses();
        return $data;
    }

    $currentNode = $DB->get_field('hierarchy_user','node_id',array('user_id'=>$USER->id));
    $list_user = get_all_child_users_from_nodes($currentNode);
    if($list_user=="") $list_user=$USER->id;
    else $list_user .=",".$USER->id;

    $sql_course = "select e.courseid from mdl_user_enrolments ue,mdl_enrol e where ue.enrolid=e.id and ue.userid in(".$list_user.") group by courseid";
    $rs_course = $DB->get_records_sql($sql_course,array());
    $course_arr = array();
    foreach ($rs_course as $rs_row) $course_arr[] = $rs_row->courseid;
    $courses = array();
    if(!empty($course_arr)){
        $list_courses = implode(",", $course_arr);
        $sql_course = "SELECT id,fullname,sortorder from mdl_course where visible=1 AND id in(" . $list_courses . ")";
        $rs_course = $DB->get_records_sql($sql_course,array());
        foreach ($rs_course as $row) {
            $course = new stdClass();
            $course->label = $row->fullname;
            $course->value = $row->id;
            $course->sortorder = $row->sortorder;
            $course->type = 'course';
            $courses [$row->id] = $course;
        }
    }
    return $courses;
}

function getCourses_Category_User() {
    global $DB,$USER;

    if ((is_siteadmin($USER->id)) || (is_vendor($USER->id)) || is_report_administrator()) {
        $data = getCourses_Category();
        return $data;
    }

    $currentNode = $DB->get_field('hierarchy_user','node_id',array('user_id'=>$USER->id));
    $list_user = get_all_users_from_nodes($currentNode);

    $sql_course = "select e.courseid from mdl_user_enrolments ue,mdl_enrol e where ue.enrolid=e.id and ue.userid in(".$list_user.") group by courseid";
    $rs_course = $DB->get_records_sql($sql_course,array());
    $course_arr = array();
    $data = array();
    foreach ($rs_course as $rs_row) {
        $course_arr[] = $rs_row->courseid;
    }
    if(!empty($course_arr)){
        $sql_category = "SELECT id,name,path,sortorder from mdl_course_categories where visible=1 AND id in(select category from mdl_course where visible=1 AND id in(" . implode(",", $course_arr) . ")) order by path ASC";
        //echo $sql_category;
        $rs = $DB->get_records_sql($sql_category,array());
        $category_map = [];
        foreach ($rs as $row) {
            if($row->name!=""){
                // Find all course in this category
                $nodes = explode('/', $row->path);
                for ($i = count($nodes)-1; $i>0; $i--){
                    if($i == count($nodes)-1){
                        $category = new stdClass();
                        $category->label = $row->name;
                        $category->value = $row->id;
                        $category->sortorder = $row->sortorder;
                        $category->type = 'category';
                        $category->children = [];
                        $category_map[$row->id] = $category;
                        $sql_course_cate = "SELECT * from mdl_course where visible=1 AND category=".$row->id." and id in(".implode(",", $course_arr).") order by fullname ASC";
                        $courses = $DB->get_records_sql($sql_course_cate,array());
                        foreach ($courses as $course_record) {
                            $course = new stdClass();
                            $course->label = $course_record->fullname;
                            $course->value = $course_record->id;
                            $course->sortorder = $course_record->sortorder;
                            $course->type = 'course';
                            $category->children[] = $course;
                        }
                    }else{
                        $category = $category_map[$nodes[$i]];
                        if(empty($category)){
                            $category = new stdClass();
                            $row = $DB->get_record('course_categories', ['id'=>$nodes[$i]]);
                            $category->label = $row->name;
                            $category->value = $row->id;
                            $category->type = 'category';
                            $category->sortorder = $row->sortorder;
                            $category->children = [];
                            $category_map[$row->id] = $category;
                        }
                        $child_category = $category_map[$nodes[$i+1]];
                        $category->children['cat_'.$nodes[$i+1]] = $child_category;
                    }
                    if($i == 1){
                        $data[$nodes[$i]] = $category_map[$nodes[$i]];
                    }
                }
            }
        }
    }
    return $data;
}
function getCourses_Category() {
    global $DB, $USER;
    $data = [];
    $category_map = [];
    $rs = [];
    if (is_report_administrator()) { // check is_report_administrator first in case it's a vendor as well
    } else if (is_vendor($USER->id)) {// Check if the user want to see is Vendor or not
        $arr_course_ids = get_vendor_course_ids($USER->id);
        $list = implode(",",$arr_course_ids);
        $sql = "SELECT * from mdl_course_categories 
     where visible=1 AND id in(select category from mdl_course where id in(".$list.")) order by path ASC";
        $rs = $DB->get_records_sql($sql,array());

        foreach ($rs as $row) {
            if($row->name!=""){
                // Find all course in this category
                $nodes = explode('/', $row->path);
                for ($i = count($nodes)-1; $i>0; $i--){
                    if($i == count($nodes)-1){
                        $category = new stdClass();
                        $category->label = $row->name;
                        $category->value = $row->id;
                        $category->sortorder = $row->sortorder;
                        $category->type = 'category';
                        $category->children = [];
                        $category_map[$row->id] = $category;
                        $sql_course = "select * from mdl_course where visible=1 AND category=".$row->id." and id in(".$list.") order by fullname ASC";
                        $courses = $DB->get_records_sql($sql_course,array());
                        foreach ($courses as $course_record) {
                            $course = new stdClass();
                            $course->label = $course_record->fullname;
                            $course->value = $course_record->id;
                            $course->sortorder = $course_record->sortorder;
                            $course->type = 'course';
                            $category->children[] = $course;
                        }
                    }else{
                        $category = $category_map[$nodes[$i]];
                        if(empty($category)){
                            $category = new stdClass();
                            $row = $DB->get_record('course_categories', ['id'=>$nodes[$i]]);
                            $category->label = $row->name;
                            $category->value = $row->id;
                            $category->sortorder = $row->sortorder;
                            $category->type = 'category';
                            $category->children = [];
                            $category_map[$row->id] = $category;
                        }
                        $child_category = $category_map[$nodes[$i+1]];
                        $category->children['cat_'.$nodes[$i+1]] = $child_category;
                    }
                    if($i == 1){
                        $data[$nodes[$i]] = $category_map[$nodes[$i]];
                    }
                }
            }
        }
        return $data;
    }

    // report administrator, site admin or non-hierarchy
    $rs = $DB->get_records('course_categories', array('visible' => 1), 'path ASC');
    foreach ($rs as $row) {
        if ($row->name != "") {
            // Find all course in this category
            $nodes = explode('/', $row->path);
            for ($i = count($nodes) - 1; $i > 0; $i--) {
                if ($i == count($nodes) - 1) {
                    $category = new stdClass();
                    $category->label = $row->name;
                    $category->value = $row->id;
                    $category->sortorder = $row->sortorder;
                    $category->type = 'category';
                    $category->children = [];
                    $category_map[$row->id] = $category;
                    $courses = $DB->get_records('course', array('visible' => 1, 'category' => $row->id), 'fullname ASC');
                    foreach ($courses as $course_record) {
                        $course = new stdClass();
                        $course->label = $course_record->fullname;
                        $course->value = $course_record->id;
                        $course->sortorder = $course_record->sortorder;
                        $course->type = 'course';
                        $category->children[] = $course;
                    }
                } else {
                    $category = $category_map[$nodes[$i]];
                    if (empty($category)) {
                        $category = new stdClass();
                        $row = $DB->get_record('course_categories', ['id' => $nodes[$i]]);
                        $category->label = $row->name;
                        $category->value = $row->id;
                        $category->sortorder = $row->sortorder;
                        $category->type = 'category';
                        $category->children = [];
                        $category_map[$row->id] = $category;
                    }
                    $child_category = $category_map[$nodes[$i + 1]];
                    $category->children['cat_' . $nodes[$i + 1]] = $child_category;
                }
                if ($i == 1) {
                    $data[$nodes[$i]] = $category_map[$nodes[$i]];
                }
            }
        }
    }
    return $data;
}

function add_space($num){
    $str = "";
    if($num<2) return $str;
    for($i=1;$i<$num;$i++){
        $str.=" . ";
    }
    return $str;
}

function get_course_categoryname($category_name){
    global $DB,$USER;
    // Get all category and course under this category
    $category_path = $DB->get_field('course_categories','path',array('name'=>trim($category_name)));
    if(is_vendor($USER->id)){
        $arr_course_ids = get_vendor_course_ids($USER->id);
        $list = implode(",", $arr_course_ids);
        $sql = "SELECT id from mdl_course where id in(".$list.") AND category in (select id from mdl_course_categories where path like '".$category_path."%')";
    }else {
        $sql = "SELECT id from mdl_course where category in (select id from mdl_course_categories where path like '".$category_path."%')";
    }
    $rs = $DB->get_records_sql($sql,array());
    $arr_course = array();
    foreach ($rs as $row) {
        $arr_course[] = $row->id;
    }
    return implode(",", $arr_course);
}

function getCourses() {
    global $DB,$USER;
    if (is_report_administrator()) {
        $sql = "SELECT id,fullname,sortorder FROM mdl_course where visible=1 AND category <>0 and (format='scorm' OR format='topics' OR format = 'singleactivity') ORDER BY fullname";
    } else if (is_vendor($USER->id)) {
        $arr_course_ids = get_vendor_course_ids($USER->id);
        $list = implode(",", $arr_course_ids);
        $sql = "SELECT id,fullname,sortorder FROM mdl_course where visible=1 AND category <>0 and (format='scorm' OR format='topics' OR format = 'singleactivity') AND id in(" . $list . ") ORDER BY fullname";
    } else { // siteadmin or non-hierarchy
        $sql = "SELECT id,fullname,sortorder FROM mdl_course where visible=1 AND category <>0 and (format='scorm' OR format='topics' OR format = 'singleactivity') ORDER BY fullname";
    }
    $rs = $DB->get_records_sql($sql);
    $array = array();
    foreach ($rs as $row) {
        $course = new stdClass();
        $course->label = $row->fullname;
        $course->value = $row->id;
        $course->sortorder = $row->sortorder;
        $course->type = 'course';
        $array[$row->id] = $course;
    }
    return $array;
}

function getCohorts() {
    global $DBH;
    $STH = $DBH->prepare("SELECT name FROM mdl_cohort ORDER BY name");
    $STH->execute();

    $data = $STH->fetchall(PDO::FETCH_COLUMN, 0);
    return $data;
}

function getReportingLevel($field_id, $user_id) {
    global $DBH;
    $STH = $DBH->prepare("SELECT data FROM mdl_user_info_data where fieldid='" . $field_id . "' and userid='" . $user_id . "'");
    $STH->execute();

    $data = $STH->fetch();
    return $data[0];
}

function filter_date($array, $logic) {
    $results = array();
    $results = array_filter($array, $logic);
    return $results;
}

function custom_array_filter($array, $key, $value) {
    $results = array();
    if (is_array($array)) {
        if (isset($array[$key]) && $array[$key] == $value)
            $results[] = $array;

        foreach ($array as $subarray)
            $results = array_merge($results, custom_array_filter($subarray, $key, $value));
    }
    return $results;
}

function dual_custom_array_filter($array, $key, $value1, $value2) {
    $results = array();
    if (is_array($array)) {
        if (isset($array[$key]) && ($array[$key] == $value1 || $array[$key] == $value2))
            $results[] = $array;

        foreach ($array as $subarray) {
            $results = array_merge($results, dual_custom_array_filter($subarray, $key, $value1));
            $results = array_merge($results, dual_custom_array_filter($subarray, $key, $value2));
        }
    }
    return $results;
}

function getFirstnameLastname($fullname) {
    $firstname = "";
    $lastname = "";
    $parts = explode(" ", $fullname);
    $cnt = 1;
    foreach($parts as $pkey=>$pvalue) {
        if($cnt<count($parts))
            $firstname .= $pvalue." ";
        else $lastname .= $pvalue;
        $cnt++;
    }
    $result = array(trim($firstname), trim($lastname));
    return $result;
}

function  get_gradefilterstatus(){
    if (isset($_POST['gradecheckbox'])){
        $result = "y";
    }else{
        $result ="n";
    }
    return $result;
}
if (!function_exists('objectToArray')){
    function objectToArray( $result ) {
        if( !is_object( $result ) && !is_array( $result ) )
        {
            return $result;
        }
        if( is_object( $result ) )
        {
            $result = get_object_vars( $result );
        }
        return array_map( 'objectToArray', $result );
    }
}
function getModulename($rowmoduletype,$rowcourseid,$rowcoursemoduleid,$rowinstance){
    global $USER, $DB,$CFG;
    $DBH = new PDO("mysql:host=$CFG->dbhost;dbname=$CFG->dbname", $CFG->dbuser, $CFG->dbpass);
    $prefix = "mdl_";
    $modulesinput = get_request_param("coursemodules");
    $moduletable = "mdl_".$rowmoduletype;

    $sql = "Select ".$moduletable.".name name
            From ".$moduletable.
        " Where ".$moduletable.".id = ?";
    $result = $DB->get_records_sql($sql,array($rowinstance));
    $result = objectToArray($result);

    foreach($result as $value){
        $result = $value;
    }

    return $result;

}

function getModulename2($modulesinput_instanceid,$rowmoduletype,$rowcourseid,$rowcoursemoduleid,$rowinstance){
    global $USER, $DB,$CFG;
    $DBH = new PDO("mysql:host=$CFG->dbhost;dbname=$CFG->dbname", $CFG->dbuser, $CFG->dbpass);
    $prefix = "mdl_";
    $modulesinput = get_request_param("coursemodules");
    $moduletable = "mdl_".$rowmoduletype;
    if($modulesinput !=""){
        $sql = "Select ".$moduletable.".name name
            From ".$moduletable.
            " Where ".$moduletable.".id = ?";
        $result = $DB->get_records_sql($sql,array($modulesinput_instanceid));
        $result = objectToArray($result);
    }else{
        $sql = "Select ".$moduletable.".name name
            From ".$moduletable.
            " Where ".$moduletable.".id = ?";
        $result = $DB->get_records_sql($sql,array($rowinstance));
        $result = objectToArray($result);
    }

    foreach($result as $value){
        $result = $value;
    }

    return $result;

}

function getTotalNoCourse($courseselected){
    global $USER, $DB,$CFG;
    $DBH = new PDO("mysql:host=$CFG->dbhost;dbname=$CFG->dbname", $CFG->dbuser, $CFG->dbpass);
    $sql = "Select count(id) as totalnumber from mdl_course";
    $result = $DB->get_records_sql($sql,array());
    $result = objectToArray($result);
    foreach($result as $value){
        $result = $value;
    }
    if($courseselected !=''){
        $result = "1";
    }
    return $result;
}
function get_no_student_enrolled($currentcourseid){
    global $USER, $DB,$CFG;
    $DBH = new PDO("mysql:host=$CFG->dbhost;dbname=$CFG->dbname", $CFG->dbuser, $CFG->dbpass);
    if($currentcourseid !=''){

        $sql = "Select ue.id as userenrollid,e.id,e.courseid,e.enrol,ue.enrolid,ue.userid,min(ue.timecreated) from mdl_user_enrolments ue 
            Left Join mdl_enrol e On e.id = ue.enrolid 
            LEFT join mdl_course c On c.id = e.courseid
            where c.id = ? 
            Group BY courseid,userid
            "
        ;
        $result = $DB->get_records_sql($sql,array($currentcourseid));
    }else{
        $sql = "Select ue.id as userenrollid,e.id,e.courseid,e.enrol,ue.enrolid,ue.userid,min(ue.timecreated) from mdl_user_enrolments ue 
            Left Join mdl_enrol e On e.id = ue.enrolid 
            LEFT join mdl_course c On c.id = e.courseid
            Group BY courseid,userid
            ";
        $result = $DB->get_records_sql($sql,array());
    }
    $result = objectToArray($result);

    return $result;

}
// This function only apply when user select multiple nodes. Otherwise, it will return null.
// The report will give more graphs
//@ return array of node_id refer to list of users under that node
// arr[2] = userid1,userid2,userid3
function get_all_users_selectednodes($selectednodes){
    global $DB;
    $results = array();
    $arr_nodes = explode(",", $selectednodes);
    if(count($arr_nodes)>1){
        foreach ($arr_nodes as $nodeid) {
            $rs = $DB->get_records('hierarchy_user',array('node_id'=>$nodeid));
            $results[$nodeid] = get_all_users_from_node($nodeid);
        }
    }
    return $results;
}
// Check that if the $parent is the real parent of $child in the hierarchy. Otherwise, access is denied

function is_parent_node($parent,$child){
    global $DB;
    $return = false;
    $root = find_root_node();
    $root_node_id = $root->id;
    $node_id = $child; // Start from child
    if($parent==$child) return true;
    $i=0;
    while($node_id!=$root_node_id){
        $node_id = $DB->get_field('hierarchy_node','parent_node_id',array('id'=>$node_id));
        if($node_id==$parent){
            $return = true;
            break;
        }
        $i++;
        //   if($i==5) break;
    }
    return $return;
}
// Find all users
// Case 1: if current user in the same node with selected node, Only this user and all children under this node will be included in the Queue.
// Case 2: if current user is not the same node with selected node, All user from this node and children node will be included
// @Array of selected nodes
//@ Return a list of users.
function get_all_users_from_nodes($selectednodes){
    global $DB,$USER;
    if($selectednodes==null){
        throw new Exception('The user must be in the hierarchy');
    };
    $arrnodes = explode(',', $selectednodes);
    $arr_users = array();
    $is_admin =is_siteadmin($USER->id);
    $view_report_allowed = view_report_allowed();
    // Check if the user belong to selected node list, then the current user has to be added into the list
    $currentUserNodeId = $DB->get_field('hierarchy_user','node_id',array('user_id'=>$USER->id));
    if(!$is_admin && !$view_report_allowed){
        foreach ($arrnodes as $child) {
            $test = is_parent_node($currentUserNodeId,$child);
            if($test==false) {
                throw new Exception(get_string('notallowtoaccess','block_training_report'));
            }
        }
    }
    if(!empty($currentUserNodeId)&&(in_array($currentUserNodeId,$arrnodes))&&(!$is_admin))
        //if user selected their postion node in the hierarchy. List of users will include their user_id
        $arr_users [] = $USER->id;
    else{
        // Add all users in selected nodes into the list
        $sql_user = "SELECT user_id from mdl_hierarchy_user where node_id in($selectednodes) group by user_id";
        $rs_user = $DB->get_records_sql($sql_user);
        if($rs_user){
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
        $all_children_nodes = find_children_nodes($node_id);

        if (!empty($all_children_nodes)) {
            foreach($all_children_nodes as $child_node_id){
                if(!in_array($child_node_id, $nodes_queue)) $nodes_queue [] = $child_node_id;
            }
        }
        // Include the current nodes as well => This will other user can see each others in the same node
        // $nodes_queue [] = $node_id; // => Should be disabled
    }
    // Get all users in these nodes: nodes_queue.
    $all_users = array();
    if(!empty($nodes_queue)){
        $list = implode(',',$nodes_queue);
        $sql = "select user_id from mdl_hierarchy_user where node_id in($list)";
        $all_users = $DB->get_records_sql($sql,array());
    }
    foreach ($all_users as $r) {
        $arr_users [] = $r->user_id;
    }

    $list = implode(',',$arr_users);
    return $list;
}

// Find all child users under this node only. Not include users in the current node
// @Array of selected nodes
//@ Return a list of users.
function get_all_child_users_from_nodes($selectednodes){
    global $DB;
    if($selectednodes==null) return false;
    $arrnodes = explode(',', $selectednodes);
    $nodes_queue = array();
    $list="";
    foreach ($arrnodes as $node_id) {
        // Find all children nodes of current node
        // if(!in_array($node_id, $nodes_queue)) $nodes_queue [] = $node_id;
        $all_children_nodes = find_children_nodes($node_id);
        foreach($all_children_nodes as $child_node_id){
            if(!in_array($child_node_id, $nodes_queue)) $nodes_queue [] = $child_node_id;
        }
    }
    // Get all users in these nodes: nodes_queue.
    if(!empty($nodes_queue)){
        $list = implode(',',$nodes_queue);
        $sql = "select user_id from mdl_hierarchy_user where node_id in($list)";
        $all_users = $DB->get_records_sql($sql,array());
        $arr_users = array();
        foreach ($all_users as $r) {
            $arr_users [] = $r->user_id;
        }
        $list = implode(',',$arr_users);
    }
    return $list;
}
//@ Get all user FROM SELECTED NODE AND CHILDREN NODES
function get_all_users_from_node($selectednode){
    global $DB;
    if($selectednode==null) return false;
    $nodes_queue = array($selectednode);

    $all_children_nodes = find_children_nodes($selectednode);
    if(!empty($all_children_nodes)){
        foreach($all_children_nodes as $child_node_id){
            if(!in_array($child_node_id, $nodes_queue)) $nodes_queue [] = $child_node_id;
        }
    }
    // Get all users in these nodes: nodes_queue.
    $list = implode(',',$nodes_queue);
    $sql = "select user_id from mdl_hierarchy_user where node_id in($list)";
    $all_users = $DB->get_records_sql($sql,array());
    $arr_users = array();
    foreach ($all_users as $r) {
        $arr_users [] = $r->user_id;
    }
    $list = implode(',',$arr_users);
    return $list;
}
// Get the label for the selected nodes
function get_selectednodes_name($arr_selectednodes){
    global $DB;
    $arr = array();
    if(!empty($arr_selectednodes)){
        foreach ($arr_selectednodes as $nodeid => $value) {
            // Display the description in the label
            $arr[$nodeid]['label'] = $DB->get_field('hierarchy_node','name',array('id'=>$nodeid));
            $arr[$nodeid]['completed'] = 0;
            $arr[$nodeid]['num_users'] = 0;
        }
    }
    return $arr;
}

function shortern_course_name2($coursename){
    $num_limit = 22; // default Limited number of charactor showing in course name
    $str = $coursename;
    if(strlen($coursename)>$num_limit){
        $str = substr($coursename,0,$num_limit)."..";
    }
    return $str;
}

function get_reporting_compile_folder(){
    global $CFG;
    $mode = 0777;  // permission to write file in this folder
    $path = $CFG->dataroot."/reporting";
    if(!file_exists($path)) mkdir($path,$mode,true);
    $path.="/compile";
    if(!file_exists($path)) mkdir($path,$mode,true);
    return $path;
}

function get_time_stamp_for_condition($condition, $date) {
    $date_time = DateTime::createFromFormat('d/m/Y', $date);

    if ($condition === 'before') {
        $date_time->setTime(0, 0, 0);
    } elseif ($condition === 'after') {
        $date_time->setTime(23, 59, 59);
    }

    return $date_time->format('U');
}

function get_compliance_reports(
    $courseids = null,
    $enrolled_filter = null,
    $completion_filter = null,
    $selectednodes = null,
    $suspended_user_filter = null,
    $user_profile_filters = null,
    $include_profiles = null
) {
    global $CFG, $DB;

    require_once("$CFG->libdir/completionlib.php");

    $operators = array(
        'before' => '<',
        'after' => '>'
    );
    // Get courses and users enrolled in the course based on filters
    // Sometimes users can be enrolled multiple times with different enrolment method
    // so we want to only get the minimum time
    $user_name_fields_sql = get_all_user_name_fields(true, 'u') ;
    $user_profile_fields = array();
    $course_enrolments_joins = '';
    $course_enrolments_params = array();
    $course_enrolments_extra_fields = array();

    if (!empty($selectednodes)) {
        $hierarchy_nodes = $selectednodes;

        foreach ($selectednodes as $node) {
            $hierarchy_nodes = array_merge($hierarchy_nodes, find_children_nodes($node));
        }

        list($nodes_query, $nodes_params) = $DB->get_in_or_equal($hierarchy_nodes);
        $course_enrolments_joins .= " JOIN {hierarchy_user} hu
      ON hu.user_id = u.id
      AND hu.node_id $nodes_query
      JOIN {hierarchy_node} hn
      ON hn.id = hu.node_id";
        $course_enrolments_params = array_merge($course_enrolments_params, $nodes_params);
        $course_enrolments_extra_fields[] = 'hu.node_id';
        $course_enrolments_extra_fields[] = 'hn.name as node_name';
    }

    $count = 0;
    // Required profile fields for filtering
    if (!empty($user_profile_filters)) {
        foreach ($user_profile_filters as $profile_filter) {
            $course_enrolments_joins .= " JOIN {user_info_data} uid$count
        ON uid$count.userid = u.id
        AND uid$count.fieldid = $profile_filter->id";
            if ($profile_filter->type === 'datetime') {
                $course_enrolments_joins .= " AND uid$count.data {$operators[$profile_filter->condition]} ?";
                $course_enrolments_params[] =
                    get_time_stamp_for_condition($profile_filter->condition, $profile_filter->value);
            } else {
                $course_enrolments_joins .= " AND uid$count.data = ?";
                $course_enrolments_params[] = $profile_filter->value;
            }

            $user_profile_fields[$count] = strtolower($profile_filter->shortname);

            $count++;
        }
    }

    // Optional profile field for displaying
    if (!empty($include_profiles)) {
        foreach ($include_profiles as $key => $include_profile) {
            if (!in_array($include_profile, $user_profile_fields)) {
                $course_enrolments_joins .= " LEFT JOIN {user_info_data} uid$count
          ON uid$count.userid = u.id
          AND uid$count.fieldid = ?";
                $course_enrolments_params[] = $include_profile->id;

                $user_profile_fields[$count] = strtolower($include_profile->shortname);
            } else {
                unset($include_profiles[$key]);
            }
            $count++;
        }
    }

    // Prefix profile fields with table alias and convert to sql
    $user_profile_fields_sql = implode(',', array_map(function($profile, $index) {
        return "uid$index.data as profile_field_" . $profile;
    }, $user_profile_fields, array_keys($user_profile_fields)));

    $course_enrolments_extra_fields = $user_name_fields_sql . ',' . implode(',', $course_enrolments_extra_fields);
    if (!empty($user_profile_fields_sql)) {
        $course_enrolments_extra_fields .= ',' . $user_profile_fields_sql;
    }

    $course_enrolments_query = "SELECT c.id as courseid, u.id as userid, c.fullname as coursename,
      MIN(ue.timecreated) as enrolled_date, $course_enrolments_extra_fields
    FROM {user} u
    JOIN {user_enrolments} ue
    ON ue.userid = u.id
    JOIN {enrol} e
    ON e.id = ue.enrolid
    JOIN {course} c
    ON c.id = e.courseid
    $course_enrolments_joins
    WHERE u.username <> 'guest'
    AND u.deleted <> 1
    AND e.enrol IN ('manual', 'cohort')";
    if (!empty($courseids)) {
        list($courseids_query, $courseids_params) = $DB->get_in_or_equal($courseids);
        $course_enrolments_query .= " AND c.id $courseids_query";
        $course_enrolments_params = array_merge($course_enrolments_params, $courseids_params);
    }
    if ($suspended_user_filter == 'only_suspended') {
        $course_enrolments_query .= " AND u.suspended = 1";
    } elseif ($suspended_user_filter == 'not_include_suspended') {
        $course_enrolments_query .= " AND u.suspended <> 1";
    }
    $course_enrolments_query .= " GROUP BY c.id, u.id";
    if (!empty($enrolled_filter) && !empty($operators[$enrolled_filter->condition])) {
        $course_enrolments_query .= " HAVING enrolled_date {$operators[$enrolled_filter->condition]} ?";
        $course_enrolments_params[] = $enrolled_filter->value;
    }

    // print_object($course_enrolments_params);
    // print_object($course_enrolments_query);die();

    $params = array();
    $user_name_fields_sql = get_all_user_name_fields(true, 'ce');
    $user_profile_fields_sql = implode(',', array_map(function($profile, $index) {
        return "ce.profile_field_" . $profile;
    }, $user_profile_fields, array_keys($user_profile_fields)));
    $report_extra_fields = array();

    if (!empty($selectednodes)) {
        $report_extra_fields[] = 'ce.node_id';
        $report_extra_fields[] = 'ce.node_name';
    }

    $report_extra_fields = $user_name_fields_sql . ',' . implode(',', $report_extra_fields);
    if (!empty($user_profile_fields_sql)) {
        $report_extra_fields .= ',' . $user_profile_fields_sql;
    }
    $query = "SELECT ce.courseid, ce.userid, COUNT(cm.id) as num_activities, COUNT(cmc.id) as num_completed_activities,
      ce.enrolled_date, MAX(cmc.timemodified) as completion_date, ce.coursename,
      $report_extra_fields
    FROM {course_modules} cm
    JOIN ($course_enrolments_query) ce
    ON ce.courseid = cm.course
    LEFT JOIN {course_modules_completion} cmc
    ON cmc.coursemoduleid = cm.id
    AND ce.userid = cmc.userid
    WHERE cm.deletioninprogress <> 1
    AND cm.completion IN (" . COMPLETION_TRACKING_MANUAL . ", " . COMPLETION_TRACKING_AUTOMATIC . ")
    GROUP BY ce.courseid, ce.userid";
    $params = array_merge($params, $course_enrolments_params);

    // print_object($params);
    // print_object($query);die();

    return $DB->get_recordset_sql($query, $params);
}


//funtion to get the course completed and not completed users, courses and status
// function get_course_completed_details($completed=true, $completion_date='' ){
//   global $DB;
//   //echo $completion_date;
//   //return array
//   $user_course_arr = array();

//   $newSQL = "SELECT user_id, MAX(activity_completiondate) as datecompleted, course_id, COUNT(*) as total, COUNT(IF(activity_completionstatus = 1, 1, NULL)) as progress FROM `mdl_automated_report_tmp` GROUP BY user_id, course_id";

//   if($completed){
//     $newSQL .= " HAVING total = progress";
//   } else {
//     $newSQL .= " HAVING total <> progress";
//   }
//   //echo $newSQL;
//   $newrs = $DB->get_recordset_sql($newSQL);

//   if($newrs->valid()){
//     foreach($newrs as $result):
//       if($completion_date){
//         if( date('y-m-d',$result->datecompleted) == date('y-m-d', strtotime(str_replace('/', '-', $completion_date))) ){
//           $user_course_arr['users'][]   = $result->user_id;
//           $user_course_arr['courses'][] = $result->course_id;
//         } else {
//           break;
//         }
//       } else {
//         $user_course_arr['users'][]   = $result->user_id;
//         $user_course_arr['courses'][] = $result->course_id;
//       }
//     endforeach;
//   }
//   $newrs->close();

//   return $user_course_arr;
// }

//funtion to get the course completed and not completed users, courses and status
function get_course_completed_details($completed=true, $completion_date=array() ){
    global $DB;

    $user_course_arr = array();

    $newSQL = "SELECT user_id, MAX(activity_completiondate) as datecompleted, course_id, COUNT(*) as total, COUNT(IF(activity_completionstatus = 1 or activity_completionstatus = 2, 1, NULL)) as progress FROM `mdl_automated_report_tmp` GROUP BY user_id, course_id";

    if($completed){
        $newSQL .= " HAVING total = progress";
        if($completion_date['date_from'] AND $completion_date['date_to']){
            $datefrom = strtotime(str_replace('/','-',$completion_date['date_from']));
            $dateto = strtotime(str_replace('/','-',$completion_date['date_to'])) + (24*60*60);
            $newSQL .= " AND (datecompleted BETWEEN {$datefrom} AND {$dateto})";
        } else if ($completion_date['date_from'] AND !$completion_date['date_to']){
            $datefrom = strtotime(str_replace('/','-',$completion_date['date_from']));
            $newSQL .= " AND (datecompleted >= {$datefrom})";
        } else if ( !$completion_date['date_from'] AND $completion_date['date_to'] ){
            $dateto = strtotime(str_replace('/','-',$completion_date['date_to'])) + (24*60*60);
            $newSQL .= " AND (datecompleted <= {$dateto})";
        }
    } else {
        $newSQL .= " HAVING total <> progress";
    }
    //echo $newSQL;
    $newrs = $DB->get_recordset_sql($newSQL);

    if($newrs->valid()){
        foreach($newrs as $result):
            $user_course_arr['users'][]   = $result->user_id;
            $user_course_arr['courses'][] = $result->course_id;
        endforeach;
    }
    $newrs->close();

    return $user_course_arr;
}


function get_ext_qual_filter_completion_dates($completeddate_from, $completeddate_to, $completion_status){

    $newSQL = '';

    if($completion_status){
        if($completeddate_from AND $completeddate_to){
            $datefrom = strtotime(str_replace('/','-',$completeddate_from));
            $dateto = strtotime(str_replace('/','-',$completeddate_to)) + (24*60*60);
            $newSQL .= "(qu.completiondate BETWEEN {$datefrom} AND {$dateto})";
        } else if ($completeddate_from AND !$completeddate_to){
            $datefrom = strtotime(str_replace('/','-',$completeddate_from));
            $newSQL .= "(qu.completiondate >= {$datefrom})";
        } else if ( !$completeddate_from AND $completeddate_to ){
            $dateto = strtotime(str_replace('/','-',$completeddate_to)) + (24*60*60);
            $newSQL .= "(qu.completiondate <= {$dateto})";
        }
    } else {
        $newSQL .= "(qu.completiondate = '')";
    }

    return $newSQL;
}

function get_ext_qual_filter_due_dates($expireddate_from, $expireddate_to){

    $newSQL = '';

    if($expireddate_from AND $expireddate_to){
        $datefrom = strtotime(str_replace('/','-',$expireddate_from));
        $dateto = strtotime(str_replace('/','-',$expireddate_to)) + (24*60*60);
        $newSQL .= "(qu.expireddate BETWEEN {$datefrom} AND {$dateto})";
    } else if ($expireddate_from AND !$expireddate_to){
        $datefrom = strtotime(str_replace('/','-',$expireddate_from));
        $newSQL .= "(qu.expireddate >= {$datefrom})";
    } else if ( !$expireddate_from AND $expireddate_to ){
        $dateto = strtotime(str_replace('/','-',$expireddate_to)) + (24*60*60);
        $newSQL .= "(qu.expireddate <= {$dateto})";
    }

    return $newSQL;
}

//function to return the userids based on manager name
function get_users_for_manager($mgrname = ''){
    global $DB;
    //echo $mgrname;
    $query = "SELECT uid.userid FROM mdl_user_info_data as uid
            INNER JOIN mdl_user_info_field as uif ON uif.id = uid.fieldid
            WHERE uif.name = 'Worker Manager' AND uid.data LIKE ?
          ";
    $newrs = $DB->get_records_sql($query, array('%'.$mgrname.'%'));
    return array_keys($newrs);
}

function get_user_profile_field_data($uid, $field_shortname){
    global $DB;

    static $fields_cache;
    if (!isset($fields_cache)) {
        $fields_cache = [];
    }
    if (!isset($fields_cache[$uid])) {
        $user_fields = profile_get_user_fields_with_data($uid);
        $user_fields_dict = [];
        foreach ($user_fields as $user_field) {
            $user_fields_dict[$user_field->field->shortname] = $user_field->data;
        }

        $fields_cache[$uid] = $user_fields_dict;
    }

    return isset($fields_cache[$uid][$field_shortname]) ? $fields_cache[$uid][$field_shortname] : '';
}

function get_user_completion_info($uid, $cid) {
    global $CFG, $DB;

    require_once("$CFG->libdir/completionlib.php");

    static $user_course_cache;
    if (!isset($user_course_cache)) {
        $user_course_cache = [];
    }

    if (!isset($user_course_cache[$cid])) {
        // Load for whole course
        $course_completions = $DB->get_records_sql("SELECT id, user_id, activity_completiondate, activity_completionstatus
      FROM `mdl_automated_report_tmp`
      WHERE course_id = ?", [$cid]);

        $course_user_completion_info = [];
        foreach ($course_completions as $course_completion) {
            if (!isset($course_user_completion_info[$course_completion->user_id])) {
                $course_user_completion_info[$course_completion->user_id] = [
                    'total' => 0,
                    'completed' => 0,
                    'max_completion_date' => 0,
                ];
            }

            $course_user_completion_info[$course_completion->user_id]['total']++;

            if (
                $course_completion->activity_completionstatus == COMPLETION_COMPLETE
                || $course_completion->activity_completionstatus == COMPLETION_COMPLETE_PASS
            ) {
                $course_user_completion_info[$course_completion->user_id]['completed']++;

                if ($course_user_completion_info[$course_completion->user_id]['max_completion_date'] < $course_completion->activity_completiondate) {
                    $course_user_completion_info[$course_completion->user_id]['max_completion_date'] = $course_completion->activity_completiondate;
                }
            }
        }

        $user_course_cache[$cid] = $course_user_completion_info;
    }

    if (!isset($user_course_cache[$cid][$uid])) {
        return [
            'total' => 0,
            'completed' => 0,
            'max_completion_date' => 0,
        ];
    }

    return $user_course_cache[$cid][$uid];

}

//function to get the course detail information required for course completed report
//course category
//course program
//course name
//course activities
function report_get_course_detail($cid, $uid){
    global $DB;
    $return = array();

    $course = get_course($cid);

    //get category and program detail
    $cat = get_course_cat_name($course->category);

    //get course activity details
    $activies_detail = $DB->get_records('automated_report_tmp', array('user_id'=>$uid, 'course_id'=>$cid), 'id, activity_type, activity_name');
    $activity = array();
    foreach($activies_detail as $act){
        $activity[] = array('type' => $act->activity_type, 'name' => $act->activity_name);
    }

    // print_object($activity);
    $return['course_name']     = $course->fullname;
    $return['course_cat']      = $cat['cat'];
    $return['course_prog']     = $cat['prog'];
    $return['course_act']      = $activity;
    return $return;
}

//internal function used to get the course category name
//it will return the category name as program name if category depth level = 1
//otherwise return the category name based on the depth level.
function get_course_cat_name($catid){
    global $DB;

    $return = array();

    $cat = $DB->get_record('course_categories', array('id' => $catid));
    $return['prog'] = $cat->name;
    if($cat->depth != 1){
        $parent_cats = explode('/', substr($cat->path, 1));
        array_pop($parent_cats);
        $parent_cats = implode(',', $parent_cats);

        $parent_cat_names = $DB->get_records_sql_menu("SELECT id, name FROM {course_categories} WHERE id IN ({$parent_cats}) ");
        $return['cat'] = implode(',', array_values( $parent_cat_names) );
    } else {
        $return['cat'] = $cat->name;
    }

    return $return;
}

//function to get the user enrol date for the course
function get_user_course_enrol_date($uid, $cid){
    global $DB;

    static $course_cache;

    if (!isset($course_cache)) {
        $course_cache = [];
    }

    if (!isset($course_cache[$cid])) {
        $sql = "SELECT DISTINCT user_id, FROM_UNIXTIME(course_enrolmentdate, '%d/%m/%Y') as date_assign
      FROM `mdl_automated_report_tmp`
      WHERE course_id = ?
      GROUP BY user_id, course_id";
        $user_dates = $DB->get_recordset_sql($sql, array($uid, $cid));

        $user_dates_grouped_by_user_id = [];
        foreach ($user_dates as $user_date) {
            $user_dates_grouped_by_user_id[$user_date->userid] = date('d/m/Y', $user_date->date_assign);
        }

        $course_cache[$cid] = $user_dates_grouped_by_user_id;
    }

    return !empty($course_cache[$cid][$uid]) ? $course_cache[$cid][$uid] : '';
    //return date('d/m/Y', $date)
}

//function to get leadership courses based on tag name = leadership
//required for leadership course completed by gender reports
function get_courses_by_tag($tag = ''){
    global $DB;

    if($tag){
        $tag = strtolower($tag);
        $sql = "SELECT ti.itemid FROM {tag_instance} as ti INNER JOIN {tag} as t ON ti.tagid = t.id WHERE t.name = ? AND ti.itemtype = 'course'";
        $result = $DB->get_records_sql($sql, array($tag));

        if(!empty($result)){
            //echo '<pre>'.print_r($result,1).'</pre>';
            return array_keys($result);
        }
        return array(0);
    }
    return array(0);
}

//Functions to remove facilitators from reporting calculation: they do not need to complete the courses
function get_roles_show_reports(){
    global $DB;
    $roles_show = $DB->get_fieldset_sql("SELECT roleid FROM mdl_role_capabilities WHERE capability=?",array('moodle/course:isincompletionreports'));
    return $roles_show;
}
function get_roles_not_show_reports(){
    global $DB;
    //course:isincompletionreports
    $roles = array();
    $rs = $DB->get_records('role',array());
    $roles_show = get_roles_show_reports();
    if(!empty($rs)){
        foreach ($rs as $row) {
            if(in_array($row->id,$roles_show)) continue;
            $roles [] = $row->id;
        }
    }
    return $roles;
}

function report_get_individual_exclude_courses($userid){
    global $DB;
    $courses = $DB->get_fieldset_sql("SELECT courseid FROM mdl_report_noshow_users WHERE userid=? group by courseid",array($userid));
    return $courses;
}

function report_get_users_courses_exclude(){
    global $DB;
    $users_courses = $DB->get_records_sql("SELECT id,userid,courseid FROM mdl_report_noshow_users group by userid,courseid",array());
    $table = array();
    if(!empty($users_courses)){
        foreach ($users_courses as $row) {
            $table[$row->userid][$row->courseid] = 1;
        }
    }
    return $table;
}

function update_report_noshow_users(){
    global $DB;
    $noshow_roleids = get_roles_not_show_reports();
    $show_roleids = get_roles_show_reports();

    $noshow_roleids_list = implode(',', $noshow_roleids);
    $show_roleids_list = implode(',', $show_roleids);
    $table = array();
    //Get all users who have been assigned to this roles from the coures.
    if($noshow_roleids_list && $noshow_roleids_list!=""){
        $sql = 'SELECT ra.id,ra.userid,ra.roleid,cx.instanceid as courseid from mdl_role_assignments as ra 
    INNER JOIN mdl_context cx on cx.id=ra.contextid 
    WHERE contextlevel = ? AND ra.roleid in('.$noshow_roleids_list.') group by ra.userid,cx.instanceid';
        $rs = $DB->get_records_sql($sql,array(CONTEXT_COURSE));
        if(!empty($rs)){
            foreach ($rs as $row) {
                //Insert userid,roleid,courseid into the reporting table. Then, we can minus the result from this tables.
                //Check if user has another role to show reports. Then, remove them from the table
                if($DB->record_exists_sql('SELECT ra.* from mdl_role_assignments as ra 
          INNER JOIN mdl_context cx on cx.id=ra.contextid 
          WHERE contextlevel = ? AND ra.roleid in('.$show_roleids_list.') and cx.instanceid=? and ra.userid=? group by ra.userid,cx.instanceid',array(CONTEXT_COURSE,$row->courseid,$row->userid))){
                    //Remove user
                    $DB->delete_records('report_noshow_users',array('userid'=>$row->userid,'courseid'=>$row->courseid));
                    continue;
                }
                $table [$row->userid][$row->courseid] = 1;
                if(!$DB->record_exists('report_noshow_users',array('userid'=>$row->userid,'courseid'=>$row->courseid,'roleid'=>$row->roleid))){
                    $record = (object) array('userid'=>$row->userid,'courseid'=>$row->courseid,'roleid'=>$row->roleid);
                    $DB->insert_record('report_noshow_users',$record);
                }
            }
        }
    }
    $rs_facilitators = $DB->get_records('report_noshow_users');
    if(!empty($rs_facilitators)){
        foreach ($rs_facilitators as $row) {
            if(!isset($table[$row->userid]) || !isset($table[$row->userid][$row->courseid])) $DB->delete_records('report_noshow_users',array('id'=>$row->id));
        }
    }
}
//Return function true if
function duedate_condition_between_continue($duedate,$conditions){
    $return = false;
    $duedate = intval($duedate);
    if($conditions['date_from']!=0 && $duedate<$conditions['date_from']) $return = true;
    if($conditions['date_to']!=0 && $duedate>$conditions['date_to']) $return = true;
    return $return;
}

/**
 * Return reportMap
 * Explanation
 * key = report identifier
 * val = report filename
 *
 * The function should prepend /blocks/reporting/report, unless /blocks/... exists at the start
 * see exception below as an example
 */
function getReportMap() {
    return array(
        // 'general_reports'                      => 'general', //hide individual report #16424
        'individual_reports'                   => 'individual',
        'activity_reports'                     => 'activity',
        'course_overview_reports'              => 'courseoverview',
        'usertranscript'                       => 'user_transcript',
        'alllearningstatusreport'              => 'report_all_learning_status',

        // exception, not from /blocks/reporting/report
        'myexternaltraining'                   => '/blocks/external_qualification/my_qualification',

        'learningbundle_progress_reports'      => 'learningbundle_progress',
        'compliance_modules_status_reports'    => 'compliance_modules_status',
        'allstaffreport'                       => 'users',
        'learningactivitycataloguereport'      => 'learningcatalogue',
        'leadership_courses_completed_reports' => 'leadership_courses_completed',
        'reporting_filter'                     => 'filter',
        'reporting_setting'                    => 'settingcolor'
    );
}

/**
 * Return keys from report_map based on business rules
 * @param type $userid
 * @return Array array of report keys
 */
function getAccessibleReportKeys($userid = null) {
    global $USER;
    if (!isset($userid) || empty($userid)) {
        $userid = $USER->id;
    }
    $context             = context_system::instance();
    $is_admin            = is_siteadmin($userid);
    $is_manager          = is_user_manager($userid);
    $is_user_hierarchy   = is_user_in_hierarchy($userid);
    $view_report_allowed = has_capability('block/reporting:viewreports', $context);

    $report_map = getReportMap();
    $keys = array();

    // admin can view all reports
    if ($is_admin) {
        $keys = array_keys($report_map);
    }
    else {
        if ($is_user_hierarchy || $is_manager || $view_report_allowed) {
            $keys = array(
                // 'individual_reports',  //hide individual report #16426
                // 'course_overview_reports', //hide individual report #16427
                'myexternaltraining',
                'usertranscript',
            );

            /**
             * remove the following reports from manager access as per MMSG ticket#24434
             * compliance_modules_status_reports,
             * learningbundle_progress_reports
             */
            if ($is_manager || $view_report_allowed) {
                $keys = array_merge($keys, array(
                    'alllearningstatusreport',
                ));
            }
            if ($view_report_allowed) {
                $keys = array_merge($keys, array(
                    // 'general_reports', //hide individual report #16424
                    'compliance_modules_status_reports',
                    'learningbundle_progress_reports',
                    'activity_reports',
                    'allstaffreport',
                    // 'learningactivitycataloguereport', //hide individual report #16423
                    'leadership_courses_completed_reports'
                ));
            }
        }
    }
    return $keys;
}

/**
 * Check if report is accessible by user
 * @param type $filepath The filepath of the filename
 * @param type $userid User id
 * @return boolean
 */
function isReportAccessible($filepath, $userid = null) {
    global $USER;
    if (!isset($userid)) {
        $userid = $USER->id;
    }
    if (!isset($filepath) || empty($filepath)) {
        return false;
    }
    $reports = getAccessibleReportKeys($userid);

    $path_parts = pathinfo($filepath);
    $filename = $path_parts['filename'];

    $key = getReportKeyByFilename($filename);
//  error_log('$key = ' . var_export($key, true));
//  error_log('$reports = ' . var_export($reports, true));

    if (!in_array($key, $reports)) {
        return false;
    }
    return true;
}

/**
 * Get report key
 * @param type $filename
 * @return type
 */
function getReportKeyByFilename($filename) {
    $report_map = getReportMap();
    return array_search($filename, $report_map);
}

function view_report_allowed($context = null) {
    if (!isset($context)) {
        $context = context_system::instance();
    }
    return has_capability('block/training_report:viewreports', $context) || has_capability('block/training_report:viewallreports_as_administrators', $context);
}

function view_users_report_allowed($context = null) {
    if (!isset($context)) {
        $context = context_system::instance();
    }
    return has_capability('block/training_report:view_users_report', $context)
    || has_capability('block/training_report:viewallreports_as_administrators', $context);
}

function getAdminInfo($params = array(), $fields = '*') {
    global $DB;
    if (!isset($params) || empty($params)) {
        $params = array('username' => 'admin');
    }
    return $DB->get_record('user', $params, $fields);
}

/**
 * any change to profile short name, please update the map
 * Do not change the keys
 */
function get_user_profile_fields_map(){
    return array(
        'posStartDate' => 'Effectivedate',
        'empID' => 'EmployeeID',
        'posTitle' => 'PositionTitle',
        'jobProfile' => 'JobProfile',
        'managerName' => 'WorkerManager',
        'segment' => 'Segment',
        'jobFamily' => 'JobFamily',
        'supOrg' => 'SupervisoryOrganization',
        'state' => 'State',
        'costCenter' => 'CostCenter',
    );
}

/**
 * Get current course completion status for all users
 * @param type $courseids
 * @return type
 */
function get_user_current_courses_status($courseids = array()) {
    global $DB;
    $cond = '';
    $pars = array();
    if (!empty($courseids)) {
        list($cond, $pars) = $DB->get_in_or_equal($courseids);
        $cond = 'AND cm.course ' . $cond;
    }

    $sql = <<< EOT
SELECT
  CONCAT(ce.userid, '_', ce.courseid) as id,
  ce.userid, 
  ce.courseid, 
  ce.enrolled_date, 
  COUNT(*) as total,
  SUM(IF(cmc.completionstate = 1 OR cmc.completionstate = 2, 1, 0)) as completed,
  MAX(cmc.timemodified) as max_completion_date
  -- SUM(IF(cmc.completionstate = 1 OR cmc.completionstate = 2, 1, 0)) / COUNT(*) as percentage,
  -- IF(SUM(IF(cmc.completionstate = 1 OR cmc.completionstate = 2, 1, 0)) / COUNT(*) = 1, MAX(cmc.timemodified), NULL) as completed_date
FROM (
  SELECT
    ue.userid,
    e.courseid,
    MIN(ue.timecreated) as enrolled_date
  FROM
    mdl_user u 
    JOIN mdl_user_enrolments ue ON ue.userid = u.id
    JOIN mdl_enrol e ON ue.enrolid = e.id
    where u.deleted = 0
  GROUP BY
    ue.userid, e.courseid
  ORDER BY
    ue.userid
) ce
LEFT JOIN mdl_course_modules cm ON cm.course = ce.courseid
LEFT JOIN mdl_course_modules_completion cmc ON cmc.coursemoduleid = cm.id AND ce.userid = cmc.userid
where 
  cm.completion != 0
  $cond
GROUP BY 
  CONCAT(ce.userid, '_', ce.courseid), 
  ce.userid, 
  ce.courseid, 
  ce.enrolled_date
EOT;
    $records = $DB->get_records_sql($sql, $pars);
    $data = array();
    foreach ($records as $id => $record) {
        if (!isset($data[$record->userid][$record->courseid])) {
            $data[$record->userid][$record->courseid] = array(
                'enrolled_date' => $record->enrolled_date,
                'total' => $record->total,
                'completed' => $record->completed,
                'max_completion_date' => $record->max_completion_date
            );
        }
    }
    return $data;
}

function get_all_sub_categories($categoryid){
    global $DB;
    return $DB->get_fieldset_sql('SELECT id from {course_categories} where '.$DB->sql_like('path',':path'),['path'=>'/'.$DB->sql_like_escape($categoryid).'/%']);
}

function get_individual_user_options($userid,$hierarchy){
    global $DB;
    $is_admin = is_siteadmin($userid);
    if ($is_admin || is_report_administrator()) {
        $rs = $DB->get_records('user',array('confirmed'=>1,'deleted'=>0),"firstname ASC,lastname ASC");
    }else{
        $is_vendor = is_vendor($userid);
        // check if they are vendor or other user in the hierarchy
        if($is_vendor){
            $courses_list = get_vendor_course_ids($userid);
            $users_enrolded = get_users_enrolled($courses_list);
            $list_users = implode(",",$users_enrolded);
            $sql = "SELECT id,firstname,lastname,username,email from mdl_user where id=".$userid." or id in(".$list_users.") order by firstname ASC, lastname ASC";
        }else if($hierarchy){
            $current_node_id = $DB->get_field('hierarchy_user','node_id',array('user_id'=>$userid));
            $all_users_under_thisuser = get_all_users_from_nodes($current_node_id);
            $sql = "SELECT id,firstname,lastname,username,email from mdl_user where deleted=0 and (id=".$userid." 
                     or id in(".$all_users_under_thisuser.")) order by firstname ASC, lastname ASC";
        }else if(!view_report_allowed()){
            $sql = "SELECT id,firstname,lastname,username,email from mdl_user where id=".$userid;
        }else{
            $sql = "SELECT id,firstname,lastname,username,email from mdl_user where confirmed=1 and deleted=0";
        }
        $rs = $DB->get_records_sql($sql);
    }
    $data = [];
    if(!empty($rs)){
        foreach ($rs as $row) {
            if($row->id==1) continue;
            $user = new stdClass();
            $user->id = $row->id;
            $user->value = $row->id;
            $user->label = ucfirst($row->firstname)." ".ucfirst($row->lastname)." (".$row->email.")";
            $data[] = $user;
        }
    }
    return $data;
}

function sanitize_string($str){
//   // if ($str != 'admin') {
//   //  var_dump(array_map(function($ch) { return ['hex' => bin2hex($ch), 'ch' => $ch]; }, str_split($str)));die();
//   // }
//   //My t’e’s`t – “Long” – ‘single’

    $str = str_replace(chr(150), '-', $str);
    $str = str_replace(chr(145), '\'', $str);
    $str = str_replace(chr(146), '\'', $str);
    // $str = str_replace(chr(147), '"', $str);
    // $str = str_replace(chr(148), '"', $str);
    $str = iconv("UTF-8", "UTF-8//TRANSLIT", strip_tags( trim($str) ) );
    $str = preg_replace('/[\x{2014}\x{2013}–]+/u', '-', $str);
    $str = preg_replace("!\s+!", " ", $str);
    //$str = addslashes($str);
    //$str = $DB->sql_like_escape($str);

    return $str;
}