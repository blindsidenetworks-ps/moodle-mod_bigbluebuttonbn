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
 * BigBlueButtonBN internal API for completion
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
use mod_bigbluebuttonbn\event\events;
use mod_bigbluebuttonbn\local\bbb_constants;
use mod_bigbluebuttonbn\local\bigbluebutton;
use mod_bigbluebuttonbn\local\helpers\meeting;
use moodle_exception;
use restricted_context_exception;

/**
 * External service to validate completion.
 *
 * @package   mod_bigbluebuttonbn
 * @category  external
 * @copyright 2018 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class completion_validate extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'bigbluebuttonbnid' => new external_value(PARAM_INT, 'bigbluebuttonbn instance id'),
        ]);
    }

    /**
     * Mark activity as complete
     *
     * @param int $bigbluebuttonbnid the bigbluebuttonbn instance id
     * @return array (empty array for now)
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     * @throws \restricted_context_exception
     * @throws moodle_exception
     */
    public static function execute(
        int $bigbluebuttonbnid
    ): array {
        // Validate the bigbluebuttonbnid ID.
        [
            'bigbluebuttonbnid' => $bigbluebuttonbnid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'bigbluebuttonbnid' => $bigbluebuttonbnid,
        ]);

        // Fetch the session, features, and profile.
        $instance = instance::get_from_instanceid($bigbluebuttonbnid);
        $context = $instance->get_context();

        // Validate that the user has access to this activity and to manage recordings.
        self::validate_context($context);

        // Get list with all the users enrolled in the course.
        list($sort, $sqlparams) = users_order_by_sql('u');
        // TODO : check for access / role here.
        $users = get_enrolled_users($context, 'mod/bigbluebuttonbn:view', 0, 'u.*', $sort);
        foreach ($users as $user) {
            // Enqueue a task for processing the completion.
            bigbluebutton::bigbluebuttonbn_enqueue_completion_update( $instance->get_instance_data(), $user->id);
        }
        // We might want to return a status here or some warnings.
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
