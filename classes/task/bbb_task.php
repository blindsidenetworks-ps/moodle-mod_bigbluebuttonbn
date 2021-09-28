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

use coding_exception;
use core\task\scheduled_task;
use Exception;
use mod_bigbluebuttonbn\locallib\bigbluebutton;
use mod_bigbluebuttonbn\server;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class bbb_task extends scheduled_task
{
    /**
     * @return string
     * @throws coding_exception
     */
    public function get_name()
    {
        return get_string('bbb_task', 'mod_bigbluebuttonbn');
    }

    /**
     * @throws \Exception
     */
    public function execute()
    {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');
        require_once($CFG->dirroot . '/mod/bigbluebuttonbn/classes/server.php');

        $servers = $DB->get_records_sql("SELECT * FROM {bigbluebuttonbn_servers} WHERE enabled = 1");
        foreach ($servers as $server) {
            try {
                bigbluebutton::$selected_server = new server(0, $server);
                $url = bigbluebutton::action_url('getMeetings');
                $info = bigbluebuttonbn_wrap_xml_load_file($url);
                if (!empty($info->meetings)) {
                    foreach ($info->meetings->meeting as $meeting) {
                        $meeting_id = substr($meeting->meetingID[0]->__toString(), 0, 40);
                        $bbb = $DB->get_record_sql("SELECT * FROM {bigbluebuttonbn} WHERE meetingid = :meetingid", ['meetingid' => $meeting_id]);
                        if (empty($bbb)) {
                            continue;
                        }
                        $bbb_bn_server = $DB->get_record_sql("SELECT * FROM {bigbluebuttonbn_bn_server} WHERE bnid = $bbb->id AND serverid = $server->id AND ended = 0");
                        if (empty($bbb_bn_server)) {
                            continue;
                        }
                        foreach ($meeting->attendees->attendee as $attendee) {
                            $user = $DB->get_record_sql("SELECT * FROM {user} WHERE id = :id", ['id' => $attendee->userID[0]->__toString()]);
                            if (empty($user)) {
                                continue;
                            }
                            $history = $DB->get_record_sql("SELECT * FROM {bigbluebuttonbn_history} WHERE bnserverid = $bbb_bn_server->id AND userid = $user->id ORDER BY starttime DESC LIMIT 1");
                            if (empty($history)) {
                                $history = new stdClass();
                                $history->bnid = $bbb->id;
                                $history->serverid = $server->id;
                                $history->bnserverid = $bbb_bn_server->id;
                                $history->userid = $user->id;
                                $history->starttime = time();
                                $history->endtime = time();
                                $DB->insert_record('bigbluebuttonbn_history', $history);
                            } else {
                                $history->endtime = time();
                                $DB->update_record('bigbluebuttonbn_history', $history);
                            }
                        }
                    }
                }
            } catch (moodle_exception $e) {
                echo $e->getMessage() . ' - ' . $e->debuginfo;
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
    }

}
