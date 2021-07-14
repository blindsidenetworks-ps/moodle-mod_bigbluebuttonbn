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
 * The mod_bigbluebuttonbn meetings helper
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David  (laurent [at] call-learning [dt] fr)
 */
namespace mod_bigbluebuttonbn\local\helpers;

use cache;
use cache_store;
use coding_exception;
use context_course;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\bbb_constants;
use mod_bigbluebuttonbn\local\bigbluebutton;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\meeting;
use mod_bigbluebuttonbn\plugin;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Utility class for meetings helper
 *
 * @package mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class meeting_helper {

    /**
     * Helper function enqueues list of meeting events to be stored and processed as for completion.
     *
     * @param object $bigbluebuttonbn
     * @param object $jsonobj
     *
     * @return void
     */
    public static function bigbluebuttonbn_process_meeting_events($bigbluebuttonbn, $jsonobj) {
        $meetingid = $jsonobj->{'meeting_id'};
        $recordid = $jsonobj->{'internal_meeting_id'};
        $attendees = $jsonobj->{'data'}->{'attendees'};
        foreach ($attendees as $attendee) {
            $userid = $attendee->{'ext_user_id'};
            $overrides['meetingid'] = $meetingid;
            $overrides['userid'] = $userid;
            $meta['recordid'] = $recordid;
            $meta['data'] = $attendee;
            // Stores the log.
            logs::bigbluebuttonbn_log($bigbluebuttonbn, bbb_constants::BIGBLUEBUTTON_LOG_EVENT_SUMMARY, $overrides,
                json_encode($meta));
            // Enqueue a task for processing the completion.
            bigbluebutton::bigbluebuttonbn_enqueue_completion_update($bigbluebuttonbn, $userid);
        }
    }

    /**
     * Helper for evaluating if meeting can be joined.
     *
     * @param  stdClass $bigbluebuttonbn  BigBlueButtonBN instance
     * @param  string   $mid
     * @param  integer  $userid
     *
     * @return array    status (user allowed to join or not and possible message)
     */
    public static function bigbluebuttonbn_user_can_join_meeting($bigbluebuttonbn, $mid = null, $userid = null) {
        // By default, use a meetingid without groups.
        if (empty($mid)) {
            $mid = $bigbluebuttonbn->meetingid . '-' . $bigbluebuttonbn->course . '-' . $bigbluebuttonbn->id;
        }
        $instance = instance::get_from_instanceid($bigbluebuttonbn->id);
        // When meeting is running, all authorized users can join right in.
        $meeting = new meeting($instance);
        if ($meeting->is_running()) {
            return array(true, get_string('view_message_conference_in_progress', 'bigbluebuttonbn'));
        }
        // When meeting is not running, see if the user can join.
        $context = context_course::instance($bigbluebuttonbn->course);
        $participantlist = roles::bigbluebuttonbn_get_participant_list($bigbluebuttonbn, $context);
        $isadmin = is_siteadmin($userid);
        $ismoderator = roles::bigbluebuttonbn_is_moderator($context, $participantlist, $userid);
        // If user is administrator, moderator or if is viewer and no waiting is required, join allowed.
        if ($isadmin || $ismoderator || !$bigbluebuttonbn->wait) {
            return array(true, get_string('view_message_conference_room_ready', 'bigbluebuttonbn'));
        }
        // Otherwise, no join allowed.
        return array(false, get_string('view_message_conference_wait_for_moderator', 'bigbluebuttonbn'));
    }

    /**
     * Helper for evaluating if meeting can be joined.
     *
     * @param instance $instance
     * @param boolean $running
     * @param boolean $participantcount
     *
     * @return array
     */
    public static function meeting_info_can_join($instance, $running, $participantcount) {
        $status = array("can_join" => false);
        if ($running) {
            $status["message"] = get_string('view_error_userlimit_reached', 'bigbluebuttonbn');
            if ($instance->get_user_limit() == 0 || $participantcount < $instance->get_user_limit()) {
                $status["message"] = get_string('view_message_conference_in_progress', 'bigbluebuttonbn');
                $status["can_join"] = true;
            }
            return $status;
        }
        // If user is administrator, moderator or if is viewer and no waiting is required.
        $status["message"] = get_string('view_message_conference_wait_for_moderator', 'bigbluebuttonbn');
        if ($instance->is_admin() || $instance->is_admin() | !$instance->user_must_wait_to_join()) {
            $status["message"] = get_string('view_message_conference_room_ready', 'bigbluebuttonbn');
            $status["can_join"] = true;
        }
        return $status;
    }
}
