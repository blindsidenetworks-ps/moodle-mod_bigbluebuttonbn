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
 * BigBlueButtonBN internal API for meeting
 *
 * @package   mod_bigbluebuttonbn
 * @category  external
 * @copyright 2018 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bigbluebuttonbn\external;

use context_course;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use mod_bigbluebuttonbn\event\events;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\bbb_constants;
use mod_bigbluebuttonbn\local\bigbluebutton;
use mod_bigbluebuttonbn\local\broker;
use mod_bigbluebuttonbn\local\helpers\meeting;
use mod_bigbluebuttonbn\local\helpers\roles;
use moodle_exception;
use restricted_context_exception;

/**
 * External service for meeting
 *
 * @package   mod_bigbluebuttonbn
 * @category  external
 * @copyright 2018 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class meeting_info extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'bigbluebuttonbnid' => new external_value(PARAM_INT, 'bigbluebuttonbn instance id'),
            'meetingid' => new external_value(PARAM_RAW, 'bigbluebuttonbn meetingid'),
            'updatecache' => new external_value(PARAM_BOOL, 'update cache ?', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Fetch meeting information.
     *
     * @param int $bigbluebuttonbnid the bigbluebuttonbn instance id
     * @param string $meetingid
     * @param bool $updatecache
     * @return array
     */
    public static function execute(
        int $bigbluebuttonbnid,
        string $meetingid,
        bool $updatecache = false
    ): array {
        // Validate the bigbluebuttonbnid ID.
        [
            'bigbluebuttonbnid' => $bigbluebuttonbnid,
            'meetingid' => $meetingid,
            'updatecache' => $updatecache,
        ] = self::validate_parameters(self::execute_parameters(), [
            'bigbluebuttonbnid' => $bigbluebuttonbnid,
            'meetingid' => $meetingid,
            'updatecache' => $updatecache,
        ]);

        // Fetch the session, features, and profile.
        $instance = instance::get_from_instanceid($bigbluebuttonbnid);
        $context = $instance->get_context();

        // Validate that the user has access to this activity and to manage recordings.
        self::validate_context($context);
        return static::get_meeting_info($instance->get_legacy_session_object(), $updatecache, $meetingid);
    }

    /**
     * Get meeting information
     *
     * TODO: Move this to \mod_bigbluebuttonbn\meetinginfo or \mod_bigbluebuttonbn\output\meetinginfo as appropriate.
     * Ideally the new version of this should take a \mod_bigbluebuttonbn\instance.
     *
     * @param array $bbbsession
     * @param bool $updatecache
     * @param null $meetingidoverride override for the meeting id
     * @return array
     * @throws \coding_exception
     */
    public static function get_meeting_info(
        $bbbsession,
        bool $updatecache = false,
        $meetingidoverride = null
    ) {
        global $USER;

        $bbbinfo = new \stdClass();
        if ($bbbsession['openingtime']) {
            $bbbinfo->openingtime = get_string('mod_form_field_openingtime', 'bigbluebuttonbn') . ': ' .
                userdate($bbbsession['openingtime']);
        }
        if ($bbbsession['closingtime']) {
            $bbbinfo->closingtime = get_string('mod_form_field_closingtime', 'bigbluebuttonbn') . ': ' .
                userdate($bbbsession['closingtime']);
        }

        $meetingid = !empty($meetingidoverride) ? $meetingidoverride : $bbbsession['meetingid'];
        $info = meeting::bigbluebuttonbn_get_meeting_info($meetingid, $updatecache);
        $running = false;
        if ($info['returncode'] == 'SUCCESS') {
            $running = ($info['running'] === 'true');
        }
        $activitystatus = bigbluebutton::bigbluebuttonbn_view_session_config($bbbsession, $bbbsession['bigbluebuttonbn']->id);
        $bbbinfo->statusrunning = $running;
        $bbbinfo->statusclosed = ($activitystatus == 'ended');
        if (!$running) {
            $bbbinfo->statusopen = ($activitystatus == 'open');
        }
        $participantcount = isset($info['participantCount']) ? $info['participantCount'] : 0;
        $bbbinfo->participantcount = $participantcount;
        $status = broker::meeting_info_can_join($bbbsession, $running,
            $bbbinfo->participantcount);
        $bbbinfo->canjoin = $status["can_join"];

        // When meeting is not running, see if the user can join.
        $context = context_course::instance($bbbsession['bigbluebuttonbn']->course);
        $participantlist = roles::bigbluebuttonbn_get_participant_list($bbbsession['bigbluebuttonbn'], $context);
        $isadmin = is_siteadmin($USER->id);
        $ismoderator = roles::bigbluebuttonbn_is_moderator($context, $participantlist, $USER->id);
        // If user is administrator, moderator or if is viewer and no waiting is required, join allowed.
        if ($running) {
            $bbbinfo->statusmessage = get_string('view_message_conference_in_progress', 'bigbluebuttonbn');
            $bbbinfo->startedat = floor(intval($info['startTime']) / 1000); // Milliseconds.
            $bbbinfo->moderatorcount = $info['moderatorCount'];
            $bbbinfo->moderatorplural = $info['moderatorCount'] > 1;
            $bbbinfo->participantcount = $info['participantCount'];
            $bbbinfo->participantplural = $info['participantCount'] > 1;
        } else if ($isadmin || $ismoderator || !$bbbsession['bigbluebuttonbn']->wait) {
            $bbbinfo->statusmessage = get_string('view_message_conference_room_ready', 'bigbluebuttonbn');
        } else {
            $bbbinfo->statusmessage = get_string('view_message_conference_wait_for_moderator', 'bigbluebuttonbn');
        }
        $bbbinfo->meetingid = $meetingid;
        $bbbinfo->bigbluebuttonbnid = $bbbsession['bigbluebuttonbn']->id;
        $bbbinfo->cmid = $bbbsession['cm']->id;
        $bbbinfo->userlimit = $bbbsession['userlimit'];
        $bbbinfo->presentations = [];
        if (!empty($bbbsession['presentation']) && !empty($bbbsession['presentation']['url'])) {
            $bbbinfo->presentations[] = [
                'url' => $bbbsession['presentation']['url'],
                'iconname' => $bbbsession['presentation']['icon'],
                'icondesc' => $bbbsession['presentation']['mimetype_description'],
                'name' => $bbbsession['presentation']['name'],
            ];
        }
        if (!empty($bbbsession['group'])) {
            $bbbinfo->group = $bbbsession['group'];
        }
        $bbbinfo->ismoderator = $ismoderator;
        return (array) $bbbinfo;
    }

    /**
     * Describe the return structure of the external service.
     *
     * @return external_single_structure
     * @since Moodle 3.0
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            [
                'cmid' => new external_value(PARAM_INT, 'CM id'),
                'userlimit' => new external_value(PARAM_INT, 'User limit'),
                'bigbluebuttonbnid' => new external_value(PARAM_RAW, 'bigbluebuttonbn instance id'),
                'meetingid' => new external_value(PARAM_RAW, 'Meeting id'),
                'openingtime' => new external_value(PARAM_TEXT, 'Opening time', VALUE_OPTIONAL),
                'closingtime' => new external_value(PARAM_TEXT, 'Closing time', VALUE_OPTIONAL),
                'statusrunning' => new external_value(PARAM_BOOL, 'Status running', VALUE_OPTIONAL),
                'statusclosed' => new external_value(PARAM_BOOL, 'Status closed', VALUE_OPTIONAL),
                'statusopen' => new external_value(PARAM_BOOL, 'Status open', VALUE_OPTIONAL),
                'statusmessage' => new external_value(PARAM_TEXT, 'Status message', VALUE_OPTIONAL),
                'startedat' => new external_value(PARAM_INT, 'Started at', VALUE_OPTIONAL),
                'moderatorcount' => new external_value(PARAM_INT, 'Moderator count', VALUE_OPTIONAL),
                'participantcount' => new external_value(PARAM_INT, 'Participant count', VALUE_OPTIONAL),
                'moderatorplural' => new external_value(PARAM_BOOL, 'Several moderators ?', VALUE_OPTIONAL),
                'participantplural' => new external_value(PARAM_BOOL, 'Several participants ?', VALUE_OPTIONAL),
                'canjoin' => new external_value(PARAM_BOOL, 'Can join'),
                'ismoderator' => new external_value(PARAM_BOOL, 'Is moderator'),
                'presentations' => new \external_multiple_structure(
                    new external_single_structure([
                        'url' => new external_value(PARAM_URL, 'presentation URL'),
                        'iconname' => new external_value(PARAM_RAW, 'icon name'),
                        'icondesc' => new external_value(PARAM_TEXT, 'icon text'),
                        'name' => new external_value(PARAM_TEXT, 'presentation name'),
                    ]), 'Presentation', VALUE_OPTIONAL
                ),
            ]
        );
    }
}
