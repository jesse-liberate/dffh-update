<?php
	require_once('../../../config.php');
	require_once('lib.php');
	require_once('lib_pdf.php');
	require_once($CFG->libdir.'/adminlib.php');

	$PAGE->requires->js('/lib/mindatlas/jquery/jquery.min.js', true);
	$PAGE->requires->js('/lib/mindatlas/jquery/ui/jquery-ui.min.js', true); 
	$PAGE->requires->css('/lib/mindatlas/jquery/ui/jquery-ui.min.css');
	$PAGE->requires->js('/blocks/reporting/report/js/chosen.js', true);
	$PAGE->requires->js('/blocks/reporting/report/js/jquery.tablesorter.js', true);
	$PAGE->requires->js('/blocks/reporting/report/js/jstree/dist/jstree.min.js', true);
	$PAGE->requires->js('/blocks/reporting/report/resource/chosen.jquery.js', true);
	$PAGE->requires->css('/blocks/reporting/report/css/chosen.css');
	$PAGE->requires->css('/blocks/reporting/report/js/jstree/dist/themes/default/style.min.css');
	$PAGE->requires->css('/blocks/reporting/report/css/plugin.css');

	global $USER, $DB;
	$users_ids = $USER->id;
	$DBH = new PDO("mysql:host=$CFG->dbhost;dbname=$CFG->dbname", $CFG->dbuser, $CFG->dbpass);

	if(!isset($userid)) $userid = $USER->id;
	require_login(0, false);
	$context_system = context_system::instance();
	$PAGE->set_context($context_system);

	$PAGE->set_pagelayout('reports');
	$PAGE->set_title($SITE->fullname);
	$PAGE->set_heading(get_string('reportsetting','block_reporting'));
	$PAGE->set_url('/blocks/reporting/report/settingcolor.php');
	//$PAGE->navbar->ignore_active();
	$PAGE->navbar->add("Reporting", new moodle_url('/blocks/reporting/report/settingcolor.php'));

if(!is_siteadmin($userid)) {
	echo get_string('notallowtoaccess','block_reporting');
	exit();
}

$PDF_ENABLE = get_report_pdf_functionality_enable();

$pagelayout = optional_param('pagelayout',LAYOUT_PORTRAIT,PARAM_INT);
$sub = optional_param('sub',"",PARAM_TEXT);

$table = 'report_setting';

$defaultcolor_pie = array('0'=>'#cbdde6','1'=>'#eeeeee','2'=>'#9dc2d5','3'=>'#bcb8b8'); // 0: completed, 1: not completed
$defaultcolor_bar = array('0'=>'#cbdde6','1'=>'#eeeeee');
$defaultcolor_courseoverview = array('0'=>'#cbdde6','1'=>'#eeeeee');
$default_logo = new moodle_url('/blocks/reporting/report/img/defaultlogo.png');
$reportlogo_record = $DB->get_record($table,array('name'=>'reportlogo'));
if(!empty($reportlogo_record)) $default_logo = new moodle_url('/blocks/reporting/report/gettype.php',array('id'=>$reportlogo_record->setting,'file'=>'download'));
$pagelayout_record = $DB->get_record($table,array('name'=>'pagelayout'));
if(!empty($pagelayout_record)) $pagelayout = $pagelayout_record->setting;

