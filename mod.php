<?php
/**
 * @package   block_clipboard
 * @copyright 2019 MALU {@link https://github.com/andantissimo}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require '../../config.php';
require "$CFG->libdir/externallib.php";

$copy   = optional_param('copy', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);

$url = new moodle_url('/blocks/clipboard/mod.php');
foreach (compact('copy', 'delete') as $k => $v) {
    if ($v !== 0) {
        $url->param($k, $v);
    }
}
$PAGE->set_url($url);

require_login();

if (!empty($copy)) {
    $cm = get_coursemodule_from_id(null, $copy, 0, false, MUST_EXIST);
    block_clipboard_external::copy($cm->id);
    redirect(new moodle_url('/course/view.php', [ 'id' => $cm->course ]));
}

if (!empty($delete)) {
    $cm = get_coursemodule_from_id(null, $copy, 0, false, MUST_EXIST);
    block_clipboard_external::delete($cm->id);
    redirect(new moodle_url('/course/view.php', [ 'id' => $cm->course ]));
}

redirect($CFG->wwwroot);
