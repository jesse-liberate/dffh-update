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
 * Menu profile field definition.
 *
 * @package    profilefield_menuwithfreetext
 * @copyright  2007 onwards Shane Elliot {@link http://pukunui.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class profile_define_menuwithfreetext
 *
 * @copyright  2007 onwards Shane Elliot {@link http://pukunui.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_define_menuwithfreetext extends profile_define_base {

    /**
     * Adds elements to the form for creating/editing this type of profile field.
     * @param moodleform $form
     */
    public function define_form_specific($form) {
        // Param 1 for menu type contains the options.
        $form->addElement('textarea', 'param1', get_string('profilemenuoptions', 'admin'), array('rows' => 6, 'cols' => 40));
        $form->setType('param1', PARAM_TEXT);

        // Default data.
        $form->addElement('text', 'defaultdata', get_string('profiledefaultdata', 'admin'), 'size="50"');
        $form->setType('defaultdata', PARAM_TEXT);

        // Placer holder text
        $form->addElement('text', 'param2', get_string('placeholdertext', 'profilefield_menuwithfreetext'));
        $form->setType('param2', PARAM_TEXT);
    }

    /**
     * Validates data for the profile field.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function define_validate_specific($data, $files) {
        $err = array();

        $data->param1 = str_replace("\r", '', $data->param1);

        // Check that we have at least 2 options.
        if (($options = explode("\n", $data->param1)) === false) {
            $err['param1'] = get_string('profilemenunooptions', 'admin');
        } else if (count($options) < 2) {
            $err['param1'] = get_string('profilemenutoofewoptions', 'admin');
        } else if (!empty($data->defaultdata) and !in_array($data->defaultdata, $options)) {
            // Check the default data exists in the options.
            $err['defaultdata'] = get_string('profilemenudefaultnotinoptions', 'admin');
        }
        return $err;
    }

    /**
     * Processes data before it is saved.
     * @param array|stdClass $data
     * @return array|stdClass
     */
    public function define_save_preprocess($data) {
        global $DB;
        $field = $DB->get_record("user_info_field", ['id' => $data->id]);
        // using param5 to indicate whether to sort param1 or not, if param5 is sort, sort param1
        if ($field->param5 == 'sort') {
            $array = array_map('trim', explode("\r", $data->param1));
            sort($array);
            $data->param1 = implode(PHP_EOL, $array);
        } 

        $data->param1 = str_replace("\r", '', $data->param1);

        return $data;
    }

}


