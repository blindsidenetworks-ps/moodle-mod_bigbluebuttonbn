<?php
/**
 * Ping the BigBlueButton server to see if the meeting is running
 *
 * Authors:
 *    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)    
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2012 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or

$callback = optional_param('callback', "", PARAM_TEXT);
$meetingID = optional_param('meetingid', 0, PARAM_TEXT);

if ($id) {
    if ( ! $cm = get_coursemodule_from_id('bigbluebuttonbn', $id, 0, false, MUST_EXIST) ) {
        $error = 'Course Module ID was incorrect';
    }

    if ( ! $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST) ) {
        $error = 'Course is misconfigured';
    }

    if ( ! $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $cm->instance), '*', MUST_EXIST) ) {
        $error = 'Course module is incorrect';
    }
    
    if (!$meetingID) {
    	$error = 'You must specify a meetingID';
    }

    if (!$callback) {
    	$error = 'This call must include a javascript callback';
    }
    
} else {
    $error = 'You must specify a course_module ID';
}


//header('Content-Type: text/plainjson; charset=utf-8');
if ( !$error ) {
	
	if (!isloggedin() && $PAGE->course->id == SITEID) {
		$userid = guest_user()->id;
	} else {
		$userid = $USER->id;
	}
    $hascourseaccess = ($PAGE->course->id == SITEID) || can_access_course($PAGE->course, $userid);
			
	if( !$hascourseaccess ){
		echo "Unauthorized";
		//header("HTTP/1.0 401 Unauthorized");
		//http_response_code(401);
	} else {
		$salt = trim($CFG->BigBlueButtonBNSecuritySalt);
		$url = trim(trim($CFG->BigBlueButtonBNServerURL),'/').'/';
				
		echo "Hello";
		try{
			$ismeetingrunning = bigbluebuttonbn_isMeetingRunning( $meetingID, $url, $salt );
			print $ismeetingrunning;
		}catch(Exception $e){
			echo $e;
		}
		//echo $ismeetingrunning;
		//echo $callback.'({ "status": "'.$ismeetingrunning.'" });';
				
	}

} else {
	echo $error;
	//header("HTTP/1.0 400 Bad Request. ".$error);
	//http_response_code(400);
}