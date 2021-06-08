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
 * Views analytics on the particular BBB session/meeting
 *
 * @package    mod_bigbluebuttonbn
 * @copyright  Kevin Pham <kevinpham@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_bigbluebuttonbn\plugin;

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/locallib.php');
require_once(__DIR__.'/viewlib.php');

$id = required_param('id', PARAM_RAW);
$bn = required_param('bn', PARAM_INT);
$group = optional_param('group', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

$viewinstance = bigbluebuttonbn_view_validator(null, $bn); // In locallib.
if (!$viewinstance) {
    throw new invalid_parameter_exception(get_string('view_error_url_missing_parameters', 'bigbluebuttonbn'));
}

$meetinginfo = bigbluebuttonbn_get_meeting_info_array($id);

$cm = $viewinstance['cm'];
$course = $viewinstance['course'];
$bigbluebuttonbn = $viewinstance['bigbluebuttonbn'];

require_login($course, true, $cm);

require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->dirroot.'/mod/bigbluebuttonbn/lib.php');

require_capability('mod/bigbluebuttonbn:meetinganalytics', context_module::instance($cm->id));

// Print the page header.
$pageurl = new moodle_url('/mod/bigbluebuttonbn/analytics.php', ['id' => $id, 'bn' => $bn]);
$PAGE->set_url($pageurl);
$PAGE->set_title($bigbluebuttonbn->name);
$PAGE->set_cacheable(false);
$PAGE->set_heading($course->fullname);

$table = new \mod_bigbluebuttonbn\analytics\analytics_per_session_table('uniqueid', $PAGE->url);
$table->is_downloading($download);

if (!$download) {
    // Only print headers if not asked to download data.
    $PAGE->set_title($bigbluebuttonbn->name);
    $PAGE->set_cacheable(false);
    $PAGE->set_heading($course->fullname);
    $PAGE->navbar->add(get_string('view_section_title_analytics', 'bigbluebuttonbn'), $pageurl);
    echo $OUTPUT->header();
}
$wheresql = "meta IS NOT NULL
             AND bigbluebuttonbnid = ?
             AND log = ?
             AND recordid = ?
             AND userid IS NOT NULL";
$table->set_sql('id, meta, userid', "{bigbluebuttonbn_logs}", $wheresql, [
    $bigbluebuttonbn->id,
    BIGBLUEBUTTON_LOG_EVENT_SUMMARY,
    $id
]);
$table->define_baseurl($pageurl);
$meetingsummary = $DB->get_record('bigbluebuttonbn_logs', [
    'userid' => null,
    'recordid' => $id,
    'log' => BIGBLUEBUTTON_LOG_EVENT_SUMMARY
]);
$meetingcreationinfo = $DB->get_record('bigbluebuttonbn_logs', [
    'recordid' => $id,
    'log' => BIGBLUEBUTTONBN_LOG_EVENT_CREATE
]);
$output = "";

// Prepare attendee data for Attention Box.
$table->setup();
$table->query_db(0); // No limit, fetch all rows.
$table->pageable(false);
$table->close_recordset();
$attendees = $table->rawdata;

if (isset($meetingsummary->meta)) {
    $meetingsummary->meta = json_decode($meetingsummary->meta);
}

