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
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2017 Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

use \Firebase\JWT\JWT;

global $PAGE, $USER, $CFG, $SESSION, $DB;

$params['action'] = required_param('action', PARAM_TEXT);
$params['callback'] = optional_param('callback', '', PARAM_TEXT);
$params['id'] = optional_param('id', '', PARAM_TEXT);
$params['idx'] = optional_param('idx', '', PARAM_TEXT);
$params['bigbluebuttonbn'] = optional_param('bigbluebuttonbn', 0, PARAM_INT);
$params['signed_parameters'] = optional_param('signed_parameters', '', PARAM_TEXT);
$params['updatecache'] = optional_param('updatecache', 'false', PARAM_TEXT);
$params['meta'] = optional_param('meta', '', PARAM_TEXT);

if (empty($params['action'])) {
    header('HTTP/1.0 400 Bad Request. Parameter ['.$params['action'].'] was not included');
    return;
}

$error = bigbluebuttonbn_broker_validate_parameters($params);
if (!empty($error)) {
    header('HTTP/1.0 400 Bad Request. '.$error);
    return;
}

if ($params['bigbluebuttonbn']) {
    $bbbbrokerinstance = bigbluebuttonbn_views_instance_bigbluebuttonbn($params['bigbluebuttonbn']);
    $cm = $bbbbrokerinstance['cm'];
    $bigbluebuttonbn = $bbbbrokerinstance['bigbluebuttonbn'];
    $context = context_module::instance($cm->id);
    $PAGE->set_context($context);
}

if ($params['action'] != 'recording_ready' && $params['action'] != 'live_session_events') {
    if (!isset($SESSION->bigbluebuttonbn_bbbsession) || is_null($SESSION->bigbluebuttonbn_bbbsession)) {
        header('HTTP/1.0 400 Bad Request. No session variable set');
        return;
    }
    $bbbsession = $SESSION->bigbluebuttonbn_bbbsession;
}

$userid = $USER->id;
if (!isloggedin() && $PAGE->course->id == SITEID) {
    $userid = guest_user()->id;
}
$hascourseaccess = ($PAGE->course->id == SITEID) || can_access_course($PAGE->course, $userid);

if (!$hascourseaccess) {
    header('HTTP/1.0 401 Unauthorized');
    return;
}

$type = null;
if (isset($bbbsession['bigbluebuttonbn']->type)) {
    $type = $bbbsession['bigbluebuttonbn']->type;
}

$typeprofiles = bigbluebuttonbn_get_instance_type_profiles();
$enabledfeatures = bigbluebuttonbn_get_enabled_features($typeprofiles, $type);
try {
    header('Content-Type: application/javascript; charset=utf-8');
    $a = strtolower($params['action']);
    if ($a == 'meeting_info') {
        $meetinginfo = bigbluebuttonbn_broker_meeting_info($bbbsession, $params, ($params['updatecache'] == 'true'));
        echo $meetinginfo;
        return;
    }
    if ($a == 'meeting_end') {
        $meetingend = bigbluebuttonbn_broker_meeting_end($bbbsession, $params);
        echo $meetingend;
        return;
    }
    if ($a == 'recording_play') {
        $recordingplay = bigbluebuttonbn_broker_recording_play($params);
        echo $recordingplay;
        return;
    }
    if ($a == 'recording_links') {
        $recordinglinks = bigbluebuttonbn_broker_recording_links($bbbsession, $params);
        echo $recordinglinks;
        return;
    }
    if ($a == 'recording_info') {
        $recordinginfo = bigbluebuttonbn_broker_recording_info($bbbsession, $params, $enabledfeatures['showroom']);
        echo $recordinginfo;
        return;
    }
    if ($a == 'recording_publish' || $a == 'recording_unpublish' ||
        $a == 'recording_delete' || $a == 'recording_edit' ||
        $a == 'recording_protect' || $a == 'recording_unprotect') {
        $recordingaction = bigbluebuttonbn_broker_recording_action($bbbsession, $params, $enabledfeatures['showroom']);
        echo $recordingaction;
        return;
    }
    if ($a == 'recording_import') {
        echo bigbluebuttonbn_broker_recording_import($bbbsession, $params);
        return;
    }
    if ($a == 'recording_ready') {
        bigbluebuttonbn_broker_recording_ready($params, $bigbluebuttonbn);
        return;
    }
    if ($a == 'live_session_events') {
        bigbluebuttonbn_broker_live_session_events($params, $bigbluebuttonbn, $cm);
        return;
    }
    header('HTTP/1.0 400 Bad request. The action '. $a . ' doesn\'t exist');
    return;

} catch (Exception $e) {
    header('HTTP/1.0 500 Internal Server Error. '.$e->getMessage());
    return;
}

