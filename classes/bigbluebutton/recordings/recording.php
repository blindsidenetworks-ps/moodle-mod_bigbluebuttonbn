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
 * The mod_bigbluebuttonbn/bigbluebutton/recordings/recording.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\bigbluebutton\recordings;

use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Utility class that defines a recording and provides methods for handlinging locally in Moodle and externally in BBB.
 *
 * Utility class for recording helper
 * @package mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recording extends base {

    /**
     * Class contructor.
     *
     * @param stdClass $bigbluebuttonbn BigBlueButtonBN instance object
     */
    public function __construct($id, $courseid, $bigbluebuttonbnid, $recordingid, $meetingid) {
        $this->id = $id;
        $this->courseid = $courseid;
        $this->bigbluebuttonbnid = $bigbluebuttonbnid;
        $this->recordingid = $recordingid;
        $this->meetingid = $meetingid;
    }

    /**
     * Setter for $xml.
     *
     * @param stdClass $bigbluebuttonbn BigBlueButtonBN instance object
     */
    public function set_record($record) {
        $this->record = $record;
    }

    /**
     * CRUD create.
     *
     * @param string $recordingid
     * @param stdClass $dataobject
     * 
     * @return bool|int true or new id
     */
    public function create() {
        global $DB;
        $r = new stdClass();
        // Default values.
        $r->courseid = $this->courseid;
        $r->bigbluebuttonbnid = $this->bigbluebuttonbnid;
        $r->timecreated = time();
        $r->recordingid = $this->recordingid;
        $r->meetingid = $this->meetingid;
        $rid = $DB->insert_record('bigbluebuttonbn_recordings', $r);
        if (!$rid) {
            return false;
        }
        $this->id = $rid;
        return $rid;
    }

    /**
     * CRUD read.
     *
     * @param string $recordingid
     * @param stdClass $dataobject
     * 
     * @return bool|int true or new id
     */
    public function read() {
        global $DB;
        return $DB->get_record('bigbluebuttonbn_recordings', ['id' => $this->id], '*', MUST_EXIST);
    }

    /**
     * CRUD update.
     *
     * @param string $recordingid
     * @param stdClass $dataobject
     * 
     * @return bool true
     */
    public function update($dataobject) {
        global $DB;
        $dataobject->id = $this->id;
        return $DB->update_record('bigbluebuttonbn_recordings', $dataobject);
    }

    /**
     * CRUD delete.
     *
     * @param string $recordingid
     * 
     * @return bool true
     */
    public function delete() {
        return $DB->delete_record('bigbluebuttonbn_recordings', ['id' => $this->id]);
    }
}
