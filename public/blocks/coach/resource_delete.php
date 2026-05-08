<?php
require_once('../../config.php');
require_once('lib.php');
require_login(0, false);
$html="";
if(isset($_GET['id'])){
	$id=$_GET['id'];
	if($DB->record_exists('coach_resource',array('entry'=>$id,'creater'=>$USER->id))){
		$records = $DB->get_records('coach_resource',array('entry'=>$id,'creater'=>$USER->id));
		if(!empty($records)){
			foreach ($records as $row) {
				//Delete all resources in the disk
				if($row->contenthash!=NULL){
					$folder = substr($row->contenthash,0,3);
					$file = $CFG->dataroot."/resources/".$folder."/".$row->contenthash;
					if(file_exists($file)) unlink($file);
				}
			}
		}
		if($DB->delete_records('coach_resource',array('entry'=>$id,'creater'=>$USER->id))){
			$html.=get_string('resource:delete:success','block_coach');
		}
	}else $html.=get_string('resource:delete:error','block_coach'); 
}
echo $html;
?>