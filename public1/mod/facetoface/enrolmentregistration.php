<?php

require_once('../../config.php');
require_once("$CFG->libdir/completionlib.php");
//require_once('lib_mindatlas.php');
require_once('lib.php');
global $USER, $DB;

if (!isset($userid)) {
  $userid = $USER->id;
}
$baseurl = new moodle_url('/mod/facetoface/enrolmentregistration.php');
require_login(0, false);
$context_system = context_system::instance();
$heading = get_string('f2fenrolmentstatus', 'block_facetoface');
$PAGE->set_context($context_system);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($baseurl);
$PAGE->navbar->add('home');
$PAGE->navbar->add($heading, $baseurl);

$report_type = optional_param('type'          , 'HTML', PARAM_RAW);
$sub         = optional_param('sub'           , ''    , PARAM_TEXT);
$faceid      = optional_param('faceid'        , 0     , PARAM_INT);
$suspended   = optional_param('suspendedusers', 0     , PARAM_TEXT);
$date_from   = optional_param('date_from'     , null  , PARAM_TEXT);
$date_to     = optional_param('date_to'       , null  , PARAM_TEXT);
//error_log('faceid ' . $faceid);

$is_admin = is_siteadmin($USER->id);
$is_manager = ($is_admin) ? true : ma_check_hierarchy_manager($USER->id);
$has_capability = has_capability('block/facetoface:viewreports', context_system::instance());
if (!$is_manager && !$has_capability) {
  redirect(new moodle_url('/'));
}


$option_exclude = get_string('exclude_suspended_users', 'block_reporting');
$option_include = get_string('include_suspended_users', 'block_reporting');
$option_only    = get_string('show_suspended_users_only', 'block_reporting');

if ($sub == '' || !$faceid) {
  $PAGE->requires->js('/lib/mindatlas/jquery/jquery.min.js', true);
  $PAGE->requires->js('/mod/facetoface/css/chosen.js', true);
  $PAGE->requires->css('/mod/facetoface/css/chosen.css');
  $PAGE->requires->js('/lib/mindatlas/jquery/ui/jquery-ui.min.js', true);
  $PAGE->requires->css('/lib/mindatlas/jquery/ui/jquery-ui.min.css');

  $course_options = ma_facetoface_coursef2f_options("", $has_capability, $is_manager);

  echo $OUTPUT->header();
  echo $OUTPUT->heading($heading . ' report');
  if (!$faceid && $sub != '') {
    echo get_string('error:selectfacetoface', 'mod_facetoface');    
  }
  
  $label_course   = get_string('course');
  $label_activity = get_string('activity');
  
  $form = <<< EOT
  <form action='enrolmentregistration.php' method='POST'>
    <table>
      <tr>
        <td>$label_course / $label_activity</td>
        <td><select name='faceid' class='chzn-select'>" . $course_options . '</select></td>
      </tr>
      <tr>
        <td>Session Start Date<br/>(Australia/Melbourne)</td>
        <td>
          <div class="datefilter-range">
            <div class="input-prepend">
              <span class="add-on">From</span>
              <input type="text" name="date_from" id="date_from" class="datepicker" value="$date_from" autocomplete="off">
            </div>
            <div class="input-prepend">
              <span class="add-on">To</span>
              <input type="text" name="date_to" id="date_to" class="datepicker" value="$date_to" autocomplete="off">
            </div>
          </div>
        </td>
      </tr>    
      <tr>
        <td>Suspended users</td>
        <td>
          <select name='suspendedusers'>
            <option value='none' selected='selected'>$option_exclude</option>
            <option value="all">$option_include</option>
            <option value="only">$option_only</option>
          </select>
        </td>
      </tr>        
      <tr>
        <td></td>
        <td><input type='submit' name='sub' value='Go' class='btn'></td>
      </tr>
    </table>
	</form>
  <script>
    $(document).ready(function(){
      $('.chzn-select').chosen({search_contains: true});
      $('.datepicker').datepicker({dateFormat: 'dd/mm/yy'});
    })
  </script>
EOT;
  echo $form;
  echo $OUTPUT->footer();
  exit();
}
$facetoface = $DB->get_record('facetoface', array('id' => $faceid));
$courseid = $facetoface->course;

raise_memory_limit(MEMORY_EXTRA);

$user_fields = array(
  'firstname'         => 'First name',
  'lastname'          => 'Last name',
  'firstnamephonetic' => 'Preferred first name'
);

