<?php

defined('MOODLE_INTERNAL') || die;

class block_favorites_renderer extends plugin_renderer_base {
    /**
     * @global moodle_database $DB
     * @global object $USER
     * @param int $userid
     * @return string
     */
    public function block_content($userid = 0) {
        global $DB, $USER;

        $favs = $DB->get_records_sql_menu(
            "SELECT cm.id, cm.course
               FROM {course_modules} cm
               JOIN {course} c ON c.id = cm.course
               JOIN {course_sections} cs ON cs.id = cm.section
               JOIN {block_favorites} fav ON fav.cmid = cm.id
              WHERE fav.userid = :userid
           ORDER BY c.sortorder, cs.section",
            [ 'userid' => $userid ?: $USER->id ]);

        $html  = html_writer::start_tag('ul', [ 'class' => 'block_tree list' ]);
        $html .= html_writer::start_tag('li', [ 'class' => 'type_unknown depth_1 contains_branch' ]);
        $html .= html_writer::start_tag('ul');
        foreach (array_unique(array_values($favs)) as $courseid) {
            $modinfo = course_modinfo::instance($courseid);
            $html .= html_writer::start_tag('li', [ 'class' => 'type_course depth_2' ]);
            $html .= $this->tree_item_course($modinfo->get_course());
            $html .= html_writer::start_tag('ul');
            foreach ($modinfo->sections as $cmids) {
                $cmids = array_filter($cmids, function ($cmid) use (&$favs) { return isset($favs[$cmid]); });
                foreach ($cmids as $cmid) {
                    $html .= html_writer::start_tag('li', [
                        'class' => "type_activity depth_3 item_with_icon fav-{$cmid}"
                        ]);
                    $html .= $this->tree_item_cm($modinfo->cms[$cmid]);
                    $html .= html_writer::end_tag('li');
                }
            }
            $html .= html_writer::end_tag('ul');
            $html .= html_writer::end_tag('li');
        }
        $html .= html_writer::end_tag('ul');
        $html .= html_writer::end_tag('li');
        $html .= html_writer::end_tag('ul');
        
        return html_writer::div($html, 'block_navigation');
    }

    /**
     * @global object $CFG
     * @param object $course
     * @return string
     */
    public function tree_item_course($course) {
        global $CFG;
        $shortname = $course->id == SITEID && empty($CFG->usesitenameforsitepages)
            ? get_string('sitepages')
            : format_string($course->shortname);
        return html_writer::tag('p', $shortname, [ 'class' => 'tree_item branch' ]);
    }

    /**
     * @param cm_info $cm
     * @return string
     */
    public function tree_item_cm(cm_info $cm) {
        $content = format_string($cm->name, true, [ 'context' => context_module::instance($cm->id) ]);
        $iconurl = $cm->icon
            ? $this->page->theme->pix_url($cm->icon, $cm->iconcomponent)
            : $this->page->theme->pix_url('icon', $cm->modname);
        $item = html_writer::img($iconurl, get_string('modulename', $cm->modname), [ 'class' => 'smallicon navicon' ])
              . html_writer::span($content, 'item-content-wrap');
        return html_writer::tag('p', $item, [ 'class' => 'tree_item leaf hasicon' ]);
    }
}
