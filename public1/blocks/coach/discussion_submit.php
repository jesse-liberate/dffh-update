<?php
require_once(__DIR__.'/../../config.php');
require_once('lib.php');
global $USER,$DB;
require_login(0, false);
if(!isset($USER)){
	echo get_string('notallowtoaccess','block_coach');
	die();	
}
if(isset($_POST) && !empty($_POST) && isset($_POST['message'])){
    //Post comment with text and a image
    $coachid = $_POST['c'];
    if($coachid==""){
        //Current user is a coachee, we need to find their coach
        $c_id = $DB->get_field('coachees','coachid',array('coacheeid'=>$USER->id));
        $discuss = new stdClass();
        $discuss->content = $_POST['message'];
        $discuss->coachid = $c_id;
        $discuss->coacheeid = $USER->id;
        $discuss->timecreated = time();
        $DB->insert_record('coach_discuss',$discuss);
    }else{
        //Current user is a coach
        $discuss = new stdClass();
        $discuss->content = $_POST['message'];
        $discuss->coachid = $USER->id;
        $discuss->coacheeid = $coachid;
        $discuss->timecreated = time();
        $DB->insert_record('coach_discuss',$discuss);
    }
}
?>