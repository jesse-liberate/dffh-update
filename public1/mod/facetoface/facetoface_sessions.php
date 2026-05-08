<?php
require_once('../../config.php');
require_once("$CFG->libdir/completionlib.php");
require_once('lib_mindatlas.php');
require_once('lib.php');

$libPath = '/lib/mindatlas';

global $USER, $DB;

if (!isset($userid)) $userid = $USER->id;
$baseurl = new moodle_url('/mod/facetoface/facetoface_sessions.php');
require_login(0, false);
$context_system = context_system::instance();
$heading = get_string('sessiondatesreport', 'mod_facetoface');
$PAGE->set_context($context_system);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($baseurl);
$PAGE->navbar->add('home');
$PAGE->navbar->add($heading, $baseurl);
$PAGE->requires->js($libPath  . '/jquery/jquery.min.js', true);
$PAGE->requires->js($libPath  . '/jquery/ui/jquery-ui.min.js', true);
$PAGE->requires->css($libPath . '/jquery/ui/jquery-ui.min.css');

$report_type = optional_param('type', 'HTML', PARAM_RAW);
$sub = optional_param('sub', '', PARAM_TEXT);
$datefrom = optional_param('datefrom', '', PARAM_TEXT);
$dateto = optional_param('dateto', '', PARAM_TEXT);

$is_admin     = is_siteadmin($USER->id);
$is_specialty = has_capability('block/facetoface:viewreports', $context_system);
if (!$is_admin && !$is_specialty) {
  redirect('/'); 
}

//$has_capability = has_capability('block/facetoface:viewreports', context_system::instance());
//if (ma_check_hierarchy_manager($USER->id) && !is_siteadmin($USER->id) && !$has_capability) {
//  redirect('/');
//}

if ($sub == '') {
	echo $OUTPUT->header();
	echo $OUTPUT->heading($heading);
	echo "
		<form action='facetoface_sessions.php' method='POST'>
			<table>
				<tr>
					<td>
						Session Start Times&nbsp;
					</td>
					<td>
						<div class='input-prepend'>
							<span class='add-on'>From</span>
							<input type='text' name='datefrom' id='datefrom' class='datepicker' autocomplete='off'>
						</div>
						<div class='input-prepend'>
							<span class='add-on'>To</span>
							<input type='text' name='dateto' id='dateto' class='datepicker' autocomplete='off'>
						</div>
					</td>
				</tr>
				<tr>
					<td></td>
					<td><input type='submit' name='sub' value='Go' class='btn'></td>
				</tr>
			</table>
		</form>";
	echo $OUTPUT->footer();
	echo "
		<script>
		$(function() {
				    $('#dateto').datepicker({
				    	dateFormat: 'dd/mm/yy',				
				        //minDate: 0
				    });
				    $('#datefrom').datepicker({				
				        dateFormat: 'dd/mm/yy',
				        //minDate: 0
				    });
				});
		</script>
				";
	exit();
}
// $today = time();
raise_memory_limit(MEMORY_EXTRA);
$filter = '';

//$sql_condition='WHERE fsd.timestart >= '.$today;
$conditions = array();
$params 		= array();
$filters    = array();
if ($datefrom != '') {
	$conditions[] = 'fsd.timestart >= ?';
	$params[] 		= intval(strtotime(str_replace('/', '-', $datefrom)));
	$filters[]	  = 'Start time >= ' . $datefrom;
}
if ($dateto != '') {
	$conditions[] = 'fsd.timestart <= ?';
	$params[]     = intval(strtotime(str_replace('/', '-', $dateto) . ' 23:59:59'));
	$filters[]		= 'Start time <= ' . $dateto;
}
$filter = '';
if (count($filters) > 0) {
	$filter = "<p>Filter: " . implode(' AND ', $filters) . "</p>";
}

$sql_manager = '';
//if (!is_siteadmin($USER->id)) {
//	$all_employees = face_to_face_get_all_child_users_from_userid($USER->id);
//	if (!in_array($USER->id, $all_employees)) $all_employees[] = $USER->id;
//	$sql_manager = ' AND fsi.userid in (' . implode(',', $all_employees) . ')';
//}

$sql_condition = '';
if (count($conditions) > 0) {
	$sql_condition = 'WHERE ' . implode(' AND ', $conditions);
}


$sql = <<< EOT
SELECT 
	fsd.id,
	fs.id as sessionid,
	f.name as facetofacename,
	fs.capacity,
	fs.timezone,
	fsd.timestart,
	fsd.timefinish 
FROM 
	{facetoface_sessions} as fs 
	inner join {facetoface} f on f.id = fs.facetoface 
	left join {facetoface_signups} fsi on fsi.sessionid = fs.id $sql_manager
	left join {facetoface_sessions_dates} fsd on fsd.sessionid = fs.id 
	$sql_condition
GROUP BY
	fsd.id
EOT;
// print_object($sql);
// print_object($params);
// die();
$rs = $DB->get_records_sql($sql, $params);



// error_log(var_export($rs, true));
// error_log($sql);
// error_log(var_export($params, true));
// print_object($rs);
// die();

