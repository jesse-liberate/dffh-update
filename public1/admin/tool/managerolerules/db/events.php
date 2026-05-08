<?php
$observers = array(
    array(
        'eventname' => '\core\event\user_loggedin',
        'callback' => 'tool_managerolerules_observer::user_loggedin'
    )
);