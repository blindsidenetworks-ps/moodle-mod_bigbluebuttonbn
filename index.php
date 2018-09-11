<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * View all BigBlueButton instances in this course.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2017 Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = required_param('id', PARAM_INT);
$a = optional_param('a', 0, PARAM_INT);
$g = optional_param('g', 0, PARAM_INT);

if (!$id) {
    print_error('You must specify a course_module ID or an instance ID');
    return;
}

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_login($course, true);

$context = context_course::instance($course->id);

// Print the header.
$PAGE->set_url('/mod/bigbluebuttonbn/index.php', array('id' => $id));
$PAGE->set_title(get_string('modulename', 'bigbluebuttonbn'));
$PAGE->set_heading($course->fullname);
$PAGE->set_cacheable(false);
$PAGE->set_pagelayout('incourse');

$PAGE->navbar->add(get_string('modulename', 'bigbluebuttonbn'), "index.php?id=$course->id");

// Get all the appropriate data.
if (!$bigbluebuttonbns = get_all_instances_in_course('bigbluebuttonbn', $course)) {
    notice('There are no instances of bigbluebuttonbn', "../../course/view.php?id=$course->id");
}

// Print the list of instances.
$timenow = time();
$strweek = get_string('week');
$strtopic = get_string('topic');
$headingname = get_string('index_heading_name', 'bigbluebuttonbn');
$headinggroup = get_string('index_heading_group', 'bigbluebuttonbn');
$headingusers = get_string('index_heading_users', 'bigbluebuttonbn');
$headingviewer = get_string('index_heading_viewer', 'bigbluebuttonbn');
$headingmoderator = get_string('index_heading_moderator', 'bigbluebuttonbn');
$headingactions = get_string('index_heading_actions', 'bigbluebuttonbn');
$headingrecording = get_string('index_heading_recording', 'bigbluebuttonbn');

$table = new html_table();
$table->head = array($strweek, $headingname, $headinggroup, $headingusers, $headingviewer, $headingmoderator,
    $headingrecording, $headingactions);
$table->align = array('center', 'left', 'center', 'center', 'center', 'center', 'center');

$submit = optional_param('submit', '', PARAM_TEXT);
if ($submit === 'end') {
    // A request to end the meeting.
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $a), '*', MUST_EXIST);
    if (!$bigbluebuttonbn) {
        print_error("BigBlueButton ID $a is incorrect");
    }
    $course = $DB->get_record('course', array('id' => $bigbluebuttonbn->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('bigbluebuttonbn', $bigbluebuttonbn->id, $course->id, false, MUST_EXIST);
    // User roles.
    $participantlist = bigbluebuttonbn_get_participant_list($bigbluebuttonbn, $context);
    $moderator = bigbluebuttonbn_is_moderator($context, $participantlist);
    $administrator = is_siteadmin();
    if ($moderator || $administrator) {
        bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_MEETING_ENDED, $bigbluebuttonbn, $cm);
        echo get_string('index_ending', 'bigbluebuttonbn');
        $meetingid = $bigbluebuttonbn->meetingid.'-'.$course->id.'-'.$bigbluebuttonbn->id;
        if ($g != '0') {
            $meetingid .= '['.$g.']';
        }

        bigbluebuttonbn_end_meeting($meetingid, $bigbluebuttonbn->moderatorpass);
        redirect('index.php?id='.$id);
    }
}

