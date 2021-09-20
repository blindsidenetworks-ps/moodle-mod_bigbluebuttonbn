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

namespace mod_bigbluebuttonbn\local\bigbluebutton\recordings;

use mod_bigbluebuttonbn\output\recording_row_actionbar;
use mod_bigbluebuttonbn\output\recording_row_playback;
use mod_bigbluebuttonbn\output\recording_row_preview;
use stdClass;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\output\recording_description_editable;
use mod_bigbluebuttonbn\output\recording_name_editable;
use mod_bigbluebuttonbn\recording;


/**
 * The recordings_data.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David  (laurent.david [at] call-learning [dt] fr)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class recording_data {

    /**
     * Helper function builds a row for the data used by the recording table.
     *
     * TODO: replace this with templates whenever possible so we just
     * return the data via the API.
     *
     * @param instance $instance
     * @param recording $rec a recording row
     * @param null|array $tools
     * @return stdClass
     */
    public static function row(instance $instance, recording $rec, ?array $tools = null): ?stdClass {
        global $PAGE;

        $renderer = $PAGE->get_renderer('mod_bigbluebuttonbn');
        foreach ($tools as $key => $tool) {
            if (!$instance->can_perform_on_recordings($tool)) {
                unset($tools[$key]);
            }
        }
        if (!self::include_recording_table_row($instance, $rec)) {
            return null;
        }
        $rowdata = new stdClass();

        // Set recording_playback.

        $recordingplayback = new recording_row_playback($rec, $instance);
        $rowdata->playback = $renderer->render($recordingplayback);

        // Set activity name.
        $recordingname = new recording_name_editable($rec, $instance);
        $rowdata->recording = $renderer->render_inplace_editable($recordingname);

        // Set activity description.
        $recordingdescription = new recording_description_editable($rec, $instance);
        $rowdata->description = $renderer->render_inplace_editable($recordingdescription);

        if (self::preview_enabled($instance)) {
            // Set recording_preview.
            $rowdata->preview = '';
            if ($rec->get('playbacks')) {
                $rowpreview = new recording_row_preview($rec);
                $rowdata->preview = $renderer->render($rowpreview);
            }
        }
        // Set date.
        $starttime = $rec->get('starttime');
        $rowdata->date = !is_null($starttime) ? floatval($starttime) : 0;
        // Set duration.
        $rowdata->duration = self::row_duration($rec);
        // Set actionbar, if user is allowed to manage recordings.
        if ($instance->can_manage_recordings()) {
            $actionbar = new recording_row_actionbar($rec, $tools);
            $rowdata->actionbar = $renderer->render($actionbar);
        }
        return $rowdata;
    }

    /**
     * Helper function converts recording date used in row for the data used by the recording table.
     *
     * @param recording $recording
     * @return int
     */

    /**
     * Helper function evaluates if recording preview should be included.
     *
     * @param instance $instance
     * @return boolean
     */
    public static function preview_enabled(instance $instance): bool {
        return $instance->get_instance_var('recordings_preview') == '1';
    }

    /**
     * Helper function converts recording duration used in row for the data used by the recording table.
     *
     * @param recording $recording
     * @return int
     */
    protected static function row_duration(recording $recording): int {
        $playbacks = $recording->get('playbacks');
        if (empty($playbacks)) {
            return 0;
        }
        foreach ($playbacks as $playback) {
            // Ignore restricted playbacks.
            if (array_key_exists('restricted', $playback) && strtolower($playback['restricted']) == 'true') {
                continue;
            }

            // Take the length form the fist playback with an actual value.
            if (!empty($playback['length'])) {
                return intval($playback['length']);
            }
        }
        return 0;
    }

    /**
     * Helper function to handle yet unknown recording types
     *
     * @param string $playbacktype : for now presentation, video, statistics, capture, notes, podcast
     * @return string the matching language string or a capitalised version of the provided string
     */
    public static function type_text(string $playbacktype): string {
        // Check first if string exists, and if it does'nt just default to the capitalised version of the string.
        $text = ucwords($playbacktype);
        $typestringid = 'view_recording_format_' . $playbacktype;
        if (get_string_manager()->string_exists($typestringid, 'bigbluebuttonbn')) {
            $text = get_string($typestringid, 'bigbluebuttonbn');
        }
        return $text;
    }

    /**
     * Helper function evaluates if recording row should be included in the table.
     *
     * @param instance $instance
     * @param recording $rec a bigbluebuttonbn_recordings row
     * @return boolean
     */
    protected static function include_recording_table_row(instance $instance, recording $rec): bool {
        // Exclude unpublished recordings, only if user has no rights to manage them.
        if (!$rec->get('published') && !$instance->can_manage_recordings()) {
            return false;
        }
        // Imported recordings are always shown as long as they are published.
        if ($rec->get('imported')) {
            return true;
        }
        // Administrators and moderators are always allowed.
        if ($instance->is_admin() || $instance->is_moderator()) {
            return true;
        }
        // When groups are enabled, exclude those to which the user doesn't have access to.
        if ($instance->uses_groups()) {
            return intval($rec->get('groupid')) === intval($instance->get_group_id());
        }
        return true;
    }
}
