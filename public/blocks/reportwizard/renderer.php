<?php

defined('MOODLE_INTERNAL') || die();

require_once("src/lib.php");
require_once("src/reporting-lib.php");
require_once('src/classes/report.php');
require_once('src/classes/reports_helper.php');

class block_reportwizard_renderer_base extends plugin_renderer_base
{

	public function src_path()
	{
		global $CFG;
		return $CFG->wwwroot . '/blocks/reportwizard/src';
	}

	public function report_in_table()
	{
	}

	public function form_report($report)
	{
		global $CFG;
		//'.$this->src_path().'/create_report.php
		$html = '';
		$html .= '<form method="POST" action="" id="form_create_report" class="form_report clearfix">';
		$html .= '<table class="generaltable" style="">';

		$html .= $this->form_input_report_name($report);

		$html .= $this->form_input_report_type($report);

		$html .= $this->form_input_report_category_course($report);

		$html .= $this->form_input_report_completion($report);

		// @30/07/2018 enhancement
		$html .= $this->form_input_report_enroldate($report);
		// $html .= $this->form_input_report_enrol_period_setting($report);
		// @30/07/2018 enhancement
		$html .= $this->form_input_report_completedate($report);
		// $html .= $this->form_input_report_complete_period_setting($report);    

		$html .= $this->form_input_report_hierarchy_nodes($report);

		$html .= $this->form_input_report_info_fields($report);

		$html .= $this->form_input_report_access($report);

		$html .= $this->form_input_report_shareto($report);

		$html .= '</table>';

		$html .= '<div class="float-left">';
		$html .= '<input style="" type="submit" name="submit" class="btn btn-primary" value="Save"/>';
		$html .= '<a class="btn btn-primary" href="' . $this->src_path() . '/myreports.php">Cancel</a>';
		$html .= '</div>';



		$html .= '</form>';


		return $html;
	}

	public function form_input_report_name($report)
	{

		$html = '';
		$html .= '<tr>';
		$html .= '<td>' . get_string('reportname', 'block_reportwizard') . '</td>';

		if ($report) {
			$html .= '<td><input required type="text" name="reportname" class="reportname" value="' . $report->name . '"></td>';
		} else {
			$html .= '<td><input required type="text" name="reportname" class="reportname"></td>';
		}
		$html .= '</tr>';

		return $html;
	}

	public function form_input_report_type($report)
	{
		// general 1
		// course overview 2
		// activity 3

		$general_checked = '';
		$courseoverview_checked = '';
		$activity_checked = '';
		$mandatory_online_checked = '';

		if ($report) {
			switch ($report->type) {
				case block_reportwizard_report::REPORT_TYPE_GENERAL:
					$general_checked = 'checked';
					break;
				case block_reportwizard_report::REPORT_TYPE_COURSEOVERVIEW:
					$courseoverview_checked = 'checked';
					break;
				case block_reportwizard_report::REPORT_TYPE_ACTIVITY:
					$activity_checked = 'checked';
					break;
				case block_reportwizard_report::REPORT_TYPE_MANDATORY_ONLINE:
					$mandatory_online_checked = 'checked';
					break;
				default:
					$general_checked = 'checked';
					break;
			}
		} else {
			$general_checked = 'checked';
		}

		$html = '';

		$html .= '<tr>';
		$html .= '<td>' . get_string('reporttype', 'block_reportwizard') . ': </td>';

		$html .= '<td>
					<input type="radio" name="reporttype" class="reporttype" id="input_reporttype_general" 
					required value="' . block_reportwizard_report::REPORT_TYPE_GENERAL . '" ' . $general_checked . '>
					<label for="input_reporttype_general">General Reports</label></br></br>';
		$html .= 	'<input type="radio" name="reporttype" class="reporttype" id="input_reporttype_courseoverview" 
					required value="' . block_reportwizard_report::REPORT_TYPE_COURSEOVERVIEW . '" ' . $courseoverview_checked . '>
					<label for="input_reporttype_courseoverview">SGL & Training module reports</label></br></br>';
		$html .= 	'<input type="radio" name="reporttype" class="reporttype" id="input_reporttype_activity" 
					required value="' . block_reportwizard_report::REPORT_TYPE_ACTIVITY . '" ' . $activity_checked . '>
					<label for="input_reporttype_activity">SGL element summaries</label></br></br>';
		$html .= '</td>';

		$html .= '</tr>';

		return $html;
	}


	public function form_input_report_category_course($report)
	{
		global $DB;

		$coursecats = block_reportwizard_getCourses_Category();

		$html = '';

		// category and course selector
		$html .= '<tr id="report_category_course">';
		$html .= 	'<td>' . get_string('categorycourse', 'block_reportwizard') . ': </td>';
		$html .= 	'<td>';
		// @30/07/2018 enhancement
		$html .= 		'<select name="category_course[]" id="input_category_course" multiple class="chosen-select">';
		$object_ids = [];
		if (!empty($report->object_id)) {
			$object_ids = explode(',', $report->object_id);
		}
		foreach ($coursecats as $key => $coursecat) {
			$selected = '';
			if (in_array($key, $object_ids)) {
				$selected = 'selected';
			}
			// if (isset($report->object_type)) {			
			// 	// check if this option should be selected
			// 	if ($report->object_type == block_reportwizard_report::OBJECT_CATEGORY ) {

			// 		if (strpos($key, '{category}') == 0 && substr($key, strlen('{category}')) == $report->object_id ) {
			// 			$selected = 'selected';
			// 		}

			// 	}elseif ($report->object_type == block_reportwizard_report::OBJECT_COURSE ) {
			// 		if (strpos($key, '{category}') == false && $key == $report->object_id ) {
			// 			$selected = 'selected';
			// 		}
			// 	}
			// }

			$sub_html = '<option value="' . $key . '" ' . $selected . '>' . $coursecat . '</option>';
			$html .= $sub_html;
		}

		$html .= 		'</select>';
		$html .= 	'</td>';
		$html .= '</tr>';


		// activity course selector
		$allcourses = $DB->get_records_sql('SELECT id, fullname FROM {course} WHERE category <> ? AND (format = ? OR format = ?) order by fullname', array(0, 'topics', 'singleactivity'));

		$html .= '<tr id="report_activity_course">';
		$html .= '<td>Course</td>';
		$html .= '<td>';
		$html .= 	'<select required name="activity_course">';

		foreach ($allcourses as $key => $course) {
			$selected = '';
			if (!empty($report->object_id) && $key == $report->object_id) {
				$selected = 'selected';
			}
			// if (isset($report->object_type) 
			// 	&& $report->object_type == block_reportwizard_report::OBJECT_COURSE 
			// 	&& $report->object_id == $course->id) {
			// 	$selected = 'selected';
			// }

			$html .= '<option value="' . $course->id . '" ' . $selected . '>' . $course->fullname . '</option>';
		}

		$html .= 	'</select>';
		$html .= '</td>';

		$html .= '</tr>';


		return $html;
	}


	public function form_input_report_hierarchy_nodes($report)
	{

		$value = ($report) ? $report->hierarchy_nodes : '';
		$nodeids = ($report) ? get_nodeids_from_nodes($report->hierarchy_nodes) : '';

		$html = '';
		$html .= '<input type="hidden" name="hierarchy" id="hierarchy"/>';
		$html .= '<input type="hidden" name="selectednodes" id="selectednodes" value="' . $nodeids . '"/>';
		$html .= '<input type="hidden" name="selectednodenames" id="selectednodenames" value="' . $value . '"" />';
		$html .= '<tr>';
		$html .= '<td>' . get_string('hierarchy', 'block_reportwizard') . '</td>';
		$html .= '<td>';
		$html .= '<input type="text" name="nodes" id="hie_search" class="" placeholder="Search for nodes" value="' . $value . '">';
		$html .= '<div id="jstree"></div>';
		$html .= '<p>Selected node(s): <strong id="selection">' . $value . '</strong></p>';
		$html .= '</td>';
		$html .= '</tr>';
		return $html;
	}

	public function form_input_report_enrol_period_setting($report)
	{

		$condition_options = array(
			block_reportwizard_report::PERIOD_CONDITION_BEFORE => 'before',
			block_reportwizard_report::PERIOD_CONDITION_AFTER => 'after'
		);

		$unit_options = array(
			block_reportwizard_report::PERIOD_UNIT_DAY => 'day(s)',
			block_reportwizard_report::PERIOD_UNIT_WEEK => 'week(s)',
			block_reportwizard_report::PERIOD_UNIT_MONTH => 'month(s)',
			block_reportwizard_report::PERIOD_UNIT_YEAR => 'year(s)'
		);

		$html = '';

		$html .= '<tr>';
		$html .= '<td>' . get_string('enrolled_date', 'block_reportwizard') . '</td>';
		$html .= '<td><span>is </span>';

		// before/after
		$html .= 	'<select name="enrol_period_condition" id="input_enrol_period_condition">';
		foreach ($condition_options as $value => $text) {
			$selected = '';

			if ($report) {
				if ($report->enrol_period_condition == $value) {
					$selected = 'selected';
				}
			}

			$html .= '<option value="' . $value . '" ' . $selected . '>' . $text . '</option>';
		}
		$html .= 	'</select>';

		$number_value = '0';
		if ($report) {
			$number_value = $report->enrol_period_number;
		}

		// number of unit
		$html .= 	'<input type="number" step="1" min="0" defaultValue="0" name="enrol_period_number" id="input_enrol_period_number" class="" value="' . $number_value . '" /> ';

		// unit
		$html .= 	'<select name="enrol_period_unit" id="input_enrol_period_unit">';
		foreach ($unit_options as $value => $text) {
			$selected = '';

			if ($report) {
				if ($report->enrol_period_unit == $value) {
					$selected = 'selected';
				}
			}

			$html .= '<option value="' . $value . '" ' . $selected . '>' . $text . '</option>';
		}
		$html .= 	'</select>';
		$html .= '<sapn> ago</span></td>';
		$html .= '</tr>';

		return $html;
	}

