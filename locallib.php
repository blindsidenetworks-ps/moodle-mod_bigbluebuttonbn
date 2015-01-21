<?php
/**
 * Internal library of functions for module BigBlueButtonBN.
 * 
 * @package   mod
 * @subpackage bigbluebuttonbn
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2010-2014 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/lib.php');
//require_once($CFG->libdir.'/accesslib.php');
require_once($CFG->dirroot.'/lib/accesslib.php');
require_once($CFG->dirroot.'/lib/filelib.php');
require_once($CFG->dirroot.'/lib/formslib.php');

const BIGBLUEBUTTONBN_ROLE_VIEWER = 'viewer';
const BIGBLUEBUTTONBN_ROLE_MODERATOR = 'moderator';

function bigbluebuttonbn_rand_string() {
    return md5(uniqid(rand(), true));
}

function bigbluebuttonbn_log(array $bbbsession, $event) {
    global $DB;

    $log = new stdClass();
    
    $log->meetingid = $bbbsession['meetingid'];
    $log->courseid = $bbbsession['courseid']; 
    $log->bigbluebuttonbnid = $bbbsession['bigbluebuttonbnid'];
    $log->record = $bbbsession['textflag']['record'] == 'true'? 1: 0;
    $log->timecreated = time();
    $log->event = $event;
    
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
    if ( $voiceBridge == 0)
        $voiceBridge = 70000 + rand(0, 9999);

    $meta = '';
    foreach ($metadata as $key => $value) {
        $meta = $meta.'&'.$key.'='.urlencode($value);
    }
    
    $params = 'name='.urlencode($name).'&meetingID='.urlencode($meetingID).'&attendeePW='.urlencode($attendeePW).'&moderatorPW='.urlencode($moderatorPW).'&voiceBridge='.$voiceBridge.'&logoutURL='.urlencode($logoutURL).'&record='.$record.$meta;

    $duration = intval($duration);
    if( $duration > 0 )
        $params .= '&duration='.$duration;

    if( trim( $welcome ) )
        $params .= '&welcome='.urlencode($welcome);

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


function bigbluebuttonbn_getCreateMeetingArray( $username, $meetingID, $welcomeString, $mPW, $aPW, $SALT, $URL, $logoutURL, $record='false', $duration=0, $voiceBridge=0, $metadata = array() ) {

    $xml = bigbluebuttonbn_wrap_simplexml_load_file( bigbluebuttonbn_getCreateMeetingURL($username, $meetingID, $aPW, $mPW, $welcomeString, $logoutURL, $SALT, $URL, $record, $duration, $voiceBridge, $metadata ) );

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

    if( $xml && $xml->returncode == 'SUCCESS' && $xml->messageKey ) {//The meetings were returned
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
    }
    else if($xml && $xml->returncode == 'SUCCESS'){ //If there were meetings already created

        foreach ($xml->meetings->meeting as $meeting)
        {
            $meetings[] = array( 'meetingID' => $meeting->meetingID, 'moderatorPW' => $meeting->moderatorPW, 'attendeePW' => $meeting->attendeePW, 'hasBeenForciblyEnded' => $meeting->hasBeenForciblyEnded, 'running' => $meeting->running );
        }

        return $meetings;

    }
    else if( $xml ) { //If the xml packet returned failure it displays the message to the user
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
    }
    else { //If the server is unreachable, then prompts the user of the necessary action
        return null;
    }
}

function bigbluebuttonbn_getMeetingInfoArray( $meetingID, $modPW, $URL, $SALT ) {
    $xml = bigbluebuttonbn_wrap_simplexml_load_file( bigbluebuttonbn_getMeetingInfoURL( $meetingID, $modPW, $URL, $SALT ) );
    
    if( $xml && $xml->returncode == 'SUCCESS' && $xml->messageKey == null){//The meetings were returned
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey );
    }
    else if($xml && $xml->returncode == 'SUCCESS'){ //If there were meetings already created
        return array( 'meetingID' => $xml->meetingID, 'moderatorPW' => $xml->moderatorPW, 'attendeePW' => $xml->attendeePW, 'hasBeenForciblyEnded' => $xml->hasBeenForciblyEnded, 'running' => $xml->running, 'recording' => $xml->recording, 'startTime' => $xml->startTime, 'endTime' => $xml->endTime, 'participantCount' => $xml->participantCount, 'moderatorCount' => $xml->moderatorCount, 'attendees' => $xml->attendees, 'metadata' => $xml->metadata );
    }
    else if( ($xml && $xml->returncode == 'FAILED') || $xml) { //If the xml packet returned failure it displays the message to the user
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
        //return array('returncode' => $xml->returncode, 'message' => $xml->errors->error['message'], 'messageKey' => $xml->errors->error['key']);  //For API version 0.8
    }
    else { //If the server is unreachable, then prompts the user of the necessary action
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

function bigbluebuttonbn_wrap_simplexml_load_file($url){
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

function bigbluebuttonbn_get_db_moodle_roles($rolename='all'){
    global $DB;
    if( $rolename != 'all')
        $roles = $DB->get_record('role', array('shortname' => $rolename));
    else
        $roles = $DB->get_records('role', array());
    return $roles;
}

function bigbluebuttonbn_get_role_name($role_shortname){
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
        default:
            $role = bigbluebuttonbn_get_db_moodle_roles($role_shortname);
            if( $role != null )
                $role_name = $role->name;
            else
                $role_name = $role_shortname;
            break;
    }
    return $role_name;
}

function bigbluebuttonbn_get_roles($rolename='all'){
    $roles = bigbluebuttonbn_get_db_moodle_roles($rolename);
    $roles_json = array();
    foreach($roles as $role){
        array_push($roles_json,
                array( "id" => $role->shortname,
                    "name" => bigbluebuttonbn_get_role_name($role->shortname)
                )
        );
    }
    return $roles_json;
}

function bigbluebuttonbn_get_roles_json($rolename='all'){
    return json_encode(bigbluebuttonbn_get_roles($rolename));
}

function bigbluebuttonbn_get_users($context){
    $roles = bigbluebuttonbn_get_db_moodle_roles();
    $users_array = array();
    foreach($roles as $role){
        $users = get_role_users($role->id, $context);
        foreach($users as $user){
            array_push($users_array,
                    array( "id" => $user->id,
                        "name" => $user->firstname.' '.$user->lastname
                    )
            );
        }
    }
    return $users_array;
}

function bigbluebuttonbn_get_users_json($context){
    return json_encode(bigbluebuttonbn_get_users($context));
}

function bigbluebuttonbn_get_participant_list($bigbluebuttonbn=null){
    global $DB;
    $participant_list_array = array();
    if( $bigbluebuttonbn != null ) {
        $participant_list = json_decode(htmlspecialchars_decode($bigbluebuttonbn->participants));
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

        array_push($participant_list_array,
                array(
                    "selectiontype" => "role",
                    "selectionid" => "editingteacher",
                    "role" => BIGBLUEBUTTONBN_ROLE_MODERATOR
                )
        );
        
    }
    return $participant_list_array;
}

function bigbluebuttonbn_get_participant_list_json($bigbluebuttonbnid=null){
    return json_encode(bigbluebuttonbn_get_participant_list($bigbluebuttonbnid));
}

//error_log('db_moodle_roles: ' . print_r(json_encode($db_moodle_roles), true));
function bigbluebuttonbn_is_moderator($user, $roles, $participants) {
    $participant_list = json_decode(htmlspecialchars_decode($participants));
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