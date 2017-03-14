<?php
// This file is part of Moodle - http://moodle.org/

// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * View for BigBlueButton interaction.
 *
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2015 Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
require_once dirname(dirname(dirname(__FILE__))).'/config.php';
require_once dirname(__FILE__).'/locallib.php';

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$bn = optional_param('bn', 0, PARAM_INT); // bigbluebuttonbn instance ID
$action = required_param('action', PARAM_TEXT);
$name = optional_param('name', '', PARAM_TEXT);
$description = optional_param('description', '', PARAM_TEXT);
$tags = optional_param('tags', '', PARAM_TEXT);
$errors = optional_param('errors', '', PARAM_TEXT);

if ($id) {
    $cm = get_coursemodule_from_id('bigbluebuttonbn', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($bn) {
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $bn), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $bigbluebuttonbn->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('bigbluebuttonbn', $bigbluebuttonbn->id, $course->id, false, MUST_EXIST);
} else {
    print_error('You must specify a course_module ID or a BigBlueButtonBN instance ID');
}

$context = bigbluebuttonbn_get_context_module($cm->id);

/// Print the page header
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
            } elseif (isset($bbbsession) && !is_null($bbbsession)) {
                /// Moodle event logger: Create an event for meeting left
                bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_MEETING_LEFT, $bigbluebuttonbn, $cm);

                /// Update the cache
                $meeting_info = bigbluebuttonbn_get_meeting_info($bbbsession['meetingid'], BIGBLUEBUTTONBN_FORCED);

                /// Close the tab or window where BBB was opened
                bigbluebutton_bbb_view_close_window();
            } else {
                bigbluebutton_bbb_view_close_window_manually();
            }
            break;
        case 'join':
            if (isset($bbbsession) && !is_null($bbbsession)) {
                //See if the session is in progress
                if (bigbluebuttonbn_isMeetingRunning($bbbsession['meetingid'])) {
                    /// Since the meeting is already running, we just join the session
                    bigbluebutton_bbb_view_execute_join($bbbsession, $cm, $bigbluebuttonbn);
                    break;
                }

                // If user is not administrator nor moderator (user is steudent) and waiting is required
                if (!$bbbsession['administrator'] && !$bbbsession['moderator'] && $bbbsession['wait']) {
                    header('Location: '.$bbbsession['logoutURL']);
                    break;
                }

                /// Prepare the metadata
                $metadata = array('bbb-origin' => $bbbsession['origin'],
                                  'bbb-origin-version' => $bbbsession['originVersion'],
                                  'bbb-origin-server-name' => $bbbsession['originServerName'],
                                  'bbb-origin-server-common-name' => $bbbsession['originServerCommonName'],
                                  'bbb-origin-tag' => $bbbsession['originTag'],
                                  'bbb-context' => $bbbsession['course']->fullname,
                                  'bbb-recording-name' => (isset($name) && $name != '') ? $name : $bbbsession['contextActivityName'],
                                  'bbb-recording-description' => (isset($description) && $description != '') ? $description : $bbbsession['contextActivityDescription'],
                                  'bbb-recording-tags' => (isset($tags) && $tags != '') ? $tags : $bbbsession['contextActivityTags'],
                                );

                if (bigbluebuttonbn_server_offers_bn_capabilities()) {
                    if (bigbluebuttonbn_get_cfg_recordingready_enabled()) {
                        $metadata['bn-recording-ready-url'] = $bbbsession['recordingReadyURL'];
                    }

                    if (bigbluebuttonbn_get_cfg_meetingevents_enabled()) {
                        $metadata['bn-meeting-events-url'] = $bbbsession['meetingEventsURL'];
                    }
                }

                /// Set the duration for the meeting
                $durationtime = 0;
                if (bigbluebuttonbn_get_cfg_scheduled_duration_enabled()) {
                    $durationtime = bigbluebuttonbn_get_duration($bigbluebuttonbn->closingtime);
                    if ($durationtime > 0) {
                        $bbbsession['welcome'] .= '<br><br>'.str_replace('%duration%', ''.$durationtime, get_string('bbbdurationwarning', 'bigbluebuttonbn'));
                    }
                }
                /// Execute the create command
                $response = bigbluebuttonbn_getCreateMeetingArray(
                        $bbbsession['meetingname'],
                        $bbbsession['meetingid'],
                        $bbbsession['welcome'],
                        $bbbsession['modPW'],
                        $bbbsession['viewerPW'],
                        $bbbsession['shared_secret'],
                        $bbbsession['endpoint'],
                        $bbbsession['logoutURL'],
                        $bbbsession['record'] ? 'true' : 'false',
                        $durationtime,
                        $bbbsession['voicebridge'],
                        $bbbsession['userlimit'],
                        $metadata,
                        $bbbsession['presentation']['name'],
                        $bbbsession['presentation']['url']
                      );

                if (!$response) {
                    // If the server is unreachable, then prompts the user of the necessary action
                    $print_error_key = 'view_error_unable_join_student';
                    if ($bbbsession['administrator']) {
                        $print_error_key = 'view_error_unable_join';
                    }
                    if ($bbbsession['moderator']) {
                        $print_error_key = 'view_error_unable_join_teacher';
                    }
                    print_error($print_error_key, 'bigbluebuttonbn', $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn');
                    break;
                } elseif ($response['returncode'] == 'FAILED') {
                    // The meeting was not created
                    $print_error_key = bigbluebuttonbn_get_error_key($response['messageKey'], 'view_error_create');
                    if (!$print_error_key) {
                        $print_error_key = $response['message'];
                    }
                    print_error($print_error_key, 'bigbluebuttonbn');
                    break;
                } elseif ($response['hasBeenForciblyEnded'] == 'true') {
                    print_error(get_string('index_error_forciblyended', 'bigbluebuttonbn'));
                    break;
                }

                /// Moodle event logger: Create an event for meeting created
                bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_MEETING_CREATED, $bigbluebuttonbn, $cm);
                /// Internal logger: Insert a record with the meeting created
                bigbluebuttonbn_logs($bbbsession, BIGBLUEBUTTONBN_LOG_EVENT_CREATE);
                /// Since the meeting is already running, we just join the session
                bigbluebutton_bbb_view_execute_join($bbbsession, $cm, $bigbluebuttonbn);
                break;
            }

            print_error('view_error_unable_join', 'bigbluebuttonbn');
            break;
        default:
            bigbluebutton_bbb_view_close_window();
}

