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
use mod_bigbluebuttonbn\local\bbb_constants;
use mod_bigbluebuttonbn\local\bigbluebutton;
use mod_bigbluebuttonbn\local\config;


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
        $r->meetingid = $dataobject->meetingid;
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
        $dbrecording = $DB->get_record('bigbluebuttonbn_recordings', ['id' => $id], '*', MUST_EXIST);
        if (!$dbrecording) {
            $dbrecordings = new stdClass();
        }
        $recording = $dbrecording;
        if (!$dbrecording->imported) {
            $bbbrecording = self::fetch_one($dbrecording->recordingid);
            $recording->recording = $bbbrecording;
        }
        return $recording;
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
            $recording = $dbrecording;
            if (!$dbrecording->imported) {
                $bbbrecording = self::fetch_one($dbrecording->recordingid);
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
     * Helper function to fetch one recording from a BigBlueButton server.
     *
     * @param string $rid
     *
     * @return array one bigbluebuttonbn_recording records.
     */
    public static function fetch_one($rid) {
        $recordings = array();
        // Do getRecordings is executed using a method GET (supported by all versions of BBB).
        $url = bigbluebutton::action_url('getRecordings', ['meetingID' => '', 'recordID' => $rid]);
        $xml = bigbluebutton::bigbluebuttonbn_wrap_xml_load_file($url);
        // debugging('getRecordingsURL: ' . $url);
        // debugging('recordIDs: ' . json_encode($rids));
        if ($xml && $xml->returncode == 'SUCCESS' && isset($xml->recordings)) {
            // If there were meetings already created.
            foreach ($xml->recordings->recording as $recordingxml) {
                $recording = self::parse_recording($recordingxml);
            }
        }
        return $recording;
    }

    /**
     * Helper function to fetch one or many recordings from a BigBlueButton server.
     *
     * @param array $rids
     *
     * @return array one or many bigbluebuttonbn_recordings records.
     */
    public static function fetch_many($rids) {
        $recordings = array();
        // Do getRecordings is executed using a method GET (supported by all versions of BBB).
        $url = bigbluebutton::action_url('getRecordings', ['meetingID' => '', 'recordID' => implode(',', $rids)]);
        $xml = bigbluebutton::bigbluebuttonbn_wrap_xml_load_file($url);
        //debugging('getRecordingsURL: ' . $url);
        //debugging('recordIDs: ' . json_encode($rids));
        if ($xml && $xml->returncode == 'SUCCESS' && isset($xml->recordings)) {
            // If there were meetings already created.
            foreach ($xml->recordings->recording as $recordingxml) {
                $recording = self::parse_recording($recordingxml);
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
                                    self::parse_recording($recordingxml);
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
     *
     * @param array $attributes optional array $fieldname=>requestedvalue with AND in between. Used for locating recordings.
     * @param stdClass $dataobject An object with contents equal to fieldname=>fieldvalue. Used for updating each recording.
     *
     * @return bool Success/Failure
     */
    public function update_by($attributes, $dataobject) {
        global $DB;
        $recordings = $DB->get_records('bigbluebuttonbn_recordings', $attributes);
        if (!$recordings) {
            return false;
        }
        foreach ($recordings as $r) {
            global $DB;
            $dataobject->id = $r->id;
            if(!$DB->update_record('bigbluebuttonbn_recordings', $dataobject)) {
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
        return $DB->delete_record('bigbluebuttonbn_recordings', ['id' => $recordingid]);
    }

    /**
     *
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

    /**
     * Helper convert bigbluebuttonbn_recordings row to array
     *
     * @param stdClass $dataobject
     * 
     * @return array
     */
    public static function to_array($dataobject) {
        return get_object_vars($dataobject);
    }

    /**
     * Helper convert bigbluebuttonbn_recordings row to array
     *
     * @param stdClass $dataobject
     * 
     * @return array
     */
    public static function to_json($dataobject) {
        return json_encode(self::to_array($dataobject));
    }

    /**
     * Helper function to parse an xml recording object and produce an array in the format used by the plugin.
     *
     * @param object $recording
     *
     * @return array
     */
    public static function parse_recording($recording) {
        // Add formats.
        $playbackarray = array();
        foreach ($recording->playback->format as $format) {
            $playbackarray[(string) $format->type] = array('type' => (string) $format->type,
                'url' => trim((string) $format->url), 'length' => (string) $format->length);
            // Add preview per format when existing.
            if ($format->preview) {
                $playbackarray[(string) $format->type]['preview'] =
                    self::parse_preview_images($format->preview);
            }
        }
        // Add the metadata to the recordings array.
        $metadataarray =
            self::parse_recording_meta(get_object_vars($recording->metadata));
        $recordingarray = array('recordID' => (string) $recording->recordID,
            'meetingID' => (string) $recording->meetingID, 'meetingName' => (string) $recording->name,
            'published' => (string) $recording->published, 'startTime' => (string) $recording->startTime,
            'endTime' => (string) $recording->endTime, 'playbacks' => $playbackarray);
        if (isset($recording->protected)) {
            $recordingarray['protected'] = (string) $recording->protected;
        }
        return $recordingarray + $metadataarray;
    }

    /**
     * Helper function to convert an xml recording metadata object to an array in the format used by the plugin.
     *
     * @param array $metadata
     *
     * @return array
     */
    public static function parse_recording_meta($metadata) {
        $metadataarray = array();
        foreach ($metadata as $key => $value) {
            if (is_object($value)) {
                $value = '';
            }
            $metadataarray['meta_' . $key] = $value;
        }
        return $metadataarray;
    }

    /**
     * Helper function to convert an xml recording preview images to an array in the format used by the plugin.
     *
     * @param object $preview
     *
     * @return array
     */
    public static function parse_preview_images($preview) {
        $imagesarray = array();
        foreach ($preview->images->image as $image) {
            $imagearray = array('url' => trim((string) $image));
            foreach ($image->attributes() as $attkey => $attvalue) {
                $imagearray[$attkey] = (string) $attvalue;
            }
            array_push($imagesarray, $imagearray);
        }
        return $imagesarray;
    }
}
