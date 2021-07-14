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
use mod_bigbluebuttonbn\local\helpers\meeting_helper as meeting_helper;
use moodle_exception;
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

    /**
     * Constructor for the meeting object.
     *
     * @param instance $instance
     */
    public function __construct(instance $instance) {
        $this->instance = $instance;
    }

    /**
     * Force an update of the meeting cache for this instance.
     *
     * @param instance $instance
     */
    public static function update_meeting_cache_for_instance(instance $instance): void {
        $meeting = new self($instance);
        $meeting->update_cache();
    }

    /**
     * Return meeting information for this meeting.
     *
     * @param bool $updatecache Whether to update the cache when fetching the information
     * @return stdClass
     */
    public function get_meeting_info(bool $updatecache = false): stdClass {
        $instance = $this->instance;
        $meeting = new meeting($instance);
        if ($updatecache) {
            $meeting->update_cache();
        }
        // This might raise an exception if info cannot be retrieved.
        $info = self::retrieve_meeting_info($this->instance->get_meeting_id());
        $isrunning = $info['returncode'] === 'SUCCESS' && $info['running'] === 'true';
        $activitystatus = bigbluebutton::bigbluebuttonbn_view_get_activity_status($instance);

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
        $status = meeting_helper::meeting_info_can_join(
            $instance,
            $isrunning,
            $info['participantCount'] ?? 0
        );
        $meetinginfo->canjoin = $status["can_join"];

        // If user is administrator, moderator or if is viewer and no waiting is required, join allowed.
        if ($isrunning) {
            $meetinginfo->statusmessage = get_string('view_message_conference_in_progress', 'bigbluebuttonbn');
            $meetinginfo->startedat = floor(intval($info['startTime']) / 1000); // Milliseconds.
            $meetinginfo->moderatorcount = $info['moderatorCount'];
            $meetinginfo->moderatorplural = $info['moderatorCount'] > 1;
            $meetinginfo->participantcount = $info['participantCount']?? 0;
            $meetinginfo->participantplural = $meetinginfo->participantcount > 1;
        } else {
            if ($instance->user_must_wait_to_join()) {
                $meetinginfo->statusmessage = get_string('view_message_conference_wait_for_moderator', 'bigbluebuttonbn');
            } else {
                $meetinginfo->statusmessage = get_string('view_message_conference_room_ready', 'bigbluebuttonbn');
            }
        }

        $presentation = $instance->get_presentation();
        if (!empty($presentation)) {
            $meetinginfo->presentations[] = $presentation;
        }
        $meetinginfo->attendees = $info['attendees'] ?? [];

        return $meetinginfo;
    }

    /**
     * Return meeting information for the specified instance.
     *
     * @param instance $instance
     * @param bool $updatecache Whether to update the cache when fetching the information
     * @return stdClass
     */
    public static function get_meeting_info_for_instance(instance $instance, bool $updatecache=false): stdClass {
        $meeting = new self($instance);
        if ($updatecache) {
            $meeting->update_cache();
        }
        return $meeting->get_meeting_info();
    }


    /**
     * Gets a meeting info object cached or fetched from the live session.
     *
     * @param string $meetingid
     * @param boolean $updatecache
     *
     * @return array
     */
    protected static function retrieve_meeting_info($meetingid, $updatecache = false) {
        $cachettl = (int) config::get('waitformoderator_cache_ttl');
        $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'meetings_cache');
        $result = $cache->get($meetingid);
        $now = time();
        if (!$updatecache && !empty($result) && $now < ($result['creation_time'] + $cachettl)) {
            // Use the value in the cache.
            return (array) json_decode($result['meeting_info']);
        }
        // Ping again and refresh the cache.
        $meetinginfo = bigbluebutton::get_meeting_info($meetingid);

        $cache->set($meetingid, array('creation_time' => time(), 'meeting_info' => json_encode($meetinginfo)));
        return $meetinginfo;
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
        $meetinginfo = self::retrieve_meeting_info($this->instance->get_meeting_id(), true);
        return ($meetinginfo['returncode'] === 'SUCCESS');
    }

    /**
     * Force update the meeting in cache.
     */
    public function update_cache() {
        self::retrieve_meeting_info($this->instance->get_meeting_id(), true);
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
     * @throws moodle_exception
     */
    public function create_meeting() {
        $data = $this->create_meeting_data();
        $metadata = $this->create_meeting_metadata();
        $presentationname = $this->instance->get_presentation()['name'] ?? null;
        $presentationurl = $this->instance->get_presentation()['url'] ?? null;
        return bigbluebutton::create_meeting($data, $metadata, $presentationname, $presentationurl);
    }


    /**
     * Helper to prepare data used for create meeting.
     * @param instance $instance
     * @return array
     */
    protected function create_meeting_data() {
        $data = ['meetingID' => $this->instance->get_meeting_id(),
            'name' => \mod_bigbluebuttonbn\plugin::bigbluebuttonbn_html2text($this->instance->get_meeting_name(), 64),
            'attendeePW' => $this->instance->get_viewer_password(),
            'moderatorPW' => $this->instance->get_moderator_password(),
            'logoutURL' => $this->instance->get_logout_url(),
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
     * @param  instance    $instance
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
            'bbb-recording-tags' => \mod_bigbluebuttonbn\plugin::bigbluebuttonbn_get_tags($this->instance->get_cm_id()), // Same as $id.
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

    /**
     * Send an end meeting message to BBB server
     */
    public function end_meeting() {
        bigbluebutton::end_meeting($this->instance->get_meeting_id(), $this->instance->get_moderator_password());
    }

    /**
     * Get meeting attendees
     *
     * @return mixed
     */
    public function get_attendees() {
        $info = $this->get_meeting_info();
        return $info->attendees;
    }
}
