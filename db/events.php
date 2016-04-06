<?php

defined('MOODLE_INTERNAL') || die;

$observers = [
    [
        'eventname' => '\core\event\course_module_deleted',
        'callback'  => 'block_favorites_observer::deleted',
        'internal'  => false,
        'priority'  => 1000,
    ],
];
