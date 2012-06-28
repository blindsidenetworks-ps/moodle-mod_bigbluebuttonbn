<?php
/**
 * Internal library of functions for module BigBlueButtonBN.
 * 
 * Authors:
 *    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)    
 * 
 * @package   mod
 * @subpackage bigbluebuttonbn
 * @copyright 2010-2012 Blindside Networks 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die;

function bigbluebuttonbn_log(array $bbbsession, $event) {
    global $DB;
    
    $log->meetingid = $bbbsession['meetingid'];
    $log->courseid = $bbbsession['courseid'];
    $log->bigbluebuttonbnid = $bbbsession['bigbluebuttonbnid'];
    $log->record = $bbbsession['textflag']['record'] == 'true'? 1: 0;
    $log->timecreated = time();
    $log->event = $event;
    
    $returnid = $DB->insert_record('bigbluebuttonbn_log', $log);
    
}
