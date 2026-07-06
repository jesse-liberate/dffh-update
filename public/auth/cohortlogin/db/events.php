<?php
$observers = array(
    array(
        'eventname' => '\core\event\user_loggedin',
        'callback' => 'auth_cohortlogin_userlogin_observer::observe_userlogin',
        'includefile' => 'auth/cohortlogin/lib.php'
    )
);