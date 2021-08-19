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
 * Renderer for recording name in place editable.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David  (laurent.david [at] call-learning [dt] fr)
 */

namespace mod_bigbluebuttonbn\output;

use lang_string;
use moodle_exception;
use core\output\inplace_editable;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\bigbluebutton\recordings\recording;
use mod_bigbluebuttonbn\local\proxy\bigbluebutton_proxy;
use mod_bigbluebuttonbn\local\proxy\recording_proxy;
use stdClass;

/**
 * Renderer for recording in place editable.
 *
 * Generic class
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David  (laurent.david [at] call-learning [dt] fr)
 */
abstract class recording_editable extends \core\output\inplace_editable {

    /** @var instance The bbb instance */
    protected $instance;

    /**
     * Constructor.
     *
     * @param stdClass $rec
     * @param instance $instance
     * @param string $edithint
     * @param string $editlabel
     * @throws moodle_exception
     */
    public function __construct($rec, instance $instance, string $edithint, string $editlabel) {
        $this->instance = $instance;

        $editable = $this->check_capability();
        $displayvalue = format_string(
            $this->get_recording_value($rec),
            true,
            [
                'context' => $instance->get_context(),
            ]
        );

        // Hack here: the ID is the recordID and the meeting ID.
        parent::__construct(
            'mod_bigbluebuttonbn',
            static::get_type(),
            $rec->id . ',' . $rec->recording['meetingID'],
            $editable,
            $displayvalue,
            $displayvalue,
            $edithint,
            $editlabel
        );
    }

    /**
     * Check user can access and or modify this item.
     *
     * @return bool
     * @throws \moodle_exception
     */
    protected function check_capability() {
        global $USER;

        if (!can_access_course($this->instance->get_course(), $USER)) {
            throw new moodle_exception('noaccess', 'mod_bigbluebuttonbn');
        }

        return $this->instance->can_manage_recordings();
    }

    /**
     *  Get the type of editable
     */
    protected static function get_type() {
        return '';
    }

    /**
     * Get the real recording value
     *
     * @param stdClass $rec
     * @return mixed
     */
    abstract public function get_recording_value($rec);

    /**
     * Get all necessary info from itemid
     *
     * @param string $itemid
     * @return array
     */
    public static function get_info_fromid($itemid) {
        list($recid, $recordingid) = explode(',', $itemid);
        return [$recid, $recordingid];
    }

    /**
     * Update the recording with the new value
     *
     * @param int $itemid
     * @param mixed $value
     * @return recording_editable
     */
    public static function update($itemid, $value) {
        list($recid, $meetingid) = static::get_info_fromid($itemid);
        $rec = recording::read($recid);
        $instance = instance::get_from_instanceid($rec->bigbluebuttonbnid);

        require_login($instance->get_course());

        $rec->recording[static::get_type()] = $value;
        recording::update($rec);

        // Refresh recording.
        // TODO: we need to reduce the number of calls to the server.
        $rec = recording::read($recid);
        return new static($rec, $instance);
    }

    /**
     * Get editable from type
     *
     * @param string $type
     * @return string
     */
    public static function get_editable_class($type) {
        switch ($type) {
            case recording_name_editable::get_type():
                return \mod_bigbluebuttonbn\output\recording_name_editable::class;
                break;
            case recording_description_editable::get_type():
                return \mod_bigbluebuttonbn\output\recording_description_editable::class;
                break;
        }
        return '';
    }

    /**
     * Helper function evaluates if a row for the data used by the recording table is editable.
     *
     * @return boolean
     */
    protected static function row_editable() {
        // Since the request to BBB are cached, it is safe to use the wrapper to check the server version.
        return $this->instance->can_manage_recordings()
            && (bigbluebutton_proxy::get_server_version() >= 1.0 || $this->instance->is_blindside_network_server());
    }
}
