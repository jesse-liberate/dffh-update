<?php
	require_once('../../../config.php');
	require_once("$CFG->libdir/completionlib.php");
	require_once('lib.php');
	global $USER, $DB;
	$users_ids = $USER->id;
	$DBH = new PDO("mysql:host=$CFG->dbhost;dbname=$CFG->dbname", $CFG->dbuser, $CFG->dbpass);

	if(!isset($userid)) $userid = $USER->id;
	$baseurl = new moodle_url('/blocks/reporting/report/users.php');
	require_login(0, false);
	$context_system = context_system::instance();
	$heading = get_string('users_reports', 'block_reporting');
	$PAGE->set_context($context_system);
	$PAGE->set_pagelayout('reports');
	$PAGE->set_title($SITE->fullname);
	$PAGE->set_heading($heading);
	$PAGE->set_url($baseurl);
	$PAGE->navbar->add($heading, $baseurl);

	$report_type = optional_param('type','HTML',PARAM_RAW);

	if(!has_capability('block/reporting:viewreports', $context_system)){
		redirect(new moodle_url('/'));
	}

// --------------------------------------------------------------------------------------------------------------------header
	if($report_type == 'HTML') {
		$PAGE->requires->css('/blocks/reporting/report/css/tablesorter.css?v=' . $PAGE->requires->get_jsrev());
		$PAGE->requires->css('/blocks/reporting/report/css/chosen.css?v=' . $PAGE->requires->get_jsrev());
		$PAGE->requires->css('/blocks/reporting/report/css/reporting.css?v' . $PAGE->requires->get_jsrev());

	  	echo $OUTPUT->header();
	}
// -----------------------------------------------------------------------------------get userprofile filter and grade filter

$DBH->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
raise_memory_limit(MEMORY_EXTRA);

	$default_fields = array('username'=>'User name', 'firstname'=>'First name','lastname'=>'Last name','email'=>'Email');
	$exclude_user_fields = array();

	$headers_order = [];
	$headers_order = array_map('strtolower', $headers_order);
	$columns_order = [];

	$rs = $DB->get_records('user',array('deleted'=>0,'suspended'=>0));
	if(!empty($rs)){
		ob_end_clean();
		//Get default headers
		$headers = array();
		foreach ($default_fields as $key=>$name) {
			$headers [] = $name;
		}
		//User profile headers
		$rs2 = $DB->get_records('user_info_field',array(),'sortorder ASC');
		$profile_keys = array();
		$profile_types = array();
		foreach ($rs2 as $row2) {
			if(in_array($row2->shortname,$exclude_user_fields)) continue;
			$profile_keys [] = $row2->id;
			$profile_types [$row2->id] = $row2->datatype;
			$headers [] = $row2->name;
		}

		// build columns_order and re-order the headers
		foreach ($headers as $index => $header) {
			$columns_order[$index] = array_search(strtolower($header), $headers_order);
			if ($columns_order[$index] === false) {
				$columns_order[$index] = count($headers) + $index + 1;
			}
		}
		$new_headers = [];
		foreach ($headers as $index => $header) {
			$new_headers[$columns_order[$index]] = $header;
		}
		ksort($new_headers);
		$headers = $new_headers;

		//End of headers
		$file = fopen('php://output', 'w');
		if ($report_type == 'CSV') {
			$filename = "report_".date('Y_m_d').".csv";
			header("Content-type: application/csv");
			header("Content-Disposition: attachment; filename=".$filename);
			header("Pragma: no-cache");
			header("Expires: 0");
			fputcsv($file, $headers);
		} elseif ($report_type == 'HTML') {

			echo $OUTPUT->heading($heading);
			// echo '<p>' . get_string('sorting_tip', 'block_reporting') . '</p>';
			echo '<p class="text-right"><a href="users.php?type=CSV" class="btn-custom bg-color-brand-3 hover-bg-color-brand-1 text-white"> Export CSV</a></p>';
			echo "<p>Date of report: ".date('d/m/Y')."</p>";
			echo '<div class="table-wrapper"><table id="report" class="tablesorter">';
			echo '<thead>';
			echo '<tr>';
			foreach ($headers as $header) {
				echo '<th>' . $header . '</th>';
			}
			echo '</tr>';
			echo '</thead>';
		}

		flush();
		// var_dump($profile_types);
		foreach ($rs as $row) {
			if($row->id==1) continue;
			$body_contents = array();
			foreach ($default_fields as $key=>$name) {
				if(in_array($key, array('firstname', 'lastname') ) ){
					if ($report_type == 'HTML') {
						$body_contents[] = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$row->id.'">'.$row->$key.'</a>';
					} else if ($report_type == 'CSV') {
						$body_contents[] = $row->$key;
					}
				} else {
					$body_contents [] = $row->$key;
				}
			}
			$profile_data = array();
			$row_data = $DB->get_records('user_info_data',array('userid'=>$row->id));
			foreach ($row_data as $row_data) {
				if(!isset($profile_types[$row_data->fieldid])) continue;
				switch ($profile_types[$row_data->fieldid]) {
					case 'datetime':
						$profile_data [$row_data->fieldid] = ($row_data->data==0||$row_data->data==""||is_null($row_data->data))?"":date('d/m/Y',$row_data->data);
						break;
					case 'checkbox':
						$profile_data [$row_data->fieldid] = ($row_data->data==1) ? "Yes" : "No";
						break;
					default: 
						$profile_data [$row_data->fieldid] = ($row_data->data==""||is_null($row_data->data)) ? "" : $row_data->data;
						break;
				}
				
			}
			foreach ($profile_keys as $profileid) {
				$body_contents [] = isset($profile_data[$profileid]) ? $profile_data[$profileid] : "";
			}

			// re-order body_contents
			$new_body_contents = [];
			foreach ($body_contents as $index => $body_content) {
				$new_body_contents[$columns_order[$index]] = $body_content;
			}
			ksort($new_body_contents);
			$body_contents = $new_body_contents;

			//
			if ($report_type == 'HTML') {
				echo '<tr>';
				foreach ($body_contents as $content) {
					echo '<td style="border-width:1px;">' . $content . '</td>';
				}
				echo '</tr>';
			} elseif ($report_type == 'CSV') {
				// $body_contents[0] = strip_tags($body_contents[0]);
				fputcsv($file, $body_contents);
			}
		}
		if ($report_type == 'HTML') {
			echo '</table></div>';
			echo '<div class="text-right"><a href="users.php?type=CSV" class="btn-custom bg-color-brand-3 hover-bg-color-brand-1 text-white">Export CSV</a></div>';
			echo $OUTPUT->footer();
			echo '<script src="js/jquery-1.12.2.min.js"></script>
				<script src="js/jquery.tablesorter.min.js"></script>
				<script>
					$(document).ready(function(){
						$("#report").tablesorter({
						    headers:
						        {
						            6: { sorter: "customDate" },
						            5: { sorter: "customDate" },
						        },
						    widgets: ["zebra"]
						});
					});
					$.tablesorter.addParser({
					        id: "customDate",
					        is: function(s) {
					        // return s.match(new RegExp(/^[A-Za-z]{3,10}\.? [0-9]{1,2}, [0-9]{4}|\'?[0-9]{2}$/));
					            return false;
					        },
					        format: function(s) {
					            var date = s.split("/");
					            return $.tablesorter.formatFloat(new Date(date[2], date[1], date[0]).getTime());
					        },
					        type: "numeric"
					});
				</script>';
			echo '<style>
					div.table-wrapper{
						width: 100%;
						overflow: auto;
					}
				</style>';
		}
	}