	public function form_input_report_complete_period_setting($report)
	{

		$condition_options = array(
			block_reportwizard_report::PERIOD_CONDITION_BEFORE => 'before',
			block_reportwizard_report::PERIOD_CONDITION_AFTER => 'after'
		);

		$unit_options = array(
			block_reportwizard_report::PERIOD_UNIT_DAY => 'day(s)',
			block_reportwizard_report::PERIOD_UNIT_WEEK => 'week(s)',
			block_reportwizard_report::PERIOD_UNIT_MONTH => 'month(s)',
			block_reportwizard_report::PERIOD_UNIT_YEAR => 'year(s)'
		);

		$html = '';

		$html .= '<tr>';
		$html .= '<td>' . 'Completion date: ' . '</td>';
		$html .= '<td><span>is </span>';

		// before/after
		$html .= 	'<select name="complete_period_condition" id="input_complete_period_condition">';
		foreach ($condition_options as $value => $text) {
			$selected = '';

			if ($report) {
				if ($report->complete_period_condition == $value) {
					$selected = 'selected';
				}
			}

			$html .= '<option value="' . $value . '" ' . $selected . '>' . $text . '</option>';
		}
		$html .= 	'</select>';

		// number of unit
		$number_value = '0';
		if ($report) {
			$number_value = $report->complete_period_number;
		}

		$html .= 	'<input type="number" step="1" min="0" defaultValue="0" name="complete_period_number" id="input_complete_period_number" class="" value="' . $number_value . '" /> ';

		// unit
		$html .= 	'<select name="complete_period_unit" id="input_complete_period_unit">';
		foreach ($unit_options as $value => $text) {
			$selected = '';

			if ($report) {
				if ($report->complete_period_unit == $value) {
					$selected = 'selected';
				}
			}

			$html .= '<option value="' . $value . '" ' . $selected . '>' . $text . '</option>';
		}
		$html .= 	'</select>';
		$html .= '<sapn> ago</span></td>';
		$html .= '</tr>';

		return $html;
	}

	// deprecated: use dynamic period instead
	// @30/07/2018 reuse this function with slightly enhancement
	public function form_input_report_enroldate($report)
	{

		$enrol_date_from = '';
		$enrol_date_to = '';

		if (!empty($report->enrol_date_from)) {
			$enrol_date_from = date('d/m/Y', $report->enrol_date_from);
		}
		if (!empty($report->enrol_date_to)) {
			$enrol_date_to = date('d/m/Y', $report->enrol_date_to);
		}

		$html = '';

		$html .= '<tr id="report_enrolleddate_condition">';
		$html .= '<td>' . get_string('enrolled_date', 'block_reportwizard') . '</td>';
		$html .= '<td>';

		$html .= '<div class="input-prepend">'; // from
		$html .= '<span class="add-on">From</span>';
		$html .= '<input type="text" name="enrol_date_from" id="input_enrol_date_from" class="datepicker" value="' . $enrol_date_from . '" /> ';
		$html .= '</div> ';

		$html .= '<div class="input-prepend">'; // to
		$html .= '<span class="add-on">To</span>';
		$html .= '<input type="text" name="enrol_date_to" id="input_enrol_date_to" class="datepicker" value="' . $enrol_date_to . '" /> ';
		$html .= '</div>';

		$html .= '</td>';
		$html .= '</tr>';

		// $html .= '<tr>';
		// $html .= '<td>'.'Enrol date to: '.'</td>';
		// $html .= '<td><input type="text" name="enrol_date_to" id="input_enrol_date_to" class="datepicker" value="'.$enrol_date_to.'" /> ';
		// $html .= '</tr>';

		return $html;
	}

	// deprecated: use dynamic period instead
	// @30/07/2018 reuse this function with slightly enhancement
	public function form_input_report_completedate($report)
	{

		$complete_date_from = '';
		$complete_date_to = '';

		if (!empty($report->complete_date_from)) {
			$complete_date_from = date('d/m/Y', $report->complete_date_from);
		}
		if (!empty($report->complete_date_to)) {
			$complete_date_to = date('d/m/Y', $report->complete_date_to);
		}

		$html = '';

		$html .= '<tr>';
		$html .= '<td>' . get_string('completion_date', 'block_reportwizard') . '</td>';
		$html .= '<td>';

		$html .= '<div class="input-prepend">'; // from
		$html .= '<span class="add-on">From</span>';
		$html .= '<input type="text" name="complete_date_from" id="input_complete_date_from" class="datepicker" value="' . $complete_date_from . '" /> ';
		$html .= '</div> ';

		$html .= '<div class="input-prepend">'; // to
		$html .= '<span class="add-on">To</span>';
		$html .= '<input type="text" name="complete_date_to" id="input_complete_date_to" class="datepicker" value="' . $complete_date_to . '" /> ';
		$html .= '</div> ';

		$html .= '</td>';
		$html .= '</tr>';

		// $html .= '<tr>';
		// $html .= '<td>'.'Complete date to: '.'</td>';
		// $html .= '<td><input type="text" name="complete_date_to" id="input_complete_date_to" class="datepicker" value="'.$complete_date_to.'" /> ';
		// $html .= '</tr>';

		return $html;
	}

	public function form_input_report_completion($report)
	{
		// all 2
		// completed 1
		// not completed 0

		$all_checked = '';
		$completed_checked = '';
		$notcompleted_checked = '';

		if ($report) {
			switch ($report->completion_status) {
				case block_reportwizard_report::COMPLETION_ALL:
					$all_checked = 'checked';
					break;
				case block_reportwizard_report::COMPLETION_COMPLETED:
					$completed_checked = 'checked';
					break;
				case block_reportwizard_report::COMPLETION_INCOMPLETE:
					$notcompleted_checked = 'checked';
					break;

				default:
					$all_checked = 'checked';
					break;
			}
		} else {
			$all_checked = 'checked';
		}

		$html = '';

		$html .= '<tr id="report_completion_condition">';
		$html .= '<td>' . get_string('completion', 'block_reportwizard') . '</td>';
		$html .= '<td>
					<input type="radio" name="completion" id="input_completion_all" 
					required value="' . block_reportwizard_report::COMPLETION_ALL . '" ' . $all_checked . '>
					<label for="input_completion_all">All results</label></br></br>';
		$html .= 	'<input type="radio" name="completion" id="input_completion_completed" 
					required value="' . block_reportwizard_report::COMPLETION_COMPLETED . '" ' . $completed_checked . '>
					<label for="input_completion_completed">' . get_string('completed', 'block_reportwizard') . '</label></br></br>';
		$html .= 	'<input type="radio" name="completion" id="input_completion_notcompleted" 
					required value="' . block_reportwizard_report::COMPLETION_INCOMPLETE . '" ' . $notcompleted_checked . '>
					<label for="input_completion_notcompleted">' . get_string('incomplete', 'block_reportwizard') . '</label>';
		$html .= '</td>';
		$html .= '</tr>';

		return $html;
	}


