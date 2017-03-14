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
 * Internal library of functions for module BigBlueButtonBN.
 *
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2010-2017 Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die;

global $BIGBLUEBUTTONBN_CFG, $CFG;

require_once dirname(__FILE__).'/lib.php';

const BIGBLUEBUTTONBN_FORCED = true;

const BIGBLUEBUTTONBN_TYPE_ALL = 0;
const BIGBLUEBUTTONBN_TYPE_ROOM_ONLY = 1;
const BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY = 2;

const BIGBLUEBUTTONBN_ROLE_VIEWER = 'viewer';
const BIGBLUEBUTTONBN_ROLE_MODERATOR = 'moderator';
const BIGBLUEBUTTONBN_METHOD_GET = 'GET';
const BIGBLUEBUTTONBN_METHOD_POST = 'POST';

const BIGBLUEBUTTON_EVENT_ACTIVITY_VIEWED = 'activity_viewed';
const BIGBLUEBUTTON_EVENT_MEETING_CREATED = 'meeting_created';
const BIGBLUEBUTTON_EVENT_MEETING_ENDED = 'meeting_ended';
const BIGBLUEBUTTON_EVENT_MEETING_JOINED = 'meeting_joined';
const BIGBLUEBUTTON_EVENT_MEETING_LEFT = 'meeting_left';
const BIGBLUEBUTTON_EVENT_MEETING_EVENT = 'meeting_event';
const BIGBLUEBUTTON_EVENT_RECORDING_DELETED = 'recording_deleted';
const BIGBLUEBUTTON_EVENT_RECORDING_IMPORTED = 'recording_imported';
const BIGBLUEBUTTON_EVENT_RECORDING_PUBLISHED = 'recording_published';
const BIGBLUEBUTTON_EVENT_RECORDING_UNPUBLISHED = 'recording_unpublished';

function bigbluebuttonbn_logs(array $bbbsession, $event, array $overrides = [], $meta = null) {
    global $DB;

    $log = new stdClass();

    $log->courseid = isset($overrides['courseid']) ? $overrides['courseid'] : $bbbsession['course']->id;
    $log->bigbluebuttonbnid = isset($overrides['bigbluebuttonbnid']) ?
        $overrides['bigbluebuttonbnid'] : $bbbsession['bigbluebuttonbn']->id;
    $log->userid = isset($overrides['userid']) ? $overrides['userid'] : $bbbsession['userID'];
    $log->meetingid = isset($overrides['meetingid']) ? $overrides['meetingid'] : $bbbsession['meetingid'];
    $log->timecreated = isset($overrides['timecreated']) ? $overrides['timecreated'] : time();
    $log->log = $event;
    if (isset($meta)) {
        $log->meta = $meta;
    } elseif ($event == BIGBLUEBUTTONBN_LOG_EVENT_CREATE) {
        $log->meta = '{"record":'.($bbbsession['record'] ? 'true' : 'false').'}';
    }

    $DB->insert_record('bigbluebuttonbn_logs', $log);
}

//  BigBlueButton API Calls  //
function bigbluebuttonbn_getJoinURL($meetingID, $userName, $PW, $logoutURL, $configToken = null, $userId = null) {
    $data = ['meetingID' => $meetingID,
              'fullName' => $userName,
              'password' => $PW,
              'logoutURL' => $logoutURL,
            ];

    if (!is_null($configToken)) {
        $data['configToken'] = $configToken;
    }
    if (!is_null($userId)) {
        $data['userID'] = $userId;
    }

    return bigbluebuttonbn_bigbluebutton_action_url('join', $data);
}

function bigbluebuttonbn_getCreateMeetingURL($name, $meetingID, $attendeePW, $moderatorPW, $welcome,
    $logoutURL, $record = 'false', $duration = 0, $voiceBridge = 0, $maxParticipants = 0, $metadata = array()) {
    $data = ['meetingID' => $meetingID,
              'name' => $name,
              'attendeePW' => $attendeePW,
              'moderatorPW' => $moderatorPW,
              'logoutURL' => $logoutURL,
              'record' => $record,
            ];

    $voiceBridge = intval($voiceBridge);
    if ($voiceBridge > 0 && $voiceBridge < 79999) {
        $data['voiceBridge'] = $voiceBridge;
    }

    $duration = intval($duration);
    if ($duration > 0) {
        $data['duration'] = $duration;
    }

    $maxParticipants = intval($maxParticipants);
    if ($maxParticipants > 0) {
        $data['maxParticipants'] = $maxParticipants;
    }

    if (trim($welcome)) {
        $data['welcome'] = $welcome;
    }

    return bigbluebuttonbn_bigbluebutton_action_url('create', $data, $metadata);
}

function bigbluebuttonbn_getIsMeetingRunningURL($meetingID) {
    return bigbluebuttonbn_bigbluebutton_action_url('isMeetingRunning', ['meetingID' => $meetingID]);
}

function bigbluebuttonbn_getMeetingInfoURL($meetingID) {
    return bigbluebuttonbn_bigbluebutton_action_url('getMeetingInfo', ['meetingID' => $meetingID]);
}

function bigbluebuttonbn_getMeetingsURL() {
    return bigbluebuttonbn_bigbluebutton_action_url('getMeetings');
}

/**
 * @param string $meetingID
 * @param string $modPW
 */
function bigbluebuttonbn_getEndMeetingURL($meetingID, $modPW) {
    return bigbluebuttonbn_bigbluebutton_action_url('end', ['meetingID' => $meetingID, 'password' => $modPW]);
}

/**
 * @param string $meetingID
 */
function bigbluebuttonbn_getRecordingsURL($meetingID) {
    return bigbluebuttonbn_bigbluebutton_action_url('getRecordings', ['meetingID' => $meetingID]);
}

/**
 * @param string $recordID
 */
function bigbluebuttonbn_getDeleteRecordingsURL($recordID) {
    return bigbluebuttonbn_bigbluebutton_action_url('deleteRecordings', ['recordID' => $recordID]);
}

/**
 * @param string $recordID
 * @param string $publish
 */
function bigbluebuttonbn_getPublishRecordingsURL($recordID, $publish) {
    return bigbluebuttonbn_bigbluebutton_action_url('publishRecordings', ['recordID' => $recordID, 'publish' => $publish]);
}

/**
 * @param string $recordID
 * @param array  $metadata
 */
function bigbluebuttonbn_getUpdateRecordingsURL($recordID, $metadata = array()) {
    return bigbluebuttonbn_bigbluebutton_action_url('updateRecordings', ['recordID' => $recordID], $metadata);
}

function bigbluebuttonbn_getDefaultConfigXMLURL() {
    return bigbluebuttonbn_bigbluebutton_action_url('getDefaultConfigXML');
}

function bigbluebuttonbn_bigbluebutton_action_url($action, $data = array(), $metadata = array()) {
    $base_url = bigbluebuttonbn_get_cfg_server_url().'api/'.$action.'?';

    $params = '';

    foreach ($data as $key => $value) {
        $params .= '&'.$key.'='.urlencode($value);
    }

    foreach ($metadata as $key => $value) {
        $params .= '&'.'meta_'.$key.'='.urlencode($value);
    }

    return $base_url.$params.'&checksum='.sha1($action.$params.bigbluebuttonbn_get_cfg_shared_secret());
}

function bigbluebuttonbn_getCreateMeetingArray($meetingName, $meetingID, $welcomeString, $mPW, $aPW,
        $logoutURL, $record = 'false', $duration = 0, $voiceBridge = 0, $maxParticipants = 0,
        $metadata = array(), $presentation_name = null, $presentation_url = null) {

    $create_meeting_url = bigbluebuttonbn_getCreateMeetingURL($meetingName, $meetingID, $aPW, $mPW, $welcomeString, $logoutURL, $record, $duration, $voiceBridge, $maxParticipants, $metadata);
    $method = BIGBLUEBUTTONBN_METHOD_GET;
    $data = null;

    if (!is_null($presentation_name) && !is_null($presentation_url)) {
        $method = BIGBLUEBUTTONBN_METHOD_POST;
        $data = "<?xml version='1.0' encoding='UTF-8'?><modules><module name='presentation'><document url='".$presentation_url."' /></module></modules>";
    }

    $xml = bigbluebuttonbn_wrap_xml_load_file($create_meeting_url, $method, $data);

    if ($xml) {
        $response = array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
        if ($xml->meetingID) {
            $response += array('meetingID' => $xml->meetingID, 'attendeePW' => $xml->attendeePW, 'moderatorPW' => $xml->moderatorPW, 'hasBeenForciblyEnded' => $xml->hasBeenForciblyEnded);
        }

        return $response;
    }

    return null;
}

/**
 * @param string $meetingID
 */
function bigbluebuttonbn_getMeetingArray($meetingID) {
    $meetings = bigbluebuttonbn_getMeetingsArray();
    if ($meetings) {
        foreach ($meetings as $meeting) {
            if ($meeting['meetingID'] == $meetingID) {
                return $meeting;
            }
        }
    }

    return null;
}

