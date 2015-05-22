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

require_once(dirname(__FILE__).'/lib.php');

const BIGBLUEBUTTONBN_ROLE_VIEWER = 'viewer';
const BIGBLUEBUTTONBN_ROLE_MODERATOR = 'moderator';
const BIGBLUEBUTTONBN_METHOD_GET = 'GET';
const BIGBLUEBUTTONBN_METHOD_POST = 'POST';

const BIGBLUEBUTTON_EVENT_MEETING_CREATED = 'meeting_created';
const BIGBLUEBUTTON_EVENT_MEETING_JOINED = 'meeting_joined';
const BIGBLUEBUTTON_EVENT_MEETING_ENDED = 'meeting_ended';
const BIGBLUEBUTTON_EVENT_ACTIVITY_VIEWED = 'activity_viewed';
const BIGBLUEBUTTON_EVENT_RECORDING_PUBLISHED = 'recording_published';
const BIGBLUEBUTTON_EVENT_RECORDING_UNPUBLISHED = 'recording_unpublished';
const BIGBLUEBUTTON_EVENT_RECORDING_DELETED = 'recording_deleted';
const BIGBLUEBUTTON_EVENT_MEETING_LEFT = "meeting_left";

const BIGBLUEBUTTONBN_LOG_EVENT_CREATE = "Create";
const BIGBLUEBUTTONBN_LOG_EVENT_JOIN = "Join";
const BIGBLUEBUTTONBN_LOG_EVENT_LOGOUT = "Logout";

function bigbluebuttonbn_log(array $bbbsession, $event) {
    global $DB;

    $log = new stdClass();
    
    $log->meetingid = $bbbsession['meetingid'];
    $log->courseid = $bbbsession['courseid']; 
    $log->bigbluebuttonbnid = $bbbsession['bigbluebuttonbnid'];
    $log->userid = $bbbsession['userID'];
    $log->timecreated = time();
    $log->event = $event;
    if( $event == BIGBLUEBUTTONBN_LOG_EVENT_CREATE)
        $log->meta = '{"record":'.($bbbsession['record']? 'true': 'false').'}';

    $returnid = $DB->insert_record('bigbluebuttonbn_log', $log);
}

 ////////////////////////////
//  BigBlueButton API Calls  //
 ////////////////////////////
function bigbluebuttonbn_getJoinURL( $meetingID, $userName, $PW, $SALT, $URL ) {
    $url_join = $URL."api/join?";
    $params = 'meetingID='.urlencode($meetingID).'&fullName='.urlencode($userName).'&password='.urlencode($PW);
    return ($url_join.$params.'&checksum='.sha1("join".$params.$SALT) );
}

function bigbluebuttonbn_getCreateMeetingURL($name, $meetingID, $attendeePW, $moderatorPW, $welcome, $logoutURL, $SALT, $URL, $record = 'false', $duration=0, $voiceBridge=0, $metadata = array() ) {
    $url_create = $URL."api/create?";

    $params = 'name='.urlencode($name).'&meetingID='.urlencode($meetingID).'&attendeePW='.urlencode($attendeePW).'&moderatorPW='.urlencode($moderatorPW).'&logoutURL='.urlencode($logoutURL).'&record='.$record;

    $voiceBridge = intval($voiceBridge);
    if ( $voiceBridge > 0 && $voiceBridge < 79999)
        $params .= '&voiceBridge='.$voiceBridge;

    $duration = intval($duration);
    if( $duration > 0 )
        $params .= '&duration='.$duration;

    if( trim( $welcome ) )
        $params .= '&welcome='.urlencode($welcome);

    foreach ($metadata as $key => $value) {
        $params .= '&'.$key.'='.urlencode($value);
    }

    return ( $url_create.$params.'&checksum='.sha1("create".$params.$SALT) );
}

function bigbluebuttonbn_getIsMeetingRunningURL( $meetingID, $URL, $SALT ) {
    $base_url = $URL."api/isMeetingRunning?";
    $params = 'meetingID='.urlencode($meetingID);
    return ($base_url.$params.'&checksum='.sha1("isMeetingRunning".$params.$SALT) );
}

function bigbluebuttonbn_getMeetingInfoURL( $meetingID, $modPW, $URL, $SALT ) {
    $base_url = $URL."api/getMeetingInfo?";
    $params = 'meetingID='.urlencode($meetingID).'&password='.urlencode($modPW);
    return ( $base_url.$params.'&checksum='.sha1("getMeetingInfo".$params.$SALT));
}

function bigbluebuttonbn_getMeetingsURL($URL, $SALT) {
    $base_url = $URL."api/getMeetings?";
    $params = '';
    return ( $base_url.$params.'&checksum='.sha1("getMeetings".$params.$SALT));
}

function bigbluebuttonbn_getEndMeetingURL( $meetingID, $modPW, $URL, $SALT ) {
    $base_url = $URL."api/end?";
    $params = 'meetingID='.urlencode($meetingID).'&password='.urlencode($modPW);
    return ( $base_url.$params.'&checksum='.sha1("end".$params.$SALT) );
}

function bigbluebuttonbn_getRecordingsURL($meetingID, $URL, $SALT ) {
    $base_url_record = $URL."api/getRecordings?";
    $params = "meetingID=".urlencode($meetingID);

    return ($base_url_record.$params."&checksum=".sha1("getRecordings".$params.$SALT) );
}

