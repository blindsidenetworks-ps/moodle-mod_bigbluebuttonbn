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

const BIGBLUEBUTTONBN_ROLE_VIEWER = 'viewer';
const BIGBLUEBUTTONBN_ROLE_MODERATOR = 'moderator';
const BIGBLUEBUTTONBN_METHOD_GET = 'GET';
const BIGBLUEBUTTONBN_METHOD_POST = 'POST';

const BIGBLUEBUTTON_EVENT_ACTIVITY_VIEWED = 'activity_viewed';
const BIGBLUEBUTTON_EVENT_MEETING_CREATED = 'meeting_created';
const BIGBLUEBUTTON_EVENT_MEETING_ENDED = 'meeting_ended';
const BIGBLUEBUTTON_EVENT_MEETING_JOINED = 'meeting_joined';
const BIGBLUEBUTTON_EVENT_MEETING_LEFT = "meeting_left";
const BIGBLUEBUTTON_EVENT_RECORDING_DELETED = 'recording_deleted';
const BIGBLUEBUTTON_EVENT_RECORDING_IMPORTED = 'recording_imported';
const BIGBLUEBUTTON_EVENT_RECORDING_PUBLISHED = 'recording_published';
const BIGBLUEBUTTON_EVENT_RECORDING_UNPUBLISHED = 'recording_unpublished';

function bigbluebuttonbn_logs(array $bbbsession, $event, array $overrides = [], $meta = NULL ) {
    global $DB;

    $log = new stdClass();

    $log->courseid = isset($overrides['courseid'])? $overrides['courseid']: $bbbsession['course']->id;
    $log->bigbluebuttonbnid = isset($overrides['bigbluebuttonbnid'])? $overrides['bigbluebuttonbnid']: $bbbsession['bigbluebuttonbn']->id;
    $log->userid = isset($overrides['userid'])? $overrides['userid']: $bbbsession['userID'];
    $log->meetingid = isset($overrides['meetingid'])? $overrides['meetingid']: $bbbsession['meetingid'];
    $log->timecreated = isset($overrides['timecreated'])? $overrides['timecreated']: time();
    $log->log = $event;
    if ( isset($meta) ) {
        $log->meta = $meta;
    } else if( $event == BIGBLUEBUTTONBN_LOG_EVENT_CREATE) {
        $log->meta = '{"record":'.($bbbsession['record']? 'true': 'false').'}';
    }

    $returnid = $DB->insert_record('bigbluebuttonbn_logs', $log);
}

 ////////////////////////////
//  BigBlueButton API Calls  //
 ////////////////////////////
function bigbluebuttonbn_getJoinURL( $meetingID, $userName, $PW, $SALT, $URL, $logoutURL ) {
    $url_join = $URL."api/join?";
    $params = 'meetingID='.urlencode($meetingID).'&fullName='.urlencode($userName).'&password='.urlencode($PW).'&logoutURL='.urlencode($logoutURL);
    $url = $url_join.$params.'&checksum='.sha1("join".$params.$SALT);
    return $url;
}

function bigbluebuttonbn_getCreateMeetingURL($name, $meetingID, $attendeePW, $moderatorPW, $welcome, $logoutURL, $SALT, $URL, $record = 'false', $duration=0, $voiceBridge=0, $maxParticipants=0, $metadata=array() ) {
    $url_create = $URL."api/create?";

    $params = 'name='.urlencode($name).'&meetingID='.urlencode($meetingID).'&attendeePW='.urlencode($attendeePW).'&moderatorPW='.urlencode($moderatorPW).'&logoutURL='.urlencode($logoutURL).'&record='.$record;

    $voiceBridge = intval($voiceBridge);
    if ( $voiceBridge > 0 && $voiceBridge < 79999) {
        $params .= '&voiceBridge='.$voiceBridge;
    }

    $duration = intval($duration);
    if( $duration > 0 ) {
        $params .= '&duration='.$duration;
    }

    $maxParticipants = intval($maxParticipants);
    if( $maxParticipants > 0 ) {
        $params .= '&maxParticipants='.$maxParticipants;
    }

    if( trim( $welcome ) ) {
        $params .= '&welcome='.urlencode($welcome);
    }

    foreach ($metadata as $key => $value) {
        $params .= '&'.$key.'='.urlencode($value);
    }

    $url = $url_create.$params.'&checksum='.sha1("create".$params.$SALT);
    return $url;
}

function bigbluebuttonbn_getIsMeetingRunningURL( $meetingID, $URL, $SALT ) {
    $base_url = $URL."api/isMeetingRunning?";
    $params = 'meetingID='.urlencode($meetingID);
    $url = $base_url.$params.'&checksum='.sha1("isMeetingRunning".$params.$SALT);
    return $url;
}

function bigbluebuttonbn_getMeetingInfoURL( $meetingID, $modPW, $URL, $SALT ) {
    $base_url = $URL."api/getMeetingInfo?";
    $params = 'meetingID='.urlencode($meetingID).'&password='.urlencode($modPW);
    $url = $base_url.$params.'&checksum='.sha1("getMeetingInfo".$params.$SALT);
    return $url;
}

function bigbluebuttonbn_getMeetingsURL( $URL, $SALT ) {
    $base_url = $URL."api/getMeetings?";
    $url = $base_url.'&checksum='.sha1("getMeetings".$SALT);
    return $url;
}

function bigbluebuttonbn_getEndMeetingURL( $meetingID, $modPW, $URL, $SALT ) {
    $base_url = $URL."api/end?";
    $params = 'meetingID='.urlencode($meetingID).'&password='.urlencode($modPW);
    $url = $base_url.$params.'&checksum='.sha1("end".$params.$SALT);
    return $url;
}

