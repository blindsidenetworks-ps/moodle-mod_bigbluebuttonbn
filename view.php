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
require_once($CFG->libdir . '/completionlib.php');

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
if( $bigbluebuttonbn->participants == null || $bigbluebuttonbn->participants == "" ){
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
$bbbsession['meetingname'] = $bigbluebuttonbn->name;
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
$bbbsession['durationtime'] = bigbluebuttonbn_get_duration($bigbluebuttonbn->openingtime, $bigbluebuttonbn->closingtime);
if( $bbbsession['durationtime'] > 0 )
    $bbbsession['welcome'] .= '<br><br>'.str_replace("%duration%", ''.$bbbsession['durationtime'], get_string('bbbdurationwarning', 'bigbluebuttonbn'));

//Additional info related to the course
$bbbsession['coursename'] = $course->fullname;
$bbbsession['courseid'] = $course->id;
$bbbsession['cm'] = $cm;

//Operation URLs
$bbbsession['courseURL'] = $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course;
$bbbsession['logoutURL'] = $CFG->wwwroot.'/mod/bigbluebuttonbn/bbb_view.php?action=logout&id='.$id.'&bn='.$bbbsession['bigbluebuttonbnid'];

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
$bbbsession['contextActivityTagging'] = "";
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
$PAGE->set_heading($course->shortname);
$PAGE->set_cacheable(false);
$PAGE->set_pagelayout('incourse');


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

$jsmodule = array(
        'name'     => 'mod_bigbluebuttonbn',
        'fullpath' => '/mod/bigbluebuttonbn/module.js',
        'requires' => array('datasource-get', 'datasource-jsonschema', 'datasource-polling'),
);
$PAGE->requires->js_init_call('M.mod_bigbluebuttonbn.init_view', array(), false, $jsmodule);

// Output starts here
echo $OUTPUT->header();

/// find out current groups mode
groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/bigbluebuttonbn/view.php?id=' . $cm->id);
if (groups_get_activity_groupmode($cm) == 0) {  //No groups mode
    $bbbsession['meetingid'] = $bigbluebuttonbn->meetingid.'-'.$bbbsession['courseid'].'-'.$bbbsession['bigbluebuttonbnid'];
} else {                                        // Separate groups mode
    //If doesnt have group
    $bbbsession['group'] = (!$group)?groups_get_activity_group($cm): $group;
    $bbbsession['meetingid'] = $bigbluebuttonbn->meetingid.'-'.$bbbsession['courseid'].'-'.$bbbsession['bigbluebuttonbnid'].'['.$bbbsession['group'].']';
    echo $OUTPUT->box_start('generalbox boxaligncenter');
    echo "<br>".get_string('view_groups_selection_warning', 'bigbluebuttonbn');
    echo $OUTPUT->box_end();
}

$bbbsession['joinURL'] = $CFG->wwwroot.'/mod/bigbluebuttonbn/bbb_view.php?action=join&id='.$id.'&bigbluebuttonbn='.$bbbsession['bigbluebuttonbnid'];

echo $OUTPUT->heading($bigbluebuttonbn->name, 3);
echo $OUTPUT->heading($bigbluebuttonbn->intro, 5);

$bigbluebuttonbn_view = '';
echo $OUTPUT->box_start('generalbox boxaligncenter');
if (!$bigbluebuttonbn->openingtime ) {
    if (!$bigbluebuttonbn->closingtime || time() <= $bigbluebuttonbn->closingtime){
        //GO JOINING
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

} else if ( time() < $bigbluebuttonbn->openingtime ){
    //CALLING BEFORE
    $SESSION->bigbluebuttonbn_bbbsession = $bbbsession;
    $bigbluebuttonbn_view = 'before';

    bigbluebuttonbn_view_before($bbbsession);

} else if (!$bigbluebuttonbn->closingtime || time() <= $bigbluebuttonbn->closingtime ) {
    //GO JOINING
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
        'action' => 'view',
        'meetingid' => $bbbsession['meetingid'],
        'bigbluebuttonbnid' => $bbbsession['bigbluebuttonbnid'],
        'bigbluebuttonbntype' => $bbbsession['bigbluebuttonbntype'],
        'ping_interval' => ($CFG->bigbluebuttonbn_waitformoderator_ping_interval > 0? $CFG->bigbluebuttonbn_waitformoderator_ping_interval * 1000: 10000)
);
$PAGE->requires->data_for_js('bigbluebuttonbn', $jsVars);

// Finish the page
echo $OUTPUT->footer();

function bigbluebuttonbn_view_joining($bbbsession){
    global $CFG, $DB, $OUTPUT;

    echo $OUTPUT->box_start('generalbox boxaligncenter', 'bigbluebuttonbn_view_message_box');
    echo '<br><span id="status_bar"></span><br>';
    echo '<br><span id="control_panel"></span><br>';
    echo $OUTPUT->box_end();

    echo $OUTPUT->box_start('generalbox boxaligncenter', 'bigbluebuttonbn_view_action_button_box');
    echo '<br><br><span id="join_button"></span>';
    echo $OUTPUT->box_end();

    if( $bbbsession['bigbluebuttonbntype'] == 0 ) {
        // View for the bigbluebuttonbn "Classroom" type

        //See if the session is in progress
        //if( bigbluebuttonbn_isMeetingRunning( $bbbsession['meetingid'], $bbbsession['endpoint'], $bbbsession['shared_secret'] ) ) {
        //    $initial_message = get_string('view_message_conference_in_progress', 'bigbluebuttonbn');
        //
        //} else {
        //    // If user is administrator, moderator or if is viewer and no waiting is required
        //    if( $bbbsession['administrator'] || $bbbsession['moderator'] || !$bbbsession['wait'] ) {
        //        $initial_message = get_string('view_message_conference_room_ready', 'bigbluebuttonbn');
        //
        //    } else {
        //        $initial_message = get_string('view_message_conference_about_to_start', 'bigbluebuttonbn');
        //
        //    }
        //}
        //
        //echo $OUTPUT->box_start('generalbox boxaligncenter', 'bigbluebuttonbn_view_message_box');
        //echo '<br><br><div id="status_bar">'.$initial_message.'</div>';
        //echo '<br><br><div id="control_panel"></div>';
        //echo $OUTPUT->box_end();
        //
        //echo $OUTPUT->box_start('generalbox boxaligncenter', 'bigbluebuttonbn_view_action_button_box');
        //echo "<br><br><div id=\"join_button\"><input type='button' onClick='window.open(\"".$bbbsession['joinURL']."\");' value='".get_string('view_conference_action_join', 'bigbluebuttonbn' )."'></div>";
        //echo $OUTPUT->box_end();
    }
}

function bigbluebuttonbn_view_before( $bbbsession ){

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

        ///Set strings to show
        $view_head_recording = get_string('view_head_recording', 'bigbluebuttonbn');
        $view_head_course = get_string('view_head_course', 'bigbluebuttonbn');
        $view_head_activity = get_string('view_head_activity', 'bigbluebuttonbn');
        $view_head_description = get_string('view_head_description', 'bigbluebuttonbn');
        $view_head_date = get_string('view_head_date', 'bigbluebuttonbn');
        $view_head_length = get_string('view_head_length', 'bigbluebuttonbn');
        $view_head_duration = get_string('view_head_duration', 'bigbluebuttonbn');
        $view_head_actionbar = get_string('view_head_actionbar', 'bigbluebuttonbn');
        $view_duration_min = get_string('view_duration_min', 'bigbluebuttonbn');

        ///Declare the table
        $table = new html_table();

        ///Initialize table headers
        if ( $bbbsession['administrator'] || $bbbsession['moderator'] ) {
            $table->head  = array ($view_head_recording, $view_head_activity, $view_head_description, $view_head_date, $view_head_duration, $view_head_actionbar);
            $table->align = array ('left', 'left', 'left', 'left', 'center', 'left');
        } else {
            $table->head  = array ($view_head_recording, $view_head_activity, $view_head_description, $view_head_date, $view_head_duration);
            $table->align = array ('left', 'left', 'left', 'left', 'center');
        }

        ///Build table content
        $recordings = bigbluebuttonbn_getRecordingsArray($bbbsession['meetingid'], $bbbsession['endpoint'], $bbbsession['shared_secret']);

        if ( !isset($recordings) || array_key_exists('messageKey', $recordings)) {  // There are no recordings for this meeting
            print_string('view_message_norecordings', 'bigbluebuttonbn');
        } else {                                                                    // Actually, there are recordings for this meeting
            foreach ( $recordings as $recording ){
                if ( $bbbsession['administrator'] || $bbbsession['moderator'] || $recording['published'] == 'true' ) {
                    $length = 0;
                    $endTime = isset($recording['endTime'])? floatval($recording['endTime']):0;
                    $endTime = $endTime - ($endTime % 1000);
                    $startTime = isset($recording['startTime'])? floatval($recording['startTime']):0;
                    $startTime = $startTime - ($startTime % 1000);
                    $duration = intval(($endTime - $startTime) / 60000);

                    //$meta_course = isset($recording['meta_context'])?str_replace('"', '\"', $recording['meta_context']):'';
                    $meta_activity = isset($recording['meta_contextactivity'])?str_replace('"', '\"', $recording['meta_contextactivity']):'';
                    $meta_description = isset($recording['meta_contextactivitydescription'])?str_replace('"', '\"', $recording['meta_contextactivitydescription']):'';

                    $actionbar = '';
                    $params['id'] = $bbbsession['cm']->id;
                    $params['recordingid'] = $recording['recordID'];
                    if ( $bbbsession['administrator'] || $bbbsession['moderator'] ) {
                        ///Set action [show|hide]
                        if ( $recording['published'] == 'true' ){
                            $params['action'] = 'hide';
                        } else {
                            $params['action'] = 'show';
                        }

                        //$url = new moodle_url('/mod/bigbluebuttonbn/view.php', $params);
                        //$url = new moodle_url('/mod/bigbluebuttonbn/bbb_broker.php', $params);
                        $url = null;
                        $action = null;
                        //$action = new component_action('click', 'M.mod_bigbluebuttonbn.broker_publishRecording("'.$params['action'].'", "'.$recording['recordID'].'")', array());
                        //With text
                        //$actionbar .= $OUTPUT->action_link(  $link, get_string( $params['action'] ), $action, array( 'title' => get_string($params['action'] ) )  );
                        //With icon
                        //$attributes = array('title' => get_string($params['action']), 'onclick' => 'alert("'.$params['action'].'"); return;');
                        $attributes = array('title' => get_string($params['action']), 'onclick' => 'M.mod_bigbluebuttonbn.broker_publishRecording("'.$params['action'].'", "'.$recording['recordID'].'");');
                        //$attributes = array('title' => get_string($params['action']));
                        $icon = new pix_icon('t/'.$params['action'], get_string($params['action']), 'moodle', $attributes);
                        $actionbar .= $OUTPUT->action_icon($url, $icon, $action, $attributes, false);

                        ///Set action delete
                        $params['action'] = 'delete';
                        //$url = new moodle_url('/mod/bigbluebuttonbn/view.php', $params);
                        //$action = new component_action('click', 'M.util.show_confirm_dialog', array('message' => get_string('view_delete_confirmation', 'bigbluebuttonbn')));
                        //With text
                        //$actionbar .= $OUTPUT->action_link(  $link, get_string( $params['action'] ), $action, array( 'title' => get_string($params['action']) )  );
                        //With icon
                        $attributes = array('title' => get_string($params['action']));
                        $attributes = array('title' => get_string($params['action']), 'onclick' => 'M.mod_bigbluebuttonbn.broker_deleteRecording("'.$params['action'].'", "'.$recording['recordID'].'"); return;');
                        $icon = new pix_icon('t/'.$params['action'], get_string($params['action']), 'moodle', $attributes);
                        $actionbar .= $OUTPUT->action_icon($url, $icon, $action, $attributes, false);

                        $attributes = array('title' => get_string($params['action']));
                        $icon = new pix_icon('t/unlock', get_string($params['action']), 'moodle', $attributes);
                        $actionbar .= $OUTPUT->action_icon($url, $icon, $action, $attributes, false);
                    }

                    $type = '';
                    foreach ( $recording['playbacks'] as $playback ){
                        if ($recording['published'] == 'true'){
                            $type .= $OUTPUT->action_link($playback['url'], $playback['type'], null, array('title' => $playback['type'], 'target' => '_new') ).'&#32;';
                        } else {
                            $type .= $playback['type'].'&#32;';
                        }
                    }

                    //Make sure the startTime is timestamp
                    if( !is_numeric($recording['startTime']) ){
                        $date = new DateTime($recording['startTime']);
                        $recording['startTime'] = date_timestamp_get($date);
                    } else {
                        $recording['startTime'] = $recording['startTime'] / 1000;
                    }
                    //Set corresponding format
                    $format = get_string('strftimerecentfull', 'langconfig');
                    if( isset($format) ) {
                        $formatedStartDate = userdate($recording['startTime'], $format);
                    } else {
                        $format = '%a %h %d, %Y %H:%M:%S %Z';
                        $formatedStartDate = userdate($recording['startTime'], $format, usertimezone($USER->timezone) );
                    }

                    if ( $bbbsession['administrator'] || $bbbsession['moderator'] ) {
                        $table->data[] = array ($type, $meta_activity, $meta_description, str_replace(" ", "&nbsp;", $formatedStartDate), $duration, $actionbar );
                    } else {
                        $table->data[] = array ($type, $meta_activity, $meta_description, str_replace(" ", "&nbsp;", $formatedStartDate), $duration);
                    }
                }
            }

            //Print the table
            echo '<div id="bigbluebuttonbn_html_table">'."\n";
            echo html_writer::table($table)."\n";
            echo '</div>'."\n";
        }
    }
}
?>
