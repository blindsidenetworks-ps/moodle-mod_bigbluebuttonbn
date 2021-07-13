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

use core\output\inplace_editable;
use lang_string;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\helpers\recording;
use moodle_exception;

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
     * @param array $recording
     * @param instance $instance
     */
    public function __construct($recording, instance $instance) {
        $this->instance = $instance;

        $editable = $this->check_capability();
        $displayvalue = format_string(
            $this->get_recording_value($recording),
            true,
            [
                'context' => $instance->get_context(),
            ]
        );

        // Hack here: the ID is the recordID and the meeting ID.
        parent::__construct(
            'mod_bigbluebuttonbn',
            static::get_type(),
            $recording['recordID'] . ',' . $recording['meetingID'],
            $editable,
            $displayvalue,
            $displayvalue
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
     * @param array $recording
     * @return mixed
     */
    abstract public function get_recording_value($recording);

    /**
     * Get all necessary info from itemid
     *
     * @param string $itemid
     * @return array
     */
    public static function get_info_fromid($itemid) {
        list($recordingid, $meetingid) = explode(',', $itemid);
        list($meeting, $courseid, $bbbid)  = explode('-', $meetingid);
        return [$recordingid, $meetingid, $courseid, $bbbid];
    }

    /**
     * Get recording from the recording ID / Meeting ID
     *
     * @param string $itemid
     * @return false|mixed matching recording
     */
    public static function get_recording($itemid) {
        list($recordingid, $meetingid, $courseid, $bbbid) = static::get_info_fromid($itemid);
        $recordings = recording::bigbluebuttonbn_get_recordings_array([$meetingid], [$recordingid]);
        if (!empty($recordings)) {
            return reset($recordings);
        }
        return false;
    }

    /**
     * Edit recording
     *
     * @param array $recording
     * @param string $metainfoname
     * @param mixed $value
     * @return array
     */
    public static function edit_recording($recording, $metainfoname, $value) {
        // Here we use something similar to the broker.
        // TODO: remove the broker and use some sort of interface.
        $meta = [$metainfoname => $value];
        if ($recording['imported']) {
            // Execute update on imported recording link.
            return array(
                'status' => recording::bigbluebuttonbn_update_recording_imported(
                    $recording['imported'],
                    $meta
                )
            );
        }

        // As the recordingid was not identified as imported recording link, execute update on a real recording.
        // (No need to update imported links as the update only affects the actual recording).
        // Execute update on actual recording.
        return array(
            'status' => recording::bigbluebuttonbn_update_recordings(
                $recording['recordID'],
                $meta
            )
        );
    }

    /**
     * Update the recording with the new value
     *
     * @param int $itemid
     * @param mixed $value
     * @return recording_editable
     */
    public static function update($itemid, $value) {
        list($recordingid, $meetingid, $courseid, $bbbid) = static::get_info_fromid($itemid);
        $instance = instance::get_from_instanceid($bbbid);

        require_login($instance->get_course());
        $recording = static::get_recording($itemid);

        $success = static::edit_recording($recording, static::get_type(), $value);
        // Refresh recording.
        // TODO: we need to reduce the number of calls to the server.
        $recording = static::get_recording($itemid);
        return new static($recording, $instance);
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
}