$profile_fields = array(
  'PositionTitle' => null,
  'Location'      => 'Worker Location',
  'ReferenceID'   => null,
  'WorkerManager' => null
);

$session_fields = array(
  'session_dates' => 'Session date(s)',
  'location'      => null,
  'room'          => null,
  'enrol_status'  => 'Enrolment Status'
);

$sql_condition = '';

if (!is_siteadmin($USER->id) && !$has_capability) {
  $sql_condition = 'AND ue.userid in (SELECT hu.user_id from mdl_hierarchy_user hu '
    . 'inner join mdl_hierarchy_node hn on hu.node_id=hn.id '
    . 'where hn.parent_node_id in('
    . 'select hu2.node_id from mdl_hierarchy_user as hu2 where hu2.user_id=' . $USER->id . '))';
}

$user_condition = '';
$suspended_filter = $option_include;
switch ($suspended) {
  case 'none':
    $user_condition = 'AND u.deleted = 0 AND u.suspended = 0';
    $suspended_filter = $option_exclude;
    break;
  case 'only':
    $user_condition = 'AND (u.deleted = 1 OR u.suspended = 1)';
    $suspended_filter = $option_only;
    break;
}
$filters['suspended_filter'] = $suspended_filter;

$sql = <<< EOT
	SELECT 
		DISTINCT u.id, 
		u.firstname, 
		u.lastname,
		u.firstnamephonetic
	FROM 
		{user_enrolments} as ue 
		JOIN {enrol} e ON e.id = ue.enrolid 
		JOIN {user} u ON ue.userid = u.id
		AND e.status = 0 
    $user_condition
	WHERE 
		courseid = ?
		$sql_condition
  ORDER BY 
    u.firstname, 
    u.lastname
EOT;
//error_log($sql);

$enrolled_users = $DB->get_records_sql($sql, array($courseid));

//error_log($sql);
//error_log(var_export($enrolled_users, true));

ma_set_user_profile_fields($profile_fields);
ma_set_user_session_fields($session_fields);

$start_dates = array();
$date_filter = array();
if (isset($date_from) && !empty($date_from)) {
  $start_dates['from'] = strtotime(str_replace('/', '-', $date_from));
  $date_filter[] = ' >= ' . $date_from;
}
if (isset($date_to) && !empty($date_to)) {
  $start_dates['to'] = strtotime(str_replace('/', '-', $date_to) . ' 23:59:59');
  $date_filter[] = ' <= ' . $date_to;
}
$filters['date_filter'] = $date_filter;

$sessions = facetoface_get_sessions($faceid);

if ($enrolled_users) {
  $rows = array();
  foreach ($enrolled_users as $userid => $enrolled_user) {

    $profile_data = ma_get_user_profile_data($userid, array_keys($profile_fields));
    
    // user fields columns
    $row = array();
    $row['user_url'] = $CFG->wwwroot . '/user/view.php?id=' . $enrolled_user->id;
    foreach ($user_fields as $shortname => $name) {
      $row[$name] = $enrolled_user->$shortname;
    }
    
    // user profile columns
    foreach ($profile_fields as $shortname => $name) {
      $row[$name] = '';
      if (isset($profile_data->$shortname)) {
        $row[$name] = $profile_data->$shortname;
      }
    }

    // user session columns
    //$user_sessions = ma_get_user_sessions($userid);
    $user_sessions = ma_get_user_session_data($faceid, $userid);

    
    if (!empty($start_dates) && empty($user_sessions)) {
      continue;
    }
    
    $display = true;
    if (!empty($start_dates)) {
      $display = false;
    }
    
    // 
    /**
     * expect $user_sessions contains:
     * array(
     *   sessionid => array(
     *     location => v1,
     *     venue => v2,
     *     room => v3,
     *     enrol_status => '' or 'Not Attended' or 'Attended'
     *   ),
     *   ....
     * )
     * 
     */
    if ($user_sessions) {
      
//      error_log(var_export($user_sessions, true));
      //continue;
      
      foreach ($user_sessions as $sessionid => $user_session) {
        if (!empty($user_session['enrol_status'])) {
          $dates = array();
          if (isset($sessions[$sessionid])) {
            foreach ($sessions[$sessionid]->sessiondates as $date) {
              
              if (isset($start_dates['from'])) {
                if (isset($start_dates['to'])) {
                  if ($date->timestart >= $start_dates['from'] && 
                      $date->timestart <= $start_dates['to']) {
                    $display = true;
                  }
                }
                else if ($date->timestart >= $start_dates['from']) {
                  $display = true;
                }                
              }
              else if (isset($start_dates['to']) && $date->timestart <= $start_dates['to']) {
                $display = true;
              }
              $dates[] = sprintf("%s - %s", 
                    date("d/m/Y h:iA ", $date->timestart),
                    date("d/m/Y h:iA", $date->timefinish));
            }
            $user_session['session_dates'] = implode('<br/>', $dates);
//            error_log(var_export($user_session, true));
          }                    
        }
        if (!$display) {
          continue;
        }
        $session_data = array();
        foreach ($session_fields as $key => $val) {
          $session_data[$val] = isset($user_session[$key]) ? $user_session[$key] : '';
        }
//        $session_data['Session Id'] = $sessionid;
        $new_row = array_merge($row, $session_data); 
        $rows[] = $new_row;
      }
    }
    else {
      $rows[] = $row;
    }
  
  }
//  error_log(var_export($rows, true));
}
//die();
$headers = array_merge(
  array_values($user_fields), array_values($profile_fields), array_values($session_fields)
);

