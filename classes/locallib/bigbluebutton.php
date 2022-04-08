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

use context_course;
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
     * @param array $bbbsession
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function view_bbbsession_set($context, &$bbbsession) {

        global $CFG, $USER;

        $bbbsession['username'] = fullname($USER);
        $bbbsession['userID'] = $USER->id;
        $bbbsession['administrator'] = is_siteadmin($bbbsession['userID']);
        $participantlist = bigbluebuttonbn_get_participant_list($bbbsession['bigbluebuttonbn'], $context);
        $bbbsession['moderator'] = bigbluebuttonbn_is_moderator($context, $participantlist);
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
        if (!isset($bbbsession['welcome']) || $bbbsession['welcome'] == '' || !config::get('welcome_editable')) {
            // CONTRIB-8573: default to the config and if empty, then the default string.
            $bbbsession['welcome'] = config::get('welcome_default');
            if (!$bbbsession['welcome']) {
                $bbbsession['welcome'] = get_string('mod_form_field_welcome_default', 'bigbluebuttonbn');
            }
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
        $bbbsession['lockonjoin'] = $bbbsession['bigbluebuttonbn']->lockonjoin;
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
        $bbbsession['bnserver'] = bigbluebuttonbn_is_bn_server();
        // Setting for clienttype, assign flash if not enabled, or default if not editable.
        $bbbsession['clienttype'] = config::get('clienttype_default');
        if (config::get('clienttype_editable')) {
            $bbbsession['clienttype'] = $bbbsession['bigbluebuttonbn']->clienttype;
        }
        if (!config::clienttype_enabled()) {
            $bbbsession['clienttype'] = BIGBLUEBUTTON_CLIENTTYPE_FLASH;
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

    /**
     * Check if a user has access to a given group.
     *
     * @param int $groupid
     * @param object $user
     * @param object $course
     * @param object $cm
     * @return bool
     */
    public static function user_can_access_groups($groupid, $user, $course, $cm) {
        $groupmode = groups_get_activity_groupmode($cm);
        $context = context_course::instance($course->id);
        $aag = has_capability('moodle/site:accessallgroups', $context, $user);
        if ($aag) {
            return true;
        }
        if ($groupmode == VISIBLEGROUPS or $aag) {
            $allowedgroups = groups_get_all_groups($course->id, $user->id, $course->defaultgroupingid);
        } else {
            $allowedgroups = groups_get_all_groups($course->id, $user->id, $course->defaultgroupingid);
        }
        foreach ($allowedgroups as $g) {
            if ($g->id == $groupid) {
                return true;
            }
        }
        return false;
    }
}