function bigbluebuttonbn_getRecordingsURL( $URL, $SALT, $meetingID=null ) {
    $base_url_record = $URL."api/getRecordings?";
    if( $meetingID == null ) {
        $params = "";
    } else {
        $params = "meetingID=".urlencode($meetingID);
    }
    $url = $base_url_record.$params."&checksum=".sha1("getRecordings".$params.$SALT);
    return $url;
}

function bigbluebuttonbn_getDeleteRecordingsURL( $recordID, $URL, $SALT ) {
    $url_delete = $URL."api/deleteRecordings?";
    $params = 'recordID='.urlencode($recordID);
    $url = $url_delete.$params.'&checksum='.sha1("deleteRecordings".$params.$SALT);
    return $url;
}

function bigbluebuttonbn_getPublishRecordingsURL( $recordID, $set, $URL, $SALT ) {
    $url_publish = $URL."api/publishRecordings?";
    $params = 'recordID='.$recordID."&publish=".$set;
    $url = $url_publish.$params.'&checksum='.sha1("publishRecordings".$params.$SALT);
    return $url;
}

function bigbluebuttonbn_getCreateMeetingArray( $username, $meetingID, $welcomeString, $mPW, $aPW, $SALT, $URL, $logoutURL, $record='false', $duration=0, $voiceBridge=0, $maxParticipants=0, $metadata=array(), $presentation_name=null, $presentation_url=null ) {
    $create_meeting_url = bigbluebuttonbn_getCreateMeetingURL($username, $meetingID, $aPW, $mPW, $welcomeString, $logoutURL, $SALT, $URL, $record, $duration, $voiceBridge, $maxParticipants, $metadata);
    if( !is_null($presentation_name) && !is_null($presentation_url) ) {
        $xml = bigbluebuttonbn_wrap_xml_load_file( $create_meeting_url,
                BIGBLUEBUTTONBN_METHOD_POST,
                "<?xml version='1.0' encoding='UTF-8'?><modules><module name='presentation'><document url='".$presentation_url."' /></module></modules>"
                );
    } else {
        $xml = bigbluebuttonbn_wrap_xml_load_file( $create_meeting_url );
    }

    if ( $xml ) {
        if ($xml->meetingID) {
            return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey, 'meetingID' => $xml->meetingID, 'attendeePW' => $xml->attendeePW, 'moderatorPW' => $xml->moderatorPW, 'hasBeenForciblyEnded' => $xml->hasBeenForciblyEnded );
        } else {
            return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey );
        }
    } else {
        return null;
    }
}

function bigbluebuttonbn_getMeetingsArray($meetingID, $URL, $SALT ) {
    $xml = bigbluebuttonbn_wrap_xml_load_file( bigbluebuttonbn_getMeetingsURL($URL, $SALT) );

    if ( $xml && $xml->returncode == 'SUCCESS' && $xml->messageKey ) {    //The meetings were returned
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);

    } else if($xml && $xml->returncode == 'SUCCESS') {                    //If there were meetings already created
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
    $xml = bigbluebuttonbn_wrap_xml_load_file( bigbluebuttonbn_getMeetingInfoURL( $meetingID, $modPW, $URL, $SALT ) );
    return $xml;
}

function bigbluebuttonbn_getMeetingInfoArray( $meetingID, $modPW, $URL, $SALT ) {
    $xml = bigbluebuttonbn_wrap_xml_load_file( bigbluebuttonbn_getMeetingInfoURL( $meetingID, $modPW, $URL, $SALT ) );

    if( $xml && $xml->returncode == 'SUCCESS' && $xml->messageKey == null){//The meeting info was returned
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey );

    } else if($xml && $xml->returncode == 'SUCCESS'){ //If there were meetings already created
        return array('returncode' => $xml->returncode, 'meetingID' => $xml->meetingID, 'moderatorPW' => $xml->moderatorPW, 'attendeePW' => $xml->attendeePW, 'hasBeenForciblyEnded' => $xml->hasBeenForciblyEnded, 'running' => $xml->running, 'recording' => $xml->recording, 'startTime' => $xml->startTime, 'endTime' => $xml->endTime, 'participantCount' => $xml->participantCount, 'moderatorCount' => $xml->moderatorCount, 'attendees' => $xml->attendees, 'metadata' => $xml->metadata );

    } else if( ($xml && $xml->returncode == 'FAILED') || $xml) { //If the xml packet returned failure it displays the message to the user
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);

    } else { //If the server is unreachable, then prompts the user of the necessary action
        return null;
    }
}

function bigbluebuttonbn_getRecordingsArray( $meetingIDs, $URL, $SALT ) {
    $recordings = array();

    if ( is_array($meetingIDs) ) {
        // getRecordings is executed using a method POST (supported only on BBB 1.0 and later)
        $xml = bigbluebuttonbn_wrap_xml_load_file( bigbluebuttonbn_getRecordingsURL( $URL, $SALT ), BIGBLUEBUTTONBN_METHOD_POST, $meetingIDs );
    } else {
        // getRecordings is executed using a method GET (supported by all versions of BBB)
        $xml = bigbluebuttonbn_wrap_xml_load_file( bigbluebuttonbn_getRecordingsURL( $URL, $SALT, $meetingIDs ) );
    }

    if ( $xml && $xml->returncode == 'SUCCESS' && isset($xml->recordings) ) { //If there were meetings already created
        foreach ( $xml->recordings->recording as $recording ) {
            $recordings[] = bigbluebuttonbn_getRecordingArrayRow($recording);
        }

        usort($recordings, 'bigbluebuttonbn_recordingBuildSorter');
    }

    return $recordings;
}

function bigbluebuttonbn_index_recordings($recordings, $index_key='recordID') {
    $indexed_recordings = array();

    foreach ($recordings as $recording) {
        $indexed_recordings[$recording[$index_key]] = $recording;
    }

    return $indexed_recordings;
}

