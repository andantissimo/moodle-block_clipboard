<?php

defined('MOODLE_INTERNAL') || die;

class block_favorites extends block_base {

    public function init() {
        $this->title = get_string('pluginname', __CLASS__);
    }

    public function applicable_formats() {
        return [
            'site-index'  => true,
            'course-view' => true,
        ];
    }

    public function get_content() {
        if ($this->content !== null)
            return $this->content;

        $editing = $this->page->user_is_editing();
        $context = context_course::instance($this->page->course->id);
        $capable = has_all_capabilities([
            'moodle/course:manageactivities',
            //'moodle/backup:backupactivity',
            //'moodle/restore:restoreactivity',
        ], $context);
        if (!$editing || !$capable)
            return $this->content = '';

        /* @var $renderer block_favorites_renderer */
        $renderer = $this->page->get_renderer(__CLASS__);

        $this->page->requires->js_call_amd('block_favorites/icons', 'init');

        $this->content = new stdClass;
        $this->content->text = $renderer->block_content();

        return $this->content;
    }
}
