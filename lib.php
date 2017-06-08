<?php
/**
 * Library calls for Moodle and BigBlueButton.
 *
 * @package   mod_bigbluebuttonbn
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2010-2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die;

global $BIGBLUEBUTTONBN_CFG, $CFG;

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

require_once(dirname(__FILE__).'/JWT.php');

if( file_exists(dirname(__FILE__).'/config.php') ) {
    require_once(dirname(__FILE__).'/config.php');
    if( isset($BIGBLUEBUTTONBN_CFG) ) {
        $CFG = (object) array_merge((array)$CFG, (array)$BIGBLUEBUTTONBN_CFG);
    }
} else {
    $BIGBLUEBUTTONBN_CFG = new stdClass();
}

/*
 * DURATIONCOMPENSATION: Feature removed by configuration
 */
$BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_scheduled_duration_enabled = 0;
/*
 * Remove this block when restored
 */

const BIGBLUEBUTTONBN_DEFAULT_SERVER_URL = "http://test-install.blindsidenetworks.com/bigbluebutton/";
const BIGBLUEBUTTONBN_DEFAULT_SHARED_SECRET = "8cd8ef52e8e101574e400365b55e11a6";

const BIGBLUEBUTTONBN_LOG_EVENT_CREATE = "Create";
const BIGBLUEBUTTONBN_LOG_EVENT_JOIN = "Join";
const BIGBLUEBUTTONBN_LOG_EVENT_LOGOUT = "Logout";
const BIGBLUEBUTTONBN_LOG_EVENT_IMPORT = "Import";
const BIGBLUEBUTTONBN_LOG_EVENT_DELETE = "Delete";

