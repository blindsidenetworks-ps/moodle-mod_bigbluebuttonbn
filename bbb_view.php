<?php
/**
 * View for BigBlueButton interaction  
 *
 * @package   mod_bigbluebuttonbn
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = optional_param('id', 0, PARAM_INT);  // course_module ID, or
$bn = optional_param('bn', 0, PARAM_INT);  // bigbluebuttonbn instance ID
$action = required_param('action', PARAM_TEXT);

if ($id) {
    $cm = get_coursemodule_from_id('bigbluebuttonbn', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($bn) {
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $bn), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $bigbluebuttonbn->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('bigbluebuttonbn', $bigbluebuttonbn->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or a BigBlueButtonBN instance ID');
}

require_login($course, true, $cm);

if ( $CFG->version < '2013111800' ) {
    //This is valid before v2.6
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
} else {
    //This is valid after v2.6
    $context = context_module::instance($cm->id);
}

/// Print the page header
$PAGE->set_context($context);
$PAGE->set_url('/mod/bigbluebuttonbn/bbb_view.php', array('id' => $cm->id, 'bigbluebuttonbn' => $bigbluebuttonbn->id));
$PAGE->set_title(format_string($bigbluebuttonbn->name));
$PAGE->set_cacheable(false);
$PAGE->blocks->show_only_fake_blocks();


$bbbsession = $SESSION->bigbluebuttonbn_bbbsession;
if ( !isset($bbbsession) || is_null($bbbsession) ) {
    print_error( 'view_error_unable_join', 'bigbluebuttonbn' );

} else {
    switch (strtolower($action)) {
        case 'logout':
            bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_MEETING_LEFT, $bigbluebuttonbn, $context, $cm);
            bigbluebutton_bbb_view_close_window();

            break;
        case 'join':
            //See if the session is in progress
            if( bigbluebuttonbn_isMeetingRunning( $bbbsession['meetingid'], $bbbsession['endpoint'], $bbbsession['shared_secret'] ) ) {
                /// Since the meeting is already running, we just join the session
                if( $bbbsession['administrator'] || $bbbsession['moderator'] ) {
                    $join_url = bigbluebuttonbn_getJoinURL($bbbsession['meetingid'], $bbbsession['username'], $bbbsession['modPW'], $bbbsession['shared_secret'], $bbbsession['endpoint'], $bbbsession['userID']);

                } else {
                    $join_url = bigbluebuttonbn_getJoinURL($bbbsession['meetingid'], $bbbsession['username'], $bbbsession['viewerPW'], $bbbsession['shared_secret'], $bbbsession['endpoint'], $bbbsession['userID']);
                }

                /// Moodle event logger: Create an event for meeting joined
                bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_MEETING_JOINED, $bigbluebuttonbn, $context, $cm);
                header('Location: '.$join_url );

            } else {
                // If user is administrator, moderator or if is viewer and no waiting is required
                if( $bbbsession['administrator'] || $bbbsession['moderator'] || !$bbbsession['wait'] ) {
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
                            $bbbsession['endpoint'],
                            $bbbsession['logoutURL'],
                            $bbbsession['record']? 'true': 'false',
                            $bbbsession['durationtime'],
                            $bbbsession['voicebridge'],
                            $metadata,
                            $bbbsession['presentation']['name'],
                            $bbbsession['presentation']['url']
                    );

                    if (!$response) {
                        // If the server is unreachable, then prompts the user of the necessary action
                        if ( $bbbsession['administrator'] ) {
                            print_error( 'view_error_unable_join', 'bigbluebuttonbn', $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn' );
                        } else if ( $bbbsession['moderator'] ) {
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
                        bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_MEETING_CREATED, $bigbluebuttonbn, $context, $cm);

                        /// Internal logger: Instert a record with the meeting created
                        bigbluebuttonbn_log($bbbsession, 'Create');

                        $join_url = bigbluebuttonbn_getJoinURL($bbbsession['meetingid'], $bbbsession['username'], $bbbsession['modPW'], $bbbsession['shared_secret'], $bbbsession['endpoint'], $bbbsession['userID']);

                        /// Moodle event logger: Create an event for meeting joined
                        bigbluebuttonbn_event_log(BIGBLUEBUTTON_EVENT_MEETING_JOINED, $bigbluebuttonbn, $context, $cm);
                        header('Location: '.$join_url );
                    }                    

                } else {
                    //Show the wait for moderator code
                    echo $OUTPUT->header();
                    echo $OUTPUT->heading($bigbluebuttonbn->name, 3);
                    echo $OUTPUT->box_start('generalbox boxaligncenter');
                    echo "<br /><center>".get_string('view_message_conference_about_to_start', 'bigbluebuttonbn' ).'&nbsp;'.get_string('view_message_conference_wait_for_moderator', 'bigbluebuttonbn' )."</center><br /><br />";
                    echo '<center><img src="pix/polling.gif"></center>';
                    echo $OUTPUT->box_end();

                    //JavaScript variables
                    $jsVars = array(
                            'action' => 'ping',
                            'meetingid' => $bbbsession['meetingid'],
                            'joining' => 'true',
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

                    echo $OUTPUT->footer();
                }
            }

            break;
        default:
            bigbluebutton_bbb_view_close_window();
    }
}

////////////////// Local functions /////////////////////
function bigbluebutton_bbb_view_close_window() {
    global $OUTPUT, $PAGE;

    echo $OUTPUT->header();
    $PAGE->requires->js_init_call('M.mod_bigbluebuttonbn.viewend_CloseWindow');
    echo $OUTPUT->footer();

}

?>
