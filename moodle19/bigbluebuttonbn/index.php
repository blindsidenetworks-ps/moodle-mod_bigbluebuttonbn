<?php
/**
 * View all BigBlueButtonBN instances in this course.
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


$id = required_param('id', PARAM_INT);    // Course Module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // bigbluebuttonbn instance ID


if (! $course = get_record('course', 'id', $id)) {
    error('Course ID is incorrect');
}

require_course_login($course);


$coursecontext = get_context_instance(CONTEXT_COURSE, $id);
$moderator = has_capability('mod/bigbluebuttonbn:moderate', $coursecontext);

add_to_log($course->id, 'bigbluebuttonbn', 'view all', "index.php?id=$course->id", '');


/// Get all required stringsbigbluebuttonbn

$strbigbluebuttonbns = get_string('modulenameplural', 'bigbluebuttonbn');
$strbigbluebuttonbn  = get_string('modulename', 'bigbluebuttonbn');


/// Print the header

$navlinks = array();
$navlinks[] = array('name' => $strbigbluebuttonbns, 'link' => '', 'type' => 'activity');
$navigation = build_navigation($navlinks);

print_header_simple($strbigbluebuttonbns, '', $navigation, '', '', true, '', navmenu($course));

/// Get all the appropriate data

if (! $bigbluebuttonbns = get_all_instances_in_course('bigbluebuttonbn', $course)) {
    notice('There are no instances of bigbluebuttonbn', "../../course/view.php?id=$course->id");
}

/// Print the list of instances (your module will probably extend this)

$timenow  = time();
$strweek  = get_string('week');
$strtopic = get_string('topic');
$heading_name = get_string('index_header_name', 'bigbluebuttonbn' );
$heading_users = get_string('index_heading_users', 'bigbluebuttonbn');
$heading_viewer = get_string('index_heading_viewer', 'bigbluebuttonbn');
$heading_moderator = get_string('index_heading_moderator', 'bigbluebuttonbn' );
$heading_actions = get_string('index_heading_actions', 'bigbluebuttonbn' );


if ($course->format == 'weeks') {
    $table->head  = array ($strweek, $heading_name, $heading_users, $heading_viewer, $heading_moderator, $heading_actions);
    $table->align = array ('center', 'center', 'center', 'center', 'center',  'center' );
} else if ($course->format == 'topics') {
    $table->head  = array ($strtopic, $strname);
    $table->align = array ('center', 'left', 'left', 'left');
} else {
    $table->head  = array ($strname);
    $table->align = array ('left', 'left', 'left');
}


$salt = trim($CFG->BigBlueButtonSecuritySalt);
$url = trim(trim($CFG->BigBlueButtonServerURL),'/').'/';
$logoutURL = $CFG->wwwroot;

if( isset($_POST['submit']) && $_POST['submit'] == 'end' ) { 
	//
	// A request to end the meeting
	//
	if (! $bigbluebuttonbn = get_record('bigbluebuttonbn', 'id', $a)) {
        	error("BigBlueButton ID $a is incorrect");
	}
	print get_string('index_ending', 'bigbluebuttonbn');

	$meetingID = $bigbluebuttonbn->meetingid;
	$modPW = $bigbluebuttonbn->moderatorpass;

	$getArray = BigBlueButtonBN::endMeeting( $meetingID, $modPW, $url, $salt );
	// print_object( $getArray );
	$bigbluebuttonbn->meetingid = bigbluebuttonbn_rand_string( 16 );
	if (! update_record('bigbluebuttonbn', $bigbluebuttonbn) ) {
		notice( "Unable to assign a new meetingid" );
	} else {
		redirect('index.php?id='.$id);
	}
}

// print_object( $bigbluebuttonbns );

foreach ($bigbluebuttonbns as $bigbluebuttonbn) {
	$info = null;
	$joinURL = null;
	$user = null;
	$result = null;
	$users = "-";
	$running = "-";
	$actions = "-";
	$viewerList = "-";
	$moderatorList = "-";
		
	// print_object( $bigbluebuttonbn );

    if (!$bigbluebuttonbn->visible) {
    	// Nothing to do
    } else {
		$modPW = get_field( 'bigbluebuttonbn', 'moderatorpass', 'name', $bigbluebuttonbn->name );
		$attPW = get_field( 'bigbluebuttonbn', 'viewerpass',  'name', $bigbluebuttonbn->name );

		// print "## $modPW ##";

		$joinURL = '<a href="view.php?id='.$bigbluebuttonbn->coursemodule.'">'.format_string($bigbluebuttonbn->name).'</a>';
		// $status = $bigbluebuttonbn->meetingid;

		//echo "XX";

		//
		// Output Users in the meeting
		//
		$getArray = BigBlueButtonBN::getMeetingInfoArray( $bigbluebuttonbn->meetingid, $modPW, $url, $salt );

		// print_object( $getArray );

		if (!$getArray) {
			//
			// The server was unreachable
			//
			error( get_string( 'index_unable_display', 'bigbluebuttonbn' ));
			return;
		}

		if (isset($getArray['messageKey'])) {
			//
			// There was an error returned
			//
			if ($info['messageKey'] == "checksumError") {
				error( get_string( 'index_checksum_error', 'bigbluebuttonbn' ));
				return;
			}

			if ($getArray['messageKey'] == "notFound" ) {
				//
				// The meeting does not exist yet on the BigBlueButton server.  This is OK.
				//
			} else {
				//
				// There was an error
				//
				$users = $getArray['messageKey'].": ".$info['message'];
			}
		} else {

			//
			// The meeting info was returned
			//
			if ($getArray['running'] == 'true') {
				//$status =  get_string('index_running', 'bigbluebuttonbn' );
				
				if ( $moderator ) {
					$actions = '<form name="form1" method="post" action=""><INPUT type="hidden" name="id" value="'.$id.'"><INPUT type="hidden" name="a" value="'.$bigbluebuttonbn->id.'"><INPUT type="submit" name="submit" value="end" onclick="return confirm(\''. get_string('index_confirm_end', 'bigbluebuttonbn' ).'\')"></form>';
				}

				$xml = $getArray['attendees'];
				if (count( $xml ) && count( $xml->attendee ) ) {
					$users = count( $xml->attendee );
					$viewer_count = 0;
					$moderator_count = 0;
					foreach ( $xml->attendee as $attendee ) {
						if ($attendee->role == "MODERATOR" ) {
							if ( $viewer_count++ > 0 ) {
								$moderatorList .= ", ";
							} else {
								$moderatorList = "";
							}
							$moderatorList .= $attendee->fullName;
						} else {
							if ( $moderator_count++ > 0 ) {
								$viewerList .= ", ";
							} else {
								$viewerList = "";
							}
							$viewerList .= $attendee->fullName;
						}
					}
				}
			}
		}
	}

	if ($course->format == 'weeks' or $course->format == 'topics' ) {
		$table->data[] = array ($bigbluebuttonbn->section, $joinURL, $users, $viewerList, $moderatorList, $actions );
	} else {
		$table->data[] = array ($bigbluebuttonbn->section, $joinURL, $users, $viewerList, $moderatorList, $actions );
	}
}

print_heading($strbigbluebuttonbns);
print_table($table);

print_footer($course);

?>
