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
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2010-2017 Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once(dirname(__FILE__).'/locallib.php');

if ($ADMIN->fulltree) {
    // Configuration for BigBlueButton.
    // Renders general settings.
    bigbluebutonbn_settings_general($settings);
    // Evaluates if recordings are enabled for the Moodle site.
    if ((boolean)\mod_bigbluebuttonbn\locallib\config::recordings_enabled()) {
        bigbluebutonbn_settings_recordings($settings);
    }
    // Renders settings for meetings.
    bigbluebutonbn_settings_meetings($settings);
    // Renders settings for extended capabilities.
    bigbluebutonbn_settings_extended($settings);
}
