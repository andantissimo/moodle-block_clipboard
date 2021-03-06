<?php
/**
 * @package   block_clipboard
 * @copyright 2019 MALU {@link https://github.com/andantissimo}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

$observers = array(
    [
        'eventname' => '\core\event\course_module_deleted',
        'callback'  => 'block_clipboard_observer::deleted',
        'internal'  => false,
        'priority'  => 1000,
    ],
);