/**
 * Callback for meeting info.
 *
 * @param array $bbbsession
 * @param array $params
 * @param boolean $updatecache
 *
 * @return string
 */
function bigbluebuttonbn_broker_meeting_info($bbbsession, $params, $updatecache) {
    $callbackresponse = array();
    $info = bigbluebuttonbn_get_meeting_info($params['id'], $updatecache);
    $callbackresponse['info'] = $info;
    $running = false;
    if ($info['returncode'] == 'SUCCESS') {
        $running = ($info['running'] === 'true');
    }
    $callbackresponse['running'] = $running;
    $status = array();
    $status["join_url"] = $bbbsession['joinURL'];
    $status["join_button_text"] = get_string('view_conference_action_join', 'bigbluebuttonbn');
    $status["end_button_text"] = get_string('view_conference_action_end', 'bigbluebuttonbn');
    $participantcount = 0;
    if (isset($info['participantCount'])) {
        $participantcount = $info['participantCount'];
    }
    $canjoin = bigbluebuttonbn_broker_meeting_info_can_join($bbbsession, $running, $participantcount);
    $status["can_join"] = $canjoin["can_join"];
    $status["message"] = $canjoin["message"];
    $canend = bigbluebuttonbn_broker_meeting_info_can_end($bbbsession, $running);
    $status["can_end"] = $canend["can_end"];
    $callbackresponse['status'] = $status;
    $callbackresponsedata = json_encode($callbackresponse);
    return "{$params['callback']}({$callbackresponsedata});";
}

/**
 * Helper for evaluating if meeting can be joined, it is used by meeting info callback.
 *
 * @param array $bbbsession
 * @param boolean $running
 * @param boolean $participantcount
 *
 * @return array
 */
function bigbluebuttonbn_broker_meeting_info_can_join($bbbsession, $running, $participantcount) {
    $status = array("can_join" => false);
    if ($running) {
        $status["message"] = get_string('view_error_userlimit_reached', 'bigbluebuttonbn');
        if ($bbbsession['userlimit'] == 0 || $participantcount < $bbbsession['userlimit']) {
            $status["message"] = get_string('view_message_conference_in_progress', 'bigbluebuttonbn');
            $status["can_join"] = true;
        }
        return $status;
    }
    // If user is administrator, moderator or if is viewer and no waiting is required.
    $status["message"] = get_string('view_message_conference_wait_for_moderator', 'bigbluebuttonbn');
    if ($bbbsession['administrator'] || $bbbsession['moderator'] || !$bbbsession['wait']) {
        $status["message"] = get_string('view_message_conference_room_ready', 'bigbluebuttonbn');
        $status["can_join"] = true;
    }
    return $status;
}

/**
 * Helper for evaluating if meeting can be ended, it is used by meeting info callback.
 *
 * @param array $bbbsession
 * @param boolean $running
 *
 * @return boolean
 */
function bigbluebuttonbn_broker_meeting_info_can_end($bbbsession, $running) {
    if ($running && ($bbbsession['administrator'] || $bbbsession['moderator'])) {
        return array("can_end" => true);
    }
    return array("can_end" => false);
}

