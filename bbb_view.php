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

$id = optional_param('id', 0, PARAM_INT);
$bn = optional_param('bn', 0, PARAM_INT);
$action = required_param('action', PARAM_TEXT);
$name = optional_param('name', '', PARAM_TEXT);
$description = optional_param('description', '', PARAM_TEXT);
$tags = optional_param('tags', '', PARAM_TEXT);
$errors = optional_param('errors', '', PARAM_TEXT);

if ($id) {
    $cm = get_coursemodule_from_id('bigbluebuttonbn', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($bn) {
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $bn), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $bigbluebuttonbn->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('bigbluebuttonbn', $bigbluebuttonbn->id, $course->id, false, MUST_EXIST);
} else {
    print_error('You must specify a course_module ID or a BigBlueButtonBN instance ID');
}

$context = context_module::instance($cm->id);

// Print the page header.
$PAGE->set_context($context);
$PAGE->set_url('/mod/bigbluebuttonbn/bbb_view.php', array('id' => $cm->id, 'bigbluebuttonbn' => $bigbluebuttonbn->id));
$PAGE->set_title(format_string($bigbluebuttonbn->name));
$PAGE->set_cacheable(false);
$PAGE->set_heading($course->fullname);
$PAGE->blocks->show_only_fake_blocks();

require_login($course, true, $cm);

if (isset($SESSION) && isset($SESSION->bigbluebuttonbn_bbbsession)) {
    $bbbsession = $SESSION->bigbluebuttonbn_bbbsession;
}

switch (strtolower($action)) {
    case 'logout':
        if (isset($errors) && $errors != '') {
            bigbluebutton_bbb_view_errors($errors, $id);
        } else if (isset($bbbsession) && !is_null($bbbsession)) {
            // Moodle event logger: Create an event for meeting left.
            bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_MEETING_LEFT, $bigbluebuttonbn, $cm);

            // Update the cache.
            $meetinginfo = bigbluebuttonbn_get_meeting_info($bbbsession['meetingid'], BIGBLUEBUTTONBN_FORCED);

            // Close the tab or window where BBB was opened.
            bigbluebutton_bbb_view_close_window();
        } else {
            bigbluebutton_bbb_view_close_window_manually();
        }
        break;
    case 'join':
        if (!isset($bbbsession) || is_null($bbbsession)) {
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
        $response = bigbluebutton_bbb_view_create_meeting($bbbsession, $bigbluebuttonbn, $name, $description, $tags);

        if (!$response) {
            // If the server is unreachable, then prompts the user of the necessary action.
            $printerrorkey = 'view_error_unable_join_student';
            if ($bbbsession['administrator']) {
                $printerrorkey = 'view_error_unable_join';
            }
            if ($bbbsession['moderator']) {
                $printerrorkey = 'view_error_unable_join_teacher';
            }
            print_error($printerrorkey, 'bigbluebuttonbn',
                $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn');
            break;
        }

        if ($response['returncode'] == 'FAILED') {
            // The meeting was not created.
            $printerrorkey = bigbluebuttonbn_get_error_key($response['messageKey'], 'view_error_create');
            if (!$printerrorkey) {
                $printerrorkey = $response['message'];
            }
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
    default:
        bigbluebutton_bbb_view_close_window();
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

function bigbluebutton_bbb_view_create_meeting($bbbsession, $bigbluebuttonbn, $name, $description, $tags) {

    // Prepare the metadata.
    $bbbrecordingname = $bbbsession['contextActivityName'];
    if (!empty($name)) {
        $bbbrecordingname = $name;
    }
    $bbbrecordingdescription = $bbbsession['contextActivityDescription'];
    if (!empty($description)) {
        $bbbrecordingdescription = $description;
    }
    $bbbrecordingtags = $bbbsession['contextActivityTags'];
    if (!empty($tags)) {
        $bbbrecordingtags = $tags;
    }
    $metadata = array('bbb-origin' => $bbbsession['origin'],
                      'bbb-origin-version' => $bbbsession['originVersion'],
                      'bbb-origin-server-name' => $bbbsession['originServerName'],
                      'bbb-origin-server-common-name' => $bbbsession['originServerCommonName'],
                      'bbb-origin-tag' => $bbbsession['originTag'],
                      'bbb-context' => $bbbsession['course']->fullname,
                      'bbb-recording-name' => $bbbrecordingname,
                      'bbb-recording-description' => $bbbrecordingdescription,
                      'bbb-recording-tags' => $bbbrecordingtags,
                    );

    if (bigbluebuttonbn_is_bn_server()) {
        if (bigbluebuttonbn_get_cfg_recordingready_enabled()) {
            $metadata['bn-recording-ready-url'] = $bbbsession['recordingReadyURL'];
        }
        if (bigbluebuttonbn_get_cfg_meetingevents_enabled()) {
            $metadata['bn-meeting-events-url'] = $bbbsession['meetingEventsURL'];
        }
    }

    // Set the duration for the meeting.
    $durationtime = 0;
    if (bigbluebuttonbn_get_cfg_scheduled_duration_enabled()) {
        $durationtime = bigbluebuttonbn_get_duration($bigbluebuttonbn->closingtime);
        if ($durationtime > 0) {
            $bbbsession['welcome'] .= '<br><br>'.str_replace('%duration%', ''.$durationtime,
                get_string('bbbdurationwarning', 'bigbluebuttonbn'));
        }
    }
    // Execute the create command.
    return bigbluebuttonbn_get_create_meeting_array(
            $bbbsession['meetingname'],
            $bbbsession['meetingid'],
            $bbbsession['welcome'],
            $bbbsession['modPW'],
            $bbbsession['viewerPW'],
            $bbbsession['logoutURL'],
            $bbbsession['record'] ? 'true' : 'false',
            $durationtime,
            $bbbsession['voicebridge'],
            $bbbsession['userlimit'],
            $metadata,
            $bbbsession['presentation']['name'],
            $bbbsession['presentation']['url']
        );
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
