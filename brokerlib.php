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
 * Broker helper methods.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 * @author    Darko Miletic  (darko.miletic [at] gmail [dt] com)
 */

defined('MOODLE_INTERNAL') || die();

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
        bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events['meeting_end'], $bbbsession['bigbluebuttonbn']);
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
    // Excecute action.
    $callbackresponse = bigbluebuttonbn_broker_recording_action_perform($action, $params, $recordings);
    if ($callbackresponse['status']) {
        // Moodle event logger: Create an event for action performed on recording.
        bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events[$action], $bbbsession['bigbluebuttonbn'],
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
        $decodedparameters = \Firebase\JWT\JWT::decode($params['signed_parameters'],
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
        // We make sure messages are sent only once.
        if (bigbluebuttonbn_get_count_callback_event_log($decodedparameters->record_id) == 0) {
            bigbluebuttonbn_send_notification_recording_ready($bigbluebuttonbn);
        }
        $overrides = array('meetingid' => $decodedparameters->meeting_id);
        $meta = '{"recordid":'.$decodedparameters->record_id.'}';
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
        bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events['recording_import'], $bbbsession['bigbluebuttonbn'],
            ['other' => $params['id']]);
    }
    $callbackresponsedata = json_encode($callbackresponse);
    return "{$params['callback']}({$callbackresponsedata});";
}

/**
 * Helper for responding when storing live session events is requested.
 *
 * @param array $params
 * @param object $bigbluebuttonbn
 *
 * @return void
 */
function bigbluebuttonbn_broker_live_session_events($params, $bigbluebuttonbn) {
    // Decodes the received JWT string.
    try {
        $decodedparameters = \Firebase\JWT\JWT::decode($params['signed_parameters'],
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
            bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events['live_session'], $bigbluebuttonbn,
                ['timecreated' => $event->timestamp, 'userid' => $event->user, 'other' => $event->event]);
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
    $params['server_ping'] = ['id' => 'The meetingID must be specified.'];
    $params['meeting_info'] = ['id' => 'The meetingID must be specified.'];
    $params['meeting_end'] = ['id' => 'The meetingID must be specified.'];
    $params['recording_play'] = ['id' => 'The recordingID must be specified.'];
    $params['recording_info'] = ['id' => 'The recordingID must be specified.'];
    $params['recording_links'] = ['id' => 'The recordingID must be specified.'];
    $params['recording_publish'] = ['id' => 'The recordingID must be specified.'];
    $params['recording_unpublish'] = ['id' => 'The recordingID must be specified.'];
    $params['recording_delete'] = ['id' => 'The recordingID must be specified.'];
    $params['recording_protect'] = ['id' => 'The recordingID must be specified.'];
    $params['recording_unprotect'] = ['id' => 'The recordingID must be specified.'];
    $params['recording_edit'] = ['id' => 'The recordingID must be specified.',
        'meta' => 'A meta parameter should be included'];
    $params['recording_import'] = ['id' => 'The recordingID must be specified.'];
    $params['recording_ready'] = [
        'signed_parameters' => 'A JWT encoded string must be included as [signed_parameters].'
    ];
    $params['live_session_events'] = [
        'signed_parameters' => 'A JWT encoded string must be included as [signed_parameters].'
    ];
    return $params;
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
