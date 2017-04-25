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
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2010-2017 Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->dirroot.'/calendar/lib.php');
require_once($CFG->dirroot.'/message/lib.php');
require_once($CFG->dirroot.'/mod/lti/OAuth.php');
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

const BIGBLUEBUTTONBN_DEFAULT_SERVER_URL = 'http://test-install.blindsidenetworks.com/bigbluebutton/';
const BIGBLUEBUTTONBN_DEFAULT_SHARED_SECRET = '8cd8ef52e8e101574e400365b55e11a6';

const BIGBLUEBUTTONBN_LOG_EVENT_CREATE = 'Create';
const BIGBLUEBUTTONBN_LOG_EVENT_JOIN = 'Join';
const BIGBLUEBUTTONBN_LOG_EVENT_LOGOUT = 'Logout';
const BIGBLUEBUTTONBN_LOG_EVENT_IMPORT = 'Import';
const BIGBLUEBUTTONBN_LOG_EVENT_DELETE = 'Delete';

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
 * @param object $mform An object from the form in mod_form.php
 *
 * @return int The id of the newly inserted bigbluebuttonbn record
 */
function bigbluebuttonbn_add_instance($data, $mform) {
    global $DB;

    $draftitemid = isset($data->presentation) ? $data->presentation : null;
    $context = context_module::instance($data->coursemodule);

    bigbluebuttonbn_process_pre_save($data);

    unset($data->presentation);
    $bigbluebuttonbnid = $DB->insert_record('bigbluebuttonbn', $data);
    $data->id = $bigbluebuttonbnid;

    bigbluebuttonbn_update_media_file($bigbluebuttonbnid, $context, $draftitemid);

    bigbluebuttonbn_process_post_save($data);

    return $bigbluebuttonbnid;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @return bool Success/Fail
 */
function bigbluebuttonbn_update_instance($data, $mform) {
    global $DB;

    $data->id = $data->instance;
    $draftitemid = isset($data->presentation) ? $data->presentation : null;
    $context = context_module::instance($data->coursemodule);

    bigbluebuttonbn_process_pre_save($data);

    unset($data->presentation);
    $DB->update_record('bigbluebuttonbn', $data);

    bigbluebuttonbn_update_media_file($data->id, $context, $draftitemid);

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

    if (!$bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $id))) {
        return false;
    }

    // End the session associated with this instance (if it's running).
    $meetingid = $bigbluebuttonbn->meetingid.'-'.$bigbluebuttonbn->course.'-'.$bigbluebuttonbn->id;
    $modpw = $bigbluebuttonbn->moderatorpass;

    if (bigbluebuttonbn_is_meeting_running($meetingid)) {
        bigbluebuttonbn_end_meeting($meetingid, $modpw);
    }

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

