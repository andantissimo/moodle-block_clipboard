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
     * @global object $USER
     * @return object
     */
    public static function content() {
        global $USER;
        return block_favorites_user::from_id($USER->id)->content;
    }

    /**
     * @return external_value
     */
    public static function content_returns() {
        return new external_single_structure([
            'courses' => new external_multiple_structure(
                new external_single_structure([
                    'id'         => new external_value(PARAM_INT),
                    'shortname'  => new external_value(PARAM_RAW),
                    'activities' => new external_multiple_structure(
                        new external_single_structure([
                            'id'      => new external_value(PARAM_INT),
                            'iconurl' => new external_value(PARAM_URL),
                            'content' => new external_value(PARAM_RAW),
                        ])
                    ),
                ])
            ),
        ]);
    }

    /**
     * @return external_function_parameters
     */
    public static function star_parameters() {
        return new external_function_parameters([
            'cmid'    => new external_value(PARAM_INT),
            'starred' => new external_value(PARAM_BOOL),
        ]);
    }

    /**
     * @global object $USER
     * @param int $cmid
     * @param boolean $starred
     * @return boolean
     */
    public static function star($cmid, $starred) {
        global $USER;

        $params = self::validate_parameters(self::star_parameters(), [ 'cmid' => $cmid, 'starred' => $starred ]);

        $cm = get_coursemodule_from_id(null, $params['cmid'], 0, false, MUST_EXIST);
        $user = block_favorites_user::from_id($USER->id);
        if ($params['starred']) {
            $user->starred($cm->id) || $user->star($cm->id);
        } else {
            $user->unstar($cm->id);
        }
        return true;
    }

    /**
     * @return external_value
     */
    public static function star_returns() {
        return new external_value(PARAM_BOOL);
    }
}
