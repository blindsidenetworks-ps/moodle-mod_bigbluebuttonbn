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

namespace mod_bigbluebuttonbn\external;

use core\notification;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use mod_bigbluebuttonbn\event\events;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\helpers\meeting_helper;
use mod_bigbluebuttonbn\local\proxy\bigbluebutton_proxy;
use mod_bigbluebuttonbn\meeting;
use moodle_exception;
use restricted_context_exception;

/**
 * External service to check whether a usr can join a meeting.
 *
 * @package   mod_bigbluebuttonbn
 * @category  external
 * @copyright 2018 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class can_join extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'course module id', VALUE_REQUIRED)
        ]);
    }

    /**
     * Updates a recording
     *
     * @param int $bigbluebuttonbnid the bigbluebuttonbn instance id
     * @param int $groupid the groupid (either 0 or the groupid)
     * @return array (empty array for now)
     * @throws \restricted_context_exception
     */
    public static function execute(
        int $bigbluebuttonbnid,
        int $groupid
    ): array {
        // Validate the bigbluebuttonbnid ID.
        [
            'cmid' => $cmid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
        ]);

        $canjoin = bigbluebutton_proxy::can_join_meeting($cmid);
        $canjoin['cmid'] = $cmid;

        return $canjoin;
    }

    /**
     * Describe the return structure of the external service.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'can_join' => new external_value(PARAM_BOOL, 'Can join session'),
            'message' => new external_value(PARAM_RAW, 'Message if we cannot join', VALUE_OPTIONAL),
            'cmid' => new external_value(PARAM_INT, 'course module id', VALUE_REQUIRED),
        ]);
    }
}
