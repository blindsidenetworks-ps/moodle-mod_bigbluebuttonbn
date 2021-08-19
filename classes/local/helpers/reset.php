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
 * The mod_bigbluebuttonbn resetting instance helper
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David  (laurent [at] call-learning [dt] fr)
 */

namespace mod_bigbluebuttonbn\local\helpers;

use context_module;
use core_tag_tag;
use mod_bigbluebuttonbn\local\bigbluebutton\recordings\recording;
use mod_bigbluebuttonbn\local\bigbluebutton\recordings\recording_helper;
use mod_bigbluebuttonbn\local\config;

/**
 * Utility class for resetting instance routines helper
 *
 * @package mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reset {

    /**
     * Used by the reset_course_userdata for deleting recordings in a BBB server linked to bigbluebuttonbn instances in the course.
     *
     * @param int $courseid
     */
    public static function reset_recordings(int $courseid): void {
        global $DB;

        $course = $DB->get_record('course', ['id' => $courseid]);

        // Criteria for search : courseid or bigbluebuttonbn=null or subset=false or includedeleted=true.
        $recordings = recording_helper::get_recordings_for_course(
            $course,
            [], // Exclude itself.
            false,
            true
        );

        // Remove all the recordings.
        recording::delete(implode(",", array_keys($recordings)));
    }

    /**
     * Used by the reset_course_userdata for deleting tags linked to bigbluebuttonbn instances in the course.
     *
     * @param array $courseid
     * @return array status array
     */
    public static function reset_tags($courseid) {
        global $DB;
        // Remove all the tags linked to the room/activities in this course.
        if ($bigbluebuttonbns = $DB->get_records('bigbluebuttonbn', array('course' => $courseid))) {
            foreach ($bigbluebuttonbns as $bigbluebuttonbn) {
                if (!$cm = get_coursemodule_from_instance('bigbluebuttonbn', $bigbluebuttonbn->id, $courseid)) {
                    continue;
                }
                $context = context_module::instance($cm->id);
                core_tag_tag::delete_instances('mod_bigbluebuttonbn', null, $context->id);
            }
        }
    }

    /**
     * Used by the reset_course_userdata for deleting events linked to bigbluebuttonbn instances in the course.
     *
     * @param string $courseid
     * @return bool status array
     */
    public static function reset_events($courseid) {
        global $DB;
        // Remove all the events.
        return $DB->delete_records('event', array('modulename' => 'bigbluebuttonbn', 'courseid' => $courseid));
    }

    /**
     * Returns status used on every defined reset action.
     *
     * @param string $item
     * @return array status array
     */
    public static function reset_getstatus($item) {
        return array('component' => get_string('modulenameplural', 'bigbluebuttonbn')
        , 'item' => get_string("removed{$item}", 'bigbluebuttonbn')
        , 'error' => false);
    }

    /**
     * Define items to be reset by course/reset.php
     *
     * @return array
     */
    public static function reset_course_items() {
        $items = array("events" => 0, "tags" => 0, "logs" => 0);
        // Include recordings only if enabled.
        if ((boolean) config::recordings_enabled()) {
            $items["recordings"] = 0;
        }
        return $items;
    }
}
