<?php
/**
 * @package   block_clipboard
 * @copyright 2019 MALU {@link https://github.com/andantissimo}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

$functions = [
    'block_clipboard_get_tree' => [
        'classname'    => 'block_clipboard_external',
        'methodname'   => 'get_tree',
        'description'  => 'Returns a data tree',
        'type'         => 'read',
        'ajax'         => true,
        'capabilities' => 'moodle/course:manageactivities'
    ],
    'block_clipboard_star' => [
        'classname'    => 'block_clipboard_external',
        'methodname'   => 'star',
        'description'  => 'Stars an activity',
        'type'         => 'write',
        'ajax'         => true,
        'capabilities' => 'moodle/course:manageactivities'
    ],
    'block_clipboard_duplicate' => [
        'classname'    => 'block_clipboard_external',
        'methodname'   => 'duplicate',
        'description'  => 'Duplicates an activity',
        'type'         => 'write',
        'ajax'         => true,
        'capabilities' => 'moodle/course:manageactivities'
    ],
];
