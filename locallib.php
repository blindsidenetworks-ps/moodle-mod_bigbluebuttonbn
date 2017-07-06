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
const BIGBLUEBUTTON_EVENT_ACTIVITY_MANAGEMENT_VIEWED = 'activity_management_viewed';
const BIGBLUEBUTTON_EVENT_LIVE_SESSION = 'live_session';
const BIGBLUEBUTTON_EVENT_MEETING_CREATED = 'meeting_created';
const BIGBLUEBUTTON_EVENT_MEETING_ENDED = 'meeting_ended';
const BIGBLUEBUTTON_EVENT_MEETING_JOINED = 'meeting_joined';
const BIGBLUEBUTTON_EVENT_MEETING_LEFT = 'meeting_left';
const BIGBLUEBUTTON_EVENT_RECORDING_DELETED = 'recording_deleted';
const BIGBLUEBUTTON_EVENT_RECORDING_IMPORTED = 'recording_imported';
const BIGBLUEBUTTON_EVENT_RECORDING_PROTECTED = 'recording_protected';
const BIGBLUEBUTTON_EVENT_RECORDING_PUBLISHED = 'recording_published';
const BIGBLUEBUTTON_EVENT_RECORDING_UNPROTECTED = 'recording_unprotected';
const BIGBLUEBUTTON_EVENT_RECORDING_UNPUBLISHED = 'recording_unpublished';
const BIGBLUEBUTTON_EVENT_RECORDING_EDITED = 'recording_edited';
const BIGBLUEBUTTON_EVENT_RECORDING_VIEWED = 'recording_viewed';

/**
 * @param array  $bbbsession
 * @param string $event
 * @param array  $overrides
 * @param string $meta
 */
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

/**
 * @param string $meetingid
 * @param string $username
 * @param string $pw
 * @param string $logouturl
 * @param string $configtoken
 * @param string $userid
 */
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

    return \mod_bigbluebuttonbn\locallib\bigbluebutton::action_url('join', $data);
}

/**
 * @param string $recordid
 * @param array  $metadata
 */
function bigbluebuttonbn_get_update_recordings_url($recordid, $metadata = array()) {
    return \mod_bigbluebuttonbn\locallib\bigbluebutton::action_url('updateRecordings', ['recordID' => $recordid], $metadata);
}

/**
 * @param array  $data
 * @param array  $metadata
 * @param string $pname
 * @param string $purl
 */
