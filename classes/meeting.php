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

use cache;
use cache_store;
use mod_bigbluebuttonbn\local\bigbluebutton;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\local\exceptions\bigbluebutton_exception;
use mod_bigbluebuttonbn\local\exceptions\server_not_available_exception;
use mod_bigbluebuttonbn\local\helpers\meeting_helper as meeting_helper;
use stdClass;

/**
 * Class meeting
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 Andrew Lyons <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class meeting {

    /** @var instance The bbb instance */
    protected $instance;

    /** @var stdClass Info about the meeting */
    protected $meetinginfo = null;

    /**
     * Constructor for the meeting object.
     *
     * @param instance $instance
     */
    public function __construct(instance $instance) {
        $this->instance = $instance;
    }

    /**
     * Get currently stored meeting info
     *
     * @return mixed|stdClass
     * @throws \coding_exception
     */
    public function get_meeting_info() {
        if (!$this->meetinginfo) {
            $this->meetinginfo = $this->do_get_meeting_info();
        }
        return $this->meetinginfo;
    }

    /**
     * Return meeting information for the specified instance.
     *
     * @param instance $instance
     * @param bool $updatecache Whether to update the cache when fetching the information
     * @return stdClass
     */
    public static function get_meeting_info_for_instance(instance $instance, bool $updatecache = false): stdClass {
        $meeting = new self($instance);
        return $meeting->do_get_meeting_info($updatecache);
    }

    /**
     * Helper function returns a sha1 encoded string that is unique and will be used as a seed for meetingid.
     *
     * @return string
     */
    public static function get_unique_meetingid_seed() {
        global $DB;
        do {
            $encodedseed = sha1(plugin::bigbluebuttonbn_random_password(12));
            $meetingid = (string) $DB->get_field('bigbluebuttonbn', 'meetingid', array('meetingid' => $encodedseed));
        } while ($meetingid == $encodedseed);
        return $encodedseed;
    }

    /**
     * Is meeting running ?
     *
     * @return bool
     */
    public function is_running() {
        return $this->get_meeting_info()->statusrunning ?? false;
    }

    /**
     * Force update the meeting in cache.
     */
    public function update_cache() {
        $this->meetinginfo = $this->do_get_meeting_info(true);
    }

    /**
     * Get meeting attendees
     *
     * @return mixed
     */
    public function get_attendees() {
        return $this->get_meeting_info()->attendees ?? [];
    }

    /**
     * Can the meeting be joined ?
     *
     * @return bool
     */
    public function can_join() {
        return $this->get_meeting_info()->canjoin;
    }

    /**
     * Number of participants
     *
     * @return int
     */
    public function get_participant_count() {
        return $this->get_meeting_info()->participantcount;
    }

    /**
     * Creates a bigbluebutton meeting, send the message to BBB and returns the response in an array.
     *
     * @param array $data
     * @param array $metadata
     * @param string $pname
     * @param string $purl
     *
     * @return array
     * @throws bigbluebutton_exception
     * @throws server_not_available_exception
     */
    public function create_meeting() {
        $data = $this->create_meeting_data();
        $metadata = $this->create_meeting_metadata();
        $presentationname = $this->instance->get_presentation()['name'] ?? null;
        $presentationurl = $this->instance->get_presentation()['url'] ?? null;
        return bigbluebutton::create_meeting($data, $metadata, $presentationname, $presentationurl);
    }

    /**
     * Send an end meeting message to BBB server
     */
    public function end_meeting() {
        bigbluebutton::end_meeting($this->instance->get_meeting_id(), $this->instance->get_moderator_password());
    }

    /**
     * Get meeting join URL
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_join_url() {
        return bigbluebutton::bigbluebuttonbn_get_join_url(
            $this->instance->get_meeting_id(),
            $this->instance->get_user_fullname(),
            $this->instance->get_current_user_password(),
            $this->instance->get_logout_url()->out(false),
            null,
            $this->instance->get_user_id(),
            $this->get_meeting_info()->createtime
        );
    }

    /**
     * Return meeting information for this meeting.
     *
     * @param bool $updatecache Whether to update the cache when fetching the information
     * @return stdClass
     */
    protected function do_get_meeting_info(bool $updatecache = false): stdClass {
        $instance = $this->instance;
        $meetinginfo = $instance->get_instance_info();
        $activitystatus = bigbluebutton::bigbluebuttonbn_view_get_activity_status($instance);
        // This might raise an exception if info cannot be retrieved.
        // But this might be totally fine as the meeting is maybe not yet created on BBB side.
        $participantcount = 0;
        try {
            $info = self::retrieve_cached_meeting_info($this->instance->get_meeting_id(), $updatecache);
            $meetinginfo->statusrunning = $info['running'] === 'true';
            $meetinginfo->createtime = $info['createTime'] ?? null;
            $participantcount = isset($info['participantCount']) ? $info['participantCount'] : 0;
        } catch (bigbluebutton_exception $e) {
            // The meeting is not created on BBB side, so we have to setup a couple of values here.
            $meetinginfo->statusrunning = false;
            $meetinginfo->createtime = null;
        }
        $meetinginfo->statusclosed = $activitystatus === 'ended';
        $meetinginfo->statusopen = !$meetinginfo->statusrunning && $activitystatus === 'open';
        $meetinginfo->participantcount = $participantcount;
        $meetinginfo->canjoin = false;

        $canforcejoin = $instance->is_admin() || $instance->is_moderator();
        if ($meetinginfo->statusrunning) {
            if (!$instance->has_user_limit_been_reached($participantcount)
                || !$instance->does_current_user_count_towards_user_limit()
            ) {
                $meetinginfo->canjoin = true;
            }
        }
        if ($instance->is_room_available() && $canforcejoin) {
            $meetinginfo->canjoin = true;
        }
        // Double check that the user has the capabilities to join.
        $meetinginfo->canjoin = $meetinginfo->canjoin && $instance->can_join();

        // If user is administrator, moderator or if is viewer and no waiting is required, join allowed.
        if ($meetinginfo->statusrunning) {
            $meetinginfo->statusmessage = get_string('view_message_conference_in_progress', 'bigbluebuttonbn');
            $meetinginfo->startedat = floor(intval($info['startTime']) / 1000); // Milliseconds.
            $meetinginfo->moderatorcount = $info['moderatorCount'];
            $meetinginfo->moderatorplural = $info['moderatorCount'] > 1;
            $meetinginfo->participantcount = $info['participantCount'] ?? 0;
            $meetinginfo->participantplural = $meetinginfo->participantcount > 1;
        } else {
            if ($instance->user_must_wait_to_join() && !$canforcejoin) {
                $meetinginfo->statusmessage = get_string('view_message_conference_wait_for_moderator', 'bigbluebuttonbn');
            } else {
                $meetinginfo->statusmessage = get_string('view_message_conference_room_ready', 'bigbluebuttonbn');
            }
        }

        $presentation = $instance->get_presentation();
        if (!empty($presentation)) {
            $meetinginfo->presentations[] = $presentation;
        }
        $meetinginfo->attendees = [];

        $meetinginfo->attendees = $info['attendees'] ?? [];
        return $meetinginfo;
    }

    /**
     * Gets a meeting info object cached or fetched from the live session.
     *
     * @param string $meetingid
     * @param boolean $updatecache
     *
     * @return array
     * @throws \coding_exception
     * @throws bigbluebutton_exception
     */
    protected static function retrieve_cached_meeting_info($meetingid, $updatecache = false) {
        $cachettl = (int) config::get('waitformoderator_cache_ttl');
        $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'meetings_cache');
        $result = $cache->get($meetingid);
        $now = time();
        if (!$updatecache && !empty($result) && $now < ($result['creation_time'] + $cachettl)) {
            // Use the value in the cache.
            return (array) json_decode($result['meeting_info']);
        }
        $cache->delete($meetingid); // Make sure we purges the cache before checking info.
        // Ping again and refresh the cache.
        $meetinginfo = bigbluebutton::get_meeting_info($meetingid);

        $cache->set($meetingid, array('creation_time' => time(), 'meeting_info' => json_encode($meetinginfo)));
        return $meetinginfo;
    }

    /**
     * Helper to prepare data used for create meeting.
     *
     * @param instance $instance
     * @return array
     */
    protected function create_meeting_data() {
        $data = ['meetingID' => $this->instance->get_meeting_id(),
            'name' => \mod_bigbluebuttonbn\plugin::bigbluebuttonbn_html2text($this->instance->get_meeting_name(), 64),
            'attendeePW' => $this->instance->get_viewer_password(),
            'moderatorPW' => $this->instance->get_moderator_password(),
            'logoutURL' => $this->instance->get_logout_url()->out(false),
        ];
        $data['record'] = $this->instance->should_record() ? 'true' : 'false';
        // Check if auto_start_record is enable.
        if ($data['record'] == 'true' && $this->instance->should_record_from_start()) {
            $data['autoStartRecording'] = 'true';
            // Check if hide_record_button is enable.
            if (!$this->instance->should_show_recording_button()) {
                $data['allowStartStopRecording'] = 'false';
            }
        }
        $data['welcome'] = trim($this->instance->get_welcome_message());
        $voicebridge = intval($this->instance->get_voice_bridge());
        if ($voicebridge > 0 && $voicebridge < 79999) {
            $data['voiceBridge'] = $voicebridge;
        }
        $maxparticipants = intval($this->instance->get_user_limit());
        if ($maxparticipants > 0) {
            $data['maxParticipants'] = $maxparticipants;
        }
        if ($this->instance->get_mute_on_start()) {
            $data['muteOnStart'] = 'true';
        }
        return $data;
    }

    /**
     * Helper for preparing metadata used while creating the meeting.
     *
     * @param instance $instance
     * @return array
     */
    protected function create_meeting_metadata() {
        global $USER;
        // Create standard metadata.
        $origindata = $this->instance->get_origin_data();
        $metadata = [
            'bbb-origin' => $origindata->origin,
            'bbb-origin-version' => $origindata->originVersion,
            'bbb-origin-server-name' => $origindata->originServerName,
            'bbb-origin-server-common-name' => $origindata->originServerCommonName,
            'bbb-origin-tag' => $origindata->originTag,
            'bbb-context' => $this->instance->get_course()->fullname,
            'bbb-context-id' => $this->instance->get_course_id(),
            'bbb-context-name' => trim(html_to_text($this->instance->get_course()->fullname, 0)),
            'bbb-context-label' => trim(html_to_text($this->instance->get_course()->shortname, 0)),
            'bbb-recording-name' => plugin::bigbluebuttonbn_html2text($this->instance->get_meeting_name(), 64),
            'bbb-recording-description' => plugin::bigbluebuttonbn_html2text($this->instance->get_meeting_description(),
                64),
            'bbb-recording-tags' => \mod_bigbluebuttonbn\plugin::bigbluebuttonbn_get_tags($this->instance->get_cm_id()),
            // Same as $id.
        ];
        // Special metadata for recording processing.
        if ((boolean) config::get('recordingstatus_enabled')) {
            $metadata["bn-recording-status"] = json_encode(
                array(
                    'email' => array('"' . fullname($USER) . '" <' . $USER->email . '>'),
                    'context' => $this->instance->get_view_url(),
                )
            );
        }
        if ((boolean) config::get('recordingready_enabled')) {
            $metadata['bn-recording-ready-url'] = $this->instance->get_record_ready_url()->out(false);
        }
        if ((boolean) config::get('meetingevents_enabled')) {
            $metadata['analytics-callback-url'] = $this->instance->get_meeting_event_notification_url()->out(false);
        }
        return $metadata;
    }
}
