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

use core\task\adhoc_task;
use mod_bigbluebuttonbn\local\proxy\bigbluebutton_proxy;
use mod_bigbluebuttonbn\local\proxy\recording_proxy;

/**
 * Class containing the scheduled task for converting legacy recordings to 2.5.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 Jesus Federico, Blindside Networks Inc <jesus at blindsidenetworks dot com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upgrade_recordings extends adhoc_task {
    /**
     * Run the migration task.
     */
    public function execute() {
        if ($this->process_bigbluebuttonbn_logs()) {
            \core\task\manager::queue_adhoc_task(new upgrade_recordings());
        }
    }

    protected function process_bigbluebuttonbn_logs(): bool {
        global $DB;

        mtrace("Executing upgrade_recordings");

        // Magic number should be increased before release.
        $chunksize = 10;

        // Initialize counter.
        $recordscount = 0;

        // Fetch 'Create' logs that correspnd to recorded meetings.
        $sql = "SELECT *
                FROM {bigbluebuttonbn_logs}
                WHERE log = 'Create' AND meta LIKE '%true%'
                ORDER BY meetingid,courseid,bigbluebuttonbnid,timecreated
                LIMIT {$chunksize};";
        $logs = $DB->get_records_sql($sql);

        // Get meetingids from the fetched logs.
        foreach($logs as $log) {
            $recs[$log->meetingid] = (array)$log;
        }
        $meetingids = array_keys($recs);

        // Retrieve recordings from the meetingids with paginated requests.
        $recordings = recording_proxy::fetch_recordings($meetingids, 'meetingID');

        // Create an instance of bigbluebuttonbn_recording per valid recording.
        foreach($recordings as $recordingid => $recording) {
            $rec = $recs[$recording['meetingID']];
            $newrecording = array(
                'courseid' => $rec['courseid'],
                'bigbluebuttonbnid' => $rec['bigbluebuttonbnid'],
                'recordingid' => $recordingid,
                'headless' => 0, // If activity does not exist, the flag should be set to 1.
                'status' => 2,
            );
            if ($DB->count_records('bigbluebuttonbn_recordings', $newrecording) == 0) {
                $newrecording = $DB->insert_record('bigbluebuttonbn_recordings', $newrecording);
                $newrecording['timecreated'] = $rec['timecreated'];
                $newrecording = $DB->update_record('bigbluebuttonbn_recordings', $newrecording);
            }
        }

        // Delete processed logs.
        foreach($logs as $log) {
            $DB->delete_records('bigbluebuttonbn_logs', array('id' => $log->id));
            $recordscount++;
        }

        return ($recordscount == $chunksize);
    }
}
