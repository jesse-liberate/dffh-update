<?php
$tasks = array(  
    array(  
        'classname' => 'block_reportwizard\task\insert_report_wzd_completion_table', 
        'blocking' => 0, 
        'minute' => '*/5',  
        'hour' => '*', 
        'day' => '*',  
        'dayofweek' => '*', 
        'month' => '*'  
    ),
    array(  
        'classname' => 'block_reportwizard\task\insert_report_wzd_completion_table_full', 
        'blocking' => 0, 
        'minute' => '5',  
        'hour' => '2', 
        'day' => '*',  
        'dayofweek' => '*', 
        'month' => '*'  
    ),
    array(  
        'classname' => 'block_reportwizard\task\report_wzd_schedule', 
        'blocking' => 0, 
        'minute' => '30',  
        'hour' => '2', 
        'day' => '*',  
        'dayofweek' => '*', 
        'month' => '*'  
    )
);