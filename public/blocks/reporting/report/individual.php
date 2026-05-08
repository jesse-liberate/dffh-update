<?php
require_once('../../../config.php');

require_once('lib_pdf.php');
use mikehaertl\tmp\File;
use mikehaertl\wkhtmlto\Pdf;
use block_reporting\report\util_class;
$PDF_ENABLE = get_report_pdf_functionality_enable();

require_once($CFG->dirroot .'/blocks/reporting/report/smarty/Smarty.class.php');
require_once('lib.php');

	$PAGE->requires->js('/lib/mindatlas/jquery/jquery.min.js', true);
	$PAGE->requires->js('/lib/mindatlas/jquery/ui/jquery-ui.min.js', true); 
	$PAGE->requires->css('/lib/mindatlas/jquery/ui/jquery-ui.min.css');
	$PAGE->requires->js('/blocks/reporting/report/js/chosen.js', true);
	$PAGE->requires->js('/blocks/reporting/report/js/jquery.tablesorter.js', true);
	$PAGE->requires->js('/blocks/reporting/report/resource/chosen.jquery.js', true);
	$PAGE->requires->js('/blocks/reporting/report/js/Chartjs/Chart.js', true);
	$PAGE->requires->css('/blocks/reporting/report/css/chosen.css?v=' . $PAGE->requires->get_jsrev());
	$PAGE->requires->css('/blocks/reporting/report/css/reporting.css?v=' . $PAGE->requires->get_jsrev());

$DBH = new PDO("mysql:host=$CFG->dbhost;dbname=$CFG->dbname", $CFG->dbuser, $CFG->dbpass);

if(!isset($userid)) $userid = $USER->id;

require_login(0, false);
$context_system = context_system::instance();
$PAGE->set_context($context_system);

$STH = $DBH->prepare("select value from mdl_config where name='siteadmins'");
$STH->execute();
$admin_users_string = $STH->fetch(PDO::FETCH_COLUMN, 0);
$array_with_admin_ids = explode(",", $admin_users_string);

//REMOVE ALL DELETED FIELDS TO AVOID ISSUE
remove_deleted_fields();

$report = "";
$uid="";
$report_type = 'HTML';

if(isset($_POST['report'])){
	$report = $_POST['report'];
	$uid = $_POST['uid'];
	$report_type = $_POST['type'];
}

$smarty = new Smarty;
$path = realpath(".");
// $smarty->compile_dir = $path . '/compile';
$smarty->compile_dir = get_reporting_compile_folder();
$smarty->template_dir = $path . '/template' ;


$PAGE->set_pagelayout('reports');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading(get_string('individual_reports', 'block_reporting'));
$PAGE->set_url('/blocks/reporting/report/individual.php');
$PAGE->navbar->add(get_string('individual_reports', 'block_reporting'), new moodle_url('/blocks/reporting/report/individual.php'));


//===============================================================
// Check if the hierarchy is exist or not
// $hierarchy = is_hierarchy_installed();
$hierarchy = is_user_in_hierarchy($USER->id);
$hierarchy_query = array("fields"=>'','table'=>'','where'=>'');
if($hierarchy){
 	$hierarchy_query = get_hierarchy_query($USER->id);
 }

$has_capability = has_capability('block/reporting:viewreports', context_system::instance());

if(!$report || $report_type == 'HTML') {
  echo $OUTPUT->header();
}

if(!$report) {
	$is_vendor = is_vendor($USER->id);
	$is_admin = is_siteadmin($USER->id);
	if(!$has_capability&&!$is_vendor&&!$hierarchy) { 
		echo get_string('title:individual','block_reporting');
		echo get_string('notallowtoaccess','block_reporting'); 
		echo $OUTPUT->footer();
		exit(0);
	}
	$list_user_options = get_individual_user_options($USER->id,$hierarchy,$has_capability,$is_vendor);
	if($PDF_ENABLE) $smarty->assign('report_pdf', PDF_ENABLE);
	$smarty->assign('user_fullname_options',$list_user_options);
	$smarty->display('interface_individual.html.tpl');
	echo $OUTPUT->footer();
	exit(0);
}



$user_joins = '';
$wheres = 'Where c.visible=1 AND ';
$params = array();

