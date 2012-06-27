<?php
/**
 * Language File
 *
 * Authors:
 *    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 *    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 *    
 * Translation files available at 
 *     http://www.getlocalization.com/bigbluebutton_moodle2x
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2012 Blindside Networks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
defined('MOODLE_INTERNAL') || die();

$string = Array(
        'bbbduetimeoverstartingtime'=> "The due time for this activity must be greater than the starting time",
        'bbbdurationwarning'=> "The maximum duration for this session is %duration% minutes.",
        'bbbfinished'=> "This activity is over.",
        'bbbinprocess'=> "This activity is in process.",
        'bbbnorecordings'=> "There is no recording yet, please come back later.",
        'bbbnotavailableyet'=> "Sorry, this session is not yet available.",
        'bbbrecordwarning'=> "This session is being recorded.",
        'bbburl'=> "The URL of your BigBlueButton server must end with /bigbluebutton/. (This default URL is for a BigBlueButton server provided by Blindside Networks that you can use for testing.)",
        'bigbluebuttonbn:join'=> "Join a meeting",
        'bigbluebuttonbn:moderate'=> "Moderate a meeting",
        'bigbluebuttonbn'=> "BigBlueButton",
        'bigbluebuttonbnfieldset'=> "Custom example fieldset",
        'bigbluebuttonbnintro'=> "BigBlueButton Intro",
        'bigbluebuttonbnSalt'=> "Security Salt",
        'bigbluebuttonbnUrl'=> "BigBlueButton Server URL",
        'bigbluebuttonbnWait'=> "User has to wait",
        'configsecuritysalt'=> "The security salt of your BigBlueButton server.  (This default salt is for a BigBlueButton server provided by Blindside Networks that you can use for testing.)",
        'general_error_unable_connect'=> "Unable to connect. Please check the url of the BigBlueButton server AND check to see if the BigBlueButton server is running.",
        'index_confirm_end'=> "Do you wish to end the virtual class?",
        'index_disabled'=> "disabled",
        'index_enabled'=> "enabled",
        'index_ending'=> "Ending the virtual classroom ... please wait",
        'index_error_checksum'=> "A checksum error occured. Make sure you entered the correct salt.",
        'index_error_forciblyended'=> "Unable to join this meeting because it has been manualy ended.",
        'index_error_unable_display'=> "Unable to display the meetings. Please check the url of the BigBlueButton server AND check to see if the BigBlueButton server is running.",
        'index_heading_actions'=> "Actions",
        'index_heading_group'=> "Group",
        'index_heading_moderator'=> "Moderators",
        'index_heading_name'=> "Room",
        'index_heading_recording'=> "Recording",
        'index_heading_users'=> "Users",
        'index_heading_viewer'=> "Viewers",
        'index_heading'=> "BigBlueButton Rooms",
        'index_running'=> "running",
        'index_warning_adding_meeting'=> "Unable to assign a new meetingid.",
        'mod_form_block_general'=> "General settings",
        'mod_form_block_record'=> "Record settings",
        'mod_form_block_schedule'=> "Schedule for sessions",
        'mod_form_field_availabledate'=> "Join open",
        'mod_form_field_description'=> "Description of recorded session",
        'mod_form_field_duedate'=> "Join closed",
        'mod_form_field_duration_help'=> "Setting the duration for a meeting will establish the maximum time for a meeting to keep alive before the recording finish",
        'mod_form_field_duration'=> "Duration",
        'mod_form_field_limitusers'=> "Limit users",
        'mod_form_field_limitusers_help'=> "Maximum limit of users allowed in a meeting",
        'mod_form_field_name'=> "Virtual classroom name",
        'mod_form_field_newwindow'=> "Open BigBlueButton in a new window",
        'mod_form_field_record'=> "Record",
        'mod_form_field_voicebridge_help'=> "Voice conference number that participants enter to join the voice conference.",
        'mod_form_field_voicebridge'=> "Voice bridge",
        'mod_form_field_wait'=> "Students must wait until a moderator joins",
        'mod_form_field_welcome_default'=> "<br>Welcome to <b>%%CONFNAME%%</b>!<br><br>To understand how BigBlueButton works see our <a href=\"event:http://www.bigbluebutton.org/content/videos\"><u>tutorial videos</u></a>.<br><br>To join the audio bridge click the headset icon (upper-left hand corner). <b>Please use a headset to avoid causing echo for others.</b>",
        'mod_form_field_welcome_help'=> "Replaces the default message setted up for the BigBlueButton server. The message can includes keywords  (%%CONFNAME%%, %%DIALNUM%%, %%CONFNUM%%) which will be substituted automatically, and also html tags like <b>...</b> or <i></i> ",
        'mod_form_field_welcome'=> "Welcome message",
        'modulename'=> "BigBlueButtonBN",
        'modulenameplural'=> "BigBlueButtonBN",
        'pluginadministration'=> "BigBlueButton administration",
        'pluginname'=> "BigBlueButtonBN",
        'serverhost'=> "Server Name",
        'view_error_no_group_student'=> "You have not been erolled in a group. Please contact your Teacher or the Administrator.",
        'view_error_no_group_teacher'=> "There are no groups configured yet. Please set up groups or contact the Administrator.",
        'view_error_no_group'=> "There are no groups configured yet. Please set up groups before trying to join the meeting.",
        'view_error_unable_join_student'=> "Unable to connect to the BigBlueButton server. Please contact your Teacher or the Administrator.",
        'view_error_unable_join_teacher'=> "Unable to connect to the BigBlueButton server. Please contact the Administrator.",
        'view_error_unable_join'=> "Unable to join the meeting. Please check the url of the BigBlueButton server AND check to see if the BigBlueButton server is running.",
        'view_groups_selection_join'=> "Join",
        'view_groups_selection'=> "Select the group you want to join and confirm the action",
        'view_login_moderator'=> "Logging in as moderator ...",
        'view_login_viewer'=> "Logging in as viewer ...",
        'view_noguests'=> "The BigBlueButtonBN is not open to guests",
        'view_nojoin'=> "You are not in a role allowed to join this session.",
        'view_recording_list_actionbar_delete'=> "Delete",
        'view_recording_list_actionbar_hide'=> "Hide",
        'view_recording_list_actionbar_show'=> "Show",
        'view_recording_list_actionbar'=> "Toolbar",
        'view_recording_list_activity'=> "Activity",
        'view_recording_list_course'=> "Course",
        'view_recording_list_date'=> "Date",
        'view_recording_list_description'=> "Description",
        'view_recording_list_recording'=> "Recording",
        'view_wait'=> "The virtual class has not yet started.  Waiting until a moderator joins ...",
        );

?>