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
 * View for BigBlueButton interaction.
 *
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2015-2017 Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$action = required_param('action', PARAM_TEXT);
$id = optional_param('id', 0, PARAM_INT);
$bn = optional_param('bn', 0, PARAM_INT);
$href = optional_param('href', '', PARAM_TEXT);
$mid = optional_param('mid', '', PARAM_TEXT);
$rid = optional_param('rid', '', PARAM_TEXT);
$rtype = optional_param('rtype', 'presentation', PARAM_TEXT);
$errors = optional_param('errors', '', PARAM_TEXT);

$bbbviewinstance = bigbluebuttonbn_views_validator($id, $bn);
if (!$bbbviewinstance) {
    print_error(get_string('view_error_url_missing_parameters', 'bigbluebuttonbn'));
}

$cm = $bbbviewinstance['cm'];
$course = $bbbviewinstance['course'];
$bigbluebuttonbn = $bbbviewinstance['bigbluebuttonbn'];

$context = context_module::instance($cm->id);

// Print the page header.
$PAGE->set_context($context);
$PAGE->set_url('/mod/bigbluebuttonbn/bbb_view.php', array('id' => $cm->id, 'bigbluebuttonbn' => $bigbluebuttonbn->id));
$PAGE->set_title(format_string($bigbluebuttonbn->name));
$PAGE->set_cacheable(false);
$PAGE->set_heading($course->fullname);
$PAGE->blocks->show_only_fake_blocks();

require_login($course, true, $cm);

$bbbsession = null;
if (isset($SESSION) && isset($SESSION->bigbluebuttonbn_bbbsession)) {
    $bbbsession = $SESSION->bigbluebuttonbn_bbbsession;
}

switch (strtolower($action)) {
    case 'logout':
        if (isset($errors) && $errors != '') {
            bigbluebutton_bbb_view_errors($errors, $id);
            break;
        }

        if (is_null($bbbsession)) {
            bigbluebutton_bbb_view_close_window_manually();
            break;
        }

        // Moodle event logger: Create an event for meeting left.
        bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_MEETING_LEFT, $bigbluebuttonbn, $cm);

        // Update the cache.
        $meetinginfo = bigbluebuttonbn_get_meeting_info($bbbsession['meetingid'], BIGBLUEBUTTONBN_FORCED);

        // Close the tab or window where BBB was opened.
        bigbluebutton_bbb_view_close_window();
        break;
    case 'join':
        if (is_null($bbbsession)) {
            print_error('view_error_unable_join', 'bigbluebuttonbn');
            break;
        }

        // See if the session is in progress.
        if (bigbluebuttonbn_is_meeting_running($bbbsession['meetingid'])) {
            // Since the meeting is already running, we just join the session.
            bigbluebutton_bbb_view_join_meeting($bbbsession, $cm, $bigbluebuttonbn);
            break;
        }

        // If user is not administrator nor moderator (user is steudent) and waiting is required.
        if (!$bbbsession['administrator'] && !$bbbsession['moderator'] && $bbbsession['wait']) {
            header('Location: '.$bbbsession['logoutURL']);
            break;
        }

        // As the meeting doesn't exist, try to create it.
        $response = bigbluebuttonbn_get_create_meeting_array(
            bigbluebutton_bbb_view_create_meeting_data($bbbsession, $bigbluebuttonbn),
            bigbluebutton_bbb_view_create_meeting_metadata($bbbsession),
            $bbbsession['presentation']['name'],
            $bbbsession['presentation']['url']
        );

        if (!$response) {
            // The server is unreachable.
            if ($bbbsession['administrator']) {
                print_error('view_error_unable_join', 'bigbluebuttonbn',
                    $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn');
                break;
            }
            if ($bbbsession['moderator']) {
                print_error('view_error_unable_join_teacher', 'bigbluebuttonbn',
                    $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn');
                break;
            }

            print_error('view_error_unable_join_student', 'bigbluebuttonbn',
                $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn');
            break;
        }

        if ($response['returncode'] == 'FAILED') {
            // The meeting was not created.
            if (!$printerrorkey) {
                print_error($response['message'], 'bigbluebuttonbn');
                break;
            }
            $printerrorkey = bigbluebuttonbn_get_error_key($response['messageKey'], 'view_error_create');
            print_error($printerrorkey, 'bigbluebuttonbn');
            break;
        }

        if ($response['hasBeenForciblyEnded'] == 'true') {
            print_error(get_string('index_error_forciblyended', 'bigbluebuttonbn'));
            break;
        }

        // Moodle event logger: Create an event for meeting created.
        bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_MEETING_CREATED, $bigbluebuttonbn, $cm);
        // Internal logger: Insert a record with the meeting created.
        bigbluebuttonbn_logs($bbbsession, BIGBLUEBUTTONBN_LOG_EVENT_CREATE);
        // Since the meeting is already running, we just join the session.
        bigbluebutton_bbb_view_join_meeting($bbbsession, $cm, $bigbluebuttonbn);
        break;
    case 'play':
        $href = bigbluebutton_bbb_view_playback_href($href, $mid, $rid, $rtype);

        // Moodle event logger: Create an event for meeting left.
        bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_RECORDING_VIEWED, $bigbluebuttonbn, $cm, ['other' => $rid]);
        // Execute the redirect.
        header('Location: '.urldecode($href));
        break;
    default:
        bigbluebutton_bbb_view_close_window();
}

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

