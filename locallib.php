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

global $CFG;

require_once(dirname(__FILE__).'/lib.php');

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

    // Default values.
    $log->courseid = $bbbsession['course']->id;
    $log->bigbluebuttonbnid = $bbbsession['bigbluebuttonbn']->id;
    $log->userid = $bbbsession['userID'];
    $log->meetingid = $bbbsession['meetingid'];
    $log->timecreated = time();
    // Overrides.
    foreach ($overrides as $key => $value) {
        $log->$key = $value;
    }

    $log->log = $event;
    if (isset($meta)) {
        $log->meta = $meta;
    } else if ($event == BIGBLUEBUTTONBN_LOG_EVENT_CREATE) {
        $log->meta = '{"record":'.($bbbsession['record'] ? 'true' : 'false').'}';
    }

    $DB->insert_record('bigbluebuttonbn_logs', $log);
}

// BigBlueButton API Calls.
function bigbluebuttonbn_get_join_url($meetingid, $username, $pw, $logouturl, $configtoken = null, $userid = null) {
    $data = ['meetingID' => $meetingid,
              'fullName' => $username,
              'password' => $pw,
              'logoutURL' => $logouturl,
            ];

    if (!is_null($configtoken)) {
        $data['configToken'] = $configtoken;
    }
    if (!is_null($userid)) {
        $data['userID'] = $userid;
    }

    return bigbluebuttonbn_bigbluebutton_action_url('join', $data);
}

function bigbluebuttonbn_get_create_meeting_url($name, $meetingid, $attendeepw, $moderatorpw, $welcome,
    $logouturl, $record = 'false', $duration = 0, $voicebridge = 0, $maxparticipants = 0, $metadata = array()) {
    $data = ['meetingID' => $meetingid,
              'name' => $name,
              'attendeePW' => $attendeepw,
              'moderatorPW' => $moderatorpw,
              'logoutURL' => $logouturl,
              'record' => $record,
            ];

    $voicebridge = intval($voicebridge);
    if ($voicebridge > 0 && $voicebridge < 79999) {
        $data['voiceBridge'] = $voicebridge;
    }

    $duration = intval($duration);
    if ($duration > 0) {
        $data['duration'] = $duration;
    }

    $maxparticipants = intval($maxparticipants);
    if ($maxparticipants > 0) {
        $data['maxParticipants'] = $maxparticipants;
    }

    if (trim($welcome)) {
        $data['welcome'] = $welcome;
    }

    return bigbluebuttonbn_bigbluebutton_action_url('create', $data, $metadata);
}

/**
 * @param string $recordid
 * @param array  $metadata
 */
function bigbluebuttonbn_get_update_recordings_url($recordid, $metadata = array()) {
    return bigbluebuttonbn_bigbluebutton_action_url('updateRecordings', ['recordID' => $recordid], $metadata);
}

/**
 * @param string $action
 * @param array  $data
 * @param array  $metadata
 */
function bigbluebuttonbn_bigbluebutton_action_url($action = '', $data = array(), $metadata = array()) {
    $baseurl = bigbluebuttonbn_get_cfg_server_url().'api/'.$action.'?';

    $params = '';

    foreach ($data as $key => $value) {
        $params .= '&'.$key.'='.urlencode($value);
    }

    foreach ($metadata as $key => $value) {
        $params .= '&'.'meta_'.$key.'='.urlencode($value);
    }

    return $baseurl.$params.'&checksum='.sha1($action.$params.bigbluebuttonbn_get_cfg_shared_secret());
}

function bigbluebuttonbn_get_create_meeting_array($meetingname, $meetingid, $welcomestring, $mpw, $apw,
        $logouturl, $record = 'false', $duration = 0, $voicebridge = 0, $maxparticipants = 0,
        $metadata = array(), $pname = null, $purl = null) {

    $createmeetingurl = bigbluebuttonbn_get_create_meeting_url($meetingname, $meetingid, $apw, $mpw, $welcomestring,
        $logouturl, $record, $duration, $voicebridge, $maxparticipants, $metadata);
    $method = BIGBLUEBUTTONBN_METHOD_GET;
    $data = null;

    if (!is_null($pname) && !is_null($purl)) {
        $method = BIGBLUEBUTTONBN_METHOD_POST;
        $data = "<?xml version='1.0' encoding='UTF-8'?><modules><module name='presentation'><document url='".
            $purl."' /></module></modules>";
    }

    $xml = bigbluebuttonbn_wrap_xml_load_file($createmeetingurl, $method, $data);

    if ($xml) {
        $response = array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
        if ($xml->meetingID) {
            $response += array('meetingID' => $xml->meetingID, 'attendeePW' => $xml->attendeePW,
                'moderatorPW' => $xml->moderatorPW, 'hasBeenForciblyEnded' => $xml->hasBeenForciblyEnded);
        }

        return $response;
    }

    return null;
}

/**
 * @param string $meetingid
 */
function bigbluebuttonbn_get_meeting_array($meetingid) {
    $meetings = bigbluebuttonbn_get_meetings_array();
    if ($meetings) {
        foreach ($meetings as $meeting) {
            if ($meeting['meetingID'] == $meetingid) {
                return $meeting;
            }
        }
    }

    return null;
}

