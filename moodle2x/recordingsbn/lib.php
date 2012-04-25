<?php
/**
 * View and administrate BigBlueButton playback recordings
 *
 * Authors:
 *    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 *
 * @package   mod_bigbluebutton
 * @copyright 2011-2012 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die();

////////////////////////////////////////////////////////////////////////////////
// Moodle core API                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function recordingsbn_supports($feature) {
    switch($feature) {
        case FEATURE_IDNUMBER:                 return false;
        case FEATURE_GROUPS:                   return false;
        case FEATURE_GROUPINGS:                return false;
        case FEATURE_GROUPMEMBERSONLY:         return false;
        case FEATURE_MOD_INTRO:                return false;
        case FEATURE_COMPLETION_TRACKS_VIEWS:  return false;
        case FEATURE_GRADE_HAS_GRADE:          return false;
        case FEATURE_GRADE_OUTCOMES:           return false;
        case FEATURE_MOD_ARCHETYPE:            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_BACKUP_MOODLE2:           return true;
        
        default:                               return null;
    }
}

/**
 * Saves a new instance of the recordingsbn into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $recordingsbn An object from the form in mod_form.php
 * @param mod_recordingsbn_mod_form $mform
 * @return int The id of the newly inserted recordingsbn record
 */
function recordingsbn_add_instance(stdClass $recordingsbn, mod_recordingsbn_mod_form $mform = null) {
    global $DB;

    $recordingsbn->timecreated = time();

    # You may have to add extra stuff in here #

    return $DB->insert_record('recordingsbn', $recordingsbn);
}

/**
 * Updates an instance of the recordingsbn in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $recordingsbn An object from the form in mod_form.php
 * @param mod_recordingsbn_mod_form $mform
 * @return boolean Success/Fail
 */
function recordingsbn_update_instance(stdClass $recordingsbn, mod_recordingsbn_mod_form $mform = null) {
    global $DB;

    $recordingsbn->timemodified = time();
    $recordingsbn->id = $recordingsbn->instance;

    # You may have to add extra stuff in here #

    return $DB->update_record('recordingsbn', $recordingsbn);
}

/**
 * Removes an instance of the recordingsbn from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function recordingsbn_delete_instance($id) {
    global $DB;

    if (! $recordingsbn = $DB->get_record('recordingsbn', array('id' => $id))) {
        return false;
    }

    # Delete any dependent records here #

    $DB->delete_records('recordingsbn', array('id' => $recordingsbn->id));

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return stdClass|null
 */
function recordingsbn_user_outline($course, $user, $mod, $recordingsbn) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return string HTML
 */
function recordingsbn_user_complete($course, $user, $mod, $recordingsbn) {
    return '';
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in recordingsbn activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function recordingsbn_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Returns all activity in recordingsbns since a given time
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
function recordingsbn_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see recordingsbn_get_recent_mod_activity()}

 * @return void
 */
function recordingsbn_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function recordingsbn_cron () {
    return true;
}

/**
 * Returns an array of users who are participanting in this recordingsbn
 *
 * Must return an array of users who are participants for a given instance
 * of recordingsbn. Must include every user involved in the instance,
 * independient of his role (student, teacher, admin...). The returned
 * objects must contain at least id property.
 * See other modules as example.
 *
 * @param int $recordingsbnid ID of an instance of this module
 * @return boolean|array false if no participants, array of objects otherwise
 */
function recordingsbn_get_participants($recordingsbnid) {
    return false;
}

/**
 * Returns all other caps used in the module
 *
 * @example return array('moodle/site:accessallgroups');
 * @return array
 */
function recordingsbn_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

////////////////////////////////////////////////////////////////////////////////
// Gradebook API                                                              //
////////////////////////////////////////////////////////////////////////////////

/**
 * Is a given scale used by the instance of recordingsbn?
 *
 * This function returns if a scale is being used by one recordingsbn
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $recordingsbnid ID of an instance of this module
 * @return bool true if the scale is used by the given recordingsbn instance
 */
function recordingsbn_scale_used($recordingsbnid, $scaleid) {

    $return = false;

    return $return;
    
}

/**
 * Checks if scale is being used by any instance of recordingsbn.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param $scaleid int
 * @return boolean true if the scale is used by any recordingsbn instance
 */
function recordingsbn_scale_used_anywhere($scaleid) {
    $return = false;

    return $return;
}

////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function recordingsbn_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * Serves the files from the recordingsbn file areas
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return void this should never return to the caller
 */
function recordingsbn_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

////////////////////////////////////////////////////////////////////////////////
// Navigation API                                                             //
////////////////////////////////////////////////////////////////////////////////

/**
 * Extends the global navigation tree by adding recordingsbn nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the recordingsbn module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function recordingsbn_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {
}

/**
 * Extends the settings navigation with the recordingsbn settings
 *
 * This function is called when the context for the page is a recordingsbn module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $recordingsbnnode {@link navigation_node}
 */
function recordingsbn_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $recordingsbnnode=null) {
}

