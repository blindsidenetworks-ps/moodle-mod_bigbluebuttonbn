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
 * The broker routines
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\local;

use coding_exception;
use Exception;
use mod_bigbluebuttonbn\bigbluebutton\recordings\recording;
use mod_bigbluebuttonbn\bigbluebutton\recordings\recording_helper;
use mod_bigbluebuttonbn\bigbluebutton\recordings\recording_proxy;
use mod_bigbluebuttonbn\event\events;
use mod_bigbluebuttonbn\local\notifier;
use mod_bigbluebuttonbn\local\helpers\logs;
use mod_bigbluebuttonbn\local\helpers\meeting;

defined('MOODLE_INTERNAL') || die();
global $CFG;

/**
 * The broker routines
 *
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class broker {

    /**
     * Callback for meeting info.
     *
     * @param array $bbbsession
     * @param array $params
     * @param boolean $updatecache
     *
     * @return string
     */
    public static function meeting_info($bbbsession, $params, $updatecache) {
        $callbackresponse = array();
        $info = meeting::bigbluebuttonbn_get_meeting_info($params['id'], $updatecache);
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
        $canjoin =
            self::meeting_info_can_join($bbbsession, $running, $participantcount);
        $status["can_join"] = $canjoin["can_join"];
        $status["message"] = $canjoin["message"];
        $canend = self::meeting_info_can_end($bbbsession, $running);
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
    public static function meeting_info_can_join($bbbsession, $running, $participantcount) {
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
    public static function meeting_info_can_end($bbbsession, $running) {
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
    public static function meeting_end($bbbsession, $params) {
        if (!$bbbsession['administrator'] && !$bbbsession['moderator']) {
            header('HTTP/1.0 401 Unauthorized. User not authorized to execute end command');
            return;
        }
        // Execute the end command.
        meeting::bigbluebuttonbn_end_meeting($params['id'], $bbbsession['modPW']);
        // Moodle event logger: Create an event for meeting ended.
        if (isset($bbbsession['bigbluebuttonbn'])) {
            \mod_bigbluebuttonbn\local\helpers\logs::bigbluebuttonbn_event_log(events::$events['meeting_end'],
                $bbbsession['bigbluebuttonbn']);
        }
        // Update the cache.
        meeting::bigbluebuttonbn_get_meeting_info($params['id'], bbb_constants::BIGBLUEBUTTONBN_UPDATE_CACHE);
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
    public static function recording_links($bbbsession, $params) {
        if (!$bbbsession['managerecordings']) {
            header('HTTP/1.0 401 Unauthorized. User not authorized to execute update command');
            return;
        }
        $callbackresponse = array('status' => false);
        if (isset($params['id']) && $params['id'] != '') {
            $callbackresponse['status'] = true;
            $callbackresponse['links'] = recording::count_by(
                [
                    'recordingid' => $params['id'],
                    'imported' => true,
                ]
            );
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
    public static function recording_info($bbbsession, $params, $showroom) {
        if (!$bbbsession['managerecordings']) {
            header('HTTP/1.0 401 Unauthorized. User not authorized to execute command');
            return;
        }
        // Retrieve array of recordings that includes real and imported.
        $bigbluebuttonbn = $bbbsession['bigbluebuttonbn'];
        $callbackresponse = array('status' => true, 'found' => false);
        $bigbluebuttonbnid = null;
        if ($showroom) {
            $bigbluebuttonbnid = $bigbluebuttonbn->id;
        }
        $recordings = recording_helper::get_recordings(
            $bigbluebuttonbn->course,
            $bigbluebuttonbnid,
            $showroom,
            $bigbluebuttonbn->recordings_deleted,
            recording::INCLUDE_IMPORTED_RECORDINGS
        );

        if (array_key_exists($params['id'], $recordings)) {
            // Look up for an update on the imported recording.
            if (!array_key_exists('messageKey', $recordings[$params['id']])) {
                // The recording was found.
                $callbackresponse =
                    self::recording_info_current($recordings[$params['id']], $params);
            }
            $callbackresponsedata = json_encode($callbackresponse);
            return "{$params['callback']}({$callbackresponsedata});";
        }
        // As the recordingid was not identified as imported recording link, look up for a real recording.
        $recordings = recording_proxy::bigbluebutton_fetch_recordings($params['id']);
        if (array_key_exists($params['id'], $recordings)) {
            // The recording was found.
            $callbackresponse =
                self::recording_info_current($recordings[$params['id']], $params);
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
    public static function recording_info_current($recording, $params) {
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
    public static function recording_play($params) {
        $callbackresponse = array('status' => true, 'found' => false);
        $recordings = recording_proxy::fetch_recordings($params['id']);
        if (array_key_exists($params['id'], $recordings)) {
            // The recording was found.
            $callbackresponse = self::recording_info_current($recordings[$params['id']], $params);
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
    public static function recording_action($bbbsession, $params, $showroom) {
        if (!$bbbsession['managerecordings']) {
            header('HTTP/1.0 401 Unauthorized. User not authorized to execute command');
            return;
        }
        // Retrieve array of recordings that includes real and imported.
        $callbackresponse = array('status' => true, 'found' => false);
        $courseid = $bbbsession['course']->id;
        $bigbluebuttonbnid = null;
        if ($showroom) {
            $bigbluebuttonbnid = $bbbsession['bigbluebuttonbn']->id;
        }
        $includedeleted = $bbbsession['bigbluebuttonbn']->recordings_deleted;
        $handler = new handler($bbbsession['bigbluebuttonbn']);
        // Retrieve the array of imported recordings.
        $recordings = $handler->get_recordings(
            $courseid,
            $bigbluebuttonbnid,
            $showroom,
            $includedeleted,
            recording::INCLUDE_IMPORTED_RECORDINGS
        );

        $action = strtolower($params['action']);
        // Excecute action.
        $callbackresponse =
            self::recording_action_perform($action, $params, $recordings);
        if ($callbackresponse['status']) {
            // Moodle event logger: Create an event for action performed on recording.
            \mod_bigbluebuttonbn\local\helpers\logs::bigbluebuttonbn_event_log(
                events::$events[$action],
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
    public static function recording_action_perform($action, $params, $recordings) {
        if ($action == 'recording_publish') {
            return self::recording_action_publish($params, $recordings);
        }
        if ($action == 'recording_unpublish') {
            return self::recording_action_unpublish($params, $recordings);
        }
        if ($action == 'recording_edit') {
            return self::recording_action_edit($params, $recordings);
        }
        if ($action == 'recording_delete') {
            return self::recording_action_delete($params, $recordings);
        }
        if ($action == 'recording_protect') {
            return self::recording_action_protect($params, $recordings);
        }
        if ($action == 'recording_unprotect') {
            return self::recording_action_unprotect($params, $recordings);
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
    public static function recording_action_publish($params, $recordings) {
        if ($recordings[$params['id']]->imported) {
            /* Since the recording link is the one fetched from the BBB server, imported recordings can not be
             * unpublished. There is no need to do anything else.
             */
            return array('status' => 'This action can not be performed on imported links.');
        }
        // As the recordingid was not identified as imported recording link, execute publish on a real recording.
        return array(
            'status' => recording_proxy::bigbluebutton_publish_recordings(
                $params['id'],
                'true'
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
    public static function recording_action_unpublish($params, $recordings) {
        if ($recordings[$params['id']]->imported) {
            /* Since the recording link is the one fetched from the BBB server, imported recordings can not be
             * unpublished. There is no need to do anything else.
             */
            return array('status' => 'This action can not be performed on imported links.');
        }
        // As the recordingid was not identified as imported recording link, execute unpublish on a real recording.
        return array(
            'status' => recording_proxy::bigbluebutton_publish_recordings(
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
     * @throws \dml_exception
     */
    public static function recording_action_protect($params, $recordings) {
        if ($recordings[$params['id']]->imported) {
            /* Since the recording link is the one fetched from the BBB server, imported recordings can not be
             * protected. There is no need to do anything else.
             */
            return array('status' => 'This action can not be performed on imported links.');
        }
        // As the recordingid was not identified as imported recording link, execute protect on a real recording.
        return array(
            'status' => recording_proxy::bigbluebutton_update_recordings(
                $params['id'],
                array('protect' => 'true')
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
    public static function recording_action_unprotect($params, $recordings) {
        if ($recordings[$params['id']]->imported) {
            /* Since the recording link is the one fetched from the BBB server, imported recordings can not be
             * unprotected. There is no need to do anything else.
             */
            return array('status' => 'This action can not be performed on imported links.');
        }
        // As the recordingid was not identified as imported recording link, execute unprotect on a real recording.
        return array(
            'status' => recording_proxy::bigbluebutton_update_recordings(
                $params['id'],
                array('protect' => 'false')
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
    public static function recording_action_delete($params, $recordings) {
        if ($recordings[$params['id']]->imported) {
            // Execute delete on imported recording link.
            return array(
                'status' => recording::delete(
                    $recordings[$params['id']]->id
                )
            );
        }
        // As the recordingid was not identified as imported recording link, execute delete on a real recording.
        // Step 1, delete imported links associated to the recording.
        recording::delete_by(
            ['recordingid' => $recordings[$params['id']]->recordingid,
            'imported' => true]
        );
        // Step 2, perform the actual delete by sending the corresponding request to BBB.
        return array(
            'status' => recording_proxy::bigbluebutton_delete_recordings($params['id'])
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
    public static function recording_action_edit($params, $recordings) {
        if ($recordings[$params['id']]->imported) {
            // Execute update on imported recording link.
            return array(
                'status' => recording_proxy::bigbluebutton_update_recording_imported(
                    $recordings[$params['id']]['imported'],
                    json_decode($params['meta'], true)
                )
            );
        }

        // As the recordingid was not identified as imported recording link, execute update on a real recording.
        return array(
            'status' => recording_proxy::bigbluebutton_update_recordings(
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
    public static function recording_ready($params, $bigbluebuttonbn) {
        // Decodes the received JWT string.
        try {
            $decodedparameters = \Firebase\JWT\JWT::decode(
                $params['signed_parameters'],
                config::get('shared_secret'),
                array('HS256')
            );
        } catch (Exception $e) {
            $error = 'Caught exception: ' . $e->getMessage();
            header('HTTP/1.0 400 Bad Request. ' . $error);
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
                notifier::notify_recording_ready($bigbluebuttonbn);
                header('HTTP/1.0 202 Accepted');
                return;
            }
            // We make sure messages are sent only once.
            if (
                \mod_bigbluebuttonbn\local\helpers\logs::bigbluebuttonbn_get_count_callback_event_log(
                    $decodedparameters->record_id) == 0) {
                notifier::notify_recording_ready($bigbluebuttonbn);
            }
            $overrides = array('meetingid' => $decodedparameters->meeting_id);
            $meta['recordid'] = $decodedparameters->record_id;
            $meta['callback'] = 'recording_ready';
            logs::bigbluebuttonbn_log($bigbluebuttonbn, bbb_constants::BIGBLUEBUTTON_LOG_EVENT_CALLBACK, $overrides,
                json_encode($meta));
            header('HTTP/1.0 202 Accepted');
        } catch (Exception $e) {
            $error = 'Caught exception: ' . $e->getMessage();
            header('HTTP/1.0 503 Service Unavailable. ' . $error);
        }
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
    public static function meeting_events($bigbluebuttonbn) {
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
                config::get('shared_secret'),
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
        logs::bigbluebuttonbn_log($bigbluebuttonbn, bbb_constants::BIGBLUEBUTTON_LOG_EVENT_CALLBACK, $overrides,
            json_encode($meta));
        if (recording_proxy::bigbluebuttonbn_get_count_callback_event_log($meta['recordid'], 'meeting_events') == 1) {
            // Process the events.
            meeting::bigbluebuttonbn_process_meeting_events($bigbluebuttonbn, $jsonobj);
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
    public static function validate_parameters($params) {
        $action = strtolower($params['action']);
        $requiredparams = self::required_parameters();
        if (!array_key_exists($action, $requiredparams)) {
            return 'Action ' . $params['action'] . ' can not be performed.';
        }
        return self::validate_parameters_message($params, $requiredparams[$action]);
    }

    /**
     * Helper for responding after the parameters received are validated.
     *
     * @param array $params
     * @param array $requiredparams
     *
     * @return string
     */
    public static function validate_parameters_message($params, $requiredparams) {
        foreach ($requiredparams as $param => $message) {
            if (!array_key_exists($param, $params) || $params[$param] == '') {
                return $message;
            }
        }
    }

    /**
     * Helper for definig rules for validating required parameters.
     */
    public static function required_parameters() {
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
     * Helper for performing validation of completion.
     *
     * @param object $bigbluebuttonbn
     * @param array $params
     *
     * @return void
     */
    public static function completion_validate($bigbluebuttonbn, $params) {
        $context = \context_course::instance($bigbluebuttonbn->course);
        // Get list with all the users enrolled in the course.
        list($sort, $sqlparams) = users_order_by_sql('u');
        $users = get_enrolled_users($context, 'mod/bigbluebuttonbn:view', 0, 'u.*', $sort);
        foreach ($users as $user) {
            // Enqueue a task for processing the completion.
            \mod_bigbluebuttonbn\local\bigbluebutton::bigbluebuttonbn_enqueue_completion_update($bigbluebuttonbn, $user->id);
        }
        $callbackresponse['status'] = 200;
        $callbackresponsedata = json_encode($callbackresponse);
        return "{$params['callback']}({$callbackresponsedata});";
    }
}
