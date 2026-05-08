<?php
require_once('../../../config.php');
require_once('lib.php');
global $DB, $OUTPUT;
$node_id="";
$path = "http:".$CFG->wwwroot."/admin/tool/hierarchy/";
if(isset($_GET['node_id'])) {
	$node_id = $_GET['node_id'];
	$res = $_GET['res'];
	$str_sort = "";
	if(isset($_GET['sort'])){
		$dir = $_GET['dir'];
		$sort = $_GET['sort'];
		$str_sort = " order by $sort $dir";
	}
	$default = " order by firstname";
	$sql="select u.id,u.firstname,u.lastname,hn.description as nodedescription from mdl_user u join mdl_hierarchy_user hu on u.id=hu.user_id
			left join mdl_hierarchy_node hn on hn.id=hu.node_id ";
	if($res==1){
		$childs = findchildrennodes($node_id);
		if(count($childs)<1) $str=$node_id;
		else $str = join(',',$childs).",".$node_id;

		$sql .= " where hu.node_id in ($str)";
	} else  $sql .= " where hu.node_id=".$node_id;
	 $sql .= ($str_sort!="") ? $str_sort : $default;
	$rs = $DB->get_records_sql($sql,array());
	$coachdata = array();
	$coaches = $DB->get_records('hierarchy_coach',array('node_id' =>$node_id));
		if($coaches){
			$coachtable = new html_table();   
			$arr_header=array('Coach name','');
			foreach ($arr_header as $r) {
				  $coachtable->head[] = $r;
			}
			$coachtable->attributes['class'] = 'coach-table';
			$coachtable->id = 'coach';
			foreach($coaches as $coach){
				$user = $DB->get_record('user',array('id' => $coach->user_id));
				$name ="<a href='".$CFG->wwwroot."/user/profile.php?id=".$user->id."'>".fullname($user)."</a>";
				$link = '<a class="btn btn-danger" href="assign_coach.php?r=1&u='.$user->id.'&n='.$node_id.'"> Remove as coach </a>';
				$coachdata[] = array($name,$link);
			}
			$coachtable->data = $coachdata;
			echo '<h4 class="mt-5"> Agency Coach </h4>';
			echo html_writer::table($coachtable);
		}
	if(!empty($rs)){
		$table = new html_table();   
			$arr_header=array('idcolumn','firstname','lastname','nodedescription','coach');
		foreach ($arr_header as $r) {
			$columnicon="";
			if(isset($dir)&&($sort==$r)){
				$dir2 = ($dir=='ASC') ? "DESC":"ASC"; // swap the sorting next time
				$columnicon = "<img class='iconsort' src='themes/blue/".($dir2=="ASC" ? 'desc':'asc').".gif' alt=''/>";
			}else $dir2="ASC";
		  	$table->head[] = "<a href='visualization.php?n=$node_id&sort=$r&dir=$dir2'>".get_string($r,'tool_hierarchy')."</a>".$columnicon;
		}
		$table->attributes['class'] = 'tablesorter node-table';
		$table->id = 'report';
		// Get all data
		$data = array();
		
		$hascoach = 0;
		foreach($rs as $row){
			$record = $DB->get_record('hierarchy_coach',array('user_id'=>$row->id,'node_id'=> $node_id));
			if($record){
				$hascoach = 1;	
			}
		}
		
		$noderecord = $DB->get_record('hierarchy_node', array('id' => $node_id));
		$hascoachrecord = $DB->get_record('hierarchy_coach',array('node_id'=> $node_id));
		foreach ($rs as $row) {
		
			if ($noderecord->parent_node_id == 1) {
				if($hascoachrecord->userid == $row->id){
					$coach = '<a class="btn btn-danger" href="assign_coach.php?r=1&u='.$row->id.'&n='.$node_id.'"> Remove as coach </a>';
					$id = "<input class='is-coach' type='checkbox' name='".$row->firstname."' id='".$row->id."'>";
					
				}else{
					if($hascoachrecord){
						$coach = '<span class="btn btn-secondary">Agency already has a coach assigned</span>';
					}else {
						$coach = '<a class="btn btn-primary" href="assign_coach.php?u='.$row->id.'&n='.$node_id.'"> Assign as coach </as>';
					}
					
					$id = "<input class='coach-test' type='checkbox' name='".$row->firstname."' id='".$row->id."'>";
				}
				$firstname ="<a href='".$CFG->wwwroot."/user/profile.php?id=".$row->id."'>".$row->firstname."</a>";
				$lastname ="<a href='".$CFG->wwwroot."/user/profile.php?id=".$row->id."'>".$row->lastname."</a>";
			}else{
				if($hascoachrecord->userid == $row->id){
					$coach = '<a class="btn btn-danger" href="assign_coach.php?r=1&u='.$row->id.'&n='.$node_id.'"> Remove as coach </a>';
					$id = "<input class='is-coach' type='checkbox' name='".$row->firstname."' id='".$row->id."'>";
					
				}else{
					if($hascoachrecord){
						$coach = '<span class="btn btn-secondary">Agency already has a coach assigned</span>';
					}
					
					$id = "<input class='coach-test' type='checkbox' name='".$row->firstname."' id='".$row->id."'>";
				}
				$coach = '<a class="btn btn-secondary" href="assign_coach.php?r=1&u='.$row->id.'&n='.$node_id.'">Can not assign coach to this node</a>';
				$firstname ="<a href='".$CFG->wwwroot."/user/profile.php?id=".$row->id."'>".$row->firstname."</a>";
				$lastname ="<a href='".$CFG->wwwroot."/user/profile.php?id=".$row->id."'>".$row->lastname."</a>";
			}
				$data[] = array($id,$firstname,$lastname,$row->nodedescription,$coach);
			
			
		}
		$table->data  = $data;
		echo '<h4 class="mt-5"> Agency Users </h4>';
		echo html_writer::table($table);
	}
}
?>


