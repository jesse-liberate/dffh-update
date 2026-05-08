<?php 
require('../../../config.php');
require_once $CFG->libdir.'/adminlib.php';
require_once('lib.php');
global $DB;

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('visualization','tool_hierarchy'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/admin/tool/hierarchy/visualization.php');
admin_externalpage_setup('hierarchy_visualization');
$PAGE->requires->jquery();
 $PAGE->requires->jquery_plugin('ui');
   $PAGE->requires->jquery_plugin('ui-css');
if ($CFG->forcelogin) {
    require_login(); 
}
$PAGE->requires->js('/admin/tool/hierarchy/js/coach.js');

echo $OUTPUT->header();

$recursive = false;
// For submit the form
if(isset($_POST['report'])){
  if((isset($_POST['recursive']))&&($_POST['recursive']=='true')){
    $recursive = true;
  }
  $_SESSION['recursive'] = $recursive;
}else if(isset($_SESSION['recursive'])) $recursive = $_SESSION['recursive'];
if(!is_hierarchyfeature_installed()) {
  echo get_string('hierarchynotinstall','tool_hierarchy');
  exit();
}
$hierarchy_nodes = get_all_hierarchy_tree($recursive);
$root_node = findrootnode();

// ===================== sorting firstname, lastname, node
$str_sort="";
if(isset($_GET['sort'])){
  $dir = ($_GET['dir']=="ASC") ? "dir=ASC": "dir=DESC";
  $str_sort = "&sort=".$_GET['sort']."&".$dir;
  $_SESSION['currentnode'] = $_GET['n'];
 }
//---------------------

 $currentnode = $root_node->id;
  if(isset($_POST['selectednode'])) {
    $currentnode = $_SESSION['currentnode'] = $_POST['selectednode'];
 }
 if(!isset($_SESSION['currentnode'])){
   $_SESSION['currentnode']=$root_node->id;
 } else { 
  if($_SESSION['currentnode']!="") $currentnode = $_SESSION['currentnode'];
}

if(isset($_GET['update'])){
  if($_SESSION['new_node']!="") $currentnode = $_SESSION['new_node'];
}

echo get_string('hierarchytitle','tool_hierarchy');
?>
  <form action="visualization.php" method="POST" name="frm" id='frm'>
  <input type="hidden" name="report" value="1" />
  <input type="hidden" name="hierarchy" id="hierarchy"/>
  <input type="hidden" name="selectednode" id="selectednode"/>
  <input type="hidden" name="listuser" id="listuser"/>
  <input type="hidden" name="currentnode" id="currentnode" value='<?php echo $currentnode; ?>'/>
  <?php 
        if($recursive){
          echo "<input type='radio' name='recursive' id='recursive' value='true' checked onclick=\"document.getElementById('frm').submit()\" title='".get_string('showallusershint','tool_hierarchy')."'>";
          echo get_string('showallusers','tool_hierarchy');
          echo "<input type='radio' name='recursive' id='recursive' value='false' onclick=\"document.getElementById('frm').submit()\" title='".get_string('showusersinthenodehint','tool_hierarchy')."'>";
          echo get_string('showusersinthenode','tool_hierarchy');
        } else{
          echo "<input type='radio' name='recursive' id='recursive' value='true' onclick=\"document.getElementById('frm').submit()\" title='".get_string('showallusershint','tool_hierarchy')."'>";
          echo get_string('showallusers','tool_hierarchy');
          echo "<input type='radio' name='recursive' id='recursive' value='false' checked onclick=\"document.getElementById('frm').submit()\" title='".get_string('showusersinthenodehint','tool_hierarchy')."'>";
          echo get_string('showusersinthenode','tool_hierarchy');
        }
          
  ?>
<div class='row-fluid'>
  <?php if(isset($_GET['update'])) echo $_SESSION['msg_update']; ?>
</div>
<div class='row-fluid'>
    <div class='span6'>
    <table class='hierarchy'>
       <tr>
      <td style='vertical-align: top;'><strong>Hierarchy</strong></td>
      <td class='hierarchy-filter-block'>
        <input type="text" id="hie_search" placeholder="Search for a node"/>
        <div id='jstree'></div>
      </td>
      </tr>
    </table>
  </div>
 <div class='span6'>
  <table width='100%'>
      <tr>
          <td>
            <?php
              echo "<input type='button' name='move' id='move' value='".get_string('relocate','tool_hierarchy')."' onclick='getUser()' title='".get_string('relocatehint','tool_hierarchy')."'/> ";
              echo "<input type='button' name='paste' id='paste' value='".get_string('moveto','tool_hierarchy')."' onclick='moveUser()'  title='".get_string('movetohint','tool_hierarchy')."' disabled/>";
            ?>
            <div id='selection'></div>
          </td>
      </tr>
    <tr>
          <td>
              <div id='loader' style='display:none'> <img src='css/loading.gif' width='50px' height='50px'> </div>
              <div id="user_selection"  name='user_selection'></div>
          </td>
      </tr>
  </table>
 </div>
</div>
  </form>
    <link rel="stylesheet" type="text/css" href="css/chosen.css">
   <link rel="stylesheet" type="text/css" href="js/jstree/dist/themes/default/style.min.css">
   <script src="//code.jquery.com/jquery-1.10.2.js"></script>
   <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
   <script src="js/chosen.js"></script>
   <script src="js/jstree/dist/jstree.min.js"></script>
    <style>
      .chosen-container-single .chosen-single {
        line-height: 30px;
      }
      .chosen-container-single .chosen-single {
        height: 30px;
      }
      .hierarchy-filter-block div {
        padding-top: 7px;
      }
      .hierarchy-filter-block p {
        padding-top: 10px;
      }
      .general-report tr > td {
        vertical-align: top;
      }
    .chosen-container {
      min-width: 220px;
    }   
  </style>
    <script type="text/javascript">
      var jq = $.noConflict();

    // init jstree
    jq('#jstree').jstree({ 
      'core' : {
          'data' : <?php echo $hierarchy_nodes; ?>
      },
      'plugins' : ['search'],
      'search' :  {
        "show_only_matches" : true
      },
    });
   jq('#jstree').on('loaded.jstree', function() {
    jq('#jstree').jstree('open_all');
    jq('#jstree').jstree('deselect_all');
    jq('#jstree').jstree('select_node',<?php echo $currentnode; ?>);
    });

    // update hierarchy selection
    jq('#jstree').on('changed.jstree', function (e, data) {
      var i, j, r = []; list = [];
      for(i = 0, j = data.selected.length; i < j; i++) {
        r.push(data.instance.get_node(data.selected[i]).text);
        list.push(data.instance.get_node(data.selected[i]).id);
      }
     // jq('#selection').html(r.join(', '));
      if(data.node != undefined){
        var link; 
        if(document.getElementById('recursive').checked){
            link = 'get_users.php?res=1<?php echo $str_sort;?>&node_id='+data.node.id;
        }else{
            link = 'get_users.php?res=0&node_id='+data.node.id;
        }
        jq(document).ready(function(){
          jq('#loader').show();
          jq('#user_selection').load(link,function(text){
            jq('#user_selection').html(text);
            jq('#loader').hide();
          });
        });
      }
      // jq('#user_selection').ready(function()){
      //       jq('#loader').hide();  
      //     }
      jq('#selectednode').val(list);
      // console.log("NODE ID: " + data.node.id);
      // console.log(data);
      if(data.node != undefined) {
        if(data.node.id != <?php echo $currentnode; ?>)
          jq('#hierarchy').val(data.node.id);
        else
          jq('#hierarchy').removeAttr('value');
      }
    }).jstree();

    // jstree search
    var to = false;
    jq('#hie_search').keyup(function () {
      if(to) { clearTimeout(to); }
      to = setTimeout(function () {
        var v = jq('#hie_search').val();
        jq('#jstree').jstree(true).search(v);
      }, 250);
    });
    
    function getUser(){
      var arr_checked = jq("input:checked");
      val= []; id=[]; sel=<?php echo "'".get_string('relocateusermsg','tool_hierarchy')."'"; ?>;

      jq("#selection").html("");
      for(var i=0;i<arr_checked.length;i++){
        if(arr_checked[i].name=='recursive') continue;
          val.push(arr_checked[i].name);
          id.push(arr_checked[i].id);
      }
      sel = sel + val.join(', ');
      jq("#selection").html(sel);
      jq("#listuser").val(id.join(','));
      if(arr_checked.length>1){
          jq("#move").attr("disabled",true);
          jq("#paste").removeAttr("disabled");
        }
    }
    function moveUser(){
      //var str = jq("#selection").html() + " have been moved to node: "+jq("#currentnode").val();
      link = "move_users.php?u="+jq("#listuser").val() + "&n="+jq("#selectednode").val();
      jq("#move").removeAttr("disabled");
      jq("#paste").attr("disabled",true);
      jq("#coach").attr("disabled",true);

        jq(document).ready(function(){
          jq('#selection').load(link,function(text){
            jq('#selection').html(text);
          });});
    }

    function assignCoach(){
      //var str = jq("#selection").html() + " have been moved to node: "+jq("#currentnode").val();
      var arr_checked = jq(".coach-test:checked");
      val= []; id=[]; sel=<?php echo "'".get_string('relocateusermsg','tool_hierarchy')."'"; ?>;

      jq("#selection").html("");
      for(var i=0;i<arr_checked.length;i++){
        if(arr_checked[i].name=='recursive') continue;
          val.push(arr_checked[i].name);
          id.push(arr_checked[i].id);
      }
      sel = sel + val.join(', ');
      jq("#selection").html(sel);
      jq("#listuser").val(id.join(','));
      link = "assign_coach.php?u="+jq("#listuser").val() + "&n="+jq("#selectednode").val();
      jq("#move").removeAttr("disabled");
      jq("#paste").attr("disabled",true);
      jq("#coach").attr("disabled",true);

        jq(document).ready(function(){
          jq('#selection').load(link,function(text){
            jq('#selection').html(text);
          });});
    }
</script>
<?php echo $OUTPUT->footer(); ?>
