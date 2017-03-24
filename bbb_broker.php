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
 * Intermediator for managing actions executed by the BigBlueButton server.
 *
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2015-2017 Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

use \Firebase\JWT\JWT;

global $PAGE, $USER, $CFG, $SESSION, $DB;

$params['action'] = optional_param('action', '', PARAM_TEXT);
$params['callback'] = optional_param('callback', '', PARAM_TEXT);
$params['id'] = optional_param('id', '', PARAM_TEXT);
$params['idx'] = optional_param('idx', '', PARAM_TEXT);
$params['bigbluebuttonbn'] = optional_param('bigbluebuttonbn', 0, PARAM_INT);
$params['signed_parameters'] = optional_param('signed_parameters', '', PARAM_TEXT);

if (empty($params['action'])) {
    header('HTTP/1.0 400 Bad Request. Parameter ['.$params['action'].'] was not included');
    return;
}

$error = bigbluebuttonbn_broker_validate_parameters($params);
if (!empty($error)) {
    header('HTTP/1.0 400 Bad Request. '.$error);
    return;
}

if (isset($params['bigbluebuttonbn']) && $params['bigbluebuttonbn'] != 0) {
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $params['bigbluebuttonbn']), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $bigbluebuttonbn->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('bigbluebuttonbn', $bigbluebuttonbn->id, $course->id, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
}

if ($params['action'] != 'recording_ready' && $params['action'] != 'meeting_events') {
    if (!isset($SESSION->bigbluebuttonbn_bbbsession) || is_null($SESSION->bigbluebuttonbn_bbbsession)) {
        header('HTTP/1.0 400 Bad Request. No session variable set');
        return;
    }
    $bbbsession = $SESSION->bigbluebuttonbn_bbbsession;
}

if (!isloggedin() && $PAGE->course->id == SITEID) {
    $userid = guest_user()->id;
} else {
    $userid = $USER->id;
}
$hascourseaccess = ($PAGE->course->id == SITEID) || can_access_course($PAGE->course, $userid);

if (!$hascourseaccess) {
    header('HTTP/1.0 401 Unauthorized');
    return;
}

$instancetypeprofiles = bigbluebuttonbn_get_instance_type_profiles();
$features = $instancetypeprofiles[0]['features'];
if (isset($bbbsession['bigbluebuttonbn']->type)) {
    $features = $instancetypeprofiles[$bbbsession['bigbluebuttonbn']->type]['features'];
}
$showroom = (in_array('all', $features) || in_array('showroom', $features));
$showrecordings = (in_array('all', $features) || in_array('showrecordings', $features));
$importrecordings = (in_array('all', $features) || in_array('importrecordings', $features));

try {
    header('Content-Type: application/javascript; charset=utf-8');
    $a = strtolower($params['action']);
    if ($a == 'meeting_info') {
        $meetinginfo = bigbluebuttonbn_broker_meeting_info($bbbsession, $params);
        echo $meetinginfo;
        return;
    }

    if ($a == 'meeting_end') {
        $meetingend = bigbluebuttonbn_broker_meeting_end($bbbsession, $params, $bbbsession['bigbluebuttonbn'], $bbbsession['cm']);
        echo $meetingend;
        return;
    }

    if ($a == 'recording_links') {
        $recordinglinks = bigbluebuttonbn_broker_recording_links($bbbsession, $params);
        echo $recordinglinks;
        return;
    }

    if ($a == 'recording_info') {
        $recordinginfo = bigbluebuttonbn_broker_recording_info($bbbsession, $params, $showroom);
        echo $recordinginfo;
        return;
    }

    if ($a == 'recording_publish' || $a == 'recording_unpublish' || $a == 'recording_delete') {
        $recordingaction = bigbluebuttonbn_broker_recording_action($bbbsession, $params, $showroom,
                                                                   $bbbsession['bigbluebuttonbn'], $bbbsession['cm']);
        echo $recordingaction;
        return;
    }

    if ($a == 'recording_ready') {
        bigbluebuttonbn_broker_recording_ready($params, $bigbluebuttonbn);
        return;
    }

    if ($a == 'recording_import') {
        echo bigbluebuttonbn_broker_recording_import($bbbsession, $params);
        return;
    }

    if ($a == 'meeting_events') {
        bigbluebuttonbn_broker_meeting_events($params, $bigbluebuttonbn, $cm);
        return;
    }

} catch (Exception $e) {
    header('HTTP/1.0 500 Internal Server Error. '.$e->getMessage());
    return;
}