function bigbluebutton_bbb_view_playback_href_lookup($playbacks, $type) {
    foreach ($playbacks as $playback) {
        if ($playback['type'] == $type) {
            return $playback['url'];
        }
    }
    return '';
}

function bigbluebutton_bbb_view_close_window() {
    global $OUTPUT, $PAGE;

    echo $OUTPUT->header();
    $PAGE->requires->yui_module('moodle-mod_bigbluebuttonbn-rooms', 'M.mod_bigbluebuttonbn.rooms.window_close');
    echo $OUTPUT->footer();
}

function bigbluebutton_bbb_view_close_window_manually() {
    echo get_string('view_message_tab_close', 'bigbluebuttonbn');
}

function bigbluebutton_bbb_view_create_meeting_data(&$bbbsession, $bigbluebuttonbn) {
    $data = ['meetingID' => $bbbsession['meetingid'],
              'name' => $bbbsession['meetingname'],
              'attendeePW' => $bbbsession['viewerPW'],
              'moderatorPW' => $bbbsession['modPW'],
              'logoutURL' => $bbbsession['logoutURL'],
            ];
    $data['record'] = bigbluebutton_bbb_view_create_meeting_data_record($bbbsession['record']);
    $data['welcome'] = trim($bbbsession['welcome']);

    // Set the duration for the meeting.
    $durationtime = bigbluebutton_bbb_view_create_meeting_data_duration($bigbluebuttonbn->closingtime);
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

function bigbluebutton_bbb_view_create_meeting_data_record($record) {
    if ((boolean)\mod_bigbluebuttonbn\locallib\config::recordings_enabled() && $record) {
        return 'true';
    }
    return 'false';
}

function bigbluebutton_bbb_view_create_meeting_data_duration($closingtime) {
    if ((boolean)\mod_bigbluebuttonbn\locallib\config::get('scheduled_duration_enabled')) {
        return bigbluebuttonbn_get_duration($closingtime);
    }
    return 0;
}

function bigbluebutton_bbb_view_create_meeting_metadata(&$bbbsession) {
    global $USER;

    $metadata = ['bbb-origin' => $bbbsession['origin'],
                 'bbb-origin-version' => $bbbsession['originVersion'],
                 'bbb-origin-server-name' => $bbbsession['originServerName'],
                 'bbb-origin-server-common-name' => $bbbsession['originServerCommonName'],
                 'bbb-origin-tag' => $bbbsession['originTag'],
                 'bbb-context' => $bbbsession['course']->fullname,
                 'bbb-recording-name' => $bbbsession['meetingname'],
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

function bigbluebutton_bbb_view_join_meeting($bbbsession, $cm, $bigbluebuttonbn) {
    // Update the cache.
    $meetinginfo = bigbluebuttonbn_get_meeting_info($bbbsession['meetingid'], BIGBLUEBUTTONBN_FORCED);

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
        $password, $bbbsession['logoutURL'], null, $bbbsession['userID']);
    // Moodle event logger: Create an event for meeting joined.
    bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_MEETING_JOINED, $bigbluebuttonbn, $cm);
    // Internal logger: Instert a record with the meeting created.
    bigbluebuttonbn_logs($bbbsession, BIGBLUEBUTTONBN_LOG_EVENT_JOIN);
    // Before executing the redirect, increment the number of participants.
    bigbluebuttonbn_participant_joined($bbbsession['meetingid'],
        ($bbbsession['administrator'] || $bbbsession['moderator']));
    // Execute the redirect.
    header('Location: '.$joinurl);
}

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
