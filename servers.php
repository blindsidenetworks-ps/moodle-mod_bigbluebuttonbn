<?php
/**
 * This script is owned by CBlue SPRL, please contact CBlue regarding any licences issues.
 *
 * @date :       30/10/2020
 * @author:      rlemaire@cblue.be
 * @copyright:   CBlue SPRL, 2020
 */

use mod_bigbluebuttonbn\servers\controller;

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');


$action = optional_param('action', controller::ACTION_VIEW, PARAM_ALPHANUMEXT);

// No guest autologin.
require_login(0, false);

$PAGE->set_context(context_system::instance());


$controller = new controller();
$controller->execute($action);
