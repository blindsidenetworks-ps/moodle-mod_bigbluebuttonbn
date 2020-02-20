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
class mod_bigbluebuttonbn_lib_testcase extends advanced_testcase {
    /**
     * @var testing_data_generator|null $generator
     */
    public $generator = null;
    /**
     * @var object|null $bbactivity
     */
    public $bbactivity = null;
    /**
     * @var object|null $course
     */
    public $course = null;

    /**
     * Convenience function to create a instance of an bigbluebuttonactivty.
     *
     * @param object|null $course course to add the module to
     * @param array $params Array of parameters to pass to the generator
     * @param array $options Array of options to pass to the generator
     * @return array($context, $cm, $instance) Testable wrapper around the assign class.
     * @throws coding_exception
     * @throws moodle_exception
     */
    protected function create_instance($course = null, $params = [], $options = []) {
        if (!$course) {
            $course = $this->course;
        }
        $params['course'] = $course->id;
        $options['visible'] = 1;
        $instance = $this->generator->create_module('bigbluebuttonbn', $params, $options);
        list($course, $cm) = get_course_and_cm_from_instance($instance, 'bigbluebuttonbn');
        $context = context_module::instance($cm->id);

        return array($context, $cm, $instance);
    }

    /**
     * Get the corresponding form data
     *
     * @param object $bbactivity the current bigbluebutton activity
     * @param object|null $course the course or null (taken from $this->course if null)
     * @return mixed
     * @throws coding_exception
     */
    protected function get_form_data_from_instance($bbactivity, $course = null) {
        global $USER;
        if (!$course) {
            $course = $this->course;
        }
        $currentuser = $USER;
        $this->setAdminUser();
        $bbactivitycm = get_coursemodule_from_instance('bigbluebuttonbn', $bbactivity->id);
        list($cm, $context, $module, $data, $cw) = get_moduleinfo_data($bbactivitycm, $course);
        $this->setUser($USER);
        return $data;
    }

    public function setUp() {
        global $CFG;
        parent::setUp();
        set_config('enablecompletion', true); // Enable completion for all tests.
        $this->generator = $this->getDataGenerator();
        $this->course = $this->generator->create_course(['enablecompletion' => 1]);
    }

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
        $result = bigbluebuttonbn_get_completion_state($this->course, $bbactivitycm, $user->id, COMPLETION_AND);
        $this->assertEquals(COMPLETION_AND, $result);

        list($bbactivitycontext, $bbactivitycm, $bbactivity) =
                $this->create_instance(null, ['completionattendance' => 1, 'completionengagementchats' => 1,
                        'completionengagementtalks' => 1]);

        // Add a couple of fake logs.
        $overrides = array('meetingid' => $bbactivity->meetingid);
        $meta = '{"origin":0, "data": {"duration": 120, "engagement": {"chats": 2, "talks":2} }}';
        bigbluebuttonbn_log($bbactivity, BIGBLUEBUTTON_LOG_EVENT_SUMMARY, $overrides, $meta);
        bigbluebuttonbn_log($bbactivity, BIGBLUEBUTTON_LOG_EVENT_SUMMARY, $overrides, $meta);
        $result = bigbluebuttonbn_get_completion_state($this->course, $bbactivitycm, $user->id, COMPLETION_AND);
        $this->assertEquals(COMPLETION_AND, $result);
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

    public function test_bigbluebuttonbn_delete_instance_log() {
        global $DB;
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        bigbluebuttonbn_delete_instance_log($bbactivity);
        $this->assertTrue($DB->record_exists('bigbluebuttonbn_logs', array('bigbluebuttonbnid' => $bbactivity->id,
                'log' => BIGBLUEBUTTONBN_LOG_EVENT_DELETE)));
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
        bigbluebuttonbn_log($bbactivity, BIGBLUEBUTTONBN_LOG_EVENT_JOIN, $overrides, $meta);
        bigbluebuttonbn_log($bbactivity, BIGBLUEBUTTONBN_LOG_EVENT_PLAYED, $overrides);
        $result = bigbluebuttonbn_user_outline($this->course, $user, null, $bbactivity);
        $this->assertRegExp('/.* has joined the session for 2 times/', $result);
    }

