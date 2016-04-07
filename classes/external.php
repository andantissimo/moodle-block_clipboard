<?php

defined('MOODLE_INTERNAL') || die;

class block_favorites_external extends external_api {
    /**
     * @return external_function_parameters
     */
    public static function content_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * @global object $USER
     * @global moodle_page $PAGE
     * @return object
     */
    public static function content() {
        global $USER, $PAGE;

        self::validate_context(context_system::instance());

        $PAGE->set_context(context_system::instance());

        return block_favorites_user::from_id($USER->id)->content;
    }

    /**
     * @return external_description
     */
    public static function content_returns() {
        return new external_single_structure([
            'courses' => new external_multiple_structure(
                new external_single_structure([
                    'id'         => new external_value(PARAM_INT),
                    'shortname'  => new external_value(PARAM_RAW),
                    'activities' => new external_multiple_structure(
                        new external_single_structure([
                            'id'      => new external_value(PARAM_INT),
                            'iconurl' => new external_value(PARAM_URL),
                            'content' => new external_value(PARAM_RAW),
                        ])
                    ),
                ])
            ),
        ]);
    }

    /**
     * @return external_function_parameters
     */
    public static function star_parameters() {
        return new external_function_parameters([
            'cmid'    => new external_value(PARAM_INT),
            'starred' => new external_value(PARAM_BOOL),
        ]);
    }

    /**
     * @global object $USER
     * @param int $cmid
     * @param boolean $starred
     * @return boolean
     */
    public static function star($cmid, $starred) {
        global $USER;

        $params = self::validate_parameters(self::star_parameters(), [ 'cmid' => $cmid, 'starred' => $starred ]);

        require_sesskey();

        $context = context_module::instance($params['cmid']);
        self::validate_context($context);
        require_capability('moodle/course:manageactivities', $context);

        $user = block_favorites_user::from_id($USER->id);
        if ($params['starred']) {
            $user->starred($params['cmid']) || $user->star($params['cmid']);
        } else {
            $user->unstar($params['cmid']);
        }
        return true;
    }

    /**
     * @return external_description
     */
    public static function star_returns() {
        return new external_value(PARAM_BOOL);
    }

    /**
     * @return external_function_parameters
     */
    public static function duplicate_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT),
            'section'  => new external_value(PARAM_INT),
            'cmid'     => new external_value(PARAM_INT),
        ]);
    }

    /**
     * @global moodle_page $PAGE
     * @param int $courseid
     * @param int $section
     * @param int $cmid
     * @return string
     */
    public static function duplicate($courseid, $section, $cmid) {
        global $PAGE;

        $params = self::validate_parameters(self::duplicate_parameters(),
            [ 'courseid' => $courseid, 'section' => $section, 'cmid' => $cmid ]);

        require_sesskey();

        $cm = get_coursemodule_from_id(null, $params['cmid'], null, false, MUST_EXIST);
        $course = get_course($params['courseid']);
        $section = get_fast_modinfo($course)->get_section_info($params['section'], MUST_EXIST);

        $course = get_course($params['courseid']);
        $context = context_course::instance($course->id);
        self::validate_context($context);

        $newcm = block_favorites_backup::duplicate($course, $section, $cm);
        if (!$newcm)
            throw new moodle_exception('Failed to duplicate activity');

        $PAGE->set_context($context);
        $PAGE->set_url('/course/view.php', [ 'id' => $course->id ]);

        /* @var $courserenderer core_course_renderer */
        $courserenderer = $PAGE->get_renderer('core', 'course');
        $completioninfo = new completion_info($course);
        $courserenderer->course_section_cm($course, $completioninfo, $newcm, null);
        $content = new stdClass;
        $content->cmid        = $newcm->id;
        $content->fullcontent = $courserenderer->course_section_cm_list_item($course, $completioninfo, $newcm, null);

        return $content;
    }

    /**
     * @return external_description
     */
    public static function duplicate_returns() {
        return new external_single_structure([
            'cmid'        => new external_value(PARAM_INT),
            'fullcontent' => new external_value(PARAM_RAW),
        ]);
    }
}
