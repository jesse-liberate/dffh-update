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
 * Authentication Plugin: Email Authentication
 *
 * @author Martin Dougiamas
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package auth_ma_email
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');

/**
 * Email authentication plugin.
 */
class auth_plugin_ma_email extends auth_plugin_base {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'ma_email';
        $this->config = get_config('auth_ma_email');
    }

    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     *
     * @deprecated since Moodle 3.1
     */
    public function auth_plugin_email() {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::__construct();
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    function user_login ($username, $password) {
        global $CFG, $DB;
        if ($user = $DB->get_record('user', array('username'=>$username, 'mnethostid'=>$CFG->mnet_localhost_id))) {
            return validate_internal_user_password($user, $password);
        }
        return false;
    }

    /**
     * Updates the user's password.
     *
     * called when the user password is updated.
     *
     * @param  object  $user        User table object  (with system magic quotes)
     * @param  string  $newpassword Plaintext password (with system magic quotes)
     * @return boolean result
     *
     */
    function user_update_password($user, $newpassword) {
        $user = get_complete_user_data('id', $user->id);
        // This will also update the stored hash to the latest algorithm
        // if the existing hash is using an out-of-date algorithm (or the
        // legacy md5 algorithm).
        return update_internal_user_password($user, $newpassword);
    }

    function can_signup() {
        return true;
    }

    /**
     * Sign up a new user ready for confirmation.
     * Password is passed in plaintext.
     *
     * @param object $user new user object
     * @param boolean $notify print notice with link and terminate
     */
    function user_signup($user, $notify=true) {
        // Standard signup, without custom confirmatinurl.
        return $this->user_signup_with_confirmation($user, $notify);
    }

    /**
     * Sign up a new user ready for confirmation.
     *
     * Password is passed in plaintext.
     * A custom confirmationurl could be used.
     *
     * @param object $user new user object
     * @param boolean $notify print notice with link and terminate
     * @param string $confirmationurl user confirmation URL
     * @return boolean true if everything well ok and $notify is set to true
     * @throws moodle_exception
     * @since Moodle 3.2
     */
    public function user_signup_with_confirmation($user, $notify=true, $confirmationurl = null) {
        global $CFG, $DB, $SESSION;
        require_once($CFG->dirroot.'/user/profile/lib.php');
        require_once($CFG->dirroot.'/user/lib.php');

        $allowed_domains = explode("\n", str_replace(["\r\n", "\n\r", "\r"], "\n",
            $this->config->allowed_domains));
        $allowed_domains = array_flip($allowed_domains);

        $plainpassword = $user->password;
        $user->password = hash_internal_user_password($user->password);
        if (empty($user->calendartype)) {
            $user->calendartype = $CFG->calendartype;
        }

        $user->id = user_create_user($user, false, false);

        user_add_password_history($user->id, $plainpassword);

        // Save any custom profile field information.
        profile_save_data($user);

        // Save wantsurl against user's profile, so we can return them there upon confirmation.
        if (!empty($SESSION->wantsurl)) {
            set_user_preference('auth_ma_email_wantsurl', $SESSION->wantsurl, $user);
        }

        // Trigger event.
        \core\event\user_created::create_from_userid($user->id)->trigger();

        $user_email_domain = substr(strrchr($user->email, '@'), 1);
        $is_user_allowed = array_key_exists($user_email_domain, $allowed_domains);

        if ($is_user_allowed) {
            if (!send_confirmation_email($user, $confirmationurl)) {
                print_error('auth_ma_emailnoemail', 'auth_ma_email');
            }
        } else {
            if (!$this->send_confirmation_email_support($user)) {
                print_error('auth_ma_emailnoemail', 'auth_ma_email');
            }
        }

        if ($notify) {
            global $CFG, $PAGE, $OUTPUT;
            $emailconfirm = get_string('emailconfirm');
            $PAGE->navbar->add($emailconfirm);
            $PAGE->set_title($emailconfirm);
            $PAGE->set_heading($PAGE->course->fullname);
            echo $OUTPUT->header();
            notice(get_string('emailconfirmsent', '', $user->email), "$CFG->wwwroot/index.php");
        } else {
            return true;
        }
    }

    /**
     * Returns true if plugin allows confirming of new users.
     *
     * @return bool
     */
    function can_confirm() {
        return true;
    }

    /**
     * Confirm the new user as registered.
     *
     * @param string $username
     * @param string $confirmsecret
     */
    function user_confirm($username, $confirmsecret) {
        global $DB, $SESSION, $CFG, $SITE;
        $user = get_complete_user_data('username', $username);

        if (!empty($user)) {
            if ($user->auth != $this->authtype) {
                return AUTH_CONFIRM_ERROR;

            } else if ($user->secret == $confirmsecret && $user->confirmed) {
                return AUTH_CONFIRM_ALREADY;

            } else if ($user->secret == $confirmsecret) {   // They have provided the secret key to get in
                $DB->set_field("user", "confirmed", 1, array("id"=>$user->id));
                if ($wantsurl = get_user_preferences('auth_ma_email_wantsurl', false, $user)) {
                    // Ensure user gets returned to page they were trying to access before signing up.
                    $SESSION->wantsurl = $wantsurl;
                    unset_user_preference('auth_ma_email_wantsurl', $user);
                }

                return AUTH_CONFIRM_OK;
            }
        } else {
            return AUTH_CONFIRM_ERROR;
        }
    }

    function prevent_local_passwords() {
        return false;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    function is_internal() {
        return true;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    function can_change_password() {
        return true;
    }

    /**
     * Returns the URL for changing the user's pw, or empty if the default can
     * be used.
     *
     * @return moodle_url
     */
    function change_password_url() {
        return null; // use default internal method
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * @return bool
     */
    function can_reset_password() {
        return true;
    }

    /**
     * Returns true if plugin can be manually set.
     *
     * @return bool
     */
    function can_be_manually_set() {
        return true;
    }

    /**
     * Returns whether or not the captcha element is enabled.
     * @return bool
     */
    function is_captcha_enabled() {
        return get_config("auth_{$this->authtype}", 'recaptcha');
    }

    /**
     * Return a form to capture user details for account creation.
     * This is used in /login/signup.php.
     * @return moodle_form A form which edits a record from the user table.
     */
    function signup_form() {
        global $CFG;

        require_once($CFG->dirroot.'/login/signup_form.php');
        return new \auth_ma_email\form\login_signup_form(null, null, 'post', '', array('autocomplete' => 'on'));
    }

    /**
     * Send email to admin with confirmation text and activation link for
     * new user.
     *
     * @param user $user A {@link $USER} object
     * @return bool Returns true if mail was sent OK to *any* admin and false if otherwise.
     */
    function send_confirmation_email_support($user) {
        global $CFG, $DB;
        $config = $this->config;

        $site = get_site();
        $supportuser = core_user::get_support_user();

        $data = new stdClass();
        $data->firstname = $user->firstname;
        $data->username = $user->username;
        $data->email = $user->email;
        $data->sitename  = format_string($site->fullname);
        $data->admin     = generate_email_signoff();
        $data->supportuser = fullname($supportuser);

        $data->userdata = '';
        foreach(((array) $user) as $dataname => $datavalue) {
            $data->userdata	 .= $dataname . ': ' . $datavalue . PHP_EOL;
        }

        // Add custom fields
        $data->userdata .= $this->list_custom_fields($user);

        /*----------------------------START: Email to Registration Approval--------------------------------------*/

        $subject = get_string('auth_emailadminconfirmationsubject', 'auth_ma_email', format_string($site->fullname));

        $username = urlencode($user->username);
        $username = str_replace('.', '%2E', $username); // prevent problems with trailing dots
        $data->link = $CFG->wwwroot .'/auth/ma_email/confirm.php?data='. $user->secret .'/'. $username;

        // Data passed to the decide.php page,
        $data->decide = $CFG->wwwroot .'/auth/ma_email/decide.php?username='.$username;
        $data->decide .= '&secret='.$user->secret;

        $user->mailformat = 1;  // Always send HTML version as well

        /*-----------------------------------Acknowledgement email to user------------------------------------------*/

        $acksubject = get_string('auth_emailadminacksubject', 'auth_ma_email', format_string($site->fullname));
        $ackmessage     = get_string('auth_emailadminackmessage', 'auth_ma_email', $data);
        $ackmessagehtml = text_to_html(get_string('auth_emailadminackmessage', 'auth_ma_email', $data), false, false, true);
        email_to_user($user, $supportuser, $acksubject, $ackmessage, $ackmessagehtml);

        /*-------------------------------CONTINUE: Email to Registration Approval-----------------------------------*/
        $admins = get_admins();
        $return = false;
        $admin_found = false;

        // Send message to first admin (main) only. Remove "break" for all admins
        $config->notif_strategy = intval($config->notif_strategy);
        $send_list = array();
        foreach ($admins as $admin) {
            if ($config->notif_strategy < 0 || $config->notif_strategy == $admin->id) {
                $admin_found = true;
            }
            if ($admin_found) {
                $send_list[] = $admin;
                if ($config->notif_strategy == -1 || $config->notif_strategy >= 0 ) {
                    break;
                }
            }
        }
        // echo '<pre> Send list: '.print_r($send_list, true).'</pre>';
        $errors = array();
        $data_decide = $data->decide;
        foreach ($send_list as $admin) {
            $data->admin = fullname($admin);
            $data->decide = $data_decide . '&adminid=' . $admin->id;

            $messagehtml = nl2br(get_string('auth_emailadminconfirmation', 'auth_ma_email', $data));
            $message = html_to_text($messagehtml);

            $result = email_to_user($admin, $supportuser, $subject, $message, $messagehtml);
            $return |= $result;
            if ($result) {
                $errors[] = $admin->username;
            }
        }

        $error = '';
        if (!$admin_found) {
            $error = 'No admin found based on notification strategy. Please check auth_ma_email configuration.';
        }

        if (count($errors) > 0) {
            $error = 'Could not send registration notification to: ';
            foreach($errors as $admin) {
                $error .= $admin . " ";
            }
        }

        return $return;
    }

    /**
     * Return an array with custom user properties.
     *
     * @param user $user A {@link $USER} object
     */
    function list_custom_fields($user) {
        global $CFG, $DB;

        $result = '';
        if ($fields = $DB->get_records('user_info_field')) {
            foreach($fields as $field) {
                $fieldobj = new profile_field_base($field->id, $user->id);
                $result .= format_string($fieldobj->field->name.':') . ' ' . $fieldobj->display_data() . PHP_EOL;
            }
        }

        return $result;
    }

}


