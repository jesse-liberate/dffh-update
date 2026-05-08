<?php
require_once('../../../config.php');
require_once('lib.php');
global $DB;

$u="";
$n="";
if(isset($_GET['u'])){
	$u=$_GET['u'];
	$n=$_GET['n'];

	// Move all users: $u into node: $n
	$arr_users = explode(',', $u);
	$msg = "<hr>";
	$i=1;
	// Check if Position Code and Report To fields are existing:
	// It will update these fields into database
	$is_update_profile_fields = false;

	$hierarchy_fields = get_hierarchy_fields();
	$PositionCode_field_id = $hierarchy_fields['positioncode'];
	$ReportTo_field_id = $hierarchy_fields['reportto'];
	if($PositionCode_field_id!="" && $ReportTo_field_id!="") $is_update_profile_fields = true;
	foreach ($arr_users as $user) {
		$record = $DB->get_record('hierarchy_user',array('user_id'=>$user));
		if(!empty($record)){
			if($record->node_id!=$n){
				// Update the node_id of each user_id in hiearchy_user table
				$update_record = new stdClass();
				$update_record->id = $record->id;
				$update_record->node_id = $n;	// New node
				$update_record->user_id = $user; // For selected user
				if($DB->update_record('hierarchy_user',$update_record)){
					$rs = $DB->get_record('user',array('id'=>$user));
					$msg .= $i.". ".$rs->firstname. " ".$rs->lastname." <br>";
					$i++;
					if($is_update_profile_fields){
						$current_node = $DB->get_record('hierarchy_node',array('id'=>$n));
						$parent_name="";
						if($current_node->parent_node_id!=NULL){
							$parent_name = $DB->get_field('hierarchy_node','name',array('id'=>$current_node->parent_node_id));
						}
						//UPDATE POSITION
						$user_position = $DB->get_record('user_info_data',array('fieldid'=>$PositionCode_field_id,'userid'=>$user));
						if(!empty($user_position)){
							$user_position->data = $current_node->name;
							$DB->update_record('user_info_data',$user_position);
						}else{
							$new_position_data = new stdClass();
							$new_position_data->userid = $user;
							$new_position_data->fieldid = $PositionCode_field_id;
							$new_position_data->data = $current_node->name;
							$DB->insert_record('user_info_data',$new_position_data);
						}
						// UPDATE REPORT TO
						$user_reportto = $DB->get_record('user_info_data',array('fieldid'=>$ReportTo_field_id,'userid'=>$user));
						if(!empty($user_reportto)){
							$user_reportto->data = $parent_name;
							$DB->update_record('user_info_data',$user_reportto);
						}else{
							$new_reportto_data = new stdClass();
							$new_reportto_data->userid = $user;
							$new_reportto_data->fieldid = $ReportTo_field_id;
							$new_reportto_data->data = $parent_name;
							$DB->insert_record('user_info_data',$new_reportto_data);
						}
					}
				}
			}
		}
	}
	$node_name = $DB->get_field('hierarchy_node','name',array('id'=>$n));
	$msg.= get_string('moveusermsg','tool_hierarchy');
	$msg.=$node_name."<hr>";
	echo "<img src='css/loading.gif' height='70px'/><br>";
	// echo $msg;
	$_SESSION['msg_update'] = $msg;
	$_SESSION['new_node'] = $n;
	// For debugging:
	 // $rs = $DB->get_records_sql("select * from mdl_hierarchy_user where user_id in($u)");
	 // foreach ($rs as $row) {
	 // 	echo "<br> Move user : ".$row->user_id." from node ".$row->node_id." to ".$n;
	 // }
	echo "<script type='text/javascript'>window.location='visualization.php?update=1'</script>";
}
?>
