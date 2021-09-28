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
 * @author    amayard@cblue.be
 * @date      25/08/2021
 * @copyright 2021, CBlue SPRL, support@cblue.be
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   block_admin_presets
 */

namespace mod_bigbluebuttonbn\task;

use coding_exception;
use core\task\scheduled_task;
use mod_bigbluebuttonbn\locallib\bigbluebutton;
use mod_bigbluebuttonbn\server;

defined('MOODLE_INTERNAL') || die();

class interrogate_servers extends scheduled_task {

    /**
     * @return string
     * @throws coding_exception
     */
    public function get_name() {
        return get_string('interrogateservers', 'mod_bigbluebuttonbn');
    }

    /**
     * This script is triggered by a cron job every 5 minutes, see db/tasks.php to check the configuration
     * It'll get all BBB servers configured on the platform that are 'enabled', then will loop through them and will
     * - Check if there are any meetings using BBB API and register their count.
     * - If there are meetings it'll loop through them and interrogate BBB API to get attendees count
     * - Create a database record with the fetched data in table 'mdl_bigbluebuttonbn_statistic'
     *
     * @throws \dml_exception
     */
    public function execute() {
        global $DB;

        //get all servers recrods from DB
        $servers = $DB->get_records_sql("SELECT * FROM {bigbluebuttonbn_servers} WHERE enabled = 1");

        //loop through servers and call APIS to get data
        foreach ($servers as $server) {
            //set server info
            $data = new \stdClass();
            $data->servername = $server->name;
            $data->serverid = $server->id;
            $data->timecreated = time();
            $data->attendeescount = 0;

            //get server's meetings info
            bigbluebutton::$selected_server = new server(0, $server);
            $url = bigbluebutton::action_url('getMeetings');
            $servinfo = bigbluebuttonbn_wrap_xml_load_file($url);

            if (count((array)$servinfo->meetings) === 0) {
                $data->meetingscount = 0;
            } else {
                $data->meetingscount = count($servinfo->meetings->meeting);

                //get server's each independant meeting info so we can count attendees
                foreach ($servinfo->meetings->meeting as $meeting) {
                    $url = bigbluebutton::action_url('getMeetingInfo', ['meetingID' => $meeting->meetingID->__toString()]);
                    $meetinginfo = bigbluebuttonbn_wrap_xml_load_file($url);
                    $data->attendeescount += count($meetinginfo->attendees->attendee);
                }
            }

            //insert into DB
            $DB->insert_record('bigbluebuttonbn_statistics', $data);
        }
    }
}