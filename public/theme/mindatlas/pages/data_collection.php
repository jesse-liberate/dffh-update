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
require_once(__DIR__ . '../../lib.php');
require_once($CFG->dirroot . '/blocks/theme_support/classes/mindatlas_theme_library.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib_mindatlas.php');
global $THEME, $USER;

require_login();

$theme_lib = new mindatlas_theme_library();

$url = new moodle_url('/theme/mindatlas/pages/data_collection.php');
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
$imageurl= $THEME->brand["mycourses_banner"];
$content = '
    <section id="section-banner" class=" page-banner" style="background-image: url('.$imageurl.')">
        <div class="banner-box">
            <h1 class="page-title">Facilitated training data collection - information for participants</h1>
        </div>
    </section>';
$content .= '<div class="container mt-5"><p> This document outlines what training data are collected from practitioners and agencies who are participating in the Family Preservation and Reunification Response (the Response) and what the data are used for.</p>';

$content .= "<h4>   What training data are collected?</h4>";

$content .= "<p> Information is collected via: </p>";
   
$content .= "<ul>  1.Program administrative data, including:<li>  Name, role type, email and agency (NB. Name and email are not used in any reporting)</li>
<li>Facilitated learning attendance per practice module/cultural element
</li>
<li>Team Leader coaching training attendance.
</li>
</ul>";
$content .= "<ul>   2.Practitioner and Team Leader pre and post training surveys, including:
<li>  Role type, agency, years of work experience(NB. Name and email are not collected)</li>
<li> Participant skills self-assessment, per practice module/cultural element
</li>
<li> Participation in self-guided learning 
</li>
<li>  Participant perception of training effectiveness, per practice module/ cultural element
</li>
<li> Participant perception of training appropriateness, per practice module/cultural element
</li>
<li>  Participant perception of training quality, per practice module/cultural element.
</li>
</ul>";

$content .= "<ul>  3.Practitioner and Team Leader online survey, including:<li>  Staff rated acceptability, appropriateness, and feasibility of the training model.</li>
</ul>";

 
    
$content .= "<h4>     Why is this data collected?</h4>";
    
$content .= "<p>  Training is a core strategy used in the Response to effectively implement the evidence-based practice modules with families. Collecting training data enables us to monitor the delivery, uptake, quality and effectiveness of training. It enables us to continually improve the training strategy by flagging any potential issues or gaps that need to be addressed. </p>";
      
$content .= "<p> <b>  Please note that the training data collection is not a way of monitoring individual performance or agency compliance.</b> </p>";
  
$content .= "<p> The data contribute to a broader set of Implementation Quality Indicators collected across the Response. These indicators provide insights to support data-led decision making and continuous quality improvement at both a local and systems level. This aims to enhance the implementation of the Response and ultimately improve outcomes for children and families.</p></div>";
  
  
   
   
   
   
    
   
    
   
    
    
    
   
    
   
    
   
    
$text1 = $THEME->brand["training_text"];
$text2 = $THEME->brand["training_textt"];

echo $OUTPUT->header();
echo $content;
echo $OUTPUT->footer();
