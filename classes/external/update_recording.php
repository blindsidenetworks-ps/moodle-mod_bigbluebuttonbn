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
 * BigBlueButtonBN internal API for recordings
 *
 * @package   mod_bigbluebuttonbn
 * @category  external
 * @copyright 2018 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bigbluebuttonbn\external;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\bigbluebutton\recordings\recording;
use mod_bigbluebuttonbn\local\bigbluebutton\recordings\recording_helper;
use mod_bigbluebuttonbn\local\bigbluebutton\recordings\recording_action;

/**
 * External service to update the details of one recording.
 *
 * @package   mod_bigbluebuttonbn
 * @category  external
 * @copyright 2018 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_recording extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'bigbluebuttonbnid' => new external_value(PARAM_INT, 'bigbluebuttonbn instance id'),
            'recordingid' => new external_value(PARAM_ALPHANUMEXT, 'The bigbluebutton recording ID'),
            'action' => new external_value(PARAM_ALPHANUMEXT, 'The action to perform'),
            'recid' => new external_value(PARAM_RAW, 'The bigbluebuttonbn_recordings row id', VALUE_OPTIONAL),
            'additionaloptions' => new external_value(PARAM_RAW, 'Additional options', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Updates a recording
     *
     * @param int $bigbluebuttonbnid the bigbluebuttonbn instance id
     * @param string $recordingid
     * @param string $action
     * @param string|null $recid
     * @param string|null $additionaloptions
     * @return array (empty array for now)
     * @throws \coding_exception
     */
    public static function execute(
        int $bigbluebuttonbnid,
        string $recordingid,
        string $action,
        string $recid = null,
        string $additionaloptions = null
    ): array {
        // Validate the bigbluebuttonbnid ID.
        [
            'bigbluebuttonbnid' => $bigbluebuttonbnid,
            'recordingid' => $recordingid,
            'action' => $action,
            'recid' => $recid,
            'additionaloptions' => $additionaloptions,
        ] = self::validate_parameters(self::execute_parameters(), [
            'bigbluebuttonbnid' => $bigbluebuttonbnid,
            'recordingid' => $recordingid,
            'action' => $action,
            'recid' => $recid,
            'additionaloptions' => $additionaloptions,
        ]);

        switch ($action) {
            case 'delete':
            case 'edit':
            case 'protect':
            case 'publish':
            case 'unprotect':
            case 'unpublish':
            case 'import':
                break;
            default:
                throw new \coding_exception("Unknown action '{$action}'");
        }

        // Fetch the session, features, and profile.
        $instance = instance::get_from_instanceid($bigbluebuttonbnid);
        $context = $instance->get_context();
        $enabledfeatures = $instance->get_enabled_features();

        // Validate that the user has access to this activity and to manage recordings.
        self::validate_context($context);
        require_capability('mod/bigbluebuttonbn:managerecordings', $context);

        // Fetch the list of recordings.
        $recordings = recording_helper::get_recordings(
            $instance->get_course_id(),
            $instance->get_instance_id(),
            $enabledfeatures['showroom'],
            $instance->get_instance_var('recordings_deleted'),
            $enabledfeatures['importrecordings']
        );

        // Specific action such as import, delete, publish, unpublish, edit,....
        if (method_exists(recording_action::class, $action)) {
            forward_static_call_array(
                array('\mod_bigbluebuttonbn\local\bigbluebutton\recordings\recording_action',
                    $action),
                array(['id' => $recordingid, 'instanceid' => $instance->get_instance_id()],
                    $recordings)
            );
        }
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
