<?php
	require_once('../../../config.php');
	require_once($CFG->dirroot .'/blocks/reporting/report/smarty/Smarty.class.php');
	require_once('lib.php');
	global $USER, $DB;
	$users_ids = $USER->id;
	$DBH = new PDO("mysql:host=$CFG->dbhost;dbname=$CFG->dbname", $CFG->dbuser, $CFG->dbpass);

	if(!isset($userid)) $userid = $USER->id;
	require_login(0, false);
	$context_system = context_system::instance();

	$STH = $DBH->prepare("select value from mdl_config where name='siteadmins'");
	$STH->execute();
	$admin_users_string = $STH->fetch(PDO::FETCH_COLUMN, 0);
	$array_with_admin_ids = explode(",", $admin_users_string);


	$report = isset($_GET['report']);
	$mine = isset($_GET['mine']);

	$report_type = 'HTML';
// -----------------------------------------------------------------------------------------------------------------get value
	if($report && !$mine) {
		$course = get_get('course');

		$completionstatus = get_get('completionstatus');
		$enrolleddate = get_get('enrolleddate');
		$completiondate = get_get('completiondate');
		$enrol_date_condition = get_get('enrol_date_condition');
		$completion_date_condition = get_get('completion_date_condition');
		$gradeinputs = get_get('gradeinputs');
		$gradecomparation = get_get('gradecomparation');
		$suspendedusers = get_get('suspendedusers');
		// $hierarchy = get_get('hierarchy');
		// $children_node_ids = get_get('node_ids');    // not in the form???
		//$user_status = get_get('user_status');
		//var_dump($gradecomparation);
		if(isset($_GET['type'])) {
			$report_type = $_GET['type'];
		}
	}
// --------------------------------------------------------------------------------------------------------------smarty setup
	$smarty = new Smarty;
	$path = realpath(".");
	$smarty->compile_dir = get_reporting_compile_folder();
	$smarty->template_dir = $path . '/template' ;

//	$PAGE->set_course($SITE);
//	$PAGE->set_pagetype('site-index');
	$PAGE->set_pagelayout('reports');
	$PAGE->set_title($SITE->fullname);
	$PAGE->set_heading($SITE->fullname);
	$PAGE->set_url('/blocks/reporting/report/general_graph.php');
//	$PAGE->navbar->ignore_active();
	$PAGE->navbar->add("Reporting", new moodle_url('/blocks/reporting/report/general_graph.php'));
// --------------------------------------------------------------------------------------------------------------------header
	if(!$report || $report_type == 'HTML') {
	  echo $OUTPUT->header();
	}
