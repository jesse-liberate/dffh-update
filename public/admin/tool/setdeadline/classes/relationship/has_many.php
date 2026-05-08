<?php
namespace tool_setdeadline\relationship;

class has_many extends base {
    /**
     * Related class name
     *
     * @var string
     */
    protected $related;
    protected $foreign_key;
    protected $local_key;

    public function __construct($model, $related, $foreign_key, $local_key = null) {
        parent::__construct($model);
        $this->related = $related;
        $this->foreign_key = $foreign_key;
        
        if (isset($local_key)) {
            $this->local_key = $local_key;
        } else {
            $this->local_key = 'id';
        }
    }

    public function get() {
        global $DB;

        $classname = $this->related;
        $conditions = $this->conditions;
        $params = $this->params;
        $related_entity = new $classname;
        $local_key = $this->local_key;

        $conditions[] = "{$this->foreign_key} = ?";
        $params[] = $this->model->$local_key;

        $fields = implode(', ', $related_entity->fields);
        $table = $classname::get_table();

        $relations = $DB->get_records_sql("SELECT $fields
            FROM {{$table}}
            WHERE " . implode(' AND ', $conditions), $params);

        $return = [];
        if (!empty($relations)) {
            foreach ($relations as $relation) {
                $return[$relation->id] = new $classname($relation);
            }
        }

        return $return;
    }

    public function delete() {
        global $DB;

        $classname = $this->related;
        $conditions = $this->conditions;
        $params = $this->params;
        $related_entity = new $classname;

        $conditions[] = "{$this->foreign_key} = ?";
        $params[] = $this->model->$local_key;

        $fields = implode(', ', $related_entity->fields);
        $table = $classname::get_table();

        return $DB->delete_records_select($table, implode(' AND ', $conditions));
    }
}