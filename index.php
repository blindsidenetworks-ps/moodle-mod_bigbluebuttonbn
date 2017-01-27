<?php
/**
 * View all BigBlueButton instances in this course.
 * 
 * @package   mod_bigbluebuttonbn
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2010-2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/locallib.php');

$id = required_param('id', PARAM_INT); // Course Module ID, or
$a  = optional_param('a', 0, PARAM_INT); // bigbluebuttonbn instance ID
$g  = optional_param('g', 0, PARAM_INT); // group instance ID

if ($id) {
    $course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
} else {
    print_error('You must specify a course_module ID or an instance ID');
}

require_login($course, true);

$context = bigbluebuttonbn_get_context_course($course->id);

/// Print the header
$PAGE->set_url('/mod/bigbluebuttonbn/index.php', array('id'=>$id));
$PAGE->set_title(get_string('modulename', 'bigbluebuttonbn'));
$PAGE->set_heading($course->fullname);
$PAGE->set_cacheable(false);
$PAGE->set_pagelayout('incourse');

//$navigation = build_navigation(array('name' => get_string('modulename', 'bigbluebuttonbn'), 'link' => '', 'type' => 'activity'));
$PAGE->navbar->add(get_string('modulename', 'bigbluebuttonbn'), "index.php?id=$course->id");

/// Get all the appropriate data
if (!$bigbluebuttonbns = get_all_instances_in_course('bigbluebuttonbn', $course)) {
    notice('There are no instances of bigbluebuttonbn', "../../course/view.php?id=$course->id");
}

/// Print the list of instances
$timenow            = time();
$strweek            = get_string('week');
$strtopic           = get_string('topic');
$heading_name       = get_string('index_heading_name', 'bigbluebuttonbn');
$heading_group      = get_string('index_heading_group', 'bigbluebuttonbn');
$heading_users      = get_string('index_heading_users', 'bigbluebuttonbn');
$heading_viewer     = get_string('index_heading_viewer', 'bigbluebuttonbn');
$heading_moderator  = get_string('index_heading_moderator', 'bigbluebuttonbn');
$heading_actions    = get_string('index_heading_actions', 'bigbluebuttonbn');
$heading_recording  = get_string('index_heading_recording', 'bigbluebuttonbn');

$table = new html_table();

$table->head  = array($strweek, $heading_name, $heading_group, $heading_users, $heading_viewer, $heading_moderator, $heading_recording, $heading_actions);
$table->align = array('center', 'left', 'center', 'center', 'center', 'center', 'center');

$endpoint = bigbluebuttonbn_get_cfg_server_url();
$shared_secret = bigbluebuttonbn_get_cfg_shared_secret();
$logoutURL = $CFG->wwwroot;

$submit = optional_param('submit', '', PARAM_TEXT);
if ($submit === 'end') {
    //
    // A request to end the meeting
    //
    if (!$bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $a), '*', MUST_EXIST)) {
        print_error("BigBlueButton ID $a is incorrect");
    }
    $course = $DB->get_record('course', array('id' => $bigbluebuttonbn->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('bigbluebuttonbn', $bigbluebuttonbn->id, $course->id, false, MUST_EXIST);

    //User roles
    if ($bigbluebuttonbn->participants == null || $bigbluebuttonbn->participants == "" || $bigbluebuttonbn->participants == "[]") {
        //The room that is being used comes from a previous version
        $moderator = has_capability('mod/bigbluebuttonbn:moderate', $context);
    } else {
        $moderator = bigbluebuttonbn_is_moderator($USER->id, get_user_roles($context, $USER->id, true), $bigbluebuttonbn->participants);
    }
    $administrator = has_capability('moodle/category:manage', $context);

    if ($moderator || $administrator) {
        bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_MEETING_ENDED, $bigbluebuttonbn, $context, $cm);

        echo get_string('index_ending', 'bigbluebuttonbn');

        $meetingID = $bigbluebuttonbn->meetingid . '-' . $course->id . '-' . $bigbluebuttonbn->id;
        $modPW = $bigbluebuttonbn->moderatorpass;
        if ($g != '0') {
            $getArray = bigbluebuttonbn_wrap_xml_load_file(bigbluebuttonbn_getEndMeetingURL($meetingID . '[' . $g . ']', $modPW, $endpoint, $shared_secret));
        } else {
            $getArray = bigbluebuttonbn_wrap_xml_load_file(bigbluebuttonbn_getEndMeetingURL($meetingID, $modPW, $endpoint, $shared_secret));
        }
	   redirect('index.php?id=' . $id);
    }
}

foreach ($bigbluebuttonbns as $bigbluebuttonbn) {
    $cm = get_coursemodule_from_id('bigbluebuttonbn', $bigbluebuttonbn->coursemodule, 0, false, MUST_EXIST);

    //User roles
    if ($bigbluebuttonbn->participants == null || $bigbluebuttonbn->participants == "" || $bigbluebuttonbn->participants == "[]") {
        //The room that is being used comes from a previous version
        $moderator = has_capability('mod/bigbluebuttonbn:moderate', $context);
    } else {
        $moderator = bigbluebuttonbn_is_moderator($USER->id, get_user_roles($context, $USER->id, true), $bigbluebuttonbn->participants);
    }
    $administrator = has_capability('moodle/category:manage', $context);

    if (groups_get_activity_groupmode($cm) > 0) {
        $table->data[] = displayBigBlueButtonRooms($endpoint, $shared_secret, ($administrator || $moderator), $course, $bigbluebuttonbn, (object)array('id'=>0, 'name'=>get_string('allparticipants')));
        $groups = groups_get_activity_allowed_groups($cm);
        if (isset($groups)) {
            foreach ($groups as $group) {
                $table->data[] = displayBigBlueButtonRooms($endpoint, $shared_secret, ($administrator || $moderator), $course, $bigbluebuttonbn, $group);
            }
        }
    } else {
        $table->data[] = displayBigBlueButtonRooms($endpoint, $shared_secret, ($administrator || $moderator), $course, $bigbluebuttonbn);
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('index_heading', 'bigbluebuttonbn'));
echo html_writer::table($table);

echo $OUTPUT->footer();

/// Functions
function displayBigBlueButtonRooms($endpoint, $shared_secret, $moderator, $course, $bigbluebuttonbn, $groupObj = null) {
    $joinURL = null;
    $group = "-";
    $users = "-";
    $running = "-";
    $actions = "-";
    $viewerList = "-";
    $moderatorList = "-";
    $recording = "-";

    if (!$bigbluebuttonbn->visible) {
        // Nothing to do
    } else {
        $modPW = $bigbluebuttonbn->moderatorpass;
        $attPW = $bigbluebuttonbn->viewerpass;

        $meetingID = $bigbluebuttonbn->meetingid . '-' . $course->id . '-' . $bigbluebuttonbn->id;
        //
        // Output Users in the meeting
        //
        if ($groupObj == null) {
            $meetingInfo = bigbluebuttonbn_getMeetingInfoArray($meetingID, $modPW, $endpoint, $shared_secret);
            $joinURL = '<a href="view.php?id=' . $bigbluebuttonbn->coursemodule . '">' . format_string($bigbluebuttonbn->name) . '</a>';
        } else {
            $meetingInfo = bigbluebuttonbn_getMeetingInfoArray($meetingID . '[' . $groupObj->id . ']', $modPW, $endpoint, $shared_secret);
            $joinURL = '<a href="view.php?id=' . $bigbluebuttonbn->coursemodule . '&group=' . $groupObj->id . '">' . format_string($bigbluebuttonbn->name) . '</a>';
            $group = $groupObj->name;
        }
        
        if (!$meetingInfo) {
            //
            // The server was unreachable
            //
            print_error(get_string('index_error_unable_display', 'bigbluebuttonbn'));
            return;
        }

        if (isset($meetingInfo['messageKey'])) {
            //
            // There was an error returned
            //
            if ($meetingInfo['messageKey'] == "checksumError") {
                print_error(get_string('index_error_checksum', 'bigbluebuttonbn'));
                return;
            }

            if ($meetingInfo['messageKey'] == "notFound" || $meetingInfo['messageKey'] == "invalidMeetingId") {
                //
                // The meeting does not exist yet on the BigBlueButton server.  This is OK.
                //
            } else {
                //
                // There was an error
                //
                $users = $meetingInfo['messageKey'] . ": " . $meetingInfo['message'];
            }
        } else {
            //
            // The meeting info was returned
            //
            if ($meetingInfo['running'] == 'true') {
                if ($moderator) {
                    if ($groupObj == null) {
                        $actions = '<form name="form1" method="post" action=""><INPUT type="hidden" name="id" value="' . $course->id . '"><INPUT type="hidden" name="a" value="' . $bigbluebuttonbn->id . '"><INPUT type="submit" name="submit" value="end" onclick="return confirm(\'' . get_string('index_confirm_end', 'bigbluebuttonbn') . '\')"></form>';
                    } else {
                        $actions = '<form name="form1" method="post" action=""><INPUT type="hidden" name="id" value="' . $course->id . '"><INPUT type="hidden" name="a" value="' . $bigbluebuttonbn->id . '"><INPUT type="hidden" name="g" value="' . $groupObj->id . '"><INPUT type="submit" name="submit" value="end" onclick="return confirm(\'' . get_string('index_confirm_end', 'bigbluebuttonbn') . '\')"></form>';
                    }
                }
                if (isset($meetingInfo['recording']) && $meetingInfo['recording'] == 'true') { // if it has been set when meeting created, set the variable on/off
                    $recording = get_string('index_enabled', 'bigbluebuttonbn');
                }
                 
                $xml = $meetingInfo['attendees'];
                if (count($xml) && count($xml->attendee)) {
                    $users = count($xml->attendee);
                    $viewer_count = 0;
                    $moderator_count = 0;
                    foreach ($xml->attendee as $attendee) {
                        if ($attendee->role == "MODERATOR") {
                            if ($viewer_count++ > 0) {
                                $moderatorList .= ", ";
                            } else {
                                $moderatorList = "";
                            }
                            $moderatorList .= $attendee->fullName;
                        } else {
                            if ($moderator_count++ > 0) {
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

        return array($bigbluebuttonbn->section, $joinURL, $group, $users, $viewerList, $moderatorList, $recording, $actions);
    }
}