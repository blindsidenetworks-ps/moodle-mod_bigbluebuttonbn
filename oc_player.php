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
 * View for Opencast recordings.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    2021 Farbod Zamani Boroujeni - ELAN e.V.
 */

use mod_bigbluebuttonbn\local\helpers\opencast;
use mod_bigbluebuttonbn\local\view;
use mod_bigbluebuttonbn\plugin;
use context_module;
use moodle_url;

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

global $PAGE, $OUTPUT, $CFG;

require_once($CFG->dirroot . '/mod/lti/locallib.php');
require_once($CFG->dirroot . '/lib/oauthlib.php');

$identifier = required_param('identifier', PARAM_TEXT);
$bn = optional_param('bn', 0, PARAM_INT);

$bbbviewinstance = view::bigbluebuttonbn_view_validator(null, $bn);
if (!$bbbviewinstance) {
    print_error(get_string('view_error_url_missing_parameters', 'bigbluebuttonbn'));
}

// Get configs from filter_opencast
$opencastfilterconfig = opencast::bigbluebuttonbn_check_opencast_filter();
if (!$opencastfilterconfig) {
    print_error(get_string('view_error_missing_filter_opencast_config', 'bigbluebuttonbn'));
}

$cm = $bbbviewinstance['cm'];
$course = $bbbviewinstance['course'];
$bigbluebuttonbn = $bbbviewinstance['bigbluebuttonbn'];
$context = context_module::instance($cm->id);

require_login($course, true, $cm);

// Capability check.
require_capability('mod/bigbluebuttonbn:view', $context);

$baseurl = new moodle_url('/mod/bigbluebuttonbn/oc_player.php', array('identifier' => $identifier, 'bn' => $bn));
$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(format_string($bigbluebuttonbn->name));
$PAGE->set_heading($course->fullname);

// Add $identifier to the end of playerurl.
$opencastfilterconfig['playerurl'] .= $identifier;

// Create LTI parameters.
$params = opencast::bigbluebuttonbn_create_lti_parameters_opencast($opencastfilterconfig);

// Using block_opencast renderer in order to use render_lti_form function.
$opencastrenderer = $PAGE->get_renderer('block_opencast');

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($bigbluebuttonbn->name));

// Render the LTI form from block_opencast renderer function.
echo $opencastrenderer->render_lti_form($opencastfilterconfig['ltiendpoint'], $params);

// Use block_opencast LTI form handler javascript to submit the lti form.
$PAGE->requires->js_call_amd('block_opencast/block_lti_form_handler', 'init');
echo $OUTPUT->footer();
