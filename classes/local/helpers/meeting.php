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
use context_course;
use mod_bigbluebuttonbn\local\bbb_constants;
use mod_bigbluebuttonbn\local\bigbluebutton;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\plugin;
use stdClass;
use mod_bigbluebuttonbn\local\helpers\opencast;

defined('MOODLE_INTERNAL') || die();

/**
 * Utility class for meetings helper
 *
 * @package mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class meeting {

    /**
     * Creates a bigbluebutton meeting and returns the response in an array.
     *
     * @param array  $data
     * @param array  $metadata
     * @param string $pname
     * @param string $purl
     *
     * @return array
     */
    public static function bigbluebuttonbn_get_create_meeting_array($data, $metadata = array(), $pname = null, $purl = null) {
        $createmeetingurl = bigbluebutton::action_url('create', $data, $metadata);
        $method = 'GET';
        $payload = null;
        if (!is_null($pname) && !is_null($purl)) {
            $method = 'POST';
            $payload = "<?xml version='1.0' encoding='UTF-8'?><modules><module name='presentation'><document url='" .
                $purl . "' /></module></modules>";
        }
        $xml = bigbluebutton::bigbluebuttonbn_wrap_xml_load_file($createmeetingurl, $method, $payload);
        if ($xml) {
            $response = array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
            if ($xml->meetingID) {
                $response += array('meetingID' => $xml->meetingID, 'attendeePW' => $xml->attendeePW,
                    'moderatorPW' => $xml->moderatorPW, 'hasBeenForciblyEnded' => $xml->hasBeenForciblyEnded);
            }
            return $response;
        }
        return array('returncode' => 'FAILED', 'message' => 'unreachable', 'messageKey' => 'Server is unreachable');
    }

    /**
     * Fetch meeting info and wrap response in array.
     *
     * @param string $meetingid
     *
     * @return array
     */
    public static function bigbluebuttonbn_get_meeting_info_array($meetingid) {
        $xml = bigbluebutton::bigbluebuttonbn_wrap_xml_load_file(
            bigbluebutton::action_url('getMeetingInfo', ['meetingID' => $meetingid])
        );
        if ($xml && $xml->returncode == 'SUCCESS' && empty($xml->messageKey)) {
            // Meeting info was returned.
            return array('returncode' => $xml->returncode,
                'meetingID' => $xml->meetingID,
                'moderatorPW' => $xml->moderatorPW,
                'attendeePW' => $xml->attendeePW,
                'hasBeenForciblyEnded' => $xml->hasBeenForciblyEnded,
                'running' => $xml->running,
                'recording' => $xml->recording,
                'startTime' => $xml->startTime,
                'endTime' => $xml->endTime,
                'participantCount' => $xml->participantCount,
                'moderatorCount' => $xml->moderatorCount,
                'attendees' => $xml->attendees,
                'metadata' => $xml->metadata,
            );
        }
        if ($xml) {
            // Either failure or success without meeting info.
            return (array) $xml;
        }
        // If the server is unreachable, then prompts the user of the necessary action.
        return array('returncode' => 'FAILED', 'message' => 'unreachable', 'messageKey' => 'Server is unreachable');
    }

    /**
     * Perform end on BBB.
     *
     * @param string $meetingid
     * @param string $modpw
     */
    public static function bigbluebuttonbn_end_meeting($meetingid, $modpw) {
        $xml = bigbluebutton::bigbluebuttonbn_wrap_xml_load_file(
            bigbluebutton::action_url('end', ['meetingID' => $meetingid, 'password' => $modpw])
        );
        if ($xml) {
            // If the xml packet returned failure it displays the message to the user.
            return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
        }
        // If the server is unreachable, then prompts the user of the necessary action.
        return null;
    }

    /**
     * Gets a meeting info object cached or fetched from the live session.
     *
     * @param string $meetingid
     * @param boolean $updatecache
     *
     * @return array
     */
    public static function bigbluebuttonbn_get_meeting_info($meetingid, $updatecache = false) {
        $cachettl = (int) config::get('waitformoderator_cache_ttl');
        $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'meetings_cache');
        $result = $cache->get($meetingid);
        $now = time();
        if (!$updatecache && !empty($result) && $now < ($result['creation_time'] + $cachettl)) {
            // Use the value in the cache.
            return (array) json_decode($result['meeting_info']);
        }
        // Ping again and refresh the cache.
        $meetinginfo = (array) bigbluebutton::bigbluebuttonbn_wrap_xml_load_file(
            bigbluebutton::action_url('getMeetingInfo', ['meetingID' => $meetingid])
        );
        $cache->set($meetingid, array('creation_time' => time(), 'meeting_info' => json_encode($meetinginfo)));
        return $meetinginfo;
    }

    /**
     * Perform isMeetingRunning on BBB.
     *
     * @param string $meetingid
     * @param boolean $updatecache
     *
     * @return boolean
     */
    public static function bigbluebuttonbn_is_meeting_running($meetingid, $updatecache = false) {
        /* As a workaround to isMeetingRunning that always return SUCCESS but only returns true
         * when at least one user is in the session, we use getMeetingInfo instead.
         */
        $meetinginfo = self::bigbluebuttonbn_get_meeting_info($meetingid, $updatecache);
        return ($meetinginfo['returncode'] === 'SUCCESS');
    }

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
     * Helper for preparing metadata used while creating the meeting.
     *
     * @param  array    $bbbsession
     * @return array
     */
    public static function bigbluebuttonbn_create_meeting_metadata(&$bbbsession) {
        global $USER;
        // Create standard metadata.
        $metadata = [
            'bbb-origin' => $bbbsession['origin'],
            'bbb-origin-version' => $bbbsession['originVersion'],
            'bbb-origin-server-name' => $bbbsession['originServerName'],
            'bbb-origin-server-common-name' => $bbbsession['originServerCommonName'],
            'bbb-origin-tag' => $bbbsession['originTag'],
            'bbb-context' => $bbbsession['course']->fullname,
            'bbb-context-id' => $bbbsession['course']->id,
            'bbb-context-name' => trim(html_to_text($bbbsession['course']->fullname, 0)),
            'bbb-context-label' => trim(html_to_text($bbbsession['course']->shortname, 0)),
            'bbb-recording-name' => plugin::bigbluebuttonbn_html2text($bbbsession['meetingname'], 64),
            'bbb-recording-description' => plugin::bigbluebuttonbn_html2text($bbbsession['meetingdescription'],
                64),
            'bbb-recording-tags' => \mod_bigbluebuttonbn\plugin::bigbluebuttonbn_get_tags($bbbsession['cm']->id), // Same as $id.
        ];
        // Special metadata for recording processing.
        if ((boolean) config::get('recordingstatus_enabled')) {
            $metadata["bn-recording-status"] = json_encode(
                array(
                    'email' => array('"' . fullname($USER) . '" <' . $USER->email . '>'),
                    'context' => $bbbsession['bigbluebuttonbnURL'],
                )
            );
        }
        if ((boolean) config::get('recordingready_enabled')) {
            $metadata['bn-recording-ready-url'] = $bbbsession['recordingReadyURL'];
        }
        if ((boolean) config::get('meetingevents_enabled')) {
            $metadata['analytics-callback-url'] = $bbbsession['meetingEventsURL'];
        }
        // If block_opencast is installed and the option to send the Opencast series ID to BBB is enabled,
        // pass the Opencast series ID of the course as opencast-dc-isPartOf within the BBB metadata.
        // Additionally, in order to identify and get the BBB recording on opencast, $bbbsession['meetingid'] as opencast-dc-subject metadata will be sent.
        if ((boolean) config::get('opencast_recording')) {
            $ocseriesid = opencast::bigbluebuttonbn_check_opencast($bbbsession['course']->id);
            if ($ocseriesid != false) {
                $metadata['opencast-dc-isPartOf'] = $ocseriesid;
                $metadata['opencast-dc-subject'] = $bbbsession['meetingid'];
            }
        }
        return $metadata;
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
        // When meeting is running, all authorized users can join right in.
        if (self::bigbluebuttonbn_is_meeting_running($mid)) {
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
     * End the session associated with this instance (if it's running).
     *
     * @param object $bigbluebuttonbn
     *
     * @return void
     */
    public static function bigbluebuttonbn_end_meeting_if_running($bigbluebuttonbn) {
        $meetingid = $bigbluebuttonbn->meetingid . '-' . $bigbluebuttonbn->course . '-' . $bigbluebuttonbn->id;
        if (self::bigbluebuttonbn_is_meeting_running($meetingid)) {
            self::bigbluebuttonbn_end_meeting($meetingid, $bigbluebuttonbn->moderatorpass);
        }
    }
}
