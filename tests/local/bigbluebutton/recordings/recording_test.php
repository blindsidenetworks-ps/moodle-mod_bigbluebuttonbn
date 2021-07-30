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
use mod_bigbluebuttonbn\local\bbb_constants;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider tests class.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class recording_test extends \advanced_testcase {

    /**
     * @var array of courses
     */
    public $courses = [];
    /**
     * @var array of activities (bbb)
     */
    public $bbactivities = [];
    /**
     * Model to build
     */
    const BB_ACTIVITIES = [
        'BBACTIVITY1' => ['courseindex' => 0, 'type' => bbb_constants::BIGBLUEBUTTONBN_TYPE_ALL, 'nbrecordings' => 2],
        'BBACTIVITY2' => ['courseindex' => 0, 'type' => bbb_constants::BIGBLUEBUTTONBN_TYPE_ALL, 'nbrecordings' => 3],
        'BBACTIVITY3' => ['courseindex' => 1, 'type' => bbb_constants::BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY, 'nbrecordings' => 3],
    ];

    /**
     * Setup
     *
     */
    public function setUp(): void {
        parent::setUp();
        $maxcourseindexindex = array_reduce(
            static::BB_ACTIVITIES,
            function($acc, $item) {
                return $acc > $item['courseindex'] ? $acc : $item['courseindex'];
            },
            0
        );
        for ($i = 0; $i <= $maxcourseindexindex; $i++) {
            $this->courses[] = $this->getDataGenerator()->create_course(['groupmodeforce' => true, 'groupmode' => VISIBLEGROUPS]);
        }
        $bbngenerator = $this->getDataGenerator()->get_plugin_generator('mod_bigbluebuttonbn');
        foreach (static::BB_ACTIVITIES as $aname => $activity) {
            $bbactivity = $bbngenerator->create_instance(
                [
                    'course' => $this->courses[$activity['courseindex']]->id,
                    'type' => $activity['type'],
                    'name' => $aname
                ]
            );
            for ($nbrecordings = 0; $nbrecordings < $activity['nbrecordings']; $nbrecordings++) {
                $this->getDataGenerator()
                    ->get_plugin_generator('mod_bigbluebuttonbn')
                    ->create_recording(['bigbluebuttonbnid' => $bbactivity->id,
                        'meta_bbb-recording-name' => "Pre-Recording $nbrecordings"]);
            }
            $this->bbactivities[] = $bbactivity;
        }
    }

    /**
     * Test for bigbluebuttonbn_get_allrecordings().
     */
    public function test_bigbluebuttonbn_get_allrecordings() {
        $this->resetAfterTest();
        $recordings = recording_helper::get_recordings_for_instance(instance::get_from_instanceid($this->bbactivities[0]->id));
        $this->assertCount(2, $recordings);

        $recordings = recording_helper::get_recordings_for_instance(instance::get_from_instanceid($this->bbactivities[1]->id));
        $this->assertCount(3, $recordings);

        $recordings = recording_helper::get_recordings_for_instance(instance::get_from_instanceid($this->bbactivities[2]->id));
        $this->assertCount(3, $recordings);

    }

    /**
     * Test for bigbluebuttonbn_get_allrecordings().
     *
     * TODO: rewrite this with @dataProvider
     */
    public function test_bigbluebuttonbn_get_recording_for_group() {
        $this->resetAfterTest();
        $testcourse = $this->courses[0];
        $bbactivity = $this->bbactivities[0];
        $group1 = $this->getDataGenerator()->create_group(['idnumber' => 'G1', 'courseid' => $testcourse->id]);
        $group2 = $this->getDataGenerator()->create_group(['idnumber' => 'G2', 'courseid' => $testcourse->id]);
        $student1 = $this->getDataGenerator()->create_and_enrol($testcourse);
        $student2 = $this->getDataGenerator()->create_and_enrol($testcourse);
        $student3 = $this->getDataGenerator()->create_and_enrol($testcourse); // No group.
        $teacher = $this->getDataGenerator()->create_and_enrol($testcourse, 'teacher');
        $this->getDataGenerator()->create_group_member(['userid' => $student1, 'groupid' => $group1->id]);
        $this->getDataGenerator()->create_group_member(['userid' => $student2, 'groupid' => $group2->id]);

        $recording1 = $this->getDataGenerator()
            ->get_plugin_generator('mod_bigbluebuttonbn')
            ->create_recording(['bigbluebuttonbnid' => $bbactivity->id, 'Group' => $group1->idnumber,
                'meta_bbb-recording-name' => 'Recording 1']);

        $recording2 = $this->getDataGenerator()
            ->get_plugin_generator('mod_bigbluebuttonbn')
            ->create_recording(['bigbluebuttonbnid' => $bbactivity->id, 'Group' => $group2->idnumber,
                'meta_bbb-recording-name' => 'Recording 2']);

        $this->setUser($student1);
        $instance1 = instance::get_from_instanceid($this->bbactivities[0]->id);
        $instance1->set_group_id($group1->id);
        $recordings = recording_helper::get_recordings_for_instance($instance1);
        $this->assertCount(1, $recordings);
        $this->assertEquals($recordings[$recording1->recordingid]->recording['meta_bbb-recording-name'], 'Recording 1');

        $this->setUser($student2);
        $instance2 = instance::get_from_instanceid($this->bbactivities[0]->id);
        $instance2->set_group_id($group2->id);
        $recordings = recording_helper::get_recordings_for_instance($instance2);
        $this->assertCount(1, $recordings);
        $this->assertEquals($recordings[$recording2->recordingid]->recording['meta_bbb-recording-name'], 'Recording 2');

        $this->setUser($student3);
        $instance3 = instance::get_from_instanceid($this->bbactivities[0]->id);
        $recordings = recording_helper::get_recordings_for_instance($instance3);
        $this->assertIsArray($recordings);
        $recordingnames = array_map(function($r) {
            return $r->recording['meta_bbb-recording-name'];
        }, $recordings);
        $this->assertCount(4, $recordingnames);
        $this->assertContains('Pre-Recording 0', $recordingnames);
        $this->assertContains('Pre-Recording 1', $recordingnames);
    }

    /**
     * Test for provider::get_metadata().
     */
    public function test_bigbluebuttonbn_get_recording_type_text() {
        $this->resetAfterTest(true);
        $this->assertEquals('Presentation', recording_data::type_text('presentation'));
        $this->assertEquals('Video', recording_data::type_text('video'));
        $this->assertEquals('Videos', recording_data::type_text('videos'));
        $this->assertEquals('Whatever', recording_data::type_text('whatever'));
        $this->assertEquals('Whatever It Can Be', recording_data::type_text('whatever it can be'));
    }
}

