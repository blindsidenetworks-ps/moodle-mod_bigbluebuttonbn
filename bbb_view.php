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

use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\exceptions\server_not_available_exception;
use mod_bigbluebuttonbn\meeting;
use mod_bigbluebuttonbn\local\helpers\files;
use mod_bigbluebuttonbn\local\helpers\roles;
use mod_bigbluebuttonbn\local\proxy\recording_proxy;
use mod_bigbluebuttonbn\local\proxy\bigbluebutton_proxy;
use mod_bigbluebuttonbn\local\view;
use mod_bigbluebuttonbn\logger;
use mod_bigbluebuttonbn\plugin;
use mod_bigbluebuttonbn\recording;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

global $SESSION, $PAGE, $CFG, $DB, $USER;

$action = required_param('action', PARAM_TEXT);
$id = optional_param('id', 0, PARAM_INT);
$bn = optional_param('bn', 0, PARAM_INT);
$rid = optional_param('rid', '', PARAM_TEXT);
$rtype = optional_param('rtype', 'presentation', PARAM_TEXT);
$errors = optional_param('errors', '', PARAM_TEXT);
$timeline = optional_param('timeline', 0, PARAM_INT);
$index = optional_param('index', 0, PARAM_INT);
$group = optional_param('group', -1, PARAM_INT);

// Get the bbb instance from either the cmid (id), or the instanceid (bn).
$id = optional_param('id', 0, PARAM_INT);
if ($id) {
    $instance = instance::get_from_cmid($id);
} else {
    $bn = optional_param('bn', 0, PARAM_INT);
    if ($bn) {
        $instance = instance::get_from_instanceid($bn);
    }
}

if (!$instance) {
    throw new moodle_exception('view_error_url_missing_parameters', plugin::COMPONENT);
}

$cm = $instance->get_cm();
$course = $instance->get_course();
$bigbluebuttonbn = $instance->get_instance_data();
$context = $instance->get_context();

require_login($course, true, $cm);

// Note : this uses the group optional_param as a value to decide which groupid.
$groupid = groups_get_activity_group($cm, true) ?: null;
if ($groupid) {
    $instance->set_group_id($groupid);
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

        if (empty($bigbluebuttonbn)) {
            bigbluebuttonbn_bbb_view_close_window_manually();
            break;
        }
        // Moodle event logger: Create an event for meeting left.
        logger::log_meeting_left_event($instance);

        // Update the cache.
        $meeting = new meeting($instance);
        $meeting->update_cache();

        // Check the origin page.
        $select = "userid = ? AND log = ?";
        $params = [
            'userid' => $USER->id,
            'log' => logger::EVENT_JOIN,
        ];
        $accesses = $DB->get_records_select('bigbluebuttonbn_logs', $select, $params, 'id ASC', 'id, meta', 1);
        $lastaccess = end($accesses);
        if (!empty($lastaccess->meta)) {
            $lastaccess = json_decode($lastaccess->meta);
            // If the user acceded from Timeline it should be redirected to the Dashboard.
            if (isset($lastaccess->origin) && $lastaccess->origin == logger::ORIGIN_TIMELINE) {
                redirect($CFG->wwwroot . '/my/');
            }
        }

        // Close the tab or window where BBB was opened.
        bigbluebuttonbn_bbb_view_close_window();
        break;
    case 'join':
        if (empty($bigbluebuttonbn)) {
            throw new moodle_exception('view_error_unable_join', 'bigbluebuttonbn');
            break;
        }
        // Check the origin page.
        $origin = logger::ORIGIN_BASE;
        if ($timeline) {
            $origin = logger::ORIGIN_TIMELINE;
        } else if ($index) {
            $origin = logger::ORIGIN_INDEX;
        }

        // See if the session is in progress.
        $meeting = new meeting($instance);
        if ($meeting->is_running()) {
            // Since the meeting is already running, we just join the session.
            $meeting->join($origin);
            break;
        }

        // If user is not administrator nor moderator (user is student) and waiting is required.
        if ($instance->user_must_wait_to_join()) {
            redirect($instance->get_logout_url());
            break;
        }

        // As the meeting doesn't exist, try to create it.
        try {
            $meeting = new meeting($instance);
            $response = $meeting->create_meeting();
            // New recording management: Insert a recordingID that corresponds to the meeting created.
            if ($instance->is_recorded()) {
                $recording = new recording(0, (object) array(
                    'courseid' => $instance->get_course_id(),
                    'bigbluebuttonbnid' => $instance->get_instance_id(),
                    'recordingid' => $response['internalMeetingID'],
                    'groupid' => $instance->get_group_id())
                    );
                $recording->create();
                // TODO: We may want to catch if the record was not created.
            }
            // Moodle event logger: Create an event for meeting created.
            logger::log_meeting_created_event($instance);
            // Since the meeting is already running, we just join the session.
            $meeting->join($origin);
        } catch (server_not_available_exception $e) {
            bigbluebutton_proxy::handle_server_not_available($instance);
        }
        break;

    case 'play':
        $href = bigbluebuttonbn_bbb_view_playback_href($rid, $rtype);
        logger::log_recording_played_event($instance, $rid);

        // Execute the redirect.
        header('Location: ' . urldecode($href));
        break;
    default:
        bigbluebuttonbn_bbb_view_close_window();
}

