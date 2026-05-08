<?php 
require('../../../config.php');
global $DB;

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/admin/tool/hierarchy/hierarchy_node_edit.php');

//Setup Breadcrumbs
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Site administration');
$PAGE->navbar->add('Users');
$PAGE->navbar->add('Accounts');
$PAGE->navbar->add('Manage Hierarchy', new moodle_url('/admin/tool/hierarchy/hierarchy_level_setup.php'));
$PAGE->navbar->add('List of Nodes', new moodle_url('/admin/tool/hierarchy/hierarchy_node_setup.php'));
	
if ($CFG->forcelogin) {
    require_login();
}

$level_id = '';
$node_id = '';
if (isset($_GET['level_id'])) {
    $level_id = $_GET['level_id'];
} else if (isset($_GET['node_id'])) {
    $node_id = $_GET['node_id'];
} else {
    header('Location:' . $CFG->wwwroot .'/admin/tool/hierarchy/hierarchy_node_setup.php');
}
   
?>

<?php
if(isset($_POST['save'])) {

    if($_POST['node_name'] == "") {
            echo $OUTPUT->header();
            displayform();   
            echo $OUTPUT->footer();
        ?>
        <script>
            document.getElementById('texte').innerHTML = 'Please fill in all required fields\n';
        </script>
        <?php
         
    }else {
        $node_name = $_POST['node_name'];
        $node_description = $_POST['node_description'];
        $level_id = $_POST['level_id'];
        $parent_node_id = '';

        if(isset($_POST['parent_node_id']) && empty($_POST['parent_node_id']) != true){
            $parent_node_id = $_POST['parent_node_id'];    
        }

        if(isset($_POST['check_list']) && empty($_POST['check_list']) != true){
            $children_node_ids = $_POST['check_list'];    
        }

        $node_record = $DB->get_record('hierarchy_node',array('id'=>$node_id));
    
        if($node_record == false){
            $new_node_record = new stdClass();
            $new_node_record->name = $node_name;
            $new_node_record->description = $node_description;
            $new_node_record->level_id = $level_id;
            $new_node_record->parent_node_id = $parent_node_id;
            // $path = ''; 
            // $parent_node_record = $DB->get_record('hierarchy_node', array('id'=>$parent_node_id));
            // while ($parent_node_record != false) {
            //     $path = $parent_node_record->id . "," . $path;
            //     $parent_node_record = $DB->get_record('hierarchy_node', array('id'=>$parent_node_record->parent_node_id));
            // }
            // $new_node_record->path = $path;
            $node_id = $DB->insert_record('hierarchy_node', $new_node_record);        
        } else {
            
            //try {
                //$transaction = $DB->start_delegated_transaction();

                /* update */
                $updated_node_record = new stdClass();
                $updated_node_record->id = $node_id;
                $updated_node_record->name = $node_name;
                $updated_node_record->description = $node_description;
                $updated_node_record->level_id = $level_id;
                $updated_node_record->parent_node_id = $parent_node_id;
                $DB->update_record('hierarchy_node', $updated_node_record); 
                //$path = ''; 
                
                // $parent_node_record = $DB->get_record('hierarchy_node', array('id'=>$parent_node_id));
                // /* update parent path */
                // while ($parent_node_record != false) {
                //     $path = $parent_node_record->id . "," . $path;
                //     $parent_node_record = $DB->get_record('hierarchy_node', array('id'=>$parent_node_record->parent_node_id));
                // } 
                // $updated_node_record->path = $path;
                  
                // $node_old_path; 
                // $current_node_record = $DB->get_record('hierarchy_node', array('id'=>$node_id));
                // if ($current_node_record != false) {
                //     $node_old_path = $current_node_record->path;
                // }

                // /* update children path */
                // $children_node_records = $DB->get_records('hierarchy_node', array('parent_node_id'=>$node_id));
                // foreach ($children_node_records as $child_node_record) {
                //     $child_node_old_path = $child_node_record->path;
                //     $pos = strpos($child_node_old_path, $node_old_path);
                //     echo "pos :" . $pos;
                //     // $parent_ids = explode(",", $child_node_old_path);
                //     // foreach ($parent_ids as $index => $id) {
                //     //     echo $index . "</br>";
                //     //     echo $id . "</br>";
                //     // }
                //     // $old_parent_path;
                //     // $old_parent_id_index;
                //     // $parent_ids = explode(",", $child_node_path);
                //     // foreach ($parent_ids as $index => $parent_id) {
                //     //     echo "parent_id : " . $parent_id . "</br>";
                //     //     echo "node_id : " . $node_id . "</br>";
                //     //     var_dump(strcmp($parent_id, $node_id) == 0);
                //     //     var_dump($index);
                //     //     echo "-----------" . "</br>";
                //     //     if (strcmp($parent_id, $node_id) == 0) {
                //     //         echo $index;
                //     //         $old_parent_id_index = $index - 1;
                //     //         break;
                //     //     }
                //     // }

                //     // echo $old_parent_id_index . "</br>";
                //     // echo substr($child_node_path, $old_parent_id_index) . "</br>";
                    
                //     // $new_child_node_path = $path . substr($child_node_path, $old_parent_id_index);
                //     // echo $new_child_node_path . "</br>";
                //     /* update */
                //     $updated_new_child_node_record = new stdClass();
                //     $updated_new_child_node_record->id = $child_node_record->id;
                //     $updated_new_child_node_record->path = $new_child_node_path;
                //     $DB->update_record('hierarchy_node', $updated_new_child_node_record);   
                // }

                
                //$transaction->allow_commit();
            //} catch(Exception $e) {
            //    $transaction->rollback($e);
            //}
        } 
        header('Location:' . $CFG->wwwroot .'/admin/tool/hierarchy/hierarchy_level_setup.php');
        //header('Location:' . $CFG->wwwroot .'/hierarchy_report/hierarchy_report_node_setup.php?level_id=' . $level_id);  
    }

} else if (isset($_POST['cancel'])) {
    //header('Location:' . $CFG->wwwroot .'/admin/tool/hierarchy/hierarchy_node_setup.php'); 
    header('Location:' . $CFG->wwwroot .'/admin/tool/hierarchy/hierarchy_level_setup.php');
} else {
    echo $OUTPUT->header();
    displayform();   
    echo $OUTPUT->footer();
}

