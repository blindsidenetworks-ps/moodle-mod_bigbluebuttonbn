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
 * BBB Library notifier test.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */

namespace mod_bigbluebuttonbn\local;

use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\test\testcase_helper;

/**
 * Tests for the notifier class.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
class notifier_test extends testcase_helper {
    /**
     * Test notificiation updated
     *
     */
    public function test_notify_instance_updated() {
        global $DB;
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user2->id, $this->course->id);

        $this->setUser($user1);
        $messagesink = $this->redirectMessages();
        list($bbactivitycontext, $bbactivitycm, $bbactivity) = $this->create_instance();
        $bbformdata = $this->get_form_data_from_instance($bbactivity);
        $bbactivity->coursemodule = $bbactivitycm; // We submit the form here.
        notifier::notify_instance_updated($bbformdata, 'updated');
        ob_start();
        $this->runAdhocTasks();
        ob_end_clean();
        $this->assertNotEmpty($messagesink->get_messages());
        $message = $messagesink->get_messages()[0];
        $this->assertEquals(
            "BigBlueButton BIGBLUEBUTTON 1 [1] has been Updated

Details:

		 Title: BigBlueButton 1

		 Description:

		 Start date: 1 January 1970

		 End date: 1 January 1970

		 by: Admin User

-------------------------

This automatic notification message was sent by Admin User from the course
Test course 1

Links:
------
[1] https://www.example.com/moodle/mod/bigbluebuttonbn/view.php?id={$bbactivitycm->id}
", $message->fullmessage);
    }
}