if ($report_type === 'HTML') {
  displayHTML($facetoface, $heading, $headers, $rows, $filters);
}
else {
  exportData($facetoface, $headers, $rows);
}

function exportData($f2f, $headers, $rows) {
  $file = fopen('php://output', 'w');
  $filename = "report_" . str_replace(" ", "_", $f2f->name) . "_" . date('Y_m_d') . ".csv";
  header("Content-type: application/csv");
  header("Content-Disposition: attachment; filename=" . $filename);
  header("Pragma: no-cache");
  header("Expires: 0");
  fputcsv($file, $headers);
  foreach ($rows as $row) {
    $line = array();
    foreach ($headers as $header) {
      $data = isset($row[$header]) ? $row[$header] : '';
      if ($header === 'Session date(s)') {
        $data = str_replace('<br/>', "\n", $data);
      }
      $line[] = $data;
    }
    fputcsv($file, $line);
  }
}

function displayHTML($f2f, $heading, $headers, $rows, $filters) { 
  global $CFG, $PAGE, $OUTPUT;
  
  $libPath = '/lib/mindatlas';
  $PAGE->requires->js($libPath  . '/jquery/jquery.min.js', true);
  $PAGE->requires->js($libPath  . '/jquery/ui/jquery-ui.min.js', true);
  $PAGE->requires->css($libPath . '/jquery/ui/jquery-ui.min.css');
  $PAGE->requires->js('/mod/facetoface/css/jquery.tablesorter.min.js', true);
  $PAGE->requires->css('/mod/facetoface/css/tablesorter.css');
  $export_url = '<p class="pull-right"><a href="enrolmentregistration.php?type=CSV&sub=1&faceid=' 
              . $f2f->id 
              . '" class="export btn"> Export CSV</a></p>';
  $course = get_course($f2f->course);
  
  echo $OUTPUT->header();
  echo $OUTPUT->heading($heading . ' report');
  // echo '<p>' . get_string('sorting_tip', 'block_reporting') . '</p>';
  echo $export_url;
  echo "<p>Date of report: " . date('d/m/Y') . "</p>";
  echo '<p>' . get_string('course') . ' \ ' . get_string('activity') . ': ' . $course->fullname . ' \ ' . $f2f->name . '</p>';
  if (isset($filters['date_filter']) && !empty($filters['date_filter'])) {
    echo '<p>Session Start Date ';
    echo implode(' AND ', $filters['date_filter']);
    echo '</p>';
  }
  echo '<p>Suspended users: ' . $filters['suspended_filter'] . '</p>';
  if(is_array($rows)){
    echo '<p>Total records: ' . count($rows) . '</p>';
  }else{
    echo '<p>Total records: 0 </p>';
  }
  echo '<table id="report" class="tablesorter">';
  echo '<thead>';
  echo '<tr>'; 

  foreach ($headers as $header) {
    echo '<th>' . $header . '</th>';
  }
  echo '</tr>';
  echo '</thead>';
  echo '<tbody>';
  
//  error_log(var_export($rows, true));
  
  if (is_array($rows) && count($rows) > 0) {
    foreach ($rows as $row) {
      echo '<tr>';
      foreach ($headers as $header) {
        $data = isset($row[$header]) ? $row[$header] : '';
        if ($header === 'First name' || $header === 'Last name') {
          $data = '<a href="' . $row['user_url'] .'">' . $row[$header] . '</a>';
        }
        echo '<td style="border-width:1px;">' . $data . '</td>';
      }
      echo '</tr>';
    }
  }
  echo '</tbody>';
  echo '</table>';
  echo $export_url;
  $script = <<< EOT
<script type="text/javascript">
  $(document).ready(function(){
    $.tablesorter.addParser({ 
      // set a unique id 
      id: 'session_date',
      is: function(s) { 
        // return false so this parser is not auto detected 
        return false; 
      }, 
      format: function(s) {
        // format your data for normalization 
        if (s != '') {
          var m = s.match(/^([0-9]{2})\/([0-9]{2})\/([0-9]{4}) ([0-9]{2})\:([0-9]{2})(AM|PM)/);
          var m1 = '';
          if (m) {
            if (m[6] === 'PM' && m[4] < 12) {
              m[4] = parseInt(m[4]) + 12;
            }
            else if (m[6] == 'AM' && m[4] == 12) {
              m[4] = parseInt(m[4]) - 12;
            }
            var m1 = m[3] + '-' + m[2] + '-' + m[1]
                   + 'T' + m[4] + ':' + m[5] + ':' + '00';
            return $.tablesorter.formatFloat(new Date(m1).getTime());
          }
        }
        return '';
      }, 
      // set type, either numeric or text 
      type: 'numeric' 
    }); 
    $("#report").tablesorter({
      headers: {
        7: {
          sorter: 'session_date'
        }
      }
    });
    
  });
</script>
EOT;
  
  echo $OUTPUT->footer();
  echo $script;
}

