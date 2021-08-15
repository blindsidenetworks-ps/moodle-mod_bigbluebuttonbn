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
 * The mod_bigbluebuttonbn/bigbluebutton/recordings/action.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\local\bigbluebutton\recordings;

use mod_bigbluebuttonbn\instance;

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
     * Import recording
     *
     * @param recording $recording
     * @param instance $instance
     */
    public static function import($recording, instance $instance) {
        $recording->create_imported_recording($instance);
    }

    /**
     * Helper for performing delete on recordings.
     *
     * @param recording $recording
     * @param instance $instance
     */
    public static function delete($recording, $instance) {
        // As the recordingid was not identified as imported recording link, execute delete on a real recording.
        // Step 1, delete imported links associated to the recording.
        $recordingstodelete = recording::get_records(['recordingid' => $recording->get('recordingid'),
            'imported' => true]);
        foreach ($recordingstodelete as $rec) {
            $rec->delete();
        }
        $recording->delete();
    }

    /**
     * Helper for performing edit on recordings.
     *
     * @param recording $recording
     * @param instance $instance
     */
    public static function edit($recording, $instance) {
        $recording->update();
    }

    /**
     * Helper for performing unprotect on recordings.
     *
     * @param recording $recording
     * @param instance $instance
     */
    public static function unprotect($recording, $instance) {
        if ($recording->get('imported')) {
            /* Since the recording link is the one fetched from the BBB server, imported recordings can not be
             * unprotected. There is no need to do anything else.
             */
            throw new \moodle_exception('cannotperformaction', 'mod_bigblubuebuttobn', '', 'unprotect');
        }
        $recording->set('protected', false);
        $recording->update();
    }

    /**
     * Helper for performing protect on recordings.
     *
     * @param recording $recording
     * @param instance $instance
     */
    public static function protect($recording, $instance) {
        if ($recording->get('imported')) {
            /* Since the recording link is the one fetched from the BBB server, imported recordings can not be
             * protected. There is no need to do anything else.
             */
            throw new \moodle_exception('cannotperformaction', 'mod_bigblubuebuttobn', '', 'protect');
        }
        $recording->set('protected', true);
        $recording->update();
    }

    /**
     * Helper for performing unpublish on recordings.
     *
     * @param recording $recording
     * @param instance $instance
     */
    public static function unpublish($recording, $instance) {
        if ($recording->get('imported')) {
            /* Since the recording link is the one fetched from the BBB server, imported recordings can not be
             * unpublished. There is no need to do anything else.
             */
            throw new \moodle_exception('cannotperformaction', 'mod_bigblubuebuttobn', '', 'unpublish');
        }
        $recording->set('published', false);
        $recording->update();
    }

    /**
     * Helper for performing publish on recordings.
     *
     * @param recording $recording
     * @param instance $instance
     */
    public static function publish($recording, $instance) {
        if ($recording->get('imported')) {
            /* Since the recording link is the one fetched from the BBB server, imported recordings can not be
             * unpublished. There is no need to do anything else.
             */
            throw new \moodle_exception('cannotperformaction', 'mod_bigblubuebuttobn', '', 'publish');
        }
        $recording->set('published', true);
        $recording->update();
    }
}
