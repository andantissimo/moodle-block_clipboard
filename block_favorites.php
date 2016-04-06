<?php

defined('MOODLE_INTERNAL') || die;

class block_favorites extends block_base {

    public function init() {
        $this->title = get_string('pluginname', __CLASS__);
    }

    public function applicable_formats() {
        return [
            'course-view' => true,
        ];
    }

    /**
     * @global object $USER
     * @return object
     */
    public function get_content() {
        global $USER;

        if ($this->content !== null)
            return $this->content;

        $editing = $this->page->user_is_editing();
        $context = context_course::instance($this->page->course->id);
        $capable = has_all_capabilities([
            'moodle/course:manageactivities',
            //'moodle/backup:backuptargetimport',
            //'moodle/restore:restoretargetimport',
        ], $context);
        if (!$editing || !$capable)
            return $this->content = '';

        $this->page->requires->js_call_amd('block_favorites/icons', 'init');

        $renderer = $this->page->get_renderer('core');
        $content = block_favorites_user::from_id($USER->id)->content;

        $this->content = new stdClass;
        $this->content->text = $renderer->render_from_template('block_favorites/content', $content);

        return $this->content;
    }
}
