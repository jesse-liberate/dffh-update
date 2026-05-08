<?php
require_once('../../config.php');
require_once('lib.php');
require_login(0, false);
if(isset($_GET['id'])){
	$id=$_GET['id'];
	$records = $DB->get_records('coach_resource',array('entry'=>$id,'creater'=>$USER->id));
	$result = array();
	if(!empty($records)){
		$count=1;
		$attachment=0;
		foreach ($records as $row) {
			if($count==1){
				$result['entry'] = $row->entry;
				$result['name'] = $row->name;
				$result['description'] = $row->description;
				$result['link'] = $row->link;
				$result['timecreated'] = date('d/m/Y H:i',$row->timecreated);
			}
			if($row->contenthash!=NULL){
				$attachment++;
				$result['attachment'.$count] = "&t=".time()."&file=download.".$row->extension;
				$result['attachment_fname'.$count] = $row->filename;
			}
			$result['id'.$count] = $row->id;
			$count++;
		}
		$result['num_attached'] = $attachment;
	}
	echo json_encode($result);
}
?>