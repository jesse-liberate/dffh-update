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
 * @package    block_mindatlas_coachmanagement
 * @copyright  Daniel Neis <danielneis@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_mindatlas_coachmanagement extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_mindatlas_coachmanagement');
    }

    function get_content() {
        global $CFG, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $this->content->text = "<a href=\"/#\">Mindatlas Block Base Installed1</a> <br>
                                <a href=\"/#\">Mindatlas Block Base Installed2</a> <br>
                                <a href=\"/#\">Mindatlas Block Base Installed3</a> <br>
                                <a href=\"/#\">Mindatlas Block Base Installed4</a> ";

        return $this->content;
    }


    function has_config() {return true;}

}
