<?php
/*
 * Capabilities
 *
 * Authors:
 *      Fred Dixon (ffdixon [at] blindsidenetworks [dt] org)
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright 2010 Blindside Networks Inc.
 * @package mod/bigbluebutton
 */
$mod_bigbluebutton_capabilities = array(
	
	//
	// Ability to join a meeting
    'mod/bigbluebutton:join' => array(
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
	'mod/bigbluebutton:moderate' => array(
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
