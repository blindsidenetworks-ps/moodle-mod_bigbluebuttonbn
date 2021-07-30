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

namespace mod_bigbluebuttonbn\local;

use coding_exception;
use Exception;
use mod_bigbluebuttonbn\event\events;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\helpers\logs;

defined('MOODLE_INTERNAL') || die();
global $CFG;

/**
 * The broker routines
 *
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class broker {

    /**
     * Helper for validating the parameters received.
     *
     * @param array $params
     *
     * @return string
     */
    public static function validate_parameters($params) {
        $action = strtolower($params['action']);
        $requiredparams = self::required_parameters();
        if (!array_key_exists($action, $requiredparams)) {
            return 'Action ' . $params['action'] . ' can not be performed.';
        }
        return self::validate_parameters_message($params, $requiredparams[$action]);
    }

    /**
     * Helper for responding after the parameters received are validated.
     *
     * @param array $params
     * @param array $requiredparams
     *
     * @return string
     */
    protected static function validate_parameters_message($params, $requiredparams) {
        foreach ($requiredparams as $param => $message) {
            if (!array_key_exists($param, $params) || $params[$param] == '') {
                return $message;
            }
        }
    }

    /**
     * Helper for definig rules for validating required parameters.
     */
    protected static function required_parameters() {
        $params['server_ping'] = [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The meetingID must be specified.'
        ];
        $params['meeting_info'] = [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The meetingID must be specified.'
        ];
        $params['meeting_end'] = [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The meetingID must be specified.'
        ];
        $params['recording_play'] = [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The recordingID must be specified.'
        ];
        $params['recording_info'] = [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The recordingID must be specified.'
        ];
        $params['recording_links'] = [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The recordingID must be specified.'
        ];
        $params['recording_publish'] = [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The recordingID must be specified.'
        ];
        $params['recording_unpublish'] = [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The recordingID must be specified.'
        ];
        $params['recording_delete'] = [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The recordingID must be specified.'
        ];
        $params['recording_protect'] = [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The recordingID must be specified.'
        ];
        $params['recording_unprotect'] = [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The recordingID must be specified.'
        ];
        $params['recording_edit'] = [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The recordingID must be specified.',
            'meta' => 'A meta parameter should be included'
        ];
        $params['recording_import'] = [
            'callback' => 'This request must include a javascript callback.',
            'id' => 'The recordingID must be specified.'
        ];
        $params['recording_ready'] = [
            'bigbluebuttonbn' => 'The BigBlueButtonBN instance ID must be specified.',
            'signed_parameters' => 'A JWT encoded string must be included as [signed_parameters].'
        ];
        $params['recording_list_table'] = [
            'id' => 'The Bigbluebutton activity id must be specified.',
            'callback' => 'This request must include a javascript callback.',
        ];
        $params['meeting_events'] = [
            'bigbluebuttonbn' => 'The BigBlueButtonBN instance ID must be specified.'
        ];
        $params['completion_validate'] = [
            'callback' => 'This request must include a javascript callback.',
            'bigbluebuttonbn' => 'The BigBlueButtonBN instance ID must be specified.'
        ];
        return $params;
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
            bigbluebutton::bigbluebuttonbn_enqueue_completion_update($bigbluebuttonbn, $user->id);
        }
    }
}
