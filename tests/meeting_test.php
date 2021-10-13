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
 * Meeting test.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn;

use mod_bigbluebuttonbn\test\testcase_helper_trait;

/**
 * Meeting tests class.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @covers \mod_bigbluebuttonbn\meeting
 * @coversDefaultClass \mod_bigbluebuttonbn\meeting
 */
class meeting_test extends \advanced_testcase {
    use testcase_helper_trait;

    /**
     * Setup Test
     */
    public function setUp(): void {
        parent::setUp();
        $this->initialise_mock_server();
        // We do not force the group mode so we can change the activity group mode during test.
        $this->course = $this->getDataGenerator()->create_course(['groupmode' => SEPARATEGROUPS]);
        $this->getDataGenerator()->create_group(['name' => 'G1', 'courseid' => $this->course->id]);
        $this->getDataGenerator()->create_group(['name' => 'G2', 'courseid' => $this->course->id]);
    }

    /**
     * Get a list of possible test (dataprovider)
     *
     * @return array[]
     */
    public function get_instance_types_meeting_info(): array {
        return [
            'Instance Type ALL - No Group' => [
                'type' => instance::TYPE_ALL,
                'groupname' => null,
                'groupmode' => NOGROUPS,
                'canjoin' => ['useringroup' => true, 'usernotingroup' => true],
            ],
            'Instance Type ALL - Group 1 - Visible groups' => [
                'type' => instance::TYPE_ALL,
                'groupname' => 'G1',
                'groupmode' => VISIBLEGROUPS,
                'canjoin' => ['useringroup' => true, 'usernotingroup' => true],
            ],
            'Instance Type ALL - Group 1 - Separate groups' => [
                'type' => instance::TYPE_ALL,
                'groupname' => 'G1',
                'groupmode' => SEPARATEGROUPS,
                'canjoin' => ['useringroup' => true, 'usernotingroup' => false],
            ],
            'Instance Type ROOM Only - No Group' => [
                'type' => instance::TYPE_ROOM_ONLY,
                'groupname' => null,
                'groupmode' => NOGROUPS,
                'canjoin' => ['useringroup' => true, 'usernotingroup' => true],
            ],
            'Instance Type ROOM Only - Group 1 - Visible groups' => [
                'type' => instance::TYPE_ROOM_ONLY,
                'groupname' => 'G1',
                'groupmode' => VISIBLEGROUPS,
                'canjoin' => ['useringroup' => true, 'usernotingroup' => true],
            ],
            'Instance Type ROOM Only - Group 1 - Separate groups' => [
                'type' => instance::TYPE_ROOM_ONLY,
                'groupname' => 'G1',
                'groupmode' => SEPARATEGROUPS,
                'canjoin' => ['useringroup' => true, 'usernotingroup' => false],
            ],
            'Instance Type Recording Only - No Group' => [
                'type' => instance::TYPE_RECORDING_ONLY,
                'groupname' => null,
                'groupmode' => NOGROUPS,
                'canjoin' => ['useringroup' => false, 'usernotingroup' => false]
            ],
            'Instance Type Recording Only - Group 1' => [
                'type' => instance::TYPE_RECORDING_ONLY,
                'groupname' => 'G1',
                'groupmode' => VISIBLEGROUPS,
                'canjoin' => ['useringroup' => false, 'usernotingroup' => false]
            ]
        ];
    }

    /**
     * Test for bigbluebuttonbn_get_allrecordings status refresh.
     *
     * @dataProvider get_instance_types_meeting_info
     * @param int $type
     * @param string $groupname
     * @covers ::create_meeting
     * @covers ::create_meeting_data
     * @covers ::create_meeting_metadata
     */
    public function test_create_meeting(int $type, $groupname) {
        [$meeting, $useringroup, $usernotingroup, $groupid, $activity] =
            $this->prepare_meeting($type, $groupname, SEPARATEGROUPS, false);
        $meeting->create_meeting();
        $meetinginfo = $meeting->get_meeting_info();
        $this->assertNotNull($meetinginfo);
        $this->assertEquals($activity->id, $meetinginfo->bigbluebuttonbnid);
        $this->assertFalse($meetinginfo->statusrunning);
        $this->assertStringContainsString("is ready", $meetinginfo->statusmessage);
        $this->assertEquals($groupid, $meetinginfo->groupid);
    }

