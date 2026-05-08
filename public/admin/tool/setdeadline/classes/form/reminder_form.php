<?php
namespace tool_setdeadline\form;

use tool_setdeadline\model\reminder;


require_once("$CFG->libdir/formslib.php");

class reminder_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;
        $customdata = $this->_customdata;

        $deadline_types = $customdata['deadline_types'];
        $all_courses = $customdata['all_courses'];
        $all_users = $customdata['all_users'];
        $all_cohorts = $customdata['all_cohorts'];
        $all_admins = $customdata['all_admins'];

        $mform->addElement(
            'select',
            'deadline_type',
            get_string('deadline_type', 'tool_setdeadline'),
            $deadline_types
        );

        $courses_select = $mform->addElement('select', 'course', get_string('course'), $all_courses, array('class'=>'chosen-select','data-placeholder'=>'Select learning bundle'));

        // $users_select = $mform->addElement('select', 'user', get_string('user'), $all_users);
        // $users_select->setMultiple(true);

        $cohorts_select = $mform->addElement('select', 'cohort', get_string('cohort', 'core_cohort'), $all_cohorts, array('class'=>'chosen-select','data-placeholder'=>'Select cohort'));
        $cohorts_select->setMultiple(true);

        $period_group = [];
        $period_group[] = $mform->createElement(
            'text',
            'pone',
            get_string('period_one', 'tool_setdeadline')
        );
        $period_group[] = $mform->createElement(
            'static',
            'pone_desc',
            '',
            get_string('days', 'tool_setdeadline')
        );

        $mform->addGroup(
            $period_group,
            'pone_group',
            get_string('period_one', 'tool_setdeadline'),
            null,
            false
        );

        $mform->setType('pone', PARAM_INT);
        $mform->setType('pone_group', PARAM_INT);

        $period_group = [];
        $period_group[] = $mform->createElement(
            'text',
            'ptwo',
            get_string('period_two', 'tool_setdeadline')
        );
        $period_group[] = $mform->createElement(
            'static',
            'ptwo_desc',
            '',
            get_string('days', 'tool_setdeadline') .
                ' ' . get_string('after_period_one', 'tool_setdeadline')
        );

        $mform->addGroup(
            $period_group,
            'ptwo_group',
            get_string('period_two', 'tool_setdeadline'),
            null,
            false
        );

        $mform->setType('ptwo', PARAM_INT);
        $mform->addGroupRule(
            'pone_group',
            [
                [
                    [
                        get_string('pone_required', 'tool_setdeadline'), 'required', null, 'client'
                    ],
                    [
                        get_string('pone_required', 'tool_setdeadline'), 'required', null, 'client'
                    ]
                ]
            ],
            'required'
        );
        $mform->addGroupRule(
            'ptwo_group',
            [
                [
                    [
                        get_string('ptwo_required', 'tool_setdeadline'), 'required', null, 'client'
                    ],
                    [
                        get_string('ptwo_required', 'tool_setdeadline'), 'required', null, 'client'
                    ]
                ]
            ],
            'required'
        );

        $mform->addElement(
            'checkbox',
            'repeated',
            get_string('repeat_period_two_indefinitely', 'tool_setdeadline')
        );

        $mform->addElement(
            'header',
            'also_send_to',
            get_string('also_send_to', 'tool_setdeadline')
        );

        $manager_checkbox = $mform->addElement(
            'checkbox',
            'manager',
            get_string('manager', 'tool_setdeadline'),
            null
        );
        $manager_checkbox->setChecked(true);

        $mform->addElement(
            'checkbox',
            'siteadmin',
            get_string('site_admin', 'tool_setdeadline')
        );
        //Disable this due to BlueCorss has it owns site admin
        // $site_admins_select = $mform->addElement(
        //     'select',
        //     'emailtoadmin',
        //     '',
        //     $all_admins
        // );
        // reset($all_admins);
        // $site_admins_select->setSelected(key($all_admins));
        // $site_admins_select->setMultiple(true);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons();
    }

    public function set_data($default_values) {
        $reminder = new reminder($default_values);
        $default_values->course = $reminder->courseid;
        // $reminder_assigns = $reminder->reminder_assigns;
        // var_dump($reminder);
        // die();
        $type = $reminder->type;
        $values = [];
        if($type=='cohort'){
            $arr_vals = explode(',', $reminder->instanceid);
            foreach ($arr_vals as $val) {
                $values[$type][] = $val;
            }
        }
        // foreach ($reminder_assigns as $reminder_assign) {
        //     $type = $reminder_assign->type;
        //     $values[$type][] = $reminder_assign->instanceid;
        //     $default_values->course = $reminder_assign->courseid;
        // }
        foreach ($values as $type => $type_values) {
            if (count($type_values) == 1) {
                $default_values->$type = reset($type_values);
            } else {
                $default_values->$type = $type_values;
            }
        }

        $default_values->deadline_type = $type;
        $default_values->pone = $default_values->firstreminder / 86400;
        $default_values->ptwo = $default_values->secondreminder / 86400;
        $default_values->emailtoadmin = explode(',', $default_values->emailadminlist);
        
        parent::set_data($default_values);
    }
}
