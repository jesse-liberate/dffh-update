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
 * Copyright (C) 2007-2011 Catalyst IT (http://www.catalyst.net.nz)
 * Copyright (C) 2011-2013 Totara LMS (http://www.totaralms.com)
 * Copyright (C) 2014 onwards Catalyst IT (http://www.catalyst-eu.net)
 *
 * @package    mod
 * @subpackage facetoface
 * @copyright  2014 onwards Catalyst IT <http://www.catalyst-eu.net>
 * @author     Stacey Walker <stacey@catalyst-eu.net>
 * @author     Alastair Munro <alastair.munro@totaralms.com>
 * @author     Aaron Barnes <aaron.barnes@totaralms.com>
 * @author     Francois Marier <francois@catalyst.net.nz>
 */

// MA-MODIFIED 

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

class mod_facetoface_signup_team_form extends moodleform {

    public function definition() {
        global $USER, $DB;       

        $session = facetoface_get_session($this->_customdata['s']);
        $facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface));

        $mform =& $this->_form;
        // var_dump($this->_customdata);
        $showdiscountcode = $this->_customdata['showdiscountcode'];

        $mform->addElement('hidden', 's', $this->_customdata['s']);
        $mform->setType('s', PARAM_INT);

        $mform->addElement('hidden', 'backtoallsessions', $this->_customdata['backtoallsessions']);
        $mform->setType('backtoallsessions', PARAM_INT);

        $teammembers = face_to_face_get_all_child_users_from_userid($USER->id);
        // echo '<pre>'.print_r($teammembers,true).'</pre>';

        $mform->addElement('header', 'description', 'Select team members to enrol: ');

        if (!empty($teammembers)) {
            foreach ($teammembers as $key => $userid) {
                $fullname = $DB->get_field('user','firstname',array('id'=>$userid)).' '.$DB->get_field('user','lastname',array('id'=>$userid));
                if (facetoface_get_user_submissions($facetoface->id, $userid)) {
                    $mform->addElement('static', 'alreadysignedup', $fullname,'Already signed up');
                } else {
                    $mform->addElement('advcheckbox', 'teammembers['.$userid.']', $fullname, null, null, array(0, 1));
                }
            }
        }

        if ($showdiscountcode) {
            $mform->addElement('text', 'discountcode', get_string('discountcode', 'facetoface'), 'size="6"');
            $mform->addRule('discountcode', null, 'required', null, 'client');
            $mform->setType('discountcode', PARAM_TEXT);
        } else {
            $mform->addElement('hidden', 'discountcode', '');
            $mform->setType('discountcode', PARAM_TEXT);
        }

        $this->add_action_buttons(true, get_string('signup', 'facetoface'));
    }
    
}
