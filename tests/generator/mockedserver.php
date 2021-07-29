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
        switch ($path) {
            case '/api/':
                $this->handle_status();
                break;
            case '/api/create':
                $this->handle_create_room();
                break;
            case '/api/end':
                $this->handle_end_meeting();
                break;
            case '/api/getMeetingInfo':
                $this->handle_get_meeting_info();
                break;
            case '/api/join':
                $this->handle_join_meeting();
                break;
            case '/api/getRecordings':
                $this->handle_get_recordings();
        }
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

        // Default room configuration.
        $roomconfig = (object) [
            'meetingID' => $meetingid,
            'internalMeetingID' => sha1($meetingid),
            'parentMeetingID' => 'bbb-none',

            'meetingName' => 'meetingname',

            'attendeePW' => 'ap',
            'moderatorPW' => 'mp',
            'voiceBridge' => 0,
            'dialNumber' => 0000,
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
            'metadata' => (object) [
                'bbb-context' => '',
                'bbb-context-id' => 0,
                'bbb-context-label' => '',
                'bbb-context-name' => '',
                'bbb-origin' => '',
                'bbb-origin-server-common-name' => '',
                'bbb-origin-server-name' => '',
                'bbb-origin-tag' => '',
                'bbb-origin-version' => '',
                'bbb-recording-description' => '',
                'bbb-recording-name' => '',
                'bbb-recording-tags' => '',
                'bn-meetingid' => $meetingid,
                'bn-priority' => '20',
                'bn-protected' => 'true',
                'bn-userid' => 'moodle-testing',
            ],
            'running' => 'true',
        ];

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
     * @param string $meetingid
     * @param stdClass $data
     * @param string $presentername
     * @param int $start
     * @param int $end
     */
    public function add_recording(string $meetingid, stdClass $data, string $presentername, int $start, int $end): void {
        global $CFG;

        $instance = instance::get_from_meetingid($meetingid);

        $recordings = $this->find_recordings($meetingid);
        $pluginversion = get_config('mod_bigbluebuttonbn')->version;
        $basic = [
            'recordingID' => '',
            'meetingID' => $meetingid,
            'name' => '',
            'published' => 'true',
            'protected' => 'true',
            'startTime' => time() - HOURSECS - rand(0, 3600),
            'endTime' => time() - rand(0, 3600),
            'participants' => 1,
            'metadata' => [
                'bbb-origin-version' => $CFG->release,
                'bbb-recording-tags' => '',
                'bbb-origin-server-name' => parse_url($CFG->wwwroot, PHP_URL_HOST),
                'bbb-recording-name' => $instance->get_meeting_name(),
                'bbb-recording-description' => '',
                'bbb-context-label' => $instance->get_course()->shortname,
                'bbb-context-id' => $instance->get_course()->id,
                'bbb-origin-server-common-name' => '',
                'bbb-origin-tag' => "moodle-mod_bigbluebuttonbn ({$pluginversion})",
                'bbb-context' => $instance->get_course()->fullname,
                'bbb-origin' => 'Moodle',
                'isBreakout' => 'false',
                'bbb-context-name' => $instance->get_course()->fullname,
                'bn-presenter-name' => $presentername
            ],
            'playback' => [
                'format' => [
                    'type' => 'presentation',
                    'url' => '',
                    'length' => $end - $start,
                ],
            ],
        ];

        $recording = array_merge($basic, (array) $data);
        $recording->playback->format->length = $recording->endTime - $recording->startTime;
        $recordings[] = $recording;

        $this->save_room_state($meetingid, self::TYPE_RECORDINGS, $recordings);
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
     * Find all recordings for the specified meeting
     *
     * @param string $meetingid
     * @return array
     */
    protected function find_recordings(string $meetingid): array {
        $filename = $this->get_meeting_status_filename($meetingid, self::TYPE_RECORDINGS);
        if (file_exists($filename)) {
            return json_decode(file_get_contents($filename));
        }

        return [];
    }

    /**
     * Save the room state file.
     *
     * @param string $meetingid
     * @param string $type (See type constants)
     * @param stdClass $meetingdata
     */
    protected function save_room_state(string $meetingid, string $type, stdClass $meetingdata): void {
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

    /**
     * Handle a request for the status page.
     */
    protected function handle_status(): void {
        $this->respond('SUCCESS', 200, (object) ['version' => 0.9]);
    }

    /**
     * Handle a request to create a new room.
     */
    protected function handle_create_room(): void {
        $meetingid = optional_param('meetingID', '', PARAM_RAW);

        // Default room configuration.
        $roomconfig = (object) [
            'meetingID' => $meetingid,
            'meetingName' => optional_param('name', '', PARAM_RAW),
            'attendeePW' => optional_param('attendeePW', '', PARAM_RAW),
            'moderatorPW' => optional_param('moderatorPW', '', PARAM_RAW),
            'voiceBridge' => optional_param('voiceBridge', '70000', PARAM_RAW),
            'dialNumber' => optional_param('dialNumber', 0000, PARAM_RAW),
            'metadata' => (object) [
                'bbb-context' => optional_param('meta_bbb-context', '', PARAM_RAW),
                'bbb-context-id' => optional_param('meta_bbb-context-id', '', PARAM_RAW),
                'bbb-context-label' => optional_param('meta_bbb-context-label', '', PARAM_RAW),
                'bbb-context-name' => optional_param('meta_bbb-context-name', '', PARAM_RAW),
                'bbb-origin' => optional_param('meta_bbb-origin', '', PARAM_RAW),
                'bbb-origin-server-common-name' => optional_param('meta_bbb-server-common-name', '', PARAM_RAW),
                'bbb-origin-server-name' => optional_param('meta_bbb-server-name', '', PARAM_RAW),
                'bbb-origin-tag' => optional_param('meta_bbb-origin-tag', '', PARAM_RAW),
                'bbb-origin-version' => optional_param('meta_bbb-version', '', PARAM_RAW),
                'bbb-recording-description' => optional_param('meta_bbb-recording-description', '', PARAM_RAW),
                'bbb-recording-name' => optional_param('meta_bbb-recording-name', '', PARAM_RAW),
                'bbb-recording-tags' => optional_param('meta_bbb-recording-tags', '', PARAM_RAW),
            ],
        ];

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

        $this->respond('SUCCESS', 200, $response);
    }

    /**
     * Handle a request to end a meeting.
     */
    protected function handle_end_meeting(): void {
        $meetingid = optional_param('meetingID', '', PARAM_RAW);

        $roomconfig = $this->find_room_configuration($meetingid);
        if ($roomconfig === null) {
            $this->send_room_not_found($meetingid);
        }

        $password = required_param('password', PARAM_RAW);
        if ($password !== $roomconfig->moderatorPW) {
            $this->respond('FAILED', 403, $this->get_message('denied', 'Access denied'));
        }

        // Remove the config.
        $this->remove_room_configuration($meetingid);

        $this->respond('SUCCESS', 200, $this->get_message(
            'sentEndMeetingRequest',
            'A request to end the meeting was sent. Please wait a few seconds, and then use '
            .'the getMeetingInfo or isMeetingRunning API calls to verify that it was ended.'
        ));
    }

    /**
     * Handle a request to fetch meeting info for a room.
     */
    protected function handle_get_meeting_info(): void {
        $meetingid = optional_param('meetingID', '', PARAM_RAW);

        $roomconfig = $this->find_room_configuration($meetingid);

        if ($roomconfig === null) {
            $this->send_room_not_found($meetingid);
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
        $this->respond('SUCCESS', 200, $response);
    }

    /**
     * Handle a request to join a meeting.
     */
    protected function handle_join_meeting(): void {
        $meetingid = optional_param('meetingID', '', PARAM_RAW);

        $roomconfig = $this->find_room_configuration($meetingid);
        if ($roomconfig === null) {
            $this->send_room_not_found($meetingid);
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
            $this->respond('FAILED', 503, (object) []);
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
     */
    protected function handle_get_recordings(): void {
        $meetingid = optional_param('meetingID', '', PARAM_RAW);

        $recordings = $this->find_recordings($meetingid);
        if (empty($recordings)) {
            $response = $this->get_message('noRecordings', 'There are no recordings for the meeting(s).');
            $response->recordings = [];
        } else {
            $response = (object) [
                'recordings' => $recordings,
            ];
        }

        $this->respond('SUCCESS', 200, $response);
    }

    /**
     * Send a room not found response for a specific meeting.
     *
     * @param string $meetingid
     */
    protected function send_room_not_found(string $meetingid): void {
        $response = (object) [
            'messageKey' => 'notFound',
            'message' => 'We could not find a meeting with that meeting ID',
        ];

        $this->respond('FAILED', 404, $response);
    }

    /**
     * Get a message response with the specified message
     *
     * @param string $messagekey
     * @param string $message
     * @return stdClas
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
    protected function respond(string $status, int $code, stdClass $response): void {
        header('Content-Type: text/xml');
        header("HTTP/1.0 {$code}");

        $response->returncode = $status;

        $document = new SimpleXMLElement('<?xml version="1.0"?><response></response>');
        $this->convert_to_xml($document, $response);
        echo $document->saveXML();
        exit;
    }
}
