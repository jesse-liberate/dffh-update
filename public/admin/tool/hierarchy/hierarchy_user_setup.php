<?php 
require('../../../config.php');
global $DB;

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/admin/tool/hierarchy/hierarchy_user_setup.php');

if ($CFG->forcelogin) {
    require_login(); 
}

if (isset($_GET["page"])) { 
    $page = $_GET["page"]; 
} else { 
    $page=1; 
} 

echo $OUTPUT->header();
    
?>
<head>
  <link rel="stylesheet" href="css/jq.css" type="text/css" media="print, projection, screen" />
  <link rel="stylesheet" href="themes/blue/style.css" type="text/css" media="print, projection, screen" />
</head>
<?php
    echo"<h2>List of Users</h2>";
  
    /*------------------ Filter form --------------------*/
    echo "<form id='searchUserForm' action='' method='POST'>";

    echo "<label>Enter name</label>";
    echo "<input type='text' name='searchUserTxt' size='35'/>";

    echo "<label>OR Node name</label>";
    echo "<input type='text' name='searchNodeTxt' size='35'/>";

    echo "</br>";
    echo "<input class='btn' id='searchUserSubmit' type='submit' value='Filter' name='searchUser'/>";
    echo "</form>";
    /*---------------------------------------------------*/  

    $num_rec_per_page=40;
    $start_from = ($page-1) * $num_rec_per_page; 
    $isFiltered = false;

    if (isset($_POST['searchUserTxt']) && !empty(trim($_POST['searchUserTxt']))) {
        $searchTxt = preg_replace('!\s+!', ' ', trim($_POST['searchUserTxt']));
        $searchTxtArray = explode(' ', $searchTxt);

        $isFiltered = true;

        $fisrtname = $searchTxtArray[0];

        if (!isset($searchTxtArray[1])) {
            $sql = "SELECT * 
                    FROM  mdl_user 
                    WHERE mdl_user.deleted = 0
                    AND   mdl_user.suspended = 0
                    AND   mdl_user.firstname LIKE '$fisrtname%'
                    ORDER BY mdl_user.lastname"; 
        } else {
            $lastname = $searchTxtArray[1];
            $sql = "SELECT * 
                    FROM  mdl_user 
                    WHERE mdl_user.deleted = 0
                    AND   mdl_user.suspended = 0
                    AND   mdl_user.firstname LIKE '$fisrtname%'
                    AND   mdl_user.lastname LIKE '$lastname%'
                    ORDER BY mdl_user.lastname"; 
        }

        $user_records = $DB->get_records_sql($sql);     
        
    } else {
        
        $sql = "SELECT * 
                FROM  mdl_user 
                WHERE mdl_user.deleted = 0
                AND   mdl_user.suspended = 0
                ORDER BY mdl_user.firstname
                LIMIT $start_from, $num_rec_per_page";
        $user_records = $DB->get_records_sql($sql);
        
    }


    if (isset($_POST['searchNodeTxt']) && !empty(trim($_POST['searchNodeTxt']))) {
        $nodetxt = preg_replace('!\s+!', ' ', trim($_POST['searchNodeTxt']));
        $useridsinnodes = array();
        $isFiltered = true;

        $nodesql = "SELECT id
                    FROM  mdl_hierarchy_node
                    WHERE mdl_hierarchy_node.name LIKE '%$nodetxt%'
                    OR   mdl_hierarchy_node.description LIKE '%$nodetxt%'
                    ORDER BY mdl_hierarchy_node.level_id"; 
        $nodeidlist = $DB->get_records_sql($nodesql);           

        foreach ($nodeidlist as $nodeid) {
            $useridsql = "SELECT user_id
                          FROM  mdl_hierarchy_user
                          WHERE mdl_hierarchy_user.node_id = '$nodeid->id'"; 
            $useridlist = $DB->get_records_sql($useridsql);
            foreach ($useridlist as $userid) {
                $useridsinnodes[]=$userid->user_id;
            }            
        }  

        $useridsinnodes = implode("','", $useridsinnodes);  // make a string of userid such as '666','393','392','391'

        $sql = "SELECT * 
                FROM  mdl_user 
                WHERE mdl_user.id IN ('$useridsinnodes')
                ORDER BY mdl_user.firstname
                LIMIT $start_from, $num_rec_per_page";
        $user_records = $DB->get_records_sql($sql);

    }


    
    if (!empty($user_records)) {
        echo"<div id='reportDisplay'>";
        echo"<table id='myTable' class='tablesorter'>";
            echo"<thead>";
                echo"<tr>";
                    echo"<th>Firstname</th>";
                    echo"<th>Lastname</th>";
                    echo"<th>Node Name</th>";
                    echo"<th>level</th>";
                    echo"<th>Edit</th>";
                echo"</tr>";
            echo"</thead>";
            echo"<tbody>";
        foreach ($user_records as $user_record) {
            echo"<tr>";
            echo "<td><a href='hierarchy_user_edit.php?user_id=" . $user_record->id . "&page=" . $page . "'>" . $user_record->firstname . "</a></td>";
            echo "<td><a href='hierarchy_user_edit.php?user_id=" . $user_record->id . "&page=" . $page . "'>" . $user_record->lastname . "</a></td>";
            
            $user_node_record = $DB->get_record('hierarchy_user', array('user_id'=>$user_record->id));

            if ($user_node_record != false) {
                $node_record = $DB->get_record('hierarchy_node', array('id'=>$user_node_record->node_id));
                if (!empty($node_record)) {
                    echo "<td>" . $node_record->description . "</td>";
                    $level_record = $DB->get_record('hierarchy_level', array('id'=>$node_record->level_id));
                    echo "<td>" . $level_record->level . "</td>";
                } else {echo "<td></td>"; echo "<td></td>";} 
            } else {
                echo "<td></td>";
                echo "<td></td>";
            }

            echo "<td>";
                //echo "<a href='hierarchy_user_delete.php?user_id=" . $user_record->id . "'><img src='".$OUTPUT->pix_url('t/delete')."' alt='' class='iconsmall'></a>";
                echo "<a href='hierarchy_user_edit.php?user_id=" . $user_record->id . "&page=" . $page . "'><img src='".$OUTPUT->pix_url('t/edit')."' alt='' class='iconsmall'></a>";
            echo "</td>";
            echo"</tr>";

        }

        echo"</tbody>";
        echo"</table>";
        echo"</div>";
    } else {
        echo "<h5>No user matches the name!</h5>";
    }

    // Update according to hierarchy-management-plugin update
    $total_records = $DB->count_records('user', array('deleted'=>0, 'suspended'=>0));
    $total_pages = ceil($total_records / $num_rec_per_page); 

    if (!$isFiltered) {        
        echo "<a href='hierarchy_user_setup.php?page=1'>".'|<'."</a> "; // Goto 1st page  

        for ($i=1; $i<=$total_pages; $i++) { 
                echo "<a href='hierarchy_user_setup.php?page=".$i."'>".$i."</a> "; 
        }; 
        echo "<a href='hierarchy_user_setup.php?page=$total_pages'>".'>|'."</a> "; // Goto last page
    }

?>

<?php echo $OUTPUT->footer();?>   
<!-- Run jQuery functions after footer -->
<script type="text/javascript">
  $(function() {    
    $("#myTable").tablesorter({sortList:[[0,0]], widgets: ['zebra'],  headers: { 5:{sorter: false}}});
  }); 
</script>
</body>
    
    

    
    