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

use mod_bigbluebuttonbn\local\settings\settings;

defined('MOODLE_INTERNAL') || die;

global $CFG;

if ($hassiteconfig) {
    // Configuration for BigBlueButton.
    $renderer = new \mod_bigbluebuttonbn\local\settings\renderer($settings);
    // Renders general settings.
    settings::bigbluebuttonbn_settings_general($renderer);
    // Evaluates if recordings are enabled for the Moodle site.
    if (\mod_bigbluebuttonbn\local\config::recordings_enabled()) {
        // Renders settings for record feature.
        settings::bigbluebuttonbn_settings_record($renderer);
        // Renders settings for import recordings.
        settings::bigbluebuttonbn_settings_importrecordings($renderer);
        // Renders settings for showing recordings.
        settings::bigbluebuttonbn_settings_showrecordings($renderer);
    }
    // Renders settings for meetings.
    settings::bigbluebuttonbn_settings_waitmoderator($renderer);
    settings::bigbluebuttonbn_settings_voicebridge($renderer);
    settings::bigbluebuttonbn_settings_preupload($renderer);
    settings::bigbluebuttonbn_settings_preupload_manage_default_file($renderer);
    settings::bigbluebuttonbn_settings_userlimit($renderer);
    settings::bigbluebuttonbn_settings_duration($renderer);
    settings::bigbluebuttonbn_settings_participants($renderer);
    settings::bigbluebuttonbn_settings_notifications($renderer);
    settings::bigbluebuttonbn_settings_clienttype($renderer);
    settings::bigbluebuttonbn_settings_muteonstart($renderer);
    settings::bigbluebuttonbn_settings_locksettings($renderer);
    settings::bigbluebuttonbn_settings_default_messages($renderer);
    // Renders settings for extended capabilities.
    settings::bigbluebuttonbn_settings_extended($renderer);
    // Renders settings for experimental features.
    settings::bigbluebuttonbn_settings_experimental($renderer);
}