function bigbluebuttonbn_get_meetings_array() {
    $xml = bigbluebuttonbn_wrap_xml_load_file(bigbluebuttonbn_bigbluebutton_action_url('getMeetings'));

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
 * @param string $meetingid
 */
function bigbluebuttonbn_get_meeting_info_array($meetingid) {
    $xml = bigbluebuttonbn_wrap_xml_load_file(
        bigbluebuttonbn_bigbluebutton_action_url('getMeetingInfo', ['meetingID' => $meetingid])
      );

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

    // If the server is unreachable, then prompts the user of the necessary action.
    return null;
}

/**
 * helper function to retrieve recordings from a BigBlueButton server.
 *
 * @param string or array $meetingids   list of meetingIDs "mid1,mid2,mid3" or array("mid1","mid2","mid3")
 * @param string or array $recordingids list of $recordingids "rid1,rid2,rid3" or array("rid1","rid2","rid3") for filtering
 *
 * @return associative array with recordings indexed by recordID, each recording is a non sequential associative array
 */
function bigbluebuttonbn_get_recordings_array($meetingids, $recordingids = null) {
    $recordings = array();

    $meetingidsarray = $meetingids;
    if (!is_array($meetingids)) {
        $meetingidsarray = explode(',', $meetingids);
    }

    // If $meetingidsarray is not empty a paginated getRecordings request is executed.
    if (!empty($meetingidsarray)) {
        $pages = floor(count($meetingidsarray) / 25) + 1;
        for ($page = 1; $page <= $pages; ++$page) {
            $mids = array_slice($meetingidsarray, ($page - 1) * 25, 25);
            // Do getRecordings is executed using a method GET (supported by all versions of BBB).
            $xml = bigbluebuttonbn_wrap_xml_load_file(
                bigbluebuttonbn_bigbluebutton_action_url('getRecordings', ['meetingID' => implode(',', $mids)])
              );
            if ($xml && $xml->returncode == 'SUCCESS' && isset($xml->recordings)) {
                // If there were meetings already created.
                foreach ($xml->recordings->recording as $recording) {
                    $recordingarrayvalue = bigbluebuttonbn_get_recording_array_value($recording);
                    $recordings[$recordingarrayvalue['recordID']] = $recordingarrayvalue;
                }
                uasort($recordings, 'bigbluebuttonbn_recording_build_sorter');
            }
        }
    }

    // Filter recordings based on recordingIDs.
    if (!empty($recordings) && !is_null($recordingids)) {
        $recordingidsarray = $recordingids;
        if (!is_array($recordingids)) {
            $recordingidsarray = explode(',', $recordingids);
        }

        foreach ($recordings as $key => $recording) {
            if (!in_array($recording['recordID'], $recordingidsarray)) {
                unset($recordings[$key]);
            }
        }
    }

    return $recordings;
}

/**
 * Helper function to retrieve imported recordings from the Moodle database.
 * The references are stored as events in bigbluebuttonbn_logs.
 *
 * @param string $courseid
 * @param string $bigbluebuttonbnid
 * @param bool   $subset
 *
 * @return associative array with imported recordings indexed by recordID, each recording is a non sequential associative
 * array that corresponds to the actual recording in BBB
 */
function bigbluebuttonbn_get_recordings_imported_array($courseid, $bigbluebuttonbnid = null, $subset = true) {
    global $DB;

    $select = "courseid = '{$courseid}' AND bigbluebuttonbnid <> '{$bigbluebuttonbnid}' AND log = '".
        BIGBLUEBUTTONBN_LOG_EVENT_IMPORT."'";
    if ($bigbluebuttonbnid === null) {
        $select = "courseid = '{$courseid}' AND log = '".BIGBLUEBUTTONBN_LOG_EVENT_IMPORT."'";
    } else if ($subset) {
        $select = "bigbluebuttonbnid = '{$bigbluebuttonbnid}' AND log = '".BIGBLUEBUTTONBN_LOG_EVENT_IMPORT."'";
    }
    $recordsimported = $DB->get_records_select('bigbluebuttonbn_logs', $select);

    // Check if array is not sequential.
    //error_log(json_encode($recordsimported));
    //if (!empty($recordsimported) && array_keys($recordsimported) !== range(0, count($recordsimported) - 1)) {
    //    // The response contains a single record and needs to be converted to a sequential array format.
    //    error_log(json_encode((array)$recordsimported));
    //    $key = array_keys($recordsimported);
    //    $recordsimported = array($key => $recordsimported[$key]);
    //}
    //error_log(json_encode($recordsimported));

    $recordsimportedarray = array();
    foreach ($recordsimported as $recordimported) {
        $meta = json_decode($recordimported->meta, true);
        $recording = $meta['recording'];
        $recordsimportedarray[$recording['recordID']] = $recording;
    }

    return $recordsimportedarray;
}

function bigbluebuttonbn_get_default_config_xml() {
    $xml = bigbluebuttonbn_wrap_xml_load_file(
        bigbluebuttonbn_bigbluebutton_action_url('getDefaultConfigXML')
      );

    return $xml;
}

function bigbluebuttonbn_get_default_config_xml_array() {
    $defaultconfigxml = bigbluebuttonbn_getDefaultConfigXML();

    return (array) $defaultconfigxml;
}

function bigbluebuttonbn_get_recording_array_value($recording) {
    // Add formats.
    $playbackarray = array();
    foreach ($recording->playback->format as $format) {
        $playbackarray[(string) $format->type] = array('type' => (string) $format->type,
            'url' => (string) $format->url, 'length' => (string) $format->length);
        // Add preview per format when existing.
        if ($format->preview) {
            $imagesarray = array();
            foreach ($format->preview->images->image as $image) {
                $imagearray = array('url' => (string) $image);
                foreach ($image->attributes() as $attkey => $attvalue) {
                    $imagearray[$attkey] = (string) $attvalue;
                }
                array_push($imagesarray, $imagearray);
            }
            $playbackarray[(string) $format->type]['preview'] = $imagesarray;
        }
    }

    // Add the metadata to the recordings array.
    $metadataarray = array();
    $metadata = get_object_vars($recording->metadata);
    foreach ($metadata as $key => $value) {
        if (is_object($value)) {
            $value = '';
        }
        $metadataarray['meta_'.$key] = $value;
    }

    $recordingarrayvalue = array('recordID' => (string) $recording->recordID,
        'meetingID' => (string) $recording->meetingID, 'meetingName' => (string) $recording->name,
        'published' => (string) $recording->published, 'startTime' => (string) $recording->startTime,
        'endTime' => (string) $recording->endTime, 'playbacks' => $playbackarray) + $metadataarray;

    return $recordingarrayvalue;
}

function bigbluebuttonbn_recording_build_sorter($a, $b) {
    if ($a['startTime'] < $b['startTime']) {
        return -1;
    } else if ($a['startTime'] == $b['startTime']) {
        return 0;
    }

    return 1;
}

/**
 * @param string $recordids
 */
function bigbluebuttonbn_delete_recordings($recordids) {
    $ids = explode(',', $recordids);
    foreach ($ids as $id) {
        $xml = bigbluebuttonbn_wrap_xml_load_file(
            bigbluebuttonbn_bigbluebutton_action_url('deleteRecordings', ['recordID' => $id])
          );
        if ($xml && $xml->returncode != 'SUCCESS') {
            return false;
        }
    }

    return true;
}

/**
 * @param string $recordids
 * @param string $publish
 */
function bigbluebuttonbn_publish_recordings($recordids, $publish = 'true') {
    $ids = explode(',', $recordids);
    foreach ($ids as $id) {
        $xml = bigbluebuttonbn_wrap_xml_load_file(
            bigbluebuttonbn_bigbluebutton_action_url('publishRecordings', ['recordID' => $id, 'publish' => $publish])
          );
        if ($xml && $xml->returncode != 'SUCCESS') {
            return false;
        }
    }

    return true;
}

/**
 * @param string $meetingid
 * @param string $modpw
 */
function bigbluebuttonbn_end_meeting($meetingid, $modpw) {
    $xml = bigbluebuttonbn_wrap_xml_load_file(
        bigbluebuttonbn_bigbluebutton_action_url('end', ['meetingID' => $meetingid, 'password' => $modpw])
      );

    if ($xml) {
        // If the xml packet returned failure it displays the message to the user.
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
    }

    // If the server is unreachable, then prompts the user of the necessary action.
    return null;
}

/**
 * @param string $meetingid
 */
function bigbluebuttonbn_is_meeting_running($meetingid) {
    $xml = bigbluebuttonbn_wrap_xml_load_file(
        bigbluebuttonbn_bigbluebutton_action_url('isMeetingRunning', ['meetingID' => $meetingid])
      );

    if ($xml && $xml->returncode == 'SUCCESS') {
        return ($xml->running == 'true') ? true : false;
    }

    return false;
}

function bigbluebuttonbn_get_server_version() {
    $xml = bigbluebuttonbn_wrap_xml_load_file(
        bigbluebuttonbn_bigbluebutton_action_url()
      );

    if ($xml && $xml->returncode == 'SUCCESS') {
        return $xml->version;
    }

    return null;
}

/**
 * @param string $url
 * @param string $data
 */
function bigbluebuttonbn_wrap_xml_load_file($url, $method = BIGBLUEBUTTONBN_METHOD_GET,
    $data = null, $contenttype = 'text/xml') {

    //debugging('Request to: '.$url, DEBUG_DEVELOPER);
    //error_log('Request to: '.$url);

    if (extension_loaded('curl')) {
        $response = bigbluebuttonbn_wrap_xml_load_file_curl_request($url, $method, $data, $contenttype);

        if (!$response) {
            //debugging('No response on wrap_simplexml_load_file', DEBUG_DEVELOPER);
            return null;
        }

        //debugging('Response: '.$response, DEBUG_DEVELOPER);
        //error_log('Response: '.$response);

        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);

            return $xml;
        } catch (Exception $e) {
            libxml_use_internal_errors($previous);
            $error = 'Caught exception: '.$e->getMessage();
            //debugging($error, DEBUG_DEVELOPER);
            return null;
        }
    }

    // Alternative request non CURL based.
    $previous = libxml_use_internal_errors(true);
    try {
        $response = simplexml_load_file($url, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
        //debugging('Response processed: '.$response->asXML(), DEBUG_DEVELOPER);
        return $response;
    } catch (Exception $e) {
        $error = 'Caught exception: '.$e->getMessage();
        //debugging($error, DEBUG_DEVELOPER);
        libxml_use_internal_errors($previous);
        return null;
    }
}

