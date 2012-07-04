<?php

/**
 * Capabilities for BigBlueButton
 *
 * Authors:
 *    Fred Dixon (ffdixon [at] blindsidenetworks [dt] com)
 *    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 Blindside Networks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
        
        'mod/bigbluebuttonbn:addinstance' => array(
                'riskbitmask' => RISK_XSS,
        
                'captype' => 'write',
                'contextlevel' => CONTEXT_COURSE,
                'archetypes' => array(
                        'editingteacher' => CAP_ALLOW,
                        'manager' => CAP_ALLOW
                ),
                'clonepermissionsfrom' => 'moodle/course:manageactivities'
        ),
        
        //
        // Ability to join a meeting
        'mod/bigbluebuttonbn:join' => array(
                'captype' => 'read',
                'contextlevel' => CONTEXT_MODULE,
                'legacy' => array(
                        'student' => CAP_ALLOW,
                        'teacher' => CAP_ALLOW,
                        'editingteacher' => CAP_ALLOW,
                        'manager' => CAP_ALLOW
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
                        'manager' => CAP_ALLOW
                )
        )
        

        
);

?>
