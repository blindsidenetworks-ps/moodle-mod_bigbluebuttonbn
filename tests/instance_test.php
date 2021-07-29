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
 * Tests for the Big Blue Button Instance.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 Andrew Lyons <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bigbluebuttonbn;

/**
 * Tests for the Big Blue Button Instance.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 Andrew Lyons <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instance_test extends \advanced_testcase {

    /**
     * Test get from
     * @param string $function
     * @param string $field
     * @dataProvider get_from_location_provider
     */
    public function test_get_from(string $function, string $field): void {
        $this->resetAfterTest();

        [
            'record' => $record,
        ] = $this->get_test_instance();

        $instance = call_user_func("mod_bigbluebuttonbn\instance::{$function}", $record->{$field});

        $this->assertInstanceOf(instance::class, $instance);
        $this->assertEquals($record->id, $instance->get_instance_id());
        $this->assertEquals($record->cmid, $instance->get_cm_id());
        $this->assertEquals($record->cmid, $instance->get_cm()->id);
    }

    /**
     * Get from location provider
     *
     * @return \string[][]
     */
    public function get_from_location_provider(): array {
        return [
            ['get_from_instanceid', 'id'],
            ['get_from_cmid', 'cmid'],
        ];
    }

    /**
     * Get from meeting id
     */
    public function test_get_from_meetingid(): void {
        $this->resetAfterTest();

        [
            'record' => $record,
        ] = $this->get_test_instance();

        // The meetingid is confusingly made up of a meetingid field, courseid, instanceid, and groupid.
        $instance = instance::get_from_meetingid(sprintf(
            "%s-%s-%s",
            $record->meetingid,
            $record->course,
            $record->id
        ));

        $this->assertInstanceOf(instance::class, $instance);
        $this->assertEquals($record->id, $instance->get_instance_id());
        $this->assertEquals($record->cmid, $instance->get_cm_id());
        $this->assertEquals($record->cmid, $instance->get_cm()->id);
    }

    public function test_get_all_instances_in_course(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $records = [];
        for ($i = 0; $i < 5; $i++) {
            $records[] = $this->getDataGenerator()->create_module('bigbluebuttonbn', [
                'course' => $course->id,
            ]);
        }

        $instances = instance::get_all_instances_in_course($course->id);
        $this->assertCount(5, $instances);
        foreach ($instances as $instance) {
            $this->assertInstanceOf(instance::class, $instance);
        }
    }

    /**
     * Get test instance from data
     *
     * @param array $data
     * @return array
     */
    protected function get_test_instance(array $data = []): array {
        $course = $this->getDataGenerator()->create_course();
        $record = $this->getDataGenerator()->create_module('bigbluebuttonbn', array_merge([
            'course' => $course->id,
        ], $data));

        return [
            'course' => $course,
            'record' => $record,
        ];
    }
}
