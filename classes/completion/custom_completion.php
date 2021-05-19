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
 * The mod_bigbluebuttonbn custom_completion event.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */

namespace mod_bigbluebuttonbn\completion;

use core_completion\activity_custom_completion;
use Exception;
use mod_bigbluebuttonbn\local\bbb_constants;

defined('MOODLE_INTERNAL') || die();

/**
 * Class custom_completion
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
class custom_completion extends activity_custom_completion {

    /**
     * Get current state
     *
     * @param string $rule
     * @return int
     * @throws \dml_exception
     */
    public function get_state(string $rule): int {
        global $DB;

        // Get bigbluebuttonbn details.
        $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $this->cm->instance), '*',
            MUST_EXIST);
        if (!$bigbluebuttonbn) {
            throw new Exception("Can't find bigbluebuttonbn {$this->cm->instance}");
        }

        // Default return value.
        $value = false;

        $sql = "SELECT * FROM {bigbluebuttonbn_logs} ";
        $sql .= "WHERE bigbluebuttonbnid = ? AND userid = ? AND log = ?";
        $logs =
            $DB->get_records_sql($sql, array($bigbluebuttonbn->id, $this->userid, bbb_constants::BIGBLUEBUTTON_LOG_EVENT_SUMMARY));

        switch ($rule) {
            case 'completionattendance':
                if (!$logs) {
                    // As completion by attendance was required, the activity hasn't been completed.
                    return false;
                }
                $attendancecount = 0;
                foreach ($logs as $log) {
                    $summary = json_decode($log->meta);
                    $attendancecount += $summary->data->duration;
                }
                $attendancecount /= 60;
                $value =
                    isset($bigbluebuttonbn->completionattendance) && $bigbluebuttonbn->completionattendance <= $attendancecount;
                break;
            case 'completionengagementchats':
                if (!$logs) {
                    // As completion by engagement with chat was required, the activity hasn't been completed.
                    return false;
                }
                $engagementchatscount = 0;
                foreach ($logs as $log) {
                    $summary = json_decode($log->meta);
                    $engagementchatscount += $summary->data->engagement->chats;
                }
                $value = isset($bigbluebuttonbn->completionengagementchats) &&
                    $bigbluebuttonbn->completionengagementchats <= $engagementchatscount;
                break;
            case 'completionengagementtalks':
                if (!$logs) {
                    // As completion by engagement with talk was required, the activity hasn't been completed.
                    return false;
                }
                $engagementtalkscount = 0;
                foreach ($logs as $log) {
                    $summary = json_decode($log->meta);
                    $engagementtalkscount += $summary->data->engagement->talks;
                }
                $value = isset($bigbluebuttonbn->completionengagementtalks) &&
                    $bigbluebuttonbn->completionengagementtalks <= $engagementtalkscount;
                break;
        }
        return $value ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;

    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completionattendance', 'completionengagementchats', 'completionengagementtalks'];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     * @throws \coding_exception
     */
    public function get_custom_rule_descriptions(): array {
        $completionengagementchats = $this->cm->customdata['customcompletionrules']['completionengagementchats'] ?? 0;
        $completionengagementtalks = $this->cm->customdata['customcompletionrules']['completionengagementtalks'] ?? 0;
        $completionattendance = $this->cm->customdata['customcompletionrules']['completionattendance'] ?? 0;
        return [
            'completionengagementchats' => get_string('completionengagementchatsdesc', 'mod_bigbluebuttonbn',
                $completionengagementchats),
            'completionengagementtalks' => get_string('completionengagementtalksdesc', 'mod_bigbluebuttonbn',
                $completionengagementtalks),
            'completionattendance' => get_string('completionattendancedesc', 'mod_bigbluebuttonbn',
                $completionattendance),
        ];
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionengagementchats',
            'completionengagementtalks',
            'completionattendance',
        ];
    }
}