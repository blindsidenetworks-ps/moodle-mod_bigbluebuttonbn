<?php
/**
 * Settings for BigBlueButtonBN
 *
 * @package   mod_bigbluebuttonbn
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2010-2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add( new admin_setting_heading('bigbluebuttonbn_config_general_heading',
            get_string('config_general', 'bigbluebuttonbn'),
            get_string('config_general_description', 'bigbluebuttonbn')));

    $settings->add( new admin_setting_configtext( 'bigbluebuttonbn_server_url',
            get_string( 'config_server_url', 'bigbluebuttonbn' ),
            get_string( 'config_server_url_description', 'bigbluebuttonbn' ), 'http://test-install.blindsidenetworks.com/bigbluebutton/' ) );
    $settings->add( new admin_setting_configtext( 'bigbluebuttonbn_shared_secret',
            get_string( 'config_shared_secret', 'bigbluebuttonbn' ),
            get_string( 'config_shared_secret_description', 'bigbluebuttonbn' ), '8cd8ef52e8e101574e400365b55e11a6' ) );

    $settings->add( new admin_setting_heading('bigbluebuttonbn_feature_heading',
            get_string('config_feature', 'bigbluebuttonbn'),
            get_string('config_feature_description', 'bigbluebuttonbn')));
    //default value for 'recording' feature
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_recording_default',
            get_string('config_feature_recording_default', 'bigbluebuttonbn'),
            get_string('config_feature_recording_default_description', 'bigbluebuttonbn'),
            1));
    //default value for 'wait for moderator' feature
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_waitformoderator_default',
            get_string('config_feature_waitformoderator_default', 'bigbluebuttonbn'),
            get_string('config_feature_waitformoderator_default_description', 'bigbluebuttonbn'),
            0));
    //ping interval value for 'wait for moderator' feature
    $settings->add(new admin_setting_configtext_with_advanced('bigbluebuttonbn_waitformoderator_ping_interval',
            get_string('config_feature_waitformoderator_ping_interval', 'bigbluebuttonbn'),
            get_string('config_feature_waitformoderator_ping_interval_description', 'bigbluebuttonbn'),
            array('value' => '10', 'fix' => false), PARAM_INT));
    //cache TTL value for 'wait for moderator' feature
    $settings->add(new admin_setting_configtext_with_advanced('bigbluebuttonbn_waitformoderator_cache_ttl',
            get_string('config_feature_waitformoderator_cache_ttl', 'bigbluebuttonbn'),
            get_string('config_feature_waitformoderator_cache_ttl_description', 'bigbluebuttonbn'),
            60, PARAM_INT));
    //default value for 'open in a new window' feature
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_newwindow_default',
            get_string('config_feature_newwindow_default', 'bigbluebuttonbn'),
            get_string('config_feature_newwindow_default_description', 'bigbluebuttonbn'),
            1));
    //default value for 'recording tagging' feature
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_recordingtagging_default',
            get_string('config_feature_recordingtagging_default', 'bigbluebuttonbn'),
            get_string('config_feature_recordingtagging_default_description', 'bigbluebuttonbn'),
            0));
    
    $settings->add( new admin_setting_heading('bigbluebuttonbn_ui_heading',
            get_string('config_ui', 'bigbluebuttonbn'),
            get_string('config_ui_description', 'bigbluebuttonbn')));

    //ui for 'recording' feature
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_recording_editable',
            get_string('config_ui_recording_editable', 'bigbluebuttonbn'),
            get_string('config_ui_recording_editable_description', 'bigbluebuttonbn'),
            1));
    //ui for 'wait for moderator' feature
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_waitformoderator_editable',
            get_string('config_ui_waitformoderator_editable', 'bigbluebuttonbn'),
            get_string('config_ui_waitformoderator_editable_description', 'bigbluebuttonbn'),
            1));
    //ui for 'open in a new window' feature
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_newwindow_editable',
            get_string('config_ui_newwindow_editable', 'bigbluebuttonbn'),
            get_string('config_ui_newwindow_editable_description', 'bigbluebuttonbn'),
            1));
    //ui for establishing static voicebridge
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_voicebridge_editable',
            get_string('config_ui_voicebridge_editable', 'bigbluebuttonbn'),
            get_string('config_ui_voicebridge_editable_description', 'bigbluebuttonbn'),
            0));
    //ui for 'recording tagging' feature
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_recordingtagging_editable',
            get_string('config_ui_recordingtagging_editable', 'bigbluebuttonbn'),
            get_string('config_ui_recordingtagging_editable_description', 'bigbluebuttonbn'),
            1));
    
    
    $settings->add( new admin_setting_heading('bigbluebuttonbn_permission_heading',
            get_string('config_permission', 'bigbluebuttonbn'),
            get_string('config_permission_description', 'bigbluebuttonbn')));

    $options = array(
            '1' => get_string('mod_form_field_participant_list_type_all', 'bigbluebuttonbn'),
            '2' => get_string('mod_form_field_participant_list_type_role', 'bigbluebuttonbn'),
            '3' => get_string('mod_form_field_participant_list_type_owner', 'bigbluebuttonbn')
            );

    $settings->add(new admin_setting_configselect('bigbluebuttonbn_moderator_default',
            get_string('config_permission_moderator_default', 'bigbluebuttonbn'),
            get_string('config_permission_moderator_default_description', 'bigbluebuttonbn'),
            3, $options));
}

?>