function bigbluebuttonbn_getMeetingsArray() {
    $xml = bigbluebuttonbn_wrap_xml_load_file(bigbluebuttonbn_getMeetingsURL());

    if ($xml && $xml->returncode == 'SUCCESS' && empty($xml->messageKey)) {
        // Meetings were returned.
        $meetings = array();
        foreach ($xml->meetings->meeting as $meeting) {
            $meetings[] = array('meetingID' => $meeting->meetingID,
                                'moderatorPW' => $meeting->moderatorPW,
                                'attendeePW' => $meeting->attendeePW,
                                'hasBeenForciblyEnded' => $meeting->hasBeenForciblyEnded,
                                'running' => $meeting->running, );
        }

        return $meetings;
    }

    if ($xml) {
        // Either failutre or success without meetings.
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
    }

    // If the server is unreachable, then prompts the user of the necessary action.
    return null;
}

/**
 * @param string $meetingID
 */
function bigbluebuttonbn_getMeetingInfo($meetingID) {
    $xml = bigbluebuttonbn_wrap_xml_load_file(bigbluebuttonbn_getMeetingInfoURL($meetingID));

    return $xml;
}

/**
 * @param string $meetingID
 */
function bigbluebuttonbn_getMeetingInfoArray($meetingID) {
    $xml = bigbluebuttonbn_wrap_xml_load_file(bigbluebuttonbn_getMeetingInfoURL($meetingID));

    if ($xml && $xml->returncode == 'SUCCESS' && empty($xml->messageKey)) {
        // Meeting info was returned.
        return array('returncode' => $xml->returncode,
                     'meetingID' => $xml->meetingID,
                     'moderatorPW' => $xml->moderatorPW,
                     'attendeePW' => $xml->attendeePW,
                     'hasBeenForciblyEnded' => $xml->hasBeenForciblyEnded,
                     'running' => $xml->running,
                     'recording' => $xml->recording,
                     'startTime' => $xml->startTime,
                     'endTime' => $xml->endTime,
                     'participantCount' => $xml->participantCount,
                     'moderatorCount' => $xml->moderatorCount,
                     'attendees' => $xml->attendees,
                     'metadata' => $xml->metadata,
                   );
    }

    if ($xml) {
        // Either failutre or success without meeting info.
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
    }

    //I f the server is unreachable, then prompts the user of the necessary action.
    return null;
}

/**
 * helper function to retrieve recordings from a BigBlueButton server.
 *
 * @param string or array $meetingIDs   an array or string containing a list of meetingIDs "mid1,mid2,mid3" or array("mid1","mid2","mid3")
 * @param string or array $recordingIDs an array or string containing a list of $recordingIDs "rid1,rid2,rid3" or array("rid1","rid2","rid3") to be used as a filter
 *
 * @return associative array containing the actual recordings indexed by recordID, each recording is also a non sequential associative array itself
 */
function bigbluebuttonbn_getRecordingsArray($meetingIDs, $recordingIDs = null) {
    $recordings = array();

    $meetingIDsArray = $meetingIDs;
    if (!is_array($meetingIDs)) {
        $meetingIDsArray = explode(',', $meetingIDs);
    }

    // If $meetingIDsArray is not empty a paginated getRecordings request is executed.
    if (!empty($meetingIDsArray)) {
        $pages = floor(sizeof($meetingIDsArray) / 25) + 1;
        for ($page = 1; $page <= $pages; ++$page) {
            $mIDs = array_slice($meetingIDsArray, ($page - 1) * 25, 25);
            // Do getRecordings is executed using a method GET (supported by all versions of BBB).
            $xml = bigbluebuttonbn_wrap_xml_load_file(bigbluebuttonbn_getRecordingsURL(implode(',', $mIDs)));
            if ($xml && $xml->returncode == 'SUCCESS' && isset($xml->recordings)) {
                // If there were meetings already created.
                foreach ($xml->recordings->recording as $recording) {
                    $recording_array_value = bigbluebuttonbn_getRecordingArrayValue($recording);
                    $recordings[$recording_array_value['recordID']] = $recording_array_value;
                }
                uasort($recordings, 'bigbluebuttonbn_recordingBuildSorter');
            }
        }
    }

    // Filter recordings based on recordingIDs.
    if (!empty($recordings) && !is_null($recordingIDs)) {
        $recordingIDsArray = $recordingIDs;
        if (!is_array($recordingIDs)) {
            $recordingIDsArray = explode(',', $recordingIDs);
        }

        foreach ($recordings as $key => $recording) {
            if (!in_array($recording['recordID'], $recordingIDsArray)) {
                unset($recordings[$key]);
            }
        }
    }

    return $recordings;
}

/**
 * helper function to retrieve imported recordings from the Moodle database. The references are stored as events in bigbluebuttonbn_logs.
 *
 * @param string $courseID
 * @param string $bigbluebuttonbnID
 * @param bool   $subset
 *
 * @return associative array containing the imported recordings indexed by recordID, each recording is also a non sequential associative array itself that corresponds to the actual recording in BBB
 */
function bigbluebuttonbn_getRecordingsImportedArray($courseID, $bigbluebuttonbnID = null, $subset = true) {
    global $DB;

    $select = "courseid = '{$courseID}' AND bigbluebuttonbnid <> '{$bigbluebuttonbnID}' AND log = '".BIGBLUEBUTTONBN_LOG_EVENT_IMPORT."'";
    if ($bigbluebuttonbnID === null) {
        $select = "courseid = '{$courseID}' AND log = '".BIGBLUEBUTTONBN_LOG_EVENT_IMPORT."'";
    } elseif ($subset) {
        $select = "bigbluebuttonbnid = '{$bigbluebuttonbnID}' AND log = '".BIGBLUEBUTTONBN_LOG_EVENT_IMPORT."'";
    }
    $records_imported = $DB->get_records_select('bigbluebuttonbn_logs', $select);

    $recordings_imported = $records_imported;
    // Check if array is not sequential.
    if (!empty($records_imported) && array_keys($records_imported) !== range(0, count($records_imported) - 1)) {
        // The response contains a single record and needs to be converted to a sequential array format.
        $recordings_imported = array($records_imported);
    }

    $recordings_imported_array = array();
    foreach ($recordings_imported as $key => $recording_imported) {
        $meta = json_decode($recording_imported->meta, true);
        $recordings_imported_array[$meta['recording']['recordID']] = $meta['recording'];
    }

    return $recordings_imported_array;
}

function bigbluebuttonbn_getDefaultConfigXML() {
    $xml = bigbluebuttonbn_wrap_xml_load_file(bigbluebuttonbn_getDefaultConfigXMLURL());

    return $xml;
}

function bigbluebuttonbn_getDefaultConfigXMLArray() {
    $default_config_xml = bigbluebuttonbn_getDefaultConfigXML();
    $default_config_xml_array = (array) $default_config_xml;

    return $default_config_xml_array;
}

function bigbluebuttonbn_getRecordingArrayValue($recording) {
    // Add formats.
    $playbackArray = array();
    foreach ($recording->playback->format as $format) {
        $playbackArray[(string) $format->type] = array('type' => (string) $format->type, 'url' => (string) $format->url, 'length' => (string) $format->length);
        // Add preview per format when existing.
        if ($format->preview) {
            $imagesArray = array();
            foreach ($format->preview->images->image as $image) {
                $imageArray = array('url' => (string) $image);
                foreach ($image->attributes() as $attKey => $attValue) {
                    $imageArray[$attKey] = (string) $attValue;
                }
                array_push($imagesArray, $imageArray);
            }
            $playbackArray[(string) $format->type]['preview'] = $imagesArray;
        }
    }

    // Add the metadata to the recordings array.
    $metadataArray = array();
    $metadata = get_object_vars($recording->metadata);
    foreach ($metadata as $key => $value) {
        if (is_object($value)) {
            $value = '';
        }
        $metadataArray['meta_'.$key] = $value;
    }

    $recordingArrayValue = array('recordID' => (string) $recording->recordID, 'meetingID' => (string) $recording->meetingID, 'meetingName' => (string) $recording->name, 'published' => (string) $recording->published, 'startTime' => (string) $recording->startTime, 'endTime' => (string) $recording->endTime, 'playbacks' => $playbackArray) + $metadataArray;

    return $recordingArrayValue;
}

function bigbluebuttonbn_recordingBuildSorter($a, $b) {
    if ($a['startTime'] < $b['startTime']) {
        return -1;
    } elseif ($a['startTime'] == $b['startTime']) {
        return 0;
    }

    return 1;
}

/**
 * @param string $recordIDs
 */
function bigbluebuttonbn_doDeleteRecordings($recordIDs) {
    $ids = explode(',', $recordIDs);
    foreach ($ids as $id) {
        $xml = bigbluebuttonbn_wrap_xml_load_file(bigbluebuttonbn_getDeleteRecordingsURL($id));
        if ($xml && $xml->returncode != 'SUCCESS') {
            return false;
        }
    }

    return true;
}

/**
 * @param string $recordIDs
 * @param string $publish
 */
function bigbluebuttonbn_doPublishRecordings($recordIDs, $publish) {
    $ids = explode(',', $recordIDs);
    foreach ($ids as $id) {
        $xml = bigbluebuttonbn_wrap_xml_load_file(bigbluebuttonbn_getPublishRecordingsURL($id, $publish));
        if ($xml && $xml->returncode != 'SUCCESS') {
            return false;
        }
    }

    return true;
}

/**
 * @param string $meetingID
 * @param string $modPW
 */
function bigbluebuttonbn_doEndMeeting($meetingID, $modPW) {
    $xml = bigbluebuttonbn_wrap_xml_load_file(bigbluebuttonbn_getEndMeetingURL($meetingID, $modPW));

    if ($xml) {
        // If the xml packet returned failure it displays the message to the user.
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
    }

    // If the server is unreachable, then prompts the user of the necessary action.
    return null;
}

