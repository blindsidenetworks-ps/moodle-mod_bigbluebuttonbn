<?php
/**
 * Settings for BigBlueButtonBN
 *
 * Authors:
 *    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 *    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)    
 * 
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2014 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add( new admin_setting_configtext( 'BigBlueButtonBNServerURL', get_string( 'bigbluebuttonbnUrl', 'bigbluebuttonbn' ), get_string( 'bbburl', 'bigbluebuttonbn' ), 'http://test-install.blindsidenetworks.com/bigbluebutton/' ) );
    $settings->add( new admin_setting_configtext( 'BigBlueButtonBNSecuritySalt', get_string( 'bigbluebuttonbnSalt', 'bigbluebuttonbn' ), get_string( 'configsecuritysalt', 'bigbluebuttonbn' ), '8cd8ef52e8e101574e400365b55e11a6' ) );
    $settings->add( new admin_setting_configcheckbox( 'BigBlueButtonBNAllowRecording', get_string( 'bigbluebuttonbnAllowRecording', 'bigbluebuttonbn' ), get_string( 'bbballowrecording', 'bigbluebuttonbn' ), '0' ) );
    $settings->add( new admin_setting_configcheckbox( 'BigBlueButtonBNAllowAllModerators', get_string( 'bigbluebuttonbnAllowAllModerators', 'bigbluebuttonbn' ), get_string( 'bbballowallmoderators', 'bigbluebuttonbn' ), '0' ) );
}

?>
