<?php

namespace tool_enrolmentemail;

require_once(__DIR__ . '/../locallib.php');
require_once(__DIR__ . '/../constants.php');

class emailmanager {

  /** @var string */
  private $subjectline;
  /** @var string */
  private $signaturename;
  /** @var string */
  private $contact;
  /** @var string */
  private $content;
  /** @var object */
  private $receiver;
  /** @var object */
  private $sender;
  /** @var string[] */
  private $placeholders = array();
  /** @var string */
  private $content_filled;
  /** @var string */
  private $subjectline_filled;

  /**
   * email class constructor
   *
   * @param   string  $subjectline    email subject line
   * @param   string  $signaturename  email signature name
   * @param   string  $contact        email contact details
   * @param   string  $content        email content
   * @param   array   $placeholders   array of [placeholder name => placeholder value] 
   */
  public function __construct($subjectline, $signaturename, $contact, $content, $placeholders = array()) {  
    $this->subjectline = $subjectline;
    $this->signaturename = $signaturename;
    $this->contact = $contact;
    $this->content = $content;
    $this->placeholders = $placeholders;
  }

  /**
   * Set placeholder
   * If name already exists in placeholders, the value will be updated
   *
   * @param   string  $name   placeholder name
   * @param   string  $value  placeholder value
   */
  public function set_placeholder($name, $value) {
    $this->placeholders[$name] = $value;
  }

  public function set_placeholders($placeholders) {
    $this->placeholders = $placeholders;
  }

  public function get_placeholders() {
    return $this->placeholders;
  }

  public function set_subjectline($subjectline) {
    $this->subjectline = $subjectline;
  }

  public function get_subjectline() {
    return $this->subjectline;
  }

  public function set_signaturename($signaturename) {
    $this->signaturename = $signaturename;
  }

  public function get_signaturename() {
    return $this->signaturename;
  }

  public function set_contact($contact) {
    $this->contact = $contact;
  }

  public function get_contact() {
    return $this->contact;
  }

  public function set_content($content) {
    $this->content = $content;
  }

  public function get_content() {
    return $this->content;
  }

  public function set_sender($sender) {
    $this->sender = $sender;
  }

  public function get_sender() {
    return $this->sender;
  }

  public function set_receiver($receiver) {
    $this->receiver = $receiver;
  }

  public function get_receiver() {
    return $this->receiver;
  }

  /**
   * Replace placeholders in content and subjectline with actual value
   */
  public function fill_in() {
    $availablenames = array(
      'firstname',
      'lastname',
      'sitename',
      'signaturename'
    );
    if (!empty($this->placeholders)) {
      $this->content_filled = $this->content;
      $this->subjectline_filled = $this->subjectline;
      foreach ($this->placeholders as $placeholder => $value) {
        $this->content_filled = str_replace("{\$$placeholder}", $value, $this->content_filled);
        // only available names are allowed
        if (in_array($placeholder, $availablenames)) {
          $this->subjectline_filled = str_replace("{\$$placeholder}", $value, $this->subjectline_filled);
        }
      }
    }
  }

  /**
   * Send email
   */
  public function send() {
    $messagehtml = $this->content_filled;
    $messagetext = html_to_text($this->content_filled);
    $ok = email_to_user($this->receiver, $this->sender, $this->subjectline_filled, $messagetext, $messagehtml);
  }
}