function displayform() {
    global $DB, $level_id, $node_id;
    if (empty($node_id) != true) {
        $node_record = $DB->get_record('hierarchy_node', array('id'=>$node_id));
        echo"<div id='refresher'>";
        echo"<h2>Add/Edit Node</h2>";
        echo"<div id='texte'></div>";
            echo"<form id='newform' action='' method='POST'>";
                echo "<input type='hidden' id='node_id' name='node_id' value='". $node_record->id . "'>";
                echo "<br/>";
                echo "<br/>";
                echo "<label>Node Name</label>";
                echo "<input type='input' id='node_name' name='node_name' value='". $node_record->name . "'>";
                echo "<br/>";
                echo "<br/>";
                echo"<label>Node Description</label>";
                echo "<textarea rows='4' cols='50' id='node_description' name='node_description'>";
                echo $node_record->description;
                echo "</textarea>";
                echo "<br/>";
                echo "<br/>";
                if (isset($_GET['change_level'])) {
                    echo"<label>Parent Node</label>";
                    $parent_node_record = $DB->get_record('hierarchy_node', array('id'=>$node_record->parent_node_id));
                    if ($parent_node_record != false) {
                        echo "<p>" . $parent_node_record->name . "</p>";
                        echo "<input type='hidden' id='parent_node_id' name='parent_node_id' value='". $parent_node_record->id . "'>";
                    }
                    
                } else if (isset($_GET['change_parent_node'])) {
                    echo"<label>Parent Node</label>";
                    $parent_node_record = $DB->get_record('hierarchy_node', array('id'=>$node_record->parent_node_id));
                    $level_record = $DB->get_record('hierarchy_level', array('id'=>$node_record->level_id));
                    $level = $level_record->level;
                    $parent_level;
                    if ($level > 1) {

                        echo "<select id='parent_node_id' name='parent_node_id' class='chzn-select'>";

                        $parent_level = $level - 1;

                        $tmp = array();
                        $possible_parent_node_records = array();
                        for ($i=2; $i<=$parent_level;$i++) {
                            $parent_level_record = $DB->get_record('hierarchy_level', array('level'=>$i));
                            $tmp = $DB->get_records('hierarchy_node', array('level_id'=>$parent_level_record->id));
                            foreach ($tmp as $t) {
                                array_push($possible_parent_node_records, $t);
                            }                            
                        }

                        if ($parent_node_record != false) {
                            echo "<option value=''></option>";
                            foreach ($possible_parent_node_records as $possible_parent_node_record) {
                                if($possible_parent_node_record->id == $parent_node_record->id ) {
                                    echo "<option value='" . $possible_parent_node_record->id . "' Selected>" . $possible_parent_node_record->name . " ( id : " . $possible_parent_node_record->id . ")". "</option>";    
                                } else {
                                    echo "<option value='" . $possible_parent_node_record->id . "'>" . $possible_parent_node_record->name . " ( id : " . $possible_parent_node_record->id . ")". "</option>";    
                                }
                            }
                        } else {
                            echo "<option value=''></option>";
                            foreach ($possible_parent_node_records as $possible_parent_node_record) {
                                echo "<option value='" . $possible_parent_node_record->id . "'>" . $possible_parent_node_record->name . " ( id : " . $possible_parent_node_record->id . ")" . "</option>";    
                            }

                        }
                        echo "</select>";
                    }
                }
                

                echo "</br>";
                echo "</br>";
                echo"<label>Children Node</label>";
                $children_node_records = $DB->get_records('hierarchy_node', array('parent_node_id'=>$node_id));
                if ($children_node_records != false) {
                    foreach ($children_node_records as $children_node_record) {
                        //echo "<p><input type='checkbox' name='check_list[]' value='" . $children_node_record->id . "' checked='true'>" . $children_node_record->name . "</p>";    
                        echo "<p>". $children_node_record->name . "</p>";    
                    }
                }
                
                echo "</br>";
                echo "</br>";
                if (isset($_GET['change_level'])) { 
                    echo "<label>Level</label>";
                    if ($parent_node_record == false && count($children_node_records) == 0) {
                        $all_levels_sql = 'SELECT   id,
                                                level
                                       FROM     {hierarchy_level}
                                       ORDER BY level';
                        $level_records = $DB->get_records_sql($all_levels_sql);
                        echo "<select id='level_id' name='level_id'>";
                        foreach ($level_records as $level_record) {
                            if ($level_record->id == $node_record->level_id) {
                                echo "<option value='" . $level_record->id . "' Selected>" . $level_record->level . "</option>";                        
                            } else {
                                echo "<option value='" . $level_record->id . "'>" . $level_record->level . "</option>";
                            }
                        }
                        echo "</select>";
                    } else {
                        $level_record = $DB->get_record('hierarchy_level', array('id'=>$node_record->level_id));
                        echo "<select id='level_id' name='level_id'>";
                        echo "<option value='" . $level_record->id . "' Selected>" . $level_record->level . "</option>";     
                        echo "</select>";
                    }
                } else if (isset($_GET['change_parent_node'])) {
                    echo "<label>Level</label>";
                    
                    $level_record = $DB->get_record('hierarchy_level', array('id'=>$node_record->level_id));
                    if ($level_record != false) {
                        echo "<p>" . $level_record->level . "</p>";
                        echo "<input type='hidden' id='level_id' name='level_id' value='". $level_record->id . "'>";
                    }
                }
                
                echo "<br/>";
                echo "<br/>";
                echo '<input class="btn" id="save" type="submit" value="Save" name="save"/>';
                echo '<input class="btn" id="cancel" type="submit" value="Cancel" name="cancel"/>';
            echo "</form>";
        echo "</div>";
    } else if (empty($level_id) != true) {
        $level_record = $DB->get_record('hierarchy_level', array('id'=>$level_id));
        echo"<div id='refresher'>";
        echo"<h2>Add/Edit Node Under Level : " . $level_record->level . " </h2>";
        echo"<div id='texte'></div>";
            echo"<form id='newform' action='' method='POST'>";
                echo "<input type='hidden' id='level_id' name='level_id' value='" . $level_record->id . "'>";
                echo "<br/>";
                echo "<br/>";
                echo"<label>Node Name</label>";
                echo "<input type='text' id='node_name' name='node_name' value=''>";
                echo "<br/>";
                echo "<br/>";
                echo"<label>Node Description</label>";
                echo "<textarea rows='4' cols='50' id='node_description' name='node_description'>";
                echo "</textarea>";
                echo "<br/>";
                echo "<br/>";
                $level = $level_record->level;
                $parent_level;
                if ($level > 0) {
                    echo"<label>Parent Node</label>";
                    echo "<select id='parent_node_id' name='parent_node_id' class='chzn-select'>";

                        $parent_level = $level - 1;

                        $tmp = array();
                        $possible_parent_node_records = array();
                        for ($i=0; $i<=$parent_level;$i++) {
                            $parent_level_record = $DB->get_record('hierarchy_level', array('level'=>$i));
                            $tmp = $DB->get_records('hierarchy_node', array('level_id'=>$parent_level_record->id));
                            foreach ($tmp as $t) {
                                array_push($possible_parent_node_records, $t);
                            }                            
                        }

                        if ($parent_node_record != false) {
                            echo "<option value=''></option>";
                            foreach ($possible_parent_node_records as $possible_parent_node_record) {
                                if($possible_parent_node_record->id == $parent_node_record->id ) {
                                    echo "<option value='" . $possible_parent_node_record->id . "' Selected>" . $possible_parent_node_record->name . " ( id : " . $possible_parent_node_record->id . ")". "</option>";    
                                } else {
                                    echo "<option value='" . $possible_parent_node_record->id . "'>" . $possible_parent_node_record->name . " ( id : " . $possible_parent_node_record->id . ")". "</option>";    
                                }
                            }
                        } else {
                            echo "<option value=''></option>";
                            foreach ($possible_parent_node_records as $possible_parent_node_record) {
                                echo "<option value='" . $possible_parent_node_record->id . "'>" . $possible_parent_node_record->name . " ( id : " . $possible_parent_node_record->id . ")" . "</option>";    
                            }

                        }
                        echo "</select>";
                }
                echo "<br/>";
                echo "<br/>";                
                echo'<input class="btn" id="save" type="submit" value="Save" name="save"/>';
                echo'<input class="btn" id="cancel" type="submit" value="Cancel" name="cancel"/>';
            echo "</form>";
        echo "</div>";
    }
   
}
?>


    
    
    