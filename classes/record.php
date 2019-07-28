<?php
/**
 * @package   block_clipboard
 * @copyright 2019 MALU {@link https://github.com/andantissimo}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

class block_clipboard_record {
    /**
     * @global moodle_database $DB
     * @param int $userid
     * @return stdClass
     */
    public static function get_tree(int $userid) {
        global $DB;

        $favs = $DB->get_records_sql_menu(
            "SELECT cm.id, cm.course
               FROM {course_modules} cm
               JOIN {course} c ON c.id = cm.course
               JOIN {block_clipboard} fav ON fav.cmid = cm.id
              WHERE fav.userid = :userid
           ORDER BY c.sortorder",
            [ 'userid' => $userid ]);
        list ($favcmids, $favcourseids) = [ array_keys($favs), array_unique(array_values($favs)) ];

        $tree = new stdClass;
        $tree->courses = [];
        foreach ($favcourseids as $courseid) {
            $modinfo = course_modinfo::instance($courseid);
            $course             = new stdClass;
            $course->id         = $modinfo->courseid;
            $course->shortname  = format_string($modinfo->get_course()->shortname);
            $course->activities = [];
            foreach ($modinfo->sections as $cmids) {
                foreach (array_intersect($cmids, $favcmids) as $cmid) {
                    $cm = $modinfo->cms[$cmid];
                    $activity          = new stdClass;
                    $activity->id      = $cm->id;
                    $activity->iconurl = $cm->get_icon_url()->out(false);
                    $activity->name    = format_string($cm->name, true, [ 'context' => $cm->context ]);
                    $course->activities[] = $activity;
                }
            }
            $tree->courses[] = $course;
        }
        return $tree;
    }

    /**
     * @global moodle_database $DB
     * @param int $userid
     * @param int $cmid
     * @return bool
     */
    public static function starred(int $userid, int $cmid) {
        global $DB;
        return $DB->record_exists('block_clipboard', [ 'userid' => $userid, 'cmid' => $cmid ]);
    }

    /**
     * @global moodle_database $DB
     * @param int $userid
     * @param int $cmid
     */
    public static function star(int $userid, int $cmid) {
        global $DB;
        if (!self::starred($userid, $cmid)) {
            $DB->insert_record('block_clipboard', [ 'userid' => $userid, 'cmid' => $cmid, 'timecreated' => time() ]);
        }
    }

    /**
     * @global moodle_database $DB
     * @param int $userid
     * @param int $cmid
     */
    public static function unstar(int $userid, int $cmid) {
        global $DB;
        $DB->delete_records('block_clipboard', [ 'userid' => $userid, 'cmid' => $cmid ]);
    }
}
