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
 * Views analytics/metrics on all the recordings logged from BBB for a specific
 * BBB instance, or for all BBB instances ever created
 *
 * @package    mod_bigbluebuttonbn
 * @copyright  Kevin Pham <kevinpham@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_bigbluebuttonbn\plugin;

require(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once(__DIR__.'/locallib.php');
require_once(__DIR__.'/viewlib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->dirroot.'/mod/bigbluebuttonbn/lib.php');

$group = optional_param('group', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$bn = optional_param('bn', 0, PARAM_INT);

// Check and scope the report (all site vs single BBB instance).
if (!empty($bn)) {
    // Scoping checks for the BBB instance.
    $viewinstance = bigbluebuttonbn_view_validator(null, $bn); // In locallib.
    if (!$viewinstance) {
        throw new invalid_parameter_exception(get_string('view_error_url_missing_parameters', 'bigbluebuttonbn'));
    }
    $cm = $viewinstance['cm'];
    $course = $viewinstance['course'];
    $bigbluebuttonbn = $viewinstance['bigbluebuttonbn'];
    require_login($course, true, $cm);
    $title = $bigbluebuttonbn->name;
    $heading = $course->fullname;

} else {
    // Checks for the site admin report.
    require_login();
    $title = get_string('view_analytics_heading_recordings', 'bigbluebuttonbn');
    $heading = get_string('bigbluebuttonbn', 'bigbluebuttonbn');
    admin_externalpage_setup('bigbluebuttonbnrecordingsanalyticsreport', '', null, '', array('pagelayout' => 'report'));
}
require_capability('mod/bigbluebuttonbn:recordinganalytics', context_system::instance());

// Print the page header.
$pageurl = new moodle_url('/mod/bigbluebuttonbn/analytics-recordings.php', ['bn' => $bn]);
$PAGE->set_url($pageurl);
$PAGE->set_title($title);
$PAGE->set_cacheable(false);
$PAGE->set_heading($heading);

$table = new \mod_bigbluebuttonbn\analytics\analytics_for_recordings_table('bigbluebuttonbn_recordings_metrics_table', $PAGE->url);
$table->is_downloading($download, 'bigbluebuttonbn-recordinganalytics-' . time());

if (!$download) {
    // Only print headers if not asked to download data.
    $PAGE->set_title($title);
    $PAGE->set_cacheable(false);
    $PAGE->set_heading($heading);
    $PAGE->navbar->add(get_string('view_section_title_analytics', 'bigbluebuttonbn'), $pageurl);
    echo $OUTPUT->header();
}

// Analytics Report Query.
$wheresql = "meta IS NOT NULL
             AND log = :log
             AND userid IS NOT NULL";
$params = ['log' => BIGBLUEBUTTONBN_LOG_EVENT_CREATE];
if (!empty($bn) && !empty($bigbluebuttonbn->id)) {
    $wheresql .= " AND bigbluebuttonbnid = :bigbluebuttonbnid";
    $params['bigbluebuttonbnid'] = $bigbluebuttonbn->id;
}
$table->set_sql('id, meta, userid, timecreated', "{bigbluebuttonbn_logs}", $wheresql, $params);
$table->define_baseurl($pageurl);

// Add cloned table modifying the query for Past Month instead.
$pastmonthtable = clone $table;
$wheresqlwithmonthconstraint = $wheresql;
$wheresql .= " AND timecreated > :timecreated";
$params['timecreated'] = time() - (30 * DAYSECS); // Currently set to 1 calendar month.
$pastmonthtable->set_sql('id, meta, userid, timecreated', "{bigbluebuttonbn_logs}", $wheresql, $params);
$pastmonthtable->setup();
$pastmonthtable->query_db(0); // No limit, fetch all rows.
$pastmonthtable->pageable(false);
$pastmonthtable->close_recordset();
$pastmonthmeetingcreateevents = $pastmonthtable->rawdata;

$output = "";

// Prepare attendee data for Attention Box.
$table->setup();
$table->query_db(0); // No limit, fetch all rows.
$table->pageable(false);
$table->close_recordset();
$meetingcreateevents = $table->rawdata;

if (isset($meetingsummary->meta)) {
    $meetingsummary->meta = json_decode($meetingsummary->meta);
}

if (!$download && !empty($meetingcreateevents)) {
    $tables = "";
    $output .= $OUTPUT->heading(get_string('view_analytics_heading_recordings', 'bigbluebuttonbn'), 3);

    // Function to help render the table given the data ($events) and a table name.
    $createoverviewtable = function($tablename, $events) {
        // Helper function to return a reducer that will sum an array of objects
        // given a key. This will default to zero for the value if the value does
        // not exist in the object.
        $sumkeyfunc = function($key) use($events) {
            return array_reduce($events, function($acc, $row) use ($key) {
                $data = json_decode($row->meta);
                return $acc + ($data->{$key} ?? 0);
            }, 0);
        };

        // Count all valid events.
        $totaleventswithrecordings = array_reduce($events, function($acc, $event){
            $data = json_decode($event->meta);
            $acc += isset($data->recordinglastmodified) ? 1 : 0;
            return $acc;
        }, 0);
        $processingdurationtotalinseconds = $sumkeyfunc('processingduration') / 1000;
        $filesizetotal = $sumkeyfunc('filesize');
        $overviewtable = new html_table();
        $overviewtable->caption = $tablename;
        $overviewtable->attributes['class'] = 'w-auto admintable generaltable mr-4 table-bordered';
        $htmlparams = ['class' => 'text-center'];
        $data = [
            [get_string('view_analytics_total_recordings', 'bigbluebuttonbn'), $totaleventswithrecordings],
            [get_string('view_analytics_total_storage_used', 'bigbluebuttonbn'), display_size($filesizetotal)],
            [
                get_string('view_analytics_total_playback_duration', 'bigbluebuttonbn'),
                userdate($sumkeyfunc('playbackduration') / 1000, get_string('strftimetime24seconds', 'bigbluebuttonbn'), 'UTC')
            ],
            [
                get_string('view_analytics_total_processing_time', 'bigbluebuttonbn'),
                userdate(
                    $processingdurationtotalinseconds,
                    get_string('strftimetime24seconds', 'bigbluebuttonbn'),
                    'UTC')
            ],
            [
                get_string('view_analytics_average_queue_time', 'bigbluebuttonbn'),
                userdate(
                    empty($totaleventswithrecordings) ? 0 : ($sumkeyfunc('queueduration') / 1000 / $totaleventswithrecordings),
                    get_string('strftimetime24seconds', 'bigbluebuttonbn'),
                    'UTC')
            ],
            [
                get_string('view_analytics_average_processing_time', 'bigbluebuttonbn'),
                userdate(
                    empty($totaleventswithrecordings) ? 0 : ($processingdurationtotalinseconds / $totaleventswithrecordings),
                    get_string('strftimetime24seconds', 'bigbluebuttonbn'),
                    'UTC' )
            ],
            [
                get_string('view_analytics_processing_speed', 'bigbluebuttonbn'),
                display_size($processingdurationtotalinseconds ? ($filesizetotal / $processingdurationtotalinseconds) : 0).' / sec'
            ],
        ];
        $overviewtable->data = $data;
        $overviewtablehtml = html_writer::table($overviewtable);
        return $overviewtablehtml;
    };

    // Generate and return the HTML for the Ovewview Section.
    $tables .= $createoverviewtable(
        get_string('view_analytics_heading_overview', 'bigbluebuttonbn'),
        $meetingcreateevents);

    // Generate and return the HTML for the Past Month Section.
    $tables .= $createoverviewtable(
        get_string('view_analytics_heading_past_month', 'bigbluebuttonbn'),
        $pastmonthmeetingcreateevents);

    // Add all tables to the output.
    $output .= '<div class="d-flex align-items-start">'.$tables.'</div>';
}

if (!$download) {
    echo $output;
}

$table->build_table();
$table->finish_output();

if (!$download) {
    echo $OUTPUT->footer();
}
