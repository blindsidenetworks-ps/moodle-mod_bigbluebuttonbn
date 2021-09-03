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

namespace mod_bigbluebuttonbn\local\proxy;

use mod_bigbluebuttonbn\local\bigbluebutton\recordings\recording_helper;

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
            'protect' => $protected,
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
     * @param array $keyids list of meetingids or recordingids
     * @param string $key the param name used for the BBB request (<recordID>|meetingID)
     * @return array (associative) with recordings indexed by recordID, each recording is a non sequential array
     */
    public static function fetch_recordings(array $keyids = [], string $key = 'recordID'): array {
        // Normalize ids to array.
        if (!is_array($keyids)) {
            $keyids = explode(',', $keyids);
        }

        // If $ids is empty return array() to prevent a getRecordings with meetingID and recordID set to ''.
        if (empty($keyids)) {
            return array();
        }

        $recordings = array();
        // Execute a paginated getRecordings request. The page size is arbitrarily hardcoded to 25.
        $pagecount = 25;
        $pages = floor(count($keyids) / $pagecount) + 1;
        if (count($keyids) > 0 && count($keyids) % $pagecount == 0) {
            $pages--;
        }
        for ($page = 1; $page <= $pages; ++$page) {
            $ids = array_slice($keyids, ($page - 1) * $pagecount, $pagecount);
            $recordings += self::fetch_recordings_page($ids, $key);
        }

        // Sort recordings.
        self::sort_recordings($recordings);
        return $recordings;
    }

    /**
     * Helper function to fetch one page of upto 25 recordings from a BigBlueButton server.
     *
     * @param array $ids
     * @param string $key
     * @return array
     */
    private static function fetch_recordings_page(array $ids, $key = 'recordID'): array {
        // The getRecordings call is executed using a method GET (supported by all versions of BBB).
        $xml = self::fetch_endpoint_xml('getRecordings', [$key => implode(',', $ids)]);

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
            $recording = self::parse_recording($recordingxml);
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
                        $recording = self::parse_recording($recordingxml);
                        $recordings[$recording['recordID']] = $recording;
                    }
                }
            }
        }

        return $recordings;
    }

    /**
     *  Helper function to sort an array of recordings. It compares the startTime in two recording objects.
     *
     * @param array $recordings
     */
    public static function sort_recordings(array &$recordings) {
        uasort($recordings, function($a, $b) {
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
        });
    }

    /**
     * Helper function to parse an xml recording object and produce an array in the format used by the plugin.
     *
     * @param object $recording
     *
     * @return array
     */
    public static function parse_recording(object $recording): array {
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
    public static function parse_recording_meta(array $metadata): array {
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
    public static function parse_preview_images(object $preview): array {
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
