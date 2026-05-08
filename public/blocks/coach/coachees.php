<?php 
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('lib.php');
global $DB;
if(!isset($userid)) $userid = $USER->id;
$context_system = context_system::instance();
$PAGE->set_context($context_system);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('coachees','block_coach'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/blocks/coach/coachees.php');
if ($CFG->forcelogin) {
    require_login();
}
$required_capabilities = array(
  'block/coach:assign_coaches_to_users',
  'block/coach:manage_coaches'
);
$no_permission = true;
foreach ($required_capabilities as $required_capability) {
  if (has_capability($required_capability, $context_system)) {
    $no_permission = false;
    break;
  }
}
if ($no_permission) {
  throw new required_capability_exception($context_system, $required_capability, 'nopermissions', '');
}
$html="";
$html_lib="<script src='js/jquery-1.12.2.min.js'></script>
  <script src='js/jquery.tablesorter.js'></script>

  <link rel='stylesheet' type='text/css' href='js/style.css' />";

if(isset($_GET['u'])) $html.=get_string('save:success','block_coach');
if(isset($_GET['e'])) $html.=get_string('save:error','block_coach');
if(isset($_GET['d'])) $html.=get_string('delete:success','block_coach');

$html.=get_string('coachs:title','block_coach');

  $html.="<table class='tablesorter' id='report'>";
  $html.="<thead><tr>";
  $html.="<th>".get_string('coachs:name','block_coach')."</th>";
  $html.="<th>".get_string('coachs:email','block_coach')."</th>";
  $html.="<th>".get_string('coachs:size','block_coach')."</th>";
  $html.="<th>Action</th>";
  $html.="</tr></thead>";
  $html.="<tbody>";
//EDIT HERE, LIMIT FOR COACHS ONLY
 $context_system_id = get_context_systemid();
 $coach_role_id = get_coach_roleid();

$sql="SELECT u.id, u.firstname,u.lastname,u.email 
 from mdl_user as u inner join mdl_coach c on u.id=c.userid WHERE u.deleted=0 and is_student=".NOT_STUDENT_OR_SUPERVISOR." ORDER BY u.firstname,u.lastname";
$rs = $DB->get_records_sql($sql);
if(!empty($rs)){
  foreach ($rs as $row) {
  	$num_users = $DB->count_records('coachees',array('coachid'=>$row->id,'is_student'=>NOT_STUDENT_OR_SUPERVISOR));
    $fullname = ucfirst($row->firstname)." ".ucfirst($row->lastname);
    $html.="<tr>";
    $html.="<td>".$fullname."</td>";
    $html.="<td>".$row->email."</td>";
    $html.="<td>".$num_users."</td>";
    $html.="<td>";
    if (has_capability('block/coach:assign_coaches_to_users', $context_system)) {
      $html .= "<a href='coachees_add.php?id=".$row->id."'><img src='js/users.png' title='".get_string('coachs:managecoachees','block_coach')."'/> </a>";
    }
    if (has_capability('block/coach:manage_coaches', $context_system)) {
      $html .= "<a href='coach_delete.php?id=".$row->id."'><img src='js/delete.png'> </a>";
    }
    $html .= "</td>";
    $html.="</tr>";
  }
}
$html.="</tbody></table>";
if (has_capability('block/coach:manage_coaches', $context_system)) {
  $html.="<a href='".$CFG->wwwroot."/blocks/coach/coach_add.php' class='btn'>".get_string('coachs:addnew','block_coach')."</a>";
}
$html.="<br><br>";

echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();
echo $html_lib;
?>
<script>
$(document).ready(function () {
    $("#report").tablesorter({
  headers:
    {
    },
    widgets: ['zebra']
    });
});
</script>