/**
 -- OLD CODE. Can be removed after testing --
if (!empty($rs)) {
  ob_end_clean();
  if ($report_type == 'HTML') {
    $PAGE->requires->css('/mod/facetoface/css/tablesorter.css');
    $PAGE->requires->css('/mod/facetoface/css/chosen.css');

    echo $OUTPUT->header();
  }
  //Get default headers
  $headers = array();
  foreach ($default_fields as $key => $name) {
    $headers [] = $name;
  }
  //User profile headers
  $rs2 = $DB->get_records('user_info_field', array(), 'sortorder ASC');
  $profile_keys = array();
  $profile_types = array();
  foreach ($rs2 as $row2) {
    if (!in_array($row2->shortname, $include_user_fields))
      continue;
    $profile_keys [] = $row2->id;
    $profile_types [$row2->id] = $row2->datatype;
    $header_name = $row2->name;
    if ($row2->name == 'Location') {
      $header_name = 'Worker Location';
    }
    $headers [] = $header_name;
  }
  $headers [] = 'Session date';
  $headers [] = 'Location';
  $headers [] = 'Facilitator';
  $headers [] = 'Enrolment Status';
  //End of headers
  $file = fopen('php://output', 'w');
  if ($report_type == 'CSV') {
    $filename = "report_" . str_replace(" ", "_", $facetoface->name) . "_" . date('Y_m_d') . ".csv";
    header("Content-type: application/csv");
    header("Content-Disposition: attachment; filename=" . $filename);
    header("Pragma: no-cache");
    header("Expires: 0");
    fputcsv($file, $headers);
    fputcsv($file, $body_contents);
  } elseif ($report_type == 'HTML') {

    echo $OUTPUT->heading($heading);
    // echo '<p>' . get_string('sorting_tip', 'block_reporting') . '</p>';
    echo '<p class="pull-right"><a href="enrolmentregistration.php?type=CSV&sub=1&faceid=' . $faceid . '" class="export btn"> Export CSV</a></p>';
    echo "<p>Date of report: " . date('d/m/Y') . "</p>";
    echo "<p>Face to face: " . $facetoface->name . "</p>";
    echo '<table id="report" class="tablesorter">';
    echo '<thead>';
    echo '<tr>';
    foreach ($headers as $header) {
      echo '<th>' . $header . '</th>';
    }
    echo '</tr>';
    echo '</thead>';
  }
  

  flush();



  // var_dump($profile_types);
  $num_sessions = $DB->get_records('facetoface_sessions', array('facetoface' => $faceid));
  // echo '<pre>'.print_r($num_sessions,1).'</pre>';
  foreach ($rs as $row) {
    // $sessions_row = $DB->get_records_sql
    foreach ($num_sessions as $session) {
      // echo $faceid;
      // echo '<pre>'.print_r($row,1).'</pre>';
      $body_contents = array();
      $user = $DB->get_record('user', array('id' => $row->userid));
      if (!$user->deleted):
        foreach ($default_fields as $key => $name) {
          if (in_array($key, array('firstname', 'lastname'))) {
            $body_contents[] = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $row->userid . '">' . $user->$key . '</a>';
          } else {
            $body_contents [] = $user->$key;
          }
          // $body_contents [] = $user->$key;
        }
        $profile_data = array();
        $row_data = $DB->get_records('user_info_data', array('userid' => $user->id));
        foreach ($row_data as $row_data) {
          if (!isset($profile_types[$row_data->fieldid]))
            continue;
          switch ($profile_types[$row_data->fieldid]) {
            case 'datetime':
              $profile_data [$row_data->fieldid] = ($row_data->data == 0 || $row_data->data == "" || is_null($row_data->data)) ? "" : date('d/m/Y', $row_data->data);
              break;
            case 'checkbox':
              $profile_data [$row_data->fieldid] = ($row_data->data == 1) ? "Yes" : "No";
              break;
            default:
              $profile_data [$row_data->fieldid] = ($row_data->data == "" || is_null($row_data->data)) ? "" : $row_data->data;
              break;
          }
        }
        foreach ($profile_keys as $profileid) {
          $body_contents [] = isset($profile_data[$profileid]) ? $profile_data[$profileid] : "";
        }

        error_log('$user->id ' . $user->id);
        error_log('$session->id ' . $session->id);

        //Check if user attend to the facetoface session or not
        $f2f_user_details = get_f2f_detail_user($user->id, $session->id, $courseid);

// error_log('f2f_user_details ' . var_export($f2f_user_details, true));
        // echo '<pre>'.print_r($f2f_user_details,1).'</pre>';
        $body_contents [] = ($session->datetimeknown) ? $f2f_user_details['session_date'] : get_string('wait-listed', 'facetoface');
        $body_contents [] = $f2f_user_details['location'];
        $body_contents [] = $f2f_user_details['facilitator'];
        $body_contents [] = $f2f_user_details['attend'];
      endif;
      // $body_contents [] = '';
      // $body_contents [] = '';
      // $body_contents [] = '';
      // $body_contents [] = 'N';

      if ($report_type == 'HTML') {
        echo '<tr>';
        foreach ($body_contents as $content) {
          echo '<td style="border-width:1px;">' . $content . '</td>';
        }
        echo '</tr>';
      } elseif ($report_type == 'CSV') {
        // $body_contents[0] = strip_tags($body_contents[0]);
        array_walk($body_contents, function(&$val, $key) {
          $val = strip_tags($val);
        });
        fputcsv($file, $body_contents);
      }
    }
  }
  if ($report_type == 'HTML') {
    echo '</table>';
    echo '<div class="pull-right"><a href="enrolmentregistration.php?type=CSV&sub=1&faceid=' . $faceid . '" class="export btn">Export CSV</a></div>';
    echo $OUTPUT->footer();
    echo '<script src="' . $CFG->wwwroot . '/mod/facetoface/css/jquery-1.12.2.min.js"></script>
				<script src="' . $CFG->wwwroot . '/mod/facetoface/css/jquery.tablesorter.min.js"></script>
				<script>
					$(document).ready(function(){
						$("#report").tablesorter({
						    // headers:
						    //     {
						    //         6: { sorter: "customDate" },
						    //         5: { sorter: "customDate" },
						    //     },
						    // widgets: ["zebra"]
						});
					});
					// $.tablesorter.addParser({
					//         id: "customDate",
					//         is: function(s) {
					//         // return s.match(new RegExp(/^[A-Za-z]{3,10}\.? [0-9]{1,2}, [0-9]{4}|\'?[0-9]{2}$/));
					//             return false;
					//         },
					//         format: function(s) {
					//             var date = s.split("/");
					//             return $.tablesorter.formatFloat(new Date(date[2], date[1], date[0]).getTime());
					//         },
					//         type: "numeric"
					// });
				</script>';
  }
} else {
  redirect($baseurl, 'Could not find any result in the face to face <strong>' . $facetoface->name . '</strong>', '', core\output\notification::NOTIFY_ERROR);
}
*/
