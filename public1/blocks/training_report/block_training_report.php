<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Main entry class for Training Report block.
 *
 * @package    block_training_report
 * @copyright  2021 MindAtlas <support@mindatlas.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Training Report block class.
 *
 * @package    block_training_report
 * @copyright  2021 MindAtlas <support@mindatlas.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_training_report extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_training_report');
    }

    /**
     * Returns the contents.
     *
     * @return stdClass contents of block
     */
    public function get_content() {
      global $DB, $USER,$CFG;

      if ($this->content !== null) {
        return $this->content;
      }
    //   $cfg_enable_tags = get_enabletags_configuration();
      $content = '';
      // Get the search link for this :
      $content.="<ul>";
        $is_manager = block_training_report_helper::is_user_manager($USER->id);

        require_once(__DIR__ . '/api/helper.php');

        if (is_siteadmin($USER->id) || is_report_administrator()) {
            // $content .= '<li><a href="' . $CFG->wwwroot . '/blocks/training_report/pages/reports/general.php">' . get_string('general_report', 'block_training_report') . '</a></li>';
            $content .= '<li><a href="' . $CFG->wwwroot . '/blocks/training_report/pages/reports/activity.php">' . get_string('activity_report', 'block_training_report') . '</a></li>';
            $content .= '<li><a href="' . $CFG->wwwroot . '/blocks/training_report/pages/reports/courseoverview.php">' . get_string('courseoverview_report', 'block_training_report') . '</a></li>';
            $content .= '<li><a href="' . $CFG->wwwroot . '/blocks/training_report/pages/reports/user.php">' . get_string('user_report', 'block_training_report') . '</a></li>';
            $content .= '<li><a href="' . $CFG->wwwroot . '/blocks/training_report/pages/reports/individual.php">' . get_string('individual_report', 'block_training_report') . '</a></li>';
            $content .= '<li><a href="' . $CFG->wwwroot . '/mod/facetoface/interest_report.php">' . get_string('eoi_report', 'block_training_report') . '</a></li>';
            // $content .= '<li class="report-coaching" ><a href="' . $CFG->wwwroot . '/blocks/training_report/pages/reports/coaching.php">Coaching report</a></li>';
        }else{
            if($is_manager || is_organisational_admin()){
                $content .= '<li><a href="' . $CFG->wwwroot . '/blocks/training_report/pages/reports/activity.php">' . get_string('activity_report', 'block_training_report') . '</a></li>';
                $content .= '<li><a href="' . $CFG->wwwroot . '/blocks/training_report/pages/reports/courseoverview.php">' . get_string('courseoverview_report', 'block_training_report') . '</a></li>';
                $content .= '<li><a href="' . $CFG->wwwroot . '/blocks/training_report/pages/reports/user.php">' . get_string('user_report', 'block_training_report') . '</a></li>';
            }
            $content .= '<li><a href="' . $CFG->wwwroot . '/blocks/training_report/pages/reports/individual.php">' . get_string('individual_report', 'block_training_report') . '</a></li>';
            // if($this->view_users_report_allowed()){
            //     $content .= '<li><a href="' . $CFG->wwwroot . '/blocks/training_report/pages/reports/user.php">' . get_string('user_report', 'block_training_report') . '</a></li>';
            // }
        }

        if (has_all_capabilities([
            'moodle/site:configview',
            'block/training_report:changereportsfilter',
        ], context_system::instance())) {
            $content .= '<li><a href="' . $CFG->wwwroot . '/admin/settings.php?section=blocksettingtraining_report">' . get_string('report_settings', 'block_training_report') . '</a></li>';
        }
      $content.="</ul>";
      $this->content = new stdClass;
      $this->content->text = $content;
      return $this->content;
    }

    /**
     * Subclasses should override this and return true if the
     * subclass block has a settings.php file.
     *
     * @return boolean
     */
    function has_config() {
        return true;
    }

    function view_report_allowed($context = null) {
        if (!isset($context)) {
            $context = context_system::instance();
        }
        return has_capability('block/training_report:viewreports', $context);
    }

    function view_users_report_allowed($context = null) {
        if (!isset($context)) {
            $context = context_system::instance();
        }
        return has_capability('block/training_report:view_users_report', $context);
    }
}
