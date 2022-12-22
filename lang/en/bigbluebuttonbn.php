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
 * Language File.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 */
defined('MOODLE_INTERNAL') || die();

$string['activityoverview'] = 'You have upcoming BigBlueButton sessions';
$string['bbbduetimeoverstartingtime'] = 'The close time must be later than the open time.';
$string['bbbdurationwarning'] = 'The maximum duration for this session is %duration% minutes.';
$string['bbbrecordwarning'] = 'This session may be recorded.';
$string['bbbrecordallfromstartwarning'] = 'This session is being recorded from the start.';
$string['bigbluebuttonbn:addinstance'] = 'Add a new BigBlueButton room';
$string['bigbluebuttonbn:join'] = 'Join a BigBlueButton room';
$string['bigbluebuttonbn:view'] = 'View BigBlueButton room';
$string['bigbluebuttonbn:addinstancewithmeeting'] = 'Create live sessions instance';
$string['bigbluebuttonbn:addinstancewithrecording'] = 'Create instance with recordings';
$string['bigbluebuttonbn:managerecordings'] = 'Manage recordings';
$string['bigbluebuttonbn:publishrecordings'] = 'Publish recordings';
$string['bigbluebuttonbn:unpublishrecordings'] = 'Unpublish recordings';
$string['bigbluebuttonbn:protectrecordings'] = 'Protect recordings';
$string['bigbluebuttonbn:unprotectrecordings'] = 'Unprotect recordings';
$string['bigbluebuttonbn:deleterecordings'] = 'Delete recordings';
$string['bigbluebuttonbn:importrecordings'] = 'Import recordings';
$string['bigbluebuttonbn'] = 'BigBlueButton';
$string['cannotperformaction'] = 'Cannot perform action {$a} on this recording';
$string['indicator:cognitivedepth'] = 'BigBlueButton cognitive';
$string['indicator:cognitivedepth_help'] = 'This indicator is based on the cognitive depth reached by the student in a BigBlueButton activity.';
$string['indicator:socialbreadth'] = 'BigBlueButton social';
$string['indicator:socialbreadth_help'] = 'This indicator is based on the social breadth reached by the student in a BigBlueButton activity.';
$string['modulename'] = 'BigBlueButton';
$string['modulenameplural'] = 'BigBlueButton';
$string['modulename_help'] = 'BigBlueButton is an open-source web conferencing system designed for online learning, which supports real-time sharing of audio, video, chat, slides, screen sharing, a multi-user whiteboard, breakout rooms, polling and emojis.

Using BigBlueButton you can create a room for sessions such as live online classes, virtual office hours or group collaboration with remote students. The session can be recorded for later playback.';
$string['modulename_link'] = 'BigBlueButton/view';
$string['nosuchinstance'] = 'No such instance {$a->entity} with ID {$a->id}';
$string['pluginadministration'] = 'BigBlueButton administration';
$string['pluginname'] = 'BigBlueButton';

$string['removedevents'] = 'Deleted events';
$string['removedtags'] = 'Deleted tags';
$string['removedlogs'] = 'Deleted custom logs';
$string['removedrecordings'] = 'Deleted recordings';
$string['resetevents'] = 'Delete events';
$string['resettags'] = 'Delete tags';
$string['resetlogs'] = 'Delete custom logs';
$string['resetrecordings'] = 'Delete recordings';
$string['resetlogs_help'] = 'Deleting the logs will result in lost references to recordings.';
$string['resetrecordings_help'] = 'Deleting the recordings will make them inaccessible from anywhere. This action cannot be undone!';

$string['search:activity'] = 'BigBlueButton - activity information';
$string['search:tags'] = 'BigBlueButton - tags information';
$string['settings'] = 'BigBlueButton settings';
$string['privacy:metadata:bigbluebuttonbn'] = 'BigBlueButton session configuration';
$string['privacy:metadata:bigbluebuttonbn:participants'] = 'A list of rules that define the role users will have in the BigBlueButton session. A user ID may be stored as permissions can be granted per role or per user.';
$string['privacy:metadata:bigbluebuttonbn_logs'] = 'Stores events triggered when using the plugin.';
$string['privacy:metadata:bigbluebuttonbn_logs:userid'] = 'The user ID of the user who triggered the event.';
$string['privacy:metadata:bigbluebuttonbn_logs:timecreated'] = 'The time when the log was created.';
$string['privacy:metadata:bigbluebuttonbn_logs:meetingid'] = 'The session ID the user had access to.';
$string['privacy:metadata:bigbluebuttonbn_logs:log'] = 'The type of event triggered by the user.';
$string['privacy:metadata:bigbluebuttonbn_logs:meta'] = 'Additional information related to the session or the recording.';
$string['privacy:metadata:bigbluebutton'] = 'In order to create and join BigBlueButton sessions, user data needs to be exchanged with the server.';
$string['privacy:metadata:bigbluebutton:userid'] = 'The user ID of the user accessing the BigBlueButton server.';
$string['privacy:metadata:bigbluebutton:fullname'] = 'The full name of the user accessing the BigBlueButton server.';
$string['privacy:metadata:bigbluebuttonbn_recordings'] = 'Stores metadata about recordings.';
$string['privacy:metadata:bigbluebuttonbn_recordings:userid'] = 'The user ID of the user who last changed a recording.';

$string['completionattendance'] = 'Student must attend the session for:';
$string['completionattendance_desc'] = 'Enter and remain in the room for at least {$a} minute(s).';
$string['completionattendance_event_desc'] = 'Student has entered the room and remained in the session for at least {$a} minute(s)';
$string['completionattendancegroup'] = 'Require attendance';
$string['completionattendancegroup_help'] = 'Attending the meeting for (n) minutes is required for completion.';

