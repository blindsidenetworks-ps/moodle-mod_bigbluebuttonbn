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
 * Instance record for mod_bigbluebuttonbn.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 Andrew Lyons <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_bigbluebuttonbn;

use mod_bigbluebuttonbn\local\bigbluebutton;
use mod_bigbluebuttonbn\local\broker;
use mod_bigbluebuttonbn\local\helpers\meeting as meeting_helper;
use stdClass;

class meeting {

    /** @var instance The bbb instance */
    protected $instance;

    /**
     * Constructor for the meeting object.
     *
     * @param instance $instance
     */
    public function __construct(instance $instance) {
        $this->instance = $instance;
    }

    /**
     * Force an update of the meeting cache for this meeting.
     */
    public function update_meeting_cache(): void {
        meeting_helper::bigbluebuttonbn_get_meeting_info($this->instance->get_meeting_id(), true);
    }

    /**
     * Force an update of the meeting cache for this instance.
     *
     * @param instance $instance
     */
    public static function update_meeting_cache_for_instance(instance $instance): void {
        $meeting = new self($instance);
        $meeting->update_meeting_info_cache();
    }

    /**
     * Return meeting information for this meeting.
     *
     * @param bool $updatecache Whether to update the cache when fetching the information
     * @return stdClass
     */
    public function get_meeting_info(bool $updatecache = false): stdClass {
        $instance = $this->instance;

        $info = meeting_helper::bigbluebuttonbn_get_meeting_info($instance->get_meeting_id(), $updatecache);
        $isrunning = $info['returncode'] === 'SUCCESS' && $info['running'] === 'true';
        $bbbsession = $instance->get_legacy_session_object();
        $activitystatus = bigbluebutton::bigbluebuttonbn_view_get_activity_status($bbbsession);

        $meetinginfo = (object) [
            'instanceid' => $instance->get_instance_id(),
            'bigbluebuttonbnid' => $instance->get_instance_id(),
            'meetingid' => $instance->get_meeting_id(),
            'cmid' => $instance->get_cm_id(),
            'ismoderator' => $instance->is_moderator(),

            'joinurl' => $instance->get_join_url()->out(),
            'openingtime' => $instance->get_instance_var('openingtime'),
            'closingtime' => $instance->get_instance_var('closingtime'),

            'statusrunning' => $isrunning,
            'statusclosed' => $activitystatus === 'ended',
            'statusopen' => !$isrunning && $activitystatus === 'open',

            'userlimit' => $instance->get_user_limit(),
            'group' => $instance->get_group_id(),

            'presentations' => [],
        ];

        $participantcount = isset($info['participantCount']) ? $info['participantCount'] : 0;
        $meetinginfo->participantcount = $participantcount;
        $status = broker::meeting_info_can_join(
            $instance->get_legacy_session_object(),
            $isrunning,
            $meetinginfo->participantcount
        );
        $meetinginfo->canjoin = $status["can_join"];

        // If user is administrator, moderator or if is viewer and no waiting is required, join allowed.
        if ($isrunning) {
            $meetinginfo->statusmessage = get_string('view_message_conference_in_progress', 'bigbluebuttonbn');
            $meetinginfo->startedat = floor(intval($info['startTime']) / 1000); // Milliseconds.
            $meetinginfo->moderatorcount = $info['moderatorCount'];
            $meetinginfo->moderatorplural = $info['moderatorCount'] > 1;
            $meetinginfo->participantcount = $info['participantCount'];
            $meetinginfo->participantplural = $info['participantCount'] > 1;
        } else {
            if ($instance->user_must_wait_to_join()) {
                $meetinginfo->statusmessage = get_string('view_message_conference_wait_for_moderator', 'bigbluebuttonbn');
            } else {
                $meetinginfo->statusmessage = get_string('view_message_conference_room_ready', 'bigbluebuttonbn');
            }
        }

        $presentation = $instance->get_presentation();
        if (!empty($presentation)) {
            $meetinginfo->presentations[] =  $presentation;
        }

        return $meetinginfo;
    }

    /**
     * Return meeting information for the specified instance.
     *
     * @param instance $instance
     * @param bool $updatecache Whether to update the cache when fetching the information
     * @return stdClass
     */
    public static function get_meeting_info_for_instance(instance $instance, bool $updatecache): stdClass {
        $meeting = new self($instance);
        return $meeting->get_meeting_info($updatecache);
    }
}
