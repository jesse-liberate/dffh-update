<?php

namespace tool_setdeadline\form;

use tool_setdeadline\model\reminder;

require_once("$CFG->libdir/formslib.php");
require_once('../../../lib.php');

class reminder_override_form extends \moodleform {

  public function definition() {
    $mform = $this->_form;
    $customdata = $this->_customdata;

    $all_admins = $customdata['all_admins'];
    $courseid   = $customdata['courseid'];

    $edit       = isset($customdata['edit']) ? true : false;

    if (!$edit) {
      $users_select = $mform->addElement('select', 'user', get_string('user'), $customdata['all_users']);
      $users_select->setMultiple(true);
      $mform->addRule('user', 'Please select a user', 'required', null, 'client');
    }
    else {
      $user_text = $mform->addElement('text', 'user', get_string('user'), 'size="60"');
      $user_text->freeze();
      $mform->setType('user', PARAM_TEXT);
    }

    $mform->addElement(
      'date_selector', 'firstreminder', get_string('override_deadline:firstreminder', 'tool_setdeadline'));

    $period_group = [];
    
    $secondreminder_box = $mform->createElement(
      'text', 'secondreminder', get_string('override_deadline:secondreminder', 'tool_setdeadline')
    );
    $secondreminder_box->freeze();
  
    $group[] = $secondreminder_box;
    $group[] = $mform->createElement(
      'static', 'secondreminder_desc', '', get_string('override_deadline:after_firstreminder', 'tool_setdeadline')
    );
    $mform->addGroup(
      $group, 'reminder_group', get_string('override_deadline:secondreminder', 'tool_setdeadline'), null, false
    );
    
    $mform->setType('firstreminder' , PARAM_INT);
    $mform->setType('secondreminder', PARAM_INT);

    $repeated_box = $mform->addElement(
      'checkbox', 'repeated', get_string('repeat_period_two_indefinitely', 'tool_setdeadline')
    );
    //$repeated_box->setChecked(true);
    $repeated_box->freeze();

    $mform->addElement(
      'header', 'also_send_to', get_string('also_send_to', 'tool_setdeadline')
    );

    $manager_checkbox = $mform->addElement(
      'checkbox', 'manager', get_string('manager', 'tool_setdeadline')
    );
    //$manager_checkbox->setChecked(true);
    $manager_checkbox->freeze();

    $siteadmin_checkbox = $mform->addElement(
      'checkbox', 'siteadmin', get_string('site_admin', 'tool_setdeadline')
    );
    //$siteadmin_checkbox->setChecked(true);
    $siteadmin_checkbox->freeze();
    
    // $site_admins_select = $mform->addElement(
    //   'select', 'emailtoadmin', '', $all_admins
    // );
    // reset($all_admins);
    // // $site_admins_select->setSelected(key($all_admins));
    // $site_admins_select->setMultiple(true);
    // $site_admins_select->freeze();

    $mform->addElement('hidden', 'id');
    $mform->setType('id', PARAM_INT);
    
    if (isset($customdata['reminderid'])) {
      $mform->addElement('hidden', 'reminderid', $customdata['reminderid']);
      $mform->setType('reminderid', PARAM_INT);
    }
    if (isset($customdata['overrideid'])) {
      $mform->addElement('hidden', 'overrideid', $customdata['overrideid']);
      $mform->setType('overrideid', PARAM_INT);
    }

    $mform->addElement('hidden', 'courseid', $courseid);
    $mform->setType('courseid', PARAM_INT);


    $this->add_action_buttons();
  }
  
  public function setData($data = null) {
    // $reminder_assigns = $reminder->reminder_assigns;
    // error_log(var_export($reminder_assigns, true));
    // error_log(var_export(explode(',', $reminder->emailadminlist), true));
    
    if (isset($data)) {
      if (isset($data['secondreminder'])) {
        $data['secondreminder'] = $data['secondreminder'] / 86400;
      }
      if (isset($data['emailadminlist'])) {
        $data['emailtoadmin'] = explode(',', $data['emailadminlist']);
        if (!isset($data['siteadmin']) || $data['siteadmin'] == 0) {
          $data['emailtoadmin'] = array();
        }
      }
      parent::set_data($data);      
    }
  }

  public function set_data($default_values) {
    error_log(__FILE__ . ' ' . __METHOD__);
    $reminder = new reminder($default_values);
    $reminder_assigns = $reminder->reminder_assigns;
    $type = '';
    $values = [];
    
    //error_log(var_export($reminder_assigns, true));

    foreach ($reminder_assigns as $reminder_assign) {
      $type = $reminder_assign->type;
      $values[$type][] = $reminder_assign->instanceid;
      $default_values->course = $reminder_assign->courseid;
    }
    foreach ($values as $type => $type_values) {
      if (count($type_values) == 1) {
        $default_values->$type = reset($type_values);
      } else {
        $default_values->$type = $type_values;
      }
    }

    error_log('deadline type ' . $type);
//    $default_values->deadline_type = $type;
//    $default_values->pone = $default_values->firstreminder / 86400;
    $default_values->ptwo = $default_values->secondreminder / 86400;
    $default_values->emailtoadmin = explode(',', $default_values->emailadminlist);
//
    parent::set_data($default_values);
  }

  public function validation($data, $files) {
      $errors = parent::validation($data, $files);

      if (array_key_exists('firstreminder', $data) && $data['firstreminder'] < time()) {
          $errors['firstreminder'] = "First reminder must be the future date";
      }

      return $errors;
  }

}