function bigbluebuttonbn_getDeleteRecordingsURL( $recordID, $URL, $SALT ) {
    $url_delete = $URL."api/deleteRecordings?";
    $params = 'recordID='.urlencode($recordID);
    return ($url_delete.$params.'&checksum='.sha1("deleteRecordings".$params.$SALT) );
}

function bigbluebuttonbn_getPublishRecordingsURL( $recordID, $set, $URL, $SALT ) {
    $url_delete = $URL."api/publishRecordings?";
    $params = 'recordID='.$recordID."&publish=".$set;
    return ($url_delete.$params.'&checksum='.sha1("publishRecordings".$params.$SALT) );
}

function bigbluebuttonbn_getCapabilitiesURL($URL, $SALT) {
    $base_url = $URL."api/getCapabilities?";
    $params = '';
    return ( $base_url.$params.'&checksum='.sha1("getCapabilities".$params.$SALT));
}

function bigbluebuttonbn_getCreateMeetingArray( $username, $meetingID, $welcomeString, $mPW, $aPW, $SALT, $URL, $logoutURL, $record='false', $duration=0, $voiceBridge=0, $metadata=array(), $presentation_name=null, $presentation_url=null ) {

    if( !is_null($presentation_name) && !is_null($presentation_url) ) {
        $xml = bigbluebuttonbn_wrap_xml_load_file( bigbluebuttonbn_getCreateMeetingURL($username, $meetingID, $aPW, $mPW, $welcomeString, $logoutURL, $SALT, $URL, $record, $duration, $voiceBridge, $metadata),
                BIGBLUEBUTTONBN_METHOD_POST,
                "<?xml version='1.0' encoding='UTF-8'?><modules><module name='presentation'><document url='".$presentation_url."' /></module></modules>"
                );
    } else {
        $xml = bigbluebuttonbn_wrap_simplexml_load_file( bigbluebuttonbn_getCreateMeetingURL($username, $meetingID, $aPW, $mPW, $welcomeString, $logoutURL, $SALT, $URL, $record, $duration, $voiceBridge, $metadata) );
    }

    if( $xml ) {
        if($xml->meetingID) return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey, 'meetingID' => $xml->meetingID, 'attendeePW' => $xml->attendeePW, 'moderatorPW' => $xml->moderatorPW, 'hasBeenForciblyEnded' => $xml->hasBeenForciblyEnded );
        else return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey );
    }
    else {
        return null;
    }
}

function bigbluebuttonbn_getMeetingsArray( $URL, $SALT ) {
    $xml = bigbluebuttonbn_wrap_simplexml_load_file( bigbluebuttonbn_getMeetingsURL( $URL, $SALT ) );

    if( $xml && $xml->returncode == 'SUCCESS' && $xml->messageKey ) {    //The meetings were returned
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);

    } else if($xml && $xml->returncode == 'SUCCESS'){                    //If there were meetings already created
        foreach ($xml->meetings->meeting as $meeting) {
            $meetings[] = array( 'meetingID' => $meeting->meetingID, 'moderatorPW' => $meeting->moderatorPW, 'attendeePW' => $meeting->attendeePW, 'hasBeenForciblyEnded' => $meeting->hasBeenForciblyEnded, 'running' => $meeting->running );
        }
        return $meetings;

    } else if( $xml ) { //If the xml packet returned failure it displays the message to the user
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);

    } else { //If the server is unreachable, then prompts the user of the necessary action
        return null;
    }
}

function bigbluebuttonbn_getMeetingInfo( $meetingID, $modPW, $URL, $SALT ) {
    $xml = bigbluebuttonbn_wrap_simplexml_load_file( bigbluebuttonbn_getMeetingInfoURL( $meetingID, $modPW, $URL, $SALT ) );
    return $xml;
}

function bigbluebuttonbn_getMeetingInfoArray( $meetingID, $modPW, $URL, $SALT ) {
    $xml = bigbluebuttonbn_wrap_simplexml_load_file( bigbluebuttonbn_getMeetingInfoURL( $meetingID, $modPW, $URL, $SALT ) );

    if( $xml && $xml->returncode == 'SUCCESS' && $xml->messageKey == null){//The meeting info was returned
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey );

    } else if($xml && $xml->returncode == 'SUCCESS'){ //If there were meetings already created
        return array( 'meetingID' => $xml->meetingID, 'moderatorPW' => $xml->moderatorPW, 'attendeePW' => $xml->attendeePW, 'hasBeenForciblyEnded' => $xml->hasBeenForciblyEnded, 'running' => $xml->running, 'recording' => $xml->recording, 'startTime' => $xml->startTime, 'endTime' => $xml->endTime, 'participantCount' => $xml->participantCount, 'moderatorCount' => $xml->moderatorCount, 'attendees' => $xml->attendees, 'metadata' => $xml->metadata );

    } else if( ($xml && $xml->returncode == 'FAILED') || $xml) { //If the xml packet returned failure it displays the message to the user
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);

    } else { //If the server is unreachable, then prompts the user of the necessary action
        return null;
    }
}

