<?php

/**
 * Join a BigBlueButton room
 *
 * Authors:
 *      Fred Dixon (ffdixon [at] blindsidenetworks [dt] org)
 *      Jesus Federico (jesus [at] blindsidenetworks [dt] org)
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2012 Blindside Networks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');


$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // bigbluebuttonbn instance ID

if ($id) {
    if (! $cm = get_coursemodule_from_id('bigbluebuttonbn', $id)) {
        error('Course Module ID was incorrect');
    }

    if (! $course = get_record('course', 'id', $cm->course)) {
        error('Course is misconfigured');
    }

    if (! $bigbluebuttonbn = get_record('bigbluebuttonbn', 'id', $cm->instance)) {
        error('Course module is incorrect');
    }

} else if ($a) {
    if (! $bigbluebuttonbn = get_record('bigbluebuttonbn', 'id', $a)) {
        error('Course module is incorrect');
    }
    if (! $course = get_record('course', 'id', $bigbluebuttonbn->course)) {
        error('Course is misconfigured');
    }
    if (! $cm = get_coursemodule_from_instance('bigbluebuttonbn', $bigbluebuttonbn->id, $course->id)) {
        error('Course Module ID was incorrect');
    }

} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);

//print_object( $context );
//
$moderator = has_capability('mod/bigbluebuttonbn:moderate', $context);
//print "###".$endmeeting."###";

//$join = has_capability('mod/bigbluebuttonbn:join', $context);
//print "###".$join."###";

//exit;

add_to_log($course->id, "bigbluebuttonbn", "join", "view.php?id=$cm->id", "$bigbluebuttonbn->id");
// add_to_log($course->id, "bigbluebuttonbn", "join meeting", "view.php?course=$course->id&id=$USER->id", "$USER->id" );

/// Print the page header
$strbigbluebuttonbns = get_string('modulenameplural', 'bigbluebuttonbn');
$strbigbluebuttonbn  = get_string('modulename', 'bigbluebuttonbn');

$navlinks = array();
$navlinks[] = array('name' => $strbigbluebuttonbns, 'link' => "index.php?id=$course->id", 'type' => 'activity');
$navlinks[] = array('name' => format_string($bigbluebuttonbn->name), 'link' => '', 'type' => 'activityinstance');

$navigation = build_navigation($navlinks);

print_header_simple(format_string($bigbluebuttonbn->name), '', $navigation, '', '', true,
update_module_button($cm->id, $course->id, $strbigbluebuttonbn), navmenu($course, $cm));

//
// bigbluebuttonbn Setup
//

$salt = trim($CFG->bigbluebuttonbnSecuritySalt);
$url = trim(trim($CFG->bigbluebuttonbnServerURL),'/').'/';
$logoutURL = $CFG->wwwroot;
$username = $USER->firstname.' '.$USER->lastname;
$userID = $USER->id;

//$modPW = get_field( 'bigbluebuttonbn', 'moderatorpass', 'name', $bigbluebuttonbn->name );
//$viewerPW = get_field( 'bigbluebuttonbn', 'attendeepass', 'name', $bigbluebuttonbn->name );

$modPW = $bigbluebuttonbn->moderatorpass;
$viewerPW = $bigbluebuttonbn->viewerpass;
	
if( $moderator ) {
	//
	// Join as Moderator
	//
	print "<br />".get_string('view_login_moderator', 'bigbluebuttonbn' )."<br /><br />";
	print "<center><img src='loading.gif' /></center>";
	
	$response = BigBlueButtonBN::createMeetingArray( $bigbluebuttonbn->name, $bigbluebuttonbn->meetingid, "", $modPW, $viewerPW, $salt, $url, $logoutURL );

	if (!$response) {
		// If the server is unreachable, then prompts the user of the necessary action
		error( 'Unable to join the meeting. Please check the url of the bigbluebuttonbn server AND check to see if the bigbluebuttonbn server is running.' );
	}

	if( $response['returncode'] == "FAILED" ) {
		// The meeting was not created
		if ($response['messageKey'] == "checksumError"){
			 error( get_string( 'index_checksum_error', 'bigbluebuttonbn' ));
		}
		else {
			error( $response['message'] );
		}
	}

	$joinURL = BigBlueButtonBN::joinURL($bigbluebuttonbn->meetingid, $username, $modPW, $salt, $url, $userID);
	redirect( $joinURL );

} else {
	//
	// Login as a viewer, check if we need to wait
	//

	// "Viewer";
	if( $bigbluebuttonbn->wait ) {
		// check if the session is running; if not, user is not allowed to join
		// print "MeeingID: #".$bigbluebuttonbn->meetingid."#<br>";
		$arr = BigBlueButtonBN::getMeetingInfoArray( $bigbluebuttonbn->meetingid, $modPW, $url, $salt );
		$joinURL = BigBlueButtonBN::joinURL( $bigbluebuttonbn->meetingid, $username, $viewerPW, $salt, $url, $userID);

		// print_object( $arr );
		// print "Is Meeting runnign: #".BigBlueButtonBN::isMeetingRunning( $bigbluebuttonbn->meetingid,  $url, $salt )."#<br>";
		// print "BBB";
		
		if( BigBlueButtonBN::isMeetingRunning( $bigbluebuttonbn->meetingid, $url, $salt ) == "true" ) {
			//
			// since the meeting is already running, we just join the session
			//
			print "<br />".get_string('view_login_viewer', 'bigbluebuttonbn' )."<br /><br />";
			print "<center><img src='loading.gif' /></center>";
			
			redirect( $joinURL );

		} else {
			print "<br />".get_string('view_wait', 'bigbluebuttonbn' )."<br /><br />";
			print '<center><img src="polling.gif"></center>';
		}
?>
<p></p>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
<script type="text/javascript" src="heartbeat.js"></script>
<!-- script type="text/javascript" src="md5.js"></script -->
<!-- script type="text/javascript" src="jquery.xml2json.js"></script -->
<script type="text/javascript" >
                        $(document).ready(function(){
                        $.jheartbeat.set({
                        url: "<?php echo $CFG->wwwroot ?>/mod/bigbluebuttonbn/test.php?name=<?echo $bigbluebuttonbn->meetingid; ?>",
                        delay: 5000
                        }, function() {
                                mycallback();
                        });
                        });
                function mycallback() {
                        // Not elegant, but works around a bug in IE8
                        var isMeeting = ($("#HeartBeatDIV").text().search("true")  > 0 );
                        if ( isMeeting ) {
                                window.location = "<?php echo $joinURL ?>";
                        }
                }
</script>
<?php
	} else {
	
	//
	// Join as Viewer, no wait check
	//

	print "<br />".get_string('view_login_viewer', 'bigbluebuttonbn' )."<br /><br />";
	print "<center><img src='loading.gif' /></center>";
	
	$response = BigBlueButtonBN::createMeetingArray( "" , $bigbluebuttonbn->meetingid, "", $modPW, $viewerPW, $salt, $url, $logoutURL );

	if (!$response) {
		// If the server is unreachable, then prompts the user of the necessary action
		error( 'Unable to join the meeting. Please contact your administrator.' );
	}

	if( $response['returncode'] == "FAILED" ) {
		// The meeting was not created
		if ($response['messageKey'] == "checksumError"){
			error( get_string( 'index_checksum_error', 'bigbluebuttonbn' ));
		}
		else {
			error( $response['message'] );
		}
	}

	$joinURL = BigBlueButtonBN::joinURL($bigbluebuttonbn->meetingid, $username, $viewerPW, $salt, $url, $userID);
	redirect( $joinURL );

	}
}

// Finish the page
print_footer($course);

?>
