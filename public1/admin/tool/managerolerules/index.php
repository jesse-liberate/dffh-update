<?php

/**
 * @package   tool_managerolerules
 * @copyright  2017 Eric Pham
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Include required libary files
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('lib.php');

// Include what we need

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin'); 
$PAGE->set_title($SITE->fullname.': Role Rules');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/admin/tool/managerolerules/index.php');


$contextid = optional_param('contextid', 0, PARAM_INT);
$params['showall'] = true;
$baseurl = new moodle_url('/admin/tool/managerolerules/index.php', $params);

if ($CFG->forcelogin) {  require_login();}
if(!is_siteadmin()){
    echo $OUTPUT->header();
    echo get_string('notallowtoaccess','tool_managerolerules');
    echo $OUTPUT->footer();
    die();
}
$html="";
$html_lib="<script src='js/jquery-1.12.2.min.js'></script>
  <script src='js/jquery.tablesorter.js'></script>

  <link rel='stylesheet' type='text/css' href='js/style.css' />";
$html.=get_string('managerolerules:title','tool_managerolerules');
$html.=get_all_assign_roles();
$html.="<a class='btn' href='role_setting_edit.php'>Add user role</a>";
$html.=get_string('systemrole:note','tool_managerolerules');

$html.=get_string('rulelistheader','tool_managerolerules');

$role_setting_rules = $DB->get_records('role_setting_rules');

// echo '<pre>'.print_r($role_setting_rules, true).'</pre>';

foreach ($role_setting_rules as $ruleid=>$rule) {
    $rule_conditions_array = $DB->get_fieldset_select('role_setting_conditions', 'description', 'rule_id =?',array($ruleid));
    $rule_conditions_string = implode(' AND ', $rule_conditions_array);

    $line = array();
    $line[] = $rule->rule_name;
    $line[] = $rule_conditions_string;
    $line[] = $DB->count_records('role_rule_members', array('ruleid'=>$ruleid));
    // $line[] = $rule->description;

    $urlparams = array('id' => $ruleid, 'returnurl' => $baseurl->out_as_local_url());
    
    $buttons = array();     

    $buttons[] = html_writer::link(new moodle_url('/admin/tool/managerolerules/role_setting_rule_edit.php',$urlparams),
        html_writer::empty_tag('img', array('src' => 'js/edit.png', 'alt' => get_string('edit'), 'class' => 'iconsmall')),
        array('title' => get_string('edit')));

    $buttons[] = html_writer::link(new moodle_url('/admin/tool/managerolerules/role_setting_rule_members.php', $urlparams),
                html_writer::empty_tag('img', array('src' => 'js/users.png', 'alt' => get_string('rulemembers', 'tool_managerolerules'), 'class' => 'iconsmall')),
                array('title' => get_string('rulemembers', 'tool_managerolerules')));

    $buttons[] = html_writer::link(new moodle_url('/admin/tool/managerolerules/role_setting_rule_delete.php',$urlparams + array('delete' => 1)),
                html_writer::empty_tag('img', array('src' => 'js/delete.png', 'alt' => get_string('delete'), 'class' => 'iconsmall')),
                array('title' => get_string('delete')));

    $line[] = implode(' ', $buttons);
    $data_rules[] = $row = new html_table_row($line);
}


if (isset($data_rules)) {
    $table_rules = new html_table();

    $table_rules->head = array(get_string('rulename', 'tool_managerolerules'), get_string('ruleconditions', 'tool_managerolerules'), get_string('rulesize', 'tool_managerolerules'), get_string('ruleaction', 'tool_managerolerules'));

    $table_rules->colclasses = array('leftalign name', 'leftalign description', 'leftalign description','centeralign source');

    $table_rules->id = 'role_setting_rules';
    $table_rules->attributes['class'] = 'tablesorter';
    $table_rules->data  = $data_rules;

    $html.= html_writer::table($table_rules);
} else $html.='<p>Please setup rules to apply for roles.</p>';

$html.="<a class='btn' href='role_setting_rule_add.php'>Add new rule</a></br>";


echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();   
echo $html_lib;

?>
<style type="text/css">
	table tr th:nth-child(2){
	    width: 40%;
	}
	table tr th:nth-child(3){
	    width: 20%;
	}
</style>
<script>
$(document).ready(function () {
    $("#report").tablesorter({
  headers:
    {
    },
    widgets: ['zebra']
    });
    $("#role_setting_rules").tablesorter({
  headers:
    {
    },
    widgets: ['zebra']
    });
});
</script>