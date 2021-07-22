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
use mod_bigbluebuttonbn\bigbluebutton\recordings\recording_proxy;
use mod_bigbluebuttonbn\local\helpers\instance;
use mod_bigbluebuttonbn\local\helpers\recording as recording_broker;

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
    /**
     * Constructor.
     *
     * @param array $recording
     * @param array $bbbsession
     * @throws \moodle_exception
     */
    public function __construct($recording, $bbbsession) {
        $editable = static::check_capability($bbbsession);
        $displayvalue =
            format_string($this->get_recording_value($recording, $bbbsession)
                , true, array('context' => \context_module::instance($bbbsession['cm']->id)));

        // Hack here: the ID is the recordID and the meeting ID.
        parent::__construct('mod_bigbluebuttonbn', static::get_type(),
            $recording['recordID'] . ',' . $recording['meetingID'], $editable,
            $displayvalue, $displayvalue);
    }

    /**
     * Check user can access and or modify this item
     *
     * @param array $bbbsession
     * @return bool
     * @throws \moodle_exception
     */
    protected static function check_capability($bbbsession) {
        global $USER;
        if (!can_access_course($bbbsession['course'], $USER)) {
            throw new moodle_exception('noaccess', 'mod_bigbluebuttonbn');
        }
        if (!$bbbsession['managerecordings']) {
            return false;
        }
        return true;
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
     * @param array $bbbsession
     * @return mixed
     */
    abstract public function get_recording_value($recording, $bbbsession);

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
        global $DB;
        list($recordingid, $meetingid, $courseid, $bbbid) = static::get_info_fromid($itemid);
        // Retrieve a bigbluebuttonbn instance from the target $bbbid and instantiate a handler.
        $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $bbbid), '*', MUST_EXIST);
        // Fetch the recordings for the given recordingid.
        $recordings = recording_proxy::bigbluebutton_fetch_recordings([$recordingid]);
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
                'status' => recording_proxy::bigbluebutton_update_recording_imported(
                    $recording['imported'],
                    $meta
                )
            );
        }

        // As the recordingid was not identified as imported recording link, execute update on a real recording.
        // (No need to update imported links as the update only affects the actual recording).
        // Execute update on actual recording.
        return array(
            'status' => recording_broker::bigbluebutton_update_recordings(
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
        [
            'bbbsession' => $bbbsession,
            'context' => $context,
            'enabledfeatures' => $enabledfeatures,
            'typeprofiles' => $typeprofiles,
        ] = instance::get_session_from_id($bbbid);
        require_login($bbbsession['course']);
        $recording = static::get_recording($itemid);

        $success = static::edit_recording($recording, static::get_type(), $value);
        // Refresh recording.
        // TODO: we need to reduce the number of calls to the server.
        $recording = static::get_recording($itemid);
        return new static($recording, $bbbsession);
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
