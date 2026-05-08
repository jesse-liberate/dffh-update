<?php

require_once('classes/report.php');


function creator_type($user_id) {
	if(is_siteadmin($user_id)) {
		return '1';
	}elseif (is_manager_node($user_id)) {
		return '2';
	}
}

function is_manager_node($node_id) {
	global $DB;

	$result = false;

	$records = $DB->get_records_sql('select parent_node_id from mdl_hierarchy_node group by parent_node_id', array(''));

	foreach ($records as $key => $record) {
		if ($node_id == $record->parent_node_id) {
			$result = true;
		}
	}

	return $result;
	
}

function is_manager_user($user_id) {
	global $DB;

	$result = false;

	$user_node = get_user_nodeid($user_id);
	if ($user_node) {
		return is_manager_node($user_node);
	}

	return $result;
	
}


function get_user_nodeid($user_id){
	global $DB;

	$user_node  = $DB->get_record('hierarchy_user', array('user_id' => $user_id));
	if ($user_node) {
		// return $user_node->id;
		return $user_node->node_id;
	}else{
		return false;
	}
}

// Transfer datepicker date sting to php timestamp
// Forward slash (/) signifies American M/D/Y formatting, a dash (-) signifies European D-M-Y and a period (.) signifies ISO Y.M.D.
function date_to_timestamp($dmy) {
	$dmy = str_replace("/","-",$dmy);
	return strtotime($dmy);
}


function timestamp_to_date($time, $format = 'd/m/Y') {
	if (!is_null($time) && $time!='0') {
		return date($format,$time);
	}else{
		return '';
	}
}


function is_hierarchy_installed_RW(){
	global $DB;
	if($DB->record_exists('config_plugins',array('plugin'=>'tool_hierarchy'))) return true;
	return false;
}


function can_access_report($report_id, $user_id) {
	global $DB;

	if ( is_siteadmin($user_id) ) return true;

	$node_id = $DB->get_field('hierarchy_user','node_id',array('user_id'=>$user_id));

	if ( !is_hierarchy_installed_RW() || $node_id==false || !$DB->record_exists('report_wzd_shareto', array('report_id'=>$report_id)) ) return false;

	$node_name = $DB->get_field('hierarchy_node','name',array('id'=>$node_id));

	// If there is a record of both node_name and report_id --> this user can access
	if ( $DB->record_exists('report_wzd_shareto', array('node_name'=>$node_name, 'report_id'=>$report_id)) ) return true;

	return false;
}


function get_coursecat_from_post($category_course) {
	global $DB;

	$type = '';
	$id = '';

	if (strpos($category_course, '{category}') === 0) { //it is category
		$type = block_reportwizard_report::OBJECT_CATEGORY;
		$id = substr($category_course, strlen('{category}'));
	}else{ // it is course
		$type = block_reportwizard_report::OBJECT_COURSE;
		$id = $category_course;
	}

	return array('type' => $type, 'id' => $id );

}
//Some node names do not matched with the names showing on the hierarchy. Therefore, it will be transfered to node id
function get_db_nodenames_from_nodeids($nodeids){
	global $DB;
	if(trim($nodeids)=="") return '';
	else{
		$nodenames = $DB->get_fieldset_sql('SELECT name from mdl_hierarchy_node where id in('.$nodeids.')',array());
		return implode(', ', $nodenames);
	}
}

function get_nodeids_from_nodes($nodenames){
	global $DB;
	if(trim($nodenames)=="") return '';
	else{
		$arr_nodes = explode(', ', $nodenames);
		$nodeids = array();
		foreach ($arr_nodes as $nodename) {
			$nodeids [] = $DB->get_field('hierarchy_node','id',array('name'=>$nodename));
		}
		return implode(',', $nodeids);
	}
}


