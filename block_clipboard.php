<?php
/**
 * @package   block_clipboard
 * @copyright 2019 MALU {@link https://github.com/andantissimo}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

class block_clipboard extends block_base {

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

        if (!$this->page->user_is_editing())
            return $this->content = '';

        $context = context_course::instance($this->page->course->id);
        $capabilities = [
            'backup'  => has_capability('moodle/backup:backuptargetimport', $context),
            'restore' => has_capability('moodle/restore:restoretargetimport', $context),
        ];
        $this->page->requires->js_call_amd('block_clipboard/course', 'setup', [ $capabilities ]);

        $renderer = $this->page->get_renderer('core');
        $tree = block_clipboard_record::get_tree($USER->id);

        $this->content = new stdClass;
        $this->content->text = $renderer->render_from_template('block_clipboard/content', $tree);

        return $this->content;
    }
}