// -----------------------------------------------------------------------------------get userprofile filter and grade filter
	// if $report = false; not this case as $report = true
	if(!$report) {	 	
		// get course information 
		$courses = getCourses();
		// -------------------------------------------------------------------------get user profile field filter information

			/* get dynamic filter information */
			$filter_records = $DB->get_records('reporting_filter');
			
			if ($filter_records != false) {
				$user_profile_filters_array = array();
				foreach ($filter_records as $filter_record) {
					$user_profile_filter = new Object();
			 		$user_info_field_id = $filter_record->user_info_field_id;
			 		$user_info_field_name = null;
			 		$user_info_field_type = null;
			 		$user_info_field_record = $DB->get_record('user_info_field', array('id'=>$user_info_field_id));
			 		if ($user_info_field_record != false) {
			 			$user_info_field_name = $user_info_field_record->name;
			 			$user_info_field_shortName = $user_info_field_record->shortname;
			 			$user_info_field_type = $user_info_field_record->datatype;
			 			/* prepare for text filter */
			 			if (strcmp($user_info_field_type, 'text') == 0) {
			 				$user_profile_values_array = array();
			 				$find_all_user_profile_values_sql = "SELECT DISTINCT data 
			 						                             FROM   {user_info_data} 							
			 						                             WHERE  fieldid = " . $user_info_field_id;
			 				$user_profile_values_records = $DB->get_records_sql($find_all_user_profile_values_sql);

			 				foreach ($user_profile_values_records as $user_profile_values_record) {
			 					$user_profile_values_array[] = $user_profile_values_record->data;
			 				}

			 				$user_profile_filter->user_profile_values = $user_profile_values_array;

			 			/* prepare for checkbox filter */
			 			} else if (strcmp($user_info_field_type, 'checkbox') == 0) {
			 				$user_profile_values_array = array(1, 0);
			 				$user_profile_filter->user_profile_values = $user_profile_values_array;
			 				
			 			/* prepare for menu filter */
			 			} else if (strcmp($user_info_field_type, 'menu') == 0) {
			 				$user_profile_values_array = array();
			 				$find_all_user_profile_values_sql = "SELECT param1 
			 						                             FROM   {user_info_field} 							
			 						                             WHERE  id = " . $user_info_field_id;
			 				$user_profile_values_record = $DB->get_record_sql($find_all_user_profile_values_sql);

			 				if ($user_profile_values_record != false) {
			 					$user_profile_values_array = explode("\n", $user_profile_values_record->param1);
			 				}
			 					
			 				foreach ($user_profile_values_array as $index => $user_profile_value) {
			 					if (strlen(trim($user_profile_value)) == 0) {
			 						unset($user_profile_values_array[$index]);
			 					}
			 				}
			 				$user_profile_filter->user_profile_values = $user_profile_values_array;
			 			}
			 		}

			 		$user_profile_filter->id = $user_info_field_id;
			 		$user_profile_filter->name = $user_info_field_record->name;
			 		$user_profile_filter->shortname = $user_info_field_record->shortname;
			 		$user_profile_filter->type = $user_info_field_record->datatype;
			 		$user_profile_filters_array[] = $user_profile_filter;
			 	}
			}
		// --------------------------------------------------------------------------------------get grade filter information
			$general_filter_records = $DB->get_records('general_filter');
			if ($general_filter_records != false) {
				$general_filters_array = array();
				foreach ($general_filter_records as $general_filter_record) {
					//var_dump("datavalue<br/>");
					//var_dump($general_filter_record);
					//var_dump("datavalue ++++++++++++++++++++++++++++++++++++++++ status<br/>");
					//var_dump($general_filter_record->status);
					$general_filters = new Object();
					$general_filter_id = $general_filter_record->id;
					$general_filter_name = $general_filter_record->filtername;
					$general_filter_status = $general_filter_record->status;
					//var_dump("data status<br/>");
					//var_dump($general_filter_status);
					if($general_filter_status =='Y'){
						$general_filters->id = $general_filter_record->id;
						$general_filters->filtername = $general_filter_record->filtername;
						$general_filters_array[] = $general_filters;
					}
			 	}
			 	//var_dump($general_filter_records);
			}
			//var_dump("passed value ++++++++");
			//var_dump($general_filters_array);
		// ---------------------------------------------------------------------------------------------------- assign smarty
			$smarty->assign('courses', $courses);
			//remove hierarchy function
			// $smarty->assign('hierarchy_nodes', $hierarchy_nodes);
			if(isset($general_filters_array)) $smarty->assign('general_filters_array', $general_filters_array);
			$smarty->assign('user_profile_filters_array', $user_profile_filters_array);
			$smarty->display('interface_general_graph.html.tpl');

			echo $OUTPUT->footer();
			exit(0);
	}

	$wheres = "Where u.username != 'guest' \n ";
	
	if($course && !empty($course)) {
		$wheres .= "AND c.fullname = '" . $course . "'\n";
	}

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
		
		//Not attempted
		if ($completionstatus == "0") {

		} 
		
		else if ($completionstatus == "1") {
			
		} 
		//Completed
		else if($completionstatus == "2") {
			$wheres .= "AND cmc.completionstate = 1" . "\n";
		}
	}

	if($enrolleddate && !empty($enrolleddate)) {
		$enrolleddate = str_replace('/', '-', $enrolleddate);
		//var_dump($enrol_date_condition);
		if (isset($enrol_date_condition) && !empty($enrol_date_condition)) {

			if ($enrol_date_condition == "1") {
				$wheres .= "AND ue.timecreated <= '" . strtotime($enrolleddate) . "'\n";
			} else if ($enrol_date_condition == '2')  {
				$wheres .= "AND ue.timecreated >= '" . strtotime($enrolleddate) . "'\n";
			}
		}
	}

	if($completiondate && !empty($completiondate)) {
		$completiondate = str_replace('/', '-', $completiondate);
		if ($completion_date_condition == 1) {
			$wheres .= "AND cmc.timemodified <= '" . strtotime($completiondate) . "'\n";		
		} else if ($completion_date_condition == 2) {
			$wheres .= "AND cmc.timemodified >= '" . strtotime($completiondate) . "'\n";		
		}
	}

	if(isset($gradeinputs) && $gradeinputs !=""){
		$wheres .= "AND finalgrade.finalgrade ". $gradecomparation. "'" . $gradeinputs . "'";
	}

	$groupby ='GROUP BY ';

	$tables = '';
	$dynamic_fields = "";
	$dynamic_tables = "";
	$filter_records = $DB->get_records('reporting_filter');
	$filters_array = array();

	if ($filter_records != false) {
		foreach ($filter_records as $filter_record) {
			//var_dump($filter_record);
			$user_info_field_id = $filter_record->user_info_field_id; //userid
			$user_info_field_record = $DB->get_record('user_info_field', array('id'=>$user_info_field_id));//table tr
			$shortname = strtolower(str_replace(" ","_", $user_info_field_record->shortname));
			$filter_name = strtolower(str_replace(" ","_", $user_info_field_record->name));
			$datatype = strtolower(str_replace(" ","_", $user_info_field_record->datatype));

			$alias_table_name = $shortname . "info";
			//$user_info_data_value = $DB->get_record('',array('id'=>$user_info_field_id,)
			/* dynamic tables */
			$dynamic_tables .= "LEFT JOIN mdl_user_info_data " . $alias_table_name . "\n";
			$dynamic_tables .= "ON (u.id=" . $alias_table_name . ".userid AND " . $alias_table_name . ".fieldid=" . $user_info_field_id . ")" . "\n";

			/* dynamic fields */
			$dynamic_fields .= ", " . $alias_table_name . ".data as " . $filter_name . "\n";
			$dynamic_fields .= ", " . $alias_table_name . ".data as " . $datatype . "\n";

			/* dynamic filters */
			$filter_value = get_get($filter_name);
			// if (!empty($filter_value)) {
			if ($filter_value != false) {
				$wheres .= "AND " . $alias_table_name . ".data= '" . $filter_value . "'\n";
			}
			$filters_array[$user_info_field_record->name] = new stdclass();
			$filters_array[$user_info_field_record->name]->record = $filter_record;
			$filters_array[$user_info_field_record->name]->type = $datatype;	
		}
	}
