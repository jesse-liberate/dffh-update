<?php
// require_once($CFG->dirroot.'/admin/tool/hierarchy/lib.php');

function reportwizard_find_children_nodes($nodename) {
    global $DB;

    $all_children_nodes = array();

    $node = $DB->get_record('hierarchy_node', array('name' => $nodename ));

    if (!$node) {
        return $all_children_nodes; 
    }

    $node_id = $node->id;

    $all_children_node_ids = array();
    $children_node_ids_queue = array();

    

    $lower_level_children_nodes_records = $DB->get_records('hierarchy_node', array('parent_node_id'=>$node_id));
    if ($lower_level_children_nodes_records != false) {
        foreach ($lower_level_children_nodes_records as $lower_level_children_nodes_record) {
            $all_children_node_ids[] = $lower_level_children_nodes_record->id;
            $all_children_nodes[] = $lower_level_children_nodes_record;
            $children_node_ids_queue[] = $lower_level_children_nodes_record->id;
        }
    }
    
    while (count($children_node_ids_queue) != 0 ) {
        foreach ($children_node_ids_queue as $index=>$child_node_id) {
            $lower_level_children_nodes_records = $DB->get_records('hierarchy_node', array('parent_node_id'=>$child_node_id));
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


function get_user_node_id($user_id){
    global $DB;

    $user_node  = $DB->get_record('hierarchy_user', array('user_id' => $user_id));
    if ($user_node) {
        return $user_node->node_id;
    }else{
        return false;
    }
}
