<?php

defined('MOODLE_INTERNAL') || die;

/**
 * @property-read int $id
 * @property-read object $content
 */
class block_favorites_user {
    /**
     * @var int
     */
    private $id;

    /**
     * @param int $id
     * @return block_favorites_user
     */
    public static function from_id($id) {
        return new self($id);
    }

    /**
     * @param int $id
     */
    private function __construct($id) {
        $this->id = $id;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        $method = "get_{$name}";
        if (!method_exists($this, $method))
            throw new coding_exception("Unknown property {$name}");
        return $this->$method();
    }

    /**
     * @return int
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * @global object $CFG
     * @global moodle_database $DB
     * @global moodle_page $PAGE
     * @return object
     */
    public function get_content() {
        global $CFG, $DB, $PAGE;

        $favs = $DB->get_records_sql_menu(
            "SELECT cm.id, cm.course
               FROM {course_modules} cm
               JOIN {course} c ON c.id = cm.course
               JOIN {course_sections} cs ON cs.id = cm.section
               JOIN {block_favorites} fav ON fav.cmid = cm.id
              WHERE fav.userid = :userid
           ORDER BY c.sortorder, cs.section",
            [ 'userid' => $this->id ]);

        $content = new stdClass;
        $content->courses = [];
        foreach (array_unique(array_values($favs)) as $courseid) {
            $modinfo = course_modinfo::instance($courseid);
            $course             = new stdClass;
            $course->id         = $modinfo->courseid;
            $course->shortname  = $modinfo->courseid == SITEID && empty($CFG->usesitenameforsitepages)
                ? get_string('sitepages')
                : format_string($modinfo->get_course()->shortname);
            $course->activities = [];
            foreach ($modinfo->sections as $cmids) {
                $cmids = array_filter($cmids, function ($cmid) use (&$favs) { return isset($favs[$cmid]); });
                foreach ($cmids as $cmid) {
                    $cm = $modinfo->cms[$cmid];
                    $activity          = new stdClass;
                    $activity->id      = $cm->id;
                    $activity->iconurl = $cm->icon
                        ? (string)$PAGE->theme->pix_url($cm->icon, $cm->iconcomponent)
                        : (string)$PAGE->theme->pix_url('icon', $cm->modname);
                    $activity->content = format_string($cm->name, true, [ 'context' => $cm->context ]);
                    $course->activities[] = $activity;
                }
            }
            $content->courses[] = $course;
        }
        return $content;
    }

    /**
     * @global moodle_database $DB
     * @param int $cmid
     * @return boolean
     */
    public function starred($cmid) {
        global $DB;
        return $DB->record_exists('block_favorites', [ 'userid' => $this->id, 'cmid' => $cmid ]);
    }

    /**
     * @global moodle_database $DB
     * @param int $cmid
     */
    public function star($cmid) {
        global $DB;
        $DB->insert_record('block_favorites', [ 'userid' => $this->id, 'cmid' => $cmid, 'timecreated' => time() ]);
    }

    /**
     * @global moodle_database $DB
     * @param int $cmid
     */
    public function unstar($cmid) {
        global $DB;
        $DB->delete_records('block_favorites', [ 'userid' => $this->id, 'cmid' => $cmid ]);
    }
}
