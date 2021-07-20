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
 * The mod_bigbluebuttonbn/bigbluebutton/recordings/handler.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\bigbluebutton\recordings;

use stdClass;
use mod_bigbluebuttonbn\bigbluebutton\recordings\base as recording_base;
use mod_bigbluebuttonbn\local\bigbluebutton;

defined('MOODLE_INTERNAL') || die();

/**
 * Utility class for handling BBB recordings.
 *
 * Utility class for recording helper
 * @package mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class handler {

    /** @var stdClass mod_bigbluebuttonbn instance. */
    protected $bigbluebuttonbn;

    /**
     * Class contructor.
     *
     * @param stdClass $bigbluebuttonbn BigBlueButtonBN instance object
     */
    public function __construct($bigbluebuttonbn) {
        $this->bigbluebuttonbn = $bigbluebuttonbn;
    }

    /**
     *
     * @param array $conditions optional array $fieldname=>requestedvalue with AND in between. Used for locating recordings.
     * @param object $dataobject An object with contents equal to fieldname=>fieldvalue. Used for updating each recording.
     *
     * @return bool Success/Failure
     */
    public function update_all_recordings($conditions, $dataobject) {
        global $DB;
        $recordings = $DB->get_records('bigbluebuttonbn_recordings', $conditions);
        if (!$recordings) {
            return false;
        }
        foreach ($recordings as $r) {
            $recording = new recording($r->id, $courseid->id, $r->bigbluebuttonbnid, $r->recordingid, $r->meetingid);
            if (!$recording->update($dataobject)) {
                // TODO: There should be a way to rollback if it fails after updating one or many of the recordings.
                return false;
            }
        }
        return true;
    }

    /**
     * Get the basic data to display in the table view
     *
     * @param bool $subset. If $subset=true the query is performed on one single bigbluebuttonbn instance.
     * @param bool $includedeleted. If $includedeleted=true the query is performed on one single bigbluebuttonbn instance.
     * @param bool $includeimported. If $includeimported=true the returned array also includes imported recordings.
     *
     * @return array array containing the recordings indexed by recordingid, each recording is also a
     * mod_bigbluebuttonbn/bigbluebutton/recordings/recording object which contains a non sequential array itself ($xml)
     * that corresponds to the actual recording in BBB.
     */
    public function get_recordings_for_view($subset, $includedeleted, $includeimported) {
        $bigbluebuttonbnid = null;
        if ($subset) {
            $bigbluebuttonbnid = $this->bigbluebuttonbn->id;
        }
        return $this->get_recordings(
            $this->bigbluebuttonbn->course,
            $bigbluebuttonbnid,
            $subset,
            $includedeleted,
            $includeimported
        );
    }

    /**
     * Helper function to retrieve recordings from the BigBlueButton.
     *
     * @param string $courseid
     * @param string $bigbluebuttonbnid
     * @param bool $subset. If $subset=true the query is performed on one single bigbluebuttonbn instance.
     * @param bool $includedeleted. If $includedeleted=true the query is performed on one single bigbluebuttonbn instance.
     * @param bool $includeimported. If $includeimported=true the returned array also includes imported recordings.
     *
     * @return array associative array containing the recordings indexed by recordingid, each recording is also a
     * mod_bigbluebuttonbn/bigbluebutton/recordings/recording object which contains a non sequential array itself ($xml)
     * that corresponds to the actual recording in BBB.
     */
    public function get_recordings($courseid = 0, $bigbluebuttonbnid = null, $subset = true,
        $includedeleted = false, $includeimported = false) {
        global $DB;
        // Retrieve DB recordings.
        $select = $this->sql_select_for_recordings($courseid, $bigbluebuttonbnid, $subset, $includedeleted, $includeimported);
        $dbrecordings = $DB->get_records_select('bigbluebuttonbn_recordings', $select, null, 'id');
        // Fetch BBB recordings.
        $recordingsids = $DB->get_records_select_menu('bigbluebuttonbn_recordings', $select, null, 'id', 'id, recordingid');
        $bbbrecordings = $this->fetch_recordings(array_values($recordingsids));

        // Prepare the $oldrecordings for the response.
        $recordings = array();
        foreach ($dbrecordings as $id => $dbrecording) {
            if (isset($bbbrecordings[$dbrecording->recordingid])) {
                $bbbrecording = $bbbrecordings[$dbrecording->recordingid];
                if (!$dbrecording->imported) {
                    $dbrecording->recording = $bbbrecording;
                }
                $recordings[$dbrecording->recordingid] = $dbrecording;
            }
        }
        return $recordings;
    }

    /**
     * Helper function to define the sql used for gattering the bigbluebuttonbnids whose meetingids should be included
     * in the getRecordings request
     *
     * @param string $courseid
     * @param string $bigbluebuttonbnid
     * @param bool $subset
     * @param bool $includedeleted
     * @param bool $includeimported
     *
     * @return string containing the sql used for getting the target bigbluebuttonbn instances
     */
    private function sql_select_for_recordings($courseid, $bigbluebuttonbnid = null, $subset = true,
        $includedeleted = false, $includeimported =  false) {
        if (empty($courseid)) {
            $courseid = 0;
        }
        $select = "";
        // Start with the filters.
        if (!$includedeleted) {
            // Exclude headless recordings unless includedeleted.
            $select .= "headless = false AND ";
        }
        if (!$includeimported) {
            // Exclude imported recordings unless includedeleted.
            $select .= "imported = false AND ";
        }
        // Add the meain criteria for the search.
        if (empty($bigbluebuttonbnid)) {
            // Include all recordings in given course if bigbluebuttonbnid is not included.
            return $select . "courseid = '{$courseid}'";
        }
        if ($subset) {
            // Include only one bigbluebutton instance if subset filter is included.
            return $select . "bigbluebuttonbnid = '{$bigbluebuttonbnid}'";
        }
        // Include only from one course and instance is used for imported recordings.
        return $select . "bigbluebuttonbnid <> '{$bigbluebuttonbnid}' AND course = '{$courseid}'";
    }


    /**
     * Helper function to fetch recordings from a BigBlueButton server.
     *
     * @param string|array $recordingids list of $recordingids "rid1,rid2,rid3" or array("rid1","rid2","rid3")
     *
     * @return array (associative) with recordings indexed by recordID, each recording is a non sequential array
     */
    private function fetch_recordings($recordingids = []) {
        // Normalize recordingids to array.
        if (!is_array($recordingids)) {
            $recordingids = explode(',', $recordingids);
        }

        // If $recordingids is empty return array() to prevent a getRecordings with meetingID and recordID set to ''.
        if (empty($recordingids)) {
            return array();
        }

        $recordings = array();
        // Execute a paginated getRecordings request. The page size is arbitrarily hardcoded to 25.
        $pagecount = 25;
        $pages = floor(count($recordingids) / $pagecount) + 1;
        if (count($recordingids) > 0 && count($recordingids) % $pagecount == 0) {
            $pages--;
        }
        for ($page = 1; $page <= $pages; ++$page) {
            $rids = array_slice($recordingids, ($page - 1) * $pagecount, $pagecount);
            $recordings += $this->fetch_recordings_page($rids);
        }
        // Sort recordings.
        uasort($recordings, "\\mod_bigbluebuttonbn\\bigbluebutton\\recordings\\handler::recording_build_sorter");
        return $recordings;
    }

    /**
     * Helper function to fetch one page of upto 25 recordings from a BigBlueButton server.
     *
     * @param array $rids
     *
     * @return array
     */
    private function fetch_recordings_page($rids) {
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
     * Helper function to parse an xml recording object and produce an array in the format used by the plugin.
     *
     * @param object $recording
     *
     * @return array
     */
    private function parse_recording($recording) {
        // Add formats.
        $playbackarray = array();
        foreach ($recording->playback->format as $format) {
            $playbackarray[(string) $format->type] = array('type' => (string) $format->type,
                'url' => trim((string) $format->url), 'length' => (string) $format->length);
            // Add preview per format when existing.
            if ($format->preview) {
                $playbackarray[(string) $format->type]['preview'] =
                    $this->parse_preview_images($format->preview);
            }
        }
        // Add the metadata to the recordings array.
        $metadataarray =
            $this->parse_recording_meta(get_object_vars($recording->metadata));
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
    private function parse_recording_meta($metadata) {
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
    private function parse_preview_images($preview) {
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

        /**
     * Helper function to sort an array of recordings. It compares the startTime in two recording objecs.
     *
     * @param object $a
     * @param object $b
     *
     * @return array
     */
    public static function recording_build_sorter($a, $b) {
        global $CFG;
        $resultless = !empty($CFG->bigbluebuttonbn_recordings_sortorder) ? -1 : 1;
        $resultmore = !empty($CFG->bigbluebuttonbn_recordings_sortorder) ? 1 : -1;
        if ($a['startTime'] < $b['startTime']) {
            return $resultless;
        }
        if ($a['startTime'] == $b['startTime']) {
            return 0;
        }
        return $resultmore;
    }

}