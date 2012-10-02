<?php

/**
 * Upgrade logic.
 *
 * Authors:
 *    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 *    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)    
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2012 Blindside Networks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

function xmldb_bigbluebuttonbn_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;
    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes
    
    $result = true;

    if ($result && $oldversion < 2012040200) {
        // Define field intro to be droped from bigbluebuttonbn
        $table = new xmldb_table('bigbluebuttonbn');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, 'medium', null, null, null, null,'name');

        // Drop field intro
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field, $continue=true, $feedback=true);
        }

        // Define field introformat to be droped from bigbluebuttonbn
        $table = new xmldb_table('bigbluebuttonbn');
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'intro');

        // Drop field introformat
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field, $continue=true, $feedback=true);
        }

        // Once we reach this point, we can store the new version and consider the module
        // upgraded to the version 2012040200 so the next time this block is skipped
        upgrade_mod_savepoint(true, 2012040200, 'bigbluebuttonbn');
    }
    
    if ($result && $oldversion < 2012062705) {

        // Define table bigbluebuttonbn_log to be created
        $table = new xmldb_table('bigbluebuttonbn_log');

        // Adding fields to table bigbluebuttonbn_log
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('meetingid', XMLDB_TYPE_CHAR, '256', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('bigbluebuttonbnid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('record', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('event', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);
                
        // Adding keys to table bigbluebuttonbn_log
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for bigbluebuttonbn_log
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // bigbluebuttonbn savepoint reached
        upgrade_mod_savepoint(true, 2012062705, 'bigbluebuttonbn');
    }

    if ($result && $oldversion < 2012100100) {

        $table = new xmldb_table('bigbluebuttonbn');
        $field = new xmldb_field('welcome');
        $field->set_attributes(XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, null, null, 'type');

        $dbman->change_field_type($table, $field, $continue=true, $feedback=true);
        
        upgrade_mod_savepoint(true, 2012100100, 'bigbluebuttonbn');
    }
    return $result;
}

?>
