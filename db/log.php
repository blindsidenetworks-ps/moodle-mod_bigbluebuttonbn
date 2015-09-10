<?php

/**
 * Definition of log events
 *
 * @package   mod_bigbluebuttonbn
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2010-2014 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die();

global $DB;

$logs = array(
    array('module'=>'bigbluebuttonbn', 'action'=>'add', 'mtable'=>'bigbluebuttonbn', 'field'=>'name'),
    array('module'=>'bigbluebuttonbn', 'action'=>'update', 'mtable'=>'bigbluebuttonbn', 'field'=>'name'),
    array('module'=>'bigbluebuttonbn', 'action'=>'view', 'mtable'=>'bigbluebuttonbn', 'field'=>'name'),
    array('module'=>'bigbluebuttonbn', 'action'=>'view all', 'mtable'=>'bigbluebuttonbn', 'field'=>'name'),
    array('module'=>'bigbluebuttonbn', 'action'=>'create', 'mtable'=>'bigbluebuttonbn', 'field'=>'name'),
    array('module'=>'bigbluebuttonbn', 'action'=>'end', 'mtable'=>'bigbluebuttonbn', 'field'=>'name'),
    array('module'=>'bigbluebuttonbn', 'action'=>'join', 'mtable'=>'bigbluebuttonbn', 'field'=>'name'),
    array('module'=>'bigbluebuttonbn', 'action'=>'left', 'mtable'=>'bigbluebuttonbn', 'field'=>'name'),
    array('module'=>'bigbluebuttonbn', 'action'=>'publish', 'mtable'=>'bigbluebuttonbn', 'field'=>'name'),
    array('module'=>'bigbluebuttonbn', 'action'=>'unpublish', 'mtable'=>'bigbluebuttonbn', 'field'=>'name'),
    array('module'=>'bigbluebuttonbn', 'action'=>'delete', 'mtable'=>'bigbluebuttonbn', 'field'=>'name')
);