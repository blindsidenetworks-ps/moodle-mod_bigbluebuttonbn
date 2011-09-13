<?php

/**
 * Test for when a meeting is running.
 *
 * Authors:
 *      Fred Dixon (ffdixon [at] blindsidenetworks [dt] org)
 *
 * @package   mod_bigbluebutton
 * @copyright 2010 Blindside Networks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once( "../../config.php" );
require_once("lib.php");

$name = $_GET['name'];

$salt = trim($CFG->BigBlueButtonSecuritySalt);
$url = trim(trim($CFG->BigBlueButtonServerURL),'/').'/';

echo BigBlueButton::getMeetingXML( $name, $url, $salt );
?>
