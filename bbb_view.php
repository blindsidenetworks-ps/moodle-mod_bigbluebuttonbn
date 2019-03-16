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
 * View for BigBlueButton interaction.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

use mod_bigbluebuttonbn\plugin;

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/locallib.php');
require_once(__DIR__.'/bbb_viewlib.php');

global $SESSION;

$action = required_param('action', PARAM_TEXT);
$id = optional_param('id', 0, PARAM_INT);
$bn = optional_param('bn', 0, PARAM_INT);
$href = optional_param('href', '', PARAM_TEXT);
$mid = optional_param('mid', '', PARAM_TEXT);
$rid = optional_param('rid', '', PARAM_TEXT);
$rtype = optional_param('rtype', 'presentation', PARAM_TEXT);
$errors = optional_param('errors', '', PARAM_TEXT);
$timeline = optional_param('timeline', 0, PARAM_INT);
$index = optional_param('index', 0, PARAM_INT);
$group = optional_param('group', -1, PARAM_INT);

$bbbviewinstance = bigbluebuttonbn_view_validator($id, $bn);
if (!$bbbviewinstance) {
    print_error(get_string('view_error_url_missing_parameters', 'bigbluebuttonbn'));
}

$cm = $bbbviewinstance['cm'];
$course = $bbbviewinstance['course'];
$bigbluebuttonbn = $bbbviewinstance['bigbluebuttonbn'];
$context = context_module::instance($cm->id);

require_login($course, true, $cm);

$bbbsession = null;
if (isset($SESSION->bigbluebuttonbn_bbbsession)) {
    $bbbsession = $SESSION->bigbluebuttonbn_bbbsession;
}

