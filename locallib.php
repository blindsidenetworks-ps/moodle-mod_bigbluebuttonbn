<?php
/**
 * Internal library of functions for module BigBlueButtonBN.
 *
 * @package   mod
 * @subpackage bigbluebuttonbn
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2010-2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die;

global $BIGBLUEBUTTONBN_CFG, $CFG;

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
const BIGBLUEBUTTON_EVENT_MEETING_LEFT = "meeting_left";
const BIGBLUEBUTTON_EVENT_MEETING_EVENT = "meeting_event";
const BIGBLUEBUTTON_EVENT_RECORDING_DELETED = 'recording_deleted';
const BIGBLUEBUTTON_EVENT_RECORDING_IMPORTED = 'recording_imported';
const BIGBLUEBUTTON_EVENT_RECORDING_PUBLISHED = 'recording_published';
const BIGBLUEBUTTON_EVENT_RECORDING_UNPUBLISHED = 'recording_unpublished';

function bigbluebuttonbn_logs(array $bbbsession, $event, array $overrides = [], $meta = NULL) {
    global $DB;

    $log = new stdClass();

    $log->courseid = isset($overrides['courseid'])? $overrides['courseid']: $bbbsession['course']->id;
    $log->bigbluebuttonbnid = isset($overrides['bigbluebuttonbnid'])? $overrides['bigbluebuttonbnid']: $bbbsession['bigbluebuttonbn']->id;
    $log->userid = isset($overrides['userid'])? $overrides['userid']: $bbbsession['userID'];
    $log->meetingid = isset($overrides['meetingid'])? $overrides['meetingid']: $bbbsession['meetingid'];
    $log->timecreated = isset($overrides['timecreated'])? $overrides['timecreated']: time();
    $log->log = $event;
    if (isset($meta)) {
        $log->meta = $meta;
    } else if ($event == BIGBLUEBUTTONBN_LOG_EVENT_CREATE) {
        $log->meta = '{"record":'.($bbbsession['record']? 'true': 'false').'}';
    }

    $returnid = $DB->insert_record('bigbluebuttonbn_logs', $log);
}

 ////////////////////////////
//  BigBlueButton API Calls  //
 ////////////////////////////
function bigbluebuttonbn_getJoinURL( $meetingID, $userName, $PW, $SALT, $URL, $logoutURL, $configToken=NULL, $userId=NULL) {
    $url_join = $URL."api/join?";
    $params = 'meetingID='.urlencode($meetingID).'&fullName='.urlencode($userName).'&password='.urlencode($PW).'&logoutURL='.urlencode($logoutURL);
    if (!is_null($userId)) {
        $params .= "&userID=".urlencode($userId);
    }
    if (!is_null($configToken)) {
        $params .= '&configToken='.$configToken;
    }
    $url = $url_join.$params.'&checksum='.sha1("join".$params.$SALT);
    return $url;
}

function bigbluebuttonbn_getCreateMeetingURL($name, $meetingID, $attendeePW, $moderatorPW, $welcome, $logoutURL, $SALT, $URL, $record = 'false', $duration=0, $voiceBridge=0, $maxParticipants=0, $metadata=array()) {
    $url_create = $URL."api/create?";

    $params = 'name='.urlencode($name).'&meetingID='.urlencode($meetingID).'&attendeePW='.urlencode($attendeePW).'&moderatorPW='.urlencode($moderatorPW).'&logoutURL='.urlencode($logoutURL).'&record='.$record;

    $voiceBridge = intval($voiceBridge);
    if ($voiceBridge > 0 && $voiceBridge < 79999) {
        $params .= '&voiceBridge='.$voiceBridge;
    }

    $duration = intval($duration);
    if ($duration > 0) {
        $params .= '&duration='.$duration;
    }

    $maxParticipants = intval($maxParticipants);
    if ($maxParticipants > 0) {
        $params .= '&maxParticipants='.$maxParticipants;
    }

    if (trim( $welcome )) {
        $params .= '&welcome='.urlencode($welcome);
    }

    foreach ($metadata as $key => $value) {
        $params .= '&'.$key.'='.urlencode($value);
    }

    $url = $url_create.$params.'&checksum='.sha1("create".$params.$SALT);
    return $url;
}

function bigbluebuttonbn_getIsMeetingRunningURL( $meetingID, $URL, $SALT) {
    $base_url = $URL."api/isMeetingRunning?";
    $params = 'meetingID='.urlencode($meetingID);
    $url = $base_url.$params.'&checksum='.sha1("isMeetingRunning".$params.$SALT);
    return $url;
}

function bigbluebuttonbn_getMeetingInfoURL( $meetingID, $modPW, $URL, $SALT) {
    $base_url = $URL."api/getMeetingInfo?";
    $params = 'meetingID='.urlencode($meetingID).'&password='.urlencode($modPW);
    $url = $base_url.$params.'&checksum='.sha1("getMeetingInfo".$params.$SALT);
    return $url;
}

function bigbluebuttonbn_getMeetingsURL($URL, $SALT) {
    $base_url = $URL."api/getMeetings?";
    $url = $base_url.'&checksum='.sha1("getMeetings".$SALT);
    return $url;
}

function bigbluebuttonbn_getEndMeetingURL($meetingID, $modPW, $URL, $SALT) {
    $base_url = $URL."api/end?";
    $params = 'meetingID='.urlencode($meetingID).'&password='.urlencode($modPW);
    $url = $base_url.$params.'&checksum='.sha1("end".$params.$SALT);
    return $url;
}

function bigbluebuttonbn_getRecordingsURL($meetingID, $URL, $SALT) {
    $base_url_record = $URL."api/getRecordings?";
    $params = "meetingID=".urlencode($meetingID);
    $url = $base_url_record.$params."&checksum=".sha1("getRecordings".$params.$SALT);
    return $url;
}

function bigbluebuttonbn_getDeleteRecordingsURL($recordID, $URL, $SALT) {
    $url_delete = $URL."api/deleteRecordings?";
    $params = 'recordID='.urlencode($recordID);
    $url = $url_delete.$params.'&checksum='.sha1("deleteRecordings".$params.$SALT);
    return $url;
}

function bigbluebuttonbn_getPublishRecordingsURL($recordID, $set, $URL, $SALT) {
    $url_publish = $URL."api/publishRecordings?";
    $params = 'recordID='.$recordID."&publish=".$set;
    $url = $url_publish.$params.'&checksum='.sha1("publishRecordings".$params.$SALT);
    return $url;
}

function bigbluebuttonbn_getUpdateRecordingsURL($recordID, $URL, $SALT, $metadata=array()) {
    $url_update = $URL."api/updateRecordings?";
    $params = 'recordID='.$recordID.$meta;
    foreach ($metadata as $key => $value) {
        $params .= '&'.$key.'='.urlencode($value);
    }
    $url = $url_update.$params.'&checksum='.sha1("updateRecordings".$params.$SALT);
    return $url;
}

function bigbluebuttonbn_getDefaultConfigXMLURL($URL, $SALT) {
    $url_default_config = $URL."api/getDefaultConfigXML?";
    $params = '';
    $url = $url_default_config.$params.'&checksum='.sha1("getDefaultConfigXML".$params.$SALT);
    return $url;
}

function bigbluebuttonbn_getCreateMeetingArray($username, $meetingID, $welcomeString, $mPW, $aPW, $SALT, $URL, $logoutURL, $record='false', $duration=0, $voiceBridge=0, $maxParticipants=0, $metadata=array(), $presentation_name=NULL, $presentation_url=NULL) {
    $create_meeting_url = bigbluebuttonbn_getCreateMeetingURL($username, $meetingID, $aPW, $mPW, $welcomeString, $logoutURL, $SALT, $URL, $record, $duration, $voiceBridge, $maxParticipants, $metadata);
    if (!is_null($presentation_name) && !is_null($presentation_url)) {
        $xml = bigbluebuttonbn_wrap_xml_load_file( $create_meeting_url,
                BIGBLUEBUTTONBN_METHOD_POST,
                "<?xml version='1.0' encoding='UTF-8'?><modules><module name='presentation'><document url='".$presentation_url."' /></module></modules>"
                );
    } else {
        $xml = bigbluebuttonbn_wrap_xml_load_file( $create_meeting_url );
    }

    if ($xml) {
        if ($xml->meetingID) {
            return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey, 'meetingID' => $xml->meetingID, 'attendeePW' => $xml->attendeePW, 'moderatorPW' => $xml->moderatorPW, 'hasBeenForciblyEnded' => $xml->hasBeenForciblyEnded );
        } else {
            return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey );
        }
    } else {
        return null;
    }
}

function bigbluebuttonbn_getMeetingArray( $meetingID, $URL, $SALT) {
    $meetings = bigbluebuttonbn_getMeetingsArray( $URL, $SALT );
    if ($meetings) {
        foreach ( $meetings as $meeting) {
            if ($meeting['meetingID'] == $meetingID) {
                return $meeting;
            }
        }
    }
    return null;
}

function bigbluebuttonbn_getMeetingsArray( $URL, $SALT) {
    $xml = bigbluebuttonbn_wrap_xml_load_file( bigbluebuttonbn_getMeetingsURL($URL, $SALT) );

    if ($xml && $xml->returncode == 'SUCCESS' && $xml->messageKey) {    //The meetings were returned
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);

    } else if($xml && $xml->returncode == 'SUCCESS') {                    //If there were meetings already created
        foreach ($xml->meetings->meeting as $meeting) {
            $meetings[] = array( 'meetingID' => $meeting->meetingID, 'moderatorPW' => $meeting->moderatorPW, 'attendeePW' => $meeting->attendeePW, 'hasBeenForciblyEnded' => $meeting->hasBeenForciblyEnded, 'running' => $meeting->running );
        }
        return $meetings;

    } else if ($xml) { //If the xml packet returned failure it displays the message to the user
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);

    } else { //If the server is unreachable, then prompts the user of the necessary action
        return null;
    }
}

function bigbluebuttonbn_getMeetingInfo( $meetingID, $modPW, $URL, $SALT) {
    $xml = bigbluebuttonbn_wrap_xml_load_file( bigbluebuttonbn_getMeetingInfoURL( $meetingID, $modPW, $URL, $SALT ) );
    return $xml;
}

function bigbluebuttonbn_getMeetingInfoArray( $meetingID, $modPW, $URL, $SALT) {
    $xml = bigbluebuttonbn_wrap_xml_load_file( bigbluebuttonbn_getMeetingInfoURL( $meetingID, $modPW, $URL, $SALT ) );

    if ($xml && $xml->returncode == 'SUCCESS' && $xml->messageKey == NULL){//The meeting info was returned
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey );

    } else if($xml && $xml->returncode == 'SUCCESS'){ //If there were meetings already created
        return array('returncode' => $xml->returncode, 'meetingID' => $xml->meetingID, 'moderatorPW' => $xml->moderatorPW, 'attendeePW' => $xml->attendeePW, 'hasBeenForciblyEnded' => $xml->hasBeenForciblyEnded, 'running' => $xml->running, 'recording' => $xml->recording, 'startTime' => $xml->startTime, 'endTime' => $xml->endTime, 'participantCount' => $xml->participantCount, 'moderatorCount' => $xml->moderatorCount, 'attendees' => $xml->attendees, 'metadata' => $xml->metadata );

    } else if (($xml && $xml->returncode == 'FAILED') || $xml) { //If the xml packet returned failure it displays the message to the user
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);

    } else { //If the server is unreachable, then prompts the user of the necessary action
        return null;
    }
}

/**
 * helper function to retrieve recordings from a BigBlueButton server
 *
 * @param string or array $meetingIDs         an array or string containing a list of meetingIDs "mid1,mid2,mid3" or array("mid1","mid2","mid3")
 * @param string or array $recordingIDs       an array or string containing a list of $recordingIDs "rid1,rid2,rid3" or array("rid1","rid2","rid3") to be used as a filter
 * @param string $URL                         a string containing the BigBlueButton endpoint. If no parameter is passed the value is taken from the configuration.
 * @param string $SALT                        a string containing the BigBlueButton shared secret. If no parameter is passed the value is taken from the configuration.
 *
 * @return associative array containing the actual recordings indexed by recordID, each recording is also a non sequential associative array itself
 */

