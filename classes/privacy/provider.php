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
 * Privacy class for requesting user data.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\privacy;

use core_privacy\local\metadata\collection;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');

/**
* Privacy class for requesting user data.
*
* @package   mod_bigbluebuttonbn
* @copyright 2018 - present, Blindside Networks Inc
* @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
* @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
*/
class provider implements
      \core_privacy\local\metadata\provider,
      \core_privacy\local\request\plugin\provider {

    // This trait must be included.
    use \core_privacy\local\legacy_polyfill;

    /**
     * Returns metadata.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function _get_metadata(collection $collection) : collection {

        $collection->add_database_table('bigbluebuttonbn_logs', [
            'userid' => 'privacy:metadata:logs:userid',
            'timecreated' => 'privacy:metadata:logs:timecreated',
            'meetingid' => 'privacy:metadata:logs:meetingid',
            'log' => 'privacy:metadata:logs:log',
            'meta' => 'privacy:metadata:logs:meta',
        ], 'privacy:metadata:logs');

        /**
         * The table bigbluebuttonbn stores only the room properties, but there may be chance
         * for some personal information to be stored in the form of metadata in the column
         * 'participants' as the rules may be set to define the role specif users would
         * have in BBB. It is fair to say that only the userid is stored, which is useless if
         * the user is removed. But if this is a concern a refactoring on the way the rules are stored
         * will be required.
         */
        $collection->add_subsystem_link(
            'core_files',
            [],
            'privacy:metadata:participants'
        );

        /**
         * Personal information has to be passed to BigBlueButton
         * this includes the user ID and user fullname
         */
        $collection->add_external_location_link('bigbluebutton_server', [
                'userid' => 'privacy:metadata:bigbluebutton_server:userid',
                'fullname' => 'privacy:metadata:bigbluebutton_server:fullname',
            ], 'privacy:metadata:bigbluebutton_server');


        return $collection;
    }

    public static function _delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);
            $DB->delete_records('bigbluebuttonbn_logs', ['bigbluebuttonbnid' => $instanceid, 'userid' => $userid]);
        }
    }

}
