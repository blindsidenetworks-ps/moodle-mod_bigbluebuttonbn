<?php
/**
 * Ping the BigBlueButton server to see if the meeting is running
 *
 * @package   mod_bigbluebuttonbn
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

global $PAGE, $USER;

$params['callback'] = required_param('callback', PARAM_TEXT);
$params['action']  = required_param('action', PARAM_TEXT);
$params['id'] = required_param('id', PARAM_TEXT);
$params['bigbluebuttonbn'] = required_param('bigbluebuttonbn', PARAM_INT);

$error = '';

if ($params['bigbluebuttonbn']) {
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $params['bigbluebuttonbn']), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $bigbluebuttonbn->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('bigbluebuttonbn', $bigbluebuttonbn->id, $course->id, false, MUST_EXIST);

    if ( $CFG->version < '2013111800' ) {
        //This is valid before v2.6
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    } else {
        //This is valid after v2.6
        $context = context_module::instance($cm->id);
    }

    $error = bigbluebuttonbn_bbb_broker_add_error($error, "BigBlueButtonBN ID was not included");
}

$error = bigbluebuttonbn_bbb_broker_validate_parameters($params);

/*

//Execute actions if there is one and it is allowed
if( isset($action) && isset($recordingid) && ($bbbsession['administrator'] || $bbbsession['moderator']) ){
    if( $action == 'show' ) {
        bigbluebuttonbn_doPublishRecordings($recordingid, 'true', $bbbsession['endpoint'], $bbbsession['shared_secret']);
        bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_RECORDING_PUBLISHED, $bigbluebuttonbn, $context, $cm);

    } else if( $action == 'hide') {
        bigbluebuttonbn_doPublishRecordings($recordingid, 'false', $bbbsession['endpoint'], $bbbsession['shared_secret']);
        bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_RECORDING_UNPUBLISHED, $bigbluebuttonbn, $context, $cm);

    } else if( $action == 'delete') {
        bigbluebuttonbn_doDeleteRecordings($recordingid, $bbbsession['endpoint'], $bbbsession['shared_secret']);
        bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_RECORDING_DELETED, $bigbluebuttonbn, $context, $cm);
    }
}

 */

//error_log($error);
$bbbsession = $SESSION->bigbluebuttonbn_bbbsession;
if ( !isset($bbbsession) || is_null($bbbsession) ) {
    $error = bigbluebuttonbn_bbb_broker_add_error($error, "No session variable set");
}
//error_log($error);
header('Content-Type: application/json; charset=utf-8');
if ( empty($error) ) {

    if (!isloggedin() && $PAGE->course->id == SITEID) {
        $userid = guest_user()->id;
    } else {
        $userid = $USER->id;
    }
    $hascourseaccess = ($PAGE->course->id == SITEID) || can_access_course($PAGE->course, $userid);

    if( !$hascourseaccess ){
        header("HTTP/1.0 401 Unauthorized");
    } else {
        try {
            switch ( strtolower($params['action']) ){
                /*
                case 'ping':
                    $meeting_info = bigbluebuttonbn_bbb_broker_get_meeting_info($params['id'], $bbbsession['modPW']);
                    $meeting_running = bigbluebuttonbn_bbb_broker_is_meeting_running($meeting_info); 
                    echo $params['callback'].'({ "running": '.($meeting_running? 'true':'false').' });';

                    break;
                */
                case 'info':
                    $meeting_info = bigbluebuttonbn_bbb_broker_get_meeting_info($params['id'], $bbbsession['modPW']);
                    $meeting_running = bigbluebuttonbn_bbb_broker_is_meeting_running($meeting_info); 

                    $status_can_end = '';
                    if( $meeting_running ) {
                        $initial_message = get_string('view_message_conference_in_progress', 'bigbluebuttonbn');
                        $join_button_text = get_string('view_conference_action_join', 'bigbluebuttonbn');
                        $can_join = true;

                        if( $bbbsession['administrator'] || $bbbsession['moderator'] ) {
                            $end_button_text = get_string('view_conference_action_end', 'bigbluebuttonbn');
                            $can_end = true;
                            $status_can_end = '"can_end": '.($can_end? 'true': 'false').', "end_button_text": "'.$end_button_text.'", ';
                        }

                    } else {
                        // If user is administrator, moderator or if is viewer and no waiting is required
                        if( $bbbsession['administrator'] || $bbbsession['moderator'] || !$bbbsession['wait'] ) {
                            $initial_message = get_string('view_message_conference_room_ready', 'bigbluebuttonbn');
                            $join_button_text = get_string('view_conference_action_join', 'bigbluebuttonbn');
                            $can_join = true;

                        } else {
                            $initial_message = get_string('view_message_conference_not_started', 'bigbluebuttonbn');
                            $join_button_text = get_string('view_conference_action_lineup', 'bigbluebuttonbn');
                            $can_join = false;
                        }
                    }
                    echo $params['callback'].'({ "running": '.($meeting_running? 'true':'false').', "info": '.json_encode($meeting_info).', "status": {"can_join": '.($can_join? 'true':'false').',"join_url": "'.$bbbsession['joinURL'].'","join_button_text": "'.$join_button_text.'", '.$status_can_end.'"message": "'.$initial_message.'"} });';
                    break;
                case 'end':
                    error_log("Executing end meeting");
                    if( $bbbsession['administrator'] || $bbbsession['moderator'] ) {
                        //Execute the end command
                        $meeting_info = bigbluebuttonbn_bbb_broker_do_end_meeting($params['id'], $bbbsession['modPW']);

                        /// Update the cache
                        bigbluebuttonbn_bbb_broker_get_meeting_info($params['id'], $bbbsession['modPW'], true);

                        echo $params['callback'].'({ "status": true });';
                    } else {
                        error_log("ERROR: User not authorized to execute end command");
                        header("HTTP/1.0 401 Unauthorized. User not authorized to execute end command");
                    }
                    break;
                case 'recordings':
                    break;
                case 'publish':
                    error_log("Executing publish");
                    $meeting_info = bigbluebuttonbn_bbb_broker_do_publish_recording($params['id'], true);
                    bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_RECORDING_PUBLISHED, $bigbluebuttonbn, $context, $cm);
                    echo $params['callback'].'({ "status": "true" });';
                    break;
                case 'unpublish':
                    error_log("Executing unpublish");
                    $meeting_info = bigbluebuttonbn_bbb_broker_do_publish_recording($params['id'], false);
                    bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_RECORDING_UNPUBLISHED, $bigbluebuttonbn, $context, $cm);
                    echo $params['callback'].'({ "status": "true" });';
                    break;
                case 'delete':
                    error_log("Executing delete");
                    //$meeting_info = bigbluebuttonbn_bbb_broker_do_delete_recording($params['id']);
                    bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_RECORDING_DELETED, $bigbluebuttonbn, $context, $cm);
                    echo $params['callback'].'({ "status": "true" });';
                    break;
            }

        } catch(Exception $e) {
            error_log("ERROR: ".$e->getCode().", ".$e->getMessage());
            header("HTTP/1.0 502 Bad Gateway. ".$e->getMessage());
        }
    }

} else {
    header("HTTP/1.0 400 Bad Request. ".$error);
}
?>
