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
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once(dirname(__FILE__).'/lib.php');

/** @var BIGBLUEBUTTONBN_UPDATE_CACHE boolean set to true indicates that cache has to be updated */
const BIGBLUEBUTTONBN_UPDATE_CACHE = true;
/** @var BIGBLUEBUTTONBN_TYPE_ALL integer set to 0 defines an instance type that inclueds room and recordings */
const BIGBLUEBUTTONBN_TYPE_ALL = 0;
/** @var BIGBLUEBUTTONBN_TYPE_ROOM_ONLY integer set to 1 defines an instance type that inclueds only room */
const BIGBLUEBUTTONBN_TYPE_ROOM_ONLY = 1;
/** @var BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY integer set to 2 defines an instance type that inclueds only recordings */
const BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY = 2;
/** @var BIGBLUEBUTTONBN_ROLE_VIEWER string defines the bigbluebutton viewer role */
const BIGBLUEBUTTONBN_ROLE_VIEWER = 'viewer';
/** @var BIGBLUEBUTTONBN_ROLE_MODERATOR string defines the bigbluebutton moderator role */
const BIGBLUEBUTTONBN_ROLE_MODERATOR = 'moderator';

/**
 * Register a bigbluebuttonbn event
 *
 * @param array  $bbbsession
 * @param string $event
 * @param array  $overrides
 * @param string $meta
 *
 * @return void
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
 * Builds and retunrs a url for joining a bigbluebutton meeting.
 *
 * @param string $meetingid
 * @param string $username
 * @param string $pw
 * @param string $logouturl
 * @param string $configtoken
 * @param string $userid
 *
 * @return string
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
 * Creates a bigbluebutton meeting and returns the response in an array.
 *
 * @param array  $data
 * @param array  $metadata
 * @param string $pname
 * @param string $purl
 *
 * @return array
 */
