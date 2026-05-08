<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace block_reporting\report;

use \PDO;

/**
 * Description of db_class
 *
 * @author david
 */
class db_class {
  private static $instance = null;
  private $conn;
  
  private function __construct() {
    global $CFG;
    $this->conn = new \PDO("mysql:host=$CFG->dbhost;dbname=$CFG->dbname", $CFG->dbuser, $CFG->dbpass);
    $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }
  
  public static function getInstance() {
    if(!self::$instance)
    {
      self::$instance = new db_class();
    }
   
    return self::$instance;
  }
  
  public function getConnection() {
    return $this->conn;
  }  
}
