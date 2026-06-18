<?php

namespace auth_ma_email\form;

use login_signup_form as base_login_signup_form;
use core_user;

class login_signup_form extends base_login_signup_form {
    public $user_field = null;
    
    
    function definition() {
        global $USER, $CFG,$PAGE;

        $mform = $this->_form;

        $mform->addElement('text', 'username', get_string('username'), 'maxlength="100" size="12" autocapitalize="none"');
        $mform->setType('username', PARAM_RAW);
        $mform->addRule('username', get_string('missingusername'), 'required',null,'server');

        if (!empty($CFG->passwordpolicy)){
            $mform->addElement('static', 'passwordpolicyinfo', '', print_password_policy());
        }
        $mform->addElement('password', 'password', get_string('password'), [
            'maxlength' => MAX_PASSWORD_CHARACTERS,
            'size' => 12
        ]);
        $mform->setType('password', core_user::get_property_type('password'));
        $mform->addRule('password', get_string('missingpassword'), 'required',null,'server');
        $mform->addRule('password', get_string('maximumchars', '', MAX_PASSWORD_CHARACTERS),
            'maxlength', MAX_PASSWORD_CHARACTERS, 'client');
            
            
        $mform->addElement('password', 'confirmpassword', 'Confirm password'/*get_string('password_confirm', 'auth')*/, [
            'maxlength' => MAX_PASSWORD_CHARACTERS,
            'size' => 12
        ]);
        $mform->setType('confirmpassword', core_user::get_property_type('password'));
        
        $mform->addRule('confirmpassword', get_string('missingpassword'), 'required', null, 'client');
        $mform->addRule(array('confirmpassword', 'password'), 'Passwords must match'/* get_string('passwords_must_match')*/, 'compare', 'eq', 'server');

        $mform->addElement('text', 'email', get_string('email'), 'maxlength="100" size="25"');
        $mform->setType('email', core_user::get_property_type('email'));
        $mform->addRule('email', get_string('missingemail'), 'required', null, 'client');
        $mform->setForceLtr('email');

        $mform->addElement('text', 'email2', get_string('emailagain'), 'maxlength="100" size="25"');
        $mform->setType('email2', core_user::get_property_type('email'));
        $mform->addRule('email2', get_string('missingemail'), 'required', null, 'client');
        $mform->setForceLtr('email2');
        
        $mform->addRule(array('email', 'email2'), 'Emails must match'/* get_string('passwords_must_match')*/, 'compare', 'eq', 'server');

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

        /*$mform->addElement('text', 'city', get_string('city'), 'maxlength="120" size="20"');
        $mform->setType('city', core_user::get_property_type('city'));
        if (!empty($CFG->defaultcity)) {
            $mform->setDefault('city', $CFG->defaultcity);
        }

        $country = get_string_manager()->get_list_of_countries();
        $default_country[''] = get_string('selectacountry');
        $country = array_merge($default_country, $country);
        $mform->addElement('select', 'country', get_string('country'), $country);

        if( !empty($CFG->country) ){
            $mform->setDefault('country', $CFG->country);
        }else{
            $mform->setDefault('country', '');
        }*/

        profile_signup_fields($mform);
        
        $html_content = "<div id='moved_agencies_notification'><h3>Have you moved agencies or roles?</h3><p>If so, please do not create a new account. Instead, login with your old email address, then edit your profile to update your email (for LMS notifications) and organisational information (agency, role and program).</p><p>To request your username also be updated to your new email address, please email <a href='mailto:Response@dffh.vic.gov.au'>Response@dffh.vic.gov.au</a></p></div>";
                     
        // 2. Create a static form element to hold the HTML
        $html_element = $mform->addElement('static', 'moved_agencies_notification', '', $html_content);
        
        //$PAGE->requires->js_call_amd('auth_ma_email/formhelper', 'init', ['lastone']);
        
        $js = "
        
        require(['jquery'], function($) {

        var str =
          'I have read and understood the <a style=\"text-decoration: underline;\" target=\"_blank\" href=\"/auth/ma_email/pages/collection_statement.php\">Collection Statement</a> relating to the use of the Learning Management System and how DFFH will collect and use my personal information';
        
            $('label[for=\"id_profile_field_CollectionStatement\"]').css('width','calc(100% - 40px)').html(str);
            
            })";
    
        $PAGE->requires->js_init_code($js);

        if (signup_captcha_enabled()) {
            $mform->addElement('recaptcha', 'recaptcha_element', get_string('security_question', 'auth'));
            $mform->addHelpButton('recaptcha_element', 'recaptcha', 'auth');
            $mform->closeHeaderBefore('recaptcha_element');
        }

        // Hook for plugins to extend form definition.
        core_login_extend_signup_form($mform);

        // Add "Agree to sitepolicy" controls. By default it is a link to the policy text and a checkbox but
        // it can be implemented differently in custom sitepolicy handlers.
        $manager = new \core_privacy\local\sitepolicy\manager();
        $manager->signup_form($mform);

        // buttons
        $this->set_display_vertical();
        $this->add_action_buttons(true, get_string('createaccount'));

    }    
    

    /*function definition() {
        global $USER, $CFG;

        $mform = $this->_form;

        $mform->addElement('header', 'createuserandpass', new \lang_string('createuserandpass', 'auth_ma_email'), '');

        $mform->addElement('text', 'email', get_string('email'), 'maxlength="100" size="25"');
        $mform->setType('email', core_user::get_property_type('email'));
        $mform->addRule('email', get_string('missingemail'), 'required', null, 'client');
        $mform->addRule('email', new \lang_string('casesensitiveerror', 'auth_ma_email'), 'regex', '/^[^A-Z]+$/', 'client');
        $mform->setForceLtr('email');

        $mform->addElement('text', 'email2', get_string('emailagain'), 'maxlength="100" size="25" autocomplete="off"');
        $mform->setType('email2', core_user::get_property_type('email'));
        $mform->addRule('email2', get_string('missingemail'), 'required', null, 'client');
        $mform->addRule('email2', new \lang_string('casesensitiveerror', 'auth_ma_email'), 'regex', '/^[^A-Z]+$/', 'client');
        $mform->setForceLtr('email2');

        $this->user_field = $mform->addElement('hidden', 'username', '');
        $mform->setType('username', PARAM_RAW);

        if (!empty($CFG->passwordpolicy)){
            $mform->addElement('static', 'passwordpolicyinfo', '', print_password_policy());
        }
        $mform->addElement('password', 'password', get_string('password'), 'maxlength="32" size="12"');
        $mform->setType('password', core_user::get_property_type('password'));
        $mform->addRule('password', get_string('missingpassword'), 'required', null, 'client');
        
        $mform->addElement('password', 'confirmPassword', new \lang_string('confirmpassword', 'auth_ma_email'), 'maxlength="32" size="12"');
        $mform->setType('password', core_user::get_property_type('password'));
        //        $mform->addRule('confirmPassword', get_string('passwordsdiffer'), 'required', null, 'client');
        $mform->addRule('confirmPassword', get_string('missingpassword'), 'required', null, 'client');
        
        $mform->addRule(array('password', 'confirmPassword'), new \lang_string('passwords_must_match', 'auth_ma_email'), 'compare', 'eq', 'client');
        
        //Liberate: Moved rule to after element definitions
        //Expected arguments from parent class
        //parent::addRule($element, $message, $type, $format, $validation, $reset, $force);          
        
        $mform->addElement('header', 'supplyinfo', new \lang_string('supplyinfo', 'auth_ma_email'),'');

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
    }*/
}
