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
 * Library calls for Moodle and BigBlueButton.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2017 Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->dirroot.'/calendar/lib.php');
require_once($CFG->dirroot.'/message/lib.php');
require_once($CFG->dirroot.'/mod/lti/OAuth.php');
require_once($CFG->dirroot.'/tag/lib.php');
require_once($CFG->libdir.'/accesslib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/datalib.php');
require_once($CFG->libdir.'/coursecatlib.php');
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/formslib.php');


if (file_exists(dirname(__FILE__).'/vendor/firebase/php-jwt/src/JWT.php')) {
    require_once(dirname(__FILE__).'/vendor/firebase/php-jwt/src/JWT.php');
}

if (!isset($CFG->bigbluebuttonbn)) {
    $CFG->bigbluebuttonbn = array();
}

if (file_exists(dirname(__FILE__).'/config.php')) {
    require_once(dirname(__FILE__).'/config.php');
    // Old BigBlueButtonBN cfg schema. For backward compatibility.
    global $BIGBLUEBUTTONBN_CFG;
    if (isset($BIGBLUEBUTTONBN_CFG)) {
        foreach ((array) $BIGBLUEBUTTONBN_CFG as $key => $value) {
            $cfgkey = str_replace("bigbluebuttonbn_", "", $key);
            $CFG->bigbluebuttonbn[$cfgkey] = $value;
        }
    }
}

/*
 * DURATIONCOMPENSATION: Feature removed by configuration
 */
$CFG->bigbluebuttonbn['scheduled_duration_enabled'] = 0;
/*
 * Remove this block when restored
 */

 /** @var BIGBLUEBUTTONBN_DEFAULT_SERVER_URL string of default bigbluebutton server url */
const BIGBLUEBUTTONBN_DEFAULT_SERVER_URL = 'http://test-install.blindsidenetworks.com/bigbluebutton/';
/** @var BIGBLUEBUTTONBN_DEFAULT_SHARED_SECRET string of default bigbluebutton server shared secret */
const BIGBLUEBUTTONBN_DEFAULT_SHARED_SECRET = '8cd8ef52e8e101574e400365b55e11a6';
/** @var BIGBLUEBUTTONBN_LOG_EVENT_CREATE string of event create for bigbluebuttonbn_logs */
const BIGBLUEBUTTONBN_LOG_EVENT_CREATE = 'Create';
/** @var BIGBLUEBUTTONBN_LOG_EVENT_JOIN string of event join for bigbluebuttonbn_logs */
const BIGBLUEBUTTONBN_LOG_EVENT_JOIN = 'Join';
/** @var BIGBLUEBUTTONBN_LOG_EVENT_LOGOUT string of event logout for bigbluebuttonbn_logs */
const BIGBLUEBUTTONBN_LOG_EVENT_LOGOUT = 'Logout';
/** @var BIGBLUEBUTTONBN_LOG_EVENT_IMPORT string of event import for bigbluebuttonbn_logs */
const BIGBLUEBUTTONBN_LOG_EVENT_IMPORT = 'Import';
/** @var BIGBLUEBUTTONBN_LOG_EVENT_DELETE string of event delete for bigbluebuttonbn_logs */
const BIGBLUEBUTTONBN_LOG_EVENT_DELETE = 'Delete';

/**
 * Indicates API features that the forum supports.
 *
 * @uses FEATURE_IDNUMBER
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_BACKUP_MOODLE2
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @uses FEATURE_SHOW_DESCRIPTION
 * @param string $feature
 * @return mixed True if yes (some features may use other values)
 */
