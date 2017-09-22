<?php
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// BigBlueButtonBN configuration file for moodle                         //
//                                                                       //
// This file should be renamed "config.php" in the plugin directory      //
//                                                                       //
// It is intended to be used for setting configuration by default and    //
// also for enable/diable configuration options in the admin setting UI  //
// for those multitenancy deployments where the admin account is given   //
// to the tenant owner and some shared information like the              //
// bigbluebutton_server_url and bigbluebutton_shared_secret must been    //
// kept private. And also when some of the features are going to be      //
// disabled for all the tenants in that server                           //
//                                                                       //
//                                                                       //
///////////////////////////////////////////////////////////////////////////
//                                                                       //
///////////////////////////////////////////////////////////////////////////
/**
 * Configuration file for bigbluebuttonbn
 *
 * @package   mod_bigbluebuttonbn
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

unset($BIGBLUEBUTTONBN_CFG);
global $BIGBLUEBUTTONBN_CFG;
$BIGBLUEBUTTONBN_CFG = new stdClass();

//=========================================================================
// Any parameter included in this fill will not be shown in the admin UI //
// If there was a previous configuration, the parameters here included   //
// will override the parameters already configured (if they were         //
// configured already)                                                   //
//=========================================================================



//=========================================================================
// 1. GENERAL CONFIGURATION
//=========================================================================
// First, you need to configure the credentials for accessing the
// bigbluebutton server.
// The URL of your BigBlueButton server must end with /bigbluebutton/.
// This default URL is for a BigBlueButton server provided by Blindside
// Networks that you can use for testing.

$BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_server_url = 'http://test-install.blindsidenetworks.com/bigbluebutton/';
$BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_shared_secret = '8cd8ef52e8e101574e400365b55e11a6';



//=========================================================================
// 2. CONFIGURATION FOR "RECORDING" FEATURE
//=========================================================================
// Same as for the General Configuration, you need first to set the
// parameter values.
// As these are checkboxes in the moodle admin ui, the expected values
// are 1=checked, 0=unchecked.

// When the value is set to 1 (checked) the bigbluebuttonbn rooms or
// activities will have the recording capability enabled by default.
//$BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recording_default = 1;

// When the value is set to 1 (checked) the recording capability can be
// enabled/disabled by the user creating or editing the room or activity.
//$BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recording_editable = 0;

// When the value is set to 1 (checked) the list of recordings in both
// bigbluebuttonbn and recordingbn are generated using icons.
$BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recording_icons_enabled = 1;



//=========================================================================
// 3. CONFIGURATION FOR "RECORDING TAGGING" FEATURE
//=========================================================================
// The "Recording tagging" feature should be used for adding extra
// information to the recording metadata that later on can be used to
// identify the recording. This allows the user who starts the session
// who is usually a teacher (or anyone with edition capabilities in the
// course) to add an specific name, description and tags that later on
// can be used to identify the recording in the list of recordings.

// When the value is set to 1 (checked) the bigbluebuttonbn rooms or
// activities will have the 'recording tagging' capability enabled by
// default.
$BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordingtagging_default = 0;

// When the value is set to 1 (checked) the recording tagging capability
// can be enabled/disabled by the user creating or editing the room or
// activity.
$BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordingtagging_editable = 1;


//=========================================================================
// 4. CONFIGURATION FOR "IMPORT RECORDINGS" FEATURE
//=========================================================================
// The "Import recordings" feature should only be used by Administrators
// or Teachers (or anyone with edition capabilities in the
// course). When this feature is enabled and the meeting can be recorded,
// a button will be shown in the intermediate page that will allow importing
// recordings from a different activity even from a different course.
//
// When the value is set to 1 (checked) the bigbluebuttonbn rooms or
// activities will have the 'import recordings' capability enabled.
$BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_importrecordings_enabled = 0;

// When the value is set to 1 (checked) the import recordings capability
// can import recordings from deleted activities.
$BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_importrecordings_from_deleted_activities_enabled = 0;


//=========================================================================
// 4. CONFIGURATION FOR "WAIT FOR MODERATOR" FEATURE
//=========================================================================
// This feature makes the rooms or activity work as a traditional classroom
// cloed until the moderator (teacher) comes to unlock the room. The students
// or other viewers must wait until a moderators join to have the
// 'Join session' button enabled

// When the value is set to 1 (checked) the bigbluebuttonbn rooms or
// activities will have the 'wait for moderator' capability enabled by
// default.
//$BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_waitformoderator_default = 0;

// When the value is set to 1 (checked) the 'wait for moderator'
// capability can be enabled/disabled by the user creating or editing
// the room or activity.
//$BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_waitformoderator_editable = 1;


// When the 'wait for moderator' capability is enabled, the ping interval
// is used for pooling the status of the server. Its value is expresed
// in seconds. The default values is 15 secs.
$BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_waitformoderator_ping_interval = 15;

// When the 'wait for moderator' capability is enabled, the ping interval
// is used for pooling the status of the server. But for reducing the
// load to the BigBluebutton server, the information retrieved from it is
// cached. The value is expresed in seconds and is also used for other
// information gathering. The default value is 60 secs.
$BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_waitformoderator_cache_ttl = 60;



//=========================================================================
// 5. CONFIGURATION FOR "STATIC VOICE BRIDGE" FEATURE
//=========================================================================
// A conference voice bridge number can be permanently assigned to a room
// or activity.
$BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_voicebridge_editable = 0;



//=========================================================================
// 6. CONFIGURATION FOR "PRE-UPLOAD PRESENTATION" FEATURE
//=========================================================================
// Since version 0.8, BigBluebutton has an implementation for allowing
// preuploading presentation. When this feature is enabled, users creating or
// editing a room or activity can upload a PDF or Office document to the
// Moodle file repository and vinculate it to the BigBlueButtonBN room or
// activity in one step. This file will be pulled by the BigBluebutton server
// when the meeting session is accessed for the first time.
$BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_preuploadpresentation_enabled = 1;

//=========================================================================
// 7. CONFIGURATION FOR "USER LIMIT" FEATURE
//=========================================================================
// It is possible to establish a limit of users per session. This limit can be
// applied to each room or activity, or globally.

// The number of users allowed in a session by default when a new room or
// conference is added. If the number is set to 0, no limit is established.
//$BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_userlimit_default = 0;

// When the value is set to 1 (checked) the 'wait for moderator'
// capability can be enabled/disabled by the user creating or editing
// the room or activity.
$BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_userlimit_editable = 0;

//=========================================================================
// 8. CONFIGURATION FOR "PERMISSIONS" FEATURE
//=========================================================================
// Defines a rule applied by default to all the new rooms or activities created
// for defining the users who will have access to the meeting session as Moderators.
// By default only the owner is assigned.
// The values for this parameter can be 'owner' and/or any of the roles defined in
// Moodle (including the custom parameters). The value used will be the key for the role.
// [owner|manager|coursecreator|editingteacher|teacher|student|guest|user|frontpage|ANY_CUSTOM_ROLE]

//$BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_moderator_default = 'owner';

//=========================================================================
// 9. CONFIGURATION FOR "NOTIFICATION SENDING" FEATURE
//=========================================================================
// When the value is set to 1 (checked) the 'notification sending'
// capability can be used by the user creating or editing the room or
// activity.
$BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_sendnotifications_enabled = 0;

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!

////////////  CONFIGURATION FOR FEATURES OFFERED BY BN SERVERS  ////////////
//=========================================================================
// 10. CONFIGURATION FOR "RECORDING READY" FEATURE
//=========================================================================
// When the value is set to 1 (checked) the 'notify users when recording ready'
// capability is enabled, meaning that a message will be sent to all enrolled
// users in a course when a recording is ready
//$BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordingready_enabled = 0;
//$BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordingstatus_enabled = 0;

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
