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
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\local;
defined('MOODLE_INTERNAL') || die();

/**
 * Handles the global configuration based on config.php.
 *
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class config {

    /**
     * Returns moodle version.
     *
     * @return string
     */
    public static function get_moodle_version_major() {
        global $CFG;
        $versionarray = explode('.', $CFG->version);
        return $versionarray[0];
    }

    /**
     * Returns configuration default values.
     *
     * @return array
     */
    public static function defaultvalues() {
        return array(
            'server_url' => (string) bbb_constants::BIGBLUEBUTTONBN_DEFAULT_SERVER_URL,
            'shared_secret' => (string) bbb_constants::BIGBLUEBUTTONBN_DEFAULT_SHARED_SECRET,
            'voicebridge_editable' => false,
            'importrecordings_enabled' => false,
            'importrecordings_from_deleted_enabled' => false,
            'waitformoderator_default' => false,
            'waitformoderator_editable' => true,
            'waitformoderator_ping_interval' => '10',
            'waitformoderator_cache_ttl' => '60',
            'userlimit_default' => '0',
            'userlimit_editable' => false,
            'preuploadpresentation_enabled' => false,
            'sendnotifications_enabled' => false,
            'recordingready_enabled' => false,
            'recordingstatus_enabled' => false,
            'meetingevents_enabled' => false,
            'participant_moderator_default' => '0',
            'scheduled_pre_opening' => '10',
            'recordings_enabled' => true,
            'recordings_html_default' => false,
            'recordings_html_editable' => false,
            'recordings_deleted_default' => false,
            'recordings_deleted_editable' => false,
            'recordings_imported_default' => false,
            'recordings_imported_editable' => false,
            'recordings_preview_default' => true,
            'recordings_preview_editable' => false,
            'recordings_validate_url' => true,
            'recording_default' => true,
            'recording_editable' => true,
            'recording_icons_enabled' => true,
            'recording_all_from_start_default' => false,
            'recording_all_from_start_editable' => false,
            'recording_hide_button_default' => false,
            'recording_hide_button_editable' => false,
            'general_warning_message' => '',
            'general_warning_roles' => 'editingteacher,teacher',
            'general_warning_box_type' => 'info',
            'general_warning_button_text' => '',
            'general_warning_button_href' => '',
            'general_warning_button_class' => '',
            'muteonstart_default' => false,
            'muteonstart_editable' => false,
            'disablecam_default' => false,
            'disablecam_editable' => true,
            'disablemic_default' => false,
            'disablemic_editable' => true,
            'disableprivatechat_default' => false,
            'disableprivatechat_editable' => true,
            'disablepublicchat_default' => false,
            'disablepublicchat_editable' => true,
            'disablenote_default' => false,
            'disablenote_editable' => true,
            'hideuserlist_default' => false,
            'hideuserlist_editable' => true,
            'lockedlayout_default' => false,
            'lockedlayout_editable' => true,
            'lockonjoin_default' => false,
            'lockonjoin_editable' => true,
            'lockonjoinconfigurable_default' => false,
            'lockonjoinconfigurable_editable' => true,
            'welcome_default' => '',
        );
    }

    /**
     * Returns default value for an specific setting.
     *
     * @param string $setting
     * @return string
     */
    public static function defaultvalue($setting) {
        $defaultvalues = self::defaultvalues();
        if (!array_key_exists($setting, $defaultvalues)) {
            return null;
        }
        return $defaultvalues[$setting];
    }

    /**
     * Returns value for an specific setting.
     *
     * @param string $setting
     * @return string
     */
    public static function get($setting) {
        global $CFG;
        if (isset($CFG->bigbluebuttonbn[$setting])) {
            return (string)$CFG->bigbluebuttonbn[$setting];
        }
        if (isset($CFG->{'bigbluebuttonbn_'.$setting})) {
            return (string)$CFG->{'bigbluebuttonbn_'.$setting};
        }
        return self::defaultvalue($setting);
    }

    /**
     * Validates if recording settings are enabled.
     *
     * @return boolean
     */
    public static function recordings_enabled() {
        return (boolean)self::get('recordings_enabled');
    }

    /**
     * Validates if imported recording settings are enabled.
     *
     * @return boolean
     */
    public static function importrecordings_enabled() {
        return (boolean)self::get('importrecordings_enabled');
    }

    /**
     * Wraps current settings in an array.
     *
     * @return array
     */
    public static function get_options() {
        return array(
               'version_major' => self::get_moodle_version_major(),
               'voicebridge_editable' => self::get('voicebridge_editable'),
               'importrecordings_enabled' => self::get('importrecordings_enabled'),
               'importrecordings_from_deleted_enabled' => self::get('importrecordings_from_deleted_enabled'),
               'waitformoderator_default' => self::get('waitformoderator_default'),
               'waitformoderator_editable' => self::get('waitformoderator_editable'),
               'userlimit_default' => self::get('userlimit_default'),
               'userlimit_editable' => self::get('userlimit_editable'),
               'preuploadpresentation_enabled' => self::get('preuploadpresentation_enabled'),
               'sendnotifications_enabled' => self::get('sendnotifications_enabled'),
               'recordings_enabled' => self::get('recordings_enabled'),
               'meetingevents_enabled' => self::get('meetingevents_enabled'),
               'recordings_html_default' => self::get('recordings_html_default'),
               'recordings_html_editable' => self::get('recordings_html_editable'),
               'recordings_deleted_default' => self::get('recordings_deleted_default'),
               'recordings_deleted_editable' => self::get('recordings_deleted_editable'),
               'recordings_imported_default' => self::get('recordings_imported_default'),
               'recordings_imported_editable' => self::get('recordings_imported_editable'),
               'recordings_preview_default' => self::get('recordings_preview_default'),
               'recordings_preview_editable' => self::get('recordings_preview_editable'),
               'recordings_validate_url' => self::get('recordings_validate_url'),
               'recording_default' => self::get('recording_default'),
               'recording_editable' => self::get('recording_editable'),
               'recording_icons_enabled' => self::get('recording_icons_enabled'),
               'recording_all_from_start_default' => self::get('recording_all_from_start_default'),
               'recording_all_from_start_editable' => self::get('recording_all_from_start_editable'),
               'recording_hide_button_default' => self::get('recording_hide_button_default'),
               'recording_hide_button_editable' => self::get('recording_hide_button_editable'),
               'general_warning_message' => self::get('general_warning_message'),
               'general_warning_box_type' => self::get('general_warning_box_type'),
               'general_warning_button_text' => self::get('general_warning_button_text'),
               'general_warning_button_href' => self::get('general_warning_button_href'),
               'general_warning_button_class' => self::get('general_warning_button_class'),
               'muteonstart_editable' => self::get('muteonstart_editable'),
               'muteonstart_default' => self::get('muteonstart_default'),
               'disablecam_editable' => self::get('disablecam_editable'),
               'disablecam_default' => self::get('disablecam_default'),
               'disablemic_editable' => self::get('disablemic_editable'),
               'disablemic_default' => self::get('disablemic_default'),
               'disableprivatechat_editable' => self::get('disableprivatechat_editable'),
               'disableprivatechat_default' => self::get('disableprivatechat_default'),
               'disablepublicchat_editable' => self::get('disablepublicchat_editable'),
               'disablepublicchat_default' => self::get('disablepublicchat_default'),
               'disablenote_editable' => self::get('disablenote_editable'),
               'disablenote_default' => self::get('disablenote_default'),
               'hideuserlist_editable' => self::get('hideuserlist_editable'),
               'hideuserlist_default' => self::get('hideuserlist_default'),
               'lockedlayout_editable' => self::get('lockedlayout_editable'),
               'lockedlayout_default' => self::get('lockedlayout_default'),
               'lockonjoin_editable' => self::get('lockonjoin_editable'),
               'lockonjoin_default' => self::get('lockonjoin_default'),
               'lockonjoinconfigurable_editable' => self::get('lockonjoinconfigurable_editable'),
               'lockonjoinconfigurable_default' => self::get('lockonjoinconfigurable_default'),
               'welcome_default' => self::get('welcome_default'),
          );
    }

    /**
     * Helper function returns an array with enabled features for an specific profile type.
     *
     * @param array $typeprofiles
     * @param string $type
     *
     * @return array
     */
    public static function bigbluebuttonbn_get_enabled_features($typeprofiles, $type = null) {
        $enabledfeatures = array();
        $features = $typeprofiles[bbb_constants::BIGBLUEBUTTONBN_TYPE_ALL]['features'];
        if (!is_null($type) && key_exists($type, $typeprofiles)) {
            $features = $typeprofiles[$type]['features'];
        }
        $enabledfeatures['showroom'] = (in_array('all', $features) || in_array('showroom', $features));
        // Evaluates if recordings are enabled for the Moodle site.
        $enabledfeatures['showrecordings'] = false;
        if (self::recordings_enabled()) {
            $enabledfeatures['showrecordings'] = (in_array('all', $features) || in_array('showrecordings', $features));
        }
        $enabledfeatures['importrecordings'] = false;
        if (self::importrecordings_enabled()) {
            $enabledfeatures['importrecordings'] = (in_array('all', $features) || in_array('importrecordings', $features));
        }
        return $enabledfeatures;
    }
}
