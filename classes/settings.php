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

namespace mod_bigbluebuttonbn;

use admin_category;
use admin_setting;
use admin_setting_configcheckbox;
use admin_setting_configmultiselect;
use admin_setting_configpasswordunmask;
use admin_setting_configstoredfile;
use admin_setting_configtext;
use admin_setting_configtextarea;
use admin_setting_heading;
use admin_settingpage;
use cache_helper;
use lang_string;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\local\helpers\roles;

/**
 * The mod_bigbluebuttonbn settings helper
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David  (laurent [at] call-learning [dt] fr)
 */
class settings {

    /** @var admin_setting shared value */
    private $admin;

    /** @var bool Module is enabled */
    private $moduleenabled;

    /** @var string The name of the section */
    private $section;

    /** @var string The section name prefix */
    private $sectionnameprefix = "mod_bigbluebuttonbn";

    /**
     * Constructor for the bigbluebuttonbn settings.
     *
     * @param admin_category $admin
     * @param \core\plugininfo\mod $module
     * @param string $categoryname for the plugin setting (main setting page)
     */
    public function __construct(admin_category $admin, \core\plugininfo\mod $module, string $categoryname) {
        $this->moduleenabled = $module->is_enabled() === true;
        $this->admin = $admin;
        $this->section = $categoryname;

        $modbigbluebuttobnfolder = new admin_category(
            $categoryname,
            new lang_string('pluginname', 'mod_bigbluebuttonbn'),
            $module->is_enabled() === false
        );

        $admin->add('modsettings', $modbigbluebuttobnfolder);

        $mainsettings = $this->add_general_settings();
        $admin->add($categoryname, $mainsettings);
    }

    /**
     * Add all settings.
     */
    public function add_all_settings(): void {
        // Evaluates if recordings are enabled for the Moodle site.

        // Renders settings for record feature.
        $this->add_record_settings();
        // Renders settings for import recordings.
        $this->add_importrecordings_settings();
        // Renders settings for showing recordings.
        $this->add_showrecordings_settings();

        // Renders settings for meetings.
        $this->add_waitmoderator_settings();
        $this->add_voicebridge_settings();
        $this->add_preupload_settings();
        $this->add_userlimit_settings();
        $this->add_participants_settings();
        $this->add_notifications_settings();
        $this->add_muteonstart_settings();
        $this->add_locksettings_settings();
        // Renders settings for extended capabilities.
        $this->add_extended_settings();
        // Renders settings for experimental features.
        $this->add_experimental_settings();
    }

