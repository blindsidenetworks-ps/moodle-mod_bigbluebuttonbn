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
use mod_bigbluebuttonbn\locallib\bigbluebutton;

require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');
require_once($CFG->dirroot . '/lib/grouplib.php');

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
     * @param mixed $args
     * @return array HTML, javascript and other data.
     * @throws \coding_exception
     * @throws \moodle_exception
     * @throws \require_login_exception
     * @throws \required_capability_exception
     */
    public static function mobile_course_view($args) {

        global $OUTPUT, $SESSION;

        $args = (object) $args;
        $viewinstance = bigbluebuttonbn_view_validator($args->cmid, null);
        $versionname = $args->appversioncode >= 3950 ? 'latest' : 'ionic3';
        if (!$viewinstance) {
            $error = get_string('view_error_url_missing_parameters', 'bigbluebuttonbn');
            return(self::mobile_print_error($error));
        }

        $bbbsession = bigbluebutton::build_bbb_session_fromviewinstance($viewinstance);

        // Create variables for easy access.
        $bigbluebuttonbn = $viewinstance['bigbluebuttonbn'];
        $serverversion = $bbbsession['serverversion'];
        $cm = $bbbsession['cm'];
        $course = $bbbsession['course'];
        $context = $bbbsession['context'];

        // Check activity status.
        $activitystatus = \mod_bigbluebuttonbn\locallib\mobileview::get_activity_status($bbbsession);
        if ($activitystatus == 'not_started') {
            $message = get_string('view_message_conference_not_started', 'bigbluebuttonbn');

            $notstarted = array();
            $notstarted['starts_at'] = '';
            $notstarted['ends_at'] = '';
            if (!empty($bigbluebuttonbn->openingtime)) {
                $notstarted['starts_at'] = sprintf(
                    '%s: %s',
                    get_string('mod_form_field_openingtime', 'bigbluebuttonbn'),
                    userdate($bigbluebuttonbn->openingtime)
                );
            }
            if (!empty($bigbluebuttonbn->closingtime)) {
                $notstarted['ends_at'] = sprintf(
                    '%s: %s',
                    get_string('mod_form_field_closingtime', 'bigbluebuttonbn'),
                    userdate($bigbluebuttonbn->closingtime)
                );
            }

            return(self::mobile_print_notification($bigbluebuttonbn, $cm, $message, $notstarted));
        }
        if ($activitystatus == 'ended') {
            $message = get_string('view_message_conference_has_ended', 'bigbluebuttonbn');
            return(self::mobile_print_notification($bigbluebuttonbn, $cm, $message));
        }

        // Check if the BBB server is working.
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

        // Mark viewed by user (if required).
        $completion = new \completion_info($course);
        $completion->set_module_viewed($cm);

        // Validate if the user is in a role allowed to join.
        if (!has_capability('moodle/category:manage', $context) &&
            !has_capability('mod/bigbluebuttonbn:join', $context)) {
            $error = get_string('view_nojoin', 'bigbluebuttonbn');
            return(self::mobile_print_error($error));
        }

        // Initialize session variable used across views.
        $SESSION->bigbluebuttonbn_bbbsession = $bbbsession;

        // Logic of bbb_view for join to session.
        // If user is not administrator nor moderator (user is student) and waiting is required.
        if (!$bbbsession['administrator'] && !$bbbsession['moderator'] && $bbbsession['wait']) {
            $canjoin = \mod_bigbluebuttonbn\locallib\bigbluebutton::can_join_meeting($args->cmid);
            if (!$canjoin['can_join']) {
                $message = get_string('view_message_conference_wait_for_moderator', 'bigbluebuttonbn');
                return (self::mobile_print_notification($bigbluebuttonbn, $cm, $message));
            }
        }

        // See if the BBB session is already in progress.
        if (!bigbluebuttonbn_is_meeting_running($bbbsession['meetingid'])) {

            // The meeting doesnt exist in BBB server, must be created.
            $response = bigbluebuttonbn_get_create_meeting_array(
                \mod_bigbluebuttonbn\locallib\mobileview::create_meeting_data($bbbsession),
                \mod_bigbluebuttonbn\locallib\mobileview::create_meeting_metadata($bbbsession),
                $bbbsession['presentation']['name'],
                $bbbsession['presentation']['url']
            );

            if (empty($response)) {
                // The BBB server is failing.
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
                // The meeting could not be created.
                $errorkey = bigbluebuttonbn_get_error_key($response['messageKey'],  'view_error_create');
                $e = get_string($errorkey, 'bigbluebuttonbn');
                return(self::mobile_print_error($e));
            }
            if ($response['hasBeenForciblyEnded'] == 'true') {
                $e = get_string('index_error_forciblyended', 'bigbluebuttonbn');
                return(self::mobile_print_error($e));
            }

            // Event meeting created.
            bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events['meeting_create'], $bigbluebuttonbn);
            // Insert a record that meeting was created.
            $overrides = array('meetingid' => $bbbsession['meetingid']);
            $meta = '{"record":'.($bbbsession['record'] ? 'true' : 'false').'}';
            bigbluebuttonbn_log($bbbsession['bigbluebuttonbn'], BIGBLUEBUTTONBN_LOG_EVENT_CREATE, $overrides, $meta);
        }

        // It is part of 'bigbluebuttonbn_bbb_view_join_meeting' in bbb_view.
        // Update the cache.
        $meetinginfo = bigbluebuttonbn_get_meeting_info($bbbsession['meetingid'], BIGBLUEBUTTONBN_UPDATE_CACHE);
        if ($bbbsession['userlimit'] > 0 && intval($meetinginfo['participantCount']) >= $bbbsession['userlimit']) {
            // No more users allowed to join.
            $message = get_string('view_error_userlimit_reached', 'bigbluebuttonbn');
            return(self::mobile_print_notification($bigbluebuttonbn, $cm, $message));
        }

        // Build final url to BBB.
        $bbbsession['createtime'] = $meetinginfo['createTime'];
        $urltojoin = \mod_bigbluebuttonbn\locallib\mobileview::build_url_join_session($bbbsession);

        // Check groups access and show message.
        $msjgroup = array();
        $groupmode = groups_get_activity_groupmode($bbbsession['cm']);
        if ($groupmode != NOGROUPS) {
            $msjgroup = array("message" => get_string('view_mobile_message_groups_not_supported',
                'bigbluebuttonbn'));
        }

        $data = array(
            'bigbluebuttonbn' => $bigbluebuttonbn,
            'bbbsession' => (object) $bbbsession,
            'msjgroup' => $msjgroup,
            'urltojoin' => $urltojoin,
            'cmid' => $cm->id,
            'courseid' => $course->id
        );

        // We want to show a notification when user excedded 45 seconds without click button.
        $jstimecreatedmeeting = 'setTimeout(function(){
        document.getElementById("bigbluebuttonbn-mobile-notifications").style.display = "block";
        document.getElementById("bigbluebuttonbn-mobile-join").disabled = true;
        document.getElementById("bigbluebuttonbn-mobile-meetingready").style.display = "none";
        }, 45000);';

        return array(
            'templates' => array(
                array(
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template("mod_bigbluebuttonbn/mobile_view_page_$versionname", $data),
                ),
            ),
            'javascript' => $jstimecreatedmeeting,
            'otherdata' => '',
            'files' => ''
        );
    }

    /**
     * Returns the view for errors.
     * @param string $error Error to display.
     *
     * @return array       HTML, javascript and otherdata
     */
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

    /**
     * Returns the view for messages.
     * @param object $bigbluebuttonbn
     * @param stdClass $cm
     * @param string $message Message to display.
     * @param array $notstarted Extra messages for not started session.
     * @return array HTML, javascript and otherdata
     */
    protected static function mobile_print_notification($bigbluebuttonbn, $cm, $message, $notstarted = array()) {

        global $OUTPUT, $CFG;
        $data = array(
            'bigbluebuttonbn' => $bigbluebuttonbn,
            'cmid' => $cm->id,
            'message' => $message,
            'not_started' => $notstarted
        );

        return array(
            'templates' => array(
                array(
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_bigbluebuttonbn/mobile_view_notification', $data),
                ),
            ),
            'javascript' => file_get_contents($CFG->dirroot . '/mod/bigbluebuttonbn/mobile.notification.js'),
            'otherdata' => '',
            'files' => ''
        );
    }
}
