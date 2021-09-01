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
 * The bigbluebutton proxy class.
 *
 * This class acts as a proxy between Moodle and the BigBlueButton API server,
 * and handles all requests relating to the server and meetings.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\local\proxy;

use cache_store;
use Exception;
use cache;
use completion_info;
use mod_bigbluebuttonbn\completion\custom_completion;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\local\exceptions\bigbluebutton_exception;
use mod_bigbluebuttonbn\local\exceptions\server_not_available_exception;
use mod_bigbluebuttonbn\meeting;
use mod_bigbluebuttonbn\plugin;
use mod_bigbluebuttonbn\variable;
use moodle_exception;
use moodle_url;
use stdClass;

class bigbluebutton_proxy extends proxy_base {

    /**
     * Builds and retunrs a url for joining a bigbluebutton meeting.
     *
     * @param string $meetingid
     * @param string $username
     * @param string $pw
     * @param string $logouturl
     * @param string $configtoken
     * @param string $userid
     * @param string $createtime
     *
     * @return string
     */
    public static function get_join_url(
        string $meetingid,
        string $username,
        string $pw,
        string $logouturl,
        string $configtoken = null,
        string $userid = null,
        string $createtime = null
    ): ?string {
        $data = [
            'meetingID' => $meetingid,
            'fullName' => $username,
            'password' => $pw,
            'logoutURL' => $logouturl,
        ];

        if (!is_null($configtoken)) {
            $data['configToken'] = $configtoken;
        }

        if (!is_null($userid)) {
            $data['userID'] = $userid;
        }

        if (!is_null($createtime)) {
            $data['createTime'] = $createtime;
        }

        return self::action_url('join', $data);
    }

    /**
     * Perform api request on BBB.
     *
     * @return null|string
     */
    public static function get_server_version(): ?string {
        $cache = cache::make('mod_bigbluebuttonbn', 'serverinfo');
        $serverversion = $cache->get('serverversion');

        if (!$serverversion) {
            $xml = self::fetch_endpoint_xml('');
            if (!$xml || $xml->returncode != 'SUCCESS') {
                return null;
            }

            if (!isset($xml->version)) {
                return null;
            }

            $serverversion = (string) $xml->version;
            $cache->set('serverversion', $serverversion);
        }

        return (double) $serverversion;
    }

    /**
     * Helper for getting the owner userid of a bigbluebuttonbn instance.
     *
     * @param stdClass $bigbluebuttonbn BigBlueButtonBN instance
     * @return integer ownerid (a valid user id or null if not registered/found)
     */
    public static function get_instance_ownerid($bigbluebuttonbn) {
        global $DB;

        $filters = [
            'bigbluebuttonbnid' => $bigbluebuttonbn->id,
            'log' => 'Add',
        ];

        return (integer) $DB->get_field('bigbluebuttonbn_logs', 'userid', $filters);
    }

    /**
     * Helper evaluates if a voicebridge number is unique.
     *
     * @param int $instance
     * @param int $voicebridge
     * @return string
     */
    public static function is_voicebridge_number_unique(int $instance, int $voicebridge): bool {
        global $DB;
        if ($voicebridge == 0) {
            return true;
        }
        $select = 'voicebridge = ' . $voicebridge;
        if ($instance != 0) {
            $select .= ' AND id <>' . $instance;
        }
        if (!$DB->get_records_select('bigbluebuttonbn', $select)) {
            return true;
        }
        return false;
    }

