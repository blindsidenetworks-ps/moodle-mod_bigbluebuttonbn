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
 * Configuration file for bigbluebuttonbn.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/*
 * This file should be renamed to "config.php" in the plugin directory
 *
 * It is intended to be used for setting configuration by default and
 * also for enable/diable configuration options in the admin setting UI
 * for those multitenancy deployments where the admin account is given
 * to the tenant owner and some shared information like the
 * bigbluebutton_server_url and bigbluebutton_shared_secret must been
 * kept private. And also when some of the features are going to be
 * disabled for all the tenants in that server
 **/

/*
 * Any parameter included in this fill will not be shown in the admin UI
 * If there was a previous configuration, the parameters here included
 * will override the parameters already configured (if they were
 * configured already)
** ------------------------------------------------------------------- **/

/*
 * 1. GENERAL CONFIGURATION
 ** ------------------------------------------------------------------ **
 **/

/*
 * 1.1. BIGBLUEBUTTON SERVER CONFIGURATION
 *
 * First, you need to configure the credentials for accessing the
 * bigbluebutton server.
 * The URL of your BigBlueButton server must end with /bigbluebutton/.
 * This default URL is for a BigBlueButton server provided by Blindside
 * Networks that you can use for testing.
 **/

$CFG->bigbluebuttonbn['server_url'] = 'http://test-install.blindsidenetworks.com/bigbluebutton/';
$CFG->bigbluebuttonbn['shared_secret'] = '8cd8ef52e8e101574e400365b55e11a6';

/*
 * 1.2. CONFIGURATION FOR "RECORDING" FEATURE
 *
 * Same as for the General Configuration, you need first to set the
 * parameter values.
 * As these are checkboxes in the moodle admin ui, the expected values
 * are 1=checked, 0=unchecked.
 **/

/* When the value is set to 0 (unchecked) the all the features for recordings
 *  are ignored. Recording features are enabled by default.
 *  $CFG->bigbluebuttonbn['recordings_enabled'] = 1;
 */

/* When the value is set to 1 (checked) the bigbluebuttonbn rooms or
 *  activities will have the recording capability enabled by default.
 *  $CFG->bigbluebuttonbn['recording_default'] = 1;
 */

/* When the value is set to 1 (checked) the recording capability can be
 *  enabled/disabled by the user creating or editing the room or activity.
 *  $CFG->bigbluebuttonbn['recording_editable'] = 0;
 */

/* When the value is set to 1 (checked) the list of recordings in both
 * bigbluebuttonbn and recordingbn are generated using icons.
 * $CFG->bigbluebuttonbn['recording_icons_enabled'] = 1;
 */

/*
 * 1.3. CONFIGURATION FOR "IMPORT RECORDINGS" FEATURE
 *
 * The "Import recordings" feature should only be used by Administrators
 * or Teachers (or anyone with edition capabilities in the
 * course). When this feature is enabled and the meeting can be recorded,
 * a button will be shown in the intermediate page that will allow importing
 * recordings from a different activity even from a different course.
 **/

 /*
 * When the value is set to 1 (checked) the bigbluebuttonbn rooms or
 * activities will have the 'import recordings' capability enabled.
 * $CFG->bigbluebuttonbn['importrecordings_enabled'] = 0;
 */

/*
 * When the value is set to 1 (checked) the import recordings capability
 * can import recordings from deleted activities.
 * $CFG->bigbluebuttonbn['importrecordings_from_deleted_enabled'] = 0;
 */

/*
 * 1.4. CONFIGURATION FOR "WAIT FOR MODERATOR" FEATURE
 *
 * This feature makes the rooms or activity work as a traditional classroom
 * cloed until the moderator (teacher) comes to unlock the room. The students
 * or other viewers must wait until a moderators join to have the
 * 'Join session' button enabled
 **/

 /*
 * When the value is set to 1 (checked) the bigbluebuttonbn rooms or
 * activities will have the 'wait for moderator' capability enabled by
 * default.
 * $CFG->bigbluebuttonbn['waitformoderator_default'] = 0;
 */

/*
 * When the value is set to 1 (checked) the 'wait for moderator'
 * capability can be enabled/disabled by the user creating or editing
 * the room or activity.
 * $CFG->bigbluebuttonbn['waitformoderator_editable'] = 1;
 */

/*
 * When the 'wait for moderator' capability is enabled, the ping interval
 * is used for pooling the status of the server. Its value is expresed
 * in seconds. The default values is 15 secs.
 * $CFG->bigbluebuttonbn['waitformoderator_ping_interval'] = 15;
 */