$string['completionengagementchats'] = 'Chats';
$string['completionengagementchats_desc'] = 'Participate in {$a} chat(s).';
$string['completionengagementchats_event_desc'] = 'Has raised {$a} chat(s)';
$string['completionengagementtalks'] = 'Talk';
$string['completionengagementtalks_desc'] = 'Talk {$a} time(s)';
$string['completionengagementtalks_event_desc'] = 'Has raised {$a} talk(s)';
$string['completionengagementraisehand'] = 'Require raised hand';
$string['completionengagementraisehand_desc'] = 'Raise hand {$a} time(s).';
$string['completionengagementraisehand_event_desc'] = 'Has raised hand {$a} times';
$string['completionengagementpollvotes'] = 'Poll votes';
$string['completionengagementpollvotes_desc'] = 'Vote in polls {$a} time(s).';
$string['completionengagementpollvotes_event_desc'] = 'Has answered {$a} poll vote(s)';
$string['completionengagementemojis'] = 'Emojis';
$string['completionengagementemojis_desc'] = 'Change {$a} times his/her emoji(s).';
$string['completionengagementemojis_event_desc'] = 'Changed {$a} time his/her emoji(s)';

$string['completionengagement_desc'] = 'Engage in activities during the meeting.';
$string['completionengagementgroup'] = 'Require participation';
$string['completionengagementgroup_help'] = 'Active participation during the session is required for completion.';

$string['completionupdatestate'] = 'Completion update state';
$string['completionvalidatestate'] = 'Validate completion';
$string['completionvalidatestatetriggered'] = 'Validate completion has been triggered.';

$string['completionview'] = 'Require view';
$string['completionview_desc'] = 'Student must view the room to complete it.';
$string['completionview_event_desc'] = 'Has viewed the room.';
$string['sendnotification'] = 'Send notification';

$string['minute'] = 'minute';
$string['minutes'] = 'minutes';

$string['config_general'] = 'General settings';
$string['config_general_description'] = 'These settings are always used.';
$string['config_server_url'] = 'BigBlueButton Server URL';
$string['config_server_url_description'] = 'The URL of your BigBlueButton server must end with /bigbluebutton/. (This default URL is for a BigBlueButton server provided by Blindside Networks that you can use for testing.)';
$string['config_shared_secret'] = 'BigBlueButton Shared Secret';
$string['config_shared_secret_description'] = 'The security salt of your BigBlueButton server.  (This default salt is for a BigBlueButton server provided by Blindside Networks that you can use for testing.)';
$string['config_checksum_algorithm'] = 'BigBlueButton Checksum Algorithm';
$string['config_checksum_algorithm_description'] = 'The checksum algorithm of your BigBlueButton server.
(SHA1 guarantees compatibility with older server versions but is less secure whereas SHA512 is FIPS 140-2 compliant.)';

$string['config_recording'] = 'Recording';
$string['config_recording_description'] = 'These settings are feature specific';
$string['config_recording_default'] = 'Recording enabled by default';
$string['config_recording_default_description'] = 'Should the setting \'Session can be recorded\' be enabled by default when adding a new BigBlueButton room?';
$string['config_recording_editable'] = 'Session can be recorded editable';
$string['config_recording_editable_description'] = 'Should \'Session can be recorded\' be editable in the BigBlueButton activity settings?';
$string['config_recording_protect_editable'] = 'Protected recordings state can be edited';
$string['config_recording_protect_editable_description'] = 'If checked the interface includes an option for protecting/unprotecting recordings.';
$string['config_recording_all_from_start_default'] = 'Start recording from the beginning';
$string['config_recording_all_from_start_default_description'] = 'Should the setting \'Start recording from the beginning\' be enabled by default when adding a new BigBlueButton room?';
$string['config_recording_all_from_start_editable'] = 'Start recording from the beginning editable';
$string['config_recording_all_from_start_editable_description'] = 'Should \'Start recording from the beginning\' be editable in the BigBlueButton activity settings?';
$string['config_recording_hide_button_default'] = 'Hide recording button';
$string['config_recording_hide_button_default_description'] = 'If checked the button for record will be hidden';
$string['config_recording_hide_button_editable'] = 'Hide recording button editable';
$string['config_recording_hide_button_editable_description'] = 'Should \'Hide recording button\' be editable in the BigBlueButton activity settings?';
$string['config_recording_refresh_period'] = 'Recording refresh period (in seconds)';
$string['config_recording_refresh_period_description'] = 'How often should the BigBlueButton server be queried to refresh remote information for a recording?';
$string['config_recordings'] = 'Show recordings';
$string['config_recordings_description'] = 'These settings are feature specific';
$string['config_recordings_general'] = 'Show recording settings';
$string['config_recordings_general_description'] = 'These settings are used only when showing recordings';
$string['config_recordings_imported_default'] = 'Show only imported links enabled by default';
$string['config_recordings_imported_default_description'] = 'If enabled the recording table will include only the imported links to recordings.';
$string['config_recordings_imported_editable'] = 'Show only imported links feature can be edited';
$string['config_recordings_imported_editable_description'] = 'Show only imported links by default can be edited when the instance is added or updated.';
$string['config_recordings_preview_default'] = 'Preview is enabled by default';
$string['config_recordings_preview_default_description'] = 'If enabled the table includes a preview of the presentation.';
$string['config_recordings_preview_editable'] = 'Preview feature can be edited';
$string['config_recordings_preview_editable_description'] = 'Preview feature can be edited when the instance is added or updated.';
$string['config_recordings_sortorder'] = 'Order the recordings in ascending order.';
$string['config_recordings_sortorder_description'] = 'By default recordings are displayed in descending order. When checked they will be sorted in ascending order.';

