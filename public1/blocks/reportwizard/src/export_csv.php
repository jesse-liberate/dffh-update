<?php
require_once('../../../config.php');
require_once('../renderer.php');
require_once('classes/report.php');
require_once('classes/reports_helper.php');
require_once('lib.php');

global $DB;

require_login();
$context_system = context_system::instance();
$PAGE->set_context($context_system);

$report_id = required_param('id', PARAM_INT);
$report = null;
$reports_helper = new block_reportwizard_reports_helper();

if ( $DB->record_exists('report_wzd_report', array('id'=>$report_id)) ) {
	$report = new block_reportwizard_report( $DB->get_record('report_wzd_report', array('id' => $report_id)) );
}else{
	redirect('myreports.php', 'Invalid URL. No report found.', null, \core\output\notification::NOTIFY_ERROR);
}


$content = $reports_helper->report_csv_content($report);

// echo '<pre>';
// var_dump($content);
// echo '</pre>';
// die();

$headers = $content->headers;
$data_list = $content->data_list;


$filename = 'report_'.date('Y_m_d').'.csv';
$line = array();

$file="";
$file.=implode(",",$headers);
$file.="\n";

$line = array();
if (is_array($data_list)) {
	foreach ($data_list as $data) {
		foreach ($headers as $header) {
			if (isset($data[$header])) {
				$line[] = $data[$header];
			} else {
				$line[] = '';
			}
		}
		$file.=implode(",", $line);
		$file.="\n";
		unset($line);
	}
} else { // No reports found
	$file="There is no data";
}

header('Content-Type: application/csv'); //Outputting the file as a csv file
header('Content-Disposition: attachment; filename='.$filename); //Defining the name of the file and suggesting the browser to offer a 'Save to disk ' option
header('Pragma: no-cache');
header("Expires: 0");

echo $file;

?>