function bigbluebuttonbn_supports($feature) {
    if (!$feature) {
        return null;
    }
    $features = array(
        (string) FEATURE_IDNUMBER => true,
        (string) FEATURE_GROUPS => true,
        (string) FEATURE_GROUPINGS => true,
        (string) FEATURE_GROUPMEMBERSONLY => true,
        (string) FEATURE_MOD_INTRO => true,
        (string) FEATURE_BACKUP_MOODLE2 => true,
        (string) FEATURE_COMPLETION_TRACKS_VIEWS => true,
        (string) FEATURE_GRADE_HAS_GRADE => false,
        (string) FEATURE_GRADE_OUTCOMES => false,
        (string) FEATURE_SHOW_DESCRIPTION => true,
    );
    if (isset($features[(string) $feature])) {
        return $features[$feature];
    }
    return null;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $data  An object from the form in mod_form.php
 * @return int The id of the newly inserted bigbluebuttonbn record
 */
function bigbluebuttonbn_add_instance($data) {
    global $DB;
    // Excecute preprocess.
    bigbluebuttonbn_process_pre_save($data);
    // Pre-set initial values.
    $data->presentation = bigbluebuttonbn_get_media_file($data);
    // Insert a record.
    $data->id = $DB->insert_record('bigbluebuttonbn', $data);
    // Encode meetingid.
    $meetingid = bigbluebuttonbn_encode_meetingid($data->id);
    // Set the meetingid column in the bigbluebuttonbn table.
    $DB->set_field('bigbluebuttonbn', 'meetingid', $meetingid, array('id' => $data->id));
    // Complete the process.
    bigbluebuttonbn_process_post_save($data);
    return $data->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $data  An object from the form in mod_form.php
 * @return bool Success/Fail
 */
function bigbluebuttonbn_update_instance($data) {
    global $DB;
    // Excecute preprocess.
    bigbluebuttonbn_process_pre_save($data);
    // Pre-set initial values.
    $data->id = $data->instance;
    $data->presentation = bigbluebuttonbn_get_media_file($data);
    // Update a record.
    $DB->update_record('bigbluebuttonbn', $data);
    // Complete the process.
    bigbluebuttonbn_process_post_save($data);
    return true;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 *
 * @return bool Success/Failure
 */
function bigbluebuttonbn_delete_instance($id) {
    global $DB;
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $id));
    if (!$bigbluebuttonbn) {
        return false;
    }
    // TODO: End the meeting if it is running.

    // Perform delete.
    if (!$DB->delete_records('bigbluebuttonbn', array('id' => $bigbluebuttonbn->id))) {
        return false;
    }
    if (!$DB->delete_records('event', array('modulename' => 'bigbluebuttonbn', 'instance' => $bigbluebuttonbn->id))) {
        return false;
    }
    // Log action performed.
    return bigbluebuttonbn_delete_instance_log($bigbluebuttonbn);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the data that depends on it.
 *
 * @param object $bigbluebuttonbn Id of the module instance
 *
 * @return bool Success/Failure
 */
function bigbluebuttonbn_delete_instance_log($bigbluebuttonbn) {
    global $DB, $USER;
    $log = new stdClass();
    $log->meetingid = $bigbluebuttonbn->meetingid;
    $log->courseid = $bigbluebuttonbn->course;
    $log->bigbluebuttonbnid = $bigbluebuttonbn->id;
    $log->userid = $USER->id;
    $log->timecreated = time();
    $log->log = BIGBLUEBUTTONBN_LOG_EVENT_DELETE;
    $sql  = "SELECT * FROM {bigbluebuttonbn_logs} ";
    $sql .= "WHERE bigbluebuttonbnid = ? AND log = ? AND ". $DB->sql_compare_text('meta') . " = ?";
    $logs = $DB->get_records_sql($sql, array($bigbluebuttonbn->id, BIGBLUEBUTTONBN_LOG_EVENT_CREATE, "{\"record\":true}"));
    $log->meta = "{\"has_recordings\":false}";
    if (!empty($logs)) {
        $log->meta = "{\"has_recordings\":true}";
    }
    if (!$DB->insert_record('bigbluebuttonbn_logs', $log)) {
        return false;
    }
    return true;
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description.
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $bigbluebuttonbn
 *
 * @return bool
 */
function bigbluebuttonbn_user_outline($course, $user, $mod, $bigbluebuttonbn) {
    global $DB;
    $completed = $DB->count_records('bigbluebuttonbn_logs', array('courseid' => $course->id,
        'bigbluebuttonbnid' => $bigbluebuttonbn->id, 'userid' => $user->id, 'log' => 'Join', ), '*');
    if ($completed > 0) {
        return fullname($user).' '.get_string('view_message_has_joined', 'bigbluebuttonbn').' '.
            get_string('view_message_session_for', 'bigbluebuttonbn').' '.(string) $completed.' '.
            get_string('view_message_times', 'bigbluebuttonbn');
    }
    return '';
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $bigbluebuttonbn
 *
 * @return bool
 */
function bigbluebuttonbn_user_complete($course, $user, $mod, $bigbluebuttonbn) {
    global $DB;
    $completed = $DB->count_records('bigbluebuttonbn_logs', array('courseid' => $course->id,
        'bigbluebuttonbnid' => $bigbluebuttonbn->id, 'userid' => $user->id, 'log' => 'Join', ),
        '*', IGNORE_MULTIPLE);
    return $completed > 0;
}

/**
 * Returns all other caps used in module.
 *
 * @return string[]
 */
function bigbluebuttonbn_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * List of view style log actions.
 *
 * @return string[]
 */
function bigbluebuttonbn_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List of update style log actions.
 *
 * @return string[]
 */
function bigbluebuttonbn_get_post_actions() {
    return array('update', 'add', 'create', 'join', 'end', 'left', 'publish', 'unpublish', 'delete');
}

/**
 * Print an overview of all bigbluebuttonbn instances for the courses.
 *
 * @param array $courses
 * @param array $htmlarray Passed by reference
 *
 * @return void
 */
function bigbluebuttonbn_print_overview($courses, &$htmlarray) {
    if (empty($courses) || !is_array($courses)) {
        return array();
    }
    $bns = get_all_instances_in_courses('bigbluebuttonbn', $courses);
    foreach ($bns as $bn) {
        $now = time();
        if ($bn->openingtime and (!$bn->closingtime or $bn->closingtime > $now)) {
            // A bigbluebuttonbn is scheduled.
            if (empty($htmlarray[$bn->course]['bigbluebuttonbn'])) {
                $htmlarray[$bn->course]['bigbluebuttonbn'] = '';
            }
            $htmlarray[$bn->course]['bigbluebuttonbn'] = bigbluebuttonbn_print_overview_element($bn, $now);
        }
    }
}

/**
 * Print an overview of a bigbluebuttonbn instance.
 *
 * @param array $bigbluebuttonbn
 * @param int $now
 *
 * @return string
 */
function bigbluebuttonbn_print_overview_element($bigbluebuttonbn, $now) {
    global $CFG;
    $start = 'started_at';
    if ($bigbluebuttonbn->openingtime > $now) {
        $start = 'starts_at';
    }
    $classes = '';
    if ($bigbluebuttonbn->visible) {
        $classes = 'class="dimmed" ';
    }
    $str  = '<div class="bigbluebuttonbn overview">'."\n";
    $str .= '  <div class="name">'.get_string('modulename', 'bigbluebuttonbn').':&nbsp;'."\n";
    $str .= '    <a '.$classes.'href="'.$CFG->wwwroot.'/mod/bigbluebuttonbn/view.php?id='.$bigbluebuttonbn->coursemodule.
      '">'.$bigbluebuttonbn->name.'</a>'."\n";
    $str .= '  </div>'."\n";
    $str .= '  <div class="info">'.get_string($start, 'bigbluebuttonbn').': '.userdate($bigbluebuttonbn->openingtime).
        '</div>'."\n";
    $str .= '  <div class="info">'.get_string('ends_at', 'bigbluebuttonbn').': '.userdate($bigbluebuttonbn->closingtime)
      .'</div>'."\n";
    $str .= '</div>'."\n";
    return $str;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php.
 *
 * @param object $coursemodule
 *
 * @return null|cached_cm_info
 */
function bigbluebuttonbn_get_coursemodule_info($coursemodule) {
    global $DB;
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $coursemodule->instance),
        'id, name, intro, introformat');
    if (!$bigbluebuttonbn) {
        return null;
    }
    $info = new cached_cm_info();
    $info->name = $bigbluebuttonbn->name;
    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('bigbluebuttonbn', $bigbluebuttonbn, $coursemodule->id, false);
    }
    return $info;
}

/**
 * Runs any processes that must run before a bigbluebuttonbn insert/update.
 *
 * @param object $bigbluebuttonbn BigBlueButtonBN form data
 *
 * @return void
 **/
function bigbluebuttonbn_process_pre_save(&$bigbluebuttonbn) {
    bigbluebuttonbn_process_pre_save_instance($bigbluebuttonbn);
    bigbluebuttonbn_process_pre_save_checkboxes($bigbluebuttonbn);
    bigbluebuttonbn_process_pre_save_common($bigbluebuttonbn);
    $bigbluebuttonbn->participants = htmlspecialchars_decode($bigbluebuttonbn->participants);
}

/**
 * Runs process for defining the instance (insert/update).
 *
 * @param object $bigbluebuttonbn BigBlueButtonBN form data
 *
 * @return void
 **/
function bigbluebuttonbn_process_pre_save_instance(&$bigbluebuttonbn) {
    $bigbluebuttonbn->timemodified = time();
    if ((integer)$bigbluebuttonbn->instance == 0) {
        $bigbluebuttonbn->timecreated = time();
        $bigbluebuttonbn->timemodified = 0;
        // As it is a new activity, assign passwords.
        $bigbluebuttonbn->moderatorpass = bigbluebuttonbn_random_password(12);
        $bigbluebuttonbn->viewerpass = bigbluebuttonbn_random_password(12, $bigbluebuttonbn->moderatorpass);
    }
}

/**
 * Runs process for assigning default value to checkboxes.
 *
 * @param object $bigbluebuttonbn BigBlueButtonBN form data
 *
 * @return void
 **/
function bigbluebuttonbn_process_pre_save_checkboxes(&$bigbluebuttonbn) {
    if (!isset($bigbluebuttonbn->wait)) {
        $bigbluebuttonbn->wait = 0;
    }
    if (!isset($bigbluebuttonbn->record)) {
        $bigbluebuttonbn->record = 0;
    }
    if (!isset($bigbluebuttonbn->recordings_html)) {
        $bigbluebuttonbn->recordings_html = 0;
    }
    if (!isset($bigbluebuttonbn->recordings_deleted)) {
        $bigbluebuttonbn->recordings_deleted = 0;
    }
    if (!isset($bigbluebuttonbn->recordings_imported)) {
        $bigbluebuttonbn->recordings_imported = 0;
    }
    if (!isset($bigbluebuttonbn->recordings_preview)) {
        $bigbluebuttonbn->recordings_preview = 0;
    }
}

/**
 * Runs process for wipping common settings when 'recordings only'.
 *
 * @param object $bigbluebuttonbn BigBlueButtonBN form data
 *
 * @return void
 **/
function bigbluebuttonbn_process_pre_save_common(&$bigbluebuttonbn) {
    // Make sure common settings are removed when 'recordings only'.
    if ($bigbluebuttonbn->type == BIGBLUEBUTTONBN_TYPE_RECORDING_ONLY) {
        $bigbluebuttonbn->groupmode = 0;
        $bigbluebuttonbn->groupingid = 0;
    }
}

/**
 * Runs any processes that must be run after a bigbluebuttonbn insert/update.
 *
 * @param object $bigbluebuttonbn BigBlueButtonBN form data
 *
 * @return void
 **/
function bigbluebuttonbn_process_post_save(&$bigbluebuttonbn) {
    if (isset($bigbluebuttonbn->notification) && $bigbluebuttonbn->notification) {
        bigbluebuttonbn_process_post_save_notification($bigbluebuttonbn);
    }
    bigbluebuttonbn_process_post_save_event($bigbluebuttonbn);
}

/**
 * Generates a message on insert/update which is sent to all users enrolled.
 *
 * @param object $bigbluebuttonbn BigBlueButtonBN form data
 *
 * @return void
 **/
function bigbluebuttonbn_process_post_save_notification(&$bigbluebuttonbn) {
    $action = get_string('mod_form_field_notification_msg_modified', 'bigbluebuttonbn');
    if (isset($bigbluebuttonbn->add) && !empty($bigbluebuttonbn->add)) {
        $action = get_string('mod_form_field_notification_msg_created', 'bigbluebuttonbn');
    }
    $context = context_course::instance($bigbluebuttonbn->course);
    \mod_bigbluebuttonbn\locallib\notifier::notification_process($context, $bigbluebuttonbn, $action);
}

/**
 * Generates an event after a bigbluebuttonbn insert/update.
 *
 * @param object $bigbluebuttonbn BigBlueButtonBN form data
 *
 * @return void
 **/
function bigbluebuttonbn_process_post_save_event(&$bigbluebuttonbn) {
    global $DB;
    // Delete evento to the calendar when/if openingtime is NOT set.
    if (!isset($bigbluebuttonbn->openingtime) || !$bigbluebuttonbn->openingtime) {
        $DB->delete_records('event', array('modulename' => 'bigbluebuttonbn', 'instance' => $bigbluebuttonbn->id));
        return;
    }
    // Add evento to the calendar as openingtime is set.
    $event = new stdClass();
    $event->name = $bigbluebuttonbn->name;
    $event->courseid = $bigbluebuttonbn->course;
    $event->groupid = 0;
    $event->userid = 0;
    $event->modulename = 'bigbluebuttonbn';
    $event->instance = $bigbluebuttonbn->id;
    $event->timestart = $bigbluebuttonbn->openingtime;
    $event->durationtime = 0;
    if ($bigbluebuttonbn->closingtime) {
        $event->durationtime = $bigbluebuttonbn->closingtime - $bigbluebuttonbn->openingtime;
    }
    $event->id = $DB->get_field('event', 'id', array('modulename' => 'bigbluebuttonbn',
        'instance' => $bigbluebuttonbn->id));
    if ($event->id) {
        $calendarevent = calendar_event::load($event->id);
        $calendarevent->update($event);
        return;
    }
    calendar_event::create($event);
}

/**
 * Get a full path to the file attached as a preuploaded presentation
 * or if there is none, set the presentation field will be set to blank.
 *
 * @param object $bigbluebuttonbn BigBlueButtonBN form data
 *
 * @return string
 */
function bigbluebuttonbn_get_media_file(&$bigbluebuttonbn) {
    if (!isset($bigbluebuttonbn->presentation) || $bigbluebuttonbn->presentation == '') {
        return '';
    }
    $context = context_module::instance($bigbluebuttonbn->coursemodule);
    // Set the filestorage object.
    $fs = get_file_storage();
    // Save the file if it exists that is currently in the draft area.
    file_save_draft_area_files($bigbluebuttonbn->presentation, $context->id, 'mod_bigbluebuttonbn', 'presentation', 0);
    // Get the file if it exists.
    $files = $fs->get_area_files($context->id, 'mod_bigbluebuttonbn', 'presentation', 0,
        'itemid, filepath, filename', false);
    // Check that there is a file to process.
    $filesrc = '';
    if (count($files) == 1) {
        // Get the first (and only) file.
        $file = reset($files);
        $filesrc = '/'.$file->get_filename();
    }
    return $filesrc;
}

/**
 * Serves the bigbluebuttonbn attachments. Implements needed access control ;-).
 *
 * @category files
 *
 * @param stdClass $course        course object
 * @param stdClass $cm            course module object
 * @param stdClass $context       context object
 * @param string   $filearea      file area
 * @param array    $args          extra arguments
 * @param bool     $forcedownload whether or not force download
 * @param array    $options       additional options affecting the file serving
 *
 * @return false|null false if file not found, does not return if found - justsend the file
 */
function bigbluebuttonbn_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    if (!bigbluebuttonbn_pluginfile_valid($context, $filearea)) {
        return false;
    }
    $file = bigbluebuttonbn_pluginfile_file($course, $cm, $context, $filearea, $args);
    if (empty($file)) {
        return false;
    }
    // Finally send the file.
    send_stored_file($file, 0, 0, $forcedownload, $options); // download MUST be forced - security!
}

/**
 * Helper for validating pluginfile.
 * @param stdClass $context       context object
 * @param string   $filearea      file area
 *
 * @return false|null false if file not valid
 */
function bigbluebuttonbn_pluginfile_valid($context, $filearea) {
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }
    if ($filearea !== 'presentation') {
        return false;
    }
    if (!array_key_exists($filearea, bigbluebuttonbn_get_file_areas())) {
        return false;
    }
    return true;
}

