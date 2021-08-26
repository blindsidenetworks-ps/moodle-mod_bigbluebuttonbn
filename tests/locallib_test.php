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
 * Local library tests class.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');

/**
 * Local library tests class.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
class mod_bigbluebuttonbn_locallib_testcase extends advanced_testcase {
    /**
     * Clean the temporary mocked up recordings
     *
     * @throws coding_exception
     */
    public function tearDown(): void {
        parent::tearDown();
        $this->getDataGenerator()->get_plugin_generator('mod_bigbluebuttonbn')
            ->bigbluebuttonbn_clean_recordings_array_fetch();
    }

    /**
     * Test for provider::get_metadata().
     */
    public function test_bigbluebuttonbn_get_recording_type_text() {
        $this->resetAfterTest(true);
        $this->assertEquals('Presentation', bigbluebuttonbn_get_recording_type_text('presentation'));
        $this->assertEquals('Video', bigbluebuttonbn_get_recording_type_text('video'));
        $this->assertEquals('Videos', bigbluebuttonbn_get_recording_type_text('videos'));
        $this->assertEquals('Whatever', bigbluebuttonbn_get_recording_type_text('whatever'));
        $this->assertEquals('Whatever It Can Be', bigbluebuttonbn_get_recording_type_text('whatever it can be'));
    }

    public function test_bigbluebuttonbn_get_users_select_ordered() {
        $this->resetAfterTest();
        $numstudents = 12;
        $numteachers = 3;
        $groupsnum = 3;

        list($course, $groups, $students, $teachers, $bbactivity, $roleids) =
            $this->setup_course_students_teachers(
                ['enablecompletion' => true, 'groupmode' => strval(SEPARATEGROUPS), 'groupmodeforce' => 1],
                $numstudents, $numteachers, $groupsnum);
        $context = context_course::instance($course->id);
        // Prevent access all groups.
        role_change_permission($roleids['teacher'], $context, 'moodle/site:accessallgroups', CAP_PREVENT);
        $this->setUser($teachers[0]);
        $users = bigbluebuttonbn_get_users_select($context, $bbactivity);
        $usernames = array_map(function($u) {
            return $u['name'];
        }, $users);
        $usernamessorted = $usernames;
        sort($usernamessorted, SORT_NATURAL);

        $this->assertEquals(
            $usernamessorted,
            array_values($usernames)
        );
        // Then check that the array kept the indexes.
        foreach ($users as $k => $u) {
            $this->assertEquals($k, $u['id']);
        }
    }

    public function test_bigbluebuttonbn_get_users_select_separate_groups_prevent_all() {
        $this->resetAfterTest();
        $numstudents = 12;
        $numteachers = 3;
        $groupsnum = 3;

        list($course, $groups, $students, $teachers, $bbactivity, $roleids) =
            $this->setup_course_students_teachers(
                ['enablecompletion' => true, 'groupmode' => strval(SEPARATEGROUPS), 'groupmodeforce' => 1],
                $numstudents, $numteachers, $groupsnum);
        $context = context_course::instance($course->id);
        // Prevent access all groups.
        role_change_permission($roleids['teacher'], $context, 'moodle/site:accessallgroups', CAP_PREVENT);
        $this->setUser($teachers[0]);
        $users = bigbluebuttonbn_get_users_select($context, $bbactivity);
        $this->assertCount(($numstudents + $numteachers) / $groupsnum, $users);
        $this->setUser($teachers[1]);
        $users = bigbluebuttonbn_get_users_select($context, $bbactivity);
        $this->assertCount(($numstudents + $numteachers) / $groupsnum, $users);
        $this->setUser($teachers[2]);
        $users = bigbluebuttonbn_get_users_select($context, $bbactivity);
        $this->assertCount(($numstudents + $numteachers) / $groupsnum, $users);
        $course->groupmode = strval(SEPARATEGROUPS);
        $course->groupmodeforce = "0";
        update_course($course);
        $this->setUser($teachers[2]);
        $users = bigbluebuttonbn_get_users_select($context, $bbactivity);
        $this->assertCount($numstudents + $numteachers, $users);

    }

    public function test_bigbluebuttonbn_get_users_select_separate_groups() {
        $this->resetAfterTest();
        $numstudents = 12;
        $numteachers = 3;
        $groupsnum = 3;
        list($course, $groups, $students, $teachers, $bbactivity, $roleids) =
            $this->setup_course_students_teachers(
                ['enablecompletion' => true, 'groupmode' => strval(VISIBLEGROUPS), 'groupmodeforce' => 1],
                $numstudents, $numteachers, $groupsnum);

        $context = context_course::instance($course->id);
        $this->setUser($teachers[0]);
        $users = bigbluebuttonbn_get_users_select($context, $bbactivity);
        $this->assertCount($numstudents + $numteachers, $users);
        $this->setUser($teachers[1]);
        $users = bigbluebuttonbn_get_users_select($context, $bbactivity);
        $this->assertCount($numstudents + $numteachers, $users);
        $this->setUser($teachers[1]);
        $users = bigbluebuttonbn_get_users_select($context, $bbactivity);
        $this->assertCount($numstudents + $numteachers, $users);
    }

    public function test_bigbluebuttonbn_get_role() {
        $this->resetAfterTest();
        $numstudents = 12;
        $numteachers = 3;
        $groupsnum = 3;
        list($course, $groups, $students, $teachers, $bbactivity, $roleids) =
            $this->setup_course_students_teachers(
                ['enablecompletion' => true,
                    'groupmode' => strval(VISIBLEGROUPS),
                    'groupmodeforce' => 1],
                $numstudents, $numteachers, $groupsnum);

        $context = context_course::instance($course->id);
        $this->setUser($teachers[0]);
        $roles = bigbluebuttonbn_get_roles_select($context);
        $this->assertTrue(count($roles) >= 4);
        // Then check that the array kept the indexes.
        foreach ($roles as $k => $r) {
            $this->assertEquals($k, $r['id']);
        }
    }

    /**
     * Generate a course, several students and several groups
     *
     * @param object $courserecord
     * @param int $numstudents
     * @param int $numteachers
     * @param int $groupsnum
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    protected function setup_course_students_teachers($courserecord, $numstudents, $numteachers, $groupsnum) {
        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course($courserecord);
        $groups = [];
        for ($i = 0; $i < $groupsnum; $i++) {
            $groups[] = $generator->create_group(array('courseid' => $course->id));
        }
        $group1 = $generator->create_group(array('courseid' => $course->id));
        $group2 = $generator->create_group(array('courseid' => $course->id));

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
}

