<?php
/**
 * Capabilities for BigBlueButton
 *
 * @package   mod_bigbluebuttonbn
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2010-2014 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
        
        'mod/bigbluebuttonbn:addinstance' => array(
                'riskbitmask' => RISK_XSS,
        
                'captype' => 'write',
                'contextlevel' => CONTEXT_COURSE,
                'archetypes' => array(
                        'manager' => CAP_ALLOW,
                        //'coursecreator' => CAP_ALLOW,
                        'editingteacher' => CAP_ALLOW
                ),
                'clonepermissionsfrom' => 'moodle/course:manageactivities'
        ),

        //
        // Ability to join a meeting
        'mod/bigbluebuttonbn:join' => array(
                'captype' => 'read',
                'contextlevel' => CONTEXT_MODULE,
                'legacy' => array(
                        'manager' => CAP_ALLOW,
                        //'coursecreator' => CAP_ALLOW,
                        'editingteacher' => CAP_ALLOW,
                        'teacher' => CAP_ALLOW,
                        'student' => CAP_ALLOW
                )
        ),

        //
        // Ability to moderate a meeting
        'mod/bigbluebuttonbn:moderate' => array(
                'captype' => 'read',
                'contextlevel' => CONTEXT_MODULE,
                'legacy' => array(
                        'manager' => CAP_ALLOW,
                        //'coursecreator' => CAP_ALLOW,
                        'editingteacher' => CAP_ALLOW,
                        'teacher' => CAP_ALLOW
                )
        ),

        //
        // Ability to manage recordings
        'mod/bigbluebuttonbn:managerecordings' => array(
                'captype' => 'read',
                'contextlevel' => CONTEXT_MODULE,
                'legacy' => array(
                        'manager' => CAP_ALLOW,
                        //'coursecreator' => CAP_ALLOW,
                        'editingteacher' => CAP_ALLOW,
                        'teacher' => CAP_ALLOW
                )
        )
);