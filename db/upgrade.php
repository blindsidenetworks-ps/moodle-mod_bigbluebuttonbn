<?php
// This file is part of Moodle - http://moodle.org/

// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Upgrade logic.
 *
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2010-2015 Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
function xmldb_bigbluebuttonbn_upgrade($oldversion = 0)
{
    global $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    if ($oldversion < 2015080605) {
        //// Drop field description
        xmldb_bigbluebuttonbn_drop_field($dbman, 'bigbluebuttonbn', 'description');

        //// Change welcome, allow null
        $field_definition = array('type' => XMLDB_TYPE_TEXT, 'precision' => null, 'unsigned' => null, 'notnull' => XMLDB_NOTNULL, 'sequence' => null, 'default' => null, 'previous' => 'type');
        xmldb_bigbluebuttonbn_add_change_field($dbman, 'bigbluebuttonbn', 'welcome', $field_definition);

        //// Change userid definition in bigbluebuttonbn_log
        $field_definition = array('type' => XMLDB_TYPE_INTEGER, 'precision' => '10', 'unsigned' => XMLDB_UNSIGNED, 'notnull' => XMLDB_NOTNULL, 'sequence' => null, 'default' => null, 'previous' => 'bigbluebuttonbnid');
        xmldb_bigbluebuttonbn_add_change_field($dbman, 'bigbluebuttonbn_log', 'userid', $field_definition);

        upgrade_mod_savepoint(true, 2015080605, 'bigbluebuttonbn');
    }

    if ($oldversion < 2016011305) {
        //// Define field type to be droped from bigbluebuttonbn
        xmldb_bigbluebuttonbn_drop_field($dbman, 'bigbluebuttonbn', 'type');

        //// Rename table bigbluebuttonbn_log to bigbluebuttonbn_logs
        xmldb_bigbluebuttonbn_rename_table($dbman, 'bigbluebuttonbn_log', 'bigbluebuttonbn_logs');

        //// Rename field event to log in table bigbluebuttonbn_logs
        xmldb_bigbluebuttonbn_rename_field($dbman, 'bigbluebuttonbn_logs', 'event', 'log');

        upgrade_mod_savepoint(true, 2016011305, 'bigbluebuttonbn');
    }

    if ($oldversion < 2016080106) {
        //// Drop field newwindow
        xmldb_bigbluebuttonbn_drop_field($dbman, 'bigbluebuttonbn', 'newwindow');

        //// Add field type
        $field_definition = array('type' => XMLDB_TYPE_INTEGER, 'precision' => '2', 'unsigned' => XMLDB_UNSIGNED, 'notnull' => XMLDB_NOTNULL, 'sequence' => null, 'default' => 0, 'previous' => 'id');
        xmldb_bigbluebuttonbn_add_change_field($dbman, 'bigbluebuttonbn', 'type', $field_definition);

        //// Add field recordings_html
        $field_definition = array('type' => XMLDB_TYPE_INTEGER, 'precision' => '1', 'unsigned' => XMLDB_UNSIGNED, 'notnull' => XMLDB_NOTNULL, 'sequence' => null, 'default' => 0, 'previous' => null);
        xmldb_bigbluebuttonbn_add_change_field($dbman, 'bigbluebuttonbn', 'recordings_html', $field_definition);

        //// Add field recordings_deleted_activities
        $field_definition = array('type' => XMLDB_TYPE_INTEGER, 'precision' => '1', 'unsigned' => XMLDB_UNSIGNED, 'notnull' => XMLDB_NOTNULL, 'sequence' => null, 'default' => 1, 'previous' => null);
        xmldb_bigbluebuttonbn_add_change_field($dbman, 'bigbluebuttonbn', 'recordings_deleted_activities', $field_definition);

        upgrade_mod_savepoint(true, 2016080106, 'bigbluebuttonbn');
    }

    return true;
}

function xmldb_bigbluebuttonbn_add_change_field($dbman, $table_name, $field_name, $field_definition)
{
    $table = new xmldb_table($table_name);
    $field = new xmldb_field($field_name);
    $field->set_attributes($field_definition['type'], $field_definition['precision'], $field_definition['unsigned'], $field_definition['notnull'], $field_definition['sequence'], $field_definition['default'], $field_definition['previous']);
    if ($dbman->field_exists($table, $field)) {
        $dbman->change_field($table, $field, true, true);

        return;
    }

    $dbman->add_field($table, $field, true, true);
}

function xmldb_bigbluebuttonbn_drop_field($dbman, $table_name, $field_name)
{
    $table = new xmldb_table($table_name);
    $field = new xmldb_field($field_name);
    if ($dbman->field_exists($table, $field)) {
        $dbman->drop_field($table, $field, true, true);
    }
}

function xmldb_bigbluebuttonbn_rename_field($dbman, $table_name, $field_name_old, $field_name_new)
{
    $table = new xmldb_table($table_name);
    $field = new xmldb_field($field_name_old);
    if ($dbman->field_exists($table, $field)) {
        $dbman->rename_field($table, $field, $field_name_new, true, true);
    }
}

function xmldb_bigbluebuttonbn_rename_table($dbman, $table_name_old, $table_name_new)
{
    $table = new xmldb_table($table_name_old);
    if ($dbman->table_exists($table)) {
        $dbman->rename_table($table, $table_name_new, true, true);
    }
}
