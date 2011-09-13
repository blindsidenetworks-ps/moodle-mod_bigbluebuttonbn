<?php

/*
 * Upgrade
 *
 * Authors:
 *      Fred Dixon (ffdixon [at] blindsidenetworks [dt] org)
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright 2010 Blindside Networks Inc.
 * @package mod/bigbluebutton
 */

// This file keeps track of upgrades to
// the bigbluebutton module

function xmldb_bigbluebutton_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

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