function bigbluebuttonbn_delete_instance_log($bigbluebuttonbn) {
    global $DB, $USER;

    $log = new stdClass();
    $log->meetingid = $bigbluebuttonbn->meetingid;
    $log->courseid = $bigbluebuttonbn->course;
    $log->bigbluebuttonbnid = $bigbluebuttonbn->id;
    $log->userid = $USER->id;
    $log->timecreated = time();
    $log->log = BIGBLUEBUTTONBN_LOG_EVENT_DELETE;

    $logs = $DB->get_records('bigbluebuttonbn_logs',
        array('bigbluebuttonbnid' => $bigbluebuttonbn->id, 'log' => BIGBLUEBUTTONBN_LOG_EVENT_CREATE, 'meta' => "{\"record\":true}")
      );
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
 * @return bool
 */
function bigbluebuttonbn_user_outline($course, $user, $mod, $bigbluebuttonbn) {
    global $DB;

    $completed = $DB->count_records('bigbluebuttonbn_logs', array('courseid' => $course->id,
                                                              'bigbluebuttonbnid' => $bigbluebuttonbn->id,
                                                              'userid' => $user->id,
                                                              'log' => 'Join', ), '*');

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
 * @return bool
 */
function bigbluebuttonbn_user_complete($course, $user, $mod, $bigbluebuttonbn) {
    global $DB;

    $completed = $DB->count_recorda('bigbluebuttonbn_logs', array('courseid' => $course->id,
                                                              'bigbluebuttonbnid' => $bigbluebuttonbn->id,
                                                              'userid' => $user->id,
                                                              'log' => 'Join', ), '*', IGNORE_MULTIPLE);

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
 * @global object
 * @global object
 *
 * @param array $courses
 * @param array $htmlarray Passed by reference
 */
function bigbluebuttonbn_print_overview($courses, &$htmlarray) {

    if (empty($courses) || !is_array($courses) || count($courses) == 0) {
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
    $str = '<div class="bigbluebuttonbn overview">'."\n";
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
 * @global object
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
 * Runs any processes that must run before
 * a bigbluebuttonbn insert/update.
 *
 * @global object
 *
 * @param object $bigbluebuttonbn BigBlueButtonBN form data
 **/
function bigbluebuttonbn_process_pre_save(&$bigbluebuttonbn) {
    $bigbluebuttonbn->timemodified = time();

    if (!isset($bigbluebuttonbn->timecreated) || !$bigbluebuttonbn->timecreated) {
        $bigbluebuttonbn->timecreated = time();
        // Assign password only if it is a new activity.
        $bigbluebuttonbn->moderatorpass = bigbluebuttonbn_random_password(12);
        $bigbluebuttonbn->viewerpass = bigbluebuttonbn_random_password(12);
        $bigbluebuttonbn->timemodified = 0;
    }

    if (!isset($bigbluebuttonbn->wait)) {
        $bigbluebuttonbn->wait = 0;
    }
    if (!isset($bigbluebuttonbn->record)) {
        $bigbluebuttonbn->record = 0;
    }
    if (!isset($bigbluebuttonbn->recordings_deleted_activities)) {
        $bigbluebuttonbn->recordings_deleted_activities = 0;
    }
    if (!isset($bigbluebuttonbn->recordings_html)) {
        $bigbluebuttonbn->recordings_html = 0;
    }

    $bigbluebuttonbn->participants = htmlspecialchars_decode($bigbluebuttonbn->participants);
}

/**
 * Runs any processes that must be run
 * after a bigbluebuttonbn insert/update.
 *
 * @global object
 *
 * @param object $bigbluebuttonbn BigBlueButtonBN form data
 **/
function bigbluebuttonbn_process_post_save(&$bigbluebuttonbn) {
    global $DB, $CFG;

    $action = get_string('mod_form_field_notification_msg_modified', 'bigbluebuttonbn');

    /* Now that an id was assigned, generate and set the meetingid property based on
     * [Moodle Instance + Activity ID + BBB Secret] (but only for new activities) */
    if (isset($bigbluebuttonbn->add) && !empty($bigbluebuttonbn->add)) {
        $meetingid = sha1($CFG->wwwroot.$bigbluebuttonbn->id.bigbluebuttonbn_get_cfg_shared_secret());
        $DB->set_field('bigbluebuttonbn', 'meetingid', $meetingid, array('id' => $bigbluebuttonbn->id));

        $action = get_string('mod_form_field_notification_msg_created', 'bigbluebuttonbn');
    }

    bigbluebuttonbn_process_post_save_event($bigbluebuttonbn);

    if (isset($bigbluebuttonbn->notification) && $bigbluebuttonbn->notification) {
        bigbluebuttonbn_notification_process($bigbluebuttonbn, $action);
    }
}

function bigbluebuttonbn_process_post_save_event($bigbluebuttonbn) {
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
 * Update the bigbluebuttonbn activity to include any file
 * that was uploaded, or if there is none, set the
 * presentation field to blank.
 *
 * @param int      $bigbluebuttonbnid the bigbluebuttonbn id
 * @param stdClass $context            the context
 * @param int      $draftitemid        the draft item
 */
function bigbluebuttonbn_update_media_file($bigbluebuttonbnid, $context, $draftitemid) {
    global $DB;

    // Set the filestorage object.
    $fs = get_file_storage();
    // Save the file if it exists that is currently in the draft area.
    file_save_draft_area_files($draftitemid, $context->id, 'mod_bigbluebuttonbn', 'presentation', 0);
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
    // Set the presentation column in the bigbluebuttonbn table.
    $DB->set_field('bigbluebuttonbn', 'presentation', $filesrc, array('id' => $bigbluebuttonbnid));
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
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    if ($filearea !== 'presentation') {
        return false;
    }

    if (!array_key_exists($filearea, bigbluebuttonbn_get_file_areas())) {
        return false;
    }

    $filename = bigbluebuttonbn_pluginfile_filename($course, $cm, $context, $args);
    if (!$filename) {
        return false;
    }

    $fullpath = "/$context->id/mod_bigbluebuttonbn/$filearea/0/".$filename;
    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // Finally send the file.
    send_stored_file($file, 0, 0, $forcedownload, $options); // download MUST be forced - security!
}

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
 * Returns an array with all the roles contained in the database.
 *
 * @return array a list of available roles
 */
function bigbluebuttonbn_get_db_moodle_roles($rolename = 'all') {
    global $DB;

    $roletarget = array();
    if ($rolename != 'all') {
        $roletarget['shortname'] = $rolename;
    }
    return $DB->get_records('role', $roletarget);
}

function bigbluebuttonbn_notification_process($bigbluebuttonbn, $action) {
    global $USER;

    // Prepare message.
    $msg = new stdClass();

    // Build the message_body.
    $msg->action = $action;
    $msg->activity_type = '';
    $msg->activity_title = $bigbluebuttonbn->name;

    // Add the meeting details to the message_body.
    $msg->action = ucfirst($action);
    $msg->activity_description = '';
    if (!empty($bigbluebuttonbn->intro)) {
        $msg->activity_description = trim($bigbluebuttonbn->intro);
    }
    $msg->activity_openingtime = bigbluebuttonbn_format_activity_time($bigbluebuttonbn->openingtime);
    $msg->activity_closingtime = bigbluebuttonbn_format_activity_time($bigbluebuttonbn->closingtime);
    $msg->activity_owner = fullname($USER);

    // Send notification to all users enrolled.
    bigbluebuttonbn_notification_send($USER, $bigbluebuttonbn, bigbluebuttonbn_notification_msg_html($msg));
}

function bigbluebuttonbn_notification_msg_html($msg) {
    $messagetext = '<p>'.$msg->activity_type.' &quot;'.$msg->activity_title.'&quot; '.
        get_string('email_body_notification_meeting_has_been', 'bigbluebuttonbn').' '.$msg->action.'.</p>'."\n";
    $messagetext .= '<p><b>'.$msg->activity_title.'</b> '.
        get_string('email_body_notification_meeting_details', 'bigbluebuttonbn').':'."\n";
    $messagetext .= '<table border="0" style="margin: 5px 0 0 20px"><tbody>'."\n";
    $messagetext .= '<tr><td style="font-weight:bold;color:#555;">'.
        get_string('email_body_notification_meeting_title', 'bigbluebuttonbn').': </td><td>'."\n";
    $messagetext .= $msg->activity_title.'</td></tr>'."\n";
    $messagetext .= '<tr><td style="font-weight:bold;color:#555;">'.
        get_string('email_body_notification_meeting_description', 'bigbluebuttonbn').': </td><td>'."\n";
    $messagetext .= $msg->activity_description.'</td></tr>'."\n";
    $messagetext .= '<tr><td style="font-weight:bold;color:#555;">'.
        get_string('email_body_notification_meeting_start_date', 'bigbluebuttonbn').': </td><td>'."\n";
    $messagetext .= $msg->activity_openingtime.'</td></tr>'."\n";
    $messagetext .= '<tr><td style="font-weight:bold;color:#555;">'.
        get_string('email_body_notification_meeting_end_date', 'bigbluebuttonbn').': </td><td>'."\n";
    $messagetext .= $msg->activity_closingtime.'</td></tr>'."\n";
    $messagetext .= '<tr><td style="font-weight:bold;color:#555;">'.$msg->action.' '.
        get_string('email_body_notification_meeting_by', 'bigbluebuttonbn').': </td><td>'."\n";
    $messagetext .= $msg->activity_owner.'</td></tr></tbody></table></p>'."\n";
}

function bigbluebuttonbn_notification_send($sender, $bigbluebuttonbn, $message = '') {
    global $DB;

    $context = context_course::instance($bigbluebuttonbn->course);
    $course = $DB->get_record('course', array('id' => $bigbluebuttonbn->course), '*', MUST_EXIST);

    // Complete message.
    $msg = new stdClass();
    $msg->user_name = fullname($sender);
    $msg->user_email = $sender->email;
    $msg->course_name = "$course->fullname";
    $message .= '<p><hr/><br/>'.get_string('email_footer_sent_by', 'bigbluebuttonbn').' '.
        $msg->user_name.'('.$msg->user_email.') ';
    $message .= get_string('email_footer_sent_from', 'bigbluebuttonbn').' '.$msg->course_name.'.</p>';

    $users = get_enrolled_users($context);
    foreach ($users as $user) {
        if ($user->id != $sender->id) {
            $messageid = message_post_message($sender, $user, $message, FORMAT_HTML);
            $msgsend = ' was sent.';
            if (empty($messageid)) {
                $msgsend = ' was NOT sent.';
            }
            debugging('Msg to '.$msg->user_name.$msgsend, DEBUG_DEVELOPER);
        }
    }
}
