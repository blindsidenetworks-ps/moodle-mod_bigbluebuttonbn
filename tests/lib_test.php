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

use mod_bigbluebuttonbn\completion\custom_completion;
use mod_bigbluebuttonbn\local\bbb_constants;
use mod_bigbluebuttonbn\local\helpers\logs;

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/mod/bigbluebuttonbn/tests/helpers.php');
require_once($CFG->dirroot . '/mod/bigbluebuttonbn/lib.php');

/**
 * BBB Library tests class.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
class lib_test extends \bbb_simple_test {

    public function test_bigbluebuttonbn_supports() {
        $this->resetAfterTest();
        $this->assertTrue(bigbluebuttonbn_supports(FEATURE_IDNUMBER));
        $this->assertTrue(bigbluebuttonbn_supports(FEATURE_MOD_INTRO));
        $this->assertFalse(bigbluebuttonbn_supports(FEATURE_GRADE_HAS_GRADE));
    }

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
        $result = $completion->get_overall_completion_state();
        $this->assertEquals(COMPLETION_COMPLETE, $result);
    }

    public function test_bigbluebuttonbn_add_instance() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $bbformdata = $this->get_form_data_from_instance($bbactivity);
        $id = bigbluebuttonbn_add_instance($bbformdata);
        $this->assertNotNull($id);
    }

    public function test_bigbluebuttonbn_update_instance() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $bbformdata = $this->get_form_data_from_instance($bbactivity);
        $result = bigbluebuttonbn_update_instance($bbformdata);
        $this->assertTrue($result);
    }

    public function test_bigbluebuttonbn_delete_instance() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $result = bigbluebuttonbn_delete_instance($bbactivity->id);
        $this->assertTrue($result);
    }

    public function test_bigbluebuttonbn_user_outline() {
        $this->resetAfterTest();
        $user = $this->generator->create_user();
        $this->setUser($user);
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $result = bigbluebuttonbn_user_outline($this->course, $user, null, $bbactivity);
        $this->assertEquals('', $result);

        // Now create a couple of logs.
        $overrides = array('meetingid' => $bbactivity->meetingid);
        $meta = '{"origin":0}';
        logs::bigbluebuttonbn_log($bbactivity, bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_JOIN, $overrides, $meta);
        logs::bigbluebuttonbn_log($bbactivity, bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_PLAYED, $overrides);
        $result = bigbluebuttonbn_user_outline($this->course, $user, null, $bbactivity);
        $this->assertMatchesRegularExpression('/.* has joined the session for 2 times/', $result);
    }

    public function test_bigbluebuttonbn_user_complete() {
        $this->resetAfterTest();
        $user = $this->generator->create_user();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $this->setUser($user);
        $overrides = array('meetingid' => $bbactivity->meetingid);
        $meta = '{"origin":0}';
        logs::bigbluebuttonbn_log($bbactivity, bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_JOIN, $overrides, $meta);
        logs::bigbluebuttonbn_log($bbactivity, bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_PLAYED, $overrides);
        $result = bigbluebuttonbn_user_complete($this->course, $user, $bbactivity);
        $this->assertEquals(2, $result);
    }

    public function test_bigbluebuttonbn_get_extra_capabilities() {
        $this->resetAfterTest();
        $this->assertEquals(array('moodle/site:accessallgroups'), bigbluebuttonbn_get_extra_capabilities());
    }

    public function test_bigbluebuttonbn_reset_course_form_definition() {
        global $CFG, $PAGE;
        $PAGE->set_course($this->course);
        $this->setAdminUser();
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        include_once($CFG->dirroot . '/mod/bigbluebuttonbn/mod_form.php');
        $data = new stdClass();
        $data->instance = $bbactivity;
        $data->id = $bbactivity->id;
        $data->course = $bbactivity->course;

        $form = new mod_bigbluebuttonbn_mod_form($data, 1, $bbactivitycm, $this->course);
        $refclass = new ReflectionClass("mod_bigbluebuttonbn_mod_form");
        $formprop = $refclass->getProperty('_form');
        $formprop->setAccessible(true);

        /* @var $mform MoodleQuickForm quickform object definition */
        $mform = $formprop->getValue($form);
        bigbluebuttonbn_reset_course_form_definition($mform);
        $this->assertNotNull($mform->getElement('bigbluebuttonbnheader'));
    }

    public function test_bigbluebuttonbn_reset_course_form_defaults() {
        global $CFG;
        $this->resetAfterTest();
        $results = bigbluebuttonbn_reset_course_form_defaults($this->course);
        $this->assertEquals(array(
            'reset_bigbluebuttonbn_events' => 0,
            'reset_bigbluebuttonbn_tags' => 0,
            'reset_bigbluebuttonbn_logs' => 0,
            'reset_bigbluebuttonbn_recordings' => 0,
        ), $results);
    }

    public function test_bigbluebuttonbn_reset_userdata() {
        global $CFG;
        $this->resetAfterTest();
        $data = new stdClass();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $data->courseid = $this->course->id;
        $data->reset_bigbluebuttonbn_tags = true;
        $data->reset_bigbluebuttonbn_tags = true;
        $data->course = $bbactivity->course;
        $results = bigbluebuttonbn_reset_userdata($data);
        $this->assertEquals(array(
            'component' => 'BigBlueButton',
            'item' => 'Deleted tags',
            'error' => false,
        ), $results[0]);
    }

    public function test_bigbluebuttonbn_get_coursemodule_info() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $info = bigbluebuttonbn_get_coursemodule_info($bbactivitycm);
        $this->assertEquals($info->name, $bbactivity->name);
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
            ], $completioncm2->get_custom_rule_descriptions());
    }

    public function test_bigbluebuttonbn_pluginfile() {
        $this->resetAfterTest();
        $this->markTestSkipped(
            'For now this test on send file and it should be mocked to avoid the real API CALL.'
        );
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

    public function test_bigbluebuttonbn_check_updates_since() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $result = bigbluebuttonbn_check_updates_since($bbactivitycm, 0);
        $this->assertEquals(
            '{"configuration":{"updated":false},"contentfiles":{"updated":false},"introfiles":' .
            '{"updated":false},"completion":{"updated":false}}',
            json_encode($result)
        );
    }

    public function test_mod_bigbluebuttonbn_get_fontawesome_icon_map() {
        $this->resetAfterTest();
        $this->assertEquals(array('mod_bigbluebuttonbn:icon' => 'icon-bigbluebutton'),
            mod_bigbluebuttonbn_get_fontawesome_icon_map());
    }

    public function test_mod_bigbluebuttonbn_core_calendar_provide_event_action() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();

        // Standard use case, the meeting start and we want add an action event to join the meeting.
        $event = $this->create_action_event($this->course, $bbactivity, bbb_constants::BIGBLUEBUTTON_EVENT_MEETING_START);
        $factory = new \core_calendar\action_factory();
        $actionevent = mod_bigbluebuttonbn_core_calendar_provide_event_action($event, $factory);
        $this->assertEquals("Join session", $actionevent->get_name());

        // User has already joined the meeting (there is log event BIGBLUEBUTTONBN_LOG_EVENT_JOIN already for this user).
        $overrides = array('meetingid' => $bbactivity->meetingid);
        $meta = '{"origin":0}';
        logs::bigbluebuttonbn_log($bbactivity, bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_JOIN, $overrides, $meta);
        $bbactivity->closingtime = time() - 1000;
        $bbactivity->openingtime = time() - 2000;
        $DB->update_record('bigbluebuttonbn', $bbactivity);
        $event = $this->create_action_event($this->course, $bbactivity, bbb_constants::BIGBLUEBUTTON_EVENT_MEETING_START);
        $actionevent = mod_bigbluebuttonbn_core_calendar_provide_event_action($event, $factory);
        $this->assertNull($actionevent);
    }

    /**
     * Creates an action event.
     *
     * @param \stdClass $course The course the bigbluebutton activity is in
     * @param object $bbbactivity The bigbluebutton activity to create an event for
     * @param string $eventtype The event type. eg. ASSIGN_EVENT_TYPE_DUE.
     * @return bool|calendar_event
     */
    private function create_action_event($course, $bbbactivity, $eventtype) {
        $event = new stdClass();
        $event->name = 'Calendar event';
        $event->modulename = 'bigbluebuttonbn';
        $event->courseid = $course->id;
        $event->instance = $bbbactivity->id;
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->priority = CALENDAR_EVENT_USER_OVERRIDE_PRIORITY;
        $event->eventtype = $eventtype;
        $event->timestart = time();

        return calendar_event::create($event);
    }

    /**
     * Test setting navigation admin menu
     */
    public function test_bigbluebuttonbn_extend_settings_navigation_admin() {
        global $PAGE, $CFG;
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $CFG->bigbluebuttonbn_meetingevents_enabled = true;

        $PAGE->set_cm($bbactivitycm);
        $PAGE->set_context(context_module::instance($bbactivitycm->id));
        $PAGE->set_url('/mod/bigbluebuttonbn/view.php', ['id' => $bbactivitycm->id]);
        $settingnav = $PAGE->settingsnav;

        $this->setAdminUser();
        $node = navigation_node::create('testnavigationnode');
        bigbluebuttonbn_extend_settings_navigation($settingnav, $node);
        $this->assertCount(1, $node->get_children_key_list());
    }

    public function test_bigbluebuttonbn_extend_settings_navigation_user() {
        global $PAGE, $CFG;
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $user = $this->generator->create_user();
        $this->setUser($user);
        list($course, $bbactivitycmuser) = get_course_and_cm_from_instance($bbactivity->id, 'bigbluebuttonbn');

        $CFG->bigbluebuttonbn_meetingevents_enabled = true;

        $PAGE->set_cm($bbactivitycmuser);
        $PAGE->set_context(context_module::instance($bbactivitycm->id));
        $PAGE->set_url('/mod/bigbluebuttonbn/view.php', ['id' => $bbactivitycm->id]);

        $settingnav = $PAGE->settingsnav;
        $node = navigation_node::create('testnavigationnode');
        bigbluebuttonbn_extend_settings_navigation($settingnav, $node);
        $this->assertCount(0, $node->get_children_key_list());
    }
}