	public function form_input_report_info_fields($report)
	{
		global $DB;

		//$fields = $DB->get_records('user_info_field', null, 'sortorder ASC');
		$sql = <<<EOT
SELECT uif.*
from mdl_reporting_filter rf 
join mdl_user_info_field uif ON rf.user_info_field_id=uif.id 
ORDER BY uif.sortorder ASC
EOT;
		$fields = $DB->get_records_sql($sql, null);

		$html = '';

		foreach ($fields as $key => $field) {

			$value = '';

			if ($report) {
				$filter = $DB->get_record('report_wzd_infofield_filter', array('report_id' => $report->id, 'infofield_id' => $field->id));
				if ($filter) {
					$value = $filter->infofield_data;
				}
			}

			$html .= '<tr>';
			$html .= '<td>' . $field->name . '</td>';
			$html .= '<td>';

			// handle different infofield datatype
			switch ($field->datatype) {
					// @30/07/2018 enhancement
					// case 'menu':
					// 	$options = explode("\n",$field->param1);

					// 	// force infofield_id to stay in $_POST when no opteion selected
					// 	$html .= '<input type="hidden" value="force_post" name="infofield_'.$field->id.'[]" >';
					// 	// add [] after name to make multiple select work
					// 	$html .= '<select multiple="multiple" name="infofield_'.$field->id.'[]" >';
					// 	foreach ($options as $key => $option) {
					// 		//skip white space option
					// 		if($option === ''){
					// 			continue;
					// 		}
					// 		$selected = '';
					// 		if (strpos($value, $option) !== false) {
					// 			$selected = 'selected';
					// 		}
					// 		$html .= '<option value="'.$option.'" '.$selected.'>'.$option.'</option>';
					// 	}
					// 	$html .= '</select>';
					// 	$html .= '<p> Press "Ctrl" while click to select multiple options or deselect.</p>';
					// 	break;

				case 'checkbox':
					$empty_selected = '';
					$checked_selected = '';
					$unchecked_selected = '';
					switch ($value) {
						case '':
							$empty_selected = 'selected';
							break;
						case '1':
							$checked_selected = 'selected';
							break;
						case '0':
							$unchecked_selected = 'selected';
							break;
						default:
							$empty_selected = 'selected';
							break;
					}

					$html .= '<select name="infofield_' . $field->id . '" >';
					$html .= '<option value="" ' . $empty_selected . '> </option>';
					$html .= '<option value="1" ' . $checked_selected . '>Checked</option>';
					$html .= '<option value="0" ' . $unchecked_selected . '>Unchecked</option>';
					$html .= '</select>';
					break;

				case 'datetime':
					$datetime_values = json_decode($value, true);
					$value_from = null;
					$value_to = null;
					if (!empty($datetime_values['from'])) {
						$value_from = $datetime_values['from'];
					}
					if (!empty($datetime_values['to'])) {
						$value_to = $datetime_values['to'];
					}
					$html .= '<div class="input-prepend">'; // from
					$html .= '<span class="add-on">From</span>';
					$html .= '<input type="text" class="datepicker" name="infofield_' . $field->id . '[from]" id="input_' . $field->shortname . '_from" value="' . $value_from . '">';
					$html .= '</div> ';

					$html .= '<div class="input-prepend">'; // to
					$html .= '<span class="add-on">To</span>';
					$html .= '<input type="text" class="datepicker" name="infofield_' . $field->id . '[to]" id="input_' . $field->shortname . '_to" value="' . $value_to . '">';
					$html .= '</div>';
					break;

					// text/menu inputs
				default:
					$query = "SELECT data, data as value FROM {user_info_data} WHERE fieldid = ? and data <> '' GROUP BY data ORDER BY data";
					$options = $DB->get_records_sql_menu($query, [$field->id]);
					// add [] after name to make multiple select work
					$html .= '<select class="chosen-select" multiple="multiple" name="infofield_' . $field->id . '[]" >';
					foreach ($options as $key => $option) {
						//skip white space option
						if ($option === '') {
							continue;
						}
						$selected = '';
						if (strpos($value, $option) !== false) {
							$selected = 'selected';
						}
						$html .= '<option value="' . $option . '" ' . $selected . '>' . $option . '</option>';
					}
					$html .= '</select>';
					// $html .= '<input type="text" name="infofield_'.$field->id.'" id="input_'.$field->shortname.'" value="'.$value.'">';
					break;
			}

			$html .= '</td>';


			$html .= '</tr>';
		}

		return $html;
	}

	public function form_input_report_access($report)
	{
		// private 0
		// public 1

		$private_checked = '';
		$public_checked = '';

		if ($report) {
			switch ($report->access_type) {
				case block_reportwizard_report::ACCESS_PRIVATE:
					$private_checked = 'checked';
					break;
				case block_reportwizard_report::ACCESS_PUBLIC:
					$public_checked = 'checked';

				default:
					$private_checked = 'checked';
					break;
			}
		} else {
			$private_checked = 'checked';
		}

		$html  = '';
		$html .= '<tr>';
		$html .= '<td>' . get_string('access', 'block_reportwizard') . '</td>';
		$html .= '<td>
					<input type="radio" name="access_type" id="input_access_private" 
					required  value="' . block_reportwizard_report::ACCESS_PRIVATE . '" ' . $private_checked . '>
					<label for="input_access_private">' . get_string('private', 'block_reportwizard') . '</label></br></br>';
		$html .= 	'<input type="radio" name="access_type" id="input_access_public" 
					required value="' . block_reportwizard_report::ACCESS_PUBLIC . '" ' . $public_checked . '>
					<label for="input_access_public">' . get_string('public', 'block_reportwizard') . '</label></br></br>';
		$html .= '</td>';
		$html .= '</tr>';

		return $html;
	}

	public function form_input_report_shareto($report)
	{
		$value = ($report) ? $report->share_to : '';
		$nodeids = ($report) ? get_nodeids_from_nodes($report->share_to) : '';
		$html = '';
		$html .= '<input type="hidden" name="hierarchy_shareto" id="hierarchy_shareto"/>';
		$html .= '<input type="hidden" name="selectednodes_shareto" id="selectednodes_shareto" value="' . $nodeids . '"/>';
		$html .= '<input type="hidden" name="selectednodenames_shareto" id="selectednodenames_shareto" value="' . $value . '"/>';
		$html .= '<tr 	id="report_share_to" 
						data-private="' . block_reportwizard_report::ACCESS_PRIVATE . '" 
					  	data-public="' . block_reportwizard_report::ACCESS_PUBLIC . '" >';
		$html .= '<td>' . get_string('shareto', 'block_reportwizard') . ' </td>';
		$html .= '<td>';
		$html .= '<input type="text" name="shareto" id="hie_search_shareto" class="shareto" placeholder="Search for nodes" value="' . $value . '">';
		$html .= '<div id="jstree_shareto"></div>';
		$html .= '<p>Selected node(s): <strong id="selection_shareto">' . $value . '</strong></p>';
		$html .= '</td>';
		$html .= '</tr>';

		return $html;
	}


	public function view_report($report)
	{

		$html = '';
		$html .= '<table class="generaltable table-view-report">';

		$html .= 	'<tr>
						<td>Report name: </td>
						<td>' . $report->name . '</td>
					</tr>';

		$html .= 	'<tr>
						<td>Report type: </td>
						<td>' . $report->str_report_type() . '</td>
					</tr>';

		$html .= 	'<tr>
						<td>Category/Course: </td>
						<td>' . $report->get_report_object_name() . '</td>
					</tr>';

		$html .= 	'<tr>
						<td>Hierarchy nodes: </td>
						<td>' . $this->get_visual_hierarchy_node($report->hierarchy_nodes) . '</td>
					</tr>';

		if ($report->type != block_reportwizard_report::REPORT_TYPE_MANDATORY_ONLINE) {
			$html .= 	'<tr>
						<td>Enrolment date:</td>
						<td>' . $report->str_enrol_conition() . '</td>
					</tr>';
		}
		// $html .= 	'<tr>
		// 				<td>Enrolment date: </td>
		// 				<td>'.$report->str_enrol_period_conition().'</td>
		// 			</tr>';	

		$html .= '<tr>
						<td>Completion date:</td>
						<td>' . $report->str_complete_conition() . '</td>
					</tr>';
		// $html .= 	'<tr>
		// 				<td>Completion date: </td>
		// 				<td>'.$report->str_complete_period_conition().'</td>
		// 			</tr>';	
		if ($report->type != block_reportwizard_report::REPORT_TYPE_MANDATORY_ONLINE) {
			$html .= 	'<tr>
							<td>Completion: </td>
							<td>' . $report->str_completion_status() . '</td>
						</tr>';
		}

		$html .= 	$this->view_report_infofield_filters($report);

		$html .= 	'<tr>
						<td>Access: </td>
						<td>' . $report->str_access_type() . '</td>
					</tr>';

		if ($report) {
			if ($report->str_access_type() == 'Public') {
				$html .= 	'<tr>
								<td>Share to: </td>
								<td>' . $report->share_to . '</td>
							</tr>';
			}
		}

		$html .= '</table>';

		return $html;
	}

	public function get_visual_hierarchy_node($node_name)
	{
		global $DB;
		$return = array();

		foreach (explode(",", $node_name) as $node_nm) {
			$node = $DB->get_record('hierarchy_node', array('name' => trim($node_nm)));
			if (empty($node)) continue;
			$return[] = sprintf("%s - %s", $node->name, $node->description);
		}
		return implode(", ", $return);
	}

	public function run_report($report)
	{
		global $DB;

		$filter_columns = $DB->get_records('report_wzd_infofield_filter', array('report_id' => $report->id));

		switch ($report->type) {
			case block_reportwizard_report::REPORT_TYPE_GENERAL:
				return $this->generalreport($report, $filter_columns);
				break;
			case block_reportwizard_report::REPORT_TYPE_ACTIVITY:
				return $this->activityreport($report, $filter_columns);
				break;
			case block_reportwizard_report::REPORT_TYPE_COURSEOVERVIEW:
				return $this->courseoverviewreport($report, $filter_columns);
				break;
			case block_reportwizard_report::REPORT_TYPE_MANDATORY_ONLINE:
				return $this->mandatoryonlinereport($report, $filter_columns);
				break;
			default:
				break;
		}
	}
	public function get_report_data($report)
	{
		global $DB;
		$filter_columns = $DB->get_records('report_wzd_infofield_filter', array('report_id' => $report->id));

		switch ($report->type) {
			case block_reportwizard_report::REPORT_TYPE_GENERAL:
				return $this->generalreport_data($report, $filter_columns);
				break;
			case block_reportwizard_report::REPORT_TYPE_ACTIVITY:
				return $this->activityreport_data($report, $filter_columns);
				break;
			case block_reportwizard_report::REPORT_TYPE_COURSEOVERVIEW:
				return $this->courseoverviewreport_data($report, $filter_columns);
				break;
			case block_reportwizard_report::REPORT_TYPE_MANDATORY_ONLINE:
				return $this->mandatoryonlinereport_data($report, $filter_columns);
				break;
			default:
				break;
		}
	}