if($sub!=""){
	$post = $_POST;
	$array_pie = array('0'=>'','1'=>'','2'=>'','3'=>'');
	if($post['pie_com']!='') $array_pie[0] = $post['pie_com']; else $array_pie[0] = $defaultcolor_pie[0];
	if($post['pie_notcom']!='') $array_pie[1] = $post['pie_notcom']; else $array_pie[1] = $defaultcolor_pie[1];
	if($post['pie_comhigh']!='') $array_pie[2] = $post['pie_comhigh']; else $array_pie[2] = $defaultcolor_pie[2];
	if($post['pie_com']!='') $array_pie[3] = $post['pie_notcomhigh']; 	else $array_pie[3] = $defaultcolor_pie[3];

	$array_bar = array('0'=>'','1'=>'');
	if($post['bar_com']!='') $array_bar[0] = $post['bar_com']; else $array_bar[0] = $defaultcolor_bar[0];
	if($post['bar_notcom']!='') $array_bar[1] = $post['bar_notcom']; else $array_bar[1] = $defaultcolor_bar[1];

	$array_course = array('0'=>'','1'=>'');
	if($post['cour_bg']!='') $array_course[0] = $post['cour_bg']; else $array_course[0] = $defaultcolor_courseoverview[0];
	if($post['cour_per']!='') $array_course[1] = $post['cour_per']; else $array_course[1] = $defaultcolor_courseoverview[1];

// update value into database
	$pie = '#'.implode(",#", $array_pie);
	$bar = '#'.implode(",#", $array_bar);
	$cour = '#'.implode(",#", $array_course);
	$rs = $DB->get_records('report_setting',array());
	foreach($rs as $k=>$row){
		switch ($row->name) {
			case 'pie_chart':   // update record into pie chart record
				$r = $row;
				$r->setting = $pie;
				$DB->update_record($table,$r);
				break;
			case 'bar_chart':   // update record into pie chart record
				$r = $row;
				$r->setting = $bar;
				$DB->update_record($table,$r);
				break;
			case 'courseoverview_chart':   // update record into pie chart record
				$r = $row;
				$r->setting = $cour;
				$DB->update_record($table,$r);
				break;
			default:
				# code...
				break;
		}
	}
// Check if no record in database. Get default value.	
	if(!$DB->record_exists($table,array('name'=>'pie_chart'))){
		// Insert default record

		$record = new stdClass();
		$record->name="pie_chart";
		$record->timecreated=time();
		$record->description="Pie chart setting";
		$record->setting=implode(',', $defaultcolor_pie);
		$DB->insert_record($table,$record);
	}
	if(!$DB->record_exists($table,array('name'=>'bar_chart'))){
		// Insert default record

		$record = new stdClass();
		$record->name="bar_chart";
		$record->timecreated=time();
		$record->description="Bar chart setting";
		$record->setting=implode(',', $defaultcolor_bar);
		$DB->insert_record($table,$record);
	}
	if(!$DB->record_exists($table,array('name'=>'courseoverview_chart'))){
		// Insert default record

		$record = new stdClass();
		$record->name="courseoverview_chart";
		$record->timecreated=time();
		$record->description="Course Overview chart setting";
		$record->setting=implode(',', $defaultcolor_courseoverview);
		$DB->insert_record($table,$record);
	}
	//PDF CONFIGURATIONS
	if($PDF_ENABLE){
		if(empty($pagelayout_record)){
			$new = new stdClass();
			$new->name="pagelayout";
			$new->timecreated=time();
			$new->setting=$pagelayout;
			$DB->insert_record($table,$new);
		}else{
			$pagelayout_record->setting = $pagelayout;
			$DB->update_record($table,$pagelayout_record);
		}
		$file = $_FILES['filelogo'];
		if($file['error']==0&&$file['name']!=""){
			$fileid=0;
			if(empty($reportlogo_record)){
				$fileid = insert_or_update_picture_to_moodle($file,null,'block_reporting','reportlogo');
				$new = new stdClass();
				$new->name = 'reportlogo';
				$new->timecreated = time();
				$new->setting = $fileid;
				$DB->insert_record('report_setting',$new);
			}else{
				$old_fileid = $reportlogo_record->setting;
				$fileid = insert_or_update_picture_to_moodle($file,$old_fileid,'block_reporting','reportlogo');
				$reportlogo_record->setting = $fileid;
				$DB->update_record('report_setting',$reportlogo_record);
		 	}
			$default_logo = new moodle_url('/blocks/reporting/report/gettype.php',array('id'=>$fileid,'file'=>'download'));
		}
	}
}
$default_logo= html_writer::img($default_logo, '', array('class' => 'report_logo'));
// Get values from database:
$rs_pie = $DB->get_record($table,array('name'=>'pie_chart'));
if(!empty($rs_pie)) $arr_pie = explode(',', $rs_pie->setting);
else $arr_pie = $defaultcolor_pie;

$rs_bar = $DB->get_record($table,array('name'=>'bar_chart'));
if(!empty($rs_bar)) $arr_bar = explode(',', $rs_bar->setting);
else $arr_bar = $defaultcolor_bar;

$rs_course= $DB->get_record($table,array('name'=>'courseoverview_chart'));
if(!empty($rs_course)) $arr_course = explode(',', $rs_course->setting);
else $arr_course = $defaultcolor_courseoverview;

echo $OUTPUT->header();
// Start showing the form
echo "<script src='js/jscolor.js'></script>";

if($sub!="") echo get_string('savemessage','block_reporting');

echo html_writer::start_tag('form',array('action'=> new moodle_url('settingcolor.php'),'id'=>'frmsetting','method'=>'POST','enctype'=>'multipart/form-data'));

echo html_writer::start_tag('table', [
	'class' => 'w-100 mb-3',
]);
echo html_writer::start_tag('tbody');

echo html_writer::start_tag('tr');
echo html_writer::tag('td', html_writer::tag('h3',get_string('pie_chart','block_reporting')), [
	'colspan' => 2,
]);
echo html_writer::end_tag('tr');

echo html_writer::start_tag('tr');
echo html_writer::tag('td', html_writer::tag('strong', get_string('pie_completed','block_reporting')), [
	'class' => 'pr-5 color-brand-1',
]);
echo html_writer::tag('td', html_writer::empty_tag('input',array('type'=>'text','name'=>'pie_com','value'=>$arr_pie[0],'class'=>'jscolor form-control mb-2')));
echo html_writer::end_tag('tr');

