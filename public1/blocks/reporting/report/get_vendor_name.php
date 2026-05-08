<?php
require_once('../../../config.php');
require_once('lib.php');

 $term = $_REQUEST["term"];
if (!$term) return;
$result = array();

$find_all_vendor = "SELECT vendorname FROM mdl_report_vendor_user 
 WHERE vendorname like '%".$term."%' GROUP BY vendorname order by vendorname ASC";

$rows = $DB->get_records_sql($find_all_vendor);

if ($rows != false) {
	foreach ($rows as $row) {
		$result[]  = $row->vendorname;
	}
	
}

echo json_encode($result);

?>