function bigbluebuttonbn_broker_meeting_info($bbbsession, $params) {

    $meetinginfo = bigbluebuttonbn_get_meeting_info($params['id']);

    $meetingrunning = false;
    if ($meetinginfo['returncode'] == 'SUCCESS') {
        $meetingrunning = bigbluebuttonbn_is_meeting_running($meetinginfo['meetingID']);
    }

    $statuscanjoin = '"can_join": false';
    $statuscanend = '"can_end": false';
    $statuscantag = '"can_tag": false';
    if ($meetingrunning) {
        $joinbuttontext = get_string('view_conference_action_join', 'bigbluebuttonbn');
        $initialmessage = get_string('view_error_userlimit_reached', 'bigbluebuttonbn');
        $canjoin = false;
        if ($bbbsession['userlimit'] == 0 || $meetinginfo->participantCount < $bbbsession['userlimit']) {
            $initialmessage = get_string('view_message_conference_in_progress', 'bigbluebuttonbn');
            $canjoin = true;
        }

        if ($bbbsession['administrator'] || $bbbsession['moderator']) {
            $endbuttontext = get_string('view_conference_action_end', 'bigbluebuttonbn');
            $statuscanend = '"can_end": true, "end_button_text": "'.$endbuttontext.'"';
        }
    } else {
        // If user is administrator, moderator or if is viewer and no waiting is required.
        $joinbuttontext = get_string('view_conference_action_join', 'bigbluebuttonbn');
        $initialmessage = get_string('view_message_conference_room_ready', 'bigbluebuttonbn');
        $canjoin = true;
        if (!$bbbsession['administrator'] && !$bbbsession['moderator'] && $bbbsession['wait']) {
            $initialmessage = get_string('view_message_conference_not_started', 'bigbluebuttonbn');
            if ($bbbsession['wait']) {
                $initialmessage .= ' '.get_string('view_message_conference_wait_for_moderator', 'bigbluebuttonbn');
            }
            $canjoin = false;
        }

        $cantag = false;
        if ($bbbsession['tagging'] && ($bbbsession['administrator'] || $bbbsession['moderator'])) {
            $cantag = true;
        }
        $statuscantag = '"can_tag": '.($cantag ? 'true' : 'false');
    }
    $statuscanjoin = '"can_join": '.($canjoin ? 'true' : 'false');
    return $params['callback'].'({"running": '.($meetingrunning ? 'true' : 'false').
                              ',"info": '.json_encode($meetinginfo).
                              ',"status": {'.'"join_url": "'.$bbbsession['joinURL'].'", '.
                                             '"join_button_text": "'.$joinbuttontext.'", '.
                                             '"message": "'.$initialmessage.'", '.
                                             $statuscanjoin.', '.
                                             $statuscanend.', '.
                                             $statuscantag.
                                          '}});';
}

function bigbluebuttonbn_broker_meeting_end($bbbsession, $params, $bigbluebuttonbn, $cm) {

    if (!$bbbsession['administrator'] && !$bbbsession['moderator']) {
        header('HTTP/1.0 401 Unauthorized. User not authorized to execute end command');
        return;
    }

    // Execute the end command.
    bigbluebuttonbn_end_meeting($params['id'], $bbbsession['modPW']);
    // Moodle event logger: Create an event for meeting ended.
    if (isset($bigbluebuttonbn)) {
        bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_MEETING_ENDED, $bigbluebuttonbn, $cm);
    }
    // Update the cache.
    bigbluebuttonbn_get_meetinginfo($params['id'], BIGBLUEBUTTONBN_FORCED);

    return $params['callback'].'({ "status": true });';
}

function bigbluebuttonbn_broker_recording_links($bbbsession, $params) {

    if (!$bbbsession['managerecordings']) {
        header('HTTP/1.0 401 Unauthorized. User not authorized to execute end command');
        return;
    }

    $out = $params['callback'].'({"status": "false"});';
    if (isset($params['id']) && $params['id'] != '') {
        $importedall = bigbluebuttonbn_get_recording_imported_instances($params['id']);
        $out = $params['callback'].'({ "status": "true", "links": '.count($importedall).'});';
    }
    return $out;
}

