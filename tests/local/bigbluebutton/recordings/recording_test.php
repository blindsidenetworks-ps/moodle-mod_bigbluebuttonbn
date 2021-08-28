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
 * @covers \mod_bigbluebuttonbn\local\bigbluebutton\recordings\recording
 * @coversDefaultClass \mod_bigbluebuttonbn\local\bigbluebutton\recordings\recording
 */
class recording_test extends \advanced_testcase {

    public function setUp(): void {
        parent::setUp();

        $this->require_mock_server();
        $this->getDataGenerator()->get_plugin_generator('mod_bigbluebuttonbn')->reset_mock();
    }

    protected function create_activity_with_recordings(int $type, array $recordingdata): array {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bigbluebuttonbn');

        $course = $this->getDataGenerator()->create_course(['groupmodeforce' => true, 'groupmode' => VISIBLEGROUPS]);

        $activity = $generator->create_instance([
            'course' => $course->id,
            'type' => $type,
        ]);
        $generator->create_meeting([
            'instanceid' => $activity->id,
        ]);

        $recordings = [];
        $i = 0;
        foreach ($recordingdata as $data) {
            $recordings[] = $generator->create_recording(array_merge([
                'bigbluebuttonbnid' => $activity->id,
                'name' => "Pre-Recording $i",
            ], $data));
            $i++;
        }

        return [
            'course' => $course,
            'activity' => $activity,
            'recordings' => $recordings,
        ];
    }

    /**
     * Test for bigbluebuttonbn_get_allrecordings status refresh.
     *
     * @dataProvider get_status_provider
     * @covers ::get
     */
    public function test_get_allrecordings_status_refresh(int $status) {
        ['recordings' => $recordings] = $this->create_activity_with_recordings(instance::TYPE_ALL, [['status' => $status]]);

        $this->assertEquals($status, (new recording($recordings[0]->id))->get('status'));
    }

    /**
     * @covers ::get_name
     */
    public function test_get_name(): void {
        ['recordings' => $recordings] = $this->create_activity_with_recordings(instance::TYPE_ALL, [['name' => 'Example name']]);

        $this->assertEquals('Example name', (new recording($recordings[0]->id))->get('name'));
    }

    /**
     * @covers ::get_description
     */
    public function test_get_description(): void {
        ['recordings' => $recordings] = $this->create_activity_with_recordings(instance::TYPE_ALL, [[
            'description' => 'Example description',
        ]]);

        $this->assertEquals('Example description', (new recording($recordings[0]->id))->get('description'));
    }

    public function get_status_provider(): array {
        return [
            [recording::RECORDING_STATUS_PROCESSED],
            [recording::RECORDING_STATUS_DISMISSED],
        ];
    }

    protected function require_mock_server(): void {
        if (!defined('TEST_MOD_BIGBLUEBUTTONBN_MOCK_SERVER')) {
            $this->markTestSkipped(
                'The TEST_MOD_BIGBLUEBUTTONBN_MOCK_SERVER constant must be defined to run mod_bigbluebuttonbn tests'
            );
        }
    }
}
