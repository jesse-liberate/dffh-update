<?php
	require_once('../../config.php');
	require_once("$CFG->libdir/completionlib.php");
	require_once('lib_mindatlas.php');
	global $USER, $DB;

	if(!isset($userid)) $userid = $USER->id;
	$baseurl = new moodle_url('/mod/facetoface/interest_report.php');
	require_login(0, false);
  if (!is_siteadmin($USER->id)) {
    redirect('/');
  }
  
	$context_system = context_system::instance();
	$heading = get_string('expressionsofinterest','mod_facetoface');
	$PAGE->set_context($context_system);
	$PAGE->set_pagelayout('standard');
	$PAGE->set_title($SITE->fullname);
	$PAGE->set_heading($SITE->fullname);
	$PAGE->set_url($baseurl);
	$PAGE->navbar->add('home');
	$PAGE->navbar->add($heading, $baseurl);
	

	$report_type = optional_param('type','HTML',PARAM_RAW);
	$sub = optional_param('sub','',PARAM_TEXT);
	$facetofaceid = optional_param('faceid',0,PARAM_INT);

//	$is_admin = is_siteadmin($USER->id);
//    $is_manager = ($is_admin) ? true : ma_check_hierarchy_manager($USER->id);
//    if(!$is_manager){
//    	redirect(new moodle_url('/'));
//    }

	if($sub=='' || !$facetofaceid){
		$PAGE->requires->js('/lib/mindatlas/jquery/jquery.min.js', true);
		$PAGE->requires->js('/mod/facetoface/css/chosen.js', true);
		$PAGE->requires->css('/mod/facetoface/css/chosen.css');

		$facetoface_options = ma_facetoface_coursef2f_options();

		echo $OUTPUT->header();
		echo $OUTPUT->heading($heading);
		if(!$facetofaceid && $sub!='') echo get_string('error:selectfacetoface','mod_facetoface');
		echo "<form action='interest_report.php' method='POST'>
			<table>
				<tr><th style='padding-right:10px; padding-bottom:10px; min-width: 102px;'>Face to face</th>
					<td><select name='faceid' class='chzn-select'>".$facetoface_options."</select></td>
				</tr>
				<tr><th></th>
					<td><input type='submit' name='sub' value='Go' class='btn btn-primary'></td>
				</tr>
			</table>
		</form>";
		echo "<script>
				$(document).ready(function(){
					$('.chzn-select').chosen({search_contains: true});
				})
			</script>";
		echo $OUTPUT->footer();
		exit();
	}
	$facetoface = $DB->get_record('facetoface',array('id'=>$facetofaceid));

	raise_memory_limit(MEMORY_EXTRA);

	$default_fields = array('firstname'=>'First name','lastname'=>'Last name','email'=>'Email','firstnamephonetic'=>'Preferred first name');
	// $exclude_user_fields = array('PositionCode','Datefield','managersemail','QUAL','ReportTo','DML_FROMPAYROLL','Rolename');
	$include_user_fields = array('PositionTitle','JobProfile','Effectivedate','ContingentWorkerType','WorkerManager','Segment', 'CostCenter','JobFamily','SupervisoryOrganization','Location','State', 'LeaveofAbsenceRequests', 'TimeType');
	//array to replace user porfile field as required in report
	$replaced_names = [
		'TimeType'               => 'Employment status', 
		'LeaveofAbsenceRequests' => 'Employee status'
	];
	$sql_condition = '';
	//if(!is_siteadmin($USER->id)) $sql_condition = 'AND u.id in (SELECT hu.user_id from mdl_hierarchy_user hu inner join mdl_hierarchy_node hn on hu.node_id=hn.id where hn.parent_node_id in(select hu2.node_id from mdl_hierarchy_user as hu2 where hu2.user_id='.$USER->id.'))';
	$rs = $DB->get_records_sql('SELECT u.*,fi.timemodified as dateexpressed from mdl_user as u inner join mdl_facetoface_interest fi on fi.userid=u.id where deleted=0 and suspended=0 and fi.facetoface=? '.$sql_condition,array($facetofaceid));
	// echo $facetofaceid; die();
	// echo "<pre>".print_r($rs,true)."</pre>";
	if(!empty($rs)){
		ob_end_clean();
		if($report_type == 'HTML') {
			$PAGE->requires->css('/mod/facetoface/css/tablesorter.css');
			$PAGE->requires->css('/mod/facetoface/css/chosen.css');

		  	echo $OUTPUT->header();
		}
		//Get default headers
		$headers = array('Date expressed interest');
		foreach ($default_fields as $key=>$name) {
			$headers [] = $name;
		}
		//User profile headers
		$rs2 = $DB->get_records('user_info_field',array(),'sortorder ASC');
		$profile_keys = array();
		$profile_types = array();
		foreach ($rs2 as $row2) {
			if(!in_array($row2->shortname,$include_user_fields)) continue;
			$profile_keys [] = $row2->id;
			$profile_types [$row2->id] = $row2->datatype;
			$headers [] = (isset($replaced_names[$row2->shortname])) ? $replaced_names[$row2->shortname] : $row2->name;
		}
		//End of headers
		$file = fopen('php://output', 'w');
		if ($report_type == 'CSV') {
			$filename = "report_".str_replace(" ","_", $facetoface->name)."_".date('Y_m_d').".csv";
			header("Content-type: application/csv");
			header("Content-Disposition: attachment; filename=".$filename);
			header("Pragma: no-cache");
			header("Expires: 0");
			fputcsv($file, $headers);
		} elseif ($report_type == 'HTML') {

			echo $OUTPUT->heading($heading);
			// echo '<p>' . get_string('sorting_tip', 'block_reporting') . '</p>';
			echo '<p class="pull-right"><a href="interest_report.php?type=CSV&sub=1&faceid='.$facetofaceid.'" class="export btn"> Export CSV</a></p>';
			echo "<p>Date of report: ".date('d/m/Y')."</p>";
			echo "<p>Face to face: ".$facetoface->name."</p>";
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
		// var_dump($profile_types);
		foreach ($rs as $row) {
			$body_contents = array();
			$body_contents [] = date('d/m/Y',$row->dateexpressed);
			foreach ($default_fields as $key=>$name) {
				if(in_array($key, array('firstname','lastname'))){
					$body_contents[] = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$row->id.'">'.$row->$key.'</a>';
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
			echo '</table>';
			echo '<div class="pull-right"><a href="interest_report.php?type=CSV&sub=1&faceid='.$facetofaceid.'" class="export btn">Export CSV</a></div>';
			echo $OUTPUT->footer();
			echo '<script src="'.$CFG->wwwroot.'/mod/facetoface/css/jquery-1.12.2.min.js"></script>
				<script src="'.$CFG->wwwroot.'/mod/facetoface/css/jquery.tablesorter.min.js"></script>
				<script src="'.$CFG->wwwroot.'/mod/facetoface/css/chosen.js"></script>
				
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
		}
	}else{
		redirect($baseurl,'Could not find any result in the face to face <strong>'.$facetoface->name.'</strong>', '', core\output\notification::NOTIFY_ERROR);
	}
?>
