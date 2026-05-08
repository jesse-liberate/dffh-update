<?php

// use block_coursemanagement\primarycourse;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->dirroot . '/blocks/mindatlas_formbuilder/api/formbuilder.php');
require_once(__DIR__ . '/ma_ajax_controller_base.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');
// customization for particular project
class theme_mindle_ajax_controller extends ma_ajax_controller_base
{

    //DFFH API
    protected function dffh_ajax_list_forms($payload)
    {
        $this->result->data = dffh_list_forms($payload);
    }

    protected function dffh_ajax_create_form($payload)
    {
        $this->result->data = dffh_create_form($payload);
    }
    protected function dffh_add_formfield($payload)
    {
        $this->result->data = dffh_add_formfield($payload);
    }

    protected function dffh_ajax_update_form($payload)
    {
        $this->result->data = dffh_update_form($payload);
    }

    protected function dffh_ajax_detail_form($payload)
    {
        $this->result->data = dffh_detail_form($payload);
    }
    protected function dffh_ajax_view_formbuilder($payload)
    {
        $formfielddata = dffh_view_formbuilder_user(1, 1, (int)$payload['formid']);
  
        //var_dump($formfielddata);die();
    $html = '';
   
    foreach ($formfielddata as $formfield) {
        $html .=  dffh_render_formfield($formfield,(int)$payload['formid']);
        
    }
    $this->result->data = dffh_view_formbuilder($payload);
    $this->result->data['html'] = $html;
       
    }
    
    protected function dffh_ajax_remove_formbuilder($payload)
    {
        $this->result->data = dffh_remove_formbuilder($payload);
    }
    protected function dffh_edit_formfield($payload)
    {
        $this->result->data = dffh_update_formfield($payload);
    }

    protected function dffh_submit_data_formfield($payload)
    {
        $this->result->data = dffh_submit_data($payload);
    }
}
