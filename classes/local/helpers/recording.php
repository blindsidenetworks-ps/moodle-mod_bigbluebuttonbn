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
 * The mod_bigbluebuttonbn recordings instance helper
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David  (laurent [at] call-learning [dt] fr)
 */

namespace mod_bigbluebuttonbn\local\helpers;

use html_table;
use html_table_row;
use html_writer;
use mod_bigbluebuttonbn\event\events;
use mod_bigbluebuttonbn\local\bbb_constants;
use mod_bigbluebuttonbn\local\bigbluebutton;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\local\view;
use mod_bigbluebuttonbn\output\recording_description_editable;
use mod_bigbluebuttonbn\output\recording_name_editable;
use mod_bigbluebuttonbn\plugin;
use mod_bigbluebuttonbn_generator;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Utility class for recordings instance helper
 *
 * @package mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recording {

    /**
     * Helper function to retrieve imported recordings from the Moodle database.
     * The references are stored as events in bigbluebuttonbn_logs.
     *
     * @param string $courseid
     * @param string $bigbluebuttonbnid
     * @param bool $subset
     *
     * @return array with imported recordings indexed by recordID, each recording
     * is a non sequential array that corresponds to the actual recording in BBB
     */
    public static function fetch_imported_recording($courseid = 0, $bigbluebuttonbnid = null, $subset = true) {
        global $DB;
        $select =
            self::sql_select_for_imported_recordings($courseid, $bigbluebuttonbnid,
                $subset);
        $recordsimported = $DB->get_records_select('bigbluebuttonbn_logs', $select);
        $recordsimportedarray = array();
        foreach ($recordsimported as $recordimported) {
            $meta = json_decode($recordimported->meta, true);
            $recording = $meta['recording'];
            // Override imported flag with actual ID.
            $recording['imported'] = $recordimported->id;
            if (isset($recordimported->protected)) {
                $recording['protected'] = (string) $recordimported->protected;
            }
            $recordsimportedarray[$recording['recordID']] = $recording;
        }
        return $recordsimportedarray;
    }

    /**
     * Perform deleteRecordings on BBB.
     *
     * @param string $recordids
     *
     * @return boolean
     */
    public static function bigbluebuttonbn_delete_recordings($recordids) {
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
    public static function bigbluebuttonbn_publish_recordings($recordids, $publish = 'true') {
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
    public static function bigbluebuttonbn_update_recordings($recordids, $params) {
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
     * Get the basic data to display in the table view
     *
     * @param array $bbbsession the current session
     * @param array $enabledfeatures feature enabled for this activity
     * @return array array containing the recordings indexed by recordID, each recording is also a
     * non sequential array itself that corresponds to the actual recording in BBB
     */
    public static function get_recordings_for_table_view($bbbsession, $enabledfeatures) {
        $bigbluebuttonbnid = null;
        if ($enabledfeatures['showroom']) {
            $bigbluebuttonbnid = $bbbsession['bigbluebuttonbn']->id;
        }
        return self::get_recordings(
            $bbbsession['course']->id,
            $bigbluebuttonbnid,
            $enabledfeatures['showroom'],
            $bbbsession['bigbluebuttonbn']->recordings_deleted,
            $enabledfeatures['importrecordings']
        );

    }

    /**
     * Helper function returns an array with all the instances of imported recordings for a recordingid.
     *
     * @param string $recordid
     *
     * @return array
     */
    public static function bigbluebuttonbn_get_recording_imported_instances($recordid) {
        global $DB;
        $sql = 'SELECT * FROM {bigbluebuttonbn_logs} WHERE log = ? AND meta LIKE ? AND meta LIKE ?';
        $recordingsimported = $DB->get_records_sql($sql, array(bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_IMPORT, '%recordID%',
            "%{$recordid}%"));
        return $recordingsimported;
    }

    /**
     * Helper function to count the imported recordings for a recordingid.
     *
     * @param string $recordid
     *
     * @return integer
     */
    public static function bigbluebuttonbn_count_recording_imported_instances($recordid) {
        global $DB;
        $sql = 'SELECT COUNT(DISTINCT id) FROM {bigbluebuttonbn_logs} WHERE log = ? AND meta LIKE ? AND meta LIKE ?';
        return $DB->count_records_sql($sql, array(bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_IMPORT, '%recordID%', "%{$recordid}%"));
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
     * @return array array containing the recordings indexed by recordID, each recording is also a
     * non sequential array itself that corresponds to the actual recording in BBB
     */
    public static function get_recordings($courseid = 0, $bigbluebuttonbnid = null, $subset = true,
        $includedeleted = false, $includeimported = false) {
        global $DB;
        $select = self::sql_select_for_recordings($courseid, $bigbluebuttonbnid, $subset, $includedeleted);
        $records = $DB->get_records_select_menu('bigbluebuttonbn_recordings', $select, null, 'id', 'id, recordingid');
        // Get actual recordings.
        $recordings = self::fetch_recordings(array_values($records));
        if ($includeimported) {
            $recordings += self::fetch_imported_recording($courseid, $bigbluebuttonbnid, $subset);
        }
        return $recordings;
    }

    /**
     * Helper function to define the sql used for gattering the bigbluebuttonbnids whose meetingids should be included
     * in the getRecordings request considering only those that belong to imported recordings.
     *
     * @param string $courseid
     * @param string $bigbluebuttonbnid
     * @param bool $subset
     *
     * @return string containing the sql used for getting the target bigbluebuttonbn instances
     */
    public static function sql_select_for_imported_recordings($courseid = 0, $bigbluebuttonbnid = null,
        $subset = true) {
        $sql = "log = '" . bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_IMPORT . "'";
        if (empty($courseid)) {
            $courseid = 0;
        }
        if (empty($bigbluebuttonbnid)) {
            return $sql . " AND courseid = '{$courseid}'";
        }
        if ($subset) {
            return $sql . " AND bigbluebuttonbnid = '{$bigbluebuttonbnid}'";
        }
        return $sql . " AND courseid = '{$courseid}' AND bigbluebuttonbnid <> '{$bigbluebuttonbnid}'";
    }

    /**
     * Helper function to define the sql used for gattering the bigbluebuttonbnids whose meetingids should be included
     * in the getRecordings request
     *
     * @param string $courseid
     * @param string $bigbluebuttonbnid
     * @param bool $subset
     * @param bool $includedeleted.
     *
     * @return string containing the sql used for getting the target bigbluebuttonbn instances
     */
    public static function sql_select_for_recordings($courseid, $bigbluebuttonbnid = null, $subset = true,
        $includedeleted = false) {
        if (empty($courseid)) {
            $courseid = 0;
        }
        $select = "";
        if (!$includedeleted) {
            // Exclude headless recordings from getRecordings requests unless includedeleted.
            $select = "headless = false AND ";
        }
        if (empty($bigbluebuttonbnid)) {
            // Fetch all recordings in given course if bigbluebuttonbnid filter is not included.
            return $select . "courseid = '{$courseid}'";
        }
        if ($subset) {
            // Fetch only one bigbluebutton instance if subset filter is included.
            return $select . "bigbluebuttonbnid = '{$bigbluebuttonbnid}'";
        }
        // Fetch only from one course and instance is used for imported recordings.
        return $select . "bigbluebuttonbnid <> '{$bigbluebuttonbnid}' AND course = '{$courseid}'";
    }

    /**
     * Protect/Unprotect an imported recording.
     *
     * @param string $id
     * @param boolean $protect
     *
     * @return boolean
     */
    public static function bigbluebuttonbn_protect_recording_imported($id, $protect = true) {
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
    public static function bigbluebuttonbn_update_recording_imported($id, $params) {
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
     * Delete an imported recording.
     *
     * @param string $id
     *
     * @return boolean
     */
    public static function bigbluebuttonbn_delete_recording_imported($id) {
        global $DB;
        // Execute delete.
        $DB->delete_records('bigbluebuttonbn_logs', array('id' => $id));
        return true;
    }

    /**
     * Publish an imported recording.
     *
     * @param string $id
     * @param boolean $publish
     *
     * @return boolean
     */
    public static function bigbluebuttonbn_publish_recording_imported($id, $publish = true) {
        global $DB;
        // Locate the record to be updated.
        $record = $DB->get_record('bigbluebuttonbn_logs', array('id' => $id));
        $meta = json_decode($record->meta, true);
        // Prepare data for the update.
        $meta['recording']['published'] = ($publish) ? 'true' : 'false';
        $record->meta = json_encode($meta);
        // Proceed with the update.
        $DB->update_record('bigbluebuttonbn_logs', $record);
        return true;
    }

    /**
     * Helper for performing import on recordings.
     *
     * @param array $bbbsession
     * @param string $recordingid
     * @param string $importmeetingid
     *
     * @return string
     */
    public static function recording_import($bbbsession, $recordingid, $importmeetingid) {
        $recordings = self::fetch_recordings([$recordingid]);
        $overrides = array('meetingid' => $importmeetingid);
        $meta = json_encode((object) [
            'recording' => $recordings[$recordingid]
        ]);
        logs::bigbluebuttonbn_log($bbbsession['bigbluebuttonbn'],
            bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_IMPORT,
            $overrides,
            $meta);
        // Moodle event logger: Create an event for recording imported.
        if (isset($bbbsession['bigbluebutton']) && isset($bbbsession['cm'])) {
            \mod_bigbluebuttonbn\local\helpers\logs::bigbluebuttonbn_event_log(
                events::$events['recording_import'],
                $bbbsession['bigbluebuttonbn'],
                ['other' => $bbbsession['bigbluebuttonbn']->id]
            );
        }
    }
}
