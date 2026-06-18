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

use core\analytics\indicator\read_actions;

require_once(__DIR__ . '../../../../config.php');
//require_once(__DIR__ . '../../lib.php');
global $THEME, $USER;

$url = new moodle_url('/auth/ma_email/pages/data_collection.php');
$PAGE->set_url($url);

$context = context_system::instance();

$PAGE->set_context($context);

$title = 'Data collection';
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('standard');
// $PAGE->add_body_classes(['full-width']);
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('Data collection', $url);

$output = $PAGE->get_renderer('core', 'course');
//$imageurl= $THEME->brand["mycourses_banner"];
$content = "<p>This notice outlines how your personal information is collected, used, and shared when you participate in training via the learning management system (LMS).  The Department of Families, Fairness and Housing (the department) is committed to protecting your privacy.</p> 
<p>The department collects and handles personal information in the LMS for the purposes of:</p>
<ol>
<li>Supporting overall maintenance and administration of the LMS</li>
<li>Managing your uptake, progress and completion of each training module</li>
<li>Developing strategies to improve engagement across agencies using de-identified training completion data</li>
<li>Identifying weaknesses and areas of improvement in the LMS operations.</li>
</ol>
<p>The types of personal information collected by the department (visible in your training profile) for the above purposes include:</p>
<ol>
<li>Your name and email address</li>
<li>Your organisation, program area, local area and title</li>
<li>Your self-guided learning completion</li>
<li>Your training completion.</li>
</ol>
<p>In order to understand your engagement with the LMS, make training improvements and increase uptake, your personal information above will be disclosed to Implementation Science leads within the department. Training completion may also be shared with your allocated Practice Leads at the Centre for Excellence in Child and Family Welfare or the Victorian Aboriginal Child Care Agency. Team Leaders and Implementation Coordinators within your agency may also have access to your information, to monitor their teams’ completion of relevant training and self-guided learning.</p>
<p>De-identified data from an organisation-based level (aggregate only) may be disclosed to the relevant department evaluation teams, local implementation team members and service provider leadership teams to understand and manage the relationship between training, coaching and family outcomes. None of your personally identifiable information will be used for this purpose or any other purpose (unless required by law).</p>
<p>Please be aware that if you do not provide us with this information, you will not be able to complete the training. You may access your information that you provide to the department within your training profile. You may self manage your training profile at any time by amending or updating your details in the account settings.</p>
<p>For more information, please refer to the department's privacy policy at <a href='https://www.dffh.vic.gov.au' target='_blank'>https://www.dffh.vic.gov.au</a> or contact us at <a href='mailto:FS-Implementation@dffh.vic.gov.au'>FS-Implementation@dffh.vic.gov.au</a>. You may also reach the department’s Privacy team directly at <a href='mailto:privacy@dffh.vic.gov.au'>privacy@dffh.vic.gov.au</a>.</p>";


echo $OUTPUT->header();
echo $content;