function bigbluebuttonbn_wrap_xml_load_file_curl_request($url, $method = BIGBLUEBUTTONBN_METHOD_GET,
    $data = null, $contenttype = 'text/xml') {
    $c = new curl();
    $c->setopt(array('SSL_VERIFYPEER' => true));
    if ($method == BIGBLUEBUTTONBN_METHOD_POST) {
        if (is_null($data) || is_array($data)) {
            return $c->post($url);
        }

        $options = array();
        $options['CURLOPT_HTTPHEADER'] = array(
                 'Content-Type: '.$contenttype,
                 'Content-Length: '.strlen($data),
                 'Content-Language: en-US',
               );

        return $c->post($url, $data, $options);
    }

    return $c->get($url);
}

function bigbluebuttonbn_get_user_roles($context, $userid) {
    global $DB;

    $userroles = get_user_roles($context, $userid);
    if ($userroles) {
        $where = '';
        foreach ($userroles as $value) {
            $where .= (empty($where) ? ' WHERE' : ' AND').' id='.$value->roleid;
        }
        $userroles = $DB->get_records_sql('SELECT * FROM {role}'.$where);
    }

    return $userroles;
}

function bigbluebuttonbn_get_guest_role() {
    $guestrole = get_guest_role();

    return array($guestrole->id => $guestrole);
}

function bigbluebuttonbn_get_roles(context $context = null) {
    $roles = role_get_names($context);
    $rolesarray = array();
    foreach ($roles as $role) {
        $rolesarray[$role->shortname] = $role->localname;
    }

    return $rolesarray;
}

function bigbluebuttonbn_get_role($shortname) {
    $roles = role_get_names();
    foreach ($roles as $role) {
        if ($role->shortname == $shortname) {
            return $role;
        }
    }
}

function bigbluebuttonbn_get_roles_select($roles = array()) {
    $rolesarray = array();
    foreach ($roles as $key => $value) {
        $rolesarray[] = array('id' => $key, 'name' => $value);
    }

    return $rolesarray;
}

function bigbluebuttonbn_get_users_select($users) {
    $usersarray = array();
    foreach ($users as $user) {
        $usersarray[] = array('id' => $user->id, 'name' => fullname($user));
    }

    return $usersarray;
}

function bigbluebuttonbn_get_participant_list($bigbluebuttonbn, $context = null) {
    if ($bigbluebuttonbn == null) {
        return bigbluebuttonbn_get_participant_list_default($context);
    }

    $participantlistarray = array();
    $participantlist = json_decode($bigbluebuttonbn->participants);
    foreach ($participantlist as $participant) {
        $participantlistarray[] = array('selectiontype' => $participant->selectiontype,
                                          'selectionid' => $participant->selectionid,
                                          'role' => $participant->role, );
    }

    return $participantlistarray;
}

function bigbluebuttonbn_get_participant_list_default($context) {
    global $USER;

    $participantlistarray = array();
    $participantlistarray[] = array('selectiontype' => 'all',
                                       'selectionid' => 'all',
                                       'role' => BIGBLUEBUTTONBN_ROLE_VIEWER, );

    $moderatordefaults = explode(',', bigbluebuttonbn_get_cfg_moderator_default());
    foreach ($moderatordefaults as $moderatordefault) {
        if ($moderatordefault == 'owner') {
            $users = get_enrolled_users($context);
            foreach ($users as $user) {
                if ($user->id == $USER->id) {
                    $participantlistarray[] = array('selectiontype' => 'user',
                                                       'selectionid' => $USER->id,
                                                       'role' => BIGBLUEBUTTONBN_ROLE_MODERATOR, );
                    break;
                }
            }
            continue;
        }

        $participantlistarray[] = array('selectiontype' => 'role',
                                          'selectionid' => $moderatordefault,
                                          'role' => BIGBLUEBUTTONBN_ROLE_MODERATOR, );
    }

    return $participantlistarray;
}

function bigbluebuttonbn_get_participant_list_json($bigbluebuttonbnid = null) {
    return json_encode(bigbluebuttonbn_get_participant_list($bigbluebuttonbnid));
}

function bigbluebuttonbn_is_moderator($context, $participants, $userid = null, $userroles = null) {
    global $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    if (empty($userroles)) {
        $userroles = get_user_roles($context, $userid, true);
    }

    if (empty($participants)) {
        // The room that is being used comes from a previous version.
        return has_capability('mod/bigbluebuttonbn:moderate', $context);
    }

    $participantlist = json_decode($participants);
    // Iterate participant rules.
    foreach ($participantlist as $participant) {
        if ($participant->role == BIGBLUEBUTTONBN_ROLE_MODERATOR) {
            // Looks for all configuration.
            if ($participant->selectiontype == 'all') {
                return true;
            }
            // Looks for users.
            if ($participant->selectiontype == 'user' && $participant->selectionid == $userid) {
                return true;
            }
            // Looks for roles.
            if ($participant->selectiontype == 'role') {
                $role = bigbluebuttonbn_get_role($participant->selectionid);
                if (array_key_exists($role->id, $userroles)) {
                    return true;
                }
            }
        }
    }

    return false;
}

function bigbluebuttonbn_get_error_key($messagekey, $defaultkey = null) {
    $key = $defaultkey;
    if ($messagekey == 'checksumError') {
        $key = 'index_error_checksum';
    } else if ($messagekey == 'maxConcurrent') {
        $key = 'view_error_max_concurrent';
    }

    return $key;
}

function bigbluebuttonbn_voicebridge_unique($voicebridge, $id = null) {
    global $DB;

    $isunique = true;
    if ($voicebridge != 0) {
        $table = 'bigbluebuttonbn';
        $select = 'voicebridge = '.$voicebridge;
        if ($id) {
            $select .= ' AND id <> '.$id;
        }
        if ($DB->get_records_select($table, $select)) {
            $isunique = false;
        }
    }

    return $isunique;
}

function bigbluebuttonbn_get_duration($closingtime) {
    $duration = 0;
    $now = time();
    if ($closingtime > 0 && $now < $closingtime) {
        $duration = ceil(($closingtime - $now) / 60);
        $compensationtime = intval(bigbluebuttonbn_get_cfg_scheduled_duration_compensation());
        $duration = intval($duration) + $compensationtime;
    }

    return $duration;
}

