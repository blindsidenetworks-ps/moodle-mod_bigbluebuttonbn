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

use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\bigbluebutton;
use mod_bigbluebuttonbn\local\helpers\logs;
use mod_bigbluebuttonbn\local\view;
use mod_bigbluebuttonbn\output\view_page;
use mod_bigbluebuttonbn\plugin;

require(__DIR__ . '/../../config.php');

// Get the bbb instance from either the cmid (id), or the instanceid (bn).
$id = optional_param('id', 0, PARAM_INT);
if ($id) {
    $instance = instance::get_from_cmid($id);
} else {
    $bn = optional_param('bn', 0, PARAM_INT);
    if ($bn) {
        $instance = instance::get_from_instanceid($bn);
    }
}

if (!$instance) {
    throw new moodle_exception('view_error_url_missing_parameters', plugin::COMPONENT);
}

$cm = $instance->get_cm();
$course = $instance->get_course();
$bigbluebuttonbn = $instance->get_instance_data();

require_login($course, true, $cm);

$groupid = groups_get_activity_group($cm, true) ?: null;
if ($groupid) {
    $instance->set_group_id($groupid);
}

// In locallib.
// TODO Move to \mod_bigbluebuttonbn\log::log_event().
logs::bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events['view'], $bigbluebuttonbn);
//END TODO

// Require a working server.
bigbluebutton::require_working_server($instance);

// Mark viewed by user (if required).
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Print the page header.
$PAGE->set_url($instance->get_view_url());
$PAGE->set_title($cm->name);
$PAGE->set_cacheable(false);
$PAGE->set_heading($course->fullname);

// Validate if the user is in a role allowed to join.
if (!$instance->can_join()) {
    // TODO Consider using \core\notification::add('message', \core\notification::ERROR);
    // Combined with a redirect() to the course homepage.
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        sprintf(
            '<p>%s</p>%s',
            get_string(isguestuser() ? 'view_noguests' : 'view_nojoin', plugin::COMPONENT),
            get_string('liketologin')
        ),
        get_login_url(),
        new moodle_url('/course/view.php', ['id' => $course->id])
    );
    echo $OUTPUT->footer();
    exit;
}

// Output starts.
$renderer = $PAGE->get_renderer('mod_bigbluebuttonbn');

echo $OUTPUT->header();
echo $renderer->render(new view_page($instance));

// Output finishes.
echo $OUTPUT->footer();

// Shows version as a comment.
echo '<!-- ' . $instance->get_origin_data()->originTag . ' -->' . "\n";

// Initialize session variable used across views.
// TODO: Get rid of this ASAP !
// Before this can happen, all places which only retrieve it from the SESSION need to modify the page URL to specify the
// instanceid.
$SESSION->bigbluebuttonbn_bbbsession = $instance->get_legacy_session_object();
