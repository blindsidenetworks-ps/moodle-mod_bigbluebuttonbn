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
 * View for BigBlueButton interaction.
 *
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2015-2017 Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
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

if (isset($SESSION) && isset($SESSION->bigbluebuttonbn_bbbsession)) {
    $bbbsession = $SESSION->bigbluebuttonbn_bbbsession;
}

$output = '';

// Print the page header.
$PAGE->set_context($context);
$PAGE->set_url('/mod/bigbluebuttonbn/import_view.php', array('id' => $cm->id, 'bigbluebuttonbn' => $bigbluebuttonbn->id));
$PAGE->set_title(format_string($bigbluebuttonbn->name));
$PAGE->set_cacheable(false);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

$output .= $OUTPUT->header();

$output .= '<h4>Import recording links</h4>';

$options = bigbluebuttonbn_import_get_courses_for_select($bbbsession);
$selected = bigbluebuttonbn_selected_course($options, $tc);
if (empty($options)) {
    $output .= html_writer::tag('div', get_string('view_error_import_no_courses', 'bigbluebuttonbn'));
} else {
    $output .= html_writer::tag('div', html_writer::select($options, 'import_recording_links_select', $selected));

    // Get course recordings.
    if ($course->id == $selected) {
        $recordings = bigbluebuttonbn_get_recordings($selected, $bigbluebuttonbn->id, false,
            bigbluebuttonbn_get_cfg_importrecordings_from_deleted_activities_enabled());
    } else {
        $recordings = bigbluebuttonbn_get_recordings($selected, null, false,
            bigbluebuttonbn_get_cfg_importrecordings_from_deleted_activities_enabled());
    }
    if (!empty($recordings)) {
        // Exclude the ones that are already imported.
        $recordings = bigbluebuttonbn_unset_existent_recordings_already_imported($recordings,
            $course->id, $bigbluebuttonbn->id);
    }
    // Store recordings (indexed) in a session variable.
    $SESSION->bigbluebuttonbn_importrecordings = $recordings;

    // Proceed with rendering.
    if (!empty($recordings)) {
        $output .= html_writer::tag('span', '',
            ['id' => 'import_recording_links_table', 'name' => 'import_recording_links_table']);
        $output .= bigbluebutton_output_recording_table($bbbsession, $recordings, ['importing']);
    } else {
        $output .= html_writer::tag('div', get_string('view_error_import_no_recordings', 'bigbluebuttonbn'));
    }
    $output .= html_writer::start_tag('br');
    $output .= html_writer::tag('input', '',
        array('type' => 'button', 'value' => get_string('view_recording_button_return', 'bigbluebuttonbn'),
              'onclick' => 'window.location=\''.$CFG->wwwroot.'/mod/bigbluebuttonbn/view.php?id='.$cm->id.'\''));

    $jsvars = array(
        'bn' => $bn,
        'tc' => $selected,
        'locales' => bigbluebuttonbn_get_locales_for_view(),
    );
    $PAGE->requires->data_for_js('bigbluebuttonbn', $jsvars);

    $jsmodule = array(
            'name' => 'mod_bigbluebuttonbn',
            'fullpath' => '/mod/bigbluebuttonbn/module.js',
            'requires' => array('datasource-get', 'datasource-jsonschema', 'datasource-polling'),
    );
    $PAGE->requires->js_init_call('M.mod_bigbluebuttonbn.import_view_init', array(), false, $jsmodule);
}

$output .= $OUTPUT->footer();

// Finally, render the output.
echo $output;

function bigbluebuttonbn_selected_course($options, $tc = '') {
    if (empty($options)) {
        $selected = '';
    } else if (array_key_exists($tc, $options)) {
        $selected = $tc;
    } else {
        $selected = '';
    }

    return $selected;
}