function bigbluebuttonbn_broker_recording_info($bbbsession, $params, $showroom) {

    if (!$bbbsession['managerecordings']) {
        header('HTTP/1.0 401 Unauthorized. User not authorized to execute end command');
        return;
    }

    // Retrieve the array of imported recordings.
    $bigbluebuttonbnid = null;
    if ($showroom) {
        $bigbluebuttonbnid = $bbbsession['bigbluebuttonbn']->id;
    }
    $recordings = bigbluebuttonbn_get_recordings($bbbsession['course']->id, $bigbluebuttonbnid, $showroom,
        $bbbsession['bigbluebuttonbn']->recordings_deleted_activities);
    if (isset($recordings[$params['id']])) {
        // Look up for an update on the imported recording.
        $recording = $recordings[$params['id']];
        $out = $params['callback'].'({ "status": "false" });';
        if (isset($recording) && !empty($recording) && !array_key_exists('messageKey', $recording)) {
            // The recording was found.
            $out = $params['callback'].'({ "status": "true", "published": "'.$recording['published'].'"});';
        }
        return $out;
    }

    // As the recordingid was not identified as imported recording link, look up for a real recording.
    $recording = bigbluebuttonbn_get_recordings_array($params['idx'], $params['id']);
    $out = $params['callback'].'({"status": "false"});';
    if (isset($recording) && !empty($recording) && array_key_exists($params['id'], $recording)) {
        // The recording was found.
        $out = $params['callback'].'({ "status": "true", "published": "'.$recording[$params['id']]['published'].'"});';
    }
    return $out;
}

function bigbluebuttonbn_broker_recording_action($bbbsession, $params, $showroom, $bigbluebuttonbn, $cm) {

    if (!$bbbsession['managerecordings']) {
        header('HTTP/1.0 401 Unauthorized. User not authorized to execute end command');
        return;
    }

    $callbackresponse = array();
    $callbackresponse['status'] = 'false';
    $eventlog = null;

    // Retrieve array of recordings that includes real and imported.
    $bigbluebuttonbnid = null;
    if ($showroom) {
        $bigbluebuttonbnid = $bbbsession['bigbluebuttonbn']->id;
    }
    $recordings = bigbluebuttonbn_get_recordings($bbbsession['course']->id, $bigbluebuttonbnid, $showroom,
        $bbbsession['bigbluebuttonbn']->recordings_deleted_activities);

    // Excecute action.
    switch (strtolower($params['action'])) {
        case 'recording_publish':
            $callbackresponse = bigbluebuttonbn_broker_recording_action_publish($bbbsession, $params, $recordings);
            $eventlog = BIGBLUEBUTTON_EVENT_RECORDING_PUBLISHED;
            break;
        case 'recording_unpublish':
            $callbackresponse = bigbluebuttonbn_broker_recording_action_unpublish($bbbsession, $params, $recordings);
            $eventlog = BIGBLUEBUTTON_EVENT_RECORDING_UNPUBLISHED;
            break;
        case 'recording_delete':
            $callbackresponse = bigbluebuttonbn_broker_recording_action_delete($bbbsession, $params, $recordings);
            $eventlog = BIGBLUEBUTTON_EVENT_RECORDING_DELETED;
            break;
    }

    if (isset($bigbluebuttonbn) && $callbackresponse['status'] === 'true') {
        // Moodle event logger: Create an event for action performed on recording.
        bigbluebuttonbn_event_log($eventlog, $bigbluebuttonbn, $cm);
    }

    $callbackresponsedata = json_encode($callbackresponse);
    return "{$params['callback']}({$callbackresponsedata});";
}