/**
 * Callback for meeting end.
 *
 * @param array $bbbsession
 * @param array $params
 *
 * @return string
 */
function bigbluebuttonbn_broker_meeting_end($bbbsession, $params) {
    if (!$bbbsession['administrator'] && !$bbbsession['moderator']) {
        header('HTTP/1.0 401 Unauthorized. User not authorized to execute end command');
        return;
    }
    // Execute the end command.
    bigbluebuttonbn_end_meeting($params['id'], $bbbsession['modPW']);
    // Moodle event logger: Create an event for meeting ended.
    if (isset($bbbsession['bigbluebuttonbn'])) {
        bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_MEETING_ENDED, $bbbsession['bigbluebuttonbn'],
            $bbbsession['cm']);
    }
    // Update the cache.
    bigbluebuttonbn_get_meeting_info($params['id'], BIGBLUEBUTTONBN_UPDATE_CACHE);
    $callbackresponse = array('status' => true);
    $callbackresponsedata = json_encode($callbackresponse);
    return "{$params['callback']}({$callbackresponsedata});";
}

/**
 * Callback for recording links.
 *
 * @param array $bbbsession
 * @param array $params
 *
 * @return string
 */
function bigbluebuttonbn_broker_recording_links($bbbsession, $params) {
    if (!$bbbsession['managerecordings']) {
        header('HTTP/1.0 401 Unauthorized. User not authorized to execute update command');
        return;
    }
    $callbackresponse = array('status' => false);
    if (isset($params['id']) && $params['id'] != '') {
        $importedall = bigbluebuttonbn_get_recording_imported_instances($params['id']);
        $callbackresponse['status'] = true;
        $callbackresponse['links'] = count($importedall);
    }
    $callbackresponsedata = json_encode($callbackresponse);
    return "{$params['callback']}({$callbackresponsedata});";
}

/**
 * Callback for recording info.
 *
 * @param array $bbbsession
 * @param array $params
 * @param boolean $showroom
 *
 * @return string
 */
function bigbluebuttonbn_broker_recording_info($bbbsession, $params, $showroom) {
    if (!$bbbsession['managerecordings']) {
        header('HTTP/1.0 401 Unauthorized. User not authorized to execute command');
        return;
    }
    $callbackresponse = array('status' => true, 'found' => false);
    $courseid = $bbbsession['course']->id;
    $bigbluebuttonbnid = null;
    if ($showroom) {
        $bigbluebuttonbnid = $bbbsession['bigbluebuttonbn']->id;
    }
    $includedeleted = $bbbsession['bigbluebuttonbn']->recordings_deleted;
    // Retrieve the array of imported recordings.
    $recordings = bigbluebuttonbn_get_allrecordings($courseid, $bigbluebuttonbnid, $showroom, $includedeleted);
    if (array_key_exists($params['id'], $recordings)) {
        // Look up for an update on the imported recording.
        if (!array_key_exists('messageKey', $recordings[$params['id']])) {
            // The recording was found.
            $callbackresponse = bigbluebuttonbn_broker_recording_info_current($recordings[$params['id']], $params);
        }
        $callbackresponsedata = json_encode($callbackresponse);
        return "{$params['callback']}({$callbackresponsedata});";
    }
    // As the recordingid was not identified as imported recording link, look up for a real recording.
    $recordings = bigbluebuttonbn_get_recordings_array($params['idx'], $params['id']);
    if (array_key_exists($params['id'], $recordings)) {
        // The recording was found.
        $callbackresponse = bigbluebuttonbn_broker_recording_info_current($recordings[$params['id']], $params);
    }
    $callbackresponsedata = json_encode($callbackresponse);
    return "{$params['callback']}({$callbackresponsedata});";
}

/**
 * Data used as for the callback for recording info.
 *
 * @param array $recording
 * @param array $params
 *
 * @return string
 */
