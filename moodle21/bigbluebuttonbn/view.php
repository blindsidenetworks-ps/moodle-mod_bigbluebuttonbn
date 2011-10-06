<?php
/**
 * Join a BigBlueButton room
 *
 * Authors:
 * 	Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 *      Jesus Federico  (jesus [at] blindsidenetworks [dt] com)    
 * 
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2011 Blindside Networks 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$b  = optional_param('n', 0, PARAM_INT);  // bigbluebuttonbn instance ID

if ($id) {
    $cm         = get_coursemodule_from_id('bigbluebuttonbn', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $bigbluebuttonbn  = $DB->get_record('bigbluebuttonbn', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($b) {
    $bigbluebuttonbn  = $DB->get_record('bigbluebuttonbn', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $bigbluebuttonbn->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('bigbluebuttonbn', $bigbluebuttonbn->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

//Extra parameters
$redirect = optional_param('redirect', 0, PARAM_INT);

require_login($course, true, $cm);

$context = get_context_instance(CONTEXT_MODULE, $cm->id);
$PAGE->set_context($context);

// show some info for guests
if (isguestuser()) {
    $PAGE->set_title(format_string($bigbluebuttonbn->name));
    echo $OUTPUT->header();
    echo $OUTPUT->confirm('<p>'.get_string('view_noguests', 'bigbluebuttonbn').'</p>'.get_string('liketologin'),
            get_login_url(), $CFG->wwwroot.'/course/view.php?id='.$course->id);

    echo $OUTPUT->footer();
    exit;
}

$moderator = has_capability('mod/bigbluebuttonbn:moderate', $context);
$administrator = has_capability('moodle/category:manage', $context);
add_to_log($course->id, 'bigbluebuttonbn', 'view', "view.php?id=$cm->id", $bigbluebuttonbn->name, $cm->id);

//Validates if the BigBlueButton server is running 
//BigBlueButton server data
$bbbsession['salt'] = trim($CFG->BigBlueButtonBNSecuritySalt);
$bbbsession['url'] = trim(trim($CFG->BigBlueButtonBNServerURL),'/').'/';

$serverVersion = BigBlueButtonBN::getServerVersion($bbbsession['url']); 
if ( !isset($serverVersion) ) { //Server is not working
    //$meetingsArray = BigBlueButtonBN::getMeetingsArray($url, $salt);
    if ( $administrator )
        print_error( 'view_error_unable_join', 'bigbluebuttonbn', $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn' );
    else if ( $moderator )
        print_error( 'view_error_unable_join_teacher', 'bigbluebuttonbn', $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course );
    else
        print_error( 'view_error_unable_join_student', 'bigbluebuttonbn', $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course );
} else {
    $xml = BigBlueButtonBN::_wrap_simplexml_load_file( BigBlueButtonBN::getMeetingsURL( $bbbsession['url'], $bbbsession['salt'] ) );
    if ( !isset($xml) || $xml->returncode == 'FAILED' ){ // The salt is wrong
        if ( $administrator ) 
            print_error( 'view_error_unable_join', 'bigbluebuttonbn', $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn' );
        else if ( $moderator )
            print_error( 'view_error_unable_join_teacher', 'bigbluebuttonbn', $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course );
        else
            print_error( 'view_error_unable_join_student', 'bigbluebuttonbn', $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course );
    }
}

//
// BigBlueButton Setup
//
//Server data
$bbbsession['modPW'] = $bigbluebuttonbn->moderatorpass;
$bbbsession['viewerPW'] = $bigbluebuttonbn->viewerpass;
//User data
$bbbsession['username'] = $USER->firstname.' '.$USER->lastname;
$bbbsession['userID'] = $USER->id;
$bbbsession['flag']['moderator'] = $moderator;
$bbbsession['textflag']['moderator'] = $moderator? 'true': 'false';
$bbbsession['flag']['administrator'] = $administrator;
$bbbsession['textflag']['administrator'] = $administrator? 'true': 'false';

//Load the email of the users enroled as teacher for the course
$bbbsession['useremail'] = '';
$context = get_context_instance(CONTEXT_COURSE, $bigbluebuttonbn->course);
$teachers = get_role_users(3, $context); //Teacher
foreach( $teachers as $teacher ){
    if( $bbbsession['useremail'] != '' )
        $bbbsession['useremail'] .= ',';
    $bbbsession['useremail'] .= $teacher->email;
}
$teachers = get_role_users(4, $context); //Non-editing teacher
foreach( $teachers as $teacher ){
    if( $bbbsession['useremail'] != '' )
        $bbbsession['useremail'] .= ',';
    $bbbsession['useremail'] .= $teacher->email;
}

//Database info related to the activity
$bbbsession['meetingname'] = $bigbluebuttonbn->name;
$bbbsession['welcome'] = $bigbluebuttonbn->welcome;
if( !isset($bbbsession['welcome']) || $bbbsession['welcome'] == '') {
    // $bbbsession['welcome'] = str_replace("%course%", '<b>'.$course->fullname.'</b>', get_string('mod_form_field_welcome_default', 'bigbluebuttonbn').'. '); //Replace the keys %course% and %school%
    $bbbsession['welcome'] = get_string('mod_form_field_welcome_default', 'bigbluebuttonbn'); 
}

$bbbsession['voicebridge'] = $bigbluebuttonbn->voicebridge;
$bbbsession['description'] = $bigbluebuttonbn->description;
$bbbsession['flag']['newwindow'] = $bigbluebuttonbn->newwindow;
$bbbsession['flag']['wait'] = $bigbluebuttonbn->wait;
$bbbsession['flag']['record'] = $bigbluebuttonbn->record;
$bbbsession['textflag']['newwindow'] = $bigbluebuttonbn->newwindow? 'true':'false';
$bbbsession['textflag']['wait'] = $bigbluebuttonbn->wait? 'true': 'false';
$bbbsession['textflag']['record'] = $bigbluebuttonbn->record? 'true': 'false';
if( $bigbluebuttonbn->record )
    $bbbsession['welcome'] .= '<br><br>'.get_string('bbbrecordwarning', 'bigbluebuttonbn');

$bbbsession['timeavailable'] = $bigbluebuttonbn->timeavailable;
$bbbsession['timedue'] = $bigbluebuttonbn->timedue;
$bbbsession['timeduration'] = intval($bigbluebuttonbn->timeduration / 60);
if( $bbbsession['timeduration'] > 0 )
    $bbbsession['welcome'] .= '<br><br>'.str_replace("%duration%", ''.$bbbsession['timeduration'], get_string('bbbdurationwarning', 'bigbluebuttonbn'));

//Additional info related to the course
$bbbsession['coursename'] = $course->fullname;
$bbbsession['courseid'] = $course->id;
$bbbsession['cm'] = $cm;

//Operation URLs
$bbbsession['courseURL'] = $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course;
$bbbsession['logoutURL'] = $CFG->wwwroot.'/mod/bigbluebuttonbn/index.php?id='.$bigbluebuttonbn->course;
//
// BigBlueButton Setup Ends
//

/// Print the page header
$PAGE->set_url('/mod/bigbluebuttonbn/view.php', array('id' => $cm->id));

if ( isset($redirect) && $redirect == 1 ){
    $PAGE->blocks->show_only_fake_blocks();
} else {
    $PAGE->set_title($bigbluebuttonbn->name);
    $PAGE->set_heading($course->shortname);
    $PAGE->set_button(update_module_button($cm->id, $course->id, get_string('modulename', 'bigbluebuttonbn')));
}

$PAGE->requires->js('/mod/bigbluebuttonbn/js/jquery.min.js', true);    
$PAGE->requires->js('/mod/bigbluebuttonbn/js/heartbeat.js', true);    
$PAGE->requires->js('/mod/bigbluebuttonbn/js/dataTables.js', true);    
$PAGE->requires->js('/mod/bigbluebuttonbn/js/bigbluebuttonbn.js', true);    

echo '<script type="text/javascript" >var logouturl = "'.$bbbsession['logoutURL'].'";</script>'."\n";
echo '<script type="text/javascript" >var newwindow = "'.$bbbsession['textflag']['newwindow'].'";</script>'."\n";
echo '<script type="text/javascript" >var waitformoderator = "'.$bbbsession['textflag']['wait'].'";</script>'."\n";
echo '<script type="text/javascript" >var ismoderator = "'.$bbbsession['textflag']['moderator'].'";</script>'."\n";

$PAGE->set_cacheable(false);

// Output starts here
echo $OUTPUT->header();

/// find out current groups mode
groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/bigbluebuttonbn/view.php?id=' . $cm->id);
if (groups_get_activity_groupmode($cm) == 0) {
    $bbbsession['meetingid'] = $bigbluebuttonbn->meetingid;
} else {
    $bbbsession['meetingid'] = $bigbluebuttonbn->meetingid.'['.groups_get_activity_group($cm).']';
    if ($moderator) // Take off the option visible groups       
        $PAGE->requires->js_init_call('M.mod_bigbluebuttonbn.setusergroups');
}

if( $moderator) 
    $bbbsession['joinURL'] = BigBlueButtonBN::joinURL($bbbsession['meetingid'], $bbbsession['username'], $bbbsession['modPW'], $bbbsession['salt'], $bbbsession['url'], $bbbsession['userID']);
else
    $bbbsession['joinURL'] = BigBlueButtonBN::joinURL($bbbsession['meetingid'], $bbbsession['username'], $bbbsession['viewerPW'], $bbbsession['salt'], $bbbsession['url'], $bbbsession['userID']);
//echo 'meetingid = '.$bbbsession['meetingid']."<br>\n";
//echo 'joinurl = '.$bbbsession['joinURL']."<br>\n";
echo '<script type="text/javascript" >var meetingid = "'.$bbbsession['meetingid'].'";</script>'."\n";
echo '<script type="text/javascript" >var joinurl = "'.$bbbsession['joinURL'].'";</script>'."\n";

if (!$bigbluebuttonbn->timeavailable ) {
    if (!$bigbluebuttonbn->timedue || time() <= $bigbluebuttonbn->timedue){
        //GO JOINING
        bigbluebuttonbn_view_joining( $bbbsession );
        
    } else {
        //CALLING AFTER
        echo $OUTPUT->heading(get_string('bbbfinished', 'bigbluebuttonbn'));
        echo $OUTPUT->box_start('generalbox boxaligncenter', 'dates');

        bigbluebuttonbn_view_after( $bbbsession );
        
        echo $OUTPUT->box_end();
        
    }
    
} else if ( time() < $bigbluebuttonbn->timeavailable ){
    //CALLING BEFORE
    echo $OUTPUT->heading(get_string('bbbnotavailableyet', 'bigbluebuttonbn'));
    echo $OUTPUT->box_start('generalbox boxaligncenter', 'dates');

    bigbluebuttonbn_view_before( $bbbsession );
    
    echo $OUTPUT->box_end();
    
} else if (!$bigbluebuttonbn->timedue || time() <= $bigbluebuttonbn->timedue ) {
    //GO JOINING
    bigbluebuttonbn_view_joining( $bbbsession );
        
} else {
    //CALLING AFTER
    echo $OUTPUT->heading(get_string('bbbfinished', 'bigbluebuttonbn'));
    echo $OUTPUT->box_start('generalbox boxaligncenter', 'dates');

    bigbluebuttonbn_view_after( $bbbsession );
        
    echo $OUTPUT->box_end();
    
}

// Finish the page
echo $OUTPUT->footer();


function bigbluebuttonbn_view_joining( $bbbsession ){

        echo '<script type="text/javascript" >var bigbluebuttonbn_view = "join";</script>'."\n";

        if( $bbbsession['flag']['moderator'] || !$bbbsession['flag']['wait'] ) {  // If is a moderator or if is a viewer and no waiting is required
            //
            // Join directly
            //
            
            $response = BigBlueButtonBN::createMeetingArray( $bbbsession['meetingname'], $bbbsession['meetingid'], $bbbsession['welcome'], $bbbsession['modPW'], $bbbsession['viewerPW'], $bbbsession['salt'], $bbbsession['url'], $bbbsession['logoutURL'], $bbbsession['textflag']['record'], $bbbsession['timeduration'], $bbbsession['voicebridge'], array("meta_course" => $bbbsession['coursename'], "meta_activity" => $bbbsession['meetingname'], "meta_description" => $bbbsession['description'], "meta_email" => $bbbsession['useremail'], "meta_recording" => $bbbsession['textflag']['record']) );

            if ( groups_get_activity_groupmode($bbbsession['cm']) > 0 && groups_get_activity_allowed_groups($bbbsession['cm']) ){
                print '<script type="text/javascript" >var joining = "false";</script>';
                print get_string('view_groups_selection', 'bigbluebuttonbn' )."&nbsp;&nbsp;<input type='button' onClick='bigbluebuttonbn_joinURL()' value='".get_string('view_groups_selection_join', 'bigbluebuttonbn' )."'>";
                
            } else {
                print '<script type="text/javascript" >var joining = "true";</script>';
                
                if( $bbbsession['flag']['moderator'] )
                    print "<br />".get_string('view_login_moderator', 'bigbluebuttonbn' )."<br /><br />";
                else
                    print "<br />".get_string('view_login_viewer', 'bigbluebuttonbn' )."<br /><br />";
                
                print "<center><img src='pix/loading.gif' /></center>";
                
            }
        
        } else {    // "Viewer" && Waiting for moderator is required;

            echo '<script type="text/javascript" >var joining = "true";</script>'."\n";

            print "<div align='center'>";
            if( BigBlueButtonBN::isMeetingRunning( $bbbsession['meetingid'], $bbbsession['url'], $bbbsession['salt'] ) == "true" ) {
                //
                // since the meeting is already running, we just join the session
                //
                print "<br />".get_string('view_login_viewer', 'bigbluebuttonbn' )."<br /><br />";
                print "<center><img src='pix/loading.gif' /></center>";
	
            } else {
                print "<br />".get_string('view_wait', 'bigbluebuttonbn' )."<br /><br />";
                print '<center><img src="pix/polling.gif"></center>';
            }

            print "</div>";
    
        }
        
}

function bigbluebuttonbn_view_before( $bbbsession ){

    echo '<script type="text/javascript" >'."\n";
    echo '    var joining = "false";'."\n";
    echo '    var bigbluebuttonbn_view = "before";'."\n"; 
    echo '</script>'."\n";

    echo '<table>';
    if ($bbbsession['timeavailable']) {
        echo '<tr><td class="c0">'.get_string('mod_form_field_availabledate','bigbluebuttonbn').':</td>';
        echo '    <td class="c1">'.userdate($bbbsession['timeavailable']).'</td></tr>';
    }
    if ($bbbsession['timedue']) {
        echo '<tr><td class="c0">'.get_string('mod_form_field_duedate','bigbluebuttonbn').':</td>';
        echo '    <td class="c1">'.userdate($bbbsession['timedue']).'</td></tr>';
    }
    echo '</table>';
        
}


function bigbluebuttonbn_view_after( $bbbsession ){

    echo '<script type="text/javascript" >'."\n";
    echo '    var joining = "false";'."\n";
    echo '    var bigbluebuttonbn_view = "after";'."\n"; 
    echo '    var view_recording_list_recording = "'.get_string('view_recording_list_recording', 'bigbluebuttonbn').'";'."\n";
    echo '    var view_recording_list_course = "'.get_string('view_recording_list_course', 'bigbluebuttonbn').'";'."\n";
    echo '    var view_recording_list_activity = "'.get_string('view_recording_list_activity', 'bigbluebuttonbn').'";'."\n";
    echo '    var view_recording_list_description = "'.get_string('view_recording_list_description', 'bigbluebuttonbn').'";'."\n";
    echo '    var view_recording_list_date = "'.get_string('view_recording_list_date', 'bigbluebuttonbn').'";'."\n";
    echo '    var view_recording_list_actionbar = "'.get_string('view_recording_list_actionbar', 'bigbluebuttonbn').'";'."\n";
    echo '</script>'."\n";

    $recordingsArray = BigBlueButtonBN::getRecordingsArray($bbbsession['meetingid'], $bbbsession['url'], $bbbsession['salt']); 
        
    if ( !isset($recordingsArray) || array_key_exists('messageKey', $recordingsArray)) {   // There are no recordings for this meeting
        if ( $bbbsession['flag']['record'] )
            print_string('bbbnorecordings', 'bigbluebuttonbn');
        
    } else {                                                                                // Actually, there are recordings for this meeting
        echo '    <center>'."\n";
            
        echo '      <table cellpadding="0" cellspacing="0" border="0" class="display" id="example">'."\n";
        echo '        <thead>'."\n";
        echo '        </thead>'."\n";
        echo '        <tbody>'."\n";
        echo '        </tbody>'."\n";
        echo '        <tfoot>'."\n";
        echo '        </tfoot>'."\n";
        echo '      </table>'."\n";

        echo '    </center>'."\n";
            
    }
        
}


?>