function bigbluebuttonbn_getRecordingsArray($meetingID, $URL, $SALT ) {
    $xml = bigbluebuttonbn_wrap_simplexml_load_file( bigbluebuttonbn_getRecordingsURL( $meetingID, $URL, $SALT ) );
    if( $xml && $xml->returncode == 'SUCCESS' && $xml->messageKey ) {//The meetings were returned
        return array('returncode' => (string) $xml->returncode, 'message' => (string) $xml->message, 'messageKey' => (string) $xml->messageKey);
    } else if($xml && $xml->returncode == 'SUCCESS'){ //If there were meetings already created
        $recordings = array();

        foreach ($xml->recordings->recording as $recording) {
            $playbackArray = array();
            foreach ( $recording->playback->format as $format ){
                $playbackArray[(string) $format->type] = array( 'type' => (string) $format->type, 'url' => (string) $format->url );
            }

            //Add the metadata to the recordings array
            $metadataArray = array();
            $metadata = get_object_vars($recording->metadata);
            foreach ($metadata as $key => $value) {
                if(is_object($value)) $value = '';
                $metadataArray['meta_'.$key] = $value;
            }
            
            $recordings[] = array( 'recordID' => (string) $recording->recordID, 'meetingID' => (string) $recording->meetingID, 'meetingName' => (string) $recording->name, 'published' => (string) $recording->published, 'startTime' => (string) $recording->startTime, 'endTime' => (string) $recording->endTime, 'playbacks' => $playbackArray ) + $metadataArray;
            
        }

        usort($recordings, 'bigbluebuttonbn_recordingBuildSorter');

        return $recordings;

    } else if( $xml ) { //If the xml packet returned failure it displays the message to the user
        return array('returncode' => (string) $xml->returncode, 'message' => (string) $xml->message, 'messageKey' => (string) $xml->messageKey);
    } else { //If the server is unreachable, then prompts the user of the necessary action
        return NULL;
    }
}

function bigbluebuttonbn_getCapabilitiesArray($URL, $SALT) {
    //$xml = bigbluebuttonbn_wrap_simplexml_load_file( bigbluebuttonbn_getCapabilitiesURL( $URL, $SALT ) );
    //// Mocking the getCapabilities
    $response = "<response><returncode>SUCCESS</returncode><capabilities><capability><name>extendedUI</name><endpoint>http://konekti.blindsidenetworks.com/api/v1/extended-ui</endpoint></capability></capabilities></response>";
    $xml = new SimpleXMLElement($response, LIBXML_NOCDATA);
    ////

    if( $xml && $xml->returncode == 'SUCCESS' && $xml->messageKey ) {    //The capabilities were returned
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);

    } else if($xml && $xml->returncode == 'SUCCESS'){ //If there were meetings already created
        foreach ($xml->capabilities->capability as $capability) {
            $capabilities[] = array( 'name' => (string)$capability->name, 'endpoint' => (string)$capability->endpoint );
        }
        return $capabilities;

    } else if( $xml ) { //If the xml packet returned failure it displays the message to the user
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);

    } else { //If the server is unreachable, then prompts the user of the necessary action
        return null;
    }
}

function bigbluebuttonbn_recordingBuildSorter($a, $b){
    if( $a['startTime'] < $b['startTime']) return -1;
    else if( $a['startTime'] == $b['startTime']) return 0;
    else return 1;
}

function bigbluebuttonbn_doDeleteRecordings( $recordIDs, $URL, $SALT ) {
    $ids = 	explode(",", $recordIDs);
    foreach( $ids as $id){
        $xml = bigbluebuttonbn_wrap_simplexml_load_file( bigbluebuttonbn_getDeleteRecordingsURL($id, $URL, $SALT) );
        if( $xml && $xml->returncode != 'SUCCESS' )
            return false;
    }
    return true;
}

function bigbluebuttonbn_doPublishRecordings( $recordIDs, $set, $URL, $SALT ) {
    $ids = 	explode(",", $recordIDs);
    foreach( $ids as $id){
        $xml = bigbluebuttonbn_wrap_simplexml_load_file( bigbluebuttonbn_getPublishRecordingsURL($id, $set, $URL, $SALT) );
        if( $xml && $xml->returncode != 'SUCCESS' )
            return false;
    }
    return true;
}

function bigbluebuttonbn_doEndMeeting( $meetingID, $modPW, $URL, $SALT ) {
    $xml = bigbluebuttonbn_wrap_simplexml_load_file( bigbluebuttonbn_getEndMeetingURL( $meetingID, $modPW, $URL, $SALT ) );

    if( $xml ) { //If the xml packet returned failure it displays the message to the user
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
    }
    else { //If the server is unreachable, then prompts the user of the necessary action
        return null;
    }

}

function bigbluebuttonbn_isMeetingRunning( $meetingID, $URL, $SALT ) {
    $xml = bigbluebuttonbn_wrap_simplexml_load_file( bigbluebuttonbn_getIsMeetingRunningURL( $meetingID, $URL, $SALT ) );
    
    if( $xml && $xml->returncode == 'SUCCESS' )
        return ( ( $xml->running == 'true' ) ? true : false);
    else
        return ( false );
}


function bigbluebuttonbn_getServerVersion( $URL ){
    $base_url_record = $URL."api";

    $xml = bigbluebuttonbn_wrap_simplexml_load_file( $base_url_record );
    if( $xml && $xml->returncode == 'SUCCESS' )
        return $xml->version;
    else
        return NULL;

}

function bigbluebuttonbn_getMeetingXML( $meetingID, $URL, $SALT ) {
    $xml = bigbluebuttonbn_wrap_simplexml_load_file( bigbluebuttonbn_getIsMeetingRunningURL( $meetingID, $URL, $SALT ) );
    if( $xml && $xml->returncode == 'SUCCESS')
        return ( str_replace('</response>', '', str_replace("<?xml version=\"1.0\"?>\n<response>", '', $xml->asXML())));
    else
        return 'false';
}

