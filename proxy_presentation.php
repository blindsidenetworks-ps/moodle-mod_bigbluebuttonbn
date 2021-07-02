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

define('RECORD_ID_REGEX', '/[a-f0-9]{40}-[0-9]{13}/');

// If path is of format /presentation/{recording-id}/xxxx we need to check
// user has access to the recording in question. Urls of this format contain
// sensitive media.
$pathparts = explode('/', $relativepath);
if (isset($pathparts[1]) && $pathparts[1] === 'presentation' && isset($pathparts[2])) { // Legacy handling.
    $recordingid = $pathparts[2];
} else if (preg_match(RECORD_ID_REGEX, $relativepath, $matches) && !empty($matches)) { // BBB 2.3+ handling.
    $recordingid = reset($matches);
}

if (!empty($recordingid)) {
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

    // MeetingID can sometimes have [xxx] at the end. Needs sanitisation.
    if (strpos($bbbinstanceid, '[') !== false) {
        $bbbinstanceid = substr($bbbinstanceid, 0, strpos($bbbinstanceid, '['));
    }

    $viewinstance = bigbluebuttonbn_view_instance_bigbluebuttonbn($bbbinstanceid);
    require_login($viewinstance['course'], true, $viewinstance['cm']);
} else {
    require_login();
}

// One JS asset contains a non relative route we need to rewrite
// to be relative in order to work with the proxy. We also filter out the
// Content-Length Header in this case to avoid truncation.
$jstobereplaced = $relativepath === '/playback/presentation/2.0/lib/writing.js';

$curl = new curl();

if (!empty($_SERVER['HTTP_RANGE'])) {
    $curl->setHeader("Range: {$_SERVER['HTTP_RANGE']}");
}

$curl->setopt([
    'CURLOPT_CERTINFO'          => 1,
    'CURLOPT_SSL_VERIFYPEER'    => true,
    'CURLOPT_HEADERFUNCTION'    => function ($curl, $header) use ($jstobereplaced) {
        if (!$jstobereplaced ||  stripos($header, 'content-length') === false) {
            header($header);
        }
        return strlen($header);
    },
    'CURLOPT_WRITEFUNCTION'     => function ($curl, $body) use ($jstobereplaced, $relativepath) {
        // Even if we modify the body, we need to return the original length.
        $originalbodylength = strlen($body);

        if ($jstobereplaced) {
            $body = str_replace("let url = '/presentation/'", "let url = '../../../presentation/'", $body);
        }

        // HACK: Version 2.3+ replaces to ensure it routes all assets and subsequent calls through the proxy appropriately.
        if (strpos($relativepath, '2.0') === false && strpos($relativepath, '/capture/') === false) {
            // Due to it referencing other resources non-relatively, which was
            // implied for a non-proxied playback, it would not go through the
            // proxy and instead attempt to use a resource at which doesn't
            // exist in moodle-land.
            $proxybase = explode($relativepath, $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'])[0];
            // Replace absolute paths with relative paths, and add <base> to
            // ensure that's the base path used for all relative calls (rather
            // than doing many more str_replaces).
            $body = str_replace('src="/', 'src="', $body);
            $body = str_replace('href="/', 'href="', $body);
            $body = str_replace('<head>', '<head><base href="//'.$proxybase.'/">', $body);
            // This one was added as it seemed like it was being used to
            // reference the base URL of the web app. As this going through the
            // proxy, it would need to have this file as the prefix for the URL
            // instead.
            $body = str_replace(
                '/playback/presentation/',
                '/mod/bigbluebuttonbn/proxy_presentation.php/playback/presentation/',
                $body
            );
            // This was being set at the base prefix for all resource requests
            // such as meeting notes, video, cursor (mainly the XML includes).
            $body = str_replace('"/presentation"', '"/mod/bigbluebuttonbn/proxy_presentation.php/presentation"', $body);
        }

        echo $body;

        return $originalbodylength;
    },
]);

$curl->get(\mod_bigbluebuttonbn\locallib\bigbluebutton::root() . ltrim($relativepath, '/'));