function bigbluebuttonbn_broker_recording_info_current($recording, $params) {
    $callbackresponse['status'] = true;
    $callbackresponse['found'] = true;
    $callbackresponse['published'] = (string) $recording['published'];
    if (!isset($params['meta']) || empty($params['meta'])) {
        return $callbackresponse;
    }
    $meta = json_decode($params['meta'], true);
    foreach (array_keys($meta) as $key) {
        $callbackresponse[$key] = '';
        if (isset($recording[$key])) {
            $callbackresponse[$key] = trim($recording[$key]);
        }
    }
    return $callbackresponse;
}

/**
 * Callback for recording play.
 *
 * @param array $params
 *
 * @return string
 */
function bigbluebuttonbn_broker_recording_play($params) {
    $callbackresponse = array('status' => true, 'found' => false);
    $recordings = bigbluebuttonbn_get_recordings_array($params['idx'], $params['id']);
    if (array_key_exists($params['id'], $recordings)) {
        // The recording was found.
        $callbackresponse = bigbluebuttonbn_broker_recording_info_current($recordings[$params['id']], $params);
    }
    $callbackresponsedata = json_encode($callbackresponse);
    return "{$params['callback']}({$callbackresponsedata});";
}

/**
 * Callback for recording action.
 * (publush/unpublish/protect/unprotect/edit/delete)
 *
 * @param array $bbbsession
 * @param array $params
 * @param boolean $showroom
 *
 * @return string
 */
function bigbluebuttonbn_broker_recording_action($bbbsession, $params, $showroom) {
    if (!$bbbsession['managerecordings']) {
        header('HTTP/1.0 401 Unauthorized. User not authorized to execute end command');
        return;
    }
    // Retrieve array of recordings that includes real and imported.
    $bigbluebuttonbnid = null;
    if ($showroom) {
        $bigbluebuttonbnid = $bbbsession['bigbluebuttonbn']->id;
    }
    $recordings = bigbluebuttonbn_get_allrecordings($bbbsession['course']->id, $bigbluebuttonbnid, $showroom,
        $bbbsession['bigbluebuttonbn']->recordings_deleted);

    $action = strtolower($params['action']);
    $events = bigbluebuttonbn_events_action();
    // Excecute action.
    $eventlog = $events[$action];
    $callbackresponse = bigbluebuttonbn_broker_recording_action_perform($action, $params, $recordings);
    if ($callbackresponse['status']) {
        // Moodle event logger: Create an event for action performed on recording.
        bigbluebuttonbn_event_log($eventlog, $bbbsession['bigbluebuttonbn'], $bbbsession['cm'],
            ['other' => $params['id']]);
    }
    $callbackresponsedata = json_encode($callbackresponse);
    return "{$params['callback']}({$callbackresponsedata});";
}

/**
 * Helper for performing actions on recordings.
 * (publush/unpublish/protect/unprotect/edit/delete)
 *
 * @param string $action
 * @param array $params
 * @param array $recordings
 *
 * @return array
 */
function bigbluebuttonbn_broker_recording_action_perform($action, $params, $recordings) {
    if ($action == 'recording_publish') {
        return bigbluebuttonbn_broker_recording_action_publish($params, $recordings);
    }
    if ($action == 'recording_unpublish') {
        return bigbluebuttonbn_broker_recording_action_unpublish($params, $recordings);
    }
    if ($action == 'recording_edit') {
        return bigbluebuttonbn_broker_recording_action_edit($params, $recordings);
    }
    if ($action == 'recording_delete') {
        return bigbluebuttonbn_broker_recording_action_delete($params, $recordings);
    }
    if ($action == 'recording_protect') {
        return bigbluebuttonbn_broker_recording_action_protect($params, $recordings);
    }
    if ($action == 'recording_unprotect') {
        return bigbluebuttonbn_broker_recording_action_unprotect($params, $recordings);
    }
}

