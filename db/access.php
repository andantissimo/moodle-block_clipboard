<?php

defined('MOODLE_INTERNAL') || die;

$capabilities = [
    'block/favorites:addinstance' => [
        'clonepermissionsfrom' => 'moodle/site:manageblocks',
        'riskbitmask'   => RISK_SPAM | RISK_XSS,
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_BLOCK,
        'archetypes'    => [ 'editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW ]
    ],
];
