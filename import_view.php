<?php
/**
 * View for BigBlueButton interaction
 *
 * @package   mod_bigbluebuttonbn
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/locallib.php');

$bn = required_param('bn', PARAM_INT); // bigbluebuttonbn instance ID
$tc = optional_param('tc', 0, PARAM_INT); // target course ID

if ($bn) {
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $bn), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $bigbluebuttonbn->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('bigbluebuttonbn', $bigbluebuttonbn->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('view_error_url_missing_parameters', 'bigbluebuttonbn'));
}

$context = bigbluebuttonbn_get_context_module($cm->id);

require_login($course, true, $cm);

if (isset($SESSION) && isset($SESSION->bigbluebuttonbn_bbbsession)) {
    $bbbsession = $SESSION->bigbluebuttonbn_bbbsession;
}

$output = '';

/// Print the page header
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

    $recordings = bigbluebuttonbn_getRecordingsArrayByCourse($selected, $bbbsession['endpoint'], $bbbsession['shared_secret']);
    //exclude the ones that are already imported
    $recordings = bigbluebuttonbn_import_exlcude_recordings_already_imported($bbbsession['course']->id, $bbbsession['bigbluebuttonbn']->id, $recordings);
    //store remaining recordings (indexed) in a session variable
    $SESSION->bigbluebuttonbn_importrecordings = bigbluebuttonbn_index_recordings($recordings);
    if (empty($recordings)) {
        $output .= html_writer::tag('div', get_string('view_error_import_no_recordings', 'bigbluebuttonbn'));
    } else {
        $output .= html_writer::tag('span', '', ['id' => 'import_recording_links_table', 'name'=>'import_recording_links_table']);
        $output .= bigbluebutton_output_recording_table($bbbsession, $recordings, ['importing']);
    }
    $output .= html_writer::start_tag('br');
    $buttonoptions = array(
        'type' => 'button',
        'class' => 'btn btn-default',
        'value' => get_string('view_recording_button_return', 'bigbluebuttonbn'),
        'onclick' => 'window.location=\'' . $CFG->wwwroot . '/mod/bigbluebuttonbn/view.php?id=' . $cm->id . '\''
      );
    $output .= html_writer::tag('input', '', $buttonoptions);

    $jsvars = array(
        'bn' => $bn,
        'tc' => $selected,
        'locales' => bigbluebuttonbn_get_locales_for_ui()
    );
    $PAGE->requires->data_for_js('bigbluebuttonbn', $jsvars);

    $jsmodule = array(
            'name'     => 'mod_bigbluebuttonbn',
            'fullpath' => '/mod/bigbluebuttonbn/module.js',
            'requires' => array('datasource-get', 'datasource-jsonschema', 'datasource-polling'),
    );
    $PAGE->requires->js_init_call('M.mod_bigbluebuttonbn.import_view_init', array(), false, $jsmodule);
}

$output .= $OUTPUT->footer();

// finally, render the output
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
