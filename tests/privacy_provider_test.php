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

use core_privacy\local\metadata\collection;
use mod_bigbluebuttonbn\privacy\provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider tests class.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class mod_bigbluebuttonbn_privacy_provider_testcase extends \core_privacy\tests\provider_testcase {

    /**
     * Test for provider::get_metadata().
     */
    public function test_get_metadata() {
        $this->resetAfterTest(true);
        
        $collection = new collection('mod_bigbluebuttonbn');
        $newcollection = provider::get_metadata($collection);
        $itemcollection = $newcollection->get_collection();
        $this->assertCount(3, $itemcollection);

        $bigbluebuttonbntable = array_shift($itemcollection);
        $this->assertEquals('bigbluebuttonbn', $bigbluebuttonbntable->get_name());

        $bigbluebuttonbnlogstable = array_shift($itemcollection);
        $this->assertEquals('bigbluebuttonbn_logs', $bigbluebuttonbnlogstable->get_name());

        $bigbluebuttonserver = array_shift($itemcollection);
        $this->assertEquals('bigbluebutton', $bigbluebuttonserver->get_name());

        $privacyfields = $bigbluebuttonbntable->get_privacy_fields();
        $this->assertArrayHasKey('participants', $privacyfields);
        $this->assertEquals('privacy:metadata:bigbluebuttonbn', $bigbluebuttonbntable->get_summary());

        $privacyfields = $bigbluebuttonbnlogstable->get_privacy_fields();
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('timecreated', $privacyfields);
        $this->assertArrayHasKey('meetingid', $privacyfields);
        $this->assertArrayHasKey('log', $privacyfields);
        $this->assertArrayHasKey('meta', $privacyfields);
        $this->assertEquals('privacy:metadata:bigbluebuttonbn_logs', $bigbluebuttonbnlogstable->get_summary());

        $privacyfields = $bigbluebuttonserver->get_privacy_fields();
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('fullname', $privacyfields);
        $this->assertEquals('privacy:metadata:bigbluebutton', $bigbluebuttonserver->get_summary());
    }

}
