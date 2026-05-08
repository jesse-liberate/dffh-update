<?php 
require('../../../config.php');
global $DB;
   
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title($SITE->fullname.': Manage Hierarchy: Delete Level');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/admin/tool/hierarchy/hierarchy_level_delete.php');

//Setup Breadcrumbs
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Site administration');
$PAGE->navbar->add('Users');
$PAGE->navbar->add('Accounts');
$PAGE->navbar->add('Manage Hierarchy', new moodle_url('/admin/tool/hierarchy/hierarchy_level_setup.php'));

if ($CFG->forcelogin) {
    require_login();
}
    
if ($_GET['level_id']==NULL) {
    $level_id = "";
    header('Location:' . $CFG->wwwroot .'/admin/tool/hierarchy/hierarchy_level_setup.php'); 
} else {
    if (isset($_GET['type']) && !empty($_GET['type'])) {
        $type = $_GET['type'];
        if (strcmp($type,'continue') == 0) {
            $level_id = $_GET['level_id'];
            $DB->delete_records('hierarchy_level', array('id'=>$level_id)); 
            header('Location:' . $CFG->wwwroot .'/admin/tool/hierarchy/hierarchy_level_setup.php'); 
        } else if (strcmp($type,'cancel') == 0) {
            header('Location:' . $CFG->wwwroot .'/admin/tool/hierarchy/hierarchy_level_setup.php'); 
        }
    } else {
        $level_record = $DB->get_record('hierarchy_level', array('id'=>$_GET['level_id']));
        //var_dump($contractor_record);
        echo $OUTPUT->header();
        echo "<h2>Delete Level</h2>";
        echo "<div class='box general box'>";
        echo    "<p>Are you absolutely sure you want to completely delete level : ". $level_record->level ."?</p>";
        echo    "<div class='buttons'>";
        echo        "<div class='singlebutton'>";
        echo            "<form action=" . $CFG->wwwroot . "/admin/tool/hierarchy/hierarchy_level_delete.php  method='get'>";
        echo                "<input type='submit' name='type' value='continue'>";
        echo                "<input type='submit' name='type' value='cancel'>";
        echo                "<input type='hidden' name='level_id' value=". $_GET['level_id'] .">";
        echo            "</from>";
        echo        "</div>";
        echo   "</div>";
        echo  "</div>";
        echo $OUTPUT->footer();
    }
}  


?>
