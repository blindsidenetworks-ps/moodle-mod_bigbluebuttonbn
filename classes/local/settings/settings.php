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

use mod_bigbluebuttonbn\local\bbb_constants;
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
     * Helper function renders general settings if the feature is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_general(&$renderer) {
        // Configuration for BigBlueButton.
        if ((boolean) validator::section_general_shown()) {
            $renderer->render_group_header('general');
            $renderer->render_group_element(
                'server_url',
                $renderer->render_group_element_text('server_url', bbb_constants::BIGBLUEBUTTONBN_DEFAULT_SERVER_URL)
            );
            $renderer->render_group_element(
                'shared_secret',
                $renderer->render_group_element_text('shared_secret', bbb_constants::BIGBLUEBUTTONBN_DEFAULT_SHARED_SECRET)
            );
        }
    }

    /**
     * Helper function renders record settings if the feature is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_record(&$renderer) {
        // Configuration for 'recording' feature.
        if ((boolean) validator::section_record_meeting_shown()) {
            $renderer->render_group_header('recording');
            $renderer->render_group_element(
                'recording_default',
                $renderer->render_group_element_checkbox('recording_default', 1)
            );
            $renderer->render_group_element(
                'recording_editable',
                $renderer->render_group_element_checkbox('recording_editable', 1)
            );
            $renderer->render_group_element(
                'recording_icons_enabled',
                $renderer->render_group_element_checkbox('recording_icons_enabled', 1)
            );

            // Add recording start to load and allow/hide stop/pause.
            $renderer->render_group_element(
                'recording_all_from_start_default',
                $renderer->render_group_element_checkbox('recording_all_from_start_default', 0)
            );
            $renderer->render_group_element(
                'recording_all_from_start_editable',
                $renderer->render_group_element_checkbox('recording_all_from_start_editable', 0)
            );
            $renderer->render_group_element(
                'recording_hide_button_default',
                $renderer->render_group_element_checkbox('recording_hide_button_default', 0)
            );
            $renderer->render_group_element(
                'recording_hide_button_editable',
                $renderer->render_group_element_checkbox('recording_hide_button_editable', 0)
            );
        }
    }

    /**
     * Helper function renders import recording settings if the feature is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_importrecordings(&$renderer) {
        // Configuration for 'import recordings' feature.
        if ((boolean) validator::section_import_recordings_shown()) {
            $renderer->render_group_header('importrecordings');
            $renderer->render_group_element(
                'importrecordings_enabled',
                $renderer->render_group_element_checkbox('importrecordings_enabled', 0)
            );
            $renderer->render_group_element(
                'importrecordings_from_deleted_enabled',
                $renderer->render_group_element_checkbox('importrecordings_from_deleted_enabled', 0)
            );
        }
    }

    /**
     * Helper function renders show recording settings if the feature is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_showrecordings(&$renderer) {
        // Configuration for 'show recordings' feature.
        if ((boolean) validator::section_show_recordings_shown()) {
            $renderer->render_group_header('recordings');
            $renderer->render_group_element(
                'recordings_html_default',
                $renderer->render_group_element_checkbox('recordings_html_default', 1)
            );
            $renderer->render_group_element(
                'recordings_html_editable',
                $renderer->render_group_element_checkbox('recordings_html_editable', 0)
            );
            $renderer->render_group_element(
                'recordings_deleted_default',
                $renderer->render_group_element_checkbox('recordings_deleted_default', 1)
            );
            $renderer->render_group_element(
                'recordings_deleted_editable',
                $renderer->render_group_element_checkbox('recordings_deleted_editable', 0)
            );
            $renderer->render_group_element(
                'recordings_imported_default',
                $renderer->render_group_element_checkbox('recordings_imported_default', 0)
            );
            $renderer->render_group_element(
                'recordings_imported_editable',
                $renderer->render_group_element_checkbox('recordings_imported_editable', 1)
            );
            $renderer->render_group_element(
                'recordings_preview_default',
                $renderer->render_group_element_checkbox('recordings_preview_default', 1)
            );
            $renderer->render_group_element(
                'recordings_preview_editable',
                $renderer->render_group_element_checkbox('recordings_preview_editable', 0)
            );
            $renderer->render_group_element(
                'recordings_sortorder',
                $renderer->render_group_element_checkbox('recordings_sortorder', 0)
            );
            $renderer->render_group_element(
                'recordings_validate_url',
                $renderer->render_group_element_checkbox('recordings_validate_url', 1)
            );
        }
    }

    /**
     * Helper function renders wait for moderator settings if the feature is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_waitmoderator(&$renderer) {
        // Configuration for wait for moderator feature.
        if ((boolean) validator::section_wait_moderator_shown()) {
            $renderer->render_group_header('waitformoderator');
            $renderer->render_group_element(
                'waitformoderator_default',
                $renderer->render_group_element_checkbox('waitformoderator_default', 0)
            );
            $renderer->render_group_element(
                'waitformoderator_editable',
                $renderer->render_group_element_checkbox('waitformoderator_editable', 1)
            );
            $renderer->render_group_element(
                'waitformoderator_ping_interval',
                $renderer->render_group_element_text('waitformoderator_ping_interval', 10, PARAM_INT)
            );
            $renderer->render_group_element(
                'waitformoderator_cache_ttl',
                $renderer->render_group_element_text('waitformoderator_cache_ttl', 60, PARAM_INT)
            );
        }
    }

    /**
     * Helper function renders static voice bridge settings if the feature is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_voicebridge(&$renderer) {
        // Configuration for "static voice bridge" feature.
        if ((boolean) validator::section_static_voice_bridge_shown()) {
            $renderer->render_group_header('voicebridge');
            $renderer->render_group_element(
                'voicebridge_editable',
                $renderer->render_group_element_checkbox('voicebridge_editable', 0)
            );
        }
    }

    /**
     * Helper function renders preuploaded presentation settings if the feature is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_preupload(&$renderer) {
        // Configuration for "preupload presentation" feature.
        if ((boolean) validator::section_preupload_presentation_shown()) {
            // This feature only works if curl is installed.
            $preuploaddescripion = get_string('config_preuploadpresentation_description', 'bigbluebuttonbn');
            if (!extension_loaded('curl')) {
                $preuploaddescripion .= '<div class="form-defaultinfo">';
                $preuploaddescripion .= get_string('config_warning_curl_not_installed', 'bigbluebuttonbn');
                $preuploaddescripion .= '</div><br>';
            }
            $renderer->render_group_header('preuploadpresentation', null, $preuploaddescripion);
            if (extension_loaded('curl')) {
                $renderer->render_group_element(
                    'preuploadpresentation_enabled',
                    $renderer->render_group_element_checkbox('preuploadpresentation_enabled', 0)
                );
            }
        }
    }

    /**
     * Helper function renders preuploaded presentation manage file if the feature is enabled.
     * This allow to select a file for use as default in all BBB instances if preuploaded presetantion is enable.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_preupload_manage_default_file(&$renderer) {
        // Configuration for "preupload presentation" feature.
        if ((boolean) validator::section_preupload_presentation_shown()) {
            if (extension_loaded('curl')) {
                // This feature only works if curl is installed.
                $renderer->render_filemanager_default_file_presentation("presentation_default");
            }
        }
    }

    /**
     * Helper function renders userlimit settings if the feature is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_userlimit(&$renderer) {
        // Configuration for "user limit" feature.
        if ((boolean) validator::section_user_limit_shown()) {
            $renderer->render_group_header('userlimit');
            $renderer->render_group_element(
                'userlimit_default',
                $renderer->render_group_element_text('userlimit_default', 0, PARAM_INT)
            );
            $renderer->render_group_element(
                'userlimit_editable',
                $renderer->render_group_element_checkbox('userlimit_editable', 0)
            );
        }
    }

    /**
     * Helper function renders duration settings if the feature is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_duration(&$renderer) {
        // Configuration for "scheduled duration" feature.
        if ((boolean) validator::section_scheduled_duration_shown()) {
            $renderer->render_group_header('scheduled');
            $renderer->render_group_element(
                'scheduled_duration_enabled',
                $renderer->render_group_element_checkbox('scheduled_duration_enabled', 1)
            );
            $renderer->render_group_element(
                'scheduled_duration_compensation',
                $renderer->render_group_element_text('scheduled_duration_compensation', 10, PARAM_INT)
            );
            $renderer->render_group_element(
                'scheduled_pre_opening',
                $renderer->render_group_element_text('scheduled_pre_opening', 10, PARAM_INT)
            );
        }
    }

    /**
     * Helper function renders participant settings if the feature is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_participants(&$renderer) {
        // Configuration for defining the default role/user that will be moderator on new activities.
        if ((boolean) validator::section_moderator_default_shown()) {
            $renderer->render_group_header('participant');
            // UI for 'participants' feature.
            $roles = roles::bigbluebuttonbn_get_roles(null, false);
            $owner = array('0' => get_string('mod_form_field_participant_list_type_owner', 'bigbluebuttonbn'));
            $renderer->render_group_element(
                'participant_moderator_default',
                $renderer->render_group_element_configmultiselect(
                    'participant_moderator_default',
                    array_keys($owner),
                    $owner + $roles // CONTRIB-7966: don't use array_merge here so it does not reindex the array.
                )
            );
        }
    }

    /**
     * Helper function renders notification settings if the feature is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_notifications(&$renderer) {
        // Configuration for "send notifications" feature.
        if ((boolean) validator::section_send_notifications_shown()) {
            $renderer->render_group_header('sendnotifications');
            $renderer->render_group_element(
                'sendnotifications_enabled',
                $renderer->render_group_element_checkbox('sendnotifications_enabled', 1)
            );
        }
    }

    /**
     * Helper function renders client type settings if the feature is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_clienttype(&$renderer) {
        // Configuration for "clienttype" feature.
        if ((boolean) validator::section_clienttype_shown()) {
            $renderer->render_group_header('clienttype');
            $renderer->render_group_element(
                'clienttype_editable',
                $renderer->render_group_element_checkbox('clienttype_editable', 0)
            );
            // Web Client default.
            $default = intval((int) \mod_bigbluebuttonbn\local\config::get('clienttype_default'));
            $choices = array(bbb_constants::BIGBLUEBUTTON_CLIENTTYPE_FLASH => get_string('mod_form_block_clienttype_flash',
                'bigbluebuttonbn'),
                bbb_constants::BIGBLUEBUTTON_CLIENTTYPE_HTML5 => get_string('mod_form_block_clienttype_html5', 'bigbluebuttonbn'));
            $renderer->render_group_element(
                'clienttype_default',
                $renderer->render_group_element_configselect(
                    'clienttype_default',
                    $default,
                    $choices
                )
            );
        }
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_muteonstart(&$renderer) {
        // Configuration for BigBlueButton.
        if ((boolean) validator::section_muteonstart_shown()) {
            $renderer->render_group_header('muteonstart');
            $renderer->render_group_element(
                'muteonstart_default',
                $renderer->render_group_element_checkbox('muteonstart_default', 0)
            );
            $renderer->render_group_element(
                'muteonstart_editable',
                $renderer->render_group_element_checkbox('muteonstart_editable', 0)
            );
        }
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_locksettings(&$renderer) {
        $renderer->render_group_header('locksettings');
        // Configuration for various lock settings for meetings.
        self::bigbluebuttonbn_settings_disablecam($renderer);
        self::bigbluebuttonbn_settings_disablemic($renderer);
        self::bigbluebuttonbn_settings_disableprivatechat($renderer);
        self::bigbluebuttonbn_settings_disablepublicchat($renderer);
        self::bigbluebuttonbn_settings_disablenote($renderer);
        self::bigbluebuttonbn_settings_hideuserlist($renderer);
        self::bigbluebuttonbn_settings_lockedlayout($renderer);
        self::bigbluebuttonbn_settings_lockonjoin($renderer);
        self::bigbluebuttonbn_settings_lockonjoinconfigurable($renderer);
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_disablecam(&$renderer) {
        // Configuration for BigBlueButton.
        if ((boolean) validator::section_disablecam_shown()) {
            $renderer->render_group_element(
                'disablecam_default',
                $renderer->render_group_element_checkbox('disablecam_default', 0)
            );
            $renderer->render_group_element(
                'disablecam_editable',
                $renderer->render_group_element_checkbox('disablecam_editable', 1)
            );
        }
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_disablemic(&$renderer) {
        // Configuration for BigBlueButton.
        if ((boolean) validator::section_disablemic_shown()) {
            $renderer->render_group_element(
                'disablemic_default',
                $renderer->render_group_element_checkbox('disablemic_default', 0)
            );
            $renderer->render_group_element(
                'disablecam_editable',
                $renderer->render_group_element_checkbox('disablemic_editable', 1)
            );
        }
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_disableprivatechat(&$renderer) {
        // Configuration for BigBlueButton.
        if ((boolean) validator::section_disableprivatechat_shown()) {
            $renderer->render_group_element(
                'disableprivatechat_default',
                $renderer->render_group_element_checkbox('disableprivatechat_default', 0)
            );
            $renderer->render_group_element(
                'disableprivatechat_editable',
                $renderer->render_group_element_checkbox('disableprivatechat_editable', 1)
            );
        }
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_disablepublicchat(&$renderer) {
        // Configuration for BigBlueButton.
        if ((boolean) validator::section_disablepublicchat_shown()) {
            $renderer->render_group_element(
                'disablepublicchat_default',
                $renderer->render_group_element_checkbox('disablepublicchat_default', 0)
            );
            $renderer->render_group_element(
                'disablepublicchat_editable',
                $renderer->render_group_element_checkbox('disablepublicchat_editable', 1)
            );
        }
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_disablenote(&$renderer) {
        // Configuration for BigBlueButton.
        if ((boolean) validator::section_disablenote_shown()) {
            $renderer->render_group_element(
                'disablenote_default',
                $renderer->render_group_element_checkbox('disablenote_default', 0)
            );
            $renderer->render_group_element(
                'disablenote_editable',
                $renderer->render_group_element_checkbox('disablenote_editable', 1)
            );
        }
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_hideuserlist(&$renderer) {
        // Configuration for BigBlueButton.
        if ((boolean) validator::section_hideuserlist_shown()) {
            $renderer->render_group_element(
                'hideuserlist_default',
                $renderer->render_group_element_checkbox('hideuserlist_default', 0)
            );
            $renderer->render_group_element(
                'hideuserlist_editable',
                $renderer->render_group_element_checkbox('hideuserlist_editable', 1)
            );
        }
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_lockedlayout(&$renderer) {
        // Configuration for BigBlueButton.
        if ((boolean) validator::section_lockedlayout_shown()) {
            $renderer->render_group_element(
                'lockedlayout_default',
                $renderer->render_group_element_checkbox('lockedlayout_default', 0)
            );
            $renderer->render_group_element(
                'lockedlayout_editable',
                $renderer->render_group_element_checkbox('lockedlayout_editable', 1)
            );
        }
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_lockonjoin(&$renderer) {
        // Configuration for BigBlueButton.
        if ((boolean) validator::section_lockonjoin_shown()) {
            $renderer->render_group_element(
                'lockonjoin_default',
                $renderer->render_group_element_checkbox('lockonjoin_default', 0)
            );
            $renderer->render_group_element(
                'lockonjoin_editable',
                $renderer->render_group_element_checkbox('lockonjoin_editable', 1)
            );
        }
    }

    /**
     * Helper function renders general settings if the feature is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_lockonjoinconfigurable(&$renderer) {
        // Configuration for BigBlueButton.
        if ((boolean) validator::section_lockonjoinconfigurable_shown()) {
            $renderer->render_group_element(
                'lockonjoinconfigurable_default',
                $renderer->render_group_element_checkbox('lockonjoinconfigurable_default', 0)
            );
            $renderer->render_group_element(
                'lockonjoinconfigurable_editable',
                $renderer->render_group_element_checkbox('lockonjoinconfigurable_editable', 1)
            );
        }
    }

    /**
     * Helper function renders default messages settings.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_default_messages(&$renderer) {
        $renderer->render_group_header('default_messages');
        $renderer->render_group_element(
            'welcome_default',
            $renderer->render_group_element_textarea('welcome_default', '', PARAM_TEXT)
        );
    }

    /**
     * Helper function renders extended settings if any of the features there is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_extended(&$renderer) {
        // Configuration for extended capabilities.
        if (!(boolean) validator::section_settings_extended_shown()) {
            return;
        }
        $renderer->render_group_header('extended_capabilities');
        // UI for 'notify users when recording ready' feature.
        $renderer->render_group_element(
            'recordingready_enabled',
            $renderer->render_group_element_checkbox('recordingready_enabled', 0)
        );
        // Configuration for extended BN capabilities should go here.
    }

    /**
     * Helper function renders experimental settings if any of the features there is enabled.
     *
     * @param object $renderer
     *
     * @return void
     */
    public static function bigbluebuttonbn_settings_experimental(&$renderer) {
        // Configuration for experimental features should go here.
        $renderer->render_group_header('experimental_features');
        // UI for 'register meeting events' feature.
        $renderer->render_group_element(
            'meetingevents_enabled',
            $renderer->render_group_element_checkbox('meetingevents_enabled', 0)
        );
    }
}