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

    protected function require_mock_server(): void {
        if (!defined('TEST_MOD_BIGBLUEBUTTONBN_MOCK_SERVER')) {
            $this->markTestSkipped(
                'The TEST_MOD_BIGBLUEBUTTONBN_MOCK_SERVER constant must be defined to run mod_bigbluebuttonbn tests'
            );
        }
    }
}