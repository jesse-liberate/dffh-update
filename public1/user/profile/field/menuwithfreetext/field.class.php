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
 * Menu profile field.
 *
 * @package    profilefield_menuwithfreetext
 * @copyright  2007 onwards Shane Elliot {@link http://pukunui.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class profile_field_menuwithfreetext
 *
 * @copyright  2007 onwards Shane Elliot {@link http://pukunui.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_field_menuwithfreetext extends profile_field_base {
    private static $is_first = true;

    /** @var array $options */
    public $options;

    /** @var int $datakey */
    public $datakey;

    public $freetext;

    /**
     * Constructor method.
     *
     * Pulls out the options for the menu from the database and sets the the corresponding key for the data if it exists.
     *
     * @param int $fieldid
     * @param int $userid
     * @param object $fielddata
     */
    public function __construct($fieldid = 0, $userid = 0, $fielddata = null) {
        // First call parent constructor.
        parent::__construct($fieldid, $userid, $fielddata);

        // Param 1 for menu type is the options.
        if (isset($this->field->param1)) {
            $options = explode("\n", $this->field->param1);
        } else {
            $options = array();
        }
        $this->options = array();
        if (!empty($this->field->required)) {
            $placeholdertext = !empty($this->field->param2) ? $this->field->param2 : get_string('choose').'...';

            $this->options[''] = $placeholdertext;
        } else {
            $this->options[''] = '';
        }
        foreach ($options as $key => $option) {
            // Multilang formatting with filters.
            $this->options[$option] = format_string($option, true, ['context' => context_system::instance()]);
        }

        $this->options['other'] = 'Other';

        // Set the data key.
        if ($this->data !== "") {
            $key = $this->data;
            if (isset($this->options[$key]) || ($key = array_search($key, $this->options)) !== false) {
                $this->data = $key;
                $this->datakey = $key;
            } else {
                $this->freetext = $this->data;
                $this->datakey = 'other';
            }
        } else {
            $this->datakey = "";
        }
    }

    /**
     * Create the code snippet for this field instance
     * Overwrites the base class method
     * @param MoodleQuickForm $mform Moodle form instance
     */
    public function edit_field_add($mform) {
        global $PAGE;

        if (static::$is_first) {
            static::$is_first = false;
            $PAGE->requires->css('/user/profile/field/menuwithfreetext/style/chosen.min.css');
        }
        $PAGE->requires->js_call_amd('profilefield_menuwithfreetext/form', 'initField', [
            '#id_' . $this->inputname,
        ]);

        if (!empty($this->field->description)) {
            $mform->addElement('static', "{$this->inputname}desc", '', strip_tags($this->field->description));
        }
        $mform->addElement('select', $this->inputname, format_string($this->field->name), $this->options, [
            'data-placeholder' => $this->field->param2,
        ]);

        $mform->addElement('text', "{$this->inputname}text", '');
        $mform->setType("{$this->inputname}text", PARAM_TEXT);
        foreach ($this->options as $value => $ignored) {
            if ($value === 'other') {
                continue;
            }
            $mform->hideIf("{$this->inputname}text", $this->inputname, 'eq', $value);
        }
        if (!empty($this->datakey) && $this->datakey === 'other') {
            $mform->setDefault("{$this->inputname}text", $this->freetext);
        }


    }

    /**
     * Set the default value for this field instance
     * Overwrites the base class method.
     * @param moodleform $mform Moodle form instance
     */
    public function edit_field_set_default($mform) {
        $key = $this->field->defaultdata;
        if (isset($this->options[$key]) || ($key = array_search($key, $this->options)) !== false){
            $defaultkey = $key;
        } else {
            $defaultkey = '';
        }
        $mform->setDefault($this->inputname, $defaultkey);
    }

    /**
     * Sets the required flag for the field in the form object
     *
     * @param MoodleQuickForm $mform instance of the moodleform class
     */
    public function edit_field_set_required($mform) {
        global $USER;
        parent::edit_field_set_required($mform);
        $data = data_submitted();
        $value = !empty($data->{$this->inputname}) ? $data->{$this->inputname} : '';
        if ($this->is_required() && ($this->userid == $USER->id || isguestuser()) && ($data === false || $value === 'other')) {
            $mform->addRule("{$this->inputname}text", get_string('required'), 'required');
        }
    }

    /**
     * The data from the form returns the key.
     *
     * This should be converted to the respective option string to be saved in database
     * Overwrites base class accessor method.
     *
     * @param mixed $data The key returned from the select input in the form
     * @param stdClass $datarecord The object that will be used to save the record
     * @return mixed Data or null
     */
    public function edit_save_data_preprocess($data, $datarecord) {
        if ($data === 'other') {
            $submitted_data = data_submitted();
            $data = $submitted_data->{$this->inputname . 'text'};
            return $data;
        }
        return isset($this->options[$data]) ? $data : null;
    }

    /**
     * When passing the user object to the form class for the edit profile page
     * we should load the key for the saved data
     *
     * Overwrites the base class method.
     *
     * @param stdClass $user User object.
     */
    public function edit_load_user_data($user) {
        $user->{$this->inputname} = $this->datakey;
    }

    /**
     * HardFreeze the field if locked.
     * @param moodleform $mform instance of the moodleform class
     */
    public function edit_field_set_locked($mform) {
        if (!$mform->elementExists($this->inputname)) {
            return;
        }
        if ($this->is_locked() and !has_capability('moodle/user:update', context_system::instance())) {
            $mform->hardFreeze($this->inputname);
            $mform->setConstant($this->inputname, format_string($this->datakey));
        }
    }
    /**
     * Convert external data (csv file) from value to key for processing later by edit_save_data_preprocess
     *
     * @param string $value one of the values in menu options.
     * @return int options key for the menu
     */
    public function convert_external_data($value) {
        if (isset($this->options[$value])) {
            $retval = $value;
        } else {
            $retval = array_search($value, $this->options);
        }

        // If value is not found in options then return null, so that it can be handled
        // later by edit_save_data_preprocess.
        if ($retval === false) {
            $retval = null;
        }
        return $retval;
    }

    /**
     * Return the field type and null properties.
     * This will be used for validating the data submitted by a user.
     *
     * @return array the param type and null property
     * @since Moodle 3.2
     */
    public function get_field_properties() {
        return array(PARAM_TEXT, NULL_NOT_ALLOWED);
    }
}


