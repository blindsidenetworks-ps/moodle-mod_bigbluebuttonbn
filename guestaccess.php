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
 * Page to grant external users access to a BBB session
 *
 * @package    mod_bigbluebuttonbn
 * @author     Angela Baier
 * @copyright  2020 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');

global $PAGE, $OUTPUT;

// Process parameters.
// Process the guest link ID which it is required.
$gid = required_param('gid', PARAM_ALPHANUM);
// Process the guestname and guest access code which is optional until the user enters the room.
$guestname = optional_param('guestname', '', PARAM_TEXT);
$guestlinkpass = optional_param('guestlinkpass', '', PARAM_TEXT);

// Set up the page.
$PAGE->set_url(new moodle_url('/mod/bigbluebuttonbn/guestaccess.php',
        ['gid' => $gid, 'guestname' => $guestname, 'guestlinkpass' => $guestlinkpass]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');

// Check if the guest access feature is enabled on this Moodle instance. If not, redirect the user after showing an error message.
if (!\mod_bigbluebuttonbn\locallib\config::guestlink_enabled()) {
    $redirecturl = new moodle_url('/');
    throw new moodle_exception('guestaccess_feature_disabled', 'bigbluebuttonbn', $redirecturl);
}

// Get the BigBlueButton activity instance for the given guest link ID.
$bigbluebuttonbn = bigbluebuttonbn_get_bigbluebuttonbn_by_guestlinkid($gid);

// Check if an activity instance was found for the given guest link ID. If not, redirect the user after showing an error message.
if (!$bigbluebuttonbn->guestlink) {
    $redirecturl = new moodle_url('/');
    throw new moodle_exception('guestaccess_instance_notfound', 'bigbluebuttonbn', $redirecturl);
}

// Prepare template context.
$actionurl = new moodle_url('/mod/bigbluebuttonbn/guestaccess.php', ['gid' => $gid]);
$templatecontext = ['name' => $bigbluebuttonbn->name,
        'action' => $actionurl->out(),
        'gid' => $gid,
        'guestlinkpassenabled' => is_null($bigbluebuttonbn->guestlinkpass) ? false : true,
        'guestlinkpasserror' => false,
        'guestnameerror' => false,
        'guestname' => ''
];

// Check if the form was submitted by checking if the guest name and (if required) guest access code parameters were contained
// in the GET request at all.
$submitted = isset($_GET['guestname']) && (!$bigbluebuttonbn->guestlinkpass || isset($_GET['guestlinkpass']));

// If the form was not yet submitted.
if ($submitted == false) {
    // Output page header.
    echo $OUTPUT->header();

    // Output the guest access form from the mustache template.
    echo $OUTPUT->render_from_template('mod_bigbluebuttonbn/guestaccess_view', $templatecontext);

    // Output page footer.
    echo $OUTPUT->footer();

    // Otherwise, if the form was submitted.
} else {
    // Check if the submitted data can be considered as valid guest access.
    // This means that a guest name was submitted.
    // Additionally, the correct guest access code was submitted, if a guest access code was set in this activity instance.
    $guestaccessvalid = ($guestname && ($bigbluebuttonbn->guestlinkpass == $guestlinkpass || !$bigbluebuttonbn->guestlinkpass));

    // If the guest access is not valid yet.
    if ($guestaccessvalid == false) {
        // If a guest name was submitted by the user.
        if (!empty($guestname)) {
            // Remember it for pre-filling the form again.
            $templatecontext['guestname'] = $guestname;

            // Otherwise.
        } else {
            // Enable the error message.
            $templatecontext['guestnameerror'] = true;
        }

        // If the correct guest access code was submitted by the user.
        if ($bigbluebuttonbn->guestlinkpass == $guestlinkpass) {
            // Remember it for pre-filling the form again.
            $templatecontext['guestlinkpass'] = $guestlinkpass;

            // Otherwise.
        } else {
            // Enable the error message.
            $templatecontext['guestlinkpasserror'] = true;
        }

        // Output page header.
        echo $OUTPUT->header();

        // Output the guest access form from the mustache template.
        echo $OUTPUT->render_from_template('mod_bigbluebuttonbn/guestaccess_view', $templatecontext);

        // Output page footer.
        echo $OUTPUT->footer();

        // Otherwise, if the guest access is valid and the user can join the room.
    } else {
        // Get the course and course module by the given BigBlueButton instance.
        list($course, $cm) = get_course_and_cm_from_instance($bigbluebuttonbn, 'bigbluebuttonbn');

        // Build BBB session.
        $context = context_module::instance($cm->id);
        $bbbsession = [];
        $bbbsession['course'] = $course;
        $bbbsession['coursename'] = $course->fullname;
        $bbbsession['cm'] = $cm;
        $bbbsession['bigbluebuttonbn'] = $bigbluebuttonbn;
        $bbbsession['guest'] = true;
        \mod_bigbluebuttonbn\locallib\bigbluebutton::view_bbbsession_set($context, $bbbsession);

        // If the meeting is already running.
        if (bigbluebuttonbn_is_meeting_running($bbbsession['meetingid'])) {

            // Set the username.
            $bbbsession['username'] = $guestname;

            // Let the user just join the session.
            bigbluebuttonbn_join_meeting($bbbsession, $bigbluebuttonbn);

            // If the meeting is not running yet.
        } else {
            // Get the ping interval and add it to the template context.
            $pinginterval = (int)\mod_bigbluebuttonbn\locallib\config::get('waitformoderator_ping_interval') * 1000;
            $templatecontext['pinginterval'] = $pinginterval;

            // Output page header.
            echo $OUTPUT->header();

            // Output the guest access form from the mustache template.
            echo $OUTPUT->render_from_template('mod_bigbluebuttonbn/guestaccess_wait', $templatecontext);

            // Output page footer.
            echo $OUTPUT->footer();
        }
    }
}
