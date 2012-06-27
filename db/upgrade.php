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
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0',
            'intro');

        // Drop field introformat
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field, $continue=true, $feedback=true);
        }

        // Once we reach this point, we can store the new version and consider the module
        // upgraded to the version 2012040200 so the next time this block is skipped
        upgrade_mod_savepoint(true, 2012040200, 'bigbluebuttonbn');
    }

    return $result;
}

?>
