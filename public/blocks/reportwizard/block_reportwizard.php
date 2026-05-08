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
 * Newblock block caps.
 *
 * @package    block_reportwizard
 * @copyright  Daniel Neis <danielneis@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once('src/hierarchy-lib.php');
require_once('src/classes/reports_helper.php');

class block_reportwizard extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_reportwizard');
    }

    function get_content() {
        global $CFG, $OUTPUT, $USER, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $html = '';

        

        if (is_siteadmin($USER) || is_manager_node(get_user_nodeid($USER->id)) ) {

            $renderer = $PAGE->get_renderer('block_reportwizard');
            $reports_helper = new block_reportwizard_reports_helper();
            $reports = $reports_helper->all_user_reports($USER->id);

            $html .= $renderer->block_content($reports);

            $this->content = new stdClass();
            $this->content->items = array();
            $this->content->icons = array();
            $this->content->footer = '<link rel="stylesheet" href="'.$CFG->wwwroot.'/blocks/reportwizard/src/css/block.css">';

            $this->content->text = $html;

        }else $this->content = '';


        return $this->content;
    }

    // public function instance_allow_multiple() {
    //       return true;
    // }

    // function has_config() {return true;}

}
