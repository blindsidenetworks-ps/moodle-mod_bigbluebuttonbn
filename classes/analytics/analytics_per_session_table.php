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
class analytics_per_session_table extends \table_sql {

    public function __construct($uniqueid, \moodle_url $url, $perpage = 100) {
        parent::__construct($uniqueid);

        $this->set_attribute('class', 'generaltable generalbox');
        $cols = [
            'participant',
            'duration',
            'timejoined',
            'timeleft',
            'talktime',
            'messages',
            'emojis',
            'handraises',
        ];

        $this->define_columns($cols);
        $this->define_headers([
            get_string('view_analytics_participant', 'bigbluebuttonbn'),
            get_string('view_analytics_duration', 'bigbluebuttonbn'),
            get_string('view_analytics_timejoined', 'bigbluebuttonbn'),
            get_string('view_analytics_timeleft', 'bigbluebuttonbn'),
            get_string('view_analytics_talktime', 'bigbluebuttonbn'),
            get_string('view_analytics_messages', 'bigbluebuttonbn'),
            get_string('view_analytics_emojis', 'bigbluebuttonbn'),
            get_string('view_analytics_handraises', 'bigbluebuttonbn'),
        ]);
        $this->pagesize = $perpage;
        $systemcontext = \context_system::instance();
        $this->context = $systemcontext;
        $this->collapsible(false);
        $this->sortable(false, 'id', SORT_DESC); // This is due to the data being stored in the meta field as json.
        $this->pageable(false);
        $this->is_downloadable(true);
        $this->define_baseurl($url);

        // Column styling by class
        // Engagement values should be centered.
        $this->column_class('messages', 'text-center');
        $this->column_class('emojis', 'text-center');
        $this->column_class('handraises', 'text-center');
    }

    public function col_participant($row) {
        $data = json_decode($row->meta)->data;

        if ($this->is_downloading()) {
            return $data->name;
        }

        $profilelink = new \moodle_url('/user/view.php', ['id' => $row->userid, 'course' => 2]);
        $htmlparams = ['class' => 'badge badge-primary ml-2'];
        $moderatorbadge = \html_writer::tag('span', get_string('view_analytics_moderators', 'bigbluebuttonbn'), $htmlparams);
        $userlinkelement = \html_writer::tag('a', $data->name, ['href' => $profilelink]);
        return $userlinkelement.($data->moderator ? $moderatorbadge : '');
    }

    public function col_duration($row) {
        // Duration is stored in seconds, so convert to 00:00:00 time.
        $data = json_decode($row->meta)->data;
        $durationhms = userdate($data->duration,
            get_string('strftimetime24seconds', 'bigbluebuttonbn'), 'UTC');
        return $durationhms;
    }

    public function col_timejoined($row) {
        // Duration is stored in seconds, so convert to 00:00:00 time.
        $data = json_decode($row->meta)->data;
        return userdate(bigbluebuttonbn_datetime_to_timestamp($data->joins[0]),
            get_string('strftimetime12seconds', 'bigbluebuttonbn'));
    }

    public function col_timeleft($row) {
        // Time left is stored as a datetime, so show the hh:mm only.
        $data = json_decode($row->meta)->data;
        return userdate(bigbluebuttonbn_datetime_to_timestamp($data->leaves[0]),
            get_string('strftimetime12seconds', 'bigbluebuttonbn'));
    }

    public function col_talktime($row) {
        // Duration is stored in seconds, so convert to 00:00:00 time.
        $data = json_decode($row->meta)->data;
        $durationhms = userdate($data->engagement->talk_time, get_string('strftimetime24seconds', 'bigbluebuttonbn'), 'UTC');
        return $durationhms;
    }

    public function col_messages($row) {
        $data = json_decode($row->meta)->data;
        return $data->engagement->chats;
    }

    public function col_emojis($row) {
        $data = json_decode($row->meta)->data;
        return $data->engagement->emojis;
    }

    public function col_handraises($row) {
        $data = json_decode($row->meta)->data;
        return $data->engagement->raisehand;
    }

    /**
     * Returns the default sort columns defined, without any filtering done by tablelib internally.
     *
     * @return array column name => SORT_... constant.
     */
    public function get_sort_columns() {
        return [$this->sort_default_column => $this->sort_default_order];
    }

}
