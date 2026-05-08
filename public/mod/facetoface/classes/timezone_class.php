<?php


//require_once('../../lib/classes/date.php');

/**
 * Description of timezone_class
 *
 * @author david
 */
class timezone_class {
  
  // unix timestamp
  private $t;
  
  // timestring in Y-m-d H:i:s format
  private $timestring;
  
  // DateTime object
  private $dt;
  
  // user timezone name
  private $user_timezone;
  
  // user DateTimeZone object
  private $user_tz;
  
  private $user_offset;
  
  // session timezone name
  private $session_timezone;
  
  // session DateTimeZone object
  private $session_tz;
  
  private $session_offset;
  
  // server timezone name
  private $server_timezone;
  
  // server DateTimeZone object
  private $server_tz;
  
  private $server_offset;
  
  
  
  public function __construct($t,
                              $user_timezone,
                              $session_timezone,
                              $server_timezone = null) {

    if (!isset($server_timezone)) {
      $server_timezone = core_date::get_server_timezone();
    }

    if (!isset($user_timezone)) {
      $user_timezone = core_date::get_user_timezone();
    }
    
    // normalise timezone, 99 to convert to actual timezone text value
    $server_timezone = core_date::normalise_timezone($server_timezone);
    $user_timezone = core_date::normalise_timezone($user_timezone);
    $session_timezone = core_date::normalise_timezone($session_timezone);

    $this->server_timezone  = $server_timezone;   
    $this->server_tz  = new DateTimeZone($this->server_timezone);
    $this->t          = $t;
    $this->timestring = $this->toTimeString($this->t);
    $this->dt = new DateTime($this->timestring, new DateTimeZone($this->server_timezone));    
    $this->server_offset  = $this->server_tz->getOffset($this->dt);
    
    $this->setSessionTimezone($session_timezone);
    $this->setUserTimezone($user_timezone);
  }
  
  private function setSessionTimezone($timezone) {
    $this->session_timezone = $timezone;
    $this->session_tz       = new DateTimeZone($this->session_timezone);
    $this->session_offset   = $this->session_tz->getOffset($this->dt);
  }
  
  private function setUserTimezone($timezone) {
    $this->user_timezone = $timezone;
    $this->user_tz       = new DateTimeZone($this->user_timezone);
    $this->user_offset   = $this->user_tz->getOffset($this->dt);
  }  
 
  private function toTimeString($timestamp, $format = 'Y-m-d H:i:s') {
    $dt = new DateTime("now", new DateTimeZone($this->server_timezone));
    $dt->setTimestamp($timestamp);
    return $dt->format($format);
  }
  
  public function getSessiontime($format = 'Y-m-d H:i:s') {
    $dt = new DateTime("now", new DateTimeZone($this->server_timezone));
    $dt->setTimestamp($this->t);
    return $dt->format($format);
  }
  
  public function getUsertime($format = 'Y-m-d H:i:s') {
    $dt = new DateTime("now", new DateTimeZone($this->server_timezone));
    $dt->setTimestamp(
      $this->t
      - ($this->server_offset - $this->user_offset)
      + ($this->server_offset - $this->session_offset)
    );
    return $dt->format($format);
  }
  
  public function getSessiontimezone() {
    return $this->session_timezone;
  }
  
  public function getUsertimezone() {
    return $this->user_timezone;
  }

  public function getServertimezone() {
    return $this->server_timezone;
  }
}