function bigbluebuttonbn_getRecordingArray( $recordingID, $meetingID, $URL, $SALT ) {
    $recordingArray = array();

    $xml = bigbluebuttonbn_wrap_xml_load_file( bigbluebuttonbn_getRecordingsURL( $URL, $SALT, $meetingID ) );

    if ( $xml && $xml->returncode == 'SUCCESS' && isset($xml->recordings) ) { //If there were meetings already created
        foreach ($xml->recordings->recording as $recording) {
            if( $recording->recordID == $recordingID ) {
                $recordingArray = bigbluebuttonbn_getRecordingArrayRow($recording);
                break;
            }
        }
    }

    return $recordingArray;
}

function bigbluebuttonbn_getRecordingArrayRow( $recording ) {

    $playbackArray = array();
    foreach ( $recording->playback->format as $format ) {
        $playbackArray[(string) $format->type] = array( 'type' => (string) $format->type, 'url' => (string) $format->url, 'length' => (string) $format->length );
    }

    //Add the metadata to the recordings array
    $metadataArray = array();
    $metadata = get_object_vars($recording->metadata);
    foreach ( $metadata as $key => $value ) {
        if ( is_object($value) ) {
            $value = '';
        }
        $metadataArray['meta_'.$key] = $value;
    }

    $recordingArrayRow = array( 'recordID' => (string) $recording->recordID, 'meetingID' => (string) $recording->meetingID, 'meetingName' => (string) $recording->name, 'published' => (string) $recording->published, 'startTime' => (string) $recording->startTime, 'endTime' => (string) $recording->endTime, 'playbacks' => $playbackArray ) + $metadataArray;

    return $recordingArrayRow;
}

function bigbluebuttonbn_recordingBuildSorter($a, $b){
    if ( $a['startTime'] < $b['startTime'] ) {
        return -1;
    } else if ( $a['startTime'] == $b['startTime']) {
        return 0;
    } else {
        return 1;
    }
}

function bigbluebuttonbn_doDeleteRecordings( $recordIDs, $URL, $SALT ) {
    $ids = 	explode(",", $recordIDs);
    foreach( $ids as $id){
        $xml = bigbluebuttonbn_wrap_xml_load_file( bigbluebuttonbn_getDeleteRecordingsURL($id, $URL, $SALT) );
        if( $xml && $xml->returncode != 'SUCCESS' ) {
            return false;
        }
    }
    return true;
}

function bigbluebuttonbn_doPublishRecordings( $recordIDs, $set, $URL, $SALT ) {
    $ids = 	explode(",", $recordIDs);
    foreach( $ids as $id){
        $xml = bigbluebuttonbn_wrap_xml_load_file( bigbluebuttonbn_getPublishRecordingsURL($id, $set, $URL, $SALT) );
        if( $xml && $xml->returncode != 'SUCCESS' ) {
            return false;
        }
    }
    return true;
}

function bigbluebuttonbn_doEndMeeting( $meetingID, $modPW, $URL, $SALT ) {
    $xml = bigbluebuttonbn_wrap_xml_load_file( bigbluebuttonbn_getEndMeetingURL( $meetingID, $modPW, $URL, $SALT ) );

    if( $xml ) { //If the xml packet returned failure it displays the message to the user
        return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
    }
    else { //If the server is unreachable, then prompts the user of the necessary action
        return null;
    }
}

function bigbluebuttonbn_isMeetingRunning( $meetingID, $URL, $SALT ) {
    $xml = bigbluebuttonbn_wrap_xml_load_file( bigbluebuttonbn_getIsMeetingRunningURL( $meetingID, $URL, $SALT ) );
    if ( $xml && $xml->returncode == 'SUCCESS' ) {
        return ( ( $xml->running == 'true' ) ? true : false);
    } else {
        return ( false );
    }
}


function bigbluebuttonbn_getServerVersion( $URL ){
    $xml = bigbluebuttonbn_wrap_xml_load_file( $URL."api" );
    if ( $xml && $xml->returncode == 'SUCCESS' ) {
        return $xml->version;
    } else {
        return NULL;
    }
}

function bigbluebuttonbn_getMeetingXML( $meetingID, $URL, $SALT ) {
    $xml = bigbluebuttonbn_wrap_xml_load_file( bigbluebuttonbn_getIsMeetingRunningURL( $meetingID, $URL, $SALT ) );
    if ( $xml && $xml->returncode == 'SUCCESS') {
        return ( str_replace('</response>', '', str_replace("<?xml version=\"1.0\"?>\n<response>", '', $xml->asXML())));
    } else {
        return 'false';
    }
}

