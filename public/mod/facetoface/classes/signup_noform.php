<?php

// MA-MODIFIED 

require_once("$CFG->libdir/formslib.php");

/**
 * Class for compatibility with current facetoface's form but this
 * form will automatically signup user with the first manager's email
 * so users are not required to click sign-up in confirm page
 */
class mod_facetoface_signup_noform extends mod_facetoface_signup_form {
    /**
     * This function is overriden to automatically submit data without users clicking submit button
     *
     * @param string $method This parameter is ignored in this form
     * @return void
     */
    function _process_submission($method) {
        $manageremail = '';
        if (!empty($this->_customdata['managersemail'])) {
            $manageremail = $this->_customdata['managersemail'];
        }
        $emailarray = null;

        if (!empty($manageremail)) {
            $emailarray = explode(';', $manageremail);
        }

        if (empty($emailarray)) {
            $emailarray = array('');
        }

        $this->_form->updateSubmission(array(
            'submitbutton' => 1,
            'manageremail' => array_shift($emailarray), // First manager's email
        ), array());
    }
}
