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

namespace mod_bigbluebuttonbn\local;

use mod_bigbluebuttonbn\local\helpers\meeting;

defined('MOODLE_INTERNAL') || die();
global $CFG;

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
    public static function build_url_join_session(instance $instance, ?int $createtime): string {
        $joinurl = bigbluebutton::bigbluebuttonbn_get_join_url(
            $instance->get_meeting_id(),
            $instance->get_user_fullname(),
            $instance->get_current_user_password(),
            $instance->get_logout_url(),
            null,
            $instance->get_user_id(),
            $createtime
        );

        return($joinurl);
    }

    /**
     * Helper for preparing metadata used while creating the meeting.
     *
     * @param  array    $bbbsession
     * @return array
     */
    public static function create_meeting_metadata(&$bbbsession) {
        return meeting::bigbluebuttonbn_create_meeting_metadata($bbbsession);
    }

    /**
     * Helper to prepare data used for create meeting.
     * @param array $bbbsession
     * @return array
     * @throws \coding_exception
     */
    public static function create_meeting_data(&$bbbsession) {
        $data = ['meetingID' => $bbbsession['meetingid'],
            'name' => \mod_bigbluebuttonbn\plugin::bigbluebuttonbn_html2text($bbbsession['meetingname'], 64),
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
        if ((boolean) config::recordings_enabled() && $record) {
            return 'true';
        }
        return 'false';
    }
}
