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
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    2021 Farbod Zamani Boroujeni - ELAN e.V.
 */

global $PAGE, $OUTPUT, $CFG;

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

require_once($CFG->dirroot . '/mod/lti/locallib.php');
require_once($CFG->dirroot . '/lib/oauthlib.php');

$identifier = required_param('identifier', PARAM_TEXT);
$bn = optional_param('bn', 0, PARAM_INT);

$bbbviewinstance = bigbluebuttonbn_view_validator(null, $bn);
if (!$bbbviewinstance) {
    print_error(get_string('view_error_url_missing_parameters', 'bigbluebuttonbn'));
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

// Get endpoint from engageurl setting of filter_opencast plugin or apiurl setting of tool_opencast plugin.
$endpoint = get_config('filter_opencast', 'engageurl');
if (empty($endpoint)) {
    $endpoint = get_config('tool_opencast', 'apiurl');
}

// Get player url either from playerurl setting or default paella.
$playerurl = get_config('filter_opencast', 'playerurl');
if (empty($playerurl)) {
    $playerurl = '/play/' . $identifier;
} else {
    $playerurl .= '?id=' . $identifier;
}

if (strpos($endpoint, 'http') !== 0) {
    $endpoint = 'http://' . $endpoint;
}

$ltiendpoint = rtrim($endpoint, '/') . '/lti';

// Create parameters.
$params = bigbluebuttonbn_create_lti_parameters($ltiendpoint, $playerurl);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($bigbluebuttonbn->name));
echo render_lti_form($ltiendpoint, $params);

$PAGE->requires->js_call_amd('mod_bigbluebuttonbn/mod_lti_form_handler', 'init');
echo $OUTPUT->footer();

/**
 * Create necessary lti parameters.
 * @param string $endpoint of the opencast instance.
 * @param string $playerurl the player url to pass as custom_tool in lti params
 *
 * @return array lti parameters
 * @throws dml_exception
 * @throws moodle_exception
 */
function bigbluebuttonbn_create_lti_parameters($endpoint, $playerurl) {
    global $CFG, $COURSE, $USER;

    // Get consumerkey and consumersecret from filter_opencast.
    $consumerkey = get_config('filter_opencast', 'consumerkey');
    $consumersecret = get_config('filter_opencast', 'consumersecret');

    $helper = new oauth_helper(array('oauth_consumer_key'    => $consumerkey,
                                    'oauth_consumer_secret' => $consumersecret));

    // Set all necessary parameters.
    $params = array();
    $params['oauth_version'] = '1.0';
    $params['oauth_nonce'] = $helper->get_nonce();
    $params['oauth_timestamp'] = $helper->get_timestamp();
    $params['oauth_consumer_key'] = $consumerkey;

    $params['context_id'] = $COURSE->id;
    $params['context_label'] = trim($COURSE->shortname);
    $params['context_title'] = trim($COURSE->fullname);
    $params['resource_link_id'] = 'o' . random_int(1000, 9999) . '-' . random_int(1000, 9999);
    $params['resource_link_title'] = 'Opencast';
    $params['context_type'] = ($COURSE->format == 'site') ? 'Group' : 'CourseSection';
    $params['launch_presentation_locale'] = current_language();
    $params['ext_lms'] = 'moodle-2';
    $params['tool_consumer_info_product_family_code'] = 'moodle';
    $params['tool_consumer_info_version'] = strval($CFG->version);
    $params['oauth_callback'] = 'about:blank';
    $params['lti_version'] = 'LTI-1p0';
    $params['lti_message_type'] = 'basic-lti-launch-request';
    $urlparts = parse_url($CFG->wwwroot);
    $params['tool_consumer_instance_guid'] = $urlparts['host'];
    $params['custom_tool'] = $playerurl;

    // User data.
    $params['user_id'] = $USER->id;
    $params['lis_person_name_given'] = $USER->firstname;
    $params['lis_person_name_family'] = $USER->lastname;
    $params['lis_person_name_full'] = $USER->firstname . ' ' . $USER->lastname;
    $params['ext_user_username'] = $USER->username;
    $params['lis_person_contact_email_primary'] = $USER->email;
    $params['roles'] = lti_get_ims_role($USER, null, $COURSE->id, false);

    if (!empty($CFG->mod_lti_institution_name)) {
        $params['tool_consumer_instance_name'] = trim(html_to_text($CFG->mod_lti_institution_name, 0));
    } else {
        $params['tool_consumer_instance_name'] = get_site()->shortname;
    }

    $params['launch_presentation_document_target'] = 'iframe';
    $params['oauth_signature_method'] = 'HMAC-SHA1';
    $signedparams = lti_sign_parameters($params, $endpoint, "POST", $consumerkey, $consumersecret);
    $params['oauth_signature'] = $signedparams['oauth_signature'];

    return $params;
}

/**
 * Display the lti form.
 *
 * @param string $endpoint of the opencast instance.
 * @param array $params lti parameters.
 * @return string
 */
function render_lti_form($endpoint, $params) {
    $content = "<form action=\"" . $endpoint .
        "\" name=\"ltiLaunchForm\" id=\"ltiLaunchForm\" method=\"post\" encType=\"application/x-www-form-urlencoded\">\n";

    // Construct html form for the launch parameters.
    foreach ($params as $key => $value) {
        $key = htmlspecialchars($key);
        $value = htmlspecialchars($value);
        $content .= "<input type=\"hidden\" name=\"{$key}\"";
        $content .= " value=\"";
        $content .= $value;
        $content .= "\"/>\n";
    }
    $content .= "</form>\n";

    return $content;
}