if($uid!="") $wheres .= " u.id=" . $uid . " \n";
else{
	echo $OUTPUT->header();
	echo get_string('title:individual','block_reporting');
	echo get_string('error:selectusername','block_reporting');
	echo "<b><a href='individual.php' class='btn'>Back</a>";
	echo $OUTPUT->footer();
	exit;
}


//=======================================================
// If user is part of the hierarchy, they are not allow to see anyone higher than them
//
if(!$has_capability){
	//================= FOR VENDOR ===========================
	if(is_vendor($USER->id)){
		// If user does not do any course relating to this vendor => can not access
		// If user does some courses relating to this vendor => vendor should allow to see this course
	    $arr_course_ids = get_vendor_course_ids($USER->id);
	    $list = implode(",", $arr_course_ids);
	    $wheres .= " AND c.id in (" . $list . ") \n ";
	//=======================================================
	}else{ // Normal user. They can not access if they are not part of the hierarchy
		// If they are in the hierarchy, they can see only see people under their level.
		$current_node_id = $DB->get_field('hierarchy_user','node_id',array('user_id'=>$USER->id));
		$all_users_under_thisuser = get_all_users_from_nodes($current_node_id);
		$sql_check = "select id from mdl_user where id=" . $uid . " 
		and id in(".$all_users_under_thisuser.")";
		if(!$DB->record_exists_sql($sql_check)){ 
			echo get_string('title:individual','block_reporting');
			echo get_string('noresult','block_reporting'); 
			echo "<b><a href='individual.php' class='btn'>Back</a>";
			echo $OUTPUT->footer();
			exit();
		}
	}
}

// ---------------------------- setup color for the graphs ----------------
$color_pie = array();

	$defaultcolor_pie = get_default_colors();
	$pie = $DB->get_record('report_setting',array('name'=>'pie_chart'));
	if(!empty($pie)) $color_pie = explode(',', $pie->setting);
	else $color_pie = $defaultcolor_pie;

// -----------------------------------------------------------------get value----------------





// ================================================
// For dynamic fields only
	$array_dynamic_query = array('fields'=>'','table'=>'','where'=>'');

	$filter_records = $DB->get_records_sql('SELECT rf.* from mdl_reporting_filter rf inner join mdl_user_info_field uif ON rf.user_info_field_id=uif.id ORDER BY uif.sortorder ASC');
	$filters_array = array();
	$date_fields = array();
	$date_fields_start = 6; // NUMBER OF DEFAULT FIELDS
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
	}

$date_field_script = get_reporting_sort_date_script($date_fields);
$smarty->assign('date_field_script', $date_field_script);
$smarty->assign('filters_array', $filters_array);


// ================ GET ALL PLUGIN DATA. ==================
// Add new plugin, go to lib.php, finding function: get_plugin_installed()
	$array_plugin = get_plugin_installed();

// ======================== PLUGIN ==========================

	$groupby ="GROUP BY";	
	$general_filter_records = util_class::getGeneralFilterRecords();

	if ($general_filter_records != false){
		foreach ($general_filter_records as $general_filter_record){
			$filter_name = $general_filter_record->filtername;
			$array_dynamic_query['fields'] .= ", u.{$filter_name} as user_{$filter_name}";
		}
	}
	$smarty->assign('general_filter_records', $general_filter_records);
$query ="
		Select u.id as userid, u.firstname as firstname, u.lastname as lastname, cm.id as coursemoduleid, ue.enrolid,e.courseid,c.fullname as coursename, m.name as module,cm.completion, cmc.completionstate as completionstatus,ue.timecreated as enrolleddate, cmc.timemodified as completiondate,cm.instance as instance,labelname.name as labelname, bookname.name as bookname,resourcename.name as resourcename, urlname.name as urlname,choicename.name as choicename,
		 foldername.name as foldername, pagename.name as pagename ,scormname.sid as scormid, 
		 scormname.name as scormname,scormtrack.value as scormstatus 
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

// -------------------- EXECUTE QUERY -----------------------
$orderby=" ORDER BY c.fullname,m.name, u.firstname,u.lastname,u.id ";
if(trim($groupby=="GROUP BY")) $groupby="";

	$query = sprintf($query.$array_dynamic_query['table'].$array_plugin['tables'].$hierarchy_query['table']. $wheres .$orderby.$groupby);
