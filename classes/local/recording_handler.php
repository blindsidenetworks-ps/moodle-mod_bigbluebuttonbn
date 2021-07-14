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
 * The mod_bigbluebuttonbn local/recording_handler.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\local;

use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Utility class for handling BBB recordings.
 *
 * Utility class for recording helper
 * @package mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recording_handler {

    /** @var int RECORDING_HEADLESS integer set to 1 defines that the activity used to create the recording no longer exists */
    public const RECORDING_HEADLESS = 1;
    /** @var int RECORDING_IMPORTED integer set to 1 defines that the recording is not the original but an imported one */
    public const RECORDING_IMPORTED = 1;

    /** @var stdClass course_module record. */
    private $bigbluebuttonbn;

    /**
     * Class contructor.
     *
     * @param stdClass $bigbluebuttonbn BigBlueButtonBN instance object
     */
    public function __construct(stdClass $bigbluebuttonbn) {
        $this->bigbluebuttonbn = $bigbluebuttonbn;
    }

    public function recording_create($recordingid, $meetingid) {
        global $DB;
        $recording = new stdClass();
        // Default values.
        $recording->courseid = (int) $this->bigbluebuttonbn->course;
        $recording->bigbluebuttonbnid = (int) $this->bigbluebuttonbn->id;
        $recording->timecreated = time();
        $recording->recordingid = $recordingid;
        $recording->meetingid = $meetingid;
        return $DB->insert_record('bigbluebuttonbn_recordings', $recording);
    }

    public function recording_read($id) {
        global $DB;
        return $DB->get_record('bigbluebuttonbn_recordings', array('id' => $id), '*', MUST_EXIST);
    }

    public function recording_read_by_recordingid($recordingid) {
        global $DB;
        return $DB->get_record('bigbluebuttonbn_recordings', array('recordingid' => $recordingid), '*', MUST_EXIST);
    }

    public function recording_update($recordingid) {
    }

    /**
     *
     * @param array $conditions optional array $fieldname=>requestedvalue with AND in between. Used for locating recordings.
     * @param object $dataobject An object with contents equal to fieldname=>fieldvalue. Used for updating each recording.
     *
     * @return bool Success/Failure
     */
    public function recording_update_all($conditions, $dataobject) {
        global $DB;
        $recordings = $DB->get_records('bigbluebuttonbn_recordings', $conditions);
        if (!$recordings) {
            return false;
        }
        foreach ($recordings as $recording) {
            $dataobject->id = $recording->id;
            if (!$this->recording_update_one($dataobject)){
                return false;
            }
        }
        return true;
    }

    public function recording_update_one($dataobject) {
        global $DB;
        return $DB->update_record('bigbluebuttonbn_recordings', $dataobject);
    }


    public function recording_delete($recordingid) {
    }
}