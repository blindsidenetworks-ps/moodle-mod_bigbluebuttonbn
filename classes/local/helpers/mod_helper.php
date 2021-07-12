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
 * The mod_bigbluebuttonbn instance (module) helper
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David  (laurent [at] call-learning [dt] fr)
 */
namespace mod_bigbluebuttonbn\local\helpers;

use calendar_event;
use mod_bigbluebuttonbn\local\bbb_constants;
use mod_bigbluebuttonbn\local\bigbluebutton;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\local\helpers\logs;
use mod_bigbluebuttonbn\local\notifier;
use mod_bigbluebuttonbn\local\view;
use mod_bigbluebuttonbn\plugin;
use context_module;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Utility class for all instance (module) routines helper
 *
 * @package mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_helper {

    /**
     * Runs any processes that must run before a bigbluebuttonbn insert/update.
     *
     * @param object $bigbluebuttonbn BigBlueButtonBN form data
     *
     * @return void
     **/
    public static function bigbluebuttonbn_process_pre_save(&$bigbluebuttonbn) {
        static::bigbluebuttonbn_process_pre_save_instance($bigbluebuttonbn);
        static::bigbluebuttonbn_process_pre_save_checkboxes($bigbluebuttonbn);
        static::bigbluebuttonbn_process_pre_save_common($bigbluebuttonbn);
        $bigbluebuttonbn->participants = htmlspecialchars_decode($bigbluebuttonbn->participants);
    }

    /**
     * Runs process for defining the instance (insert/update).
     *
     * @param object $bigbluebuttonbn BigBlueButtonBN form data
     *
     * @return void
     **/
    public static function bigbluebuttonbn_process_pre_save_instance(&$bigbluebuttonbn) {
        global $CFG;
        $bigbluebuttonbn->timemodified = time();
        if ((integer) $bigbluebuttonbn->instance == 0) {
            $bigbluebuttonbn->meetingid = 0;
            $bigbluebuttonbn->timecreated = time();
            $bigbluebuttonbn->timemodified = 0;
            // As it is a new activity, assign passwords.
            $bigbluebuttonbn->moderatorpass = plugin::bigbluebuttonbn_random_password(12);
            $bigbluebuttonbn->viewerpass =
                plugin::bigbluebuttonbn_random_password(12, $bigbluebuttonbn->moderatorpass);
        }
    }

    /**
     * Runs process for assigning default value to checkboxes.
     *
     * @param object $bigbluebuttonbn BigBlueButtonBN form data
     *
     * @return void
     **/
    public static function bigbluebuttonbn_process_pre_save_checkboxes(&$bigbluebuttonbn) {
        if (!isset($bigbluebuttonbn->wait)) {
            $bigbluebuttonbn->wait = 0;
        }
        if (!isset($bigbluebuttonbn->record)) {
            $bigbluebuttonbn->record = 0;
        }
        if (!isset($bigbluebuttonbn->recordallfromstart)) {
            $bigbluebuttonbn->recordallfromstart = 0;
        }
        if (!isset($bigbluebuttonbn->recordhidebutton)) {
            $bigbluebuttonbn->recordhidebutton = 0;
        }
        if (!isset($bigbluebuttonbn->recordings_html)) {
            $bigbluebuttonbn->recordings_html = 0;
        }
        if (!isset($bigbluebuttonbn->recordings_deleted)) {
            $bigbluebuttonbn->recordings_deleted = 0;
        }
        if (!isset($bigbluebuttonbn->recordings_imported)) {
            $bigbluebuttonbn->recordings_imported = 0;
        }
        if (!isset($bigbluebuttonbn->recordings_preview)) {
            $bigbluebuttonbn->recordings_preview = 0;
        }
        if (!isset($bigbluebuttonbn->muteonstart)) {
            $bigbluebuttonbn->muteonstart = 0;
        }
        if (!isset($bigbluebuttonbn->disablecam)) {
            $bigbluebuttonbn->disablecam = 0;
        }
        if (!isset($bigbluebuttonbn->disablemic)) {
            $bigbluebuttonbn->disablemic = 0;
        }
        if (!isset($bigbluebuttonbn->disableprivatechat)) {
            $bigbluebuttonbn->disableprivatechat = 0;
        }
        if (!isset($bigbluebuttonbn->disablepublicchat)) {
            $bigbluebuttonbn->disablepublicchat = 0;
        }
        if (!isset($bigbluebuttonbn->disablenote)) {
            $bigbluebuttonbn->disablenote = 0;
        }
        if (!isset($bigbluebuttonbn->hideuserlist)) {
            $bigbluebuttonbn->hideuserlist = 0;
        }
        if (!isset($bigbluebuttonbn->lockedlayout)) {
            $bigbluebuttonbn->lockedlayout = 0;
        }
        if (!isset($bigbluebuttonbn->lockonjoin)) {
            $bigbluebuttonbn->lockonjoin = 0;
        }
        if (!isset($bigbluebuttonbn->lockonjoinconfigurable)) {
            $bigbluebuttonbn->lockonjoinconfigurable = 0;
        }
        if (!isset($bigbluebuttonbn->recordings_validate_url)) {
            $bigbluebuttonbn->recordings_validate_url = 1;
        }
    }

    /**
     * Runs process for wipping common settings when 'recordings only'.
     *
     * @param object $bigbluebuttonbn BigBlueButtonBN form data
     *
     * @return void
     **/
    public static function bigbluebuttonbn_process_pre_save_common(&$bigbluebuttonbn) {
        // Make sure common settings are removed when 'recordings only'.
        if ($bigbluebuttonbn->type == bbb_constants::BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY) {
            $bigbluebuttonbn->groupmode = 0;
            $bigbluebuttonbn->groupingid = 0;
        }
    }

    /**
     * Runs any processes that must be run after a bigbluebuttonbn insert/update.
     *
     * @param object $bigbluebuttonbn BigBlueButtonBN form data
     *
     * @return void
     **/
    public static function bigbluebuttonbn_process_post_save(&$bigbluebuttonbn) {
        if (isset($bigbluebuttonbn->notification) && $bigbluebuttonbn->notification) {
            static::bigbluebuttonbn_process_post_save_notification($bigbluebuttonbn);
        }
        static::bigbluebuttonbn_process_post_save_event($bigbluebuttonbn);
        static::bigbluebuttonbn_process_post_save_completion($bigbluebuttonbn);
    }

    /**
     * Generates a message on insert/update which is sent to all users enrolled.
     *
     * @param object $bigbluebuttonbn BigBlueButtonBN form data
     *
     * @return void
     **/
    public static function bigbluebuttonbn_process_post_save_notification(&$bigbluebuttonbn) {
        $action = get_string('mod_form_field_notification_msg_modified', 'bigbluebuttonbn');
        if (isset($bigbluebuttonbn->add) && !empty($bigbluebuttonbn->add)) {
            $action = get_string('mod_form_field_notification_msg_created', 'bigbluebuttonbn');
        }
        notifier::notify_instance_updated($bigbluebuttonbn, $action);
    }

    /**
     * Generates an event after a bigbluebuttonbn insert/update.
     *
     * @param object $bigbluebuttonbn BigBlueButtonBN form data
     *
     * @return void
     **/
    public static function bigbluebuttonbn_process_post_save_event(&$bigbluebuttonbn) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');
        $eventid = $DB->get_field('event', 'id', array('modulename' => 'bigbluebuttonbn',
            'instance' => $bigbluebuttonbn->id));
        // Delete the event from calendar when/if openingtime is NOT set.
        if (!isset($bigbluebuttonbn->openingtime) || !$bigbluebuttonbn->openingtime) {
            if ($eventid) {
                $calendarevent = calendar_event::load($eventid);
                $calendarevent->delete();
            }
            return;
        }
        // Add evento to the calendar as openingtime is set.
        $event = new stdClass();
        $event->eventtype = bbb_constants::BIGBLUEBUTTON_EVENT_MEETING_START;
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->name = get_string('calendarstarts', 'bigbluebuttonbn', $bigbluebuttonbn->name);
        $event->description = format_module_intro('bigbluebuttonbn', $bigbluebuttonbn, $bigbluebuttonbn->coursemodule, false);
        $event->format = FORMAT_HTML;
        $event->courseid = $bigbluebuttonbn->course;
        $event->groupid = 0;
        $event->userid = 0;
        $event->modulename = 'bigbluebuttonbn';
        $event->instance = $bigbluebuttonbn->id;
        $event->timestart = $bigbluebuttonbn->openingtime;
        $event->timeduration = 0;
        $event->timesort = $event->timestart;
        $event->visible = instance_is_visible('bigbluebuttonbn', $bigbluebuttonbn);
        $event->priority = null;
        // Update the event in calendar when/if eventid was found.
        if ($eventid) {
            $event->id = $eventid;
            $calendarevent = calendar_event::load($eventid);
            $calendarevent->update($event);
            return;
        }
        calendar_event::create($event);
    }

    /**
     * Generates an event after a bigbluebuttonbn activity is completed.
     *
     * @param object $bigbluebuttonbn BigBlueButtonBN form data
     *
     * @return void
     **/
    public static function bigbluebuttonbn_process_post_save_completion($bigbluebuttonbn) {
        if (!empty($bigbluebuttonbn->completionexpected)) {
            \core_completion\api::update_completion_date_event(
                $bigbluebuttonbn->coursemodule,
                'bigbluebuttonbn',
                $bigbluebuttonbn->id,
                $bigbluebuttonbn->completionexpected
            );
        }
    }
}
