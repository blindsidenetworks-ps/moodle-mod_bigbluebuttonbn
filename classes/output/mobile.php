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

use mod_bigbluebuttonbn\event\events;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\bbb_constants;
use mod_bigbluebuttonbn\local\bigbluebutton;
use mod_bigbluebuttonbn\local\helpers\logs;
use mod_bigbluebuttonbn\local\helpers\meeting as meeting_helper;
use mod_bigbluebuttonbn\local\mobileview;
use mod_bigbluebuttonbn\local\view;
use mod_bigbluebuttonbn\output\mod_bigbluebuttonbn\local\helpers\roles;

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
     *
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
        $versionname = $args->appversioncode >= 3950 ? 'latest' : 'ionic3';
        $viewinstance = view::bigbluebuttonbn_view_validator($args->cmid, null);
        if (!$viewinstance) {
            $error = get_string('view_error_url_missing_parameters', 'bigbluebuttonbn');
            return self::mobile_print_error($error);
        }

        $instance = instance::get_from_cmid($args->cmid);
        $cm = $instance->get_cm();
        $course = $instance->get_course();
        $bigbluebuttonbn = $instance->get_instance_data();

        // Check activity status.
        if ($instance->before_start_time()) {
            $message = get_string('view_message_conference_not_started', 'bigbluebuttonbn');

            $notstarted = [
                'starts_at' => '',
                'ends_at' => '',
            ];
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

            return self::mobile_print_notification($instance, $message, $notstarted);
        }

        if ($instance->has_ended()) {
            $message = get_string('view_message_conference_has_ended', 'bigbluebuttonbn');
            return self::mobile_print_notification($instance, $message);
        }

        // Check if the BBB server is working.
        $serverversion = bigbluebutton::bigbluebuttonbn_get_server_version();
        if ($serverversion === null) {
            return self::mobile_print_error(bigbluebutton::get_server_not_available_message($instance));
        }

        // Mark viewed by user (if required).
        $completion = new \completion_info($course);
        $completion->set_module_viewed($cm);

        // Validate if the user is in a role allowed to join.
        if (!$instance->has_join()) {
            return self::mobile_print_error(get_string('view_nojoin', 'bigbluebuttonbn'));
        }

        // Note: This logic should match bbb_view.php.

        // Logic of bbb_view for join to session.
        if ($instance->user_must_wait_to_join()) {
            // If user is not administrator nor moderator (user is student) and waiting is required.
            return self::mobile_print_notification(
                $instance,
                get_string('view_message_conference_wait_for_moderator', 'bigbluebuttonbn')
            );
        }

        // See if the BBB session is already in progress.
        if (!meeting_helper::bigbluebuttonbn_is_meeting_running($instance->get_meeting_id())) {
            $presentationname = $instance->get_presentation()['name'] ?? null;
            $presentationurl = $instance->get_presentation()['url'] ?? null;
            // The meeting doesnt exist in BBB server, must be created.
            $response = meeting_helper::bigbluebuttonbn_get_create_meeting_array(
                mobileview::create_meeting_data($instance),
                mobileview::create_meeting_metadata($instance),
                $presentationname,
                $presentationurl
            );

            if (empty($response)) {
                return self::mobile_print_error(bigbluebutton::get_server_not_available_message($instance));
            }

            if ($response['returncode'] == 'FAILED') {
                // The meeting could not be created.
                $errorkey = roles::bigbluebuttonbn_get_participant_listget_error_key($response['messageKey'], 'view_error_create');
                $e = get_string($errorkey, 'bigbluebuttonbn');
                return self::mobile_print_error($e);
            }

            if ($response['hasBeenForciblyEnded'] == 'true') {
                $e = get_string('index_error_forciblyended', 'bigbluebuttonbn');
                return self::mobile_print_error($e);
            }

            // Event meeting created.
            logs::log_meeting_created_event($instance);
        }

        // It is part of 'bigbluebuttonbn_bbb_view_join_meeting' in bbb_view.
        // Update the cache.
        $meetinginfo = meeting_helper::bigbluebuttonbn_get_meeting_info(
            $instance->get_meeting_id(),
            bbb_constants::BIGBLUEBUTTONBN_UPDATE_CACHE
        );

        if ($instance->has_user_limit_been_reached(intval($meetinginfo['participantCount']))) {
            // No more users allowed to join.
            return self::mobile_print_notification($instance, get_string('view_error_userlimit_reached', 'bigbluebuttonbn'));
        }

        // Build final url to BBB.
        $urltojoin = mobileview::build_url_join_session($instance, $meetinginfo['createTime']);

        // Check groups access and show message.
        $msjgroup = array();
        $groupmode = groups_get_activity_groupmode($instance->get_cm());
        if ($groupmode != NOGROUPS) {
            $msjgroup = array("message" => get_string('view_mobile_message_groups_not_supported',
                'bigbluebuttonbn'));
        }

        $data = array(
            'bigbluebuttonbn' => $bigbluebuttonbn,
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
     *
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
     *
     * @param instance $instance
     * @param string $message Message to display.
     * @param array $notstarted Extra messages for not started session.
     * @return array HTML, javascript and otherdata
     */
    protected static function mobile_print_notification(instance $instance, $message, $notstarted = array()) {
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
            'javascript' => file_get_contents($CFG->dirroot . '/mod/bigbluebuttonbn/mobileapp/mobile.notification.js'),
            'otherdata' => '',
            'files' => ''
        );
    }
}
