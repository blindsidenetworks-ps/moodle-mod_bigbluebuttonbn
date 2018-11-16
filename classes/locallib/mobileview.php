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
 * The mod_bigbluebuttonbn locallib/mobileview.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2018 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bigbluebuttonbn\locallib;

require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');

/**
 * Methods used to render view BBB in mobile.
 *
 * @copyright 2018 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobileview {

    /**
     * Setup the bbbsession variable that is used all accross the plugin.
     *
     * @param object $context
     * @param array $bbbsession
     * @return array $bbbsession
     */
    public static function bigbluebuttonbn_view_bbbsession_set($context, &$bbbsession) {

        global $CFG, $USER;
        // User data.
        $bbbsession['username'] = fullname($USER);
        $bbbsession['userID'] = $USER->id;
        // User roles.
        $bbbsession['administrator'] = is_siteadmin($bbbsession['userID']);
        $participantlist = bigbluebuttonbn_get_participant_list($bbbsession['bigbluebuttonbn'], $context);
        $bbbsession['moderator'] = bigbluebuttonbn_is_moderator($context, $participantlist);
        $bbbsession['managerecordings'] = ($bbbsession['administrator']
            || has_capability('mod/bigbluebuttonbn:managerecordings', $context));
        $bbbsession['importrecordings'] = ($bbbsession['managerecordings']);
        // Server data.
        $bbbsession['modPW'] = $bbbsession['bigbluebuttonbn']->moderatorpass;
        $bbbsession['viewerPW'] = $bbbsession['bigbluebuttonbn']->viewerpass;
        // Database info related to the activity.
        $bbbsession['meetingid'] = $bbbsession['bigbluebuttonbn']->meetingid.'-'.$bbbsession['course']->id.'-'.
            $bbbsession['bigbluebuttonbn']->id;
        $bbbsession['meetingname'] = $bbbsession['bigbluebuttonbn']->name;
        $bbbsession['meetingdescription'] = $bbbsession['bigbluebuttonbn']->intro;
        // Extra data for setting up the Meeting.
        $bbbsession['userlimit'] = intval((int)\mod_bigbluebuttonbn\locallib\config::get('userlimit_default'));
        if ((boolean)\mod_bigbluebuttonbn\locallib\config::get('userlimit_editable')) {
            $bbbsession['userlimit'] = intval($bbbsession['bigbluebuttonbn']->userlimit);
        }
        $bbbsession['voicebridge'] = $bbbsession['bigbluebuttonbn']->voicebridge;
        if ($bbbsession['bigbluebuttonbn']->voicebridge > 0) {
            $bbbsession['voicebridge'] = 70000 + $bbbsession['bigbluebuttonbn']->voicebridge;
        }
        $bbbsession['wait'] = $bbbsession['bigbluebuttonbn']->wait;
        $bbbsession['record'] = $bbbsession['bigbluebuttonbn']->record;
        $bbbsession['welcome'] = $bbbsession['bigbluebuttonbn']->welcome;
        if (!isset($bbbsession['welcome']) || $bbbsession['welcome'] == '') {
            $bbbsession['welcome'] = get_string('mod_form_field_welcome_default', 'bigbluebuttonbn');
        }
        if ($bbbsession['bigbluebuttonbn']->record) {
            $bbbsession['welcome'] .= '<br><br>'.get_string('bbbrecordwarning', 'bigbluebuttonbn');
        }
        $bbbsession['openingtime'] = $bbbsession['bigbluebuttonbn']->openingtime;
        $bbbsession['closingtime'] = $bbbsession['bigbluebuttonbn']->closingtime;
        // Additional info related to the course.
        $bbbsession['context'] = $context;
        // Metadata (origin).
        $bbbsession['origin'] = 'Moodle';
        $bbbsession['originVersion'] = $CFG->release;
        $parsedurl = parse_url($CFG->wwwroot);
        $bbbsession['originServerName'] = $parsedurl['host'];
        $bbbsession['originServerUrl'] = $CFG->wwwroot;
        $bbbsession['originServerCommonName'] = '';
        $bbbsession['originTag'] = 'moodle-mod_bigbluebuttonbn ('.get_config('mod_bigbluebuttonbn', 'version').')';
        $bbbsession['bnserver'] = bigbluebuttonbn_is_bn_server();
        // Setting for clienttype, assign flash if not enabled, or default if not editable.
        $bbbsession['clienttype'] = \mod_bigbluebuttonbn\locallib\config::get('clienttype_default');
        if (\mod_bigbluebuttonbn\locallib\config::get('clienttype_editable')) {
            $bbbsession['clienttype'] = $bbbsession['bigbluebuttonbn']->clienttype;
        }
        if (!\mod_bigbluebuttonbn\locallib\config::clienttype_enabled()) {
            $bbbsession['clienttype'] = BIGBLUEBUTTON_CLIENTTYPE_FLASH;
        }

        return($bbbsession);
    }

}