function get_list_available_cohort(){
	global $DB;
	$data = array(''=>'None','-1'=>'Myself');
	$rs =  $DB->get_records('cohort',[],'id,name');
	if(!empty($rs)){
		foreach ($rs as $row) {
			$data[$row->id] = $row->name. ' (cohort)';
		}
	}
	return $data;
}
function run_reportwizard_schedule(){
	global $DB,$PAGE,$CFG;
	require_once($CFG->dirroot.'/blocks/reportwizard/renderer.php');
	require_once($CFG->dirroot.'/blocks/reportwizard/src/classes/report.php');
	$DB->execute("DELETE FROM mdl_report_wzd_schedule where report_id not in(select id from mdl_report_wzd_report)");
	$records = $DB->get_records('report_wzd_schedule',array());
	if(!empty($records)){
		foreach ($records as $row) {
			//Ignore if the system does not schedule to send to anyone
			if($row->cohortid=="" || $row->cohortid==0) continue;
			if($row->frequency=="None") continue; //Ignore if the schedule is disable "None"
			// if($row->startdate > time()) continue; // ignore if the schedule has not come yet. 
			$reportwizard = $DB->get_record('report_wzd_report',array('id'=>$row->report_id));
			$row->name = $reportwizard->name;
			$row->creator_id = $reportwizard->creator_id;

	        $nextrundate = strtotime(date('Y/m/d',$row->nextrun)); // convert the date to become the first of the day at 12:00am
	        $today = strtotime(date('Y/m/d'));

	        $canrun = $nextrundate <= $today ? true : false;

	        $recipients = array($row->creator_id);
	        
	        if($row->cohortid!=-1){
	        	$more_users = $DB->get_fieldset_select('cohort_members','userid','cohortid=?',[$row->cohortid]);
	        	foreach ($more_users as $uid) {
	        		if(!in_array($uid, $recipients)) $recipients [] = $uid; 
	        	}
	        }
			// echo "<pre>".print_r($row,true)."</pre>"; var_dump($canrun);
	        if ($canrun && !empty($reportwizard)) {

	        	$report_row = new block_reportwizard_report($reportwizard);
	        	$output = $PAGE->get_renderer('block_reportwizard');
	        	$fulldata = $output->get_report_data($report_row);

	            switch ($row->frequency) {
	                case 'Daily': $nextrun_new = strtotime('1 day', $row->nextrun); break;
	                case 'Weekly': $nextrun_new = strtotime('1 week', $row->nextrun); break;
	                case 'Fortnightly': $nextrun_new = strtotime('1 fortnight', $row->nextrun); break;
	                case 'Monthly': $nextrun_new = strtotime('1 month', $row->nextrun); break;
	                case 'Quarterly': $nextrun_new = strtotime('3 months', $row->nextrun); break;
	                case 'Yearly': $nextrun_new = strtotime('1 year', $row->nextrun); break; 
	                default:
	                    # code...
	                    break;
	            }
				// if(count($fulldata)<=1) continue;
	            //Add Paul and Eric in the CC
	            // $recipients [] = 9862; $recipients [] = 4213; $recipients [] = 9782;
				//If no CSV result, ignore to send email
	        	send_reportwizard_CSV($row, $fulldata, 'csv', $recipients);
	        	// send_reportwizard_CSV($row, $fulldata, 'csv', [4213,9862]);


	            $row->lastrun = time(); //strtotime(date('Y/m/d'));
	            $row->nextrun = $nextrun_new;
	            $DB->update_record('report_wzd_schedule', $row);
	        }
		}
	}
}
function send_reportwizard_CSV($report, $fulldata, $type, $recipients) {
	global $CFG;

	if (defined('REPORT_INFO')) {
		global $DEBUG_RESULTS;
	}

	/*=======================GENERATE CSV FILE ON SERVER=======================*/
	$reportfolder = 'reportwizard';

	$reportfolder_path = $CFG->dataroot.'/'.$reportfolder;
	if (!file_exists($reportfolder_path)) mkdir($reportfolder_path, 0777, true);

	$filename = preg_replace('/\s+/', '', $type).'_'.$report->id.'_'.date('Y-m-d', time()).'.csv';
	$filepath = $reportfolder_path.'/'.$filename;
	$csv = fopen($filepath, 'w');
	$line = array();

	if ($csv === false) {
		echo("$filepath cannot be created (Possible problem with permission)");
		return;
	}
	foreach ($fulldata as $data_row) {
		// var_dump($data_row)
		fputcsv($csv, $data_row);
	}
	
	fclose($csv);
	if (defined('REPORT_INFO')) {
		$DEBUG_RESULTS[] = 'File has been saved at: ' . $filepath . " <a class='btn' href='".$CFG->wwwroot."/admin/tool/automatedreport/viewfile.php?id=".$report->id."&f=".$filename."'>Download</a>";
	}
	/*============================================================================================*/
	$attachment = $reportfolder . '/' . $filename;

	send_email_reportwizard($report, $recipients, $attachment, $filename);
}

