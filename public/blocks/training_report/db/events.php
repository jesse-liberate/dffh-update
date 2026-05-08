<?php

$observers = [
    [
        'eventname' => '\core\event\course_module_completion_updated',
        'callback' => '\block_training_report\observer::course_module_completion_updated',
    ],
    [
        'eventname' => '\core\event\course_module_created',
        'callback' => '\block_training_report\observer::course_module_created',
    ],
    [
        'eventname' => '\core\event\course_module_deleted',
        'callback' => '\block_training_report\observer::course_module_deleted',
    ],
    [
        'eventname' => '\core\event\course_module_updated',
        'callback' => '\block_training_report\observer::course_module_updated',
    ],
    [
        'eventname' => '\core\event\course_deleted',
        'callback' => '\block_training_report\observer::course_deleted',
    ],
    [
        'eventname' => '\core\event\role_assigned',
        'callback' => '\block_training_report\observer::role_assigned',
    ],
    [
        'eventname' => '\core\event\role_unassigned',
        'callback' => '\block_training_report\observer::role_unassigned',
    ],
    [
        'eventname' => '\core\event\user_enrolment_created',
        'callback' => '\block_training_report\observer::user_enrolment_created',
    ],
    [
        'eventname' => '\core\event\user_enrolment_deleted',
        'callback' => '\block_training_report\observer::user_enrolment_deleted',
    ],
    [
        'eventname' => '\core\event\user_enrolment_updated',
        'callback' => '\block_training_report\observer::user_enrolment_updated',
    ],
    [
        'eventname' => '\core\event\course_category_updated',
        'callback' => '\block_training_report\observer::course_category_updated',
    ],
    [
        'eventname' => '\core\event\course_updated',
        'callback' => '\block_training_report\observer::course_updated',
    ],
];
