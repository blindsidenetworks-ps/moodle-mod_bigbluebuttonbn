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
 * Renderer for the Index page.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Darko Miletic  (darko.miletic [at] gmail [dt] com)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\output;

use coding_exception;
use html_table;
use html_writer;
use mod_bigbluebuttonbn\local\helpers\roles;
use mod_bigbluebuttonbn\local\helpers\meeting;
use mod_bigbluebuttonbn\plugin;
use moodle_exception;
use renderable;
use renderer_base;
use stdClass;

/**
 * Class index
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class index implements renderable {

    /** @var stdClass */
    protected $course;

    /** @var stdClass[] */
    protected $instances;

    /**
     * Constructor for the index renderable.
     *
     * @param stdClass $course
     * @param stdClass[] List of bbbbn instances
     * @throws coding_exception
     */
    public function __construct(stdClass $course, array $instances) {
        $this->course = $course;
        $this->instances = $instances;
    }

    /**
     * Get the table for the index page.
     *
     * @param renderer_base $output
     * @return html_table
     */
    public function get_table(renderer_base $output): html_table {
        // Print the list of instances.
        $table = new html_table();
        $table->head = [
            get_string('week'),
            get_string('index_heading_name', plugin::COMPONENT),
            get_string('index_heading_group', plugin::COMPONENT),
            get_string('index_heading_users', plugin::COMPONENT),
            get_string('index_heading_viewer', plugin::COMPONENT),
            get_string('index_heading_moderator', plugin::COMPONENT),
            get_string('index_heading_recording', plugin::COMPONENT),
            get_string('index_heading_actions', plugin::COMPONENT),
        ];
        $table->align = ['center', 'left', 'center', 'center', 'center', 'center', 'center'];

        foreach ($this->instances as $instance) {
            $this->add_instance_to_table($output, $table, $instance);
        }

        return $table;
    }

    /**
     * Add details of the bigbluebuttonbn instance to the table.
     *
     * @param renderer_base $output
     * @param html_table $table
     * @param stdClass $instance
     */
    protected function add_instance_to_table(renderer_base $output, html_table $table, stdClass $instance): void {
        global $PAGE;

        if (!$instance->visible) {
            return;
        }

        $cm = get_coursemodule_from_id('bigbluebuttonbn', $instance->coursemodule, 0, false, MUST_EXIST);

        // User roles.
        $participantlist = roles::bigbluebuttonbn_get_participant_list($instance, $PAGE->context);
        $moderator = roles::bigbluebuttonbn_is_moderator($PAGE->context, $participantlist);
        $administrator = is_siteadmin();
        $canmoderate = ($administrator || $moderator);

        // Add a the data for the bbb instance.
        if (groups_get_activity_groupmode($cm) == 0) {
            $table->data[] = $this->add_room_row_to_table($output, $canmoderate, $instance);
        } else {
            // Add the 'All participants' room information.
            $groupobj = (object) [
                'id' => 0,
                'name' => get_string('allparticipants'),
            ];
            $table->data[] = $this->add_room_row_to_table($output, $canmoderate, $instance, $groupobj);

            // Add a the data for the groups belonging to the bbb instance, if any.
            $groups = groups_get_activity_allowed_groups($cm);
            foreach ($groups as $group) {
                $table->data[] = $this->add_room_row_to_table($output, $canmoderate, $instance, $group);
            }
        }
    }

    /**
     * Displays the general view.
     *
     * @param renderer_base $output
     * @param bool $moderator
     * @param stdClass $instance
     * @param stdClass|null $group
     * @return array
     * @throws moodle_exception
     */
    protected function add_room_row_to_table(
        renderer_base $output,
        bool $moderator,
        stdClass $instance,
        ?stdClass $group = null
    ): array {
        $meetingid = plugin::get_meeting_id($instance, $group);
        $meetinginfo = meeting::bigbluebuttonbn_get_meeting_info_array($meetingid);

        if (empty($meetinginfo)) {
            // The server was unreachable.
            throw new moodle_exception('index_error_unable_display', plugin::COMPONENT);
        }

        if (isset($meetinginfo['messageKey']) && ($meetinginfo['messageKey'] == 'checksumError')) {
            // There was an error returned.
            throw new moodle_exception('index_error_checksum', plugin::COMPONENT);
        }

        $url = new \moodle_url('/mod/bigbluebuttonbn/view.php', ['id' => $instance->coursemodule]);
        $groupname = '';

        if ($group) {
            $url->param('group', $group->id);
            $groupname = $group->name;
        }

        $joinurl = html_writer::link($url, format_string($instance->name));

        // The meeting info was returned.
        if (array_key_exists('running', $meetinginfo) && $meetinginfo['running'] == 'true') {
            return [
                $instance->section,
                $joinurl,
                $groupname,
                $this->get_room_usercount($output, $meetinginfo),
                $this->get_room_attendee_list($output, $meetinginfo, 'VIEWER'),
                $this->get_room_attendee_list($output, $meetinginfo, 'MODERATOR'),
                $this->get_room_record_info($output, $meetinginfo),
                $this->get_room_actions($output, $moderator, $instance, $meetingid),
            ];
        }

        return [$instance->section, $joinurl, $groupname, '', '', '', '', ''];
    }

    /**
     * Count the number of users in the meeting.
     *
     * @param renderer_base $output
     * @param array $meetinginfo
     * @return int
     */
    protected function get_room_usercount(renderer_base $output, $meetinginfo): int {
        if (property_exists($meetinginfo['attendees'], 'attendees')) {
            return count($meetinginfo['attendees']->attendee);
        }

        return 0;
    }

    /**
     * Returns attendee list.
     *
     * @param renderer_base $output
     * @param array $meetinginfo
     * @param string $role
     * @return string
     */
    protected function get_room_attendee_list(renderer_base $output, $meetinginfo, $role): string {
        $attendees = [];

        if (count($meetinginfo['attendees']) && count($meetinginfo['attendees']->attendee)) {
            foreach ($meetinginfo['attendees']->attendee as $attendee) {
                if ($attendee->role == $role) {
                    $attendees[] = $attendee->fullname;
                }
            }
        }

        return implode(', ', $attendees);
    }

    /**
     * Returns indication of recording enabled.
     *
     * @param renderer_base $output
     * @param array $meetinginfo
     * @return string
     */
    protected function get_room_record_info(renderer_base $output, $meetinginfo) {
        if (isset($meetinginfo['recording']) && $meetinginfo['recording'] === 'true') {
            // If it has been set when meeting created, set the variable on/off.
            return get_string('index_enabled', 'bigbluebuttonbn');
        }

        return '';
    }

    /**
     * Returns room actions.
     *
     * @param renderer_base $output
     * @param boolean $ismoderator
     * @param object $instance
     * @param string $meetingid
     * @return string
     * @throws moodle_exception
     */
    protected function get_room_actions(renderer_base $output, $ismoderator, $instance, $meetingid): string {
        if ($ismoderator) {
            return $output->render_from_template('mod_bigbluebuttonbn/end_session_button', (object) [
                'bigbluebuttonbnid' => $instance->id,
                'meetingid' => $meetingid,
            ]);
        }

        return '';
    }
}
