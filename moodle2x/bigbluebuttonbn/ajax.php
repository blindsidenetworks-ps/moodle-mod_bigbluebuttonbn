<?php //Buld the <head>

/**
 * View and administrate BigBlueButton playback recordings
 *
 * Authors:
 *      Jesus Federico  (jesus [at] blindsidenetworks [dt] com)    
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2011-2012 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once(dirname(__FILE__).'/bbb_api/bbb_api.php');
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$salt = trim($CFG->BigBlueButtonBNSecuritySalt);
$url = trim(trim($CFG->BigBlueButtonBNServerURL),'/').'/';

echo '{ "aaData": ['."\n";

if ( isset($_GET['name']) && $_GET['name'] != '' ){

    $meetingID = $_GET['name'];

    if ( !isset($_GET['admin']) || ($admin = $_GET['admin']) != 'true' )
        $admin = 'false'; 	//To be replaced by a jquery operation
	
    $recordingsbn = BigBlueButtonBN::getRecordingsArray($meetingID, $url, $salt);

    $view_recording_list_actionbar_hide = get_string('view_recording_list_actionbar_hide', 'bigbluebuttonbn');
    $view_recording_list_actionbar_show = get_string('view_recording_list_actionbar_show', 'bigbluebuttonbn');
    $view_recording_list_actionbar_delete = get_string('view_recording_list_actionbar_delete', 'bigbluebuttonbn');
              
    if( isset($recordingsbn) && !isset($recordingsbn['messageKey']) ){
        foreach ( $recordingsbn as $recording ){
            if ( $admin == 'true' || $recording['published'] == 'true' ) {
                
                $meta_course = isset($recording['meta_course'])?str_replace('"', '\"', $recording['meta_course']):'';
                $meta_activity = isset($recording['meta_activity'])?str_replace('"', '\"', $recording['meta_activity']):'';
                $meta_description = isset($recording['meta_description'])?str_replace('"', '\"', $recording['meta_description']):'';

                $actionbar = '';
                if ( $admin == 'true' ) {
                    $deleteURL = BigBlueButtonBN::deleteRecordingsURL($recording['recordID'], $url, $salt);
                    if ( $recording['published'] == 'true' ){
                        $publishURL = BigBlueButtonBN::setPublishRecordingsURL($recording['recordID'], 'false', $url, $salt);
                        $actionbar = "<a id='actionbar-publish-a-".$recording['recordID']."' title='".$view_recording_list_actionbar_hide."' href='#'><img id='actionbar-publish-img-".$recording['recordID']."' src='pix/hide.gif' class='iconsmall' onClick='actionCall(\\\"".$publishURL."\\\", \\\"publish\\\", \\\"".$recording['recordID']."\\\")'   /></a>";
                    } else {
                        $publishURL = BigBlueButtonBN::setPublishRecordingsURL($recording['recordID'], 'true', $url, $salt);
                        $actionbar = "<a id='actionbar-publish-a-".$recording['recordID']."' title='".$view_recording_list_actionbar_show."' href='#'><img id='actionbar-publish-img-".$recording['recordID']."' src='pix/show.gif' class='iconsmall' onClick='actionCall(\\\"".$publishURL."\\\", \\\"publish\\\", \\\"".$recording['recordID']."\\\")'   /></a>";
                    }
                    $actionbar .= "<a id='actionbar-delete-a-".$recording['recordID']."' title='".$view_recording_list_actionbar_delete."' href='#'><img id='actionbar-delete-img-".$recording['recordID']."' src='pix/delete.gif' class='iconsmall' alt='Delete' onClick='actionCall(\\\"".$deleteURL."\\\", \\\"delete\\\", \\\"".$recording['recordID']."\\\")'   /></a>";
                          }
                
                $type = '';
                foreach ( $recording['playbacks'] as $playback ){
                    $type .= '<a href=\"'.$playback['url'].'\" target=\"_new\">'.$playback['type'].'</a>&#32;';	
                }
                
                //Make sure the startTime is timestamp
                // date_default_timezone_set($USER->timezone);
                if( !is_numeric($recording['startTime']) ){
                    $date = date_create($recording['startTime']);
                    $recording['startTime'] = date_timestamp_get($date) * 1000;
                }
                //Format the date
                $formatedStartDate = date("D M j, Y G:i:s T", $recording['startTime']/1000);
         		echo '	["'.$type.'","'.$meta_course.'","'.$meta_activity.'","'.$meta_description.'","'.str_replace( " ", "&nbsp;", $formatedStartDate).'","'.$actionbar.'"],'."\n";
            }
        }
    }

}

echo '	["","","","","",""]'."\n";
echo ']  }'."\n";


?>
