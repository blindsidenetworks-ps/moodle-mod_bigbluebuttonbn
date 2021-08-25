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
 * The recording proxy.
 *
 * This class acts as a proxy between Moodle and the BigBlueButton API server,
 * and deals with all requests relating to recordings.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David  (laurent [at] call-learning [dt] fr)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\local\proxy;

use mod_bigbluebuttonbn\local\bigbluebutton\recordings\recording_helper;

class recording_proxy extends proxy_base {
    /**
     * Perform deleteRecordings on BBB.
     *
     * @param string $recordid a recording id
     * @return boolean
     */
    public static function delete_recording(string $recordid): bool {
        $result = self::fetch_endpoint_xml('deleteRecordings', ['recordID' => $recordid]);
        if (!$result || $result->returncode != 'SUCCESS') {
            return false;
        }
        return true;
    }

    /**
     * Perform publishRecordings on BBB.
     *
     * @param string $recordid
     * @param string $publish
     * @return boolean
     */
    public static function publish_recording(string $recordid, string $publish = 'true'): bool {
        $result = self::fetch_endpoint_xml('publishRecordings', [
            'recordID' => $recordid,
            'publish' => $publish,
        ]);
        if (!$result || $result->returncode != 'SUCCESS') {
            return false;
        }
        return true;
    }


    /**
     * Perform publishRecordings on BBB.
     *
     * @param string $recordid
     * @param string $protected
     * @return boolean
     */
    public static function protect_recording(string $recordid, string $protected = 'true'): bool {
        $result = self::fetch_endpoint_xml('updateRecordings', [
            'recordID' => $recordid,
            ['protect' => $protected],
        ]);
        if (!$result || $result->returncode != 'SUCCESS') {
            return false;
        }
        return true;
    }

    /**
     * Perform updateRecordings on BBB.
     *
     * @param string $recordid a single record identifier
     * @param array $params ['key'=>param_key, 'value']
     */
    public static function update_recording(string $recordid, array $params): bool {
        $result = self::fetch_endpoint_xml('updateRecordings', array_merge([
            'recordID' => $recordid
        ], $params));

        return $result ? $result->returncode == 'SUCCESS' : false;
    }

    /**
     * Helper function to fetch recordings from a BigBlueButton server.
     *
     * @param array $recordingids list of $recordingids
     * @return array (associative) with recordings indexed by recordID, each recording is a non sequential array
     */
    public static function fetch_recordings(array $recordingids = []): array {
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
            $recordings += self::fetch_recordings_page($rids);
        }
        // Sort recordings.
        recording_helper::sort_recordings($recordings);
        return $recordings;
    }

    /**
     * Helper function to fetch one page of upto 25 recordings from a BigBlueButton server.
     *
     * @param array $rids
     * @return array
     */
    private static function fetch_recordings_page(array $rids): array {
        // The getRecordings call is executed using a method GET (supported by all versions of BBB).
        $xml = self::fetch_endpoint_xml('getRecordings', ['meetingID' => '', 'recordID' => implode(',', $rids)]);

        if (!$xml) {
            return [];
        }

        if ($xml->returncode != 'SUCCESS') {
            return [];
        }

        if (!isset($xml->recordings)) {
            return [];
        }

        $recordings = [];
        // If there were meetings already created.
        foreach ($xml->recordings->recording as $recordingxml) {
            $recording = recording_helper::parse_recording($recordingxml);
            $recordings[$recording['recordID']] = $recording;

            // Check if there is childs.
            if (isset($recordingxml->breakoutRooms->breakoutRoom)) {
                foreach ($recordingxml->breakoutRooms->breakoutRoom as $breakoutroom) {
                    $xml = self::fetch_endpoint_xml('getRecordings', ['recordID' => implode(',', (array) $breakoutroom)]);
                    if (!$xml || $xml->returncode != 'SUCCESS' || empty($xml->recordings)) {
                        continue;
                    }

                    // If there were meetings already created.
                    foreach ($xml->recordings->recording as $recordingxml) {
                        $recording = recording_helper::parse_recording($recordingxml);
                        $recordings[$recording['recordID']] = $recording;
                    }
                }
            }
        }

        return $recordings;
    }
}
