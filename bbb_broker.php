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
 * Intermediator for handling requests from the BigBlueButton server.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @author    Darko Miletic  (darko.miletic [at] gmail [dt] com)
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/locallib.php');
require_once(__DIR__.'/brokerlib.php');

use \Firebase\JWT\JWT;

global $PAGE, $USER, $CFG, $SESSION, $DB;

$params['action'] = optional_param('action', '', PARAM_TEXT);
$params['callback'] = optional_param('callback', '', PARAM_TEXT);
$params['id'] = optional_param('id', '', PARAM_TEXT);
$params['idx'] = optional_param('idx', '', PARAM_TEXT);
$params['bigbluebuttonbn'] = optional_param('bigbluebuttonbn', 0, PARAM_INT);
$params['signed_parameters'] = optional_param('signed_parameters', '', PARAM_TEXT);
$params['updatecache'] = optional_param('updatecache', 'false', PARAM_TEXT);
$params['meta'] = optional_param('meta', '', PARAM_TEXT);

if (empty($params['action'])) {
    header('HTTP/1.0 400 Bad Request. Parameter ['.$params['action'].'] was not included');
    return;
}

// The endpoints for ajax requests are now implemented in bbb_ajax.php.
// The endpoints for recording_ready and live_session_event callbacks must be moved to services (CONTRIB-7440).
// But in order to support the transition, requests other than the callbacks are redirected to bbb_ajax.php.
if ($params['action'] != 'recording_ready' && $params['action'] != 'live_session_events') {
    $url = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_ajax.php?' . http_build_query($params, '', '&');
    header("Location: " . $url);
    exit;
}

$error = bigbluebuttonbn_broker_validate_parameters($params);
if (!empty($error)) {
    header('HTTP/1.0 400 Bad Request. '.$error);
    return;
}

if ($params['bigbluebuttonbn']) {
    $bbbbrokerinstance = bigbluebuttonbn_view_instance_bigbluebuttonbn($params['bigbluebuttonbn']);
    $bigbluebuttonbn = $bbbbrokerinstance['bigbluebuttonbn'];
}

try {
    $a = strtolower($params['action']);
    if ($a == 'recording_ready') {
        bigbluebuttonbn_broker_recording_ready($params, $bigbluebuttonbn);
        return;
    }
    if ($a == 'live_session_events') {
        bigbluebuttonbn_broker_live_session_events($params, $bigbluebuttonbn);
        return;
    }
    header('HTTP/1.0 400 Bad request. The action '. $a . ' doesn\'t exist');
} catch (Exception $e) {
    header('HTTP/1.0 500 Internal Server Error. '.$e->getMessage());
}