$string['config_importrecordings'] = 'Import recordings';
$string['config_importrecordings_description'] = 'These settings are feature specific.';
$string['config_importrecordings_enabled'] = 'Import recordings enabled';
$string['config_importrecordings_enabled_description'] = 'When this and the recording feature are enabled, it is possible to import recordings from different courses into an activity.';
$string['config_importrecordings_from_deleted_enabled'] = 'Import recordings from deleted activities enabled';
$string['config_importrecordings_from_deleted_enabled_description'] = 'When this and the import recording feature are enabled, it is possible to import recordings from activities that are no longer in the course.';

$string['config_waitformoderator'] = 'Wait for moderator';
$string['config_waitformoderator_description'] = 'These settings are feature specific';
$string['config_waitformoderator_default'] = 'Wait for moderator enabled by default';
$string['config_waitformoderator_default_description'] = 'Should the setting \'Wait for moderator\' be enabled by default when adding a new BigBlueButton room?';
$string['config_waitformoderator_editable'] = 'Wait for moderator editable';
$string['config_waitformoderator_editable_description'] = 'Should \'Wait for moderator\' be editable in the BigBlueButton activity settings?';
$string['config_waitformoderator_ping_interval'] = 'Wait for moderator ping (seconds)';
$string['config_waitformoderator_ping_interval_description'] = 'How often should the server be pinged to check if the moderator has entered the room?';
$string['config_waitformoderator_cache_ttl'] = 'Wait for moderator cache TTL (seconds)';
$string['config_waitformoderator_cache_ttl_description'] = 'To support a heavy load of clients this plugin makes use of a cache. This parameter defines the time the cache will be kept before the next request is sent to the BigBlueButton server.';

$string['config_voicebridge'] = 'Voice bridge';
$string['config_voicebridge_description'] = 'These settings enable or disable options in the UI and also define default values for these options.';
$string['config_voicebridge_editable'] = 'Conference voice bridge can be edited';
$string['config_voicebridge_editable_description'] = 'A conference voice bridge number can be permanently assigned to a room. When assigned, the number cannot be used for any other room.';

$string['config_preuploadpresentation'] = 'Pre-upload presentation';
$string['config_preuploadpresentation_description'] = 'These settings enable or disable options in the UI and also define default values for these options. The feature works only if the Moodle server is accessible to BigBlueButton.';
$string['config_preuploadpresentation_editable'] = 'Pre-uploading presentation editable';
$string['config_preuploadpresentation_editable_description'] = 'Preupload presentation feature is editable in the UI when the room or conference is added or updated.';

$string['config_presentation_default'] = 'Default presentation file';
$string['config_presentation_default_description'] = 'A file may be provided for use in all rooms.';

$string['config_participant'] = 'Participants';
$string['config_participant_description'] = 'These settings define the default role for participants.';
$string['config_participant_moderator_default'] = 'Moderator';
$string['config_participant_moderator_default_description'] = 'This rule is used by default when a new room is added.';

$string['config_userlimit'] = 'User limit';
$string['config_userlimit_description'] = 'These settings enable or disable options in the UI and also define default values for these options.';
$string['config_userlimit_default'] = 'User limit enabled by default';
$string['config_userlimit_default_description'] = 'The number of users allowed in a session by default when a new room is added. Set to 0 to allow an unlimited number of users.';
$string['config_userlimit_editable'] = 'User limit feature can be edited';
$string['config_userlimit_editable_description'] = 'User limit value by default can be edited when the room or conference is added or updated.';

$string['config_scheduled'] = 'Scheduled sessions';
$string['config_scheduled_description'] = 'These settings define default behaviour for scheduled sessions.';
$string['config_scheduled_pre_opening'] = 'Accessible before opening time';
$string['config_scheduled_pre_opening_description'] = 'The time in minutes that the room is open for prior to the scheduled opening time.';

$string['config_sendnotifications'] = 'Configuration for "Send notifications" feature';
$string['config_sendnotifications_description'] = 'These settings enable or disable options in the UI and also define default values for these options.';
$string['config_sendnotifications_enabled'] = 'Send notifications enabled';
$string['config_sendnotifications_enabled_description'] = 'If enabled the UI for editing the activity includes an option for sending a notification to enrolled user when the activity is added or updated.';

$string['config_extended_capabilities'] = 'Extended capabilities';
$string['config_extended_capabilities_description'] = 'Configuration for extended capabilities when the BigBlueButton server offers them.';
$string['config_uidelegation_enabled'] = 'UI delegation is enabled';
$string['config_uidelegation_enabled_description'] = 'These settings enable or disable the UI delegation to the BigBlueButton server.';
$string['config_recordingready_enabled'] = 'Send recording available notification';
$string['config_recordingready_enabled_description'] = 'If enabled, a notification will be sent when a recording link is available. This feature requires the script post_publish_recording_ready_callback to be enabled on the BigBlueButton server.';
$string['config_meetingevents_enabled'] = 'Register live sessions';
$string['config_meetingevents_enabled_description'] = 'If enabled, live sessions will be processed after the session ends. This feature is required for Activity completion and will only work if the BigBlueButton server is capable of processing post_events scripts.';

$string['config_warning_curl_not_installed'] = 'This feature requires the CURL extension for php installed and enabled. The settings will be accessible only if this condition is fulfilled.';
$string['config_warning_bigbluebuttonbn_cfg_deprecated'] = 'BigBlueButton makes use of config.php with a global variable that has been deprecated. Please convert the file as it will not be supported in future versions.';