/**
 * Helper for performing publish on recordings.
 *
 * @param array $params
 * @param array $recordings
 *
 * @return array
 */
function bigbluebuttonbn_broker_recording_action_publish($params, $recordings) {
    if (bigbluebuttonbn_broker_recording_is_imported($recordings, $params['id'])) {
        // Execute publish on imported recording link, if the real recording is published.
        $realrecordings = bigbluebuttonbn_get_recordings_array(
            $recordings[$params['id']]['meetingID'], $recordings[$params['id']]['recordID']);
        // Only if the physical recording exist and it is published, execute publish on imported recording link.
        if (!isset($realrecordings[$params['id']])) {
            return array(
                'status' => false,
                'message' => get_string('view_recording_publish_link_deleted', 'bigbluebuttonbn')
              );
        }
        if ($realrecordings[$params['id']]['published'] !== 'true') {
            return array(
                'status' => false,
                'message' => get_string('view_recording_publish_link_not_published', 'bigbluebuttonbn')
              );
        }
        return array(
            'status' => bigbluebuttonbn_publish_recording_imported(
                $recordings[$params['id']]['imported'], true
            )
          );
    }
    // As the recordingid was not identified as imported recording link, execute actual publish.
    return array(
        'status' => bigbluebuttonbn_publish_recordings(
            $params['id'], 'true'
        )
      );
}

/**
 * Helper for performing unprotect on recordings.
 *
 * @param array $params
 * @param array $recordings
 *
 * @return array
 */
function bigbluebuttonbn_broker_recording_action_unprotect($params, $recordings) {
    if (bigbluebuttonbn_broker_recording_is_imported($recordings, $params['id'])) {
        // Execute unprotect on imported recording link, if the real recording is unprotected.
        $realrecordings = bigbluebuttonbn_get_recordings_array(
            $recordings[$params['id']]['meetingID'], $recordings[$params['id']]['recordID']);
        // Only if the physical recording exist and it is published, execute unprotect on imported recording link.
        if (!isset($realrecordings[$params['id']])) {
            return array(
                'status' => false,
                'message' => get_string('view_recording_unprotect_link_deleted', 'bigbluebuttonbn')
              );
        }
        if ($realrecordings[$params['id']]['protected'] === 'true') {
            return array(
                'status' => false,
                'message' => get_string('view_recording_unprotect_link_not_unprotected', 'bigbluebuttonbn')
              );
        }
        return array(
            'status' => bigbluebuttonbn_protect_recording_imported(
                $recordings[$params['id']]['imported'], false
            )
          );
    }
    // As the recordingid was not identified as imported recording link, execute actual uprotect.
    return array(
        'status' => bigbluebuttonbn_update_recordings(
            $params['id'], array('protect' => 'false')
        )
      );
}

/**
 * Helper for performing unpublish on recordings.
 *
 * @param array $params
 * @param array $recordings
 *
 * @return array
 */
function bigbluebuttonbn_broker_recording_action_unpublish($params, $recordings) {
    global $DB;
    if (bigbluebuttonbn_broker_recording_is_imported($recordings, $params['id'])) {
        // Execute unpublish or protect on imported recording link.
        return array(
            'status' => bigbluebuttonbn_publish_recording_imported(
                $recordings[$params['id']]['imported'], false
            )
          );
    }
    // As the recordingid was not identified as imported recording link, execute unpublish on a real recording.
    // First: Unpublish imported links associated to the recording.
    $importedall = bigbluebuttonbn_get_recording_imported_instances($params['id']);
    foreach ($importedall as $key => $record) {
        $meta = json_decode($record->meta, true);
        // Prepare data for the update.
        $meta['recording']['published'] = 'false';
        $importedall[$key]->meta = json_encode($meta);
        // Proceed with the update.
        $DB->update_record('bigbluebuttonbn_logs', $importedall[$key]);
    }
    // Second: Execute the actual unpublish.
    return array(
        'status' => bigbluebuttonbn_publish_recordings(
            $params['id'], 'false'
        )
      );
}