function bigbluebuttonbn_getRecordingsArray($meetingIDs, $recordingIDs=NULL, $URL=NULL, $SALT=NULL) {
    $recordings = array();

    $endpoint = is_null($URL) ? bigbluebuttonbn_get_cfg_server_url() : $URL;
    $shared_secret = is_null($SALT) ? bigbluebuttonbn_get_cfg_shared_secret() : $SALT;

    if (is_array($meetingIDs)) {
        $meetingIDsArray = $meetingIDs;
    } else {
        $meetingIDsArray = explode(",", $meetingIDs);
    }

    // If $meetingIDsArray is not empty a paginated getRecordings request is executed
    if (!empty($meetingIDsArray)) {
        $pages = floor(sizeof($meetingIDsArray) / 25) + 1;
        for ($page = 1; $page <= $pages; $page++) {
            $mIDs = array_slice($meetingIDsArray, ($page-1)*25, 25);
            // getRecordings is executed using a method GET (supported by all versions of BBB)
            $xml = bigbluebuttonbn_wrap_xml_load_file(bigbluebuttonbn_getRecordingsURL(implode(',', $mIDs), $endpoint, $shared_secret));
            if ($xml && $xml->returncode == 'SUCCESS' && isset($xml->recordings)) { //If there were meetings already created
                foreach ( $xml->recordings->recording as $recording) {
                    $recording_array_value = bigbluebuttonbn_getRecordingArrayValue($recording);
                    $recordings[$recording_array_value['recordID']] = $recording_array_value;
                }
                uasort($recordings, 'bigbluebuttonbn_recordingBuildSorter');
            }
        }
    }

    // Filter recordings based on recordingIDs
    if (!is_null($recordingIDs)) {
        if (is_array($recordingIDs)) {
            $recordingIDsArray = $recordingIDs;
        } else {
            $recordingIDsArray = explode(",", $recordingIDs);
        }

        foreach ($recordings as $key => $recording) {
            if ( !in_array($recording['recordID'], $recordingIDsArray) ) {
                unset($recordings[$key]);
            }
        }
    }

    return $recordings;
}

 /**
  * helper function to retrieve imported recordings from the Moodle database. The references are stored as events in bigbluebuttonbn_logs
  *
  * @param string $courseID
  * @param string $bigbluebuttonbnID
  * @param boolean $subset
  * @param boolean $include_deleted
  *
  * @return associative array containing the imported recordings indexed by recordID, each recording is also a non sequential associative array itself that corresponds to the actual recording in BBB
  */

function bigbluebuttonbn_getRecordingsImportedArray($courseID, $bigbluebuttonbnID=NULL, $subset=TRUE, $include_deleted=FALSE) {
    global $DB;

    if ($bigbluebuttonbnID == NULL) {
        $select = "courseid = '{$courseID}' AND log = '".BIGBLUEBUTTONBN_LOG_EVENT_IMPORT."'";
    } else if ($subset) {
        $select = "bigbluebuttonbnid = '{$bigbluebuttonbnID}' AND log = '".BIGBLUEBUTTONBN_LOG_EVENT_IMPORT."'";
    } else {
        $select = "courseid = '{$courseID}' AND bigbluebuttonbnid <> '{$bigbluebuttonbnID}' AND log = '".BIGBLUEBUTTONBN_LOG_EVENT_IMPORT."'";
    }
    $records_imported = $DB->get_records_select('bigbluebuttonbn_logs', $select);

    // Check if array is sequential already
    if (array() === $records_imported || array_keys($records_imported) != range(0, count($records_imported) - 1) ) {
        // if it's, lets assume that the array contains correct recording records as retrieved from the database
        $recordings_imported = $records_imported;
    } else {
        // if it's, lets assume that the parameter contains a single record and convert it to a sequential array format
        $recordings_imported = array($records_imported);
    }

    $recordings_imported_array = array();
    foreach ($recordings_imported as $key => $recording_imported) {
        $meta = json_decode($recording_imported->meta, true);
        $recordings_imported_array[$meta['recording']['recordID']] = $meta['recording'];
    }

    return $recordings_imported_array;
}


function bigbluebuttonbn_getDefaultConfigXML( $URL, $SALT) {
    $xml = bigbluebuttonbn_wrap_xml_load_file( bigbluebuttonbn_getDefaultConfigXMLURL($URL, $SALT) );
    return $xml;
}

function bigbluebuttonbn_getDefaultConfigXMLArray( $URL, $SALT) {
    $default_config_xml = bigbluebuttonbn_getDefaultConfigXML( $URL, $SALT );
    $default_config_xml_array = (array) $default_config_xml;
    return $default_config_xml_array;
}

