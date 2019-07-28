<?php
/**
 * @package   block_clipboard
 * @copyright 2019 MALU {@link https://github.com/andantissimo}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

$capabilities = [
    'block/clipboard:addinstance' => [
        'clonepermissionsfrom' => 'moodle/site:manageblocks',
        'riskbitmask'   => RISK_SPAM | RISK_XSS,
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_BLOCK,
        'archetypes'    => [ 'editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW ]
    ],
];