/**
 * @param string $meetingID
 */
function bigbluebuttonbn_isMeetingRunning($meetingID) {
    $xml = bigbluebuttonbn_wrap_xml_load_file(bigbluebuttonbn_getIsMeetingRunningURL($meetingID));
    if ($xml && $xml->returncode == 'SUCCESS') {
        return ($xml->running == 'true') ? true : false;
    }

    return false;
}

function bigbluebuttonbn_getServerVersion() {
    $xml = bigbluebuttonbn_wrap_xml_load_file(bigbluebuttonbn_get_cfg_server_url().'api');
    if ($xml && $xml->returncode == 'SUCCESS') {
        return $xml->version;
    }

    return null;
}

function bigbluebuttonbn_getMeetingXML($meetingID) {
    $xml = bigbluebuttonbn_wrap_xml_load_file(bigbluebuttonbn_getIsMeetingRunningURL($meetingID));
    if ($xml && $xml->returncode == 'SUCCESS') {
        return str_replace('</response>', '', str_replace("<?xml version=\"1.0\"?>\n<response>", '', $xml->asXML()));
    }

    return 'false';
}

/**
 * @param string $url
 * @param string $data
 */
function bigbluebuttonbn_wrap_xml_load_file($url, $method = BIGBLUEBUTTONBN_METHOD_GET,
    $data = null, $content_type = 'text/xml') {

    if (bigbluebuttonbn_debugdisplay()) {
        error_log('Request to: '.$url);
    }

    if (extension_loaded('curl')) {
        $response = bigbluebuttonbn_wrap_xml_load_file_curl_request($url, $method, $data, $content_type);

        if (!$response) {
            error_log('No response on wrap_simplexml_load_file');

            return null;
        }

        if (bigbluebuttonbn_debugdisplay()) {
            error_log('Response: '.$response);
        }

        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);

            return $xml;
        } catch (Exception $e) {
            libxml_use_internal_errors($previous);
            $error = 'Caught exception: '.$e->getMessage();
            error_log($error);

            return null;
        }
    }

    // Alternative request non CURL based.
    $previous = libxml_use_internal_errors(true);
    try {
        $response = simplexml_load_file($url, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
        if (bigbluebuttonbn_debugdisplay()) {
            error_log('Response processed: '.$response->asXML());
        }

        return $response;
    } catch (Exception $e) {
        $error = 'Caught exception: '.$e->getMessage();
        error_log($error);
        libxml_use_internal_errors($previous);

        return null;
    }
}

function bigbluebuttonbn_wrap_xml_load_file_curl_request($url, $method = BIGBLUEBUTTONBN_METHOD_GET,
    $data = null, $content_type = 'text/xml') {
    $c = new curl();
    $c->setopt(array('SSL_VERIFYPEER' => true));
    if ($method == BIGBLUEBUTTONBN_METHOD_POST) {
        if (is_null($data) || is_array($data)) {
            return $c->post($url);
        }

        $options = array();
        $options['CURLOPT_HTTPHEADER'] = array(
                 'Content-Type: '.$content_type,
                 'Content-Length: '.strlen($data),
                 'Content-Language: en-US',
               );

        return $c->post($url, $data, $options);
    }

    return $c->get($url);
}

function bigbluebuttonbn_get_user_roles($context, $userid) {
    global $DB;

    $user_roles = get_user_roles($context, $userid);
    if ($user_roles) {
        $where = '';
        foreach ($user_roles as $key => $value) {
            $where .= (empty($where) ? ' WHERE' : ' AND').' id='.$value->roleid;
        }
        $user_roles = $DB->get_records_sql('SELECT * FROM {role}'.$where);
    }

    return $user_roles;
}

function bigbluebuttonbn_get_guest_role() {
    $guest_role = get_guest_role();

    return array($guest_role->id => $guest_role);
}

function bigbluebuttonbn_get_roles(context $context = null) {
    $roles = role_get_names($context);
    $roles_array = array();
    foreach ($roles as $role) {
        $roles_array[$role->shortname] = $role->localname;
    }

    return $roles_array;
}

function bigbluebuttonbn_get_roles_select($roles = array()) {
    $roles_array = array();
    foreach ($roles as $key => $value) {
        $roles_array[] = array('id' => $key, 'name' => $value);
    }

    return $roles_array;
}

function bigbluebuttonbn_get_users_select($users) {
    $users_array = array();
    foreach ($users as $user) {
        $users_array[] = array('id' => $user->id, 'name' => fullname($user));
    }

    return $users_array;
}

function bigbluebuttonbn_get_participant_list($bigbluebuttonbn, $context = null) {
    if ($bigbluebuttonbn == null) {
        return bigbluebuttonbn_get_participant_list_default($context);
    }

    $participant_list_array = array();
    $participant_list = json_decode($bigbluebuttonbn->participants);
    foreach ($participant_list as $participant) {
        $participant_list_array[] = array('selectiontype' => $participant->selectiontype,
                                          'selectionid' => $participant->selectionid,
                                          'role' => $participant->role, );
    }

    return $participant_list_array;
}

function bigbluebuttonbn_get_participant_list_default($context) {
    global $USER;

    $participant_list_array = array();
    $participant_list_array[] = array('selectiontype' => 'all',
                                       'selectionid' => 'all',
                                       'role' => BIGBLUEBUTTONBN_ROLE_VIEWER, );

    $moderator_defaults = explode(',', bigbluebuttonbn_get_cfg_moderator_default());
    foreach ($moderator_defaults as $moderator_default) {
        if ($moderator_default == 'owner') {
            $users = get_enrolled_users($context);
            foreach ($users as $user) {
                if ($user->id == $USER->id) {
                    $participant_list_array[] = array('selectiontype' => 'user',
                                                       'selectionid' => $USER->id,
                                                       'role' => BIGBLUEBUTTONBN_ROLE_MODERATOR, );
                    break;
                }
            }
            continue;
        }

        $participant_list_array[] = array('selectiontype' => 'role',
                                          'selectionid' => $moderator_default,
                                          'role' => BIGBLUEBUTTONBN_ROLE_MODERATOR, );
    }

    return $participant_list_array;
}
function bigbluebuttonbn_get_participant_list_json($bigbluebuttonbnid = null) {
    return json_encode(bigbluebuttonbn_get_participant_list($bigbluebuttonbnid));
}

function bigbluebuttonbn_is_moderator($user, $roles, $participants) {
    $participant_list = json_decode($participants);

    // Iterate participant rules.
    foreach ($participant_list as $participant) {
        if ($participant->role == BIGBLUEBUTTONBN_ROLE_MODERATOR) {
            // Looks for all configuration.
            if ($participant->selectiontype == 'all') {
                return true;
            }
            // Looks for users.
            if ($participant->selectiontype == 'user' && $participant->selectionid == $user) {
                return true;
            }
            // Looks for roles
            if ($participant->selectiontype == 'role') {
                foreach ($roles as $role) {
                    if ($participant->selectionid == $role->shortname) {
                        return true;
                    }
                }
            }
        }
    }

    return false;
}

function bigbluebuttonbn_get_error_key($messageKey, $defaultKey = null) {
    $key = $defaultKey;
    if ($messageKey == 'checksumError') {
        $key = 'index_error_checksum';
    } elseif ($messageKey == 'maxConcurrent') {
        $key = 'view_error_max_concurrent';
    }

    return $key;
}

function bigbluebuttonbn_voicebridge_unique($voicebridge, $id = null) {
    global $DB;

    $is_unique = true;
    if ($voicebridge != 0) {
        $table = 'bigbluebuttonbn';
        $select = 'voicebridge = '.$voicebridge;
        if ($id) {
            $select .= ' AND id <> '.$id;
        }
        if ($DB->get_records_select($table, $select)) {
            $is_unique = false;
        }
    }

    return $is_unique;
}

function bigbluebuttonbn_get_duration($closingtime) {
    $duration = 0;
    $now = time();
    if ($closingtime > 0 && $now < $closingtime) {
        $duration = ceil(($closingtime - $now) / 60);
        $compensation_time = intval(bigbluebuttonbn_get_cfg_scheduled_duration_compensation());
        $duration = intval($duration) + $compensation_time;
    }

    return $duration;
}

function bigbluebuttonbn_get_presentation_array($context, $presentation, $id = null) {
    $presentation_name = null;
    $presentation_url = null;
    $presentation_icon = null;
    $presentation_mimetype_description = null;

    if (!empty($presentation)) {
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_bigbluebuttonbn', 'presentation', 0,
            'itemid, filepath, filename', false);
        if (count($files) >= 1) {
            $file = reset($files);
            unset($files);
            $presentation_name = $file->get_filename();
            $presentation_icon = file_file_icon($file, 24);
            $presentation_mimetype_description = get_mimetype_description($file);
            $presentation_nonce_value = null;

            if (!is_null($id)) {
                //Create the nonce component for granting a temporary public access
                $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn',
                    'presentation_cache');
                $presentation_nonce_key = sha1($id);
                //The item id was adapted for granting public access to the presentation once in order to allow BigBlueButton to gather the file
                $presentation_nonce_value = bigbluebuttonbn_generate_nonce();
                $cache->set($presentation_nonce_key, array('value' => $presentation_nonce_value, 'counter' => 0));
            }
            $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                $file->get_filearea(), $presentation_nonce_value, $file->get_filepath(), $file->get_filename());

            $presentation_url = $url->out(false);
        }
    }

    $presentation_array = array('url' => $presentation_url, 'name' => $presentation_name,
                                'icon' => $presentation_icon,
                                'mimetype_description' => $presentation_mimetype_description);

    return $presentation_array;
}

