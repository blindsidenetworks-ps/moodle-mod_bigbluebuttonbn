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

function bigbluebuttonbn_view_bbbsession_set($context, &$bbbsession) {
    global $CFG, $USER;

    // User data.
    $bbbsession['username'] = fullname($USER);
    $bbbsession['userID'] = $USER->id;
    $bbbsession['roles'] = bigbluebuttonbn_view_bbbsession_roles($context, $USER->id);

    // User roles.
    $bbbsession['administrator'] = is_siteadmin($bbbsession['userID']);
    $participantlist = bigbluebuttonbn_get_participant_list($bbbsession['bigbluebuttonbn'], $context);
    $bbbsession['moderator'] = bigbluebuttonbn_is_moderator(
        $context, json_encode($participantlist), $bbbsession['userID'], $bbbsession['roles']);
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
}

function bigbluebuttonbn_view_bbbsession_roles($context, $userid) {
    if (isguestuser()) {
        return bigbluebuttonbn_get_guest_role();
    }
    return bigbluebuttonbn_get_user_roles($context, $userid);
}

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

/*
There are no groups,
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
    $groups = groups_get_all_groups($bbbsession['course']->id);
    if (empty($groups)) {
        // No groups in this course.
        bigbluebuttonbn_view_message_box($bbbsession, get_string('view_groups_nogroups_warning', 'bigbluebuttonbn'), 'info', true);
        return;
    }

    if ($groupmode == SEPARATEGROUPS) {
        $groups = groups_get_activity_allowed_groups($bbbsession['cm']);
    }

    $bbbsession['group'] = groups_get_activity_group($bbbsession['cm'], true);
    $groupname = get_string('allparticipants');
    if ($bbbsession['group'] != 0) {
        $groupname = groups_get_group_name($bbbsession['group']);
    }

    // Assign group default values.
    $bbbsession['meetingid'] = $bbbsession['bigbluebuttonbn']->meetingid.'-'.$bbbsession['course']->id.'-'.
        $bbbsession['bigbluebuttonbn']->id.'['.$bbbsession['group'].']';
    $bbbsession['meetingname'] = $bbbsession['bigbluebuttonbn']->name.' ('.$groupname.')';

    if (count($groups) == 0) {
        // Only the All participants group exists.
        bigbluebuttonbn_view_message_box($bbbsession,
            get_string('view_groups_notenrolled_warning', 'bigbluebuttonbn'), 'info', false);
        return;
    }

    if (count($groups) == 1) {
        // There is only one group and the user has access to it.
        return;
    }

    bigbluebuttonbn_view_message_box($bbbsession, get_string('view_groups_selection_warning', 'bigbluebuttonbn'), 'warning');
    $urltoroot = $CFG->wwwroot.'/mod/bigbluebuttonbn/view.php?id='.$bbbsession['cm']->id;
    groups_print_activity_menu($bbbsession['cm'], $urltoroot);
    echo '<br><br>';
}

function bigbluebuttonbn_view_message_box(&$bbbsession, $message, $type='warning', $onlymoderator=false) {
    global $OUTPUT;
    if ($onlymoderator && !$bbbsession['moderator'] && !$bbbsession['administrator']) {
        return;
    }
    echo $OUTPUT->box_start('generalbox boxaligncenter');
    echo '<br><div class="alert alert-' . $type . '">' . $message . '</div>';
    echo $OUTPUT->box_end();
}

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

    // Renders general warning when configured.
    $cfg = \mod_bigbluebuttonbn\locallib\config::get_options();
    $output  = bigbluebuttonbn_view_render_warning(
        (string)$cfg['general_warning_message'],
        (string)$cfg['general_warning_box_type'],
        (string)$cfg['general_warning_button_href'],
        (string)$cfg['general_warning_button_text'],
        (string)$cfg['general_warning_button_class']);

    $output .= $OUTPUT->heading($bbbsession['meetingname'], 3);
    $output .= $OUTPUT->heading($bbbsession['meetingdescription'], 5);

    if ($enabledfeatures['showroom']) {
        $output .= bigbluebuttonbn_view_render_room($bbbsession, $activity, $jsvars);
        $PAGE->requires->yui_module('moodle-mod_bigbluebuttonbn-rooms',
            'M.mod_bigbluebuttonbn.rooms.init', array($jsvars));
    }

    if ($enabledfeatures['showrecordings']) {
        $output .= bigbluebuttonbn_view_render_recording_section($bbbsession, $type, $enabledfeatures, $jsvars);
        $PAGE->requires->yui_module('moodle-mod_bigbluebuttonbn-recordings',
                'M.mod_bigbluebuttonbn.recordings.init', array($jsvars));
    }

    echo $output.html_writer::empty_tag('br').html_writer::empty_tag('br').html_writer::empty_tag('br');
    $PAGE->requires->yui_module('moodle-mod_bigbluebuttonbn-broker', 'M.mod_bigbluebuttonbn.broker.init', array($jsvars));
}

function bigbluebuttonbn_view_render_recording_section(&$bbbsession, $type, $enabledfeatures, &$jsvars) {
    $output = '';
    // Evaluates if the recordings are enterely disabled.
    if (!(boolean)\mod_bigbluebuttonbn\locallib\config::recordings_enabled()) {
        if ($type == BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY) {
            $output .= bigbluebuttonbn_view_render_warning(
                get_string('view_message_recordings_disabled', 'bigbluebuttonbn'), 'danger');
        }
        return $output;
    }
    // Recordings are rendered either if it is a 'recordings only' instance or it is set to be recorded.
    if ($type == BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY || $bbbsession['record']) {
        $output .= html_writer::tag('h4', get_string('view_section_title_recordings', 'bigbluebuttonbn'));
        $output .= bigbluebuttonbn_view_render_recordings($bbbsession, $enabledfeatures['showroom'], $jsvars);
        if ($enabledfeatures['importrecordings'] && $bbbsession['importrecordings']) {
            $output .= bigbluebuttonbn_view_render_imported($bbbsession);
        }
    }
    return $output;
}

function bigbluebuttonbn_view_render_warning($message, $type='info', $href='', $text='', $class='') {
    global $OUTPUT;
    $output = "\n";
    // Evaluates if config_warning is enabled.
    if (empty($message)) {
        return $output;
    }
    $output .= $OUTPUT->box_start('box boxalignleft adminerror alert alert-' . $type . ' alert-block fade in',
      'bigbluebuttonbn_view_general_warning')."\n";
    $output .= '  <button type="button" class="close" data-dismiss="alert">&times;</button>'.$message."\n";
    $output .= '  <div class="singlebutton">'."\n";
    if (!empty($href)) {
        $output .= bigbluebuttonbn_view_render_warning_button($href, $text, $class);
    }
    $output .= '  </div>'."\n";
    $output .= $OUTPUT->box_end()."\n";
    return $output;
}

function bigbluebuttonbn_view_render_warning_button($href, $text = '', $class = '') {
    if ($text == '') {
        $text = get_string('ok', 'moodle');
    }
    if ($class == '') {
        $class = 'btn btn-secondary';
    }
    $output  = '  <form method="post" action="' . $href . '" class="form-inline">'."\n";
    $output .= '      <button type="submit" class="' . $class . '"'."\n";
    $output .= '          title=""'."\n";
    $output .= '          >' . $text . '</button>'."\n";
    $output .= '  </form>'."\n";
    return $output;
}

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

    $output = $OUTPUT->box_start('generalbox boxaligncenter', 'bigbluebuttonbn_view_message_box');
    $output .= '<br><span id="status_bar"></span><br>';
    $output .= '<span id="control_panel"></span>';
    $output .= $OUTPUT->box_end();

    $output .= $OUTPUT->box_start('generalbox boxaligncenter', 'bigbluebuttonbn_view_action_button_box');
    $output .= '<br><br><span id="join_button"></span>&nbsp;<span id="end_button"></span>'."\n";
    $output .= $OUTPUT->box_end();

    if ($activity == 'ended') {
        $output .= bigbluebuttonbn_view_ended($bbbsession);
    }

    return $output;
}

function bigbluebuttonbn_view_include_recordings(&$bbbsession) {
    if ($bbbsession['bigbluebuttonbn']->type == BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY &&
        $bbbsession['bigbluebuttonbn']->recordings_imported) {
        return false;
    }
    return true;
}

function bigbluebuttonbn_view_render_recordings(&$bbbsession, $showroom, &$jsvars) {
    $bigbluebuttonbnid = null;
    if ($showroom) {
        $bigbluebuttonbnid = $bbbsession['bigbluebuttonbn']->id;
    }

    // Get recordings.
    $recordings = array();
    if ( bigbluebuttonbn_view_include_recordings($bbbsession) ) {
        $recordings = bigbluebuttonbn_get_recordings(
            $bbbsession['course']->id, $bigbluebuttonbnid, $showroom,
            $bbbsession['bigbluebuttonbn']->recordings_deleted
          );
    }

    // Get recording links.
    $recordingsimported = bigbluebuttonbn_get_recordings_imported_array(
        $bbbsession['course']->id, $bigbluebuttonbnid, $showroom
      );

    /* Perform aritmetic addition instead of merge so the imported recordings corresponding to existent
     * recordings are not included. */
    $recordings += $recordingsimported;

    if (empty($recordings) || array_key_exists('messageKey', $recordings)) {
        // There are no recordings to be shown.
        return html_writer::div(get_string('view_message_norecordings', 'bigbluebuttonbn'), '',
            array('id' => 'bigbluebuttonbn_html_table'));
    }

    // There are recordings for this meeting.
    // JavaScript variables for recordings.
    $jsvars += array(
            'recordings_html' => $bbbsession['bigbluebuttonbn']->recordings_html == '1',
          );

    // If there are meetings with recordings load the data to the table.
    if ($bbbsession['bigbluebuttonbn']->recordings_html) {
        // Render a plain html table.
        return bigbluebutton_output_recording_table($bbbsession, $recordings)."\n";
    }

    // JavaScript variables for recordings with YUI.
    $jsvars += array(
            'columns' => bigbluebuttonbn_get_recording_columns($bbbsession),
            'data' => bigbluebuttonbn_get_recording_data($bbbsession, $recordings),
          );

    // Render a YUI table.
    return html_writer::div('', '', array('id' => 'bigbluebuttonbn_yui_table'));
}

