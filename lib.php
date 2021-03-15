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

use mod_bigbluebuttonbn\local\bbb_constants;
use mod_bigbluebuttonbn\local\bigbluebutton;
use mod_bigbluebuttonbn\local\helpers\files;
use mod_bigbluebuttonbn\local\helpers\instance;
use mod_bigbluebuttonbn\local\helpers\logs;
use mod_bigbluebuttonbn\local\helpers\meeting;
use mod_bigbluebuttonbn\local\helpers\reset;
use mod_bigbluebuttonbn\plugin;

global $CFG;

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
        (string) FEATURE_MOD_INTRO => true,
        (string) FEATURE_BACKUP_MOODLE2 => true,
        (string) FEATURE_COMPLETION_TRACKS_VIEWS => true,
        (string) FEATURE_COMPLETION_HAS_RULES => true,
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
 * Obtains the automatic completion state for this bigbluebuttonbn based on any conditions
 * in bigbluebuttonbn settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 *
 * @return bool True if completed, false if not. (If no conditions, then return
 *   value depends on comparison type)
 */
function bigbluebuttonbn_get_completion_state($course, $cm, $userid, $type) {
    global $DB;

    // Get bigbluebuttonbn details.
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $cm->instance), '*',
            MUST_EXIST);
    if (!$bigbluebuttonbn) {
        throw new Exception("Can't find bigbluebuttonbn {$cm->instance}");
    }

    // Default return value.
    $result = $type;

    $sql  = "SELECT * FROM {bigbluebuttonbn_logs} ";
    $sql .= "WHERE bigbluebuttonbnid = ? AND userid = ? AND log = ?";
    $logs = $DB->get_records_sql($sql, array($bigbluebuttonbn->id, $userid, bbb_constants::BIGBLUEBUTTON_LOG_EVENT_SUMMARY));

    if ($bigbluebuttonbn->completionattendance) {
        if (!$logs) {
            // As completion by attendance was required, the activity hasn't been completed.
            return false;
        }
        $attendancecount = 0;
        foreach ($logs as $log) {
            $summary = json_decode($log->meta);
            $attendancecount += $summary->data->duration;
        }
        $attendancecount /= 60;
        $value = $bigbluebuttonbn->completionattendance <= $attendancecount;
        if ($type == COMPLETION_AND) {
            $result = $result && $value;
        } else {
            $result = $result || $value;
        }
    }

    if ($bigbluebuttonbn->completionengagementchats) {
        if (!$logs) {
            // As completion by engagement with chat was required, the activity hasn't been completed.
            return false;
        }
        $engagementchatscount = 0;
        foreach ($logs as $log) {
            $summary = json_decode($log->meta);
            $engagementchatscount += $summary->data->engagement->chats;
        }
        $value = $bigbluebuttonbn->completionengagementchats <= $engagementchatscount;
        if ($type == COMPLETION_AND) {
            $result = $result && $value;
        } else {
            $result = $result || $value;
        }
    }

    if ($bigbluebuttonbn->completionengagementtalks) {
        if (!$logs) {
            // As completion by engagement with talk was required, the activity hasn't been completed.
            return false;
        }
        $engagementtalkscount = 0;
        foreach ($logs as $log) {
            $summary = json_decode($log->meta);
            $engagementtalkscount += $summary->data->engagement->talks;
        }
        $value = $bigbluebuttonbn->completionengagementtalks <= $engagementtalkscount;
        if ($type == COMPLETION_AND) {
            $result = $result && $value;
        } else {
            $result = $result || $value;
        }
    }

    return $result;
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
    instance::bigbluebuttonbn_process_pre_save($bigbluebuttonbn);
    // Pre-set initial values.
    $bigbluebuttonbn->presentation = files::bigbluebuttonbn_get_media_file($bigbluebuttonbn);
    // Insert a record.
    $bigbluebuttonbn->id = $DB->insert_record('bigbluebuttonbn', $bigbluebuttonbn);
    // Encode meetingid.
    $bigbluebuttonbn->meetingid = plugin::bigbluebuttonbn_unique_meetingid_seed();
    // Set the meetingid column in the bigbluebuttonbn table.
    $DB->set_field('bigbluebuttonbn', 'meetingid', $bigbluebuttonbn->meetingid, array('id' => $bigbluebuttonbn->id));
    // Log insert action.
    logs::bigbluebuttonbn_log($bigbluebuttonbn, bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_ADD);
    // Complete the process.
    instance::bigbluebuttonbn_process_post_save($bigbluebuttonbn);
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
    instance::bigbluebuttonbn_process_pre_save($bigbluebuttonbn);
    // Pre-set initial values.
    $bigbluebuttonbn->id = $bigbluebuttonbn->instance;
    $bigbluebuttonbn->presentation = files::bigbluebuttonbn_get_media_file($bigbluebuttonbn);
    // Update a record.
    $DB->update_record('bigbluebuttonbn', $bigbluebuttonbn);
    // Get the meetingid column in the bigbluebuttonbn table.
    $bigbluebuttonbn->meetingid = (string)$DB->get_field('bigbluebuttonbn', 'meetingid', array('id' => $bigbluebuttonbn->id));
    // Log update action.
    logs::bigbluebuttonbn_log($bigbluebuttonbn, bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_EDIT);
    // Complete the process.
    instance::bigbluebuttonbn_process_post_save($bigbluebuttonbn);
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
    logs::bigbluebuttonbn_delete_instance_log($bigbluebuttonbn);

    return $result;
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
    if ($completed = bigbluebuttonbn_user_complete($course, $user, $bigbluebuttonbn)) {
        return fullname($user) . ' ' . get_string('view_message_has_joined', 'bigbluebuttonbn') . ' ' .
            get_string('view_message_session_for', 'bigbluebuttonbn') . ' ' . (string) $completed . ' ' .
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
 * @param object $bigbluebuttonbn
 *
 * @return bool
 */
function bigbluebuttonbn_user_complete($courseorid, $userorid, $bigbluebuttonbn) {
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
        bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_JOIN, bbb_constants::BIGBLUEBUTTONBN_LOG_EVENT_PLAYED));
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
 * Called by course/reset.php
 *
 * @param object $mform
 * @return void
 */
