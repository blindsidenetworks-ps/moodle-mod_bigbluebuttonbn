<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Settings for BigBlueButtonBN.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 */

use mod_bigbluebuttonbn\local\settings\renderer;
use mod_bigbluebuttonbn\local\settings\settings;
use mod_bigbluebuttonbn\local\settings\validator;

defined('MOODLE_INTERNAL') || die;

global $CFG;

$bbbsettings = new settings($ADMIN, $module, $section);

// Evaluates if recordings are enabled for the Moodle site.

// Renders settings for record feature.
$bbbsettings->bigbluebuttonbn_settings_record();
// Renders settings for import recordings.
$bbbsettings->bigbluebuttonbn_settings_importrecordings();
// Renders settings for showing recordings.
$bbbsettings->bigbluebuttonbn_settings_showrecordings();
// Renders settings for Opencast integration.
$bbbsettings->bigbluebuttonbn_settings_opencast_integration();

// Renders settings for meetings.
$bbbsettings->bigbluebuttonbn_settings_waitmoderator();
$bbbsettings->bigbluebuttonbn_settings_voicebridge();
$bbbsettings->bigbluebuttonbn_settings_preupload();
$bbbsettings->bigbluebuttonbn_settings_userlimit();
$bbbsettings->bigbluebuttonbn_settings_participants();
$bbbsettings->bigbluebuttonbn_settings_notifications();
$bbbsettings->bigbluebuttonbn_settings_muteonstart();
$bbbsettings->bigbluebuttonbn_settings_locksettings();
// Renders settings for extended capabilities.
$bbbsettings->bigbluebuttonbn_settings_extended();
// Renders settings for experimental features.
$bbbsettings->bigbluebuttonbn_settings_experimental();

$settings = null;

