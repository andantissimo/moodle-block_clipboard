<?php

defined('MOODLE_INTERNAL') || die;

$functions = [
    'block_favorites_content' => [
        'classname'    => 'block_favorites_external',
        'methodname'   => 'content',
        'description'  => 'Returns a block content',
        'type'         => 'read',
        'ajax'         => true,
        'capabilities' => 'moodle/course:manageactivities'
    ],
    'block_favorites_star' => [
        'classname'    => 'block_favorites_external',
        'methodname'   => 'star',
        'description'  => 'Stars an activity',
        'type'         => 'write',
        'ajax'         => true,
        'capabilities' => 'moodle/course:manageactivities'
    ],
    'block_favorites_duplicate' => [
        'classname'    => 'block_favorites_external',
        'methodname'   => 'duplicate',
        'description'  => 'Duplicates an activity',
        'type'         => 'write',
        'ajax'         => true,
        'capabilities' => 'moodle/course:manageactivities'
    ],
];
