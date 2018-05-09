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
 * @copyright 2010-2018 Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 */
defined('MOODLE_INTERNAL') || die();

$string['activityoverview'] = 'You have upcoming bigbluebuttonbn sessions';
$string['bbbduetimeoverstartingtime'] = 'The due time for this activity must be greater than the starting time';
$string['bbbdurationwarning'] = 'The maximum duration for this session is %duration% minutes.';
$string['bbbrecordwarning'] = 'This session may be recorded.';
$string['bigbluebuttonbn:addinstance'] = 'Add a new bigbluebuttonbn room/activity';
$string['bigbluebuttonbn:join'] = 'Join a bigbluebuttonbn meeting';
$string['bigbluebuttonbn:managerecordings'] = 'Manage bigbluebuttonbn recordings';
$string['bigbluebuttonbn'] = 'BigBlueButton';

$string['privacy:metadata:bigbluebuttonbn'] = 'Stores the configuration for the room or activity that defines the features and general behaviour of the BigBlueButton session.';
$string['privacy:metadata:bigbluebuttonbn:participants'] = 'A list of rules that define the role users will in the live meeting. A user ID may be stored as permissions can be granted per role or user.';
$string['privacy:metadata:bigbluebuttonbn_logs'] = 'Stores events triggered when using the plugin.';
$string['privacy:metadata:bigbluebuttonbn_logs:userid'] = 'The user ID of the user who triggered the event.';
$string['privacy:metadata:bigbluebuttonbn_logs:timecreated'] = 'The time at which the log was created.';
$string['privacy:metadata:bigbluebuttonbn_logs:meetingid'] = 'The meeting ID the user had access to.';
$string['privacy:metadata:bigbluebuttonbn_logs:log'] = 'The type of event triggered by the user.';
$string['privacy:metadata:bigbluebuttonbn_logs:meta'] = 'May include extra information related to the meeting or the recording afected by the event.';
$string['privacy:metadata:bigbluebutton'] = 'In order to create and join BigBlueButton sessions, user data needs to be exchanged with the server.';
$string['privacy:metadata:bigbluebutton:userid'] = 'The userid of the user accessing the BigBlueButton server.';
$string['privacy:metadata:bigbluebutton:fullname'] = 'The fullname of the user accessing the BigBlueButton server.';

$string['config_general'] = 'General configuration';
$string['config_general_description'] = 'These settings are <b>always</b> used';
$string['config_server_url'] = 'BigBlueButton Server URL';
$string['config_server_url_description'] = 'The URL of your BigBlueButton server must end with /bigbluebutton/. (This default URL is for a BigBlueButton server provided by Blindside Networks that you can use for testing.)';
$string['config_shared_secret'] = 'BigBlueButton Shared Secret';
$string['config_shared_secret_description'] = 'The security salt of your BigBlueButton server.  (This default salt is for a BigBlueButton server provided by Blindside Networks that you can use for testing.)';

$string['config_recording'] = 'Configuration for "Record meeting" feature';
$string['config_recording_description'] = 'These settings are feature specific';
$string['config_recording_default'] = 'Recording feature enabled by default';
$string['config_recording_default_description'] = 'If enabled the sessions created in BigBlueButton will have recording capabilities.';
$string['config_recording_editable'] = 'Recording feature can be edited';
$string['config_recording_editable_description'] = 'If checked the interface includes an option for enable and disable the recording feature.';
$string['config_recording_icons_enabled'] = 'Icons for recording management';
$string['config_recording_icons_enabled_description'] = 'When enabled, the recording management panel shows icons for the publish/unpublish and delete actions.';