function send_email_reportwizard($report, $recipients, $attachment, $attachment_name) {
	global $DB, $SITE, $CFG;

	$destination = $CFG->dataroot."/reportwizard_emails_out.log";

	$is_debug = defined('REPORT_INFO');

	if ($is_debug) {
		global $DEBUG_RESULTS;
	}

	$supportuser = core_user::get_support_user();
	$supportuser->username = $supportuser->email;
	$count = 1;
	$owner_record = $DB->get_record('user',array('id'=>$report->creator_id));
	$owner = $owner_record->firstname." ".$owner_record->lastname;

	foreach ($recipients as $key => $userid) {
		// Get required fields for sending email in moodle
		$recipient = $DB->get_record_sql('SELECT id,
				username,
				email,
				firstname,
				lastname,
				maildisplay,
				mailformat,
				firstnamephonetic,
				lastnamephonetic,
				middlename,
				alternatename
			FROM {user} WHERE id = ?', array(
				$userid,
		));

		if ($recipient !== false) {
			$today = date('j F Y');
			$subject = "{$SITE->fullname}: {$report->name}";
			$result_criteria_display = 'No filter for results';
			$result_criteria_display_html = '<p>No filter for results</p>';

$messagetext = <<<EOT
Hi {$recipient->firstname},

Please find attached the {$report->name} for $today.


You are receiving this report created by $owner. If you no longer wish to receive these reports, please contact $owner_record->email .

{$supportuser->firstname} {$supportuser->lastname}
{$supportuser->email}
EOT;
$messagehtml = <<<EOT
Hi {$recipient->firstname},<br/>
<br/>
Please find attached the {$report->name} for $today. 

You are receiving this report created by $owner. If you no longer wish to receive these reports, please contact $owner_record->email .<br/>
<br/>
{$supportuser->firstname} {$supportuser->lastname}<br/>
{$supportuser->email}
EOT;
			// Use moodle's email_to_user to send email
			if (!$is_debug) {
				email_to_user($recipient, $supportuser, $subject, $messagetext, $messagehtml,
					// $attachment and $attachname are required for sending attachment
					$attachment, $attachment_name);
				$line = date('Y-m-d').", To:  ".$recipient->email."\n";
				error_log($line, 3, $destination);
				echo "<br>Email has been sent to ".$recipient->email;
			} else {
				$mailformat = $recipient->mailformat === '1' ? 'HTML/Text' : 'Text only';

				$DEBUG_RESULTS[] = '';
				$DEBUG_RESULTS[] = "From: {$supportuser->firstname} {$supportuser->lastname} ({$supportuser->email})";
				$DEBUG_RESULTS[] = "To: $recipient->firstname $recipient->lastname ($recipient->email)";
				$DEBUG_RESULTS[] = "Subject: $subject";

				if ($recipient->mailformat === '1') {
					$DEBUG_RESULTS[] = '<div class="email-message">' . $messagehtml . '</div>';
				} else {
					$DEBUG_RESULTS[] = '<pre>' . $messagetext . '</pre>';
				}
			}
			$count++;
		} elseif ($is_debug) {
			$DEBUG_RESULTS[] = 'Cannot find user info of id: ' . $userid;
		}
	}
}