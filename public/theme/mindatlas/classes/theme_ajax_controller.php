<?php

// use block_coursemanagement\primarycourse;

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/theme_ajax_controller_base.php');
require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->dirroot . '/theme/mindatlas/api/dffh_ajax_facetoface_lib.php');
require_once($CFG->dirroot . '/theme/mindatlas/classes/ma_ajax_controller_base.php');
// customization for particular project
class theme_mindle_ajax_controller extends ma_ajax_controller_base
{

    //DFFH API
    protected function dffh_ajax_list_training_session($payload)
    {
        $this->result->data = dffh_list_training_session($payload);
    }

    protected function dffh_ajax_list_coaching_session($payload)
    {
        $this->result->data = dffh_list_coaching_session($payload);
    }

    protected function dffh_ajax_list_requested_training_session($payload)
    {
        $this->result->data = dffh_list_requested_training_session($payload);
    }
    protected function dffh_ajax_detail_requested_training_session($payload)
    {
        $this->result->data = dffh_detail_requested_training_session($payload);
    }
    protected function dffh_ajax_get_session_course($payload)
    {
        $this->result->data = dffh_get_session_course($payload);
    }
    protected function dffh_ajax_create_session($payload){
        $this->result->data =  dffh_create_session($payload);
    }
    protected function dffh_ajax_get_session_id($payload)
    {
        $this->result->data = dffh_get_session_id($payload);
    }

    function dffh_ajax_get_available_users($payload){
        $this->result->data =  dffh_get_available_users($payload);
    }
    function dffh_ajax_get_available_fields($payload){
        $this->result->data =  dffh_get_available_fields($payload);
    }
    

    protected function dffh_ajax_check_coach($payload)
    {
        $this->result->data = dffh_check_coach($payload);
    }

    protected function dffh_ajax_check_admin($payload)
    {
        $this->result->data = dffh_check_admin($payload);
    }

    protected function dffh_ajax_has_coach($payload)
    {
        $this->result->data = dffh_has_coach($payload);
    }
    
    
    protected function dffh_ajax_remove_requested_training_session($payload)
    {
        $this->result->data = dffh_remove_request($payload);
    }
}
