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

ob_start();
header('Content-Type: text/json; charset=utf-8');
print '{["'.date('Y/m/d H:i:s').'"]}';

//print '{"error":{"lang":"en-US","description":"No definition found for Table search.web"}}';

