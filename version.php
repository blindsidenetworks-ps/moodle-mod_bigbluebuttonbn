<?php 
/**
 * Version for BigBlueButtonBN Moodle Activity Module.
 *
 * Authors:
 *    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 *    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)    
 * 
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2012 Blindside Networks 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die;

$module->version  = 2012042306;         // The current module version (Date: YYYYMMDDXX)
$module->requires = 2010112400;         // Requires this Moodle version
$module->cron     = 0;                  // Period for cron to check this module (secs)
$module->component = 'mod_bigbluebuttonbn'; // To check on upgrade, that module sits in correct place
$module->maturity = MATURITY_RC;      // [MATURITY_STABLE | MATURITY_RC | MATURITY_BETA | MATURITY_ALPHA]
$module->release  = '1.0.7'; 

?>
