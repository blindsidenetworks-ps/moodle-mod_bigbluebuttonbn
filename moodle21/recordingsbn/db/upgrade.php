<?php

/**
 * View and administrate BigBlueButton playback recordings
 *
 * Authors:
 *      Jesus Federico (jesus [at] b l i n ds i de n  e t w o r ks [dt] com)
 *
 * @package   mod_bigbluebutton
 * @copyright 2011 Blindside Networks Inc.
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

    // And upgrade begins here. For each one, you'll need one
    // block of code similar to the next one. Please, delete
    // this comment lines once this file start handling proper
    // upgrade code.

    // if ($oldversion < YYYYMMDD00) { //New version in version.php
    //
    // }

    // Lines below (this included)  MUST BE DELETED once you get the first version
    // of your module ready to be installed. They are here only
    // for demonstrative purposes and to show how the recordingsbn
    // iself has been upgraded.

    // For each upgrade block, the file recordingsbn/version.php
    // needs to be updated . Such change allows Moodle to know
    // that this file has to be processed.

    // To know more about how to write correct DB upgrade scripts it's
    // highly recommended to read information available at:
    //   http://docs.moodle.org/en/Development:XMLDB_Documentation
    // and to play with the XMLDB Editor (in the admin menu) and its
    // PHP generation posibilities.

    // First example, some fields were added to install.xml on 2007/04/01
    if ($oldversion < 2007040100) {

        // Define field course to be added to recordingsbn
        $table = new xmldb_table('recordingsbn');
        $field = new xmldb_field('course', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'id');

        // Add field course
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field intro to be added to recordingsbn
        $table = new xmldb_table('recordingsbn');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, 'medium', null, null, null, null,'name');

        // Add field intro
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field introformat to be added to recordingsbn
        $table = new xmldb_table('recordingsbn');
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0',
            'intro');

        // Add field introformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Once we reach this point, we can store the new version and consider the module
        // upgraded to the version 2007040100 so the next time this block is skipped
        upgrade_mod_savepoint(true, 2007040100, 'recordingsbn');
    }

    // Second example, some hours later, the same day 2007/04/01
    // two more fields and one index were added to install.xml (note the micro increment
    // "01" in the last two digits of the version
    if ($oldversion < 2007040101) {

        // Define field timecreated to be added to recordingsbn
        $table = new xmldb_table('recordingsbn');
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0',
            'introformat');

        // Add field timecreated
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field timemodified to be added to recordingsbn
        $table = new xmldb_table('recordingsbn');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0',
            'timecreated');

        // Add field timemodified
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index course (not unique) to be added to recordingsbn
        $table = new xmldb_table('recordingsbn');
        $index = new xmldb_index('courseindex', XMLDB_INDEX_NOTUNIQUE, array('course'));

        // Add index to course field
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Another save point reached
        upgrade_mod_savepoint(true, 2007040101, 'recordingsbn');
    }

    // Third example, the next day, 2007/04/02 (with the trailing 00), some actions were performed to install.php,
    // related with the module
    if ($oldversion < 2007040200) {

        // insert here code to perform some actions (same as in install.php)

        upgrade_mod_savepoint(true, 2007040200, 'recordingsbn');
    }

    // And that's all. Please, examine and understand the 3 example blocks above. Also
    // it's interesting to look how other modules are using this script. Remember that
    // the basic idea is to have "blocks" of code (each one being executed only once,
    // when the module version (version.php) is updated.

    // Lines above (this included) MUST BE DELETED once you get the first version of
    // yout module working. Each time you need to modify something in the module (DB
    // related, you'll raise the version and add one upgrade block here.

    // Final return of upgrade result (true, all went good) to Moodle.
    return true;
}
