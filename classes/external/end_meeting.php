<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * BigBlueButtonBN internal API for meeting
 *
 * @package   mod_bigbluebuttonbn
 * @category  external
 * @copyright 2018 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bigbluebuttonbn\external;

use core\notification;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use mod_bigbluebuttonbn\event\events;
use mod_bigbluebuttonbn\local\bbb_constants;
use mod_bigbluebuttonbn\local\helpers\instance;
use mod_bigbluebuttonbn\local\helpers\meeting;
use moodle_exception;
use restricted_context_exception;

/**
 * External service to end a meeting.
 *
 * @package   mod_bigbluebuttonbn
 * @category  external
 * @copyright 2018 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class end_meeting extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'bigbluebuttonbnid' => new external_value(PARAM_INT, 'bigbluebuttonbn instance id'),
            'meetingid' => new external_value(PARAM_RAW, 'bigbluebuttonbn meetingid'),
        ]);
    }

    /**
     * Updates a recording
     *
     * @param int $bigbluebuttonbnid the bigbluebuttonbn instance id
     * @param string $meetingid
     * @return array (empty array for now)
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     * @throws \restricted_context_exception
     * @throws moodle_exception
     */
    public static function execute(
        int $bigbluebuttonbnid,
        string $meetingid
    ): array {
        // Validate the bigbluebuttonbnid ID.
        [
            'bigbluebuttonbnid' => $bigbluebuttonbnid,
            'meetingid' => $meetingid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'bigbluebuttonbnid' => $bigbluebuttonbnid,
            'meetingid' => $meetingid,
        ]);

        // Fetch the session, features, and profile.
        [
            'bbbsession' => $bbbsession,
            'context' => $context
        ] = instance::get_session_from_id($bigbluebuttonbnid);

        // Validate that the user has access to this activity and to manage recordings.
        self::validate_context($context);
        if (!$bbbsession['administrator'] && !$bbbsession['moderator']) {
            throw new restricted_context_exception();
        }

        // Execute the end command.
        meeting::bigbluebuttonbn_end_meeting($meetingid, $bbbsession['modPW']);

        // Moodle event logger: Create an event for meeting ended.
        if (isset($bbbsession['bigbluebuttonbn'])) {
            \mod_bigbluebuttonbn\local\helpers\logs::bigbluebuttonbn_event_log(events::$events['meeting_end'],
                $bbbsession['bigbluebuttonbn']);
        }

        // Update the cache.
        meeting::bigbluebuttonbn_get_meeting_info($meetingid, bbb_constants::BIGBLUEBUTTONBN_UPDATE_CACHE);

        notification::add(get_string('end_session_notification', 'mod_bigbluebuttonbn'), notification::INFO);

        return [];
    }

    /**
     * Describe the return structure of the external service.
     *
     * @return external_single_structure
     * @since Moodle 3.0
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([]);
    }
}