function bigbluebuttonbn_getRecordingArrayValue($recording) {

    //Add formats
    $playbackArray = array();
    foreach ($recording->playback->format as $format) {
        $playbackArray[(string) $format->type] = array('type' => (string) $format->type, 'url' => (string) $format->url, 'length' => (string) $format->length);
        //Add preview per format when existing
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

    //Add the metadata to the recordings array
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

function bigbluebuttonbn_recordingBuildSorter($a, $b){
    if ($a['startTime'] < $b['startTime']) {
        return -1;
    } else if ($a['startTime'] == $b['startTime']) {
        return 0;
    } else {
        return 1;
    }
}

function bigbluebuttonbn_doDeleteRecordings( $recordIDs, $URL, $SALT) {
    $ids = explode(",", $recordIDs);
    foreach( $ids as $id){
        $xml = bigbluebuttonbn_wrap_xml_load_file( bigbluebuttonbn_getDeleteRecordingsURL($id, $URL, $SALT) );
        if ($xml && $xml->returncode != 'SUCCESS') {
            return false;
        }
    }
    return true;
}

function bigbluebuttonbn_doPublishRecordings( $recordIDs, $set, $URL, $SALT) {
    $ids = 	explode(",", $recordIDs);
    foreach( $ids as $id){
        $xml = bigbluebuttonbn_wrap_xml_load_file( bigbluebuttonbn_getPublishRecordingsURL($id, $set, $URL, $SALT) );
        if ($xml && $xml->returncode != 'SUCCESS') {
            return false;
        }
    }
    return true;
}

function bigbluebuttonbn_doEndMeeting( $meetingID, $modPW, $URL, $SALT) {
    $xml = bigbluebuttonbn_wrap_xml_load_file( bigbluebuttonbn_getEndMeetingURL( $meetingID, $modPW, $URL, $SALT ) );

    if ($xml) { //If the xml packet returned failure it displays the message to the user
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
    }
    else { //If the server is unreachable, then prompts the user of the necessary action
        return null;
    }
}

function bigbluebuttonbn_isMeetingRunning( $meetingID, $URL, $SALT) {
    $xml = bigbluebuttonbn_wrap_xml_load_file( bigbluebuttonbn_getIsMeetingRunningURL( $meetingID, $URL, $SALT ) );
    if ($xml && $xml->returncode == 'SUCCESS') {
        return ( ( $xml->running == 'true' ) ? true : false);
    } else {
        return ( false );
    }
}


function bigbluebuttonbn_getServerVersion( $URL) {
    $xml = bigbluebuttonbn_wrap_xml_load_file( $URL."api" );
    if ($xml && $xml->returncode == 'SUCCESS') {
        return $xml->version;
    } else {
        return NULL;
    }
}

function bigbluebuttonbn_getMeetingXML( $meetingID, $URL, $SALT) {
    $xml = bigbluebuttonbn_wrap_xml_load_file( bigbluebuttonbn_getIsMeetingRunningURL( $meetingID, $URL, $SALT ) );
    if ($xml && $xml->returncode == 'SUCCESS') {
        return ( str_replace('</response>', '', str_replace("<?xml version=\"1.0\"?>\n<response>", '', $xml->asXML())));
    } else {
        return 'false';
    }
}

function bigbluebuttonbn_wrap_xml_load_file($url, $method=BIGBLUEBUTTONBN_METHOD_GET, $data=NULL, $content_type='text/xml') {
    if (bigbluebuttonbn_debugdisplay()) {
        error_log("Request to: " . $url);
    }

    if (extension_loaded('curl')) {
        $c = new curl();
        $c->setopt( Array( "SSL_VERIFYPEER" => true));
        if ($method == BIGBLUEBUTTONBN_METHOD_POST) {
            if (!is_null($data)) {
                if (!is_array($data)) {
                    $options['CURLOPT_HTTPHEADER'] = array(
                            'Content-Type: ' . $content_type,
                            'Content-Length: ' . strlen($data),
                            'Content-Language: en-US'
                        );
                    $response = $c->post($url, $data, $options);

                } else {
                    $response = $c->post($url, $data);
                }

            } else {
                $response = $c->post($url);
            }

        } else {
            $response = $c->get($url);
        }

        if ($response) {
            if (bigbluebuttonbn_debugdisplay()) {
                error_log("Response: " . $response);
            }
            $previous = libxml_use_internal_errors(true);
            try {
                $xml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
                return $xml;
            } catch (Exception $e){
                libxml_use_internal_errors($previous);
                $error = 'Caught exception: ' . $e->getMessage();
                error_log($error);
                return NULL;
            }
        } else {
            error_log("No response on wrap_simplexml_load_file");
            return NULL;
        }

    } else {
        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_file($url, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
            if (bigbluebuttonbn_debugdisplay()) {
                error_log("Response processed: " . $response->asXML());
            }
            return $xml;
        } catch (Exception $e){
            $error = 'Caught exception: ' . $e->getMessage();
            error_log($error);
            libxml_use_internal_errors($previous);
            return NULL;
        }
    }
}

function bigbluebuttonbn_get_roles(context $context = null) {
    $roles = role_get_names($context);
    $roles_array = array();
    foreach($roles as $role) {
        $roles_array[$role->shortname] = $role->localname;
    }
    return $roles_array;
}

function bigbluebuttonbn_get_roles_select($roles = array()) {
    $roles_array = array();
    foreach($roles as $key => $value) {
        $roles_array[] = array("id" => $key, "name" => $value);
    }
    return $roles_array;
}

function bigbluebuttonbn_get_users(context $context = null) {
    $users = get_enrolled_users($context);
    return $users;
}

function bigbluebuttonbn_get_users_select($users) {
    $users_array = array();
    foreach($users as $user) {
        $users_array[] = array("id" => $user->id, "name" => fullname($user));
    }
    return $users_array;
}

function bigbluebuttonbn_get_participant_list($bigbluebuttonbn=NULL, $context=NULL){
    global $CFG, $USER;

    $participant_list_array = array();

    if ($bigbluebuttonbn != null) {
        $participant_list = json_decode($bigbluebuttonbn->participants);
        if (is_array($participant_list)) {
            foreach ($participant_list as $participant) {
                $participant_list_array[] = array("selectiontype" => $participant->selectiontype,
                                                  "selectionid" => $participant->selectionid,
                                                  "role" => $participant->role);
            }
        }
    } else {
        $participant_list_array[] = array("selectiontype" => "all",
                                          "selectionid" => "all",
                                          "role" => BIGBLUEBUTTONBN_ROLE_VIEWER);

        $moderator_defaults = bigbluebuttonbn_get_cfg_moderator_default();
        if (!isset($moderator_defaults)) {
            $moderator_defaults = array('owner');
        } else {
            $moderator_defaults = explode(',', $moderator_defaults);
        }
        foreach( $moderator_defaults as $moderator_default) {
            if ($moderator_default == 'owner') {
                $users = bigbluebuttonbn_get_users($context);
                foreach( $users as $user) {
                    if ($user->id == $USER->id) {
                        $participant_list_array[] = array("selectiontype" => "user",
                                                          "selectionid" => $USER->id,
                                                          "role" => BIGBLUEBUTTONBN_ROLE_MODERATOR);
                        break;
                    }
                }
            } else {
                $participant_list_array[] = array("selectiontype" => "role",
                                                  "selectionid" => $moderator_default,
                                                  "role" => BIGBLUEBUTTONBN_ROLE_MODERATOR);
            }
        }
    }

    return $participant_list_array;
}

function bigbluebuttonbn_get_participant_list_json($bigbluebuttonbnid=NULL){
    return json_encode(bigbluebuttonbn_get_participant_list($bigbluebuttonbnid));
}

function bigbluebuttonbn_is_moderator($user, $roles, $participants) {
    $participant_list = json_decode($participants);

    if (is_array($participant_list)) {
        // Iterate participant rules
        foreach($participant_list as $participant){
            if ($participant->role == BIGBLUEBUTTONBN_ROLE_MODERATOR) {
                // looks for all configuration
                if ($participant->selectiontype == 'all') {
                    return true;
                }
                // looks for users
                if ($participant->selectiontype == 'user' && $participant->selectionid == $user ) {
                    return true;
                }
                // looks for roles
                if ($participant->selectiontype == 'role') {
                    foreach( $roles as $role) {
                        if ($participant->selectionid == $role->shortname) {
                            return true;
                        }
                    }
                }
            }
        }
    }

    return false;
}

function bigbluebuttonbn_get_error_key($messageKey, $defaultKey = null) {
    $key = $defaultKey;
    if ($messageKey == "checksumError") {
        $key = 'index_error_checksum';
    } else if ($messageKey == 'maxConcurrent') {
        $key = 'view_error_max_concurrent';
    }
    return $key;
}

function bigbluebuttonbn_voicebridge_unique($voicebridge, $id=NULL) {
    global $DB;

    $is_unique = true;
    if ($voicebridge != 0) {
        $table = "bigbluebuttonbn";
        $select = "voicebridge = ".$voicebridge;
        if ($id) {
            $select .= " AND id <> ".$id;
        }
        if ($rooms = $DB->get_records_select($table, $select) ) {
            $is_unique = false;
        }
    }

    return $is_unique;
}

function bigbluebuttonbn_get_duration($openingtime, $closingtime) {
    global $CFG;

    $duration = 0;
    $now = time();
    if ($closingtime > 0 && $now < $closingtime) {
        $duration = ceil(($closingtime - $now)/60);
        $compensation_time = intval(bigbluebuttonbn_get_cfg_scheduled_duration_compensation());
        $duration = intval($duration) + $compensation_time;
    }

    return $duration;
}

function bigbluebuttonbn_get_presentation_array($context, $presentation, $id=NULL) {
    $presentation_name = null;
    $presentation_url = null;
    $presentation_icon = null;
    $presentation_mimetype_description = null;

    if (!empty($presentation)) {
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_bigbluebuttonbn', 'presentation', 0, 'itemid, filepath, filename', false);
        if (count($files) < 1) {
            //resource_print_filenotfound($resource, $cm, $course);
            //die;
            //exit;
        } else {
            $file = reset($files);
            unset($files);
            $presentation_name = $file->get_filename();
            $presentation_icon = file_file_icon($file, 24);
            $presentation_mimetype_description = get_mimetype_description($file);

            if (!is_null($id)) {
                //Create the nonce component for granting a temporary public access
                $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'presentation_cache');
                $presentation_nonce_key = sha1($id);
                $presentation_nonce_value = bigbluebuttonbn_generate_nonce();
                $cache->set($presentation_nonce_key, array( "value" => $presentation_nonce_value, "counter" => 0 ));

                //The item id was adapted for granting public access to the presentation once in order to allow BigBlueButton to gather the file
                $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $presentation_nonce_value, $file->get_filepath(), $file->get_filename());
            } else {
                $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), null, $file->get_filepath(), $file->get_filename());
            }
            $presentation_url = $url->out(false);
        }
    }

    $presentation_array = array( "url" => $presentation_url, "name" => $presentation_name, "icon" => $presentation_icon, "mimetype_description" => $presentation_mimetype_description);

    return $presentation_array;
}

