<?php 
require('../../../config.php');
global $DB;
   
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('frontpage');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/admin/tool/hierarchy/hierarchy_node_delete.php');
$PAGE->navbar->add("List of Levels", new moodle_url('/admin/tool/hierarchy/hierarchy_node_delete.php'));

if ($CFG->forcelogin) {
    require_login();
}

// if ($categoryid && !$category->visible && !has_capability('moodle/category:viewhiddencategories', $PAGE->context)) {
//     throw new moodle_exception('unknowncategory');
// }
    
if ($_GET['node_id']==NULL) {
    $node_id = "";
    header('Location:' . $CFG->wwwroot . '/admin/tool/hierarchy/hierarchy_node_setup.php'); 
} else {
    $node_id = $_GET['node_id'];


    if (isset($_GET['type']) && !empty($_GET['type'])) {
        $type = $_GET['type'];
        $node_record = $DB->get_record('hierarchy_node', array('id'=>$node_id));

        if (strcmp($type,'Continue') == 0) {
            $node_id = $_GET['node_id'];
            $whether_delete_node = true;
            
            $user_records = $DB->get_records('hierarchy_user', array('node_id'=>$node_id));
            if ($user_records != false) {
                $whether_delete_node = false;
                header('Location:' . $CFG->wwwroot .'/admin/tool/hierarchy/hierarchy_node_setup.php?level_id=' . $node_record->level_id . '&error=There are some exsiting users in the node');     
            }

            $children_records = $DB->get_records('hierarchy_node', array('parent_node_id'=>$node_id));
            if ($children_records != false) {
                $whether_delete_node = false;
                header('Location:' . $CFG->wwwroot .'/admin/tool/hierarchy/hierarchy_node_setup.php?level_id=' . $node_record->level_id . '&error=There are some children nodes existing');     
            }

            if ($user_records == false && $children_records == false && $whether_delete_node = true) {
                $DB->delete_records('hierarchy_node', array('id'=>$node_id));
                header('Location:' . $CFG->wwwroot .'/admin/tool/hierarchy/hierarchy_node_setup.php?level_id=' . $node_record->level_id); 
            }
        } else if (strcmp($type,'Cancel') == 0) {
            header('Location:' . $CFG->wwwroot .'/admin/tool/hierarchy/hierarchy_node_setup.php?level_id=' . $node_record->level_id); 
        }
    } 


    else {
        $node_record = $DB->get_record('hierarchy_node', array('id'=>$node_id));

        $user_records = $DB->get_records('hierarchy_user', array('node_id'=>$node_id));

            echo $OUTPUT->header();
            echo "<h2>Delete Node</h2>";
            echo "<div class='box general box'>";
        if (empty($user_records)) {
            echo    "<p>There are no users assigned to ". $node_record->name .".</p>";
            echo    "<p>Are you absolutely sure you want to completely delete this node?</p>";
            echo    "<div class='buttons'>";
            echo        "<div class='singlebutton'>";
            echo            "<form action=" . $CFG->wwwroot . "/admin/tool/hierarchy/hierarchy_node_delete.php  method='get'>";
            echo                "<input type='submit' name='type' value='Continue'>";
            echo                "<input type='submit' name='type' value='Cancel'>";
            echo                "<input type='hidden' name='node_id' value=". $_GET['node_id'] .">";
            echo            "</from>";
            echo        "</div>";
            echo   "</div>";
        } else {
            echo    "<p>This node (". $node_record->description .") cannot be deleted because the following users assigned to it: </p>";
            $namelist = array();
            foreach ($user_records as $user_record) {                
                $sql = "SELECT firstname, lastname
                        FROM  mdl_user
                        WHERE mdl_user.id = '$user_record->user_id'"; 
                $namelist[] = $DB->get_records_sql($sql);
                
            }
            foreach ($namelist as $namearray) {
                foreach ($namearray as $key=>$name) {
                    echo    "<p>  - ". $name->firstname." ".$name->lastname.".</p>";      
                }                 
            }
            echo    "<p>Please re-assign these users to other nodes before deleting this node.</php>";
            echo    "<div class='buttons'>";
            echo        "<div class='singlebutton'>";
            echo            "<form action=" . $CFG->wwwroot . "/admin/tool/hierarchy/hierarchy_node_delete.php  method='get'>";
            echo                "<a href=".$CFG->wwwroot."/admin/tool/hierarchy/hierarchy_user_setup.php"."><input type='button' name='type' value='Re-assign'></a>";
            echo                "<input type='submit' name='type' value='Cancel'>";
            echo                "<input type='hidden' name='node_id' value=". $_GET['node_id'] .">";
            echo            "</from>";
            echo        "</div>";
            echo   "</div>";
           
        }        
            echo  "</div>";
            echo $OUTPUT->footer();
    }
}  

?>