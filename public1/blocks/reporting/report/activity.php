<?php
	require_once('../../../config.php');

	require_once('lib_pdf.php');
	use mikehaertl\tmp\File;
	use mikehaertl\wkhtmlto\Pdf;
	$PDF_ENABLE = get_report_pdf_functionality_enable();

	require_once($CFG->dirroot .'/blocks/reporting/report/smarty/Smarty.class.php');
	require_once('lib.php');

	$PAGE->requires->js('/lib/mindatlas/jquery/jquery.min.js', true);
	$PAGE->requires->js('/lib/mindatlas/jquery/ui/jquery-ui.min.js', true); 
	$PAGE->requires->css('/lib/mindatlas/jquery/ui/jquery-ui.min.css');
	$PAGE->requires->js('/blocks/reporting/report/js/chosen.js', true);
	$PAGE->requires->js('/blocks/reporting/report/js/jquery.tablesorter.js', true);
	$PAGE->requires->js('/blocks/reporting/report/js/jstree/dist/jstree.min.js', true);
	$PAGE->requires->js('/blocks/reporting/report/resource/chosen.jquery.js', true);
	$PAGE->requires->css('/blocks/reporting/report/css/chosen.css');
	$PAGE->requires->css('/blocks/reporting/report/js/jstree/dist/themes/default/style.min.css');
	$PAGE->requires->css('/blocks/reporting/report/css/reporting.css?v=' . $PAGE->requires->get_jsrev());

	global $USER, $DB;
	$users_ids = $USER->id;
	$DBH = new PDO("mysql:host=$CFG->dbhost;dbname=$CFG->dbname", $CFG->dbuser, $CFG->dbpass);

	if(!isset($userid)) $userid = $USER->id;
	require_login(0, false);
	$context_system = context_system::instance();
	$PAGE->set_context($context_system);


	$report = isset($_GET['report']);
	$mine = isset($_GET['mine']);

// Check if the hierarchy is exist or not
$hierarchy = is_hierarchy_installed();

//REMOVE ALL DELETED FIELDS TO AVOID ISSUE
remove_deleted_fields();

	$report_type = 'HTML';
// -----------------------------------------------------------------------------------------------------------------get value
	if($report && !$mine) {
		$course = get_get('course');
		if(isset($course) && $course != ''){
			 
		}else{
			$nameErr = "Please select course";
		}
		$completionstatus = get_get('completionstatus');
		// $enrolleddate = get_get('enrolleddate');
		// $enrol_date_condition = get_get('enrol_date_condition');
		// $completion_date_condition = get_get('completion_date_condition');
		$gradeinputs = get_get('gradeinputs');
		$gradecomparation = get_get('gradecomparation');
		$report_type = get_get('type');
		$suspendedusers = get_get('suspendedusers');
		// @23/07/2018 enhancement
		$enrolleddate_from = get_get('enrolleddate_from');
		$enrolleddate_to = get_get('enrolleddate_to');
	}
// --------------------------------------------------------------------------------------------------------------smarty setup
	$smarty = new Smarty;
	$path = realpath(".");
	// $smarty->compile_dir = $path . '/compile';
	$smarty->compile_dir = get_reporting_compile_folder();
	$smarty->template_dir = $path . '/template' ;

	$PAGE->set_course($SITE);
	$PAGE->set_pagelayout('reports');
	$PAGE->set_title($SITE->fullname);
	$PAGE->set_heading(get_string('activity_reports', 'block_reporting'));
	$PAGE->set_url('/blocks/reporting/report/activity.php');
	$PAGE->navbar->add(get_string('activity_reports', 'block_reporting'), new moodle_url('/blocks/reporting/report/activity.php'));
	// $PAGE->requires->css('/blocks/reporting/report/css/jquery-ui-1.11.4.css');
	// Jacky: the previous url is wrong, i corrected it
	// Jacky: JQuery UI affect the language menu in header
	// Jacky: as it's not working at the begining, I comment it
// --------------------------------------------------------------------------------------------------------------------header
	if(!$report || $report_type == 'HTML') {
	  echo $OUTPUT->header();
	}
