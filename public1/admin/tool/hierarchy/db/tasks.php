<?php
$tasks = array( 
    array( 
        'classname' => 'tool_hierarchy\task\importing_users', 
        'blocking' => 0, 
        'minute' => '*/10', 
        'hour' => '*',  
        'day' => '*',            
        'dayofweek' => '*',      
        'month' => '*'           
    ),
    array(
        'classname' => 'tool_hierarchy\task\import_unassign_hierarchy', 
        'blocking' => 0,         
        'minute' => '*/5',         
        'hour' => '*',           
        'day' => '*',            
        'dayofweek' => '*',      
        'month' => '*'           
    )
);
?>