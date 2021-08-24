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
    $recordings = bigbluebuttonbn_get_allrecordings(
        $bbbsession['course']->id,
        $bigbluebuttonbnid,
        $showroom,
        $bbbsession['bigbluebuttonbn']->recordings_deleted
    );

    $action = strtolower($params['action']);
    // Excecute action.
    $callbackresponse = bigbluebuttonbn_broker_recording_action_perform($action, $params, $recordings);
    if ($callbackresponse['status']) {
        // Moodle event logger: Create an event for action performed on recording.
        bigbluebuttonbn_event_log(
            \mod_bigbluebuttonbn\event\events::$events[$action],
            $bbbsession['bigbluebuttonbn'],
            ['other' => $params['id']]
        );
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
            $recordings[$params['id']]['meetingID'],
            $recordings[$params['id']]['recordID']
        );
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
                $recordings[$params['id']]['imported'],
                true
            )
        );
    }
    // As the recordingid was not identified as imported recording link, execute actual publish.
    return array(
        'status' => bigbluebuttonbn_publish_recordings(
            $params['id'],
            'true'
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
            $recordings[$params['id']]['meetingID'],
            $recordings[$params['id']]['recordID']
        );
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
                $recordings[$params['id']]['imported'],
                false
            )
        );
    }
    // As the recordingid was not identified as imported recording link, execute actual uprotect.
    return array(
        'status' => bigbluebuttonbn_update_recordings(
            $params['id'],
            array('protect' => 'false')
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
                $recordings[$params['id']]['imported'],
                false
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
            $params['id'],
            'false'
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
                $recordings[$params['id']]['imported'],
                true
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
            $params['id'],
            array('protect' => 'true')
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
                $recordings[$params['id']]['imported'],
                json_decode($params['meta'], true)
            )
        );
    }

    // As the recordingid was not identified as imported recording link, execute update on a real recording.
    // (No need to update imported links as the update only affects the actual recording).
    // Execute update on actual recording.
    return array(
        'status' => bigbluebuttonbn_update_recordings(
            $params['id'],
            json_decode($params['meta'])
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
        $decodedparameters = \Firebase\JWT\JWT::decode(
            $params['signed_parameters'],
            \mod_bigbluebuttonbn\locallib\config::get('shared_secret'),
            array('HS256')
        );
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
        $meta['recordid'] = $decodedparameters->record_id;
        $meta['callback'] = 'recording_ready';
        bigbluebuttonbn_log($bigbluebuttonbn, BIGBLUEBUTTON_LOG_EVENT_CALLBACK, $overrides, json_encode($meta));
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
        bigbluebuttonbn_event_log(
            \mod_bigbluebuttonbn\event\events::$events['recording_import'],
            $bbbsession['bigbluebuttonbn'],
            ['other' => $params['id']]
        );
    }
    $callbackresponsedata = json_encode($callbackresponse);
    return "{$params['callback']}({$callbackresponsedata});";
}

/**
 * Helper for responding when storing live meeting events is requested.
 *
 * The callback with a POST request includes:
 *  - Authentication: Bearer <A JWT token containing {"exp":<TIMESTAMP>} encoded with HS512>
 *  - Content Type: application/json
 *  - Body: <A JSON Object>
 *
 * @param object $bigbluebuttonbn
 *
 * @return void
 */
