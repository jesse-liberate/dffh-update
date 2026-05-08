<?php
if (!defined('CLI_SCRIPT')) {
	define('CLI_SCRIPT', true);
}
define('TWO_DAYS',172800);
define('ONE_DAY',86400);
require_once(realpath(dirname(__FILE__)) . '/../../../../config.php');
require_once(__DIR__ . '/../lib.php');

defined('MOODLE_INTERNAL') || die();

//Check if the full script is running, then this process is stopped.
$full_script_running = get_config('block_reportwizard', 'completion_full_cron_running');
if($full_script_running==1) exit();


global $USER, $DB;
$DBH = new PDO("mysql:host=$CFG->dbhost;dbname=$CFG->dbname", $CFG->dbuser, $CFG->dbpass, array(
	PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"
));



//GET ALL USERS WHO ACCESS RECENTLY. 
//TO REDUCE THE CALCULATION FOR ALL USERS, WE ONLY UPDATE WITH USES WHO ACCESS RECENTLY

$plugin_name = 'block_reportwizard';
$prop_name = 'last_cron_run_insert_wzd_completion_table';

$last_cron_run = get_config($plugin_name, $prop_name);
// if last cron run is not set, it will be set to 1 hour ago
if ($last_cron_run == false) {
  $last_cron_run = time() - (60 * 60);
}
set_config($prop_name, time(), $plugin_name);

$arr_users_recently=array();
// $recently_days = time() - TWO_DAYS;
// This doesn't sound right and therefore it's commented out. This is based on user access. 
// But site admin can mark activity as complete without user access.
//$sql_recently='SELECT id from mdl_user where lastaccess>='.$recently_days;

// get userid where course module completion record timemodified has changed since the last cron run
$sql_recently = 'select distinct userid as id from {course_modules_completion} '
              . 'where timemodified >= ?';

