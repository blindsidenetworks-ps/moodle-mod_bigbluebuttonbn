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
 * The mod_bigbluebuttonbn locallib/bigbluebutton.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\local;

use completion_info;
use context_course;
use context_module;
use curl;
use Exception;
use mod_bigbluebuttonbn\completion\custom_completion;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\helpers\files;
use mod_bigbluebuttonbn\local\helpers\logs;
use mod_bigbluebuttonbn\local\helpers\meeting;
use mod_bigbluebuttonbn\local\helpers\roles;
use mod_bigbluebuttonbn\plugin;
use stdClass;

defined('MOODLE_INTERNAL') || die();
global $CFG;

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
     * @param array  $data
     * @param array  $metadata
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
     * Get BBB session information from viewinstance
     *
     * @param object $viewinstance
     * @return mixed
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \require_login_exception
     * @throws \required_capability_exception
     */
    public static function build_bbb_session_fromviewinstance($viewinstance) {
        $cm = $viewinstance['cm'];
        $course = $viewinstance['course'];
        $bigbluebuttonbn = $viewinstance['bigbluebuttonbn'];
        return self::build_bbb_session($cm, $course, $bigbluebuttonbn);
    }

    /**
     * Get BBB session from parameters
     *
     * @param \course_modinfo $cm
     * @param object $course
     * @param object $bigbluebuttonbn
     * @return mixed
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \require_login_exception
     * @throws \required_capability_exception
     */
    public static function build_bbb_session($cm, $course, $bigbluebuttonbn) {
        global $CFG;
        $context = context_module::instance($cm->id);
        require_login($course->id, false, $cm, true, true);
        require_capability('mod/bigbluebuttonbn:join', $context);

        // Add view event.
        logs::bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events['view'], $bigbluebuttonbn);

        // Create array bbbsession with configuration for BBB server.
        $bbbsession['course'] = $course;
        $bbbsession['coursename'] = $course->fullname;
        $bbbsession['cm'] = $cm;
        $bbbsession['bigbluebuttonbn'] = $bigbluebuttonbn;
        self::view_bbbsession_set($context, $bbbsession);

        $serverversion = self::bigbluebuttonbn_get_server_version();
        $bbbsession['serverversion'] = (string) $serverversion;

        // Operation URLs.
        $bbbsession['bigbluebuttonbnURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/view.php?id=' . $cm->id;
        $bbbsession['logoutURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_view.php?action=logout&id=' . $cm->id .
            '&bn=' . $bbbsession['bigbluebuttonbn']->id;
        $bbbsession['recordingReadyURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_broker.php?action=recording_' .
            'ready&bigbluebuttonbn=' . $bbbsession['bigbluebuttonbn']->id;
        $bbbsession['meetingEventsURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_broker.php?action=meeting' .
            '_events&bigbluebuttonbn=' . $bbbsession['bigbluebuttonbn']->id;
        $bbbsession['joinURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_view.php?action=join&id=' . $cm->id .
            '&bn=' . $bbbsession['bigbluebuttonbn']->id;

        return $bbbsession;
    }

    /**
     * Build standard array with configurations required for BBB server.
     *
     * @param \context $context
     * @param array $bbbsession
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function view_bbbsession_set($context, &$bbbsession) {

        global $CFG, $USER;

        $bbbsession['username'] = fullname($USER);
        $bbbsession['userID'] = $USER->id;
        $bbbsession['administrator'] = is_siteadmin($bbbsession['userID']);
        $participantlist =
            roles::bigbluebuttonbn_get_participant_list($bbbsession['bigbluebuttonbn'], $context);
        $bbbsession['moderator'] = roles::bigbluebuttonbn_is_moderator($context, $participantlist);
        $bbbsession['managerecordings'] = ($bbbsession['administrator']
            || has_capability('mod/bigbluebuttonbn:managerecordings', $context));
        $bbbsession['importrecordings'] = ($bbbsession['managerecordings']);
        $bbbsession['modPW'] = $bbbsession['bigbluebuttonbn']->moderatorpass;
        $bbbsession['viewerPW'] = $bbbsession['bigbluebuttonbn']->viewerpass;
        $bbbsession['meetingid'] = $bbbsession['bigbluebuttonbn']->meetingid.'-'.$bbbsession['course']->id.'-'.
            $bbbsession['bigbluebuttonbn']->id;
        $bbbsession['meetingname'] = $bbbsession['bigbluebuttonbn']->name;
        $bbbsession['meetingdescription'] = $bbbsession['bigbluebuttonbn']->intro;
        $bbbsession['userlimit'] = intval((int) config::get('userlimit_default'));
        if ((boolean) config::get('userlimit_editable')) {
            $bbbsession['userlimit'] = intval($bbbsession['bigbluebuttonbn']->userlimit);
        }
        $bbbsession['voicebridge'] = $bbbsession['bigbluebuttonbn']->voicebridge;
        if ($bbbsession['bigbluebuttonbn']->voicebridge > 0) {
            $bbbsession['voicebridge'] = 70000 + $bbbsession['bigbluebuttonbn']->voicebridge;
        }
        $bbbsession['wait'] = $bbbsession['bigbluebuttonbn']->wait;
        $bbbsession['record'] = $bbbsession['bigbluebuttonbn']->record;
        $bbbsession['recordallfromstart'] = $CFG->bigbluebuttonbn_recording_all_from_start_default;
        if ($CFG->bigbluebuttonbn_recording_all_from_start_editable) {
            $bbbsession['recordallfromstart'] = $bbbsession['bigbluebuttonbn']->recordallfromstart;
        }
        $bbbsession['recordhidebutton'] = $CFG->bigbluebuttonbn_recording_hide_button_default;
        if ($CFG->bigbluebuttonbn_recording_hide_button_editable) {
            $bbbsession['recordhidebutton'] = $bbbsession['bigbluebuttonbn']->recordhidebutton;
        }
        $bbbsession['welcome'] = $bbbsession['bigbluebuttonbn']->welcome;
        if (!isset($bbbsession['welcome']) || $bbbsession['welcome'] == '') {
            $bbbsession['welcome'] = get_string('mod_form_field_welcome_default', 'bigbluebuttonbn');
        }
        if ($bbbsession['bigbluebuttonbn']->record) {
            // Check if is enable record all from start.
            if ($bbbsession['recordallfromstart']) {
                $bbbsession['welcome'] .= '<br><br>'.get_string('bbbrecordallfromstartwarning',
                        'bigbluebuttonbn');
            } else {
                $bbbsession['welcome'] .= '<br><br>'.get_string('bbbrecordwarning', 'bigbluebuttonbn');
            }
        }
        $bbbsession['openingtime'] = $bbbsession['bigbluebuttonbn']->openingtime;
        $bbbsession['closingtime'] = $bbbsession['bigbluebuttonbn']->closingtime;
        $bbbsession['muteonstart'] = $bbbsession['bigbluebuttonbn']->muteonstart;
        // Lock settings.
        $bbbsession['disablecam'] = $bbbsession['bigbluebuttonbn']->disablecam;
        $bbbsession['disablemic'] = $bbbsession['bigbluebuttonbn']->disablemic;
        $bbbsession['disableprivatechat'] = $bbbsession['bigbluebuttonbn']->disableprivatechat;
        $bbbsession['disablepublicchat'] = $bbbsession['bigbluebuttonbn']->disablepublicchat;
        $bbbsession['disablenote'] = $bbbsession['bigbluebuttonbn']->disablenote;
        $bbbsession['hideuserlist'] = $bbbsession['bigbluebuttonbn']->hideuserlist;
        $bbbsession['lockedlayout'] = $bbbsession['bigbluebuttonbn']->lockedlayout;
        $bbbsession['lockonjoin'] = $bbbsession['bigbluebuttonbn']->lockonjoin;
        $bbbsession['lockonjoinconfigurable'] = $bbbsession['bigbluebuttonbn']->lockonjoinconfigurable;
        // Additional info related to the course.
        $bbbsession['context'] = $context;
        // Metadata (origin).
        $bbbsession['origin'] = 'Moodle';
        $bbbsession['originVersion'] = $CFG->release;
        $parsedurl = parse_url($CFG->wwwroot);
        $bbbsession['originServerName'] = $parsedurl['host'];
        $bbbsession['originServerUrl'] = $CFG->wwwroot;
        $bbbsession['originServerCommonName'] = '';
        $bbbsession['originTag'] = 'moodle-mod_bigbluebuttonbn ('.get_config('mod_bigbluebuttonbn', 'version').')';
        $bbbsession['bnserver'] = plugin::bigbluebuttonbn_is_bn_server();
    }

    /**
     * Can join meeting.
     *
     * @param int $cmid
     * @return array|bool[]
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \require_login_exception
     * @throws \required_capability_exception
     */
    public static function can_join_meeting($cmid) {
        global $CFG;
        $canjoin = array('can_join' => false, 'message' => '');

        $viewinstance = view::bigbluebuttonbn_view_validator($cmid, null);
        if ($viewinstance) {
            $bbbsession = self::build_bbb_session_fromviewinstance($viewinstance);
            if ($bbbsession) {
                $info = meeting::bigbluebuttonbn_get_meeting_info($bbbsession['meetingid'], false);
                $running = false;
                if ($info['returncode'] == 'SUCCESS') {
                    $running = ($info['running'] === 'true');
                }
                $participantcount = 0;
                if (isset($info['participantCount'])) {
                    $participantcount = $info['participantCount'];
                }
                $canjoin = broker::meeting_info_can_join($bbbsession, $running,
                    $participantcount);
            }
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
        $xml = self::bigbluebuttonbn_wrap_xml_load_file(
            self::action_url()
        );
        if ($xml && $xml->returncode == 'SUCCESS') {
            return $xml->version;
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
     * @return object
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
     * Helper function to retrive the default config.xml file.
     *
     * @return string
     */
    public static function bigbluebuttonbn_get_default_config_xml() {
        $xml = self::bigbluebuttonbn_wrap_xml_load_file(
            self::action_url('getDefaultConfigXML')
        );
        return $xml;
    }

    /**
     * Helper evaluates if the bigbluebutton server used belongs to blindsidenetworks domain.
     *
     * @return boolean
     */
    public static function bigbluebuttonbn_has_html5_client() {
        $checkurl = self::root() . "html5client/check";
        $curlinfo = self::bigbluebuttonbn_wrap_xml_load_file_curl_request($checkurl, 'HEAD');
        return (isset($curlinfo['http_code']) && $curlinfo['http_code'] == 200);
    }

    /**
     * Helper for getting the owner userid of a bigbluebuttonbn instance.
     *
     * @param  stdClass $bigbluebuttonbn  BigBlueButtonBN instance
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
     * Helper for evaluating if scheduled activity is avaiable.
     *
     * @param  stdClass  $bigbluebuttonbn  BigBlueButtonBN instance
     *
     * @return array                       status (room available or not and possible warnings)
     */
    public static function bigbluebuttonbn_room_is_available($bigbluebuttonbn) {
        $open = true;
        $closed = false;
        $warnings = array();

        $timenow = time();
        $timeopen = $bigbluebuttonbn->openingtime;
        $timeclose = $bigbluebuttonbn->closingtime;
        if (!empty($timeopen) && $timeopen > $timenow) {
            $open = false;
        }
        if (!empty($timeclose) && $timenow > $timeclose) {
            $closed = true;
        }

        if (!$open || $closed) {
            if (!$open) {
                $warnings['notopenyet'] = userdate($timeopen);
            }
            if ($closed) {
                $warnings['expired'] = userdate($timeclose);
            }
            return array(false, $warnings);
        }

        return array(true, $warnings);
    }

    /**
     * Return the status of an activity [open|not_started|ended].
     *
     * @param array $bbbsession
     * @return string
     */
    public static function bigbluebuttonbn_view_get_activity_status(&$bbbsession) {
        $now = time();
        if (!empty($bbbsession['bigbluebuttonbn']->openingtime) && $now < $bbbsession['bigbluebuttonbn']->openingtime) {
            // The activity has not been opened.
            return 'not_started';
        }
        if (!empty($bbbsession['bigbluebuttonbn']->closingtime) && $now > $bbbsession['bigbluebuttonbn']->closingtime) {
            // The activity has been closed.
            return 'ended';
        }
        // The activity is open.
        return 'open';
    }

     * Ensure that the remote server was contactable.
     *
     * @param instance $instance
     */
    public static function require_working_server(instance $instance): void {
        $serverversion = self::bigbluebuttonbn_get_server_version();
        if ($serverversion !== null) {
            return;
        }

        self::handle_server_not_available($instance);
    }

    /**
     * Handle the server not being available.
     *
     * @param instance $instance
     */
    public static function handle_server_not_available(instance $instance): void {
        if ($instance->is_admin()) {
            $errmsg = 'view_error_unable_join';
            $url = new moodle_url('/admin/settings.php', ['section' => 'modsettingbigbluebuttonbn']);
        } else if ($instance->is_moderator()) {
            $errmsg = 'view_error_unable_join_teacher';
            $url = new moodle_url('/course/view.php', ['id' => $instance->get_course_id()]);
        } else {
            $errmsg = 'view_error_unable_join_student';
            $url = new moodle_url('/course/view.php', ['id' => $instance->get_course_id()]);
        }

        \core\notification::add(get_string($errmsg, 'mod_bigbluebuttonbn'), \core\notification::ERROR);
        redirect($url);
    }
}
