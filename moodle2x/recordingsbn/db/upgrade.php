<?php

/**
 * View and administrate BigBlueButton playback recordings
 *
 * Authors:
 *    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 *
 * @package   mod_bigbluebutton
 * @copyright 2011-2012 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute recordingsbn upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_recordingsbn_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    if ($oldversion < 2012040200) {

        $table = new xmldb_table('recordingsbn');
        
        // Define field intro to be droped from recordingsbn
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, 'medium', null, null, null, null,'name');

        // Drop field intro
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field, $continue=true, $feedback=true);
        }

        // Define field introformat to be droped from recordingsbn
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0',
            'intro');

        // Add field introformat
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field, $continue=true, $feedback=true);
        }

        // Once we reach this point, we can store the new version and consider the module
        // upgraded to the version 2012040200 so the next time this block is skipped
        upgrade_mod_savepoint(true, 2012040200, 'recordingsbn');
    }

    if ($oldversion < 2012040210) {
        $table = new xmldb_table('recordingsbn');
        
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0',
        		'name');
        
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_default($table, $field, $continue=true, $feedback=true);
        }
        
        upgrade_mod_savepoint(true, 2012040210, 'recordingsbn');
    }
        
    
    // Final return of upgrade result (true, all went good) to Moodle.
    return true;
}
