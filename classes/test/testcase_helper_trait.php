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

namespace mod_bigbluebuttonbn\test;

use context_module;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\meeting;
use stdClass;
use testing_data_generator;

/**
 * BBB Library tests class trait.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
trait testcase_helper_trait {
    /** @var testing_data_generator|null */
    protected $generator = null;

    /** @var object|null */
    protected $course = null;

    /**
     * Convenience function to create a instance of an bigbluebuttonactivty.
     *
     * @param object|null $course course to add the module to
     * @param array $params Array of parameters to pass to the generator
     * @param array $options Array of options to pass to the generator
     * @return array($context, $cm, $instance) Testable wrapper around the assign class.
     * @throws \moodle_exception
     */
    protected function create_instance($course = null, $params = [], $options = []) {
        if (!$course) {
            $course = $this->get_course();
        }
        $params['course'] = $course->id;
        $options['visible'] = 1;
        $instance = $this->getDataGenerator()->create_module('bigbluebuttonbn', $params, $options);
        list($course, $cm) = get_course_and_cm_from_instance($instance, 'bigbluebuttonbn');
        $context = context_module::instance($cm->id);

        return [$context, $cm, $instance];
    }

    /**
     * Get the matching form data
     *
     * @param object $bbactivity the current bigbluebutton activity
     * @param object|null $course the course or null (taken from $this->get_course() if null)
     * @return mixed
     */
    protected function get_form_data_from_instance($bbactivity, $course = null) {
        global $USER;

        if (!$course) {
            $course = $this->get_course();
        }
        $this->setAdminUser();
        $bbactivitycm = get_coursemodule_from_instance('bigbluebuttonbn', $bbactivity->id);
        list($cm, $context, $module, $data, $cw) = get_moduleinfo_data($bbactivitycm, $course);
        $this->setUser($USER);
        return $data;
    }

    /**
     * Get or create course if it does not exist
     *
     * @return object|stdClass|null
     */
    protected function get_course() {
        if (!$this->course) {
            $this->course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        }
        return $this->course;
    }

    /**
     * Generate a course, several students and several groups
     *
     * @param object $courserecord
     * @param int $numstudents
     * @param int $numteachers
     * @param int $groupsnum
     * @return array
     */
    protected function setup_course_students_teachers($courserecord, $numstudents, $numteachers, $groupsnum) {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course($courserecord);
        $groups = [];
        for ($i = 0; $i < $groupsnum; $i++) {
            $groups[] = $generator->create_group(array('courseid' => $course->id));
        }
        $generator->create_group(array('courseid' => $course->id));
        $generator->create_group(array('courseid' => $course->id));

        $roleids = $DB->get_records_menu('role', null, '', 'shortname, id');

        $students = [];
        for ($i = 0; $i < $numstudents; $i++) {
            $student = $generator->create_user();
            $generator->enrol_user($student->id, $course->id, $roleids['student']);
            $groupid = $groups[$i % $groupsnum]->id;
            groups_add_member($groupid, $student->id);
            $students[] = $student;
        }

        $teachers = [];
        for ($i = 0; $i < $numteachers; $i++) {
            $teacher = $generator->create_user();
            $generator->enrol_user($teacher->id, $course->id, $roleids['teacher']);
            $groupid = $groups[$i % $groupsnum]->id;
            groups_add_member($groupid, $teacher->id);
            $teachers[] = $teacher;
        }
        $bbactivity = $generator->create_module(
            'bigbluebuttonbn',
            array('course' => $course->id),
            ['visible' => true]);

        get_fast_modinfo(0, 0, true);
        return array($course, $groups, $students, $teachers, $bbactivity, $roleids);
    }

    /**
     * This test requires mock server to be present.
     */
    protected function require_mock_server(): void {
        if (!defined('TEST_MOD_BIGBLUEBUTTONBN_MOCK_SERVER')) {
            $this->markTestSkipped(
                'The TEST_MOD_BIGBLUEBUTTONBN_MOCK_SERVER constant must be defined to run mod_bigbluebuttonbn tests'
            );
        }
    }

    /**
     * Create an return an array of recordings
     *
     * @param instance $instance
     * @param array $recordingdata array of recording information
     * @return array
     * @throws \coding_exception
     */
    protected function create_recordings_for_instance($instance, $recordingdata = []) {
        $recordings = [];
        $bbbgenerator = $this->getDataGenerator()->get_plugin_generator('mod_bigbluebuttonbn');
        // Create the meetings on the mock server, so like this we can find the recordings.
        $meeting = new meeting($instance);
        if (!$meeting->is_running()) {
            $bbbgenerator->create_meeting([
                'instanceid' => $instance->get_instance_id(),
                'groupid' => $instance->get_group_id()

            ]);
        }
        foreach ($recordingdata as $rindex => $data) {
            $recordings[] = $bbbgenerator->create_recording(
                array_merge([
                    'bigbluebuttonbnid' => $instance->get_instance_id(),
                    'groupid' => $instance->get_group_id()
                ], $data)
            );
        }
        return $recordings;
    }

    /**
     * Create an activity which includes a set of recordings.
     *
     * @param stdClass $course
     * @param int $type
     * @param array $recordingdata array of recording information
     * @param int $groupid
     * @return array
     */
    protected function create_activity_with_recordings($course, int $type, array $recordingdata, $groupid = 0): array {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bigbluebuttonbn');

        $activity = $generator->create_instance([
            'course' => $course->id,
            'type' => $type
        ]);

        $instance = instance::get_from_instanceid($activity->id);
        $instance->set_group_id($groupid);
        $recordings = $this->create_recordings_for_instance($instance, $recordingdata);
        return [
            'course' => $course,
            'activity' => $activity,
            'recordings' => $recordings,
        ];
    }

    /**
     * Create a course, users and recording from dataset given in an array form
     *
     * @param array $dataset
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function create_from_dataset($dataset) {
        list('type' => $type, 'recordingsdata' => $recordingsdata, 'groups' => $groups, 'users' => $users) = $dataset;
        $plugingenerator = $this->getDataGenerator()->get_plugin_generator('mod_bigbluebuttonbn');

        $coursedata = empty($groups) ? [] : ['groupmodeforce' => true, 'groupmode' => VISIBLEGROUPS];
        $this->course = $this->getDataGenerator()->create_course($coursedata);

        foreach ($users as $userdata) {
            $this->getDataGenerator()->create_and_enrol($this->course, $userdata['role'], ['username' => $userdata['username']]);
        }

        if ($groups) {
            foreach ($groups as $groupname => $students) {
                $group = $this->getDataGenerator()->create_group(['name' => $groupname, 'courseid' => $this->course->id]);
                foreach ($students as $username) {
                    $user = \core_user::get_user_by_username($username);
                    $this->getDataGenerator()->create_group_member(['userid' => $user->id, 'groupid' => $group->id]);
                }
            }
        }
        $activity = $plugingenerator->create_instance([
            'course' => $this->course->id,
            'type' => $type,
            'name' => 'Example'
        ]);
        $instance = instance::get_from_instanceid($activity->id);
        foreach ($recordingsdata as $groupname => $recordings) {
            if ($groups) {
                $groupid = groups_get_group_by_name($this->course->id, $groupname);
                $instance->set_group_id($groupid);
            }
            $this->create_recordings_for_instance($instance, $recordings);
        }
        return $activity->id;
    }

    /**
     * Prepare a set of recordings with logs so it can be tested in the upgrade/log process
     *
     * @param int $numstudents
     * @param int $numteachers
     * @param int $numgroups
     * @return array
     * @throws \coding_exception
     */
    protected function prepare_recordings_with_logs($numstudents = 1, $numteachers = 1, $numgroups = 2) {
        list($course, $groups, $students, $teachers, $bbbinstance, $roleids) =
            $this->setup_course_students_teachers((object)
            ['groupmode' => strval(VISIBLEGROUPS), 'groupmodeforce' => 1], $numstudents, $numteachers, $numgroups);

        $baselogdata = [
            'courseid' => $course->id,
            'bigbluebuttonbnid' => $bbbinstance->id,
            'userid' => $teachers[0]->id,
            'timecreated' => '1613150758',
            'log' => 'Create',
            'meta' => '{"record":true}'
        ];
        $bbbgenerator = $this->getDataGenerator()->get_plugin_generator('mod_bigbluebuttonbn');
        // Then create recordings and logs for each group.
        $instance = instance::get_from_instanceid($bbbinstance->id);
        $groupsid = array_map(function($el) {
            return $el->id;
        }, $groups);
        array_push($groupsid, 0);
        $recordings = [];
        foreach ($groupsid as $groupid) {
            $instance->set_group_id($groupid);
            $recordings = array_merge($recordings,
                $this->create_recordings_for_instance($instance, [['name' => 'Recording for group' . $groupid]]));
            $baselogdata['meetingid'] = $instance->get_meeting_id();
            $logs[] = $bbbgenerator->create_log($baselogdata);
        }
        return compact(
            'course',
            'groups',
            'students',
            'recordings',
            'teachers',
            'instance',
            'logs'

        );
    }
}
