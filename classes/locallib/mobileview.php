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
global $CFG;
require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');

/**
 * Methods used to render view BBB in mobile.
 *
 * @copyright 2018 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobileview {

    /**
     * Build url for join to session.
     * This method is similar to "join_meeting()" in bbb_view.
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
    public static function get_activity_status(&$bbbsession) {
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
    public static function create_meeting_metadata(&$bbbsession) {
        return bigbluebuttonbn_create_meeting_metadata($bbbsession);
    }

    /**
     * Helper to prepare data used for create meeting.
     * @param array $bbbsession
     * @return array
     * @throws \coding_exception
     */
    public static function create_meeting_data(&$bbbsession) {
        $data = ['meetingID' => $bbbsession['meetingid'],
            'name' => bigbluebuttonbn_html2text($bbbsession['meetingname'], 64),
            'attendeePW' => $bbbsession['viewerPW'],
            'moderatorPW' => $bbbsession['modPW'],
            'logoutURL' => $bbbsession['logoutURL'],
        ];
        $data['record'] = self::create_meeting_data_record($bbbsession['record']);
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
        $durationtime = self::create_meeting_data_duration($bbbsession['bigbluebuttonbn']->closingtime);
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
    public static function create_meeting_data_record($record) {
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
    public static function create_meeting_data_duration($closingtime) {
        if ((boolean)\mod_bigbluebuttonbn\locallib\config::get('scheduled_duration_enabled')) {
            return bigbluebuttonbn_get_duration($closingtime);
        }
        return 0;
    }
}
