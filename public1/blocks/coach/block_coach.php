<?php
require_once(dirname(__FILE__). '/../../config.php');
require_once('lib.php');

class block_coach extends block_base {
    public function init() {
    	$context_system = context_system::instance();
    	$is_student = has_capability('block/coach:isstudent',$context_system);
    	if($is_student) $this->title = get_string('coach:student','block_coach');
	  	else $this->title = get_string('coach:coach','block_coach');
    }

	public function get_content() {
	 	global $DB, $USER,$CFG;

	 	if ($this->content !== null) {
			return $this->content;
	 	}
		$content = '';
		
		if (is_supervising($USER->id)) {
			$context_system = context_system::instance();
	    	$is_student = has_capability('block/coach:isstudent',$context_system);
	    	$title = get_string('coach:coach','block_coach');
	    	if($is_student) $title = get_string('coach:student','block_coach');

			$content .="<div class='coach'><ul>
				<li><a href='".$CFG->wwwroot."/blocks/coach/index.php'>".$title."</a></li>
				</ul></div>";			
		}

		$this->content = new stdClass;
		$this->content->text = $content;
		return $this->content;
	}
}
?>