// -----------------------------------------------------------------------check whether facetoface plugin have been installed

		$facetofacefield="";
		$facetofacetable ="";
		$facetofacegroupby = "";
		$is_install_facetoface = $DB->get_records('modules',array('name'=>'facetoface'));
		if (empty($is_install_facetoface)) {
				$facetofacefield.="";
			}else{ 
				$facetofacefield.=", facetofacename.name as facetofacename";
				$facetofacetable.=" LEFT OUTER JOIN (
								 	SELECT Distinct mdl_facetoface.name name, mdl_facetoface.course cid, mdl_facetoface.id fid 
								 	FROM mdl_facetoface, mdl_course 
								 	Where mdl_facetoface.course = mdl_course.id
								 	) as facetofacename 
									ON ( facetofacename.cid = c.id and cm.instance = facetofacename.fid and m.name = 'facetoface') ";
				$facetofacegroupby.="facetofacename";
		}
// --------------------------------check whether certificate plugin have been installed
	$certificatefield="";
	$certificatetable ="";
	$certificategroupby = "";
	$is_install_certificate = $DB->get_records('modules',array('name'=>'certificate'));
		if (empty($is_install_certificate)) {
			$certificatefield.="";
		}else{ 

			$certificatefield.=', certificatename.name as certificatename';
			$certificatetable.=" LEFT OUTER JOIN (
							 	SELECT Distinct mdl_certificate.name name, mdl_certificate.course cid, mdl_certificate.id id 
							 	FROM mdl_certificate, mdl_course 
							 	Where mdl_certificate.course = mdl_course.id
							 	) as certificatename 
								ON ( certificatename.cid = c.id and cm.instance = certificatename.id and m.name = 'certificate') ";
			$certificategroupby.="certificate";
	}
