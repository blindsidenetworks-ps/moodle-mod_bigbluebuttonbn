<?php
/**
 * Ping the BigBlueButton server to see if the meeting is running
 *
 * Authors:
 *    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)    
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2012 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

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

require_login($course, true, $cm);

$url = trim(trim($CFG->bigbluebuttonbnServerURL),'/').'/';
$salt = trim($CFG->bigbluebuttonbnSecuritySalt);

$meetingID = optional_param('meetingid', 0, PARAM_TEXT);
if( $meetingID ){
    echo bigbluebuttonbn_getMeetingXML( $meetingID, $url, $salt );
} else {
    echo 'false';
}



