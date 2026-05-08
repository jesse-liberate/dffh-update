<?php
// --------------------------------------------------------------------------------------------------------------------config
require_once('../../../config.php');
global $USER, $DB;
$users_ids = $USER->id;

if(!isset($userid)) $userid = $USER->id;
require_login(0, false);

$context_system = context_system::instance();
$PAGE->set_context($context_system);

$PAGE->set_pagelayout('reports');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading("Delete");
$PAGE->set_url("/blocks/reporting/report/delete_vendor.php");

if(!is_siteadmin($USER->id)) { echo get_string('notallowtoaccess','block_reporting'); return ;}
if(isset($_POST['sub'])){// Confirm for delete the resources
	$id = $_POST['id'];
	//Delete all resource tags record
	$DB->delete_records('report_vendor_user',array('id'=>$id));
	echo "<script type='text/javascript'>window.location='vendor_setting.php'</script>";
}

if(!isset($_GET['id'])) return;
else{
	$id = $_GET['id'];
	$row = $DB->get_record('report_vendor_user',array('id'=>$id));
	$username = $DB->get_record('user',array('id'=>$row->userid));
	$html="";
	$html.="<form action='delete_vendor.php' method='POST'>";
	$html.="<table width='50%' align='center' style='border-collapse: separate;border-spacing: 20px;'>";
	$html.="<tr><td align='center'>Do you want to continue delete the vendor <strong>".$row->vendorname."</strong> of User <strong> ".$username->firstname." ".$username->lastname." </strong>?</td></tr>";
	$html.="<tr><td align='center'>
	<input type='submit' name='sub' value='Confirm'>
	<a href='vendor_setting.php' class='btn'> Cancel </a>
	</td></tr>";
	$html.="</table>";
	$html.="<input type='hidden' name='id' value='".$id."'>";
	$html.="</form>";

	echo $OUTPUT->header();
	echo $html;
	echo $OUTPUT->footer();
}
?>
