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
 * The mod_bigbluebuttonbn recordings instance helper
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David  (laurent [at] call-learning [dt] fr)
 */

namespace mod_bigbluebuttonbn\local\helpers;

use html_table;
use html_table_row;
use html_writer;
use mod_bigbluebuttonbn\event\events;
use mod_bigbluebuttonbn\local\bbb_constants;
use mod_bigbluebuttonbn\local\bigbluebutton;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\local\view;
use mod_bigbluebuttonbn\output\recording_description_editable;
use mod_bigbluebuttonbn\output\recording_name_editable;
use mod_bigbluebuttonbn\plugin;
use mod_bigbluebuttonbn_generator;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Utility class for recordings instance helper
 *
 * @package mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recording {

    /** @var int INCLUDE_IMPORTED_RECORDINGS boolean set to true defines that the list should include imported recordings */
    public const INCLUDE_IMPORTED_RECORDINGS = true;

    /**
     * Helper function to retrieve recordings from a BigBlueButton server.
     *
     * @param string|array $recordingids list of $recordingids "rid1,rid2,rid3" or array("rid1","rid2","rid3")
     *
     * @return array array with recordings indexed by recordID, each recording is a non sequential array
     */
    public static function bigbluebuttonbn_get_recordings_array($recordingids = []) {
        $recordingidsarray = $recordingids;
        if (!is_array($recordingids)) {
            $recordingidsarray = explode(',', $recordingids);
        }
        // If $recordingidsarray is empty there is no need to go further.
        if (empty($recordingidsarray)) {
            return array();
        }
        $recordings = self::bigbluebuttonbn_get_recordings_array_fetch($recordingidsarray);
        // Sort recordings.
        uasort($recordings, "\\mod_bigbluebuttonbn\\local\\helpers\\recording::bigbluebuttonbn_recording_build_sorter");
        return $recordings;
    }

    /**
     * Helper function to fetch recordings from a BigBlueButton server.
     *
     * @param array $meetingidsarray array with meeting ids in the form array("mid1","mid2","mid3")
     *
     * @return array (associative) with recordings indexed by recordID, each recording is a non sequential array
     */
    public static function bigbluebuttonbn_get_recordings_array_fetch($meetingidsarray) {
        // TODO: We will need to completely rewrite this by
        // Having a recording singleton which instantiation would depend on the condition here or
        // overriding the higher level function (bigbluebuttonbn_get_recordings_array).

        if ((defined('PHPUNIT_TEST') && PHPUNIT_TEST)
            || defined('BEHAT_SITE_RUNNING')
            || defined('BEHAT_TEST')
            || defined('BEHAT_UTIL')) {
            // Just return the fake recording.
            global $CFG;
            require_once($CFG->libdir . '/testing/generator/lib.php');
            require_once($CFG->dirroot . '/mod/bigbluebuttonbn/tests/generator/lib.php');
            return mod_bigbluebuttonbn_generator::bigbluebuttonbn_get_recordings_array_fetch($meetingidsarray);
        }
        $recordings = array();
        // Execute a paginated getRecordings request.
        $pagecount = 25;
        $pages = floor(count($meetingidsarray) / $pagecount) + 1;
        if (count($meetingidsarray) > 0 && count($meetingidsarray) % $pagecount == 0) {
            $pages--;
        }
        for ($page = 1; $page <= $pages; ++$page) {
            $mids = array_slice($meetingidsarray, ($page - 1) * $pagecount, $pagecount);
            $recordings += self::bigbluebuttonbn_get_recordings_array_fetch_page($mids);
        }
        return $recordings;
    }

    /**
     * Helper function to fetch one page of upto 25 recordings from a BigBlueButton server.
     *
     * @param array $rids
     *
     * @return array
     */
    public static function bigbluebuttonbn_get_recordings_array_fetch_page($rids) {
        $recordings = array();
        // Do getRecordings is executed using a method GET (supported by all versions of BBB).
        $url = bigbluebutton::action_url('getRecordings', ['meetingID' => '', 'recordID' => implode(',', $rids)]);
        $xml = bigbluebutton::bigbluebuttonbn_wrap_xml_load_file($url);
        debugging('getRecordingsURL: ' . $url);
        debugging('recordIDs: ' . json_encode($rids));
        if ($xml && $xml->returncode == 'SUCCESS' && isset($xml->recordings)) {
            // If there were meetings already created.
            foreach ($xml->recordings->recording as $recordingxml) {
                $recording = self::bigbluebuttonbn_get_recording_array_value($recordingxml);
                $recordings[$recording['recordID']] = $recording;

                // Check if there is childs.
                if (isset($recordingxml->breakoutRooms->breakoutRoom)) {
                    foreach ($recordingxml->breakoutRooms->breakoutRoom as $breakoutroom) {
                        $url = bigbluebutton::action_url(
                            'getRecordings',
                            ['recordID' => implode(',', (array) $breakoutroom)]
                        );
                        $xml = bigbluebutton::bigbluebuttonbn_wrap_xml_load_file($url);
                        if ($xml && $xml->returncode == 'SUCCESS' && isset($xml->recordings)) {
                            // If there were meetings already created.
                            foreach ($xml->recordings->recording as $recordingxml) {
                                $recording =
                                    self::bigbluebuttonbn_get_recording_array_value($recordingxml);
                                $recordings[$recording['recordID']] = $recording;
                            }
                        }
                    }
                }
            }
        }
        return $recordings;
    }

    /**
     * Helper function to remove a set of recordings from an array.
     *
     * @param array $rids
     * @param array $recordings
     *
     * @return array
     */
    public static function bigbluebuttonbn_get_recordings_array_filter($rids, &$recordings) {
        foreach ($recordings as $key => $recording) {
            if (!in_array($recording['recordID'], $rids)) {
                unset($recordings[$key]);
            }
        }
        return $recordings;
    }

    /**
     * Helper function to retrieve imported recordings from the Moodle database.
     * The references are stored as events in bigbluebuttonbn_logs.
     *
     * @param string $courseid
     * @param string $bigbluebuttonbnid
     * @param bool $subset
     *
     * @return array with imported recordings indexed by recordID, each recording
     * is a non sequential array that corresponds to the actual recording in BBB
     */
    public static function bigbluebuttonbn_get_recordings_imported_array($courseid = 0, $bigbluebuttonbnid = null, $subset = true) {
        global $DB;
        $select =
            self::bigbluebuttonbn_get_recordings_imported_sql_select($courseid, $bigbluebuttonbnid,
                $subset);
        $recordsimported = $DB->get_records_select('bigbluebuttonbn_logs', $select);
        $recordsimportedarray = array();
        foreach ($recordsimported as $recordimported) {
            $meta = json_decode($recordimported->meta, true);
            $recording = $meta['recording'];
            // Override imported flag with actual ID.
            $recording['imported'] = $recordimported->id;
            if (isset($recordimported->protected)) {
                $recording['protected'] = (string) $recordimported->protected;
            }
            $recordsimportedarray[$recording['recordID']] = $recording;
        }
        return $recordsimportedarray;
    }

    /**
     * Helper function to convert an xml recording object to an array in the format used by the plugin.
     *
     * @param object $recording
     *
     * @return array
     */
    public static function bigbluebuttonbn_get_recording_array_value($recording) {
        // Add formats.
        $playbackarray = array();
        foreach ($recording->playback->format as $format) {
            $playbackarray[(string) $format->type] = array('type' => (string) $format->type,
                'url' => trim((string) $format->url), 'length' => (string) $format->length);
            // Add preview per format when existing.
            if ($format->preview) {
                $playbackarray[(string) $format->type]['preview'] =
                    self::bigbluebuttonbn_get_recording_preview_images($format->preview);
            }
        }
        // Add the metadata to the recordings array.
        $metadataarray =
            self::bigbluebuttonbn_get_recording_array_meta(get_object_vars($recording->metadata));
        $recordingarray = array('recordID' => (string) $recording->recordID,
            'meetingID' => (string) $recording->meetingID, 'meetingName' => (string) $recording->name,
            'published' => (string) $recording->published, 'startTime' => (string) $recording->startTime,
            'endTime' => (string) $recording->endTime, 'playbacks' => $playbackarray);
        if (isset($recording->protected)) {
            $recordingarray['protected'] = (string) $recording->protected;
        }
        return $recordingarray + $metadataarray;
    }

    /**
     * Helper function to convert an xml recording preview images to an array in the format used by the plugin.
     *
     * @param object $preview
     *
     * @return array
     */
    public static function bigbluebuttonbn_get_recording_preview_images($preview) {
        $imagesarray = array();
        foreach ($preview->images->image as $image) {
            $imagearray = array('url' => trim((string) $image));
            foreach ($image->attributes() as $attkey => $attvalue) {
                $imagearray[$attkey] = (string) $attvalue;
            }
            array_push($imagesarray, $imagearray);
        }
        return $imagesarray;
    }

    /**
     * Helper function to convert an xml recording metadata object to an array in the format used by the plugin.
     *
     * @param array $metadata
     *
     * @return array
     */
    public static function bigbluebuttonbn_get_recording_array_meta($metadata) {
        $metadataarray = array();
        foreach ($metadata as $key => $value) {
            if (is_object($value)) {
                $value = '';
            }
            $metadataarray['meta_' . $key] = $value;
        }
        return $metadataarray;
    }

    /**
     * Helper function to sort an array of recordings. It compares the startTime in two recording objecs.
     *
     * @param object $a
     * @param object $b
     *
     * @return array
     */
    public static function bigbluebuttonbn_recording_build_sorter($a, $b) {
        global $CFG;
        $resultless = !empty($CFG->bigbluebuttonbn_recordings_sortorder) ? -1 : 1;
        $resultmore = !empty($CFG->bigbluebuttonbn_recordings_sortorder) ? 1 : -1;
        if ($a['startTime'] < $b['startTime']) {
            return $resultless;
        }
        if ($a['startTime'] == $b['startTime']) {
            return 0;
        }
        return $resultmore;
    }

    /**
     * Perform deleteRecordings on BBB.
     *
     * @param string $recordids
     *
     * @return boolean
     */
    public static function bigbluebuttonbn_delete_recordings($recordids) {
        $ids = explode(',', $recordids);
        foreach ($ids as $id) {
            $xml = bigbluebutton::bigbluebuttonbn_wrap_xml_load_file(
                bigbluebutton::action_url('deleteRecordings', ['recordID' => $id])
            );
            if ($xml && $xml->returncode != 'SUCCESS') {
                return false;
            }
        }
        return true;
    }

    /**
     * Perform publishRecordings on BBB.
     *
     * @param string $recordids
     * @param string $publish
     */
    public static function bigbluebuttonbn_publish_recordings($recordids, $publish = 'true') {
        $ids = explode(',', $recordids);
        foreach ($ids as $id) {
            $xml = bigbluebutton::bigbluebuttonbn_wrap_xml_load_file(
                bigbluebutton::action_url('publishRecordings',
                    ['recordID' => $id, 'publish' => $publish])
            );
            if ($xml && $xml->returncode != 'SUCCESS') {
                return false;
            }
        }
        return true;
    }

    /**
     * Perform updateRecordings on BBB.
     *
     * @param string $recordids
     * @param array $params ['key'=>param_key, 'value']
     */
    public static function bigbluebuttonbn_update_recordings($recordids, $params) {
        $ids = explode(',', $recordids);
        foreach ($ids as $id) {
            $xml = bigbluebutton::bigbluebuttonbn_wrap_xml_load_file(
                bigbluebutton::action_url('updateRecordings', ['recordID' => $id] + (array) $params)
            );
            if ($xml && $xml->returncode != 'SUCCESS') {
                return false;
            }
        }
        return true;
    }

    /**
     * Helper function converts recording date used in row for the data used by the recording table.
     *
     * @param array $recording
     *
     * @return integer
     */
    public static function bigbluebuttonbn_get_recording_data_row_date($recording) {
        if (!isset($recording['startTime'])) {
            return 0;
        }
        return floatval($recording['startTime']);
    }

    /**
     * Helper function evaluates if recording preview should be included.
     *
     * @param array $bbbsession
     *
     * @return boolean
     */
    public static function bigbluebuttonbn_get_recording_data_preview_enabled($bbbsession) {
        return ((double) $bbbsession['serverversion'] >= 1.0 && $bbbsession['bigbluebuttonbn']->recordings_preview == '1');
    }

    /**
     * Helper function converts recording duration used in row for the data used by the recording table.
     *
     * @param array $recording
     *
     * @return integer
     */
    public static function bigbluebuttonbn_get_recording_data_row_duration($recording) {
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
    public static function bigbluebuttonbn_get_recording_data_row_date_formatted($starttime) {
        global $USER;
        $starttime = $starttime - ($starttime % 1000);
        // Set formatted date.
        $dateformat = get_string('strftimerecentfull', 'langconfig') . ' %Z';
        return userdate($starttime / 1000, $dateformat, usertimezone($USER->timezone));
    }

    /**
     * Helper function builds recording actionbar used in row for the data used by the recording table.
     *
     * @param array $recording
     * @param array $tools
     *
     * @return string
     */
    public static function bigbluebuttonbn_get_recording_data_row_actionbar($recording, $tools) {
        $actionbar = '';
        foreach ($tools as $tool) {
            $buttonpayload =
                self::bigbluebuttonbn_get_recording_data_row_actionbar_payload($recording, $tool);
            if ($tool == 'protect') {
                if (isset($recording['imported'])) {
                    $buttonpayload['disabled'] = 'disabled';
                }
                if (!isset($recording['protected'])) {
                    $buttonpayload['disabled'] = 'invisible';
                }
            }
            if ($tool == 'delete') {
                $buttonpayload['requireconfirmation'] = true;
            }
            $actionbar .= view::bigbluebuttonbn_actionbar_render_button($recording, $buttonpayload);
        }
        $head = html_writer::start_tag('div', array(
            'id' => 'recording-actionbar-' . $recording['recordID'],
            'data-recordingid' => $recording['recordID'],
            'data-additionaloptions' => $recording['meetingID']));
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
    public static function bigbluebuttonbn_get_recording_data_row_actionbar_payload($recording, $tool) {
        if ($tool == 'protect') {
            $protected = 'false';
            if (isset($recording['protected'])) {
                $protected = $recording['protected'];
            }
            return self::bigbluebuttonbn_get_recording_data_row_action_protect($protected);
        }
        if ($tool == 'publish') {
            return self::bigbluebuttonbn_get_recording_data_row_action_publish($recording['published']);
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
    public static function bigbluebuttonbn_get_recording_data_row_action_protect($protected) {
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
    public static function bigbluebuttonbn_get_recording_data_row_action_publish($published) {
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
    public static function bigbluebuttonbn_get_recording_data_row_preview($recording) {
        $options = array('id' => 'preview-' . $recording['recordID']);
        if ($recording['published'] === 'false') {
            $options['hidden'] = 'hidden';
        }
        $recordingpreview = html_writer::start_tag('div', $options);
        foreach ($recording['playbacks'] as $playback) {
            if (isset($playback['preview'])) {
                $recordingpreview .= self::bigbluebuttonbn_get_recording_data_row_preview_images($playback);
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
    public static function bigbluebuttonbn_get_recording_data_row_preview_images($playback) {
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
     * @param array $recording
     * @param array $bbbsession
     *
     * @return string
     */
    public static function bigbluebuttonbn_get_recording_data_row_types($recording, $bbbsession) {
        $dataimported = 'false';
        $title = '';
        if (isset($recording['imported'])) {
            $dataimported = 'true';
            $title = get_string('view_recording_link_warning', 'bigbluebuttonbn');
        }
        $visibility = '';
        if ($recording['published'] === 'false') {
            $visibility = 'hidden ';
        }
        $id = 'playbacks-' . $recording['recordID'];
        $recordingtypes = html_writer::start_tag('div', array('id' => $id, 'data-imported' => $dataimported,
            'data-additionaloptions' => $recording['meetingID'], 'data-recordingid' => $recording['recordID'],
            'title' => $title, $visibility => $visibility));
        foreach ($recording['playbacks'] as $playback) {
            $recordingtypes .= self::bigbluebuttonbn_get_recording_data_row_type($recording,
                $bbbsession, $playback);
        }
        $recordingtypes .= html_writer::end_tag('div');
        return $recordingtypes;
    }

    /**
     * Helper function renders the link used for recording type in row for the data used by the recording table.
     *
     * @param array $recording
     * @param array $bbbsession
     * @param array $playback
     *
     * @return string
     */
    public static function bigbluebuttonbn_get_recording_data_row_type($recording, $bbbsession, $playback) {
        global $CFG, $OUTPUT;
        if (!view::bigbluebuttonbn_include_recording_data_row_type($recording, $bbbsession, $playback)) {
            return '';
        }
        $text = self::bigbluebuttonbn_get_recording_type_text($playback['type']);
        $href = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_view.php?action=play&bn=' . $bbbsession['bigbluebuttonbn']->id .
            '&mid=' . $recording['meetingID'] . '&rid=' . $recording['recordID'] . '&rtype=' . $playback['type'];
        if (!isset($recording['imported']) || !isset($recording['protected']) || $recording['protected'] === 'false') {
            $href .= '&href=' . urlencode(trim($playback['url']));
        }
        $linkattributes = array(
            'id' => 'recording-play-' . $playback['type'] . '-' . $recording['recordID'],
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
    public static function bigbluebuttonbn_get_recording_type_text($playbacktype) {
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
     * @param array $bbbsession
     *
     * @return string
     */
    public static function bigbluebuttonbn_get_recording_data_row_meta_activity($recording, $bbbsession) {
        $payload = array();
        if (self::bigbluebuttonbn_get_recording_data_row_editable($bbbsession)) {
            $payload = array('recordingid' => $recording['recordID'], 'meetingid' => $recording['meetingID'],
                'action' => 'edit', 'tag' => 'edit',
                'target' => 'name');
        }
        $oldsource = 'meta_contextactivity';
        if (isset($recording[$oldsource])) {
            $metaname = trim($recording[$oldsource]);
            return self::bigbluebuttonbn_get_recording_data_row_text($recording, $metaname, $oldsource,
                $payload);
        }
        $newsource = 'meta_bbb-recording-name';
        if (isset($recording[$newsource])) {
            $metaname = trim($recording[$newsource]);
            return self::bigbluebuttonbn_get_recording_data_row_text($recording, $metaname, $newsource,
                $payload);
        }
        $metaname = trim($recording['meetingName']);
        return self::bigbluebuttonbn_get_recording_data_row_text($recording, $metaname, $newsource,
            $payload);
    }

    /**
     * Helper function renders the description for recording used in row for the data used by the recording table.
     *
     * @param array $recording
     * @param array $bbbsession
     *
     * @return string
     */
    public static function bigbluebuttonbn_get_recording_data_row_meta_description($recording, $bbbsession) {
        $payload = array();
        if (self::bigbluebuttonbn_get_recording_data_row_editable($bbbsession)) {
            $payload = array('recordingid' => $recording['recordID'], 'meetingid' => $recording['meetingID'],
                'action' => 'edit', 'tag' => 'edit',
                'target' => 'description');
        }
        $oldsource = 'meta_contextactivitydescription';
        if (isset($recording[$oldsource])) {
            $metadescription = trim($recording[$oldsource]);
            return self::bigbluebuttonbn_get_recording_data_row_text($recording, $metadescription,
                $oldsource, $payload);
        }
        $newsource = 'meta_bbb-recording-description';
        if (isset($recording[$newsource])) {
            $metadescription = trim($recording[$newsource]);
            return self::bigbluebuttonbn_get_recording_data_row_text($recording, $metadescription,
                $newsource, $payload);
        }
        return self::bigbluebuttonbn_get_recording_data_row_text($recording, '', $newsource, $payload);
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
    public static function bigbluebuttonbn_get_recording_data_row_text($recording, $text, $source, $data) {
        $htmltext = '<span>' . htmlentities($text) . '</span>';
        if (empty($data)) {
            return $htmltext;
        }
        return $htmltext;
    }

    /**
     * Get the basic data to display in the table view
     *
     * @param array $bbbsession the current session
     * @param array $enabledfeatures feature enabled for this activity
     * @return array array containing the recordings indexed by recordID, each recording is also a
     * non sequential array itself that corresponds to the actual recording in BBB
     */
    public static function bigbluebutton_get_recordings_for_table_view($bbbsession, $enabledfeatures) {
        $bigbluebuttonbnid = null;
        if ($enabledfeatures['showroom']) {
            $bigbluebuttonbnid = $bbbsession['bigbluebuttonbn']->id;
        }
        return self::get_recordings(
            $bbbsession['course']->id,
            $bigbluebuttonbnid,
            $enabledfeatures['showroom'],
            $bbbsession['bigbluebuttonbn']->recordings_deleted,
            $enabledfeatures['importrecordings']
        );

    }

    /**
     * Helper function evaluates if recording row should be included in the table.
     *
     * @param array $bbbsession
     * @param array $recording
     *
     * @return boolean
     */
    public static function bigbluebuttonbn_include_recording_table_row($bbbsession, $recording) {
        // Exclude unpublished recordings, only if user has no rights to manage them.
        if ($recording['published'] != 'true' && !$bbbsession['managerecordings']) {
            return false;
        }
        // Imported recordings are always shown as long as they are published.
        if (isset($recording['imported'])) {
            return true;
        }
        // Administrators and moderators are always allowed.
        if ($bbbsession['administrator'] || $bbbsession['moderator']) {
            return true;
        }
        // When groups are enabled, exclude those to which the user doesn't have access to.
        if (isset($bbbsession['group']) && $recording['meetingID'] != $bbbsession['meetingid']) {
            return false;
        }
        return true;
    }

    /**
     * Helper function triggers a send notification when the recording is ready.
     *
     * @param object $bigbluebuttonbn
     *
     * @return void
     */
    public static function bigbluebuttonbn_send_notification_recording_ready($bigbluebuttonbn) {
        \mod_bigbluebuttonbn\local\notifier::notify_recording_ready($bigbluebuttonbn);
    }

    /**
     * Helper function returns an array with all the instances of imported recordings for a recordingid.
     *
     * @param string $recordid
     *
     * @return array
     */
    public static function bigbluebuttonbn_get_recording_imported_instances($recordid) {
        global $DB;
        $sql = 'SELECT * FROM {bigbluebuttonbn_logs} WHERE log = ? AND meta LIKE ? AND meta LIKE ?';
        $recordingsimported = $DB->get_records_sql($sql, array(bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_IMPORT, '%recordID%',
            "%{$recordid}%"));
        return $recordingsimported;
    }

    /**
     * Helper function to count the imported recordings for a recordingid.
     *
     * @param string $recordid
     *
     * @return integer
     */
    public static function bigbluebuttonbn_count_recording_imported_instances($recordid) {
        global $DB;
        $sql = 'SELECT COUNT(DISTINCT id) FROM {bigbluebuttonbn_logs} WHERE log = ? AND meta LIKE ? AND meta LIKE ?';
        return $DB->count_records_sql($sql, array(bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_IMPORT, '%recordID%', "%{$recordid}%"));
    }

    /**
     * Helper function iterates an array with recordings and unset those already imported.
     *
     * @param array $recordings
     * @param integer $courseid
     * @param integer $bigbluebuttonbnid
     *
     * @return array
     */
    public static function bigbluebuttonbn_unset_existent_recordings_already_imported($recordings, $courseid, $bigbluebuttonbnid) {
        $recordingsimported = self::bigbluebuttonbn_get_recordings_imported_array($courseid, $bigbluebuttonbnid, true);
        foreach ($recordings as $key => $recording) {
            if (isset($recordingsimported[$recording['recordID']])) {
                unset($recordings[$key]);
            }
        }
        return $recordings;
    }

    /**
     * Helper function to retrieve recordings from the BigBlueButton.
     *
     * @param string $courseid
     * @param string $bigbluebuttonbnid
     * @param bool $subset. If $subset=true the query is performed on one single bigbluebuttonbn instance.
     * @param bool $includedeleted. If $includedeleted=true the query is performed on one single bigbluebuttonbn instance.
     * @param bool $includeimported. If $includeimported=true the returned array also includes imported recordings.
     *
     * @return array array containing the recordings indexed by recordID, each recording is also a
     * non sequential array itself that corresponds to the actual recording in BBB
     */
    public static function get_recordings($courseid = 0, $bigbluebuttonbnid = null, $subset = true,
        $includedeleted = false, $includeimported = false) {
        global $DB;
        $select = self::bigbluebuttonbn_get_recordings_sql_select($courseid, $bigbluebuttonbnid, $subset, $includedeleted);
        $records = $DB->get_records_select_menu('bigbluebuttonbn_recordings', $select, null, 'id', 'id, recordingid');
        // Get actual recordings.
        $recordings = self::bigbluebuttonbn_get_recordings_array(array_values($records));
        if ($includeimported) {
            $recordings += self::bigbluebuttonbn_get_recordings_imported_array($courseid, $bigbluebuttonbnid, $subset);
        }
        return $recordings;
    }

    /**
     * Helper function to define the sql used for gattering the bigbluebuttonbnids whose meetingids should be included
     * in the getRecordings request considering only those that belong to imported recordings.
     *
     * @param string $courseid
     * @param string $bigbluebuttonbnid
     * @param bool $subset
     *
     * @return string containing the sql used for getting the target bigbluebuttonbn instances
     */
    public static function bigbluebuttonbn_get_recordings_imported_sql_select($courseid = 0, $bigbluebuttonbnid = null,
        $subset = true) {
        $sql = "log = '" . bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_IMPORT . "'";
        if (empty($courseid)) {
            $courseid = 0;
        }
        if (empty($bigbluebuttonbnid)) {
            return $sql . " AND courseid = '{$courseid}'";
        }
        if ($subset) {
            return $sql . " AND bigbluebuttonbnid = '{$bigbluebuttonbnid}'";
        }
        return $sql . " AND courseid = '{$courseid}' AND bigbluebuttonbnid <> '{$bigbluebuttonbnid}'";
    }

    /**
     * Helper function to define the sql used for gattering the bigbluebuttonbnids whose meetingids should be included
     * in the getRecordings request considering only those that belong to deleted activities.
     *
     * @param string $courseid
     * @param string $bigbluebuttonbnid
     * @param bool $subset
     *
     * @return string containing the sql used for getting the target bigbluebuttonbn instances
     */
    public static function bigbluebuttonbn_get_recordings_deleted_sql_select($courseid = 0, $bigbluebuttonbnid = null,
        $subset = true) {
        $sql = "log = '" . bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_DELETE .
            "' AND meta like '%has_recordings%' AND meta like '%true%'";
        if (empty($courseid)) {
            $courseid = 0;
        }
        if (empty($bigbluebuttonbnid)) {
            return $sql . " AND courseid = {$courseid}";
        }
        if ($subset) {
            return $sql . " AND bigbluebuttonbnid = '{$bigbluebuttonbnid}'";
        }
        return $sql . " AND courseid = {$courseid} AND bigbluebuttonbnid <> '{$bigbluebuttonbnid}'";
    }

    /**
     * Helper function to define the sql used for gattering the bigbluebuttonbnids whose meetingids should be included
     * in the getRecordings request
     *
     * @param string $courseid
     * @param string $bigbluebuttonbnid
     * @param bool $subset
     * @param bool $includedeleted.
     *
     * @return string containing the sql used for getting the target bigbluebuttonbn instances
     */
    public static function bigbluebuttonbn_get_recordings_sql_select($courseid, $bigbluebuttonbnid = null, $subset = true,
        $includedeleted = false) {
        if (empty($courseid)) {
            $courseid = 0;
        }
        $select = "";
        if (!$includedeleted) {
            // Exclude headless recordings from getRecordings requests unless includedeleted.
            $select = "headless = false AND ";
        }
        if (empty($bigbluebuttonbnid)) {
            // Fetch all recordings in given course if bigbluebuttonbnid filter is not included.
            return $select . "courseid = '{$courseid}'";
        }
        if ($subset) {
            // Fetch only one bigbluebutton instance if subset filter is included.
            return $select . "bigbluebuttonbnid = '{$bigbluebuttonbnid}'";
        }
        // Fetch only from one course and instance is used for imported recordings.
        return $select . "bigbluebuttonbnid <> '{$bigbluebuttonbnid}' AND course = '{$courseid}'";
    }

    /**
     * Helper function evaluates if a row for the data used by the recording table is editable.
     *
     * @param array $bbbsession
     *
     * @return boolean
     */
    public static function bigbluebuttonbn_get_recording_data_row_editable($bbbsession) {
        return ($bbbsession['managerecordings'] && ((double) $bbbsession['serverversion'] >= 1.0 || $bbbsession['bnserver']));
    }

    /**
     * Helper function builds a row for the data used by the recording table.
     *
     * @param array $bbbsession
     * @param array $recording
     * @param array $tools
     *
     * @return array
     */
    public static function bigbluebuttonbn_get_recording_data_row($bbbsession, $recording,
        $tools = ['protect', 'publish', 'delete']) {
        global $OUTPUT, $PAGE;
        if (!self::bigbluebuttonbn_include_recording_table_row($bbbsession, $recording)) {
            return;
        }
        $rowdata = new stdClass();
        // Set recording_types.
        $rowdata->playback = self::bigbluebuttonbn_get_recording_data_row_types($recording, $bbbsession);
        // Set activity name.
        $recordingname = new recording_name_editable($recording, $bbbsession);
        $rowdata->recording = $PAGE->get_renderer('core')
            ->render_from_template('core/inplace_editable', $recordingname->export_for_template($OUTPUT));
        // Set activity description.
        $recordingdescription = new recording_description_editable($recording, $bbbsession);
        $rowdata->description = $PAGE->get_renderer('core')
            ->render_from_template('core/inplace_editable', $recordingdescription->export_for_template($OUTPUT));

        if (self::bigbluebuttonbn_get_recording_data_preview_enabled($bbbsession)) {
            // Set recording_preview.
            $rowdata->preview = self::bigbluebuttonbn_get_recording_data_row_preview($recording);
        }
        // Set date.
        $rowdata->date = self::bigbluebuttonbn_get_recording_data_row_date($recording);
        // Set formatted date.
        $rowdata->date_formatted = self::bigbluebuttonbn_get_recording_data_row_date_formatted($rowdata->date);
        // Set formatted duration.
        $rowdata->duration_formatted = $rowdata->duration = self::bigbluebuttonbn_get_recording_data_row_duration($recording);
        // Set actionbar, if user is allowed to manage recordings.
        if ($bbbsession['managerecordings']) {
            $rowdata->actionbar = self::bigbluebuttonbn_get_recording_data_row_actionbar($recording, $tools);
        }
        return $rowdata;
    }

    /**
     * Protect/Unprotect an imported recording.
     *
     * @param string $id
     * @param boolean $protect
     *
     * @return boolean
     */
    public static function bigbluebuttonbn_protect_recording_imported($id, $protect = true) {
        global $DB;
        // Locate the record to be updated.
        $record = $DB->get_record('bigbluebuttonbn_logs', array('id' => $id));
        $meta = json_decode($record->meta, true);
        // Prepare data for the update.
        $meta['recording']['protected'] = ($protect) ? 'true' : 'false';
        $record->meta = json_encode($meta);
        // Proceed with the update.
        $DB->update_record('bigbluebuttonbn_logs', $record);
        return true;
    }

    /**
     * Update an imported recording.
     *
     * @param string $id
     * @param array $params ['key'=>param_key, 'value']
     *
     * @return boolean
     */
    public static function bigbluebuttonbn_update_recording_imported($id, $params) {
        global $DB;
        // Locate the record to be updated.
        // TODO: rework this routine completely (use object/array instead of json data).
        $record = $DB->get_record('bigbluebuttonbn_logs', array('id' => $id));
        $meta = json_decode($record->meta, true);
        // Prepare data for the update.
        $meta['recording'] = $params + $meta['recording'];
        $record->meta = json_encode($meta);
        // Proceed with the update.
        if (!$DB->update_record('bigbluebuttonbn_logs', $record)) {
            return false;
        }
        return true;
    }

    /**
     * Delete an imported recording.
     *
     * @param string $id
     *
     * @return boolean
     */
    public static function bigbluebuttonbn_delete_recording_imported($id) {
        global $DB;
        // Execute delete.
        $DB->delete_records('bigbluebuttonbn_logs', array('id' => $id));
        return true;
    }

    /**
     * Publish an imported recording.
     *
     * @param string $id
     * @param boolean $publish
     *
     * @return boolean
     */
    public static function bigbluebuttonbn_publish_recording_imported($id, $publish = true) {
        global $DB;
        // Locate the record to be updated.
        $record = $DB->get_record('bigbluebuttonbn_logs', array('id' => $id));
        $meta = json_decode($record->meta, true);
        // Prepare data for the update.
        $meta['recording']['published'] = ($publish) ? 'true' : 'false';
        $record->meta = json_encode($meta);
        // Proceed with the update.
        $DB->update_record('bigbluebuttonbn_logs', $record);
        return true;
    }

    /**
     * Helper for performing import on recordings.
     *
     * @param array $bbbsession
     * @param string $recordingid
     * @param string $importmeetingid
     *
     * @return string
     */
    public static function recording_import($bbbsession, $recordingid, $importmeetingid) {
        $recordings = self::bigbluebuttonbn_get_recordings_array([$recordingid]);
        $overrides = array('meetingid' => $importmeetingid);
        $meta = json_encode((object) [
            'recording' => $recordings[$recordingid]
        ]);
        logs::bigbluebuttonbn_log($bbbsession['bigbluebuttonbn'],
            bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_IMPORT,
            $overrides,
            $meta);
        // Moodle event logger: Create an event for recording imported.
        if (isset($bbbsession['bigbluebutton']) && isset($bbbsession['cm'])) {
            \mod_bigbluebuttonbn\local\helpers\logs::bigbluebuttonbn_event_log(
                events::$events['recording_import'],
                $bbbsession['bigbluebuttonbn'],
                ['other' => $bbbsession['bigbluebuttonbn']->id]
            );
        }
    }
}
