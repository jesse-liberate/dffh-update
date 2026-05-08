<?php 
require('../../../config.php');
global $DB;

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title($SITE->fullname.': Manage Hierarchy');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/admin/tool/hierarchy/hierarchy_level_setup.php');

if ($CFG->forcelogin) {
    require_login(); 
}

echo $OUTPUT->header();
    
?>
<head>
  <link rel="stylesheet" href="css/jq.css" type="text/css" media="print, projection, screen" />
  <link rel="stylesheet" href="themes/blue/style.css" type="text/css" media="print, projection, screen" />
</head>

<?php
    echo"<h2>List of Levels</h2>";
  
    echo"<div id='reportDisplay'>";
        echo"<table id='myTable' class='tablesorter'>";
            echo"<thead>";
                echo"<tr>";
                     echo"<th>Level</th>";
                     echo"<th>Level Description</th>";
                     echo"<th>Nodes Number</th>";
                     echo"<th>Edit</th>";
                echo"</tr>";
            echo"</thead>";
            echo"<tbody>";

    $lowest_level_sql = 'SELECT   level,
                                  id 
                         FROM     {hierarchy_level}
                         Order by id DESC 
                         limit    1';
    $lowest_level_record = $DB->get_record_sql($lowest_level_sql);
    
    $level_records = $DB->get_records('hierarchy_level');
    foreach ($level_records as $level_record) {
        echo"<tr>";
        echo "<td><a href='hierarchy_node_setup.php?level_id=" . $level_record->id . "'>" . $level_record->level . "</a></td>";
        echo "<td>" . $level_record->description . "</td>";

        $level_id = $level_record->id;
        $nodes_number_under_level = $DB->get_records('hierarchy_node', array('level_id'=>$level_id));
        echo "<td>" . count($nodes_number_under_level) . "</td>";
        
        echo"<td>";

        $nodes_records = $DB->get_records('hierarchy_node', array('level_id'=>$level_id));
        if ($lowest_level_record->id ==  $level_id && count($nodes_records) == 0 ) {
             echo "<a href='hierarchy_level_delete.php?level_id=" . $level_record->id . "'><img src='".$OUTPUT->image_url('t/delete')."' alt='' class='iconsmall'></a>";
        }
        echo "<a href='hierarchy_level_edit.php?level_id=" . $level_record->id . "'><img src='".$OUTPUT->image_url('t/edit')."' alt='' class='iconsmall'></a>";
        //echo "<a href='hierarchy_report_node_setup.php?level_id=" . $level_record->id . "'><img src='".$OUTPUT->pix_url('t/edit')."' alt='' class='iconsmall'></a>";           
        echo "</td>";
        echo"</tr>";
    }

    echo"</tbody>";
    echo"</table>";

    echo"<td><a class='btn' href='hierarchy_level_edit.php'>Add New Level</a></td></br>";
    echo"</div>";
?>

<?php echo $OUTPUT->footer();?>
<!-- Run jQuery functions after footer -->
<script type="text/javascript">
  $(function() {    
    $("#myTable").tablesorter({sortList:[[0,0]], widgets: ['zebra'],  headers: { 3:{sorter: false}}});
  }); 
</script> 
    
    

    
    