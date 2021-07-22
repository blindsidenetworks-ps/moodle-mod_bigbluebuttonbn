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
 * The mod_bigbluebuttonbn/bigbluebutton/recordings/proxy is a proxy or wrapper for the bigbluebutton API
 * that works as a helper for handling all the requests.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David  (laurent [at] call-learning [dt] fr)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\bigbluebutton\recordings;

use mod_bigbluebuttonbn\bigbluebutton\recordings\helper;
use mod_bigbluebuttonbn\local\bigbluebutton;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Utility class for recordings instance helper
 *
 * @package mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recording_proxy {

    /**
     * Perform deleteRecordings on BBB.
     *
     * @param string $recordids
     *
     * @return boolean
     */
    public static function bigbluebutton_delete_recordings($recordids) {
        $ids = explode(',', $recordids);
        foreach ($ids as $id) {
            $xml = bigbluebutton::bigbluebuttonbn_wrap_xml_load_file(
                bigbluebutton::action_url('deleteRecordings', ['recordID' => $id])
            );
            if ($xml && $xml->returncode != 'SUCCESS') {
                return false;
            }
        }
        return true;
    }

    /**
     * Perform publishRecordings on BBB.
     *
     * @param string $recordids
     * @param string $publish
     */
    public static function bigbluebutton_publish_recordings($recordids, $publish = 'true') {
        $ids = explode(',', $recordids);
        foreach ($ids as $id) {
            $xml = bigbluebutton::bigbluebuttonbn_wrap_xml_load_file(
                bigbluebutton::action_url('publishRecordings',
                    ['recordID' => $id, 'publish' => $publish])
            );
            if ($xml && $xml->returncode != 'SUCCESS') {
                return false;
            }
        }
        return true;
    }

    /**
     * Perform updateRecordings on BBB.
     *
     * @param string $recordids
     * @param array $params ['key'=>param_key, 'value']
     */
    public static function bigbluebutton_update_recordings($recordids, $params) {
        $ids = explode(',', $recordids);
        foreach ($ids as $id) {
            $xml = bigbluebutton::bigbluebuttonbn_wrap_xml_load_file(
                bigbluebutton::action_url('updateRecordings', ['recordID' => $id] + (array) $params)
            );
            if ($xml && $xml->returncode != 'SUCCESS') {
                return false;
            }
        }
        return true;
    }

    /**
     * Protect/Unprotect an imported recording.
     *
     * @param string $id
     * @param boolean $protect
     *
     * @return boolean
     */
    public static function bigbluebutton_protect_recording_imported($id, $protect = true) {
        global $DB;
        // Locate the record to be updated.
        $record = $DB->get_record('bigbluebuttonbn_logs', array('id' => $id));
        $meta = json_decode($record->meta, true);
        // Prepare data for the update.
        $meta['recording']['protected'] = ($protect) ? 'true' : 'false';
        $record->meta = json_encode($meta);
        // Proceed with the update.
        $DB->update_record('bigbluebuttonbn_logs', $record);
        return true;
    }

    /**
     * Update an imported recording.
     *
     * @param string $id
     * @param array $params ['key'=>param_key, 'value']
     *
     * @return boolean
     */
    public static function bigbluebutton_update_recording_imported($id, $params) {
        global $DB;
        // Locate the record to be updated.
        // TODO: rework this routine completely (use object/array instead of json data).
        $record = $DB->get_record('bigbluebuttonbn_logs', array('id' => $id));
        $meta = json_decode($record->meta, true);
        // Prepare data for the update.
        $meta['recording'] = $params + $meta['recording'];
        $record->meta = json_encode($meta);
        // Proceed with the update.
        if (!$DB->update_record('bigbluebuttonbn_logs', $record)) {
            return false;
        }
        return true;
    }

    /**
     * Helper function to fetch recordings from a BigBlueButton server.
     *
     * @param string|array $recordingids list of $recordingids "rid1,rid2,rid3" or array("rid1","rid2","rid3")
     *
     * @return array (associative) with recordings indexed by recordID, each recording is a non sequential array
     */
    public function bigbluebutton_fetch_recordings($recordingids = []) {
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
            $recordings += self::bigbluebutton_fetch_recordings_page($rids);
        }
        // Sort recordings.
        uasort($recordings, "\\mod_bigbluebuttonbn\\bigbluebutton\\recordings\\recording::recording_build_sorter");
        return $recordings;
    }

    /**
     * Helper function to fetch one page of upto 25 recordings from a BigBlueButton server.
     *
     * @param array $rids
     *
     * @return array
     */
    private function bigbluebutton_fetch_recordings_page($rids) {
        $recordings = array();
        // Do getRecordings is executed using a method GET (supported by all versions of BBB).
        $url = bigbluebutton::action_url('getRecordings', ['meetingID' => '', 'recordID' => implode(',', $rids)]);
        $xml = bigbluebutton::bigbluebuttonbn_wrap_xml_load_file($url);
        //debugging('getRecordingsURL: ' . $url);
        //debugging('recordIDs: ' . json_encode($rids));
        if ($xml && $xml->returncode == 'SUCCESS' && isset($xml->recordings)) {
            // If there were meetings already created.
            foreach ($xml->recordings->recording as $recordingxml) {
                $recording = recording::parse_recording($recordingxml);
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
                                $recording = recording::parse_recording($recordingxml);
                                $recordings[$recording['recordID']] = $recording;
                            }
                        }
                    }
                }
            }
        }
        return $recordings;
    }
}
