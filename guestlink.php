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

require('../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');

global $PAGE, $OUTPUT;

$gid = required_param('gid', PARAM_ALPHANUM); // This is required.
$guestname = optional_param('guestname', '', PARAM_TEXT);
$guestpass = optional_param('guestpass', '', PARAM_TEXT);
$PAGE->set_url(new moodle_url('/mod/bigbluebuttonbn/guestlink.php',
        ['gid' => $gid, 'guestname' => $guestname, 'guestpass' => $guestpass]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');

if (!\mod_bigbluebuttonbn\locallib\config::get('participant_guestlink')) {
    echo get_string('guestlink_form_guestlink_disabled', 'bigbluebuttonbn');
    die();
}

$bigbluebuttonbn = bigbluebuttonbn_get_bigbluebuttonbn_by_guestlinkid($gid);
if (!$bigbluebuttonbn->guestlinkenabled) {
    echo get_string('guestlink_form_guestlink_disabled_instance', 'bigbluebuttonbn');
    die();
}
$valid = ($guestname && ($bigbluebuttonbn->guestpass == $guestpass || !$bigbluebuttonbn->guestpass));

if (!$valid) {
    $guestpasserrormessage = false;
    $guestnameerrormessage = false;
    if ($guestpass && $bigbluebuttonbn->guestpass != $guestpass) {
        $guestpasserrormessage = true;
    }
    $context = ['name' => $bigbluebuttonbn->name, 'gid' => $gid,
        'guestpassenabled' => $bigbluebuttonbn->guestpass,
        'guestpasserrormessage' => $guestpasserrormessage,
        'guestnameerrormessage' => $guestnameerrormessage,
        'guestname' => $guestname,
    ];

    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('mod_bigbluebuttonbn/guestaccess_view', $context);
    echo $OUTPUT->footer();
} else {
    list($course, $cm) = get_course_and_cm_from_instance($bigbluebuttonbn, 'bigbluebuttonbn');
    $context = context_module::instance($cm->id);
    $bbbsession = [];
    $bbbsession['course'] = $course;
    $bbbsession['coursename'] = $course->fullname;
    $bbbsession['cm'] = $cm;
    $bbbsession['bigbluebuttonbn'] = $bigbluebuttonbn;
    $bbbsession['guest'] = true;

    \mod_bigbluebuttonbn\locallib\bigbluebutton::view_bbbsession_set($context, $bbbsession);
    if (bigbluebuttonbn_is_meeting_running($bbbsession['meetingid'])) {
        $bbbsession['username'] = $guestname;
        // Since the meeting is already running, we just join the session.
        bigbluebuttonbn_join_meeting($bbbsession, $bigbluebuttonbn);
    } else {
        $pinginterval = (int)\mod_bigbluebuttonbn\locallib\config::get('waitformoderator_ping_interval') * 1000;
        echo $OUTPUT->header();
        echo get_string('guestlink_form_join_waiting', 'bigbluebuttonbn');
        echo "<script> setTimeout(function () {location.reload();}, $pinginterval);</script>";
        echo $OUTPUT->footer();
    }
}
