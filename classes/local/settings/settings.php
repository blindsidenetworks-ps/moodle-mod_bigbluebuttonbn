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
 * The mod_bigbluebuttonbn settings helper
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David  (laurent [at] call-learning [dt] fr)
 */

namespace mod_bigbluebuttonbn\local\settings;

use admin_category;
use admin_setting;
use admin_setting_configcheckbox;
use admin_setting_configmultiselect;
use admin_setting_configselect;
use admin_setting_configstoredfile;
use admin_setting_configtext;
use admin_setting_configtextarea;
use admin_setting_heading;
use admin_settingpage;
use lang_string;
use mod_bigbluebuttonbn\local\bbb_constants;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\local\helpers\roles;

defined('MOODLE_INTERNAL') || die();

/**
 * Utility class for all files routines helper
 *
 * @package mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings {

    /**
     * @var admin_setting shared value
     */
    private $admin;

    /**
     * Module is enabled ?
     * @var bool
     */
    private $moduleenabled;

    /**
     * Current section
     * @var
     */
    private $section;

    /**
     * settings constructor.
     *
     * @param admin_category $admin
     * @param object  $module
     * @param string $section for the plugin setting (main setting page)
     */
    public function __construct(&$admin, $module, $section) {
        $this->moduleenabled = $module->is_enabled() === true;
        $this->admin = $admin;

        $bbbcategorysection = $section.'cat';
        $modbigbluebuttobnfolder = new admin_category($bbbcategorysection,
            new lang_string('pluginname', 'mod_bigbluebuttonbn'),
            $module->is_enabled() === false);

        $admin->add('modsettings', $modbigbluebuttobnfolder);

        $mainsettings = $this->bigbluebuttonbn_settings_general($section);
        $admin->add($bbbcategorysection, $mainsettings);

        $this->section = $bbbcategorysection;

    }

    /**
     * Add the setting and lock it conditionally
     * @param string $name
     * @param admin_setting $item
     * @param admin_settingpage $settings
     */
    protected function add_conditional_element($name, $item, &$settings) {
        global $CFG;
        if (isset($CFG->bigbluebuttonbn) && isset($CFG->bigbluebuttonbn[$name])) {
            if ($item->config_read($item->name)) {
                // A value has been set, we can safely ommit the setting and it won't interfere with installation
                // process.
                // The idea behind it is to hide values from end-users in case we use multitenancy for example.
                // TODO: check if this approach is valid.
                return;
            }
        }
        $settings->add($item);
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     * @param string $sectioname
     *
     * @return admin_settingpage
     * @throws \coding_exception
     */
    public function bigbluebuttonbn_settings_general($sectioname) {
        $settingsgeneral = new admin_settingpage($sectioname, get_string('config_general', 'bigbluebuttonbn'),
            'moodle/site:config', !((boolean) validator::section_general_shown()) && ($this->moduleenabled));
        if ($this->admin->fulltree) {
            // Configuration for BigBlueButton.
            $item = new admin_setting_heading('bigbluebuttonbn_config_general',
                '',
                get_string('config_general_description', 'bigbluebuttonbn'));

            $settingsgeneral->add($item);
            $item = new admin_setting_configtext('bigbluebuttonbn_server_url',
                get_string('config_server_url', 'bigbluebuttonbn'),
                get_string('config_server_url_description', 'bigbluebuttonbn'),
                bbb_constants::BIGBLUEBUTTONBN_DEFAULT_SERVER_URL, PARAM_RAW);
            $this->add_conditional_element(
                'server_url',
                $item,
                $settingsgeneral
            );
            $item = new admin_setting_configtext('bigbluebuttonbn_shared_secret',
                get_string('config_shared_secret', 'bigbluebuttonbn'),
                get_string('config_shared_secret_description', 'bigbluebuttonbn'),
                bbb_constants::BIGBLUEBUTTONBN_DEFAULT_SHARED_SECRET, PARAM_RAW);
            $this->add_conditional_element(
                'shared_secret',
                $item,
                $settingsgeneral
            );
            $settingsgeneral->add($item);
            $item = new admin_setting_heading('bigbluebuttonbn_config_default_messages',
                get_string('config_default_messages', 'bigbluebuttonbn'),
                get_string('config_default_messages_description', 'bigbluebuttonbn'));
            $settingsgeneral->add($item);
            $item = new admin_setting_configtextarea('bigbluebuttonbn_welcome_default',
                get_string('config_welcome_default', 'bigbluebuttonbn'),
                get_string('config_welcome_default_description', 'bigbluebuttonbn'),
                '', PARAM_TEXT);
            $this->add_conditional_element(
                'welcome_default',
                $item,
                $settingsgeneral
            );
        }
        return $settingsgeneral;
    }

    /**
     * Helper function renders record settings if the feature is enabled.
     *
     * @return void
     */
    public function bigbluebuttonbn_settings_record() {
        // Configuration for 'recording' feature.
        $recordingsetting = new admin_settingpage('recording', get_string('config_recording', 'bigbluebuttonbn'),
            'moodle/site:config', !((boolean) validator::section_record_meeting_shown()) && ($this->moduleenabled));
        if ($this->admin->fulltree) {
            $item = new admin_setting_heading('bigbluebuttonbn_config_recording',
                '',
                get_string('config_recording_description', 'bigbluebuttonbn'));
            $recordingsetting->add($item);
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_recording_default',
                get_string('config_recording_default', 'bigbluebuttonbn'),
                get_string('config_recording_default_description', 'bigbluebuttonbn'),
                1);
            $this->add_conditional_element(
                'recording_default',
                $item,
                $recordingsetting
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_recording_editable',
                get_string('config_recording_editable', 'bigbluebuttonbn'),
                get_string('config_recording_editable_description', 'bigbluebuttonbn'),
                1);
            $this->add_conditional_element(
                'recording_editable',
                $item,
                $recordingsetting
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_recording_icons_enabled',
                get_string('config_recording_icons_enabled', 'bigbluebuttonbn'),
                get_string('config_recording_icons_enabled_description', 'bigbluebuttonbn'),
                1);
            $this->add_conditional_element(
                'recording_icons_enabled',
                $item,
                $recordingsetting
            );

            // Add recording start to load and allow/hide stop/pause.
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_recording_all_from_start_default',
                get_string('config_recording_all_from_start_default', 'bigbluebuttonbn'),
                get_string('config_recording_all_from_start_default_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'recording_all_from_start_default',
                $item,
                $recordingsetting
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_recording_all_from_start_editable',
                get_string('config_recording_all_from_start_editable', 'bigbluebuttonbn'),
                get_string('config_recording_all_from_start_editable_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'recording_all_from_start_editable',
                $item,
                $recordingsetting
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_recording_hide_button_default',
                get_string('config_recording_hide_button_default', 'bigbluebuttonbn'),
                get_string('config_recording_hide_button_default_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'recording_hide_button_default',
                $item,
                $recordingsetting
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_recording_hide_button_editable',
                get_string('config_recording_hide_button_editable', 'bigbluebuttonbn'),
                get_string('config_recording_hide_button_editable_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'recording_hide_button_editable',
                $item,
                $recordingsetting
            );
        }
        $this->admin->add($this->section, $recordingsetting);
    }

    /**
     * Helper function renders import recording settings if the feature is enabled.
     *
     *
     * @return void
     * @throws \coding_exception
     */
    public function bigbluebuttonbn_settings_importrecordings() {
        // Configuration for 'import recordings' feature.
        $importrecordingsettings = new admin_settingpage('importrecordings',
            get_string('config_importrecordings', 'bigbluebuttonbn'),
            'moodle/site:config', !((boolean) validator::section_import_recordings_shown()) && ($this->moduleenabled));
        if ($this->admin->fulltree) {
            $item = new admin_setting_heading('bigbluebuttonbn_config_importrecordings',
                '',
                get_string('config_importrecordings_description', 'bigbluebuttonbn'));
            $importrecordingsettings->add($item);
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_importrecordings_enabled',
                get_string('config_importrecordings_enabled', 'bigbluebuttonbn'),
                get_string('config_importrecordings_enabled_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'importrecordings_enabled',
                $item,
                $importrecordingsettings
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_importrecordings_from_deleted_enabled',
                get_string('config_importrecordings_from_deleted_enabled', 'bigbluebuttonbn'),
                get_string('config_importrecordings_from_deleted_enabled_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'importrecordings_from_deleted_enabled',
                $item,
                $importrecordingsettings
            );
        }
        $this->admin->add($this->section, $importrecordingsettings);
    }

    /**
     * Helper function renders show recording settings if the feature is enabled.
     *
     *
     * @return void
     */
    public function bigbluebuttonbn_settings_showrecordings() {
        // Configuration for 'show recordings' feature.
        $showrecordingsettings = new admin_settingpage('showrecordings',
            get_string('config_recordings', 'bigbluebuttonbn'),
            'moodle/site:config', !((boolean) validator::section_show_recordings_shown()) && ($this->moduleenabled));
        if ($this->admin->fulltree) {
            $item = new admin_setting_heading('bigbluebuttonbn_config_recordings',
                '',
                get_string('config_recordings_description', 'bigbluebuttonbn'));
            $showrecordingsettings->add($item);
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_recordings_html_default',
                get_string('config_recordings_html_default', 'bigbluebuttonbn'),
                get_string('config_recordings_html_default_description', 'bigbluebuttonbn'),
                1);
            $this->add_conditional_element(
                'recordings_html_default',
                $item,
                $showrecordingsettings
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_recordings_html_editable',
                get_string('config_recordings_html_editable', 'bigbluebuttonbn'),
                get_string('config_recordings_html_editable_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'recordings_html_editable',
                $item,
                $showrecordingsettings
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_recordings_deleted_default',
                get_string('config_recordings_deleted_default', 'bigbluebuttonbn'),
                get_string('config_recordings_deleted_default_description', 'bigbluebuttonbn'),
                1);
            $this->add_conditional_element(
                'recordings_deleted_default',
                $item,
                $showrecordingsettings
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_recordings_deleted_editable',
                get_string('config_recordings_deleted_editable', 'bigbluebuttonbn'),
                get_string('config_recordings_deleted_editable_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'recordings_deleted_editable',
                $item,
                $showrecordingsettings
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_recordings_imported_default',
                get_string('config_recordings_imported_default', 'bigbluebuttonbn'),
                get_string('config_recordings_imported_default_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'recordings_imported_default',
                $item,
                $showrecordingsettings
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_recordings_imported_editable',
                get_string('config_recordings_imported_editable', 'bigbluebuttonbn'),
                get_string('config_recordings_imported_editable_description', 'bigbluebuttonbn'),
                1);
            $this->add_conditional_element(
                'recordings_imported_editable',
                $item,
                $showrecordingsettings
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_recordings_preview_default',
                get_string('config_recordings_preview_default', 'bigbluebuttonbn'),
                get_string('config_recordings_preview_default_description', 'bigbluebuttonbn'),
                1);
            $this->add_conditional_element(
                'recordings_preview_default',
                $item,
                $showrecordingsettings
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_recordings_preview_editable',
                get_string('config_recordings_preview_editable', 'bigbluebuttonbn'),
                get_string('config_recordings_preview_editable_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'recordings_preview_editable',
                $item,
                $showrecordingsettings
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_recordings_sortorder',
                get_string('config_recordings_sortorder', 'bigbluebuttonbn'),
                get_string('config_recordings_sortorder_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'recordings_sortorder',
                $item,
                $showrecordingsettings
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_recordings_validate_url',
                get_string('config_recordings_validate_url', 'bigbluebuttonbn'),
                get_string('config_recordings_validate_url_description', 'bigbluebuttonbn'),
                1);
            $this->add_conditional_element(
                'recordings_validate_url',
                $item,
                $showrecordingsettings
            );
        }
        $this->admin->add($this->section, $showrecordingsettings);
    }

    /**
     * Helper function renders wait for moderator settings if the feature is enabled.
     *
     *
     * @return void
     */
    public function bigbluebuttonbn_settings_waitmoderator() {
        // Configuration for wait for moderator feature.
        $waitmoderatorsettings = new admin_settingpage('waitformoderator',
            get_string('config_waitformoderator', 'bigbluebuttonbn'),
            'moodle/site:config', !((boolean) validator::section_wait_moderator_shown()) && ($this->moduleenabled));
        if ($this->admin->fulltree) {
            $item = new admin_setting_heading('bigbluebuttonbn_config_waitformoderator',
                '',
                get_string('config_waitformoderator_description', 'bigbluebuttonbn'));
            $waitmoderatorsettings->add($item);
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_waitformoderator_default',
                get_string('config_waitformoderator_default', 'bigbluebuttonbn'),
                get_string('config_waitformoderator_default_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'waitformoderator_default',
                $item,
                $waitmoderatorsettings
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_waitformoderator_editable',
                get_string('config_waitformoderator_editable', 'bigbluebuttonbn'),
                get_string('config_waitformoderator_editable_description', 'bigbluebuttonbn'),
                1);
            $this->add_conditional_element(
                'waitformoderator_editable',
                $item,
                $waitmoderatorsettings
            );
            $item = new admin_setting_configtext('bigbluebuttonbn_waitformoderator_ping_interval',
                get_string('config_waitformoderator_ping_interval', 'bigbluebuttonbn'),
                get_string('config_waitformoderator_ping_interval_description', 'bigbluebuttonbn'),
                10, PARAM_INT);
            $this->add_conditional_element(
                'waitformoderator_ping_interval',
                $item,
                $waitmoderatorsettings
            );
            $item = new admin_setting_configtext('bigbluebuttonbn_waitformoderator_cache_ttl',
                get_string('config_waitformoderator_cache_ttl', 'bigbluebuttonbn'),
                get_string('config_waitformoderator_cache_ttl_description', 'bigbluebuttonbn'),
                60, PARAM_INT);
            $this->add_conditional_element(
                'waitformoderator_cache_ttl',
                $item,
                $waitmoderatorsettings
            );
        }
        $this->admin->add($this->section, $waitmoderatorsettings);
    }

    /**
     * Helper function renders static voice bridge settings if the feature is enabled.
     *
     *
     * @return void
     */
    public function bigbluebuttonbn_settings_voicebridge() {
        // Configuration for "static voice bridge" feature.
        $voicebridgesettings = new admin_settingpage('voicebridge',
            get_string('config_voicebridge', 'bigbluebuttonbn'),
            'moodle/site:config', !((boolean) validator::section_static_voice_bridge_shown())
            && ($this->moduleenabled));
        if ($this->admin->fulltree) {
            $item = new admin_setting_heading('bigbluebuttonbn_config_voicebridge',
                '',
                get_string('config_voicebridge_description', 'bigbluebuttonbn'));
            $voicebridgesettings->add($item);
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_voicebridge_editable',
                get_string('config_voicebridge_editable', 'bigbluebuttonbn'),
                get_string('config_voicebridge_editable_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'voicebridge_editable',
                $item,
                $voicebridgesettings
            );
        }
        $this->admin->add($this->section, $voicebridgesettings);
    }

    /**
     * Helper function renders preuploaded presentation settings if the feature is enabled.
     *
     *
     * @return void
     */
    public function bigbluebuttonbn_settings_preupload() {
        // Configuration for "preupload presentation" feature.
        $preuploadsettings = new admin_settingpage('preupload',
            get_string('config_preuploadpresentation', 'bigbluebuttonbn'),
            'moodle/site:config', !((boolean) validator::section_preupload_presentation_shown())
            && ($this->moduleenabled));
        if ($this->admin->fulltree) {
            // This feature only works if curl is installed (but it is as now required by Moodle). The checks have been removed.
            $item = new admin_setting_heading('bigbluebuttonbn_config_preuploadpresentation',
                '',
                get_string('config_preuploadpresentation_description', 'bigbluebuttonbn'));
            $preuploadsettings->add($item);

            $item = new admin_setting_configcheckbox('bigbluebuttonbn_preuploadpresentation_enabled',
                get_string('config_preuploadpresentation_enabled', 'bigbluebuttonbn'),
                get_string('config_preuploadpresentation_enabled_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'preuploadpresentation_enabled',
                $item,
                $preuploadsettings
            );
            // Note: checks on curl library have been removed as it is a requirement from Moodle.
            $filemanageroptions = array();
            $filemanageroptions['accepted_types'] = '*';
            $filemanageroptions['maxbytes'] = 0;
            $filemanageroptions['subdirs'] = 0;
            $filemanageroptions['maxfiles'] = 1;
            $filemanageroptions['mainfile'] = true;

            $filemanager = new admin_setting_configstoredfile('mod_bigbluebuttonbn/presentationdefault',
                get_string('config_presentation_default', 'bigbluebuttonbn'),
                get_string('config_presentation_default_description', 'bigbluebuttonbn'),
                'presentationdefault',
                0,
                $filemanageroptions);

            $preuploadsettings->add($filemanager);
        }
        $this->admin->add($this->section, $preuploadsettings);

    }

    /**
     * Helper function renders userlimit settings if the feature is enabled.
     *
     *
     * @return void
     */
    public function bigbluebuttonbn_settings_userlimit() {
        $userlimitsettings = new admin_settingpage('userlimit',
            get_string('config_userlimit', 'bigbluebuttonbn'),
            'moodle/site:config', !((boolean) validator::section_user_limit_shown())
            && ($this->moduleenabled));
        if ($this->admin->fulltree) {
            // Configuration for "user limit" feature.
            $item = new admin_setting_heading('bigbluebuttonbn_config_userlimit',
                '',
                get_string('config_userlimit_description', 'bigbluebuttonbn'));
            $userlimitsettings->add($item);
            $item = new admin_setting_configtext('bigbluebuttonbn_userlimit_default',
                get_string('config_userlimit_default', 'bigbluebuttonbn'),
                get_string('config_userlimit_default_description', 'bigbluebuttonbn'),
                0, PARAM_INT);
            $this->add_conditional_element(
                'userlimit_default',
                $item,
                $userlimitsettings
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_userlimit_editable',
                get_string('config_userlimit_editable', 'bigbluebuttonbn'),
                get_string('config_userlimit_editable_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'userlimit_editable',
                $item,
                $userlimitsettings
            );
        }
        $this->admin->add($this->section, $userlimitsettings);
    }

    /**
     * Helper function renders participant settings if the feature is enabled.
     *
     *
     * @return void
     */
    public function bigbluebuttonbn_settings_participants() {
        // Configuration for defining the default role/user that will be moderator on new activities.
        $participantsettings = new admin_settingpage('participant',
            get_string('config_participant', 'bigbluebuttonbn'),
            'moodle/site:config', !((boolean) validator::section_moderator_default_shown())
            && ($this->moduleenabled));
        if ($this->admin->fulltree) {
            $item = new admin_setting_heading('bigbluebuttonbn_config_participant',
                '',
                get_string('config_participant_description', 'bigbluebuttonbn'));
            $participantsettings->add($item);
            // UI for 'participants' feature.
            $roles = roles::bigbluebuttonbn_get_roles(null, false);
            $owner = array('0' => get_string('mod_form_field_participant_list_type_owner', 'bigbluebuttonbn'));
            $item = new admin_setting_configmultiselect('bigbluebuttonbn_participant_moderator_default',
                get_string('config_participant_moderator_default', 'bigbluebuttonbn'),
                get_string('config_participant_moderator_default_description', 'bigbluebuttonbn'),
                array_keys($owner), $owner + $roles);
            $this->add_conditional_element(
                'participant_moderator_default',
                $item,
                $participantsettings
            );
        }
        $this->admin->add($this->section, $participantsettings);
    }

    /**
     * Helper function renders notification settings if the feature is enabled.
     *
     *
     * @return void
     */
    public function bigbluebuttonbn_settings_notifications() {
        // Configuration for "send notifications" feature.
        $notificationssettings = new admin_settingpage('bigbluebuttonmnotifications',
            get_string('config_sendnotifications', 'bigbluebuttonbn'),
            'moodle/site:config', !((boolean) validator::section_send_notifications_shown())
            && ($this->moduleenabled));
        if ($this->admin->fulltree) {
            $item = new admin_setting_heading('bigbluebuttonbn_config_sendnotifications',
                '',
                get_string('config_sendnotifications_description', 'bigbluebuttonbn'));
            $notificationssettings->add($item);
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_sendnotifications_enabled',
                get_string('config_sendnotifications_enabled', 'bigbluebuttonbn'),
                get_string('config_sendnotifications_enabled_description', 'bigbluebuttonbn'),
                1);
            $this->add_conditional_element(
                'sendnotifications_enabled',
                $item,
                $notificationssettings
            );
        }
        $this->admin->add($this->section, $notificationssettings);
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     *
     * @return void
     */
    public function bigbluebuttonbn_settings_muteonstart() {
        // Configuration for BigBlueButton.
        $muteonstartsetting = new admin_settingpage('muteonstart',
            get_string('config_muteonstart', 'bigbluebuttonbn'),
            'moodle/site:config', !((boolean) validator::section_muteonstart_shown())
            && ($this->moduleenabled));
        if ($this->admin->fulltree) {
            $item = new admin_setting_heading('bigbluebuttonbn_config_muteonstart',
                '',
                get_string('config_muteonstart_description', 'bigbluebuttonbn'));
            $muteonstartsetting->add($item);
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_muteonstart_default',
                get_string('config_muteonstart_default', 'bigbluebuttonbn'),
                get_string('config_muteonstart_default_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'muteonstart_default',
                $item,
                $muteonstartsetting
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_muteonstart_editable',
                get_string('config_muteonstart_editable', 'bigbluebuttonbn'),
                get_string('config_muteonstart_editable_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'muteonstart_editable',
                $item,
                $muteonstartsetting
            );
        }
        $this->admin->add($this->section, $muteonstartsetting);
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     *
     * @return void
     */
    public function bigbluebuttonbn_settings_locksettings() {
        $category = new admin_category('bigbluebuttonbnlocksettings',
            get_string('config_locksettings', 'bigbluebuttonbn'),
            get_string('config_locksettings_description', 'bigbluebuttonbn'));
        $this->admin->add($this->section, $category);
        // Configuration for various lock settings for meetings.
        $this->bigbluebuttonbn_settings_disablecam();
        $this->bigbluebuttonbn_settings_disablemic();
        $this->bigbluebuttonbn_settings_disablepublicchat();
        $this->bigbluebuttonbn_settings_disablenote();
        $this->bigbluebuttonbn_settings_hideuserlist();
        $this->bigbluebuttonbn_settings_lockedlayout();
        $this->bigbluebuttonbn_settings_lockonjoin();
        $this->bigbluebuttonbn_settings_lockonjoinconfigurable();
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     *
     * @return void
     */
    public function bigbluebuttonbn_settings_disablecam() {
        // Configuration for BigBlueButton.
        $disablecamsettings = new admin_settingpage('disablecam',
            get_string('config_disablecam_default', 'bigbluebuttonbn'),
            'moodle/site:config', !((boolean) validator::section_disablecam_shown())
            && ($this->moduleenabled));
        if ($this->admin->fulltree) {
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_disablecam_default',
                get_string('config_disablecam_default', 'bigbluebuttonbn'),
                get_string('config_disablecam_default_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'disablecam_default',
                $item,
                $disablecamsettings
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_disablecam_editable',
                get_string('config_disablecam_editable', 'bigbluebuttonbn'),
                get_string('config_disablecam_editable_description', 'bigbluebuttonbn'),
                1);
            $this->add_conditional_element(
                'disablecam_editable',
                $item,
                $disablecamsettings
            );
        }
        $this->admin->add('bigbluebuttonbnlocksettings', $disablecamsettings);
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     *
     * @return void
     */
    public function bigbluebuttonbn_settings_disablemic() {
        // Configuration for BigBlueButton.
        $disablemicsetting = new admin_settingpage('disablemic',
            get_string('config_disablemic_default', 'bigbluebuttonbn'),
            'moodle/site:config', !((boolean) validator::section_disablemic_shown())
            && ($this->moduleenabled));
        if ($this->admin->fulltree) {
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_disablemic_default',
                get_string('config_disablemic_default', 'bigbluebuttonbn'),
                get_string('config_disablemic_default_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'disablemic_default',
                $item,
                $disablemicsetting
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_disablemic_editable',
                get_string('config_disablemic_editable', 'bigbluebuttonbn'),
                get_string('config_disablemic_editable_description', 'bigbluebuttonbn'),
                1);
            $this->add_conditional_element(
                'disablecam_editable',
                $item,
                $disablemicsetting
            );
        }
        $this->admin->add('bigbluebuttonbnlocksettings', $disablemicsetting);
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     *
     * @return void
     * @throws \coding_exception
     */
    public function bigbluebuttonbn_settings_disableprivatechat() {
        // Configuration for BigBlueButton.
        $disableprivatechatsetting = new admin_settingpage('disableprivatechat',
            get_string('config_disableprivatechat_default', 'bigbluebuttonbn'),
            'moodle/site:config', !((boolean) validator::section_disableprivatechat_shown())
            && ($this->moduleenabled));
        if ($this->admin->fulltree) {
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_disableprivatechat_default',
                get_string('config_disableprivatechat_default', 'bigbluebuttonbn'),
                get_string('config_disableprivatechat_default_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'disableprivatechat_default',
                $item,
                $disableprivatechatsetting
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_disableprivatechat_editable',
                get_string('config_disableprivatechat_editable', 'bigbluebuttonbn'),
                get_string('config_disableprivatechat_editable_description', 'bigbluebuttonbn'),
                1);
            $this->add_conditional_element(
                'disableprivatechat_editable',
                $item,
                $disableprivatechatsetting
            );
        }
        $this->admin->add('bigbluebuttonbnlocksettings', $disableprivatechatsetting);
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     *
     * @return void
     */
    public function bigbluebuttonbn_settings_disablepublicchat() {
        // Configuration for BigBlueButton.
        $disablepublicchatsetting = new admin_settingpage('disablepublicchat',
            get_string('config_disableprivatechat_default', 'bigbluebuttonbn'),
            'moodle/site:config', !((boolean) validator::section_disablepublicchat_shown())
            && ($this->moduleenabled));
        if ($this->admin->fulltree) {
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_disablepublicchat_default',
                get_string('config_disablepublicchat_default', 'bigbluebuttonbn'),
                get_string('config_disablepublicchat_default_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'disablepublicchat_default',
                $item,
                $disablepublicchatsetting
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_disablepublicchat_editable',
                get_string('config_disablepublicchat_editable', 'bigbluebuttonbn'),
                get_string('config_disablepublicchat_editable_description', 'bigbluebuttonbn'),
                1);
            $this->add_conditional_element(
                'disablepublicchat_editable',
                $item,
                $disablepublicchatsetting
            );
        }
        $this->admin->add('bigbluebuttonbnlocksettings', $disablepublicchatsetting);
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     *
     * @return void
     */
    public function bigbluebuttonbn_settings_disablenote() {
        // Configuration for BigBlueButton.
        $disablenotesetting = new admin_settingpage('disablenote',
            get_string('config_disablenote_default', 'bigbluebuttonbn'),
            'moodle/site:config', !((boolean) validator::section_disablenote_shown())
            && ($this->moduleenabled));
        if ($this->admin->fulltree) {
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_disablenote_default',
                get_string('config_disablenote_default', 'bigbluebuttonbn'),
                get_string('config_disablenote_default_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'disablenote_default',
                $item,
                $disablenotesetting
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_disablenote_editable',
                get_string('config_disablenote_editable', 'bigbluebuttonbn'),
                get_string('config_disablenote_editable_description', 'bigbluebuttonbn'),
                1);
            $this->add_conditional_element(
                'disablenote_editable',
                $item,
                $disablenotesetting
            );
        }
        $this->admin->add('bigbluebuttonbnlocksettings', $disablenotesetting);
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     *
     * @return void
     */
    public function bigbluebuttonbn_settings_hideuserlist() {
        // Configuration for BigBlueButton.
        $hideuserlistsetting = new admin_settingpage('hideuserlist',
            get_string('config_hideuserlist_default', 'bigbluebuttonbn'),
            'moodle/site:config', !((boolean) validator::section_hideuserlist_shown())
            && ($this->moduleenabled));
        if ($this->admin->fulltree) {
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_hideuserlist_default',
                get_string('config_hideuserlist_default', 'bigbluebuttonbn'),
                get_string('config_hideuserlist_default_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'hideuserlist_default',
                $item,
                $hideuserlistsetting
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_hideuserlist_editable',
                get_string('config_hideuserlist_editable', 'bigbluebuttonbn'),
                get_string('config_hideuserlist_editable_description', 'bigbluebuttonbn'),
                1);
            $this->add_conditional_element(
                'hideuserlist_editable',
                $item,
                $hideuserlistsetting
            );
        }
        $this->admin->add('bigbluebuttonbnlocksettings', $hideuserlistsetting);
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     *
     * @return void
     */
    public function bigbluebuttonbn_settings_lockedlayout() {
        // Configuration for BigBlueButton.
        $lockedlayoutsetting = new admin_settingpage('lockedlayout',
            get_string('config_lockedlayout_default', 'bigbluebuttonbn'),
            'moodle/site:config', !((boolean) validator::section_lockedlayout_shown())
            && ($this->moduleenabled));
        if ($this->admin->fulltree) {
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_lockedlayout_default',
                get_string('config_lockedlayout_default', 'bigbluebuttonbn'),
                get_string('config_lockedlayout_default_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'lockedlayout_default',
                $item,
                $lockedlayoutsetting
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_lockedlayout_editable',
                get_string('config_lockedlayout_editable', 'bigbluebuttonbn'),
                get_string('config_lockedlayout_editable_description', 'bigbluebuttonbn'),
                1);
            $this->add_conditional_element(
                'lockedlayout_editable',
                $item,
                $lockedlayoutsetting
            );
        }
        $this->admin->add('bigbluebuttonbnlocksettings', $lockedlayoutsetting);
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     *
     * @return void
     */
    public function bigbluebuttonbn_settings_lockonjoin() {
        // Configuration for BigBlueButton.
        $lockonjoinsetting = new admin_settingpage('lockonjoin',
            get_string('config_lockonjoin_default', 'bigbluebuttonbn'),
            'moodle/site:config', !((boolean) validator::section_lockonjoin_shown())
            && ($this->moduleenabled));
        if ($this->admin->fulltree) {
            if ((boolean) validator::section_lockonjoin_shown()) {
                $item = new admin_setting_configcheckbox('bigbluebuttonbn_lockonjoin_default',
                    get_string('config_lockonjoin_default', 'bigbluebuttonbn'),
                    get_string('config_lockonjoin_default_description', 'bigbluebuttonbn'),
                    0);
                $this->add_conditional_element(
                    'lockonjoin_default',
                    $item,
                    $lockonjoinsetting
                );
                $item = new admin_setting_configcheckbox('bigbluebuttonbn_lockonjoin_editable',
                    get_string('config_lockonjoin_editable', 'bigbluebuttonbn'),
                    get_string('config_lockonjoin_editable_description', 'bigbluebuttonbn'),
                    1);
                $this->add_conditional_element(
                    'lockonjoin_editable',
                    $item,
                    $lockonjoinsetting
                );
            }
        }
        $this->admin->add('bigbluebuttonbnlocksettings', $lockonjoinsetting);
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     *
     * @return void
     */
    public function bigbluebuttonbn_settings_lockonjoinconfigurable() {
        // Configuration for BigBlueButton.
        $lockonjoinconfigurablesetting = new admin_settingpage('lockonjoinconfigurable',
            get_string('config_lockonjoinconfigurable_default', 'bigbluebuttonbn'),
            'moodle/site:config', !((boolean) validator::section_lockonjoinconfigurable_shown())
            && ($this->moduleenabled));
        if ($this->admin->fulltree) {
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_lockonjoinconfigurable_default',
                get_string('config_lockonjoinconfigurable_default', 'bigbluebuttonbn'),
                get_string('config_lockonjoinconfigurable_default_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'lockonjoinconfigurable_default',
                $item,
                $lockonjoinconfigurablesetting
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_lockonjoinconfigurable_editable',
                get_string('config_lockonjoinconfigurable_editable', 'bigbluebuttonbn'),
                get_string('config_lockonjoinconfigurable_editable_description', 'bigbluebuttonbn'),
                1);
            $this->add_conditional_element(
                'lockonjoinconfigurable_editable',
                $item,
                $lockonjoinconfigurablesetting
            );
        }
        $this->admin->add('bigbluebuttonbnlocksettings', $lockonjoinconfigurablesetting);
    }

    /**
     * Helper function renders extended settings if any of the features there is enabled.
     *
     *
     * @return void
     * @throws \coding_exception
     */
    public function bigbluebuttonbn_settings_extended() {
        // Configuration for extended capabilities.
        $extendedcapabilitiessetting = new admin_settingpage('extendedcapabilities',
            get_string('config_extended_capabilities', 'bigbluebuttonbn'),
            'moodle/site:config', !((boolean) validator::section_settings_extended_shown())
            && ($this->moduleenabled));
        if ($this->admin->fulltree) {
            $item = new admin_setting_heading('bigbluebuttonbn_config_extended_capabilities',
                '',
                get_string('config_extended_capabilities_description', 'bigbluebuttonbn'));
            $extendedcapabilitiessetting->add($item);
            // UI for 'notify users when recording ready' feature.
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_recordingready_enabled',
                get_string('config_recordingready_enabled', 'bigbluebuttonbn'),
                get_string('config_recordingready_enabled_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'recordingready_enabled',
                $item,
                $extendedcapabilitiessetting
            );
        }
        $this->admin->add($this->section, $extendedcapabilitiessetting);
        // Configuration for extended BN capabilities should go here.
    }

    /**
     * Helper function renders experimental settings if any of the features there is enabled.
     *
     * @return void
     */
    public function bigbluebuttonbn_settings_experimental() {
        // Configuration for experimental features should go here.
        $experimentalfeaturessetting = new admin_settingpage('experimentalfeatures',
            get_string('config_experimental_features', 'bigbluebuttonbn'),
            'moodle/site:config', !((boolean) validator::section_settings_extended_shown())
            && ($this->moduleenabled));
        if ($this->admin->fulltree) {
            $item = new admin_setting_heading('bigbluebuttonbn_config_experimental_features',
                '',
                get_string('config_experimental_features_description', 'bigbluebuttonbn'));
            $experimentalfeaturessetting->add($item);
            // UI for 'register meeting events' feature.
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_meetingevents_enabled',
                get_string('config_meetingevents_enabled', 'bigbluebuttonbn'),
                get_string('config_meetingevents_enabled_description', 'bigbluebuttonbn'),
                0);
            $this->add_conditional_element(
                'meetingevents_enabled',
                $item,
                $experimentalfeaturessetting
            );
        }
        $this->admin->add($this->section, $experimentalfeaturessetting);
    }

    /**
     * Helper function renders Opencast integration settings if block_opencast is installed.
     *
     *
     * @return void
     */
    function bigbluebuttonbn_settings_opencast_integration() {
        // Configuration for 'Opencast integration' feature when Opencast plugins are installed.
        // Through validator::section_opencast_shown(), it checks if the block_opencast is installed.
        $opencastrecordingsetting = new admin_settingpage('opencast', get_string('config_opencast', 'bigbluebuttonbn'),
            'moodle/site:config', !((boolean) validator::section_opencast_shown()) && ($this->moduleenabled));
        if ($this->admin->fulltree) {
            $item = new admin_setting_heading('bigbluebuttonbn_config_opencast_recording',
                '',
                get_string('config_opencast_description', 'bigbluebuttonbn'));
            $opencastrecordingsetting->add($item);
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_opencast_recording',
                get_string('config_opencast_recording', 'bigbluebuttonbn'),
                get_string('config_opencast_recording_description', 'bigbluebuttonbn'),
                1);
            $this->add_conditional_element(
                'opencast_recording',
                $item,
                $opencastrecordingsetting
            );
            $item = new admin_setting_configcheckbox('bigbluebuttonbn_opencast_show_recording',
                get_string('config_opencast_show_recording', 'bigbluebuttonbn'),
                get_string('config_opencast_show_recording_description', 'bigbluebuttonbn'),
                1);
            $this->add_conditional_element(
                'opencast_show_recording',
                $item,
                $opencastrecordingsetting
            );
           
        }
        $this->admin->add($this->section, $opencastrecordingsetting);
    }

}