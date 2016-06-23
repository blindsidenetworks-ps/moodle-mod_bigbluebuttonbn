<?php
/**
 * View a BigBlueButton room
 *
 * @package   mod_bigbluebuttonbn
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2010-2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = required_param('id', PARAM_INT);              // Course Module ID, or
$b  = optional_param('n', 0, PARAM_INT);            // bigbluebuttonbn instance ID
$group  = optional_param('group', 0, PARAM_INT);    // group instance ID

if ($id) {
    $cm = get_coursemodule_from_id('bigbluebuttonbn', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($b) {
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $b), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $bigbluebuttonbn->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('bigbluebuttonbn', $bigbluebuttonbn->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('view_error_url_missing_parameters', 'bigbluebuttonbn'));
}

require_login($course, true, $cm);

$version_major = bigbluebuttonbn_get_moodle_version_major();
if ( $version_major < '2013111800' ) {
    //This is valid before v2.6
    $module = $DB->get_record('modules', array('name' => 'bigbluebuttonbn'));
    $module_version = $module->version;
} else {
    //This is valid after v2.6
    $module_version = get_config('mod_bigbluebuttonbn', 'version');
}
$context = bigbluebuttonbn_get_context_module($cm->id);


bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_ACTIVITY_VIEWED, $bigbluebuttonbn, $context, $cm);

////////////////////////////////////////////////
/////  BigBlueButton Session Setup Starts  /////
////////////////////////////////////////////////
// BigBluebuttonBN activity data
$bbbsession['bigbluebuttonbn'] = $bigbluebuttonbn;

// User data
$bbbsession['username'] = get_string('fullnamedisplay', 'moodle', $USER);
$bbbsession['userID'] = $USER->id;
$bbbsession['roles'] = get_user_roles($context, $USER->id, true);

// User roles
if( $bigbluebuttonbn->participants == null || $bigbluebuttonbn->participants == "" || $bigbluebuttonbn->participants == "[]" ){
    //The room that is being used comes from a previous version
    $bbbsession['moderator'] = has_capability('mod/bigbluebuttonbn:moderate', $context);
} else {
    $bbbsession['moderator'] = bigbluebuttonbn_is_moderator($bbbsession['userID'], $bbbsession['roles'], $bigbluebuttonbn->participants);
}
$bbbsession['administrator'] = has_capability('moodle/category:manage', $context);
$bbbsession['managerecordings'] = ($bbbsession['administrator'] || has_capability('mod/bigbluebuttonbn:managerecordings', $context));

// BigBlueButton server data
$bbbsession['endpoint'] = bigbluebuttonbn_get_cfg_server_url();
$bbbsession['shared_secret'] = bigbluebuttonbn_get_cfg_shared_secret();

// Server data
$bbbsession['modPW'] = $bigbluebuttonbn->moderatorpass;
$bbbsession['viewerPW'] = $bigbluebuttonbn->viewerpass;

// Database info related to the activity
$bbbsession['meetingdescription'] = $bigbluebuttonbn->intro;
$bbbsession['welcome'] = $bigbluebuttonbn->welcome;
if( !isset($bbbsession['welcome']) || $bbbsession['welcome'] == '') {
    $bbbsession['welcome'] = get_string('mod_form_field_welcome_default', 'bigbluebuttonbn'); 
}

$bbbsession['userlimit'] = intval($bigbluebuttonbn->userlimit);
$bbbsession['voicebridge'] = ($bigbluebuttonbn->voicebridge > 0)? 70000 + $bigbluebuttonbn->voicebridge: $bigbluebuttonbn->voicebridge;
$bbbsession['wait'] = $bigbluebuttonbn->wait;
$bbbsession['record'] = $bigbluebuttonbn->record;
if( $bigbluebuttonbn->record )
    $bbbsession['welcome'] .= '<br><br>'.get_string('bbbrecordwarning', 'bigbluebuttonbn');
$bbbsession['tagging'] = $bigbluebuttonbn->tagging;

$bbbsession['openingtime'] = $bigbluebuttonbn->openingtime;
$bbbsession['closingtime'] = $bigbluebuttonbn->closingtime;

// Additional info related to the course
$bbbsession['course'] = $course;
$bbbsession['coursename'] = $course->fullname;
$bbbsession['cm'] = $cm;
$bbbsession['context'] = $context;

// Metadata (origin)
$bbbsession['origin'] = "Moodle";
$bbbsession['originVersion'] = $CFG->release;
$parsedUrl = parse_url($CFG->wwwroot);
$bbbsession['originServerName'] = $parsedUrl['host'];
$bbbsession['originServerUrl'] = $CFG->wwwroot;
$bbbsession['originServerCommonName'] = '';
$bbbsession['originTag'] = 'moodle-mod_bigbluebuttonbn ('.$module_version.')';
////////////////////////////////////////////////
/////   BigBlueButton Session Setup Ends   /////
////////////////////////////////////////////////

// Validates if the BigBlueButton server is running
$serverVersion = bigbluebuttonbn_getServerVersion($bbbsession['endpoint']);
if ( !isset($serverVersion) ) { //Server is not working
    if ( $bbbsession['administrator'] )
        print_error( 'view_error_unable_join', 'bigbluebuttonbn', $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn' );
    else if ( $bbbsession['moderator'] )
        print_error( 'view_error_unable_join_teacher', 'bigbluebuttonbn', $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course );
    else
        print_error( 'view_error_unable_join_student', 'bigbluebuttonbn', $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course );
} else {
    $xml = bigbluebuttonbn_wrap_xml_load_file( bigbluebuttonbn_getMeetingsURL( $bbbsession['endpoint'], $bbbsession['shared_secret'] ) );
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

// Print the page header
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot.'/mod/bigbluebuttonbn/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($bigbluebuttonbn->name));
$PAGE->set_cacheable(false);
$PAGE->set_heading($course->fullname);

if( $bigbluebuttonbn->newwindow == 1 ) {
    $PAGE->blocks->show_only_fake_blocks();

} else {
    $PAGE->set_pagelayout('incourse');
}

// Validate if the user is in a role allowed to join
if ( !has_capability('moodle/category:manage', $context) && !has_capability('mod/bigbluebuttonbn:join', $context) ) {
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

// Operation URLs
$bbbsession['courseURL'] = $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course;
$bbbsession['logoutURL'] = $CFG->wwwroot.'/mod/bigbluebuttonbn/bbb_view.php?action=logout&id='.$id.'&bn='.$bbbsession['bigbluebuttonbn']->id;
$bbbsession['recordingReadyURL'] = $CFG->wwwroot.'/mod/bigbluebuttonbn/bbb_broker.php?action=recording_ready';
$bbbsession['joinURL'] = $CFG->wwwroot.'/mod/bigbluebuttonbn/bbb_view.php?action=join&id='.$id.'&bigbluebuttonbn='.$bbbsession['bigbluebuttonbn']->id;

$bigbluebuttonbn_view = '';

// Output starts here
echo $OUTPUT->header();

/// find out current groups mode
$groupmode = groups_get_activity_groupmode($bbbsession['cm']);
if ($groupmode == NOGROUPS ) {  //No groups mode
    $bbbsession['meetingid'] = $bbbsession['bigbluebuttonbn']->meetingid.'-'.$bbbsession['course']->id.'-'.$bbbsession['bigbluebuttonbn']->id;
    $bbbsession['meetingname'] = $bbbsession['bigbluebuttonbn']->name;

} else {                                        // Separate or visible groups mode
    echo $OUTPUT->box_start('generalbox boxaligncenter');
    echo '<br><div class="alert alert-warning">'.get_string('view_groups_selection_warning', 'bigbluebuttonbn').'</div>';
    echo $OUTPUT->box_end();

    $bbbsession['group'] = groups_get_activity_group($bbbsession['cm'], true);
    if ($groupmode == SEPARATEGROUPS ) {
        groups_print_activity_menu($cm, $CFG->wwwroot.'/mod/bigbluebuttonbn/view.php?id='.$bbbsession['cm']->id, false, true);
        if( $bbbsession['group'] == 0 ) {
            if ( $bbbsession['administrator'] ) {
                $my_groups = groups_get_all_groups($bbbsession['course']->id);
            } else {
                $my_groups = groups_get_activity_allowed_groups($bbbsession['cm']);
            }
            $current_group = current($my_groups);
            $bbbsession['group'] = $current_group->id;
        }

    } else {
        groups_print_activity_menu($cm, $CFG->wwwroot.'/mod/bigbluebuttonbn/view.php?id='.$bbbsession['cm']->id);
    }

    $bbbsession['meetingid'] = $bbbsession['bigbluebuttonbn']->meetingid.'-'.$bbbsession['course']->id.'-'.$bbbsession['bigbluebuttonbn']->id.'['.$bbbsession['group'].']';
    if( $bbbsession['group'] > 0 )
        $group_name = groups_get_group_name($bbbsession['group']);
    else
        $group_name = get_string('allparticipants');
    $bbbsession['meetingname'] = $bbbsession['bigbluebuttonbn']->name.' ('.$group_name.')';
}
// Metadata (context)
$bbbsession['contextActivityName'] = $bbbsession['meetingname'];
$bbbsession['contextActivityDescription'] = bigbluebuttonbn_html2text($bbbsession['meetingdescription'], 64);
$bbbsession['contextActivityTags'] = "";

$now = time();
if (!empty($bigbluebuttonbn->openingtime) && $now < $bigbluebuttonbn->openingtime ) {
    //CALLING BEFORE
    $bigbluebuttonbn_view = 'before';

    // Initialize session variable used across views
    $SESSION->bigbluebuttonbn_bbbsession = $bbbsession;
    bigbluebuttonbn_view_before($bbbsession);

} else if (!empty($bigbluebuttonbn->closingtime) && $now > $bigbluebuttonbn->closingtime) {
    //CALLING AFTER
    $bigbluebuttonbn_view = 'after';
    $bbbsession['presentation'] = bigbluebuttonbn_get_presentation_array($context, $bigbluebuttonbn->presentation);

    // Initialize session variable used across views
    $SESSION->bigbluebuttonbn_bbbsession = $bbbsession;
    bigbluebuttonbn_view_after($bbbsession);

} else {
    //GO JOINING
    $bigbluebuttonbn_view = 'join';
    $bbbsession['presentation'] = bigbluebuttonbn_get_presentation_array($bbbsession['context'], $bigbluebuttonbn->presentation, $bigbluebuttonbn->id);

    // Initialize session variable used across views
    $SESSION->bigbluebuttonbn_bbbsession = $bbbsession;
    bigbluebuttonbn_view_joining($bbbsession);

    //JavaScript variables
    $waitformoderator_ping_interval = bigbluebuttonbn_get_cfg_waitformoderator_ping_interval();
    $jsVars = array(
        'action' => $bigbluebuttonbn_view,
        'meetingid' => $bbbsession['meetingid'],
        'bigbluebuttonbnid' => $bbbsession['bigbluebuttonbn']->id,
        'ping_interval' => ($waitformoderator_ping_interval > 0? $waitformoderator_ping_interval * 1000: 15000),
        'userlimit' => $bbbsession['userlimit'],
        'locales' => bigbluebuttonbn_get_locales_for_ui()
    );
    $PAGE->requires->data_for_js('bigbluebuttonbn', $jsVars);

    $jsmodule = array(
        'name'     => 'mod_bigbluebuttonbn',
        'fullpath' => '/mod/bigbluebuttonbn/module.js',
        'requires' => array('datasource-get', 'datasource-jsonschema', 'datasource-polling'),
    );
    $PAGE->requires->js_init_call('M.mod_bigbluebuttonbn.view_init', array(), false, $jsmodule);
}

// Finish the page
echo $OUTPUT->footer();

function bigbluebuttonbn_view_joining($bbbsession){
    global $CFG, $DB, $OUTPUT;

    echo $OUTPUT->heading($bbbsession['meetingname'], 3);
    echo $OUTPUT->heading($bbbsession['meetingdescription'], 5);
    echo $OUTPUT->box_start('generalbox boxaligncenter', 'bigbluebuttonbn_view_message_box');
    echo '<br><span id="status_bar"></span><br>';
    echo '<span id="control_panel"></span>';
    echo $OUTPUT->box_end();
    if( $bbbsession['tagging'] && ($bbbsession['administrator'] || $bbbsession['moderator']) ){
        echo ''.
          '<div id="panelContent" class="hidden">'.
          '  <div class="yui3-widget-bd">'.
          '    <form>'.
          '      <fieldset>'.
          '        <input type="hidden" name="join" id="meeting_join_url" value="">'.
          '        <input type="hidden" name="message" id="meeting_message" value="">'.
          '        <div>'.
          '          <label for="name">'.get_string('view_recording_name', 'bigbluebuttonbn').'</label><br/>'.
          '          <input type="text" name="name" id="recording_name" placeholder="">'.
          '        </div><br>'.
          '        <div>'.
          '          <label for="description">'.get_string('view_recording_description', 'bigbluebuttonbn').'</label><br/>'.
          '          <input type="text" name="description" id="recording_description" value="" placeholder="">'.
          '        </div><br>'.
          '        <div>'.
          '          <label for="tags">'.get_string('view_recording_tags', 'bigbluebuttonbn').'</label><br/>'.
          '          <input type="text" name="tags" id="recording_tags" value="" placeholder="">'.
          '        </div>'.
          '      </fieldset>'.
          '    </form>'.
          '  </div>'.
          '</div>';
    }

    echo $OUTPUT->box_start('generalbox boxaligncenter', 'bigbluebuttonbn_view_action_button_box');
    echo '<br><br><span id="join_button"></span>&nbsp;<span id="end_button"></span>';
    echo $OUTPUT->box_end();

    bigbluebuttonbn_view_recordings($bbbsession);
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

    bigbluebuttonbn_view_recordings($bbbsession);
}

function bigbluebuttonbn_view_recordings($bbbsession) {
    global $CFG;

    if( isset($bbbsession['record']) && $bbbsession['record'] ) {
        $output = html_writer::tag('h4', get_string('view_section_title_recordings', 'bigbluebuttonbn') );

        $meetingID='';
        $results = bigbluebuttonbn_getRecordedMeetings($bbbsession['course']->id, $bbbsession['bigbluebuttonbn']->id);

        //if( $recordingsbn->include_deleted_activities ) {
        //    $results_deleted = bigbluebuttonbn_getRecordedMeetingsDeleted($bbbsession['course']->id, $bbbsession['bigbluebuttonbn']->id);
        //    $results = array_merge($results, $results_deleted);
        //}

        if( $results ){
            //Eliminates duplicates
            $mIDs = array();
            foreach ($results as $result) {
                $mIDs[$result->meetingid] = $result->meetingid;
            }
            //Generates the meetingID string
            foreach ($mIDs as $mID) {
                if (strlen($meetingID) > 0) $meetingID .= ',';
                $meetingID .= $mID;
            }
        }

        // Get actual recordings
        //$recordings = bigbluebuttonbn_getRecordingsArray($bbbsession['meetingid'], $bbbsession['endpoint'], $bbbsession['shared_secret']);
        if ( $meetingID != '' ) {
            $recordings = bigbluebuttonbn_getRecordingsArray($meetingID, $bbbsession['endpoint'], $bbbsession['shared_secret']);
        } else {
            $recordings = Array();
        }
        // Get recording links
        $recordings_imported = bigbluebuttonbn_getRecordingsImportedArray($bbbsession['course']->id, $bbbsession['bigbluebuttonbn']->id);
        // Merge the recordings
        $recordings = array_merge( $recordings, $recordings_imported );
        // Render the table
        $output .= bigbluebutton_output_recording_table($bbbsession, $recordings)."\n";

        if ( $bbbsession['managerecordings'] && bigbluebuttonbn_get_cfg_importrecordings_enabled() ) {
            $button_import_recordings = html_writer::tag( 'input', '', array('type' => 'button', 'value' => get_string('view_recording_button_import', 'bigbluebuttonbn'), 'onclick' => 'window.location=\''.$CFG->wwwroot.'/mod/bigbluebuttonbn/import_view.php?bn='.$bbbsession['bigbluebuttonbn']->id.'\'') );
            $output .= html_writer::start_tag('br');
            $output .= html_writer::tag('span', $button_import_recordings, ['id'=>"import_recording_links_button"]);
            $output .= html_writer::tag('span', '', ['id'=>"import_recording_links_table"]);
        }

        echo $output;
    }
}