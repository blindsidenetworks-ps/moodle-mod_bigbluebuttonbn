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
 * URL external functions and service definitions.
 *
 * @package   mod_bigbluebuttonbn
 * @category  external
 * @copyright 2018 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

defined('MOODLE_INTERNAL') || die;

$functions = array(

    'mod_bigbluebuttonbn_view_bigbluebuttonbn' => array(
        'classname'     => 'mod_bigbluebuttonbn_external',
        'methodname'    => 'view_bigbluebuttonbn',
        'description'   => 'Trigger the course module viewed event and update the module completion status.',
        'type'          => 'write',
        'capabilities'  => 'mod/bigbluebuttonbn:view',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_bigbluebuttonbn_get_bigbluebuttonbns_by_courses' => array(
        'classname'     => 'mod_bigbluebuttonbn_external',
        'methodname'    => 'get_bigbluebuttonbns_by_courses',
        'description'   => 'Returns a list of bigbluebuttonbns in a provided list of courses, if no list is provided
                            all bigbluebuttonbns that the user can view will be returned.',
        'type'          => 'read',
        'capabilities'  => 'mod/bigbluebuttonbn:view',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
);
