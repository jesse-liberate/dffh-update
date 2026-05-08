<?php 
require('../../../config.php');
global $DB;

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/admin/tool/hierarchy/hierarchy_user_edit.php');

//Setup Breadcrumbs
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Site administration');
$PAGE->navbar->add('Users');
$PAGE->navbar->add('Accounts');
$PAGE->navbar->add('Assign User to Hierarchy', new moodle_url('/admin/tool/hierarchy/hierarchy_user_setup.php'));
	
if ($CFG->forcelogin) {
    require_login();
}

$user_id;
$page; 
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
} else {
    $user_id = '';
}

if (isset($_GET['page'])) {
    $page = $_GET['page'];
} else {
    $page = '';
}
  
?>

<?php
if(isset($_POST['save'])) {
    if(isset($_POST['user_id']) && empty($_POST['user_id']) != true && isset($_POST['node_id'])) {
        $user_node_record = $DB->get_record('hierarchy_user', array('user_id'=>$_POST['user_id']));
        
        /* user node record exists */
        if ($user_node_record != false) {
            if (empty($_POST['node_id'] == true)) {
                /* delete user node record*/
                $DB->delete_records('hierarchy_user', array('user_id'=>$user_id));
            } else {
                /* update user node record */
                $updated_user_node_record = new stdClass();
                $updated_user_node_record->id = $user_node_record->id;
                $updated_user_node_record->node_id = $_POST['node_id'];
                $DB->update_record('hierarchy_user', $updated_user_node_record);

                /*-----------------Get record of User Info data--------------------*/
                $user_info_data_id = $DB->get_field('user_info_data', 'id', array('userid'=>$_POST['user_id'], 'fieldid'=>'11')); 

                /*-----------------Update Store info to User Info data table when change hierarchy --------------------*/
                $sql2 = "SELECT description
                         FROM {hierarchy_node}
                         WHERE id = ?";
                $node = current($DB->get_records_sql($sql2, array($_POST['node_id'])));

                $record = new stdClass();
                $record->id = $user_info_data_id;
                $record->data = $node->description;
                $DB->update_record('user_info_data', $record, false); 
                /*-------------------------------------*/

            }

        /* user node record not exists */
        } else {
            if (empty($_POST['node_id'] != true)) {
                $new_user_node_record = new stdClass();
                $new_user_node_record->user_id = $_POST['user_id'];
                $new_user_node_record->node_id = $_POST['node_id'] ;
                $DB->insert_record('hierarchy_user', $new_user_node_record);
            }
        }
    }
    header('Location:' . $CFG->wwwroot .'/admin/tool/hierarchy/hierarchy_user_setup.php?page=' . $page);  
} else if (isset($_POST['cancel'])) {
    header('Location:' . $CFG->wwwroot .'/admin/tool/hierarchy/hierarchy_user_setup.php?page=' . $page); 
} else {
    echo $OUTPUT->header();
    displayform($user_id);   
    echo $OUTPUT->footer();   
}



function displayform($user_id) {
    global $DB;
    $user_record;
    if (isset($user_id) && empty($user_id) != true) {
        $user_record = $DB->get_record('user', array('id'=>$user_id));
        $current_user_node_record = $DB->get_record('hierarchy_user', array('user_id'=>$user_id));
        $current_node_record = false;
        if ($current_user_node_record != false) {
            $current_node_record = $DB->get_record('hierarchy_node', array('id'=>$current_user_node_record->node_id));
        }

 //       $node_records = $DB->get_records('hierarchy_node');
 //       $node_records = $DB->get_records('hierarchy_node', array $conditions=null, $sort='', $fields='*', $limitfrom=0, $limitnum=0);
        $node_records = $DB->get_records('hierarchy_node', null, 'level_id', '*', 0, 0);

        echo"<div id='refresher'>";
        echo"<h2>Assign user to node</h2>";
        echo"<div id='texte'></div>";
            echo"<form id='newform' action='' method='POST'>";
                echo"<label>First Name</label>";
                echo $user_record->firstname;
                echo "</br>";
                echo "</br>";
                echo"<label>Last Name</label>";
                echo  $user_record->lastname;
                echo "<input type='hidden' name='user_id' value=" . $user_record->id . ">"; 
                echo "</br>";
                echo "</br>";
                echo"<label>Node</label>";
                if ($node_records != false) {
                    echo "<select id='node_id' name='node_id'>";
                    echo "<option></option>";
                    foreach ($node_records as $node_record) {
                        if ($current_node_record->id != $node_record->id) {
                            echo "<option value='" . $node_record->id . "' >" . $node_record->description . "</option>";
                        } else {
                            echo "<option value='" . $node_record->id . "' selected>". $node_record->description . "</option>";
                        }
                    }
                    echo "</select>";
                }
                echo "</br>";
                echo "</br>";
            
                echo'<input class="btn" id="save" type="submit" value="Save" name="save"/>';
                echo'<input class="btn" id="cancel" type="submit" value="Cancel" name="cancel"/>';
            echo "</form>";
        echo "</div>";
    } else {
        // // $all_levels_sql = 'SELECT   id,
        // //                             level
        // //                    FROM     {hierarchy_level}
        // //                    ORDER BY level DESC
        // //                    LIMIT    1';

        // $level_record = $DB->get_record_sql($all_levels_sql);
        // echo"<div id='refresher'>";
        // echo"<h2>Add/Edit Level</h2>";
        // echo"<div id='texte'></div>";
        //     echo"<form id='newform' action='' method='POST'>";
        //         echo"<label>Level</label>";
        //         echo "<select id='level' name='level'>";
        //         echo "<option value='" . ($level_record->level + 1) . "'>" . ($level_record->level + 1) . "</option>";
        //         echo "</select>";
        //         echo "<br/>";
        //         echo "<br/>";
        //         echo"<label>Level Description</label>";
        //         echo "<textarea rows='4' cols='50' id='level_description' name='level_description'>";
        //         echo "</textarea>";
        //         echo "<br/>";
        //         echo "<br/>";
        //         echo'<input class="btn" id="save" type="submit" value="Save" name="save"/>';
        //         echo'<input class="btn" id="cancel" type="submit" value="Cancel" name="cancel"/>';
        //     echo "</form>";
        // echo "</div>";
    }
   
}
?>


    
    
    