function bigbluebuttonbn_wrap_simplexml_load_file($url) {
    error_log($url);
    if (extension_loaded('curl')) {
        $c = new curl();
        $c->setopt( Array( "SSL_VERIFYPEER" => true));
        $response = $c->get($url);

        if($response) {
            $previous = libxml_use_internal_errors(true);
            try {
                $xml = new SimpleXMLElement($response, LIBXML_NOCDATA);
                return $xml;
            } catch (Exception $e){
                libxml_use_internal_errors($previous);
                error_log("The XML response is not correct on wrap_simplexml_load_file: ".$e->getMessage());
                return NULL;
            }
        } else {
            error_log("No response on wrap_simplexml_load_file");
            return NULL;
        }
    } else {
        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_file($url,'SimpleXMLElement', LIBXML_NOCDATA);
            return $xml;
        } catch  (Exception $e){
            libxml_use_internal_errors($previous);
            return NULL;
        }
    }
}

function bigbluebuttonbn_wrap_xml_load_file($url, $method=BIGBLUEBUTTONBN_METHOD_GET, $data_string=null){
    if (extension_loaded('curl')) {
        $c = new curl();
        $c->setopt( Array( "SSL_VERIFYPEER" => true));
        if( $method == BIGBLUEBUTTONBN_METHOD_POST ) {
            $options = array();
            if( !is_null($data_string) ) {
                $options['CURLOPT_HTTPHEADER'] = array(
                            'Content-Type: text/xml',
                            'Content-Length: '.strlen($data_string),
                            'Content-Language: en-US'
                        );
                //$options['CURLOPT_POSTFIELDS'] = $data_string;
                $response = $c->post($url, $data_string, $options);
            } else {
                $response = $c->post($url);
            }
        } else {
            $response = $c->get($url);
        }

        if($response) {
            $previous = libxml_use_internal_errors(true);
            try {
                $xml = new SimpleXMLElement($response, LIBXML_NOCDATA);
                return $xml;
            } catch (Exception $e){
                libxml_use_internal_errors($previous);
                error_log("The XML response is not correct on wrap_simplexml_load_file: ".$e->getMessage());
                return NULL;
            }
        } else {
            error_log("No response on wrap_simplexml_load_file");
            return NULL;
        }
    } else {
        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_file($url,'SimpleXMLElement', LIBXML_NOCDATA);
            return $xml;
        } catch  (Exception $e){
            libxml_use_internal_errors($previous);
            return NULL;
        }
    }
}

function bigbluebuttonbn_get_role_name($role_shortname){
    $role = bigbluebuttonbn_get_db_moodle_roles($role_shortname);
    if( $role != null && $role->name != "") {
        $role_name = $role->name;
    } else {
        switch ($role_shortname) {
            case 'manager':         $role_name = get_string('manager', 'role'); break;
            case 'coursecreator':   $role_name = get_string('coursecreators'); break;
            case 'editingteacher':  $role_name = get_string('defaultcourseteacher'); break;
            case 'teacher':         $role_name = get_string('noneditingteacher'); break;
            case 'student':         $role_name = get_string('defaultcoursestudent'); break;
            case 'guest':           $role_name = get_string('guest'); break;
            case 'user':            $role_name = get_string('authenticateduser'); break;
            case 'frontpage':       $role_name = get_string('frontpageuser', 'role'); break;
            // We should not get here, the role UI should require the name for custom roles!
            default:                $role_name = $role_shortname; break;
        }
    }

    return $role_name;
}

function bigbluebuttonbn_get_roles($rolename='all', $format='json'){
    $roles = bigbluebuttonbn_get_db_moodle_roles($rolename);
    $roles_array = array();
    foreach($roles as $role){
        if( $format=='json' ) {
            array_push($roles_array,
                    array( "id" => $role->shortname,
                        "name" => bigbluebuttonbn_get_role_name($role->shortname)
                    )
            );
        } else {
            $roles_array[$role->shortname] = bigbluebuttonbn_get_role_name($role->shortname);
        }
    }
    return $roles_array;
}

function bigbluebuttonbn_get_roles_json($rolename='all'){
    return json_encode(bigbluebuttonbn_get_roles($rolename));
}

function bigbluebuttonbn_get_users_json($users, $full=false) {
    if( $full ) {
        return json_encode($users);
    } else {
        $users_array = array();
        foreach($users as $user){
            array_push($users_array,
                    array( "id" => $user->id,
                            "name" => $user->firstname.' '.$user->lastname
                    )
            );
        }
        return json_encode($users_array);
    }
}

function bigbluebuttonbn_get_participant_list($bigbluebuttonbn=null, $context=null){
    global $CFG, $USER;

    $participant_list_array = array();

    if( $bigbluebuttonbn != null ) {
        $participant_list = json_decode($bigbluebuttonbn->participants);
        if (is_array($participant_list)) {
            foreach($participant_list as $participant){
                array_push($participant_list_array,
                        array(
                            "selectiontype" => $participant->selectiontype,
                            "selectionid" => $participant->selectionid,
                            "role" => $participant->role
                        )
                );
            }
        }
    } else {
        array_push($participant_list_array,
                array(
                    "selectiontype" => "all",
                    "selectionid" => "all",
                    "role" => BIGBLUEBUTTONBN_ROLE_VIEWER
                )
        );
        
        $moderator_defaults = $CFG->bigbluebuttonbn_moderator_default;
        if ( !isset($moderator_defaults) ) {
            $moderator_defaults = array('owner');
        } else {
            $moderator_defaults = explode(',', $moderator_defaults);
        }
        foreach( $moderator_defaults as $moderator_default ) {
            if( $moderator_default == 'owner' ) {
                $users = bigbluebuttonbn_get_users($context);
                foreach( $users as $user ){
                    if( $user->id == $USER->id ){
                        array_push($participant_list_array,
                                array(
                                        "selectiontype" => "user",
                                        "selectionid" => $USER->id,
                                        "role" => BIGBLUEBUTTONBN_ROLE_MODERATOR
                                )
                        );
                        break;
                    }
                }
            } else {
                array_push($participant_list_array,
                        array(
                                "selectiontype" => "role",
                                "selectionid" => $moderator_default,
                                "role" => BIGBLUEBUTTONBN_ROLE_MODERATOR
                        )
                );
            }
        }
    }

    return $participant_list_array;
}