	public function generalreport($report, $filter_columns)
	{
		global $DB, $CFG;

		$reports_helper = new block_reportwizard_reports_helper();
		$results = $reports_helper->get_filtered_results($report);

		$report_extra_columns = $this->report_get_extra_columns_details($report->id);

		$html = '';
		$html .= '<table class="generaltable tablesorter table-view-report report-type-general">';
		$html .= '<thead>';
		$html .= '<tr>';
		$html .= '<th>Full name</th>';
		$html .= '<th>Course name</th>';
		$html .= '<th>Module</th>';
		$html .= '<th>Completion status</th>';
		$html .= '<th>Enrolment date</th>';
		$html .= '<th>Completion date</th>';
		$html .= $this->report_extra_columns_th($report_extra_columns);
		$html .= '</tr>';
		$html .= '</thead>';

		$html .= '<tbody>';
		$no_results = false;
		if (!empty($results)) {

			foreach ($results as $record) {

				$html .= '<tr>';
				$html .= '<td>' . $record->user_fullname . '</td>';
				$html .= '<td>' . $record->course_name . '</td>';
				$html .= '<td>' . $record->activity_name . '</td>';

				$status = '';
				if ($record->activity_name == 'scorm') {
					if ($record->scormstatus == 'completed' || $record->scormstatus == 'passed') {
						$status = get_string('completed', 'block_reportwizard');
					} else $status = get_string('incomplete', 'block_reportwizard');
				} elseif ($record->activity_completionstatus == '1' or $record->activity_completionstatus == '2') {
					$status = get_string('completed', 'block_reportwizard');
				} else $status = get_string('incomplete', 'block_reportwizard');

				$html .= '<td>' . $status . '</td>';

				$html .= '<td>';
				$html .= (!is_null($record->course_enrolmentdate)) ? date('d/m/Y', $record->course_enrolmentdate) : '';
				$html .= '</td>';
				$html .= '<td>';
				$html .= (!is_null($record->activity_completiondate) && $record->activity_completiondate != '0') ? date('d/m/Y', $record->activity_completiondate) : '';
				$html .= '</td>';
				$html .= $this->report_extra_columns_td($report_extra_columns, $record->user_id);
				$html .= '</tr>';
			}
		} else {
			$html .= 'No results were found for your selection.';
			$no_results = true;
		}
		$html .= '</tbody>';
		$html .= '</table>';

		return [$html, $no_results];
	}


	public function activityreport($report, $filter_columns)
	{
		global $DB, $CFG;

		$reports_helper = new block_reportwizard_reports_helper();
		$results = $reports_helper->get_filtered_results($report);

		$report_extra_columns = $this->report_get_extra_columns_details($report->id);

		$activityreports = array();

		foreach ($results as $key => $record) {
			$status = '';
			if ($record->activity_name == 'scorm') {
				if ($record->scormstatus == 'completed' || $record->scormstatus == 'passed') {
					$status = get_string('completed', 'block_reportwizard');
				} else $status = get_string('incomplete', 'block_reportwizard');
			} elseif ($record->activity_completionstatus == '1') {
				$status = get_string('completed', 'block_reportwizard');
			} else $status = get_string('incomplete', 'block_reportwizard');

			$activityreports[$record->user_fullname]['userid'] = $record->user_id;
			$activityreports[$record->user_fullname]['course_enrolmentdate'] = $record->course_enrolmentdate;
			$activityreports[$record->user_fullname]['activity'][$record->activity_name] = $status;
		}

		$html = '';
		$html .= '<table class="generaltable tablesorter table-view-report report-type-activity">';
		$html .= '<thead>';
		$html .= '<tr>';
		$html .= '<th>Full name</th>';
		$html .= '<th>Enrolment date</th>';
		// 30/07/2018 enhancement
		if (!empty($activityreports)) {
			foreach (current($activityreports)['activity'] as $activity_name => $status_value) {
				$html .= '<th>' . $activity_name . '</th>';
			}
		}
		$html .= $this->report_extra_columns_th($report_extra_columns);
		$html .= '</tr>';
		$html .= '</thead>';

		$html .= '<tbody>';
		$no_results = false;
		if (!empty($activityreports)) {
			foreach ($activityreports as $fullname => $record) {

				$html .= '<tr>';
				$html .= '<td>' . $fullname . '</td>';
				$html .= '<td>';
				$html .= (!is_null($record['course_enrolmentdate'])) ? date('d/m/Y', $record['course_enrolmentdate']) : '';
				$html .= '</td>';
				foreach ($record['activity'] as $activity_name => $status) {
					$html .= '<td style="border-width:1px;text-align:center">';
					if ($status == get_string('completed', 'block_reportwizard')) $html .= '<img src="images/completion-auto-y.png" alt="' . $status . '" title="' . $status . '" width="16" height="16">';
					else $html .= '<img src="images/completion-auto-n.png" alt="' . $status . '" title="' . $status . '" width="16" height="16">';
					$html .= '</td>';
				}
				$html .= $this->report_extra_columns_td($report_extra_columns, $record['userid']);
				$html .= '</tr>';
			}
		} else {
			$html .= 'No results were found for your selection.';
			$no_results = true;
		}
		$html .= '</tbody>';
		$html .= '</table>';

		return [$html, $no_results];
	}


	public function courseoverviewreport($report, $filter_columns)
	{
		global $DB, $CFG;

		$report_extra_columns = $this->report_get_extra_columns_details($report->id);

		// echo '<pre>filter_columns: '.print_r($filter_columns,true).'</pre>';		
		$reports_helper = new block_reportwizard_reports_helper();
		$results = $reports_helper->get_filtered_results($report);

		$courseoverviewreports = array();

		foreach ($results as $key => $record) {
			$courseoverviewreports[$record->user_id][$record->course_id]['user_fullname'] = $record->user_fullname;
			$courseoverviewreports[$record->user_id][$record->course_id]['course_name'] = $record->course_name;
			$courseoverviewreports[$record->user_id][$record->course_id]['course_enrolmentdate'] = $record->course_enrolmentdate;
			if ($record->activity_completionstatus == 1 || $record->activity_completionstatus == 2) {
				$courseoverviewreports[$record->user_id][$record->course_id]['activity_completiondate'][] = $record->activity_completiondate;
			} else {
				$courseoverviewreports[$record->user_id][$record->course_id]['activity_completiondate'][] = 0;
			}
		}

		$data = array();

		foreach ($courseoverviewreports as $user_id => $records) {
			foreach ($records as $course_id => $record) {
				$total = 0;
				$completed = 0;
				$course_completiondate = 0;
				foreach ($record['activity_completiondate'] as $key => $activity_completiondate) {
					$total++;
					if (isset($activity_completiondate) && ($activity_completiondate > 0)) {
						$completed++;
						if ($activity_completiondate >= $course_completiondate) $course_completiondate = $activity_completiondate;
					}
				}
				$percent = ($total > 0) ? round($completed / $total, 2) * 100 : 0;

				// CONDITION FOR COMPLETION STATUS
				if ($report->completion_status == block_reportwizard_report::COMPLETION_COMPLETED && $percent < 100) {
					unset($courseoverviewreports[$user_id][$course_id]);
					continue;
				}
				if ($report->completion_status == block_reportwizard_report::COMPLETION_INCOMPLETE && $percent  == 100) {
					unset($courseoverviewreports[$user_id][$course_id]);
					continue;
				}

				$courseoverviewreports[$user_id][$course_id]['total_activity'] = $total;
				$courseoverviewreports[$user_id][$course_id]['completed_activity'] = $completed;
				$courseoverviewreports[$user_id][$course_id]['course_completion_percent'] = $percent;
				$courseoverviewreports[$user_id][$course_id]['course_completiondate'] = ($total == $completed && $completed > 0) ? $course_completiondate : 0;

				$data[$course_id][$user_id][] = $percent;
			}
		}

		$barchart = array();
		foreach ($data as $course_id => $records) {
			$barchart[$course_id]['total_percent'] = 0;
			foreach ($records as $user_id => $array) {
				$barchart[$course_id]['total_percent'] += $array[0];
				$barchart[$course_id]['count'] = sizeof($records);
			}
		}

		$barchart_data = array();
		foreach ($barchart as $course_id => $barchart_element) {
			$element = new stdClass();
			$fullname = $DB->get_field('course', 'fullname', array('id' => $course_id));
			if (strlen($fullname) > 25) {
				$fullname = str_split($fullname, 25)[0] . "...";
			}

			$element->fullname = $fullname;
			$element->average_percent = round($barchart_element['total_percent'] / $barchart_element['count'], 2);
			$barchart_data[] = $element;
		}

		$html = '';
		$completed_percent = 0;
		$completed_percent_total = 0;
		$pie_completed = 0;
		$pie_completed_color = '#cbdde6';
		$pie_not_completed_color = '#eeeeee';

		$html .= '<table class="generaltable tablesorter table-view-report report-type-courseoverview">';
		$html .= '<thead>';
		$html .= '<tr>';
		$html .= '<th>Full name</th>';
		$html .= '<th>Course</th>';
		$html .= '<th>Enrolment date</th>';
		$html .= '<th>Completion date</th>';
		$html .= $this->report_extra_columns_th($report_extra_columns);
		$html .= '</tr>';
		$html .= '</thead>';

		$html .= '<tbody>';
		$no_results = false;
		if (!empty($courseoverviewreports)) {
			foreach ($courseoverviewreports as $user_id => $records) {
				foreach ($records as $course_id => $record) {
					$completed_percent += $record["course_completion_percent"];
					$completed_percent_total += 100;

					$html .= '<tr>';
					$html .= '<td>' . $record['user_fullname'] . '</td>';
					// $html .= '<td>'.$record['course_name'].' - '.$record['course_completion_percent'].'</td>';
					$html .= '<td style="border-width:1px;">';
					$html .= '<div class="report_progress_bar">';
					$html .= '<div class="report_progress_text" name="' . $record["course_completion_percent"] . '">';
					$html .= $record["course_name"] . ' <b>' . $record["course_completion_percent"] . '%</b>';
					$html .= '</div>';
					$html .= '<div class="report_progress_percentage" role="report_progress_bar" style="width:' . $record["course_completion_percent"] . '%">';
					$html .= '</div>';
					$html .= '</div>';
					$html .= '</td>';
					$html .= '<td>';
					$html .= (!is_null($record['course_enrolmentdate'])) ? date('d/m/Y', $record['course_enrolmentdate']) : '';
					$html .= '</td>';
					$html .= '<td>';
					$html .= (!is_null($record['course_completiondate']) && ($record['course_completiondate'] > 0)) ? date('d/m/Y', $record['course_completiondate']) : '';
					$html .= '</td>';
					$html .= $this->report_extra_columns_td($report_extra_columns, $user_id);
					$html .= '</tr>';
				}
			}
		} else {
			$html .= 'No results were found for your selection.';
			$no_results = true;
		}
		$html .= '</tbody>';
		$html .= '</table>';

		if ($completed_percent_total) {
			$pie_completed = round(($completed_percent / $completed_percent_total) * 100, 2);
		} else {
			$pie_completed = 0;
		}

		$canvas = '';
		$canvas .= '<div id="canvas-holder">';
		$canvas .= 	'<div id="diagramleft">';
		$canvas .=		'<div class="abc">Overall Progress</div>';
		$canvas .=		'<canvas id="overallcompletion" width="300" height="300" data-completed="' . $pie_completed . '"></canvas>';
		$canvas .=	'</div>';
		$canvas .=	'<div id="diagramright">';
		$canvas .=		'<div class="abc">Course Progress</div>';
		$canvas .=		'<canvas id="coursecompletion" width="350" height="350"></canvas>';
		$canvas .=	'</div>';
		$canvas .=	'<div class="clearfix"></div>';
		$canvas .= '</div>';

		$script = '';
		$script .= '<script>';
		$script .= 'var pie_data = [' . $pie_completed . ', ' . (100 - $pie_completed) . '];';
		$script .= 'var pie_color = ["' . $pie_completed_color . '", "' . $pie_not_completed_color . '"];';
		$script .= 'var barchart_json = ' . json_encode($barchart_data);
		$script .= '</script>';



		return [$canvas . $html . $script, $no_results];
	}

