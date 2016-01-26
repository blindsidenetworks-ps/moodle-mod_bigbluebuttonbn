<?php
/**
 * Intermediator for managing actions executed by the BigBlueButton server
 *
 * @package   mod_bigbluebuttonbn
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

global $PAGE, $USER, $CFG, $SESSION, $DB;

$params['action']  = optional_param('action', '', PARAM_TEXT);
$params['callback'] = optional_param('callback', '', PARAM_TEXT);
$params['id'] = optional_param('id', '', PARAM_TEXT);
$params['idx'] = optional_param('idx', '', PARAM_TEXT);
$params['bigbluebuttonbn'] = optional_param('bigbluebuttonbn', 0, PARAM_INT);
$params['signed_parameters'] = optional_param('signed_parameters', '', PARAM_TEXT);

$endpoint = bigbluebuttonbn_get_cfg_server_url();
$shared_secret = bigbluebuttonbn_get_cfg_shared_secret();

$error = '';

if( empty($params['action']) ) {
    $error = bigbluebuttonbn_bbb_broker_add_error($error, "Parameter [action] was not included");

} else {
    $error = bigbluebuttonbn_bbb_broker_validate_parameters($params);

    if( empty($error) && $params['action'] != "recording_ready" ) {

        if ($params['bigbluebuttonbn'] != 0) {
            $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $params['bigbluebuttonbn']), '*', MUST_EXIST);
            $course = $DB->get_record('course', array('id' => $bigbluebuttonbn->course), '*', MUST_EXIST);
            $cm = get_coursemodule_from_instance('bigbluebuttonbn', $bigbluebuttonbn->id, $course->id, false, MUST_EXIST);
            $context = bigbluebuttonbn_get_context_module($cm->id);
        }

        if ( isset($SESSION->bigbluebuttonbn_bbbsession) && !is_null($SESSION->bigbluebuttonbn_bbbsession) ) {
            $bbbsession = $SESSION->bigbluebuttonbn_bbbsession;
        } else {
            $error = bigbluebuttonbn_bbb_broker_add_error($error, "No session variable set");
        }
    }
}

header('Content-Type: application/javascript; charset=utf-8');
if ( empty($error) ) {

    if (!isloggedin() && $PAGE->course->id == SITEID) {
        $userid = guest_user()->id;
    } else {
        $userid = $USER->id;
    }
    $hascourseaccess = ($PAGE->course->id == SITEID) || can_access_course($PAGE->course, $userid);

    if( !$hascourseaccess ){
        header("HTTP/1.0 401 Unauthorized");
        return;
    } else {
        try {
            switch ( strtolower($params['action']) ){
                case 'meeting_info':
                    $meeting_info = bigbluebuttonbn_bbb_broker_get_meeting_info($params['id'], $bbbsession['modPW']);
                    $meeting_running = bigbluebuttonbn_bbb_broker_is_meeting_running($meeting_info); 

                    $status_can_end = '';
                    $status_can_tag = '';
                    if( $meeting_running ) {
                        $join_button_text = get_string('view_conference_action_join', 'bigbluebuttonbn');
                        if( $bbbsession['userlimit'] == 0 || $meeting_info->participantCount < $bbbsession['userlimit'] ) {
                            $initial_message = get_string('view_message_conference_in_progress', 'bigbluebuttonbn');
                            $can_join = true;

                        } else {
                            $initial_message = get_string('view_error_userlimit_reached', 'bigbluebuttonbn');
                            $can_join = false;
                        }

                        if( $bbbsession['administrator'] || $bbbsession['moderator'] ) {
                            $end_button_text = get_string('view_conference_action_end', 'bigbluebuttonbn');
                            $can_end = true;
                            $status_can_end = '"can_end": true, "end_button_text": "'.$end_button_text.'", ';
                        }

                    } else {
                        // If user is administrator, moderator or if is viewer and no waiting is required
                        if ( $bbbsession['administrator'] || $bbbsession['moderator'] || !$bbbsession['wait'] ) {
                            $initial_message = get_string('view_message_conference_room_ready', 'bigbluebuttonbn');
                            $join_button_text = get_string('view_conference_action_join', 'bigbluebuttonbn');
                            $can_join = true;

                        } else {
                            $initial_message = get_string('view_message_conference_not_started', 'bigbluebuttonbn');
                            if ( $bbbsession['wait'] ) {
                                $initial_message .= ' '.get_string('view_message_conference_wait_for_moderator', 'bigbluebuttonbn');
                            }
                            $join_button_text = get_string('view_conference_action_lineup', 'bigbluebuttonbn');
                            $can_join = false;
                        }

                        if( $bbbsession['tagging'] && ($bbbsession['administrator'] || $bbbsession['moderator']) ) {
                            $can_tag = true;

                        } else {
                            $can_tag = false;
                        }
                        $status_can_end = '"can_tag": '.($can_tag? 'true': 'false').', ';
                    }

                    echo $params['callback'].'({ "running": '.($meeting_running? 'true':'false').', "info": '.json_encode($meeting_info).', "status": {"can_join": '.($can_join? 'true':'false').',"join_url": "'.$bbbsession['joinURL'].'","join_button_text": "'.$join_button_text.'", '.$status_can_end.$status_can_tag.'"message": "'.$initial_message.'"} });';
                    break;
                case 'meeting_end':
                    if( $bbbsession['administrator'] || $bbbsession['moderator'] ) {
                        //Execute the end command
                        $meeting_info = bigbluebuttonbn_bbb_broker_do_end_meeting($params['id'], $bbbsession['modPW']);
                        // Moodle event logger: Create an event for meeting ended
                        if( isset($bigbluebuttonbn) )
                            bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_MEETING_ENDED, $bigbluebuttonbn, $context, $cm);
                        /// Update the cache
                        $meeting_info = bigbluebuttonbn_bbb_broker_get_meeting_info($params['id'], $bbbsession['modPW'], true);

                        echo $params['callback'].'({ "status": true });';
                    } else {
                        error_log("ERROR: User not authorized to execute end command");
                        header("HTTP/1.0 401 Unauthorized. User not authorized to execute end command");
                    }
                    break;
                case 'recording_list':
                    break;
                case 'recording_info':
                    $recording = bigbluebuttonbn_getRecordingArray($params['id'], $params['idx'], $endpoint, $shared_secret);
                    if ( isset($recording) && !empty($recording) && !array_key_exists('messageKey', $recording)) {  // The recording was found
                        echo $params['callback'].'({ "status": "true", "published": "'.$recording['published'].'"});';
                    } else {
                        echo $params['callback'].'({ "status": "false" });';
                    }
                    break;
                case 'recording_publish':
                    if( $bbbsession['managerecordings'] ) {
                        $meeting_info = bigbluebuttonbn_bbb_broker_do_publish_recording($params['id'], true);
                        // Moodle event logger: Create an event for recording published
                        if( isset($bigbluebuttonbn) ) {
                            bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_RECORDING_PUBLISHED, $bigbluebuttonbn, $context, $cm);
                        }
                    }
                    echo $params['callback'].'({ "status": "true" });';
                    break;
                case 'recording_unpublish':
                    if( $bbbsession['managerecordings'] ) {
                        $meeting_info = bigbluebuttonbn_bbb_broker_do_publish_recording($params['id'], false);
                        // Moodle event logger: Create an event for recording unpublished
                        if( isset($bigbluebuttonbn) ) {
                            bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_RECORDING_UNPUBLISHED, $bigbluebuttonbn, $context, $cm);
                        }
                    }
                    echo $params['callback'].'({ "status": "true" });';
                    break;
                case 'recording_delete':
                    if( $bbbsession['managerecordings'] ) {
                        $meeting_info = bigbluebuttonbn_bbb_broker_do_delete_recording($params['id']);
                        // Moodle event logger: Create an event for recording deleted
                        if( isset($bigbluebuttonbn) ) {
                            bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_RECORDING_DELETED, $bigbluebuttonbn, $context, $cm);
                        }
                    }
                    echo $params['callback'].'({ "status": "true" });';
                    break;
                case 'recording_ready':
                    //Decodes the received JWT string
                    try {
                        $decoded_parameters = JWT::decode($params['signed_parameters'], $shared_secret, array('HS256'));

                    } catch (Exception $e) {
                        $error = 'Caught exception: '.$e->getMessage();
                        error_log($error);
                        header("HTTP/1.0 400 Bad Request. ".$error);
                        return;
                    }

                    // Lookup the bigbluebuttonbn activity corresponding to the meeting_id received
                    try {
                        $meeting_id_elements = explode("[", $decoded_parameters->meeting_id);
                        $meeting_id_elements = explode("-", $meeting_id_elements[0]);
                        $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $meeting_id_elements[2]), '*', MUST_EXIST);

                    } catch (Exception $e) {
                        $error = 'Caught exception: '.$e->getMessage();
                        error_log($error);
                        header("HTTP/1.0 410 Gone. ".$error);
                        return;
                    }

                    // Sends the messages
                    try {
                        bigbluebuttonbn_send_notification_recording_ready($bigbluebuttonbn);
                        header("HTTP/1.0 202 Accepted");
                        return;
                    } catch (Exception $e) {
                        $error = 'Caught exception: '.$e->getMessage();
                        error_log($error);
                        header("HTTP/1.0 503 Service Unavailable. ".$error);
                        return;
                    }
                    break;
                case 'moodle_notify':
                    break;
                case 'moodle_event':
                    break;
            }

        } catch(Exception $e) {
            error_log("BBB_BROKER ERROR: ".$e->getCode().", ".$e->getMessage());
            header("HTTP/1.0 502 Bad Gateway. ".$e->getMessage());
            return;
        }
    }

} else {
    header("HTTP/1.0 400 Bad Request. ".$error);
    return;
}