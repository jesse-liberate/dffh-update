<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = array(
        'block/coach:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_ALLOW
        ),
 
        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),
    'block/coach:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),

    'block/coach:viewaddupdatemodule' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'user' => CAP_ALLOW
        )
    ),

    'block/coach:viewdeletemodule' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'user' => CAP_ALLOW
        )
    ),

    'block/coach:addlearningplan' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'manager' => CAP_ALLOW
        )
    ),
    'block/coach:isstudent' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'manager' => CAP_ALLOW
        )
    ),
    'block/coach:viewteamlearningplan' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'manager' => CAP_ALLOW
        )
    ),
    'block/coachee:iscoachee' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
           'manager' => CAP_ALLOW
        )
    ),
    'block/coach:assign_coaches_to_users' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
    ),
    'block/coach:manage_coaches' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM
    )
);