function bigbluebuttonbn_get_presentation_array($context, $presentation, $id = null) {
    $pname = null;
    $purl = null;
    $picon = null;
    $pmimetypedescrip = null;

    if (!empty($presentation)) {
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_bigbluebuttonbn', 'presentation', 0,
            'itemid, filepath, filename', false);
        if (count($files) >= 1) {
            $file = reset($files);
            unset($files);
            $pname = $file->get_filename();
            $picon = file_file_icon($file, 24);
            $pmimetypedescrip = get_mimetype_description($file);
            $pnoncevalue = null;

            if (!is_null($id)) {
                // Create the nonce component for granting a temporary public access.
                $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn',
                    'presentation_cache');
                $pnoncekey = sha1($id);
                /* The item id was adapted for granting public access to the presentation once in order
                 * to allow BigBlueButton to gather the file. */
                $pnoncevalue = bigbluebuttonbn_generate_nonce();
                $cache->set($pnoncekey, array('value' => $pnoncevalue, 'counter' => 0));
            }
            $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                $file->get_filearea(), $pnoncevalue, $file->get_filepath(), $file->get_filename());

            $purl = $url->out(false);
        }
    }

    $parray = array('url' => $purl, 'name' => $pname,
                               'icon' => $picon,
                               'mimetype_description' => $pmimetypedescrip);

    return $parray;
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

    $versionarray = explode('.', $CFG->version);

    return $versionarray[0];
}

function bigbluebuttonbn_event_log_standard($eventtype, $bigbluebuttonbn, $cm,
        $timecreated = null, $userid = null, $eventsubtype = null) {

    $context = context_module::instance($cm->id);
    $eventproperties = array('context' => $context, 'objectid' => $bigbluebuttonbn->id);

    switch ($eventtype) {
        case BIGBLUEBUTTON_EVENT_MEETING_JOINED:
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_meeting_joined::create($eventproperties);
            break;
        case BIGBLUEBUTTON_EVENT_MEETING_CREATED:
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_meeting_created::create($eventproperties);
            break;
        case BIGBLUEBUTTON_EVENT_MEETING_ENDED:
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_meeting_ended::create($eventproperties);
            break;
        case BIGBLUEBUTTON_EVENT_MEETING_LEFT:
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_meeting_left::create($eventproperties);
            break;
        case BIGBLUEBUTTON_EVENT_RECORDING_PUBLISHED:
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_recording_published::create($eventproperties);
            break;
        case BIGBLUEBUTTON_EVENT_RECORDING_UNPUBLISHED:
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_recording_unpublished::create($eventproperties);
            break;
        case BIGBLUEBUTTON_EVENT_RECORDING_DELETED:
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_recording_deleted::create($eventproperties);
            break;
        case BIGBLUEBUTTON_EVENT_ACTIVITY_VIEWED:
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_activity_viewed::create($eventproperties);
            break;
        case BIGBLUEBUTTON_EVENT_ACTIVITY_MANAGEMENT_VIEWED:
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_activity_management_viewed::create($eventproperties);
            break;
        case BIGBLUEBUTTON_EVENT_MEETING_EVENT:
            $eventproperties['userid'] = $userid;
            $eventproperties['timecreated'] = $timecreated;
            $eventproperties['other'] = $eventsubtype;
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_meeting_event::create($eventproperties);
            break;
    }

    if (isset($event)) {
        $event->trigger();
    }
}

function bigbluebuttonbn_event_log($eventtype, $bigbluebuttonbn, $cm) {
    bigbluebuttonbn_event_log_standard($eventtype, $bigbluebuttonbn, $cm);
}

function bigbluebuttonbn_meeting_event_log($event, $bigbluebuttonbn, $cm) {
    bigbluebuttonbn_event_log_standard(BIGBLUEBUTTON_EVENT_MEETING_EVENT, $bigbluebuttonbn, $cm,
        $event->timestamp, $event->user, $event->event);
}

/**
 * @param string $meetingid
 * @param bool $ismoderator
 */
function bigbluebuttonbn_participant_joined($meetingid, $ismoderator) {
    $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'meetings_cache');
    $result = $cache->get($meetingid);
    $meetinginfo = json_decode($result['meeting_info']);
    $meetinginfo->participantCount += 1;
    if ($ismoderator) {
        $meetinginfo->moderatorCount += 1;
    }
    $cache->set($meetingid, array('creation_time' => $result['creation_time'],
        'meeting_info' => json_encode($meetinginfo)));
}

/**
 * @param string $meetingid
 * @param boolean $forced
 */
function bigbluebuttonbn_get_meeting_info($meetingid, $forced = false) {
    $cachettl = bigbluebuttonbn_get_cfg_waitformoderator_cache_ttl();

    $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'meetings_cache');
    $result = $cache->get($meetingid);
    $now = time();
    if (!$forced && isset($result) && $now < ($result['creation_time'] + $cachettl)) {
        // Use the value in the cache.
        return (array) json_decode($result['meeting_info']);
    }

    // Ping again and refresh the cache.
    $meetinginfo = (array) bigbluebuttonbn_wrap_xml_load_file(
        bigbluebuttonbn_bigbluebutton_action_url('getMeetingInfo', ['meetingID' => $meetingid])
      );
    $cache->set($meetingid, array('creation_time' => time(), 'meeting_info' => json_encode($meetinginfo)));

    return $meetinginfo;
}

/**
 * @param string $recordingid
 * @param string $bigbluebuttonbnid
 * @param boolean $publish
 */
