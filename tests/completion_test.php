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
 * Tests for Big Blue Button Completion.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
namespace mod_bigbluebuttonbn;

use completion_info;
use context_module;
use mod_bigbluebuttonbn\completion\custom_completion;
use mod_bigbluebuttonbn\local\bbb_constants;
use mod_bigbluebuttonbn\local\helpers\logs;
use mod_bigbluebuttonbn\test\testcase_helper;

/**
 * Tests for Big Blue Button Completion.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
class completion_test extends testcase_helper {
    /**
     * Test completion state
     *
     * TODO: split this test into bits and put in a local class
     * TODO: Make sure we test with an instance having completionattendance set to something > 0
     */
    public function test_bigbluebuttonbn_get_completion_state() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $user = $this->generator->create_user();
        $this->setUser($user);
        $completion = new custom_completion($bbactivitycm, $user->id);
        $result = $completion->get_overall_completion_state();
        // No custom rules so complete.
        $this->assertEquals(COMPLETION_COMPLETE, $result);

        // Now with a custom rule.
        list($bbactivitycontext, $bbactivitycm, $bbactivity) =
            $this->create_instance(null);
        $bbactivitycm->override_customdata('customcompletionrules', [
            'completionengagementchats' => '1',
            'completionattendance' => '1'
        ]);
        $completion = new custom_completion($bbactivitycm, $user->id);
        $result = $completion->get_overall_completion_state();
        $this->assertEquals(COMPLETION_INCOMPLETE, $result);
        // Add a couple of fake logs.
        $overrides = array('meetingid' => $bbactivity->meetingid);
        $meta = '{"origin":0, "data": {"duration": 120, "engagement": {"chats": 2, "talks":2} }}';
        logs::bigbluebuttonbn_log($bbactivity, bbb_constants::BIGBLUEBUTTON_LOG_EVENT_SUMMARY, $overrides, $meta);
        logs::bigbluebuttonbn_log($bbactivity, bbb_constants::BIGBLUEBUTTON_LOG_EVENT_SUMMARY, $overrides, $meta);
        // Now 2 x 120 mins of duration.
        $result = $completion->get_overall_completion_state();
        $this->assertEquals(COMPLETION_COMPLETE, $result);
    }

    public function test_mod_bigbluebuttonbn_get_completion_active_rule_descriptions() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        // Two activities, both with automatic completion. One has the 'completionsubmit' rule, one doesn't.
        // Inspired from the same test in forum.
        list($bbactivitycontext, $cm1, $bbactivity) = $this->create_instance($this->course,
            ['completion' => '2', 'completionattendance' => '1']);
        list($bbactivitycontext, $cm2, $bbactivity) = $this->create_instance($this->course,
            ['completion' => '2', 'completionattendance' => '0']);

        // Data for the stdClass input type.
        // This type of input would occur when checking the default completion rules for an activity type, where we don't have
        // any access to cm_info, rather the input is a stdClass containing completion and customdata attributes, just like cm_info.
        $moddefaults = (object) [
            'customdata' => [
                'customcompletionrules' => [
                    'completionsubmit' => '1',
                ],
            ],
            'completion' => 2,
        ];

        $completioncm1 = new custom_completion($cm1, $user->id);
        // TODO: check the return value here as there might be an issue with the function compared to the forum for example.
        $this->assertEquals(
            [
                'completionengagementchats' => get_string('completionengagementchatsdesc', 'mod_bigbluebuttonbn',
                    0),
                'completionengagementtalks' => get_string('completionengagementtalksdesc', 'mod_bigbluebuttonbn',
                    0),
                'completionattendance' => get_string('completionattendancedesc', 'mod_bigbluebuttonbn',
                    1),
                'completionengagementraisehand' => get_string('completionengagementraisehanddesc', 'mod_bigbluebuttonbn',
                    0),
                'completionengagementpollvotes' => get_string('completionengagementpollvotesdesc', 'mod_bigbluebuttonbn',
                    0),
                'completionengagementemojis' => get_string('completionengagementemojisdesc', 'mod_bigbluebuttonbn',
                    0)
            ],
            $completioncm1->get_custom_rule_descriptions());
        $completioncm2 = new custom_completion($cm2, $user->id);
        $this->assertEquals(
            [
                'completionengagementchats' => get_string('completionengagementchatsdesc', 'mod_bigbluebuttonbn',
                    0),
                'completionengagementtalks' => get_string('completionengagementtalksdesc', 'mod_bigbluebuttonbn',
                    0),
                'completionattendance' => get_string('completionattendancedesc', 'mod_bigbluebuttonbn',
                    0),
                'completionengagementraisehand' => get_string('completionengagementraisehanddesc', 'mod_bigbluebuttonbn',
                    0),
                'completionengagementpollvotes' => get_string('completionengagementpollvotesdesc', 'mod_bigbluebuttonbn',
                    0),
                'completionengagementemojis' => get_string('completionengagementemojisdesc', 'mod_bigbluebuttonbn',
                    0)
            ], $completioncm2->get_custom_rule_descriptions());
    }

    public function test_bigbluebuttonbn_view() {
        $this->resetAfterTest();
        $this->setAdminUser();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance([],
            array('completion' => 2, 'completionview' => 1));

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        bigbluebuttonbn_view($bbactivity, $this->course, $bbactivitycm, context_module::instance($bbactivitycm->id));

        $events = $sink->get_events();
        $this->assertCount(3, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_bigbluebuttonbn\event\activity_viewed', $event);
        $this->assertEquals($bbactivitycontext, $event->get_context());
        $url = new \moodle_url('/mod/bigbluebuttonbn/view.php', array('id' => $bbactivitycontext->instanceid));
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());

        // Check completion status.
        $completion = new completion_info($this->course);
        $completiondata = $completion->get_data($bbactivitycm);
        $this->assertEquals(1, $completiondata->completionstate);
    }
}
