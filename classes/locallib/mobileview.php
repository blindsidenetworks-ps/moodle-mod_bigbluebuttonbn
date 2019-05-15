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
 * The mod_bigbluebuttonbn locallib/mobileview.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bigbluebuttonbn\locallib;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');

/**
 * Methods used to render view BBB in mobile.
 *
 * @copyright 2018 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobileview {

    /**
     * Return standard array with configurations required for BBB server.
     * @param context $context
     * @param array $session
     * @return mixed
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function bigbluebuttonbn_view_bbbsession_set($context, &$session) {

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
        $session['userlimit'] = intval((int)\mod_bigbluebuttonbn\locallib\config::get('userlimit_default'));
        if ((boolean)\mod_bigbluebuttonbn\locallib\config::get('userlimit_editable')) {
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
        $session['clienttype'] = \mod_bigbluebuttonbn\locallib\config::get('clienttype_default');

        if (\mod_bigbluebuttonbn\locallib\config::get('clienttype_editable')) {
            $session['clienttype'] = $session['bigbluebuttonbn']->clienttype;
        }

        if (!\mod_bigbluebuttonbn\locallib\config::clienttype_enabled()) {
            $session['clienttype'] = BIGBLUEBUTTON_CLIENTTYPE_FLASH;
        }

        return($session);
    }

    /**
     * Build url for join to session.
     * This method is similar to "bigbluebutton_bbb_view_join_meeting()" in bbb_view.
     * @param array $bbbsession
     * @return string
     */
    public static function build_url_join_session(&$bbbsession) {
        $password = $bbbsession['viewerPW'];
        if ($bbbsession['administrator'] || $bbbsession['moderator']) {
            $password = $bbbsession['modPW'];
        }
        $joinurl = bigbluebuttonbn_get_join_url($bbbsession['meetingid'], $bbbsession['username'],
            $password, $bbbsession['logoutURL'], null, $bbbsession['userID'], $bbbsession['clienttype']);

        return($joinurl);
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

    /**
     * Helper for preparing metadata used while creating the meeting.
     *
     * @param  array    $bbbsession
     * @return array
     */
    public static function bigbluebutton_bbb_view_create_meeting_metadata(&$bbbsession) {

        global $USER;
        // Create standard metadata.
        $metadatabbb = [
            'bbb-origin' => $bbbsession['origin'],
            'bbb-origin-version' => $bbbsession['originVersion'],
            'bbb-origin-server-name' => $bbbsession['originServerName'],
            'bbb-origin-server-common-name' => $bbbsession['originServerCommonName'],
            'bbb-origin-tag' => $bbbsession['originTag'],
            'bbb-context' => $bbbsession['course']->fullname,
            'bbb-recording-name' => bigbluebuttonbn_html2text($bbbsession['meetingname'], 64),
            'bbb-recording-description' => bigbluebuttonbn_html2text($bbbsession['meetingdescription'], 64),
            'bbb-recording-tags' => bigbluebuttonbn_get_tags($bbbsession['cm']->id), // Same as $id.
        ];
        // Check recording status.
        if ((boolean)\mod_bigbluebuttonbn\locallib\config::get('recordingstatus_enabled')) {
            $metadatabbb["bn-recording-status"] = json_encode(
                array(
                    'email' => array('"' . fullname($USER) . '" <' . $USER->email . '>'),
                    'context' => $bbbsession['bigbluebuttonbnURL']
                )
            );
        }
        if ((boolean)\mod_bigbluebuttonbn\locallib\config::get('recordingready_enabled')) {
            $metadatabbb['bn-recording-ready-url'] = $bbbsession['recordingReadyURL'];
        }
        if ((boolean)\mod_bigbluebuttonbn\locallib\config::get('meetingevents_enabled')) {
            $metadatabbb['bn-meeting-events-url'] = $bbbsession['meetingEventsURL'];
        }
        return $metadatabbb;
    }

    /**
     * Helper to prepare data used for create meeting.
     * @param array $bbbsession
     * @return array
     * @throws \coding_exception
     */
    public static function bigbluebutton_bbb_view_create_meeting_data(&$bbbsession) {
        $data = ['meetingID' => $bbbsession['meetingid'],
            'name' => bigbluebuttonbn_html2text($bbbsession['meetingname'], 64),
            'attendeePW' => $bbbsession['viewerPW'],
            'moderatorPW' => $bbbsession['modPW'],
            'logoutURL' => $bbbsession['logoutURL'],
        ];
        $data['record'] = self::bigbluebutton_bbb_view_create_meeting_data_record($bbbsession['record']);
        // Check if auto_start_record is enable.
        if ($data['record'] == 'true' && $bbbsession['recordallfromstart']) {
            $data['autoStartRecording'] = 'true';
            // Check if hide_record_button is enable.
            if ($bbbsession['recordallfromstart'] && $bbbsession['recordhidebutton']) {
                $data['allowStartStopRecording'] = 'false';
            }
        }
        $data['welcome'] = trim($bbbsession['welcome']);
        // Set the duration for the meeting.
        $durationtime = self::bigbluebutton_bbb_view_create_meeting_data_duration($bbbsession['bigbluebuttonbn']->closingtime);
        if ($durationtime > 0) {
            $data['duration'] = $durationtime;
            $data['welcome'] .= '<br><br>';
            $data['welcome'] .= str_replace(
                '%duration%',
                (string) $durationtime,
                get_string('bbbdurationwarning', 'bigbluebuttonbn')
            );
        }
        $voicebridge = intval($bbbsession['voicebridge']);
        if ($voicebridge > 0 && $voicebridge < 79999) {
            $data['voiceBridge'] = $voicebridge;
        }
        $maxparticipants = intval($bbbsession['userlimit']);
        if ($maxparticipants > 0) {
            $data['maxParticipants'] = $maxparticipants;
        }
        if ($bbbsession['muteonstart']) {
            $data['muteOnStart'] = 'true';
        }
        return $data;
    }

    /**
     * Helper for returning the flag to know if the meeting is recorded.
     *
     * @param  boolean    $record
     * @return string
     */
    public static function bigbluebutton_bbb_view_create_meeting_data_record($record) {
        if ((boolean)\mod_bigbluebuttonbn\locallib\config::recordings_enabled() && $record) {
            return 'true';
        }
        return 'false';
    }

    /**
     * Helper for returning the duration expected for the meeting.
     *
     * @param  string    $closingtime
     * @return integer
     */
    public static function bigbluebutton_bbb_view_create_meeting_data_duration($closingtime) {
        if ((boolean)\mod_bigbluebuttonbn\locallib\config::get('scheduled_duration_enabled')) {
            return bigbluebuttonbn_get_duration($closingtime);
        }
        return 0;
    }
}
