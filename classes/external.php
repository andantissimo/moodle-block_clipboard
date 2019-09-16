<?php
/**
 * @package   block_clipboard
 * @copyright 2019 MALU {@link https://github.com/andantissimo}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

class block_clipboard_external extends external_api {
    /**
     * @return external_function_parameters
     */
    public static function get_tree_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * @global stdClass $USER
     * @global moodle_page $PAGE
     * @return stdClass
     */
    public static function get_tree() {
        global $USER, $PAGE;

        self::validate_context(context_system::instance());

        $PAGE->set_context(context_system::instance());

        return block_clipboard_record::get_tree($USER->id);
    }

    /**
     * @return external_description
     */
    public static function get_tree_returns() {
        return new external_single_structure([
            'courses' => new external_multiple_structure(
                new external_single_structure([
                    'id'         => new external_value(PARAM_INT),
                    'shortname'  => new external_value(PARAM_RAW),
                    'activities' => new external_multiple_structure(
                        new external_single_structure([
                            'id'      => new external_value(PARAM_INT),
                            'iconurl' => new external_value(PARAM_URL),
                            'name'    => new external_value(PARAM_RAW),
                        ])
                    ),
                ])
            ),
        ]);
    }

    /**
     * @return external_function_parameters
     */
    public static function copy_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT),
        ]);
    }

    /**
     * @global stdClass $USER
     * @param int $cmid
     * @return bool
     */
    public static function copy($cmid) {
        global $USER;

        $params = self::validate_parameters(self::copy_parameters(), [ 'cmid' => $cmid ]);

        require_sesskey();

        $context = context_module::instance($params['cmid']);
        self::validate_context($context);
        require_capability('moodle/course:manageactivities', $context);

        return block_clipboard_record::insert($USER->id, $params['cmid']);
    }

    /**
     * @return external_description
     */
    public static function copy_returns() {
        return new external_value(PARAM_BOOL);
    }

    /**
     * @return external_function_parameters
     */
    public static function paste_parameters() {
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
     * @return stdClass
     */
    public static function paste($courseid, $section, $cmid) {
        global $PAGE;

        $params = self::validate_parameters(self::paste_parameters(),
            [ 'courseid' => $courseid, 'section' => $section, 'cmid' => $cmid ]);

        require_sesskey();

        $cm = get_coursemodule_from_id(null, $params['cmid'], null, false, MUST_EXIST);
        $course = get_course($params['courseid']);
        $section = get_fast_modinfo($course)->get_section_info($params['section'], MUST_EXIST);

        $course = get_course($params['courseid']);
        $context = context_course::instance($course->id);
        self::validate_context($context);

        $newcm = block_clipboard_backup::duplicate($course, $section, $cm);
        if (!$newcm)
            throw new moodle_exception('Failed to duplicate activity');

        $PAGE->set_context($context);
        $PAGE->set_url('/course/view.php', [ 'id' => $course->id ]);

        /** @var core_course_renderer $courserenderer */
        $courserenderer = $PAGE->get_renderer('core', 'course');
        $completioninfo = new completion_info($course);
        $courserenderer->course_section_cm($course, $completioninfo, $newcm, null);

        $response              = new stdClass;
        $response->cmid        = $newcm->id;
        $response->fullcontent = $courserenderer->course_section_cm_list_item($course, $completioninfo, $newcm, null);
        return $response;
    }

    /**
     * @return external_description
     */
    public static function paste_returns() {
        return new external_single_structure([
            'cmid'        => new external_value(PARAM_INT),
            'fullcontent' => new external_value(PARAM_RAW),
        ]);
    }

    /**
     * @return external_function_parameters
     */
    public static function delete_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT),
        ]);
    }

    /**
     * @global stdClass $USER
     * @param int $cmid
     * @return bool
     */
    public static function delete($cmid) {
        global $USER;

        $params = self::validate_parameters(self::delete_parameters(), [ 'cmid' => $cmid ]);

        require_sesskey();

        return block_clipboard_record::delete($USER->id, $params['cmid']);
    }

    /**
     * @return external_description
     */
    public static function delete_returns() {
        return new external_value(PARAM_BOOL);
    }
}
