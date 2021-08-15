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
 * The recording entity.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\local\bigbluebutton\recordings;

use core\persistent;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\proxy\recording_proxy;
use stdClass;

/**
 * Utility class that defines a recording and provides methods for handlinging locally in Moodle and externally in BBB.
 *
 * Utility class for recording helper
 *
 * @package mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recording extends persistent {
    /** The table name. */
    const TABLE = 'bigbluebuttonbn_recordings';

    /** @var int Defines that the activity used to create the recording no longer exists */
    public const RECORDING_HEADLESS = 1;

    /** @var int Defines that the recording is not the original but an imported one */
    public const RECORDING_IMPORTED = 1;

    /** @var int Defines that the list should include imported recordings */
    public const INCLUDE_IMPORTED_RECORDINGS = true;

    /** @var int A meeting set to be recorded still awaits for a recording update */
    public const RECORDING_STATUS_AWAITING = 0;

    /** @var int A meeting set to be recorded was not recorded and dismissed by BBB */
    public const RECORDING_STATUS_DISMISSED = 1;

    /** @var int A meeting set to be recorded has a recording processed */
    public const RECORDING_STATUS_PROCESSED = 2;

    /** @var int A meeting set to be recorded received notification callback from BBB */
    public const RECORDING_STATUS_NOTIFIED = 3;

    /** @var bool $metadatachanged has metadata been changed so the remote information needs to be updated ? */
    protected $metadatachanged = false;

    /**
     * Create an instance of this class.
     *
     * @param int $id If set, this is the id of an existing record, used to load the data.
     * @param stdClass|null $record If set will be passed to from_record
     */
    public function __construct($id = 0, stdClass $record = null) {
        if ($record) {
            $record->headless = $record->headless ?? false;
            $record->imported = $record->imported ?? false;
            $record->groupid = $record->groupid ?? 0;
            $record->status = $record->status ?? self::RECORDING_STATUS_AWAITING;
        }
        parent::__construct($id, $record);
    }

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'courseid' => array(
                'type' => PARAM_INT,
            ),
            'bigbluebuttonbnid' => array(
                'type' => PARAM_INT,
            ),
            'groupid' => array(
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
            ),
            'recordingid' => array(
                'type' => PARAM_RAW,
            ),
            'headless' => array(
                'type' => PARAM_BOOL,
            ),
            'imported' => array(
                'type' => PARAM_BOOL,
            ),
            'status' => array(
                'type' => PARAM_INT,
            ),
            'remotedata' => array(
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => ''
            ),
            'remotedatatstamp' => array(
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null
            ),
            'name' => array(
                'type' => PARAM_TEXT,
                'null' => NULL_ALLOWED,
                'default' => null
            ),
            'description' => array(
                'type' => PARAM_TEXT,
                'null' => NULL_ALLOWED,
                'default' => 0
            ),
            'protected' => array(
                'type' => PARAM_BOOL,
                'null' => NULL_ALLOWED,
                'default' => null
            ),
            'starttime' => array(
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null
            ),
            'endtime' => array(
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null
            ),
            'published' => array(
                'type' => PARAM_BOOL,
                'null' => NULL_ALLOWED,
                'default' => null
            ),
            'protect' => array(
                'type' => PARAM_BOOL,
                'null' => NULL_ALLOWED,
                'default' => null
            ),
            'playbacks' => array(
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null
            ),
        );
    }

    /**
     * Before doing the database update, let's update metadata
     *
     * @return void
     */
    protected function before_update() {
        // We update if the remote metadata has been changed locally.
        $this->update_remotedata();
        $this->sync_remote_recording(); // Fetch again to be sure.
    }

    /**
     * Update remote data if metadata has changed.
     *
     */
    protected function update_remotedata() {
        // We update if the remote metadata has been changed locally.
        if ($this->metadatachanged) {
            if (!$this->get('imported') && $this->metadatachanged) {
                // As the recordingid was not identified as imported recording link, execute update on a real recording.
                // (No need to update imported links as the update only affects the actual recording).
                // Execute update on actual recording.
                // Check if any of the metatadata was touched then, we need to update the remote recording.
                recording_proxy::update_recording(
                    $this->get('recordingid'),
                    $this->remote_meta_convert());
                $this->metadatachanged = false;
                $this->set('remotedatatstamp', time());
            }
        }
    }

    /**
     * Update locally stored metadata from remote recording values.
     */
    public function sync_remote_recording() {
        if (!$this->get('imported')) {
            $rid = $this->get('recordingid');
            $this->raw_set('remotedatatstamp', time()); // Make sure we stop refreshing now.
            $bbbrecording = recording_proxy::fetch_recordings([$rid]);
            if (!empty($bbbrecording[$rid])) {
                $this->raw_set('remotedata', json_encode($bbbrecording[$rid]));
                $this->metadatachanged = false;
            }
        }
    }

    /**
     * Create a new imported recording from current recording
     *
     * @param instance $instance
     * @return recording
     * @throws \coding_exception
     * @throws \core\invalid_persistent_exception
     */
    public function create_imported_recording(instance $instance) {
        $recordingrec = $this->to_record();
        if ($this->must_sync()) {
            $this->sync_remote_recording(); // Make sure we have the right metadata.
        }
        unset($recordingrec->id);
        $recordingrec->bigbluebuttonbnid = $instance->get_instance_id();
        $recordingrec->courseid = $instance->get_course_id();
        $recordingrec->groupid = 0; // The recording is available to everyone.
        $recordingrec->remotedata = $this->raw_get('remotedata');
        $recordingrec->imported = true;
        $importedrecording = new recording(0, $recordingrec);
        $importedrecording->create();
        return $importedrecording;
    }

    /**
     * Delete the recording in the BBB button
     *
     * @return void
     */
    protected function before_delete() {
        $recordid = $this->get('recordingid');
        $imported = $this->get('imported');
        if ($recordid && !$imported) {
            recording_proxy::delete_recording($recordid);
        }
    }

    /**
     * Set name
     *
     * @param string $value
     */
    protected function set_name($value) {
        $this->remote_meta_set('name', trim($value));
    }

    /**
     * Set Description
     *
     * @param string $value
     */
    protected function set_description($value) {
        $this->remote_meta_set('description', trim($value));
    }

    /**
     * Recording is protected
     *
     * @param bool $value
     */
    protected function set_protected($value) {
        $realvalue = $value ? "true" : "false";
        $this->remote_meta_set('protected', $realvalue);
    }

    /**
     * Recording starttime
     *
     * @param int $value
     */
    protected function set_starttime($value) {
        $this->remote_meta_set('starttime', $value);
    }

    /**
     * Recording endtime
     *
     * @param int $value
     */
    protected function set_endtime($value) {
        $this->remote_meta_set('endtime', $value);
    }

    /**
     * Recording is published
     *
     * @param bool $value
     */
    protected function set_published($value) {
        $realvalue = $value ? "true" : "false";
        $this->remote_meta_set('published', $realvalue);
        // Now set this flag onto the remote bbb server.
        recording_proxy::publish_recording($this->get('recordingid'), $realvalue);
    }

    /**
     * POSSIBLE_REMOTE_META_SOURCE match a field type and its metadataname (historical and current).
     */
    const POSSIBLE_REMOTE_META_SOURCE = [
        'description' => array('meta_bbb-recording-description', 'meta_contextactivitydescription'),
        'name' => array('meta_bbb-recording-name', 'meta_contextactivity', 'meetingName'),
        'playbacks' => array('playbacks'),
        'starttime' => array('startTime'),
        'endtime' => array('endTime'),
        'published' => array('published'),
        'protected' => array('protect'),
        'tags' => array('meta_bbb-recording-tags')
    ];

    /**
     * Get the real metadata name for the possible source.
     *
     * @param string $sourcetype the name of the source we look for (name, description...)
     * @param array $metadata current metadata
     */
    protected function get_possible_meta_name_for_source($sourcetype, $metadata): string {
        $possiblesource = self::POSSIBLE_REMOTE_META_SOURCE[$sourcetype];
        $possiblesourcename = $possiblesource[0];
        foreach ($possiblesource as $possiblesname) {
            if (isset($meta[$possiblesname])) {
                $possiblesourcename = $possiblesname;
            }
        }
        return $possiblesourcename;
    }

    /**
     * Convert string (metadata) to json object
     *
     * @return mixed|null
     */
    protected function remote_meta_convert() {
        $remotemeta = $this->raw_get('remotedata');
        return json_decode($remotemeta, true);
    }

    /**
     * Description is stored in the metadata, so we sometimes needs to do some conversion.
     */
    protected function get_description() {
        return trim($this->remote_meta_get('description'));

    }

    /**
     * Name is stored in the metadata
     */
    protected function get_name() {
        return trim($this->remote_meta_get('name'));
    }

    /**
     * List of playbacks for this recording
     *
     * @return mixed|null
     * @throws \coding_exception
     */
    protected function get_playbacks() {
        return $this->remote_meta_get('playbacks');
    }

    /**
     * Is protected
     *
     * @return mixed|null
     * @throws \coding_exception
     */
    protected function get_protected() {
        return $this->remote_meta_get('protected');
    }

    /**
     * Start time
     *
     * @return mixed|null
     * @throws \coding_exception
     */
    protected function get_starttime() {
        return $this->remote_meta_get('starttime');
    }

    /**
     * Start time
     *
     * @return mixed|null
     * @throws \coding_exception
     */
    protected function get_endtime() {
        return $this->remote_meta_get('endtime');
    }

    /**
     * Is published
     *
     * @return mixed|null
     * @throws \coding_exception
     */
    protected function get_published() {
        $publishedtext = $this->remote_meta_get('published');
        return $publishedtext === "true" ? true : false;
    }

    /**
     * Set locally stored metadata from this instance
     *
     * @param string $fieldname
     * @param mixed $value
     * @throws \coding_exception
     */
    protected function remote_meta_set($fieldname, $value) {
        $this->metadatachanged = true;
        $meta = $this->remote_meta_convert();
        $possiblesourcename = $this->get_possible_meta_name_for_source($fieldname, $meta);
        $meta[$possiblesourcename] = $value;
        $this->raw_set('remotedata', json_encode($meta));
        $this->raw_set('remotedatatstamp', time());
    }

    /**
     * Get locally stored metadata from this instance
     *
     * @param string $fieldname
     * @return mixed|null
     */
    protected function remote_meta_get($fieldname) {
        if ($this->must_sync()) {
            $this->sync_remote_recording();
        }
        $meta = $this->remote_meta_convert();
        $possiblesourcename = $this->get_possible_meta_name_for_source($fieldname, $meta);
        return $meta[$possiblesourcename] ?? null;
    }

    /**
     * RESYNC_INTERVAL
     */
    const RESYNC_INTERVAL = 600; // 10 mins ?

    /**
     * Should we sync the metadata with remote recording metadata ?
     *
     * @return bool
     * @throws \coding_exception
     */
    protected function must_sync() {
        $rdatats = $this->raw_get('remotedatatstamp');
        $rdata = $this->raw_get('remotedata');

        return (empty($rdata) || ($rdatats + self::RESYNC_INTERVAL) < time());
    }
}
