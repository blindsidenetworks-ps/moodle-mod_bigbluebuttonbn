<?php
/**
 * Ping the BigBlueButton server to see if the meeting is running
 *
 * @package   mod_bigbluebuttonbn
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2013-2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$meetingID = required_param('meetingid', PARAM_TEXT);
$callback = required_param('callback', PARAM_TEXT);
$id = optional_param('id', 0, PARAM_INT);

if (!$meetingID) {
    $error = 'You must specify a meetingID';
}

if (!$callback) {
    $error = 'This call must include a javascript callback';
}

header('Content-Type: application/json; charset=utf-8');
if ( !isset($error) ) {

    if (!isloggedin() && $PAGE->course->id == SITEID) {
        $userid = guest_user()->id;
    } else {
        $userid = $USER->id;
    }
    $hascourseaccess = ($PAGE->course->id == SITEID) || can_access_course($PAGE->course, $userid);
    	
    if( !$hascourseaccess ){
        header("HTTP/1.0 401 Unauthorized");
    } else {
        $url = trim(trim($CFG->bigbluebuttonbn_server_url),'/').'/';
        $shared_secret = trim($CFG->bigbluebuttonbn_shared_secret);

        try {
            $meeting_running = bigbluebuttonbn_meeting_running( $meetingID, $url, $shared_secret );
            if( $meeting_running  ) {
                ///log the join event
                if ( $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $id), '*', MUST_EXIST) ) {
                    $course = $DB->get_record('course', array('id' => $bigbluebuttonbn->course), '*', MUST_EXIST);
                    $cm = get_coursemodule_from_instance('bigbluebuttonbn', $bigbluebuttonbn->id, $course->id, false, MUST_EXIST);
                    /// Moodle event logger: Create an event for meeting joined
                    if ( $CFG->version < '2014051200' ) {
                        //This is valid before v2.7
                        add_to_log($course->id, 'bigbluebuttonbn', 'meeting joined', '', $bigbluebuttonbn->name, $cm->id);
                    } else {
                        //This is valid after v2.7
                        $context = context_module::instance($cm->id);
                        $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_meeting_joined::create(
                                array(
                                        'context' => $context,
                                        'objectid' => $bigbluebuttonbn->id
                                )
                        );
                        $event->trigger();
                    }
                }
            }
            echo $callback.'({ "status": "'.($meeting_running?'true':'false').'" });';
        } catch(Exception $e) {
            header("HTTP/1.0 502 Bad Gateway. ".$e->getMessage());
        }
    }
} else {
    header("HTTP/1.0 400 Bad Request. ".$error);
}

function bigbluebuttonbn_meeting_running($meetingID, $url, $shared_secret) {
    global $CFG;

    $meeting_running = false;
    $cache_ttl = $CFG->bigbluebuttonbn_waitformoderator_cache_ttl;

    $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'ping_cache');
    $result = $cache->get($meetingID);
    
    $now = time();
    if( isset($result) && $now < ($result['creation_time'] + $cache_ttl) ) {
        //Use the value in the cache
        error_log('Data taken from cache');
        $meeting_running = $result['meeting_running'];
    } else {
        //Ping again and refresh the cache
        error_log('Data taken from the ping response');
        $meeting_running = bigbluebuttonbn_isMeetingRunning( $meetingID, $url, $shared_secret );
        $cache->set($meetingID, array('creation_time' => time(), 'meeting_running' => $meeting_running));
    }
    return $meeting_running;
}