foreach ($bigbluebuttonbns as $bigbluebuttonbn) {
    if ($bigbluebuttonbn->visible) {
        $cm = get_coursemodule_from_id('bigbluebuttonbn', $bigbluebuttonbn->coursemodule, 0, false, MUST_EXIST);
        // User roles.
        $participantlist = bigbluebuttonbn_get_participant_list($bigbluebuttonbn, $context);
        $moderator = bigbluebuttonbn_is_moderator($context, $participantlist);
        $administrator = is_siteadmin();
        $canmoderate = ($administrator || $moderator);
        // Add a the data for the bigbluebuttonbn instance.
        $groupobj = null;
        if (groups_get_activity_groupmode($cm) > 0) {
            $groupobj = (object) array('id' => 0, 'name' => get_string('allparticipants'));
        }
        $table->data[] = bigbluebuttonbn_index_display_room($canmoderate, $course, $bigbluebuttonbn, $groupobj);
        // Add a the data for the groups belonging to the bigbluebuttonbn instance, if any.
        $groups = groups_get_activity_allowed_groups($cm);
        foreach ($groups as $group) {
            $table->data[] = bigbluebuttonbn_index_display_room($canmoderate, $course, $bigbluebuttonbn, $group);
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('index_heading', 'bigbluebuttonbn'));
echo html_writer::table($table);
echo $OUTPUT->footer();

/**
 * Displays the general view.
 *
 * @param boolean $moderator
 * @param object $course
 * @param object $bigbluebuttonbn
 * @param object $groupobj
 * @return array
 */
function bigbluebuttonbn_index_display_room($moderator, $course, $bigbluebuttonbn, $groupobj = null) {
    $meetingid = $bigbluebuttonbn->meetingid.'-'.$course->id.'-'.$bigbluebuttonbn->id;
    $paramgroup = '';
    $groupname = '';
    if ($groupobj) {
        $meetingid .= '['.$groupobj->id.']';
        $paramgroup = '&group='.$groupobj->id;
        $groupname = $groupobj->name;
    }
    $meetinginfo = bigbluebuttonbn_get_meeting_info_array($meetingid);
    if (empty($meetinginfo)) {
        // The server was unreachable.
        print_error(get_string('index_error_unable_display', 'bigbluebuttonbn'));
        return;
    }
    if (isset($meetinginfo['messageKey']) && $meetinginfo['messageKey'] == 'checksumError') {
        // There was an error returned.
        print_error(get_string('index_error_checksum', 'bigbluebuttonbn'));
        return;
    }
    // Output Users in the meeting.
    $joinurl = '<a href="view.php?id='.$bigbluebuttonbn->coursemodule.$paramgroup.'">'.format_string($bigbluebuttonbn->name).'</a>';
    $group = $groupname;
    $users = '';
    $viewerlist = '';
    $moderatorlist = '';
    $recording = '';
    $actions = '';
    // The meeting info was returned.
    if (array_key_exists('running', $meetinginfo) && $meetinginfo['running'] == 'true') {
        $users = bigbluebuttonbn_index_display_room_users($meetinginfo);
        $viewerlist = bigbluebuttonbn_index_display_room_users_attendee_list($meetinginfo, 'VIEWER');
        $moderatorlist = bigbluebuttonbn_index_display_room_users_attendee_list($meetinginfo, 'MODERATOR');
        $recording = bigbluebuttonbn_index_display_room_recordings($meetinginfo);
        $actions = bigbluebuttonbn_index_display_room_actions($moderator, $course, $bigbluebuttonbn, $groupobj);
    }
    return array($bigbluebuttonbn->section, $joinurl, $group, $users, $viewerlist, $moderatorlist, $recording, $actions);
}

/**
 * Count the number of users in the meeting.
 *
 * @param array $meetinginfo
 * @return integer
 */
function bigbluebuttonbn_index_display_room_users($meetinginfo) {
    $users = '';
    if (count($meetinginfo['attendees']) && count($meetinginfo['attendees']->attendee)) {
        $users = count($meetinginfo['attendees']->attendee);
    }
    return $users;
}

/**
 * Returns attendee list.
 *
 * @param array $meetinginfo
 * @param string $role
 * @return string
 */
function bigbluebuttonbn_index_display_room_users_attendee_list($meetinginfo, $role) {
    $attendeelist = '';
    if (count($meetinginfo['attendees']) && count($meetinginfo['attendees']->attendee)) {
        $attendeecount = 0;
        foreach ($meetinginfo['attendees']->attendee as $attendee) {
            if ($attendee->role == $role) {
                $attendeelist .= ($attendeecount++ > 0 ? ', ' : '').$attendee->fullName;
            }
        }
    }
    return $attendeelist;
}

/**
 * Returns indication of recording enabled.
 *
 * @param array $meetinginfo
 * @return string
 */
function bigbluebuttonbn_index_display_room_recordings($meetinginfo) {
    $recording = '';
    if (isset($meetinginfo['recording']) && $meetinginfo['recording'] === 'true') {
        // If it has been set when meeting created, set the variable on/off.
        $recording = get_string('index_enabled', 'bigbluebuttonbn');
    }
    return $recording;
}

/**
 * Returns room actions.
 *
 * @param boolean $moderator
 * @param object $course
 * @param object $bigbluebuttonbn
 * @param object $groupobj
 * @return string
 */
function bigbluebuttonbn_index_display_room_actions($moderator, $course, $bigbluebuttonbn, $groupobj = null) {
    $actions = '';
    if ($moderator) {
        $actions .= '<form name="form1" method="post" action="">'."\n";
        $actions .= '  <INPUT type="hidden" name="id" value="'.$course->id.'">'."\n";
        $actions .= '  <INPUT type="hidden" name="a" value="'.$bigbluebuttonbn->id.'">'."\n";
        if ($groupobj != null) {
            $actions .= '  <INPUT type="hidden" name="g" value="'.$groupobj->id.'">'."\n";
        }
        $actions .= '  <INPUT type="submit" name="submit" value="' .
            get_string('view_conference_action_end', 'bigbluebuttonbn') .
            '" class="btn btn-primary btn-sm" onclick="return confirm(\'' .
            get_string('index_confirm_end', 'bigbluebuttonbn') . '\')">' . "\n";
        $actions .= '</form>'."\n";
    }
    return $actions;
}
