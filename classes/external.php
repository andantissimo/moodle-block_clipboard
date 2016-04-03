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
     * @global moodle_page $PAGE
     * @global object $USER
     * @return string
     */
    public static function content() {
        global $PAGE, $USER;

        /* @var $renderer block_favorites_renderer */
        $renderer = $PAGE->get_renderer('block_favorites');
        return $renderer->block_content($USER->id);
    }

    /**
     * @return external_value
     */
    public static function content_returns() {
        return new external_value(PARAM_RAW);
    }

    /**
     * @return external_function_parameters
     */
	public static function star_parameters() {
		return new external_function_parameters([
			'cmid' => new external_value(PARAM_INT),
		]);
	}

    /**
     * @global moodle_database $DB
     * @global object $USER
     * @param int $cmid
     * @return boolean
     */
	public static function star($cmid) {
        global $DB, $USER;

        $params = self::validate_parameters(self::star_parameters(), [ 'cmid' => $cmid ]);
        $cm = get_coursemodule_from_id(null, $params['cmid'], 0, false, MUST_EXIST);
        if ($DB->record_exists('block_favorites', [ 'userid' => $USER->id, 'cmid' => $cm->id ])) {
            $DB->delete_records('block_favorites', [ 'userid' => $USER->id, 'cmid' => $cm->id ]);
            return false;
        }
        $DB->insert_record('block_favorites', [ 'userid' => $USER->id, 'cmid' => $cm->id, 'timecreated' => time() ]);
        return true;
	}

    /**
     * @return external_value
     */
    public static function star_returns() {
        return new external_value(PARAM_BOOL);
    }
}
