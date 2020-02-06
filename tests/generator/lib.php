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
 * mod_bigbluebuttonbn data generator
 *
 * @package    mod_bigbluebuttonbn
 * @category   test
 * @copyright  2018 - present, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/bigbluebuttonbn/lib.php');

/**
 * bigbluebuttonbn module data generator
 *
 * @package    mod_bigbluebuttonbn
 * @category   test
 * @copyright  2018 - present, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class mod_bigbluebuttonbn_generator extends \testing_module_generator {

    /**
     * Creates an instance of bigbluebuttonbn for testing purposes.
     *
     * @param array|stdClass $record data for module being generated.
     * @param null|array $options general options for course module.
     * @return stdClass record from module-defined table with additional field cmid
     */
    public function create_instance($record = null, array $options = null) {
        $now = time();
        $defaults = array(
                "type" => 0,
                "meetingid" => sha1(rand()),
                "record" => true,
                "moderatorpass" => "mp",
                "viewerpass" => "ap",
                "participants" => "{}",
                "timecreated" => $now,
                "timemodified" => $now,
                "presentation" => null,
        );
        $record = (array) $record;
        foreach ($defaults as $key => $value) {
            if (!isset($record[$key])) {
                $record[$key] = $value;
            }
        }
        return parent::create_instance((object) $record, (array) $options);
    }

    /**
     * Create a recording for the given bbb activity
     *
     * @param object $record  for the record
     * @param array|null $options other specific options
     * @return array the recording array
     * @throws dml_exception
     */
    public function create_recording($record, array $options = null) {
        global $CFG, $DB;
        $record = (array) $record;

        $bbactivityid = $record['bigbluebuttonbnid']; // Must be there.
        unset($record['bigbluebuttonbnid']);
        $bbactivity = $DB->get_record('bigbluebuttonbn', array('id' => $bbactivityid));

        $options = (array) $options;
        $defaultoptions = [
                'playbacknotes' => true,
                'playbackpresentation' => true,
                'recordingtime' => 1000,
                'remotehost' => 'localhost.com'
        ];
        $options = array_merge($defaultoptions, $options);

        // Get options.
        $recordingtime = $options['recordingtime'];
        $playbacknotes = $options['playbacknotes'];
        $playbackpresentation = $options['playbackpresentation'];
        $remotehost = $options['remotehost'];

        $timenow = time();
        $recordid = sha1(rand()) . '-' . $timenow;
        $playbacks = [];
        if ($playbacknotes) {
            $playbacks['notes'] = array(
                    'type' => 'notes',
                    'url' => "https://{$remotehost}/test-install/{$recordid}/notes",
                    'length' => '',
            );
        }
        if ($playbackpresentation) {
            $playbacks['presentation'] = array(
                    'type' => 'presentation',
                    'url' =>
                            "https://{$remotehost}/test-install/{$recordid}/presentation",
                    'length' => '',
            );
        }

        // Build the recording data.
        $recording = [
                'recordID' => $recordid,
                'meetingID' => $bbactivity->meetingid,
                'meetingName' => $bbactivity->name,
                'startTime' => $timenow,
                'endTime' => $timenow + $recordingtime,
                'playbacks' => $playbacks,
                'published' => 'true',
                'protected' => 'false',
                'meta_bbb-context-label' => 'testcourse_12',
                'meta_bbb-origin-server-name' => 'bigbluebuttonm.local',
                'meta_bbb-context' => 'Test course: BBB',
                'meta_analytics-callback-url' => $CFG->wwwroot .
                        '/mod/bigbluebuttonbn/bbb_broker.php?action=meeting_events&bigbluebuttonbn=' . $bbactivity->id,
                'meta_bbb-origin-tag' => 'moodle-mod_bigbluebuttonbn (2019101001)',
                'meta_bbb-origin-version' => '3.7.4+ (Build: 20200117)',
                'meta_bbb-recording-description' => '',
                'meta_bbb-recording-name' => $bbactivity->name,
                'meta_bbb-origin-server-common-name' => '',
                'meta_bbb-context-name' => get_course($bbactivity->course)->fullname,
                'meta_bbb-context-id' => \context_course::instance($bbactivity->course)->instanceid,
                'meta_bbb-recording-tags' => '',
                'meta_bbb-origin' => 'Moodle',
                'meta_isBreakout' => 'false',
                'meta_bn-presenter-name' => fullname(core_user::get_support_user()),
        ];

        $recording = array_merge($recording, $record); // Get all other values.

        // Add the logs if not we won't find anything.
        $this->create_log(['bigbluebuttonbnid' => $bbactivity->id, 'userid' => core_user::get_support_user()->id,
                'meta' => "{'record':true}"]);

        $this->bigbluebuttonbn_add_to_recordings_array_fetch($recording);
        return $recording;
    }

    /**
     * Create a log record
     * @param null $record
     * @param array|null $options
     * @throws dml_exception
     */
    public function create_log($record = null, array $options = null) {
        global $DB;
        $record = (array) $record;
        $bigbluebuttonbnid = $record['bigbluebuttonbnid'];
        $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $bigbluebuttonbnid));
        $default = [
                'meetingid' => $bigbluebuttonbn->meetingid . '-' . $bigbluebuttonbn->course . '-' . $bigbluebuttonbn->id,
        ];
        $record = array_merge($default, $record);
        bigbluebuttonbn_log($bigbluebuttonbn, BIGBLUEBUTTONBN_LOG_EVENT_CREATE, $record);
    }

    /**
     * Manages fake recording so we can cut off the API call while testing
     */

    /**
     * This the name of the $CFG entry to store the recording info in
     */
    const FAKE_RECORDING_VAR_NAME = 'bbb_fake_recordings';

    /**
     * This add a new mocked up recording
     * @param array $recording
     * @throws dml_exception
     */
    public function bigbluebuttonbn_add_to_recordings_array_fetch($recording) {
        global $CFG;
        $currentrecordings = get_config('mod_bigbluebuttonbn', static::FAKE_RECORDING_VAR_NAME);
        if (!$currentrecordings) {
            $currentrecordings = [];
        } else {
            $currentrecordings = unserialize($currentrecordings);
        }
        $currentrecordings[$recording['recordID']] = $recording;
        set_config(static::FAKE_RECORDING_VAR_NAME, serialize($currentrecordings), 'mod_bigbluebuttonbn');
    }

    /**
     * Method to fetch all mocked up recordings
     * @param int $meetingsid
     * @return array
     * @throws dml_exception
     */
    public static function bigbluebuttonbn_get_recordings_array_fetch($meetingsid) {
        global $CFG;
        $allrecordings = get_config('mod_bigbluebuttonbn', static::FAKE_RECORDING_VAR_NAME);
        if (!$allrecordings) {
            $allrecordings = [];
        }
        $allrecordings = unserialize($allrecordings);
        return array_filter($allrecordings,
                function($bbitem) use ($meetingsid) {
                    $meetingidrexp = "/{$bbitem['meetingID']}.*/";
                    return !empty(preg_grep($meetingidrexp, $meetingsid));
                }
        );
    }

    /**
     * Clean local recording array (between tests)
     */
    public function bigbluebuttonbn_clean_recordings_array_fetch() {
        global $CFG;
        set_config(static::FAKE_RECORDING_VAR_NAME, null, 'mod_bigbluebuttonbn');
    }
}