$string['config_recordings'] = 'Configuration for "Show recordings" feature';
$string['config_recordings_description'] = 'These settings are feature specific';
$string['config_recordings_general'] = 'Show recording settings';
$string['config_recordings_general_description'] = 'These settings are used only when showing recordings';
$string['config_recordings_html_default'] = 'UI as html is enabled by default';
$string['config_recordings_html_default_description'] = 'If enabled the recording table is shown in plain HTML by default.';
$string['config_recordings_html_editable'] = 'UI as html feature can be edited';
$string['config_recordings_html_editable_description'] = 'UI as html value by default can be edited when the instance is added or updated.';
$string['config_recordings_deleted_default'] = 'Include recordings from deleted activities enabled by default';
$string['config_recordings_deleted_default_description'] = 'If enabled the recording table will include the recordings belonging to deleted activities if there is any.';
$string['config_recordings_deleted_editable'] = 'Include recordings from deleted activities feature can be edited';
$string['config_recordings_deleted_editable_description'] = 'Include recordings from deleted activities by default can be edited when the instance is added or updated.';
$string['config_recordings_imported_default'] = 'Show only imported links enabled by default';
$string['config_recordings_imported_default_description'] = 'If enabled the recording table will include only the imported links to recordings.';
$string['config_recordings_imported_editable'] = 'Show only imported links feature can be edited';
$string['config_recordings_imported_editable_description'] = 'Show only imported links by default can be edited when the instance is added or updated.';
$string['config_recordings_preview_default'] = 'Preview is enabled by default';
$string['config_recordings_preview_default_description'] = 'If enabled the table includes a preview of the presentation.';
$string['config_recordings_preview_editable'] = 'Preview feature can be edited';
$string['config_recordings_preview_editable_description'] = 'Preview feature can be edited when the instance is added or updated.';


$string['config_importrecordings'] = 'Configuration for "Import recordings" feature';
$string['config_importrecordings_description'] = 'These settings are feature specific';
$string['config_importrecordings_enabled'] = 'Import recordings enabled';
$string['config_importrecordings_enabled_description'] = 'When this and the recording feature are enabled, it is possible to import recordings from different courses into an activity.';
$string['config_importrecordings_from_deleted_enabled'] = 'Import recordings from deleted activities enabled';
$string['config_importrecordings_from_deleted_enabled_description'] = 'When this and the import recording feature are enabled, it is possible to import recordings from activities that are no longer in the course.';

$string['config_waitformoderator'] = 'Configuration for "Wait for moderator" feature';
$string['config_waitformoderator_description'] = 'These settings are feature specific';
$string['config_waitformoderator_default'] = 'Wait for moderator enabled by default';
$string['config_waitformoderator_default_description'] = 'Wait for moderator feature is enabled by default when a new room or conference is added.';
$string['config_waitformoderator_editable'] = 'Wait for moderator feature can be edited';
$string['config_waitformoderator_editable_description'] = 'Wait for moderator value by default can be edited when the room or conference is added or updated.';
$string['config_waitformoderator_ping_interval'] = 'Wait for moderator ping (seconds)';
$string['config_waitformoderator_ping_interval_description'] = 'When the wait for moderator feature is enabled, the client pings for the status of the session each [number] seconds. This parameter defines the interval for requests made to the Moodle server';
$string['config_waitformoderator_cache_ttl'] = 'Wait for moderator cache TTL (seconds)';
$string['config_waitformoderator_cache_ttl_description'] = 'To support a heavy load of clients this plugin makes use of a cache. This parameter defines the time the cache will be kept before the next request is sent to the BigBlueButton server.';

$string['config_voicebridge'] = 'Configuration for "Voice bridge" feature';
$string['config_voicebridge_description'] = 'These settings enable or disable options in the UI and also define default values for these options.';
$string['config_voicebridge_editable'] = 'Conference voice bridge can be edited';
$string['config_voicebridge_editable_description'] = 'Conference voice bridge number can be permanently assigned to a room conference. When assigned, the number can not be used by any other room or conference';

$string['config_preuploadpresentation'] = 'Configuration for "Pre-upload presentation" feature';
$string['config_preuploadpresentation_description'] = 'These settings enable or disable options in the UI and also define default values for these options. The feature works only if the Moodle server is accessible to BigBlueButton..';
$string['config_preuploadpresentation_enabled'] = 'Pre-uploading presentation enabled';
$string['config_preuploadpresentation_enabled_description'] = 'Preupload presentation feature is enabled in the UI when the room or conference is added or updated.';

$string['config_participant'] = 'Participant configuration';
$string['config_participant_description'] = 'These settings define the role by default for participants in a conference.';
$string['config_participant_moderator_default'] = 'Moderator by default';
$string['config_participant_moderator_default_description'] = 'This rule is used by default when a new room is added.';

$string['config_userlimit'] = 'Configuration for "User limit" feature';
$string['config_userlimit_description'] = 'These settings enable or disable options in the UI and also define default values for these options.';
$string['config_userlimit_default'] = 'User limit enabled by default';
$string['config_userlimit_default_description'] = 'The number of users allowed in a session by default when a new room or conference is added. If the number is set to 0, no limit is established';
$string['config_userlimit_editable'] = 'User limit feature can be edited';
$string['config_userlimit_editable_description'] = 'User limit value by default can be edited when the room or conference is added or updated.';

