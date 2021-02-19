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
use mod_bigbluebuttonbn\local\bbb_constants;
use stdClass;

defined('MOODLE_INTERNAL') || die();

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
}