/**
 * Helper for getting pluginfile.
 *
 * @param stdClass $course        course object
 * @param stdClass $cm            course module object
 * @param stdClass $context       context object
 * @param string   $filearea      file area
 * @param array    $args          extra arguments
 *
 * @return object
 */
function bigbluebuttonbn_pluginfile_file($course, $cm, $context, $filearea, $args) {
    $filename = bigbluebuttonbn_pluginfile_filename($course, $cm, $context, $args);
    if (!$filename) {
        return false;
    }
    $fullpath = "/$context->id/mod_bigbluebuttonbn/$filearea/0/".$filename;
    $fs = get_file_storage();
    $file = $fs->get_file_by_hash(sha1($fullpath));
    if (!$file || $file->is_directory()) {
        return false;
    }
    return $file;
}

/**
 * Helper for getting pluginfile name.
 *
 * @param stdClass $course        course object
 * @param stdClass $cm            course module object
 * @param stdClass $context       context object
 * @param array    $args          extra arguments
 *
 * @return array
 */
function bigbluebuttonbn_pluginfile_filename($course, $cm, $context, $args) {
    global $DB;
    if (count($args) > 1) {
        if (!$bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $cm->instance))) {
            return;
        }
        $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'presentation_cache');
        $noncekey = sha1($bigbluebuttonbn->id);
        $presentationnonce = $cache->get($noncekey);
        $noncevalue = $presentationnonce['value'];
        $noncecounter = $presentationnonce['counter'];
        if ($args['0'] != $noncevalue) {
            return;
        }
        // The nonce value is actually used twice because BigBlueButton reads the file two times.
        $noncecounter += 1;
        $cache->set($noncekey, array('value' => $noncevalue, 'counter' => $noncecounter));
        if ($noncecounter == 2) {
            $cache->delete($noncekey);
        }
        return $args['1'];
    }
    require_course_login($course, true, $cm);
    if (!has_capability('mod/bigbluebuttonbn:join', $context)) {
        return;
    }
    return implode('/', $args);
}

/**
 * Returns an array of file areas.
 *
 * @category files
 *
 * @return array a list of available file areas
 */
function bigbluebuttonbn_get_file_areas() {
    $areas = array();
    $areas['presentation'] = get_string('mod_form_block_presentation', 'bigbluebuttonbn');
    return $areas;
}

/**
 * Get icon mapping for font-awesome.
 */
function mod_bigbluebuttonbn_get_fontawesome_icon_map() {
    return [
        'mod_bigbluebuttonbn:i/bigbluebutton' => 'fa-bigbluebutton',
    ];
}
