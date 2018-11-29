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

defined('MOODLE_INTERNAL') || die();
global $CFG;

use core_privacy\tests\provider_testcase;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use mod_bigbluebuttonbn\privacy\provider;

if (!class_exists("\\core_privacy\\tests\\provider_testcase", true)) {
    die();
}

require_once($CFG->dirroot . '/mod/bigbluebuttonbn/lib.php');

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
        $newcollection = \mod_bigbluebuttonbn\privacy\provider::get_metadata($collection);
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
        $bigbluebuttonbn = $this->getDataGenerator()->create_module('bigbluebuttonbn', array('course' => $course));

        // Another bigbluebuttonbn activity that has no user activity.
        $this->getDataGenerator()->create_module('bigbluebuttonbn', array('course' => $course));

        // Create a user which will make a submission.
        $user = $this->getDataGenerator()->create_user();

        $this->create_bigbluebuttonbn_log($bigbluebuttonbn, $user);

        // Check the contexts supplied are correct.
        $contextlist = \mod_bigbluebuttonbn\privacy\provider::get_contexts_for_userid($user->id);
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
        $bigbluebuttonbn = $this->getDataGenerator()->create_module('bigbluebuttonbn', array('course' => $course));

        // Create users which will make submissions.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->create_bigbluebuttonbn_log($bigbluebuttonbn, $user1);
        $this->create_bigbluebuttonbn_log($bigbluebuttonbn, $user1);
        $this->create_bigbluebuttonbn_log($bigbluebuttonbn, $user2);

        // Export all of the data for the context for user 1.
        $cmcontext = context_module::instance($bigbluebuttonbn->cmid);
        $this->export_context_data_for_user($user1->id, $cmcontext, 'mod_bigbluebuttonbn');
        $writer = \core_privacy\local\request\writer::with_context($cmcontext);

        $this->assertTrue($writer->has_any_data());

        $data = $writer->get_data();
        $this->assertCount(2, $data->logs);
    }

    /**
     * Test that only users with relevant contexts are fetched.
     */
    public function test_get_users_in_context() {
        // For backward compatibility with old versions of Moodle.
        if (!class_exists('\core_privacy\local\request\userlist')) {
            return;
        }

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        // The bigbluebuttonbn activity the user will have to work with.
        $bigbluebuttonbn = $this->getDataGenerator()->create_module('bigbluebuttonbn', array('course' => $course));

        // Create users which will make submissions.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->create_bigbluebuttonbn_log($bigbluebuttonbn, $user1);
        $this->create_bigbluebuttonbn_log($bigbluebuttonbn, $user1);
        $this->create_bigbluebuttonbn_log($bigbluebuttonbn, $user2);

        // Export all of the data for the context for user 1.
        $cmcontext = context_module::instance($bigbluebuttonbn->cmid);

        $userlist = new \core_privacy\local\request\userlist($cmcontext, 'mod_bigbluebuttonbn');
        \mod_bigbluebuttonbn\privacy\provider::get_users_in_context($userlist);

        // Ensure correct users are found in relevant contexts.
        $this->assertCount(2, $userlist);
        $expected = [intval($user1->id), intval($user2->id)];
        $actual = $userlist->get_userids();
        $this->assertEquals(sort($expected), sort($actual));
    }

    /**
     * Test for provider::delete_data_for_all_users_in_context().
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        $this->resetAfterTest();

        $e = $this->get_bigbluebuttonbn_environemnt();

        // Before deletion, we should have 3 responses, 1 Add event and 2 Create events (1 per user).
        $count = $DB->count_records('bigbluebuttonbn_logs', ['bigbluebuttonbnid' => $e['instance']->id]);
        $this->assertEquals(3, $count);

        // Delete data based on context.
        $cmcontext = context_module::instance($e['instance']->cmid);
        \mod_bigbluebuttonbn\privacy\provider::delete_data_for_all_users_in_context($cmcontext);

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
        \mod_bigbluebuttonbn\privacy\provider::delete_data_for_user($contextlist);

        // After deletion the bigbluebuttonbn logs for the first user should have been deleted.
        $count = $DB->count_records('bigbluebuttonbn_logs',
            ['bigbluebuttonbnid' => $e['instance']->id, 'userid' => $e['users'][0]->id]);
        $this->assertEquals(0, $count);

        // Check the logs for the other user is still there.
        $count = $DB->count_records('bigbluebuttonbn_logs',
            ['bigbluebuttonbnid' => $e['instance']->id, 'userid' => $e['users'][1]->id]);
        $this->assertEquals(1, $count);
    }

    /**
     * Test that data for users in approved userlist is deleted.
     */
    public function test_delete_data_for_users() {
        global $DB;

        // For backward compatibility with old versions of Moodle.
        if (!class_exists('\core_privacy\local\request\approved_userlist')) {
            return;
        }

        $this->resetAfterTest();

        $e = $this->get_bigbluebuttonbn_environemnt();

        // Delete user 1 and 2 data from chat 1 context only.
        $context = \context_module::instance($e['instance']->cmid);
        $approveduserids = [$e['users'][0]->id];
        $approvedlist = new \core_privacy\local\request\approved_userlist($context, 'mod_bigbluebuttonbn', $approveduserids);
        \mod_bigbluebuttonbn\privacy\provider::delete_data_for_users($approvedlist);

        // After deletion the bigbluebuttonbn logs for the first user should have been deleted.
        $count = $DB->count_records('bigbluebuttonbn_logs',
            ['bigbluebuttonbnid' => $e['instance']->id, 'userid' => $e['users'][0]->id]);
        $this->assertEquals(0, $count);

        // Check the logs for the other user is still there.
        $count = $DB->count_records('bigbluebuttonbn_logs',
            ['bigbluebuttonbnid' => $e['instance']->id, 'userid' => $e['users'][1]->id]);
        $this->assertEquals(1, $count);
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
        $this->create_bigbluebuttonbn_log($e['instance'], $e['users'][0]);
        $this->create_bigbluebuttonbn_log($e['instance'], $e['users'][1]);

        return $e;
    }

    /**
     * Mimicks the creation of an bigbluebuttonbn log.
     *
     * @param object $bigbluebuttonbn
     * @param object $user
     */
    protected function create_bigbluebuttonbn_log($bigbluebuttonbn, $user) {
        $overrides = [
            'meetingid' => sha1($bigbluebuttonbn->meetingid) . '-' . $bigbluebuttonbn->course . '-' . $bigbluebuttonbn->id,
            'userid' => $user->id
        ];
        bigbluebuttonbn_log($bigbluebuttonbn, BIGBLUEBUTTONBN_LOG_EVENT_CREATE, $overrides);
    }
}
