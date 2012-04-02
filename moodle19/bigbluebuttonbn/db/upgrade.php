<?php

/*
 * Upgrade
 *
 * Authors:
 *      Fred Dixon (ffdixon [at] blindsidenetworks [dt] org)
 *      Jesus Federico (jesus [at] blindsidenetworks [dt] org)
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright 2010-2012 Blindside Networks Inc.
 * @package mod/bigbluebuttonbn
 */

// This file keeps track of upgrades to
// the bigbluebuttonbn module

function xmldb_bigbluebuttonbn_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

    if ($result && $oldversion < 2010123100) {
	// nothing to do yet
    }

    return $result;
}

?>