function bigbluebuttonbn_get_participant_list_json($bigbluebuttonbnid=null){
    return json_encode(bigbluebuttonbn_get_participant_list($bigbluebuttonbnid));
}

function bigbluebuttonbn_is_moderator($user, $roles, $participants) {
    $participant_list = json_decode($participants);

    if (is_array($participant_list)) {
        // Iterate looking for all configuration
        foreach($participant_list as $participant){
            if( $participant->selectiontype == 'all' ) {
                if ( $participant->role == BIGBLUEBUTTONBN_ROLE_MODERATOR )
                    return true;
            }
        }

        //Iterate looking for roles
        $db_moodle_roles = bigbluebuttonbn_get_db_moodle_roles();
        foreach($participant_list as $participant){
            if( $participant->selectiontype == 'role' ) {
                foreach( $roles as $role ) {
                    $db_moodle_role = bigbluebuttonbn_moodle_db_role_lookup($db_moodle_roles, $role->roleid);
                    if( $participant->selectionid == $db_moodle_role->shortname ) {
                        if ( $participant->role == BIGBLUEBUTTONBN_ROLE_MODERATOR )
                            return true;
                    }
                }
            }
        }

        //Iterate looking for users
        foreach($participant_list as $participant){
            if( $participant->selectiontype == 'user' ) {
                if( $participant->selectionid == $user ) {
                    if ( $participant->role == BIGBLUEBUTTONBN_ROLE_MODERATOR )
                        return true;
                }
            }
        }
    }

    return false;
}

function bigbluebuttonbn_moodle_db_role_lookup($db_moodle_roles, $role_id) {
    foreach( $db_moodle_roles as $db_moodle_role ){
        if( $role_id ==  $db_moodle_role->id ) {
            return $db_moodle_role;
        }
    }
}

function bigbluebuttonbn_get_error_key($messageKey, $defaultKey = null) {
    $key = $defaultKey;
    if ( $messageKey == "checksumError" ){
        $key = 'index_error_checksum';
    } else if ( $messageKey == 'maxConcurrent' ) {
        $key = 'view_error_max_concurrent';
    }
    return $key;
}

function bigbluebuttonbn_voicebridge_unique($voicebridge, $id=null) {
    global $DB;
    $is_unique = true;
    $table = "bigbluebuttonbn";
    //$select = "voicebridge = ".$voicebridge." AND (closingtime = 0 OR closingtime > ".time().")";
    $select = "voicebridge = ".$voicebridge;
    if( $id ) $select .= " AND id <> ".$id; 
    if ( $rooms = $DB->get_records_select($table, $select)  ) {
        $is_unique = false;
    }

    return $is_unique;
}

function bigbluebuttonbn_get_duration($openingtime, $closingtime) {
    global $CFG;

    $duration = 0;
    $now = time();
    if( $closingtime > 0 && $now < $closingtime ) {
        $duration = ceil(($closingtime - $now)/60);
        $compensation_time = intval($CFG->bigbluebuttonbn_scheduled_duration_compensation);
        $duration = intval($duration) + $compensation_time;
    }

    return $duration;
}

function bigbluebuttonbn_get_presentation_array($context, $presentation, $id=null) {
    $presentation_name = null;
    $presentation_url = null;
    $presentation_icon = null;
    $presentation_mimetype_description = null;

    if( !empty($presentation) ) {
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

            if( !is_null($id) ) {
                //Create the nonce component for granting a temporary public access
                $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'presentation_cache');
                $presentation_nonce_key = sha1($id);
                $presentation_nonce_value = bigbluebuttonbn_generate_nonce(); 
                $cache->set($presentation_nonce_key, array( "value" => $presentation_nonce_value, "counter" => 0 ));

                //The item id was adapted for granting public access to the presentation once in order to allow BigBlueButton to gather the file
                //$url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
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

function bigbluebuttonbn_random_password( $length = 8 ) {

    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
    $password = substr( str_shuffle( $chars ), 0, $length );

    return $password;
}

function bigbluebuttonbn_event_log($event_type, $bigbluebuttonbn, $context, $cm) {
    global $CFG;

    if ( $CFG->version < '2014051200' ) {
        //This is valid before v2.7
        add_to_log($course->id, 'bigbluebuttonbn', $event_type, '', $bigbluebuttonbn->name, $cm->id);
    } else {
        //This is valid after v2.7
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
        }

        $event->trigger();
    }
}

function bigbluebuttonbn_bbb_broker_get_recordings($meetingid, $password, $forced=false) {
    global $CFG;

    $recordings = array();
    $endpoint = trim(trim($CFG->bigbluebuttonbn_server_url),'/').'/';
    $shared_secret = trim($CFG->bigbluebuttonbn_shared_secret);
    $cache_ttl = $CFG->bigbluebuttonbn_waitformoderator_cache_ttl;
    
    $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'meetings_cache');
}