function bigbluebuttonbn_broker_meeting_events($bigbluebuttonbn) {
    // Decodes the received JWT string.
    try {
        // Get the HTTP headers (getallheaders is a PHP function that may only work with Apache).
        $headers = getallheaders();

        // Pull the Bearer from the headers.
        if (!array_key_exists('Authorization', $headers)) {
            $msg = 'Authorization failed';
            header('HTTP/1.0 400 Bad Request. ' . $msg);
            return;
        }
        $authorization = explode(" ", $headers['Authorization']);

        // Verify the authenticity of the request.
        $token = \Firebase\JWT\JWT::decode(
            $authorization[1],
            \mod_bigbluebuttonbn\locallib\config::get('shared_secret'),
            array('HS512')
        );

        // Get JSON string from the body.
        $jsonstr = file_get_contents('php://input');

        // Convert JSON string to a JSON object.
        $jsonobj = json_decode($jsonstr);
    } catch (Exception $e) {
        $msg = 'Caught exception: ' . $e->getMessage();
        header('HTTP/1.0 400 Bad Request. ' . $msg);
        return;
    }

    // Validate that the bigbluebuttonbn activity corresponds to the meeting_id received.
    $meetingidelements = explode('[', $jsonobj->{'meeting_id'});
    $meetingidelements = explode('-', $meetingidelements[0]);
    if (!isset($bigbluebuttonbn) || $bigbluebuttonbn->meetingid != $meetingidelements[0]) {
        $msg = 'The activity may have been deleted';
        header('HTTP/1.0 410 Gone. ' . $msg);
        return;
    }

    // We make sure events are processed only once.
    $overrides = array('meetingid' => $jsonobj->{'meeting_id'});
    $meta['recordid'] = $jsonobj->{'internal_meeting_id'};
    $meta['callback'] = 'meeting_events';
    bigbluebuttonbn_log($bigbluebuttonbn, BIGBLUEBUTTON_LOG_EVENT_CALLBACK, $overrides, json_encode($meta));
    if (bigbluebuttonbn_get_count_callback_event_log($meta['recordid'], 'meeting_events') == 1) {
        // Process the events.
        bigbluebuttonbn_process_meeting_events($bigbluebuttonbn, $jsonobj);
        header('HTTP/1.0 200 Accepted. Enqueued.');
        return;
    }

    header('HTTP/1.0 202 Accepted. Already processed.');
}

/**
 * Helper for validating the parameters received.
 *
 * @param array $params
 *
 * @return string
 */