/**
 * Helper for performing protect on recordings.
 *
 * @param array $params
 * @param array $recordings
 *
 * @return array
 */
function bigbluebuttonbn_broker_recording_action_protect($params, $recordings) {
    global $DB;
    if (bigbluebuttonbn_broker_recording_is_imported($recordings, $params['id'])) {
        // Execute unpublish or protect on imported recording link.
        return array(
            'status' => bigbluebuttonbn_protect_recording_imported(
                $recordings[$params['id']]['imported'], true
            )
          );
    }
    // As the recordingid was not identified as imported recording link, execute protect on a real recording.
    // First: Protect imported links associated to the recording.
    $importedall = bigbluebuttonbn_get_recording_imported_instances($params['id']);
    foreach ($importedall as $key => $record) {
        $meta = json_decode($record->meta, true);
        // Prepare data for the update.
        $meta['recording']['protected'] = 'true';
        $importedall[$key]->meta = json_encode($meta);
        // Proceed with the update.
        $DB->update_record('bigbluebuttonbn_logs', $importedall[$key]);
    }
    // Second: Execute the actual protect.
    return array(
        'status' => bigbluebuttonbn_update_recordings(
            $params['id'], array('protect' => 'true')
        )
      );
}

/**
 * Helper for performing delete on recordings.
 *
 * @param array $params
 * @param array $recordings
 *
 * @return array
 */
function bigbluebuttonbn_broker_recording_action_delete($params, $recordings) {
    global $DB;
    if (bigbluebuttonbn_broker_recording_is_imported($recordings, $params['id'])) {
        // Execute delete on imported recording link.
        return array(
            'status' => bigbluebuttonbn_delete_recording_imported(
                $recordings[$params['id']]['imported']
            )
          );
    }
    // As the recordingid was not identified as imported recording link, execute delete on a real recording.
    // First: Delete imported links associated to the recording.
    $importedall = bigbluebuttonbn_get_recording_imported_instances($params['id']);
    if ($importedall > 0) {
        foreach (array_keys($importedall) as $key) {
            // Execute delete on imported links.
            $DB->delete_records('bigbluebuttonbn_logs', array('id' => $key));
        }
    }
    // Second: Execute the actual delete.
    return array(
        'status' => bigbluebuttonbn_delete_recordings($params['id'])
      );
}

/**
 * Helper for performing edit on recordings.
 *
 * @param array $params
 * @param array $recordings
 *
 * @return array
 */
function bigbluebuttonbn_broker_recording_action_edit($params, $recordings) {
    if (bigbluebuttonbn_broker_recording_is_imported($recordings, $params['id'])) {
        // Execute update on imported recording link.
        return array(
            'status' => bigbluebuttonbn_update_recording_imported(
                $recordings[$params['id']]['imported'], json_decode($params['meta'], true)
            )
          );
    }

    // As the recordingid was not identified as imported recording link, execute update on a real recording.
    // (No need to update imported links as the update only affects the actual recording).
    // Execute update on actual recording.
    return array(
        'status' => bigbluebuttonbn_update_recordings(
            $params['id'], json_decode($params['meta'])
        )
      );
}

/**
 * Helper for responding when recording ready is performed.
 *
 * @param array $params
 * @param object $bigbluebuttonbn
 *
 * @return void
 */
