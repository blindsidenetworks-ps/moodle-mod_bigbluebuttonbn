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

$action  = optional_param('action', 0, PARAM_TEXT);
$recordingid  = optional_param('recordingid', 0, PARAM_TEXT);

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

if ( $CFG->version < '2014051200' ) {
    //This is valid before v2.7
    add_to_log($course->id, 'bigbluebuttonbn', 'view', 'view.php?id=$cm->id', $bigbluebuttonbn->name, $cm->id);
} else {
    //This is valid after v2.7
    $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_activity_viewed::create(
            array(
                    'context' => $context,
                    'objectid' => $bigbluebuttonbn->id
            )
    );
    $event->trigger();
}

//User data
$bbbsession['username'] = get_string('fullnamedisplay', 'moodle', $USER);
$bbbsession['userID'] = $USER->id;
$bbbsession['roles'] = get_user_roles($context, $USER->id, true);

if( $bigbluebuttonbn->participants == null || $bigbluebuttonbn->participants == "" ){
    //The room that is being used comes from a previous version
    $moderator = has_capability('mod/bigbluebuttonbn:moderate', $context);
} else {
    $moderator = bigbluebuttonbn_is_moderator($bbbsession['userID'], $bbbsession['roles'], $bigbluebuttonbn->participants);
}
$administrator = has_capability('moodle/category:manage', $context);

//Validates if the BigBlueButton server is running 
//BigBlueButton server data
$bbbsession['url'] = trim(trim($CFG->bigbluebuttonbn_server_url),'/').'/';
$bbbsession['shared_secret'] = trim($CFG->bigbluebuttonbn_shared_secret);