$string['config_scheduled'] = 'Configuration for "Scheduled sessions"';
$string['config_scheduled_description'] = 'These settings define some of the behaviour by default for scheduled sessions.';
$string['config_scheduled_duration_enabled'] = 'Calculate duration enabled';
$string['config_scheduled_duration_enabled_description'] = 'The duration of an scheduled session is calculated based on the opening and closing times.';
$string['config_scheduled_duration_compensation'] = 'Compensatory time (minutes)';
$string['config_scheduled_duration_compensation_description'] = 'Minutes added to the scheduled closing when calculating the duration.';
$string['config_scheduled_pre_opening'] = 'Accessible before opening time (minutes)';
$string['config_scheduled_pre_opening_description'] = 'The time in minutes for the session to be acceessible before the schedules opening time is due.';

$string['config_sendnotifications'] = 'Configuration for "Send notifications" feature';
$string['config_sendnotifications_description'] = 'These settings enable or disable options in the UI and also define default values for these options.';
$string['config_sendnotifications_enabled'] = 'Send notifications enabled';
$string['config_sendnotifications_enabled_description'] = 'Send notifications feature is enabled in the UI when the room or conference is added or updated.';

$string['config_extended_capabilities'] = 'Configuration for extended capabilities';
$string['config_extended_capabilities_description'] = 'Configuration for extended capabilities when the BigBlueButton server offers them.';
$string['config_uidelegation_enabled'] = 'UI delegation is enabled';
$string['config_uidelegation_enabled_description'] = 'These settings enable or disable the UI delegation to the BigBlueButton server.';
$string['config_recordingready_enabled'] = 'Notifications when recording ready enabled';
$string['config_recordingready_enabled_description'] = 'Notifications when recording ready feature is enabled.';
$string['config_meetingevents_enabled'] = 'Register meeting events';
$string['config_meetingevents_enabled_description'] = 'Register meeting events feature is enabled.';

$string['config_warning_curl_not_installed'] = 'This feature requires the CURL extension for php installed and enabled. The settings will be accessible only if this condition is fulfilled.';
$string['config_warning_bigbluebuttonbn_cfg_deprecated'] = 'BigBlueButtonBN is making use of config.php with a global variable that has been deprecated. Please convert the file as it will not be supported in future versions';

$string['general_error_unable_connect'] = 'Unable to connect. Please check the url of the BigBlueButton server AND check to see if the BigBlueButton server is running.';

