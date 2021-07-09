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
use external_description;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\broker;
use mod_bigbluebuttonbn\local\helpers\logs;
use mod_bigbluebuttonbn\local\helpers\recording;
use moodle_exception;

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
            'additionaloptions' => new external_value(PARAM_RAW, 'additional options', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Updates a recording
     *
     * @param int $bigbluebuttonbnid the bigbluebuttonbn instance id
     * @param string $recordingid
     * @param string $action
     * @param null $additionaloptions
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
        string $recordingid,
        string $action,
        $additionaloptions = null
    ): array {
        // Validate the bigbluebuttonbnid ID.
        [
            'bigbluebuttonbnid' => $bigbluebuttonbnid,
            'recordingid' => $recordingid,
            'action' => $action,
            'additionaloptions' => $additionaloptions,
        ] = self::validate_parameters(self::execute_parameters(), [
            'bigbluebuttonbnid' => $bigbluebuttonbnid,
            'recordingid' => $recordingid,
            'action' => $action,
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
        $bbbsession = $instance->get_legacy_session_object();

        // Validate that the user has access to this activity and to manage recordings.
        self::validate_context($context);
        require_capability('mod/bigbluebuttonbn:managerecordings', $context);

        // Fetch the list of recordings.
        $recordings = recording::bigbluebutton_get_recordings_for_table_view($bbbsession, $enabledfeatures);

        // Specific action for import
        // TODO: refactor this so we do all the operation in the recording table instead of the
        // broker.
        if ($action != 'import') {
            // Perform the action.
            broker::recording_action_perform("recording_{$action}", ['id' => $recordingid], $recordings);
        } else {
            recording::recording_import($bbbsession, $recordingid, $additionaloptions);
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