function bigbluebuttonbn_generate_nonce() {

    $mt = microtime();
    $rand = mt_rand();

    return md5($mt.$rand);
}

function bigbluebuttonbn_random_password( $length = 8) {

    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
    $password = substr( str_shuffle( $chars ), 0, $length );

    return $password;
}

function bigbluebuttonbn_get_moodle_version_major() {
    global $CFG;

    $version_array = explode('.', $CFG->version);
    return $version_array[0];
}

function bigbluebuttonbn_event_log_standard($event_type, $bigbluebuttonbn, $context, $cm, $timecreated=NULL, $userid=NULL, $event_subtype=NULL) {
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
            $event_properties["userid"] = $userid;
            $event_properties["timecreated"] = $timecreated;
            $event_properties["other"] = $event_subtype;
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_meeting_event::create($event_properties);
            break;
    }

    $event->trigger();
}

function bigbluebuttonbn_event_log_legacy($event_type, $bigbluebuttonbn, $context, $cm) {
    global $DB;

    switch ($event_type) {
        case BIGBLUEBUTTON_EVENT_MEETING_JOINED:
            $event = 'join';
            break;
        case BIGBLUEBUTTON_EVENT_MEETING_CREATED:
            $event = 'create';
            break;
        case BIGBLUEBUTTON_EVENT_MEETING_ENDED:
            $event = 'end';
            break;
        case BIGBLUEBUTTON_EVENT_MEETING_LEFT:
            $event = 'left';
            break;
        case BIGBLUEBUTTON_EVENT_RECORDING_PUBLISHED:
            $event = 'publish';
            break;
        case BIGBLUEBUTTON_EVENT_RECORDING_UNPUBLISHED:
            $event = 'unpublish';
            break;
        case BIGBLUEBUTTON_EVENT_RECORDING_DELETED:
            $event = 'delete';
            break;
        case BIGBLUEBUTTON_EVENT_ACTIVITY_VIEWED:
            $event = 'view';
            break;
        case BIGBLUEBUTTON_EVENT_ACTIVITY_MANAGEMENT_VIEWED:
            $event = 'view all';
            break;
        default:
            return;
    }
    $course = $DB->get_record('course', array('id' => $bigbluebuttonbn->course), '*', MUST_EXIST);

    add_to_log($course->id, 'bigbluebuttonbn', $event, '', $bigbluebuttonbn->name, $cm->id);
}

function bigbluebuttonbn_event_log($event_type, $bigbluebuttonbn, $context, $cm) {
    global $CFG;

    $version_major = bigbluebuttonbn_get_moodle_version_major();
    if ($version_major < '2014051200') {
        //This is valid before v2.7
        bigbluebuttonbn_event_log_legacy($event_type, $bigbluebuttonbn, $context, $cm);

    } else {
        //This is valid only after v2.7
        bigbluebuttonbn_event_log_standard($event_type, $bigbluebuttonbn, $context, $cm);
    }
}

function bigbluebuttonbn_meeting_event_log($event, $bigbluebuttonbn, $context, $cm) {
    global $CFG;

    $version_major = bigbluebuttonbn_get_moodle_version_major();
    if ($version_major >= '2014051200') {
        //This is valid only after v2.7
        bigbluebuttonbn_event_log_standard(BIGBLUEBUTTON_EVENT_MEETING_EVENT, $bigbluebuttonbn, $context, $cm, $event->timestamp, $event->user, $event->event);
    }
}

function bigbluebuttonbn_bbb_broker_get_recordings($meetingid, $password, $forced=FALSE) {
    global $CFG;

    $recordings = array();
    $endpoint = bigbluebuttonbn_get_cfg_server_url();
    $shared_secret = bigbluebuttonbn_get_cfg_shared_secret();
    $cache_ttl = bigbluebuttonbn_get_cfg_waitformoderator_cache_ttl();

    $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'meetings_cache');
}

function bigbluebuttonbn_bbb_broker_participant_joined($meetingid, $is_moderator) {
    $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'meetings_cache');
    $result = $cache->get($meetingid);
    $meeting_info = json_decode($result['meeting_info']);
    $meeting_info->participantCount += 1;
    if ($is_moderator) {
        $meeting_info->moderatorCount += 1;
    }
    $cache->set($meetingid, array('creation_time' => $result['creation_time'], 'meeting_info' => json_encode($meeting_info) ));
}

function bigbluebuttonbn_bbb_broker_is_meeting_running($meeting_info) {
    $meeting_running = ( isset($meeting_info) && isset($meeting_info->returncode) && $meeting_info->returncode == 'SUCCESS' );

    return $meeting_running;
}

function bigbluebuttonbn_bbb_broker_get_meeting_info($meetingid, $password=NULL, $forced=FALSE) {
    global $CFG;

    $meeting_info = array();
    $endpoint = bigbluebuttonbn_get_cfg_server_url();
    $shared_secret = bigbluebuttonbn_get_cfg_shared_secret();
    $cache_ttl = bigbluebuttonbn_get_cfg_waitformoderator_cache_ttl();

    $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'meetings_cache');
    $result = $cache->get($meetingid);
    $now = time();
    if (isset($result) && $now < ($result['creation_time'] + $cache_ttl) && !$forced) {
        //Use the value in the cache
        $meeting_info = json_decode($result['meeting_info']);
    } else {
        if ($password == NULL) {
            if (isset($result)) {
                $moderatorPW = $result['meeting_info']['moderatorPW'];
            } else {
                $meeting = bigbluebuttonbn_getMeetingArray($meetingid, $endpoint, $shared_secret);
                if ($meeting) {
                    $moderatorPW = $meeting['moderatorPW'];
                }
            }
        } else {
            $moderatorPW = $password;
        }
        //Ping again and refresh the cache
        $meeting_info = (array) bigbluebuttonbn_getMeetingInfo( $meetingid, $moderatorPW, $endpoint, $shared_secret );
        $cache->set($meetingid, array('creation_time' => time(), 'meeting_info' => json_encode($meeting_info) ));
    }

    return $meeting_info;
}

function bigbluebuttonbn_bbb_broker_do_end_meeting($meetingid, $password){
    global $CFG;

    $endpoint = bigbluebuttonbn_get_cfg_server_url();
    $shared_secret = bigbluebuttonbn_get_cfg_shared_secret();

    bigbluebuttonbn_doEndMeeting($meetingid, $password, $endpoint, $shared_secret);
}

function bigbluebuttonbn_bbb_broker_do_publish_recording($recordingid, $publish=TRUE){
    global $CFG;

    $endpoint = bigbluebuttonbn_get_cfg_server_url();
    $shared_secret = bigbluebuttonbn_get_cfg_shared_secret();

    bigbluebuttonbn_doPublishRecordings($recordingid, ($publish)? 'true': 'false', $endpoint, $shared_secret);
}

function bigbluebuttonbn_bbb_broker_do_publish_recording_imported($recordingid, $courseID, $bigbluebuttonbnID, $publish=TRUE){
    global $DB;

    //Locate the record to be updated
    $records = $DB->get_records('bigbluebuttonbn_logs', array('bigbluebuttonbnid' => $bigbluebuttonbnID, 'log' => BIGBLUEBUTTONBN_LOG_EVENT_IMPORT));

    $recordings_imported = array();
    foreach ($records as $key => $record) {
        $meta = json_decode($record->meta, true);
        if ($recordingid == $meta['recording']['recordID']) {
            // Found, prepare data for the update
            $meta['recording']['published'] = ($publish)? 'true': 'false';
            $records[$key]->meta = json_encode($meta);

            // Proceed with the update
            $DB->update_record("bigbluebuttonbn_logs", $records[$key]);
        }
    }
}

function bigbluebuttonbn_bbb_broker_do_delete_recording($recordingid){
    global $CFG;

    $endpoint = bigbluebuttonbn_get_cfg_server_url();
    $shared_secret = bigbluebuttonbn_get_cfg_shared_secret();

    bigbluebuttonbn_doDeleteRecordings($recordingid, $endpoint, $shared_secret);
}

function bigbluebuttonbn_bbb_broker_do_delete_recording_imported($recordingid, $courseID, $bigbluebuttonbnID){
    global $DB;

    //Locate the record to be updated
    $records = $DB->get_records('bigbluebuttonbn_logs', array('bigbluebuttonbnid' => $bigbluebuttonbnID, 'log' => BIGBLUEBUTTONBN_LOG_EVENT_IMPORT));

    $recordings_imported = array();
    foreach ($records as $key => $record) {
        $meta = json_decode($record->meta, true);
        if ($recordingid == $meta['recording']['recordID']) {
            // Execute delete
            $DB->delete_records("bigbluebuttonbn_logs", array('id' => $key));
        }
    }
}

