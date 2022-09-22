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
 * Contains unit tests for mod_bigbluebuttonbn\dates.
 *
 * @package    mod_bigbluebuttonbn
 * @category   test
 * @copyright  2022 - present, Blindside Networks Inc
 * @author    Shamiso Jaravaza (shamiso.jaravaza@blindsidenetworks.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_bigbluebuttonbn;

use advanced_testcase;
use cm_info;
use core\activity_dates;
use mod_bigbluebuttonbn\test\testcase_helper_trait;

/**
 * Class for unit testing mod_bigbluebuttonbn\dates.
 *
 */
class dates_test extends advanced_testcase {
    use testcase_helper_trait;
    /**
     * Data provider for get_dates_for_module().
     * @return array[]
     */
    public function get_dates_for_module_provider(): array {
        $now = time();
        $before = $now - DAYSECS;
        $earlier = $before - DAYSECS;
        $after = $now + DAYSECS;
        $later = $after + DAYSECS;

        return [
            'without any dates' => [
                null, null, []
            ],
            'only with opening time' => [
                $after, null, [
                    ['label' => get_string('activitydate:opens', 'course'), 'timestamp' => $after],
                ]
            ],
            'only with closing time' => [
                null, $after, [
                    ['label' => get_string('activitydate:closes', 'course'), 'timestamp' => $after],
                ]
            ],
            'with both times' => [
                $after, $later, [
                    ['label' => get_string('activitydate:opens', 'course'), 'timestamp' => $after],
                    ['label' => get_string('activitydate:closes', 'course'), 'timestamp' => $later],
                ]
            ],
            'between the dates' => [
                $before, $after, [
                    ['label' => get_string('activitydate:opened', 'course'), 'timestamp' => $before],
                    ['label' => get_string('activitydate:closes', 'course'), 'timestamp' => $after],
                ]
            ],
            'dates are past' => [
                $earlier, $before, [
                    ['label' => get_string('activitydate:opened', 'course'), 'timestamp' => $earlier],
                    ['label' => get_string('activitydate:closed', 'course'), 'timestamp' => $before],
                ]
            ],
        ];
    }

    /**
     * Test for get_dates_for_module().
     *
     * @dataProvider get_dates_for_module_provider
     * @param int|null $timeopen The 'openingtime' value of the session.
     * @param int|null $timeclose The 'closingtime' value of the session.
     * @param array $expected The expected value of calling get_dates_for_module()
     */
    public function test_get_dates_for_module(?int $timeopen, ?int $timeclose, array $expected) {
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_and_enrol($course, 'student');
        $this->setUser($user);
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $instance = instance::get_from_instanceid( (int) $bbactivity->id);

        if ($timeopen) {
            $bbactivity->openingtime = $timeopen;
        }
        if ($timeclose) {
            $bbactivity->closingtime = $timeclose;
        }
        $DB->update_record('bigbluebuttonbn', $bbactivity);

        $this->setUser($user);
        // Make sure we're using a cm_info object.
        $bbactivitycm = cm_info::create($bbactivitycm);
        $dates = activity_dates::get_dates_for_module($bbactivitycm, (int) $user->id);

        $this->assertEquals($expected, $dates);
    }
}