function bigbluebuttonbn_bbb_broker_is_meeting_running($meeting_info) {
    //$meeting_running = ( isset($meeting_info) && isset($meeting_info['returncode']) && $meeting_info['returncode'] == 'SUCCESS' && $meeting_info['running'] == 'true' );
    $meeting_running = ( isset($meeting_info) && isset($meeting_info->returncode) && $meeting_info->returncode == 'SUCCESS' );
    return $meeting_running;
}

function bigbluebuttonbn_bbb_broker_get_meeting_info($meetingid, $password, $forced=false) {
    global $CFG;

    $meeting_info = array();
    $endpoint = trim(trim($CFG->bigbluebuttonbn_server_url),'/').'/';
    $shared_secret = trim($CFG->bigbluebuttonbn_shared_secret);
    $cache_ttl = $CFG->bigbluebuttonbn_waitformoderator_cache_ttl;

    $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'meetings_cache');
    $result = $cache->get($meetingid);
    $now = time();
    if( isset($result) && $now < ($result['creation_time'] + $cache_ttl) && !$forced ) {
        //Use the value in the cache
        $meeting_info = json_decode($result['meeting_info']);
        //error_log("ENCODED 1");
        //error_log(json_encode($meeting_info));
    } else {
        //Ping again and refresh the cache
        error_log("Ping again and refresh the cache");
        $meeting_info = (array) bigbluebuttonbn_getMeetingInfo( $meetingid, $password, $endpoint, $shared_secret );
        $cache->set($meetingid, array('creation_time' => time(), 'meeting_info' => json_encode($meeting_info) ));
        //error_log("NATURAL 0");
        //error_log(json_encode($meeting_info));
        //$result = $cache->get($meetingid);
        //error_log("ENCODED 0");
        //$meeting_info = json_decode($result['meeting_info']);
        //error_log(json_encode($meeting_info));
    }

    return $meeting_info;
}

function bigbluebuttonbn_bbb_broker_do_end_meeting($meetingid, $password){
    global $CFG;

    $endpoint = trim(trim($CFG->bigbluebuttonbn_server_url),'/').'/';
    $shared_secret = trim($CFG->bigbluebuttonbn_shared_secret);

    bigbluebuttonbn_doEndMeeting($meetingid, $password, $endpoint, $shared_secret);
}

function bigbluebuttonbn_bbb_broker_do_publish_recording($recordingid, $publish=true){
    global $CFG;

    $endpoint = trim(trim($CFG->bigbluebuttonbn_server_url),'/').'/';
    $shared_secret = trim($CFG->bigbluebuttonbn_shared_secret);

    bigbluebuttonbn_doPublishRecordings($recordingid, ($publish)? 'true': 'false', $endpoint, $shared_secret);
}

function bigbluebuttonbn_bbb_broker_do_delete_recording($recordingid){
    global $CFG;

    $endpoint = trim(trim($CFG->bigbluebuttonbn_server_url),'/').'/';
    $shared_secret = trim($CFG->bigbluebuttonbn_shared_secret);

    bigbluebuttonbn_doDeleteRecordings($recordingid, $endpoint, $shared_secret);
}

function bigbluebuttonbn_bbb_broker_validate_parameters($params) {
    $error = '';

    if ( !isset($params['callback']) ) {
        $error = $bigbluebuttonbn_bbb_broker_add_error($error, 'This call must include a javascript callback.');
    }

    if ( !isset($params['action']) ) {
        $error = $bigbluebuttonbn_bbb_broker_add_error($error, 'Action parameter must be included.');
    } else {
        switch ( strtolower($params['action']) ){
            case 'server_ping':
            case 'meeting_info':
            case 'meeting_end':
                if ( !isset($params['id']) ) {
                    $error = $bigbluebuttonbn_bbb_broker_add_error($error, 'The meetingID must be specified.');
                }
                break;
            case 'recording_list':
            case 'recording_publish':
            case 'recording_unpublish':
            case 'recording_delete':
                if ( !isset($params['id']) ) {
                    $error = bigbluebuttonbn_bbb_broker_add_error($error, 'The recordingID must be specified.');
                }
                break;
            case 'recording_ready':
                if( empty($params['signed_parameters']) ) {
                    $error = bigbluebuttonbn_bbb_broker_add_error($error, 'A JWT encoded string must be included as [signed_parameters].');
                }
                break;
            default:
                $error = bigbluebuttonbn_bbb_broker_add_error($error, 'Action '.$params['action'].' can not be performed.');
        }
    }

    return $error;
}

function bigbluebuttonbn_bbb_broker_add_error($org_msg, $new_msg='') {
    $error = $org_msg;

    if( !empty($new_msg) ) {
        error_log($new_msg);
        if( !empty($error) ) $error .= ' ';
        $error .= $new_msg;
    }

    return $error;
}

function bigbluebuttonbn_get_predefinedprofile_name($profile_id) {
    if( $profile_id == 0 )
        return get_string('predefined_profile_default', 'bigbluebuttonbn');
    else if( $profile_id == 1 )
        return get_string('predefined_profile_classroom', 'bigbluebuttonbn');
    else if( $profile_id == 2 )
        return get_string('predefined_profile_conferenceroom', 'bigbluebuttonbn');
    else if( $profile_id == 3 )
        return get_string('predefined_profile_collaborationroom', 'bigbluebuttonbn');
    else if( $profile_id == 4 )
        return get_string('predefined_profile_scheduledsession', 'bigbluebuttonbn');
}

