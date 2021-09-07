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
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\proxy\recording_proxy;

/**
 * Class containing the scheduled task for converting legacy recordings to 2.5.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 Jesus Federico, Blindside Networks Inc <jesus at blindsidenetworks dot com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upgrade_imported_recordings extends adhoc_task {
    /**
     * Run the migration task.
     */
    public function execute() {
        if ($this->process_bigbluebuttonbn_logs()) {
            \core\task\manager::queue_adhoc_task(new upgrade_recordings());
        }
    }

    /**
     * Process all bigbluebuttonbn logs looking for entries which should be converted to meetings.
     */
    protected function process_bigbluebuttonbn_logs(): bool {
        global $DB;

        mtrace("Executing upgrade_recordings...");

        // Magic number should be increased before release.
        $chunksize = 10;
        $expected = $chunksize; // Expected number of records to process.

        // Initialize counter.
        $recordscount = 0;

        // Fetch 'Create' logs that correspnd to imported recordings.
        $sql = "SELECT *
                FROM {bigbluebuttonbn_logs}
                WHERE log = 'Import'
                ORDER BY courseid,bigbluebuttonbnid,meetingid,timecreated
                LIMIT {$chunksize};";
        $logs = $DB->get_records_sql($sql);

        if ($logs) {
            // Get instanceids from the fetched logs.
            $recsbyinstanceid = [];
            foreach ($logs as $log) {
                $recsbyinstanceid[$log->bigbluebuttonbnid] = (array) $log;
            }
            $instanceids = array_keys($recsbyinstanceid);

            // Fetch instances that correspnd to instanceids selected.
            $instances = $DB->get_records_list('bigbluebuttonbn', 'id', $instanceids);

            // Create an instance of bigbluebuttonbn_recording per valid imported recording.
            mtrace("Creating new recording records...");
            foreach ($logs as $log) {
                $recording = json_decode($log->meta, true)['recording'];
                $instance = instance::get_from_meetingid($recording['meetingID']);
                $newrecording = array(
                    'courseid' => $instance->get_course_id(),
                    'bigbluebuttonbnid' => $instance->get_instance_id(),
                    'groupid' => $instance->get_group_id(), // The groupid should be taken from the meetingID.
                    'recordingid' => $recording['recordID'],
                    'status' => 2,
                    'imported' => 1,
                );
                if ($DB->count_records('bigbluebuttonbn_recordings', $newrecording) == 0) {
                    // Set headless flag to 1 if activity does not exist.
                    $newrecording['headless'] = !in_array($log->bigbluebuttonbnid, (array) $instances) ? 1 : 0;
                    $newrecording['importeddata'] = json_encode($recording);
                    $newrecording['timecreated'] = $log->timecreated;
                    $newrecording['timemodified'] = $log->timecreated;
                    $newrecording = $DB->insert_record('bigbluebuttonbn_recordings', $newrecording);
                    mtrace(json_encode($newrecording));
                }
            }

            // Delete processed logs.
            mtrace("Deleting migrated log records...");
            foreach ($logs as $log) {
                mtrace(json_encode($DB->get_records('bigbluebuttonbn_logs', array('id' => $log->id))));
                $DB->delete_records('bigbluebuttonbn_logs', array('id' => $log->id));
                $recordscount++;
            }
            $expected = count($logs) < $chunksize ? count($logs) : $chunksize;
        } else {
            $expected = 0;
        }
        return ($recordscount == $expected);
    }
}
