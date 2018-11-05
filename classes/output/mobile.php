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

        $showget = true;
        if ($bigbluebuttonbn->requiredtime && !has_capability('mod/bigbluebuttonbn:manage', $context)) {
            if (bigbluebuttonbn_get_course_time($bigbluebuttonbn->course) < ($bigbluebuttonbn->requiredtime * 60)) {
                $showget = false;
            }
        }

        $bigbluebuttonbn->name = format_string($bigbluebuttonbn->name);
        list($bigbluebuttonbn->intro, $bigbluebuttonbn->introformat) =
                        external_format_text($bigbluebuttonbn->intro, $bigbluebuttonbn->introformat, $context->id,
                                                'mod_bigbluebuttonbn', 'intro');
        $data = array(
            'bigbluebuttonbn' => $bigbluebuttonbn,
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
