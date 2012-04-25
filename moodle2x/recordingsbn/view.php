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


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // recordingsbn instance ID - it should be named as the first character of the module

if ($id) {
    $cm         = get_coursemodule_from_id('recordingsbn', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $recordingsbn  = $DB->get_record('recordingsbn', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $recordingsbn  = $DB->get_record('recordingsbn', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $recordingsbn->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('recordingsbn', $recordingsbn->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
$PAGE->set_context($context);

// show some info for guests
if (isguestuser()) {
    $PAGE->set_title(format_string($recordingsbn->name));
    echo $OUTPUT->header();
    echo $OUTPUT->confirm('<p>'.get_string('view_noguests', 'recordingsbn').'</p>'.get_string('liketologin'),
            get_login_url(), $CFG->wwwroot.'/course/view.php?id='.$course->id);

    echo $OUTPUT->footer();
    exit;
}

$moderator = has_capability('mod/bigbluebuttonbn:moderate', $context);

add_to_log($course->id, 'recordingsbn', 'view', "view.php?id={$cm->id}", $recordingsbn->name, $cm->id);

/// Print the page header
$PAGE->set_url($CFG->wwwroot.'/mod/recordingsbn/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($recordingsbn->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_button(update_module_button($cm->id, $course->id, get_string('modulename', 'recordingsbn')));
$PAGE->set_context($context);
$PAGE->set_cacheable(false);


$PAGE->requires->js('/mod/bigbluebuttonbn/js/libs/jquery/1.7.2/jquery.min.js', true);    
$PAGE->requires->js('/mod/bigbluebuttonbn/js/libs/dataTables/1.9.1/jquery.dataTables.min.js', true);    
$PAGE->requires->js('/mod/bigbluebuttonbn/js/bigbluebuttonbn.js', true);    

// Output starts here
echo $OUTPUT->header();

//
// BigBlueButton Setup
//

$salt = trim($CFG->BigBlueButtonBNSecuritySalt);
$url = trim(trim($CFG->BigBlueButtonBNServerURL),'/').'/';
$logoutURL = $CFG->wwwroot;
$username = $USER->firstname.' '.$USER->lastname;
$userID = $USER->id;

// Recordings plugin code
$results = $DB->get_records_sql('SELECT * FROM '.$CFG->prefix.'bigbluebuttonbn WHERE '.$CFG->prefix.'bigbluebuttonbn.course ='.$course->id );
$meetingID='';

$groups = groups_get_all_groups($course->id);
if( isset($groups) && count($groups) > 0 ){  //If the course has groups include groupid in the name to look for possible recordings related to the sub-activities
    foreach ($results as $result) {
        if (strlen($meetingID) > 0) $meetingID .= ',';
        $meetingID .= $result->meetingid;
        foreach ( $groups as $group ){
            $meetingID .= ','.$result->meetingid.'['.$group->id.']';
        }
    }
    
} else {                                    // No groups means that it wont check any other sub-activity
    foreach ($results as $result) {
        if (strlen($meetingID) > 0) $meetingID .= ',';
        $meetingID .= $result->meetingid;
    }
    
}

echo $OUTPUT->heading($recordingsbn->name);
echo $OUTPUT->box_start('generalbox boxaligncenter', 'dates');

echo '<script type="text/javascript" >var meetingid = "'.$meetingID.'";</script>'."\n";
echo '<script type="text/javascript" >var ismoderator = "'.($moderator?'true':'false').'";</script>'."\n";

echo '<script type="text/javascript" >'."\n";
echo '    var joining = "false";'."\n";
echo '    var bigbluebuttonbn_view = "after";'."\n"; 
echo '    var view_recording_list_recording = "'.get_string('view_recording_list_recording', 'bigbluebuttonbn').'";'."\n";
echo '    var view_recording_list_course = "'.get_string('view_recording_list_course', 'bigbluebuttonbn').'";'."\n";
echo '    var view_recording_list_activity = "'.get_string('view_recording_list_activity', 'bigbluebuttonbn').'";'."\n";
echo '    var view_recording_list_description = "'.get_string('view_recording_list_description', 'bigbluebuttonbn').'";'."\n";
echo '    var view_recording_list_date = "'.get_string('view_recording_list_date', 'bigbluebuttonbn').'";'."\n";
echo '    var view_recording_list_actionbar = "'.get_string('view_recording_list_actionbar', 'bigbluebuttonbn').'";'."\n";
echo '    var view_recording_list_actionbar_hide = "'.get_string('view_recording_list_actionbar_hide', 'bigbluebuttonbn').'";'."\n";
echo '    var view_recording_list_actionbar_show = "'.get_string('view_recording_list_actionbar_show', 'bigbluebuttonbn').'";'."\n";
echo '    var view_recording_list_actionbar_delete = "'.get_string('view_recording_list_actionbar_delete', 'bigbluebuttonbn').'";'."\n";
echo '</script>'."\n";

echo '    <center>'."\n";
echo '      <div id="dynamic"></div>'."\n";
echo '      <table cellpadding="0" cellspacing="0" border="0" class="display" id="recordingsbn">'."\n";
echo '        <thead>'."\n";
echo '        </thead>'."\n";
echo '        <tbody>'."\n";
echo '        </tbody>'."\n";
echo '        <tfoot>'."\n";
echo '        </tfoot>'."\n";
echo '      </table>'."\n";
echo '    </center>'."\n";

echo $OUTPUT->box_end();

// Finish the page
echo $OUTPUT->footer();

?>
