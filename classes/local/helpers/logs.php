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
 * The mod_bigbluebuttonbn logs helper
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David  (laurent [at] call-learning [dt] fr)
 */
namespace mod_bigbluebuttonbn\local\helpers;

use context_module;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\bbb_constants;
use stdClass;

/**
 * Utility class for all logs routines helper
 *
 * @package mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class logs {

    /**
     * Register a bigbluebuttonbn event
     *
     * @param object $bigbluebuttonbn
     * @param string $event
     * @param array  $overrides
     * @param string $meta
     *
     * @return bool Success/Failure
     */
    public static function bigbluebuttonbn_log($bigbluebuttonbn, $event, array $overrides = [], $meta = null) {
        global $DB, $USER;
        $log = new stdClass();
        // Default values.
        $log->courseid = $bigbluebuttonbn->course;
        $log->bigbluebuttonbnid = $bigbluebuttonbn->id;
        $log->userid = $USER->id;
        $log->meetingid = $bigbluebuttonbn->meetingid;
        $log->timecreated = time();
        $log->log = $event;
        $log->meta = $meta;
        // Overrides.
        foreach ($overrides as $key => $value) {
            $log->$key = $value;
        }
        if (!$DB->insert_record('bigbluebuttonbn_logs', $log)) {
            return false;
        }
        return true;
    }

    /**
     * Given an ID of an instance of this module,
     * this function will permanently delete the data that depends on it.
     *
     * @param object $bigbluebuttonbn Id of the module instance
     *
     * @return bool Success/Failure
     */
    public static function bigbluebuttonbn_delete_instance_log($bigbluebuttonbn) {
        global $DB;
        $sql = "SELECT * FROM {bigbluebuttonbn_logs} ";
        $sql .= "WHERE bigbluebuttonbnid = ? AND log = ? AND " . $DB->sql_compare_text('meta') . " = ?";
        $logs = $DB->get_records_sql($sql,
            array($bigbluebuttonbn->id, bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_CREATE, "{\"record\":true}"));
        $meta = "{\"has_recordings\":" . empty($logs) ? "true" : "false" . "}";
        static::bigbluebuttonbn_log($bigbluebuttonbn, bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_DELETE, [], $meta);
    }

    /**
     * Helper function to get how much callback events are logged.
     *
     * @param string $recordid
     * @param string $callbacktype
     *
     * @return integer
     */
    public static function bigbluebuttonbn_get_count_callback_event_log($recordid, $callbacktype = 'recording_ready') {
        global $DB;
        $sql = 'SELECT count(DISTINCT id) FROM {bigbluebuttonbn_logs} WHERE log = ? AND meta LIKE ? AND meta LIKE ?';
        // Callback type added on version 2.4, validate recording_ready first or assume it on records with no callback.
        if ($callbacktype == 'recording_ready') {
            $sql .= ' AND (meta LIKE ? OR meta NOT LIKE ? )';
            $count =
                $DB->count_records_sql($sql, array(bbb_constants::BIGBLUEBUTTON_LOG_EVENT_CALLBACK, '%recordid%', "%$recordid%",
                    $callbacktype, 'callback'));
            return $count;
        }
        $sql .= ' AND meta LIKE ?;';
        $count = $DB->count_records_sql($sql,
            array(bbb_constants::BIGBLUEBUTTON_LOG_EVENT_CALLBACK, '%recordid%', "%$recordid%", "%$callbacktype%"));
        return $count;
    }

    /**
     * Helper register a bigbluebuttonbn event.
     *
     * @param string $type
     * @param object $bigbluebuttonbn
     * @param array $options [timecreated, userid, other]
     *
     * @return void
     */
    public static function bigbluebuttonbn_event_log($type, $bigbluebuttonbn, $options = []) {
        global $DB;
        if (!in_array($type, \mod_bigbluebuttonbn\event\events::$events)) {
            // No log will be created.
            return;
        }
        $course = $DB->get_record('course', array('id' => $bigbluebuttonbn->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('bigbluebuttonbn', $bigbluebuttonbn->id, $course->id, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $params = array('context' => $context, 'objectid' => $bigbluebuttonbn->id);
        if (array_key_exists('timecreated', $options)) {
            $params['timecreated'] = $options['timecreated'];
        }
        if (array_key_exists('userid', $options)) {
            $params['userid'] = $options['userid'];
        }
        if (array_key_exists('other', $options)) {
            $params['other'] = $options['other'];
        }
        $event = call_user_func_array(
            '\mod_bigbluebuttonbn\event\\' . $type . '::create',
            array($params)
        );
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('course', $course);
        $event->add_record_snapshot('bigbluebuttonbn', $bigbluebuttonbn);
        $event->trigger();
    }

    /**
     * Log the relevant events for when a meeting was created.
     *
     * @param mod_helper $instance
     */
    public static function log_meeting_created_event(instance $instance): void {
        // Moodle event logger: Create an event for meeting created.
        self::bigbluebuttonbn_event_log(
            \mod_bigbluebuttonbn\event\events::$events['meeting_create'],
            $instance->get_instance_data()
        );

        // Internal logger: Insert a record with the meeting created.
        self::bigbluebuttonbn_log(
            $instance->get_instance_data(),
            bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_CREATE,
            ['meetingid' => $instance->get_meeting_id()],
            json_encode((object) [
                'record' => $instance->is_recorded() ? 'true' : 'false',
            ])
        );
    }

    /**
     * Log the relevant events for when a meeting was joined.
     *
     * @param mod_helper $instance
     * @param int $origin
     */
    public static function log_meeting_joined_event(instance $instance, int $origin): void {
        // Moodle event logger: Create an event for meeting joined.
        self::bigbluebuttonbn_event_log(
            \mod_bigbluebuttonbn\event\events::$events['meeting_join'],
            $instance->get_instance_data()
        );

        // Internal logger: Instert a record with the meeting created.
        self::bigbluebuttonbn_log(
            $instance->get_instance_data(),
            bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_JOIN,
            ['meetingid' => $instance->get_meeting_id()],
            json_encode((object) ['origin' => $origin])
        );
    }

    /**
     * Log the relevant events for when a user left a meeting.
     *
     * @param mod_helper $instance
     */
    public static function log_meeting_left_event(instance $instance): void {
        // Moodle event logger: Create an event for meeting left.
        self::bigbluebuttonbn_event_log(
            \mod_bigbluebuttonbn\event\events::$events['meeting_left'],
            $instance->get_instance_data()
        );
    }

    /**
     * Log the relevant events for when a recording has been played.
     *
     * @param mod_helper $instance
     */
    public static function log_recording_played_event(instance $instance): void {
        // Moodle event logger: Create an event for recording played.
        self::bigbluebuttonbn_event_log(
            \mod_bigbluebuttonbn\event\events::$events['recording_play'],
            $instance->get_instance_data(),
            ['other' => $rid]
        );

        // Internal logger: Instert a record with the playback played.
        self::bigbluebuttonbn_log(
            $instance->get_instance_data(),
            bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_PLAYED,
            ['meetingid' => $instance->get_meeting_id()]
        );
    }
}
