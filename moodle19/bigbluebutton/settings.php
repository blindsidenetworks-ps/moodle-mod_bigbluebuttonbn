<?php

/**
 * Accept settings.
 *
 * Authors:
 *      Fred Dixon (ffdixon [at] blindsidenetworks [dt] org)
 *
 * @package   mod_bigbluebutton
 * @copyright 2010 Blindside Networks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

$settings->add( new admin_setting_configtext( 'BigBlueButtonServerURL', get_string( 'bigbluebuttonUrl', 'bigbluebutton' ), get_string( 'bbburl', 'bigbluebutton' ), 'http://test-install.blindsidenetworks.com/bigbluebutton/' ) );
$settings->add( new admin_setting_configtext( 'BigBlueButtonSecuritySalt', get_string( 'bigbluebuttonSalt', 'bigbluebutton' ), get_string( 'configsecuritysalt', 'bigbluebutton' ), '8cd8ef52e8e101574e400365b55e11a6' ) );

?>
