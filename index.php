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
 * View all BigBlueButton instances in this course.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 */

use mod_bigbluebuttonbn\plugin;
use mod_bigbluebuttonbn\output\renderer;
use mod_bigbluebuttonbn\output\index;

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/locallib.php');

$id = required_param('id', PARAM_INT);
$a = optional_param('a', 0, PARAM_INT);
$g = optional_param('g', 0, PARAM_INT);

$course = $DB->get_record('course', ['id' => $id]);
if (!$course) {
    throw new moodle_exception('invalidcourseid', plugin::COMPONENT);
}

require_login($course, true);

$PAGE->set_url('/mod/bigbluebuttonbn/index.php', ['id' => $id]);
$PAGE->set_title(get_string('modulename', plugin::COMPONENT));
$PAGE->set_heading($course->fullname);
$PAGE->set_cacheable(false);
$PAGE->set_pagelayout('incourse');

$PAGE->navbar->add($PAGE->title, $PAGE->url);

$action = optional_param('action', '', PARAM_TEXT);
if ($action === 'end') {
    // A request to end the meeting.
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', ['id' => $a]);
    if (!$bigbluebuttonbn) {
        throw new moodle_exception('index_error_bbtn', plugin::COMPONENT, '', $a);
    }
    $course = $DB->get_record('course', array('id' => $bigbluebuttonbn->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('bigbluebuttonbn', $bigbluebuttonbn->id, $course->id, false, MUST_EXIST);
    // User roles.
    $participantlist = bigbluebuttonbn_get_participant_list($bigbluebuttonbn, $PAGE->context);
    $moderator = bigbluebuttonbn_is_moderator($PAGE->context, $participantlist);
    $administrator = is_siteadmin();
    if ($moderator || $administrator) {
        bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events['meeting_end'], $bigbluebuttonbn);
        echo get_string('index_ending', plugin::COMPONENT);
        $meetingid = sprintf('%s-%d-%d', $bigbluebuttonbn->meetingid, $course->id, $bigbluebuttonbn->id);
        if ($g != 0) {
            $meetingid .= sprintf('[%d]', $g);
        }

        bigbluebuttonbn_end_meeting($meetingid, $bigbluebuttonbn->moderatorpass);
        redirect($PAGE->url);
    }
}

/** @var renderer $renderer */
$renderer = $PAGE->get_renderer(plugin::COMPONENT);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('index_heading', plugin::COMPONENT));
echo $renderer->render(new index($course));
echo $OUTPUT->footer();
