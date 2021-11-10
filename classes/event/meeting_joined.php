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

namespace mod_bigbluebuttonbn\event;

/**
 * The mod_bigbluebuttonbn meeting joined event, triggered when the user joins the session.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class meeting_joined extends base
{
    /**
     * Init method.
     * @param string $crud
     * @param int $edulevel
     */
    protected function init($crud = 'r', $edulevel = self::LEVEL_PARTICIPATING) {
        parent::init($crud, $edulevel);
        $this->description = "The user with id '##userid' has joined a bigbluebutton meeting for ".
            "the bigbluebuttonbn activity with id '##objectid' for the course id ".
            "'##courseid'.";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_meeting_joined', 'bigbluebuttonbn');
    }

    /**
     * Return objectid mapping.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'bigbluebuttonbn', 'restore' => 'bigbluebuttonbn'];
    }
}
