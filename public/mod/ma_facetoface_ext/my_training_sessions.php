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
require_once(__DIR__ . '/lib/lib.php');
require_once(__DIR__.'/lib/mindatlas_plugin_library.php');

$plugin_f2f_lib = true;

require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once(__DIR__ . '/lib/lib_facetoface.php');

global $THEME, $USER;

require_login();

$plugin_lib = new mindatlas_plugin_library();

$url = new moodle_url('/mod/ma_facetoface_ext/my_training_sessions.php');
$PAGE->set_url($url);


$context = context_system::instance();

$PAGE->set_context($context);

$title = 'My training sessions';
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('standard');
// $PAGE->add_body_classes(['full-width']);
$PAGE->navbar->ignore_active();
$PAGE->navbar->add('My training sessions', $url);

$output = $PAGE->get_renderer('core', 'course');
//$imageurl= $THEME->brand["mycourses_banner"];
$content = '
    <section id="section-banner" class=" page-banner" style="background-image: url(img/banner.jpg)">
        <div class="banner-box">
            <h1 class="page-title">Training Booking</h1>
        </div>
    </section>';
$text1 = get_config('theme_mindatlas', 'training_text');
$text2 = get_config('theme_mindatlas', 'training_textt');
$content .= '<div class="training-wrapper">
<section id="text-details">'.$text1.' 
    <div class="row second-text">
        <div class="col-lg-9">
        '.$text2.'
        </div>
        <div class="col-lg-3">
        <button type="button" class="btn-custom bg-color-brand-2 hover-bg-color-brand-2 float-left text-white font-weight-bold mb-2 w-100" data-toggle="modal" data-target="#exampleModal">
        Link to data collection
</button>

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Facilitated training data collection - information for participants</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        ';

       
    $content .= '<div class=""><p> This document outlines what training data are collected from practitioners and agencies who are participating in the Family Preservation and Reunification Response (the Response) and what the data are used for.</p>';
    
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
      
    $content .='  </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
        </div>
    </div>
</section>';

$content .= '<h3 class="mt-5 mb-2">Booking Upcoming Events</h3>';
echo $OUTPUT->header();
echo $content;

?>

<?php
$back_url = $CFG->wwwroot;

$user_sessions = dffh_get_user_sessions($USER->id);

$sessionsTable = "";

if(!empty($user_sessions)) {
    
    $sessionsTable .= "<table className='training-table w-100'>
                <tbody>
                    <tr className='training-table-header'>
                        <td>TRAINING MODULE</td>
                        <td>SHORT DESCRIPTION</td>
                        <td>LOCATION</td>
                        <td>START DATE</td>
                        <td>END DATE</td>
                        <td>TIME</td>
                        <td className='button-head'>REGISTER/WAITLIST</td>
                    </tr>";
                    
                    foreach($user_sessions as $item) {
                       $sessionsTable .= "<tr key='".$item->id."' className=''>
                        <td>".$item->name."</td>
                        <td>".$item->details."</td>
                        <td>".$item->location."</td>
                        <td>";
                        
                        if($item->timestart) $sessionsTable .= "<p>".$item->timestart."</p>";
                        if($item->timestart2) $sessionsTable .= "<p>".$item->timestart2."</p>";
                        if($item->timestart3) $sessionsTable .= "<p>".$item->timestart3."</p>";
                    
                        $sessionsTable .= "</td><td>";
                        if($item->timefinish) $sessionsTable .= "<p>".$item->timefinish."</p>";
                        if($item->timefinish2) $sessionsTable .= "<p>".$item->timefinish2."</p>";
                        if($item->timefinish3) $sessionsTable .= "<p>".$item->timefinish3."</p>";
                        
                        $sessionsTable .= "</td><td>";
                        if($item->time) $sessionsTable .= "<p>".$item->time."</p>";
                        if($item->time2) $sessionsTable .= "<p>".$item->time2."</p>";
                        if($item->time3) $sessionsTable .= "<p>".$item->time3."</p>";
                        $sessionsTable .= "</td><td className='button-td mt-2'>";
                        
                        if($item->status != null) {
                            $sessionsTable .= "<a href='/mod/ma_facetoface_ext/sessions_details.php?id=".$item->id."' className='btn-custom bg-color-brand-2 hover-bg-color-brand-2 float-left text-white font-weight-bold mb-2'>".$item->status."</a>";
                        } else {
                          $sessionsTable .= "<a className='btn p-3 btn-primary'>Book</a>";
                        }
                        
                        if($item->statuscancel != null) {
                            $sessionsTable .= "<a href='/mod/ma_facetoface_ext/sessions_details.php?id=".$item->id."' className='btn-custom bg-color-brand-2 hover-bg-color-brand-2 float-left text-white font-weight-bold mb-2'>".$item->statuscancel."</a>";
                        }
                    
                        $sessionsTable .= "</td>
                      </tr>";
                        
                    }
                $sessionsTable .= "</tbody></table>";
    }
    
    echo $sessionsTable;

?>
</div>
<?php
$content = '<div class="training-wrapper"><h3 class="mt-5">Available Practice Modules</h3><p>Below is the full list of available training sessions as part of the Response.</p>';



$content .= '<table class="module-table w-100">
<thead>
    <tr class="module-table-header">
        <td>MODULE</td>
        <td>DURATION</td>
    </tr>
</thead>
<tbody>';

$content .= theme_mindatlas_get_modules();
$content .= '</tbody> </table>';
$content .= '</div>';
$content .= '<script src="src/theme.js" type="text/javascript"></script>';
echo $content;
echo $OUTPUT->footer();