	public function mandatoryonlinereport($report, $filter_columns)
	{
		global $DB, $CFG;

		$report_extra_columns = $this->report_get_extra_columns_details($report->id);

		// echo '<pre>filter_columns: '.print_r($filter_columns,true).'</pre>';		
		$reports_helper = new block_reportwizard_reports_helper();
		$results = $reports_helper->get_filtered_results($report);

		// echo '<pre>filter_columns: '.print_r($results,true).'</pre>'; die();

		$courseoverviewreports = array();

		foreach ($results as $key => $record) {
			$courseoverviewreports[$record->user_id]['user_fullname'] = $record->user_fullname;
			$courseoverviewreports[$record->user_id]['email'] = $record->email;
			$courseoverviewreports[$record->user_id][$record->course_id]['course_name'] = $record->course_name;
			$courseoverviewreports[$record->user_id][$record->course_id]['course_enrolmentdate'] = $record->course_enrolmentdate;
			if ($record->activity_completionstatus == 1 || $record->activity_completionstatus == 2) {
				$courseoverviewreports[$record->user_id][$record->course_id]['activity_completiondate'][] = $record->activity_completiondate;
			} else {
				$courseoverviewreports[$record->user_id][$record->course_id]['activity_completiondate'][] = 0;
			}
		}

		// echo '<pre>filter_columns: '.print_r($courseoverviewreports,true).'</pre>'; die();
		$data = array();
		$ignore = false;
		foreach ($courseoverviewreports as $user_id => $records) {
			foreach ($records as $course_id => $record) {
				if ($ignore) continue;
				if (!isset($record['activity_completiondate'])) continue;
				$total = 0;
				$completed = 0;
				$course_completiondate = 0;
				foreach ($record['activity_completiondate'] as $key => $activity_completiondate) {
					$total++;
					if (isset($activity_completiondate) && ($activity_completiondate > 0)) {
						$completed++;
						if ($activity_completiondate >= $course_completiondate) $course_completiondate = $activity_completiondate;
					}
				}
				$percent = ($total > 0) ? round($completed / $total, 2) * 100 : 0;

				// CONDITION FOR COMPLETION STATUS
				if ($total != $completed) {
					$ignore = true;
					unset($courseoverviewreports[$user_id]);
					continue;
				}
				$courseoverviewreports[$user_id][$course_id]['total_activity'] = $total;
				$courseoverviewreports[$user_id][$course_id]['completed_activity'] = $completed;
				$courseoverviewreports[$user_id][$course_id]['course_completiondate'] = $course_completiondate;
			}
			if (isset($courseoverviewreports[$user_id]) && $course_completiondate != 0) {
				$courseoverviewreports[$user_id]['completiondate'] = $course_completiondate;
			}
			$ignore = false;
		}

		// echo '<pre>filter_columns: '.print_r($courseoverviewreports,true).'</pre>'; die();
		$schedule = $this->report_get_schedule_details($report);
		if (!empty($schedule) && $schedule->conds_from_date != 0) {
			$header_completion_date = 'Completion date from ' . date('d/m/Y', $schedule->conds_from_date);
		} else {
			$header_completion_date = 'Completion date';
		}
		// var_dump($schedule); die();
		$html = '';

		$html .= '<table class="generaltable tablesorter table-view-report report-type-courseoverview">';
		$html .= '<thead>';
		$html .= '<tr>';
		$html .= '<th>Full name</th>';
		$html .= '<th>Email</th>';
		$html .= '<th>' . $header_completion_date . '</th>';
		$html .= $this->report_extra_columns_th($report_extra_columns);
		$html .= '</tr>';
		$html .= '</thead>';

		$html .= '<tbody>';
		$no_results = false;
		if (!empty($courseoverviewreports)) {
			foreach ($courseoverviewreports as $user_id => $records) {
				$completed_date = intval($records['completiondate']);
				if (intval($schedule->conds_from_date) >= $completed_date) continue;
				if (!empty($report->complete_date_from) && intval($report->complete_date_from) > $completed_date) continue;
				if (!empty($report->complete_date_to) && intval($report->complete_date_to) < $completed_date) continue;

				// echo "<pre>".print_r($records,true)."</pre>";
				$html .= '<tr>';
				$html .= '<td>' . $records['user_fullname'] . '</td>';
				$html .= '<td>' . $records['email'] . '</td>';
				$html .= '<td>' . date('d/m/Y', $records['completiondate']) . '</td>';
				$html .= $this->report_extra_columns_td($report_extra_columns, $user_id);
				$html .= '</tr>';
			}
		} else {
			$html .= 'No results were found for your selection.';
			$no_results = true;
		}
		$html .= '</tbody>';
		$html .= '</table>';

		return [$html, $no_results];
	}

