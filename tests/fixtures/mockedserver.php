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
 * Mocked BigBlueButton Server.
 *
 * This file provides sample responses for use in testing.
 *
 * @package mod_bigbluebuttonbn
 * @copyright 2021 Andrew Lyons <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bigbluebuttonbn\testing\fixtures;

use mod_bigbluebuttonbn\testing\generator\mockedserver;
// We should not have any require login or MOODLE_INTERNAL Check in this file.
// phpcs:disable moodle.Files.RequireLogin.Missing
require_once(__DIR__ . '/../../../../config.php');

defined('BEHAT_SITE_RUNNING') || redirect(new moodle_url('/'));

require_once(__DIR__ . '/../generator/mockedserver.php');

$server = new mockedserver();
$server->serve($_SERVER['PATH_INFO']);
