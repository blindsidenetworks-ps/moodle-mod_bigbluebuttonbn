<?php 
/**
 * Version for BigBlueButtonBN Moodle Activity Module.
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
global $CFG;

$version = 2014070306;
$cron = 0;
$component = 'mod_bigbluebuttonbn';
$maturity = MATURITY_BETA;    // [MATURITY_STABLE | MATURITY_RC | MATURITY_BETA | MATURITY_ALPHA]
$release = '1.0.11';

if ( $CFG->version < '2013111800' ) {
    $module->version = $version;
    $module->requires = 2010112400.1;
    $module->cron = $cron;
    $module->component = $component;
    $module->maturity = $maturity;
    $module->release = $release;
} else {
    $plugin->version  = $version;
    $plugin->requires = 2013101800;
    $plugin->cron     = $cron;
    $plugin->component = $component;
    $plugin->maturity = $maturity;
    $plugin->release  = $release;
}