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
 * Mobile output class for bigbluebuttonbn
 *
 * @package    mod_bigbluebuttonbn
 * @copyright  2018 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\output;

defined('MOODLE_INTERNAL') || die();

use context_module;
use mod_bigbluebuttonbn_external;
require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');

/**
 * Mobile output class for bigbluebuttonbn
 *
 * @package    mod_bigbluebuttonbn
 * @copyright  2018 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class mobile {

    /**
     * Returns the bigbluebuttonbn course view for the mobile app.
     * @param  array $args Arguments from tool_mobile_get_content WS
     *
     * @return array       HTML, javascript and otherdata
     */
    public static function mobile_course_view($args) {

        global $OUTPUT, $USER, $DB, $SESSION, $CFG;

        $args = (object) $args;
        $issues = array();
        $showget = true;

        $viewinstance = bigbluebuttonbn_view_validator($args->cmid, null);
        if (!$viewinstance) {
            $issues[] = get_string('view_error_url_missing_parameters', 'bigbluebuttonbn');
            // Only show error in mobile.
            $showget = false;
        }

        $cm = $viewinstance['cm'];
        $course = $viewinstance['course'];
        $bigbluebuttonbn = $viewinstance['bigbluebuttonbn'];
        $context = context_module::instance($cm->id);

        // Add view event.
        bigbluebuttonbn_event_log(\mod_bigbluebuttonbn\event\events::$events['view'], $bigbluebuttonbn);

        // Additional info related to the course.
        $bbbsession['course'] = $course;
        $bbbsession['coursename'] = $course->fullname;
        $bbbsession['cm'] = $cm;
        $bbbsession['bigbluebuttonbn'] = $bigbluebuttonbn;

        // Set common variables for session.
        $bbbsession = \mod_bigbluebuttonbn\locallib\mobileview::bigbluebuttonbn_view_bbbsession_set($context, $bbbsession);

        // Validates if the BigBlueButton server is working.
        $serverversion = bigbluebuttonbn_get_server_version();
        if (is_null($serverversion)) {
            if ($bbbsession['administrator']) {
                $issues[] = get_string('view_error_unable_join', 'bigbluebuttonbn',
                    $CFG->wwwroot.'/admin/settings.php?section=modsettingbigbluebuttonbn');
            }
            if ($bbbsession['moderator']) {
                $issues[] = get_string('view_error_unable_join_teacher', 'bigbluebuttonbn',
                    $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course);
            }

            $issues[] = get_string('view_error_unable_join_student', 'bigbluebuttonbn',
                $CFG->wwwroot.'/course/view.php?id='.$bigbluebuttonbn->course);

            // Only show error in mobile.
            $showget = false;
        }
        $bbbsession['serverversion'] = (string) $serverversion;

        // Mark viewed by user (if required).
        $completion = new \completion_info($course);
        $completion->set_module_viewed($cm);

        // Validate if the user is in a role allowed to join.
        if (!has_capability('moodle/category:manage', $context) &&
            !has_capability('mod/bigbluebuttonbn:join', $context)) {

            // TODO:: Check error message.
            // Only show error in mobile.
            $showget = false;
        }

        // TODO:: Check urls required for mobile app.
        // Operation URLs.
        $bbbsession['bigbluebuttonbnURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/view.php?id=' . $bbbsession['cm']->id;
        $bbbsession['logoutURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_view.php?action=logout&id='.$args->cmid .
            '&bn=' . $bbbsession['bigbluebuttonbn']->id;
        $bbbsession['recordingReadyURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_broker.php?action=recording_' .
            'ready&bigbluebuttonbn=' . $bbbsession['bigbluebuttonbn']->id;
        $bbbsession['meetingEventsURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_broker.php?action=meeting' .
            '_events&bigbluebuttonbn=' . $bbbsession['bigbluebuttonbn']->id;
        $bbbsession['joinURL'] = $CFG->wwwroot . '/mod/bigbluebuttonbn/bbb_view.php?action=join&id=' . $args->cmid .
            '&bn=' . $bbbsession['bigbluebuttonbn']->id;


        // Initialize session variable used across views.
        $SESSION->bigbluebuttonbn_bbbsession = $bbbsession;

        // TODO:: Implement logic of bbb_view for join to session.


        $data = array(
            'bigbluebuttonbn' => $bigbluebuttonbn,
            'bbbsession' => $bbbsession['joinURL'],
            'showget' => $showget && count($issues) > 0,
            'issues' => $issues,
            'issue' => $issues[0],
            'numissues' => count($issues),
            'cmid' => $cm->id,
            'courseid' => $args->courseid
        );

        return array(
            'templates' => array(
                array(
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_bigbluebuttonbn/mobile_view_page', $data),
                ),
            ),
            'javascript' => '',
            'otherdata' => '',
            'files' => $issues
        );
    }

    /**
     * Returns the bigbluebuttonbn issues view for the mobile app.
     * @param  array $args Arguments from tool_mobile_get_content WS
     *
     * @return array       HTML, javascript and otherdata
     */
    public static function mobile_issues_view($args) {
        global $OUTPUT, $USER, $DB;

        $args = (object) $args;
        $cm = get_coursemodule_from_id('bigbluebuttonbn', $args->cmid);

        // Capabilities check.
        require_login($args->courseid , false , $cm, true, true);

        $context = context_module::instance($cm->id);

        require_capability ('mod/bigbluebuttonbn:view', $context);
        if ($args->userid != $USER->id) {
            require_capability('mod/bigbluebuttonbn:manage', $context);
        }
        $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $cm->instance));

        // Get bigbluebuttonbns from external (taking care of exceptions).
        try {
            $issued = mod_bigbluebuttonbn_external::issue_bigbluebuttonbn($cm->instance);
            $bigbluebuttonbns = mod_bigbluebuttonbn_external::get_issued_bigbluebuttonbns($cm->instance);
            $issues = array_values($bigbluebuttonbns['issues']); // Make it mustache compatible.
        } catch (Exception $e) {
            $issues = array();
        }

        $data = array(
            'issues' => $issues
        );

        return array(
            'templates' => array(
                array(
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_bigbluebuttonbn/mobile_view_issues', $data),
                ),
            ),
            'javascript' => '',
            'otherdata' => ''
        );
    }
}