// -----------------------------------------------------------------------------------get userprofile filter and grade filter
if(!$report) {	 	
	// get course information 
		if($hierarchy){
			$courses = getCourse_HierarchyUser();
		} else {
			$courses = getCourses();
		}
	 $user_profile_filters_array = get_reporting_filter_array($hierarchy);
	 $general_filters_array = get_general_filter_array();

	$smarty->assign('courses', $courses);
	 $datepicker_fields = get_reporting_date_picker_script();
	 $smarty->assign('datepicker_fields', $datepicker_fields);
	if(!empty($general_filters_array)) $smarty->assign('general_filters_array', $general_filters_array);
	if(!empty($user_profile_filters_array)) $smarty->assign('user_profile_filters_array', $user_profile_filters_array);

	if($PDF_ENABLE) $smarty->assign('report_pdf', PDF_ENABLE);

	if($hierarchy){
		$hierarchy_nodes = get_hierarchy_tree($USER->id);
		$root_node_id = get_root_hierarchy($USER->id);
		//echo print_r($hierarchy_nodes,true);
		$smarty->assign('hierarchy_nodes', $hierarchy_nodes);
		$smarty->assign('root_node_id', $root_node_id);
		$smarty->display('interface_activity_hierarchy.html.tpl');
	} else{
		$smarty->display('interface_activity.html.tpl');
	}

	echo $OUTPUT->footer();
	exit(0);
}

//
	// @25/07/2018 enhancement
	$global_params = [];
	list($q_course, $p_course) = $DB->get_in_or_equal($course);
	$wheres = "Where c.visible=1 AND u.username != 'guest' AND c.id $q_course and completion<>0 ";
	$global_params = array_merge($global_params, $p_course);

	$coursename = $DB->get_field('course','fullname',array('id'=>$course));
	$coursename = " Course - <strong>".$coursename."</strong> ";
	$smarty->assign('coursename', $coursename);

	switch ($suspendedusers) {
		case 'none':
			$wheres .= " AND u.suspended = '0'". "\n";
			break;
		case 'only':
			$wheres .= " AND u.suspended = '1'". "\n";
			break;
		default:
			break;
	}
	

	if($completionstatus && !empty($completionstatus)) {
		
		if ($completionstatus == "1") {
			$wheres .= "AND ((cmc.completionstate != 1 AND cmc.completionstate != 2) OR (cmc.completionstate is NULL))"  ;
		} 
		//Completed
		else if($completionstatus == "2") {
			$wheres .= "AND (cmc.completionstate = 1 OR cmc.completionstate = 2) " ;
		}
	}

	// @23/07/2018 enhancement
	if (!empty($enrolleddate_from)) {
		$enrolleddate_from = strtotime(str_replace('/', '-', $enrolleddate_from) . ' 00:00');
		$wheres .= "AND (ue.timecreated >= '" . $enrolleddate_from . "') ";
	}
	if (!empty($enrolleddate_to)) {
		$enrolleddate_to = strtotime(str_replace('/', '-', $enrolleddate_to) . ' 23:59');
		$wheres .= "AND (ue.timecreated <= '" . $enrolleddate_to . "') ";
	}
	// if($enrolleddate && !empty($enrolleddate)) {
	// 	$enrolleddate = str_replace('/', '-', $enrolleddate);
	// 	//var_dump($enrol_date_condition);
	// 	if (isset($enrol_date_condition) && !empty($enrol_date_condition)) {

	// 		if ($enrol_date_condition == "1") {
	// 			$wheres .= "AND ue.timecreated <= '" . strtotime($enrolleddate) . "'\n";
	// 		} else if ($enrol_date_condition == '2')  {
	// 			$wheres .= "AND ue.timecreated >= '" . strtotime($enrolleddate) . "'\n";
	// 		}
	// 	}
	// }


	if(isset($gradeinputs) && $gradeinputs !=""){
		$wheres .= "AND finalgrade.finalgrade ". $gradecomparation. "'" . $gradeinputs . "'";
	}

	$groupby ='GROUP BY ';


