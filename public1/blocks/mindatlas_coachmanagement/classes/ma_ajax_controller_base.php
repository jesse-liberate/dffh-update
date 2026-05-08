<?php
// TODO: Alpha phase, move to lib/mindatlas when mature.

class ma_ajax_controller_base {
    // 'action name' => 'description'
    var $actions = [

    ];

    // input
    var $action;
    var $payload;

    // output
    var $result;
    
    function __construct() {
        $this->result =  new StdClass();
        $this->result->error = false;
        $this->data = null; // put the data you want to return here in your action functions
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

        // stop if input validation not pass
        if(!$this->validate_action($action, $payload)) {
            return $this->result;
        }

        $this->$action($payload);
        
        return $this->result;
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