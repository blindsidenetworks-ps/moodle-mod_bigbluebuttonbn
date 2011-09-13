<?php

/**
 * View and administrate BigBlueButton playback recordings
 *
 * Authors:
 *      Jesus Federico (jesus [at] b l i n ds i de n  e t w o r ks [dt] com)
 *
 * @package   mod_bigbluebutton
 * @copyright 2011 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

/***************************** remove these comment marks and modify the code as needed
    'mod/recordingsbn:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'guest' => CAP_ALLOW,
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),

    'mod/recordingsbn:submit' => array(
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'student' => CAP_ALLOW
        )
    ),
******************************/
);