// Display RESULT =================================================
$hierarchy_query = array("fields"=>'','table'=>'','where'=>'');
if($hierarchy){
	$selectednodes = $_GET['selectednodes'];
	$list_hierarchy_users = get_all_users_from_nodes($selectednodes); // List of users has been added to hierarchy condition
	$hierarchy_query = get_hierarchy_query($list_hierarchy_users);
	$wheres .=$hierarchy_query['where'];
}

// For dynamic fields only
	$array_dynamic_query = array('fields'=>'','table'=>'','where'=>'');

	$filter_records = $DB->get_records_sql('SELECT rf.* from mdl_reporting_filter rf inner join mdl_user_info_field uif ON rf.user_info_field_id=uif.id ORDER BY uif.sortorder ASC');
	$filters_array = array();
	$date_fields = array();
	$date_fields_start = 2; // NUMBER OF DEFAULT FIELDS
	if ($filter_records != false) {
		foreach ($filter_records as $record) {
			//var_dump($filter_record);
			$fieldid = $record->user_info_field_id; //userid
			$rs = $DB->get_record('user_info_field', array('id'=>$fieldid));//table tr

			$filters_array[$rs->shortname] = new stdclass();
			$filters_array[$rs->shortname]->record = $rs;
			$filters_array[$rs->shortname]->type = $rs->datatype;
			if($rs->datatype=='datetime') $date_fields [] = $date_fields_start;
			$date_fields_start++;
		}
		// Get all 
		$array_dynamic_query = get_reporting_filter_query();
		$wheres .= $array_dynamic_query['where'];
		// @25/07/2018 enhancement
		if (!empty($array_dynamic_query['p_dynamic'])) {
			$global_params = array_merge($global_params, $array_dynamic_query['p_dynamic']);
		}
	}
	// ---------------------------------------------------------------------------------------------add general filter fields
	$general_filter_records = $DB->get_records('general_filter');
	$general_filters_array2 = array();
	$arr_user_profile_labels = get_list_user_profile_labels();

	if (!empty($general_filter_records) && is_siteadmin($USER->id)) {
		// @23/07/2018 enhancement
		foreach ($general_filter_records as $general_filter_record){
			if ($general_filter_record->status =='Y') {
				if ($general_filter_record->filtername == 'lastaccess') {
					$lastaccess_from = get_get('lastaccess_from');
					if (!empty($lastaccess_from)) {
						$lastaccess_from = strtotime(str_replace('/', '-', $lastaccess_from) . ' 00:00');
						$wheres .= " AND (lastaccess >= '" . $lastaccess_from . "') ";
					}
					$lastaccess_to = get_get('lastaccess_to');
					if (!empty($lastaccess_to)) {
						$lastaccess_to = strtotime(str_replace('/', '-', $lastaccess_to) . ' 23:59');
						$wheres .= " AND (lastaccess <= '" . $lastaccess_to . "') ";
					}
				} else {
					if (!empty($_GET[$general_filter_record->filtername])) {
						// @25/07/2018 enhancement
						list($q_general, $p_general) = $DB->get_in_or_equal($_GET[$general_filter_record->filtername]);
						$wheres .= " AND (" . $general_filter_record->filtername . " " . $q_general . ") ";
						$global_params = array_merge($global_params, $p_general);
					}
				} 
				// --------------------------------------------------general_filters_array2			
				$general_filters_array2[$general_filter_record->filtername]= new stdclass();
				$general_filters_array2[$general_filter_record->filtername]->id = $general_filter_record->id;
				$general_filters_array2[$general_filter_record->filtername]->filtername = $general_filter_record->filtername;
				$general_filters_array2[$general_filter_record->filtername]->filterdesc = 
				$arr_user_profile_labels[$general_filter_record->filtername];
				if($general_filter_record->filtername=='lastaccess') $date_fields [] = $date_fields_start;
				$date_fields_start++;
			} 
		}
		// echo '<pre>$filtername: '.print_r($filtername,true).'</pre>';
	}

 	//$smarty->debugging = true;	
	// echo '<pre>'.print_r($general_filters_array2,true).'</pre>';

	$smarty->assign('filters_array', $filters_array);
	$smarty->assign('general_filters_array2', $general_filters_array2);