function bigbluebuttonbn_reset_course_form_definition(&$mform) {
    $items = reset::bigbluebuttonbn_reset_course_items();
    $mform->addElement('header', 'bigbluebuttonbnheader', get_string('modulenameplural', 'bigbluebuttonbn'));
    foreach ($items as $item => $default) {
        $mform->addElement(
            'advcheckbox',
            "reset_bigbluebuttonbn_{$item}",
            get_string("reset{$item}", 'bigbluebuttonbn')
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
    $items = reset::bigbluebuttonbn_reset_course_items();
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
    $items = reset::bigbluebuttonbn_reset_course_items();
    $status = array();
    // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
    // See MDL-9367.
    if (array_key_exists('recordings', $items) && !empty($data->reset_bigbluebuttonbn_recordings)) {
        // Remove all the recordings from a BBB server that are linked to the room/activities in this course.
        reset::bigbluebuttonbn_reset_recordings($data->courseid);
        unset($items['recordings']);
        $status[] = reset::bigbluebuttonbn_reset_getstatus('recordings');
    }
    if (!empty($data->reset_bigbluebuttonbn_tags)) {
        // Remove all the tags linked to the room/activities in this course.
        reset::bigbluebuttonbn_reset_tags($data->courseid);
        unset($items['tags']);
        $status[] = reset::bigbluebuttonbn_reset_getstatus('tags');
    }
    // TODO : seems to be duplicated code unless we just want to force reset tags.
    foreach ($items as $item => $default) {
        // Remove instances or elements linked to this course, others than recordings or tags.
        if (!empty($data->{"reset_bigbluebuttonbn_{$item}"})) {
            call_user_func("bigbluebuttonbn_reset_{$item}", $data->courseid);
            $status[] = reset::bigbluebuttonbn_reset_getstatus($item);
        }
    }
    return $status;
}

// Removed bigbluebuttonbn_get_view_actions as deprecated and used for legacy logs.
// Removed bigbluebuttonbn_get_post_actions as deprecated and used for legacy logs.
// Removed bigbluebuttonbn_print_overview as deprecated since 3.2.
// Removed bigbluebuttonbn_print_overview_element as deprecated since 3.2.

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

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, intro, introformat, completionattendance';
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', $dbparams, $fields);
    if (!$bigbluebuttonbn) {
        return null;
    }
    $info = new cached_cm_info();
    $info->name = $bigbluebuttonbn->name;
    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('bigbluebuttonbn', $bigbluebuttonbn, $coursemodule->id, false);
    }
    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules']['completionattendance'] = $bigbluebuttonbn->completionattendance;
    }

    return $info;
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_bigbluebuttonbn_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (empty($cm->customdata['customcompletionrules'])
        || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completionattendance':
                if (!empty($val)) {
                    $descriptions[] = get_string('completionattendancedesc', 'bigbluebuttonbn', $val);
                    $descriptions[] = get_string('completionengagementdesc', 'bigbluebuttonbn', $val);
                }
                break;
            default:
                break;
        }
    }
    return $descriptions;
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
    if (!files::bigbluebuttonbn_pluginfile_valid($context, $filearea)) {
        return false;
    }
    $file = files::bigbluebuttonbn_pluginfile_file($course, $cm, $context, $filearea, $args);
    if (empty($file)) {
        return false;
    }
    // Finally send the file.
    return send_stored_file($file, 0, 0, $forcedownload, $options); // download MUST be forced - security!
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

    $event = \mod_bigbluebuttonbn\event\activity_viewed::create($params); // Fix event name.
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
function mod_bigbluebuttonbn_core_calendar_provide_event_action(
    calendar_event $event,
    \core_calendar\action_factory $factory
) {
    global $CFG, $DB;
    // Get mod info.
    $cm = get_fast_modinfo($event->courseid)->instances['bigbluebuttonbn'][$event->instance];

    // Get bigbluebuttonbn activity.
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $event->instance), '*', MUST_EXIST);

    // Get if the user has joined in live session or viewed the recorded.
    $usercomplete = bigbluebuttonbn_user_complete($event->courseid, $event->userid, $bigbluebuttonbn);
    // Get if the room is available.
    list($roomavailable) = bigbluebutton::bigbluebuttonbn_room_is_available($bigbluebuttonbn);
    // Get if the user can join.
    list($usercanjoin) = meeting::bigbluebuttonbn_user_can_join_meeting($bigbluebuttonbn);
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
    $url = new moodle_url('/mod/bigbluebuttonbn/view.php', array('id' => $cm->id));
    if (groups_get_activity_groupmode($cm) == NOGROUPS) {
        // No groups mode.
        $string = get_string('view_conference_action_join', 'bigbluebuttonbn');
        $url = new moodle_url('/mod/bigbluebuttonbn/bbb_view.php', array('action' => 'join',
            'id' => $cm->id, 'bn' => $bigbluebuttonbn->id, 'timeline' => 1));
    }

    return $factory->create_instance($string, $url, 1, $actionable);
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settingsnav The settings navigation object
 * @param navigation_node $nodenav The node to add module settings to
 */
function bigbluebuttonbn_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $nodenav) {
    global $PAGE, $USER;
    // Don't add validate completion if the callback for meetingevents is NOT enabled.
    if (!(boolean)\mod_bigbluebuttonbn\local\config::get('meetingevents_enabled')) {
        return;
    }
    // Don't add validate completion if user is not allowed to edit the activity.
    $context = context_module::instance($PAGE->cm->id);
    if (!has_capability('moodle/course:manageactivities', $context, $USER->id)) {
        return;
    }
    $completionvalidate = '#action=completion_validate&bigbluebuttonbn=' . $PAGE->cm->instance;
    $nodenav->add(get_string('completionvalidatestate', 'bigbluebuttonbn'),
        $completionvalidate, navigation_node::TYPE_CONTAINER);
}
