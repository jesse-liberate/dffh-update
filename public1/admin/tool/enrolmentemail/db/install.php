<?php

require_once(__DIR__ . '/commonlib.php');

/**
  * Post installation procedure
  *
  */
function xmldb_tool_enrolmentemail_install() {
  set_default_config();
  populate_courselist();
}