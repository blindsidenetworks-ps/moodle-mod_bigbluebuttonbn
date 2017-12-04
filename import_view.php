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
 * View for importing BigBlueButtonBN recordings.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2017 Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$bn = required_param('bn', PARAM_INT);
$tc = optional_param('tc', 0, PARAM_INT);

if (!$bn) {
    print_error(get_string('view_error_url_missing_parameters', 'bigbluebuttonbn'));
    return;
}

$bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $bn), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $bigbluebuttonbn->course), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('bigbluebuttonbn', $bigbluebuttonbn->id, $course->id, false, MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);

if (!isset($SESSION) || !isset($SESSION->bigbluebuttonbn_bbbsession)) {
    print_error(get_string('view_error_invalid_session', 'bigbluebuttonbn'));
    return;
}

if (!(boolean)\mod_bigbluebuttonbn\locallib\config::importrecordings_enabled()) {
    print_error(get_string('view_message_importrecordings_disabled', 'bigbluebuttonbn'));
    return;
}

$bbbsession = $SESSION->bigbluebuttonbn_bbbsession;
$options = bigbluebuttonbn_import_get_courses_for_select($bbbsession);
$selected = bigbluebuttonbn_selected_course($options, $tc);
$body = html_writer::tag('div', get_string('view_error_import_no_courses', 'bigbluebuttonbn'));
if (!empty($options)) {
    $body = html_writer::tag('div', html_writer::select($options, 'import_recording_links_select', $selected));
    // Get course recordings.
    $bigbluebuttonbnid = null;
    if ($course->id == $selected) {
        $bigbluebuttonbnid = $bigbluebuttonbn->id;
    }
    $recordings = bigbluebuttonbn_get_allrecordings($selected, $bigbluebuttonbnid, false,
            (boolean)\mod_bigbluebuttonbn\locallib\config::get('importrecordings_from_deleted_enabled'));
    // Exclude the ones that are already imported.
    if (!empty($recordings)) {
        $recordings = bigbluebuttonbn_unset_existent_recordings_already_imported($recordings,
            $course->id, $bigbluebuttonbn->id);
    }
    // Store recordings (indexed) in a session variable.
    $SESSION->bigbluebuttonbn_importrecordings = $recordings;
    // Proceed with rendering.
    if (!empty($recordings)) {
        $body .= html_writer::tag('span', '',
            ['id' => 'import_recording_links_table', 'name' => 'import_recording_links_table']);
        $body .= bigbluebuttonbn_output_recording_table($bbbsession, $recordings, ['import']);
    } else {
        $body .= html_writer::tag('div', get_string('view_error_import_no_recordings', 'bigbluebuttonbn'));
    }
    $body .= html_writer::start_tag('br');
    $body .= html_writer::tag('input', '',
        array('type' => 'button', 'class' => 'btn btn-secondary',
              'value' => get_string('view_recording_button_return', 'bigbluebuttonbn'),
              'onclick' => 'window.location=\''.$CFG->wwwroot.'/mod/bigbluebuttonbn/view.php?id='.$cm->id.'\''));
    // JavaScript for locales.
    $PAGE->requires->strings_for_js(array_keys(bigbluebuttonbn_get_strings_for_js()), 'bigbluebuttonbn');
    // Require JavaScript modules.
    $PAGE->requires->yui_module('moodle-mod_bigbluebuttonbn-imports', 'M.mod_bigbluebuttonbn.imports.init',
        array(array('bn' => $bn, 'tc' => $selected)));
    $PAGE->requires->yui_module('moodle-mod_bigbluebuttonbn-broker', 'M.mod_bigbluebuttonbn.broker.init',
        array());
    $PAGE->requires->yui_module('moodle-mod_bigbluebuttonbn-recordings', 'M.mod_bigbluebuttonbn.recordings.init',
        array('recordings_html' => true));
}
// Print the page header.
$PAGE->set_context($context);
$PAGE->set_url('/mod/bigbluebuttonbn/import_view.php', array('id' => $cm->id, 'bigbluebuttonbn' => $bigbluebuttonbn->id));
$PAGE->set_title(format_string($bigbluebuttonbn->name));
$PAGE->set_cacheable(false);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');
// Render output.
$output = $OUTPUT->header();
$output .= html_writer::tag('h4', get_string('view_recording_button_import', 'bigbluebuttonbn'));
$output .= $body;
$output .= $OUTPUT->footer();
// Finally, render the output.
echo $output;

/**
 * Validate selected course coming as a parameter.
 *
 * @param array   $options
 * @param string  $tc
 * @return string
 */
function bigbluebuttonbn_selected_course($options, $tc = '') {
    if (array_key_exists($tc, $options)) {
        return $tc;
    }
    return '';
}
