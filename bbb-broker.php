<?php //Buld the <head>

/**
 * View and administrate BigBlueButton playback recordings
 *
 * Authors:
 *    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)    
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2011-2012 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$moderator = false;

$cid = optional_param('cid', 0, PARAM_INT);
if ($cid) {
    $course = $DB->get_record('course', array('id'=>$cid), '*', MUST_EXIST);
    require_login($course, true);

    $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
    $moderator = has_capability('mod/bigbluebuttonbn:moderate', $coursecontext);
}

$action = optional_param('action', 0, PARAM_TEXT); 
if(!$action) $action="version";

$salt = trim($CFG->BigBlueButtonBNSecuritySalt);
$url = trim(trim($CFG->BigBlueButtonBNServerURL),'/').'/';

switch ($action) {
    case "publish":
        $recordingID = optional_param('recordingid', 0, PARAM_TEXT);
        if( !$moderator ) {
            echo "Action not allowed";
        } else if( $recordingID ){
            if (bigbluebuttonbn_doPublishRecordings($recordingID, 'true', $url, $salt)) {
                echo "Success";
            } else {
                echo "Failed";
            }
        } else {
            echo "Missing recordingid";
        }
        break;
    case "unpublish":
        $recordingID = optional_param('recordingid', 0, PARAM_TEXT);
        if( !$moderator ) {
            echo "Action not allowed";
        } else if( $recordingID ){
           	if (bigbluebuttonbn_doPublishRecordings($recordingID, 'false', $url, $salt)) {
                echo "Success";
        	} else {
                echo "Failed";
        	}
        } else {
            echo "Missing recordingid";
        }	
        break;
    case "delete":
        $recordingID = optional_param('recordingid', 0, PARAM_TEXT);
        if( !$moderator ) {
            echo "Action not allowed";
        } else if( $recordingID ){
           	if (bigbluebuttonbn_doDeleteRecordings($recordingID, $url, $salt)) {
                echo "Success";
        	} else {
                echo "Failed";
        	}
        } else {
            echo "Missing recordingid";
        }	
        break;
    case "ping":
        $meetingID = optional_param('meetingID', 0, PARAM_TEXT);
        if( $meetingID ){
            echo bigbluebuttonbn_getMeetingXML( $meetingID, $url, $salt );
        } else {
            echo 'false';
        }	
        break;
    case "test":
        $meetingID = optional_param('meetingID', 0, PARAM_TEXT);
        if( $meetingID ){
            if(bigbluebuttonbn_isMeetingRunning( $meetingID, $url, $salt ))
                echo 'true';
            else
                echo 'false';
                
        } else {
            echo 'false';
        }	
        break;
    default:
        echo bigbluebuttonbn_getServerVersion($url);
}


?>
