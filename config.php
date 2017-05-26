<?php
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// BigBlueButtonBN configuration file for moodle                         //
//                                                                       //
// This file should be renamed "config.php" in the plugin directory      //
//                                                                       //
// It is intended to be used for setting configuration by default and    //
// also for enable/diable configuration options in the admin setting UI  //
// for those multitenancy deployments where the admin account is given   //
// to the tenant owner and some shared information like the              //
// bigbluebutton_server_url and bigbluebutton_shared_secret must been    //
// kept private. And also when some of the features are going to be      //
// disabled for all the tenants in that server                           //
//                                                                       //
//                                                                       //
///////////////////////////////////////////////////////////////////////////
//                                                                       //
///////////////////////////////////////////////////////////////////////////
/**
 * Configuration file for bigbluebuttonbn
 *
 * @package   mod_bigbluebuttonbn
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

unset($BIGBLUEBUTTONBN_CFG);
global $BIGBLUEBUTTONBN_CFG;
$BIGBLUEBUTTONBN_CFG = new stdClass();

$BIGBLUEBUTTONBN_CFG->bigbluebuttonbn_recordingstatus_enabled = 1;

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
