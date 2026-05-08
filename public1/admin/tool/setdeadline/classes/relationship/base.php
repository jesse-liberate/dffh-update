<?php
namespace tool_setdeadline\relationship;

use tool_setdeadline\model\base as model;

abstract class base {
    /**
     * @var model
     */
    protected $model;
    protected $conditions = [];
    protected $params = [];

    public function __construct(model $model) {
        $this->model = $model;
    }

    public function add_condition_raw($condition, $params = []) {
        $this->conditions[] = $condition;
        foreach ($params as $param) {
            $this->params[] = $param;
        }
    }

    public function add_condition($field, $operator, $value) {
        $this->add_condition_raw("$field $operator ?", $value);
    }

    public abstract function get();
}