function bigbluebuttonbn_view_render_imported(&$bbbsession) {
    global $CFG;

    $button = html_writer::tag('input', '',
        array('type' => 'button',
              'value' => get_string('view_recording_button_import', 'bigbluebuttonbn'),
              'class' => 'btn btn-secondary',
              'onclick' => 'window.location=\''.$CFG->wwwroot.'/mod/bigbluebuttonbn/import_view.php?bn='.
                  $bbbsession['bigbluebuttonbn']->id.'\''));
    $output = html_writer::start_tag('br');
    $output .= html_writer::tag('span', $button, array('id' => 'import_recording_links_button'));
    $output .= html_writer::tag('span', '', array('id' => 'import_recording_links_table'));

    return $output;
}

function bigbluebuttonbn_view_ended(&$bbbsession) {
    global $OUTPUT;

    if (!is_null($bbbsession['presentation']['url'])) {
        $attributes = array('title' => $bbbsession['presentation']['name']);
        $icon = new pix_icon($bbbsession['presentation']['icon'], $bbbsession['presentation']['mimetype_description']);

        return '<h4>'.get_string('view_section_title_presentation', 'bigbluebuttonbn').'</h4>'.
                ''.$OUTPUT->action_icon($bbbsession['presentation']['url'], $icon, null, array(), false).''.
                ''.$OUTPUT->action_link($bbbsession['presentation']['url'],
                      $bbbsession['presentation']['name'], null, $attributes).'<br><br>';
    }

    return '';
}

// Hot-fix: Only for v2017101004, to be removed in the next release if db upgrade is added.
function bigbluebuttonbn_verify_passwords(&$bigbluebuttonbn) {
    global $DB;
    if (empty($bigbluebuttonbn->moderatorpass) || empty($bigbluebuttonbn->viewerpass)) {
        $bigbluebuttonbn->moderatorpass = bigbluebuttonbn_random_password(12);
        $bigbluebuttonbn->viewerpass = bigbluebuttonbn_random_password(12, $bigbluebuttonbn->moderatorpass);
        // Store passwords in the database.
        $DB->update_record('bigbluebuttonbn', $bigbluebuttonbn);
    }
}
