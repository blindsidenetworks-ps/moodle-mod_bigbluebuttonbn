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

use advanced_testcase;
use moodle_exception;

/**
 * Tests for the Big Blue Button Instance.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 Andrew Lyons <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_bigbluebuttonbn\instance
 */
class instance_test extends advanced_testcase {

    /**
     * Test get from
     *
     * @param string $function
     * @param string $field
     * @dataProvider get_from_location_provider
     * @covers ::get_from_instanceid
     * @covers ::get_from_cmid
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
     * @return string[][]
     */
    public function get_from_location_provider(): array {
        return [
            ['get_from_instanceid', 'id'],
            ['get_from_cmid', 'cmid'],
        ];
    }

    /**
     * Get an instance from a cmid.
     * @covers ::get_from_cmid
     */
    public function test_get_from_cmid(): void {
        $this->resetAfterTest();

        [
            'record' => $record,
            'cm' => $cm,
        ] = $this->get_test_instance();

        $instance = instance::get_from_cmid($cm->id);

        $this->assertInstanceOf(instance::class, $instance);
        $this->assertEquals($record->id, $instance->get_instance_id());
        $this->assertEquals($cm->id, $instance->get_cm()->id);
    }

    /**
     * If the instance was not found, and exception should be thrown.
     * @covers ::get_from_cmid
     */
    public function test_get_from_cmid_not_found(): void {
        $this->assertNull(instance::get_from_cmid(100));
    }

    /**
     * If the instance was not found, and exception should be thrown.
     */
    public function test_get_from_instance_not_found(): void {
        $this->assertNull(instance::get_from_instanceid(100));
    }

    /**
     * Get from meeting id
     *
     * @covers ::get_from_meetingid
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

    /**
     * Get the get_from_meetingid() function where the meetingid includes a groupid.
     *
     * @covers ::get_from_meetingid
     */
    public function test_get_from_meetingid_group(): void {
        $this->resetAfterTest();

        [
            'record' => $record,
            'course' => $course,
            'cm' => $cm,
        ] = $this->get_test_instance();

        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);

        $instance = instance::get_from_meetingid(
            sprintf("%s-%s-%s[0]", $record->meetingid, $record->course, $record->id)
        );

        $this->assertEquals($cm->instance, $instance->get_instance_id());
        $this->assertEquals($cm->id, $instance->get_cm_id());
    }

    /**
     * Ensure that invalid meetingids throw an appropriate exception.
     *
     * @dataProvider invalid_meetingid_provider
     * @param string $meetingid
     * @covers ::get_from_meetingid
     */
    public function test_get_from_meetingid_invalid(string $meetingid): void {
        $this->expectException(moodle_exception::class);
        instance::get_from_meetingid($meetingid);
    }

    public function invalid_meetingid_provider(): array {
        // Meeting IDs are in the formats:
        // - <meetingid[string]>-<courseid[number]>-<instanceid[number]>
        // - <meetingid[string]>-<courseid[number]>-<instanceid[number]>[<groupid[number]>]
        // Note: deducing the group from meeting id will soon be deprecated.
        return [
            'Non-numeric instanceid' => ['aaa-123-aaa'],
        ];
    }

    /**
     * Test the get_all_instances_in_course function.
     *
     * @covers ::get_all_instances_in_course
     */
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
        $cm = get_fast_modinfo($course)->instances['bigbluebuttonbn'][$record->id];

        return [
            'course' => $course,
            'record' => $record,
            'cm' => $cm,
        ];
    }

    /**
     * Test the get_meeting_id function for a meeting configured for a group.
     *
     * @covers ::get_meeting_id
     */
    public function test_get_meeting_id_with_groups(): void {
        $this->resetAfterTest();

        [
            'record' => $record,
            'course' => $course,
        ] = $this->get_test_instance();

        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);

        $instance = instance::get_from_instanceid($record->id);

        // No group.
        $this->assertEquals(
            sprintf("%s-%s-%s[0]", $record->meetingid, $record->course, $record->id),
            $instance->get_meeting_id(0)
        );

        // Specified group.
        $this->assertEquals(
            sprintf("%s-%s-%s[%d]", $record->meetingid, $record->course, $record->id, $group->id),
            $instance->get_meeting_id($group->id)
        );
    }
}
