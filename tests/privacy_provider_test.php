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
 * Privacy provider tests.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

use core_privacy\local\metadata\collection;
use mod_bigbluebuttonbn\privacy\provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider tests class.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class mod_bigbluebuttonbn_privacy_provider_testcase extends \core_privacy\tests\provider_testcase {

    /**
     * Test for provider::get_metadata().
     */
    public function test_get_metadata() {
        $this->resetAfterTest(true);

        $collection = new collection('mod_bigbluebuttonbn');
        $newcollection = provider::get_metadata($collection);
        $itemcollection = $newcollection->get_collection();
        $this->assertCount(3, $itemcollection);

        $bigbluebuttonbntable = array_shift($itemcollection);
        $this->assertEquals('bigbluebuttonbn', $bigbluebuttonbntable->get_name());

        $bigbluebuttonbnlogstable = array_shift($itemcollection);
        $this->assertEquals('bigbluebuttonbn_logs', $bigbluebuttonbnlogstable->get_name());

        $bigbluebuttonserver = array_shift($itemcollection);
        $this->assertEquals('bigbluebutton', $bigbluebuttonserver->get_name());

        $privacyfields = $bigbluebuttonbntable->get_privacy_fields();
        $this->assertArrayHasKey('participants', $privacyfields);
        $this->assertEquals('privacy:metadata:bigbluebuttonbn', $bigbluebuttonbntable->get_summary());

        $privacyfields = $bigbluebuttonbnlogstable->get_privacy_fields();
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('timecreated', $privacyfields);
        $this->assertArrayHasKey('meetingid', $privacyfields);
        $this->assertArrayHasKey('log', $privacyfields);
        $this->assertArrayHasKey('meta', $privacyfields);
        $this->assertEquals('privacy:metadata:bigbluebuttonbn_logs', $bigbluebuttonbnlogstable->get_summary());

        $privacyfields = $bigbluebuttonserver->get_privacy_fields();
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('fullname', $privacyfields);
        $this->assertEquals('privacy:metadata:bigbluebutton', $bigbluebuttonserver->get_summary());
    }

    /**
     * Test for provider::get_contexts_for_userid().
     */
    public function test_get_contexts_for_userid() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        // The bigbluebuttonbn activity the user will have submitted something for.
        $bigbluebuttonbn = $this->getDataGenerator()->create_module('bigbluebuttonbn', array('course' => $course->id));

        // Another bigbluebuttonbn activity that has no user activity.
        //$this->getDataGenerator()->create_module('bigbluebuttonbn', array('course' => $course->id));

        // Create a user which will make a submission.
        //$user = $this->getDataGenerator()->create_user();

        //$this->create_bigbluebuttonbn_log($course->id, $bigbluebuttonbn->id, $user->id);

        // Check the contexts supplied are correct.
        //$contextlist = provider::get_contexts_for_userid($user->id);
        //$this->assertCount(2, $contextlist);

        //$contextformodule = $contextlist->current();
        //$cmcontext = context_module::instance($bigbluebuttonbn->cmid);
        //$this->assertEquals($cmcontext->id, $contextformodule->id);

        //$contextlist->next();
        //$contextforsystem = $contextlist->current();
        //$this->assertEquals(SYSCONTEXTID, $contextforsystem->id);
    }

    /**
     * Mimicks the creation of an bigbluebuttonbn log.
     *
     * There is no API we can use to insert an bigbluebuttonbn log, so we
     * will simply insert directly into the database.
     *
     * @param int $courseid
     * @param int $bigbluebuttonbnid
     * @param int $userid
     */
    protected function create_bigbluebuttonbn_log(int $courseid, int $bigbluebuttonbnid, int $userid) {
        global $DB;

        $bigbluebuttonbnlogdata = [
            'courseid' => $courseid,
            'bigbluebuttonbnid' => $bigbluebuttonbnid,
            'userid' => $userid,
            'meetingid' => sha1($bigbluebuttonbnid) . '-' . $courseid . '-' . $bigbluebuttonbnid,
            'timecreated' => time(),
            'meta' => NULL
        ];

        $DB->insert_record('bigbluebuttonbn_logs', $bigbluebuttonbnlogdata);
    }

}
