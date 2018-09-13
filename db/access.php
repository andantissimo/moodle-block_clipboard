<?php
/**
 * @package   block_favorites
 * @copyright 2018 MALU {@link https://github.com/andantissimo}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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