function bigbluebuttonbn_bbb_broker_validate_parameters($params) {
    $error = '';

    if (!isset($params['callback'])) {
        $error = bigbluebuttonbn_bbb_broker_add_error($error, 'This call must include a javascript callback.');
    }

    if (!isset($params['action'])) {
        $error = bigbluebuttonbn_bbb_broker_add_error($error, 'Action parameter must be included.');
    } else {
        switch ( strtolower($params['action'])) {
            case 'server_ping':
            case 'meeting_info':
            case 'meeting_end':
                if (!isset($params['id'])) {
                    $error = bigbluebuttonbn_bbb_broker_add_error($error, 'The meetingID must be specified.');
                }
                break;
            case 'recording_info':
            case 'recording_links':
            case 'recording_publish':
            case 'recording_unpublish':
            case 'recording_delete':
            case 'recording_import':
                if (!isset($params['id'])) {
                    $error = bigbluebuttonbn_bbb_broker_add_error($error, 'The recordingID must be specified.');
                }
                break;
            case 'recording_ready':
            case 'meeting_events':
                if (empty($params['signed_parameters'])) {
                    $error = bigbluebuttonbn_bbb_broker_add_error($error, 'A JWT encoded string must be included as [signed_parameters].');
                }
                break;
            case 'moodle_event':
                break;
            default:
                $error = bigbluebuttonbn_bbb_broker_add_error($error, 'Action '.$params['action'].' can not be performed.');
        }
    }

    return $error;
}

function bigbluebuttonbn_bbb_broker_add_error($org_msg, $new_msg='') {
    $error = $org_msg;

    if (!empty($new_msg)) {
        if (!empty($error) ) $error .= ' ';
        $error .= $new_msg;
    }

    return $error;
}

function bigbluebuttonbn_setConfigXMLParams( $meetingID, $configXML, $URL, $SALT) {
    $params = 'configXML='.urlencode($configXML).'&meetingID='.urlencode($meetingID);
    $config_xml_params = $params.'&checksum='.sha1("setConfigXML".$params.$SALT);
    return $config_xml_params;
}

function bigbluebuttonbn_setConfigXML( $meetingID, $configXML, $URL, $SALT) {
    $url_default_config = $URL."api/setConfigXML?";
    $config_xml_params = bigbluebuttonbn_setConfigXMLParams( $meetingID, $configXML, $URL, $SALT );
    $xml = bigbluebuttonbn_wrap_xml_load_file($url_default_config, BIGBLUEBUTTONBN_METHOD_POST, $config_xml_params, 'application/x-www-form-urlencoded');
    return $xml;
}

function bigbluebuttonbn_setConfigXMLArray( $meetingID, $configXML, $URL, $SALT) {
    $config_xml = bigbluebuttonbn_setConfigXML( $meetingID, $configXML, $URL, $SALT );
    $config_xml_array = (array) $config_xml;
    return $config_xml_array;
}

function bigbluebuttonbn_bbb_broker_set_config_xml($meetingID, $configXML) {
    $config_token = null;

    $endpoint = bigbluebuttonbn_get_cfg_server_url();
    $shared_secret = bigbluebuttonbn_get_cfg_shared_secret();

    $config_xml_array = bigbluebuttonbn_setConfigXMLArray($meetingID, $configXML, $endpoint, $shared_secret);
    if ($config_xml_array['returncode'] == 'SUCCESS') {
        error_log(json_encode($config_xml_array));
        $config_token = $config_xml_array['configToken'];
    } else {
        error_log("BigBlueButton was not able to set the custom config.xml file");
    }

    return $config_token;
}

function bigbluebuttonbn_get_recording_data_row($bbbsession, $recording, $tools=["publishing", "deleting"]) {
    global $OUTPUT, $CFG, $USER;

    $row = null;

    if ($bbbsession['managerecordings'] || $recording['published'] == 'true') {
        $startTime = isset($recording['startTime'])? floatval($recording['startTime']):0;
        $startTime = $startTime - ($startTime % 1000);
        $first_playback = array_values($recording['playbacks'])[0];
        $length = isset($first_playback['length'])? $first_playback['length']: 0;
        $duration = intval($length);

        //For backward compatibility
        if (isset($recording['meta_contextactivity'])) {
            $meta_activity = htmlentities($recording['meta_contextactivity']);
        } if (isset($recording['meta_bbb-recording-name'])) {
            $meta_activity = htmlentities($recording['meta_bbb-recording-name']);
        } else {
            $meta_activity = htmlentities($recording['meetingName']);
        }

        $meta_description = html_writer::start_tag('div', array('class' => 'col-md-20'));
        if (isset($recording['meta_contextactivitydescription']) && trim($recording['meta_contextactivitydescription']) != '') {
            $meta_description .= htmlentities($recording['meta_contextactivitydescription']);
        } else if (isset($recording['meta_bbb-recording-description']) && trim($recording['meta_bbb-recording-description']) != '') {
            $meta_description .= htmlentities($recording['meta_bbb-recording-description']);
        } else {
            $meta_description .= htmlentities('');
        }
        $meta_description .= html_writer::end_tag('div');

        //Set recording_types
        if (isset($recording['imported'])) {
            $attributes = 'data-imported="true" title='.get_string('view_recording_link_warning', 'bigbluebuttonbn');
        } else {
            $attributes = 'data-imported="false"';
        }

        $recording_types = '';
        if ($recording['published'] == 'true') {
            $recording_types .= '<div id="playbacks-'.$recording['recordID'].'" '.$attributes.'>';
        } else {
            $recording_types .= '<div id="playbacks-'.$recording['recordID'].'" '.$attributes.'" hidden>';
        }
        foreach ($recording['playbacks'] as $playback) {
            $recording_types .= $OUTPUT->action_link($playback['url'], get_string('view_recording_format_'.$playback['type'], 'bigbluebuttonbn'), null, array('title' => get_string('view_recording_format_'.$playback['type'], 'bigbluebuttonbn'), 'target' => '_new') ).'&#32;';
        }
        $recording_types .= '</div>';

        //Set recording_preview
        $recording_preview = '';
        foreach ($recording['playbacks'] as $playback) {
            if (isset($playback['preview'])) {
                foreach ($playback['preview'] as $image) {
                    $recording_preview .= html_writer::empty_tag('img', array('src' => $image['url'], 'class' => 'thumbnail'));
                }
                $recording_preview .= html_writer::empty_tag('br');
                $recording_preview .= html_writer::tag('div', get_string('view_recording_preview_help', 'bigbluebuttonbn'), array('class' => 'text-muted small'));
                break;
            }
        }

        //Initialize variables for styling text
        $head = $tail = '';

        //Set actionbar, if user is allowed to manage recordings
        $actionbar = '';
        if ($bbbsession['managerecordings']) {
            // Set style for imported links
            if (isset($recording['imported'])) {
                $tag_tail = ' '.get_string('view_recording_link', 'bigbluebuttonbn');
                $head = '<i>';
                $tail = '</i>';
            } else {
                $tag_tail = '';
            }

            $url = '#';
            $action = null;

            if (in_array("publishing", $tools)) {
                ///Set action [show|hide]
                if ($recording['published'] == 'true') {
                    $manage_tag = 'hide';
                    $manage_action = 'unpublish';
                } else {
                    $manage_tag = 'show';
                    $manage_action = 'publish';
                }
                $onclick = 'M.mod_bigbluebuttonbn.broker_manageRecording("'.$manage_action.'", "'.$recording['recordID'].'", "'.$recording['meetingID'].'");';

                if (bigbluebuttonbn_get_cfg_recording_icons_enabled()) {
                    //With icon for publish/unpublish
                    $icon_attributes = array('id' => 'recording-btn-'.$manage_action.'-'.$recording['recordID']);
                    $icon = new pix_icon('t/'.$manage_tag, get_string($manage_tag).$tag_tail, 'moodle', $icon_attributes);
                    $link_attributes = array('id' => 'recording-link-'.$manage_action.'-'.$recording['recordID'], 'onclick' => $onclick);
                    $actionbar .= $OUTPUT->action_icon($url, $icon, $action, $link_attributes, false);
                } else {
                    //With text for publish/unpublish
                    $link_attributes = array('title' => get_string($manage_tag).$tag_tail, 'class' => 'btn btn-xs', 'onclick' => $onclick);
                    $actionbar .= $OUTPUT->action_link($url, get_string($manage_tag).$tag_tail, $action, $link_attributes);
                    $actionbar .= "&nbsp;";
                }
            }

            if (in_array("deleting", $tools)) {
                $onclick = 'M.mod_bigbluebuttonbn.broker_manageRecording("delete", "'.$recording['recordID'].'", "'.$recording['meetingID'].'");';

                if (bigbluebuttonbn_get_cfg_recording_icons_enabled()) {
                    //With icon for delete
                    $icon_attributes = array('id' => 'recording-btn-delete-'.$recording['recordID']);
                    $icon = new pix_icon('t/delete', get_string('delete').$tag_tail, 'moodle', $icon_attributes);
                    $link_attributes = array('id' => 'recording-link-delete-'.$recording['recordID'], 'onclick' => $onclick);
                    $actionbar .= $OUTPUT->action_icon($url, $icon, $action, $link_attributes, false);
                } else {
                    //With text for delete
                    $link_attributes = array('title' => get_string('delete').$tag_tail, 'class' => 'btn btn-xs btn-danger', 'onclick' => $onclick);
                    $actionbar .= $OUTPUT->action_link($url, get_string('delete').$tag_tail, $action, $link_attributes);
                }
            }

            if (in_array("importing", $tools)) {
                $onclick = 'M.mod_bigbluebuttonbn.broker_manageRecording("import", "'.$recording['recordID'].'", "'.$recording['meetingID'].'");';

                if (bigbluebuttonbn_get_cfg_recording_icons_enabled()) {
                    //With icon for import
                    $icon_attributes = array('id' => 'recording-btn-import-'.$recording['recordID']);
                    $icon = new pix_icon('i/import', get_string('import'), 'moodle', $icon_attributes);
                    $link_attributes = array('id' => 'recording-link-import-'.$recording['recordID'], 'onclick' => $onclick);
                    $actionbar .= $OUTPUT->action_icon($url, $icon, $action, $link_attributes, false);
                } else {
                    //With text for import
                    $link_attributes = array('title' => get_string('import'), 'class' => 'btn btn-xs btn-danger', 'onclick' => $onclick);
                    $actionbar .= $OUTPUT->action_link($url, get_string('import'), $action, $link_attributes);
                }
            }
        }

        //Set corresponding format
        $dateformat = get_string('strftimerecentfull', 'langconfig').' %Z';
        $formattedStartDate = userdate($startTime / 1000, $dateformat, usertimezone($USER->timezone));

        $row = new stdClass();
        $row->recording = "{$head}{$recording_types}{$tail}";
        $row->activity = "{$head}{$meta_activity}{$tail}";
        $row->description = "{$head}{$meta_description}{$tail}";
        $row->preview = "{$head}{$recording_preview}{$tail}";
        $row->date = floatval($recording['startTime']);
        $row->date_formatted = "{$head}{$formattedStartDate}{$tail}";
        $row->duration = "{$duration}";
        $row->duration_formatted = "{$head}{$duration}{$tail}";
        if ($bbbsession['managerecordings']) {
            $row->actionbar = $actionbar;
        }
    }

    return $row;
}