// ================ GET ALL PLUGIN DATA. ==================
// Add new plugin, go to lib.php, finding function: get_plugin_installed()
	$array_plugin = get_plugin_installed();
// ----------------------------------------------------------CURRENT QUERY-----------------------------------------------
		$query ="
		 Select u.id as userid, u.firstname as firstname, u.lastname as lastname, u.username as username, u.email as email, u.city as city, u.country as country, u.lastaccess as lastaccess, cm.id as coursemoduleid, ue.enrolid,e.courseid,c.fullname as coursename, m.name as module,cm.completion,cmc.completionstate as completionstatus,ue.timecreated as enrolleddate, cmc.timemodified as completiondate,cm.instance as instance,
		 scormname.name as scormname, labelname.name as labelname, bookname.name as bookname,resourcename.name as resourcename,quizname.name as quizname, urlname.name as urlname,assignname.name as assignname,choicename.name as choicename, 
		 foldername.name as foldername, pagename.name as pagename 
		 ".$array_plugin['fields'].$array_dynamic_query['fields'].$hierarchy_query['fields']."
		 FROM mdl_user as u 
		 LEFT JOIN mdl_user_enrolments ue On u.id = ue.userid 
		 LEFT JOIN mdl_enrol e On e.id = ue.enrolid 
		 LEFT JOIN mdl_course c On c.id = e.courseid 
		 LEFT JOIN mdl_course_modules cm On c.id = cm.course 
		 LEFT JOIN mdl_modules m On m.id = cm.module
		 LEFT JOIN mdl_course_modules_completion cmc On (cmc.userid = u.id AND cmc.coursemoduleid=cm.id)
		 LEFT OUTER JOIN (
		 	SELECT Distinct mdl_label.name name, mdl_label.course cid, mdl_label.id id 
		 	FROM mdl_label, mdl_course 
		 	Where mdl_label.course = mdl_course.id
		 	) as labelname 
			ON ( labelname.cid = c.id and cm.instance = labelname.id  and m.name = 'label')
		LEFT OUTER JOIN (
		 	SELECT Distinct mdl_folder.name name, mdl_folder.course cid, mdl_folder.id id 
		 	FROM mdl_folder, mdl_course 
		 	Where mdl_folder.course = mdl_course.id
		 	) as foldername
			ON ( foldername.cid = c.id and cm.instance = foldername.id  and m.name = 'folder') 
		LEFT OUTER JOIN (
		 	SELECT Distinct mdl_page.name name, mdl_page.course cid, mdl_page.id id 
		 	FROM mdl_page, mdl_course 
		 	Where mdl_page.course = mdl_course.id
		 	) as pagename
			ON ( pagename.cid = c.id and cm.instance = pagename.id  and m.name = 'page') 
		 LEFT OUTER JOIN (
		 	SELECT Distinct mdl_book.name name, mdl_book.course cid, mdl_book.id id
		 	FROM mdl_book, mdl_course 
		 	Where mdl_book.course = mdl_course.id
		 	) as bookname 
			ON ( bookname.cid = c.id and cm.instance = bookname.id and m.name = 'book') 
		 LEFT OUTER JOIN (
		 	SELECT Distinct mdl_resource.name name, mdl_resource.course cid, mdl_resource.id id 
		 	FROM mdl_resource, mdl_course 
		 	Where mdl_resource.course = mdl_course.id
		 	) as resourcename 
			ON ( resourcename.cid = c.id and cm.instance = resourcename.id and m.name = 'resource') 
		 LEFT OUTER JOIN (
		 	SELECT Distinct mdl_url.name name, mdl_url.course cid, mdl_url.id id  
		 	FROM mdl_url, mdl_course 
		 	Where mdl_url.course = mdl_course.id
		 	) as urlname 
			ON ( urlname.cid = c.id and cm.instance = urlname.id and m.name = 'url') 
		 LEFT OUTER JOIN (
		 	SELECT Distinct mdl_choice.name name, mdl_choice.course cid, mdl_choice.id id 
		 	FROM mdl_choice, mdl_course 
		 	Where mdl_choice.course = mdl_course.id
		 	) as choicename
			ON ( choicename.cid = c.id and cm.instance = choicename.id and m.name = 'choice') 
		 LEFT OUTER JOIN (
		 	SELECT Distinct mdl_quiz.name name, mdl_quiz.course cid, mdl_quiz.id id
		 	FROM mdl_quiz, mdl_course
		 	Where mdl_quiz.course = mdl_course.id
		 	) as quizname 
			ON ( quizname.cid = c.id and cm.instance = quizname.id and m.name = 'quiz') 
		 LEFT OUTER JOIN (
		 	SELECT Distinct mdl_assign.name name, mdl_assign.course cid, mdl_assign.id id
		 	FROM mdl_assign, mdl_course 
		 	Where mdl_assign.course = mdl_course.id
		 	) as assignname 
			ON ( assignname.cid = c.id and cm.instance = assignname.id and m.name = 'assign')
		LEFT OUTER JOIN (
		 	SELECT Distinct mdl_scorm.id sid, mdl_scorm.name name, mdl_scorm.course cid,mdl_scorm.id id
		 	FROM mdl_scorm, mdl_course, mdl_modules m
		 	Where mdl_scorm.course = mdl_course.id 
		 	Group by mdl_scorm.id
		 	) as scormname 
			ON ( scormname.cid = c.id and cm.instance = scormname.id  and m.name='scorm')
		LEFT OUTER JOIN (
			SELECT mdl_scorm_scoes_track.userid userid, mdl_scorm_scoes_track.scormid scormid, mdl_scorm_scoes_track.value value, mdl_scorm_scoes_track.attempt attempt
			FROM mdl_scorm_scoes_track
			WHERE mdl_scorm_scoes_track.element = 'cmi.core.lesson_status' 
			GROUP BY mdl_scorm_scoes_track.userid, mdl_scorm_scoes_track.scormid
			ORDER BY mdl_scorm_scoes_track.attempt
			) as scormtrack
			ON (u.id = scormtrack.userid
			AND scormname.sid = scormtrack.scormid)  
		 "
		 ;	
