<?php
/**
 * @package   block_clipboard
 * @copyright 2019 MALU {@link https://github.com/andantissimo}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once $CFG->dirroot . '/backup/util/includes/backup_includes.php';
require_once $CFG->dirroot . '/backup/util/includes/restore_includes.php';
require_once $CFG->libdir . '/filelib.php';

class block_clipboard_backup {
    /**
     * @global stdClass $CFG
     * @global stdClass $USER
     * @param stdClass $course
     * @param stdClass $section
     * @param stdClass $cm
     * @return cm_info|null
     */
    public static function duplicate($course, $section, $cm) {
        global $CFG, $USER;

        $backupcontext = context_course::instance($cm->course);
        $restorecontext = context_course::instance($course->id);
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

        return $newcm;
    }
}
