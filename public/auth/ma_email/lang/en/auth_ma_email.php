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
 * Strings for component 'auth_ma_email', language 'en'.
 *
 * @package   auth_ma_email
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['auth_ma_emaildescription'] = '<p>MindAtlas email-based self-registration enables a user to create their own account via the \'sign up\' button on the login page. 
The user then receives an email containing a secure link to a page where they can confirm their account. 
Future logins just check the username and password against the stored values in the Moodle database.</p>
If a user registers an account with an email address that is not in the whitelist domain, the admin will receive an email with the user\'s details to approve or reject the account.
<p>Note: In addition to enabling the plugin, email-based self-registration must also be selected from the self registration drop-down menu on the \'Manage authentication\' page.</p>';
$string['auth_ma_emailnoemail'] = 'Tried to send you an email but failed!';
$string['auth_ma_emailrecaptcha'] = 'Adds a visual/audio confirmation form element to the sign-up page for email self-registering users. This protects your site against spammers and contributes to a worthwhile cause. See https://www.google.com/recaptcha for more details.';
$string['auth_ma_emailrecaptcha_key'] = 'Enable reCAPTCHA element';
$string['auth_ma_emailsettings'] = 'Settings';
$string['pluginname'] = 'MindAtlas email-based self-registration';
$string['privacy:metadata'] = 'The MindAtlas email-based self-registration authentication plugin does not store any personal data.';

$string['auth_emailadminconfirmationsubject'] = '{$a}: New account request';
$string['auth_emailadminacksubject'] = 'Pending Approval: LMS registration';
$string['auth_emailadminackmessage'] = '
Dear {$a->firstname},

Thank you for your request to register with the LMS. 

Please make sure you\'ve registered using your organisation/business email address. 

Your request is pending approval by our site administrators. You will receive an email notification once your registration has been reviewed. 

We appreciate your interest and look forward to supporting your learning. 

Thank you,
LMS administrator 
';

$string['auth_emailadminconfirmation'] = '
Hi {$a->admin},

A new account has been requested at {$a->sitename} with the following data:

Full name: {$a->firstname}
Username:  {$a->username}
Email:     {$a->email}

To confirm or reject the new account, please click <a href="{$a->decide}">here.</a>

You can also confirm accounts from within Moodle by going to
Site Administration -> Users -> Accounts -> Browse list of users

Thank you.
{$a->supportuser}
';
$string['auth_emailadminuserconfirmationsubject'] = '{$a}: Your LMS registration has been approved';
$string['auth_emailadminuserconfirmation'] = '
Dear {$a->firstname},

Good news. Your registration with the LMS has been approved with the following details:

Username: {$a->username}
Password: password chosen at sign up
These details can be updated upon logging in.

Please go to {$a->link} to access your courses.

We look forward to supporting your learning journey. 

Thank you,
LMS administrator 
';
$string['auth_emailadmin_confirmed'] = 'You have successfully confirmed {$a->firstname} {$a->lastname}\'s registration.';
$string['auth_emailadmin_alreadyconfirmed'] = 'Registration has already been confirmed';
$string['auth_emailadmin_rejected'] = 'Registration has been rejected.';
$string['auth_emailadmin_confirmmessage'] = 'Are you sure you want to confirm {$a->fullname}?';
$string['auth_emailadmin_rejectedfailed'] = 'Unable to reject this registration because it has been confirmed before.';
$string['auth_emailadmin_userrejectionsubject'] = 'LMS registration - Unrecognised email domain';
$string['auth_emailadmin_userrejectionwithreason'] = '
Hi {$a->firstname},

Your account has been rejected for the following reason:

<i>{$a->reason}</i>

Please respond to this email to provide additional information so that your account maybe confirmed.

Thank you.
{$a->supportuser}

';

$string['auth_emailadmin_userrejectionwithoutreason'] = '
Dear {$a->firstname},

Thanks for your interest in the LMS.

It looks like the email you used isn\'t linked to an approved organisation.

If you registered with a personal email address, please register again using your organisation/business email address.

If you believe this is an error, please reach out to the site administrators.

Thank you 
LMS administrator 
';
$string['auth_emailadmin_rejectedwithemail'] = 'Registration has been rejected and email has been sent to user.';
$string['auth_emailadmin_nouserfound'] = 'This account has been rejected before.';
$string['auth_emailadmin_rejectedwithreason'] = 'Rejected user with following message:';
$string['auth_emailadmin_confirmorreject'] = 'Confirm or Reject User Account';
$string['auth_emailadmin_approveregistration'] = 'Approve registration:';
$string['auth_emailadmin_reason'] = 'Reason:';
$string['auth_emailadmin_reason_placeholder'] = 'Input reason for rejecting account...';
$string['auth_emailadmin_emailuser'] = 'Email user';
$string['auth_emailadmin_emailinstruction1'] = 'If the Email User checkbox is ticked, a new user will receive an email about the rejection details and has a chance to provide further information. Then the new user registration could be confirmed.';
$string['auth_emailadmin_emailinstruction2'] = 'If the Email User checkbox is NOT ticked, a new user registration will be rejected completely.';
$string['auth_emailadmin_userconfirmed'] = 'This account has been approved by {$a->firstname} {$a->lastname}';
$string['auth_emailadmin_userconfirmed_noadmin'] = 'The user have been approved without any approval from admin.';
$string['auth_emailadmin_actionsheader'] = 'Actions history';
$string['auth_emailadmin_approveduser'] = 'Approved user';
$string['auth_emailadmin_updateregistration'] = 'Update Registration';