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

use context_course;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use mod_bigbluebuttonbn\event\events;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\meeting;
use mod_bigbluebuttonbn\local\bbb_constants;
use mod_bigbluebuttonbn\local\bigbluebutton;
use mod_bigbluebuttonbn\local\broker;
use mod_bigbluebuttonbn\local\helpers\roles;
use moodle_exception;
use restricted_context_exception;

/**
 * External service for meeting
 *
 * @package   mod_bigbluebuttonbn
 * @category  external
 * @copyright 2018 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class meeting_info extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'bigbluebuttonbnid' => new external_value(PARAM_INT, 'bigbluebuttonbn instance id'),
            'meetingid' => new external_value(PARAM_RAW, 'bigbluebuttonbn meetingid'),
            'updatecache' => new external_value(PARAM_BOOL, 'update cache ?', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Fetch meeting information.
     *
     * @param int $bigbluebuttonbnid the bigbluebuttonbn instance id
     * @param string $meetingid
     * @param bool $updatecache
     * @return array
     */
    public static function execute(
        int $bigbluebuttonbnid,
        string $meetingid,
        bool $updatecache = false
    ): array {
        // Validate the bigbluebuttonbnid ID.
        [
            'bigbluebuttonbnid' => $bigbluebuttonbnid,
            'meetingid' => $meetingid,
            'updatecache' => $updatecache,
        ] = self::validate_parameters(self::execute_parameters(), [
            'bigbluebuttonbnid' => $bigbluebuttonbnid,
            'meetingid' => $meetingid,
            'updatecache' => $updatecache,
        ]);

        // Fetch the session, features, and profile.
        $instance = instance::get_from_instanceid($bigbluebuttonbnid);
        $context = $instance->get_context();

        // Validate that the user has access to this activity and to manage recordings.
        self::validate_context($context);

        $meeting = new meeting($instance);

        return (array) $meeting->get_meeting_info($updatecache);
    }

    /**
     * Describe the return structure of the external service.
     *
     * @return external_single_structure
     * @since Moodle 3.0
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            [
                'cmid' => new external_value(PARAM_INT, 'CM id'),
                'userlimit' => new external_value(PARAM_INT, 'User limit'),
                'bigbluebuttonbnid' => new external_value(PARAM_RAW, 'bigbluebuttonbn instance id'),
                'meetingid' => new external_value(PARAM_RAW, 'Meeting id'),
                'openingtime' => new external_value(PARAM_TEXT, 'Opening time', VALUE_OPTIONAL),
                'closingtime' => new external_value(PARAM_TEXT, 'Closing time', VALUE_OPTIONAL),
                'statusrunning' => new external_value(PARAM_BOOL, 'Status running', VALUE_OPTIONAL),
                'statusclosed' => new external_value(PARAM_BOOL, 'Status closed', VALUE_OPTIONAL),
                'statusopen' => new external_value(PARAM_BOOL, 'Status open', VALUE_OPTIONAL),
                'statusmessage' => new external_value(PARAM_TEXT, 'Status message', VALUE_OPTIONAL),
                'startedat' => new external_value(PARAM_INT, 'Started at', VALUE_OPTIONAL),
                'moderatorcount' => new external_value(PARAM_INT, 'Moderator count', VALUE_OPTIONAL),
                'participantcount' => new external_value(PARAM_INT, 'Participant count', VALUE_OPTIONAL),
                'moderatorplural' => new external_value(PARAM_BOOL, 'Several moderators ?', VALUE_OPTIONAL),
                'participantplural' => new external_value(PARAM_BOOL, 'Several participants ?', VALUE_OPTIONAL),
                'canjoin' => new external_value(PARAM_BOOL, 'Can join'),
                'ismoderator' => new external_value(PARAM_BOOL, 'Is moderator'),
                'presentations' => new \external_multiple_structure(
                    new external_single_structure([
                        'url' => new external_value(PARAM_URL, 'presentation URL'),
                        'iconname' => new external_value(PARAM_RAW, 'icon name'),
                        'icondesc' => new external_value(PARAM_TEXT, 'icon text'),
                        'name' => new external_value(PARAM_TEXT, 'presentation name'),
                    ])
                ),
                'joinurl' => new external_value(PARAM_URL, 'Join URL'),
            ]
        );
    }
}
