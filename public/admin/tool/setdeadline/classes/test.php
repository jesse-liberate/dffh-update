<?php
require(__DIR__.'/../../../config.php');
require_once('mindatlas_setdeadline_library.php');

$test = new mindatlas_setdeadline_library();

// JN: Below method is removed as of 2021-02-15 because the function
// does not get overdue courses but only get courses with due dates
$data = $test->get_user_courses_has_due();
echo "<pre>".print_r($data,true)."</pre>";