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

namespace mod_bigbluebuttonbn\task;

use coding_exception;
use core\task\scheduled_task;
use Exception;
use mod_bigbluebuttonbn\locallib\bigbluebutton;
use mod_bigbluebuttonbn\server;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class get_recordings extends scheduled_task
{
    /**
     * @return string
     * @throws coding_exception
     */
    public function get_name()
    {
        return get_string('getrecordings', 'mod_bigbluebuttonbn');
    }

    /**
     * This is executed by a cron job, see db/tasks to see the configuration
     * You can manually launch this job by using php cli : sudo -u www-data php admin/cli/scheduled_task.php --execute="\mod_bigbluebuttonbn\task\get_recordings"
     *
     * The function will fetch all meetings id from DB.
     * It'll then fetch all bbb servers from DB.
     * Then loop through servers to interrogate BBB API about recordings related to our meeting ids and store them.
     * We loop through fetched recordings and check our database to avoid inserting recordings element that we already have stored and insert new elements.
     *
     * @throws \Exception
     */
    public function execute()
    {
        global $DB;

        // Save current server to reset it at then end of the job.
        $current_server = \mod_bigbluebuttonbn\locallib\bigbluebutton::$selected_server;

        // Fetch all configurated servers in DB.
        $servers = $DB->get_records_sql("SELECT * FROM {bigbluebuttonbn_servers} WHERE enabled = 1");

        //Set up array to store recordings related to meetings.
        $recordings = array();

        //Loop through servers and interrogate BBB API to fetch recordings.
        foreach ($servers as $server) {
            // Do getRecordings is executed using a method GET (supported by all versions of BBB).
            \mod_bigbluebuttonbn\locallib\bigbluebutton::$selected_server = new \mod_bigbluebuttonbn\server(0, $server);
            $url = \mod_bigbluebuttonbn\locallib\bigbluebutton::action_url('getRecordings');
            $xml = bigbluebuttonbn_wrap_xml_load_file($url);
            if ($xml && $xml->returncode == 'SUCCESS' && isset($xml->recordings)) {
                // If there were meetings already created.
                foreach ($xml->recordings->recording as $recordingxml) {
                    $recording = bigbluebuttonbn_get_recording_array_value($recordingxml);
                    $recordings[$recording['recordID']] = $recording;

                    // Check if there is childs.
                    if (isset($recordingxml->breakoutRooms->breakoutRoom)) {
                        foreach ($recordingxml->breakoutRooms->breakoutRoom as $breakoutroom) {
                            $url = \mod_bigbluebuttonbn\locallib\bigbluebutton::action_url('getRecordings',
                                ['recordID' => implode(',', (array) $breakoutroom)]);
                            $xml = bigbluebuttonbn_wrap_xml_load_file($url);
                            if ($xml && $xml->returncode == 'SUCCESS' && isset($xml->recordings)) {
                                // If there were meetings already created.
                                foreach ($xml->recordings->recording as $recordingxml) {
                                    $recording = bigbluebuttonbn_get_recording_array_value($recordingxml);
                                    $recordings[$recording['recordID']] = $recording;
                                }
                            }
                        }
                    }
                }
            }
        }
        // Reset current server so we're back to where we where before looping through servers.
        \mod_bigbluebuttonbn\locallib\bigbluebutton::$selected_server = $current_server;

        // Fetch recrodings that are already in DB.
        $localrecordings = $DB->get_records_sql("SELECT recordingid from {bigbluebuttonbn_recordings}");

        // Loop through recordings fetched from BBB API.
        foreach ($recordings as $recording) {
            //Check if the recording is already in DB, if not create the insert.
            if (!array_key_exists($recording['recordID'], $localrecordings)) {
                $data = new stdClass();
                $data->recordingid = $recording['recordID'];
                $data->meetingid = $recording['meetingID'];
                $data->meetingname = $recording['meetingName'];
                $data->published = $recording['published'];
                $data->starttime = $recording['startTime'];
                $data->endtime = $recording['endTime'];
                if (!isset($recording['meta_bbb-recording-name'])) {
                    $data->recordingname = is_null($recording['meta_bbb-recording-name']) ? $recording['meetingName'] : $recording['meta_bbb-recording-name'];
                } else {
                    $data->recordingname = $recording['meetingName'];
                }
                if (!isset($recording['meta_bbb-recording-name'])) {
                    $data->recordingdescription = is_null($recording['meta_bbb-recording-description']) ? $recording['meetingName'] : $recording['meta_bbb-recording-description'];
                } else {
                    $data->recordingdescription = $recording['meetingName'];
                }
                $data->recordingdescription = is_null($recording['meta_bbb-recording-description']) ? $recording['meetingName'] : $recording['meta_bbb-recording-description'];
                $data->recordinglink = $recording['playbacks']['presentation']['url'];
                $data->recordingtype = $recording['playbacks']['presentation']['type'];
                $data->recordinglength = $recording['playbacks']['presentation']['length'];
                $data->hostingserverurl = $this->get_hosting_server_url_from_recording_url($recording['playbacks']['presentation']['url']);

                $DB->insert_record('bigbluebuttonbn_recordings', $data);
            }
        }
    }

    public function get_hosting_server_url_from_recording_url($recordingurl) {
        $hostserverurl = explode('/', $recordingurl);
        $hostserverurl = $hostserverurl[0] . '//' . $hostserverurl[2] . '/bigbluebutton';
        return $hostserverurl;
    }
}
