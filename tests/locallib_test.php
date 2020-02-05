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
 * Local library tests class.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');

/**
 * Local library tests class.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
class mod_bigbluebuttonbn_locallib_testcase extends advanced_testcase {

    /**
     * Test for provider::get_metadata().
     */
    public function test_bigbluebuttonbn_get_recording_type_text() {
        $this->resetAfterTest(true);
        $this->assertEquals('Presentation', bigbluebuttonbn_get_recording_type_text('presentation'));
        $this->assertEquals('Video', bigbluebuttonbn_get_recording_type_text('video'));
        $this->assertEquals('Videos', bigbluebuttonbn_get_recording_type_text('videos'));
        $this->assertEquals('Whatever', bigbluebuttonbn_get_recording_type_text('whatever'));
        $this->assertEquals('Whatever It Can Be', bigbluebuttonbn_get_recording_type_text('whatever it can be'));
    }
}
