<?php 
require('../../../config.php');
global $DB;

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title($SITE->fullname.': Manage Hierarchy: Add/Edit Level');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/admin/tool/hierarchy/hierarchy_level_edit.php');

//Setup Breadcrumbs
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Site administration');
$PAGE->navbar->add('Users');
$PAGE->navbar->add('Accounts');
$PAGE->navbar->add('Manage Hierarchy', new moodle_url('/admin/tool/hierarchy/hierarchy_level_setup.php'));
	
if ($CFG->forcelogin) {
    require_login();
}

$level_id; 
if (isset($_GET['level_id'])) {
    $level_id = $_GET['level_id'];
} else {
    $level_id = '';
}
   
?>

<?php
if(isset($_POST['save'])) {

    if(isset($_POST['level_id']) && empty($_POST['level_id']) != true) {
        $level_record = $DB->get_record('hierarchy_level',array('id'=>intval($level_id)));
        if ($level_record != false) {
            $updated_level_record = new stdClass();
            $updated_level_record->id = $level_id;
            $updated_level_record->description = $_POST['level_description'];
            $DB->update_record('hierarchy_level', $updated_level_record);
        }
        
    } else {
        $new_level_record = new stdClass();
        $new_level_record->level = $_POST['level'];
        $new_level_record->description = $_POST['level_description'];
        $level_id = $DB->insert_record('hierarchy_level', $new_level_record);
    } 

    header('Location:' . $CFG->wwwroot .'/admin/tool/hierarchy/hierarchy_level_setup.php'); 
    
} else if (isset($_POST['cancel'])) {
    header('Location:' . $CFG->wwwroot .'/admin/tool/hierarchy/hierarchy_level_setup.php'); 
} else {
    echo $OUTPUT->header();
    displayform($level_id);   
    echo $OUTPUT->footer();   
}

function displayform($level_id) {
    global $DB;
    // $levels_sql = 'SELECT   LEVEL 
    //                FROM     {hierarchy_level} 
    //                Order BY LEVEL';
    // $level_records = $DB->get_records_sql($levels_sql);

    // /* start level */
    // $start_level_sql = 'SELECT   LEVEL 
    //                     FROM     {hierarchy_level}  
    //                     Order BY LEVEL
    //                     LIMIT    1';
    // $start_level_record = $DB->get_record_sql($start_level_sql);
    // $start_level = $start_level_record->level;

    // /* end level */
    // $end_level_sql = 'SELECT   LEVEL 
    //                   FROM     {hierarchy_level} 
    //                   Order BY LEVEL DESC
    //                   LIMIT    1  ';
    // $end_level_record = $DB->get_record_sql($end_level_sql);
    // $end_level = $end_level_record->level;

    // $num = count($level_records);
    // if ($num != 0) {
    //     $available_level_list = [];
    //     for($i = $start_level; $i <= $end_level + 1;$i++) {
    //         $available_level_list[$i] = 1; 
    //     }
    // }
       
    // foreach ($level_records as $level_record) {
    //     $level = $level_record->level;
    //     $available_level_list[$level] = 0;
    // }

    $level_record;
    if (isset($level_id) && empty($level_id) != true) {
        $level_record = $DB->get_record('hierarchy_level', array('id'=>$level_id));
        echo"<div id='refresher'>";
        echo"<h2 class='headingblock header'>Add/Edit Level</h2>";
        echo"<div id='texte'></div>";
            echo"<form id='newform' action='' method='POST'>";
                echo"<label>Level</label>";
                echo "<select id='level' name='level'>";
                echo "<option value='" . $level_record->level . "'>" . $level_record->level . "</option>";
                echo "</select>";
                echo "<input type='hidden' id='level_id' name='level_id' value='". $level_record->id . "'>";
                echo "<br/>";
                echo "<br/>";
                echo"<label>Level Description</label>";
                echo "<textarea rows='4' cols='50' id='level_description' name='level_description'>";
                echo $level_record->description;
                echo "</textarea>";
                echo "<br/>";
                echo "<br/>";
                echo'<input class="btn" id="save" type="submit" value="Save" name="save"/>';
                echo'<input class="btn" id="cancel" type="submit" value="Cancel" name="cancel"/>';
            echo "</form>";
        echo "</div>";
    } else {
        $all_levels_sql = 'SELECT   id,
                                    level
                           FROM     {hierarchy_level}
                           ORDER BY level DESC
                           LIMIT    1';

        $level_record = $DB->get_record_sql($all_levels_sql);
        echo"<div id='refresher'>";
        echo"<h2 class='headingblock header'>Add/Edit Level</h2>";
        echo"<div id='texte'></div>";
            echo"<form id='newform' action='' method='POST'>";
                echo"<label>Level</label>";
                echo "<select id='level' name='level'>";
                echo "<option value='" . ($level_record->level + 1) . "'>" . ($level_record->level + 1) . "</option>";
                echo "</select>";
                echo "<br/>";
                echo "<br/>";
                echo"<label>Level Description</label>";
                echo "<textarea rows='4' cols='50' id='level_description' name='level_description'>";
                echo "</textarea>";
                echo "<br/>";
                echo "<br/>";
                echo'<input class="btn" id="save" type="submit" value="Save" name="save"/>';
                echo'<input class="btn" id="cancel" type="submit" value="Cancel" name="cancel"/>';
            echo "</form>";
        echo "</div>";
    }
   
}
?>


    
    
    