$string['index_confirm_end'] = 'Do you wish to end the virtual class?';
$string['index_disabled'] = 'disabled';
$string['index_enabled'] = 'enabled';
$string['index_ending'] = 'Ending the virtual classroom ... please wait';
$string['index_error_checksum'] = 'A checksum error occurred. Make sure you entered the correct salt.';
$string['index_error_forciblyended'] = 'Unable to join this meeting because it has been manually ended.';
$string['index_error_unable_display'] = 'Unable to display the meetings. Please check the url of the BigBlueButton server AND check to see if the BigBlueButton server is running.';
$string['index_heading_actions'] = 'Actions';
$string['index_heading_group'] = 'Group';
$string['index_heading_moderator'] = 'Moderators';
$string['index_heading_name'] = 'Room';
$string['index_heading_recording'] = 'Recording';
$string['index_heading_users'] = 'Users';
$string['index_heading_viewer'] = 'Viewers';
$string['index_heading'] = 'BigBlueButton Rooms';
$string['mod_form_block_general'] = 'General settings';
$string['mod_form_block_room'] = 'Activity/Room settings';
$string['mod_form_block_recordings'] = 'Recording settings';
$string['mod_form_block_presentation'] = 'Presentation content';
$string['mod_form_block_participants'] = 'Participants';
$string['mod_form_block_schedule'] = 'Schedule for session';
$string['mod_form_block_record'] = 'Record settings';
$string['mod_form_field_openingtime'] = 'Join open';
$string['mod_form_field_closingtime'] = 'Join closed';
$string['mod_form_field_intro'] = 'Description';
$string['mod_form_field_intro_help'] = 'A short description for the room or conference.';
$string['mod_form_field_duration_help'] = 'Setting the duration for a meeting will establish the maximum time for a meeting to keep alive before the recording finish';
$string['mod_form_field_duration'] = 'Duration';
$string['mod_form_field_userlimit'] = 'User limit';
$string['mod_form_field_userlimit_help'] = 'Maximum limit of users allowed in a meeting. If the limit is set to 0 the number of users will be unlimited.';
$string['mod_form_field_name'] = 'Virtual classroom name';
$string['mod_form_field_room_name'] = 'Room name';
$string['mod_form_field_conference_name'] = 'Conference name';
$string['mod_form_field_record'] = 'Session can be recorded';
$string['mod_form_field_voicebridge'] = 'Voice bridge [####]';
$string['mod_form_field_voicebridge_help'] = 'Voice conference number that participants enter to join the voice conference when using dial-in. A number between 1 and 9999 must be typed. If the value is 0 the static voicebridge number will be ignored and a random number will be generated by BigBlueButton. A number 7 will preced to the four digits typed';
$string['mod_form_field_voicebridge_format_error'] = 'Format error. You should input a number between 1 and 9999.';
$string['mod_form_field_voicebridge_notunique_error'] = 'Not a unique value. This number is being used by another room or conference.';
$string['mod_form_field_wait'] = 'Wait for moderator';
$string['mod_form_field_wait_help'] = 'Viewers must wait until a moderator enters the session before they can do so';
$string['mod_form_field_welcome'] = 'Welcome message';
$string['mod_form_field_welcome_help'] = 'Replaces the default message setted up for the BigBlueButton server. The message can includes keywords  (%%CONFNAME%%, %%DIALNUM%%, %%CONFNUM%%) which will be substituted automatically, and also html tags like <b>...</b> or <i></i> ';
$string['mod_form_field_welcome_default'] = '<br>Welcome to <b>%%CONFNAME%%</b>!<br><br>For help on using BigBlueButton see these (short)  <a href="event:http://www.bigbluebutton.org/content/videos"><u>tutorial videos</u></a>.<br><br>To join the audio bridge click the phone icon (top center). <b>Please use a headset to avoid causing background noise for others.</b>';
$string['mod_form_field_participant_add'] = 'Add participant';
$string['mod_form_field_participant_list'] = 'Participant list';
$string['mod_form_field_participant_list_type_all'] = 'All users enrolled';
$string['mod_form_field_participant_list_type_role'] = 'Role';
$string['mod_form_field_participant_list_type_user'] = 'User';
$string['mod_form_field_participant_list_type_owner'] = 'Owner';
$string['mod_form_field_participant_list_text_as'] = 'as';
$string['mod_form_field_participant_list_action_add'] = 'Add';
$string['mod_form_field_participant_list_action_remove'] = 'Remove';
$string['mod_form_field_participant_bbb_role_moderator'] = 'Moderator';
$string['mod_form_field_participant_bbb_role_viewer'] = 'Viewer';
$string['mod_form_field_instanceprofiles'] = 'Instance type';
$string['mod_form_field_instanceprofiles_help'] = 'Select the type for this BigBlueButtonBN instance.';
$string['mod_form_field_notification'] = 'Send notification';
$string['mod_form_field_notification_help'] = 'Send a notification to users enrolled to let them know that this activity has been created or modified';
$string['mod_form_field_notification_created_help'] = 'Send a notification to users enrolled to let them know that this activity has been created';
$string['mod_form_field_notification_modified_help'] = 'Send a notification to users enrolled to let them know that this activity has been modified';
$string['mod_form_field_notification_msg_created'] = 'created';
$string['mod_form_field_notification_msg_modified'] = 'modified';
$string['mod_form_field_notification_msg_at'] = 'at';
$string['mod_form_field_recordings_html'] = 'Show the table in plain html';
$string['mod_form_field_recordings_deleted'] = 'Include recordings from deleted activities';
$string['mod_form_field_recordings_imported'] = 'Show only imported links';
$string['mod_form_field_recordings_preview'] = 'Show recording preview';

$string['modulename'] = 'BigBlueButtonBN';
$string['modulenameplural'] = 'BigBlueButtonBN';
$string['modulename_help'] = 'BigBlueButtonBN lets you create from within Moodle links to real-time on-line classrooms using BigBlueButton, an open source web conferencing system for distance education.

Using BigBlueButtonBN you can specify for the title, description, calendar entry (which gives a date range for joining the session), groups, and details about the recording of the on-line session.