$string['config_muteonstart'] = 'Mute on start';
$string['config_muteonstart_description'] = 'These settings enable or disable options in the UI and also define default values for these options.';
$string['config_muteonstart_default'] = 'Mute on start enabled by default';
$string['config_muteonstart_default_description'] = 'If enabled the session will be muted on start.';
$string['config_muteonstart_editable'] = 'Mute on start can be edited';
$string['config_muteonstart_editable_description'] = 'Mute on start by default can be edited when the instance is added or updated.';
$string['config_welcome_default'] = 'Default welcome message';
$string['config_welcome_default_description'] = 'The welcome message is displayed when participants enter the room. If the field is left blank, then a message set on the BigBlueButton server is displayed.';
$string['config_welcome_editable'] = 'Default welcome message is editable by teachers';
$string['config_welcome_editable_description'] = 'Welcome message can be edited when the instance is added or updated';
$string['config_default_messages'] = 'Default messages';
$string['config_default_messages_description'] = 'Set message defaults for activities';

$string['config_locksettings'] = 'Configuration for locking settings';
$string['config_locksettings_description'] = 'These settings enable or disable options in the UI for locking settings, and also define default values for these options.';

$string['config_disablecam_default'] = 'Disable webcam enabled by default';
$string['config_disablecam_default_description'] = 'If enabled the webcams will be disabled.';
$string['config_disablecam_editable'] = 'Disable webcam can be edited';
$string['config_disablecam_editable_description'] = 'Disable webcam by default can be edited when the instance is added or updated.';

$string['config_disablemic_default'] = 'Disable mic enabled by default';
$string['config_disablemic_default_description'] = 'If enabled the microphones will be disabled.';
$string['config_disablemic_editable'] = 'Disable mic can be edited';
$string['config_disablemic_editable_description'] = 'Disable mic by default can be edited when the instance is added or updated.';

$string['config_disableprivatechat_default'] = 'Disable private chat enabled by default';
$string['config_disableprivatechat_default_description'] = 'If enabled the private chat will be disabled.';
$string['config_disableprivatechat_editable'] = 'Disable private chat can be edited';
$string['config_disableprivatechat_editable_description'] = 'Disable private chat by default can be edited when the instance is added or updated.';

$string['config_disablepublicchat_default'] = 'Disable public chat enabled by default';
$string['config_disablepublicchat_default_description'] = 'If enabled the public chat will be disabled.';
$string['config_disablepublicchat_editable'] = 'Disable public chat can be edited';
$string['config_disablepublicchat_editable_description'] = 'Disable public chat by default can be edited when the instance is added or updated.';

$string['config_disablenote_default'] = 'Disable shared notes enabled by default';
$string['config_disablenote_default_description'] = 'If enabled the shared notes will be disabled.';
$string['config_disablenote_editable'] = 'Disable shared notes can be edited';
$string['config_disablenote_editable_description'] = 'Disable shared notes by default can be edited when the instance is added or updated.';

$string['config_hideuserlist_default'] = 'Hide user list enabled by default';
$string['config_hideuserlist_default_description'] = 'If enabled the session user list will be hidden.';
$string['config_hideuserlist_editable'] = 'Hide user list can be edited';
$string['config_hideuserlist_editable_description'] = 'Hide user list by default can be edited when the instance is added or updated.';

$string['config_lockonjoin_default'] = 'Lock on join enabled by default';
$string['config_lockonjoin_default_description'] = 'If enabled the settings locked by configuration are applied to the user when they join. Lock configuration must be enabled for this to apply.';
$string['config_lockonjoin_editable'] = 'Lock on join can be edited';
$string['config_lockonjoin_editable_description'] = 'Lock on join by default can be edited when the instance is added or updated.';

$string['config_experimental_features'] = 'Experimental features';
$string['config_experimental_features_description'] = 'Configuration for experimental features.';