if ($timeline || $index) {
    // If the user come from timeline or index page, the $bbbsession should be created or overriden here.
    $bbbsession['course'] = $course;
    $bbbsession['coursename'] = $course->fullname;
    $bbbsession['cm'] = $cm;
    $bbbsession['bigbluebuttonbn'] = $bigbluebuttonbn;
    bigbluebuttonbn_view_bbbsession_set($context, $bbbsession);

    // Validates if the BigBlueButton server is working.
    $serverversion = bigbluebuttonbn_get_server_version();
    if (is_null($serverversion)) {
        if ($bbbsession['administrator']) {
            print_error('view_error_unable_join', 'bigbluebuttonbn',
                $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn');
            exit;
        }
        if ($bbbsession['moderator']) {
            print_error('view_error_unable_join_teacher', 'bigbluebuttonbn',
                $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course);
            exit;
        }
        print_error('view_error_unable_join_student', 'bigbluebuttonbn',
            $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course);
        exit;
    }
    $bbbsession['serverversion'] = (string) $serverversion;

    // Operation URLs.
    $bbbsession['bigbluebuttonbnURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/view.php?id=' . $bbbsession['cm']->id;
    $bbbsession['logoutURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_view.php?action=logout&id='.$id .
        '&bn=' . $bbbsession['bigbluebuttonbn']->id;
    $bbbsession['recordingReadyURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_broker.php?action=recording_' .
        'ready&bigbluebuttonbn=' . $bbbsession['bigbluebuttonbn']->id;
    $bbbsession['meetingEventsURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_broker.php?action=meeting' .
        '_events&bigbluebuttonbn=' . $bbbsession['bigbluebuttonbn']->id;
    $bbbsession['joinURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_view.php?action=join&id=' . $cm->id .
        '&bn=' . $bbbsession['bigbluebuttonbn']->id;

    // Check status and set extra values.
    $activitystatus = bigbluebuttonbn_view_get_activity_status($bbbsession);
    if ($activitystatus == 'ended') {
        $bbbsession['presentation'] = bigbluebuttonbn_get_presentation_array(
            $bbbsession['context'], $bbbsession['bigbluebuttonbn']->presentation);
    } else if ($activitystatus == 'open') {
        $bbbsession['presentation'] = bigbluebuttonbn_get_presentation_array(
            $bbbsession['context'], $bbbsession['bigbluebuttonbn']->presentation, $bbbsession['bigbluebuttonbn']->id);
    }

    // Check group.
    if ($group >= 0) {
        $bbbsession['group'] = $group;
        $groupname = get_string('allparticipants');
        if ($bbbsession['group'] != 0) {
            $groupname = groups_get_group_name($bbbsession['group']);
        }

        // Assign group default values.
        $bbbsession['meetingid'] .= '['.$bbbsession['group'].']';
        $bbbsession['meetingname'] .= ' ('.$groupname.')';
    }

    // Initialize session variable used across views.
    $SESSION->bigbluebuttonbn_bbbsession = $bbbsession;
}

// Print the page header.
$PAGE->set_context($context);
$PAGE->set_url('/mod/bigbluebuttonbn/bbb_view.php', array('id' => $cm->id, 'bigbluebuttonbn' => $bigbluebuttonbn->id));
$PAGE->set_title(format_string($bigbluebuttonbn->name));
$PAGE->set_cacheable(false);
$PAGE->set_heading($course->fullname);
$PAGE->blocks->show_only_fake_blocks();

switch (strtolower($action)) {
    case 'logout':
        if (isset($errors) && $errors != '') {
            bigbluebutton_bbb_view_errors($errors, $id);
            break;
        }
        if (is_null($bbbsession)) {
            bigbluebutton_bbb_view_close_window_manually();
            break;
        }
        // Moodle event logger: Create an event for meeting left.
        bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events['meeting_left'], $bigbluebuttonbn);
        // Update the cache.
        $meetinginfo = bigbluebuttonbn_get_meeting_info($bbbsession['meetingid'], BIGBLUEBUTTONBN_UPDATE_CACHE);
        // Check the origin page.
        $records = $DB->get_records(
            'bigbluebuttonbn_logs',
            ['userid' => $bbbsession['userID'], 'log' => 'Join'],
            'id DESC',
            'id, meta',
            0,
            1
        );
        $lastaccess = null;
        if (!empty($records)) {
            $item = reset($records);
            $lastaccess = json_decode($item->meta);
        }
        // If the user acceded from Timeline it should be redirected to the Dashboard.
        if (isset($lastaccess->origin) && $lastaccess->origin == BIGBLUEBUTTON_ORIGIN_TIMELINE) {
            redirect($CFG->wwwroot . '/my/');
        }
        // Close the tab or window where BBB was opened.
        bigbluebutton_bbb_view_close_window();
        break;
    case 'join':
        if (is_null($bbbsession)) {
            print_error('view_error_unable_join', 'bigbluebuttonbn');
            break;
        }
        // Check the origin page.
        $origin = BIGBLUEBUTTON_ORIGIN_BASE;
        if ($timeline) {
            $origin = BIGBLUEBUTTON_ORIGIN_TIMELINE;
        } else if ($index) {
            $origin = BIGBLUEBUTTON_ORIGIN_INDEX;
        }
        // See if the session is in progress.
        if (bigbluebuttonbn_is_meeting_running($bbbsession['meetingid'])) {
            // Since the meeting is already running, we just join the session.
            bigbluebutton_bbb_view_join_meeting($bbbsession, $bigbluebuttonbn, $origin);
            break;
        }
        // If user is not administrator nor moderator (user is steudent) and waiting is required.
        if (!$bbbsession['administrator'] && !$bbbsession['moderator'] && $bbbsession['wait']) {
            header('Location: '.$bbbsession['logoutURL']);
            break;
        }
        // As the meeting doesn't exist, try to create it.
        $response = bigbluebuttonbn_get_create_meeting_array(
            bigbluebutton_bbb_view_create_meeting_data($bbbsession),
            bigbluebutton_bbb_view_create_meeting_metadata($bbbsession),
            $bbbsession['presentation']['name'],
            $bbbsession['presentation']['url']
        );
        if (empty($response)) {
            // The server is unreachable.
            if ($bbbsession['administrator']) {
                print_error('view_error_unable_join', 'bigbluebuttonbn',
                    $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn');
                break;
            }
            if ($bbbsession['moderator']) {
                print_error('view_error_unable_join_teacher', 'bigbluebuttonbn',
                    $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn');
                break;
            }
            print_error('view_error_unable_join_student', 'bigbluebuttonbn',
                $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn');
            break;
        }
        if ($response['returncode'] == 'FAILED') {
            // The meeting was not created.
            if (!$printerrorkey) {
                print_error($response['message'], 'bigbluebuttonbn');
                break;
            }
            $printerrorkey = bigbluebuttonbn_get_error_key($response['messageKey'], 'view_error_create');
            print_error($printerrorkey, 'bigbluebuttonbn');
            break;
        }
        if ($response['hasBeenForciblyEnded'] == 'true') {
            print_error(get_string('index_error_forciblyended', 'bigbluebuttonbn'));
            break;
        }
        // Moodle event logger: Create an event for meeting created.
        bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events['meeting_create'], $bigbluebuttonbn);
        // Internal logger: Insert a record with the meeting created.
        $overrides = array('meetingid' => $bbbsession['meetingid']);
        $meta = '{"record":'.($bbbsession['record'] ? 'true' : 'false').'}';
        bigbluebuttonbn_log($bbbsession['bigbluebuttonbn'], BIGBLUEBUTTONBN_LOG_EVENT_CREATE, $overrides, $meta);
        // Since the meeting is already running, we just join the session.
        bigbluebutton_bbb_view_join_meeting($bbbsession, $bigbluebuttonbn, $origin);
        break;
    case 'play':
        $href = bigbluebutton_bbb_view_playback_href($href, $mid, $rid, $rtype);
        // Moodle event logger: Create an event for meeting left.
        bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events['recording_play'], $bigbluebuttonbn,
            ['other' => $rid]);
        // Execute the redirect.
        header('Location: '.urldecode($href));
        break;
    default:
        bigbluebutton_bbb_view_close_window();
}
