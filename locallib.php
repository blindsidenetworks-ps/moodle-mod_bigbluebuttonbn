<?php
/**
 * Internal library of functions for module BigBlueButtonBN.
 * 
 * Authors:
 *    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)    
 * 
 * @package   mod
 * @subpackage bigbluebuttonbn
 * @copyright 2010-2012 Blindside Networks 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->dirroot.'/lib/filelib.php');


function bigbluebuttonbn_rand_string($len, $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
{
    $string = '';
    for ($i = 0; $i < $len; $i++)
    {
        $pos = rand(0, strlen($chars)-1);
        $string .= $chars{$pos};
    }
    return (sha1($string));
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

        usort($recordings, bigbluebuttonbn_recordingBuildSorter('startTime'));

        return $recordings;

    } else if( $xml ) { //If the xml packet returned failure it displays the message to the user
        return array('returncode' => (string) $xml->returncode, 'message' => (string) $xml->message, 'messageKey' => (string) $xml->messageKey);
    } else { //If the server is unreachable, then prompts the user of the necessary action
        return NULL;
    }
}

function bigbluebuttonbn_recordingBuildSorter($key){
    return function ($a, $b) use ($key){
        if( $a[$key] < $b[$key]) return -1;
        else if( $a[$key] == $b[$key]) return 0;
        else return 1;
    };
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

        if($response)
            return (new SimpleXMLElement($response, LIBXML_NOCDATA));
        else
            return false;

    } else {
        return (simplexml_load_file($url,'SimpleXMLElement', LIBXML_NOCDATA));
    }

}
