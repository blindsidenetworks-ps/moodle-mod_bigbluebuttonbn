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
 * @author    Laurent David  (laurent.david [at] call-learning [dt] fr)
 */

namespace mod_bigbluebuttonbn\bigbluebutton\recordings;

use stdClass;
use mod_bigbluebuttonbn\bigbluebutton\recordings\recording;
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
    public function get_recordings1($courseid = 0, $bigbluebuttonbnid = null, $subset = true,
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
                    error_log(">>>>>>>>>>>>>>>>>>>>>>");
                    error_log(gettype($bbbrecording));
                    error_log(json_encode($bbbrecording));
                } else if (empty($dbrecording->recording)) {
                    continue;
                } else {
                    error_log("----------------------");
                    $dbrecording->recording = json_decode($dbrecording->recording, true);
                    error_log(gettype($dbrecording->recording));
                    error_log(json_encode($bbbrecording));
                }
                $recordings[$dbrecording->recordingid] = $dbrecording;
            }
        }
        return $recordings;
    }


    public function get_recordings($courseid = 0, $bigbluebuttonbnid = null, $subset = true,
        $includedeleted = false, $includeimported = false) {
        global $DB;
        // Retrieve DB recordings.
        $select = $this->sql_select_for_recordings($courseid, $bigbluebuttonbnid, $subset, $includedeleted, $includeimported);
        $dbrecordings = $DB->get_records_select('bigbluebuttonbn_recordings', $select, null, 'id');
        // Fetch BBB recordings.
        $recordingsids = $DB->get_records_select_menu('bigbluebuttonbn_recordings', $select, null, 'id', 'id, recordingid');
        $bbbrecordings = $this->fetch_recordings(array_values($recordingsids));

        /* Activities set to be recorded insert a bigbluebuttonbn_recording row on create, but it does not mean that
         * the meeting was recorded. We are responding only with the ones that have a processed recording in BBB.
         */
        $recordings = array();
        foreach ($dbrecordings as $id => $dbrecording) {
            $recordingid = $dbrecording->recordingid;
            // If there is not a BBB recording assiciated, continue.
            if (!isset($bbbrecordings[$recordingid])) {
                continue;
            }
            // Always assign the recording value fetched from BBB.
            $dbrecording->recording = $bbbrecordings[$recordingid];
            // But if the recording was imported, override the metadata with the value stored in the database.
            if ($dbrecording->imported) {
                $importedrecording = $dbrecording->recording;
                foreach($importedrecording as $varname => $value) {
                    $varnames = explode('_', $varname);
                    if ($varnames[0] == 'meta' ) {
                        $dbrecording->recording[$varname] = $value;
                    }
                }
            }
            // Finally, add the dbrecording to the indexed array to be returned.
            $recordings[$recordingid] = $dbrecording;
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
    public function fetch_recordings($recordingids = []) {
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

    /**
     * Helper function iterates an array with recordings and unset those already imported.
     *
     * @param array $recordings the source recordings.
     * @param integer $courseid
     * @param integer $bigbluebuttonbnid
     *
     * @return array
     */
    public function unset_existent_imported_recordings($recordings, $courseid, $bigbluebuttonbnid) {
        global $DB;
        // Retrieve DB imported recordings.
        $select = $this->sql_select_for_imported_recordings($courseid, $bigbluebuttonbnid, true);
        $dbrecordings = $DB->get_records_select('bigbluebuttonbn_recordings', $select, null, 'id');
        // Index the $importedrecordings for the response.
        $importedrecordings = array();
        foreach ($dbrecordings as $id => $dbrecording) {
            $importedrecordings[$dbrecording->recordingid] = $dbrecording;
        }
        // Unset from $recordings if recording is already imported.
        foreach ($recordings as $recordingid => $recording) {
            if (isset($importedrecordings[$recordingid])) {
                unset($recordings[$recordingid]);
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
     *
     * @return string containing the sql used for getting the target bigbluebuttonbn instances
     */
    private function sql_select_for_imported_recordings($courseid, $bigbluebuttonbnid = null, $subset = true,
        $includedeleted = false) {
        if (empty($courseid)) {
            $courseid = 0;
        }
        $select = "imported = true AND ";
        // Start with the filters.
        if (!$includedeleted) {
            // Exclude headless recordings unless includedeleted.
            $select .= "headless = false AND ";
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
}