function bigbluebuttonbn_wrap_xml_load_file($url, $method=BIGBLUEBUTTONBN_METHOD_GET, $data=null) {
    if ( bigbluebuttonbn_debugdisplay() ) error_log("Request to: ".$url);

    if (extension_loaded('curl')) {
        $c = new curl();
        $c->setopt( Array( "SSL_VERIFYPEER" => true));
        if( $method == BIGBLUEBUTTONBN_METHOD_POST ) {
            if( !is_null($data) ) {
                if( !is_array($data) ) {
                    $options['CURLOPT_HTTPHEADER'] = array(
                            'Content-Type: text/xml',
                            'Content-Length: '.strlen($data),
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
            $previous = libxml_use_internal_errors(true);
            try {
                $xml = new SimpleXMLElement($response, LIBXML_NOCDATA);
                return $xml;
            } catch (Exception $e){
                libxml_use_internal_errors($previous);
                // Caught exception $e->getMessage().
                return NULL;
            }
        } else {
            // No response on wrap_simplexml_load_file.
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

function bigbluebuttonbn_get_user_roles($context, $userid) {
    global $DB;

    $user_roles = array();
    $user_roles = get_user_roles($context, $userid);
    if ($user_roles) {
        $where = '';
        foreach ($user_roles as $key => $value){
            $where .= (empty($where) ? ' WHERE' : ' OR').' id='.$value->roleid;
        }
        $user_roles = $DB->get_records_sql('SELECT * FROM {role}'.$where);
    }
    return $user_roles;
}

function bigbluebuttonbn_get_guest_role(context $context = null) {
    $guest_role = get_guest_role();
    return array($guest_role->id => $guest_role);
}

function bigbluebuttonbn_get_users(context $context = null) {
    $users = get_enrolled_users($context,'',0,'u.*',null,0,0,true);
    foreach ($users as $key => $value) {
        $users[$key] = fullname($value);
    }
    return $users;
}

function bigbluebuttonbn_get_users_json($users) {
    $users_array = array();
    foreach ($users as $key => $value) {
        array_push($users_array, array(
            "id" => $key,
            "name" => $value)
        );
    }
    return json_encode($users_array);
}

function bigbluebuttonbn_get_users_select(context $context = null) {
    $users = get_enrolled_users($context,'',0,'u.*',null,0,0,true);
    foreach ($users as $key => $value) {
        $users[$key] = array('id' => $value->id, 'name' => fullname($value));
    }
    return $users;
}

function bigbluebuttonbn_get_role_name($role_shortname) {
    $role = bigbluebuttonbn_get_role($role_shortname);
    if (!$role) {
        return get_string('mod_form_field_participant_role_unknown', 'bigbluebuttonbn');
    }
    if ($role->name != "") {
        return $role->name;
    }
    return role_get_name($role);
}

function bigbluebuttonbn_get_roles($rolename='all', $format='json'){
    $roles = bigbluebuttonbn_get_moodle_roles($rolename);
    $roles_array = array();
    foreach($roles as $role) {
        if( $format=='json' ) {
            array_push($roles_array,
                    array( "id" => $role->id,
                        "name" => bigbluebuttonbn_get_role_name($role->id)
                    )
            );
        } else {
            $roles_array[$role->id] = bigbluebuttonbn_get_role_name($role->id);
        }
    }
    return $roles_array;
}

function bigbluebuttonbn_get_roles_json($rolename='all'){
    return json_encode(bigbluebuttonbn_get_roles($rolename));
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
    if (is_numeric($id) && array_key_exists($id, $roles)) {
        return $roles[$id];
    }
    foreach ($roles as $role) {
        if ($role->shortname == $id) {
            return $role;
        }
    }
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
        if ($rule['selectiontype'] === 'role' && !is_numeric($rule['selectionid'])) {
            $role = bigbluebuttonbn_get_role($rule['selectionid']);
            if ($role) {
                $rule['selectionid'] = $role->id;
            }
        }
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
    $moderatordefaults = explode(',', bigbluebuttonbn_get_cfg_moderator_default());
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

function bigbluebuttonbn_is_moderator($user, $roles, $participants) {
    $participantlist = json_decode($participants);
    if (!is_array($participantlist)) {
        return false;
    }
    // Iterate looking for all configuration.
    foreach ($participantlist as $participant) {
        if( $participant->selectiontype == 'all' ) {
            if ( $participant->role == BIGBLUEBUTTONBN_ROLE_MODERATOR ) {
                return true;
            }
        }
    }
    // Iterate looking for roles.
    $moodleroles = bigbluebuttonbn_get_moodle_roles();
    foreach ($participantlist as $participant) {
        if ($participant->selectiontype == 'role') {
            $selectionid = $participant->selectionid;
            // For backward compatibility when selectiontype contains the role shortname.
            if ( !is_numeric($selectionid) ) {
                $moodlerole = bigbluebuttonbn_moodle_db_role_lookup($moodleroles, $selectionid);
                $selectionid = $moodlerole->id;
            }
            if (array_key_exists($selectionid, $roles)) {
                if ( $participant->role == BIGBLUEBUTTONBN_ROLE_MODERATOR ) {
                    return true;
                }
            }
        }
    }
    // Iterate looking for users.
    foreach($participantlist as $participant) {
        if( $participant->selectiontype == 'user' ) {
            if( $participant->selectionid == $user ) {
                if ( $participant->role == BIGBLUEBUTTONBN_ROLE_MODERATOR ) {
                    return true;
                }
            }
        }
    }
    return false;
}

function bigbluebuttonbn_moodle_db_role_lookup($db_moodle_roles, $role_id) {
    if ( is_int($role_id) && array_key_exists($role_id, $db_moodle_roles) ) {
        return $db_moodle_roles[$role_id];
    }
    foreach( $db_moodle_roles as $db_moodle_role ) {
        if ( $role_id == $db_moodle_role->shortname ) {
            return $db_moodle_role;
        }
    }
}

function bigbluebuttonbn_get_error_key($messageKey, $defaultKey = null) {
    $key = $defaultKey;
    if ( $messageKey == "checksumError" ) {
        $key = 'index_error_checksum';
    } else if ( $messageKey == 'maxConcurrent' ) {
        $key = 'view_error_max_concurrent';
    }
    return $key;
}

function bigbluebuttonbn_voicebridge_unique($voicebridge, $id=null) {
    global $DB;

    $is_unique = true;
    if ( $voicebridge != 0 ) {
        $table = "bigbluebuttonbn";
        $select = "voicebridge = ".$voicebridge;
        if ( $id ) {
            $select .= " AND id <> ".$id;
        }
        if ( $rooms = $DB->get_records_select($table, $select)  ) {
            $is_unique = false;
        }
    }

    return $is_unique;
}

function bigbluebuttonbn_get_duration($openingtime, $closingtime) {
    global $CFG;

    $duration = 0;
    $now = time();
    if ( $closingtime > 0 && $now < $closingtime ) {
        $duration = ceil(($closingtime - $now)/60);
        $compensation_time = intval(bigbluebuttonbn_get_cfg_scheduled_duration_compensation());
        $duration = intval($duration) + $compensation_time;
    }

    return $duration;
}

function bigbluebuttonbn_get_presentation_array($context, $presentation, $id=null) {
    $presentation_name = null;
    $presentation_url = null;
    $presentation_icon = null;
    $presentation_mimetype_description = null;

    if ( !empty($presentation) ) {
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

function bigbluebuttonbn_get_moodle_version_major() {
    global $CFG;

    $version_array = explode('.', $CFG->version);
    return $version_array[0];
}

function bigbluebuttonbn_event_log_standard($event_type, $bigbluebuttonbn, $context, $cm) {
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
    if ( $version_major < '2014051200' ) {
        //This is valid before v2.7
        bigbluebuttonbn_event_log_legacy($event_type, $bigbluebuttonbn, $context, $cm);

    } else {
        //This is valid after v2.7
        bigbluebuttonbn_event_log_standard($event_type, $bigbluebuttonbn, $context, $cm);
    }
}

function bigbluebuttonbn_bbb_broker_get_recordings($meetingid, $password, $forced=false) {
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
    if( $is_moderator ) {
        $meeting_info->moderatorCount += 1;
    }
    $cache->set($meetingid, array('creation_time' => $result['creation_time'], 'meeting_info' => json_encode($meeting_info) ));
}

function bigbluebuttonbn_bbb_broker_is_meeting_running($meeting_info) {
    $meeting_running = ( isset($meeting_info) && isset($meeting_info->returncode) && $meeting_info->returncode == 'SUCCESS' );

    return $meeting_running;
}

function bigbluebuttonbn_bbb_broker_get_meeting_info($meetingid, $password, $forced=false) {
    global $CFG;

    $meeting_info = array();
    $endpoint = bigbluebuttonbn_get_cfg_server_url();
    $shared_secret = bigbluebuttonbn_get_cfg_shared_secret();
    $cache_ttl = bigbluebuttonbn_get_cfg_waitformoderator_cache_ttl();

    $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'meetings_cache');
    $result = $cache->get($meetingid);
    $now = time();
    if( isset($result) && $now < ($result['creation_time'] + $cache_ttl) && !$forced ) {
        //Use the value in the cache
        $meeting_info = json_decode($result['meeting_info']);
    } else {
        //Ping again and refresh the cache
        $meeting_info = (array) bigbluebuttonbn_getMeetingInfo( $meetingid, $password, $endpoint, $shared_secret );
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

function bigbluebuttonbn_bbb_broker_do_publish_recording($recordingid, $publish=true){
    global $CFG;

    $endpoint = bigbluebuttonbn_get_cfg_server_url();
    $shared_secret = bigbluebuttonbn_get_cfg_shared_secret();

    bigbluebuttonbn_doPublishRecordings($recordingid, ($publish)? 'true': 'false', $endpoint, $shared_secret);
}

function bigbluebuttonbn_bbb_broker_do_publish_recording_imported($recordingid, $courseID, $bigbluebuttonbnID, $publish=true){
    global $DB;

    //Locate the record to be updated
    $records = $DB->get_records('bigbluebuttonbn_logs', array('bigbluebuttonbnid' => $bigbluebuttonbnID, 'log' => BIGBLUEBUTTONBN_LOG_EVENT_IMPORT));

    $recordings_imported = array();
    foreach ($records as $key => $record) {
        $meta = json_decode($record->meta, true);
        if( $recordingid == $meta['recording']['recordID'] ) {
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
        if( $recordingid == $meta['recording']['recordID'] ) {
            // Execute delete
            $DB->delete_records("bigbluebuttonbn_logs", array('id' => $key));
        }
    }
}

function bigbluebuttonbn_bbb_broker_validate_parameters($params) {
    $error = '';

    if ( !isset($params['callback']) ) {
        $error = bigbluebuttonbn_bbb_broker_add_error($error, 'This call must include a javascript callback.');
    }

    if ( !isset($params['action']) ) {
        $error = bigbluebuttonbn_bbb_broker_add_error($error, 'Action parameter must be included.');
    } else {
        switch ( strtolower($params['action']) ){
            case 'server_ping':
            case 'meeting_info':
            case 'meeting_end':
                if ( !isset($params['id']) ) {
                    $error = bigbluebuttonbn_bbb_broker_add_error($error, 'The meetingID must be specified.');
                }
                break;
            case 'recording_list':
            case 'recording_info':
            case 'recording_publish':
            case 'recording_unpublish':
            case 'recording_delete':
            case 'recording_import':
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
        if( !empty($error) ) $error .= ' ';
        $error .= $new_msg;
    }

    return $error;
}

function bigbluebuttonbn_get_recording_data_row($bbbsession, $recording, $tools=["publishing", "deleting"]) {
    global $OUTPUT, $CFG, $USER;

    $row = null;

    if ( $bbbsession['managerecordings'] || $recording['published'] == 'true' ) {
        $startTime = isset($recording['startTime'])? floatval($recording['startTime']):0;
        $startTime = $startTime - ($startTime % 1000);
        $duration = intval(array_values($recording['playbacks'])[0]['length']);

        //For backward compatibility
        if( isset($recording['meta_contextactivity']) ) {
            $meta_activity = str_replace('"', '\"', $recording['meta_contextactivity']);
        } if( isset($recording['meta_bbb-recording-name']) ) {
            $meta_activity = str_replace('"', '\"', $recording['meta_bbb-recording-name']);
        } else {
            $meta_activity = str_replace('"', '\"', $recording['meetingName']);
        }

        if( isset($recording['meta_contextactivitydescription']) ) {
            $meta_description = str_replace('"', '\"', $recording['meta_contextactivitydescription']);
        } else if( isset($recording['meta_bbb-recording-description']) ) {
            $meta_description = str_replace('"', '\"', $recording['meta_bbb-recording-description']);
        } else {
            $meta_description = '';
        }

        //Set recording_types
        if ( isset($recording['imported']) ) {
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
        foreach ( $recording['playbacks'] as $playback ) {
            $recording_types .= $OUTPUT->action_link($playback['url'], get_string('view_recording_format_'.$playback['type'], 'bigbluebuttonbn'), null, array('title' => get_string('view_recording_format_'.$playback['type'], 'bigbluebuttonbn'), 'target' => '_new') ).'&#32;';
        }
        $recording_types .= '</div>';

        //Initialize variables for styling text
        $head = $tail = '';

        //Set actionbar, if user is allowed to manage recordings
        $actionbar = '';
        if ( $bbbsession['managerecordings'] ) {
            // Set style for imported links
            if( isset($recording['imported']) ) {
                $recordings_imported_count = 0;
                $tag_tail = ' '.get_string('view_recording_link', 'bigbluebuttonbn');
                $head = '<i>';
                $tail = '</i>';
            } else {
                $recordings_imported_array = bigbluebuttonbn_getRecordingsImportedAllInstancesArray($recording['recordID']);
                $recordings_imported_count = count($recordings_imported_array);
                $tag_tail = '';
            }

            $url = '#';
            $action = null;

            if (in_array("publishing", $tools)) {
                ///Set action [show|hide]
                if ( $recording['published'] == 'true' ){
                    $manage_tag = 'hide';
                    $manage_action = 'unpublish';
                } else {
                    $manage_tag = 'show';
                    $manage_action = 'publish';
                }
                $onclick = 'M.mod_bigbluebuttonbn.broker_manageRecording("'.$manage_action.'", "'.$recording['recordID'].'", "'.$recording['meetingID'].'");';

                if ( bigbluebuttonbn_get_cfg_recording_icons_enabled() ) {
                    //With icon for publish/unpublish
                    $icon_attributes = array('id' => 'recording-btn-'.$manage_action.'-'.$recording['recordID']);
                    $icon = new pix_icon('t/'.$manage_tag, get_string($manage_tag).$tag_tail, 'moodle', $icon_attributes);
                    $link_attributes = array('id' => 'recording-link-'.$manage_action.'-'.$recording['recordID'], 'onclick' => $onclick, 'data-links' => $recordings_imported_count);
                    $actionbar .= $OUTPUT->action_icon($url, $icon, $action, $link_attributes, false);
                } else {
                    //With text for publish/unpublish
                    $link_attributes = array('title' => get_string($manage_tag).$tag_tail, 'class' => 'btn btn-xs', 'onclick' => $onclick, 'data-links' => $recordings_imported_count);
                    $actionbar .= $OUTPUT->action_link($url, get_string($manage_tag).$tag_tail, $action, $link_attributes);
                    $actionbar .= "&nbsp;";
                }
            }

            if (in_array("deleting", $tools)) {
                $onclick = 'M.mod_bigbluebuttonbn.broker_manageRecording("delete", "'.$recording['recordID'].'", "'.$recording['meetingID'].'");';

                if ( bigbluebuttonbn_get_cfg_recording_icons_enabled() ) {
                    //With icon for delete
                    $icon_attributes = array('id' => 'recording-btn-delete-'.$recording['recordID']);
                    $icon = new pix_icon('t/delete', get_string('delete').$tag_tail, 'moodle', $icon_attributes);
                    $link_attributes = array('id' => 'recording-link-delete-'.$recording['recordID'], 'onclick' => $onclick, 'data-links' => $recordings_imported_count);
                    $actionbar .= $OUTPUT->action_icon($url, $icon, $action, $link_attributes, false);
                } else {
                    //With text for delete
                    $link_attributes = array('title' => get_string('delete').$tag_tail, 'class' => 'btn btn-xs btn-danger', 'onclick' => $onclick, 'data-links' => $recordings_imported_count);
                    $actionbar .= $OUTPUT->action_link($url, get_string('delete').$tag_tail, $action, $link_attributes);
                }
            }

            if (in_array("importing", $tools)) {
                $onclick = 'M.mod_bigbluebuttonbn.broker_manageRecording("import", "'.$recording['recordID'].'", "'.$recording['meetingID'].'");';

                if ( bigbluebuttonbn_get_cfg_recording_icons_enabled() ) {
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
        $row->date = floatval($recording['startTime']);
        $row->date_formatted = "{$head}{$formattedStartDate}{$tail}";
        $row->duration = "{$duration}";
        $row->duration_formatted = "{$head}{$duration}{$tail}";
        if ( $bbbsession['managerecordings'] ) {
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
    $view_recording_date = get_string('view_recording_date', 'bigbluebuttonbn');
    $view_recording_duration = get_string('view_recording_duration', 'bigbluebuttonbn');
    $view_recording_actionbar = get_string('view_recording_actionbar', 'bigbluebuttonbn');

    ///Initialize table headers
    $recordingsbn_columns = array(
        array("key" =>"recording", "label" => $view_recording_recording, "width" => "125px", "allowHTML" => true),
        array("key" =>"activity", "label" => $view_recording_activity, "sortable" => true, "width" => "175px", "allowHTML" => true),
        array("key" =>"description", "label" => $view_recording_description, "sortable" => true, "width" => "250px", "allowHTML" => true),
        array("key" =>"date", "label" => $view_recording_date, "sortable" => true, "width" => "220px", "allowHTML" => true),
        array("key" =>"duration", "label" => $view_recording_duration, "width" => "50px")
        );

    if ( $bbbsession['managerecordings'] ) {
        array_push($recordingsbn_columns, array("key" =>"actionbar", "label" => $view_recording_actionbar, "width" => "75px", "allowHTML" => true));
    }

    return $recordingsbn_columns;
}

function bigbluebuttonbn_get_recording_data($bbbsession, $recordings, $tools=["publishing", "deleting"]) {
    $table_data = array();

    ///Build table content
    if ( isset($recordings) && !array_key_exists('messageKey', $recordings)) {  // There are recordings for this meeting
        foreach ( $recordings as $recording ) {
            $row = bigbluebuttonbn_get_recording_data_row($bbbsession, $recording, $tools);
            if( $row != null ) {
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
    $view_duration_min = get_string('view_recording_duration_min', 'bigbluebuttonbn');

    ///Declare the table
    $table = new html_table();
    $table->data = array();

    ///Initialize table headers
    if ( $bbbsession['managerecordings'] ) {
        $table->head  = array ($view_recording_recording, $view_recording_activity, $view_recording_description, $view_recording_date, $view_recording_duration, $view_recording_actionbar);
        $table->align = array ('left', 'left', 'left', 'left', 'center', 'left');
    } else {
        $table->head  = array ($view_recording_recording, $view_recording_activity, $view_recording_description, $view_recording_date, $view_recording_duration);
        $table->align = array ('left', 'left', 'left', 'left', 'center');
    }

    ///Build table content
    if ( isset($recordings) && !array_key_exists('messageKey', $recordings)) {  // There are recordings for this meeting.
        foreach ( $recordings as $recording ) {
            if ( !isset($recording['imported']) && isset($bbbsession['group']) && $recording['meetingID'] != $bbbsession['meetingid'] ) {
                continue;
            }
            $row = new html_table_row();
            $row->id = 'recording-td-'.$recording['recordID'];
            if ( isset($recording['imported']) ) {
                $row->attributes['data-imported'] = 'true';
                $row->attributes['title'] = get_string('view_recording_link_warning', 'bigbluebuttonbn');
            } else {
                $row->attributes['data-imported'] = 'false';
            }

            $row_data = bigbluebuttonbn_get_recording_data_row($bbbsession, $recording, $tools);
            if( $row_data != null ) {
                $row_data->date_formatted = str_replace(" ", "&nbsp;", $row_data->date_formatted);
                if ( $bbbsession['managerecordings'] ) {
                    $row->cells = array ($row_data->recording, $row_data->activity, $row_data->description, $row_data->date_formatted, $row_data->duration_formatted, $row_data->actionbar );
                } else {
                    $row->cells = array ($row_data->recording, $row_data->activity, $row_data->description, $row_data->date_formatted, $row_data->duration_formatted );
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

    if( $host_ends_length > 0 && $host_ends[$host_ends_length -1] == 'com' &&  $host_ends[$host_ends_length -2] == 'blindsidenetworks' ) {
        //Validate the capabilities offered
        $capabilities = bigbluebuttonbn_getCapabilitiesArray( $endpoint, $shared_secret );
        if( $capabilities ) {
            foreach ($capabilities as $capability) {
                if ( $capability["name"] == $capability_name) {
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

function bigbluebuttonbn_get_locales_for_ui() {
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

function bigbluebuttonbn_get_cfg_recordingstatus_enabled() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordingstatus_enabled)? $BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordingstatus_enabled: (isset($CFG->bigbluebuttonbn_recordingstatus_enabled)? $CFG->bigbluebuttonbn_recordingstatus_enabled: false));
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

function bigbluebuttonbn_import_get_courses_for_select(array $bbbsession) {

    if( $bbbsession['administrator'] ) {
        $courses = get_courses('all', 'c.id ASC', 'c.id,c.shortname,c.fullname');
        //It includes the name of the site as a course (category 0), so remove the first one
        unset($courses["1"]);
    } else {
        $courses = enrol_get_users_courses($bbbsession['userID'], false, 'id,shortname,fullname');
    }

    $courses_for_select = [];
    foreach($courses as $course) {
        if( $course->id != $bbbsession['course']->id ) {
            $courses_for_select[$course->id] = $course->fullname;
        }
    }
    return $courses_for_select;
}

function bigbluebuttonbn_getRecordedMeetingsDeleted($courseID, $bigbluebuttonbnID=NULL) {
    global $DB;

    $records_deleted = array();

    $filter = array('courseid' => $courseID, 'log' => BIGBLUEBUTTONBN_LOG_EVENT_DELETE );
    if ( $bigbluebuttonbnID != NULL ) {
        $filter['id'] = $bigbluebuttonbnID;
    }

    $bigbluebuttonbns_deleted = $DB->get_records('bigbluebuttonbn_logs', $filter);

    foreach ($bigbluebuttonbns_deleted as $key => $bigbluebuttonbn_deleted) {
        $records = $DB->get_records('bigbluebuttonbn_logs', array('courseid' => $courseID, 'log' => BIGBLUEBUTTONBN_LOG_EVENT_CREATE));

        if( !empty($records) ) {
            //Remove duplicates
            $unique_records = array();
            foreach ($records as $key => $record) {
                if (array_key_exists($record->meetingid, $unique_records) ) {
                    unset($records[$key]);
                } else {
                    $meta = json_decode($record->meta);
                    if ( !$meta->record ) {
                        unset($records[$key]);
                    } else if ( $bigbluebuttonbn_deleted->meetingid != substr($record->meetingid, 0, strlen($bigbluebuttonbn_deleted->meetingid))) {
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

    $records = Array();

    $filter = array('course' => $courseID);
    if ( $bigbluebuttonbnID != NULL ) {
        $filter['id'] = $bigbluebuttonbnID;
    }
    $bigbluebuttonbns = $DB->get_records('bigbluebuttonbn', $filter);

    if ( !empty($bigbluebuttonbns) ) {
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
            if( array_search($record_key, $unique_records) === false ) {
                array_push($unique_records, $record_key);
            } else {
                unset($records[$key]);
            }
        }

        //Remove the ones with record=false
        foreach ($records as $key => $record) {
            $meta = json_decode($record->meta);
            if ( !$meta || !$meta->record ) {
                unset($records[$key]);
            }
        }
    }

    return $records;
}

function bigbluebuttonbn_getRecordingsArrayByCourse($courseID, $URL, $SALT) {
    $recordings = array();

    // Load the meetingIDs to be used in the getRecordings request
    if ( is_numeric($courseID) ) {
        $results = bigbluebuttonbn_getRecordedMeetings($courseID);

        if( bigbluebuttonbn_get_cfg_importrecordings_from_deleted_activities_enabled() ) {
            $results_deleted = bigbluebuttonbn_getRecordedMeetingsDeleted($courseID);
            $results = array_merge($results, $results_deleted);
        }

        if( $results ) {
            $mIDs = array();
            //Eliminates duplicates
            foreach ($results as $result) {
                $mIDs[$result->meetingid] = $result->meetingid;
            }

            // If there are mIDs excecute a paginated getRecordings request
            if ( !empty($mIDs) ) {
                $pages = floor(sizeof($mIDs) / 25) + 1;
                for ( $page = 1; $page <= $pages; $page++ ) {
                    $meetingIDs = array_slice($mIDs, ($page-1)*25, 25);
                    $fetched_recordings = bigbluebuttonbn_getRecordingsArray(implode(',', $meetingIDs), $URL, $SALT);
                    $recordings = array_merge($recordings, $fetched_recordings);
                }
            }
        }
    }

    return $recordings;
}

function bigbluebuttonbn_import_get_recordings_imported($records) {
    $recordings_imported = array();

    foreach ($records as $key => $record) {
        $meta = json_decode($record->meta, true);
        $recordings_imported[] = $meta['recording'];
    }

    return $recordings_imported;
}

function bigbluebuttonbn_import_exlcude_recordings_already_imported($courseID, $bigbluebuttonbnID, $recordings) {
    $recordings_already_imported = bigbluebuttonbn_getRecordingsImportedArray($courseID, $bigbluebuttonbnID);
    $recordings_already_imported_indexed = bigbluebuttonbn_index_recordings($recordings_already_imported);

    foreach ($recordings as $key => $recording) {
        if( isset($recordings_already_imported_indexed[$recording['recordID']]) ) {
            unset($recordings[$key]);
        }
    }
    return $recordings;
}

function bigbluebutton_output_recording_table($bbbsession, $recordings, $tools=['publishing','deleting']) {

    if ( isset($recordings) && !empty($recordings) ) {  // There are recordings for this meeting
        $table = bigbluebuttonbn_get_recording_table($bbbsession, $recordings, $tools);
    }

    $output = '';
    if( isset($table->data) ) {
        //Print the table
        $output .= '<div id="bigbluebuttonbn_html_table">'."\n";
        $output .= html_writer::table($table)."\n";
        $output .= '</div>'."\n";

    } else {
        $output .= get_string('view_message_norecordings', 'bigbluebuttonbn').'<br>'."\n";
    }

    return $output;
}

function bigbluebuttonbn_getRecordingsImported($courseID, $bigbluebuttonbnID=NULL) {
    global $DB;

    if ( $bigbluebuttonbnID != NULL ) {
        // Fetch only those related to the $courseID and $bigbluebuttonbnID requested
        $recordings_imported = $DB->get_records('bigbluebuttonbn_logs', array('courseid' => $courseID, 'bigbluebuttonbnid' => $bigbluebuttonbnID, 'log' => BIGBLUEBUTTONBN_LOG_EVENT_IMPORT));
    } else {
        // Fetch all the ones corresponding to the $courseID requested
        $recordings_imported = $DB->get_records('bigbluebuttonbn_logs', array('courseid' => $courseID, 'log' => BIGBLUEBUTTONBN_LOG_EVENT_IMPORT));
    }
    return $recordings_imported;
}

function bigbluebuttonbn_getRecordingsImportedArray($courseID, $bigbluebuttonbnID=NULL) {
    $recordings_imported = bigbluebuttonbn_getRecordingsImported($courseID, $bigbluebuttonbnID);
    $recordings_imported_array = bigbluebuttonbn_import_get_recordings_imported($recordings_imported);
    return $recordings_imported_array;
}

function bigbluebuttonbn_getRecordingsImportedAllInstances($recordID) {
    global $DB, $CFG;

    $recordings_imported = $DB->get_records_sql('SELECT * FROM '.$CFG->prefix.'bigbluebuttonbn_logs WHERE log=? AND '.$DB->sql_like('meta', '?'), array( BIGBLUEBUTTONBN_LOG_EVENT_IMPORT, '%'.$recordID.'%' ));
    return $recordings_imported;
}

function bigbluebuttonbn_getRecordingsImportedAllInstancesArray($recordID) {
    $recordings_imported = bigbluebuttonbn_getRecordingsImportedAllInstances($recordID);
    $recordings_imported_array = bigbluebuttonbn_import_get_recordings_imported($recordings_imported);
    return $recordings_imported_array;
}

function bigbluebuttonbn_debugdisplay() {
    global $CFG;

    return (bool)$CFG->debugdisplay;
}

function bigbluebuttonbn_html2text($html, $len) {
    $text = strip_tags($html);
    $text = str_replace("&nbsp;", ' ', $text);
    if( strlen($text) > $len ) {
        $text = substr($text, 0, $len)."...";
    } else {
        $text = substr($text, 0, $len);
    }
    return $text;
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
