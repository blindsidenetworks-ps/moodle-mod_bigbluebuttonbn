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
 * Config all BigBlueButtonBN instances in this course.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 */

use mod_bigbluebuttonbn\local\bbb_constants;
use mod_bigbluebuttonbn\local\bigbluebutton;
use mod_bigbluebuttonbn\local\helpers\roles;
use mod_bigbluebuttonbn\local\view;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Moodle class for mod_form.
 *
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_bigbluebuttonbn_mod_form extends moodleform_mod {

    /**
     * Define (add) particular settings this activity can have.
     *
     * @return void
     */
    public function definition() {
        global $CFG, $DB, $OUTPUT, $PAGE;
        $mform = &$this->_form;

        // Validates if the BigBlueButton server is running.
        $serverversion = bigbluebutton::bigbluebuttonbn_get_server_version();
        if (is_null($serverversion)) {
            throw new moodle_exception('general_error_unable_connect', 'bigbluebuttonbn',
                $CFG->wwwroot . '/admin/settings.php?section=modsettingbigbluebuttonbn');
        }
        $bigbluebuttonbn = null;
        if ($this->current->id) {
            $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $this->current->id), '*', MUST_EXIST);
        }
        // UI configuration options.
        $cfg = \mod_bigbluebuttonbn\local\config::get_options();

        // Get only those that are allowed.
        // TODO: check as here it seems more logical to get this through: $this->_course.
        $course = get_course($this->current->course);
        $context = context_course::instance($course->id);

        $instancetyperofiles = $this->get_instance_type_profiles();
        $this->bigbluebuttonbn_mform_add_block_profiles($mform, $instancetyperofiles);
        // Data for participant selection.
        $participantlist = roles::bigbluebuttonbn_get_participant_list($bigbluebuttonbn, $context);
        // Add block 'General'.
        $this->bigbluebuttonbn_mform_add_block_general($mform, $cfg);
        // Add block 'Room'.
        $this->bigbluebuttonbn_mform_add_block_room($mform, $cfg);
        // Add block 'Lock'.
        $this->bigbluebuttonbn_mform_add_block_locksettings($mform, $cfg);
        // Add block 'Preuploads'.
        $this->bigbluebuttonbn_mform_add_block_preuploads($mform, $cfg);
        // Add block 'Participant List'.
        $this->bigbluebuttonbn_mform_add_block_user_role_mapping($mform, $participantlist);
        // Add block 'Schedule'.
        $this->bigbluebuttonbn_mform_add_block_schedule($mform, $this->current);
        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();
        // Add standard buttons, common to all modules.
        $this->add_action_buttons();

        $jsvars = [
            'instanceTypeDefault' => array_keys($instancetyperofiles)[0],
        ];;

        // Now add the instance type profiles to the form as a html hidden field.
        $mform->addElement('html', html_writer::div('', 'd-none', [
            'data-profile-types' => json_encode($instancetyperofiles),
            'data-participant-data' => json_encode(roles::bigbluebuttonbn_get_participant_data($context, $bigbluebuttonbn)),
        ]));

        $PAGE->requires->js_call_amd('mod_bigbluebuttonbn/modform', 'init', [$jsvars]);
    }

    /**
     * Get instance type profile.
     *
     * @return array|array[]
     * @throws coding_exception
     * @throws moodle_exception
     */
    protected function get_instance_type_profiles() {
        // Add profile data here instead of passing it by parameters.
        $context = context_course::instance($this->_course->id);
        $instancetyperofiles = bigbluebutton::bigbluebuttonbn_get_instance_type_profiles_create_allowed(
            has_capability('mod/bigbluebuttonbn:meeting', $context),
            has_capability('mod/bigbluebuttonbn:recording', $context)
        );
        // If none is allowed, fail and return.
        if (empty($instancetyperofiles)) {
            global $CFG;
            // Also check module context for those that are allowed.
            $contextm = context_module::instance($this->_cm->id);
            $instancetyperofiles = bigbluebutton::bigbluebuttonbn_get_instance_type_profiles_create_allowed(
                has_capability('mod/bigbluebuttonbn:meeting', $contextm),
                has_capability('mod/bigbluebuttonbn:recording', $contextm)
            );
            // If still none is allowed, fail and return.
            if (empty($instancetyperofiles)) {
                throw new moodle_exception('general_error_not_allowed_to_create_instances', 'bigbluebuttonbn',
                    $CFG->wwwroot . '/admin/settings.php?section=modsettingbigbluebuttonbn');
            }
        }
        return $instancetyperofiles;
    }

    /**
     * Prepare the attachment for being stored.
     *
     * @param array $defaultvalues
     * @return void
     */
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);

        // Completion: tick by default if completion attendance settings is set to 1 or more.
        $defaultvalues['completionattendanceenabled'] = 0;
        if (!empty($defaultvalues['completionattendance'])) {
            $defaultvalues['completionattendanceenabled'] = 1;
        }
        // Check if we are Editing an existing instance.
        if ($this->current->instance) {
            // Pre-uploaded presentation: copy existing files into draft area.
            try {
                $draftitemid = file_get_submitted_draft_itemid('presentation');
                file_prepare_draft_area($draftitemid, $this->context->id, 'mod_bigbluebuttonbn', 'presentation', 0,
                    array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1, 'mainfile' => true)
                );
                $defaultvalues['presentation'] = $draftitemid;
            } catch (Exception $e) {
                debugging('Presentation could not be loaded: ' . $e->getMessage(), DEBUG_DEVELOPER);
                return;
            }
            // Completion: tick if completion attendance settings is set to 1 or more.
            $defaultvalues['completionattendanceenabled'] = 0;
            if (!empty($this->current->completionattendance)) {
                $defaultvalues['completionattendanceenabled'] = 1;
            }
        }
    }

    /**
     * Validates the data processed by the form.
     *
     * @param array $data
     * @param array $files
     * @return void
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (isset($data['openingtime']) && isset($data['closingtime'])) {
            if ($data['openingtime'] != 0 && $data['closingtime'] != 0 &&
                $data['closingtime'] < $data['openingtime']) {
                $errors['closingtime'] = get_string('bbbduetimeoverstartingtime', 'bigbluebuttonbn');
            }
        }
        if (isset($data['voicebridge'])) {
            if (!bigbluebutton::bigbluebuttonbn_voicebridge_unique($data['instance'], $data['voicebridge'])) {
                $errors['voicebridge'] = get_string('mod_form_field_voicebridge_notunique_error', 'bigbluebuttonbn');
            }
        }
        return $errors;
    }

    /**
     * Add elements for setting the custom completion rules.
     *
     * @return array List of added element names, or names of wrapping group elements.
     * @category completion
     */
    public function add_completion_rules() {
        $mform = $this->_form;
        if (!(boolean) \mod_bigbluebuttonbn\local\config::get('meetingevents_enabled')) {
            return [];
        }

        // Elements for completion by Attendance.
        $attendance['grouplabel'] = get_string('completionattendancegroup', 'bigbluebuttonbn');
        $attendance['rulelabel'] = get_string('completionattendance', 'bigbluebuttonbn');
        $attendance['group'] = [
            $mform->createElement('advcheckbox', 'completionattendanceenabled', '', $attendance['rulelabel'] . '&nbsp;'),
            $mform->createElement('text', 'completionattendance', '', ['size' => 3]),
            $mform->createElement('static', 'completionattendanceunit', ' ', get_string('minutes', 'bigbluebuttonbn'))
        ];
        $mform->setType('completionattendance', PARAM_INT);
        $mform->addGroup($attendance['group'], 'completionattendancegroup', $attendance['grouplabel'], [' '], false);
        $mform->addHelpButton('completionattendancegroup', 'completionattendancegroup', 'bigbluebuttonbn');
        $mform->disabledIf('completionattendancegroup', 'completionview', 'notchecked');
        $mform->disabledIf('completionattendance', 'completionattendanceenabled', 'notchecked');

        // Elements for completion by Engagement.
        $engagement['grouplabel'] = get_string('completionengagementgroup', 'bigbluebuttonbn');
        $engagement['chatlabel'] = get_string('completionengagementchats', 'bigbluebuttonbn');
        $engagement['talklabel'] = get_string('completionengagementtalks', 'bigbluebuttonbn');
        $engagement['raisehand'] = get_string('completionengagementraisehand', 'bigbluebuttonbn');
        $engagement['pollvotes'] = get_string('completionengagementpollvotes', 'bigbluebuttonbn');
        $engagement['emojis'] = get_string('completionengagementemojis', 'bigbluebuttonbn');
        $engagement['group'] = [
            $mform->createElement('advcheckbox', 'completionengagementchats', '', $engagement['chatlabel'] . '&nbsp;&nbsp;'),
            $mform->createElement('advcheckbox', 'completionengagementtalks', '', $engagement['talklabel'] . '&nbsp;&nbsp;'),
            $mform->createElement('advcheckbox', 'completionengagementraisehand', '', $engagement['raisehand'] . '&nbsp;&nbsp;'),
            $mform->createElement('advcheckbox', 'completionengagementpollvotes', '', $engagement['pollvotes'] . '&nbsp;&nbsp;'),
            $mform->createElement('advcheckbox', 'completionengagementemojis', '', $engagement['emojis'] . '&nbsp;&nbsp;'),
        ];
        $mform->addGroup($engagement['group'], 'completionengagementgroup', $engagement['grouplabel'], [' '], false);
        $mform->addHelpButton('completionengagementgroup', 'completionengagementgroup', 'bigbluebuttonbn');
        $mform->disabledIf('completionengagementgroup', 'completionview', 'notchecked');

        return ['completionattendancegroup', 'completionengagementgroup'];
    }

    /**
     * Called during validation to see whether some module-specific completion rules are selected.
     *
     * @param array $data Input data not yet validated.
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled($data) {
        return (!empty($data['completionattendanceenabled']) && $data['completionattendance'] != 0);
    }

    /**
     * Allows module to modify the data returned by form get_data().
     * This method is also called in the bulk activity completion form.
     *
     * Only available on moodleform_mod.
     *
     * @param stdClass $data the form data to be modified.
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        // Turn off completion settings if the checkboxes aren't ticked.
        if (!empty($data->completionunlocked)) {
            $autocompletion = !empty($data->completion) && $data->completion == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->completionattendanceenabled) || !$autocompletion) {
                $data->completionattendance = 0;
            }
        }
    }

    /**
     * Function for showing the block for selecting profiles.
     *
     * @param object $mform
     * @param array $profiles
     * @return void
     */
    private function bigbluebuttonbn_mform_add_block_profiles(&$mform, $profiles) {
        if ((boolean) \mod_bigbluebuttonbn\local\config::recordings_enabled()) {
            $mform->addElement('select', 'type', get_string('mod_form_field_instanceprofiles', 'bigbluebuttonbn'),
                bigbluebutton::bigbluebuttonbn_get_instance_profiles_array($profiles));
            $mform->addHelpButton('type', 'mod_form_field_instanceprofiles', 'bigbluebuttonbn');
        }
    }

    /**
     * Function for showing the block for general settings.
     *
     * @param object $mform
     * @param array $cfg
     * @return void
     */
    private function bigbluebuttonbn_mform_add_block_general(&$mform, $cfg) {
        global $CFG;
        $mform->addElement('header', 'general', get_string('mod_form_block_general', 'bigbluebuttonbn'));
        $mform->addElement('text', 'name', get_string('mod_form_field_name', 'bigbluebuttonbn'),
            'maxlength="64" size="32"');
        $mform->setType('name', empty($CFG->formatstringstriptags) ? PARAM_CLEANHTML : PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_intro_elements(get_string('mod_form_field_intro', 'bigbluebuttonbn'));
        $mform->setAdvanced('introeditor');
        $mform->setAdvanced('showdescription');
        if ($cfg['sendnotifications_enabled']) {
            $field = ['type' => 'checkbox', 'name' => 'notification', 'data_type' => PARAM_INT,
                'description_key' => 'mod_form_field_notification'];
            $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
                $field['description_key'], 0);
        }
    }

    /**
     * Function for showing details of the room settings for the room.
     *
     * @param object $mform
     * @param array $cfg
     * @return void
     */
    private function bigbluebuttonbn_mform_add_block_room_room(&$mform, $cfg) {
        $field = ['type' => 'textarea', 'name' => 'welcome', 'data_type' => PARAM_CLEANHTML,
            'description_key' => 'mod_form_field_welcome'];
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['welcome_default'], ['wrap' => 'virtual', 'rows' => 5, 'cols' => '60']);
        $field = ['type' => 'hidden', 'name' => 'voicebridge', 'data_type' => PARAM_INT,
            'description_key' => null];
        if ($cfg['voicebridge_editable']) {
            $field['type'] = 'text';
            $field['data_type'] = PARAM_TEXT;
            $field['description_key'] = 'mod_form_field_voicebridge';
            $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
                $field['description_key'], 0, ['maxlength' => 4, 'size' => 6],
                ['message' => get_string('mod_form_field_voicebridge_format_error', 'bigbluebuttonbn'),
                    'type' => 'numeric', 'rule' => '####', 'validator' => 'server']
            );
        } else {
            $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
                $field['description_key'], 0, ['maxlength' => 4, 'size' => 6]);
        }
        $field = ['type' => 'hidden', 'name' => 'wait', 'data_type' => PARAM_INT, 'description_key' => null];
        if ($cfg['waitformoderator_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_wait';
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['waitformoderator_default']);
        $field = ['type' => 'hidden', 'name' => 'userlimit', 'data_type' => PARAM_INT, 'description_key' => null];
        if ($cfg['userlimit_editable']) {
            $field['type'] = 'text';
            $field['data_type'] = PARAM_TEXT;
            $field['description_key'] = 'mod_form_field_userlimit';
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['userlimit_default']);
        $field = ['type' => 'hidden', 'name' => 'record', 'data_type' => PARAM_INT, 'description_key' => null];
        if ($cfg['recording_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_record';
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['recording_default']);

        // Record all from start and hide button.
        $field = ['type' => 'hidden', 'name' => 'recordallfromstart', 'data_type' => PARAM_INT, 'description_key' => null];
        if ($cfg['recording_all_from_start_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_recordallfromstart';
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['recording_all_from_start_default']);

        $field = ['type' => 'hidden', 'name' => 'recordhidebutton', 'data_type' => PARAM_INT, 'description_key' => null];
        if ($cfg['recording_hide_button_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_recordhidebutton';
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['recording_hide_button_default']);

        $mform->disabledIf('recordallfromstart', 'record', $condition = 'notchecked', $value = '0');
        $mform->disabledIf('recordhidebutton', 'record', $condition = 'notchecked', $value = '0');
        $mform->disabledIf('recordhidebutton', 'recordallfromstart', $condition = 'notchecked', $value = '0');
        // End Record all from start and hide button.

        $field = ['type' => 'hidden', 'name' => 'muteonstart', 'data_type' => PARAM_INT, 'description_key' => null];
        if ($cfg['muteonstart_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_muteonstart';
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['muteonstart_default']);

    }

    /**
     * Function for showing details of the lock settings for the room.
     *
     * @param object $mform
     * @param array $cfg
     * @return void
     */
    private function bigbluebuttonbn_mform_add_block_locksettings(&$mform, $cfg) {
        $mform->addElement('header', 'lock', get_string('mod_form_locksettings', 'bigbluebuttonbn'));

        $locksettings = false;

        $field = ['type' => 'hidden', 'name' => 'disablecam', 'data_type' => PARAM_INT, 'description_key' => null];
        if ($cfg['disablecam_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_disablecam';
            $locksettings = true;
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['disablecam_default']);

        $field = ['type' => 'hidden', 'name' => 'disablemic', 'data_type' => PARAM_INT, 'description_key' => null];
        if ($cfg['disablemic_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_disablemic';
            $locksettings = true;
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['disablemic_default']);

        $field = ['type' => 'hidden', 'name' => 'disableprivatechat', 'data_type' => PARAM_INT, 'description_key' => null];
        if ($cfg['disableprivatechat_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_disableprivatechat';
            $locksettings = true;
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['disableprivatechat_default']);

        $field = ['type' => 'hidden', 'name' => 'disablepublicchat', 'data_type' => PARAM_INT, 'description_key' => null];
        if ($cfg['disablepublicchat_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_disablepublicchat';
            $locksettings = true;
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['disablepublicchat_default']);

        $field = ['type' => 'hidden', 'name' => 'disablenote', 'data_type' => PARAM_INT, 'description_key' => null];
        if ($cfg['disablenote_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_disablenote';
            $locksettings = true;
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['disablenote_default']);

        $field = ['type' => 'hidden', 'name' => 'hideuserlist', 'data_type' => PARAM_INT, 'description_key' => null];
        if ($cfg['hideuserlist_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_hideuserlist';
            $locksettings = true;
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['hideuserlist_default']);

        $field = ['type' => 'hidden', 'name' => 'lockedlayout', 'data_type' => PARAM_INT, 'description_key' => null];
        if ($cfg['lockedlayout_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_lockedlayout';
            $locksettings = true;
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['lockedlayout_default']);

        $field = ['type' => 'hidden', 'name' => 'lockonjoin', 'data_type' => PARAM_INT, 'description_key' => null];
        if ($cfg['lockonjoin_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_lockonjoin';
            $locksettings = true;
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['lockonjoin_default']);

        $field = ['type' => 'hidden', 'name' => 'lockonjoinconfigurable', 'data_type' => PARAM_INT, 'description_key' => null];
        if ($cfg['lockonjoinconfigurable_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_lockonjoinconfigurable';
            $locksettings = true;
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['lockonjoinconfigurable_default']);

        // Output message if no settings.
        if (!$locksettings) {
            $field = ['type' => 'static', 'name' => 'no_locksettings',
                'defaultvalue' => get_string('mod_form_field_nosettings', 'bigbluebuttonbn')];
            $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], null, null,
                $field['defaultvalue']);
        }
    }

    /**
     * Function for showing details of the recording settings for the room.
     *
     * @param object $mform
     * @param array $cfg
     * @return void
     */
    private function bigbluebuttonbn_mform_add_block_room_recordings(&$mform, $cfg) {
        $recordingsettings = false;
        $field = ['type' => 'hidden', 'name' => 'recordings_html', 'data_type' => PARAM_INT,
            'description_key' => null];
        if ($cfg['recordings_html_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_recordings_html';
            $recordingsettings = true;
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['recordings_html_default']);
        $field = ['type' => 'hidden', 'name' => 'recordings_deleted', 'data_type' => PARAM_INT,
            'description_key' => null];
        if ($cfg['recordings_deleted_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_recordings_deleted';
            $recordingsettings = true;
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['recordings_deleted_default']);
        $field = ['type' => 'hidden', 'name' => 'recordings_imported', 'data_type' => PARAM_INT,
            'description_key' => null];
        if ($cfg['importrecordings_enabled'] && $cfg['recordings_imported_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_recordings_imported';
            $recordingsettings = true;
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['recordings_imported_default']);
        $field = ['type' => 'hidden', 'name' => 'recordings_preview', 'data_type' => PARAM_INT,
            'description_key' => null];
        if ($cfg['recordings_preview_editable']) {
            $field['type'] = 'checkbox';
            $field['description_key'] = 'mod_form_field_recordings_preview';
            $recordingsettings = true;
        }
        $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], $field['data_type'],
            $field['description_key'], $cfg['recordings_preview_default']);

        if (!$recordingsettings) {
            $field = ['type' => 'static', 'name' => 'no_recordings',
                'defaultvalue' => get_string('mod_form_field_nosettings', 'bigbluebuttonbn')];
            $this->bigbluebuttonbn_mform_add_element($mform, $field['type'], $field['name'], null, null,
                $field['defaultvalue']);
        }
    }

    /**
     * Function for showing the block for room settings.
     *
     * @param object $mform
     * @param array $cfg
     * @return void
     */
    private function bigbluebuttonbn_mform_add_block_room(&$mform, $cfg) {
        if ($cfg['voicebridge_editable'] || $cfg['waitformoderator_editable'] ||
            $cfg['userlimit_editable'] || $cfg['recording_editable']) {
            $mform->addElement('header', 'room', get_string('mod_form_block_room', 'bigbluebuttonbn'));
            $this->bigbluebuttonbn_mform_add_block_room_room($mform, $cfg);
        }
        if ($cfg['recordings_html_editable'] || $cfg['recordings_deleted_editable'] ||
            $cfg['recordings_imported_editable'] || $cfg['recordings_preview_editable']) {
            $mform->addElement('header', 'recordings', get_string('mod_form_block_recordings', 'bigbluebuttonbn'));
            $this->bigbluebuttonbn_mform_add_block_room_recordings($mform, $cfg);
        }
    }

    /**
     * Function for showing the block for preuploaded presentation.
     *
     * @param object $mform
     * @param array $cfg
     * @return void
     */
    private function bigbluebuttonbn_mform_add_block_preuploads(&$mform, $cfg) {
        if ($cfg['preuploadpresentation_enabled']) {
            $mform->addElement('header', 'preuploadpresentation',
                get_string('mod_form_block_presentation', 'bigbluebuttonbn'));
            $mform->setExpanded('preuploadpresentation');
            $filemanageroptions = array();
            $filemanageroptions['accepted_types'] = '*';
            $filemanageroptions['maxbytes'] = 0;
            $filemanageroptions['subdirs'] = 0;
            $filemanageroptions['maxfiles'] = 1;
            $filemanageroptions['mainfile'] = true;
            $mform->addElement('filemanager', 'presentation', get_string('selectfiles'),
                null, $filemanageroptions);
        }
    }

    /**
     * Function for showing the block for setting participant roles.
     *
     * @param object $mform
     * @param array $participantlist
     * @return void
     */
    private function bigbluebuttonbn_mform_add_block_user_role_mapping(&$mform, $participantlist) {
        global $OUTPUT;
        $participantselection = roles::bigbluebuttonbn_get_participant_selection_data();
        $mform->addElement('header', 'permissions', get_string('mod_form_block_participants', 'bigbluebuttonbn'));
        $mform->setExpanded('permissions');
        $mform->addElement('hidden', 'participants', json_encode($participantlist));
        $mform->setType('participants', PARAM_TEXT);
        $selectiontype = new single_select(new moodle_url(qualified_me()),
            'bigbluebuttonbn_participant_selection_type',
            $participantselection['type_options'],
            $participantselection['type_selected']);
        $selectionparticipants = new single_select(new moodle_url(qualified_me()),
            'bigbluebuttonbn_participant_selection',
            $participantselection['options'],
            $participantselection['selected']);
        $action = new single_button(new moodle_url(qualified_me()),
            get_string('mod_form_field_participant_list_action_add', 'bigbluebuttonbn'),
            'post',
            false,
            ['name' => 'bigbluebuttonbn_participant_selection_add']
        );
        $pformcontext = [
            'selectionType' => $selectiontype->export_for_template($OUTPUT),
            'selectionParticipant' => $selectionparticipants->export_for_template($OUTPUT),
            'action' => $action->export_for_template($OUTPUT),
        ];
        $html = $OUTPUT->render_from_template('mod_bigbluebuttonbn/participant_form', $pformcontext);
        $mform->addElement('static', 'static_participant_list',
            get_string('mod_form_field_participant_list', 'bigbluebuttonbn'), $html);
    }

    /**
     * Function for showing the block for integration with the calendar.
     *
     * @param object $mform
     * @param object $activity
     * @return void
     */
    private function bigbluebuttonbn_mform_add_block_schedule(&$mform, &$activity) {
        $mform->addElement('header', 'schedule', get_string('mod_form_block_schedule', 'bigbluebuttonbn'));
        if (isset($activity->openingtime) && $activity->openingtime != 0 ||
            isset($activity->closingtime) && $activity->closingtime != 0) {
            $mform->setExpanded('schedule');
        }
        $mform->addElement('date_time_selector', 'openingtime',
            get_string('mod_form_field_openingtime', 'bigbluebuttonbn'), array('optional' => true));
        $mform->setDefault('openingtime', 0);
        $mform->addElement('date_time_selector', 'closingtime',
            get_string('mod_form_field_closingtime', 'bigbluebuttonbn'), array('optional' => true));
        $mform->setDefault('closingtime', 0);
    }

    /**
     * Function for showing an element.
     *
     * @param object $mform
     * @param string $type
     * @param string $name
     * @param string $datatype
     * @param string $descriptionkey
     * @param string $defaultvalue
     * @param array $options
     * @param string $rule
     * @return void
     */
    private function bigbluebuttonbn_mform_add_element(&$mform, $type, $name, $datatype,
        $descriptionkey, $defaultvalue = null, $options = null, $rule = null) {
        if ($type === 'hidden' || $type === 'static') {
            $mform->addElement($type, $name, $defaultvalue);
            $mform->setType($name, $datatype);
            return;
        }
        $mform->addElement($type, $name, get_string($descriptionkey, 'bigbluebuttonbn'), $options);
        if (get_string_manager()->string_exists($descriptionkey . '_help', 'bigbluebuttonbn')) {
            $mform->addHelpButton($name, $descriptionkey, 'bigbluebuttonbn');
        }
        if (!empty($rule)) {
            $mform->addRule($name, $rule['message'], $rule['type'], $rule['rule'], $rule['validator']);
        }
        $mform->setDefault($name, $defaultvalue);
        $mform->setType($name, $datatype);
    }
}