/**
 * Helper for getting the playback url that corresponds to an specific type.
 *
 * @param string $rid
 * @param string $rtype
 * @return string
 */
function bigbluebuttonbn_bbb_view_playback_href($rid, $rtype) {
    $recording = recording::get_record(['id' => $rid]);
    if (empty($recording)) {
        return '';
    }
    return bigbluebuttonbn_bbb_view_playback_href_lookup($recording->get('playbacks'), $rtype);
}

/**
 * Helper for looking up playback url in the recording playback array.
 *
 * @param array $playbacks
 * @param string $type
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
    // Behat does not like when we close the Windows as it is expecting to locate
    // on click part of the pages (bug with selenium raising an exception). So this is a workaround.
    if (!defined('BEHAT_SITE_RUNNING')) {
        $PAGE->requires->js_call_amd('mod_bigbluebuttonbn/rooms', 'setupWindowAutoClose');
    }
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
 * @param instance $instance
 * @return object
 */
function bigbluebuttonbn_bbb_view_create_meeting_data(instance $instance) {
    $data = [
        'meetingID' => $instance->get_meeting_id(),
        'name' => plugin::html2text($instance->get_meeting_name(), 64),
        'attendeePW' => $instance->get_viewer_password(),
        'moderatorPW' => $instance->get_moderator_password(),
        'logoutURL' => $instance->get_logout_url()->out(false),
        'record' => bigbluebuttonbn_bbb_view_create_meeting_data_record($instance->is_recorded()),
        'autoStartRecording' => $instance->should_record_from_start(),
        'allowStartStopRecording' => $instance->allow_recording_start_stop(),
        'welcome' => trim($instance->get_welcome_message()),
        'muteOnStart' => $instance->get_mute_on_start(),
    ];

    $voicebridge = $instance->get_voice_bridge();
    if ($voicebridge > 0 && $voicebridge < 79999) {
        $data['voiceBridge'] = $voicebridge;
    }

    $maxparticipants = $instance->get_user_limit();
    if ($maxparticipants > 0) {
        $data['maxParticipants'] = $maxparticipants;
    }

    // Lock settings.
    $lockedsettings = [
        'lockSettingsDisableCam' => 'disablecam',
        'lockSettingsDisableMic' => 'disablemic',
        'lockSettingsDisablePrivateChat' => 'disableprivatechat',
        'lockSettingsDisablePublicChat' => 'disablepublicchat',
        'lockSettingsDisableNote' => 'disablenote',
        'lockSettingsHideUserList' => 'hideuserlist',
        'lockSettingsLockedLayout' => 'lockedlayout',
        'lockSettingsLockOnJoin' => 'lockonjoin',
        'lockSettingsLockOnJoinConfigurable' => 'lockonjoinconfigurable',
    ];

    foreach ($lockedsettings as $datakey => $instancekey) {
        $data[$datakey] = $instance->get_instance_var($instancekey);
    }

    foreach ($data as $key => $value) {
        if (is_bool($value)) {
            $data[$key] = $value ? 'true' : 'false';
        }
    }

    return $data;
}

/**
 * Helper for returning the flag to know if the meeting is recorded.
 *
 * @param boolean $record
 * @return string
 */
function bigbluebuttonbn_bbb_view_create_meeting_data_record($record) {
    if ((boolean) \mod_bigbluebuttonbn\local\config::recordings_enabled() && $record) {
        return 'true';
    }
    return 'false';
}

/**
 * Helper for showinf error messages if any.
 *
 * @param string $serrors
 * @param string $id
 * @return string
 */
function bigbluebuttonbn_bbb_view_errors($serrors, $id) {
    global $CFG, $OUTPUT;
    $errors = (array) json_decode(urldecode($serrors));
    $msgerrors = '';
    foreach ($errors as $error) {
        $msgerrors .= html_writer::tag('p', $error->{'message'}, array('class' => 'alert alert-danger')) . "\n";
    }
    echo $OUTPUT->header();
    throw new moodle_exception('view_error_bigbluebutton', 'bigbluebuttonbn',
        $CFG->wwwroot . '/mod/bigbluebuttonbn/view.php?id=' . $id, $msgerrors, $serrors);
    echo $OUTPUT->footer();
}
