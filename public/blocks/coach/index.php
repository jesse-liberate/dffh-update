<?php
require_once('../../config.php');
include("lib.php");
global $USER, $DB;
if(!isset($userid)) $userid = $USER->id;
require_login(0, false);
$context_system = context_system::instance();
$PAGE->set_context($context_system);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($SITE->fullname);
$PAGE->set_url('/blocks/coach/index.php');
$PAGE->requires->css('/blocks/learningplan/css/style.css');
//site admin and manager can add learning plan
$can_addlearningplan = has_capability('block/coach:addlearningplan',$context_system);
$view_teammember_learningplan = has_capability('block/coach:viewteamlearningplan',$context_system);
$is_student = false;
$coachee_name = 'coachee';
if(!is_siteadmin($USER->id)){
  $is_student = has_capability('block/coach:isstudent',$context_system);
  if($is_student) $coachee_name = 'student';
  $_SESSION['is_student_role'] = $is_student;
}
$html="<div id='coach_main_page'>";
$html_lib = "<script src='js/jquery-1.12.2.min.js'></script>
  <script src='js/jquery.tablesorter.js'></script>
  <script src='resource/chosen.jquery.js' type='text/javascript'></script>
  <link rel='stylesheet' href='resource/chosen.css'>
  <link rel='stylesheet' href='js/style.css'>";

if(isset($_GET['l_p'])){
  $html.=get_string('learningplan:updatesuccess','block_coach');
}
$type="";
$tabs = array('tab1'=>'','tab2'=>'','tab3'=>'');
if(isset($_GET['type'])) $type = $_GET['type'];
if(isset($_POST)){
  if(isset($_POST['coachee'])) $_SESSION['coacheeid'] = $_POST['coachee'];
  if(isset($_POST['type'])){
    $type=$_POST['type'];
    if($type=='discuss') update_discussion_message($_POST);
    //Resource might return some errors due to upload some unexpected files 
    // var_dump($_POST);
    // var_dump($_FILES);
    // die();
    if($type=='resource') $html.=update_coach_resource($_POST,$_FILES,$is_student);
  }
}
//Select the tab to be default active one
switch ($type) {
  case 'discuss': $tabs['tab3'] ='checked'; break;
  case 'resource': $tabs['tab2'] ='checked'; break;
  default: 
  if(!$is_student) $tabs['tab1'] ='checked';
  else $tabs['tab2'] ='checked';
  break;
}

if($is_student) $html.=get_string('coach:studenttitle','block_coach');
else $html.=get_string('coach:developmenttitle','block_coach');

if($view_teammember_learningplan){
  $coachee_options = get_team_member_user_options($is_student);
  $html.=$coachee_options;
  if(!isset($_SESSION['coacheeid'])) $html.=get_string('coachee:selectwarning','block_coach',$coachee_name);
}

$higher_pos_id = get_coach_supervisor_userid();
if($higher_pos_id){
  $higher_user = $DB->get_record('user',array('id'=>$higher_pos_id));
  $fullname = ucfirst($higher_user->firstname)." ".ucfirst($higher_user->lastname);
  if($is_student){
    $html.=get_string('supervisor-student:title','block_coach',$fullname);
  }else{ 
    $html.=get_string('coach-coachee:title','block_coach',$fullname);
  }
}


// if(isset($_SESSION['is_student_role'])&&($_SESSION['is_student_role'])) $tab_learning_plan = get_string('tab:studentplan','block_coach');
$tab_learning_plan = get_string('tab:learningplan','block_coach');

$learningplan = get_coachee_learningplan();
$resource_html = get_resources_html();
$discussion = get_discussion_html($is_student);
if(!$is_student){
  $html.='<input id="tab1" type="radio" name="tabs" class="hidden" '.$tabs['tab1'].'>
  <label for="tab1" id="label_tab1"><a href="index.php">'.$tab_learning_plan.'</a></label>';
}
$html.='
  <input id="tab2" type="radio" name="tabs" class="hidden" '.$tabs['tab2'].'>
  <label for="tab2" id="label_tab2"><a href="index.php?type=resource">'.get_string('tab:resource','block_coach').'</a></label>
    
  <input id="tab3" type="radio" name="tabs" class="hidden" '.$tabs['tab3'].'>
  <label for="tab3" id="label_tab3"><a href="index.php?type=discuss">'.get_string('tab:discuss','block_coach').'</a></label>';
