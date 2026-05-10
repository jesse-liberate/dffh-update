<?php

/**
 * general_report.php
 * Created by Fan Li on 2/02/2021.
 *
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 * To further improve query performance, please create index on mdl_hierarchy_user.node_id!!!!!!!!!!!!
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 */


define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/ajax_controller.php');
require_once($CFG->dirroot.'/mod/facetoface/lib_mindatlas.php');
require_once('./helper.php');

//header('Access-Control-Allow-Origin: *');
//header('Access-Control-Allow-Methods: POST');
//header('Access-Control-Max-Age: 1000');

$PAGE->set_context(context_system::instance());

class API extends ajax_controller {
    public $per_page = 50;

    function __construct() {
        parent::__construct();
        if (!empty($this->payload['recordperpage'])) {
            $this->per_page = $this->payload['recordperpage'];
        }
        echo json_encode($this->process($this->action, $this->payload), JSON_UNESCAPED_SLASHES);
    }

    function get_formbuilder_coaching(){

        global $DB;

        $forms = $DB->get_records('formbuilder_form', array('visible' => 1));
        $arr_fields = [];
        foreach($forms as $form){
        
        $optiondata = new stdClass;
        $optiondata->value = $form->id;
        $optiondata->label = $form->name;
        $arr_fields [] = $optiondata;
        }

        return $arr_fields;
    }

    function get_form_data() {
        global $DB, $USER;
        $report_type = $this->payload['report_type'];
        $hierarchy = is_hierarchy_installed();
        $courses = [];
        switch ($report_type) {
            case 'activity':
                if ($hierarchy) {
                    $courses = getCourse_HierarchyUser();
                } else {
                    $courses = getCourses();
                }
                break;
            case 'individual':
                $hierarchy = is_user_in_hierarchy($USER->id);
                $this->result->data = [
                    'users' => get_individual_user_options($USER->id, $hierarchy),
                    'courses' => $courses,
                    'logoURL' => $this->get_logoURL(),
                ];
                return;
            case 'coaching':
            $hierarchy = is_user_in_hierarchy($USER->id);
            $this->result->data = [
                'users' => get_individual_user_options($USER->id, true),
                'forms' => self::get_formbuilder_coaching(),
                'courses' => $courses,
                'logoURL' => $this->get_logoURL(),
            ];
            return;
            case 'users':
                break;
            default:
                if ($hierarchy) {
                    $courses = getCourses_Category_User();
                } else {
                    $courses = getCourses_Category();
                }
        }

        $this->get_sorted_course_category($courses);

        if ($hierarchy) {
            $hierarchy_root_userid = $USER->id;

            try {
                $hierarchy_nodes = get_hierarchy_tree($hierarchy_root_userid);
            } catch (Exception $e) {
                $this->result->error = $e->getMessage();
                return;
            }
            $this->result->data = [
                'hierarchy_nodes' => json_decode(json_encode($hierarchy_nodes)),
                'courses' => $courses,
                'is_hierarchy_installed' => 1,
                'logoURL' => $this->get_logoURL(),
            ];
        } else {
            //no hierarchy will query for all users
            $this->result->data = [
                'courses' => $courses,
                'is_hierarchy_installed' => 0,
                'logoURL' => $this->get_logoURL(),
            ];
        }
    }

    function get_logoURL(){
        global $CFG;
        $context = context_system::instance();

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'block_training_report', 'client_logo', 0);
        $url = "";
        foreach ($files as $file) {
            $filename = $file->get_filename();
            if($file->get_filesize()==0) continue;
            $path = '/' . $context->id . '/' . 'block_training_report' . '/' . 'client_logo' . '/0/' . $file->get_filename();
            // $url = moodle_url::make_file_url('/pluginfile.php', $path,false);
            $url = $CFG->wwwroot.'/pluginfile.php'.$path;
        }