function bigbluebuttonbn_broker_recording_ready($params, $bigbluebuttonbn) {
    // Decodes the received JWT string.
    try {
        $decodedparameters = JWT::decode($params['signed_parameters'],
            \mod_bigbluebuttonbn\locallib\config::get('shared_secret'), array('HS256'));
    } catch (Exception $e) {
        $error = 'Caught exception: '.$e->getMessage();
        header('HTTP/1.0 400 Bad Request. '.$error);
        return;
    }

    // Validate that the bigbluebuttonbn activity corresponds to the meeting_id received.
    $meetingidelements = explode('[', $decodedparameters->meeting_id);
    $meetingidelements = explode('-', $meetingidelements[0]);

    if (!isset($bigbluebuttonbn) || $bigbluebuttonbn->meetingid != $meetingidelements[0]) {
        header('HTTP/1.0 410 Gone. The activity may have been deleted');
        return;
    }
    // Sends the messages.
    try {
        // Workaround for CONTRIB-7438.
        // Proceed as before when no record_id is provided.
        if (!isset($decodedparameters->record_id)) {
            bigbluebuttonbn_send_notification_recording_ready($bigbluebuttonbn);
            header('HTTP/1.0 202 Accepted');
            return;
        }
        // We make sure messages are send only once.
        if (bigbluebuttonbn_get_count_callback_event_log($decodedparameters->record_id) == 0) {
            bigbluebuttonbn_send_notification_recording_ready($bigbluebuttonbn);
        }
        $overrides = array('meetingid' => $decodedparameters->meeting_id);
        $meta = '{"recordid":"'.$decodedparameters->record_id.'"}';
        bigbluebuttonbn_log($bigbluebuttonbn, BIGBLUEBUTTON_LOG_EVENT_CALLBACK, $overrides, $meta);
        header('HTTP/1.0 202 Accepted');
    } catch (Exception $e) {
        $error = 'Caught exception: '.$e->getMessage();
        header('HTTP/1.0 503 Service Unavailable. '.$error);
    }
}

/**
 * Helper for performing import on recordings.
 *
 * @param array $bbbsession
 * @param array $params
 *
 * @return string
 */
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
    $callbackresponse = array('status' => true);
    $importrecordings[$params['id']]['imported'] = true;
    $overrides = array('meetingid' => $importrecordings[$params['id']]['meetingID']);
    $meta = '{"recording":'.json_encode($importrecordings[$params['id']]).'}';
    bigbluebuttonbn_log($bbbsession['bigbluebuttonbn'], BIGBLUEBUTTONBN_LOG_EVENT_IMPORT, $overrides, $meta);
    // Moodle event logger: Create an event for recording imported.
    if (isset($bbbsession['bigbluebutton']) && isset($bbbsession['cm'])) {
        bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_RECORDING_IMPORTED, $bbbsession['bigbluebuttonbn'],
            $bbbsession['cm'], ['other' => $params['id']]);
    }
    $callbackresponsedata = json_encode($callbackresponse);
    return "{$params['callback']}({$callbackresponsedata});";
}

/**
 * Helper for responding when storing live session events is requested.
 *
 * @param array $params
 * @param object $bigbluebuttonbn
 * @param object $cm
 *
 * @return void
 */
function bigbluebuttonbn_broker_live_session_events($params, $bigbluebuttonbn, $cm) {
    // Decodes the received JWT string.
    try {
        $decodedparameters = JWT::decode($params['signed_parameters'],
            \mod_bigbluebuttonbn\locallib\config::get('shared_secret'), array('HS256'));
    } catch (Exception $e) {
        $error = 'Caught exception: '.$e->getMessage();
        header('HTTP/1.0 400 Bad Request. '.$error);
        return;
    }
    // Validate that the bigbluebuttonbn activity corresponds to the meeting_id received.
    $meetingidelements = explode('[', $decodedparameters->meeting_id);
    $meetingidelements = explode('-', $meetingidelements[0]);

    if (!isset($bigbluebuttonbn) || $bigbluebuttonbn->meetingid != $meetingidelements[0]) {
        header('HTTP/1.0 410 Gone. The activity may have been deleted');
        return;
    }
    // Store the events.
    try {
        foreach ($decodedparameters->events as $event) {
            $options = ['timecreated' => $event->timestamp, 'userid' => $event->user, 'other' => $event->event];
            bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_LIVE_SESSION, $bigbluebuttonbn, $cm, $options);
        }
        header('HTTP/1.0 202 Accepted');
    } catch (Exception $e) {
        $error = "Caught exception: {$e->getMessage()}";
        header("HTTP/1.0 503 Service Unavailable. {$error}");
    }
}

