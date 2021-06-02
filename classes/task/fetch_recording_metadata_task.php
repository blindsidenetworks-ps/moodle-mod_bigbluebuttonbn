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

defined('MOODLE_INTERNAL') || die();

// Time between checking the same record.
const TIME_BETWEEN_SUBSEQUENT_CHECKS = 20 * MINSECS;
// After 2 weeks from the meeting start date, if no recording metadata is
// returned, consider the meeting as recording-free.
const TIME_BEFORE_FLAGGING_AS_NOT_RECORDED = 2 * WEEKSECS;
// This is a limit documented in code @  bigbluebuttonbn_get_recordings_array_fetch_page function call.
const BBB_API_GET_RECORDINGS_RECORDS_PER_REQUEST = 25;
// Default processing limit for backlog of BBB sessions.
const DEFAULT_PROCESSING_LIMIT = 100;

/**
 * A scheduled task to fetch the recordings' metadata and store it for further reporting
 *
 * @package    mod_bigbluebuttonbn
 * @author     Kevin Pham <kevinpham@catalyst-au.net>
 * @copyright  Catalyst IT, 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fetch_recording_metadata_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('fetchmetaforrecordings', 'mod_bigbluebuttonbn');
    }

    /**
     * Run chat cron.
     */
    public function execute() {
        require_once(__DIR__.'/../../locallib.php');
        global $CFG, $DB;

        $sql = "SELECT recordid, id, *
                  FROM {bigbluebuttonbn_logs}
                 WHERE recordid IS NOT NULL
                   AND log = :log
                   AND ".$DB->sql_like('meta', ':recordtrue')."
                   AND ".$DB->sql_like('meta', ':recordedfalse', false, false, $notlike = true)."
                   AND (
                       ".$DB->sql_like('meta', ':recordinglastmodified', false, false, $notlike = true)."
                       OR ".$DB->sql_like('meta', ':endtime', false, false, $notlike = true).")
                 LIMIT :limit
                   ";
        $meetingswithnorecordingmeta = $DB->get_records_sql($sql, [
            'log' => BIGBLUEBUTTONBN_LOG_EVENT_CREATE,
            'recordtrue' => '%"record":"true"%', // Recording is enabled.
            'recordedfalse' => '%"recorded":false%', // Recording did not happen (set after meeting ended).
            'recordinglastmodified' => '%recordinglastmodified%',
            'endtime' => '%endtime%',
            'limit' => DEFAULT_PROCESSING_LIMIT
        ]);
        // Filter out all the meetings that have been recently checked, so we can.
        $relevantmeetings = [];
        foreach ($meetingswithnorecordingmeta as $recordid => $meeting) {
            $meta = json_decode($meeting->meta);
            // Check the time for lastchecked, and make sure that it has been at least
            // 20 minutes from the last check for it to check again.
            if (!isset($meta->lastchecked) || time() > $meta->lastchecked + TIME_BETWEEN_SUBSEQUENT_CHECKS) {
                $relevantmeetings[$recordid] = $meeting;
            }
        }

        // Split the records to process in chunks, such that they will not go over the
        // 25 record response from BBB's getRecordings call.
        $chunkedmeetings = array_chunk($relevantmeetings, BBB_API_GET_RECORDINGS_RECORDS_PER_REQUEST, $preservekeys = true);

        foreach ($chunkedmeetings as $relevantmeetings) {
            // Fetch and update the record's recordinglastmodified timestamp.
            $recordids = array_keys($relevantmeetings);
            $metas = bigbluebuttonbn_get_recordings_meta($recordids);
            // Fetch and set the endtime for the meeting based on the recording info.
            $recordings = bigbluebuttonbn_get_recordings_array_fetch_page([], $recordids);
            // Loop through and perform updates as required based on information gathered.
            foreach ($relevantmeetings as $recordid => $meeting) {
                $meta = json_decode($meeting->meta);
                // Ensure it only sets the metadata if it has not already been set or is empty.
                if (empty($meta->recordinglastmodified) && isset($metas[$recordid]['lastmodified'])) {
                    $meta->recordinglastmodified = $metas[$recordid]['lastmodified'];
                }
                if (empty($meta->filesize) && isset($metas[$recordid]['filesize'])) {
                    $meta->filesize = $metas[$recordid]['filesize'];
                }
                if (empty($meta->playbackduration) && isset($metas[$recordid]['playbackduration'])) {
                    $meta->playbackduration = $metas[$recordid]['playbackduration'];
                }
                if (empty($meta->processingduration) && isset($metas[$recordid]['processingduration'])) {
                    $meta->processingduration = $metas[$recordid]['processingduration'];
                }
                // This sets the wait/queue time before the video starts being processed.
                if (empty($meta->queueduration) &&
                        !empty($metas[$recordid]['lastmodified']) &&
                        !empty($metas[$recordid]['processingduration']) &&
                        $recordings[$recordid]['endTime']) {
                    // Queue duration = last modified - processing duration - end time (for meeting).
                    $meta->queueduration = $metas[$recordid]['lastmodified'] -
                        $metas[$recordid]['processingduration'] -
                        $recordings[$recordid]['endTime'];
                }

                // Mark the record if no recording was detected, or should be considered recording-free.
                if (!isset($recordings[$recordid])) {
                    // Check and see if the record has a lastchecked already linked to it.
                    // If it has surpassed the threshold, it is safe to consider the meeting
                    // as having not been recorded.
                    if (time() > $meeting->timecreated + TIME_BEFORE_FLAGGING_AS_NOT_RECORDED) {
                        unset($meta->lastchecked);
                        $meta->recorded = false;
                    } else {
                        // Set a lastchecked timestamp, to ensure subsequent requests within a
                        // certain threshold (defaulting to 20 minutes) are not made and the
                        // requesting is skipped.
                        $meta->lastchecked = time();
                    }

                    $DB->update_record('bigbluebuttonbn_logs', ['id' => $meeting->id, 'meta' => json_encode($meta)]);
                    continue;
                }

                $endtime = $recordings[$recordid]['endTime'];
                $meta->endtime = $endtime;
                $meta->recordingprocessingtime = $meta->recordinglastmodified - $meta->endtime;
                unset($meta->lastchecked); // Clear this value if it exists as it is only relevant for processing the queue.
                $DB->update_record('bigbluebuttonbn_logs', ['id' => $meeting->id, 'meta' => json_encode($meta)]);
            }
        }
    }
}