function bigbluebuttonbn_get_predefinedprofiles() {
    $predefinedprofiles = array(
            array('id' => 0, 'name' => get_string('predefined_profile_default', 'bigbluebuttonbn'), 'features' => array( 'wait' => true, 'record' => true, 'presentation' => true, 'permission' => true, 'schedule' => true, 'groups' => true) ),
            array('id' => 1, 'name' => get_string('predefined_profile_classroom', 'bigbluebuttonbn'), 'features' => array( 'wait' => true, 'record' => true, 'presentation' => true, 'permission' => true, 'schedule' => false, 'groups' => false) ),
            array('id' => 2, 'name' => get_string('predefined_profile_conferenceroom', 'bigbluebuttonbn'), 'features' => array( 'wait' => true, 'record' => true, 'presentation' => true, 'permission' => true, 'schedule' => false, 'groups' => false) ),
            array('id' => 3, 'name' => get_string('predefined_profile_collaborationroom', 'bigbluebuttonbn'), 'features' => array( 'wait' => true, 'record' => true, 'presentation' => true, 'permission' => true, 'schedule' => false, 'groups' => true) ),
            array('id' => 4, 'name' => get_string('predefined_profile_scheduledsession', 'bigbluebuttonbn'), 'features' => array( 'wait' => true, 'record' => true, 'presentation' => true, 'permission' => true, 'schedule' => true, 'groups' => false) )
    );

    return $predefinedprofiles;
}

function bigbluebuttonbn_get_predefinedprofile_display_array() {
    $predefinedprofiles = bigbluebuttonbn_get_predefinedprofiles();

    $predefinedprofile_display_array = array();
    foreach($predefinedprofiles as $predefinedprofile) {
        $profile = array($predefinedprofile['id'] => $predefinedprofile['name']);
        $predefinedprofile_display_array = array_merge($predefinedprofile_display_array, $profile);
    }

    return $predefinedprofile_display_array;
}

function bigbluebuttonbn_get_recording_table($bbbsession) {
    global $OUTPUT, $CFG;

    ///Set strings to show
    $view_head_recording = get_string('view_head_recording', 'bigbluebuttonbn');
    $view_head_course = get_string('view_head_course', 'bigbluebuttonbn');
    $view_head_activity = get_string('view_head_activity', 'bigbluebuttonbn');
    $view_head_description = get_string('view_head_description', 'bigbluebuttonbn');
    $view_head_date = get_string('view_head_date', 'bigbluebuttonbn');
    $view_head_length = get_string('view_head_length', 'bigbluebuttonbn');
    $view_head_duration = get_string('view_head_duration', 'bigbluebuttonbn');
    $view_head_actionbar = get_string('view_head_actionbar', 'bigbluebuttonbn');
    $view_duration_min = get_string('view_duration_min', 'bigbluebuttonbn');

    ///Declare the table
    $table = new html_table();
    $table->data = array();

    ///Initialize table headers
    if ( $bbbsession['administrator'] || $bbbsession['moderator'] ) {
        $table->head  = array ($view_head_recording, $view_head_activity, $view_head_description, $view_head_date, $view_head_duration, $view_head_actionbar);
        $table->align = array ('left', 'left', 'left', 'left', 'center', 'left');
    } else {
        $table->head  = array ($view_head_recording, $view_head_activity, $view_head_description, $view_head_date, $view_head_duration);
        $table->align = array ('left', 'left', 'left', 'left', 'center');
    }

    ///Build table content
    $recordings = bigbluebuttonbn_getRecordingsArray($bbbsession['meetingid'], $bbbsession['endpoint'], $bbbsession['shared_secret']);

    if ( isset($recordings) && !array_key_exists('messageKey', $recordings)) {  // There are no recordings for this meeting
        foreach ( $recordings as $recording ){
            if ( $bbbsession['administrator'] || $bbbsession['moderator'] || $recording['published'] == 'true' ) {
                $length = 0;
                $endTime = isset($recording['endTime'])? floatval($recording['endTime']):0;
                $endTime = $endTime - ($endTime % 1000);
                $startTime = isset($recording['startTime'])? floatval($recording['startTime']):0;
                $startTime = $startTime - ($startTime % 1000);
                $duration = intval(($endTime - $startTime) / 60000);

                //$meta_course = isset($recording['meta_context'])?str_replace('"', '\"', $recording['meta_context']):'';
                $meta_activity = isset($recording['meta_contextactivity'])?str_replace('"', '\"', $recording['meta_contextactivity']):'';
                $meta_description = isset($recording['meta_contextactivitydescription'])?str_replace('"', '\"', $recording['meta_contextactivitydescription']):'';

                $actionbar = '';
                $params['id'] = $bbbsession['cm']->id;
                $params['recordingid'] = $recording['recordID'];
                if ( $bbbsession['administrator'] || $bbbsession['moderator'] ) {
                    $url = '#';
                    $action = null;

                    ///Set action [show|hide]
                    if ( $recording['published'] == 'true' ){
                        $manage_tag = 'hide';
                        $manage_action = 'unpublish';
                    } else {
                        $manage_tag = 'show';
                        $manage_action = 'publish';
                    }

                    if ($CFG->bigbluebuttonbn_recording_icons_enabled) {
                        //With icon for publish/unpublish
                        $icon_attributes = array('id' => 'recording-btn-'.$manage_action.'-'.$recording['recordID']);
                        $icon = new pix_icon('t/'.$manage_tag, get_string($manage_tag), 'moodle', $icon_attributes);
                        $link_attributes = array('id' => 'recording-link-'.$manage_action.'-'.$recording['recordID'], 'onclick' => 'M.mod_bigbluebuttonbn.broker_manageRecording("'.$manage_action.'", "'.$recording['recordID'].'");');
                        $actionbar .= $OUTPUT->action_icon($url, $icon, $action, $link_attributes, false);

                        //With icon for delete
                        $icon_attributes = array('id' => 'recording-btn-delete-'.$recording['recordID']);
                        $icon = new pix_icon('t/delete', get_string('delete'), 'moodle', $icon_attributes);
                        $link_attributes = array('id' => 'recording-link-delete-'.$recording['recordID'], 'onclick' => 'if(confirm("'.get_string('view_delete_confirmation', 'bigbluebuttonbn').'?")) M.mod_bigbluebuttonbn.broker_manageRecording("delete", "'.$recording['recordID'].'");');
                        $actionbar .= $OUTPUT->action_icon($url, $icon, $action, $link_attributes, false);

                    } else {
                        //With text for publish/unpublish
                        $actionbar .= $OUTPUT->action_link($url, get_string($manage_tag), $action, array('title' => get_string($manage_tag), 'onclick' => 'M.mod_bigbluebuttonbn.broker_manageRecording("'.$manage_action.'", "'.$recording['recordID'].'");') );
                        $actionbar .= "&nbsp;";

                        //With text for delete
                        $actionbar .= $OUTPUT->action_link($url, get_string('delete'), $action, array('title' => get_string('delete'), 'onclick' => 'if(confirm("Are you sure to delete?")) M.mod_bigbluebuttonbn.broker_manageRecording("delete", "'.$recording['recordID'].'");') );
                    }
                }

                $type = '';
                foreach ( $recording['playbacks'] as $playback ){
                    if ($recording['published'] == 'true'){
                        $type .= $OUTPUT->action_link($playback['url'], $playback['type'], null, array('title' => $playback['type'], 'target' => '_new') ).'&#32;';
                    } else {
                        $type .= $playback['type'].'&#32;';
                    }
                }

                //Make sure the startTime is timestamp
                if( !is_numeric($recording['startTime']) ){
                    $date = new DateTime($recording['startTime']);
                    $recording['startTime'] = date_timestamp_get($date);
                } else {
                    $recording['startTime'] = $recording['startTime'] / 1000;
                }
                //Set corresponding format
                $format = get_string('strftimerecentfull', 'langconfig');
                if( isset($format) ) {
                    $formatedStartDate = userdate($recording['startTime'], $format);
                } else {
                    $format = '%a %h %d, %Y %H:%M:%S %Z';
                    $formatedStartDate = userdate($recording['startTime'], $format, usertimezone($USER->timezone) );
                }

                $row = new html_table_row();
                $row->id = 'recording-td-'.$recording['recordID'];
                if ( $bbbsession['administrator'] || $bbbsession['moderator'] ) {
                    $row->cells = array ($type, $meta_activity, $meta_description, str_replace(" ", "&nbsp;", $formatedStartDate), $duration, $actionbar );
                    //$table->data[] = array ($type, $meta_activity, $meta_description, str_replace(" ", "&nbsp;", $formatedStartDate), $duration, $actionbar );
                } else {
                    $row->cells = array ($type, $meta_activity, $meta_description, str_replace(" ", "&nbsp;", $formatedStartDate), $duration);
                    //$table->data[] = array ($type, $meta_activity, $meta_description, str_replace(" ", "&nbsp;", $formatedStartDate), $duration);
                }
                array_push($table->data, $row);
            }
        }
    }

    return $table;
}

