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
 * The mod_bigbluebuttonbn locallib/notifier.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2017 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\locallib;

use html_writer;
use mod_bigbluebuttonbn\plugin;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');

/**
 * Helper class for sending notifications.
 *
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notifier
{
    /**
     * Prepares html message body for instance updated notification.
     *
     * @param object $msg
     * @return string
     */
    public static function htmlmsg_instance_updated($msg) {
        $messagetext = '<p>'.get_string('pluginname', 'bigbluebuttonbn').
            ' <b>'.$msg->activity_url.'</b> '.
            get_string('email_body_notification_meeting_has_been', 'bigbluebuttonbn').' '.$msg->action.'.</p>'."\n";
        $messagetext .= '<p>'.get_string('email_body_notification_meeting_details', 'bigbluebuttonbn').':'."\n";
        $messagetext .= '<table border="0" style="margin: 5px 0 0 20px"><tbody>'."\n";
        $messagetext .= '<tr><td style="font-weight:bold;color:#555;">'.
            get_string('email_body_notification_meeting_title', 'bigbluebuttonbn').': </td><td>'."\n";
        $messagetext .= $msg->activity_title.'</td></tr>'."\n";
        $messagetext .= '<tr><td style="font-weight:bold;color:#555;">'.
            get_string('email_body_notification_meeting_description', 'bigbluebuttonbn').': </td><td>'."\n";
        $messagetext .= $msg->activity_description.'</td></tr>'."\n";
        $messagetext .= '<tr><td style="font-weight:bold;color:#555;">'.
            get_string('email_body_notification_meeting_start_date', 'bigbluebuttonbn').': </td><td>'."\n";
        $messagetext .= $msg->activity_openingtime.'</td></tr>'."\n";
        $messagetext .= '<tr><td style="font-weight:bold;color:#555;">'.
            get_string('email_body_notification_meeting_end_date', 'bigbluebuttonbn').': </td><td>'."\n";
        $messagetext .= $msg->activity_closingtime.'</td></tr>'."\n";
        $messagetext .= '<tr><td style="font-weight:bold;color:#555;">'.$msg->action.' '.
            get_string('email_body_notification_meeting_by', 'bigbluebuttonbn').': </td><td>'."\n";
        $messagetext .= $msg->activity_owner.'</td></tr></tbody></table></p>'."\n";
        $messagetext .= '<p><hr/><br/>'.get_string('email_footer_sent_by', 'bigbluebuttonbn').' '.
            $msg->user_name.' ';
        $messagetext .= get_string('email_footer_sent_from', 'bigbluebuttonbn').' '.$msg->course_name.'.</p>';
        return $messagetext;
    }

    /**
     * Starts the notification process.
     *
     * @param object $bigbluebuttonbn
     * @param string $action
     * @return void
     */
    public static function notify_instance_updated($bigbluebuttonbn, $action) {
        global $USER;
        $coursemodinfo = \course_modinfo::instance($bigbluebuttonbn->course);
        $course = $coursemodinfo->get_course($bigbluebuttonbn->course);
        $sender = $USER;
        // Prepare message.
        $msg = (object) array();
        // Build the message_body.
        $msg->action = $action;
        $msg->activity_url = html_writer::link(
            plugin::necurl('/mod/bigbluebuttonbn/view.php', ['id' => $bigbluebuttonbn->coursemodule]),
            format_string($bigbluebuttonbn->name)
        );
        $msg->activity_title = format_string($bigbluebuttonbn->name);
        // Add the meeting details to the message_body.
        $msg->action = ucfirst($action);
        $msg->activity_description = '';
        if (!empty($bigbluebuttonbn->intro)) {
            $msg->activity_description = format_string(trim($bigbluebuttonbn->intro));
        }
        $msg->activity_openingtime = bigbluebuttonbn_format_activity_time($bigbluebuttonbn->openingtime);
        $msg->activity_closingtime = bigbluebuttonbn_format_activity_time($bigbluebuttonbn->closingtime);
        $msg->activity_owner = fullname($sender);

        $msg->user_name = fullname($sender);
        $msg->user_email = $sender->email;
        $msg->course_name = $course->fullname;

        // Send notification to all users enrolled.
        self::enqueue_notifications($bigbluebuttonbn, $sender, self::htmlmsg_instance_updated($msg));
    }

    /**
     * Prepares html message body for recording ready notification.
     *
     * @param object $bigbluebuttonbn
     *
     * @return void
     */
    public static function htmlmsg_recording_ready($bigbluebuttonbn) {
        return '<p>'.get_string('email_body_recording_ready_for', 'bigbluebuttonbn').
            ' &quot;' . $bigbluebuttonbn->name . '&quot; '.
            get_string('email_body_recording_ready_is_ready', 'bigbluebuttonbn').'.</p>';
    }

    /**
     * Helper function triggers a send notification when the recording is ready.
     *
     * @param object $bigbluebuttonbn
     *
     * @return void
     */
    public static function notify_recording_ready($bigbluebuttonbn) {
        // Instead of get_admin, the firs user enrolled with editing privileges may be used as the sender.
        $sender = get_admin();
        $htmlmsg = self::htmlmsg_recording_ready($bigbluebuttonbn);
        self::enqueue_notifications($bigbluebuttonbn, $sender, $htmlmsg);
    }

    /**
     * Enqueue notifications to be sent to all users in a context where the instance belongs.
     *
     * @param object $bigbluebuttonbn
     * @param object $sender
     * @param string $htmlmsg
     * @return void
     */
    public static function enqueue_notifications($bigbluebuttonbn, $sender, $htmlmsg) {
        foreach (self::receivers($bigbluebuttonbn->course) as $receiver) {
            if ($sender->id != $receiver->id) {
                // Enqueue a task for sending a notification.
                try {
                    // Create the instance of completion_update_state task.
                    $task = new \mod_bigbluebuttonbn\task\send_notification();
                    // Add custom data.
                    $data = array(
                        'sender' => $sender,
                        'receiver' => $receiver,
                        'htmlmsg' => $htmlmsg
                    );
                    $task->set_custom_data($data);
                    // Enqueue it.
                    \core\task\manager::queue_adhoc_task($task);
                } catch (Exception $e) {
                    mtrace("Error while enqueuing completion_uopdate_state task. " . (string) $e);
                }
            }
        }
    }

    /**
     * Sends notification to a user.
     *
     * @param object $sender
     * @param object $receiver
     * @param object $htmlmsg
     * @return void
     */
    public static function send_notification($sender, $receiver, $htmlmsg) {
        // Send the message.
        message_post_message($sender, $receiver, $htmlmsg, FORMAT_HTML);
    }

    /**
     * Define users to be notified.
     *
     * @param object $courseid
     * @return array
     */
    public static function receivers($courseid) {
        $context = \context_course::instance($courseid);
        $users = array();
        // Potential users should be active users only.
        $users = get_enrolled_users($context, 'mod/bigbluebuttonbn:view', 0, 'u.*', null, 0, 0, true);
        return $users;
    }
}