function bigbluebuttonbn_publish_recording_imported($recordingid, $bigbluebuttonbnid, $publish = true) {
    global $DB;

    // Locate the record to be updated.
    $records = $DB->get_records('bigbluebuttonbn_logs', array('bigbluebuttonbnid' => $bigbluebuttonbnid,
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

function bigbluebuttonbn_delete_recording_imported($recordingid, $bigbluebuttonbnid) {
    global $DB;

    // Locate the record to be updated.
    $records = $DB->get_records('bigbluebuttonbn_logs', array('bigbluebuttonbnid' => $bigbluebuttonbnid,
        'log' => BIGBLUEBUTTONBN_LOG_EVENT_IMPORT));

    foreach ($records as $key => $record) {
        $meta = json_decode($record->meta, true);
        if ($recordingid == $meta['recording']['recordID']) {
            // Execute delete.
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

function bigbluebuttonbn_add_error($oldmsg, $newmsg = '') {
    $error = $oldmsg;

    if (!empty($newmsg)) {
        if (!empty($error)) {
            $error .= ' ';
        }
        $error .= $newmsg;
    }

    return $error;
}

/**
 * @param string $meetingid
 * @param string $configxml
 */
function bigbluebuttonbn_set_config_xml_params($meetingid, $configxml) {
    $params = 'configXML='.urlencode($configxml).'&meetingID='.urlencode($meetingid);
    $configxmlparams = $params.'&checksum='.sha1('setConfigXML'.$params.bigbluebuttonbn_get_cfg_shared_secret());

    return $configxmlparams;
}

/**
 * @param string $meetingid
 * @param string $configxml
 */
function bigbluebuttonbn_set_config_xml($meetingid, $configxml) {
    $urldefaultconfig = bigbluebuttonbn_get_cfg_server_url().'api/setConfigXML?';
    $configxmlparams = bigbluebuttonbn_set_config_xml_params($meetingid, $configxml);
    $xml = bigbluebuttonbn_wrap_xml_load_file($urldefaultconfig, BIGBLUEBUTTONBN_METHOD_POST,
        $configxmlparams, 'application/x-www-form-urlencoded');

    return $xml;
}

/**
 * @param string $meetingid
 * @param string $configxml
 */
function bigbluebuttonbn_set_config_xml_array($meetingid, $configxml) {
    $configxml = bigbluebuttonbn_setConfigXML($meetingid, $configxml);
    $configxmlarray = (array) $configxml;
    if ($configxmlarray['returncode'] != 'SUCCESS') {
        //debugging('BigBlueButton was not able to set the custom config.xml file', DEBUG_DEVELOPER);
        return '';
    }

    return $configxmlarray['configToken'];
}

function bigbluebuttonbn_get_recording_data_row($bbbsession, $recording, $tools = ['publishing', 'deleting']) {
    global $USER;

    $row = null;

    if ($bbbsession['managerecordings'] || $recording['published'] == 'true') {
        $row = new stdClass();

        // Set recording_types.
        $row->recording = bigbluebuttonbn_get_recording_data_row_types($recording);

        // Set activity name and description.
        $row->activity = bigbluebuttonbn_get_recording_data_row_meta_activity($recording);
        $row->description = bigbluebuttonbn_get_recording_data_row_meta_description($recording);

        // Set recording_preview.
        $row->preview = bigbluebuttonbn_get_recording_data_row_preview($recording);

        // Set date.
        $starttime = isset($recording['startTime']) ? floatval($recording['startTime']) : 0;
        $starttime = $starttime - ($starttime % 1000);
        $row->date = floatval($recording['startTime']);

        // Set formatted date.
        $dateformat = get_string('strftimerecentfull', 'langconfig').' %Z';
        $row->date_formatted = userdate($starttime / 1000, $dateformat, usertimezone($USER->timezone));

        // Set formatted duration.
        $firstplayback = array_values($recording['playbacks'])[0];
        $length = isset($firstplayback['length']) ? $firstplayback['length'] : 0;
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
        $manageaction = 'publish';
        $managetag = 'show';
        if ($recording['published'] == 'true') {
            $manageaction = 'unpublish';
            $managetag = 'hide';
        }
        $actionbar .= bigbluebuttonbn_actionbar_render($manageaction, $managetag, $recording);
    }

    if (in_array('deleting', $tools)) {
        $manageaction = $managetag = 'delete';
        $actionbar .= bigbluebuttonbn_actionbar_render($manageaction, $managetag, $recording);
    }

    if (in_array('importing', $tools)) {
        $manageaction = $managetag = 'import';
        $actionbar .= bigbluebuttonbn_actionbar_render($manageaction, $managetag, $recording);
    }

    return $actionbar;
}

function bigbluebuttonbn_get_recording_data_row_preview($recording) {
    $recordingpreview = '';
    foreach ($recording['playbacks'] as $playback) {
        if (isset($playback['preview'])) {
            foreach ($playback['preview'] as $image) {
                $recordingpreview .= html_writer::empty_tag('img',
                    array('src' => $image['url'], 'class' => 'thumbnail'));
            }
            $recordingpreview .= html_writer::empty_tag('br');
            $recordingpreview .= html_writer::tag('div',
                get_string('view_recording_preview_help', 'bigbluebuttonbn'), array('class' => 'text-muted small'));
            break;
        }
    }

    return $recordingpreview;
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

    $recordingtypes = '<div id="playbacks-'.$recording['recordID'].'" '.$attributes.' '.$visibility.'>';
    foreach ($recording['playbacks'] as $playback) {
        $recordingtypes .= $OUTPUT->action_link($playback['url'], get_string('view_recording_format_'.$playback['type'],
            'bigbluebuttonbn'), null, array('title' => get_string('view_recording_format_'.$playback['type'],
            'bigbluebuttonbn'), 'target' => '_new')).'&#32;';
    }
    $recordingtypes .= '</div>';

    return $recordingtypes;
}

function bigbluebuttonbn_get_recording_data_row_meta_activity($recording) {
    if (isset($recording['meta_contextactivity'])) {
        return htmlentities($recording['meta_contextactivity']);
    } else if (isset($recording['meta_bbb-recording-name'])) {
        return htmlentities($recording['meta_bbb-recording-name']);
    }

    return htmlentities($recording['meetingName']);
}

function bigbluebuttonbn_get_recording_data_row_meta_description($recording) {
    $metadescription = html_writer::start_tag('div', array('class' => 'col-md-20'));
    if (isset($recording['meta_contextactivitydescription']) &&
        trim($recording['meta_contextactivitydescription']) != '') {
        $metadescription .= htmlentities($recording['meta_contextactivitydescription']);
    } else if (isset($recording['meta_bbb-recording-description']) &&
               trim($recording['meta_bbb-recording-description']) != '') {
        $metadescription .= htmlentities($recording['meta_bbb-recording-description']);
    }
    $metadescription .= html_writer::end_tag('div');

    return $metadescription;
}

function bigbluebuttonbn_actionbar_render($manageaction, $managetag, $recording) {
    global $OUTPUT;

    $onclick = 'M.mod_bigbluebuttonbn.broker.recordingAction("'.$manageaction.'", "'.
        $recording['recordID'].'", "'.$recording['meetingID'].'");';
    if (bigbluebuttonbn_get_cfg_recording_icons_enabled()) {
        // With icon for $manageaction.
        $iconattributes = array('id' => 'recording-btn-'.$manageaction.'-'.$recording['recordID']);
        $icon = new pix_icon('i/'.$managetag, get_string($managetag), 'moodle', $iconattributes);
        $linkattributes = array('id' => 'recording-link-'.$manageaction.'-'.$recording['recordID'],
            'onclick' => $onclick);

        return $OUTPUT->action_icon('#', $icon, null, $linkattributes, false);
    }

    // With text for $manageaction.
    $linkattributes = array('title' => get_string($managetag), 'class' => 'btn btn-xs btn-danger',
        'onclick' => $onclick);

    return $OUTPUT->action_link('#', get_string($manageaction), null, $linkattributes);
}

function bigbluebuttonbn_get_recording_columns($bbbsession) {
    // Set strings to show.
    $recording = get_string('view_recording_recording', 'bigbluebuttonbn');
    $activity = get_string('view_recording_activity', 'bigbluebuttonbn');
    $description = get_string('view_recording_description', 'bigbluebuttonbn');
    $preview = get_string('view_recording_preview', 'bigbluebuttonbn');
    $date = get_string('view_recording_date', 'bigbluebuttonbn');
    $duration = get_string('view_recording_duration', 'bigbluebuttonbn');
    $actionbar = get_string('view_recording_actionbar', 'bigbluebuttonbn');

    // Initialize table headers.
    $recordingsbncolumns = array(
        array('key' => 'recording', 'label' => $recording, 'width' => '125px', 'allowHTML' => true),
        array('key' => 'activity', 'label' => $activity, 'sortable' => true, 'width' => '175px', 'allowHTML' => true),
        array('key' => 'description', 'label' => $description, 'width' => '250px', 'sortable' => true,
            'width' => '250px', 'allowHTML' => true),
        array('key' => 'preview', 'label' => $preview, 'width' => '250px', 'allowHTML' => true),
        array('key' => 'date', 'label' => $date, 'sortable' => true, 'width' => '225px', 'allowHTML' => true),
        array('key' => 'duration', 'label' => $duration, 'width' => '50px'),
        );

    if ($bbbsession['managerecordings']) {
        array_push($recordingsbncolumns, array('key' => 'actionbar', 'label' => $actionbar, 'width' => '100px',
            'allowHTML' => true));
    }

    return $recordingsbncolumns;
}

function bigbluebuttonbn_get_recording_data($bbbsession, $recordings, $tools = ['publishing', 'deleting']) {
    $tabledata = array();

    // Build table content.
    if (isset($recordings) && !array_key_exists('messageKey', $recordings)) {
        // There are recordings for this meeting.
        foreach ($recordings as $recording) {
            $row = bigbluebuttonbn_get_recording_data_row($bbbsession, $recording, $tools);
            if ($row != null) {
                array_push($tabledata, $row);
            }
        }
    }

    return $tabledata;
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

            $rowdata = bigbluebuttonbn_get_recording_data_row($bbbsession, $recording, $tools);
            if ($rowdata != null) {
                $rowdata->date_formatted = str_replace(' ', '&nbsp;', $rowdata->date_formatted);
                $row->cells = array($rowdata->recording, $rowdata->activity, $rowdata->description,
                    $rowdata->preview, $rowdata->date_formatted, $rowdata->duration_formatted);
                if ($bbbsession['managerecordings']) {
                    $row->cells[] = $rowdata->actionbar;
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
    $messagetext = '<p>'.get_string('email_body_recording_ready_for', 'bigbluebuttonbn').' '.
        $msg->activity_type.' &quot;'.$msg->activity_title.'&quot; '.
        get_string('email_body_recording_ready_is_ready', 'bigbluebuttonbn').'.</p>';

    bigbluebuttonbn_send_notification($sender, $bigbluebuttonbn, $messagetext);
}

function bigbluebuttonbn_server_offers_bn_capabilities() {
    // Validates if the server may have extended capabilities.
    $parsedurl = parse_url(bigbluebuttonbn_get_cfg_server_url());
    if (!isset($parsedurl['host'])) {
        return false;
    }

    $h = $parsedurl['host'];
    $hends = explode('.', $h);
    $hendslength = count($hends);

    return ($hends[$hendslength - 1] == 'com' && $hends[$hendslength - 2] == 'blindsidenetworks');
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
            'unpublish_confirmation_warning_s' => get_string('view_recording_unpublish_confirmation_warning_s',
                'bigbluebuttonbn'),
            'unpublish_confirmation_warning_p' => get_string('view_recording_unpublish_confirmation_warning_p',
                'bigbluebuttonbn'),
            'delete_confirmation' => get_string('view_recording_delete_confirmation', 'bigbluebuttonbn'),
            'delete_confirmation_warning_s' => get_string('view_recording_delete_confirmation_warning_s',
                'bigbluebuttonbn'),
            'delete_confirmation_warning_p' => get_string('view_recording_delete_confirmation_warning_p',
                'bigbluebuttonbn'),
            'import_confirmation' => get_string('view_recording_import_confirmation', 'bigbluebuttonbn'),
            'conference_ended' => get_string('view_message_conference_has_ended', 'bigbluebuttonbn'),
            'conference_not_started' => get_string('view_message_conference_not_started', 'bigbluebuttonbn'),
    );

    return $locales;
}

/**
 * @return string
 */
function bigbluebuttonbn_get_cfg_server_url() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['server_url'])) {
        return trim(trim($CFG->bigbluebuttonbn['server_url']), '/').'/';
    }

    if (isset($CFG->bigbluebuttonbn_server_url)) {
        return trim(trim($CFG->bigbluebuttonbn_server_url), '/').'/';
    }

    if (isset($CFG->BigBlueButtonBNServerURL)) {
        return trim(trim($CFG->BigBlueButtonBNServerURL), '/').'/';
    }

    return  BIGBLUEBUTTONBN_DEFAULT_SERVER_URL;
}

/**
 * @return string
 */
function bigbluebuttonbn_get_cfg_shared_secret() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['shared_secret'])) {
        return trim($CFG->bigbluebuttonbn['shared_secret']);
    }

    if (isset($CFG->bigbluebuttonbn_shared_secret)) {
        return trim($CFG->bigbluebuttonbn_shared_secret);
    }

    if (isset($CFG->BigBlueButtonBNSecuritySalt)) {
        return trim($CFG->BigBlueButtonBNSecuritySalt);
    }

    return  BIGBLUEBUTTONBN_DEFAULT_SHARED_SECRET;
}

/**
 * @return boolean
 */
function bigbluebuttonbn_get_cfg_voicebridge_editable() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['voicebridge_editable'])) {
        return $CFG->bigbluebuttonbn['voicebridge_editable'];
    }

    if (isset($CFG->bigbluebuttonbn_voicebridge_editable)) {
        return $CFG->bigbluebuttonbn_voicebridge_editable;
    }

    return  false;
}