function bigbluebuttonbn_broker_recording_action_publish($bbbsession, $params, $recordings) {

    $status = 'true';
    if (isset($recordings[$params['id']]) && isset($recordings[$params['id']]['imported'])) {
        // Execute publish on imported recording link, if the real recording is published.
        $realrecordings = bigbluebuttonbn_get_recordings_array($recordings[$params['id']]['meetingID'],
                                                             $recordings[$params['id']]['recordID']);
        $status = $realrecordings[$params['id']]['published'];
        if ($status === 'true') {
            // Only if the physical recording is published, execute publish on imported recording link.
            bigbluebuttonbn_publish_recording_imported($params['id'], $bbbsession['bigbluebuttonbn']->id, true);
        }
    } else {
        // As the recordingid was not identified as imported recording link, execute publish on a real recording.
        bigbluebuttonbn_publish_recordings($params['id'], 'true');
    }

    $response = array('status' => $status);
    if ($status === 'false') {
        $response['message'] = get_string('view_recording_publish_link_error', 'bigbluebuttonbn');
    }
    return $response;
}

function bigbluebuttonbn_broker_recording_action_unpublish($bbbsession, $params, $recordings) {
    global $DB;

    if (isset($recordings[$params['id']]) && isset($recordings[$params['id']]['imported'])) {
        // Execute unpublish on imported recording link.
        bigbluebuttonbn_publish_recording_imported($params['id'], $bbbsession['bigbluebuttonbn']->id, false);
        return array('status' => 'true');
    }

    // As the recordingid was not identified as imported recording link, execute unpublish on a real recording.
    // First: Unpublish imported links associated to the recording.
    $importedall = bigbluebuttonbn_get_recording_imported_instances($params['id']);

    if ($importedall > 0) {
        foreach ($importedall as $key => $record) {
            $meta = json_decode($record->meta, true);
            // Prepare data for the update.
            $meta['recording']['published'] = 'false';
            $importedall[$key]->meta = json_encode($meta);

            // Proceed with the update.
            $DB->update_record('bigbluebuttonbn_logs', $importedall[$key]);
        }
    }
    // Second: Execute the real unpublish.
    bigbluebuttonbn_publish_recordings($params['id'], 'false');
    $response = array('status' => 'true');
    return $response;
}

function bigbluebuttonbn_broker_recording_action_delete($bbbsession, $params, $recordings) {
    global $DB;

    if (isset($recordings[$params['id']]) && isset($recordings[$params['id']]['imported'])) {
        // Execute delete on imported recording link.
        bigbluebuttonbn_delete_recording_imported($params['id'], $bbbsession['bigbluebuttonbn']->id);
        return array('status' => 'true');
    }

    // As the recordingid was not identified as imported recording link, execute delete on a real recording.
    // First: Delete imported links associated to the recording.
    $importedall = bigbluebuttonbn_get_recording_imported_instances($params['id']);

    if ($importedall > 0) {
        foreach (array_keys($importedall) as $key) {
            // Execute delete.
            $DB->delete_records('bigbluebuttonbn_logs', array('id' => $key));
        }
    }
    // Second: Execute the real delete.
    //bigbluebuttonbn_delete_recordings($params['id']);

    $response = array('status' => 'true');
    return $response;
}

function bigbluebuttonbn_broker_recording_ready($params, $bigbluebuttonbn) {

    // Decodes the received JWT string.
    try {
        $decodedparameters = JWT::decode($params['signed_parameters'], bigbluebuttonbn_get_cfg_shared_secret(),
            array('HS256'));
    } catch (Exception $e) {
        $error = 'Caught exception: '.$e->getMessage();
        header('HTTP/1.0 400 Bad Request. '.$error);
        return;
    }

    // Validate that the bigbluebuttonbn activity corresponds to the meeting_id received.
    $meetingidelements = explode('[', $decodedparameters->meeting_id);
    $meetingidelements = explode('-', $meetingidelements[0]);

    if (!isset($bigbluebuttonbn) || $bigbluebuttonbn->meetingid != $meetingidelements[0]) {
        $error = 'Caught exception: '.$e->getMessage();
        header('HTTP/1.0 410 Gone. '.$error);
        return;
    }

    // Sends the messages.
    try {
        bigbluebuttonbn_send_notification_recording_ready($bigbluebuttonbn);
        header('HTTP/1.0 202 Accepted');
    } catch (Exception $e) {
        $error = 'Caught exception: '.$e->getMessage();
        header('HTTP/1.0 503 Service Unavailable. '.$error);
    }
}

