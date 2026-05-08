<?php

namespace auth_ma_email\form;

use login_signup_form as base_login_signup_form;
use core_user;

class login_signup_form extends base_login_signup_form {
    public $user_field = null;

    function definition() {
        global $USER, $CFG;

        $mform = $this->_form;

        $mform->addElement('header', 'createuserandpass', get_string('createuserandpass'), '');

        $mform->addElement('text', 'email', get_string('email'), 'maxlength="100" size="25"');
        $mform->setType('email', core_user::get_property_type('email'));
        $mform->addRule('email', get_string('missingemail'), 'required', null, 'client');
        $mform->addRule('email', get_string('casesensitiveerror'), 'regex', '/^[^A-Z]+$/', 'client');
        $mform->setForceLtr('email');

        $mform->addElement('text', 'email2', get_string('emailagain'), 'maxlength="100" size="25" autocomplete="off"');
        $mform->setType('email2', core_user::get_property_type('email'));
        $mform->addRule('email2', get_string('missingemail'), 'required', null, 'client');
        $mform->addRule('email2', get_string('casesensitiveerror'), 'regex', '/^[^A-Z]+$/', 'client');
        $mform->setForceLtr('email2');

        $this->user_field = $mform->addElement('hidden', 'username', '');
        $mform->setType('username', PARAM_RAW);

        if (!empty($CFG->passwordpolicy)){
            $mform->addElement('static', 'passwordpolicyinfo', '', print_password_policy());
        }
        $mform->addElement('password', 'password', get_string('password'), 'maxlength="32" size="12"');
        $mform->setType('password', core_user::get_property_type('password'));
        $mform->addRule('password', get_string('missingpassword'), 'required', null, 'client');
        $mform->addRule(array('password', 'confirmPassword'), get_string('passwords_must_match'), 'compare', 'eq', 'client');
        $mform->addElement('password', 'confirmPassword', get_string('confirmpassword'), 'maxlength="32" size="12"');
        $mform->setType('password', core_user::get_property_type('password'));
        //        $mform->addRule('confirmPassword', get_string('passwordsdiffer'), 'required', null, 'client');
        $mform->addRule('confirmPassword', get_string('missingpassword'), 'required', null, 'client');
        $mform->addElement('header', 'supplyinfo', get_string('supplyinfo'),'');

        $namefields = useredit_get_required_name_fields();
        foreach ($namefields as $field) {
            $mform->addElement('text', $field, get_string($field), 'maxlength="100" size="30"');
            $mform->setType($field, core_user::get_property_type('firstname'));
            $stringid = 'missing' . $field;
            if (!get_string_manager()->string_exists($stringid, 'moodle')) {
                $stringid = 'required';
            }
            $mform->addRule($field, get_string($stringid), 'required', null, 'client');
        }

        $mform->addElement('hidden', 'city', get_string('city'));
        $mform->setType('city', core_user::get_property_type('city'));
        if (!empty($CFG->defaultcity)) {
            $mform->setDefault('city', $CFG->defaultcity);
        }

        $mform->addElement('hidden', 'country', get_string('country'));
        $mform->setType('country', PARAM_TEXT);

        if( !empty($CFG->country) ){
            $mform->setDefault('country', $CFG->country);
        }else{
            $mform->setDefault('country', '');
        }

        profile_signup_fields($mform);
        if ($fields = profile_get_signup_fields()) {
            foreach ($fields as $field) {
                // Check if we change the categories.
                if (!isset($currentcat) || $currentcat != $field->categoryid) {
                    $mform->setExpanded('category_'.$field->categoryid, true);
                };
            }
        }

        if (signup_captcha_enabled()) {
            $mform->addElement('recaptcha', 'recaptcha_element', get_string('security_question', 'auth'));
            $mform->addHelpButton('recaptcha_element', 'recaptcha', 'auth');
            $mform->closeHeaderBefore('recaptcha_element');
        }

        // Add "Agree to sitepolicy" controls. By default it is a link to the policy text and a checkbox but
        // it can be implemented differently in custom sitepolicy handlers.
        $manager = new \core_privacy\local\sitepolicy\manager();
        $manager->signup_form($mform);

        // buttons
        $this->add_action_buttons(true, get_string('createaccount'));

        if (!empty($_POST['email'])) {
            $_POST['username'] = $_POST['email'];
            $this->user_field->setValue($_POST['email']);
        }
    }
}