	public function generalreport_data($report, $filter_columns)
	{
		global $DB, $CFG;

		$reports_helper = new block_reportwizard_reports_helper();
		$results = $reports_helper->get_filtered_results($report);

		$report_extra_columns = $this->report_get_extra_columns_details($report->id);

		$return = array();

		$default_header = ['Full name', 'Course name', 'Module', 'Completion status', 'Enrolment date', ' Completion date'];

		$columns_headers = $this->report_extra_columns_header($report_extra_columns);
		if (!empty($columns_headers)) {
			$return[] = array_merge($default_header, $columns_headers);
		} else {
			$return[] = $default_header;
		}

		$no_results = false;
		if (!empty($results)) {

			foreach ($results as $record) {
				$line = array();

				// $html .= '<tr>';
				// $html .= '<td>' . $record->user_fullname . '</td>';
				// $html .= '<td>' . $record->course_name . '</td>';
				// $html .= '<td>' . $record->activity_name . '</td>';

				$status = '';
				if ($record->activity_name == 'scorm') {
					if ($record->scormstatus == 'completed' || $record->scormstatus == 'passed') {
						$status = get_string('completed', 'block_reportwizard');
					} else $status = get_string('incomplete', 'block_reportwizard');
				} elseif ($record->activity_completionstatus == '1' or $record->activity_completionstatus == '2') {
					$status = get_string('completed', 'block_reportwizard');
				} else $status = get_string('incomplete', 'block_reportwizard');

				// $html .= '<td>' . $status . '</td>';

				// $html .= '<td>';
				// $html .= (!is_null($record->course_enrolmentdate)) ? date('d/m/Y', $record->course_enrolmentdate) : '';
				// $html .= '</td>';
				// $html .= '<td>';
				// $html .= (!is_null($record->activity_completiondate) && $record->activity_completiondate != '0') ? date('d/m/Y', $record->activity_completiondate) : '';
				// $html .= '</td>';
				// $html .= $this->report_extra_columns_td($report_extra_columns, $record->user_id);
				// $html .= '</tr>';

				$enrolment_date = (!is_null($record->course_enrolmentdate)) ? date('d/m/Y', $record->course_enrolmentdate) : '';
				$completion_date = (!is_null($record->activity_completiondate) && $record->activity_completiondate != '0') ? date('d/m/Y', $record->activity_completiondate) : '';

				$line = [$record->user_fullname, $record->course_name, $record->activity_name, $status, $enrolment_date, $completion_date];
				$columns_data = $this->report_extra_columns_data($report_extra_columns, $record->user_id);
				if (!empty($columns_data)) {
					$return[] = array_merge($line, $columns_data);
				} else {
					$return[] = $line;
				}
			}
		}
		return $return;
	}


	public function activityreport_data($report, $filter_columns)
	{
		global $DB, $CFG;

		$reports_helper = new block_reportwizard_reports_helper();
		$results = $reports_helper->get_filtered_results($report);

		$report_extra_columns = $this->report_get_extra_columns_details($report->id);

		$activityreports = array();

		foreach ($results as $key => $record) {
			$status = '';
			if ($record->activity_name == 'scorm') {
				if ($record->scormstatus == 'completed' || $record->scormstatus == 'passed') {
					$status = get_string('completed', 'block_reportwizard');
				} else $status = get_string('incomplete', 'block_reportwizard');
			} elseif ($record->activity_completionstatus == '1') {
				$status = get_string('completed', 'block_reportwizard');
			} else $status = get_string('incomplete', 'block_reportwizard');

			$activityreports[$record->user_fullname]['userid'] = $record->user_id;
			$activityreports[$record->user_fullname]['course_enrolmentdate'] = $record->course_enrolmentdate;
			$activityreports[$record->user_fullname]['activity'][$record->activity_name] = $status;
		}

		$return = array();

		$default_header = ['Full name', 'Enrolment date'];
		if (!empty($activityreports)) {
			foreach (current($activityreports)['activity'] as $activity_name => $status_value) {
				$default_header[] = $activity_name;
			}
		}
		$columns_headers = $this->report_extra_columns_header($report_extra_columns);
		if (!empty($columns_headers)) {
			$return[] = array_merge($default_header, $columns_headers);
		} else {
			$return[] = $default_header;
		}
		$no_results = false;
		if (!empty($activityreports)) {
			foreach ($activityreports as $fullname => $record) {
				$line = array();
				// $html .= '<tr>';
				// $html .= '<td>' . $fullname . '</td>';
				// $html .= '<td>';
				// $html .= (!is_null($record['course_enrolmentdate'])) ? date('d/m/Y', $record['course_enrolmentdate']) : '';
				// $html .= '</td>';
				// foreach ($record['activity'] as $activity_name => $status) {
				// 	$html .= '<td style="border-width:1px;text-align:center">';
				// 	if ($status == 'Completed') $html .= '<img src="images/completion-auto-y.png" alt="" width="16" height="16">';
				// 	else $html .= '<img src="images/completion-auto-n.png" alt="" width="16" height="16">';
				// 	$html .= '</td>';
				// }
				// $html .= $this->report_extra_columns_td($report_extra_columns, $record['userid']);
				// $html .= '</tr>';
				$enrolment_date = (!is_null($record['course_enrolmentdate'])) ? date('d/m/Y', $record['course_enrolmentdate']) : '';
				$line = [$fullname, $enrolment_date];
				foreach ($record['activity'] as $activity_name => $status) {
					if ($status == 'Completed') $line[] = get_string('completed', 'block_reportwizard');
					else $line[] = get_string('incomplete', 'block_reportwizard');
				}
				$columns_data = $this->report_extra_columns_data($report_extra_columns, $record['userid']);
				if (!empty($columns_data)) {
					$return[] = array_merge($line, $columns_data);
				} else {
					$return[] = $line;
				}
			}
		}
		return $return;
	}


	public function courseoverviewreport_data($report, $filter_columns)
	{
		global $DB, $CFG;

		$return = array();

		$report_extra_columns = $this->report_get_extra_columns_details($report->id);

		// echo '<pre>filter_columns: '.print_r($filter_columns,true).'</pre>';		
		$reports_helper = new block_reportwizard_reports_helper();
		$results = $reports_helper->get_filtered_results($report);

		$courseoverviewreports = array();

		foreach ($results as $key => $record) {
			$courseoverviewreports[$record->user_id][$record->course_id]['user_fullname'] = $record->user_fullname;
			$courseoverviewreports[$record->user_id][$record->course_id]['course_name'] = $record->course_name;
			$courseoverviewreports[$record->user_id][$record->course_id]['course_enrolmentdate'] = $record->course_enrolmentdate;
			$courseoverviewreports[$record->user_id][$record->course_id]['activity_completiondate'][] = $record->activity_completiondate;
		}

		$data = array();

		foreach ($courseoverviewreports as $user_id => $records) {
			foreach ($records as $course_id => $record) {
				$total = 0;
				$completed = 0;
				$course_completiondate = 0;
				foreach ($record['activity_completiondate'] as $key => $activity_completiondate) {
					$total++;
					if (isset($activity_completiondate) && ($activity_completiondate > 0)) {
						$completed++;
						if ($activity_completiondate >= $course_completiondate) $course_completiondate = $activity_completiondate;
					}
				}
				$percent = ($total > 0) ? round($completed / $total, 2) * 100 : 0;

				// CONDITION FOR COMPLETION STATUS
				if ($report->completion_status == block_reportwizard_report::COMPLETION_COMPLETED && $percent < 100) {
					unset($courseoverviewreports[$user_id][$course_id]);
					continue;
				}
				if ($report->completion_status == block_reportwizard_report::COMPLETION_INCOMPLETE && $percent  == 100) {
					unset($courseoverviewreports[$user_id][$course_id]);
					continue;
				}

				$courseoverviewreports[$user_id][$course_id]['total_activity'] = $total;
				$courseoverviewreports[$user_id][$course_id]['completed_activity'] = $completed;
				$courseoverviewreports[$user_id][$course_id]['course_completion_percent'] = $percent;
				$courseoverviewreports[$user_id][$course_id]['course_completiondate'] = ($total == $completed && $completed > 0) ? $course_completiondate : 0;

				$data[$course_id][$user_id][] = $percent;
			}
		}

		$barchart = array();
		foreach ($data as $course_id => $records) {
			$barchart[$course_id]['total_percent'] = 0;
			foreach ($records as $user_id => $array) {
				$barchart[$course_id]['total_percent'] += $array[0];
				$barchart[$course_id]['count'] = sizeof($records);
			}
		}

		$barchart_data = array();
		foreach ($barchart as $course_id => $barchart_element) {
			$element = new stdClass();
			$fullname = $DB->get_field('course', 'fullname', array('id' => $course_id));
			if (strlen($fullname) > 25) {
				$fullname = str_split($fullname, 25)[0] . "...";
			}

			$element->fullname = $fullname;
			$element->average_percent = round($barchart_element['total_percent'] / $barchart_element['count'], 2);
			$barchart_data[] = $element;
		}

		$html = '';
		$completed_percent = 0;
		$completed_percent_total = 0;
		$pie_completed = 0;
		$pie_completed_color = '#cbdde6';
		$pie_not_completed_color = '#eeeeee';

		$return = array();
		$default_header = [
			'Full name', 'Course', 'Percentage', 'Enrolment date', 'Completion date'
		];

		$columns_headers = $this->report_extra_columns_header($report_extra_columns);
		if (!empty($columns_headers)) {
			$return[] = array_merge($default_header, $columns_headers);
		} else {
			$return[] = $default_header;
		}

		$no_results = false;
		if (!empty($courseoverviewreports)) {
			foreach ($courseoverviewreports as $user_id => $records) {
				foreach ($records as $course_id => $record) {
					$line = array();
					$completed_percent += $record["course_completion_percent"];
					$completed_percent_total += 100;

					// $html .= '<tr>';
					// $html .= '<td>' . $record['user_fullname'] . '</td>';
					// // $html .= '<td>'.$record['course_name'].' - '.$record['course_completion_percent'].'</td>';
					// $html .= '<td style="border-width:1px;">';
					// $html .= '<div class="report_progress_bar">';
					// $html .= '<div class="report_progress_text" name="' . $record["course_completion_percent"] . '">';
					// $html .= $record["course_name"] . ' <b>' . $record["course_completion_percent"] . '%</b>';
					// $html .= '</div>';
					// $html .= '<div class="report_progress_percentage" role="report_progress_bar" style="width:' . $record["course_completion_percent"] . '%">';
					// $html .= '</div>';
					// $html .= '</div>';
					// $html .= '</td>';
					// $html .= '<td>';

					// $html .= (!is_null($record['course_enrolmentdate'])) ? date('d/m/Y', $record['course_enrolmentdate']) : '';
					// $html .= '</td>';
					// $html .= '<td>';
					// $html .= (!is_null($record['course_completiondate']) && ($record['course_completiondate'] > 0)) ? date('d/m/Y', $record['course_enrolmentdate']) : '';
					// $html .= '</td>';
					// $html .= $this->report_extra_columns_td($report_extra_columns, $user_id);
					// $html .= '</tr>';
					$enrolment_date = (!is_null($record['course_enrolmentdate'])) ? date('d/m/Y', $record['course_enrolmentdate']) : '';
					$completion_date = (!is_null($record['course_completiondate']) && ($record['course_completiondate'] > 0)) ? date('d/m/Y', $record['course_completiondate']) : '';

					$line = [$record['user_fullname'], $record["course_name"], $record["course_completion_percent"], $enrolment_date, $completion_date];
					$columns_data = $this->report_extra_columns_data($report_extra_columns, $user_id);
					if (!empty($columns_data)) {
						$return[] = array_merge($line, $columns_data);
					} else {
						$return[] = $line;
					}
				}
			}
		}
		return $return;
	}

