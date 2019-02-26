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
 * Broker view helper methods.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 * @author    Darko Miletic  (darko.miletic [at] gmail [dt] com)
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Helper for getting the playback url that corresponds to an specific type.
 *
 * @param  string   $href
 * @param  string   $mid
 * @param  string   $rid
 * @param  string   $rtype
 * @return string
 */
function bigbluebutton_bbb_view_playback_href($href, $mid, $rid, $rtype) {
    if ($href != '' || $mid == '' || $rid == '') {
        return $href;
    }
    $recordings = bigbluebuttonbn_get_recordings_array($mid, $rid);
    if (empty($recordings)) {
        return '';
    }
    return bigbluebutton_bbb_view_playback_href_lookup($recordings[$rid]['playbacks'], $rtype);
}

/**
 * Helper for looking up playback url in the recording playback array.
 *
 * @param  array    $playbacks
 * @param  string   $type
 * @return string
 */
function bigbluebutton_bbb_view_playback_href_lookup($playbacks, $type) {
    foreach ($playbacks as $playback) {
        if ($playback['type'] == $type) {
            return $playback['url'];
        }
    }
    return '';
}

/**
 * Helper for closing the tab or window when the user lefts the meeting.
 *
 * @return string
 */
function bigbluebutton_bbb_view_close_window() {
    global $OUTPUT, $PAGE;
    echo $OUTPUT->header();
    $PAGE->requires->yui_module('moodle-mod_bigbluebuttonbn-rooms', 'M.mod_bigbluebuttonbn.rooms.windowClose');
    echo $OUTPUT->footer();
}

/**
 * Helper for showing a message when the tab or window can not be closed.
 *
 * @return string
 */
function bigbluebutton_bbb_view_close_window_manually() {
    echo get_string('view_message_tab_close', 'bigbluebuttonbn');
}

/**
 * Helper for preparing data used for creating the meeting.
 *
 * @param  array    $bbbsession
 * @return object
 */
function bigbluebutton_bbb_view_create_meeting_data(&$bbbsession) {
    $data = ['meetingID' => $bbbsession['meetingid'],
        'name' => bigbluebuttonbn_html2text($bbbsession['meetingname'], 64),
        'attendeePW' => $bbbsession['viewerPW'],
        'moderatorPW' => $bbbsession['modPW'],
        'logoutURL' => $bbbsession['logoutURL'],
    ];
    $data['record'] = bigbluebutton_bbb_view_create_meeting_data_record($bbbsession['record']);
    $data['welcome'] = trim($bbbsession['welcome']);
    // Set the duration for the meeting.
    $durationtime = bigbluebutton_bbb_view_create_meeting_data_duration($bbbsession['bigbluebuttonbn']->closingtime);
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
function bigbluebutton_bbb_view_create_meeting_data_record($record) {
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
function bigbluebutton_bbb_view_create_meeting_data_duration($closingtime) {
    if ((boolean)\mod_bigbluebuttonbn\locallib\config::get('scheduled_duration_enabled')) {
        return bigbluebuttonbn_get_duration($closingtime);
    }
    return 0;
}

/**
 * Helper for preparing metadata used while creating the meeting.
 *
 * @param  array    $bbbsession
 * @return array
 */
function bigbluebutton_bbb_view_create_meeting_metadata(&$bbbsession) {
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
 * Helper for preparing data used while joining the meeting.
 *
 * @param  array    $bbbsession
 * @param object   $bigbluebuttonbn
 */
function bigbluebutton_bbb_view_join_meeting($bbbsession, $bigbluebuttonbn, $origin = 0) {
    // Update the cache.
    $meetinginfo = bigbluebuttonbn_get_meeting_info($bbbsession['meetingid'], BIGBLUEBUTTONBN_UPDATE_CACHE);
    if ($bbbsession['userlimit'] > 0 && intval($meetinginfo['participantCount']) >= $bbbsession['userlimit']) {
        // No more users allowed to join.
        header('Location: '.$bbbsession['logoutURL']);
        return;
    }
    // Build the URL.
    $password = $bbbsession['viewerPW'];
    if ($bbbsession['administrator'] || $bbbsession['moderator']) {
        $password = $bbbsession['modPW'];
    }
    $joinurl = bigbluebuttonbn_get_join_url($bbbsession['meetingid'], $bbbsession['username'],
        $password, $bbbsession['logoutURL'], null, $bbbsession['userID'], $bbbsession['clienttype']);
    // Moodle event logger: Create an event for meeting joined.
    bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events['meeting_join'], $bigbluebuttonbn);
    // Internal logger: Instert a record with the meeting created.
    $overrides = array('meetingid' => $bbbsession['meetingid']);
    $meta = '{"origin":'.$origin.'}';
    bigbluebuttonbn_log($bbbsession['bigbluebuttonbn'], BIGBLUEBUTTONBN_LOG_EVENT_JOIN, $overrides, $meta);
    // Before executing the redirect, increment the number of participants.
    bigbluebuttonbn_participant_joined($bbbsession['meetingid'],
        ($bbbsession['administrator'] || $bbbsession['moderator']));
    // Execute the redirect.
    header('Location: '.$joinurl);
}

/**
 * Helper for showinf error messages if any.
 *
 * @param  string   $serrors
 * @param  string   $id
 * @return string
 */
function bigbluebutton_bbb_view_errors($serrors, $id) {
    global $CFG, $OUTPUT;
    $errors = (array) json_decode(urldecode($serrors));
    $msgerrors = '';
    foreach ($errors as $error) {
        $msgerrors .= html_writer::tag('p', $error->{'message'}, array('class' => 'alert alert-danger'))."\n";
    }
    echo $OUTPUT->header();
    print_error('view_error_bigbluebutton', 'bigbluebuttonbn',
        $CFG->wwwroot.'/mod/bigbluebuttonbn/view.php?id='.$id, $msgerrors, $serrors);
    echo $OUTPUT->footer();
}
