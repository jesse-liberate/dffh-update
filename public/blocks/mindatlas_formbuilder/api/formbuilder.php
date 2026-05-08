<?php

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');
require_once($CFG->libdir . '/accesslib.php');
// require_login(); 

function dffh_error_message($message)
{
    return array('error' => true, 'message' => $message);
}
function dffh_list_forms($payload)
{
    global $DB, $USER;
    $userid = $payload['userid'];
    $is_admin = is_siteadmin($userid);

    if ($is_admin == false) {
        return dffh_error_message("require admin permission for this access");
    }
    $query = "SELECT f.id, f.name, f.timecreated, f.timeupdated, count(ff.id) as total_field FROM mdl_formbuilder_form f LEFT JOIN mdl_formbuilder_form_info_field ff ON f.id = ff.formid GROUP BY f.id";

    $forms = $DB->get_records_sql($query);

    return  array_values($forms);
}

function dffh_create_form($payload)
{
    global $DB, $USER;
    $userid = $payload['userid'];
    $is_admin = is_siteadmin($userid);

    if ($is_admin == false) {
        return dffh_error_message("require admin permission for this access");
    }
    if ($DB->get_record('formbuilder_form', array('name' => $payload['name'])) != false)
        return dffh_error_message("The form is existed!");
    $formobject = new stdClass();
    $formobject->name = $payload['name'];
    $formobject->description = $payload['description'];
    $formobject->visible = $payload['visible'];;
    $formobject->timecreated = time();
    $formobject->timeupdated = time();
    $formobject->id = $DB->insert_record('formbuilder_form', $formobject);

    return $formobject;
}

function dffh_update_form($payload)
{
    global $DB, $USER;
    $userid = $payload['userid'];
    $is_admin = is_siteadmin($userid);
    if ($is_admin == false) {
        return dffh_error_message("require admin permission for this access");
    }
    if ($payload['visible'] == null || $payload['visible'] == '') {
        return dffh_error_message("require visible");
    }
    $formobj = $DB->get_record("formbuilder_form", array('id' => $payload['formid']));
    $formobj->visible = intval($payload['visible']);
    return $formobj;
}

function dffh_detail_form($payload)
{
    global $DB, $USER;
    $userid = $payload['userid'];
    $is_admin = is_siteadmin($userid);
    if ($is_admin == false) {
        return dffh_error_message("require admin permission for this access");
    }
    if ($DB->get_record('formbuilder_form', array('id' => $payload['formid'])) == false) {
        dffh_error_message("The form does not exist");
    }
    $formfields = $DB->get_records('formbuilder_form_info_field', array('formid' => $payload['formid']));
    if ($formfields == false) {
        dffh_error_message("The form is empty");
    }
    return $formfields;
}

//There are four type of fields 
define('TEXT_INPUT', 'text');
define('TEXT_AREA', 'textarea');
define('CHECKBOX', 'checkbox');
define("DROPDOWN", 'menu');

