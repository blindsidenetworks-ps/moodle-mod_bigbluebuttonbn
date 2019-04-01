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
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 * @author    Darko Miletic  (darko.miletic [at] gmail [dt] com)
 */

defined('MOODLE_INTERNAL') || die();

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
    $reset = get_string('reset');
    $search = get_string('search');
    $output = "<form id='bigbluebuttonbn_recordings_searchform'>
                 <input id='searchtext' type='text'>
                 <input id='searchsubmit' type='submit' value='{$search}'>
                 <input id='searchreset' type='submit' value='{$reset}'>
               </form>";
    $output .= html_writer::div('', '', array('id' => 'bigbluebuttonbn_recordings_table'));

    return $output;
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