function bigbluebuttonbn_get_recording_columns($bbbsession, $recordings) {
    ///Set strings to show
    $view_recording_recording = get_string('view_recording_recording', 'bigbluebuttonbn');
    $view_recording_activity = get_string('view_recording_activity', 'bigbluebuttonbn');
    $view_recording_description = get_string('view_recording_description', 'bigbluebuttonbn');
    $view_recording_preview = get_string('view_recording_preview', 'bigbluebuttonbn');
    $view_recording_date = get_string('view_recording_date', 'bigbluebuttonbn');
    $view_recording_duration = get_string('view_recording_duration', 'bigbluebuttonbn');
    $view_recording_actionbar = get_string('view_recording_actionbar', 'bigbluebuttonbn');

    ///Initialize table headers
    $recordingsbn_columns = array(
        array("key" => "recording", "label" => $view_recording_recording, "width" => "125px", "allowHTML" => true),
        array("key" => "activity", "label" => $view_recording_activity, "sortable" => true, "width" => "175px", "allowHTML" => true),
        array("key" => "description", "label" => $view_recording_description, "width" => "250px", "sortable" => true, "width" => "250px", "allowHTML" => true),
        array("key" => "preview", "label" => $view_recording_preview, "width" => "250px","allowHTML" => true),
        array("key" => "date", "label" => $view_recording_date, "sortable" => true, "width" => "225px", "allowHTML" => true),
        array("key" => "duration", "label" => $view_recording_duration, "width" => "50px")
        );

    if ($bbbsession['managerecordings']) {
        array_push($recordingsbn_columns, array("key" =>"actionbar", "label" => $view_recording_actionbar, "width" => "100px", "allowHTML" => true));
    }

    return $recordingsbn_columns;
}

function bigbluebuttonbn_get_recording_data($bbbsession, $recordings, $tools=["publishing", "deleting"]) {
    $table_data = array();

    ///Build table content
    if (isset($recordings) && !array_key_exists('messageKey', $recordings)) {  // There are recordings for this meeting
        foreach ( $recordings as $recording) {
            $row = bigbluebuttonbn_get_recording_data_row($bbbsession, $recording, $tools);
            if ($row != null) {
                array_push($table_data, $row);
            }
        }
    }

    return $table_data;
}

function bigbluebuttonbn_get_recording_table($bbbsession, $recordings, $tools=['publishing','deleting']) {
    global $OUTPUT, $CFG;

    ///Set strings to show
    $view_recording_recording = get_string('view_recording_recording', 'bigbluebuttonbn');
    $view_recording_course = get_string('view_recording_course', 'bigbluebuttonbn');
    $view_recording_activity = get_string('view_recording_activity', 'bigbluebuttonbn');
    $view_recording_description = get_string('view_recording_description', 'bigbluebuttonbn');
    $view_recording_date = get_string('view_recording_date', 'bigbluebuttonbn');
    $view_recording_length = get_string('view_recording_length', 'bigbluebuttonbn');
    $view_recording_duration = get_string('view_recording_duration', 'bigbluebuttonbn');
    $view_recording_actionbar = get_string('view_recording_actionbar', 'bigbluebuttonbn');
    $view_recording_playback = get_string('view_recording_playback', 'bigbluebuttonbn');
    $view_recording_preview = get_string('view_recording_preview', 'bigbluebuttonbn');
    $view_duration_min = get_string('view_recording_duration_min', 'bigbluebuttonbn');

    ///Declare the table
    $table = new html_table();
    $table->data = array();

    ///Initialize table headers
    if ($bbbsession['managerecordings']) {
        $table->head  = array ($view_recording_playback, $view_recording_recording, $view_recording_description, $view_recording_preview, $view_recording_date, $view_recording_duration, $view_recording_actionbar);
        $table->align = array ('left', 'left', 'left', 'left', 'left', 'center', 'left');
    } else {
        $table->head  = array ($view_recording_playback. $view_recording_recording, $view_recording_description, $view_recording_preview, $view_recording_date, $view_recording_duration);
        $table->align = array ('left', 'left', 'left', 'left', 'left', 'center');
    }

    ///Build table content
    if (isset($recordings) && !array_key_exists('messageKey', $recordings)) {  // There are recordings for this meeting
        foreach ( $recordings as $recording) {
            $row = new html_table_row();
            $row->id = 'recording-td-'.$recording['recordID'];
            if (isset($recording['imported'])) {
                $row->attributes['data-imported'] = 'true';
                $row->attributes['title'] = get_string('view_recording_link_warning', 'bigbluebuttonbn');
            } else {
                $row->attributes['data-imported'] = 'false';
            }

            $row_data = bigbluebuttonbn_get_recording_data_row($bbbsession, $recording, $tools);
            if ($row_data != null) {
                $row_data->date_formatted = str_replace(" ", "&nbsp;", $row_data->date_formatted);
                if ($bbbsession['managerecordings']) {
                    $row->cells = array ($row_data->recording, $row_data->activity, $row_data->description, $row_data->preview, $row_data->date_formatted, $row_data->duration_formatted, $row_data->actionbar );
                } else {
                    $row->cells = array ($row_data->recording, $row_data->activity, $row_data->description, $row_data->preview, $row_data->date_formatted, $row_data->duration_formatted );
                }

                array_push($table->data, $row);
            }
        }
    }

    return $table;
}

function bigbluebuttonbn_send_notification_recording_ready($bigbluebuttonbn) {
    $sender = get_admin();

    // Prepare message
    $msg = new stdClass();

    /// Build the message_body
    $msg->activity_type = "";
    $msg->activity_title = $bigbluebuttonbn->name;
    $message_text = '<p>'.get_string('email_body_recording_ready_for', 'bigbluebuttonbn').' '.$msg->activity_type.' &quot;'.$msg->activity_title.'&quot; '.get_string('email_body_recording_ready_is_ready', 'bigbluebuttonbn').'.</p>';

    bigbluebuttonbn_send_notification($sender, $bigbluebuttonbn, $message_text);
}

function bigbluebuttonbn_server_offers($capability_name){
    global $CFG;

    $capability_offered = null;

    $endpoint = bigbluebuttonbn_get_cfg_server_url();
    $shared_secret = bigbluebuttonbn_get_cfg_shared_secret();

    //Validates if the server may have extended capabilities
    $parse = parse_url($endpoint);
    $host = $parse['host'];
    $host_ends = explode(".", $host);
    $host_ends_length = count($host_ends);

    if ($host_ends_length > 0 && $host_ends[$host_ends_length -1] == 'com' &&  $host_ends[$host_ends_length -2] == 'blindsidenetworks') {
        //Validate the capabilities offered
        $capabilities = bigbluebuttonbn_getCapabilitiesArray( $endpoint, $shared_secret );
        if ($capabilities) {
            foreach ($capabilities as $capability) {
                if ($capability["name"] == $capability_name) {
                    $capability_offered = $capability;
                }
            }
        }
    }

    return $capability_offered;
}

function bigbluebuttonbn_server_offers_bn_capabilities(){
    //Validates if the server may have extended capabilities
    $parsed_url = parse_url(bigbluebuttonbn_get_cfg_server_url());
    $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
    $host_ends = explode(".", $host);
    $host_ends_length = count($host_ends);

    return ( $host_ends_length > 0 && $host_ends[$host_ends_length -1] == 'com' && $host_ends[$host_ends_length -2] == 'blindsidenetworks' );
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
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_server_url)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_server_url: (isset($CFG->bigbluebuttonbn_server_url)? $CFG->bigbluebuttonbn_server_url: (isset($CFG->BigBlueButtonBNServerURL)? $CFG->BigBlueButtonBNServerURL: BIGBLUEBUTTONBN_DEFAULT_SERVER_URL)));
}

