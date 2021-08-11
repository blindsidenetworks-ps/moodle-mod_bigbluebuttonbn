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
 * The recordings_data.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David  (laurent.david [at] call-learning [dt] fr)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\local\bigbluebutton\recordings;

use html_writer;
use mod_bigbluebuttonbn\instance;
use stdClass;
use mod_bigbluebuttonbn\local\bigbluebutton;
use mod_bigbluebuttonbn\local\view;
use mod_bigbluebuttonbn\output\recording_description_editable;
use mod_bigbluebuttonbn\output\recording_name_editable;
use mod_bigbluebuttonbn\plugin;

defined('MOODLE_INTERNAL') || die();

/**
 * Utility class for preparing recordings as data for being consumed by renderers.
 *
 * Utility class for recording helper
 * @package mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recording_data {

    /**
     * Helper function builds a row for the data used by the recording table.
     *
     * @param instance $instance
     * @param stdClass $rec a bigbluebuttonbn_recordings row
     * @param array $tools
     *
     * @return stdClass
     */
    public static function row($instance, $rec,
        $tools = ['protect', 'publish', 'delete']) {
        global $OUTPUT, $PAGE;
        if (!self::include_recording_table_row($instance, $rec)) {
            return null;
        }
        $rowdata = new stdClass();
        // Set recording_types.
        $rowdata->playback = self::row_types($rec, $instance);
        // Set activity name.
        $recordingname = new recording_name_editable($rec, $instance);
        $rowdata->recording = $PAGE->get_renderer('core')
            ->render_from_template('core/inplace_editable', $recordingname->export_for_template($OUTPUT));
        // Set activity description.
        $recordingdescription = new recording_description_editable($rec, $instance);
        $rowdata->description = $PAGE->get_renderer('core')
            ->render_from_template('core/inplace_editable', $recordingdescription->export_for_template($OUTPUT));

        if (self::preview_enabled($instance)) {
            // Set recording_preview.
            $rowdata->preview = self::row_preview($rec->recording);
        }
        // Set date.
        $rowdata->date = self::row_date($rec->recording);
        // Set formatted date.
        $rowdata->date_formatted = self::row_date_formatted($rowdata->date);
        // Set formatted duration.
        $rowdata->duration_formatted = $rowdata->duration = self::row_duration($rec->recording);
        // Set actionbar, if user is allowed to manage recordings.
        if ($instance->can_manage_recordings()) {
            $rowdata->actionbar = self::row_actionbar($rec, $tools);
        }
        return $rowdata;
    }

    /**
     * Helper function converts recording date used in row for the data used by the recording table.
     *
     * @param array $recording
     *
     * @return integer
     */
    public static function row_date($recording) {
        if (!isset($recording['startTime'])) {
            return 0;
        }
        return floatval($recording['startTime']);
    }

    /**
     * Helper function evaluates if recording preview should be included.
     *
     * @param instance $instance
     *
     * @return boolean
     */
    public static function preview_enabled($instance) {
        return (bigbluebutton::bigbluebuttonbn_get_server_version() >= 1.0
            && $instance->get_instance_var('recordings_preview') == '1');
    }

    /**
     * Helper function converts recording duration used in row for the data used by the recording table.
     *
     * @param array $recording
     *
     * @return integer
     */
    public static function row_duration($recording) {
        foreach (array_values($recording['playbacks']) as $playback) {
            // Ignore restricted playbacks.
            if (array_key_exists('restricted', $playback) && strtolower($playback['restricted']) == 'true') {
                continue;
            }
            // Take the lenght form the fist playback with an actual value.
            if (!empty($playback['length'])) {
                return intval($playback['length']);
            }
        }
        return 0;
    }

    /**
     * Helper function format recording date used in row for the data used by the recording table.
     *
     * @param integer $starttime
     *
     * @return string
     */
    public static function row_date_formatted($starttime) {
        global $USER;
        $starttime = $starttime - ($starttime % 1000);
        // Set formatted date.
        $dateformat = get_string('strftimerecentfull', 'langconfig') . ' %Z';
        return userdate($starttime / 1000, $dateformat, usertimezone($USER->timezone));
    }

    /**
     * Helper function builds recording actionbar used in row for the data used by the recording table.
     *
     * @param stdClass $rec a bigbluebuttonbn_recordings row
     * @param array $tools
     *
     * @return string
     */
    public static function row_actionbar($rec, $tools) {
        $actionbar = '';
        foreach ($tools as $tool) {
            $buttonpayload =
                self::row_actionbar_payload($rec->recording, $tool);
            if ($tool == 'protect') {
                if ($rec->imported) {
                    $buttonpayload['disabled'] = 'disabled';
                }
                if (!isset($rec->recording['protected'])) {
                    $buttonpayload['disabled'] = 'invisible';
                }
            }
            if ($tool == 'publish') {
                if ($rec->imported) {
                    $buttonpayload['disabled'] = 'disabled';
                }
            }
            if (!$rec->imported && ($tool == 'delete' || $tool == 'publish') ) {
                $buttonpayload['requireconfirmation'] = true;
            }
            $actionbar .= view::actionbar_render_button($rec, $buttonpayload);
        }
        $head = html_writer::start_tag('div', array(
            'id' => 'recording-actionbar-' . $rec->recording['recordID'],
            'data-recid' => $rec->id,
            'data-recordingid' => $rec->recording['recordID'],
            'data-additionaloptions' => $rec->recording['meetingID']));
        $tail = html_writer::end_tag('div');
        return $head . $actionbar . $tail;
    }

    /**
     * Helper function returns the corresponding payload for an actionbar button used in row
     * for the data used by the recording table.
     *
     * @param array $recording
     * @param array $tool
     *
     * @return array
     */
    public static function row_actionbar_payload($recording, $tool) {
        if ($tool == 'protect') {
            $protected = 'false';
            if (isset($recording['protected'])) {
                $protected = $recording['protected'];
            }
            return self::row_action_protect($protected);
        }
        if ($tool == 'publish') {
            return self::row_action_publish($recording['published']);
        }
        return array('action' => $tool, 'tag' => $tool);
    }

    /**
     * Helper function returns the payload for protect action button used in row
     * for the data used by the recording table.
     *
     * @param string $protected
     *
     * @return array
     */
    public static function row_action_protect($protected) {
        if ($protected == 'true') {
            return array('action' => 'unprotect', 'tag' => 'lock');
        }
        return array('action' => 'protect', 'tag' => 'unlock');
    }

    /**
     * Helper function returns the payload for publish action button used in row
     * for the data used by the recording table.
     *
     * @param string $published
     *
     * @return array
     */
    public static function row_action_publish($published) {
        if ($published == 'true') {
            return array('action' => 'unpublish', 'tag' => 'hide');
        }
        return array('action' => 'publish', 'tag' => 'show');
    }

    /**
     * Helper function builds recording preview used in row for the data used by the recording table.
     *
     * @param array $recording
     *
     * @return string
     */
    public static function row_preview($recording) {
        $options = array('id' => 'preview-' . $recording['recordID']);
        if ($recording['published'] === 'false') {
            $options['hidden'] = 'hidden';
        }
        $recordingpreview = html_writer::start_tag('div', $options);
        foreach ($recording['playbacks'] as $playback) {
            if (isset($playback['preview'])) {
                $recordingpreview .= self::row_preview_images($playback);
                break;
            }
        }
        $recordingpreview .= html_writer::end_tag('div');
        return $recordingpreview;
    }

    /**
     * Helper function builds element with actual images used in recording preview row based on a selected playback.
     *
     * @param array $playback
     *
     * @return string
     */
    public static function row_preview_images($playback) {
        global $CFG;
        $recordingpreview = html_writer::start_tag('div', array('class' => 'container-fluid'));
        $recordingpreview .= html_writer::start_tag('div', array('class' => 'row'));
        foreach ($playback['preview'] as $image) {
            if ($CFG->bigbluebuttonbn_recordings_validate_url &&
                !bigbluebutton::bigbluebuttonbn_is_valid_resource(trim($image['url']))) {
                return '';
            }
            $recordingpreview .= html_writer::start_tag('div', array('class' => ''));
            $recordingpreview .= html_writer::empty_tag(
                'img',
                array('src' => trim($image['url']) . '?' . time(), 'class' => 'recording-thumbnail pull-left')
            );
            $recordingpreview .= html_writer::end_tag('div');
        }
        $recordingpreview .= html_writer::end_tag('div');
        $recordingpreview .= html_writer::start_tag('div', array('class' => 'row'));
        $recordingpreview .= html_writer::tag(
            'div',
            get_string('view_recording_preview_help', 'bigbluebuttonbn'),
            array('class' => 'text-center text-muted small')
        );
        $recordingpreview .= html_writer::end_tag('div');
        $recordingpreview .= html_writer::end_tag('div');
        return $recordingpreview;
    }

    /**
     * Helper function renders recording types to be used in row for the data used by the recording table.
     *
     * @param stdClass $rec a bigbluebuttonbn_recordings row
     * @param instance $instance
     *
     * @return string
     */
    public static function row_types($rec, $instance) {
        $dataimported = 'false';
        $title = '';
        if ($rec->imported) {
            $dataimported = 'true';
            $title = get_string('view_recording_link_warning', 'bigbluebuttonbn');
        }
        $visibility = '';
        if ($rec->recording['published'] === 'false') {
            $visibility = 'hidden ';
        }
        $id = 'playbacks-' . $rec->recording['recordID'];
        $recordingtypes = html_writer::start_tag('div', array('id' => $id, 'data-recid' => $rec->id,
            'data-imported' => $dataimported, 'data-additionaloptions' => $rec->recording['meetingID'],
            'data-recordingid' => $rec->recording['recordID'], 'title' => $title, $visibility => $visibility));
        foreach ($rec->recording['playbacks'] as $playback) {
            $recordingtypes .= self::row_type($rec, $instance, $playback);
        }
        $recordingtypes .= html_writer::end_tag('div');
        return $recordingtypes;
    }

    /**
     * Helper function renders the link used for recording type in row for the data used by the recording table.
     *
     * @param stdClass $rec a bigbluebuttonbn_recordings row
     * @param instance $instance
     * @param array $playback
     *
     * @return string
     */
    public static function row_type($rec, $instance, $playback) {
        global $CFG, $OUTPUT;
        if (!self::include_recording_data_row_type($rec, $instance, $playback)) {
            return '';
        }
        $text = self::type_text($playback['type']);
        $href = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_view.php?action=play&bn=' . $instance->get_instance_id() .
        '&rid=' . $rec->id . '&rtype=' . $playback['type'];
        // SECURITY WARNING.
        // A parameter href with the URL to the recording is added only when the BBB server doesn't implement "protected recording".
        // This is equivalent to use an a tag with href and target="_blank". The vulnerability is in BBB and not Moodle.
        // Using of a proxy that protects the recordings such as Scalelite (v1.2 or later by Blindside Networks) is encouraged.
        if (!isset($rec->recording['protected']) || $rec->recording['protected'] === 'false') {
            $href .= '&href=' . urlencode(trim($playback['url']));
        }
        $linkattributes = array(
            'id' => 'recording-play-' . $playback['type'] . '-' . $rec->id,
            'class' => 'btn btn-sm btn-default',
            'onclick' => 'M.mod_bigbluebuttonbn.recordings.recordingPlay(this);',
            'data-action' => 'play',
            'data-target' => $playback['type'],
            'data-href' => $href,
        );
        if ($CFG->bigbluebuttonbn_recordings_validate_url && !plugin::bigbluebuttonbn_is_bn_server()
            && !bigbluebutton::bigbluebuttonbn_is_valid_resource(trim($playback['url']))) {
            $linkattributes['class'] = 'btn btn-sm btn-warning';
            $linkattributes['title'] = get_string('view_recording_format_errror_unreachable', 'bigbluebuttonbn');
            unset($linkattributes['data-href']);
        }
        return $OUTPUT->action_link('#', $text, null, $linkattributes) . '&#32;';
    }

    /**
     * Helper function to handle yet unknown recording types
     *
     * @param string $playbacktype : for now presentation, video, statistics, capture, notes, podcast
     *
     * @return string the matching language string or a capitalised version of the provided string
     */
    public static function type_text($playbacktype) {
        // Check first if string exists, and if it does'nt just default to the capitalised version of the string.
        $text = ucwords($playbacktype);
        $typestringid = 'view_recording_format_' . $playbacktype;
        if (get_string_manager()->string_exists($typestringid, 'bigbluebuttonbn')) {
            $text = get_string($typestringid, 'bigbluebuttonbn');
        }
        return $text;
    }

    /**
     * Helper function renders the name for recording used in row for the data used by the recording table.
     *
     * @param array $recording
     * @param instance $instance
     *
     * @return string
     */
    public static function row_meta_activity($recording, $instance) {
        $payload = array();
        if (self::row_editable($instance)) {
            $payload = array('recordingid' => $recording['recordID'], 'meetingid' => $recording['meetingID'],
                'action' => 'edit', 'tag' => 'edit',
                'target' => 'name');
        }
        $oldsource = 'meta_contextactivity';
        if (isset($recording[$oldsource])) {
            $metaname = trim($recording[$oldsource]);
            return self::row_text($recording, $metaname, $oldsource,
                $payload);
        }
        $newsource = 'meta_bbb-recording-name';
        if (isset($recording[$newsource])) {
            $metaname = trim($recording[$newsource]);
            return self::row_text($recording, $metaname, $newsource,
                $payload);
        }
        $metaname = trim($recording['meetingName']);
        return self::row_text($recording, $metaname, $newsource,
            $payload);
    }

    /**
     * Helper function renders the description for recording used in row for the data used by the recording table.
     *
     * @param array $recording
     * @param instance $instance
     *
     * @return string
     */
    public static function row_meta_description($recording, $instance) {
        $payload = array();
        if (self::row_editable($instance)) {
            $payload = array('recordingid' => $recording['recordID'], 'meetingid' => $recording['meetingID'],
                'action' => 'edit', 'tag' => 'edit',
                'target' => 'description');
        }
        $oldsource = 'meta_contextactivitydescription';
        if (isset($recording[$oldsource])) {
            $metadescription = trim($recording[$oldsource]);
            return self::row_text($recording, $metadescription,
                $oldsource, $payload);
        }
        $newsource = 'meta_bbb-recording-description';
        if (isset($recording[$newsource])) {
            $metadescription = trim($recording[$newsource]);
            return self::row_text($recording, $metadescription,
                $newsource, $payload);
        }
        return self::row_text($recording, '', $newsource, $payload);
    }

    /**
     * Helper function renders text element for recording used in row for the data used by the recording table.
     *
     * @param array $recording
     * @param string $text
     * @param string $source
     * @param array $data
     *
     * @return string
     */
    public static function row_text($recording, $text, $source, $data) {
        $htmltext = '<span>' . htmlentities($text) . '</span>';
        if (empty($data)) {
            return $htmltext;
        }
        return $htmltext;
    }

    /**
     * Helper function evaluates if a row for the data used by the recording table is editable.
     *
     * @param instance $instance
     *
     * @return boolean
     */
    public static function row_editable($instance) {
        // Since the request to BBB are cached, it is safe to use the wrapper to check the server version.
        return $instance->can_manage_recordings()
            && (bigbluebutton::bigbluebuttonbn_get_server_version() >= 1.0 || $instance->is_blindside_network_server());
    }

    /**
     * Helper function evaluates if recording row should be included in the table.
     *
     * @param instance $instance
     * @param stdClass $rec a bigbluebuttonbn_recordings row
     *
     * @return boolean
     */
    public static function include_recording_table_row($instance, $rec) {
        // Exclude unpublished recordings, only if user has no rights to manage them.
        if ($rec->recording['published'] != 'true' && !$instance->can_manage_recordings()) {
            return false;
        }
        // Imported recordings are always shown as long as they are published.
        if ($rec->imported) {
            return true;
        }
        // Administrators and moderators are always allowed.
        if ($instance->is_admin() || $instance->is_moderator()) {
            return true;
        }
        // When groups are enabled, exclude those to which the user doesn't have access to.
        if ($instance->uses_groups()) {
            return $rec->recording['meetingID'] === $instance->get_meeting_id();
        }
        return true;
    }

    /**
     * Helper function renders the link used for recording type in row for the data used by the recording table.
     *
     * @param stdClass $rec a bigbluebuttonbn_recordings row
     * @param instance $instance
     * @param array $playback
     *
     * @return boolean
     */
    public static function include_recording_data_row_type($rec, $instance, $playback) {
        // All types that are not restricted are included.
        if (array_key_exists('restricted', $playback) && strtolower($playback['restricted']) == 'false') {
            return true;
        }
        // All types that are not statistics are included.
        if ($playback['type'] != 'statistics') {
            return true;
        }
        // Exclude imported recordings.
        if ($rec->imported) {
            return false;
        }
        // Exclude non moderators.
        if (!$instance->is_admin() && !$instance->is_moderator()) {
            return false;
        }
        return true;
    }
}