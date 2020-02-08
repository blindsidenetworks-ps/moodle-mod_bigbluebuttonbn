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
 * Intermediator for handling ajax requests resulting on BigBlueButton actions.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/locallib.php');
require_once(__DIR__.'/brokerlib.php');

global $PAGE, $USER, $CFG, $SESSION, $DB;

$params['action'] = optional_param('action', '', PARAM_TEXT);
$params['callback'] = optional_param('callback', '', PARAM_TEXT);
$params['id'] = optional_param('id', '', PARAM_TEXT);
$params['idx'] = optional_param('idx', '', PARAM_TEXT);
$params['bigbluebuttonbn'] = optional_param('bigbluebuttonbn', 0, PARAM_INT);
$params['signed_parameters'] = optional_param('signed_parameters', '', PARAM_TEXT);
$params['updatecache'] = optional_param('updatecache', 'false', PARAM_TEXT);
$params['meta'] = optional_param('meta', '', PARAM_TEXT);

require_login(null, true);
require_sesskey();

if (empty($params['action'])) {
    header('HTTP/1.0 400 Bad Request. Parameter ['.$params['action'].'] was not included');
    return;
}

$error = bigbluebuttonbn_broker_validate_parameters($params);
if (!empty($error)) {
    header('HTTP/1.0 400 Bad Request. '.$error);
    return;
}

if ($params['bigbluebuttonbn']) {
    $bbbbrokerinstance = bigbluebuttonbn_view_instance_bigbluebuttonbn($params['bigbluebuttonbn']);
    $cm = $bbbbrokerinstance['cm'];
    $bigbluebuttonbn = $bbbbrokerinstance['bigbluebuttonbn'];
    $context = context_module::instance($cm->id);
}

if (!isset($SESSION->bigbluebuttonbn_bbbsession) || is_null($SESSION->bigbluebuttonbn_bbbsession)) {
    header('HTTP/1.0 400 Bad Request. No session variable set');
    return;
}
$bbbsession = $SESSION->bigbluebuttonbn_bbbsession;

$userid = $USER->id;
if (!isloggedin() && $PAGE->course->id == SITEID) {
    $userid = guest_user()->id;
}
$hascourseaccess = ($PAGE->course->id == SITEID) || can_access_course($PAGE->course, $userid);

if (!$hascourseaccess) {
    header('HTTP/1.0 401 Unauthorized');
    return;
}

$type = null;
if (isset($bbbsession['bigbluebuttonbn']->type)) {
    $type = $bbbsession['bigbluebuttonbn']->type;
}

$typeprofiles = bigbluebuttonbn_get_instance_type_profiles();
$enabledfeatures = bigbluebuttonbn_get_enabled_features($typeprofiles, $type);
try {
    header('Content-Type: application/javascript; charset=utf-8');
    $a = strtolower($params['action']);
    if ($a == 'meeting_info') {
        $meetinginfo = bigbluebuttonbn_broker_meeting_info($bbbsession, $params, ($params['updatecache'] == 'true'));
        echo $meetinginfo;
        return;
    }
    if ($a == 'meeting_end') {
        $meetingend = bigbluebuttonbn_broker_meeting_end($bbbsession, $params);
        echo $meetingend;
        return;
    }
    if ($a == 'recording_play') {
        $recordingplay = bigbluebuttonbn_broker_recording_play($params);
        echo $recordingplay;
        return;
    }
    if ($a == 'recording_links') {
        $recordinglinks = bigbluebuttonbn_broker_recording_links($bbbsession, $params);
        echo $recordinglinks;
        return;
    }
    if ($a == 'recording_info') {
        $recordinginfo = bigbluebuttonbn_broker_recording_info($bbbsession, $params, $enabledfeatures['showroom']);
        echo $recordinginfo;
        return;
    }
    if ($a == 'recording_publish' || $a == 'recording_unpublish' ||
        $a == 'recording_delete' || $a == 'recording_edit' ||
        $a == 'recording_protect' || $a == 'recording_unprotect') {
        $recordingaction = bigbluebuttonbn_broker_recording_action($bbbsession, $params, $enabledfeatures['showroom']);
        echo $recordingaction;
        return;
    }
    if ($a == 'recording_import') {
        echo bigbluebuttonbn_broker_recording_import($bbbsession, $params);
        return;
    }
    header('HTTP/1.0 400 Bad request. The action '. $a . ' doesn\'t exist');
} catch (Exception $e) {
    header('HTTP/1.0 500 Internal Server Error. '.$e->getMessage());
}
