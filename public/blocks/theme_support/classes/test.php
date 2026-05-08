<?php
require(__DIR__.'/../../../config.php');
require_once('mindatlas_theme_library.php');

$test = new mindatlas_theme_library();
$payload = [
	'sesskey'=>$USER->sesskey,
	'userid'=>$USER->id
	// 'courseId' => 2
];
// $data = $test->get_user_learning_progress($payload);
// $data = $test->get_user_badges($payload);
$data = $test->get_user_learning_progress($payload);
echo "<pre>".print_r($data,true)."</pre>";

$data = $test->get_user_course_summary($payload);
echo "<pre>".print_r($data,true)."</pre>";