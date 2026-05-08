<?php
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot.'/admin/tool/setdeadline/lib.php');
class setdeadline_manager_form extends moodleform {
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $courses = setdeadline_get_manager_course();
        $courseid = "";
        if (!empty($this->_customdata['courseid'])) {
            $courseid = $this->_customdata['courseid'];
        }

        $mform->addElement('select', 'courseid', get_string('coursename', 'tool_setdeadline'), $courses, $courseid);

        $mform->addElement('float', 'firstreminder', get_string('firstreminder', 'tool_setdeadline'));
        $mform->addElement('float', 'secondreminder', get_string('secondreminder', 'tool_setdeadline'));
        $mform->addElement('checkbox', 'repeated', get_string('reminder:repeat', 'tool_setdeadline'));

        $this->add_action_buttons();
    }
}