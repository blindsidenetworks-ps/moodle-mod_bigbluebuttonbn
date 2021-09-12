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

namespace mod_bigbluebuttonbn\task;

use advanced_testcase;
use core\message\message;
use core\task\adhoc_task;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\proxy\recording_proxy;
use mod_bigbluebuttonbn\recording;
use mod_bigbluebuttonbn\test\testcase_helper_trait;
use stdClass;

/**
 * Class containing the scheduled task for lti module.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2019 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_bigbluebuttonbn\task\upgrade_recordings
 * @coversDefaultClass \mod_bigbluebuttonbn\task\upgrade_recordings
 */
class upgrade_recordings_imported_test extends advanced_testcase {
    use testcase_helper_trait;

    /**
     * @var object $course
     */
    protected $course;
    /**
     * @var object $bbbinstance
     */
    protected $bbbinstance;
    /**
     * @var array $groups
     */
    protected $groups;
    /**
     * @var array $teachers
     */
    protected $teachers;
    /**
     * @var array $students
     */
    protected $students;
    /**
     * @var array $logs
     */
    protected $logs = [];

    /**
     * Setup
     */
    public function setUp(): void {
        global $DB;
        $this->resetAfterTest();
        $this->require_mock_server();

        list('logs' => $this->logs,
            'course' => $this->course,
            'groups' => $this->groups,
            'recordings' => $recordings,
            'teachers' => $teachers,
            'instance' => $instance) = $this->prepare_recordings_with_logs();

        $baselogdata = [
            'courseid' => $this->course->id,
            'bigbluebuttonbnid' => $instance->get_instance_id(),
            'userid' => $teachers[0]->id,
            'timecreated' => '1613150758',
            'log' => 'Import',
            'meta' => '{"record":true}'
        ];
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bigbluebuttonbn');
        // Build fake metadata.
        foreach ($recordings as $rec) {
            $instance->set_group_id($rec->groupid);
            $data = recording_proxy::fetch_recordings([$rec->recordingid]);
            $data = end($data);
            $metaonly = array_filter($data, function($key) {
                return strstr($key, 'meta_');
            }, ARRAY_FILTER_USE_KEY);
            $recordonly = array_diff_key($data, $metaonly);

            $baselogdata['meetingid'] = $instance->get_meeting_id();
            $baselogdata['meta'] = json_encode(array_merge([
                'recording' => $recordonly,
                'imported' => true
            ], $metaonly));
            $logs[] = $generator->create_log($baselogdata);
        }
    }

    /**
     * Upgrade task test
     */
    public function test_upgrade_recordings(): void {
        global $DB;
        $upgraderecording = new upgrade_imported_recordings();
        $rc = new \ReflectionClass(upgrade_imported_recordings::class);
        $rcm = $rc->getMethod('process_bigbluebuttonbn_logs');
        $rcm->setAccessible(true);
        ob_start();
        $returnvalue = $rcm->invoke($upgraderecording);
        ob_end_clean();
        $this->assertTrue($returnvalue);
        $this->assertEmpty($DB->get_records('bigbluebuttonbn_logs', array('log' => 'Import')));

        $this->assertEquals(3, recording::count_records(array('imported' => '1')));
        $this->assertEquals(1, recording::count_records(['groupid' => $this->groups[0]->id, 'imported' => '1']));
        $this->assertEquals(1, recording::count_records(['groupid' => $this->groups[1]->id, 'imported' => '1']));
        $this->assertEquals(1, recording::count_records(['groupid' => 0, 'imported' => '1']));
    }
}
