<?php
require_once('../../../config.php');
require_once('lib.php');
global $DB;

$u="";
$n="";
if(isset($_GET['u'])){
    if($_GET['r'] == 1){
        $user=$_GET['u'];
        $node=$_GET['n'];
        $msg = "<hr>";
            $record = $DB->get_record('hierarchy_coach',array('user_id'=>$user,'node_id'=> $node));
            if(!empty($record)){
                $newcoach = new stdClass();
                $newcoach->user_id = $user;
                $newcoach->node_id = $node;
                $DB->delete_records('hierarchy_coach',array('user_id'=>$user)); 
            }
        $node_name = $DB->get_field('hierarchy_node','name',array('id'=>$node));
        $msg.= get_string('removemsg','tool_hierarchy');
        $msg.=$node_name."<hr>";
        echo "<img src='css/loading.gif' height='70px'/><br>";
        $_SESSION['msg_update'] = $msg;
        echo "<script type='text/javascript'>window.location='visualization.php?update=1'</script>";
    }else{
        $users=$_GET['u'];
        $node=$_GET['n'];
            $record = $DB->get_record('hierarchy_coach',array('user_id'=>$users,'node_id'=> $node));
            if(empty($record)){
                $newcoach = new stdClass();
                $newcoach->user_id = $users;
                $newcoach->node_id = $node;
                $DB->insert_record('hierarchy_coach',$newcoach);
            }
        $node_name = $DB->get_field('hierarchy_node','name',array('id'=>$node));
        $msg.= get_string('coachmsg','tool_hierarchy');
        $msg.=$node_name."<hr>";
        echo "<img src='css/loading.gif' height='70px'/><br>";
        $_SESSION['msg_update'] = $msg;
        echo "<script type='text/javascript'>window.location='visualization.php?update=1'</script>";
    }
}
?>