if (!$download && !empty($attendees)) {
    $tables = "";
    if (isset($meetingsummary->meta)) {
        $output .= $OUTPUT->heading($meetingsummary->meta->meetingname, 3);
        $output .= $OUTPUT->heading(
            userdate(
                bigbluebuttonbn_datetime_to_timestamp($meetingsummary->meta->start),
                get_string('strftimedatetimetimezone', 'bigbluebuttonbn')
            ),
            6
        );

        // Overview Box.
        $overviewtable = new html_table();
        $overviewtable->caption = get_string('view_analytics_heading_overview', 'bigbluebuttonbn');
        $overviewtable->attributes['class'] = 'w-auto admintable generaltable mr-4 table-bordered';
        $durationhms = userdate($meetingsummary->meta->duration, get_string('strftimetime24seconds', 'bigbluebuttonbn'), 'UTC');
        $htmlparams = ['class' => 'text-center'];
        $data = [
            [
                get_string('view_analytics_duration', 'bigbluebuttonbn'),
                \html_writer::tag('div', $durationhms, ['class' => 'text-right'])
            ],
            [
                get_string('view_analytics_moderators', 'bigbluebuttonbn'),
                \html_writer::tag('div', $meetingsummary->meta->moderators, $htmlparams),
            ],
            [
                get_string('view_analytics_viewers', 'bigbluebuttonbn'),
                \html_writer::tag('div', $meetingsummary->meta->viewers, $htmlparams),
            ]
        ];

        // Recording info.
        if (!empty($meetingcreationinfo)) {
            $meta = json_decode($meetingcreationinfo->meta);

            // Recorded: Is this session recorded?
            if ($meta->record === 'false' || (isset($meta->recorded) && $meta->recorded === false)) {
                $recordedresult = get_string('no');
            } else if (!empty($meta->recordinglastmodified)) {
                $recordedresult = get_string('yes');
                $recorded = true;
            } else {
                $recordedresult = get_string('view_analytics_recorded_status_unknown', 'bigbluebuttonbn');
            }
            $data[] = [
                get_string('view_analytics_recorded', 'bigbluebuttonbn'),
                \html_writer::tag('div', $recordedresult, ['class' => 'text-center'])
            ];

            // Processing Time - If recorded (confirmed), how long did it take?
            if (!empty($recorded)) {
                if (isset($meta->recordingprocessingtime)) {
                    $recordingprocessingtimeinseconds = $meta->recordingprocessingtime / 1000;
                    $recordingprocessingtime = userdate(
                        $recordingprocessingtimeinseconds,
                        get_string('strftimetime24seconds', 'bigbluebuttonbn'),
                        'UTC'
                    );
                    $recordingprocessingtimetitle = format_time($recordingprocessingtimeinseconds);
                } else {
                    $recordingprocessingtime = get_string('view_analytics_recorded_status_unknown', 'bigbluebuttonbn');
                }
                $data[] = [
                    get_string('view_analytics_recordingprocessingtime', 'bigbluebuttonbn'),
                    \html_writer::tag('div', $recordingprocessingtime, [
                        'class' => 'text-right',
                        'title' => $recordingprocessingtimetitle ?? ''
                    ])
                ];
            }

        }

        $overviewtable->data = $data;
        $overviewtablehtml = html_writer::table($overviewtable);
        $tables .= $overviewtablehtml;
    }

    // Init data for Attention box.
    $attentiontotals = [];

    // Loop through and add +1 for every student who engages in the session, against the type of engagement (regardless of amount).
    $attentiontotals = array_reduce($attendees, function($acc, $attendee){
        $meta = json_decode($attendee->meta);
        $engagement = $meta->data->engagement;
        foreach ($engagement as $engagementtype => $engagementnumber) {
            $acc[$engagementtype] = ($acc[$engagementtype] ?? 0) + ($engagementnumber ? 1 : 0);
        }
        return $acc;
    }, $attentiontotals);

    foreach ($attendees as &$attendee) {
        $meta = json_decode($attendee->meta);
        $engagement = (array)$meta->data->engagement;
        $attentiontotals = array_reduce(array_keys($engagement), function($acc, $key) use ($engagement) {
            $acc[$key] = ($acc[$key] ?? 0) + ($engagement[$key] ? 1 : 0);
            return $acc;
        }, $attentiontotals);
    }

    // Attention Box.
    $attentiontable = new html_table();
    $attentiontable->caption = get_string('view_analytics_heading_attention', 'bigbluebuttonbn');
    $attentiontable->attributes['class'] = 'w-auto admintable generaltable mr-4 table-bordered';
    $htmlparams = ['class' => 'text-center'];
    $totalattendees = count($attendees);
    $data = [
        [
            get_string('view_analytics_number_of_students_speaking', 'bigbluebuttonbn'),
            $attentiontotals['talks'].' / '.$totalattendees
        ],
        [
            get_string('view_analytics_number_of_students_messaging', 'bigbluebuttonbn'),
            $attentiontotals['chats'].' / '.$totalattendees
        ],
        [
            get_string('view_analytics_number_of_students_using_emojis', 'bigbluebuttonbn'),
            $attentiontotals['emojis'].' / '.$totalattendees
        ],
        [
            get_string('view_analytics_number_of_students_raising_hands', 'bigbluebuttonbn'),
            $attentiontotals['raisehand'].' / '.$totalattendees
        ],
    ];
    $attentiontable->data = $data;
    $attentiontablehtml = html_writer::table($attentiontable);
    $tables .= $attentiontablehtml;

    // Files box.
    $filestable = new html_table();
    $filestable->caption = get_string('view_analytics_heading_files', 'bigbluebuttonbn');
    $filestable->attributes['class'] = 'w-25 admintable generaltable table-bordered';
    $htmlparams = ['class' => 'text-center'];
    $data = [];
    foreach ($meetingsummary->meta->files as $file) {
        $data[] = [$file];
    }
    $filestable->data = $data;
    $filestablehtml = html_writer::table($filestable);
    $tables .= $filestablehtml;

    $output .= '<div class="d-flex align-items-start">'.$tables.'</div>';
}

if (!$download) {
    echo $output;
}

// Modify data - add in any poll data.
if (!empty($meetingsummary->meta->polls)) {
    $totalattendees = count($table->rawdata);
    foreach ($meetingsummary->meta->polls as $index => $poll) {
        $totalpollvotes = count((array)$poll->votes);
        $pollid = 'poll'.$poll->id;
        $table->columns[$pollid] = count($table->columns);
        $table->column_class[$pollid] = 'text-center';
        $table->column_style[$pollid] = '';
        $table->column_suppress[$pollid] = false;

        $newheader = get_string('view_analytics_poll', 'bigbluebuttonbn')." ".($index + 1);
        if (!$download) {
            $newheader .= \html_writer::tag('div', (
                    $totalpollvotes." / ".$totalattendees." ".get_string('view_analytics_poll_responses', 'bigbluebuttonbn')
                ), ['class' => 'small']);
        }
        $table->headers[] = $newheader;

        foreach ($table->rawdata as $rawattendeedata) {
            // Note: This may to be rechecked for guests who participate, as their ids might be empty.
            $rawattendeedata->{$pollid} = $poll->votes->{$rawattendeedata->userid} ?? '-';
        }
    }
}

$table->build_table();
$table->finish_output();

if (!$download) {
    echo $OUTPUT->footer();
}
