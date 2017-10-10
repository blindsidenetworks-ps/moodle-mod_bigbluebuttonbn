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
 * The mod_bigbluebuttonbn locallib/config.
 *
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2017 - present Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

namespace mod_bigbluebuttonbn\locallib;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');

class config {

    /**
     * @return string
     */
    public static function get_moodle_version_major() {
        global $CFG;
        $versionarray = explode('.', $CFG->version);
        return $versionarray[0];
    }

    /**
     * @return array
     */
    public static function defaultvalues() {
        return array(
            'server_url' => (string) BIGBLUEBUTTONBN_DEFAULT_SERVER_URL,
            'shared_secret' => (string) BIGBLUEBUTTONBN_DEFAULT_SHARED_SECRET,
            'importrecordings_enabled' => 'false',
            'voicebridge_editable' => 'false',
            'importrecordings_enabled' => 'false',
            'importrecordings_from_deleted_enabled' => 'false',
            'waitformoderator_default' => 'false',
            'waitformoderator_editable' => 'true',
            'waitformoderator_ping_interval' => '10',
            'waitformoderator_cache_ttl' => '60',
            'userlimit_default' => '0',
            'userlimit_editable' => 'false',
            'preuploadpresentation_enabled' => 'false',
            'sendnotifications_enabled' => 'false',
            'recordingready_enabled' => 'false',
            'recordingstatus_enabled' => 'false',
            'meetingevents_enabled' => 'false',
            'participant_moderator_default' => 'owner',
            'scheduled_duration_enabled' => 'false',
            'scheduled_duration_compensation' => '10',
            'scheduled_pre_opening' => '10',
            'recordings_enabled' => true,
            'recordings_html_default' => 'false',
            'recordings_html_editable' => 'false',
            'recordings_deleted_default' => 'false',
            'recordings_deleted_editable' => 'false',
            'recordings_imported_default' => 'false',
            'recordings_imported_editable' => 'false',
            'recording_default' => 'true',
            'recording_editable' => 'true',
            'recording_icons_enabled' => 'true',
            'general_warning_message' => '',
            'general_warning_button_text' => '',
            'general_warning_button_href' => '',
            'general_warning_button_class' => '',
        );
    }

    /**
     * @return string
     */
    public static function defaultvalue($setting) {
        $defaultvalues = self::defaultvalues();
        if (!array_key_exists($setting, $defaultvalues)) {
            return;
        }
        return $defaultvalues[$setting];
    }

    /**
     * @return string
     */
    public static function get($setting) {
        global $CFG;
        if (isset($CFG->bigbluebuttonbn[$setting])) {
            return (string) $CFG->bigbluebuttonbn[$setting];
        }
        if (isset($CFG->{'bigbluebuttonbn_'.$setting})) {
            return (string)$CFG->{'bigbluebuttonbn_'.$setting};
        }
        return  self::defaultvalue($setting);
    }

    /**
     * @return boolean
     */
    public static function recordings_enabled() {
        return self::get('recordings_enabled');
    }

    /**
     * @return array
     */
    public static function get_options() {
        return [
               'version_major' => self::get_moodle_version_major(),
               'voicebridge_editable' => self::get('voicebridge_editable'),
               'waitformoderator_default' => self::get('waitformoderator_default'),
               'waitformoderator_editable' => self::get('waitformoderator_editable'),
               'userlimit_default' => self::get('userlimit_default'),
               'userlimit_editable' => self::get('userlimit_editable'),
               'preuploadpresentation_enabled' => self::get('preuploadpresentation_enabled'),
               'sendnotifications_enabled' => self::get('sendnotifications_enabled'),
               'recordings_enabled' => self::get('recordings_enabled'),
               'recordings_html_default' => self::get('recordings_html_default'),
               'recordings_html_editable' => self::get('recordings_html_editable'),
               'recordings_deleted_default' => self::get('recordings_deleted_default'),
               'recordings_deleted_editable' => self::get('recordings_deleted_editable'),
               'recordings_imported_default' => self::get('recordings_imported_default'),
               'recordings_imported_editable' => self::get('recordings_imported_editable'),
               'recording_default' => self::get('recording_default'),
               'recording_editable' => self::get('recording_editable'),
               'recording_icons_enabled' => self::get('recording_icons_enabled'),
               'general_warning_message' => self::get('general_warning_message'),
               'general_warning_button_text' => self::get('general_warning_button_text'),
               'general_warning_button_href' => self::get('general_warning_button_href'),
               'general_warning_button_class' => self::get('general_warning_button_class'),
          ];
    }
}
