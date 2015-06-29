<?php
/**
 * View for BigBlueButton interaction  
 *
 * @package   mod_bigbluebuttonbn
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = optional_param('id', 0, PARAM_INT);  // course_module ID, or
$bn = optional_param('bn', 0, PARAM_INT);  // bigbluebuttonbn instance ID
$action = required_param('action', PARAM_TEXT);

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

require_login($course, true, $cm);

$context = bigbluebuttonbn_get_context_module($cm->id);

/// Print the page header
$PAGE->set_context($context);
$PAGE->set_url('/mod/bigbluebuttonbn/bbb_view.php', array('id' => $cm->id, 'bigbluebuttonbn' => $bigbluebuttonbn->id));
$PAGE->set_title(format_string($bigbluebuttonbn->name));
$PAGE->set_cacheable(false);
$PAGE->blocks->show_only_fake_blocks();


$bbbsession = $SESSION->bigbluebuttonbn_bbbsession;
if ( !isset($bbbsession) || is_null($bbbsession) ) {
    print_error( 'view_error_unable_join', 'bigbluebuttonbn' );

} else {
    switch (strtolower($action)) {
        case 'logout':
            /// Moodle event logger: Create an event for meeting left
            bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_MEETING_LEFT, $bigbluebuttonbn, $context, $cm);

            /// Update the cache
            $meeting_info = bigbluebuttonbn_bbb_broker_get_meeting_info($bbbsession['meetingid'], $bbbsession['modPW'], true);

            /// Execute the redirect
            $view_url = $CFG->wwwroot.'/mod/bigbluebuttonbn/view.php?id='.$id;
            header('Location: '.$view_url );
            break;
        case 'join':
            //See if the session is in progress
            if( bigbluebuttonbn_isMeetingRunning( $bbbsession['meetingid'], $bbbsession['endpoint'], $bbbsession['shared_secret'] ) ) {
                /// Since the meeting is already running, we just join the session
                //// Update the cache
                $meeting_info = bigbluebuttonbn_bbb_broker_get_meeting_info($bbbsession['meetingid'], $bbbsession['modPW'], true);
                //// Build the URL
                if( $bbbsession['administrator'] || $bbbsession['moderator'] ) {
                    $join_url = bigbluebuttonbn_getJoinURL($bbbsession['meetingid'], $bbbsession['username'], $bbbsession['modPW'], $bbbsession['shared_secret'], $bbbsession['endpoint'], $bbbsession['userID']);
                } else {
                    $join_url = bigbluebuttonbn_getJoinURL($bbbsession['meetingid'], $bbbsession['username'], $bbbsession['viewerPW'], $bbbsession['shared_secret'], $bbbsession['endpoint'], $bbbsession['userID']);
                }
                //// Moodle event logger: Create an event for meeting joined
                bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_MEETING_JOINED, $bigbluebuttonbn, $context, $cm);
                /// Internal logger: Instert a record with the meeting created
                bigbluebuttonbn_log($bbbsession, 'Join');
                //// Before executing the redirect, increment the number of participants
                bigbluebuttonbn_bbb_broker_participant_joined($bbbsession['meetingid'], ($bbbsession['administrator'] || $bbbsession['moderator']) );
                //// Execute the redirect
                header('Location: '.$join_url );

            } else {
                // If user is administrator, moderator or if is viewer and no waiting is required
                if( $bbbsession['administrator'] || $bbbsession['moderator'] || !$bbbsession['wait'] ) {
                    /// Prepare the metadata
                    $metadata = array("meta_origin" => $bbbsession['origin'],
                            "meta_originVersion" => $bbbsession['originVersion'],
                            "meta_originServerName" => $bbbsession['originServerName'],
                            "meta_originServerCommonName" => $bbbsession['originServerCommonName'],
                            "meta_originTag" => $bbbsession['originTag'],
                            "meta_context" => $bbbsession['context'],
                            "meta_recordingDescription" => $bbbsession['contextActivityDescription'],
                            "meta_recordingTags" => $bbbsession['contextActivityTags'],
                            "meta_recordingReadyURL" => $bbbsession['recordingReadyURL'],
                            "meta_canvas-recording-ready-url" => $bbbsession['recordingReadyURL'],
                            "meta_recording-ready-url" => $bbbsession['recordingReadyURL']
                    );

                    /// Set the duration for the meeting
                    if ( isset($CFG->bigbluebuttonbn_scheduled_duration_enabled) && $CFG->bigbluebuttonbn_scheduled_duration_enabled ) {
                        $durationtime = bigbluebuttonbn_get_duration($bigbluebuttonbn->openingtime, $bigbluebuttonbn->closingtime);
                        if( $durationtime > 0 )
                            $bbbsession['welcome'] .= '<br><br>'.str_replace("%duration%", ''.$durationtime, get_string('bbbdurationwarning', 'bigbluebuttonbn'));
                    } else {
                        $durationtime = 0;
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
                            $bbbsession['record']? 'true': 'false',
                            $durationtime,
                            $bbbsession['voicebridge'],
                            $metadata,
                            $bbbsession['presentation']['name'],
                            $bbbsession['presentation']['url']
                    );

                    if (!$response) {
                        // If the server is unreachable, then prompts the user of the necessary action
                        if ( $bbbsession['administrator'] ) {
                            print_error( 'view_error_unable_join', 'bigbluebuttonbn', $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn' );
                        } else if ( $bbbsession['moderator'] ) {
                            print_error( 'view_error_unable_join_teacher', 'bigbluebuttonbn', $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn' );
                        } else {
                            print_error( 'view_error_unable_join_student', 'bigbluebuttonbn', $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn' );
                        }

                    } else if( $response['returncode'] == "FAILED" ) {
                        // The meeting was not created
                        $error_key = bigbluebuttonbn_get_error_key( $response['messageKey'], 'view_error_create' );
                        if( !$error_key ) {
                            print_error( $response['message'], 'bigbluebuttonbn' );
                        } else {
                            print_error( $error_key, 'bigbluebuttonbn' );
                        }

                    } else if ($response['hasBeenForciblyEnded'] == "true"){
                        print_error( get_string( 'index_error_forciblyended', 'bigbluebuttonbn' ));

                    } else { ///////////////Everything is ok /////////////////////
                        /// Moodle event logger: Create an event for meeting created
                        bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_MEETING_CREATED, $bigbluebuttonbn, $context, $cm);
                        /// Internal logger: Instert a record with the meeting created
                        bigbluebuttonbn_log($bbbsession, 'Create');
                        //// Update the cache
                        $meeting_info = bigbluebuttonbn_bbb_broker_get_meeting_info($bbbsession['meetingid'], $bbbsession['modPW'], true);
                        //// Build the URL
                        if( $bbbsession['administrator'] || $bbbsession['moderator'] ) {
                            $password = $bbbsession['modPW'];
                        } else {
                            $password = $bbbsession['viewerPW'];
                        }
                        $join_url = bigbluebuttonbn_getJoinURL($bbbsession['meetingid'], $bbbsession['username'], $password, $bbbsession['shared_secret'], $bbbsession['endpoint'], $bbbsession['userID']);
                        /// Moodle event logger: Create an event for meeting joined
                        bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_MEETING_JOINED, $bigbluebuttonbn, $context, $cm);
                        /// Internal logger: Instert a record with the meeting created
                        bigbluebuttonbn_log($bbbsession, 'Join');
                        //// Before executing the redirect, increment the number of participants
                        bigbluebuttonbn_bbb_broker_participant_joined($bbbsession['meetingid'], ($bbbsession['administrator'] || $bbbsession['moderator']) );
                        //// Execute the redirect
                        header('Location: '.$join_url );
                    }                    

                } else {
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                }
            }
            break;
        default:
            bigbluebutton_bbb_view_close_window();
    }
}

////////////////// Local functions /////////////////////
function bigbluebutton_bbb_view_close_window() {
    global $OUTPUT, $PAGE;

    echo $OUTPUT->header();
    $PAGE->requires->js_init_call('M.mod_bigbluebuttonbn.view_windowClose');
    echo $OUTPUT->footer();
}