////////////////// Local functions /////////////////////
function bigbluebutton_bbb_view_close_window()
{
    global $OUTPUT, $PAGE;

    echo $OUTPUT->header();
    $PAGE->requires->js_init_call('M.mod_bigbluebuttonbn.view_windowClose');
    echo $OUTPUT->footer();
}

function bigbluebutton_bbb_view_close_window_manually()
{
    echo get_string('view_message_tab_close', 'bigbluebuttonbn');
}

function bigbluebutton_bbb_view_execute_join($bbbsession, $cm, $bigbluebuttonbn)
{
    //// Update the cache
    $meeting_info = bigbluebuttonbn_get_meeting_info($bbbsession['meetingid'], BIGBLUEBUTTONBN_FORCED);

    if ($bbbsession['userlimit'] > 0 && intval($meeting_info['participantCount']) >= $bbbsession['userlimit']) {
        // No more users allowed to join
        header('Location: '.$bbbsession['logoutURL']);

        return;
    }

    //// Build the URL
    $password = $bbbsession['viewerPW'];
    if ($bbbsession['administrator'] || $bbbsession['moderator']) {
        $password = $bbbsession['modPW'];
    }
    $join_url = bigbluebuttonbn_getJoinURL($bbbsession['meetingid'], $bbbsession['username'], $password, $bbbsession['logoutURL'], null, $bbbsession['userID']);
    //// Moodle event logger: Create an event for meeting joined
    bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_MEETING_JOINED, $bigbluebuttonbn, $cm);
    /// Internal logger: Instert a record with the meeting created
    bigbluebuttonbn_logs($bbbsession, BIGBLUEBUTTONBN_LOG_EVENT_JOIN);
    //// Before executing the redirect, increment the number of participants
    bigbluebuttonbn_participant_joined($bbbsession['meetingid'], ($bbbsession['administrator'] || $bbbsession['moderator']));
    //// Execute the redirect
    header('Location: '.$join_url);
}

function bigbluebutton_bbb_view_errors($sErrors, $id)
{
    global $CFG, $OUTPUT;

    $errors = (array) json_decode(urldecode($sErrors));
    $msgErrors = '';
    foreach ($errors as $error) {
        $msgErrors .= html_writer::tag('p', $error->{'message'}, array('class' => 'alert alert-danger'))."\n";
    }

    echo $OUTPUT->header();
    print_error('view_error_bigbluebutton', 'bigbluebuttonbn', $CFG->wwwroot.'/mod/bigbluebuttonbn/view.php?id='.$id, $msgErrors, $sErrors);
    echo $OUTPUT->footer();
}
