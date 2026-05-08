<?php

/**
 * @package   tool_cohortenrolmentrules
 * @copyright  2016  Charlie Tran
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Include required libary files
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require($CFG->dirroot.'/cohort/lib.php');
require_once('lib.php');

// Include what we need
// require_once($CFG->dirroot.'/vendor/autoload.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin'); 
$PAGE->set_title($SITE->fullname.': Cohort Enrolment Rules');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/admin/tool/cohortenrolmentrules/index.php');


$contextid = optional_param('contextid', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$searchquery  = optional_param('search', '', PARAM_RAW);

$cohort_classname_exist = false;
if(file_exists($CFG->dataroot."/cohort/classes/output/cohortname.php")) $cohort_classname_exist = true;

if ($CFG->forcelogin) {
    require_login();
}


if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
} else {
    $context = context_system::instance();
}

echo $OUTPUT->header();
$NUM_PER_PAGE = 50;

$cohorts = cohort_get_all_cohorts($page, 50, $searchquery);

echo get_string('cohortsettingheader', 'tool_cohortenrolmentrules');

$params = array('page' => $page);
if ($contextid) {
    $params['contextid'] = $contextid;
}
if ($searchquery) {
    $params['search'] = $searchquery;
}
$params['showall'] = true;

$baseurl = new moodle_url('/admin/tool/cohortenrolmentrules/index.php', $params);

if (!empty($cohorts['cohorts'])) {
    $NUM_PAGES = ceil($cohorts['allcohorts']/$NUM_PER_PAGE);
    $pagination_html="";
    if($NUM_PAGES>1){
        $pagination_html="Page: ";
        for ($i=0;$i<$NUM_PAGES;$i++) {
            $page_number = $i + 1;

            if($page==$i) $pagination_html.= $page_number." ";
            else 
                $pagination_html.= "<a href='".$CFG->wwwroot."/admin/tool/cohortenrolmentrules/index.php?page=".$i."'>".$page_number." </a>";
        }
    }
    foreach($cohorts['cohorts'] as $id=>$cohort) {
        $line = array();
        $cohortcontext = context::instance_by_id($cohort->contextid);
        $cohort->description = file_rewrite_pluginfile_urls($cohort->description, 'pluginfile.php', $cohortcontext->id, 'cohort', 'description', $cohort->id);
        
        if ($cohort_classname_exist) {
            $tmpl = new \core_cohort\output\cohortname($cohort);
            $line[] = $OUTPUT->render_from_template('core/inplace_editable', $tmpl->export_for_template($OUTPUT));        
            $tmpl = new \core_cohort\output\cohortidnumber($cohort);
            $line[] = $OUTPUT->render_from_template('core/inplace_editable', $tmpl->export_for_template($OUTPUT));
        } else {
            $line[] = format_string($cohort->name);
            $line[] = s($cohort->idnumber); // All idnumbers are plain text.
        }

        $line[] = $DB->count_records('cohort_members', array('cohortid'=>$cohort->id));

        // Get the applied rules of cohort
        if($DB->record_exists('cohort_setting',array('cohort_id'=>$id))){
            $rules_combination = explode(' OR ', $DB->get_field('cohort_setting', 'rules_combination', array('cohort_id'=>$id)));
            $rulenames = array();
            foreach ($rules_combination as $key => $ruleid) {
                array_push($rulenames, $DB->get_field('cohort_setting_rules', 'rule_name', array('id'=>$ruleid)));
            }
            $appliedrules = implode(' OR ', $rulenames);
            $line[] = format_text($appliedrules, $cohort->descriptionformat);
        }else{
            if($cohort->name=="All users") $line[] = "Default";
            else $line[] = "(Has not been assigned yet)";
        }


        $buttons = array();
        if (empty($cohort->component)) {
            $cohortmanager = has_capability('moodle/cohort:manage', $cohortcontext);
            $cohortcanassign = has_capability('moodle/cohort:assign', $cohortcontext);

            $urlparams = array('id' => $cohort->id, 'returnurl' => $baseurl->out_as_local_url());
            $showhideurl = new moodle_url('/cohort/edit.php', $urlparams + array('sesskey' => sesskey()));
            if ($cohortmanager) {
                // if ($cohort->visible) {
                //     $showhideurl->param('hide', 1);
                //     $visibleimg = html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/hide'), 'alt' => get_string('hide'), 'class' => 'iconsmall'));
                //     $buttons[] = html_writer::link($showhideurl, $visibleimg, array('title' => get_string('hide')));
                // } else {
                //     $showhideurl->param('show', 1);
                //     $visibleimg = html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/show'), 'alt' => get_string('show'), 'class' => 'iconsmall'));
                //     $buttons[] = html_writer::link($showhideurl, $visibleimg, array('title' => get_string('show')));
                // }            
                if($cohort->name!="All users"){ // All users should not be assigned to any cohorts.
                    $buttons[] = html_writer::link(new moodle_url('/admin/tool/cohortenrolmentrules/cohort_setting_edit.php', $urlparams),
                    html_writer::empty_tag('img', array('src' => $CFG->wwwroot.'/admin/tool/cohortenrolmentrules/js/edit.png', 'alt' => get_string('edit'), 'class' => 'iconsmall')),
                    array('title' => get_string('edit')));
                }
                // $buttons[] = html_writer::link(new moodle_url('/cohort/edit.php', $urlparams + array('delete' => 1)),
                //     html_writer::empty_tag('img', array('src' => $CFG->wwwroot.'/admin/tool/cohortenrolmentrules/js/delete.png', 'alt' => get_string('delete'), 'class' => 'iconsmall')),
                //     array('title' => get_string('delete')));
                $editcolumnisempty = false;
            }
            if ($cohortcanassign) {
                $buttons[] = html_writer::link(new moodle_url('/cohort/assign.php', $urlparams),
                    html_writer::empty_tag('img', array('src' => $CFG->wwwroot.'/admin/tool/cohortenrolmentrules/js/users.png', 'alt' => get_string('assign', 'core_cohort'), 'class' => 'iconsmall')),
                    array('title' => get_string('assign', 'core_cohort')));
                $editcolumnisempty = false;
            }
        }
        $line[] = implode(' ', $buttons);

        $data[] = $row = new html_table_row($line);
        if (!$cohort->visible) {
            $row->attributes['class'] = 'dimmed_text';
        }
    }

    $table = new html_table();
    $table->head  = array(get_string('name', 'cohort'), 
                          get_string('idnumber', 'cohort'), 
                          get_string('memberscount', 'cohort'),
                          get_string('appliedrules', 'tool_cohortenrolmentrules')
                          );
    $table->colclasses = array('leftalign name', 'leftalign id', 'leftalign description', 'leftalign size','centeralign source');

    if (!$editcolumnisempty) {
        $table->head[] = 'Action';
        $table->colclasses[] = 'centeralign action';
    } else {
        // Remove last column from $data.
        foreach ($data as $row) {
            array_pop($row->cells);
        }
    }
    $table->id = 'cohorts';
    $table->attributes['class'] = 'admintable generaltable';
    $table->data  = $data;
    echo $pagination_html;
    echo html_writer::table($table);
    echo $pagination_html;

    echo "\n<br><a class='btn btn-primary' href='../../../cohort/edit.php?contextid=1'>Add Cohort</a></br>";

} else echo get_string('nocohort','tool_cohortenrolmentrules');
// echo "<a class='btn' href='cohort_setting_add.php'>Add Cohort</a></br></br></br>";

/* ------------------------------------------ Display Rule list ------------------------------------------ */

