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
 * The mod_bigbluebuttonbn constants
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David  (laurent [at] call-learning [dt] fr)
 */
namespace mod_bigbluebuttonbn\local;
defined('MOODLE_INTERNAL') || die();

/**
 * Utility class to store constants
 *
 * Utility class for activity helper
 * @package mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bbb_constants {
    /** @var string BIGBLUEBUTTONBN_DEFAULT_SERVER_URL of default bigbluebutton server url */
    public const BIGBLUEBUTTONBN_DEFAULT_SERVER_URL = 'http://test-install.blindsidenetworks.com/bigbluebutton/';
    /** @var string BIGBLUEBUTTONBN_DEFAULT_SHARED_SECRET of default bigbluebutton server shared secret */
    public const BIGBLUEBUTTONBN_DEFAULT_SHARED_SECRET = '8cd8ef52e8e101574e400365b55e11a6';
    /** @var string BIGBLUEBUTTONBN_LOG_EVENT_ADD defines the bigbluebuttonbn Add event */
    public const BIGBLUEBUTTONBN_LOG_EVENT_ADD = 'Add';
    /** @var string BIGBLUEBUTTONBN_LOG_EVENT_EDIT defines the bigbluebuttonbn Edit event */
    public const BIGBLUEBUTTONBN_LOG_EVENT_EDIT = 'Edit';
    /** @var string BIGBLUEBUTTONBN_LOG_EVENT_CREATE defines the bigbluebuttonbn Create event */
    public const BIGBLUEBUTTONBN_LOG_EVENT_CREATE = 'Create';
    /** @var string BIGBLUEBUTTONBN_LOG_EVENT_JOIN defines the bigbluebuttonbn Join event */
    public const BIGBLUEBUTTONBN_LOG_EVENT_JOIN = 'Join';
    /** @var string BIGBLUEBUTTONBN_LOG_EVENT_PLAYED defines the bigbluebuttonbn Playback event */
    public const BIGBLUEBUTTONBN_LOG_EVENT_PLAYED = 'Played';
    /** @var string BIGBLUEBUTTONBN_LOG_EVENT_LOGOUT defines the bigbluebuttonbn Logout event */
    public const BIGBLUEBUTTONBN_LOG_EVENT_LOGOUT = 'Logout';
    /** @var string BIGBLUEBUTTONBN_LOG_EVENT_IMPORT defines the bigbluebuttonbn Import event */
    public const BIGBLUEBUTTONBN_LOG_EVENT_IMPORT = 'Import';
    /** @var string BIGBLUEBUTTONBN_LOG_EVENT_DELETE defines the bigbluebuttonbn Delete event */
    public const BIGBLUEBUTTONBN_LOG_EVENT_DELETE = 'Delete';
    /** @var string BIGBLUEBUTTON_LOG_EVENT_CALLBACK defines the bigbluebuttonbn Callback event */
    public const BIGBLUEBUTTON_LOG_EVENT_CALLBACK = 'Callback';
    /** @var string BIGBLUEBUTTON_LOG_EVENT_SUMMARY defines the bigbluebuttonbn Summary event */
    public const BIGBLUEBUTTON_LOG_EVENT_SUMMARY = 'Summary';
    /** @var bool BIGBLUEBUTTONBN_UPDATE_CACHE boolean set to true indicates that cache has to be updated */
    public const BIGBLUEBUTTONBN_UPDATE_CACHE = true;
    /** @var int BIGBLUEBUTTONBN_TYPE_ALL integer set to 0 defines an instance type that inclueds room and recordings */
    public const BIGBLUEBUTTONBN_TYPE_ALL = 0;
    /** @var int BIGBLUEBUTTONBN_TYPE_ROOM_ONLY integer set to 1 defines an instance type that inclueds only room */
    public const BIGBLUEBUTTONBN_TYPE_ROOM_ONLY = 1;
    /** @var int BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY integer set to 2 defines an instance type that inclueds only recordings */
    public const BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY = 2;
    /** @var int BIGBLUEBUTTONBN_ROLE_VIEWER string defines the bigbluebutton viewer role */
    public const BIGBLUEBUTTONBN_ROLE_VIEWER = 'viewer';
    /** @var string BIGBLUEBUTTONBN_ROLE_MODERATOR string defines the bigbluebutton moderator role */
    public const BIGBLUEBUTTONBN_ROLE_MODERATOR = 'moderator';
    /** @var string  BIGBLUEBUTTON_EVENT_MEETING_START string defines the bigbluebuttonbn meeting_start event */
    public const BIGBLUEBUTTON_EVENT_MEETING_START = 'meeting_start';
    /** @var int BIGBLUEBUTTON_ORIGIN_BASE integer set to 0 defines that the user acceded the session from activity page */
    public const BIGBLUEBUTTON_ORIGIN_BASE = 0;
    /** @var int BIGBLUEBUTTON_ORIGIN_TIMELINE integer set to 1 defines that the user acceded the session from Timeline */
    public const BIGBLUEBUTTON_ORIGIN_TIMELINE = 1;
    /** @var int BIGBLUEBUTTON_ORIGIN_INDEX integer set to 2 defines that the user acceded the session from Index */
    public const BIGBLUEBUTTON_ORIGIN_INDEX = 2;
}
