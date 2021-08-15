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
 * Mocked server class.
 *
 * @package mod_bigbluebuttonbn
 * @copyright 2021 Andrew Lyons <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bigbluebuttonbn\testing\generator;

use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\bigbluebutton\recordings\recording;
use moodle_url;
use SimpleXMLElement;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Class mockedserver
 *
 * @package mod_bigbluebuttonbn
 * @copyright 2021 Andrew Lyons <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mockedserver {
    /** @var string Type for room configuration */
    const TYPE_CONFIG = 'roomconfig';

    /** @var string Type for Recording list */
    const TYPE_RECORDINGS = 'recordings';

    /**
     * Handle the response for the specified path.
     *
     * @param string $path The PATH_INFO
     */
    public function serve(string $path): void {
        $metaparamconverter = function($paramkeys) {
            $allparams = [];
            foreach ($paramkeys as $key) {
                $allparams[$key] = optional_param('meta_' . $key, null, PARAM_RAW);
            }
            return $allparams;
        };
        $obtainparams = function($paramname, $default = '') {
            return optional_param($paramname, $default, PARAM_RAW);
        };
        list($status, $code, $response) = $this->do_serve($path, $metaparamconverter, $obtainparams);

        $this->http_respond($status, $code, $response);
    }

    /**
     * Handle the response for the specified path.
     *
     * @param string $path The PATH_INFO
     */
    public function query_server(string $path): string {
        list('path' => $realpath, 'query' => $query) = parse_url($path);
        $params = [];
        parse_str(str_replace('&amp;', '&', $query), $params);
        $metaparamconverter = function($paramkeys) use ($params) {
            $allparams = [];
            foreach ($paramkeys as $key) {
                $allparams[$key] = $params['meta_' . $key] ?? null;
            }
            return $allparams;
        };
        $obtainparams = function($paramname, $default = '') use ($params) {
            return $params[$paramname] ?? '';
        };

        list($status, $code, $response) = $this->do_serve($realpath, $metaparamconverter, $obtainparams);
        $response = (object) $response;
        $response->returncode = $status;
        $document = new SimpleXMLElement('<?xml version="1.0"?><response></response>');
        $this->convert_to_xml($document, $response);
        return $document->saveXML();
    }

    /**
     * Common routine for PHP unit and Behat
     *
     * @param string $path
     * @param callable $metaparamconverter
     * @param callable $obtainparams
     * @return array|string[]
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function do_serve(string $path, callable $metaparamconverter, callable $obtainparams) {
        list($status, $code, $response) = ['', '', ''];
        $meetingid = $obtainparams('meetingID');
        // TODO: the API seems to be oriented toward one or several recordings.
        $recordingsid = $obtainparams('recordID');
        switch ($path) {
            case '/api/':
                list($status, $code, $response) = $this->handle_status();
                break;
            case '/api/create':
                $metadata = $this->build_metadata(
                    $metaparamconverter(
                        array_keys($this->get_base_metadata())
                    ));
                $roomconfig = $this->build_room_from_params(
                    (object) $metadata,
                    $obtainparams('name'),
                    $obtainparams('attendeePW'),
                    $obtainparams('moderatorPW'),
                    $obtainparams('voiceBridge', '7000'),
                    $obtainparams('dialNumber', 0000)
                );
                list($status, $code, $response) = $this->handle_create_room((object) $roomconfig, $meetingid);
                break;
            case '/api/end':
                list($status, $code, $response) = $this->handle_end_meeting($meetingid);
                break;
            case '/api/getMeetingInfo':
                list($status, $code, $response) = $this->handle_get_meeting_info($meetingid);
                break;
            case '/api/join':
                list($status, $code, $response) = $this->handle_join_meeting($meetingid);
                break;
            case '/api/getRecordings':
                list($status, $code, $response) = $this->handle_get_recordings($meetingid, $recordingsid);
                break;
            case '/api/updateRecordings':
                $metadata = $this->build_metadata(
                    $metaparamconverter(
                        array_merge(
                            array_keys($this->get_base_metadata()),
                            ['bbb-recording-description', 'bbb-recording-name', 'bbb-recording-tags']
                        )));
                list($status, $code, $response) = $this->handle_update_recording($recordingsid, $metadata);
                break;
            case '/api/deleteRecordings':
                list($status, $code, $response) = $this->handle_delete_recording($recordingsid);
                break;
        }
        return array($status, $code, $response);
    }

    /**
     * Add an in-progress meeting with specific configuraiton.
     *
     * @param string $meetingid
     * @param stdClass $data
     * @return stdClass
     */
    public function add_meeting(string $meetingid, stdClass $data): stdClass {
        $creationtime = time();

        $metadata = $this->build_metadata([
            'bn-meetingid' => $meetingid,
            'bn-priority' => '20',
            'bn-protected' => 'true',
            'bn-userid' => 'moodle-testing',
        ]);
        $roomconfig = $this->build_room_from_params((object) $metadata, $meetingid, 'meetingname',
            'ap', 'mp', 0, 0000);
        $roomconfig = array_merge($roomconfig, [
            'internalMeetingID' => sha1($meetingid),
            'parentMeetingID' => 'bbb-none',
            'createTime' => $creationtime,
            'startTime' => $creationtime,
            'endTime' => 0,
            'createDate' => date('D M d H:i:s e Y', $creationtime),
            'hasUserJoined' => 'false',
            'recording' => 'true',
            'hasBeenForciblyEnded' => 'false',
            'messageKey' => [],
            'participantCount' => 0,
            'listenerCount' => 0,
            'videoCount' => 0,
            'maxUsers' => 0,
            'moderatorCount' => 0,
            'attendees' => [],
            'running' => 'true'
        ]);

        // Default room configuration.
        $roomconfig = (object) $roomconfig;

        // Merge in any existing room configuration.
        // This may be pre-configured from a test, or it may be from when the room was created.
        if ($preconfiguration = $this->find_room_configuration($meetingid)) {
            // phpcs:disable moodle.PHP.ForbiddenFunctions.FoundWithAlternative
            error_log("Found pre-config. Merging");
            // phpcs:enable moodle.PHP.ForbiddenFunctions.FoundWithAlternative
            $roomconfig = (object) array_merge(
                (array) $roomconfig,
                (array) $preconfiguration
            );
        }

        foreach ((array) $data as $key => $value) {
            $roomconfig->{$key} = $value;
        }

        $this->save_room_state($meetingid, self::TYPE_CONFIG, $roomconfig);

        return $roomconfig;
    }

    /**
     * Add a recording for the specified meeting.
     *
     * @param instance $instance
     * @param array $data
     * @return string newly created recording id
     */
    public function add_recording(instance $instance, array $data): string {
        $presentername = $data['presentername'] ?? 'Fake presenter';
        $starttime = $data['startTime'] ?? (time() - HOURSECS * rand(1, 15) * 24) * 1000; // Time is in ms.
        $endtime = $data['endTime'] ?? $starttime + HOURSECS * rand(1, 5) * 1000;// Time is in ms.
        $recordingname = $data['name'] ?? $instance->get_meeting_name();
        $recordingdesc = $data['description'] ?? '';
        $recordingtags = $data['tags'] ?? '';
        $meetingid = $instance->get_meeting_id();

        $metadata = $this->build_metadata(
            [
                'bbb-context' => $instance->get_course()->fullname,
                'bbb-context-id' => $instance->get_course()->id,
                'bbb-context-label' => $instance->get_course()->shortname,
                'bbb-context-name' => $instance->get_course()->fullname,
                'bbb-recording-description' => $recordingdesc,
                'bbb-recording-name' => $recordingname,
                'bbb-recording-tags' => $recordingtags
            ]
        );

        $metadata = (object) array_merge($metadata, [
            'isBreakout' => 'false',
            'bn-presenter-name' => $presentername,
            'bn-recording-ready-url' => new moodle_url('/mod/bigbluebuttonbn/bbb_broker.php',
                array(
                    'action' => 'recording_ready',
                    'bigbluebuttonbn' => $instance->get_instance_id()
                )),
        ]);
        $roomconfig = $this->build_room_from_params($metadata, $meetingid);
        $recordingconfig = array_merge($roomconfig, [
            'recordID' => self::create_recording_id($meetingid),
            'published' => 'true',
            'protected' => 'true',
            'startTime' => $starttime, // Time is in ms.
            'endTime' => $endtime,
            'participants' => 1,
            'playback' => (object) [
                'format' => (object) [
                    'type' => 'presentation',
                    'url' => '',
                    'length' => ($endtime - $starttime) / 1000, // In seconds.
                ],
            ],
        ]);

        $recording = (object) $recordingconfig;
        $recordings = $this->find_api_recordings_from_meetingid($meetingid);
        $recordings[$recording->recordID] = $recording;

        $this->save_room_state($meetingid, self::TYPE_RECORDINGS, $recordings);
        return $recording->recordID;
    }

    /**
     * Fetch recording data as it would be returned by the API
     *
     * @param string $meetingid
     * @param string $recordingid
     * @return array
     */
    public function fetch_recording(string $meetingid, string $recordingid): array {
        $filename = $this->get_meeting_status_filename($meetingid, self::TYPE_RECORDINGS);
        if (file_exists($filename)) {
            $recordings = json_decode(file_get_contents($filename));
            if ($recordings) {
                $recording = array_filter($recordings, function($rec) use ($recordingid) {
                    return $rec->recordID == $recordingid;
                });
                return $recording[0] ?? [];
            }
        }

        return [];
    }

    /**
     * Generate a recordingId as would BBB do it
     *
     * @param string $meetingid
     * @return string
     */
    public static function create_recording_id(string $meetingid): string {
        $baserecordid = md5($meetingid);
        $uid = time() + rand(1, 100000);
        return "$baserecordid-$uid";
    }

    /**
     * Get the meeting status file path.
     *
     * @param string $meetingid
     * @param string $type
     * @return string
     */
    protected function get_meeting_status_filename(string $meetingid, string $type): string {
        $datasourcedir = make_temp_directory('mod_bigbluebutton_mock');
        $hashedmeetingid = sha1($meetingid);

        return "{$datasourcedir}/{$type}_{$hashedmeetingid}.json";
    }

    /**
     * Fetch the room configuration for the specified room
     *
     * If no existing configuration is found, then a null value is returned.
     *
     * @param string $meetingid
     * @return null|stdClass
     */
    protected function find_room_configuration(string $meetingid): ?stdClass {
        $filename = $this->get_meeting_status_filename($meetingid, self::TYPE_CONFIG);
        if (file_exists($filename)) {
            return json_decode(file_get_contents($filename));
        }

        return null;
    }

    /**
     * Remove room configuration for a meeting.
     *
     * This may be used when ending a meeting.
     *
     * @param string $meetingid
     */
    protected function remove_room_configuration(string $meetingid): void {
        $filename = $this->get_meeting_status_filename($meetingid, self::TYPE_CONFIG);
        if (file_exists($filename)) {
            unlink($filename);
        }
    }

    /**
     * Find all recordings as returned by the API for the specified meeting
     *
     * @param string $meetingid
     * @return array
     */
    protected function find_api_recordings_from_meetingid(string $meetingid): array {
        $filename = $this->get_meeting_status_filename($meetingid, self::TYPE_RECORDINGS);
        if (file_exists($filename)) {
            $recordings = json_decode(file_get_contents($filename));
            $returnedrecordings = [];
            foreach ($recordings as $recording) {
                $returnedrecordings[$recording->recordID] = $recording;
            }
            return $returnedrecordings;
        }

        return [];
    }

    /**
     * Find all recordings returned by the API for the specified recordingsid
     *
     * @param array $recordingids
     * @return array
     */
    protected function find_api_recordings(array $recordingids): array {
        // We only have a trace of the meetingid in the bbbtable.
        $allrecordings = [];
        foreach ($recordingids as $rid) {
            $recording = $this->find_api_recording($rid);
            if ($recording) {
                $allrecordings[$recording->recordID] = $recording;
            }
        }
        return $allrecordings;
    }

    /**
     * Find related returned by the API for the specified recordingsid
     *
     * @param string $recordingid
     * @return object|null
     */
    protected function find_api_recording(string $recordingid): ?object {
        global $DB;
        // TODO: currenly we are creating an infinite loop if we use recording::get_record as it might
        // fetch the recording and then repeat the same loop. So best is
        // for now to directly query the DB just to get the bigbluebuttonbnid.
        $recordings = $DB->get_records(recording::TABLE, array('recordingid' => $recordingid, 'imported' => false));
        $recording = end($recordings);
        $instance = instance::get_from_instanceid($recording->bigbluebuttonbnid);
        // Here we will check in all possible groups for this course. This is not what BBB API is doing,
        // but as in the mock server the handle is the meetingId, we need to go through each group to check.

        $filenames = [];
        $filenames[] = $this->get_meeting_status_filename($instance->get_meeting_id(), self::TYPE_RECORDINGS);
        // TODO this might be an issue also on the real server as here we open up Moodle to retrieve
        // any recording even if we are not in the same group. Maybe we will need to add a groupid column to the recording table.
        if ($allgroups = groups_get_all_groups($instance->get_course_id())) {
            foreach ($allgroups as $g) {
                $filenames[] = $this->get_meeting_status_filename($instance->get_meeting_id($g->id), self::TYPE_RECORDINGS);
            }
        }
        foreach ($filenames as $filename) {
            if (file_exists($filename)) {
                foreach (json_decode(file_get_contents($filename)) as $frecording) {
                    if ($frecording->recordID == $recordingid) {
                        return $frecording;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Save the room state file.
     *
     * @param string $meetingid
     * @param string $type (See type constants)
     * @param mixed $meetingdata
     */
    protected function save_room_state(string $meetingid, string $type, $meetingdata): void {
        $filename = $this->get_meeting_status_filename($meetingid, $type);

        file_put_contents($filename, json_encode($meetingdata));
    }

    /**
     * Convert the data object to XML using SimpleXML.
     *
     * @param SimpleXMLElement $node
     * @param mixed $data
     */
    protected function convert_to_xml(SimpleXMLElement $node, $data): void {
        foreach ((array) $data as $key => $value) {
            if (is_object($value) && !empty($value->forcexmlarraytype)) {
                $newkey = $value->forcexmlarraytype;
                $value = array_values($value->array);
                $subnode = $node->addChild($key);
                foreach ($value as $val) {
                    $arraynode = $subnode->addChild($newkey);
                    $this->convert_to_xml($arraynode, $val);
                }

            } else {
                if (is_object($value)) {
                    $value = (array) $value;
                }
                if (is_numeric($key)) {
                    $key = "_{$key}";
                }
                if (is_array($value)) {
                    $subnode = $node->addChild($key);
                    $this->convert_to_xml($subnode, $value);
                } else {
                    $node->addChild((string) $key, htmlspecialchars($value));
                }
            }
        }
    }

    /**
     * Handle a request for the status page.
     */
    protected function handle_status(): array {
        return array('SUCCESS', 200, (object) ['version' => 0.9]);
    }

    /**
     * Handle a request to create a new room.
     *
     * @param object $roomconfig
     * @param string $meetingid
     * @return array
     */
    protected function handle_create_room(object $roomconfig, string $meetingid = ''): array {
        $roomconfig = $this->add_meeting($meetingid, $roomconfig);

        $responsekeys = [
            'attendeePW',
            'createDate',
            'createTime',
            'dialNumber',
            'hasBeenForciblyEnded',
            'hasUserJoined',
            'internalMeetingID',
            'meetingID',
            'messageKey',
            'moderatorPW',
            'parentMeetingID',
            'voiceBridge',
        ];

        $response = (object) [];
        foreach ($responsekeys as $key) {
            $response->{$key} = $roomconfig->{$key};
        }
        return array('SUCCESS', 200, $response);
    }

    /**
     * Build room config from either optional_params (priority 1) and/or default value provided as parameters
     *
     * @param object $metadata
     * @param string $meetingid
     * @param string $meetingname
     * @param string $attendeepw
     * @param string $moderatorpw
     * @param string $voicebridge
     * @param int $dialnumber
     * @return object
     * @throws \coding_exception
     */
    protected function build_room_from_params(object $metadata, string $meetingid = '', string $meetingname = '',
        string $attendeepw = '', string $moderatorpw = '',
        string $voicebridge = '7000',
        int $dialnumber = 0000): array {
        // Default room configuration.
        return [
            'meetingID' => $meetingid,
            'meetingName' => $meetingname,
            'attendeePW' => $attendeepw,
            'moderatorPW' => $moderatorpw,
            'voiceBridge' => $voicebridge,
            'dialNumber' => $dialnumber,
            'metadata' => $metadata,
        ];
    }

    /**
     * Build room config from either optional_params (priority 1) and/or default value provided as parameter
     *
     * @param array $additionalinfo an array with value that will override default values. If values are null they
     * will be ignored.
     * @return array
     */
    protected function build_metadata(array $additionalinfo): array {
        $basemetadata = $this->get_base_metadata();
        // We ignore null values.
        return array_merge($basemetadata, array_filter($additionalinfo,
            function($a) {
                return !is_null($a);
            }
        ));
    }

    /**
     * Get base metadata
     *
     * @return array
     * @throws \dml_exception
     */
    protected function get_base_metadata(): array {
        global $CFG;
        $pluginversion = get_config('mod_bigbluebuttonbn')->version;
        return [
            'bbb-context' => '',
            'bbb-context-id' => '',
            'bbb-context-label' => '',
            'bbb-context-name' => '',
            'bbb-origin' => 'Moodle',
            'bbb-origin-server-common-name' => parse_url($CFG->wwwroot, PHP_URL_HOST),
            'bbb-origin-server-name' => 'BBB Moodle',
            'bbb-origin-tag' => "moodle-mod_bigbluebuttonbn ({$pluginversion})",
            'bbb-origin-version' => $CFG->release
        ];
    }

    /**
     * Handle a request to end a meeting.
     *
     * @param string $meetingid
     * @return array
     * @throws \coding_exception
     */
    protected function handle_end_meeting(string $meetingid = ''): array {
        $roomconfig = $this->find_room_configuration($meetingid);
        if ($roomconfig === null) {
            return $this->send_room_not_found($meetingid);
        }

        $password = required_param('password', PARAM_RAW);
        if ($password !== $roomconfig->moderatorPW) {
            return array('FAILED', 403, $this->get_message('denied', 'Access denied'));
        }

        // Remove the config.
        $this->remove_room_configuration($meetingid);

        return array('SUCCESS', 200, $this->get_message(
            'sentEndMeetingRequest',
            'A request to end the meeting was sent. Please wait a few seconds, and then use '
            . 'the getMeetingInfo or isMeetingRunning API calls to verify that it was ended.'
        ));
    }

    /**
     * Handle a request to fetch meeting info for a room.
     *
     * @param string $meetingid
     * @return array
     */
    protected function handle_get_meeting_info(string $meetingid = ''): array {
        $roomconfig = $this->find_room_configuration($meetingid);

        if ($roomconfig === null) {
            return $this->send_room_not_found($meetingid);
        }

        $responsekeys = [
            'meetingName',
            'meetingID',
            'internalMeetingID',
            'parentMeetingID',
            'createTime',
            'createDate',
            'voiceBridge',
            'dialNumber',
            'attendeePW',
            'moderatorPW',
            'running',
            'hasUserJoined',
            'recording',
            'hasBeenForciblyEnded',
            'startTime',
            'endTime',
            'participantCount',
            'listenerCount',
            'videoCount',
            'maxUsers',
            'moderatorCount',
            'attendees',
            'metadata',
        ];

        $response = (object) [];
        foreach ($responsekeys as $key) {
            $response->{$key} = $roomconfig->{$key};
        }

        // Now some calculated keys.
        $response->duration = (int) ((int) microtime(true) - $response->createTime);
        // phpcs:disable moodle.PHP.ForbiddenFunctions.FoundWithAlternative,moodle.PHP.ForbiddenFunctions.Found
        error_log(print_r($response, true));
        // phpcs:enable moodle.PHP.ForbiddenFunctions.FoundWithAlternative,moodle.PHP.ForbiddenFunctions.Found
        return array('SUCCESS', 200, $response);
    }

    /**
     * Handle a request to join a meeting.
     *
     * @param string $meetingid
     * @return array
     * @throws \coding_exception
     */
    protected function handle_join_meeting(string $meetingid = ''): array {
        $roomconfig = $this->find_room_configuration($meetingid);
        if ($roomconfig === null) {
            return $this->send_room_not_found($meetingid);
        }

        $attendee = (object) [
            'userID' => required_param('userID', PARAM_INT),
            'fullName' => required_param('fullName', PARAM_RAW),
            'isListeningOnly' => 'false',
            'hasJoinedVoice' => 'true',
            'hasVideo' => 'true',
            'clientType' => 'HTML5',
            'customdata' => [],
        ];

        $password = required_param('password', PARAM_RAW);
        if ($password === $roomconfig->moderatorPW) {
            $attendee->role = 'MODERATOR';
            $attendee->isPresenter = 'true';
            $roomconfig->moderatorCount++;
        } else if ($password === $roomconfig->attendeePW) {
            $attendee->role = 'VIEWER';
            $attendee->isPresenter = 'false';
            $roomconfig->participantCount++;
        } else {
            return array('FAILED', 503, (object) []);
        }

        $roomconfig->attendees[] = $attendee;

        $this->save_room_state($meetingid, self::TYPE_CONFIG, $roomconfig);

        echo <<<EOF
<document>
    <head>
        <title>Mocked meeting</title>
        <script type="text/javascript">window.name = "Mocked meeting";</script>
    </head>
    <body>
        <div data-identifier="meetingName">{$roomconfig->meetingName}</div>
        <div data-identifier="fullName">{$attendee->fullName}</div>
        <div data-identifier="attendeeRole">{$attendee->role}</div>
    </body>
</document>
EOF;

        die;
    }

    /**
     * Handle a request to fetch a list of recordings for a room.
     *
     * @param string $meetingid
     * @param string $recordingsid
     * @return array
     */
    protected function handle_get_recordings(string $meetingid = '', string $recordingsid = ''): array {
        if ($meetingid) {
            $recordings = $this->find_api_recordings_from_meetingid($meetingid);
        } else {
            $recordings = $this->find_api_recordings(explode(',', $recordingsid));
        }
        if (empty($recordings)) {
            $response = $this->get_message('noRecordings', 'There are no recordings for the meeting(s).');
            $response->recordings = [];
        } else {
            $response = (object) [
                'recordings' => (object) ['forcexmlarraytype' => 'recording', 'array' => $recordings]
            ];
        }

        return array('SUCCESS', 200, $response);
    }

    /**
     * Handle a request to delete a recording
     *
     * @param string $recordingsid
     * @return array
     */
    protected function handle_delete_recording($recordingsid) {
        $recording = $this->find_api_recording($recordingsid);
        if ($recording) {
            $recordings = $this->find_api_recordings_from_meetingid($recording->meetingID);
            $recordings = array_filter($recordings, function($r) use ($recording) {
                return $r->recordID != $recording->recordID;
            });
            $this->save_room_state($recording->meetingID, self::TYPE_RECORDINGS, $recordings);
            return array('SUCCESS', 200, ['deleted' => true]);
        } else {
            $response = (object) [
                'messageKey' => 'notFound',
                'message' => 'We could not find a recording with that recording ID',
            ];
            return array('FAILED', 404, $response);
        }
    }

    /**
     * Handle a request to update a recording.
     *
     * @param string $recordingid
     * @param array $metadata
     * @return array
     */
    protected function handle_update_recording(string $recordingid, array $metadata): array {
        $recording = $this->find_api_recording($recordingid);
        if ($recording) {
            $updated = false;
            foreach ($metadata as $metakey => $metavalue) {
                if (!is_null($metavalue)) {
                    $recording->metadata->$metakey = $metavalue;
                    $updated = true;
                }
            }
            if ($updated) {
                $allrecordings = $this->find_api_recordings_from_meetingid($recording->meetingID);
                $allrecordings[$recording->recordID] = $recording;
                $this->save_room_state($recording->meetingID, self::TYPE_RECORDINGS, $allrecordings);
            }
            return array('SUCCESS', 200, ['updated' => true]);
        }
        $response = (object) [
            'messageKey' => 'notFound',
            'message' => 'We could not find a recording with that recording ID',
        ];
        return array('FAILED', 404, $response);
    }

    /**
     * Send a room not found response for a specific meeting.
     *
     * @param string $meetingid
     * @return array
     */
    protected function send_room_not_found(string $meetingid): array {
        $response = (object) [
            'messageKey' => 'notFound',
            'message' => 'We could not find a meeting with that meeting ID',
        ];

        return array('FAILED', 404, $response);
    }

    /**
     * Get a message response with the specified message
     *
     * @param string $messagekey
     * @param string $message
     * @return stdClass
     */
    protected function get_message(string $messagekey, string $message): stdClass {
        return (object) [
            'messageKey' => $messagekey,
            'message' => $message,
        ];
    }

    /**
     * Respond to the request.
     *
     * @param string $status
     * @param int $code
     * @param stdClass $response
     */
    protected function http_respond(string $status, int $code, stdClass $response): void {
        header('Content-Type: text/xml');
        header("HTTP/1.0 {$code}");

        $response->returncode = $status;

        $document = new SimpleXMLElement('<?xml version="1.0"?><response></response>');
        $this->convert_to_xml($document, $response);
        echo $document->saveXML();
        exit;
    }
}