// $query = sprintf($query. $dynamic_tables .$wheres);
 // echo ($query);
//echo "<pre>", print_r($query, true), "</pre>";
$STH = $DBH->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));

$STH->execute($params);

if ($STH) {
	$STH->setFetchMode(PDO::FETCH_ASSOC);
	$rows = $STH->fetchall();
	$userinfo_row = array();
	$modules = array();
	$countrow = count($rows);
			// echo"<pre>";print_r($rows);echo"</pre>";

	if($countrow<1) { 
		echo get_string('individualresult','block_reporting');
		echo get_string('noresult','block_reporting');
	}
	else {
		$num_completed = 0;
		$num_activities = 0;
		foreach($rows as $row){
			
			if(($row["completion"] == '')||($row["completion"]===null)||($row["completion"] == 0))
				continue;
				if(($row['completionstatus'] != 1)&&($row['completionstatus'] !=2)){
					$row["completiondate"]="";
				}
				$userinfo_row[$row["userid"]][$row["coursemoduleid"]]["firstname"] = $row["firstname"];
				$userinfo_row[$row["userid"]][$row["coursemoduleid"]]["lastname"] = $row["lastname"];
				$userinfo_row[$row["userid"]][$row["coursemoduleid"]]["enrolleddate"] = $row["enrolleddate"];
				$userinfo_row[$row["userid"]][$row["coursemoduleid"]]["coursemoduleid"] = $row["coursemoduleid"];
				$userinfo_row[$row["userid"]][$row["coursemoduleid"]]["coursename"] = $row["coursename"];
				$userinfo_row[$row["userid"]][$row["coursemoduleid"]]["moduletype"] = $row["module"];
				$userinfo_row[$row["userid"]][$row["coursemoduleid"]]["courseid"] = $row["courseid"];
				$userinfo_row[$row["userid"]][$row["coursemoduleid"]]["completiondate"] = $row["completiondate"];
				$userinfo_row[$row["userid"]][$row["coursemoduleid"]]["scormstatus"] = $row["scormstatus"];
				$userinfo_row[$row["userid"]][$row["coursemoduleid"]]["completionstatus"] = $row["completionstatus"];
				$rowmoduletype = $row["module"];
				$rowcoursemoduleid = $row["coursemoduleid"];
				$rowinstance = $row["instance"];
				$rowcourseid = $row["courseid"];
				$rowmodulename = getModulename($rowmoduletype,$rowcourseid,$rowcoursemoduleid,$rowinstance);
				$userinfo_row[$row["userid"]][$row["coursemoduleid"]]["modulename"] = $rowmodulename;
					
				// Only being used by hierarchy 
				if($hierarchy){
					$userinfo_row[$row["userid"]][$row["coursemoduleid"]]["node_name"] = $row['node_name'];
					$userinfo_row[$row["userid"]][$row["coursemoduleid"]]["leveldescription"] = $row['leveldescription'];
					$userinfo_row[$row["userid"]][$row["coursemoduleid"]]["nodedescription"] = $row['nodedescription'];
					// $userinfo_row[$row["userid"]][$row["coursemoduleid"]]["level"] = $row['level'];
					// $userinfo_row[$row["userid"]][$row["coursemoduleid"]]["node_id"] = $row['node_id'];
				}

				foreach($filters_array as $key=>$val){
					// Check if the checkbox will show YES/NO instead of 1/0
					if($val->type=="checkbox"){
						if($row[$key]=='1') $row[$key] = 'Yes';
						if($row[$key]=='0') $row[$key] = 'No';
					}
					$userinfo_row[$row["userid"]][$row["coursemoduleid"]]["profile_result"][$key]["type"] = $val->type;
					$userinfo_row[$row["userid"]][$row["coursemoduleid"]]["profile_result"][$key]['value'] = $row[$key];
				}

				foreach ($general_filter_records as $filter) {
					$userinfo_row[$row["userid"]][$row["coursemoduleid"]]["profile_result"][$filter->filtername] = [
						'type' => $filter->datatype,
						'value' => $row['user_' . $filter->filtername],
					];
				}


				if(!empty($general_filters_array2)){
					foreach($general_filters_array2 as $key=>$val){
				}
					
					$userinfo_row[$row["userid"]][$row["coursemoduleid"]]["grade"]= $row[strtolower(str_replace(' ', '_', $key))];
				}

				$coursenamefilter = $row['coursename'];
				// echo"<pre>",print_r($userinfo_row[$row["userid"]]),"</pre>";

				// For display graph
				// ONLY COURSE WITH THE TICK BOX WILL BE COUNTED
				if(($row['completion']==1)||($row['completion']==2))
					$num_activities++;
				if(($row['completionstatus'] == 1)||($row['completionstatus'] ==2)||($row["scormstatus"]=="passed")||($row["scormstatus"]=="completed")){
					$num_completed++;
				}
			}
			// Calculate for graph
			if($num_activities==0){
				$percentage_true = 0;
				$percentage_false = 100;
				$total_overall_diagram_value['true'] = 0;
				$total_overall_diagram_value['false'] = 100;
			}else{
				$percentage_true = ($num_completed/$num_activities)*100;
				$percentage_false = 100 - $percentage_true;
				$total_overall_diagram_value['true'] = number_format($percentage_true,2,'.','');
				$total_overall_diagram_value['false'] = number_format($percentage_false,2,'.','');
			}

			  // echo"<pre>";print_r(count($userinfo_row[$row["userid"]]));echo"</pre>";
			// foreach ($userinfo_row as $row) {
			// 	echo"<pre>",print_r($row),"</pre>";
			// }
				 

		// echo"<pre>",print_r($userinfo_row,true),"</pre>";
		$smarty->assign('userinfo_row', $userinfo_row);
		if($hierarchy){
			if($report_type == 'HTML') {

				$smarty->assign('pie_color_completed',$color_pie[0]);
				$smarty->assign('pie_color_not_completed',$color_pie[1]);
				$smarty->assign('pie_highlightcolor_completed',$color_pie[2]);
				$smarty->assign('pie_highlightcolor_not_completed',$color_pie[3]);

				$smarty->assign('total_overall_diagram_value_true',$total_overall_diagram_value['true']);
				$smarty->assign('total_overall_diagram_value_false',$total_overall_diagram_value['false']);
				$smarty->display('report_individual_hierarchy.html.tpl');
			} elseif($report_type == 'CSV') {
				header("Content-type: application/csv");
				header("Content-Disposition: attachment; filename=report.csv");
				header("Pragma: no-cache");
				header("Expires: 0");
				$smarty->display('report_hierarchy.csv.tpl');
			}
		}else{
			if($report_type == 'HTML') {
				$smarty->assign('pie_color_completed',$color_pie[0]);
				$smarty->assign('pie_color_not_completed',$color_pie[1]);
				$smarty->assign('pie_highlightcolor_completed',$color_pie[2]);
				$smarty->assign('pie_highlightcolor_not_completed',$color_pie[3]);

				$smarty->assign('total_overall_diagram_value_true',$total_overall_diagram_value['true']);
				$smarty->assign('total_overall_diagram_value_false',$total_overall_diagram_value['false']);
				$smarty->display('report_individual.html.tpl');
			} elseif($report_type == 'CSV') {
				header("Content-type: application/csv");
				header("Content-Disposition: attachment; filename=report.csv");
				header("Pragma: no-cache");
				header("Expires: 0");
				$smarty->display('report.csv.tpl');
			}
		}
		if($report_type=='PDF' && $PDF_ENABLE) {
			$pdf_settings = get_report_pdf_options();
			$pdf_configures = get_report_pdf_configuration($pdf_settings);
			$pdf = new Pdf($pdf_configures);
			$smarty->assign('pie_color_completed',$color_pie[0]);
			$smarty->assign('pie_color_not_completed',$color_pie[1]);
			$smarty->assign('pie_highlightcolor_completed',$color_pie[2]);
			$smarty->assign('pie_highlightcolor_not_completed',$color_pie[3]);

			$smarty->assign('total_overall_diagram_value_true',$total_overall_diagram_value['true']);
			$smarty->assign('total_overall_diagram_value_false',$total_overall_diagram_value['false']);
			$smarty->assign('userinfo_row', $userinfo_row);
			$pdf_html = $smarty->fetch('report_individual_pdf.html.tpl');
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
