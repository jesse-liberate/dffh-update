<?php
require_once("$CFG->libdir/formslib.php");
require_once('lib.php');

class schedule_form extends moodleform {
    const INTPUT_DATETIME_FORMAT = 'd/m/Y';

    static $frequency_list = [
        'None',
        'Daily',
        'Weekly',
        'Fortnightly',
        'Monthly',
        'Quarterly',
        'Yearly',
    ];

    public function definition() {
        global $USER,$PAGE,$DB;
        $mform = $this->_form;
        $customdata = $this->_customdata;
        $reportid = $customdata['reportid'];

        $PAGE->requires->css('/blocks/reportwizard/src/css/jquery.datetimepicker.min.css');

        // $reportformat = array('CSV','PDF');
        // $mform->addElement('select', 'reportformat', get_string('reportformat', 'block_reportwizard'), $reportformat);
        // $mform->setDefault('reportformat', 'PDF');

        $frequency = static::$frequency_list;
        $frequency_element = $mform->addElement('select', 'frequency', get_string('frequency', 'block_reportwizard'), $frequency);

        $startdate_element = $mform->addElement('text', 'startdate', get_string('startdate','block_reportwizard'));
        $mform->setType('startdate', PARAM_TEXT);
        $mform->addRule('startdate', get_string('startdate:required','block_reportwizard'), 'required');

        $cohort = get_list_available_cohort();
        $mform->addElement('select', 'cohortid', get_string('cohort', 'block_reportwizard'), $cohort);

        $mform->addElement('hidden', 'reportid', $reportid);
        $mform->setType('reportid', PARAM_RAW );

        $mform->addElement('hidden', 'reportformat', 'CSV');
        $mform->setType('reportformat', PARAM_TEXT );

        $record = $DB->get_record('report_wzd_schedule',array('report_id'=>$reportid));
        if(!empty($record)){
            $mform->setDefault('cohortid',$record->cohortid);
            $mform->setDefault('startdate',($record->startdate==0) ? "": $this->convert_timestamp_to_datetime($record->startdate));
            $mform->setDefault('frequency', array_search($record->frequency, $frequency));
            $mform->hardFreeze('frequency');
            $mform->setConstant('frequency', array_search($record->frequency, $frequency));
            $mform->hardFreeze('startdate');
            $mform->setConstant('startdate', ($record->startdate==0) ? "": $this->convert_timestamp_to_datetime($record->startdate));
        }

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'savechanges', get_string('savechanges'));
        $buttonarray[] = &$mform->createElement('cancel');

        $mform->addGroup($buttonarray, 'buttonarray', '', array(' '), false);

    }
    public function validation($data, $files) {
        $errors = array();

        if (!empty($data['startdate'])){
            $data['startdate'] = $this->convert_datetime_to_timestamp($data['startdate']);
        }

        $now = new DateTime();
        $now->setTime(23, 59, 59);
        $now = $now->getTimestamp();

        if ($data['startdate'] < $now) {
            $errors['startdate'] = 'Start date must be after today';
        }

        return $errors;
    }
    public function get_data() {
        $frequency = static::$frequency_list;
        // $data = parent::get_data();
        if(isset($_POST) && !empty($_POST)){
            $data = new stdClass();
            foreach ($_POST as $key => $value) {
                $data->$key = $value;
            }
            if (isset($data->frequency)) {
                $data->frequency = $frequency[$data->frequency];
            }
            if (!empty($data->startdate) && $data->startdate!=0 && $data->startdate!="") {
                $data->startdate = $this->convert_datetime_to_timestamp($data->startdate);
            }
            return $data;
        }else return false;
    }

    public function set_data($defaults) {
        $mform = $this->_form;

        $defaults = clone $defaults;
        if (!empty($defaults->frequency)) {
            $defaults->frequency = array_search($defaults->frequency, static::$frequency_list);
        }
        if($defaults->startdate!=0 && trim($defaults->startdate)!=""){
            $defaults->startdate = $this->convert_timestamp_to_datetime($defaults->startdate);
        }else $defaults->startdate="";
        return parent::set_data($defaults);
    }
    public function get_defaults() {
        return $this->_form->_defaultValues;
    }

    protected function convert_datetime_to_timestamp($datetime) {
        return DateTime::createFromFormat(self::INTPUT_DATETIME_FORMAT, $datetime)->getTimestamp();
    }

    protected function convert_timestamp_to_datetime($timestamp) {
        return date(self::INTPUT_DATETIME_FORMAT, $timestamp);
    }
}