function bigbluebuttonbn_get_cfg_shared_secret_default() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_shared_secret)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_shared_secret: (isset($CFG->bigbluebuttonbn_shared_secret)? $CFG->bigbluebuttonbn_shared_secret: (isset($CFG->BigBlueButtonBNSecuritySalt)? $CFG->BigBlueButtonBNSecuritySalt: BIGBLUEBUTTONBN_DEFAULT_SHARED_SECRET)));
}

function bigbluebuttonbn_get_cfg_voicebridge_editable() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_voicebridge_editable)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_voicebridge_editable: (isset($CFG->bigbluebuttonbn_voicebridge_editable)? $CFG->bigbluebuttonbn_voicebridge_editable: false));
}

function bigbluebuttonbn_get_cfg_recording_default() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recording_default)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recording_default: (isset($CFG->bigbluebuttonbn_recording_default)? $CFG->bigbluebuttonbn_recording_default: true));
}

function bigbluebuttonbn_get_cfg_recording_editable() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recording_editable)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recording_editable: (isset($CFG->bigbluebuttonbn_recording_editable)? $CFG->bigbluebuttonbn_recording_editable: true));
}

function bigbluebuttonbn_get_cfg_recording_tagging_default() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordingtagging_default)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordingtagging_default: (isset($CFG->bigbluebuttonbn_recordingtagging_default)? $CFG->bigbluebuttonbn_recordingtagging_default: false));
}

function bigbluebuttonbn_get_cfg_recording_tagging_editable() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordingtagging_editable)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordingtagging_editable: (isset($CFG->bigbluebuttonbn_recordingtagging_editable)? $CFG->bigbluebuttonbn_recordingtagging_editable: false));
}

function bigbluebuttonbn_get_cfg_recording_icons_enabled() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recording_icons_enabled)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recording_icons_enabled: (isset($CFG->bigbluebuttonbn_recording_icons_enabled)? $CFG->bigbluebuttonbn_recording_icons_enabled: true));
}

function bigbluebuttonbn_get_cfg_importrecordings_enabled() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_importrecordings_enabled)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_importrecordings_enabled: (isset($CFG->bigbluebuttonbn_importrecordings_enabled)? $CFG->bigbluebuttonbn_importrecordings_enabled: false));
}

function bigbluebuttonbn_get_cfg_importrecordings_from_deleted_activities_enabled() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_importrecordings_from_deleted_activities_enabled)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_importrecordings_from_deleted_activities_enabled: (isset($CFG->bigbluebuttonbn_importrecordings_from_deleted_activities_enabled)? $CFG->bigbluebuttonbn_importrecordings_from_deleted_activities_enabled: false));
}

function bigbluebuttonbn_get_cfg_waitformoderator_default() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_waitformoderator_default)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_waitformoderator_default: (isset($CFG->bigbluebuttonbn_waitformoderator_default)? $CFG->bigbluebuttonbn_waitformoderator_default: false));
}

function bigbluebuttonbn_get_cfg_waitformoderator_editable() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_waitformoderator_editable)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_waitformoderator_editable: (isset($CFG->bigbluebuttonbn_waitformoderator_editable)? $CFG->bigbluebuttonbn_waitformoderator_editable: true));
}

function bigbluebuttonbn_get_cfg_waitformoderator_ping_interval() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_waitformoderator_ping_interval)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_waitformoderator_ping_interval: (isset($CFG->bigbluebuttonbn_waitformoderator_ping_interval)? $CFG->bigbluebuttonbn_waitformoderator_ping_interval: 15));
}

function bigbluebuttonbn_get_cfg_waitformoderator_cache_ttl() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_waitformoderator_cache_ttl)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_waitformoderator_cache_ttl: (isset($CFG->bigbluebuttonbn_waitformoderator_cache_ttl)? $CFG->bigbluebuttonbn_waitformoderator_cache_ttl: 60));
}

function bigbluebuttonbn_get_cfg_userlimit_default() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_userlimit_default)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_userlimit_default: (isset($CFG->bigbluebuttonbn_userlimit_default)? $CFG->bigbluebuttonbn_userlimit_default: 0));
}

function bigbluebuttonbn_get_cfg_userlimit_editable() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_userlimit_editable)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_userlimit_editable: (isset($CFG->bigbluebuttonbn_userlimit_editable)? $CFG->bigbluebuttonbn_userlimit_editable: false));
}

function bigbluebuttonbn_get_cfg_preuploadpresentation_enabled() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    if (extension_loaded('curl')) {
        // This feature only works if curl is installed
        return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_preuploadpresentation_enabled)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_preuploadpresentation_enabled: (isset($CFG->bigbluebuttonbn_preuploadpresentation_enabled)? $CFG->bigbluebuttonbn_preuploadpresentation_enabled: false));
    } else {
        return false;
    }
}

function bigbluebuttonbn_get_cfg_sendnotifications_enabled() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_sendnotifications_enabled)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_sendnotifications_enabled: (isset($CFG->bigbluebuttonbn_sendnotifications_enabled)? $CFG->bigbluebuttonbn_sendnotifications_enabled: false));
}

function bigbluebuttonbn_get_cfg_recordingready_enabled() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordingready_enabled)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordingready_enabled: (isset($CFG->bigbluebuttonbn_recordingready_enabled)? $CFG->bigbluebuttonbn_recordingready_enabled: false));
}

function bigbluebuttonbn_get_cfg_meetingevents_enabled() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_meetingevents_enabled)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_meetingevents_enabled: (isset($CFG->bigbluebuttonbn_meetingevents_enabled)? $CFG->bigbluebuttonbn_meetingevents_enabled: false));
}

function bigbluebuttonbn_get_cfg_moderator_default() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_moderator_default)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_moderator_default: (isset($CFG->bigbluebuttonbn_moderator_default)? $CFG->bigbluebuttonbn_moderator_default: 'owner'));
}

function bigbluebuttonbn_get_cfg_scheduled_duration_enabled() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_scheduled_duration_enabled)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_scheduled_duration_enabled: (isset($CFG->bigbluebuttonbn_scheduled_duration_enabled)? $CFG->bigbluebuttonbn_scheduled_duration_enabled: false));
}

function bigbluebuttonbn_get_cfg_scheduled_duration_compensation() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_scheduled_duration_compensation)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_scheduled_duration_compensation: (isset($CFG->bigbluebuttonbn_scheduled_duration_compensation)? $CFG->bigbluebuttonbn_scheduled_duration_compensation: 10));
}

function bigbluebuttonbn_get_cfg_scheduled_pre_opening() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_scheduled_pre_opening)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_scheduled_pre_opening: (isset($CFG->bigbluebuttonbn_scheduled_pre_opening)? $CFG->bigbluebuttonbn_scheduled_pre_opening: 10));
}

function bigbluebuttonbn_get_cfg_recordings_html_default() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordings_html_default)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordings_html_default: (isset($CFG->bigbluebuttonbn_recordings_html_default)? $CFG->bigbluebuttonbn_recordings_html_default: false));
}

function bigbluebuttonbn_get_cfg_recordings_html_editable() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordings_html_editable)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordings_html_editable: (isset($CFG->bigbluebuttonbn_recordings_html_editable)? $CFG->bigbluebuttonbn_recordings_html_editable: false));
}

function bigbluebuttonbn_get_cfg_recordings_deleted_activities_default() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordings_deleted_activities_default)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordings_deleted_activities_default: (isset($CFG->bigbluebuttonbn_recordings_deleted_activities_default)? $CFG->bigbluebuttonbn_recordings_deleted_activities_default: false));
}

function bigbluebuttonbn_get_cfg_recordings_deleted_activities_editable() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordings_deleted_activities_editable)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordings_deleted_activities_editable: (isset($CFG->bigbluebuttonbn_recordings_deleted_activities_editable)? $CFG->bigbluebuttonbn_recordings_deleted_activities_editable: false));
}

function bigbluebuttonbn_import_get_courses_for_select(array $bbbsession) {

    if ($bbbsession['administrator']) {
        $courses = get_courses('all', 'c.id ASC', 'c.id,c.shortname,c.fullname');
        //It includes the name of the site as a course (category 0), so remove the first one
        unset($courses["1"]);
    } else {
        $courses = enrol_get_users_courses($bbbsession['userID'], false, 'id,shortname,fullname');
    }

    $courses_for_select = [];
    foreach($courses as $course) {
        $courses_for_select[$course->id] = $course->fullname;
    }
    return $courses_for_select;
}

