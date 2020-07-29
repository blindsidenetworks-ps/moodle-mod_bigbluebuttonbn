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
 * Presentation proxy script.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Kenneth Hendricks (kennethhendricks@catalyst-au.net)
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once(dirname(__FILE__) . '/locallib.php');

$relativepath = get_file_argument();

if (empty($relativepath)) {
    exit();
}


// If path is of format /presentation/{recording-id}/xxxx we need to check
// user has access to the recording in question. Urls of this format contain
// sensitive media.
$pathparts = explode('/', $relativepath);
if (isset($pathparts[1]) && $pathparts[1] === 'presentation' && isset($pathparts[2])) {
    $recordingid = $pathparts[2];
    $recordings = bigbluebuttonbn_get_recordings_array_fetch_page([], [$recordingid]);

    if (!isset($recordings[$recordingid])) {
        throw new moodle_exception("invalidaccessparameter");
    }

    $meetingid = $recordings[$recordingid]['meetingID'];

    $meetingidparts = explode('-', $meetingid);
    if (!isset($meetingidparts[2])) {
        throw new moodle_exception("invalidaccessparameter");
    }

    $bbbinstanceid = $meetingidparts[2];
    $viewinstance = bigbluebuttonbn_view_instance_bigbluebuttonbn($bbbinstanceid);
    require_login($viewinstance['course'], true, $viewinstance['cm']);
} else {
    require_login();
}

// One JS asset contains a non relative route we need to rewrite
// to be relative in order to work with the proxy. We also filter out the
// Content-Length Header in this case to avoid truncation
$jstobereplaced = $relativepath === '/playback/presentation/2.0/lib/writing.js';

$curl = new curl();

if (!empty($_SERVER['HTTP_RANGE'])) {
    $curl->setHeader("Range: {$_SERVER['HTTP_RANGE']}");
}

$curl->setopt([
    'CURLOPT_CERTINFO'          => 1,
    'CURLOPT_SSL_VERIFYPEER'    => true,
    'CURLOPT_HEADERFUNCTION'    => function ($curl, $header) use ($jstobereplaced) {
        if (!$jstobereplaced || substr($header, 0, 14) !== "Content-Length") {
            header($header);
        }
        return strlen($header);
    },
    'CURLOPT_WRITEFUNCTION'     => function ($curl, $body) use ($jstobereplaced) {
        // Even if we modify the body, we need to return the original length
        $originalbodylength = strlen($body);

        if ($jstobereplaced) {
            $body = str_replace("let url = '/presentation/'", "let url = '../../../presentation/'", $body);
        }

        echo $body;

        return $originalbodylength;
    },
]);

$curl->get(\mod_bigbluebuttonbn\locallib\bigbluebutton::root() . $relativepath);
