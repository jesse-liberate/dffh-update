<?php

require_once(dirname(__FILE__). '/../../config.php');
require_once($CFG->dirroot.'/blocks/reporting/report/lib.php');
class block_reporting extends block_base {
    public function init() {
	  $this->title = "Reporting";
    }

	public function get_content() {
	 	global $CFG, $USER;

	 	if ($this->content !== null) {
			return $this->content;
	 	}
	 	// Check if the user not in the hierarchy, not vendor and not admin. They can not allow to see report
		$is_user_hierarchy = is_user_in_hierarchy($USER->id);
		$vendor = is_vendor($USER->id);
		$content = '';
		$context = context_system::instance();
		if(($is_user_hierarchy)||has_capability('block/reporting:viewreports', $context)){
		 	$DBH = new PDO("mysql:host=$CFG->dbhost;dbname=$CFG->dbname", $CFG->dbuser, $CFG->dbpass);
			$content .= '<ul>';
			$content .= '<li><a href="' . $CFG->wwwroot . '/blocks/reporting/report/general.php">' . get_string('general_reports', 'block_reporting') . '</a></li>';
			$content .= '<li><a href="' . $CFG->wwwroot . '/blocks/reporting/report/individual.php">' . get_string('individual_reports', 'block_reporting') . '</a></li>';
			$content .= '<li><a href="' . $CFG->wwwroot . '/blocks/reporting/report/activity.php">' . get_string('activity_reports', 'block_reporting') . '</a></li>';
			$content .= '<li><a href="' . $CFG->wwwroot . '/blocks/reporting/report/courseoverview.php">' . get_string('course_overview_reports', 'block_reporting') . '</a></li>';
			if(has_capability('block/reporting:viewreports', $context)){
				$content .= '<li><a href="' . $CFG->wwwroot . '/blocks/reporting/report/users.php">' . get_string('users_reports', 'block_reporting') . '</a></li>';
			}
			
			if(has_capability('block/reporting:changereportsfilter', $context)){
				$content .= '<li><a href="' . $CFG->wwwroot . '/blocks/reporting/report/filter.php">' . get_string('reporting_filter', 'block_reporting') . '</a></li>';
				$content .= '<li><a href="' . $CFG->wwwroot . '/blocks/reporting/report/settingcolor.php">' . get_string('reporting_setting', 'block_reporting') . '</a></li>';
			}
			/*---------Only Site Admin and Manager can view Report filter----------*/
			$context_system = context_system::instance();
			$roles = get_user_roles($context_system, $USER->id, false);

			$content .= '</ul>';
		}
		$this->content = new stdClass;
		$this->content->text = $content;
		return $this->content;
	}
}
?>
