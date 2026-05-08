<?php
// --------------------------------------------------------------------------------------------------------------------config
	require_once('../../../config.php');
	// require_once($CFG->dirroot .'/blocks/reporting/report/smarty/Smarty.class.php');
	require_once('lib.php');
	// require_once($CFG->libdir.'/adminlib.php');
	$PAGE->requires->js('/lib/mindatlas/jquery/jquery.min.js', true);
	$PAGE->requires->js('/lib/mindatlas/jquery/ui/jquery-ui.min.js', true); 
	$PAGE->requires->css('/lib/mindatlas/jquery/ui/jquery-ui.min.css');

	global $USER, $DB;

	require_login(0, false);
	// Only admin can access this page
	if(!is_siteadmin($USER->id)) {
		echo get_string('notallowtoaccess','block_reporting');
		exit();
	}
	$context_system = context_system::instance();
	$PAGE->set_context($context_system);

	$PAGE->set_pagelayout('reports');
	$PAGE->set_title($SITE->fullname);
	$PAGE->set_heading(get_string('vendorsetting','block_reporting'));
	$PAGE->set_url('/blocks/reporting/report/vendor_add.php');
	//$PAGE->navbar->ignore_active();
	$PAGE->navbar->add("Vendors", new moodle_url('/blocks/reporting/report/vendor_add.php'));

$back = "<script type='text/javascript'>window.location='vendor_setting.php'</script>";

$html="";
$id="";
$vendorname="";
$username="";

if(isset($_GET['id'])) $id = $_GET['id'];
if(isset($_POST['sub'])){
	$id = $_POST['id'];
	$vendorname=$_POST['name'];
	$username=$_POST['uname'];
	$sql = "select id from mdl_user where concat(firstname, ' ', lastname) LIKE '%".$username."%'";
	$userid = $DB->get_field_sql($sql,array());
	if($userid!=false){
		if($id==""){// Insert new vendor
			$new = new stdClass();
			$new->vendorname = $vendorname;
			$new->userid = $userid;
			$new->courselist = get_vendor_course_list($userid);
			$new->timecreated = time();
			$DB->insert_record('report_vendor_user',$new);
		}else{// Update the vendor name
			$update = new stdClass();
			$update->id = $id;
			$update->vendorname = $vendorname;
			$update->userid = $userid;
			$update->courselist = get_vendor_course_list($userid);
			$DB->update_record('report_vendor_user',$update);
		}
	} else $html.=get_string('couldnotfinduseras','block_reporting')."<strong>".$username."</strong>";
	echo $back;
}

if($id!="") {
	$record = $DB->get_record('report_vendor_user',array('id'=>$id));
	$user = $DB->get_record('user',array('id'=>$record->userid));
	$username = $user->firstname." ".$user->lastname;
	$vendorname = $record->vendorname;
}

$html.="<form action='vendor_add.php' method='POST'>";
$html.=get_string('vendorname','block_reporting');
$html.="<input type='text' size='35' name='name' id='name' value='".$vendorname."'> <br>";

$html.=get_string('username','block_reporting');
$html.="<input type='text' size='35' name='uname' id='uname' value='".$username."'> <br>";

$html.="<input type='submit' name='sub' value='Save changes'> ";
$html.="<input type='hidden' name='id' value='".$id."'>";
$html.="<a href='#' class='btn' onclick='window.history.back();'>Back</a>";
$html.="</form>";

echo $OUTPUT->header();
// Start showing the form
echo  get_string('vendorsheader','block_reporting');

echo $html;

echo $OUTPUT->footer();
?>
<script>
	//Note: jQuery UI - Only use Datepicker and Autocomplete Libraries
$(function(){
	var src = "get_username.php";
	$('head').append('<link rel=\"stylesheet\" href=\"http://code.jquery.com/ui/1.10.2/themes/smoothness/jquery-ui.css\" />');
	$('head').append('<style>.ui-autocomplete { max-height: 100px; overflow-y: auto; overflow-x: hidden; padding-right: 20px; } * html .ui-autocomplete { height: 100px; }</style>');
	$("#uname").autocomplete({ source: src }); // /blocks/reporting/
	$("#name").autocomplete({ source: "get_vendor_name.php" }); // /blocks/reporting/
});
</script>
<style>
.label_text{
    display: inline-block;
    width: 120px;
    font-weight: bold;
}
</style>