$rs_recently = $DB->get_records_sql($sql_recently, array($last_cron_run));
if(!empty($rs_recently)){
	foreach ($rs_recently as $row) {
		$arr_users_recently [] = $row->id;
	}
}
if(!empty($arr_users_recently)){
	//ONLY DELETE USES WHO RECENTLY ACCESS, THEN UPDATE IT BACK AGAIN
	$list_users = implode(",", $arr_users_recently);
	echo "Users completion recently: ".$list_users. "</br>\n";

	/*----------Get data and insert into the mdt_report_wzd_completion table----------*/
	$where="";
	if($DB->record_exists('report_wzd_completion',array())){
		$where = " WHERE u.id in(".$list_users.") ";
	}
	try {
	    $transaction = $DB->start_delegated_transaction();

	    $activitylist = $DB->get_fieldset_select('modules', 'name', null);

	    $activityselect = '';
	    $activityjoin = '';

	    foreach ($activitylist as $key => $activityname) {
	    	// Scorm has a another query
	    	if ($activityname != 'scorm') {
	    		
	    		$activityselect .= $activityname.'.name as '.$activityname.'name, ';
	    		
	    		$activityjoin .= 'LEFT OUTER JOIN (';
	    		$activityjoin .= 'SELECT Distinct mdl_'.$activityname.'.name name, mdl_'.$activityname.'.course cid, mdl_'.$activityname.'.id id ';
			 	$activityjoin .= 'FROM mdl_'.$activityname.', mdl_course ';
			  	$activityjoin .= 'Where mdl_'.$activityname.'.course = mdl_course.id ';
			 	$activityjoin .= ' ) as '.$activityname; 
				$activityjoin .= ' ON ( '.$activityname.'.cid = c.id and cm.instance = '.$activityname.'.id and m.name = "'.$activityname.'" )';
	    	}
	    }

	    $query = "
	    Select u.id as userid, u.deleted as deleted, u.suspended as suspended, u.confirmed as confirmed, u.firstname as firstname, u.lastname as lastname, cm.id as coursemoduleid, 
			 ue.enrolid,e.courseid,c.fullname as coursename, m.name as module, cm.completion ,
			 cmc.completionstate as completionstatus,ue.timecreated as enrolleddate, cmc.timemodified as completiondate,
			 cm.instance as instance, ".$activityselect."
			 scormname.sid as scormid, scormname.name as scormname, scormtrack.value as scormstatus

			 FROM mdl_user as u 
			 LEFT JOIN mdl_user_enrolments ue On u.id = ue.userid
			 LEFT JOIN mdl_enrol e On e.id = ue.enrolid
			 LEFT JOIN mdl_course c On c.id = e.courseid
			 LEFT JOIN mdl_course_modules cm On c.id = cm.course
			 LEFT JOIN mdl_modules m On m.id = cm.module
			 LEFT JOIN mdl_course_modules_completion cmc On (cmc.userid = u.id AND cmc.coursemoduleid=cm.id)"
			 .$activityjoin."
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
			".$where." 
			ORDER BY u.id, c.id
			";

		$DBH->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
	  	$query = sprintf($query);
		$STH = $DBH->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		// echo "<pre>Query: ".print_r($query, true)."</pre></br>";
		// echo $query;

		$STH->execute();
		$STH->setFetchMode(PDO::FETCH_ASSOC);

		// echo "<pre>STH: ".print_r($rows, true)."</pre>";

		while ($value = $STH->fetch()) {
			if(($value["completion"] == '')||($value["completion"]===null)||($value["completion"] == 0))
					continue;
			// Nn need to insert Guest user (id = 1), deleted user and suspended users
			if ($value['userid'] != 1 && !$value['deleted'] && !$value['suspended'] && $value['confirmed']) {
				$category_id = $DB->get_field('course', 'category', array('id'=>$value['courseid']));
				$category_name = $DB->get_field('course_categories', 'name', array('id'=>$category_id));

				$record = new stdClass();
				$record->user_id = $value['userid'];
				$record->user_fullname = $value['firstname'].' '.$value['lastname'];

				if (is_hierarchy_installed_RW()) {
					$node_id = $DB->get_field('hierarchy_user','node_id',array('user_id' => $value['userid']));
					if ($node_id == false) {
						$record->node_id = NULL;				
						$record->node_description = NULL;
					} else {
						$record->node_id = $node_id;				
						$record->node_description = $DB->get_field('hierarchy_node','description',array('id'=>$node_id));
					}
				} else {
					$record->node_id = NULL;
					$record->node_description = NULL;
				}

				$record->course_id = $value['courseid'];		
				$record->course_name = $value['coursename'];
				$record->category_id = $category_id;
				$record->category_name = $category_name;
				$record->activity_instance_id = $value['instance'];

				$record->course_enrolmentdate = $value['enrolleddate'];

				$record->activity_type = $value['module'];
				foreach ($value as $k => $v) {
					if (strpos($k, 'name') !== false) {
						if ($k != 'firstname' && $k != 'lastname' && $k != 'coursename' && $v != '') {
							$record->activity_name = $v;
						}
					}
				}
				$record->scormstatus = $value['scormstatus'];
				$record->activity_completionstatus = $value['completionstatus'];
				$record->activity_completiondate = $value['completiondate'];
				

				// To make sure that each record in the master table is unique
				$record_existing = $DB->get_record('report_wzd_completion',array(
					'user_id'=>$value['userid'],
					'course_id'=>$value['courseid'],
					'activity_instance_id'=>$value['instance'],
					'activity_type'=>$value['module'],
				));
				if (empty($record_existing)) {
					$record->timecreated = time();
					$DB->insert_record('report_wzd_completion', $record);
				}else{
					// var_dump($record_existing);
					if( $record_existing->scormstatus!=$value['scormstatus'] ||
						$record_existing->activity_completionstatus!=$value['completionstatus'] ||
						$record_existing->activity_completiondate!=$value['completiondate']
					){
						$record_existing->scormstatus = $value['scormstatus'];
						$record_existing->activity_completionstatus = $value['completionstatus'];
						$record_existing->activity_completiondate = $value['completiondate'];
						$DB->update_record('report_wzd_completion',$record_existing);
					}
				}
			}
		}
		
	    $transaction->allow_commit();

	    echo "Successful: Records updated." . "</br>";
	} catch(Exception $e) {
	    $transaction->rollback($e);
	    echo "Error: process cannot insert successfully ." . "</br>";
	    exit();
	}
}
?>