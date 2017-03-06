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
 * The mod_bigbluebuttonbn recording imported event.
 *
 * @package   mod_bigbluebuttonbn
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2014-2016 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

namespace mod_bigbluebuttonbn\event;
defined('MOODLE_INTERNAL') || die();

class bigbluebuttonbn_recording_imported extends \core\event\base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'bigbluebuttonbn';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return "Recording imported";
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $rid = isset($this->other['rid']) ? $this->other['rid'] : '';
        $a = (object) array('userid' => $this->userid, 'recordingid' => $rid, 'courseid' => $this->contextinstanceid);
        return "The user with id '$a->userid' has imported a recording with id '$a->recordingid' into the course id '$a->courseid'.";
    }

    /**
     * Return the legacy event log data.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        return(array($this->courseid, 'bigbluebuttonbn', 'recording imported',
                'view.php?pageid=' . $this->objectid, "Recording imported", $this->contextinstanceid));
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/bigbluebuttonbn/view.php', array('id' => $this->objectid));
    }

    public static function get_objectid_mapping() {
        return array('db' => 'bigbluebuttonbn', 'restore' => 'bigbluebuttonbn');
    }
}