function bigbluebuttonbn_generate_nonce() {
    $mt = microtime();
    $rand = mt_rand();

    return md5($mt.$rand);
}

function bigbluebuttonbn_random_password($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?';
    $password = substr(str_shuffle($chars), 0, $length);

    return $password;
}

function bigbluebuttonbn_get_moodle_version_major() {
    global $CFG;

    $version_array = explode('.', $CFG->version);

    return $version_array[0];
}

function bigbluebuttonbn_event_log_standard($event_type, $bigbluebuttonbn, $cm,
        $timecreated = null, $userid = null, $event_subtype = null) {

    $context = context_module::instance($cm->id);
    $event_properties = array('context' => $context, 'objectid' => $bigbluebuttonbn->id);

    switch ($event_type) {
        case BIGBLUEBUTTON_EVENT_MEETING_JOINED:
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_meeting_joined::create($event_properties);
            break;
        case BIGBLUEBUTTON_EVENT_MEETING_CREATED:
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_meeting_created::create($event_properties);
            break;
        case BIGBLUEBUTTON_EVENT_MEETING_ENDED:
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_meeting_ended::create($event_properties);
            break;
        case BIGBLUEBUTTON_EVENT_MEETING_LEFT:
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_meeting_left::create($event_properties);
            break;
        case BIGBLUEBUTTON_EVENT_RECORDING_PUBLISHED:
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_recording_published::create($event_properties);
            break;
        case BIGBLUEBUTTON_EVENT_RECORDING_UNPUBLISHED:
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_recording_unpublished::create($event_properties);
            break;
        case BIGBLUEBUTTON_EVENT_RECORDING_DELETED:
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_recording_deleted::create($event_properties);
            break;
        case BIGBLUEBUTTON_EVENT_ACTIVITY_VIEWED:
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_activity_viewed::create($event_properties);
            break;
        case BIGBLUEBUTTON_EVENT_ACTIVITY_MANAGEMENT_VIEWED:
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_activity_management_viewed::create($event_properties);
            break;
        case BIGBLUEBUTTON_EVENT_MEETING_EVENT:
            $event_properties['userid'] = $userid;
            $event_properties['timecreated'] = $timecreated;
            $event_properties['other'] = $event_subtype;
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_meeting_event::create($event_properties);
            break;
    }

    if (isset($event)) {
        $event->trigger();
    }
}

function bigbluebuttonbn_event_log($event_type, $bigbluebuttonbn, $cm) {
    bigbluebuttonbn_event_log_standard($event_type, $bigbluebuttonbn, $cm);
}

function bigbluebuttonbn_meeting_event_log($event, $bigbluebuttonbn, $cm) {
    bigbluebuttonbn_event_log_standard(BIGBLUEBUTTON_EVENT_MEETING_EVENT, $bigbluebuttonbn, $cm, $event->timestamp, $event->user, $event->event);
}

/**
 * @param bool $is_moderator
 */
function bigbluebuttonbn_participant_joined($meetingid, $is_moderator) {
    $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'meetings_cache');
    $result = $cache->get($meetingid);
    $meeting_info = json_decode($result['meeting_info']);
    $meeting_info->participantCount += 1;
    if ($is_moderator) {
        $meeting_info->moderatorCount += 1;
    }
    $cache->set($meetingid, array('creation_time' => $result['creation_time'], 'meeting_info' => json_encode($meeting_info)));
}

function bigbluebuttonbn_is_meeting_running($meeting_info) {
    $meeting_running = (isset($meeting_info) && isset($meeting_info->returncode) && $meeting_info->returncode == 'SUCCESS');

    return $meeting_running;
}

function bigbluebuttonbn_get_meeting_info($meetingid, $forced = false) {
    $cache_ttl = bigbluebuttonbn_get_cfg_waitformoderator_cache_ttl();

    $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'meetings_cache');
    $result = $cache->get($meetingid);
    $now = time();
    if (!$forced && isset($result) && $now < ($result['creation_time'] + $cache_ttl)) {
        // Use the value in the cache.
        return json_decode($result['meeting_info']);
    }

    // Ping again and refresh the cache.
    $meeting_info = (array) bigbluebuttonbn_getMeetingInfo($meetingid);
    $cache->set($meetingid, array('creation_time' => time(), 'meeting_info' => json_encode($meeting_info)));

    return $meeting_info;
}

function bigbluebuttonbn_end_meeting($meetingid, $password) {
    bigbluebuttonbn_doEndMeeting($meetingid, $password);
}

function bigbluebuttonbn_publish_recording($recordingid, $publish = true) {
    bigbluebuttonbn_doPublishRecordings($recordingid, ($publish) ? 'true' : 'false');
}

function bigbluebuttonbn_publish_recording_imported($recordingid, $bigbluebuttonbnID, $publish = true) {
    global $DB;

    //Locate the record to be updated
    $records = $DB->get_records('bigbluebuttonbn_logs', array('bigbluebuttonbnid' => $bigbluebuttonbnID,
        'log' => BIGBLUEBUTTONBN_LOG_EVENT_IMPORT));

    foreach ($records as $key => $record) {
        $meta = json_decode($record->meta, true);
        if ($recordingid == $meta['recording']['recordID']) {
            // Found, prepare data for the update.
            $meta['recording']['published'] = ($publish) ? 'true' : 'false';
            $records[$key]->meta = json_encode($meta);

            // Proceed with the update.
            $DB->update_record('bigbluebuttonbn_logs', $records[$key]);
        }
    }
}

function bigbluebuttonbn_delete_recording($recordingid) {
    bigbluebuttonbn_doDeleteRecordings($recordingid);
}

function bigbluebuttonbn_delete_recording_imported($recordingid, $bigbluebuttonbnID) {
    global $DB;

    //Locate the record to be updated
    $records = $DB->get_records('bigbluebuttonbn_logs', array('bigbluebuttonbnid' => $bigbluebuttonbnID, 'log' => BIGBLUEBUTTONBN_LOG_EVENT_IMPORT));

    foreach ($records as $key => $record) {
        $meta = json_decode($record->meta, true);
        if ($recordingid == $meta['recording']['recordID']) {
            // Execute delete
            $DB->delete_records('bigbluebuttonbn_logs', array('id' => $key));
        }
    }
}

function bigbluebuttonbn_validate_parameters($params) {
    $error = '';

    if (!isset($params['callback'])) {
        return bigbluebuttonbn_add_error($error, 'This call must include a javascript callback.');
    }

    if (!isset($params['action'])) {
        return bigbluebuttonbn_add_error($error, 'Action parameter must be included.');
    }

    switch (strtolower($params['action'])) {
        case 'server_ping':
        case 'meeting_info':
        case 'meeting_end':
            if (!isset($params['id'])) {
                return bigbluebuttonbn_add_error($error, 'The meetingID must be specified.');
            }
            break;
        case 'recording_info':
        case 'recording_links':
        case 'recording_publish':
        case 'recording_unpublish':
        case 'recording_delete':
        case 'recording_import':
            if (!isset($params['id'])) {
                return bigbluebuttonbn_add_error($error, 'The recordingID must be specified.');
            }
            break;
        case 'recording_ready':
        case 'meeting_events':
            if (empty($params['signed_parameters'])) {
                return bigbluebuttonbn_add_error($error, 'A JWT encoded string must be included as [signed_parameters].');
            }
            break;
        case 'moodle_event':
            break;
        default:
            return bigbluebuttonbn_add_error($error, 'Action '.$params['action'].' can not be performed.');
    }

    return '';
}

function bigbluebuttonbn_add_error($org_msg, $new_msg = '') {
    $error = $org_msg;

    if (!empty($new_msg)) {
        if (!empty($error)) {
            $error .= ' ';
        }
        $error .= $new_msg;
    }

    return $error;
}

/**
 * @param string $meetingID
 * @param string $configXML
 */
function bigbluebuttonbn_setConfigXMLParams($meetingID, $configXML) {
    $params = 'configXML='.urlencode($configXML).'&meetingID='.urlencode($meetingID);
    $config_xml_params = $params.'&checksum='.sha1('setConfigXML'.$params.bigbluebuttonbn_get_cfg_shared_secret());

    return $config_xml_params;
}

/**
 * @param string $meetingID
 * @param string $configXML
 */
function bigbluebuttonbn_setConfigXML($meetingID, $configXML) {
    $url_default_config = bigbluebuttonbn_get_cfg_server_url().'api/setConfigXML?';
    $config_xml_params = bigbluebuttonbn_setConfigXMLParams($meetingID, $configXML);
    $xml = bigbluebuttonbn_wrap_xml_load_file($url_default_config, BIGBLUEBUTTONBN_METHOD_POST, $config_xml_params, 'application/x-www-form-urlencoded');

    return $xml;
}

/**
 * @param string $meetingID
 * @param string $configXML
 */
function bigbluebuttonbn_setConfigXMLArray($meetingID, $configXML) {
    $config_xml = bigbluebuttonbn_setConfigXML($meetingID, $configXML);
    $config_xml_array = (array) $config_xml;

    return $config_xml_array;
}