/*
 * When the 'wait for moderator' capability is enabled, the ping interval
 * is used for pooling the status of the server. But for reducing the
 * load to the BigBluebutton server, the information retrieved from it is
 * cached. The value is expresed in seconds and is also used for other
 * information gathering. The default value is 60 secs.
 * $CFG->bigbluebuttonbn['waitformoderator_cache_ttl'] = 60;
 */

/*
 * 1.5. CONFIGURATION FOR "STATIC VOICE BRIDGE" FEATURE
 *
 **/
/*
 * A conference voice bridge number can be permanently assigned to a room
 * or activity.
 * $CFG->bigbluebuttonbn['voicebridge_editable'] = 0;
 */

/*
 * 1.6. CONFIGURATION FOR "PRE-UPLOAD PRESENTATION" FEATURE
 *
 **/
/*
 * Since version 0.8, BigBluebutton has an implementation for allowing
 * preuploading presentation. When this feature is enabled, users creating or
 * editing a room or activity can upload a PDF or Office document to the
 * Moodle file repository and vinculate it to the BigBlueButtonBN room or
 * activity in one step. This file will be pulled by the BigBluebutton server
 * when the meeting session is accessed for the first time.
 * $CFG->bigbluebuttonbn['preuploadpresentation_enabled'] = 1;
 */

/*
 * 1.7. CONFIGURATION FOR "USER LIMIT" FEATURE
 *
 * It is possible to establish a limit of users per session. This limit can be
 * applied to each room or activity, or globally.
 **/

/*
  * The number of users allowed in a session by default when a new room or
  * conference is added. If the number is set to 0, no limit is established.
  * $CFG->bigbluebuttonbn['userlimit_default'] = 0;
  */

/*
 * When the value is set to 1 (checked) the 'wait for moderator'
 * capability can be enabled/disabled by the user creating or editing
 * the room or activity.
 * $CFG->bigbluebuttonbn['userlimit_editable'] = 0;
 */

/*
 * 1.8. CONFIGURATION FOR "PERMISSIONS" FEATURE
 *
 * Defines a rule applied by default to all the new rooms or activities created
 * for defining the users who will have access to the meeting session as Moderators.
 * By default only the owner is assigned.
 **/

/*
 * The values for this parameter can be '0' (which identifies the owner) and/or any of the role IDs defined in
 * Moodle (including the custom parameters). The value used will be the key for the role.
 * [owner=0|manager=1|coursecreator=2|editingteacher=3|teacher=4|student=5|guest=6|user=7|frontpage=8|ANY_CUSTOM_ROLE=xx]
 * $CFG->bigbluebuttonbn['participant_moderator_default'] = '0';
 */

/*
 * 1.9. CONFIGURATION FOR "NOTIFICATION SENDING" FEATURE
 *
 **/
/*
 * When the value is set to 1 (checked) the 'notification sending'
 * capability can be used by the user creating or editing the room or
 * activity.
 * $CFG->bigbluebuttonbn['sendnotifications_enabled'] = 0;
 */

/*
 * 1.10. GENERAL CONFIGURATION FOR RECORDINGS UI
 *
 **/
/*
 * When the value is set to 1 (checked) the bigbluebuttonbn resources
 * will show the recodings in an html table by default.
 * $CFG->bigbluebuttonbn['recordings_html_default'] = 0;
 */

/*
 * When the value is set to 1 (checked) the 'html ui' capability can be
 * enabled/disabled by the user creating or editing the resource.
 * $CFG->bigbluebuttonbn['recordings_html_editable'] = 0;
 */

/*
 * When the value is set to 1 (checked) the bigbluebuttonbn resources
 * will show the recodings belonging to deleted activities as part of the list.
 * $CFG->bigbluebuttonbn['recordings_deleted_default'] = 1;
 */

/*
 * When the value is set to 1 (checked) the 'include recordings from deleted activities'
 * capability can be enabled/disabled by the user creating or editing the resource.
 * $CFG->bigbluebuttonbn['recordings_deleted_editable'] = 0;
 */

/*
 * When the value is set to 1 (checked) the bigbluebuttonbn resources for recordings
 * will show only the imported links as part of the list.
 * $CFG->bigbluebuttonbn['recordings_imported_default'] = 0;
 */

/*
 * When the value is set to 1 (checked) the 'show only imported links'
 * capability can be enabled/disabled by the user creating or editing the resource for recordings.
 * $CFG->bigbluebuttonbn['recordings_imported_editable'] = 1;
 */

