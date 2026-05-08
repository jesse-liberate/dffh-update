<?php
// Include required libary files
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require($CFG->dirroot.'/cohort/lib.php');
require_once('lib.php');

generate_cohort_rule_based_profile_fields();