To view later recordings, add a RecordingsBN resource to this course.';
$string['modulename_link'] = 'BigBlueButtonBN/view';
$string['starts_at'] = 'Starts';
$string['started_at'] = 'Started';
$string['ends_at'] = 'Ends';
$string['pluginadministration'] = 'BigBlueButton administration';
$string['pluginname'] = 'BigBlueButtonBN';
$string['serverhost'] = 'Server Name';
$string['view_error_no_group_student'] = 'You have not been enrolled in a group. Please contact your Teacher or the Administrator.';
$string['view_error_no_group_teacher'] = 'There are no groups configured yet. Please set up groups or contact the Administrator.';
$string['view_error_no_group'] = 'There are no groups configured yet. Please set up groups before trying to join the meeting.';
$string['view_error_unable_join_student'] = 'Unable to connect to the BigBlueButton server. Please contact your Teacher or the Administrator.';
$string['view_error_unable_join_teacher'] = 'Unable to connect to the BigBlueButton server. Please contact the Administrator.';
$string['view_error_unable_join'] = 'Unable to join the meeting. Please check the url of the BigBlueButton server AND check to see if the BigBlueButton server is running.';
$string['view_error_bigbluebutton'] = 'BigBlueButton responded with errors. {$a}';
$string['view_error_create'] = 'The BigBlueButton server responded with an error message, the meeting could not be created.';
$string['view_error_max_concurrent'] = 'Number of concurrent meetings allowed has been reached.';
$string['view_error_userlimit_reached'] = 'The number of users allowed in a meeting has been reached.';
$string['view_error_url_missing_parameters'] = 'There are parameters missing in this URL';
$string['view_error_import_no_courses'] = 'No courses to look up for recordings';
$string['view_error_import_no_recordings'] = 'No recordings in this course for importing';
$string['view_error_invalid_session'] = 'The session is expired. Go back to the activity main page.';
$string['view_groups_selection_join'] = 'Join';
$string['view_groups_selection'] = 'Select the group you want to join and confirm the action';
$string['view_login_moderator'] = 'Logging in as moderator ...';
$string['view_login_viewer'] = 'Logging in as viewer ...';
$string['view_noguests'] = 'The BigBlueButtonBN is not open to guests';
$string['view_nojoin'] = 'You are not in a role allowed to join this session.';
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
$string['view_recording_format_presentation'] = 'presentation';
$string['view_recording_format_video'] = 'video';
$string['view_recording_format_statistics'] = 'statistics';
$string['view_section_title_presentation'] = 'Presentation file';
$string['view_section_title_recordings'] = 'Recordings';
$string['view_message_norecordings'] = 'There are no recording to show.';
$string['view_message_finished'] = 'This activity is over.';
$string['view_message_notavailableyet'] = 'This session is not yet available.';

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

$string['view_message_room_closed'] = 'This room is closed.';
$string['view_message_room_ready'] = 'This room is ready.';
$string['view_message_room_open'] = 'This room is open.';
$string['view_message_conference_room_ready'] = 'This conference room is ready. You can join the session now.';
$string['view_message_conference_not_started'] = 'This conference has not started yet.';
$string['view_message_conference_wait_for_moderator'] = 'Waiting for a moderator to join.';
$string['view_message_conference_in_progress'] = 'This conference is in progress.';
$string['view_message_conference_has_ended'] = 'This conference has ended.';
$string['view_message_tab_close'] = 'This tab/window must be closed manually';
$string['view_message_recordings_disabled'] = 'Recordings were disabled on this server. BigBlueButtonBN instances for recordings only can not be used.';
$string['view_message_importrecordings_disabled'] = 'Feature for import recording links is disabled on this server.';

$string['view_groups_selection_warning'] = 'There is a conference room for each group and you have access to more than one. Be sure to select the correct one.';
$string['view_groups_nogroups_warning'] = 'The room was configured for using groups but the course does not have goups defined.';
$string['view_groups_notenrolled_warning'] = 'The room was configured for using groups but you are not enrolled in any of them.';
$string['view_conference_action_join'] = 'Join session';
$string['view_conference_action_end'] = 'End session';

