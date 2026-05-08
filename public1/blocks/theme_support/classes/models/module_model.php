<?php


//this file is just example file copy from VBA.
class mt_module_model {
    public $id;
    public $name;
    public $questions;

    function __construct($name = '') {
        global $DB;
        $record = $DB->get_record('mt_modules', array('name'=>$this->name));
        if($record) {
            $this->id = $record->id;
            $this->name = $record->name;
            
        }else{
            $this->id = 0;
            $this->name = $name;
            $this->questions = [];
        }
    }

    public static function get_modules() {
        global $DB;
        $records = $DB->get_records('mt_modules', array());
        return $records;
    }

    function db_id() {
        global $DB;
        $record = $DB->get_record('mt_modules', array('id'=>$this->id));
        return $record;
    }

    function save() {
        global $DB;
        $record = $DB->get_record('mt_modules', array('name'=>$this->name));

        if($this->id) {
            $record = new stdClass();
            // $record->
            return $DB->update_record('mt_modules', $record);
        }

        if($record) {
            $this->id = $record->id;
            $this->name = $record->name;
            
        }else{
            // $newId = block_marking_tool_create_module($this->name);
            // $this->id = $newId;
            // $this->name = $name;
        }
    }

    // will create if question not exist
    function overwrite_question($number, $html) {
        global $DB;
        $record = $DB->get_record('mt_question', array('module'=>$this->module, 'number'=>$number));
        if($record) {
            
        }
    }
    
    function get_questions() {
        
    }


}