$string['general_error_unable_connect'] = 'Unable to connect. Please check the url of the BigBlueButton server AND check to see if the BigBlueButton server is running.
Details : {$a}';
$string['general_error_no_answer'] = 'Empty response. Please check the url of the BigBlueButton server AND check to see if the BigBlueButton server is running.';
$string['general_error_not_allowed_to_create_instances'] = 'User is not allowed to create any type of instance.';
$string['general_error_not_found'] = 'Entity not found : {$a}.';
$string['general_error_cannot_create_meeting'] = 'Cannot create session.';
$string['general_error_cannot_get_recordings'] = 'Cannot get recordings.';
$string['index_confirm_end'] = 'Do you want to end the session?';
$string['index_disabled'] = 'disabled';
$string['index_enabled'] = 'enabled';
$string['index_ending'] = 'Ending the session ... please wait';
$string['index_error_checksum'] = 'A checksum error occurred. Please check that you entered the correct secret.';
$string['index_error_forciblyended'] = 'Unable to join the session because it has been manually ended.';
$string['index_error_unable_display'] = 'Unable to display the sessions. Please check the url of the BigBlueButton server AND check to see if the BigBlueButton server is running.';
$string['index_heading_actions'] = 'Actions';
$string['index_heading_group'] = 'Group';
$string['index_heading_moderator'] = 'Moderators';
$string['index_heading_name'] = 'Room';
$string['index_heading_recording'] = 'Recording';
$string['index_heading_users'] = 'Users';
$string['index_heading_viewer'] = 'Viewers';
$string['index_heading'] = 'BigBlueButton rooms';
$string['instanceprofilewithoutrecordings'] = 'This instance profile cannot display recordings';
$string['mod_form_block_general'] = 'General';
$string['mod_form_block_room'] = 'Room settings';
$string['mod_form_block_recordings'] = 'Recording view';
$string['mod_form_block_presentation'] = 'Presentation content';
$string['mod_form_block_presentation_default'] = 'Presentation default content';
$string['mod_form_block_participants'] = 'Role assigned during live session';
$string['mod_form_block_schedule'] = 'Session timing';
$string['mod_form_block_record'] = 'Record settings';
$string['mod_form_field_openingtime'] = 'Open';
$string['mod_form_field_closingtime'] = 'Close';
$string['mod_form_field_intro'] = 'Description';
$string['mod_form_field_intro_help'] = 'A short description of the room.';
$string['mod_form_field_duration_help'] = 'Setting the duration for a meeting will establish the maximum time for a meeting to keep alive before the recording finish';
$string['mod_form_field_duration'] = 'Duration';
$string['mod_form_field_userlimit'] = 'User limit';
$string['mod_form_field_userlimit_help'] = 'The maximum number of users allowed in a session. Set to 0 to allow an unlimited number of users.';
$string['mod_form_field_name'] = 'Room name';
$string['mod_form_field_room_name'] = 'Room name';
$string['mod_form_field_conference_name'] = 'Session name';
$string['mod_form_field_record'] = 'The session may be recorded.';
$string['mod_form_field_voicebridge'] = 'Voice bridge [####]';
$string['mod_form_field_voicebridge_help'] = 'A number between 1 and 9999 that participants enter to join the voice session when using dial-in. If the value is 0 then the static voice bridge number will be ignored and a random number will be generated by BigBlueButton. A number 7 will prefix the four digits typed.';
$string['mod_form_field_voicebridge_format_error'] = 'Format error. You should input a number between 1 and 9999.';
$string['mod_form_field_voicebridge_notunique_error'] = 'Not a unique value. This number is being used by another room.';
$string['mod_form_field_wait'] = 'Wait for moderator';
$string['mod_form_field_wait_help'] = 'Do participants have to wait for a moderator before they can enter the room?';
$string['mod_form_field_welcome'] = 'Welcome message';
$string['mod_form_field_welcome_help'] = 'The welcome message is displayed when participants enter the room. If the field is left blank, then a default message set in the site administration is displayed.';
$string['mod_form_field_welcome_default'] = 'Welcome to %%CONFNAME%%.';
$string['mod_form_field_participant_add'] = 'Add assignee';
$string['mod_form_field_participant_list'] = 'Assignee';
$string['mod_form_field_participant_list_type_all'] = 'All users enrolled';
$string['mod_form_field_participant_list_type_role'] = 'Role';
$string['mod_form_field_participant_list_type_user'] = 'User';
$string['mod_form_field_participant_list_type_owner'] = 'Owner';
$string['mod_form_field_participant_list_text_as'] = 'joins session as';
$string['mod_form_field_participant_list_action_add'] = 'Add';
$string['mod_form_field_participant_list_action_remove'] = 'Remove';
$string['mod_form_field_participant_bbb_role_moderator'] = 'Moderator';
$string['mod_form_field_participant_bbb_role_viewer'] = 'Viewer';
$string['mod_form_field_instanceprofiles'] = 'Instance type';
$string['mod_form_field_instanceprofiles_help'] = 'If a session is to be recorded, select \'Room with recordings\', otherwise \'Room only\'. After a session is recorded, if there are to be no more sessions, select \'Recordings only\'.';
$string['mod_form_field_muteonstart'] = 'Mute on start';
$string['mod_form_field_notification'] = 'Notify this change to users enrolled';
$string['mod_form_field_notification_help'] = 'Send a notification to all users enrolled to let them know that this activity has been added or updated';
$string['mod_form_field_notification_created_help'] = 'Send a notification to all users enrolled to let them know that this activity has been created';
$string['mod_form_field_notification_modified_help'] = 'Send a notification to all users enrolled to let them know that this activity has been updated';
$string['mod_form_field_notification_msg_at'] = 'at';
$string['mod_form_field_recordings_html'] = 'Show the table in plain HTML';
$string['mod_form_field_recordings_imported'] = 'Show only imported links';
$string['mod_form_field_recordings_preview'] = 'Show recording preview';
$string['mod_form_field_recordallfromstart'] = 'Record all from start';
$string['mod_form_field_recordhidebutton'] = 'Hide recording button';
$string['mod_form_field_nosettings'] = 'No settings can be edited';
$string['mod_form_field_disablecam'] = 'Disable webcams';
$string['mod_form_field_disablemic'] = 'Disable microphones';
$string['mod_form_field_disableprivatechat'] = 'Disable private chat';
$string['mod_form_field_disablepublicchat'] = 'Disable public chat';
$string['mod_form_field_disablenote'] = 'Disable shared notes';
$string['mod_form_field_hideuserlist'] = 'Hide user list';
$string['mod_form_field_lockonjoin'] = 'Lock settings on join';
$string['mod_form_locksettings'] = 'Lock settings';
$string['report_join_info']  = 'Has joined the room {$a} time(s)';
$string['report_play_recording_info']  = 'Has played a recording {$a} time(s)';
$string['report_room_view']  = 'Has viewed the room';
$string['starts_at'] = 'Starts';
$string['started_at'] = 'Started';
$string['ends_at'] = 'Ends';
$string['calendarstarts'] = '{$a} is scheduled for';
$string['recordings_from_deleted_activities'] = 'Recordings from deleted activities';
$string['view_error_no_group_student'] = 'You have not been added to a group. Please contact your teacher.';
$string['view_error_no_group_teacher'] = 'There are no groups. You need to create some groups.';
$string['view_error_no_group'] = 'There are no groups. You need to create some groups before trying to join the session.';
$string['view_error_unable_join_student'] = 'Unable to connect to the BigBlueButton server.';
$string['view_error_unable_join_teacher'] = 'Unable to connect to the BigBlueButton server. Please contact an administrator.';
$string['view_error_unable_join'] = 'Unable to enter the room. Please check the URL of the BigBlueButton server AND check to see if the BigBlueButton server is running.';
$string['view_error_bigbluebutton'] = 'BigBlueButton responded with errors. {$a}';
$string['view_error_create'] = 'The BigBlueButton server responded with an error message. The room could not be created.';
$string['view_error_max_concurrent'] = 'The number of concurrent sessions allowed has been reached.';
$string['view_error_userlimit_reached'] = 'The number of users allowed in a session has been reached.';
$string['view_error_url_missing_parameters'] = 'There are parameters missing in this URL';
$string['view_error_import_no_courses'] = 'There are no courses to look up for recordings.';
$string['view_error_import_no_recordings'] = 'There are no recordings in this course for importing.';
$string['view_error_invalid_session'] = 'The session has expired. Go back to the activity page.';
$string['view_groups_selection_join'] = 'Join';
$string['view_groups_selection'] = 'Select the group you want to join and confirm the action';
$string['view_login_moderator'] = 'Logging in as moderator ...';
$string['view_login_viewer'] = 'Logging in as viewer ...';
$string['view_noguests'] = 'The BigBlueButton room is not open to guests.';
$string['view_nojoin'] = 'You do not have a role that is allowed to join this session.';
$string['view_recording_list_actionbar_edit'] = 'Edit';
$string['view_recording_list_actionbar_delete'] = 'Delete';
$string['view_recording_list_actionbar_import'] = 'Import';
$string['view_recording_list_actionbar_hide'] = 'Hide';
$string['view_recording_list_actionbar_show'] = 'Show';
$string['view_recording_list_actionbar_publish'] = 'Publish';
$string['view_recording_list_actionbar_unpublish'] = 'Unpublish';
$string['view_recording_list_actionbar_protect'] = 'Make it private';
$string['view_recording_list_actionbar_unprotect'] = 'Make it public';
$string['view_recording_list_action_publish'] = 'Publishing';
$string['view_recording_list_action_unpublish'] = 'Unpublishing';
$string['view_recording_list_action_process'] = 'Processing';
$string['view_recording_list_action_delete'] = 'Deleting';
$string['view_recording_list_action_protect'] = 'Protecting';
$string['view_recording_list_action_unprotect'] = 'Unprotecting';
$string['view_recording_list_action_update'] = 'Updating';
$string['view_recording_list_action_edit'] = 'Updating';
$string['view_recording_list_action_play'] = 'Play';
$string['view_recording_list_actionbar'] = 'Toolbar';
$string['view_recording_list_activity'] = 'Activity';
$string['view_recording_list_course'] = 'Course';
$string['view_recording_list_date'] = 'Date';
$string['view_recording_list_description'] = 'Description';
$string['view_recording_list_duration'] = 'Duration';
$string['view_recording_list_recording'] = 'Recording';
$string['view_recording_button_import'] = 'Import recording links';
$string['view_recording_button_return'] = 'Go back';
$string['view_recording_format_notes'] = 'Notes';
$string['view_recording_format_podcast'] = 'Podcast';
$string['view_recording_format_presentation'] = 'Presentation';
$string['view_recording_format_screenshare'] = 'Screenshare';
$string['view_recording_format_statistics'] = 'Statistics';
$string['view_recording_format_video'] = 'Video';
$string['view_recording_format_errror_unreachable'] = 'The URL for this recording format is unreachable.';
$string['view_section_title_presentation'] = 'Presentation file';
$string['view_section_title_recordings'] = 'Recordings';
$string['view_message_norecordings'] = 'There are no recordings available.';
$string['view_message_finished'] = 'This activity is over.';
$string['view_message_notavailableyet'] = 'This session is not yet available.';
$string['view_recording_select_course'] = 'Select a course first in the drop down menu';


