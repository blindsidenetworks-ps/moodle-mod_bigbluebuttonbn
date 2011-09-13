<?php

/**
 * Upgrade logic.
 *
 * Authors:
 *      Fred Dixon (ffdixon [at] blindsidenetworks [dt] com)
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 Blindside Networks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

function xmldb_bigbluebuttonbn_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;

    $result = true;

/// And upgrade begins here. For each one, you'll need one
/// block of code similar to the next one. Please, delete
/// this comment lines once this file start handling proper
/// upgrade code.

/// if ($result && $oldversion < YYYYMMDD00) { //New version in version.php
///     $result = result of "/lib/ddllib.php" function calls
/// }

    if ($result && $oldversion < 2010123100) {
	// nothing to do yet
    }

    return $result;
}

?>