// ------------------------------Check if Certificate has been installed
		$performancereviewfield="";
		$performancereviewtable="";
		$performancereviewgroupby="";
		$is_install_performancereview = $DB->get_records('modules',array('name'=>'performancereview'));
		if (!empty($is_install_performancereview)) {
			$performancereviewfield=", performancereviewname.name as performancereviewname ";
			$performancereviewtable = "LEFT OUTER JOIN (
							 	SELECT Distinct mdl_performancereview.name name, mdl_performancereview.course cid, mdl_performancereview.id id 
							 	FROM mdl_performancereview, mdl_course 
							 	Where mdl_performancereview.course = mdl_course.id
							 	) as performancereviewname
								ON ( performancereviewname.cid = c.id and cm.instance = performancereviewname.id  and m.name = 'performancerview')";
			$performancereviewgroupby = "performancereviewname";
		}

// -------------------------------------------------------------------------------------------------add general filter fields

	$general_filter_records = $DB->get_records('general_filter');
	$general_filters_array2 = array();

	if ($general_filter_records != false){
		foreach ($general_filter_records as $general_filter_record){
			$general_filter_id = $general_filter_record->id;
			$general_filter_name = $general_filter_record->filtername;
			$general_filter_status = $general_filter_record->status;
			if($general_filter_status =='Y'){

				$dynamic_tables .= "
				LEFT OUTER JOIN (
					SELECT	Distinct 
							mdl_grade_grades.id gradeid,
							mdl_grade_grades.itemid gradeitemid,
							mdl_grade_grades.finalgrade finalgrade,
							mdl_grade_grades.userid gradeuserid,
							mdl_grade_items.id gitemid,
						  	mdl_grade_items.courseid itemcourseid,
						  	mdl_grade_items.itemmodule itemmodule,
						   	mdl_grade_items.itemname itemname,
						  	mdl_course.id courseid,
						  	mdl_modules.name modulenames,
						  	mdl_user.id userid
					FROM	mdl_grade_grades,
							mdl_user, 
							mdl_course,
							mdl_modules,
							mdl_grade_items
					Where   mdl_grade_grades.itemid = mdl_grade_items.id 
							and mdl_course.id = mdl_grade_items.courseid 
							and mdl_grade_grades.userid = mdl_user.id 
							and mdl_grade_items.itemmodule = mdl_modules.name  
					Group BY mdl_user.id, mdl_course.id, mdl_grade_items.itemname, mdl_grade_grades.id
					) as finalgrade
				ON (finalgrade.gradeitemid = finalgrade.gitemid and c.id = finalgrade.courseid and u.id = finalgrade.gradeuserid and finalgrade.itemmodule = m.name )
				";
					$dynamic_fields .= ", finalgrade.finalgrade as grade, finalgrade.itemname as gradeactivityname";

					//facetofacename.name as facetofacename, scormname.name as scormname
					$wheres .=" ";
					$groupby .=' u.id, CourseName, itemname,Module ,grade ORDER BY u.id';
// ------------------------------------------------------------------------------------------------general_filters_array2
		
		$general_filters_array2[$general_filter_record->filtername]= new stdclass();
		$general_filters_array2[$general_filter_record->filtername]->id = $general_filter_record->id;
		$general_filters_array2[$general_filter_record->filtername]->filtername = $general_filter_record->filtername;
		}
		else{
				$dynamic_fields .=", assignname.name as assignname, scormname.name as scormname, quizname.name as quizname ".$facetofacefield;
			$groupby .= " u.id, CourseName, assignname,scormname,resourcename,quizname,facetofacename,Module ORDER BY u.id";
			}
		}
	}
	$smarty->assign('filters_array', $filters_array);
	$smarty->assign('general_filters_array2', $general_filters_array2);
