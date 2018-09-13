<?php
/**
 * @package   block_favorites
 * @copyright 2018 MALU {@link https://github.com/andantissimo}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

class block_favorites_observer {
    /**
     * @global moodle_database $DB
     * @param \core\event\course_module_deleted $event
     */
    public static function deleted(\core\event\course_module_deleted $event) {
        global $DB;

        $DB->delete_records('block_favorites', [ 'cmid' => $event->objectid ]);
    }
}