$string['view_recording'] = 'recording';
$string['view_recording_link'] = 'imported link';
$string['view_recording_link_warning'] = 'This is a link pointing to a recording that was created in a different course or activity';
$string['view_recording_delete_confirmation'] = 'Are you sure to delete this {$a}?';
$string['view_recording_delete_confirmation_warning_s'] = 'This recording has {$a} link associated that was imported in a different course or activity. If the recording is deleted that link will also be removed';
$string['view_recording_delete_confirmation_warning_p'] = 'This recording has {$a} links associated that were imported in different courses or activities. If the recording is deleted those links will also be removed';
$string['view_recording_publish_link_deleted'] = 'This link can not be re-published because the actual recording does not exist in the current BigBlueButton server. The link should be removed.';
$string['view_recording_publish_link_not_published'] = 'This link can not be re-published because the actual recording is unpublished';
$string['view_recording_unpublish_confirmation'] = 'Are you sure to unpublish this {$a}?';
$string['view_recording_unpublish_confirmation_warning_s'] = 'This recording has {$a} link associated that was imported in a different course or activity. If the recording is unpublished that link will also be unpublished';
$string['view_recording_unpublish_confirmation_warning_p'] = 'This recording has {$a} links associated that were imported in different courses or activities. If the recording is unpublished those links will also be unpublished';
$string['view_recording_import_confirmation'] = 'Are you sure to import this recording?';
$string['view_recording_unprotect_link_deleted'] = 'This link can not be un-protected because the actual recording does not exist in the current BigBlueButton server. The link should be removed.';
$string['view_recording_unprotect_link_not_unprotected'] = 'This link can not be un-protected because the actual recording is protected';
$string['view_recording_actionbar'] = 'Toolbar';
$string['view_recording_activity'] = 'Activity';
$string['view_recording_course'] = 'Course';
$string['view_recording_date'] = 'Date';
$string['view_recording_description'] = 'Description';
$string['view_recording_length'] = 'Length';
$string['view_recording_duration'] = 'Duration';
$string['view_recording_recording'] = 'Recording';
$string['view_recording_duration_min'] = 'min';
$string['view_recording_name'] = 'Name';
$string['view_recording_tags'] = 'Tags';
$string['view_recording_playback'] = 'Playback';
$string['view_recording_preview'] = 'Preview';
$string['view_recording_preview_help'] = 'Hover over an image to view it in full size';
$string['view_recording_modal_button'] = 'Apply';
$string['view_recording_modal_title'] = 'Set values for recording';

$string['event_activity_created'] = 'BigBlueButtonBN activity created';
$string['event_activity_deleted'] = 'BigBlueButtonBN activity deleted';
$string['event_activity_modified'] = 'BigBlueButtonBN activity modified';
$string['event_activity_viewed'] = 'BigBlueButtonBN activity viewed';
$string['event_activity_viewed_all'] = 'BigBlueButtonBN activity management viewed';
$string['event_meeting_created'] = 'BigBlueButtonBN meeting created';
$string['event_meeting_ended'] = 'BigBlueButtonBN meeting forcibly ended';
$string['event_meeting_joined'] = 'BigBlueButtonBN meeting joined';
$string['event_meeting_left'] = 'BigBlueButtonBN meeting left';
$string['event_recording_deleted'] = 'Recording deleted';
$string['event_recording_imported'] = 'Recording imported';
$string['event_recording_published'] = 'Recording published';
$string['event_recording_unpublished'] = 'Recording unpublished';

$string['instance_type_default'] = 'Room/Activity with recordings';
$string['instance_type_room_only'] = 'Room/Activity only';
$string['instance_type_recording_only'] = 'Recordings only';

$string['email_body_notification_meeting_has_been'] = 'has been';
$string['email_body_notification_meeting_details'] = 'Details';
$string['email_body_notification_meeting_title'] = 'Title';
$string['email_body_notification_meeting_description'] = 'Description';
$string['email_body_notification_meeting_start_date'] = 'Start date';
$string['email_body_notification_meeting_end_date'] = 'End date';
$string['email_body_notification_meeting_by'] = 'by';
$string['email_body_recording_ready_for'] = 'Recording for';
$string['email_body_recording_ready_is_ready'] = 'is ready';
$string['email_footer_sent_by'] = 'This automatic notification message was sent by';
$string['email_footer_sent_from'] = 'from the course';

$string['view_error_meeting_not_running'] = 'Something went wrong, the meeting is not running.';
$string['view_error_current_state_not_found'] = 'Current state was not found. The recording may have been deleted or the BigBlueButton server is not compatible with the action performed.';
$string['view_error_action_not_completed'] = 'Action could not be completed';
$string['view_warning_default_server'] = 'This Moodle server is making use of the BigBlueButton testing server that comes pre-configured by default. It should be replaced for production.';
