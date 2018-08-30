<?php

defined('MOODLE_INTERNAL') || die;

/**
 * Hook called to check whether async course module deletion should be performed or not.
 *
 * @return false to pass through.
 */
function block_favorites_course_module_background_deletion_recommended() {
    // course_module_deleted event won't be triggered immediately
    // when the course recycle bin is enabled.
    if (class_exists('\tool_recyclebin\course_bin') && \tool_recyclebin\course_bin::is_enabled()) {
        // find $cmid argument from callstack:
        // function course_delete_module($cmid, $async)
        foreach (array_slice(debug_backtrace(0, 3), 1) as $bt) {
            if ($bt['function'] === 'course_delete_module' and list($cmid) = $bt['args']) {
                // invoke the course module deletion obeserver
                // to remove the item from favorites
                $cm = get_coursemodule_from_id(null, $cmid, 0, false, MUST_EXIST);
                $event = \core\event\course_module_deleted::create([
                    'courseid' => $cm->course,
                    'context'  => context_module::instance($cm->id),
                    'objectid' => $cm->id,
                    'other'    => [
                        'modulename' => $cm->name,
                        'instanceid' => $cm->instance,
                    ]
                ]);
                block_favorites_observer::deleted($event);
                break;
            }
        }
    }
    return false;
}
