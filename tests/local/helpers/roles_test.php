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
namespace mod_bigbluebuttonbn\local\helpers;
defined('MOODLE_INTERNAL') || die();

use context_course;
use core_tag_tag;
use mod_bigbluebuttonbn\local\bbb_constants;

global $CFG;
require_once($CFG->dirroot . '/mod/bigbluebuttonbn/tests/helpers.php');

/**
 * BBB Library tests class.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
class roles_test extends \bbb_simple_test {
    /**
     * Test select separate group prevent all
     *
     */
    public function test_bigbluebuttonbn_get_users_select_separate_groups_prevent_all() {
        $this->resetAfterTest();
        $numstudents = 12;
        $numteachers = 3;
        $groupsnum = 3;

        list($course, $groups, $students, $teachers, $bbactivity, $roleids) =
            $this->setup_course_students_teachers(
                (object) ['enablecompletion' => true, 'groupmode' => strval(SEPARATEGROUPS), 'groupmodeforce' => 1],
                $numstudents, $numteachers, $groupsnum);
        $context = context_course::instance($course->id);
        // Prevent access all groups.
        role_change_permission($roleids['teacher'], $context, 'moodle/site:accessallgroups', CAP_PREVENT);
        $this->setUser($teachers[0]);
        $users = roles::bigbluebuttonbn_get_users_select($context, $bbactivity);
        $this->assertCount(($numstudents + $numteachers) / $groupsnum, $users);
        $this->setUser($teachers[1]);
        $users = roles::bigbluebuttonbn_get_users_select($context, $bbactivity);
        $this->assertCount(($numstudents + $numteachers) / $groupsnum, $users);
        $this->setUser($teachers[2]);
        $users = roles::bigbluebuttonbn_get_users_select($context, $bbactivity);
        $this->assertCount(($numstudents + $numteachers) / $groupsnum, $users);
        $course->groupmode = strval(SEPARATEGROUPS);
        $course->groupmodeforce = "0";
        update_course($course);
        $this->setUser($teachers[2]);
        $users = roles::bigbluebuttonbn_get_users_select($context, $bbactivity);
        $this->assertCount($numstudents + $numteachers, $users);

    }

    /**
     * Test select separate groups
     *
     */
    public function test_bigbluebuttonbn_get_users_select_separate_groups() {
        $this->resetAfterTest();
        $numstudents = 12;
        $numteachers = 3;
        $groupsnum = 3;
        list($course, $groups, $students, $teachers, $bbactivity, $roleids) =
            $this->setup_course_students_teachers(
                (object)['enablecompletion' => true, 'groupmode' => strval(VISIBLEGROUPS), 'groupmodeforce' => 1],
                $numstudents, $numteachers, $groupsnum);

        $context = context_course::instance($course->id);
        $this->setUser($teachers[0]);
        $users = roles::bigbluebuttonbn_get_users_select($context, $bbactivity);
        $this->assertCount($numstudents + $numteachers, $users);
        $this->setUser($teachers[1]);
        $users = roles::bigbluebuttonbn_get_users_select($context, $bbactivity);
        $this->assertCount($numstudents + $numteachers, $users);
        $this->setUser($teachers[1]);
        $users = roles::bigbluebuttonbn_get_users_select($context, $bbactivity);
        $this->assertCount($numstudents + $numteachers, $users);
    }


}


