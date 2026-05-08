<?php
/**
 * @package    block_reportwizard
 * @copyright  Mindatlas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

// namespace block_reportwizard;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/blocks/reportwizard/src/classes/report.php');
require_once($CFG->dirroot.'/blocks/reportwizard/src/hierarchy-lib.php');
require_once($CFG->dirroot.'/blocks/reportwizard/src/lib.php');
require_once($CFG->dirroot.'/blocks/reportwizard/src/lib_report.php');
require_once($CFG->dirroot.'/lib/mindatlas/malib.php');

class block_reportwizard_reports_helper_base {

	const FILTER_ENABLED = '1';
	const FILTER_DISABLED = '0';

    const COLUMN_SHOW = '1';
    const COLUMN_HIDDEN = '0';

	public function user_public_reports($userid) {
        global $DB;

        $reports = array();
        $my_reports = $DB->get_records('report_wzd_report', array('access_type'=>block_reportwizard_report::ACCESS_PUBLIC, 'creator_id'=>$userid));

        $user_nodeid = $DB->get_record('hierarchy_user', array('user_id' => $userid))->node_id;
        $user_nodename = $DB->get_record('hierarchy_node', array('id' => $user_nodeid))->name;
        $shareto_records = $DB->get_records('report_wzd_shareto', array('node_name'=>$user_nodename));
        foreach ($shareto_records as $key => $shareto_record) {
            $report_record = $DB->get_record('report_wzd_report', array('id'=>$shareto_record->report_id, 'access_type'=>block_reportwizard_report::ACCESS_PUBLIC));

            if ($report_record) {
                $report = new block_reportwizard_report($report_record);

                // avoid duplicate data, if user is creator, it already added
                if ($report->creator_id == $userid) {
                    continue;
                }

                $reports[] = $report;
            }

        }



        foreach ($my_reports as $record) {
            $report = new block_reportwizard_report($record);
            $reports[] = $report;
        }

        return $reports;
	}

	public function user_private_reports($userid) {
		global $DB;

		$reports = array();

		$records = $DB->get_records('report_wzd_report', array('creator_id' => $userid, 'access_type'=>block_reportwizard_report::ACCESS_PRIVATE));

		foreach ($records as $record) {
			$report = new block_reportwizard_report($record);
			$reports[] = $report;
		}

		return $reports;
	}

    public function all_user_reports($userid) {
        return array_merge($this->user_public_reports($userid),$this->user_private_reports($userid));
    }

    // Deprecated: Manage filters page is included into edit and create report page
	public function enable_report_filter($report_id,$infofield_id,$enabled){
        global $DB;
        
        $record = $DB->get_record('report_wzd_infofield_filter', array('report_id'=>$report_id,'infofield_id'=>$infofield_id));

    	if ($enabled) {
    		$infofield_enabled = self::FILTER_ENABLED;
    	}else{
    		$infofield_enabled = self::FILTER_DISABLED;
    	}

        if ($record) {
        	$record->infofield_enabled = $infofield_enabled;
        	return $DB->update_record('report_wzd_infofield_filter', $record);
        }else{
        	$new_record = new stdClass();
        	$new_record->report_id = $report_id;
        	$new_record->infofield_id = $infofield_id;
        	$new_record->infofield_enabled = $infofield_enabled;
        	return $DB->insert_record('report_wzd_infofield_filter', $new_record);
        }
    }

    public function set_filter_display($report_id,$infofield_id,$display){
        global $DB;
        
        $record = $DB->get_record('report_wzd_infofield_filter', array('report_id'=>$report_id,'infofield_id'=>$infofield_id));

        if ($display) {
            $infofield_display = self::COLUMN_SHOW;
        }else{
            $infofield_display = self::COLUMN_HIDDEN;
        }

        if ($record) {
            $record->infofield_display = $infofield_display;
            return $DB->update_record('report_wzd_infofield_filter', $record);
        }else{
            $new_record = new stdClass();
            $new_record->report_id = $report_id;
            $new_record->infofield_id = $infofield_id;
            $new_record->infofield_display = $infofield_display;
            return $DB->insert_record('report_wzd_infofield_filter', $new_record);
        }
    }

    public function save_filter_value($report_id,$infofield_id,$value){
        global $DB;
        
        $record = $DB->get_record('report_wzd_infofield_filter', array('report_id'=>$report_id,'infofield_id'=>$infofield_id));

        // for menu type infofield put all values together
        if (gettype($value)=="array") {
            // @30/07/2018 enhancement
            $value = array_filter($value);
            // remove empty value
            foreach ($value as $key=>$item) {
                if ($item == "" || $item == "force_post" ) {
                    unset($value[$key]);
                }
            }
            if (empty($value)) {
                $value = '';
            } else{
                $value = json_encode($value);
            }
            // $value = implode(",", $value);
        }
        
        if ($record) {
        	$record->infofield_data = $value;
            $record->infofield_enabled = self::FILTER_ENABLED;
            return $DB->update_record('report_wzd_infofield_filter', $record);
        }else{
            $new_record = new stdClass();
            $new_record->report_id = $report_id;
            $new_record->infofield_id = $infofield_id;
            $new_record->infofield_enabled = self::FILTER_ENABLED;
            $new_record->infofield_data = $value;
            return $DB->insert_record('report_wzd_infofield_filter', $new_record);
        }
    }

    public function delete_old_value_filter($report_id,$updated_fields){
        global $DB;
        $rs = $DB->get_records('report_wzd_infofield_filter', array('report_id'=>$report_id));
        if(!empty($rs)){
            foreach ($rs as $row) {
                if(!in_array($row->infofield_id,$updated_fields)){
                    $DB->delete_records('report_wzd_infofield_filter',array('report_id'=>$report_id,'infofield_id'=>$row->infofield_id));
                }else continue;
            }
        }
    }

    public function share_to($report_id,$node_names){
        global $DB;

        // sip if it's a pricate report
        $access_type = $DB->get_fieldset_select('report_wzd_report', 'access_type', 'id = '.$report_id)[0];
        if ($access_type == block_reportwizard_report::ACCESS_PRIVATE) {
            return;
        }

        $DB->delete_records('report_wzd_shareto', array('report_id' => $report_id));

        $array_node_names = explode(",",$node_names);

        foreach ($array_node_names as $node_name) {
            $node_name = trim($node_name);
            $selected_node = $DB->get_record('hierarchy_node', array('name' => $node_name ));

            if (!$selected_node) {
                continue;
            }

            $shareto_nodes = reportwizard_find_children_nodes($node_name);
            $shareto_nodes[] = $selected_node;

            foreach ($shareto_nodes as $key => $shareto_node) {


                if (is_manager_node($shareto_node->id)) {

                    $new_record = new stdClass();
                    $new_record->report_id = $report_id;
                    $new_record->node_name = $shareto_node->name;
                    $new_record->timecreated = time();

                    $DB->insert_record('report_wzd_shareto', $new_record);
                }

            }

        }

    }

    public function get_results($report) {
        global $DB, $USER;

        $userid = $USER->id;
        if($userid==0) $userid = $report->creator_id;
        $current_nodeid = get_user_node_id($userid);

        // CONDITION FOR HIERARCHY NODES
        $reporton_nodes = array();
        $hierarchy_nodes = explode(', ', $report->hierarchy_nodes);
        foreach ($hierarchy_nodes as $key => $node_name) {
            $node_id = $DB->get_field('hierarchy_node','id',array('name'=>$node_name));
            if (!in_array($node_id, $reporton_nodes)) {
                $reporton_nodes[] = $node_id;
            }
            $all_children_nodes = \reporting\lib\find_children_nodes($node_id);
            foreach ($all_children_nodes as $key => $id) {
                if (!in_array($id, $reporton_nodes)) {
                    $reporton_nodes[] = $id;
                }
            }           
        }

        $reporton_nodes_2 = array();
        foreach ($reporton_nodes as $key=>$reporton_nodeid) {
            //Allow mindatlas site admin to see the whole report
            if (\reporting\lib\is_parent_node($current_nodeid, $reporton_nodeid) || is_siteadmin($userid)) {
                $reporton_nodes_2[] = $reporton_nodeid;
            }
        }

        if (empty($reporton_nodes_2)){
            $reporton_nodes_2[] = "False";
        } 

        $reporton_nodes_str = implode(',', $reporton_nodes_2);
        // echo '<pre>reporton_nodes_str: '.print_r($report,true).'</pre>';
        // echo '<pre>reporton_nodes_str: '.print_r($reporton_nodes_str,true).'</pre>';
        // echo '<pre>current_nodeid: '.print_r($current_nodeid,true).'</pre>';

        $sql = 'SELECT rwc.*, u.email FROM {report_wzd_completion} as rwc 
                INNER JOIN mdl_user u on u.id=rwc.user_id ';

        $sql.= 'WHERE u.deleted=0 and u.suspended=0 and rwc.node_id IN ('.$reporton_nodes_str.') ';

        // @30/07/2018 enhancement
        // CONDITION FOR CATEGORIES AND COURSES
        $category_arr = [];
        $course_arr = [];
        $course_conditions = [];
        if (!empty($report->object_id)){
            $report_course_and_category_ids = explode(',', $report->object_id);
            foreach ($report_course_and_category_ids as $key => $value) {
                if (strpos($value, '{category}') !== false) {
                    $pattern = '/{.*}(\d*)/';
                    $cat_id = preg_replace($pattern, '$1', $value);
                    $category_arr[] = $cat_id;
                    $sub_catids = reportwizard_get_all_sub_categories($cat_id);
                    if(!empty($sub_catids)){
                        $category_arr = array_merge($category_arr, $sub_catids);
                    }
                } else {
                    $course_arr[] = $value;
                }
            }
        }
        if (!empty($category_arr)){
            $course_conditions[] = ' rwc.category_id in (' . implode(',', $category_arr) . ')';
        }
        if (!empty($course_arr)){
            $course_conditions[] = ' rwc.course_id in (' . implode(',', $course_arr) . ')';
        }
        if (!empty($course_conditions)){
            $sql .= 'AND (' . implode(' OR ', $course_conditions) . ') ';
        }
        // switch ($report->object_type) {
        //     case block_reportwizard_report::OBJECT_CATEGORY:
        //         $sql.= 'AND category_id = '.$report->object_id.' ';
        //         break;
        //     case block_reportwizard_report::OBJECT_COURSE:
        //         // If object_type == OBJECT_COURSE and object_id == 1, means ALL courses
        //         $sql.= ($report->object_id!='1') ? 'AND course_id = '.$report->object_id.' ' : '';
        //         break;      
        //     default:
        //         break;
        // }   

        // CONDITON FOR ENROLMENT DATE
        if ($report->enrol_date_from != 0 && $report->enrol_date_to != 0) {
            // 86400 is the seconds in a day. Need to add this value to the enrol_date_to to get to the end of that date
            $enrol_date_to = $report->enrol_date_to + 86400;
            $sql.= 'AND rwc.course_enrolmentdate >= '.$report->enrol_date_from.' ';
            $sql.= 'AND rwc.course_enrolmentdate < '.$enrol_date_to.' ';
        } elseif ($report->enrol_date_from == 0 && $report->enrol_date_to != 0) {
            $enrol_date_to = $report->enrol_date_to + 86400;
            $sql.= 'AND rwc.course_enrolmentdate < '.$enrol_date_to.' ';
        } elseif ($report->enrol_date_from != 0 && $report->enrol_date_to == 0) {
            $sql.= 'AND rwc.course_enrolmentdate >= '.$report->enrol_date_from.' ';
        }

        // @30/07/2018 enhancement
        // CONDITION FOR COMPLETION DATE
        if($report->type != block_reportwizard_report::REPORT_TYPE_MANDATORY_ONLINE){
            if (!empty($report->complete_date_from)) {
                $sql .= 'AND rwc.activity_completiondate >= '. $report->complete_date_from. ' ';
            }
            if (!empty($report->complete_date_to)) {
                $complete_date_to = $report->complete_date_to + 86400;
                $sql .= 'AND rwc.activity_completiondate <= '. $complete_date_to. ' ';
            }
        }

        // CONDITION FOR COMPLETION STATUS
        // only works for general reports and course activity reports,
        // courseoverview reports need to include all completed and incomplete records to calculate course progress
        if ( $report->type == block_reportwizard_report::REPORT_TYPE_GENERAL || 
             $report->type == block_reportwizard_report::REPORT_TYPE_ACTIVITY) {
            switch ($report->completion_status) {
                case block_reportwizard_report::COMPLETION_COMPLETED:
                    $sql.= "AND ( rwc.activity_completionstatus = '1' OR rwc.activity_completionstatus = '2') ";
                    break;
                case block_reportwizard_report::COMPLETION_INCOMPLETE:
                    $sql.= "AND ( rwc.activity_completionstatus = '0' OR rwc.activity_completionstatus is null ) ";
                    break;      
                default:
                    break;
            }             
        }
        //Sort by name, course name
        $sql.=" ORDER BY rwc.user_fullname ASC, rwc.course_name ASC ";


        $results = $DB->get_records_sql($sql);

        return $results;

    }


    public function get_filtered_results($report){
        global $DB;

        $results = array();

        $raw_results = $this->get_results($report);

        // no filter enable settings now, so don't check infofield_enabled column here
        $report_filters = $DB->get_records('report_wzd_infofield_filter', array('report_id' => $report->id));

        // @30/07/2018 enhancement
        // ========= get users profiles in to users_profiles array ========
        $users_profiles = [];
        foreach ($raw_results as $key => $value) {
            $users_profiles[$value->user_id] = [];
        }
        // profile_get_user_fields_with_data: a moodle function
        foreach ($users_profiles as $key => $value) {    
            $profiles = profile_get_user_fields_with_data($key);
            foreach ($profiles as $p_key => $p_value) {
                $users_profiles[$key][$p_value->fieldid] = [ 
                    'datatype' => $p_value->field->datatype, 
                    'data' => $p_value->data
                ];
            } 
        }
        // var_dump($users_profiles);die();
        // var_dump($report_filters);die();
        // ===============================================================
        foreach ($raw_results as $raw_result) {
            $match = true;
            $raw_result->course_name = ma_lib_sanitize_string($raw_result->course_name);
            $raw_result->activity_name = ma_lib_sanitize_string($raw_result->activity_name);

            foreach ($report_filters as $report_filter) {
                if ($report_filter->infofield_data != '') {
                    // @30/07/2018 enhancement
                    $report_filter_data_arr = json_decode($report_filter->infofield_data, true);
                    if (!isset($users_profiles[$raw_result->user_id][$report_filter->infofield_id])){
                        $match = false;
                    } else {
                        $datatype = $users_profiles[$raw_result->user_id][$report_filter->infofield_id]['datatype'];
                        $user_infofield_value = $users_profiles[$raw_result->user_id][$report_filter->infofield_id]['data'];
                        switch ($datatype) {
                            case 'datetime':
                                // var_dump($report_filter_data_arr);die();
                                $from = null;
                                if (isset($report_filter_data_arr['from'])) {
                                    $from = strtotime(str_replace('/', '-', $report_filter_data_arr['from']).' 00:00');
                                }
                                $to = null;
                                if (isset($report_filter_data_arr['to'])) {
                                    $to = strtotime(str_replace('/', '-', $report_filter_data_arr['to']) . ' 23:59');
                                }
                                if ($user_infofield_value > $to || $user_infofield_value < $from){
                                    $match = false;
                                }
                                break;
                            case 'checkbox':
                                if ($user_infofield_value != $report_filter_data_arr) {
                                    $match = false;
                                }
                                break;
                            default:
                                if (!in_array($user_infofield_value, $report_filter_data_arr)) {
                                    $match = false;
                                }
                                break;
                        }
                    }
                    // $user_infofield_value = $DB->get_field('user_info_data', 'data', array('userid' => $raw_result->user_id, 'fieldid'=>$report_filter->infofield_id ));
                    
                    // if($user_infofield_value === false || $user_infofield_value ===''){
                    //     $match = false;                       
                    // }else{
                    //     // consider menu type infofield, compare string can cover both == and sub string
                    //     // 0 also == false, should use ===
                    //     if (strpos($report_filter->infofield_data, $user_infofield_value) === false) {
                    //         $match = false;
                    //     }   
                    // }
                }
            }

            if ($match) {
                $results[] = $raw_result;
            }
        }

        return $results;
    }

    public function report_csv_content($report) {
        global $DB;

        $results = $this->get_filtered_results($report);

        $report_extra_columns = $DB->get_records('report_wzd_infofield_filter', array('report_id'=>$report->id, 'infofield_display'=>block_reportwizard_reports_helper::COLUMN_SHOW));

        $content = new stdClass();
        $headers = array();
        $data_list = array();

        switch ($report->type) {
            case block_reportwizard_report::REPORT_TYPE_GENERAL:
                $headers[] = 'Full name';
                $headers[] = 'Course';
                $headers[] = 'Module';
                $headers[] = 'Completion status';
                $headers[] = 'Enrolled date';
                $headers[] = 'Completion date';
                $headers = $this->report_csv_add_extra_header($headers, $report_extra_columns);

                if (!empty($results)) {

                    foreach ($results as $key => $result) {
                        $status = '';
                        if ($result->activity_name=='scorm') {
                            if ($result->scormstatus=='completed' || $result->scormstatus=='passed') {
                                $status = get_string('completed', 'block_reportwizard');
                            } else $status = get_string('incomplete', 'block_reportwizard');
                        } elseif ($result->activity_completionstatus=='1') {
                            $status = get_string('completed', 'block_reportwizard');
                        } else $status = get_string('incomplete', 'block_reportwizard');

                         $data_list[$key]['Full name'] = $result->user_fullname;
                         $data_list[$key]['Course'] = $result->course_name;
                         $data_list[$key]['Module'] = $result->activity_name;

                         $data_list[$key]['Completion status'] = $status;
                         $data_list[$key]['Enrolled date'] = timestamp_to_date($result->course_enrolmentdate);     
                         $data_list[$key]['Completion date'] = timestamp_to_date($result->activity_completiondate);   

                         $data_list = $this->report_csv_add_extra_content($data_list, $key, $report_extra_columns, $result->user_id);

                    }
                }                

                break;


            case block_reportwizard_report::REPORT_TYPE_COURSEOVERVIEW:
                $headers[] = 'Full name';
                $headers[] = 'Course';
                $headers[] = 'Progress';
                $headers[] = 'Enrolled date';
                $headers[] = 'Completion date';
                $headers = $this->report_csv_add_extra_header($headers, $report_extra_columns);



                $courseoverviewreports = array();

                foreach ($results as $key => $record) {
                    $courseoverviewreports[$record->user_id][$record->course_id]['user_fullname']=$record->user_fullname;
                    $courseoverviewreports[$record->user_id][$record->course_id]['course_name']=$record->course_name;
                    $courseoverviewreports[$record->user_id][$record->course_id]['course_enrolmentdate']=$record->course_enrolmentdate;
                    $courseoverviewreports[$record->user_id][$record->course_id]['activity_completiondate'][]=$record->activity_completiondate;
                }

                $data = array();            

                foreach ($courseoverviewreports as $user_id => $records) {
                    $total_percent = 0;
                    foreach ($records as $course_id => $record) {
                        $total = 0;
                        $completed = 0;
                        $course_completiondate = 0;
                        foreach ($record['activity_completiondate'] as $key => $activity_completiondate) {
                            $total++;
                            if ( isset($activity_completiondate)&&($activity_completiondate>0) ) {
                                $completed++;
                                if ($activity_completiondate >= $course_completiondate) $course_completiondate = $activity_completiondate;
                            }
                        }
                        $percent = ($total>0) ? round($completed/$total,2)*100 : 0;

                        // CONDITION FOR COMPLETION STATUS
                        if ($report->completion_status == block_reportwizard_report::COMPLETION_COMPLETED && $percent < 100) {     
                            unset($courseoverviewreports[$user_id][$course_id]);
                            continue;
                        }
                        if ($report->completion_status == block_reportwizard_report::COMPLETION_INCOMPLETE && $percent  == 100) {
                            unset($courseoverviewreports[$user_id][$course_id]);
                            continue;
                        }
                        
                        $courseoverviewreports[$user_id][$course_id]['total_activity']=$total;
                        $courseoverviewreports[$user_id][$course_id]['completed_activity']=$completed;
                        $courseoverviewreports[$user_id][$course_id]['course_completion_percent'] = $percent;
                        $courseoverviewreports[$user_id][$course_id]['course_completiondate'] = ($total==$completed && $completed>0) ? $course_completiondate : 0;

                        $data[$course_id][$user_id][] = $percent;
                    }
                }

                foreach ($courseoverviewreports as $user_id=>$records) {
                    foreach ($records as $course_id => $record) {
                        $data_list[$user_id.'-'.$course_id]['Full name'] = $record['user_fullname'];
                        $data_list[$user_id.'-'.$course_id]['Course'] = $record['course_name'];
                        $data_list[$user_id.'-'.$course_id]['Progress'] = $record['course_completion_percent'].'%';
                        $data_list[$user_id.'-'.$course_id]['Enrolled date'] = timestamp_to_date($record['course_enrolmentdate']);
                        $data_list[$user_id.'-'.$course_id]['Completion date'] = timestamp_to_date($record['course_completiondate']);
                        $data_list = $this->report_csv_add_extra_content($data_list, $user_id.'-'.$course_id, $report_extra_columns, $user_id);
                    }
                }

                break;

            case block_reportwizard_report::REPORT_TYPE_MANDATORY_ONLINE:
                $schedule = block_reportwizard_report::report_get_schedule_details($report);
                $headers[] = 'Full name';
                $headers[] = 'Email';
                // $headers[] = 'Enrolled date';
                $header_completion_date  = 'Completion date';
                if(!empty($schedule) && $schedule->conds_from_date!=0){
                    $header_completion_date = 'Completion date from '.date('d/m/Y',$schedule->conds_from_date);
                }
                $headers[] = $header_completion_date;
                
                $headers = $this->report_csv_add_extra_header($headers, $report_extra_columns);



                $courseoverviewreports = array();

                foreach ($results as $key => $record) {
                    $courseoverviewreports[$record->user_id]['user_fullname']=$record->user_fullname;
                    $courseoverviewreports[$record->user_id]['email']=$record->email;
                    $courseoverviewreports[$record->user_id][$record->course_id]['course_name']=$record->course_name;
                    $courseoverviewreports[$record->user_id][$record->course_id]['course_enrolmentdate']=$record->course_enrolmentdate;
                    if($record->activity_completionstatus==1 || $record->activity_completionstatus==2){
                        $courseoverviewreports[$record->user_id][$record->course_id]['activity_completiondate'][]=$record->activity_completiondate;
                    }else{
                        $courseoverviewreports[$record->user_id][$record->course_id]['activity_completiondate'][] = 0;
                    }
                }

                $data = array();            
                $ignore = false;

                foreach ($courseoverviewreports as $user_id => $records) {
                    $total_percent = 0;
                    $course_completiondate = 0;
                    foreach ($records as $course_id => $record) {
                        if($ignore) continue;
                        if(!isset($record['activity_completiondate'])) continue;
                        $total = 0;
                        $completed = 0;
                        foreach ($record['activity_completiondate'] as $key => $activity_completiondate) {
                            $total++;
                            if ( isset($activity_completiondate)&&($activity_completiondate>0) ) {
                                $completed++;
                                if ($activity_completiondate >= $course_completiondate) $course_completiondate = $activity_completiondate;
                            }
                        }
                        $percent = ($total>0) ? round($completed/$total,2)*100 : 0;

                        // CONDITION FOR COMPLETION STATUS
                        if ($percent < 100 && $total !=$completed) { 
                            $ignore = true;    
                            unset($courseoverviewreports[$user_id]);
                            continue;
                        }
                        
                        $courseoverviewreports[$user_id][$course_id]['total_activity']=$total;
                        $courseoverviewreports[$user_id][$course_id]['completed_activity']=$completed;
                        $courseoverviewreports[$user_id][$course_id]['course_completiondate'] = $course_completiondate;

                    }
                    if(isset($courseoverviewreports[$user_id]) && $course_completiondate!=0){
                        $courseoverviewreports[$user_id]['completiondate'] = $course_completiondate;
                    }
                    $ignore = false;
                }

                foreach ($courseoverviewreports as $user_id=>$records) {
                    $completed_date = intval($records['completiondate']);
                    if(intval($schedule->conds_from_date) >= $completed_date) continue;
                    if (!empty($report->complete_date_from) && intval($report->complete_date_from) > $completed_date) continue;
                    if (!empty($report->complete_date_to) && intval($report->complete_date_to) < $completed_date ) continue;
                    $data_list[$user_id]['Full name'] = $records['user_fullname'];
                    $data_list[$user_id]['Email'] = $records['email'];
                    $data_list[$user_id][$header_completion_date] = timestamp_to_date($records['completiondate']);
                    $data_list = $this->report_csv_add_extra_content($data_list, $user_id, $report_extra_columns, $user_id);
                }
                // echo "<pre>".print_r($data_list,true)."</pre>"; die();
                break;

            case block_reportwizard_report::REPORT_TYPE_ACTIVITY:
                $headers[] = 'Full name';
                $headers[] = 'Enrolled date';

                $activityreports = array();

                foreach ($results as $key => $record) {
                    $status = '';
                    if ($record->activity_name=='scorm') {
                        if ($record->scormstatus=='completed' || $record->scormstatus=='passed') {
                            $status = get_string('completed', 'block_reportwizard');
                        } else $status = get_string('incomplete', 'block_reportwizard');
                    } elseif ($record->activity_completionstatus=='1') {
                        $status = get_string('completed', 'block_reportwizard');
                    } else $status = get_string('incomplete', 'block_reportwizard');

                    $activityreports[$record->user_fullname]['userid'] = $record->user_id;
                    $activityreports[$record->user_fullname]['course_enrolmentdate'] = $record->course_enrolmentdate;
                    $activityreports[$record->user_fullname]['activity'][$record->activity_name] = $status;
                }


                // headers
                foreach (current($activityreports)['activity'] as $activity_name => $status_value) {
                    $headers[] = $activity_name;
                }   
                $headers = $this->report_csv_add_extra_header($headers, $report_extra_columns);

                // datalist
                foreach ($activityreports as $fullname=>$record) {

                    $data_list[$fullname]['Full name'] = $fullname;
                    $data_list[$fullname]['Enrolled date'] = timestamp_to_date($record['course_enrolmentdate']);
                    foreach ($record['activity'] as $activity_name=>$status) {
                        $data_list[$fullname][$activity_name] = $status;
                    }
                    $data_list = $this->report_csv_add_extra_content($data_list, $fullname, $report_extra_columns, $record['userid']);
                }

                break;


        }



        $content->headers = $headers;
        $content->data_list = $data_list;

        return  $content;

    }

    public function report_csv_add_extra_header($headers, $colums){
        global $DB;

        if ($colums) {
            foreach ($colums as $key => $column) {
                $column_name = $DB->get_field('user_info_field', 'name', array('id'=>$column->infofield_id));
                if ($column_name) {
                    $headers[] = $column_name;
                }
            }
        }

        return $headers;

    }

    public function report_csv_add_extra_content($data_list, $key, $colums, $userid){
        global $DB,$USER;

        if ($colums) {
            foreach ($colums as $column) {
                $column_name = $DB->get_field('user_info_field', 'name', array('id'=>$column->infofield_id));
                $column_datatype = $DB->get_field('user_info_field', 'datatype', array('id'=>$column->infofield_id));
                $column_value = $DB->get_field('user_info_data', 'data', array('fieldid'=>$column->infofield_id, 'userid'=>$userid));
                if ($column_value) {
                    if($column_datatype=='datetime') $column_value = date('d/m/Y',$column_value);
                    else if($column_datatype=='checkbox') 
                        $column_value = ($column_value==1)? "Yes" : "No";

                    $data_list[$key][$column_name] = $column_value;
                }else{
                    $data_list[$key][$column_name] = '';
                }
            }
        }

        return $data_list;
    }


}

class block_reportwizard_reports_helper extends block_reportwizard_reports_helper_base {
    public static function save_reportwizard_schedule($data){
        global $DB,$USER;
        $reportid = $data->reportid;
        $record = $DB->get_record('report_wzd_schedule',array('report_id'=>$data->reportid));
        if(empty($record)){
            $record = (object)[
                'report_id'=>$reportid,
                'report_format'=>$data->reportformat,
                'cohortid'=>$data->cohortid,
                'startdate'=>$data->startdate,
                'frequency'=>$data->frequency,
                'userid'=>$USER->id,
                'nextrun'=>$data->startdate,
                'timecreated'=>time()
            ];
            $DB->insert_record('report_wzd_schedule',$record);
        }else{
            $record->report_format = $data->reportformat;
            $record->cohortid = $data->cohortid;
            if (isset($data->startdate)) {
                $record->startdate = $data->startdate;
                $record->nextrun = $data->startdate;
            }
            if (isset($data->frequency)) {
                $record->frequency = $data->frequency;
            }
            $record->timecreated = time();
            $DB->update_record('report_wzd_schedule',$record);
        }
    } 
}