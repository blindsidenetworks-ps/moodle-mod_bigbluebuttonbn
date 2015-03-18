<?php
/**
 * Ping the BigBlueButton server to see if the meeting is running
 *
 * @package   mod_bigbluebuttonbn
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

global $PAGE, $USER;

$params['callback'] = required_param('callback', PARAM_TEXT);
$params['action']  = required_param('action', 0, PARAM_TEXT);
$params['id'] = required_param('id', PARAM_TEXT);
$params['bigbluebuttonbn'] = required_param('bigbluebuttonbn', 0, PARAM_INT);

$error = bigbluebuttonbn_broker_validate_parameters($params);

header('Content-Type: application/json; charset=utf-8');
if ( !empty($error) ) {

    if (!isloggedin() && $PAGE->course->id == SITEID) {
        $userid = guest_user()->id;
    } else {
        $userid = $USER->id;
    }
    $hascourseaccess = ($PAGE->course->id == SITEID) || can_access_course($PAGE->course, $userid);
    	
    if( !$hascourseaccess ){
        header("HTTP/1.0 401 Unauthorized");
    } else {
        $endpoint = trim(trim($CFG->bigbluebuttonbn_server_url),'/').'/';
        $shared_secret = trim($CFG->bigbluebuttonbn_shared_secret);

        try {
            switch ( strtolower($params['action']) ){
                case 'ping':
                    $meeting_running = bigbluebuttonbn_meeting_ping( $params['id'], $endpoint, $shared_secret );
                    if( $meeting_running  ) {
                        bigbluebuttonbn_broker_event_log(BIGBLUEBUTTON_EVENT_MEETING_JOINED, $params['bigbluebuttonbn']);
                    }
                    echo $params['callback'].'({ "status": "'.($meeting_running?'true':'false').'" });';

                    break;
                case 'info':
                    break;
                case 'end':
                    break;
                case 'recordings':
                    break;
                case 'publish':
                    break;
                case 'unpublish':
                    break;
                case 'delete':
                    break;
            }

        } catch(Exception $e) {
            header("HTTP/1.0 502 Bad Gateway. ".$e->getMessage());
        }
    }

} else {
    header("HTTP/1.0 400 Bad Request. ".$error);
}

function bigbluebuttonbn_meeting_ping($meetingid, $endpoint, $shared_secret) {
    global $CFG;

    $meeting_running = false;
    $cache_ttl = $CFG->bigbluebuttonbn_waitformoderator_cache_ttl;

    $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'ping_cache');
    $result = $cache->get($meetingid);
    $now = time();
    if( isset($result) && $now < ($result['creation_time'] + $cache_ttl) ) {
        //Use the value in the cache
        $meeting_running = $result['meeting_running'];
    } else {
        //Ping again and refresh the cache
        $meeting_running = bigbluebuttonbn_isMeetingRunning( $meetingid, $endpoint, $shared_secret );
        $cache->set($meetingid, array('creation_time' => time(), 'meeting_running' => $meeting_running));
    }

    return $meeting_running;
}

function bigbluebuttonbn_broker_validate_parameters($params) {
    $error = '';

    if (!$params['callback']) {
        if( !empty($error) ) $error .= ' ';
        $error .= 'This call must include a javascript callback.';
    }

    if (!$params['action']) {
        if( !empty($error) ) $error .= ' ';
        $error .= 'Action parameter must be included.';
    } else {
        switch ( strtolower($params['action']) ){
            case 'ping': 
            case 'info':
            case 'end':
                if (!$params['id']) {
                    bigbluebuttonbn_broker_add_error('The meetingID must be specified.');
                }
                break;
            case 'recordings': 
            case 'publish':
            case 'unpublish':
            case 'delete':
                if (!$params['id']) {
                    bigbluebuttonbn_broker_add_error('The recordingID must be specified.');
                }
                break;
            default:
                bigbluebuttonbn_broker_add_error('Action '.$params['action'].' can not be performed.');
        }
    }

    return $error;
}

function bigbluebuttonbn_broker_add_error($error) {

    if( !empty($error) ) $error .= ' ';
    $error .= 'The meetingID must be specified.';

    return $error;
}

function bigbluebuttonbn_broker_event_log($event_type, $bigbluebuttonbn_id) {

    if ( $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $bigbluebuttonbn_id), '*', MUST_EXIST) ) {
        $course = $DB->get_record('course', array('id' => $bigbluebuttonbn->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('bigbluebuttonbn', $bigbluebuttonbn->id, $course->id, false, MUST_EXIST);
        /// Moodle event logger: Create an event for meeting joined
        bigbluebuttonbn_event_log($event_type, $bigbluebuttonbn, $context, $cm);
    }

}
