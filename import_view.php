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
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

use mod_bigbluebuttonbn\local\view;
use mod_bigbluebuttonbn\plugin;
use mod_bigbluebuttonbn\output\import_view;
use mod_bigbluebuttonbn\output\renderer;

require(__DIR__ . '/../../config.php');
global $DB, $PAGE, $OUTPUT;
$originbn = required_param('originbn', PARAM_INT);
$frombn = optional_param('frombn', 0, PARAM_INT);
$courseidscope = optional_param('courseidscope', 0, PARAM_INT);

if (!$originbn) {
    throw new moodle_exception('view_error_url_missing_parameters', plugin::COMPONENT);
}

list('cm' => $cm, 'course' => $course, 'bigbluebuttonbn' => $bigbluebuttonbn) =
    view::bigbluebuttonbn_view_instance_bigbluebuttonbn($originbn);
require_login($course, true, $cm);

// TODO: check if this is still necessary.
if (!isset($SESSION) || !isset($SESSION->bigbluebuttonbn_bbbsession)) {
    throw new moodle_exception('view_error_invalid_session', plugin::COMPONENT);
}

if (!(boolean) \mod_bigbluebuttonbn\local\config::importrecordings_enabled()) {
    throw new moodle_exception('view_message_importrecordings_disabled', plugin::COMPONENT);
}

// Print the page header.
$PAGE->set_url('/mod/bigbluebuttonbn/import_view.php', ['originbn' => $bigbluebuttonbn->id]);
$PAGE->set_title($bigbluebuttonbn->name);
$PAGE->set_cacheable(false);
$PAGE->set_heading($course->fullname);

// View widget must be initialized here in order to properly load javascript.
$view = new import_view($originbn, $frombn, $courseidscope);

/** @var renderer $renderer */
$renderer = $PAGE->get_renderer(plugin::COMPONENT);

echo $OUTPUT->header();

echo $renderer->render($view);

echo $OUTPUT->footer();