function dffh_add_formfield($payload)
{
    global $DB, $USER;
    $userid = $payload['userid'];
    $fields = $payload['fields'];
    $is_admin = is_siteadmin($userid);
    if ($is_admin == false) {
        return dffh_error_message("require admin permission for this access");
    }

    if ($DB->get_record("formbuilder_form", array('id' => $payload['formid'])) == false) {
        return dffh_error_message("form does not exist");
    }

    // if($DB->get_record("formbuilder_form", array('formid' => $payload['formid'])) != false){
    //     return "The form is already having data, can not added field";
    // }

    // if ($DB->get_record('formbuilder_form_info_field', array('formid' => $payload['formid'], 'shortname' => $payload['shortname'])) != false) {
    //     return dffh_error_message("the field is already existed");
    // }

    // if (
    //     $payload['shortname'] == null || $payload['shortname'] == ''
    //     || $payload['name'] == null || $payload['name'] == ''
    //     || $payload['visible'] == null || $payload['visible'] == ''
    //     || $payload['param1'] == null || $payload['param1'] == ''
    //     || $payload['datatype'] == null || $payload['datatype'] == ''

    // ) {
    //     return dffh_error_message("require shortname, name, visible, param1, datatype");
    // }

    foreach ($fields as $field) {
        $formfieldobj = new stdClass();
        $formfieldobj->name = $field['field']['name'];
        $formfieldobj->visible = 1;
        $formfieldobj->descriptionformat = 1;
        $formfieldobj->datatype = $field['field']['datatype'];
        $formfieldobj->shortname = $field['field']['shortname'];
        $formfieldobj->sortorder = $field['field']['sortorder'];
        $formfieldobj->description = $field['field']['description'];
        $formfieldobj->description = $field['field']['defaultdata'];
        $formfieldobj->param1 = $field['field']['param1'];
        $formfieldobj->formid = $payload['formid'];
        $formfieldobj->id = $DB->insert_record('formbuilder_form_info_field', $formfieldobj, true);
    }
    return true;
}
//update form field name and visibile 
function dffh_update_formfield($payload)
{
    global $DB, $USER;
    $userid = $payload['userid'];
    $form_id = $payload['formid'];
    $fields = $payload['fields'];
    $form_data = $payload['formData'];
   
    $is_admin = is_siteadmin($userid);
    if ($is_admin == false) {
        return dffh_error_message("require admin permission for this access");
    }

    $form = $DB->get_record("formbuilder_form", array('id' => $form_id));

    if ($form == false) {
        return dffh_error_message("form does not exist");
    }
    $form->timeupdated = time();
    $form->name = $form_data['name'];
    $form->description = $form_data['description'];
    $form->visible = (int)$form_data['visible'];
    $DB->update_record('formbuilder_form', $form);


    foreach ($fields as $field) {
        $formfieldobj = $DB->get_record("formbuilder_form_info_field", array('id' => $field['field']['id']));
        if ($formfieldobj == false) {
            $formfieldobj = new stdClass();
            $formfieldobj->name = $field['field']['name'];
            $formfieldobj->visible = 1;
            $formfieldobj->shortname = $field['field']['shortname'];
            $formfieldobj->descriptionformat = 1;
            $formfieldobj->datatype = $field['field']['datatype'];
            $formfieldobj->sortorder = $field['field']['sortorder'];
            $formfieldobj->defaultdata = $field['field']['defaultdata'];
            $formfieldobj->description = $field['field']['description'];
            $formfieldobj->param1 = $field['field']['param1'];
            $formfieldobj->formid = $payload['formid'];
            $formfieldobj->id = $DB->insert_record('formbuilder_form_info_field', $formfieldobj, true);
        } else {
            // $formfieldobj->id = $field['field']['id'];
            $formfieldobj->name = $field['field']['name'];
            $formfieldobj->visible = 1;
            $formfieldobj->descriptionformat = 1;
            $formfieldobj->datatype = $field['field']['datatype'];
            $formfieldobj->shortname = $field['field']['shortname'];
            $formfieldobj->sortorder = $field['field']['sortorder'];
            $formfieldobj->description = $field['field']['description'];
            $formfieldobj->defaultdata = $field['field']['defaultdata'];
            $formfieldobj->required = $field['field']['required'];
            $formfieldobj->param1 = $field['field']['param1'];
            $formfieldobj->formid = $form_id;
            $DB->update_record('formbuilder_form_info_field', $formfieldobj);
        }
    }

    return true;
}
//Todo: implementation
function dffh_view_formbuilder_data($payload)
{
    global $DB;
    return;
}

