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
     * @return object
     */
    public static function content() {
        global $USER;

        self::validate_context(context_system::instance());

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
     * @global object $CFG
     * @global object $USER
     * @global moodle_page $PAGE
     * @param int $courseid
     * @param int $section
     * @param int $cmid
     * @return string
     */
    public static function duplicate($courseid, $section, $cmid) {
        global $CFG, $USER, $PAGE;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        require_once($CFG->libdir . '/filelib.php');

        $params = self::validate_parameters(self::duplicate_parameters(),
            [ 'courseid' => $courseid, 'section' => $section, 'cmid' => $cmid ]);

        $cm = get_coursemodule_from_id(null, $params['cmid'], null, false, MUST_EXIST);
        $course = get_course($params['courseid']);
        $section = get_fast_modinfo($course)->get_section_info($params['section'], MUST_EXIST);
        $backupcontext = context_course::instance($cm->course);
        $restorecontext = context_course::instance($course->id);
        self::validate_context($restorecontext);
        require_capability('moodle/course:manageactivities', $restorecontext);
        require_capability('moodle/restore:restoretargetimport', $restorecontext);
        require_capability('moodle/backup:backuptargetimport', $backupcontext);
        if (!course_allowed_module($course, $cm->modname))
            throw new moodle_exception('No permission to create that activity');

        if (!plugin_supports('mod', $cm->modname, FEATURE_BACKUP_MOODLE2)) {
            $a          = new stdClass;
            $a->modtype = get_string('modulename', $cm->modname);
            $a->modname = format_string($cm->name);
            throw new moodle_exception('duplicatenosupport', 'error', '', $a);
        }

        $bc = new backup_controller(backup::TYPE_1ACTIVITY, $cm->id, backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id);
        $backupid       = $bc->get_backupid();
        $backupbasepath = $bc->get_plan()->get_basepath();
        $bc->execute_plan();
        $bc->destroy();

        $rc = new restore_controller($backupid, $course->id,
            backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id, backup::TARGET_EXISTING_ADDING);
        $cmcontext = context_module::instance($cm->id);
        if (!$rc->execute_precheck()) {
            $precheckresults = $rc->get_precheck_results();
            if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
                if (empty($CFG->keeptempdirectoriesonbackup)) {
                    fulldelete($backupbasepath);
                }
            }
        }
        $rc->execute_plan();

        $newcm = null;
        $tasks = $rc->get_plan()->get_tasks();
        foreach ($tasks as $task) {
            if ($task instanceof restore_activity_task) {
                if ($task->get_old_contextid() == $cmcontext->id) {
                    $newcmid = $task->get_moduleid();
                    $newcm = get_fast_modinfo($course)->get_cm($newcmid);
                    break;
                }
            }
        }
        if ($newcm) {
            moveto_module($newcm, $section);
            $event = \core\event\course_module_created::create_from_cm($newcm);
            $event->trigger();
        }
        rebuild_course_cache($course->id);

        $rc->destroy();

        if (empty($CFG->keeptempdirectoriesonbackup)) {
            fulldelete($backupbasepath);
        }

        if (!$newcm)
            throw new moodle_exception('Failed to duplicate activity');

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