$string['view_message_session_started_at'] = 'This session started at';
$string['view_message_session_running_for'] = 'This session has been running for';
$string['view_message_hour'] = 'hour';
$string['view_message_hours'] = 'hours';
$string['view_message_minute'] = 'minute';
$string['view_message_minutes'] = 'minutes';
$string['view_message_moderator'] = 'moderator';
$string['view_message_moderators'] = 'moderators';
$string['view_message_viewer'] = 'viewer';
$string['view_message_viewers'] = 'viewers';
$string['view_message_user'] = 'user';
$string['view_message_users'] = 'users';
$string['view_message_has_joined'] = 'has joined';
$string['view_message_have_joined'] = 'have joined';
$string['view_message_session_no_users'] = 'There are no users in this session';
$string['view_message_session_has_user'] = 'There is';
$string['view_message_session_has_users'] = 'There are';
$string['view_message_session_for'] = 'the session for';
$string['view_message_times'] = 'times';
$string['view_message_and'] = 'and';

$string['view_message_room_closed'] = 'This room is closed.';
$string['view_message_room_ready'] = 'This room is ready.';
$string['view_message_room_open'] = 'This room is open.';
$string['view_message_conference_room_ready'] = 'This room is ready. You can join the session now.';
$string['view_message_conference_not_started'] = 'The session has not started yet.';
$string['view_message_conference_wait_for_moderator'] = 'Waiting for a moderator to join.';
$string['view_message_conference_in_progress'] = 'The session is in progress.';
$string['view_message_conference_has_ended'] = 'The session has ended.';
$string['view_message_conference_user_limit_reached'] = 'The number of users allowed in a session has been reached';
$string['view_message_tab_close'] = 'This tab/window must be closed manually';
$string['view_message_recordings_disabled'] = 'Recordings are disabled on the server. BigBlueButton activities of type \'Recordings only\' cannot be used.';
$string['view_message_importrecordings_disabled'] = 'Import recording links is disabled on the server.';