function bigbluebuttonbn_get_create_meeting_array($data, $metadata = array(), $pname = null, $purl = null) {
    $createmeetingurl = \mod_bigbluebuttonbn\locallib\bigbluebutton::action_url('create', $data, $metadata);
    $method = BIGBLUEBUTTONBN_METHOD_GET;
    $data = null;

    if (!is_null($pname) && !is_null($purl)) {
        $method = BIGBLUEBUTTONBN_METHOD_POST;
        $data = "<?xml version='1.0' encoding='UTF-8'?><modules><module name='" . $pname . "'><document url='".
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
    $xml = bigbluebuttonbn_wrap_xml_load_file(\mod_bigbluebuttonbn\locallib\bigbluebutton::action_url('getMeetings'));

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
        // Either failure or success without meetings.
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
        \mod_bigbluebuttonbn\locallib\bigbluebutton::action_url('getMeetingInfo', ['meetingID' => $meetingid])
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
        // Either failure or success without meeting info.
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
function bigbluebuttonbn_get_recordings_array($meetingids, $recordingids = []) {
    $meetingidsarray = $meetingids;
    if (!is_array($meetingids)) {
        $meetingidsarray = explode(',', $meetingids);
    }

    // If $meetingidsarray is empty there is no need to go further.
    if (empty($meetingidsarray)) {
        return array();
    }
    $recordings = bigbluebuttonbn_get_recordings_array_fetch($meetingidsarray);

    // Filter recordings based on recordingIDs.
    $recordingidsarray = $recordingids;
    if (!is_array($recordingids)) {
        $recordingidsarray = explode(',', $recordingids);
    }

    if (empty($recordingidsarray)) {
        // No recording ids, no need to filter.
        return $recordings;
    }

    return bigbluebuttonbn_get_recordings_array_filter($recordingidsarray, $recordings);
}

/**
 * helper function to fetch recordings from a BigBlueButton server.
 *
 * @param array $meetingidsarray   array with meeting ids in the form array("mid1","mid2","mid3")
 *
 * @return associative array with recordings indexed by recordID, each recording is a non sequential associative array
 */
function bigbluebuttonbn_get_recordings_array_fetch($meetingidsarray) {

    $recordings = array();

    // Execute a paginated getRecordings request.
    $pages = floor(count($meetingidsarray) / 25) + 1;
    for ($page = 1; $page <= $pages; ++$page) {
        $mids = array_slice($meetingidsarray, ($page - 1) * 25, 25);
        // Do getRecordings is executed using a method GET (supported by all versions of BBB).
        $xml = bigbluebuttonbn_wrap_xml_load_file(
            \mod_bigbluebuttonbn\locallib\bigbluebutton::action_url('getRecordings', ['meetingID' => implode(',', $mids)])
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

    return $recordings;
}

function bigbluebuttonbn_get_recordings_array_filter($recordingidsarray, &$recordings) {

    foreach ($recordings as $key => $recording) {
        if (!in_array($recording['recordID'], $recordingidsarray)) {
            unset($recordings[$key]);
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

    $select = "courseid = '{$courseid}' AND bigbluebuttonbnid <> '{$bigbluebuttonbnid}' AND log = '" .
        BIGBLUEBUTTONBN_LOG_EVENT_IMPORT . "'";
    if ($bigbluebuttonbnid === null) {
        $select = "courseid = '{$courseid}' AND log = '" . BIGBLUEBUTTONBN_LOG_EVENT_IMPORT . "'";
    } else if ($subset) {
        $select = "bigbluebuttonbnid = '{$bigbluebuttonbnid}' AND log = '" . BIGBLUEBUTTONBN_LOG_EVENT_IMPORT . "'";
    }
    $recordsimported = $DB->get_records_select('bigbluebuttonbn_logs', $select);

    $recordsimportedarray = array();
    foreach ($recordsimported as $recordimported) {
        $meta = json_decode($recordimported->meta, true);
        $recording = $meta['recording'];
        // Override imported flag with actual ID.
        $recording['imported'] = $recordimported->id;
        /////////////////////////////////////////
        // MOCKUP TO BE DELETED BEFORE RELEASE
        // Force protected.
        $recording['protected'] = 'false';
        ////////////////////////////////////////
        $recordsimportedarray[$recording['recordID']] = $recording;
    }

    return $recordsimportedarray;
}

function bigbluebuttonbn_get_default_config_xml() {
    $xml = bigbluebuttonbn_wrap_xml_load_file(
        \mod_bigbluebuttonbn\locallib\bigbluebutton::action_url('getDefaultConfigXML')
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
    $metadataarray = bigbluebuttonbn_get_recording_array_meta(get_object_vars($recording->metadata));
    $recordingarray = array('recordID' => (string) $recording->recordID,
        'meetingID' => (string) $recording->meetingID, 'meetingName' => (string) $recording->name,
        'published' => (string) $recording->published, 'startTime' => (string) $recording->startTime,
        'endTime' => (string) $recording->endTime, 'playbacks' => $playbackarray);
    if (isset($recording->protected)) {
        $recordingarray['protected'] = (string) $recording->protected;
    }
    /////////////////////////////////////////
    // MOCKUP TO BE DELETED BEFORE RELEASE
    // Force protected.
    $recordingarray['protected'] = 'false';
    ////////////////////////////////////////
    return $recordingarray + $metadataarray;
}

function bigbluebuttonbn_get_recording_array_meta($metadata) {
    $metadataarray = array();
    foreach ($metadata as $key => $value) {
        if (is_object($value)) {
            $value = '';
        }
        $metadataarray['meta_'.$key] = $value;
    }
    return $metadataarray;
}

function bigbluebuttonbn_recording_build_sorter($a, $b) {
    if ($a['startTime'] < $b['startTime']) {
        return -1;
    }
    if ($a['startTime'] == $b['startTime']) {
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
            \mod_bigbluebuttonbn\locallib\bigbluebutton::action_url('deleteRecordings', ['recordID' => $id])
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
            \mod_bigbluebuttonbn\locallib\bigbluebutton::action_url('publishRecordings', ['recordID' => $id, 'publish' => $publish])
          );
        if ($xml && $xml->returncode != 'SUCCESS') {
            return false;
        }
    }

    return true;
}

/**
 * @param string $recordids
 * @param array $params ['key'=>param_key, 'value']
 */
function bigbluebuttonbn_update_recordings($recordids, $params) {
    $ids = explode(',', $recordids);
    foreach ($ids as $id) {
        $xml = bigbluebuttonbn_wrap_xml_load_file(
            \mod_bigbluebuttonbn\locallib\bigbluebutton::action_url('updateRecordings', ['recordID' => $id] + (array) $params)
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
        \mod_bigbluebuttonbn\locallib\bigbluebutton::action_url('end', ['meetingID' => $meetingid, 'password' => $modpw])
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
        \mod_bigbluebuttonbn\locallib\bigbluebutton::action_url('isMeetingRunning', ['meetingID' => $meetingid])
      );

    if ($xml && $xml->returncode == 'SUCCESS') {
        return ($xml->running == 'true');
    }

    return false;
}

function bigbluebuttonbn_get_server_version() {
    $xml = bigbluebuttonbn_wrap_xml_load_file(
        \mod_bigbluebuttonbn\locallib\bigbluebutton::action_url()
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

    if (extension_loaded('curl')) {
        $response = bigbluebuttonbn_wrap_xml_load_file_curl_request($url, $method, $data, $contenttype);

        if (!$response) {
            debugging('No response on wrap_simplexml_load_file', DEBUG_DEVELOPER);
            return null;
        }

        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);

            return $xml;
        } catch (Exception $e) {
            libxml_use_internal_errors($previous);
            $error = 'Caught exception: '.$e->getMessage();
            debugging($error, DEBUG_DEVELOPER);
            return null;
        }
    }

    // Alternative request non CURL based.
    $previous = libxml_use_internal_errors(true);
    try {
        $response = simplexml_load_file($url, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
        return $response;
    } catch (Exception $e) {
        $error = 'Caught exception: '.$e->getMessage();
        debugging($error, DEBUG_DEVELOPER);
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

function bigbluebuttonbn_get_users(context $context = null) {
    $users = (array) get_enrolled_users($context,'',0,'u.*',null,0,0,true);
    foreach ($users as $key => $value) {
        $users[$key] = fullname($value);
    }
    return $users;
}

function bigbluebuttonbn_get_users_select(context $context = null) {
    $users = (array) get_enrolled_users($context,'',0,'u.*',null,0,0,true);
    foreach ($users as $key => $value) {
        $users[$key] = array('id' => $value->id, 'name' => fullname($value));
    }
    return $users;
}

function bigbluebuttonbn_get_roles(context $context = null) {
    $roles = (array) role_get_names($context);
    foreach ($roles as $key => $value) {
        $roles[$key] = $value->localname;
    }
    return $roles;
}

function bigbluebuttonbn_get_roles_select(context $context = null) {
    $roles = (array) role_get_names($context);
    foreach ($roles as $key => $value) {
        $roles[$key] = array('id' => $value->id, 'name' => $value->localname);
    }
    return $roles;
}

function bigbluebuttonbn_get_role($id) {
    $roles = (array) role_get_names();
    if (is_numeric($id)) {
        return (object)$roles[$id];
    }
    foreach ($roles as $role) {
        if ($role->shortname == $id) {
            return $role;
        }
    }
}

function bigbluebuttonbn_role_unknown() {
    return array("id" => "0", "name" => "", "shortname" => "unknown", "description" => "", "sortorder" => "0", "archetype" => "guest", "localname" => "Unknown");
}

function bigbluebuttonbn_get_participant_data($context) {
    $data = array(
        'all' => array(
            'name' => get_string('mod_form_field_participant_list_type_all', 'bigbluebuttonbn'),
            'children' => []
          )
      );
    $data['role'] = array(
        'name' => get_string('mod_form_field_participant_list_type_role', 'bigbluebuttonbn'),
        'children' => bigbluebuttonbn_get_roles_select($context)
      );
    $data['user'] = array(
        'name' => get_string('mod_form_field_participant_list_type_user', 'bigbluebuttonbn'),
        'children' => bigbluebuttonbn_get_users_select($context)
      );
    return $data;
}

function bigbluebuttonbn_get_participant_list($bigbluebuttonbn, $context) {
    if ($bigbluebuttonbn == null) {
        return bigbluebuttonbn_get_participant_list_default($context);
    }
    return bigbluebuttonbn_get_participant_rules_encoded($bigbluebuttonbn);
}

function bigbluebuttonbn_get_participant_rules_encoded($bigbluebuttonbn) {
    $rules = json_decode($bigbluebuttonbn->participants, true);
    if (!is_array($rules)) {
        return array();
    }
    foreach ($rules as $key => $rule) {
        if ( $rule['selectiontype'] !== 'role' || is_numeric($rule['selectionid']) ) {
            continue;
        }
        $role = bigbluebuttonbn_get_role($rule['selectionid']);
        if ( $role == null ) {
            unset($rules[$key]);
            continue;
        }
        $rule['selectionid'] = $role->id;
        $rules[$key] = $rule;
    }
    return $rules;
}

function bigbluebuttonbn_get_participant_list_default($context) {
    global $USER;
    $participantlistarray = array();
    $participantlistarray[] = array(
        'selectiontype' => 'all',
        'selectionid' => 'all',
        'role' => BIGBLUEBUTTONBN_ROLE_VIEWER);
    $moderatordefaults = explode(',', \mod_bigbluebuttonbn\locallib\config::get('moderator_default'));
    foreach ($moderatordefaults as $moderatordefault) {
        if ($moderatordefault == 'owner') {
            if (is_enrolled($context, $USER->id)) {
                $participantlistarray[] = array(
                    'selectiontype' => 'user',
                    'selectionid' => $USER->id,
                    'role' => BIGBLUEBUTTONBN_ROLE_MODERATOR);
            }
            continue;
        }
        $participantlistarray[] = array(
              'selectiontype' => 'role',
              'selectionid' => $moderatordefault,
              'role' => BIGBLUEBUTTONBN_ROLE_MODERATOR);
    }
    return $participantlistarray;
}

function bigbluebuttonbn_get_participant_selection_data() {
    return [
        'type_options' => [
            'all' => get_string('mod_form_field_participant_list_type_all', 'bigbluebuttonbn'),
            'role' => get_string('mod_form_field_participant_list_type_role', 'bigbluebuttonbn'),
            'user' => get_string('mod_form_field_participant_list_type_user', 'bigbluebuttonbn'),
          ],
        'type_selected' => 'all',
        'options' => ['all' => '---------------'],
        'selected' => 'all',
      ];
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
    if (!is_array($participantlist)) {
        return false;
    }
    // Iterate participant rules.
    foreach ($participantlist as $participant) {
        if (bigbluebuttonbn_is_moderator_rule_validation($participant, $userid, $userroles)) {
            return true;
        }
    }
    return false;
}

function bigbluebuttonbn_is_moderator_rule_validation($participant, $userid, $userroles) {
    if ($participant->role == BIGBLUEBUTTONBN_ROLE_VIEWER) {
        return false;
    }
    // Looks for all configuration.
    if ($participant->selectiontype == 'all') {
        return true;
    }
    // Looks for users.
    if ($participant->selectiontype == 'user' && $participant->selectionid == $userid) {
        return true;
    }
    // Looks for roles.
    $role = bigbluebuttonbn_get_role($participant->selectionid);
    if (array_key_exists($role->id, $userroles)) {
        return true;
    }
    return false;
}

function bigbluebuttonbn_get_error_key($messagekey, $defaultkey = null) {
    if ($messagekey == 'checksumError') {
        return 'index_error_checksum';
    }
    if ($messagekey == 'maxConcurrent') {
        return 'view_error_max_concurrent';
    }
    return $defaultkey;
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
        $compensationtime = intval((int)\mod_bigbluebuttonbn\locallib\config::get('scheduled_duration_compensation'));
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

function bigbluebuttonbn_events() {
    return array(
        (string) BIGBLUEBUTTON_EVENT_ACTIVITY_VIEWED,
        (string) BIGBLUEBUTTON_EVENT_ACTIVITY_MANAGEMENT_VIEWED,
        (string) BIGBLUEBUTTON_EVENT_LIVE_SESSION,
        (string) BIGBLUEBUTTON_EVENT_MEETING_CREATED,
        (string) BIGBLUEBUTTON_EVENT_MEETING_ENDED,
        (string) BIGBLUEBUTTON_EVENT_MEETING_JOINED,
        (string) BIGBLUEBUTTON_EVENT_MEETING_LEFT,
        (string) BIGBLUEBUTTON_EVENT_RECORDING_DELETED,
        (string) BIGBLUEBUTTON_EVENT_RECORDING_IMPORTED,
        (string) BIGBLUEBUTTON_EVENT_RECORDING_PROTECTED,
        (string) BIGBLUEBUTTON_EVENT_RECORDING_PUBLISHED,
        (string) BIGBLUEBUTTON_EVENT_RECORDING_UNPROTECTED,
        (string) BIGBLUEBUTTON_EVENT_RECORDING_UNPUBLISHED,
        (string) BIGBLUEBUTTON_EVENT_RECORDING_EDITED,
        (string) BIGBLUEBUTTON_EVENT_RECORDING_VIEWED
    );
}

function bigbluebuttonbn_events_action() {
    return array(
        'view' => (string) BIGBLUEBUTTON_EVENT_ACTIVITY_VIEWED,
        'view_management' => (string) BIGBLUEBUTTON_EVENT_ACTIVITY_MANAGEMENT_VIEWED,
        'live_action' => (string) BIGBLUEBUTTON_EVENT_LIVE_SESSION,
        'meeting_create' => (string) BIGBLUEBUTTON_EVENT_MEETING_CREATED,
        'meeting_end' => (string) BIGBLUEBUTTON_EVENT_MEETING_ENDED,
        'meeting_join' => (string) BIGBLUEBUTTON_EVENT_MEETING_JOINED,
        'meeting_left' => (string) BIGBLUEBUTTON_EVENT_MEETING_LEFT,
        'recording_delete' => (string) BIGBLUEBUTTON_EVENT_RECORDING_DELETED,
        'recording_import' => (string) BIGBLUEBUTTON_EVENT_RECORDING_IMPORTED,
        'recording_protect' => (string) BIGBLUEBUTTON_EVENT_RECORDING_PROTECTED,
        'recording_publish' => (string) BIGBLUEBUTTON_EVENT_RECORDING_PUBLISHED,
        'recording_unprotect' => (string) BIGBLUEBUTTON_EVENT_RECORDING_UNPROTECTED,
        'recording_unpublish' => (string) BIGBLUEBUTTON_EVENT_RECORDING_UNPUBLISHED,
        'recording_edit' => (string) BIGBLUEBUTTON_EVENT_RECORDING_EDITED,
        'recording_play' => (string) BIGBLUEBUTTON_EVENT_RECORDING_VIEWED
    );
}

function bigbluebuttonbn_event_log_standard($eventtype, $bigbluebuttonbn, $cm, $options = []) {
    $events = bigbluebuttonbn_events();
    if (!in_array($eventtype, $events)) {
        // No log will be created.
        return;
    }
    $context = context_module::instance($cm->id);
    $eventproperties = array('context' => $context, 'objectid' => $bigbluebuttonbn->id);
    if (array_key_exists('timecreated', $options)) {
        $eventproperties['timecreated'] = $options['timecreated'];
    }
    if (array_key_exists('userid', $options)) {
        $eventproperties['userid'] = $options['userid'];
    }
    if (array_key_exists('other', $options)) {
        $eventproperties['other'] = $options['other'];
    }
    $event = call_user_func_array('\mod_bigbluebuttonbn\event\bigbluebuttonbn_'.$eventtype.'::create',
      array($eventproperties));
    $event->trigger();
}

function bigbluebuttonbn_event_log($eventtype, $bigbluebuttonbn, $cm, $options = []) {
    bigbluebuttonbn_event_log_standard($eventtype, $bigbluebuttonbn, $cm, $options);
}

function bigbluebuttonbn_live_session_event_log($event, $bigbluebuttonbn, $cm) {
    bigbluebuttonbn_event_log_standard(BIGBLUEBUTTON_EVENT_LIVE_SESSION, $bigbluebuttonbn, $cm,
        ['timecreated' => $event->timestamp, 'userid' => $event->user, 'other' => $event->event]);
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
    $cachettl = (int)\mod_bigbluebuttonbn\locallib\config::get('waitformoderator_cache_ttl');
    $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'meetings_cache');
    $result = $cache->get($meetingid);
    $now = time();
    if (!$forced && isset($result) && $now < ($result['creation_time'] + $cachettl)) {
        // Use the value in the cache.
        return (array) json_decode($result['meeting_info']);
    }
    // Ping again and refresh the cache.
    $meetinginfo = (array) bigbluebuttonbn_wrap_xml_load_file(
        \mod_bigbluebuttonbn\locallib\bigbluebutton::action_url('getMeetingInfo', ['meetingID' => $meetingid])
      );
    $cache->set($meetingid, array('creation_time' => time(), 'meeting_info' => json_encode($meetinginfo)));
    return $meetinginfo;
}

/**
 * @param string $id
 * @param boolean $publish
 */
function bigbluebuttonbn_publish_recording_imported($id, $publish = true) {
    global $DB;
    // Locate the record to be updated.
    $record = $DB->get_record('bigbluebuttonbn_logs', array('id' => $id));
    $meta = json_decode($record->meta, true);
    // Prepare data for the update.
    $meta['recording']['published'] = ($publish) ? 'true' : 'false';
    $record->meta = json_encode($meta);
    // Proceed with the update.
    $DB->update_record('bigbluebuttonbn_logs', $record);
    return true;
}

/**
 * @param string $id
 */
function bigbluebuttonbn_delete_recording_imported($id) {
    global $DB;
    // Execute delete.
    $DB->delete_records('bigbluebuttonbn_logs', array('id' => $id));
    return true;
}

/**
 * @param string $id
 * @param array $params ['key'=>param_key, 'value']
 */
function bigbluebuttonbn_update_recording_imported($id, $params) {
    global $DB;
    // Locate the record to be updated.
    $record = $DB->get_record('bigbluebuttonbn_logs', array('id' => $id));
    $meta = json_decode($record->meta, true);
    // Prepare data for the update.
    $meta['recording'] = $params + $meta['recording'];
    $record->meta = json_encode($meta);
    // Proceed with the update.
    if (!$DB->update_record('bigbluebuttonbn_logs', $record)) {
        return false;
    }
    return true;
}

/**
 * @param string $meetingid
 * @param string $configxml
 */
function bigbluebuttonbn_set_config_xml_params($meetingid, $configxml) {
    $params = 'configXML='.urlencode($configxml).'&meetingID='.urlencode($meetingid);
    $configxmlparams = $params.'&checksum='.sha1('setConfigXML'.$params.\mod_bigbluebuttonbn\locallib\config::get('shared_secret'));
    return $configxmlparams;
}

/**
 * @param string $meetingid
 * @param string $configxml
 */
function bigbluebuttonbn_set_config_xml($meetingid, $configxml) {
    $urldefaultconfig = \mod_bigbluebuttonbn\locallib\config::get('server_url').'api/setConfigXML?';
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
        debugging('BigBlueButton was not able to set the custom config.xml file', DEBUG_DEVELOPER);
        return '';
    }
    return $configxmlarray['configToken'];
}

function bigbluebuttonbn_get_recording_data_row($bbbsession, $recording, $tools = ['protect', 'publish', 'delete']) {
    global $USER;
    $row = null;
    $managerecordings = $bbbsession['managerecordings'];
    if ($managerecordings || $recording['published'] == 'true') {
        $row = new stdClass();
        // Set recording_types.
        $row->recording = bigbluebuttonbn_get_recording_data_row_types($recording, $bbbsession['bigbluebuttonbn']->id);
        // Set activity name and description.
        $row->activity = bigbluebuttonbn_get_recording_data_row_meta_activity($recording, $managerecordings);
        $row->description = bigbluebuttonbn_get_recording_data_row_meta_description($recording, $managerecordings);
        // Set recording_preview.
        $row->preview = bigbluebuttonbn_get_recording_data_row_preview($recording);
        // Set date.
        $starttime = 0;
        if (isset($recording['startTime'])) {
            $starttime = floatval($recording['startTime']);
        }
        $row->date = $starttime;
        $starttime = $starttime - ($starttime % 1000);
        // Set formatted date.
        $dateformat = get_string('strftimerecentfull', 'langconfig').' %Z';
        $row->date_formatted = userdate($starttime / 1000, $dateformat, usertimezone($USER->timezone));
        // Set formatted duration.
        $firstplayback = array_values($recording['playbacks'])[0];
        $length = isset($firstplayback['length']) ? $firstplayback['length'] : 0;
        $row->duration_formatted = $row->duration = intval($length);
        // Set actionbar, if user is allowed to manage recordings.
        if ($managerecordings) {
            $row->actionbar = bigbluebuttonbn_get_recording_data_row_actionbar($recording, $tools);
        }
    }
    return $row;
}

function bigbluebuttonbn_get_recording_data_row_actionbar($recording, $tools) {
    $actionbar = '';
    foreach ($tools as $tool) {
        $actionbar .= bigbluebuttonbn_actionbar_render_button(
            $recording,
            bigbluebuttonbn_get_recording_data_row_actionbar_payload($recording, $tool)
          );
    }
    $head = html_writer::start_tag('div', array(
        'id' => 'recording-actionbar-' . $recording['recordID'],
        'data-recordingid' => $recording['recordID'],
        'data-meetingid' => $recording['meetingID']));
    $tail = html_writer::end_tag('div');
    return $head . $actionbar . $tail;
}

function bigbluebuttonbn_get_recording_data_row_action_protect($protected) {
    if ($protected == 'true') {
        return array('action' => 'unprotect', 'tag' => 'lock');
    }
    return array('action' => 'protect', 'tag' => 'unlock');
}

function bigbluebuttonbn_get_recording_data_row_action_publish($published) {
    if ($published == 'true') {
        return array('action' => 'unpublish', 'tag' => 'hide');
    }
    return array('action' => 'publish', 'tag' => 'show');
}

function bigbluebuttonbn_get_recording_data_row_actionbar_payload($recording, $tool) {
    if ($tool == 'protect' && isset($recording['protected'])) {
        return bigbluebuttonbn_get_recording_data_row_action_protect($recording['protected']);
    }
    if ($tool == 'publish') {
        return bigbluebuttonbn_get_recording_data_row_action_publish($recording['published']);
    }
    return array('action' => $tool, 'tag' => $tool);
}

function bigbluebuttonbn_get_recording_data_row_preview($recording) {
    $visibility = '';
    if ($recording['published'] === 'false') {
        $visibility = 'hidden ';
    }
    $recordingpreview = html_writer::start_tag('div',
        array('id' => 'preview-'.$recording['recordID'], $visibility => $visibility));
    foreach ($recording['playbacks'] as $playback) {
        if (isset($playback['preview'])) {
            foreach ($playback['preview'] as $image) {
                $recordingpreview .= html_writer::empty_tag('img',
                    array('src' => $image['url'] . '?' . time(), 'class' => 'thumbnail'));
            }
            $recordingpreview .= html_writer::empty_tag('br');
            $recordingpreview .= html_writer::tag('div',
                get_string('view_recording_preview_help', 'bigbluebuttonbn'), array('class' => 'text-muted small'));
            break;
        }
    }
    $recordingpreview .= html_writer::end_tag('div');
    return $recordingpreview;
}

function bigbluebuttonbn_get_recording_data_row_types($recording, $bigbluebuttonbnid) {
    global $CFG, $OUTPUT;
    $dataimported = 'false';
    $title = '';
    if (isset($recording['imported'])) {
        $dataimported = 'true';
        $title = get_string('view_recording_link_warning', 'bigbluebuttonbn');
    }
    $visibility = '';
    if ($recording['published'] === 'false') {
        $visibility = 'hidden ';
    }
    $id = 'playbacks-'.$recording['recordID'];
    $recordingtypes = html_writer::start_tag('div', array('id' => $id, 'data-imported' => $dataimported,
          'data-recordingid' => $recording['recordID'], 'data-meetingid' => $recording['meetingID'],
          'title' => $title, $visibility => $visibility));
    foreach ($recording['playbacks'] as $playback) {
        $onclick = 'M.mod_bigbluebuttonbn.recordings.recording_play(this);';
        $href = $CFG->wwwroot.'/mod/bigbluebuttonbn/bbb_view.php?action=playback&bn='.$bigbluebuttonbnid.
            '&href='.urlencode($playback['url']).'&rid='.$recording['recordID'];
        $linkattributes = array('title' => get_string('view_recording_format_'.$playback['type'], 'bigbluebuttonbn'),
            'class' => 'btn btn-sm btn-default', 'onclick' => $onclick,
            'data-action' => 'play', 'data-href' => $href);
        $recordingtypes .= $OUTPUT->action_link('#', get_string('view_recording_format_'.$playback['type'],
            'bigbluebuttonbn'), null, $linkattributes).'&#32;';
    }
    $recordingtypes .= html_writer::end_tag('div');
    return $recordingtypes;
}

function bigbluebuttonbn_get_recording_data_row_meta_activity($recording, $editable) {
    $payload = array();
    if ($editable) {
        $payload = array('recordingid' => $recording['recordID'], 'meetingid' => $recording['meetingID'],
            'action' => 'edit', 'tag' => 'edit',
            'target' => 'name', 'source' => 'meta_bbb-recording-name');
    }
    if (isset($recording['meta_contextactivity'])) {
        $payload['source'] = 'meta_contextactivity';
        return bigbluebuttonbn_get_recording_data_row_text($recording, $recording[$payload['source']], $payload);
    }

    if (isset($recording['meta_bbb-recording-name'])) {
        return bigbluebuttonbn_get_recording_data_row_text($recording, $recording[$payload['source']], $payload);
    }

    return bigbluebuttonbn_get_recording_data_row_text($recording, $recording['meetingName'], $payload);
}

function bigbluebuttonbn_get_recording_data_row_meta_description($recording, $editable) {
    $payload = array();
    if ($editable) {
        $payload = array('recordingid' => $recording['recordID'], 'meetingid' => $recording['meetingID'],
            'action' => 'edit', 'tag' => 'edit',
            'target' => 'description', 'source' => 'meta_bbb-recording-description');
    }

    if (isset($recording['meta_contextactivitydescription'])) {
        $payload['source'] = 'meta_contextactivitydescription';
        $metadescription = trim($recording[$payload['source']]);
        if (!empty($metadescription)) {
            return bigbluebuttonbn_get_recording_data_row_text($recording, $metadescription, $payload);
        }
    }

    if (isset($recording['meta_bbb-recording-description'])) {
        $metadescription = trim($recording[$payload['source']]);
        if (!empty($metadescription)) {
            return bigbluebuttonbn_get_recording_data_row_text($recording, $metadescription, $payload);
        }
    }

    return bigbluebuttonbn_get_recording_data_row_text($recording, '', $payload);
}

function bigbluebuttonbn_get_recording_data_row_text($recording, $text, $data) {
    $htmltext = '<span>' . htmlentities($text) . '</span>';

    if (empty($data)) {
        return $htmltext;
    }

    $target = $data['action'] . '-' . $data['target'];
    $id = 'recording-' . $target . '-' . $data['recordingid'];
    $attributes = array('id' => $id, 'class' => 'quickeditlink col-md-20',
        'data-recordingid' => $data['recordingid'], 'data-meetingid' => $data['meetingid'],
        'data-target' => $data['target'], 'data-source' => $data['source']);
    $head = html_writer::start_tag('div', $attributes);
    $tail = html_writer::end_tag('div');

    $payload = array('action' => $data['action'], 'tag' => $data['tag'], 'target' => $data['target']);
    $htmllink = bigbluebuttonbn_actionbar_render_button($recording, $payload);

    return $head . $htmltext . $htmllink . $tail;
}

function bigbluebuttonbn_actionbar_render_button($recording, $data) {
    global $OUTPUT;

    if (!$data) {
        return '';
    }

    $target = $data['action'];
    if (isset($data['target'])) {
        $target .= '-' . $data['target'];
    }
    $id = 'recording-' . $target . '-' . $recording['recordID'];
    $onclick = 'M.mod_bigbluebuttonbn.recordings.recording_'.$data['action'].'(this);';
    if ((boolean)\mod_bigbluebuttonbn\locallib\config::get('recording_icons_enabled')) {
        // With icon for $manageaction.
        $iconattributes = array('id' => $id, 'class' => 'iconsmall');
        $icon = new pix_icon('i/'.$data['tag'],
            get_string('view_recording_list_actionbar_' . $data['action'], 'bigbluebuttonbn'),
            'moodle', $iconattributes);
        $linkattributes = array(
            'id' => $id,
            'onclick' => $onclick,
            'data-action' => $data['action'],
            'data-links' => bigbluebuttonbn_get_count_recording_imported_instances($recording['recordID'])
          );
        return $OUTPUT->action_icon('#', $icon, null, $linkattributes, false);
    }

    // With text for $manageaction.
    $linkattributes = array('title' => get_string($data['tag']), 'class' => 'btn btn-xs btn-danger',
        'onclick' => $onclick);
    return $OUTPUT->action_link('#', get_string($data['action']), null, $linkattributes);
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
        array('key' => 'description', 'label' => $description, 'sortable' => true, 'width' => '250px', 'allowHTML' => true),
        array('key' => 'preview', 'label' => $preview, 'width' => '250px', 'allowHTML' => true),
        array('key' => 'date', 'label' => $date, 'sortable' => true, 'width' => '225px', 'allowHTML' => true),
        array('key' => 'duration', 'label' => $duration, 'width' => '50px'),
        );

    if ($bbbsession['managerecordings']) {
        array_push($recordingsbncolumns, array('key' => 'actionbar', 'label' => $actionbar, 'width' => '120px',
            'allowHTML' => true));
    }

    return $recordingsbncolumns;
}

function bigbluebuttonbn_get_recording_data($bbbsession, $recordings, $tools = ['protect', 'publish', 'delete']) {
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

function bigbluebuttonbn_get_recording_table($bbbsession, $recordings, $tools = ['protect', 'publish', 'delete']) {
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
    $table->head = array($playback, $recording, $description, $preview, $date, $duration);
    $table->align = array('left', 'left', 'left', 'left', 'left', 'center');
    if ($bbbsession['managerecordings']) {
        $table->head[] = $actionbar;
        $table->align[] = 'left';
    }

    // Build table content.
    if (isset($recordings) && !array_key_exists('messageKey', $recordings)) {
        // There are recordings for this meeting.
        foreach ($recordings as $recording) {
            if ( !bigbluebuttonbn_include_recording_table_row($bbbsession, $recording) ) {
                continue;
            }
            bigbluebuttonbn_get_recording_table_row($bbbsession, $recording, $tools, $table);
        }
    }

    return $table;
}

function bigbluebuttonbn_get_recording_table_row($bbbsession, $recording, $tools, &$table) {
    $rowdata = bigbluebuttonbn_get_recording_data_row($bbbsession, $recording, $tools);

    if ($rowdata == null) {
        return;
    }

    $row = new html_table_row();
    $row->id = 'recording-td-'.$recording['recordID'];
    $row->attributes['data-imported'] = 'false';
    $texthead = '';
    $texttail = '';
    if (isset($recording['imported'])) {
        $row->attributes['title'] = get_string('view_recording_link_warning', 'bigbluebuttonbn');
        $row->attributes['data-imported'] = 'true';
        $texthead = '<em>';
        $texttail = '</em>';
    }
    $rowdata->date_formatted = str_replace(' ', '&nbsp;', $rowdata->date_formatted);
    $row->cells = array(
        $texthead . $rowdata->recording . $texttail,
        $texthead . $rowdata->activity . $texttail, $texthead . $rowdata->description . $texttail,
        $rowdata->preview, $texthead . $rowdata->date_formatted . $texttail,
        $rowdata->duration_formatted
      );
    if ($bbbsession['managerecordings']) {
        $row->cells[] = $rowdata->actionbar;
    }
    array_push($table->data, $row);
}

function bigbluebuttonbn_include_recording_table_row($bbbsession, $recording) {
    return !( !isset($recording['imported']) && isset($bbbsession['group']) && $recording['meetingID'] != $bbbsession['meetingid'] );
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

function bigbluebuttonbn_is_bn_server() {
    // Validates if the server may have extended capabilities.
    $parsedurl = parse_url(\mod_bigbluebuttonbn\locallib\config::get('server_url'));
    if (!isset($parsedurl['host'])) {
        return false;
    }

    $h = $parsedurl['host'];
    $hends = explode('.', $h);
    $hendslength = count($hends);

    return ($hends[$hendslength - 1] == 'com' && $hends[$hendslength - 2] == 'blindsidenetworks');
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

function bigbluebutton_output_recording_table($bbbsession, $recordings, $tools = ['protect', 'publish', 'delete']) {
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

/**
 * helper function to obtain the tags linked to a bigbluebuttonbn activity
 *
 * @param string $id
 *
 * @return string containing the tags separated by commas
 */
function bigbluebuttonbn_get_tags($id) {
    $tagsarray = core_tag_tag::get_item_tags_array('core', 'course_modules', $id);
    return implode(',', $tagsarray);
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
function bigbluebuttonbn_get_recordings($courseid, $bigbluebuttonbnid = null, $subset = true,
        $includedeleted = false) {
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

    $sql = 'SELECT COUNT(DISTINCT id) FROM {bigbluebuttonbn_logs} WHERE log = ? AND meta LIKE ? AND meta LIKE ?';

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
                    'sendnotifications', 'preuploadpresentation', 'permissions', 'schedule', 'groups')),
            array('id' => BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY, 'name' => get_string('instance_type_recording_only',
                'bigbluebuttonbn'), 'features' => array('showrecordings', 'importrecordings')),
    );

    return $instanceprofiles;
}

function bigbluebuttonbn_get_enabled_features($typeprofiles, $type = null) {
    $enabledfeatures = array();

    $features = $typeprofiles[0]['features'];
    if (!is_null($type)) {
        $features = $typeprofiles[$type]['features'];
    }
    $enabledfeatures['showroom'] = (in_array('all', $features) || in_array('showroom', $features));
    $enabledfeatures['showrecordings'] = (in_array('all', $features) || in_array('showrecordings', $features));
    $enabledfeatures['importrecordings'] = (in_array('all', $features) || in_array('importrecordings', $features));

    return $enabledfeatures;
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

function bigbluebuttonbn_get_strings_for_js() {
    $locale = bigbluebuttonbn_get_locale();
    $stringman = get_string_manager();
    $strings = $stringman->load_component_strings('bigbluebuttonbn', $locale);
    return $strings;
}

function bigbluebuttonbn_get_locale() {
    $lang = get_string('locale', 'core_langconfig');
    return substr($lang, 0, strpos($lang, '.'));
}

function bigbluebuttonbn_get_localcode() {
    $locale = bigbluebuttonbn_get_locale();
    return substr($locale, 0, strpos($locale, '_'));
}
