<?php
class theme_support_course_model {
    public $id;
    public $name;
    public $questions;

    function __construct($name = '') {
        global $DB;
        $record = $DB->get_record('course', array('id'=>$this->id));
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