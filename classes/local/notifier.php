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

namespace mod_bigbluebuttonbn\local;

use html_writer;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\output\notifier_instance_updated;
use mod_bigbluebuttonbn\plugin;
use moodle_url;

/**
 * Helper class for sending notifications.
 *
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notifier
{
    /**
     * Starts the notification process.
     *
     * @param object $bigbluebuttonbn
     * @param string $action
     * @return void
     */
    public static function notify_instance_updated($bigbluebuttonbn, $action) {
        global $USER, $OUTPUT;
        $notifierouput = new notifier_instance_updated($bigbluebuttonbn, $USER, $action);
        // Send notification to all users enrolled.
        self::enqueue_notifications($bigbluebuttonbn, $USER, $OUTPUT->render($notifierouput));
    }

    /**
     * Prepares html message body for recording ready notification.
     *
     * @param object $bigbluebuttonbn
     *
     * @return string
     */
    public static function htmlmsg_recording_ready($bigbluebuttonbn) {
        $instance = instance::get_from_instanceid($bigbluebuttonbn->id);
        $coursemodinfo = \course_modinfo::instance($bigbluebuttonbn->course);
        $course = $coursemodinfo->get_course($bigbluebuttonbn->course);
        $link = html_writer::link($instance->get_view_url(), format_string($bigbluebuttonbn->name));
        return '<p>' . get_string('email_body_recording_ready_for', 'bigbluebuttonbn') .
            ' <b>' . $link . '</b> ' .
            get_string('email_body_recording_ready_in_course', 'bigbluebuttonbn') .
            ' ' . $course->fullname . '.</p>';
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

    /**
     * Helper function returns time in a formatted string.
     *
     * @param int $time
     * @return string
     */
    protected static function format_activity_time(int $time): string {
        global $CFG;
        require_once($CFG->dirroot . '/calendar/lib.php');

        if ($time) {
            $activitytime = [
                calendar_day_representation($time),
                get_string('mod_form_field_notification_msg_at', 'bigbluebuttonbn'),
                calendar_time_representation($time),
            ];

            return implode(' ', $activitytime);
        }
        return '';
    }

}
