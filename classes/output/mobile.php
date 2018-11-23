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
 * Mobile output class for bigbluebuttonbn
 *
 * @package    mod_bigbluebuttonbn
 * @copyright  2018 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\output;

defined('MOODLE_INTERNAL') || die();

use context_module;
use mod_bigbluebuttonbn_external;
require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');

/**
 * Mobile output class for bigbluebuttonbn
 *
 * @package    mod_bigbluebuttonbn
 * @copyright  2018 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class mobile {

    /**
     * Returns the bigbluebuttonbn course view for the mobile app.
     * @param  array $args Arguments from tool_mobile_get_content WS
     *
     * @return array       HTML, javascript and otherdata
     */
    public static function mobile_course_view($args) {

        global $OUTPUT, $USER, $DB, $SESSION, $CFG;

        $args = (object) $args;

        $viewinstance = bigbluebuttonbn_view_validator($args->cmid, null);
        if (!$viewinstance) {
            $error = get_string('view_error_url_missing_parameters', 'bigbluebuttonbn');
            return(self::mobile_print_error($error));
        }

        $cm = $viewinstance['cm'];
        $course = $viewinstance['course'];
        $bigbluebuttonbn = $viewinstance['bigbluebuttonbn'];
        $context = context_module::instance($cm->id);

        require_login($course->id, false , $cm, true, true);
        require_capability('mod/bigbluebuttonbn:join', $context);

        // Add view event.
        bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events['view'], $bigbluebuttonbn);

        // Additional info related to the course.
        $bbbsession['course'] = $course;
        $bbbsession['coursename'] = $course->fullname;
        $bbbsession['cm'] = $cm;
        $bbbsession['bigbluebuttonbn'] = $bigbluebuttonbn;

        // Set common variables for session.
        $bbbsession = \mod_bigbluebuttonbn\locallib\mobileview::bigbluebuttonbn_view_bbbsession_set($context, $bbbsession);

        // Check activity status.
        $activitystatus = \mod_bigbluebuttonbn\locallib\mobileview::bigbluebuttonbn_view_get_activity_status($bbbsession);
        if ($activitystatus == 'not_started') {
            $error = get_string('view_message_conference_not_started', 'bigbluebuttonbn');
            return(self::mobile_print_error($error));
        }
        if ($activitystatus == 'ended') {
            $error = get_string('view_message_conference_has_ended', 'bigbluebuttonbn');
            return(self::mobile_print_error($error));
        }

        // Validates if the BigBlueButton server is working.
        $serverversion = bigbluebuttonbn_get_server_version();
        if (is_null($serverversion)) {

            if ($bbbsession['administrator']) {
                $error = get_string('view_error_unable_join', 'bigbluebuttonbn');
            } else if ($bbbsession['moderator']) {
                $error = get_string('view_error_unable_join_teacher', 'bigbluebuttonbn');
            } else {
                $error = get_string('view_error_unable_join_student', 'bigbluebuttonbn');
            }

            return(self::mobile_print_error($error));
        }
        $bbbsession['serverversion'] = (string) $serverversion;

        // Mark viewed by user (if required).
        $completion = new \completion_info($course);
        $completion->set_module_viewed($cm);

        // Validate if the user is in a role allowed to join.
        if (!has_capability('moodle/category:manage', $context) &&
            !has_capability('mod/bigbluebuttonbn:join', $context)) {
            $error = get_string('view_nojoin', 'bigbluebuttonbn');
            return(self::mobile_print_error($error));
        }

        // Operation URLs.
        $bbbsession['bigbluebuttonbnURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/view.php?id=' . $bbbsession['cm']->id;
        $bbbsession['logoutURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_view.php?action=logout&id='.$args->cmid .
            '&bn=' . $bbbsession['bigbluebuttonbn']->id;
        $bbbsession['recordingReadyURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_broker.php?action=recording_' .
            'ready&bigbluebuttonbn=' . $bbbsession['bigbluebuttonbn']->id;
        $bbbsession['meetingEventsURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_broker.php?action=meeting' .
            '_events&bigbluebuttonbn=' . $bbbsession['bigbluebuttonbn']->id;
        $bbbsession['joinURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_view.php?action=join&id=' . $args->cmid .
            '&bn=' . $bbbsession['bigbluebuttonbn']->id;

        // Initialize session variable used across views.
        $SESSION->bigbluebuttonbn_bbbsession = $bbbsession;

        // Logic of bbb_view for join to session.
        // If user is not administrator nor moderator (user is student) and waiting is required.
        if (!$bbbsession['administrator'] && !$bbbsession['moderator'] && $bbbsession['wait']) {
            $message = get_string('view_message_conference_wait_for_moderator', 'bigbluebuttonbn');
            return(self::mobile_print_error($message));
        }

        // See if the session is in progress.
        if (!bigbluebuttonbn_is_meeting_running($bbbsession['meetingid'])) {

            // As the meeting doesn't exist, try to create it.
            $response = bigbluebuttonbn_get_create_meeting_array(
                \mod_bigbluebuttonbn\locallib\mobileview::bigbluebutton_bbb_view_create_meeting_data($bbbsession),
                \mod_bigbluebuttonbn\locallib\mobileview::bigbluebutton_bbb_view_create_meeting_metadata($bbbsession),
                $bbbsession['presentation']['name'],
                $bbbsession['presentation']['url']
            );

            if (empty($response)) {
                // The server is unreachable.
                if ($bbbsession['administrator']) {
                    $e = get_string('view_error_unable_join', 'bigbluebuttonbn');
                } else if ($bbbsession['moderator']) {
                    $e = get_string('view_error_unable_join_teacher', 'bigbluebuttonbn');
                } else {
                    $e = get_string('view_error_unable_join_student', 'bigbluebuttonbn');
                }
                return(self::mobile_print_error($e));
            }
            if ($response['returncode'] == 'FAILED') {
                // The meeting was not created.
                $printerrorkey = bigbluebuttonbn_get_error_key($response['messageKey'],  'view_error_create');
                $e = get_string($printerrorkey, 'bigbluebuttonbn');
                return(self::mobile_print_error($e));
            }
            if ($response['hasBeenForciblyEnded'] == 'true') {
                $e = get_string('index_error_forciblyended', 'bigbluebuttonbn');
                return(self::mobile_print_error($e));
            }

            // Moodle event logger: Create an event for meeting created.
            bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events['meeting_create'], $bigbluebuttonbn);
            // Internal logger: Insert a record with the meeting created.
            $overrides = array('meetingid' => $bbbsession['meetingid']);
            $meta = '{"record":'.($bbbsession['record'] ? 'true' : 'false').'}';
            bigbluebuttonbn_log($bbbsession['bigbluebuttonbn'], BIGBLUEBUTTONBN_LOG_EVENT_CREATE, $overrides, $meta);
        }

        // It is part of 'bigbluebutton_bbb_view_join_meeting' in bbb_view.
        // Update the cache.
        $meetinginfo = bigbluebuttonbn_get_meeting_info($bbbsession['meetingid'], BIGBLUEBUTTONBN_UPDATE_CACHE);
        if ($bbbsession['userlimit'] > 0 && intval($meetinginfo['participantCount']) >= $bbbsession['userlimit']) {
            // No more users allowed to join.
            $message = get_string('view_error_userlimit_reached', 'bigbluebuttonbn');
            return(self::mobile_print_error($message));
        }

        // Moodle event logger: Create an event for meeting joined.
        bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events['meeting_join'], $bigbluebuttonbn);

        // Internal logger: Insert a record with the meeting created.
        $overrides = array('meetingid' => $bbbsession['meetingid']);
        bigbluebuttonbn_log($bbbsession['bigbluebuttonbn'], BIGBLUEBUTTONBN_LOG_EVENT_JOIN, $overrides);

        // Increment the number of participants.
        bigbluebuttonbn_participant_joined($bbbsession['meetingid'], ($bbbsession['administrator'] || $bbbsession['moderator']));

        // Build final url to BBB.
        $urltojoin = \mod_bigbluebuttonbn\locallib\mobileview::build_url_join_session($bbbsession);

        $data = array(
            'bigbluebuttonbn' => $bigbluebuttonbn,
            'bbbsession' => (object) $bbbsession,
            'urltojoin' => $urltojoin,
            'cmid' => $cm->id,
            'courseid' => $args->courseid
        );

        return array(
            'templates' => array(
                array(
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_bigbluebuttonbn/mobile_view_page', $data),
                ),
            ),
            'javascript' => '',
            'otherdata' => '',
            'files' => ''
        );
    }

    protected static function mobile_print_error($error) {

        global $OUTPUT;
        $data = array(
            'error' => $error
        );

        return array(
            'templates' => array(
                array(
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_bigbluebuttonbn/mobile_view_error', $data),
                ),
            ),
            'javascript' => '',
            'otherdata' => '',
            'files' => ''
        );
    }
}
