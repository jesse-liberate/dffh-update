<?php
require_once($CFG->dirroot.'/lib/mindatlas/phpwkhtmltopdf/BaseCommand.php');
require_once($CFG->dirroot.'/lib/mindatlas/phpwkhtmltopdf/Command.php');
require_once($CFG->dirroot.'/lib/mindatlas/phpwkhtmltopdf/File.php');
require_once($CFG->dirroot.'/lib/mindatlas/phpwkhtmltopdf/Image.php');
require_once($CFG->dirroot.'/lib/mindatlas/phpwkhtmltopdf/Pdf.php');

define('LAYOUT_PORTRAIT',1);
define('LAYOUT_LANDSCAPE',2);
define('PDF_ENABLE',2);

function get_report_pdf_configuration($standard_options){
	global $DB;
	$arr = array(
		'no-outline',
	    'margin-top' => 30,
		'margin-bottom' => 20,
		'margin-right' => 10,
		'margin-left' => 20,
		'header-spacing' => 5,
		'footer-spacing' => 5,
		'header-html' => "<!DOCTYPE html>".html_writer::img($standard_options['reportlogo'], '', array('style' => 'height:70px')),
		'footer-right' => 'Page [page] of [toPage]',
		'binary' => 'wkhtmltopdf',
		'ignoreWarnings' => true,
		'orientation' => $standard_options['pagelayout'],
		'commandOptions' => array(
	        'useExec' => true,// Can help if generation fails without a useful error message
	        'procEnv' => array(
	            // Check the output of 'locale' on your system to find supported languages
	            'LANG' => 'en_US.utf-8',
	        ),
	        'procOptions' => array(
	            // This will bypass the cmd.exe which seems to be recommended on Windows
	            'bypass_shell' => true,
	        ),
	    ),
	);
	return $arr;
}

function insert_or_update_picture_to_moodle($file, $fid = null,$component,$filearea) {
    $tmp_name = $file["tmp_name"];
    // basename() may prevent filesystem traversal attacks;
    // further validation/sanitation of the filename may be appropriate
    $name = basename($file['name']);

    // generate a random num for itemid
    $itemid = time();
    // moodle function->save to 'files' table
    $context = context_system::instance();
    $fs = get_file_storage();
    $file_record = array(
        'contextid'=>$context->id,
        'component'=>$component,
        'filearea'=>$filearea,
        'itemid'=> $itemid,
        'filepath'=>'/',
        'filename'=>$name,
        'timecreated'=>time(),
        'timemodified'=>time()
    );
    if ($fid != null) {
        $oldfile = $fs->get_file_by_id($fid);
        if ($oldfile){
            $oldfile->delete();
            $oldfile->get_parent_directory()->delete();
        }
    }
    $newfile = $fs->create_file_from_pathname($file_record, "$tmp_name");
    return $newfile->get_id();
}
function get_report_pdf_options(){
	global $DB;
	$options = array(
		'pagelayout'=>LAYOUT_PORTRAIT,
		'reportlogo'=>new moodle_url('/blocks/reporting/report/img/defaultlogo.png')
	);
	$reportlogo = $DB->get_record('report_setting',array('name'=>'reportlogo'));
	if(!empty($reportlogo)){
		$options['reportlogo'] = new moodle_url('/blocks/reporting/report/gettype.php',array('id'=>$reportlogo->setting,'file'=>'download'));
	}
	$pagelayout = $DB->get_record('report_setting',array('name'=>'pagelayout'));
	if(!empty($pagelayout)){
		$options['pagelayout'] = $pagelayout->setting;
	}
	switch (intval($options['pagelayout'])) {
		case 2: $options['pagelayout'] = 'landscape';
			break;
		default:
			$options['pagelayout'] = 'portrait';
			break;
	}
	return $options;
}
function get_report_pdf_functionality_enable(){
	global $DB;
	$field = $DB->get_field('report_setting','setting',array('name'=>'report_pdf'));
	switch (intval($field)) {
		case 2:
			return true;
			break;
		default:
			return false;
			break;
	}
}