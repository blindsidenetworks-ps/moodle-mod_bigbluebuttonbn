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
 * The mod_bigbluebuttonbn/bigbluebutton/recordings/recording_action.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\local\bigbluebutton\recordings;

use mod_bigbluebuttonbn\instance;

defined('MOODLE_INTERNAL') || die();

/**
 * Collection of helper methods for handling recordings actions in Moodle.
 *
 * Utility class for meeting actions
 *
 * @package mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recording_action {

    /**
     * Helper for performing publish on recordings.
     *
     * @param array $params
     * @param array $recordings
     *
     * @return array
     */
    public static function publish($params, $recordings) {
        if ($recordings[$params['id']]->imported) {
            /* Since the recording link is the one fetched from the BBB server, imported recordings can not be
             * unpublished. There is no need to do anything else.
             */
            return array('status' => 'This action can not be performed on imported links.');
        }
        // As the recordingid was not identified as imported recording link, execute publish on a real recording.
        return array(
            'status' => recording_proxy::bigbluebutton_publish_recordings(
                $params['id'],
                'true'
            )
        );
    }

    /**
     * Helper for performing unpublish on recordings.
     *
     * @param array $params
     * @param array $recordings
     *
     * @return array
     */
    public static function unpublish($params, $recordings) {
        if ($recordings[$params['id']]->imported) {
            /* Since the recording link is the one fetched from the BBB server, imported recordings can not be
             * unpublished. There is no need to do anything else.
             */
            return array('status' => 'This action can not be performed on imported links.');
        }
        // As the recordingid was not identified as imported recording link, execute unpublish on a real recording.
        return array(
            'status' => recording_proxy::bigbluebutton_publish_recordings(
                $params['id'],
                'false'
            )
        );
    }


    /**
     * Helper for performing protect on recordings.
     *
     * @param array $params
     * @param array $recordings
     *
     * @return array
     */
    public static function protect($params, $recordings) {
        if ($recordings[$params['id']]->imported) {
            /* Since the recording link is the one fetched from the BBB server, imported recordings can not be
             * protected. There is no need to do anything else.
             */
            return array('status' => 'This action can not be performed on imported links.');
        }
        // As the recordingid was not identified as imported recording link, execute protect on a real recording.
        return array(
            'status' => recording_proxy::bigbluebutton_update_recordings(
                $params['id'],
                array('protect' => 'true')
            )
        );
    }

    /**
     * Helper for performing unprotect on recordings.
     *
     * @param array $params
     * @param array $recordings
     *
     * @return array
     */
    public static function unprotect($params, $recordings) {
        if ($recordings[$params['id']]->imported) {
            /* Since the recording link is the one fetched from the BBB server, imported recordings can not be
             * unprotected. There is no need to do anything else.
             */
            return array('status' => 'This action can not be performed on imported links.');
        }
        // As the recordingid was not identified as imported recording link, execute unprotect on a real recording.
        return array(
            'status' => recording_proxy::bigbluebutton_update_recordings(
                $params['id'],
                array('protect' => 'false')
            )
        );
    }

    /**
     * Helper for performing delete on recordings.
     *
     * @param array $params
     * @param array $recordings
     *
     * @return array
     */
    public static function delete($params, $recordings) {
        if ($recordings[$params['id']]->imported) {
            // Execute delete on imported recording link.
            return array(
                'status' => recording::delete(
                    $recordings[$params['id']]->id
                )
            );
        }
        // As the recordingid was not identified as imported recording link, execute delete on a real recording.
        // Step 1, delete imported links associated to the recording.
        recording::delete_by(
            ['recordingid' => $recordings[$params['id']]->recordingid,
            'imported' => true]
        );
        // Step 2, perform the actual delete by sending the corresponding request to BBB.
        return array(
            'status' => recording_proxy::bigbluebutton_delete_recordings($params['id'])
        );
    }

    /**
     * Helper for performing edit on recordings.
     *
     * @param array $params
     * @param array $recordings
     *
     * @return array
     */
    public static function edit($params, $recordings) {
        if ($recordings[$params['id']]->imported) {
            // Execute update on imported recording link.
            return array(
                'status' => recording_proxy::bigbluebutton_update_recording_imported(
                    $recordings[$params['id']]['imported'],
                    json_decode($params['meta'], true)
                )
            );
        }

        // As the recordingid was not identified as imported recording link, execute update on a real recording.
        return array(
            'status' => recording_proxy::bigbluebutton_update_recordings(
                $params['id'],
                json_decode($params['meta'])
            )
        );
    }

    /**
     * Import recording
     *
     * @param array $params
     * @param array $recordings
     */
    public static function import($params, $recordings) {
        $recordings = recording::read_by(['recordingid' => $params['id']]);
        if ($recordings) {
            $recording = $recordings[$params['id']];
            $recording->bigbluebuttonbnid = $params['instanceid'];
            $instance = instance::get_from_instanceid($params['instanceid']);
            $recording->courseid = $instance->get_course_id();
            $recording->groupid = $instance->get_group_id();
            if (!$recording->imported) {
                $recording->imported = true;
                $recording->recording = json_encode($recording->recording);
            }
            // TODO : check what to do if the origincourseid.
            recording::create($recording);
        }
    }
}
