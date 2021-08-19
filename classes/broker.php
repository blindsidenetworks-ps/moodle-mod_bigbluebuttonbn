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
 * The broker routines
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn;

use Exception;
use coding_exception;
use mod_bigbluebuttonbn\event\events;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\helpers\logs;
use mod_bigbluebuttonbn\local\proxy\bigbluebutton_proxy;

class broker {

    /** @var array List of required params */
    protected $requiredparams = [
        'server_ping' => [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The meetingID must be specified.'
        ],
        'meeting_info' => [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The meetingID must be specified.'
        ],
        'meeting_end' => [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The meetingID must be specified.'
        ],
        'recording_play' => [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The recordingID must be specified.'
        ],
        'recording_info' => [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The recordingID must be specified.'
        ],
        'recording_links' => [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The recordingID must be specified.'
        ],
        'recording_publish' => [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The recordingID must be specified.'
        ],
        'recording_unpublish' => [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The recordingID must be specified.'
        ],
        'recording_delete' => [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The recordingID must be specified.'
        ],
        'recording_protect' => [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The recordingID must be specified.'
        ],
        'recording_unprotect' => [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The recordingID must be specified.'
        ],
        'recording_edit' => [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The recordingID must be specified.',
            'meta' => 'A meta parameter should be included'
        ],
        'recording_import' => [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The recordingID must be specified.'
        ],
        'recording_ready' => [
            'bigbluebuttonbn' => 'The BigBlueButtonBN instance ID must be specified.',
            'signed_parameters' => 'A JWT encoded string must be included as [signed_parameters].'
        ],
        'recording_list_table' => [
            'id' => 'The Bigbluebutton activity id must be specified.',
            'callback' => 'This request must include a javascript callback.',
        ],
        'meeting_events' => [
            'bigbluebuttonbn' => 'The BigBlueButtonBN instance ID must be specified.'
        ],
        'completion_validate' => [
            'callback' => 'This request must include a javascript callback.',
            'bigbluebuttonbn' => 'The BigBlueButtonBN instance ID must be specified.'
        ],
    ];

    /**
     * Validate the supplied list of parameters, providing feedback about any missing or incorrect values.
     *
     * @param array $params
     * @return null|string
     */
    public function validate_parameters(array $params): ?string {
        if (!array_key_exists('action', $params)) {
            return 'No action specified';
        }

        $action = strtolower($params['action']);
        if (!array_key_exists($action, $this->requiredparams)) {
            return "Action {$params['action']} can not be performed.";
        }
        return $this->validate_parameters_message($params, $this->requiredparams[$action]);
    }

    /**
     * Check whether the specified parameter is valid.
     *
     * @param array $params
     * @param array $requiredparams
     * @return null|string
     */
    protected static function validate_parameters_message(array $params, array $requiredparams): ?string {
        foreach ($requiredparams as $param => $message) {
            if (!array_key_exists($param, $params) || $params[$param] == '') {
                return $message;
            }
        }

        // Everything is valid.
        return null;
    }

    /**
     * Helper for performing validation of completion.
     *
     * @param object $bigbluebuttonbn
     * @param array $params
     *
     */
    protected static function completion_validate($bigbluebuttonbn, $params) {
        $context = \context_course::instance($bigbluebuttonbn->course);
        // Get list with all the users enrolled in the course.
        list($sort, $sqlparams) = users_order_by_sql('u');
        $users = get_enrolled_users($context, 'mod/bigbluebuttonbn:view', 0, 'u.*', $sort);
        foreach ($users as $user) {
            // Enqueue a task for processing the completion.
            bigbluebutton_proxy::enqueue_completion_update($bigbluebuttonbn, $user->id);
        }
    }
}
