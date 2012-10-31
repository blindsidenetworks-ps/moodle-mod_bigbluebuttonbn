<?php

/**
 * View and administrate BigBlueButton playback recordings
 *
 * Authors:
 *    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)    
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2011-2012 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$moderator = false;
$viewer = false;

$cid = optional_param('cid', 0, PARAM_INT); // course_module ID, or
$mid  = optional_param('mid', 0, PARAM_TEXT);  // bigbluebuttonbn instance ID
if ($cid) {
    $course = $DB->get_record('course', array('id'=>$cid), '*', MUST_EXIST);
    require_login($course, true);

    $viewer = true;
    $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
    $moderator = has_capability('mod/bigbluebuttonbn:moderate', $coursecontext);
}

$ajax_response = '';
if( $viewer ) {
    $salt = trim($CFG->BigBlueButtonBNSecuritySalt);
    $url = trim(trim($CFG->BigBlueButtonBNServerURL),'/').'/';
    
    if ( $mid ){
        $recordingsbn = bigbluebuttonbn_getRecordingsArray($mid, $url, $salt);
    
        $view_recording_list_actionbar_hide = get_string('view_recording_list_actionbar_hide', 'bigbluebuttonbn');
        $view_recording_list_actionbar_show = get_string('view_recording_list_actionbar_show', 'bigbluebuttonbn');
        $view_recording_list_actionbar_delete = get_string('view_recording_list_actionbar_delete', 'bigbluebuttonbn');
    
        if( isset($recordingsbn) && !isset($recordingsbn['messageKey']) ){
            foreach ( $recordingsbn as $recording ){
                if ( $moderator || $recording['published'] == 'true' ) {
                    
                    $endTime = isset($recording['endTime'])? intval(str_replace('"', '\"', $recording['endTime'])):0;
                    $endTime = $endTime - ($endTime % 1000);
                    $startTime = isset($recording['startTime'])? intval(str_replace('"', '\"', $recording['startTime'])):0;
                    $startTime = $startTime - ($startTime % 1000);
                    $duration = intval(($endTime - $startTime) / 60000);
                    
                    $meta_course = isset($recording['meta_context'])?str_replace('"', '\"', $recording['meta_context']):'';
                    $meta_activity = isset($recording['meta_contextactivity'])?str_replace('"', '\"', $recording['meta_contextactivity']):'';
                    $meta_description = isset($recording['meta_contextactivitydescription'])?str_replace('"', '\"', $recording['meta_contextactivitydescription']):'';
    
                    $actionbar = '';
                    if ( $moderator ) {
                        $deleteURL = bigbluebuttonbn_getDeleteRecordingsURL($recording['recordID'], $url, $salt);
                        if ( $recording['published'] == 'true' ){
                            $publishURL = bigbluebuttonbn_getPublishRecordingsURL($recording['recordID'], 'false', $url, $salt);
                            $actionbar = "<a id='actionbar-publish-a-".$recording['recordID']."' title='".$view_recording_list_actionbar_hide."' href='#'><img id='actionbar-publish-img-".$recording['recordID']."' src='pix/hide.gif' class='iconsmall' onClick='actionCall(\\\"unpublish\\\", \\\"".$recording['recordID']."\\\", \\\"".$cid."\\\")'   /></a>";
                        } else {
                            $publishURL = bigbluebuttonbn_getPublishRecordingsURL($recording['recordID'], 'true', $url, $salt);
                            $actionbar = "<a id='actionbar-publish-a-".$recording['recordID']."' title='".$view_recording_list_actionbar_show."' href='#'><img id='actionbar-publish-img-".$recording['recordID']."' src='pix/show.gif' class='iconsmall' onClick='actionCall(\\\"publish\\\", \\\"".$recording['recordID']."\\\", \\\"".$cid."\\\")'   /></a>";
                        }
                        $actionbar .= "<a id='actionbar-delete-a-".$recording['recordID']."' title='".$view_recording_list_actionbar_delete."' href='#'><img id='actionbar-delete-img-".$recording['recordID']."' src='pix/delete.gif' class='iconsmall' onClick='actionCall(\\\"delete\\\", \\\"".$recording['recordID']."\\\", \\\"".$cid."\\\")'   /></a>";
                    }
    
                    $type = '';
                    foreach ( $recording['playbacks'] as $playback ){
                        $type .= '<a href=\"'.$playback['url'].'\" target=\"_new\">'.$playback['type'].'</a>&#32;';
                    }
    
                    //Make sure the startTime is timestamp
                    if( !is_numeric($recording['startTime']) ){
                        $date = new DateTime($recording['startTime']);
                        $recording['startTime'] = date_timestamp_get($date);
                    } else {
                        $recording['startTime'] = $recording['startTime'] / 1000;
                    }
                    //Set corresponding format
                    //$format = isset(get_string('strftimerecentfull', 'langconfig'));
                    //if( !isset($format) )
                    $format = '%a %h %d %H:%M:%S %Z %Y';
                    //Format the date
                    $formatedStartDate = userdate($recording['startTime'], $format, usertimezone($USER->timezone) );
                    if( strlen($ajax_response) > 0 ) $ajax_response .= ", \n";
                    $ajax_response .= '["'.$type.'","'.$meta_course.'","'.$meta_activity.'","'.$meta_description.'","'.str_replace( " ", "&nbsp;", $formatedStartDate).'", "'. $duration.'", "'.$actionbar.'"]';
                }
            }
        }
    
    }
    
}

echo '{ "aaData": ['."\n";
if( strlen($ajax_response) > 0 )
    echo $ajax_response."\n";
else
    echo '["","","","","","",""]'."\n";
echo ']  }'."\n";


?>