function bigbluebuttonbn_set_config_xml($meetingID, $configXML) {
    $config_xml_array = bigbluebuttonbn_setConfigXMLArray($meetingID, $configXML);
    if ($config_xml_array['returncode'] != 'SUCCESS') {
        error_log('BigBlueButton was not able to set the custom config.xml file');

        return '';
    }

    return $config_xml_array['configToken'];
}

function bigbluebuttonbn_get_recording_data_row($bbbsession, $recording, $tools = ['publishing', 'deleting']) {
    global $USER;

    $row = null;

    if ($bbbsession['managerecordings'] || $recording['published'] == 'true') {
        $row = new stdClass();

        // Set recording_types.
        $row->recording = bigbluebuttonbn_get_recording_data_row_types($recording);

        // Set activity name and description.
        $row->activity = bigbluebuttonbn_get_recording_data_row_meta_activity(recording);
        $row->description = bigbluebuttonbn_get_recording_data_row_meta_description(recording);

        // Set recording_preview.
        $row->preview = bigbluebuttonbn_get_recording_data_row_preview($recording);

        // Set date.
        $startTime = isset($recording['startTime']) ? floatval($recording['startTime']) : 0;
        $startTime = $startTime - ($startTime % 1000);
        $row->date = floatval($recording['startTime']);

        // Set formatted date.
        $dateformat = get_string('strftimerecentfull', 'langconfig').' %Z';
        $row->date_formatted = userdate($startTime / 1000, $dateformat, usertimezone($USER->timezone));

        // Set formatted duration.
        $first_playback = array_values($recording['playbacks'])[0];
        $length = isset($first_playback['length']) ? $first_playback['length'] : 0;
        $row->duration_formatted = $row->duration = intval($length);

        // Set actionbar, if user is allowed to manage recordings.
        if ($bbbsession['managerecordings']) {
            $row->actionbar = bigbluebuttonbn_get_recording_data_row_actionbar($recording, $tools);
        }
    }

    return $row;
}

function bigbluebuttonbn_get_recording_data_row_actionbar($recording, $tools) {
    $actionbar = '';

    if (in_array('publishing', $tools)) {
        // Set action [show|hide].
        $manage_action = 'publish';
        $manage_tag = 'show';
        if ($recording['published'] == 'true') {
            $manage_action = 'unpublish';
            $manage_tag = 'hide';
        }
        $actionbar .= bigbluebuttonbn_actionbar_render($manage_action, $manage_tag, $recording);
    }

    if (in_array('deleting', $tools)) {
        $manage_action = $manage_tag = 'delete';
        $actionbar .= bigbluebuttonbn_actionbar_render($manage_action, $manage_tag, $recording);
    }

    if (in_array('importing', $tools)) {
        $manage_action = $manage_tag = 'import';
        $actionbar .= bigbluebuttonbn_actionbar_render($manage_action, $manage_tag, $recording);
    }

    return $actionbar;
}

function bigbluebuttonbn_get_recording_data_row_preview($recording) {
    $recording_preview = '';
    foreach ($recording['playbacks'] as $playback) {
        if (isset($playback['preview'])) {
            foreach ($playback['preview'] as $image) {
                $recording_preview .= html_writer::empty_tag('img',
                    array('src' => $image['url'], 'class' => 'thumbnail'));
            }
            $recording_preview .= html_writer::empty_tag('br');
            $recording_preview .= html_writer::tag('div',
                get_string('view_recording_preview_help', 'bigbluebuttonbn'), array('class' => 'text-muted small'));
            break;
        }
    }

    return $recording_preview;
}

function bigbluebuttonbn_get_recording_data_row_types($recording) {
    global $OUTPUT;

    $attributes = 'data-imported="false"';
    if (isset($recording['imported'])) {
        $attributes = 'data-imported="true" title="'.get_string('view_recording_link_warning', 'bigbluebuttonbn').'"';
    }

    $visibility = '';
    if ($recording['published'] === 'false') {
        $visibility = 'hidden ';
    }

    $recording_types = '<div id="playbacks-'.$recording['recordID'].'" '.$attributes.' '.$visibility.'>';
    foreach ($recording['playbacks'] as $playback) {
        $recording_types .= $OUTPUT->action_link($playback['url'], get_string('view_recording_format_'.$playback['type'], 'bigbluebuttonbn'), null, array('title' => get_string('view_recording_format_'.$playback['type'], 'bigbluebuttonbn'), 'target' => '_new')).'&#32;';
    }
    $recording_types .= '</div>';

    return $recording_types;
}

function bigbluebuttonbn_get_recording_data_row_meta_activity($recording) {
    if (isset($recording['meta_contextactivity'])) {
        return htmlentities($recording['meta_contextactivity']);
    } elseif (isset($recording['meta_bbb-recording-name'])) {
        return htmlentities($recording['meta_bbb-recording-name']);
    }

    return htmlentities($recording['meetingName']);
}

function bigbluebuttonbn_get_recording_data_row_meta_description($recording) {
    $meta_description = html_writer::start_tag('div', array('class' => 'col-md-20'));
    if (isset($recording['meta_contextactivitydescription']) && trim($recording['meta_contextactivitydescription']) != '') {
        $meta_description .= htmlentities($recording['meta_contextactivitydescription']);
    } elseif (isset($recording['meta_bbb-recording-description']) && trim($recording['meta_bbb-recording-description']) != '') {
        $meta_description .= htmlentities($recording['meta_bbb-recording-description']);
    }
    $meta_description .= html_writer::end_tag('div');

    return $meta_description;
}

function bigbluebuttonbn_actionbar_render($manage_action, $manage_tag, $recording) {
    global $OUTPUT;

    $onclick = 'M.mod_bigbluebuttonbn.broker_manageRecording("'.$manage_action.'", "'.$recording['recordID'].'", "'.$recording['meetingID'].'");';
    if (bigbluebuttonbn_get_cfg_recording_icons_enabled()) {
        //With icon for $manage_action
        $icon_attributes = array('id' => 'recording-btn-'.$manage_action.'-'.$recording['recordID']);
        $icon = new pix_icon('i/'.$manage_tag, get_string($manage_tag), 'moodle', $icon_attributes);
        $link_attributes = array('id' => 'recording-link-'.$manage_action.'-'.$recording['recordID'], 'onclick' => $onclick);

        return $OUTPUT->action_icon('#', $icon, null, $link_attributes, false);
    }

    //With text for $manage_action
    $link_attributes = array('title' => get_string($manage_tag), 'class' => 'btn btn-xs btn-danger', 'onclick' => $onclick);

    return $OUTPUT->action_link('#', get_string($manage_action), null, $link_attributes);
}

function bigbluebuttonbn_get_recording_columns($bbbsession) {
    // Set strings to show
    $recording = get_string('view_recording_recording', 'bigbluebuttonbn');
    $activity = get_string('view_recording_activity', 'bigbluebuttonbn');
    $description = get_string('view_recording_description', 'bigbluebuttonbn');
    $preview = get_string('view_recording_preview', 'bigbluebuttonbn');
    $date = get_string('view_recording_date', 'bigbluebuttonbn');
    $duration = get_string('view_recording_duration', 'bigbluebuttonbn');
    $actionbar = get_string('view_recording_actionbar', 'bigbluebuttonbn');

    // Initialize table headers.
    $recordingsbn_columns = array(
        array('key' => 'recording', 'label' => $recording, 'width' => '125px', 'allowHTML' => true),
        array('key' => 'activity', 'label' => $activity, 'sortable' => true, 'width' => '175px', 'allowHTML' => true),
        array('key' => 'description', 'label' => $description, 'width' => '250px', 'sortable' => true, 'width' => '250px', 'allowHTML' => true),
        array('key' => 'preview', 'label' => $preview, 'width' => '250px', 'allowHTML' => true),
        array('key' => 'date', 'label' => $date, 'sortable' => true, 'width' => '225px', 'allowHTML' => true),
        array('key' => 'duration', 'label' => $duration, 'width' => '50px'),
        );

    if ($bbbsession['managerecordings']) {
        array_push($recordingsbn_columns, array('key' => 'actionbar', 'label' => $actionbar, 'width' => '100px', 'allowHTML' => true));
    }

    return $recordingsbn_columns;
}

function bigbluebuttonbn_get_recording_data($bbbsession, $recordings, $tools = ['publishing', 'deleting']) {
    $table_data = array();

    // Build table content.
    if (isset($recordings) && !array_key_exists('messageKey', $recordings)) {  // There are recordings for this meeting
        foreach ($recordings as $recording) {
            $row = bigbluebuttonbn_get_recording_data_row($bbbsession, $recording, $tools);
            if ($row != null) {
                array_push($table_data, $row);
            }
        }
    }

    return $table_data;
}

