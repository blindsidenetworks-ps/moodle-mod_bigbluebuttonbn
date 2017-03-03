<?php
/**
 * Upgrade logic.
 *
 * @package   mod_bigbluebuttonbn
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2010-2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

function xmldb_bigbluebuttonbn_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    $table_bigbluebuttonbn = new xmldb_table('bigbluebuttonbn');

    if ($oldversion < 2015080605) {
        //// Drop field description
        xmldb_bigbluebuttonbn_drop_field($dbman, $table_bigbluebuttonbn, 'description');

        //// Change welcome, allow null
        $field_definition = array('type' => XMLDB_TYPE_TEXT, 'precision' => null, 'unsigned' => null, 'notnull' => XMLDB_NOTNULL, 'sequence' => null, 'default' => null,'previous' => 'type');
        xmldb_bigbluebuttonbn_change_field($dbman, $table_bigbluebuttonbn, 'welcome', $field_definition);

        // Update the bigbluebuttonbn_log table
        $table_bigbluebuttonbn_log = new xmldb_table('bigbluebuttonbn_log');

        //// Change userid definition
        $field_definition = array('type' => XMLDB_TYPE_INTEGER, 'precision' => '10', 'unsigned' => XMLDB_UNSIGNED, 'notnull' => XMLDB_NOTNULL, 'sequence' => null, 'default' => null,'previous' => 'bigbluebuttonbnid');
        xmldb_bigbluebuttonbn_change_field($dbman, $table_bigbluebuttonbn_log, 'userid', $field_definition);

        upgrade_mod_savepoint(true, 2015080605, 'bigbluebuttonbn');
    }

    if ($oldversion < 2016011305) {
        //// Define field type to be droped from bigbluebuttonbn
        xmldb_bigbluebuttonbn_drop_field($dbman, $table_bigbluebuttonbn, 'type');

        //// Rename table bigbluebuttonbn_log to bigbluebuttonbn_logs
        xmldb_bigbluebuttonbn_rename_table($dbman, 'bigbluebuttonbn_log', 'bigbluebuttonbn_logs')

        //// Rename field event to log in table bigbluebuttonbn_logs
        $table_bigbluebuttonbn_logs = new xmldb_table('bigbluebuttonbn_logs');
        xmldb_bigbluebuttonbn_rename_field($dbman, $table_bigbluebuttonbn_logs, 'event', 'log');

        upgrade_mod_savepoint(true, 2016011305, 'bigbluebuttonbn');
    }

    if ($oldversion < 2016080106) {
        //// Drop field newwindow
        xmldb_bigbluebuttonbn_drop_field($dbman, $table_bigbluebuttonbn, 'newwindow');

        //// Add field type
        $field_definition = array('type' => XMLDB_TYPE_INTEGER, 'precision' => '2', 'unsigned' => XMLDB_UNSIGNED, 'notnull' => XMLDB_NOTNULL, 'sequence' => null, 'default' => 0,'previous' => 'id');
        xmldb_bigbluebuttonbn_add_field($dbman, $table_bigbluebuttonbn, 'type', $field_definition);

        //// Add field recordings_html
        $field_definition = array('type' => XMLDB_TYPE_INTEGER, 'precision' => '1', 'unsigned' => XMLDB_UNSIGNED, 'notnull' => XMLDB_NOTNULL, 'sequence' => null, 'default' => 0,'previous' => null);
        xmldb_bigbluebuttonbn_add_field($dbman, $table_bigbluebuttonbn, 'recordings_html', $field_definition);

        //// Add field recordings_deleted_activities
        $field_definition = array('type' => XMLDB_TYPE_INTEGER, 'precision' => '1', 'unsigned' => XMLDB_UNSIGNED, 'notnull' => XMLDB_NOTNULL, 'sequence' => null, 'default' => 1,'previous' => null);
        xmldb_bigbluebuttonbn_add_field($dbman, $table_bigbluebuttonbn, 'recordings_deleted_activities', $field_definition);

        upgrade_mod_savepoint(true, 2016080106, 'bigbluebuttonbn');
    }

    return true;
}

function xmldb_bigbluebuttonbn_add_field($dbman, $table, $field_name, $field_definition) {
    $field = new xmldb_field($field_name);
    $field->set_attributes($field_definition['type'], $field_definition['precision'], $field_definition['unsigned'], $field_definition['notnull'], $field_definition['sequence'], $field_definition['default'], $field_definition['previous']);
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field, $continue=true, $feedback=true);
    }
}

function xmldb_bigbluebuttonbn_drop_field($dbman, $table, $field_name) {
    $field = new xmldb_field($field_name);
    if ($dbman->field_exists($table, $field)) {
        $dbman->drop_field($table, $field, $continue=true, $feedback=true);
    }
}

function xmldb_bigbluebuttonbn_change_field($dbman, $table, $field_name, $field_definition) {
    $field = new xmldb_field($field_name);
    $field->set_attributes($field_definition['type'], $field_definition['precision'], $field_definition['unsigned'], $field_definition['notnull'], $field_definition['sequence'], $field_definition['default'], $field_definition['previous']);
    if( $dbman->field_exists($table, $field) ) {
        $dbman->change_field($table, $field, $continue=true, $feedback=true);
    }
}

function xmldb_bigbluebuttonbn_rename_field($dbman, $table, $field_name_old, $field_name_new) {
    $field = new xmldb_field($field_name_old);
    if ( $dbman->field_exists($table, $field) ) {
        $dbman->rename_field($table, $field, $field_name_new, $continue=true, $feedback=true);
    }
}

function xmldb_bigbluebuttonbn_rename_table($dbman, $table_name_old, $table_name_new) {
    $table = new xmldb_table($table_name_old);
    if ($dbman->table_exists($table)) {
        $dbman->rename_table($table, $table_name_new, $continue=true, $feedback=true);
    }
}
