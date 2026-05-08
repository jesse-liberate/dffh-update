<?php
require_once(realpath(dirname(__FILE__)) . '/../../../../config.php');
require_once(__DIR__ . '/../lib.php');

defined('MOODLE_INTERNAL') || die();

global $USER, $DB;
$DBH = new PDO("mysql:host=$CFG->dbhost;dbname=$CFG->dbname", $CFG->dbuser, $CFG->dbpass, array(
	PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"
));

// /*----------Truncate the table----------*/
// try {
//     $transaction = $DB->start_delegated_transaction();
// 	$trunc_tbl = "TRUNCATE table {report_wzd_completion}";
// 	$DB->execute($trunc_tbl);
//     $transaction->allow_commit();
// } catch(Exception $e) {
//     echo "Error : Process cannot clear all existing data" . "</br>";
//     $transaction->rollback($e);
// }

// echo "Successful: Clear all existing data" . "</br>";

/*----------Get data and insert into the mdt_report_wzd_completion table----------*/
$where="";
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
		 c.id as courseid,c.fullname as coursename, m.name as module, cm.completion ,
		 cmc.completionstate as completionstatus,ue.timecreated as enrolleddate, cmc.timemodified as completiondate,
		 cm.instance as instance, ".$activityselect."
		 scormname.sid as scormid, scormname.name as scormname, scormtrack.value as scormstatus

		 FROM mdl_user as u 
		 INNER JOIN (
			SELECT ue.userid,e.courseid,min(ue.timecreated) as timecreated
			FROM mdl_user_enrolments ue
			inner join mdl_enrol e on e.id=ue.enrolid
			group by ue.userid, e.courseid
		 ) ue on ue.userid=u.id   
		 LEFT JOIN mdl_course c On c.id = ue.courseid 
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
	echo "<pre>Query: ".print_r($query, true)."</pre></br>";
	echo $query;
	die();
	
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
			$record->timecreated = time();

			// To make sure that each record in the master table is unique
			if ( !$DB->record_exists('report_wzd_completion',array('user_id'=>$value['userid'],'course_id'=>$value['courseid'],'activity_instance_id'=>$value['instance'],'activity_type'=>$value['module'])) ) {
				$DB->insert_record('report_wzd_completion', $record);
			}
		}
	}
	
    $transaction->allow_commit();

    echo "Successful: Records inserted." . "</br>";
} catch(Exception $e) {
    $transaction->rollback($e);
    echo "Error: process cannot insert successfully ." . "</br>";
    exit();
}
?>