    /**
     * Test for get meeting info
     *
     * @param int $type
     * @param string $groupname
     * @dataProvider get_instance_types_meeting_info
     * @covers ::get_meeting_info
     * @covers ::do_get_meeting_info
     */
    public function test_get_meeting_info(int $type, $groupname) {
        [$meeting, $useringroup, $usernotingroup, $groupid, $activity] = $this->prepare_meeting($type, $groupname);
        $meetinginfo = $meeting->get_meeting_info();
        $this->assertNotNull($meetinginfo);
        $this->assertEquals($activity->id, $meetinginfo->bigbluebuttonbnid);
        $this->assertTrue($meetinginfo->statusrunning);
        $this->assertStringContainsString("in progress", $meetinginfo->statusmessage);
        $this->assertEquals($groupid, $meetinginfo->groupid);
        $meeting->end_meeting();
        $meeting->update_cache();
        $meetinginfo = $meeting->get_meeting_info();
        $this->assertFalse($meetinginfo->statusrunning);
    }

    /**
     * Test for get meeting info
     *
     * @param int $type
     * @param string $groupname
     * @param bool $canjoin
     * @dataProvider get_instance_types_meeting_info
     * @covers ::can_join
     */
    public function test_can_join(int $type, $groupname, $groupmode, $canjoin) {
        [$meeting, $useringroup, $usernotingroup, $groupid, $activity] = $this->prepare_meeting($type, $groupname, $groupmode);
        $this->setUser($useringroup);
        $meeting->update_cache();
        $this->assertEquals($canjoin['useringroup'], $meeting->can_join());
        if ($meeting->can_join()) {
            $meetinginfo = $meeting->get_meeting_info();
            $this->assertStringContainsString("This conference is in progress", $meetinginfo->statusmessage);
        }
        if ($groupname) {
            $this->setUser($usernotingroup);
            $meeting->update_cache();
            $this->assertEquals($canjoin['usernotingroup'], $meeting->can_join());
        }
    }

    protected function prepare_meeting(int $type, $groupname, $groupmode = SEPARATEGROUPS, $createmeeting = true) {
        $this->resetAfterTest();
        $bbbgenerator = $this->getDataGenerator()->get_plugin_generator('mod_bigbluebuttonbn');
        $groupid = 0;
        $useringroup = $this->getDataGenerator()->create_and_enrol($this->get_course());
        $usernotingroup = $this->getDataGenerator()->create_and_enrol($this->get_course());
        if (!empty($groupname)) {
            $groupid = groups_get_group_by_name($this->get_course()->id, $groupname);
            $this->getDataGenerator()->create_group_member(array('groupid' => $groupid, 'userid' => $useringroup->id));
        }
        $meetinginfo = [
            'course' => $this->get_course()->id,
            'type' => $type
        ];
        $activity = $bbbgenerator->create_instance($meetinginfo, ['groupmode' => $groupmode]);
        $instance = instance::get_from_instanceid($activity->id);
        if ($groupid) {
            $instance->set_group_id($groupid);
        }
        if ($createmeeting) {
            // Create the meetings on the mock server, so we can join it as a simple user.
            $bbbgenerator->create_meeting([
                'instanceid' => $instance->get_instance_id(),
                'groupid' => $instance->get_group_id()
            ]);
        }
        $meeting = new meeting($instance);
        return [$meeting, $useringroup, $usernotingroup, $groupid, $activity];
    }
}