$string['view_groups_selection_warning'] = 'There is a room for each group and you have access to more than one. Be sure to select the correct one.';
$string['view_groups_nogroups_warning'] = 'The room was configured for using groups but the course does not have groups defined.';
$string['view_groups_notenrolled_warning'] = 'The room was configured for using groups but you are not a member of a group.';
$string['view_conference_action_join'] = 'Join session';
$string['view_conference_action_end'] = 'End session';

$string['view_recording'] = 'recording';
$string['view_recording_link'] = 'imported link';
$string['view_recording_link_warning'] = 'This is a link pointing to a recording that was created in a different course or activity.';
$string['view_recording_delete_confirmation'] = 'Are you sure you want to delete {$a}?';
$string['view_recording_delete_confirmation_warning_s'] = 'This recording has {$a} link associated that was imported into a different course or activity. If the recording is deleted, this link will also be removed.';
$string['view_recording_delete_confirmation_warning_p'] = 'This recording has {$a} links associated that were imported into different courses or activities. If the recording is deleted, these links will also be removed.';
$string['view_recording_publish_confirmation'] = 'Are you sure you want to publish this {$a}?';
$string['view_recording_publish_confirmation_warning_s'] = 'This recording has {$a} link associated that was imported into a different course or activity. If the recording is published, this link will also be published.';
$string['view_recording_publish_confirmation_warning_p'] = 'This recording has {$a} links associated that were imported into different courses or activities. If the recording is published, these links will also be published.';
$string['view_recording_publish_link_deleted'] = 'This link cannot be republished because the original recording does not exist on the BigBlueButton server. The link should be removed.';
$string['view_recording_publish_link_not_published'] = 'This link cannot be republished because the original recording is unpublished.';
$string['view_recording_unpublish_confirmation'] = 'Are you sure to unpublish this {$a}?';
$string['view_recording_unpublish_confirmation_warning_s'] = 'This recording has {$a} link associated that was imported into a different course or activity. If the recording is unpublished, this link will also be unpublished.';
$string['view_recording_unpublish_confirmation_warning_p'] = 'This recording has {$a} links associated that were imported into different courses or activities. If the recording is unpublished, these links will also be unpublished.';
$string['view_recording_protect_confirmation'] = 'Are you sure you want to protect this {$a}?';
$string['view_recording_protect_confirmation_warning_s'] = 'This recording has {$a} link associated that was imported into a different course or activity. If the recording is protected it will also affect the imported links.';
$string['view_recording_protect_confirmation_warning_p'] = 'This recording has {$a} links associated that were imported into different courses or activities. If the recording is protected it will also affect the imported links.';
$string['view_recording_unprotect_confirmation'] = 'Are you sure you want to unprotect this {$a}?';
$string['view_recording_unprotect_confirmation_warning_s'] = 'This recording has {$a} link associated that was imported into a different course or activity. If the recording is unprotected it will also affect the imported links.';
$string['view_recording_unprotect_confirmation_warning_p'] = 'This recording has {$a} links associated that were imported into different courses or activities. If the recording is unprotected it will also affect the imported links.';
$string['view_recording_import_confirmation'] = 'Are you sure you want to import this recording?';
$string['view_recording_unprotect_link_deleted'] = 'This link cannot be unprotected because the original recording does not exist on the BigBlueButton server. The link should be removed.';
$string['view_recording_unprotect_link_not_unprotected'] = 'This link cannot be unprotected because the original recording is protected.';
$string['view_recording_actionbar'] = 'Toolbar';
$string['view_recording_activity'] = 'Activity';
$string['view_recording_course'] = 'Course';
$string['view_recording_date'] = 'Date';
$string['view_recording_description'] = 'Description';
$string['view_recording_description_editlabel'] = 'Edit description';
$string['view_recording_description_edithint'] = 'A description may be added to help identify the recording.';
$string['view_recording_length'] = 'Length';
$string['view_recording_meeting'] = 'Meeting';
$string['view_recording_duration'] = 'Duration';
$string['view_recording_recording'] = 'Recording';
$string['view_recording_duration_min'] = 'min';
$string['view_recording_name'] = 'Name';
$string['view_recording_name_editlabel'] = 'Edit name';
$string['view_recording_name_edithint'] = 'A name may be added to help identify the recording.';
$string['view_recording_tags'] = 'Tags';
$string['view_recording_playback'] = 'Playback';
$string['view_recording_preview'] = 'Preview';
$string['view_recording_preview_help'] = 'Hover over an image to view it in full size';
$string['view_recording_modal_button'] = 'Apply';
$string['view_recording_modal_title'] = 'Set values for recording';
$string['view_recording_yui_first'] = 'First';
$string['view_recording_yui_prev'] = 'Previous';
$string['view_recording_yui_next'] = 'Next';
$string['view_recording_yui_last'] = 'Last';
$string['view_recording_yui_page'] = 'Page';
$string['view_recording_yui_go'] = 'Go';
$string['view_recording_yui_rows'] = 'Rows';
$string['view_recording_yui_show_all'] = 'Show all';

