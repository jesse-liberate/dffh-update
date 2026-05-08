<?php 
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('lib.php');
global $DB;
if(!isset($userid)) $userid = $USER->id;
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('supervisors','block_coach'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/blocks/coach/supervisor.php');
if ($CFG->forcelogin) {
    require_login();
}
if(!is_siteadmin($userid)) {
  echo get_string('notallowtoaccess','block_coach');
  exit();
}
admin_externalpage_setup('tool_assign_supervisor');

$html="";
$html_lib="<script src='js/jquery-1.12.2.min.js'></script>
  <script src='js/jquery.tablesorter.js'></script>

  <link rel='stylesheet' type='text/css' href='js/style.css' />";

if(isset($_GET['u'])) $html.=get_string('save:success','block_coach');
if(isset($_GET['e'])) $html.=get_string('save:error','block_coach');
if(isset($_GET['d'])) $html.=get_string('delete:success','block_coach');

$html.=get_string('managesupervisor:title','block_coach');

  $html.="<table class='tablesorter' id='report'>";
  $html.="<thead><tr>";
  $html.="<th rowspan='2'>".get_string('supervisor:name','block_coach')."</th>";
  $html.="<th rowspan='2'>".get_string('supervisor:email','block_coach')."</th>";
  $html.="<th colspan='2'>".get_string('student','block_coach')."</th>";
  $html.="<th colspan='2'>".get_string('graduatestudent','block_coach')."</th>";
  $html.="<th rowspan='2'>Action</th>";
  $html.="</tr><tr>";
  $html.="<th>".get_string('number_student','block_coach')."</th>";
  $html.="<th>".get_string('add_remove','block_coach')."</th>";
  $html.="<th>".get_string('number_graduatestudent','block_coach')."</th>";
  $html.="<th>".get_string('add_remove','block_coach')."</th>";
  $html.="</tr></thead>";
  $html.="<tbody>";
//EDIT HERE, LIMIT FOR COACHS ONLY
 $context_system_id = get_context_systemid();
 $supervisor_role_id = get_supervisor_roleid();

$sql="SELECT u.id, u.firstname,u.lastname,u.email 
 from mdl_user as u inner join mdl_coach c on u.id=c.userid WHERE u.deleted=0 and is_student=".STUDENT_OR_SUPERVISOR." ORDER BY u.firstname,u.lastname";
$rs = $DB->get_records_sql($sql);
if(!empty($rs)){
  foreach ($rs as $row) {
    $num_students = $DB->count_records('coachees',array('coachid'=>$row->id,'is_student'=>STUDENT_OR_SUPERVISOR));
    $num_graduate = $DB->count_records('coachees',array('coachid'=>$row->id,'is_student'=>GRADUTE_STUDENT));

    $fullname = ucfirst($row->firstname)." ".ucfirst($row->lastname);
    $html.="<tr>";
    $html.="<td><a href='".$CFG->wwwroot."/user/profile.php?id=".$row->id."'>".$fullname."</td>";
    $html.="<td>".$row->email."</td>";
    //Students
    $html.="<td>".$num_students."</td>";
    $html.="<td><a href='supervisor_add.php?id=".$row->id."'> <img src='js/users.png' width='16px' title='".get_string('student:mananage','block_coach')."'/> </a></td>";
    //Graduate students
    $html.="<td>".$num_graduate."</td>";
    $html.="<td><a href='graduate_add.php?id=".$row->id."'> <img src='js/graduate_small.png' width='16px' title='".get_string('studentgradute:mananage','block_coach')."'/> </a></td>";
    $html.="<td>
        <a href='supervisor_delete.php?id=".$row->id."' title='".get_string('removepreceptor','block_coach')."'><img src='js/delete.png'> </a>
    </td>";
    $html.="</tr>";
  }
}
$html.="</tbody></table>";
$html.="<a href='".$CFG->wwwroot."/blocks/coach/supervisor_new.php' class='btn'>".get_string('supervisor:addnew','block_coach')."</a>";
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