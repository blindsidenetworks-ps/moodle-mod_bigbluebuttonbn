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
 * The view helpers
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\local;

use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\helpers\recording;
use pix_icon;

/**
 * The view helpers
 *
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class view {

    /**
     * Helper function returns array with the instance settings used in views based on id.
     *
     * @param string $id
     *
     * @return array
     */
    public static function bigbluebuttonbn_view_instance_id($id) {
        global $DB;
        $cm = get_coursemodule_from_id('bigbluebuttonbn', $id, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $cm->instance), '*', MUST_EXIST);
        return array('cm' => $cm, 'course' => $course, 'bigbluebuttonbn' => $bigbluebuttonbn);
    }

    /**
     * Helper function returns array with the instance settings used in views based on bigbluebuttonbnid.
     *
     * @param int $bigbluebuttonbnid
     *
     * @return array
     */
    public static function bigbluebuttonbn_view_instance_bigbluebuttonbn($bigbluebuttonbnid) {
        global $DB;
        $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $bigbluebuttonbnid), '*', MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $bigbluebuttonbn->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('bigbluebuttonbn', $bigbluebuttonbn->id, $course->id, false, MUST_EXIST);
        return array('cm' => $cm, 'course' => $course, 'bigbluebuttonbn' => $bigbluebuttonbn);
    }

    /**
     * Helper function returns array with the instance settings used in views.
     *
     * @param string $id
     * @param object $bigbluebuttonbnid
     *
     * @return array
     */
    public static function bigbluebuttonbn_view_validator($id, $bigbluebuttonbnid) {
        if ($id) {
            return self::bigbluebuttonbn_view_instance_id($id);
        }
        if ($bigbluebuttonbnid) {
            return self::bigbluebuttonbn_view_instance_bigbluebuttonbn($bigbluebuttonbnid);
        }
    }

    /**
     * Helper function returns time in a formatted string.
     *
     * @param integer $time
     *
     * @return string
     */
    public static function bigbluebuttonbn_format_activity_time($time) {
        global $CFG;
        require_once($CFG->dirroot . '/calendar/lib.php');
        $activitytime = '';
        if ($time) {
            $activitytime = calendar_day_representation($time) . ' ' .
                get_string('mod_form_field_notification_msg_at', 'bigbluebuttonbn') . ' ' .
                calendar_time_representation($time);
        }
        return $activitytime;
    }

    /**
     * Helper function render a button for the recording action bar
     *
     * @param array $recording
     * @param array $data
     *
     * @return string
     */
    public static function bigbluebuttonbn_actionbar_render_button($recording, $data) {
        global $PAGE;
        if (empty($data)) {
            return '';
        }
        $target = $data['action'];
        if (isset($data['target'])) {
            $target .= '-' . $data['target'];
        }
        $id = 'recording-' . $target . '-' . $recording['recordID'];
        if ((boolean) config::get('recording_icons_enabled')) {
            // With icon for $manageaction.
            $iconattributes = array('id' => $id, 'class' => 'iconsmall');
            $linkattributes = array(
                'id' => $id,
                'data-action' => $data['action'],
                'data-require-confirmation' => !empty($data['requireconfirmation']),
            );
            if (!isset($recording['imported'])) {
                $linkattributes['data-links'] = recording::bigbluebuttonbn_count_recording_imported_instances(
                    $recording['recordID']
                );
            }
            if (isset($data['disabled'])) {
                $iconattributes['class'] .= ' fa-' . $data['disabled'];
                $linkattributes['class'] = 'disabled';
            }
            $icon = new pix_icon(
                'i/' . $data['tag'],
                get_string('view_recording_list_actionbar_' . $data['action'], 'bigbluebuttonbn'),
                'moodle',
                $iconattributes
            );
            return $PAGE->get_renderer('core')->action_icon('#', $icon, null, $linkattributes, false);
        }
        // With text for $manageaction.
        $linkattributes = array('title' => get_string($data['tag']), 'class' => 'btn btn-xs btn-danger');
        return $PAGE->get_renderer('core')->action_link('#', get_string($data['action']), null, $linkattributes);
    }

    /**
     * Helper function renders the link used for recording type in row for the data used by the recording table.
     *
     * @param array $recording
     * @param instance $instance
     * @param array $playback
     *
     * @return boolean
     */
    public static function bigbluebuttonbn_include_recording_data_row_type($recording, $instance, $playback) {
        // All types that are not restricted are included.
        if (array_key_exists('restricted', $playback) && strtolower($playback['restricted']) == 'false') {
            return true;
        }
        // All types that are not statistics are included.
        if ($playback['type'] != 'statistics') {
            return true;
        }
        // Exclude imported recordings.
        if (isset($recording['imported'])) {
            return false;
        }
        // Exclude non moderators.
        if (!$instance->is_admin() && !$instance->is_moderator()) {
            return false;
        }
        return true;
    }
}
