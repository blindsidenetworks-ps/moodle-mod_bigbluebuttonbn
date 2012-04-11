<?php
/**
 * Language File
 *
 * Authors:
 *    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 *    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 *    
 * Translation files available at 
 *     http://www.getlocalization.com/bigbluebutton_moodle2x
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2012 Blindside Networks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
defined('MOODLE_INTERNAL') || die();

$recordingsbn_locales = Array(
        'modulename' => 'RecordingsBN',
        'modulenameplural' => 'RecordingsBN',
        'modulename_help' => 'Use the recordingsbn module as a resource of the course in order to have access to the playback recordings related to it.',
        'recordingsbnname' => 'Recordings name',
        'recordingsbnname_help' => 'RecordingsBN provides a list of playback recordings in a BigBlueButton Server providing direct access to them.',
        'recordingsbn' => 'RecordingsBN',
        'pluginadministration' => 'recordingsbn administration',
        'pluginname' => 'RecordingsBN',
        'recordingsbn:view' => 'View recordings',
        'view_noguests' => 'The RecordingsBN module is not open to guests',
        );

foreach($recordingsbn_locales as $key => $value){
    $string[$key] = $value;
}

?>