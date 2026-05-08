<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_tool_selfregistration_upgrade($oldversion) {
    if ($oldversion < 2021061004) {
        global $DB;

        //Organisation Data
        $organisation_data_category = $DB->get_record('user_info_category', ['name' => 'Organisation Data']);
        if (empty($organisation_data_category)) {
            $organisation_data_category = new stdClass();
            $organisation_data_category->name = "Organisation Data";
            $sql = "SELECT MAX(sortorder) as maxorder
                    FROM {user_info_category}";
            $record = $DB->get_record_sql($sql);
            $organisation_data_category->sortorder = $record->maxorder + 1;
            $organisation_data_category->id = $DB->insert_record('user_info_category', $organisation_data_category, true);
        }

        $category_id = $organisation_data_category->id;

        //Add Organisation or Agency field
        $field = $DB->get_record('user_info_field', ['shortname' => 'OrganisationOrAgency']);
        if (empty($field)) {
            $field = new stdClass();
            $field->shortname = 'OrganisationOrAgency';
            $field->name = 'Organisation or Agency';
            $field->signup = 1;
            $field->visible = 2;
            $field->categoryid = $category_id;
            $field->descriptionformat = 1;
            $field->datatype = 'menuwithfreetext';
            $sql = "SELECT MAX(sortorder) as maxorder
                    FROM {user_info_field}
                    where categoryid = " . $category_id;
            $record = $DB->get_record_sql($sql);
            $field->sortorder = $record->maxorder + 1;
            $DB->insert_record('user_info_field', $field, true);
        }

        //Add Organisation or Agency field
        $field = $DB->get_record('user_info_field', ['shortname' => 'RoleOrPosition']);
        if (empty($field)) {
            $field = new stdClass();
            $field->shortname = 'RoleOrPosition';
            $field->name = 'Role/Position titles';
            $field->signup = 1;
            $field->visible = 2;
            $field->categoryid = $category_id;
            $field->descriptionformat = 1;
            $field->datatype = 'menuwithfreetext';
            $sql = "SELECT MAX(sortorder) as maxorder
                    FROM {user_info_field}
                    where categoryid = " . $category_id;
            $record = $DB->get_record_sql($sql);
            $field->sortorder = $record->maxorder + 1;
            $field->param1 =
                'Practitioner' . PHP_EOL .
                'Leader' . PHP_EOL .
                'CP Navigator' . PHP_EOL .
                'Program Manager';
            $DB->insert_record('user_info_field', $field, true);
        }

        //Add Program field
        $field = $DB->get_record('user_info_field', ['shortname' => 'Program']);
        if (empty($field)) {
            $field = new stdClass();
            $field->shortname = 'Program';
            $field->name = 'Program';
            $field->signup = 1;
            $field->visible = 2;
            $field->categoryid = $category_id;
            $field->descriptionformat = 1;
            $field->datatype = 'menu';
            $sql = "SELECT MAX(sortorder) as maxorder
                    FROM {user_info_field}
                    where categoryid = " . $category_id;
            $record = $DB->get_record_sql($sql);
            $field->sortorder = $record->maxorder + 1;
            $DB->insert_record('user_info_field', $field, true);
        }
    }

    if ($oldversion < 2021062800) {
        global $DB;

        $organisation_data_category = $DB->get_record('user_info_category', ['name' => 'Organisation Data']);
        $category_id = $organisation_data_category->id;

        //Add local areas
        $field = $DB->get_record('user_info_field', ['shortname' => 'LocalAreas']);
        if (empty($field)) {
            $field = new stdClass();
            $field->shortname = 'LocalAreas';
            $field->name = 'Local Areas';
            $field->signup = 1;
            $field->visible = 2;
            $field->categoryid = $category_id;
            $field->descriptionformat = 1;
            $field->param1 =
                'Barwon' . PHP_EOL .
                'Bayside Peninsula' . PHP_EOL .
                'Brimbank Melton' . PHP_EOL .
                'Central Highlands' . PHP_EOL .
                'Goulburn' . PHP_EOL .
                'Hume Moreland' . PHP_EOL .
                'Inner Eastern Melbourne' . PHP_EOL .
                'Inner Gippsland' . PHP_EOL .
                'Loddon' . PHP_EOL .
                'Mallee' . PHP_EOL .
                'North Eastern Melbourne' . PHP_EOL .
                'Outer Eastern Melbourne' . PHP_EOL .
                'Outer Gippsland' . PHP_EOL .
                'Ovens Murray' . PHP_EOL .
                'Southern Melbourne' . PHP_EOL .
                'Western Melbourne' . PHP_EOL .
                'Wimmera South West';
            $field->datatype = 'multiselect';
            $sql = "SELECT MAX(sortorder) as maxorder
                    FROM {user_info_field}
                    where categoryid = " . $category_id;
            $record = $DB->get_record_sql($sql);
            $field->sortorder = $record->maxorder + 1;
            $DB->insert_record('user_info_field', $field, true);
        }
    }

    // rename Local Areas to Working Sites
    if ($oldversion < 2021062801) {
        global $DB;

        $field = $DB->get_record('user_info_field', ['shortname' => 'LocalAreas']);
        $field->shortname = 'WorkingSites';
        $field->name = 'Working Sites';
        $DB->update_record('user_info_field', $field);
    }

    return true;
}