	public function mandatoryonlinereport_data($report, $filter_columns)
	{
		global $DB, $CFG;

		$return = array();

		$report_extra_columns = $this->report_get_extra_columns_details($report->id);
		$schedule = $this->report_get_schedule_details($report);

		// echo '<pre>filter_columns: '.print_r($filter_columns,true).'</pre>';		
		$reports_helper = new block_reportwizard_reports_helper();
		$results = $reports_helper->get_filtered_results($report);

		// echo '<pre>filter_columns: '.print_r($results,true).'</pre>'; die();
		$courseoverviewreports = array();

		foreach ($results as $key => $record) {
			$courseoverviewreports[$record->user_id]['user_fullname'] = $record->user_fullname;
			$courseoverviewreports[$record->user_id]['email'] = $record->email;
			// $courseoverviewreports[$record->user_id][$record->course_id]['course_name']=$record->course_name;
			// $courseoverviewreports[$record->user_id][$record->course_id]['course_enrolmentdate']=$record->course_enrolmentdate;
			if ($record->activity_completionstatus == 1 || $record->activity_completionstatus == 2) {
				$courseoverviewreports[$record->user_id][$record->course_id]['activity_completiondate'][] = $record->activity_completiondate;
			} else {
				$courseoverviewreports[$record->user_id][$record->course_id]['activity_completiondate'][] = 0;
			}
		}

		$data = array();
		$ignore = false;

		foreach ($courseoverviewreports as $user_id => $records) {
			$course_completiondate = 0;
			foreach ($records as $course_id => $record) {
				if ($ignore) continue;
				if (!isset($record['activity_completiondate'])) continue;
				$total = 0;
				$completed = 0;
				foreach ($record['activity_completiondate'] as $key => $activity_completiondate) {
					$total++;
					if (isset($activity_completiondate) && ($activity_completiondate > 0)) {
						$completed++;
						if ($activity_completiondate >= $course_completiondate) $course_completiondate = $activity_completiondate;
					}
				}
				$percent = ($total > 0) ? round($completed / $total, 2) * 100 : 0;

				// CONDITION FOR COMPLETION STATUS
				if ($percent < 100 && $total != $completed) {
					$ignore = true;
					unset($courseoverviewreports[$user_id]);
					continue;
				}

				$courseoverviewreports[$user_id][$course_id]['total_activity'] = $total;
				$courseoverviewreports[$user_id][$course_id]['completed_activity'] = $completed;
				$courseoverviewreports[$user_id][$course_id]['course_completiondate'] = $course_completiondate;
			}
			if (isset($courseoverviewreports[$user_id]) && $course_completiondate != 0) {
				$courseoverviewreports[$user_id]['completiondate'] = $course_completiondate;
			}
			$ignore = false;
		}

		$html = '';

		$return = array();
		$default_header = [
			'Full name', 'Email'
		];
		if (!empty($schedule) && $schedule->conds_from_date != 0) {
			$default_header[] = 'Completion date from ' . date('d/m/Y', $schedule->conds_from_date);
		} else {
			$default_header[] = 'Completion date';
		}

		$columns_headers = $this->report_extra_columns_header($report_extra_columns);
		if (!empty($columns_headers)) {
			$return[] = array_merge($default_header, $columns_headers);
		} else {
			$return[] = $default_header;
		}

		$no_results = false;
		if (!empty($courseoverviewreports)) {
			foreach ($courseoverviewreports as $user_id => $records) {
				if (intval($schedule->conds_from_date) >= intval($records['completiondate'])) continue;
				$line = [$records['user_fullname'], $records['email'], date('d/m/Y', $records['completiondate'])];
				$columns_data = $this->report_extra_columns_data($report_extra_columns, $user_id);
				if (!empty($columns_data)) {
					$return[] = array_merge($line, $columns_data);
				} else {
					$return[] = $line;
				}
			}
		}
		return $return;
	}
	public function report_get_schedule_details($report)
	{
		global $DB;
		if ($report->type == block_reportwizard_report::REPORT_TYPE_MANDATORY_ONLINE) {
			$schedule = $DB->get_record('report_wzd_schedule', array('report_id' => $report->id), 'frequency,startdate,lastrun,nextrun');
			if (!empty($schedule)) {
				$days_diff = (time() - abs(intval($schedule->startdate))) / 86400;
				$schedule->conds_from_date = ($schedule->lastrun == 0 || $schedule->lastrun == NULL) ? 0 : $schedule->lastrun;
				return $schedule;
			}
		}
	}
	public function report_get_extra_columns_details($reportid)
	{
		global $DB;
		return $DB->get_records_sql('SELECT rpf.*,uif.datatype from mdl_report_wzd_infofield_filter as rpf inner join mdl_user_info_field uif on rpf.infofield_id=uif.id where rpf.report_id=? and rpf.infofield_display=?', array($reportid, block_reportwizard_reports_helper::COLUMN_SHOW));
	}

	public function report_extra_columns_th($colums)
	{
		global $DB;

		$html = '';

		if ($colums) {
			foreach ($colums as $key => $column) {
				$column_name = $DB->get_field('user_info_field', 'name', array('id' => $column->infofield_id));
				if ($column_name) {
					$html .= '<th>' . $column_name . '</th>';
				} else {
					$html .= '<th></th>';
				}
			}
		}

		return $html;
	}

	public function report_extra_columns_td($colums, $userid)
	{
		global $DB;

		$html = '';

		if ($colums) {
			foreach ($colums as $key => $column) {
				$column_value = $DB->get_field('user_info_data', 'data', array('fieldid' => $column->infofield_id, 'userid' => $userid));
				if ($column_value && $column_value != "") {
					if ($column->datatype == 'datetime') $column_value = date('d/m/Y', $column_value);
					else if ($column->datatype == 'checkbox')
						$column_value = ($column_value == 1) ? "Yes" : "No";

					$html .= '<td>' . $column_value . '</td>';
				} else {
					$html .= '<td></td>';
				}
			}
		}

		return $html;
	}

	public function report_extra_columns_header($colums)
	{
		global $DB;

		$return = array();

		if ($colums) {
			foreach ($colums as $key => $column) {
				$column_name = $DB->get_field('user_info_field', 'name', array('id' => $column->infofield_id));
				if ($column_name) {
					$return[] = $column_name;
				} else {
					$return[] = '';
				}
			}
		}

		return $return;
	}

	public function report_extra_columns_data($colums, $userid)
	{
		global $DB;

		$return = array();

		if ($colums) {
			foreach ($colums as $key => $column) {
				$column_value = $DB->get_field('user_info_data', 'data', array('fieldid' => $column->infofield_id, 'userid' => $userid));
				$column_datatype = $DB->get_field('user_info_field', 'datatype', array('id' => $column->infofield_id));
				if ($column_value) {
					if ($column_datatype == 'datetime') $column_value = date('d/m/Y', $column_value);
					else if ($column_datatype == 'checkbox')
						$column_value = ($column_value == 1) ? "Yes" : "No";

					$return[] = $column_value;
				} else {
					$return[] = '';
				}
			}
		}

		return $return;
	}


