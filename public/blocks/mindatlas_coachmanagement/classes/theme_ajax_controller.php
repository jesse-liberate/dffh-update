<?php

// use block_coursemanagement\primarycourse;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->dirroot . '/blocks/mindatlas_coachmanagement/api/coachmanagement.php');
require_once(__DIR__ . '/ma_ajax_controller_base.php');
// customization for particular project
class theme_mindle_ajax_controller extends ma_ajax_controller_base
{

    //DFFH API
    protected function dffh_ajax_get_coach_users($payload)
    {
        $this->result->data = dffh_get_coach_users($payload);
    }

    protected function dffh_ajax_request_coaching_session($payload)
    {
        $this->result->data = dffh_request_coaching_session($payload);
    }

    protected function dffh_ajax_list_coaching_session_manager($payload)
    {
        $this->result->data = dffh_list_coaching_session_manager($payload);
    }

    protected function dffh_ajax_check_coach($payload)
    {
        $this->result->data = dffh_check_coach($payload);
    }

    protected function dffh_ajax_request_list_course($payload)
    {
        $this->result->data = dffh_request_list_course($payload);
    }

    protected function dffh_ajax_update_requested_training_session($payload)
    {
        $this->result->data = dffh_update_request_coaching_session($payload);
    }

    function dffh_ajax_get_available_users($payload){
        $this->result->data =  dffh_get_available_users($payload);
    }

    protected function dffh_ajax_create_session($payload){
        $this->result->data =  dffh_create_session($payload);
    }

}
