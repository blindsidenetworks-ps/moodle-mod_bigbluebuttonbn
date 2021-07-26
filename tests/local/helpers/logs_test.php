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
namespace mod_bigbluebuttonbn\local\helpers;
defined('MOODLE_INTERNAL') || die();
use mod_bigbluebuttonbn\local\bbb_constants;
global $CFG;
require_once($CFG->dirroot . '/mod/bigbluebuttonbn/tests/helpers.php');

/**
 * BBB Library tests class.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
class logs_test extends \bbb_simple_test {
    /**
     * Test delete instance logs
     *
     */
    public function test_bigbluebuttonbn_delete_instance_log() {
        global $DB;
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        logs::bigbluebuttonbn_delete_instance_log($bbactivity);
        $this->assertTrue($DB->record_exists('bigbluebuttonbn_logs', array('bigbluebuttonbnid' => $bbactivity->id,
            'log' => bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_DELETE)));
    }

    /**
     * Test log method
     */
    public function test_bigbluebuttonbn_log() {
        global $DB;
        $this->resetAfterTest();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        logs::bigbluebuttonbn_log($bbactivity, bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_PLAYED);
        $this->assertTrue($DB->record_exists('bigbluebuttonbn_logs', array('bigbluebuttonbnid' => $bbactivity->id)));
    }


}