function bigbluebuttonbn_get_create_meeting_array($data, $metadata = array(), $pname = null, $purl = null) {
    $createmeetingurl = \mod_bigbluebuttonbn\locallib\bigbluebutton::action_url('create', $data, $metadata);
    $method = 'GET';
    $data = null;
    if (!is_null($pname) && !is_null($purl)) {
        $method = 'POST';
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
    return array('returncode' => 'FAILED', 'message' => 'unreachable', 'messageKey' => 'Server is unreachable');
}

/**
 * Fetch meeting info and wrap response in array.
 *
 * @param string $meetingid
 *
 * @return array
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
        return (array)$xml;
    }
    // If the server is unreachable, then prompts the user of the necessary action.
    return array('returncode' => 'FAILED', 'message' => 'unreachable', 'messageKey' => 'Server is unreachable');
}

/**
 * Helper function to retrieve recordings from a BigBlueButton server.
 *
 * @param string|array $meetingids   list of meetingIDs "mid1,mid2,mid3" or array("mid1","mid2","mid3")
 * @param string|array $recordingids list of $recordingids "rid1,rid2,rid3" or array("rid1","rid2","rid3") for filtering
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
    // Sort recordings.
    uasort($recordings, 'bigbluebuttonbn_recording_build_sorter');
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
 * Helper function to fetch recordings from a BigBlueButton server.
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
        $recordings += bigbluebuttonbn_get_recordings_array_fetch_page($mids);
    }
    return $recordings;
}

/**
 * Helper function to fetch one page of upto 25 recordings from a BigBlueButton server.
 *
 * @param array  $mids
 *
 * @return array
 */
function bigbluebuttonbn_get_recordings_array_fetch_page($mids) {
    $recordings = array();
    // Do getRecordings is executed using a method GET (supported by all versions of BBB).
    $url = \mod_bigbluebuttonbn\locallib\bigbluebutton::action_url('getRecordings', ['meetingID' => implode(',', $mids)]);
    $xml = bigbluebuttonbn_wrap_xml_load_file($url);
    if ($xml && $xml->returncode == 'SUCCESS' && isset($xml->recordings)) {
        // If there were meetings already created.
        foreach ($xml->recordings->recording as $recordingxml) {
            $recording = bigbluebuttonbn_get_recording_array_value($recordingxml);
            $recordings[$recording['recordID']] = $recording;
        }
    }
    return $recordings;
}

/**
 * Helper function to remove a set of recordings from an array.
 *
 * @param array  $rids
 * @param array  $recordings
 *
 * @return array
 */
function bigbluebuttonbn_get_recordings_array_filter($rids, &$recordings) {
    foreach ($recordings as $key => $recording) {
        if (!in_array($recording['recordID'], $rids)) {
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
 * @return associative array with imported recordings indexed by recordID, each recording
 * is a non sequential associative array that corresponds to the actual recording in BBB
 */
function bigbluebuttonbn_get_recordings_imported_array($courseid = 0, $bigbluebuttonbnid = null, $subset = true) {
    global $DB;
    $select = bigbluebuttonbn_get_recordings_imported_sql_select($courseid, $bigbluebuttonbnid, $subset);
    $recordsimported = $DB->get_records_select('bigbluebuttonbn_logs', $select);
    $recordsimportedarray = array();
    foreach ($recordsimported as $recordimported) {
        $meta = json_decode($recordimported->meta, true);
        $recording = $meta['recording'];
        // Override imported flag with actual ID.
        $recording['imported'] = $recordimported->id;
        if (isset($recordimported->protected)) {
            $recording['protected'] = (string) $recordimported->protected;
        }
        $recordsimportedarray[$recording['recordID']] = $recording;
    }
    return $recordsimportedarray;
}

/**
 * Helper function to retrive the default config.xml file.
 *
 * @return string
 */
function bigbluebuttonbn_get_default_config_xml() {
    $xml = bigbluebuttonbn_wrap_xml_load_file(
        \mod_bigbluebuttonbn\locallib\bigbluebutton::action_url('getDefaultConfigXML')
      );
    return $xml;
}

/**
 * Helper function to convert an xml recording object to an array in the format used by the plugin.
 *
 * @param object $recording
 *
 * @return array
 */
function bigbluebuttonbn_get_recording_array_value($recording) {
    // Add formats.
    $playbackarray = array();
    foreach ($recording->playback->format as $format) {
        $playbackarray[(string) $format->type] = array('type' => (string) $format->type,
            'url' => trim((string) $format->url), 'length' => (string) $format->length);
        // Add preview per format when existing.
        if ($format->preview) {
            $playbackarray[(string) $format->type]['preview'] = bigbluebuttonbn_get_recording_preview_images($format->preview);
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
    return $recordingarray + $metadataarray;
}

/**
 * Helper function to convert an xml recording preview images to an array in the format used by the plugin.
 *
 * @param object $preview
 *
 * @return array
 */
function bigbluebuttonbn_get_recording_preview_images($preview) {
    $imagesarray = array();
    foreach ($preview->images->image as $image) {
        $imagearray = array('url' => trim((string) $image));
        foreach ($image->attributes() as $attkey => $attvalue) {
            $imagearray[$attkey] = (string) $attvalue;
        }
        array_push($imagesarray, $imagearray);
    }
    return $imagesarray;
}

/**
 * Helper function to convert an xml recording metadata object to an array in the format used by the plugin.
 *
 * @param array $metadata
 *
 * @return array
 */
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

/**
 * Helper function to sort an array of recordings. It compares the startTime in two recording objecs.
 *
 * @param object $a
 * @param object $b
 *
 * @return array
 */
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
 * Perform deleteRecordings on BBB.
 *
 * @param string $recordids
 *
 * @return boolean
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
 * Perform publishRecordings on BBB.
 *
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
 * Perform updateRecordings on BBB.
 *
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
 * Perform end on BBB.
 *
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
 * Perform isMeetingRunning on BBB.
 *
 * @param string $meetingid
 */
function bigbluebuttonbn_is_meeting_running($meetingid) {
    /* As a workaround to isMeetingRunning that always return SUCCESS but only returns true
     * when at least one user is in the session, we use getMeetingInfo instead.
     */
    $xml = bigbluebuttonbn_wrap_xml_load_file(
        \mod_bigbluebuttonbn\locallib\bigbluebutton::action_url('getMeetingInfo', ['meetingID' => $meetingid])
      );
    return ($xml && $xml->returncode == 'SUCCESS');
}

/**
 * Perform api request on BBB.
 *
 * @return string
 */
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
 * Perform api request on BBB and wraps the response in an XML object
 *
 * @param string $url
 * @param string $method
 * @param string $data
 * @param string $contenttype
 *
 * @return object
 */
function bigbluebuttonbn_wrap_xml_load_file($url, $method = 'GET', $data = null, $contenttype = 'text/xml') {
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

/**
 * Perform api request on BBB using CURL and wraps the response in an XML object
 *
 * @param string $url
 * @param string $method
 * @param string $data
 * @param string $contenttype
 *
 * @return object
 */
function bigbluebuttonbn_wrap_xml_load_file_curl_request($url, $method = 'GET', $data = null, $contenttype = 'text/xml') {
    $c = new curl();
    $c->setopt(array('SSL_VERIFYPEER' => true));
    if ($method == 'POST') {
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

/**
 * End the session associated with this instance (if it's running).
 *
 * @param object $bigbluebuttonbn
 *
 * @return void
 */
function bigbluebuttonbn_end_meeting_if_running($bigbluebuttonbn) {
    $meetingid = $bigbluebuttonbn->meetingid.'-'.$bigbluebuttonbn->course.'-'.$bigbluebuttonbn->id;
    if (bigbluebuttonbn_is_meeting_running($meetingid)) {
        bigbluebuttonbn_end_meeting($meetingid, $bigbluebuttonbn->moderatorpass);
    }
}

/**
 * Returns user roles in a context.
 *
 * @param object $context
 * @param integer $userid
 *
 * @return array $userroles
 */
function bigbluebuttonbn_get_user_roles($context, $userid) {
    global $DB;
    $userroles = get_user_roles($context, $userid);
    if ($userroles) {
        $where = '';
        foreach ($userroles as $userrole) {
            $where .= (empty($where) ? ' WHERE' : ' OR').' id=' . $userrole->roleid;
        }
        $userroles = $DB->get_records_sql('SELECT * FROM {role}'.$where);
    }
    return $userroles;
}

/**
 * Returns guest role wrapped in an array.
 *
 * @return array
 */
function bigbluebuttonbn_get_guest_role() {
    $guestrole = get_guest_role();
    return array($guestrole->id => $guestrole);
}

/**
 * Returns an array containing all the users in a context.
 *
 * @param context $context
 *
 * @return array $users
 */
function bigbluebuttonbn_get_users(context $context = null) {
    $users = (array) get_enrolled_users($context, '', 0, 'u.*', null, 0, 0, true);
    foreach ($users as $key => $value) {
        $users[$key] = fullname($value);
    }
    return $users;
}

/**
 * Returns an array containing all the users in a context wrapped for html select element.
 *
 * @param context $context
 *
 * @return array $users
 */
function bigbluebuttonbn_get_users_select(context $context = null) {
    $users = (array) get_enrolled_users($context, '', 0, 'u.*', null, 0, 0, true);
    foreach ($users as $key => $value) {
        $users[$key] = array('id' => $value->id, 'name' => fullname($value));
    }
    return $users;
}

/**
 * Returns an array containing all the roles in a context.
 *
 * @param context $context
 *
 * @return array $roles
 */
function bigbluebuttonbn_get_roles(context $context = null) {
    $roles = (array) role_get_names($context);
    foreach ($roles as $key => $value) {
        $roles[$key] = $value->localname;
    }
    return $roles;
}

/**
 * Returns an array containing all the roles in a context wrapped for html select element.
 *
 * @param context $context
 *
 * @return array $users
 */
function bigbluebuttonbn_get_roles_select(context $context = null) {
    $roles = (array) role_get_names($context);
    foreach ($roles as $key => $value) {
        $roles[$key] = array('id' => $value->id, 'name' => $value->localname);
    }
    return $roles;
}

/**
 * Returns role that corresponds to an id.
 *
 * @param string|integer $id
 *
 * @return object $role
 */
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

/**
 * Returns an array to populate a list of participants used in mod_form.js.
 *
 * @param context $context
 *
 * @return array $data
 */
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

/**
 * Returns an array to populate a list of participants used in mod_form.php.
 *
 * @param object $bigbluebuttonbn
 * @param context $context
 *
 * @return array
 */
function bigbluebuttonbn_get_participant_list($bigbluebuttonbn, $context) {
    if ($bigbluebuttonbn == null) {
        return bigbluebuttonbn_get_participant_list_default($context);
    }
    return bigbluebuttonbn_get_participant_rules_encoded($bigbluebuttonbn);
}

/**
 * Returns an array to populate a list of participants used in mod_form.php with default values.
 *
 * @param context $context
 *
 * @return array
 */
function bigbluebuttonbn_get_participant_list_default($context) {
    global $USER;
    $participantlistarray = array();
    $participantlistarray[] = array(
        'selectiontype' => 'all',
        'selectionid' => 'all',
        'role' => BIGBLUEBUTTONBN_ROLE_VIEWER);
    $moderatordefaults = explode(',', \mod_bigbluebuttonbn\locallib\config::get('participant_moderator_default'));
    foreach ($moderatordefaults as $moderatordefault) {
        if ($moderatordefault == '0') {
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

/**
 * Returns an array to populate a list of participants used in mod_form.php with bigbluebuttonbn values.
 *
 * @param object $bigbluebuttonbn
 *
 * @return array
 */
function bigbluebuttonbn_get_participant_rules_encoded($bigbluebuttonbn) {
    $rules = json_decode($bigbluebuttonbn->participants, true);
    if (!is_array($rules)) {
        return array();
    }
    foreach ($rules as $key => $rule) {
        if ($rule['selectiontype'] !== 'role' || is_numeric($rule['selectionid'])) {
            continue;
        }
        $role = bigbluebuttonbn_get_role($rule['selectionid']);
        if ($role == null) {
            unset($rules[$key]);
            continue;
        }
        $rule['selectionid'] = $role->id;
        $rules[$key] = $rule;
    }
    return $rules;
}

/**
 * Returns an array to populate a list of participant_selection used in mod_form.php.
 *
 * @return array
 */
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

/**
 * Evaluate if a user in a context is moderator based on roles and participation rules.
 *
 * @param context $context
 * @param string $participants
 * @param integer $userid
 *
 * @return boolean
 */
function bigbluebuttonbn_is_moderator($context, $participants, $userid = null) {
    global $USER;
    if (empty($participants)) {
        // The room that is being used comes from a previous version.
        return has_capability('mod/bigbluebuttonbn:moderate', $context);
    }
    $participantlist = json_decode($participants);
    if (!is_array($participantlist)) {
        return false;
    }
    if (empty($userid)) {
        $userid = $USER->id;
    }
    $userroles = bigbluebuttonbn_get_guest_role();
    if (!isguestuser()) {
        $userroles = bigbluebuttonbn_get_user_roles($context, $userid);
    }
    return bigbluebuttonbn_is_moderator_validator($participantlist, $userid , $userroles);
}

/**
 * Iterates participant list rules to evaluate if a user is moderator.
 *
 * @param array $participantlist
 * @param integer $userid
 * @param array $userroles
 *
 * @return boolean
 */
function bigbluebuttonbn_is_moderator_validator($participantlist, $userid, $userroles) {
    // Iterate participant rules.
    foreach ($participantlist as $participant) {
        if (bigbluebuttonbn_is_moderator_validate_rule($participant, $userid, $userroles)) {
            return true;
        }
    }
    return false;
}

/**
 * Evaluate if a user is moderator based on roles and a particular participation rule.
 *
 * @param object $participant
 * @param integer $userid
 * @param array $userroles
 *
 * @return boolean
 */
function bigbluebuttonbn_is_moderator_validate_rule($participant, $userid, $userroles) {
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

/**
 * Helper returns error message key for the language file that corresponds to a bigbluebutton error key.
 *
 * @param string $messagekey
 * @param string $defaultkey
 *
 * @return string
 */
function bigbluebuttonbn_get_error_key($messagekey, $defaultkey = null) {
    if ($messagekey == 'checksumError') {
        return 'index_error_checksum';
    }
    if ($messagekey == 'maxConcurrent') {
        return 'view_error_max_concurrent';
    }
    return $defaultkey;
}

/**
 * Helper evaluates if a voicebridge number is unique.
 *
 * @param integer $instance
 * @param integer $voicebridge
 *
 * @return string
 */
function bigbluebuttonbn_voicebridge_unique($instance, $voicebridge) {
    global $DB;
    if ($voicebridge == 0) {
        return true;
    }
    $select = 'voicebridge = ' . $voicebridge;
    if ($instance != 0) {
        $select .= ' AND id <>' . $instance;
    }
    if (!$DB->get_records_select('bigbluebuttonbn', $select)) {
        return true;
    }
    return false;
}

/**
 * Helper estimate a duration for the meeting based on the closingtime.
 *
 * @param integer $closingtime
 *
 * @return integer
 */
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

/**
 * Helper return array containing the file descriptor for a preuploaded presentation.
 *
 * @param context $context
 * @param string $presentation
 * @param integer $id
 *
 * @return array
 */
function bigbluebuttonbn_get_presentation_array($context, $presentation, $id = null) {
    if (empty($presentation)) {
        return array('url' => null, 'name' => null, 'icon' => null, 'mimetype_description' => null);
    }
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_bigbluebuttonbn', 'presentation', 0,
        'itemid, filepath, filename', false);
    if (count($files) == 0) {
        return array('url' => null, 'name' => null, 'icon' => null, 'mimetype_description' => null);
    }
    $file = reset($files);
    unset($files);
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
    return array('name' => $file->get_filename(), 'icon' => file_file_icon($file, 24),
            'url' => $url->out(false), 'mimetype_description' => get_mimetype_description($file));
}

/**
 * Helper generates a nonce used for the preuploaded presentation callback url.
 *
 * @return string
 */
function bigbluebuttonbn_generate_nonce() {
    $mt = microtime();
    $rand = mt_rand();
    return md5($mt.$rand);
}

/**
 * Helper generates a random password.
 *
 * @param integer $length
 * @param string $unique
 *
 * @return string
 */
function bigbluebuttonbn_random_password($length = 8, $unique = "") {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?';
    do {
        $password = substr(str_shuffle($chars), 0, $length);
    } while ($unique == $password);
    return $password;
}

/**
 * Helper register a bigbluebuttonbn event.
 *
 * @param string $type
 * @param object $bigbluebuttonbn
 * @param array $options [timecreated, userid, other]
 *
 * @return void
 */
function bigbluebuttonbn_event_log($type, $bigbluebuttonbn, $options = []) {
    global $DB;
    if (!in_array($type, \mod_bigbluebuttonbn\event\events::$events)) {
        // No log will be created.
        return;
    }
    $course = $DB->get_record('course', array('id' => $bigbluebuttonbn->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('bigbluebuttonbn', $bigbluebuttonbn->id, $course->id, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    $params = array('context' => $context, 'objectid' => $bigbluebuttonbn->id);
    if (array_key_exists('timecreated', $options)) {
        $params['timecreated'] = $options['timecreated'];
    }
    if (array_key_exists('userid', $options)) {
        $params['userid'] = $options['userid'];
    }
    if (array_key_exists('other', $options)) {
        $params['other'] = $options['other'];
    }
    $event = call_user_func_array('\mod_bigbluebuttonbn\event\\' . $type . '::create',
        array($params));
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('bigbluebuttonbn', $bigbluebuttonbn);
    $event->trigger();
}

/**
 * Updates the meeting info cached object when a participant has joined.
 *
 * @param string $meetingid
 * @param bool $ismoderator
 *
 * @return void
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
 * Gets a meeting info object cached or fetched from the live session.
 *
 * @param string $meetingid
 * @param boolean $updatecache
 *
 * @return array
 */
function bigbluebuttonbn_get_meeting_info($meetingid, $updatecache = false) {
    $cachettl = (int)\mod_bigbluebuttonbn\locallib\config::get('waitformoderator_cache_ttl');
    $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'meetings_cache');
    $result = $cache->get($meetingid);
    $now = time();
    if (!$updatecache && isset($result) && $now < ($result['creation_time'] + $cachettl)) {
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
 * Publish an imported recording.
 *
 * @param string $id
 * @param boolean $publish
 *
 * @return boolean
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
 * Delete an imported recording.
 *
 * @param string $id
 *
 * @return boolean
 */
function bigbluebuttonbn_delete_recording_imported($id) {
    global $DB;
    // Execute delete.
    $DB->delete_records('bigbluebuttonbn_logs', array('id' => $id));
    return true;
}

/**
 * Update an imported recording.
 *
 * @param string $id
 * @param array $params ['key'=>param_key, 'value']
 *
 * @return boolean
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
 * Protect/Unprotect an imported recording.
 *
 * @param string $id
 * @param boolean $protect
 *
 * @return boolean
 */
function bigbluebuttonbn_protect_recording_imported($id, $protect = true) {
    global $DB;
    // Locate the record to be updated.
    $record = $DB->get_record('bigbluebuttonbn_logs', array('id' => $id));
    $meta = json_decode($record->meta, true);
    // Prepare data for the update.
    $meta['recording']['protected'] = ($protect) ? 'true' : 'false';
    $record->meta = json_encode($meta);
    // Proceed with the update.
    $DB->update_record('bigbluebuttonbn_logs', $record);
    return true;
}

/**
 * Sets a custom config.xml file for being used on create.
 *
 * @param string $meetingid
 * @param string $configxml
 *
 * @return object
 */
function bigbluebuttonbn_set_config_xml($meetingid, $configxml) {
    $urldefaultconfig = \mod_bigbluebuttonbn\locallib\config::get('server_url').'api/setConfigXML?';
    $configxmlparams = bigbluebuttonbn_set_config_xml_params($meetingid, $configxml);
    $xml = bigbluebuttonbn_wrap_xml_load_file($urldefaultconfig, 'POST',
        $configxmlparams, 'application/x-www-form-urlencoded');
    return $xml;
}

/**
 * Sets qs used with a custom config.xml file request.
 *
 * @param string $meetingid
 * @param string $configxml
 *
 * @return string
 */
function bigbluebuttonbn_set_config_xml_params($meetingid, $configxml) {
    $params = 'configXML='.urlencode($configxml).'&meetingID='.urlencode($meetingid);
    $configxmlparams = $params.'&checksum='.sha1('setConfigXML'.$params.\mod_bigbluebuttonbn\locallib\config::get('shared_secret'));
    return $configxmlparams;
}

/**
 * Sets a custom config.xml file for being used on create.
 *
 * @param string $meetingid
 * @param string $configxml
 *
 * @return array
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

/**
 * Helper function builds a row for the data used by the recording table.
 *
 * @param array $bbbsession
 * @param array $recording
 * @param array $tools
 *
 * @return array
 */
function bigbluebuttonbn_get_recording_data_row($bbbsession, $recording, $tools = ['protect', 'publish', 'delete']) {
    if (!bigbluebuttonbn_include_recording_table_row($bbbsession, $recording)) {
        return;
    }
    $rowdata = new stdClass();
    // Set recording_types.
    $rowdata->recording = bigbluebuttonbn_get_recording_data_row_types($recording, $bbbsession);
    // Set activity name.
    $rowdata->activity = bigbluebuttonbn_get_recording_data_row_meta_activity($recording, $bbbsession);
    // Set activity description.
    $rowdata->description = bigbluebuttonbn_get_recording_data_row_meta_description($recording, $bbbsession);
    if (bigbluebuttonbn_get_recording_data_preview_enabled($bbbsession)) {
        // Set recording_preview.
        $rowdata->preview = bigbluebuttonbn_get_recording_data_row_preview($recording);
    }
    // Set date.
    $rowdata->date = bigbluebuttonbn_get_recording_data_row_date($recording);
    // Set formatted date.
    $rowdata->date_formatted = bigbluebuttonbn_get_recording_data_row_date_formatted($rowdata->date);
    // Set formatted duration.
    $rowdata->duration_formatted = $rowdata->duration = bigbluebuttonbn_get_recording_data_row_duration($recording);
    // Set actionbar, if user is allowed to manage recordings.
    if ($bbbsession['managerecordings']) {
        $rowdata->actionbar = bigbluebuttonbn_get_recording_data_row_actionbar($recording, $tools);
    }
    return $rowdata;
}

/**
 * Helper function evaluates if a row for the data used by the recording table is editable.
 *
 * @param array $bbbsession
 *
 * @return boolean
 */
function bigbluebuttonbn_get_recording_data_row_editable($bbbsession) {
    return ($bbbsession['managerecordings'] && ((double)$bbbsession['serverversion'] >= 1.0 || $bbbsession['bnserver']));
}

/**
 * Helper function evaluates if recording preview should be included.
 *
 * @param array $bbbsession
 *
 * @return boolean
 */
function bigbluebuttonbn_get_recording_data_preview_enabled($bbbsession) {
    return ((double)$bbbsession['serverversion'] >= 1.0 && $bbbsession['bigbluebuttonbn']->recordings_preview == '1');
}

/**
 * Helper function converts recording date used in row for the data used by the recording table.
 *
 * @param array $recording
 *
 * @return integer
 */
function bigbluebuttonbn_get_recording_data_row_date($recording) {
    if (!isset($recording['startTime'])) {
        return 0;
    }
    return floatval($recording['startTime']);
}

/**
 * Helper function format recording date used in row for the data used by the recording table.
 *
 * @param integer $starttime
 *
 * @return string
 */
function bigbluebuttonbn_get_recording_data_row_date_formatted($starttime) {
    global $USER;
    $starttime = $starttime - ($starttime % 1000);
    // Set formatted date.
    $dateformat = get_string('strftimerecentfull', 'langconfig').' %Z';
    return userdate($starttime / 1000, $dateformat, usertimezone($USER->timezone));
}

/**
 * Helper function converts recording duration used in row for the data used by the recording table.
 *
 * @param array $recording
 *
 * @return integer
 */
function bigbluebuttonbn_get_recording_data_row_duration($recording) {
    $firstplayback = array_values($recording['playbacks'])[0];
    $length = 0;
    if (isset($firstplayback['length'])) {
        $length = $firstplayback['length'];
    }
    return intval($length);
}

/**
 * Helper function builds recording actionbar used in row for the data used by the recording table.
 *
 * @param array $recording
 * @param array $tools
 *
 * @return string
 */
function bigbluebuttonbn_get_recording_data_row_actionbar($recording, $tools) {
    $actionbar = '';
    foreach ($tools as $tool) {
        $buttonpayload = bigbluebuttonbn_get_recording_data_row_actionbar_payload($recording, $tool);
        if ($tool == 'protect') {
            if (isset($recording['imported'])) {
                $buttonpayload['disabled'] = 'disabled';
            }
            if (!isset($recording['protected'])) {
                $buttonpayload['disabled'] = 'invisible';
            }
        }
        $actionbar .= bigbluebuttonbn_actionbar_render_button($recording, $buttonpayload);
    }
    $head = html_writer::start_tag('div', array(
        'id' => 'recording-actionbar-' . $recording['recordID'],
        'data-recordingid' => $recording['recordID'],
        'data-meetingid' => $recording['meetingID']));
    $tail = html_writer::end_tag('div');
    return $head . $actionbar . $tail;
}

/**
 * Helper function returns the corresponding payload for an actionbar button used in row
 * for the data used by the recording table.
 *
 * @param array $recording
 * @param array $tool
 *
 * @return array
 */
function bigbluebuttonbn_get_recording_data_row_actionbar_payload($recording, $tool) {
    if ($tool == 'protect') {
        $protected = 'false';
        if (isset($recording['protected'])) {
            $protected = $recording['protected'];
        }
        return bigbluebuttonbn_get_recording_data_row_action_protect($protected);
    }
    if ($tool == 'publish') {
        return bigbluebuttonbn_get_recording_data_row_action_publish($recording['published']);
    }
    return array('action' => $tool, 'tag' => $tool);
}

/**
 * Helper function returns the payload for protect action button used in row
 * for the data used by the recording table.
 *
 * @param string $protected
 *
 * @return array
 */
function bigbluebuttonbn_get_recording_data_row_action_protect($protected) {
    if ($protected == 'true') {
        return array('action' => 'unprotect', 'tag' => 'lock');
    }
    return array('action' => 'protect', 'tag' => 'unlock');
}

/**
 * Helper function returns the payload for publish action button used in row
 * for the data used by the recording table.
 *
 * @param string $published
 *
 * @return array
 */
function bigbluebuttonbn_get_recording_data_row_action_publish($published) {
    if ($published == 'true') {
        return array('action' => 'unpublish', 'tag' => 'hide');
    }
    return array('action' => 'publish', 'tag' => 'show');
}

/**
 * Helper function builds recording preview used in row for the data used by the recording table.
 *
 * @param array $recording
 *
 * @return string
 */
function bigbluebuttonbn_get_recording_data_row_preview($recording) {
    $options = array('id' => 'preview-'.$recording['recordID'], 'class' => 'container');
    if ($recording['published'] === 'false') {
        $options['hidden'] = 'hidden';
    }
    $recordingpreview = html_writer::start_tag('div', $options);
    foreach ($recording['playbacks'] as $playback) {
        if (isset($playback['preview'])) {
            $recordingpreview .= html_writer::start_tag('div', array('class' => 'row'));
            foreach ($playback['preview'] as $image) {
                $recordingpreview .= html_writer::empty_tag('img',
                    array('src' => trim($image['url']) . '?' . time(), 'class' => 'recording-thumbnail col-sm'));
            }
            $recordingpreview .= html_writer::end_tag('div');
            $recordingpreview .= html_writer::tag('div',
                get_string('view_recording_preview_help', 'bigbluebuttonbn'), array('class' => 'row text-muted small'));
            break;
        }
    }
    $recordingpreview .= html_writer::end_tag('div');
    return $recordingpreview;
}

/**
 * Helper function renders recording types to be used in row for the data used by the recording table.
 *
 * @param array $recording
 * @param array $bbbsession
 *
 * @return string
 */
function bigbluebuttonbn_get_recording_data_row_types($recording, $bbbsession) {
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
          'data-meetingid' => $recording['meetingID'], 'data-recordingid' => $recording['recordID'],
          'title' => $title, $visibility => $visibility));
    foreach ($recording['playbacks'] as $playback) {
        $recordingtypes .= bigbluebuttonbn_get_recording_data_row_type($recording, $bbbsession, $playback);
    }
    $recordingtypes .= html_writer::end_tag('div');
    return $recordingtypes;
}

/**
 * Helper function renders the link used for recording type in row for the data used by the recording table.
 *
 * @param array $recording
 * @param array $bbbsession
 * @param array $playback
 *
 * @return string
 */
function bigbluebuttonbn_get_recording_data_row_type($recording, $bbbsession, $playback) {
    global $CFG, $OUTPUT;
    if (!bigbluebuttonbn_include_recording_data_row_type($recording, $bbbsession, $playback)) {
        return '';
    }
    $title = get_string('view_recording_format_'.$playback['type'], 'bigbluebuttonbn');
    $onclick = 'M.mod_bigbluebuttonbn.recordings.recordingPlay(this);';
    $href = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_view.php?action=play&bn=' . $bbbsession['bigbluebuttonbn']->id .
      '&mid='.$recording['meetingID'] . '&rid=' . $recording['recordID'] . '&rtype=' . $playback['type'];
    if (!isset($recording['imported']) || !isset($recording['protected']) || $recording['protected'] === 'false') {
        $href .= '&href='.urlencode(trim($playback['url']));
    }
    $id = 'recording-play-' . $playback['type'] . '-' . $recording['recordID'];
    $linkattributes = array(
        'id' => $id,
        'onclick' => $onclick,
        'data-action' => 'play',
        'data-target' => $playback['type'],
        'data-href' => $href,
        'class' => 'btn btn-sm btn-default'
      );
    return $OUTPUT->action_link('#', $title, null, $linkattributes) . '&#32;';
}

/**
 * Helper function renders the name for recording used in row for the data used by the recording table.
 *
 * @param array $recording
 * @param array $bbbsession
 *
 * @return string
 */
function bigbluebuttonbn_get_recording_data_row_meta_activity($recording, $bbbsession) {
    $payload = array();
    if (bigbluebuttonbn_get_recording_data_row_editable($bbbsession)) {
        $payload = array('recordingid' => $recording['recordID'], 'meetingid' => $recording['meetingID'],
            'action' => 'edit', 'tag' => 'edit',
            'target' => 'name');
    }
    $oldsource = 'meta_contextactivity';
    if (isset($recording[$oldsource])) {
        $metaname = trim($recording[$oldsource]);
        return bigbluebuttonbn_get_recording_data_row_text($recording, $metaname, $oldsource, $payload);
    }
    $newsource = 'meta_bbb-recording-name';
    if (isset($recording[$newsource])) {
        $metaname = trim($recording[$newsource]);
        return bigbluebuttonbn_get_recording_data_row_text($recording, $metaname, $newsource, $payload);
    }
    $metaname = trim($recording['meetingName']);
    return bigbluebuttonbn_get_recording_data_row_text($recording, $metaname, $newsource, $payload);
}

/**
 * Helper function renders the description for recording used in row for the data used by the recording table.
 *
 * @param array $recording
 * @param array $bbbsession
 *
 * @return string
 */
function bigbluebuttonbn_get_recording_data_row_meta_description($recording, $bbbsession) {
    $payload = array();
    if (bigbluebuttonbn_get_recording_data_row_editable($bbbsession)) {
        $payload = array('recordingid' => $recording['recordID'], 'meetingid' => $recording['meetingID'],
            'action' => 'edit', 'tag' => 'edit',
            'target' => 'description');
    }
    $oldsource = 'meta_contextactivitydescription';
    if (isset($recording[$oldsource])) {
        $metadescription = trim($recording[$oldsource]);
        return bigbluebuttonbn_get_recording_data_row_text($recording, $metadescription, $oldsource, $payload);
    }
    $newsource = 'meta_bbb-recording-description';
    if (isset($recording[$newsource])) {
        $metadescription = trim($recording[$newsource]);
        return bigbluebuttonbn_get_recording_data_row_text($recording, $metadescription, $newsource, $payload);
    }
    return bigbluebuttonbn_get_recording_data_row_text($recording, '', $newsource, $payload);
}

/**
 * Helper function renders text element for recording used in row for the data used by the recording table.
 *
 * @param array $recording
 * @param string $text
 * @param string $source
 * @param array $data
 *
 * @return string
 */
function bigbluebuttonbn_get_recording_data_row_text($recording, $text, $source, $data) {
    $htmltext = '<span>' . htmlentities($text) . '</span>';
    if (empty($data)) {
        return $htmltext;
    }
    $target = $data['action'] . '-' . $data['target'];
    $id = 'recording-' . $target . '-' . $data['recordingid'];
    $attributes = array('id' => $id, 'class' => 'quickeditlink col-md-20',
        'data-recordingid' => $data['recordingid'], 'data-meetingid' => $data['meetingid'],
        'data-target' => $data['target'], 'data-source' => $source);
    $head = html_writer::start_tag('div', $attributes);
    $tail = html_writer::end_tag('div');
    $payload = array('action' => $data['action'], 'tag' => $data['tag'], 'target' => $data['target']);
    $htmllink = bigbluebuttonbn_actionbar_render_button($recording, $payload);
    return $head . $htmltext . $htmllink . $tail;
}

/**
 * Helper function render a button for the recording action bar
 *
 * @param array $recording
 * @param array $data
 *
 * @return string
 */
function bigbluebuttonbn_actionbar_render_button($recording, $data) {
    global $OUTPUT;
    if (empty($data)) {
        return '';
    }
    $target = $data['action'];
    if (isset($data['target'])) {
        $target .= '-' . $data['target'];
    }
    $id = 'recording-' . $target . '-' . $recording['recordID'];
    $onclick = 'M.mod_bigbluebuttonbn.recordings.recording' . ucfirst($data['action']) . '(this);';
    if ((boolean)\mod_bigbluebuttonbn\locallib\config::get('recording_icons_enabled')) {
        // With icon for $manageaction.
        $iconattributes = array('id' => $id, 'class' => 'iconsmall');
        $linkattributes = array(
            'id' => $id,
            'onclick' => $onclick,
            'data-action' => $data['action']
          );
        if (!isset($recording['imported'])) {
            $linkattributes['data-links'] = bigbluebuttonbn_count_recording_imported_instances(
              $recording['recordID']);
        }
        if (isset($data['disabled'])) {
            $iconattributes['class'] .= ' fa-' . $data['disabled'];
            $linkattributes['class'] = 'disabled';
            unset($linkattributes['onclick']);
        }
        $icon = new pix_icon('i/'.$data['tag'],
            get_string('view_recording_list_actionbar_' . $data['action'], 'bigbluebuttonbn'),
            'moodle', $iconattributes);
        return $OUTPUT->action_icon('#', $icon, null, $linkattributes, false);
    }
    // With text for $manageaction.
    $linkattributes = array('title' => get_string($data['tag']), 'class' => 'btn btn-xs btn-danger',
        'onclick' => $onclick);
    return $OUTPUT->action_link('#', get_string($data['action']), null, $linkattributes);
}

/**
 * Helper function builds the data used for headers by the recording table.
 *
 * @param array $bbbsession
 *
 * @return array
 */
function bigbluebuttonbn_get_recording_columns($bbbsession) {
    $columns = array();
    // Initialize table headers.
    $columns[] = array('key' => 'recording', 'label' => get_string('view_recording_recording', 'bigbluebuttonbn'),
        'width' => '125px', 'allowHTML' => true);
    $columns[] = array('key' => 'activity', 'label' => get_string('view_recording_activity', 'bigbluebuttonbn'),
        'sortable' => true, 'width' => '175px', 'allowHTML' => true);
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
    return $columns;
}

/**
 * Helper function builds the data used by the recording table.
 *
 * @param array $bbbsession
 * @param array $recordings
 * @param array $tools
 *
 * @return array
 */
function bigbluebuttonbn_get_recording_data($bbbsession, $recordings, $tools = ['protect', 'publish', 'delete']) {
    $tabledata = array();
    // Build table content.
    if (isset($recordings) && !array_key_exists('messageKey', $recordings)) {
        // There are recordings for this meeting.
        foreach ($recordings as $recording) {
            $rowdata = bigbluebuttonbn_get_recording_data_row($bbbsession, $recording, $tools);
            if (!empty($rowdata)) {
                array_push($tabledata, $rowdata);
            }
        }
    }
    return $tabledata;
}

/**
 * Helper function builds the recording table.
 *
 * @param array $bbbsession
 * @param array $recordings
 * @param array $tools
 *
 * @return object
 */
function bigbluebuttonbn_get_recording_table($bbbsession, $recordings, $tools = ['protect', 'publish', 'delete']) {
    // Declare the table.
    $table = new html_table();
    $table->data = array();
    // Initialize table headers.
    $table->head[] = get_string('view_recording_playback', 'bigbluebuttonbn');
    $table->head[] = get_string('view_recording_recording', 'bigbluebuttonbn');
    $table->head[] = get_string('view_recording_description', 'bigbluebuttonbn');
    if (bigbluebuttonbn_get_recording_data_preview_enabled($bbbsession)) {
        $table->head[] = get_string('view_recording_preview', 'bigbluebuttonbn');
    }
    $table->head[] = get_string('view_recording_date', 'bigbluebuttonbn');
    $table->head[] = get_string('view_recording_duration', 'bigbluebuttonbn');
    $table->align = array('left', 'left', 'left', 'left', 'left', 'center');
    $table->size = array('', '', '', '', '', '');
    if ($bbbsession['managerecordings']) {
        $table->head[] = get_string('view_recording_actionbar', 'bigbluebuttonbn');
        $table->align[] = 'left';
        $table->size[] = (count($tools) * 40) . 'px';
    }
    // Build table content.
    foreach ($recordings as $recording) {
        $rowdata = bigbluebuttonbn_get_recording_data_row($bbbsession, $recording, $tools);
        if (!empty($rowdata)) {
            $row = bigbluebuttonbn_get_recording_table_row($bbbsession, $recording, $rowdata);
            array_push($table->data, $row);
        }
    }
    return $table;
}

/**
 * Helper function builds the recording table row and insert into table.
 *
 * @param array $bbbsession
 * @param array $recording
 * @param object $rowdata
 *
 * @return object
 */
function bigbluebuttonbn_get_recording_table_row($bbbsession, $recording, $rowdata) {
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
    $row->cells = array();
    $row->cells[] = $texthead . $rowdata->recording . $texttail;
    $row->cells[] = $texthead . $rowdata->activity . $texttail;
    $row->cells[] = $texthead . $rowdata->description . $texttail;
    if (bigbluebuttonbn_get_recording_data_preview_enabled($bbbsession)) {
        $row->cells[] = $rowdata->preview;
    }
    $row->cells[] = $texthead . $rowdata->date_formatted . $texttail;
    $row->cells[] = $rowdata->duration_formatted;
    if ($bbbsession['managerecordings']) {
        $row->cells[] = $rowdata->actionbar;
    }
    return $row;
}

/**
 * Helper function evaluates if recording row should be included in the table.
 *
 * @param array $bbbsession
 * @param array $recording
 *
 * @return boolean
 */
function bigbluebuttonbn_include_recording_table_row($bbbsession, $recording) {
    // Exclude unpublished recordings, only if user has no rights to manage them.
    if ($recording['published'] != 'true' && !$bbbsession['managerecordings']) {
        return false;
    }
    // Imported recordings are always shown as long as they are published.
    if (isset($recording['imported'])) {
        return true;
    }
    // When groups are enabled, exclude those to which the user doesn't have access to.
    if (isset($bbbsession['group']) && $recording['meetingID'] != $bbbsession['meetingid']) {
        return false;
    }
    return true;
}

/**
 * Helper function triggers a send notification when the recording is ready.
 *
 * @param object $bigbluebuttonbn
 *
 * @return void
 */
function bigbluebuttonbn_send_notification_recording_ready($bigbluebuttonbn) {
    $sender = get_admin();
    // Prepare message.
    $messagetext = '<p>'.get_string('email_body_recording_ready_for', 'bigbluebuttonbn').
        ' &quot;' . $bigbluebuttonbn->name . '&quot; '.
        get_string('email_body_recording_ready_is_ready', 'bigbluebuttonbn').'.</p>';
    $context = context_course::instance($bigbluebuttonbn->course);
    \mod_bigbluebuttonbn\locallib\notifier::notification_send($context, $sender, $bigbluebuttonbn, $messagetext);
}

/**
 * Helper evaluates if the bigbluebutton server used belongs to blindsidenetworks domain.
 *
 * @return boolean
 */
function bigbluebuttonbn_is_bn_server() {
    $parsedurl = parse_url(\mod_bigbluebuttonbn\locallib\config::get('server_url'));
    if (!isset($parsedurl['host'])) {
        return false;
    }
    $h = $parsedurl['host'];
    $hends = explode('.', $h);
    $hendslength = count($hends);
    return ($hends[$hendslength - 1] == 'com' && $hends[$hendslength - 2] == 'blindsidenetworks');
}

/**
 * Helper function returns a list of courses a user has access to, wrapped in an array that can be used
 * by a html select.
 *
 * @param array $bbbsession
 *
 * @return array
 */
function bigbluebuttonbn_import_get_courses_for_select(array $bbbsession) {
    if ($bbbsession['administrator']) {
        $courses = get_courses('all', 'c.fullname ASC', 'c.id,c.shortname,c.fullname');
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

/**
 * Helper function renders recording table.
 *
 * @param array $bbbsession
 * @param array $recordings
 * @param array $tools
 *
 * @return array
 */
function bigbluebuttonbn_output_recording_table($bbbsession, $recordings, $tools = ['protect', 'publish', 'delete']) {
    if (isset($recordings) && !empty($recordings)) {
        // There are recordings for this meeting.
        $table = bigbluebuttonbn_get_recording_table($bbbsession, $recordings, $tools);
    }
    if (!isset($table) || !isset($table->data)) {
        // Render a table with "No recordings".
        return html_writer::div(get_string('view_message_norecordings', 'bigbluebuttonbn'), '',
            array('id' => 'bigbluebuttonbn_recordings_table'));
    }
    // Render the table.
    return html_writer::div(html_writer::table($table), '', array('id' => 'bigbluebuttonbn_recordings_table'));
}

/**
 * Helper function to convert an html string to plain text.
 *
 * @param string $html
 * @param integer $len
 *
 * @return string
 */
function bigbluebuttonbn_html2text($html, $len = 0) {
    $text = strip_tags($html);
    $text = str_replace('&nbsp;', ' ', $text);
    $textlen = strlen($text);
    $text = substr($text, 0, $len);
    if ($textlen > $len) {
        $text .= '...';
    }
    return $text;
}

/**
 * Helper function to obtain the tags linked to a bigbluebuttonbn activity
 *
 * @param string $id
 *
 * @return string containing the tags separated by commas
 */
function bigbluebuttonbn_get_tags($id) {
    if (class_exists('core_tag_tag')) {
        return implode(',', core_tag_tag::get_item_tags_array('core', 'course_modules', $id));
    }
    return implode(',', tag_get_tags('bigbluebuttonbn', $id));
}

/**
 * Helper function to define the sql used for gattering the bigbluebuttonbnids whose meetingids should be included
 * in the getRecordings request
 *
 * @param string $courseid
 * @param string $bigbluebuttonbnid
 * @param bool   $subset
 *
 * @return string containing the sql used for getting the target bigbluebuttonbn instances
 */
function bigbluebuttonbn_get_recordings_sql_select($courseid, $bigbluebuttonbnid = null, $subset = true) {
    if (empty($courseid)) {
        $courseid = 0;
    }
    if (empty($bigbluebuttonbnid)) {
        return "course = '{$courseid}'";
    }
    if ($subset) {
        return "id = '{$bigbluebuttonbnid}'";
    }
    return "id <> '{$bigbluebuttonbnid}' AND course = '{$courseid}'";
}

/**
 * Helper function to define the sql used for gattering the bigbluebuttonbnids whose meetingids should be included
 * in the getRecordings request considering only those that belong to deleted activities.
 *
 * @param string $courseid
 * @param string $bigbluebuttonbnid
 * @param bool   $subset
 *
 * @return string containing the sql used for getting the target bigbluebuttonbn instances
 */
function bigbluebuttonbn_get_recordings_deleted_sql_select($courseid = 0, $bigbluebuttonbnid = null, $subset = true) {
    $sql = "log = '" . BIGBLUEBUTTONBN_LOG_EVENT_DELETE . "' AND meta like '%has_recordings%' AND meta like '%true%'";
    if (empty($courseid)) {
        $courseid = 0;
    }
    if (empty($bigbluebuttonbnid)) {
        return $sql . " AND courseid = {$courseid}";
    }
    if ($subset) {
        return $sql . " AND bigbluebuttonbnid = '{$bigbluebuttonbnid}'";
    }
    return $sql . " AND courseid = {$courseid} AND bigbluebuttonbnid <> '{$bigbluebuttonbnid}'";
}

/**
 * Helper function to define the sql used for gattering the bigbluebuttonbnids whose meetingids should be included
 * in the getRecordings request considering only those that belong to imported recordings.
 *
 * @param string $courseid
 * @param string $bigbluebuttonbnid
 * @param bool   $subset
 *
 * @return string containing the sql used for getting the target bigbluebuttonbn instances
 */
function bigbluebuttonbn_get_recordings_imported_sql_select($courseid = 0, $bigbluebuttonbnid = null, $subset = true) {
    $sql = "log = '" . BIGBLUEBUTTONBN_LOG_EVENT_IMPORT . "'";
    if (empty($courseid)) {
        $courseid = 0;
    }
    if (empty($bigbluebuttonbnid)) {
        return $sql . " AND courseid = '{$courseid}'";
    }
    if ($subset) {
        return $sql . " AND bigbluebuttonbnid = '{$bigbluebuttonbnid}'";
    }
    return $sql . " AND courseid = '{$courseid}' AND bigbluebuttonbnid <> '{$bigbluebuttonbnid}'";
}

/**
 * Helper function to get recordings  and imported recordings together.
 *
 * @param string $courseid
 * @param string $bigbluebuttonbnid
 * @param bool   $subset
 * @param bool   $includedeleted
 *
 * @return associative array containing the recordings indexed by recordID, each recording is also a
 * non sequential associative array itself that corresponds to the actual recording in BBB
 */
function bigbluebuttonbn_get_allrecordings($courseid = 0, $bigbluebuttonbnid = null, $subset = true, $includedeleted = false) {
    $recordings = bigbluebuttonbn_get_recordings($courseid, $bigbluebuttonbnid, $subset, $includedeleted);
    $recordingsimported = bigbluebuttonbn_get_recordings_imported_array($courseid, $bigbluebuttonbnid, $subset);
    return ($recordings + $recordingsimported);
}

/**
 * Helper function to retrieve recordings from the BigBlueButton. The references are stored as events
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
function bigbluebuttonbn_get_recordings($courseid = 0, $bigbluebuttonbnid = null, $subset = true, $includedeleted = false) {
    global $DB;
    $select = bigbluebuttonbn_get_recordings_sql_select($courseid, $bigbluebuttonbnid, $subset);
    $bigbluebuttonbns = $DB->get_records_select_menu('bigbluebuttonbn', $select, null, 'id', 'id, meetingid');
    /* Consider logs from deleted bigbluebuttonbn instances whose meetingids should be included in
     * the getRecordings request. */
    if ($includedeleted) {
        $selectdeleted = bigbluebuttonbn_get_recordings_deleted_sql_select($courseid, $bigbluebuttonbnid, $subset);
        $bigbluebuttonbnsdel = $DB->get_records_select_menu('bigbluebuttonbn_logs', $selectdeleted, null,
            'bigbluebuttonbnid', 'bigbluebuttonbnid, meetingid');
        if (!empty($bigbluebuttonbnsdel)) {
            // Merge bigbluebuttonbnis from deleted instances, only keys are relevant.
            // Artimetic merge is used in order to keep the keys.
            $bigbluebuttonbns += $bigbluebuttonbnsdel;
        }
    }
    // Gather the meetingids from bigbluebuttonbn logs that include a create with record=true.
    if (empty($bigbluebuttonbns)) {
        return array();
    }
    // Prepare select for loading records based on existent bigbluebuttonbns.
    $sql = 'SELECT DISTINCT meetingid, bigbluebuttonbnid FROM {bigbluebuttonbn_logs} WHERE ';
    $sql .= '(bigbluebuttonbnid='.implode(' OR bigbluebuttonbnid=', array_keys($bigbluebuttonbns)).')';
    // Include only Create events and exclude those with record not true.
    $sql .= ' AND log = ? AND meta LIKE ? AND meta LIKE ?';
    // Execute select for loading records based on existent bigbluebuttonbns.
    $records = $DB->get_records_sql_menu($sql, array(BIGBLUEBUTTONBN_LOG_EVENT_CREATE, '%record%', '%true%'));
    // Get actual recordings.
    return bigbluebuttonbn_get_recordings_array(array_keys($records));
}

/**
 * Helper function iterates an array with recordings and unset those already imported.
 *
 * @param array $recordings
 * @param integer $courseid
 * @param integer $bigbluebuttonbnid
 *
 * @return array
 */
function bigbluebuttonbn_unset_existent_recordings_already_imported($recordings, $courseid, $bigbluebuttonbnid) {
    $recordingsimported = bigbluebuttonbn_get_recordings_imported_array($courseid, $bigbluebuttonbnid, true);
    foreach ($recordings as $key => $recording) {
        if (isset($recordingsimported[$recording['recordID']])) {
            unset($recordings[$key]);
        }
    }
    return $recordings;
}

/**
 * Helper function to count the imported recordings for a recordingid.
 *
 * @param string $recordid
 *
 * @return integer
 */
function bigbluebuttonbn_count_recording_imported_instances($recordid) {
    global $DB;
    $sql = 'SELECT COUNT(DISTINCT id) FROM {bigbluebuttonbn_logs} WHERE log = ? AND meta LIKE ? AND meta LIKE ?';
    return $DB->count_records_sql($sql, array(BIGBLUEBUTTONBN_LOG_EVENT_IMPORT, '%recordID%', "%{$recordid}%"));
}

/**
 * Helper function returns an array with all the instances of imported recordings for a recordingid.
 *
 * @param string $recordid
 *
 * @return array
 */
function bigbluebuttonbn_get_recording_imported_instances($recordid) {
    global $DB;
    $sql = 'SELECT * FROM {bigbluebuttonbn_logs} WHERE log = ? AND meta LIKE ? AND meta LIKE ?';
    $recordingsimported = $DB->get_records_sql($sql, array(BIGBLUEBUTTONBN_LOG_EVENT_IMPORT, '%recordID%',
        "%{$recordid}%"));
    return $recordingsimported;
}

/**
 * Helper function returns an array with the profiles (with features per profile) for the different types
 * of bigbluebuttonbn instances.
 *
 * @return array
 */
function bigbluebuttonbn_get_instance_type_profiles() {
    $instanceprofiles = array(
            array('id' => BIGBLUEBUTTONBN_TYPE_ALL, 'name' => get_string('instance_type_default', 'bigbluebuttonbn'),
                'features' => array('all')),
            array('id' => BIGBLUEBUTTONBN_TYPE_ROOM_ONLY, 'name' => get_string('instance_type_room_only', 'bigbluebuttonbn'),
                'features' => array('showroom', 'welcomemessage', 'voicebridge', 'waitformoderator', 'userlimit', 'recording',
                    'sendnotifications', 'preuploadpresentation', 'permissions', 'schedule', 'groups',
                    'modstandardelshdr', 'availabilityconditionsheader', 'tagshdr', 'competenciessection')),
            array('id' => BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY, 'name' => get_string('instance_type_recording_only',
                'bigbluebuttonbn'), 'features' => array('showrecordings', 'importrecordings')),
    );
    return $instanceprofiles;
}

/**
 * Helper function returns an array with enabled features for an specific profile type.
 *
 * @param array $typeprofiles
 * @param string $type
 *
 * @return array
 */
function bigbluebuttonbn_get_enabled_features($typeprofiles, $type = null) {
    $enabledfeatures = array();
    $features = $typeprofiles[0]['features'];
    if (!is_null($type)) {
        $features = $typeprofiles[$type]['features'];
    }
    $enabledfeatures['showroom'] = (in_array('all', $features) || in_array('showroom', $features));
    // Evaluates if recordings are enabled for the Moodle site.
    $enabledfeatures['showrecordings'] = false;
    if (\mod_bigbluebuttonbn\locallib\config::recordings_enabled()) {
        $enabledfeatures['showrecordings'] = (in_array('all', $features) || in_array('showrecordings', $features));
    }
    $enabledfeatures['importrecordings'] = false;
    if (\mod_bigbluebuttonbn\locallib\config::importrecordings_enabled()) {
        $enabledfeatures['importrecordings'] = (in_array('all', $features) || in_array('importrecordings', $features));
    }
    return $enabledfeatures;
}

/**
 * Helper function returns an array with the profiles (with features per profile) for the different types
 * of bigbluebuttonbn instances.
 *
 * @param array $profiles
 *
 * @return array
 */
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

/**
 * Helper function returns time in a formatted string.
 *
 * @param integer $time
 *
 * @return string
 */
function bigbluebuttonbn_format_activity_time($time) {
    $activitytime = '';
    if ($time) {
        $activitytime = calendar_day_representation($time).' '.
          get_string('mod_form_field_notification_msg_at', 'bigbluebuttonbn').' '.
          calendar_time_representation($time);
    }
    return $activitytime;
}

/**
 * Helper function returns array with all the strings to be used in javascript.
 *
 * @return array
 */
function bigbluebuttonbn_get_strings_for_js() {
    $locale = bigbluebuttonbn_get_locale();
    $stringman = get_string_manager();
    $strings = $stringman->load_component_strings('bigbluebuttonbn', $locale);
    return $strings;
}

/**
 * Helper function returns the locale set by moodle.
 *
 * @return string
 */
function bigbluebuttonbn_get_locale() {
    $lang = get_string('locale', 'core_langconfig');
    return substr($lang, 0, strpos($lang, '.'));
}

/**
 * Helper function returns the locale code based on the locale set by moodle.
 *
 * @return string
 */
function bigbluebuttonbn_get_localcode() {
    $locale = bigbluebuttonbn_get_locale();
    return substr($locale, 0, strpos($locale, '_'));
}

/**
 * Helper function returns array with the instance settings used in views.
 *
 * @param string $id
 * @param object $bigbluebuttonbnid
 *
 * @return array
 */
function bigbluebuttonbn_view_validator($id, $bigbluebuttonbnid) {
    if ($id) {
        return bigbluebuttonbn_view_instance_id($id);
    }
    if ($bigbluebuttonbnid) {
        return bigbluebuttonbn_view_instance_bigbluebuttonbn($bigbluebuttonbnid);
    }
    return;
}

/**
 * Helper function returns array with the instance settings used in views based on id.
 *
 * @param string $id
 *
 * @return array
 */
function bigbluebuttonbn_view_instance_id($id) {
    global $DB;
    $cm = get_coursemodule_from_id('bigbluebuttonbn', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $cm->instance), '*', MUST_EXIST);
    return array('cm' => $cm, 'course' => $course, 'bigbluebuttonbn' => $bigbluebuttonbn);
}

/**
 * Helper function returns array with the instance settings used in views based on bigbluebuttonbnid.
 *
 * @param object $bigbluebuttonbnid
 *
 * @return array
 */
function bigbluebuttonbn_view_instance_bigbluebuttonbn($bigbluebuttonbnid) {
    global $DB;
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $bigbluebuttonbnid), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $bigbluebuttonbn->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('bigbluebuttonbn', $bigbluebuttonbn->id, $course->id, false, MUST_EXIST);
    return array('cm' => $cm, 'course' => $course, 'bigbluebuttonbn' => $bigbluebuttonbn);
}

/**
 * Helper function renders general warning message for settings (if any).
 *
 * @param object $renderer
 *
 * @return void
 */
function bigbluebuttonbn_settings_general_warning(&$renderer) {
    return;
}

/**
 * Helper function renders general settings if the feature is enabled.
 *
 * @param object $renderer
 *
 * @return void
 */
function bigbluebuttonbn_settings_general(&$renderer) {
    // Configuration for BigBlueButton.
    if ((boolean)\mod_bigbluebuttonbn\settings\validator::section_general_shown()) {
        $renderer->render_group_header('general');
        $renderer->render_group_element('server_url',
            $renderer->render_group_element_text('server_url', BIGBLUEBUTTONBN_DEFAULT_SERVER_URL));
        $renderer->render_group_element('shared_secret',
            $renderer->render_group_element_text('shared_secret', BIGBLUEBUTTONBN_DEFAULT_SHARED_SECRET));
    }
}

/**
 * Helper function renders record settings if the feature is enabled.
 *
 * @param object $renderer
 *
 * @return void
 */
function bigbluebuttonbn_settings_record(&$renderer) {
    // Configuration for 'recording' feature.
    if ((boolean)\mod_bigbluebuttonbn\settings\validator::section_record_meeting_shown()) {
        $renderer->render_group_header('recording');
        $renderer->render_group_element('recording_default',
            $renderer->render_group_element_checkbox('recording_default', 1));
        $renderer->render_group_element('recording_editable',
            $renderer->render_group_element_checkbox('recording_editable', 1));
        $renderer->render_group_element('recording_icons_enabled',
            $renderer->render_group_element_checkbox('recording_icons_enabled', 1));
    }
}

/**
 * Helper function renders import recording settings if the feature is enabled.
 *
 * @param object $renderer
 *
 * @return void
 */
function bigbluebuttonbn_settings_importrecordings(&$renderer) {
    // Configuration for 'import recordings' feature.
    if ((boolean)\mod_bigbluebuttonbn\settings\validator::section_import_recordings_shown()) {
        $renderer->render_group_header('importrecordings');
        $renderer->render_group_element('importrecordings_enabled',
            $renderer->render_group_element_checkbox('importrecordings_enabled', 0));
        $renderer->render_group_element('importrecordings_from_deleted_enabled',
            $renderer->render_group_element_checkbox('importrecordings_from_deleted_enabled', 0));
    }
}

/**
 * Helper function renders show recording settings if the feature is enabled.
 *
 * @param object $renderer
 *
 * @return void
 */
function bigbluebuttonbn_settings_showrecordings(&$renderer) {
    // Configuration for 'show recordings' feature.
    if ((boolean)\mod_bigbluebuttonbn\settings\validator::section_show_recordings_shown()) {
        $renderer->render_group_header('recordings');
        $renderer->render_group_element('recordings_html_default',
            $renderer->render_group_element_checkbox('recordings_html_default', 1));
        $renderer->render_group_element('recordings_html_editable',
            $renderer->render_group_element_checkbox('recordings_html_editable', 0));
        $renderer->render_group_element('recordings_deleted_default',
            $renderer->render_group_element_checkbox('recordings_deleted_default', 1));
        $renderer->render_group_element('recordings_deleted_editable',
            $renderer->render_group_element_checkbox('recordings_deleted_editable', 0));
        $renderer->render_group_element('recordings_imported_default',
            $renderer->render_group_element_checkbox('recordings_imported_default', 0));
        $renderer->render_group_element('recordings_imported_editable',
            $renderer->render_group_element_checkbox('recordings_imported_editable', 1));
        $renderer->render_group_element('recordings_preview_default',
            $renderer->render_group_element_checkbox('recordings_preview_default', 1));
        $renderer->render_group_element('recordings_preview_editable',
            $renderer->render_group_element_checkbox('recordings_preview_editable', 0));
    }
}

/**
 * Helper function renders wait for moderator settings if the feature is enabled.
 *
 * @param object $renderer
 *
 * @return void
 */
function bigbluebuttonbn_settings_waitmoderator(&$renderer) {
    // Configuration for wait for moderator feature.
    if ((boolean)\mod_bigbluebuttonbn\settings\validator::section_wait_moderator_shown()) {
        $renderer->render_group_header('waitformoderator');
        $renderer->render_group_element('waitformoderator_default',
            $renderer->render_group_element_checkbox('waitformoderator_default', 0));
        $renderer->render_group_element('waitformoderator_editable',
            $renderer->render_group_element_checkbox('waitformoderator_editable', 1));
        $renderer->render_group_element('waitformoderator_ping_interval',
            $renderer->render_group_element_text('waitformoderator_ping_interval', 10, PARAM_INT));
        $renderer->render_group_element('waitformoderator_cache_ttl',
            $renderer->render_group_element_text('waitformoderator_cache_ttl', 60, PARAM_INT));
    }
}

/**
 * Helper function renders static voice bridge settings if the feature is enabled.
 *
 * @param object $renderer
 *
 * @return void
 */
function bigbluebuttonbn_settings_voicebridge(&$renderer) {
    // Configuration for "static voice bridge" feature.
    if ((boolean)\mod_bigbluebuttonbn\settings\validator::section_static_voice_bridge_shown()) {
        $renderer->render_group_header('voicebridge');
        $renderer->render_group_element('voicebridge_editable',
            $renderer->render_group_element_checkbox('voicebridge_editable', 0));
    }
}

/**
 * Helper function renders preuploaded presentation settings if the feature is enabled.
 *
 * @param object $renderer
 *
 * @return void
 */
function bigbluebuttonbn_settings_preupload(&$renderer) {
    // Configuration for "preupload presentation" feature.
    if ((boolean)\mod_bigbluebuttonbn\settings\validator::section_preupload_presentation_shown()) {
        // This feature only works if curl is installed.
        $preuploaddescripion = get_string('config_preuploadpresentation_description', 'bigbluebuttonbn');
        if (!extension_loaded('curl')) {
            $preuploaddescripion .= '<div class="form-defaultinfo">';
            $preuploaddescripion .= get_string('config_warning_curl_not_installed', 'bigbluebuttonbn');
            $preuploaddescripion .= '</div><br>';
        }
        $renderer->render_group_header('preuploadpresentation', null, $preuploaddescripion);
        if (extension_loaded('curl')) {
            $renderer->render_group_element('preuploadpresentation_enabled',
                $renderer->render_group_element_checkbox('preuploadpresentation_enabled', 0));
        }
    }
}

/**
 * Helper function renders userlimit settings if the feature is enabled.
 *
 * @param object $renderer
 *
 * @return void
 */
function bigbluebuttonbn_settings_userlimit(&$renderer) {
    // Configuration for "user limit" feature.
    if ((boolean)\mod_bigbluebuttonbn\settings\validator::section_user_limit_shown()) {
        $renderer->render_group_header('userlimit');
        $renderer->render_group_element('userlimit_default',
            $renderer->render_group_element_text('userlimit_default', 0, PARAM_INT));
        $renderer->render_group_element('userlimit_editable',
            $renderer->render_group_element_checkbox('userlimit_editable', 0));
    }
}

/**
 * Helper function renders duration settings if the feature is enabled.
 *
 * @param object $renderer
 *
 * @return void
 */
function bigbluebuttonbn_settings_duration(&$renderer) {
    // Configuration for "scheduled duration" feature.
    if ((boolean)\mod_bigbluebuttonbn\settings\validator::section_scheduled_duration_shown()) {
        $renderer->render_group_header('scheduled');
        $renderer->render_group_element('scheduled_duration_enabled',
            $renderer->render_group_element_checkbox('scheduled_duration_enabled', 1));
        $renderer->render_group_element('scheduled_duration_compensation',
            $renderer->render_group_element_text('scheduled_duration_compensation', 10, PARAM_INT));
        $renderer->render_group_element('scheduled_pre_opening',
            $renderer->render_group_element_text('scheduled_pre_opening', 10, PARAM_INT));
    }
}

/**
 * Helper function renders participant settings if the feature is enabled.
 *
 * @param object $renderer
 *
 * @return void
 */
function bigbluebuttonbn_settings_participants(&$renderer) {
    // Configuration for defining the default role/user that will be moderator on new activities.
    if ((boolean)\mod_bigbluebuttonbn\settings\validator::section_moderator_default_shown()) {
        $renderer->render_group_header('participant');
        // UI for 'participants' feature.
        $roles = bigbluebuttonbn_get_roles();
        $owner = array('0' => get_string('mod_form_field_participant_list_type_owner', 'bigbluebuttonbn'));
        $renderer->render_group_element('participant_moderator_default',
            $renderer->render_group_element_configmultiselect('participant_moderator_default',
                array_keys($owner), array_merge($owner, $roles))
          );
    }
}

/**
 * Helper function renders notification settings if the feature is enabled.
 *
 * @param object $renderer
 *
 * @return void
 */
function bigbluebuttonbn_settings_notifications(&$renderer) {
    // Configuration for "send notifications" feature.
    if ((boolean)\mod_bigbluebuttonbn\settings\validator::section_send_notifications_shown()) {
        $renderer->render_group_header('sendnotifications');
        $renderer->render_group_element('sendnotifications_enabled',
            $renderer->render_group_element_checkbox('sendnotifications_enabled', 1));
    }
}

/**
 * Helper function renders extended settings if any of the features there is enabled.
 *
 * @param object $renderer
 *
 * @return void
 */
function bigbluebuttonbn_settings_extended(&$renderer) {
    // Configuration for extended BN capabilities.
    if (!bigbluebuttonbn_is_bn_server()) {
        return;
    }
    // Configuration for 'notify users when recording ready' feature.
    if ((boolean)\mod_bigbluebuttonbn\settings\validator::section_settings_extended_shown()) {
        $renderer->render_group_header('extended_capabilities');
        // UI for 'notify users when recording ready' feature.
        $renderer->render_group_element('recordingready_enabled',
            $renderer->render_group_element_checkbox('recordingready_enabled', 0));
        // UI for 'register meeting events' feature.
        $renderer->render_group_element('meetingevents_enabled',
            $renderer->render_group_element_checkbox('meetingevents_enabled', 0));
    }
}

/**
 * Helper function returns an encoded meetingid.
 *
 * @param string $seed
 *
 * @return string
 */
function bigbluebuttonbn_encode_meetingid($seed) {
    global $CFG;
    return sha1($CFG->wwwroot . $seed . \mod_bigbluebuttonbn\locallib\config::get('shared_secret'));
}

/**
 * Helper function renders the link used for recording type in row for the data used by the recording table.
 *
 * @param array $recording
 * @param array $bbbsession
 * @param array $playback
 *
 * @return boolean
 */
function bigbluebuttonbn_include_recording_data_row_type($recording, $bbbsession, $playback) {
    // All types that are not statistics are included.
    if ($playback['type'] != 'statistics') {
        return true;
    }
    // Exclude imported recordings.
    if (isset($recording['imported'])) {
        return false;
    }
    // Exclude non moderators.
    if (!$bbbsession['administrator'] && !$bbbsession['moderator']) {
        return false;
    }
    return true;
}

/**
 * Renders the general warning message.
 *
 * @param string $message
 * @param string $type
 * @param string $href
 * @param string $text
 * @param string $class
 *
 * @return string
 */
function bigbluebuttonbn_render_warning($message, $type='info', $href='', $text='', $class='') {
    global $OUTPUT;
    $output = "\n";
    // Evaluates if config_warning is enabled.
    if (empty($message)) {
        return $output;
    }
    $output .= $OUTPUT->box_start('box boxalignleft adminerror alert alert-' . $type . ' alert-block fade in',
      'bigbluebuttonbn_view_general_warning') . "\n";
    $output .= '    ' . $message . "\n";
    $output .= '  <div class="singlebutton pull-right">' . "\n";
    if (!empty($href)) {
        $output .= bigbluebuttonbn_render_warning_button($href, $text, $class);
    }
    $output .= '  </div>' . "\n";
    $output .= $OUTPUT->box_end() . "\n";
    return $output;
}

/**
 * Renders the general warning button.
 *
 * @param string $href
 * @param string $text
 * @param string $class
 * @param string $title
 *
 * @return string
 */
function bigbluebuttonbn_render_warning_button($href, $text = '', $class = '', $title = '') {
    if ($text == '') {
        $text = get_string('ok', 'moodle');
    }
    if ($title == '') {
        $title = $text;
    }
    if ($class == '') {
        $class = 'btn btn-secondary';
    }
    $output  = '  <form method="post" action="' . $href . '" class="form-inline">'."\n";
    $output .= '      <button type="submit" class="' . $class . '"'."\n";
    $output .= '          title="' . $title . '"'."\n";
    $output .= '          >' . $text . '</button>'."\n";
    $output .= '  </form>'."\n";
    return $output;
}
