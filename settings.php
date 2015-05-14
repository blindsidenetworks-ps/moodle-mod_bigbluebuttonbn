<?php
/**
 * Settings for BigBlueButtonBN
 *
 * @package   mod_bigbluebuttonbn
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2010-2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/locallib.php');

if ($ADMIN->fulltree) {
    $settings->add( new admin_setting_heading('bigbluebuttonbn_config_general',
            get_string('config_general', 'bigbluebuttonbn'),
            get_string('config_general_description', 'bigbluebuttonbn')));

    $settings->add( new admin_setting_configtext( 'bigbluebuttonbn_server_url',
            get_string( 'config_server_url', 'bigbluebuttonbn' ),
            get_string( 'config_server_url_description', 'bigbluebuttonbn' ),
            'http://test-install.blindsidenetworks.com/bigbluebutton/'));
    $settings->add( new admin_setting_configtext( 'bigbluebuttonbn_shared_secret',
            get_string( 'config_shared_secret', 'bigbluebuttonbn' ),
            get_string( 'config_shared_secret_description', 'bigbluebuttonbn' ), 
            '8cd8ef52e8e101574e400365b55e11a6'));

    //// Configuration for recording feature
    $settings->add( new admin_setting_heading('bigbluebuttonbn_recording',
            get_string('config_feature_recording', 'bigbluebuttonbn'),
            get_string('config_feature_recording_description', 'bigbluebuttonbn')));
    // default value for 'recording' feature
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_recording_default',
            get_string('config_feature_recording_default', 'bigbluebuttonbn'),
            get_string('config_feature_recording_default_description', 'bigbluebuttonbn'),
            1));
    // UI for 'recording' feature
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_recording_editable',
            get_string('config_feature_recording_editable', 'bigbluebuttonbn'),
            get_string('config_feature_recording_editable_description', 'bigbluebuttonbn'),
            1));
    // Front panel for 'recording' managment feature
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_recording_icons_enabled',
            get_string('config_feature_recording_icons_enabled', 'bigbluebuttonbn'),
            get_string('config_feature_recording_icons_enabled_description', 'bigbluebuttonbn'),
            1));
    
    //// Configuration for recording feature
    $settings->add( new admin_setting_heading('bigbluebuttonbn_recordingtagging',
            get_string('config_feature_recordingtagging', 'bigbluebuttonbn'),
            get_string('config_feature_recordingtagging_description', 'bigbluebuttonbn')));
    // default value for 'recording tagging' feature
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_recordingtagging_default',
            get_string('config_feature_recordingtagging_default', 'bigbluebuttonbn'),
            get_string('config_feature_recordingtagging_default_description', 'bigbluebuttonbn'),
            0));
    // UI for 'recording tagging' feature
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_recordingtagging_editable',
            get_string('config_feature_recordingtagging_editable', 'bigbluebuttonbn'),
            get_string('config_feature_recordingtagging_editable_description', 'bigbluebuttonbn'),
            1));

    //// Configuration for wait for moderator feature
    $settings->add( new admin_setting_heading('bigbluebuttonbn_feature_waitformoderator',
            get_string('config_feature_waitformoderator', 'bigbluebuttonbn'),
            get_string('config_feature_waitformoderator_description', 'bigbluebuttonbn')));
    //default value for 'wait for moderator' feature
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_waitformoderator_default',
            get_string('config_feature_waitformoderator_default', 'bigbluebuttonbn'),
            get_string('config_feature_waitformoderator_default_description', 'bigbluebuttonbn'),
            0));
    // UI for 'wait for moderator' feature
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_waitformoderator_editable',
            get_string('config_feature_waitformoderator_editable', 'bigbluebuttonbn'),
            get_string('config_feature_waitformoderator_editable_description', 'bigbluebuttonbn'),
            1));
    //ping interval value for 'wait for moderator' feature
    $settings->add(new admin_setting_configtext('bigbluebuttonbn_waitformoderator_ping_interval',
            get_string('config_feature_waitformoderator_ping_interval', 'bigbluebuttonbn'),
            get_string('config_feature_waitformoderator_ping_interval_description', 'bigbluebuttonbn'),
            10, PARAM_INT));
    //cache TTL value for 'wait for moderator' feature
    $settings->add(new admin_setting_configtext('bigbluebuttonbn_waitformoderator_cache_ttl',
            get_string('config_feature_waitformoderator_cache_ttl', 'bigbluebuttonbn'),
            get_string('config_feature_waitformoderator_cache_ttl_description', 'bigbluebuttonbn'),
            60, PARAM_INT));

    $settings->add( new admin_setting_heading('bigbluebuttonbn_feature_voicebridge',
            get_string('config_feature_voicebridge', 'bigbluebuttonbn'),
            get_string('config_feature_voicebridge_description', 'bigbluebuttonbn')));
    // UI for establishing static voicebridge
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_voicebridge_editable',
            get_string('config_feature_voicebridge_editable', 'bigbluebuttonbn'),
            get_string('config_feature_voicebridge_editable_description', 'bigbluebuttonbn'),
            0));

    $settings->add( new admin_setting_heading('bigbluebuttonbn_feature_preuploadpresentation',
            get_string('config_feature_preuploadpresentation', 'bigbluebuttonbn'),
            get_string('config_feature_preuploadpresentation_description', 'bigbluebuttonbn')));
    // UI for 'preupload presentation' feature
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_preuploadpresentation_enabled',
            get_string('config_feature_preuploadpresentation_enabled', 'bigbluebuttonbn'),
            get_string('config_feature_preuploadpresentation_enabled_description', 'bigbluebuttonbn'),
            0));

    $settings->add( new admin_setting_heading('config_scheduled',
            get_string('config_scheduled', 'bigbluebuttonbn'),
            get_string('config_scheduled_description', 'bigbluebuttonbn')));
    // calculated duration for 'scheduled session' feature
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_scheduled_duration_enabled',
            get_string('config_scheduled_duration_enabled', 'bigbluebuttonbn'),
            get_string('config_scheduled_duration_enabled_description', 'bigbluebuttonbn'),
            1));
    // compensatory time for 'scheduled session' feature
    $settings->add(new admin_setting_configtext('bigbluebuttonbn_scheduled_duration_compensation',
            get_string('config_scheduled_duration_compensation', 'bigbluebuttonbn'),
            get_string('config_scheduled_duration_compensation_description', 'bigbluebuttonbn'),
            10, PARAM_INT));
    // pre-opening time for 'scheduled session' feature
    $settings->add(new admin_setting_configtext('bigbluebuttonbn_scheduled_pre_opening',
            get_string('config_scheduled_pre_opening', 'bigbluebuttonbn'),
            get_string('config_scheduled_pre_opening_description', 'bigbluebuttonbn'),
            10, PARAM_INT));
    
    $settings->add( new admin_setting_heading('bigbluebuttonbn_permission',
            get_string('config_permission', 'bigbluebuttonbn'),
            get_string('config_permission_description', 'bigbluebuttonbn')));
    // UI for 'permissions' feature
    $roles = bigbluebuttonbn_get_roles('all', 'array');
    $owner = array('owner' => get_string('mod_form_field_participant_list_type_owner', "bigbluebuttonbn"));
    $settings->add(new admin_setting_configmultiselect('bigbluebuttonbn_moderator_default',
            get_string('config_permission_moderator_default', 'bigbluebuttonbn'),
            get_string('config_permission_moderator_default_description', 'bigbluebuttonbn'),
            array_keys($owner), array_merge($owner, $roles)));

    $settings->add( new admin_setting_heading('bigbluebuttonbn_profile',
            get_string('config_feature_predefinedprofile', 'bigbluebuttonbn'),
            get_string('config_feature_predefinedprofile_description', 'bigbluebuttonbn')));
    // UI for 'predefined profile' feature
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_predefinedprofile_enabled',
            get_string('config_feature_predefinedprofile_enabled', 'bigbluebuttonbn'),
            get_string('config_feature_predefinedprofile_enabled_description', 'bigbluebuttonbn'),
            1));
   
    /*
    $settings->add( new admin_setting_heading('bigbluebuttonbn_extended',
            get_string('config_feature_extendedfeatures', 'bigbluebuttonbn'),
            get_string('config_feature_extendedfeatures_description', 'bigbluebuttonbn')));
    // ui for 'delegated view' feature
    $settings->add(new admin_setting_configcheckbox('bigbluebuttonbn_predefinedprofile_enabled',
            get_string('config_feature_predefinedprofile_enabled', 'bigbluebuttonbn'),
            get_string('config_feature_predefinedprofile_enabled_description', 'bigbluebuttonbn'),
            1));
    */
}

?>
