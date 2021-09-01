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

namespace mod_bigbluebuttonbn;

use mod_bigbluebuttonbn\test\testcase_helper_trait;
use restore_date_testcase;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . "/phpunit/classes/restore_date_testcase.php");

/**
 * BBB Library tests class.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
class backup_restore_test extends restore_date_testcase {
    use testcase_helper_trait;

    public function setUp(): void {
        parent::setUp();

        $this->getDataGenerator()->get_plugin_generator('mod_bigbluebuttonbn')->reset_mock();
    }

    /**
     * @dataProvider type_provider
     */
    public function test_backup_restore($type): void {
        global $DB;
        $this->resetAfterTest();
        $bbactivity = $this->getDataGenerator()->create_module(
            'bigbluebuttonbn',
            ['course' => $this->get_course()->id, 'type' => $type],
            ['visible' => true]
        );

        $newcourseid = $this->backup_and_restore($this->get_course());
        $newbbb = $DB->get_record('bigbluebuttonbn', ['course' => $newcourseid], '*', MUST_EXIST); // One record.
        $this->assertNotFalse($newbbb);
        $filterfunction = function($key) {
            return !in_array($key, ['course', 'cmid', 'id', 'course']);
        };
        $this->assertEquals(
            array_filter((array) $bbactivity, $filterfunction, ARRAY_FILTER_USE_KEY),
            array_filter((array) $newbbb, $filterfunction, ARRAY_FILTER_USE_KEY)
        );
    }

    /**
     * Instance type provider
     *
     * @return array
     */
    public function type_provider(): array {
        return [
            'Instance Type ALL' => [instance::TYPE_ALL],
            'Instance Type Recording Only' => [instance::TYPE_RECORDING_ONLY],
            'Instance  Room Only' => [instance::TYPE_ROOM_ONLY]
        ];
    }

    /**
     * @dataProvider type_provider
     */
    public function test_backup_restore_with_recordings($type): void {
        global $DB;
        $this->resetAfterTest();

        $this->require_mock_server();

        $nbrecordings = 2; // Two recordings for now.
        // This is for imported recording.
        $generator = $this->getDataGenerator();
        $othercourse = $generator->create_course();
        $bbactivityothercourse = $generator->create_module(
            'bigbluebuttonbn',
            ['course' => $this->get_course()->id, 'type' => instance::TYPE_ALL],
            ['visible' => true]
        );
        $bbactivitysamecourse = $generator->create_module(
            'bigbluebuttonbn',
            ['course' => $this->get_course()->id, 'type' => instance::TYPE_ALL],
            ['visible' => true]
        );

        $bbactivity = $generator->create_module(
            'bigbluebuttonbn',
            ['course' => $this->get_course()->id, 'type' => $type, 'name' => 'BBB Activity'],
            ['visible' => true]
        );

        $bbbgenerator = $this->getDataGenerator()->get_plugin_generator('mod_bigbluebuttonbn');
        $i = 0;
        $recordingactivitylist = [
            $bbactivityothercourse->id,
            $bbactivitysamecourse->id
        ];
        if ($type === instance::TYPE_ALL) {
            $recordingactivitylist[] = $bbactivity->id;
        }
        foreach ($recordingactivitylist as $bbbactivityid) {
            foreach (range(1, $nbrecordings) as $rindex) {
                $recordings[] = $bbbgenerator->create_recording(array_merge([
                    'bigbluebuttonbnid' => $bbbactivityid,
                    'name' => "PR Recording {$rindex}.{$i}"
                ]));
                $i++;
            }
        }
        // Then import the recordings into the instance.
        $instance = instance::get_from_instanceid($bbactivity->id);
        foreach ($recordings as $rec) {
            $rentity = recording::get_record(['id' => $rec->id]);
            if ($rentity->get('bigbluebuttonbnid') != $instance->get_instance_id()) {
                $rentity->create_imported_recording($instance);
            }
        }
        $newcourseid = $this->backup_and_restore($this->get_course());
        $newbbb = $DB->get_record('bigbluebuttonbn', ['course' => $newcourseid, 'name' => 'BBB Activity'], '*',
            MUST_EXIST); // One record.
        $this->assertNotFalse($newbbb);
        $filterfunction = function($key) {
            return !in_array($key, ['course', 'cmid', 'id', 'course']);
        };
        $this->assertEquals(
            array_filter((array) $bbactivity, $filterfunction, ARRAY_FILTER_USE_KEY),
            array_filter((array) $newbbb, $filterfunction, ARRAY_FILTER_USE_KEY)
        );
        $newinstance = instance::get_from_instanceid($newbbb->id);
        if ($type === instance::TYPE_ALL) {
            $this->assertCount($nbrecordings, recording::get_recordings_for_instance($newinstance));
        } else {
            // This type of recording has not got it own recordings.
            $this->assertCount(0, recording::get_recordings_for_instance($newinstance));
        }
        $this->assertCount($nbrecordings * ($type === instance::TYPE_ALL ? 3 : 2),
            recording::get_recordings_for_instance($newinstance, false, true, false));
    }

}
