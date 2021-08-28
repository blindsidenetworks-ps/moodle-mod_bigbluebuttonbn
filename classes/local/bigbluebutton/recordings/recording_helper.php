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

namespace mod_bigbluebuttonbn\local\bigbluebutton\recordings;

use context;
use context_course;
use context_module;
use dml_exception;
use Exception;
use Firebase\JWT\JWT;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\local\notifier;
use mod_bigbluebuttonbn\local\helpers\logs;
use mod_bigbluebuttonbn\local\proxy\recording_proxy;
use mod_bigbluebuttonbn\recording;

/**
 * Collection of helper methods for handling recordings in Moodle.
 *
 * Utility class for meeting helper
 *
 * @package mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recording_helper {

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

    /**
     * Helper for responding when recording ready is performed.
     *
     * @param instance $instance
     * @param array $params
     */
    public static function recording_ready(instance $instance, array $params): void {
        // Decodes the received JWT string.
        try {
            $decodedparameters = JWT::decode(
                $params['signed_parameters'],
                config::get('shared_secret'),
                array('HS256')
            );
        } catch (Exception $e) {
            $error = 'Caught exception: ' . $e->getMessage();
            header('HTTP/1.0 400 Bad Request. ' . $error);
            return;
        }

        // Validations.
        if (!isset($decodedparameters->record_id)) {
            header('HTTP/1.0 400 Bad request. Missing record_id parameter');
            return;
        }

        $recording = recording::get_record(['recordingid' => $decodedparameters->record_id]);
        if (!isset($recording)) {
            header('HTTP/1.0 400 Bad request. Invalid record_id');
            return;
        }

        // Sends the messages.
        try {
            // We make sure messages are sent only once.
            if ($recording->get('status') != recording::RECORDING_STATUS_NOTIFIED) {
                notifier::notify_recording_ready($instance->get_instance_data());
                $recording->set('status', recording::RECORDING_STATUS_NOTIFIED);
                $recording->update();
            }
            header('HTTP/1.0 202 Accepted');
        } catch (Exception $e) {
            $error = 'Caught exception: ' . $e->getMessage();
            header('HTTP/1.0 503 Service Unavailable. ' . $error);
        }
    }
}