// -------------------------------------------------------------------------------------------------------------EXECUTE QUERY
	$orderby=" ORDER BY u.firstname,u.lastname,u.id ";
	if(trim($groupby)=="GROUP BY") $groupby="";

	$query = sprintf($query. $array_dynamic_query['table'].$array_plugin['tables'].$hierarchy_query['table'].$wheres .$orderby.$groupby);
	// print_r("\n");
	// echo $query;
	$STH = $DBH->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	// @25/07/2018 enhancement
	$STH->execute($global_params);

	if ($STH) {
		$STH->setFetchMode(PDO::FETCH_ASSOC);
		$rows = $STH->fetchall();
		$defaultid =0;
		

		if(count($rows)<1){
			echo get_string('activityresult','block_reporting');
			echo get_string('noresult','block_reporting');
		}else{
// ------------------get the data for rows and combine them into an array


			// echo"<pre>";print_r($rows);echo"</pre>";
			$userinfo_row = array();
			$modules = array();
			foreach($rows as $row){
				$userinfo_row[$row["userid"]]["firstname"] = $row["firstname"];
				$userinfo_row[$row["userid"]]["lastname"] = $row["lastname"];
				$userinfo_row[$row["userid"]]["enrolleddate"] = date('d/m/Y',$row["enrolleddate"]);

				get_module_value($userinfo_row,$modules,$row);

				// Only being used by hierarchy 
				if($hierarchy){
					$userinfo_row[$row["userid"]]["node_name"] = $row['node_name'];
					$userinfo_row[$row["userid"]]["leveldescription"] = $row['leveldescription'];
					$userinfo_row[$row["userid"]]["nodedescription"] = $row['nodedescription'];
					// $userinfo_row[$row["userid"]]["level"] = $row['level'];
					// $userinfo_row[$row["userid"]]["node_id"] = $row['node_id'];
				}

				foreach($filters_array as $key=>$val){
					// Check if the checkbox will show YES/NO instead of 1/0
					if($val->type=="checkbox"){
						if($row[$key]=='1') $row[$key] = 'Yes';
						if($row[$key]=='0') $row[$key] = 'No';
					}
					if($val->type=="datetime"){ 
						if(($row[$key]==0)||($row[$key]=="")) $row[$key]="";
						else $row[$key] = date('d/m/Y',$row[$key]);
					}
					$userinfo_row[$row["userid"]]["profile_result"][$key]['value'] = $row[$key];
					$userinfo_row[$row["userid"]]["profile_result"][$key]["type"] = $val->type;
					
				}
				foreach($general_filters_array2 as $key=>$val){					
					$userinfo_row[$row["userid"]]["profile_result"][$key]['value']= $row[$key];
					$userinfo_row[$row["userid"]]["profile_result"][$key]['type']= $key;
				}
				if(isset($userinfo_row[$row["userid"]]["module"]))
					ksort($userinfo_row[$row["userid"]]["module"]);
				// echo "<pre>".print_r($userinfo_row[$row["userid"]]["module"],true)."</pre>";
			}
			asort($modules);
			// echo "<pre>".print_r($modules,true)."</pre>";
			//echo"<pre>";print_r($row);echo"</pre>";
			 // echo"<pre>";print_r($userinfo_row);echo"</pre>";
		if($hierarchy){
			if($report_type == 'HTML') {
				$date_field_script = get_reporting_sort_date_script($date_fields,count($modules));
				$smarty->assign('date_field_script', $date_field_script);

				$smarty->assign('userinfo_row',$userinfo_row);
				$smarty->assign('modules',$modules);
			 	$smarty->display('report_activity_hierarchy.html.tpl');
			 }else if($report_type == 'CSV') {
			 	$smarty->assign('userinfo_row',$userinfo_row);
				$smarty->assign('modules',$modules);
				header("Content-type: application/csv");
				header("Content-Disposition: attachment; filename=report.csv");
				header("Pragma: no-cache");
				header("Expires: 0");
				$smarty->display('report_activity_hierarchy.csv.tpl');
			}
		}else{
			if($report_type == 'HTML') {
				$date_field_script = get_reporting_sort_date_script($date_fields,count($modules));
				$smarty->assign('date_field_script', $date_field_script);
				$smarty->assign('userinfo_row',$userinfo_row);
				$smarty->assign('modules',$modules);
			 	$smarty->display('report_activity.html.tpl');
			 }else if($report_type == 'CSV') {
			 	$smarty->assign('userinfo_row',$userinfo_row);
				$smarty->assign('modules',$modules);
				header("Content-type: application/csv");
				header("Content-Disposition: attachment; filename=report.csv");
				header("Pragma: no-cache");
				header("Expires: 0");
				$smarty->display('report_activity.csv.tpl');
			}
		}
	if($report_type=='PDF' && $PDF_ENABLE) {
		$pdf_settings = get_report_pdf_options();
		$pdf_configures = get_report_pdf_configuration($pdf_settings);
		$pdf = new Pdf($pdf_configures);
		$smarty->assign('userinfo_row', $userinfo_row);
		$smarty->assign('modules',$modules);
		$smarty->assign('reportimagepath',$CFG->wwwroot."/blocks/reporting/report/img");
		
		$pdf_html = $smarty->fetch('report_activity_pdf.html.tpl');
		// echo $pdf_html; die();
		$html_tmp_file = new File($pdf_html, '.html', Pdf::TMP_PREFIX, null);
		$pdf->addPage($html_tmp_file->getFileName());
		ob_end_clean();
		if(!$pdf->send()){
			print_r($pdf->getError());
		}
	}
	}
	if(!$report || $report_type == 'HTML') {
		echo $OUTPUT->footer();
	}
}
?>
	 