    public function test_bigbluebuttonbn_user_complete() {
        $this->resetAfterTest();
        $user = $this->generator->create_user();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $this->setUser($user);
        $overrides = array('meetingid' => $bbactivity->meetingid);
        $meta = '{"origin":0}';
        bigbluebuttonbn_log($bbactivity, BIGBLUEBUTTONBN_LOG_EVENT_JOIN, $overrides, $meta);
        bigbluebuttonbn_log($bbactivity, BIGBLUEBUTTONBN_LOG_EVENT_PLAYED, $overrides);
        $result = bigbluebuttonbn_user_complete($this->course, $user, $bbactivity);
        $this->assertEquals(2, $result);
    }

    public function test_bigbluebuttonbn_get_extra_capabilities() {
        $this->resetAfterTest();
        $this->assertEquals(array('moodle/site:accessallgroups'), bigbluebuttonbn_get_extra_capabilities());
    }

    public function test_bigbluebuttonbn_reset_course_items() {
        global $CFG;
        $this->resetAfterTest();
        $CFG->bigbluebuttonbn_recordings_enabled = false;
        $results = bigbluebuttonbn_reset_course_items();
        $this->assertEquals(array("events" => 0, "tags" => 0, "logs" => 0), $results);
        $CFG->bigbluebuttonbn_recordings_enabled = true;
        $results = bigbluebuttonbn_reset_course_items();
        $this->assertEquals(array("events" => 0, "tags" => 0, "logs" => 0, "recordings" => 0), $results);
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
                'component' => 'BigBlueButtonBN',
                'item' => 'Deleted tags',
                'error' => false,
        ), $results[0]);
    }

    public function test_bigbluebuttonbn_reset_getstatus() {
        $this->resetAfterTest();
        $result = bigbluebuttonbn_reset_getstatus('events');
        $this->assertEquals(array(
                'component' => 'BigBlueButtonBN',
                'item' => 'Deleted events',
                'error' => false,
        ), $result);
    }

    public function test_bigbluebuttonbn_reset_events() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance(
                null,
                ['openingtime' => time()]
        );
        $formdata = $this->get_form_data_from_instance($bbactivity);
        bigbluebuttonbn_process_post_save_event($formdata);
        $this->assertEquals(1, $DB->count_records(
                'event',
                array('modulename' => 'bigbluebuttonbn', 'courseid' => $this->course->id)));
        bigbluebuttonbn_reset_events($this->course->id);
        $this->assertEquals(0, $DB->count_records(
                'event',
                array('modulename' => 'bigbluebuttonbn', 'courseid' => $this->course->id)));
    }

    public function test_bigbluebuttonbn_reset_tags() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance(null,
                array('course' => $this->course->id),
                ['visible' => true]
        );
        core_tag_tag::add_item_tag('mod_bigbluebuttonbn', 'bbitem', $bbactivity->id, $bbactivitycontext, 'newtag');
        $alltags = core_tag_tag::get_item_tags('mod_bigbluebuttonbn', 'bbitem', $bbactivity->id);
        $this->assertCount(1, $alltags);
        bigbluebuttonbn_reset_tags($this->course->id);
        $alltags = core_tag_tag::get_item_tags('mod_bigbluebuttonbn', 'bbitem', $bbactivity->id);
        $this->assertCount(0, $alltags);
    }

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
        bigbluebuttonbn_log($bbactivity, BIGBLUEBUTTONBN_LOG_EVENT_JOIN, $overrides, $meta);

        bigbluebuttonbn_reset_logs($this->course->id);
        $this->assertEquals(0, $DB->count_records(
                'bigbluebuttonbn_logs',
                array('bigbluebuttonbnid' => $bbactivity->id, 'courseid' => $this->course->id)));
    }

    public function test_bigbluebuttonbn_reset_recordings() {
        $this->resetAfterTest();
        // TODO complete this test.
        $this->markTestSkipped(
                'For now this test relies on an API call so we need to mock the API CALL.'
        );
    }

    public function test_bigbluebuttonbn_get_view_actions() {
        $this->resetAfterTest();
        $this->assertEquals(array('view', 'view all'), bigbluebuttonbn_get_view_actions());
    }

    public function test_bigbluebuttonbn_get_post_actions() {
        $this->resetAfterTest();
        $this->assertEquals(array('update', 'add', 'delete'), bigbluebuttonbn_get_post_actions());
    }

    public function test_bigbluebuttonbn_print_overview() {
        $this->resetAfterTest();

        $this->setAdminUser(); // If not modules won't be visible.
        list($bbactivitycontext, $bbactivitycm, $bbactivity1) = $this->create_instance(null,
                array('course' => $this->course->id, 'openingtime' => time()),
                ['visible' => true]
        );

        list($bbactivitycontext, $bbactivitycm, $bbactivity2) = $this->create_instance(null,
                array('course' => $this->course->id, 'openingtime' => time()),
                ['visible' => true]
        );

        $htmlarray = [];
        bigbluebuttonbn_print_overview([$this->course->id => $this->course], $htmlarray);
        $this->assertRegExp("/BigBlueButtonBN (1|2)/", $htmlarray[$this->course->id]['bigbluebuttonbn']);
    }

    public function test_bigbluebuttonbn_print_overview_element() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        // We tweak the record as it should also contain all fields from the activity instance.
        /* @var cm_info $bbactivitycm */
        $cmrecord = (object) array_merge((array) $bbactivity, (array) $bbactivitycm->get_course_module_record());
        $cmrecord->coursemodule = $bbactivity->id;
        $str = bigbluebuttonbn_print_overview_element($cmrecord, time());
        $this->assertRegExp("/bigbluebuttonbn overview/", $str);
    }

    public function test_bigbluebuttonbn_get_coursemodule_info() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $info = bigbluebuttonbn_get_coursemodule_info($bbactivitycm);
        $this->assertEquals($info->name, $bbactivity->name);
    }

    public function test_mod_bigbluebuttonbn_get_completion_active_rule_descriptions() {
        $this->resetAfterTest();
        // Two activities, both with automatic completion. One has the 'completionsubmit' rule, one doesn't.
        // Inspired from the same test in forum.
        list($bbactivitycontext, $cm1, $bbactivity) = $this->create_instance($this->course,
                ['completion' => '2', 'completionsubmit' => '1']);
        list($bbactivitycontext, $cm2, $bbactivity) = $this->create_instance($this->course,
                ['completion' => '2', 'completionsubmit' => '0']);

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

        $activeruledescriptions = [get_string('completionsubmit', 'assign')];
        // TODO: check the return value here as there might be an issue with the function compared to the forum for example.
        /*
          $this->assertEquals(mod_bigbluebuttonbn_get_completion_active_rule_descriptions($cm1), $activeruledescriptions);
          $this->assertEquals(mod_bigbluebuttonbn_get_completion_active_rule_descriptions($moddefaults), $activeruledescriptions);
        */

        $this->assertEquals(mod_bigbluebuttonbn_get_completion_active_rule_descriptions($cm2), []);
        $this->assertEquals(mod_bigbluebuttonbn_get_completion_active_rule_descriptions(new stdClass()), []);

    }

    public function test_bigbluebuttonbn_process_pre_save() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $bbformdata = $this->get_form_data_from_instance($bbactivity);
        $bbformdata->participants = '<p>this -&gt; &quot;</p>\n';
        $bbformdata->timemodified = time();
        bigbluebuttonbn_process_pre_save($bbformdata);
        $this->assertTrue($bbformdata->timemodified != 0);
        $this->assertEquals('<p>this -> "</p>\n', $bbformdata->participants);
    }

    public function test_bigbluebuttonbn_process_pre_save_instance() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $bbformdata = $this->get_form_data_from_instance($bbactivity);
        $bbformdata->instance = 0;
        $bbformdata->timemodified = time();
        bigbluebuttonbn_process_pre_save_instance($bbformdata);
        $this->assertTrue($bbformdata->timemodified == 0);
    }

    public function test_bigbluebuttonbn_process_pre_save_checkboxes() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $bbformdata = $this->get_form_data_from_instance($bbactivity);
        unset($bbformdata->wait);
        unset($bbformdata->recordallfromstart);
        bigbluebuttonbn_process_pre_save_checkboxes($bbformdata);
        $this->assertTrue(isset($bbformdata->wait));
        $this->assertTrue(isset($bbformdata->recordallfromstart));
    }

    public function test_bigbluebuttonbn_process_pre_save_common() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');
        $this->resetAfterTest();

        list($bbactivitycontext, $bbactivitycm, $bbactivity) =
                $this->create_instance(null, ['type' => BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY]);
        $bbformdata = $this->get_form_data_from_instance($bbactivity);

        $bbformdata->groupmode = '1';
        bigbluebuttonbn_process_pre_save_common($bbformdata);
        $this->assertEquals(0, $bbformdata->groupmode);
    }

    public function test_bigbluebuttonbn_process_post_save() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');
        $this->resetAfterTest();

        list($bbactivitycontext, $bbactivitycm, $bbactivity) =
                $this->create_instance(null, ['type' => BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY]);
        $bbformdata = $this->get_form_data_from_instance($bbactivity);

        // Enrol users in a course so he will receive the message.
        $teacher = $this->generator->create_user(['role' => 'editingteacher']);
        $this->generator->enrol_user($teacher->id, $this->course->id);

        // Mark the form to trigger notification.
        $bbformdata->notification = true;
        $messagesink = $this->redirectMessages();
        bigbluebuttonbn_process_post_save($bbformdata);
        // Now run cron.
        ob_start();
        $this->runAdhocTasks();
        ob_get_clean(); // Suppress output as it can fail the test.
        $this->assertEquals(1, $messagesink->count());
    }

    public function test_bigbluebuttonbn_process_post_save_notification() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');
        $this->resetAfterTest();

        list($bbactivitycontext, $bbactivitycm, $bbactivity) =
                $this->create_instance(null, ['type' => BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY]);
        $bbformdata = $this->get_form_data_from_instance($bbactivity);
        $bbformdata->add = "1";
        $messagesink = $this->redirectMessages();
        // Enrol users in a course so he will receive the message.
        $teacher = $this->generator->create_user(['role' => 'editingteacher']);
        $this->generator->enrol_user($teacher->id, $this->course->id);

        bigbluebuttonbn_process_post_save_notification($bbformdata);
        // Now run cron.
        ob_start();
        $this->runAdhocTasks();
        ob_get_clean(); // Suppress output as it can fail the test.
        $this->assertEquals(1, $messagesink->count());
    }

    public function test_bigbluebuttonbn_process_post_save_event() {
        $this->resetAfterTest();
        $this->setAdminUser();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $eventsink = $this->redirectEvents();
        $bbformdata = $this->get_form_data_from_instance($bbactivity);
        $bbformdata->openingtime = time();
        bigbluebuttonbn_process_post_save_event($bbformdata);
        $this->assertNotEmpty($eventsink->get_events());
    }

    public function test_bigbluebuttonbn_process_post_save_completion() {
        $this->resetAfterTest();
        $this->setAdminUser();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $bbformdata = $this->get_form_data_from_instance($bbactivity);
        $eventsink = $this->redirectEvents();
        $bbformdata->completionexpected = 1;
        bigbluebuttonbn_process_post_save_completion($bbformdata);
        $this->assertNotEmpty($eventsink->get_events());
    }

    public function test_bigbluebuttonbn_get_media_file() {
        $this->resetAfterTest();
        $user = $this->generator->create_user();
        $this->setUser($user);
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $bbformdata = $this->get_form_data_from_instance($bbactivity);
        $mediafilepath = bigbluebuttonbn_get_media_file($bbformdata);
        $this->assertEmpty($mediafilepath);

        // From test_delete_original_file_from_draft (lib/test/filelib_test.php)
        // Create a bbb private file.
        $bbbfilerecord = new stdClass;
        $bbbfilerecord->contextid = context_module::instance($bbformdata->coursemodule)->id;
        $bbbfilerecord->component = 'mod_bigbluebuttonbn';
        $bbbfilerecord->filearea = 'presentation';
        $bbbfilerecord->itemid = 0;
        $bbbfilerecord->filepath = '/';
        $bbbfilerecord->filename = 'bbfile.pptx';
        $bbbfilerecord->source = 'test';
        $fs = get_file_storage();
        $bbbfile = $fs->create_file_from_string($bbbfilerecord, 'Presentation file content');
        file_prepare_draft_area($bbformdata->presentation,
                context_module::instance($bbformdata->coursemodule)->id,
                'mod_bigbluebuttonbn',
                'presentation', 0);

        $mediafilepath = bigbluebuttonbn_get_media_file($bbformdata);
        $this->assertEquals('/bbfile.pptx', $mediafilepath);
    }

    public function test_bigbluebuttonbn_pluginfile() {
        $this->resetAfterTest();
        $this->markTestSkipped(
                'For now this test on send file and it should be mocked to avoid the real API CALL.'
        );

        /*
            $mediafilepath = bigbluebuttonbn_pluginfile($this->course, $bbactivitycm, context_module::instance($bbactivitycm->id),
               'presentation', ['bbfile.pptx'], false, ['preview'=>true, 'dontdie'=>true]);
               $this->assertEquals('/bbfile.pptx', $mediafilepath);
        */
    }

    public function test_bigbluebuttonbn_pluginfile_valid() {
        $this->resetAfterTest();
        $this->assertFalse(bigbluebuttonbn_pluginfile_valid(context_course::instance($this->course->id), 'presentation'));
        $this->assertTrue(bigbluebuttonbn_pluginfile_valid(context_system::instance(), 'presentation'));
        $this->assertFalse(bigbluebuttonbn_pluginfile_valid(context_system::instance(), 'otherfilearea'));
    }

    public function test_bigbluebuttonbn_pluginfile_file() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $user = $this->generator->create_user();
        $this->setUser($user);
        $this->generator->enrol_user($user->id, $this->course->id, 'editingteacher');
        // From test_delete_original_file_from_draft (lib/test/filelib_test.php)
        // Create a bbb private file.
        $bbformdata = $this->get_form_data_from_instance($bbactivity);
        $context = context_module::instance($bbformdata->coursemodule);

        $bbbfilerecord = new stdClass;
        $bbbfilerecord->contextid = $context->id;
        $bbbfilerecord->component = 'mod_bigbluebuttonbn';
        $bbbfilerecord->filearea = 'presentation';
        $bbbfilerecord->itemid = 0;
        $bbbfilerecord->filepath = '/';
        $bbbfilerecord->filename = 'bbfile.pptx';
        $bbbfilerecord->source = 'test';
        $fs = get_file_storage();
        $bbbfile = $fs->create_file_from_string($bbbfilerecord, 'Presentation file content');
        file_prepare_draft_area($bbformdata->presentation,
                context_module::instance($bbformdata->coursemodule)->id,
                'mod_bigbluebuttonbn',
                'presentation', 0);
        list($course, $bbactivitycmuser) = get_course_and_cm_from_instance($bbactivity->id, 'bigbluebuttonbn');
        /** @var stored_file $mediafile */
        $mediafile = bigbluebuttonbn_pluginfile_file($this->course, $bbactivitycmuser, $context, 'presentation', ['bbfile.pptx']);
        $this->assertEquals('bbfile.pptx', $mediafile->get_filename());
    }

    public function test_bigbluebuttonbn_default_presentation_get_file() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $user = $this->generator->create_user();
        $this->setUser($user);
        $this->generator->enrol_user($user->id, $this->course->id, 'editingteacher');
        $bbformdata = $this->get_form_data_from_instance($bbactivity);
        // From test_delete_original_file_from_draft (lib/test/filelib_test.php)
        // Create a bbb private file.
        $context = context_module::instance($bbformdata->coursemodule);
        list($course, $bbactivitycmuser) = get_course_and_cm_from_instance($bbactivity->id, 'bigbluebuttonbn');
        $mediafile = bigbluebuttonbn_default_presentation_get_file($this->course, $bbactivitycmuser, $context, ['presentation'],
                '/bbfile.pptx');
        $this->assertEquals('presentation', $mediafile);
    }

    public function test_bigbluebuttonbn_pluginfile_filename() {
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $user = $this->generator->create_user();
        $this->setUser($user);
        $this->generator->enrol_user($user->id, $this->course->id, 'editingteacher');
        $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'presentation_cache');
        $noncekey = sha1($bbactivity->id);
        $presentationnonce = $cache->get($noncekey);
        $filename = bigbluebuttonbn_pluginfile_filename($this->course, $bbactivitycm, $bbactivitycontext,
                [$presentationnonce, 'bbfile.pptx']);
        $this->assertEquals('bbfile.pptx', $filename);
    }

    public function test_bigbluebuttonbn_get_file_areas() {
        $this->resetAfterTest();
        $this->assertEquals(array(
                'presentation' => 'Presentation content',
                'presentationdefault' => 'Presentation default content',
        ), bigbluebuttonbn_get_file_areas());
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
                '{"configuration":{"updated":false},"contentfiles":{"updated":false},"introfiles":{"updated":false},"completion":{"updated":false}}',
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
        $event = $this->create_action_event($this->course, $bbactivity, BIGBLUEBUTTON_EVENT_MEETING_START);
        $factory = new \core_calendar\action_factory();
        $actionevent = mod_bigbluebuttonbn_core_calendar_provide_event_action($event, $factory);
        $this->assertEquals("Join session", $actionevent->get_name());

        // User has already joined the meeting (there is log event BIGBLUEBUTTONBN_LOG_EVENT_JOIN already for this user).
        $overrides = array('meetingid' => $bbactivity->meetingid);
        $meta = '{"origin":0}';
        bigbluebuttonbn_log($bbactivity, BIGBLUEBUTTONBN_LOG_EVENT_JOIN, $overrides, $meta);
        $bbactivity->closingtime = time() - 1000;
        $bbactivity->openingtime = time() - 2000;
        $DB->update_record('bigbluebuttonbn', $bbactivity);
        $event = $this->create_action_event($this->course, $bbactivity, BIGBLUEBUTTON_EVENT_MEETING_START);
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
     * @throws coding_exception
     */
    private function create_action_event($course, $bbbactivity, $eventtype) {
        $event = new stdClass();
        $event->name = 'Calendar event';
        $event->modulename = 'bigbluebuttonbn';
        $event->courseid = $course->id;
        $event->instance = $bbbactivity->id;
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->eventtype = $eventtype;
        $event->timestart = time();

        return calendar_event::create($event);
    }

    public function test_bigbluebuttonbn_log() {
        global $DB;
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        bigbluebuttonbn_log($bbactivity, BIGBLUEBUTTONBN_LOG_EVENT_PLAYED);
        $this->assertTrue($DB->record_exists('bigbluebuttonbn_logs', array('bigbluebuttonbnid' => $bbactivity->id)));
    }

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


