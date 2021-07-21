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
class recording {

    /** @var int RECORDING_HEADLESS integer set to 1 defines that the activity used to create the recording no longer exists */
    public const RECORDING_HEADLESS = 1;
    /** @var int RECORDING_IMPORTED integer set to 1 defines that the recording is not the original but an imported one */
    public const RECORDING_IMPORTED = 1;

    /** @var int INCLUDE_IMPORTED_RECORDINGS boolean set to true defines that the list should include imported recordings */
    public const INCLUDE_IMPORTED_RECORDINGS = true;

    /** @var int mod_bigbluebuttonbn_recordings instance id. */
    protected int $id;
    /** @var int course instance id. */
    protected int $courseid;
    /** @var int mod_bigbluebuttonbn instance id. */
    protected int $bigbluebuttonbnid;
    /** @var string bbb recordID or internalMeetingID. */
    protected string $recordingid;
    /** @var string bbb meetingID used to generate the recording. */
    protected string $meetingid;
    /** @var array  bigbluebutton recording. */
    protected array $recording;

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
     * @param string $id
     * 
     * @return stdClass a bigbluebuttonbn_recordings record.
     */
    public static function read($id) {
        global $DB;
        $dbrecording = $DB->get_record('bigbluebuttonbn_recordings', ['id' => $id], '*', MUST_EXIST);
        $recording = new stdClass();
        if (!$dbrecording->imported) {
            $bbbrecording = self::fetch([$dbrecording->recordingid]);
            $recording->recording = $bbbrecording;
        }
        return $recording;
    }

    /**
     * CRUD read by indicated attributes.
     *
     * @param array $attributes
     *
     * @return stdClass|[stdClass] one or many bigbluebuttonbn_recordings records.
     */
    public static function read_by($attributes) {
        global $DB;
        $dbrecordings = $DB->get_record('bigbluebuttonbn_recordings', $attributes, '*');
        // Assign default value to empty.
        if (!$dbrecordings) {
            $dbrecordings = array();
        }
        // Normalize to array.
        if (!is_array($dbrecordings)) {
            $dbrecordings = array($dbrecordings);
        }
        $recordings = array();
        foreach ($dbrecordings as $dbrecording) {
            $recording = new stdClass();
            if (!$dbrecording->imported) {
                $bbbrecording = self::fetch([$dbrecording->recordingid]);
                $recording->recording = $bbbrecording;
            }
            $recordings[] = $recording;
        }
        return $recordings;
    }

    /**
     * Helper function to fetch one or many recordings from a BigBlueButton server.
     *
     * @param array $rids
     *
     * @return array one or many bigbluebuttonbn_recordings records.
     */
    public static function fetch($rids) {
        $recordings = array();
        // Do getRecordings is executed using a method GET (supported by all versions of BBB).
        $url = bigbluebutton::action_url('getRecordings', ['meetingID' => '', 'recordID' => implode(',', $rids)]);
        $xml = bigbluebutton::bigbluebuttonbn_wrap_xml_load_file($url);
        debugging('getRecordingsURL: ' . $url);
        debugging('recordIDs: ' . json_encode($rids));
        if ($xml && $xml->returncode == 'SUCCESS' && isset($xml->recordings)) {
            // If there were meetings already created.
            foreach ($xml->recordings->recording as $recordingxml) {
                $recording = $this->parse_recording($recordingxml);
                $recordings[$recording['recordID']] = $recording;

                // Check if there is childs.
                if (isset($recordingxml->breakoutRooms->breakoutRoom)) {
                    foreach ($recordingxml->breakoutRooms->breakoutRoom as $breakoutroom) {
                        $url = bigbluebutton::action_url(
                            'getRecordings',
                            ['recordID' => implode(',', (array) $breakoutroom)]
                        );
                        $xml = bigbluebutton::bigbluebuttonbn_wrap_xml_load_file($url);
                        if ($xml && $xml->returncode == 'SUCCESS' && isset($xml->recordings)) {
                            // If there were meetings already created.
                            foreach ($xml->recordings->recording as $recordingxml) {
                                $recording =
                                    $this->parse_recording($recordingxml);
                                $recordings[$recording['recordID']] = $recording;
                            }
                        }
                    }
                }
            }
        }
        return $recordings;
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


    public function to_array() {
        return array(
            'id' => $this->id,
            'courseid' => $this->courseid,
            'bigbluebuttonbnid' => $this->bigbluebuttonbnid,
            'recordingid' => $this->recordingid,
            'meetingid' => $this->meetingid,
            'recording' => $this->recording
        );
    }

}
