<?php

class profile_field_multiselect extends profile_field_base {
    private static $is_first = true;

    var $options;
    var $datakey;

    /**
     * Constructor method.
     * Pulls out the options for the menu from the database and sets the
     * the corresponding key for the data if it exists
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
        foreach ($options as $key => $option) {
            // Multilang formatting with filters.
            $this->options[$option] = format_string($option, true, ['context' => context_system::instance()]);
        }

        // Set the data key.
        if ($this->data !== null) {
            $datatmp = str_replace("\r", '', $this->data);
            $keys = explode("\n", $datatmp);
            $data = [];
            foreach ($keys as $key) {
                if (isset($this->options[$key]) || ($key = array_search($key, $this->options)) !== false) {
                    $data[] = $key;
                }
            }
            $this->data = implode("\n", $data);
            $this->datakey = $data;
        }
        // print_object($this);die();
    }

    /**
     * Create the code snippet for this field instance
     * Overwrites the base class method
     * @param   object   moodleform instance
     */
    public function edit_field_add($mform) {
        global $PAGE;

        if (static::$is_first) {
            static::$is_first = false;
            $PAGE->requires->css('/user/profile/field/multiselect/style/chosen.min.css');
        }
        $PAGE->requires->js_call_amd('profilefield_multiselect/form', 'initField', [
            '#id_' . $this->inputname,
        ]);
        $mform->addElement('select', $this->inputname, format_string($this->field->name), $this->options, [
            'data-placeholder' => $this->field->param2,
        ]);
		$mform->getElement($this->inputname)->setMultiple(true);
    }

    /**
     * Set the default value for this field instance
     * Overwrites the base class method
     */
    public function edit_field_set_default($mform) {
        if (in_array($this->field->defaultdata, $this->options)){
            $defaultkey = $this->field->defaultdata;
        } else {
            $defaultkey = '';
        }
        if (!empty($defaultkey)) {
            $mform->setDefault($this->inputname, $defaultkey);
        }
    }

    /**
     * The data from the form returns the key. This should be converted to the
     * respective option string to be saved in database
     * Overwrites base class accessor method
     * @param   mixed    $data - the key returned from the select input in the form
     * @param   stdClass $datarecord The object that will be used to save the record
     */
    public function edit_save_data_preprocess($data, $datarecord) {
        //print "<pre>";print_r($data);die;
		if (is_array($data)) {
            $selected_options = [];
            foreach($data as $key) {
                if (isset($this->options[$key])) {
                    $selected_options[] = $this->options[$key];
                }
            }
            return implode("\r\n", $selected_options);
		}
        return isset($this->options[$data]) ? $this->options[$data] : NULL;
    }

    /**
     * When passing the user object to the form class for the edit profile page
     * we should load the key for the saved data
     * Overwrites the base class method
     * @param   object   user object
     */
    public function edit_load_user_data($user) {
        $user->{$this->inputname} = $this->datakey;
    }

    /**
     * HardFreeze the field if locked.
     * @param   object   instance of the moodleform class
     */
    public function edit_field_set_locked($mform) {
        if (!$mform->elementExists($this->inputname)) {
            return;
        }
        if ($this->is_locked() and !has_capability('moodle/user:update', context_system::instance())) {
            $mform->hardFreeze($this->inputname);
            $mform->setConstant($this->inputname, $this->datakey);
        }
    }
    /**
     * Convert external data (csv file) from value to key for processing later
     * by edit_save_data_preprocess
     *
     * @param string $value one of the values in menu options.
     * @return int options key for the menu
     */
    public function convert_external_data($value) {
        $retval = array_search($value, $this->options);

        // If value is not found in options then return null, so that it can be handled
        // later by edit_save_data_preprocess
        if ($retval === false) {
            $retval = null;
        }
        return $retval;
    }

    /**
     * Validate the form field from profile page
     *
     * @param stdClass $usernew
     * @return  string  contains error message otherwise null
     */
    public function edit_validate_field($usernew) {
        $errors = array();
        // Get input value.
        if (isset($usernew->{$this->inputname})) {
            if (is_array($usernew->{$this->inputname}) && isset($usernew->{$this->inputname}['text'])) {
                $value = $usernew->{$this->inputname}['text'];
            } else {
                $value = $usernew->{$this->inputname};
            }
        } else {
            $value = '';
        }

        // if ($this->is_required() && empty($value)) {
        //     $errors[$this->inputname] = get_string('required');
        // }

        $errors += parent::edit_validate_field($usernew);

        return $errors;
    }
}