        return $url;
    }

 
    function get_config_info() {
        global $DB, $USER;
        $is_admin = is_siteadmin($USER->id);
        $training_report = get_config('block_training_report');
        $training_report = json_decode(json_encode($training_report));
        if (!empty($training_report->filter_user_profile_fields)) {
            $filter_user_profile_fields = json_decode($training_report->filter_user_profile_fields, true);
            foreach ($filter_user_profile_fields as $key => $field) {
                $option_fileds = ['menu', 'multiselect', 'menuwithfreetext'];
                // get options of menu, multiselect and menuwithfreetext field
                if (in_array($field['type'], $option_fileds)) {
                    /** Now for dropdown fieds, always query from user_info_data table to get the latest options */
                    /** All site admins can view every organization in their report */
                    if ($key == "OrganisationOrAgency" && (!$is_admin)) {
                        $options = $this->get_user_organisation($USER->id);
                    } else {
                        $options = $this->get_user_profile_field_options($key);
                    }
                    $field['options'] = $options;
                    $filter_user_profile_fields[$key] = $field;

                    // $options_temp = [];
                    // // menuwithfreetext filter options may not up to date if the user add other field, so query user_info_data table again when preparing filter data
                    // if ($field['type'] == 'menuwithfreetext') {
                    //     $menuwithfreetext_field = $DB->get_record('user_info_field', ['shortname' => $key], 'id');
                    //     $options_records = $DB->get_records('user_info_data', ['fieldid' => $menuwithfreetext_field->id], 'data', 'DISTINCT(data)');
                    //     if ($options_records) {
                    //         $options_temp = array_keys($options_records);
                    //     }
                    // } else {
                    //     $options_temp = explode("\n", $field['options']);
                    // }

                    // $options = [];
                    // foreach ($options_temp as $option) {
                    //     $options[] = ['value' => $option, 'label' => $option];
                    // }
                    // $field['options'] = $options;
                    // $filter_user_profile_fields[$key] = $field;
                }

                // text field will show as dropdown options
                if (
                    $field['type'] == "text"
                ) {
                    $options = $this->get_user_profile_field_options($key);
                    $field['type'] = "menu";
                    $field['options'] = $options;
                    $filter_user_profile_fields[$key] = $field;
                }
            }
            $training_report->filter_user_profile_fields = $filter_user_profile_fields;
        }

        if (!empty($training_report->display_user_profile_fields)) {
            $display_user_profile_fields = json_decode($training_report->display_user_profile_fields, true);
            foreach ($display_user_profile_fields as $key => $field) {
                if (!empty($field['options'])) {
                    $options_temp = explode("\n", $field['options']);
                    $options = [];
                    foreach ($options_temp as $option) {
                        $options[] = ['value' => $option, 'value' => $option];
                    }
                    $field['options'] = $options;
                    $display_user_profile_fields[$key] = $field;
                }
            }
            $training_report->display_user_profile_fields = $display_user_profile_fields;
        }


        $training_report->filter_user_default_fields = json_decode($training_report->filter_user_default_fields, true);
        // prepare country data
        if (isset($training_report->filter_user_default_fields['country'])) {
            $countries = get_string_manager()->get_list_of_countries(false);
            $training_report->filter_user_default_fields['country']['options'] = [];
            foreach ($countries as $key => $value) {
                $training_report->filter_user_default_fields['country']['options'][] = ['value' => $key, 'label' => $value];
            }
        }

        $training_report->display_user_default_fields = json_decode($training_report->display_user_default_fields, true);
        if ($training_report->client_logo) {
            $url = moodle_url::make_pluginfile_url(context_system::instance()->id, 'block_training_report', 'client_logo', 0, '', $training_report->client_logo);
            $training_report->client_logo = $url->out();
        }
        unset($training_report->version);
        $this->result->data = $training_report;
    }

    function general() {
        global $DB, $USER;
        if (!view_report_allowed() && !block_training_report_helper::is_user_manager($USER->id)) {
            $this->result->error = 'You do not have the permission to view the report';
            return;
        }

        raise_memory_limit(MEMORY_EXTRA);
        $results = $this->filter_results();
        if (!empty($this->result->error)) {
            if ($this->result->error == "No result") {
                unset($this->result->error);
                return $this->returnNull();
            }
            return;
        }
        $result = $results['result'];
        $count = $results['count'];


        $data = new stdClass();
        $data->headers = [];
        $data->headers[] = ['key' => 'full_name', 'display' => 'Full name'];
        $data->headers[] = ['key' => 'course_name', 'display' => 'Course name'];
        $data->headers[] = ['key' => 'course_module_instance_name', 'display' => 'Module'];
        $data->headers[] = ['key' => 'completion_state', 'display' => 'Completion'];
        // $data->headers[] = ['key' => 'enrolment_startdate', 'display' => 'Enrolled date'];
        $data->headers[] = ['key' => 'completion_date', 'display' => 'Completion date'];
        $profile_fields_type = $this->add_config_headers($data->headers);

        foreach ($result as $key => $item) {
            $item['full_name'] = $item['firstname'] . ' ' . $item['lastname'];
            switch ($item['completion_state']) {
                case "0":
                    $item['completion_state'] = 'Not Completed';
                    break;
                case "1":
                case "2":
                    $item['completion_state'] = 'Completed';
                    break;
                case "3":
                    $item['completion_state'] = 'Not Completed'; // Failed
                    break;
                default:
                    $item['completion_state'] = 'Not Completed';
            }
            $rt_item = [];
            foreach ($data->headers as $header) {
                $rt_item[$header['key']] = $item[$header['key']];
            }
            $this->format_config_fields($rt_item, $profile_fields_type);
            $result[$key] = $rt_item;
        }

        $data->data = $result;
        if ($this->payload['display'] == 'excel') {
            $this->result->data = $this->export_excel($data->headers, $data->data, 'General_report_' . date("Y-m-d"));
        } elseif ($this->payload['display'] == 'pdf') {
            $this->result->data = $data;
        } else {
            $data->code = 0;
            $data->total_page = ($count % $this->per_page == 0) ? ($count / $this->per_page) : (floor($count / $this->per_page) + 1);
            $page = (isset($this->payload['pagenum']) && $this->payload['pagenum'] > 0) ? intval($this->payload['pagenum']) : 1;
            $data->page = $page;
            $data->per_page = $this->payload['recordperpage'];
            $this->result->data = $data;
        }
    }

    function activity() {
        global $USER;
        if (!view_report_allowed() && !block_training_report_helper::is_user_manager($USER->id)) {
            $this->result->error = 'You do not have the permission to view the report';
            return;
        }
        global $DB;
        $suspendedusers = $this->payload['suspended'];
        $enrolleddate_from = $this->payload['enrolled_from'];
        $enrolleddate_to = $this->payload['enrolled_to'];
        $completiondate_from = $this->payload['completion_from'];
        $completiondate_to = $this->payload['completion_to'];
        $selectednodes = $this->payload['hierarchy'];
        $course = $this->payload['course'];
        if (empty($course)) {
            $this->result->error = 'You must select a course';
            return;
        }
        $order_by = $this->payload['order_by'];
        if (empty($order_by)) {
            $query_order_by = " order by firstname";
        } else {
            if ($order_by['key'] == 'full_name') {
                $order_by['key'] = 'firstname';
            }
            $query_order_by = " order by " . $order_by['key'] . ' ' . $order_by['order'];
        }

        $page = (isset($this->payload['pagenum']) && $this->payload['pagenum'] > 0) ? intval($this->payload['pagenum']) : 1;
        $offset = ($page > 1) ? ($this->per_page * ($page - 1)) : 0;
        $pagination = " LIMIT $offset, " . $this->per_page;

        $hierarchy = is_hierarchy_installed();
        $users_sql = "SELECT DISTINCT(`user_id`) FROM {report_completion} rc 
                JOIN {user} u 
                ON u.id = rc.user_id  
                WHERE rc.`course_id`= ? AND u.username != 'guest' ";

        switch ($suspendedusers) {
            case 0:
                $users_sql .= " AND u.suspended = '0'" . "\n";
                break;
            case -1:
                $users_sql .= " AND u.suspended = '1'" . "\n";
                break;
            default:
                break;
        }

        if ($hierarchy) {
            try {
                $list_hierarchy_users = get_all_users_from_nodes($selectednodes); // List of users has been added to hierarchy condition
            } catch (Exception $e) {
                $this->result->error = $e->getMessage();
                return;
            }
            // if there is no user records, the result should be none
            if (empty($list_hierarchy_users)) {
                $this->result->data = [];
                return;
            }
            $users_sql .= " and u.id in (" . $list_hierarchy_users . ") ";
        }

        $users = $DB->get_records_sql($users_sql, [$course]);
        $users_arr = [];
        foreach ($users as $user) {
            $users_arr[] = $user->user_id;
        }
        if (empty($users_arr)) {
            $this->returnNull();
            return;
        }
        list($q_user, $p_user) = $DB->get_in_or_equal($users_arr);

        $params = [];
        $query_select = "SELECT u.`id` as user_id, u.`firstname`, u.`lastname` ";
        $query_table = " FROM {user} u ";
        $params = array_merge($params, $p_user);
        $wheres = " WHERE u.id $q_user";

        $activity_sql = "SELECT `course_module_instance_id`, `course_module_id`, `course_module_instance_name`
                FROM `mdl_report_completion`
                WHERE `course_id`= ? 
                GROUP BY `course_module_instance_id`, `course_module_id`";
        if ($enrolleddate_from) {
            $activity_sql .= " AND (enrolment_startdate >= " . $enrolleddate_from . ") ";
        }
        if ($enrolleddate_to) {
            $activity_sql .= " AND (enrolment_startdate <= " . $enrolleddate_to . ") ";
        }
        if ($completiondate_from) {
            $activity_sql .= " AND (completion_date >= " . $completiondate_from . ") ";
        }
        if ($completiondate_to) {
            $activity_sql .= " AND (completion_date <= " . $completiondate_to . ") ";
        }
        $module_instances = $DB->get_records_sql($activity_sql, [$course]);

        $table_num = 1;
        foreach ($module_instances as $module_instance) {
            if ($table_num == 1) {
                $query_select .= " ,rc$table_num.`enrolment_startdate` ";
            }
            $query_select .= " 
                            , rc$table_num.`completion_state` as `" . $module_instance->course_module_instance_name . "` ";
            $query_table .=
                " LEFT JOIN {report_completion} rc$table_num
                    ON rc$table_num.`user_id` = u.`id`
                    AND rc$table_num.`course_module_instance_id` = " . $module_instance->course_module_instance_id . " 
                    AND rc$table_num.`course_module_id`=" . $module_instance->course_module_id . " ";
            $table_num++;
        }

        $default_fields = $this->generate_default_user_field_filter($wheres, $params);
        $array_dynamic_query = $this->generate_custom_user_profile_filter_and_fields();
        if (!empty($array_dynamic_query['p_dynamic'])) {
            $params = array_merge($array_dynamic_query['p_dynamic'], $params);
        }
        $query_select .= $default_fields . $array_dynamic_query['fields'];
        $query_table .= $array_dynamic_query['filter_table'] . $array_dynamic_query['display_table'];
        $wheres .= $array_dynamic_query['where'];


        $sql = $query_select . $query_table . $wheres . $query_order_by;
        $sql_count = 'SELECT COUNT(*) as count ' . $query_table . $wheres . $query_order_by;
        if ($this->payload['display'] != 'excel' && $this->payload['display'] != 'pdf') {
            $sql .= $pagination;
        }
        $count = $DB->count_records_sql($sql_count, $params);
        $result = [];
        if ($count > 0) {
            $result = $DB->get_records_sql($sql, $params);
        }

        if (!empty($this->result->error)) {
            return;
        }

        $data = new stdClass();
        $data->headers = [];
        $data->headers[] = ['key' => 'full_name', 'display' => 'Full name'];
        // $data->headers[] = ['key' => 'enrolment_startdate', 'display' => 'Enrolled date'];
        foreach ($module_instances as $instance) {
            $data->headers[] = [
                'key' => strtolower($instance->course_module_instance_name),
                'display' => ucwords($instance->course_module_instance_name)
            ];
        }

        $profile_fields_type = $this->add_config_headers($data->headers, true);
        $result = json_decode(json_encode(array_values($result)), true);
        foreach ($result as $key => $record) {
            $record['full_name'] = $record['firstname'] . ' ' . $record['lastname'];
            $this->format_config_fields($record, $profile_fields_type);
            foreach ($module_instances as $instance) {
                switch ($record[strtolower($instance->course_module_instance_name)]) {
                    case "0":
                        $record[strtolower($instance->course_module_instance_name)] = 'Not Completed';
                        break;
                    case "1":
                    case "2":
                        $record[strtolower($instance->course_module_instance_name)] = 'Completed';
                        break;
                    case "3":
                        $record[strtolower($instance->course_module_instance_name)] = 'Not Completed'; //failed
                        break;
                    default:
                        $record[strtolower($instance->course_module_instance_name)] = 'Not Completed';
                }
            }
            $result[$key] = $record;
        }

        $data->data = $result;

        if ($this->payload['display'] == 'excel') {
            $this->result->data = $this->export_excel($data->headers, $data->data, 'SGL_element_summary_' . date("Y-m-d"));
        } elseif ($this->payload['display'] == 'pdf') {
            $this->result->data = $data;
        } else {
            $data->code = 0;
            $data->total_page = ($count % $this->per_page == 0) ? ($count / $this->per_page) : (floor($count / $this->per_page) + 1);
            $data->page = $page;
            $data->per_page = $this->payload['recordperpage'];
            $this->result->data = $data;
        }
    }

    public function course() {
        global $USER;
        if (!view_report_allowed() && !block_training_report_helper::is_user_manager($USER->id)) {
            $this->result->error = 'You do not have the permission to view the report';
            return;
        }

        global $DB, $USER;
        $suspendedusers = $this->payload['suspended'];
        $enrolleddate_from = $this->payload['enrolled_from'];
        $enrolleddate_to = $this->payload['enrolled_to'];
        $completiondate_from = $this->payload['completion_from'];
        $completiondate_to = $this->payload['completion_to'];
        $selectednodes = $this->payload['hierarchy'];
        $course = $this->payload['course'];
        $order_by = $this->payload['order_by'];
        if (empty($order_by)) {
            $query_order_by = " order by course_name";
        } else {
            if ($order_by['key'] == 'full_name') {
                $order_by['key'] = 'firstname';
            }
            $query_order_by = " order by " . $order_by['key'] . ' ' . $order_by['order'];
        }

        $page = (isset($this->payload['pagenum']) && $this->payload['pagenum'] > 0) ? intval($this->payload['pagenum']) : 1;
        $offset = ($page > 1) ? ($this->per_page * ($page - 1)) : 0;
        $pagination = " LIMIT $offset, " . $this->per_page;

        $hierarchy = is_hierarchy_installed();
        $users_sql = "SELECT DISTINCT(`user_id`) FROM {report_completion} rc 
                JOIN {user} u 
                ON u.id = rc.user_id  
                WHERE u.username != 'guest' ";

        switch ($suspendedusers) {
            case 0:
                $users_sql .= " AND u.suspended = '0'" . "\n";
                break;
            case -1:
                $users_sql .= " AND u.suspended = '1'" . "\n";
                break;
            default:
                break;
        }

        if ($hierarchy) {
            try {
                $list_hierarchy_users = get_all_users_from_nodes($selectednodes); // List of users has been added to hierarchy condition
            } catch (Exception $e) {
                $this->result->error = $e->getMessage();
                return;
            }
            // if there is no user records, the result should be none
            if (empty($list_hierarchy_users)) {
                $this->result->data = [];
                return;
            }
            $users_sql .= " and u.id in (" . $list_hierarchy_users . ") ";
        }

        $users = $DB->get_records_sql($users_sql);
        $users_arr = [];
        foreach ($users as $user) {
            $users_arr[] = $user->user_id;
        }
        if (empty($users_arr)) {
            $this->returnNull();
            return;
        }
        list($q_user, $p_user) = $DB->get_in_or_equal($users_arr);

        $params = [];
        $query_select = "SELECT CONCAT(rc.`user_id`, '_', rc.`course_id`) id, rc.`user_id`, category.`name` as category, rc.`course_id`, rc.`course_name`, rc.`enrolment_startdate`, u.`firstname`, u.`lastname`,  
                        CASE WHEN (SUM(CASE WHEN rc.`completion_state` = 1 THEN 1 
                                 WHEN rc.`completion_state` = 2 THEN 1  
                                 ELSE 0  
                                 END)/COUNT(*))=1 THEN (MAX(rc.`completion_date`))
                                 ELSE NULL END AS `completion_date`, 	 
                        SUM(CASE WHEN rc.`completion_state` = 1 THEN 1 
                                 WHEN rc.`completion_state` = 2 THEN 1  
                                 ELSE 0  
                                 END)/COUNT(*) AS `completion_percentage` ";
        $query_table = " FROM `mdl_report_completion` AS rc 
                        JOIN `mdl_user` AS u 
                        ON rc.`user_id` = u.`id`  
                        JOIN `mdl_course` AS course 
                        ON rc.`course_id` = course.`id` 
                        JOIN `mdl_course_categories` AS category 
                        ON course.`category` = category.`id` 
        ";
        $group_by = " GROUP BY `user_id`, `course_id` ";
        $params = array_merge($params, $p_user);
        $wheres = " WHERE u.id $q_user";
        $having = " HAVING 1=1 ";

        if (!empty($course) && !is_array($course)) {
            $temp = $course;
            $course = [];
            $course[] = $temp;
            $course_category_conditions = [];
        }

        if ($course && !empty($course)) {
            $course_arr = $course;

            if (!empty($course_arr)) {
                list($q_course, $p_course) = $DB->get_in_or_equal($course_arr);
                $course_category_conditions[] = "rc.course_id $q_course";
                $params = array_merge($params, $p_course);
            }

            if (!empty($course_category_conditions)) {
                $wheres .= " AND (" . implode(' OR ', $course_category_conditions) . ") ";
            }
        } else {
            // Add condition to apply for Vendor
            if (is_vendor($USER->id)) {
                $arr_course_ids = get_vendor_course_ids($USER->id);
                $list = implode(",", $arr_course_ids);
                $wheres .= " AND rc.course_id in (" . $list . ") \n";
            }
        }

        if ($enrolleddate_from) {
            $wheres .= " AND (rc.enrolment_startdate >= " . $enrolleddate_from . ") ";
        }
        if ($enrolleddate_to) {
            $wheres .= " AND (rc.enrolment_startdate <= " . $enrolleddate_to . ") ";
        }
        if ($completiondate_from) {
            $having .= " AND (completion_date >= " . $completiondate_from . ") ";
        }
        if ($completiondate_to) {
            $having .= " AND (completion_date <= " . $completiondate_to . ") ";
        }

        $default_fields = $this->generate_default_user_field_filter($wheres, $params);
        $array_dynamic_query = $this->generate_custom_user_profile_filter_and_fields();
        if (!empty($array_dynamic_query['p_dynamic'])) {
            $params = array_merge($array_dynamic_query['p_dynamic'], $params);
        }
        $query_select .= $default_fields . $array_dynamic_query['fields'];
        $count_table = $query_table . $array_dynamic_query['filter_table'];
        $query_table .= $array_dynamic_query['filter_table'] . $array_dynamic_query['display_table'];
        $wheres .= $array_dynamic_query['where'];

        if ($completiondate_from || $completiondate_to) {
            $count_sql = "SELECT COUNT(*) from (SELECT rc.`user_id`, rc.`course_id`, 
                            CASE WHEN (SUM(CASE WHEN rc.`completion_state` = 1 THEN 1 
                                 WHEN rc.`completion_state` = 2 THEN 1  
                                 ELSE 0  
                                 END)/COUNT(*))=1 THEN (MAX(rc.`completion_date`))
                                 ELSE NULL END AS `completion_date` 
                        "
            . $count_table . $wheres . $group_by . $having . ") `temp`";
        } else {
            $count_sql = "SELECT COUNT(*) from (SELECT rc.`user_id`, rc.`course_id` " . $query_table . $wheres . $group_by . ") `temp`";
        }
        $count = $DB->count_records_sql($count_sql, $params);
        $sql = $query_select . $query_table . $wheres . $group_by . $having . $query_order_by;
        if ($this->payload['display'] != 'excel' && $this->payload['display'] != 'pdf') {
            $sql .= $pagination;
        }
        $result = [];
        if ($count > 0) {
            $result = $DB->get_records_sql($sql, $params);
        }
        if (!empty($this->result->error)) {
            return;
        }

        $data = new stdClass();
        $data->headers = [];
        $data->headers[] = ['key' => 'full_name', 'display' => 'Full name'];
        $data->headers[] = ['key' => 'course_name', 'display' => 'Practice element/module'];
        $data->headers[] = ['key' => 'category', 'display' => 'SGL module / training'];
        // $data->headers[] = ['key' => 'enrolment_startdate', 'display' => 'Enrolled date'];
        $data->headers[] = ['key' => 'completion_percentage', 'display' => 'Completion percentage'];
        $data->headers[] = ['key' => 'completion_date', 'display' => 'Completion date'];

        $profile_fields_type = $this->add_config_headers($data->headers, true);
        $result = json_decode(json_encode(array_values($result)), true);
        foreach ($result as $key => $record) {
            $record['full_name'] = $record['firstname'] . ' ' . $record['lastname'];
            $this->format_config_fields($record, $profile_fields_type);
            $result[$key] = $record;
            unset($record['id']);
        }

        //graph related query
        if ($this->payload['graph'] && $count > 0) {
            if ($completiondate_from || $completiondate_to) {
                $bar_sub_query = "
            SELECT rc.`user_id`, rc.`course_id`, rc.`course_name`, 
            SUM(CASE WHEN rc.`completion_state` = 1 THEN 1 
                                             WHEN rc.`completion_state` = 2 THEN 1  
                                             ELSE 0  
                                             END)/COUNT(*) AS `completed`,
            CASE WHEN (SUM(CASE WHEN rc.`completion_state` = 1 THEN 1 
                                 WHEN rc.`completion_state` = 2 THEN 1  
                                 ELSE 0  
                                 END)/COUNT(*))=1 THEN (MAX(rc.`completion_date`))
                                 ELSE NULL END AS `completion_date`                                	 
            ";
                $bar_sub_query .= $count_table . $wheres . $group_by . $having;
            } else {
                $bar_sub_query = "
            SELECT rc.`user_id`, rc.`course_id`, rc.`course_name`, 
            SUM(CASE WHEN rc.`completion_state` = 1 THEN 1 
                                             WHEN rc.`completion_state` = 2 THEN 1  
                                             ELSE 0  
                                             END)/COUNT(*) AS `completed`                              	 
            ";
                $bar_sub_query .= $query_table . $wheres . $group_by;
            }
            $bar_sql = "
        SELECT temp.`course_id`, temp.`course_name` AS NAME, SUM(temp.`completed`)/COUNT(*) AS `number`,
            SUM(temp.`completed`) as `completed`, COUNT(*) as `count`
            FROM ($bar_sub_query) AS temp
            GROUP BY temp.`course_id`
        ";
            $bar_result = $DB->get_records_sql($bar_sql, $params);

            $total_completed = 0;
            $total = 0;
            foreach ($bar_result as $course) {
                $total_completed += $course->completed;
                $total += $course->count;
            }

            $data->overall_completed = round($total_completed / $total, 4);
            $data->progress = array_values($bar_result);
        }

        $data->data = $result;

        if ($this->payload['display'] == 'excel') {
            $this->result->data = $this->export_excel($data->headers, $data->data, 'SGL_element_summary_' . date("Y-m-d"));
        } elseif ($this->payload['display'] == 'pdf') {
            $this->result->data = $data;
        } else {
            $data->code = 0;
            $data->total_page = ($count % $this->per_page == 0) ? ($count / $this->per_page) : (floor($count / $this->per_page) + 1);
            $data->page = $page;
            $data->per_page = $this->payload['recordperpage'];
            $this->result->data = $data;
        }
    }

    public function individual() {
        global $USER, $DB;
        $uid = $this->payload['user_id'];

        $has_capability = view_report_allowed();
        $wheres = 'Where c.visible=1 AND ';
        $params = array();
        $groupby = "GROUP BY";

        if ($uid != "") {
            $wheres .= " u.id=" . $uid . " \n";
        } else {
            $this->result->error = 'Please select a user!';
            return;
        }
        //=======================================================
        // If user is part of the hierarchy, they are not allow to see anyone higher than them
        //
        $hierarchy = is_hierarchy_installed();
        if (!$has_capability) {
            //================= FOR VENDOR ===========================
            if (is_vendor($USER->id)) {
                // If user does not do any course relating to this vendor => can not access
                // If user does some courses relating to this vendor => vendor should allow to see this course
                $arr_course_ids = get_vendor_course_ids($USER->id);
                $list = implode(",", $arr_course_ids);
                $wheres .= " AND c.id in (" . $list . ") \n ";
                //=======================================================
            } elseif ($hierarchy) { // Normal user. They can not access if they are not part of the hierarchy
                // If they are in the hierarchy, they can see only see people under their level.
                $current_node_id = $DB->get_field('hierarchy_user', 'node_id', array('user_id' => $USER->id));
                $all_users_under_thisuser = get_all_users_from_nodes($current_node_id);
                $sql_check = "select id from mdl_user where deleted=0 and (id=" . $uid .
                    "
		                        or id in(" . $all_users_under_thisuser . "))";
                if (!$DB->record_exists_sql($sql_check)) {
                    $this->result->error = 'Could not find any user in the hierarchy!';
                    return;
                }
            }
        }

        $hierarchy = is_user_in_hierarchy($uid);
        $hierarchy_query = array("fields" => '', 'table' => '', 'where' => '');
        if ($hierarchy) {
            $hierarchy_query = get_hierarchy_query($uid);
        }

        raise_memory_limit(MEMORY_EXTRA);
        $page = (isset($this->payload['pagenum']) && $this->payload['pagenum'] > 0) ? intval($this->payload['pagenum']) : 1;
        $offset = ($page > 1) ? ($this->per_page * ($page - 1)) : 0;
        $pagination = " LIMIT $offset, " . $this->per_page;
        $results = $this->query($wheres, $params, $hierarchy_query, $groupby, $pagination);
        $result = $results['result'];
        $count = $results['count'];

        $data = new stdClass();
        $data->headers = [];
        $data->headers[] = ['key' => 'full_name', 'display' => 'Full name'];
        $data->headers[] = ['key' => 'course_name', 'display' => 'Course name'];
        $data->headers[] = ['key' => 'course_module_instance_name', 'display' => 'Module'];
        $data->headers[] = ['key' => 'completion_state', 'display' => 'Completion'];
        // $data->headers[] = ['key' => 'enrolment_startdate', 'display' => 'Enrolled date'];
        $data->headers[] = ['key' => 'completion_date', 'display' => 'Completion date'];
        $profile_fields_type = $this->add_config_headers($data->headers);

        foreach ($result as $key => $item) {
            $item['full_name'] = $item['firstname'] . ' ' . $item['lastname'];
            switch ($item['completion_state']) {
                case "0":
                    $item['completion_state'] = 'Not Completed';
                    break;
                case "1":
                case "2":
                    $item['completion_state'] = 'Completed';
                    break;
                case "3":
                    $item['completion_state'] = 'Not Completed'; // Failed
                    break;
                default:
                    $item['completion_state'] = 'Not Completed';
            }
            $rt_item = [];
            foreach ($data->headers as $header) {
                $rt_item[$header['key']] = $item[$header['key']];
            }
            $rt_item['user_id'] = $item['user_id'];
            $this->format_config_fields($rt_item, $profile_fields_type);
            $result[$key] = $rt_item;
        }

        $data->data = $result;
        if ($this->payload['display'] == 'excel') {
            $this->result->data = $this->export_excel($data->headers, $data->data, 'General_report_' . date("Y-m-d"));
        } elseif ($this->payload['display'] == 'pdf') {
            $this->result->data = $data;
        } else {
            $data->code = 0;
            $data->total_page = ($count % $this->per_page == 0) ? ($count / $this->per_page) : (floor($count / $this->per_page) + 1);
            $data->page = $page;
            $data->per_page = $this->payload['recordperpage'];
            $this->result->data = $data;
        }
    }

    public function coaching() {
        global $USER, $DB;
        $users = $this->payload['user_id'];
        $has_capability = view_report_allowed();
        $IsPractitioner_fieldid = 5;
        $RoleOrPosition_fieldid = 2;
        $OrganisationOrAgency_fieldid = 1;
        raise_memory_limit(MEMORY_EXTRA);
        $page = (isset($this->payload['pagenum']) && $this->payload['pagenum'] > 0) ? intval($this->payload['pagenum']) : 1;
        $offset = ($page > 1) ? ($this->per_page * ($page - 1)) : 0;
        $pagination = " LIMIT $offset, " . $this->per_page;
        if($this->payload['OrganisationOrAgency'][0]){
            $practitioner = ' JOIN {user_info_data} user_info_practitioner ON u.id = user_info_practitioner.userid AND user_info_practitioner.fieldid = ? AND user_info_practitioner.data = "'.$this->payload['OrganisationOrAgency'][0].'"';
        }else{
            $practitioner =' JOIN {user_info_data} user_info_practitioner ON u.id = user_info_practitioner.userid AND user_info_practitioner.fieldid = ?';
        }
        if($this->payload['RoleOrPosition'][0]){
            $position = ' JOIN {user_info_data} user_info_role
            ON u.id = user_info_role.userid AND user_info_role.fieldid = ? AND user_info_practitioner.data = "'.$this->payload['RoleOrPosition'][0].'"';
        }else{
            $position = ' JOIN {user_info_data} user_info_role
            ON u.id = user_info_role.userid AND user_info_role.fieldid = ?';
        }
        if($this->payload['Program'][0]){
            $program = '  JOIN {user_info_data} user_info_agency
            ON u.id = user_info_agency.userid AND user_info_agency.fieldid = ? AND user_info_agency.data =  "'.$this->payload['Program'][0].'"';
        }else{
            $program = '  JOIN {user_info_data} user_info_agency
            ON u.id = user_info_agency.userid AND user_info_agency.fieldid = ?';
        }

        $users = $this->payload['user_id'];
        $formdata = '';
        $userarray = [];
        foreach($users as $user){
           
            $userarray[] = (int)$user['id'];
        }
        if(empty($users)){
            // $s = $DB->get_records_sql('SELECT  face.sessionid as id, f.formbuilder as formid, face.userid as userid, dates.timestart as timestart, dates.timefinish as timefinish, u.firstname as firstname, u.lastname as lastname, user_info_practitioner.data AS practitioner,
            // user_info_role.data AS role,
            // user_info_agency.data as agency FROM {facetoface_signups} AS face
            // JOIN {user} AS u ON face.userid = u.id 
            // JOIN {facetoface_sessions} AS s ON face.sessionid = s.id 
            // JOIN {facetoface} AS f ON s.facetoface  = f.id
            // JOIN {user_info_data} AS info ON u.id = info.userid 
            // JOIN {facetoface_sessions_dates} AS dates ON face.sessionid = dates.sessionid '.$practitioner.' '.$position.' '.$program.'WHERE f.formbuilder = ?', array($IsPractitioner_fieldid,$RoleOrPosition_fieldid,$OrganisationOrAgency_fieldid,$this->payload['formid']));
            
            $s = $DB->get_records_sql('SELECT CONCAT(formdata.id, "-", formdata.f2fsessionid, "-", formdata.userid,"-", formdata.formid) AS uniqueid,  formdata.f2fsessionid as sessionid, f.formbuilder as formid, formdata.userid as userid, dates.timestart as timestart, dates.timefinish as timefinish, u.firstname as firstname, u.lastname as lastname, user_info_practitioner.data AS practitioner,
            user_info_role.data AS role,
            user_info_agency.data as agency 
            FROM {formbuilder_form_info_data} as formdata
             JOIN {user} AS u ON formdata.userid = u.id
             JOIN {user_info_data} AS info ON u.id = info.userid 
             JOIN {facetoface_sessions} AS s ON formdata.f2fsessionid = s.id 
             JOIN {facetoface} AS f ON s.facetoface  = f.id
             JOIN {facetoface_sessions_dates} AS dates ON formdata.f2fsessionid = dates.sessionid '.$practitioner.' '.$position.' '.$program.'WHERE formdata.formid = ? GROUP BY formdata.userid, formdata.formid', array($IsPractitioner_fieldid,$RoleOrPosition_fieldid,$OrganisationOrAgency_fieldid,(int)$this->payload['formid']));
           
        }else{
         
            $allusers = implode(',',$userarray);
           
                            $s = $DB->get_records_sql('SELECT CONCAT(formdata.id, "-", formdata.f2fsessionid, "-", formdata.userid,"-", formdata.formid) AS uniqueid,  formdata.f2fsessionid as sessionid, f.formbuilder as formid, formdata.userid as userid, dates.timestart as timestart, dates.timefinish as timefinish, u.firstname as firstname, u.lastname as lastname, user_info_practitioner.data AS practitioner,
            user_info_role.data AS role,
            user_info_agency.data as agency 
            FROM {formbuilder_form_info_data} as formdata
             JOIN {user} AS u ON formdata.userid = u.id
             JOIN {user_info_data} AS info ON u.id = info.userid 
             JOIN {facetoface_sessions} AS s ON formdata.f2fsessionid = s.id 
             JOIN {facetoface} AS f ON s.facetoface  = f.id
             JOIN {facetoface_sessions_dates} AS dates ON formdata.f2fsessionid = dates.sessionid '.$practitioner.' '.$position.' '.$program.'WHERE formdata.formid = ? AND formdata.userid IN ('.$allusers.')  GROUP BY formdata.userid, formdata.formid', array($IsPractitioner_fieldid,$RoleOrPosition_fieldid,$OrganisationOrAgency_fieldid,(int)$this->payload['formid']));
        }
   
        $formdata = $DB->get_records_sql('SELECT * FROM {formbuilder_form_info_field} WHERE formid = ?',array($this->payload['formid']));
        if($formdata){
            $count = count($s);
        
            $data = new stdClass();
            $data->headers = [];
            $data->headers[] = ['key' => 'full_name', 'display' => 'Full name'];
            $data->headers[] = ['key' => 'role', 'display' => 'Role'];
            $data->headers[] = ['key' => 'agency', 'display' => 'Agency'];
            $data->headers[] = ['key' => 'practitioner', 'display' => 'Practitioner'];
            $data->headers[] = ['key' => 'session_start', 'display' => 'Session date'];
            foreach($formdata as $field){
                $fieldname = $DB->get_record_sql('SELECT * FROM {formbuilder_form_info_field} WHERE id = ?',array($field->id));
               
                $data->headers[] = ['key' => $fieldname->shortname, 'display' => $fieldname->name];
            }
         
            $tcount= 0;
         
            foreach ($s as $i) {
              
             
                if($i->formid == $this->payload['formid']){
                        $i->full_name = $i->firstname . ' ' . $i->lastname;
                        foreach($formdata as $field){
                         
                        $fieldname = $DB->get_record_sql('SELECT * FROM {formbuilder_form_info_data} WHERE fieldid =? AND userid = ? AND f2fsessionid = ? ',array($field->id,$i->userid,$i->sessionid));
                        if($fieldname->data == null || $fieldname->data == ''){
                            $i->{$field->shortname} = 'empty';
                        }else{
                           
                            $i->{$field->shortname} = $fieldname->data;
                        }
                        }
                    
                        $rt_item = [];
                        $server_tz = core_date::get_server_timezone();
                        $date_format = 'd/m/Y ';
                        $timestart = ma_facetoface_get_date_format(
                            $server_tz, 
                            $i->timestart,
                            $date_format
                          );
                        $i->session_start = $timestart;
                        foreach ($data->headers as $header) {
                    
                            $rt_item[$header['key']] = $i->{$header['key']};
                        }
                     
                        $result[$tcount] = $rt_item;
                       
                }
                $tcount++;
            }
            $data->data = $result;
            if ($this->payload['display'] == 'excel') {
                $this->result->data = $this->export_excel($data->headers, $data->data, 'General_report_' . date("Y-m-d"));
            } elseif ($this->payload['display'] == 'pdf') {
                $this->result->data = $data;
            } else {
                $data->code = 0;
                $data->total_page = ($count % $this->per_page == 0) ? ($count / $this->per_page) : (floor($count / $this->per_page) + 1);
                $data->page = $page;
                $data->per_page = $this->payload['recordperpage'];
                $this->result->data = $data;
            }
        }
       
    }

    public function users() {
        global $DB, $CFG, $USER;
        raise_memory_limit(MEMORY_EXTRA);

        if (!view_users_report_allowed() && !block_training_report_helper::is_user_manager($USER->id)) {
            $this->result->error = 'You do not have the permission to view the report';
            return;
        }

        $wheres = 'Where u.deleted=0 ';

        $suspendedusers = $this->payload['suspended'];
        $selectednodes = $this->payload['hierarchy'];
        $order_by = $this->payload['order_by'];

        $hierarchy = is_hierarchy_installed();
        if ($hierarchy) {
            try {
                $list_hierarchy_users = get_all_users_from_nodes($selectednodes); // List of users has been added to hierarchy condition
            } catch (Exception $e) {
                $this->result->error = $e->getMessage();
                return;
            }
            // if there is no user records, the result should be none
            if (empty($list_hierarchy_users)) {
                return $this->returnNull();
            }
            $wheres .= " AND u.id in (" . $list_hierarchy_users . ") ";
        }

        switch ($suspendedusers) {
            case 0:
                $wheres .= " AND u.suspended = '0'" . "\n";
                break;
            case -1:
                $wheres .= " AND u.suspended = '1'" . "\n";
                break;
            default:
                break;
        }

        $page = (isset($this->payload['pagenum']) && $this->payload['pagenum'] > 0) ? intval($this->payload['pagenum']) : 1;
        $offset = ($page > 1) ? ($this->per_page * ($page - 1)) : 0;
        $pagination = " LIMIT $offset, " . $this->per_page;

        raise_memory_limit(MEMORY_EXTRA);
        global $CFG;
        $DBH = new PDO("mysql:host=$CFG->dbhost;dbname=$CFG->dbname", $CFG->dbuser, $CFG->dbpass);
        $global_params = [];
        $default_fields = $this->generate_default_user_field_filter($wheres, $global_params);
        $array_dynamic_query = $this->generate_custom_user_profile_filter_and_fields();
        if (!empty($array_dynamic_query['p_dynamic'])) {
            $global_params = array_merge($global_params, $array_dynamic_query['p_dynamic']);
        }
        $wheres .= $array_dynamic_query['where'];

        $select_count = "SELECT COUNT(*)  ";
        $select_columns =
            "Select u.id as userid, u.firstname as firstname, u.lastname as lastname 
             " . $default_fields . $array_dynamic_query['fields'];
        $select_tables = " FROM mdl_user as u ";
        if (empty($order_by)) {
            $orderby = " order by firstname";
        } else {
            if ($order_by['key'] == 'full_name') {
                $order_by['key'] = 'firstname';
            }
            $orderby = " ORDER BY " . $order_by['key'] . ' ' . $order_by['order'];
        }
        $count = 0;
        $result = [];
        if ($this->payload['display'] == 'excel' || $this->payload['display'] == 'pdf') {
            $query = $select_columns . $select_tables . $array_dynamic_query['filter_table'] . $array_dynamic_query['display_table'] . $wheres . $orderby;
            $STH = $DBH->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $STH->execute($global_params);

            if ($STH) {
                $STH->setFetchMode(PDO::FETCH_ASSOC);
                $result = $STH->fetchall();
            }
        } else {
            $count_query = $select_count . $select_tables . $array_dynamic_query['filter_table'] . $wheres . $orderby;
            $count = $DB->count_records_sql($count_query, $global_params);
            $result = [];
            if ($count > 0) {
                $query = $select_columns . $select_tables . $array_dynamic_query['filter_table'] . $array_dynamic_query['display_table'] . $wheres . $orderby . $pagination;
                $STH = $DBH->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                $STH->execute($global_params);

                if ($STH) {
                    $STH->setFetchMode(PDO::FETCH_ASSOC);
                    $result = $STH->fetchall();
                }
            }
        }


        $data = new stdClass();
        $data->headers = [];
        $data->headers[] = ['key' => 'firstname', 'display' => 'First name'];
        $data->headers[] = ['key' => 'lastname', 'display' => 'Last name'];
        $profile_fields_type = $this->add_config_headers($data->headers);

        foreach ($result as $key => $item) {
            $rt_item = [];
            $rt_item['user_id'] = $item['userid'];
            foreach ($data->headers as $header) {
                $rt_item[$header['key']] = $item[$header['key']];
            }
            $this->format_config_fields($rt_item, $profile_fields_type);
            $result[$key] = $rt_item;
        }

        $data->data = $result;
        if ($this->payload['display'] == 'excel') {
            $this->result->data = $this->export_excel($data->headers, $data->data, 'General_report_' . date("Y-m-d"));
        } elseif ($this->payload['display'] == 'pdf') {
            $this->result->data = $data;
        } else {
            $data->code = 0;
            $data->total_page = ($count % $this->per_page == 0) ? ($count / $this->per_page) : (floor($count / $this->per_page) + 1);
            $data->page = $page;
            $data->per_page = $this->payload['recordperpage'];
            $this->result->data = $data;
        }
    }

    private function filter_results($no_pagination = false) {
        global $DB, $CFG, $USER;
        $completionstatus = $this->payload['completion'];
        $suspendedusers = $this->payload['suspended'];
        $enrolleddate_from = $this->payload['enrolled_from'];
        $enrolleddate_to = $this->payload['enrolled_to'];
        $completiondate_from = $this->payload['completion_from'];
        $completiondate_to = $this->payload['completion_to'];
        $selectednodes = $this->payload['hierarchy'];
        $course = $this->payload['course'];
        $page = (isset($this->payload['pagenum']) && $this->payload['pagenum'] > 0) ? intval($this->payload['pagenum']) : 1;
        $offset = ($page > 1) ? ($this->per_page * ($page - 1)) : 0;
        $pagination = " LIMIT $offset, " . $this->per_page;

        if (!empty($course) && !is_array($course)) {
            $temp = $course;
            $course = [];
            $course[] = $temp;
        }
        $hierarchy = is_hierarchy_installed();
        $hierarchy_query = array("fields" => '', 'table' => '', 'where' => '');
        if ($hierarchy) {
            try {
                $list_hierarchy_users = get_all_users_from_nodes($selectednodes); // List of users has been added to hierarchy condition
            } catch (Exception $e) {
                $this->result->error = $e->getMessage();
                return;
            }
            // if there is no user records, the result should be none
            if (empty($list_hierarchy_users)) {
                $this->result->error = "No result";
                return;
            }
            $hierarchy_query = get_hierarchy_query($list_hierarchy_users);
            $hierarchy_query['fields'] = '';
            $hierarchy_query['table'] = '';
        }
        // Ending process hierarchy value

        $global_params = [];
        $wheres = "Where c.visible=1 AND u.username != 'guest' AND rc.course_name !='' ";

        switch ($suspendedusers) {
            case 0:
                $wheres .= " AND u.suspended = '0'" . "\n";
                break;
            case -1:
                $wheres .= " AND u.suspended = '1'" . "\n";
                break;
            default:
                break;
        }


        $wheres .= $hierarchy_query['where'];
        if ($course && !empty($course)) {
            $course_arr = $course;
            $course_category_conditions = [];

            if (!empty($course_arr)) {
                list($q_course, $p_course) = $DB->get_in_or_equal($course_arr);
                $course_category_conditions[] = "rc.course_id $q_course";
                $global_params = array_merge($global_params, $p_course);
            }

            if (!empty($course_category_conditions)) {
                $wheres .= " AND (" . implode(' OR ', $course_category_conditions) . ") ";
            }
        } else {
            // Add condition to apply for Vendor
            if (is_vendor($USER->id)) {
                $arr_course_ids = get_vendor_course_ids($USER->id);
                $list = implode(",", $arr_course_ids);
                $wheres .= " AND rc.course_id in (" . $list . ") \n";
            }
        }

        // if completion date filter filled, only return completed result, otherwise might return previously complted but now not completed records
        if ($completiondate_from || $completiondate_to) {
            $completionstatus = "2";
        }

        if ($completionstatus && !empty($completionstatus)) {
            if ($completionstatus == "1") {
                $wheres .= "AND (rc.completion_state = 0 OR rc.completion_state = 3 OR rc.completion_state is NULL)";
            }
            //Completed
            else if ($completionstatus == "2") {
                $wheres .= "AND (rc.completion_state = 1 OR rc.completion_state = 2) ";
            }
        }

        if ($enrolleddate_from) {
            $wheres .= "AND (rc.enrolment_startdate >= " . $enrolleddate_from . ") ";
        }
        if ($enrolleddate_to) {
            $wheres .= "AND (rc.enrolment_startdate <= " . $enrolleddate_to . ") ";
        }
        if ($completiondate_from) {
            $wheres .= "AND (rc.completion_date >= " . $completiondate_from . ") ";
        }
        if ($completiondate_to) {
            $wheres .= "AND (rc.completion_date <= " . $completiondate_to . ") ";
        }

        $groupby = ' GROUP BY ';

        return $this->query($wheres, $global_params, $hierarchy_query, $groupby, $pagination, $no_pagination);
    }

    private function query(&$wheres, &$global_params, &$hierarchy_query, &$groupby, $pagination, $no_pagination = false) {
        global $CFG, $DB;
        $dboptions = $DB->export_dbconfig()->dboptions;
        $collation = $dboptions['dbcollation'];
        $collationinfo = explode('_', $collation);
        $charset = reset($collationinfo);
        $DBH = new PDO("mysql:host=$CFG->dbhost;dbname=$CFG->dbname;charset=$charset", $CFG->dbuser, $CFG->dbpass);
        $default_fields = $this->generate_default_user_field_filter($wheres, $global_params);
        $array_dynamic_query = $this->generate_custom_user_profile_filter_and_fields();
        if (!empty($array_dynamic_query['p_dynamic'])) {
            $global_params = array_merge($array_dynamic_query['p_dynamic'], $global_params);
        }
        $wheres .= $array_dynamic_query['where'];
        $select_count = "SELECT COUNT(*)  ";
        $select_columns =
            "select u.id as userid, u.firstname as firstname, u.lastname as lastname, rc.*" . $default_fields
            . $array_dynamic_query['fields'];
        //        $select_columns =
        //            "select u.id as userid, u.firstname as firstname, u.lastname as lastname, rc.*" . $default_fields
        //                .$array_dynamic_query['fields'].$hierarchy_query['fields'];
        $query_table =
            " FROM mdl_user as u
             LEFT JOIN mdl_report_completion as rc on rc.user_id = u.id
             LEFT JOIN mdl_course c On c.id = rc.course_id
             ";
        // ------------------------------------------------------END OF CURRENT QUERY--------------------------------------------

        $order_by = $this->payload['order_by'];
        if (empty($order_by)) {
            $orderby = " ORDER BY rc.course_name, u.firstname";
        } else {
            if ($order_by['key'] == 'full_name') {
                $order_by['key'] = 'firstname';
            }
            $orderby = " ORDER BY " . $order_by['key'] . ' ' . $order_by['order'];
        }
        if (trim($groupby) == 'GROUP BY') $groupby = "";
        $count = 0;
        $result = [];
        if ($this->payload['display'] == 'excel' || $this->payload['display'] == 'pdf' || $no_pagination) {
            $query = sprintf($select_columns . $query_table . $array_dynamic_query['filter_table'] . $array_dynamic_query['display_table'] . $wheres . $orderby . $groupby);
            $STH = $DBH->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $STH->execute($global_params);

            if ($STH) {
                $STH->setFetchMode(PDO::FETCH_ASSOC);
                $result = $STH->fetchall();
            }
        } else {
            $count_query = $select_count . $query_table . $array_dynamic_query['filter_table'] . $wheres . $groupby;
            $count = $DB->count_records_sql($count_query, $global_params);
            if ($count > 0) {
                $query = $select_columns . $query_table . $array_dynamic_query['filter_table'] . $array_dynamic_query['display_table'] . $wheres . $orderby . $groupby . $pagination;
                $STH = $DBH->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                $STH->execute($global_params);

                if ($STH) {
                    $STH->setFetchMode(PDO::FETCH_ASSOC);
                    $result = $STH->fetchall();
                }
            }
        }
        return ['count' => $count, 'result' => $result];
    }

    private function export_excel($headers, &$rows, $filename, $type = null) {
        // output headers so that the file is downloaded rather than displayed
        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

        // do not cache the file
        header('Pragma: no-cache');
        header('Expires: 0');

        // create a file pointer connected to the output stream
        $file = fopen('php://output', 'w');

        // send the column headers
        $csv_headers = [];
        foreach ($headers as $header) {
            $csv_headers[] = $header['display'];
        }
        fputcsv($file, array_values($csv_headers));

        // output each row of the data
        if (empty($type)) {
            foreach ($rows as $row) {
                $row_data = [];
                foreach ($headers as $header) {
                    $row_data[] = $row[$header['key']];
                }
                fputcsv($file, $row_data);
            }
        } else if ($type == 'course') {
            foreach ($rows as $user) {
                foreach ($user as $course) {
                    $row_data = [];
                    foreach ($headers as $header) {
                        $row_data[] = $course[$header['key']];
                    }
                    fputcsv($file, $row_data);
                }
            }
        }

        exit();
    }

    /***
     * Created by Fan Li on 17/02/2021.
     * @param $headers report headers, configured headers will be added in the function
     * @return array customised user fields array, format: => type
     */
    private function add_config_headers(&$headers, $toLowerCase = false) {
        global $DB;
        $display_default_profile_fields = get_config('block_training_report', 'display_user_default_fields');
        $display_default_profile_fields = json_decode($display_default_profile_fields, true);
        foreach ($display_default_profile_fields as $key => $field) {
            $headers[] = ['key' => $toLowerCase ? strtolower($key) : $key, 'display' => $field['name']];
        }
        $profile_fields_type = [];
        $display_user_profile_fields = get_config('block_training_report', 'display_user_profile_fields');
        $display_user_profile_fields = json_decode($display_user_profile_fields, true);
        foreach ($display_user_profile_fields as $key => $field) {
            $headers[] = ['key' => $toLowerCase ? strtolower($key) : $key, 'display' => $field['name']];
            $field_record = $DB->get_record('user_info_field', ['shortname' => $key]);
            $profile_fields_type[$toLowerCase ? strtolower($key) : $key] = $field_record->datatype;
        }
        return $profile_fields_type;
    }

    /***
     * Created by Fan Li on 17/02/2021.
     * @param $record record to be formatted
     * @param $profile_fields_type array of field_shortname => type
     */
    private function format_config_fields(&$record, $profile_fields_type) {
        if (!empty($record['country'])) {
            try {
                $record['country'] = get_string($record['country'], 'countries');
            } catch (exception $e) {
                $e->getMessage();
            }
        }
        if(!isset($record['lastaccess']) || empty($record['lastaccess'])) {
            $record['lastaccess'] = "";
        } else {
            $record['lastaccess'] = date('d/m/Y', $record['lastaccess']);
        }
        if((isset($record['completion_state']) && $record['completion_state'] == 'Completed') || (isset($record['completion_percentage']) && $record['completion_percentage'] == 1)) {
            if ($record['completion_date'] == 0 || $record['completion_date'] == "" || !empty($record['completion_date'])) {
                $record['completion_date'] = ($record['completion_date'] == 0 || $record['completion_date'] == "" || is_null($record['completion_date'])) ? "" : date('d/m/Y', $record['completion_date']);
            }
        } elseif (isset($record['completion_date'])) {
            $record['completion_date'] = '';
        }

        if ($record['enrolment_startdate'] == 0 || $record['enrolment_startdate'] == "" || !empty($record['enrolment_startdate'])) {
            $record['enrolment_startdate'] = ($record['enrolment_startdate'] == 0 || $record['enrolment_startdate'] == "" || is_null($record['enrolment_startdate'])) ? "" : date('d/m/Y', $record['enrolment_startdate']);
        }

        if ($record['suspended'] == 1) {
            $record['suspended'] = 'Yes';
        } else {
            $record['suspended'] = 'No';
        }

        $record['completion_percentage'] =
        round($record['completion_percentage'] * 100, 2) . '%';

        foreach ($profile_fields_type as $field => $type) {
            switch ($type) {
                case 'datetime':
                    $record[$field] = ($record[$field] == 0 || $record[$field] == "" || is_null($record[$field])) ? "" : date('d/m/Y', $record[$field]);
                    break;
                case 'checkbox':
                    $record[$field] = ($record[$field] == 1) ? "Yes" : "No";
                    break;
                case 'multiselect':
                    if ($this->payload['display'] == 'excel') {
                        $record[$field] =
                            str_replace("\r", ";", $record[$field]);
                        $record[$field] =
                            str_replace("\n", ';', $record[$field]);
                        $record[$field] = preg_replace('/;+/i', ";", $record[$field]);
                    }
                    break;
                default:
                    $record[$field] = ($record[$field] == "" || is_null($record[$field])) ? "" : sanitize_string($record[$field]);
                    break;
            }
        }
    }

    private function returnNull() {
        $data = new stdClass();
        $data->code = 0;
        $data->total_page = 0;
        $data->page = 1;
        $data->per_page = $this->payload['recordperpage'];
        $data->data = [];
        $data->headers = [];
        $this->result->data = $data;
        if ($this->payload['display'] == 'excel') {
            $this->result->data = $this->export_excel($data->headers, $data->data, 'General_report_' . date("Y-m-d"));
        } else {
            $this->result->data = $data;
        }
    }

    private function generate_default_user_field_filter(&$wheres, &$global_params) {
        global $DB;
        $filter_default_fields = get_config('block_training_report', 'filter_user_default_fields');
        $filter_default_fields = json_decode($filter_default_fields, true);
        foreach ($filter_default_fields as $field_name => $info) {
            $type = $info['type'];
            if ($type == 'datetime') {
                $from = $this->payload[$field_name . '_from'];
                if (!empty($from)) {
                    $wheres .= " AND (u." . $field_name . " >= '" . $from . "') ";
                }
                $to = $this->payload[$field_name . '_to'];
                if (!empty($to)) {
                    $wheres .= " AND (u." . $field_name . " <= '" . $to . "') ";
                }
            } else {
                if (!empty($this->payload[$field_name])) {
                    list($q_general, $p_general) = $DB->get_in_or_equal($this->payload[$field_name]);
                    $wheres .= " AND (u." . $field_name . " " . $q_general . ") ";
                    $global_params = array_merge($global_params, $p_general);
                }
            }
        }
        $display_user_default_fields = get_config('block_training_report', 'display_user_default_fields');
        $display_user_default_fields = json_decode($display_user_default_fields, true);
        $select_fields = '';
        if (!empty($display_user_default_fields)) {
            foreach ($display_user_default_fields as $field_name => $info) {
                $select_fields .= " , u.$field_name ";
            }
        }
        return $select_fields;
    }

    private function generate_custom_user_profile_filter_and_fields() {
        global $DB;
        $array_query = array('fields' => '', 'display_table' => '', 'filter_table' => '', 'where' => '');
        $array_query['p_dynamic'] = array();

        //filter fields
        $filter_user_profile_fields = get_config('block_training_report', 'filter_user_profile_fields');
        $filter_user_profile_fields = json_decode($filter_user_profile_fields, true);
        if (!empty($filter_user_profile_fields)) {
            foreach ($filter_user_profile_fields as $field_name => $info) {
                $field_record = $DB->get_record('user_info_field', ['shortname' => $field_name]);
                $fieldid = $field_record->id;
                $alias_table_name = $field_record->shortname . "_filter_info";
                //where
                $type = $info['type'];
                $table_join = "";
                if ($type == 'datetime') {
                    $from = $this->payload[$field_name . '_from'];
                    if (!empty($from)) {
                        //                        $array_query['where'] .= " AND ($alias_table_name." . $field_name . " >= '" . $from . "') ";
                        $table_join .= " AND (CAST($alias_table_name.data AS unsigned) >= ?) ";
                        $array_query['p_dynamic'] = array_merge($array_query['p_dynamic'], [$from]);
                    }
                    $to = $this->payload[$field_name . '_to'];
                    if (!empty($to)) {
                        //                        $array_query['where'] .= " AND ($alias_table_name." . $field_name . " <= '" . $to . "') ";
                        $table_join .= " AND (CAST($alias_table_name.data AS unsigned) <= ?) ";
                        $array_query['p_dynamic'] = array_merge($array_query['p_dynamic'], [$to]);
                    }
                    if (empty($from) && empty($to)) {
                        continue;
                    }
                } elseif ($type == 'multiselect') {
                    if (!empty($this->payload[$field_name])) {
                        if (!is_array($this->payload[$field_name])) {
                            $table_join = " AND (" . $DB->sql_compare_text($alias_table_name . '.data') . " " . "LIKE '%{$this->payload[$field_name]}%') ";
                        } else {
                            $first = true;
                            foreach ($this->payload[$field_name] as $key => $value) {
                                if ($first) {
                                    $table_join = " AND (" . $DB->sql_compare_text($alias_table_name . '.data') . " " . "LIKE '%{$value}%' ";
                                    $first = false;
                                } else {
                                    $table_join .= " OR " . $DB->sql_compare_text($alias_table_name . '.data') . " " . "LIKE '%{$value}%'";
                                }
                            }
                            $table_join .= ") ";
                        }
                    } else {
                        continue;
                    }
                } else {
                    if (!empty($this->payload[$field_name])) {
                        list($q_dynamic, $p_dynamic) = $DB->get_in_or_equal($this->payload[$field_record->shortname]);
                        $empty_condition = '';
                        //                        $array_query['where'] .= " AND (" . $DB->sql_compare_text($alias_table_name . '.data') . " " .$q_dynamic .$empty_condition. ")";
                        $table_join = " AND (" . $DB->sql_compare_text($alias_table_name . '.data') . " " . $q_dynamic . $empty_condition . ")";
                        $array_query['p_dynamic'] = array_merge($array_query['p_dynamic'], $p_dynamic);
                    } else {
                        continue;
                    }
                }
                //Table
                if ($table_join) {
                    $array_query['filter_table'] .= " JOIN mdl_user_info_data " . $alias_table_name .
                        "\n 
      ON (u.id=" . $alias_table_name . ".userid AND " . $alias_table_name . ".fieldid=" . $fieldid . $table_join . ")" . "\n";
                }

                //Field
                //                $array_query['fields'] .= ", " . $alias_table_name . ".data as " . $field_record->shortname . "\n";
            }
        }

        //display fields
        $display_user_profile_fields = get_config('block_training_report', 'display_user_profile_fields');
        $display_user_profile_fields = json_decode($display_user_profile_fields, true);
        if (!empty($display_user_profile_fields)) {
            foreach ($display_user_profile_fields as $field_name => $info) {
                $field_record = $DB->get_record('user_info_field', ['shortname' => $field_name]);
                $fieldid = $field_record->id;
                $alias_table_name = $field_record->shortname . "_display_info";
                //Table
                $array_query['display_table'] .= "LEFT JOIN mdl_user_info_data " . $alias_table_name . "\n 
      ON (u.id=" . $alias_table_name . ".userid AND " . $alias_table_name . ".fieldid=" . $fieldid . ")" . "\n";
                //Field
                $array_query['fields'] .= ", " . $alias_table_name . ".data as " . $field_record->shortname . "\n";
            }
        }
        return $array_query;
    }

    private function get_user_profile_field_options($field_name) {
        global $DB;
        $field_record = $DB->get_record('user_info_field', ['shortname' => $field_name]);
        $fieldid = $field_record->id;
        $sql = "select DISTINCT(muid.data) from {user_info_data} muid where muid.fieldid = ? order by muid.data";
        $records = $DB->get_records_sql($sql, [$fieldid]);
        $options = [];
        $unique_values = [];
        foreach ($records as $key => $value) {
            if(empty($key)) continue;

            if ($field_record->datatype === 'multiselect') {
                $multi_values = array_map('trim', explode("\n", $key));
                foreach ($multi_values as $single_value) {
                    if (isset($unique_values[$single_value])) {
                        continue;
                    }

                    $options[] = [
                        'value' => $single_value,
                        'label' => $single_value,
                    ];
                    $unique_values[$single_value] = true;
                }

                continue;
            }

            $option['value'] = $key;
            $option['label'] = $key;
            $options[] = $option;
        }
        return $options;
    }

    private function get_user_organisation($user_id){
        global $DB;
        $sql = "select DISTINCT(muid.data) from {user_info_data} muid where muid.fieldid = 1 and muid.userid = ?";
        $record = $DB->get_record_sql($sql, [$user_id]);
        $options = [];
        $options[] = array('value' => $record->data, 'label' => $record->data);
        return $options;
    }

    /**
     * sort to be the same order as category course list in the course management.
     */
    private function get_sorted_course_category(&$course_category) {
        if (is_array($course_category)) {
            $this->sort_course_category($course_category);
            foreach ($course_category as $key => $value) {
                if (isset($value->children) && is_array($value->children)) {
                    $this->sort_course_category($value->children);
                }
            }
        }
    }

    private function sort_course_category(&$course_category) {
        usort($course_category, function ($a, $b) {
            if ($a->type == "category" && $b->type == "course") {
                return -1;
            }

            if ($a->type == "course" && $b->type == "category") {
                return 1;
            }

            return $a->sortorder - $b->sortorder;
        });
    }
};
$api_instance = new API();
exit;
