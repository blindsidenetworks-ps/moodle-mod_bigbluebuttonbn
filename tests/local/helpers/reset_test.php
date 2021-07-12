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
 * BBB Library tests class.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
namespace mod_bigbluebuttonbn\local\helpers;
defined('MOODLE_INTERNAL') || die();

use core_tag_tag;
use mod_bigbluebuttonbn\local\bbb_constants;

global $CFG;
require_once($CFG->dirroot . '/mod/bigbluebuttonbn/tests/helpers.php');

/**
 * BBB Library tests class.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
class reset_test extends \bbb_simple_test {

    /**
     * Reset course item test
     */
    public function test_bigbluebuttonbn_reset_course_items() {
        global $CFG;
        $this->resetAfterTest();
        $CFG->bigbluebuttonbn_recordings_enabled = false;
        $results = reset::bigbluebuttonbn_reset_course_items();
        $this->assertEquals(array("events" => 0, "tags" => 0, "logs" => 0), $results);
        $CFG->bigbluebuttonbn_recordings_enabled = true;
        $results = reset::bigbluebuttonbn_reset_course_items();
        $this->assertEquals(array("events" => 0, "tags" => 0, "logs" => 0, "recordings" => 0), $results);
    }

    /**
     * Reset get_status test
     */
    public function test_bigbluebuttonbn_reset_getstatus() {
        $this->resetAfterTest();
        $result = reset::bigbluebuttonbn_reset_getstatus('events');
        $this->assertEquals(array(
                'component' => 'BigBlueButton',
                'item' => 'Deleted events',
                'error' => false,
        ), $result);
    }

    /**
     * Reset event test
     */
    public function test_bigbluebuttonbn_reset_events() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance(
                null,
                ['openingtime' => time()]
        );
        $formdata = $this->get_form_data_from_instance($bbactivity);
        \mod_bigbluebuttonbn\local\helpers\mod_helper::bigbluebuttonbn_process_post_save_event($formdata);
        $this->assertEquals(1, $DB->count_records(
                'event',
                array('modulename' => 'bigbluebuttonbn', 'courseid' => $this->course->id)));
        reset::bigbluebuttonbn_reset_events($this->course->id);
        $this->assertEquals(0, $DB->count_records(
                'event',
                array('modulename' => 'bigbluebuttonbn', 'courseid' => $this->course->id)));
    }

    /**
     * Reset tags test
     */
    public function test_bigbluebuttonbn_reset_tags() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance(null,
                array('course' => $this->course->id),
                ['visible' => true]
        );
        core_tag_tag::add_item_tag('mod_bigbluebuttonbn', 'bbitem', $bbactivity->id, $bbactivitycontext, 'newtag');
        $alltags = core_tag_tag::get_item_tags('mod_bigbluebuttonbn', 'bbitem', $bbactivity->id);
        $this->assertCount(1, $alltags);
        reset::bigbluebuttonbn_reset_tags($this->course->id);
        $alltags = core_tag_tag::get_item_tags('mod_bigbluebuttonbn', 'bbitem', $bbactivity->id);
        $this->assertCount(0, $alltags);
    }

    /**
     * Reset logs test
     */
    public function test_bigbluebuttonbn_reset_logs() {
        global $DB;
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance(null,
                array('course' => $this->course->id),
                ['visible' => true]
        );

        // User has already joined the meeting (there is log event BIGBLUEBUTTONBN_LOG_EVENT_JOIN already for this user).
        $overrides = array('meetingid' => $bbactivity->meetingid);
        $meta = '{"origin":0}';
        logs::bigbluebuttonbn_log($bbactivity, bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_JOIN, $overrides, $meta);

        reset::bigbluebuttonbn_reset_logs($this->course->id);
        $this->assertEquals(0, $DB->count_records(
                'bigbluebuttonbn_logs',
                array('bigbluebuttonbnid' => $bbactivity->id, 'courseid' => $this->course->id)));
    }

    /**
     * Reset get_recordings test
     */
    public function test_bigbluebuttonbn_reset_recordings() {
        $this->resetAfterTest();
        // TODO complete this test.
        $this->markTestSkipped(
            'For now this test relies on an API call so we need to mock the API CALL.'
        );
    }

}