    /**
     * Helper function validates a remote resource.
     *
     * @param string $url
     * @return boolean
     */
    public static function is_remote_resource_valid(string $url): bool {
        $urlhost = parse_url($url, PHP_URL_HOST);
        $serverurlhost = parse_url(\mod_bigbluebuttonbn\local\config::get('server_url'), PHP_URL_HOST);
        // Skip validation when the recording URL host is the same as the configured BBB server.
        if ($urlhost == $serverurlhost) {
            return true;
        }
        // Skip validation when the recording URL was already validated.
        $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'recordings_cache');
        $result = $cache->get('validated_urls');
        $validatedurls = $result ?? array();
        if (array_key_exists($urlhost, $validatedurls)) {
            return $validatedurls[$urlhost];
        }
        // Validate the recording URL.
        $validatedurls[$urlhost] = true;
        $curlinfo = self::wrap_xml_load_file_curl_request($url, 'HEAD');
        if (!isset($curlinfo['http_code']) || $curlinfo['http_code'] != 200) {
            $error = "Resources hosted by " . $urlhost . " are unreachable. Server responded with code " . $curlinfo['http_code'];
            debugging($error, DEBUG_DEVELOPER);
            $validatedurls[$urlhost] = false;
        }
        $cache->set('validated_urls', $validatedurls);
        return $validatedurls[$urlhost];
    }

    /**
     * Helper function enqueues one user for being validated as for completion.
     *
     * @param object $bigbluebuttonbn
     * @param string $userid
     * @return void
     */
    public static function enqueue_completion_event($bigbluebuttonbn, $userid): void {
        try {
            // Create the instance of completion_update_state task.
            $task = new \mod_bigbluebuttonbn\task\completion_update_state();
            // Add custom data.
            $data = array(
                'bigbluebuttonbn' => $bigbluebuttonbn,
                'userid' => $userid,
            );
            $task->set_custom_data($data);
            // CONTRIB-7457: Task should be executed by a user, maybe Teacher as Student won't have rights for overriding.
            // $ task -> set_userid ( $ user -> id );.
            // Enqueue it.
            \core\task\manager::queue_adhoc_task($task);
        } catch (Exception $e) {
            mtrace("Error while enqueuing completion_update_state task. " . (string) $e);
        }
    }

    /**
     * Helper function enqueues completion trigger.
     *
     * @param object $bigbluebuttonbn
     * @param string $userid
     * @return void
     */
    public static function update_completion_state($bigbluebuttonbn, $userid) {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');
        list($course, $cm) = get_course_and_cm_from_instance($bigbluebuttonbn, 'bigbluebuttonbn');
        $completion = new completion_info($course);
        if (!$completion->is_enabled($cm)) {
            mtrace("Completion not enabled");
            return;
        }

        $bbbcompletion = new custom_completion($cm, $userid);
        if ($bbbcompletion->get_overall_completion_state()) {
            mtrace("Completion succeeded for user $userid");
            $completion->update_state($cm, COMPLETION_COMPLETE, $userid, true);
        } else {
            mtrace("Completion did not succeed for user $userid");
        }
    }

    /**
     * Helper function returns an array with the profiles (with features per profile) for the different types
     * of bigbluebuttonbn instances.
     *
     * @return array
     */
    public static function get_instance_type_profiles() {
        $instanceprofiles = array(
            instance::TYPE_ALL => array('id' => instance::TYPE_ALL,
                'name' => get_string('instance_type_default', 'bigbluebuttonbn'),
                'features' => array('all')),
            instance::TYPE_ROOM_ONLY => array('id' => instance::TYPE_ROOM_ONLY,
                'name' => get_string('instance_type_room_only', 'bigbluebuttonbn'),
                'features' => array('showroom', 'welcomemessage', 'voicebridge', 'waitformoderator', 'userlimit',
                    'recording', 'sendnotifications', 'preuploadpresentation', 'permissions', 'schedule', 'groups',
                    'modstandardelshdr', 'availabilityconditionsheader', 'tagshdr', 'competenciessection',
                    'completionattendance', 'completionengagement', 'availabilityconditionsheader')),
            instance::TYPE_RECORDING_ONLY => array('id' => instance::TYPE_RECORDING_ONLY,
                'name' => get_string('instance_type_recording_only', 'bigbluebuttonbn'),
                'features' => array('showrecordings', 'importrecordings', 'availabilityconditionsheader')),
        );
        return $instanceprofiles;
    }

    /**
     * Helper function returns an array with the profiles (with features per profile) for the different types
     * of bigbluebuttonbn instances that the user is allowed to create.
     *
     * @param boolean $room
     * @param boolean $recording
     *
     * @return array
     */
    public static function get_instance_type_profiles_create_allowed($room, $recording) {
        $profiles = self::get_instance_type_profiles();
        if (!$room) {
            unset($profiles[instance::TYPE_ROOM_ONLY]);
            unset($profiles[instance::TYPE_ALL]);
        }
        if (!$recording) {
            unset($profiles[instance::TYPE_RECORDING_ONLY]);
            unset($profiles[instance::TYPE_ALL]);
        }
        return $profiles;
    }

    /**
     * Helper function returns an array with the profiles (with features per profile) for the different types
     * of bigbluebuttonbn instances.
     *
     * @param array $profiles
     *
     * @return array
     */
    public static function get_instance_profiles_array($profiles = []) {
        $profilesarray = array();
        foreach ($profiles as $key => $profile) {
            $profilesarray[$profile['id']] = $profile['name'];
        }
        return $profilesarray;
    }

    /**
     * Return the status of an activity [open|not_started|ended].
     *
     * @param instance $instance
     * @return string
     */
    public static function view_get_activity_status($instance) {
        $now = time();
        if (!empty($instance->get_instance_var('openingtime')) && $now < $instance->get_instance_var('openingtime')) {
            // The activity has not been opened.
            return 'not_started';
        }
        if (!empty($instance->get_instance_var('closingtime')) && $now > $instance->get_instance_var('closingtime')) {
            // The activity has been closed.
            return 'ended';
        }
        // The activity is open.
        return 'open';
    }

    /**
     * Ensure that the remote server was contactable.
     *
     * @param instance $instance
     */
    public static function require_working_server(instance $instance): void {
        try {
            self::get_server_version();
        } catch (server_not_available_exception $e) {
            self::handle_server_not_available($instance);
        }
    }

    /**
     * Handle the server not being available.
     *
     * @param instance $instance
     */
    public static function handle_server_not_available(instance $instance): void {
        \core\notification::add(
            self::get_server_not_available_message($instance),
            \core\notification::ERROR
        );
        redirect(self::get_server_not_available_url($instance));
    }

    /**
     * Get message when server not available
     *
     * @param instance $instance
     * @return string
     */
    public static function get_server_not_available_message(instance $instance): string {
        if ($instance->is_admin()) {
            return get_string('view_error_unable_join', 'mod_bigbluebuttonbn');
        } else if ($instance->is_moderator()) {
            return get_string('view_error_unable_join_teacher', 'mod_bigbluebuttonbn');
        } else {
            return get_string('view_error_unable_join_student', 'mod_bigbluebuttonbn');
        }
    }

    /**
     * Get URL to the page displaying that the server is not available
     *
     * @param instance $instance
     * @return string
     */
    public static function get_server_not_available_url(instance $instance): string {
        if ($instance->is_admin()) {
            return new moodle_url('/admin/settings.php', ['section' => 'modsettingbigbluebuttonbn']);
        } else if ($instance->is_moderator()) {
            return new moodle_url('/course/view.php', ['id' => $instance->get_course_id()]);
        } else {
            return new moodle_url('/course/view.php', ['id' => $instance->get_course_id()]);
        }
    }

    /**
     * Create a Meeting
     *
     * @param array $data
     * @param array $metadata
     * @param null $presentationname
     * @param null $presentationurl
     * @return array
     * @throws bigbluebutton_exception
     * @throws server_not_available_exception
     */
    public static function create_meeting(
        array $data,
        array $metadata,
        ?string $presentationname = null,
        ?string $presentationurl = null
    ): array {
        $createmeetingurl = self::action_url('create', $data, $metadata);
        $method = 'GET';
        $payload = null;
        if (!is_null($presentationname) && !is_null($presentationurl)) {
            $method = 'POST';
            $payload = "<?xml version='1.0' encoding='UTF-8'?><modules><module name='presentation'><document url='" .
                $presentationurl . "' /></module></modules>";
        }

        $xml = self::wrap_xml_load_file($createmeetingurl, $method, $payload);
        self::assert_returned_xml($xml);

        if (empty($xml->meetingID)) {
            throw new bigbluebutton_exception('general_error_cannot_create_meeting', plugin::COMPONENT);
        }

        if ($xml->hasBeenForciblyEnded === 'true') {
            throw new bigbluebutton_exception('index_error_forciblyended', plugin::COMPONENT);
        }

        return [
            'meetingID' => (string) $xml->meetingID,
            'internalMeetingID' => (string) $xml->internalMeetingID,
            'attendeePW' => (string) $xml->attendeePW,
            'moderatorPW' => (string) $xml->moderatorPW
        ];
    }

    /**
     * Get meeting info for a given meeting id
     *
     * @param string $meetingid
     * @return array
     */
    public static function get_meeting_info(string $meetingid): array {
        $xmlinfo = self::fetch_endpoint_xml('getMeetingInfo', ['meetingID' => $meetingid]);
        self::assert_returned_xml($xmlinfo, $meetingid);
        return (array) $xmlinfo;
    }

    /**
     * Perform end meeting on BBB.
     *
     * @param string $meetingid
     * @param string $modpw
     */
    public static function end_meeting(string $meetingid, string $modpw): void {
        $xml = self::fetch_endpoint_xml('end', ['meetingID' => $meetingid, 'password' => $modpw]);
        self::assert_returned_xml($xml, $meetingid);
    }

    /**
     * Helper evaluates if the bigbluebutton server used belongs to blindsidenetworks domain.
     *
     * @return boolean
     */
    public static function is_bn_server() {
        if (config::get('bn_server')) {
            return true;
        }
        $parsedurl = parse_url(config::get('server_url'));
        if (!isset($parsedurl['host'])) {
            return false;
        }
        $h = $parsedurl['host'];
        $hends = explode('.', $h);
        $hendslength = count($hends);
        return ($hends[$hendslength - 1] == 'com' && $hends[$hendslength - 2] == 'blindsidenetworks');
    }
}