if(!$is_student){
  $html.='<section id="content1">'.$learningplan.'</section>';   
}
$html.='<section id="content2">'.$resource_html.'</section>
  <section id="content3">'.$discussion.'</section>';

$html.="<div id='postto_popup'></div>";
$html.="<div id='postto_background'></div>";
$html.="<div id='loading_icon'><img src='".$CFG->wwwroot."/blocks/coach/js/loading.gif' width='30'></div>";
$html.="<input type='hidden' id='my_resource' value='0'>";
$resource_detail = "<div id='confirm_delete_resource'></div>";
$html.="<div id='resource_delete_popup'>".get_string('confirm','block_coach')."
<div>".get_string('resource:delete:confirm','block_coach',$resource_detail)."</div>
<p><input type='button' id='confirm_delete_resource_btn' value='Confirm'> 
<input type='button' id='close_btn_resource' value='Cancel'>
<a href='index.php' class='btn' id='close_resource'>Close</a>
</p>
<div id='delete_resource_msg'></div>
</div>";
$html.="</div>"; // end of the all contents;
echo $OUTPUT->header();
// Start showing the form
echo $html;
echo $html_lib;
echo $OUTPUT->footer();
?>
<script type="text/javascript">
var config = {
    '.chosen-select'           : {},
    '.chosen-select-deselect'  : {allow_single_deselect:true},
    '.chosen-select-no-single' : {disable_search_threshold:10},
    '.chosen-select-no-results': {no_results_text:'Could not find any!'},
    '.chosen-select-width'     : {width:"95%"}
}
for (var selector in config) {
    $(selector).chosen(config[selector]);
}
(function($){
$('#tab3').on('change',function(){
  $('#discussion_content').scrollTop($('#discussion_content')[0].scrollHeight);
});

$(document).ready(function(){
  $('#discussion_content').scrollTop($('#discussion_content')[0].scrollHeight);
  //Resource select

   $('#resource_select').on('click', function(){
      var resourceid = $('#resource_select option:selected').val();
      var url = "<?php echo $CFG->wwwroot;?>/blocks/coach/get_resource.php?id=" + resourceid;
      console.log('Resource has been selected');
      $('#loading_icon').show();
      $('#resource_remove_msg').html('');
      //Reset editing resources
      $('#attachment_edit1').html('');
      $('#attachment_edit2').html('');
      $('#attachment_edit3').html('');
      $('#attachmentedit1').val('');
      $('#attachmentedit2').val('');
      $('#attachmentedit3').val('');
      //End of reset
      $.get(url, function( data ) {
        if(data!=""){
          $('#postto_popup').html(data);
          $('#postto_popup').show();
          $('#postto_background').show();
          $('#my_resource').val('0');
        }else{
          $('#my_resource').val('1');
          //Edit the resource in here
      $.ajax({
          type: "GET",
          url: "<?php echo $CFG->wwwroot;?>/blocks/coach/resource_edit.php",
          data: 'id=' + resourceid,
          dataType: "json", // Set the data type so jQuery can parse it for you
          success: function (data) {
              console.log(data['name']);
              $('.resource_upload #resourcename').val(data['name']);
              $('.resource_upload #resourcedesc').val(data['description']);
              $('.resource_upload #resourcelink').val(data['link']);
              $('.resource_upload #eid').val(data['entry']);
              $('.attachment_edit').css('display', 'inline-block');
              var num_attach = parseInt(data['num_attached']);
              if(num_attach>0){
                for(i=1;i<=num_attach;i++){
                  var attach_link = "<a target='_blank' href='gettype.php?id="+ data['id'+i] + data['attachment'+i] +"'> "+ data['attachment_fname'+i] +" </a>";
                  $('#attachmentedit'+i).val(data['id'+i]);
                  $('#attachment_edit'+i).html(attach_link);
                }
              }
              $('#resource_msg').show();
          }
      });
        }
        $('#loading_icon').hide();
      });
    });
  
  //Default to click to hide popup
  $("#postto_background, #close_btn_resource").click(function() {
      $("#postto_background").hide();
      $("#postto_popup").hide();
      $("#resource_delete_popup").hide();
  });
  //Remove button
  $('#resource_remove').click(function(){
    if($('#my_resource').val()==1){
      $('#confirm_delete_resource').html($('#resource_select option:selected').text());
        $('#resource_delete_popup').show();
        $('#postto_background').show();
    }else{
       $('#resource_remove_msg').html('<div class="alert alert-error">Can not remove this resource</div>');
    }
  });
  //confirm to remove resource
  $("#confirm_delete_resource_btn").click(function(){
    var resourceid = $('#resource_select option:selected').val();
  var url = "<?php echo $CFG->wwwroot;?>/blocks/coach/resource_delete.php?id=" + resourceid;
  $('#loading_icon').show();
  $.get(url, function( data ) {
    $('#delete_resource_msg').html(data);
    $("#confirm_delete_resource_btn").hide();
    $('#loading_icon').hide();
    $('#close_btn_resource').hide();
    $('#close_resource').show();
  });
  });
});
})(jQuery);


