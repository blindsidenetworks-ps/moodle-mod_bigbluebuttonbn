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
 * mod_bigbluebuttonbn data generator
 *
 * @package    mod_bigbluebuttonbn
 * @category   test
 * @copyright  2018 - present, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

defined('MOODLE_INTERNAL') || die();

/**
 * bigbluebuttonbn module data generator
 *
 * @package    mod_bigbluebuttonbn
 * @category   test
 * @copyright  2018 - present, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class mod_bigbluebuttonbn_generator extends testing_module_generator {

    public function create_instance($record = null, array $options = null) {
        $record = (object)(array)$record;

        if (!isset($record->type)) {
            $record->type = 0;
        }
        if (!isset($record->meetingid)) {
            $record->meetingid = sha1(rand());
        }
        if (!isset($record->record)) {
            $record->record = true;
        }

        if (!isset($record->moderatorpass)) {
            $record->moderatorpass = 'mp';
        }

        if (!isset($record->viewerpass)) {
            $record->viewerpass = 'vp';
        }

        if (!isset($record->participants)) {
            $record->participants = '{}';
        }

        if (!isset($record->timecreated)) {
            $record->timecreated = time();
        }

        if (!isset($record->timemodified)) {
            $record->timemodified = time();
        }

        return parent::create_instance($record, (array)$options);
    }
}
