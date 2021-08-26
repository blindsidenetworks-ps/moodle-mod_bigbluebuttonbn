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
use mod_bigbluebuttonbn\logger;
use mod_bigbluebuttonbn\test\testcase_helper;
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
class lib_test extends testcase_helper {

    public function test_bigbluebuttonbn_supports() {
        $this->resetAfterTest();
        $this->assertTrue(bigbluebuttonbn_supports(FEATURE_IDNUMBER));
        $this->assertTrue(bigbluebuttonbn_supports(FEATURE_MOD_INTRO));
        $this->assertFalse(bigbluebuttonbn_supports(FEATURE_GRADE_HAS_GRADE));
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
        $instance = instance::get_from_instanceid($bbactivity->id);
        logger::log_meeting_joined_event($instance, 0);
        logger::log_recording_played_event($instance, 1);

        $result = bigbluebuttonbn_user_outline($this->course, $user, null, $bbactivity);
        $this->assertMatchesRegularExpression('/.* has joined the session for 2 times/', $result);
    }

    public function test_bigbluebuttonbn_user_complete() {
        $this->resetAfterTest();
        $user = $this->generator->create_user();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $this->setUser($user);

        // Now create a couple of logs.
        $instance = instance::get_from_instanceid($bbactivity->id);
        logger::log_meeting_joined_event($instance, 0);
        logger::log_recording_played_event($instance, 1);

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
        $event = $this->create_action_event($this->course, $bbactivity, logger::EVENT_MEETING_START);
        $factory = new \core_calendar\action_factory();
        $actionevent = mod_bigbluebuttonbn_core_calendar_provide_event_action($event, $factory);
        $this->assertEquals("Join session", $actionevent->get_name());

        // User has already joined the meeting (there is log event EVENT_JOIN already for this user).
        $instance = instance::get_from_instanceid($bbactivity->id);
        logger::log_meeting_joined_event($instance, 0);

        $bbactivity->closingtime = time() - 1000;
        $bbactivity->openingtime = time() - 2000;
        $DB->update_record('bigbluebuttonbn', $bbactivity);
        $event = $this->create_action_event($this->course, $bbactivity, logger::EVENT_MEETING_START);
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
