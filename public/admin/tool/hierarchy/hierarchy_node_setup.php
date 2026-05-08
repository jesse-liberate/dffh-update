<?php 
require('../../../config.php');
global $DB;

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/admin/tool/hierarchy/hierarchy_node_setup.php');

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
if (isset($_GET['level_id'])) {
    $level_id = $_GET['level_id'];
} else {
    header('Location:' . $CFG->wwwroot .'/admin/tool/hierarchy/hierarchy_node_setup.php');
}

echo $OUTPUT->header();
    
?>
<head>
  <link rel="stylesheet" href="css/jq.css" type="text/css" media="print, projection, screen" />
  <link rel="stylesheet" href="themes/blue/style.css" type="text/css" media="print, projection, screen" />
</head>
<?php
    
    $level_record = $DB->get_record('hierarchy_level', array('id'=>$level_id));
    
    echo"<h2 class='headingblock header'>List of Nodes Under Level : " . $level_record->level . "</h2>";
    echo"<div id='reportDisplay'>";
        echo"<table id='myTable' class='tablesorter'>";
            echo"<thead>";
                echo"<tr>";
                     echo"<th>Node ID</th>";
                     echo"<th>Node Name</th>";
                     echo"<th>Node description</th>";
                     echo"<th>Level</th>";
                     echo"<th>Parent Node</th>";
                     echo"<th>Children Node</th>";
                     echo"<th>Edit</th>";
                echo"</tr>";
            echo"</thead>";
            echo"<tbody>";

    $node_records = $DB->get_records('hierarchy_node', array('level_id'=>$level_id));

    foreach ($node_records as $node_record) {
        echo "<tr>";
        echo "<td>" . $node_record->id . "</td>";
        echo "<td>" . $node_record->name . "</td>";
        echo "<td>" . $node_record->description . "</td>";

        $level_record = $DB->get_record('hierarchy_level', array('id'=>$node_record->level_id));
        $parent_node_record = $DB->get_record('hierarchy_node', array('id'=>$node_record->parent_node_id));
        $children_node_records = $DB->get_records('hierarchy_node', array('parent_node_id'=>$node_record->id));

        if ($level_record != false) {
            echo "<td>";
            echo "<p>" . $level_record->level;
            if ($parent_node_record == false && $children_node_records == false) {
                echo "<a href='hierarchy_node_edit.php?node_id=" . $node_record->id . "&change_level'><img src='".$OUTPUT->pix_url('t/edit')."' alt='' class='iconsmall'></a>";    
            }
            echo "</p>";
            echo "</td>";
        } else {
            echo "<td></td>";
        }
        
        if ($parent_node_record != false) {
            echo "<td>";
            echo "<p>";
            echo $parent_node_record->name;
            echo " (node id : " . $parent_node_record->id . ")";
            echo "<a href='hierarchy_node_edit.php?node_id=" . $node_record->id . "&change_parent_node'><img src='".$OUTPUT->pix_url('t/edit')."' alt='' class='iconsmall'></a>";
            echo "</p>";
            echo "</td>";

        } else {
            $parent_level = $level_record->level - 1;
            if ($parent_level >= 1) {
                $parent_level_record = $DB->get_record('hierarchy_level', array('level'=>$parent_level));
                $parent_node_records = $DB->get_records('hierarchy_node', array('level_id'=>$parent_level_record->id));
                if(count($parent_node_records) > 0) {
                    echo "<td>";
                    echo "<a href='hierarchy_node_edit.php?node_id=" . $node_record->id . "&change_parent_node'><img src='".$OUTPUT->pix_url('t/edit')."' alt='' class='iconsmall'></a>";
                    echo "</p>";
                    echo "</td>";
                } else {
                    echo "<td>";
                    echo "<p>";
                    echo "</p>";
                    echo "</td>";
                }
            } else {
                echo "<td>";
                echo "<p>";
                echo "</p>";
                echo "</td>";
            }
         
        }

       
        if ($children_node_records != false) {
            echo "<td>";
            foreach ($children_node_records as $children_node_record) {
                echo "<p>" . $children_node_record->name . " ( id : " . $children_node_record->id . ")" ."</p>";
            }
            echo "</td>";
        } else {
            echo "<td></td>";
        }
           
        echo"<td>";
        echo "<a href='hierarchy_node_delete.php?node_id=" . $node_record->id . "'><img src='".$OUTPUT->pix_url('t/delete')."' alt='' class='iconsmall'></a>";
        //echo "<a href='hierarchy_report_node_setup.php?level_id=" . $level_record->id . "'><img src='".$OUTPUT->pix_url('t/edit')."' alt='' class='iconsmall'></a>";           
        echo "</td>";

        echo"</tr>";
    }



    echo"</tbody>";
    echo"</table>";

    echo"<td><a class='btn' href='hierarchy_node_edit.php?level_id=" . $level_record->id . "'>Add New Node</a></td></br>";
    echo"</div>";
?>

<?php echo $OUTPUT->footer();?>
<!-- Run jQuery functions after footer -->
<script type="text/javascript">
  $(function() {    
    $("#myTable").tablesorter({sortList:[[0,0]], widgets: ['zebra'],  headers: { 6:{sorter: false}}});
  }); 
</script> 
</body>
    
    

    
    