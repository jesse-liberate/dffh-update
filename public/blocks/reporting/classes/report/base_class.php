<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace block_reporting\report;

/**
 * Description of base_class
 *
 * @author david
 */
abstract class base_class {
  protected $_request_values;
  public function __construct($request_values) {
    $this->_request_values = $request_values;
  }
  abstract protected function init();

  protected function getOrderByStatement($sort_matrix = array()) {
    $order_by = array();
    if (isset($this->_request_values['order']) && !empty($this->_request_values['order'])) {
      foreach ($this->_request_values['order'] as $order) {
        if (isset($sort_matrix[$order['column']])) {
          $field = $sort_matrix[$order['column']];
          $field = str_replace(' asc', ' ' . $order['dir'], $field);
          $order_by[] = $field;
        }
      }
    }
    if (!empty($order_by)) {
      return 'ORDER BY ' . implode(',', $order_by);
    }
    return '';
  }

  protected function fetchRecords($sql, $params) {
    $instance = db_class::getInstance();
    $conn = $instance->getConnection();
    $stmt = $conn->prepare($sql);
    if ($stmt) {
      $stmt->setFetchMode(\PDO::FETCH_NUM);
    }
    $stmt->execute($params);
    return $stmt->fetchAll();
  }

  protected function get_row_count($sql, $params)
  { 
    return $this->fetchRecords($sql, $params)[0][0];
  }

  protected function get_total_records_count($sql, $params)
  {
     // 1. get total record count
     $query = str_replace('__MA_FIELDS__', 'COUNT(*) as total', $sql);
     $query = str_replace('__MA_OFFSET__', 0, $query);
     $query = str_replace('__MA_JOIN__', '', $query);
     $query = str_replace('__MA_ORDERBY__', '', $query);

     return $this->get_row_count($query, $params);
  }

}
