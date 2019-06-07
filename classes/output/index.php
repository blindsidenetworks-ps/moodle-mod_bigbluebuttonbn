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
 * Renderer.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Darko Miletic  (darko.miletic [at] gmail [dt] com)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\output;

use renderable;
use html_table;
use html_writer;
use stdClass;
use coding_exception;
use mod_bigbluebuttonbn\plugin;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/bigbluebuttonbn/locallib.php');

/**
 * Class index
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Darko Miletic  (darko.miletic [at] gmail [dt] com)
 */
class index implements renderable {

    /** @var html_table */
    public $table = null;

    /**
     * index constructor.
     * @param  stdClass $course
     * @throws coding_exception
     */
    public function __construct($course) {
        global $PAGE;

        // Get all the appropriate data.
        if (!$bigbluebuttonbns = get_all_instances_in_course('bigbluebuttonbn', $course)) {
            notice(
                get_string('index_error_noinstances', plugin::COMPONENT),
                plugin::necurl('/course/view.php', ['id' => $course->id])
            );
        }

        // Print the list of instances.
        $strweek = get_string('week');
        $headingname = get_string('index_heading_name', plugin::COMPONENT);
        $headinggroup = get_string('index_heading_group', plugin::COMPONENT);
        $headingusers = get_string('index_heading_users', plugin::COMPONENT);
        $headingviewer = get_string('index_heading_viewer', plugin::COMPONENT);
        $headingmoderator = get_string('index_heading_moderator', plugin::COMPONENT);
        $headingactions = get_string('index_heading_actions', plugin::COMPONENT);
        $headingrecording = get_string('index_heading_recording', plugin::COMPONENT);

        $table = new html_table();
        $table->head = array($strweek, $headingname, $headinggroup, $headingusers, $headingviewer, $headingmoderator,
            $headingrecording, $headingactions);
        $table->align = array('center', 'left', 'center', 'center', 'center', 'center', 'center');

        foreach ($bigbluebuttonbns as $bigbluebuttonbn) {
            if ($bigbluebuttonbn->visible) {
                $cm = get_coursemodule_from_id('bigbluebuttonbn', $bigbluebuttonbn->coursemodule, 0, false, MUST_EXIST);
                // User roles.
                $participantlist = bigbluebuttonbn_get_participant_list($bigbluebuttonbn, $PAGE->context);
                $moderator = bigbluebuttonbn_is_moderator($PAGE->context, $participantlist);
                $administrator = is_siteadmin();
                $canmoderate = ($administrator || $moderator);
                // Add a the data for the bigbluebuttonbn instance.
                $groupobj = null;
                if (groups_get_activity_groupmode($cm) > 0) {
                    $groupobj = (object) array('id' => 0, 'name' => get_string('allparticipants'));
                }
                $table->data[] = self::bigbluebuttonbn_index_display_room($canmoderate, $course, $bigbluebuttonbn, $groupobj);
                // Add a the data for the groups belonging to the bigbluebuttonbn instance, if any.
                $groups = groups_get_activity_allowed_groups($cm);
                foreach ($groups as $group) {
                    $table->data[] = self::bigbluebuttonbn_index_display_room($canmoderate, $course, $bigbluebuttonbn, $group);
                }
            }
        }

        $this->table = $table;
    }

    /**
     * Displays the general view.
     *
     * @param boolean $moderator
     * @param object $course
     * @param object $bigbluebuttonbn
     * @param object $groupobj
     * @return array
     */
    public static function bigbluebuttonbn_index_display_room($moderator, $course, $bigbluebuttonbn, $groupobj = null) {
        $meetingid = sprintf('%s-%d-%d', $bigbluebuttonbn->meetingid, $course->id, $bigbluebuttonbn->id);
        $groupname = '';
        $urlparams = ['id' => $bigbluebuttonbn->coursemodule];
        if ($groupobj) {
            $meetingid .= sprintf('[%d]', $groupobj->id);
            $urlparams['group'] = $groupobj->id;
            $groupname = $groupobj->name;
        }
        $meetinginfo = bigbluebuttonbn_get_meeting_info_array($meetingid);
        if (empty($meetinginfo)) {
            // The server was unreachable.
            print_error('index_error_unable_display', plugin::COMPONENT);
        }
        if (isset($meetinginfo['messageKey']) && ($meetinginfo['messageKey'] == 'checksumError')) {
            // There was an error returned.
            print_error('index_error_checksum', plugin::COMPONENT);
        }
        // Output Users in the meeting.
        $joinurl = html_writer::link(
            plugin::necurl('/mod/bigbluebuttonbn/view.php', $urlparams),
            format_string($bigbluebuttonbn->name)
        );
        $group = $groupname;
        $users = '';
        $viewerlist = '';
        $moderatorlist = '';
        $recording = '';
        $actions = '';
        // The meeting info was returned.
        if (array_key_exists('running', $meetinginfo) && $meetinginfo['running'] == 'true') {
            $users = self::bigbluebuttonbn_index_display_room_users($meetinginfo);
            $viewerlist = self::bigbluebuttonbn_index_display_room_users_attendee_list($meetinginfo, 'VIEWER');
            $moderatorlist = self::bigbluebuttonbn_index_display_room_users_attendee_list($meetinginfo, 'MODERATOR');
            $recording = self::bigbluebuttonbn_index_display_room_recordings($meetinginfo);
            $actions = self::bigbluebuttonbn_index_display_room_actions($moderator, $course, $bigbluebuttonbn, $groupobj);
        }
        return array($bigbluebuttonbn->section, $joinurl, $group, $users, $viewerlist, $moderatorlist, $recording, $actions);
    }

    /**
     * Count the number of users in the meeting.
     *
     * @param array $meetinginfo
     * @return integer
     */
    public static function bigbluebuttonbn_index_display_room_users($meetinginfo) {
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
    public static function bigbluebuttonbn_index_display_room_users_attendee_list($meetinginfo, $role) {
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
    public static function bigbluebuttonbn_index_display_room_recordings($meetinginfo) {
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
    public static function bigbluebuttonbn_index_display_room_actions($moderator, $course, $bigbluebuttonbn, $groupobj = null) {
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
}