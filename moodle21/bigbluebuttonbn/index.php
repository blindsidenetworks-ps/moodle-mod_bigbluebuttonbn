<?php
/**
 * View all BigBlueButton instances in this course.
 * 
 * Authors:
 * 	Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 *      Jesus Federico  (jesus [at] blindsidenetworks [dt] com)    
 * 
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2011 Blindside Networks 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once('../../config.php');
require_once('lib.php');


$id = required_param('id', PARAM_INT);		// Course Module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // bigbluebuttonbn instance ID

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
require_course_login($course, true);

$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
$moderator = has_capability('mod/bigbluebuttonbn:moderate', $coursecontext);

add_to_log($course->id, 'bigbluebuttonbn', 'view all', "index.php?id=$course->id", '');


/// Get all required stringsbigbluebuttonbn

$strbigbluebuttonbns = get_string('modulenameplural', 'bigbluebuttonbn');
$strbigbluebuttonbn  = get_string('modulename', 'bigbluebuttonbn');

$PAGE->set_pagelayout('incourse');

/// Print the header

$navlinks = array();
$navlinks[] = array('name' => $strbigbluebuttonbns, 'link' => '', 'type' => 'activity');
$navigation = build_navigation($navlinks);

$PAGE->set_url('/mod/bigbluebuttonbn/index.php', array('id'=>$id));
$PAGE->navbar->add($strbigbluebuttonbns, "index.php?id=$course->id");
$PAGE->set_title($strbigbluebuttonbns);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

// print_header_simple($strbigbluebuttonbns, '', $navigation, '', '', true, '', navmenu($course));

/// Get all the appropriate data

if (! $bigbluebuttonbns = get_all_instances_in_course('bigbluebuttonbn', $course)) {
    notice('There are no instances of bigbluebuttonbn', "../../course/view.php?id=$course->id");
}

/// Print the list of instances (your module will probably extend this)

$timenow            = time();
$strweek            = get_string('week');
$strtopic           = get_string('topic');
$heading_name       = get_string('index_header_name', 'bigbluebuttonbn' );
$heading_users      = get_string('index_heading_users', 'bigbluebuttonbn');
$heading_viewer     = get_string('index_heading_viewer', 'bigbluebuttonbn');
$heading_moderator  = get_string('index_heading_moderator', 'bigbluebuttonbn' );
$heading_actions    = get_string('index_heading_actions', 'bigbluebuttonbn' );
$heading_recording  = get_string('index_heading_recording', 'bigbluebuttonbn' );


$table = new html_table();

if ($course->format == 'weeks') {
    $table->head  = array ($strweek, $heading_name, $heading_users, $heading_viewer, $heading_moderator, $heading_recording, $heading_actions );
    $table->align = array ('center', 'center', 'center', 'center', 'center',  'center', 'center' );
} else if ($course->format == 'topics') {
    $table->head  = array ($strtopic, $strname);
    $table->align = array ('center', 'left', 'left', 'left');
} else {
    $table->head  = array ($strname);
    $table->align = array ('left', 'left', 'left');
}


$salt = trim($CFG->BigBlueButtonBNSecuritySalt);
$url = trim(trim($CFG->BigBlueButtonBNServerURL),'/').'/';
$logoutURL = $CFG->wwwroot;

if( isset($_POST['submit']) && $_POST['submit'] == 'end' ) { 
	//
	// A request to end the meeting
	//
	if (! $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id'=>$a))) {
        	print_error("BigBlueButton ID $a is incorrect");
	}
	echo get_string('index_ending', 'bigbluebuttonbn');

	$meetingID = $bigbluebuttonbn->meetingid;
	$modPW = $bigbluebuttonbn->moderatorpass;

	$getArray = BigBlueButtonBN::endMeeting( $meetingID, $modPW, $url, $salt );
	redirect('index.php?id='.$id);
}

// print_object( $bigbluebuttonbns );

foreach ($bigbluebuttonbns as $bigbluebuttonbn) {
    $joinURL = null;
    $user = null;
    $result = null;
    $users = "-";
    $running = "-";
    $actions = "-";
    $viewerList = "-";
    $moderatorList = "-";
    $recording = "-";
		
    //print_object( $bigbluebuttonbn );

    if ( !$bigbluebuttonbn->visible ) {
    	// Nothing to do
    } else {
	$modPW = $bigbluebuttonbn->moderatorpass;
	$attPW = $bigbluebuttonbn->viewerpass;

	$joinURL = '<a href="view.php?id='.$bigbluebuttonbn->coursemodule.'">'.format_string($bigbluebuttonbn->name).'</a>';

	//
	// Output Users in the meeting
	//
	$getArray = BigBlueButtonBN::getMeetingInfoArray( $bigbluebuttonbn->meetingid, $modPW, $url, $salt );

	//echo $bigbluebuttonbn->meetingid;
	//print_object( $getArray );

	if (!$getArray) {
            //
            // The server was unreachable
            //
            print_error( get_string( 'index_error_unable_display', 'bigbluebuttonbn' ));
            // print_error( 'Unable to display the meetings. Please check the url of the bigbluebuttonbn server AND check to see if the bigbluebuttonbn server is running.' );
            return;
	}

	if (isset($getArray['messageKey'])) {
            //
            // There was an error returned
            //
            if ($getArray['messageKey'] == "checksumError") {
		print_error( get_string( 'index_error_checksum', 'bigbluebuttonbn' ));
		return;
            }

            if ($getArray['messageKey'] == "notFound" || $getArray['messageKey'] == "invalidMeetingId") {
		//
		// The meeting does not exist yet on the BigBlueButton server.  This is OK.
		//
            } else {
		//
		// There was an error
		//
                $users = $getArray['messageKey'].": ".$getArray['message'];
            }
	} else {

            //
            // The meeting info was returned
            //
            if ($getArray['running'] == 'true') {
		// $status =  get_string('index_running', 'bigbluebuttonbn' );
			
		if ( $moderator ) {
                    $actions = '<form name="form1" method="post" action=""><INPUT type="hidden" name="id" value="'.$id.'"><INPUT type="hidden" name="a" value="'.$bigbluebuttonbn->id.'"><INPUT type="submit" name="submit" value="end" onclick="return confirm(\''. get_string('index_confirm_end', 'bigbluebuttonbn' ).'\')"></form>';
		}
                
                if ( isset($getArray['metadata']->recording) && $getArray['metadata']->recording == 'true' ) // if it has been set when meeting created, set the variable on/off
                    $recording = get_string('index_enabled', 'bigbluebuttonbn' );

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
        
        if ($course->format == 'weeks' or $course->format == 'topics' ) {
            $table->data[] = array ($bigbluebuttonbn->section, $joinURL, $users, $viewerList, $moderatorList, $recording, $actions );
        } else {
            $table->data[] = array ($bigbluebuttonbn->section, $joinURL, $users, $viewerList, $moderatorList, $recording, $actions );
        }
        
    }

}

echo $OUTPUT->heading(get_string('index_heading', 'bigbluebuttonbn'));
echo html_writer::table($table);
echo $OUTPUT->footer();

?>