</script>

<style type="text/css">
.attachment_edit, #confirm_delete_resource{
  display: inline-block;
}
#close_resource,#resource_datetime,.attachment_edit,#resource_msg{
  display: none;
}
#resource_delete_popup p{
  margin-top: 20px;
  text-align: center;
}
#resource_remove_msg{
  display: inline-block;
  margin-left: 5px;
}
#loading_icon{
  display: none;
  top:50%;
  left:50%;
  position: fixed;
  z-index: 5000; 
}
#postto_popup img{
  max-width: 350px;
}
#postto_popup li{
  margin-bottom: 10px;
}
.resource_manage select{
    width:100%;
}
.resource_upload input[type=text],textarea{
    width:80%;
}

.resource .resource_upload,.resource .resource_manage{
  display: inline-block;
  vertical-align: top;
}
.resource .resource_manage{
  width: 30%;
}
.resource .resource_upload{
  margin-left: 20px;
  width: 66%;
}
#discussion_content{
    width: 95%;
    height: 350px;
    overflow: scroll;
    padding-bottom: 20px
}
.discuss_line .discuss_header{
    font-weight: bold;
    vertical-align: top;
    min-width: 230px;
}
.discuss_line .content, .discuss_line .discuss_date, .discuss_line .discuss_header{
  display: inline-block;
}
.discuss_line .discuss_date{
  font-style: italic;
  margin-left: 5px;
}
.coachee_table{
  margin-bottom: 30px;
}
.discussion .message,.discussion .action{
  display: inline-block;
  vertical-align: top;
}
.discussion .message{
  width: 70%;
}
.discussion textarea{
  width: 98%;
  height: 80px;
}

#coach_main_page h1 {
  padding: 50px 0;
  font-weight: 400;
  text-align: center;
}

#coach_main_page p {
  margin: 0 0 20px;
  line-height: 1.5;
}

#coach_main_page section {
  display: none;
  padding: 20px 10px;
  border-top: 1px solid #ddd;
}

.hidden{
  display: none;
}

#coach_main_page label {
  display: inline-block;
  margin: 0 0 -1px;
  padding: 15px 25px;
  font-weight: 600;
  text-align: center;
  color: #bbb;
  border: 1px solid transparent;
}

#coach_main_page label:before {
  font-weight: normal;
  margin-right: 10px;
}


#coach_main_page label:hover {
  color: #888;
  cursor: pointer;
}

#coach_main_page input:checked + label {
  color: #555;
  background-color: #e8e7e7;
  padding-left: 5px;
}

#coach_main_page #tab1:checked ~ #content1,
#coach_main_page #tab2:checked ~ #content2,
#coach_main_page #tab3:checked ~ #content3{
  display: block;
  background-color: #e8e7e7;
}

@media screen and (max-width: 650px) {
#coach_main_page  label {
    font-size: 10pt;
  }
#coach_main_page  label:before {
    margin: 0;
    font-size: 10pt;
  }
}

@media screen and (max-width: 400px) {
#coach_main_page  label {
    padding: 10px;
  }
}

</style>