// -------------------------------------------------------------------------------------------------------------CURRENT QUERY
		$query ="
		 Select u.id as userid, u.firstname as firstname, u.lastname as lastname, cm.id as coursemoduleid, ue.enrolid,e.courseid,c.fullname as coursename,c.id as courseid, m.name as module,cmc.completionstate as completionstatus,ue.timecreated as enrolleddate, cc.timecompleted as completiondate, cm.instance as instance,labelname.name as labelname, bookname.name as bookname,resourcename.name as resourcename, urlname.name as urlname,choicename.name as choicename, foldername.name as foldername, pagename.name as pagename ,scormname.sid as scormid, scormname.name as scormname,
		 scormtrack.value as scormstatus ".$dynamic_fields.$facetofacefield.$certicatefield."
		 FROM mdl_user as u 
		 LEFT JOIN mdl_user_enrolments ue On u.id = ue.userid 
		 LEFT JOIN mdl_enrol e On e.id = ue.enrolid 
		 LEFT JOIN mdl_course c On c.id = e.courseid 
		 LEFT JOIN mdl_course_modules cm On c.id = cm.course 
		 LEFT JOIN mdl_modules m On m.id = cm.module
		 LEFT JOIN mdl_course_modules_completion cmc On (cmc.userid = u.id AND cmc.coursemoduleid=cm.id)
		 LEFT JOIN mdl_course_completions cc On (cc.userid = u.id AND cc.course = c.id)
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
		 $limitation =" ";