/**
 * @return boolean
 */
function bigbluebuttonbn_get_cfg_recording_default() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['recording_default'])) {
        return $CFG->bigbluebuttonbn['recording_default'];
    }

    if (isset($CFG->bigbluebuttonbn_recording_default)) {
        return $CFG->bigbluebuttonbn_recording_default;
    }

    return  true;
}

/**
 * @return string
 */
function bigbluebuttonbn_get_cfg_recording_editable() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['recording_editable'])) {
        return $CFG->bigbluebuttonbn['recording_editable'];
    }

    if (isset($CFG->bigbluebuttonbn_recording_editable)) {
        return $CFG->bigbluebuttonbn_recording_editable;
    }

    return  true;
}

/**
 * @return boolean
 */
function bigbluebuttonbn_get_cfg_recording_tagging_default() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['recordingtagging_default'])) {
        return $CFG->bigbluebuttonbn['recordingtagging_default'];
    }

    if (isset($CFG->bigbluebuttonbn_recordingtagging_default)) {
        return $CFG->bigbluebuttonbn_recordingtagging_default;
    }

    return  false;
}

/**
 * @return boolean
 */
function bigbluebuttonbn_get_cfg_recording_tagging_editable() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['recordingtagging_editable'])) {
        return $CFG->bigbluebuttonbn['recordingtagging_editable'];
    }

    if (isset($CFG->bigbluebuttonbn_recordingtagging_editable)) {
        return $CFG->bigbluebuttonbn_recordingtagging_editable;
    }

    return  false;
}

/**
 * @return boolean
 */
function bigbluebuttonbn_get_cfg_recording_icons_enabled() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['recording_icons_enabled'])) {
        return $CFG->bigbluebuttonbn['recording_icons_enabled'];
    }

    if (isset($CFG->bigbluebuttonbn_recording_icons_enabled)) {
        return $CFG->bigbluebuttonbn_recording_icons_enabled;
    }

    return  true;
}

/**
 * @return boolean
 */
function bigbluebuttonbn_get_cfg_importrecordings_enabled() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['importrecordings_enabled'])) {
        return $CFG->bigbluebuttonbn['importrecordings_enabled'];
    }

    if (isset($CFG->bigbluebuttonbn_importrecordings_enabled)) {
        return $CFG->bigbluebuttonbn_importrecordings_enabled;
    }

    return  false;
}

/**
 * @return boolean
 */
function bigbluebuttonbn_get_cfg_importrecordings_from_deleted_activities_enabled() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['importrecordings_from_deleted_activities_enabled'])) {
        return $CFG->bigbluebuttonbn['importrecordings_from_deleted_activities_enabled'];
    }

    if (isset($CFG->bigbluebuttonbn_importrecordings_from_deleted_activities_enabled)) {
        return $CFG->bigbluebuttonbn_importrecordings_from_deleted_activities_enabled;
    }

    return  false;
}

