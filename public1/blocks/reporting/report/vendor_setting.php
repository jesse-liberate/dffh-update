<?php
// --------------------------------------------------------------------------------------------------------------------config
	require_once('../../../config.php');
	// require_once($CFG->dirroot .'/blocks/reporting/report/smarty/Smarty.class.php');
	require_once('lib.php');
	// require_once($CFG->libdir.'/adminlib.php');

	global $USER, $DB;
	$userid = $USER->id;
	// $DBH = new PDO("mysql:host=$CFG->dbhost;dbname=$CFG->dbname", $CFG->dbuser, $CFG->dbpass);

	if(!isset($userid)) $userid = $USER->id;
	require_login(0, false);
	// Only admin can access this page
	if(!is_siteadmin($userid)) {
		echo get_string('notallowtoaccess','block_reporting');
		exit();
	}
	$context_system = context_system::instance();
	$PAGE->set_context($context_system);

	$PAGE->set_pagelayout('reports');
	$PAGE->set_title($SITE->fullname);
	$PAGE->set_heading(get_string('vendorsetting','block_reporting'));
	$PAGE->set_url('/blocks/reporting/report/vendor_setting.php');
	//$PAGE->navbar->ignore_active();
	$PAGE->navbar->add("Vendor setting", new moodle_url('/blocks/reporting/report/vendor_setting.php'));

$html="";
$html.="<script type='text/javascript' src='js/jquery-latest.js'></script>";
$html.="<script type='text/javascript' src='js/jquery.tablesorter.js'></script>";
$html.="<link rel='stylesheet' types='text/css' href='css/style.css'/>";
$html.="<table class='tablesorter' id='report'>";
$html.="<thead><tr>";
$html.="<th>Vendor name</th>";
$html.="<th>Users</th>";
$html.="<th>User email</th>";
$html.="<th>Enrolled courses</th>";
$html.="<th>Action</th>";
$html.="</tr></thead>";
$html.="<tbody>";

$rs = $DB->get_records('report_vendor_user',array(),'vendorname ASC');
if($rs){
	foreach ($rs as $row) {
		$html.="<tr>";
		$html.="<td>".$row->vendorname."</td>";
			$username = get_vendor_username($row->userid);		
		$html.="<td>".$username['username']."</td>";
		$html.="<td>".$username['email']."</td>";
			$courselist = get_vendor_course_list($row->userid);
		$html.="<td>".$courselist."</td>";
			$action="<a href='delete_vendor?id=".$row->id."'><img src='css/delete.svg'></a>";
			$action.=" <a href='vendor_add?id=".$row->id."'><img src='css/edit.svg'></a>";
		$html.="<td>".$action."</td>";
		$html.="</tr>";
	}
}

$html.="</tbody>";
$html.="</table>";

$html.="<a href='vendor_add.php' class='btn'>Add vendor</a>";

echo $OUTPUT->header();
// Start showing the form
echo  get_string('vendorsuserheader','block_reporting');

echo $html;

echo $OUTPUT->footer();
?>
<script>
$(document).ready(function () {
    $("#report").tablesorter({
	// headers:
 //    {
 //        4: { sorter: "customDate" },
 //    },
    widgets: ['zebra']
    });
});
</script>