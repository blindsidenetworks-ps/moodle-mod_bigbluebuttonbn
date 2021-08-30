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

use core\task\adhoc_task;
use mod_bigbluebuttonbn\local\notifier;

/**
 * Class containing the scheduled task for lti module.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2019 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @deprecated Since Moodle 4.0
 */
class send_legacy_notification extends adhoc_task {
    /**
     * Run bigbluebuttonbn cron.
     */
    public function execute() {
        // Get the custom data.
        $data = $this->get_custom_data();
        mtrace("Execute send_notification task: Sending notification to user {$data->receiver->id}");

        // Process the completion.
        message_post_message($data->sender, $data->receiver, $data->htmlmsg, FORMAT_HTML);
    }
}