function bigbluebuttonbn_supports($feature) {
    switch($feature) {
        case FEATURE_IDNUMBER:                return true;
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        // case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;

        default: return null;
    }
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $bigbluebuttonbn An object from the form in mod_form.php
 * @return int The id of the newly inserted bigbluebuttonbn record
 */
function bigbluebuttonbn_add_instance($data, $mform) {
    global $DB, $CFG;

    $draftitemid = isset($data->presentation)? $data->presentation: null;
    $context = bigbluebuttonbn_get_context_module($data->coursemodule);

    bigbluebuttonbn_process_pre_save($data);

    unset($data->presentation);
    $bigbluebuttonbn_id = $DB->insert_record('bigbluebuttonbn', $data);
    $data->id = $bigbluebuttonbn_id;

    bigbluebuttonbn_update_media_file($bigbluebuttonbn_id, $context, $draftitemid);

    bigbluebuttonbn_process_post_save($data);

    return $bigbluebuttonbn_id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $bigbluebuttonbn An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function bigbluebuttonbn_update_instance($data, $mform) {
    global $DB, $CFG;

    $data->id = $data->instance;
    $draftitemid = isset($data->presentation)? $data->presentation: null;
    $context = bigbluebuttonbn_get_context_module($data->coursemodule);

    bigbluebuttonbn_process_pre_save($data);

    unset($data->presentation);
    $DB->update_record("bigbluebuttonbn", $data);

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
 * @return boolean Success/Failure
 */
function bigbluebuttonbn_delete_instance($id) {
    global $CFG, $DB, $USER;

    if (! $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $id))) {
        return false;
    }

    $result = true;

    //
    // End the session associated with this instance (if it's running)
    //
    //$meetingID = $bigbluebuttonbn->meetingid.'-'.$bigbluebuttonbn->course.'-'.$bigbluebuttonbn->id;
    //
    //$modPW = $bigbluebuttonbn->moderatorpass;
    //$url = bigbluebuttonbn_get_cfg_server_url();
    //$shared_secret = bigbluebuttonbn_get_cfg_shared_secret();
    //
    //if( bigbluebuttonbn_isMeetingRunning($meetingID, $url, $shared_secret) )
    //    $getArray = bigbluebuttonbn_doEndMeeting( $meetingID, $modPW, $url, $shared_secret );

    if (! $DB->delete_records('bigbluebuttonbn', array('id' => $bigbluebuttonbn->id))) {
        $result = false;
    }

    if (! $DB->delete_records('event', array('modulename'=>'bigbluebuttonbn', 'instance'=>$bigbluebuttonbn->id))) {
        $result = false;
    }

    $log = new stdClass();

    $log->meetingid = $bigbluebuttonbn->meetingid;
    $log->courseid = $bigbluebuttonbn->course;
    $log->bigbluebuttonbnid = $bigbluebuttonbn->id;
    $log->userid = $USER->id;
    $log->timecreated = time();
    $log->log = BIGBLUEBUTTONBN_LOG_EVENT_DELETE;

    $logs = $DB->get_records('bigbluebuttonbn_logs', array('bigbluebuttonbnid' => $bigbluebuttonbn->id, 'log' => BIGBLUEBUTTONBN_LOG_EVENT_CREATE));
    $has_recordings = 'false';
    if (! empty($logs) ) {
        foreach ( $logs as $l ) {
            $meta = json_decode($l->meta);
            if ( $meta->record ) {
                $has_recordings = 'true';
            }
        }
    }
    $log->meta = "{\"has_recordings\":{$has_recordings}}";

    if (! $returnid = $DB->insert_record('bigbluebuttonbn_logs', $log)) {
        $result = false;
    }

    return $result;
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 */
function bigbluebuttonbn_user_outline($course, $user, $mod, $bigbluebuttonbn) {
    return true;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 */
function bigbluebuttonbn_user_complete($course, $user, $mod, $bigbluebuttonbn) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in bigbluebuttonbn activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function bigbluebuttonbn_print_recent_activity($course, $isteacher, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Returns all activity in bigbluebuttonbn since a given time
 *
 * @param array $activities sequentially indexed array of objects
 * @param int $index
 * @param int $timestart
 * @param int $courseid
 * @param int $cmid
 * @param int $userid defaults to 0
 * @param int $groupid defaults to 0
 * @return void adds items into $activities and increases $index
 */
function bigbluebuttonbn_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see recordingsbn_get_recent_mod_activity()}

 * @return void
 */
function bigbluebuttonbn_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 **/
function bigbluebuttonbn_cron () {
    return true;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of bigbluebuttonbn. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $bigbluebuttonbnid ID of an instance of this module
 * @return mixed boolean/array of students
 */
function bigbluebuttonbn_get_participants($bigbluebuttonbnid) {
    return false;
}

/**
 * Returns all other caps used in module
 * @return array
 */
function bigbluebuttonbn_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

////////////////////////////////////////////////////////////////////////////////
// Gradebook API                                                              //
////////////////////////////////////////////////////////////////////////////////

/**
 * This function returns if a scale is being used by one bigbluebuttonbn
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $bigbluebuttonbnid ID of an instance of this module
 * @return mixed
 */
function bigbluebuttonbn_scale_used($bigbluebuttonbnid, $scaleid) {
    $return = false;

    return $return;
}

/**
 * Checks if scale is being used by any instance of bigbluebuttonbn.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any bigbluebuttonbn
 */
function bigbluebuttonbn_scale_used_anywhere($scaleid) {
    $return = false;

    return $return;
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function bigbluebuttonbn_reset_userdata($data) {
    return array();
}

/**
 * List of view style log actions
 * @return array
 */
function bigbluebuttonbn_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List of update style log actions
 * @return array
 */
function bigbluebuttonbn_get_post_actions() {
    return array('update', 'add', 'create', 'join', 'end', 'left', 'publish', 'unpublish', 'delete');
}


/**
 * @global object
 * @global object
 * @param array $courses
 * @param array $htmlarray Passed by reference
 */
function bigbluebuttonbn_print_overview($courses, &$htmlarray) {
    global $USER, $CFG;

    if (empty($courses) || !is_array($courses) || count($courses) == 0) {
        return array();
    }

    if (!$bigbluebuttonbns = get_all_instances_in_courses('bigbluebuttonbn', $courses)) {
        return;
    }

    foreach ($bigbluebuttonbns as $bigbluebuttonbn) {
        $now = time();
        if ( $bigbluebuttonbn->openingtime and (!$bigbluebuttonbn->closingtime or $bigbluebuttonbn->closingtime > $now)) { // A bigbluebuttonbn is scheduled.
            $str = '<div class="bigbluebuttonbn overview"><div class="name">'.
                 get_string('modulename', 'bigbluebuttonbn').': <a '.($bigbluebuttonbn->visible ? '' : ' class="dimmed"').
                 ' href="'.$CFG->wwwroot.'/mod/bigbluebuttonbn/view.php?id='.$bigbluebuttonbn->coursemodule.'">'.
                 $bigbluebuttonbn->name.'</a></div>';
            if ( $bigbluebuttonbn->openingtime > $now ) {
                $str .= '<div class="info">'.get_string('starts_at', 'bigbluebuttonbn').': '.userdate($bigbluebuttonbn->openingtime).'</div>';
            } else {
                $str .= '<div class="info">'.get_string('started_at', 'bigbluebuttonbn').': '.userdate($bigbluebuttonbn->openingtime).'</div>';
            }
            $str .= '<div class="info">'.get_string('ends_at', 'bigbluebuttonbn').': '.userdate($bigbluebuttonbn->closingtime).'</div></div>';

            if (empty($htmlarray[$bigbluebuttonbn->course]['bigbluebuttonbn'])) {
                $htmlarray[$bigbluebuttonbn->course]['bigbluebuttonbn'] = $str;
            } else {
                $htmlarray[$bigbluebuttonbn->course]['bigbluebuttonbn'] .= $str;
            }
        }
    }
}


/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 *
 * @global object
 * @param object $coursemodule
 * @return object|null
 */
function bigbluebuttonbn_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;

    if ( !$bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id'=>$coursemodule->instance), 'id, name, intro, introformat')) {
        return NULL;
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
 * a bigbluebuttonbn insert/update
 *
 * @global object
 * @param object $bigbluebuttonbn BigBlueButtonBN form data
 * @return void
 **/
function bigbluebuttonbn_process_pre_save(&$bigbluebuttonbn) {
    global $DB, $CFG;

    if ( !isset($bigbluebuttonbn->timecreated) || !$bigbluebuttonbn->timecreated ) {
        $bigbluebuttonbn->timecreated = time();
        //Assign password only if it is a new activity
        if( isset($bigbluebuttonbn->add) && !empty($bigbluebuttonbn->add) ) {
            $bigbluebuttonbn->moderatorpass = bigbluebuttonbn_random_password(12);
            $bigbluebuttonbn->viewerpass = bigbluebuttonbn_random_password(12);
        }

    } else {
        $bigbluebuttonbn->timemodified = time();
    }

    if (! isset($bigbluebuttonbn->wait))
        $bigbluebuttonbn->wait = 0;
    if (! isset($bigbluebuttonbn->record))
        $bigbluebuttonbn->record = 0;
    if (! isset($bigbluebuttonbn->tagging))
        $bigbluebuttonbn->tagging = 0;

    $bigbluebuttonbn->participants = htmlspecialchars_decode($bigbluebuttonbn->participants);
}

/**
 * Runs any processes that must be run
 * after a bigbluebuttonbn insert/update
 *
 * @global object
 * @param object $bigbluebuttonbn BigBlueButtonBN form data
 * @return void
 **/
function bigbluebuttonbn_process_post_save(&$bigbluebuttonbn) {
    global $DB, $CFG, $USER;

    // Now that an id was assigned, generate and set the meetingid property based on
    // [Moodle Instance + Activity ID + BBB Secret] (but only for new activities)
    if( isset($bigbluebuttonbn->add) && !empty($bigbluebuttonbn->add) ) {
        $bigbluebuttonbn_meetingid = sha1($CFG->wwwroot.$bigbluebuttonbn->id.bigbluebuttonbn_get_cfg_shared_secret());
        $DB->set_field('bigbluebuttonbn', 'meetingid', $bigbluebuttonbn_meetingid, array('id' => $bigbluebuttonbn->id));
        $action = get_string('mod_form_field_notification_msg_created', 'bigbluebuttonbn');
    } else {
        $action = get_string('mod_form_field_notification_msg_modified', 'bigbluebuttonbn');
    }
    $at = get_string('mod_form_field_notification_msg_at', 'bigbluebuttonbn');

    // Add evento to the calendar when if openingtime is set
    if ( isset($bigbluebuttonbn->openingtime) && $bigbluebuttonbn->openingtime ){
        $event = new stdClass();
        $event->name        = $bigbluebuttonbn->name;
        $event->courseid    = $bigbluebuttonbn->course;
        $event->groupid     = 0;
        $event->userid      = 0;
        $event->modulename  = 'bigbluebuttonbn';
        $event->instance    = $bigbluebuttonbn->id;
        $event->timestart   = $bigbluebuttonbn->openingtime;

        if ( $bigbluebuttonbn->closingtime ){
            $event->durationtime = $bigbluebuttonbn->closingtime - $bigbluebuttonbn->openingtime;
        } else {
            $event->durationtime = 0;
        }

        if ( $event->id = $DB->get_field('event', 'id', array('modulename'=>'bigbluebuttonbn', 'instance'=>$bigbluebuttonbn->id)) ) {
            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event);
        } else {
            calendar_event::create($event);
        }

    } else {
        $DB->delete_records('event', array('modulename'=>'bigbluebuttonbn', 'instance'=>$bigbluebuttonbn->id));
    }

    if( isset($bigbluebuttonbn->notification) && $bigbluebuttonbn->notification ) {
        // Prepare message
        $msg = new stdClass();

        /// Build the message_body
        $msg->action = $action;
        $msg->activity_type = "";
        $msg->activity_title = $bigbluebuttonbn->name;
        $message_text = '<p>'.$msg->activity_type.' &quot;'.$msg->activity_title.'&quot; '.get_string('email_body_notification_meeting_has_been', 'bigbluebuttonbn').' '.$msg->action.'.</p>';

        /// Add the meeting details to the message_body
        $msg->action = ucfirst($action);
        $msg->activity_description = "";
        if( !empty($bigbluebuttonbn->intro) )
            $msg->activity_description = trim($bigbluebuttonbn->intro);
        $msg->activity_openingtime = "";
        if ($bigbluebuttonbn->openingtime) {
            $msg->activity_openingtime = calendar_day_representation($bigbluebuttonbn->openingtime).' '.$at.' '.calendar_time_representation($bigbluebuttonbn->openingtime);
        }
        $msg->activity_closingtime = "";
        if ($bigbluebuttonbn->closingtime ) {
            $msg->activity_closingtime = calendar_day_representation($bigbluebuttonbn->closingtime).' '.$at.' '.calendar_time_representation($bigbluebuttonbn->closingtime);
        }
        $msg->activity_owner = fullname($USER);

        $message_text .= '<p><b>'.$msg->activity_title.'</b> '.get_string('email_body_notification_meeting_details', 'bigbluebuttonbn').':';
        $message_text .= '<table border="0" style="margin: 5px 0 0 20px"><tbody>';
        $message_text .= '<tr><td style="font-weight:bold;color:#555;">'.get_string('email_body_notification_meeting_title', 'bigbluebuttonbn').': </td><td>';
        $message_text .= $msg->activity_title.'</td></tr>';
        $message_text .= '<tr><td style="font-weight:bold;color:#555;">'.get_string('email_body_notification_meeting_description', 'bigbluebuttonbn').': </td><td>';
        $message_text .= $msg->activity_description.'</td></tr>';
        $message_text .= '<tr><td style="font-weight:bold;color:#555;">'.get_string('email_body_notification_meeting_start_date', 'bigbluebuttonbn').': </td><td>';
        $message_text .= $msg->activity_openingtime.'</td></tr>';
        $message_text .= '<tr><td style="font-weight:bold;color:#555;">'.get_string('email_body_notification_meeting_end_date', 'bigbluebuttonbn').': </td><td>';
        $message_text .= $msg->activity_closingtime.'</td></tr>';
        $message_text .= '<tr><td style="font-weight:bold;color:#555;">'.$msg->action.' '.get_string('email_body_notification_meeting_by', 'bigbluebuttonbn').': </td><td>';
        $message_text .= $msg->activity_owner.'</td></tr></tbody></table></p>';

        // Send notification to all users enrolled
        bigbluebuttonbn_send_notification($USER, $bigbluebuttonbn, $message_text);
    }
}

/**
 * Update the bigbluebuttonbn activity to include any file
 * that was uploaded, or if there is none, set the
 * presentation field to blank.
 *
 * @param int $bigbluebuttonbn_id the bigbluebuttonbn id
 * @param stdClass $context the context
 * @param int $draftitemid the draft item
 */
function bigbluebuttonbn_update_media_file($bigbluebuttonbn_id, $context, $draftitemid) {
    global $DB;

    // Set the filestorage object.
    $fs = get_file_storage();
    // Save the file if it exists that is currently in the draft area.
    file_save_draft_area_files($draftitemid, $context->id, 'mod_bigbluebuttonbn', 'presentation', 0);
    // Get the file if it exists.
    $files = $fs->get_area_files($context->id, 'mod_bigbluebuttonbn', 'presentation', 0, 'itemid, filepath, filename', false);
    // Check that there is a file to process.
    if (count($files) == 1) {
        // Get the first (and only) file.
        $file = reset($files);
        // Set the presentation column in the bigbluebuttonbn table.
        $DB->set_field('bigbluebuttonbn', 'presentation', '/' . $file->get_filename(), array('id' => $bigbluebuttonbn_id));
    } else {
        // Set the presentation column in the bigbluebuttonbn table.
        $DB->set_field('bigbluebuttonbn', 'presentation', '', array('id' => $bigbluebuttonbn_id));
    }
}

/**
 * Serves the bigbluebuttonbn attachments. Implements needed access control ;-)
 *
 * @package mod_bigbluebuttonbn
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */
function bigbluebuttonbn_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    $fileareas = bigbluebuttonbn_get_file_areas();
    if (!array_key_exists($filearea, $fileareas)) {
        return false;
    }

    if (!$bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id'=>$cm->instance))) {
        return false;
    }

    if( sizeof($args) > 1 ) {
        $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_bigbluebuttonbn', 'presentation_cache');
        $presentation_nonce_key = sha1($bigbluebuttonbn->id);
        $presentation_nonce = $cache->get($presentation_nonce_key);
        $presentation_nonce_value = $presentation_nonce['value'];
        $presentation_nonce_counter = $presentation_nonce['counter'];

        if( $args["0"] != $presentation_nonce_value ) {
            return false;
        }

        //The nonce value is actually used twice because BigBlueButton reads the file two times
        $presentation_nonce_counter += 1;
        if( $presentation_nonce_counter < 2 ) {
            $cache->set($presentation_nonce_key, array( "value" => $presentation_nonce_value, "counter" => $presentation_nonce_counter ));
        } else {
            $cache->delete($presentation_nonce_key);
        }

        $filename = $args["1"];

    } else {
        require_course_login($course, true, $cm);

        if (!has_capability('mod/bigbluebuttonbn:join', $context)) {
            return false;
        }

        $filename = implode('/', $args);
    }

    if ($filearea === 'presentation') {
        $fullpath = "/$context->id/mod_bigbluebuttonbn/$filearea/0/".$filename;
    } else {
        return false;
    }

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // finally send the file
    send_stored_file($file, 0, 0, $forcedownload, $options); // download MUST be forced - security!
}

/**
 * Returns an array of file areas
 *
 * @package  mod_bigbluebuttonbn
 * @category files
 * @return array a list of available file areas
 */
function bigbluebuttonbn_get_file_areas() {
    $areas = array();
    $areas['presentation'] = get_string('mod_form_block_presentation', 'bigbluebuttonbn');

    return $areas;
}

/**
 * Returns an array with all the roles contained in the database
 *
 * @package  mod_bigbluebuttonbn
 * @return array a list of available roles
 */
function bigbluebuttonbn_get_moodle_roles($rolename='all') {
    if( $rolename != 'all') {
        $roles = array(bigbluebuttonbn_get_role($rolename));
    } else {
        $roles = (array) role_get_names();
    }
    return $roles;
}

function bigbluebuttonbn_send_notification($sender, $bigbluebuttonbn, $message="") {
    global $CFG, $DB;

    $context = bigbluebuttonbn_get_context_course($bigbluebuttonbn->course);
    $course = $DB->get_record('course', array('id' => $bigbluebuttonbn->course), '*', MUST_EXIST);

    //Complete message
    $msg = new stdClass();
    $msg->user_name = fullname($sender);
    $msg->user_email = $sender->email;
    $msg->course_name = "$course->fullname";
    $message .= '<p><hr/><br/>'.get_string('email_footer_sent_by', 'bigbluebuttonbn').' '.$msg->user_name.'('.$msg->user_email.') ';
    $message .= get_string('email_footer_sent_from', 'bigbluebuttonbn').' '.$msg->course_name.'.</p>';

    $users = get_enrolled_users($context,'',0,'u.*',null,0,0,true);
    foreach( $users as $user ) {
        if( $user->id != $sender->id ){
            $messageid = message_post_message($sender, $user, $message, FORMAT_HTML);
        }
    }
}

function bigbluebuttonbn_get_context_module($id) {
    global $CFG;

    $version_major = bigbluebuttonbn_get_moodle_version_major();
    if ( $version_major < '2013111800' ) {
        //This is valid before v2.6
        $context = get_context_instance(CONTEXT_MODULE, $id);
    } else {
        //This is valid after v2.6
        $context = context_module::instance($id);
    }

    return $context;
}

function bigbluebuttonbn_get_context_course($id) {
    global $CFG;

    $version_major = bigbluebuttonbn_get_moodle_version_major();
    if ( $version_major < '2013111800' ) {
        //This is valid before v2.6
        $context = get_context_instance(CONTEXT_COURSE, $id);
    } else {
        //This is valid after v2.6
        $context = context_course::instance($id);
    }

    return $context;
}

function bigbluebuttonbn_get_cfg_server_url() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_server_url)? trim(trim($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_server_url),'/').'/': (isset($CFG->bigbluebuttonbn_server_url)? trim(trim($CFG->bigbluebuttonbn_server_url),'/').'/': 'http://test-install.blindsidenetworks.com/bigbluebutton/'));
}

function bigbluebuttonbn_get_cfg_shared_secret() {
    global $BIGBLUEBUTTONBN_CFG, $CFG;
    return (isset($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_shared_secret)? trim($BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_shared_secret): (isset($CFG->bigbluebuttonbn_shared_secret)? trim($CFG->bigbluebuttonbn_shared_secret): '8cd8ef52e8e101574e400365b55e11a6'));
}