function bigbluebuttonbn_broker_validate_parameters($params) {
    $action = strtolower($params['action']);
    $requiredparams = bigbluebuttonbn_broker_required_parameters();
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
    $params['server_ping'] = [
        'callback' => 'This request must include a javascript callback.',
        'id' => 'The meetingID must be specified.'
    ];
    $params['meeting_info'] = [
        'callback' => 'This request must include a javascript callback.',
        'id' => 'The meetingID must be specified.'
    ];
    $params['meeting_end'] = [
        'callback' => 'This request must include a javascript callback.',
        'id' => 'The meetingID must be specified.'
    ];
    $params['recording_play'] = [
        'callback' => 'This request must include a javascript callback.',
        'id' => 'The recordingID must be specified.'
    ];
    $params['recording_info'] = [
        'callback' => 'This request must include a javascript callback.',
        'id' => 'The recordingID must be specified.'
    ];
    $params['recording_links'] = [
        'callback' => 'This request must include a javascript callback.',
        'id' => 'The recordingID must be specified.'
    ];
    $params['recording_publish'] = [
        'callback' => 'This request must include a javascript callback.',
        'id' => 'The recordingID must be specified.'
    ];
    $params['recording_unpublish'] = [
        'callback' => 'This request must include a javascript callback.',
        'id' => 'The recordingID must be specified.'
    ];
    $params['recording_delete'] = [
        'callback' => 'This request must include a javascript callback.',
        'id' => 'The recordingID must be specified.'
    ];
    $params['recording_protect'] = [
        'callback' => 'This request must include a javascript callback.',
        'id' => 'The recordingID must be specified.'
    ];
    $params['recording_unprotect'] = [
        'callback' => 'This request must include a javascript callback.',
        'id' => 'The recordingID must be specified.'
    ];
    $params['recording_edit'] = [
        'callback' => 'This request must include a javascript callback.',
        'id' => 'The recordingID must be specified.',
        'meta' => 'A meta parameter should be included'
    ];
    $params['recording_import'] = [
        'callback' => 'This request must include a javascript callback.',
        'id' => 'The recordingID must be specified.'
    ];
    $params['recording_ready'] = [
        'bigbluebuttonbn' => 'The BigBlueButtonBN instance ID must be specified.',
        'signed_parameters' => 'A JWT encoded string must be included as [signed_parameters].'
    ];
    $params['recording_list_table'] = [
        'id' => 'The Bigbluebutton activity id must be specified.',
        'callback' => 'This request must include a javascript callback.',
    ];
    $params['meeting_events'] = [
        'bigbluebuttonbn' => 'The BigBlueButtonBN instance ID must be specified.'
    ];
    $params['completion_validate'] = [
        'callback' => 'This request must include a javascript callback.',
        'bigbluebuttonbn' => 'The BigBlueButtonBN instance ID must be specified.'
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

/**
 * Helper for performing validation of completion.
 *
 * @param object $bigbluebuttonbn
 * @param array $params
 *
 * @return void
 */
function bigbluebuttonbn_broker_completion_validate($bigbluebuttonbn, $params) {
    $context = \context_course::instance($bigbluebuttonbn->course);
    // Get list with all the users enrolled in the course.
    list($sort, $sqlparams) = users_order_by_sql('u');
    $users = get_enrolled_users($context, 'mod/bigbluebuttonbn:view', 0, 'u.*', $sort);
    foreach ($users as $user) {
        // Enqueue a task for processing the completion.
        bigbluebuttonbn_enqueue_completion_update($bigbluebuttonbn, $user->id);
    }
    $callbackresponse['status'] = 200;
    $callbackresponsedata = json_encode($callbackresponse);
    return "{$params['callback']}({$callbackresponsedata});";
}

/**
 * Helper function builds the data used by the recording table.
 *
 * @param array $bbbsession
 * @param array $params
 * @param array $enabledfeatures
 *
 * @return array
 * @throws coding_exception
 */
function bigbluebuttonbn_broker_get_recording_data($bbbsession, $params, $enabledfeatures) {
    $tools = ['protect', 'publish', 'delete'];
    $recordings = bigbluebutton_get_recordings_for_table_view($bbbsession, $enabledfeatures);
    $tabledata = array();
    $typeprofiles = bigbluebuttonbn_get_instance_type_profiles();
    $tabledata['activity'] = bigbluebuttonbn_view_get_activity_status($bbbsession);
    $tabledata['ping_interval'] = (int) \mod_bigbluebuttonbn\locallib\config::get('waitformoderator_ping_interval') * 1000;
    $tabledata['locale'] = bigbluebuttonbn_get_localcode();
    $tabledata['profile_features'] = $typeprofiles[0]['features'];
    $tabledata['recordings_html'] = $bbbsession['bigbluebuttonbn']->recordings_html == '1';

    $data = array();
    // Build table content.
    if (isset($recordings) && !array_key_exists('messageKey', $recordings)) {
        // There are recordings for this meeting.
        foreach ($recordings as $recording) {
            $rowdata = bigbluebuttonbn_get_recording_data_row($bbbsession, $recording, $tools);
            if (!empty($rowdata)) {
                array_push($data, $rowdata);
            }
        }
    }

    $columns = array();
    // Initialize table headers.
    $columns[] = array('key' => 'playback', 'label' => get_string('view_recording_playback', 'bigbluebuttonbn'),
        'width' => '125px', 'allowHTML' => true); // Note: here a strange bug noted whilst changing the columns, ref CONTRIB.
    $columns[] = array('key' => 'recording', 'label' => get_string('view_recording_name', 'bigbluebuttonbn'),
        'width' => '125px', 'allowHTML' => true);
    $columns[] = array('key' => 'description', 'label' => get_string('view_recording_description', 'bigbluebuttonbn'),
        'sortable' => true, 'width' => '250px', 'allowHTML' => true);
    if (bigbluebuttonbn_get_recording_data_preview_enabled($bbbsession)) {
        $columns[] = array('key' => 'preview', 'label' => get_string('view_recording_preview', 'bigbluebuttonbn'),
            'width' => '250px', 'allowHTML' => true);
    }
    $columns[] = array('key' => 'date', 'label' => get_string('view_recording_date', 'bigbluebuttonbn'),
        'sortable' => true, 'width' => '225px', 'allowHTML' => true);
    $columns[] = array('key' => 'duration', 'label' => get_string('view_recording_duration', 'bigbluebuttonbn'),
        'width' => '50px');
    if ($bbbsession['managerecordings']) {
        $columns[] = array('key' => 'actionbar', 'label' => get_string('view_recording_actionbar', 'bigbluebuttonbn'),
            'width' => '120px', 'allowHTML' => true);
    }

    $tabledata['data'] = array(
        'columns' => $columns,
        'data' => $data
    );
    $callbackresponsedata = json_encode($tabledata);
    return "{$params['callback']}({$callbackresponsedata});";
}
