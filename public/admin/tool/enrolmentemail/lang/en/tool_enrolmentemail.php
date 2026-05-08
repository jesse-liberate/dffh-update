<?php
/**
 * Strings for the Cohort Enrolment Rules.
 *
 * @package    tool_cohortenrolmentrules
 * @copyright  2016 Charlie Tran 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Manage course email notification';
$string['savechangessuccess'] = 'Successfully Saved Changes';

$string['basicconfiguration'] = 'Basic Configuration';
$string['initialcoursenotification'] = 'Initial Course Enrolment Notification';
$string['initialcoursenotification_help'] = 'Set initial course enrolment notification for a newly created course.';
$string['on'] = 'On';
$string['off'] = 'Off';
$string['maxattemptsallowed'] = 'Maximum Attempts Allowed Before Archived';
$string['maxattemptsallowed_help'] = 'Number of attempts allowed before system archives email notification in queue.';
$string['courselist'] = 'Course List';
$string['enable'] = 'Enable';
$string['emailtemplate'] = 'Email Template';
$string['subjectline'] = 'Email Subject Line';
$string['subjectline_help'] = 'Email subject line.<br/>
You can use placeholders to inject data into subject line. The following placeholders are available to use:<br/>
- {$firstname}<br/>
- {$lastname}<br/>
- {$sitename}<br/>
- {$signaturename}
';
$string['signaturename'] = '{$signaturename}';
$string['signaturename_help'] = 'A placeholder. You can customize this field and inject data into email content. If it is not specified, Support name (Administration > Server > Support contact > Support name) will be used.';
$string['contact'] = '{$contact}';
$string['contact_help'] = 'A placeholder. You can customize this field and inject data into email content. If it is not specified, Support email (Administration > Server > Support contact > Support email) will be used.';
$string['content'] = 'Email Content';
$string['content_help'] = 'Email content. If not specified, default template will be used.<br/>
You can use placeholders to inject data into email content. The following placeholders are available to use:<br/>
- {firstname}<br/>
- {lastname}<br/>
- {$sitename}<br/>
- {$sitelink}<br/>
- {$signaturename}<br/>
- {$contact}<br/>
- {$courselist}<br/>
- {$userprofilelink}
';
$string['defaulttemplate'] = 'Default Template';
$string['defaulttemplate_help'] = 'If email content is not specified, the system will use the default template for email content';
$string['defaulttemplatecontent'] = '
<p>Hi {$firstname},</p>

<p>As part of your learning on {$sitelink}, you have recently been enrolled into the following courses:
<p>{$courselist}</p>

<p>Please login to {$sitelink} and complete this training at your earliest convenience.</p>

<p>Kind regards,</p>

<p>{$signaturename}</p>

<p>{$contact}</p>
';