//echo "<pre>".print_r($rs,true)."</pre>";
if (!empty($rs)) {
	ob_end_clean();
	if ($report_type == 'HTML') {
		$PAGE->requires->css('/mod/facetoface/css/tablesorter.css');
		echo $OUTPUT->header();
	}
	//Get default headers
	$headers = array(
		'Learning activity name',
		'Start time', 'Finish time', 'Location', 'Timezone', 'Current group size', 'Maximum group size', 'Facilitator'
	);

	//End of headers
	$file = fopen('php://output', 'w');
	if ($report_type == 'CSV') {
		$filename = "report_" . date('Y_m_d') . ".csv";
		header("Content-type: application/csv");
		header("Content-Disposition: attachment; filename=" . $filename);
		header("Pragma: no-cache");
		header("Expires: 0");
		fputcsv($file, $headers);
	} elseif ($report_type == 'HTML') {

		echo $OUTPUT->heading($heading);
		// echo '<p>' . get_string('sorting_tip', 'block_reporting') . '</p>';
		echo '<p class="pull-right"><a href="facetoface_sessions.php?type=CSV&sub=1&datefrom=' . $datefrom . '&dateto=' . $dateto . '" class="export btn"> Export CSV</a></p>';
		echo "<p>Date of report: " . date('d/m/Y') . "</p>";
		echo $filter;
		echo '<p>Total records: ' . count($rs) . '</p>';
		echo '<table id="report" class="tablesorter">';
		echo '<thead>';
		echo '<tr>';
		foreach ($headers as $header) {
			echo '<th>' . $header . '</th>';
		}
		echo '</tr>';
		echo '</thead>';
	}

	flush();
	$location = $DB->get_record('facetoface_session_field', array('shortname' => 'location'));
	$facilitator = $DB->get_record('facetoface_session_field', array('shortname' => 'room'));
	// var_dump($profile_types);
	$LOCATIONS = array();
	$FACILITATORS = array();
	$server_tz = core_date::get_server_timezone();
  $date_format = 'd/m/Y H:i';
	foreach ($rs as $row) {
		if (!isset($LOCATIONS[$row->sessionid])) {
			$LOCATIONS[$row->sessionid] = $DB->get_field('facetoface_session_data', 'data', array('fieldid' => $location->id, 'sessionid' => $row->sessionid));
		}
		if (!isset($FACILITATORS[$row->sessionid])) {
			$FACILITATORS[$row->sessionid] = $DB->get_field('facetoface_session_data', 'data', array('fieldid' => $facilitator->id, 'sessionid' => $row->sessionid));
		}

//		$timestart_offset = ma_facetoface_offset_timestamp($row->timestart,   $server_tz, $row->timezone);
//		$timefinish_offset = ma_facetoface_offset_timestamp($row->timefinish, $server_tz, $row->timezone);		
    $timestart = ma_facetoface_get_date_format(
      $server_tz, 
      $row->timestart,
      $date_format
    );
    $timefinish = ma_facetoface_get_date_format(
      $server_tz, 
      $row->timefinish,
      $date_format
    );
		$body_contents = array();
		$body_contents[] = $row->facetofacename;
		$body_contents[] = $timestart;
		$body_contents[] = $timefinish;
		// $body_contents[] = date('d/m/Y H:i', $row->timestart);
		// $body_contents[] = date('d/m/Y H:i', $row->timefinish);
		$body_contents[] = $LOCATIONS[$row->sessionid];
		$body_contents[] = $row->timezone;
		$body_contents[] = facetoface_get_num_attendees($row->sessionid, MDL_F2F_STATUS_APPROVED);
		$body_contents[] = $row->capacity;
		$body_contents[] = $FACILITATORS[$row->sessionid];

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
		echo '</table>';
		echo '<div class="pull-right"><a href="facetoface_sessions.php?type=CSV&sub=1&datefrom=' . $datefrom . '&dateto=' . $dateto . '" class="export btn">Export CSV</a></div>';
		echo $OUTPUT->footer();
		echo '<script src="' . $CFG->wwwroot . '/mod/facetoface/css/jquery-1.12.2.min.js"></script>
				<script src="' . $CFG->wwwroot . '/mod/facetoface/css/jquery.tablesorter.min.js"></script>
				<script type="text/javascript">
       
					$(document).ready(function(){
						$("#report").tablesorter({
              headers: {
                1: { sorter: "customDate" },
                2: { sorter: "customDate" },
                7: { sorter: "text" },
              },
              widgets: [\'zebra\'],
            });

					});
          $.tablesorter.addParser({
            id: \'customDate\',
            is: function(s) {
            // return s.match(new RegExp(/^[A-Za-z]{3,10}\.? [0-9]{1,2}, [0-9]{4}|\'?[0-9]{2}$/));
              return false;
            },
            format: function(s) {
              var date = s.split(\'/\');
              console.log(date);
              var part2 = date[2].split(" ");
              var formatted_date = part2[0] + "-" + date[1] + "-" + date[0] + "T" + part2[1] + ":00";
              console.log(formatted_date);
              return $.tablesorter.formatFloat(new Date(formatted_date).getTime());
              //return $.tablesorter.formatFloat(new Date(date[2], date[1], date[0]).getTime());
            },
            type: \'numeric\'
          });           
				</script>';
	}
} else {
	redirect($baseurl, 'Could not find any result', '', core\output\notification::NOTIFY_ERROR);
}
 