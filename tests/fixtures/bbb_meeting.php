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
 * Mocked BigBlueButton Server.
 *
 * This file provides sample responses for use in testing.
 *
 * @package mod_bigbluebuttonbn
 * @copyright 2021 Andrew Lyons <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// We should not have any require login or MOODLE_INTERNAL Check in this file.
// phpcs:disable moodle.Files.RequireLogin.Missing
use mod_bigbluebuttonbn\instance;

require_once(__DIR__ . '/../../../../config.php');
global $OUTPUT, $PAGE;

defined('BEHAT_SITE_RUNNING') || redirect(new moodle_url('/'));
require_login();
$bbbid = required_param('bbbid', PARAM_INT);
// No need for language strings here. This is just for Behat.
$instance = instance::get_from_instanceid($bbbid);
$PAGE->set_cm($instance->get_cm());
$PAGE->set_title('Bigblue Button Fake Conference Page');
$PAGE->set_url('/mod/bigbluebuttonbn/tests/fixtures/bbb_meeting.php', array('bbbid' => $bbbid));
echo $OUTPUT->header();
echo $OUTPUT->single_button(
    new moodle_url('/mod/bigbluebuttonbn/bbb_view.php', array('action' => 'logout', 'bn' => $bbbid)),
    'End BBB Meeting');
echo $OUTPUT->footer();