	public function view_report_infofield_filters($report)
	{
		global $DB;

		$html = '';


		$infofield_filters = $DB->get_records('report_wzd_infofield_filter', array('report_id' => $report->id, 'infofield_enabled' => block_reportwizard_reports_helper::FILTER_ENABLED));

		foreach ($infofield_filters as $key => $filter) {
			$infofield = $DB->get_record('user_info_field', array('id' => $filter->infofield_id));

			if (isset($filter->infofield_data) && $filter->infofield_data != '') {
				$html .= '<tr>';
				$html .= '<td>' . $infofield->name . '</td>';
				$html .= '<td>';

				switch ($infofield->datatype) {
					case 'checkbox':
						if ($filter->infofield_data == '1') {
							$html .= '<input type="checkbox" disabled checked>';
						} elseif ($filter->infofield_data == '0') {
							$html .= '<input type="checkbox" disabled>';
						}
						break;

						// @30/07/2018 enhancement
					case 'datetime':
						$datetime_values = json_decode($filter->infofield_data, true);
						if (!empty($datetime_values['from'])) {
							$html .= 'From - ' . $datetime_values['from'];
						}
						if (!empty($datetime_values['to'])) {
							$html .= ' To - ' . $datetime_values['to'];
						}
						break;

					default:
						$values = json_decode($filter->infofield_data, true);
						$html .= implode(', ', $values);
						break;
				}

				$html .= '</td>';
				$html .= '</tr>';
			}
		}

		return $html;
	}


	public function my_public_reports($reports)
	{
		global $USER;
		$num = 1;

		$html = '';
		$html .= '<table class="generaltable table-reports table-public-reports ">';
		$html .= '<caption>Public reports</caption>';
		$html .= '<tr>
					<th class="number">No.</th>
					<th>Name</th>
					<th class="col-view">View</th>
					<th class="col-edit">Edit</th>
				</tr>';

		foreach ($reports as $report) {
			$html .= '<tr>
						<td class="number">' . $num . '</td>
						<td><a href="run_report.php?id=' . $report->id . '">' . $report->name . '</a></td>
						<td class="icons col-view">		
							<a href="view_report.php?id=' . $report->id . '"><img alt="View details" title="View details" src="' . $this->image_url('t/viewdetails') . '"></a>
						</td>';
			// Only the owner can fully manage his report template
			$html .= 	'<td class="icons col-edit">';
			// allow another site admin to edit
			if ($report->creator_id == $USER->id || is_siteadmin($USER->id)) {
				$html .=	'<a href="edit_report.php?id=' . $report->id . '"><img alt="Edit" title="Edit" src="' . $this->image_url('t/edit') . '"></a>
							<a href="manage_report_columns.php?id=' . $report->id . '"><img alt="Manage report columns" title="Manage report columns" src="' . $this->image_url('i/grades') . '"></a>
							<a href="delete_report.php?id=' . $report->id . '"><img alt="Delete" title="Delete" src="' . $this->image_url('t/delete') . '"></a>';
			}
			$html .= 	'</td>';
			$html .= '</tr>';
			$num++;
		}

		$html .= '</table>';

		return $html;
	}


	public function my_private_reports($reports)
	{
		$num = 1;

		$html = '';
		$html .= '<table class="generaltable table-reports table-private-reports">';
		$html .= '<caption>Private reports</caption>';
		$html .= '<tr>
					<th class="number">No.</th>
					<th>Name</th>
					<th>Schedule</th>
					<th class="col-view">View</th>
					<th class="col-edit">Edit</th>
				</tr>';

		foreach ($reports as $report) {
			$schedule = $this->get_reportwizard_schedule_frequency($report->id);

			$html .= '<tr>
						<td class="number">' . $num . '</td>
						<td><a href="run_report.php?id=' . $report->id . '">' . $report->name . '</a></td>
						<td><a href="schedule.php?reportid=' . $report->id . '"><img alt="Edit" title="Edit" src="' . $this->image_url('t/edit') . '"></a>' . $schedule . '</td>
						<td class="icons col-view">
								<a href="view_report.php?id=' . $report->id . '"><img alt="View details" title="View details" src="' . $this->image_url('t/viewdetails') . '"></a>				
						</td>
						<td class="icons col-edit">
								<a href="edit_report.php?id=' . $report->id . '"><img alt="Edit" title="Edit" src="' . $this->image_url('t/edit') . '"></a>
								<a href="manage_report_columns.php?id=' . $report->id . '"><img alt="Manage report columns" title="Manage report columns" src="' . $this->image_url('i/grades') . '"></a>
								<a href="delete_report.php?id=' . $report->id . '"><img alt="Delete" title="Delete" src="' . $this->image_url('t/delete') . '"></a>							
						</td>
					  </tr>';
			$num++;
		}

		$html .= '</table>';

		return $html;
	}
	public function get_reportwizard_schedule_frequency($reportid)
	{
		global $DB;
		$data = "";
		$record = $DB->get_record('report_wzd_schedule', array('report_id' => $reportid));
		if (!empty($record)) {
			if ($record->cohortid == 0 || $record->frequency == "None") return $data;
			// return "(".$record->frequency.")";
			$lastrun = ($record->lastrun == 0 || $record->lastrun == NULL) ? "None" : date('d/m/Y', $record->lastrun);
			$nextrun = date('d/m/Y', $record->nextrun);
			$title = "Last run: " . $lastrun . "\n";
			$title .= "Next run: " . $nextrun;
			return " <div class='schedule_last_run' title='" . $title . "'>(" . $record->frequency . ") </div>";
		}
		return $data;
	}

	// Deprecated: Manage filters page is included into edit and create report page
	public function form_manage_filters($report)
	{
		global $DB;

		$fields = $DB->get_records('user_info_field');

		$html = '';
		$html .= '<form method="POST" action="" id="form_manage_filters" class="form_manage_filters">';

		$html .= '<table class="generaltable table-manage-filters table-two-cols">';
		$html .= '<caption>' . $report->name . '</caption>';

		foreach ($fields as $field) {

			$checked = ' ';

			if ($DB->get_field('report_wzd_infofield_filter', 'infofield_enabled', array('report_id' => $report->id, 'infofield_id' => $field->id)) == '1') {
				$checked = 'checked';
			}

			$html .= '<tr>';
			$html .= 	'<td class="col1">' . $field->name . '</td>';
			$html .= 	'<td class="col2">';
			$html .=		'<input type="hidden" name="' . $field->id . '" value="0" data-shortname="' . $field->shortname . '" ' . $checked . ' >';
			$html .=		'<input type="checkbox" name="' . $field->id . '" value="1" data-shortname="' . $field->shortname . '" ' . $checked . ' >';
			$html .= 	'</td>';
			$html .= '</tr>';
		}

		$html .= '</table>';

		// form btns
		$html .= '<div  class="float-right">';
		$html .= 	'<input style="" type="submit" name="submit" class="btn btn-primary" value="Save"/>';
		$html .= 	'<a class="btn btn-primary" href="' . $this->src_path() . '/view_report.php?id=' . $report->id . '">Cancel</a>';
		$html .= '</div>';

		$html .= '</form>';

		return $html;
	}


	public function form_manage_columns($report)
	{
		global $DB;

		$fields = $DB->get_records('user_info_field');

		$html = '';
		$html .= '<form method="POST" action="" id="form_manage_columns" class="form_manage_columns">';

		$html .= '<table class="generaltable table-manage-columns table-two-cols">';
		$html .= '<caption>' . $report->name . '</caption>';

		// basic columns


		// info field columns
		foreach ($fields as $field) {

			$checked = ' ';
			if ($DB->get_field('report_wzd_infofield_filter', 'infofield_display', array('report_id' => $report->id, 'infofield_id' => $field->id)) == '1') {
				$checked = 'checked';
			}

			$html .= '<tr>';
			$html .= 	'<td class="col1">' . $field->name . '</td>';
			$html .= 	'<td class="col2">';
			$html .=		'<input type="hidden" name="infofield_' . $field->id . '" value="0" data-shortname="' . $field->shortname . '" >';
			$html .=		'<input type="checkbox" name="infofield_' . $field->id . '" value="1" data-shortname="' . $field->shortname . '" ' . $checked . ' >';
			$html .= 	'</td>';
			$html .= '</tr>';
		}



		$html .= '</table>';

		// form btns
		$html .= '<div class="float-left">';
		$html .= 	'<input style="" type="submit" name="submit" class="btn btn-primary" value="Save"/>';
		$html .= 	'<a class="btn btn-primary" href="' . $this->src_path() . '/view_report.php?id=' . $report->id . '">Cancel</a>';
		$html .= '</div>';

		$html .= '</form>';

		return $html;
	}

	function block_content($reports)
	{
		global $CFG;

		$html = '';

		$html .= '<table class="table-reports block-table-reports">';
		// $html .= '<tr><th class="number">No.</th><th>Name</th><th class="operations">Operations</th></tr>';

		$num = 1;
		foreach ($reports as $report) {


			$html .= '<tr>
						<td class="number">' . $num . '</td>
						<td><a target="_blank" href="' . $this->src_path() . '/run_report.php?id=' . $report->id . '">' . $report->name . '</a></td>
						<td class="icons operations">
							<span>				
								<a href="' . $this->src_path() . '/view_report.php?id=' . $report->id . '"><img alt="View details" title="View details" src="' . $this->image_url('t/viewdetails') . '"></a>
							</span>';
			$html .=	'</td>
					  </tr>';
			$num++;
		}

		$html .= '</table>';
		// $html .= '<hr>';

		$html .= '<a href="' . $CFG->wwwroot . '/blocks/reportwizard/src/myreports.php" >View my reports</a>';


		return $html;
	}
}


class block_reportwizard_renderer extends block_reportwizard_renderer_base
{
}
