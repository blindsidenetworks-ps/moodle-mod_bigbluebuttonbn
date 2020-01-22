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
 * Internal library of functions for module BigBlueButtonBN.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2019 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
namespace mod_bigbluebuttonbn\task;

use core\task\adhoc_task;
use \mod_bigbluebuttonbn\locallib\notifier;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');

/**
 * Class containing the scheduled task for lti module.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2019 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_notification extends adhoc_task
{

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('sendnotification', 'mod_bigbluebuttonbn');
    }

    /**
     * Run bigbluebuttonbn cron.
     */
    public function execute() {
        // Get the custom data.
        $data = $this->get_custom_data();
        mtrace("Execute send_notification task: Sending notification to user {$data->receiver->id}");
        // Process the completion.
        \mod_bigbluebuttonbn\locallib\notifier::send_notification($data->sender, $data->receiver, $data->htmlmsg);
    }
}
