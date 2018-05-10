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

if (!class_exists("\\core_privacy\\tests\\provider_testcase", true)) {
    die();
}

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

        $instancetable = array_shift($itemcollection);
        $this->assertEquals('bigbluebuttonbn', $instancetable->get_name());

        $instancelogstable = array_shift($itemcollection);
        $this->assertEquals('bigbluebuttonbn_logs', $instancelogstable->get_name());

        $bigbluebuttonserver = array_shift($itemcollection);
        $this->assertEquals('bigbluebutton', $bigbluebuttonserver->get_name());

        $privacyfields = $instancetable->get_privacy_fields();
        $this->assertArrayHasKey('participants', $privacyfields);
        $this->assertEquals('privacy:metadata:bigbluebuttonbn', $instancetable->get_summary());

        $privacyfields = $instancelogstable->get_privacy_fields();
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('timecreated', $privacyfields);
        $this->assertArrayHasKey('meetingid', $privacyfields);
        $this->assertArrayHasKey('log', $privacyfields);
        $this->assertArrayHasKey('meta', $privacyfields);
        $this->assertEquals('privacy:metadata:bigbluebuttonbn_logs', $instancelogstable->get_summary());

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

        // The bigbluebuttonbn activity the user will have to work with.
        $bigbluebuttonbn = $this->getDataGenerator()->create_module('bigbluebuttonbn', array('course' => $course->id));

        // Another bigbluebuttonbn activity that has no user activity.
        $this->getDataGenerator()->create_module('bigbluebuttonbn', array('course' => $course->id));

        // Create a user which will make a submission.
        $user = $this->getDataGenerator()->create_user();

        $this->create_bigbluebuttonbn_log($course->id, $bigbluebuttonbn->id, $user->id);

        // Check the contexts supplied are correct.
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);

        $contextformodule = $contextlist->current();
        $cmcontext = context_module::instance($bigbluebuttonbn->cmid);
        $this->assertEquals($cmcontext->id, $contextformodule->id);
    }

    /**
     * Test for provider::export_user_data().
     */
    public function test_export_for_context_logs() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        // The bigbluebuttonbn activity the user will have to work with.
        $bigbluebuttonbn = $this->getDataGenerator()->create_module('bigbluebuttonbn', array('course' => $course->id));

        // Create users which will make submissions.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->create_bigbluebuttonbn_log($course->id, $bigbluebuttonbn->id, $user1->id);
        $this->create_bigbluebuttonbn_log($course->id, $bigbluebuttonbn->id, $user1->id);
        $this->create_bigbluebuttonbn_log($course->id, $bigbluebuttonbn->id, $user2->id);

        // Export all of the data for the context for user 1.
        $cmcontext = context_module::instance($bigbluebuttonbn->cmid);
        $this->export_context_data_for_user($user1->id, $cmcontext, 'mod_bigbluebuttonbn');
        $writer = \core_privacy\local\request\writer::with_context($cmcontext);

        $this->assertTrue($writer->has_any_data());

        $data = $writer->get_data();
        $this->assertCount(2, $data->logs);
    }

    /**
     * Test for provider::delete_data_for_all_users_in_context().
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        $this->resetAfterTest();

        $e = $this->get_bigbluebuttonbn_environemnt();

        // Before deletion, we should have 2 responses.
        $count = $DB->count_records('bigbluebuttonbn_logs', ['bigbluebuttonbnid' => $e['instance']->id]);
        $this->assertEquals(2, $count);

        // Delete data based on context.
        $cmcontext = context_module::instance($e['instance']->cmid);
        provider::delete_data_for_all_users_in_context($cmcontext);

        // After deletion, the bigbluebuttonbn logs for that activity should have been deleted.
        $count = $DB->count_records('bigbluebuttonbn_logs', ['bigbluebuttonbnid' => $e['instance']->id]);
        $this->assertEquals(0, $count);
    }

    /**
     * Test for provider::delete_data_for_user().
     */
    public function test_delete_data_for_user() {
        global $DB;

        $this->resetAfterTest();

        $e = $this->get_bigbluebuttonbn_environemnt();

        // Delete data for the first user.
        $context = \context_module::instance($e['instance']->cmid);
        $contextlist = new \core_privacy\local\request\approved_contextlist($e['users'][0], 'bigbluebuttonbn',
            [$context->id]);
        provider::delete_data_for_user($contextlist);

        // After deletion the bigbluebuttonbn logs for the first user should have been deleted.
        $count = $DB->count_records('bigbluebuttonbn_logs',
            ['bigbluebuttonbnid' => $e['instance']->id, 'userid' => $e['users'][0]->id]);
        $this->assertEquals(0, $count);

        // Check the logs for the other user is still there.
        $bigbluebuttonbnlogs = $DB->get_records('bigbluebuttonbn_logs');
        $this->assertCount(1, $bigbluebuttonbnlogs);
        $lastlog = reset($bigbluebuttonbnlogs);
        $this->assertEquals($e['users'][1]->id, $lastlog->userid);
    }

    /**
     * Prepares the environment for testing.
     *
     * @return array $e
     */
    protected function get_bigbluebuttonbn_environemnt() {
        $e = array();

        // Create a course.
        $e['course'] = $this->getDataGenerator()->create_course();

        // Create a bigbluebuttonbn instance.
        $e['instance'] = $this->getDataGenerator()->create_module('bigbluebuttonbn',
            array('course' => $e['course']->id));

        // Create users that will use the bigbluebuttonbn instance.
        $e['users'][] = $this->getDataGenerator()->create_user();
        $e['users'][] = $this->getDataGenerator()->create_user();

        // Create the bigbluebuttonbn logs.
        $this->create_bigbluebuttonbn_log($e['course']->id, $e['instance']->id, $e['users'][0]->id);
        $this->create_bigbluebuttonbn_log($e['course']->id, $e['instance']->id, $e['users'][1]->id);

        return $e;
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

        $logdata = [
            'courseid' => $courseid,
            'bigbluebuttonbnid' => $bigbluebuttonbnid,
            'userid' => $userid,
            'meetingid' => sha1($bigbluebuttonbnid) . '-' . $courseid . '-' . $bigbluebuttonbnid,
            'timecreated' => time(),
            'log' => 'create',
            'meta' => null
        ];

        $DB->insert_record('bigbluebuttonbn_logs', $logdata);
    }
}
