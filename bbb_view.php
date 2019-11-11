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
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

global $SESSION;

$action = required_param('action', PARAM_TEXT);
$id = optional_param('id', 0, PARAM_INT);
$bn = optional_param('bn', 0, PARAM_INT);
$href = optional_param('href', '', PARAM_TEXT);
$mid = optional_param('mid', '', PARAM_TEXT);
$rid = optional_param('rid', '', PARAM_TEXT);
$rtype = optional_param('rtype', 'presentation', PARAM_TEXT);
$errors = optional_param('errors', '', PARAM_TEXT);
$timeline = optional_param('timeline', 0, PARAM_INT);
$index = optional_param('index', 0, PARAM_INT);
$group = optional_param('group', -1, PARAM_INT);

$bbbviewinstance = bigbluebuttonbn_view_validator($id, $bn);
if (!$bbbviewinstance) {
    print_error(get_string('view_error_url_missing_parameters', 'bigbluebuttonbn'));
}

$cm = $bbbviewinstance['cm'];
$course = $bbbviewinstance['course'];
$bigbluebuttonbn = $bbbviewinstance['bigbluebuttonbn'];
$context = context_module::instance($cm->id);

require_login($course, true, $cm);

$bbbsession = null;
if (isset($SESSION->bigbluebuttonbn_bbbsession)) {
    $bbbsession = $SESSION->bigbluebuttonbn_bbbsession;
}