echo get_string('rulelistheader','tool_cohortenrolmentrules');

$cohort_setting_rules = cohortsetting_get_all_rules();

// echo '<pre>'.print_r($cohort_setting_rules, true).'</pre>';

foreach ($cohort_setting_rules as $ruleid=>$rule) {
    $rule_conditions_array = $DB->get_fieldset_select('cohort_setting_conditions', 'description', 'rule_id =?',array($ruleid));
    $rule_conditions_string = implode(' AND ', $rule_conditions_array);

    $line = array();
    $line[] = $rule->rule_name;
    $line[] = $rule_conditions_string;

    // $line[] = $DB->count_records('cohort_setting_rule_members', array('ruleid'=>$ruleid));

    $rulemembers = $DB->get_records('cohort_setting_rule_members',array('ruleid'=>$ruleid));

    foreach ($rulemembers as $key=>$member) {
        $user = $DB->get_record('user', array('id'=>$member->userid, 'deleted'=>0));
        if (empty($user)) {
            unset($rulemembers[$key]);
        }
    }

    $line[] = sizeof($rulemembers);

    $urlparams = array('id' => $ruleid, 'returnurl' => $baseurl->out_as_local_url());
    
    $buttons = array();     

    $buttons[] = html_writer::link(new moodle_url('/admin/tool/cohortenrolmentrules/cohort_setting_rule_edit.php',$urlparams),
        html_writer::empty_tag('img', array('src' => $CFG->wwwroot.'/admin/tool/cohortenrolmentrules/js/edit.png', 'alt' => get_string('edit'), 'class' => 'iconsmall')),
        array('title' => get_string('edit')));

    $buttons[] = html_writer::link(new moodle_url('/admin/tool/cohortenrolmentrules/cohort_setting_rule_members.php', $urlparams),
                html_writer::empty_tag('img', array('src' => $CFG->wwwroot.'/admin/tool/cohortenrolmentrules/js/users.png', 'alt' => get_string('rulemembers', 'tool_cohortenrolmentrules'), 'class' => 'iconsmall')),
                array('title' => get_string('rulemembers', 'tool_cohortenrolmentrules')));

    $buttons[] = html_writer::link(new moodle_url('/admin/tool/cohortenrolmentrules/cohort_setting_rule_delete.php',$urlparams + array('delete' => 1)),
                html_writer::empty_tag('img', array('src' => $CFG->wwwroot.'/admin/tool/cohortenrolmentrules/js/delete.png', 'alt' => get_string('delete'), 'class' => 'iconsmall')),
                array('title' => get_string('delete')));

    $line[] = implode(' ', $buttons);
    $data_rules[] = $row = new html_table_row($line);
}


if (isset($data_rules)) {
    $table_rules = new html_table();

    $table_rules->head = array(get_string('rulename', 'tool_cohortenrolmentrules'), get_string('ruleconditions', 'tool_cohortenrolmentrules'), get_string('rulesize', 'tool_cohortenrolmentrules'), get_string('ruleaction', 'tool_cohortenrolmentrules'));

    $table_rules->colclasses = array('leftalign name', 'leftalign description', 'leftalign description','centeralign source');

    $table_rules->id = 'cohort_setting_rules';
    $table_rules->attributes['class'] = 'admintable generaltable';
    $table_rules->data  = $data_rules;

    echo html_writer::table($table_rules);
} else echo '<p>Please setup rules to apply for Cohorts.</p>';

echo "<a class='btn btn-primary' href='cohort_setting_rule_add.php'>Add Rule</a></br>";

echo $OUTPUT->footer();   

?>