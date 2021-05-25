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

namespace mod_bigbluebuttonbn\analytics;

/**
 * Analytics Table
 *
 * @package   bigbluebuttonbn
 * @author    Kevin Pham <kevinpham@catalyst-au.net>
 * @copyright 2021 onwards Catalyst IT Ltd
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class analytics_for_recordings_table extends \table_sql {

    private $datetimeformat;
    private $durationformat;

    public function __construct($uniqueid, \moodle_url $url, $perpage = 100) {
        parent::__construct($uniqueid);
        $this->datetimeformat = get_string('strftimedatetimetimezone', 'bigbluebuttonbn');
        $this->durationformat = get_string('strftimetime24seconds', 'bigbluebuttonbn');

        $this->set_attribute('class', 'generaltable generalbox');
        $cols = [
            'createtime',
            'starttime',
            'endtime',
            'playbackduration',
            'queuestarttime',
            'queueduration',
            'processingtime',
            'processingduration',
            'filesize', // Size in bytes of the recording.

            // TODO: Fancy number #1: Relative processing time speed compared with the median/average
            // TODO: Fancy number #2: Std deviation / where it sits in terms of other recordings for processing
            // TODO: Cross link to reccording?
            // TODO: Cross link to session analytics?
        ];

        $this->define_columns($cols);
        $this->define_headers([
            get_string('view_analytics_createtime', 'bigbluebuttonbn'),
            get_string('view_analytics_starttime', 'bigbluebuttonbn'),
            get_string('view_analytics_endtime', 'bigbluebuttonbn'),
            get_string('view_analytics_playbackduration', 'bigbluebuttonbn'),
            get_string('view_analytics_queuestarttime', 'bigbluebuttonbn'),
            get_string('view_analytics_queueduration', 'bigbluebuttonbn'),
            get_string('view_analytics_processingtime', 'bigbluebuttonbn'),
            get_string('view_analytics_processingduration', 'bigbluebuttonbn'),
            get_string('view_analytics_filesize', 'bigbluebuttonbn'),
        ]);
        $this->pagesize = $perpage;
        $systemcontext = \context_system::instance();
        $this->context = $systemcontext;
        $this->collapsible(false);
        $this->sortable(false, 'timecreated', SORT_DESC); // This is due to the data being stored in the meta field as json.
        $this->pageable(false);
        $this->is_downloadable(true);
        $this->define_baseurl($url);
    }


    public function col_createtime($row) {
        $data = json_decode($row->meta);
        return userdate($data->createtime / 1000, $this->datetimeformat);
    }

    public function col_starttime($row) {
        $data = json_decode($row->meta);
        return userdate($data->starttime / 1000, $this->datetimeformat);
    }

    public function col_endtime($row) {
        $data = json_decode($row->meta);
        if (isset($data->endtime)) {
            return userdate($data->endtime / 1000, $this->datetimeformat);
        }
        return '-';
    }

    public function col_playbackduration($row) {
        $data = json_decode($row->meta);
        if (!empty($data->playbackduration)) {
            return userdate($data->playbackduration / 1000, $this->durationformat, 'UTC');
        }
        return '-';
    }

    // Calculated by taking the time it finished processing - deduct the processing time, deduct the queue time.
    public function col_queuestarttime($row) {
        $data = json_decode($row->meta);
        if (empty($data->recordinglastmodified) || empty($data->processingduration) || empty($data->queuetime)) {
            return '-';
        }
        $queuestarttime = $data->recordinglastmodified - $data->processingduration - $data->queuetime;
        return userdate($queuestarttime / 1000, $this->datetimeformat);
    }

    public function col_queueduration($row) {
        $data = json_decode($row->meta);
        if (!empty($data->queueduration)) {
            return userdate($data->queueduration / 1000, $this->durationformat, 'UTC');
        }
        return '-';
    }

    public function col_processingtime($row) {
        $data = json_decode($row->meta);
        if (empty($data->recordinglastmodified) || empty($data->processingduration)) {
            return '-';
        }
        $processingtime = $data->recordinglastmodified - $data->processingduration;
        return userdate($processingtime / 1000, $this->datetimeformat);
    }

    public function col_processingduration($row) {
        $data = json_decode($row->meta);
        if (!empty($data->processingduration)) {
            return userdate($data->processingduration / 1000, $this->durationformat, 'UTC');
        }
        return '-';
    }

    public function col_filesize($row) {
        $data = json_decode($row->meta);
        if (isset($data->filesize)) {
            return display_size($data->filesize);
        }
        return '-';
    }

}