if ($timeline || $index) {
    // If the user come from timeline or index page, the $bbbsession should be created or overriden here.
    $bbbsession['course'] = $course;
    $bbbsession['coursename'] = $course->fullname;
    $bbbsession['cm'] = $cm;
    $bbbsession['bigbluebuttonbn'] = $bigbluebuttonbn;
    bigbluebuttonbn_view_bbbsession_set($context, $bbbsession);

    // Validates if the BigBlueButton server is working.
    $serverversion = bigbluebuttonbn_get_server_version();
    if (is_null($serverversion)) {
        if ($bbbsession['administrator']) {
            print_error('view_error_unable_join', 'bigbluebuttonbn',
                $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn');
            exit;
        }
        if ($bbbsession['moderator']) {
            print_error('view_error_unable_join_teacher', 'bigbluebuttonbn',
                $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course);
            exit;
        }
        print_error('view_error_unable_join_student', 'bigbluebuttonbn',
            $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course);
        exit;
    }
    $bbbsession['serverversion'] = (string) $serverversion;

    // Operation URLs.
    $bbbsession['bigbluebuttonbnURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/view.php?id=' . $bbbsession['cm']->id;
    $bbbsession['logoutURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_view.php?action=logout&id='.$id .
        '&bn=' . $bbbsession['bigbluebuttonbn']->id;
    $bbbsession['recordingReadyURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_broker.php?action=recording_' .
        'ready&bigbluebuttonbn=' . $bbbsession['bigbluebuttonbn']->id;
    $bbbsession['meetingEventsURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_broker.php?action=meeting' .
        '_events&bigbluebuttonbn=' . $bbbsession['bigbluebuttonbn']->id;
    $bbbsession['joinURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_view.php?action=join&id=' . $cm->id .
        '&bn=' . $bbbsession['bigbluebuttonbn']->id;

    // Check status and set extra values.
    $activitystatus = bigbluebuttonbn_view_get_activity_status($bbbsession);
    if ($activitystatus == 'ended') {
        $bbbsession['presentation'] = bigbluebuttonbn_get_presentation_array(
            $bbbsession['context'], $bbbsession['bigbluebuttonbn']->presentation);
    } else if ($activitystatus == 'open') {
        $bbbsession['presentation'] = bigbluebuttonbn_get_presentation_array(
            $bbbsession['context'], $bbbsession['bigbluebuttonbn']->presentation, $bbbsession['bigbluebuttonbn']->id);
    }

    // Check group.
    if ($group >= 0) {
        $bbbsession['group'] = $group;
        $groupname = get_string('allparticipants');
        if ($bbbsession['group'] != 0) {
            $groupname = groups_get_group_name($bbbsession['group']);
        }

        // Assign group default values.
        $bbbsession['meetingid'] .= '['.$bbbsession['group'].']';
        $bbbsession['meetingname'] .= ' ('.$groupname.')';
    }

    // Initialize session variable used across views.
    $SESSION->bigbluebuttonbn_bbbsession = $bbbsession;
}

// Print the page header.
$PAGE->set_context($context);
$PAGE->set_url('/mod/bigbluebuttonbn/bbb_view.php', array('id' => $cm->id, 'bigbluebuttonbn' => $bigbluebuttonbn->id));
$PAGE->set_title(format_string($bigbluebuttonbn->name));
$PAGE->set_cacheable(false);
$PAGE->set_heading($course->fullname);
$PAGE->blocks->show_only_fake_blocks();

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
        bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events['meeting_left'], $bigbluebuttonbn);
        // Update the cache.
        $meetinginfo = bigbluebuttonbn_get_meeting_info($bbbsession['meetingid'], BIGBLUEBUTTONBN_UPDATE_CACHE);
        // Check the origin page.
        $select = "userid = ? AND log = ?";
        $params = array(
                'userid' => $bbbsession['userID'],
                'log' => BIGBLUEBUTTONBN_LOG_EVENT_JOIN,
            );
        $accesses = $DB->get_records_select('bigbluebuttonbn_logs', $select, $params, 'id ASC', 'id, meta', 1);
        $lastaccess = end($accesses);
        $lastaccess = json_decode($lastaccess->meta);
        // If the user acceded from Timeline it should be redirected to the Dashboard.
        if (isset($lastaccess->origin) && $lastaccess->origin == BIGBLUEBUTTON_ORIGIN_TIMELINE) {
            redirect($CFG->wwwroot . '/my/');
        }
        // Close the tab or window where BBB was opened.
        bigbluebutton_bbb_view_close_window();
        break;
    case 'join':
        if (is_null($bbbsession)) {
            print_error('view_error_unable_join', 'bigbluebuttonbn');
            break;
        }
        // Check the origin page.
        $origin = BIGBLUEBUTTON_ORIGIN_BASE;
        if ($timeline) {
            $origin = BIGBLUEBUTTON_ORIGIN_TIMELINE;
        } else if ($index) {
            $origin = BIGBLUEBUTTON_ORIGIN_INDEX;
        }
        // See if the session is in progress.
        if (bigbluebuttonbn_is_meeting_running($bbbsession['meetingid'])) {
            // Since the meeting is already running, we just join the session.
            bigbluebutton_bbb_view_join_meeting($bbbsession, $bigbluebuttonbn, $origin);
            break;
        }
        // If user is not administrator nor moderator (user is steudent) and waiting is required.
        if (!$bbbsession['administrator'] && !$bbbsession['moderator'] && $bbbsession['wait']) {
            header('Location: '.$bbbsession['logoutURL']);
            break;
        }
        // As the meeting doesn't exist, try to create it.
        $response = bigbluebuttonbn_get_create_meeting_array(
            bigbluebutton_bbb_view_create_meeting_data($bbbsession),
            bigbluebutton_bbb_view_create_meeting_metadata($bbbsession),
            $bbbsession['presentation']['name'],
            $bbbsession['presentation']['url']
        );
        if (empty($response)) {
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
        bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events['meeting_create'], $bigbluebuttonbn);
        // Internal logger: Insert a record with the meeting created.
        $overrides = array('meetingid' => $bbbsession['meetingid']);
        $meta = '{"record":'.($bbbsession['record'] ? 'true' : 'false').'}';
        bigbluebuttonbn_log($bbbsession['bigbluebuttonbn'], BIGBLUEBUTTONBN_LOG_EVENT_CREATE, $overrides, $meta);
        // Since the meeting is already running, we just join the session.
        bigbluebutton_bbb_view_join_meeting($bbbsession, $bigbluebuttonbn, $origin);
        break;
    case 'play':
        $href = bigbluebutton_bbb_view_playback_href($href, $mid, $rid, $rtype);
        // Moodle event logger: Create an event for meeting left.
        bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events['recording_play'], $bigbluebuttonbn,
            ['other' => $rid]);
        // Internal logger: Instert a record with the playback played.
        $overrides = array('meetingid' => $bbbsession['meetingid']);
        bigbluebuttonbn_log($bbbsession['bigbluebuttonbn'], BIGBLUEBUTTONBN_LOG_EVENT_PLAYED, $overrides);
        // Execute the redirect.
        header('Location: '.urldecode($href));
        break;
    default:
        bigbluebutton_bbb_view_close_window();
}

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
    // Check if auto_start_record is enable.
    if ($data['record'] == 'true' && $bbbsession['recordallfromstart']) {
        $data['autoStartRecording'] = 'true';
        // Check if hide_record_button is enable.
        if ($bbbsession['recordhidebutton']) {
            $data['allowStartStopRecording'] = 'false';
        }
    }

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
 * @param array    $bbbsession
 * @param object   $bigbluebuttonbn
 * @param integer  $origin
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
