<?php
/**
 * @package   block_favorites
 * @copyright 2018 MALU {@link https://github.com/andantissimo}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

class block_favorites_record {
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
               JOIN {block_favorites} fav ON fav.cmid = cm.id
              WHERE fav.userid = :userid
           ORDER BY c.sortorder",
            [ 'userid' => $userid ]);

        $tree = new stdClass;
        $tree->courses = [];
        foreach (array_unique(array_values($favs)) as $courseid) {
            $modinfo = course_modinfo::instance($courseid);
            $course             = new stdClass;
            $course->id         = $modinfo->courseid;
            $course->viewurl    = (string)new moodle_url('/course/view.php', [ 'id' => $courseid ]);
            $course->shortname  = format_string($modinfo->get_course()->shortname);
            $course->activities = [];
            foreach ($modinfo->sections as $cmids) {
                $cmids = array_filter($cmids, function ($cmid) use (&$favs) { return isset($favs[$cmid]); });
                foreach ($cmids as $cmid) {
                    $cm = $modinfo->cms[$cmid];
                    $activity          = new stdClass;
                    $activity->id      = $cm->id;
                    $activity->iconurl = self::get_cm_icon_url($cm)->out();
                    $activity->content = format_string($cm->name, true, [ 'context' => $cm->context ]);
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
        return $DB->record_exists('block_favorites', [ 'userid' => $userid, 'cmid' => $cmid ]);
    }

    /**
     * @global moodle_database $DB
     * @param int $userid
     * @param int $cmid
     */
    public static function star(int $userid, int $cmid) {
        global $DB;
        if (!self::starred($userid, $cmid)) {
            $DB->insert_record('block_favorites', [ 'userid' => $userid, 'cmid' => $cmid, 'timecreated' => time() ]);
        }
    }

    /**
     * @global moodle_database $DB
     * @param int $userid
     * @param int $cmid
     */
    public static function unstar(int $userid, int $cmid) {
        global $DB;
        $DB->delete_records('block_favorites', [ 'userid' => $userid, 'cmid' => $cmid ]);
    }

    /**
     * @global moodle_page $PAGE
     * @param cm_info $cm
     * @return moodle_url
     */
    private static function get_cm_icon_url(cm_info $cm) {
        global $PAGE;
        return $cm->icon
            ? $PAGE->theme->image_url($cm->icon, $cm->iconcomponent)
            : $PAGE->theme->image_url('icon', $cm->modname);
    }
}
