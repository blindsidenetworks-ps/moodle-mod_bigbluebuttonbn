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

namespace mod_bigbluebuttonbn;

use calendar_event;
use context_module;
use mod_bigbluebuttonbn\test\testcase_helper_trait;
use mod_bigbluebuttonbn_mod_form;
use MoodleQuickForm;
use navigation_node;
use ReflectionClass;
use stdClass;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/bigbluebuttonbn/lib.php');

/**
 * BBB Library tests class.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
class lib_test extends \advanced_testcase {
    use testcase_helper_trait;

    /**
     * Check support
     *
     * @covers ::bigbluebuttonbn_supports
     */
    public function test_bigbluebuttonbn_supports() {
        $this->resetAfterTest();
        $this->assertTrue(bigbluebuttonbn_supports(FEATURE_IDNUMBER));
        $this->assertTrue(bigbluebuttonbn_supports(FEATURE_MOD_INTRO));
        $this->assertFalse(bigbluebuttonbn_supports(FEATURE_GRADE_HAS_GRADE));
    }

    /**
     * Check add instance
     *
     * @covers ::bigbluebuttonbn_add_instance
     */
    public function test_bigbluebuttonbn_add_instance() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $bbformdata = $this->get_form_data_from_instance($bbactivity);
        $id = bigbluebuttonbn_add_instance($bbformdata);
        $this->assertNotNull($id);
    }

    /**
     * Check update instance
     *
     * @covers ::bigbluebuttonbn_update_instance
     */
    public function test_bigbluebuttonbn_update_instance() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $bbformdata = $this->get_form_data_from_instance($bbactivity);
        $result = bigbluebuttonbn_update_instance($bbformdata);
        $this->assertTrue($result);
    }

    /**
     * Check delete instance
     *
     * @covers ::bigbluebuttonbn_delete_instance
     */
    public function test_bigbluebuttonbn_delete_instance() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $result = bigbluebuttonbn_delete_instance($bbactivity->id);
        $this->assertTrue($result);
    }

    /**
     * Check user outline page
     *
     * @covers ::bigbluebuttonbn_user_outline
     */
    public function test_bigbluebuttonbn_user_outline() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $this->setUser($user);

        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();

        $result = bigbluebuttonbn_user_outline($this->get_course(), $user, null, $bbactivity);
        $this->assertEquals('', $result);

        // Now create a couple of logs.
        $instance = instance::get_from_instanceid($bbactivity->id);
        logger::log_meeting_joined_event($instance, 0);
        logger::log_recording_played_event($instance, 1);

        $result = bigbluebuttonbn_user_outline($this->get_course(), $user, null, $bbactivity);
        $this->assertMatchesRegularExpression('/.* has joined the session for 2 times/', $result);
    }

    /**
     * Check user completion
     *
     * @covers ::bigbluebuttonbn_user_complete
     */
    public function test_bigbluebuttonbn_user_complete() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $this->setUser($user);

        // Now create a couple of logs.
        $instance = instance::get_from_instanceid($bbactivity->id);
        logger::log_meeting_joined_event($instance, 0);
        logger::log_recording_played_event($instance, 1);

        $result = bigbluebuttonbn_user_complete($this->get_course(), $user, $bbactivity);
        $this->assertEquals(2, $result);
    }

    /**
     * Check extra capabilities return value
     *
     * @covers ::bigbluebuttonbn_get_extra_capabilities
     */
    public function test_bigbluebuttonbn_get_extra_capabilities() {
        $this->resetAfterTest();
        $this->assertEquals(['moodle/site:accessallgroups'], bigbluebuttonbn_get_extra_capabilities());
    }

    /**
     * Check form definition
     *
     * @covers ::bigbluebuttonbn_reset_course_form_definition
     */
    public function test_bigbluebuttonbn_reset_course_form_definition() {
        global $CFG, $PAGE;
        $this->initialise_mock_server();

        $PAGE->set_course($this->get_course());
        $this->setAdminUser();
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        include_once($CFG->dirroot . '/mod/bigbluebuttonbn/mod_form.php');
        $data = new stdClass();
        $data->instance = $bbactivity;
        $data->id = $bbactivity->id;
        $data->course = $bbactivity->course;

        $form = new mod_bigbluebuttonbn_mod_form($data, 1, $bbactivitycm, $this->get_course());
        $refclass = new ReflectionClass("mod_bigbluebuttonbn_mod_form");
        $formprop = $refclass->getProperty('_form');
        $formprop->setAccessible(true);

        /* @var $mform MoodleQuickForm quickform object definition */
        $mform = $formprop->getValue($form);
        bigbluebuttonbn_reset_course_form_definition($mform);
        $this->assertNotNull($mform->getElement('bigbluebuttonbnheader'));
    }

    /**
     * Check defaults for form
     *
     * @covers ::bigbluebuttonbn_reset_course_form_defaults
     */
    public function test_bigbluebuttonbn_reset_course_form_defaults() {
        global $CFG;
        $this->resetAfterTest();
        $results = bigbluebuttonbn_reset_course_form_defaults($this->get_course());
        $this->assertEquals(array(
            'reset_bigbluebuttonbn_events' => 0,
            'reset_bigbluebuttonbn_tags' => 0,
            'reset_bigbluebuttonbn_logs' => 0,
            'reset_bigbluebuttonbn_recordings' => 0,
        ), $results);
    }

    /**
     * Check user data
     *
     * @covers ::bigbluebuttonbn_reset_userdata
     */
    public function test_bigbluebuttonbn_reset_userdata() {
        global $CFG;
        $this->resetAfterTest();
        $data = new stdClass();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $data->courseid = $this->get_course()->id;
        $data->reset_bigbluebuttonbn_tags = true;
        $data->reset_bigbluebuttonbn_tags = true;
        $data->course = $bbactivity->course;
        $results = bigbluebuttonbn_reset_userdata($data);
        $this->assertEquals([
            'component' => 'BigBlueButton',
            'item' => 'Deleted tags',
            'error' => false
        ],
            $results[0]
        );
    }

    /**
     * Check course module
     *
     * @covers ::bigbluebuttonbn_get_coursemodule_info
     */
    public function test_bigbluebuttonbn_get_coursemodule_info() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $info = bigbluebuttonbn_get_coursemodule_info($bbactivitycm);
        $this->assertEquals($info->name, $bbactivity->name);
    }

    /**
     * Check update since
     *
     * @covers ::bigbluebuttonbn_check_updates_since
     */
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

    /**
     * Check font awesome icon map
     *
     * @covers ::mod_bigbluebuttonbn_get_fontawesome_icon_map
     */
    public function test_mod_bigbluebuttonbn_get_fontawesome_icon_map() {
        $this->resetAfterTest();
        $this->assertEquals(['mod_bigbluebuttonbn:icon' => 'icon-bigbluebutton'],
            mod_bigbluebuttonbn_get_fontawesome_icon_map());
    }

    /**
     * Check event action (calendar)
     *
     * @covers ::mod_bigbluebuttonbn_core_calendar_provide_event_action
     */
    public function test_mod_bigbluebuttonbn_core_calendar_provide_event_action() {
        global $DB;
        $this->initialise_mock_server();
        $this->resetAfterTest();
        $this->setAdminUser();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();

        // Standard use case, the meeting start and we want add an action event to join the meeting.
        $event = $this->create_action_event($this->get_course(), $bbactivity, logger::EVENT_MEETING_START);
        $factory = new \core_calendar\action_factory();
        $actionevent = mod_bigbluebuttonbn_core_calendar_provide_event_action($event, $factory);
        $this->assertEquals("Join session", $actionevent->get_name());

        // User has already joined the meeting (there is log event EVENT_JOIN already for this user).
        $instance = instance::get_from_instanceid($bbactivity->id);
        logger::log_meeting_joined_event($instance, 0);

        $bbactivity->closingtime = time() - 1000;
        $bbactivity->openingtime = time() - 2000;
        $DB->update_record('bigbluebuttonbn', $bbactivity);
        $event = $this->create_action_event($this->get_course(), $bbactivity, logger::EVENT_MEETING_START);
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
     *
     * @covers ::bigbluebuttonbn_extend_settings_navigation
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

    /**
     * Check additional setting menu
     *
     * @covers ::bigbluebuttonbn_extend_settings_navigation
     */
    public function test_bigbluebuttonbn_extend_settings_navigation_user() {
        global $PAGE, $CFG;
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $user = $generator->create_user();
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