$serverVersion = bigbluebuttonbn_getServerVersion($bbbsession['url']); 
if ( !isset($serverVersion) ) { //Server is not working
    if ( $administrator )
        print_error( 'view_error_unable_join', 'bigbluebuttonbn', $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn' );
    else if ( $moderator )
        print_error( 'view_error_unable_join_teacher', 'bigbluebuttonbn', $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course );
    else
        print_error( 'view_error_unable_join_student', 'bigbluebuttonbn', $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course );
} else {
    $xml = bigbluebuttonbn_wrap_simplexml_load_file( bigbluebuttonbn_getMeetingsURL( $bbbsession['url'], $bbbsession['shared_secret'] ) );
    if ( !isset($xml) || !isset($xml->returncode) || $xml->returncode == 'FAILED' ){ // The shared secret is wrong
        if ( $administrator ) 
            print_error( 'view_error_unable_join', 'bigbluebuttonbn', $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn' );
        else if ( $moderator )
            print_error( 'view_error_unable_join_teacher', 'bigbluebuttonbn', $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course );
        else
            print_error( 'view_error_unable_join_student', 'bigbluebuttonbn', $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course );
    }
}

////////////////////////////////////////
/////  BigBlueButton Setup Starts  /////
////////////////////////////////////////
//Server data
$bbbsession['modPW'] = $bigbluebuttonbn->moderatorpass;
$bbbsession['viewerPW'] = $bigbluebuttonbn->viewerpass;
//User roles
$bbbsession['flag']['moderator'] = $moderator;
$bbbsession['textflag']['moderator'] = $moderator? 'true': 'false';
$bbbsession['flag']['administrator'] = $administrator;
$bbbsession['textflag']['administrator'] = $administrator? 'true': 'false';

//Database info related to the activity
$bbbsession['meetingname'] = $bigbluebuttonbn->name;
$bbbsession['welcome'] = $bigbluebuttonbn->welcome;
if( !isset($bbbsession['welcome']) || $bbbsession['welcome'] == '') {
    $bbbsession['welcome'] = get_string('mod_form_field_welcome_default', 'bigbluebuttonbn'); 
}

$bbbsession['voicebridge'] = 70000 + $bigbluebuttonbn->voicebridge;
$bbbsession['flag']['newwindow'] = $bigbluebuttonbn->newwindow;
$bbbsession['flag']['wait'] = $bigbluebuttonbn->wait;
$bbbsession['flag']['record'] = $bigbluebuttonbn->record;
$bbbsession['textflag']['newwindow'] = $bigbluebuttonbn->newwindow? 'true':'false';
$bbbsession['textflag']['wait'] = $bigbluebuttonbn->wait? 'true': 'false';
$bbbsession['textflag']['record'] = $bigbluebuttonbn->record? 'true': 'false';
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
$bbbsession['logoutURL'] = $CFG->wwwroot.'/mod/bigbluebuttonbn/view_end.php?id='.$id;

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
/////   BigBlueButton Setup Ends   /////
////////////////////////////////////////

//Execute actions if there is one and it is allowed
if( isset($action) && isset($recordingid) && ($administrator || $moderator) ){
    if( $action == 'show' ) {
        bigbluebuttonbn_doPublishRecordings($recordingid, 'true', $bbbsession['url'], $bbbsession['shared_secret']);
        if ( $CFG->version < '2014051200' ) {
            //This is valid before v2.7
            add_to_log($course->id, 'bigbluebuttonbn', 'recording published', "", $bigbluebuttonbn->name, $cm->id);
        } else {
            //This is valid after v2.7
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_recording_published::create(
                    array(
                            'context' => $context,
                            'objectid' => $bigbluebuttonbn->id,
                            'other' => array(
                                    //'title' => $title,
                                    'rid' => $recordingid
                            )
                    )
            );
            $event->trigger();
        }
    } else if( $action == 'hide') {
        bigbluebuttonbn_doPublishRecordings($recordingid, 'false', $bbbsession['url'], $bbbsession['shared_secret']);
        if ( $CFG->version < '2014051200' ) {
            //This is valid before v2.7
            add_to_log($course->id, 'bigbluebuttonbn', 'recording unpublished', "", $bigbluebuttonbn->name, $cm->id);
        } else {
            //This is valid after v2.7
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_recording_unpublished::create(
                    array(
                            'context' => $context,
                            'objectid' => $bigbluebuttonbn->id,
                            'other' => array(
                                    //'title' => $title,
                                    'rid' => $recordingid
                            )
                    )
            );
            $event->trigger();
        }
    } else if( $action == 'delete') {
        bigbluebuttonbn_doDeleteRecordings($recordingid, $bbbsession['url'], $bbbsession['shared_secret']);
        if ( $CFG->version < '2014051200' ) {
            //This is valid before v2.7
            add_to_log($course->id, 'bigbluebuttonbn', 'recording deleted', '', $bigbluebuttonbn->name, $cm->id);
        } else {
            //This is valid after v2.7
            $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_recording_deleted::create(
                    array(
                            'context' => $context,
                            'objectid' => $bigbluebuttonbn->id,
                            'other' => array(
                                    //'title' => $title,
                                    'rid' => $recordingid
                            )
                    )
            );
            $event->trigger();
        }
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
if( $bbbsession['flag']['administrator'] || $bbbsession['flag']['moderator'] || !$bbbsession['flag']['wait'] ) {
    $PAGE->set_pagelayout('incourse');
} else {
    //Disable blocks for layouts which do include pre-post blocks
    $PAGE->blocks->show_only_fake_blocks();
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

$bbbsession['bigbluebuttonbnid'] = $bigbluebuttonbn->id;
/// find out current groups mode
groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/bigbluebuttonbn/view.php?id=' . $cm->id);
if (groups_get_activity_groupmode($cm) == 0) {  //No groups mode
    $bbbsession['meetingid'] = $bigbluebuttonbn->meetingid.'-'.$bbbsession['courseid'].'-'.$bbbsession['bigbluebuttonbnid'];
} else {                                        // Separate groups mode
    //If doesnt have group
    $bbbsession['group'] = (!$group)?groups_get_activity_group($cm): $group;
    $bbbsession['meetingid'] = $bigbluebuttonbn->meetingid.'-'.$bbbsession['courseid'].'-'.$bbbsession['bigbluebuttonbnid'].'['.$bbbsession['group'].']';
}

if( $bbbsession['flag']['administrator'] || $bbbsession['flag']['moderator'] )
    $bbbsession['joinURL'] = bigbluebuttonbn_getJoinURL($bbbsession['meetingid'], $bbbsession['username'], $bbbsession['modPW'], $bbbsession['shared_secret'], $bbbsession['url'], $bbbsession['userID']);
else
    $bbbsession['joinURL'] = bigbluebuttonbn_getJoinURL($bbbsession['meetingid'], $bbbsession['username'], $bbbsession['viewerPW'], $bbbsession['shared_secret'], $bbbsession['url'], $bbbsession['userID']);


$joining = false;
$bigbluebuttonbn_view = '';
if (!$bigbluebuttonbn->openingtime ) {
    if (!$bigbluebuttonbn->closingtime || time() <= $bigbluebuttonbn->closingtime){
        //GO JOINING
        $bbbsession['presentation'] = bigbluebuttonbn_get_presentation_array($context, $bigbluebuttonbn->presentation, $bigbluebuttonbn->id);
        $bigbluebuttonbn_view = 'join';
        $joining = bigbluebuttonbn_view_joining($bbbsession, $context);

    } else {
        //CALLING AFTER
        $bbbsession['presentation'] = bigbluebuttonbn_get_presentation_array($context, $bigbluebuttonbn->presentation);
        $bigbluebuttonbn_view = 'after';
        echo $OUTPUT->heading(get_string('view_message_finished', 'bigbluebuttonbn'), 3);
        echo $OUTPUT->box_start('generalbox boxaligncenter', 'dates');

        bigbluebuttonbn_view_after($bbbsession);
        
        echo $OUTPUT->box_end();
    }

} else if ( time() < $bigbluebuttonbn->openingtime ){
    //CALLING BEFORE
    $bigbluebuttonbn_view = 'before';
    echo $OUTPUT->heading(get_string('view_message_notavailableyet', 'bigbluebuttonbn'), 3);
    echo $OUTPUT->box_start('generalbox boxaligncenter', 'dates');

    bigbluebuttonbn_view_before($bbbsession);

    echo $OUTPUT->box_end();

} else if (!$bigbluebuttonbn->closingtime || time() <= $bigbluebuttonbn->closingtime ) {
    //GO JOINING
    $bbbsession['presentation'] = bigbluebuttonbn_get_presentation_array($context, $bigbluebuttonbn->presentation, $bigbluebuttonbn->id);
    $bigbluebuttonbn_view = 'join';
    $joining = bigbluebuttonbn_view_joining($bbbsession, $context);

} else {
    //CALLING AFTER
    $bbbsession['presentation'] = bigbluebuttonbn_get_presentation_array($context, $bigbluebuttonbn->presentation);
    $bigbluebuttonbn_view = 'after';
    echo $OUTPUT->heading(get_string('view_message_finished', 'bigbluebuttonbn'), 3);
    echo $OUTPUT->box_start('generalbox boxaligncenter', 'dates');

    bigbluebuttonbn_view_after($bbbsession);

    echo $OUTPUT->box_end();
}

//JavaScript variables
$jsVars = array(
        'newwindow' => $bbbsession['textflag']['newwindow'],
        'waitformoderator' => $bbbsession['textflag']['wait'],
        'isadministrator' => $bbbsession['textflag']['administrator'],
        'ismoderator' => $bbbsession['textflag']['moderator'],
        'meetingid' => $bbbsession['meetingid'],
        'joinurl' => $bbbsession['joinURL'],
        'joining' => ($joining? 'true':'false'),
        'bigbluebuttonbn_view' => $bigbluebuttonbn_view,
        'bigbluebuttonbnid' => $bbbsession['bigbluebuttonbnid'],
        'ping_interval' => ($CFG->bigbluebuttonbn_waitformoderator_ping_interval > 0? $CFG->bigbluebuttonbn_waitformoderator_ping_interval * 1000: 10000)
);

$jsmodule = array(
        'name'     => 'mod_bigbluebuttonbn',
        'fullpath' => '/mod/bigbluebuttonbn/module.js',
        'requires' => array('datasource-get', 'datasource-jsonschema', 'datasource-polling'),
);
$PAGE->requires->data_for_js('bigbluebuttonbn', $jsVars);
$PAGE->requires->js_init_call('M.mod_bigbluebuttonbn.init_view', array(), false, $jsmodule);

// Finish the page
echo $OUTPUT->footer();


function bigbluebuttonbn_view_joining( $bbbsession, $context ){
    global $CFG, $DB;

    $joining = false;

    // If user is administrator, moderator or if is viewer and no waiting is required
    if( $bbbsession['flag']['administrator'] || $bbbsession['flag']['moderator'] || !$bbbsession['flag']['wait'] ) {
        //
        // Join directly
        //
        $metadata = array("meta_origin" => $bbbsession['origin'],
                "meta_originVersion" => $bbbsession['originVersion'],
                "meta_originServerName" => $bbbsession['originServerName'],
                "meta_originServerCommonName" => $bbbsession['originServerCommonName'],
                "meta_originTag" => $bbbsession['originTag'],
                "meta_context" => $bbbsession['context'],
                "meta_recording_description" => $bbbsession['contextActivityDescription'],
                "meta_recording_tagging" => $bbbsession['contextActivityTagging']);
        $response = bigbluebuttonbn_getCreateMeetingArray(
                $bbbsession['meetingname'],
                $bbbsession['meetingid'],
                $bbbsession['welcome'],
                $bbbsession['modPW'],
                $bbbsession['viewerPW'],
                $bbbsession['shared_secret'],
                $bbbsession['url'],
                $bbbsession['logoutURL'],
                $bbbsession['textflag']['record'],
                $bbbsession['durationtime'],
                $bbbsession['voicebridge'],
                $metadata,
                $bbbsession['presentation']['name'],
                $bbbsession['presentation']['url']
        );

        if (!$response) {
            // If the server is unreachable, then prompts the user of the necessary action
            if ( $bbbsession['flag']['administrator'] ) {
                print_error( 'view_error_unable_join', 'bigbluebuttonbn', $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn' );
            } else if ( $bbbsession['flag']['moderator'] ) {
                print_error( 'view_error_unable_join_teacher', 'bigbluebuttonbn', $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn' );
            } else {
                print_error( 'view_error_unable_join_student', 'bigbluebuttonbn', $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn' );
            }

        } else if( $response['returncode'] == "FAILED" ) {
            // The meeting was not created
            $error_key = bigbluebuttonbn_get_error_key( $response['messageKey'], 'view_error_create' );
            if( !$error_key ) {
                print_error( $response['message'], 'bigbluebuttonbn' );
            } else {
                print_error( $error_key, 'bigbluebuttonbn' );
            }

        } else if ($response['hasBeenForciblyEnded'] == "true"){
            print_error( get_string( 'index_error_forciblyended', 'bigbluebuttonbn' ));

        } else { ///////////////Everything is ok /////////////////////
            /// Moodle event logger: Create an event for meeting created
            if ( $CFG->version < '2014051200' ) {
                //This is valid before v2.7
                add_to_log($bbbsession['courseid'], 'bigbluebuttonbn', 'meeting created', '', $bbbsession['meetingname'], $bbbsession['cm']->id);
            } else {
                //This is valid after v2.7
                $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_meeting_created::create(
                        array(
                                'context' => $context,
                                'objectid' => $bbbsession['bigbluebuttonbnid']
                        )
                );
                $event->trigger();
            }

            /// Internal logger: Instert a record with the meeting created
            bigbluebuttonbn_log($bbbsession, 'Create');

            if ( groups_get_activity_groupmode($bbbsession['cm']) > 0 && count(groups_get_activity_allowed_groups($bbbsession['cm'])) > 1 ){
                print "&nbsp;&nbsp;".get_string('view_groups_selection', 'bigbluebuttonbn' )."&nbsp;&nbsp;<input type='button' onClick='M.mod_bigbluebuttonbn.joinURL()' value='".get_string('view_groups_selection_join', 'bigbluebuttonbn' )."'>";
            } else {
                $joining = true;

                if( $bbbsession['flag']['administrator'] || $bbbsession['flag']['moderator'] )
                    print "<br />".get_string('view_login_moderator', 'bigbluebuttonbn' )."<br /><br />";
                else
                    print "<br />".get_string('view_login_viewer', 'bigbluebuttonbn' )."<br /><br />";
                
                print "<center><img src='pix/loading.gif' /></center>";
            }

            /// Moodle event logger: Create an event for meeting joined
            if ( $CFG->version < '2014051200' ) {
                //This is valid before v2.7
                add_to_log($bbbsession['courseid'], 'bigbluebuttonbn', 'meeting joined', '', $bbbsession['meetingname'], $bbbsession['cm']->id);
            } else {
                //This is valid after v2.7
                $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_meeting_joined::create(
                        array(
                                'context' => $context,
                                'objectid' => $bbbsession['bigbluebuttonbnid']
                        )
                );
                $event->trigger();
            }
        }
    } else {
        //    
        // "Viewer" && Waiting for moderator is required;
        //
        $joining = true;

        print "<div align='center'>";
        if( bigbluebuttonbn_wrap_simplexml_load_file(bigbluebuttonbn_getIsMeetingRunningURL( $bbbsession['meetingid'], $bbbsession['url'], $bbbsession['shared_secret'] )) == "true" ) {
            /// Since the meeting is already running, we just join the session
            print "<br />".get_string('view_login_viewer', 'bigbluebuttonbn' )."<br /><br />";
            print "<center><img src='pix/loading.gif' /></center>";
            /// Moodle event logger: Create an event for meeting joined
            if ( $CFG->version < '2014051200' ) {
                //This is valid before v2.7
                add_to_log($bbbsession['courseid'], 'bigbluebuttonbn', 'meeting joined', '', $bigbluebuttonbn->name, $bbbsession['cm']->id);
            } else {
                //This is valid after v2.7
                $event = \mod_bigbluebuttonbn\event\bigbluebuttonbn_meeting_joined::create(
                        array(
                                'context' => $context,
                                'objectid' => $bigbluebuttonbn->id
                        )
                );
                $event->trigger();
            }
        } else {
            /// Since the meeting is not running, the spining wheel is shown
            print "<br />".get_string('view_wait', 'bigbluebuttonbn' )."<br /><br />";
            print '<center><img src="pix/polling.gif"></center>';
        }
        print "</div>";
    }
    return $joining;
}

function bigbluebuttonbn_view_before( $bbbsession ){

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

    if( !is_null($bbbsession['presentation']['url']) ) {
        $attributes = array('title' => $bbbsession['presentation']['name']);
        $icon = new pix_icon($bbbsession['presentation']['icon'], $bbbsession['presentation']['mimetype_description']);

        echo '<h4>'.get_string('view_section_title_presentation', 'bigbluebuttonbn').'</h4>'.
             ''.$OUTPUT->action_icon($bbbsession['presentation']['url'], $icon, null, array(), false).''.
             ''.$OUTPUT->action_link($bbbsession['presentation']['url'], $bbbsession['presentation']['name'], null, $attributes).'<br><br>';
    }

    if( isset($bbbsession['flag']['record']) && $bbbsession['flag']['record'] ) {
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
        if ( $bbbsession['flag']['administrator'] || $bbbsession['flag']['moderator'] ) {
            $table->head  = array ($view_head_recording, $view_head_activity, $view_head_description, $view_head_date, $view_head_duration, $view_head_actionbar);
            $table->align = array ('left', 'left', 'left', 'left', 'center', 'left');
        } else {
            $table->head  = array ($view_head_recording, $view_head_activity, $view_head_description, $view_head_date, $view_head_duration);
            $table->align = array ('left', 'left', 'left', 'left', 'center');
        }

        ///Build table content
        $recordings = bigbluebuttonbn_getRecordingsArray($bbbsession['meetingid'], $bbbsession['url'], $bbbsession['shared_secret']);
        
        if ( !isset($recordings) || array_key_exists('messageKey', $recordings)) {  // There are no recordings for this meeting
            print_string('view_message_norecordings', 'bigbluebuttonbn');
        } else {                                                                    // Actually, there are recordings for this meeting
            foreach ( $recordings as $recording ){
                if ( $bbbsession['flag']['administrator'] || $bbbsession['flag']['moderator'] || $recording['published'] == 'true' ) {
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
                    if ( $bbbsession['flag']['administrator'] || $bbbsession['flag']['moderator'] ) {
                        ///Set action [show|hide]
                        if ( $recording['published'] == 'true' ){
                            $params['action'] = 'hide';
                        } else {
                            $params['action'] = 'show';
                        }

                        $url = new moodle_url('/mod/bigbluebuttonbn/view.php', $params);
                        $action = null;
                        //With text
                        //$actionbar .= $OUTPUT->action_link(  $link, get_string( $params['action'] ), $action, array( 'title' => get_string($params['action'] ) )  );
                        //With icon
                        $attributes = array('title' => get_string($params['action']));
                        $icon = new pix_icon('t/'.$params['action'], get_string($params['action']), 'moodle', $attributes);
                        $actionbar .= $OUTPUT->action_icon($url, $icon, $action, $attributes, false);
            
                        ///Set action delete
                        $params['action'] = 'delete';
                        $url = new moodle_url('/mod/bigbluebuttonbn/view.php', $params);
                        $action = new component_action('click', 'M.util.show_confirm_dialog', array('message' => get_string('view_delete_confirmation', 'bigbluebuttonbn')));
                        //With text
                        //$actionbar .= $OUTPUT->action_link(  $link, get_string( $params['action'] ), $action, array( 'title' => get_string($params['action']) )  );
                        //With icon
                        $attributes = array('title' => get_string($params['action']));
                        $icon = new pix_icon('t/'.$params['action'], get_string($params['action']), 'moodle', $attributes);
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

                    if ( $bbbsession['flag']['administrator'] || $bbbsession['flag']['moderator'] ) {
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
