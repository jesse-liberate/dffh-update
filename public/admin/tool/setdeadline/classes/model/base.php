<?php
namespace tool_setdeadline\model;

use tool_setdeadline\relationship\base as relationship;
use tool_setdeadline\relationship\has_many;

abstract class base {
    protected static $table;

    /**
     * Get table name
     *
     * @return string
     */
    public static function get_table() {
        if (empty(static::$table)) {
            $class_name = static::class;
            $parts = explode('\\', $class_name);

            static::$table = end($parts);
        }
        
        return static::$table;
    }

    /**
     * @param int $id
     * @return static
     */
    public static function get_by_id($id) {
        global $DB;

        $db_record = $DB->get_record(static::get_table(), [
            'id' => $id
        ]);

        return new static($db_record);
    }

    public static function get_all($conditions = [], $sort = '', $fields = '*') {
        global $DB;

        $results = [];
        $db_records = $DB->get_records(static::get_table(), $conditions, $sort, $fields);

        foreach ($db_records as $db_record) {
            $results[$db_record->id] = new static($db_record);
        }

        return $results;
    }

    protected $fields = [];
    protected $attributes = [];
    protected $relations = [];
    protected $deleted = false;

    public function __construct($db_record = null) {
        if (isset($db_record)) {
            foreach ($this->fields as $field) {
                $value = null;
    
                if (isset($db_record->$field)) {
                    $value = $db_record->$field;
                }
    
                $this->attributes[$field] = $value;
            }
        }
    }

    public function save() {
        global $DB;

        if ($this->deleted) {
            return false;
        }

        $record = $this->to_object();

        if (!empty($record->id)) {
            $DB->update_record(static::get_table(), $record);
        } else {
            $this->id = $DB->insert_record(static::get_table(), $record);
        }

        return $this;
    }

    public function delete() {
        global $DB;

        $DB->delete_records(static::get_table(), [
            'id' => $this->id
        ]);
        $this->deleted = true;
    }

    public function to_object() {
        $object = new \stdClass;
        foreach ($this->attributes as $field => $value) {
            $object->$field = $value;
        }

        return $object;
    }

    protected function get_fields() {
        return $this->fields;
    }

    public function __get($name) {
        if (method_exists($this, 'get_' . $name)) {
            return call_user_func([$this, 'get_' . $name]);
        }

        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        if (!$this->has_relation_loaded($name)) {
            $this->load_relation($name);
        }

        return $this->relations[$name];
    }

    public function __set($name, $value) {
        if (!in_array($name, $this->fields)) {
            return;
        }

        $this->attributes[$name] = $value;
    }

    /**
     * Create has_many relationship
     *
     * @param string $related
     * @param string $foreign_key
     * @param string|null $local_key
     * @return has_many
     */
    protected function has_many($related, $foreign_key, $local_key = null) {
        return new has_many($this, $related, $foreign_key, $local_key);
    }

    protected function has_relation_loaded($key) {
        return array_key_exists($key, $this->relations);
    }

    protected function load_relation($key) {
        $relation = $this->$key();

        if ($relation instanceof relationship) {
            $this->relations[$key] = $relation->get();
        } else {
            throw new \Exception("$key is not a relation");
        }
    }
}