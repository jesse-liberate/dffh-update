<?php
$tasks = array(
    array( 
        'classname' => 'tool_cohortenrolmentrules\task\create_allusers_cohort', 
        'blocking' => 0, 
        'minute' => '25', 
        'hour' => '*/1',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
    array( 
        'classname' => 'tool_cohortenrolmentrules\task\generate_update_cohort_rules', 
        'blocking' => 0, 
        'minute' => '25', 
        'hour' => '0',
        'day' => '*/1',
        'dayofweek' => '*',
        'month' => '*'
    ),
);
