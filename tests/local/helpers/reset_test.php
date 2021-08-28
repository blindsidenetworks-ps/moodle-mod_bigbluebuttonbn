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

use core_tag_tag;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\logger;
use mod_bigbluebuttonbn\test\testcase_helper;

/**
 * BBB Library tests class.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 * @coversDefaultClass \mod_bigbluebuttonbn\local\helpers\reset
 * @covers \mod_bigbluebuttonbn\local\helpers\reset
 */
class reset_test extends testcase_helper {

    /**
     * Reset course item test
     */
    public function test_reset_course_items() {
        global $CFG;
        $this->resetAfterTest();
        $CFG->bigbluebuttonbn_recordings_enabled = false;
        $results = reset::reset_course_items();
        $this->assertEquals(array("events" => 0, "tags" => 0, "logs" => 0), $results);
        $CFG->bigbluebuttonbn_recordings_enabled = true;
        $results = reset::reset_course_items();
        $this->assertEquals(array("events" => 0, "tags" => 0, "logs" => 0, "recordings" => 0), $results);
    }

    /**
     * Reset get_status test
     */
    public function test_reset_getstatus() {
        $this->resetAfterTest();
        $result = reset::reset_getstatus('events');
        $this->assertEquals(array(
                'component' => 'BigBlueButton',
                'item' => 'Deleted events',
                'error' => false,
        ), $result);
    }

    /**
     * Reset event test
     */
    public function test_reset_events() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance(
                null,
                ['openingtime' => time()]
        );
        $formdata = $this->get_form_data_from_instance($bbactivity);
        \mod_bigbluebuttonbn\local\helpers\mod_helper::process_post_save($formdata);
        $this->assertEquals(1, $DB->count_records(
                'event',
                array('modulename' => 'bigbluebuttonbn', 'courseid' => $this->course->id)));
        reset::reset_events($this->course->id);
        $this->assertEquals(0, $DB->count_records(
                'event',
                array('modulename' => 'bigbluebuttonbn', 'courseid' => $this->course->id)));
    }

    /**
     * Reset tags test
     */
    public function test_reset_tags() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance(null,
                array('course' => $this->course->id),
                ['visible' => true]
        );
        core_tag_tag::add_item_tag('mod_bigbluebuttonbn', 'bbitem', $bbactivity->id, $bbactivitycontext, 'newtag');
        $alltags = core_tag_tag::get_item_tags('mod_bigbluebuttonbn', 'bbitem', $bbactivity->id);
        $this->assertCount(1, $alltags);
        reset::reset_tags($this->course->id);
        $alltags = core_tag_tag::get_item_tags('mod_bigbluebuttonbn', 'bbitem', $bbactivity->id);
        $this->assertCount(0, $alltags);
    }
}
