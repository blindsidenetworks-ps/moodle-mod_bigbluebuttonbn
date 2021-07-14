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
 * The mod_bigbluebuttonbn local/bigbluebutton.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\local;

use cache;
use completion_info;
use curl;
use Exception;
use mod_bigbluebuttonbn\completion\custom_completion;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\exceptions\bigbluebutton_exception;
use mod_bigbluebuttonbn\local\exceptions\server_not_available_exception;
use mod_bigbluebuttonbn\local\helpers\meeting_helper;
use mod_bigbluebuttonbn\meeting;
use mod_bigbluebuttonbn\plugin;
use moodle_exception;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Wrapper for executing http requests on a BigBlueButton server.
 *
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bigbluebutton {

    /**
     * Returns the right URL for the action specified.
     *
     * @param string $action
     * @param array $data
     * @param array $metadata
     * @return string
     */
    public static function action_url($action = '', $data = array(), $metadata = array()) {
        $baseurl = self::sanitized_url() . $action . '?';
        $metadata = array_combine(
            array_map(
                function($k) {
                    return 'meta_' . $k;
                }
                , array_keys($metadata)
            ),
            $metadata
        );
        $params = http_build_query($data + $metadata, '', '&');
        return $baseurl . $params . '&checksum=' . sha1($action . $params . self::sanitized_secret());
    }

    /**
     * Makes sure the url used doesn't is in the format required.
     *
     * @return string
     */
    public static function sanitized_url() {
        $serverurl = trim(config::get('server_url'));
        if (defined('BEHAT_SITE_RUNNING')) {
            // TODO Make this a setting.
            $serverurl = (new moodle_url('/mod/bigbluebuttonbn/tests/fixtures/mockedserver.php'))->out(false);
        }
        if (substr($serverurl, -1) == '/') {
            $serverurl = rtrim($serverurl, '/');
        }
        if (substr($serverurl, -4) == '/api') {
            $serverurl = rtrim($serverurl, '/api');
        }
        return $serverurl . '/api/';
    }

    /**
     * Makes sure the shared_secret used doesn't have trailing white characters.
     *
     * @return string
     */
    public static function sanitized_secret() {
        return trim(config::get('shared_secret'));
    }

    /**
     * Returns the BigBlueButton server root URL.
     *
     * @return string
     */
    public static function root() {
        $pserverurl = parse_url(trim(config::get('server_url')));
        $pserverurlport = "";
        if (isset($pserverurl['port'])) {
            $pserverurlport = ":" . $pserverurl['port'];
        }
        return $pserverurl['scheme'] . "://" . $pserverurl['host'] . $pserverurlport . "/";
    }

    /**
     * Can join meeting.
     *
     * @param int $cmid
     * @return array|bool[]
     */
    public static function can_join_meeting($cmid) {
        $canjoin = array('can_join' => false, 'message' => '');

        $viewinstance = view::view_validator($cmid, null);
        if ($viewinstance) {
            $instance = instance::get_from_cmid($cmid);
            $info = meeting::get_meeting_info_for_instance($instance);
            $canjoin = $info->canjoin;
        }
        return $canjoin;
    }

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
    public static function bigbluebuttonbn_get_join_url(
        $meetingid,
        $username,
        $pw,
        $logouturl,
        $configtoken = null,
        $userid = null,
        $createtime = null
    ) {
        $data = ['meetingID' => $meetingid,
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
        return static::action_url('join', $data);
    }

    /**
     * Perform api request on BBB.
     *
     * @return string
     */
    public static function bigbluebuttonbn_get_server_version() {
        $cache = cache::make('mod_bigbluebuttonbn', 'serverinfo');
        $serverversion = $cache->get('serverversion');
        if (!$serverversion) {
            $xml = self::bigbluebuttonbn_wrap_xml_load_file(
                self::action_url()
            );
            if ($xml && $xml->returncode == 'SUCCESS') {
                $cache->set('serverversion', (string) $xml->version);
                return (double) $xml->version;
            }
        } else {
            return (double) $serverversion;
        }
        return null;
    }

    /**
     * Perform api request on BBB and wraps the response in an XML object
     *
     * @param string $url
     * @param string $method
     * @param string $data
     * @param string $contenttype
     *
     * @return object
     */
    public static function bigbluebuttonbn_wrap_xml_load_file($url, $method = 'GET', $data = null, $contenttype = 'text/xml') {
        if (extension_loaded('curl')) {
            $response =
                self::bigbluebuttonbn_wrap_xml_load_file_curl_request($url, $method, $data, $contenttype);
            if (!$response) {
                debugging('No response on wrap_simplexml_load_file', DEBUG_DEVELOPER);
                return null;
            }
            $previous = libxml_use_internal_errors(true);
            try {
                $xml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
                return $xml;
            } catch (Exception $e) {
                libxml_use_internal_errors($previous);
                $error = 'Caught exception: ' . $e->getMessage();
                debugging($error, DEBUG_DEVELOPER);
                return null;
            }
        }
        // Alternative request non CURL based.
        $previous = libxml_use_internal_errors(true);
        try {
            $response = simplexml_load_file($url, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
            return $response;
        } catch (Exception $e) {
            $error = 'Caught exception: ' . $e->getMessage();
            debugging($error, DEBUG_DEVELOPER);
            libxml_use_internal_errors($previous);
            return null;
        }
    }

    /**
     * Perform api request on BBB using CURL and wraps the response in an XML object
     *
     * @param string $url
     * @param string $method
     * @param string $data
     * @param string $contenttype
     *
     * @return object|bool|string
     */
    public static function bigbluebuttonbn_wrap_xml_load_file_curl_request($url, $method = 'GET', $data = null,
        $contenttype = 'text/xml') {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $c = new curl();
        $c->setopt(array('SSL_VERIFYPEER' => true));
        if ($method == 'POST') {
            if (is_null($data) || is_array($data)) {
                return $c->post($url);
            }
            $options = array();
            $options['CURLOPT_HTTPHEADER'] = array(
                'Content-Type: ' . $contenttype,
                'Content-Length: ' . strlen($data),
                'Content-Language: en-US',
            );

            return $c->post($url, $data, $options);
        }
        if ($method == 'HEAD') {
            $c->head($url, array('followlocation' => true, 'timeout' => 1));
            return $c->get_info();
        }
        return $c->get($url);
    }

    /**
     * Helper for getting the owner userid of a bigbluebuttonbn instance.
     *
     * @param stdClass $bigbluebuttonbn BigBlueButtonBN instance
     *
     * @return integer ownerid (a valid user id or null if not registered/found)
     */
    public static function bigbluebuttonbn_instance_ownerid($bigbluebuttonbn) {
        global $DB;
        $filters = array('bigbluebuttonbnid' => $bigbluebuttonbn->id, 'log' => 'Add');
        $ownerid = (integer) $DB->get_field('bigbluebuttonbn_logs', 'userid', $filters);
        return $ownerid;
    }

    /**
     * Helper evaluates if a voicebridge number is unique.
     *
     * @param integer $instance
     * @param integer $voicebridge
     *
     * @return string
     */
    public static function bigbluebuttonbn_voicebridge_unique($instance, $voicebridge) {
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
     *
     * @return boolean
     */
    public static function bigbluebuttonbn_is_valid_resource($url) {
        $urlhost = parse_url($url, PHP_URL_HOST);
        $serverurlhost = parse_url(\mod_bigbluebuttonbn\local\config::get('server_url'), PHP_URL_HOST);
        // Skip validation when the recording URL host is the same as the configured BBB server.
        if ($urlhost == $serverurlhost) {
            return true;
        }
        // Skip validation when the recording URL was already validated.
        $validatedurls = plugin::bigbluebuttonbn_cache_get('recordings_cache', 'validated_urls', array());
        if (array_key_exists($urlhost, $validatedurls)) {
            return $validatedurls[$urlhost];
        }
        // Validate the recording URL.
        $validatedurls[$urlhost] = true;
        $curlinfo = self::bigbluebuttonbn_wrap_xml_load_file_curl_request($url, 'HEAD');
        if (!isset($curlinfo['http_code']) || $curlinfo['http_code'] != 200) {
            $error = "Resources hosted by " . $urlhost . " are unreachable. Server responded with code " . $curlinfo['http_code'];
            debugging($error, DEBUG_DEVELOPER);
            $validatedurls[$urlhost] = false;
        }
        plugin::bigbluebuttonbn_cache_set('recordings_cache', 'validated_urls', $validatedurls);
        return $validatedurls[$urlhost];
    }

    /**
     * Helper function enqueues one user for being validated as for completion.
     *
     * @param object $bigbluebuttonbn
     * @param string $userid
     *
     * @return void
     */
    public static function bigbluebuttonbn_enqueue_completion_update($bigbluebuttonbn, $userid) {
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
     *
     * @return void
     */
    public static function bigbluebuttonbn_completion_update_state($bigbluebuttonbn, $userid) {
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
    public static function bigbluebuttonbn_get_instance_type_profiles() {
        $instanceprofiles = array(
            bbb_constants::BIGBLUEBUTTONBN_TYPE_ALL => array('id' => bbb_constants::BIGBLUEBUTTONBN_TYPE_ALL,
                'name' => get_string('instance_type_default', 'bigbluebuttonbn'),
                'features' => array('all')),
            bbb_constants::BIGBLUEBUTTONBN_TYPE_ROOM_ONLY => array('id' => bbb_constants::BIGBLUEBUTTONBN_TYPE_ROOM_ONLY,
                'name' => get_string('instance_type_room_only', 'bigbluebuttonbn'),
                'features' => array('showroom', 'welcomemessage', 'voicebridge', 'waitformoderator', 'userlimit',
                    'recording', 'sendnotifications', 'preuploadpresentation', 'permissions', 'schedule', 'groups',
                    'modstandardelshdr', 'availabilityconditionsheader', 'tagshdr', 'competenciessection',
                    'completionattendance', 'completionengagement', 'availabilityconditionsheader')),
            bbb_constants::BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY => array('id' => bbb_constants::BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY,
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
    public static function bigbluebuttonbn_get_instance_type_profiles_create_allowed($room, $recording) {
        $profiles = self::bigbluebuttonbn_get_instance_type_profiles();
        if (!$room) {
            unset($profiles[bbb_constants::BIGBLUEBUTTONBN_TYPE_ROOM_ONLY]);
            unset($profiles[bbb_constants::BIGBLUEBUTTONBN_TYPE_ALL]);
        }
        if (!$recording) {
            unset($profiles[bbb_constants::BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY]);
            unset($profiles[bbb_constants::BIGBLUEBUTTONBN_TYPE_ALL]);
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
    public static function bigbluebuttonbn_get_instance_profiles_array($profiles = []) {
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
    public static function bigbluebuttonbn_view_get_activity_status($instance) {
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
            self::bigbluebuttonbn_get_server_version();
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
    public static function create_meeting(array $data, array $metadata, $presentationname = null, $presentationurl = null) {
        $createmeetingurl = self::action_url('create', $data, $metadata);
        $method = 'GET';
        $payload = null;
        if (!is_null($presentationname) && !is_null($presentationurl)) {
            $method = 'POST';
            $payload = "<?xml version='1.0' encoding='UTF-8'?><modules><module name='presentation'><document url='" .
                $presentationurl . "' /></module></modules>";
        }

        $xml = self::bigbluebuttonbn_wrap_xml_load_file($createmeetingurl, $method, $payload);
        self::assert_returned_xml($xml);
        if (empty($xml->meetingID)) {
            throw new bigbluebutton_exception('general_error_cannot_create_meeting', plugin::COMPONENT);
        }
        if ($xml->hasBeenForciblyEnded === 'true') {
            throw new bigbluebutton_exception('index_error_forciblyended', plugin::COMPONENT);
        }
        return array(
                'meetingID' => (string) $xml->meetingID,
                'internalMeetingID' => (string) $xml->internalMeetingID,
                'attendeePW' => (string) $xml->attendeePW,
                'moderatorPW' => (string) $xml->moderatorPW
            );
    }

    /**
     * Get meeting info for a given meeting id
     *
     * @param string $meetingid
     * @return array
     * @throws bigbluebutton_exception
     */
    public static function get_meeting_info(string $meetingid) {
        $xmlinfo = self::bigbluebuttonbn_wrap_xml_load_file(
            self::action_url('getMeetingInfo', ['meetingID' => $meetingid])
        );
        self::assert_returned_xml($xmlinfo, $meetingid);
        return (array) $xmlinfo;
    }

    /**
     * Perform end meeting on BBB.
     *
     * @param string $meetingid
     * @param string $modpw
     * @throws bigbluebutton_exception
     */
    public static function end_meeting($meetingid, $modpw) {
        $xml = self::bigbluebuttonbn_wrap_xml_load_file(
            self::action_url('end', ['meetingID' => $meetingid, 'password' => $modpw])
        );
        self::assert_returned_xml($xml, $meetingid);
    }

    /**
     * Get recordings from BBB.
     *
     * @param array $meetingsids
     * @throws bigbluebutton_exception
     */
    public static function get_recordings_from_meetings($meetingsids) {
        $url = self::action_url('getRecordings', ['meetingID' => implode(',', $meetingsids)]);
        $xml = self::bigbluebuttonbn_wrap_xml_load_file($url);
        self::assert_returned_xml($xml, join(',', $meetingsids));
        if (!isset($xml->recordings)) {
            throw new bigbluebutton_exception('general_error_cannot_get_recordings',
                plugin::COMPONENT, '', null, var_dump($meetingsids));
        }
        return iterator_to_array($xml->recordings->children(), false);
    }

    /**
     * Get recordings from BBB.
     *
     * @param array $recordingsids
     * @throws bigbluebutton_exception
     */
    public static function get_recordings($recordingsids) {
        $url = self::action_url('getRecordings', ['recordID' => implode(',', $recordingsids)]);
        $xml = self::bigbluebuttonbn_wrap_xml_load_file($url);
        self::assert_returned_xml($xml, join(',', $recordingsids));
        if (!isset($xml->recordings)) {
            throw new bigbluebutton_exception('general_error_cannot_get_recordings',
                plugin::COMPONENT, '', null, var_dump($recordingsids));
        }
        return iterator_to_array($xml->recordings->children(), false);
    }

    /**
     * Publish recording.
     *
     * @param int $recordingid
     * @param bool $publish
     * @throws moodle_exception
     */
    public static function publish_recording($recordingid, $publish) {
        $xml = self::bigbluebuttonbn_wrap_xml_load_file(
            self::action_url('publishRecordings',
                ['recordID' => $recordingid, 'publish' => $publish])
        );
        self::assert_returned_xml($xml);
    }

    /**
     * Delete recording
     *
     * @param int $recordingid
     * @throws bigbluebutton_exception
     */
    public static function delete_recording($recordingid) {
        $xml = self::bigbluebuttonbn_wrap_xml_load_file(
            self::action_url('deleteRecordings',
                ['recordID' => $recordingid])
        );
        self::assert_returned_xml($xml);
    }

    /**
     * Sometimes the server sends back some error and errorKeys that
     * can be converted to Moodle error messages
     */
    const MEETING_ERROR = [
        'checksumError' => 'index_error_checksum',
        'notFound' => 'general_error_not_found',
        'maxConcurrent' => 'view_error_max_concurrent',
    ];

    /**
     * Throw an exception if there is a problem in the returned XML value
     *
     * @param \SimpleXMLElement $xml
     * @param string $additionaldetails
     * @throws bigbluebutton_exception
     * @throws server_not_available_exception
     */
    protected static function assert_returned_xml($xml, $additionaldetails = '') {
        if (empty($xml)) {
            global $CFG;
            throw new server_not_available_exception('general_error_no_answer', plugin::COMPONENT,
                $CFG->wwwroot . '/admin/settings.php?section=modsettingbigbluebuttonbn', );
        }
        if ((string) $xml->returncode === 'FAILED') {
            $messagekey = (string) $xml->messageKey ?? '';
            $messagedetails = (string) $xml->message ?? '';
            $messagedetails .= $additionaldetails ? " ($additionaldetails) " : '';
            throw new bigbluebutton_exception(
                (empty($messagekey) || empty(self::MEETING_ERROR[$messagekey])) ?
                    'general_error_unable_connect' : $messagekey,
                plugin::COMPONENT,
                '',
                $messagedetails);
        }
    }

}
