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

namespace mod_bigbluebuttonbn\locallib;

use context_module;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');

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
        bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events['view'], $bigbluebuttonbn);

        // Create array bbbsession with configuration for BBB server.
        $bbbsession['course'] = $course;
        $bbbsession['coursename'] = $course->fullname;
        $bbbsession['cm'] = $cm;
        $bbbsession['bigbluebuttonbn'] = $bigbluebuttonbn;
        self::view_bbbsession_set($context, $bbbsession);

        $serverversion = bigbluebuttonbn_get_server_version();
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
     * @param array $session
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function view_bbbsession_set($context, &$session) {

        global $CFG, $USER;

        $session['username'] = fullname($USER);
        $session['userID'] = $USER->id;
        $session['administrator'] = is_siteadmin($session['userID']);
        $participantlist = bigbluebuttonbn_get_participant_list($session['bigbluebuttonbn'], $context);
        $session['moderator'] = bigbluebuttonbn_is_moderator($context, $participantlist);
        $session['managerecordings'] = ($session['administrator']
            || has_capability('mod/bigbluebuttonbn:managerecordings', $context));
        $session['importrecordings'] = ($session['managerecordings']);
        $session['modPW'] = $session['bigbluebuttonbn']->moderatorpass;
        $session['viewerPW'] = $session['bigbluebuttonbn']->viewerpass;
        $session['meetingid'] = $session['bigbluebuttonbn']->meetingid.'-'.$session['course']->id.'-'.
            $session['bigbluebuttonbn']->id;
        $session['meetingname'] = $session['bigbluebuttonbn']->name;
        $session['meetingdescription'] = $session['bigbluebuttonbn']->intro;
        $session['userlimit'] = intval((int) config::get('userlimit_default'));
        if ((boolean) config::get('userlimit_editable')) {
            $session['userlimit'] = intval($session['bigbluebuttonbn']->userlimit);
        }
        $session['voicebridge'] = $session['bigbluebuttonbn']->voicebridge;
        if ($session['bigbluebuttonbn']->voicebridge > 0) {
            $session['voicebridge'] = 70000 + $session['bigbluebuttonbn']->voicebridge;
        }
        $session['wait'] = $session['bigbluebuttonbn']->wait;
        $session['record'] = $session['bigbluebuttonbn']->record;

        $session['recordallfromstart'] = $CFG->bigbluebuttonbn_recording_all_from_start_default;
        if ($CFG->bigbluebuttonbn_recording_all_from_start_editable) {
            $session['recordallfromstart'] = $session['bigbluebuttonbn']->recordallfromstart;
        }

        $session['recordhidebutton'] = $CFG->bigbluebuttonbn_recording_hide_button_default;
        if ($CFG->bigbluebuttonbn_recording_hide_button_editable) {
            $session['recordhidebutton'] = $session['bigbluebuttonbn']->recordhidebutton;
        }

        $session['welcome'] = $session['bigbluebuttonbn']->welcome;
        if (!isset($session['welcome']) || $session['welcome'] == '') {
            $session['welcome'] = get_string('mod_form_field_welcome_default', 'bigbluebuttonbn');
        }
        if ($session['bigbluebuttonbn']->record) {
            // Check if is enable record all from start.
            if ($session['recordallfromstart']) {
                $session['welcome'] .= '<br><br>'.get_string('bbbrecordallfromstartwarning',
                        'bigbluebuttonbn');
            } else {
                $session['welcome'] .= '<br><br>'.get_string('bbbrecordwarning', 'bigbluebuttonbn');
            }
        }
        $session['openingtime'] = $session['bigbluebuttonbn']->openingtime;
        $session['closingtime'] = $session['bigbluebuttonbn']->closingtime;
        $session['muteonstart'] = $session['bigbluebuttonbn']->muteonstart;
        $session['context'] = $context;
        $session['origin'] = 'Moodle';
        $session['originVersion'] = $CFG->release;
        $parsedurl = parse_url($CFG->wwwroot);
        $session['originServerName'] = $parsedurl['host'];
        $session['originServerUrl'] = $CFG->wwwroot;
        $session['originServerCommonName'] = '';
        $session['originTag'] = 'moodle-mod_bigbluebuttonbn ('.get_config('mod_bigbluebuttonbn', 'version').')';
        $session['bnserver'] = bigbluebuttonbn_is_bn_server();
        $session['clienttype'] = config::get('clienttype_default');

        if (config::get('clienttype_editable')) {
            $session['clienttype'] = $session['bigbluebuttonbn']->clienttype;
        }

        if (!config::clienttype_enabled()) {
            $session['clienttype'] = BIGBLUEBUTTON_CLIENTTYPE_FLASH;
        }
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

        $viewinstance = bigbluebuttonbn_view_validator($cmid, null);
        if ($viewinstance) {
            $bbbsession = self::build_bbb_session_fromviewinstance($viewinstance);
            if ($bbbsession) {
                require_once($CFG->dirroot . "/mod/bigbluebuttonbn/brokerlib.php");
                $info = bigbluebuttonbn_get_meeting_info($bbbsession['meetingid'], false);
                $running = false;
                if ($info['returncode'] == 'SUCCESS') {
                    $running = ($info['running'] === 'true');
                }
                $participantcount = 0;
                if (isset($info['participantCount'])) {
                    $participantcount = $info['participantCount'];
                }
                $canjoin = bigbluebuttonbn_broker_meeting_info_can_join($bbbsession, $running, $participantcount);
            }
        }
        return $canjoin;
    }
}