function bigbluebuttonbn_get_recording_table($bbbsession, $recordings, $tools = ['publishing', 'deleting']) {
    // Set strings to show.
    $recording = get_string('view_recording_recording', 'bigbluebuttonbn');
    $description = get_string('view_recording_description', 'bigbluebuttonbn');
    $date = get_string('view_recording_date', 'bigbluebuttonbn');
    $duration = get_string('view_recording_duration', 'bigbluebuttonbn');
    $actionbar = get_string('view_recording_actionbar', 'bigbluebuttonbn');
    $playback = get_string('view_recording_playback', 'bigbluebuttonbn');
    $preview = get_string('view_recording_preview', 'bigbluebuttonbn');

    // Declare the table.
    $table = new html_table();
    $table->data = array();

    // Initialize table headers.
    $table->head = array($playback.$recording, $description, $preview, $date, $duration);
    $table->align = array('left', 'left', 'left', 'left', 'left', 'center');
    if ($bbbsession['managerecordings']) {
        $table->head[] = $actionbar;
        $table->align[] = 'left';
    }

    // Build table content.
    if (isset($recordings) && !array_key_exists('messageKey', $recordings)) {
        // There are recordings for this meeting.
        foreach ($recordings as $recording) {
            $row = new html_table_row();
            $row->id = 'recording-td-'.$recording['recordID'];
            $row->attributes['data-imported'] = 'false';
            if (isset($recording['imported'])) {
                $row->attributes['data-imported'] = 'true';
                $row->attributes['title'] = get_string('view_recording_link_warning', 'bigbluebuttonbn');
            }

            $row_data = bigbluebuttonbn_get_recording_data_row($bbbsession, $recording, $tools);
            if ($row_data != null) {
                $row_data->date_formatted = str_replace(' ', '&nbsp;', $row_data->date_formatted);
                $row->cells = array($row_data->recording, $row_data->activity, $row_data->description, $row_data->preview, $row_data->date_formatted, $row_data->duration_formatted);
                if ($bbbsession['managerecordings']) {
                    $row->cells[] = $row_data->actionbar;
                }
                array_push($table->data, $row);
            }
        }
    }

    return $table;
}

function bigbluebuttonbn_send_notification_recording_ready($bigbluebuttonbn) {
    $sender = get_admin();

    // Prepare message.
    $msg = new stdClass();

    // Build the message_body.
    $msg->activity_type = '';
    $msg->activity_title = $bigbluebuttonbn->name;
    $message_text = '<p>'.get_string('email_body_recording_ready_for', 'bigbluebuttonbn').' '.$msg->activity_type.' &quot;'.$msg->activity_title.'&quot; '.get_string('email_body_recording_ready_is_ready', 'bigbluebuttonbn').'.</p>';

    bigbluebuttonbn_send_notification($sender, $bigbluebuttonbn, $message_text);
}

function bigbluebuttonbn_server_offers_bn_capabilities() {
    // Validates if the server may have extended capabilities.
    $parsed_url = parse_url(bigbluebuttonbn_get_cfg_server_url());
    $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
    $host_ends = explode('.', $host);
    $host_ends_length = count($host_ends);

    return $host_ends_length > 0 && $host_ends[$host_ends_length - 1] == 'com' && $host_ends[$host_ends_length - 2] == 'blindsidenetworks';
}

function bigbluebuttonbn_get_locales_for_view() {
    $locales = array(
            'not_started' => get_string('view_message_conference_not_started', 'bigbluebuttonbn'),
            'wait_for_moderator' => get_string('view_message_conference_wait_for_moderator', 'bigbluebuttonbn'),
            'in_progress' => get_string('view_message_conference_in_progress', 'bigbluebuttonbn'),
            'started_at' => get_string('view_message_session_started_at', 'bigbluebuttonbn'),
            'session_no_users' => get_string('view_message_session_no_users', 'bigbluebuttonbn'),
            'session_has_user' => get_string('view_message_session_has_user', 'bigbluebuttonbn'),
            'session_has_users' => get_string('view_message_session_has_users', 'bigbluebuttonbn'),
            'has_joined' => get_string('view_message_has_joined', 'bigbluebuttonbn'),
            'have_joined' => get_string('view_message_have_joined', 'bigbluebuttonbn'),
            'user' => get_string('view_message_user', 'bigbluebuttonbn'),
            'users' => get_string('view_message_users', 'bigbluebuttonbn'),
            'viewer' => get_string('view_message_viewer', 'bigbluebuttonbn'),
            'viewers' => get_string('view_message_viewers', 'bigbluebuttonbn'),
            'moderator' => get_string('view_message_moderator', 'bigbluebuttonbn'),
            'moderators' => get_string('view_message_moderators', 'bigbluebuttonbn'),
            'publish' => get_string('view_recording_list_actionbar_publish', 'bigbluebuttonbn'),
            'publishing' => get_string('view_recording_list_actionbar_publishing', 'bigbluebuttonbn'),
            'unpublish' => get_string('view_recording_list_actionbar_unpublish', 'bigbluebuttonbn'),
            'unpublishing' => get_string('view_recording_list_actionbar_unpublishing', 'bigbluebuttonbn'),
            'modal_title' => get_string('view_recording_modal_title', 'bigbluebuttonbn'),
            'modal_button' => get_string('view_recording_modal_button', 'bigbluebuttonbn'),
            'userlimit_reached' => get_string('view_error_userlimit_reached', 'bigbluebuttonbn'),
            'recording' => get_string('view_recording', 'bigbluebuttonbn'),
            'recording_link' => get_string('view_recording_link', 'bigbluebuttonbn'),
            'recording_link_warning' => get_string('view_recording_link_warning', 'bigbluebuttonbn'),
            'unpublish_confirmation' => get_string('view_recording_unpublish_confirmation', 'bigbluebuttonbn'),
            'unpublish_confirmation_warning_s' => get_string('view_recording_unpublish_confirmation_warning_s', 'bigbluebuttonbn'),
            'unpublish_confirmation_warning_p' => get_string('view_recording_unpublish_confirmation_warning_p', 'bigbluebuttonbn'),
            'delete_confirmation' => get_string('view_recording_delete_confirmation', 'bigbluebuttonbn'),
            'delete_confirmation_warning_s' => get_string('view_recording_delete_confirmation_warning_s', 'bigbluebuttonbn'),
            'delete_confirmation_warning_p' => get_string('view_recording_delete_confirmation_warning_p', 'bigbluebuttonbn'),
            'import_confirmation' => get_string('view_recording_import_confirmation', 'bigbluebuttonbn'),
            'conference_ended' => get_string('view_message_conference_has_ended', 'bigbluebuttonbn'),
            'conference_not_started' => get_string('view_message_conference_not_started', 'bigbluebuttonbn'),
    );

    return $locales;
}

function bigbluebuttonbn_get_cfg_server_url_default() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_server_url) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_server_url : (isset($CFG->bigbluebuttonbn_server_url) ? $CFG->bigbluebuttonbn_server_url : (isset($CFG->BigBlueButtonBNServerURL) ? $CFG->BigBlueButtonBNServerURL : BIGBLUEBUTTONBN_DEFAULT_SERVER_URL));
}

function bigbluebuttonbn_get_cfg_shared_secret_default() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_shared_secret) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_shared_secret : (isset($CFG->bigbluebuttonbn_shared_secret) ? $CFG->bigbluebuttonbn_shared_secret : (isset($CFG->BigBlueButtonBNSecuritySalt) ? $CFG->BigBlueButtonBNSecuritySalt : BIGBLUEBUTTONBN_DEFAULT_SHARED_SECRET));
}

function bigbluebuttonbn_get_cfg_voicebridge_editable() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_voicebridge_editable) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_voicebridge_editable : (isset($CFG->bigbluebuttonbn_voicebridge_editable) ? $CFG->bigbluebuttonbn_voicebridge_editable : false);
}

function bigbluebuttonbn_get_cfg_recording_default() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recording_default) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recording_default : (isset($CFG->bigbluebuttonbn_recording_default) ? $CFG->bigbluebuttonbn_recording_default : true);
}

function bigbluebuttonbn_get_cfg_recording_editable() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recording_editable) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recording_editable : (isset($CFG->bigbluebuttonbn_recording_editable) ? $CFG->bigbluebuttonbn_recording_editable : true);
}

function bigbluebuttonbn_get_cfg_recording_tagging_default() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordingtagging_default) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordingtagging_default : (isset($CFG->bigbluebuttonbn_recordingtagging_default) ? $CFG->bigbluebuttonbn_recordingtagging_default : false);
}

function bigbluebuttonbn_get_cfg_recording_tagging_editable() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordingtagging_editable) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordingtagging_editable : (isset($CFG->bigbluebuttonbn_recordingtagging_editable) ? $CFG->bigbluebuttonbn_recordingtagging_editable : false);
}

function bigbluebuttonbn_get_cfg_recording_icons_enabled() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recording_icons_enabled) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recording_icons_enabled : (isset($CFG->bigbluebuttonbn_recording_icons_enabled) ? $CFG->bigbluebuttonbn_recording_icons_enabled : true);
}

function bigbluebuttonbn_get_cfg_importrecordings_enabled() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_importrecordings_enabled) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_importrecordings_enabled : (isset($CFG->bigbluebuttonbn_importrecordings_enabled) ? $CFG->bigbluebuttonbn_importrecordings_enabled : false);
}

function bigbluebuttonbn_get_cfg_importrecordings_from_deleted_activities_enabled() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_importrecordings_from_deleted_activities_enabled) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_importrecordings_from_deleted_activities_enabled : (isset($CFG->bigbluebuttonbn_importrecordings_from_deleted_activities_enabled) ? $CFG->bigbluebuttonbn_importrecordings_from_deleted_activities_enabled : false);
}

function bigbluebuttonbn_get_cfg_waitformoderator_default() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_waitformoderator_default) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_waitformoderator_default : (isset($CFG->bigbluebuttonbn_waitformoderator_default) ? $CFG->bigbluebuttonbn_waitformoderator_default : false);
}