//Todo: implementation
function dffh_view_formbuilder($payload)
{
    global $DB;
    $formid = $payload['formid'];
    $sql = "SELECT * 
            FROM {formbuilder_form_info_field}
            WHERE formid = ?
            ORDER BY sortorder ASC";
    $formfields = $DB->get_records_sql($sql, array($formid));

    $form_info = $DB->get_record('formbuilder_form', array('id' => $formid));

   
    foreach( $formfields as $field){
        if($field->datatype == "checkbox" || $field->datatype == "radio"){
            $options = explode(',',$field->defaultdata);
            $html = '';
            foreach($options as $option){
                $html .= '<div>
               
                <input className="form-control" type="'.$field->datatype.'" id="'.$option.'" name="'.$field->name.'" value="'.$option.'" />
                <label className="col-form-label" for="'.$option.'">'.$option.'</label>
              </div>';
             
            }
           if($field->visible == 1){
                $field->content = '';
                $field->content .= '<div className="form-group">
                <label className="col-form-label"><div className="form-group">';
                $field->content .= $html;
                $field->content .= '</div>';
           } 
        }else if ($field->datatype == "select"){
            $options = explode(',',$field->defaultdata);
            $html = '  <select>';
            foreach($options as $option){
                $html .= '<option value="'.$option.'">'.$option.'</option>';
            }
            $field->content = '';
           if($field->visible == 1){
                $field->content .= $html;
                $field->content .= '</select>';
           } 
        }
    }

      $fields = array_values($formfields);


    $fieldData = array_map(function ($field) {

        return ['id' => $field->id, 'field' => $field];
    }, $fields);
   
    if ($formfields == false)
        return dffh_error_message("there is no fields in the form");
    return ['formSetup' => $form_info, 'fieldForm' => $fieldData];
}

function dffh_remove_formbuilder($payload)
{
    global $DB;
    $formid = $payload['formid'];
    $field_id = $payload['field_id'];
    if ($formid) {
        $formfields = $DB->get_records_sql("SELECT * FROM {formbuilder_form_info_field} WHERE formid = ?", array($formid));
        $fields = array_values($formfields);
        foreach ($fields as $field) {
            $check_data = $DB->get_record('formbuilder_form_info_data', array('fieldid' => $field->id));
            if ($check_data != false) {
                return dffh_error_message("The form is already having data, can not delete");
            }
        }

        $sql = "DELETE FROM {formbuilder_form_info_field}
            WHERE formid = ?";
        $DB->execute($sql, array($formid));
        $sql = "DELETE FROM {formbuilder_form}
            WHERE id = ?";
        $DB->execute($sql, array($formid));
        return true;
    }
    if ($field_id) {
        $formfieldobj = $DB->get_record('formbuilder_form_info_field', array('id' => $field_id));
        if ($formfieldobj == false) {
            return dffh_error_message("the field does not exist");
        }
        $formfieldobj->visible = $formfieldobj->visible == 1 ? 0 : 1;
        $DB->update_record('formbuilder_form_info_field', $formfieldobj);
        return true;
    }

    // $sql = "SELECT * 
    //         FROM {formbuilder_form_info_field}
    //         WHERE formid = ?
    //         ORDER BY sortorder ASC";
    // $formfields = $DB->get_records_sql($sql, array($formid));

    // $form_info = $DB->get_record('formbuilder_form', array('id' => $formid));

    // if ($formfields == false)
    //     return dffh_error_message("there is no fields in the form");
    // return ['formSetup' => $form_info, 'fieldForm' => array_values($formfields)];
}

function dffh_submit_data($data)
{
    global $DB;
    var_dump($data);
    die;
    if (isset($_POST['formid']) == false) return;
    $userid = intval(required_param('attendeeid',  PARAM_INT));
    $sessionid = intval(required_param('s',  PARAM_INT));
    $formid = intval(required_param('formid',  PARAM_INT));
    foreach ($data as $item) {
        if (intval($item->visible) == 1) {
            $fieldshortname = $DB->get_record('formbuilder_form_info_field', array('shortname' => $item->shortname, 'formid' => $formid));
            if ($fieldshortname == false) return;
            $formdataobj = $DB->get_record('formbuilder_form_info_data', array('userid' => $userid, 'f2fsessionid' => $sessionid, 'fieldid' => $fieldshortname->id));
            $formdataobj->userid = $userid;
            $formdataobj->f2fsessionid = $sessionid;
            $formdataobj->data = optional_param($item->shortname, '', PARAM_TEXT);
            $DB->update_record('formbuilder_form_info_data', $formdataobj);
        }
    }
}
