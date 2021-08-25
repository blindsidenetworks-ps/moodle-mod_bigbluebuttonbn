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

use context_course;
use html_writer;
use pix_icon;
use stdClass;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\local\proxy\bigbluebutton_proxy;
use mod_bigbluebuttonbn\output\recording_description_editable;
use mod_bigbluebuttonbn\output\recording_name_editable;
use mod_bigbluebuttonbn\plugin;

class recording_data {

    /**
     * Helper function builds a row for the data used by the recording table.
     *
     * @param instance $instance
     * @param recording $rec a recording row
     * @param null|array $tools
     * @return stdClass
     */
    public static function row(instance $instance, recording $rec, ?array $tools = null): ?stdClass {
        global $OUTPUT, $PAGE;

        if ($tools === null) {
            $tools = ['protect', 'publish', 'delete'];
        }
        $context = context_course::instance($instance->get_course_id());
        foreach ($tools as $key => $tool) {
            if (!$instance->can_perform_on_recordings($tool)) {
                unset($tools[$key]);
            }
        }
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
            $rowdata->preview = self::row_preview($rec);
        }
        // Set date.
        $rowdata->date = self::row_date($rec);
        // Set formatted date.
        $rowdata->date_formatted = self::row_date_formatted($rowdata->date);
        // Set formatted duration.
        $rowdata->duration_formatted = $rowdata->duration = self::row_duration($rec);
        // Set actionbar, if user is allowed to manage recordings.
        if ($instance->can_manage_recordings()) {
            $rowdata->actionbar = self::row_actionbar($rec, $tools);
        }
        return $rowdata;
    }

    /**
     * Helper function converts recording date used in row for the data used by the recording table.
     *
     * @param recording $recording
     * @return int
     */
    public static function row_date(recording $recording): int {
        $starttime = $recording->get('starttime');
        return !is_null($starttime) ? floatval($starttime) : 0;
    }

    /**
     * Helper function evaluates if recording preview should be included.
     *
     * @param instance $instance
     * @return boolean
     */
    public static function preview_enabled(instance $instance): bool {
        return (bigbluebutton_proxy::get_server_version() >= 1.0 && $instance->get_instance_var('recordings_preview') == '1');
    }

    /**
     * Helper function converts recording duration used in row for the data used by the recording table.
     *
     * @param recording $recording
     * @return int
     */
    public static function row_duration(recording $recording): int {
        $playbacks = $recording->get('playbacks');
        if ($playbacks === null) {
            return 0;
        }
        foreach (array_values($playbacks) as $playback) {
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
     * @param int $starttime
     * @return string
     */
    public static function row_date_formatted(int $starttime): string {
        global $USER;

        $starttime = $starttime - ($starttime % 1000);
        // Set formatted date.
        $dateformat = get_string('strftimerecentfull', 'langconfig') . ' %Z';
        return userdate($starttime / 1000, $dateformat, usertimezone($USER->timezone));
    }

    /**
     * Helper function builds recording actionbar used in row for the data used by the recording table.
     *
     * @param recording $rec a recording
     * @param array $tools
     * @return string
     */
    protected static function row_actionbar(recording $rec, $tools): string {
        $actionbar = '';
        foreach ($tools as $tool) {
            if (!empty(self::TOOL_ACTION_DEFINITIONS[$tool])) {
                $buttonpayload = self::TOOL_ACTION_DEFINITIONS[$tool];
                $conditionalhiding = $buttonpayload['hidewhen'] ?? null;
                $disabledwhen = $buttonpayload['disablewhen'] ?? null;
                self::actionbar_set_disabled($buttonpayload, $disabledwhen, $rec, 'disabled');
                self::actionbar_set_disabled($buttonpayload, $conditionalhiding, $rec);
                $actionbar .= self::actionbar_render_button($rec, $buttonpayload);
            }
        }
        $head = html_writer::start_tag('div', [
            'id' => 'recording-actionbar-' . $rec->get('id'),
            'data-recordingid' => $rec->get('id'),
        ]);
        $tail = html_writer::end_tag('div');
        return $head . $actionbar . $tail;
    }

    /**
     * Read the settings for this action and disable or hide the tool from the toolbar
     *
     * @param array $buttonpayload
     * @param string $condition
     * @param recording $rec
     * @param string $value
     */
    private static function actionbar_set_disabled(&$buttonpayload, $condition, $rec, $value = 'invisible') {
        if ($condition) {
            $negates = $condition[0] === '!';
            $conditionalvariable = ltrim($condition, '!');
            if ($rec->get($conditionalvariable) xor $negates) {
                $buttonpayload['disabled'] = $value;
            }
        }
    }

    /**
     * @var array TOOLS_DEFINITION a list of definition for the the specific tools
     */
    const TOOL_ACTION_DEFINITIONS = [
        'protect' => [
            'action' => 'unprotect',
            'icon' => 'lock',
            'hidewhen' => '!protected',
            'disablewhen' => 'imported'
        ],
        'unprotect' => [
            'action' => 'protect',
            'icon' => 'unlock',
            'hidewhen' => '!protected',
            'disablewhen' => 'imported'
        ],
        'publish' => [
            'action' => 'publish',
            'icon' => 'show',
            'hidewhen' => 'published',
            'requireconfirmation' => true,
            'disablewhen' => 'imported'
        ],
        'unpublish' => [
            'action' => 'unpublish',
            'icon' => 'hide',
            'hidewhen' => '!published',
            'requireconfirmation' => true,
            'disablewhen' => 'imported'
        ],
        'delete' => [
            'action' => 'delete',
            'icon' => 'trash',
            'requireconfirmation' => true
        ],
        'import'  => [
            'action' => 'import',
            'icon' => 'import',
        ]
    ];

    /**
     * Helper function builds recording preview used in row for the data used by the recording table.
     *
     * @param recording $recording
     * @return string
     */
    public static function row_preview(recording $recording): string {
        $playbacks = $recording->get('playbacks');
        if ($playbacks === null) {
            return '';
        }
        $options = [
            'id' => 'preview-' . $recording->get('id'),
        ];
        if (!$recording->get('published')) {
            $options['hidden'] = 'hidden';
        }
        $recordingpreview = html_writer::start_tag('div', $options);
        foreach ($playbacks as $playback) {
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
     * @return string
     */
    public static function row_preview_images(array $playback): string {
        global $CFG;
        $recordingpreview = html_writer::start_tag('div', [
            'class' => 'container-fluid',
        ]);
        $recordingpreview .= html_writer::start_tag('div', [
            'class' => 'row',
        ]);
        foreach ($playback['preview'] as $image) {
            if ($CFG->bigbluebuttonbn_recordings_validate_url &&
                !bigbluebutton_proxy::is_remote_resource_valid(trim($image['url']))) {
                return '';
            }
            $recordingpreview .= html_writer::start_tag('div', [
                'class' => '',
            ]);
            $recordingpreview .= html_writer::empty_tag('img', [
                'src' => trim($image['url']) . '?' . time(),
                'class' => 'recording-thumbnail pull-left',
            ]);
            $recordingpreview .= html_writer::end_tag('div');
        }
        $recordingpreview .= html_writer::end_tag('div');
        $recordingpreview .= html_writer::start_tag('div', [
            'class' => 'row',
        ]);
        $recordingpreview .= html_writer::tag('div', get_string('view_recording_preview_help', 'bigbluebuttonbn'), [
            'class' => 'text-center text-muted small',
        ]);
        $recordingpreview .= html_writer::end_tag('div');
        $recordingpreview .= html_writer::end_tag('div');
        return $recordingpreview;
    }

    /**
     * Helper function renders recording types to be used in row for the data used by the recording table.
     *
     * @param recording $rec a recording row
     * @param instance $instance
     * @return string
     */
    public static function row_types(recording $rec, instance $instance): string {
        $dataimported = 'false';
        $title = '';
        if ($rec->get('imported')) {
            $dataimported = 'true';
            $title = get_string('view_recording_link_warning', 'bigbluebuttonbn');
        }
        $visibility = '';
        if (!$rec->get('published')) {
            $visibility = 'hidden ';
        }
        $id = 'playbacks-' . $rec->get('id');
        $recordingtypes = html_writer::start_tag('div', [
            'id' => $id,
            'data-imported' => $dataimported,
            'data-recordingid' => $rec->get('id'),
            'data-additionaloptions' => '',
            'title' => $title,
            $visibility => $visibility,
        ]);
        if ($rec->get('playbacks')) {
            foreach ($rec->get('playbacks') as $playback) {
                $recordingtypes .= self::row_type($rec, $instance, $playback);
            }
        }
        $recordingtypes .= html_writer::end_tag('div');
        return $recordingtypes;
    }

    /**
     * Helper function renders the link used for recording type in row for the data used by the recording table.
     *
     * @param recording $rec a recording row
     * @param instance $instance
     * @param array $playback
     * @return string
     */
    public static function row_type(recording $rec, instance $instance, array $playback): string {
        global $CFG, $OUTPUT;
        if (!self::include_recording_data_row_type($rec, $instance, $playback)) {
            return '';
        }
        $text = self::type_text($playback['type']);
        $href = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_view.php?action=play&bn=' . $instance->get_instance_id() .
            '&rid=' . $rec->get('id') . '&rtype=' . $playback['type'];
        $linkattributes = [
            'id' => 'recording-play-' . $playback['type'] . '-' . $rec->get('id'),
            'class' => 'btn btn-sm btn-default',
            'onclick' => 'M.mod_bigbluebuttonbn.recordings.recordingPlay(this);',
            'data-action' => 'play',
            'data-target' => $playback['type'],
            'data-href' => $href,
        ];
        return $OUTPUT->action_link('#', $text, null, $linkattributes) . '&#32;';
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
    public static function include_recording_table_row(instance $instance, recording $rec): bool {
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

    /**
     * Helper function renders the link used for recording type in row for the data used by the recording table.
     *
     * @param recording $rec a recording row
     * @param instance $instance
     * @param array $playback
     * @return boolean
     */
    public static function include_recording_data_row_type(recording $rec, $instance, array $playback): bool {
        // All types that are not restricted are included.
        if (array_key_exists('restricted', $playback) && strtolower($playback['restricted']) == 'false') {
            return true;
        }
        // All types that are not statistics are included.
        if ($playback['type'] != 'statistics') {
            return true;
        }

        // Exclude imported recordings.
        if ($rec->get('imported')) {
            return false;
        }

        // Exclude non moderators.
        if (!$instance->is_admin() && !$instance->is_moderator()) {
            return false;
        }
        return true;

    }

    /**
     * Helper function render a button for the recording action bar
     *
     * @param recording $rec a bigbluebuttonbn_recordings row
     * @param array $data
     * @return string
     */
    protected static function actionbar_render_button(recording $rec, array $data): string {
        global $PAGE;
        if (empty($data)) {
            return '';
        }
        $target = $data['action'];
        if (isset($data['target'])) {
            $target .= '-' . $data['target'];
        }
        $id = 'recording-' . $target . '-' . $rec->get('recordingid');
        if ((boolean) config::get('recording_icons_enabled')) {
            // With icon for $manageaction.
            $iconattributes = [
                'id' => $id,
                'class' => 'iconsmall',
            ];
            $linkattributes = [
                'id' => $id,
                'data-action' => $data['action'],
                'data-require-confirmation' => !empty($data['requireconfirmation']),
            ];
            if ($rec->get('imported')) {
                $linkattributes['data-links'] = recording::count_records(
                    [
                        'recordingid' => $rec->get('recordingid'),
                        'imported' => true,
                    ]
                );
            }
            if (isset($data['disabled'])) {
                $iconattributes['class'] .= ' fa-' . $data['disabled'];
                $linkattributes['class'] = 'disabled';
            }
            $icon = new pix_icon(
                'i/' . $data['icon'],
                get_string('view_recording_list_actionbar_' . $data['action'], 'bigbluebuttonbn'),
                'moodle',
                $iconattributes
            );
            return $PAGE->get_renderer('core')->action_icon('#', $icon, null, $linkattributes, false);
        }
        // With text for $manageaction.
        $linkattributes = [
            'title' => get_string($data['tag']),
            'class' => 'btn btn-xs btn-danger',
        ];
        return $PAGE->get_renderer('core')->action_link('#', get_string($data['action']), null, $linkattributes);
    }
}
