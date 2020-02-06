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
 * Behat data generator for mod_bigbluebuttonbn.
 *
 * @package   mod_bigbluebuttonbn
 * @category  test
 * @copyright  2018 - present, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Laurent David (laurent@call-learning.fr)
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Behat data generator for mod_bigbluebuttonbn.
 *
 * @copyright  2018 - present, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_bigbluebuttonbn_generator extends behat_generator_base {

    /**
     * Get all entities that can be create through this behat_generator
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
                'recordings' => [
                        'datagenerator' => 'recording',
                        'required' => ['bigbluebuttonbn', 'meta_bbb-recording-name'],
                        'switchids' => ['bigbluebuttonbn' => 'bigbluebuttonbnid'],
                ],
                'log' => [
                        'datagenerator' => 'override',
                        'required' => ['bigbluebuttonbn', 'user'],
                        'switchids' => ['bigbluebuttonbn' => 'bigbluebuttonbnid', 'user' => 'userid'],
                ],
        ];
    }

    /**
     * Look up the id of a bigbluebutton activity from its name.
     *
     * @param string $bbactivityname the bigbluebutton activity name, for example 'Test meeting'.
     * @return int corresponding id.
     * @throws dml_exception
     */
    protected function get_bigbluebuttonbn_id(string $bbactivityname): int {
        global $DB;

        if (!$id = $DB->get_field('bigbluebuttonbn', 'id', ['name' => $bbactivityname])) {
            throw new Exception('There is no bigbluebuttonbn with name "' . $bbactivityname . '" does not exist');
        }
        return $id;
    }
}