$string['event_activity_created'] = 'Activity created';
$string['event_activity_deleted'] = 'Activity deleted';
$string['event_activity_updated'] = 'Activity updated';
$string['event_meeting_created'] = 'Meeting created';
$string['event_meeting_ended'] = 'Meeting forcibly ended';
$string['event_meeting_joined'] = 'Meeting joined';
$string['event_meeting_left'] = 'Meeting left';
$string['event_recording_viewed'] = 'Recording viewed';
$string['event_recording_edited'] = 'Recording edited';
$string['event_recording_deleted'] = 'Recording deleted';
$string['event_recording_imported'] = 'Recording imported';
$string['event_recording_published'] = 'Recording published';
$string['event_recording_unpublished'] = 'Recording unpublished';
$string['event_recording_protected'] = 'Recording protected';
$string['event_recording_unprotected'] = 'Recording unprotected';
$string['event_live_session'] = 'Live session event';
$string['event_unknown'] = 'Unknown event';

$string['instance_type_default'] = 'Room with recordings';
$string['instance_type_room_only'] = 'Room only';
$string['instance_type_recording_only'] = 'Recordings only';

$string['messageprovider:instance_updated'] = 'BigBlueButton session updated';
$string['messageprovider:recording_ready'] = 'BigBlueButton recording available';
$string['new_bigblubuttonbn_activities'] = 'BigBlueButton activity';
$string['notification_instance_created_intro'] = 'The <a href="{$a->link}">{$a->name}</a> BigBlueButton room has been created.';
$string['notification_instance_created_small'] = 'A new BigBlueButton room named {$a->name} was created.';
$string['notification_instance_created_subject'] = 'A new BigBlueButton room has been created';
$string['notification_instance_description'] = 'Description';
$string['notification_instance_end_date'] = 'End date';
$string['notification_instance_name'] = 'Title';
$string['notification_instance_start_date'] = 'Start date';
$string['notification_instance_updated_intro'] = 'The <a href="{$a->link}">{$a->name}</a> BigBlueButton room has been updated.';
$string['notification_instance_updated_small'] = 'The {$a->name} BigBlueButton session was updated';
$string['notification_instance_updated_subject'] = 'Your BigBlueButton room has been updated';
$string['notification_recording_ready_small'] = 'A recording is available for the BigBlueButton room {$a->name}.';
$string['notification_recording_ready_html'] = 'A recording is now available for the session in the BigBlueButton room <a href="{$a->link}">{$a->name}</a>.';
$string['notification_recording_ready_plain'] = 'A recording is now available for the session in the BigBlueButton room {$a->name}. Go to {$a->link} to access the recording link.';
$string['notification_recording_ready_subject'] = 'Recording available';

$string['view_error_meeting_not_running'] = 'Something went wrong; the session is not running.';
$string['view_error_current_state_not_found'] = 'Current state was not found. The recording may have been deleted or the BigBlueButton server is not compatible with the action performed.';
$string['view_error_action_not_completed'] = 'Action could not be completed';
$string['view_warning_default_server'] = 'This site is using a <a href="https://bigbluebutton.org/free-bigbluebutton-service-for-moodle/" target="_blank">free BigBlueButton service for Moodle (opens in new window)</a> provided by Blindside Networks with restrictions as follows:
<ol>
<li>The maximum length for each session is 60 minutes</li>
<li>The maximum number of concurrent users per session is 25</li>
<li>Recordings expire after seven (7) days and are not downloadable</li>
<li>Student webcams are only visible to the moderator.</li>
</ol>';

$string['view_room'] = 'View room';
$string['index_error_noinstances'] = 'There are no instances of BigBlueButton rooms';
$string['index_error_bbtn'] = 'BigBlueButton ID {$a} is incorrect';

$string['view_mobile_message_reload_page_creation_time_meeting'] = 'You exceeded 45 seconds on this page. Please refresh the page to join the session.';
$string['view_mobile_message_groups_not_supported'] = 'This instance is enabled to work with groups but the mobile app doesn\'t yet support it. Please use the web version.';

$string['end_session_confirm_title'] = 'Really end session?';
$string['end_session_confirm'] = 'Are you sure you want to end the session?';
$string['end_session_notification'] = 'The session has ended.';
$string['cachedef_currentfetch'] = 'Data to list any recording fetched recently.';
$string['cachedef_serverinfo'] = 'Remote server information';
$string['cachedef_recordings'] = 'Recording metadata';
$string['cachedef_validatedurls'] = 'Cache of validated URL checks';
$string['taskname:check_pending_recordings'] = 'Fetch pending recordings';
$string['taskname:check_dismissed_recordings'] = 'Check for dismissed recordings';
$string['userlimitreached'] = 'The number of users allowed in a session has been reached.';
$string['waitformoderator'] = 'Waiting for a moderator to join.';

$string['recordingurlnotfound'] = 'The recording URL is invalid.';

// Deprecated strings still needed for older BBB versions using 3.11 language packs.
$string['mod_form_field_notification_msg_created'] = 'added';
$string['mod_form_field_notification_msg_modified'] = 'updated';
$string['email_body_notification_meeting_has_been'] = 'has been';
$string['email_body_notification_meeting_details'] = 'Details';
$string['email_body_notification_meeting_title'] = 'Title';
$string['email_body_notification_meeting_description'] = 'Description';
$string['email_body_notification_meeting_start_date'] = 'Start date';
$string['email_body_notification_meeting_end_date'] = 'End date';
$string['email_body_notification_meeting_by'] = 'by';
$string['email_body_recording_ready_for'] = 'There is a recording ready for';
$string['email_body_recording_ready_in_course'] = 'in the course';
$string['email_footer_sent_by'] = 'This automatic notification message was sent by';
$string['email_footer_sent_from'] = 'from the course';
