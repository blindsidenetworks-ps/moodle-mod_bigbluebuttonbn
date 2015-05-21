<?php
/**
 * Join a BigBlueButton room
 *
 * @package   mod_bigbluebuttonbn
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2010-2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$b  = optional_param('n', 0, PARAM_INT);  // bigbluebuttonbn instance ID
$group  = optional_param('group', 0, PARAM_INT);  // bigbluebuttonbn group ID

if ($id) {
    $cm = get_coursemodule_from_id('bigbluebuttonbn', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($b) {
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $bigbluebuttonbn->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('bigbluebuttonbn', $bigbluebuttonbn->id, $course->id, false, MUST_EXIST);
} else {
    print_error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

if ( $CFG->version < '2013111800' ) {
    //This is valid before v2.6
    $module = $DB->get_record('modules', array('name' => 'bigbluebuttonbn'));
    $module_version = $module->version;
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
} else {
    //This is valid after v2.6
    $module_version = get_config('mod_bigbluebuttonbn', 'version');
    $context = context_module::instance($cm->id);
}

bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_ACTIVITY_VIEWED, $bigbluebuttonbn, $context, $cm);

////////////////////////////////////////////////
/////  BigBlueButton Session Setup Starts  /////
////////////////////////////////////////////////
//BigBluebuttonBN activity data
$bbbsession['bigbluebuttonbnid'] = $bigbluebuttonbn->id;
$bbbsession['bigbluebuttonbntype'] = $bigbluebuttonbn->type;

//User data
$bbbsession['username'] = get_string('fullnamedisplay', 'moodle', $USER);
$bbbsession['userID'] = $USER->id;
$bbbsession['roles'] = get_user_roles($context, $USER->id, true);

//User roles
if( $bigbluebuttonbn->participants == null || $bigbluebuttonbn->participants == "" || $bigbluebuttonbn->participants == "[]" ){
    //The room that is being used comes from a previous version
    $bbbsession['moderator'] = has_capability('mod/bigbluebuttonbn:moderate', $context);
} else {
    $bbbsession['moderator'] = bigbluebuttonbn_is_moderator($bbbsession['userID'], $bbbsession['roles'], $bigbluebuttonbn->participants);
}
$bbbsession['administrator'] = has_capability('moodle/category:manage', $context);

//BigBlueButton server data
$bbbsession['endpoint'] = trim(trim($CFG->bigbluebuttonbn_server_url),'/').'/';
$bbbsession['shared_secret'] = trim($CFG->bigbluebuttonbn_shared_secret);

//Server data
$bbbsession['modPW'] = $bigbluebuttonbn->moderatorpass;
$bbbsession['viewerPW'] = $bigbluebuttonbn->viewerpass;

//Database info related to the activity
$bbbsession['meetingdescription'] = $bigbluebuttonbn->intro;
$bbbsession['welcome'] = $bigbluebuttonbn->welcome;
if( !isset($bbbsession['welcome']) || $bbbsession['welcome'] == '') {
    $bbbsession['welcome'] = get_string('mod_form_field_welcome_default', 'bigbluebuttonbn'); 
}

$bbbsession['voicebridge'] = 70000 + $bigbluebuttonbn->voicebridge;
$bbbsession['wait'] = $bigbluebuttonbn->wait;
$bbbsession['record'] = $bigbluebuttonbn->record;
if( $bigbluebuttonbn->record )
    $bbbsession['welcome'] .= '<br><br>'.get_string('bbbrecordwarning', 'bigbluebuttonbn');

$bbbsession['openingtime'] = $bigbluebuttonbn->openingtime;
$bbbsession['closingtime'] = $bigbluebuttonbn->closingtime;

//Additional info related to the course
$bbbsession['coursename'] = $course->fullname;
$bbbsession['courseid'] = $course->id;
$bbbsession['cm'] = $cm;

//Metadata
$bbbsession['origin'] = "Moodle";
$bbbsession['originVersion'] = $CFG->release;
$parsedUrl = parse_url($CFG->wwwroot);
$bbbsession['originServerName'] = $parsedUrl['host'];
$bbbsession['originServerUrl'] = $CFG->wwwroot;
$bbbsession['originServerCommonName'] = '';
$bbbsession['originTag'] = 'moodle-mod_bigbluebuttonbn ('.$module_version.')';
$bbbsession['context'] = $course->fullname;
$bbbsession['contextActivity'] = $bigbluebuttonbn->name;
$bbbsession['contextActivityDescription'] = "";
$bbbsession['contextActivityTags'] = "";
////////////////////////////////////////
/////   BigBlueButton Session Setup Ends   /////
////////////////////////////////////////

//Validates if the BigBlueButton server is running
$serverVersion = bigbluebuttonbn_getServerVersion($bbbsession['endpoint']);
if ( !isset($serverVersion) ) { //Server is not working
    if ( $bbbsession['administrator'] )
        print_error( 'view_error_unable_join', 'bigbluebuttonbn', $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn' );
    else if ( $bbbsession['moderator'] )
        print_error( 'view_error_unable_join_teacher', 'bigbluebuttonbn', $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course );
    else
        print_error( 'view_error_unable_join_student', 'bigbluebuttonbn', $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course );
} else {
    $xml = bigbluebuttonbn_wrap_simplexml_load_file( bigbluebuttonbn_getMeetingsURL( $bbbsession['endpoint'], $bbbsession['shared_secret'] ) );
    if ( !isset($xml) || !isset($xml->returncode) || $xml->returncode == 'FAILED' ){ // The shared secret is wrong
        if ( $bbbsession['administrator'] )
            print_error( 'view_error_unable_join', 'bigbluebuttonbn', $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn' );
        else if ( $bbbsession['moderator'] )
            print_error( 'view_error_unable_join_teacher', 'bigbluebuttonbn', $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course );
        else
            print_error( 'view_error_unable_join_student', 'bigbluebuttonbn', $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course );
    }
}

// Mark viewed by user (if required)
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

/// Print the page header
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot.'/mod/bigbluebuttonbn/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($bigbluebuttonbn->name));
$PAGE->set_cacheable(false);
$PAGE->set_heading($course->shortname);

if( $bigbluebuttonbn->newwindow == 1 ) {
    $PAGE->blocks->show_only_fake_blocks();

} else {
    $PAGE->set_pagelayout('incourse');
}

// Validate if the user is in a role allowed to join
if ( !has_capability('mod/bigbluebuttonbn:join', $context) ) {
    echo $OUTPUT->header();
    if (isguestuser()) {
        echo $OUTPUT->confirm('<p>'.get_string('view_noguests', 'bigbluebuttonbn').'</p>'.get_string('liketologin'),
            get_login_url(), $CFG->wwwroot.'/course/view.php?id='.$course->id);
    } else { 
        echo $OUTPUT->confirm('<p>'.get_string('view_nojoin', 'bigbluebuttonbn').'</p>'.get_string('liketologin'),
            get_login_url(), $CFG->wwwroot.'/course/view.php?id='.$course->id);
    }

    echo $OUTPUT->footer();
    exit;
}


// Output starts here
echo $OUTPUT->header();

/// find out current groups mode
if (groups_get_activity_groupmode($cm) == 0) {  //No groups mode
    $bbbsession['meetingid'] = $bigbluebuttonbn->meetingid.'-'.$bbbsession['courseid'].'-'.$bbbsession['bigbluebuttonbnid'];
    $bbbsession['meetingname'] = $bigbluebuttonbn->name;
} else {                                        // Separate groups mode
    //If doesnt have group
    $bbbsession['group'] = (!$group)?groups_get_activity_group($cm): $group;
    $bbbsession['meetingid'] = $bigbluebuttonbn->meetingid.'-'.$bbbsession['courseid'].'-'.$bbbsession['bigbluebuttonbnid'].'['.$bbbsession['group'].']';
    if( $bbbsession['group'] > 0 )
        $group_name = groups_get_group_name($bbbsession['group']);
    else
        $group_name = get_string('allparticipants');
    $bbbsession['meetingname'] = $bigbluebuttonbn->name.' ('.$group_name.')';    
    echo $OUTPUT->box_start('generalbox boxaligncenter');
    echo "<br>".get_string('view_groups_selection_warning', 'bigbluebuttonbn');
    echo $OUTPUT->box_end();
}

//Operation URLs
$bbbsession['courseURL'] = $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course;
$bbbsession['logoutURL'] = $CFG->wwwroot.'/mod/bigbluebuttonbn/bbb_view.php?action=logout&id='.$id.'&bn='.$bbbsession['bigbluebuttonbnid'];
//$bbbsession['recordingReadyURL'] = $CFG->wwwroot.'/mod/bigbluebuttonbn/bbb_broker.php?action=recording_ready&id='.$bbbsession['meetingid'].'&bigbluebuttonbn='.$bbbsession['bigbluebuttonbnid'];
$bbbsession['recordingReadyURL'] = $CFG->wwwroot.'/mod/bigbluebuttonbn/bbb_broker.php?action=recording_ready';
$bbbsession['joinURL'] = $CFG->wwwroot.'/mod/bigbluebuttonbn/bbb_view.php?action=join&id='.$id.'&bigbluebuttonbn='.$bbbsession['bigbluebuttonbnid'];

error_log("ENCODING FOR RECORDINGREADY CALLBACK");
$jwt_token = new stdClass();
$jwt_token->meeting_id = $bbbsession['meetingid'];
$jwt_key = trim($CFG->bigbluebuttonbn_shared_secret);
$jwt = JWT::encode($jwt_token, $jwt_key);
error_log($jwt);

echo $OUTPUT->heading($bigbluebuttonbn->name, 3);
echo $OUTPUT->heading($bigbluebuttonbn->intro, 5);

$bigbluebuttonbn_view = '';
echo $OUTPUT->box_start('generalbox boxaligncenter');
$now = time();
if (!$bigbluebuttonbn->openingtime ) {
    if (!$bigbluebuttonbn->closingtime || $now <= $bigbluebuttonbn->closingtime){
        //GO JOINING
        groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/bigbluebuttonbn/view.php?id=' . $cm->id);
        $bbbsession['presentation'] = bigbluebuttonbn_get_presentation_array($context, $bigbluebuttonbn->presentation, $bigbluebuttonbn->id);
        $SESSION->bigbluebuttonbn_bbbsession = $bbbsession;
        $bigbluebuttonbn_view = 'join';

        bigbluebuttonbn_view_joining($bbbsession);

    } else {
        //CALLING AFTER
        $bbbsession['presentation'] = bigbluebuttonbn_get_presentation_array($context, $bigbluebuttonbn->presentation);
        $SESSION->bigbluebuttonbn_bbbsession = $bbbsession;
        $bigbluebuttonbn_view = 'after';

        bigbluebuttonbn_view_after($bbbsession);
    }

} else if ( $now < ($bigbluebuttonbn->openingtime - intval($CFG->bigbluebuttonbn_scheduled_pre_opening) * 60) ){
    //CALLING BEFORE
    $SESSION->bigbluebuttonbn_bbbsession = $bbbsession;
    $bigbluebuttonbn_view = 'before';

    bigbluebuttonbn_view_before($bbbsession);

} else if (!$bigbluebuttonbn->closingtime || $now <= $bigbluebuttonbn->closingtime ) {
    //GO JOINING
    groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/bigbluebuttonbn/view.php?id=' . $cm->id);
    $bbbsession['presentation'] = bigbluebuttonbn_get_presentation_array($context, $bigbluebuttonbn->presentation, $bigbluebuttonbn->id);
    $SESSION->bigbluebuttonbn_bbbsession = $bbbsession;
    $bigbluebuttonbn_view = 'join';

    bigbluebuttonbn_view_joining($bbbsession);

} else {
    //CALLING AFTER
    $SESSION->bigbluebuttonbn_bbbsession = $bbbsession;
    $bbbsession['presentation'] = bigbluebuttonbn_get_presentation_array($context, $bigbluebuttonbn->presentation);
    $bigbluebuttonbn_view = 'after';

    bigbluebuttonbn_view_after($bbbsession);

}
echo $OUTPUT->box_end();

//JavaScript variables
$jsVars = array(
        'action' => $bigbluebuttonbn_view,
        'meetingid' => $bbbsession['meetingid'],
        'bigbluebuttonbnid' => $bbbsession['bigbluebuttonbnid'],
        'bigbluebuttonbntype' => $bbbsession['bigbluebuttonbntype'],
        'ping_interval' => ($CFG->bigbluebuttonbn_waitformoderator_ping_interval > 0? $CFG->bigbluebuttonbn_waitformoderator_ping_interval * 1000: 10000),
        'locales' => array(
                'not_started' => get_string('view_message_conference_not_started', 'bigbluebuttonbn' ),
                'wait_for_moderator' => get_string('view_message_conference_wait_for_moderator', 'bigbluebuttonbn' ),
                'in_progress' => get_string('view_message_conference_in_progress', 'bigbluebuttonbn' ),
                'started_at' => get_string('view_message_session_started_at', 'bigbluebuttonbn' ),
                'session_no_users' => get_string('view_message_session_no_users', 'bigbluebuttonbn' ),
                'session_has_user' => get_string('view_message_session_has_user', 'bigbluebuttonbn' ),
                'session_has_users' => get_string('view_message_session_has_users', 'bigbluebuttonbn' ),
                'has_joined' => get_string('view_message_has_joined', 'bigbluebuttonbn' ),
                'have_joined' => get_string('view_message_have_joined', 'bigbluebuttonbn' ),
                'user' => get_string('view_message_user', 'bigbluebuttonbn' ),
                'users' => get_string('view_message_users', 'bigbluebuttonbn' ),
                'viewer' => get_string('view_message_viewer', 'bigbluebuttonbn' ),
                'viewers' => get_string('view_message_viewers', 'bigbluebuttonbn' ),
                'moderator' => get_string('view_message_moderator', 'bigbluebuttonbn' ),
                'moderators' => get_string('view_message_moderators', 'bigbluebuttonbn' ),
                'publishing' => get_string('view_recording_list_actionbar_publishing', 'bigbluebuttonbn' ),
                'unpublishing' => get_string('view_recording_list_actionbar_unpublishing', 'bigbluebuttonbn' ),
        )
);
$PAGE->requires->data_for_js('bigbluebuttonbn', $jsVars);

$jsmodule = array(
        'name'     => 'mod_bigbluebuttonbn',
        'fullpath' => '/mod/bigbluebuttonbn/module.js',
        'requires' => array('datasource-get', 'datasource-jsonschema', 'datasource-polling'),
);
$PAGE->requires->js_init_call('M.mod_bigbluebuttonbn.view_init', array(), false, $jsmodule);

// Finish the page
echo $OUTPUT->footer();

function bigbluebuttonbn_view_joining($bbbsession){
    global $CFG, $DB, $OUTPUT;

    echo $OUTPUT->box_start('generalbox boxaligncenter', 'bigbluebuttonbn_view_message_box');
    echo '<br><span id="status_bar"></span><br>';
    echo '<br><span id="control_panel"></span><br>';
    echo $OUTPUT->box_end();

    echo $OUTPUT->box_start('generalbox boxaligncenter', 'bigbluebuttonbn_view_action_button_box');
    echo '<br><br><span id="join_button"></span>&nbsp;<span id="end_button"></span>';
    echo $OUTPUT->box_end();

    if( isset($bbbsession['record']) && $bbbsession['record'] ) {
        $table = bigbluebuttonbn_get_recording_table($bbbsession);
        
        if( isset($table->data) ) {
            //Print the table
            echo '<div id="bigbluebuttonbn_html_table">'."\n";
            echo html_writer::table($table)."\n";
            echo '</div>'."\n";
        }
    }

    if( $bbbsession['bigbluebuttonbntype'] == 0 ) {
        // View for the bigbluebuttonbn "Classroom" type
    }
}

function bigbluebuttonbn_view_before( $bbbsession ){
    global $CFG, $DB, $OUTPUT;
    
    echo $OUTPUT->heading(get_string('view_message_conference_not_started', 'bigbluebuttonbn'), 3);

    echo '<table>';
    if ($bbbsession['openingtime']) {
        echo '<tr><td class="c0">'.get_string('mod_form_field_openingtime','bigbluebuttonbn').':</td>';
        echo '    <td class="c1">'.userdate($bbbsession['openingtime']).'</td></tr>';
    }
    if ($bbbsession['closingtime']) {
        echo '<tr><td class="c0">'.get_string('mod_form_field_closingtime','bigbluebuttonbn').':</td>';
        echo '    <td class="c1">'.userdate($bbbsession['closingtime']).'</td></tr>';
    }
    echo '</table>';
}

function bigbluebuttonbn_view_after($bbbsession) {
    global $OUTPUT;

    echo $OUTPUT->heading(get_string('view_message_conference_has_ended', 'bigbluebuttonbn'), 3);

    if( !is_null($bbbsession['presentation']['url']) ) {
        $attributes = array('title' => $bbbsession['presentation']['name']);
        $icon = new pix_icon($bbbsession['presentation']['icon'], $bbbsession['presentation']['mimetype_description']);

        echo '<h4>'.get_string('view_section_title_presentation', 'bigbluebuttonbn').'</h4>'.
             ''.$OUTPUT->action_icon($bbbsession['presentation']['url'], $icon, null, array(), false).''.
             ''.$OUTPUT->action_link($bbbsession['presentation']['url'], $bbbsession['presentation']['name'], null, $attributes).'<br><br>';
    }

    if( isset($bbbsession['record']) && $bbbsession['record'] ) {
        echo '<h4>'.get_string('view_section_title_recordings', 'bigbluebuttonbn').'</h4>';

        $table = bigbluebuttonbn_get_recording_table($bbbsession);

        if( isset($table->data) ) {
            //Print the table
            echo '<div id="bigbluebuttonbn_html_table">'."\n";
            echo html_writer::table($table)."\n";
            echo '</div>'."\n";

        } else {
            print_string('view_message_norecordings', 'bigbluebuttonbn');
        }                                                                    // Actually, there are recordings for this meeting
    }
}

?>
