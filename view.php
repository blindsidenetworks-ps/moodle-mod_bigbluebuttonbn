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
 * View a BigBlueButton room.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2017 Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = required_param('id', PARAM_INT);
$bn = optional_param('n', 0, PARAM_INT);
$group = optional_param('group', 0, PARAM_INT);

$viewinstance = bigbluebuttonbn_views_validator($id, $bn);
if (!$viewinstance) {
    print_error(get_string('view_error_url_missing_parameters', 'bigbluebuttonbn'));
}

$cm = $viewinstance['cm'];
$course = $viewinstance['course'];
$bigbluebuttonbn = $viewinstance['bigbluebuttonbn'];

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_ACTIVITY_VIEWED, $bigbluebuttonbn, $cm);

// Additional info related to the course.
$bbbsession['course'] = $course;
$bbbsession['coursename'] = $course->fullname;
$bbbsession['cm'] = $cm;
// Hot-fix: Only for v2017101004, to be removed in the next release if db upgrade is added.
bigbluebuttonbn_verify_passwords($bigbluebuttonbn);
$bbbsession['bigbluebuttonbn'] = $bigbluebuttonbn;
bigbluebuttonbn_view_bbbsession_set($context, $bbbsession);

// Validates if the BigBlueButton server is working.
$serverversion = bigbluebuttonbn_get_server_version();
if (is_null($serverversion)) {
    if ($bbbsession['administrator']) {
        print_error('view_error_unable_join', 'bigbluebuttonbn',
            $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn');
        exit;
    }
    if ($bbbsession['moderator']) {
        print_error('view_error_unable_join_teacher', 'bigbluebuttonbn',
            $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course);
        exit;
    }
    print_error('view_error_unable_join_student', 'bigbluebuttonbn',
        $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course);
    exit;
}
$bbbsession['serverversion'] = (string) $serverversion;

// Mark viewed by user (if required).
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Print the page header.
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot.'/mod/bigbluebuttonbn/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($bigbluebuttonbn->name));
$PAGE->set_cacheable(false);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

// Validate if the user is in a role allowed to join.
if (!has_capability('moodle/category:manage', $context) && !has_capability('mod/bigbluebuttonbn:join', $context)) {
    echo $OUTPUT->header();
    if (isguestuser()) {
        echo $OUTPUT->confirm('<p>'.get_string('view_noguests', 'bigbluebuttonbn').'</p>'.get_string('liketologin'),
            get_login_url(), $CFG->wwwroot.'/course/view.php?id='.$course->id);
    } else {
        echo $OUTPUT->confirm('<p>'.get_string('view_nojoin', 'bigbluebuttonbn').'</p>'.get_string('liketologin'),
            get_login_url(), $CFG->wwwroot.'/course/view.php?id='.$course->id);
    }
    echo $OUTPUT->footer();
    exit;
}

// Operation URLs.
$bbbsession['bigbluebuttonbnURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/view.php?id=' . $bbbsession['cm']->id;
$bbbsession['logoutURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_view.php?action=logout&id='.$id .
    '&bn=' . $bbbsession['bigbluebuttonbn']->id;
$bbbsession['recordingReadyURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_broker.php?action=recording_' .
    'ready&bigbluebuttonbn=' . $bbbsession['bigbluebuttonbn']->id;
$bbbsession['meetingEventsURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_broker.php?action=meeting' .
    '_events&bigbluebuttonbn=' . $bbbsession['bigbluebuttonbn']->id;
$bbbsession['joinURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_view.php?action=join&id=' . $id .
    '&bn=' . $bbbsession['bigbluebuttonbn']->id;

// Output starts.
echo $OUTPUT->header();

bigbluebuttonbn_view_groups($bbbsession);

bigbluebuttonbn_view_render($bbbsession, bigbluebuttonbn_view_get_activity_status($bbbsession));

// Output finishes.
echo $OUTPUT->footer();

// Shows version as a comment.
echo '<!-- '.$bbbsession['originTag'].' -->'."\n";

// Initialize session variable used across views.
$SESSION->bigbluebuttonbn_bbbsession = $bbbsession;

/**
 * Setup the bbbsession variable that is used all accross the plugin.
 *
 * @param object $context
 * @param array $bbbsession
 * @return void
 */
function bigbluebuttonbn_view_bbbsession_set($context, &$bbbsession) {
    global $CFG, $USER;
    // User data.
    $bbbsession['username'] = fullname($USER);
    $bbbsession['userID'] = $USER->id;
    // User roles.
    $bbbsession['administrator'] = is_siteadmin($bbbsession['userID']);
    $participantlist = bigbluebuttonbn_get_participant_list($bbbsession['bigbluebuttonbn'], $context);
    $bbbsession['moderator'] = bigbluebuttonbn_is_moderator($context, $participantlist);
    $bbbsession['managerecordings'] = ($bbbsession['administrator']
        || has_capability('mod/bigbluebuttonbn:managerecordings', $context));
    $bbbsession['importrecordings'] = ($bbbsession['managerecordings']);
    // Server data.
    $bbbsession['modPW'] = $bbbsession['bigbluebuttonbn']->moderatorpass;
    $bbbsession['viewerPW'] = $bbbsession['bigbluebuttonbn']->viewerpass;
    // Database info related to the activity.
    $bbbsession['meetingid'] = $bbbsession['bigbluebuttonbn']->meetingid.'-'.$bbbsession['course']->id.'-'.
        $bbbsession['bigbluebuttonbn']->id;
    $bbbsession['meetingname'] = $bbbsession['bigbluebuttonbn']->name;
    $bbbsession['meetingdescription'] = $bbbsession['bigbluebuttonbn']->intro;
    // Extra data for setting up the Meeting.
    $bbbsession['userlimit'] = intval((int)\mod_bigbluebuttonbn\locallib\config::get('userlimit_default'));
    if ((boolean)\mod_bigbluebuttonbn\locallib\config::get('userlimit_editable')) {
        $bbbsession['userlimit'] = intval($bbbsession['bigbluebuttonbn']->userlimit);
    }
    $bbbsession['voicebridge'] = $bbbsession['bigbluebuttonbn']->voicebridge;
    if ($bbbsession['bigbluebuttonbn']->voicebridge > 0) {
        $bbbsession['voicebridge'] = 70000 + $bbbsession['bigbluebuttonbn']->voicebridge;
    }
    $bbbsession['wait'] = $bbbsession['bigbluebuttonbn']->wait;
    $bbbsession['record'] = $bbbsession['bigbluebuttonbn']->record;
    $bbbsession['welcome'] = $bbbsession['bigbluebuttonbn']->welcome;
    if (!isset($bbbsession['welcome']) || $bbbsession['welcome'] == '') {
        $bbbsession['welcome'] = get_string('mod_form_field_welcome_default', 'bigbluebuttonbn');
    }
    if ($bbbsession['bigbluebuttonbn']->record) {
        $bbbsession['welcome'] .= '<br><br>'.get_string('bbbrecordwarning', 'bigbluebuttonbn');
    }
    $bbbsession['openingtime'] = $bbbsession['bigbluebuttonbn']->openingtime;
    $bbbsession['closingtime'] = $bbbsession['bigbluebuttonbn']->closingtime;
    // Additional info related to the course.
    $bbbsession['context'] = $context;
    // Metadata (origin).
    $bbbsession['origin'] = 'Moodle';
    $bbbsession['originVersion'] = $CFG->release;
    $parsedurl = parse_url($CFG->wwwroot);
    $bbbsession['originServerName'] = $parsedurl['host'];
    $bbbsession['originServerUrl'] = $CFG->wwwroot;
    $bbbsession['originServerCommonName'] = '';
    $bbbsession['originTag'] = 'moodle-mod_bigbluebuttonbn ('.get_config('mod_bigbluebuttonbn', 'version').')';
    $bbbsession['bnserver'] = bigbluebuttonbn_is_bn_server();
    // Setting for clienttype, assign flash if not enabled, or default if not editable.
    $bbbsession['clienttype'] = \mod_bigbluebuttonbn\locallib\config::get('clienttype_default');
    if (\mod_bigbluebuttonbn\locallib\config::get('clienttype_editable')) {
        $bbbsession['clienttype'] = $bbbsession['bigbluebuttonbn']->clienttype;
    }
    if (!\mod_bigbluebuttonbn\locallib\config::clienttype_enabled()) {
        $bbbsession['clienttype'] = BIGBLUEBUTTON_CLIENTTYPE_FLASH;
    }
}

/**
 * Return the status of an activity [open|not_started|ended].
 *
 * @param array $bbbsession
 * @return string
 */
function bigbluebuttonbn_view_get_activity_status(&$bbbsession) {
    $now = time();
    if (!empty($bbbsession['bigbluebuttonbn']->openingtime) && $now < $bbbsession['bigbluebuttonbn']->openingtime) {
        // The activity has not been opened.
        return 'not_started';
    }
    if (!empty($bbbsession['bigbluebuttonbn']->closingtime) && $now > $bbbsession['bigbluebuttonbn']->closingtime) {
        // The activity has been closed.
        $bbbsession['presentation'] = bigbluebuttonbn_get_presentation_array(
            $bbbsession['context'], $bbbsession['bigbluebuttonbn']->presentation);
        return 'ended';
    }
    // The activity is open.
    $bbbsession['presentation'] = bigbluebuttonbn_get_presentation_array(
        $bbbsession['context'], $bbbsession['bigbluebuttonbn']->presentation, $bbbsession['bigbluebuttonbn']->id);
    return 'open';
}

/**
 * Displays the view for groups.
 *
 * @param array $bbbsession
 * @return void
 */
function bigbluebuttonbn_view_groups(&$bbbsession) {
    global $CFG;
    // Find out current group mode.
    $groupmode = groups_get_activity_groupmode($bbbsession['cm']);
    if ($groupmode == NOGROUPS) {
        // No groups mode.
        return;
    }
    // Separate or visible group mode.
    $groups = groups_get_activity_allowed_groups($bbbsession['cm']);
    if (empty($groups)) {
        // No groups in this course.
        bigbluebuttonbn_view_message_box($bbbsession, get_string('view_groups_nogroups_warning', 'bigbluebuttonbn'), 'info', true);
        return;
    }
    $bbbsession['group'] = groups_get_activity_group($bbbsession['cm'], true);
    $groupname = get_string('allparticipants');
    if ($bbbsession['group'] != 0) {
        $groupname = groups_get_group_name($bbbsession['group']);
    }
    // Assign group default values.
    $bbbsession['meetingid'] .= '['.$bbbsession['group'].']';
    $bbbsession['meetingname'] .= ' ('.$groupname.')';
    if (count($groups) == 0) {
        // Only the All participants group exists.
        bigbluebuttonbn_view_message_box($bbbsession, get_string('view_groups_notenrolled_warning', 'bigbluebuttonbn'), 'info');
        return;
    }
    $context = context_module::instance($bbbsession['cm']->id);
    if (has_capability('moodle/site:accessallgroups', $context)) {
        bigbluebuttonbn_view_message_box($bbbsession, get_string('view_groups_selection_warning', 'bigbluebuttonbn'));
    }
    $urltoroot = $CFG->wwwroot.'/mod/bigbluebuttonbn/view.php?id='.$bbbsession['cm']->id;
    groups_print_activity_menu($bbbsession['cm'], $urltoroot);
    echo '<br><br>';
}

/**
 * Displays the view for messages.
 *
 * @param array $bbbsession
 * @param string $message
 * @param string $type
 * @param boolean $onlymoderator
 * @return void
 */
function bigbluebuttonbn_view_message_box(&$bbbsession, $message, $type = 'warning', $onlymoderator = false) {
    global $OUTPUT;
    if ($onlymoderator && !$bbbsession['moderator'] && !$bbbsession['administrator']) {
        return;
    }
    echo $OUTPUT->box_start('generalbox boxaligncenter');
    echo '<br><div class="alert alert-' . $type . '">' . $message . '</div>';
    echo $OUTPUT->box_end();
}

/**
 * Displays the general view.
 *
 * @param array $bbbsession
 * @param string $activity
 * @return void
 */
function bigbluebuttonbn_view_render(&$bbbsession, $activity) {
    global $OUTPUT, $PAGE;
    $type = null;
    if (isset($bbbsession['bigbluebuttonbn']->type)) {
        $type = $bbbsession['bigbluebuttonbn']->type;
    }
    $typeprofiles = bigbluebuttonbn_get_instance_type_profiles();
    $enabledfeatures = bigbluebuttonbn_get_enabled_features($typeprofiles, $type);
    $pinginterval = (int)\mod_bigbluebuttonbn\locallib\config::get('waitformoderator_ping_interval') * 1000;
    // JavaScript for locales.
    $PAGE->requires->strings_for_js(array_keys(bigbluebuttonbn_get_strings_for_js()), 'bigbluebuttonbn');
    // JavaScript variables.
    $jsvars = array('activity' => $activity, 'ping_interval' => $pinginterval,
        'locale' => bigbluebuttonbn_get_localcode(), 'profile_features' => $typeprofiles[0]['features']);
    $output  = '';
    // Renders warning messages when configured.
    $output .= bigbluebuttonbn_view_warning_default_server($bbbsession);
    $output .= bigbluebuttonbn_view_warning_general($bbbsession);

    // Renders the rest of the page.
    $output .= $OUTPUT->heading($bbbsession['meetingname'], 3);
    // Renders the completed description.
    $desc = file_rewrite_pluginfile_urls($bbbsession['meetingdescription'], 'pluginfile.php',
        $bbbsession['context']->id, 'mod_bigbluebuttonbn', 'intro', null);
    $output .= $OUTPUT->heading($desc, 5);

    if ($enabledfeatures['showroom']) {
        $output .= bigbluebuttonbn_view_render_room($bbbsession, $activity, $jsvars);
        $PAGE->requires->yui_module('moodle-mod_bigbluebuttonbn-rooms',
            'M.mod_bigbluebuttonbn.rooms.init', array($jsvars));
    }
    if ($enabledfeatures['showrecordings']) {
        $output .= html_writer::start_tag('div', array('id' => 'bigbluebuttonbn_view_recordings'));
        $output .= bigbluebuttonbn_view_render_recording_section($bbbsession, $type, $enabledfeatures, $jsvars);
        $output .= html_writer::end_tag('div');
        $PAGE->requires->yui_module('moodle-mod_bigbluebuttonbn-recordings',
                'M.mod_bigbluebuttonbn.recordings.init', array($jsvars));
    } else if ($type == BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY) {
        $recordingsdisabled = get_string('view_message_recordings_disabled', 'bigbluebuttonbn');
        $output .= bigbluebuttonbn_render_warning($recordingsdisabled, 'danger');
    }
    echo $output.html_writer::empty_tag('br').html_writer::empty_tag('br').html_writer::empty_tag('br');
    $PAGE->requires->yui_module('moodle-mod_bigbluebuttonbn-broker', 'M.mod_bigbluebuttonbn.broker.init', array($jsvars));
}

/**
 * Renders the view for recordings.
 *
 * @param array $bbbsession
 * @param integer $type
 * @param array $enabledfeatures
 * @param array $jsvars
 * @return string
 */
function bigbluebuttonbn_view_render_recording_section(&$bbbsession, $type, $enabledfeatures, &$jsvars) {
    if ($type == BIGBLUEBUTTONBN_TYPE_ROOM_ONLY) {
        return '';
    }
    $output = '';
    if ($type == BIGBLUEBUTTONBN_TYPE_ALL && $bbbsession['record']) {
        $output .= html_writer::start_tag('div', array('id' => 'bigbluebuttonbn_view_recordings_header'));
        $output .= html_writer::tag('h4', get_string('view_section_title_recordings', 'bigbluebuttonbn'));
        $output .= html_writer::end_tag('div');
    }
    if ($type == BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY || $bbbsession['record']) {
        $output .= html_writer::start_tag('div', array('id' => 'bigbluebuttonbn_view_recordings_content'));
        $output .= bigbluebuttonbn_view_render_recordings($bbbsession, $enabledfeatures, $jsvars);
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', array('id' => 'bigbluebuttonbn_view_recordings_footer'));
        $output .= bigbluebuttonbn_view_render_imported($bbbsession, $enabledfeatures);
        $output .= html_writer::end_tag('div');
    }
    return $output;
}

/**
 * Evaluates if the warning box should be shown.
 *
 * @param array $bbbsession
 *
 * @return boolean
 */
function bigbluebuttonbn_view_warning_shown($bbbsession) {
    if (is_siteadmin($bbbsession['userID'])) {
        return true;
    }
    $generalwarningroles = explode(',', \mod_bigbluebuttonbn\locallib\config::get('general_warning_roles'));
    $userroles = bigbluebuttonbn_get_user_roles($bbbsession['context'], $bbbsession['userID']);
    foreach ($userroles as $userrole) {
        if (in_array($userrole->shortname, $generalwarningroles)) {
            return true;
        }
    }
    return false;
}

/**
 * Renders the view for room.
 *
 * @param array $bbbsession
 * @param string $activity
 * @param array $jsvars
 *
 * @return string
 */
function bigbluebuttonbn_view_render_room(&$bbbsession, $activity, &$jsvars) {
    global $OUTPUT;
    // JavaScript variables for room.
    $openingtime = '';
    if ($bbbsession['openingtime']) {
        $openingtime = get_string('mod_form_field_openingtime', 'bigbluebuttonbn').': '.
            userdate($bbbsession['openingtime']);
    }
    $closingtime = '';
    if ($bbbsession['closingtime']) {
        $closingtime = get_string('mod_form_field_closingtime', 'bigbluebuttonbn').': '.
            userdate($bbbsession['closingtime']);
    }
    $jsvars += array(
        'meetingid' => $bbbsession['meetingid'],
        'bigbluebuttonbnid' => $bbbsession['bigbluebuttonbn']->id,
        'userlimit' => $bbbsession['userlimit'],
        'opening' => $openingtime,
        'closing' => $closingtime,
    );
    // Main box.
    $output  = $OUTPUT->box_start('generalbox boxaligncenter', 'bigbluebuttonbn_view_message_box');
    $output .= '<br><span id="status_bar"></span>';
    $output .= '<br><span id="control_panel"></span>';
    $output .= $OUTPUT->box_end();
    // Action button box.
    $output .= $OUTPUT->box_start('generalbox boxaligncenter', 'bigbluebuttonbn_view_action_button_box');
    $output .= '<br><br><span id="join_button"></span>&nbsp;<span id="end_button"></span>'."\n";
    $output .= $OUTPUT->box_end();
    if ($activity == 'ended') {
        $output .= bigbluebuttonbn_view_ended($bbbsession);
    }
    return $output;
}

/**
 * Renders the view for recordings.
 *
 * @param array $bbbsession
 * @param array $enabledfeatures
 * @param array $jsvars
 *
 * @return string
 */
function bigbluebuttonbn_view_render_recordings(&$bbbsession, $enabledfeatures, &$jsvars) {
    $bigbluebuttonbnid = null;
    if ($enabledfeatures['showroom']) {
        $bigbluebuttonbnid = $bbbsession['bigbluebuttonbn']->id;
    }
    // Get recordings.
    $recordings = bigbluebuttonbn_get_recordings(
        $bbbsession['course']->id, $bigbluebuttonbnid, $enabledfeatures['showroom'],
        $bbbsession['bigbluebuttonbn']->recordings_deleted
      );
    if ($enabledfeatures['importrecordings']) {
        // Get recording links.
        $recordingsimported = bigbluebuttonbn_get_recordings_imported_array(
            $bbbsession['course']->id, $bigbluebuttonbnid, $enabledfeatures['showroom']
          );
        /* Perform aritmetic addition instead of merge so the imported recordings corresponding to existent
         * recordings are not included. */
        if ($bbbsession['bigbluebuttonbn']->recordings_imported) {
            $recordings = $recordingsimported;
        } else {
            $recordings += $recordingsimported;
        }
    }
    if (empty($recordings) || array_key_exists('messageKey', $recordings)) {
        // There are no recordings to be shown.
        return html_writer::div(get_string('view_message_norecordings', 'bigbluebuttonbn'), '',
            array('id' => 'bigbluebuttonbn_recordings_table'));
    }
    // There are recordings for this meeting.
    // JavaScript variables for recordings.
    $jsvars += array(
            'recordings_html' => $bbbsession['bigbluebuttonbn']->recordings_html == '1',
          );
    // If there are meetings with recordings load the data to the table.
    if ($bbbsession['bigbluebuttonbn']->recordings_html) {
        // Render a plain html table.
        return bigbluebuttonbn_output_recording_table($bbbsession, $recordings)."\n";
    }
    // JavaScript variables for recordings with YUI.
    $jsvars += array(
            'columns' => bigbluebuttonbn_get_recording_columns($bbbsession),
            'data' => bigbluebuttonbn_get_recording_data($bbbsession, $recordings),
          );
    // Render a YUI table.
    return html_writer::div('', '', array('id' => 'bigbluebuttonbn_recordings_table'));
}

/**
 * Renders the view for importing recordings.
 *
 * @param array $bbbsession
 * @param array $enabledfeatures
 *
 * @return string
 */
function bigbluebuttonbn_view_render_imported($bbbsession, $enabledfeatures) {
    global $CFG;
    if (!$enabledfeatures['importrecordings'] || !$bbbsession['importrecordings']) {
        return '';
    }
    $button = html_writer::tag('input', '',
        array('type' => 'button',
              'value' => get_string('view_recording_button_import', 'bigbluebuttonbn'),
              'class' => 'btn btn-secondary',
              'onclick' => 'window.location=\''.$CFG->wwwroot.'/mod/bigbluebuttonbn/import_view.php?bn='.
                  $bbbsession['bigbluebuttonbn']->id.'\''));
    $output  = html_writer::empty_tag('br');
    $output .= html_writer::tag('span', $button, array('id' => 'import_recording_links_button'));
    $output .= html_writer::tag('span', '', array('id' => 'import_recording_links_table'));
    return $output;
}

/**
 * Renders the content for ended meeting.
 *
 * @param array $bbbsession
 *
 * @return string
 */
function bigbluebuttonbn_view_ended(&$bbbsession) {
    global $OUTPUT;
    if (!is_null($bbbsession['presentation']['url'])) {
        $attributes = array('title' => $bbbsession['presentation']['name']);
        $icon = new pix_icon($bbbsession['presentation']['icon'], $bbbsession['presentation']['mimetype_description']);
        return '<h4>'.get_string('view_section_title_presentation', 'bigbluebuttonbn').'</h4>'.
                $OUTPUT->action_icon($bbbsession['presentation']['url'], $icon, null, array(), false).
                $OUTPUT->action_link($bbbsession['presentation']['url'],
                $bbbsession['presentation']['name'], null, $attributes).'<br><br>';
    }
    return '';
}

// Hot-fix: Only for v2017101004, to be removed in the next release if db upgrade is added.
/**
 * Make sure the passwords have been setup.
 *
 * @param object $bigbluebuttonbn
 *
 * @return void
 */
function bigbluebuttonbn_verify_passwords(&$bigbluebuttonbn) {
    global $DB;
    if (empty($bigbluebuttonbn->moderatorpass) || empty($bigbluebuttonbn->viewerpass)) {
        $bigbluebuttonbn->moderatorpass = bigbluebuttonbn_random_password(12);
        $bigbluebuttonbn->viewerpass = bigbluebuttonbn_random_password(12, $bigbluebuttonbn->moderatorpass);
        // Store passwords in the database.
        $DB->update_record('bigbluebuttonbn', $bigbluebuttonbn);
    }
}

/**
 * Renders a default server warning message when using test-install.
 *
 * @param array $bbbsession
 *
 * @return string
 */
function bigbluebuttonbn_view_warning_default_server(&$bbbsession) {
    if (!is_siteadmin($bbbsession['userID'])) {
        return '';
    }
    if (BIGBLUEBUTTONBN_DEFAULT_SERVER_URL != \mod_bigbluebuttonbn\locallib\config::get('server_url')) {
        return '';
    }
    return bigbluebuttonbn_render_warning(get_string('view_warning_default_server', 'bigbluebuttonbn'), 'warning');
}

/**
 * Renders a general warning message when it is configured.
 *
 * @param array $bbbsession
 *
 * @return string
 */
function bigbluebuttonbn_view_warning_general(&$bbbsession) {
    if (!bigbluebuttonbn_view_warning_shown($bbbsession)) {
        return '';
    }
    return bigbluebuttonbn_render_warning(
        (string)\mod_bigbluebuttonbn\locallib\config::get('general_warning_message'),
        (string)\mod_bigbluebuttonbn\locallib\config::get('general_warning_box_type'),
        (string)\mod_bigbluebuttonbn\locallib\config::get('general_warning_button_href'),
        (string)\mod_bigbluebuttonbn\locallib\config::get('general_warning_button_text'),
        (string)\mod_bigbluebuttonbn\locallib\config::get('general_warning_button_class')
      );
}
