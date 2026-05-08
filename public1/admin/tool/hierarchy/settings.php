<?php
require_once($CFG->dirroot.'/admin/tool/hierarchy/lib.php');
$profile_fields_choices = get_hiearchy_profile_fields();

$temp = new admin_settingpage('hierarchy_settingpage', new lang_string('hierarchy_settingpage', 'tool_hierarchy'));
$ADMIN->add('tools', $temp);

$temp->add(new admin_setting_heading('tool_hierarchy_heading', '', get_string('tool_hierarchy_heading', 'tool_hierarchy')));

$temp->add(new admin_setting_configtext('tool_hierarchy/hiearchy_rootname', get_string('hierarchyrootname','tool_hierarchy'), get_string('hierarchyrootnamedesc','tool_hierarchy'), 'LMS', PARAM_TEXT));

$temp->add(new admin_setting_configselect('tool_hierarchy/hierarchy_positioncode',
                    get_string('positioncode:label', 'tool_hierarchy'),
                    get_string('positioncode:desc', 'tool_hierarchy'),
                    0, $profile_fields_choices));

$temp->add(new admin_setting_configselect('tool_hierarchy/hierarchy_reportto',
                    get_string('reportto:label', 'tool_hierarchy'),
                    get_string('reportto:desc', 'tool_hierarchy'),
                    0, $profile_fields_choices));

$temp->add(new admin_setting_configselect('tool_hierarchy/hierarchy_positiontitle',
                    get_string('positiontitle:label', 'tool_hierarchy'),
                    get_string('positiontitle:desc', 'tool_hierarchy'),
                    0, $profile_fields_choices));


$ADMIN->add('users', new admin_category('hierarchy','Hierarchy'));
$ADMIN->add('hierarchy', new admin_externalpage('hierarchy_visualization', 'Hierarchy visualiser', "$CFG->wwwroot/$CFG->admin/tool/hierarchy/visualization.php"));
$ADMIN->add('hierarchy', new admin_externalpage('hierarchy_build_hierarchy', 'Generate hierarchy manually', "$CFG->wwwroot/$CFG->admin/tool/hierarchy/user_import/user_import_to_hierarchy_web.php"));
