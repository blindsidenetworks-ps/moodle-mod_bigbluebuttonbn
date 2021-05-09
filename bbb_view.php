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

use mod_bigbluebuttonbn\local\bbb_constants;
use mod_bigbluebuttonbn\local\bigbluebutton;
use mod_bigbluebuttonbn\local\helpers\files;
use mod_bigbluebuttonbn\local\helpers\logs;
use mod_bigbluebuttonbn\local\helpers\meeting;
use mod_bigbluebuttonbn\local\helpers\recording;
use mod_bigbluebuttonbn\local\helpers\roles;
use mod_bigbluebuttonbn\local\view;
use mod_bigbluebuttonbn\plugin;

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

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
//param to configure a custom username
$usernamecustom = optional_param('usernamecustom', '', PARAM_TEXT);

$bbbviewinstance = view::bigbluebuttonbn_view_validator($id, $bn);
if (!$bbbviewinstance) {
    throw new moodle_exception('view_error_url_missing_parameters', 'bigbluebuttonbn');
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
    // Validates if the BigBlueButton server is working.
    $serverversion = bigbluebutton::bigbluebuttonbn_get_server_version();
    if (is_null($serverversion)) {
        if ($bbbsession['administrator']) {
            throw new moodle_exception('view_error_unable_join', 'bigbluebuttonbn',
                $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn');
            exit;
        }
        if ($bbbsession['moderator']) {
            throw new moodle_exception('view_error_unable_join_teacher', 'bigbluebuttonbn',
                $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course);
            exit;
        }
        throw new moodle_exception('view_error_unable_join_student', 'bigbluebuttonbn',
            $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course);
        exit;
    }

    $bbbsession = mod_bigbluebuttonbn\local\bigbluebutton::build_bbb_session($cm, $course, $bigbluebuttonbn);

    // Check status and set extra values.
    $activitystatus = bigbluebutton::bigbluebuttonbn_view_get_activity_status($bbbsession);
    if ($activitystatus == 'ended') {
        $bbbsession['presentation'] = files::bigbluebuttonbn_get_presentation_array(
            $bbbsession['context'], $bbbsession['bigbluebuttonbn']->presentation);
    } else if ($activitystatus == 'open') {
        $bbbsession['presentation'] = files::bigbluebuttonbn_get_presentation_array(
            $bbbsession['context'], $bbbsession['bigbluebuttonbn']->presentation, $bbbsession['bigbluebuttonbn']->id);
    }

    // Check group.
    if ($group >= 0) {
        if (bigbluebutton::user_can_access_groups($group, $USER, $course, $cm)) {
            $bbbsession['group'] = $group;
            $groupname = get_string('allparticipants');
            if ($bbbsession['group'] != 0) {
                $groupname = groups_get_group_name($bbbsession['group']);
            }

            // Assign group default values.
            $bbbsession['meetingid'] .= '[' . $bbbsession['group'] . ']';
            $bbbsession['meetingname'] .= ' (' . $groupname . ')';
        } else {
            print_error('invalidaccess');
        }
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
            bigbluebuttonbn_bbb_view_errors($errors, $id);
            break;
        }
        if (is_null($bbbsession)) {
            bigbluebuttonbn_bbb_view_close_window_manually();
            break;
        }
        // Moodle event logger: Create an event for meeting left.
        logs::bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events['meeting_left'], $bigbluebuttonbn);
        // Update the cache.
        $meetinginfo =
            meeting::bigbluebuttonbn_get_meeting_info($bbbsession['meetingid'], bbb_constants::BIGBLUEBUTTONBN_UPDATE_CACHE);
        // Check the origin page.
        $select = "userid = ? AND log = ?";
        $params = array(
                'userid' => $bbbsession['userID'],
                'log' => bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_JOIN,
            );
        $accesses = $DB->get_records_select('bigbluebuttonbn_logs', $select, $params, 'id ASC', 'id, meta', 1);
        $lastaccess = end($accesses);
        $lastaccess = json_decode($lastaccess->meta);
        // If the user acceded from Timeline it should be redirected to the Dashboard.
        if (isset($lastaccess->origin) && $lastaccess->origin == bbb_constants::BIGBLUEBUTTON_ORIGIN_TIMELINE) {
            redirect($CFG->wwwroot . '/my/');
        }
        // Close the tab or window where BBB was opened.
        bigbluebuttonbn_bbb_view_close_window();
        break;
    case 'join':
        if (is_null($bbbsession)) {
            throw new moodle_exception('view_error_unable_join', 'bigbluebuttonbn');
            break;
        }
        // Check the origin page.
        $origin = bbb_constants::BIGBLUEBUTTON_ORIGIN_BASE;
        if ($timeline) {
            $origin = bbb_constants::BIGBLUEBUTTON_ORIGIN_TIMELINE;
        } else if ($index) {
            $origin = bbb_constants::BIGBLUEBUTTON_ORIGIN_INDEX;
        }
        // See if the session is in progress.
        if (meeting::bigbluebuttonbn_is_meeting_running($bbbsession['meetingid'])) {
            // Since the meeting is already running, we just join the session.
            bigbluebuttonbn_bbb_view_join_meeting($bbbsession, $bigbluebuttonbn, $origin);
            break;
        }
        // If user is not administrator nor moderator (user is steudent) and waiting is required.
        if (!$bbbsession['administrator'] && !$bbbsession['moderator'] && $bbbsession['wait']) {
            header('Location: '.$bbbsession['logoutURL']);
            break;
        }
        // As the meeting doesn't exist, try to create it.
        $response = meeting::bigbluebuttonbn_get_create_meeting_array(
            bigbluebuttonbn_bbb_view_create_meeting_data($bbbsession),
            bigbluebuttonbn_bbb_view_create_meeting_metadata($bbbsession),
            $bbbsession['presentation']['name'],
            $bbbsession['presentation']['url']
        );
        if (empty($response)) {
            // The server is unreachable.
            if ($bbbsession['administrator']) {
                throw new moodle_exception('view_error_unable_join', 'bigbluebuttonbn',
                    $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn');
                break;
            }
            if ($bbbsession['moderator']) {
                throw new moodle_exception('view_error_unable_join_teacher', 'bigbluebuttonbn',
                    $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn');
                break;
            }
            throw new moodle_exception('view_error_unable_join_student', 'bigbluebuttonbn',
                $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn');
            break;
        }
        if ($response['returncode'] == 'FAILED') {
            // The meeting was not created.
            if (!$printerrorkey) {
                throw new moodle_exception($response['message'], 'bigbluebuttonbn');
                break;
            }
            $printerrorkey = plugin::bigbluebuttonbn_get_error_key($response['messageKey'], 'view_error_create');
            throw new moodle_exception($printerrorkey, 'bigbluebuttonbn');
            break;
        }
        if ($response['hasBeenForciblyEnded'] == 'true') {
            throw new moodle_exception(get_string('index_error_forciblyended', 'bigbluebuttonbn'));
            break;
        }
        // Moodle event logger: Create an event for meeting created.
        logs::bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events['meeting_create'], $bigbluebuttonbn);
        // Internal logger: Insert a record with the meeting created.
        $overrides = array('meetingid' => $bbbsession['meetingid']);
        $meta = '{"record":'.($bbbsession['record'] ? 'true' : 'false').'}';
        logs::bigbluebuttonbn_log($bbbsession['bigbluebuttonbn'], bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_CREATE, $overrides,
            $meta);
        // Since the meeting is already running, we just join the session.
        bigbluebuttonbn_bbb_view_join_meeting($bbbsession, $bigbluebuttonbn, $origin);
        break;
    case 'play':
        $href = bigbluebuttonbn_bbb_view_playback_href($href, $mid, $rid, $rtype);
        // Moodle event logger: Create an event for meeting left.
        logs::bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events['recording_play'], $bigbluebuttonbn,
            ['other' => $rid]);
        // Internal logger: Instert a record with the playback played.
        $overrides = array('meetingid' => $bbbsession['meetingid']);
        logs::bigbluebuttonbn_log($bbbsession['bigbluebuttonbn'], bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_PLAYED, $overrides);
        // Execute the redirect.
        header('Location: '.urldecode($href));
        break;
    default:
        bigbluebuttonbn_bbb_view_close_window();
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
function bigbluebuttonbn_bbb_view_playback_href($href, $mid, $rid, $rtype) {
    if ($href != '' || $mid == '' || $rid == '') {
        return $href;
    }
    $recordings = recording::bigbluebuttonbn_get_recordings_array($mid, $rid);
    if (empty($recordings)) {
        return '';
    }
    return bigbluebuttonbn_bbb_view_playback_href_lookup($recordings[$rid]['playbacks'], $rtype);
}

/**
 * Helper for looking up playback url in the recording playback array.
 *
 * @param  array    $playbacks
 * @param  string   $type
 * @return string
 */
function bigbluebuttonbn_bbb_view_playback_href_lookup($playbacks, $type) {
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
function bigbluebuttonbn_bbb_view_close_window() {
    global $OUTPUT, $PAGE;
    echo $OUTPUT->header();
    $PAGE->requires->js_call_amd('mod_bigbluebuttonbn/rooms', 'setupWindowAutoClose');
    echo $OUTPUT->footer();
}

/**
 * Helper for showing a message when the tab or window can not be closed.
 *
 * @return string
 */
function bigbluebuttonbn_bbb_view_close_window_manually() {
    echo get_string('view_message_tab_close', 'bigbluebuttonbn');
}

/**
 * Helper for preparing data used for creating the meeting.
 *
 * @param  array    $bbbsession
 * @return object
 */
function bigbluebuttonbn_bbb_view_create_meeting_data(&$bbbsession) {
    $data = ['meetingID' => $bbbsession['meetingid'],
              'name' => plugin::bigbluebuttonbn_html2text($bbbsession['meetingname'], 64),
              'attendeePW' => $bbbsession['viewerPW'],
              'moderatorPW' => $bbbsession['modPW'],
              'logoutURL' => $bbbsession['logoutURL'],
            ];
    $data['record'] = bigbluebuttonbn_bbb_view_create_meeting_data_record($bbbsession['record']);
    // Check if auto_start_record is enable.
    if ($data['record'] == 'true' && $bbbsession['recordallfromstart']) {
        $data['autoStartRecording'] = 'true';
        // Check if hide_record_button is enable.
        if ($bbbsession['recordhidebutton']) {
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
    // Lock settings.
    if ($bbbsession['disablecam']) {
        $data['lockSettingsDisableCam'] = 'true';
    }
    if ($bbbsession['disablemic']) {
        $data['lockSettingsDisableMic'] = 'true';
    }
    if ($bbbsession['disableprivatechat']) {
        $data['lockSettingsDisablePrivateChat'] = 'true';
    }
    if ($bbbsession['disablepublicchat']) {
        $data['lockSettingsDisablePublicChat'] = 'true';
    }
    if ($bbbsession['disablenote']) {
        $data['lockSettingsDisableNote'] = 'true';
    }
    if ($bbbsession['hideuserlist']) {
        $data['lockSettingsHideUserList'] = 'true';
    }
    if ($bbbsession['lockedlayout']) {
        $data['lockSettingsLockedLayout'] = 'true';
    }
    if ($bbbsession['lockonjoin']) {
        $data['lockSettingsLockOnJoin'] = 'false';
    }
    if ($bbbsession['lockonjoinconfigurable']) {
        $data['lockSettingsLockOnJoinConfigurable'] = 'true';
    }
    return $data;
}

/**
 * Helper for returning the flag to know if the meeting is recorded.
 *
 * @param  boolean    $record
 * @return string
 */
function bigbluebuttonbn_bbb_view_create_meeting_data_record($record) {
    if ((boolean)\mod_bigbluebuttonbn\local\config::recordings_enabled() && $record) {
        return 'true';
    }
    return 'false';
}

/**
 * Helper for preparing metadata used while creating the meeting.
 *
 * @param  array    $bbbsession
 * @return array
 */
function bigbluebuttonbn_bbb_view_create_meeting_metadata(&$bbbsession) {
    return meeting::bigbluebuttonbn_create_meeting_metadata($bbbsession);
}

/**
 * Helper for preparing data used while joining the meeting.
 *
 * @param array    $bbbsession
 * @param object   $bigbluebuttonbn
 * @param integer  $origin
 */
function bigbluebuttonbn_bbb_view_join_meeting($bbbsession, $bigbluebuttonbn, $origin = 0) {
    // Update the cache.
    $meetinginfo = meeting::bigbluebuttonbn_get_meeting_info($bbbsession['meetingid'], bbb_constants::BIGBLUEBUTTONBN_UPDATE_CACHE);
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
    $bbbsession['createtime'] = $meetinginfo['createTime'];
    $joinurl = bigbluebutton::bigbluebuttonbn_get_join_url($bbbsession['meetingid'], $bbbsession['username'],
        $password, $bbbsession['logoutURL'], null, $bbbsession['userID'], $bbbsession['createtime']);
    // Moodle event logger: Create an event for meeting joined.
    logs::bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events['meeting_join'], $bigbluebuttonbn);
    // Internal logger: Instert a record with the meeting created.
    $overrides = array('meetingid' => $bbbsession['meetingid']);
    $meta = '{"origin":'.$origin.'}';
    logs::bigbluebuttonbn_log($bbbsession['bigbluebuttonbn'], bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_JOIN, $overrides, $meta);
    // Before executing the redirect, increment the number of participants.
    roles::bigbluebuttonbn_participant_joined($bbbsession['meetingid'],
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
function bigbluebuttonbn_bbb_view_errors($serrors, $id) {
    global $CFG, $OUTPUT;
    $errors = (array) json_decode(urldecode($serrors));
    $msgerrors = '';
    foreach ($errors as $error) {
        $msgerrors .= html_writer::tag('p', $error->{'message'}, array('class' => 'alert alert-danger'))."\n";
    }
    echo $OUTPUT->header();
    throw new moodle_exception('view_error_bigbluebutton', 'bigbluebuttonbn',
        $CFG->wwwroot.'/mod/bigbluebuttonbn/view.php?id='.$id, $msgerrors, $serrors);
    echo $OUTPUT->footer();
}
