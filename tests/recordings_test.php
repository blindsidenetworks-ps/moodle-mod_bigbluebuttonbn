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

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/mod/bigbluebuttonbn/lib.php');
require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');

/**
 * Privacy provider tests class.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class mod_bigbluebuttonbn_recordings_testcase extends advanced_testcase {

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
        'BBACTIVITY1' => ['courseindex' => 0, 'type' => BIGBLUEBUTTONBN_TYPE_ALL, 'nbrecordings' => 2],
        'BBACTIVITY2' => ['courseindex' => 0, 'type' => BIGBLUEBUTTONBN_TYPE_ALL, 'nbrecordings' => 3],
        'BBACTIVITY3' => ['courseindex' => 1, 'type' => BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY, 'nbrecordings' => 3],
    ];

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
            $this->courses[] = $this->getDataGenerator()->create_course();
        }
        $bbngenerator = $this->getDataGenerator()->get_plugin_generator('mod_bigbluebuttonbn');
        /* @var $bbngenerator mod_bigbluebuttonbn_generator */
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
                    ->create_recording(['bigbluebuttonbnid' => $bbactivity->id]);
            }
            $this->bbactivities[] = $bbactivity;
        }
    }

    /**
     * Clean the temporary mocked up recordings
     *
     * @throws coding_exception
     */
    public function tearDown(): void {
        parent::tearDown();
        $this->getDataGenerator()->get_plugin_generator('mod_bigbluebuttonbn')
            ->bigbluebuttonbn_clean_recordings_array_fetch();
    }

    /**
     * Test for bigbluebuttonbn_get_allrecordings().
     */
    public function test_bigbluebuttonbn_get_allrecordings() {
        $this->resetAfterTest();

        $recordings = bigbluebuttonbn_get_allrecordings($this->bbactivities[0]->course, $this->bbactivities[0]->id);
        $this->assertCount(2, $recordings);

        $recordings = bigbluebuttonbn_get_allrecordings($this->bbactivities[1]->course, $this->bbactivities[1]->id);
        $this->assertCount(3, $recordings);

        $recordings = bigbluebuttonbn_get_allrecordings($this->bbactivities[2]->course, $this->bbactivities[2]->id);
        $this->assertCount(3, $recordings);

    }
}