function bigbluebuttonbn_send_notification_recording_ready($meeting_id) {
    global $DB;

    $meeting_id_elements = explode("[", $meeting_id);
    $meeting_id_elements = explode("-", $meeting_id_elements[0]);
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $meeting_id_elements[2]), '*', MUST_EXIST);
    $sender = get_admin();

    // Prepare message
    $msg = new stdClass();

    /// Build the message_body
    $msg->activity_type = "";
    if( $bigbluebuttonbn->type != 0 )
        $msg->activity_type = bigbluebuttonbn_get_predefinedprofile_name($bigbluebuttonbn->type);
    $msg->activity_title = $bigbluebuttonbn->name;
    $message_text = get_string('email_body_recording_ready', 'bigbluebuttonbn', $msg);
    
    bigbluebuttonbn_send_notification($sender, $bigbluebuttonbn, $message_text);
}

function bigbluebuttonbn_server_offers($capability_name){
    global $CFG;

    $capability_offered = null;

    $endpoint = trim(trim($CFG->bigbluebuttonbn_server_url),'/').'/';
    $shared_secret = trim($CFG->bigbluebuttonbn_shared_secret);

    //Validates if the server may have extended capabilities
    $parse = parse_url($endpoint);
    $host = $parse['host'];
    $host_ends = explode(".", $host);
    $host_ends_length = count($host_ends);

    if( $host_ends_length > 0 && $host_ends[$host_ends_length -1] == 'com' &&  $host_ends[$host_ends_length -2] == 'blindsidenetworks' ) {
        //Validate the capabilities offered
        $capabilities = bigbluebuttonbn_getCapabilitiesArray( $endpoint, $shared_secret );
        if( !$capabilities ) {
            error_log("Server does not offer extended capabilities");

        } else {
            error_log(json_encode($capabilities));
            foreach ($capabilities as $capability) {
                if( $capability["name"] == $capability_name)
                    $capability_offered = $capability;
            }
        }
    }

    return $capability_offered;
}