// -------------------------------------------------------------------------------------------------------------EXECUTE QUERY
	if(trim($groupby)=="GROUP BY") $groupby="";

	$query = sprintf($query. $dynamic_tables. $facetofacetable.$certicatetable. $wheres .$groupby);
	//print_r("\n");
	//var_dump("<br><br><br><br>");
	// echo $query;
	$STH = $DBH->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));

	$STH->execute();

	if ($STH) {
		$STH->setFetchMode(PDO::FETCH_ASSOC);
		$rows = $STH->fetchall();
		//var_dump("<br><br><br><br><br><br><br>");
		//print_r($rows);
		//echo"<pre>";print_r($rows);echo"</pre>";
		$defaultid =0;
		


// ------------------get the data for rows and combine them into an array


			// echo"<pre>";print_r($rows);echo"</pre>";
			$userinfo_row = array();
			$modules = array();
			$totalno_module_course = 0;
			$tempcourseid = 0;
			$percentage = 0;
			$no_of_completionmodule = 0;
			$tempuserid = 0;
			$initcounter = 0;
			$total_overall_true = 0;
			$total_overall_false = 0;
			$counter = 0;
			$total_course_proportion = 0;
			$coursetrue  = 0;
			$coursefalse = 0;
			$courseselected = get_get('course');
			$total_overall_course_no = getTotalNoCourse($courseselected);
			$currentcourseid = 0;
			$overallcompletion = array();
			$totalcompletionnumber = 0;
			$totalnotcompletionnumber = 0;
			$total_overall_diagram_value = array();
			$course_completion_diagram_true = 0;
			$course_completion_diagram_false = 0;
			foreach($rows as $row){

				$userinfo_row[$row["userid"]][$row["courseid"]]["firstname"] = $row["firstname"];
				$userinfo_row[$row["userid"]][$row["courseid"]][$row['courseid']] = $row["completiondate"];
				$userinfo_row[$row["userid"]][$row["courseid"]]["lastname"] = $row["lastname"];
				$userinfo_row[$row["userid"]][$row["courseid"]]["coursename"] = $row["coursename"];
				$userinfo_row[$row["userid"]][$row["courseid"]]["enrolleddate"] = date('d,m,Y',$row["enrolleddate"]);
				if($row["completiondate"] != ''){
					$userinfo_row[$row["userid"]][$row["courseid"]]["completiondate"] = Date('d/m/Y',$row["completiondate"]);
				}else{
					$userinfo_row[$row["userid"]][$row["courseid"]]["completiondate"] ='';
				}
				
			//modules
				$modulesinput_instanceid = $row["instance"];
					$rowmoduletype = $row["module"];
					$rowcoursemoduleid = $row["coursemoduleid"];
					$rowinstance = $row["instance"];
					$rowcourseid = $row["courseid"];
					$rowmodulename = getModulename2($modulesinput_instanceid,$rowmoduletype,$rowcourseid,$rowcoursemoduleid,$rowinstance);
				// echo"<pre>",print_r($userinfo_row),"</pre>";
					foreach($rowmodulename as $modulename){
						$modulename = $modulename;
					}
				$userinfo_row[$row["userid"]][$row["courseid"]]["module"]["$modulename"] = $row["completionstatus"] ;

			//calculation of courseoverview
				$totalno_module_course = count($userinfo_row[$row["userid"]][$row["courseid"]]["module"]);
				// var_dump($totalno_module_course);
				if($initcounter ==0){
					$tempuserid = $row["userid"];
					$tempcourseid = $row["courseid"];
					if($row['completionstatus'] == 1){
						$no_of_completionmodule++;
						$total_overall_true++;
						$percentage = (int)($no_of_completionmodule  / $totalno_module_course * 100);
				 		$userinfo_row[$row["userid"]][$row["courseid"]]["percentage"] = $percentage."";
					}else{
						$total_overall_false++;
						$percentage = (int)($no_of_completionmodule  / $totalno_module_course * 100);
				 		$userinfo_row[$row["userid"]][$row["courseid"]]["percentage"] = $percentage."";
					}
					$initcounter ++;
				}else{
					//this is not first element in row
					if($row["courseid"] == $tempcourseid  && $row["userid"] == $tempuserid){
						//var_dump($counter);
						if($row['completionstatus'] == 1){
							$no_of_completionmodule++;
							$total_overall_true++;
							$percentage = (int)($no_of_completionmodule  / $totalno_module_course * 100);
						 	$userinfo_row[$row["userid"]][$row["courseid"]]["percentage"] =  $percentage."";
						}else{
							$total_overall_false++;
							$percentage = (int)($no_of_completionmodule  / $totalno_module_course * 100);
					 		$userinfo_row[$row["userid"]][$row["courseid"]]["percentage"] = $percentage."";
						}
					}else{
						$percentage = 0;
						$no_of_completionmodule = 0;
						$tempuserid = $row["userid"];
						$tempcourseid = $row["courseid"];
						if($row['completionstatus'] == 1){
							$no_of_completionmodule++;
							$total_overall_true++;
							$percentage = (int)($no_of_completionmodule  / $totalno_module_course * 100);
						 	$userinfo_row[$row["userid"]][$row["courseid"]]["percentage"] =  $percentage."";
						}else{
							$total_overall_false++;
							$percentage = (int)($no_of_completionmodule  / $totalno_module_course * 100);
					 		$userinfo_row[$row["userid"]][$row["courseid"]]["percentage"] = $percentage."";
						}
					}
				} 
				// var_dump($row["completiondate"]);
				$coursecompletiondate = $userinfo_row[$row["userid"]][$row["courseid"]]["completiondate"];

				// var_dump($coursecompletiondate);
				if($courseselected ==""){
					$currentcourseid = "";
				}else{
					$currentcourseid = $row["courseid"];
				}
				if($coursecompletiondate != ""){
					$coursetrue ++;
					$overallcompletion["completionstatus_true"] = $coursetrue;

				}else{
					$coursefalse++;
					$overallcompletion["completionstatus_false"] = $coursefalse;
				}



				//	echo"<pre>";print_r("this".$counter." is".$initcounter." is ".$row['firstname']."<br/>".$row['coursename']."<br/>this is number of completion module ".$no_of_completionmodule."<br/>"."this is total number of modules in course ".$totalno_module_course. "<br/> this is percentage of coursemodule completion ".$percentage);echo"</pre>";
				//var_dump($totalmodule_onecourse);
	
					$counter++;

	//userprofile field
				foreach($filters_array as $key=>$val){
					if($val->record->status == "Y") {
						// $userinfo_row[$row["userid"]]["profile_result"][strtolower(str_replace(' ', '', $key))];
						 $userinfo_row[$row["userid"]][$row["courseid"]]["profile_result"][strtolower(str_replace(' ', '_', $key))]["result"]= $row[strtolower(str_replace(' ', '_', $key))];
					}	
					$userinfo_row[$row["userid"]][$row["courseid"]]["profile_result"][strtolower(str_replace(' ', '_', $key))]["type"] =
						$val->type;
				}

		//calcualte total completion status
		//display all course overviews 		
			
			
			}

// Start getting data for graphs
			echo "<pre>".print_r($usserinfo_row)."</pre>";

			// course_completion_diagram_value calculation
			$course_completion_diagram_value = array();

			foreach ($userinfo_row as $users) {
				foreach($users as $user=>$key){
					$course_completion_diagram_value[$user]["coursename"]= $key['coursename'];
					if(empty($course_completion_diagram_value[$user]["true"]) && empty($course_completion_diagram_value[$user]["false"]) && empty($course_completion_diagram_value[$user]["percentage"])){
						$course_completion_diagram_value[$user]["true"] = 0;
						$course_completion_diagram_value[$user]["false"]= 0;
						$course_completion_diagram_value[$user]["percentage"]= 0;
					}
					if($key["completiondate"]!=""){
						$course_completion_diagram_value[$user]["true"]++;
						$course_completion_diagram_value[$user]["percentage"] = round($course_completion_diagram_value[$user]["true"]/($course_completion_diagram_value[$user]["true"]+$course_completion_diagram_value[$user]["false"])*100);
					}else{
						$course_completion_diagram_value[$user]["false"] ++;
						$course_completion_diagram_value[$user]["percentage"] = round($course_completion_diagram_value[$user]["true"]/($course_completion_diagram_value[$user]["true"]+$course_completion_diagram_value[$user]["false"])*100);
					}
					$course_completion_diagram_value[$user]["totalenrolled"] = $course_completion_diagram_value[$user]["true"]+	$course_completion_diagram_value[$user]["false"];
				}
			}
			$arraycounter_cc = 1;
			$course_completion_diagram_value_string = "";
			$course_completion_diagram_value_totalenrolled = "";
			foreach($course_completion_diagram_value as $user){

				if($arraycounter_cc == count($course_completion_diagram_value)){
					$course_completion_diagram_value_string .= $user["true"];
				$course_completion_diagram_value_totalenrolled .= $user["totalenrolled"];
				}else{
					$course_completion_diagram_value_string .= $user["true"].",";
				$course_completion_diagram_value_totalenrolled .= $user["totalenrolled"]." , ";
				}
				

				$arraycounter_cc++;
			}

			$course_completion_diagram_value_coursename = array();
			$course_completion_diagram_value_coursename_string = "";
			//course_completion_diagram_coursename_value assign
			$arraycounter = 1;
			foreach ($userinfo_row as $users) {
				foreach($users as $user=>$key){
					$course_completion_diagram_value_coursename[$key["coursename"]]= $key['coursename'];	
				}
			}
			foreach($course_completion_diagram_value_coursename as $coursename){
				if($arraycounter == count($course_completion_diagram_value_coursename)){
					$course_completion_diagram_value_coursename_string.= $coursename;
				}else{
				$course_completion_diagram_value_coursename_string.= $coursename." , ";

				}
				$arraycounter++;
			}
			// echo"<pre>";print_r($course_completion_diagram_true);echo"</pre>";



			foreach ($userinfo_row as $users) {
					foreach($users as $user){
						if(!empty($user['completiondate'])){
									$totalcompletionnumber ++;
						}else{
							$totalnotcompletionnumber ++;
						}
					}
				}
			$courseid_userinfo = $userinfo_row[$row["userid"]][$row["courseid"]];


			
			
			// echo"<pre>";print_r($user);echo"</pre>";
				
			
			// var_dump($totalcompletionnumber);
			//overall completion
			$totalenrolled = get_no_student_enrolled($currentcourseid);
			$totalenrolled = count($totalenrolled);

			//overall completion rate
			$total_overall_true_completion_value = $totalcompletionnumber/$totalenrolled*100;
			$total_overall_false_completion_value = $totalnotcompletionnumber/$totalenrolled*100;
			$total_overall_true_completion_value = number_format($total_overall_true_completion_value, 2, '.', '');
			$total_overall_false_completion_value= number_format($total_overall_false_completion_value, 2,'.','');
			$total_overall_diagram_value['true'] = $total_overall_true_completion_value;
			$total_overall_diagram_value['false']= $total_overall_false_completion_value;


			$total_true_proportion = round($total_overall_true / $counter *100);
			$totla_false_proportion = round($total_overall_false / $counter *100);
			
		

			// foreach($total_overall_diagram_value as $value){
			// 	$total_overall_diagram_value = $value;
			// }

			// echo"<pre>";print_r($total_overall_true_completion_value);echo"</pre>";
			// echo"<pre>";print_r($total_overall_false_completion_value);echo"</pre>";
			// echo"<pre>";print_r($total_overall_diagram_value);echo"</pre>";


			//echo"<pre>";print_r("total ture is ".$total_overall_true."<br/>total true proportion is ".$total_true_proportion."<br/>total false is ".$total_overall_false."<br/>total false proportion is ".$totla_false_proportion."<br/>total module is ".$counter);echo"</pre>";
		//	echo"<pre>";print_r($course_completion_diagram_value_totalenrolled);echo"</pre>";
		
			// echo"<pre>";print_r($course_completion_diagram_value);echo"</pre>";
			//echo"<pre>";print_r($row);echo"</pre>";
			// echo"<pre>";print_r($userinfo_row);echo"</pre>";

			if($report_type == 'HTML') {
			// echo"<pre>";print_r($total_overall_diagram_value);echo"</pre>";
				$smarty->assign('userinfo_row',$userinfo_row);
				$smarty->assign('modules',$modules);
				$smarty->assign('course_completion_diagram_value_totalenrolled',$course_completion_diagram_value_totalenrolled);
				$smarty->assign('course_completion_diagram_value_string',$course_completion_diagram_value_string);
				$smarty->assign('course_completion_diagram_value',$course_completion_diagram_value);
				$smarty->assign('course_completion_diagram_value_coursename',$course_completion_diagram_value_coursename_string);
				$smarty->assign('total_overall_diagram_value_true',$total_overall_diagram_value['true']);
				$smarty->assign('total_overall_diagram_value_false',$total_overall_diagram_value['false']);
			 	$smarty->display('report_graph.html.tpl');
			 }else if($report_type == 'CSV') {
			 	$smarty->assign('userinfo_row',$userinfo_row);
				$smarty->assign('modules',$modules);
				header("Content-type: application/csv");
				header("Content-Disposition: attachment; filename=report.csv");
				header("Pragma: no-cache");
				header("Expires: 0");
				$smarty->display('report_graph.csv.tpl');
			}
			if(!$report || $report_type == 'HTML') {
				echo $OUTPUT->footer();
			}
	}
?>

	 