    /**
     * Add the setting and lock it conditionally.
     *
     * @param string $name
     * @param admin_setting $item
     * @param admin_settingpage $settings
     */
    protected function add_conditional_element(string $name, admin_setting $item, admin_settingpage $settings): void {
        global $CFG;
        if (isset($CFG->bigbluebuttonbn) && isset($CFG->bigbluebuttonbn[$name])) {
            if ($item->config_read($item->name)) {
                // A value has been set, we can safely omit the setting and it won't interfere with installation
                // process.
                // The idea behind it is to hide values from end-users in case we use multitenancy for example.
                return;
            }
        }
        $settings->add($item);
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     * @return admin_settingpage
     * @throws \coding_exception
     */
    protected function add_general_settings(): admin_settingpage {
        $settingsgeneral = new admin_settingpage(
            "{$this->sectionnameprefix}_general",
            get_string('config_general', 'bigbluebuttonbn'),
            'moodle/site:config',
            !((boolean) setting_validator::section_general_shown()) && ($this->moduleenabled)
        );
        if ($this->admin->fulltree) {
            // Configuration for BigBlueButton.
            $item = new admin_setting_heading('bigbluebuttonbn_config_general',
                '',
                get_string('config_general_description', 'bigbluebuttonbn'));

            $settingsgeneral->add($item);
            $item = new admin_setting_configtext(
                'bigbluebuttonbn_server_url',
                get_string('config_server_url', 'bigbluebuttonbn'),
                get_string('config_server_url_description', 'bigbluebuttonbn'),
                config::DEFAULT_SERVER_URL,
                PARAM_RAW
            );
            $item->set_updatedcallback(
                function() {
                    $this->reset_cache();
                    $task = new \mod_bigbluebuttonbn\task\reset_recordings();
                    \core\task\manager::queue_adhoc_task($task);
                }
            );
            $this->add_conditional_element(
                'server_url',
                $item,
                $settingsgeneral
            );
            $item = new admin_setting_configpasswordunmask(
                'bigbluebuttonbn_shared_secret',
                get_string('config_shared_secret', 'bigbluebuttonbn'),
                get_string('config_shared_secret_description', 'bigbluebuttonbn'),
                config::DEFAULT_SHARED_SECRET
            );
            $this->add_conditional_element(
                'shared_secret',
                $item,
                $settingsgeneral
            );
            $settingsgeneral->add($item);
            $item = new admin_setting_heading(
                'bigbluebuttonbn_config_default_messages',
                get_string('config_default_messages', 'bigbluebuttonbn'),
                get_string('config_default_messages_description', 'bigbluebuttonbn')
            );
            $settingsgeneral->add($item);
            $item = new admin_setting_configtextarea(
                'bigbluebuttonbn_welcome_default',
                get_string('config_welcome_default', 'bigbluebuttonbn'),
                get_string('config_welcome_default_description', 'bigbluebuttonbn'),
                '',
                PARAM_TEXT
            );
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
     */
    protected function add_record_settings(): void {
        // Configuration for 'recording' feature.
        $recordingsetting = new admin_settingpage(
            "{$this->sectionnameprefix}_recording",
            get_string('config_recording', 'bigbluebuttonbn'),
            'moodle/site:config',
            !((boolean) setting_validator::section_record_meeting_shown()) && ($this->moduleenabled)
        );

        if ($this->admin->fulltree) {
            $item = new admin_setting_heading(
                'bigbluebuttonbn_config_recording',
                '',
                get_string('config_recording_description', 'bigbluebuttonbn')
            );
            $recordingsetting->add($item);
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_recording_default',
                get_string('config_recording_default', 'bigbluebuttonbn'),
                get_string('config_recording_default_description', 'bigbluebuttonbn'),
                1
            );
            $this->add_conditional_element(
                'recording_default',
                $item,
                $recordingsetting
            );
            $item = new admin_setting_configtext(
                'bigbluebuttonbn_recording_refresh_period',
                get_string('config_recording_refresh_period', 'bigbluebuttonbn'),
                get_string('config_recording_refresh_period_description', 'bigbluebuttonbn'),
                recording::RECORDING_REFRESH_DEFAULT_PERIOD,
                PARAM_INT
            );
            $this->add_conditional_element(
                'recording_refresh_period',
                $item,
                $recordingsetting
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_recording_editable',
                get_string('config_recording_editable', 'bigbluebuttonbn'),
                get_string('config_recording_editable_description', 'bigbluebuttonbn'),
                1
            );
            $this->add_conditional_element(
                'recording_editable',
                $item,
                $recordingsetting
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_recording_icons_enabled',
                get_string('config_recording_icons_enabled', 'bigbluebuttonbn'),
                get_string('config_recording_icons_enabled_description', 'bigbluebuttonbn'),
                1
            );
            $this->add_conditional_element(
                'recording_icons_enabled',
                $item,
                $recordingsetting
            );

            // Add recording start to load and allow/hide stop/pause.
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_recording_all_from_start_default',
                get_string('config_recording_all_from_start_default', 'bigbluebuttonbn'),
                get_string('config_recording_all_from_start_default_description', 'bigbluebuttonbn'),
                0
            );
            $this->add_conditional_element(
                'recording_all_from_start_default',
                $item,
                $recordingsetting
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_recording_all_from_start_editable',
                get_string('config_recording_all_from_start_editable', 'bigbluebuttonbn'),
                get_string('config_recording_all_from_start_editable_description', 'bigbluebuttonbn'),
                0
            );
            $this->add_conditional_element(
                'recording_all_from_start_editable',
                $item,
                $recordingsetting
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_recording_hide_button_default',
                get_string('config_recording_hide_button_default', 'bigbluebuttonbn'),
                get_string('config_recording_hide_button_default_description', 'bigbluebuttonbn'),
                0
            );
            $this->add_conditional_element(
                'recording_hide_button_default',
                $item,
                $recordingsetting
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_recording_hide_button_editable',
                get_string('config_recording_hide_button_editable', 'bigbluebuttonbn'),
                get_string('config_recording_hide_button_editable_description', 'bigbluebuttonbn'),
                0
            );
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
     */
    protected function add_importrecordings_settings(): void {
        // Configuration for 'import recordings' feature.
        $importrecordingsettings = new admin_settingpage(
            "{$this->sectionnameprefix}_importrecording",
            get_string('config_importrecordings', 'bigbluebuttonbn'),
            'moodle/site:config',
            !((boolean) setting_validator::section_import_recordings_shown()) && ($this->moduleenabled)
        );

        if ($this->admin->fulltree) {
            $item = new admin_setting_heading(
                'bigbluebuttonbn_config_importrecordings',
                '',
                get_string('config_importrecordings_description', 'bigbluebuttonbn')
            );
            $importrecordingsettings->add($item);
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_importrecordings_enabled',
                get_string('config_importrecordings_enabled', 'bigbluebuttonbn'),
                get_string('config_importrecordings_enabled_description', 'bigbluebuttonbn'),
                0
            );
            $this->add_conditional_element(
                'importrecordings_enabled',
                $item,
                $importrecordingsettings
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_importrecordings_from_deleted_enabled',
                get_string('config_importrecordings_from_deleted_enabled', 'bigbluebuttonbn'),
                get_string('config_importrecordings_from_deleted_enabled_description', 'bigbluebuttonbn'),
                0
            );
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
     */
    protected function add_showrecordings_settings(): void {
        // Configuration for 'show recordings' feature.
        $showrecordingsettings = new admin_settingpage(
            "{$this->sectionnameprefix}_showrecordings",
            get_string('config_recordings', 'bigbluebuttonbn'),
            'moodle/site:config',
            !((boolean) setting_validator::section_show_recordings_shown()) && ($this->moduleenabled)
        );

        if ($this->admin->fulltree) {
            $item = new admin_setting_heading(
                'bigbluebuttonbn_config_recordings',
                '',
                get_string('config_recordings_description', 'bigbluebuttonbn')
            );
            $showrecordingsettings->add($item);
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_recordings_html_default',
                get_string('config_recordings_html_default', 'bigbluebuttonbn'),
                get_string('config_recordings_html_default_description', 'bigbluebuttonbn'),
                1
            );
            $this->add_conditional_element(
                'recordings_html_default',
                $item,
                $showrecordingsettings
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_recordings_html_editable',
                get_string('config_recordings_html_editable', 'bigbluebuttonbn'),
                get_string('config_recordings_html_editable_description', 'bigbluebuttonbn'),
                0
            );
            $this->add_conditional_element(
                'recordings_html_editable',
                $item,
                $showrecordingsettings
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_recordings_deleted_default',
                get_string('config_recordings_deleted_default', 'bigbluebuttonbn'),
                get_string('config_recordings_deleted_default_description', 'bigbluebuttonbn'),
                1
            );
            $this->add_conditional_element(
                'recordings_deleted_default',
                $item,
                $showrecordingsettings
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_recordings_deleted_editable',
                get_string('config_recordings_deleted_editable', 'bigbluebuttonbn'),
                get_string('config_recordings_deleted_editable_description', 'bigbluebuttonbn'),
                0
            );
            $this->add_conditional_element(
                'recordings_deleted_editable',
                $item,
                $showrecordingsettings
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_recordings_imported_default',
                get_string('config_recordings_imported_default', 'bigbluebuttonbn'),
                get_string('config_recordings_imported_default_description', 'bigbluebuttonbn'),
                0
            );
            $this->add_conditional_element(
                'recordings_imported_default',
                $item,
                $showrecordingsettings
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_recordings_imported_editable',
                get_string('config_recordings_imported_editable', 'bigbluebuttonbn'),
                get_string('config_recordings_imported_editable_description', 'bigbluebuttonbn'),
                1
            );
            $this->add_conditional_element(
                'recordings_imported_editable',
                $item,
                $showrecordingsettings
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_recordings_preview_default',
                get_string('config_recordings_preview_default', 'bigbluebuttonbn'),
                get_string('config_recordings_preview_default_description', 'bigbluebuttonbn'),
                1
            );
            $this->add_conditional_element(
                'recordings_preview_default',
                $item,
                $showrecordingsettings
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_recordings_preview_editable',
                get_string('config_recordings_preview_editable', 'bigbluebuttonbn'),
                get_string('config_recordings_preview_editable_description', 'bigbluebuttonbn'),
                0
            );
            $this->add_conditional_element(
                'recordings_preview_editable',
                $item,
                $showrecordingsettings
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_recordings_sortorder',
                get_string('config_recordings_sortorder', 'bigbluebuttonbn'),
                get_string('config_recordings_sortorder_description', 'bigbluebuttonbn'),
                0
            );
            $this->add_conditional_element(
                'recordings_sortorder',
                $item,
                $showrecordingsettings
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_recordings_validate_url',
                get_string('config_recordings_validate_url', 'bigbluebuttonbn'),
                get_string('config_recordings_validate_url_description', 'bigbluebuttonbn'),
                1
            );
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
     */
    protected function add_waitmoderator_settings(): void {
        // Configuration for wait for moderator feature.
        $waitmoderatorsettings = new admin_settingpage(
            "{$this->sectionnameprefix}_waitformoderator",
            get_string('config_waitformoderator', 'bigbluebuttonbn'),
            'moodle/site:config',
            !((boolean) setting_validator::section_wait_moderator_shown()) && ($this->moduleenabled)
        );

        if ($this->admin->fulltree) {
            $item = new admin_setting_heading(
                'bigbluebuttonbn_config_waitformoderator',
                '',
                get_string('config_waitformoderator_description', 'bigbluebuttonbn')
            );
            $waitmoderatorsettings->add($item);
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_waitformoderator_default',
                get_string('config_waitformoderator_default', 'bigbluebuttonbn'),
                get_string('config_waitformoderator_default_description', 'bigbluebuttonbn'),
                0
            );
            $this->add_conditional_element(
                'waitformoderator_default',
                $item,
                $waitmoderatorsettings
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_waitformoderator_editable',
                get_string('config_waitformoderator_editable', 'bigbluebuttonbn'),
                get_string('config_waitformoderator_editable_description', 'bigbluebuttonbn'),
                1
            );
            $this->add_conditional_element(
                'waitformoderator_editable',
                $item,
                $waitmoderatorsettings
            );
            $item = new admin_setting_configtext(
                'bigbluebuttonbn_waitformoderator_ping_interval',
                get_string('config_waitformoderator_ping_interval', 'bigbluebuttonbn'),
                get_string('config_waitformoderator_ping_interval_description', 'bigbluebuttonbn'),
                10,
                PARAM_INT
            );
            $this->add_conditional_element(
                'waitformoderator_ping_interval',
                $item,
                $waitmoderatorsettings
            );
            $item = new admin_setting_configtext(
                'bigbluebuttonbn_waitformoderator_cache_ttl',
                get_string('config_waitformoderator_cache_ttl', 'bigbluebuttonbn'),
                get_string('config_waitformoderator_cache_ttl_description', 'bigbluebuttonbn'),
                60,
                PARAM_INT
            );
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
     */
    protected function add_voicebridge_settings(): void {
        // Configuration for "static voice bridge" feature.
        $voicebridgesettings = new admin_settingpage(
            "{$this->sectionnameprefix}_voicebridge",
            get_string('config_voicebridge', 'bigbluebuttonbn'),
            'moodle/site:config',
            !((boolean) setting_validator::section_static_voice_bridge_shown()) && ($this->moduleenabled)
        );

        if ($this->admin->fulltree) {
            $item = new admin_setting_heading(
                'bigbluebuttonbn_config_voicebridge',
                '',
                get_string('config_voicebridge_description', 'bigbluebuttonbn')
            );
            $voicebridgesettings->add($item);
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_voicebridge_editable',
                get_string('config_voicebridge_editable', 'bigbluebuttonbn'),
                get_string('config_voicebridge_editable_description', 'bigbluebuttonbn'),
                0
            );
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
     */
    protected function add_preupload_settings(): void {
        // Configuration for "preupload presentation" feature.
        $preuploadsettings = new admin_settingpage(
            "{$this->sectionnameprefix}_preupload",
            get_string('config_preuploadpresentation', 'bigbluebuttonbn'),
            'moodle/site:config',
            !((boolean) setting_validator::section_preupload_presentation_shown()) && ($this->moduleenabled)
        );

        if ($this->admin->fulltree) {
            // This feature only works if curl is installed (but it is as now required by Moodle). The checks have been removed.
            $item = new admin_setting_heading(
                'bigbluebuttonbn_config_preuploadpresentation',
                '',
                get_string('config_preuploadpresentation_description', 'bigbluebuttonbn')
            );
            $preuploadsettings->add($item);

            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_preuploadpresentation_enabled',
                get_string('config_preuploadpresentation_enabled', 'bigbluebuttonbn'),
                get_string('config_preuploadpresentation_enabled_description', 'bigbluebuttonbn'),
                0
            );
            $this->add_conditional_element(
                'preuploadpresentation_enabled',
                $item,
                $preuploadsettings
            );
            // Note: checks on curl library have been removed as it is a requirement from Moodle.
            $filemanageroptions = [];
            $filemanageroptions['accepted_types'] = '*';
            $filemanageroptions['maxbytes'] = 0;
            $filemanageroptions['subdirs'] = 0;
            $filemanageroptions['maxfiles'] = 1;
            $filemanageroptions['mainfile'] = true;

            $filemanager = new admin_setting_configstoredfile(
                'mod_bigbluebuttonbn/presentationdefault',
                get_string('config_presentation_default', 'bigbluebuttonbn'),
                get_string('config_presentation_default_description', 'bigbluebuttonbn'),
                'presentationdefault',
                0,
                $filemanageroptions
            );

            $preuploadsettings->add($filemanager);
        }
        $this->admin->add($this->section, $preuploadsettings);
    }

    /**
     * Helper function renders userlimit settings if the feature is enabled.
     */
    protected function add_userlimit_settings(): void {
        $userlimitsettings = new admin_settingpage(
            "{$this->sectionnameprefix}_userlimit",
            get_string('config_userlimit', 'bigbluebuttonbn'),
            'moodle/site:config',
            !((boolean) setting_validator::section_user_limit_shown()) && ($this->moduleenabled)
        );

        if ($this->admin->fulltree) {
            // Configuration for "user limit" feature.
            $item = new admin_setting_heading(
                'bigbluebuttonbn_config_userlimit',
                '',
                get_string('config_userlimit_description', 'bigbluebuttonbn')
            );
            $userlimitsettings->add($item);
            $item = new admin_setting_configtext(
                'bigbluebuttonbn_userlimit_default',
                get_string('config_userlimit_default', 'bigbluebuttonbn'),
                get_string('config_userlimit_default_description', 'bigbluebuttonbn'),
                0,
                PARAM_INT
            );
            $this->add_conditional_element(
                'userlimit_default',
                $item,
                $userlimitsettings
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_userlimit_editable',
                get_string('config_userlimit_editable', 'bigbluebuttonbn'),
                get_string('config_userlimit_editable_description', 'bigbluebuttonbn'),
                0
            );
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
     */
    protected function add_participants_settings(): void {
        // Configuration for defining the default role/user that will be moderator on new activities.
        $participantsettings = new admin_settingpage(
            "{$this->sectionnameprefix}_participant",
            get_string('config_participant', 'bigbluebuttonbn'),
            'moodle/site:config',
            !((boolean) setting_validator::section_moderator_default_shown()) && ($this->moduleenabled)
        );

        if ($this->admin->fulltree) {
            $item = new admin_setting_heading(
                'bigbluebuttonbn_config_participant',
                '',
                get_string('config_participant_description', 'bigbluebuttonbn')
            );
            $participantsettings->add($item);

            // UI for 'participants' feature.
            $roles = roles::get_roles(null, false);
            $owner = [
                '0' => get_string('mod_form_field_participant_list_type_owner', 'bigbluebuttonbn')
            ];
            $item = new admin_setting_configmultiselect(
                'bigbluebuttonbn_participant_moderator_default',
                get_string('config_participant_moderator_default', 'bigbluebuttonbn'),
                get_string('config_participant_moderator_default_description', 'bigbluebuttonbn'),
                array_keys($owner),
                $owner + $roles
            );
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
     */
    protected function add_notifications_settings(): void {
        // Configuration for "send notifications" feature.
        $notificationssettings = new admin_settingpage(
            "{$this->sectionnameprefix}_notifications",
            get_string('config_sendnotifications', 'bigbluebuttonbn'),
            'moodle/site:config',
            !((boolean) setting_validator::section_send_notifications_shown()) && ($this->moduleenabled)
        );

        if ($this->admin->fulltree) {
            $item = new admin_setting_heading(
                'bigbluebuttonbn_config_sendnotifications',
                '',
                get_string('config_sendnotifications_description', 'bigbluebuttonbn')
            );
            $notificationssettings->add($item);
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_sendnotifications_enabled',
                get_string('config_sendnotifications_enabled', 'bigbluebuttonbn'),
                get_string('config_sendnotifications_enabled_description', 'bigbluebuttonbn'),
                1
            );
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
     */
    protected function add_muteonstart_settings(): void {
        // Configuration for BigBlueButton.
        $muteonstartsetting = new admin_settingpage(
            "{$this->sectionnameprefix}_muteonstart",
            get_string('config_muteonstart', 'bigbluebuttonbn'),
            'moodle/site:config',
            !((boolean) setting_validator::section_muteonstart_shown()) && ($this->moduleenabled)
        );

        if ($this->admin->fulltree) {
            $item = new admin_setting_heading(
                'bigbluebuttonbn_config_muteonstart',
                '',
                get_string('config_muteonstart_description', 'bigbluebuttonbn')
            );
            $muteonstartsetting->add($item);
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_muteonstart_default',
                get_string('config_muteonstart_default', 'bigbluebuttonbn'),
                get_string('config_muteonstart_default_description', 'bigbluebuttonbn'),
                0
            );
            $this->add_conditional_element(
                'muteonstart_default',
                $item,
                $muteonstartsetting
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_muteonstart_editable',
                get_string('config_muteonstart_editable', 'bigbluebuttonbn'),
                get_string('config_muteonstart_editable_description', 'bigbluebuttonbn'),
                0
            );
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
     */
    protected function add_locksettings_settings(): void {
        $category = new admin_category(
            "{$this->sectionnameprefix}_locksettings",
            get_string('config_locksettings', 'bigbluebuttonbn'),
            get_string('config_locksettings_description', 'bigbluebuttonbn')
        );

        $this->admin->add($this->section, $category);

        // Configuration for various lock settings for meetings.
        $this->add_disablecam_settings($category);
        $this->add_disablemic_settings($category);
        $this->add_disablepublicchat_settings($category);
        $this->add_disablenote_settings($category);
        $this->add_hideuserlist_settings($category);
        $this->add_lockedlayout_settings($category);
        $this->add_lockonjoin_settings($category);
        $this->add_lockonjoinconfigurable_settings($category);
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     * @param admin_category $category The parent category to add to
     */
    protected function add_disablecam_settings(admin_category $category): void {
        // Configuration for BigBlueButton.
        $disablecamsettings = new admin_settingpage(
            "{$this->sectionnameprefix}_disablecam",
            get_string('config_disablecam_default', 'bigbluebuttonbn'),
            'moodle/site:config',
            !((boolean) setting_validator::section_disablecam_shown()) && ($this->moduleenabled)
        );

        if ($this->admin->fulltree) {
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_disablecam_default',
                get_string('config_disablecam_default', 'bigbluebuttonbn'),
                get_string('config_disablecam_default_description', 'bigbluebuttonbn'),
                0
            );
            $this->add_conditional_element(
                'disablecam_default',
                $item,
                $disablecamsettings
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_disablecam_editable',
                get_string('config_disablecam_editable', 'bigbluebuttonbn'),
                get_string('config_disablecam_editable_description', 'bigbluebuttonbn'),
                1
            );
            $this->add_conditional_element(
                'disablecam_editable',
                $item,
                $disablecamsettings
            );
        }
        $this->admin->add($category->name, $disablecamsettings);
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     * @param admin_category $category The parent category to add to
     */
    protected function add_disablemic_settings(admin_category $category): void {
        // Configuration for BigBlueButton.
        $disablemicsetting = new admin_settingpage(
            "{$this->sectionnameprefix}_disablemic",
            get_string('config_disablemic_default', 'bigbluebuttonbn'),
            'moodle/site:config',
            !((boolean) setting_validator::section_disablemic_shown()) && ($this->moduleenabled)
        );

        if ($this->admin->fulltree) {
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_disablemic_default',
                get_string('config_disablemic_default', 'bigbluebuttonbn'),
                get_string('config_disablemic_default_description', 'bigbluebuttonbn'),
                0
            );
            $this->add_conditional_element(
                'disablemic_default',
                $item,
                $disablemicsetting
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_disablemic_editable',
                get_string('config_disablemic_editable', 'bigbluebuttonbn'),
                get_string('config_disablemic_editable_description', 'bigbluebuttonbn'),
                1
            );
            $this->add_conditional_element(
                'disablecam_editable',
                $item,
                $disablemicsetting
            );
        }
        $this->admin->add($category->name, $disablemicsetting);
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     * @param admin_category $category The parent category to add to
     */
    protected function add_disableprivatechat_settings(admin_category $category): void {
        // Configuration for BigBlueButton.
        $disableprivatechatsetting = new admin_settingpage(
            "{$this->sectionnameprefix}_disableprivatechat",
            get_string('config_disableprivatechat_default', 'bigbluebuttonbn'),
            'moodle/site:config',
            !((boolean) setting_validator::section_disableprivatechat_shown()) && ($this->moduleenabled)
        );

        if ($this->admin->fulltree) {
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_disableprivatechat_default',
                get_string('config_disableprivatechat_default', 'bigbluebuttonbn'),
                get_string('config_disableprivatechat_default_description', 'bigbluebuttonbn'),
                0
            );
            $this->add_conditional_element(
                'disableprivatechat_default',
                $item,
                $disableprivatechatsetting
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_disableprivatechat_editable',
                get_string('config_disableprivatechat_editable', 'bigbluebuttonbn'),
                get_string('config_disableprivatechat_editable_description', 'bigbluebuttonbn'),
                1
            );
            $this->add_conditional_element(
                'disableprivatechat_editable',
                $item,
                $disableprivatechatsetting
            );
        }
        $this->admin->add($category->name, $disableprivatechatsetting);
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     * @param admin_category $category The parent category to add to
     */
    protected function add_disablepublicchat_settings(admin_category $category): void {
        // Configuration for BigBlueButton.
        $disablepublicchatsetting = new admin_settingpage(
            "{$this->sectionnameprefix}_disablepublicchat",
            get_string('config_disableprivatechat_default', 'bigbluebuttonbn'),
            'moodle/site:config',
            !((boolean) setting_validator::section_disablepublicchat_shown()) && ($this->moduleenabled)
        );

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
        $this->admin->add($category->name, $disablepublicchatsetting);
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     * @param admin_category $category The parent category to add to
     */
    protected function add_disablenote_settings(admin_category $category): void {
        // Configuration for BigBlueButton.
        $disablenotesetting = new admin_settingpage(
            "{$this->sectionnameprefix}_disablenote",
            get_string('config_disablenote_default', 'bigbluebuttonbn'),
            'moodle/site:config',
            !((boolean) setting_validator::section_disablenote_shown()) && ($this->moduleenabled)
        );

        if ($this->admin->fulltree) {
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_disablenote_default',
                get_string('config_disablenote_default', 'bigbluebuttonbn'),
                get_string('config_disablenote_default_description', 'bigbluebuttonbn'),
                0
            );
            $this->add_conditional_element(
                'disablenote_default',
                $item,
                $disablenotesetting
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_disablenote_editable',
                get_string('config_disablenote_editable', 'bigbluebuttonbn'),
                get_string('config_disablenote_editable_description', 'bigbluebuttonbn'),
                1
            );
            $this->add_conditional_element(
                'disablenote_editable',
                $item,
                $disablenotesetting
            );
        }
        $this->admin->add($category->name, $disablenotesetting);
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     * @param admin_category $category The parent category to add to
     */
    protected function add_hideuserlist_settings(admin_category $category): void {
        // Configuration for BigBlueButton.
        $hideuserlistsetting = new admin_settingpage(
            "{$this->sectionnameprefix}_hideuserlist",
            get_string('config_hideuserlist_default', 'bigbluebuttonbn'),
            'moodle/site:config',
            !((boolean) setting_validator::section_hideuserlist_shown()) && ($this->moduleenabled)
        );

        if ($this->admin->fulltree) {
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_hideuserlist_default',
                get_string('config_hideuserlist_default', 'bigbluebuttonbn'),
                get_string('config_hideuserlist_default_description', 'bigbluebuttonbn'),
                0
            );
            $this->add_conditional_element(
                'hideuserlist_default',
                $item,
                $hideuserlistsetting
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_hideuserlist_editable',
                get_string('config_hideuserlist_editable', 'bigbluebuttonbn'),
                get_string('config_hideuserlist_editable_description', 'bigbluebuttonbn'),
                1
            );
            $this->add_conditional_element(
                'hideuserlist_editable',
                $item,
                $hideuserlistsetting
            );
        }
        $this->admin->add($category->name, $hideuserlistsetting);
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     * @param admin_category $category The parent category to add to
     */
    protected function add_lockedlayout_settings(admin_category $category): void {
        // Configuration for BigBlueButton.
        $lockedlayoutsetting = new admin_settingpage(
            "{$this->sectionnameprefix}_lockedlayout",
            get_string('config_lockedlayout_default', 'bigbluebuttonbn'),
            'moodle/site:config',
            !((boolean) setting_validator::section_lockedlayout_shown()) && ($this->moduleenabled)
        );

        if ($this->admin->fulltree) {
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_lockedlayout_default',
                get_string('config_lockedlayout_default', 'bigbluebuttonbn'),
                get_string('config_lockedlayout_default_description', 'bigbluebuttonbn'),
                0
            );
            $this->add_conditional_element(
                'lockedlayout_default',
                $item,
                $lockedlayoutsetting
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_lockedlayout_editable',
                get_string('config_lockedlayout_editable', 'bigbluebuttonbn'),
                get_string('config_lockedlayout_editable_description', 'bigbluebuttonbn'),
                1
            );
            $this->add_conditional_element(
                'lockedlayout_editable',
                $item,
                $lockedlayoutsetting
            );
        }
        $this->admin->add($category->name, $lockedlayoutsetting);
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     * @param admin_category $category The parent category to add to
     */
    protected function add_lockonjoin_settings(admin_category $category): void {
        // Configuration for BigBlueButton.
        $lockonjoinsetting = new admin_settingpage(
            "{$this->sectionnameprefix}_lockonjoin",
            get_string('config_lockonjoin_default', 'bigbluebuttonbn'),
            'moodle/site:config',
            !((boolean) setting_validator::section_lockonjoin_shown()) && ($this->moduleenabled)
        );

        if ($this->admin->fulltree) {
            if ((boolean) setting_validator::section_lockonjoin_shown()) {
                $item = new admin_setting_configcheckbox(
                    'bigbluebuttonbn_lockonjoin_default',
                    get_string('config_lockonjoin_default', 'bigbluebuttonbn'),
                    get_string('config_lockonjoin_default_description', 'bigbluebuttonbn'),
                    0
                );
                $this->add_conditional_element(
                    'lockonjoin_default',
                    $item,
                    $lockonjoinsetting
                );
                $item = new admin_setting_configcheckbox(
                    'bigbluebuttonbn_lockonjoin_editable',
                    get_string('config_lockonjoin_editable', 'bigbluebuttonbn'),
                    get_string('config_lockonjoin_editable_description', 'bigbluebuttonbn'),
                    1
                );
                $this->add_conditional_element(
                    'lockonjoin_editable',
                    $item,
                    $lockonjoinsetting
                );
            }
        }
        $this->admin->add($category->name, $lockonjoinsetting);
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     * @param admin_category $category The parent category to add to
     */
    protected function add_lockonjoinconfigurable_settings(admin_category $category): void {
        // Configuration for BigBlueButton.
        $lockonjoinconfigurablesetting = new admin_settingpage(
            "{$this->sectionnameprefix}_lockonjoinconfigurable",
            get_string('config_lockonjoinconfigurable_default', 'bigbluebuttonbn'),
            'moodle/site:config',
            !((boolean) setting_validator::section_lockonjoinconfigurable_shown()) && ($this->moduleenabled)
        );

        if ($this->admin->fulltree) {
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_lockonjoinconfigurable_default',
                get_string('config_lockonjoinconfigurable_default', 'bigbluebuttonbn'),
                get_string('config_lockonjoinconfigurable_default_description', 'bigbluebuttonbn'),
                0
            );
            $this->add_conditional_element(
                'lockonjoinconfigurable_default',
                $item,
                $lockonjoinconfigurablesetting
            );
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_lockonjoinconfigurable_editable',
                get_string('config_lockonjoinconfigurable_editable', 'bigbluebuttonbn'),
                get_string('config_lockonjoinconfigurable_editable_description', 'bigbluebuttonbn'),
                1
            );
            $this->add_conditional_element(
                'lockonjoinconfigurable_editable',
                $item,
                $lockonjoinconfigurablesetting
            );
        }
        $this->admin->add($category->name, $lockonjoinconfigurablesetting);
    }

    /**
     * Helper function renders extended settings if any of the features there is enabled.
     */
    protected function add_extended_settings(): void {
        // Configuration for extended capabilities.
        $extendedcapabilitiessetting = new admin_settingpage(
            "{$this->sectionnameprefix}_extendedcapabilities",
            get_string('config_extended_capabilities', 'bigbluebuttonbn'),
            'moodle/site:config',
            !((boolean) setting_validator::section_settings_extended_shown()) && ($this->moduleenabled)
        );

        if ($this->admin->fulltree) {
            $item = new admin_setting_heading(
                'bigbluebuttonbn_config_extended_capabilities',
                '',
                get_string('config_extended_capabilities_description', 'bigbluebuttonbn')
            );
            $extendedcapabilitiessetting->add($item);
            // UI for 'notify users when recording ready' feature.
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_recordingready_enabled',
                get_string('config_recordingready_enabled', 'bigbluebuttonbn'),
                get_string('config_recordingready_enabled_description', 'bigbluebuttonbn'),
                0
            );
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
     */
    protected function add_experimental_settings(): void {
        // Configuration for experimental features should go here.
        $experimentalfeaturessetting = new admin_settingpage(
            "{$this->sectionnameprefix}_experimentalfeatures",
            get_string('config_experimental_features', 'bigbluebuttonbn'),
            'moodle/site:config',
            !((boolean) setting_validator::section_settings_extended_shown()) && ($this->moduleenabled)
        );

        if ($this->admin->fulltree) {
            $item = new admin_setting_heading(
                'bigbluebuttonbn_config_experimental_features',
                '',
                get_string('config_experimental_features_description', 'bigbluebuttonbn')
            );
            $experimentalfeaturessetting->add($item);
            // UI for 'register meeting events' feature.
            $item = new admin_setting_configcheckbox(
                'bigbluebuttonbn_meetingevents_enabled',
                get_string('config_meetingevents_enabled', 'bigbluebuttonbn'),
                get_string('config_meetingevents_enabled_description', 'bigbluebuttonbn'),
                0
            );
            $this->add_conditional_element(
                'meetingevents_enabled',
                $item,
                $experimentalfeaturessetting
            );
        }
        $this->admin->add($this->section, $experimentalfeaturessetting);
    }

    /**
     * Process reset cache.
     */
    protected function reset_cache() {
        // Reset serverinfo cache.
        cache_helper::purge_by_event('mod_bigbluebuttonbn/serversettingschanged');
    }
}
