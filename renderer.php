<?php
/**
 * @package   block_clipboard
 * @copyright 2019 MALU {@link https://github.com/andantissimo}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

class block_clipboard_renderer extends plugin_renderer_base {
    /**
     * Renders an action_menu_link item.
     *
     * @param action_menu_link $action
     * @return string HTML fragment
     */
    public function render_action_menu_link(action_menu_link $action) {
        $menu = new action_menu();
        $action->add_class('cm-edit-action');
        $menu->add($action);
        return $this->render_from_template('core/action_menu_link', $action->export_for_template($this));
    }
}
