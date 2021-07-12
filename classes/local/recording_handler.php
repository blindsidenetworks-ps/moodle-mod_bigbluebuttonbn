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
        $recording->bigbluebuttonbnid = (int) $this->bigbluebuttonbn->id;
        $recording->recordingid = $recordingid;
        $recording->meetingid = $meetingid;

        error_log(gettype($recording->bigbluebuttonbnid));
        error_log(gettype($recording->recordingid));
        error_log(gettype($recording->meetingid));

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

    public function recording_delete($recordingid) {
    }
}