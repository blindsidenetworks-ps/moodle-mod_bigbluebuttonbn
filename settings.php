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

    $settings->add( new admin_setting_heading('bigbluebuttonbn_ui_heading',
            get_string('config_ui', 'bigbluebuttonbn'),
            get_string('config_ui_description', 'bigbluebuttonbn')));

    //ui and default value for 'recording' feature
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_recording_editable',
            get_string('config_ui_recording_editable', 'bigbluebuttonbn'),
            get_string('config_ui_recording_editable_description', 'bigbluebuttonbn'),
            1));
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_recording_default',
            get_string('config_ui_recording_default', 'bigbluebuttonbn'),
            get_string('config_ui_recording_default_description', 'bigbluebuttonbn'),
            1));
    //ui and default value for 'wait for moderator' feature
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_waitformoderator_editable',
            get_string('config_ui_waitformoderator_editable', 'bigbluebuttonbn'),
            get_string('config_ui_waitformoderator_editable_description', 'bigbluebuttonbn'),
            1));
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_waitformoderator_default',
            get_string('config_ui_waitformoderator_default', 'bigbluebuttonbn'),
            get_string('config_ui_waitformoderator_default_description', 'bigbluebuttonbn'),
            0));
    //ui and default value for 'open in a new window' feature
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_newwindow_editable',
            get_string('config_ui_newwindow_editable', 'bigbluebuttonbn'),
            get_string('config_ui_newwindow_editable_description', 'bigbluebuttonbn'),
            1));
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_newwindow_default',
            get_string('config_ui_newwindow_default', 'bigbluebuttonbn'),
            get_string('config_ui_newwindow_default_description', 'bigbluebuttonbn'),
            1));
    //voicebridge
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_voicebridge_editable',
            get_string('config_ui_voicebridge_editable', 'bigbluebuttonbn'),
            get_string('config_ui_voicebridge_editable_description', 'bigbluebuttonbn'),
            0));
    //ui and default value for 'recording tagging' feature
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_recordingtagging_editable',
            get_string('config_ui_recordingtagging_editable', 'bigbluebuttonbn'),
            get_string('config_ui_recordingtagging_editable_description', 'bigbluebuttonbn'),
            1));
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_recordingtagging_default',
            get_string('config_ui_recordingtagging_default', 'bigbluebuttonbn'),
            get_string('config_ui_recordingtagging_default_description', 'bigbluebuttonbn'),
            0));
    
    
    $settings->add( new admin_setting_heading('bigbluebuttonbn_permission_heading',
            get_string('config_permission', 'bigbluebuttonbn'),
            get_string('config_permission_description', 'bigbluebuttonbn')));

}

?>