/**
 * @return boolean
 */
function bigbluebuttonbn_get_cfg_waitformoderator_default() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['waitformoderator_default'])) {
        return $CFG->bigbluebuttonbn['waitformoderator_default'];
    }

    if (isset($CFG->bigbluebuttonbn_waitformoderator_default)) {
        return $CFG->bigbluebuttonbn_waitformoderator_default;
    }

    return  false;
}

/**
 * @return boolean
 */
function bigbluebuttonbn_get_cfg_waitformoderator_editable() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['waitformoderator_editable'])) {
        return $CFG->bigbluebuttonbn['waitformoderator_editable'];
    }

    if (isset($CFG->bigbluebuttonbn_waitformoderator_editable)) {
        return $CFG->bigbluebuttonbn_waitformoderator_editable;
    }

    return  true;
}

/**
 * @return number
 */
function bigbluebuttonbn_get_cfg_waitformoderator_ping_interval() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['waitformoderator_ping_interval'])) {
        return $CFG->bigbluebuttonbn['waitformoderator_ping_interval'];
    }

    if (isset($CFG->bigbluebuttonbn_waitformoderator_ping_interval)) {
        return $CFG->bigbluebuttonbn_waitformoderator_ping_interval;
    }

    return  15;
}

/**
 * @return number
 */
function bigbluebuttonbn_get_cfg_waitformoderator_cache_ttl() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['waitformoderator_cache_ttl'])) {
        return $CFG->bigbluebuttonbn['waitformoderator_cache_ttl'];
    }

    if (isset($CFG->bigbluebuttonbn_waitformoderator_cache_ttl)) {
        return $CFG->bigbluebuttonbn_waitformoderator_cache_ttl;
    }

    return  60;
}

/**
 * @return number
 */
function bigbluebuttonbn_get_cfg_userlimit_default() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['userlimit_default'])) {
        return $CFG->bigbluebuttonbn['userlimit_default'];
    }

    if (isset($CFG->bigbluebuttonbn_userlimit_default)) {
        return $CFG->bigbluebuttonbn_userlimit_default;
    }

    return  0;
}

/**
 * @return boolean
 */
function bigbluebuttonbn_get_cfg_userlimit_editable() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['userlimit_editable'])) {
        return $CFG->bigbluebuttonbn['userlimit_editable'];
    }

    if (isset($CFG->bigbluebuttonbn_userlimit_editable)) {
        return $CFG->bigbluebuttonbn_userlimit_editable;
    }

    return  false;
}

/**
 * @return boolean
 */
function bigbluebuttonbn_get_cfg_preuploadpresentation_enabled() {
    global $CFG;

    if (!extension_loaded('curl')) {
        return false;
    }

    if (isset($CFG->bigbluebuttonbn['preuploadpresentation_enabled'])) {
        return $CFG->bigbluebuttonbn['preuploadpresentation_enabled'];
    }

    if (isset($CFG->bigbluebuttonbn_preuploadpresentation_enabled)) {
        return $CFG->bigbluebuttonbn_preuploadpresentation_enabled;
    }

    return  false;
}

/**
 * @return boolean
 */
function bigbluebuttonbn_get_cfg_sendnotifications_enabled() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['sendnotifications_enabled'])) {
        return $CFG->bigbluebuttonbn['sendnotifications_enabled'];
    }

    if (isset($CFG->bigbluebuttonbn_sendnotifications_enabled)) {
        return $CFG->bigbluebuttonbn_sendnotifications_enabled;
    }

    return  false;
}

/**
 * @return boolean
 */
function bigbluebuttonbn_get_cfg_recordingready_enabled() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['recordingready_enabled'])) {
        return $CFG->bigbluebuttonbn['recordingready_enabled'];
    }

    if (isset($CFG->bigbluebuttonbn_recordingready_enabled)) {
        return $CFG->bigbluebuttonbn_recordingready_enabled;
    }

    return  false;
}

/**
 * @return boolean
 */
function bigbluebuttonbn_get_cfg_meetingevents_enabled() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['meetingevents_enabled'])) {
        return $CFG->bigbluebuttonbn['meetingevents_enabled'];
    }

    if (isset($CFG->bigbluebuttonbn_meetingevents_enabled)) {
        return $CFG->bigbluebuttonbn_meetingevents_enabled;
    }

    return  false;
}

/**
 * @return string
 */
function bigbluebuttonbn_get_cfg_moderator_default() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['moderator_default'])) {
        return $CFG->bigbluebuttonbn['moderator_default'];
    }

    if (isset($CFG->bigbluebuttonbn_moderator_default)) {
        return $CFG->bigbluebuttonbn_moderator_default;
    }

    return  'owner';
}

/**
 * @return boolean
 */
function bigbluebuttonbn_get_cfg_scheduled_duration_enabled() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['scheduled_duration_enabled'])) {
        return $CFG->bigbluebuttonbn['scheduled_duration_enabled'];
    }

    if (isset($CFG->bigbluebuttonbn_scheduled_duration_enabled)) {
        return $CFG->bigbluebuttonbn_scheduled_duration_enabled;
    }

    return  false;
}

/**
 * @return number
 */
function bigbluebuttonbn_get_cfg_scheduled_duration_compensation() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['scheduled_duration_compensation'])) {
        return $CFG->bigbluebuttonbn['scheduled_duration_compensation'];
    }

    if (isset($CFG->bigbluebuttonbn_scheduled_duration_compensation)) {
        return $CFG->bigbluebuttonbn_scheduled_duration_compensation;
    }

    return  10;
}

/**
 * @return number
 */
function bigbluebuttonbn_get_cfg_scheduled_pre_opening() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['scheduled_pre_opening'])) {
        return $CFG->bigbluebuttonbn['scheduled_pre_opening'];
    }

    if (isset($CFG->bigbluebuttonbn_scheduled_pre_opening)) {
        return $CFG->bigbluebuttonbn_scheduled_pre_opening;
    }

    return  10;
}

/**
 * @return boolean
 */
function bigbluebuttonbn_get_cfg_recordings_html_default() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['recordings_html_default'])) {
        return $CFG->bigbluebuttonbn['recordings_html_default'];
    }

    if (isset($CFG->bigbluebuttonbn_recordings_html_default)) {
        return $CFG->bigbluebuttonbn_recordings_html_default;
    }

    return  false;
}

/**
 * @return boolean
 */
function bigbluebuttonbn_get_cfg_recordings_html_editable() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['recordings_html_editable'])) {
        return $CFG->bigbluebuttonbn['recordings_html_editable'];
    }

    if (isset($CFG->bigbluebuttonbn_recordings_html_editable)) {
        return $CFG->bigbluebuttonbn_recordings_html_editable;
    }

    return  false;
}

/**
 * @return boolean
 */
function bigbluebuttonbn_get_cfg_recordings_deleted_activities_default() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['recordings_deleted_activities_default'])) {
        return $CFG->bigbluebuttonbn['recordings_deleted_activities_default'];
    }

    if (isset($CFG->bigbluebuttonbn_recordings_deleted_activities_default)) {
        return $CFG->bigbluebuttonbn_recordings_deleted_activities_default;
    }

    return  false;
}

/**
 * @return boolean
 */
