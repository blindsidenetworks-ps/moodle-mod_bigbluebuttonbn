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
 * The recording entity.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\local\bigbluebutton\recordings;
defined('MOODLE_INTERNAL') || die();
use stdClass;

/**
 * Utility class that defines a recording and provides methods for handlinging locally in Moodle and externally in BBB.
 *
 * Utility class for recording helper
 * @package mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recording {

    /** @var int RECORDING_HEADLESS integer set to 1 defines that the activity used to create the recording no longer exists */
    public const RECORDING_HEADLESS = 1;
    /** @var int RECORDING_IMPORTED integer set to 1 defines that the recording is not the original but an imported one */
    public const RECORDING_IMPORTED = 1;

    /** @var int INCLUDE_IMPORTED_RECORDINGS boolean set to true defines that the list should include imported recordings */
    public const INCLUDE_IMPORTED_RECORDINGS = true;

    /** @var int RECORDING_STATE_AWAITING integer set to 0. A meeting set to be recorded still awaits for a recording update */
    public const RECORDING_STATE_AWAITING = 0;
    /** @var int RECORDING_STATE_DISMISSED integer set to 1. A meeting set to be recorded was not recorded and dismissed by BBB */
    public const RECORDING_STATE_DISMISSED = 1;
    /** @var int RECORDING_STATE_PROCESSED integer set to 2. A meeting set to be recorded has a recording processed */
    public const RECORDING_STATE_PROCESSED = 2;
    /** @var int RECORDING_STATE_NOTIFIED integer set to 3. A meeting set to be recorded received notification callback from BBB */
    public const RECORDING_STATE_NOTIFIED = 3;

    /**
     * CRUD create.
     *
     * @param stdClass $dataobject
     *
     * @return bool|int true or new id
     */
    public static function create($dataobject) {
        global $DB;
        $r = new stdClass();
        // Default values.
        $r->courseid = $dataobject->courseid;
        $r->bigbluebuttonbnid = $dataobject->bigbluebuttonbnid;
        $r->timecreated = time();
        $r->recordingid = $dataobject->recordingid;
        $r->headless = $dataobject->headless;
        $r->imported = $dataobject->imported;
        $r->recording = $dataobject->recording;
        $rid = $DB->insert_record('bigbluebuttonbn_recordings', $r);
        if (!$rid) {
            return false;
        }
        return $rid;
    }

    /**
     * CRUD read.
     *
     * @param string $id
     *
     * @return stdClass a bigbluebuttonbn_recordings record.
     */
    public static function read($id) {
        global $DB;
        $rec = $DB->get_record('bigbluebuttonbn_recordings', ['id' => $id], '*', MUST_EXIST);
        if ($rec->imported) {
            // On imported recordings we always need to convert rec->recording to array since it is stored serialized.
            $rec->recording = json_decode($rec->recording, true);
        } else {
            $bbbrecordings = recording_proxy::bigbluebutton_fetch_recordings([$rec->recordingid]);
            $rec->recording = $bbbrecordings[$rec->recordingid];
        }
        return $rec;
    }

    /**
     * CRUD read by indicated attributes.
     *
     * @param array $attributes
     *
     * @return [stdClass] one or many bigbluebuttonbn_recordings records indexed by recordingid.
     */
    public static function read_by($attributes) {
        global $DB;
        $recs = $DB->get_records('bigbluebuttonbn_recordings', $attributes);
        // Assign default value to empty.
        if (!$recs) {
            $recs = array();
        }
        // Normalize to array.
        if (!is_array($recs)) {
            $recs = array($recs);
        }
        $recordings = array();
        foreach ($recs as $rec) {
            $recording = $rec;
            if ($rec->imported) {
                // On imported recordings we always need to convert rec->recording to array since it is stored serialized.
                $rec->recording = json_decode($rec->recording, true);
            } else {
                $bbbrecording = recording_proxy::bigbluebutton_fetch_recordings([$rec->recordingid]);
                $recording->recording = $bbbrecording;
            }
            $recordings[$recording->recordingid] = $recording;
        }
        return $recordings;
    }

    /**
     * Helper function to count the imported recordings for a recordingid.
     *
     * @param array $attributes
     *
     * @return integer
     */
    public static function count_by($attributes) {
        global $DB;
        return $DB->count_records('bigbluebuttonbn_recordings', $attributes);
    }

    /**
     * CRUD update.
     *
     * @param string $id
     * @param stdClass $dataobject An object with contents equal to fieldname=>fieldvalue. Used for updating each recording.
     *
     * @return bool true
     */
    public static function update($id, $dataobject) {
        global $DB;
        $dataobject->id = $id;
        return $DB->update_record('bigbluebuttonbn_recordings', $dataobject);
    }

    /**
     * Update a recording by selecting it using given attributes
     *
     * @param array $attributes optional array $fieldname=>requestedvalue with AND in between. Used for locating recordings.
     * @param stdClass $dataobject An object with contents equal to fieldname=>fieldvalue. Used for updating each recording.
     *
     * @return bool Success/Failure
     */
    public static function update_by($attributes, $dataobject) {
        global $DB;
        $recs = $DB->get_records('bigbluebuttonbn_recordings', $attributes);
        if (!$recs) {
            return false;
        }
        foreach ($recs as $r) {
            global $DB;
            $dataobject->id = $r->id;
            if (!$DB->update_record('bigbluebuttonbn_recordings', $dataobject)) {
                // TODO: There should be a way to rollback if it fails after updating one or many of the recordings.
                return false;
            }
        }
        return true;
    }

    /**
     * CRUD delete.
     *
     * @param string $recordingid
     *
     * @return bool true
     */
    public static function delete($recordingid) {
        global $DB;
        return $DB->delete_records('bigbluebuttonbn_recordings', ['id' => $recordingid]);
    }

    /**
     * CRUD delete by indicated attributes.
     *
     * @param array $attributes optional array $fieldname=>requestedvalue with AND in between. Used for locating recordings.
     *
     * @return bool Success/Failure
     */
    public static function delete_by($attributes) {
        global $DB;
        return $DB->delete_records('bigbluebuttonbn_recordings', $attributes);
    }
}
