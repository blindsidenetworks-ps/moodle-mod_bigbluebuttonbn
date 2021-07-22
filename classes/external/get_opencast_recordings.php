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
 * BigBlueButtonBN internal API for Opencast recordings
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
use external_warnings;
use invalid_parameter_exception;
use mod_bigbluebuttonbn\local\bigbluebutton;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\local\helpers\instance;
use mod_bigbluebuttonbn\local\helpers\recording;
use mod_bigbluebuttonbn\local\helpers\opencast;
use mod_bigbluebuttonbn\plugin;

/**
 * External service to fetch a list of Opencast recordings from the Opencast server.
 *
 * @package   mod_bigbluebuttonbn
 * @category  external
 * @copyright 2018 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_opencast_recordings extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'bigbluebuttonbnid' => new external_value(PARAM_INT, 'bigbluebuttonbn instance id', VALUE_OPTIONAL),
            'tools' => new external_value(PARAM_RAW, 'a set of enablec tools', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Get a list of Opencast recordings
     *
     * @param int $bigbluebuttonbnid the bigbluebuttonbn instance id
     * @param string $tools
     * @return array of warnings and status result
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restricted_context_exception
     * @throws invalid_parameter_exception
     */
    public static function execute(int $bigbluebuttonbnid = 0, $tools = 'edit,delete'): array {
        $warnings = [];

        // Validate the bigbluebuttonbnid ID.
        [
            'bigbluebuttonbnid' => $bigbluebuttonbnid,
            'tools' => $tools,
        ] = self::validate_parameters(self::execute_parameters(), [
            'bigbluebuttonbnid' => $bigbluebuttonbnid,
            'tools' => $tools,
        ]);

        // Fetch the session, features, and profile.
        [
            'bbbsession' => $bbbsession,
            'context' => $context,
            'enabledfeatures' => $enabledfeatures,
            'typeprofiles' => $typeprofiles,
        ] = instance::get_session_from_id($bigbluebuttonbnid);

        if ($bigbluebuttonbnid === 0) {
            throw new invalid_parameter_exception('Both BigbluebuttonBN and Course IDs are null, we can either
            have one or the other but not both at the same time');
        }
        // Validate that the user has access to this activity.
        self::validate_context($context);

        $tools = explode(',', $tools);

        // Fetch the list of recordings.
        $opencastrecordings =
            opencast::bigbluebutton_get_opencast_recordings_for_table_view($bbbsession);

        $tabledata = [
            'activity' => bigbluebutton::bigbluebuttonbn_view_get_activity_status($bbbsession),
            'ping_interval' => (int) config::get('waitformoderator_ping_interval') * 1000,
            'locale' => plugin::bigbluebuttonbn_get_localcode(),
            'profile_features' => $typeprofiles[0]['features'],
            'columns' => [],
            'data' => '',
        ];

        $data = [];

        // Build table content.
        if (isset($opencastrecordings)) {
            // There are recordings for this meeting.
            foreach ($opencastrecordings as $opencastrecording) {
                $rowdata = opencast::bigbluebuttonbn_get_opencast_recording_data_row($bbbsession, $opencastrecording, $tools);
                if (!empty($rowdata)) {
                    $data[] = $rowdata;
                }
            }
        }

        $columns = [
            [
                'key' => 'playback',
                'label' => get_string('view_recording_playback', 'bigbluebuttonbn'),
                'width' => '125px',
                'type' => 'html',
                'allowHTML' => true,
            ],
            [
                'key' => 'name',
                'label' => get_string('view_recording_name', 'bigbluebuttonbn'),
                'width' => '125px',
                'type' => 'html',
                'allowHTML' => true,
            ],
            [
                'key' => 'description',
                'label' => get_string('view_recording_description', 'bigbluebuttonbn'),
                'sortable' => true,
                'width' => '250px',
                'type' => 'html',
                'allowHTML' => true,
            ],
        ];

        // Initialize table headers.
        // For Opencast recording table to maintain the consistency, it checks if preview is enabled for the recording table.
        if (recording::bigbluebuttonbn_get_recording_data_preview_enabled($bbbsession)) {
            $columns[] = [
                'key' => 'preview',
                'label' => get_string('view_recording_preview', 'bigbluebuttonbn'),
                'width' => '250px',
                'type' => 'html',
                'allowHTML' => true,
            ];
        }

        $columns[] = [
            'key' => 'date',
            'label' => get_string('view_recording_date', 'bigbluebuttonbn'),
            'sortable' => true,
            'width' => '225px',
            'type' => 'html',
            'allowHTML' => true,
        ];
        $columns[] = [
            'key' => 'duration',
            'label' => get_string('view_recording_duration', 'bigbluebuttonbn'),
            'width' => '50px',
            'allowHTML' => false,
            'sortable' => true,
        ];
        if ($bbbsession['managerecordings']) {
            $columns[] = [
                'key' => 'actionbar',
                'label' => get_string('view_recording_actionbar', 'bigbluebuttonbn'),
                'width' => '120px',
                'type' => 'html',
                'allowHTML' => true,
            ];
        }

        $tabledata['columns'] = $columns;
        $tabledata['data'] = json_encode($data);

        $returnval = [
            'status' => true,
            'tabledata' => $tabledata,
            'warnings' => $warnings,
        ];

        return $returnval;
    }

    /**
     * Describe the return structure of the external service.
     *
     * @return external_single_structure
     * @since Moodle 3.0
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Whether the fetch was successful'),
            'tabledata' => new external_single_structure([
                'activity' => new external_value(PARAM_ALPHA),
                'ping_interval' => new external_value(PARAM_INT),
                'locale' => new external_value(PARAM_TEXT),
                'profile_features' => new external_multiple_structure(new external_value(PARAM_TEXT)),
                'columns' => new external_multiple_structure(new external_single_structure([
                    'key' => new external_value(PARAM_ALPHA),
                    'label' => new external_value(PARAM_TEXT),
                    'width' => new external_value(PARAM_ALPHANUMEXT),
                    // See https://datatables.net/reference/option/columns.type .
                    'type' => new external_value(PARAM_ALPHANUMEXT, 'Column type', VALUE_OPTIONAL),
                    'sortable' => new external_value(PARAM_BOOL, 'Whether this column is sortable', VALUE_OPTIONAL, false),
                    'allowHTML' => new external_value(PARAM_BOOL, 'Whether this column contains HTML', VALUE_OPTIONAL, false),
                ])),
                'data' => new external_value(PARAM_RAW), // For now it will be json encoded.
            ]),
            'warnings' => new external_warnings()
        ]);
    }
}
