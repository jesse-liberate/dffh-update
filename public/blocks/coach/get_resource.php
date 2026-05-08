<?php
require_once('../../config.php');
require_once('lib.php');
require_login(0, false);
$html="";
if(isset($_GET['id'])){
	$id=$_GET['id'];
	$coacheeid="";
	if(isset($_SESSION['coacheeid'])) $coacheeid=$_SESSION['coacheeid'];
	if($coacheeid!=""){
		$resources=$DB->get_records('coach_resource',array('entry'=>$id,'creater'=>$coacheeid));
		if(!empty($resources)){
			$html.=view_coach_resource_html($resources);
		}else return $html;
	}else{
		$coachid = $DB->get_field('coachees','coachid',array('coacheeid'=>$USER->id));
		$resources=$DB->get_records('coach_resource',array('entry'=>$id,'creater'=>$coachid));
		if(!empty($resources)){
			$html.=view_coach_resource_html($resources);
		}else return $html;
	}
}
echo $html;
?>