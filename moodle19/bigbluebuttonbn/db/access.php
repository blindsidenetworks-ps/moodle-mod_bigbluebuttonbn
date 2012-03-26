<?php
/**
 * Capabilities
 *
 * Authors:
 *      Fred Dixon (ffdixon [at] blindsidenetworks [dt] org)
 *      Jesus Federico (jesus [at] blindsidenetworks [dt] org)
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2012 Blindside Networks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

$mod_bigbluebuttonbn_capabilities = array(
	
	//
	// Ability to join a meeting
    'mod/bigbluebuttonbn:join' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),

	//
	// Ability to moderate a meeting
	'mod/bigbluebuttonbn:moderate' => array(
	    'captype' => 'write',
	    'contextlevel' => CONTEXT_MODULE,
	    'legacy' => array(
	        'teacher' => CAP_ALLOW,
	        'editingteacher' => CAP_ALLOW,
	        'admin' => CAP_ALLOW
	    )
	),
);

?>
