<?php
$tasks = array(
    array(                                                                                            
        'classname' => 'tool_trackemails\task\cron_trackemails', 
        'blocking' => 2, 
        'minute' => '*/5',
        'hour' => '*', 
        'day' => '*', 
        'dayofweek' => '*', 
        'month' => '*'
    )
);
?>