function bigbluebuttonbn_get_cfg_waitformoderator_editable() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_waitformoderator_editable) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_waitformoderator_editable : (isset($CFG->bigbluebuttonbn_waitformoderator_editable) ? $CFG->bigbluebuttonbn_waitformoderator_editable : true);
}

function bigbluebuttonbn_get_cfg_waitformoderator_ping_interval() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_waitformoderator_ping_interval) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_waitformoderator_ping_interval : (isset($CFG->bigbluebuttonbn_waitformoderator_ping_interval) ? $CFG->bigbluebuttonbn_waitformoderator_ping_interval : 15);
}

function bigbluebuttonbn_get_cfg_waitformoderator_cache_ttl() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_waitformoderator_cache_ttl) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_waitformoderator_cache_ttl : (isset($CFG->bigbluebuttonbn_waitformoderator_cache_ttl) ? $CFG->bigbluebuttonbn_waitformoderator_cache_ttl : 60);
}

function bigbluebuttonbn_get_cfg_userlimit_default() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_userlimit_default) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_userlimit_default : (isset($CFG->bigbluebuttonbn_userlimit_default) ? $CFG->bigbluebuttonbn_userlimit_default : 0);
}

function bigbluebuttonbn_get_cfg_userlimit_editable() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_userlimit_editable) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_userlimit_editable : (isset($CFG->bigbluebuttonbn_userlimit_editable) ? $CFG->bigbluebuttonbn_userlimit_editable : false);
}

function bigbluebuttonbn_get_cfg_preuploadpresentation_enabled() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    if (extension_loaded('curl')) {
        // This feature only works if curl is installed
        return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_preuploadpresentation_enabled) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_preuploadpresentation_enabled : (isset($CFG->bigbluebuttonbn_preuploadpresentation_enabled) ? $CFG->bigbluebuttonbn_preuploadpresentation_enabled : false);
    }

    return false;
}

function bigbluebuttonbn_get_cfg_sendnotifications_enabled() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_sendnotifications_enabled) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_sendnotifications_enabled : (isset($CFG->bigbluebuttonbn_sendnotifications_enabled) ? $CFG->bigbluebuttonbn_sendnotifications_enabled : false);
}

function bigbluebuttonbn_get_cfg_recordingready_enabled() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordingready_enabled) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordingready_enabled : (isset($CFG->bigbluebuttonbn_recordingready_enabled) ? $CFG->bigbluebuttonbn_recordingready_enabled : false);
}

function bigbluebuttonbn_get_cfg_meetingevents_enabled() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_meetingevents_enabled) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_meetingevents_enabled : (isset($CFG->bigbluebuttonbn_meetingevents_enabled) ? $CFG->bigbluebuttonbn_meetingevents_enabled : false);
}

/**
 * @return string
 */
function bigbluebuttonbn_get_cfg_moderator_default() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_moderator_default) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_moderator_default : (isset($CFG->bigbluebuttonbn_moderator_default) ? $CFG->bigbluebuttonbn_moderator_default : 'owner');
}

function bigbluebuttonbn_get_cfg_scheduled_duration_enabled() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_scheduled_duration_enabled) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_scheduled_duration_enabled : (isset($CFG->bigbluebuttonbn_scheduled_duration_enabled) ? $CFG->bigbluebuttonbn_scheduled_duration_enabled : false);
}

function bigbluebuttonbn_get_cfg_scheduled_duration_compensation() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_scheduled_duration_compensation) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_scheduled_duration_compensation : (isset($CFG->bigbluebuttonbn_scheduled_duration_compensation) ? $CFG->bigbluebuttonbn_scheduled_duration_compensation : 10);
}

function bigbluebuttonbn_get_cfg_scheduled_pre_opening() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_scheduled_pre_opening) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_scheduled_pre_opening : (isset($CFG->bigbluebuttonbn_scheduled_pre_opening) ? $CFG->bigbluebuttonbn_scheduled_pre_opening : 10);
}

function bigbluebuttonbn_get_cfg_recordings_html_default() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordings_html_default) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordings_html_default : (isset($CFG->bigbluebuttonbn_recordings_html_default) ? $CFG->bigbluebuttonbn_recordings_html_default : false);
}

function bigbluebuttonbn_get_cfg_recordings_html_editable() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordings_html_editable) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordings_html_editable : (isset($CFG->bigbluebuttonbn_recordings_html_editable) ? $CFG->bigbluebuttonbn_recordings_html_editable : false);
}

function bigbluebuttonbn_get_cfg_recordings_deleted_activities_default() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordings_deleted_activities_default) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordings_deleted_activities_default : (isset($CFG->bigbluebuttonbn_recordings_deleted_activities_default) ? $CFG->bigbluebuttonbn_recordings_deleted_activities_default : false);
}

function bigbluebuttonbn_get_cfg_recordings_deleted_activities_editable() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;

    return isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordings_deleted_activities_editable) ? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordings_deleted_activities_editable : (isset($CFG->bigbluebuttonbn_recordings_deleted_activities_editable) ? $CFG->bigbluebuttonbn_recordings_deleted_activities_editable : false);
}

function bigbluebuttonbn_get_cfg_options() {
    return [
          'voicebridge_editable' => bigbluebuttonbn_get_cfg_voicebridge_editable(),
          'recording_default' => bigbluebuttonbn_get_cfg_recording_default(),
          'recording_editable' => bigbluebuttonbn_get_cfg_recording_editable(),
          'recording_tagging_default' => bigbluebuttonbn_get_cfg_recording_tagging_default(),
          'recording_tagging_editable' => bigbluebuttonbn_get_cfg_recording_tagging_editable(),
          'waitformoderator_default' => bigbluebuttonbn_get_cfg_waitformoderator_default(),
          'waitformoderator_editable' => bigbluebuttonbn_get_cfg_waitformoderator_editable(),
          'userlimit_default' => bigbluebuttonbn_get_cfg_userlimit_default(),
          'userlimit_editable' => bigbluebuttonbn_get_cfg_userlimit_editable(),
          'preuploadpresentation_enabled' => bigbluebuttonbn_get_cfg_preuploadpresentation_enabled(),
          'sendnotifications_enabled' => bigbluebuttonbn_get_cfg_sendnotifications_enabled(),
          'recordings_html_default' => bigbluebuttonbn_get_cfg_recordings_html_default(),
          'recordings_html_editable' => bigbluebuttonbn_get_cfg_recordings_html_editable(),
          'recordings_deleted_activities_default' => bigbluebuttonbn_get_cfg_recordings_deleted_activities_default(),
          'recordings_deleted_activities_editable' => bigbluebuttonbn_get_cfg_recordings_deleted_activities_editable(),
          'recording_icons_enabled' => bigbluebuttonbn_get_cfg_recording_icons_enabled(),
          'instance_type_enabled' => bigbluebuttonbn_recordings_enabled(),
          'instance_type_default' => BIGBLUEBUTTONBN_TYPE_ALL,
        ];
}

function bigbluebuttonbn_import_get_courses_for_select(array $bbbsession) {
    if ($bbbsession['administrator']) {
        $courses = get_courses('all', 'c.id ASC', 'c.id,c.shortname,c.fullname');
        //It includes the name of the site as a course (category 0), so remove the first one
        unset($courses['1']);
    } else {
        $courses = enrol_get_users_courses($bbbsession['userID'], false, 'id,shortname,fullname');
    }

    $courses_for_select = [];
    foreach ($courses as $course) {
        $courses_for_select[$course->id] = $course->fullname;
    }

    return $courses_for_select;
}

function bigbluebuttonbn_getRecordedMeetingsDeleted($courseID, $bigbluebuttonbnID = null) {
    global $DB;

    $records_deleted = array();

    $filter = array('courseid' => $courseID, 'log' => BIGBLUEBUTTONBN_LOG_EVENT_DELETE);
    if ($bigbluebuttonbnID != null) {
        $filter['id'] = $bigbluebuttonbnID;
    }

    $bigbluebuttonbns_deleted = $DB->get_records('bigbluebuttonbn_logs', $filter);

    foreach ($bigbluebuttonbns_deleted as $key => $bigbluebuttonbn_deleted) {
        $records = $DB->get_records('bigbluebuttonbn_logs',
            array('courseid' => $courseID, 'log' => BIGBLUEBUTTONBN_LOG_EVENT_CREATE));

        if (!empty($records)) {
            //Remove duplicates
            $unique_records = array();
            foreach ($records as $key => $record) {
                if (array_key_exists($record->meetingid, $unique_records)) {
                    unset($records[$key]);
                } else {
                    $meta = json_decode($record->meta);
                    if (!$meta->record) {
                        unset($records[$key]);
                    } elseif ($bigbluebuttonbn_deleted->meetingid != substr($record->meetingid, 0, strlen($bigbluebuttonbn_deleted->meetingid))) {
                        unset($records[$key]);
                    } else {
                        array_push($unique_records, $record->meetingid);
                    }
                }
            }

            $records_deleted = array_merge($records_deleted, $records);
        }
    }

    return $records_deleted;
}

