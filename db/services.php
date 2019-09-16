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
    'block_clipboard_copy' => [
        'classname'    => 'block_clipboard_external',
        'methodname'   => 'copy',
        'description'  => 'Copies an activity to the Clipboard',
        'type'         => 'write',
        'ajax'         => true,
        'capabilities' => 'moodle/course:manageactivities'
    ],
    'block_clipboard_paste' => [
        'classname'    => 'block_clipboard_external',
        'methodname'   => 'paste',
        'description'  => 'Pastes an activity from the Clipboard',
        'type'         => 'write',
        'ajax'         => true,
        'capabilities' => 'moodle/course:manageactivities'
    ],
    'block_clipboard_delete' => [
        'classname'    => 'block_clipboard_external',
        'methodname'   => 'delete',
        'description'  => 'Deletes an activity from the Clipboard',
        'type'         => 'write',
        'ajax'         => true,
        'capabilities' => 'moodle/course:manageactivities'
    ],
];
