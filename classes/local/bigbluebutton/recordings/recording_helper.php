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
use Exception;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\local\notifier;
use mod_bigbluebuttonbn\local\helpers\logs;
use mod_bigbluebuttonbn\local\proxy\recording_proxy;

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
     * Helper function to retrieve recordings from the BigBlueButton.
     *
     * @param instance $instance
     * @param bool $includedeleted
     * @param bool $includeimported
     * @param bool $onlyimported
     *
     * @return array containing the recordings indexed by recordID, each recording is also a
     * non sequential associative array itself that corresponds to the actual recording in BBB
     */
    public static function get_recordings_for_instance(instance $instance, bool $includedeleted = false,
        bool $includeimported = false,
        bool $onlyimported = false) {
        list($selects, $params) = self::get_basic_select_from_parameters($includedeleted, $includeimported, $onlyimported);
        $selects[] = "bigbluebuttonbnid = :bbbid";
        $params['bbbid'] = $instance->get_instance_id();
        $groupmode = groups_get_activity_groupmode($instance->get_cm());
        $context = $instance->get_context();
        if ($groupmode) {
            list($groupselects, $groupparams) =
                self::get_select_for_group($groupmode, $context, $instance->get_course_id(),
                    $instance->get_group_id(), $instance->get_cm()->groupingid);
            if ($groupselects) {
                $selects[] = $groupselects;
                $params = array_merge_recursive($params, $groupparams);
            }
        }
        return recording::get_records_select(implode(" AND ", $selects), $params);
    }

    /**
     * Helper function to retrieve recordings from a given course.
     *
     * @param object $course dataobject as a course record
     * @param array $excludedinstanceid exclude recordings from instance ids
     * @param bool $includedeleted
     * @param bool $includeimported
     * @param bool $onlyimported
     *
     * @return array containing the recordings indexed by recordID, each recording is also a
     * non sequential associative array itself that corresponds to the actual recording in BBB
     */
    public static function get_recordings_for_course(object $course, array $excludedinstanceid = [],
        bool $includedeleted = false, bool $includeimported = false, bool $onlyimported = false): array {
        list($selects, $params) = self::get_basic_select_from_parameters($includedeleted, $includeimported, $onlyimported);
        $selects[] = "courseid = :courseid";
        $params['courseid'] = $course->id;
        $groupmode = groups_get_course_groupmode($course);
        $context = \context_course::instance($course->id);
        if ($groupmode) {
            list($groupselects, $groupparams) = self::get_select_for_group($groupmode, $context, $course->id);
            if ($groupselects) {
                $selects[] = $groupselects;
                $params = array_merge_recursive($params, $groupparams);
            }
        }
        if ($excludedinstanceid) {
            global $DB;
            list($sqlexcluded, $paramexcluded) = $DB->get_in_or_equal($excludedinstanceid, SQL_PARAMS_NAMED, false);
            $selects[] = 'bigbluebuttonbnid ' . $sqlexcluded;
            $params = array_merge_recursive($params, $paramexcluded);
        }
        return recording::get_records_select(implode(" AND ", $selects), $params);
    }

    /**
     * Get select for given group mode and context
     *
     * @param int $groupmode
     * @param context $context
     * @param int $courseid
     * @param int $groupid
     * @param int $groupingid
     * @return array
     */
    protected static function get_select_for_group($groupmode, $context, $courseid, $groupid = 0, $groupingid = 0): array {
        $selects = [];
        $params = [];
        if ($groupmode) {
            global $DB;
            $accessallgroups = has_capability('moodle/site:accessallgroups', $context)
                || $groupmode == VISIBLEGROUPS;
            if ($accessallgroups) {
                if ($context instanceof \context_module) {
                    $allowedgroups = groups_get_all_groups($courseid, 0, $groupingid);
                } else {
                    $allowedgroups = groups_get_all_groups($courseid);
                }
            } else {
                global $USER;
                if ($context instanceof \context_module) {
                    $allowedgroups = groups_get_all_groups($courseid, $USER->id, $groupingid);
                } else {
                    $allowedgroups = groups_get_all_groups($courseid, $USER->id);
                }
            }
            $allowedgroupsid = array_map(function($g) {
                return $g->id;
            }, $allowedgroups);
            if ($groupid || empty($allowedgroups)) {
                $selects[] = "groupid = :groupid";
                $params['groupid'] = ($groupid && in_array($groupid, $allowedgroupsid)) ?
                    $groupid : 0;
            } else {
                if ($accessallgroups) {
                    $allowedgroupsid[] = 0;
                }
                list($groupselects, $groupparams) = $DB->get_in_or_equal($allowedgroupsid, SQL_PARAMS_NAMED);
                $selects[] = 'groupid ' . $groupselects;
                $params = array_merge_recursive($params, $groupparams);
            }
        }
        return array(implode(" AND ", $selects), $params);
    }

    /**
     * Retrieve recordings from db then fetch them from BBB and return the result
     *
     * @param string $sql
     * @param array $params
     * @return array
     * @throws \dml_exception
     */
    protected static function do_fetch_recordings(string $sql, array $params): array {
        global $DB;
        $recs = $DB->get_records_select('bigbluebuttonbn_recordings', $sql, $params, 'id, recordingid');

        $recordingsids = array_map(function($r) {
            return $r->recordingid;
        }, $recs);

        $bbbrecordings = recording_proxy::fetch_recordings($recordingsids);
        // Activities set to be recorded insert a bigbluebuttonbn_recording row on create, but it does not mean that
        // the meeting was recorded. We are responding only with the ones that have a processed recording in BBB.

        $recordings = array();
        foreach ($recs as $id => $rec) {
            $recordingid = $rec->recordingid;
            // If there is not a BBB recording assiciated skip the record.
            // NOTE: This verifies that the recording exists, even imported recordings.
            // If the recording doesn't exist, the imported link will no longer be shown in the list.
            if (!isset($bbbrecordings[$recordingid])) {
                continue;
            }
            // If the recording was imported, override the metadata with the value stored in the database.
            if ($rec->imported) {
                // We must convert rec->recording to array because the records directly pulled from the database.
                $rec->recording = json_decode($rec->recording, true);
                foreach ($rec->recording as $varname => $value) {
                    $varnames = explode('_', $varname);
                    if ($varnames[0] == 'meta') {
                        $bbbrecordings[$recordingid][$varname] = $value;
                    }
                }
            }
            // Always assign the recording value fetched from BBB.
            $rec->recording = $bbbrecordings[$recordingid];
            // Finally, add the rec to the indexed array to be returned.
            $recordings[$recordingid] = $rec;
        }
        return $recordings;
    }

    /**
     * Get basic sql select from given parameters
     *
     * @param bool $includedeleted
     * @param bool $includeimported
     * @param bool $onlyimported
     * @return array
     */
    protected static function get_basic_select_from_parameters(bool $includedeleted = false, bool $includeimported = false,
        bool $onlyimported = false): array {
        $selects = [];
        $params = [];
        // Start with the filters.
        if (!$includedeleted) {
            // Exclude headless recordings unless includedeleted.
            $selects[] = "headless != " . recording::RECORDING_HEADLESS;
        }
        if (!$includeimported) {
            // Exclude imported recordings unless includedeleted.
            $selects[] = "imported != " . recording::RECORDING_IMPORTED;
        } else if ($onlyimported) {
            // Exclude non-imported recordings.
            $selects[] = "imported = " . recording::RECORDING_IMPORTED;
        }
        // Now get only recordings that have been validated by recording ready callback.
        $selects[] = "status = :status1 OR status = :status2";
        $params['status1'] = recording::RECORDING_STATUS_PROCESSED;
        $params['status2'] = recording::RECORDING_STATUS_NOTIFIED;
        return array($selects, $params);
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
    public static function parse_recording(object $recording) {
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
    public static function parse_recording_meta(array $metadata) {
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
    public static function parse_preview_images(object $preview) {
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
            $decodedparameters = \Firebase\JWT\JWT::decode(
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
