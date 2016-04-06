<?php

defined('MOODLE_INTERNAL') || die;

class block_favorites_observer {
    /**
     * @global moodle_database $DB
     * @param \core\event\base $event
     */
    public static function deleted(\core\event\base $event) {
        global $DB;

        $DB->delete_records('block_favorites', [ 'cmid' => $event->objectid ]);
    }
}