function bigbluebuttonbn_getRecordedMeetingsDeleted($courseID, $bigbluebuttonbnID=NULL) {
    global $DB;

    $records_deleted = array();

    $filter = array('courseid' => $courseID, 'log' => BIGBLUEBUTTONBN_LOG_EVENT_DELETE );
    if ($bigbluebuttonbnID != NULL) {
        $filter['id'] = $bigbluebuttonbnID;
    }

    $bigbluebuttonbns_deleted = $DB->get_records('bigbluebuttonbn_logs', $filter);

    foreach ($bigbluebuttonbns_deleted as $key => $bigbluebuttonbn_deleted) {
        $records = $DB->get_records('bigbluebuttonbn_logs', array('courseid' => $courseID, 'log' => BIGBLUEBUTTONBN_LOG_EVENT_CREATE));

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
                    } else if ($bigbluebuttonbn_deleted->meetingid != substr($record->meetingid, 0, strlen($bigbluebuttonbn_deleted->meetingid))) {
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

function bigbluebuttonbn_getRecordedMeetings($courseID, $bigbluebuttonbnID=NULL) {
    global $DB;

    $records = array();

    $filter = array('course' => $courseID);
    if ($bigbluebuttonbnID != NULL) {
        $filter['id'] = $bigbluebuttonbnID;
    }
    $bigbluebuttonbns = $DB->get_records('bigbluebuttonbn', $filter);

    if (!empty($bigbluebuttonbns)) {
        $table = 'bigbluebuttonbn_logs';

        //Prepare select for loading records based on existent bigbluebuttonbns
        $select = "";
        foreach ($bigbluebuttonbns as $key => $bigbluebuttonbn) {
            $select .= strlen($select) == 0? "(": " OR ";
            $select .= "bigbluebuttonbnid=".$bigbluebuttonbn->id;
        }
        $select .= ") AND log='".BIGBLUEBUTTONBN_LOG_EVENT_CREATE."'";

        //Execute select for loading records based on existent bigbluebuttonbns
        $records = $DB->get_records_select($table, $select);

        //Remove duplicates
        $unique_records = array();
        foreach ($records as $key => $record) {
            $record_key = $record->meetingid.','.$record->bigbluebuttonbnid.','.$record->meta;
            if (array_search($record_key, $unique_records) === FALSE) {
                array_push($unique_records, $record_key);
            } else {
                unset($records[$key]);
            }
        }

        //Remove the ones with record=FALSE
        foreach ($records as $key => $record) {
            $meta = json_decode($record->meta);
            if (!$meta || !$meta->record) {
                unset($records[$key]);
            }
        }
    }

    return $records;
}

function bigbluebutton_output_recording_table($bbbsession, $recordings, $tools=['publishing','deleting']) {

    if (isset($recordings) && !empty($recordings)) {  // There are recordings for this meeting
        $table = bigbluebuttonbn_get_recording_table($bbbsession, $recordings, $tools);
    }

    $output = '';
    if (isset($table->data)) {
        //Render the table
        $output .= html_writer::div(html_writer::table($table), '', array('id' => 'bigbluebuttonbn_html_table'));
    } else {
        $output .= html_writer::div(get_string('view_message_norecordings', 'bigbluebuttonbn'), '', array('id' => 'bigbluebuttonbn_html_table'));
    }

    return $output;
}

function bigbluebuttonbn_debugdisplay() {
    global $CFG;

    return (bool)$CFG->debugdisplay;
}

function bigbluebuttonbn_html2text($html, $len) {
    $text = strip_tags($html);
    $text = str_replace("&nbsp;", ' ', $text);
    if (strlen($text) > $len) {
        $text = substr($text, 0, $len)."...";
    } else {
        $text = substr($text, 0, $len);
    }
    return $text;
}

function bigbluebuttonbn_get_tags($id) {
    $tags = "";
    $tags_array = core_tag_tag::get_item_tags_array('core', 'course_modules', $id);
    foreach ( $tags_array as $key => $value) {
        $tags .= ($tags == "")? $value: ",".$value;
    }
    return $tags;
}

function bigbluebuttonbn_get_recordings($courseID, $bigbluebuttonbnID=NULL, $subset=TRUE, $include_deleted=FALSE) {
    global $DB;

    // Gather the bigbluebuttonbnids whose meetingids should be included in the getRecordings request
    if ($bigbluebuttonbnID == NULL) {
        $select = "course = '{$courseID}'";
    } else if ($subset) {
        $select = "id = '{$bigbluebuttonbnID}'";
    } else {
        $select = "id <> '{$bigbluebuttonbnID}' AND course = '{$courseID}'";
    }
    $bigbluebuttonbns = $DB->get_records_select_menu('bigbluebuttonbn', $select, null, 'id', 'id, meetingid');

    // Consider logs from deleted bigbluebuttonbn instances whose meetingids should be included in the getRecordings request
    if ($include_deleted) {
        if ($bigbluebuttonbnID == NULL) {
            $select = "courseid = '{$courseID}' AND log = '".BIGBLUEBUTTONBN_LOG_EVENT_DELETE."' AND meta like '%has_recordings%' AND meta like '%true%'";
        } else if ($subset) {
            $select = "bigbluebuttonbnid = '{$bigbluebuttonbnID}' AND log = '".BIGBLUEBUTTONBN_LOG_EVENT_DELETE."' AND meta like '%has_recordings%' AND meta like '%true%'";
        } else {
            $select = "courseid = '{$courseID}' AND bigbluebuttonbnid <> '{$bigbluebuttonbnID}' AND log = '".BIGBLUEBUTTONBN_LOG_EVENT_DELETE."' AND meta like '%has_recordings%' AND meta like '%true%'";
        }
        $bigbluebuttonbns_deleted = $DB->get_records_select_menu('bigbluebuttonbn_logs', $select, null, 'bigbluebuttonbnid', 'bigbluebuttonbnid, meetingid');
        if (!empty($bigbluebuttonbns_deleted)) {
            // Merge bigbluebuttonbnis from deleted instances, only keys are relevant. Artimetic merge is used in order to keep the keys
            $bigbluebuttonbns += $bigbluebuttonbns_deleted;
        } else {
            // There is nothing to merge. Do nothing
        }
    } else {
        // Deleted should not be included. Do nothing
    }

    // Gather the meetingids from bigbluebuttonbn logs that include a create with record=true
    if (!empty($bigbluebuttonbns)) {
        //Prepare select for loading records based on existent bigbluebuttonbns
        $sql  = "SELECT DISTINCT meetingid, bigbluebuttonbnid FROM {bigbluebuttonbn_logs} WHERE ";
        $sql .= "(bigbluebuttonbnid=".implode(' OR bigbluebuttonbnid=', array_keys($bigbluebuttonbns)).")";
        //Include only Create events and exclude those with record not true
        $sql .= " AND log = ? AND meta LIKE ? AND meta LIKE ?";
        //Execute select for loading records based on existent bigbluebuttonbns
        $records = $DB->get_records_sql_menu($sql, array(BIGBLUEBUTTONBN_LOG_EVENT_CREATE, "%record%", "%true%"));
        // Get actual recordings
        $recordings = bigbluebuttonbn_getRecordingsArray(array_keys($records));
    } else {
        $recordings = array();
    }

    // Get recording links
    $recordings_imported = bigbluebuttonbn_getRecordingsImportedArray($courseID, $bigbluebuttonbnID, $subset, $include_deleted);

    // Perform aritmetic add instead of merge so the imported recordings corresponding to existent recordings are not included
    return ($recordings + $recordings_imported);
}

function bigbluebuttonbn_unset_existent_recordings_already_imported($recordings, $courseID, $bigbluebuttonbnID, $include_deleted=FALSE) {
    $recordings_imported = bigbluebuttonbn_getRecordingsImportedArray($courseID, $bigbluebuttonbnID, TRUE, $include_deleted);

    foreach ($recordings as $key => $recording) {
        if (isset($recordings_imported[$recording['recordID']])) {
            unset($recordings[$key]);
        }
    }
    return $recordings;
}

function bigbluebuttonbn_get_count_recording_imported_instances($recordID) {
    global $DB;

    $sql  = "SELECT * FROM {bigbluebuttonbn_logs} WHERE log = ? AND meta LIKE ? AND meta LIKE ?";
    $count_recordings_imported = $DB->count_records_sql($sql, array(BIGBLUEBUTTONBN_LOG_EVENT_IMPORT, "%recordID%", "%{$recordID}%"));

    return $count_recordings_imported;
}

function bigbluebuttonbn_get_recording_imported_instances($recordID) {
    global $DB;

    $sql  = "SELECT * FROM {bigbluebuttonbn_logs} WHERE log = ? AND meta LIKE ? AND meta LIKE ?";
    $recordings_imported = $DB->get_records_sql($sql, array(BIGBLUEBUTTONBN_LOG_EVENT_IMPORT, "%recordID%", "%{$recordID}%"));

    return $recordings_imported;
}

function bigbluebuttonbn_get_instance_type_profiles() {

    $instanceprofiles = array(
            array('id' => BIGBLUEBUTTONBN_TYPE_ALL, 'name' => get_string('instance_type_default', 'bigbluebuttonbn'), 'features' => array('all')),
            array('id' => BIGBLUEBUTTONBN_TYPE_ROOM_ONLY, 'name' => get_string('instance_type_room_only', 'bigbluebuttonbn'), 'features' => array('showroom', 'welcomemessage', 'voicebridge', 'waitformoderator', 'userlimit', 'recording', 'recordingtagging', 'sendnotifications', 'preuploadpresentation', 'permissions', 'schedule', 'groups')),
            array('id' => BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY, 'name' => get_string('instance_type_recording_only', 'bigbluebuttonbn'), 'features' => array('showrecordings', 'importrecordings'))
    );

    return $instanceprofiles;
}

function bigbluebuttonbn_get_instance_types_array($_instanceprofiles=NULL) {
    $instanceprofiles = is_null($_instanceprofiles) || empty($_instanceprofiles) ? bigbluebuttonbn_get_instanceprofiles() : $_instanceprofiles;

    $instanceprofiles_display_array = array();

    foreach($instanceprofiles as $instanceprofile) {
        $instanceprofiles_display_array += array("{$instanceprofile['id']}" => $instanceprofile['name']);
    }

    return $instanceprofiles_display_array;
}