/**
 * Helper for validating the parameters received.
 *
 * @param array $params
 *
 * @return string
 */
function bigbluebuttonbn_broker_validate_parameters($params) {
    $requiredparams = bigbluebuttonbn_broker_required_parameters();
    $action = strtolower($params['action']);
    if (!array_key_exists($action, $requiredparams)) {
        return 'Action '.$params['action'].' can not be performed.';
    }
    return bigbluebuttonbn_broker_validate_parameters_message($params, $requiredparams[$action]);
}

/**
 * Helper for responding after the parameters received are validated.
 *
 * @param array $params
 * @param array $requiredparams
 *
 * @return string
 */
function bigbluebuttonbn_broker_validate_parameters_message($params, $requiredparams) {
    foreach ($requiredparams as $param => $message) {
        if (!array_key_exists($param, $params) || $params[$param] == '') {
            return $message;
        }
    }
}

/**
 * Helper for definig rules for validating required parameters.
 */
function bigbluebuttonbn_broker_required_parameters() {
    $params['server_ping'] = bigbluebuttonbn_broker_required_parameters_default('meetingID');
    $params['meeting_info'] = bigbluebuttonbn_broker_required_parameters_default('meetingID');
    $params['meeting_end'] = bigbluebuttonbn_broker_required_parameters_default('meetingID');
    $params['recording_play'] = bigbluebuttonbn_broker_required_parameters_default('recordingID');
    $params['recording_info'] = bigbluebuttonbn_broker_required_parameters_default('recordingID');
    $params['recording_links'] = bigbluebuttonbn_broker_required_parameters_default('recordingID');
    $params['recording_publish'] = bigbluebuttonbn_broker_required_parameters_default('recordingID');
    $params['recording_unpublish'] = bigbluebuttonbn_broker_required_parameters_default('recordingID');
    $params['recording_delete'] = bigbluebuttonbn_broker_required_parameters_default('recordingID');
    $params['recording_protect'] = bigbluebuttonbn_broker_required_parameters_default('recordingID');
    $params['recording_unprotect'] = bigbluebuttonbn_broker_required_parameters_default('recordingID');
    $params['recording_import'] = bigbluebuttonbn_broker_required_parameters_default('recordingID');
    $params['recording_edit'] = bigbluebuttonbn_broker_required_parameters_default('recordingID');
    $params['recording_edit']['meta'] = 'A meta parameter should be included';
    $params['recording_ready'] = [
            'bigbluebuttonbn' => 'An id for the bigbluebuttonbn instance should be included.',
            'signed_parameters' => 'A JWT encoded string must be included as [signed_parameters].'
          ];
    $params['live_session_events'] = [
            'bigbluebuttonbn' => 'An id for the bigbluebuttonbn instance should be included.',
            'signed_parameters' => 'A JWT encoded string must be included as [signed_parameters].'
          ];
    return $params;
}

/**
 * Helper for definig default rules for validating required parameters.
 *
 * @param string $id
 *
 * @return array
 */
function bigbluebuttonbn_broker_required_parameters_default($id) {
    return [
              'id' => "The {$id} must be specified.",
              'callback' => 'This call must include a javascript callback.'
           ];
}

/**
 * Helper for validating if a recording is an imported link or a real one.
 *
 * @param array $recordings
 * @param string $recordingid
 *
 * @return boolean
 */
function bigbluebuttonbn_broker_recording_is_imported($recordings, $recordingid) {
    return (isset($recordings[$recordingid]) && isset($recordings[$recordingid]['imported']));
}
