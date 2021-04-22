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
 * Config BigBlueButtonBN instance guestlink access settings from the main view
 *
 * @copyright Kevin Pham <kevinpham@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_bigbluebuttonbn\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/bigbluebuttonbn/locallib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/bigbluebuttonbn/mod_form.php');

/**
 * Moodle class for mod_form.
 *
 * @copyright Kevin Pham <kevinpham@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class guestlink_access_form extends \moodleform {

    /**
     * Define (add) particular settings this activity can have.
     *
     * @return void
     */
    public function definition() {
        global $CFG, $DB, $OUTPUT, $PAGE;
        $mform = &$this->_form;

        // Guest Access URL.
        $inviteparticipantselement = $mform->createElement('text', 'guestlinkurl', get_string('view_guestlink_label', 'bigbluebuttonbn'), ['readonly' => true, 'size' => 60]);
        $mform->setType('guestlinkurl', PARAM_TEXT);
        $mform->setDefault('guestlinkurl', $this->_customdata['guestlinkurl']);

        $inviteparticipantsgroup = [];
        $inviteparticipantsgroup[] =& $inviteparticipantselement;
        $inviteparticipantsgroup[] =& $mform->createElement('button', 'guestlinkurl_copy', get_string('mod_guestlink_access_form_copy', 'bigbluebuttonbn'));
        $mform->addGroup($inviteparticipantsgroup, 'inviteparticipantsgroup', get_string('view_guestlink_label', 'bigbluebuttonbn'), array(' '), false);

        // Access Code.
        $accesscodeelement = $mform->createElement('text', 'password', '', [
            'readonly' => true,
            'size' => 29,
            'placeholder' => get_string('view_guestlink_password_no_password_set', 'bigbluebuttonbn')
        ]);
        $mform->setType('password', PARAM_TEXT);
        $mform->setDefault('password', $this->_customdata['guestlinkpassword'] ?? '');

        $accesscodegroup = [];
        $accesscodegroup[] =& $accesscodeelement;
        if (!empty($this->_customdata['guestlinkchangepassenabled'])) {
            $accesscodegroup[] =& $mform->createElement('button', 'password_clear', get_string('mod_guestlink_access_form_clear', 'bigbluebuttonbn'));
        }
        if (!empty($this->_customdata['guestlinkchangepassenabled'])) {
            $accesscodegroup[] =& $mform->createElement('button', 'password_change', get_string('mod_guestlink_access_form_generate', 'bigbluebuttonbn'));
        }
        $accesscodegroup[] =& $mform->createElement('button', 'password_copy', get_string('mod_guestlink_access_form_copy', 'bigbluebuttonbn'));
        $mform->addGroup($accesscodegroup, 'accesscodegroup', get_string('view_guestlink_password_label', 'bigbluebuttonbn'), array(' '), false);

        // Expires At.
        $defaultdateoptions = [
           'optional' => true
        ];
        $mform->addElement('date_time_selector', 'guestlinkexpiresat', get_string('view_guestlink_expires_at_label', 'bigbluebuttonbn'), $defaultdateoptions);
        $mform->setType('guestlinkexpiresat', PARAM_RAW);

        $this->add_action_buttons($cancel = false, get_string('view_guestlink_save_settings', 'bigbluebuttonbn'));

        $this->set_data([
            'guestlinkexpiresat' => $this->_customdata['guestlinkexpiresat'] ?? null
        ]);

    }

}
