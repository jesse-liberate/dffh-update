<?php

class theme_support_user_model {
    public $id;
    public $name;

    function __construct($name = '') {
        global $DB;
        $record = $DB->get_record('user', array('name'=>$this->name));
        if($record) {
            $this->id = $record->id;
            $this->name = $record->name;
            
        }else{
            $this->id = 0;
            $this->name = $name;
            $this->questions = [];
        }
    }




}