function bigbluebuttonbn_getRecordedMeetings($courseID, $bigbluebuttonbnID = null) {
    global $DB;

    $records = array();

    $filter = array('course' => $courseID);
    if ($bigbluebuttonbnID != null) {
        $filter['id'] = $bigbluebuttonbnID;
    }
    $bigbluebuttonbns = $DB->get_records('bigbluebuttonbn', $filter);

    if (!empty($bigbluebuttonbns)) {
        $table = 'bigbluebuttonbn_logs';

        // Prepare select for loading records based on existent bigbluebuttonbns.
        $select = '';
        foreach ($bigbluebuttonbns as $key => $bigbluebuttonbn) {
            $select .= strlen($select) == 0 ? '(' : ' OR ';
            $select .= 'bigbluebuttonbnid='.$bigbluebuttonbn->id;
        }
        $select .= ") AND log='".BIGBLUEBUTTONBN_LOG_EVENT_CREATE."'";

        // Execute select for loading records based on existent bigbluebuttonbns.
        $records = $DB->get_records_select($table, $select);

        // Remove duplicates.
        $unique_records = array();
        foreach ($records as $key => $record) {
            $record_key = $record->meetingid.','.$record->bigbluebuttonbnid.','.$record->meta;
            if (array_search($record_key, $unique_records) === true) {
                unset($records[$key]);
                continue;
            }
            array_push($unique_records, $record_key);
        }

        // Remove the ones with record=false.
        foreach ($records as $key => $record) {
            $meta = json_decode($record->meta);
            if (!$meta || !$meta->record) {
                unset($records[$key]);
            }
        }
    }

    return $records;
}

function bigbluebutton_output_recording_table($bbbsession, $recordings, $tools = ['publishing', 'deleting']) {
    if (isset($recordings) && !empty($recordings)) {
        // There are recordings for this meeting.
        $table = bigbluebuttonbn_get_recording_table($bbbsession, $recordings, $tools);
    }

    if (!isset($table) || !isset($table->data)) {
        // Render a table qith "No recordings".
        return html_writer::div(get_string('view_message_norecordings', 'bigbluebuttonbn'), '',
            array('id' => 'bigbluebuttonbn_html_table'));
    }

    // Render the table.
    return html_writer::div(html_writer::table($table), '', array('id' => 'bigbluebuttonbn_html_table'));
}

function bigbluebuttonbn_debugdisplay() {
    global $CFG;

    return (bool) $CFG->debugdisplay;
}

function bigbluebuttonbn_html2text($html, $len)
{
    $text = strip_tags($html);
    $text = str_replace('&nbsp;', ' ', $text);
    $text = substr($text, 0, $len);
    if (strlen($text) > $len) {
        $text .= '...';
    }

    return $text;
}

function bigbluebuttonbn_get_tags($id)
{
    $tags = '';
    $tags_array = core_tag_tag::get_item_tags_array('core', 'course_modules', $id);
    foreach ($tags_array as $tag) {
        $tags .= ($tags == '') ? $tag : ','.$tag;
    }

    return $tags;
}

/**
 * helper function to retrieve recordings from the BigBlueButton. The references are stored as events
 * in bigbluebuttonbn_logs.
 *
 * @param string $courseID
 * @param string $bigbluebuttonbnID
 * @param bool   $subset
 * @param bool   $include_deleted
 *
 * @return associative array containing the recordings indexed by recordID, each recording is also a
 * non sequential associative array itself that corresponds to the actual recording in BBB
 */
function bigbluebuttonbn_get_recordings($courseID, $bigbluebuttonbnID = null,
        $subset = true, $include_deleted = false) {
    global $DB;

    // Gather the bigbluebuttonbnids whose meetingids should be included in the getRecordings request'.
    $select = "id <> '{$bigbluebuttonbnID}' AND course = '{$courseID}'";
    $select_deleted = "courseid = '{$courseID}' AND bigbluebuttonbnid <> '{$bigbluebuttonbnID}' AND log = '".BIGBLUEBUTTONBN_LOG_EVENT_DELETE."' AND meta like '%has_recordings%' AND meta like '%true%'";
    if ($bigbluebuttonbnID === null) {
        $select = "course = '{$courseID}'";
        $select_deleted = "courseid = '{$courseID}' AND log = '".BIGBLUEBUTTONBN_LOG_EVENT_DELETE."' AND meta like '%has_recordings%' AND meta like '%true%'";
    } elseif ($subset) {
        $select = "id = '{$bigbluebuttonbnID}'";
        $select_deleted = "bigbluebuttonbnid = '{$bigbluebuttonbnID}' AND log = '".BIGBLUEBUTTONBN_LOG_EVENT_DELETE."' AND meta like '%has_recordings%' AND meta like '%true%'";
    }
    $bigbluebuttonbns = $DB->get_records_select_menu('bigbluebuttonbn', $select, null, 'id', 'id, meetingid');

    // Consider logs from deleted bigbluebuttonbn instances whose meetingids should be included in the getRecordings request.
    if ($include_deleted) {
        $bigbluebuttonbns_del = $DB->get_records_select_menu('bigbluebuttonbn_logs', $select_deleted, null, 'bigbluebuttonbnid', 'bigbluebuttonbnid, meetingid');
        if (!empty($bigbluebuttonbns_del)) {
            // Merge bigbluebuttonbnis from deleted instances, only keys are relevant. Artimetic merge is used in order to keep the keys.
            $bigbluebuttonbns += $bigbluebuttonbns_del;
        }
    }

    // Gather the meetingids from bigbluebuttonbn logs that include a create with record=true.
    $recordings = array();
    if (!empty($bigbluebuttonbns)) {
        // Prepare select for loading records based on existent bigbluebuttonbns.
        $sql = 'SELECT DISTINCT meetingid, bigbluebuttonbnid FROM {bigbluebuttonbn_logs} WHERE ';
        $sql .= '(bigbluebuttonbnid='.implode(' OR bigbluebuttonbnid=', array_keys($bigbluebuttonbns)).')';
        // Include only Create events and exclude those with record not true.
        $sql .= ' AND log = ? AND meta LIKE ? AND meta LIKE ?';
        // Execute select for loading records based on existent bigbluebuttonbns.
        $records = $DB->get_records_sql_menu($sql, array(BIGBLUEBUTTONBN_LOG_EVENT_CREATE, '%record%', '%true%'));
        // Get actual recordings.
        $recordings = bigbluebuttonbn_getRecordingsArray(array_keys($records));
    }

    // Get recording links.
    $recordings_imported = bigbluebuttonbn_getRecordingsImportedArray($courseID, $bigbluebuttonbnID, $subset);

    // Perform aritmetic add instead of merge so the imported recordings corresponding to existent recordings are not included.
    return $recordings + $recordings_imported;
}

function bigbluebuttonbn_unset_existent_recordings_already_imported($recordings, $courseID, $bigbluebuttonbnID) {
    $recordings_imported = bigbluebuttonbn_getRecordingsImportedArray($courseID, $bigbluebuttonbnID, true);

    foreach ($recordings as $key => $recording) {
        if (isset($recordings_imported[$recording['recordID']])) {
            unset($recordings[$key]);
        }
    }

    return $recordings;
}

function bigbluebuttonbn_get_count_recording_imported_instances($recordID) {
    global $DB;

    $sql = 'SELECT * FROM {bigbluebuttonbn_logs} WHERE log = ? AND meta LIKE ? AND meta LIKE ?';

    return $DB->count_records_sql($sql, array(BIGBLUEBUTTONBN_LOG_EVENT_IMPORT, '%recordID%', "%{$recordID}%"));
}

function bigbluebuttonbn_get_recording_imported_instances($recordID) {
    global $DB;

    $sql = 'SELECT * FROM {bigbluebuttonbn_logs} WHERE log = ? AND meta LIKE ? AND meta LIKE ?';
    $recordings_imported = $DB->get_records_sql($sql, array(BIGBLUEBUTTONBN_LOG_EVENT_IMPORT, '%recordID%', "%{$recordID}%"));

    return $recordings_imported;
}

function bigbluebuttonbn_get_instance_type_profiles() {
    $instanceprofiles = array(
            array('id' => BIGBLUEBUTTONBN_TYPE_ALL, 'name' => get_string('instance_type_default', 'bigbluebuttonbn'), 'features' => array('all')),
            array('id' => BIGBLUEBUTTONBN_TYPE_ROOM_ONLY, 'name' => get_string('instance_type_room_only', 'bigbluebuttonbn'), 'features' => array('showroom', 'welcomemessage', 'voicebridge', 'waitformoderator', 'userlimit', 'recording', 'recordingtagging', 'sendnotifications', 'preuploadpresentation', 'permissions', 'schedule', 'groups')),
            array('id' => BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY, 'name' => get_string('instance_type_recording_only', 'bigbluebuttonbn'), 'features' => array('showrecordings', 'importrecordings')),
    );

    return $instanceprofiles;
}

function bigbluebuttonbn_get_instance_profiles_array($profiles = null) {
    if (is_null($profiles) || empty($profiles)) {
        $profiles = bigbluebuttonbn_get_instanceprofiles();
    }

    $profiles_array = array();

    foreach ($profiles as $profile) {
        $profiles_array += array("{$profile['id']}" => $profile['name']);
    }

    return $profiles_array;
}

function bigbluebuttonbn_format_activity_time($time) {
    $activity_time = '';
    if ($time) {
        $activity_time = calendar_day_representation($time).' '.
          get_string('mod_form_field_notification_msg_at', 'bigbluebuttonbn').' '.
          calendar_time_representation($time);
    }

    return $activity_time;
}

function bigbluebuttonbn_recordings_enabled() {
    global $BIGBLUEBUTTONBN_CFG;

    return !(isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recording_default) &&
             isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recording_editable));
}
