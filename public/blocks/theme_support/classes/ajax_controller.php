<?php

global $CFG, $DB;

class ajax_controller extends ajax_controller_base{


}


class ajax_controller_base {
    // 'action name' => 'description'
    var $actions = [

    ];

    // input
    var $action;
    var $payload;

    // output
    var $result;

    public function __construct()
    {

        if(empty($_POST)){
            $post_data = json_decode(file_get_contents("php://input"), true);
            $this->action = $post_data['action'];
            $this->payload = $post_data['payload'];
        }else{
            $this->action = required_param('action', PARAM_RAW);
            $this->payload = optional_param('payload', null, PARAM_RAW);
        }
    }



    protected function validate_action($action) {

        $this->result->action = $action;
        if(!method_exists($this,$action)) {
            $this->result->error = "Action: $action is not found in actions. Please check the available actions.";
            $this->result->available_actions = $this->get_ajax_actions();
            return false;
        }

        return true;
    }

    public function process($action, $payload = null) {

        try{
            // stop if input validation not pass
            if(!$this->validate_action($action, $payload)) {
                http_response_code(400);
                return $this->result;
            }
            $this->$action($payload);
            return $this->result;
        }catch (Exception $e){
            http_response_code(500);
            return $e;
        }

    }

    protected function get_ajax_actions() {
        $ajax_actions = [];
        $class = new ReflectionClass(get_class($this));
        $methods = $class->getMethods();
        foreach($methods as $key => $method) {
            // function name starts With ajax_
            if(strpos($method->name, 'ajax_') === 0) {
                $ajax_actions[] = $method;
            }
        }
        
        // Another simple way, return an array of string without class name
        // $methods = get_class_methods(get_class($this));
        return $ajax_actions;
    }

}