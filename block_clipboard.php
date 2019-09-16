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
        /** @var block_clipboard_renderer $renderer */
        $renderer = $this->page->get_renderer('block_clipboard');

        $capabilities = [
            'backup'  => has_capability('moodle/backup:backuptargetimport', $context),
            'restore' => has_capability('moodle/restore:restoretargetimport', $context),
        ];
        $actions = [
            'copytoclipboard' => $renderer->render_action_menu_link(
                new action_menu_link_secondary(
                    new moodle_url('/blocks/clipboard/mod.php'),
                    new pix_icon('e/paste', '', 'moodle', [ 'class' => 'iconsmall' ]),
                    get_string('copytoclipboard', 'block_clipboard'),
                    [ 'class' => 'editing_copytoclipboard', 'data-action' => 'copytoclipboard' ]
                )
            ),
        ];
        $this->page->requires->js_call_amd('block_clipboard/course', 'setup', [ $capabilities, $actions ]);

        $this->content = new stdClass;
        $this->content->text = '';

        return $this->content;
    }
}