/*
 * When the value is set to 1 (checked) the bigbluebuttonbn resources
 * will show the recodings with thumbnails.
 * $CFG->bigbluebuttonbn['recordings_preview_default'] = 1;
 */

/*
 * When the value is set to 1 (checked) the 'preview ui' capability can be
 * enabled/disabled by the user creating or editing the resource.
 * $CFG->bigbluebuttonbn['recordings_preview_editable'] = 0;
 */

/*
 * 1.11. GENERAL CONFIGURATION FOR CLIENT TYPE SELECTION
 *
 **/

/*
 * When the value is set to 1 (checked) the 'clienttype' capability is enabled,
 * meaning that the administrator may be able to choose the default web client type
 * and wheter it can be editable in each room through the plugin configuration
 * $CFG->bigbluebuttonbn['clienttype_enabled'] = 0;
 */

/*
 * The WebClient selected by default is Flash (value = 0)
 * [flash=0|html5=1]
 * $CFG->bigbluebuttonbn['clienttype_default'] = 0;
 */

/*
 * When the value is set to 1 (checked) the WebClient can be chosen by
 * the user creating or editing the resource.
 * $CFG->bigbluebuttonbn['clienttype_editable'] = 0;
 */

/*
 * 1.12. CONFIGURATION FOR "MUTE ON START" FEATURE
 *
 * This feature makes the rooms muted on start. When the users joins to the session,
 * they will be muted.
 *
 **/

/*
 * When the value is set to 1 (checked) the bigbluebuttonbn rooms or
 * activities will have the 'mute on start' capability enabled by
 * default.
 * $CFG->bigbluebuttonbn['muteonstart_default'] = 0;
 */

/*
 * When the value is set to 1 (checked) the 'mute on start'
 * capability can be enabled/disabled by the user creating or editing
 * the room or activity.
 * $CFG->bigbluebuttonbn['muteonstart_editable'] = 0;
 */

/*
 *  2. CONFIGURATION FOR FEATURES OFFERED BY BN SERVERS
 ** ------------------------------------------------------------------ **
 **/

/*
 * 2.1. CONFIGURATION FOR "RECORDING READY" FEATURE
 *
 **/
/*
 * When the value is set to 1 (checked) the 'notify users when recording ready'
 * capability is enabled, meaning that a message will be sent to all enrolled
 * users in a course when a recording is ready
 * $CFG->bigbluebuttonbn['recordingready_enabled'] = 0;
 * $CFG->bigbluebuttonbn['recordingstatus_enabled'] = 0;
 */

/*
 * 2.2. CONFIGURATION FOR "REGISTER MEETING EVENTS" FEATURE
 *
 **/
/*
 * When the value is set to 1 (checked) the 'register meeting events'
 * capability is enabled, meaning that once a recording is processed by BigBlueButton
 * a message containing the events from the live session will be sent to Moodle.
 * These avents are added to the logging system and used for reports
 * $CFG->bigbluebuttonbn['meetingevents_enabled'] = 0;
 */

/*
 * 2.3. CONFIGURATION FOR "GENERAL WARNING MESSAGE" FEATURE
 *
 **/
/*
 * When general_warning_message value is different than "", the string is shown
 * as a warning message to privileged users (administrators and Teachers or users allowed to edit).
 * $CFG->bigbluebuttonbn['general_warning_message'] = "Would you like to record your BigBlueButton sessions for later viewing? ";
 */

 /*
 * The warning box is always shown to administrators, but it is also possible to define other roles
 * to whom the it will be shown. The roles are based on the shortnames defined by Moodle:
 *     'manager,coursecreator,editingteacher,teacher,student,guest,user,frontpage'
 * $CFG->bigbluebuttonbn['general_warning_roles'] = 'editingteacher,teacher';
 */

 /*
 * As the general_warning_message is shown in a box, its type can be defined with general_warning_type
 * The default type is 'info' which is normaly rendered in blue when using a bootstrap theme.
 * All the modifiers for boxed in bootstrap can be used [info|success|warning|danger].
 * $CFG->bigbluebuttonbn['general_warning_box_type'] = 'info';
 */

 /*
 * Additionally, when general_warning_button_href value is different than "", a button
 * can also be shown right after the message.
 * $CFG->bigbluebuttonbn['general_warning_button_href'] = "http://blindsidenetworks.com/";
 */

 /*
 * Finally, the text and class for the button can be modified
 * $CFG->bigbluebuttonbn['general_warning_button_text'] = "Upgrade your site";
 * $CFG->bigbluebuttonbn['general_warning_button_class'] = "btn btn-primary";
 */