echo html_writer::start_tag('tr');
echo html_writer::tag('td', html_writer::tag('strong', get_string('pie_not_completed','block_reporting')), [
	'class' => 'pr-5 color-brand-1',
]);
echo html_writer::tag('td', html_writer::empty_tag('input',array('type'=>'text','name'=>'pie_notcom','value'=>$arr_pie[1],'class'=>'jscolor form-control mb-2')));
echo html_writer::end_tag('tr');

echo html_writer::start_tag('tr');
echo html_writer::tag('td', html_writer::tag('strong', get_string('pie_completed_highlight','block_reporting')), [
	'class' => 'pr-5 color-brand-1',
]);
echo html_writer::tag('td', html_writer::empty_tag('input',array('type'=>'text','name'=>'pie_comhigh','value'=>$arr_pie[2],'class'=>'jscolor form-control mb-2')));
echo html_writer::end_tag('tr');

echo html_writer::start_tag('tr');
echo html_writer::tag('td', html_writer::tag('strong', get_string('pie_not_completed_highlight','block_reporting')), [
	'class' => 'pr-5 color-brand-1',
]);
echo html_writer::tag('td', html_writer::empty_tag('input',array('type'=>'text','name'=>'pie_notcomhigh','value'=>$arr_pie[3],'class'=>'jscolor form-control mb-2')));
echo html_writer::end_tag('tr');

echo html_writer::start_tag('tr');
echo html_writer::tag('td', html_writer::tag('h3',get_string('bar_chart','block_reporting'), [
	'class' => 'mt-3',
]), [
	'colspan' => 2,
]);
echo html_writer::end_tag('tr');

echo html_writer::start_tag('tr');
echo html_writer::tag('td', html_writer::tag('strong', get_string('bar_completed','block_reporting')), [
	'class' => 'pr-5 color-brand-1',
]);
echo html_writer::tag('td', html_writer::empty_tag('input',array('type'=>'text','name'=>'bar_com','value'=>$arr_bar[0],'class'=>'jscolor form-control mb-2')));
echo html_writer::end_tag('tr');

echo html_writer::start_tag('tr');
echo html_writer::tag('td', html_writer::tag('strong', get_string('bar_net_completed','block_reporting')), [
	'class' => 'pr-5 color-brand-1',
]);
echo html_writer::tag('td', html_writer::empty_tag('input',array('type'=>'text','name'=>'bar_notcom','value'=>$arr_bar[1],'class'=>'jscolor form-control mb-2')));
echo html_writer::end_tag('tr');

echo html_writer::start_tag('tr');
echo html_writer::tag('td', html_writer::tag('h3',get_string('courseoverview_chart','block_reporting'), [
	'class' => 'mt-3',
]), [
	'colspan' => 2,
]);
echo html_writer::end_tag('tr');

echo html_writer::start_tag('tr');
echo html_writer::tag('td', html_writer::tag('strong', get_string('courseoverview_percentage','block_reporting')), [
	'class' => 'pr-5 color-brand-1',
]);
echo html_writer::tag('td', html_writer::empty_tag('input',array('type'=>'text','name'=>'cour_per','value'=>$arr_course[1],'class'=>'jscolor form-control mb-2')));
echo html_writer::end_tag('tr');

echo html_writer::start_tag('tr');
echo html_writer::tag('td', html_writer::tag('strong', get_string('courseoverview_bg','block_reporting')), [
	'class' => 'pr-5 color-brand-1',
]);
echo html_writer::tag('td', html_writer::empty_tag('input',array('type'=>'text','name'=>'cour_bg','value'=>$arr_course[0],'class'=>'jscolor form-control mb-2')));
echo html_writer::end_tag('tr');

if($PDF_ENABLE){
	echo html_writer::start_tag('tr');
	echo html_writer::tag('td', html_writer::tag('h3',get_string('othersetting','block_reporting')), [
		'colspan' => 2,
	]);
	echo html_writer::end_tag('tr');

	echo html_writer::start_tag('tr');
	echo html_writer::tag('td', html_writer::tag('strong', get_string('pageorientation','block_reporting')), [
		'class' => 'pr-5 color-brand-1',
	]);
	echo html_writer::tag('td', html_writer::select(array(LAYOUT_PORTRAIT=>'Portrait',LAYOUT_LANDSCAPE=>'Landscape'),'pagelayout',$pagelayout, false, array('class'=>' form-control mb-2') ));
	echo html_writer::end_tag('tr');

	echo html_writer::start_tag('tr');
	echo html_writer::tag('td', html_writer::tag('strong', get_string('reportlogo','block_reporting')), [
		'class' => 'pr-5 color-brand-1',
	]);
	echo html_writer::tag('td', "<input type='file' class='form-control col-md-3' name='filelogo' id='filelogo' accept='image/*'><br>" . $default_logo);
	echo html_writer::end_tag('tr');
	// echo html_writer::label(get_string('reportlogo:hint','block_reporting'),null,false,array());
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo html_writer::empty_tag('input',array('type'=>'submit','name'=>'sub', 'class'=>'btn btn-primary','value'=>get_string('savechanges')));

echo html_writer::end_tag('form');

echo $OUTPUT->footer();
?>
