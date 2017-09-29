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
 * The mod_bigbluebuttonbn settings/renderer.
 *
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2017 - present Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

namespace mod_bigbluebuttonbn\settings;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');

class renderer {

    /**
     * @return boolean
     */
    public static function section_general_shown() {
        global $CFG;
        return (!isset($CFG->bigbluebuttonbn['server_url']) ||
                !isset($CFG->bigbluebuttonbn['shared_secret']));
    }

    /**
     * @return boolean
     */
    public static function section_record_meeting_shown() {
        global $CFG;
        return (!isset($CFG->bigbluebuttonbn['recording_default']) ||
                !isset($CFG->bigbluebuttonbn['recording_editable']) ||
                !isset($CFG->bigbluebuttonbn['recording_icons_enabled']));
    }

    /**
     * @return boolean
     */
    public static function section_import_recordings_shown() {
        global $CFG;
        return (!isset($CFG->bigbluebuttonbn['importrecordings_enabled']) ||
                !isset($CFG->bigbluebuttonbn['importrecordings_from_deleted_enabled']));

    }

    /**
     * @return boolean
     */
    public static function section_show_recordings_shown() {
        global $CFG;
        return (!isset($CFG->bigbluebuttonbn['recordings_html_default']) ||
                !isset($CFG->bigbluebuttonbn['recordings_html_editable']) ||
                !isset($CFG->bigbluebuttonbn['recordings_deleted_default']) ||
                !isset($CFG->bigbluebuttonbn['recordings_deleted_editable']) ||
                !isset($CFG->bigbluebuttonbn['recordings_imported_default']) ||
                !isset($CFG->bigbluebuttonbn['recordings_imported_editable']));
    }

    /**
     * @return boolean
     */
    public static function section_wait_moderator_shown() {
        global $CFG;
        return (!isset($CFG->bigbluebuttonbn['waitformoderator_default']) ||
                !isset($CFG->bigbluebuttonbn['waitformoderator_editable']) ||
                !isset($CFG->bigbluebuttonbn['waitformoderator_ping_interval']) ||
                !isset($CFG->bigbluebuttonbn['waitformoderator_cache_ttl']));
    }

    /**
     * @return boolean
     */
    public static function section_static_voice_bridge_shown() {
        global $CFG;
        return (!isset($CFG->bigbluebuttonbn['voicebridge_editable']));
    }

    /**
     * @return boolean
     */
    public static function section_preupload_presentation_shown() {
        global $CFG;
        return (!isset($CFG->bigbluebuttonbn['preuploadpresentation_enabled']));
    }

    /**
     * @return boolean
     */
    public static function section_user_limit_shown() {
        global $CFG;
        return (!isset($CFG->bigbluebuttonbn['userlimit_default']) ||
                !isset($CFG->bigbluebuttonbn['userlimit_editable']));
    }

    /**
     * @return boolean
     */
    public static function section_scheduled_duration_shown() {
        global $CFG;
        return (!isset($CFG->bigbluebuttonbn['scheduled_duration_enabled']));
    }

    /**
     * @return boolean
     */
    public static function section_moderator_default_shown() {
        global $CFG;
        return (!isset($CFG->bigbluebuttonbn['moderator_default']));
    }

    /**
     * @return boolean
     */
    public static function section_send_notifications_shown() {
        global $CFG;
        return (!isset($CFG->bigbluebuttonbn['sendnotifications_enabled']));
    }

    /**
     * @return boolean
     */
    public static function section_settings_extended_shown() {
        global $CFG;
        return (!isset($CFG->bigbluebuttonbn['recordingready_enabled']) ||
                !isset($CFG->bigbluebuttonbn['meetingevents_enabled']));
    }
}
