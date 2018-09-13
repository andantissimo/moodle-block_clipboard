<?php
/**
 * @package   block_favorites
 * @copyright 2018 MALU {@link https://github.com/andantissimo}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

$functions = [
    'block_favorites_get_tree' => [
        'classname'    => 'block_favorites_external',
        'methodname'   => 'get_tree',
        'description'  => 'Returns a data tree',
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
