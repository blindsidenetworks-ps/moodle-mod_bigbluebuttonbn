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
 * The mod_bigbluebuttonbn/bigbluebutton/recordings/base.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\bigbluebutton\recordings;

use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Abstract base class for recordings.
 *
 * Utility class for recording helper
 * @package mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

abstract class base {

    /** @var int RECORDING_HEADLESS integer set to 1 defines that the activity used to create the recording no longer exists */
    public const RECORDING_HEADLESS = 1;
    /** @var int RECORDING_IMPORTED integer set to 1 defines that the recording is not the original but an imported one */
    public const RECORDING_IMPORTED = 1;

    /** @var int INCLUDE_IMPORTED_RECORDINGS boolean set to true defines that the list should include imported recordings */
    public const INCLUDE_IMPORTED_RECORDINGS = true;

    /** @var stdClass mod_bigbluebuttonbn instance. */
    protected $bigbluebuttonbn;

    abstract function create();
    abstract function read();
    abstract function update();
    abstract function delete();
 }