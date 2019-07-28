<?php
/**
 * @package   block_clipboard
 * @copyright 2019 MALU {@link https://github.com/andantissimo}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

class block_clipboard_observer {
    /**
     * @global moodle_database $DB
     * @param \core\event\course_module_deleted $event
     */
    public static function deleted(\core\event\course_module_deleted $event) {
        global $DB;

        $DB->delete_records('block_clipboard', [ 'cmid' => $event->objectid ]);
    }
}
