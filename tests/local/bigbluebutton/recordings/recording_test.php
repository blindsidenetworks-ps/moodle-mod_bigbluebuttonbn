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
 * Privacy provider tests.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\local\bigbluebutton\recordings;

use mod_bigbluebuttonbn\instance;

/**
 * Privacy provider tests class.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class recording_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();

        $this->require_mock_server();
        $this->getDataGenerator()->get_plugin_generator('mod_bigbluebuttonbn')->reset_mock();
    }

    protected function setup_activities(): array {
        $this->resetAfterTest();

        $model = [
            [
                'courseindex' => 0,
                'type' => instance::TYPE_ALL,
                'recordingcount' => 2,
            ],
            [
                'courseindex' => 0,
                'type' => instance::TYPE_ALL,
                'recordingcount' => 3,
            ],
            [
                'courseindex' => 1,
                'type' => instance::TYPE_RECORDING_ONLY,
                'recordingcount' => 3,
            ],
        ];

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bigbluebuttonbn');

        $courses = [
            $this->getDataGenerator()->create_course(['groupmodeforce' => true, 'groupmode' => VISIBLEGROUPS]),
            $this->getDataGenerator()->create_course(['groupmodeforce' => true, 'groupmode' => VISIBLEGROUPS]),
        ];

        foreach ($model as $aname => $config) {
            $activity = $generator->create_instance([
                'course' => $courses[$config['courseindex']]->id,
                'type' => $config['type'],
                'name' => $aname
            ]);
            $generator->create_meeting([
                'instanceid' => $activity->id,
            ]);

            for ($recordingcount = 0; $recordingcount < $config['recordingcount']; $recordingcount++) {
                $generator->create_recording([
                    'bigbluebuttonbnid' => $activity->id,
                    'name' => "Pre-Recording $recordingcount",
                ]);
            }
            $activities[] = $activity;
        }

        return [
            'activities' => $activities,
            'courses' => $courses,
        ];
    }

    /**
     * Test for bigbluebuttonbn_get_allrecordings().
     */
    public function test_bigbluebuttonbn_get_allrecordings() {
        [
            'activities' => $activities,
        ] = $this->setup_activities();

        $recordings = recording_helper::get_recordings_for_instance(instance::get_from_instanceid($activities[0]->id));
        $this->assertCount(2, $recordings);

        $recordings = recording_helper::get_recordings_for_instance(instance::get_from_instanceid($activities[1]->id));
        $this->assertCount(3, $recordings);

        $recordings = recording_helper::get_recordings_for_instance(instance::get_from_instanceid($activities[2]->id));
        $this->assertCount(3, $recordings);
    }

    /**
     * Test for bigbluebuttonbn_get_allrecordings status refresh.
     */
    public function test_bigbluebuttonbn_get_allrecordings_status_refresh() {
        [
            'activities' => $activities,
        ] = $this->setup_activities();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bigbluebuttonbn');
        $recording1 = $generator->create_recording([
            'bigbluebuttonbnid' => $activities[0]->id,
            'name' => "Pre-Recording should be refreshed",
            'status' => recording::RECORDING_STATUS_AWAITING
        ]);
        $recording2 = $generator->create_recording([
            'bigbluebuttonbnid' => $activities[0]->id,
            'name' => "Pre-Recording should be visible",
            'status' => recording::RECORDING_STATUS_DISMISSED
        ]);
        $this->assertEquals(recording::RECORDING_STATUS_AWAITING,
            (new recording($recording1->id))->get('status'));
        $this->assertEquals(recording::RECORDING_STATUS_DISMISSED,
            (new recording($recording2->id))->get('status'));
        $recordings = recording_helper::get_recordings_for_instance(instance::get_from_instanceid($activities[0]->id));
        $this->assertCount(3, $recordings);

        $this->assertEquals(recording::RECORDING_STATUS_PROCESSED,
            (new recording($recording1->id))->get('status'));
        $this->assertEquals(recording::RECORDING_STATUS_DISMISSED,
            (new recording($recording2->id))->get('status'));
    }

    /**
     * Test for bigbluebuttonbn_get_allrecordings().
     *
     * TODO: rewrite this with @dataProvider
     */
    public function test_bigbluebuttonbn_get_recording_for_group() {
        $this->resetAfterTest(true);

        $plugingenerator = $this->getDataGenerator()->get_plugin_generator('mod_bigbluebuttonbn');

        $testcourse = $this->getDataGenerator()->create_course(['groupmodeforce' => true, 'groupmode' => VISIBLEGROUPS]);
        $teacher = $this->getDataGenerator()->create_and_enrol($testcourse, 'editingteacher');

        $group1 = $this->getDataGenerator()->create_group(['G1', 'courseid' => $testcourse->id]);
        $student1 = $this->getDataGenerator()->create_and_enrol($testcourse);
        $this->getDataGenerator()->create_group_member(['userid' => $student1, 'groupid' => $group1->id]);

        $group2 = $this->getDataGenerator()->create_group(['G2', 'courseid' => $testcourse->id]);
        $student2 = $this->getDataGenerator()->create_and_enrol($testcourse);
        $this->getDataGenerator()->create_group_member(['userid' => $student2, 'groupid' => $group2->id]);

        // No group.
        $student3 = $this->getDataGenerator()->create_and_enrol($testcourse);

        $activity = $plugingenerator->create_instance([
            'course' => $testcourse->id,
            'type' => instance::TYPE_ALL,
            'name' => 'Example',
        ]);
        $plugingenerator->create_meeting([
            'instanceid' => $activity->id,
        ]);

        // Create two recordings for all groups.
        $plugingenerator->create_recording([
            'bigbluebuttonbnid' => $activity->id,
            'name' => "Pre-Recording 1",
        ]);
        $plugingenerator->create_recording([
            'bigbluebuttonbnid' => $activity->id,
            'name' => "Pre-Recording 2",
        ]);

        $plugingenerator->create_meeting([
            'instanceid' => $activity->id,
            'groupid' => $group1->id,
        ]);
        $recording1 = $plugingenerator->create_recording([
            'bigbluebuttonbnid' => $activity->id,
            'groupid' => $group1->id,
            'name' => 'Group 1 Recording 1',
        ]);

        $plugingenerator->create_meeting([
            'instanceid' => $activity->id,
            'groupid' => $group2->id,
        ]);
        $recording2 = $plugingenerator->create_recording([
            'bigbluebuttonbnid' => $activity->id,
            'groupid' => $group2->id,
            'name' => 'Group 2 Recording 1',
        ]);

        $this->setUser($student1);
        $instance1 = instance::get_from_instanceid($activity->id);
        $instance1->set_group_id($group1->id);
        $recordings = recording_helper::get_recordings_for_instance($instance1);
        $this->assertCount(1, $recordings);
        $this->assertEquals('Group 1 Recording 1', $recordings[$recording1->id]->get('name'));

        $this->setUser($student2);
        $instance2 = instance::get_from_instanceid($activity->id);
        $instance2->set_group_id($group2->id);
        $recordings = recording_helper::get_recordings_for_instance($instance2);
        $this->assertCount(1, $recordings);
        $this->assertEquals('Group 2 Recording 1', $recordings[$recording2->id]->get('name'));

        $this->setUser($student3);
        $instance3 = instance::get_from_instanceid($activity->id);
        $recordings = recording_helper::get_recordings_for_instance($instance3);
        $this->assertIsArray($recordings);
        $recordingnames = array_map(function($r) {
            return $r->get('name');
        }, $recordings);
        $this->assertCount(4, $recordingnames);
        $this->assertContains('Pre-Recording 1', $recordingnames);
        $this->assertContains('Pre-Recording 2', $recordingnames);
    }

    /**
     * Test for provider::get_metadata().
     */
    public function test_bigbluebuttonbn_get_recording_type_text() {
        $this->assertEquals('Presentation', recording_data::type_text('presentation'));
        $this->assertEquals('Video', recording_data::type_text('video'));
        $this->assertEquals('Videos', recording_data::type_text('videos'));
        $this->assertEquals('Whatever', recording_data::type_text('whatever'));
        $this->assertEquals('Whatever It Can Be', recording_data::type_text('whatever it can be'));
    }

    protected function require_mock_server(): void {
        if (!defined('TEST_MOD_BIGBLUEBUTTONBN_MOCK_SERVER')) {
            $this->markTestSkipped(
                'The TEST_MOD_BIGBLUEBUTTONBN_MOCK_SERVER constant must be defined to run mod_bigbluebuttonbn tests'
            );
        }
    }
}
