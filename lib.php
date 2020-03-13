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
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;

// JWT is included in Moodle 3.7 core, but a local package is still needed for backward compatibility.
if (!class_exists('\Firebase\JWT\JWT')) {
    if (file_exists($CFG->libdir.'/php-jwt/src/JWT.php')) {
        require_once($CFG->libdir.'/php-jwt/src/JWT.php');
    } else {
        require_once($CFG->dirroot.'/mod/bigbluebuttonbn/vendor/firebase/php-jwt/src/JWT.php');
    }
}

if (!isset($CFG->bigbluebuttonbn)) {
    $CFG->bigbluebuttonbn = array();
}

if (file_exists(dirname(__FILE__).'/config.php')) {
    require_once(dirname(__FILE__).'/config.php');
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
/** @var BIGBLUEBUTTONBN_LOG_EVENT_ADD string of event add for bigbluebuttonbn_logs */
const BIGBLUEBUTTONBN_LOG_EVENT_ADD = 'Add';
/** @var BIGBLUEBUTTONBN_LOG_EVENT_EDIT string of event edit for bigbluebuttonbn_logs */
const BIGBLUEBUTTONBN_LOG_EVENT_EDIT = 'Edit';
/** @var BIGBLUEBUTTONBN_LOG_EVENT_CREATE string of event create for bigbluebuttonbn_logs */
const BIGBLUEBUTTONBN_LOG_EVENT_CREATE = 'Create';
/** @var BIGBLUEBUTTONBN_LOG_EVENT_JOIN string of event join for bigbluebuttonbn_logs */
const BIGBLUEBUTTONBN_LOG_EVENT_JOIN = 'Join';
/** @var BIGBLUEBUTTONBN_LOG_EVENT_PLAYED string of event record played for bigbluebuttonbn_logs */
const BIGBLUEBUTTONBN_LOG_EVENT_PLAYED = 'Played';
/** @var BIGBLUEBUTTONBN_LOG_EVENT_LOGOUT string of event logout for bigbluebuttonbn_logs */
const BIGBLUEBUTTONBN_LOG_EVENT_LOGOUT = 'Logout';
/** @var BIGBLUEBUTTONBN_LOG_EVENT_IMPORT string of event import for bigbluebuttonbn_logs */
const BIGBLUEBUTTONBN_LOG_EVENT_IMPORT = 'Import';
/** @var BIGBLUEBUTTONBN_LOG_EVENT_DELETE string of event delete for bigbluebuttonbn_logs */
const BIGBLUEBUTTONBN_LOG_EVENT_DELETE = 'Delete';
/** @var BIGBLUEBUTTON_LOG_EVENT_CALLBACK string defines the bigbluebuttonbn callback event */
const BIGBLUEBUTTON_LOG_EVENT_CALLBACK = 'Callback';
/**
 * Indicates API features that the bigbluebuttonbn supports.
 *
 * @uses FEATURE_IDNUMBER
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_BACKUP_MOODLE2
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_COMPLETION_HAS_RULES
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
 * @param object $bigbluebuttonbn  An object from the form in mod_form.php
 * @return int The id of the newly inserted bigbluebuttonbn record
 */
function bigbluebuttonbn_add_instance($bigbluebuttonbn) {
    global $DB;
    // Excecute preprocess.
    bigbluebuttonbn_process_pre_save($bigbluebuttonbn);
    // Pre-set initial values.
    $bigbluebuttonbn->presentation = bigbluebuttonbn_get_media_file($bigbluebuttonbn);
    // Insert a record.
    $bigbluebuttonbn->id = $DB->insert_record('bigbluebuttonbn', $bigbluebuttonbn);
    // Encode meetingid.
    $bigbluebuttonbn->meetingid = bigbluebuttonbn_unique_meetingid_seed();
    // Set the meetingid column in the bigbluebuttonbn table.
    $DB->set_field('bigbluebuttonbn', 'meetingid', $bigbluebuttonbn->meetingid, array('id' => $bigbluebuttonbn->id));
    // Log insert action.
    bigbluebuttonbn_log($bigbluebuttonbn, BIGBLUEBUTTONBN_LOG_EVENT_ADD);
    // Complete the process.
    bigbluebuttonbn_process_post_save($bigbluebuttonbn);
    return $bigbluebuttonbn->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $bigbluebuttonbn  An object from the form in mod_form.php
 * @return bool Success/Fail
 */
function bigbluebuttonbn_update_instance($bigbluebuttonbn) {
    global $DB;
    // Excecute preprocess.
    bigbluebuttonbn_process_pre_save($bigbluebuttonbn);
    // Pre-set initial values.
    $bigbluebuttonbn->id = $bigbluebuttonbn->instance;
    $bigbluebuttonbn->presentation = bigbluebuttonbn_get_media_file($bigbluebuttonbn);
    // Update a record.
    $DB->update_record('bigbluebuttonbn', $bigbluebuttonbn);
    // Get the meetingid column in the bigbluebuttonbn table.
    $bigbluebuttonbn->meetingid = (string)$DB->get_field('bigbluebuttonbn', 'meetingid', array('id' => $bigbluebuttonbn->id));
    // Log update action.
    bigbluebuttonbn_log($bigbluebuttonbn, BIGBLUEBUTTONBN_LOG_EVENT_EDIT);
    // Complete the process.
    bigbluebuttonbn_process_post_save($bigbluebuttonbn);
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

    if (!$bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $id))) {
        return false;
    }

    // TODO: End the meeting if it is running.

    $result = true;

    // Delete any dependent records here.
    if (!$DB->delete_records('bigbluebuttonbn', array('id' => $bigbluebuttonbn->id))) {
        $result = false;
    }

    if (!$DB->delete_records('event', array('modulename' => 'bigbluebuttonbn', 'instance' => $bigbluebuttonbn->id))) {
        $result = false;
    }

    // Log action performed.
    bigbluebuttonbn_delete_instance_log($bigbluebuttonbn);

    return $result;
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
    global $DB;
    $sql  = "SELECT * FROM {bigbluebuttonbn_logs} ";
    $sql .= "WHERE bigbluebuttonbnid = ? AND log = ? AND ". $DB->sql_compare_text('meta') . " = ?";
    $logs = $DB->get_records_sql($sql, array($bigbluebuttonbn->id, BIGBLUEBUTTONBN_LOG_EVENT_CREATE, "{\"record\":true}"));
    $meta = "{\"has_recordings\":" . empty($logs) ? "true" : "false" . "}";
    bigbluebuttonbn_log($bigbluebuttonbn, BIGBLUEBUTTONBN_LOG_EVENT_DELETE, [], $meta);
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $bigbluebuttonbn
 *
 * @return bool
 */
function bigbluebuttonbn_user_outline($course, $user, $mod, $bigbluebuttonbn) {
    if ($completed = bigbluebuttonbn_user_complete($course, $user, $mod, $bigbluebuttonbn)) {
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
 * @param object|int $courseorid
 * @param object|int $userorid
 * @param object $mod
 * @param object $bigbluebuttonbn
 *
 * @return bool
 */
function bigbluebuttonbn_user_complete($courseorid, $userorid, $mod, $bigbluebuttonbn) {
    global $DB;
    if (is_object($courseorid)) {
        $course = $courseorid;
    } else {
        $course = (object)array('id' => $courseorid);
    }
    if (is_object($userorid)) {
        $user = $userorid;
    } else {
        $user = (object)array('id' => $userorid);
    }
    $sql = "SELECT COUNT(*) FROM {bigbluebuttonbn_logs} ";
    $sql .= "WHERE courseid = ? AND bigbluebuttonbnid = ? AND userid = ? AND (log = ? OR log = ?)";
    $result = $DB->count_records_sql($sql, array($course->id, $bigbluebuttonbn->id, $user->id,
                                              BIGBLUEBUTTONBN_LOG_EVENT_JOIN, BIGBLUEBUTTONBN_LOG_EVENT_PLAYED));
    return $result;
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
 * Define items to be reset by course/reset.php
 *
 * @return array
 */
function bigbluebuttonbn_reset_course_items() {
    $items = array("events" => 0, "tags" => 0, "logs" => 0);
    // Include recordings only if enabled.
    if ((boolean)\mod_bigbluebuttonbn\locallib\config::recordings_enabled()) {
        $items["recordings"] = 0;
    }
    return $items;
}

/**
 * Called by course/reset.php
 *
 * @param object $mform
 * @return void
 */
function bigbluebuttonbn_reset_course_form_definition(&$mform) {
    $items = bigbluebuttonbn_reset_course_items();
    $mform->addElement('header', 'bigbluebuttonbnheader', get_string('modulenameplural', 'bigbluebuttonbn'));
    foreach ($items as $item => $default) {
        $mform->addElement('advcheckbox', "reset_bigbluebuttonbn_{$item}"
            , get_string("reset{$item}", 'bigbluebuttonbn')
        );
        if ($item == 'logs' || $item == 'recordings') {
            $mform->addHelpButton("reset_bigbluebuttonbn_{$item}", "reset{$item}", 'bigbluebuttonbn');
        }
    }
}

/**
 * Course reset form defaults.
 *
 * @param object $course
 * @return array
 */
function bigbluebuttonbn_reset_course_form_defaults($course) {
    $formdefaults = array();
    $items = bigbluebuttonbn_reset_course_items();
    // All unchecked by default.
    foreach ($items as $item => $default) {
        $formdefaults["reset_bigbluebuttonbn_{$item}"] = $default;
    }
    return $formdefaults;
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 *
 * @param array $data the data submitted from the reset course.
 * @return array status array
 */
function bigbluebuttonbn_reset_userdata($data) {
    $items = bigbluebuttonbn_reset_course_items();
    $status = array();
    // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
    // See MDL-9367.
    if (array_key_exists('recordings', $items) && !empty($data->reset_bigbluebuttonbn_recordings)) {
        // Remove all the recordings from a BBB server that are linked to the room/activities in this course.
        bigbluebuttonbn_reset_recordings($data->courseid);
        unset($items['recordings']);
        $status[] = bigbluebuttonbn_reset_getstatus('recordings');
    }
    if (!empty($data->reset_bigbluebuttonbn_tags)) {
        // Remove all the tags linked to the room/activities in this course.
        bigbluebuttonbn_reset_tags($data->courseid);
        unset($items['tags']);
        $status[] = bigbluebuttonbn_reset_getstatus('tags');
    }
    foreach ($items as $item => $default) {
        // Remove instances or elements linked to this course, others than recordings or tags.
        if (!empty($data->{"reset_bigbluebuttonbn_{$item}"})) {
            call_user_func("bigbluebuttonbn_reset_{$item}", $data->courseid);
            $status[] = bigbluebuttonbn_reset_getstatus($item);
        }
    }
    return $status;
}

/**
 * Returns status used on every defined reset action.
 *
 * @param string $item
 * @return array status array
 */
function bigbluebuttonbn_reset_getstatus($item) {
    return array('component' => get_string('modulenameplural', 'bigbluebuttonbn')
        , 'item' => get_string("removed{$item}", 'bigbluebuttonbn')
        , 'error' => false);
}

/**
 * Used by the reset_course_userdata for deleting events linked to bigbluebuttonbn instances in the course.
 *
 * @param string $courseid
 * @return array status array
 */
function bigbluebuttonbn_reset_events($courseid) {
    global $DB;
    // Remove all the events.
    return $DB->delete_records('event', array('modulename' => 'bigbluebuttonbn', 'courseid' => $courseid));
}

/**
 * Used by the reset_course_userdata for deleting tags linked to bigbluebuttonbn instances in the course.
 *
 * @param array $courseid
 * @return array status array
 */
function bigbluebuttonbn_reset_tags($courseid) {
    global $DB;
    // Remove all the tags linked to the room/activities in this course.
    if ($bigbluebuttonbns = $DB->get_records('bigbluebuttonbn', array('course' => $courseid))) {
        foreach ($bigbluebuttonbns as $bigbluebuttonbn) {
            if (!$cm = get_coursemodule_from_instance('bigbluebuttonbn', $bigbluebuttonbn->id, $courseid)) {
                continue;
            }
            $context = context_module::instance($cm->id);
            core_tag_tag::delete_instances('mod_bigbluebuttonbn', null, $context->id);
        }
    }
}

/**
 * Used by the reset_course_userdata for deleting bigbluebuttonbn_logs linked to bigbluebuttonbn instances in the course.
 *
 * @param string $courseid
 * @return array status array
 */
function bigbluebuttonbn_reset_logs($courseid) {
    global $DB;
    // Remove all the logs.
    return $DB->delete_records('bigbluebuttonbn_logs', array('courseid' => $courseid));
}

/**
 * Used by the reset_course_userdata for deleting recordings in a BBB server linked to bigbluebuttonbn instances in the course.
 *
 * @param string $courseid
 * @return array status array
 */
function bigbluebuttonbn_reset_recordings($courseid) {
    require_once(__DIR__.'/locallib.php');
    // Criteria for search [courseid | bigbluebuttonbn=null | subset=false | includedeleted=true].
    $recordings = bigbluebuttonbn_get_recordings($courseid, null, false, true);
    // Remove all the recordings.
    bigbluebuttonbn_delete_recordings(implode(",", array_keys($recordings)));
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
    return array('update', 'add', 'delete');
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
            $htmlarray[$bn->course]['bigbluebuttonbn'] .= bigbluebuttonbn_print_overview_element($bn, $now);
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
    require_once(__DIR__.'/locallib.php');
    $bigbluebuttonbn->timemodified = time();
    if ((integer)$bigbluebuttonbn->instance == 0) {
        $bigbluebuttonbn->meetingid = 0;
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
    if (!isset($bigbluebuttonbn->recordallfromstart)) {
        $bigbluebuttonbn->recordallfromstart = 0;
    }
    if (!isset($bigbluebuttonbn->recordhidebutton)) {
        $bigbluebuttonbn->recordhidebutton = 0;
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
    if (!isset($bigbluebuttonbn->muteonstart)) {
        $bigbluebuttonbn->muteonstart = 0;
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
    bigbluebuttonbn_process_post_save_completion($bigbluebuttonbn);
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
    \mod_bigbluebuttonbn\locallib\notifier::notification_process($bigbluebuttonbn, $action);
}

/**
 * Generates an event after a bigbluebuttonbn insert/update.
 *
 * @param object $bigbluebuttonbn BigBlueButtonBN form data
 *
 * @return void
 **/
function bigbluebuttonbn_process_post_save_event(&$bigbluebuttonbn) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/calendar/lib.php');
    $eventid = $DB->get_field('event', 'id', array('modulename' => 'bigbluebuttonbn',
        'instance' => $bigbluebuttonbn->id));
    // Delete the event from calendar when/if openingtime is NOT set.
    if (!isset($bigbluebuttonbn->openingtime) || !$bigbluebuttonbn->openingtime) {
        if ($eventid) {
            $calendarevent = calendar_event::load($eventid);
            $calendarevent->delete();
        }
        return;
    }
    // Add evento to the calendar as openingtime is set.
    $event = new stdClass();
    $event->eventtype = BIGBLUEBUTTON_EVENT_MEETING_START;
    $event->type = CALENDAR_EVENT_TYPE_ACTION;
    $event->name = get_string('calendarstarts', 'bigbluebuttonbn', $bigbluebuttonbn->name);
    $event->description = format_module_intro('bigbluebuttonbn', $bigbluebuttonbn, $bigbluebuttonbn->coursemodule);
    $event->courseid = $bigbluebuttonbn->course;
    $event->groupid = 0;
    $event->userid = 0;
    $event->modulename = 'bigbluebuttonbn';
    $event->instance = $bigbluebuttonbn->id;
    $event->timestart = $bigbluebuttonbn->openingtime;
    $event->timeduration = 0;
    $event->timesort = $event->timestart;
    $event->visible = instance_is_visible('bigbluebuttonbn', $bigbluebuttonbn);
    $event->priority = null;
    // Update the event in calendar when/if eventid was found.
    if ($eventid) {
        $event->id = $eventid;
        $calendarevent = calendar_event::load($eventid);
        $calendarevent->update($event);
        return;
    }
    calendar_event::create($event);
}

/**
 * Generates an event after a bigbluebuttonbn activity is completed.
 *
 * @param object $bigbluebuttonbn BigBlueButtonBN form data
 *
 * @return void
 **/
function bigbluebuttonbn_process_post_save_completion($bigbluebuttonbn) {
    if (!empty($bigbluebuttonbn->completionexpected)) {
        \core_completion\api::update_completion_date_event(
            $bigbluebuttonbn->coursemodule,
            'bigbluebuttonbn',
            $bigbluebuttonbn->id, $bigbluebuttonbn->completionexpected
          );
    }
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

    // Can be in context module or in context_system (if is the presentation by default).
    if (!in_array($context->contextlevel, array(CONTEXT_MODULE, CONTEXT_SYSTEM))) {
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
 * Helper for give access to the file configured in setting as default presentation.
 *
 * @param stdClass $course        course object
 * @param stdClass $cm            course module object
 * @param stdClass $context       context object
 * @param array    $args          extra arguments
 *
 * @return array
 */
function bigbluebuttonbn_default_presentation_get_file($course, $cm, $context, $args) {

    // The difference with the standard bigbluebuttonbn_pluginfile_filename() are.
    // - Context is system, so we don't need to check the cmid in this case.
    // - The area is "presentationdefault_cache".
    if (count($args) > 1) {
        $cache = cache::make_from_params(cache_store::MODE_APPLICATION,
            'mod_bigbluebuttonbn',
            'presentationdefault_cache');

        $noncekey = sha1($context->id);
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
        return($args['1']);
    }
    require_course_login($course, true, $cm);
    if (!has_capability('mod/bigbluebuttonbn:join', $context)) {
        return;
    }
    return implode('/', $args);
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

    if ($context->contextlevel == CONTEXT_SYSTEM) {
        // Plugin has a file to use as default in general setting.
        return(bigbluebuttonbn_default_presentation_get_file($course, $cm, $context, $args));
    }

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
    $areas['presentationdefault'] = get_string('mod_form_block_presentation_default', 'bigbluebuttonbn');
    return $areas;
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $bigbluebuttonbn        bigbluebuttonbn object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.0
 */
function bigbluebuttonbn_view($bigbluebuttonbn, $course, $cm, $context) {

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $bigbluebuttonbn->id
    );

    $event = \mod_bigbluebuttonbn\event\activity_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('bigbluebuttonbn', $bigbluebuttonbn);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function bigbluebuttonbn_check_updates_since(cm_info $cm, $from, $filter = array()) {
    $updates = course_check_module_updates_since($cm, $from, array('content'), $filter);
    return $updates;
}


/**
 * Get icon mapping for font-awesome.
 */
function mod_bigbluebuttonbn_get_fontawesome_icon_map() {
    return [
        'mod_bigbluebuttonbn:icon' => 'icon-bigbluebutton',
    ];
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_bigbluebuttonbn_core_calendar_provide_event_action(calendar_event $event,
        \core_calendar\action_factory $factory) {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');

    // Get mod info.
    $cm = get_fast_modinfo($event->courseid)->instances['bigbluebuttonbn'][$event->instance];

    // Get bigbluebuttonbn activity.
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $event->instance), '*', MUST_EXIST);

    // Get if the user has joined in live session or viewed the recorded.
    $usercomplete = bigbluebuttonbn_user_complete($event->courseid, $event->userid, null, $bigbluebuttonbn);
    // Get if the room is available.
    list($roomavailable) = bigbluebuttonbn_room_is_available($bigbluebuttonbn);
    // Get if the user can join.
    list($usercanjoin) = bigbluebuttonbn_user_can_join_meeting($bigbluebuttonbn);
    // Get if the time has already passed.
    $haspassed = $bigbluebuttonbn->openingtime < time();

    // Check if the room is closed and the user has already joined this session or played the record.
    if ($haspassed && !$roomavailable && $usercomplete) {
        return null;
    }

    // Check if the user can join this session.
    $actionable = ($roomavailable && $usercanjoin) || $haspassed;

    // Action data.
    $string = get_string('view_room', 'bigbluebuttonbn');
    $url = new \moodle_url('/mod/bigbluebuttonbn/view.php', array('id' => $cm->id));
    if (groups_get_activity_groupmode($cm) == NOGROUPS) {
        // No groups mode.
        $string = get_string('view_conference_action_join', 'bigbluebuttonbn');
        $url = new \moodle_url('/mod/bigbluebuttonbn/bbb_view.php', array('action' => 'join',
            'id' => $cm->id, 'bn' => $bigbluebuttonbn->id, 'timeline' => 1));
    }

    return $factory->create_instance($string, $url, 1, $actionable);
}

/**
 * Register a bigbluebuttonbn event
 *
 * @param object $bigbluebuttonbn
 * @param string $event
 * @param array  $overrides
 * @param string $meta
 *
 * @return bool Success/Failure
 */
function bigbluebuttonbn_log($bigbluebuttonbn, $event, array $overrides = [], $meta = null) {
    global $DB, $USER;
    $log = new stdClass();
    // Default values.
    $log->courseid = $bigbluebuttonbn->course;
    $log->bigbluebuttonbnid = $bigbluebuttonbn->id;
    $log->userid = $USER->id;
    $log->meetingid = $bigbluebuttonbn->meetingid;
    $log->timecreated = time();
    $log->log = $event;
    $log->meta = $meta;
    // Overrides.
    foreach ($overrides as $key => $value) {
        $log->$key = $value;
    }
    if (!$DB->insert_record('bigbluebuttonbn_logs', $log)) {
        return false;
    }
    return true;
}
