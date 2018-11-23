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

require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');

/**
 * Methods used to render view BBB in mobile.
 *
 * @copyright 2018 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobileview {

    /**
     * Setup the bbbsession variable that is used all accross the plugin.
     *
     * @param object $context
     * @param array $bbbsession
     * @return array $bbbsession
     */
    public static function bigbluebuttonbn_view_bbbsession_set($context, &$bbbsession) {

        global $CFG, $USER;
        // User data.
        $bbbsession['username'] = fullname($USER);
        $bbbsession['userID'] = $USER->id;
        // User roles.
        $bbbsession['administrator'] = is_siteadmin($bbbsession['userID']);
        $participantlist = bigbluebuttonbn_get_participant_list($bbbsession['bigbluebuttonbn'], $context);
        $bbbsession['moderator'] = bigbluebuttonbn_is_moderator($context, $participantlist);
        $bbbsession['managerecordings'] = ($bbbsession['administrator']
            || has_capability('mod/bigbluebuttonbn:managerecordings', $context));
        $bbbsession['importrecordings'] = ($bbbsession['managerecordings']);
        // Server data.
        $bbbsession['modPW'] = $bbbsession['bigbluebuttonbn']->moderatorpass;
        $bbbsession['viewerPW'] = $bbbsession['bigbluebuttonbn']->viewerpass;
        // Database info related to the activity.
        $bbbsession['meetingid'] = $bbbsession['bigbluebuttonbn']->meetingid.'-'.$bbbsession['course']->id.'-'.
            $bbbsession['bigbluebuttonbn']->id;
        $bbbsession['meetingname'] = $bbbsession['bigbluebuttonbn']->name;
        $bbbsession['meetingdescription'] = $bbbsession['bigbluebuttonbn']->intro;
        // Extra data for setting up the Meeting.
        $bbbsession['userlimit'] = intval((int)\mod_bigbluebuttonbn\locallib\config::get('userlimit_default'));
        if ((boolean)\mod_bigbluebuttonbn\locallib\config::get('userlimit_editable')) {
            $bbbsession['userlimit'] = intval($bbbsession['bigbluebuttonbn']->userlimit);
        }
        $bbbsession['voicebridge'] = $bbbsession['bigbluebuttonbn']->voicebridge;
        if ($bbbsession['bigbluebuttonbn']->voicebridge > 0) {
            $bbbsession['voicebridge'] = 70000 + $bbbsession['bigbluebuttonbn']->voicebridge;
        }
        $bbbsession['wait'] = $bbbsession['bigbluebuttonbn']->wait;
        $bbbsession['record'] = $bbbsession['bigbluebuttonbn']->record;
        $bbbsession['welcome'] = $bbbsession['bigbluebuttonbn']->welcome;
        if (!isset($bbbsession['welcome']) || $bbbsession['welcome'] == '') {
            $bbbsession['welcome'] = get_string('mod_form_field_welcome_default', 'bigbluebuttonbn');
        }
        if ($bbbsession['bigbluebuttonbn']->record) {
            $bbbsession['welcome'] .= '<br><br>'.get_string('bbbrecordwarning', 'bigbluebuttonbn');
        }
        $bbbsession['openingtime'] = $bbbsession['bigbluebuttonbn']->openingtime;
        $bbbsession['closingtime'] = $bbbsession['bigbluebuttonbn']->closingtime;
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
        $bbbsession['clienttype'] = \mod_bigbluebuttonbn\locallib\config::get('clienttype_default');
        if (\mod_bigbluebuttonbn\locallib\config::get('clienttype_editable')) {
            $bbbsession['clienttype'] = $bbbsession['bigbluebuttonbn']->clienttype;
        }
        if (!\mod_bigbluebuttonbn\locallib\config::clienttype_enabled()) {
            $bbbsession['clienttype'] = BIGBLUEBUTTON_CLIENTTYPE_FLASH;
        }

        return($bbbsession);
    }

    /**
     * Build url for join to session.
     * This method is similar to "bigbluebutton_bbb_view_join_meeting" in bbb_view.
     * @param $bbbsession
     * @return string
     */
    public static function build_url_join_session($bbbsession) {
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
        $metadata = ['bbb-origin' => $bbbsession['origin'],
            'bbb-origin-version' => $bbbsession['originVersion'],
            'bbb-origin-server-name' => $bbbsession['originServerName'],
            'bbb-origin-server-common-name' => $bbbsession['originServerCommonName'],
            'bbb-origin-tag' => $bbbsession['originTag'],
            'bbb-context' => $bbbsession['course']->fullname,
            'bbb-recording-name' => bigbluebuttonbn_html2text($bbbsession['meetingname'], 64),
            'bbb-recording-description' => bigbluebuttonbn_html2text($bbbsession['meetingdescription'], 64),
            'bbb-recording-tags' => bigbluebuttonbn_get_tags($bbbsession['cm']->id), // Same as $id.
        ];
        if ((boolean)\mod_bigbluebuttonbn\locallib\config::get('recordingstatus_enabled')) {
            $metadata["bn-recording-status"] = json_encode(
                array(
                    'email' => array('"' . fullname($USER) . '" <' . $USER->email . '>'),
                    'context' => $bbbsession['bigbluebuttonbnURL']
                )
            );
        }
        if ((boolean)\mod_bigbluebuttonbn\locallib\config::get('recordingready_enabled')) {
            $metadata['bn-recording-ready-url'] = $bbbsession['recordingReadyURL'];
        }
        if ((boolean)\mod_bigbluebuttonbn\locallib\config::get('meetingevents_enabled')) {
            $metadata['bn-meeting-events-url'] = $bbbsession['meetingEventsURL'];
        }
        return $metadata;
    }

    /**
     * Helper for preparing data used for creating the meeting.
     *
     * @param  array    $bbbsession
     * @return object
     */
    public static function bigbluebutton_bbb_view_create_meeting_data(&$bbbsession) {
        $data = ['meetingID' => $bbbsession['meetingid'],
            'name' => bigbluebuttonbn_html2text($bbbsession['meetingname'], 64),
            'attendeePW' => $bbbsession['viewerPW'],
            'moderatorPW' => $bbbsession['modPW'],
            'logoutURL' => $bbbsession['logoutURL'],
        ];
        $data['record'] = self::bigbluebutton_bbb_view_create_meeting_data_record($bbbsession['record']);
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