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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');

/**
 * Helper class for sending notifications.
 *
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notifier {
    /**
     * Starts the notification process.
     *
     * @param object $bigbluebuttonbn
     * @param string $action
     * @return void
     */
    public static function notification_process($bigbluebuttonbn, $action) {
        global $USER;
        // Prepare message.
        $msg = (object) array();
        // Build the message_body.
        $msg->action = $action;
        $msg->activity_type = '';
        $msg->activity_title = $bigbluebuttonbn->name;
        // Add the meeting details to the message_body.
        $msg->action = ucfirst($action);
        $msg->activity_description = '';
        if (!empty($bigbluebuttonbn->intro)) {
            $msg->activity_description = trim($bigbluebuttonbn->intro);
        }
        $msg->activity_openingtime = bigbluebuttonbn_format_activity_time($bigbluebuttonbn->openingtime);
        $msg->activity_closingtime = bigbluebuttonbn_format_activity_time($bigbluebuttonbn->closingtime);
        $msg->activity_owner = fullname($USER);
        // Send notification to all users enrolled.
        self::notification_send($USER, $bigbluebuttonbn, self::notification_msg_html($msg));
    }

    /**
     * Prepares html message body.
     *
     * @param object $msg
     * @return string
     */
    public static function notification_msg_html($msg) {
        $messagetext = '<p>'.$msg->activity_type.' "'.$msg->activity_title.'" '.
            get_string('email_body_notification_meeting_has_been', 'bigbluebuttonbn').' '.$msg->action.'.</p>'."\n";
        $messagetext .= '<p><b>'.$msg->activity_title.'</b> '.
            get_string('email_body_notification_meeting_details', 'bigbluebuttonbn').':'."\n";
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
        return $messagetext;
    }

    /**
     * Sends the message.
     *
     * @param object $sender
     * @param object $bigbluebuttonbn
     * @param string $message
     * @return void
     */
    public static function notification_send($sender, $bigbluebuttonbn, $message = '') {
        $coursemodinfo = \course_modinfo::instance($bigbluebuttonbn->course);
        $course = $coursemodinfo->get_course($bigbluebuttonbn->course);
        // Complete message.
        $msg = (object) array();
        $msg->user_name = fullname($sender);
        $msg->user_email = $sender->email;
        $msg->course_name = $course->fullname;
        $message .= '<p><hr/><br/>'.get_string('email_footer_sent_by', 'bigbluebuttonbn').' '.
            $msg->user_name.'('.$msg->user_email.') ';
        $message .= get_string('email_footer_sent_from', 'bigbluebuttonbn').' '.$msg->course_name.'.</p>';
        // Process the message sending.
        foreach (self::users_to_notify($bigbluebuttonbn->course) as $user) {
            if ($user->id != $sender->id) {
                message_post_message($sender, $user, $message, FORMAT_HTML);
            }
        }
    }

    /**
     * Define users to be notified.
     *
     * @param object $courseid
     * @return array
     */
    public static function users_to_notify($courseid) {
        $context = \context_course::instance($courseid);
        $users = array();
        // See if there are any users in the lesson.
        list($sort, $params) = users_order_by_sql('u');
        $users = get_enrolled_users($context, 'mod/bigbluebuttonbn:view', 0, 'u.*', $sort);
        return $users;
    }
}
