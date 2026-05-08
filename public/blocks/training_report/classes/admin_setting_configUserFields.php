<?php
/**
 * admin_setting_configtUserFields.php
 * Created by Fan Li on 29/01/2021.
 */

namespace block_training_report;


use admin_setting;

class admin_setting_configUserFields extends admin_setting
{
    /** @var array Array of choices value=>label */
    public $choices;
    private $fields;

    static $option_fileds = ['menu', ' multiselect', 'menuwithfreetext'];

    /**
     * Constructor: uses parent::__construct
     *
     * @param string $name unique ascii name, either 'mysetting' for settings that in config, or 'myplugin/mysetting' for ones in config_plugins.
     * @param string $visiblename localised
     * @param string $description long localised info
     * @param array $defaultsetting array of selected
     * @param array $choices array of $value=>$label for each checkbox
     */
    public function __construct($name, $visiblename, $description, $defaultsetting, $choices, $fields) {
        $this->choices = $choices;
        $this->fields = $fields;
        parent::__construct($name, $visiblename, $description, $defaultsetting);
    }

    /**
     * This public function may be used in ancestors for lazy loading of choices
     *
     * @todo Check if this function is still required content commented out only returns true
     * @return bool true if loaded, false if error
     */
    public function load_choices() {
        /*
        if (is_array($this->choices)) {
            return true;
        }
        .... load choices here
        */
        return true;
    }

    /**
     * Is setting related to query text - used when searching
     *
     * @param string $query
     * @return bool true on related, false on not or failure
     */
    public function is_related($query) {
        if (!$this->load_choices() or empty($this->choices)) {
            return false;
        }
        if (parent::is_related($query)) {
            return true;
        }

        foreach ($this->choices as $desc) {
            if (strpos(\core_text::strtolower($desc), $query) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the current setting if it is set
     *
     * @return mixed null if null, else an array
     */
    public function get_setting() {
        $result = $this->config_read($this->name);

        if (is_null($result)) {
            return NULL;
        }
        if ($result === '') {
            return array();
        }
        $result = json_decode($result, true);
        $enabled = array_keys($result);
        $setting = array();
        foreach ($enabled as $option) {
            $setting[$option] = 1;
        }
        return $setting;
    }

    /**
     * Saves the setting(s) provided in $data
     *
     * @param array $data An array of data, if not array returns empty str
     * @return mixed empty string on useless data or bool true=success, false=failed
     */
    public function write_setting($data) {
        global $DB;
        if (!is_array($data)) {
            return ''; // ignore it
        }
        if (!$this->load_choices() or empty($this->choices) or empty($this->fields)) {
            return '';
        }
        unset($data['xxxxx']);
        $result = [];
        foreach ($data as $key => $value) {
            if ($value and array_key_exists($key, $this->choices)) {
                $field = [];
                $field['type'] = $this->fields[$key]->datatype;
                $field['name'] = $this->fields[$key]->name;

                /** Now for dropdown fieds, always query from user_info_data table to get the latest options */

                // if (in_array($field['type'], admin_setting_configUserFields::$option_fileds) && $this->fields[$key]->id) {
                //     // menuwithfreetext filter options may not up to date if the user add other field, so query user_info_data table again when preparing filter data in report.php
                //     if ($field['type'] == 'menuwithfreetext') {
                //         $options_records = $DB->get_records('user_info_data', ['fieldid' => $this->fields[$key]->id], 'data', 'DISTINCT(data)');
                //         if ($options_records) {
                //             $field['options'] = str_replace("\r", '', implode(PHP_EOL, array_keys($options_records)));
                //         } else {
                //             $field['options'] = '';
                //         }
                //     } else {
                //         $field['options'] = $this->fields[$key]->param1;
                //     }
                // }
                $result[$key] = $field;
            }
        }
        $result = json_encode($result);
        return $this->config_write($this->name, $result) ? '' : get_string('errorsetting', 'admin');
    }

    /**
     * Returns XHTML field(s) as required by choices
     *
     * Relies on data being an array should data ever be another valid vartype with
     * acceptable value this may cause a warning/error
     * if (!is_array($data)) would fix the problem
     *
     * @todo Add vartype handling to ensure $data is an array
     *
     * @param array $data An array of checked values
     * @param string $query
     * @return string XHTML field
     */
    public function output_html($data, $query='') {
        global $OUTPUT;

        if (!$this->load_choices() or empty($this->choices)) {
            return '';
        }

        $default = $this->get_defaultsetting();
        if (is_null($default)) {
            $default = array();
        }
        if (is_null($data)) {
            $data = array();
        }

        $context = (object) [
            'id' => $this->get_id(),
            'name' => $this->get_full_name(),
        ];

        $options = array();
        $defaults = array();
        foreach ($this->choices as $key => $description) {
            if (!empty($default[$key])) {
                $defaults[] = $description;
            }

            $options[] = [
                'key' => $key,
                'checked' => !empty($data[$key]),
                'label' => highlightfast($query, $description)
            ];
        }

        if (is_null($default)) {
            $defaultinfo = null;
        } else if (!empty($defaults)) {
            $defaultinfo = implode(', ', $defaults);
        } else {
            $defaultinfo = get_string('none');
        }

        $context->options = $options;
        $context->hasoptions = !empty($options);

        $element = $OUTPUT->render_from_template('core_admin/setting_configmulticheckbox', $context);

        return format_admin_setting($this, $this->visiblename, $element, $this->description, false, '', $defaultinfo, $query);

    }
}