function bigbluebuttonbn_get_cfg_recordings_deleted_activities_editable() {
    global $CFG;

    if (isset($CFG->bigbluebuttonbn['recordings_deleted_activities_editable'])) {
        return $CFG->bigbluebuttonbn['recordings_deleted_activities_editable'];
    }

    if (isset($CFG->bigbluebuttonbn_recordings_deleted_activities_editable)) {
        return $CFG->bigbluebuttonbn_recordings_deleted_activities_editable;
    }

    return  false;
}

/**
 * @return array
 */
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
        // It includes the name of the site as a course (category 0), so remove the first one.
        unset($courses['1']);
    } else {
        $courses = enrol_get_users_courses($bbbsession['userID'], false, 'id,shortname,fullname');
    }

    $coursesforselect = [];
    foreach ($courses as $course) {
        $coursesforselect[$course->id] = $course->fullname;
    }

    return $coursesforselect;
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

function bigbluebuttonbn_html2text($html, $len) {
    $text = strip_tags($html);
    $text = str_replace('&nbsp;', ' ', $text);
    $text = substr($text, 0, $len);
    if (strlen($text) > $len) {
        $text .= '...';
    }

    return $text;
}

function bigbluebuttonbn_get_tags($id) {
    $tags = '';
    $tagsarray = core_tag_tag::get_item_tags_array('core', 'course_modules', $id);
    foreach ($tagsarray as $tag) {
        $tags .= ($tags == '') ? $tag : ','.$tag;
    }

    return $tags;
}

/**
 * helper function to retrieve recordings from the BigBlueButton. The references are stored as events
 * in bigbluebuttonbn_logs.
 *
 * @param string $courseid
 * @param string $bigbluebuttonbnid
 * @param bool   $subset
 * @param bool   $includedeleted
 *
 * @return associative array containing the recordings indexed by recordID, each recording is also a
 * non sequential associative array itself that corresponds to the actual recording in BBB
 */
function bigbluebuttonbn_get_recordings($courseid, $bigbluebuttonbnid = null,
        $subset = true, $includedeleted = false) {
    global $DB;

    // Gather the bigbluebuttonbnids whose meetingids should be included in the getRecordings request'.
    $select = "id <> '{$bigbluebuttonbnid}' AND course = '{$courseid}'";
    $selectdeleted = "courseid = '{$courseid}' AND bigbluebuttonbnid <> '{$bigbluebuttonbnid}' AND log = '".
        BIGBLUEBUTTONBN_LOG_EVENT_DELETE."' AND meta like '%has_recordings%' AND meta like '%true%'";
    if ($bigbluebuttonbnid === null) {
        $select = "course = '{$courseid}'";
        $selectdeleted = "courseid = '{$courseid}' AND log = '".BIGBLUEBUTTONBN_LOG_EVENT_DELETE.
            "' AND meta like '%has_recordings%' AND meta like '%true%'";
    } else if ($subset) {
        $select = "id = '{$bigbluebuttonbnid}'";
        $selectdeleted = "bigbluebuttonbnid = '{$bigbluebuttonbnid}' AND log = '".BIGBLUEBUTTONBN_LOG_EVENT_DELETE.
            "' AND meta like '%has_recordings%' AND meta like '%true%'";
    }
    $bigbluebuttonbns = $DB->get_records_select_menu('bigbluebuttonbn', $select, null, 'id', 'id, meetingid');

    /* Consider logs from deleted bigbluebuttonbn instances whose meetingids should be included in
     * the getRecordings request. */
    if ($includedeleted) {
        $bigbluebuttonbnsdel = $DB->get_records_select_menu('bigbluebuttonbn_logs', $selectdeleted, null,
            'bigbluebuttonbnid', 'bigbluebuttonbnid, meetingid');
        if (!empty($bigbluebuttonbnsdel)) {
            // Merge bigbluebuttonbnis from deleted instances, only keys are relevant.
            // Artimetic merge is used in order to keep the keys.
            $bigbluebuttonbns += $bigbluebuttonbnsdel;
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
        $recordings = bigbluebuttonbn_get_recordings_array(array_keys($records));
    }

    // Get recording links.
    $recordingsimported = bigbluebuttonbn_get_recordings_imported_array($courseid, $bigbluebuttonbnid, $subset);

    /* Perform aritmetic add instead of merge so the imported recordings corresponding to existent recordings
     * are not included. */
    return $recordings + $recordingsimported;
}

function bigbluebuttonbn_unset_existent_recordings_already_imported($recordings, $courseid, $bigbluebuttonbnid) {
    $recordingsimported = bigbluebuttonbn_get_recordings_imported_array($courseid, $bigbluebuttonbnid, true);

    foreach ($recordings as $key => $recording) {
        if (isset($recordingsimported[$recording['recordID']])) {
            unset($recordings[$key]);
        }
    }

    return $recordings;
}

function bigbluebuttonbn_get_count_recording_imported_instances($recordid) {
    global $DB;

    $sql = 'SELECT * FROM {bigbluebuttonbn_logs} WHERE log = ? AND meta LIKE ? AND meta LIKE ?';

    return $DB->count_records_sql($sql, array(BIGBLUEBUTTONBN_LOG_EVENT_IMPORT, '%recordID%', "%{$recordid}%"));
}

function bigbluebuttonbn_get_recording_imported_instances($recordid) {
    global $DB;

    $sql = 'SELECT * FROM {bigbluebuttonbn_logs} WHERE log = ? AND meta LIKE ? AND meta LIKE ?';
    $recordingsimported = $DB->get_records_sql($sql, array(BIGBLUEBUTTONBN_LOG_EVENT_IMPORT, '%recordID%',
        "%{$recordid}%"));

    return $recordingsimported;
}

function bigbluebuttonbn_get_instance_type_profiles() {
    $instanceprofiles = array(
            array('id' => BIGBLUEBUTTONBN_TYPE_ALL, 'name' => get_string('instance_type_default', 'bigbluebuttonbn'),
                'features' => array('all')),
            array('id' => BIGBLUEBUTTONBN_TYPE_ROOM_ONLY, 'name' => get_string('instance_type_room_only', 'bigbluebuttonbn'),
                'features' => array('showroom', 'welcomemessage', 'voicebridge', 'waitformoderator', 'userlimit', 'recording',
                    'recordingtagging', 'sendnotifications', 'preuploadpresentation', 'permissions', 'schedule', 'groups')),
            array('id' => BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY, 'name' => get_string('instance_type_recording_only',
                'bigbluebuttonbn'), 'features' => array('showrecordings', 'importrecordings')),
    );

    return $instanceprofiles;
}

function bigbluebuttonbn_get_instance_profiles_array($profiles = null) {
    if (is_null($profiles) || empty($profiles)) {
        $profiles = bigbluebuttonbn_get_instance_type_profiles();
    }

    $profilesarray = array();

    foreach ($profiles as $profile) {
        $profilesarray += array("{$profile['id']}" => $profile['name']);
    }

    return $profilesarray;
}

function bigbluebuttonbn_format_activity_time($time) {
    $activitytime = '';
    if ($time) {
        $activitytime = calendar_day_representation($time).' '.
          get_string('mod_form_field_notification_msg_at', 'bigbluebuttonbn').' '.
          calendar_time_representation($time);
    }

    return $activitytime;
}

function bigbluebuttonbn_recordings_enabled() {
    global $CFG;

    return !(isset($CFG->bigbluebuttonbn['recording_default)']) &&
             isset($CFG->bigbluebuttonbn['recording_editable']));
}