function bigbluebuttonbn_broker_recording_import($bbbsession, $params) {
    global $SESSION;

    if (!$bbbsession['managerecordings']) {
        header('HTTP/1.0 401 Unauthorized. User not authorized to execute end command');
        return;
    }

    $importrecordings = $SESSION->bigbluebuttonbn_importrecordings;
    if (!isset($importrecordings[$params['id']])) {
        $error = "Recording {$params['id']} could not be found. It can not be imported";
        header('HTTP/1.0 404 Not found. '.$error);
        return;
    }

    $importrecordings[$params['id']]['imported'] = true;
    $overrides['meetingid'] = $importrecordings[$params['id']]['meetingID'];
    $meta = '{"recording":'.json_encode($importrecordings[$params['id']]).'}';
    bigbluebuttonbn_logs($bbbsession, BIGBLUEBUTTONBN_LOG_EVENT_IMPORT, $overrides, $meta);
    // Moodle event logger: Create an event for recording imported.
    if (isset($bbbsession['bigbluebutton']) && isset($bbbsession['cm'])) {
        bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_RECORDING_IMPORTED, $bbbsession['bigbluebuttonbn'], $bbbsession['cm']);
    }

    $callbackresponse['status'] = 'true';
    $callbackresponsedata = json_encode($callbackresponse);
    return "{$params['callback']}({$callbackresponsedata});";
}

function bigbluebuttonbn_broker_meeting_events($params, $bigbluebuttonbn, $cm) {
    // Decodes the received JWT string.
    try {
        $decodedparameters = JWT::decode($params['signed_parameters'], bigbluebuttonbn_get_cfg_shared_secret(),
            array('HS256'));
    } catch (Exception $e) {
        $error = 'Caught exception: '.$e->getMessage();
        header('HTTP/1.0 400 Bad Request. '.$error);
        return;
    }

    // Validate that the bigbluebuttonbn activity corresponds to the meeting_id received.
    $meetingidelements = explode('[', $decodedparameters->meeting_id);
    $meetingidelements = explode('-', $meetingidelements[0]);

    if (!isset($bigbluebuttonbn) || $bigbluebuttonbn->meetingid != $meetingidelements[0]) {
        $error = 'Caught exception: '.$e->getMessage();
        header('HTTP/1.0 410 Gone. '.$error);
        return;
    }

    // Store the events.
    try {
        foreach ($decodedparameters->events as $event) {
            bigbluebuttonbn_meeting_event_log($event, $bigbluebuttonbn, $cm);
        }
        header('HTTP/1.0 202 Accepted');
    } catch (Exception $e) {
        $error = "Caught exception: {$e->getMessage()}";
        header("HTTP/1.0 503 Service Unavailable. {$error}");
    }
}

function bigbluebuttonbn_broker_validate_parameters($params) {

    $requiredparams = [
        'server_ping' => ['id' => 'The meetingID must be specified.'],
        'meeting_info' => ['id' => 'The meetingID must be specified.'],
        'meeting_end' => ['id' => 'The meetingID must be specified.'],
        'recording_info' => ['id' => 'The recordingID must be specified.'],
        'recording_links' => ['id' => 'The recordingID must be specified.'],
        'recording_publish' => ['id' => 'The recordingID must be specified.'],
        'recording_unpublish' => ['id' => 'The recordingID must be specified.'],
        'recording_delete' => ['id' => 'The recordingID must be specified.'],
        'recording_import' => ['id' => 'The recordingID must be specified.'],
        'recording_ready' => [
            'signed_parameters' => 'A JWT encoded string must be included as [signed_parameters].'
          ],
        'meeting_events' => [
            'signed_parameters' => 'A JWT encoded string must be included as [signed_parameters].'
          ]
      ];

    if (!isset($params['callback'])) {
        return 'This call must include a javascript callback.';
    }

    if (!isset($params['action'])) {
        return 'Action parameter must be included.';
    }

    $action = strtolower($params['action']);
    if (!array_key_exists($action, $requiredparams)) {
        return 'Action '.$params['action'].' can not be performed.';
    }

    foreach ($requiredparams[$action] as $param => $message) {
        if (!array_key_exists($param, $params) || empty($params[$param])) {
            return $message;
        }
    }
}
